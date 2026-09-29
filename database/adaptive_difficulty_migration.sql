-- ============================================================
-- STUDYQUEST: ADAPTIVE PSYCHOLOGY QUESTION DIFFICULTY MIGRATION
-- ============================================================

USE studyquest;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Alter question_attempts to support topic, category, and response time
-- Using stored procedure to conditionally add columns if they don't exist
DELIMITER //
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(64),
    IN colName VARCHAR(64),
    IN colDef VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = tableName
          AND COLUMN_NAME = colName
    ) THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', colName, ' ', colDef);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL AddColumnIfNotExists('question_attempts', 'category_id', 'INT DEFAULT NULL AFTER question_id');
CALL AddColumnIfNotExists('question_attempts', 'topic_id', 'INT DEFAULT NULL AFTER category_id');
CALL AddColumnIfNotExists('question_attempts', 'response_time', 'INT DEFAULT 0 AFTER is_correct');

DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Backfill category_id and topic_id for any existing attempts
UPDATE question_attempts qa
JOIN questions q ON qa.question_id = q.id
SET qa.category_id = q.category_id,
    qa.topic_id = q.topic_id
WHERE qa.category_id IS NULL;

-- 2. Create user_learning_profiles
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

-- 3. Create user_category_performance
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

-- 4. Create user_topic_performance
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

-- 5. Seed detailed subtopics for Psychology Domains
INSERT IGNORE INTO question_topics (category_id, name, slug, description) VALUES
-- General Psychology (Cat 1)
(1, 'History and Schools of Thought', 'history-and-schools', 'Foundations from Wundt and James to modern paradigms'),
(1, 'Consciousness and Sleep', 'consciousness-and-sleep', 'States of awareness, circadian rhythms, and sleep stages'),
(1, 'Language and Thought', 'language-thought', 'Linguistic relativity, grammar development, and speech'),

-- Biological Psychology (Cat 2)
(2, 'Neural Communication and Neurotransmitters', 'neurotransmitters', 'Action potentials, synaptic transmission, and neurotransmitters'),
(2, 'Brain Structures and Functions', 'brain-structures', 'Cerebral cortex, limbic system, and brainstem physiology'),
(2, 'Endocrine System and Genetics', 'endocrine-genetics', 'Hormones, behavioral genetics, and epigenetics'),

-- Cognitive Psychology (Cat 3)
(3, 'Memory Models and Retrieval', 'memory-models', 'Working memory, encoding, retrieval, and amnesia'),
(3, 'Attention and Executive Function', 'attention-executive', 'Selective attention, divided attention, and cognitive control'),
(3, 'Problem Solving and Decision Making', 'problem-solving', 'Heuristics, biases, algorithms, and insight'),

-- Developmental Psychology (Cat 4)
(4, 'Piaget and Vygotsky Cognitive Stages', 'cognitive-development', 'Cognitive development models and scaffolding'),
(4, 'Erikson Psychosocial Stages', 'psychosocial-stages', 'Crisis resolution across lifespan from infancy to late adulthood'),
(4, 'Attachment and Moral Development', 'attachment-moral', 'Bowlby, Ainsworth attachment styles, Kohlberg moral stages'),

-- Social Psychology (Cat 5)
(5, 'Social Cognition and Attribution', 'attribution-theory', 'Fundamental attribution error, schemas, and stereotypes'),
(5, 'Conformity, Obedience, and Group Dynamics', 'conformity-obedience', 'Asch, Milgram, Zimbardo experiments and groupthink'),
(5, 'Attitudes, Persuasion, and Aggression', 'attitudes-persuasion', 'Cognitive dissonance, elaboration likelihood, prosocial behavior'),

-- Personality Psychology (Cat 6)
(6, 'Big Five Trait Theory', 'big-five-traits', 'Ocean dimensions, stability, and psychometrics'),
(6, 'Humanistic and Phenomenological Models', 'humanistic-personality', 'Rogers self-concept, Maslow hierarchy, and actualization'),
(6, 'Biological and Behavioral Personality', 'biological-behavioral-personality', 'Eysenck PEN model and Gray reinforcement sensitivity'),

-- Abnormal Psychology (Cat 7)
(7, 'Mood and Anxiety Disorders', 'mood-anxiety-disorders', 'MDD, Bipolar, GAD, Panic, Phobias, and OCD'),
(7, 'Schizophrenia Spectrum and Psychosis', 'schizophrenia-spectrum', 'Positive/negative symptoms, dopamine hypothesis, and prognosis'),
(7, 'Personality Disorders and Trauma', 'personality-disorders-trauma', 'Cluster A/B/C traits, PTSD, and acute stress disorder'),

-- Clinical Psychology (Cat 8)
(8, 'Cognitive Behavioral Therapy (CBT)', 'cbt-interventions', 'Automatic thoughts, schemas, behavioral activation'),
(8, 'Psychodynamic and Interpersonal Therapies', 'psychodynamic-therapy', 'Transference, defense mechanisms, and IPT'),
(8, 'Third-Wave and Humanistic Therapies', 'third-wave-therapies', 'ACT, DBT, mindfulness-based interventions, and emotion-focused'),

-- Counseling Psychology (Cat 9)
(9, 'Person-Centered Counseling', 'person-centered', 'Core conditions: empathy, unconditional positive regard, congruence'),
(9, 'Crisis Intervention and Trauma Support', 'crisis-intervention', 'De-escalation, suicide risk assessment, and safety planning'),
(9, 'Career Counseling and Lifespan Transitions', 'career-counseling', 'Holland RIASEC, Super life-span model, and transition support'),

-- Industrial/Organizational Psychology (Cat 10)
(10, 'Job Analysis and Personnel Selection', 'job-analysis', 'KSAOs, structured interviews, psychometric test validation'),
(10, 'Organizational Culture and Leadership', 'org-culture-leadership', 'Transformational leadership, Schein model, job satisfaction'),
(10, 'Work Motivation and Performance Appraisal', 'work-motivation', 'Goal-setting theory, expectancy theory, and 360-degree review'),

-- Psychological Assessment (Cat 11)
(11, 'Intelligence and Cognitive Batteries', 'intelligence-batteries', 'WAIS-IV, WISC-V, Stanford-Binet, Gf-Gc theory'),
(11, 'Objective and Projective Personality Tests', 'personality-batteries', 'MMPI-2-RF, PAI, Rorschach Exner, TAT'),
(11, 'Clinical Interviewing and Report Writing', 'clinical-report-writing', 'Mental status exam, test battery integration, formulation'),

-- Research Methods (Cat 12)
(12, 'Experimental and Quasi-Experimental Designs', 'experimental-designs', 'Random assignment, control groups, internal/external validity'),
(12, 'Correlation, Observational, and Survey Designs', 'correlational-methods', 'Directionality, third-variable problem, naturalistic observation'),
(12, 'Measurement, Sampling, and Validity', 'sampling-validity', 'Stratified sampling, ecological validity, and construct validity'),

-- Psychological Statistics (Cat 13)
(13, 'Inferential Statistics and Hypothesis Testing', 'inferential-stats', 'Null hypothesis, t-tests, ANOVA, Type I and II errors, effect size'),
(13, 'Descriptive Statistics and Normal Distribution', 'descriptive-stats', 'Central tendency, variability, z-scores, skewness'),
(13, 'Correlation, Regression, and Non-Parametric Tests', 'correlation-regression', 'Pearson r, multiple regression, Chi-Square, Mann-Whitney U'),

-- Psychological Testing (Cat 14)
(14, 'Reliability and Classical Test Theory', 'reliability-ctt', 'Test-retest, Cronbach alpha, standard error of measurement'),
(14, 'Validity and Test Construction', 'validity-construction', 'Content, criterion, construct validity, item difficulty, and discrimination'),
(14, 'Item Response Theory and Standardization', 'irt-standardization', 'Item characteristic curves, norm groups, percentile ranks'),

-- Ethics and Professional Practice (Cat 15)
(15, 'Confidentiality and Tarasoff Duty', 'confidentiality-tarasoff', 'Duty to protect/warn, limits of confidentiality, subpoena response'),
(15, 'Informed Consent and Competence', 'informed-consent-competence', 'Scope of practice, assent for minors, cultural competence'),
(15, 'Multiple Relationships and Boundaries', 'boundaries-ethics', 'Dual relationships, conflicts of interest, termination ethics'),

-- Theories of Personality (Cat 16)
(16, 'Freudian and Neo-Freudian Psychodynamics', 'freudian-neo-freudian', 'Jung analytical psychology, Adler individual psychology, Horney'),
(16, 'Trait and Biological Personality Models', 'trait-models', 'Allport cardinal traits, Cattell 16PF, Eysenck'),
(16, 'Social-Cognitive Personality Models', 'social-cognitive-personality', 'Bandura reciprocal determinism, Rotter locus of control, Mischel CAPS'),

-- Learning and Behavior (Cat 17)
(17, 'Classical Conditioning Principles', 'classical-conditioning', 'UCS, UCR, CS, CR, extinction, spontaneous recovery, stimulus generalization'),
(17, 'Operant Conditioning & Reinforcement Schedules', 'reinforcement-schedules', 'Positive/negative reinforcement, punishment, VR, FR, VI, FI schedules'),
(17, 'Observational and Social Learning', 'observational-learning', 'Bandura Bobo doll, vicarious reinforcement, modeling processes'),

-- Motivation and Emotion (Cat 18)
(18, 'Theories of Emotion', 'theories-of-emotion', 'James-Lange, Cannon-Bard, Schachter-Singer Two-Factor, Lazarus appraisal'),
(18, 'Self-Determination and Need Theories', 'self-determination', 'Deci & Ryan SDT, intrinsic vs extrinsic, McClelland acquired needs'),
(18, 'Physiological and Evolutionary Motivation', 'physiological-motivation', 'Homeostasis, hypothalamus hunger centers, arousal theory, Yerkes-Dodson'),

-- Sensation and Perception (Cat 19)
(19, 'Sensory Thresholds and Psychophysics', 'sensory-thresholds', 'Absolute threshold, difference threshold, Weber Law, Signal Detection Theory'),
(19, 'Visual and Auditory Systems', 'visual-auditory', 'Photoreceptors, cochlea, pitch theories, color vision theories'),
(19, 'Perceptual Organization and Illusions', 'perceptual-organization', 'Gestalt grouping laws, depth perception, constancies, top-down processing'),

-- Health Psychology (Cat 20)
(20, 'Stress and General Adaptation Syndrome', 'stress-gas-model', 'Hans Selye GAS stages, HPA axis, cortisol, psychoneuroimmunology'),
(20, 'Biopsychosocial Models and Coping', 'biopsychosocial-coping', 'Problem-focused vs emotion-focused coping, locus of control, resilience'),
(20, 'Chronic Illness and Behavioral Adherence', 'illness-adherence', 'Health belief model, theory of planned behavior, lifestyle interventions');

-- Assign appropriate topic_id to questions that have null topic_id based on category defaults
UPDATE questions q
SET q.topic_id = (
    SELECT qt.id FROM question_topics qt 
    WHERE qt.category_id = q.category_id 
    ORDER BY qt.id ASC 
    LIMIT 1
)
WHERE q.topic_id IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
