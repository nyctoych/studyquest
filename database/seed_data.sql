-- StudyQuest Seed Data
-- Run AFTER studyquest.sql

USE studyquest;

-- =============================================
-- BOSSES
-- =============================================
INSERT INTO bosses (name, description, difficulty, max_hp, icon, xp_reward, point_reward, min_level, subject) VALUES
('The Knowledge Dragon', 'An ancient dragon guarding the secrets of general knowledge. Answer wisely, student!', 'easy', 100, '🐉', 300, 150, 1, 'General'),
('Math Titan', 'A colossal titan who speaks only in equations and riddles. Solve or be crushed!', 'medium', 120, '⚔️', 400, 200, 3, 'Mathematics'),
('Science Sage', 'A mystical sage who commands the forces of science. Only true scholars can defeat him!', 'medium', 120, '🔬', 400, 200, 3, 'Science'),
('History Phantom', 'A ghost from ancient times. Prove your knowledge of history to banish it forever!', 'hard', 150, '👻', 500, 250, 5, 'History'),
('Language Guardian', 'The guardian of words and language. Master vocabulary to claim victory!', 'hard', 150, '📚', 500, 250, 5, 'English'),
('The Code Overlord', 'A digital tyrant who rules the realm of technology. Can you code your way to freedom?', 'legendary', 200, '💻', 700, 350, 7, 'Technology');

-- =============================================
-- BOSS QUESTIONS — The Knowledge Dragon (boss_id=1)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(1, 'What does CPU stand for?', 'Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Computer Processing Utility', 'a', 'easy'),
(1, 'How many colors are in a rainbow?', '5', '6', '7', '8', 'c', 'easy'),
(1, 'What is the capital of France?', 'London', 'Berlin', 'Madrid', 'Paris', 'd', 'easy'),
(1, 'How many sides does a hexagon have?', '5', '6', '7', '8', 'b', 'easy'),
(1, 'What is the largest planet in our solar system?', 'Saturn', 'Neptune', 'Jupiter', 'Uranus', 'c', 'easy'),
(1, 'What gas do plants absorb from the atmosphere?', 'Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen', 'c', 'medium'),
(1, 'Who wrote Romeo and Juliet?', 'Charles Dickens', 'William Shakespeare', 'Jane Austen', 'Mark Twain', 'b', 'medium'),
(1, 'What is the chemical symbol for water?', 'WA', 'HO', 'H2O', 'W2O', 'c', 'easy'),
(1, 'How many bones are in the adult human body?', '196', '206', '216', '226', 'b', 'medium'),
(1, 'What is the speed of light (approx)?', '300,000 km/s', '150,000 km/s', '450,000 km/s', '200,000 km/s', 'a', 'medium');

-- =============================================
-- BOSS QUESTIONS — Math Titan (boss_id=2)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(2, 'What is the value of π (pi) rounded to 2 decimal places?', '3.12', '3.14', '3.16', '3.18', 'b', 'easy'),
(2, 'What is 15% of 200?', '25', '30', '35', '40', 'b', 'easy'),
(2, 'What is the square root of 144?', '10', '11', '12', '13', 'c', 'easy'),
(2, 'If a triangle has angles 60° and 80°, what is the third angle?', '30°', '40°', '50°', '60°', 'b', 'medium'),
(2, 'What is 2 to the power of 10?', '512', '1024', '2048', '256', 'b', 'medium'),
(2, 'What is the area of a circle with radius 5? (Use π=3.14)', '78.5', '31.4', '50.24', '25.14', 'a', 'medium'),
(2, 'Solve: 3x + 12 = 33. What is x?', '5', '6', '7', '8', 'c', 'medium'),
(2, 'What is the Fibonacci sequence? (First 5 terms)', '1,1,2,3,5', '1,2,3,4,5', '0,1,1,2,3', '1,2,4,8,16', 'a', 'hard'),
(2, 'What is the perimeter of a rectangle 8m × 5m?', '13m', '26m', '40m', '20m', 'b', 'easy'),
(2, 'What is the LCM of 4 and 6?', '8', '12', '16', '24', 'b', 'medium');

-- =============================================
-- BOSS QUESTIONS — Science Sage (boss_id=3)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(3, 'What is the atomic number of Carbon?', '4', '6', '8', '12', 'b', 'easy'),
(3, 'Which organelle is known as the powerhouse of the cell?', 'Nucleus', 'Ribosome', 'Mitochondria', 'Chloroplast', 'c', 'easy'),
(3, 'What is Newton''s First Law of Motion about?', 'Force = Mass × Acceleration', 'Inertia', 'Action-Reaction', 'Gravity', 'b', 'medium'),
(3, 'What type of bond involves sharing of electrons?', 'Ionic bond', 'Covalent bond', 'Hydrogen bond', 'Metallic bond', 'b', 'medium'),
(3, 'What is the process by which plants make food?', 'Respiration', 'Fermentation', 'Photosynthesis', 'Digestion', 'c', 'easy'),
(3, 'What is the unit of electric current?', 'Volt', 'Watt', 'Ohm', 'Ampere', 'd', 'medium'),
(3, 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'b', 'easy'),
(3, 'What is the chemical formula for table salt?', 'NaCl', 'KCl', 'CaCl2', 'MgCl2', 'a', 'easy'),
(3, 'How many chromosomes do humans have?', '23', '44', '46', '48', 'c', 'medium'),
(3, 'What is the half-life concept in radioactivity?', 'Time for all atoms to decay', 'Time for half the atoms to decay', 'The energy released per atom', 'The mass lost during decay', 'b', 'hard');

-- =============================================
-- BOSS QUESTIONS — History Phantom (boss_id=4)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(4, 'In what year did World War II end?', '1943', '1944', '1945', '1946', 'c', 'easy'),
(4, 'Who was the first President of the United States?', 'John Adams', 'Thomas Jefferson', 'Benjamin Franklin', 'George Washington', 'd', 'easy'),
(4, 'The French Revolution began in which year?', '1776', '1789', '1799', '1804', 'b', 'medium'),
(4, 'Which ancient wonder was located in Alexandria?', 'The Colossus of Rhodes', 'The Lighthouse of Alexandria', 'The Hanging Gardens', 'The Statue of Zeus', 'b', 'medium'),
(4, 'Who was the Egyptian queen who allied with Julius Caesar?', 'Nefertiti', 'Hatshepsut', 'Cleopatra', 'Berenice', 'c', 'easy'),
(4, 'The Berlin Wall fell in which year?', '1987', '1988', '1989', '1991', 'c', 'medium'),
(4, 'Which empire was the largest in history by land area?', 'Roman Empire', 'British Empire', 'Mongol Empire', 'Ottoman Empire', 'c', 'hard'),
(4, 'Who invented the printing press?', 'Leonardo da Vinci', 'Johannes Gutenberg', 'Isaac Newton', 'Galileo Galilei', 'b', 'medium'),
(4, 'The Magna Carta was signed in what year?', '1066', '1215', '1415', '1492', 'b', 'hard'),
(4, 'Which country was the first to give women the right to vote nationally?', 'USA', 'UK', 'New Zealand', 'Australia', 'c', 'hard');

-- =============================================
-- BOSS QUESTIONS — Language Guardian (boss_id=5)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(5, 'What is the synonym of "benevolent"?', 'Cruel', 'Kind', 'Angry', 'Sad', 'b', 'easy'),
(5, 'Which word is an antonym of "verbose"?', 'Wordy', 'Talkative', 'Concise', 'Elaborate', 'c', 'medium'),
(5, 'What is the plural of "criterion"?', 'Criterions', 'Criterias', 'Criteria', 'Criteriae', 'c', 'medium'),
(5, 'A "protagonist" in a story is the...', 'Villain', 'Main character', 'Narrator', 'Supporting character', 'b', 'easy'),
(5, 'What figure of speech is "The wind whispered through the trees"?', 'Simile', 'Metaphor', 'Personification', 'Hyperbole', 'c', 'medium'),
(5, 'Which sentence uses correct punctuation?', 'Its raining outside', 'It''s raining outside.', 'Its'' raining outside.', 'It''s raining outside', 'b', 'easy'),
(5, 'What does the prefix "macro-" mean?', 'Small', 'Large', 'Many', 'Fast', 'b', 'easy'),
(5, 'The word "ubiquitous" means...', 'Rare and unique', 'Present everywhere', 'Ancient and old', 'Beautiful and elegant', 'b', 'hard'),
(5, 'Which literary device uses exaggeration for effect?', 'Metaphor', 'Alliteration', 'Hyperbole', 'Onomatopoeia', 'c', 'medium'),
(5, '"Whom" is used as which part of speech?', 'Subject', 'Object', 'Verb', 'Adjective', 'b', 'hard');

-- =============================================
-- BOSS QUESTIONS — The Code Overlord (boss_id=6)
-- =============================================
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(6, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Hyper Transfer Markup Link', 'Home Tool Markup Language', 'a', 'easy'),
(6, 'Which data structure operates on LIFO principle?', 'Queue', 'Stack', 'Array', 'Linked List', 'b', 'medium'),
(6, 'What is the time complexity of Binary Search?', 'O(n)', 'O(n²)', 'O(log n)', 'O(1)', 'c', 'hard'),
(6, 'In OOP, what is "encapsulation"?', 'Inheriting from parent class', 'Bundling data and methods together', 'Creating multiple instances', 'Defining abstract methods', 'b', 'medium'),
(6, 'What does CSS stand for?', 'Computer Style Sheets', 'Creative Style Syntax', 'Cascading Style Sheets', 'Coded Style System', 'c', 'easy'),
(6, 'Which SQL command retrieves data?', 'INSERT', 'UPDATE', 'SELECT', 'DELETE', 'c', 'easy'),
(6, 'What is a "null pointer exception" in programming?', 'Out of memory error', 'Accessing an uninitialized reference', 'Integer overflow', 'Division by zero', 'b', 'medium'),
(6, 'What does API stand for?', 'Application Programming Interface', 'Automated Program Integration', 'Application Protocol Interface', 'Advanced Programming Index', 'a', 'easy'),
(6, 'Which protocol is used for secure web browsing?', 'HTTP', 'FTP', 'HTTPS', 'SMTP', 'c', 'easy'),
(6, 'What is recursion in programming?', 'A loop that runs forever', 'A function that calls itself', 'An error handling technique', 'A type of variable', 'b', 'medium');

-- =============================================
-- ACHIEVEMENTS
-- =============================================
INSERT INTO achievements (name, description, icon, category, condition_type, condition_value, xp_reward, point_reward, rarity) VALUES
('First Quest', 'Complete your very first quest. Every journey begins with a single step!', '⭐', 'academic', 'quests_completed', 1, 50, 25, 'common'),
('Quest Apprentice', 'Complete 5 quests. You''re getting the hang of this!', '📜', 'academic', 'quests_completed', 5, 75, 40, 'common'),
('Quest Knight', 'Complete 10 quests. A true academic warrior!', '⚔️', 'academic', 'quests_completed', 10, 100, 50, 'common'),
('Quest Master', 'Complete 25 quests. Mastery takes dedication!', '🏆', 'academic', 'quests_completed', 25, 200, 100, 'rare'),
('Quest Legend', 'Complete 50 quests. You are a legend among students!', '👑', 'academic', 'quests_completed', 50, 400, 200, 'epic'),
('Level Up!', 'Reach Level 5. Your dedication is paying off!', '⬆️', 'academic', 'level_reached', 5, 150, 75, 'common'),
('Level 10', 'Reach Level 10. You''re in the top tier now!', '💎', 'academic', 'level_reached', 10, 300, 150, 'rare'),
('3 Day Streak', 'Study 3 days in a row. Consistency is key!', '🔥', 'streak', 'streak_days', 3, 75, 40, 'common'),
('Week Warrior', 'Maintain a 7-day study streak. One full week of dedication!', '🌟', 'streak', 'streak_days', 7, 150, 75, 'rare'),
('Month Master', 'Maintain a 30-day study streak. Incredible discipline!', '🏅', 'streak', 'streak_days', 30, 500, 250, 'legendary'),
('Dragon Slayer', 'Defeat your first Boss. You faced the challenge and won!', '🐉', 'game', 'bosses_defeated', 1, 200, 100, 'rare'),
('Boss Slayer', 'Defeat 5 Bosses. Nothing can stop you!', '⚔️', 'game', 'bosses_defeated', 5, 400, 200, 'epic'),
('Rich Student', 'Accumulate 1000 points. Wealth follows hard work!', '💰', 'special', 'points_earned', 1000, 100, 0, 'common'),
('Epic Scholar', 'Reach Level 15 with a 14-day streak. True excellence!', '✨', 'special', 'level_reached', 15, 600, 300, 'legendary');

-- =============================================
-- REWARDS / SHOP ITEMS
-- =============================================
INSERT INTO rewards (name, description, category, cost, icon, effect_type, effect_value, rarity) VALUES
-- Powerups
('Study Potion', 'Doubles XP for your next quest completion!', 'powerup', 300, '🧪', 'xp_boost', '2x', 'rare'),
('Streak Shield', 'Protects your study streak for 1 day if you miss!', 'powerup', 500, '🛡️', 'streak_shield', '1', 'rare'),
('Lucky Charm', 'Increases point reward for next 3 quests by 50%!', 'powerup', 400, '🍀', 'point_boost', '1.5x', 'rare'),
-- Cosmetics
('Golden Frame', 'A golden profile frame. Show off your status!', 'cosmetic', 1500, '✨', 'profile_frame', 'gold', 'epic'),
('Night Sky Background', 'A beautiful night sky background for your profile.', 'cosmetic', 2000, '🌙', 'profile_background', 'night_sky', 'legendary'),
-- Special
('Mystery Box', 'A mysterious box containing a random reward!', 'special', 300, '🎁', 'mystery', 'random', 'rare'),
('XP Multiplier (24h)', 'All XP earned in the next 24 hours is doubled!', 'powerup', 700, '⭐', 'xp_boost_timed', '24h', 'epic');

