-- StudyQuest Psychology Curriculum Migration
-- Built-in structured Psychology learning and reviewer system

CREATE TABLE IF NOT EXISTS `psychology_domains` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `icon` VARCHAR(50) NOT NULL DEFAULT '🧠',
    `color` VARCHAR(30) NOT NULL DEFAULT '#8b5cf6',
    `cover_image` VARCHAR(255) DEFAULT NULL,
    `display_order` INT NOT NULL DEFAULT 1,
    `total_parts` INT NOT NULL DEFAULT 10,
    `status` ENUM('active', 'coming_soon') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `psychology_parts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `domain_id` INT NOT NULL,
    `part_number` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `part_type` ENUM('learn', 'review', 'practice', 'scenario', 'quiz', 'challenge', 'mastery') NOT NULL DEFAULT 'learn',
    `overview` TEXT,
    `key_concepts` JSON DEFAULT NULL,
    `important_terms` JSON DEFAULT NULL,
    `clinical_example` TEXT DEFAULT NULL,
    `summary_notes` TEXT DEFAULT NULL,
    `xp_reward` INT NOT NULL DEFAULT 50,
    `coin_reward` INT NOT NULL DEFAULT 25,
    `passing_score` INT NOT NULL DEFAULT 70,
    `is_published` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`domain_id`) REFERENCES `psychology_domains`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_domain_part` (`domain_id`, `part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `learning_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `part_id` INT NOT NULL,
    `question` TEXT NOT NULL,
    `choice_a` TEXT NOT NULL,
    `choice_b` TEXT NOT NULL,
    `choice_c` TEXT NOT NULL,
    `choice_d` TEXT NOT NULL,
    `correct_answer` ENUM('A', 'B', 'C', 'D') NOT NULL,
    `explanation` TEXT NOT NULL,
    `difficulty` ENUM('beginner', 'intermediate', 'advanced', 'board_exam') NOT NULL DEFAULT 'intermediate',
    `is_scenario` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`part_id`) REFERENCES `psychology_parts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_part_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `part_id` INT NOT NULL,
    `status` ENUM('locked', 'available', 'in_progress', 'completed', 'mastered') NOT NULL DEFAULT 'locked',
    `score` INT NOT NULL DEFAULT 0,
    `attempts` INT NOT NULL DEFAULT 0,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`part_id`) REFERENCES `psychology_parts`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_part` (`user_id`, `part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_domain_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `domain_id` INT NOT NULL,
    `completed_parts` INT NOT NULL DEFAULT 0,
    `total_parts` INT NOT NULL DEFAULT 10,
    `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `mastery_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`domain_id`) REFERENCES `psychology_domains`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_domain` (`user_id`, `domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `part_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `part_id` INT NOT NULL,
    `score` INT NOT NULL DEFAULT 0,
    `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
    `answers_summary` JSON DEFAULT NULL,
    `time_spent` INT NOT NULL DEFAULT 0,
    `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`part_id`) REFERENCES `psychology_parts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
