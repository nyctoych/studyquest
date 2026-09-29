<?php
require_once __DIR__ . '/../config/database.php';

class Reward {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(string $category = ''): array {
        if ($category) {
            $stmt = $this->db->prepare("SELECT * FROM rewards WHERE is_available = 1 AND category = ? ORDER BY rarity DESC, cost ASC");
            $stmt->execute([$category]);
        } else {
            $stmt = $this->db->query("SELECT * FROM rewards WHERE is_available = 1 ORDER BY category ASC, rarity DESC, cost ASC");
        }
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM rewards WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getUserInventory(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT ur.*, r.name, r.description, r.category, r.icon, r.effect_type, r.effect_value, r.rarity
            FROM user_rewards ur
            JOIN rewards r ON ur.reward_id = r.id
            WHERE ur.user_id = ?
            ORDER BY ur.purchased_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function purchase(int $userId, int $rewardId): array {
        $reward = $this->getById($rewardId);
        if (!$reward) {
            return ['success' => false, 'message' => 'Reward not found'];
        }

        // Check if user has enough points
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user['points'] < $reward['cost']) {
            return ['success' => false, 'message' => 'Not enough points'];
        }

        // Deduct points
        $stmt = $db->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        $stmt->execute([$reward['cost'], $userId]);

        // Add to inventory
        $stmt = $db->prepare("
            INSERT INTO user_rewards (user_id, reward_id, quantity) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ");
        $stmt->execute([$userId, $rewardId]);

        // Get updated points
        $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $updatedUser = $stmt->fetch();

        return [
            'success' => true,
            'message' => "Purchased {$reward['name']}!",
            'reward' => $reward,
            'new_points' => $updatedUser['points'],
        ];
    }

    public function useReward(int $userId, int $userRewardId): array {
        $stmt = $this->db->prepare("
            SELECT ur.*, r.name, r.effect_type, r.effect_value
            FROM user_rewards ur
            JOIN rewards r ON ur.reward_id = r.id
            WHERE ur.id = ? AND ur.user_id = ? AND ur.quantity > 0
        ");
        $stmt->execute([$userRewardId, $userId]);
        $item = $stmt->fetch();

        if (!$item) {
            return ['success' => false, 'message' => 'Item not found in inventory'];
        }

        $stmt = $this->db->prepare("UPDATE user_rewards SET quantity = quantity - 1, used_at = NOW() WHERE id = ?");
        $stmt->execute([$userRewardId]);

        return ['success' => true, 'effect_type' => $item['effect_type'], 'effect_value' => $item['effect_value']];
    }
}
