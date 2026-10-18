<?php
/**
 * One-time CLI cleanup: removes the schema added by the (now removed) Google OAuth integration.
 *
 *   php backend/database/RemoveOAuthCleanup.php
 *
 * Safety rules:
 *  - Never touches `users` rows, password hashes, or any gameplay data.
 *  - Refuses to drop user_oauth_providers if it still contains link records.
 *  - Restores users.password to NOT NULL only if no user has an empty/NULL password.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();
$tableExists = fn(string $t): bool => (bool)$db->query("SHOW TABLES LIKE " . $db->quote($t))->fetch();

if ($tableExists('user_oauth_providers')) {
    $links = (int)$db->query("SELECT COUNT(*) FROM user_oauth_providers")->fetchColumn();
    if ($links > 0) {
        fwrite(STDERR, "ABORT: user_oauth_providers still has $links link record(s). Review them first.\n");
        exit(1);
    }
}

foreach (['oauth_transactions', 'user_oauth_providers'] as $t) {
    if ($tableExists($t)) { $db->exec("DROP TABLE `$t`"); echo "Dropped $t\n"; }
    else { echo "$t not present\n"; }
}

$col = $db->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch(PDO::FETCH_ASSOC);
if ($col && strtolower($col['Null'] ?? '') === 'yes') {
    $bad = (int)$db->query("SELECT COUNT(*) FROM users WHERE password IS NULL OR password = ''")->fetchColumn();
    if ($bad === 0) {
        $db->exec("ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL");
        echo "Restored users.password to NOT NULL\n";
    } else {
        echo "Left users.password nullable: $bad account(s) have no password and need a verified reset.\n";
    }
}
echo "Done.\n";
