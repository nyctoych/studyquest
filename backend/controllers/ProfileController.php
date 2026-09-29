<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ProfileController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function show(): void {
        $userId = AuthMiddleware::userId();
        $user   = $this->userModel->findById($userId);
        $stats  = $this->userModel->getStats($userId);
        echo json_encode(['success' => true, 'user' => $user, 'stats' => $stats]);
    }

    public function update(): void {
        $userId = AuthMiddleware::userId();
        $data   = json_decode(file_get_contents('php://input'), true);

        $result = $this->userModel->update($userId, $data);
        $user   = $this->userModel->findById($userId);

        echo json_encode(['success' => true, 'message' => 'Profile updated!', 'user' => $user]);
    }

    public function stats(): void {
        $userId = AuthMiddleware::userId();
        $stats  = $this->userModel->getStats($userId);
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
