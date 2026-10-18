<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/CharacterAttributeAllocationMigration.php';
require_once __DIR__ . '/Character.php';

class AttributeAllocation {
    public const MILESTONES = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50];

    public const BONUSES = [
        'attack_damage'   => 10,
        'health'          => 25,
        'critical_damage' => 15,
    ];

    public const ATTRIBUTE_DESCRIPTIONS = [
        'attack_damage'   => "Increase your character's Attack Damage by +10. Deal more damage with normal attacks.",
        'health'          => "Increase your character's Maximum Health by +25. Become more durable in battle.",
        'critical_damage' => "Increase your character's Critical Damage by +15. Make critical attacks more powerful.",
    ];

    private PDO $db;

    public function __construct() {
        CharacterAttributeAllocationMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * Get all attribute allocations for a user and optional character
     */
    public function getAllocations(int $userId, ?int $characterId = null): array {
        if ($characterId !== null) {
            $stmt = $this->db->prepare("
                SELECT id, user_id, character_id, milestone_level, attribute_type, bonus_value, status, created_at
                FROM user_attribute_allocations
                WHERE user_id = ? AND character_id = ?
                ORDER BY milestone_level ASC
            ");
            $stmt->execute([$userId, $characterId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT id, user_id, character_id, milestone_level, attribute_type, bonus_value, status, created_at
                FROM user_attribute_allocations
                WHERE user_id = ?
                ORDER BY milestone_level ASC
            ");
            $stmt->execute([$userId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get aggregate bonus totals for a user and specific character
     */
    public function getAllocationTotals(int $userId, ?int $characterId = null): array {
        $allocations = $this->getAllocations($userId, $characterId);

        $damageBonus = 0;
        $healthBonus = 0;
        $critBonus   = 0;
        $byMilestone = [];

        foreach ($allocations as $a) {
            $m = (int)$a['milestone_level'];
            $type = (string)$a['attribute_type'];
            $val = (int)$a['bonus_value'];

            if ($type === 'attack_damage') {
                $damageBonus += $val;
            } elseif ($type === 'health') {
                $healthBonus += $val;
            } elseif ($type === 'critical_damage') {
                $critBonus += $val;
            }
            $byMilestone[$m] = $a;
        }

        return [
            'attack_damage'   => $damageBonus,
            'health'          => $healthBonus,
            'critical_damage' => $critBonus,
            'damage'          => $damageBonus,
            'max_health'      => $healthBonus,
            'crit_damage'     => $critBonus,
            'count'           => count($allocations),
            'completed_count' => count($allocations),
            'total_max'       => count(self::MILESTONES),
            'by_milestone'    => $byMilestone,
            'allocations'     => $allocations,
        ];
    }

    /**
     * Get all pending (unclaimed) milestones <= current level, sorted ascending
     */
    public function getPendingMilestones(int $userId, int $currentLevel, ?int $characterId = null): array {
        $clampedLevel = max(1, min(50, $currentLevel));
        $allocations = $this->getAllocations($userId, $characterId);
        $claimedMilestones = array_map(fn($a) => (int)$a['milestone_level'], $allocations);

        $pending = [];
        foreach (self::MILESTONES as $milestone) {
            if ($milestone <= $clampedLevel && !in_array($milestone, $claimedMilestones, true)) {
                $pending[] = $milestone;
            }
        }
        sort($pending, SORT_NUMERIC);
        return $pending;
    }

    /**
     * Claim an attribute milestone with strict server-side validation and concurrency protection
     */
    public function claimMilestone(int $userId, int $milestoneLevel, string $attributeType, ?int $characterId = null): array {
        if (!in_array($milestoneLevel, self::MILESTONES, true)) {
            return [
                'success' => false,
                'message' => "Invalid milestone level {$milestoneLevel}. Milestones are available at multiples of 5 up to Level 50."
            ];
        }

        if (!array_key_exists($attributeType, self::BONUSES)) {
            return [
                'success' => false,
                'message' => "Invalid attribute type. Must be 'attack_damage', 'health', or 'critical_damage'."
            ];
        }

        $serverBonusValue = self::BONUSES[$attributeType];

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            // 1. Fetch user authoritative level and selected character with row locking
            $uStmt = $this->db->prepare("
                SELECT id, level, selected_character_id, adventure_current_hp
                FROM users
                WHERE id = ?
                FOR UPDATE
            ");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if (!$inTx) $this->db->rollBack();
                return ['success' => false, 'message' => 'User not found.'];
            }

            $currentLevel = (int)($user['level'] ?? 1);
            if ($currentLevel < $milestoneLevel) {
                if (!$inTx) $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "Milestone Level {$milestoneLevel} has not been reached yet (current Level is {$currentLevel})."
                ];
            }

            // Resolve target character ID
            $resolvedCharId = $characterId !== null ? (int)$characterId : (int)($user['selected_character_id'] ?? 1);
            if ($resolvedCharId <= 0) {
                $resolvedCharId = 1;
            }

            // Verify character exists
            $charModel = new Character();
            $char = $charModel->findById($resolvedCharId);
            if (!$char || ($char['status'] ?? '') !== 'active') {
                if (!$inTx) $this->db->rollBack();
                return ['success' => false, 'message' => 'Selected character is invalid or inactive.'];
            }

            // 2. Check if milestone was already claimed
            $checkStmt = $this->db->prepare("
                SELECT id FROM user_attribute_allocations
                WHERE user_id = ? AND character_id = ? AND milestone_level = ?
                FOR UPDATE
            ");
            $checkStmt->execute([$userId, $resolvedCharId, $milestoneLevel]);
            if ($checkStmt->fetch()) {
                if (!$inTx) $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => "Milestone Level {$milestoneLevel} has already been allocated for this character."
                ];
            }

            // 3. Compute stats before allocation for health persistence calculations
            $totalsBefore = $this->getAllocationTotals($userId, $resolvedCharId);
            $scaledBefore = Character::calculateScaledStats($char, $currentLevel, $totalsBefore);
            $oldMaxHp = $scaledBefore['max_health'];

            // 4. Insert allocation record
            $insStmt = $this->db->prepare("
                INSERT INTO user_attribute_allocations (
                    user_id, character_id, milestone_level, attribute_type, bonus_value, status
                ) VALUES (?, ?, ?, ?, ?, 'applied')
            ");
            $insStmt->execute([
                $userId,
                $resolvedCharId,
                $milestoneLevel,
                $attributeType,
                $serverBonusValue
            ]);
            $allocationId = (int)$this->db->lastInsertId();

            // 5. Update health persistence if health was increased
            $totalsAfter = $this->getAllocationTotals($userId, $resolvedCharId);
            $scaledAfter = Character::calculateScaledStats($char, $currentLevel, $totalsAfter);
            $newMaxHp = $scaledAfter['max_health'];

            if ($attributeType === 'health') {
                $currentHp = $user['adventure_current_hp'] !== null ? (int)$user['adventure_current_hp'] : null;
                if ($currentHp !== null) {
                    // If player was at full health, keep them at full health
                    if ($currentHp >= $oldMaxHp) {
                        $currentHp = $newMaxHp;
                    } else {
                        // Otherwise, preserve current HP clamped to new max HP
                        $currentHp = min($newMaxHp, $currentHp);
                    }
                    $upHpStmt = $this->db->prepare("
                        UPDATE users
                        SET adventure_current_hp = ?
                        WHERE id = ?
                    ");
                    $upHpStmt->execute([$currentHp, $userId]);
                }
            }

            if (!$inTx) {
                $this->db->commit();
            }

            // Get remaining pending milestones
            $pendingAfter = $this->getPendingMilestones($userId, $currentLevel, $resolvedCharId);

            return [
                'success' => true,
                'message' => "Successfully allocated +{$serverBonusValue} " . str_replace('_', ' ', $attributeType) . "!",
                'claimed_allocation' => [
                    'id'              => $allocationId,
                    'milestone_level' => $milestoneLevel,
                    'attribute_type'  => $attributeType,
                    'bonus_value'     => $serverBonusValue,
                ],
                'character'             => $char,
                'totals'                => $totalsAfter,
                'completed_count'       => $totalsAfter['completed_count'],
                'total_max'             => count(self::MILESTONES),
                'pending_milestones'    => $pendingAfter,
                'has_pending'           => !empty($pendingAfter),
                'next_pending_milestone'=> !empty($pendingAfter) ? $pendingAfter[0] : null,
                'scaled_stats'          => $scaledAfter,
                'attack_power'          => $scaledAfter['damage'],
                'critical_attack_power' => $scaledAfter['crit_damage'],
                'max_health'            => $scaledAfter['max_health'],
            ];
        } catch (PDOException $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'uq_user_char_milestone')) {
                return [
                    'success' => false,
                    'message' => "Milestone Level {$milestoneLevel} was already allocated for this character."
                ];
            }
            return [
                'success' => false,
                'message' => "Database error during allocation: " . $e->getMessage()
            ];
        } catch (Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => "Allocation error: " . $e->getMessage()
            ];
        }
    }
}
