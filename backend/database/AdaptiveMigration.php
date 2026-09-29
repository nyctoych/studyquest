<?php
require_once __DIR__ . '/../config/database.php';

class AdaptiveMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Ensure columns exist on question_attempts
            $colCheckStmt = $db->query("
                SELECT COLUMN_NAME 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'question_attempts'
            ");
            $existingCols = $colCheckStmt->fetchAll(PDO::FETCH_COLUMN);

            if (!in_array('category_id', $existingCols)) {
                $db->exec("ALTER TABLE question_attempts ADD COLUMN category_id INT DEFAULT NULL AFTER question_id");
            }
            if (!in_array('topic_id', $existingCols)) {
                $db->exec("ALTER TABLE question_attempts ADD COLUMN topic_id INT DEFAULT NULL AFTER category_id");
            }
            if (!in_array('response_time', $existingCols)) {
                $db->exec("ALTER TABLE question_attempts ADD COLUMN response_time INT DEFAULT 0 AFTER is_correct");
            }

            // 2. Create user_learning_profiles
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_learning_profiles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL UNIQUE,
                    mastery_score DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                    adaptive_difficulty ENUM('beginner','intermediate','advanced','board_exam') NOT NULL DEFAULT 'intermediate',
                    adaptive_state VARCHAR(50) NOT NULL DEFAULT 'Developing',
                    current_streak INT NOT NULL DEFAULT 0,
                    recent_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    avg_response_time DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_ulp_user (user_id),
                    INDEX idx_ulp_mastery (mastery_score)
                ) ENGINE=InnoDB;
            ");

            // 3. Create user_category_performance
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_category_performance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    category_id INT NOT NULL,
                    questions_answered INT NOT NULL DEFAULT 0,
                    questions_correct INT NOT NULL DEFAULT 0,
                    accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    mastery_score DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                    avg_response_time DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_user_cat (user_id, category_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES question_categories(id) ON DELETE CASCADE,
                    INDEX idx_ucp_user_cat (user_id, category_id),
                    INDEX idx_ucp_accuracy (user_id, accuracy)
                ) ENGINE=InnoDB;
            ");

            // 4. Create user_topic_performance
            $db->exec("
                CREATE TABLE IF NOT EXISTS user_topic_performance (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    topic_id INT NOT NULL,
                    questions_answered INT NOT NULL DEFAULT 0,
                    questions_correct INT NOT NULL DEFAULT 0,
                    accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    mastery_score DECIMAL(5,2) NOT NULL DEFAULT 50.00,
                    avg_response_time DECIMAL(6,2) NOT NULL DEFAULT 0.00,
                    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_user_topic (user_id, topic_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (topic_id) REFERENCES question_topics(id) ON DELETE CASCADE,
                    INDEX idx_utp_user_topic (user_id, topic_id),
                    INDEX idx_utp_accuracy (user_id, accuracy)
                ) ENGINE=InnoDB;
            ");

            // 5. Ensure question_categories icon column is utf8mb4 and icons are clean
            $db->exec("ALTER TABLE question_categories MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '🧠'");
            $catIcons = [
                1 => '🧠', 2 => '🧬', 3 => '💡', 4 => '🌱', 5 => '👥',
                6 => '🎭', 7 => '⚡', 8 => '🩺', 9 => '🤝', 10 => '🏢',
                11 => '📋', 12 => '🔬', 13 => '📊', 14 => '📐', 15 => '⚖️',
                16 => '📜', 17 => '🎯', 18 => '🔥', 19 => '👁️', 20 => '❤️',
            ];
            $iconStmt = $db->prepare("UPDATE question_categories SET icon = ? WHERE id = ?");
            foreach ($catIcons as $cId => $cEmoji) {
                $iconStmt->execute([$cEmoji, $cId]);
            }

            // Ensure achievements icons are clean utf8mb4
            $db->exec("ALTER TABLE achievements MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '🏆'");
            $achIcons = [
                1 => '⭐', 2 => '📜', 3 => '⚔️', 4 => '🏆', 5 => '👑',
                6 => '⬆️', 7 => '💎', 8 => '🔥', 9 => '🌟', 10 => '🏅',
                11 => '🐉', 12 => '⚔️', 13 => '🌱', 14 => '🌷', 15 => '💰', 16 => '✨',
            ];
            $achStmt = $db->prepare("UPDATE achievements SET icon = ? WHERE id = ?");
            foreach ($achIcons as $aId => $aEmoji) {
                $achStmt->execute([$aEmoji, $aId]);
            }

            // Ensure bosses icons are clean utf8mb4
            $db->exec("ALTER TABLE bosses MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '👹'");
            $bossIcons = [1 => '🧠', 2 => '🦁', 3 => '🧬', 4 => '👹', 5 => '📊', 6 => '⚖️'];
            $bStmt = $db->prepare("UPDATE bosses SET icon = ? WHERE id = ?");
            foreach ($bossIcons as $bId => $bEmoji) {
                $bStmt->execute([$bEmoji, $bId]);
            }

            // Ensure items and item_categories icons are clean utf8mb4
            $db->exec("ALTER TABLE item_categories MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '✨'");
            $db->exec("ALTER TABLE items MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '✨'");
            $itemIcons = [
                1 => '👤', 2 => '🔴', 3 => '🟣', 4 => '🌟', 5 => '💜',
                6 => '🔥', 7 => '❄️', 8 => '⚡', 9 => '🕊️', 10 => '🦇',
                11 => '🧣', 12 => '🌌', 13 => '💫', 14 => '🌀', 15 => '💜',
                16 => '🖤', 17 => '✨', 18 => '💙', 19 => '🌟', 20 => '🏆',
            ];
            $itStmt = $db->prepare("UPDATE items SET icon = ? WHERE id = ?");
            foreach ($itemIcons as $itId => $itEmoji) {
                $itStmt->execute([$itEmoji, $itId]);
            }

            // Ensure plants and rewards icons are clean utf8mb4
            $db->exec("ALTER TABLE plants MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '🌱'");
            $plantIcons = [1 => '🌻', 2 => '🍅', 3 => '🌸', 4 => '🪷', 5 => '🌵'];
            $plStmt = $db->prepare("UPDATE plants SET icon = ? WHERE id = ?");
            foreach ($plantIcons as $plId => $plEmoji) {
                $plStmt->execute([$plEmoji, $plId]);
            }

            $db->exec("ALTER TABLE rewards MODIFY COLUMN icon VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '🎁'");
            $rewIcons = [
                1 => '🌻', 2 => '🍅', 3 => '🌸', 4 => '🪷', 5 => '🌵',
                6 => '🧪', 7 => '🛡️', 8 => '🍀', 9 => '🐲', 10 => '✨',
                11 => '🌙', 12 => '🧥', 13 => '🎁', 14 => '🪴', 15 => '⭐',
            ];
            $rewStmt = $db->prepare("UPDATE rewards SET icon = ? WHERE id = ?");
            foreach ($rewIcons as $rId => $rEmoji) {
                $rewStmt->execute([$rEmoji, $rId]);
            }

            // 6. Seed detailed subtopics if needed
            $topicCount = (int) $db->query("SELECT COUNT(*) FROM question_topics")->fetchColumn();
            if ($topicCount < 20) {
                // Ensure default topics exist
                $topicsSql = "
                    INSERT IGNORE INTO question_topics (category_id, name, slug, description) VALUES
                    (1, 'History and Schools of Thought', 'history-and-schools', 'Foundations from Wundt and James to modern paradigms'),
                    (1, 'Consciousness and Sleep', 'consciousness-and-sleep', 'States of awareness, circadian rhythms, and sleep stages'),
                    (2, 'Neural Communication and Neurotransmitters', 'neurotransmitters', 'Action potentials, synaptic transmission, and neurotransmitters'),
                    (2, 'Brain Structures and Functions', 'brain-structures', 'Cerebral cortex, limbic system, and brainstem physiology'),
                    (3, 'Memory Models and Retrieval', 'memory-models', 'Working memory, encoding, retrieval, and amnesia'),
                    (3, 'Attention and Executive Function', 'attention-executive', 'Selective attention, divided attention, and cognitive control'),
                    (3, 'Problem Solving and Decision Making', 'problem-solving', 'Heuristics, biases, algorithms, and insight'),
                    (4, 'Piaget and Vygotsky Cognitive Stages', 'cognitive-development', 'Cognitive development models and scaffolding'),
                    (4, 'Erikson Psychosocial Stages', 'psychosocial-stages', 'Crisis resolution across lifespan from infancy to late adulthood'),
                    (5, 'Social Cognition and Attribution', 'attribution-theory', 'Fundamental attribution error, schemas, and stereotypes'),
                    (5, 'Conformity, Obedience, and Group Dynamics', 'conformity-obedience', 'Asch, Milgram, Zimbardo experiments and groupthink'),
                    (6, 'Big Five Trait Theory', 'big-five-traits', 'Ocean dimensions, stability, and psychometrics'),
                    (7, 'Mood and Anxiety Disorders', 'mood-anxiety-disorders', 'MDD, Bipolar, GAD, Panic, Phobias, and OCD'),
                    (8, 'Cognitive Behavioral Therapy (CBT)', 'cbt-interventions', 'Automatic thoughts, schemas, behavioral activation'),
                    (9, 'Person-Centered Counseling', 'person-centered', 'Core conditions: empathy, unconditional positive regard, congruence'),
                    (10, 'Job Analysis and Personnel Selection', 'job-analysis', 'KSAOs, structured interviews, psychometric test validation'),
                    (11, 'Intelligence and Cognitive Batteries', 'intelligence-batteries', 'WAIS-IV, WISC-V, Stanford-Binet, Gf-Gc theory'),
                    (12, 'Experimental and Quasi-Experimental Designs', 'experimental-designs', 'Random assignment, control groups, internal/external validity'),
                    (13, 'Inferential Statistics and Hypothesis Testing', 'inferential-stats', 'Null hypothesis, t-tests, ANOVA, Type I and II errors, effect size'),
                    (13, 'Descriptive Statistics and Normal Distribution', 'descriptive-stats', 'Central tendency, variability, z-scores, skewness'),
                    (14, 'Reliability and Classical Test Theory', 'reliability-ctt', 'Test-retest, Cronbach alpha, standard error of measurement'),
                    (15, 'Confidentiality and Tarasoff Duty', 'confidentiality-tarasoff', 'Duty to protect/warn, limits of confidentiality, subpoena response'),
                    (16, 'Freudian and Neo-Freudian Psychodynamics', 'freudian-neo-freudian', 'Jung analytical psychology, Adler individual psychology, Horney'),
                    (17, 'Classical Conditioning Principles', 'classical-conditioning', 'UCS, UCR, CS, CR, extinction, spontaneous recovery, stimulus generalization'),
                    (17, 'Operant Conditioning & Reinforcement Schedules', 'reinforcement-schedules', 'Positive/negative reinforcement, punishment, VR, FR, VI, FI schedules'),
                    (18, 'Theories of Emotion', 'theories-of-emotion', 'James-Lange, Cannon-Bard, Schachter-Singer Two-Factor, Lazarus appraisal'),
                    (19, 'Sensory Thresholds and Psychophysics', 'sensory-thresholds', 'Absolute threshold, difference threshold, Weber Law, Signal Detection Theory'),
                    (20, 'Stress and General Adaptation Syndrome', 'stress-gas-model', 'Hans Selye GAS stages, HPA axis, cortisol, psychoneuroimmunology')
                ";
                $db->exec($topicsSql);
            }

            // Assign topic_id to any question with null topic_id
            $db->exec("
                UPDATE questions q
                SET q.topic_id = (
                    SELECT qt.id FROM question_topics qt 
                    WHERE qt.category_id = q.category_id 
                    ORDER BY qt.id ASC 
                    LIMIT 1
                )
                WHERE q.topic_id IS NULL AND q.category_id IS NOT NULL
            ");

            // Backfill question_attempts category_id & topic_id if null
            $db->exec("
                UPDATE question_attempts qa
                JOIN questions q ON qa.question_id = q.id
                SET qa.category_id = q.category_id,
                    qa.topic_id = q.topic_id
                WHERE qa.category_id IS NULL OR qa.topic_id IS NULL
            ");

        } catch (Throwable $e) {
            error_log("AdaptiveMigration error: " . $e->getMessage());
        }
    }
}
