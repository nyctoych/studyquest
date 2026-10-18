<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/../services/AdventureHealthService.php';

class TutorialProgress {
    private PDO $db;
    private User $userModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->userModel = new User();
    }

    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM user_tutorial_progress WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $user = $this->userModel->findById($userId);
        $isNewPlayer = (bool)($user['is_new_player'] ?? false);

        if (!$row) {
            // If existing user (not new player), consider tutorial completed so we never force them
            if (!$isNewPlayer) {
                return [
                    'user_id' => $userId,
                    'is_new_player' => false,
                    'tutorial_started' => 1,
                    'tutorial_phase' => 'completed',
                    'tutorial_step' => 11,
                    'battlefield_completed' => 1,
                    'tutorial_completed' => 1,
                    'tutorial_skipped' => 0,
                    'last_completed_step' => 11,
                ];
            }

            // For brand-new player, insert initial progress
            $ins = $this->db->prepare("
                INSERT INTO user_tutorial_progress 
                (user_id, tutorial_started, tutorial_phase, tutorial_step, battlefield_completed, tutorial_completed, tutorial_skipped, last_completed_step)
                VALUES (?, 1, 'battlefield', 0, 0, 0, 0, 0)
                ON DUPLICATE KEY UPDATE tutorial_started = 1
            ");
            $ins->execute([$userId]);

            return [
                'user_id' => $userId,
                'is_new_player' => true,
                'tutorial_started' => 1,
                'tutorial_phase' => 'battlefield',
                'tutorial_step' => 0,
                'battlefield_completed' => 0,
                'tutorial_completed' => 0,
                'tutorial_skipped' => 0,
                'last_completed_step' => 0,
            ];
        }

        return [
            'user_id' => (int)$row['user_id'],
            'is_new_player' => $isNewPlayer,
            'tutorial_started' => (int)$row['tutorial_started'],
            'tutorial_phase' => $row['tutorial_phase'],
            'tutorial_step' => (int)$row['tutorial_step'],
            'battlefield_completed' => (int)$row['battlefield_completed'],
            'tutorial_completed' => (int)$row['tutorial_completed'],
            'tutorial_skipped' => (int)$row['tutorial_skipped'],
            'last_completed_step' => (int)$row['last_completed_step'],
        ];
    }

    public function updateProgress(int $userId, array $data): array {
        $current = $this->getByUserId($userId);

        $phase = $data['phase'] ?? $data['tutorial_phase'] ?? $current['tutorial_phase'];
        $step = isset($data['step']) ? (int)$data['step'] : (isset($data['tutorial_step']) ? (int)$data['tutorial_step'] : $current['tutorial_step']);
        $battlefieldCompleted = isset($data['battlefield_completed']) ? (int)$data['battlefield_completed'] : $current['battlefield_completed'];
        $lastCompletedStep = max($current['last_completed_step'], isset($data['last_completed_step']) ? (int)$data['last_completed_step'] : $step);

        $stmt = $this->db->prepare("
            INSERT INTO user_tutorial_progress 
            (user_id, tutorial_started, tutorial_phase, tutorial_step, battlefield_completed, tutorial_completed, tutorial_skipped, last_completed_step)
            VALUES (?, 1, ?, ?, ?, 0, 0, ?)
            ON DUPLICATE KEY UPDATE
                tutorial_phase = VALUES(tutorial_phase),
                tutorial_step = VALUES(tutorial_step),
                battlefield_completed = VALUES(battlefield_completed),
                last_completed_step = VALUES(last_completed_step)
        ");
        $stmt->execute([$userId, $phase, $step, $battlefieldCompleted, $lastCompletedStep]);

        return $this->getByUserId($userId);
    }

    public function completeTutorial(int $userId, bool $grantRewards = true): array {
        $current = $this->getByUserId($userId);
        $wasAlreadyCompleted = ($current['tutorial_completed'] == 1);

        $stmt = $this->db->prepare("
            INSERT INTO user_tutorial_progress 
            (user_id, tutorial_started, tutorial_phase, tutorial_step, battlefield_completed, tutorial_completed, tutorial_skipped, last_completed_step)
            VALUES (?, 1, 'completed', 11, 1, 1, 0, 11)
            ON DUPLICATE KEY UPDATE
                tutorial_phase = 'completed',
                tutorial_step = 11,
                battlefield_completed = 1,
                tutorial_completed = 1,
                tutorial_skipped = 0,
                last_completed_step = 11
        ");
        $stmt->execute([$userId]);

        // Remove new player flag
        $this->db->prepare("UPDATE users SET is_new_player = 0 WHERE id = ?")->execute([$userId]);

        $rewards = ['xp' => 0, 'coins' => 0];
        if ($grantRewards && !$wasAlreadyCompleted) {
            $this->userModel->addXP($userId, 100);
            $this->userModel->addCoins($userId, 50);
            $rewards = ['xp' => 100, 'coins' => 50];
        }

        // Restore adventure health to full
        $healthService = AdventureHealthService::getInstance();
        $stats = $healthService->getScaledStats($userId);
        $fullHp = (int)($stats['max_hp'] ?? 150);

        $this->db->prepare("
            UPDATE users
            SET adventure_current_hp = ?,
                adventure_hp_persisted = 1,
                health_regen_last_at = NOW()
            WHERE id = ?
        ")->execute([$fullHp, $userId]);

        $user = $this->userModel->findById($userId);
        $healthState = $healthService->syncAndGetHealth($userId);

        return [
            'status' => $this->getByUserId($userId),
            'rewards' => $rewards,
            'health' => $healthState,
            'user' => $user
        ];
    }

    public function skipTutorial(int $userId): array {
        $stmt = $this->db->prepare("
            INSERT INTO user_tutorial_progress 
            (user_id, tutorial_started, tutorial_phase, tutorial_step, battlefield_completed, tutorial_completed, tutorial_skipped, last_completed_step)
            VALUES (?, 1, 'completed', 11, 1, 1, 1, 11)
            ON DUPLICATE KEY UPDATE
                tutorial_phase = 'completed',
                tutorial_completed = 1,
                tutorial_skipped = 1
        ");
        $stmt->execute([$userId]);

        // Remove new player flag without granting rewards
        $this->db->prepare("UPDATE users SET is_new_player = 0 WHERE id = ?")->execute([$userId]);

        // Restore health to full
        $healthService = AdventureHealthService::getInstance();
        $stats = $healthService->getScaledStats($userId);
        $fullHp = (int)($stats['max_hp'] ?? 150);

        $this->db->prepare("
            UPDATE users
            SET adventure_current_hp = ?,
                adventure_hp_persisted = 1,
                health_regen_last_at = NOW()
            WHERE id = ?
        ")->execute([$fullHp, $userId]);

        $user = $this->userModel->findById($userId);

        return [
            'status' => $this->getByUserId($userId),
            'user' => $user
        ];
    }

    public function resetTutorial(int $userId): array {
        // Reset tutorial progress for replay (does NOT alter user character, level, or existing progression)
        $stmt = $this->db->prepare("
            INSERT INTO user_tutorial_progress 
            (user_id, tutorial_started, tutorial_phase, tutorial_step, battlefield_completed, tutorial_completed, tutorial_skipped, last_completed_step)
            VALUES (?, 1, 'battlefield', 0, 0, 0, 0, 0)
            ON DUPLICATE KEY UPDATE
                tutorial_started = 1,
                tutorial_phase = 'battlefield',
                tutorial_step = 0,
                battlefield_completed = 0,
                tutorial_completed = 0,
                tutorial_skipped = 0,
                last_completed_step = 0
        ");
        $stmt->execute([$userId]);

        return $this->getByUserId($userId);
    }
}
