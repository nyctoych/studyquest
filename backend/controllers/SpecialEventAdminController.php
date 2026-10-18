<?php
require_once __DIR__ . '/../services/SpecialEventGenerationService.php';
require_once __DIR__ . '/../models/SpecialEvent.php';
require_once __DIR__ . '/../models/SpecialEventQuestion.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class SpecialEventAdminController {
    private SpecialEventGenerationService $generationService;
    private SpecialEvent $eventModel;
    private SpecialEventQuestion $questionModel;

    public function __construct() {
        $this->generationService = new SpecialEventGenerationService();
        $this->eventModel = new SpecialEvent();
        $this->questionModel = new SpecialEventQuestion();
    }

    /**
     * POST /special-event/admin/generate
     */
    public function generate(): void {
        AuthMiddleware::check();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $date = !empty($body['date']) ? trim((string)$body['date']) : date('Y-m-d');
        $userId = isset($body['user_id']) ? (int)$body['user_id'] : (int)(AuthMiddleware::userId() ?? 0);
        $force = !empty($body['force']);

        try {
            $result = $this->generationService->generateDailyEvent($date, $userId, $force);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/admin/events
     */
    public function events(): void {
        AuthMiddleware::check();
        $events = $this->eventModel->getAll(50, 0);
        echo json_encode(['success' => true, 'events' => $events]);
    }

    /**
     * GET /special-event/admin/events/{id}/questions
     */
    public function questions(int $eventId): void {
        AuthMiddleware::check();
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            return;
        }

        $questions = $this->questionModel->getByEventId($eventId);
        echo json_encode([
            'success' => true,
            'event' => $event,
            'question_count' => count($questions),
            'questions' => $questions
        ]);
    }

    /**
     * POST /special-event/admin/questions/{id}/approve
     */
    public function approveQuestion(int $questionId): void {
        AuthMiddleware::check();
        $this->questionModel->updateReviewStatus($questionId, 'approved');
        echo json_encode(['success' => true, 'message' => 'Question marked as approved']);
    }

    /**
     * POST /special-event/admin/questions/{id}/reject
     */
    public function rejectQuestion(int $questionId): void {
        AuthMiddleware::check();
        $this->questionModel->updateReviewStatus($questionId, 'rejected');
        echo json_encode(['success' => true, 'message' => 'Question marked as rejected']);
    }

    /**
     * POST /special-event/admin/questions/{id}/replace
     */
    public function replaceQuestion(int $questionId): void {
        AuthMiddleware::check();
        try {
            $result = $this->generationService->replaceQuestion($questionId);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /special-event/admin/events/{id}/publish
     */
    public function publishEvent(int $eventId): void {
        AuthMiddleware::check();
        $count = $this->eventModel->countQuestions($eventId);
        $approvedCount = $this->eventModel->countApprovedQuestions($eventId);

        if ($count < 1 || $approvedCount !== $count) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Cannot publish: Event has {$count} questions ({$approvedCount} approved). All generated questions must be approved before publishing."
            ]);
            return;
        }

        $this->eventModel->update($eventId, [
            'status' => 'published',
            'published_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode(['success' => true, 'message' => 'Event published successfully!']);
    }

    /**
     * GET /special-event/admin/settings
     */
    public function getSettings(): void {
        AuthMiddleware::check();
        $db = Database::getInstance();
        $rows = $db->query("SELECT setting_key, setting_value, description FROM special_event_settings")->fetchAll();
        $settings = [];
        foreach ($rows as $r) {
            $val = $r['setting_value'];
            $decoded = json_decode($val, true);
            $settings[$r['setting_key']] = [
                'value' => (json_last_error() === JSON_ERROR_NONE) ? $decoded : $val,
                'description' => $r['description']
            ];
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
    }

    /**
     * POST /special-event/admin/settings
     */
    public function updateSettings(): void {
        AuthMiddleware::check();
        $db = Database::getInstance();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $db->prepare("
            INSERT INTO special_event_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($body as $key => $val) {
            $valStr = is_array($val) ? json_encode($val) : (string)$val;
            $stmt->execute([$key, $valStr]);
        }

        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    }
}
