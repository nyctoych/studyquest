<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CharacterMigration.php';

class CharacterAttributeAllocationMigration {
    public static function run(): void {
        $db = Database::getInstance();
        if ($db->inTransaction()) {
            return;
        }

        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        // Ensure CharacterMigration has run first so characters table exists
        CharacterMigration::run();

        $db->exec("
            CREATE TABLE IF NOT EXISTS user_attribute_allocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                character_id INT NOT NULL,
                milestone_level TINYINT UNSIGNED NOT NULL,
                attribute_type ENUM('attack_damage', 'health', 'critical_damage') NOT NULL,
                bonus_value INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'applied',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_char_milestone (user_id, character_id, milestone_level),
                INDEX idx_user_char (user_id, character_id),
                CONSTRAINT fk_uaa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_uaa_character FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
}
