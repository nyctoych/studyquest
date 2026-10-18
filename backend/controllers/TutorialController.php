<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/TutorialProgress.php';

class TutorialController {
    private TutorialProgress $progressModel;

    public function __construct() {
        $this->progressModel = new TutorialProgress();
    }

    public function getStatus(): void {
        $userId = AuthMiddleware::userId();
        $status = $this->progressModel->getByUserId($userId);
        echo json_encode([
            'success' => true,
            'data' => $status,
            'tutorial' => $status
        ]);
    }

    public function saveProgress(): void {
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $this->progressModel->updateProgress($userId, $input);
        echo json_encode([
            'success' => true,
            'data' => $status,
            'tutorial' => $status
        ]);
    }

    public function complete(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressModel->completeTutorial($userId, true);
        echo json_encode([
            'success' => true,
            'message' => 'Tutorial successfully completed!',
            'data' => $result['status'],
            'tutorial' => $result['status'],
            'rewards' => $result['rewards'],
            'user' => $result['user'],
            'health' => $result['health']
        ]);
    }

    public function skip(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->progressModel->skipTutorial($userId);
        echo json_encode([
            'success' => true,
            'message' => 'Tutorial skipped.',
            'data' => $result['status'],
            'tutorial' => $result['status'],
            'user' => $result['user']
        ]);
    }

    public function reset(): void {
        $userId = AuthMiddleware::userId();
        $status = $this->progressModel->resetTutorial($userId);
        echo json_encode([
            'success' => true,
            'message' => 'Tutorial re-initialized for replay.',
            'data' => $status,
            'tutorial' => $status
        ]);
    }
}
