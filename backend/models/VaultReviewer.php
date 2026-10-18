<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/VaultMigration.php';

class VaultReviewer {
    private PDO $db;

    public function __construct() {
        VaultMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * Determines the next sequence book number for a user's vault.
     * Starts at 1 and increases monotonically.
     */
    public function getNextBookNumber(int $userId): int {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(book_number), 0) + 1 AS next_num
            FROM vault_reviewers
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() ?: 1;
    }

    /**
     * Inserts a new vault reviewer book.
     */
    public function create(array $data): ?array {
        $stmt = $this->db->prepare("
            INSERT INTO vault_reviewers (
                user_id, book_number, title, description,
                original_filename, stored_filename, file_type,
                mime_type, file_size, storage_path, visibility
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([
            $data['user_id'],
            $data['book_number'],
            $data['title'],
            $data['description'] ?? null,
            $data['original_filename'],
            $data['stored_filename'],
            $data['file_type'],
            $data['mime_type'],
            $data['file_size'],
            $data['storage_path'],
            $data['visibility'] ?? 'public'
        ]);

        if (!$success) {
            return null;
        }

        $newId = (int)$this->db->lastInsertId();
        return $this->findById($newId);
    }

    /**
     * Finds a reviewer record by primary key ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, book_number, title, description,
                   original_filename, stored_filename, file_type,
                   mime_type, file_size, storage_path, visibility,
                   highlights, edited_text,
                   created_at, updated_at
            FROM vault_reviewers
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Finds all reviewers belonging to a specific user with filtering and sorting.
     */
    public function findByUser(int $userId, ?string $fileType = null, ?string $search = null, string $sort = 'newest'): array {
        $sql = "
            SELECT id, user_id, book_number, title, description,
                   original_filename, stored_filename, file_type,
                   mime_type, file_size, storage_path, visibility,
                   highlights, edited_text,
                   created_at, updated_at
            FROM vault_reviewers
            WHERE user_id = ?
        ";
        $params = [$userId];

        if (!empty($fileType) && $fileType !== 'all') {
            // Group DOCS or PPT if requested
            if ($fileType === 'docs') {
                $sql .= " AND file_type IN ('doc', 'docx')";
            } elseif ($fileType === 'ppt') {
                $sql .= " AND file_type IN ('ppt', 'pptx')";
            } else {
                $sql .= " AND file_type = ?";
                $params[] = strtolower($fileType);
            }
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR description LIKE ? OR original_filename LIKE ?)";
            $wild = '%' . trim($search) . '%';
            $params[] = $wild;
            $params[] = $wild;
            $params[] = $wild;
        }

        switch ($sort) {
            case 'oldest':
                $sql .= " ORDER BY created_at ASC, id ASC";
                break;
            case 'book_number':
                $sql .= " ORDER BY book_number ASC";
                break;
            case 'title':
                $sql .= " ORDER BY title ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY created_at DESC, id DESC";
                break;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Gets public reviewers for a user's Mindkeeper profile in Hall of Records.
     */
    public function getPublicByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id, user_id, book_number, title, description,
                   original_filename, file_type, file_size, created_at
            FROM vault_reviewers
            WHERE user_id = ? AND visibility = 'public'
            ORDER BY book_number ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Updates reviewer metadata (title, description).
     * Strictly verifies user ownership.
     */
    public function updateMetadata(int $id, int $userId, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE vault_reviewers
            SET title = ?, description = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $id,
            $userId
        ]);
    }

    /**
     * Deletes a reviewer record with strict ownership check.
     * Returns deleted record so caller can unlink physical file.
     */
    public function delete(int $id, int $userId): ?array {
        $record = $this->findById($id);
        if (!$record || (int)$record['user_id'] !== $userId) {
            return null;
        }

        $stmt = $this->db->prepare("
            DELETE FROM vault_reviewers
            WHERE id = ? AND user_id = ?
        ");
        $success = $stmt->execute([$id, $userId]);

        return $success ? $record : null;
    }

    /**
     * Updates reviewer content and highlights. Strictly checks ownership.
     */
    public function saveContentAndHighlights(int $id, int $userId, ?string $editedText, ?string $highlights, ?int $newFileSize = null): bool {
        // Verify book existence and ownership
        $checkStmt = $this->db->prepare("SELECT user_id FROM vault_reviewers WHERE id = ?");
        $checkStmt->execute([$id]);
        $ownerId = $checkStmt->fetchColumn();
        if ($ownerId === false || (int)$ownerId !== $userId) {
            return false;
        }

        if ($newFileSize !== null) {
            $stmt = $this->db->prepare("
                UPDATE vault_reviewers
                SET edited_text = ?, highlights = ?, file_size = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$editedText, $highlights, $newFileSize, $id, $userId]);
        }

        $stmt = $this->db->prepare("
            UPDATE vault_reviewers
            SET edited_text = ?, highlights = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$editedText, $highlights, $id, $userId]);
    }
}

