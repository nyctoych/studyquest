<?php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=studyquest;charset=utf8mb4", "root", "");

echo "=== DOMAINS (" . $pdo->query("SELECT COUNT(*) FROM psychology_domains")->fetchColumn() . ") ===\n";
foreach ($pdo->query("SELECT id, display_order, name, icon FROM psychology_domains ORDER BY display_order") as $r) {
    echo "  [{$r['id']}] #{$r['display_order']} {$r['icon']} {$r['name']}\n";
}

echo "\n=== BOSSES (" . $pdo->query("SELECT COUNT(*) FROM bosses")->fetchColumn() . ") ===\n";
foreach ($pdo->query("SELECT id, name, chapter_id, boss_order FROM bosses ORDER BY id") as $r) {
    echo "  Boss {$r['id']}: '{$r['name']}' -> Chapter {$r['chapter_id']} (order {$r['boss_order']})\n";
}
