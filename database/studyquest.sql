-- StudyQuest Database Schema
-- Run this file to create all tables

CREATE DATABASE IF NOT EXISTS studyquest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studyquest;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    last_quest_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- QUESTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS quests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    subject VARCHAR(100) DEFAULT 'General',
    difficulty ENUM('easy','medium','hard','epic') DEFAULT 'medium',
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    deadline DATE DEFAULT NULL,
    status ENUM('pending','in_progress','completed','failed') DEFAULT 'pending',
    xp_reward INT DEFAULT 50,
    point_reward INT DEFAULT 25,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- QUEST SUBTASKS
-- =============================================
CREATE TABLE IF NOT EXISTS quest_subtasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quest_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- ACHIEVEMENTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(10) DEFAULT '🏆',
    category ENUM('academic','game','streak','social','special') DEFAULT 'academic',
    condition_type VARCHAR(50) NOT NULL,
    condition_value INT NOT NULL,
    xp_reward INT DEFAULT 50,
    point_reward INT DEFAULT 25,
    rarity ENUM('common','rare','epic','legendary') DEFAULT 'common'
) ENGINE=InnoDB;

-- =============================================
-- USER ACHIEVEMENTS (junction)
-- =============================================
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
) ENGINE=InnoDB;

-- =============================================
-- REWARDS / SHOP ITEMS
-- =============================================
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    category ENUM('cosmetic','powerup','special','title','booster') DEFAULT 'cosmetic',
    cost INT NOT NULL DEFAULT 100,
    image VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(10) DEFAULT '🎁',
    effect_type VARCHAR(50) DEFAULT NULL,
    effect_value VARCHAR(100) DEFAULT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    rarity ENUM('common','rare','epic','legendary') DEFAULT 'common'
) ENGINE=InnoDB;

-- =============================================
-- USER REWARDS / INVENTORY
-- =============================================
CREATE TABLE IF NOT EXISTS user_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_id INT NOT NULL,
    quantity INT DEFAULT 1,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- BOSSES
-- =============================================
CREATE TABLE IF NOT EXISTS bosses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    difficulty ENUM('easy','medium','hard','legendary') DEFAULT 'medium',
    max_hp INT DEFAULT 100,
    icon VARCHAR(10) DEFAULT '👹',
    image VARCHAR(255) DEFAULT NULL,
    xp_reward INT DEFAULT 300,
    point_reward INT DEFAULT 150,
    min_level INT DEFAULT 1,
    subject VARCHAR(100) DEFAULT 'General'
) ENGINE=InnoDB;

-- =============================================
-- BOSS QUESTIONS
-- =============================================
CREATE TABLE IF NOT EXISTS boss_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boss_id INT NOT NULL,
    question TEXT NOT NULL,
    choice_a VARCHAR(255) NOT NULL,
    choice_b VARCHAR(255) NOT NULL,
    choice_c VARCHAR(255) NOT NULL,
    choice_d VARCHAR(255) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
    FOREIGN KEY (boss_id) REFERENCES bosses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- BOSS ATTEMPTS
-- =============================================
CREATE TABLE IF NOT EXISTS boss_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    boss_id INT NOT NULL,
    boss_hp INT DEFAULT 100,
    player_hp INT DEFAULT 100,
    current_question_index INT DEFAULT 0,
    questions_order JSON DEFAULT NULL,
    status ENUM('active','won','lost','abandoned') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (boss_id) REFERENCES bosses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- INDEXES FOR PERFORMANCE
-- =============================================
CREATE INDEX idx_quests_user_id ON quests(user_id);
CREATE INDEX idx_quests_status ON quests(status);
CREATE INDEX idx_quests_deadline ON quests(deadline);
CREATE INDEX idx_boss_attempts_user_id ON boss_attempts(user_id);
CREATE INDEX idx_boss_attempts_status ON boss_attempts(status);
CREATE INDEX idx_user_achievements_user_id ON user_achievements(user_id);
