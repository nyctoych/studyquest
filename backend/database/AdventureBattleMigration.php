<?php
require_once __DIR__ . '/../config/Database.php';

class AdventureBattleMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Create adventure_active_battles table
            $db->exec("
                CREATE TABLE IF NOT EXISTS adventure_active_battles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    encounter_type ENUM('part', 'boss', 'tutorial') NOT NULL DEFAULT 'part',
                    part_id INT NULL,
                    chapter_id INT NULL,
                    boss_id INT NULL,
                    character_id INT NOT NULL,
                    player_max_health INT NOT NULL,
                    player_current_health INT NOT NULL,
                    enemy_name VARCHAR(150) NOT NULL,
                    enemy_title VARCHAR(150) NULL,
                    enemy_max_health INT NOT NULL,
                    enemy_current_health INT NOT NULL,
                    character_damage INT NOT NULL,
                    character_crit_damage INT NOT NULL,
                    question_sequence LONGTEXT NULL,
                    status ENUM('active', 'victory', 'defeat') NOT NULL DEFAULT 'active',
                    current_question_index INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_encounter (user_id, encounter_type, status),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Create adventure_combat_events table
            $db->exec("
                CREATE TABLE IF NOT EXISTS adventure_combat_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    battle_id INT NOT NULL,
                    user_id INT NOT NULL,
                    question_id INT NOT NULL,
                    selected_choice VARCHAR(10) NOT NULL,
                    is_correct TINYINT(1) NOT NULL,
                    critical_active TINYINT(1) NOT NULL DEFAULT 0,
                    damage_type VARCHAR(50) NOT NULL,
                    damage_dealt INT NOT NULL DEFAULT 0,
                    player_damage_taken INT NOT NULL DEFAULT 0,
                    player_hp_after INT NOT NULL,
                    enemy_hp_after INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_battle (battle_id),
                    FOREIGN KEY (battle_id) REFERENCES adventure_active_battles(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. Ensure encounter_type supports the separate Final Boss encounter
            $col = $db->query("SHOW COLUMNS FROM adventure_active_battles LIKE 'encounter_type'")->fetch(PDO::FETCH_ASSOC);
            if ($col && strpos($col['Type'], 'final_boss') === false) {
                $db->exec("ALTER TABLE adventure_active_battles MODIFY encounter_type ENUM('part','boss','tutorial','final_boss') NOT NULL DEFAULT 'part'");
            }

            // 4. Ensure battle_mode column exists in adventure_active_battles
            $modeCol = $db->query("SHOW COLUMNS FROM adventure_active_battles LIKE 'battle_mode'")->fetch(PDO::FETCH_ASSOC);
            if (!$modeCol) {
                $db->exec("ALTER TABLE adventure_active_battles ADD COLUMN battle_mode VARCHAR(20) NOT NULL DEFAULT 'adventure' AFTER encounter_type");
            }

            // 5. Ensure persistent Adventure HP tracking columns exist in users table
            $hpCol = $db->query("SHOW COLUMNS FROM users LIKE 'adventure_current_hp'")->fetch(PDO::FETCH_ASSOC);
            if (!$hpCol) {
                $db->exec("ALTER TABLE users ADD COLUMN adventure_current_hp INT NULL DEFAULT NULL");
            }

            $persistedCol = $db->query("SHOW COLUMNS FROM users LIKE 'adventure_hp_persisted'")->fetch(PDO::FETCH_ASSOC);
            if (!$persistedCol) {
                $db->exec("ALTER TABLE users ADD COLUMN adventure_hp_persisted TINYINT(1) NOT NULL DEFAULT 0");
            }

            // 6. Ensure passive health recovery timestamp exists in users table
            $regenCol = $db->query("SHOW COLUMNS FROM users LIKE 'health_regen_last_at'")->fetch(PDO::FETCH_ASSOC);
            if (!$regenCol) {
                $db->exec("ALTER TABLE users ADD COLUMN health_regen_last_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
            }

            // 7. Ensure final_boss_phase column exists in adventure_active_battles
            $phaseCol = $db->query("SHOW COLUMNS FROM adventure_active_battles LIKE 'final_boss_phase'")->fetch(PDO::FETCH_ASSOC);
            if (!$phaseCol) {
                $db->exec("ALTER TABLE adventure_active_battles ADD COLUMN final_boss_phase INT NOT NULL DEFAULT 1 AFTER current_question_index");
            }
        } catch (Throwable $e) {
            error_log('[AdventureBattleMigration] Error: ' . $e->getMessage());
        }
    }
}
