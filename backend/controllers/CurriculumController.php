<?php
require_once __DIR__ . '/../services/CurriculumService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CurriculumController {
    private CurriculumService $service;

    public function __construct() {
        $this->service = new CurriculumService();
    }

    public function domains(): void {
        $userId = AuthMiddleware::userId();
        $result = $this->service->getDomains($userId);
        echo json_encode([
            'success' => true,
            'domains' => $result['domains'],
            'summary' => $result['summary']
        ]);
    }

    public function domain(int $id): void {
        $userId = AuthMiddleware::userId();
        $domain = $this->service->getDomainDetails($userId, $id);

        if (!$domain) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Psychology domain not found']);
            return;
        }

        echo json_encode(['success' => true, 'domain' => $domain]);
    }

    public function part(int $id): void {
        $userId = AuthMiddleware::userId();
        $part = $this->service->getPartDetails($userId, $id);

        if (!$part) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Learning part not found']);
            return;
        }

        if (isset($part['error']) && $part['error'] === 'locked') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'locked' => true,
                'message' => $part['message'],
                'part_number' => $part['part_number'],
                'domain_name' => $part['domain_name']
            ]);
            return;
        }

        echo json_encode(['success' => true, 'part' => $part]);
    }

    public function submitPart(int $id): void {
        $userId = AuthMiddleware::userId();
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $answers = $data['answers'] ?? [];
        $timeSpent = (int)($data['time_spent'] ?? 0);

        try {
            $result = $this->service->submitPartAssessment($userId, $id, $answers, $timeSpent);
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function overview(): void {
        $userId = AuthMiddleware::userId();
        $data = $this->service->getOverview($userId);
        echo json_encode(array_merge(['success' => true], $data));
    }

    public function today(): void {
        $userId = AuthMiddleware::userId();
        $rec = $this->service->getTodayRecommendation($userId);
        echo json_encode([
            'success' => true,
            'recommendation' => $rec
        ]);
    }
}
