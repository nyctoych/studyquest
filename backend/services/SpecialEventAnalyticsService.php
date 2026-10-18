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
            $sessNum = (int) ($a['session_number'] ?? 1);
            $sessTitle = ($sessNum === 2) ? 'Session 2 (Day 2)' : 'Session 1 (Day 1)';

            $results[] = [
                'id' => (int) $a['id'],
                'special_event_id' => (int) $a['special_event_id'],
                'event_title' => $a['event_title'] ?? 'Test Your Knowledge',
                'event_date' => $a['event_date'],
                'session_number' => $sessNum,
                'session_title' => $sessTitle,
                'score' => (int) $a['score'],
                'correct_count' => (int) ($a['correct_count'] ?? $a['score']),
                'incorrect_count' => (int) ($a['incorrect_count'] ?? 0),
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
     * Complete post-examination review of questions with explanations and Study Hall links.
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
        $sessionNumber = (int) ($attempt['session_number'] ?? 1);

        $questions = $this->questionModel->getByEventAndSession($eventId, $sessionNumber);
        if (empty($questions)) {
            $questions = $this->questionModel->getByEventId($eventId);
        }

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
                'session_number' => (int) ($q['session_number'] ?? $sessionNumber),
                'subject_name' => $q['subject_name'] ?? 'Psychology',
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

        // Rank Bloom levels ascending by accuracy to identify explicit Primary Review Focus
        $rankedSummary = array_values($bloomSummary);
        usort($rankedSummary, function ($a, $b) {
            if (abs($a['accuracy'] - $b['accuracy']) > 0.01) {
                return $a['accuracy'] <=> $b['accuracy'];
            }
            return $b['average_response_time'] <=> $a['average_response_time'];
        });
        $primaryWeakness = $rankedSummary[0] ?? null;
        $secondaryWeakness = $rankedSummary[1] ?? null;

        // Fetch event adaptive metadata if targeted
        $eventStmt = $this->db->prepare("SELECT target_bloom, secondary_bloom, is_adaptive FROM special_events WHERE id = ?");
        $eventStmt->execute([$eventId]);
        $eventMeta = $eventStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $targetedBloom = $eventMeta['target_bloom'] ?? null;

        // Generate educational, learning-oriented academic interpretation (Sections 29 & 52)
        $summaryParts = [];
        if ($primaryWeakness && $primaryWeakness['accuracy'] < 75.0) {
            $summaryParts[] = "Today's primary review focus is " . $primaryWeakness['display_name'] . " (" . $primaryWeakness['accuracy'] . "% accuracy). Reviewing corresponding Study Hall clinical cases will help reinforce this diagnostic process.";
        }
        if (!empty($strongest)) {
            $summaryParts[] = "Stronger performance was demonstrated in " . implode(' and ', $strongest) . ".";
        }
        if (!empty($developing)) {
            $summaryParts[] = "Performance in " . implode(' and ', $developing) . " is actively developing.";
        }
        if ($targetedBloom && isset($bloomSummary[$targetedBloom])) {
            $tName = $displayNames[$targetedBloom] ?? ucfirst($targetedBloom);
            $tAcc = $bloomSummary[$targetedBloom]['accuracy'];
            $summaryParts[] = "Adaptive Insight: Today's exam placed increased emphasis on {$tName} items based on past review needs, achieving {$tAcc}% on these focused questions.";
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
                'primary_review_focus' => $primaryWeakness ? $primaryWeakness['display_name'] : null,
                'secondary_review_focus' => $secondaryWeakness ? $secondaryWeakness['display_name'] : null,
                'strongest_areas' => $strongest,
                'developing_areas' => $developing,
                'review_needed_areas' => $needsReview,
                'interpretation' => $academicInterpretation,
                'patterns' => $cognitivePatterns
            ],
            'adaptive_info' => [
                'is_adaptive' => !empty($eventMeta['is_adaptive']),
                'targeted_bloom' => $targetedBloom ? ($displayNames[$targetedBloom] ?? ucfirst($targetedBloom)) : null,
                'secondary_bloom' => !empty($eventMeta['secondary_bloom']) ? ($displayNames[$eventMeta['secondary_bloom']] ?? ucfirst($eventMeta['secondary_bloom'])) : null
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

    /**
     * Aggregated performance analytics for /special-event dashboard.
     */
    public function getUserAnalytics(int $userId): array {
        // 1. Overall attempt statistics
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN session_number = 1 THEN 1 ELSE 0 END) as session_1_count,
                SUM(CASE WHEN session_number = 2 THEN 1 ELSE 0 END) as session_2_count,
                MAX(score) as best_score,
                AVG(percentage) as avg_percentage,
                SUM(correct_count) as total_correct,
                SUM(incorrect_count) as total_incorrect
            FROM special_event_attempts
            WHERE user_id = ? AND status IN ('completed', 'timed_out')
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // 2. Total questions answered
        $ansStmt = $this->db->prepare("
            SELECT COUNT(a.id) 
            FROM special_event_answers a
            JOIN special_event_attempts att ON a.attempt_id = att.id
            WHERE att.user_id = ?
        ");
        $ansStmt->execute([$userId]);
        $totalAnswered = (int) $ansStmt->fetchColumn();

        // 3. Subject-level performance
        $subjStmt = $this->db->prepare("
            SELECT 
                COALESCE(q.subject_name, 'General Psychology') as subject_name,
                COUNT(a.id) as total_questions,
                SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                SUM(CASE WHEN a.is_correct = 0 THEN 1 ELSE 0 END) as incorrect_count,
                ROUND(SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(a.id) * 100, 1) as accuracy
            FROM special_event_answers a
            JOIN special_event_attempts att ON a.attempt_id = att.id
            JOIN special_event_questions q ON a.question_id = q.id
            WHERE att.user_id = ? AND att.status IN ('completed', 'timed_out')
            GROUP BY COALESCE(q.subject_name, 'General Psychology')
            ORDER BY accuracy DESC
        ");
        $subjStmt->execute([$userId]);
        $subjects = $subjStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Bloom-level performance
        $bloomStmt = $this->db->prepare("
            SELECT 
                LOWER(COALESCE(a.bloom_level, q.bloom_level, 'apply')) as bloom_level,
                COUNT(a.id) as total_questions,
                SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                ROUND(SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(a.id) * 100, 1) as accuracy
            FROM special_event_answers a
            JOIN special_event_attempts att ON a.attempt_id = att.id
            JOIN special_event_questions q ON a.question_id = q.id
            WHERE att.user_id = ? AND att.status IN ('completed', 'timed_out')
            GROUP BY LOWER(COALESCE(a.bloom_level, q.bloom_level, 'apply'))
            ORDER BY accuracy DESC
        ");
        $bloomStmt->execute([$userId]);
        $bloomData = $bloomStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Recent attempts score trend
        $trendStmt = $this->db->prepare("
            SELECT att.id, se.event_date, att.session_number, att.score, att.percentage, att.completed_at
            FROM special_event_attempts att
            JOIN special_events se ON att.special_event_id = se.id
            WHERE att.user_id = ? AND att.status IN ('completed', 'timed_out')
            ORDER BY att.completed_at ASC
            LIMIT 15
        ");
        $trendStmt->execute([$userId]);
        $trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. Study Hall recommendations
        $recStmt = $this->db->prepare("
            SELECT 
                q.domain_id,
                COALESCE(d.name, 'General Review') as domain_title,
                q.study_part_id,
                COALESCE(p.title, 'Lesson Review') as part_title,
                COALESCE(p.part_number, 1) as part_number,
                COUNT(a.id) as missed_count
            FROM special_event_answers a
            JOIN special_event_attempts att ON a.attempt_id = att.id
            JOIN special_event_questions q ON a.question_id = q.id
            LEFT JOIN psychiatric_domains d ON q.domain_id = d.id
            LEFT JOIN study_parts p ON q.study_part_id = p.id
            WHERE att.user_id = ? AND a.is_correct = 0
            GROUP BY q.domain_id, q.study_part_id
            ORDER BY missed_count DESC
            LIMIT 6
        ");
        $recStmt->execute([$userId]);
        $recs = $recStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recs as &$r) {
            $r['study_hall_link'] = "/study/{$r['domain_id']}/part/{$r['part_number']}";
        }
        unset($r);

        return [
            'summary' => [
                'total_completed_attempts' => (int) ($stats['total_attempts'] ?? 0),
                'session_1_completed' => (int) ($stats['session_1_count'] ?? 0),
                'session_2_completed' => (int) ($stats['session_2_count'] ?? 0),
                'total_questions_answered' => $totalAnswered,
                'overall_average_accuracy' => round((float) ($stats['avg_percentage'] ?? 0), 1),
                'best_score' => (int) ($stats['best_score'] ?? 0),
                'strongest_subject' => !empty($subjects) ? $subjects[0]['subject_name'] : null,
                'weakest_subject' => !empty($subjects) ? end($subjects)['subject_name'] : null,
                'strongest_bloom' => !empty($bloomData) ? ucfirst($bloomData[0]['bloom_level']) : null,
                'weakest_bloom' => !empty($bloomData) ? ucfirst(end($bloomData)['bloom_level']) : null
            ],
            'subjects' => $subjects,
            'bloom_breakdown' => $bloomData,
            'score_trend' => $trend,
            'recommendations' => $recs
        ];
    }
}
