<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4", "root", "");

echo "=== question_topics ===\n";
$stmt = $pdo->query("DESCRIBE question_topics");
foreach ($stmt->fetchAll() as $r) {
    echo "  {$r['Field']} ({$r['Type']}) Key:{$r['Key']}\n";
}

echo "=== FOREIGN KEYS ON questions ===\n";
$fk = $pdo->query("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'studyquest' AND TABLE_NAME = 'questions' AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll();
foreach ($fk as $f) {
    echo "  {$f['CONSTRAINT_NAME']}: {$f['COLUMN_NAME']} -> {$f['REFERENCED_TABLE_NAME']}({$f['REFERENCED_COLUMN_NAME']})\n";
}
