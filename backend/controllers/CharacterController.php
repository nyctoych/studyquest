<?php
require_once __DIR__ . '/../models/Character.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CharacterController {
    private Character $charModel;
    private User $userModel;

    public function __construct() {
        $this->charModel = new Character();
        $this->userModel = new User();
    }

    /**
     * GET /characters
     * Returns list of all available player characters with official attributes and lore
     */
    public function index(): void {
        $characters = $this->charModel->getAllActive();
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'characters' => $characters
        ]);
    }

    /**
     * GET /characters/{id}
     * Returns single character by id or key
     */
    public function show(string|int $id): void {
        header('Content-Type: application/json');
        if (is_numeric($id)) {
            $char = $this->charModel->findById((int)$id);
        } else {
            $char = $this->charModel->findByKey((string)$id);
        }

        if (!$char) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Character not found'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'character' => $char
        ]);
    }

    /**
     * GET /characters/current or /characters/me
     * Returns the selected character of currently logged-in user
     */
    public function current(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $char = $this->charModel->getUserSelectedCharacter($userId);
        $user = $this->userModel->findById($userId);
        $userLevel = (int)($user['level'] ?? 1);
        $scaledStats = $char ? Character::calculateScaledStats($char, $userLevel) : null;

        echo json_encode([
            'success' => true,
            'has_character' => !empty($char),
            'character' => $char,
            'level' => $userLevel,
            'scaled_stats' => $scaledStats,
            'attack_power' => $scaledStats['damage'] ?? 10,
            'critical_attack_power' => $scaledStats['crit_damage'] ?? 35,
            'scaled_max_health' => $scaledStats['max_health'] ?? 150,
            'adventure_start_hp' => $scaledStats['adventure_start_hp'] ?? 68,
        ]);
    }

    /**
     * POST /characters/select
     * Validates and sets the selected character for the user
     * Validates strictly on server side against official attributes in database
     */
    public function select(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $characterId = $input['character_id'] ?? null;
        $characterKey = $input['character_key'] ?? null;

        $targetChar = null;
        if (!empty($characterId) && is_numeric($characterId)) {
            $targetChar = $this->charModel->findById((int)$characterId);
        } elseif (!empty($characterKey) && is_string($characterKey)) {
            $targetChar = $this->charModel->findByKey($characterKey);
        }

        if (!$targetChar || $targetChar['status'] !== 'active') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid character selection. Please choose an active character.'
            ]);
            return;
        }

        $ok = $this->charModel->setUserSelectedCharacter($userId, (int)$targetChar['id']);
        if (!$ok) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save character selection. Please try again.'
            ]);
            return;
        }

        $updatedUser = $this->userModel->findById($userId);

        // Recalculate persistent Adventure starting HP for newly selected character
        $uLevel = (int)($updatedUser['level'] ?? 1);
        $scaledStats = Character::calculateScaledStats($targetChar, $uLevel);
        $nextAdventureStartHp = (int)round($scaledStats['max_health'] * 0.45);
        Database::getInstance()->prepare("UPDATE users SET adventure_current_hp = ? WHERE id = ?")->execute([$nextAdventureStartHp, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Character selection confirmed!',
            'character' => $targetChar,
            'user' => $updatedUser,
            'scaled_stats' => $scaledStats,
            'attack_power' => $scaledStats['damage'],
            'critical_attack_power' => $scaledStats['crit_damage'],
            'adventure_start_hp' => $nextAdventureStartHp,
        ]);
    }
}
