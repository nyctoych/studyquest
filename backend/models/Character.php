<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/CharacterMigration.php';

class Character {
    private PDO $db;

    public function __construct() {
        CharacterMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * Get all active characters available for selection
     */
    public function getAllActive(): array {
        $stmt = $this->db->query("
            SELECT id, key_name, name, class_type, title, health, damage, crit_damage,
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path,
                   short_intro, lore, motivation, opening_message, description, status, sort_order
            FROM characters
            WHERE status = 'active'
            ORDER BY sort_order ASC, id ASC
        ");
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Find character by primary key ID
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, key_name, name, class_type, title, health, damage, crit_damage,
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path,
                   short_intro, lore, motivation, opening_message, description, status, sort_order
            FROM characters
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find character by key_name (e.g. 'mage', 'melee', 'range')
     */
    public function findByKey(string $keyName): ?array {
        $stmt = $this->db->prepare("
            SELECT id, key_name, name, class_type, title, health, damage, crit_damage,
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path,
                   short_intro, lore, motivation, opening_message, description, status, sort_order
            FROM characters
            WHERE key_name = ?
        ");
        $stmt->execute([strtolower(trim($keyName))]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get the active character selected by a specific user
     */
    public function getUserSelectedCharacter(int $userId): ?array {
        $stmt = $this->db->prepare("
            SELECT c.id, c.key_name, c.name, c.class_type, c.title, c.health, c.damage, c.crit_damage,
                   c.sprite_path, c.opening_gif_path, c.attack_asset_path, c.idle_asset_path,
                   c.short_intro, c.lore, c.motivation, c.opening_message, c.description, c.status
            FROM users u
            JOIN characters c ON u.selected_character_id = c.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Assign character to a user with strict server-side validation
     */
    public function setUserSelectedCharacter(int $userId, int $characterId): bool {
        $char = $this->findById($characterId);
        if (!$char || $char['status'] !== 'active') {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET selected_character_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([$characterId, $userId]);
    }

    /**
     * Centralized server-side stat calculation function (Section 1)
     * Level 1 to 50 scaling:
     * - Maximum level: 50 (Level 51 is strictly forbidden)
     * - Every level-up adds 10% of the ORIGINAL BASE ATTRIBUTES (non-compounding)
     * - Formula: bonus_percent = (level - 1) * 0.10
     * - scaled_attribute = base_attribute * (1 + bonus_percent)
     */
    public function getScaledCharacterStats(int $characterId, int $level): array {
        $char = $this->findById($characterId);
        if (!$char) {
            $char = $this->findByKey('mage') ?: [
                'health' => 150,
                'damage' => 10,
                'crit_damage' => 35
            ];
        }
        return self::calculateScaledStats($char, $level);
    }

    public static function calculateScaledStats(array $character, int $level): array {
        // Enforce Level 1 to 50 clamp strictly. No level 51.
        $clampedLevel = max(1, min(50, $level));

        $baseHealth = (int)($character['health'] ?? $character['base_health'] ?? 150);
        $baseDamage = (int)($character['damage'] ?? $character['base_damage'] ?? 10);
        $baseCrit = (int)($character['crit_damage'] ?? $character['base_crit_damage'] ?? 35);

        $bonusPercent = ($clampedLevel - 1) * 0.10;

        $scaledHealth = (int)round($baseHealth * (1.0 + $bonusPercent));
        $scaledDamage = (int)round($baseDamage * (1.0 + $bonusPercent));
        $scaledCrit = (int)round($baseCrit * (1.0 + $bonusPercent));

        $adventureStartHp = (int)round($scaledHealth * 0.45);

        return [
            'level' => $clampedLevel,
            'max_health' => $scaledHealth,
            'damage' => $scaledDamage,
            'crit_damage' => $scaledCrit,
            'bonus_percent' => $bonusPercent,
            'base_health' => $baseHealth,
            'base_damage' => $baseDamage,
            'base_crit_damage' => $baseCrit,
            'health' => $scaledHealth,
            'scaled_health' => $scaledHealth,
            'scaled_damage' => $scaledDamage,
            'scaled_crit_damage' => $scaledCrit,
            'player_max_health' => $scaledHealth,
            'character_damage' => $scaledDamage,
            'character_crit_damage' => $scaledCrit,
            'attack_power' => $scaledDamage,
            'critical_attack_power' => $scaledCrit,
            'adventure_start_hp' => $adventureStartHp,
        ];
    }
}

