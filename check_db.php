<?php
$dsn = "mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, "root", "", [PDO::ATTR_TIMEOUT => 3]);
    echo "Connected successfully to MySQL!\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables (" . count($tables) . "):\n" . implode(", ", $tables) . "\n";
} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage() . "\n";
}
