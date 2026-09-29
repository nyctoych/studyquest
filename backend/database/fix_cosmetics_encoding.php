<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// Set UTF-8 encoding explicitly
$db->exec("SET NAMES utf8mb4");

// 1. Clean item_categories
$db->exec("DELETE FROM item_categories");
$stmt = $db->prepare("INSERT INTO item_categories (id, name, slug, icon) VALUES (?, ?, ?, ?)");
$categories = [
    [1, 'Skins', 'skins', '🎨'],
    [2, 'Hair', 'hair', '💇'],
    [3, 'Accessories', 'accessories', '👑'],
    [4, 'Backgrounds', 'backgrounds', '🌅'],
    [5, 'Effects', 'effects', '✨'],
    [6, 'Outfits', 'outfits', '👕'],
];
foreach ($categories as $cat) {
    $stmt->execute($cat);
}

// 2. Clean items
$db->exec("DELETE FROM items");
$stmtItem = $db->prepare("INSERT INTO items (id, category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$items = [
    // Skins (category 1)
    [1, 1, 'Crimson Scholar', 'Fierce red skin for the competitive psychology student', '🔴', 0, 'common', 'skinColor', 'f2d3b1'],
    [2, 1, 'Midnight Sage', 'Deep purple skin of a true psychology master', '🟣', 200, 'rare', 'skinColor', '9c6e3b'],
    [3, 1, 'Emerald Intellect', 'Vibrant scholar skin glowing with knowledge', '💚', 300, 'epic', 'skinColor', 'ae5d29'],

    // Hair (category 2)
    [4, 2, 'Classic Waves', 'Flowing wavy hair with academic flair', '🌊', 0, 'common', 'hair', 'shortCurls'],
    [5, 2, 'Scholar Bun', 'Professional top knot for late-night review sessions', '🎓', 150, 'common', 'hair', 'bun'],
    [6, 2, 'Wild Quiff', 'Bold spiky style for high-energy test takers', '⚡', 250, 'rare', 'hair', 'fonze'],
    [7, 2, 'Long Flowing', 'Elegant long hair with subtle highlights', '✨', 400, 'epic', 'hair', 'longButNotTooLong'],

    // Accessories (category 3)
    [8, 3, 'Reading Glasses', 'Classic scholar round glasses for studying DSM-5', '👓', 0, 'common', 'accessories', 'roundGlasses'],
    [9, 3, 'Graduation Cap', 'Wear your psychology board exam milestone with pride', '🎓', 500, 'rare', 'accessories', 'studentCap'],
    [10, 3, 'Gaming Headset', 'Show your competitive battle spirit in 1v1 duels', '🎧', 750, 'epic', 'accessories', 'headphones'],
    [11, 3, 'Crown of Wisdom', 'Golden royal crown reserved for psychology champions', '👑', 1500, 'legendary', 'accessories', 'crown'],

    // Backgrounds (category 4)
    [12, 4, 'Library Aura', 'Scholarly library backdrop with antique books and scrolls', '📚', 0, 'common', 'backgroundColor', 'b6e3f4'],
    [13, 4, 'Cosmic Mind', 'Deep space cosmos symbolizing subconscious depth', '🌌', 600, 'rare', 'backgroundColor', '1a1a2e'],
    [14, 4, 'Golden Hour', 'Warm sunset glow illuminating your academic achievements', '🌅', 800, 'epic', 'backgroundColor', 'ffd700'],
];

foreach ($items as $item) {
    $stmtItem->execute($item);
}

echo "Successfully reseeded items and item_categories with clean UTF-8 data!\n";
