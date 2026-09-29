<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4", "root", "");
$fk = $pdo->query("
    SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'studyquest' AND REFERENCED_TABLE_NAME = 'question_topics'
")->fetchAll();
foreach ($fk as $f) {
    echo "  {$f['TABLE_NAME']}.{$f['COLUMN_NAME']} ({$f['CONSTRAINT_NAME']})\n";
}
