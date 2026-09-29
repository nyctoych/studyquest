<?php

class PsychiatricCurriculumData {
    public static function seed(PDO $db, bool $force = false): void {
        $curriculum = self::getData();

        // Check if 12 compiled domains are already correctly seeded with 120 parts
        $partCount = (int)$db->query("SELECT COUNT(*) FROM study_parts WHERE source_version = 'DSM-5'")->fetchColumn();
        $domainCount = (int)$db->query("SELECT COUNT(*) FROM psychiatric_domains WHERE status = 'active'")->fetchColumn();
        $qCount = (int)$db->query("SELECT COUNT(*) FROM learning_questions WHERE source_version = 'DSM-5'")->fetchColumn();

        if (!$force && $partCount === 120 && $domainCount === 12 && $qCount >= 100) {
            return;
        }

        $db->beginTransaction();

        try {
            // Delete anything with domain_id > 12 to guarantee 12 chapters max
            $db->exec("DELETE FROM questions WHERE domain_id > 12");
            $db->exec("DELETE FROM learning_questions WHERE domain_id > 12");
            $db->exec("DELETE FROM study_parts WHERE domain_id > 12");
            $db->exec("DELETE FROM psychology_parts WHERE domain_id > 12");
            $db->exec("DELETE FROM psychiatric_topics WHERE domain_id > 12");

            foreach ($curriculum as $domainId => $domainData) {
                // Refresh topics for this domain
                $delT = $db->prepare("DELETE FROM psychiatric_topics WHERE domain_id = ?");
                $delT->execute([$domainId]);

                $topicMap = [];
                foreach ($domainData['topics'] as $tOrder => $topicName) {
                    $tStmt = $db->prepare("
                        INSERT INTO psychiatric_topics (domain_id, name, description, display_order, source_reference, status)
                        VALUES (?, ?, ?, ?, ?, 'active')
                    ");
                    $srcRef = "DSM-5 — " . $domainData['name'] . " (" . $topicName . ")";
                    $tStmt->execute([$domainId, $topicName, "Core study focus for " . $topicName, $tOrder + 1, $srcRef]);
                    $topicMap[$tOrder + 1] = (int)$db->lastInsertId();
                }

                // If domain >= 7 (the newly compiled domains), remove previous questions so new ones take effect cleanly
                if ($domainId >= 7 || $force) {
                    $delQ = $db->prepare("DELETE FROM questions WHERE domain_id = ?");
                    $delQ->execute([$domainId]);
                    $delLq = $db->prepare("DELETE FROM learning_questions WHERE domain_id = ?");
                    $delLq->execute([$domainId]);
                }

                // 2. Seed 10 Parts for this domain
                foreach ($domainData['parts'] as $partNum => $part) {
                    $topicId = $topicMap[$part['topic_order'] ?? 1] ?? null;
                    $srcRef = "DSM-5 — " . $domainData['name'] . " (Part " . $partNum . ": " . $part['title'] . ")";

                    // study_parts
                    $spStmt = $db->prepare("
                        INSERT INTO study_parts 
                        (domain_id, topic_id, part_number, title, slug, description, part_type, display_order, overview, key_concepts, important_terms, diagnostic_criteria, specifiers, differential_diagnosis, clinical_example, summary_notes, xp_reward, coin_reward, passing_score, source_version, source_reference, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DSM-5', ?, 'published')
                        ON DUPLICATE KEY UPDATE
                            topic_id = VALUES(topic_id),
                            title = VALUES(title),
                            slug = VALUES(slug),
                            description = VALUES(description),
                            part_type = VALUES(part_type),
                            overview = VALUES(overview),
                            key_concepts = VALUES(key_concepts),
                            important_terms = VALUES(important_terms),
                            diagnostic_criteria = VALUES(diagnostic_criteria),
                            specifiers = VALUES(specifiers),
                            differential_diagnosis = VALUES(differential_diagnosis),
                            clinical_example = VALUES(clinical_example),
                            summary_notes = VALUES(summary_notes),
                            source_version = 'DSM-5',
                            source_reference = VALUES(source_reference),
                            status = 'published'
                    ");

                    $keyConceptsJson = json_encode($part['key_concepts'] ?? []);
                    $termsJson = json_encode($part['important_terms'] ?? []);

                    $spStmt->execute([
                        $domainId, $topicId, $partNum, $part['title'], $part['slug'], $part['description'],
                        $part['part_type'] ?? 'learn', $partNum,
                        $part['overview'] ?? '', $keyConceptsJson, $termsJson,
                        $part['diagnostic_criteria'] ?? null, $part['specifiers'] ?? null, $part['differential_diagnosis'] ?? null,
                        $part['clinical_example'] ?? null, $part['summary_notes'] ?? null,
                        $part['xp_reward'] ?? 50, $part['coin_reward'] ?? 25, $part['passing_score'] ?? 70,
                        $srcRef
                    ]);

                    $studyPartId = (int)$db->lastInsertId();
                    if (!$studyPartId) {
                        $checkSp = $db->prepare("SELECT id FROM study_parts WHERE domain_id = ? AND part_number = ?");
                        $checkSp->execute([$domainId, $partNum]);
                        $studyPartId = (int)$checkSp->fetchColumn();
                    }

                    // Mirror into psychology_parts
                    $ppStmt = $db->prepare("
                        INSERT INTO psychology_parts 
                        (domain_id, part_number, title, slug, description, part_type, overview, key_concepts, important_terms, diagnostic_criteria, specifiers, differential_diagnosis, clinical_example, summary_notes, xp_reward, coin_reward, passing_score, source_version, source_reference, is_published)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DSM-5', ?, 1)
                        ON DUPLICATE KEY UPDATE
                            title = VALUES(title),
                            slug = VALUES(slug),
                            description = VALUES(description),
                            part_type = VALUES(part_type),
                            overview = VALUES(overview),
                            key_concepts = VALUES(key_concepts),
                            important_terms = VALUES(important_terms),
                            diagnostic_criteria = VALUES(diagnostic_criteria),
                            specifiers = VALUES(specifiers),
                            differential_diagnosis = VALUES(differential_diagnosis),
                            clinical_example = VALUES(clinical_example),
                            summary_notes = VALUES(summary_notes),
                            source_version = 'DSM-5',
                            source_reference = VALUES(source_reference),
                            is_published = 1
                    ");

                    $ppStmt->execute([
                        $domainId, $partNum, $part['title'], $part['slug'], $part['description'],
                        $part['part_type'] ?? 'learn',
                        $part['overview'] ?? '', $keyConceptsJson, $termsJson,
                        $part['diagnostic_criteria'] ?? null, $part['specifiers'] ?? null, $part['differential_diagnosis'] ?? null,
                        $part['clinical_example'] ?? null, $part['summary_notes'] ?? null,
                        $part['xp_reward'] ?? 50, $part['coin_reward'] ?? 25, $part['passing_score'] ?? 70,
                        $srcRef
                    ]);

                    $psychPartId = (int)$db->lastInsertId();
                    if (!$psychPartId) {
                        $checkPp = $db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
                        $checkPp->execute([$domainId, $partNum]);
                        $psychPartId = (int)$checkPp->fetchColumn();
                    }

                    // 3. Seed study_content_sections
                    self::seedSectionsForPart($db, $studyPartId, $part, $srcRef);

                    // 4. Seed Questions for this Part
                    if (!empty($part['questions'])) {
                        $qCheck = $db->prepare("SELECT COUNT(*) FROM learning_questions WHERE part_id = ?");
                        $qCheck->execute([$psychPartId]);
                        if ((int)$qCheck->fetchColumn() === 0) {
                            foreach ($part['questions'] as $q) {
                                self::insertQuestion($db, $domainId, $topicId, $studyPartId, $psychPartId, $q, $srcRef);
                            }
                        }
                    }
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function seedSectionsForPart(PDO $db, int $partId, array $part, string $srcRef): void {
        $sections = [
            ['section_type' => 'overview', 'title' => 'Overview & Clinical Doctrine', 'content' => $part['overview'] ?? ''],
            ['section_type' => 'diagnostic_criteria', 'title' => 'DSM-5 Diagnostic Criteria Formulation', 'content' => $part['diagnostic_criteria'] ?? ''],
            ['section_type' => 'specifiers', 'title' => 'Specifiers, Subtypes & Severity Tiers', 'content' => $part['specifiers'] ?? ''],
            ['section_type' => 'differential_diagnosis', 'title' => 'Differential Diagnosis & Boundary Distinctions', 'content' => $part['differential_diagnosis'] ?? ''],
            ['section_type' => 'case_scenario', 'title' => 'Educational Case Scenario', 'content' => $part['clinical_example'] ?? ''],
            ['section_type' => 'summary_notes', 'title' => 'High-Yield Board Review Notes', 'content' => $part['summary_notes'] ?? ''],
        ];

        $delStmt = $db->prepare("DELETE FROM study_content_sections WHERE part_id = ?");
        $delStmt->execute([$partId]);

        $insStmt = $db->prepare("
            INSERT INTO study_content_sections (part_id, section_type, title, content, display_order, source_reference, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $order = 1;
        foreach ($sections as $sec) {
            if (!empty($sec['content'])) {
                $insStmt->execute([$partId, $sec['section_type'], $sec['title'], $sec['content'], $order++, $srcRef]);
            }
        }
    }

    private static function insertQuestion(PDO $db, int $domainId, ?int $topicId, int $studyPartId, int $psychPartId, array $q, string $srcRef): void {
        $correct = strtolower(trim($q['correct_answer']));
        $correctUpper = strtoupper($correct);
        $diff = strtolower($q['difficulty'] ?? 'intermediate');

        // Insert into centralized questions table
        $qStmt = $db->prepare("
            INSERT INTO questions (domain_id, topic_id, part_id, category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty, source_version, source_reference, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DSM-5', ?, 'approved')
        ");
        $qStmt->execute([
            $domainId, $topicId, $studyPartId, $domainId,
            $q['question'], $q['choice_a'], $q['choice_b'], $q['choice_c'], $q['choice_d'],
            $correct, $q['explanation'], $diff, $srcRef
        ]);

        // Mirror into learning_questions for part-specific assessment and battle retrieval
        $lqStmt = $db->prepare("
            INSERT INTO learning_questions (domain_id, part_id, topic_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, explanation, difficulty, is_scenario, source_version, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DSM-5', 'active')
        ");
        $isScenario = (stripos($q['question'], 'scenario') !== false || stripos($q['question'], 'case') !== false || stripos($q['question'], 'presents with') !== false) ? 1 : 0;
        $lqStmt->execute([
            $domainId, $psychPartId, $topicId,
            $q['question'], $q['choice_a'], $q['choice_b'], $q['choice_c'], $q['choice_d'],
            $correctUpper, $q['explanation'], $diff, $isScenario
        ]);
    }

    private static function seedQuestionsOnly(PDO $db, array $curriculum): void {
        // Ensure learning_questions has questions for all parts
        $count = (int)$db->query("SELECT COUNT(*) FROM learning_questions WHERE source_version = 'DSM-5'")->fetchColumn();
        if ($count > 50) return;

        foreach ($curriculum as $domainId => $domainData) {
            foreach ($domainData['parts'] as $partNum => $part) {
                if (empty($part['questions'])) continue;

                $pStmt = $db->prepare("SELECT id FROM psychology_parts WHERE domain_id = ? AND part_number = ?");
                $pStmt->execute([$domainId, $partNum]);
                $psychPartId = (int)$pStmt->fetchColumn();

                $spStmt = $db->prepare("SELECT id, topic_id FROM study_parts WHERE domain_id = ? AND part_number = ?");
                $spStmt->execute([$domainId, $partNum]);
                $spRow = $spStmt->fetch();
                $studyPartId = $spRow ? (int)$spRow['id'] : $psychPartId;
                $topicId = $spRow ? (int)$spRow['topic_id'] : null;

                $srcRef = "DSM-5 — " . $domainData['name'] . " (Part " . $partNum . ")";
                foreach ($part['questions'] as $q) {
                    self::insertQuestion($db, $domainId, $topicId, $studyPartId, $psychPartId, $q, $srcRef);
                }
            }
        }
    }

    public static function getData(): array {
        require_once __DIR__ . '/PsychiatricCurriculumDataset.php';
        return PsychiatricCurriculumDataset::get();
    }
}
