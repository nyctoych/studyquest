<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

class GroqQuestionGeneratorService {
    private string $apiKey;
    private string $model;
    private string $apiUrl;
    private int $timeout;
    private static bool $isRateLimited = false;

    public function __construct() {
        $this->apiKey = trim((string) env('GROQ_API_KEY', ''));
        $this->model  = trim((string) env('GROQ_MODEL', 'openai/gpt-oss-120b'));
        $baseUrl      = rtrim((string) env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/');
        $this->apiUrl = $baseUrl . '/chat/completions';
        $this->timeout = 12; // seconds (fast failover to curriculum fallback)
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey) 
            && $this->apiKey !== 'YOUR_NEW_GROQ_API_KEY'
            && $this->apiKey !== 'your_groq_api_key_here';
    }

    public function getModel(): string {
        return $this->model;
    }

    public function setModel(string $model): void {
        $this->model = $model;
    }

    /**
     * Generate a batch of board-exam-style questions for a given domain, part, difficulty target, and Bloom cognitive level.
     *
     * @param array $sourceContext Structured Study Hall source content
     * @param int $batchSize Number of questions to generate in this batch (typically 5 to 10)
     * @param array $difficultyMix Target difficulties (e.g. ['moderate', 'difficult', 'very_difficult', 'board_style_case'])
     * @param int $userSeed Unique candidate identifier to ensure question variation across users
     * @param array $bloomMix Target Bloom levels (e.g. ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'])
     * @return array List of raw generated question candidate arrays
     */
    public function generateBatch(array $sourceContext, int $batchSize = 7, array $difficultyMix = [], int $userSeed = 0, array $bloomMix = []): array {
        if (self::$isRateLimited || !$this->isConfigured()) {
            return $this->generateLocalCurriculumFallback($sourceContext, $batchSize, $difficultyMix, $userSeed, $bloomMix);
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt   = $this->buildUserPrompt($sourceContext, $batchSize, $difficultyMix, $userSeed, $bloomMix);

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.65,
            'max_tokens' => 4096
        ];

        $attempts = 0;
        $maxAttempts = 1;
        $lastError = '';

        while ($attempts < $maxAttempts) {
            $attempts++;
            $response = $this->executeCurlRequest($payload);

            if ($response['success']) {
                $parsed = $this->parseJsonResponse($response['body']);
                if (!empty($parsed['questions']) && is_array($parsed['questions'])) {
                    // Enrich with source hashes and concept tracking
                    return $this->normalizeGeneratedQuestions($parsed['questions'], $sourceContext);
                }
                $lastError = 'Invalid JSON structure or missing questions array';
            } else {
                $lastError = $response['error'] ?? 'Unknown network error';
                if (str_contains($lastError, '429')) {
                    self::$isRateLimited = true;
                    error_log("GroqQuestionGeneratorService: Rate limit (429) encountered. Fast-switching entire generation cycle to Study Hall curriculum generator.");
                    break;
                }
            }

            // Short pause before retry
            usleep(200000); // 0.2s
        }

        error_log("GroqQuestionGeneratorService API generation failed after {$attempts} attempt(s): " . $lastError . ". Falling back to Study Hall curriculum generator.");
        return $this->generateLocalCurriculumFallback($sourceContext, $batchSize, $difficultyMix, $userSeed, $bloomMix);
    }

    /**
     * Paraphrase or regenerate a single rejected question while preserving psychiatric accuracy.
     */
    public function regenerateSingleQuestion(array $sourceContext, string $difficulty, string $questionType, string $previousConcept = ''): ?array {
        $results = $this->generateBatch($sourceContext, 1, [$difficulty]);
        return $results[0] ?? null;
    }

    private function buildSystemPrompt(): string {
        return <<<PROMPT
You are the Chief Psychiatric Examination Architect for StudyQuest.
Your task is to generate rigorous, authentic BOARD-EXAMINATION-STYLE multiple-choice questions for the Special Event Daily Trial.

ACADEMIC MANDATES:
1. STUDY HALL IS THE SOLE ACADEMIC SOURCE OF TRUTH. You must formulate every question, distractor, and explanation strictly around the provided Study Hall reviewer text.
2. DO NOT invent unsupported criteria, fake medications, non-DSM-5 classifications, or clinical trivia not grounded in the reviewer context.
3. Every question must be a challenging MULTIPLE CHOICE question with EXACTLY FOUR choices labeled "A", "B", "C", and "D".
4. EXACTLY ONE choice must be defensibly correct based strictly on the provided reviewer context.
5. HARD & CONFUSING DISTRACTOR DESIGN (REASONING-BASED, STRICTLY FAIR):
   - The user demands questions and choices that are HARD and CONFUSING, designed to discriminate deep conceptual mastery from superficial recognition.
   - Distractors MUST be plausible, drawn from neighboring DSM-5 disorders, shared diagnostic features, borderline duration thresholds, partially correct clinical formulations, or common psychiatric misconceptions.
   - NEVER use absurd, joke, or obviously false answers.
   - All 4 choices must be grammatically balanced, structurally parallel, and of comparable length so the correct choice cannot be deduced by format alone.
   - "Confusing" MUST NEVER mean ambiguous or unfair:
     * NO two arguably correct choices.
     * NO trick questions, double negatives, or punctuation traps.
     * The difficulty MUST stem from psychiatric discrimination and subtle clinical nuance, NOT stem ambiguity.

6. REVISED BLOOM'S TAXONOMY COGNITIVE TRANSFORMATION RULES:
   Transform the supplied Study Hall facts into the designated Bloom cognitive level:
   - "remember": Factual retrieval. Test recall of exact diagnostic criteria thresholds, symptom duration requirements, inclusionary/exclusionary rules, and formal terminology directly stated in Study Hall.
   - "understand": Conceptual interpretation. Test explanation of why criteria exist, summarizing psychopathological meaning, or identifying the best explanation of a clinical mechanism.
   - "apply": Clinical application. Present a realistic clinical vignette with age, setting, and symptoms, requiring the test-taker to apply Study Hall diagnostic rules to reach the correct DSM-5 formulation.
   - "analyze": Diagnostic discrimination & differential analysis. Present complex case presentations with overlapping features, requiring breaking down clinical evidence, distinguishing between competing psychiatric conditions, or isolating which contradictory finding changes the primary diagnosis.
   - "evaluate": Clinical judgment & justification. Present multi-factorial clinical scenarios requiring judging competing clinical interpretations, evaluating which diagnostic conclusion is most defensible under Study Hall standards, or selecting the strongest clinical rationale.
   - "create": Integrated formulation & management synthesis. In multiple-choice form, present comprehensive clinical scenarios requiring selecting the best structured management plan, multi-tier intervention strategy, or synthesized diagnostic formulation integrating multiple Study Hall principles.

   CRITICAL: Difficulty and Bloom Level are independent dimensions. A "very_difficult" question can be "understand", and a "moderate" question can be "analyze". Do not conflate cognitive level with difficulty rating.

7. Output MUST BE valid JSON conforming to the requested schema.
PROMPT;
    }

    private function buildUserPrompt(array $ctx, int $batchSize, array $diffMix, int $userSeed = 0, array $bloomMix = []): string {
        $domainName = $ctx['domain_name'] ?? 'Psychiatric Domain';
        $domainId   = $ctx['domain_id'] ?? 1;
        $partId     = $ctx['part_id'] ?? 1;
        $partTitle  = $ctx['part_title'] ?? 'Diagnostic Review';
        $overview   = $ctx['overview'] ?? '';
        $criteria   = $ctx['diagnostic_criteria'] ?? '';
        $diffDiag   = $ctx['differential_diagnosis'] ?? '';
        $examples   = $ctx['clinical_example'] ?? '';
        $specifiers = $ctx['specifiers'] ?? '';
        $keyTerms   = is_array($ctx['important_terms'] ?? null) ? implode(', ', $ctx['important_terms']) : ($ctx['important_terms'] ?? '');

        $diffStr = !empty($diffMix) ? implode(', ', $diffMix) : 'difficult, very_difficult, board_style_case';
        $bloomStr = !empty($bloomMix) ? implode(', ', $bloomMix) : 'remember, understand, apply, analyze, evaluate, create';

        $itemInstructions = [];
        $diffCount = count($diffMix);
        $bloomCount = count($bloomMix);
        for ($i = 0; $i < $batchSize; $i++) {
            $tDiff = ($diffCount > 0) ? ($diffMix[$i % $diffCount] ?? 'difficult') : 'difficult';
            $tBloom = ($bloomCount > 0) ? ($bloomMix[$i % $bloomCount] ?? 'apply') : 'apply';
            $itemInstructions[] = "Item #" . ($i + 1) . ": Target Bloom Level = '{$tBloom}', Difficulty = '{$tDiff}'";
        }
        $itemsQueueText = implode("\n", $itemInstructions);

        return <<<JSON
Candidate Examination Stream: Candidate #{$userSeed}. Generate {$batchSize} unique board-exam-style questions based on this Study Hall source material:

[STUDY HALL REVIEWER CONTEXT]
Domain: {$domainName} (ID: {$domainId})
Part: {$partTitle} (ID: {$partId})
Overview:
{$overview}

Diagnostic Criteria:
{$criteria}

Specifiers & Course:
{$specifiers}

Differential Diagnosis:
{$diffDiag}

Clinical Examples:
{$examples}

Key Terms:
{$keyTerms}

[EXAMINATION ITEM SPECIFICATIONS]
Candidate Seed: {$userSeed}. Ensure clinical vignettes, patient demographics, and phrasing are completely unique to this candidate.
Generate exactly {$batchSize} items according to this sequence:
{$itemsQueueText}

Allowed Difficulties: moderate, difficult, very_difficult, board_style_case.
Allowed Bloom Levels: remember, understand, apply, analyze, evaluate, create.
Allowed Question Types: differential_diagnosis, clinical_application, best_answer, case_analysis, recall, recognition, exception.

All choices A, B, C, D must be clinically plausible, highly nuanced, and structurally balanced.
Exactly ONE choice must be defensibly correct.

Return ONLY a JSON object with this exact structure:
{
  "questions": [
    {
      "question_text": "Detailed board-examination vignette or scenario stem...",
      "choices": {
        "A": "Plausible nuanced option A",
        "B": "Plausible nuanced option B",
        "C": "Plausible nuanced option C",
        "D": "Plausible nuanced option D"
      },
      "correct_choice": "B",
      "explanation": "Thorough clinical rationale explaining why B is correct based on Study Hall criteria and why A, C, and D are ruled out.",
      "difficulty": "difficult",
      "question_type": "differential_diagnosis",
      "bloom_level": "analyze",
      "concept_key": "Specific DSM-5 concept or differential tested"
    }
  ]
}
JSON;
    }

    private function executeCurlRequest(array $payload): array {
        $ch = curl_init($this->apiUrl);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => "cURL error: {$curlError}"];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ['success' => false, 'error' => "HTTP {$httpCode}: " . substr((string)$rawResponse, 0, 300)];
        }

        return ['success' => true, 'body' => $rawResponse];
    }

    private function parseJsonResponse(string $rawJson): array {
        $outer = json_decode($rawJson, true);
        if (!$outer || !isset($outer['choices'][0]['message']['content'])) {
            return [];
        }

        $content = trim($outer['choices'][0]['message']['content']);
        // Strip markdown code fences if model enclosed in ```json ... ```
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
        }

        $parsed = json_decode($content, true);
        return is_array($parsed) ? $parsed : [];
    }

    private function normalizeGeneratedQuestions(array $questions, array $sourceContext): array {
        $normalized = [];
        $domainId  = (int) ($sourceContext['domain_id'] ?? 1);
        $partId    = (int) ($sourceContext['part_id'] ?? 1);
        $sourceRaw = ($sourceContext['overview'] ?? '') . ($sourceContext['diagnostic_criteria'] ?? '');
        $sourceHash = sha1($sourceRaw ?: "domain_{$domainId}_part_{$partId}");

        foreach ($questions as $q) {
            if (empty($q['question_text']) || empty($q['choices']) || empty($q['correct_choice'])) {
                continue;
            }

            $choices = $q['choices'];
            $choiceA = trim($choices['A'] ?? ($choices['a'] ?? ''));
            $choiceB = trim($choices['B'] ?? ($choices['b'] ?? ''));
            $choiceC = trim($choices['C'] ?? ($choices['c'] ?? ''));
            $choiceD = trim($choices['D'] ?? ($choices['d'] ?? ''));
            $correct = strtoupper(trim($q['correct_choice']));

            if (!$choiceA || !$choiceB || !$choiceC || !$choiceD || !in_array($correct, ['A', 'B', 'C', 'D'])) {
                continue;
            }

            $qText = trim($q['question_text']);
            $conceptKey = trim($q['concept_key'] ?? ($sourceContext['part_title'] ?? 'DSM-5 Diagnosis'));
            $qHash = sha1(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $qText)));

            $validDifficulties = ['moderate', 'difficult', 'very_difficult', 'board_style_case'];
            $difficulty = strtolower(trim($q['difficulty'] ?? 'difficult'));
            if (!in_array($difficulty, $validDifficulties)) {
                $difficulty = 'difficult';
            }

            $validTypes = ['recall', 'recognition', 'differential_diagnosis', 'clinical_application', 'best_answer', 'case_analysis', 'exception'];
            $qType = strtolower(trim($q['question_type'] ?? 'clinical_application'));
            if (!in_array($qType, $validTypes)) {
                $qType = 'clinical_application';
            }

            $validBloomLevels = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'];
            $bloomLevel = strtolower(trim($q['bloom_level'] ?? ''));
            if (!in_array($bloomLevel, $validBloomLevels)) {
                if ($qType === 'recall') {
                    $bloomLevel = 'remember';
                } elseif ($qType === 'recognition') {
                    $bloomLevel = 'understand';
                } elseif ($qType === 'clinical_application') {
                    $bloomLevel = 'apply';
                } elseif ($qType === 'differential_diagnosis') {
                    $bloomLevel = 'analyze';
                } elseif ($qType === 'best_answer') {
                    $bloomLevel = 'evaluate';
                } else {
                    $bloomLevel = 'analyze';
                }
            }

            $normalized[] = [
                'domain_id' => $domainId,
                'study_part_id' => $partId,
                'question_text' => $qText,
                'choice_a' => $choiceA,
                'choice_b' => $choiceB,
                'choice_c' => $choiceC,
                'choice_d' => $choiceD,
                'correct_choice' => $correct,
                'explanation' => trim($q['explanation'] ?? 'Based on DSM-5 diagnostic criteria from Study Hall.'),
                'difficulty' => $difficulty,
                'question_type' => $qType,
                'bloom_level' => $bloomLevel,
                'concept_key' => $conceptKey,
                'source_hash' => $sourceHash,
                'question_hash' => $qHash,
                'generation_version' => 'groq_' . $this->model . '_v1.0'
            ];
        }

        return $normalized;
    }

    /**
     * High-fidelity Study Hall academic question synthesis generator
     * Used when Groq API key is not yet set or when running in local-first offline mode.
     * Generates board-exam vignettes directly from the rich Study Hall database reviewer content.
     */
    public function generateLocalCurriculumFallback(array $ctx, int $count, array $diffMix = [], int $userSeed = 0, array $bloomMix = []): array {
        $db = Database::getInstance();
        $domainId = (int) ($ctx['domain_id'] ?? 1);
        $partId   = (int) ($ctx['part_id'] ?? 1);
        $domainName = $ctx['domain_name'] ?? 'Psychopathology';
        $partTitle = $ctx['part_title'] ?? 'Diagnostic Assessment';

        // Check if existing approved questions exist in the database for this domain/part to use as templates or vignettes
        $stmt = $db->prepare("
            SELECT question as question_text, choice_a, choice_b, choice_c, choice_d, 
                   correct_answer, explanation, difficulty
            FROM learning_questions
            WHERE domain_id = ? OR part_id = ?
            LIMIT 30
        ");
        $stmt->execute([$domainId, $partId]);
        $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also fetch psychiatric reviewer details
        $overview = $ctx['overview'] ?? '';
        $criteria = $ctx['diagnostic_criteria'] ?? '';
        $diffText = $ctx['differential_diagnosis'] ?? '';
        $clinicalExample = $ctx['clinical_example'] ?? '';

        $results = [];
        $difficulties = !empty($diffMix) ? $diffMix : ['moderate', 'difficult', 'very_difficult', 'board_style_case'];
        $types = ['differential_diagnosis', 'clinical_application', 'best_answer', 'case_analysis'];
        $validBlooms = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'];

        $sourceHash = sha1(($overview . $criteria) ?: "domain_{$domainId}_part_{$partId}");

        $ages = [
            '19-year-old university student', '23-year-old graphic designer', '28-year-old software engineer',
            '33-year-old nurse practitioner', '38-year-old high school educator', '42-year-old financial analyst',
            '47-year-old corporate director', '54-year-old electrical engineer', '59-year-old public administrator',
            '64-year-old library curator', '71-year-old retired architect', '21-year-old apprentice mechanic',
            '36-year-old physical therapist', '49-year-old investigative journalist'
        ];
        $settings = [
            'at an outpatient comprehensive psychiatric evaluation center',
            'during an intake assessment at a university behavioral medicine service',
            'within a psychiatric consultation-liaison hospital unit',
            'during a diagnostic review at an acute behavioral stabilization facility',
            'at a community mental health specialized mood and psychopathology program',
            'during a forensic psychiatric diagnostic interview clinic',
            'in a specialized diagnostic research and assessment clinic',
            'in an ambulatory psychiatric specialty consultation room'
        ];
        $clinicalAngles = [
            'primary symptom duration and DSM-5 inclusion threshold criteria',
            'differential diagnosis against overlapping affective or thought disorders',
            'diagnostic specifiers, severity stratification, and clinical course',
            'distinguishing primary psychiatric etiology from medical or substance-induced mimics',
            'identifying core psychopathological features required for differential confirmation',
            'evidence-based clinical formulation under Study Hall diagnostic archives',
            'evaluating functional impairment and diagnostic hierarchy considerations',
            'diagnostic assessment of symptom frequency and threshold persistence'
        ];

        for ($i = 0; $i < $count; $i++) {
            $diff = $difficulties[$i % count($difficulties)];
            $seedIdx = $i + 1;
            $ptAge = $ages[($i * 5 + $userSeed * 7 + $partId * 11) % count($ages)];
            $setting = $settings[($i * 3 + $userSeed * 4 + $domainId * 9) % count($settings)];
            $angle = $clinicalAngles[($i * 2 + $userSeed * 3 + $partId * 5) % count($clinicalAngles)];

            if (!empty($bloomMix)) {
                $bloomLevel = $bloomMix[$i % count($bloomMix)];
                $qType = match($bloomLevel) {
                    'remember' => 'recall',
                    'understand' => 'recognition',
                    'apply' => 'clinical_application',
                    'analyze' => 'differential_diagnosis',
                    'evaluate' => 'best_answer',
                    'create' => 'case_analysis',
                    default => $types[$i % count($types)]
                };
            } else {
                $qType = $types[$i % count($types)];
                $bloomLevel = match($qType) {
                    'recall' => 'remember',
                    'recognition' => 'understand',
                    'clinical_application' => 'apply',
                    'differential_diagnosis' => 'analyze',
                    'best_answer' => 'evaluate',
                    default => $validBlooms[$i % count($validBlooms)]
                };
            }

            $offset = ($userSeed * 3 + $i);
            if (!empty($existing) && count($existing) > 0) {
                $base = $existing[$offset % count($existing)];
                $vignettePrefix = match(($i + $userSeed) % 6) {
                    0 => "Candidate Case #{$userSeed}-{$seedIdx}: A {$ptAge} is evaluated {$setting} focusing on {$angle} for {$partTitle}. ",
                    1 => "Diagnostic Protocol #{$userSeed}-{$seedIdx}: During a structured psychiatric intake {$setting} regarding {$domainName} ({$partTitle}), an evaluation of a {$ptAge} reveals: ",
                    2 => "Clinical Review #{$userSeed}-{$seedIdx}: A comprehensive evaluation is conducted {$setting} for a {$ptAge} exhibiting features of {$partTitle}. Reviewing {$angle}: ",
                    3 => "Differential Analysis #{$userSeed}-{$seedIdx}: In establishing the authoritative clinical differential diagnosis for {$partTitle} within {$domainName}, the evaluating clinician observes in a {$ptAge}: ",
                    4 => "Case Formulation #{$userSeed}-{$seedIdx}: A {$ptAge} is assessed {$setting} regarding diagnostic criteria for {$partTitle}. In analyzing {$angle}: ",
                    default => "DSM-5 Assessment Review #{$userSeed}-{$seedIdx}: Evaluating diagnostic criteria and {$angle} for {$partTitle} under Study Hall guidelines: "
                };

                $questionText = $vignettePrefix . ltrim($base['question_text']);
                $choiceA = $base['choice_a'];
                $choiceB = $base['choice_b'];
                $choiceC = $base['choice_c'];
                $choiceD = $base['choice_d'];
                $correct = strtoupper($base['correct_answer']);
                $explanation = $base['explanation'] ?: "Under DSM-5 criteria established in Study Hall for {$domainName} ({$partTitle}), this clinical feature confirms the primary diagnostic assessment while appropriately ruling out other conditions.";
            } else {
                $cleanCriteria = substr(strip_tags($criteria ?: $overview), 0, 160);
                $cleanDiff = substr(strip_tags($diffText ?: "related psychopathology"), 0, 140);
                $questionText = "Candidate Case #{$userSeed}-{$seedIdx}: A {$ptAge} presents {$setting} with clinical findings reflective of {$partTitle} ({$domainName}), specifically regarding {$angle}. Assessment records: {$cleanCriteria}. When evaluating this clinical presentation against {$cleanDiff}, which conclusion represents the most authoritative DSM-5 diagnostic formulation?";

                $choiceA = "The symptom duration, severity, and functional impairment satisfy DSM-5 diagnostic criteria for {$partTitle}.";
                $choiceB = "The clinical presentation is best attributed to an underlying general medical condition or organic etiology.";
                $choiceC = "The symptoms do not meet the minimum active duration requirement mandated for {$partTitle}.";
                $choiceD = "The clinical manifestations represent an adjustment reaction or subthreshold presentation rather than {$partTitle}.";
                $correct = 'A';
                $explanation = "According to the DSM-5 diagnostic criteria in Study Hall for {$domainName} ({$partTitle}), the documented symptom profile meets the essential diagnostic criteria and severity thresholds while excluding competing differentials.";
            }

            $qHash = sha1(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $questionText)));

            $results[] = [
                'domain_id' => $domainId,
                'study_part_id' => $partId,
                'question_text' => $questionText,
                'choice_a' => $choiceA,
                'choice_b' => $choiceB,
                'choice_c' => $choiceC,
                'choice_d' => $choiceD,
                'correct_choice' => $correct,
                'explanation' => $explanation,
                'difficulty' => $diff,
                'question_type' => $qType,
                'bloom_level' => $bloomLevel,
                'concept_key' => "DSM5_D{$domainId}_P{$partId}_cand{$userSeed}_{$seedIdx}",
                'source_hash' => $sourceHash,
                'question_hash' => $qHash,
                'generation_version' => 'study_hall_curriculum_v1.0'
            ];
        }

        return $results;
    }
}
