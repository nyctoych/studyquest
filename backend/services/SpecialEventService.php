<?php
require_once __DIR__ . '/../config/database.php';
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
     * Get or ensure today's published Special Event, including user's attempt status if authenticated.
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
                'message' => 'Today\'s Special Event is currently preparing. Check back soon.'
            ];
        }

        $eventId = (int) $event['id'];
        $questionCount = $this->eventModel->countQuestions($eventId);

        $attemptInfo = null;
        if ($userId) {
            $attempt = $this->attemptModel->getUserEventAttempt($userId, $eventId);
            if ($attempt) {
                $summary = $this->answerModel->getSummary((int)$attempt['id'], $questionCount);
                $durationMinutes = $this->getExamDurationMinutes();
                $elapsedSeconds = time() - strtotime($attempt['started_at']);
                $totalAllowedSeconds = $durationMinutes * 60;
                $remainingSeconds = max(0, $totalAllowedSeconds - $elapsedSeconds);

                // Auto-complete if expired during in_progress
                if ($attempt['status'] === 'in_progress' && $remainingSeconds <= 0) {
                    $this->finishAttempt($userId, (int)$attempt['id'], true);
                    $attempt = $this->attemptModel->find((int)$attempt['id']);
                }

                $attemptInfo = [
                    'id' => (int) $attempt['id'],
                    'status' => $attempt['status'],
                    'started_at' => $attempt['started_at'],
                    'completed_at' => $attempt['completed_at'],
                    'score' => (int) $attempt['score'],
                    'percentage' => (float) $attempt['percentage'],
                    'remaining_seconds' => $remainingSeconds,
                    'summary' => $summary
                ];
            }
        }

        $assignedDomains = [];
        if (!empty($event['assigned_domains'])) {
            $assignedDomains = is_string($event['assigned_domains']) ? json_decode($event['assigned_domains'], true) : $event['assigned_domains'];
        }

        return [
            'has_event' => true,
            'event' => [
                'id' => $eventId,
                'user_id' => (int) ($event['user_id'] ?? 0),
                'title' => $event['title'],
                'description' => $event['description'],
                'event_date' => $event['event_date'],
                'question_count' => $questionCount,
                'assigned_domains' => $assignedDomains,
                'status' => $event['status'],
                'duration_minutes' => $this->getExamDurationMinutes(),
                'published_at' => $event['published_at'],
                'expires_at' => $event['expires_at']
            ],
            'user_attempt' => $attemptInfo
        ];
    }

    /**
     * Start a new authoritative examination attempt for the user.
     */
    public function startAttempt(int $userId, int $eventId): array {
        $event = $this->eventModel->find($eventId);
        if (!$event || $event['status'] !== 'published') {
            throw new RuntimeException("Special Event is not available for examination.");
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

        // Check if an attempt already exists
        $existing = $this->attemptModel->getUserEventAttempt($userId, $eventId);
        if ($existing) {
            if ($existing['status'] === 'in_progress') {
                return [
                    'success' => true,
                    'message' => 'Resuming active trial attempt',
                    'attempt_id' => (int) $existing['id'],
                    'is_resumed' => true
                ];
            } else {
                throw new RuntimeException("You have already completed today's Special Event examination.");
            }
        }

        // Fetch all questions for this event
        $questions = $this->questionModel->getByEventId($eventId, true);
        if (count($questions) < 140) {
            throw new RuntimeException("Special Event does not have the required 140 questions.");
        }

        // Create snapshot randomized question ordering
        $questionIds = array_column($questions, 'id');
        shuffle($questionIds);

        $attemptId = $this->attemptModel->create([
            'special_event_id' => $eventId,
            'user_id' => $userId,
            'question_order' => $questionIds
        ]);

        return [
            'success' => true,
            'message' => 'Examination trial initialized',
            'attempt_id' => $attemptId,
            'is_resumed' => false
        ];
    }

    /**
     * Get the active attempt state including safe questions, timer, and current answers.
     */
    public function getAttemptState(int $userId, int $attemptId): array {
        $attempt = $this->attemptModel->find($attemptId);
        if (!$attempt || (int)$attempt['user_id'] !== $userId) {
            throw new RuntimeException("Attempt not found or access denied.");
        }

        $eventId = (int) $attempt['special_event_id'];
        $event = $this->eventModel->find($eventId);

        $durationMinutes = $this->getExamDurationMinutes();
        $totalAllowedSeconds = $durationMinutes * 60;
        $elapsedSeconds = time() - strtotime($attempt['started_at']);
        $remainingSeconds = max(0, $totalAllowedSeconds - $elapsedSeconds);

        // Check if timer expired while exam was in progress
        if ($attempt['status'] === 'in_progress' && $remainingSeconds <= 0) {
            return $this->finishAttempt($userId, $attemptId, true);
        }

        // Fetch questions and order them by the snapshot question_order
        $allQuestions = $this->questionModel->getByEventId($eventId);
        $keyedQuestions = [];
        foreach ($allQuestions as $q) {
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
                'event_title' => $attempt['event_title'],
                'event_date' => $attempt['event_date'],
                'status' => $attempt['status'],
                'started_at' => $attempt['started_at'],
                'total_allowed_seconds' => $totalAllowedSeconds,
                'remaining_seconds' => $remainingSeconds,
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

        // Validate server timer
        $durationMinutes = $this->getExamDurationMinutes();
        $totalAllowedSeconds = $durationMinutes * 60;
        $elapsedSeconds = time() - strtotime($attempt['started_at']);
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

        $summary = $this->answerModel->getSummary($attemptId, 140);

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

    private function getExamDurationMinutes(): int {
        $stmt = $this->db->prepare("SELECT setting_value FROM special_event_settings WHERE setting_key = 'exam_duration_minutes'");
        $stmt->execute();
        $val = $stmt->fetchColumn();
        return $val ? (int)$val : 140;
    }
}
