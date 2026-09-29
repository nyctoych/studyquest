<?php
require_once __DIR__ . '/../config/Database.php';

class AdventureMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Ensure adventure_chapter_progress table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS adventure_chapter_progress (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    domain_id INT NOT NULL,
                    status ENUM('locked','available','in_progress','boss_available','completed','mastered') NOT NULL DEFAULT 'locked',
                    parts_completed INT NOT NULL DEFAULT 0,
                    boss_defeated TINYINT(1) NOT NULL DEFAULT 0,
                    boss_status ENUM('locked','available','defeated') NOT NULL DEFAULT 'locked',
                    started_at TIMESTAMP NULL DEFAULT NULL,
                    completed_at TIMESTAMP NULL DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (domain_id) REFERENCES psychology_domains(id) ON DELETE CASCADE,
                    UNIQUE KEY uq_user_chapter (user_id, domain_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Ensure columns and status enum are up to date if table already existed
            self::ensureChapterProgressColumns($db);

            // 3. Ensure chapter_rewards table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS chapter_rewards (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    chapter_id INT NOT NULL,
                    xp_awarded INT NOT NULL DEFAULT 0,
                    coins_awarded INT NOT NULL DEFAULT 0,
                    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (chapter_id) REFERENCES psychology_domains(id) ON DELETE CASCADE,
                    UNIQUE KEY uq_user_chapter_reward (user_id, chapter_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. Ensure bosses have chapter_id and are linked to psychology domains
            self::ensureBossesLinked($db);

            // 5. Ensure adventure_part_story table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS adventure_part_story (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    part_id INT NOT NULL,
                    story_intro TEXT DEFAULT NULL,
                    dialogue_speaker VARCHAR(100) DEFAULT 'Mentor',
                    dialogue_text TEXT DEFAULT NULL,
                    story_after TEXT DEFAULT NULL,
                    enemy_name VARCHAR(100) DEFAULT 'Study Specter',
                    enemy_flavor VARCHAR(255) DEFAULT NULL,
                    enemy_max_hp INT NOT NULL DEFAULT 100,
                    FOREIGN KEY (part_id) REFERENCES psychology_parts(id) ON DELETE CASCADE,
                    UNIQUE KEY uq_part_story (part_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 6. Ensure adventure_attempts table exists
            $db->exec("
                CREATE TABLE IF NOT EXISTS adventure_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    part_id INT NOT NULL,
                    score INT NOT NULL DEFAULT 0,
                    correct_answers INT NOT NULL DEFAULT 0,
                    total_questions INT NOT NULL DEFAULT 5,
                    is_passed TINYINT(1) NOT NULL DEFAULT 0,
                    time_spent INT NOT NULL DEFAULT 0,
                    xp_earned INT NOT NULL DEFAULT 0,
                    coins_earned INT NOT NULL DEFAULT 0,
                    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (part_id) REFERENCES psychology_parts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

        } catch (Throwable $e) {
            error_log("AdventureMigration Error: " . $e->getMessage());
        }
    }

    private static function ensureChapterProgressColumns(PDO $db): void {
        try {
            // Update enum type on status to include boss_available and mastered
            $db->exec("
                ALTER TABLE adventure_chapter_progress 
                MODIFY COLUMN status ENUM('locked','available','in_progress','boss_available','completed','mastered') NOT NULL DEFAULT 'locked'
            ");
        } catch (Throwable $e) {
            // ignore if already modified
        }

        try {
            $db->exec("
                ALTER TABLE adventure_chapter_progress 
                ADD COLUMN IF NOT EXISTS boss_status ENUM('locked','available','defeated') NOT NULL DEFAULT 'locked' AFTER boss_defeated
            ");
        } catch (Throwable $e) {}

        try {
            $db->exec("
                ALTER TABLE adventure_chapter_progress 
                ADD COLUMN IF NOT EXISTS started_at TIMESTAMP NULL DEFAULT NULL AFTER boss_status
            ");
        } catch (Throwable $e) {}
    }

    private static function ensureBossesLinked(PDO $db): void {
        try {
            $db->exec("ALTER TABLE bosses ADD COLUMN IF NOT EXISTS chapter_id INT DEFAULT NULL");
            $db->exec("ALTER TABLE bosses ADD COLUMN IF NOT EXISTS portrait_asset VARCHAR(255) DEFAULT NULL");
            $db->exec("ALTER TABLE bosses ADD COLUMN IF NOT EXISTS flavor_text TEXT DEFAULT NULL");
            $db->exec("ALTER TABLE bosses ADD COLUMN IF NOT EXISTS chapter_flavor TEXT DEFAULT NULL");
            $db->exec("ALTER TABLE bosses ADD COLUMN IF NOT EXISTS boss_order INT DEFAULT 1");
        } catch (Throwable $e) {}

        require_once __DIR__ . '/AdventureStoryDataset.php';
        $chapters = AdventureStoryDataset::getChapters();

        foreach ($chapters as $domainId => $b) {
            // Check if boss exists for chapter
            $stmt = $db->prepare("SELECT id FROM bosses WHERE chapter_id = ? LIMIT 1");
            $stmt->execute([$domainId]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                $up = $db->prepare("
                    UPDATE bosses SET 
                        name = ?, story_title = ?, description = ?, max_hp = ?, xp_reward = ?, point_reward = ?,
                        portrait_asset = ?, idle_asset = ?, image = ?, story_intro = ?, dialogue_intro = ?,
                        dialogue_defeat = ?, restoration_text = ?, mind_seal_name = ?, fracture_shard_name = ?,
                        flavor_text = ?, chapter_flavor = ?, boss_order = 1
                    WHERE id = ?
                ");
                $up->execute([
                    $b['boss_name'], $b['boss_title'], $b['setting_description'], $b['boss_max_hp'], $b['boss_xp'], $b['boss_coins'],
                    $b['boss_portrait'], $b['boss_animated'], $b['boss_portrait'], $b['story_intro'], $b['boss_dialogue_intro'],
                    $b['boss_dialogue_defeat'], $b['story_outro'], $b['mind_seal_name'], $b['fracture_shard_name'],
                    $b['story_intro'], $b['story_outro'], $existingId
                ]);
            } else {
                $ins = $db->prepare("
                    INSERT INTO bosses (name, story_title, description, difficulty, max_hp, icon, xp_reward, point_reward, chapter_id, portrait_asset, idle_asset, image, story_intro, dialogue_intro, dialogue_defeat, restoration_text, mind_seal_name, fracture_shard_name, flavor_text, chapter_flavor, boss_order)
                    VALUES (?, ?, ?, 'hard', ?, '👹', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $ins->execute([
                    $b['boss_name'], $b['boss_title'], $b['setting_description'], $b['boss_max_hp'],
                    $b['boss_xp'], $b['boss_coins'], $domainId, $b['boss_portrait'], $b['boss_animated'],
                    $b['boss_portrait'], $b['story_intro'], $b['boss_dialogue_intro'], $b['boss_dialogue_defeat'],
                    $b['story_outro'], $b['mind_seal_name'], $b['fracture_shard_name'], $b['story_intro'], $b['story_outro']
                ]);
            }
        }
    }
}
