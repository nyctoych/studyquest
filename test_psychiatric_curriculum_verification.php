<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/services/AdventureProgressService.php';
require_once __DIR__ . '/backend/services/AdaptiveDifficultyService.php';
require_once __DIR__ . '/backend/services/CurriculumService.php';
require_once __DIR__ . '/backend/services/GeminiService.php';

$pdo = Database::getInstance();
$checks = [];

function check($label, $condition) {
    global $checks;
    $checks[] = ['label' => $label, 'pass' => (bool)$condition];
    echo ($condition ? "[PASS] " : "[FAIL] ") . $label . "\n";
}

echo "=== STUDYQUEST PSYCHIATRIC CURRICULUM VERIFICATION ===\n\n";

// 1. Domains count & source version
$domCount = (int)$pdo->query("SELECT COUNT(*) FROM psychiatric_domains WHERE source_version = 'DSM-5'")->fetchColumn();
check("psychiatric_domains has 19 domains configured with source_version = 'DSM-5'", $domCount === 19);

// 2. Each domain has 10 parts
$partCheck = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT domain_id, COUNT(*) as cnt FROM study_parts GROUP BY domain_id HAVING cnt = 10
    ) t
")->fetchColumn();
check("Every psychiatric domain contains exactly 10 parts in study_parts (19 domains)", $partCheck === 19);

// 3. Psychology domains mirrored
$psyDomCount = (int)$pdo->query("SELECT COUNT(*) FROM psychology_domains WHERE source_version = 'DSM-5'")->fetchColumn();
check("psychology_domains mirrored with 19 domains and source_version = 'DSM-5'", $psyDomCount === 19);

// 4. Psychology parts mirrored
$psyPartCheck = (int)$pdo->query("
    SELECT COUNT(*) FROM (
        SELECT domain_id, COUNT(*) as cnt FROM psychology_parts GROUP BY domain_id HAVING cnt = 10
    ) t
")->fetchColumn();
check("psychology_parts mirrored with 10 parts per domain (190 total parts)", $psyPartCheck === 19);

// 5. Structured content sections
$secCount = (int)$pdo->query("SELECT COUNT(*) FROM study_content_sections")->fetchColumn();
check("study_content_sections populated with structured educational reviewer content ($secCount sections)", $secCount >= 1000);

require_once __DIR__ . '/backend/database/PsychiatricCurriculumMigration.php';
PsychiatricCurriculumMigration::run();

// 6. Question bank associations
$qAssoc = (int)$pdo->query("
    SELECT COUNT(*) FROM questions 
    WHERE domain_id IS NOT NULL AND part_id IS NOT NULL AND source_version = 'DSM-5' AND status = 'approved'
")->fetchColumn();
check("Question bank contains approved DSM-5 questions linked to domain_id & part_id ($qAssoc questions)", $qAssoc >= 190);

// 7. Adventure Boss questions strictly from current domain
$advService = new AdventureProgressService();
for ($d = 1; $d <= 5; $d++) {
    $bossQuestions = $advService->getBossQuestionsForDomain($d, 10);
    $validDomain = true;
    foreach ($bossQuestions as $bq) {
        $qRow = $pdo->query("SELECT domain_id FROM questions WHERE id = " . (int)$bq['id'])->fetch();
        if ($qRow && (int)$qRow['domain_id'] !== $d) {
            $validDomain = false;
            break;
        }
    }
    check("Chapter {$d} Boss questions test multiple concepts STRICTLY from Domain {$d}", $validDomain && count($bossQuestions) > 0);
}

// 8. Adaptive Difficulty Service Attempt Recording with domain_id & part_id
$adaptive = new AdaptiveDifficultyService();
// Grab sample question
$sampleQ = $pdo->query("SELECT id, domain_id, part_id FROM questions WHERE domain_id = 4 AND part_id IS NOT NULL LIMIT 1")->fetch();
if ($sampleQ) {
    $attemptResult = $adaptive->recordAttempt(1, (int)$sampleQ['id'], true, 12);
    $lastAttempt = $pdo->query("SELECT domain_id, part_id FROM question_attempts WHERE id = " . (int)$attemptResult['attempt_id'])->fetch();
    check("question_attempts records domain_id and part_id correctly", (int)$lastAttempt['domain_id'] === 4 && !empty($lastAttempt['part_id']));
}

// 9. Weak area recommendation links back to Study Hall
$studyLink = $adaptive->resolveStudyLinkForTopic(null, 'Major Depressive Disorder');
check("Weak area recommendation links to Study Hall with URL: " . $studyLink['study_url'], strpos($studyLink['study_url'], '/study/') === 0 && !empty($studyLink['part_number']));

// 10. GeminiService generates draft question from Study Hall content
$gemini = new GeminiService();
$draftRes = $gemini->generatePsychologyQuestion([
    'domain' => 'Depressive Disorders',
    'domain_id' => 4,
    'topic' => 'Major Depressive Disorder',
    'part' => 'Part 2',
    'difficulty' => 'intermediate',
    'reviewer_summary' => 'Major Depressive Disorder requires five or more depressive symptoms during the same 2-week period with depressed mood or loss of interest/pleasure.'
]);
check("GeminiService generates draft question with status 'draft' from Study Hall context", $draftRes['success'] && ($draftRes['question']['status'] ?? '') === 'draft');

$qId = (int)$draftRes['question']['id'];
$revOk = $gemini->submitForReview($qId);
check("Gemini question advances from draft to under_review", $revOk);

$appOk = $gemini->approveQuestion($qId);
$finalStatus = $pdo->query("SELECT status FROM questions WHERE id = {$qId}")->fetchColumn();
check("Gemini question advances from under_review to approved for official use", $appOk && $finalStatus === 'approved');

echo "\nVerification Complete!\n";
