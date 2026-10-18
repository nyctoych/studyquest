<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/BattlefieldPreferencesMigration.php';

class BattlefieldPreference {
    private PDO $db;

    public function __construct() {
        BattlefieldPreferencesMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * Retrieve persistent battlefield toggle preferences for a user.
     * Default state is strictly false (OFF) for all toggles if no record exists.
     */
    public function getByUserId(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT skip_animation, auto_confirm, critical_mode
            FROM user_battlefield_preferences
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'skip_animation' => false,
                'auto_confirm'   => false,
                'critical_mode'  => false,
            ];
        }

        return [
            'skip_animation' => (bool)$row['skip_animation'],
            'auto_confirm'   => (bool)$row['auto_confirm'],
            'critical_mode'  => (bool)$row['critical_mode'],
        ];
    }

    /**
     * Persist toggle preferences independently for a user account.
     */
    public function savePreferences(int $userId, array $prefs): array {
        $current = $this->getByUserId($userId);

        $skipAnimation = array_key_exists('skip_animation', $prefs)
            ? filter_var($prefs['skip_animation'], FILTER_VALIDATE_BOOLEAN)
            : $current['skip_animation'];

        $autoConfirm = array_key_exists('auto_confirm', $prefs)
            ? filter_var($prefs['auto_confirm'], FILTER_VALIDATE_BOOLEAN)
            : $current['auto_confirm'];

        $criticalMode = array_key_exists('critical_mode', $prefs)
            ? filter_var($prefs['critical_mode'], FILTER_VALIDATE_BOOLEAN)
            : $current['critical_mode'];

        $stmt = $this->db->prepare("
            INSERT INTO user_battlefield_preferences (user_id, skip_animation, auto_confirm, critical_mode)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                skip_animation = VALUES(skip_animation),
                auto_confirm = VALUES(auto_confirm),
                critical_mode = VALUES(critical_mode),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $userId,
            $skipAnimation ? 1 : 0,
            $autoConfirm ? 1 : 0,
            $criticalMode ? 1 : 0,
        ]);

        return [
            'skip_animation' => $skipAnimation,
            'auto_confirm'   => $autoConfirm,
            'critical_mode'  => $criticalMode,
        ];
    }
}
