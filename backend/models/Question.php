<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AdaptiveDifficultyService.php';

class Question {
    private PDO $db;
    private AdaptiveDifficultyService $adaptiveService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->adaptiveService = new AdaptiveDifficultyService();
    }

    /**
     * Get all official psychology categories with question statistics.
     */
    public function getCategories(): array {
        $sql = "
            SELECT qc.*, 
                   COUNT(q.id) AS total_questions,
                   SUM(CASE WHEN q.difficulty = 'beginner' THEN 1 ELSE 0 END) AS beginner_count,
                   SUM(CASE WHEN q.difficulty = 'intermediate' THEN 1 ELSE 0 END) AS intermediate_count,
                   SUM(CASE WHEN q.difficulty = 'advanced' THEN 1 ELSE 0 END) AS advanced_count,
                   SUM(CASE WHEN q.difficulty = 'board_exam' THEN 1 ELSE 0 END) AS board_exam_count
            FROM question_categories qc
            LEFT JOIN questions q ON qc.id = q.category_id AND q.status = 'approved'
            GROUP BY qc.id
            ORDER BY qc.id ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get subtopics for a given category.
     */
    public function getTopics(int $categoryId): array {
        $stmt = $this->db->prepare("SELECT * FROM question_topics WHERE category_id = ? ORDER BY name ASC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    /**
     * Fetch level-adaptive psychology questions using the 10-step adaptive engine.
     */
    public function getAdaptiveQuestionsForUser(
        int $userId, 
        int $count = 10, 
        ?int $categoryId = null, 
        ?int $topicId = null, 
        string $mode = 'practice'
    ): array {
        return $this->adaptiveService->selectAdaptiveQuestions($userId, $count, $categoryId, $topicId, $mode);
    }

    /**
     * Fetch questions for multiplayer 1v1 battles.
     * Both players receive the exact same question set matching match difficulty.
     */
    public function getForBattle(int $count = 10, ?int $categoryId = null, ?string $difficulty = null): array {
        $where = ["q.status = 'approved'"];
        $params = [];

        if ($categoryId) {
            $where[] = "q.category_id = ?";
            $params[] = $categoryId;
        }

        if ($difficulty) {
            $where[] = "q.difficulty = ?";
            $params[] = $difficulty;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT q.id, q.category_id, q.topic_id, q.question, 
                   q.choice_a, q.choice_b, q.choice_c, q.choice_d,
                   q.correct_answer, q.difficulty, q.explanation, q.source_reference,
                   qc.name AS category_name, qc.icon AS category_icon, qc.color AS category_color
            FROM questions q
            JOIN question_categories qc ON q.category_id = qc.id
            WHERE {$whereClause}
            ORDER BY RAND()
            LIMIT ?
        ";

        $params[] = $count;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll();

        // Fallback without difficulty if not enough questions in specific tier
        if (count($questions) < $count) {
            $needed = $count - count($questions);
            $fallbackSql = "
                SELECT q.id, q.category_id, q.topic_id, q.question, 
                       q.choice_a, q.choice_b, q.choice_c, q.choice_d,
                       q.correct_answer, q.difficulty, q.explanation, q.source_reference,
                       qc.name AS category_name, qc.icon AS category_icon, qc.color AS category_color
                FROM questions q
                JOIN question_categories qc ON q.category_id = qc.id
                WHERE q.status = 'approved' " . ($categoryId ? "AND q.category_id = {$categoryId}" : "") . "
                ORDER BY RAND()
                LIMIT {$needed}
            ";
            $fallback = $this->db->query($fallbackSql)->fetchAll();
            $questions = array_merge($questions, $fallback);
        }

        return $questions;
    }

    /**
     * Strip correct answer keys from question array for client safety.
     */
    public function stripAnswers(array $questions): array {
        return array_map(function($q) {
            return [
                'id'             => $q['id'],
                'category_id'    => $q['category_id'],
                'topic_id'       => $q['topic_id'] ?? null,
                'category_name'  => $q['category_name'] ?? '',
                'category_icon'  => $q['category_icon'] ?? '🧠',
                'category_color' => $q['category_color'] ?? '#6366f1',
                'question_text'  => $q['question'],
                'difficulty'     => $q['difficulty'],
                'choices'        => [
                    ['key' => 'A', 'text' => $q['choice_a']],
                    ['key' => 'B', 'text' => $q['choice_b']],
                    ['key' => 'C', 'text' => $q['choice_c']],
                    ['key' => 'D', 'text' => $q['choice_d']],
                ]
            ];
        }, $questions);
    }

    /**
     * Map student level to primary cognitive difficulty tier.
     */
    public function mapLevelToDifficulty(int $level): string {
        return $this->adaptiveService->getBaselineDifficulty($level);
    }

    /**
     * Record a student question attempt using the adaptive service.
     */
    public function recordAttempt(int $userId, int $questionId, bool $isCorrect, int $responseTime = 0, ?string $battleId = null): array {
        return $this->adaptiveService->recordAttempt($userId, $questionId, $isCorrect, $responseTime, $battleId);
    }

    /**
     * Detect a student's weak areas across psychology categories.
     */
    public function getUserWeakAreas(int $userId): array {
        $readiness = $this->adaptiveService->getFullReadinessProfile($userId);
        $weakest = $readiness['weakest_category'];

        return [
            'has_data'        => !empty($readiness['categories']),
            'weakest_area'    => $weakest ? [
                'category_id'    => $weakest['category_id'],
                'category_name'  => $weakest['category_name'],
                'category_slug'  => $weakest['category_slug'],
                'category_icon'  => $weakest['category_icon'],
                'total_attempts' => $weakest['questions_answered'],
                'correct_attempts'=> $weakest['questions_correct'],
                'accuracy_rate'  => $weakest['accuracy'],
            ] : null,
            'weakest_topic'   => $readiness['weakest_topic'],
            'strongest_area'  => $readiness['strongest_category'],
            'recommendation'  => $readiness['recommendation'],
            'all_categories'  => $readiness['categories'],
        ];
    }

    /**
     * Full adaptive psychology profile for dashboard and practice views.
     */
    public function getAdaptiveProfile(int $userId): array {
        return $this->adaptiveService->getFullReadinessProfile($userId);
    }

    /**
     * Get detailed analytics for student dashboard.
     */
    public function getAnalytics(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total_answered,
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) AS total_correct,
                ROUND(AVG(response_time), 1) AS avg_response_time,
                SUM(CASE WHEN difficulty = 'beginner' AND is_correct = 1 THEN 1 ELSE 0 END) AS beginner_correct,
                SUM(CASE WHEN difficulty = 'intermediate' AND is_correct = 1 THEN 1 ELSE 0 END) AS intermediate_correct,
                SUM(CASE WHEN difficulty = 'advanced' AND is_correct = 1 THEN 1 ELSE 0 END) AS advanced_correct,
                SUM(CASE WHEN difficulty = 'board_exam' AND is_correct = 1 THEN 1 ELSE 0 END) AS board_exam_correct
            FROM question_attempts 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch() ?: [];

        $totalAnswered = (int) ($stats['total_answered'] ?? 0);
        $totalCorrect = (int) ($stats['total_correct'] ?? 0);
        $stats['overall_accuracy'] = $totalAnswered > 0 ? round(($totalCorrect / $totalAnswered) * 100, 1) : 0;

        return $stats;
    }

    /**
     * Create a new question (e.g., from AI generator or admin).
     */
    public function createQuestion(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO questions (
                domain_id, category_id, topic_id, part_id, question, choice_a, choice_b, choice_c, choice_d,
                correct_answer, difficulty, recommended_level_min, recommended_level_max,
                explanation, source_version, source_reference, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['domain_id'] ?? null,
            $data['category_id'] ?? ($data['domain_id'] ?? 7),
            $data['topic_id'] ?? null,
            $data['part_id'] ?? null,
            $data['question'],
            $data['choice_a'],
            $data['choice_b'],
            $data['choice_c'],
            $data['choice_d'],
            strtolower($data['correct_answer']),
            $data['difficulty'] ?? 'intermediate',
            $data['recommended_level_min'] ?? 1,
            $data['recommended_level_max'] ?? 99,
            $data['explanation'] ?? '',
            $data['source_version'] ?? 'DSM-5',
            $data['source_reference'] ?? ($data['source_version'] ?? 'DSM-5'),
            $data['status'] ?? 'draft',
            $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update review status of a question.
     */
    public function updateStatus(int $questionId, string $status): bool {
        $allowed = ['draft', 'under_review', 'approved', 'rejected', 'archived'];
        if (!in_array($status, $allowed)) return false;

        $stmt = $this->db->prepare("UPDATE questions SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $questionId]);
    }
}
