<?php
require_once __DIR__ . '/../models/Question.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/GeminiService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class QuestionController {
    private Question $questionModel;
    private GeminiService $geminiService;

    public function __construct() {
        $this->questionModel = new Question();
        $this->geminiService = new GeminiService();
    }

    /**
     * GET /questions/categories
     */
    public function categories(): void {
        AuthMiddleware::check();
        $categories = $this->questionModel->getCategories();
        echo json_encode(['success' => true, 'categories' => $categories]);
    }

    /**
     * GET /questions/topics?category_id=...
     */
    public function topics(): void {
        AuthMiddleware::check();
        $categoryId = (int) ($_GET['category_id'] ?? 1);
        $topics = $this->questionModel->getTopics($categoryId);
        echo json_encode(['success' => true, 'topics' => $topics]);
    }

    /**
     * GET /questions/adaptive-profile
     * Fetch user's current demonstrated Psychology mastery and adaptive difficulty.
     */
    public function adaptiveProfile(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $profile = $this->questionModel->getAdaptiveProfile($userId);
        echo json_encode(['success' => true, 'data' => $profile, 'profile' => $profile]);
    }

    /**
     * GET /questions/practice
     * Adaptive level-based psychology practice and board-exam questions.
     */
    public function practice(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $count = min(25, max(1, (int) ($_GET['count'] ?? 10)));
        $categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int) $_GET['category_id'] : null;
        $topicId = isset($_GET['topic_id']) && is_numeric($_GET['topic_id']) ? (int) $_GET['topic_id'] : null;
        $mode = in_array($_GET['mode'] ?? '', ['board_exam', 'practice']) ? $_GET['mode'] : 'practice';

        $result = $this->questionModel->getAdaptiveQuestionsForUser($userId, $count, $categoryId, $topicId, $mode);
        $rawQuestions = $result['questions'] ?? [];
        $adaptiveInfo = $result['adaptive_info'] ?? [];

        // Format choices uniformly and hide correct_answer
        $formatted = array_map(function($q) {
            return [
                'id'               => $q['id'],
                'category_id'      => $q['category_id'],
                'topic_id'         => $q['topic_id'] ?? null,
                'category_name'    => $q['category_name'] ?? '',
                'category_icon'    => $q['category_icon'] ?? '🧠',
                'category_color'   => $q['category_color'] ?? '#6366f1',
                'topic_name'       => $q['topic_name'] ?? 'Core Concepts',
                'question_text'    => $q['question'],
                'difficulty'       => $q['difficulty'],
                'explanation'      => $q['explanation'],
                'source_reference' => $q['source_reference'] ?? null,
                'choices'          => [
                    ['key' => 'A', 'text' => $q['choice_a']],
                    ['key' => 'B', 'text' => $q['choice_b']],
                    ['key' => 'C', 'text' => $q['choice_c']],
                    ['key' => 'D', 'text' => $q['choice_d']],
                ]
            ];
        }, $rawQuestions);

        echo json_encode([
            'success'       => true,
            'questions'     => $formatted,
            'count'         => count($formatted),
            'adaptive_info' => $adaptiveInfo,
        ]);
    }

    /**
     * POST /questions/attempt
     * Submit an answer to a question and update adaptive learning profile.
     */
    public function submitAttempt(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];

        $questionId   = (int) ($input['question_id'] ?? 0);
        $selectedKey  = strtolower(trim($input['selected_answer'] ?? ''));
        $responseTime = max(0, (int) ($input['response_time'] ?? 0));
        $battleId     = $input['battle_id'] ?? null;

        if (!$questionId || !in_array($selectedKey, ['a', 'b', 'c', 'd'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid question ID or answer key.']);
            return;
        }

        // Fetch actual question
        $stmt = Database::getInstance()->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $q = $stmt->fetch();

        if (!$q) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Question not found.']);
            return;
        }

        $isCorrect = ($selectedKey === strtolower($q['correct_answer']));

        // Record attempt in database and update adaptive profile
        $attemptResult = $this->questionModel->recordAttempt($userId, $questionId, $isCorrect, $responseTime, $battleId);

        // Calculate XP and point reward based on difficulty tier
        $xpEarned = $isCorrect ? match($q['difficulty']) {
            'beginner'     => 15,
            'intermediate' => 25,
            'advanced'     => 40,
            'board_exam'   => 60,
            default        => 20
        } : 5; // small consolation XP for practicing

        $pointsEarned = $isCorrect ? match($q['difficulty']) {
            'beginner'     => 5,
            'intermediate' => 10,
            'advanced'     => 15,
            'board_exam'   => 25,
            default        => 5
        } : 0;

        $userModel = new User();
        $userModel->addXP($userId, $xpEarned);
        if ($pointsEarned > 0) {
            $userModel->addPoints($userId, $pointsEarned);
        }

        echo json_encode([
            'success'             => true,
            'is_correct'          => $isCorrect,
            'correct_answer'      => strtoupper($q['correct_answer']),
            'explanation'         => $q['explanation'],
            'source_reference'    => $q['source_reference'] ?? null,
            'xp_earned'           => $xpEarned,
            'points_earned'       => $pointsEarned,
            'attempt'             => $attemptResult,
            'mastery_score'       => $attemptResult['mastery_score'],
            'adaptive_difficulty' => $attemptResult['adaptive_difficulty'],
            'adaptive_state'      => $attemptResult['adaptive_state'],
            'streak'              => $attemptResult['current_streak'],
            'user_explanation'    => $attemptResult['user_explanation'],
        ]);
    }

    /**
     * GET /questions/session-summary
     * Calculate summary of completed practice session.
     */
    public function sessionSummary(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $count = min(30, max(1, (int) ($_GET['count'] ?? 10)));

        $service = new AdaptiveDifficultyService();
        $attempts = $service->getRecentAttempts($userId, $count);
        $recentPerf = $service->calculateRecentPerformance($attempts);
        $readiness = $service->getFullReadinessProfile($userId);

        echo json_encode([
            'success' => true,
            'summary' => [
                'questions_answered'    => $recentPerf['count'],
                'questions_correct'     => $recentPerf['correct'],
                'questions_incorrect'   => $recentPerf['incorrect'],
                'accuracy'              => $recentPerf['accuracy'],
                'avg_response_time'     => $recentPerf['avg_response_time'],
                'current_level'         => $readiness['user_level'],
                'adaptive_difficulty'   => $readiness['adaptive_difficulty'],
                'adaptive_state'        => $readiness['adaptive_state'],
                'mastery_score'         => $readiness['mastery_score'],
                'strongest_category'    => $readiness['strongest_category'] ? $readiness['strongest_category']['category_name'] : null,
                'strongest_cat_acc'     => $readiness['strongest_category'] ? $readiness['strongest_category']['accuracy'] : null,
                'weakest_category'      => $readiness['weakest_category'] ? $readiness['weakest_category']['category_name'] : null,
                'weakest_cat_acc'       => $readiness['weakest_category'] ? $readiness['weakest_category']['accuracy'] : null,
                'strongest_topic'       => $readiness['strongest_topic'] ? $readiness['strongest_topic']['topic_name'] : null,
                'weakest_topic'         => $readiness['weakest_topic'] ? $readiness['weakest_topic']['topic_name'] : null,
                'recommendation'        => $readiness['recommendation'],
                'user_explanation'      => $readiness['user_explanation'],
                'study_url'             => $readiness['study_url'] ?? '/study',
                'weakest_domain_id'     => $readiness['weakest_domain_id'] ?? null,
                'weakest_domain_name'   => $readiness['weakest_domain_name'] ?? null,
                'weakest_part_number'   => $readiness['weakest_part_number'] ?? null,
                'weakest_part_title'    => $readiness['weakest_part_title'] ?? null,
            ]
        ]);
    }

    /**
     * GET /questions/weak-areas
     */
    public function weakAreas(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $data = $this->questionModel->getUserWeakAreas($userId);
        echo json_encode(['success' => true, 'data' => $data]);
    }

    /**
     * GET /questions/analytics
     */
    public function analytics(): void {
        AuthMiddleware::check();
        $userId = AuthMiddleware::userId();
        $stats = $this->questionModel->getAnalytics($userId);
        $weak = $this->questionModel->getUserWeakAreas($userId);
        $profile = $this->questionModel->getAdaptiveProfile($userId);
        echo json_encode([
            'success'    => true, 
            'analytics'  => $stats, 
            'categories' => $weak['all_categories'],
            'profile'    => $profile
        ]);
    }

    /**
     * POST /questions/generate-ai
     */
    public function generateAI(): void {
        AuthMiddleware::check();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $result = $this->geminiService->generatePsychologyQuestion($input);
        echo json_encode($result);
    }

    /**
     * POST /questions/{id}/review
     */
    public function review(int $questionId): void {
        AuthMiddleware::check();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $input['status'] ?? 'approved';

        $success = $this->questionModel->updateStatus($questionId, $status);
        echo json_encode(['success' => $success, 'message' => "Question status updated to {$status}."]);
    }
}
