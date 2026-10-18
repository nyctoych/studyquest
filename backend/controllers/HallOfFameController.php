<?php
require_once __DIR__ . '/../services/HallOfFameService.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../config/database.php';

class HallOfFameController {
    private HallOfFameService $service;

    public function __construct() {
        $this->service = new HallOfFameService();
    }

    /**
     * GET /hall-of-fame/announcements
     * Public endpoint for live animated announcement scroll.
     */
    public function announcements(): void {
        $batchId = isset($_GET['batch_id']) && is_numeric($_GET['batch_id']) ? (int)$_GET['batch_id'] : null;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 100;

        try {
            $announcements = $this->service->getPublicAnnouncements($batchId, $limit);
            echo json_encode([
                'success' => true,
                'count' => count($announcements),
                'announcements' => $announcements
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /hall-of-fame/latest-passers
     * Real-time endpoint for physical world scroll ticker displaying latest board exam passers 1-by-1.
     */
    public function latestPassers(): void {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
        try {
            $data = $this->service->getLatestBoardPassers($limit);
            echo json_encode(array_merge(['success' => true], $data));
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /hall-of-fame/batches
     * Public endpoint for supported PRC examination batches and official sources.
     */
    public function batches(): void {
        try {
            $batches = $this->service->getBatches();
            echo json_encode([
                'success' => true,
                'batches' => $batches
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /hall-of-fame/my-claims
     * Authenticated endpoint for viewing the user's passing claims.
     */
    public function myClaims(): void {
        $userId = AuthMiddleware::userId();
        try {
            $claims = $this->service->getUserClaims($userId);
            echo json_encode([
                'success' => true,
                'claims' => $claims
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /hall-of-fame/claim
     * Authenticated endpoint to submit a board examination passing claim.
     */
    public function submitClaim(): void {
        $userId = AuthMiddleware::userId();
        $body = json_decode(file_get_contents('php://input'), true) ?: [];

        $batchId = isset($body['batch_id']) ? (int)$body['batch_id'] : 0;
        $firstName = isset($body['first_name']) ? trim((string)$body['first_name']) : '';
        $middleName = isset($body['middle_name']) ? trim((string)$body['middle_name']) : '';
        $lastName = isset($body['last_name']) ? trim((string)$body['last_name']) : '';
        $submittedName = isset($body['submitted_full_name']) ? trim((string)$body['submitted_full_name']) : '';

        // Format into standard PRC roster format: LAST NAME, FIRST NAME MIDDLE NAME
        if ($lastName !== '' && $firstName !== '') {
            $submittedName = strtoupper($lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . $middleName : ''));
        } elseif ($submittedName !== '' && str_contains($submittedName, ',')) {
            $parts = explode(',', $submittedName, 2);
            $lastName = trim($parts[0]);
            $tokens = preg_split('/\s+/', trim($parts[1]));
            if (!empty($tokens)) {
                $firstName = $tokens[0];
                if (count($tokens) > 1) {
                    $middleName = end($tokens);
                }
            }
        }

        $displayPreference = isset($body['display_preference']) ? trim((string)$body['display_preference']) : 'MASKED';
        $consentConfirmed = !empty($body['consent_confirmed']);

        if ($batchId <= 0 || $submittedName === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please provide an examination batch, first name, and last name.']);
            return;
        }

        try {
            $res = $this->service->submitClaim($userId, $batchId, $submittedName, $displayPreference, $consentConfirmed, $firstName, $middleName, $lastName);
            if (!$res['success']) {
                http_response_code(422);
            }
            echo json_encode($res);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * GET /hall-of-fame/admin/queue
     * Admin endpoint: review pending examinee claims.
     */
    public function adminQueue(): void {
        $this->requireAdmin();
        $status = $_GET['status'] ?? 'ALL';

        try {
            $queue = $this->service->getAdminQueue($status);
            echo json_encode([
                'success' => true,
                'count' => count($queue),
                'queue' => $queue
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /hall-of-fame/admin/decide
     * Admin endpoint: approve, reject, or request correction on a claim.
     */
    public function adminDecide(): void {
        $adminId = $this->requireAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?: [];

        $claimId = isset($body['claim_id']) ? (int)$body['claim_id'] : 0;
        $decision = isset($body['decision']) ? trim((string)$body['decision']) : '';
        $notes = isset($body['notes']) ? trim((string)$body['notes']) : null;

        if ($claimId <= 0 || !in_array(strtoupper($decision), ['APPROVE', 'REJECT', 'NEEDS_CORRECTION'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid claim ID or decision.']);
            return;
        }

        try {
            $res = $this->service->decideClaim($adminId, $claimId, $decision, $notes);
            echo json_encode($res);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * POST /hall-of-fame/admin/import
     * Admin endpoint: import official PRC examinees text roster.
     */
    public function adminImport(): void {
        $adminId = $this->requireAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?: [];

        $batchId = isset($body['batch_id']) ? (int)$body['batch_id'] : 0;
        $rawText = isset($body['raw_roster']) ? (string)$body['raw_roster'] : '';

        if ($batchId <= 0 || trim($rawText) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Batch ID and official examinee roster text are required.']);
            return;
        }

        try {
            $res = $this->service->importRoster($adminId, $batchId, $rawText);
            echo json_encode($res);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Helper to verify administrative permissions.
     */
    private function requireAdmin(): int {
        $userId = AuthMiddleware::userId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized user.']);
            exit;
        }

        $isAdmin = (
            $user['username'] === 'admin' ||
            str_contains(strtolower($user['email']), 'admin') ||
            in_array((int)$user['id'], [1, 4], true)
        );

        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden. Administrator privileges required.']);
            exit;
        }

        return $userId;
    }
}
