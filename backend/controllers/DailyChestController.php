<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../database/DailyChestMigration.php';

class DailyChestController {
    private PDO $db;
    private User $userModel;

    public const XP_REWARD = 1500;
    public const GOLD_REWARD = 300;

    public function __construct() {
        DailyChestMigration::run();
        $this->db = Database::getInstance();
        $this->userModel = new User();
    }

    public function getStatus(): void {
        $userId = AuthMiddleware::userId();
        $user = $this->userModel->findById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        $today = date('Y-m-d');
        $lastClaim = $user['last_daily_chest_at'] ?? null;
        $canClaim = empty($lastClaim) || ($lastClaim !== $today);

        $now = time();
        $tomorrowMidnight = strtotime('tomorrow 00:00:00');
        $secondsUntilReset = max(0, $tomorrowMidnight - $now);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'can_claim' => $canClaim,
            'xp_reward' => self::XP_REWARD,
            'gold_reward' => self::GOLD_REWARD,
            'last_claimed_at' => $lastClaim,
            'seconds_until_reset' => $secondsUntilReset
        ]);
    }

    public function claim(): void {
        $userId = AuthMiddleware::userId();
        $user = $this->userModel->findById($userId);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        $today = date('Y-m-d');
        $lastClaim = $user['last_daily_chest_at'] ?? null;

        if (!empty($lastClaim) && $lastClaim === $today) {
            $now = time();
            $tomorrowMidnight = strtotime('tomorrow 00:00:00');
            $secondsUntilReset = max(0, $tomorrowMidnight - $now);

            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'can_claim' => false,
                'message' => 'You have already collected your daily chest today. Return tomorrow!',
                'seconds_until_reset' => $secondsUntilReset
            ]);
            return;
        }

        // Award 1500 EXP and 300 Gold
        $xpRes = $this->userModel->addXP($userId, self::XP_REWARD);
        $this->userModel->addPoints($userId, self::GOLD_REWARD);
        $this->userModel->addCoins($userId, self::GOLD_REWARD);

        // Update last claim date
        $updateStmt = $this->db->prepare("UPDATE users SET last_daily_chest_at = ? WHERE id = ?");
        $updateStmt->execute([$today, $userId]);

        // Insert claim log
        $logStmt = $this->db->prepare("
            INSERT INTO daily_chest_claims (user_id, xp_awarded, coins_awarded, claimed_date, claimed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([$userId, self::XP_REWARD, self::GOLD_REWARD, $today]);

        $freshUser = $this->userModel->findById($userId);
        $now = time();
        $tomorrowMidnight = strtotime('tomorrow 00:00:00');
        $secondsUntilReset = max(0, $tomorrowMidnight - $now);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Daily Chest collected! +1,500 EXP and +300 Gold added to your account.',
            'xp_awarded' => self::XP_REWARD,
            'gold_awarded' => self::GOLD_REWARD,
            'leveled_up' => $xpRes['leveled_up'] ?? false,
            'new_level' => $xpRes['new_level'] ?? $freshUser['level'],
            'can_claim' => false,
            'seconds_until_reset' => $secondsUntilReset,
            'user' => $freshUser
        ]);
    }
}
