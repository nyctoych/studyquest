<?php
require_once __DIR__ . '/../models/Quest.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../services/CurriculumService.php';

class QuestController {
    private Quest $questModel;
    private User $userModel;
    private Achievement $achievementModel;

    public function __construct() {
        $this->questModel       = new Quest();
        $this->userModel        = new User();
        $this->achievementModel = new Achievement();
    }

    public function index(): void {
        $userId  = AuthMiddleware::userId();
        $filters = [
            'status'     => $_GET['status'] ?? '',
            'subject'    => $_GET['subject'] ?? '',
            'difficulty' => $_GET['difficulty'] ?? '',
            'sort'       => $_GET['sort'] ?? 'newest',
        ];
        $quests = $this->questModel->getAll($userId, $filters);
        echo json_encode(['success' => true, 'quests' => $quests, 'data' => $quests]);
    }

    public function show(int $id): void {
        $userId = AuthMiddleware::userId();
        $quest  = $this->questModel->getById($id, $userId);
        if (!$quest) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quest not found']);
            return;
        }
        echo json_encode(['success' => true, 'quest' => $quest, 'data' => $quest]);
    }

    public function store(): void {
        $userId = AuthMiddleware::userId();
        $data   = json_decode(file_get_contents('php://input'), true);

        if (empty($data['title'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quest title is required']);
            return;
        }

        $questId = $this->questModel->create($userId, $data);
        $quest   = $this->questModel->getById($questId, $userId);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Quest created!', 'quest' => $quest]);
    }

    public function update(int $id): void {
        $userId = AuthMiddleware::userId();
        $data   = json_decode(file_get_contents('php://input'), true);
        $result = $this->questModel->update($id, $userId, $data);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quest not found or no changes made']);
            return;
        }

        $quest = $this->questModel->getById($id, $userId);
        echo json_encode(['success' => true, 'message' => 'Quest updated!', 'quest' => $quest]);
    }

    public function complete(int $id): void {
        $userId  = AuthMiddleware::userId();
        $rewards = $this->questModel->complete($id, $userId);

        if (!$rewards) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Quest not found or already completed']);
            return;
        }

        // Award XP and points
        $xpResult = $this->userModel->addXP($userId, $rewards['xp_reward']);
        $this->userModel->addPoints($userId, $rewards['point_reward']);

        // Update streak
        $streakResult = $this->userModel->updateStreak($userId);

        // Get updated user for achievement checking
        $user = $this->userModel->findById($userId);
        $completedCount = $this->questModel->getCompletedCount($userId);

        // Check achievements
        $newAchievements = $this->achievementModel->checkAndUnlock($userId, [
            'quests_completed' => $completedCount,
            'level'            => $user['level'],
            'streak'           => $user['streak'],
            'total_points'     => $user['points'],
        ]);

        // Award achievement XP/points
        foreach ($newAchievements as $achievement) {
            if ($achievement['xp_reward'] > 0) $this->userModel->addXP($userId, $achievement['xp_reward']);
            if ($achievement['point_reward'] > 0) $this->userModel->addPoints($userId, $achievement['point_reward']);
        }

        $updatedUser = $this->userModel->findById($userId);

        echo json_encode([
            'success'          => true,
            'message'          => 'Quest completed! 🎉',
            'xp_reward'        => $rewards['xp_reward'],
            'point_reward'     => $rewards['point_reward'],
            'xp_result'        => $xpResult,
            'streak'           => $streakResult,
            'new_achievements' => $newAchievements,
            'user'             => $updatedUser,
        ]);
    }

    public function destroy(int $id): void {
        $userId = AuthMiddleware::userId();
        $result = $this->questModel->delete($id, $userId);
        if (!$result) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quest not found']);
            return;
        }
        echo json_encode(['success' => true, 'message' => 'Quest deleted']);
    }

    public function toggleSubtask(int $questId, int $subtaskId): void {
        $userId = AuthMiddleware::userId();
        // Verify quest belongs to user
        $quest = $this->questModel->getById($questId, $userId);
        if (!$quest) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quest not found']);
            return;
        }
        $this->questModel->toggleSubtask($subtaskId, $questId);
        echo json_encode(['success' => true, 'message' => 'Subtask updated']);
    }

    public function today(): void {
        $userId = AuthMiddleware::userId();
        $quests = $this->questModel->getTodayQuests($userId);
        try {
            $curriculumService = new CurriculumService();
            $todayStudy = $curriculumService->getTodayRecommendation($userId);
        } catch (Throwable $e) {
            $todayStudy = null;
        }
        echo json_encode(['success' => true, 'quests' => $quests, 'today_study' => $todayStudy]);
    }
}
