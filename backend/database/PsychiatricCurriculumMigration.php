<?php
require_once __DIR__ . '/../config/database.php';

class PsychiatricCurriculumMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Create psychiatric hierarchy tables
            self::createTables($db);

            // 2. Ensure columns on questions, question_attempts, psychology_domains, psychology_parts
            self::ensureColumns($db);

            // 3. Seed Psychiatric Domains
            self::seedDomains($db);

            // 4. Seed Topics & 10 Parts per Domain with DSM-5 Structured Reviewer Content
            self::seedCurriculumData($db);

            // 5. Seed Bosses for all 19 Chapters
            self::seedPsychiatricBosses($db);

        } catch (Throwable $e) {
            error_log("PsychiatricCurriculumMigration Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
        }
    }

    private static function createTables(PDO $db): void {
        // psychiatric_domains
        $db->exec("
            CREATE TABLE IF NOT EXISTS psychiatric_domains (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                icon VARCHAR(50) NOT NULL DEFAULT '🧠',
                color VARCHAR(30) NOT NULL DEFAULT '#8b5cf6',
                display_order INT NOT NULL DEFAULT 1,
                total_parts INT NOT NULL DEFAULT 10,
                source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5',
                source_reference VARCHAR(255) DEFAULT 'DSM-5',
                status ENUM('active','inactive','coming_soon') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // psychiatric_topics
        $db->exec("
            CREATE TABLE IF NOT EXISTS psychiatric_topics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                domain_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                display_order INT NOT NULL DEFAULT 1,
                source_reference VARCHAR(255) DEFAULT 'DSM-5',
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (domain_id) REFERENCES psychiatric_domains(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // study_parts
        $db->exec("
            CREATE TABLE IF NOT EXISTS study_parts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                domain_id INT NOT NULL,
                topic_id INT DEFAULT NULL,
                part_number INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                description TEXT,
                part_type ENUM('learn','review','practice','scenario','quiz','challenge','mastery') NOT NULL DEFAULT 'learn',
                display_order INT NOT NULL DEFAULT 1,
                overview LONGTEXT,
                key_concepts JSON DEFAULT NULL,
                important_terms JSON DEFAULT NULL,
                diagnostic_criteria LONGTEXT DEFAULT NULL,
                specifiers LONGTEXT DEFAULT NULL,
                differential_diagnosis LONGTEXT DEFAULT NULL,
                clinical_example LONGTEXT DEFAULT NULL,
                summary_notes LONGTEXT DEFAULT NULL,
                xp_reward INT NOT NULL DEFAULT 50,
                coin_reward INT NOT NULL DEFAULT 25,
                passing_score INT NOT NULL DEFAULT 70,
                source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5',
                source_reference VARCHAR(255) DEFAULT NULL,
                status ENUM('active','inactive','published','draft') NOT NULL DEFAULT 'published',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sp_domain_part (domain_id, part_number),
                FOREIGN KEY (domain_id) REFERENCES psychiatric_domains(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // study_content_sections
        $db->exec("
            CREATE TABLE IF NOT EXISTS study_content_sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                part_id INT NOT NULL,
                section_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                display_order INT NOT NULL DEFAULT 1,
                source_reference VARCHAR(255) DEFAULT 'DSM-5',
                status ENUM('active','inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (part_id) REFERENCES study_parts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    private static function ensureColumns(PDO $db): void {
        // Enhance psychology_domains for seamless compatibility
        $colCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'psychology_domains'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('source_version', $colCheck)) {
            $db->exec("ALTER TABLE psychology_domains ADD COLUMN source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5' AFTER total_parts");
        }
        if (!in_array('source_reference', $colCheck)) {
            $db->exec("ALTER TABLE psychology_domains ADD COLUMN source_reference VARCHAR(255) DEFAULT 'DSM-5' AFTER source_version");
        }

        // Enhance psychology_parts
        $pColCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'psychology_parts'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('diagnostic_criteria', $pColCheck)) {
            $db->exec("ALTER TABLE psychology_parts ADD COLUMN diagnostic_criteria LONGTEXT DEFAULT NULL AFTER important_terms");
        }
        if (!in_array('specifiers', $pColCheck)) {
            $db->exec("ALTER TABLE psychology_parts ADD COLUMN specifiers LONGTEXT DEFAULT NULL AFTER diagnostic_criteria");
        }
        if (!in_array('differential_diagnosis', $pColCheck)) {
            $db->exec("ALTER TABLE psychology_parts ADD COLUMN differential_diagnosis LONGTEXT DEFAULT NULL AFTER specifiers");
        }
        if (!in_array('source_version', $pColCheck)) {
            $db->exec("ALTER TABLE psychology_parts ADD COLUMN source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5' AFTER passing_score");
        }
        if (!in_array('source_reference', $pColCheck)) {
            $db->exec("ALTER TABLE psychology_parts ADD COLUMN source_reference VARCHAR(255) DEFAULT NULL AFTER source_version");
        }

        // Enhance questions
        try {
            $db->exec("ALTER TABLE questions DROP FOREIGN KEY questions_ibfk_2");
        } catch (Throwable $e) {}
        try {
            $db->exec("ALTER TABLE user_topic_performance DROP FOREIGN KEY user_topic_performance_ibfk_2");
        } catch (Throwable $e) {}
        try {
            $db->exec("ALTER TABLE questions MODIFY COLUMN category_id INT NULL DEFAULT 7");
        } catch (Throwable $e) {}

        $qColCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'questions'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('domain_id', $qColCheck)) {
            $db->exec("ALTER TABLE questions ADD COLUMN domain_id INT DEFAULT NULL AFTER id");
        }
        if (!in_array('part_id', $qColCheck)) {
            $db->exec("ALTER TABLE questions ADD COLUMN part_id INT DEFAULT NULL AFTER topic_id");
        }
        if (!in_array('source_version', $qColCheck)) {
            $db->exec("ALTER TABLE questions ADD COLUMN source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5' AFTER explanation");
        }
        try {
            $db->exec("ALTER TABLE questions MODIFY COLUMN status ENUM('draft','under_review','approved','rejected','archived') DEFAULT 'approved'");
        } catch (Throwable $e) {}

        // Enhance learning_questions
        $lqColCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'learning_questions'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('source_version', $lqColCheck)) {
            $db->exec("ALTER TABLE learning_questions ADD COLUMN source_version VARCHAR(50) NOT NULL DEFAULT 'DSM-5' AFTER explanation");
        }
        if (!in_array('topic_id', $lqColCheck)) {
            $db->exec("ALTER TABLE learning_questions ADD COLUMN topic_id INT DEFAULT NULL AFTER part_id");
        }
        if (!in_array('domain_id', $lqColCheck)) {
            $db->exec("ALTER TABLE learning_questions ADD COLUMN domain_id INT DEFAULT NULL AFTER id");
        }

        // Enhance question_attempts
        $qaColCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'question_attempts'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('domain_id', $qaColCheck)) {
            $db->exec("ALTER TABLE question_attempts ADD COLUMN domain_id INT DEFAULT NULL AFTER question_id");
        }
        if (!in_array('part_id', $qaColCheck)) {
            $db->exec("ALTER TABLE question_attempts ADD COLUMN part_id INT DEFAULT NULL AFTER topic_id");
        }

        // Ensure users avatar columns exist (used in BattleRating, Chronicle, Auth)
        $uColCheck = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('avatar_style', $uColCheck)) {
            $db->exec("ALTER TABLE users ADD COLUMN avatar_style VARCHAR(50) DEFAULT 'adventurer' AFTER nickname");
        }
        if (!in_array('avatar_seed', $uColCheck)) {
            $db->exec("ALTER TABLE users ADD COLUMN avatar_seed VARCHAR(100) DEFAULT 'default' AFTER avatar_style");
        }
        if (!in_array('avatar_options', $uColCheck)) {
            $db->exec("ALTER TABLE users ADD COLUMN avatar_options LONGTEXT DEFAULT NULL AFTER avatar_seed");
        }
    }

    public static function getDomainDefinitions(): array {
        return [
            1 => [
                'name' => 'Neurodevelopmental Disorders',
                'slug' => 'neurodevelopmental-disorders',
                'description' => 'Disorders with onset in the developmental period characterized by developmental deficits producing impairments of personal, social, academic, or occupational functioning.',
                'icon' => '🧬',
                'color' => '#8b5cf6',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Neurodevelopmental Disorders',
            ],
            2 => [
                'name' => 'Schizophrenia Spectrum and Other Psychotic Disorders',
                'slug' => 'schizophrenia-spectrum-psychotic-disorders',
                'description' => 'Abnormalities defined in one or more of five domains: delusions, hallucinations, disorganized thinking/speech, grossly disorganized or abnormal motor behavior (including catatonia), and negative symptoms.',
                'icon' => '👁️',
                'color' => '#6366f1',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Schizophrenia Spectrum and Other Psychotic Disorders',
            ],
            3 => [
                'name' => 'Bipolar and Related Disorders',
                'slug' => 'bipolar-and-related-disorders',
                'description' => 'Mood disorders characterized by severe fluctuations between manic or hypomanic states and major depressive episodes, separated as a bridge between psychotic disorders and depressive disorders.',
                'icon' => '⚡',
                'color' => '#ec4899',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Bipolar and Related Disorders',
            ],
            4 => [
                'name' => 'Depressive Disorders',
                'slug' => 'depressive-disorders',
                'description' => 'Presence of sad, empty, or irritable mood, accompanied by somatic and cognitive changes that significantly affect the individual\'s capacity to function.',
                'icon' => '🌧️',
                'color' => '#3b82f6',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Depressive Disorders',
            ],
            5 => [
                'name' => 'Anxiety Disorders',
                'slug' => 'anxiety-disorders',
                'description' => 'Disorders that share features of excessive fear and anxiety and related behavioral disturbances, differing from normative fear by being excessive or persisting beyond developmentally appropriate periods.',
                'icon' => '🌪️',
                'color' => '#f59e0b',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Anxiety Disorders',
            ],
            6 => [
                'name' => 'Obsessive-Compulsive and Related Disorders',
                'slug' => 'obsessive-compulsive-related-disorders',
                'description' => 'Conditions characterized by obsessions (intrusive, unwanted thoughts/urges) and compulsions (repetitive behaviors or mental acts), or preoccupations with body appearance, hoarding, or body-focused repetitiveness.',
                'icon' => '🔄',
                'color' => '#14b8a6',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Obsessive-Compulsive and Related Disorders',
            ],
            7 => [
                'name' => 'Trauma-, Stressor-Related and Dissociative Disorders',
                'slug' => 'trauma-stressor-dissociative-disorders',
                'description' => 'Conditions precipitated by explicit traumatic or severe life stressors, along with disruptions in the normal integration of consciousness, memory, identity, emotion, and perception.',
                'icon' => '🛡️',
                'color' => '#ef4444',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Trauma, Stressor & Dissociative Disorders',
            ],
            8 => [
                'name' => 'Somatic Symptom and Related Disorders',
                'slug' => 'somatic-symptom-and-related-disorders',
                'description' => 'Prominence of somatic symptoms associated with significant distress and impairment, characterized by abnormal thoughts, feelings, and behaviors in response to these symptoms.',
                'icon' => '🩺',
                'color' => '#10b981',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Somatic Symptom and Related Disorders',
            ],
            9 => [
                'name' => 'Feeding, Eating, and Elimination Disorders',
                'slug' => 'feeding-eating-elimination-disorders',
                'description' => 'Persistent disturbances in eating or feeding behaviors, alongside developmental disturbances in physiological elimination of urine and feces.',
                'icon' => '⚖️',
                'color' => '#f97316',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Feeding, Eating, and Elimination Disorders',
            ],
            10 => [
                'name' => 'Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria',
                'slug' => 'sleep-sexual-gender-disorders',
                'description' => 'Dissatisfaction regarding sleep quality/timing, clinical disruptions in the human sexual response cycle, and marked incongruence between experienced and assigned gender.',
                'icon' => '🌙',
                'color' => '#4338ca',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria',
            ],
            11 => [
                'name' => 'Substance-Related, Addictive, and Disruptive Disorders',
                'slug' => 'substance-addictive-disruptive-disorders',
                'description' => 'Pathological substance abuse, tolerance, withdrawal, and gambling, combined with externalizing problems in emotional and behavioral self-control.',
                'icon' => '🧪',
                'color' => '#7c3aed',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Substance, Addictive & Disruptive Disorders',
            ],
            12 => [
                'name' => 'Neurocognitive, Personality, and Paraphilic Disorders',
                'slug' => 'neurocognitive-personality-paraphilic-disorders',
                'description' => 'Acquired declines in cognitive domains, pervasive and inflexible personality pathology across Clusters A-C, and clinically distressing paraphilias.',
                'icon' => '🎭',
                'color' => '#9333ea',
                'source_version' => 'DSM-5',
                'source_reference' => 'DSM-5 — Neurocognitive, Personality, and Paraphilic Disorders',
            ],
        ];
    }

    private static function seedDomains(PDO $db): void {
        // 1. Clean up any domains > 12 to strictly enforce 12 chapters first
        try {
            $db->exec("DELETE FROM user_domain_progress WHERE domain_id > 12");
            $db->exec("DELETE FROM adventure_chapter_progress WHERE domain_id > 12");
            $db->exec("DELETE FROM chapter_rewards WHERE chapter_id > 12");
            $db->exec("DELETE FROM learning_questions WHERE domain_id > 12");
            $db->exec("DELETE FROM questions WHERE domain_id > 12");
            $db->exec("DELETE FROM study_content_sections WHERE part_id IN (SELECT id FROM study_parts WHERE domain_id > 12)");
            $db->exec("DELETE FROM study_parts WHERE domain_id > 12");
            $db->exec("DELETE FROM psychology_parts WHERE domain_id > 12");
            $db->exec("DELETE FROM psychiatric_topics WHERE domain_id > 12");
            $db->exec("DELETE FROM bosses WHERE id > 12 OR chapter_id > 12");
            $db->exec("DELETE FROM psychology_domains WHERE id > 12");
            $db->exec("DELETE FROM psychiatric_domains WHERE id > 12");
        } catch (Throwable $e) {
            // Silently handle if cascade handled it
        }

        // 2. Temporarily decouple slugs for domains 7..12 to prevent unique constraint collisions during re-alignment
        try {
            $db->exec("UPDATE psychiatric_domains SET slug = CONCAT('temp-slug-', id) WHERE id BETWEEN 7 AND 12");
            $db->exec("UPDATE psychology_domains SET slug = CONCAT('temp-slug-', id) WHERE id BETWEEN 7 AND 12");
        } catch (Throwable $e) {}

        // 3. Upsert the 12 compiled domains
        $domains = self::getDomainDefinitions();

        foreach ($domains as $id => $d) {
            // Seed psychiatric_domains table
            $stmt = $db->prepare("
                INSERT INTO psychiatric_domains (id, name, slug, description, icon, color, display_order, total_parts, source_version, source_reference, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 10, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    slug = VALUES(slug),
                    description = VALUES(description),
                    icon = VALUES(icon),
                    color = VALUES(color),
                    display_order = VALUES(display_order),
                    source_version = VALUES(source_version),
                    source_reference = VALUES(source_reference),
                    status = 'active'
            ");
            $stmt->execute([
                $id, $d['name'], $d['slug'], $d['description'], $d['icon'], $d['color'], $id, $d['source_version'], $d['source_reference']
            ]);

            // Mirror into psychology_domains for complete backwards compatibility with existing foreign keys
            $stmt2 = $db->prepare("
                INSERT INTO psychology_domains (id, name, slug, description, icon, color, display_order, total_parts, source_version, source_reference, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 10, ?, ?, 'active')
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    slug = VALUES(slug),
                    description = VALUES(description),
                    icon = VALUES(icon),
                    color = VALUES(color),
                    display_order = VALUES(display_order),
                    source_version = VALUES(source_version),
                    source_reference = VALUES(source_reference),
                    status = 'active'
            ");
            $stmt2->execute([
                $id, $d['name'], $d['slug'], $d['description'], $d['icon'], $d['color'], $id, $d['source_version'], $d['source_reference']
            ]);
        }
    }

    private static function seedCurriculumData(PDO $db): void {
        // We will seed the topics, 10 structured parts for each domain, and questions
        require_once __DIR__ . '/PsychiatricCurriculumData.php';
        PsychiatricCurriculumData::seed($db);
    }

    private static function seedPsychiatricBosses(PDO $db): void {
        $bossData = [
            1 => [
                'name' => 'The Synaptic Weaver',
                'description' => 'A neurodevelopmental aberration manipulating atypical neural pathways and developmental milestones.',
                'max_hp' => 100,
                'xp_reward' => 500,
                'point_reward' => 300,
                'portrait_asset' => 'assets/bosses/brain/happy.png',
                'flavor_text' => 'The Synaptic Weaver tests your grasp of early developmental onset, neurobiology, and ASD/ADHD distinctions.',
                'chapter_flavor' => 'With the Synaptic Weaver unraveled, the foundational taxonomy of Neurodevelopmental Disorders is mastered in your grimoire.',
            ],
            2 => [
                'name' => 'The Hallucinatory Eidolon',
                'description' => 'An ethereal archon of disorganized cognition, persistent persecutory delusions, and negative symptom blunting.',
                'max_hp' => 140,
                'xp_reward' => 550,
                'point_reward' => 350,
                'portrait_asset' => 'assets/bosses/skull/happy.png',
                'flavor_text' => 'Delusions and auditory perceptions distort the sanctuary. Discern reality using DSM-5 Criterion A to banish it.',
                'chapter_flavor' => 'The psychotic veil dissolves into empirical lucidity. Schizophrenia Spectrum mastery achieved.',
            ],
            3 => [
                'name' => 'The Cyclical Leviathan',
                'description' => 'A colossal bipolar entity oscillating between unbridled manic grandiosity and crushing depressive inertia.',
                'max_hp' => 180,
                'xp_reward' => 600,
                'point_reward' => 400,
                'portrait_asset' => 'assets/bosses/purplebrain/happy.png',
                'flavor_text' => 'Differentiate manic duration, hypomanic stability, and rapid cycling specifiers to shatter its rhythm.',
                'chapter_flavor' => 'Equilibrium is restored. The Bipolar and Related Disorders spectrum yields its deepest diagnostic truths.',
            ],
            4 => [
                'name' => 'The Abyssal Shade',
                'description' => 'A dark spectral manifestation of persistent anhedonia, vegetative dysregulation, and melancholia.',
                'max_hp' => 230,
                'xp_reward' => 650,
                'point_reward' => 450,
                'portrait_asset' => 'assets/bosses/clock/happy.png',
                'flavor_text' => 'Navigate the 5-of-9 diagnostic criteria, persistent dysthymia, and grief differentials to survive.',
                'chapter_flavor' => 'Light pierces the depressive abyss! Depressive Disorders compendium conquered.',
            ],
            5 => [
                'name' => 'The Hyperarousal Phantom',
                'description' => 'A terrifying entity of autonomic adrenaline surges, catastrophic panic cognitions, and agoraphobic fear.',
                'max_hp' => 290,
                'xp_reward' => 700,
                'point_reward' => 500,
                'portrait_asset' => 'assets/bosses/shadows/happy.png',
                'flavor_text' => 'Overcome cognitive misappraisals of panic and generalized worry through precise clinical differential criteria.',
                'chapter_flavor' => 'The autonomic tempest subsides. Mastery over Anxiety Disorders is firmly inscribed.',
            ],
            6 => [
                'name' => 'The Perpetual Ritualist',
                'description' => 'A clockwork specter trapped in compulsive neutralization, intrusive contamination, and dysmorphic agony.',
                'max_hp' => 350,
                'xp_reward' => 750,
                'point_reward' => 550,
                'portrait_asset' => 'assets/bosses/mask/happy.png',
                'flavor_text' => 'Dissect the obsessive loop and insight specifiers to dismantle its compulsive ward.',
                'chapter_flavor' => 'The compulsive cycle is severed. Obsessive-Compulsive & Related Disorders conquered.',
            ],
            7 => [
                'name' => 'The Intrusive Revenant',
                'description' => 'A traumatic memory-phantom reliving intrusive nightmares, dissociative flashbacks, and hypervigilance.',
                'max_hp' => 420,
                'xp_reward' => 800,
                'point_reward' => 600,
                'portrait_asset' => 'assets/bosses/brain/happy.png',
                'flavor_text' => 'Distinguish Acute Stress Disorder from PTSD and identify Criterion A thresholds to banish this ghost.',
                'chapter_flavor' => 'Trauma processed into resilience. Trauma- and Dissociative Disorders mastered.',
            ],
            8 => [
                'name' => 'The Somatosensory Golem',
                'description' => 'A stony behemoth translating psychological distress into functional neurological deficits and somatic terror.',
                'max_hp' => 500,
                'xp_reward' => 850,
                'point_reward' => 650,
                'portrait_asset' => 'assets/bosses/heart/happy.png',
                'flavor_text' => 'Verify neurological incompatibility and distinguish Illness Anxiety Disorder from Somatic Symptom Disorder.',
                'chapter_flavor' => 'The mind-body enigma is decoded. Somatic Symptom and Related Disorders claimed.',
            ],
            9 => [
                'name' => 'The Dysmorphic Siren',
                'description' => 'A deceptive chimera enforcing visceral starvation, compensatory purging cycles, and severe body distortion.',
                'max_hp' => 590,
                'xp_reward' => 900,
                'point_reward' => 700,
                'portrait_asset' => 'assets/bosses/book/happy.png',
                'flavor_text' => 'Analyze BMI criteria, compensatory behaviors, and ARFID differentials to overcome this trial.',
                'chapter_flavor' => 'Nourishment and diagnostic clarity prevail. Feeding, Eating & Elimination Disorders mastered.',
            ],
            10 => [
                'name' => 'The Somnambulist Fiend',
                'description' => 'An oneiric demon of hypocretin collapse, cataplexy, sleep paralysis, and non-REM terror arousals.',
                'max_hp' => 690,
                'xp_reward' => 950,
                'point_reward' => 750,
                'portrait_asset' => 'assets/bosses/wing/happy.png',
                'flavor_text' => 'Distinguish REM behavior disorder from non-REM sleepwalking to survive the dream realm.',
                'chapter_flavor' => 'The sleep-wake architecture is illuminated. Sleep, Sexual & Gender Dysphoria mastered.',
            ],
            11 => [
                'name' => 'The Dopaminergic Wyrm',
                'description' => 'A voracious serpent embodying neural tolerance, withdrawal delirium, and compulsive behavioral gambling.',
                'max_hp' => 800,
                'xp_reward' => 1100,
                'point_reward' => 850,
                'portrait_asset' => 'assets/bosses/eye/happy.png',
                'flavor_text' => 'Master the 11 core DSM-5 criteria and differentiate primary disorders from substance-induced states.',
                'chapter_flavor' => 'Addictive neurocircuitry decoded. Substance-Related & Disruptive Disorders mastered.',
            ],
            12 => [
                'name' => 'The Sovereign of the Fragmented Mind',
                'description' => 'The supreme apex arbiter synthesizing progressive cognitive decline, Cluster A-C personality pathology, and paraphilias.',
                'max_hp' => 950,
                'xp_reward' => 1500,
                'point_reward' => 1000,
                'portrait_asset' => 'assets/bosses/hat/happy.png',
                'flavor_text' => 'Synthesize neurocognitive impairment, character pathology, and Criterion A/B thresholds in the ultimate licensure trial.',
                'chapter_flavor' => 'You have conquered every psychiatric trial in the DSM-5 realm. Crowned Master of Psychopathology!',
            ],
        ];

        foreach ($bossData as $chId => $b) {
            $stmt = $db->prepare("
                INSERT INTO bosses (id, chapter_id, name, description, max_hp, xp_reward, point_reward, portrait_asset, flavor_text, chapter_flavor, boss_order, difficulty, min_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'hard', ?)
                ON DUPLICATE KEY UPDATE
                    chapter_id = VALUES(chapter_id),
                    name = VALUES(name),
                    description = VALUES(description),
                    max_hp = VALUES(max_hp),
                    xp_reward = VALUES(xp_reward),
                    point_reward = VALUES(point_reward),
                    portrait_asset = VALUES(portrait_asset),
                    flavor_text = VALUES(flavor_text),
                    chapter_flavor = VALUES(chapter_flavor)
            ");
            $minLvl = max(1, ($chId - 1) * 2 + 1);
            $stmt->execute([
                $chId, $chId, $b['name'], $b['description'], $b['max_hp'], $b['xp_reward'], $b['point_reward'], $b['portrait_asset'], $b['flavor_text'], $b['chapter_flavor'], $minLvl
            ]);
        }

        try {
            $db->exec("DELETE FROM bosses WHERE id > 12 OR chapter_id > 12");
        } catch (Throwable $e) {}
    }
}
