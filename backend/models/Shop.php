<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/CosmeticsMigration.php';

class ShopItem {
    private PDO $db;

    public function __construct() {
        CosmeticsMigration::run();
        $this->db = Database::getInstance();
    }

    public function getAll(?string $categorySlug = null): array {
        $sql = "SELECT i.*, ic.name AS category_name, ic.slug AS category_slug, ic.icon AS category_icon,
                       COALESCE(i.type, ic.slug) AS type
                FROM items i
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE i.is_available = 1";
        $params = [];
        if ($categorySlug) {
            $sql .= " AND (ic.slug = ? OR i.type = ?)";
            $params[] = $categorySlug;
            $params[] = $categorySlug;
        }
        $sql .= " ORDER BY ic.id, i.is_default DESC, i.rarity, i.coin_cost";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT i.*, ic.name AS category_name, ic.slug AS category_slug, COALESCE(i.type, ic.slug) AS type
             FROM items i JOIN item_categories ic ON i.category_id = ic.id
             WHERE i.id = ? AND i.is_available = 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getCategories(): array {
        $stmt = $this->db->query("SELECT * FROM item_categories WHERE slug IN ('attack_effect', 'splash_effect', 'potions', 'potion') ORDER BY id");
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

        // Also ensure all default items (is_default = 1 or coin_cost = 0) are in inventory
        $defStmt = $this->db->prepare("
            SELECT i.*, ic.name AS category_name, ic.slug AS category_slug,
                   NOW() as purchased_at,
                   (SELECT ue.item_id FROM user_equipped_items ue
                    WHERE ue.user_id = ? AND ue.item_id = i.id LIMIT 1) AS is_equipped
            FROM items i
            JOIN item_categories ic ON i.category_id = ic.id
            WHERE i.is_available = 1 AND (i.is_default = 1 OR i.coin_cost = 0)
        ");
        $defStmt->execute([$userId]);
        $defaultItems = $defStmt->fetchAll();

        $existingIds = array_column($items, 'id');
        foreach ($defaultItems as $defItem) {
            if (!in_array($defItem['id'], $existingIds)) {
                $items[] = $defItem;
            }
        }

        foreach ($items as &$item) {
            $item['is_equipped'] = !is_null($item['is_equipped']);
        }

        // Include Dodge Potion charges & purchased stock in consumable inventory
        require_once __DIR__ . '/../services/DodgePotionService.php';
        $dodgeState = DodgePotionService::getInstance()->getCharges($userId);
        if ($dodgeState['total_available'] > 0) {
            $dodgeItemStmt = $this->db->prepare("
                SELECT i.*, ic.name AS category_name, ic.slug AS category_slug,
                       NOW() as purchased_at, 0 as is_equipped
                FROM items i
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE i.effect_key = 'dodge-potion' OR i.type = 'dodge_potion'
                LIMIT 1
            ");
            $dodgeItemStmt->execute();
            $dodgeItem = $dodgeItemStmt->fetch();
            if ($dodgeItem) {
                $dodgeItem['quantity'] = $dodgeState['total_available'];
                $dodgeItem['daily_charges'] = $dodgeState['daily_charges'];
                $dodgeItem['purchased_stock'] = $dodgeState['purchased_stock'];
                $dodgeItem['is_equipped'] = false;
                $items[] = $dodgeItem;
            }
        }

        return $items;
    }

    public function userOwns(int $userId, int $itemId): bool {
        $item = $this->getById($itemId);
        if (!$item) return false;
        // Potions are consumables, not permanently owned one-off cosmetic items
        if (($item['type'] ?? '') === 'potion' || ($item['type'] ?? '') === 'dodge_potion' || ($item['category_slug'] ?? '') === 'potion' || ($item['category_slug'] ?? '') === 'potions') {
            return false;
        }
        if (!empty($item['is_default']) || (int)$item['coin_cost'] === 0) {
            return true;
        }
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_items WHERE user_id = ? AND item_id = ?"
        );
        $stmt->execute([$userId, $itemId]);
        return (bool) $stmt->fetchColumn();
    }

    public function resolveUserClass(int $userId): string {
        $userStmt = $this->db->prepare("
            SELECT COALESCE(c.class_type, 'mage') as class_type
            FROM users u
            LEFT JOIN characters c ON u.selected_character_id = c.id
            WHERE u.id = ?
        ");
        $userStmt->execute([$userId]);
        $raw = strtolower((string)($userStmt->fetchColumn() ?: 'mage'));
        if (str_contains($raw, 'range') || str_contains($raw, 'archer')) {
            return 'range';
        }
        if (str_contains($raw, 'melee') || str_contains($raw, 'warrior')) {
            return 'melee';
        }
        return 'mage';
    }

    /**
     * Get all equipped items for a user, keyed by category_slug.
     * If no attack or splash effects are equipped, automatically equips the common default skin.
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

        $userClass = $this->resolveUserClass($userId);

        // Verify class match for attack_effect (if equipped skin belongs to another class, fall back to current class common skin)
        if (!empty($equipped['attack_effect'])) {
            $itemClass = strtolower((string)($equipped['attack_effect']['character_class'] ?? 'all'));
            if ($itemClass !== 'all' && $itemClass !== $userClass) {
                unset($equipped['attack_effect']);
            }
        }

        // Auto-equip common default attack effect if none equipped
        if (empty($equipped['attack_effect'])) {
            $attStmt = $this->db->prepare("
                SELECT i.*, ic.slug AS category_slug, ic.name AS category_name
                FROM items i
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE ic.slug = 'attack_effect' 
                  AND (i.character_class = ? OR i.character_class = 'all')
                  AND (i.is_default = 1 OR i.rarity = 'common' OR i.coin_cost = 0)
                ORDER BY i.is_default DESC, i.coin_cost ASC, i.id ASC
                LIMIT 1
            ");
            $attStmt->execute([$userClass]);
            $defaultAttack = $attStmt->fetch();
            if ($defaultAttack) {
                $this->db->prepare("
                    INSERT INTO user_equipped_items (user_id, slot, item_id)
                    VALUES (?, 'attack_effect', ?)
                    ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)
                ")->execute([$userId, $defaultAttack['id']]);
                $equipped['attack_effect'] = $defaultAttack;
            }
        }

        // Verify class match for splash_effect
        if (!empty($equipped['splash_effect'])) {
            $splashClass = strtolower((string)($equipped['splash_effect']['character_class'] ?? 'all'));
            if ($splashClass !== 'all' && $splashClass !== $userClass) {
                unset($equipped['splash_effect']);
            }
        }

        // Auto-equip common default splash effect if none equipped
        if (empty($equipped['splash_effect'])) {
            $splashStmt = $this->db->prepare("
                SELECT i.*, ic.slug AS category_slug, ic.name AS category_name
                FROM items i
                JOIN item_categories ic ON i.category_id = ic.id
                WHERE ic.slug = 'splash_effect'
                  AND (i.character_class = ? OR i.character_class = 'all')
                  AND (i.is_default = 1 OR i.rarity = 'common' OR i.coin_cost = 0)
                ORDER BY i.is_default DESC, i.coin_cost ASC, i.id ASC
                LIMIT 1
            ");
            $splashStmt->execute([$userClass]);
            $defaultSplash = $splashStmt->fetch();
            if ($defaultSplash) {
                $this->db->prepare("
                    INSERT INTO user_equipped_items (user_id, slot, item_id)
                    VALUES (?, 'splash_effect', ?)
                    ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)
                ")->execute([$userId, $defaultSplash['id']]);
                $equipped['splash_effect'] = $defaultSplash;
            }
        }

        return $equipped;
    }

    public function purchase(int $userId, int $itemId): array {
        $item = $this->getById($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        // Dodge Potion Consumable Routing
        if (($item['type'] ?? '') === 'dodge_potion' || ($item['effect_key'] ?? '') === 'dodge-potion' || ($item['name'] ?? '') === 'Dodge Potion') {
            require_once __DIR__ . '/../services/DodgePotionService.php';
            return DodgePotionService::getInstance()->purchase($userId, $itemId);
        }

        // Instant Consumable Potion Routing (Health Potion +200 HP)
        $isPotion = ($item['type'] ?? '') === 'potion' || ($item['category_slug'] ?? '') === 'potion' || ($item['category_slug'] ?? '') === 'potions';
        if ($isPotion) {
            require_once __DIR__ . '/../services/AdventureHealthService.php';
            return AdventureHealthService::getInstance()->purchaseHealthPotion($userId, $itemId);
        }

        if ($this->userOwns($userId, $itemId)) {
            return ['success' => false, 'message' => 'You already own this item'];
        }

        try {
            $this->db->beginTransaction();

            // Check coins with row locking for concurrency safety
            $stmt = $this->db->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user || $user['coins'] < $item['coin_cost']) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Insufficient Scholar Coins'];
            }

            // Deduct coins & points
            $this->db->prepare("UPDATE users SET coins = GREATEST(0, coins - ?), points = GREATEST(0, points - ?) WHERE id = ?")->execute([$item['coin_cost'], $item['coin_cost'], $userId]);

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

            $this->db->commit();
            return ['success' => true, 'message' => "Purchased {$item['name']}!", 'new_coin_balance' => $newBalance];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
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

        // Calling getEquipped will auto-equip the common default skin if slot is attack_effect or splash_effect
        $equipped = $this->getEquipped($userId);

        return ['success' => true, 'message' => 'Cosmetic reset to default', 'equipped' => $equipped];
    }
}
