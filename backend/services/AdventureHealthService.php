<?php
/**
 * AdventureHealthService.php
 * Centralized, server-authoritative service for StudyQuest Adventure Health:
 * - Persistent Adventure HP carry-over
 * - Automatic passive regeneration (+50 HP per completed hour)
 * - Scaled Max HP capping based on Character + Level (1–50)
 * - Health Potion restoration (+200 HP clamped to Max HP)
 * - Battle damage persistence and defeat recovery
 * - Strict isolation from Arena
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../database/AdventureBattleMigration.php';
require_once __DIR__ . '/../models/Character.php';

class AdventureHealthService {
    private static ?AdventureHealthService $instance = null;
    private PDO $db;
    private Character $charModel;

    public const REGEN_HP_PER_HOUR = 50;
    public const SECONDS_PER_HOUR  = 3600;
    public const POTION_HEAL_AMOUNT = 200;

    public function __construct() {
        AdventureBattleMigration::run();
        $this->db = Database::getInstance();
        $this->charModel = new Character();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Resolves the current character and level scaled stats for a user.
     */
    public function getScaledStats(int $userId, ?int $userLevel = null, ?int $charId = null): array {
        if ($userLevel === null || $charId === null) {
            $uStmt = $this->db->prepare("SELECT level, selected_character_id FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            $userLevel = (int)($uRow['level'] ?? 1);
            $charId = (int)($uRow['selected_character_id'] ?? 1);
        }

        $char = $this->charModel->getUserSelectedCharacter($userId) 
             ?: ($charId ? $this->charModel->findById($charId) : null)
             ?: $this->charModel->findByKey('mage')
             ?: ['health' => 150, 'damage' => 10, 'crit_damage' => 35, 'name' => 'Mage'];

        $scaled = Character::calculateScaledStats($char, $userLevel);
        $maxHp = (int)($scaled['max_health'] ?? $scaled['scaled_health'] ?? $scaled['health'] ?? 150);
        $damage = (int)($scaled['damage'] ?? $scaled['scaled_damage'] ?? 10);
        $critDamage = (int)($scaled['crit_damage'] ?? $scaled['scaled_crit_damage'] ?? 35);

        return [
            'character' => $char,
            'character_name' => $char['name'] ?? 'Mage',
            'character_level' => $userLevel,
            'max_hp' => $maxHp,
            'damage' => $damage,
            'crit_damage' => $critDamage,
            'scaled' => $scaled,
        ];
    }

    /**
     * Loads authoritative Adventure HP, applies any pending completed-hour regeneration,
     * clamps to current scaled Max HP, and commits the state.
     * Uses row-level locking (FOR UPDATE) to prevent concurrent double-regeneration.
     */
    public function syncAndGetHealth(int $userId): array {
        $stats = $this->getScaledStats($userId);
        $maxHp = $stats['max_hp'];

        $inTx = $this->db->inTransaction();
        if (!$inTx) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare("
                SELECT id, level, selected_character_id, adventure_current_hp,
                       adventure_hp_persisted, health_regen_last_at
                FROM users
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if (!$inTx) $this->db->rollBack();
                return [
                    'current_hp' => $maxHp,
                    'max_hp' => $maxHp,
                    'regen_rate' => self::REGEN_HP_PER_HOUR,
                    'seconds_until_next_regen' => 0,
                    'last_regen_at' => date('Y-m-d H:i:s'),
                    'attack_power' => $stats['damage'],
                    'critical_attack_power' => $stats['crit_damage'],
                    'character_name' => $stats['character_name'],
                    'character_level' => $stats['character_level'],
                ];
            }

            $currentHp = $user['adventure_current_hp'];
            $isPersisted = (bool)($user['adventure_hp_persisted'] ?? 0);
            $lastRegenStr = $user['health_regen_last_at'];
            $now = time();

            // 1. Initialization Rule:
            // If persistent Adventure HP does not exist yet, initialize to 45% of scaled Max HP.
            if ($currentHp === null || !$isPersisted) {
                $currentHp = (int)round($maxHp * 0.45);
                $lastRegenStr = date('Y-m-d H:i:s', $now);

                $up = $this->db->prepare("
                    UPDATE users
                    SET adventure_current_hp = ?,
                        adventure_hp_persisted = 1,
                        health_regen_last_at = ?
                    WHERE id = ?
                ");
                $up->execute([$currentHp, $lastRegenStr, $userId]);
            } else {
                $currentHp = (int)$currentHp;
            }

            // Ensure lastRegenStr is valid
            $lastRegenTime = $lastRegenStr ? strtotime($lastRegenStr) : $now;
            if (!$lastRegenTime || $lastRegenTime > $now) {
                $lastRegenTime = $now;
                $lastRegenStr = date('Y-m-d H:i:s', $now);
            }

            // 2. Passive Regeneration Rule (+50 HP per completed hour):
            // Only applies if currentHp < maxHp
            $elapsedSeconds = max(0, $now - $lastRegenTime);
            $completedHours = (int)floor($elapsedSeconds / self::SECONDS_PER_HOUR);

            $recoveredHp = 0;
            if ($currentHp < $maxHp) {
                if ($completedHours > 0) {
                    $recoveredHp = $completedHours * self::REGEN_HP_PER_HOUR;
                    $currentHp = min($maxHp, $currentHp + $recoveredHp);

                    if ($currentHp >= $maxHp) {
                        // Reached full health: advance checkpoint to now so no excess healing accumulates
                        $lastRegenTime = $now;
                        $lastRegenStr = date('Y-m-d H:i:s', $now);
                    } else {
                        // Advance checkpoint by completed hours to preserve fractional progress
                        $lastRegenTime += ($completedHours * self::SECONDS_PER_HOUR);
                        $lastRegenStr = date('Y-m-d H:i:s', $lastRegenTime);
                    }

                    $up = $this->db->prepare("
                        UPDATE users
                        SET adventure_current_hp = ?,
                            health_regen_last_at = ?
                        WHERE id = ?
                    ");
                    $up->execute([$currentHp, $lastRegenStr, $userId]);
                }
            } else {
                // If already at or above max HP, clamp to Max HP and keep timestamp fresh
                if ($currentHp > $maxHp) {
                    $currentHp = $maxHp;
                    $up = $this->db->prepare("UPDATE users SET adventure_current_hp = ? WHERE id = ?");
                    $up->execute([$currentHp, $userId]);
                }
                $lastRegenTime = $now;
                $lastRegenStr = date('Y-m-d H:i:s', $now);
            }

            if (!$inTx) {
                $this->db->commit();
            }

            // Calculate countdown until next +50 HP tick
            $remainingSecondsInHour = 0;
            if ($currentHp < $maxHp) {
                $currentElapsed = max(0, time() - $lastRegenTime);
                $remainingSecondsInHour = max(0, self::SECONDS_PER_HOUR - ($currentElapsed % self::SECONDS_PER_HOUR));
            }

            return [
                'current_hp' => (int)$currentHp,
                'max_hp' => (int)$maxHp,
                'regen_rate' => self::REGEN_HP_PER_HOUR,
                'recovered_now' => $recoveredHp,
                'seconds_until_next_regen' => $remainingSecondsInHour,
                'last_regen_at' => $lastRegenStr,
                'attack_power' => $stats['damage'],
                'critical_attack_power' => $stats['crit_damage'],
                'character_name' => $stats['character_name'],
                'character_level' => $stats['character_level'],
                'is_full' => $currentHp >= $maxHp,
            ];

        } catch (\Throwable $e) {
            if (!$inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[AdventureHealthService Error] ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Purchases and immediately consumes a Health Potion:
     * - Server-authoritative validation of user, coins, and potion item
     * - Immediate +200 Current Adventure HP
     * - Clamped to scaled Max HP
     * - Rejects if HP is already full
     */
    public function purchaseHealthPotion(int $userId, int $itemId): array {
        // Fetch item details
        $iStmt = $this->db->prepare("
            SELECT i.*, ic.slug as category_slug
            FROM items i
            JOIN item_categories ic ON i.category_id = ic.id
            WHERE i.id = ? AND i.is_available = 1
        ");
        $iStmt->execute([$itemId]);
        $item = $iStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['success' => false, 'message' => 'Potion not found or unavailable.'];
        }

        $isPotion = ($item['type'] === 'potion' || $item['category_slug'] === 'potion' || $item['category_slug'] === 'potions');
        if (!$isPotion) {
            return ['success' => false, 'message' => 'This item is not a Health Potion.'];
        }

        $coinCost = (int)$item['coin_cost'];
        $healAmount = self::POTION_HEAL_AMOUNT; // Authoritative +200 HP

        $this->db->beginTransaction();
        try {
            // 1. Sync and lock user health and coins
            $uStmt = $this->db->prepare("
                SELECT id, coins, level, selected_character_id, adventure_current_hp,
                       adventure_hp_persisted, health_regen_last_at
                FROM users
                WHERE id = ?
                FOR UPDATE
            ");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'User not found.'];
            }

            // 2. Validate coins
            $userCoins = (int)$user['coins'];
            if ($userCoins < $coinCost) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error_code' => 'INSUFFICIENT_COINS',
                    'message' => 'Insufficient Scholar Coins for Health Potion.'
                ];
            }

            // 3. Calculate current scaled Max HP
            $stats = $this->getScaledStats($userId, (int)$user['level'], (int)$user['selected_character_id']);
            $maxHp = $stats['max_hp'];

            // 4. Calculate existing HP after pending passive regeneration
            $currentHp = $user['adventure_current_hp'];
            if ($currentHp === null || !(bool)$user['adventure_hp_persisted']) {
                $currentHp = (int)round($maxHp * 0.45);
            } else {
                $currentHp = (int)$currentHp;
            }

            $lastRegenTime = $user['health_regen_last_at'] ? strtotime($user['health_regen_last_at']) : time();
            $elapsedSeconds = max(0, time() - $lastRegenTime);
            $completedHours = (int)floor($elapsedSeconds / self::SECONDS_PER_HOUR);
            if ($completedHours > 0 && $currentHp < $maxHp) {
                $currentHp = min($maxHp, $currentHp + ($completedHours * self::REGEN_HP_PER_HOUR));
            }

            // 5. Full Health check
            if ($currentHp >= $maxHp) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error_code' => 'ALREADY_FULL_HEALTH',
                    'message' => 'Your Adventure Health is already at maximum!',
                    'current_hp' => $maxHp,
                    'max_hp' => $maxHp
                ];
            }

            // 6. Apply Healing clamped to Max HP
            $actualHealed = min($healAmount, $maxHp - $currentHp);
            $newHp = min($maxHp, $currentHp + $healAmount);

            // Deduct coins
            $newCoins = $userCoins - $coinCost;
            $this->db->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newCoins, $userId]);

            // Save new Adventure HP & update timestamp if full
            $newLastRegen = ($newHp >= $maxHp) ? date('Y-m-d H:i:s') : $user['health_regen_last_at'];
            if (!$newLastRegen) {
                $newLastRegen = date('Y-m-d H:i:s');
            }

            $up = $this->db->prepare("
                UPDATE users
                SET adventure_current_hp = ?,
                    adventure_hp_persisted = 1,
                    health_regen_last_at = ?
                WHERE id = ?
            ");
            $up->execute([$newHp, $newLastRegen, $userId]);

            // 7. Log Coin Transaction
            $txStmt = $this->db->prepare("
                INSERT INTO coin_transactions 
                (user_id, amount, type, source, reference_id, description, balance_after)
                VALUES (?, ?, 'spend', 'purchase', ?, ?, ?)
            ");
            $txStmt->execute([
                $userId,
                $coinCost,
                $itemId,
                "Purchased & Consumed Health Potion (+{$actualHealed} HP)",
                $newCoins
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Health Potion consumed! Restored {$actualHealed} HP.",
                'healed_amount' => $actualHealed,
                'potion_heal' => $healAmount,
                'current_hp' => $newHp,
                'max_hp' => $maxHp,
                'new_coin_balance' => $newCoins,
                'item' => $item,
            ];

        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[AdventureHealthService Purchase Error] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    /**
     * Saves the player's persistent Adventure HP state after an Adventure battle:
     * - Surviving victory / partial HP: persists remaining HP (clamped to 0..Max HP)
     * - Defeat (playerHpAfter <= 0 or battleStatus === 'defeat'): saves EXACTLY 0 HP (DO NOT reset to 45% or 1 HP)
     * - Preserves the ongoing health recovery timestamp checkpoint if already regenerating
     * - Strictly applies ONLY to Adventure battles (DOES NOT apply to Arena matches)
     */
    public function recordBattleOutcome(int $userId, int $playerHpAfter, string $battleStatus): array {
        $stats = $this->getScaledStats($userId);
        $maxHp = $stats['max_hp'];
        $nowStr = date('Y-m-d H:i:s');

        if ($battleStatus === 'defeat' || $playerHpAfter <= 0) {
            // Defeat state: Persistent Adventure HP MUST be 0.
            // The player remains at 0 until passive hourly regeneration (+50 HP) or Health Potion (+200 HP).
            $persistedHp = 0;
        } else {
            // Surviving HP: clamp between 1 and Max HP
            $persistedHp = min($maxHp, max(1, $playerHpAfter));
        }

        // Check if user already had an active regen timer that should be preserved
        $uStmt = $this->db->prepare("SELECT adventure_current_hp, health_regen_last_at FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $row = $uStmt->fetch(PDO::FETCH_ASSOC);

        $existingLastRegen = $row['health_regen_last_at'] ?? null;
        $wasFull = ($row && (int)($row['adventure_current_hp'] ?? 0) >= $maxHp);

        // If user was previously at full HP, start the timer now.
        // If user already had a running recovery timer, preserve it so their partial hour progress is NOT reset!
        $regenTimestampToSave = (!$existingLastRegen || $wasFull) ? $nowStr : $existingLastRegen;

        $stmt = $this->db->prepare("
            UPDATE users
            SET adventure_current_hp = ?,
                adventure_hp_persisted = 1,
                health_regen_last_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$persistedHp, $regenTimestampToSave, $userId]);

        return [
            'current_hp' => $persistedHp,
            'max_hp' => $maxHp,
            'last_regen_at' => $regenTimestampToSave,
            'is_defeated' => ($persistedHp < 1),
        ];
    }
}
