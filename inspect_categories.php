<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4", "root", "");
foreach ($pdo->query("SELECT id, name, slug FROM question_categories ORDER BY id") as $r) {
    echo "  Cat {$r['id']}: {$r['name']} ({$r['slug']})\n";
}
