<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../models/SpecialEventAttempt.php';

class SpecialEventRewardService {
    private PDO $db;
    private User $userModel;
    private Achievement $achievementModel;
    private SpecialEventAttempt $attemptModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->achievementModel = new Achievement();
        $this->attemptModel = new SpecialEventAttempt();
    }

    /**
     * Calculate and apply rewards for an evaluated Special Event attempt.
     * Prevents duplicate reward claiming.
     */
    public function awardRewards(int $userId, int $attemptId, array $evalResult): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt) {
            return ['awarded' => false, 'message' => 'Attempt not found'];
        }

        // Prevent double reward payout
        if ((int)$attempt['xp_awarded'] > 0 || (int)$attempt['coins_awarded'] > 0) {
            return [
                'awarded' => false,
                'already_claimed' => true,
                'xp_awarded' => (int)$attempt['xp_awarded'],
                'coins_awarded' => (int)$attempt['coins_awarded']
            ];
        }

        $correctCount = (int) ($evalResult['correct_count'] ?? 0);
        $percentage = (float) ($evalResult['percentage'] ?? 0.0);
        $totalQuestions = (int) ($evalResult['total_questions'] ?? 140);

        // Fetch reward rates from settings
        $settings = $this->getRewardSettings();
        $baseXp = $settings['base_xp_reward'];
        $baseCoins = $settings['base_coin_reward'];
        $perCorrectXp = $settings['per_correct_xp'];
        $perCorrectCoins = $settings['per_correct_coins'];

        $totalXp = $baseXp + ($correctCount * $perCorrectXp);
        $totalCoins = $baseCoins + ($correctCount * $perCorrectCoins);

        // Perfect score bonus
        $isPerfect = ($correctCount >= $totalQuestions && $totalQuestions > 0);
        if ($isPerfect) {
            $totalXp += $settings['perfect_score_bonus_xp'];
            $totalCoins += $settings['perfect_score_bonus_coins'];
        }

        // Passing bonus (>= 75%)
        $isPassed = ($percentage >= $settings['passing_percentage']);
        if ($isPassed) {
            $totalXp += 250;
            $totalCoins += 100;
        }

        // Credit User XP & Coins
        $xpResult = $this->userModel->addXP($userId, $totalXp);
        $this->userModel->addCoins($userId, $totalCoins);

        // Update attempt record with awarded rewards
        $this->attemptModel->update($attemptId, [
            'xp_awarded' => $totalXp,
            'coins_awarded' => $totalCoins
        ]);

        // Track achievements
        $unlockedAchievements = $this->checkSpecialEventAchievements($userId, $correctCount, $percentage, $isPerfect);

        return [
            'awarded' => true,
            'xp_gained' => $totalXp,
            'coins_gained' => $totalCoins,
            'is_perfect' => $isPerfect,
            'is_passed' => $isPassed,
            'new_level' => $xpResult['new_level'] ?? null,
            'leveled_up' => $xpResult['leveled_up'] ?? false,
            'unlocked_achievements' => $unlockedAchievements
        ];
    }

    private function getRewardSettings(): array {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM special_event_settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'base_xp_reward' => (int) ($settings['base_xp_reward'] ?? 500),
            'base_coin_reward' => (int) ($settings['base_coin_reward'] ?? 250),
            'per_correct_xp' => (int) ($settings['per_correct_xp'] ?? 5),
            'per_correct_coins' => (int) ($settings['per_correct_coins'] ?? 2),
            'perfect_score_bonus_xp' => (int) ($settings['perfect_score_bonus_xp'] ?? 1000),
            'perfect_score_bonus_coins' => (int) ($settings['perfect_score_bonus_coins'] ?? 500),
            'passing_percentage' => (float) ($settings['passing_percentage'] ?? 75.0)
        ];
    }

    private function checkSpecialEventAchievements(int $userId, int $score, float $percentage, bool $isPerfect): array {
        $unlocked = [];
        try {
            // Count total completed special event attempts
            $countStmt = $this->db->prepare("
                SELECT COUNT(*) FROM special_event_attempts 
                WHERE user_id = ? AND status = 'completed'
            ");
            $countStmt->execute([$userId]);
            $completedTrials = (int) $countStmt->fetchColumn();

            $unlocked = $this->achievementModel->checkAndUnlock($userId, [
                'special_events_completed' => $completedTrials,
                'special_event_score' => $score,
                'special_event_percentage' => $percentage,
                'special_event_perfect' => $isPerfect ? 1 : 0
            ]);
        } catch (Throwable $e) {
            error_log("SpecialEventRewardService achievement error: " . $e->getMessage());
        }
        return $unlocked;
    }
}
