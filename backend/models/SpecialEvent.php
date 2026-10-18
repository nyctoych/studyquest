<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEvent {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM special_events WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByDate(string $date, int $userId = 0): ?array {
        $stmt = $this->db->prepare("SELECT * FROM special_events WHERE event_date = ? AND user_id = ?");
        $stmt->execute([$date, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findByDateAndUser(string $date, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM special_events WHERE event_date = ? AND user_id = ?");
        $stmt->execute([$date, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function getTodayPublishedEvent(int $userId = 0): ?array {
        $today = date('Y-m-d');
        // 1. Check user-specific published event if requested
        if ($userId > 0) {
            $stmt = $this->db->prepare("
                SELECT * FROM special_events 
                WHERE event_date = ? AND user_id = ? AND status = 'published'
                LIMIT 1
            ");
            $stmt->execute([$today, $userId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        // 2. Check global published event (user_id = 0)
        $stmt = $this->db->prepare("
            SELECT * FROM special_events 
            WHERE event_date = ? AND user_id = 0 AND status = 'published'
            LIMIT 1
        ");
        $stmt->execute([$today]);
        $row = $stmt->fetch();
        if ($row) return $row;

        // 3. Fallback to ANY published event for today so users can immediately take the exam
        $stmt = $this->db->prepare("
            SELECT * FROM special_events 
            WHERE event_date = ? AND status = 'published'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$today]);
        return $stmt->fetch() ?: null;
    }

    public function getTodayEvent(int $userId = 0): ?array {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM special_events WHERE event_date = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$today, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $assignedDomains = null;
        if (isset($data['assigned_domains'])) {
            $assignedDomains = is_array($data['assigned_domains']) ? json_encode($data['assigned_domains']) : $data['assigned_domains'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO special_events (
                user_id, event_date, title, description, question_count, assigned_domains, status,
                generation_status, generation_version, generated_at, published_at, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'] ?? 0,
            $data['event_date'] ?? date('Y-m-d'),
            $data['title'] ?? 'The Grand Trial of the Mindrealm',
            $data['description'] ?? '140-question comprehensive psychiatric and psychopathology board-style mock examination.',
            $data['question_count'] ?? 140,
            $assignedDomains,
            $data['status'] ?? 'draft',
            $data['generation_status'] ?? 'pending',
            $data['generation_version'] ?? 'v1.0',
            $data['generated_at'] ?? null,
            $data['published_at'] ?? null,
            $data['expires_at'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE special_events SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function countQuestions(int $eventId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM special_event_questions WHERE special_event_id = ?");
        $stmt->execute([$eventId]);
        return (int) $stmt->fetchColumn();
    }

    public function countApprovedQuestions(int $eventId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM special_event_questions 
            WHERE special_event_id = ? AND review_status = 'approved'
        ");
        $stmt->execute([$eventId]);
        return (int) $stmt->fetchColumn();
    }

    public function getAll(int $limit = 30, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT se.*, 
                   COUNT(seq.id) as actual_question_count,
                   SUM(CASE WHEN seq.review_status = 'approved' THEN 1 ELSE 0 END) as approved_question_count
            FROM special_events se
            LEFT JOIN special_event_questions seq ON se.id = seq.special_event_id
            GROUP BY se.id
            ORDER BY se.event_date DESC, se.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM special_events WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
