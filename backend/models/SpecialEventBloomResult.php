<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEventBloomResult {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function saveAttemptBloom(int $attemptId, string $bloomLevel, int $questionCount, int $correctCount, int $incorrectCount, float $accuracy, int $avgResponseTime): bool {
        $stmt = $this->db->prepare("
            INSERT INTO special_event_bloom_results (
                attempt_id, bloom_level, question_count, correct_count, incorrect_count, accuracy, average_response_time, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                question_count = VALUES(question_count),
                correct_count = VALUES(correct_count),
                incorrect_count = VALUES(incorrect_count),
                accuracy = VALUES(accuracy),
                average_response_time = VALUES(average_response_time)
        ");
        return $stmt->execute([
            $attemptId,
            $bloomLevel,
            $questionCount,
            $correctCount,
            $incorrectCount,
            $accuracy,
            $avgResponseTime
        ]);
    }

    public function getByAttemptId(int $attemptId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_bloom_results
            WHERE attempt_id = ?
            ORDER BY FIELD(bloom_level, 'remember', 'understand', 'apply', 'analyze', 'evaluate', 'create')
        ");
        $stmt->execute([$attemptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get historical Bloom performance across completed attempts for a user
     */
    public function getUserBloomHistory(int $userId, int $limit = 30): array {
        $stmt = $this->db->prepare("
            SELECT se.event_date, sea.id as attempt_id, sea.score, sea.percentage,
                   sebr.bloom_level, sebr.question_count, sebr.correct_count,
                   sebr.incorrect_count, sebr.accuracy, sebr.average_response_time,
                   sea.completed_at
            FROM special_event_attempts sea
            JOIN special_events se ON sea.special_event_id = se.id
            JOIN special_event_bloom_results sebr ON sea.id = sebr.attempt_id
            WHERE sea.user_id = ? AND sea.status = 'completed'
            ORDER BY sea.completed_at DESC, FIELD(sebr.bloom_level, 'remember', 'understand', 'apply', 'analyze', 'evaluate', 'create')
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit * 6, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $r) {
            $attId = (int) $r['attempt_id'];
            if (!isset($grouped[$attId])) {
                $grouped[$attId] = [
                    'attempt_id' => $attId,
                    'event_date' => $r['event_date'],
                    'score' => (int) $r['score'],
                    'percentage' => (float) $r['percentage'],
                    'completed_at' => $r['completed_at'],
                    'levels' => []
                ];
            }
            $grouped[$attId]['levels'][$r['bloom_level']] = [
                'bloom_level' => $r['bloom_level'],
                'question_count' => (int) $r['question_count'],
                'correct_count' => (int) $r['correct_count'],
                'incorrect_count' => (int) $r['incorrect_count'],
                'accuracy' => (float) $r['accuracy'],
                'average_response_time' => (int) $r['average_response_time']
            ];
        }

        return array_values($grouped);
    }

    /**
     * Get Bloom performance partitioned by domain for an attempt
     */
    public function getBloomByDomain(int $attemptId): array {
        $stmt = $this->db->prepare("
            SELECT seq.domain_id, pd.name as domain_name,
                   seq.bloom_level,
                   COUNT(seq.id) as question_count,
                   SUM(CASE WHEN sea.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                   SUM(CASE WHEN sea.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count,
                   ROUND((SUM(CASE WHEN sea.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(seq.id)) * 100, 2) as accuracy,
                   ROUND(AVG(sea.response_time), 0) as average_response_time
            FROM special_event_answers sea
            JOIN special_event_questions seq ON sea.question_id = seq.id
            JOIN psychiatric_domains pd ON seq.domain_id = pd.id
            WHERE sea.attempt_id = ?
            GROUP BY seq.domain_id, seq.bloom_level
            ORDER BY seq.domain_id ASC, FIELD(seq.bloom_level, 'remember', 'understand', 'apply', 'analyze', 'evaluate', 'create')
        ");
        $stmt->execute([$attemptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
