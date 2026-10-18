<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/DodgePotionMigration.php';

class DodgePotionService {
    private static ?DodgePotionService $instance = null;
    private PDO $db;

    public function __construct() {
        DodgePotionMigration::run();
        $this->db = Database::getInstance();
    }

    public static function getInstance(): DodgePotionService {
        if (self::$instance === null) {
            self::$instance = new DodgePotionService();
        }
        return self::$instance;
    }

    /**
     * Get authoritative Dodge Potion allowance and purchased stock.
     * Automatically applies server-side daily replenishment (3 charges per day).
     */
    public function getCharges(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT dodge_potion_daily_date, dodge_potion_daily_charges, dodge_potion_purchased_stock
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'daily_charges' => 0,
                'daily_max' => 3,
                'purchased_stock' => 0,
                'total_available' => 0,
                'daily_date' => date('Y-m-d')
            ];
        }

        $today = date('Y-m-d');
        $dailyCharges = (int)($row['dodge_potion_daily_charges'] ?? 3);
        $dailyDate = $row['dodge_potion_daily_date'];
        $purchasedStock = max(0, (int)($row['dodge_potion_purchased_stock'] ?? 0));

        // If today is a new daily cycle, replenish daily charges to 3
        if ($dailyDate !== $today) {
            $dailyCharges = 3;
            $dailyDate = $today;
            $upStmt = $this->db->prepare("
                UPDATE users
                SET dodge_potion_daily_date = ?,
                    dodge_potion_daily_charges = 3
                WHERE id = ?
            ");
            $upStmt->execute([$today, $userId]);
        }

        return [
            'daily_charges' => $dailyCharges,
            'daily_max' => 3,
            'purchased_stock' => $purchasedStock,
            'total_available' => $dailyCharges + $purchasedStock,
            'daily_date' => $dailyDate
        ];
    }

    /**
     * Purchase a Dodge Potion in the Market (Price: 200 coins).
     * Server validates price, deducts coins, and credits purchased consumable inventory.
     */
    public function purchase(int $userId, int $itemId): array {
        $itemStmt = $this->db->prepare("SELECT * FROM items WHERE id = ? AND is_available = 1");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            return ['success' => false, 'message' => 'Item not found in Market.'];
        }

        $price = (int)$item['coin_cost'];
        if ($price <= 0) {
            $price = 200;
        }

        try {
            $this->db->beginTransaction();

            // Lock user row
            $uStmt = $this->db->prepare("
                SELECT id, coins, dodge_potion_purchased_stock
                FROM users
                WHERE id = ?
                FOR UPDATE
            ");
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'User account not found.'];
            }

            $currentCoins = (int)$user['coins'];
            if ($currentCoins < $price) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error_code' => 'INSUFFICIENT_COINS',
                    'message' => "Insufficient Scholar Coins. Dodge Potion costs {$price} coins."
                ];
            }

            // Deduct coins & increment purchased stock
            $newCoins = $currentCoins - $price;
            $newPurchasedStock = (int)$user['dodge_potion_purchased_stock'] + 1;

            $upStmt = $this->db->prepare("
                UPDATE users
                SET coins = ?,
                    points = ?,
                    dodge_potion_purchased_stock = ?
                WHERE id = ?
            ");
            $upStmt->execute([$newCoins, $newCoins, $newPurchasedStock, $userId]);

            // Log coin transaction
            $this->db->prepare("
                INSERT INTO coin_transactions
                (user_id, amount, type, source, reference_id, description, balance_after)
                VALUES (?, ?, 'spend', 'purchase', ?, 'Purchased: Dodge Potion', ?)
            ")->execute([$userId, $price, $itemId, $newCoins]);

            $this->db->commit();

            $chargeState = $this->getCharges($userId);

            return [
                'success' => true,
                'message' => 'Purchased Dodge Potion! Ready for battlefield use.',
                'new_coin_balance' => $newCoins,
                'potion_state' => $chargeState
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Database error during purchase: ' . $e->getMessage()];
        }
    }

    /**
     * Consume exactly one Dodge Potion use (daily allowance first, then purchased inventory).
     * Server-authoritative and atomic.
     */
    public function consume(int $userId, ?int $battleId = null, ?int $questionId = null): array {
        try {
            $this->db->beginTransaction();

            $uStmt = $this->db->prepare("
                SELECT id, dodge_potion_daily_date, dodge_potion_daily_charges, dodge_potion_purchased_stock
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

            $today = date('Y-m-d');
            $dailyDate = $user['dodge_potion_daily_date'];
            $dailyCharges = (int)($user['dodge_potion_daily_charges'] ?? 3);
            $purchasedStock = max(0, (int)($user['dodge_potion_purchased_stock'] ?? 0));

            // Refresh daily charges if new calendar day
            if ($dailyDate !== $today) {
                $dailyCharges = 3;
                $dailyDate = $today;
            }

            if ($dailyCharges <= 0 && $purchasedStock <= 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error_code' => 'NO_CHARGES',
                    'message' => 'No Dodge Potion charges remaining. Purchase more in the Market or await tomorrow\'s daily replenishment.'
                ];
            }

            // Consume daily allowance first
            $consumedType = 'daily';
            if ($dailyCharges > 0) {
                $dailyCharges -= 1;
                $consumedType = 'daily';
            } else {
                $purchasedStock -= 1;
                $consumedType = 'purchased';
            }

            $upStmt = $this->db->prepare("
                UPDATE users
                SET dodge_potion_daily_date = ?,
                    dodge_potion_daily_charges = ?,
                    dodge_potion_purchased_stock = ?
                WHERE id = ?
            ");
            $upStmt->execute([$dailyDate, $dailyCharges, $purchasedStock, $userId]);

            // Log consumption event
            $logStmt = $this->db->prepare("
                INSERT INTO dodge_potion_usage_log
                (user_id, battle_id, question_id, charge_type, remaining_daily, remaining_purchased)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $logStmt->execute([
                $userId,
                $battleId,
                $questionId,
                $consumedType,
                $dailyCharges,
                $purchasedStock
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'consumed_type' => $consumedType,
                'daily_charges' => $dailyCharges,
                'daily_max' => 3,
                'purchased_stock' => $purchasedStock,
                'total_available' => $dailyCharges + $purchasedStock
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to consume Dodge Potion: ' . $e->getMessage()];
        }
    }
}
