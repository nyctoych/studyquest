<?php
require_once __DIR__ . '/../services/SpecialEventService.php';
require_once __DIR__ . '/../services/SpecialEventAnalyticsService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class SpecialEventController {
    private SpecialEventService $service;
    private SpecialEventAnalyticsService $analyticsService;

    public function __construct() {
        $this->service = new SpecialEventService();
        $this->analyticsService = new SpecialEventAnalyticsService();
    }

    /**
     * GET /special-event/today
     */
    public function today(): void {
        $userId = AuthMiddleware::isLoggedIn() ? AuthMiddleware::userId() : null;
        try {
            $data = $this->service->getTodayEvent($userId);
            echo json_encode(array_merge(['success' => true], $data));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /special-event/{id}/start
     */
    public function start(int $eventId): void {
        $userId = AuthMiddleware::userId();
        try {
            $result = $this->service->startAttempt($userId, $eventId);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/attempts/{attemptId}
     */
    public function attempt(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        try {
            $data = $this->service->getAttemptState($userId, $attemptId);
            echo json_encode($data);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /special-event/attempts/{attemptId}/answer
     */
    public function submitAnswer(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $questionId = (int) ($body['question_id'] ?? 0);
        $selectedChoice = isset($body['selected_choice']) ? (string)$body['selected_choice'] : null;
        $isMarked = isset($body['is_marked']) ? (bool)$body['is_marked'] : null;
        $responseTime = (int) ($body['response_time'] ?? 0);

        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing question_id']);
            return;
        }

        try {
            $result = $this->service->submitAnswer($userId, $attemptId, $questionId, $selectedChoice, $isMarked, $responseTime);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /special-event/attempts/{attemptId}/mark
     */
    public function toggleMark(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $questionId = (int) ($body['question_id'] ?? 0);
        $isMarked = (bool) ($body['is_marked'] ?? true);

        if (!$questionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing question_id']);
            return;
        }

        try {
            $result = $this->service->toggleMark($userId, $attemptId, $questionId, $isMarked);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /special-event/attempts/{attemptId}/submit
     */
    public function finish(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        try {
            $result = $this->service->finishAttempt($userId, $attemptId);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/history
     */
    public function history(): void {
        $userId = AuthMiddleware::userId();
        try {
            $history = $this->analyticsService->getUserHistory($userId);
            echo json_encode(['success' => true, 'history' => $history]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/attempts/{attemptId}/review
     */
    public function review(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        try {
            $review = $this->analyticsService->getAttemptReview($attemptId, $userId);
            if (!$review) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Review not found or trial still in progress.']);
                return;
            }
            echo json_encode(array_merge(['success' => true], $review));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/attempts/{attemptId}/bloom
     * Returns 6-level Bloom breakdown, cognitive profile, Study Hall recommendations, and error analysis.
     */
    public function bloom(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        try {
            $bloomData = $this->analyticsService->getAttemptBloomAnalytics($attemptId, $userId);
            if (!$bloomData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bloom analytics not found or unauthorized.']);
                return;
            }
            echo json_encode(array_merge(['success' => true], $bloomData));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/bloom/history
     * Returns historical Bloom performance across completed attempts for the authenticated user.
     */
    public function bloomHistory(): void {
        $userId = AuthMiddleware::userId();
        try {
            $history = $this->analyticsService->getUserBloomHistory($userId);
            echo json_encode(['success' => true, 'history' => $history]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /special-event/attempts/{attemptId}/bloom/domains
     * Returns Bloom performance cross-tabulated by psychiatric domain.
     */
    public function bloomDomains(int $attemptId): void {
        $userId = AuthMiddleware::userId();
        try {
            $bloomData = $this->analyticsService->getAttemptBloomAnalytics($attemptId, $userId);
            if (!$bloomData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bloom domain analytics not found or unauthorized.']);
                return;
            }
            echo json_encode([
                'success' => true,
                'attempt_id' => $attemptId,
                'domains' => $bloomData['domain_breakdown'] ?? []
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
