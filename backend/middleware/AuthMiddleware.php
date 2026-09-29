<?php
class AuthMiddleware {
    public static function check(): array {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
            exit;
        }
        return ['user_id' => $_SESSION['user_id']];
    }

    public static function userId(): int {
        self::check();
        return (int) $_SESSION['user_id'];
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
}
