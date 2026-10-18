<?php
require_once __DIR__ . '/../config/database.php';

class BattlefieldPreferencesMigration {
    public static function run(): void {
        $db = Database::getInstance();

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_battlefield_preferences (
                user_id INT PRIMARY KEY,
                skip_animation TINYINT(1) NOT NULL DEFAULT 0,
                auto_confirm TINYINT(1) NOT NULL DEFAULT 0,
                critical_mode TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
