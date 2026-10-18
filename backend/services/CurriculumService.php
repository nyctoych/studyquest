<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../database/CurriculumMigration.php';
require_once __DIR__ . '/../database/PsychiatricCurriculumMigration.php';

class CurriculumService {
    private PDO $db;
    private User $userModel;
    private Achievement $achievementModel;

    public function __construct() {
        PsychiatricCurriculumMigration::run();
        CurriculumMigration::run();
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->achievementModel = new Achievement();
    }

    /**
     * Ensure user domain and part progress records exist
     */
    public function ensureUserProgress(int $userId): void {
        $domains = $this->db->query("SELECT id FROM psychology_domains WHERE status = 'active' ORDER BY display_order ASC")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($domains as $domainId) {
            // Ensure domain progress row
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO user_domain_progress (user_id, domain_id, completed_parts, total_parts, percentage, mastery_score, is_completed)
                VALUES (?, ?, 0, 10, 0.00, 0.00, 0)
            ");
            $stmt->execute([$userId, $domainId]);

            // Ensure Part 1 is available
            $part1Stmt = $this->db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = 1");
            $part1Stmt->execute([$domainId]);
            $part1Id = $part1Stmt->fetchColumn();

            if ($part1Id) {
                $checkProg = $this->db->prepare("SELECT status FROM user_part_progress WHERE user_id = ? AND part_id = ?");
                $checkProg->execute([$userId, $part1Id]);
                $currStatus = $checkProg->fetchColumn();

                if (!$currStatus) {
                    $insertP1 = $this->db->prepare("
                        INSERT INTO user_part_progress (user_id, part_id, status, started_at)
                        VALUES (?, ?, 'available', NOW())
                    ");
                    $insertP1->execute([$userId, $part1Id]);
                }
            }

            // Sync unlocked status for subsequent parts based on previous part completion
            $parts = $this->db->prepare("SELECT id, part_number FROM psychology_parts WHERE domain_id = ? ORDER BY part_number ASC");
            $parts->execute([$domainId]);
            $allParts = $parts->fetchAll(PDO::FETCH_ASSOC);

            for ($i = 0; $i < count($allParts) - 1; $i++) {
                $currentPartId = $allParts[$i]['id'];
                $nextPartId = $allParts[$i + 1]['id'];

                $statusStmt = $this->db->prepare("SELECT status FROM user_part_progress WHERE user_id = ? AND part_id = ?");
                $statusStmt->execute([$userId, $currentPartId]);
                $cStatus = $statusStmt->fetchColumn();

                if (in_array($cStatus, ['completed', 'mastered'])) {
                    $nextStatusStmt = $this->db->prepare("SELECT status FROM user_part_progress WHERE user_id = ? AND part_id = ?");
                    $nextStatusStmt->execute([$userId, $nextPartId]);
                    $nStatus = $nextStatusStmt->fetchColumn();

                    if (!$nStatus || $nStatus === 'locked') {
                        $upNext = $this->db->prepare("
                            INSERT INTO user_part_progress (user_id, part_id, status, started_at)
                            VALUES (?, ?, 'available', NOW())
                            ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                        ");
                        $upNext->execute([$userId, $nextPartId]);
                    }
                }
            }
        }

        // Always synchronize user_domain_progress with actual completed parts from user_part_progress
        $syncStmt = $this->db->prepare("
            UPDATE user_domain_progress udp
            JOIN (
                SELECT 
                    p.domain_id,
                    COUNT(CASE WHEN upp.status IN ('completed', 'mastered') THEN 1 END) as actual_completed,
                    COALESCE(AVG(CASE WHEN upp.status IN ('completed', 'mastered') THEN upp.score END), 0) as avg_score
                FROM psychology_parts p
                LEFT JOIN user_part_progress upp ON p.id = upp.part_id AND upp.user_id = ?
                GROUP BY p.domain_id
            ) stats ON udp.domain_id = stats.domain_id AND udp.user_id = ?
            SET 
                udp.completed_parts = stats.actual_completed,
                udp.percentage = ROUND((stats.actual_completed / 10) * 100, 2),
                udp.mastery_score = ROUND(stats.avg_score, 2),
                udp.is_completed = IF(stats.actual_completed >= 10, 1, udp.is_completed),
                udp.completed_at = IF(stats.actual_completed >= 10 AND udp.completed_at IS NULL, NOW(), udp.completed_at)
        ");
        $syncStmt->execute([$userId, $userId]);
    }

    /**
     * Get all domains with user's progress
     */
    public function getDomains(int $userId): array {
        $this->ensureUserProgress($userId);

        $stmt = $this->db->prepare("
            SELECT 
                d.*,
                COALESCE(udp.completed_parts, 0) as completed_parts,
                COALESCE(udp.percentage, 0.00) as percentage,
                COALESCE(udp.mastery_score, 0.00) as mastery_score,
                COALESCE(udp.is_completed, 0) as is_completed,
                udp.completed_at
            FROM psychology_domains d
            LEFT JOIN user_domain_progress udp ON d.id = udp.domain_id AND udp.user_id = ?
            WHERE d.status = 'active'
            ORDER BY d.display_order ASC
        ");
        $stmt->execute([$userId]);
        $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute overall statistics
        $totalPartsCompleted = 0;
        $totalMasterySum = 0;
        $completedDomainsCount = 0;

        foreach ($domains as &$domain) {
            $domain['completed_parts'] = (int)$domain['completed_parts'];
            $domain['total_parts'] = (int)$domain['total_parts'];
            $domain['percentage'] = (float)$domain['percentage'];
            $domain['mastery_score'] = (float)$domain['mastery_score'];
            $domain['is_completed'] = (bool)$domain['is_completed'];

            $totalPartsCompleted += $domain['completed_parts'];
            $totalMasterySum += $domain['mastery_score'];
            if ($domain['is_completed']) {
                $completedDomainsCount++;
            }

            // Quick peek at active part in this domain
            $activePartStmt = $this->db->prepare("
                SELECT p.id, p.part_number, p.title, upp.status
                FROM psychology_parts p
                LEFT JOIN user_part_progress upp ON p.id = upp.part_id AND upp.user_id = ?
                WHERE p.domain_id = ?
                ORDER BY p.part_number ASC
            ");
            $activePartStmt->execute([$userId, $domain['id']]);
            $domainParts = $activePartStmt->fetchAll(PDO::FETCH_ASSOC);

            $currentPart = null;
            foreach ($domainParts as $dp) {
                if ($dp['status'] === 'in_progress' || $dp['status'] === 'available') {
                    $currentPart = $dp;
                    break;
                }
            }
            if (!$currentPart && count($domainParts) > 0) {
                $currentPart = end($domainParts);
            }
            $domain['current_part'] = $currentPart;
            $domain['first_part_id'] = $domainParts[0]['id'] ?? null;
        }

        $totalPartsCurriculum = count($domains) * 10;
        $overallPercentage = $totalPartsCurriculum > 0 ? round(($totalPartsCompleted / $totalPartsCurriculum) * 100, 1) : 0;
        $avgMastery = count($domains) > 0 ? round($totalMasterySum / count($domains), 1) : 0;

        return [
            'domains' => $domains,
            'summary' => [
                'total_parts_completed' => $totalPartsCompleted,
                'total_parts_curriculum' => $totalPartsCurriculum,
                'overall_percentage' => $overallPercentage,
                'completed_domains_count' => $completedDomainsCount,
                'total_domains' => count($domains),
                'average_mastery' => $avgMastery,
            ]
        ];
    }

    /**
     * Get domain details with all 10 parts and user status
     */
    public function getDomainDetails(int $userId, int $domainId): ?array {
        $this->ensureUserProgress($userId);

        $domainStmt = $this->db->prepare("
            SELECT d.*, 
                   COALESCE(udp.completed_parts, 0) as completed_parts,
                   COALESCE(udp.percentage, 0.00) as percentage,
                   COALESCE(udp.mastery_score, 0.00) as mastery_score,
                   COALESCE(udp.is_completed, 0) as is_completed,
                   udp.completed_at
            FROM psychology_domains d
            LEFT JOIN user_domain_progress udp ON d.id = udp.domain_id AND udp.user_id = ?
            WHERE d.id = ? AND d.status = 'active'
        ");
        $domainStmt->execute([$userId, $domainId]);
        $domain = $domainStmt->fetch(PDO::FETCH_ASSOC);

        if (!$domain) return null;

        $domain['completed_parts'] = (int)$domain['completed_parts'];
        $domain['total_parts'] = (int)$domain['total_parts'];
        $domain['percentage'] = (float)$domain['percentage'];
        $domain['mastery_score'] = (float)$domain['mastery_score'];
        $domain['is_completed'] = (bool)$domain['is_completed'];

        // Get parts
        $partsStmt = $this->db->prepare("
            SELECT 
                p.id, p.part_number, p.title, p.slug, p.description, p.part_type,
                p.xp_reward, p.coin_reward, p.passing_score,
                COALESCE(upp.status, 'locked') as user_status,
                COALESCE(upp.score, 0) as user_score,
                COALESCE(upp.attempts, 0) as attempts,
                upp.completed_at
            FROM psychology_parts p
            LEFT JOIN user_part_progress upp ON p.id = upp.part_id AND upp.user_id = ?
            WHERE p.domain_id = ?
            ORDER BY p.part_number ASC
        ");
        $partsStmt->execute([$userId, $domainId]);
        $parts = $partsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($parts as $idx => &$part) {
            $part['part_number'] = (int)$part['part_number'];
            $part['xp_reward'] = (int)$part['xp_reward'];
            $part['coin_reward'] = (int)$part['coin_reward'];
            $part['passing_score'] = (int)$part['passing_score'];
            $part['user_score'] = (int)$part['user_score'];
            $part['attempts'] = (int)$part['attempts'];

            // Status clarification & lock reason
            if ($part['user_status'] === 'locked') {
                if ($part['part_number'] === 10) {
                    $part['lock_reason'] = 'Complete Parts 1–9 to unlock the Domain Mastery Challenge';
                } else {
                    $part['lock_reason'] = "Complete Part " . ($part['part_number'] - 1) . " to unlock";
                }
            } else {
                $part['lock_reason'] = null;
            }
        }

        $domain['parts'] = $parts;
        return $domain;
    }

    /**
     * Get specific part content & questions for study / assessment
     */
    public function getPartDetails(int $userId, int $partId): ?array {
        $this->ensureUserProgress($userId);

        $stmt = $this->db->prepare("
            SELECT 
                p.*,
                d.name as domain_name,
                d.slug as domain_slug,
                d.icon as domain_icon,
                d.color as domain_color,
                COALESCE(upp.status, 'locked') as user_status,
                COALESCE(upp.score, 0) as user_score,
                COALESCE(upp.attempts, 0) as user_attempts,
                upp.completed_at
            FROM psychology_parts p
            JOIN psychology_domains d ON p.domain_id = d.id
            LEFT JOIN user_part_progress upp ON p.id = upp.part_id AND upp.user_id = ?
            WHERE p.id = ?
        ");
        $stmt->execute([$userId, $partId]);
        $part = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$part) return null;

        // Check if locked
        if ($part['user_status'] === 'locked') {
            return [
                'error' => 'locked',
                'message' => 'This part is locked. Complete the previous part first.',
                'part_number' => (int)$part['part_number'],
                'domain_name' => $part['domain_name']
            ];
        }

        // Mark as in_progress if currently available
        if ($part['user_status'] === 'available') {
            $upStatus = $this->db->prepare("UPDATE user_part_progress SET status = 'in_progress' WHERE user_id = ? AND part_id = ?");
            $upStatus->execute([$userId, $partId]);
            $part['user_status'] = 'in_progress';
        }

        // Decode JSON fields
        $part['key_concepts'] = json_decode($part['key_concepts'] ?? '[]', true) ?: [];
        $part['important_terms'] = json_decode($part['important_terms'] ?? '[]', true) ?: [];

        // Fetch questions
        $qStmt = $this->db->prepare("
            SELECT id, question, choice_a, choice_b, choice_c, choice_d, difficulty, is_scenario
            FROM learning_questions
            WHERE part_id = ? AND status = 'active'
            ORDER BY id ASC
        ");
        $qStmt->execute([$partId]);
        $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        $part['questions'] = $questions;
        $part['question_count'] = count($questions);

        // Fetch structured study content sections if available
        $secStmt = $this->db->prepare("
            SELECT scs.section_type, scs.title, scs.content, scs.source_reference
            FROM study_content_sections scs
            JOIN study_parts sp ON scs.part_id = sp.id
            WHERE sp.domain_id = ? AND sp.part_number = ?
            ORDER BY scs.display_order ASC
        ");
        $secStmt->execute([$part['domain_id'], $part['part_number']]);
        $part['sections'] = $secStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch neighboring parts for navigation
        $prevStmt = $this->db->prepare("SELECT id, title, part_number FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
        $prevStmt->execute([$part['domain_id'], (int)$part['part_number'] - 1]);
        $part['prev_part'] = $prevStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $nextStmt = $this->db->prepare("SELECT id, title, part_number FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
        $nextStmt->execute([$part['domain_id'], (int)$part['part_number'] + 1]);
        $part['next_part'] = $nextStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $part;
    }

    /**
     * Submit assessment for a learning part
     */
    public function submitPartAssessment(int $userId, int $partId, array $answers, int $timeSpent = 0): array {
        $partStmt = $this->db->prepare("
            SELECT p.*, d.id as domain_id, d.name as domain_name
            FROM psychology_parts p
            JOIN psychology_domains d ON p.domain_id = d.id
            WHERE p.id = ?
        ");
        $partStmt->execute([$partId]);
        $part = $partStmt->fetch(PDO::FETCH_ASSOC);

        if (!$part) {
            throw new Exception("Part not found");
        }

        // Fetch questions with correct answers
        $qStmt = $this->db->prepare("
            SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty
            FROM learning_questions
            WHERE part_id = ? AND status = 'active'
            ORDER BY id ASC
        ");
        $qStmt->execute([$partId]);
        $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($questions)) {
            throw new Exception("No active assessment questions found for this part");
        }

        $totalQuestions = count($questions);
        $correctCount = 0;
        $reviewDetails = [];

        foreach ($questions as $q) {
            $qid = $q['id'];
            $userAns = isset($answers[$qid]) ? strtoupper(trim($answers[$qid])) : '';
            $isCorrect = ($userAns === $q['correct_answer']);

            if ($isCorrect) {
                $correctCount++;
            }

            $reviewDetails[] = [
                'question_id' => $qid,
                'question' => $q['question'],
                'user_answer' => $userAns,
                'correct_answer' => $q['correct_answer'],
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'],
                'choices' => [
                    'A' => $q['choice_a'],
                    'B' => $q['choice_b'],
                    'C' => $q['choice_c'],
                    'D' => $q['choice_d'],
                ]
            ];
        }

        $score = (int)round(($correctCount / $totalQuestions) * 100);
        $passingScore = (int)$part['passing_score'];
        $isPassed = ($score >= $passingScore);
        $isMastered = ($score >= 90);

        // Record attempt
        $attemptStmt = $this->db->prepare("
            INSERT INTO part_attempts (user_id, part_id, score, is_passed, answers_summary, time_spent, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $attemptStmt->execute([
            $userId, $partId, $score, $isPassed ? 1 : 0, json_encode($reviewDetails), $timeSpent
        ]);

        $xpAwarded = 0;
        $coinsAwarded = 0;
        $newAchievements = [];
        $domainCompleted = false;
        $domainRewards = null;
        $nextPartUnlocked = null;

        if ($isPassed) {
            // Check previous status
            $prevProgStmt = $this->db->prepare("SELECT status, score, attempts FROM user_part_progress WHERE user_id = ? AND part_id = ?");
            $prevProgStmt->execute([$userId, $partId]);
            $prevProg = $prevProgStmt->fetch(PDO::FETCH_ASSOC);

            $wasAlreadyPassed = $prevProg && in_array($prevProg['status'], ['completed', 'mastered']);
            $newStatus = $isMastered ? 'mastered' : 'completed';
            $bestScore = $prevProg ? max((int)$prevProg['score'], $score) : $score;
            $newAttempts = $prevProg ? ((int)$prevProg['attempts'] + 1) : 1;

            $upPart = $this->db->prepare("
                INSERT INTO user_part_progress (user_id, part_id, status, score, attempts, completed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    status = IF(status = 'mastered', 'mastered', VALUES(status)),
                    score = GREATEST(score, VALUES(score)),
                    attempts = attempts + 1,
                    completed_at = NOW()
            ");
            $upPart->execute([$userId, $partId, $newStatus, $bestScore, $newAttempts]);

            // Base XP & Coins (Study Hall: 1,250 XP per reviewer)
            $baseXp = (int)($part['xp_reward'] ?: 1250);
            $baseCoins = (int)($part['coin_reward'] ?: 25);

            if (!$wasAlreadyPassed) {
                $xpAwarded = $baseXp;
                $coinsAwarded = $baseCoins;
                if ($isMastered) {
                    $xpAwarded += 25; // Mastery bonus
                    $coinsAwarded += 15;
                }
            } else {
                // Repeat practice bonus
                $xpAwarded = 15;
                $coinsAwarded = 5;
            }

            if ($xpAwarded > 0) {
                $this->userModel->addXP($userId, $xpAwarded);
                $this->userModel->addPoints($userId, $coinsAwarded);
                $this->userModel->updateStreak($userId);
            }

            // Unlock next part in this domain
            $currPartNum = (int)$part['part_number'];
            if ($currPartNum < 10) {
                $nextPartNum = $currPartNum + 1;
                $nextPStmt = $this->db->prepare("SELECT id, title, part_number FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
                $nextPStmt->execute([$part['domain_id'], $nextPartNum]);
                $nextPart = $nextPStmt->fetch(PDO::FETCH_ASSOC);

                if ($nextPart) {
                    $unlockStmt = $this->db->prepare("
                        INSERT INTO user_part_progress (user_id, part_id, status, started_at)
                        VALUES (?, ?, 'available', NOW())
                        ON DUPLICATE KEY UPDATE status = IF(status = 'locked', 'available', status)
                    ");
                    $unlockStmt->execute([$userId, $nextPart['id']]);
                    $nextPartUnlocked = $nextPart;
                }
            }

            // Update user_domain_progress
            $completedCountStmt = $this->db->prepare("
                SELECT COUNT(*) as completed_count, AVG(score) as avg_score
                FROM user_part_progress upp
                JOIN psychology_parts p ON upp.part_id = p.id
                WHERE upp.user_id = ? AND p.domain_id = ? AND upp.status IN ('completed', 'mastered')
            ");
            $completedCountStmt->execute([$userId, $part['domain_id']]);
            $domainStats = $completedCountStmt->fetch(PDO::FETCH_ASSOC);

            $completedParts = (int)($domainStats['completed_count'] ?? 0);
            $domainPercentage = round(($completedParts / 10) * 100, 2);
            $masteryScore = round((float)($domainStats['avg_score'] ?? 0), 2);

            // Check if domain is fully completed (all 10 parts)
            $domainRowStmt = $this->db->prepare("SELECT is_completed FROM user_domain_progress WHERE user_id = ? AND domain_id = ?");
            $domainRowStmt->execute([$userId, $part['domain_id']]);
            $prevDomainCompleted = (bool)$domainRowStmt->fetchColumn();

            $nowCompleted = ($completedParts >= 10);

            if ($nowCompleted && !$prevDomainCompleted) {
                $domainCompleted = true;
                $otherCompleted = (int)$this->db->query("
                    SELECT COUNT(*) FROM user_domain_progress 
                    WHERE user_id = {$userId} AND is_completed = 1 AND domain_id != {$part['domain_id']}
                ")->fetchColumn();
                $isLastDomain = ($otherCompleted >= 11);

                $domainXp = $isLastDomain ? 3700 : 3300;
                $domainRewards = [
                    'xp' => $domainXp,
                    'coins' => 300,
                    'title' => "Master of {$part['domain_name']}"
                ];
                $this->userModel->addXP($userId, $domainRewards['xp']);
                $this->userModel->addPoints($userId, $domainRewards['coins']);
            }

            $upDomainStmt = $this->db->prepare("
                UPDATE user_domain_progress
                SET completed_parts = ?,
                    percentage = ?,
                    mastery_score = ?,
                    is_completed = ?,
                    completed_at = IF(? = 1 AND completed_at IS NULL, NOW(), completed_at)
                WHERE user_id = ? AND domain_id = ?
            ");
            $upDomainStmt->execute([
                $completedParts, $domainPercentage, $masteryScore,
                $nowCompleted ? 1 : 0, $nowCompleted ? 1 : 0,
                $userId, $part['domain_id']
            ]);

            // Check achievements
            $totalPartsCompleted = (int)$this->db->query("
                SELECT COUNT(*) FROM user_part_progress 
                WHERE user_id = {$userId} AND status IN ('completed', 'mastered')
            ")->fetchColumn();

            $totalDomainsCompleted = (int)$this->db->query("
                SELECT COUNT(*) FROM user_domain_progress 
                WHERE user_id = {$userId} AND is_completed = 1
            ")->fetchColumn();

            $user = $this->userModel->findById($userId);
            $newAchievements = $this->achievementModel->checkAndUnlock($userId, [
                'curriculum_parts_completed' => $totalPartsCompleted,
                'curriculum_domains_completed' => $totalDomainsCompleted,
                'curriculum_domain_specific' => $part['domain_id'],
                'level' => $user['level'],
                'streak' => $user['streak'],
                'total_points' => $user['points'],
            ]);
        } else {
            // Failed attempt: update attempt count in user_part_progress
            $upAttempt = $this->db->prepare("
                INSERT INTO user_part_progress (user_id, part_id, status, attempts)
                VALUES (?, ?, 'in_progress', 1)
                ON DUPLICATE KEY UPDATE attempts = attempts + 1
            ");
            $upAttempt->execute([$userId, $partId]);
        }

        $freshUser = $this->userModel->findById($userId);

        return [
            'success' => true,
            'is_passed' => $isPassed,
            'is_mastered' => $isMastered,
            'score' => $score,
            'passing_score' => $passingScore,
            'correct_count' => $correctCount,
            'total_questions' => $totalQuestions,
            'xp_awarded' => $xpAwarded,
            'coins_awarded' => $coinsAwarded,
            'domain_completed' => $domainCompleted,
            'domain_rewards' => $domainRewards,
            'next_part' => $nextPartUnlocked,
            'new_achievements' => $newAchievements,
            'review' => $reviewDetails,
            'user' => $freshUser
        ];
    }

    /**
     * Get curriculum overview and recommendation
     */
    public function getOverview(int $userId): array {
        $domainsData = $this->getDomains($userId);
        $recommendation = $this->getTodayRecommendation($userId);

        // Fetch user badges
        $badgesStmt = $this->db->prepare("
            SELECT d.id, d.name, d.icon, d.color, udp.is_completed, udp.mastery_score, udp.completed_at
            FROM psychology_domains d
            JOIN user_domain_progress udp ON d.id = udp.domain_id AND udp.user_id = ?
            ORDER BY d.display_order ASC
        ");
        $badgesStmt->execute([$userId]);
        $domainBadges = $badgesStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'summary' => $domainsData['summary'],
            'domains' => $domainsData['domains'],
            'domain_badges' => $domainBadges,
            'today_recommendation' => $recommendation,
        ];
    }

    /**
     * Determine active part to study today
     */
    public function getTodayRecommendation(int $userId): ?array {
        $this->ensureUserProgress($userId);

        // 1. Check for in_progress part
        $stmt = $this->db->prepare("
            SELECT p.id as part_id, p.part_number, p.title as part_title, p.part_type,
                   d.id as domain_id, d.name as domain_name, d.icon as domain_icon, d.color as domain_color,
                   upp.status, upp.score
            FROM user_part_progress upp
            JOIN psychology_parts p ON upp.part_id = p.id
            JOIN psychology_domains d ON p.domain_id = d.id
            WHERE upp.user_id = ? AND upp.status = 'in_progress'
            ORDER BY d.display_order ASC, p.part_number ASC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rec) {
            $rec['reason'] = 'In Progress';
            return $rec;
        }

        // 2. Check for available part
        $stmt = $this->db->prepare("
            SELECT p.id as part_id, p.part_number, p.title as part_title, p.part_type,
                   d.id as domain_id, d.name as domain_name, d.icon as domain_icon, d.color as domain_color,
                   upp.status, upp.score
            FROM user_part_progress upp
            JOIN psychology_parts p ON upp.part_id = p.id
            JOIN psychology_domains d ON p.domain_id = d.id
            WHERE upp.user_id = ? AND upp.status = 'available'
            ORDER BY d.display_order ASC, p.part_number ASC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rec) {
            $rec['reason'] = 'Next Up on Learning Path';
            return $rec;
        }

        // 3. Fallback: First part of first domain
        $stmt = $this->db->prepare("
            SELECT p.id as part_id, p.part_number, p.title as part_title, p.part_type,
                   d.id as domain_id, d.name as domain_name, d.icon as domain_icon, d.color as domain_color,
                   'available' as status, 0 as score
            FROM psychology_parts p
            JOIN psychology_domains d ON p.domain_id = d.id
            WHERE d.status = 'active' AND p.part_number = 1
            ORDER BY d.display_order ASC
            LIMIT 1
        ");
        $stmt->execute();
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rec) {
            $rec['reason'] = 'Start Your Journey';
        }
        return $rec ?: null;
    }
}
