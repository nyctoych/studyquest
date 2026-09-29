-- ============================================================
-- StudyQuest — Adventure & Game-World Migration
-- Run AFTER all previous migrations:
--   studyquest.sql
--   multiplayer_migration.sql
--   psychology_curriculum_migration.sql
--   cosmetics_migration.sql
-- ============================================================

USE studyquest;

-- ── NICKNAME + NEW PLAYER FLAG ────────────────────────────────
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS nickname VARCHAR(30) DEFAULT NULL COMMENT 'Public in-game display name',
  ADD COLUMN IF NOT EXISTS is_new_player TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = show tutorial on next login',
  ADD COLUMN IF NOT EXISTS has_seen_loading TINYINT(1) NOT NULL DEFAULT 0;

-- Ensure nickname uniqueness (skip if already exists)
-- Note: If users already exist, set nickname = username as default
UPDATE users SET nickname = username WHERE nickname IS NULL;

-- Add unique constraint on nickname
ALTER TABLE users
  MODIFY COLUMN nickname VARCHAR(30) NOT NULL,
  ADD UNIQUE KEY IF NOT EXISTS uq_nickname (nickname);

-- ── EXTEND BOSSES TABLE FOR ADVENTURE INTEGRATION ────────────
ALTER TABLE bosses
  ADD COLUMN IF NOT EXISTS chapter_id INT DEFAULT NULL COMMENT 'Links boss to a psychology domain chapter',
  ADD COLUMN IF NOT EXISTS portrait_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of boss portrait',
  ADD COLUMN IF NOT EXISTS idle_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of idle animation',
  ADD COLUMN IF NOT EXISTS attack_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of attack animation',
  ADD COLUMN IF NOT EXISTS hit_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of hit/damage animation',
  ADD COLUMN IF NOT EXISTS defeat_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of defeat animation',
  ADD COLUMN IF NOT EXISTS intro_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of intro cinematic',
  ADD COLUMN IF NOT EXISTS victory_asset VARCHAR(255) DEFAULT NULL COMMENT 'URL or filename of victory taunt',
  ADD COLUMN IF NOT EXISTS flavor_text TEXT DEFAULT NULL COMMENT 'Boss lore/story text shown before battle',
  ADD COLUMN IF NOT EXISTS chapter_flavor TEXT DEFAULT NULL COMMENT 'Chapter completion narrative',
  ADD COLUMN IF NOT EXISTS boss_order INT DEFAULT 1 COMMENT 'Order within chapter (for multi-boss chapters)';

-- ── ADVENTURE CHAPTER PROGRESS ────────────────────────────────
-- Maps to psychology_domains (one chapter per domain)
CREATE TABLE IF NOT EXISTS adventure_chapter_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    domain_id INT NOT NULL,
    status ENUM('locked','available','in_progress','boss_available','completed','mastered') NOT NULL DEFAULT 'locked',
    parts_completed INT NOT NULL DEFAULT 0,
    boss_defeated TINYINT(1) NOT NULL DEFAULT 0,
    boss_status ENUM('locked','available','defeated') NOT NULL DEFAULT 'locked',
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (domain_id) REFERENCES psychology_domains(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_chapter (user_id, domain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CHAPTER REWARDS (Single claim protection) ──────────────────
CREATE TABLE IF NOT EXISTS chapter_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chapter_id INT NOT NULL,
    xp_awarded INT NOT NULL DEFAULT 0,
    coins_awarded INT NOT NULL DEFAULT 0,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES psychology_domains(id) ON DELETE CASCADE,
    UNIQUE KEY uq_user_chapter_reward (user_id, chapter_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ADVENTURE PART STORY DATA ─────────────────────────────────
-- Extends psychology_parts with story/dialogue content
CREATE TABLE IF NOT EXISTS adventure_part_story (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    story_intro TEXT DEFAULT NULL COMMENT 'Narrative introduction to this part',
    dialogue_speaker VARCHAR(100) DEFAULT 'Mentor' COMMENT 'Name of the dialogue character',
    dialogue_text TEXT DEFAULT NULL COMMENT 'Character dialogue before the battle',
    story_after TEXT DEFAULT NULL COMMENT 'Brief narrative after completing the part',
    enemy_name VARCHAR(100) DEFAULT 'Study Specter' COMMENT 'Name of the enemy in this part',
    enemy_flavor VARCHAR(255) DEFAULT NULL COMMENT 'Short enemy description',
    enemy_max_hp INT NOT NULL DEFAULT 100 COMMENT 'Enemy HP for this part battle',
    FOREIGN KEY (part_id) REFERENCES psychology_parts(id) ON DELETE CASCADE,
    UNIQUE KEY uq_part_story (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── ADVENTURE PART ATTEMPTS ───────────────────────────────────
CREATE TABLE IF NOT EXISTS adventure_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    part_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    correct_answers INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 5,
    is_passed TINYINT(1) NOT NULL DEFAULT 0,
    time_spent INT NOT NULL DEFAULT 0 COMMENT 'Seconds',
    xp_earned INT NOT NULL DEFAULT 0,
    coins_earned INT NOT NULL DEFAULT 0,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES psychology_parts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── CHRONICLE / ACTIVITY FEED ─────────────────────────────────
CREATE TABLE IF NOT EXISTS chronicle_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_type ENUM('achievement','adventure_victory','boss_victory','multiplayer_win','level_up','chapter_complete') NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL COMMENT 'Extra context (chapter name, score, etc.)',
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_chronicle_user ON chronicle_posts(user_id);
CREATE INDEX IF NOT EXISTS idx_chronicle_public ON chronicle_posts(is_public);
CREATE INDEX IF NOT EXISTS idx_chronicle_created ON chronicle_posts(created_at);

-- ── SEED: DEFAULT ADVENTURE STORY DATA ───────────────────────
-- Default enemy names and story snippets for Cognitive Psychology (domain 1)
-- These are examples; full story data can be added via admin tool
INSERT IGNORE INTO adventure_part_story (part_id, story_intro, dialogue_speaker, dialogue_text, story_after, enemy_name, enemy_flavor, enemy_max_hp)
SELECT 
    pp.id,
    CONCAT('You stand at the threshold of ', pd.name, '. The path forward is shrouded in mystery.') as story_intro,
    'The Archivist' as dialogue_speaker,
    CONCAT('To advance through Part ', pp.part_number, ': ', pp.title, ', you must prove your knowledge. Answer correctly to strike!') as dialogue_text,
    CONCAT('You have conquered ', pp.title, '! Your understanding grows stronger.') as story_after,
    CONCAT(pp.title, ' Specter') as enemy_name,
    CONCAT('A manifestation of unmastered ', pp.title, ' concepts.') as enemy_flavor,
    CASE 
        WHEN pp.part_number = 10 THEN 300
        WHEN pp.part_number >= 8 THEN 150
        WHEN pp.part_number >= 5 THEN 120
        ELSE 100
    END as enemy_max_hp
FROM psychology_parts pp
JOIN psychology_domains pd ON pp.domain_id = pd.id
WHERE pp.is_published = 1;

-- ── SEED: DEFAULT BOSS LINKING ────────────────────────────────
-- Link existing bosses to domains if they exist
-- The memory_devourer boss links to first cognitive psychology domain
UPDATE bosses SET 
    portrait_asset = '/assets/boss/memory_devourer.jpg',
    flavor_text = 'The Memory Devourer feasts on forgotten knowledge. Only a true master of Cognitive Psychology can defeat it.',
    chapter_flavor = 'With the Memory Devourer defeated, the secrets of Cognitive Psychology are yours. The world of Psychology opens wider before you.',
    boss_order = 1
WHERE name LIKE '%Memory%' OR name LIKE '%Cognitive%' OR id = 1
LIMIT 1;

-- ── INDEXES ───────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_adventure_progress_user ON adventure_chapter_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_adventure_attempts_user ON adventure_attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_adventure_attempts_part ON adventure_attempts(part_id);
