<?php
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/Match.php';
require_once __DIR__ . '/../models/BattleRating.php';
require_once __DIR__ . '/../models/User.php';

class MultiplayerController {
    private function requireAuth(): array {
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            exit;
        }
        return ['user_id' => (int)$_SESSION['user_id']];
    }

    private function requireArenaAccess(): int {
        $auth = $this->requireAuth();
        $userId = $auth['user_id'];
        $userModel = new User();
        $user = $userModel->findById($userId);
        if (($user['level'] ?? 1) < 15) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error_code' => 'LEVEL_LOCKED',
                'message' => 'The Arena requires Level 15 to enter. Current level: ' . ($user['level'] ?? 1),
                'required_level' => 15,
                'current_level' => (int)($user['level'] ?? 1)
            ]);
            exit;
        }
        return $userId;
    }

    private function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data);
    }

    // GET /multiplayer/questions
    public function getQuestions(): void {
        $this->requireArenaAccess();
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $difficulty  = $_GET['difficulty'] ?? null;
        $count       = min((int)($_GET['count'] ?? 10), 20);

        $model     = new Question();
        $questions = $model->getForBattle($count, $categoryId, $difficulty);

        $this->json(['success' => true, 'questions' => $model->stripAnswers($questions), 'count' => count($questions)]);
    }

    // GET /multiplayer/questions/categories
    public function getCategories(): void {
        $this->requireArenaAccess();
        $model      = new Question();
        $categories = $model->getCategories();
        $this->json(['success' => true, 'categories' => $categories]);
    }

    // POST /multiplayer/match/save  (called by Node.js Socket.IO server with internal token)
    public function saveMatch(): void {
        // Validate internal token
        $internalKey = $this->getHeader('X-Internal-Token');
        $expectedKey = defined('SOCKET_INTERNAL_TOKEN') ? SOCKET_INTERNAL_TOKEN : 'studyquest_socket_secret_2024';
        if ($internalKey !== $expectedKey) {
            $this->json(['success' => false, 'message' => 'Forbidden'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        if (!$body) {
            $this->json(['success' => false, 'message' => 'Invalid payload'], 400);
            return;
        }

        $matchModel  = new BattleMatch();
        $ratingModel = new BattleRating();
        $userModel   = new User();
        $qModel      = new Question();

        // Create match record
        $matchId = $matchModel->create($body['room_id'], $body['battle_mode'] ?? 'ranked', count($body['question_ids'] ?? []));

        // Add question records
        foreach ($body['question_ids'] ?? [] as $order => $questionId) {
            $matchModel->addQuestion($matchId, $questionId, $order);
        }

        $matchModel->start($matchId);

        // Process each player's result
        $players = $body['players'] ?? [];
        foreach ($players as $playerData) {
            $userId      = (int)$playerData['user_id'];
            $outcome     = $playerData['outcome']; // won, lost, draw, disconnect
            $ratingBefore = $ratingModel->getByUserId($userId);

            // ELO is computed per pair — we trust the Node server's pre-calculated values
            $ratingAfter  = (int)($playerData['rating_after'] ?? $ratingBefore);
            $ratingChange = $ratingAfter - $ratingBefore;
            $xpEarned     = (int)($playerData['xp_earned'] ?? 0);
            $coinsEarned  = (int)($playerData['coins_earned'] ?? 0);

            // Add player to match
            $matchModel->addPlayer($matchId, $userId, $playerData['slot'], $ratingBefore);

            // Update player result
            $matchModel->updatePlayerResult($matchId, $userId, [
                'hp_end'          => (int)($playerData['hp_end'] ?? 0),
                'correct_answers' => (int)($playerData['correct_answers'] ?? 0),
                'wrong_answers'   => (int)($playerData['wrong_answers'] ?? 0),
                'outcome'         => $outcome,
                'xp_earned'       => $xpEarned,
                'coins_earned'    => $coinsEarned,
                'rating_after'    => $ratingAfter,
                'rating_change'   => $ratingChange,
            ]);

            // Update user rating & stats
            $ratingModel->updateRating($userId, $ratingAfter);
            $ratingModel->updateBattleStats($userId, $outcome);
            $ratingModel->recordHistory($userId, $matchId, $ratingBefore, $ratingAfter, $outcome);

            // Grant XP
            if ($xpEarned > 0) {
                $userModel->addXP($userId, $xpEarned);
            }

            // Update coins (credit wager winnings or deduct wager loss)
            if ($coinsEarned !== 0) {
                $this->updateCoins($userId, $coinsEarned, $coinsEarned > 0 ? 'battle_wager_win' : 'battle_wager_loss', $matchId);
            }
        }

        $matchModel->complete($matchId);
        $this->json(['success' => true, 'match_id' => $matchId]);
    }

    // GET /multiplayer/history
    public function getHistory(): void {
        $userId  = $this->requireArenaAccess();
        $model   = new BattleMatch();
        $history = $model->getHistory($userId);
        $this->json(['success' => true, 'history' => $history]);
    }

    // GET /multiplayer/stats
    public function getStats(): void {
        $userId      = $this->requireArenaAccess();
        $matchModel  = new BattleMatch();
        $ratingModel = new BattleRating();
        $stats       = $matchModel->getStats($userId);
        $rating      = $ratingModel->getByUserId($userId);
        $this->json(['success' => true, 'stats' => array_merge($stats, ['battle_rating' => $rating])]);
    }

    // GET /multiplayer/leaderboard?type=rating|level|weekly
    public function getLeaderboard(): void {
        $type        = $_GET['type'] ?? 'rating';
        $ratingModel = new BattleRating();
        $rows        = $ratingModel->getLeaderboard($type, 50);
        $this->json(['success' => true, 'leaderboard' => $rows, 'type' => $type]);
    }

    // GET /multiplayer/my-rank
    public function getMyRank(): void {
        $auth = $this->requireAuth();
        $db   = \Database::getInstance();

        $stmt = $db->prepare(
            "SELECT COUNT(*) + 1 as rank FROM users WHERE battle_rating > (SELECT battle_rating FROM users WHERE id = ?) AND total_battles > 0"
        );
        $stmt->execute([$auth['user_id']]);
        $rank = $stmt->fetchColumn();

        $this->json(['success' => true, 'rank' => (int)$rank]);
    }

    // GET /multiplayer/combat-profile?user_id=123
    public function getCombatProfile(): void {
        $internalKey = $this->getHeader('X-Internal-Token');
        $expectedKey = defined('SOCKET_INTERNAL_TOKEN') ? SOCKET_INTERNAL_TOKEN : 'studyquest_socket_secret_2024';

        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($userId <= 0 || $internalKey !== $expectedKey) {
            $auth = $this->requireAuth();
            if ($userId <= 0) {
                $userId = $auth['user_id'];
            }
        }

        require_once __DIR__ . '/../models/Character.php';
        require_once __DIR__ . '/../models/Shop.php';
        $charModel = new Character();
        $shopModel = new ShopItem();

        $stats = $charModel->getScaledStatsForUser($userId);
        $char = $charModel->getUserSelectedCharacter($userId) ?: $charModel->findByKey('mage');
        $equipped = $shopModel->getEquipped($userId);

        $this->json([
            'success' => true,
            'user_id' => $userId,
            'stats' => $stats,
            'character' => $char,
            'equipped' => $equipped
        ]);
    }

    private function getHeader(string $name): string {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (!empty($_SERVER[$serverKey])) {
            return (string)$_SERVER[$serverKey];
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $k => $v) {
                    if (strcasecmp($k, $name) === 0) {
                        return (string)$v;
                    }
                }
            }
        }
        return '';
    }

    private function updateCoins(int $userId, int $amount, string $source, ?int $refId = null): void {
        if ($amount === 0) return;
        $db = \Database::getInstance();
        $db->prepare("UPDATE users SET coins = GREATEST(0, coins + ?) WHERE id = ?")->execute([$amount, $userId]);
        $balStmt = $db->prepare("SELECT coins FROM users WHERE id = ?");
        $balStmt->execute([$userId]);
        $row = $balStmt->fetch();
        $newBalance = $row ? (int)$row['coins'] : 0;
        $type = $amount > 0 ? 'earn' : 'spend';
        $desc = $amount > 0 ? "Ranked Arena wager win (+{$amount} coins)" : "Ranked Arena wager loss (" . abs($amount) . " coins)";
        $db->prepare(
            "INSERT INTO coin_transactions (user_id, amount, type, source, reference_id, description, balance_after)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$userId, abs($amount), $type, $source, $refId, $desc, $newBalance]);
    }
}
