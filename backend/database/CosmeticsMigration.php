<?php
require_once __DIR__ . '/../config/database.php';

class CosmeticsMigration {
    private static bool $migrated = false;

    public static function run(): void {
        if (self::$migrated) return;
        self::$migrated = true;

        try {
            $db = Database::getInstance();

            // 1. Add extra columns to items if needed
            $cols = $db->query("SHOW COLUMNS FROM items")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('png_path', $cols)) {
                $db->exec("ALTER TABLE items ADD COLUMN png_path VARCHAR(255) NULL AFTER effect_key");
            }
            if (!in_array('gif_path', $cols)) {
                $db->exec("ALTER TABLE items ADD COLUMN gif_path VARCHAR(255) NULL AFTER png_path");
            }
            if (!in_array('character_class', $cols)) {
                $db->exec("ALTER TABLE items ADD COLUMN character_class VARCHAR(50) DEFAULT 'all' AFTER gif_path");
            }
            if (!in_array('is_default', $cols)) {
                $db->exec("ALTER TABLE items ADD COLUMN is_default TINYINT(1) DEFAULT 0 AFTER character_class");
            }
            if (!in_array('type', $cols)) {
                $db->exec("ALTER TABLE items ADD COLUMN type VARCHAR(50) NULL AFTER effect_key");
            }
            // Ensure rarity supports 'mythical'
            $db->exec("ALTER TABLE items MODIFY COLUMN rarity ENUM('common','rare','epic','legendary','mythical') DEFAULT 'common'");

            // 2. Ensure categories exist
            $catStmt = $db->prepare("SELECT id FROM item_categories WHERE slug = ?");
            
            $catStmt->execute(['attack_effect']);
            $attackCatId = $catStmt->fetchColumn();
            if (!$attackCatId) {
                $db->prepare("INSERT INTO item_categories (name, slug, icon) VALUES (?, ?, ?)")
                   ->execute(['Attack Effects', 'attack_effect', '⚔️']);
                $attackCatId = (int)$db->lastInsertId();
            }

            $catStmt->execute(['splash_effect']);
            $splashCatId = $catStmt->fetchColumn();
            if (!$splashCatId) {
                $db->prepare("INSERT INTO item_categories (name, slug, icon) VALUES (?, ?, ?)")
                   ->execute(['Splash Effects', 'splash_effect', '💥']);
                $splashCatId = (int)$db->lastInsertId();
            }

            $catStmt->execute(['potions']);
            $potionCatId = $catStmt->fetchColumn();
            if (!$potionCatId) {
                $catStmt->execute(['potion']);
                $potionCatId = $catStmt->fetchColumn();
            }
            if (!$potionCatId) {
                $db->prepare("INSERT INTO item_categories (name, slug, icon) VALUES (?, ?, ?)")
                   ->execute(['Potions', 'potions', '🧪']);
                $potionCatId = (int)$db->lastInsertId();
            }

            // 3. Define the products
            $products = [
                // Attack Effects: Mage
                [
                    'category_id' => $attackCatId,
                    'name' => 'Default Arcane Orb',
                    'description' => 'A focused sphere of twilight arcane essence, pulsing with dark wisdom.',
                    'effect_key' => 'default-orb',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.gif',
                    'character_class' => 'mage',
                    'is_default' => 1
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Elite Phantom Orb',
                    'description' => 'A spectral orb surrounded by swirling soul vapors.',
                    'effect_key' => 'elite-orb',
                    'rarity' => 'rare',
                    'coin_cost' => 3000,
                    'png_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/elite orb.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/elite orb.gif',
                    'character_class' => 'mage',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Epic Void Orb',
                    'description' => 'A dense nexus of abyssal gravity that distorts the battlefield.',
                    'effect_key' => 'epic-orb',
                    'rarity' => 'epic',
                    'coin_cost' => 6000,
                    'png_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/epic orb.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/epic orb.gif',
                    'character_class' => 'mage',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Mythic Nebula Orb',
                    'description' => 'An ancient cosmological singularity burning with radiant astral fury.',
                    'effect_key' => 'mythical-orb',
                    'rarity' => 'legendary',
                    'coin_cost' => 12000,
                    'png_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/mythical orb.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/mythical orb.gif',
                    'character_class' => 'mage',
                    'is_default' => 0
                ],

                // Attack Effects: Range
                [
                    'category_id' => $attackCatId,
                    'name' => 'Default Swift Arrow',
                    'description' => 'A sharp, aerodynamic fletched arrow loosed with deadly scholastic precision.',
                    'effect_key' => 'default-arrow',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.gif',
                    'character_class' => 'range',
                    'is_default' => 1
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Elite Falcon Arrow',
                    'description' => 'Infused with swift wind currents that whistle across the chamber.',
                    'effect_key' => 'elite-arrow',
                    'rarity' => 'rare',
                    'coin_cost' => 3000,
                    'png_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/elite arrow.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/elite arrow.gif',
                    'character_class' => 'range',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Epic Glacial Arrow',
                    'description' => 'Forged from perpetual frost, trailing crystallizing ice vapor.',
                    'effect_key' => 'epic-arrow',
                    'rarity' => 'epic',
                    'coin_cost' => 6000,
                    'png_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow epicc.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow epicc.gif',
                    'character_class' => 'range',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Mythic Solar Arrow',
                    'description' => 'A blinding shaft of golden luminescence that pierces all shadows.',
                    'effect_key' => 'myth-arrow',
                    'rarity' => 'legendary',
                    'coin_cost' => 12000,
                    'png_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow myth.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow myth.gif',
                    'character_class' => 'range',
                    'is_default' => 0
                ],

                // Attack Effects: Melee
                [
                    'category_id' => $attackCatId,
                    'name' => 'Default Crescent Cleave',
                    'description' => 'A fierce crescent wave cut through the air by twin balanced blades.',
                    'effect_key' => 'default-melee',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/default melee.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/default melee.gif',
                    'character_class' => 'melee',
                    'is_default' => 1
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Elite Tempest Slash',
                    'description' => 'A dual-cyclone blade strike that lacerates the space before the wielder.',
                    'effect_key' => 'elite-melee',
                    'rarity' => 'rare',
                    'coin_cost' => 3000,
                    'png_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.gif',
                    'character_class' => 'melee',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Epic Melee Slash',
                    'description' => 'An epic tempestuous crescent strike cleaving space and shattering defenses.',
                    'effect_key' => 'epic-melee',
                    'rarity' => 'epic',
                    'coin_cost' => 6000,
                    'png_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/epic melee.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/epic melee.gif',
                    'character_class' => 'melee',
                    'is_default' => 0
                ],
                [
                    'category_id' => $attackCatId,
                    'name' => 'Mythical Melee Cleave',
                    'description' => 'A legendary dual-blade vortex unleashing mythical crimson fury.',
                    'effect_key' => 'mythical-melee',
                    'rarity' => 'mythical',
                    'coin_cost' => 12000,
                    'png_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/mythical melee.png',
                    'gif_path' => 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/mythical melee.gif',
                    'character_class' => 'melee',
                    'is_default' => 0
                ],

                // 12 Splash Effects across Range, Melee, and Mage
                // RANGE (Archer)
                [
                    'category_id' => $splashCatId,
                    'name' => 'Default Arrow Splash',
                    'description' => 'A sharp, kinetic arrow impact dispersing crisp piercing energy.',
                    'effect_key' => 'default-arrow-splash',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/ARCHER (RANGE)/default arrow SPLASHH.gif',
                    'character_class' => 'range',
                    'is_default' => 1,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Elite Arrow Splash',
                    'description' => 'An elite piercing burst scattering concentrated emerald and azure force.',
                    'effect_key' => 'elite-arrow-splash',
                    'rarity' => 'rare',
                    'coin_cost' => 3500,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/ARCHER (RANGE)/elite arrow SPLASHH.gif',
                    'character_class' => 'range',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Epic Arrow Splash',
                    'description' => 'An explosive shockwave detonation that tears into target vitals.',
                    'effect_key' => 'epic-arrow-splash',
                    'rarity' => 'epic',
                    'coin_cost' => 7000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/ARCHER (RANGE)/epic arrow SPLASHH.gif',
                    'character_class' => 'range',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Mythical Arrow Splash',
                    'description' => 'A supreme celestial arrow detonation burning with mythical solar energy.',
                    'effect_key' => 'mythical-arrow-splash',
                    'rarity' => 'mythical',
                    'coin_cost' => 12000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/ARCHER (RANGE)/mythical arrow SPLASHH.gif',
                    'character_class' => 'range',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],

                // MELEE (Double Wielder / Dual-Wielder)
                [
                    'category_id' => $splashCatId,
                    'name' => 'Default Melee Splash',
                    'description' => 'A direct blade impact cross-slash slicing through enemy guard.',
                    'effect_key' => 'default-melee-splash',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/default melee SPLASH.gif',
                    'character_class' => 'melee',
                    'is_default' => 1,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Elite Melee Splash',
                    'description' => 'A swift double-crescent blade flurry erupting upon point-blank impact.',
                    'effect_key' => 'elite-melee-splash',
                    'rarity' => 'rare',
                    'coin_cost' => 3500,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/elite melee SPLASH.gif',
                    'character_class' => 'melee',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Epic Melee Splash',
                    'description' => 'A tempestuous multi-blade detonation that pulverizes surrounding matter.',
                    'effect_key' => 'epic-melee-splash',
                    'rarity' => 'epic',
                    'coin_cost' => 7000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/epic melee SPLASHH.gif',
                    'character_class' => 'melee',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Mythical Melee Splash',
                    'description' => 'A godlike dual-blade cleave fracturing reality with legendary crimson fury.',
                    'effect_key' => 'mythical-melee-splash',
                    'rarity' => 'mythical',
                    'coin_cost' => 12000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/mythical melee SPLASHH.gif',
                    'character_class' => 'melee',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],

                // MAGE (Necromancer)
                [
                    'category_id' => $splashCatId,
                    'name' => 'Default Orb Splash',
                    'description' => 'A shadowy orb explosion collapsing into an occult violet singularity.',
                    'effect_key' => 'default-orb-splash',
                    'rarity' => 'common',
                    'coin_cost' => 0,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/default orb SPLASHH.gif',
                    'character_class' => 'mage',
                    'is_default' => 1,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Elite Orb Splash',
                    'description' => 'An intense occult pulse radiating spectral flames across the impact point.',
                    'effect_key' => 'elite-orb-splash',
                    'rarity' => 'rare',
                    'coin_cost' => 3500,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/elite orb SPLASHH.gif',
                    'character_class' => 'mage',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Epic Orb Splash',
                    'description' => 'A devastating void star supernova discharging destructive mystic shocks.',
                    'effect_key' => 'epic-orb-splash',
                    'rarity' => 'epic',
                    'coin_cost' => 7000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/epic orb SPLASHH.gif',
                    'character_class' => 'mage',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                [
                    'category_id' => $splashCatId,
                    'name' => 'Mythical Orb Splash',
                    'description' => 'An eldritch cosmological rupture exploding with transcendent soul power.',
                    'effect_key' => 'mythical-orb-splash',
                    'rarity' => 'mythical',
                    'coin_cost' => 12000,
                    'png_path' => '',
                    'gif_path' => 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/mythical orb SPLASHH.gif',
                    'character_class' => 'mage',
                    'is_default' => 0,
                    'type' => 'splash_effect'
                ],
                // Potions
                [
                    'category_id' => $potionCatId,
                    'name' => 'Health Potion',
                    'description' => 'Restores 200 HP to your Adventure health.',
                    'effect_key' => 'health-potion',
                    'rarity' => 'rare',
                    'coin_cost' => 100,
                    'png_path' => 'assets/POTIONS/health potion.png',
                    'gif_path' => 'assets/POTIONS/health potion.gif',
                    'character_class' => 'all',
                    'is_default' => 0,
                    'type' => 'potion',
                    'icon' => '🧪'
                ]
            ];

            $checkStmt = $db->prepare("SELECT id FROM items WHERE effect_key = ? LIMIT 1");
            $insertStmt = $db->prepare("
                INSERT INTO items 
                (category_id, name, description, icon, coin_cost, rarity, is_available, effect_key, type, png_path, gif_path, character_class, is_default)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)
            ");
            $updateStmt = $db->prepare("
                UPDATE items 
                SET category_id = ?, name = ?, description = ?, icon = ?, coin_cost = ?, rarity = ?, type = ?, png_path = ?, gif_path = ?, character_class = ?, is_default = ?
                WHERE id = ?
            ");

            foreach ($products as $p) {
                $icon = $p['icon'] ?? '✨';
                $pType = $p['type'] ?? ($p['category_id'] === $potionCatId ? 'potion' : ($p['category_id'] === $splashCatId ? 'splash_effect' : 'attack_effect'));
                $checkStmt->execute([$p['effect_key']]);
                $existingId = $checkStmt->fetchColumn();
                if ($existingId) {
                    $updateStmt->execute([
                        $p['category_id'], $p['name'], $p['description'], $icon, $p['coin_cost'],
                        $p['rarity'], $pType, $p['png_path'], $p['gif_path'], $p['character_class'], $p['is_default'],
                        $existingId
                    ]);
                } else {
                    $insertStmt->execute([
                        $p['category_id'], $p['name'], $p['description'], $icon, $p['coin_cost'],
                        $p['rarity'], $p['effect_key'], $pType, $p['png_path'], $p['gif_path'], $p['character_class'], $p['is_default']
                    ]);
                }
            }

            // Sync type backfills
            $db->exec("UPDATE items SET type = 'attack_effect' WHERE category_id = {$attackCatId} AND (type IS NULL OR type = '')");
            $db->exec("UPDATE items SET type = 'splash_effect' WHERE category_id = {$splashCatId} AND (type IS NULL OR type = '')");
            $db->exec("UPDATE items SET type = 'potion' WHERE category_id = {$potionCatId} AND (type IS NULL OR type = '')");

        } catch (\Throwable $e) {
            error_log('[CosmeticsMigration Error] ' . $e->getMessage());
        }
    }
}
