<?php
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../services/AchievementManagerService.php';

class AchievementController {
    private AchievementManagerService $achievementService;

    public function __construct() {
        $this->achievementService = AchievementManagerService::getInstance();
    }

    /**
     * Get Achievement Hall data for authenticated player.
     * Evaluates server-authoritative progress across all 30 cards / 32 milestones.
     */
    public function index(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();

        $data = $this->achievementService->getAchievementHallData($userId);

        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'summary'      => $data['summary'],
            'cards'        => $data['cards'],
            'achievements' => $data['cards'], // Backward compatibility
            'data'         => $data['cards']
        ]);
    }

    /**
     * Atomically claim a verified milestone reward.
     */
    public function claim(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();

        $input = json_decode(file_get_contents('php://input'), true);
        $milestoneId = isset($input['milestone_id']) ? (int)$input['milestone_id'] : (int)($_POST['milestone_id'] ?? 0);

        if ($milestoneId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid milestone ID provided.']);
            return;
        }

        $result = $this->achievementService->claimMilestone($userId, $milestoneId);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Atomically claim ALL verified and claimable milestone rewards at once.
     */
    public function claimAll(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();

        $result = $this->achievementService->claimAllMilestones($userId);

        if (!$result['success']) {
            http_response_code($result['status_code'] ?? 400);
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Public endpoint to view a shared achievement without authentication.
     */
    public function publicShare(string $token): void {
        $cleanToken = trim($token);
        if (empty($cleanToken)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Share token required.']);
            return;
        }

        $shareData = $this->achievementService->getPublicShareData($cleanToken);

        if (!$shareData) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Shared achievement not found or expired.']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data'    => $shareData
        ]);
    }
}
