<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEventAnswer {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function saveAnswer(int $attemptId, int $questionId, ?string $selectedChoice, ?bool $isMarked, int $responseTime = 0, bool $isCorrect = false, ?string $bloomLevel = null): bool {
        // If bloomLevel not provided, fetch from special_event_questions
        if (!$bloomLevel) {
            $qStmt = $this->db->prepare("SELECT bloom_level FROM special_event_questions WHERE id = ?");
            $qStmt->execute([$questionId]);
            $bloomLevel = $qStmt->fetchColumn() ?: 'apply';
        }

        $stmt = $this->db->prepare("
            INSERT INTO special_event_answers (
                attempt_id, question_id, bloom_level, selected_choice, is_correct, response_time, is_marked, answered_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                bloom_level = IF(VALUES(bloom_level) IS NOT NULL, VALUES(bloom_level), bloom_level),
                selected_choice = VALUES(selected_choice),
                is_correct = IF(VALUES(selected_choice) IS NULL, 0, VALUES(is_correct)),
                response_time = response_time + VALUES(response_time),
                is_marked = IF(VALUES(is_marked) IS NOT NULL, VALUES(is_marked), is_marked),
                answered_at = NOW()
        ");
        return $stmt->execute([
            $attemptId,
            $questionId,
            $bloomLevel,
            $selectedChoice ? strtoupper($selectedChoice) : null,
            $isCorrect ? 1 : 0,
            max(0, $responseTime),
            $isMarked !== null ? ($isMarked ? 1 : 0) : null
        ]);
    }

    public function toggleMark(int $attemptId, int $questionId, bool $isMarked): bool {
        $stmt = $this->db->prepare("
            INSERT INTO special_event_answers (
                attempt_id, question_id, is_marked, answered_at
            ) VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                is_marked = VALUES(is_marked)
        ");
        return $stmt->execute([$attemptId, $questionId, $isMarked ? 1 : 0]);
    }

    public function getByAttemptId(int $attemptId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_answers 
            WHERE attempt_id = ?
        ");
        $stmt->execute([$attemptId]);
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['question_id']] = $r;
        }
        return $indexed;
    }

    public function getAnswer(int $attemptId, int $questionId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_answers
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->execute([$attemptId, $questionId]);
        return $stmt->fetch() ?: null;
    }

    public function getSummary(int $attemptId, int $totalQuestions = 140): array {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(CASE WHEN selected_choice IS NOT NULL AND selected_choice != '' THEN 1 END) as answered_count,
                COUNT(CASE WHEN is_marked = 1 THEN 1 END) as marked_count,
                SUM(is_correct) as correct_count
            FROM special_event_answers
            WHERE attempt_id = ?
        ");
        $stmt->execute([$attemptId]);
        $row = $stmt->fetch() ?: ['answered_count' => 0, 'marked_count' => 0, 'correct_count' => 0];

        $answered = (int) $row['answered_count'];
        $marked = (int) $row['marked_count'];
        $unanswered = max(0, $totalQuestions - $answered);

        return [
            'total_questions' => $totalQuestions,
            'answered_count' => $answered,
            'unanswered_count' => $unanswered,
            'marked_count' => $marked,
            'correct_count' => (int) ($row['correct_count'] ?? 0)
        ];
    }
}
