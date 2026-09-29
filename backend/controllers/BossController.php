<?php
require_once __DIR__ . '/../models/Boss.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class BossController {
    private Boss $bossModel;
    private User $userModel;
    private Achievement $achievementModel;

    public function __construct() {
        $this->bossModel        = new Boss();
        $this->userModel        = new User();
        $this->achievementModel = new Achievement();
    }

    public function index(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $bosses = $this->bossModel->getAll();
        $history = $this->bossModel->getUserBossHistory($userId);
        
        $defeatedBossIds = array_column(
            array_filter($history, fn($h) => $h['status'] === 'won'),
            'boss_id'
        );

        foreach ($bosses as &$boss) {
            $boss['user_defeated'] = in_array($boss['id'], $defeatedBossIds);
            $boss['points_reward'] = (int) ($boss['point_reward'] ?? 150);
            $boss['xp_reward'] = (int) ($boss['xp_reward'] ?? 300);
        }

        echo json_encode(['success' => true, 'bosses' => $bosses, 'data' => $bosses]);
    }

    public function show(int $id): void {
        AuthMiddleware::check();
        $boss = $this->bossModel->getById($id);
        if (!$boss) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Boss not found']);
            return;
        }

        $boss['points_reward'] = (int) ($boss['point_reward'] ?? 150);
        $boss['xp_reward'] = (int) ($boss['xp_reward'] ?? 300);

        $userId = AuthMiddleware::userId();
        $rawQuestions = $this->bossModel->getQuestions($id, 10, $userId);
        $questions = [];
        foreach ($rawQuestions as $q) {
            $questions[] = [
                'id' => $q['id'],
                'question_text' => $q['question'],
                'option_a' => $q['choice_a'],
                'option_b' => $q['choice_b'],
                'option_c' => $q['choice_c'],
                'option_d' => $q['choice_d'],
                'choice_a' => $q['choice_a'],
                'choice_b' => $q['choice_b'],
                'choice_c' => $q['choice_c'],
                'choice_d' => $q['choice_d'],
                'correct_answer' => strtoupper($q['correct_answer']),
                'subject' => $boss['subject'] ?? 'General Knowledge'
            ];
        }
        $boss['questions'] = $questions;

        echo json_encode(['success' => true, 'boss' => $boss, 'data' => $boss]);
    }

    public function recordVictory(int $bossId): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $boss = $this->bossModel->getById($bossId);
        if (!$boss) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Boss not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $playerHp = max(1, (int) ($input['player_hp'] ?? 100));

        // Record won attempt in database
        $stmt = Database::getInstance()->prepare("
            INSERT INTO boss_attempts (user_id, boss_id, boss_hp, player_hp, current_question_index, status, completed_at)
            VALUES (?, ?, 0, ?, 0, 'won', NOW())
        ");
        $stmt->execute([$userId, $bossId, $playerHp]);

        // Award rewards
        $xpReward = (int) ($boss['xp_reward'] ?? 300);
        $pointReward = (int) ($boss['point_reward'] ?? 150);

        $xpResult = $this->userModel->addXP($userId, $xpReward);
        $this->userModel->addPoints($userId, $pointReward);

        // Check & unlock achievements (e.g. Dragon Slayer / Boss Slayer)
        $bossWins = $this->bossModel->getDefeatedCount($userId);
        $user = $this->userModel->findById($userId);

        $newAchievements = $this->achievementModel->checkAndUnlock($userId, [
            'bosses_defeated' => $bossWins,
            'level'           => $user['level'],
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Victory! +{$xpReward} XP and +{$pointReward} Coins awarded!",
            'rewards' => [
                'xp'     => $xpReward,
                'points' => $pointReward,
                'coins'  => $pointReward,
            ],
            'xp_result'        => $xpResult,
            'new_achievements' => $newAchievements,
            'boss_wins'        => $bossWins,
            'user'             => $user,
        ]);
    }

    public function recordDefeat(int $bossId): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $boss = $this->bossModel->getById($bossId);
        if (!$boss) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Boss not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $bossHp = max(1, (int) ($input['boss_hp'] ?? 100));

        $stmt = Database::getInstance()->prepare("
            INSERT INTO boss_attempts (user_id, boss_id, boss_hp, player_hp, current_question_index, status, completed_at)
            VALUES (?, ?, ?, 0, 0, 'lost', NOW())
        ");
        $stmt->execute([$userId, $bossId, $bossHp]);

        echo json_encode([
            'success' => true,
            'message' => 'Battle recorded as defeat',
        ]);
    }

    public function start(int $bossId): void {
        $userId = AuthMiddleware::userId();
        $boss = $this->bossModel->getById($bossId);
        if (!$boss) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Boss not found']);
            return;
        }

        $attemptId = $this->bossModel->startAttempt($userId, $bossId);
        $attempt = $this->bossModel->getAttemptWithQuestion($attemptId, $userId);

        echo json_encode([
            'success' => true,
            'message' => "Battle started! Defeat {$boss['name']}!",
            'attempt' => $attempt,
            'boss' => $boss,
        ]);
    }

    public function submitAnswer(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        $data   = json_decode(file_get_contents('php://input'), true);
        $answer = strtolower(trim($data['answer'] ?? ''));

        if (!in_array($answer, ['a', 'b', 'c', 'd'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid answer. Use a, b, c, or d']);
            return;
        }

        $result = $this->bossModel->submitAnswer($attemptId, $userId, $answer);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode($result);
            return;
        }

        // Handle battle end
        if ($result['status'] === 'won') {
            // Get boss info for rewards
            $stmt = Database::getInstance()->prepare("SELECT ba.boss_id FROM boss_attempts ba WHERE ba.id = ?");
            $stmt->execute([$attemptId]);
            $bossId = (int) $stmt->fetchColumn();
            $boss = $this->bossModel->getById($bossId);

            $xpResult = $this->userModel->addXP($userId, $boss['xp_reward']);
            $this->userModel->addPoints($userId, $boss['point_reward']);

            $bossWins = $this->bossModel->getDefeatedCount($userId);
            $user = $this->userModel->findById($userId);

            $newAchievements = $this->achievementModel->checkAndUnlock($userId, [
                'bosses_defeated' => $bossWins,
                'level'           => $user['level'],
            ]);

            $result['boss_rewards'] = [
                'xp'     => $boss['xp_reward'],
                'points' => $boss['point_reward'],
            ];
            $result['xp_result']        = $xpResult;
            $result['new_achievements'] = $newAchievements;
            $result['user']             = $this->userModel->findById($userId);
        }

        echo json_encode($result);
    }

    public function getAttempt(int $attemptId): void {
        $userId  = AuthMiddleware::userId();
        $attempt = $this->bossModel->getAttemptWithQuestion($attemptId, $userId);
        if (!$attempt) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Attempt not found']);
            return;
        }
        echo json_encode(['success' => true, 'attempt' => $attempt]);
    }

    public function history(): void {
        $userId  = AuthMiddleware::userId();
        $history = $this->bossModel->getUserBossHistory($userId);
        echo json_encode(['success' => true, 'history' => $history]);
    }
}
