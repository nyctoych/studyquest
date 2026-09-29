-- ============================================================
-- StudyQuest Multiplayer Migration
-- Run AFTER studyquest.sql and seed_data.sql
-- ============================================================

USE studyquest;

-- ── ADD MULTIPLAYER COLUMNS TO USERS ─────────────────────────
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS coins INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS battle_rating INT DEFAULT 1200,
  ADD COLUMN IF NOT EXISTS wins INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS losses INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS draws INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS total_battles INT DEFAULT 0;

-- ── QUESTION CATEGORIES ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS question_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    icon VARCHAR(10) DEFAULT '🧠',
    color VARCHAR(20) DEFAULT '#6366f1'
) ENGINE=InnoDB;

-- ── PSYCHOLOGY QUESTION BANK ──────────────────────────────────
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    question TEXT NOT NULL,
    choice_a VARCHAR(500) NOT NULL,
    choice_b VARCHAR(500) NOT NULL,
    choice_c VARCHAR(500) NOT NULL,
    choice_d VARCHAR(500) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    difficulty ENUM('beginner','intermediate','advanced','board_exam') DEFAULT 'intermediate',
    explanation TEXT DEFAULT NULL,
    source_reference VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES question_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS idx_questions_category ON questions(category_id);
CREATE INDEX IF NOT EXISTS idx_questions_difficulty ON questions(difficulty);
CREATE INDEX IF NOT EXISTS idx_questions_status ON questions(status);

-- ── MATCHES ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id VARCHAR(50) NOT NULL UNIQUE,
    status ENUM('waiting','in_progress','completed','cancelled','abandoned') DEFAULT 'waiting',
    battle_mode ENUM('ranked','casual') DEFAULT 'ranked',
    questions_count INT DEFAULT 10,
    started_at TIMESTAMP NULL DEFAULT NULL,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS idx_matches_room_id ON matches(room_id);
CREATE INDEX IF NOT EXISTS idx_matches_status ON matches(status);

-- ── MATCH PLAYERS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS match_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    user_id INT NOT NULL,
    player_slot ENUM('player1','player2') NOT NULL,
    hp_start INT DEFAULT 100,
    hp_end INT DEFAULT NULL,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    outcome ENUM('won','lost','draw','disconnect') DEFAULT NULL,
    xp_earned INT DEFAULT 0,
    coins_earned INT DEFAULT 0,
    rating_before INT DEFAULT 1200,
    rating_after INT DEFAULT 1200,
    rating_change INT DEFAULT 0,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS idx_match_players_user ON match_players(user_id);
CREATE INDEX IF NOT EXISTS idx_match_players_match ON match_players(match_id);

-- ── MATCH QUESTIONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS match_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    question_id INT NOT NULL,
    question_order INT NOT NULL,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── MATCHMAKING QUEUE (audit log) ────────────────────────────
CREATE TABLE IF NOT EXISTS matchmaking_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('waiting','matched','cancelled','timeout') DEFAULT 'waiting',
    preferred_category_id INT DEFAULT NULL,
    preferred_difficulty ENUM('beginner','intermediate','advanced','board_exam') DEFAULT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    matched_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── BATTLE RATING HISTORY ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS rating_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    rating_before INT NOT NULL,
    rating_after INT NOT NULL,
    rating_change INT NOT NULL,
    outcome ENUM('won','lost','draw') NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── COIN TRANSACTIONS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coin_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    type ENUM('earn','spend') NOT NULL,
    source ENUM('battle_win','level_up','achievement','quest','purchase','admin') NOT NULL,
    reference_id INT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    balance_after INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── ITEM CATEGORIES ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS item_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '🎨'
) ENGINE=InnoDB;

-- ── COSMETIC ITEMS (Shop) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(10) DEFAULT '🎁',
    coin_cost INT NOT NULL DEFAULT 100,
    rarity ENUM('common','rare','epic','legendary') DEFAULT 'common',
    dicebear_property VARCHAR(100) DEFAULT NULL,
    dicebear_value VARCHAR(100) DEFAULT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── USER OWNED ITEMS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_item (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── USER EQUIPPED ITEMS ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_equipped_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot VARCHAR(100) NOT NULL,
    item_id INT NOT NULL,
    equipped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_slot (user_id, slot),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── ITEM CATEGORIES SEED ──────────────────────────────────────
INSERT IGNORE INTO item_categories (name, slug, icon) VALUES
('Skins', 'skins', '🎨'),
('Hair', 'hair', '💇'),
('Accessories', 'accessories', '👑'),
('Backgrounds', 'backgrounds', '🌅'),
('Effects', 'effects', '✨'),
('Outfits', 'outfits', '👕');

-- ── COSMETIC ITEMS SEED ───────────────────────────────────────
INSERT IGNORE INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value) VALUES
-- Skins (category 1)
(1, 'Crimson Scholar', 'Fierce red skin for the competitive psychology student', '🔴', 0, 'common', 'skinColor', 'f2d3b1'),
(1, 'Midnight Sage', 'Deep purple skin of a true psychology master', '🟣', 200, 'rare', 'skinColor', '9c6e3b'),
(1, 'Emerald Intellect', 'Vibrant scholar skin', '💚', 300, 'epic', 'skinColor', 'ae5d29'),
-- Hair (category 2)
(2, 'Classic Waves', 'Flowing wavy hair', '🌊', 0, 'common', 'hair', 'shortCurls'),
(2, 'Scholar Bun', 'Professional top knot', '🎓', 150, 'common', 'hair', 'bun'),
(2, 'Wild Quiff', 'Bold spiky style', '⚡', 250, 'rare', 'hair', 'fonze'),
(2, 'Long Flowing', 'Elegant long hair', '✨', 400, 'epic', 'hair', 'longButNotTooLong'),
-- Accessories (category 3)
(3, 'Reading Glasses', 'Classic scholar glasses', '👓', 0, 'common', 'accessories', 'roundGlasses'),
(3, 'Graduation Cap', 'Wear your achievement', '🎓', 500, 'rare', 'accessories', 'studentCap'),
(3, 'Gaming Headset', 'Show your competitive spirit', '🎧', 750, 'epic', 'accessories', 'headphones'),
(3, 'Crown of Wisdom', 'For the top-rated battlers', '👑', 1500, 'legendary', 'accessories', 'crown'),
-- Backgrounds (category 4)
(4, 'Library Aura', 'Scholarly library backdrop', '📚', 0, 'common', 'backgroundColor', 'b6e3f4'),
(4, 'Cosmic Mind', 'Deep space background', '🌌', 600, 'rare', 'backgroundColor', '1a1a2e'),
(4, 'Golden Hour', 'Warm golden glow', '🌅', 800, 'epic', 'backgroundColor', 'ffd700');
