<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/MockExamBlueprint.php';
require_once __DIR__ . '/../models/SpecialEvent.php';
require_once __DIR__ . '/../models/SpecialEventQuestion.php';
require_once __DIR__ . '/../models/SpecialEventAttempt.php';
require_once __DIR__ . '/../models/SpecialEventAnswer.php';
require_once __DIR__ . '/SpecialEventGenerationService.php';
require_once __DIR__ . '/SpecialEventScoringService.php';
require_once __DIR__ . '/SpecialEventRewardService.php';
require_once __DIR__ . '/SpecialEventAnalyticsService.php';

class SpecialEventService {
    private PDO $db;
    private SpecialEvent $eventModel;
    private SpecialEventQuestion $questionModel;
    private SpecialEventAttempt $attemptModel;
    private SpecialEventAnswer $answerModel;
    private SpecialEventGenerationService $generationService;
    private SpecialEventScoringService $scoringService;
    private SpecialEventRewardService $rewardService;
    private SpecialEventAnalyticsService $analyticsService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->eventModel = new SpecialEvent();
        $this->questionModel = new SpecialEventQuestion();
        $this->attemptModel = new SpecialEventAttempt();
        $this->answerModel = new SpecialEventAnswer();
        $this->generationService = new SpecialEventGenerationService();
        $this->scoringService = new SpecialEventScoringService();
        $this->rewardService = new SpecialEventRewardService();
        $this->analyticsService = new SpecialEventAnalyticsService();
    }

    /**
     * Get or ensure today's published Special Event, including two-session status and blueprints.
     */
    public function getTodayEvent(?int $userId = null): array {
        $today = date('Y-m-d');
        $targetUserId = (int) ($userId ?? 0);
        $event = $this->eventModel->getTodayPublishedEvent($targetUserId);

        // If today's event does not exist yet for this user, generate it automatically
        if (!$event) {
            $genResult = $this->generationService->generateDailyEvent($today, $targetUserId);
            if ($genResult['success'] && !empty($genResult['event_id'])) {
                $event = $this->eventModel->find((int)$genResult['event_id']);
            }
        }

        if (!$event || $event['status'] !== 'published') {
            return [
                'has_event' => false,
                'status' => $event['status'] ?? 'none',
                'message' => 'Today\'s Test Your Knowledge examination is currently preparing. Check back soon.'
            ];
        }

        $eventId = (int) $event['id'];
        $questionCount = $this->eventModel->countQuestions($eventId);

        // If event has legacy 140 questions, auto-regenerate with the 450 blueprint items
        if ($questionCount < 450 && $targetUserId > 0) {
            $regen = $this->generationService->generateDailyEvent($today, $targetUserId);
            if ($regen['success'] && !empty($regen['event_id'])) {
                $eventId = (int)$regen['event_id'];
                $event = $this->eventModel->find($eventId);
                $questionCount = $this->eventModel->countQuestions($eventId);
            }
        }

        $blueprint = MockExamBlueprint::getBlueprint();
        $session1Config = MockExamBlueprint::getSession(1);
        $session2Config = MockExamBlueprint::getSession(2);

        $session1Count = $this->questionModel->countByEventAndSession($eventId, 1);
        $session2Count = $this->questionModel->countByEventAndSession($eventId, 2);

        $session1Attempt = null;
        $session2Attempt = null;
        $activeAttempt = null;

        if ($userId) {
            // Retrieve session 1 attempt
            $s1Raw = $this->attemptModel->getUserEventAttempt($userId, $eventId, 1);
            if ($s1Raw) {
                $s1Summary = $this->answerModel->getSummary((int)$s1Raw['id'], $session1Count ?: 200);
                $duration = $this->getSessionDurationMinutes(1);
                $elapsed = time() - strtotime($s1Raw['started_at']);
                $allowed = $duration * 60;
                $remaining = max(0, $allowed - $elapsed);

                if ($s1Raw['status'] === 'in_progress' && $remaining <= 0) {
                    $this->finishAttempt($userId, (int)$s1Raw['id'], true);
                    $s1Raw = $this->attemptModel->find((int)$s1Raw['id']);
                }

                $session1Attempt = [
                    'id' => (int) $s1Raw['id'],
                    'session_number' => 1,
                    'status' => $s1Raw['status'],
                    'started_at' => $s1Raw['started_at'],
                    'completed_at' => $s1Raw['completed_at'],
                    'score' => (int) $s1Raw['score'],
                    'correct_count' => (int) ($s1Raw['correct_count'] ?? $s1Raw['score']),
                    'incorrect_count' => (int) ($s1Raw['incorrect_count'] ?? 0),
                    'percentage' => (float) $s1Raw['percentage'],
                    'remaining_seconds' => $remaining,
                    'summary' => $s1Summary
                ];

                if ($s1Raw['status'] === 'in_progress') {
                    $activeAttempt = $session1Attempt;
                }
            }

            // Retrieve session 2 attempt
            $s2Raw = $this->attemptModel->getUserEventAttempt($userId, $eventId, 2);
            if ($s2Raw) {
                $s2Summary = $this->answerModel->getSummary((int)$s2Raw['id'], $session2Count ?: 250);
                $duration = $this->getSessionDurationMinutes(2);
                $elapsed = time() - strtotime($s2Raw['started_at']);
                $allowed = $duration * 60;
                $remaining = max(0, $allowed - $elapsed);

                if ($s2Raw['status'] === 'in_progress' && $remaining <= 0) {
                    $this->finishAttempt($userId, (int)$s2Raw['id'], true);
                    $s2Raw = $this->attemptModel->find((int)$s2Raw['id']);
                }

                $session2Attempt = [
                    'id' => (int) $s2Raw['id'],
                    'session_number' => 2,
                    'status' => $s2Raw['status'],
                    'started_at' => $s2Raw['started_at'],
                    'completed_at' => $s2Raw['completed_at'],
                    'score' => (int) $s2Raw['score'],
                    'correct_count' => (int) ($s2Raw['correct_count'] ?? $s2Raw['score']),
                    'incorrect_count' => (int) ($s2Raw['incorrect_count'] ?? 0),
                    'percentage' => (float) $s2Raw['percentage'],
                    'remaining_seconds' => $remaining,
                    'summary' => $s2Summary
                ];

                if ($s2Raw['status'] === 'in_progress' && !$activeAttempt) {
                    $activeAttempt = $session2Attempt;
                }
            }
        }

        // Determine session availability
        $s1Status = 'available';
        if ($session1Attempt) {
            $s1Status = $session1Attempt['status'];
        }

        $s2Status = 'locked';
        $s2LockReason = 'Complete Session 1 before beginning Session 2.';
        if ($session1Attempt && in_array($session1Attempt['status'], ['completed', 'timed_out', 'disqualified'])) {
            $s2Status = $session2Attempt ? $session2Attempt['status'] : 'available';
            $s2LockReason = null;
        }

        $sessions = [
            [
                'session_number' => 1,
                'name' => $session1Config['name'],
                'short_title' => 'Day 1 — Session 1',
                'target_questions' => $session1Config['target_questions'],
                'actual_questions' => $session1Count,
                'duration_minutes' => $this->getSessionDurationMinutes(1),
                'status' => $s1Status,
                'can_start' => ($s1Status === 'available' || $s1Status === 'in_progress'),
                'subjects' => $session1Config['subjects'],
                'attempt' => $session1Attempt
            ],
            [
                'session_number' => 2,
                'name' => $session2Config['name'],
                'short_title' => 'Day 2 — Session 2',
                'target_questions' => $session2Config['target_questions'],
                'actual_questions' => $session2Count,
                'duration_minutes' => $this->getSessionDurationMinutes(2),
                'status' => $s2Status,
                'can_start' => ($s2Status === 'available' || $s2Status === 'in_progress'),
                'lock_reason' => $s2LockReason,
                'subjects' => $session2Config['subjects'],
                'attempt' => $session2Attempt
            ]
        ];

        // Combined Full Examination Summary if both sessions are completed
        $fullExamSummary = null;
        if ($session1Attempt && in_array($session1Attempt['status'], ['completed', 'timed_out']) &&
            $session2Attempt && in_array($session2Attempt['status'], ['completed', 'timed_out'])) {
            $totalQuestions = ($session1Attempt['summary']['total_questions'] ?? 200) + ($session2Attempt['summary']['total_questions'] ?? 250);
            $totalCorrect = ($session1Attempt['score'] ?? 0) + ($session2Attempt['score'] ?? 0);
            $totalIncorrect = ($session1Attempt['incorrect_count'] ?? 0) + ($session2Attempt['incorrect_count'] ?? 0);
            $overallPct = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0;

            $fullExamSummary = [
                'total_questions' => $totalQuestions,
                'total_score' => $totalCorrect,
                'total_incorrect' => $totalIncorrect,
                'percentage' => $overallPct,
                'passed' => $overallPct >= 75.0,
                'session_1' => [
                    'score' => $session1Attempt['score'],
                    'percentage' => $session1Attempt['percentage']
                ],
                'session_2' => [
                    'score' => $session2Attempt['score'],
                    'percentage' => $session2Attempt['percentage']
                ]
            ];
        }

        return [
            'has_event' => true,
            'event' => [
                'id' => $eventId,
                'user_id' => (int) ($event['user_id'] ?? 0),
                'title' => 'TEST YOUR KNOWLEDGE',
                'subtitle' => 'Daily Psychometrician Board Examination Simulator',
                'description' => 'Challenge your understanding with a structured, two-session mock board examination. Every question is grounded in your Study Hall reviewers, helping you identify knowledge gaps, strengthen academic reasoning, and track your progress over time.',
                'event_date' => $event['event_date'],
                'question_count' => $questionCount,
                'assigned_domains' => $event['assigned_domains'] ?? [],
                'status' => $event['status'],
                'is_adaptive' => !empty($event['is_adaptive']),
                'blueprint' => $blueprint,
                'published_at' => $event['published_at'],
                'expires_at' => $event['expires_at']
            ],
            'sessions' => $sessions,
            'full_exam_summary' => $fullExamSummary,
            'user_attempt' => $activeAttempt ?: ($session2Attempt ?: $session1Attempt)
        ];
    }

    /**
     * Start a new authoritative examination attempt for the user for the designated session (1 or 2).
     */
    public function startAttempt(int $userId, int $eventId, int $sessionNumber = 1): array {
        $sessionNumber = ($sessionNumber === 2) ? 2 : 1;

        $event = $this->eventModel->find($eventId);
        if (!$event || $event['status'] !== 'published') {
            throw new RuntimeException("Test Your Knowledge examination is not available.");
        }

        // If the targeted event is global (0) or belongs to another user, redirect to this user's personal event
        if ((int)$event['user_id'] !== $userId) {
            $userTodayEvent = $this->eventModel->getTodayPublishedEvent($userId);
            if (!$userTodayEvent) {
                $gen = $this->generationService->generateDailyEvent(date('Y-m-d'), $userId);
                if ($gen['success'] && !empty($gen['event_id'])) {
                    $userTodayEvent = $this->eventModel->find((int)$gen['event_id']);
                }
            }
            if ($userTodayEvent) {
                $eventId = (int) $userTodayEvent['id'];
                $event = $userTodayEvent;
            }
        }

        // If starting Session 2, ensure Session 1 is already completed
        if ($sessionNumber === 2) {
            $s1Attempt = $this->attemptModel->getUserEventAttempt($userId, $eventId, 1);
            if (!$s1Attempt || !in_array($s1Attempt['status'], ['completed', 'timed_out', 'disqualified'])) {
                throw new RuntimeException("You must complete Session 1 (Day 1) before starting Session 2.");
            }
        }

        // Check if an attempt already exists for this session
        $existing = $this->attemptModel->getUserEventAttempt($userId, $eventId, $sessionNumber);
        if ($existing) {
            if ($existing['status'] === 'in_progress') {
                return [
                    'success' => true,
                    'message' => "Resuming active Session {$sessionNumber} attempt",
                    'attempt_id' => (int) $existing['id'],
                    'session_number' => $sessionNumber,
                    'is_resumed' => true
                ];
            } else {
                throw new RuntimeException("You have already completed Session {$sessionNumber} of today's examination.");
            }
        }

        // Fetch questions for this session
        $questions = $this->questionModel->getByEventAndSession($eventId, $sessionNumber, true);
        if (empty($questions)) {
            // Fallback: fetch all and take half
            $all = $this->questionModel->getByEventId($eventId, true);
            if (empty($all)) {
                throw new RuntimeException("No examination questions are available for this session.");
            }
            $questions = ($sessionNumber === 1) ? array_slice($all, 0, 200) : array_slice($all, 200);
        }

        // Ensure questions are strictly sorted: Subject 1 first, then Subject 2, by display_order
        usort($questions, function($a, $b) use ($sessionNumber) {
            $subjA = trim($a['subject_name'] ?? '');
            $subjB = trim($b['subject_name'] ?? '');
            if ($subjA !== $subjB) {
                if ($sessionNumber === 1) {
                    $isASubj1 = stripos($subjA, 'personality') !== false;
                    $isBSubj1 = stripos($subjB, 'personality') !== false;
                    if ($isASubj1 && !$isBSubj1) return -1;
                    if (!$isASubj1 && $isBSubj1) return 1;
                } else {
                    $isASubj1 = stripos($subjA, 'industrial') !== false;
                    $isBSubj1 = stripos($subjB, 'industrial') !== false;
                    if ($isASubj1 && !$isBSubj1) return -1;
                    if (!$isASubj1 && $isBSubj1) return 1;
                }
            }
            $orderA = (int)($a['display_order'] ?? $a['id']);
            $orderB = (int)($b['display_order'] ?? $b['id']);
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }
            return ((int)$a['id']) <=> ((int)$b['id']);
        });

        // Questions are strictly ordered by subject (no randomization)
        $questionIds = array_column($questions, 'id');

        $attemptId = $this->attemptModel->create([
            'special_event_id' => $eventId,
            'user_id' => $userId,
            'session_number' => $sessionNumber,
            'question_order' => $questionIds
        ]);

        return [
            'success' => true,
            'message' => "Session {$sessionNumber} examination initialized",
            'attempt_id' => $attemptId,
            'session_number' => $sessionNumber,
            'is_resumed' => false
        ];
    }

    /**
     * Get the active attempt state including safe questions, timer, session info, and current answers.
     */
    public function getAttemptState(int $userId, int $attemptId): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or access denied.");
        }

        $eventId = (int) $attempt['special_event_id'];
        $sessionNumber = (int) ($attempt['session_number'] ?? 1);

        $durationMinutes = $this->getSessionDurationMinutes($sessionNumber);
        $totalAllowedSeconds = $durationMinutes * 60;
        $elapsedSeconds = isset($attempt['elapsed_seconds']) ? (int)$attempt['elapsed_seconds'] : max(0, time() - strtotime($attempt['started_at']));
        $remainingSeconds = max(0, $totalAllowedSeconds - $elapsedSeconds);

        // Check if timer expired while exam was in progress
        if ($attempt['status'] === 'in_progress' && $remainingSeconds <= 0) {
            return $this->finishAttempt($userId, $attemptId, true);
        }

        // Fetch questions for this session and order them by the snapshot question_order
        $sessionQuestions = $this->questionModel->getByEventAndSession($eventId, $sessionNumber);
        if (empty($sessionQuestions)) {
            $sessionQuestions = $this->questionModel->getByEventId($eventId);
        }

        $keyedQuestions = [];
        foreach ($sessionQuestions as $q) {
            $keyedQuestions[$q['id']] = $this->questionModel->getClientSafeQuestion($q);
        }

        $orderedQuestions = [];
        $order = $attempt['question_order'];
        if (!empty($order) && is_array($order)) {
            foreach ($order as $qId) {
                if (isset($keyedQuestions[$qId])) {
                    $orderedQuestions[] = $keyedQuestions[$qId];
                }
            }
        }
        if (empty($orderedQuestions)) {
            $orderedQuestions = array_values($keyedQuestions);
        }

        // Fetch user's recorded answers
        $answers = $this->answerModel->getByAttemptId($attemptId);

        // Compute summary counts
        $totalQuestions = count($orderedQuestions);
        $answeredCount = 0;
        $markedCount = 0;

        $userAnswers = [];
        foreach ($orderedQuestions as $idx => $q) {
            $qId = $q['id'];
            $ans = $answers[$qId] ?? null;
            $selected = $ans ? ($ans['selected_choice'] ?: null) : null;
            $isMarked = $ans ? (bool)$ans['is_marked'] : false;

            if ($selected) {
                $answeredCount++;
            }
            if ($isMarked) {
                $markedCount++;
            }

            $userAnswers[$qId] = [
                'selected_choice' => $selected,
                'is_marked' => $isMarked,
                'index' => $idx + 1
            ];
        }

        return [
            'success' => true,
            'attempt' => [
                'id' => $attemptId,
                'special_event_id' => $eventId,
                'session_number' => $sessionNumber,
                'session_title' => ($sessionNumber === 2) ? 'Session 2 — Examination Day 2' : 'Session 1 — Examination Day 1',
                'event_title' => $attempt['event_title'] ?? 'Test Your Knowledge',
                'event_date' => $attempt['event_date'],
                'status' => $attempt['status'],
                'disqualification_reason' => $attempt['disqualification_reason'] ?? null,
                'tab_switch_count' => (int) ($attempt['tab_switch_count'] ?? 0),
                'started_at' => $attempt['started_at'],
                'total_allowed_seconds' => $totalAllowedSeconds,
                'remaining_seconds' => $remainingSeconds,
                'duration_minutes' => $durationMinutes,
                'summary' => [
                    'total_questions' => $totalQuestions,
                    'answered_count' => $answeredCount,
                    'unanswered_count' => max(0, $totalQuestions - $answeredCount),
                    'marked_count' => $markedCount
                ]
            ],
            'questions' => $orderedQuestions,
            'answers' => $userAnswers
        ];
    }

    /**
     * Submit an answer choice or toggle mark for a specific question in the attempt.
     */
    public function submitAnswer(int $userId, int $attemptId, int $questionId, ?string $selectedChoice, ?bool $isMarked, int $responseTime = 0): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or unauthorized.");
        }

        if ($attempt['status'] !== 'in_progress') {
            throw new RuntimeException("This examination attempt is already completed.");
        }

        $sessionNumber = (int) ($attempt['session_number'] ?? 1);

        // Validate server timer (3 hours = 180 minutes per subject)
        $durationMinutes = $this->getSessionDurationMinutes($sessionNumber);
        $totalAllowedSeconds = $durationMinutes * 60;
        $elapsedSeconds = isset($attempt['elapsed_seconds']) ? (int)$attempt['elapsed_seconds'] : max(0, time() - strtotime($attempt['started_at']));
        if ($elapsedSeconds > $totalAllowedSeconds) {
            $this->finishAttempt($userId, $attemptId, true);
            throw new RuntimeException("Examination time has expired.");
        }

        // Check that question belongs to this event
        $question = $this->questionModel->find($questionId);
        if (!$question || (int)$question['special_event_id'] !== (int)$attempt['special_event_id']) {
            throw new RuntimeException("Invalid question for this examination.");
        }

        if ($selectedChoice !== null) {
            $selectedChoice = strtoupper(trim($selectedChoice));
            if (!in_array($selectedChoice, ['A', 'B', 'C', 'D'])) {
                throw new RuntimeException("Invalid choice selected.");
            }
        }

        $bloomLevel = strtolower($question['bloom_level'] ?? 'apply');
        $correctChoice = strtoupper(trim($question['correct_choice'] ?? ''));
        $isCorrect = ($selectedChoice !== null && $selectedChoice === $correctChoice);

        $this->answerModel->saveAnswer($attemptId, $questionId, $selectedChoice, $isMarked, $responseTime, $isCorrect, $bloomLevel);

        $totalQuestionsTarget = ($sessionNumber === 2) ? 250 : 200;
        $summary = $this->answerModel->getSummary($attemptId, $totalQuestionsTarget);

        $response = [
            'success' => true,
            'summary' => $summary
        ];

        // Check immediate feedback setting (Section 52)
        $immediateFeedbackEnabled = $this->isImmediateFeedbackEnabled();
        if ($immediateFeedbackEnabled && $selectedChoice !== null) {
            $cognitiveSkills = [
                'remember' => 'Retrieve previously learned facts, terminology, definitions, and diagnostic criteria.',
                'understand' => 'Explain, interpret, and recognize underlying clinical meaning.',
                'apply' => 'Apply Study Hall psychiatric knowledge directly in practical clinical situations.',
                'analyze' => 'Analyze clinical information and distinguish between competing psychiatric possibilities.',
                'evaluate' => 'Judge competing clinical alternatives and determine the best-supported diagnosis.',
                'create' => 'Formulate and determine appropriate clinical intervention and management strategies.'
            ];

            $response['feedback'] = [
                'immediate' => true,
                'bloom_level' => strtoupper($bloomLevel),
                'cognitive_skill' => $cognitiveSkills[$bloomLevel] ?? 'Cognitive reasoning under Bloom taxonomy',
                'question_result' => $isCorrect ? 'Correct' : 'Incorrect',
                'is_correct' => $isCorrect
            ];
        } else {
            $response['feedback'] = [
                'immediate' => false
            ];
        }

        return $response;
    }

    private function isImmediateFeedbackEnabled(): bool {
        $stmt = $this->db->query("SELECT setting_value FROM special_event_settings WHERE setting_key = 'immediate_feedback'");
        $val = $stmt ? $stmt->fetchColumn() : null;
        return $val === 'true' || $val === '1';
    }

    /**
     * Toggle the review mark for a question.
     */
    public function toggleMark(int $userId, int $attemptId, int $questionId, bool $isMarked): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Unauthorized.");
        }

        $this->answerModel->toggleMark($attemptId, $questionId, $isMarked);
        return ['success' => true, 'is_marked' => $isMarked];
    }

    /**
     * Final submission of the examination: evaluates score, grants rewards, and generates breakdown.
     */
    public function finishAttempt(int $userId, int $attemptId, bool $isTimedOut = false): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or unauthorized.");
        }

        $eventId = (int) $attempt['special_event_id'];

        // Evaluate server score
        $eval = $this->scoringService->evaluateAttempt($attemptId, $eventId);

        $finalStatus = $isTimedOut ? 'timed_out' : 'completed';

        // Update attempt record
        $this->attemptModel->update($attemptId, [
            'status' => $finalStatus,
            'completed_at' => date('Y-m-d H:i:s'),
            'score' => $eval['score'],
            'correct_count' => $eval['correct_count'],
            'incorrect_count' => $eval['incorrect_count'],
            'percentage' => $eval['percentage'],
            'total_time' => $eval['total_time']
        ]);

        // Grant rewards
        $rewards = $this->rewardService->awardRewards($userId, $attemptId, $eval);

        return [
            'success' => true,
            'is_completed' => true,
            'attempt_id' => $attemptId,
            'status' => $finalStatus,
            'evaluation' => $eval,
            'rewards' => $rewards
        ];
    }

    /**
     * Terminate and disqualify an examination attempt due to proctoring violation (Cheating).
     */
    public function disqualifyAttempt(int $userId, int $attemptId, string $reason = 'Cheating'): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or unauthorized.");
        }

        $this->attemptModel->update($attemptId, [
            'status' => 'disqualified',
            'disqualification_reason' => $reason,
            'tab_switch_count' => 3,
            'completed_at' => date('Y-m-d H:i:s'),
            'score' => 0,
            'percentage' => 0.00,
            'xp_awarded' => 0,
            'coins_awarded' => 0
        ]);

        return [
            'success' => true,
            'disqualified' => true,
            'status' => 'disqualified',
            'reason' => $reason,
            'message' => 'Examination terminated due to anti-cheating detection: ' . $reason
        ];
    }

    /**
     * Increment the recorded tab switch count for an ongoing attempt.
     */
    public function recordTabSwitch(int $userId, int $attemptId, int $count = 1): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or unauthorized.");
        }

        $this->attemptModel->update($attemptId, [
            'tab_switch_count' => $count
        ]);

        return ['success' => true, 'tab_switch_count' => $count];
    }

    /**
     * Combined Full 450-item examination overview for an event.
     */
    public function getExamOverview(int $userId, int $eventId): ?array {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return null;
        }

        $s1 = $this->attemptModel->getUserEventAttempt($userId, $eventId, 1);
        $s2 = $this->attemptModel->getUserEventAttempt($userId, $eventId, 2);

        $s1Eval = ($s1 && in_array($s1['status'], ['completed', 'timed_out'])) ? $this->scoringService->evaluateAttempt((int)$s1['id'], $eventId) : null;
        $s2Eval = ($s2 && in_array($s2['status'], ['completed', 'timed_out'])) ? $this->scoringService->evaluateAttempt((int)$s2['id'], $eventId) : null;

        $totalQuestions = ($s1Eval ? $s1Eval['total_questions'] : 200) + ($s2Eval ? $s2Eval['total_questions'] : 250);
        $totalScore = ($s1Eval ? $s1Eval['score'] : 0) + ($s2Eval ? $s2Eval['score'] : 0);
        $totalIncorrect = ($s1Eval ? $s1Eval['incorrect_count'] : 0) + ($s2Eval ? $s2Eval['incorrect_count'] : 0);
        $totalPercentage = $totalQuestions > 0 ? round(($totalScore / $totalQuestions) * 100, 2) : 0;

        // Combined subject stats
        $combinedSubjects = [];
        if ($s1Eval && !empty($s1Eval['subject_stats'])) {
            foreach ($s1Eval['subject_stats'] as $subj => $data) {
                $combinedSubjects[$subj] = $data;
            }
        }
        if ($s2Eval && !empty($s2Eval['subject_stats'])) {
            foreach ($s2Eval['subject_stats'] as $subj => $data) {
                $combinedSubjects[$subj] = $data;
            }
        }

        return [
            'event_id' => $eventId,
            'event_title' => $event['title'],
            'event_date' => $event['event_date'],
            'is_fully_completed' => ($s1Eval !== null && $s2Eval !== null),
            'total_questions' => $totalQuestions,
            'total_score' => $totalScore,
            'total_incorrect' => $totalIncorrect,
            'percentage' => $totalPercentage,
            'passed' => $totalPercentage >= 75.0,
            'session_1' => $s1Eval,
            'session_2' => $s2Eval,
            'subject_stats' => $combinedSubjects,
            'blueprint' => MockExamBlueprint::getBlueprint()
        ];
    }

    public function getSessionDurationMinutes(int $sessionNumber = 1): int {
        return 180; // 3 hours per subject
    }
}
