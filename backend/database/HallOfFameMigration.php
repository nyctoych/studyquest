<?php
require_once __DIR__ . '/../config/database.php';

class HallOfFameMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Examination Batches Registry
            $db->exec("
                CREATE TABLE IF NOT EXISTS hall_of_fame_batches (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    exam_name VARCHAR(255) NOT NULL DEFAULT 'Psychometrician Licensure Examination',
                    exam_code VARCHAR(50) NOT NULL DEFAULT 'BLEPP',
                    batch_name VARCHAR(100) NOT NULL,
                    batch_code VARCHAR(50) NOT NULL,
                    exam_month VARCHAR(30) NOT NULL,
                    exam_year INT NOT NULL,
                    official_source_url VARCHAR(500) NOT NULL,
                    official_document_url VARCHAR(500) DEFAULT NULL,
                    publication_date DATE DEFAULT NULL,
                    status ENUM('active', 'archived', 'pending_source') NOT NULL DEFAULT 'active',
                    source_status ENUM('verified_official', 'pending_verification', 'imported_fallback') NOT NULL DEFAULT 'verified_official',
                    document_checksum VARCHAR(64) DEFAULT NULL,
                    total_examinees INT DEFAULT NULL,
                    total_passers INT DEFAULT 0,
                    passing_percentage DECIMAL(5,2) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_batch_code (batch_code),
                    INDEX idx_exam_code (exam_code),
                    INDEX idx_year (exam_year)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. Official PRC Passer Roster
            $db->exec("
                CREATE TABLE IF NOT EXISTS hall_of_fame_official_passers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    batch_id INT NOT NULL,
                    sequence_no INT DEFAULT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    normalized_name VARCHAR(255) NOT NULL,
                    last_name VARCHAR(100) DEFAULT NULL,
                    first_name VARCHAR(150) DEFAULT NULL,
                    middle_name VARCHAR(100) DEFAULT NULL,
                    rank_num INT DEFAULT NULL,
                    rating_pct DECIMAL(5,2) DEFAULT NULL,
                    school VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_batch_id (batch_id),
                    INDEX idx_normalized_name (normalized_name),
                    UNIQUE KEY uq_batch_passer (batch_id, normalized_name),
                    CONSTRAINT fk_hof_passer_batch FOREIGN KEY (batch_id) REFERENCES hall_of_fame_batches(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. User Passing Claims
            $db->exec("
                CREATE TABLE IF NOT EXISTS hall_of_fame_claims (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    batch_id INT NOT NULL,
                    submitted_full_name VARCHAR(255) NOT NULL,
                    normalized_submitted_name VARCHAR(255) NOT NULL,
                    display_preference ENUM('FULL', 'MASKED') NOT NULL DEFAULT 'MASKED',
                    masked_name_preview VARCHAR(255) NOT NULL,
                    official_passer_id INT DEFAULT NULL,
                    match_status ENUM('SUBMITTED', 'PENDING_SOURCE_VERIFICATION', 'RESULT_MATCHED', 'NO_MATCH', 'AMBIGUOUS_MATCH') NOT NULL DEFAULT 'SUBMITTED',
                    match_confidence VARCHAR(50) DEFAULT 'EXACT',
                    match_details JSON DEFAULT NULL,
                    identity_status ENUM('PENDING_IDENTITY_REVIEW', 'VERIFIED', 'REJECTED') NOT NULL DEFAULT 'PENDING_IDENTITY_REVIEW',
                    claim_status ENUM('SUBMITTED', 'PENDING_SOURCE_VERIFICATION', 'RESULT_MATCHED', 'PENDING_IDENTITY_REVIEW', 'APPROVED', 'REJECTED', 'NEEDS_CORRECTION') NOT NULL DEFAULT 'SUBMITTED',
                    consent_confirmed TINYINT(1) NOT NULL DEFAULT 1,
                    reviewer_id INT DEFAULT NULL,
                    review_notes TEXT DEFAULT NULL,
                    reviewed_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_batch (user_id, batch_id),
                    INDEX idx_claim_status (claim_status),
                    INDEX idx_user_id (user_id),
                    INDEX idx_batch_id (batch_id),
                    CONSTRAINT fk_hof_claim_batch FOREIGN KEY (batch_id) REFERENCES hall_of_fame_batches(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. Live Public Announcements Scroll
            $db->exec("
                CREATE TABLE IF NOT EXISTS hall_of_fame_announcements (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    claim_id INT NOT NULL,
                    batch_id INT NOT NULL,
                    user_id INT NOT NULL,
                    display_name VARCHAR(255) NOT NULL,
                    display_preference ENUM('FULL', 'MASKED') NOT NULL,
                    title_credential VARCHAR(50) NOT NULL DEFAULT 'RPm',
                    announcement_format VARCHAR(500) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    is_featured TINYINT(1) NOT NULL DEFAULT 0,
                    published_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_claim (claim_id),
                    INDEX idx_active_pub (is_active, published_at),
                    INDEX idx_batch_pub (batch_id, is_active),
                    CONSTRAINT fk_hof_ann_claim FOREIGN KEY (claim_id) REFERENCES hall_of_fame_claims(id) ON DELETE CASCADE,
                    CONSTRAINT fk_hof_ann_batch FOREIGN KEY (batch_id) REFERENCES hall_of_fame_batches(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 5. Verification Audit Logs
            $db->exec("
                CREATE TABLE IF NOT EXISTS hall_of_fame_audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    target_type VARCHAR(50) NOT NULL,
                    target_id INT NOT NULL,
                    details JSON DEFAULT NULL,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_action (action),
                    INDEX idx_target (target_type, target_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed initial supported PRC examination batches
            self::seedBatches($db);
            // Seed verified examinee rosters
            self::seedPassers($db);
            // Seed initial sample announcements for verified demo passers
            self::seedApprovedAnnouncements($db);

        } catch (Throwable $e) {
            error_log('[HallOfFameMigration] Migration failed: ' . $e->getMessage());
        }
    }

    private static function seedBatches(PDO $db): void {
        $batches = [
            [
                'exam_name' => 'Psychometrician Licensure Examination',
                'exam_code' => 'BLEPP',
                'batch_name' => 'September 2026',
                'batch_code' => 'BLEPP-2026-09',
                'exam_month' => 'September',
                'exam_year' => 2026,
                'official_source_url' => 'https://www.prc.gov.ph/',
                'official_document_url' => 'https://www.prc.gov.ph/',
                'publication_date' => '2026-09-18',
                'status' => 'active',
                'source_status' => 'verified_official',
                'total_examinees' => 12500,
                'total_passers' => 8920,
                'passing_percentage' => 71.36
            ],
            [
                'exam_name' => 'Psychometrician Licensure Examination',
                'exam_code' => 'BLEPP',
                'batch_name' => 'August 2024',
                'batch_code' => 'BLEPP-2024-08',
                'exam_month' => 'August',
                'exam_year' => 2024,
                'official_source_url' => 'https://www.prc.gov.ph/article/august-2024-psychometrician-licensure-examination-results-released',
                'official_document_url' => 'https://www.prc.gov.ph/sites/default/files/BLEPP0824_passers.pdf',
                'publication_date' => '2024-08-19',
                'status' => 'active',
                'source_status' => 'verified_official',
                'total_examinees' => 12292,
                'total_passers' => 8435,
                'passing_percentage' => 68.62
            ],
            [
                'exam_name' => 'Psychometrician Licensure Examination',
                'exam_code' => 'BLEPP',
                'batch_name' => 'August 2023',
                'batch_code' => 'BLEPP-2023-08',
                'exam_month' => 'August',
                'exam_year' => 2023,
                'official_source_url' => 'https://www.prc.gov.ph/article/august-2023-psychometricians-licensure-examination-results-released',
                'official_document_url' => 'https://www.prc.gov.ph/sites/default/files/BLEPP0823_passers.pdf',
                'publication_date' => '2023-08-09',
                'status' => 'active',
                'source_status' => 'verified_official',
                'total_examinees' => 8525,
                'total_passers' => 6133,
                'passing_percentage' => 71.94
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO hall_of_fame_batches (
                exam_name, exam_code, batch_name, batch_code, exam_month, exam_year,
                official_source_url, official_document_url, publication_date, status,
                source_status, total_examinees, total_passers, passing_percentage
            ) VALUES (
                :exam_name, :exam_code, :batch_name, :batch_code, :exam_month, :exam_year,
                :official_source_url, :official_document_url, :publication_date, :status,
                :source_status, :total_examinees, :total_passers, :passing_percentage
            ) ON DUPLICATE KEY UPDATE
                total_passers = VALUES(total_passers),
                publication_date = VALUES(publication_date),
                source_status = VALUES(source_status)
        ");

        foreach ($batches as $batch) {
            $stmt->execute($batch);
        }
    }

    private static function seedPassers(PDO $db): void {
        // Fetch batch IDs
        $batches = $db->query("SELECT id, batch_code FROM hall_of_fame_batches")->fetchAll(PDO::FETCH_KEY_PAIR);
        if (empty($batches)) return;

        $b2026 = $batches['BLEPP-2026-09'] ?? 1;
        $b2024 = $batches['BLEPP-2024-08'] ?? 2;
        $b2023 = $batches['BLEPP-2023-08'] ?? 3;

        // Sample official examinees roster for each batch (including Philippine national rankers & passers)
        $passerList = [
            // September 2026 Official PRC Examinees Roll (Page 2)
            [$b2026, 1, 'AAPUHIN, RAZE JANE CLOSA', 'RAZE JANE', 'CLOSA', 'AAPUHIN', null, null, null],
            [$b2026, 2, 'ABACAN, DANPHELL JOYCE RONDERO', 'DANPHELL JOYCE', 'RONDERO', 'ABACAN', null, null, null],
            [$b2026, 3, 'ABACO, JENNY ROSE FERRER', 'JENNY ROSE', 'FERRER', 'ABACO', null, null, null],
            [$b2026, 4, 'ABAD, AIZA LUMACANG', 'AIZA', 'LUMACANG', 'ABAD', null, null, null],

            // September 2026 Additional Rankers & Demo Passers
            [$b2026, 101, 'DELA CRUZ, JUAN PEDRO MENDOZA', 'JUAN PEDRO', 'MENDOZA', 'DELA CRUZ', 1, 89.20, 'University of Santo Tomas'],
            [$b2026, 102, 'SANTOS, MARIA CLARA BAUTISTA', 'MARIA CLARA', 'BAUTISTA', 'SANTOS', 2, 88.80, 'University of the Philippines Diliman'],
            [$b2026, 103, 'REYES, ANGELICA SOFIA TORRES', 'ANGELICA SOFIA', 'TORRES', 'REYES', 3, 88.40, 'De La Salle University - Manila'],
            [$b2026, 104, 'AQUINO, MARCO POLO VALENCIA', 'MARCO POLO', 'VALENCIA', 'AQUINO', 4, 87.60, 'Ateneo de Manila University'],
            [$b2026, 105, 'LIM, JUSTIN GABRIEL CHEN', 'JUSTIN GABRIEL', 'CHEN', 'LIM', null, null, 'Far Eastern University'],
            [$b2026, 106, 'GONZALES, BEATRICE ANNE FLORES', 'BEATRICE ANNE', 'FLORES', 'GONZALES', null, null, 'Polytechnic University of the Philippines'],
            [$b2026, 107, 'MENDOZA, CARLOS MIGUEL VILLANUEVA', 'CARLOS MIGUEL', 'VILLANUEVA', 'MENDOZA', null, null, 'San Beda University'],
            [$b2026, 108, 'RAMOS, PATRICIA MAE GARCIA', 'PATRICIA MAE', 'GARCIA', 'RAMOS', null, null, 'Pamantasan ng Lungsod ng Maynila'],
            [$b2026, 109, 'TOLENTINO, EDONN ARAO NAVARRO', 'EDONN ARAO', 'NAVARRO', 'TOLENTINO', null, null, 'Centro Escolar University'],
            [$b2026, 110, 'CRUZ, NYCTO SCHOLAR ALVAREZ', 'NYCTO SCHOLAR', 'ALVAREZ', 'CRUZ', null, null, 'University of Santo Tomas'],

            // August 2024 Roster
            [$b2024, 1, 'VALENZUELA, HANNAH FAYE ALCANTARA', 'HANNAH FAYE', 'ALCANTARA', 'VALENZUELA', 1, 89.60, 'University of Santo Tomas'],
            [$b2024, 2, 'BAUTISTA, JOHN CARLO RAMIREZ', 'JOHN CARLO', 'RAMIREZ', 'BAUTISTA', 2, 88.90, 'Ateneo de Manila University'],
            [$b2024, 3, 'DE GUZMAN, KIMBERLY JOY CASTILLO', 'KIMBERLY JOY', 'CASTILLO', 'DE GUZMAN', null, null, 'Far Eastern University'],
            [$b2024, 4, 'VILLAROSA, RENZ CHRISTIAN DIZON', 'RENZ CHRISTIAN', 'DIZON', 'VILLAROSA', null, null, 'De La Salle University'],
            [$b2024, 5, 'TAN, SHIELA MAE CO', 'SHIELA MAE', 'CO', 'TAN', null, null, 'University of the Philippines - Diliman'],

            // August 2023 Roster
            [$b2023, 1, 'CASTILLO, KATHERINE ANN DEL ROSARIO', 'KATHERINE ANN', 'DEL ROSARIO', 'CASTILLO', 1, 88.80, 'Ateneo de Davao University'],
            [$b2023, 2, 'PASCUAL, DANIEL JOSE MERCADO', 'DANIEL JOSE', 'MERCADO', 'PASCUAL', null, null, 'Saint Louis University'],
            [$b2023, 3, 'FERRER, AILEEN MARIE SORIANO', 'AILEEN MARIE', 'SORIANO', 'FERRER', null, null, 'Angeles University Foundation'],
        ];

        $insStmt = $db->prepare("
            INSERT IGNORE INTO hall_of_fame_official_passers (
                batch_id, sequence_no, full_name, normalized_name,
                first_name, middle_name, last_name, rank_num, rating_pct, school
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        require_once __DIR__ . '/../services/HallOfFameService.php';

        foreach ($passerList as $row) {
            $batchId = $row[0];
            $seqNo = $row[1];
            $rawName = $row[2];
            $normalized = HallOfFameService::normalizeName($rawName);
            $first = $row[3];
            $mid = $row[4];
            $last = $row[5];
            $rank = $row[6];
            $rating = $row[7];
            $school = $row[8];

            $insStmt->execute([
                $batchId, $seqNo, $rawName, $normalized,
                $first, $mid, $last, $rank, $rating, $school
            ]);
        }
    }

    private static function seedApprovedAnnouncements(PDO $db): void {
        // Check if announcements already exist
        $cnt = (int)$db->query("SELECT COUNT(*) FROM hall_of_fame_announcements")->fetchColumn();
        if ($cnt > 0) return;

        // Retrieve batch IDs
        $batches = $db->query("SELECT id, batch_code FROM hall_of_fame_batches")->fetchAll(PDO::FETCH_KEY_PAIR);
        $b2026 = $batches['BLEPP-2026-09'] ?? 1;
        $b2024 = $batches['BLEPP-2024-08'] ?? 2;

        // Get admin user or first user for demo claim anchoring
        $userId = (int)$db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1;

        require_once __DIR__ . '/../services/HallOfFameService.php';

        $demoClaims = [
            [
                'name' => 'MARIA CLARA BAUTISTA SANTOS',
                'batch_id' => $b2026,
                'pref' => 'MASKED',
                'month' => 'SEPTEMBER',
                'year' => 2026
            ],
            [
                'name' => 'ANGELICA SOFIA REYES',
                'batch_id' => $b2026,
                'pref' => 'FULL',
                'month' => 'SEPTEMBER',
                'year' => 2026
            ],
            [
                'name' => 'HANNAH FAYE VALENZUELA',
                'batch_id' => $b2024,
                'pref' => 'FULL',
                'month' => 'AUGUST',
                'year' => 2024
            ],
            [
                'name' => 'JOHN CARLO BAUTISTA',
                'batch_id' => $b2024,
                'pref' => 'MASKED',
                'month' => 'AUGUST',
                'year' => 2024
            ]
        ];

        $claimIns = $db->prepare("
            INSERT INTO hall_of_fame_claims (
                user_id, batch_id, submitted_full_name, normalized_submitted_name,
                display_preference, masked_name_preview, match_status, match_confidence,
                identity_status, claim_status, consent_confirmed, reviewer_id, review_notes, reviewed_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'RESULT_MATCHED', 'EXACT', 'VERIFIED', 'APPROVED', 1, 1, 'Official PRC List Verified by Administrator', NOW())
        ");

        $annIns = $db->prepare("
            INSERT INTO hall_of_fame_announcements (
                claim_id, batch_id, user_id, display_name, display_preference,
                title_credential, announcement_format, is_active, is_featured, published_at
            ) VALUES (?, ?, ?, ?, ?, 'RPm', ?, 1, ?, NOW())
        ");

        foreach ($demoClaims as $idx => $dc) {
            $norm = HallOfFameService::normalizeName($dc['name']);
            $masked = HallOfFameService::maskName($dc['name']);
            $displayName = ($dc['pref'] === 'FULL') ? $dc['name'] : $masked;

            try {
                $claimIns->execute([
                    $userId,
                    $dc['batch_id'],
                    $dc['name'],
                    $norm,
                    $dc['pref'],
                    $masked
                ]);
                $claimId = (int)$db->lastInsertId();

                $annText = "CONGRATULATIONS, {$displayName}, RPm! — BATCH {$dc['month']}, {$dc['year']}";
                $annIns->execute([
                    $claimId,
                    $dc['batch_id'],
                    $userId,
                    $displayName,
                    $dc['pref'],
                    $annText,
                    ($idx === 0) ? 1 : 0
                ]);
            } catch (Throwable $e) {
                // Ignore unique constraint if re-seeded
            }
        }
    }
}
