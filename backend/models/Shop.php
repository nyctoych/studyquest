<?php
require_once __DIR__ . '/../config/database.php';

class ShopItem {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(?string $categorySlug = null): array {
        $sql = "SELECT i.*, ic.name AS category_name, ic.slug AS category_slug, ic.icon AS category_icon
                FROM items i
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE i.is_available = 1";
        $params = [];
        if ($categorySlug) {
            $sql .= " AND ic.slug = ?";
            $params[] = $categorySlug;
        }
        $sql .= " ORDER BY ic.name, i.rarity, i.coin_cost";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT i.*, ic.name AS category_name, ic.slug AS category_slug
             FROM items i JOIN item_categories ic ON i.category_id = ic.id
             WHERE i.id = ? AND i.is_available = 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getCategories(): array {
        $stmt = $this->db->query("SELECT * FROM item_categories ORDER BY id");
        return $stmt->fetchAll();
    }

    public function getUserInventory(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT i.*, ic.name AS category_name, ic.slug AS category_slug,
                    ui.purchased_at,
                    (SELECT ue.item_id FROM user_equipped_items ue
                     WHERE ue.user_id = ? AND ue.item_id = i.id LIMIT 1) AS is_equipped
             FROM user_items ui
             JOIN items i ON ui.item_id = i.id
             JOIN item_categories ic ON i.category_id = ic.id
             WHERE ui.user_id = ?
             ORDER BY ic.id, i.rarity, i.name"
        );
        $stmt->execute([$userId, $userId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['is_equipped'] = !is_null($item['is_equipped']);
        }
        return $items;
    }

    public function userOwns(int $userId, int $itemId): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_items WHERE user_id = ? AND item_id = ?"
        );
        $stmt->execute([$userId, $itemId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Get all equipped items for a user, keyed by category_slug.
     * Returns: { 'aura' => {...item}, 'character_skin' => {...item}, ... }
     */
    public function getEquipped(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT ue.slot, i.*, ic.slug AS category_slug, ic.name AS category_name
             FROM user_equipped_items ue
             JOIN items i ON ue.item_id = i.id
             JOIN item_categories ic ON i.category_id = ic.id
             WHERE ue.user_id = ?"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $equipped = [];
        foreach ($rows as $row) {
            $equipped[$row['slot']] = $row;
        }
        return $equipped;
    }

    public function purchase(int $userId, int $itemId): array {
        $item = $this->getById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }
        if ($this->userOwns($userId, $itemId)) {
            return ['success' => false, 'message' => 'You already own this item'];
        }

        // Check coins
        $stmt = $this->db->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user || $user['coins'] < $item['coin_cost']) {
            return ['success' => false, 'message' => 'Insufficient Scholar Coins'];
        }

        // Deduct coins
        $this->db->prepare("UPDATE users SET coins = coins - ? WHERE id = ?")->execute([$item['coin_cost'], $userId]);

        // Record new balance
        $stmt = $this->db->prepare("SELECT coins FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $newBalance = (int)$stmt->fetch()['coins'];

        // Log transaction
        $this->db->prepare(
            "INSERT INTO coin_transactions (user_id, amount, type, source, reference_id, description, balance_after)
             VALUES (?, ?, 'spend', 'purchase', ?, ?, ?)"
        )->execute([$userId, $item['coin_cost'], $itemId, "Purchased: {$item['name']}", $newBalance]);

        // Add to inventory
        $this->db->prepare(
            "INSERT INTO user_items (user_id, item_id) VALUES (?, ?)"
        )->execute([$userId, $itemId]);

        return ['success' => true, 'message' => "Purchased {$item['name']}!", 'new_coin_balance' => $newBalance];
    }

    public function equip(int $userId, int $itemId): array {
        if (!$this->userOwns($userId, $itemId)) {
            return ['success' => false, 'message' => 'You do not own this item'];
        }
        $item = $this->getById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        // Slot key = category_slug (e.g. 'aura', 'character_skin', 'back_effect', 'hit_effect', 'finisher_effect')
        $slot = $item['category_slug'];

        // Upsert equipped slot (one item per category slot per user)
        $stmt = $this->db->prepare(
            "INSERT INTO user_equipped_items (user_id, slot, item_id)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), equipped_at = NOW()"
        );
        $stmt->execute([$userId, $slot, $itemId]);

        return ['success' => true, 'message' => "{$item['name']} equipped!", 'slot' => $slot, 'item' => $item];
    }

    public function unequip(int $userId, string $slot): array {
        $this->db->prepare(
            "DELETE FROM user_equipped_items WHERE user_id = ? AND slot = ?"
        )->execute([$userId, $slot]);

        return ['success' => true, 'message' => 'Item unequipped'];
    }
}
