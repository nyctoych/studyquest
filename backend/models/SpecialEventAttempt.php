<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEventAttempt {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT sea.*, se.title as event_title, se.event_date,
                   TIMESTAMPDIFF(SECOND, sea.started_at, NOW()) as elapsed_seconds
            FROM special_event_attempts sea
            JOIN special_events se ON sea.special_event_id = se.id
            WHERE sea.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['question_order'] = !empty($row['question_order']) ? json_decode($row['question_order'], true) : [];
            $row['choice_orders'] = !empty($row['choice_orders']) ? json_decode($row['choice_orders'], true) : [];
        }
        return $row ?: null;
    }

    public function getUserActiveAttempt(int $userId, int $eventId, int $sessionNumber = 1): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_attempts
            WHERE user_id = ? AND special_event_id = ? AND session_number = ? AND status = 'in_progress'
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $eventId, $sessionNumber]);
        $row = $stmt->fetch();
        if ($row) {
            $row['question_order'] = !empty($row['question_order']) ? json_decode($row['question_order'], true) : [];
            $row['choice_orders'] = !empty($row['choice_orders']) ? json_decode($row['choice_orders'], true) : [];
        }
        return $row ?: null;
    }

    public function getUserEventAttempt(int $userId, int $eventId, int $sessionNumber = 1): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_attempts
            WHERE user_id = ? AND special_event_id = ? AND session_number = ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $eventId, $sessionNumber]);
        $row = $stmt->fetch();
        if ($row) {
            $row['question_order'] = !empty($row['question_order']) ? json_decode($row['question_order'], true) : [];
            $row['choice_orders'] = !empty($row['choice_orders']) ? json_decode($row['choice_orders'], true) : [];
        }
        return $row ?: null;
    }

    public function getUserEventAttempts(int $userId, int $eventId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM special_event_attempts
            WHERE user_id = ? AND special_event_id = ?
            ORDER BY session_number ASC, id ASC
        ");
        $stmt->execute([$userId, $eventId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['question_order'] = !empty($row['question_order']) ? json_decode($row['question_order'], true) : [];
            $row['choice_orders'] = !empty($row['choice_orders']) ? json_decode($row['choice_orders'], true) : [];
        }
        return $rows;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO special_event_attempts (
                special_event_id, session_number, user_id, started_at, status,
                question_order, choice_orders
            ) VALUES (?, ?, ?, NOW(), 'in_progress', ?, ?)
        ");
        $stmt->execute([
            $data['special_event_id'],
            $data['session_number'] ?? 1,
            $data['user_id'],
            json_encode($data['question_order'] ?? []),
            json_encode($data['choice_orders'] ?? [])
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $fields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE special_event_attempts SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getUserHistory(int $userId, int $limit = 30): array {
        $stmt = $this->db->prepare("
            SELECT sea.*, se.title as event_title, se.event_date
            FROM special_event_attempts sea
            JOIN special_events se ON sea.special_event_id = se.id
            WHERE sea.user_id = ?
            ORDER BY sea.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
