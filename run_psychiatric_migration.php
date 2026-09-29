<?php
require_once __DIR__ . '/backend/database/PsychiatricCurriculumMigration.php';

echo "Running PsychiatricCurriculumMigration...\n";
PsychiatricCurriculumMigration::run();
echo "Migration finished.\n";

$db = Database::getInstance();
$domCount = $db->query("SELECT COUNT(*) FROM psychiatric_domains")->fetchColumn();
$topCount = $db->query("SELECT COUNT(*) FROM psychiatric_topics")->fetchColumn();
$partCount = $db->query("SELECT COUNT(*) FROM study_parts")->fetchColumn();
$secCount = $db->query("SELECT COUNT(*) FROM study_content_sections")->fetchColumn();
$qCount = $db->query("SELECT COUNT(*) FROM questions WHERE source_version = 'DSM-5'")->fetchColumn();
$lqCount = $db->query("SELECT COUNT(*) FROM learning_questions WHERE source_version = 'DSM-5'")->fetchColumn();
$bossCount = $db->query("SELECT COUNT(*) FROM bosses")->fetchColumn();

echo "Psychiatric Domains: $domCount\n";
echo "Psychiatric Topics: $topCount\n";
echo "Study Parts: $partCount\n";
echo "Content Sections: $secCount\n";
echo "DSM-5 Questions: $qCount\n";
echo "DSM-5 Learning Questions: $lqCount\n";
echo "Bosses: $bossCount\n";
