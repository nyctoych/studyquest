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
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path, profile_avatar_path,
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
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path, profile_avatar_path,
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
                   sprite_path, opening_gif_path, attack_asset_path, idle_asset_path, profile_avatar_path,
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
                   c.sprite_path, c.opening_gif_path, c.attack_asset_path, c.idle_asset_path, c.profile_avatar_path,
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
     * Centralized server-side stat calculation function (Section 1 & Section 7)
     * Level 1 to 50 scaling:
     * - Maximum level: 50 (Level 51 is strictly forbidden)
     * - Every level-up adds 10% of the ORIGINAL BASE ATTRIBUTES (non-compounding)
     * - Formula: bonus_percent = (level - 1) * 0.10
     * - scaled_attribute = base_attribute * (1 + bonus_percent)
     * - Flat attribute milestone bonuses applied strictly ON TOP:
     *   Final Attack Damage  = Scaled Attack Damage  + Allocated Damage Bonuses (+10 per milestone)
     *   Final Critical Damage = Scaled Crit Damage    + Allocated Crit Bonuses   (+15 per milestone)
     *   Final Maximum Health = Scaled Max Health     + Allocated Health Bonuses (+25 per milestone)
     */
    public function getScaledCharacterStats(int $characterId, int $level, ?int $userId = null): array {
        $char = $this->findById($characterId);
        if (!$char) {
            $char = $this->findByKey('mage') ?: [
                'health' => 150,
                'damage' => 10,
                'crit_damage' => 35
            ];
        }

        $attributeBonuses = [];
        if ($userId !== null && $userId > 0) {
            require_once __DIR__ . '/AttributeAllocation.php';
            $allocModel = new AttributeAllocation();
            $attributeBonuses = $allocModel->getAllocationTotals($userId, $characterId);
        }

        return self::calculateScaledStats($char, $level, $attributeBonuses);
    }

    /**
     * Helper to retrieve full stats with allocations for a user's selected character
     */
    public function getScaledStatsForUser(int $userId, ?int $characterId = null, ?int $userLevel = null): array {
        if ($userLevel === null || $characterId === null) {
            $stmt = $this->db->prepare("SELECT level, selected_character_id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userLevel === null) {
                $userLevel = (int)($u['level'] ?? 1);
            }
            if ($characterId === null) {
                $characterId = (int)($u['selected_character_id'] ?? 1);
            }
        }

        return $this->getScaledCharacterStats($characterId ?: 1, $userLevel ?: 1, $userId);
    }

    public static function calculateScaledStats(array $character, int $level, array $attributeBonuses = []): array {
        // Enforce Level 1 to 50 clamp strictly. No level 51.
        $clampedLevel = max(1, min(50, $level));

        $baseHealth = (int)($character['health'] ?? $character['base_health'] ?? 150);
        $baseDamage = (int)($character['damage'] ?? $character['base_damage'] ?? 10);
        $baseCrit   = (int)($character['crit_damage'] ?? $character['base_crit_damage'] ?? 35);

        $bonusPercent = ($clampedLevel - 1) * 0.10;

        $scaledHealth = (int)round($baseHealth * (1.0 + $bonusPercent));
        $scaledDamage = (int)round($baseDamage * (1.0 + $bonusPercent));
        $scaledCrit   = (int)round($baseCrit * (1.0 + $bonusPercent));

        // Extract flat permanent milestone bonuses (either passed or nested in character)
        $allocatedDamage = (int)(
            $attributeBonuses['attack_damage'] ?? 
            $attributeBonuses['damage'] ?? 
            $character['allocated_bonuses']['attack_damage'] ?? 
            $character['allocated_damage'] ?? 
            0
        );
        $allocatedHealth = (int)(
            $attributeBonuses['health'] ?? 
            $attributeBonuses['max_health'] ?? 
            $character['allocated_bonuses']['health'] ?? 
            $character['allocated_health'] ?? 
            0
        );
        $allocatedCrit = (int)(
            $attributeBonuses['critical_damage'] ?? 
            $attributeBonuses['crit_damage'] ?? 
            $character['allocated_bonuses']['critical_damage'] ?? 
            $character['allocated_crit_damage'] ?? 
            0
        );

        // Additive application on top of scaled stats:
        // Final Attack Damage = Scaled Attack Damage + Allocated Attack Damage Bonuses
        // Final Critical Damage = Scaled Critical Damage + Allocated Critical Damage Bonuses
        // Final Maximum Health = Scaled Maximum Health + Allocated Health Bonuses
        $finalHealth = $scaledHealth + $allocatedHealth;
        $finalDamage = $scaledDamage + $allocatedDamage;
        $finalCrit   = $scaledCrit + $allocatedCrit;

        $adventureStartHp = (int)$finalHealth;

        return [
            'level' => $clampedLevel,
            // Final authoritative values (Scaled + Flat Milestone Bonuses)
            'max_health'            => $finalHealth,
            'damage'                => $finalDamage,
            'crit_damage'           => $finalCrit,
            'health'                => $finalHealth,
            'player_max_health'     => $finalHealth,
            'character_damage'      => $finalDamage,
            'character_crit_damage' => $finalCrit,
            'attack_power'          => $finalDamage,
            'critical_attack_power' => $finalCrit,
            'adventure_start_hp'    => $adventureStartHp,

            // Pure level-scaled stats without allocations
            'scaled_health'         => $scaledHealth,
            'scaled_damage'         => $scaledDamage,
            'scaled_crit_damage'    => $scaledCrit,
            'level_scaled_health'   => $scaledHealth,
            'level_scaled_damage'   => $scaledDamage,
            'level_scaled_crit'     => $scaledCrit,

            // Original base attributes
            'base_health'           => $baseHealth,
            'base_damage'           => $baseDamage,
            'base_crit_damage'      => $baseCrit,
            'bonus_percent'         => $bonusPercent,

            // Allocated bonuses breakdown
            'allocated_health'      => $allocatedHealth,
            'allocated_damage'      => $allocatedDamage,
            'allocated_crit_damage' => $allocatedCrit,
            'allocated_bonuses'     => [
                'attack_damage'   => $allocatedDamage,
                'health'          => $allocatedHealth,
                'critical_damage' => $allocatedCrit,
            ],
        ];
    }
}

