<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SpecialEventQuestion.php';
require_once __DIR__ . '/../models/SpecialEventAnswer.php';

class SpecialEventScoringService {
    private PDO $db;
    private SpecialEventQuestion $questionModel;
    private SpecialEventAnswer $answerModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->questionModel = new SpecialEventQuestion();
        $this->answerModel = new SpecialEventAnswer();
    }

    /**
     * Compute authoritative scores and deep breakdown analytics for a Special Event attempt.
     */
    public function evaluateAttempt(int $attemptId, int $eventId): array {
        // Fetch all authoritative questions for this event
        $questions = $this->questionModel->getByEventId($eventId);
        $totalQuestions = count($questions);

        // Fetch user's submitted answers
        $answers = $this->answerModel->getByAttemptId($attemptId);

        $correctCount = 0;
        $incorrectCount = 0;
        $unansweredCount = 0;
        $totalResponseTime = 0;

        $responseTimes = [];
        $domainStats = [];
        $partStats = [];
        $difficultyStats = [];
        $typeStats = [];

        // Prepare updates for is_correct in special_event_answers
        $updateStmt = $this->db->prepare("UPDATE special_event_answers SET is_correct = ? WHERE attempt_id = ? AND question_id = ?");

        foreach ($questions as $q) {
            $qId = (int) $q['id'];
            $domId = (int) $q['domain_id'];
            $domName = $q['domain_name'] ?: "Domain {$domId}";
            $partId = (int) $q['study_part_id'];
            $partTitle = $q['part_title'] ?: "Part {$partId}";
            $diff = $q['difficulty'];
            $qType = $q['question_type'];
            $correctChoice = strtoupper($q['correct_choice']);

            // Init breakdowns
            if (!isset($domainStats[$domId])) {
                $domainStats[$domId] = ['domain_id' => $domId, 'name' => $domName, 'total' => 0, 'correct' => 0];
            }
            if (!isset($partStats[$partId])) {
                $partStats[$partId] = ['part_id' => $partId, 'domain_id' => $domId, 'title' => $partTitle, 'total' => 0, 'correct' => 0];
            }
            if (!isset($difficultyStats[$diff])) {
                $difficultyStats[$diff] = ['difficulty' => $diff, 'total' => 0, 'correct' => 0];
            }
            if (!isset($typeStats[$qType])) {
                $typeStats[$qType] = ['type' => $qType, 'total' => 0, 'correct' => 0];
            }

            $domainStats[$domId]['total']++;
            $partStats[$partId]['total']++;
            $difficultyStats[$diff]['total']++;
            $typeStats[$qType]['total']++;

            $ans = $answers[$qId] ?? null;
            $selected = $ans ? strtoupper(trim($ans['selected_choice'] ?? '')) : '';
            $respTime = $ans ? (int)$ans['response_time'] : 0;

            if ($respTime > 0) {
                $responseTimes[] = $respTime;
                $totalResponseTime += $respTime;
            }

            // Bloom's Taxonomy tracking
            $bloom = strtolower(trim($q['bloom_level'] ?? ''));
            if (!in_array($bloom, ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'])) {
                $bloom = 'apply';
            }
            if (!isset($bloomStats[$bloom])) {
                $bloomStats[$bloom] = [
                    'bloom_level' => $bloom,
                    'question_count' => 0,
                    'correct_count' => 0,
                    'incorrect_count' => 0,
                    'response_times' => []
                ];
            }
            $bloomStats[$bloom]['question_count']++;

            if ($selected === '') {
                $unansweredCount++;
                $isCorrect = false;
                $bloomStats[$bloom]['incorrect_count']++;
            } elseif ($selected === $correctChoice) {
                $correctCount++;
                $isCorrect = true;
                $domainStats[$domId]['correct']++;
                $partStats[$partId]['correct']++;
                $difficultyStats[$diff]['correct']++;
                $typeStats[$qType]['correct']++;
                $bloomStats[$bloom]['correct_count']++;
            } else {
                $incorrectCount++;
                $isCorrect = false;
                $bloomStats[$bloom]['incorrect_count']++;
            }

            if ($ans && (int)$ans['response_time'] > 0) {
                $bloomStats[$bloom]['response_times'][] = (int)$ans['response_time'];
            }

            if ($ans) {
                $updateStmt->execute([$isCorrect ? 1 : 0, $attemptId, $qId]);
            }
        }

        $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0.00;

        // Calculate timing stats
        $avgResponseTime = !empty($responseTimes) ? round(array_sum($responseTimes) / count($responseTimes), 1) : 0;
        $fastestResponse = !empty($responseTimes) ? min($responseTimes) : 0;
        $slowestResponse = !empty($responseTimes) ? max($responseTimes) : 0;

        // Format percentages
        foreach ($domainStats as &$ds) {
            $ds['percentage'] = $ds['total'] > 0 ? round(($ds['correct'] / $ds['total']) * 100, 1) : 0;
        }
        unset($ds);

        foreach ($partStats as &$ps) {
            $ps['percentage'] = $ps['total'] > 0 ? round(($ps['correct'] / $ps['total']) * 100, 1) : 0;
        }
        unset($ps);

        foreach ($difficultyStats as &$diffs) {
            $diffs['percentage'] = $diffs['total'] > 0 ? round(($diffs['correct'] / $diffs['total']) * 100, 1) : 0;
        }
        unset($diffs);

        foreach ($typeStats as &$ts) {
            $ts['percentage'] = $ts['total'] > 0 ? round(($ts['correct'] / $ts['total']) * 100, 1) : 0;
        }
        unset($ts);

        // Calculate Bloom's Taxonomy analytics and save to special_event_bloom_results
        require_once __DIR__ . '/../models/SpecialEventBloomResult.php';
        $bloomModel = new SpecialEventBloomResult();
        $orderedLevels = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'];
        $bloomPerformance = [];

        foreach ($orderedLevels as $lvl) {
            $bs = $bloomStats[$lvl] ?? [
                'bloom_level' => $lvl,
                'question_count' => 0,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'response_times' => []
            ];

            $qCnt = $bs['question_count'];
            $cCnt = $bs['correct_count'];
            $iCnt = $bs['incorrect_count'];
            $acc = $qCnt > 0 ? round(($cCnt / $qCnt) * 100, 1) : 0.0;
            $avgTime = !empty($bs['response_times']) ? (int) round(array_sum($bs['response_times']) / count($bs['response_times'])) : 0;
            $pctOfTotal = $totalQuestions > 0 ? round(($qCnt / $totalQuestions) * 100, 1) : 0.0;

            $diffFromOverall = $acc - $percentage;
            $relPerf = match(true) {
                $diffFromOverall >= 7.0 => 'above_average',
                $diffFromOverall <= -7.0 => 'below_average',
                default => 'consistent'
            };

            $lvlResult = [
                'bloom_level' => $lvl,
                'question_count' => $qCnt,
                'correct_count' => $cCnt,
                'incorrect_count' => $iCnt,
                'accuracy' => $acc,
                'average_response_time' => $avgTime,
                'percentage_of_total' => $pctOfTotal,
                'relative_performance' => $relPerf
            ];

            $bloomPerformance[] = $lvlResult;

            if ($qCnt > 0) {
                $bloomModel->saveAttemptBloom(
                    $attemptId,
                    $lvl,
                    $qCnt,
                    $cCnt,
                    $iCnt,
                    $acc,
                    $avgTime
                );
            }
        }

        // Sort domains by percentage to identify weak areas
        $weakDomains = array_values($domainStats);
        usort($weakDomains, fn($a, $b) => $a['percentage'] <=> $b['percentage']);

        return [
            'total_questions' => $totalQuestions,
            'score' => $correctCount,
            'correct_count' => $correctCount,
            'incorrect_count' => $incorrectCount,
            'unanswered_count' => $unansweredCount,
            'percentage' => $percentage,
            'total_time' => $totalResponseTime,
            'average_response_time' => $avgResponseTime,
            'fastest_response' => $fastestResponse,
            'slowest_response' => $slowestResponse,
            'domains' => array_values($domainStats),
            'parts' => array_values($partStats),
            'difficulties' => array_values($difficultyStats),
            'question_types' => array_values($typeStats),
            'bloom_performance' => $bloomPerformance,
            'weakest_domains' => array_slice($weakDomains, 0, 3)
        ];
    }
}
