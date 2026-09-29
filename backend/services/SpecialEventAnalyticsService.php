<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SpecialEventAttempt.php';
require_once __DIR__ . '/../models/SpecialEventQuestion.php';
require_once __DIR__ . '/../models/SpecialEventAnswer.php';

class SpecialEventAnalyticsService {
    private PDO $db;
    private SpecialEventAttempt $attemptModel;
    private SpecialEventQuestion $questionModel;
    private SpecialEventAnswer $answerModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->attemptModel = new SpecialEventAttempt();
        $this->questionModel = new SpecialEventQuestion();
        $this->answerModel = new SpecialEventAnswer();
    }

    public function getUserHistory(int $userId): array {
        $attempts = $this->attemptModel->getUserHistory($userId);
        $results = [];

        foreach ($attempts as $a) {
            $results[] = [
                'id' => (int) $a['id'],
                'special_event_id' => (int) $a['special_event_id'],
                'event_title' => $a['event_title'],
                'event_date' => $a['event_date'],
                'score' => (int) $a['score'],
                'correct_count' => (int) $a['correct_count'],
                'incorrect_count' => (int) $a['incorrect_count'],
                'percentage' => (float) $a['percentage'],
                'total_time' => (int) $a['total_time'],
                'status' => $a['status'],
                'xp_awarded' => (int) $a['xp_awarded'],
                'coins_awarded' => (int) $a['coins_awarded'],
                'completed_at' => $a['completed_at']
            ];
        }

        return $results;
    }

    /**
     * Complete post-examination review of all 140 questions with explanations and Study Hall links.
     */
    public function getAttemptReview(int $attemptId, int $userId): ?array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            return null;
        }

        // Only allow review if attempt has been completed
        if ($attempt['status'] === 'in_progress') {
            return null;
        }

        $eventId = (int) $attempt['special_event_id'];
        $questions = $this->questionModel->getByEventId($eventId);
        $answers = $this->answerModel->getByAttemptId($attemptId);

        // If attempt has custom question ordering, sort accordingly
        $order = $attempt['question_order'];
        if (!empty($order) && is_array($order)) {
            $keyed = [];
            foreach ($questions as $q) {
                $keyed[$q['id']] = $q;
            }
            $sorted = [];
            foreach ($order as $qId) {
                if (isset($keyed[$qId])) {
                    $sorted[] = $keyed[$qId];
                }
            }
            if (!empty($sorted)) {
                $questions = $sorted;
            }
        }

        $reviewList = [];
        foreach ($questions as $idx => $q) {
            $qId = (int) $q['id'];
            $ans = $answers[$qId] ?? null;
            $selected = $ans ? strtoupper($ans['selected_choice'] ?? '') : null;
            $correct = strtoupper($q['correct_choice']);
            $isCorrect = ($selected === $correct);

            $reviewList[] = [
                'index' => $idx + 1,
                'id' => $qId,
                'domain_id' => (int) $q['domain_id'],
                'domain_name' => $q['domain_name'],
                'study_part_id' => (int) $q['study_part_id'],
                'part_title' => $q['part_title'],
                'part_number' => (int) ($q['part_number'] ?? 1),
                'question_text' => $q['question_text'],
                'choices' => [
                    'A' => $q['choice_a'],
                    'B' => $q['choice_b'],
                    'C' => $q['choice_c'],
                    'D' => $q['choice_d']
                ],
                'selected_choice' => $selected,
                'correct_choice' => $correct,
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'],
                'difficulty' => $q['difficulty'],
                'question_type' => $q['question_type'],
                'bloom_level' => $q['bloom_level'] ?? 'apply',
                'response_time' => $ans ? (int)$ans['response_time'] : 0,
                'study_hall_link' => "/study/{$q['domain_id']}/part/" . ($q['part_number'] ?? 1)
            ];
        }

        return [
            'attempt' => $attempt,
            'review_questions' => $reviewList
        ];
    }

    /**
     * Comprehensive Bloom's Taxonomy analytics for a completed attempt.
     */
    public function getAttemptBloomAnalytics(int $attemptId, int $userId): ?array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            return null;
        }

        require_once __DIR__ . '/../models/SpecialEventBloomResult.php';
        $bloomModel = new SpecialEventBloomResult();
        $bloomResults = $bloomModel->getByAttemptId($attemptId);

        // If bloom results haven't been calculated yet, calculate using scoring service
        if (empty($bloomResults)) {
            require_once __DIR__ . '/SpecialEventScoringService.php';
            $scoring = new SpecialEventScoringService();
            $scoring->evaluateAttempt($attemptId, (int)$attempt['special_event_id']);
            $bloomResults = $bloomModel->getByAttemptId($attemptId);
        }

        // Fetch questions and answers for error analysis and Study Hall recommendations
        $eventId = (int)$attempt['special_event_id'];
        $questions = $this->questionModel->getByEventId($eventId);
        $answers = $this->answerModel->getByAttemptId($attemptId);

        // Map bloom levels to uppercase display names
        $displayNames = [
            'remember' => 'Remember',
            'understand' => 'Understand',
            'apply' => 'Apply',
            'analyze' => 'Analyze',
            'evaluate' => 'Evaluate',
            'create' => 'Create'
        ];

        $strongest = [];
        $developing = [];
        $needsReview = [];
        $bloomSummary = [];

        foreach ($bloomResults as $r) {
            $lvl = $r['bloom_level'];
            $acc = (float)$r['accuracy'];
            $title = $displayNames[$lvl] ?? ucfirst($lvl);

            $bloomSummary[$lvl] = [
                'bloom_level' => $lvl,
                'display_name' => $title,
                'question_count' => (int)$r['question_count'],
                'correct_count' => (int)$r['correct_count'],
                'incorrect_count' => (int)$r['incorrect_count'],
                'accuracy' => $acc,
                'average_response_time' => (int)$r['average_response_time']
            ];

            if ($acc >= 80.0) {
                $strongest[] = $title;
            } elseif ($acc >= 60.0) {
                $developing[] = $title;
            } else {
                $needsReview[] = $title;
            }
        }

        // If categorization is empty, fall back gracefully
        if (empty($strongest) && empty($developing) && empty($needsReview)) {
            $strongest = ['All areas assessed'];
        }

        // Generate neutral academic assessment summary statement (Sections 51 & 54)
        $summaryParts = [];
        if (!empty($strongest)) {
            $summaryParts[] = "Your strongest demonstrated performance in today's assessment was in " . implode(' and ', $strongest) . " questions.";
        }
        if (!empty($developing)) {
            $summaryParts[] = "Your performance in " . implode(' and ', $developing) . " was developing.";
        }
        if (!empty($needsReview)) {
            $summaryParts[] = "Questions requiring " . implode(' and ', $needsReview) . " produced more errors, indicating that reviewing the related Study Hall topics may help strengthen higher-order clinical reasoning.";
        } else {
            $summaryParts[] = "All cognitive domains demonstrated solid proficiency on this assessment.";
        }
        $academicInterpretation = implode(' ', $summaryParts);

        // Bloom Error Analysis (Section 56)
        $missedByBloom = [
            'remember' => [],
            'understand' => [],
            'apply' => [],
            'analyze' => [],
            'evaluate' => [],
            'create' => []
        ];
        $allMissed = [];

        foreach ($questions as $q) {
            $qId = (int)$q['id'];
            $bLvl = strtolower($q['bloom_level'] ?? 'apply');
            $ans = $answers[$qId] ?? null;
            $selected = $ans ? strtoupper(trim($ans['selected_choice'] ?? '')) : '';
            $correct = strtoupper(trim($q['correct_choice']));

            if ($selected !== $correct) {
                $missedItem = [
                    'question_id' => $qId,
                    'bloom_level' => $bLvl,
                    'domain_id' => (int)$q['domain_id'],
                    'domain_name' => $q['domain_name'] ?? '',
                    'study_part_id' => (int)$q['study_part_id'],
                    'part_title' => $q['part_title'] ?? '',
                    'part_number' => (int)($q['part_number'] ?? 1),
                    'selected_choice' => $selected ?: 'Unanswered',
                    'correct_choice' => $correct,
                    'response_time' => $ans ? (int)$ans['response_time'] : 0,
                    'study_hall_link' => "/study/{$q['domain_id']}/part/" . ($q['part_number'] ?? 1)
                ];
                $missedByBloom[$bLvl][] = $missedItem;
                $allMissed[] = $missedItem;
            }
        }

        // Identify observable assessment patterns (Section 56)
        $cognitivePatterns = [];
        $accRemember = $bloomSummary['remember']['accuracy'] ?? 0;
        $accUnderstand = $bloomSummary['understand']['accuracy'] ?? 0;
        $accApply = $bloomSummary['apply']['accuracy'] ?? 0;
        $accAnalyze = $bloomSummary['analyze']['accuracy'] ?? 0;
        $accEvaluate = $bloomSummary['evaluate']['accuracy'] ?? 0;
        $accCreate = $bloomSummary['create']['accuracy'] ?? 0;

        if ($accRemember >= 75 && $accApply < 65) {
            $cognitivePatterns[] = "Strong factual recall with lower clinical application accuracy.";
        }
        if ($accUnderstand >= 75 && $accAnalyze < 65) {
            $cognitivePatterns[] = "Solid conceptual understanding with opportunities to enhance differential case analysis.";
        }
        if ($accApply >= 75 && $accEvaluate < 65) {
            $cognitivePatterns[] = "Proficient clinical application with lower accuracy on multi-factor evaluation.";
        }
        if ($accAnalyze >= 75 && $accCreate < 60) {
            $cognitivePatterns[] = "Strong diagnostic analysis with developing management formulation skills.";
        }
        if (empty($cognitivePatterns)) {
            $cognitivePatterns[] = "Balanced cognitive performance across foundational and higher-order assessment questions.";
        }

        // Study Hall Recommendations (Section 55)
        // Aggregate missed questions by domain and study part
        $recommendedMap = [];
        foreach ($allMissed as $m) {
            $partKey = $m['domain_id'] . '_' . $m['study_part_id'];
            if (!isset($recommendedMap[$partKey])) {
                $recommendedMap[$partKey] = [
                    'domain_id' => $m['domain_id'],
                    'domain_name' => $m['domain_name'],
                    'study_part_id' => $m['study_part_id'],
                    'part_title' => $m['part_title'],
                    'part_number' => $m['part_number'],
                    'study_hall_link' => $m['study_hall_link'],
                    'missed_count' => 0,
                    'bloom_levels' => []
                ];
            }
            $recommendedMap[$partKey]['missed_count']++;
            $recommendedMap[$partKey]['bloom_levels'][] = $displayNames[$m['bloom_level']] ?? $m['bloom_level'];
        }

        foreach ($recommendedMap as &$rec) {
            $rec['bloom_levels'] = array_values(array_unique($rec['bloom_levels']));
        }
        unset($rec);

        // Sort by highest missed count
        $recommendations = array_values($recommendedMap);
        usort($recommendations, fn($a, $b) => $b['missed_count'] <=> $a['missed_count']);

        // Domain-level Bloom Breakdown
        $domainBloom = $bloomModel->getBloomByDomain($attemptId);

        return [
            'attempt_id' => $attemptId,
            'event_id' => $eventId,
            'bloom_summary' => array_values($bloomSummary),
            'cognitive_profile' => [
                'strongest_areas' => $strongest,
                'developing_areas' => $developing,
                'review_needed_areas' => $needsReview,
                'interpretation' => $academicInterpretation,
                'patterns' => $cognitivePatterns
            ],
            'study_hall_recommendations' => array_slice($recommendations, 0, 8),
            'error_analysis' => [
                'total_errors' => count($allMissed),
                'errors_by_bloom' => array_map('count', $missedByBloom)
            ],
            'domain_breakdown' => $domainBloom
        ];
    }

    public function getUserBloomHistory(int $userId): array {
        require_once __DIR__ . '/../models/SpecialEventBloomResult.php';
        $bloomModel = new SpecialEventBloomResult();
        return $bloomModel->getUserBloomHistory($userId);
    }
}
