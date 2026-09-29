<?php
require_once __DIR__ . '/../config/database.php';

class SpecialEventMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. special_events table
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL DEFAULT 0,
                    event_date DATE NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT DEFAULT NULL,
                    question_count INT NOT NULL DEFAULT 140,
                    assigned_domains JSON DEFAULT NULL,
                    status ENUM('draft', 'generating', 'validating', 'pending_review', 'published', 'expired', 'generation_failed') NOT NULL DEFAULT 'draft',
                    generation_status VARCHAR(50) NOT NULL DEFAULT 'pending',
                    generation_version VARCHAR(50) NOT NULL DEFAULT 'v1.0',
                    generated_at DATETIME DEFAULT NULL,
                    published_at DATETIME DEFAULT NULL,
                    expires_at DATETIME DEFAULT NULL,
                    error_log TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_event_date_user (event_date, user_id),
                    INDEX idx_event_date (event_date),
                    INDEX idx_user_id (user_id),
                    INDEX idx_event_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. special_event_questions table
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_event_questions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    special_event_id INT NOT NULL,
                    study_part_id INT NOT NULL,
                    domain_id INT NOT NULL,
                    question_text TEXT NOT NULL,
                    choice_a TEXT NOT NULL,
                    choice_b TEXT NOT NULL,
                    choice_c TEXT NOT NULL,
                    choice_d TEXT NOT NULL,
                    correct_choice ENUM('A', 'B', 'C', 'D') NOT NULL,
                    explanation TEXT NOT NULL,
                    difficulty ENUM('moderate', 'difficult', 'very_difficult', 'board_style_case') NOT NULL DEFAULT 'difficult',
                    question_type ENUM('recall', 'recognition', 'differential_diagnosis', 'clinical_application', 'best_answer', 'case_analysis', 'exception') NOT NULL DEFAULT 'clinical_application',
                    bloom_level ENUM('remember', 'understand', 'apply', 'analyze', 'evaluate', 'create') NOT NULL DEFAULT 'apply',
                    source_hash VARCHAR(64) NOT NULL,
                    question_hash VARCHAR(64) NOT NULL,
                    concept_key VARCHAR(150) NOT NULL,
                    generation_version VARCHAR(50) NOT NULL DEFAULT 'v1.0',
                    review_status ENUM('pending_review', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
                    display_order INT NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_event_id (special_event_id),
                    INDEX idx_study_part_id (study_part_id),
                    INDEX idx_domain_id (domain_id),
                    INDEX idx_bloom_level (bloom_level),
                    INDEX idx_question_hash (question_hash),
                    INDEX idx_review_status (review_status),
                    FOREIGN KEY (special_event_id) REFERENCES special_events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. special_event_attempts table
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_event_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    special_event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    started_at DATETIME NOT NULL,
                    completed_at DATETIME DEFAULT NULL,
                    score INT NOT NULL DEFAULT 0,
                    correct_count INT NOT NULL DEFAULT 0,
                    incorrect_count INT NOT NULL DEFAULT 0,
                    percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    total_time INT NOT NULL DEFAULT 0,
                    status ENUM('in_progress', 'completed', 'timed_out', 'abandoned') NOT NULL DEFAULT 'in_progress',
                    question_order LONGTEXT DEFAULT NULL,
                    choice_orders LONGTEXT DEFAULT NULL,
                    xp_awarded INT NOT NULL DEFAULT 0,
                    coins_awarded INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_attempt_user (user_id),
                    INDEX idx_attempt_event (special_event_id),
                    INDEX idx_attempt_status (status),
                    FOREIGN KEY (special_event_id) REFERENCES special_events(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 4. special_event_answers table
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_event_answers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    attempt_id INT NOT NULL,
                    question_id INT NOT NULL,
                    bloom_level ENUM('remember', 'understand', 'apply', 'analyze', 'evaluate', 'create') NULL DEFAULT NULL,
                    selected_choice ENUM('A', 'B', 'C', 'D') DEFAULT NULL,
                    is_correct TINYINT(1) NOT NULL DEFAULT 0,
                    response_time INT NOT NULL DEFAULT 0,
                    is_marked TINYINT(1) NOT NULL DEFAULT 0,
                    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_attempt_question (attempt_id, question_id),
                    INDEX idx_ans_attempt (attempt_id),
                    INDEX idx_ans_question (question_id),
                    INDEX idx_ans_bloom (bloom_level),
                    FOREIGN KEY (attempt_id) REFERENCES special_event_attempts(id) ON DELETE CASCADE,
                    FOREIGN KEY (question_id) REFERENCES special_event_questions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 5. special_event_bloom_results table (Aggregated per-attempt Bloom performance)
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_event_bloom_results (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    attempt_id INT NOT NULL,
                    bloom_level ENUM('remember', 'understand', 'apply', 'analyze', 'evaluate', 'create') NOT NULL,
                    question_count INT NOT NULL DEFAULT 0,
                    correct_count INT NOT NULL DEFAULT 0,
                    incorrect_count INT NOT NULL DEFAULT 0,
                    accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    average_response_time INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_attempt_bloom (attempt_id, bloom_level),
                    INDEX idx_bloom_attempt (attempt_id),
                    INDEX idx_bloom_level (bloom_level),
                    FOREIGN KEY (attempt_id) REFERENCES special_event_attempts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 6. special_event_settings table
            $db->exec("
                CREATE TABLE IF NOT EXISTS special_event_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT NOT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            self::seedDefaultSettings($db);

        } catch (Throwable $e) {
            error_log("SpecialEventMigration Error: " . $e->getMessage());
            throw $e;
        }
    }

    private static function seedDefaultSettings(PDO $db): void {
        $defaults = [
            'target_question_count' => ['140', 'Total required questions per daily Special Event'],
            'exam_duration_minutes' => ['140', 'Standard exam duration in minutes'],
            'difficulty_distribution' => [
                json_encode([
                    'moderate' => 28,          // 20%
                    'difficult' => 49,         // 35%
                    'very_difficult' => 42,    // 30%
                    'board_style_case' => 21   // 15%
                ]),
                'Configurable difficulty distribution counts for 140 questions'
            ],
            'passing_percentage' => ['75', 'Minimum percentage required for mastery rewards'],
            'base_xp_reward' => ['500', 'Base XP for completing the 140-question trial'],
            'base_coin_reward' => ['250', 'Base Coins for completing the 140-question trial'],
            'per_correct_xp' => ['5', 'Bonus XP awarded per correct answer'],
            'per_correct_coins' => ['2', 'Bonus Coins awarded per correct answer'],
            'perfect_score_bonus_xp' => ['1000', 'Bonus XP for 140/140 score'],
            'perfect_score_bonus_coins' => ['500', 'Bonus Coins for 140/140 score'],
            'bloom_distribution' => [
                json_encode([
                    'remember' => 20,
                    'understand' => 25,
                    'apply' => 35,
                    'analyze' => 30,
                    'evaluate' => 18,
                    'create' => 12
                ]),
                'Configurable Bloom Taxonomy question distribution (sum = 140)'
            ],
            'immediate_feedback' => ['false', 'Whether to reveal Bloom level and feedback immediately upon answering in practice mode'],
            'groq_model' => ['llama-3.3-70b-versatile', 'Configurable Groq LLM model']
        ];

        $stmt = $db->prepare("
            INSERT INTO special_event_settings (setting_key, setting_value, description)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE description = VALUES(description)
        ");

        foreach ($defaults as $k => [$val, $desc]) {
            $stmt->execute([$k, $val, $desc]);
        }
    }
}
