-- ============================================================
-- StudyQuest — Revised Cosmetics Migration
-- Replaces old item_categories with the new 5-category system:
--   1. character_skin   2. aura   3. back_effect
--   4. hit_effect       5. finisher_effect
-- Run AFTER multiplayer_migration.sql
-- ============================================================

USE studyquest;

-- ── ADD effect_key + preview_css columns to items (if not present) ─
ALTER TABLE items
  ADD COLUMN IF NOT EXISTS effect_key   VARCHAR(100) DEFAULT NULL COMMENT 'CSS/Phaser animation identifier',
  ADD COLUMN IF NOT EXISTS preview_css  VARCHAR(100) DEFAULT NULL COMMENT 'CSS class for shop preview';

-- ── CLEAR OLD COSMETIC DATA ───────────────────────────────────────
-- Safe truncate order respects FK constraints
DELETE FROM user_equipped_items WHERE slot IN ('accessories','hair','backgrounds','effects','outfits','skins','general');
DELETE FROM user_items WHERE item_id IN (SELECT id FROM items);
DELETE FROM items;
DELETE FROM item_categories;

-- Reset auto-increment
ALTER TABLE item_categories AUTO_INCREMENT = 1;
ALTER TABLE items AUTO_INCREMENT = 1;

-- ── NEW ITEM CATEGORIES ───────────────────────────────────────────
INSERT INTO item_categories (name, slug, icon) VALUES
('Character Skin',   'character_skin',  '🧬'),
('Aura',             'aura',            '✨'),
('Back Effect',      'back_effect',     '🪽'),
('Hit Effect',       'hit_effect',      '💥'),
('Finisher Effect',  'finisher_effect', '🏆');

-- Category IDs after insert:
-- 1 = character_skin
-- 2 = aura
-- 3 = back_effect
-- 4 = hit_effect
-- 5 = finisher_effect

-- ── CHARACTER SKIN ITEMS ──────────────────────────────────────────
INSERT INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value, effect_key, preview_css) VALUES
(1, 'Default Scholar',   'The classic psychology student look.',                              '👤', 0,    'common',    'skinColor', 'f2d3b1', 'skin-default',   'skin-default'),
(1, 'Crimson Prodigy',   'Fierce red scholar who never backs down from a debate.',            '🔴', 300,  'rare',      'skinColor', 'c25b3a', 'skin-crimson',   'skin-crimson'),
(1, 'Midnight Sage',     'Deep indigo scholar who mastered the unconscious mind.',            '🟣', 600,  'epic',      'skinColor', '4c2d7c', 'skin-midnight',  'skin-midnight'),
(1, 'Auric Genius',      'Radiant golden scholar. The pinnacle of psychological mastery.',   '🌟', 1500, 'legendary', 'skinColor', 'c8973a', 'skin-auric',     'skin-auric');

-- ── AURA ITEMS ────────────────────────────────────────────────────
INSERT INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value, effect_key, preview_css) VALUES
(2, 'Psychic Pulse',      'A gentle violet aura of heightened perception.',                  '💜', 0,    'common',    NULL, NULL, 'aura-psychic',  'aura-psychic'),
(2, 'Flame Ward',         'A blazing orange aura that radiates raw cognitive power.',        '🔥', 400,  'rare',      NULL, NULL, 'aura-flame',    'aura-flame'),
(2, 'Glacial Mind',       'An icy blue aura of perfect analytical clarity.',                 '❄️', 700,  'epic',      NULL, NULL, 'aura-ice',      'aura-ice'),
(2, 'Thunder Sovereign',  'A crackling golden aura feared by all challengers.',              '⚡', 1800, 'legendary', NULL, NULL, 'aura-thunder',  'aura-thunder');

-- ── BACK EFFECT ITEMS ─────────────────────────────────────────────
INSERT INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value, effect_key, preview_css) VALUES
(3, 'Scholar Wings',  'Pure white feathered wings of an enlightened psychology student.',    '🕊️', 0,    'common',    NULL, NULL, 'wings-light',  'wings-light'),
(3, 'Shadow Wings',   'Dark obsidian wings of a master of the subconscious.',                '🦇', 500,  'rare',      NULL, NULL, 'wings-dark',   'wings-dark'),
(3, 'Crimson Cape',   'A flowing scarlet cape that billows with competitive spirit.',        '🧣', 800,  'epic',      NULL, NULL, 'cape-crimson', 'cape-crimson'),
(3, 'Galaxy Trail',   'A cosmic trail of stardust marking each step of a legend.',          '🌌', 2000, 'legendary', NULL, NULL, 'cape-galaxy',  'cape-galaxy');

-- ── HIT EFFECT ITEMS ──────────────────────────────────────────────
INSERT INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value, effect_key, preview_css) VALUES
(4, 'Focus Burst',        'A clean white flash when your knowledge lands.',                 '💫', 0,    'common',    NULL, NULL, 'hit-focus',    'hit-focus'),
(4, 'Psych Shatter',      'A blue psychic shockwave that fractures the opponent.',          '🌀', 350,  'rare',      NULL, NULL, 'hit-psych',    'hit-psych'),
(4, 'Mind Blast',         'A violet explosion of pure mental force.',                       '💜', 650,  'epic',      NULL, NULL, 'hit-mind',     'hit-mind'),
(4, 'Cognitive Collapse', 'A legendary dark implosion — the ultimate psychological strike.','🖤', 1600, 'legendary', NULL, NULL, 'hit-collapse', 'hit-collapse');

-- ── FINISHER EFFECT ITEMS ─────────────────────────────────────────
INSERT INTO items (category_id, name, description, icon, coin_cost, rarity, dicebear_property, dicebear_value, effect_key, preview_css) VALUES
(5, 'Victory Flash',     'A bright clean flash of triumph.',                               '✨', 0,    'common',    NULL, NULL, 'finish-flash',    'finish-flash'),
(5, 'Insight Surge',     'A rising pulse of blue light bursts from the victor.',           '💙', 450,  'rare',      NULL, NULL, 'finish-insight',  'finish-insight'),
(5, 'Enlightenment',     'A golden sunburst descends upon the champion.',                  '🌟', 900,  'epic',      NULL, NULL, 'finish-enlighten','finish-enlighten'),
(5, 'Board Exam Legend', 'The legendary finisher — shakes the entire arena with thunder.', '🏆', 2500, 'legendary', NULL, NULL, 'finish-legend',   'finish-legend');
