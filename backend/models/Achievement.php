<?php
require_once __DIR__ . '/../config/database.php';

class Achievement {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM achievements ORDER BY rarity DESC, condition_value ASC");
        return $stmt->fetchAll();
    }

    public function getUserAchievements(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT a.*, ua.unlocked_at
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY ua.unlocked_at DESC, a.rarity DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getUnlockedIds(int $userId): array {
        $stmt = $this->db->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'achievement_id');
    }

    public function unlock(int $userId, int $achievementId): bool {
        // Check if already unlocked
        $stmt = $this->db->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $stmt->execute([$userId, $achievementId]);
        if ($stmt->fetch()) return false; // already unlocked

        $stmt = $this->db->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
        return $stmt->execute([$userId, $achievementId]);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM achievements WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Check and unlock achievements based on current user stats.
     * Returns array of newly unlocked achievements.
     */
    public function checkAndUnlock(int $userId, array $context = []): array {
        $allAchievements = $this->getAll();
        $unlockedIds = $this->getUnlockedIds($userId);
        $newlyUnlocked = [];

        foreach ($allAchievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) continue;

            $conditionMet = false;
            $type = $achievement['condition_type'];
            $required = (int)$achievement['condition_value'];

            switch ($type) {
                case 'quests_completed':
                    $conditionMet = ($context['quests_completed'] ?? 0) >= $required;
                    break;
                case 'level_reached':
                    $conditionMet = ($context['level'] ?? 1) >= $required;
                    break;
                case 'streak_days':
                    $conditionMet = ($context['streak'] ?? 0) >= $required;
                    break;
                case 'bosses_defeated':
                    $conditionMet = ($context['bosses_defeated'] ?? 0) >= $required;
                    break;
                case 'points_earned':
                    $conditionMet = ($context['total_points'] ?? 0) >= $required;
                    break;
                case 'curriculum_parts_completed':
                    $conditionMet = ($context['curriculum_parts_completed'] ?? 0) >= $required;
                    break;
                case 'curriculum_domains_completed':
                    $conditionMet = ($context['curriculum_domains_completed'] ?? 0) >= $required;
                    break;
                case 'curriculum_domain_specific':
                    $conditionMet = ($context['curriculum_domain_specific'] ?? 0) == $required;
                    break;
            }

            if ($conditionMet) {
                $this->unlock($userId, $achievement['id']);
                $newlyUnlocked[] = $achievement;
            }
        }

        return $newlyUnlocked;
    }
}
