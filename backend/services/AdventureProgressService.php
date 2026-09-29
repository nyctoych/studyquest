<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Boss.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../database/AdventureMigration.php';
require_once __DIR__ . '/../database/CurriculumMigration.php';
require_once __DIR__ . '/../database/PsychiatricCurriculumMigration.php';

class AdventureProgressService {
    private PDO $db;
    private User $userModel;
    private Boss $bossModel;
    private Achievement $achievementModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->bossModel = new Boss();
        $this->achievementModel = new Achievement();

        // Ensure database tables and structure are verified
        PsychiatricCurriculumMigration::run();
        AdventureMigration::run();
        CurriculumMigration::run();
    }

    /**
     * Initializes default progress for a user.
     * Chapter 1 is AVAILABLE with Part 1 AVAILABLE.
     * Chapters 2+ are LOCKED.
     */
    public function initUserProgress(int $userId): void {
        // Find Chapter 1 (display_order = 1)
        $stmt = $this->db->query("SELECT id FROM psychology_domains WHERE display_order = 1 AND status = 'active' LIMIT 1");
        $ch1Id = $stmt->fetchColumn();

        if ($ch1Id) {
            // Ensure Chapter 1 has status 'available' if missing or locked
            $this->db->prepare("
                INSERT INTO adventure_chapter_progress (user_id, domain_id, status, started_at)
                VALUES (?, ?, 'available', NOW())
                ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
            ")->execute([$userId, $ch1Id]);

            // Ensure Part 1 of Chapter 1 is available
            $p1Stmt = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = 1 LIMIT 1");
            $p1Stmt->execute([$ch1Id]);
            $p1Id = $p1Stmt->fetchColumn();

            if ($p1Id) {
                $this->db->prepare("
                    INSERT INTO user_part_progress (user_id, part_id, status)
                    VALUES (?, ?, 'available')
                    ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                ")->execute([$userId, $p1Id]);
            }
        }
    }

    /**
     * Retrieves the Adventure Map chapters and user progression.
     */
    public function getAdventureMap(int $userId): array {
        $this->initUserProgress($userId);

        $stmt = $this->db->prepare("
            SELECT pd.id, pd.name, pd.slug, pd.description, pd.icon, pd.color,
                   pd.display_order, pd.total_parts, pd.status,
                   COALESCE(acp.status, 'locked') as chapter_status,
                   COALESCE(acp.parts_completed, 0) as parts_completed,
                   COALESCE(acp.boss_defeated, 0) as boss_defeated,
                   COALESCE(acp.boss_status, 'locked') as boss_status,
                   acp.started_at,
                   acp.completed_at,
                   ac.act_number, ac.act_title, ac.story_title as chapter_story_title,
                   ac.setting_description, ac.mind_seal_name, ac.fracture_shard_name,
                   b.id as boss_id, b.name as boss_name, b.story_title as boss_story_title,
                   b.portrait_asset as boss_portrait, b.idle_asset as boss_idle_asset,
                   b.max_hp as boss_max_hp, b.xp_reward as boss_xp, b.point_reward as boss_coins,
                   b.flavor_text as boss_flavor
            FROM psychology_domains pd
            LEFT JOIN adventure_chapters ac
                   ON ac.domain_id = pd.id
            LEFT JOIN adventure_chapter_progress acp
                   ON acp.domain_id = pd.id AND acp.user_id = ?
            LEFT JOIN bosses b
                   ON b.chapter_id = pd.id AND b.boss_order = 1
            WHERE pd.status = 'active'
            ORDER BY pd.display_order ASC
        ");
        $stmt->execute([$userId]);
        $chapters = $stmt->fetchAll();

        // Fetch user's story flags, recovered mind seals, and fracture shards
        $flagsStmt = $this->db->prepare("SELECT flag_key, flag_value FROM story_flags WHERE user_id = ?");
        $flagsStmt->execute([$userId]);
        $userFlags = $flagsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $recoveredSeals = [];
        $discoveredShards = [];
        $activeChapterId = null;
        $allCompleted = true;

        foreach ($chapters as $index => &$ch) {
            $order = (int)$ch['display_order'];
            $partsCompleted = (int)$ch['parts_completed'];
            $bossDefeated = (bool)$ch['boss_defeated'];
            $rawStatus = $ch['chapter_status'];

            // Chapter 1 is always available at minimum
            if ($order === 1 && $rawStatus === 'locked') {
                $rawStatus = 'available';
            }

            // Compute actual chapter status
            if ($bossDefeated || $rawStatus === 'completed' || $rawStatus === 'mastered') {
                $status = ($rawStatus === 'mastered') ? 'mastered' : 'completed';
                $bossStatus = 'defeated';
                if (!empty($ch['mind_seal_name'])) {
                    $recoveredSeals[] = [
                        'chapter_order' => $order,
                        'name' => $ch['mind_seal_name'],
                        'shard' => $ch['fracture_shard_name'] ?? ''
                    ];
                }
                if (!empty($ch['fracture_shard_name'])) {
                    $discoveredShards[] = [
                        'chapter_order' => $order,
                        'name' => $ch['fracture_shard_name']
                    ];
                }
            } elseif ($partsCompleted >= (int)$ch['total_parts']) {
                $status = 'boss_available';
                $bossStatus = 'available';
            } elseif ($partsCompleted > 0 || $rawStatus === 'in_progress') {
                $status = 'in_progress';
                $bossStatus = 'locked';
            } else {
                $status = $rawStatus; // 'available' or 'locked'
                $bossStatus = 'locked';
            }

            $ch['chapter_status'] = $status;
            $ch['boss_status']    = $bossStatus;
            $ch['parts_completed'] = $partsCompleted;
            $ch['boss_defeated']   = $bossDefeated;
            $ch['required_chapter_order'] = max(1, $order - 1);

            // Check if there is an active unlocked chapter to highlight
            if ($status !== 'completed' && $status !== 'mastered' && $status !== 'locked') {
                if ($activeChapterId === null) {
                    $activeChapterId = (int)$ch['id'];
                }
                $allCompleted = false;
            } elseif ($status === 'locked') {
                $allCompleted = false;
            }
        }

        // If all available chapters completed, active chapter is the last one
        if ($activeChapterId === null && !empty($chapters)) {
            $activeChapterId = (int)$chapters[count($chapters) - 1]['id'];
        }

        $blackArchiveUnlocked = ($allCompleted && count($chapters) >= 12) || isset($userFlags['black_archive_unlocked']);

        return [
            'success' => true,
            'chapters' => $chapters,
            'current_unlocked_chapter_id' => $activeChapterId,
            'all_chapters_completed' => $allCompleted,
            'recovered_mind_seals' => $recoveredSeals,
            'discovered_fracture_shards' => $discoveredShards,
            'total_mind_seals_count' => count($recoveredSeals),
            'black_archive_unlocked' => $blackArchiveUnlocked,
            'story_flags' => $userFlags,
        ];
    }

    /**
     * Retrieves chapter details, parts 1-10 progress, and boss state.
     * Rejects access if chapter is locked.
     */
    public function getChapterProgress(int $userId, int $chapterId): array {
        $this->initUserProgress($userId);

        $stmt = $this->db->prepare("
            SELECT pd.*,
                   COALESCE(acp.status, 'locked') as chapter_status,
                   COALESCE(acp.parts_completed, 0) as parts_completed,
                   COALESCE(acp.boss_defeated, 0) as boss_defeated,
                   COALESCE(acp.boss_status, 'locked') as boss_status,
                   acp.completed_at,
                   ac.act_number, ac.act_title, ac.story_title as chapter_story_title,
                   ac.setting_description, ac.story_intro as chapter_story_intro,
                   ac.story_outro as chapter_story_outro, ac.mind_seal_name, ac.fracture_shard_name
            FROM psychology_domains pd
            LEFT JOIN adventure_chapters ac ON ac.domain_id = pd.id
            LEFT JOIN adventure_chapter_progress acp
                   ON acp.domain_id = pd.id AND acp.user_id = ?
            WHERE pd.id = ?
        ");
        $stmt->execute([$userId, $chapterId]);
        $chapter = $stmt->fetch();

        if (!$chapter) {
            return [
                'success' => false,
                'message' => 'Chapter not found',
                'status_code' => 404,
            ];
        }

        // Chapter 1 is always accessible
        if ((int)$chapter['display_order'] === 1 && $chapter['chapter_status'] === 'locked') {
            $chapter['chapter_status'] = 'available';
        }

        // If chapter is locked, return chapter structure with all parts and boss locked
        // so the client can inspect the map while strictly disallowing play.
        if ($chapter['chapter_status'] === 'locked') {
            $prevOrder = max(1, (int)$chapter['display_order'] - 1);
            $prevStmt = $this->db->prepare("SELECT name FROM psychology_domains WHERE display_order = ? LIMIT 1");
            $prevStmt->execute([$prevOrder]);
            $prevName = $prevStmt->fetchColumn() ?: "Chapter {$prevOrder}";

            $partsStmt = $this->db->prepare("
                SELECT pp.id, pp.part_number, pp.title, pp.description, pp.part_type,
                       pp.xp_reward, pp.coin_reward, pp.passing_score,
                       'locked' as part_status,
                       0 as best_score,
                       0 as attempts,
                       aps.story_title, aps.narrative_phase, aps.speaker_title,
                       aps.story_intro, aps.enemy_name, aps.enemy_flavor, aps.enemy_max_hp,
                       aps.dialogue_speaker, aps.dialogue_text, aps.academic_connection,
                       aps.fracture_clue, aps.story_after
                FROM psychology_parts pp
                LEFT JOIN adventure_part_story aps ON aps.part_id = pp.id
                WHERE pp.domain_id = ? AND pp.is_published = 1
                ORDER BY pp.part_number ASC
            ");
            $partsStmt->execute([$chapterId]);
            $parts = $partsStmt->fetchAll();

            $bossStmt = $this->db->prepare("
                SELECT id, name, story_title, description, difficulty, max_hp, icon,
                       portrait_asset, idle_asset, image, story_intro, dialogue_intro,
                       dialogue_defeat, restoration_text, mind_seal_name, fracture_shard_name,
                       flavor_text, chapter_flavor, xp_reward, point_reward
                FROM bosses WHERE chapter_id = ? ORDER BY boss_order ASC LIMIT 1
            ");
            $bossStmt->execute([$chapterId]);
            $boss = $bossStmt->fetch() ?: null;
            if ($boss) {
                $boss['boss_status'] = 'locked';
            }

            $chapter['parts_completed'] = 0;
            $chapter['boss_defeated'] = 0;
            $chapter['boss_status'] = 'locked';
            $chapter['required_chapter_order'] = $prevOrder;
            $chapter['required_chapter_name'] = $prevName;

            return [
                'success' => true,
                'locked' => true,
                'chapter' => $chapter,
                'parts'   => $parts,
                'boss'    => $boss,
                'can_fight_boss' => false,
                'required_chapter_order' => $prevOrder,
                'required_chapter_name' => $prevName,
                'message' => "Complete {$prevName} to unlock this chapter.",
            ];
        }

        // Get all 10 parts with user progress
        $partsStmt = $this->db->prepare("
            SELECT pp.id, pp.part_number, pp.title, pp.description, pp.part_type,
                   pp.xp_reward, pp.coin_reward, pp.passing_score,
                   COALESCE(upp.status, 'locked') as part_status,
                   COALESCE(upp.score, 0) as best_score,
                   COALESCE(upp.attempts, 0) as attempts,
                   aps.story_title, aps.narrative_phase, aps.speaker_title,
                   aps.story_intro, aps.enemy_name, aps.enemy_flavor, aps.enemy_max_hp,
                   aps.dialogue_speaker, aps.dialogue_text, aps.academic_connection,
                   aps.fracture_clue, aps.story_after
            FROM psychology_parts pp
            LEFT JOIN user_part_progress upp ON upp.part_id = pp.id AND upp.user_id = ?
            LEFT JOIN adventure_part_story aps ON aps.part_id = pp.id
            WHERE pp.domain_id = ? AND pp.is_published = 1
            ORDER BY pp.part_number ASC
        ");
        $partsStmt->execute([$userId, $chapterId]);
        $parts = $partsStmt->fetchAll();

        // Enforce sequential part progression inside the chapter
        // Part 1 is always available if chapter is unlocked.
        // Part N (N > 1) is unlocked ONLY if Part N-1 is completed or mastered.
        $prevCompleted = true;
        $completedCount = 0;

        foreach ($parts as &$part) {
            $pNum = (int)$part['part_number'];
            $isComplete = in_array($part['part_status'], ['completed', 'mastered']);

            if ($isComplete) {
                $completedCount++;
                $prevCompleted = true;
            } elseif ($pNum === 1 || $prevCompleted) {
                if ($part['part_status'] === 'locked') {
                    $part['part_status'] = 'available';
                }
                $prevCompleted = false;
            } else {
                $part['part_status'] = 'locked';
                $prevCompleted = false;
            }
        }

        // Get boss for this chapter
        $bossStmt = $this->db->prepare("
            SELECT id, name, story_title, description, difficulty, max_hp, icon,
                   portrait_asset, idle_asset, image, story_intro, dialogue_intro,
                   dialogue_defeat, restoration_text, mind_seal_name, fracture_shard_name,
                   flavor_text, chapter_flavor, xp_reward, point_reward
            FROM bosses WHERE chapter_id = ? ORDER BY boss_order ASC LIMIT 1
        ");
        $bossStmt->execute([$chapterId]);
        $boss = $bossStmt->fetch() ?: null;

        $bossDefeated = (bool)$chapter['boss_defeated'];
        $canFightBoss = ($completedCount >= (int)$chapter['total_parts']);

        if ($boss) {
            if ($bossDefeated) {
                $boss['boss_status'] = 'defeated';
            } elseif ($canFightBoss) {
                $boss['boss_status'] = 'available';
            } else {
                $boss['boss_status'] = 'locked';
            }

            // Requirement 19: Boss questions must come from the CURRENT chapter/domain.
            // The boss should test multiple concepts from the chapter.
            $boss['questions'] = $this->getBossQuestionsForDomain($chapterId, 10);
        }

        // Determine updated chapter status
        if ($bossDefeated) {
            $computedChapterStatus = 'completed';
        } elseif ($canFightBoss) {
            $computedChapterStatus = 'boss_available';
        } elseif ($completedCount > 0) {
            $computedChapterStatus = 'in_progress';
        } else {
            $computedChapterStatus = 'available';
        }

        $chapter['chapter_status'] = $computedChapterStatus;
        $chapter['parts_completed'] = $completedCount;
        $chapter['boss_status'] = $boss ? $boss['boss_status'] : 'locked';

        return [
            'success' => true,
            'chapter' => $chapter,
            'parts'   => $parts,
            'boss'    => $boss,
            'can_fight_boss' => $canFightBoss,
        ];
    }

    /**
     * Requirement 19: Get boss questions strictly from the CURRENT chapter/domain.
     */
    public function getBossQuestionsForDomain(int $domainId, int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT id, question, choice_a, choice_b, choice_c, choice_d,
                   correct_answer, explanation, difficulty
            FROM questions
            WHERE domain_id = ? AND status = 'approved'
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$domainId, $limit]);
        $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($raw) < $limit) {
            $needed = $limit - count($raw);
            $lqStmt = $this->db->prepare("
                SELECT lq.id, lq.question, lq.choice_a, lq.choice_b, lq.choice_c, lq.choice_d,
                       lq.correct_answer, lq.explanation, lq.difficulty
                FROM learning_questions lq
                JOIN psychology_parts pp ON lq.part_id = pp.id
                WHERE pp.domain_id = ? AND lq.status = 'active'
                ORDER BY RAND()
                LIMIT ?
            ");
            $lqStmt->execute([$domainId, $needed]);
            $raw = array_merge($raw, $lqStmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return array_map(function($q) {
            return [
                'id' => $q['id'],
                'question' => $q['question'],
                'choices' => [
                    'A' => $q['choice_a'],
                    'B' => $q['choice_b'],
                    'C' => $q['choice_c'],
                    'D' => $q['choice_d'],
                ],
                'difficulty' => $q['difficulty'] ?? 'advanced',
                'correct_answer' => strtoupper($q['correct_answer']),
                'explanation' => $q['explanation'] ?? '',
            ];
        }, $raw);
    }

    /**
     * Validates and submits part answers, unlocks next part,
     * and sets chapter to boss_available if Part 10 is completed.
     */
    public function completePart(int $userId, int $partId, array $answers, int $timeSpent): array {
        // Get part & chapter info
        $partStmt = $this->db->prepare("
            SELECT pp.*, pd.id as domain_id, pd.display_order, pd.name as domain_name, pd.total_parts
            FROM psychology_parts pp
            JOIN psychology_domains pd ON pd.id = pp.domain_id
            WHERE pp.id = ?
        ");
        $partStmt->execute([$partId]);
        $part = $partStmt->fetch();

        if (!$part) {
            return ['success' => false, 'message' => 'Part not found', 'status_code' => 404];
        }

        $domainId = (int)$part['domain_id'];
        $partNum  = (int)$part['part_number'];

        // Enforce chapter unlock: Chapters beyond Chapter 1 must be unlocked
        if ((int)$part['display_order'] > 1) {
            $chProgStmt = $this->db->prepare("
                SELECT status FROM adventure_chapter_progress
                WHERE user_id = ? AND domain_id = ?
            ");
            $chProgStmt->execute([$userId, $domainId]);
            $chStatus = $chProgStmt->fetchColumn();
            if (!$chStatus || $chStatus === 'locked') {
                return [
                    'success' => false,
                    'locked'  => true,
                    'status_code' => 403,
                    'message' => "This chapter is locked. Complete previous chapters first.",
                ];
            }
        }

        // Enforce sequential part access: Part N requires Part N-1 to be completed
        if ($partNum > 1) {
            $prevStmt = $this->db->prepare("
                SELECT upp.status FROM psychology_parts pp
                JOIN user_part_progress upp ON upp.part_id = pp.id AND upp.user_id = ?
                WHERE pp.domain_id = ? AND pp.part_number = ?
            ");
            $prevStmt->execute([$userId, $domainId, $partNum - 1]);
            $prev = $prevStmt->fetch();
            if (!$prev || !in_array($prev['status'], ['completed', 'mastered'])) {
                return [
                    'success' => false,
                    'locked'  => true,
                    'status_code' => 403,
                    'message' => "Complete Part " . ($partNum - 1) . " first.",
                ];
            }
        }

        // Grade answers
        $totalQ = count($answers);
        $correct = 0;
        foreach ($answers as $answer) {
            $qStmt = $this->db->prepare("
                SELECT correct_answer FROM learning_questions WHERE id = ?
                UNION
                SELECT correct_answer FROM questions WHERE id = ?
                LIMIT 1
            ");
            $qStmt->execute([$answer['question_id'], $answer['question_id']]);
            $row = $qStmt->fetch();
            if ($row && strtoupper($row['correct_answer']) === strtoupper($answer['chosen'])) {
                $correct++;
            }
        }

        $score       = $totalQ > 0 ? round(($correct / $totalQ) * 100) : 0;
        $isPassed    = ($score >= (int)$part['passing_score']) || ($totalQ <= 3 && $correct >= 2) || ($score >= 60);
        $xpEarned    = $isPassed ? (int)$part['xp_reward'] : (int)($part['xp_reward'] * 0.25);
        $coinsEarned = $isPassed ? (int)$part['coin_reward'] : (int)($part['coin_reward'] * 0.1);

        // Record attempt
        $attemptStmt = $this->db->prepare("
            INSERT INTO adventure_attempts (user_id, part_id, score, correct_answers, total_questions, is_passed, time_spent, xp_earned, coins_earned)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $attemptStmt->execute([$userId, $partId, $score, $correct, $totalQ, $isPassed ? 1 : 0, $timeSpent, $xpEarned, $coinsEarned]);

        $bossUnlocked = false;

        if ($isPassed) {
            // Save part progress as completed
            $upsertStmt = $this->db->prepare("
                INSERT INTO user_part_progress (user_id, part_id, status, score, attempts, started_at, completed_at)
                VALUES (?, ?, 'completed', ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    status = IF(status NOT IN ('completed','mastered'), 'completed', status),
                    score = GREATEST(score, VALUES(score)),
                    attempts = attempts + 1,
                    completed_at = IF(status NOT IN ('completed','mastered'), NOW(), completed_at)
            ");
            $upsertStmt->execute([$userId, $partId, $score]);

            // Sequential progression: Unlock next part if within total parts
            if ($partNum < (int)$part['total_parts']) {
                $nextStmt = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = ? AND is_published = 1");
                $nextStmt->execute([$domainId, $partNum + 1]);
                $nextPartId = $nextStmt->fetchColumn();

                if ($nextPartId) {
                    $this->db->prepare("
                        INSERT INTO user_part_progress (user_id, part_id, status)
                        VALUES (?, ?, 'available')
                        ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                    ")->execute([$userId, $nextPartId]);
                }
            }

            // Count completed parts
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM user_part_progress upp
                JOIN psychology_parts pp ON pp.id = upp.part_id
                WHERE upp.user_id = ? AND pp.domain_id = ?
                  AND upp.status IN ('completed', 'mastered')
            ");
            $countStmt->execute([$userId, $domainId]);
            $completedParts = (int)$countStmt->fetchColumn();

            // Check if all parts completed -> unlock Chapter Boss
            if ($completedParts >= (int)$part['total_parts']) {
                $bossUnlocked = true;
                $this->db->prepare("
                    INSERT INTO adventure_chapter_progress (user_id, domain_id, status, parts_completed, boss_status, started_at)
                    VALUES (?, ?, 'boss_available', ?, 'available', NOW())
                    ON DUPLICATE KEY UPDATE
                        parts_completed = VALUES(parts_completed),
                        status = IF(status != 'completed' AND status != 'mastered', 'boss_available', status),
                        boss_status = IF(boss_defeated = 1, 'defeated', 'available')
                ")->execute([$userId, $domainId, $completedParts]);
            } else {
                $this->db->prepare("
                    INSERT INTO adventure_chapter_progress (user_id, domain_id, status, parts_completed, started_at)
                    VALUES (?, ?, 'in_progress', ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        parts_completed = VALUES(parts_completed),
                        status = IF(status NOT IN ('boss_available', 'completed', 'mastered'), 'in_progress', status)
                ")->execute([$userId, $domainId, $completedParts]);
            }

            // Award rewards
            $this->userModel->addXP($userId, $xpEarned);
            $this->userModel->addCoins($userId, $coinsEarned);

        } else {
            // Attempted but not passed
            $this->db->prepare("
                INSERT INTO user_part_progress (user_id, part_id, status, score, attempts, started_at)
                VALUES (?, ?, 'in_progress', ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    score = GREATEST(score, VALUES(score)),
                    attempts = attempts + 1
            ")->execute([$userId, $partId, $score]);

            $this->userModel->addXP($userId, $xpEarned);
            $this->userModel->addCoins($userId, $coinsEarned);
        }

        $user = $this->userModel->findById($userId);

        return [
            'success'       => true,
            'score'         => $score,
            'correct'       => $correct,
            'total'         => $totalQ,
            'is_passed'     => $isPassed,
            'xp_earned'     => $xpEarned,
            'coins_earned'  => $coinsEarned,
            'boss_unlocked' => $bossUnlocked,
            'new_level'     => (int)$user['level'],
            'new_xp'        => (int)$user['xp'],
            'new_coins'     => (int)($user['coins'] ?? 0),
        ];
    }

    /**
     * Checks if Parts 1-10 are completed for a chapter.
     */
    public function checkBossAvailability(int $userId, int $chapterId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT pp.part_number) as completed_count,
                   (SELECT total_parts FROM psychology_domains WHERE id = ?) as total_needed
            FROM user_part_progress upp
            JOIN psychology_parts pp ON pp.id = upp.part_id
            WHERE upp.user_id = ? AND pp.domain_id = ?
              AND upp.status IN ('completed', 'mastered')
              AND pp.is_published = 1
        ");
        $stmt->execute([$chapterId, $userId, $chapterId]);
        $row = $stmt->fetch();

        if (!$row) return false;
        $completed = (int)$row['completed_count'];
        $needed    = (int)($row['total_needed'] ?: 10);

        return $completed >= $needed;
    }

    /**
     * Processes Chapter Boss Victory with full transaction safety,
     * duplicate request protection, single-claim rewards,
     * and automatic unlock of next chapter.
     */
    public function completeBoss(int $userId, int $chapterId, int $bossId, array $battleData): array {
        // Step 1: Validate Chapter
        $chStmt = $this->db->prepare("SELECT * FROM psychology_domains WHERE id = ?");
        $chStmt->execute([$chapterId]);
        $chapter = $chStmt->fetch();

        if (!$chapter) {
            return ['success' => false, 'message' => 'Chapter not found', 'status_code' => 404];
        }

        // Step 1b: Verify chapter is unlocked
        if ((int)$chapter['display_order'] > 1) {
            $chProgStmt = $this->db->prepare("SELECT status FROM adventure_chapter_progress WHERE user_id = ? AND domain_id = ?");
            $chProgStmt->execute([$userId, $chapterId]);
            $chStatus = $chProgStmt->fetchColumn();
            if (!$chStatus || $chStatus === 'locked') {
                return [
                    'success' => false,
                    'status_code' => 403,
                    'message' => 'Chapter is locked. Complete previous chapters first.',
                ];
            }
        }

        // Step 2: Validate Boss
        $bossStmt = $this->db->prepare("SELECT * FROM bosses WHERE id = ?");
        $bossStmt->execute([$bossId]);
        $boss = $bossStmt->fetch();

        if (!$boss) {
            // Fallback to chapter's primary boss
            $bossStmt = $this->db->prepare("SELECT * FROM bosses WHERE chapter_id = ? ORDER BY boss_order ASC LIMIT 1");
            $bossStmt->execute([$chapterId]);
            $boss = $bossStmt->fetch();
        }

        if (!$boss) {
            return ['success' => false, 'message' => 'Chapter Boss not found', 'status_code' => 404];
        }

        // Step 3: Validate Parts 1-10 are complete
        if (!$this->checkBossAvailability($userId, $chapterId)) {
            return [
                'success' => false,
                'status_code' => 400,
                'code' => 'PARTS_INCOMPLETE',
                'message' => 'Parts 1 to 10 must be completed before boss victory can be recorded.',
            ];
        }

        // Step 4: Validate battle authenticity
        $playerHp = (int)($battleData['player_hp'] ?? 100);
        $bossHp   = (int)($battleData['boss_hp'] ?? 0);
        if ($bossHp > 0 || $playerHp <= 0) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Invalid battle outcome. Boss was not defeated.',
            ];
        }

        // Begin transaction for completion
        $this->db->beginTransaction();

        try {
            // Check existing completion & rewards
            $checkStmt = $this->db->prepare("
                SELECT status, boss_defeated, completed_at 
                FROM adventure_chapter_progress 
                WHERE user_id = ? AND domain_id = ?
                FOR UPDATE
            ");
            $checkStmt->execute([$userId, $chapterId]);
            $progress = $checkStmt->fetch();

            $rewardStmt = $this->db->prepare("
                SELECT id FROM chapter_rewards 
                WHERE user_id = ? AND chapter_id = ?
            ");
            $rewardStmt->execute([$userId, $chapterId]);
            $rewardClaimed = (bool)$rewardStmt->fetchColumn();

            $isAlreadyCompleted = $rewardClaimed || ($progress && (
                $progress['status'] === 'completed' || 
                $progress['status'] === 'mastered' || 
                (int)$progress['boss_defeated'] === 1
            ));

            // Record victory attempt in boss_attempts
            $this->db->prepare("
                INSERT INTO boss_attempts (user_id, boss_id, boss_hp, player_hp, current_question_index, status, completed_at)
                VALUES (?, ?, 0, ?, 0, 'won', NOW())
            ")->execute([$userId, $boss['id'], max(1, $playerHp)]);

            // Determine next chapter
            $currentOrder = (int)$chapter['display_order'];
            $nextOrder = $currentOrder + 1;
            $nextStmt = $this->db->prepare("SELECT id, name, display_order FROM psychology_domains WHERE display_order = ? AND status = 'active' LIMIT 1");
            $nextStmt->execute([$nextOrder]);
            $nextChapter = $nextStmt->fetch();

            // ── DUPLICATE / REPLAY HANDLING ───────────────────────────
            if ($isAlreadyCompleted) {
                // Ensure next chapter is available even on replay
                if ($nextChapter) {
                    $this->db->prepare("
                        INSERT INTO adventure_chapter_progress (user_id, domain_id, status, started_at)
                        VALUES (?, ?, 'available', NOW())
                        ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                    ")->execute([$userId, $nextChapter['id']]);

                    // Ensure Part 1 of next chapter is available
                    $p1Next = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = 1 LIMIT 1");
                    $p1Next->execute([$nextChapter['id']]);
                    $p1Id = $p1Next->fetchColumn();
                    if ($p1Id) {
                        $this->db->prepare("
                            INSERT INTO user_part_progress (user_id, part_id, status)
                            VALUES (?, ?, 'available')
                            ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                        ")->execute([$userId, $p1Id]);
                    }
                }

                // Ensure story flags are recorded even on replay
                $flagStmt = $this->db->prepare("
                    INSERT INTO story_flags (user_id, flag_key, flag_value)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE flag_value = VALUES(flag_value), updated_at = NOW()
                ");
                if (!empty($boss['mind_seal_name'])) {
                    $flagStmt->execute([$userId, "mind_seal_ch_{$currentOrder}", $boss['mind_seal_name']]);
                }
                if (!empty($boss['fracture_shard_name'])) {
                    $flagStmt->execute([$userId, "fracture_shard_ch_{$currentOrder}", $boss['fracture_shard_name']]);
                }
                $flagStmt->execute([$userId, "chapter_{$currentOrder}_restored", '1']);

                $this->db->commit();
                $user = $this->userModel->findById($userId);

                return [
                    'success'                 => true,
                    'chapter_completed'       => true,
                    'is_first_completion'     => false,
                    'already_completed'       => true,
                    'completed_chapter_id'    => $chapterId,
                    'completed_chapter_order' => $currentOrder,
                    'completed_chapter_name'  => $chapter['name'],
                    'boss_defeated'           => true,
                    'mind_seal_recovered'     => $boss['mind_seal_name'] ?? '',
                    'fracture_shard_recovered'=> $boss['fracture_shard_name'] ?? '',
                    'restoration_text'        => $boss['restoration_text'] ?? '',
                    'dialogue_defeat'         => $boss['dialogue_defeat'] ?? '',
                    'rewards' => [
                        'xp'     => 0,
                        'coins'  => 0,
                        'points' => 0,
                    ],
                    'new_achievements'        => [],
                    'next_chapter_id'         => $nextChapter ? (int)$nextChapter['id'] : null,
                    'next_chapter_order'      => $nextChapter ? (int)$nextChapter['display_order'] : null,
                    'next_chapter_name'       => $nextChapter ? $nextChapter['name'] : null,
                    'next_chapter_unlocked'   => ($nextChapter !== false),
                    'all_chapters_completed'  => ($nextChapter === false),
                    'black_archive_unlocked'  => ($nextChapter === false || $currentOrder === 12),
                    'user'                    => $user,
                ];
            }

            // ── FIRST COMPLETION ──────────────────────────────────────
            $xpReward   = (int)($boss['xp_reward'] ?: 500);
            $coinReward = (int)($boss['point_reward'] ?: 300);

            // 1. Mark Chapter as COMPLETED
            $this->db->prepare("
                INSERT INTO adventure_chapter_progress 
                    (user_id, domain_id, status, parts_completed, boss_defeated, boss_status, completed_at)
                VALUES (?, ?, 'completed', 10, 1, 'defeated', NOW())
                ON DUPLICATE KEY UPDATE
                    status = 'completed',
                    boss_defeated = 1,
                    boss_status = 'defeated',
                    completed_at = COALESCE(completed_at, NOW())
            ")->execute([$userId, $chapterId]);

            // 2. Protect Rewards: Record in chapter_rewards
            $this->db->prepare("
                INSERT INTO chapter_rewards (user_id, chapter_id, xp_awarded, coins_awarded, awarded_at)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([$userId, $chapterId, $xpReward, $coinReward]);

            // 3. Record Story Flags (Mind Seals, Fracture Shards, Restoration)
            $flagStmt = $this->db->prepare("
                INSERT INTO story_flags (user_id, flag_key, flag_value)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE flag_value = VALUES(flag_value), updated_at = NOW()
            ");
            if (!empty($boss['mind_seal_name'])) {
                $flagStmt->execute([$userId, "mind_seal_ch_{$currentOrder}", $boss['mind_seal_name']]);
            }
            if (!empty($boss['fracture_shard_name'])) {
                $flagStmt->execute([$userId, "fracture_shard_ch_{$currentOrder}", $boss['fracture_shard_name']]);
            }
            $flagStmt->execute([$userId, "chapter_{$currentOrder}_restored", '1']);

            // 4. Grant XP & Coins
            $this->userModel->addXP($userId, $xpReward);
            $this->userModel->addCoins($userId, $coinReward);

            // 5. Record Chronicle Entry
            $this->db->prepare("
                INSERT INTO chronicle_posts (user_id, post_type, title, body, metadata, is_public)
                VALUES (?, 'chapter_complete', ?, ?, ?, 1)
            ")->execute([
                $userId,
                "Conquered Chapter {$currentOrder}: {$chapter['name']}",
                "Restoration of {$boss['name']} complete. " . ($boss['mind_seal_name'] ? "Recovered {$boss['mind_seal_name']}." : "Pathway opened."),
                json_encode([
                    'chapter_id' => $chapterId,
                    'chapter_name' => $chapter['name'],
                    'boss_name' => $boss['name'],
                    'mind_seal' => $boss['mind_seal_name'] ?? '',
                    'xp' => $xpReward,
                    'coins' => $coinReward
                ])
            ]);

            // 6. Check & Unlock Achievements
            $bossWins = $this->bossModel->getDefeatedCount($userId);
            $user = $this->userModel->findById($userId);
            $newAchievements = $this->achievementModel->checkAndUnlock($userId, [
                'bosses_defeated' => $bossWins,
                'level'           => $user['level'],
            ]);

            // 7. Automatic Next Chapter Unlock
            $nextChapterUnlocked = false;
            $allChaptersCompleted = false;

            if ($nextChapter) {
                // Set next chapter status to AVAILABLE
                $this->db->prepare("
                    INSERT INTO adventure_chapter_progress (user_id, domain_id, status, started_at)
                    VALUES (?, ?, 'available', NOW())
                    ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                ")->execute([$userId, $nextChapter['id']]);

                // Set Part 1 of next chapter to AVAILABLE
                $p1Next = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = 1 LIMIT 1");
                $p1Next->execute([$nextChapter['id']]);
                $p1Id = $p1Next->fetchColumn();
                if ($p1Id) {
                    $this->db->prepare("
                        INSERT INTO user_part_progress (user_id, part_id, status)
                        VALUES (?, ?, 'available')
                        ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                    ")->execute([$userId, $p1Id]);
                }

                $nextChapterUnlocked = true;
            } else {
                $allChaptersCompleted = true;
                $flagStmt->execute([$userId, "black_archive_unlocked", '1']);
            }

            // Commit transaction
            $this->db->commit();

            $freshUser = $this->userModel->findById($userId);

            return [
                'success'                 => true,
                'chapter_completed'       => true,
                'is_first_completion'     => true,
                'already_completed'       => false,
                'completed_chapter_id'    => $chapterId,
                'completed_chapter_order' => $currentOrder,
                'completed_chapter_name'  => $chapter['name'],
                'boss_name'               => $boss['name'],
                'boss_defeated'           => true,
                'mind_seal_recovered'     => $boss['mind_seal_name'] ?? '',
                'fracture_shard_recovered'=> $boss['fracture_shard_name'] ?? '',
                'restoration_text'        => $boss['restoration_text'] ?? '',
                'dialogue_defeat'         => $boss['dialogue_defeat'] ?? '',
                'rewards' => [
                    'xp'     => $xpReward,
                    'coins'  => $coinReward,
                    'points' => $coinReward,
                ],
                'new_achievements'        => $newAchievements,
                'next_chapter_id'         => $nextChapter ? (int)$nextChapter['id'] : null,
                'next_chapter_order'      => $nextChapter ? (int)$nextChapter['display_order'] : null,
                'next_chapter_name'       => $nextChapter ? $nextChapter['name'] : null,
                'next_chapter_unlocked'   => $nextChapterUnlocked,
                'all_chapters_completed'  => $allChaptersCompleted,
                'black_archive_unlocked'  => ($allChaptersCompleted || $currentOrder === 12),
                'user'                    => $freshUser,
            ];

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log("completeBoss error: " . $e->getMessage());
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Failed to record chapter completion: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Requirement 39 & 40: Returns full story state and saved progression.
     */
    public function getStoryState(int $userId): array {
        $flagsStmt = $this->db->prepare("SELECT flag_key, flag_value FROM story_flags WHERE user_id = ?");
        $flagsStmt->execute([$userId]);
        $flags = $flagsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $chaptersStmt = $this->db->prepare("
            SELECT ac.chapter_number, ac.act_number, ac.act_title, ac.story_title,
                   ac.mind_seal_name, ac.fracture_shard_name,
                   COALESCE(acp.boss_defeated, 0) as boss_defeated,
                   COALESCE(acp.status, 'locked') as status
            FROM adventure_chapters ac
            LEFT JOIN adventure_chapter_progress acp
                   ON acp.domain_id = ac.domain_id AND acp.user_id = ?
            ORDER BY ac.chapter_number ASC
        ");
        $chaptersStmt->execute([$userId]);
        $chapters = $chaptersStmt->fetchAll();

        $recoveredSeals = [];
        $discoveredShards = [];
        $completedCount = 0;

        foreach ($chapters as $c) {
            if ((int)$c['boss_defeated'] === 1) {
                $completedCount++;
                if (!empty($c['mind_seal_name'])) {
                    $recoveredSeals[] = [
                        'chapter' => $c['chapter_number'],
                        'name' => $c['mind_seal_name'],
                        'act' => $c['act_title']
                    ];
                }
                if (!empty($c['fracture_shard_name'])) {
                    $discoveredShards[] = [
                        'chapter' => $c['chapter_number'],
                        'name' => $c['fracture_shard_name']
                    ];
                }
            }
        }

        $blackArchiveUnlocked = ($completedCount >= 12) || isset($flags['black_archive_unlocked']);

        return [
            'success' => true,
            'completed_chapters' => $completedCount,
            'total_chapters' => count($chapters),
            'recovered_mind_seals' => $recoveredSeals,
            'discovered_fracture_shards' => $discoveredShards,
            'black_archive_unlocked' => $blackArchiveUnlocked,
            'creator_defeated' => isset($flags['creator_defeated']),
            'flags' => $flags,
        ];
    }

    /**
     * Requirement 21 & 22: The Black Archive & The Creator encounter data.
     */
    public function getBlackArchiveData(int $userId): array {
        $storyState = $this->getStoryState($userId);

        // Fetch user's current phase progress from story_flags
        $phaseFlag = $storyState['flags']['creator_phase'] ?? '1';
        $currentPhase = (int)$phaseFlag;

        $phases = [
            1 => [
                'phase' => 1,
                'name' => 'Phase I — The Author',
                'title' => 'Architect of the Fractured Mindrealm',
                'description' => 'The Creator manifests as the meticulous scholar who attempted to rigidly categorize all human consciousness into impermeable compartments.',
                'boss_hp' => 500,
                'asset' => 'assets/bosses/hat/2happyhat.png',
                'animated' => 'assets/bosses/hat/2HappyHat.gif',
                'dialogue_intro' => 'I sought only perfection! Order! Certainty! Why must human minds resist the sacred boundaries of taxonomy?!',
                'dialogue_outro' => 'The boundaries shatter... but the Fracture remains woven into the very fabric of the archives!',
            ],
            2 => [
                'phase' => 2,
                'name' => 'Phase II — The Fracture',
                'title' => 'The Void of Forced Certainty',
                'description' => 'The anomaly itself manifests, fueled by the impossible paradox of trying to eliminate uncertainty from organic human psychology.',
                'boss_hp' => 650,
                'asset' => 'assets/bosses/shadows/2happyshad.png',
                'animated' => 'assets/bosses/shadows/2happyshadows.gif',
                'dialogue_intro' => 'When you force complex souls into rigid boxes, the edges tear! Behold the fury of the Fracture!',
                'dialogue_outro' => 'The darkness recoils... yet the guardians you saved still call from the echoes!',
            ],
            3 => [
                'phase' => 3,
                'name' => 'Phase III — The Lost Guardians',
                'title' => 'Echoes of the Twelve Realms',
                'description' => 'The Creator channels the lingering echoes of all twelve corrupted guardians in a tempest of psychiatric complexity.',
                'boss_hp' => 800,
                'asset' => 'assets/bosses/purplebrain/2happypurplebrain.png',
                'animated' => 'assets/bosses/purplebrain/happy.gif',
                'dialogue_intro' => 'Recall the Cradle! The City of Warnings! The Palace of Identity! Can you synthesize all twelve doctrines against me?!',
                'dialogue_outro' => 'The guardians stand with you... their harmony rejects my control!',
            ],
            4 => [
                'phase' => 4,
                'name' => 'Phase IV — Final Mastery',
                'title' => 'The Creator Unmasked',
                'description' => 'The climactic final test of psychiatric licensure mastery. Defeating the Creator liberates the Mindrealm forever.',
                'boss_hp' => 1000,
                'asset' => 'assets/bosses/brain/2happy-brain.png',
                'animated' => 'assets/bosses/brain/2happybrain.gif',
                'dialogue_intro' => 'Mindkeeper... if you truly understand human suffering, prove that empathy and diagnostic science can co-exist without tyranny!',
                'dialogue_outro' => 'I see it now... The mind cannot be caged. It must be nurtured, understood, and healed. The Mindrealm is restored.',
            ],
        ];

        // Draw 10 comprehensive mastery questions across all 12 domains
        $questionsStmt = $this->db->prepare("
            SELECT q.id, q.question, q.choice_a, q.choice_b, q.choice_c, q.choice_d,
                   q.correct_answer, q.explanation, q.difficulty, pd.name as domain_name
            FROM questions q
            LEFT JOIN psychology_parts pp ON pp.id = q.part_id
            LEFT JOIN psychology_domains pd ON pd.id = COALESCE(q.domain_id, pp.domain_id)
            WHERE q.status = 'approved' AND q.difficulty IN ('advanced', 'board_exam', 'intermediate')
            ORDER BY RAND()
            LIMIT 10
        ");
        $questionsStmt->execute();
        $questions = array_map(function($q) {
            return [
                'id' => $q['id'],
                'question' => $q['question'],
                'domain' => $q['domain_name'],
                'choices' => [
                    'A' => $q['choice_a'],
                    'B' => $q['choice_b'],
                    'C' => $q['choice_c'],
                    'D' => $q['choice_d'],
                ],
                'difficulty' => $q['difficulty'] ?? 'board_exam',
                'correct_answer' => strtoupper($q['correct_answer']),
                'explanation' => $q['explanation'] ?? '',
            ];
        }, $questionsStmt->fetchAll(PDO::FETCH_ASSOC));

        return [
            'success' => true,
            'unlocked' => $storyState['black_archive_unlocked'],
            'current_phase' => $currentPhase,
            'phase_data' => $phases[$currentPhase] ?? $phases[1],
            'all_phases' => $phases,
            'questions' => $questions,
            'creator_defeated' => $storyState['creator_defeated'],
        ];
    }

    /**
     * Requirement 22: Complete a Phase of The Creator battle.
     */
    public function completeBlackArchivePhase(int $userId, int $phase, array $battleData): array {
        $playerHp = (int)($battleData['player_hp'] ?? 100);
        $bossHp   = (int)($battleData['boss_hp'] ?? 0);

        if ($bossHp > 0 || $playerHp <= 0) {
            return ['success' => false, 'message' => 'Phase battle incomplete.'];
        }

        $nextPhase = $phase + 1;
        $isFinal = ($phase >= 4);

        $flagStmt = $this->db->prepare("
            INSERT INTO story_flags (user_id, flag_key, flag_value)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE flag_value = VALUES(flag_value), updated_at = NOW()
        ");

        if ($isFinal) {
            $flagStmt->execute([$userId, 'creator_defeated', '1']);
            $flagStmt->execute([$userId, 'creator_phase', 'completed']);

            // Grant apex reward
            $this->userModel->addXP($userId, 2500);
            $this->userModel->addCoins($userId, 1500);

            // Record chronicle
            $this->db->prepare("
                INSERT INTO chronicle_posts (user_id, post_type, title, body, metadata, is_public)
                VALUES (?, 'game_mastery', 'The Mindrealm Restored: The Creator Defeated', 'Through diagnostic mastery and empathetic understanding, the Fracture has been healed.', '{}', 1)
            ")->execute([$userId]);
        } else {
            $flagStmt->execute([$userId, 'creator_phase', (string)$nextPhase]);
        }

        return [
            'success' => true,
            'phase_completed' => $phase,
            'next_phase' => $isFinal ? null : $nextPhase,
            'is_final_victory' => $isFinal,
            'rewards' => $isFinal ? ['xp' => 2500, 'coins' => 1500] : ['xp' => 250, 'coins' => 150],
        ];
    }
}
