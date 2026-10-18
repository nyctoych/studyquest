<?php
require_once __DIR__ . '/../config/database.php';

class DailyChestMigration {
    public static function run(): void {
        $db = Database::getInstance();

        // 1. Add last_daily_chest_at to users table if not exists
        $userCols = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('last_daily_chest_at', $userCols)) {
            $db->exec("ALTER TABLE users ADD COLUMN last_daily_chest_at DATE DEFAULT NULL AFTER coins");
        }

        // 2. Create daily_chest_claims table for tracking
        $db->exec("
            CREATE TABLE IF NOT EXISTS daily_chest_claims (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                xp_awarded INT NOT NULL DEFAULT 1500,
                coins_awarded INT NOT NULL DEFAULT 300,
                claimed_date DATE NOT NULL,
                claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_date (user_id, claimed_date),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
