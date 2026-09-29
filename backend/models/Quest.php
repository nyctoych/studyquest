<?php
require_once __DIR__ . '/../config/database.php';

class Quest {
    private PDO $db;

    // XP and point rewards by difficulty
    private array $difficultyRewards = [
        'easy'   => ['xp' => 50,  'points' => 25],
        'medium' => ['xp' => 100, 'points' => 50],
        'hard'   => ['xp' => 200, 'points' => 100],
        'epic'   => ['xp' => 400, 'points' => 200],
    ];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(int $userId, array $filters = []): array {
        $where = ['user_id = ?'];
        $params = [$userId];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['subject'])) {
            $where[] = 'subject = ?';
            $params[] = $filters['subject'];
        }
        if (!empty($filters['difficulty'])) {
            $where[] = 'difficulty = ?';
            $params[] = $filters['difficulty'];
        }

        $orderBy = 'ORDER BY ';
        if (!empty($filters['sort'])) {
            $orderBy .= match($filters['sort']) {
                'deadline' => 'deadline ASC',
                'priority' => "FIELD(priority, 'urgent','high','medium','low')",
                'newest'   => 'created_at DESC',
                default    => 'created_at DESC',
            };
        } else {
            $orderBy .= 'created_at DESC';
        }

        $sql = "SELECT * FROM quests WHERE " . implode(' AND ', $where) . " $orderBy";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $quests = $stmt->fetchAll();

        foreach ($quests as &$quest) {
            $quest['subtasks'] = $this->getSubtasks((int)$quest['id']);
        }

        return $quests;
    }

    public function getById(int $questId, int $userId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM quests WHERE id = ? AND user_id = ?");
        $stmt->execute([$questId, $userId]);
        $quest = $stmt->fetch();
        if (!$quest) return null;
        $quest['subtasks'] = $this->getSubtasks($questId);
        return $quest;
    }

    public function create(int $userId, array $data): int {
        $difficulty = $data['difficulty'] ?? 'medium';
        $rewards = $this->difficultyRewards[$difficulty];

        $stmt = $this->db->prepare("
            INSERT INTO quests (user_id, title, description, subject, difficulty, priority, deadline, xp_reward, point_reward)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $data['title'],
            $data['description'] ?? null,
            $data['subject'] ?? 'General',
            $difficulty,
            $data['priority'] ?? 'medium',
            $data['deadline'] ?? null,
            $data['xp_reward'] ?? $rewards['xp'],
            $data['point_reward'] ?? $rewards['points'],
        ]);
        $questId = (int) $this->db->lastInsertId();

        if (!empty($data['subtasks']) && is_array($data['subtasks'])) {
            foreach ($data['subtasks'] as $index => $subtask) {
                $subStmt = $this->db->prepare("INSERT INTO quest_subtasks (quest_id, title, sort_order) VALUES (?, ?, ?)");
                $subStmt->execute([$questId, $subtask, $index]);
            }
        }

        return $questId;
    }

    public function update(int $questId, int $userId, array $data): bool {
        $allowed = ['title', 'description', 'subject', 'difficulty', 'priority', 'deadline', 'status'];
        $fields = [];
        $values = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        $values[] = $questId;
        $values[] = $userId;
        $stmt = $this->db->prepare("UPDATE quests SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?");
        return $stmt->execute($values);
    }

    public function complete(int $questId, int $userId): ?array {
        $quest = $this->getById($questId, $userId);
        if (!$quest || $quest['status'] === 'completed') return null;

        $stmt = $this->db->prepare("UPDATE quests SET status = 'completed', completed_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$questId, $userId]);

        return [
            'xp_reward' => (int) $quest['xp_reward'],
            'point_reward' => (int) $quest['point_reward'],
        ];
    }

    public function delete(int $questId, int $userId): bool {
        $stmt = $this->db->prepare("DELETE FROM quests WHERE id = ? AND user_id = ?");
        return $stmt->execute([$questId, $userId]);
    }

    public function getSubtasks(int $questId): array {
        $stmt = $this->db->prepare("SELECT * FROM quest_subtasks WHERE quest_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$questId]);
        return $stmt->fetchAll();
    }

    public function toggleSubtask(int $subtaskId, int $questId): bool {
        $stmt = $this->db->prepare("UPDATE quest_subtasks SET completed = NOT completed WHERE id = ? AND quest_id = ?");
        return $stmt->execute([$subtaskId, $questId]);
    }

    public function getTodayQuests(int $userId): array {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM quests WHERE user_id = ? AND (deadline = ? OR status = 'in_progress') AND status != 'completed' ORDER BY priority DESC LIMIT 10");
        $stmt->execute([$userId, $today]);
        return $stmt->fetchAll();
    }

    public function getRecentCompleted(int $userId, int $limit = 5): array {
        $stmt = $this->db->prepare("SELECT * FROM quests WHERE user_id = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getCompletedCount(int $userId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM quests WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }
}
