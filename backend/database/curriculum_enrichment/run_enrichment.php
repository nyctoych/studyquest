<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/Database.php';

try {
    $db = Database::getInstance();
    echo "Connected to database successfully.\n";

    // Check pre-existing progress row count
    $initialProgressCount = $db->query("SELECT COUNT(*) FROM user_part_progress")->fetchColumn();
    echo "Current user_part_progress rows before enrichment: {$initialProgressCount}\n";

    $domainFiles = [
        1 => __DIR__ . '/domain1_neurodevelopmental.php',
        2 => __DIR__ . '/domain2_schizophrenia.php',
        3 => __DIR__ . '/domain3_bipolar.php',
        4 => __DIR__ . '/domain4_depressive.php',
        5 => __DIR__ . '/domain5_anxiety.php',
        6 => __DIR__ . '/domain6_ocd.php',
        7 => __DIR__ . '/domain7_trauma.php',
        8 => __DIR__ . '/domain8_somatic.php',
        9 => __DIR__ . '/domain9_eating.php',
        10 => __DIR__ . '/domain10_sleep_sexual_gender.php',
        11 => __DIR__ . '/domain11_substance_disruptive.php',
        12 => __DIR__ . '/domain12_ncd_personality_paraphilic.php',
    ];

    $psychUpdateStmt = $db->prepare("
        UPDATE psychology_parts
        SET title = ?,
            slug = ?,
            description = ?,
            overview = ?,
            diagnostic_criteria = ?,
            specifiers = ?,
            differential_diagnosis = ?,
            clinical_example = ?,
            summary_notes = ?,
            key_concepts = ?,
            important_terms = ?
        WHERE domain_id = ? AND part_number = ?
    ");

    $studyUpdateStmt = $db->prepare("
        UPDATE study_parts
        SET title = ?,
            slug = ?,
            description = ?,
            overview = ?,
            diagnostic_criteria = ?,
            specifiers = ?,
            differential_diagnosis = ?,
            clinical_example = ?,
            summary_notes = ?,
            key_concepts = ?,
            important_terms = ?
        WHERE domain_id = ? AND part_number = ?
    ");

    $findStudyPartStmt = $db->prepare("SELECT id FROM study_parts WHERE domain_id = ? AND part_number = ?");
    $delSectionsStmt = $db->prepare("DELETE FROM study_content_sections WHERE part_id = ?");
    $insSectionStmt = $db->prepare("
        INSERT INTO study_content_sections (part_id, section_type, title, content, display_order, source_reference, status)
        VALUES (?, ?, ?, ?, ?, 'DSM-5', 'active')
    ");

    $totalPsychUpdated = 0;
    $totalStudyUpdated = 0;
    $totalSectionsInserted = 0;

    foreach ($domainFiles as $domainId => $filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("Enrichment file missing: {$filePath}");
        }

        $domainData = require $filePath;
        echo "\nProcessing Domain {$domainId} (" . count($domainData) . " parts)...\n";

        foreach ($domainData as $partNum => $p) {
            $keyConceptsJson = !empty($p['key_concepts']) ? json_encode($p['key_concepts'], JSON_UNESCAPED_UNICODE) : null;
            $importantTermsJson = !empty($p['important_terms']) ? json_encode($p['important_terms'], JSON_UNESCAPED_UNICODE) : null;

            // 1. Update psychology_parts
            $psychUpdateStmt->execute([
                $p['title'],
                $p['slug'],
                $p['description'],
                $p['overview'],
                $p['diagnostic_criteria'],
                $p['specifiers'],
                $p['differential_diagnosis'],
                $p['clinical_example'],
                $p['summary_notes'],
                $keyConceptsJson,
                $importantTermsJson,
                $domainId,
                $partNum
            ]);
            $totalPsychUpdated += $psychUpdateStmt->rowCount();

            // 2. Update study_parts
            $studyUpdateStmt->execute([
                $p['title'],
                $p['slug'],
                $p['description'],
                $p['overview'],
                $p['diagnostic_criteria'],
                $p['specifiers'],
                $p['differential_diagnosis'],
                $p['clinical_example'],
                $p['summary_notes'],
                $keyConceptsJson,
                $importantTermsJson,
                $domainId,
                $partNum
            ]);
            $totalStudyUpdated += $studyUpdateStmt->rowCount();

            // 3. Find study_part id
            $findStudyPartStmt->execute([$domainId, $partNum]);
            $studyPartId = $findStudyPartStmt->fetchColumn();

            if ($studyPartId) {
                $delSectionsStmt->execute([$studyPartId]);

                $sections = [
                    ['overview', 'Foundational Doctrine & Clinical Overview', $p['overview']],
                    ['diagnostic_criteria', 'DSM-5 Diagnostic Criteria & Symptom Thresholds', $p['diagnostic_criteria']],
                    ['specifiers', 'Subtypes, Specifiers & Severity Stratification', $p['specifiers']],
                    ['differential_diagnosis', 'Differential Diagnosis & Clinical Boundaries', $p['differential_diagnosis']],
                    ['case_scenario', 'Applied Clinical Vignette & Formulation', $p['clinical_example']],
                    ['summary_notes', 'Curricular Synthesis & Licensure Board Pearls', $p['summary_notes']],
                ];

                $order = 1;
                foreach ($sections as [$secType, $secTitle, $secContent]) {
                    if (!empty($secContent)) {
                        $insSectionStmt->execute([
                            $studyPartId,
                            $secType,
                            $secTitle,
                            $secContent,
                            $order++
                        ]);
                        $totalSectionsInserted++;
                    }
                }
            }
        }
    }

    $finalProgressCount = $db->query("SELECT COUNT(*) FROM user_part_progress")->fetchColumn();

    echo "\n============================================\n";
    echo "CURRICULUM ENRICHMENT COMPLETE!\n";
    echo "Psychology Parts Updated: {$totalPsychUpdated} (120 parts)\n";
    echo "Study Parts Updated: {$totalStudyUpdated} (120 parts)\n";
    echo "Study Content Sections Inserted: {$totalSectionsInserted} (720 sections)\n";
    echo "user_part_progress row count: {$finalProgressCount} (Preserved: " . ($initialProgressCount === $finalProgressCount ? "YES" : "NO") . ")\n";
    echo "============================================\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
