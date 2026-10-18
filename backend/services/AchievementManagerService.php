<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../database/AchievementSystemMigration.php';

class AchievementManagerService {
    private static ?self $instance = null;
    private PDO $db;
    private User $userModel;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        // Ensure migrations have executed
        AchievementSystemMigration::run();
    }

    /**
     * Authoritative Server-side evaluation of user eligibility across all 32 milestones.
     */
    public function syncUserProgress(int $userId): void {
        // 1. Fetch user core stats
        $userStmt = $this->db->prepare("SELECT id, level, xp, points, coins, wins FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;

        $userLevel = (int)$user['level'];

        // 2. Fetch Adventure Chapter completion status (domain_id 1 to 12)
        $chStmt = $this->db->prepare("
            SELECT pd.display_order as chapter_num, acp.boss_defeated, acp.status
            FROM psychology_domains pd
            LEFT JOIN adventure_chapter_progress acp ON acp.domain_id = pd.id AND acp.user_id = ?
            WHERE pd.status = 'active'
        ");
        $chStmt->execute([$userId]);
        $chaptersCompleted = [];
        while ($row = $chStmt->fetch(PDO::FETCH_ASSOC)) {
            $chNum = (int)$row['chapter_num'];
            $isComplete = ((int)$row['boss_defeated'] === 1) || in_array($row['status'], ['completed', 'mastered'], true);
            $chaptersCompleted[$chNum] = $isComplete;
        }

        // 3. Adventure Campaign full completion
        $flagStmt = $this->db->prepare("
            SELECT flag_key, flag_value FROM story_flags
            WHERE user_id = ? AND flag_key IN ('final_boss_completed', 'adventure_complete', 'creator_defeated')
        ");
        $flagStmt->execute([$userId]);
        $flags = $flagStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $finalBossCleared = (($flags['final_boss_completed'] ?? '') === '1') ||
                            (($flags['adventure_complete'] ?? '') === '1') ||
                            (($flags['creator_defeated'] ?? '') === '1');
        
        $all12ChaptersCleared = (count(array_filter($chaptersCompleted)) >= 12);
        $adventureCampaignComplete = $finalBossCleared || $all12ChaptersCleared;

        // 4. Study Hall Subject completion status (domain_id 1 to 12)
        $shStmt = $this->db->prepare("
            SELECT pd.display_order as domain_num, udp.completed_parts, udp.total_parts, udp.is_completed
            FROM psychology_domains pd
            LEFT JOIN user_domain_progress udp ON udp.domain_id = pd.id AND udp.user_id = ?
            WHERE pd.status = 'active'
        ");
        $shStmt->execute([$userId]);
        $subjectsCompleted = [];
        while ($row = $shStmt->fetch(PDO::FETCH_ASSOC)) {
            $domNum = (int)$row['domain_num'];
            $isDone = ((int)($row['is_completed'] ?? 0) === 1) || ((int)($row['completed_parts'] ?? 0) >= 10);
            $subjectsCompleted[$domNum] = $isDone;
        }
        $studyHallAllComplete = (count(array_filter($subjectsCompleted)) >= 12);

        // 5. Multiplayer victories
        $mpStmt = $this->db->prepare("
            SELECT COUNT(mp.id) FROM match_players mp
            JOIN matches m ON m.id = mp.match_id
            WHERE mp.user_id = ? AND mp.outcome = 'won' AND m.status = 'completed'
        ");
        $mpStmt->execute([$userId]);
        $actualMpWins = (int)$mpStmt->fetchColumn();
        $totalWins = max($actualMpWins, (int)($user['wins'] ?? 0));

        // 6. Fetch all 32 milestones from DB
        $milestones = $this->db->query("SELECT * FROM achievement_milestones ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 7. Upsert user unlock status
        $unlockStmt = $this->db->prepare("
            INSERT INTO user_milestone_progress (user_id, milestone_id, is_unlocked, unlocked_at, share_token)
            VALUES (?, ?, 1, NOW(), ?)
            ON DUPLICATE KEY UPDATE
                is_unlocked = 1,
                unlocked_at = COALESCE(unlocked_at, NOW()),
                share_token = COALESCE(share_token, VALUES(share_token))
        ");

        foreach ($milestones as $m) {
            $isEligible = false;
            $type = $m['condition_type'];
            $val = (int)$m['condition_value'];

            switch ($type) {
                case 'adventure_chapter':
                    $isEligible = !empty($chaptersCompleted[$val]);
                    break;
                case 'adventure_campaign':
                    $isEligible = $adventureCampaignComplete;
                    break;
                case 'studyhall_domain':
                    $isEligible = !empty($subjectsCompleted[$val]);
                    break;
                case 'studyhall_all':
                    $isEligible = $studyHallAllComplete;
                    break;
                case 'level':
                    $isEligible = ($userLevel >= $val);
                    break;
                case 'multiplayer_wins':
                    $isEligible = ($totalWins >= $val);
                    break;
            }

            if ($isEligible) {
                $token = bin2hex(random_bytes(16));
                $unlockStmt->execute([$userId, (int)$m['id'], $token]);
            }
        }
    }

    /**
     * Retrieve full Achievement Hall state with 30 cards, 32 milestones, and summary metrics.
     */
    public function getAchievementHallData(int $userId): array {
        // Synchronize server-validated unlocks first
        $this->syncUserProgress($userId);

        // Fetch user progress for current context
        $userStmt = $this->db->prepare("SELECT level, wins FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: ['level' => 1, 'wins' => 0];

        $mpStmt = $this->db->prepare("
            SELECT COUNT(mp.id) FROM match_players mp
            JOIN matches m ON m.id = mp.match_id
            WHERE mp.user_id = ? AND mp.outcome = 'won' AND m.status = 'completed'
        ");
        $mpStmt->execute([$userId]);
        $mpWins = max((int)$mpStmt->fetchColumn(), (int)$user['wins']);

        $chaptersCompletedCount = $this->db->prepare("
            SELECT COUNT(DISTINCT pd.id)
            FROM psychology_domains pd
            JOIN adventure_chapter_progress acp ON acp.domain_id = pd.id AND acp.user_id = ?
            WHERE acp.boss_defeated = 1 OR acp.status IN ('completed', 'mastered')
        ");
        $chaptersCompletedCount->execute([$userId]);
        $chCount = (int)$chaptersCompletedCount->fetchColumn();

        $shCountStmt = $this->db->prepare("
            SELECT COUNT(DISTINCT pd.id)
            FROM psychology_domains pd
            JOIN user_domain_progress udp ON udp.domain_id = pd.id AND udp.user_id = ?
            WHERE udp.is_completed = 1 OR udp.completed_parts >= 10
        ");
        $shCountStmt->execute([$userId]);
        $shCount = (int)$shCountStmt->fetchColumn();

        // Query all cards with their milestones and user progress
        $sql = "
            SELECT 
                c.id as card_id,
                c.card_key,
                c.title as card_title,
                c.subtitle as card_subtitle,
                c.category,
                c.icon as card_icon,
                c.display_order as card_order,
                m.id as milestone_id,
                m.milestone_key,
                m.title as milestone_title,
                m.description as milestone_desc,
                m.condition_type,
                m.condition_value,
                m.xp_reward,
                m.coins_reward,
                m.display_order as milestone_order,
                COALESCE(ump.is_unlocked, 0) as is_unlocked,
                ump.unlocked_at,
                COALESCE(ump.is_claimed, 0) as is_claimed,
                ump.claimed_at,
                ump.share_token
            FROM achievement_cards c
            JOIN achievement_milestones m ON m.card_id = c.id
            LEFT JOIN user_milestone_progress ump ON ump.milestone_id = m.id AND ump.user_id = ?
            ORDER BY c.display_order ASC, m.display_order ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cardsMap = [];
        $totalMilestones = 0;
        $claimedCount = 0;
        $unclaimedCount = 0;
        $lockedCount = 0;
        $totalXpClaimed = 0;
        $totalCoinsClaimed = 0;

        foreach ($rows as $r) {
            $cardId = (int)$r['card_id'];
            $milestoneId = (int)$r['milestone_id'];
            $condType = $r['condition_type'];
            $condVal = (int)$r['condition_value'];

            $isUnlocked = (int)$r['is_unlocked'] === 1;
            $isClaimed = (int)$r['is_claimed'] === 1;

            // Compute current numerical progress
            $currProgress = 0;
            $targetProgress = $condVal;

            switch ($condType) {
                case 'adventure_chapter':
                    $currProgress = $isUnlocked ? 1 : 0;
                    $targetProgress = 1;
                    break;
                case 'adventure_campaign':
                    $currProgress = min(12, $chCount);
                    $targetProgress = 12;
                    break;
                case 'studyhall_domain':
                    $currProgress = $isUnlocked ? 1 : 0;
                    $targetProgress = 1;
                    break;
                case 'studyhall_all':
                    $currProgress = min(12, $shCount);
                    $targetProgress = 12;
                    break;
                case 'level':
                    $currProgress = min($condVal, (int)$user['level']);
                    $targetProgress = $condVal;
                    break;
                case 'multiplayer_wins':
                    $currProgress = min($condVal, $mpWins);
                    $targetProgress = $condVal;
                    break;
            }

            // Milestone Status
            $status = 'locked';
            if ($isClaimed) {
                $status = 'claimed';
                $claimedCount++;
                $totalXpClaimed += (int)$r['xp_reward'];
                $totalCoinsClaimed += (int)$r['coins_reward'];
            } elseif ($isUnlocked) {
                $status = 'unlocked'; // Claim Available!
                $unclaimedCount++;
            } else {
                $lockedCount++;
            }
            $totalMilestones++;

            // Ensure share token exists
            $shareToken = $r['share_token'];
            if (!$shareToken) {
                $shareToken = bin2hex(random_bytes(16));
                $insToken = $this->db->prepare("
                    INSERT INTO user_milestone_progress (user_id, milestone_id, is_unlocked, share_token)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE share_token = COALESCE(share_token, VALUES(share_token))
                ");
                $insToken->execute([$userId, $milestoneId, $isUnlocked ? 1 : 0, $shareToken]);
            }

            $milestoneObj = [
                'id'                => $milestoneId,
                'milestone_key'     => $r['milestone_key'],
                'title'             => $r['milestone_title'],
                'description'       => $r['milestone_desc'],
                'condition_type'    => $condType,
                'condition_value'   => $condVal,
                'xp_reward'         => (int)$r['xp_reward'],
                'coins_reward'      => (int)$r['coins_reward'],
                'current_progress'  => $currProgress,
                'target_progress'   => $targetProgress,
                'progress_percent'  => $targetProgress > 0 ? min(100, round(($currProgress / $targetProgress) * 100)) : 0,
                'status'            => $status,
                'is_unlocked'       => $isUnlocked,
                'unlocked_at'       => $r['unlocked_at'],
                'is_claimed'        => $isClaimed,
                'claimed_at'        => $r['claimed_at'],
                'share_token'       => $shareToken
            ];

            if (!isset($cardsMap[$cardId])) {
                $cardsMap[$cardId] = [
                    'id'            => $cardId,
                    'card_key'      => $r['card_key'],
                    'title'         => $r['card_title'],
                    'subtitle'      => $r['card_subtitle'],
                    'category'      => $r['category'],
                    'icon'          => $r['card_icon'],
                    'display_order' => (int)$r['card_order'],
                    'milestones'    => []
                ];
            }

            $cardsMap[$cardId]['milestones'][] = $milestoneObj;
        }

        $cardsList = array_values($cardsMap);
        foreach ($cardsList as &$card) {
            $card['is_multi_milestone'] = count($card['milestones']) > 1;
            
            // Overall card status
            $allClaimed = true;
            $anyAvailable = false;
            foreach ($card['milestones'] as $m) {
                if ($m['status'] !== 'claimed') $allClaimed = false;
                if ($m['status'] === 'unlocked') $anyAvailable = true;
            }
            if ($allClaimed) {
                $card['status'] = 'claimed';
            } elseif ($anyAvailable) {
                $card['status'] = 'unlocked';
            } else {
                $card['status'] = 'locked';
            }
        }

        $summary = [
            'total_cards'           => count($cardsList), // Exactly 30
            'total_milestones'      => $totalMilestones, // Exactly 32
            'claimed_count'         => $claimedCount,
            'unclaimed_count'       => $unclaimedCount,
            'locked_count'          => $lockedCount,
            'completion_percentage' => $totalMilestones > 0 ? round(($claimedCount / $totalMilestones) * 100, 1) : 0,
            'total_xp_claimed'      => $totalXpClaimed,
            'total_coins_claimed'   => $totalCoinsClaimed
        ];

        return [
            'summary' => $summary,
            'cards'   => $cardsList
        ];
    }

    /**
     * Atomically claim a single milestone reward.
     * Prevents duplicate claims, race conditions, and client spoofing.
     */
    public function claimMilestone(int $userId, int $milestoneId): array {
        // Ensure latest progress is reconciled
        $this->syncUserProgress($userId);

        $this->db->beginTransaction();
        try {
            // Lock user milestone progress row
            $stmt = $this->db->prepare("
                SELECT ump.*, m.title, m.xp_reward, m.coins_reward, m.condition_type, m.condition_value
                FROM achievement_milestones m
                LEFT JOIN user_milestone_progress ump ON ump.milestone_id = m.id AND ump.user_id = ?
                WHERE m.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$userId, $milestoneId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $this->db->rollBack();
                return ['success' => false, 'status_code' => 404, 'message' => 'Milestone not found.'];
            }

            if ((int)($row['is_unlocked'] ?? 0) !== 1) {
                $this->db->rollBack();
                return ['success' => false, 'status_code' => 400, 'message' => 'This milestone has not been unlocked yet.'];
            }

            if ((int)($row['is_claimed'] ?? 0) === 1) {
                $this->db->rollBack();
                return ['success' => false, 'status_code' => 400, 'message' => 'This milestone reward has already been claimed.'];
            }

            $xpReward = (int)$row['xp_reward'];
            $coinsReward = (int)$row['coins_reward'];

            // 1. Mark milestone as claimed
            $updateUmp = $this->db->prepare("
                UPDATE user_milestone_progress
                SET is_claimed = 1, claimed_at = NOW()
                WHERE user_id = ? AND milestone_id = ?
            ");
            $updateUmp->execute([$userId, $milestoneId]);

            // 2. Fetch and lock user record for atomic XP and coin grant
            $uStmt = $this->db->prepare("SELECT id, xp, level, coins FROM users WHERE id = ? FOR UPDATE");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            $prevLevel = (int)$user['level'];
            $newXp = (int)$user['xp'] + $xpReward;
            $newLevel = $this->userModel->calculateLevel($newXp);
            $leveledUp = $newLevel > $prevLevel;
            $newCoinsBalance = (int)$user['coins'] + $coinsReward;

            // 3. Grant XP, Level, Coins & Points atomically
            $updUser = $this->db->prepare("
                UPDATE users 
                SET xp = ?, level = ?, coins = coins + ?, points = points + ? 
                WHERE id = ?
            ");
            $updUser->execute([$newXp, $newLevel, $coinsReward, $coinsReward, $userId]);

            // 4. Log in coin_transactions
            $txStmt = $this->db->prepare("
                INSERT INTO coin_transactions (user_id, amount, type, source, reference_id, description, balance_after, created_at)
                VALUES (?, ?, 'earn', 'achievement', ?, ?, ?, NOW())
            ");
            $txStmt->execute([
                $userId,
                $coinsReward,
                $milestoneId,
                "Achievement claimed: " . $row['title'],
                $newCoinsBalance
            ]);

            $this->db->commit();

            return [
                'success'         => true,
                'milestone_id'    => $milestoneId,
                'milestone_title' => $row['title'],
                'xp_reward'       => $xpReward,
                'coins_reward'    => $coinsReward,
                'user'            => [
                    'xp'             => $newXp,
                    'level'          => $newLevel,
                    'leveled_up'     => $leveledUp,
                    'previous_level' => $prevLevel,
                    'coins'          => $newCoinsBalance
                ]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('claimMilestone error: ' . $e->getMessage());
            return ['success' => false, 'status_code' => 500, 'message' => 'Failed to claim achievement reward: ' . $e->getMessage()];
        }
    }

    /**
     * Atomically claim ALL unlocked and unclaimed milestone rewards at once.
     * Reconciles user progress, locks candidate rows, awards total XP and Coins,
     * logs individual coin transactions, and returns a detailed batch summary.
     */
    public function claimAllMilestones(int $userId): array {
        // Ensure latest server-side progress is reconciled
        $this->syncUserProgress($userId);

        $this->db->beginTransaction();
        try {
            // Find all unlocked and unclaimed milestones for this user with row locking
            $stmt = $this->db->prepare("
                SELECT ump.milestone_id, m.title, m.xp_reward, m.coins_reward
                FROM user_milestone_progress ump
                JOIN achievement_milestones m ON m.id = ump.milestone_id
                WHERE ump.user_id = ?
                  AND ump.is_unlocked = 1
                  AND (ump.is_claimed = 0 OR ump.is_claimed IS NULL)
                FOR UPDATE
            ");
            $stmt->execute([$userId]);
            $claimable = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($claimable)) {
                $this->db->rollBack();
                return [
                    'success'       => false,
                    'status_code'   => 400,
                    'message'       => 'No claimable achievements available at this time.',
                    'claimed_count' => 0
                ];
            }

            $totalXpReward = 0;
            $totalCoinsReward = 0;
            $milestoneIds = [];
            $claimedDetails = [];

            foreach ($claimable as $row) {
                $mId = (int)$row['milestone_id'];
                $xp = (int)$row['xp_reward'];
                $coins = (int)$row['coins_reward'];

                $totalXpReward += $xp;
                $totalCoinsReward += $coins;
                $milestoneIds[] = $mId;
                $claimedDetails[] = [
                    'id'           => $mId,
                    'title'        => $row['title'],
                    'xp_reward'    => $xp,
                    'coins_reward' => $coins
                ];
            }

            // 1. Mark all eligible milestones as claimed
            $inPlaceholders = implode(',', array_fill(0, count($milestoneIds), '?'));
            $updateUmp = $this->db->prepare("
                UPDATE user_milestone_progress
                SET is_claimed = 1, claimed_at = NOW()
                WHERE user_id = ? AND milestone_id IN ($inPlaceholders)
            ");
            $params = array_merge([$userId], $milestoneIds);
            $updateUmp->execute($params);

            // 2. Fetch and lock user record for atomic XP and coin grant
            $uStmt = $this->db->prepare("SELECT id, xp, level, coins FROM users WHERE id = ? FOR UPDATE");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            $prevLevel = (int)$user['level'];
            $newXp = (int)$user['xp'] + $totalXpReward;
            $newLevel = $this->userModel->calculateLevel($newXp);
            $leveledUp = $newLevel > $prevLevel;
            $newCoinsBalance = (int)$user['coins'] + $totalCoinsReward;

            // 3. Grant XP, Level, Coins & Points atomically
            $updUser = $this->db->prepare("
                UPDATE users 
                SET xp = ?, level = ?, coins = coins + ?, points = points + ? 
                WHERE id = ?
            ");
            $updUser->execute([$newXp, $newLevel, $totalCoinsReward, $totalCoinsReward, $userId]);

            // 4. Log in coin_transactions for each claimed milestone
            $txStmt = $this->db->prepare("
                INSERT INTO coin_transactions (user_id, amount, type, source, reference_id, description, balance_after, created_at)
                VALUES (?, ?, 'earn', 'achievement', ?, ?, ?, NOW())
            ");
            $runningBalance = (int)$user['coins'];
            foreach ($claimedDetails as $c) {
                $runningBalance += $c['coins_reward'];
                $txStmt->execute([
                    $userId,
                    $c['coins_reward'],
                    $c['id'],
                    "Batch achievement claim: " . $c['title'],
                    $runningBalance
                ]);
            }

            $this->db->commit();

            return [
                'success'            => true,
                'claimed_count'      => count($claimedDetails),
                'total_xp'           => $totalXpReward,
                'total_coins'        => $totalCoinsReward,
                'claimed_milestones' => $claimedDetails,
                'user'               => [
                    'xp'             => $newXp,
                    'level'          => $newLevel,
                    'leveled_up'     => $leveledUp,
                    'previous_level' => $prevLevel,
                    'coins'          => $newCoinsBalance
                ]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('claimAllMilestones error: ' . $e->getMessage());
            return [
                'success'     => false,
                'status_code' => 500,
                'message'     => 'Failed to claim all achievement rewards: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve public achievement share data by secure token.
     * Only returns non-sensitive public display information.
     */
    public function getPublicShareData(string $token): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                u.nickname,
                u.username,
                u.level as player_level,
                c.title as card_title,
                c.subtitle as card_subtitle,
                c.category,
                c.icon,
                m.title as milestone_title,
                m.description,
                m.xp_reward,
                m.coins_reward,
                ump.unlocked_at,
                ump.claimed_at,
                ump.is_unlocked,
                ump.is_claimed,
                ump.share_token
            FROM user_milestone_progress ump
            JOIN users u ON u.id = ump.user_id
            JOIN achievement_milestones m ON m.id = ump.milestone_id
            JOIN achievement_cards c ON c.id = m.card_id
            WHERE ump.share_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        return [
            'player_name'       => $row['nickname'] ?: $row['username'],
            'player_level'      => (int)$row['player_level'],
            'card_title'        => $row['card_title'],
            'card_subtitle'     => $row['card_subtitle'],
            'milestone_title'   => $row['milestone_title'],
            'description'       => $row['description'],
            'category'          => $row['category'],
            'icon'              => $row['icon'],
            'xp_reward'         => (int)$row['xp_reward'],
            'coins_reward'      => (int)$row['coins_reward'],
            'unlocked_at'       => $row['unlocked_at'],
            'claimed_at'        => $row['claimed_at'],
            'is_claimed'        => (bool)$row['is_claimed'],
            'share_token'       => $row['share_token']
        ];
    }
}
