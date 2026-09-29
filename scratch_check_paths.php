<?php
$f1 = 'C:/xampp/htdocs/assets/adventure/battlefields/chapter-12-creator-domain.jpg';
$f2 = 'C:/xampp/htdocs/StudyQuest/assets/adventure/battlefields/chapter-12-creator-domain.jpg';
$f3 = 'C:/xampp/htdocs/StudyQuest/frontend/public/assets/adventure/battlefields/chapter-12-creator-domain.jpg';

echo json_encode([
    'f1_root_assets_exists' => file_exists($f1),
    'f1_size' => file_exists($f1) ? filesize($f1) : null,
    'f2_studyquest_assets_exists' => file_exists($f2),
    'f2_size' => file_exists($f2) ? filesize($f2) : null,
    'f3_frontend_public_exists' => file_exists($f3),
    'f3_size' => file_exists($f3) ? filesize($f3) : null,
], JSON_PRETTY_PRINT);
