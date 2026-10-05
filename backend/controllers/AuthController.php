<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $nickname = trim($data['nickname'] ?? $username);

        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters']);
            return;
        }

        // Nickname validation
        if (empty($nickname)) $nickname = $username;
        if (strlen($nickname) < 3 || strlen($nickname) > 20) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nickname must be 3-20 characters']);
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $nickname)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nickname may only contain letters, numbers, and underscores']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            return;
        }

        if (strlen($password) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            return;
        }

        if ($this->userModel->findByEmail($email)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already registered']);
            return;
        }

        if ($this->userModel->findByUsername($username)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username already taken']);
            return;
        }

        if ($this->userModel->findByNickname($nickname)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Nickname already taken']);
            return;
        }

        $userId = $this->userModel->create($username, $email, $password, $nickname);
        $user = $this->userModel->findById($userId);

        $_SESSION['user_id'] = $userId;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Account created successfully!',
            'user'    => $user,
        ]);
    }

    public function login(): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $identifier = trim($data['email'] ?? $data['username'] ?? '');
        $password   = $data['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email/username and password are required']);
            return;
        }

        // Try email first, then username
        $user = $this->userModel->findByEmail($identifier) 
             ?? $this->userModel->findByUsername($identifier);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }

        $_SESSION['user_id'] = $user['id'];

        // Remove sensitive data
        unset($user['password']);
        if (!empty($user['avatar_options']) && is_string($user['avatar_options'])) {
            $user['avatar_options'] = json_decode($user['avatar_options'], true);
        } else {
            $user['avatar_options'] = $user['avatar_options'] ?? null;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Welcome back, ' . $user['username'] . '!',
            'user' => $user,
        ]);
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function me(): void {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }

        $stats = $this->userModel->getStats($_SESSION['user_id']);

        // Load authoritative Adventure health state
        require_once __DIR__ . '/../services/AdventureHealthService.php';
        $healthState = AdventureHealthService::getInstance()->syncAndGetHealth((int)$_SESSION['user_id']);
        $user['adventure_current_hp'] = $healthState['current_hp'];
        $user['adventure_max_hp'] = $healthState['max_hp'];
        $user['adventure_health'] = $healthState;

        echo json_encode(['success' => true, 'user' => $user, 'stats' => $stats, 'health' => $healthState]);
    }
}
