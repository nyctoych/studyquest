<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SpecialEvent.php';
require_once __DIR__ . '/../models/SpecialEventQuestion.php';
require_once __DIR__ . '/GroqQuestionGeneratorService.php';
require_once __DIR__ . '/QuestionValidationService.php';

class SpecialEventGenerationService {
    private PDO $db;
    private SpecialEvent $eventModel;
    private SpecialEventQuestion $questionModel;
    private GroqQuestionGeneratorService $groqService;
    private QuestionValidationService $validator;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->eventModel = new SpecialEvent();
        $this->questionModel = new SpecialEventQuestion();
        $this->groqService = new GroqQuestionGeneratorService();
        $this->validator = new QuestionValidationService();
    }

    /**
     * Generate or ensure the daily 140-question Special Event for a specific user and date.
     * Divides exactly 140 questions across 4 Study Hall subjects (35 questions each).
     * Different users receive different subjects and distinct question sets.
     *
     * @param string $eventDate Date in 'Y-m-d' format
     * @param int $userId Target user ID (0 for global/admin template)
     * @param bool $forceRegenerate Whether to wipe and regenerate an existing event
     * @return array Result summary with event data and validation status
     */
    public function generateDailyEvent(string $eventDate = '', int $userId = 0, bool $forceRegenerate = false): array {
        if (empty($eventDate)) {
            $eventDate = date('Y-m-d');
        }

        $existing = $this->eventModel->findByDate($eventDate, $userId);
        if ($existing && !$forceRegenerate) {
            if ($existing['status'] === 'published') {
                $count = $this->eventModel->countQuestions((int)$existing['id']);
                if ($count === 140) {
                    return [
                        'success' => true,
                        'message' => 'Published 140-question event already exists for today',
                        'event_id' => (int) $existing['id'],
                        'event' => $existing,
                        'question_count' => $count
                    ];
                }
            }
        }

        // 1. Select 4 distinct Study Hall psychiatric domains for this user and date
        $selectedDomains = $this->selectFourDomainsForUser($userId, $eventDate);
        if (count($selectedDomains) < 4) {
            throw new RuntimeException("Could not allocate 4 Study Hall subjects. Please check psychiatric_domains table.");
        }

        $assignedDomainsData = [];
        foreach ($selectedDomains as $d) {
            $assignedDomainsData[] = [
                'id' => (int) $d['id'],
                'name' => $d['name'],
                'question_count' => 35
            ];
        }

        $domainNames = array_column($assignedDomainsData, 'name');
        $title = "The Grand Trial of the Mindrealm: " . date('F j, Y', strtotime($eventDate));
        $desc = "A 140-question clinical mock examination assessing diagnostic mastery across 4 Study Hall domains: " . implode(', ', $domainNames) . ". Exactly 35 items per domain.";

        // Create or reset event record
        $eventId = null;
        if ($existing) {
            $eventId = (int) $existing['id'];
            $this->eventModel->update($eventId, [
                'title' => $title,
                'description' => $desc,
                'assigned_domains' => json_encode($assignedDomainsData),
                'status' => 'generating',
                'generation_status' => 'in_progress',
                'error_log' => null
            ]);
            // Clear prior draft questions if regenerating
            $this->db->prepare("DELETE FROM special_event_questions WHERE special_event_id = ?")->execute([$eventId]);
        } else {
            $eventId = $this->eventModel->create([
                'user_id' => $userId,
                'event_date' => $eventDate,
                'title' => $title,
                'description' => $desc,
                'question_count' => 140,
                'assigned_domains' => $assignedDomainsData,
                'status' => 'generating',
                'generation_status' => 'in_progress',
                'generation_version' => 'v1.0'
            ]);
        }

        try {
            $allApprovedQuestions = [];

            // 2. Generate exactly 35 questions for each of the 4 domains
            // Overall difficulty targets across 140:
            // Moderate: 28 (7 per domain)
            // Difficult: 49 (12, 13, 12, 12)
            // Very Difficult: 42 (11, 10, 11, 10)
            // Board Style Case: 21 (5, 5, 5, 6)
            $domainDifficultyPlans = [
                0 => ['moderate' => 7, 'difficult' => 12, 'very_difficult' => 11, 'board_style_case' => 5],
                1 => ['moderate' => 7, 'difficult' => 13, 'very_difficult' => 10, 'board_style_case' => 5],
                2 => ['moderate' => 7, 'difficult' => 12, 'very_difficult' => 11, 'board_style_case' => 5],
                3 => ['moderate' => 7, 'difficult' => 12, 'very_difficult' => 10, 'board_style_case' => 6],
            ];

            // Load Bloom's Taxonomy configurable distribution
            $bloomDistSetting = $this->db->query("SELECT setting_value FROM special_event_settings WHERE setting_key = 'bloom_distribution'")->fetchColumn();
            $bloomDistribution = $bloomDistSetting ? json_decode($bloomDistSetting, true) : null;
            if (!$bloomDistribution || !is_array($bloomDistribution)) {
                $bloomDistribution = [
                    'remember' => 20,
                    'understand' => 25,
                    'apply' => 35,
                    'analyze' => 30,
                    'evaluate' => 18,
                    'create' => 12
                ];
            }

            // Partition the 140 questions across the 4 domains (35 questions each) for Bloom levels
            $domainBloomPlans = [0 => [], 1 => [], 2 => [], 3 => []];
            $domainTotals = [0 => 0, 1 => 0, 2 => 0, 3 => 0];

            foreach ($bloomDistribution as $bloom => $totalCnt) {
                $base = intdiv($totalCnt, 4);
                $rem = $totalCnt % 4;
                for ($d = 0; $d < 4; $d++) {
                    $domainBloomPlans[$d][$bloom] = $base;
                    $domainTotals[$d] += $base;
                }
                for ($r = 0; $r < $rem; $r++) {
                    asort($domainTotals);
                    $minDomain = array_key_first($domainTotals);
                    $domainBloomPlans[$minDomain][$bloom]++;
                    $domainTotals[$minDomain]++;
                }
            }

            foreach ($selectedDomains as $domainIdx => $domain) {
                $domainId = (int) $domain['id'];
                $domainName = $domain['name'];
                $parts = $this->fetchDomainParts($domainId);
                if (empty($parts)) {
                    throw new RuntimeException("No study parts found for domain {$domainName} (ID {$domainId}).");
                }

                $diffPlan = $domainDifficultyPlans[$domainIdx];
                $domainDiffQueue = [];
                foreach ($diffPlan as $diff => $cnt) {
                    for ($k = 0; $k < $cnt; $k++) {
                        $domainDiffQueue[] = $diff;
                    }
                }
                shuffle($domainDiffQueue);

                $bloomPlan = $domainBloomPlans[$domainIdx] ?? [];
                $domainBloomQueue = [];
                foreach ($bloomPlan as $bLevel => $bCnt) {
                    for ($k = 0; $k < $bCnt; $k++) {
                        $domainBloomQueue[] = $bLevel;
                    }
                }
                shuffle($domainBloomQueue);

                $domainApprovedQuestions = [];
                $partCount = count($parts);
                $partIdx = 0;
                $batchSize = 7;
                $attempts = 0;
                $maxAttempts = 120;

                while (count($domainApprovedQuestions) < 35 && $attempts < $maxAttempts) {
                    $attempts++;
                    $needed = 35 - count($domainApprovedQuestions);
                    $currentBatchSize = min($batchSize, $needed);

                    $sourceCtx = $parts[$partIdx % $partCount];
                    $partIdx++;

                    $batchDiffs = array_slice($domainDiffQueue, count($domainApprovedQuestions), $currentBatchSize);
                    $batchBlooms = array_slice($domainBloomQueue, count($domainApprovedQuestions), $currentBatchSize);

                    $candidates = $this->groqService->generateBatch($sourceCtx, $currentBatchSize, $batchDiffs, $userId, $batchBlooms);

                    foreach ($candidates as $cand) {
                        if (count($domainApprovedQuestions) >= 35) {
                            break;
                        }

                        // Validate against all questions approved so far in this event
                        $combinedPool = array_merge($allApprovedQuestions, $domainApprovedQuestions);
                        $valResult = $this->validator->validateQuestion($cand, $combinedPool);

                        if ($valResult['valid']) {
                            $norm = $valResult['normalized'];
                            $shuffled = $this->randomizeChoices($norm);
                            $domainApprovedQuestions[] = $shuffled;
                        }
                    }
                }

                if (count($domainApprovedQuestions) !== 35) {
                    throw new RuntimeException("Domain '{$domainName}' reached " . count($domainApprovedQuestions) . " items instead of the required 35 items.");
                }

                $allApprovedQuestions = array_merge($allApprovedQuestions, $domainApprovedQuestions);
            }

            if (count($allApprovedQuestions) !== 140) {
                throw new RuntimeException("Failed to reach exactly 140 valid questions. Generated: " . count($allApprovedQuestions));
            }

            // 3. Database transaction: insert all 140 finalized questions
            $this->db->beginTransaction();

            // Shuffle overall question order so the 4 domains are interspersed across the 140 examination items
            shuffle($allApprovedQuestions);

            $order = 1;
            foreach ($allApprovedQuestions as $q) {
                $q['special_event_id'] = $eventId;
                $q['display_order'] = $order++;
                $this->questionModel->create($q);
            }

            // 4. Final validation before publishing
            $savedCount = $this->eventModel->countQuestions($eventId);
            if ($savedCount !== 140) {
                $this->db->rollBack();
                throw new RuntimeException("Integrity check failed: {$savedCount} questions inserted instead of 140.");
            }

            // Mark published
            $expiresAt = date('Y-m-d 23:59:59', strtotime($eventDate));
            $this->eventModel->update($eventId, [
                'status' => 'published',
                'generation_status' => 'completed',
                'generated_at' => date('Y-m-d H:i:s'),
                'published_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => "Successfully generated and published 140-question Special Event for user {$userId} on {$eventDate} (4 domains, 35 items each)",
                'event_id' => $eventId,
                'question_count' => 140,
                'assigned_domains' => $assignedDomainsData,
                'status' => 'published'
            ];

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $errorMsg = $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
            error_log("SpecialEventGenerationService Error: " . $errorMsg);

            $this->eventModel->update($eventId, [
                'status' => 'generation_failed',
                'generation_status' => 'failed',
                'error_log' => $errorMsg
            ]);

            return [
                'success' => false,
                'message' => 'Event generation failed: ' . $e->getMessage(),
                'event_id' => $eventId,
                'status' => 'generation_failed'
            ];
        }
    }

    /**
     * Select 4 distinct active psychiatric domains from Study Hall for a user.
     * Uses seeded randomization per (userId, eventDate) so each user has distinct subjects.
     */
    public function selectFourDomainsForUser(int $userId, string $eventDate): array {
        $allDomains = $this->fetchActiveDomains();
        if (count($allDomains) < 4) {
            return $allDomains;
        }

        $seed = ($userId > 0) ? crc32("user_{$userId}_{$eventDate}") : crc32("global_{$eventDate}");
        mt_srand($seed);

        $shuffled = $allDomains;
        for ($i = count($shuffled) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $shuffled[$i];
            $shuffled[$i] = $shuffled[$j];
            $shuffled[$j] = $tmp;
        }
        mt_srand(); // reset seed

        return array_slice($shuffled, 0, 4);
    }

    /**
     * Extract active domains from Study Hall.
     */
    public function fetchActiveDomains(): array {
        $stmt = $this->db->query("
            SELECT id, name, description, display_order
            FROM psychiatric_domains
            WHERE status = 'active'
            ORDER BY display_order ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $stmt2 = $this->db->query("
                SELECT id, name, description, display_order
                FROM psychology_domains
                WHERE status = 'active'
                ORDER BY display_order ASC
            ");
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rows ?: [];
    }

    /**
     * Extract all study parts for a specific domain.
     */
    public function fetchDomainParts(int $domainId): array {
        $stmt = $this->db->prepare("
            SELECT pd.id as domain_id, pd.name as domain_name, pd.description as domain_desc,
                   sp.id as part_id, sp.title as part_title, sp.part_number,
                   sp.overview, sp.diagnostic_criteria, sp.specifiers,
                   sp.differential_diagnosis, sp.clinical_example, sp.important_terms,
                   sp.summary_notes
            FROM psychiatric_domains pd
            JOIN study_parts sp ON pd.id = sp.domain_id
            WHERE pd.id = ?
            ORDER BY sp.part_number ASC
        ");
        $stmt->execute([$domainId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $stmt2 = $this->db->prepare("
                SELECT pd.id as domain_id, pd.name as domain_name, pd.description as domain_desc,
                       sp.id as part_id, sp.title as part_title, sp.part_number,
                       sp.overview, sp.diagnostic_criteria, sp.specifiers,
                       sp.differential_diagnosis, sp.clinical_example, sp.important_terms,
                       sp.summary_notes
                FROM psychology_domains pd
                JOIN psychology_parts sp ON pd.id = sp.domain_id
                WHERE pd.id = ?
                ORDER BY sp.part_number ASC
            ");
            $stmt2->execute([$domainId]);
            $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $rows ?: [];
    }

    /**
     * Randomize the four choices (A, B, C, D) and update the correct_choice mapping authoritative key.
     */
    public function randomizeChoices(array $q): array {
        $choicesMap = [
            'A' => $q['choice_a'],
            'B' => $q['choice_b'],
            'C' => $q['choice_c'],
            'D' => $q['choice_d']
        ];

        $correctOriginalKey = strtoupper($q['correct_choice']);
        $correctOriginalText = $choicesMap[$correctOriginalKey] ?? $q['choice_a'];

        // Shuffle keys
        $keys = ['A', 'B', 'C', 'D'];
        shuffle($keys);

        $newChoices = [];
        $newCorrect = 'A';

        foreach (['A', 'B', 'C', 'D'] as $idx => $targetKey) {
            $fromKey = $keys[$idx];
            $text = $choicesMap[$fromKey];
            $newChoices[$targetKey] = $text;
            if ($fromKey === $correctOriginalKey) {
                $newCorrect = $targetKey;
            }
        }

        $q['choice_a'] = $newChoices['A'];
        $q['choice_b'] = $newChoices['B'];
        $q['choice_c'] = $newChoices['C'];
        $q['choice_d'] = $newChoices['D'];
        $q['correct_choice'] = $newCorrect;

        return $q;
    }

    /**
     * Replace a single rejected question in an event with a newly validated replacement question.
     */
    public function replaceQuestion(int $questionId): array {
        $existingQ = $this->questionModel->find($questionId);
        if (!$existingQ) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        $eventId = (int) $existingQ['special_event_id'];
        $domainId = (int) $existingQ['domain_id'];
        $partId = (int) $existingQ['study_part_id'];

        // Fetch source context
        $stmt = $this->db->prepare("
            SELECT pd.id as domain_id, pd.name as domain_name,
                   sp.id as part_id, sp.title as part_title, sp.part_number,
                   sp.overview, sp.diagnostic_criteria, sp.differential_diagnosis,
                   sp.clinical_example, sp.specifiers, sp.important_terms
            FROM psychiatric_domains pd
            JOIN study_parts sp ON pd.id = sp.domain_id
            WHERE pd.id = ? AND sp.id = ?
        ");
        $stmt->execute([$domainId, $partId]);
        $source = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'domain_id' => $domainId,
            'part_id' => $partId,
            'domain_name' => $existingQ['domain_name'] ?? 'Domain',
            'part_title' => $existingQ['part_title'] ?? 'Part'
        ];

        $allQuestions = $this->questionModel->getByEventId($eventId);
        $candidates = $this->groqService->generateBatch($source, 2, [$existingQ['difficulty']]);

        foreach ($candidates as $cand) {
            $val = $this->validator->validateQuestion($cand, $allQuestions);
            if ($val['valid']) {
                $norm = $this->randomizeChoices($val['normalized']);
                $norm['display_order'] = $existingQ['display_order'];
                $this->questionModel->update($questionId, $norm);
                return [
                    'success' => true,
                    'message' => 'Question successfully replaced and validated',
                    'question' => $this->questionModel->find($questionId)
                ];
            }
        }

        return ['success' => false, 'message' => 'Failed to generate a valid replacement question. Please try again.'];
    }
}
