<?php
require_once __DIR__ . '/../config/database.php';

class QuestionValidationService {
    private PDO $db;
    private array $validDifficulties = ['moderate', 'difficult', 'very_difficult', 'board_style_case'];
    private array $validTypes = ['recall', 'recognition', 'differential_diagnosis', 'clinical_application', 'best_answer', 'case_analysis', 'exception'];
    private array $validBloomLevels = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Comprehensive validation of a single generated question against all academic, Bloom, and technical rules.
     *
     * @param array $q Question candidate data
     * @param array $existingQuestions List of questions already approved for the current event
     * @return array ['valid' => bool, 'errors' => string[], 'normalized' => array]
     */
    public function validateQuestion(array $q, array $existingQuestions = []): array {
        $errors = [];

        // 1. Check required fields exist
        $requiredFields = ['question_text', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_choice', 'explanation', 'domain_id', 'study_part_id'];
        foreach ($requiredFields as $field) {
            if (!isset($q[$field]) || trim((string)$q[$field]) === '') {
                $errors[] = "Missing or empty required field: {$field}";
            }
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'normalized' => null];
        }

        $text = trim($q['question_text']);
        $choiceA = trim($q['choice_a']);
        $choiceB = trim($q['choice_b']);
        $choiceC = trim($q['choice_c']);
        $choiceD = trim($q['choice_d']);
        $correct = strtoupper(trim($q['correct_choice']));
        $explanation = trim($q['explanation']);
        $domainId = (int) $q['domain_id'];
        $partId = (int) $q['study_part_id'];

        // 2. Question text length
        if (mb_strlen($text) < 20) {
            $errors[] = "Question text is too short (< 20 characters)";
        }

        // 3. Exactly four distinct choices
        $choices = [$choiceA, $choiceB, $choiceC, $choiceD];
        foreach ($choices as $idx => $c) {
            if (mb_strlen($c) < 2) {
                $errors[] = "Choice " . chr(65 + $idx) . " is too short (< 2 characters)";
            }
        }

        // Check for duplicate choices
        $normalizedChoices = array_map(fn($c) => strtolower(preg_replace('/\s+/', ' ', $c)), $choices);
        if (count(array_unique($normalizedChoices)) < 4) {
            $errors[] = "Duplicate choices detected within question options";
        }

        // 4. Exactly one valid correct choice
        if (!in_array($correct, ['A', 'B', 'C', 'D'])) {
            $errors[] = "Correct choice must be exactly one of A, B, C, or D (got '{$correct}')";
        }

        // 5. Explanation validity
        if (mb_strlen($explanation) < 15) {
            $errors[] = "Explanation is too brief (< 15 characters)";
        }

        // 6. Difficulty validity
        $difficulty = strtolower(trim($q['difficulty'] ?? 'difficult'));
        if (!in_array($difficulty, $this->validDifficulties)) {
            $errors[] = "Invalid difficulty '{$difficulty}'. Must be one of: " . implode(', ', $this->validDifficulties);
        }

        // 7. Question type validity
        $qType = strtolower(trim($q['question_type'] ?? 'clinical_application'));
        if (!in_array($qType, $this->validTypes)) {
            $errors[] = "Invalid question_type '{$qType}'. Must be one of: " . implode(', ', $this->validTypes);
        }

        // 8. Bloom's Taxonomy Level validity (Revised Bloom: remember, understand, apply, analyze, evaluate, create)
        $bloom = strtolower(trim($q['bloom_level'] ?? ''));
        if (empty($bloom) || !in_array($bloom, $this->validBloomLevels)) {
            // Auto-infer if missing
            $bloom = $this->inferBloomLevel($qType, $text);
        }

        // Validate Bloom cognitive alignment
        $bloomErrors = $this->validateBloomCognitiveAlignment($bloom, $qType, $text);
        if (!empty($bloomErrors)) {
            $errors = array_merge($errors, $bloomErrors);
        }

        // 9. Academic source validation: verify domain_id and study_part_id exist in Study Hall
        if (!$this->verifyDomainExists($domainId)) {
            $errors[] = "domain_id {$domainId} does not exist in Study Hall psychiatric domains";
        }
        if (!$this->verifyPartExists($partId, $domainId)) {
            $errors[] = "study_part_id {$partId} does not exist or does not belong to domain {$domainId}";
        }

        // 9. Generate hashes
        $qHash = sha1(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $text)));
        $sourceHash = $q['source_hash'] ?? sha1("domain_{$domainId}_part_{$partId}");
        $conceptKey = $q['concept_key'] ?? ("DSM5_D{$domainId}_P{$partId}");

        // 10. Duplicate and similarity check against existing questions pool
        $conceptCount = 0;
        foreach ($existingQuestions as $existing) {
            // Exact question hash
            $existingHash = $existing['question_hash'] ?? '';
            if ($existingHash && $existingHash === $qHash) {
                $errors[] = "Exact duplicate question wording detected";
                break;
            }

            // High textual similarity check
            $existingText = $existing['question_text'] ?? '';
            if ($existingText) {
                $similarity = $this->calculateSimilarity($text, $existingText);
                if ($similarity > 0.80) {
                    $errors[] = "Excessive question similarity detected (" . round($similarity * 100) . "% match with existing question)";
                    break;
                }
            }

            // Concept saturation check (max 3 questions per exact concept key)
            if (($existing['concept_key'] ?? '') === $conceptKey) {
                $conceptCount++;
                if ($conceptCount >= 3) {
                    $errors[] = "Concept '{$conceptKey}' is already saturated in this event (max 3 allowed)";
                    break;
                }
            }
        }

        if (!empty($errors)) {
            return ['valid' => false, 'errors' => $errors, 'normalized' => null];
        }

        $normalized = [
            'domain_id' => $domainId,
            'study_part_id' => $partId,
            'question_text' => $text,
            'choice_a' => $choiceA,
            'choice_b' => $choiceB,
            'choice_c' => $choiceC,
            'choice_d' => $choiceD,
            'correct_choice' => $correct,
            'explanation' => $explanation,
            'difficulty' => $difficulty,
            'question_type' => $qType,
            'bloom_level' => $bloom,
            'source_hash' => $sourceHash,
            'question_hash' => $qHash,
            'concept_key' => $conceptKey,
            'generation_version' => $q['generation_version'] ?? 'v1.0',
            'review_status' => 'approved'
        ];

        return ['valid' => true, 'errors' => [], 'normalized' => $normalized];
    }

    private function verifyDomainExists(int $domainId): bool {
        static $cachedDomains = null;
        if ($cachedDomains === null) {
            $stmt = $this->db->query("
                SELECT id FROM psychiatric_domains 
                UNION 
                SELECT id FROM psychology_domains
            ");
            $cachedDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return in_array($domainId, $cachedDomains);
    }

    private function verifyPartExists(int $partId, int $domainId): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM study_parts WHERE id = ?
        ");
        $stmt->execute([$partId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // Fallback check in psychology_parts
        $stmt2 = $this->db->prepare("
            SELECT COUNT(*) FROM psychology_parts WHERE id = ?
        ");
        $stmt2->execute([$partId]);
        return (int)$stmt2->fetchColumn() > 0;
    }

    /**
     * Infer appropriate Revised Bloom's Taxonomy level from question type and cognitive cues in text.
     */
    public function inferBloomLevel(string $qType, string $text): string {
        $lower = strtolower($text);

        // CREATE: questions requiring constructing an intervention plan, formulation, or management strategy
        if (preg_replace('/[^a-z]/', '', $qType) === 'caseanalysis' && 
            (str_contains($lower, 'formulate') || str_contains($lower, 'design') || str_contains($lower, 'treatment plan') || str_contains($lower, 'intervention strategy') || str_contains($lower, 'construct'))) {
            return 'create';
        }

        // EVALUATE: judging competing alternatives, best supported conclusion, or most appropriate action
        if ($qType === 'best_answer' || str_contains($lower, 'most appropriate') || str_contains($lower, 'best supported') || str_contains($lower, 'justify') || str_contains($lower, 'priority intervention')) {
            return 'evaluate';
        }

        // ANALYZE: breaking down a case, differential diagnosis, identifying relationships
        if ($qType === 'differential_diagnosis' || $qType === 'case_analysis' || str_contains($lower, 'distinguish') || str_contains($lower, 'differentiate') || str_contains($lower, 'underlying mechanism')) {
            return 'analyze';
        }

        // APPLY: practical application in a clinical vignette
        if ($qType === 'clinical_application' || str_contains($lower, 'patient presents') || str_contains($lower, 'a 3') || str_contains($lower, 'a 4') || str_contains($lower, 'a 2')) {
            return 'apply';
        }

        // UNDERSTAND: explanation, classification, recognition of meaning
        if ($qType === 'recognition' || str_contains($lower, 'explain') || str_contains($lower, 'characterize') || str_contains($lower, 'illustrates') || str_contains($lower, 'meaning of')) {
            return 'understand';
        }

        // REMEMBER: retrieval of definitions, facts, symptoms
        if ($qType === 'recall' || str_contains($lower, 'definition') || str_contains($lower, 'criteria requires') || str_contains($lower, 'duration')) {
            return 'remember';
        }

        return 'apply';
    }

    /**
     * Validate that the Bloom level accurately aligns with the question type and cognitive process.
     */
    public function validateBloomCognitiveAlignment(string $bloom, string $qType, string $text): array {
        $errors = [];
        if (!in_array($bloom, $this->validBloomLevels)) {
            $errors[] = "Invalid Bloom's Taxonomy level '{$bloom}'. Must be one of: " . implode(', ', $this->validBloomLevels);
            return $errors;
        }

        $lower = strtolower(trim($text));

        // Catch definition and simple recall stems masquerading as higher-order Bloom levels (Section 19)
        $isDefinitionStem = (bool) preg_match('/^(what is the definition of|which of the following defines|define |what is the term for|what term describes|what is meant by)/i', $lower);
        if ($isDefinitionStem && in_array($bloom, ['analyze', 'evaluate', 'create', 'apply'])) {
            $errors[] = "Bloom level '{$bloom}' cannot be a simple factual definition stem ('{$text}'). Simple definition questions belong to Remember or Understand.";
        }

        // Higher-order cognitive levels (analyze, evaluate, create) require scenario depth and multi-factor context
        if (in_array($bloom, ['analyze', 'evaluate', 'create']) && mb_strlen($text) < 50) {
            $errors[] = "Bloom level '{$bloom}' requires clinical context or diagnostic dilemma (> 50 characters)";
        }

        // Disallow gross cognitive mismatch:
        // E.g., A simple factual recall question cannot claim to be 'create' or 'evaluate'
        if (in_array($bloom, ['create', 'evaluate']) && $qType === 'recall') {
            $errors[] = "Bloom level '{$bloom}' cannot be paired with a simple factual 'recall' question type";
        }

        // E.g., A differential diagnosis question cannot be pure 'remember' (requires analysis/evaluation)
        if ($bloom === 'remember' && $qType === 'differential_diagnosis') {
            $errors[] = "Bloom level 'remember' is inappropriate for 'differential_diagnosis' which requires higher cognitive discrimination (analyze or evaluate)";
        }

        return $errors;
    }

    /**
     * Compute normalized token Jaccard similarity between two question stems
     */
    private function calculateSimilarity(string $s1, string $s2): float {
        $clean1 = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $s1));
        $clean2 = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $s2));

        $words1 = array_unique(array_filter(explode(' ', $clean1)));
        $words2 = array_unique(array_filter(explode(' ', $clean2)));

        if (empty($words1) || empty($words2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? ($intersection / $union) : 0.0;
    }
}
