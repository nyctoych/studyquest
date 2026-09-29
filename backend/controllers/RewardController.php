<?php
require_once __DIR__ . '/../models/Reward.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class RewardController {
    private Reward $rewardModel;

    public function __construct() {
        $this->rewardModel = new Reward();
    }

    public function index(): void {
        AuthMiddleware::check();
        $category = $_GET['category'] ?? '';
        $rewards  = $this->rewardModel->getAll($category);
        echo json_encode(['success' => true, 'rewards' => $rewards, 'data' => $rewards]);
    }

    public function purchase(int $rewardId): void {
        $userId = AuthMiddleware::userId();
        $result = $this->rewardModel->purchase($userId, $rewardId);
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
    }

    public function inventory(): void {
        $userId    = AuthMiddleware::userId();
        $inventory = $this->rewardModel->getUserInventory($userId);
        echo json_encode(['success' => true, 'inventory' => $inventory, 'data' => $inventory]);
    }

    public function useItem(int $userRewardId): void {
        $userId = AuthMiddleware::userId();
        $result = $this->rewardModel->useReward($userId, $userRewardId);
        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
    }
}
