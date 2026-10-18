<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ChronicleController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /chronicle — public feed ─────────────────────────
    public function feed(): void {
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare("
            SELECT cp.id, cp.post_type, cp.title, cp.body, cp.metadata, cp.created_at,
                   u.nickname, u.level, u.avatar_style, u.avatar_seed, u.avatar_options,
                   u.selected_character_id, COALESCE(c.class_type, 'MAGE') AS character_class, COALESCE(c.key_name, 'mage') AS character_key
            FROM chronicle_posts cp
            JOIN users u ON u.id = cp.user_id
            LEFT JOIN characters c ON COALESCE(u.selected_character_id, 1) = c.id
            WHERE cp.is_public = 1
            ORDER BY cp.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            if (!empty($post['metadata']) && is_string($post['metadata'])) {
                $post['metadata'] = json_decode($post['metadata'], true);
            }
            if (!empty($post['avatar_options']) && is_string($post['avatar_options'])) {
                $post['avatar_options'] = json_decode($post['avatar_options'], true);
            } else {
                $post['avatar_options'] = $post['avatar_options'] ?? null;
            }
            // Only expose safe public info
            unset($post['email'], $post['password']);
        }

        echo json_encode(['success' => true, 'posts' => $posts, 'page' => $page]);
    }

    // ── POST /chronicle — create post ────────────────────────
    public function post(): void {
        $userId = AuthMiddleware::userId();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $postType  = $data['post_type'] ?? '';
        $title     = trim($data['title'] ?? '');
        $body      = trim($data['body'] ?? '');
        $metadata  = $data['metadata'] ?? null;
        $isPublic  = (bool)($data['is_public'] ?? true);

        $allowedTypes = ['achievement','adventure_victory','boss_victory','multiplayer_win','level_up','chapter_complete'];
        if (!in_array($postType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid post type']);
            return;
        }
        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO chronicle_posts (user_id, post_type, title, body, metadata, is_public)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $postType, $title, $body,
            $metadata ? json_encode($metadata) : null,
            $isPublic ? 1 : 0
        ]);

        $postId = (int)$this->db->lastInsertId();
        echo json_encode(['success' => true, 'post_id' => $postId, 'message' => 'Posted to Chronicle!']);
    }

    // ── DELETE /chronicle/{id} — delete own post ─────────────
    public function delete(int $postId): void {
        $userId = AuthMiddleware::userId();
        $stmt = $this->db->prepare("DELETE FROM chronicle_posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Post removed']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found or not yours']);
        }
    }
}
