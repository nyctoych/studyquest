<?php
require_once __DIR__ . '/../config/database.php';

class CharacterMigration {
    public static function run(): void {
        $db = Database::getInstance();
        if ($db->inTransaction()) {
            return;
        }

        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        try {
            // 1. Create characters table
            $db->exec("
                CREATE TABLE IF NOT EXISTS characters (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    key_name VARCHAR(50) NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL,
                    class_type VARCHAR(50) NOT NULL,
                    title VARCHAR(150) NOT NULL,
                    health INT NOT NULL DEFAULT 150,
                    damage INT NOT NULL DEFAULT 10,
                    crit_damage INT NOT NULL DEFAULT 35,
                    sprite_path VARCHAR(255) NOT NULL,
                    opening_gif_path VARCHAR(255) NULL,
                    attack_asset_path VARCHAR(255) NULL,
                    idle_asset_path VARCHAR(255) NULL,
                    short_intro VARCHAR(255) NOT NULL,
                    lore TEXT NOT NULL,
                    motivation TEXT NOT NULL,
                    opening_message TEXT NOT NULL,
                    description TEXT NOT NULL,
                    status ENUM('active', 'coming_soon', 'locked') NOT NULL DEFAULT 'active',
                    sort_order INT NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_char_status (status),
                    INDEX idx_char_class (class_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Add selected_character_id to users if not present
            $colCheck = $db->query("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
            ");
            $existingCols = $colCheck->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('selected_character_id', $existingCols)) {
                $db->exec("ALTER TABLE users ADD COLUMN selected_character_id INT DEFAULT NULL AFTER nickname");
                $db->exec("ALTER TABLE users ADD CONSTRAINT fk_user_character FOREIGN KEY (selected_character_id) REFERENCES characters(id) ON DELETE SET NULL");
            }

            // 2b. Add profile_avatar_path to characters if not present
            $charColCheck = $db->query("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'characters'
            ");
            $existingCharCols = $charColCheck->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('profile_avatar_path', $existingCharCols)) {
                $db->exec("ALTER TABLE characters ADD COLUMN profile_avatar_path VARCHAR(255) NULL AFTER idle_asset_path");
            }

            // 3. Seed or synchronize the three official character records
            $characters = [
                [
                    'key_name' => 'mage',
                    'name' => 'Necromancer',
                    'class_type' => 'MAGE',
                    'title' => 'The Umbral Psychomancer',
                    'health' => 150,
                    'damage' => 10,
                    'crit_damage' => 35,
                    'sprite_path' => '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer.png',
                    'opening_gif_path' => '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer OPENING.gif',
                    'attack_asset_path' => '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Attack.gif',
                    'idle_asset_path' => '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif',
                    'profile_avatar_path' => '/assets/PROFILE-AVATAR/NecroProfile.png',
                    'short_intro' => 'A dark scholar of the subconscious who commands necrotic insight and cognitive runes.',
                    'lore' => 'Hailing from the Catacombs of Memory, the Psychomancer walks the shadowed boundary of human consciousness. While others fear the Fracture that shattered the Mindrealm, the Mage recognizes an unraveling of repressed truths and dormant cognitive potential. With each incantation of dialectic reason, they command spectral essence to reconstruct synaptic connections and expose the illusions that haunt the psyche.',
                    'motivation' => 'To pierce the obsidian veil of the Black Archive, reconstruct the shattered Psyche, and prove that true understanding transcends mortal dread.',
                    'opening_message' => 'Consciousness is a veil cast over the void. The Fracture cries out for one who does not fear the shadows within.',
                    'description' => 'Specializes in high critical potency and arcane knowledge manipulation, uncovering deeper cognitive truths beneath traumatic distortions.',
                    'status' => 'active',
                    'sort_order' => 1,
                ],
                [
                    'key_name' => 'melee',
                    'name' => 'Dual-Wielder',
                    'class_type' => 'MELEE',
                    'title' => 'The Twin-Blade Executor',
                    'health' => 200,
                    'damage' => 10,
                    'crit_damage' => 20,
                    'sprite_path' => '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png',
                    'opening_gif_path' => '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee OPENING.gif',
                    'attack_asset_path' => '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee Attack.gif',
                    'idle_asset_path' => '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee IDLE.gif',
                    'profile_avatar_path' => '/assets/PROFILE-AVATAR/MeleeProfile.png',
                    'short_intro' => 'A vanguard of pure discipline who cleaves mental distortion with twin obsidian blades.',
                    'lore' => 'When the Fracture ripped open the emotional core of the Mindrealm, raw neurosis and cognitive dissonance assumed monstrous physical forms. The Dual-Wielder was once a Guardian of the Cognitive Citadel who refused surrender to existential despair. Bearing twin blades tempered in sensory grounding and behavioral conditioning, they cleave through the paralyzing fog of irrational fears and perceptual distortions.',
                    'motivation' => 'To shatter the idols of cognitive distortion with unrelenting force, guarding vulnerable seekers and restoring equilibrium through indomitable willpower.',
                    'opening_message' => 'Doubt is a phantom that vanishes before unwavering resolve. Stand firm, and let our twin blades carve the path forward.',
                    'description' => 'Boasts supreme durability and grounded resilience, standing unwavering against overwhelming psychological assaults.',
                    'status' => 'active',
                    'sort_order' => 2,
                ],
                [
                    'key_name' => 'range',
                    'name' => 'Archer',
                    'class_type' => 'RANGE',
                    'title' => 'The Starlight Marksman',
                    'health' => 110,
                    'damage' => 15,
                    'crit_damage' => 50,
                    'sprite_path' => '/assets/CHARACTERS/ARCHER (RANGE)/Archer.png',
                    'opening_gif_path' => '/assets/CHARACTERS/ARCHER (RANGE)/Archerr OPENING.gif',
                    'attack_asset_path' => '/assets/CHARACTERS/ARCHER (RANGE)/Archerr ATTACKING.gif',
                    'idle_asset_path' => '/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif',
                    'profile_avatar_path' => '/assets/PROFILE-AVATAR/ArcherProfile.png',
                    'short_intro' => 'A keen observer attuned to synaptic signals, piercing fallacies from afar with unerring focus.',
                    'lore' => 'From the silent canopy of the Lucid Woodlands, the Ranger observes the subtlest tremors across the neural web. Possessing an acute sensory threshold and unmatched metacognitive precision, their arrows are woven from pure focused attention. While the Fracture corrupts from within, the Marksman identifies the exact focal point of mental distortions, striking with surgical, lethal clarity before illusions can take root.',
                    'motivation' => 'To spot the subtle lies whispered by the Fracture, strike down cognitive biases from afar, and illuminate the forgotten pathways of the Mindrealm.',
                    'opening_message' => 'A restless mind misses the target; a focused spirit never falters. From the perimeter of shadow, we shall bring piercing clarity.',
                    'description' => 'Excels at piercing offensive precision and massive critical strikes, dismantling complex mental fallacies from great distances.',
                    'status' => 'active',
                    'sort_order' => 3,
                ],
            ];

            $upsertStmt = $db->prepare("
                INSERT INTO characters (
                    key_name, name, class_type, title, health, damage, crit_damage,
                    sprite_path, opening_gif_path, attack_asset_path, idle_asset_path, profile_avatar_path,
                    short_intro, lore, motivation, opening_message, description, status, sort_order
                ) VALUES (
                    :key_name, :name, :class_type, :title, :health, :damage, :crit_damage,
                    :sprite_path, :opening_gif_path, :attack_asset_path, :idle_asset_path, :profile_avatar_path,
                    :short_intro, :lore, :motivation, :opening_message, :description, :status, :sort_order
                ) ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    class_type = VALUES(class_type),
                    title = VALUES(title),
                    health = VALUES(health),
                    damage = VALUES(damage),
                    crit_damage = VALUES(crit_damage),
                    sprite_path = VALUES(sprite_path),
                    opening_gif_path = VALUES(opening_gif_path),
                    attack_asset_path = VALUES(attack_asset_path),
                    idle_asset_path = VALUES(idle_asset_path),
                    profile_avatar_path = VALUES(profile_avatar_path),
                    short_intro = VALUES(short_intro),
                    lore = VALUES(lore),
                    motivation = VALUES(motivation),
                    opening_message = VALUES(opening_message),
                    description = VALUES(description),
                    status = VALUES(status),
                    sort_order = VALUES(sort_order)
            ");

            foreach ($characters as $char) {
                $upsertStmt->execute($char);
            }

        } catch (Exception $e) {
            error_log('[CharacterMigration] Error: ' . $e->getMessage());
        }
    }
}
