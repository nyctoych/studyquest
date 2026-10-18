<?php
require_once __DIR__ . '/../config/database.php';

class DodgePotionMigration {
    public static function run(): void {
        $db = Database::getInstance();

        // 1. Add Dodge Potion columns to users table if not exists
        $userCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('dodge_potion_daily_date', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN dodge_potion_daily_date DATE DEFAULT NULL AFTER coins");
        }
        if (!in_array('dodge_potion_daily_charges', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN dodge_potion_daily_charges INT NOT NULL DEFAULT 3 AFTER dodge_potion_daily_date");
        }
        if (!in_array('dodge_potion_purchased_stock', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN dodge_potion_purchased_stock INT NOT NULL DEFAULT 0 AFTER dodge_potion_daily_charges");
        }

        // 2. Ensure Potions category exists
        $catStmt = $db->prepare("SELECT id FROM item_categories WHERE slug = 'potions' OR slug = 'potion' LIMIT 1");
        $catStmt->execute();
        $catId = $catStmt->fetchColumn();

        if (!$catId) {
            $db->exec("INSERT INTO item_categories (name, slug, icon) VALUES ('Potions', 'potions', '🧪')");
            $catId = (int)$db->lastInsertId();
        } else {
            $catId = (int)$catId;
        }

        // 3. Ensure Dodge Potion item exists in items table
        $itemStmt = $db->prepare("SELECT id FROM items WHERE effect_key = 'dodge-potion' OR name = 'Dodge Potion' LIMIT 1");
        $itemStmt->execute();
        $itemId = $itemStmt->fetchColumn();

        $description = 'Skip the current battle question without dealing or receiving damage. The next question will load immediately.';
        $pngPath = 'assets/POTIONS/dodge potion.png';

        if (!$itemId) {
            $insertStmt = $db->prepare("
                INSERT INTO items (
                    category_id, name, description, icon, coin_cost, rarity, is_available,
                    effect_key, type, png_path, gif_path, character_class, is_default
                ) VALUES (
                    ?, 'Dodge Potion', ?, '🧪', 200, 'rare', 1,
                    'dodge-potion', 'dodge_potion', ?, ?, 'all', 0
                )
            ");
            $insertStmt->execute([$catId, $description, $pngPath, $pngPath]);
        } else {
            $updateStmt = $db->prepare("
                UPDATE items SET
                    category_id = ?,
                    name = 'Dodge Potion',
                    description = ?,
                    coin_cost = 200,
                    is_available = 1,
                    rarity = 'rare',
                    type = 'dodge_potion',
                    effect_key = 'dodge-potion',
                    png_path = ?,
                    gif_path = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$catId, $description, $pngPath, $pngPath, $itemId]);
        }

        // 4. Create dodge_potion_usage_log for authoritative auditing
        $db->exec("
            CREATE TABLE IF NOT EXISTS dodge_potion_usage_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                battle_id INT DEFAULT NULL,
                question_id INT DEFAULT NULL,
                charge_type ENUM('daily', 'purchased') NOT NULL,
                remaining_daily INT NOT NULL,
                remaining_purchased INT NOT NULL,
                used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_used (user_id, used_at),
                INDEX idx_battle (battle_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Ensure adventure_combat_events allows is_correct to be NULL for skipped questions
        try {
            $ceCol = $db->query("SHOW COLUMNS FROM adventure_combat_events LIKE 'is_correct'")->fetch(PDO::FETCH_ASSOC);
            if ($ceCol && strtoupper($ceCol['Null']) !== 'YES') {
                $db->exec("ALTER TABLE adventure_combat_events MODIFY is_correct TINYINT(1) NULL DEFAULT NULL");
            }
        } catch (Exception $e) {
            // Ignore if table does not exist yet
        }
    }
}
