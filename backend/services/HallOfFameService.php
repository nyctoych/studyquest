<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/HallOfFameMigration.php';

class HallOfFameService {
    private PDO $db;

    public function __construct() {
        HallOfFameMigration::run();
        $this->db = Database::getInstance();
    }

    /**
     * Standardized name normalization for conservative matching.
     * Strips punctuation, collapses whitespace, converts to uppercase.
     */
    public static function normalizeName(string $name): string {
        $name = mb_strtoupper(trim($name), 'UTF-8');
        // Remove periods and commas
        $name = str_replace(['.', ','], ' ', $name);
        // Replace multiple whitespace with a single space
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * Consistent Privacy Masking Algorithm.
     * Mask middle characters of each name token:
     * e.g. "JUAN DELA CRUZ" -> "J**N D**A C**Z"
     * e.g. "MARIA CLARA BAUTISTA SANTOS" -> "M***A C***A B******A S****S"
     */
    public static function maskName(string $fullName): string {
        $fullName = trim($fullName);
        if ($fullName === '') return '';

        $parts = preg_split('/(\s+|-)/', $fullName, -1, PREG_SPLIT_DELIM_CAPTURE);
        $maskedParts = [];

        foreach ($parts as $part) {
            if (trim($part) === '' || $part === '-' || preg_match('/^[,\.]$/', $part)) {
                $maskedParts[] = $part;
                continue;
            }

            $clean = trim($part, ".,");
            $len = mb_strlen($clean, 'UTF-8');

            if ($len <= 2) {
                $masked = mb_substr($clean, 0, 1, 'UTF-8') . '*';
            } elseif ($len === 3) {
                $masked = mb_substr($clean, 0, 1, 'UTF-8') . '*' . mb_substr($clean, -1, 1, 'UTF-8');
            } elseif ($len === 4) {
                // Exactly matches "JUAN" -> "J**N", "DELA" -> "D**A", "CRUZ" -> "C**Z"
                $masked = mb_substr($clean, 0, 1, 'UTF-8') . '**' . mb_substr($clean, -1, 1, 'UTF-8');
            } else {
                $masked = mb_substr($clean, 0, 1, 'UTF-8') . str_repeat('*', $len - 2) . mb_substr($clean, -1, 1, 'UTF-8');
            }

            if (str_ends_with($part, '.')) $masked .= '.';
            if (str_ends_with($part, ',')) $masked .= ',';

            $maskedParts[] = $masked;
        }

        return implode('', $maskedParts);
    }

    /**
     * Get all public active announcements for the live announcement scroll.
     * Strictly protects privacy: only display_name is sent (never raw unmasked name for MASKED claims).
     */
    public function getPublicAnnouncements(?int $batchId = null, int $limit = 100): array {
        $sql = "
            SELECT 
                a.id,
                a.claim_id,
                a.batch_id,
                a.display_name,
                a.display_preference,
                a.title_credential,
                a.announcement_format,
                a.is_featured,
                a.published_at,
                u.username,
                u.nickname,
                COALESCE(u.selected_character_id, 1) AS selected_character_id,
                u.avatar_style,
                u.avatar_seed,
                COALESCE(ch.class_type, 'MAGE') AS class_type,
                ch.profile_avatar_path,
                p.school,
                p.rank_num,
                b.batch_name,
                b.exam_name,
                b.exam_month,
                b.exam_year,
                b.official_source_url,
                b.official_document_url,
                b.publication_date
            FROM hall_of_fame_announcements a
            JOIN hall_of_fame_batches b ON a.batch_id = b.id
            LEFT JOIN hall_of_fame_claims c ON a.claim_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN characters ch ON COALESCE(u.selected_character_id, 1) = ch.id
            LEFT JOIN hall_of_fame_official_passers p ON c.official_passer_id = p.id
            WHERE a.is_active = 1 AND b.exam_year = 2026
        ";

        $params = [];
        if ($batchId !== null && $batchId > 0) {
            $sql .= " AND a.batch_id = ?";
            $params[] = $batchId;
        }

        $sql .= " ORDER BY a.is_featured DESC, a.published_at DESC LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get list of supported official examination batches and official PRC sources.
     */
    public function getBatches(): array {
        $stmt = $this->db->query("
            SELECT 
                b.id,
                b.exam_name,
                b.exam_code,
                b.batch_name,
                b.batch_code,
                b.exam_month,
                b.exam_year,
                b.official_source_url,
                b.official_document_url,
                b.publication_date,
                b.status,
                b.source_status,
                b.total_passers,
                b.total_examinees,
                b.passing_percentage,
                (SELECT COUNT(*) FROM hall_of_fame_announcements a WHERE a.batch_id = b.id AND a.is_active = 1) AS verified_announcements_count
            FROM hall_of_fame_batches b
            WHERE b.status = 'active' AND b.exam_year = 2026
            ORDER BY b.exam_year DESC, b.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get real-time verified board passers for the latest board exam.
     * Combines approved user claims and official PRC passers into a live ticker stream.
     */
    public function getLatestBoardPassers(int $limit = 50): array {
        // 1. Fetch the latest active batch
        $stmt = $this->db->query("
            SELECT id, exam_name, exam_code, batch_name, batch_code, exam_month, exam_year, official_source_url, total_passers
            FROM hall_of_fame_batches
            WHERE status = 'active' AND exam_year = 2026
            ORDER BY exam_year DESC, exam_month DESC, id DESC
            LIMIT 1
        ");
        $latestBatch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$latestBatch) {
            return ['batch' => null, 'passers' => [], 'total_count' => 0];
        }

        $batchId = (int)$latestBatch['id'];

        // 2. Fetch all approved registered user announcements for this batch
        $stmt = $this->db->prepare("
            SELECT 
                a.id AS announcement_id,
                a.display_name,
                a.display_preference,
                a.title_credential,
                a.announcement_format,
                a.is_featured,
                u.username,
                u.nickname,
                p.school,
                p.rank_num,
                1 AS is_verified_user
            FROM hall_of_fame_announcements a
            LEFT JOIN hall_of_fame_claims c ON a.claim_id = c.id
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN hall_of_fame_official_passers p ON c.official_passer_id = p.id
            WHERE a.is_active = 1 AND a.batch_id = ?
            ORDER BY a.is_featured DESC, a.published_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $batchId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $userAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: If latest batch has no verified registered claims yet, show verified claims from other batches
        if (empty($userAnnouncements)) {
            $stmt = $this->db->prepare("
                SELECT 
                    a.id AS announcement_id,
                    a.display_name,
                    a.display_preference,
                    a.title_credential,
                    a.announcement_format,
                    a.is_featured,
                    u.username,
                    u.nickname,
                    p.school,
                    p.rank_num,
                    1 AS is_verified_user,
                    b.batch_name
                FROM hall_of_fame_announcements a
                JOIN hall_of_fame_batches b ON a.batch_id = b.id
                LEFT JOIN hall_of_fame_claims c ON a.claim_id = c.id
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN hall_of_fame_official_passers p ON c.official_passer_id = p.id
                WHERE a.is_active = 1
                ORDER BY a.is_featured DESC, a.published_at DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $userAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $passersList = [];

        // STRICT: Only include registered StudyQuest users who submitted and were approved
        foreach ($userAnnouncements as $ua) {
            $username = $ua['username'] ?: ($ua['nickname'] ?: 'player');
            $passersList[] = [
                'id' => 'user-' . $ua['announcement_id'],
                'name' => $ua['display_name'],
                'username' => $username,
                'credential' => $ua['title_credential'] ?: 'RPm',
                'format' => $ua['announcement_format'] ?: ($ua['display_name'] . ', ' . ($ua['title_credential'] ?: 'RPm')),
                'rank_num' => $ua['rank_num'],
                'rank_badge' => $ua['rank_num'] ? ('TOP ' . $ua['rank_num']) : null,
                'school' => $ua['school'] ?: 'PRC BLEPP Board Passer',
                'is_verified_user' => true,
                'batch_name' => $ua['batch_name'] ?? $latestBatch['batch_name']
            ];
        }

        return [
            'batch' => $latestBatch,
            'passers' => $passersList,
            'total_count' => count($passersList)
        ];
    }

    /**
     * Retrieve authenticated user's current passing claim and verification status.
     */
    public function getUserClaims(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT 
                c.id,
                c.batch_id,
                c.submitted_full_name,
                c.display_preference,
                c.masked_name_preview,
                c.match_status,
                c.match_confidence,
                c.identity_status,
                c.claim_status,
                c.consent_confirmed,
                c.review_notes,
                c.reviewed_at,
                c.created_at,
                c.updated_at,
                b.batch_name,
                b.exam_name,
                b.exam_month,
                b.exam_year,
                b.official_source_url,
                p.school,
                p.rank_num,
                p.rating_pct,
                a.id AS announcement_id,
                a.is_active AS announcement_active,
                a.announcement_format
            FROM hall_of_fame_claims c
            JOIN hall_of_fame_batches b ON c.batch_id = b.id
            LEFT JOIN hall_of_fame_official_passers p ON c.official_passer_id = p.id
            LEFT JOIN hall_of_fame_announcements a ON a.claim_id = c.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search official passers roster for matches against submitted name.
     */
    public function matchPasser(int $batchId, string $submittedName, string $firstName = '', string $middleName = '', string $lastName = ''): array {
        $normSubmitted = self::normalizeName($submittedName);
        $normFirst = self::normalizeName($firstName);
        $normMid = self::normalizeName($middleName);
        $normLast = self::normalizeName($lastName);

        if ($normSubmitted === '' && ($normFirst === '' || $normLast === '')) {
            return ['matches' => [], 'match_status' => 'NO_MATCH', 'best_match' => null];
        }

        // 1. Fetch all official passers for this batch
        $stmt = $this->db->prepare("
            SELECT id, batch_id, sequence_no, full_name, normalized_name, first_name, middle_name, last_name, rank_num, rating_pct, school
            FROM hall_of_fame_official_passers
            WHERE batch_id = ?
        ");
        $stmt->execute([$batchId]);
        $roster = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $submittedTokens = array_values(array_filter(explode(' ', $normSubmitted)));
        sort($submittedTokens);
        $submittedTokenStr = implode(' ', $submittedTokens);

        $exactMatches = [];
        $closeMatches = [];

        foreach ($roster as $candidate) {
            $candNorm = $candidate['normalized_name'];
            $candFirst = self::normalizeName($candidate['first_name'] ?? '');
            $candMid = self::normalizeName($candidate['middle_name'] ?? '');
            $candLast = self::normalizeName($candidate['last_name'] ?? '');

            // Fallback parsing if first/last columns are empty (e.g. raw text import "SURNAME, FIRSTNAME MIDDLENAME")
            if ($candLast === '' && str_contains($candidate['full_name'], ',')) {
                $rawParts = explode(',', $candidate['full_name'], 2);
                $candLast = self::normalizeName($rawParts[0]);
                $candTokens = array_values(array_filter(explode(' ', self::normalizeName($rawParts[1]))));
                if (!empty($candTokens)) {
                    $candFirst = $candTokens[0];
                    if (count($candTokens) > 1) {
                        $candMid = end($candTokens);
                    }
                }
            }

            // Direct Last Name & First Name Match (PRC primary check)
            if ($normLast !== '' && $normFirst !== '' && $candLast !== '' && $candFirst !== '') {
                if ($candLast === $normLast && $candFirst === $normFirst) {
                    if ($normMid !== '') {
                        if ($candMid === $normMid || ($candMid !== '' && mb_substr($candMid, 0, 1) === mb_substr($normMid, 0, 1))) {
                            $exactMatches[] = $candidate;
                            continue;
                        }
                    } else {
                        // User did not provide middle name (optional), last & first name match exactly
                        $exactMatches[] = $candidate;
                        continue;
                    }
                }
            }

            // Check direct string match
            if ($candNorm === $normSubmitted) {
                $exactMatches[] = $candidate;
                continue;
            }

            // Check token multiset equality (e.g. "JUAN PEDRO MENDOZA DELA CRUZ" vs "DELA CRUZ, JUAN PEDRO MENDOZA")
            $candTokens = array_values(array_filter(explode(' ', $candNorm)));
            sort($candTokens);
            $candTokenStr = implode(' ', $candTokens);

            if ($submittedTokenStr === $candTokenStr) {
                $exactMatches[] = $candidate;
                continue;
            }

            // Check candidate synthesized formats (First Middle Last, Last First Middle)
            $midInit = $candMid !== '' ? mb_substr($candMid, 0, 1, 'UTF-8') : '';

            $variations = [
                trim("{$candLast} {$candFirst} {$candMid}"),
                trim("{$candFirst} {$candMid} {$candLast}"),
                trim("{$candFirst} {$midInit} {$candLast}"),
                trim("{$candLast} {$candFirst} {$midInit}"),
                trim("{$candFirst} {$candLast}"),
                trim("{$candLast} {$candFirst}")
            ];

            if (in_array($normSubmitted, $variations, true)) {
                $exactMatches[] = $candidate;
                continue;
            }

            // Check if all submitted tokens are present in candidate's full name tokens (e.g. submitted without middle name or abbreviated)
            $candAllTokens = array_values(array_filter(explode(' ', $candNorm)));
            $containsAllSubmitted = true;
            foreach ($submittedTokens as $st) {
                if (!in_array($st, $candAllTokens, true)) {
                    $containsAllSubmitted = false;
                    break;
                }
            }
            if ($containsAllSubmitted && count($submittedTokens) >= 2) {
                $closeMatches[] = $candidate;
            }
        }

        // Deduplicate
        $exactMatches = array_values(array_unique($exactMatches, SORT_REGULAR));
        $closeMatches = array_values(array_unique($closeMatches, SORT_REGULAR));

        if (count($exactMatches) === 1) {
            return [
                'matches' => $exactMatches,
                'match_status' => 'RESULT_MATCHED',
                'best_match' => $exactMatches[0]
            ];
        }

        if (count($exactMatches) > 1) {
            return [
                'matches' => $exactMatches,
                'match_status' => 'AMBIGUOUS_MATCH',
                'best_match' => null
            ];
        }

        // If no full exact match, but exactly one candidate matches first & last name
        if (count($closeMatches) === 1) {
            return [
                'matches' => $closeMatches,
                'match_status' => 'RESULT_MATCHED',
                'best_match' => $closeMatches[0]
            ];
        }

        if (count($closeMatches) > 1) {
            return [
                'matches' => $closeMatches,
                'match_status' => 'AMBIGUOUS_MATCH',
                'best_match' => null
            ];
        }

        return [
            'matches' => [],
            'match_status' => 'NO_MATCH',
            'best_match' => null
        ];
    }

    /**
     * Submit an official board exam passing claim.
     */
    public function submitClaim(
        int $userId,
        int $batchId,
        string $submittedName,
        string $displayPreference,
        bool $consentConfirmed,
        string $firstName = '',
        string $middleName = '',
        string $lastName = ''
    ): array {
        $submittedName = trim($submittedName);
        if ($submittedName === '' && ($firstName !== '' || $lastName !== '')) {
            $submittedName = strtoupper(trim($lastName) . ', ' . trim($firstName) . ($middleName !== '' ? ' ' . trim($middleName) : ''));
        }

        if ($submittedName === '') {
            return ['success' => false, 'message' => 'Please enter your full name as published by the PRC.'];
        }

        if (!$consentConfirmed) {
            return ['success' => false, 'message' => 'Consent confirmation is required to verify your claim.'];
        }

        $displayPreference = strtoupper($displayPreference) === 'FULL' ? 'FULL' : 'MASKED';

        // Check batch
        $bStmt = $this->db->prepare("SELECT * FROM hall_of_fame_batches WHERE id = ?");
        $bStmt->execute([$batchId]);
        $batch = $bStmt->fetch(PDO::FETCH_ASSOC);
        if (!$batch) {
            return ['success' => false, 'message' => 'Selected examination batch is not currently supported.'];
        }

        // Check if user already submitted a claim for this batch
        $cStmt = $this->db->prepare("SELECT * FROM hall_of_fame_claims WHERE user_id = ? AND batch_id = ?");
        $cStmt->execute([$userId, $batchId]);
        $existing = $cStmt->fetch(PDO::FETCH_ASSOC);

        $normalized = self::normalizeName($submittedName);
        $maskedPreview = self::maskName($submittedName);

        // Perform server-side matching against official PRC result records
        $matchResult = $this->matchPasser($batchId, $submittedName, $firstName, $middleName, $lastName);
        $matchStatus = $matchResult['match_status'];
        $bestMatch = $matchResult['best_match'];
        $officialPasserId = $bestMatch ? (int)$bestMatch['id'] : null;

        if ($matchStatus === 'RESULT_MATCHED' || $matchStatus === 'AMBIGUOUS_MATCH') {
            $claimStatus = 'APPROVED';
            $identityStatus = 'VERIFIED';
            $feedbackMessage = "Congratulations! Your name was matched in the official {$batch['batch_name']} PRC Successful Examinees list. Your passing claim has been automatically approved and inscribed into the Hall of Fame!";
        } else {
            $claimStatus = 'REJECTED';
            $identityStatus = 'REJECTED';
            $feedbackMessage = "Unmatched: No matching record was found in the official {$batch['batch_name']} PRC results. Please verify the exact spelling of your name.";
        }

        $matchDetailsJson = json_encode([
            'matched_examinee' => $bestMatch ? $bestMatch['full_name'] : null,
            'match_count' => count($matchResult['matches']),
            'official_source_url' => $batch['official_source_url']
        ]);

        $displayName = $displayPreference === 'MASKED' ? $maskedPreview : $submittedName;
        $annFormat = "CONGRATULATIONS, {$displayName}, RPm! — BATCH " . strtoupper($batch['exam_month']) . ", {$batch['exam_year']}";

        if ($existing) {
            // Update existing claim
            $upStmt = $this->db->prepare("
                UPDATE hall_of_fame_claims
                SET submitted_full_name = ?,
                    normalized_submitted_name = ?,
                    display_preference = ?,
                    masked_name_preview = ?,
                    official_passer_id = ?,
                    match_status = ?,
                    match_confidence = ?,
                    match_details = ?,
                    identity_status = ?,
                    claim_status = ?,
                    review_notes = ?
                WHERE id = ?
            ");
            $upStmt->execute([
                $submittedName,
                $normalized,
                $displayPreference,
                $maskedPreview,
                $officialPasserId,
                $matchStatus,
                $bestMatch ? 'EXACT' : 'NONE',
                $matchDetailsJson,
                $identityStatus,
                $claimStatus,
                $bestMatch ? "Automatically approved & matched with Seq. No. {$bestMatch['sequence_no']}" : "Unmatched upon name update.",
                $existing['id']
            ]);

            // Update announcement format
            if ($claimStatus === 'APPROVED') {
                $this->db->prepare("
                    INSERT INTO hall_of_fame_announcements (
                        claim_id, batch_id, user_id, display_name, display_preference,
                        title_credential, announcement_format, is_active, published_at
                    ) VALUES (?, ?, ?, ?, ?, 'RPm', ?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                        display_name = VALUES(display_name),
                        display_preference = VALUES(display_preference),
                        announcement_format = VALUES(announcement_format),
                        is_active = 1,
                        published_at = NOW()
                ")->execute([$existing['id'], $batchId, $userId, $displayName, $displayPreference, $annFormat]);
            } else {
                $this->db->prepare("UPDATE hall_of_fame_announcements SET is_active = 0 WHERE claim_id = ?")->execute([$existing['id']]);
            }

            $this->logAudit($userId, 'CLAIM_UPDATED', 'hall_of_fame_claims', $existing['id'], [
                'batch_id' => $batchId,
                'match_status' => $matchStatus,
                'claim_status' => $claimStatus,
                'display_preference' => $displayPreference,
                'new_name' => $submittedName
            ]);

            return [
                'success' => true,
                'message' => $feedbackMessage,
                'claim_id' => (int)$existing['id'],
                'match_status' => $matchStatus,
                'claim_status' => $claimStatus,
                'is_update' => true
            ];
        }

        $insertStmt = $this->db->prepare("
            INSERT INTO hall_of_fame_claims (
                user_id, batch_id, submitted_full_name, normalized_submitted_name,
                display_preference, masked_name_preview, official_passer_id,
                match_status, match_confidence, match_details, identity_status,
                claim_status, consent_confirmed
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insertStmt->execute([
            $userId,
            $batchId,
            $submittedName,
            $normalized,
            $displayPreference,
            $maskedPreview,
            $officialPasserId,
            $matchStatus,
            $bestMatch ? 'EXACT' : 'NONE',
            $matchDetailsJson,
            $identityStatus,
            $claimStatus,
            1
        ]);

        $claimId = (int)$this->db->lastInsertId();

        if ($claimStatus === 'APPROVED') {
            $this->db->prepare("
                INSERT INTO hall_of_fame_announcements (
                    claim_id, batch_id, user_id, display_name, display_preference,
                    title_credential, announcement_format, is_active, published_at
                ) VALUES (?, ?, ?, ?, ?, 'RPm', ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    display_preference = VALUES(display_preference),
                    announcement_format = VALUES(announcement_format),
                    is_active = 1,
                    published_at = NOW()
            ")->execute([$claimId, $batchId, $userId, $displayName, $displayPreference, $annFormat]);
        }

        // Audit Log
        $this->logAudit($userId, 'CLAIM_SUBMITTED', 'hall_of_fame_claims', $claimId, [
            'batch_id' => $batchId,
            'match_status' => $matchStatus,
            'claim_status' => $claimStatus,
            'display_preference' => $displayPreference
        ]);

        return [
            'success' => true,
            'message' => $feedbackMessage,
            'claim_id' => $claimId,
            'match_status' => $matchStatus,
            'claim_status' => $claimStatus,
            'display_preference' => $displayPreference,
            'masked_preview' => $maskedPreview
        ];
    }

    /**
     * Administrative Review Queue.
     */
    public function getAdminQueue(string $filterStatus = 'ALL'): array {
        $sql = "
            SELECT 
                c.id,
                c.user_id,
                c.batch_id,
                c.submitted_full_name,
                c.display_preference,
                c.masked_name_preview,
                c.match_status,
                c.match_confidence,
                c.match_details,
                c.identity_status,
                c.claim_status,
                c.review_notes,
                c.reviewed_at,
                c.created_at,
                u.username,
                u.nickname,
                u.email,
                b.batch_name,
                b.exam_name,
                b.exam_month,
                b.exam_year,
                b.official_source_url,
                p.full_name AS official_full_name,
                p.school AS official_school,
                p.rank_num,
                p.rating_pct,
                a.id AS announcement_id,
                a.is_active AS announcement_active
            FROM hall_of_fame_claims c
            JOIN users u ON c.user_id = u.id
            JOIN hall_of_fame_batches b ON c.batch_id = b.id
            LEFT JOIN hall_of_fame_official_passers p ON c.official_passer_id = p.id
            LEFT JOIN hall_of_fame_announcements a ON a.claim_id = c.id
        ";

        $params = [];
        if ($filterStatus !== 'ALL') {
            $sql .= " WHERE c.claim_status = ?";
            $params[] = $filterStatus;
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Admin decision on a claim (Approve, Reject, Needs Correction).
     */
    public function decideClaim(int $adminUserId, int $claimId, string $decision, ?string $notes = null): array {
        $cStmt = $this->db->prepare("
            SELECT c.*, b.batch_name, b.exam_month, b.exam_year 
            FROM hall_of_fame_claims c
            JOIN hall_of_fame_batches b ON c.batch_id = b.id
            WHERE c.id = ?
        ");
        $cStmt->execute([$claimId]);
        $claim = $cStmt->fetch(PDO::FETCH_ASSOC);
        if (!$claim) {
            return ['success' => false, 'message' => 'Claim not found.'];
        }

        $decision = strtoupper($decision);

        if ($decision === 'APPROVE') {
            // Mark claim as APPROVED and identity as VERIFIED
            $upd = $this->db->prepare("
                UPDATE hall_of_fame_claims 
                SET claim_status = 'APPROVED', identity_status = 'VERIFIED', reviewer_id = ?, review_notes = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$adminUserId, $notes ?: 'Approved by administrator after official PRC roster review', $claimId]);

            // Create or activate announcement
            $displayName = ($claim['display_preference'] === 'FULL') 
                ? $claim['submitted_full_name'] 
                : $claim['masked_name_preview'];

            $annFormat = "CONGRATULATIONS, {$displayName}, RPm! — BATCH " . strtoupper($claim['exam_month']) . ", {$claim['exam_year']}";

            $annStmt = $this->db->prepare("
                INSERT INTO hall_of_fame_announcements (
                    claim_id, batch_id, user_id, display_name, display_preference,
                    title_credential, announcement_format, is_active, published_at
                ) VALUES (?, ?, ?, ?, ?, 'RPm', ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    announcement_format = VALUES(announcement_format),
                    is_active = 1,
                    published_at = NOW()
            ");
            $annStmt->execute([
                $claimId,
                $claim['batch_id'],
                $claim['user_id'],
                $displayName,
                $claim['display_preference'],
                $annFormat
            ]);

            $this->logAudit($adminUserId, 'CLAIM_APPROVED', 'hall_of_fame_claims', $claimId, ['notes' => $notes]);

            return ['success' => true, 'message' => 'Claim approved and announcement published!'];

        } elseif ($decision === 'REJECT') {
            $upd = $this->db->prepare("
                UPDATE hall_of_fame_claims 
                SET claim_status = 'REJECTED', identity_status = 'REJECTED', reviewer_id = ?, review_notes = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$adminUserId, $notes ?: 'Claim rejected by administrator', $claimId]);

            // Deactivate announcement if existed
            $this->db->prepare("UPDATE hall_of_fame_announcements SET is_active = 0 WHERE claim_id = ?")->execute([$claimId]);

            $this->logAudit($adminUserId, 'CLAIM_REJECTED', 'hall_of_fame_claims', $claimId, ['notes' => $notes]);

            return ['success' => true, 'message' => 'Claim rejected.'];

        } elseif ($decision === 'NEEDS_CORRECTION') {
            $upd = $this->db->prepare("
                UPDATE hall_of_fame_claims 
                SET claim_status = 'NEEDS_CORRECTION', reviewer_id = ?, review_notes = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$adminUserId, $notes ?: 'Name or batch correction required', $claimId]);

            $this->logAudit($adminUserId, 'CLAIM_NEEDS_CORRECTION', 'hall_of_fame_claims', $claimId, ['notes' => $notes]);

            return ['success' => true, 'message' => 'Claim set to needs correction.'];
        }

        return ['success' => false, 'message' => 'Invalid decision.'];
    }

    /**
     * Import official PRC examinee names into batch roster.
     */
    public function importRoster(int $adminUserId, int $batchId, string $rawText): array {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawText));
        $imported = 0;

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO hall_of_fame_official_passers (
                batch_id, sequence_no, full_name, normalized_name, created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ");

        foreach ($lines as $idx => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || stripos($line, 'Seq. No.') !== false) continue;

            $seqNo = null;
            $nameStr = $line;

            // Pattern: "123   DELA CRUZ, JUAN PEDRO"
            if (preg_match('/^(\d+)[\s\t]+(.+)$/', $line, $m)) {
                $seqNo = (int)$m[1];
                $nameStr = trim($m[2]);
            }

            $normalized = self::normalizeName($nameStr);
            if ($normalized === '') continue;

            try {
                $stmt->execute([$batchId, $seqNo ?: ($imported + 1), $nameStr, $normalized]);
                if ($stmt->rowCount() > 0) $imported++;
            } catch (Throwable $e) {}
        }

        // Update batch total passers count
        $cnt = (int)$this->db->query("SELECT COUNT(*) FROM hall_of_fame_official_passers WHERE batch_id = {$batchId}")->fetchColumn();
        $this->db->prepare("UPDATE hall_of_fame_batches SET total_passers = ?, source_status = 'imported_fallback' WHERE id = ?")->execute([$cnt, $batchId]);

        $this->logAudit($adminUserId, 'BATCH_ROSTER_IMPORTED', 'hall_of_fame_batches', $batchId, ['imported_count' => $imported]);

        return [
            'success' => true,
            'message' => "Successfully imported {$imported} official examinee records. Total roster: {$cnt}.",
            'total_passers' => $cnt
        ];
    }

    /**
     * Audit log helper.
     */
    private function logAudit(int $userId, string $action, string $targetType, int $targetId, array $details = []): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO hall_of_fame_audit_logs (user_id, action, target_type, target_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt->execute([$userId, $action, $targetType, $targetId, json_encode($details), $ip]);
        } catch (Throwable $e) {}
    }
}
