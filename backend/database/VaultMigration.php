<?php
require_once __DIR__ . '/../config/database.php';

class VaultMigration {
    public static function run(): void {
        $db = Database::getInstance();

        // 1. Ensure vault_reviewers table exists
        $db->exec("
            CREATE TABLE IF NOT EXISTS vault_reviewers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_number INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                original_filename VARCHAR(255) NOT NULL,
                stored_filename VARCHAR(255) NOT NULL,
                file_type VARCHAR(20) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_size BIGINT NOT NULL,
                storage_path VARCHAR(255) NOT NULL,
                visibility ENUM('public', 'private') NOT NULL DEFAULT 'public',
                highlights LONGTEXT NULL,
                edited_text LONGTEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_user_book (user_id, book_number),
                INDEX idx_user_created (user_id, created_at),
                INDEX idx_title (title)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 1b. Ensure highlights and edited_text exist if table was already created
        try {
            $colsStmt = $db->query("SHOW COLUMNS FROM vault_reviewers LIKE 'highlights'");
            if (!$colsStmt->fetch()) {
                $db->exec("ALTER TABLE vault_reviewers ADD COLUMN highlights LONGTEXT NULL AFTER visibility");
            }
        } catch (Throwable $e) {}

        try {
            $colsStmt = $db->query("SHOW COLUMNS FROM vault_reviewers LIKE 'edited_text'");
            if (!$colsStmt->fetch()) {
                $db->exec("ALTER TABLE vault_reviewers ADD COLUMN edited_text LONGTEXT NULL AFTER highlights");
            }
        } catch (Throwable $e) {}

        // 2. Ensure storage directory exists and is secured
        $storageDir = __DIR__ . '/../storage/vault';
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $htaccessPath = $storageDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "# Deny direct access to uploaded manuscripts\nOrder Deny,Allow\nDeny from all\n<IfModule mod_php.c>\n    php_flag engine off\n</IfModule>\n<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n<IfModule mod_php8.c>\n    php_flag engine off\n</IfModule>\n");
        }
    }
}
