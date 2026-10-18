<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Character.php';
require_once __DIR__ . '/../models/Achievement.php';
require_once __DIR__ . '/../models/VaultReviewer.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

class ProfileController {
    private User $userModel;
    private Character $charModel;
    private Achievement $achModel;
    private VaultReviewer $vaultModel;

    public function __construct() {
        $this->userModel = new User();
        $this->charModel = new Character();
        $this->achModel = new Achievement();
        $this->vaultModel = new VaultReviewer();
    }

    public function show(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $user   = $this->userModel->findById($userId);
        $stats  = $this->userModel->getStats($userId);
        echo json_encode(['success' => true, 'user' => $user, 'stats' => $stats]);
    }

    public function update(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $data   = json_decode(file_get_contents('php://input'), true);

        $result = $this->userModel->update($userId, $data);
        $user   = $this->userModel->findById($userId);

        echo json_encode(['success' => true, 'message' => 'Profile updated!', 'user' => $user]);
    }

    public function stats(): void {
        header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $stats  = $this->userModel->getStats($userId);
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    /**
     * GET /profile/mindkeeper/{id}
     * Returns public Mindkeeper Record profile for Hall of Records player inspection.
     * Contains only authoritative, public progress data. Never exposes credentials.
     */
    public function mindkeeper(int $targetUserId): void {
        header('Content-Type: application/json');
        $currentUserId = AuthMiddleware::userId();
        $db = Database::getInstance();

        // 1. Fetch user public credentials
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.nickname, u.level, u.xp, u.points,
                   u.streak, u.longest_streak, u.selected_character_id, u.created_at,
                   u.avatar_style, u.avatar_seed,
                   COALESCE(u.battle_rating, 1200) AS battle_rating,
                   COALESCE(u.wins, 0) AS wins,
                   COALESCE(u.losses, 0) AS losses,
                   COALESCE(u.draws, 0) AS draws,
                   COALESCE(u.total_battles, (COALESCE(u.wins, 0) + COALESCE(u.losses, 0))) AS total_battles
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Mindkeeper record not found.']);
            return;
        }

        // 2. Character
        $char = $this->charModel->getUserSelectedCharacter($targetUserId);
        if (!$char) {
            // Default to Necromancer (mage) if not explicitly chosen
            $char = $this->charModel->findByKey('mage');
        }

        // 3. Adventure progression
        $advStmt = $db->prepare("
            SELECT acp.domain_id, acp.status, acp.parts_completed,
                   pd.display_order AS chapter_number, pd.name AS chapter_name, pd.total_parts
            FROM adventure_chapter_progress acp
            JOIN psychology_domains pd ON pd.id = acp.domain_id
            WHERE acp.user_id = ?
            ORDER BY pd.display_order DESC
        ");
        $advStmt->execute([$targetUserId]);
        $advRows = $advStmt->fetchAll();

        $currentChapter = 1;
        $currentChapterName = 'Neurodevelopmental Disorders';
        $currentPart = 1;
        $totalParts = 10;
        $isChapterComplete = false;

        if (!empty($advRows)) {
            $foundActive = false;
            // Iterate in ascending order to find the highest non-locked chapter
            foreach (array_reverse($advRows) as $row) {
                if (in_array($row['status'], ['available', 'in_progress', 'boss_available'])) {
                    $currentChapter = (int)$row['chapter_number'];
                    $currentChapterName = $row['chapter_name'];
                    $currentPart = min((int)$row['total_parts'], (int)$row['parts_completed'] + 1);
                    $totalParts = (int)$row['total_parts'];
                    $isChapterComplete = false;
                    $foundActive = true;
                    break;
                }
            }
            if (!$foundActive) {
                $top = $advRows[0];
                $currentChapter = (int)$top['chapter_number'];
                $currentChapterName = $top['chapter_name'];
                $currentPart = (int)$top['total_parts'];
                $totalParts = (int)$top['total_parts'];
                $isChapterComplete = true;
            }
        }

        // 4. Unlocked achievements
        $allAch = $this->achModel->getUserAchievements($targetUserId);
        $unlockedAch = array_values(array_filter($allAch, fn($a) => !empty($a['unlocked_at'])));

        // 5. Vault reviewers
        $vaultBooks = $this->vaultModel->getPublicByUser($targetUserId);

        echo json_encode([
            'success' => true,
            'is_self' => ($currentUserId === $targetUserId),
            'profile' => [
                'id'            => (int)$user['id'],
                'username'      => $user['username'],
                'nickname'      => $user['nickname'] ?: $user['username'],
                'level'         => (int)($user['level'] ?: 1),
                'xp'            => (int)($user['xp'] ?: 0),
                'streak'        => (int)($user['streak'] ?: 0),
                'battle_rating' => (int)$user['battle_rating'],
                'wins'          => (int)$user['wins'],
                'losses'        => (int)$user['losses'],
                'draws'         => (int)$user['draws'],
                'total_battles' => (int)$user['total_battles'],
                'created_at'    => $user['created_at']
            ],
            'character' => $char ? [
                'id'          => $char['id'],
                'key_name'    => $char['key_name'],
                'name'        => $char['name'],
                'class_type'  => $char['class_type'],
                'title'       => $char['title'],
                'sprite_path' => $char['sprite_path'],
                'idle_asset'  => $char['idle_asset_path'] ?? null
            ] : null,
            'adventure' => [
                'chapter_number'     => $currentChapter,
                'chapter_name'       => $currentChapterName,
                'part_number'        => $currentPart,
                'total_parts'        => $totalParts,
                'is_chapter_complete'=> $isChapterComplete
            ],
            'achievements' => array_map(function($a) {
                return [
                    'id'          => (int)$a['id'],
                    'name'        => $a['name'],
                    'description' => $a['description'],
                    'icon'        => $a['icon'] ?? '🏆',
                    'category'    => $a['category'],
                    'rarity'      => $a['rarity'],
                    'unlocked_at' => $a['unlocked_at']
                ];
            }, $unlockedAch),
            'vault' => array_map(function($b) {
                return [
                    'id'                => (int)$b['id'],
                    'user_id'           => (int)$b['user_id'],
                    'book_number'       => (int)$b['book_number'],
                    'title'             => $b['title'],
                    'description'       => $b['description'],
                    'original_filename' => $b['original_filename'],
                    'file_type'         => $b['file_type'],
                    'file_size'         => (int)$b['file_size'],
                    'created_at'        => $b['created_at']
                ];
            }, $vaultBooks)
        ]);
    }
}
