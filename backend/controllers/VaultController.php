<?php
require_once __DIR__ . '/../models/VaultReviewer.php';
require_once __DIR__ . '/../services/DocumentParserService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class VaultController {
    private VaultReviewer $vaultModel;
    private array $config;

    public function __construct() {
        $this->vaultModel = new VaultReviewer();
        $this->config = require __DIR__ . '/../config/vault.php';
    }

    /**
     * GET /vault
     * Returns all books in current user's personal vault with optional search/filter.
     */
    public function index(): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();

        $type   = $_GET['type'] ?? null;
        $search = $_GET['search'] ?? null;
        $sort   = $_GET['sort'] ?? 'newest';

        $books = $this->vaultModel->findByUser($userId, $type, $search, $sort);

        echo json_encode([
            'success'       => true,
            'books'         => $books,
            'total_count'   => count($books),
            'max_file_size' => $this->config['max_file_size'],
            'max_file_label'=> $this->config['max_file_size_label']
        ]);
    }

    /**
     * POST /vault/upload
     * Uploads a new manuscript reviewer, validates server-side, and generates next book number.
     */
    public function upload(): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();

        if (empty($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No manuscript was selected for upload.']);
            return;
        }

        $uploaded = $_FILES['file'];
        $valResult = DocumentParserService::validateUpload($uploaded, $this->config);
        if (!$valResult['valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $valResult['error']]);
            return;
        }

        $ext = $valResult['file_type'];
        $originalFilename = $valResult['original_name'];
        $customTitle = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // If no custom title provided, derive clean title from original filename
        $title = ($customTitle !== '')
            ? $customTitle
            : DocumentParserService::cleanTitleFromFilename($originalFilename);

        // Generate safe, collision-resistant storage filename
        $storageDir = $this->config['storage_path'];
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $uniqueToken = bin2hex(random_bytes(8));
        $storedFilename = sprintf('vault_u%d_%s_%d.%s', $userId, $uniqueToken, time(), $ext);
        $destinationPath = $storageDir . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file($uploaded['tmp_name'], $destinationPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'The reviewer manuscript could not be stored on the server.']);
            return;
        }

        // DB record creation
        try {
            $bookNumber = $this->vaultModel->getNextBookNumber($userId);
            $newBook = $this->vaultModel->create([
                'user_id'          => $userId,
                'book_number'      => $bookNumber,
                'title'            => $title,
                'description'      => $description ?: null,
                'original_filename'=> $originalFilename,
                'stored_filename'  => $storedFilename,
                'file_type'        => $ext,
                'mime_type'        => $valResult['mime_type'],
                'file_size'        => $valResult['file_size'],
                'storage_path'     => $destinationPath,
                'visibility'       => 'public'
            ]);

            if (!$newBook) {
                @unlink($destinationPath);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to seal manuscript record into database.']);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => "Book " . sprintf('%02d', $bookNumber) . " added to Vault.",
                'book'    => $newBook
            ]);
        } catch (Throwable $e) {
            @unlink($destinationPath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error sealing book: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /vault/{id}
     * Retrieves reviewer metadata.
     */
    public function show(int $id): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $book = $this->vaultModel->findById($id);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Manuscript not found.']);
            return;
        }

        $isOwner = ((int)$book['user_id'] === $userId);
        if (!$isOwner && $book['visibility'] !== 'public') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied to this personal manuscript.']);
            return;
        }

        echo json_encode([
            'success'  => true,
            'book'     => $book,
            'is_owner' => $isOwner
        ]);
    }

    /**
     * PUT /vault/{id}
     * Edits reviewer title and description. Owner only.
     */
    public function update(int $id): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reviewer title cannot be empty.']);
            return;
        }

        $updated = $this->vaultModel->updateMetadata($id, $userId, [
            'title'       => $title,
            'description' => $description ?: null
        ]);

        if (!$updated) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unable to update reviewer metadata or not authorized.']);
            return;
        }

        $refreshed = $this->vaultModel->findById($id);
        echo json_encode([
            'success' => true,
            'message' => 'Reviewer metadata updated successfully.',
            'book'    => $refreshed
        ]);
    }

    /**
     * DELETE /vault/{id}
     * Deletes reviewer record and physical file. Owner only.
     */
    public function destroy(int $id): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();

        $deleted = $this->vaultModel->delete($id, $userId);
        if (!$deleted) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unable to delete manuscript: not found or unauthorized.']);
            return;
        }

        // Delete physical file
        $filePath = $deleted['storage_path'] ?? '';
        if ($filePath && file_exists($filePath)) {
            @unlink($filePath);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Reviewer removed from your Vault.',
            'id'      => $id
        ]);
    }

    /**
     * GET /vault/{id}/preview or /vault/view/{id}
     * Securely streams PDF or raw file for in-browser modal viewing.
     */
    public function preview(int $id): void {
        $userId = AuthMiddleware::userId();
        $book = $this->vaultModel->findById($id);

        if (!$book) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Manuscript not found.']);
            return;
        }

        $isOwner = ((int)$book['user_id'] === $userId);
        if (!$isOwner && $book['visibility'] !== 'public') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            return;
        }

        $filePath = $book['storage_path'];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Physical manuscript file is missing.']);
            return;
        }

        // Clean output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        $mime = $book['mime_type'] ?: 'application/octet-stream';
        $filename = basename($book['original_filename']);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=3600');

        readfile($filePath);
        exit;
    }

    /**
     * GET /vault/{id}/content
     * Returns parsed document structure (for DOCX, PPTX, TXT modal rendering).
     */
    public function content(int $id): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $book = $this->vaultModel->findById($id);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Manuscript not found.']);
            return;
        }

        $isOwner = ((int)$book['user_id'] === $userId);
        if (!$isOwner && $book['visibility'] !== 'public') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            return;
        }

        $filePath = $book['storage_path'];
        $fileType = $book['file_type'];

        $parsed = DocumentParserService::parseDocument($filePath, $fileType);

        echo json_encode([
            'success'  => true,
            'book'     => [
                'id'          => $book['id'],
                'book_number' => $book['book_number'],
                'title'       => $book['title'],
                'file_type'   => $book['file_type'],
                'file_size'   => $book['file_size'],
                'created_at'  => $book['created_at'],
                'updated_at'  => $book['updated_at'],
                'highlights'  => !empty($book['highlights']) ? json_decode($book['highlights'], true) : [],
                'edited_text' => $book['edited_text'] ?? null,
                'is_owner'    => $isOwner
            ],
            'document'   => $parsed,
            'highlights' => !empty($book['highlights']) ? json_decode($book['highlights'], true) : [],
            'edited_text'=> $book['edited_text'] ?? null
        ]);
    }

    /**
     * PUT or POST /vault/{id}/content
     * Persistently saves user reviewer edits and text highlights. Strictly owner only.
     */
    public function saveContent(int $id): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $userId = AuthMiddleware::userId();
        $book = $this->vaultModel->findById($id);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Reviewer manuscript not found.']);
            return;
        }

        $isOwner = ((int)$book['user_id'] === $userId);
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Only the manuscript owner may save edits or highlights.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $content = isset($input['content']) ? (string)$input['content'] : null;
        $highlightsRaw = $input['highlights'] ?? null;
        $highlightsJson = null;

        if ($highlightsRaw !== null) {
            if (is_array($highlightsRaw)) {
                $highlightsJson = json_encode($highlightsRaw, JSON_UNESCAPED_UNICODE);
            } elseif (is_string($highlightsRaw)) {
                $highlightsJson = $highlightsRaw;
            }
        }

        $filePath = $book['storage_path'];
        $fileType = strtolower($book['file_type']);
        $newFileSize = null;

        // If file is TXT and content is provided, write content directly to storage file
        if ($fileType === 'txt' && $content !== null) {
            if (!file_exists($filePath) || !is_writable(dirname($filePath))) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Reviewer storage file is not writable on the server.']);
                return;
            }

            // Ensure backup or preserve recoverable original
            $backupPath = $filePath . '.bak';
            if (!file_exists($backupPath)) {
                @copy($filePath, $backupPath);
            }

            $written = @file_put_contents($filePath, $content);
            if ($written === false) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to write updated manuscript content to disk.']);
                return;
            }
            clearstatcache(true, $filePath);
            $newFileSize = filesize($filePath);
        }

        $updated = $this->vaultModel->saveContentAndHighlights($id, $userId, $content, $highlightsJson, $newFileSize);
        if (!$updated) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to persist reviewer revisions into database.']);
            return;
        }

        $refreshed = $this->vaultModel->findById($id);
        $reparsed = DocumentParserService::parseDocument($filePath, $fileType);

        echo json_encode([
            'success'     => true,
            'message'     => 'Reviewer revisions and highlights sealed into the Vault.',
            'book'        => $refreshed,
            'document'    => $reparsed,
            'highlights'  => !empty($refreshed['highlights']) ? json_decode($refreshed['highlights'], true) : [],
            'edited_text' => $refreshed['edited_text'] ?? null
        ]);
    }

    /**
     * GET /vault/{id}/download
     * Securely streams manuscript download with safe headers.
     */
    public function download(int $id): void {
        $userId = AuthMiddleware::userId();
        $book = $this->vaultModel->findById($id);

        if (!$book) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Manuscript not found.']);
            return;
        }

        $isOwner = ((int)$book['user_id'] === $userId);
        if (!$isOwner && $book['visibility'] !== 'public') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            return;
        }

        $filePath = $book['storage_path'];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Physical manuscript file is missing.']);
            return;
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        $filename = basename($book['original_filename']);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($filePath);
        exit;
    }

    /**
     * GET /vault/user/{userId}
     * Returns public vault books for a user profile in Hall of Records.
     */
    public function userVault(int $targetUserId): void {
        if (!headers_sent()) header('Content-Type: application/json');
        $books = $this->vaultModel->getPublicByUser($targetUserId);

        echo json_encode([
            'success' => true,
            'books'   => $books,
            'total'   => count($books)
        ]);
    }
}
