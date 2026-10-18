<?php
require_once __DIR__ . '/../models/BattlefieldPreference.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class BattlefieldPreferenceController {
    private BattlefieldPreference $prefModel;

    public function __construct() {
        $this->prefModel = new BattlefieldPreference();
    }

    /**
     * GET /battlefield/preferences
     */
    public function getPreferences(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $prefs = $this->prefModel->getByUserId($userId);
        echo json_encode([
            'success'     => true,
            'preferences' => $prefs,
        ]);
    }

    /**
     * POST /battlefield/preferences
     */
    public function updatePreferences(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $updated = $this->prefModel->savePreferences($userId, $input);
        echo json_encode([
            'success'     => true,
            'preferences' => $updated,
            'message'     => 'Battlefield preferences saved.',
        ]);
    }
}
