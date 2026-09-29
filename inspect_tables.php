<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4", "root", "");

function showCols($pdo, $table) {
    echo "=== $table ===\n";
    $stmt = $pdo->query("DESCRIBE $table");
    foreach ($stmt->fetchAll() as $row) {
        echo "  {$row['Field']} ({$row['Type']}) " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " Key:{$row['Key']} Def:{$row['Default']}\n";
    }
}

showCols($pdo, 'psychology_domains');
showCols($pdo, 'psychology_parts');
showCols($pdo, 'questions');
showCols($pdo, 'learning_questions');
showCols($pdo, 'question_attempts');
