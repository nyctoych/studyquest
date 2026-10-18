<?php
require_once __DIR__ . '/../config/database.php';

class TutorialOnboardingMigration {
    public static function run(): void {
        $db = Database::getInstance();

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_tutorial_progress (
                user_id INT PRIMARY KEY,
                tutorial_started TINYINT(1) NOT NULL DEFAULT 0,
                tutorial_phase VARCHAR(32) NOT NULL DEFAULT 'battlefield',
                tutorial_step INT NOT NULL DEFAULT 0,
                battlefield_completed TINYINT(1) NOT NULL DEFAULT 0,
                tutorial_completed TINYINT(1) NOT NULL DEFAULT 0,
                tutorial_skipped TINYINT(1) NOT NULL DEFAULT 0,
                last_completed_step INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
