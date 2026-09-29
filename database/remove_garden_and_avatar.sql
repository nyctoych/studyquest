-- ============================================================
-- StudyQuest — Remove Garden & Avatar Migration
-- ============================================================

USE studyquest;

-- 1. Drop foreign-keyed Garden tables safely
DROP TABLE IF EXISTS user_plants;
DROP TABLE IF EXISTS flower_pots;
DROP TABLE IF EXISTS plants;

-- 2. Clean up garden items from rewards
DELETE FROM user_rewards WHERE reward_id IN (SELECT id FROM rewards WHERE category IN ('plant', 'garden'));
DELETE FROM rewards WHERE category IN ('plant', 'garden');

-- 3. Clean up garden-specific achievements
DELETE FROM user_achievements WHERE achievement_id IN (SELECT id FROM achievements WHERE condition_type = 'plants_grown');
DELETE FROM achievements WHERE condition_type = 'plants_grown';

-- 4. Remove Avatar columns from users if they exist
SET @dbname = DATABASE();
SET @tablename = 'users';

-- avatar_style
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'avatar_style') > 0,
  'ALTER TABLE users DROP COLUMN avatar_style;',
  'SELECT 1;'
));
PREPARE drop_col FROM @preparedStatement;
EXECUTE drop_col;
DEALLOCATE PREPARE drop_col;

-- avatar_seed
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'avatar_seed') > 0,
  'ALTER TABLE users DROP COLUMN avatar_seed;',
  'SELECT 1;'
));
PREPARE drop_col FROM @preparedStatement;
EXECUTE drop_col;
DEALLOCATE PREPARE drop_col;

-- avatar_options
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'avatar_options') > 0,
  'ALTER TABLE users DROP COLUMN avatar_options;',
  'SELECT 1;'
));
PREPARE drop_col FROM @preparedStatement;
EXECUTE drop_col;
DEALLOCATE PREPARE drop_col;

-- 5. Remove legacy DiceBear columns from items if they exist
SET @tablename = 'items';

-- dicebear_property
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'dicebear_property') > 0,
  'ALTER TABLE items DROP COLUMN dicebear_property;',
  'SELECT 1;'
));
PREPARE drop_col FROM @preparedStatement;
EXECUTE drop_col;
DEALLOCATE PREPARE drop_col;

-- dicebear_value
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'dicebear_value') > 0,
  'ALTER TABLE items DROP COLUMN dicebear_value;',
  'SELECT 1;'
));
PREPARE drop_col FROM @preparedStatement;
EXECUTE drop_col;
DEALLOCATE PREPARE drop_col;
