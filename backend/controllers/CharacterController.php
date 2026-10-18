<?php
require_once __DIR__ . '/../models/Character.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AttributeAllocation.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CharacterController {
    private Character $charModel;
    private User $userModel;
    private AttributeAllocation $allocModel;

    public function __construct() {
        $this->charModel = new Character();
        $this->userModel = new User();
        $this->allocModel = new AttributeAllocation();
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
     * Returns the selected character of currently logged-in user with allocated attribute bonuses
     */
    public function current(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $char = $this->charModel->getUserSelectedCharacter($userId);
        $user = $this->userModel->findById($userId);
        $userLevel = (int)($user['level'] ?? 1);
        $charId = (int)($char['id'] ?? 1);

        $allocTotals = $this->allocModel->getAllocationTotals($userId, $charId);
        $pendingMilestones = $this->allocModel->getPendingMilestones($userId, $userLevel, $charId);
        $scaledStats = $char ? Character::calculateScaledStats($char, $userLevel, $allocTotals) : null;

        echo json_encode([
            'success'                   => true,
            'has_character'             => !empty($char),
            'character'                 => $char,
            'level'                     => $userLevel,
            'scaled_stats'              => $scaledStats,
            'attack_power'              => $scaledStats['damage'] ?? 10,
            'critical_attack_power'     => $scaledStats['crit_damage'] ?? 35,
            'scaled_max_health'         => $scaledStats['max_health'] ?? 150,
            'adventure_start_hp'        => $scaledStats['adventure_start_hp'] ?? 68,
            'allocations'               => $allocTotals['allocations'] ?? [],
            'allocation_totals'         => $allocTotals,
            'completed_allocations_count'=> $allocTotals['count'] ?? 0,
            'total_milestones'          => count(AttributeAllocation::MILESTONES),
            'pending_milestones'        => $pendingMilestones,
            'has_pending_allocations'   => !empty($pendingMilestones),
            'next_pending_milestone'    => !empty($pendingMilestones) ? $pendingMilestones[0] : null,
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
        $allocTotals = $this->allocModel->getAllocationTotals($userId, (int)$targetChar['id']);
        $scaledStats = Character::calculateScaledStats($targetChar, $uLevel, $allocTotals);
        $nextAdventureStartHp = (int)$scaledStats['max_health'];
        Database::getInstance()->prepare("
            UPDATE users 
            SET adventure_current_hp = ?,
                adventure_hp_persisted = 1,
                health_regen_last_at = NOW()
            WHERE id = ?
        ")->execute([$nextAdventureStartHp, $userId]);

        echo json_encode([
            'success'               => true,
            'message'               => 'Character selection confirmed!',
            'character'             => $targetChar,
            'user'                  => $updatedUser,
            'scaled_stats'          => $scaledStats,
            'attack_power'          => $scaledStats['damage'],
            'critical_attack_power' => $scaledStats['crit_damage'],
            'adventure_start_hp'    => $nextAdventureStartHp,
            'allocation_totals'     => $allocTotals,
            'pending_milestones'    => $this->allocModel->getPendingMilestones($userId, $uLevel, (int)$targetChar['id']),
        ]);
    }

    /**
     * GET /characters/attribute-allocations
     * Returns full milestone allocation breakdown, totals, and pending milestones for the current user
     */
    public function getAttributeAllocations(): void {
        header('Content-Type: application/json');
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();

        $user = $this->userModel->findById($userId);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        $charId = isset($_GET['character_id']) && is_numeric($_GET['character_id'])
            ? (int)$_GET['character_id']
            : (int)($user['selected_character_id'] ?? 1);

        $char = $this->charModel->findById($charId) ?: $this->charModel->findByKey('mage');
        $userLevel = (int)($user['level'] ?? 1);

        $allocTotals = $this->allocModel->getAllocationTotals($userId, $charId);
        $pendingMilestones = $this->allocModel->getPendingMilestones($userId, $userLevel, $charId);
        $scaledStats = Character::calculateScaledStats($char, $userLevel, $allocTotals);

        echo json_encode([
            'success'                    => true,
            'user_id'                    => $userId,
            'user_level'                 => $userLevel,
            'character'                  => $char,
            'character_id'               => $charId,
            'milestones'                 => AttributeAllocation::MILESTONES,
            'allocations'                => $allocTotals['allocations'] ?? [],
            'allocation_totals'          => $allocTotals,
            'completed_count'            => $allocTotals['completed_count'] ?? 0,
            'total_max'                  => count(AttributeAllocation::MILESTONES),
            'pending_milestones'         => $pendingMilestones,
            'has_pending'                => !empty($pendingMilestones),
            'next_pending_milestone'     => !empty($pendingMilestones) ? $pendingMilestones[0] : null,
            'scaled_stats'               => $scaledStats,
            'attack_power'               => $scaledStats['damage'],
            'critical_attack_power'      => $scaledStats['crit_damage'],
            'max_health'                 => $scaledStats['max_health'],
            'attribute_descriptions'     => AttributeAllocation::ATTRIBUTE_DESCRIPTIONS,
            'bonus_values'               => AttributeAllocation::BONUSES,
        ]);
    }

    /**
     * POST /characters/attribute-allocations/claim
     * Claims a permanent attribute bonus for an eligible milestone level
     */
    public function claimAttributeAllocation(): void {
        header('Content-Type: application/json');
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $milestoneLevel = isset($input['milestone_level']) ? (int)$input['milestone_level'] : 0;
        $attributeType = isset($input['attribute_type']) ? trim((string)$input['attribute_type']) : '';
        $charId = isset($input['character_id']) && is_numeric($input['character_id']) ? (int)$input['character_id'] : null;

        if ($milestoneLevel <= 0 || empty($attributeType)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Missing milestone_level or attribute_type.'
            ]);
            return;
        }

        $result = $this->allocModel->claimMilestone($userId, $milestoneLevel, $attributeType, $charId);

        if (!$result['success']) {
            http_response_code(400);
            echo json_encode($result);
            return;
        }

        echo json_encode($result);
    }
}
