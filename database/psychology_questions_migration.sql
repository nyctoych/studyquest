-- ==========================================================
-- STUDYQUEST: PSYCHOLOGY QUESTIONS & BOARD-EXAM SYSTEM MIGRATION
-- ==========================================================

USE studyquest;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure/Update question_categories Table
CREATE TABLE IF NOT EXISTS question_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    icon VARCHAR(10) DEFAULT '🧠',
    color VARCHAR(20) DEFAULT '#6366f1'
) ENGINE=InnoDB;

TRUNCATE TABLE question_categories;

-- Insert or replace the 20 official Psychology Categories
INSERT INTO question_categories (id, name, slug, description, icon, color) VALUES
(1, 'General Psychology', 'general-psychology', 'Foundational principles, schools of thought, and introductory concepts.', '🧠', '#6366f1'),
(2, 'Biological Psychology', 'biological-psychology', 'Neuroscience, brain anatomy, neurotransmitters, and physiological bases of behavior.', '🧬', '#ec4899'),
(3, 'Cognitive Psychology', 'cognitive-psychology', 'Attention, memory, perception, problem solving, and language processing.', '💡', '#3b82f6'),
(4, 'Developmental Psychology', 'developmental-psychology', 'Lifespan development, cognitive growth, psychosocial crises, and maturation.', '🌱', '#10b981'),
(5, 'Social Psychology', 'social-psychology', 'Social influence, group dynamics, attitudes, conformity, and interpersonal behavior.', '👥', '#f59e0b'),
(6, 'Personality Psychology', 'personality-psychology', 'Trait theories, psychodynamics, humanistic models, and individual differences.', '🎭', '#8b5cf6'),
(7, 'Abnormal Psychology', 'abnormal-psychology', 'Psychopathology, DSM-5 diagnostic criteria, etiology, and clinical syndromes.', '⚡', '#ef4444'),
(8, 'Clinical Psychology', 'clinical-psychology', 'Psychotherapy modalities, clinical interventions, diagnostics, and patient formulation.', '🩺', '#06b6d4'),
(9, 'Counseling Psychology', 'counseling-psychology', 'Facilitative skills, career counseling, crisis intervention, and wellness models.', '🤝', '#14b8a6'),
(10, 'Industrial/Organizational Psychology', 'io-psychology', 'Workplace behavior, job analysis, personnel selection, leadership, and org culture.', '🏢', '#f97316'),
(11, 'Psychological Assessment', 'psychological-assessment', 'Administration, scoring, interpretation of psychological batteries and clinical reports.', '📋', '#64748b'),
(12, 'Research Methods', 'research-methods', 'Experimental designs, sampling, validity, reliability, and scientific methodology.', '🔬', '#a855f7'),
(13, 'Psychological Statistics', 'psychological-statistics', 'Parametric/nonparametric statistics, ANOVA, hypothesis testing, and data analysis.', '📊', '#0ea5e9'),
(14, 'Psychological Testing', 'psychological-testing', 'Psychometrics, test construction, standardization, norms, and item analysis.', '📐', '#eab308'),
(15, 'Ethics and Professional Practice', 'ethics-and-professional-practice', 'Codes of ethics, confidentiality, informed consent, competence, and licensure standards.', '⚖️', '#d97706'),
(16, 'Theories of Personality', 'theories-of-personality', 'In-depth comparative analysis of psychoanalytic, neo-Freudian, and cognitive personality models.', '📜', '#84cc16'),
(17, 'Learning and Behavior', 'learning-and-behavior', 'Classical conditioning, operant conditioning, reinforcement schedules, and behavior modification.', '🎯', '#10b981'),
(18, 'Motivation and Emotion', 'motivation-and-emotion', 'Drive theories, physiological arousal, affective states, and intrinsic/extrinsic motivation.', '🔥', '#f43f5e'),
(19, 'Sensation and Perception', 'sensation-and-perception', 'Sensory thresholds, psychophysics, visual/auditory processing, and perceptual illusions.', '👁️', '#6366f1'),
(20, 'Health Psychology', 'health-psychology', 'Biopsychosocial model of health, chronic illness, stress management, and behavioral medicine.', '❤️', '#ec4899')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    description = VALUES(description),
    icon = VALUES(icon),
    color = VALUES(color);

-- 2. Create question_topics Table
CREATE TABLE IF NOT EXISTS question_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES question_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uk_cat_topic (category_id, slug)
) ENGINE=InnoDB;

-- Seed core topics
INSERT IGNORE INTO question_topics (category_id, name, slug, description) VALUES
(1, 'History and Schools of Psychology', 'history-and-schools', 'Foundations from Wundt and James to modern paradigms'),
(2, 'Neural Communication and Neurotransmitters', 'neurotransmitters', 'Action potentials, synaptic transmission, and chemical messengers'),
(3, 'Memory Models and Retrieval', 'memory-models', 'Sensory, short-term, working memory, and long-term storage'),
(4, 'Piaget and Vygotsky Cognitive Stages', 'cognitive-development', 'Sensorimotor to formal operations and zone of proximal development'),
(5, 'Social Cognition and Attribution', 'attribution-theory', 'Fundamental attribution error, self-serving bias, and heuristic processing'),
(6, 'Big Five Trait Theory', 'big-five-traits', 'Openness, Conscientiousness, Extraversion, Agreeableness, Neuroticism'),
(7, 'Mood and Anxiety Disorders', 'mood-anxiety-disorders', 'MDD, Bipolar, GAD, Panic Disorder, and Phobias'),
(8, 'Cognitive Behavioral Therapy (CBT)', 'cbt-interventions', 'Cognitive restructuring, automatic thoughts, and behavioral experiments'),
(9, 'Person-Centered Counseling', 'person-centered', 'Unconditional positive regard, empathy, and congruence by Carl Rogers'),
(10, 'Job Analysis and Performance Appraisal', 'job-analysis', 'KSAOs, critical incident technique, and 360-degree feedback'),
(11, 'Intelligence and Personality Batteries', 'clinical-batteries', 'WAIS, WISC, MMPI-2, and projective assessments'),
(12, 'Experimental and Quasi-Experimental Designs', 'experimental-designs', 'Independent/dependent variables, internal/external validity'),
(13, 'Inferential Statistics and Hypothesis Testing', 'inferential-stats', 't-tests, ANOVA, p-values, Type I and Type II errors'),
(14, 'Reliability and Validity in Psychometrics', 'psychometric-properties', 'Test-retest, internal consistency, construct and criterion validity'),
(15, 'Confidentiality and Tarasoff Duty', 'confidentiality-and-tarasoff', 'Privileged communication, exceptions, and duty to protect'),
(16, 'Freudian Psychoanalysis', 'freudian-psychoanalysis', 'Id, ego, superego, defense mechanisms, and psychosexual stages'),
(17, 'Operant Conditioning & Reinforcement Schedules', 'reinforcement-schedules', 'Fixed/variable ratio and interval reinforcement schedules'),
(18, 'Self-Determination Theory & Drives', 'self-determination', 'Autonomy, competence, relatedness, and Maslow hierarchy'),
(19, 'Trichromatic & Opponent-Process Theory', 'color-vision', 'Photoreceptors, retinal ganglion cells, and color perception'),
(20, 'General Adaptation Syndrome (GAS)', 'stress-gas-model', 'Alarm, resistance, and exhaustion stages by Hans Selye');

-- 3. Ensure/Upgrade questions Table Structure
DROP TABLE IF EXISTS questions;
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    topic_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    choice_a VARCHAR(500) NOT NULL,
    choice_b VARCHAR(500) NOT NULL,
    choice_c VARCHAR(500) NOT NULL,
    choice_d VARCHAR(500) NOT NULL,
    correct_answer ENUM('a','b','c','d') NOT NULL,
    difficulty ENUM('beginner','intermediate','advanced','board_exam') DEFAULT 'intermediate',
    recommended_level_min INT DEFAULT 1,
    recommended_level_max INT DEFAULT 99,
    explanation TEXT DEFAULT NULL,
    source_reference VARCHAR(255) DEFAULT NULL,
    status ENUM('draft','under_review','approved','rejected','archived') DEFAULT 'approved',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES question_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES question_topics(id) ON DELETE SET NULL,
    INDEX idx_questions_diff_status (difficulty, status),
    INDEX idx_questions_cat_diff (category_id, difficulty),
    INDEX idx_questions_level_range (recommended_level_min, recommended_level_max)
) ENGINE=InnoDB;

-- 4. Create question_attempts Table (for Student Performance & Weak-Topic Detection)
CREATE TABLE IF NOT EXISTS question_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    battle_id VARCHAR(50) DEFAULT NULL,
    is_correct TINYINT(1) NOT NULL,
    response_time INT DEFAULT 0, -- in seconds or ms
    difficulty ENUM('beginner','intermediate','advanced','board_exam') DEFAULT 'intermediate',
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_qa_user_perf (user_id, is_correct),
    INDEX idx_qa_user_question (user_id, question_id)
) ENGINE=InnoDB;

-- 5. Seed Official Psychology Board-Exam Question Bank (Curated, Multi-Tiered)
-- Remove any legacy non-psychology questions
DELETE FROM questions WHERE status = 'approved' AND id <= 500;

INSERT INTO questions 
(category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, recommended_level_min, recommended_level_max, explanation, source_reference, status)
VALUES
-- =================== LEVEL 1–5: BEGINNER (Remember / Identify) ===================
(1, 'Who established the first formal psychology research laboratory in Leipzig, Germany in 1879?', 
 'Sigmund Freud', 'William James', 'Wilhelm Wundt', 'John B. Watson', 
 'c', 'beginner', 1, 5, 
 'Wilhelm Wundt is recognized as the father of modern psychology for founding the first experimental laboratory in Leipzig in 1879.', 'Schultz & Schultz, A History of Modern Psychology', 'approved'),

(1, 'Which psychological perspective focuses primarily on observable behaviors and external stimuli rather than internal mental states?', 
 'Behaviorism', 'Psychodynamic perspective', 'Gestalt psychology', 'Humanistic perspective', 
 'a', 'beginner', 1, 5, 
 'Behaviorism, pioneered by Watson and Skinner, emphasizes that psychology should be a science of observable behavior.', 'Passer & Smith, Psychology: The Science of Mind and Behavior', 'approved'),

(2, 'Which part of a neuron is primarily responsible for receiving incoming chemical signals from neighboring neurons?', 
 'Axon hillock', 'Dendrites', 'Myelin sheath', 'Terminal buttons', 
 'b', 'beginner', 1, 5, 
 'Dendrites are branching extensions that receive incoming neurotransmitter signals and conduct impulses toward the cell body.', 'Kalat, Biological Psychology', 'approved'),

(2, 'The central nervous system (CNS) consists of which two anatomical components?', 
 'Sympathetic and parasympathetic systems', 'Brain and spinal cord', 'Somatic and autonomic divisions', 'Cerebrum and cerebellum only', 
 'b', 'beginner', 1, 5, 
 'The central nervous system consists exclusively of the brain and the spinal cord.', 'Pinel, Biopsychology', 'approved'),

(3, 'Which memory system holds visual sensory information for a brief fraction of a second before decay?', 
 'Echoic memory', 'Iconic memory', 'Procedural memory', 'Episodic memory', 
 'b', 'beginner', 1, 5, 
 'Iconic memory is the visual sensory register discovered by George Sperling, lasting approximately 250 to 500 milliseconds.', 'Sperling (1960); Sternberg, Cognitive Psychology', 'approved'),

(4, 'According to Jean Piaget, in which stage of cognitive development do children achieve an understanding of object permanence?', 
 'Sensorimotor stage', 'Preoperational stage', 'Concrete operational stage', 'Formal operational stage', 
 'a', 'beginner', 1, 5, 
 'Object permanence—understanding that objects continue to exist even when hidden—develops during Piaget sensorimotor stage (birth to ~2 years).', 'Piaget, The Construction of Reality in the Child', 'approved'),

(5, 'What social psychological concept refers to the reduction in individual effort when working collectively in a group compared to working alone?', 
 'Group polarization', 'Social facilitation', 'Social loafing', 'Deindividuation', 
 'c', 'beginner', 1, 5, 
 'Social loafing is the tendency for people to exert less effort when pooling efforts toward a common goal than when individually accountable.', 'Latané, Williams, & Harkins (1979)', 'approved'),

(6, 'Which of the following represents the five dimensions of the Big Five / Five-Factor Model of personality?', 
 'Openness, Conscientiousness, Extraversion, Agreeableness, Neuroticism', 
 'Introversion, Extraversion, Psychoticism, Stability, Dominance', 
 'Id, Ego, Superego, Libido, Thanatos', 
 'Cardinal traits, Central traits, Secondary traits, Cardinal types, Surface traits', 
 'a', 'beginner', 1, 5, 
 'The Big Five framework (acronym OCEAN) encompasses Openness, Conscientiousness, Extraversion, Agreeableness, and Neuroticism.', 'Costa & McCrae, Revised NEO Personality Inventory', 'approved'),

(7, 'Which diagnostic classification manual is published by the American Psychiatric Association (APA) and widely used for mental disorders?', 
 'ICD-11', 'DSM-5-TR', 'PCL-R', 'MMPI-3', 
 'b', 'beginner', 1, 5, 
 'The Diagnostic and Statistical Manual of Mental Disorders, Fifth Edition, Text Revision (DSM-5-TR) is the standard classification in clinical psychology.', 'American Psychiatric Association (2022)', 'approved'),

(17, 'In Pavlov famous experiments with dogs, what was the meat powder initially before conditioning took place?', 
 'Conditioned stimulus (CS)', 'Unconditioned stimulus (UCS)', 'Conditioned response (CR)', 'Unconditioned response (UCR)', 
 'b', 'beginner', 1, 5, 
 'The meat powder naturally triggered salivation without prior learning, making it an unconditioned stimulus (UCS).', 'Pavlov, Conditioned Reflexes', 'approved'),

(18, 'According to Abraham Maslow hierarchy of needs, which level of needs must be satisfied first before higher-order needs can be addressed?', 
 'Safety needs', 'Belongingness needs', 'Physiological needs', 'Self-actualization', 
 'c', 'beginner', 1, 5, 
 'Physiological needs (food, water, warmth, sleep) form the fundamental base of Maslow hierarchy of needs.', 'Maslow, Motivation and Personality', 'approved'),

(15, 'What is the primary ethical principle requiring psychologists to obtain clear consent regarding the nature, risks, and limits of psychological services before beginning treatment?', 
 'Privileged communication', 'Informed consent', 'Competence', 'Beneficence', 
 'b', 'beginner', 1, 5, 
 'Informed consent ensures clients understand the goals, procedures, fees, and confidentiality limits before initiating services.', 'APA Ethical Principles of Psychologists and Code of Conduct', 'approved'),

-- =================== LEVEL 6–15: INTERMEDIATE (Understand / Apply) ===================
(1, 'A child begins to display fear whenever hearing the chime of an ice cream truck because the chime was repeatedly paired with a vicious dog barking. Which process explains this learned fear?', 
 'Operant reinforcement', 'Classical conditioning', 'Vicarious punishment', 'Latent learning', 
 'b', 'intermediate', 6, 15, 
 'A neutral stimulus (truck chime) repeatedly paired with an aversive unconditioned stimulus (barking dog) becomes a conditioned stimulus evoking a conditioned response.', 'Domjan, The Principles of Learning and Behavior', 'approved'),

(2, 'A patient who suffers a stroke in Broca area of the left frontal lobe will most likely exhibit which characteristic communication deficit?', 
 'Fluent speech with severely impaired auditory comprehension', 
 'Total inability to recognize familiar human faces', 
 'Non-fluent, labored, telegraphic speech with relatively intact comprehension', 
 'Inability to transfer short-term memories into long-term storage', 
 'c', 'intermediate', 6, 15, 
 'Damage to Broca area results in expressive (motor) aphasia, characterized by halting, effortful speech while language comprehension remains largely intact.', 'Gazzaniga, Cognitive Neuroscience', 'approved'),

(3, 'According to Baddeley multicomponent working memory model, which subsystem is responsible for processing and rehearsing speech-based information?', 
 'Visuospatial sketchpad', 'Phonological loop', 'Episodic buffer', 'Central executive', 
 'b', 'intermediate', 6, 15, 
 'The phonological loop consists of a phonological store and an articulatory rehearsal mechanism for verbal and auditory information.', 'Baddeley, Working Memory, Thought, and Action', 'approved'),

(4, 'According to Erik Erikson psychosocial stages of development, an adolescent struggling to develop a cohesive sense of personal values and future direction is facing which crisis?', 
 'Industry vs. Inferiority', 'Identity vs. Role Confusion', 'Intimacy vs. Isolation', 'Generativity vs. Stagnation', 
 'b', 'intermediate', 6, 15, 
 'Adolescence (ages 12-18) centers on Erikson crisis of Identity vs. Role Confusion, synthesizing roles and personal identity.', 'Erikson, Childhood and Society', 'approved'),

(5, 'When explaining our own failures, we tend to cite situational factors, yet when explaining our successes, we attribute them to internal disposition. What cognitive bias is this?', 
 'Fundamental attribution error', 'Self-serving bias', 'Confirmation bias', 'Halo effect', 
 'b', 'intermediate', 6, 15, 
 'The self-serving bias is the pervasive tendency to attribute positive outcomes to internal causes and negative outcomes to external circumstances.', 'Miller & Ross (1975); Aronson, The Social Animal', 'approved'),

(6, 'According to Carl Rogers person-centered theory, psychological distress and neurotic anxiety primarily arise from a state of:', 
 'Repressed aggressive instincts in the unconscious', 
 'Incongruence between the self-concept and the organismic ideal self', 
 'Deficits in social learning reinforcement schedules', 
 'Malfunctioning neurotransmitter reuptake pumps', 
 'b', 'intermediate', 6, 15, 
 'Rogers posited that incongruence between a person perceived self-concept and their actual organismic experiences produces anxiety and defense.', 'Rogers, Client-Centered Therapy', 'approved'),

(7, 'A client experiences sudden, recurrent episodes of intense terror, accompanied by palpitations, sweating, shortness of breath, and a fear of losing control, occurring without an identifiable trigger. Which diagnosis is most likely?', 
 'Generalized Anxiety Disorder', 'Panic Disorder', 'Specific Phobia', 'Social Anxiety Disorder', 
 'b', 'intermediate', 6, 15, 
 'Panic disorder is defined by recurrent unexpected panic attacks accompanied by persistent concern about future attacks or their consequences.', 'DSM-5-TR, American Psychiatric Association', 'approved'),

(12, 'In an experiment investigating whether a new memory technique enhances test scores, what role do the test scores represent in the research design?', 
 'Independent variable', 'Dependent variable', 'Confounding variable', 'Extraneous baseline', 
 'b', 'intermediate', 6, 15, 
 'The dependent variable is the measured outcome variable hypothesized to change as a function of manipulations in the independent variable.', 'Gravetter & Forzano, Research Methods for the Behavioral Sciences', 'approved'),

(13, 'If a test of mechanical aptitude yields a normal distribution with a mean of 100 and a standard deviation of 15, what percentage of examinees score between 85 and 115?', 
 'Approximately 50%', 'Approximately 68%', 'Approximately 95%', 'Approximately 99.7%', 
 'b', 'intermediate', 6, 15, 
 'In any normal distribution, approximately 68.26% of all scores fall within one standard deviation (+/- 1 SD) of the mean.', 'Aiken & Groth-Marnat, Psychological Testing and Assessment', 'approved'),

(14, 'A psychological test that yields consistent and stable results across repeated administrations to the same individuals possesses high:', 
 'Construct validity', 'Test-retest reliability', 'Criterion-related validity', 'Face validity', 
 'b', 'intermediate', 6, 15, 
 'Test-retest reliability reflects the degree to which test scores remain consistent and reproducible over time.', 'Anastasi & Urbina, Psychological Testing', 'approved'),

(15, 'Under the Tarasoff court ruling (Tarasoff v. Regents of the University of California), when is a mental health professional legally and ethically mandated to breach client confidentiality?', 
 'When the client confesses to past petty theft', 
 'When the client poses a serious, explicit, and imminent threat of physical violence to an identifiable third party', 
 'Whenever the client spouse requests clinical progress notes', 
 'When the client discloses an unconsummated extramarital affair', 
 'b', 'intermediate', 6, 15, 
 'The Tarasoff ruling established the duty to protect/warn identifiable victims when a client presents an imminent, serious risk of physical violence.', 'Tarasoff v. Regents of the Univ. of California (1976)', 'approved'),

(17, 'A slot machine that rewards gamblers with money after an unpredictable, varying number of lever pulls operates on which schedule of reinforcement?', 
 'Fixed ratio schedule', 'Variable ratio schedule', 'Fixed interval schedule', 'Variable interval schedule', 
 'b', 'intermediate', 6, 15, 
 'A variable-ratio schedule delivers reinforcement after an unpredictable number of responses, generating high, steady rates of responding resistant to extinction.', 'Skinner, The Behavior of Organisms', 'approved'),

-- =================== LEVEL 16–25: ADVANCED (Analyze / Interpret) ===================
(7, 'A 32-year-old patient presents with a two-month history of depressed mood, profound anhedonia, psychomotor retardation, insomnia, feelings of worthlessness, and a 6 kg unintentional weight loss. The patient has never experienced an elevated, expansive, or irritable mood. Which diagnosis is indicated?', 
 'Persistent Depressive Disorder (Dysthymia)', 
 'Bipolar II Disorder, Depressed Episode', 
 'Major Depressive Disorder, Single Episode', 
 'Cyclothymic Disorder', 
 'c', 'advanced', 16, 25, 
 'Meeting five of nine symptoms including depressed mood and anhedonia for at least two weeks without history of hypomania or mania establishes Major Depressive Disorder.', 'DSM-5-TR, Major Depressive Disorder Criteria', 'approved'),

(8, 'In cognitive therapy for depression, Aaron Beck described the "Cognitive Triad," which consists of negative automatic beliefs regarding which three domains?', 
 'The id, the ego, and the superego', 
 'The self, the personal world/experiences, and the future', 
 'Parents, peers, and authority figures', 
 'Thoughts, emotional reactions, and motor behaviors', 
 'b', 'advanced', 16, 25, 
 'Beck cognitive triad posits that depressed individuals hold negative, distorted schemas about the Self, the World (ongoing experiences), and the Future.', 'Beck, Cognitive Therapy of Depression', 'approved'),

(11, 'When evaluating the Minnesota Multiphasic Personality Inventory (MMPI-2), an elevated score on the F (Infrequency) Validity Scale alongside elevated symptom clinical scales most likely suggests:', 
 'Defensive responding with intentional denial of psychological problems', 
 'Exaggeration of symptoms ("faking bad"), severe distress, or random responding', 
 'Careful, consistent acquiescent response style', 
 'High motivation for employment selection and impression management', 
 'b', 'advanced', 16, 16, 
 'Elevated F Scale scores indicate atypical responding, malingering/faking bad, profound psychopathology, or failure to comprehend test items.', 'Graham, MMPI-2: Assessing Personality and Psychopathology', 'approved'),

(12, 'A developmental psychologist conducts a study assessing the impact of social media on adolescents across 10 years, following the same 500 participants from age 12 to 22. What research design was implemented?', 
 'Cross-sectional design', 
 'Longitudinal design', 
 'Sequential cross-lagged design', 
 'Meta-analytic retrospective design', 
 'b', 'advanced', 16, 25, 
 'A longitudinal design tracks and repeatedly measures the exact same cohort of participants over an extended timeframe.', 'Shaughnessy, Zechmeister, & Zechmeister, Research Methods in Psychology', 'approved'),

(13, 'A clinical researcher conducts a two-way ANOVA evaluating therapy type (CBT vs. Psychodynamic) and session frequency (1x vs. 3x weekly). The analysis reveals a significant interaction effect. This indicates that:', 
 'Both main effects must automatically be statistically significant', 
 'The efficacy of the therapy type depends upon the frequency of the sessions', 
 'Neither therapy produces measurable symptom reduction compared to placebo', 
 'A Type I error was definitively committed due to violations of sphericity', 
 'b', 'advanced', 16, 25, 
 'A statistical interaction means the effect of one independent variable on the dependent variable differs as a function of the level of the second independent variable.', 'Field, Discovering Statistics Using IBM SPSS Statistics', 'approved'),

(14, 'Which psychometric index represents the standard deviation of error in an individual observed test score, allowing the calculation of confidence intervals around true scores?', 
 'Standard Error of Measurement (SEM)', 
 'Standard Error of the Mean (SE_x)', 
 'Coefficient of determination (r²)', 
 'Cronbach alpha (α)', 
 'a', 'advanced', 16, 25, 
 'The Standard Error of Measurement (SEM = SD * sqrt(1 - r_xx)) quantifies measurement error and is used to construct confidence intervals around test scores.', 'Crocker & Algina, Introduction to Classical and Modern Test Theory', 'approved'),

(15, 'A licensed psychologist in private practice is invited to enter into a commercial real estate business partnership with an active individual psychotherapy client. According to the APA Ethics Code, entering this arrangement constitutes:', 
 'An acceptable dual relationship provided fees are shared equally', 
 'An unethical multiple relationship that risks impairing objectivity and exploiting the client', 
 'A mandatory referral to peer consultation with no ethical prohibition', 
 'An ethical business venture if the client signs an arbitration waiver', 
 'b', 'advanced', 16, 25, 
 'APA Ethics Code Standard 3.05 strictly prohibits entering multiple relationships if it risks impairing professional competence, objectivity, or causes exploitation.', 'APA Ethics Code Standard 3.05 (Multiple Relationships)', 'approved'),

(10, 'In Industrial/Organizational Psychology, which evaluation methodology systematically identifies the Knowledge, Skills, Abilities, and Other characteristics (KSAOs) necessary to execute a job?', 
 'Job evaluation', 'Job analysis', 'Realistic job preview', 'Assessment center simulation', 
 'b', 'advanced', 16, 25, 
 'Job analysis is the foundational process of determining the duties, requirements, and KSAOs for specific positions.', 'Cascio & Aguinis, Applied Psychology in Human Resource Management', 'approved'),

(16, 'In Carl Jung analytical psychology, universally inherited, archaic mental representations and memory traces residing in the collective unconscious are termed:', 
 'Ego defense mechanisms', 'Archetypes', 'Organismic complexes', 'Operant schemas', 
 'b', 'advanced', 16, 25, 
 'Jung posited that archetypes (e.g., the Shadow, Anima/Animus, Self) are universal, primordial symbols and patterns in the collective unconscious.', 'Jung, The Archetypes and the Collective Unconscious', 'approved'),

(2, 'The long-term potentiation (LTP) mechanism, considered the primary cellular foundation of synaptic plasticity and memory formation, relies heavily on which neurotransmitter receptor system?', 
 'GABA_A receptors', 'NMDA and AMPA glutamate receptors', 'Muscarinic acetylcholine receptors', 'D2 dopamine receptors', 
 'b', 'advanced', 16, 25, 
 'LTP in the hippocampus depends on glutamate activation of AMPA receptors followed by depolarization-driven unblocking of NMDA receptors to permit calcium influx.', 'Bliss & Collingridge (1993); Kandel, Principles of Neural Science', 'approved'),

-- =================== LEVEL 26+: BOARD-EXAM LEVEL (Synthesize / Evaluate) ===================
(7, 'A 44-year-old corporate executive is referred for assessment following severe relationship dysfunction and vocational burnout. Testing reveals a chronic pattern of grandiosity, an insatiable need for admiration, profound lack of empathy, hypersensitivity to perceived criticism, and recurrent exploitation of subordinates, persisting unchanged since young adulthood across diverse personal and professional settings. Medical etiologies and substance use have been ruled out. What is the definitive differential diagnosis?', 
 'Bipolar I Disorder, manic episode with psychotic features', 
 'Narcissistic Personality Disorder', 
 'Borderline Personality Disorder with antisocial traits', 
 'Delusional Disorder, grandiose type', 
 'b', 'board_exam', 26, 99, 
 'The enduring, pervasive pattern of grandiosity, need for admiration, and lack of empathy originating in early adulthood meets full diagnostic criteria for Narcissistic Personality Disorder.', 'DSM-5-TR, Cluster B Personality Disorders', 'approved'),

(8, 'During a psychotherapeutic intake, a client suffering from severe Panic Disorder with Agoraphobia expresses dread of somatic sensations such as tachycardia and lightheadedness, catastrophizing them as impending cardiac arrest. Which evidence-based cognitive-behavioral intervention is most specifically indicated to address this interoceptive conditioning?', 
 'Free association and dream interpretation', 
 'Systematic interoceptive exposure exercises', 
 'Thought suppression and distraction techniques', 
 'Unconditional client self-affirmation', 
 'b', 'board_exam', 26, 99, 
 'Interoceptive exposure systematically induces feared bodily sensations (e.g., hyperventilation, spinning) to extinguish conditioned autonomic catastrophic fear.', 'Barlow, Anxiety and Its Disorders: Nature and Treatment of Anxiety and Panic', 'approved'),

(11, 'A clinical neuropsychologist evaluates a 68-year-old retired architect presenting with progressive memory decline. On the WAIS-IV, the patient demonstrates an intact Verbal Comprehension Index (VCI = 114), but severely degraded Processing Speed (PSI = 72) and Perceptual Reasoning (PRI = 78). On the Rey-Osterrieth Complex Figure Test, the patient exhibits severe constructional apraxia and rapid visual recall decay. These psychometric findings are most characteristically indicative of:', 
 'Frontotemporal lobar degeneration with behavioral variant', 
 'Cortical Alzheimer-type dementia progression with visuospatial/executive disruption', 
 'Normal age-related cognitive slowdown without pathological impairment', 
 'Primary progressive aphasia, non-fluent agrammatic variant', 
 'b', 'board_exam', 26, 99, 
 'Marked discrepancies between preserved crystallized verbal comprehension and severely impaired perceptual reasoning/constructional recall indicate neurodegenerative Alzheimer pathology.', 'Lezak et al., Neuropsychological Assessment (5th ed.)', 'approved'),

(15, 'A board-certified clinical psychologist receives a formal court subpoena issued by an opposing attorney requesting all confidential psychiatric therapy records and raw psychological test data of a patient involved in a child custody dispute. The patient explicitly refuses to waive confidentiality. According to APA Ethics Code Standard 9.04 and 1.02, what is the psychologist primary ethical obligation?', 
 'Immediately disclose all psychotherapy notes and raw test protocols to comply with the attorney subpoena', 
 'Assert psychotherapist-patient privilege, withhold the confidential records, and seek a protective order or judicial ruling from the judge', 
 'Destroy all raw psychological test protocols immediately to prevent misuse', 
 'Release only the MMPI-2 answer keys while concealing clinical interview notes', 
 'b', 'board_exam', 26, 99, 
 'A subpoena issued by an attorney does not override privilege; psychologists must protect client confidentiality and raw test data until a valid judge order or client release is issued.', 'APA Ethics Code Standard 9.04; Committee on Legal Issues (COLI)', 'approved'),

(14, 'An industrial test developer creates a 100-item pre-employment aptitude battery. The overall test has a reliability coefficient of r_xx = .75. Using the Spearman-Brown prophecy formula, if the test is lengthened to 200 items of parallel quality, what is the estimated new reliability coefficient?', 
 'r_xx = .80', 'r_xx = .86', 'r_xx = .92', 'r_xx = .98', 
 'b', 'board_exam', 26, 99, 
 'By Spearman-Brown formula: r_new = (k * r) / (1 + (k - 1) * r). For k = 2: (2 * .75) / (1 + 1 * .75) = 1.50 / 1.75 = .857 (~.86).', 'Nunnally & Bernstein, Psychometric Theory', 'approved'),

(13, 'In structural equation modeling (SEM) and factor analysis, what is the key distinction between Exploratory Factor Analysis (EFA) and Confirmatory Factor Analysis (CFA)?', 
 'EFA requires pre-specified theoretical hypotheses regarding factor loadings; CFA does not', 
 'EFA identifies underlying latent dimensions without a priori constraints; CFA tests whether empirical data fit an explicitly hypothesized measurement model', 
 'EFA uses exclusively continuous variables, whereas CFA is restricted to binary categorical data', 
 'EFA computes statistical power; CFA computes effect size only', 
 'b', 'board_exam', 26, 99, 
 'EFA is a data-driven reduction technique to discover latent factors; CFA is theory-driven and tests whether empirical data conform to a pre-specified model.', 'Kline, Principles and Practice of Structural Equation Modeling', 'approved'),

(12, 'In epidemiological and clinical trials, if an investigator assesses the efficacy of an antidepressant but experiences non-random participant attrition (dropouts primarily in the placebo control group due to lack of symptom improvement), what primary threat to validity is introduced?', 
 'Testing threat', 'Attrition / Differential mortality threat to internal validity', 'Instrumentation threat', 'History threat', 
 'b', 'board_exam', 26, 99, 
 'Differential attrition (non-random loss of participants across study arms) compromises internal validity by systematically biasing group equivalency.', 'Campbell & Stanley, Experimental and Quasi-Experimental Designs for Research', 'approved'),

(16, 'A patient entering psychoanalytic treatment frequently reacts with intense anger and resentment toward the therapist whenever the therapist sets neutral session boundaries, mirroring the patient unresolved conflicts with an authoritarian father. In psychoanalytic terminology, this unconscious phenomenon is known as:', 
 'Countertransference', 'Transference', 'Projective identification', 'Reaction formation', 
 'b', 'board_exam', 26, 99, 
 'Transference is the unconscious redirection of feelings, expectations, and attitudes forged in childhood relationships toward the therapist.', 'Freud, The Dynamics of Transference; Mitchell & Black, Freud and Beyond', 'approved');

-- 6. Transform Bosses into Psychology Guardians
UPDATE bosses SET 
    name = 'The Amnesia Golem',
    description = 'Guardian of the Cognitive Realm. Tests your mastery of memory models, cognitive stages, and perceptual mechanisms.',
    icon = '🧠',
    subject = 'Cognitive Psychology',
    max_hp = 100,
    xp_reward = 300,
    point_reward = 150
WHERE id = 1;

UPDATE bosses SET 
    name = 'The Schema Sphinx',
    description = 'Ancient keeper of developmental milestones and personality architectures. Riddle solver of Erikson and Piaget.',
    icon = '🦁',
    subject = 'Developmental & Personality Psychology',
    max_hp = 120,
    xp_reward = 400,
    point_reward = 200
WHERE id = 2;

UPDATE bosses SET 
    name = 'The Neuro Titan',
    description = 'Behemoth of the biological foundations. Commands neural networks, brain lobes, and neurochemical pathways.',
    icon = '🧬',
    subject = 'Biological Psychology',
    max_hp = 120,
    xp_reward = 400,
    point_reward = 200
WHERE id = 3;

UPDATE bosses SET 
    name = 'The Diagnostic Chimera',
    description = 'A fierce entity embodying clinical psychopathology, DSM-5 diagnostic criteria, and psychiatric differentials.',
    icon = '👾',
    subject = 'Abnormal & Clinical Psychology',
    max_hp = 150,
    xp_reward = 500,
    point_reward = 250
WHERE id = 4;

UPDATE bosses SET 
    name = 'The Psychometrics Phantom',
    description = 'Master of statistics, reliability indices, factor matrices, and psychological test standardization.',
    icon = '📊',
    subject = 'Psychological Assessment & Statistics',
    max_hp = 150,
    xp_reward = 500,
    point_reward = 250
WHERE id = 5;

UPDATE bosses SET 
    name = 'The Archon of Ethics',
    description = 'Supreme judge of professional conduct, licensing boards, informed consent, and ethical dilemmas.',
    icon = '⚖️',
    subject = 'Ethics & Professional Practice',
    max_hp = 200,
    xp_reward = 700,
    point_reward = 350
WHERE id = 6;

-- 7. Replace Boss Questions with Psychology Boss Questions
DELETE FROM boss_questions;

-- Boss 1 (The Amnesia Golem - Cognitive Psychology)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(1, 'Which memory system holds visual sensory impressions for only a few hundred milliseconds before decaying?', 'Echoic memory', 'Iconic memory', 'Semantic memory', 'Implicit memory', 'b', 'easy'),
(1, 'The classic study by George Miller (1956) established that the capacity of short-term memory is approximately:', '3 plus or minus 1 chunks', '7 plus or minus 2 chunks', '12 plus or minus 3 chunks', 'Unlimited with elaborative rehearsal', 'b', 'easy'),
(1, 'In Baddeley model of working memory, which component oversees attention allocation and strategy switching?', 'Phonological loop', 'Visuospatial sketchpad', 'Central executive', 'Episodic buffer', 'c', 'medium'),
(1, 'Patient H.M. suffered dense anterograde amnesia after bilateral medial temporal lobectomy, yet could learn motor mirror tracing. This showed preservation of:', 'Episodic memory', 'Semantic memory', 'Procedural / Implicit memory', 'Autobiographical memory', 'c', 'medium'),
(1, 'The serial position effect shows superior recall for items at the beginning and end of a list, representing which two effects?', 'Priming and habituation', 'Primacy and recency effects', 'Proactive and retroactive interference', 'Encoding and storage effects', 'b', 'medium');

-- Boss 2 (The Schema Sphinx - Developmental & Personality)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(2, 'According to Piaget, modifying an existing cognitive schema to incorporate new contradictory information is termed:', 'Assimilation', 'Accommodation', 'Equilibration', 'Centration', 'b', 'medium'),
(2, 'In Erikson psychosocial theory, a child between ages 6 and 12 who takes pride in school accomplishments is resolving:', 'Trust vs. Mistrust', 'Autonomy vs. Shame', 'Industry vs. Inferiority', 'Initiative vs. Guilt', 'c', 'medium'),
(2, 'Which defense mechanism involves channeling unacceptable aggressive or sexual impulses into socially constructive activities?', 'Sublimation', 'Reaction formation', 'Projection', 'Displacement', 'a', 'medium'),
(2, 'In Mary Ainsworth Strange Situation, an infant who shows distress upon maternal departure and seeks comfort warmly upon reunion exhibits:', 'Insecure-avoidant attachment', 'Secure attachment', 'Insecure-resistant attachment', 'Disorganized attachment', 'b', 'medium'),
(2, 'According to Carl Rogers, when parents accept and cherish a child only when the child meets parental expectations, they impose:', 'Unconditional positive regard', 'Conditions of worth', 'Organismic valuing', 'Incongruent congruence', 'b', 'medium');

-- Boss 3 (The Neuro Titan - Biological Psychology)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(3, 'Which neurotransmitter is primarily implicated in voluntary motor control and the mesolimbic reward system?', 'Acetylcholine', 'Dopamine', 'Serotonin', 'GABA', 'b', 'medium'),
(3, 'The principal inhibitory neurotransmitter in the mammalian central nervous system is:', 'Glutamate', 'Norepinephrine', 'GABA', 'Substance P', 'c', 'medium'),
(3, 'Which subcortical limbic structure plays a pivotal role in processing fear, threat appraisal, and emotional memories?', 'Hippocampus', 'Amygdala', 'Basal ganglia', 'Thalamus', 'b', 'medium'),
(3, 'During the resting membrane potential of a typical neuron, the intracellular fluid is negatively charged relative to the outside, measured at approximately:', '-70 mV', '+40 mV', '0 mV', '-120 mV', 'a', 'hard'),
(3, 'Saltatory conduction, which accelerates the propagation of action potentials along an axon, occurs due to:', 'Nodes of Ranvier along myelinated sheaths', 'Continuous opening of unmyelinated sodium channels', 'Direct electrical transmission across gap junctions', 'Sustained calcium influx at the dendrites', 'a', 'hard');

-- Boss 4 (The Diagnostic Chimera - Abnormal & Clinical Psychology)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(4, 'To meet DSM-5 diagnostic criteria for Bipolar I Disorder, an individual must have experienced at least one lifetime:', 'Hypomanic episode', 'Full manic episode', 'Major depressive episode', 'Cyclothymic mood shift', 'b', 'medium'),
(4, 'Persistent, intrusive, and distressing thoughts (e.g., contamination fears) paired with repetitive neutralizing rituals define:', 'Obsessive-Compulsive Disorder (OCD)', 'Generalized Anxiety Disorder (GAD)', 'Illness Anxiety Disorder', 'Somatic Symptom Disorder', 'a', 'medium'),
(4, 'In schizophrenia, which of the following is categorized as a "negative symptom"?', 'Auditory hallucinations', 'Delusions of persecution', 'Avolition and affective flattening', 'Disorganized tangential speech', 'c', 'hard'),
(4, 'A major differential distinguishing Schizoaffective Disorder from Major Depressive Disorder with Psychotic Features is:', 'Presence of delusions/hallucinations for >= 2 weeks in the absence of prominent mood episodes', 'Psychotic symptoms occur strictly during severe mood episodes', 'Schizoaffective disorder does not have mood symptoms', 'Psychotic features in schizoaffective disorder are exclusively grandiose', 'a', 'hard'),
(4, 'A cluster of symptoms including flashbacks, hypervigilance, and avoidance following traumatic exposure lasting between 3 days and 1 month is termed:', 'Post-Traumatic Stress Disorder', 'Acute Stress Disorder', 'Adjustment Disorder with anxiety', 'Dissociative Fugue', 'b', 'hard');

-- Boss 5 (The Psychometrics Phantom - Assessment & Statistics)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(5, 'Which form of validity evaluates how well an assessment predicts performance on a future benchmark criterion (e.g., college GPA)?', 'Content validity', 'Predictive criterion-related validity', 'Face validity', 'Construct convergent validity', 'b', 'medium'),
(5, 'In classical test theory, an observed score (X) is mathematically conceived as the sum of which two components?', 'Mean score and variance', 'True score (T) and error (E)', 'Reliability coefficient and validity ratio', 'Z-score and standard error', 'b', 'medium'),
(5, 'Rejecting a null hypothesis that is in reality true in the population constitutes a:', 'Type I error (alpha)', 'Type II error (beta)', 'Standard error of estimate', 'Power deficiency', 'a', 'hard'),
(5, 'A clinical assessment tool with an internal consistency reliability coefficient (Cronbach alpha) of .92 is considered:', 'Unacceptably unreliable', 'Adequately reliable for experimental research only', 'Highly reliable for clinical decision-making', 'Biased by negative item covariance', 'c', 'medium'),
(5, 'On a standardized intelligence test with mean = 100 and SD = 15, an examinee obtaining a score of 130 sits at approximately which percentile rank?', '84th percentile', '90th percentile', '98th percentile', '99.9th percentile', 'c', 'hard');

-- Boss 6 (The Archon of Ethics - Ethics & Professional Practice)
INSERT INTO boss_questions (boss_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty) VALUES
(6, 'Under the landmark Tarasoff ruling, the psychotherapist legal and ethical duty to protect third parties is triggered when:', 'A client reveals past criminal misdemeanors', 'A client presents a serious, foreseeable, and imminent threat of violence to an identifiable victim', 'A client engages in recreational substance use', 'A client expresses vague, non-specific general frustration with coworkers', 'b', 'medium'),
(6, 'According to the APA Code of Ethics, when a psychologist is providing services to multiple persons who have a relationship (e.g., family counseling), the psychologist must:', 'Designate the individual paying the fee as the sole client', 'Clarify at the outset who the clients are and the limits of confidentiality', 'Conduct separate private assessments with no shared boundaries', 'Waive all informed consent requirements', 'b', 'medium'),
(6, 'A psychologist discovers that a close personal friend has applied for psychotherapy at the psychologist private clinic. The most ethically sound response is to:', 'Accept the friend into therapy with a discounted rate', 'Refer the friend to an independent qualified practitioner to prevent an unethical multiple relationship', 'Have the friend sign a waiver consenting to dual roles', 'Conduct therapy only through remote video sessions', 'b', 'hard'),
(6, 'When a psychologist administers standardized psychological tests, raw test protocols and test security must be safeguarded to:', 'Prevent public exposure of proprietary items and preserve test validity', 'Ensure that only attorneys can inspect test manuals', 'Eliminate the need for examiner training', 'Comply with tax filing mandates', 'a', 'hard'),
(6, 'The ethical principle of Beneficence and Nonmaleficence mandates that psychologists:', 'Guarantee client recovery within a specified timeline', 'Strive to benefit those with whom they work and take care to do no harm', 'Offer pro bono services to 100% of their caseload', 'Never terminate therapy even when no longer beneficial', 'b', 'medium');

SET FOREIGN_KEY_CHECKS = 1;

