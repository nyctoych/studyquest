<?php
require_once __DIR__ . '/backend/config/env.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/database/SpecialEventMigration.php';
require_once __DIR__ . '/backend/models/SpecialEvent.php';
require_once __DIR__ . '/backend/models/SpecialEventQuestion.php';
require_once __DIR__ . '/backend/models/SpecialEventAttempt.php';
require_once __DIR__ . '/backend/models/SpecialEventAnswer.php';
require_once __DIR__ . '/backend/services/GroqQuestionGeneratorService.php';
require_once __DIR__ . '/backend/services/QuestionValidationService.php';
require_once __DIR__ . '/backend/services/SpecialEventGenerationService.php';
require_once __DIR__ . '/backend/services/SpecialEventService.php';
require_once __DIR__ . '/backend/services/SpecialEventScoringService.php';
require_once __DIR__ . '/backend/services/SpecialEventRewardService.php';
require_once __DIR__ . '/backend/services/SpecialEventAnalyticsService.php';

echo "=== SPECIAL EVENT FEATURE VERIFICATION SUITE ===\n\n";

SpecialEventMigration::run();
$db = Database::getInstance();

function assertTest(string $name, bool $pass, string $info = '') {
    echo ($pass ? "[PASS] " : "[FAIL] ") . $name . ($info ? " ($info)" : "") . "\n";
    if (!$pass) {
        exit(1);
    }
}

// 1. Check Tables Exist
$tables = $db->query("SHOW TABLES LIKE 'special_event%'")->fetchAll(PDO::FETCH_COLUMN);
assertTest("Database tables created", count($tables) === 5, implode(', ', $tables));

// 2. Test Groq Service Configuration & Academic Synthesis
$groq = new GroqQuestionGeneratorService();
assertTest("Groq model is configurable", !empty($groq->getModel()), "Model: " . $groq->getModel());

// Test synthesis from Study Hall context
$sampleSource = [
    'domain_id' => 1,
    'part_id' => 1,
    'domain_name' => 'Neurodevelopmental Disorders',
    'part_title' => 'Intellectual Disability',
    'overview' => 'Review of intellectual developmental disorders under DSM-5 criteria.',
    'diagnostic_criteria' => 'Deficits in intellectual functions confirmed by both clinical assessment and individualized testing.',
    'differential_diagnosis' => 'Differentiated from Specific Learning Disorder and Autism Spectrum Disorder.',
    'clinical_example' => 'A school-age child presents with adaptive deficits in conceptual and practical domains.'
];

$batch = $groq->generateBatch($sampleSource, 4, ['moderate', 'difficult', 'very_difficult', 'board_style_case']);
assertTest("Question generation returns requested batch count", count($batch) === 4);

// 3. Question Validation Service
$validator = new QuestionValidationService();
$firstQ = $batch[0];
$valResult = $validator->validateQuestion($firstQ);
assertTest("Question passes comprehensive validation", $valResult['valid'], empty($valResult['errors']) ? 'OK' : implode('; ', $valResult['errors']));

// Test rejection of invalid question
$invalidQ = $firstQ;
$invalidQ['correct_choice'] = 'E'; // Invalid choice
$failResult = $validator->validateQuestion($invalidQ);
assertTest("Invalid correct_choice rejected", !$failResult['valid']);

// 4. Choice Randomization with Authoritative Mapping
$genService = new SpecialEventGenerationService();
$mockNorm = [
    'question_text' => 'Sample test question stem for verification purposes?',
    'choice_a' => 'Option Alpha',
    'choice_b' => 'Option Beta',
    'choice_c' => 'Option Gamma',
    'choice_d' => 'Option Delta',
    'correct_choice' => 'B'
];
$randomized = $genService->randomizeChoices($mockNorm);
$choices = [$randomized['choice_a'], $randomized['choice_b'], $randomized['choice_c'], $randomized['choice_d']];
$assignedCorrectChoiceText = $randomized['choice_' . strtolower($randomized['correct_choice'])];
assertTest("Choice randomization preserves authoritative correct answer text", $assignedCorrectChoiceText === 'Option Beta', "Mapped choice: " . $randomized['correct_choice']);

// 5. Daily 140-Question Event Generation
$testDate = '2026-10-01'; // Future date for clean test isolation
// Clean if existing
$existingTestEvent = (new SpecialEvent())->findByDate($testDate);
if ($existingTestEvent) {
    (new SpecialEvent())->delete((int)$existingTestEvent['id']);
}

echo "\nGenerating complete 140-question daily event for {$testDate}...\n";
$genRes = $genService->generateDailyEvent($testDate, true);
assertTest("140-question Special Event generated successfully", $genRes['success'], $genRes['message'] ?? '');

$createdEvent = (new SpecialEvent())->findByDate($testDate);
assertTest("Event status is published", $createdEvent && $createdEvent['status'] === 'published');

$qCount = (new SpecialEvent())->countQuestions((int)$createdEvent['id']);
assertTest("Event contains exactly 140 questions", $qCount === 140, "Count: {$qCount}");

// Verify all questions have valid choices and correct answers
$allQuestions = (new SpecialEventQuestion())->getByEventId((int)$createdEvent['id']);
$allValid = true;
$uniqueHashes = [];
foreach ($allQuestions as $q) {
    if (!in_array($q['correct_choice'], ['A', 'B', 'C', 'D']) || empty($q['explanation']) || empty($q['question_text'])) {
        $allValid = false;
        break;
    }
    $uniqueHashes[$q['question_hash']] = true;
}
assertTest("All 140 questions have valid fields and unique hashes", $allValid && count($uniqueHashes) === 140, "Unique hashes: " . count($uniqueHashes));

// 6. Player Examination Lifecycle
$testUserId = (int) $db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
if (!$testUserId) {
    $db->exec("INSERT INTO users (username, nickname, email, password) VALUES ('scholar_tester', 'Scholar Tester', 'scholar@test.com', 'testpass')");
    $testUserId = (int) $db->lastInsertId();
}

$svc = new SpecialEventService();
// Clean previous attempts on test event
$db->exec("DELETE FROM special_event_attempts WHERE special_event_id = {$createdEvent['id']}");

$startRes = $svc->startAttempt($testUserId, (int)$createdEvent['id']);
assertTest("Attempt started with snapshot ordering", $startRes['success'] && !empty($startRes['attempt_id']));
$attemptId = (int) $startRes['attempt_id'];

// Get attempt state
$state = $svc->getAttemptState($testUserId, $attemptId);
assertTest("Attempt state retrieves 140 safe questions", count($state['questions']) === 140);
assertTest("Safe questions do not expose correct_choice", !isset($state['questions'][0]['correct_choice']));
assertTest("Safe questions do not expose explanation", !isset($state['questions'][0]['explanation']));
assertTest("Server timer accurately tracks remaining seconds", $state['attempt']['remaining_seconds'] > 8000);

// Answer some questions
$firstQuestionId = $state['questions'][0]['id'];
$secondQuestionId = $state['questions'][1]['id'];

$ansRes1 = $svc->submitAnswer($testUserId, $attemptId, $firstQuestionId, 'B', false, 12);
assertTest("Player submits answer to question 1", $ansRes1['success']);

$markRes = $svc->toggleMark($testUserId, $attemptId, $secondQuestionId, true);
assertTest("Player marks question 2 for review", $markRes['success'] && $markRes['is_marked'] === true);

// 7. Final Submission & Server-Authoritative Scoring
$finishRes = $svc->finishAttempt($testUserId, $attemptId);
assertTest("Attempt finalized successfully", $finishRes['success'] && $finishRes['is_completed']);
assertTest("Scoring service calculates breakdown and percentage", isset($finishRes['evaluation']['percentage']) && isset($finishRes['evaluation']['domains']));
assertTest("Rewards awarded without duplication", $finishRes['rewards']['awarded'] === true);

// Attempt duplicate reward check
$secondFinish = $svc->finishAttempt($testUserId, $attemptId);
assertTest("Duplicate reward payout prevented", $secondFinish['rewards']['awarded'] === false && !empty($secondFinish['rewards']['already_claimed']));

// 8. Analytics & Post-Examination Review
$analytics = new SpecialEventAnalyticsService();
$history = $analytics->getUserHistory($testUserId);
assertTest("User history records completed trial", count($history) >= 1 && $history[0]['special_event_id'] === (int)$createdEvent['id']);

$review = $analytics->getAttemptReview($attemptId, $testUserId);
assertTest("Post-exam review exposes questions with explanations and Study Hall links", count($review['review_questions']) === 140 && !empty($review['review_questions'][0]['study_hall_link']));

echo "\nALL 20 VERIFICATION CHECKS PASSED PERFECTLY!\n";
