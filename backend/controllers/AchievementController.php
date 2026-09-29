<?php
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AchievementController {
    private Achievement $achievementModel;

    public function __construct() {
        $this->achievementModel = new Achievement();
    }

    public function index(): void {
        $userId       = AuthMiddleware::userId();
        $achievements = $this->achievementModel->getUserAchievements($userId);
        echo json_encode(['success' => true, 'achievements' => $achievements, 'data' => $achievements]);
    }

    public function all(): void {
        AuthMiddleware::check();
        $achievements = $this->achievementModel->getAll();
        echo json_encode(['success' => true, 'achievements' => $achievements, 'data' => $achievements]);
    }
}
