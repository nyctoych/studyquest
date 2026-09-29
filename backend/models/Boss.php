<?php
require_once __DIR__ . '/../config/database.php';

class Boss {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM bosses ORDER BY min_level ASC, difficulty ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bosses WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function getBossCategories(int $bossId): array {
        return match($bossId) {
            1 => [3],          // Cognitive Psychology
            2 => [4, 6, 16],   // Developmental & Personality
            3 => [2, 19],      // Biological Psychology & Sensation
            4 => [7, 8, 9],    // Abnormal & Clinical Psychology
            5 => [11, 13, 14], // Psychological Assessment, Statistics, Testing
            6 => [15],         // Ethics and Professional Practice
            default => [1],
        };
    }

    public function getQuestions(int $bossId, int $limit = 10, ?int $userId = null): array {
        $questions = [];

        // If userId provided, check if user has weak topics inside the boss's allowed categories
        if ($userId) {
            $allowedCats = self::getBossCategories($bossId);
            $inCats = implode(',', $allowedCats);

            // Check weak topics in these categories
            $weakTopicStmt = $this->db->prepare("
                SELECT utp.topic_id 
                FROM user_topic_performance utp
                JOIN question_topics qt ON utp.topic_id = qt.id
                WHERE utp.user_id = ? AND qt.category_id IN ({$inCats})
                ORDER BY utp.accuracy ASC
                LIMIT 1
            ");
            $weakTopicStmt->execute([$userId]);
            $weakTopicId = $weakTopicStmt->fetchColumn();

            if ($weakTopicId) {
                // Bias 40% of questions towards this weak topic from main questions table
                $biasCount = min(4, (int) round($limit * 0.4));
                $qStmt = $this->db->prepare("
                    SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty
                    FROM questions
                    WHERE category_id IN ({$inCats}) AND topic_id = ? AND status = 'approved'
                    ORDER BY RAND()
                    LIMIT ?
                ");
                $qStmt->execute([(int) $weakTopicId, $biasCount]);
                $questions = $qStmt->fetchAll();
            }
        }

        $remaining = $limit - count($questions);
        if ($remaining > 0) {
            $stmt = $this->db->prepare("SELECT * FROM boss_questions WHERE boss_id = ? ORDER BY RAND() LIMIT ?");
            $stmt->execute([$bossId, $remaining]);
            $bossQ = $stmt->fetchAll();
            $questions = array_merge($questions, $bossQ);
        }

        // If still needed, fill from questions table for allowed categories
        if (count($questions) < $limit) {
            $allowedCats = self::getBossCategories($bossId);
            $inCats = implode(',', $allowedCats);
            $needed = $limit - count($questions);
            $fallback = $this->db->query("
                SELECT id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty
                FROM questions
                WHERE category_id IN ({$inCats}) AND status = 'approved'
                ORDER BY RAND()
                LIMIT {$needed}
            ")->fetchAll();
            $questions = array_merge($questions, $fallback);
        }

        return $questions;
    }

    public function getQuestionById(int $questionId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM boss_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $row = $stmt->fetch();
        if (!$row) {
            $stmt2 = $this->db->prepare("SELECT * FROM questions WHERE id = ?");
            $stmt2->execute([$questionId]);
            $row = $stmt2->fetch();
        }
        return $row ?: null;
    }

    public function startAttempt(int $userId, int $bossId): int {
        $boss = $this->getById($bossId);
        if (!$boss) return 0;

        // Abandon any active attempt
        $stmt = $this->db->prepare("UPDATE boss_attempts SET status = 'abandoned' WHERE user_id = ? AND boss_id = ? AND status = 'active'");
        $stmt->execute([$userId, $bossId]);

        // Get shuffled question IDs
        $questions = $this->getQuestions($bossId, 10);
        $questionIds = array_column($questions, 'id');

        $stmt = $this->db->prepare("
            INSERT INTO boss_attempts (user_id, boss_id, boss_hp, player_hp, current_question_index, questions_order, status)
            VALUES (?, ?, ?, ?, 0, ?, 'active')
        ");
        $stmt->execute([$userId, $bossId, $boss['max_hp'], 100, json_encode($questionIds)]);
        return (int) $this->db->lastInsertId();
    }

    public function getActiveAttempt(int $userId, int $bossId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM boss_attempts WHERE user_id = ? AND boss_id = ? AND status = 'active' ORDER BY started_at DESC LIMIT 1");
        $stmt->execute([$userId, $bossId]);
        $attempt = $stmt->fetch();
        if ($attempt) {
            $attempt['questions_order'] = json_decode($attempt['questions_order'], true);
        }
        return $attempt ?: null;
    }

    public function submitAnswer(int $attemptId, int $userId, string $answer): array {
        $stmt = $this->db->prepare("SELECT * FROM boss_attempts WHERE id = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$attemptId, $userId]);
        $attempt = $stmt->fetch();

        if (!$attempt) {
            return ['success' => false, 'message' => 'No active battle found'];
        }

        $questionIds = json_decode($attempt['questions_order'], true);
        $currentIndex = (int) $attempt['current_question_index'];

        if ($currentIndex >= count($questionIds)) {
            return ['success' => false, 'message' => 'No more questions'];
        }

        $questionId = $questionIds[$currentIndex];
        $question = $this->getQuestionById($questionId);

        $isCorrect = strtolower($answer) === strtolower($question['correct_answer']);
        $damage = 10;

        $newBossHp = $attempt['boss_hp'];
        $newPlayerHp = $attempt['player_hp'];

        if ($isCorrect) {
            $newBossHp = max(0, $attempt['boss_hp'] - $damage);
        } else {
            $newPlayerHp = max(0, $attempt['player_hp'] - $damage);
        }

        $nextIndex = $currentIndex + 1;
        $status = 'active';

        if ($newBossHp <= 0) {
            $status = 'won';
        } elseif ($newPlayerHp <= 0) {
            $status = 'lost';
        } elseif ($nextIndex >= count($questionIds)) {
            // Out of questions, determine winner by HP
            $status = $newBossHp < $newPlayerHp ? 'won' : 'lost';
        }

        $completedAt = ($status !== 'active') ? 'NOW()' : 'NULL';

        $stmt = $this->db->prepare("
            UPDATE boss_attempts 
            SET boss_hp = ?, player_hp = ?, current_question_index = ?, status = ?, completed_at = " . ($status !== 'active' ? 'NOW()' : 'completed_at') . "
            WHERE id = ?
        ");
        $stmt->execute([$newBossHp, $newPlayerHp, $nextIndex, $status, $attemptId]);

        // Get next question if battle continues
        $nextQuestion = null;
        if ($status === 'active' && $nextIndex < count($questionIds)) {
            $nextQuestion = $this->getQuestionById($questionIds[$nextIndex]);
            // Remove correct answer from response
            unset($nextQuestion['correct_answer']);
        }

        return [
            'success' => true,
            'is_correct' => $isCorrect,
            'correct_answer' => $question['correct_answer'],
            'damage' => $damage,
            'boss_hp' => $newBossHp,
            'player_hp' => $newPlayerHp,
            'status' => $status,
            'next_question' => $nextQuestion,
            'question_number' => $currentIndex + 1,
            'total_questions' => count($questionIds),
        ];
    }

    public function getAttemptWithQuestion(int $attemptId, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM boss_attempts WHERE id = ? AND user_id = ?");
        $stmt->execute([$attemptId, $userId]);
        $attempt = $stmt->fetch();
        if (!$attempt) return null;

        $questionIds = json_decode($attempt['questions_order'], true);
        $currentIndex = (int) $attempt['current_question_index'];

        if ($currentIndex < count($questionIds)) {
            $question = $this->getQuestionById($questionIds[$currentIndex]);
            unset($question['correct_answer']); // Never send answer to client
            $attempt['current_question'] = $question;
        } else {
            $attempt['current_question'] = null;
        }

        $attempt['total_questions'] = count($questionIds);
        $attempt['question_number'] = $currentIndex + 1;
        $attempt['questions_order'] = null; // Don't expose order

        return $attempt;
    }

    public function getUserBossHistory(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT ba.*, b.name as boss_name, b.icon as boss_icon, b.difficulty
            FROM boss_attempts ba
            JOIN bosses b ON ba.boss_id = b.id
            WHERE ba.user_id = ?
            ORDER BY ba.started_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getDefeatedCount(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM boss_attempts WHERE user_id = ? AND status = 'won'");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
