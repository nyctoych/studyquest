-- ============================================================
-- StudyQuest Psychology Questions Seed Data
-- Run AFTER multiplayer_migration.sql
-- ============================================================

USE studyquest;

-- ── QUESTION CATEGORIES ───────────────────────────────────────
INSERT IGNORE INTO question_categories (name, slug, description, icon, color) VALUES
('General Psychology', 'general-psychology', 'Core psychological concepts and history', '🧠', '#6366f1'),
('Developmental Psychology', 'developmental-psychology', 'Human growth and development across the lifespan', '🌱', '#10b981'),
('Abnormal Psychology', 'abnormal-psychology', 'Psychological disorders and their characteristics', '🔬', '#f59e0b'),
('Clinical Psychology', 'clinical-psychology', 'Assessment, diagnosis, and treatment', '🏥', '#ef4444'),
('Social Psychology', 'social-psychology', 'Social influences on behavior and cognition', '👥', '#8b5cf6'),
('Cognitive Psychology', 'cognitive-psychology', 'Mental processes: memory, thinking, language', '💡', '#06b6d4'),
('Biological Psychology', 'biological-psychology', 'Neural and biological basis of behavior', '🧬', '#84cc16'),
('Personality Psychology', 'personality-psychology', 'Personality theories and assessment', '🎭', '#f97316'),
('Psychological Assessment', 'psychological-assessment', 'Tests, measurements, and psychometrics', '📊', '#ec4899'),
('Industrial/Organizational Psychology', 'io-psychology', 'Psychology in the workplace', '🏢', '#14b8a6'),
('Research Methods', 'research-methods', 'Scientific methods in psychology research', '🔍', '#a855f7'),
('Statistics', 'statistics', 'Statistical analysis in psychological research', '📈', '#3b82f6'),
('Ethics', 'ethics', 'Ethical principles and standards in psychology', '⚖️', '#78716c');

-- ── GENERAL PSYCHOLOGY QUESTIONS ──────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(1, 'Who is considered the "father of psychology" for establishing the first experimental psychology laboratory in 1879?', 
 'Sigmund Freud', 'William James', 'Wilhelm Wundt', 'John Watson', 'c', 'beginner',
 'Wilhelm Wundt established the first psychology laboratory in Leipzig, Germany in 1879, marking the formal beginning of psychology as a science.'),

(1, 'The biopsychosocial model suggests that psychological disorders are caused by:',
 'Purely biological factors', 'Purely psychological factors', 'Interaction of biological, psychological, and social factors', 'Social learning alone', 'c', 'intermediate',
 'The biopsychosocial model integrates biological, psychological, and social factors in understanding health and illness.'),

(1, 'Which perspective in psychology focuses on unconscious motivations and childhood experiences?',
 'Behavioral', 'Cognitive', 'Humanistic', 'Psychodynamic', 'd', 'beginner',
 'The psychodynamic perspective, originating with Freud, emphasizes the role of unconscious processes and early experiences.'),

(1, 'The term "nature vs. nurture" refers to the debate about:',
 'Evolution vs. creation', 'Genetics vs. environment', 'Conscious vs. unconscious mind', 'Free will vs. determinism', 'b', 'beginner',
 'Nature vs. nurture refers to the relative contributions of genetic/biological factors versus environmental/experiential factors.'),

(1, 'Which school of psychology was founded by John B. Watson and focused on observable behavior?',
 'Structuralism', 'Functionalism', 'Behaviorism', 'Gestalt Psychology', 'c', 'beginner',
 'Behaviorism, founded by Watson, focused exclusively on observable behavior and rejected the study of mental processes.'),

(1, 'Abraham Maslow is best known for his:',
 'Theory of cognitive development', 'Hierarchy of needs', 'Classical conditioning experiments', 'Operant conditioning principles', 'b', 'beginner',
 'Maslow\'s hierarchy of needs is a motivational theory comprising five levels: physiological, safety, love/belonging, esteem, and self-actualization.'),

(1, 'The "door-in-the-face" technique is a compliance strategy where:',
 'You start with a small request then make a larger one', 'You start with a large request then follow with a smaller one', 'You make requests repeatedly until compliance', 'You use fear to gain compliance', 'b', 'intermediate',
 'The door-in-the-face technique starts with an unreasonably large request (which gets rejected), then follows with a more reasonable smaller request.'),

(1, 'Which of the following best describes "intrinsic motivation"?',
 'Motivation driven by external rewards', 'Motivation driven by internal satisfaction', 'Motivation driven by social approval', 'Motivation driven by avoiding punishment', 'b', 'intermediate',
 'Intrinsic motivation refers to engaging in behavior for its own sake, driven by internal satisfaction rather than external rewards.');

-- ── DEVELOPMENTAL PSYCHOLOGY QUESTIONS ────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(2, 'According to Jean Piaget, children in the "preoperational stage" (ages 2–7) typically exhibit:',
 'Abstract reasoning', 'Conservation skills', 'Egocentrism', 'Formal logic', 'c', 'intermediate',
 'Preoperational children exhibit egocentrism — difficulty seeing things from others\' perspectives — along with magical thinking.'),

(2, 'Erik Erikson\'s psychosocial stage for young adulthood (ages 18–40) is:',
 'Identity vs. Role Confusion', 'Intimacy vs. Isolation', 'Generativity vs. Stagnation', 'Integrity vs. Despair', 'b', 'intermediate',
 'The intimacy vs. isolation stage focuses on forming intimate relationships. Failure leads to isolation and loneliness.'),

(2, 'Object permanence — knowing that objects continue to exist when not visible — develops during Piaget\'s:',
 'Preoperational stage', 'Concrete operational stage', 'Sensorimotor stage', 'Formal operational stage', 'c', 'beginner',
 'Object permanence develops during the sensorimotor stage (birth to 2 years), typically around 8-12 months.'),

(2, 'Lev Vygotsky\'s "Zone of Proximal Development" (ZPD) refers to:',
 'What a child can do independently', 'The gap between actual and potential development with guidance', 'The child\'s biological maturation level', 'Memory capacity at various ages', 'b', 'intermediate',
 'ZPD is the difference between what a learner can do without help and what they can do with guidance from a more skilled person.'),

(2, 'Which attachment style is characterized by children who show distress when the caregiver leaves but resist comfort upon return?',
 'Secure attachment', 'Anxious-ambivalent attachment', 'Avoidant attachment', 'Disorganized attachment', 'b', 'advanced',
 'Anxious-ambivalent (resistant) attachment is characterized by extreme distress at separation and difficulty being soothed upon reunion.'),

(2, 'Kohlberg\'s highest level of moral development is called:',
 'Preconventional morality', 'Conventional morality', 'Postconventional morality', 'Transcendental morality', 'c', 'intermediate',
 'Postconventional morality (Level 3) involves abstract reasoning about justice, human rights, and universal ethical principles.'),

(2, 'According to Ainsworth\'s Strange Situation, what behavior best characterizes a securely attached infant?',
 'Shows no distress when caregiver leaves', 'Uses caregiver as safe base, distressed on separation but comforted on return', 'Ignores caregiver completely', 'Shows intense anxiety and cannot be comforted', 'b', 'advanced',
 'Securely attached infants explore freely, are distressed when the caregiver leaves, and are easily comforted upon return.');

-- ── ABNORMAL PSYCHOLOGY QUESTIONS ────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(3, 'Which DSM-5 criterion is NOT typically required for a psychological disorder diagnosis?',
 'Clinically significant distress', 'Functional impairment', 'Low intelligence quotient', 'Behavioral/psychological syndrome', 'c', 'intermediate',
 'A psychological disorder requires distress or impairment and must reflect a dysfunction — intelligence level alone is not a criterion.'),

(3, 'Major Depressive Disorder requires symptoms lasting at least:',
 '1 week', '2 weeks', '1 month', '6 months', 'b', 'beginner',
 'DSM-5 requires at least 5 depressive symptoms nearly every day for at least 2 weeks, with at least one being depressed mood or anhedonia.'),

(3, 'Which anxiety disorder involves persistent, excessive worry about multiple life domains?',
 'Panic Disorder', 'Social Anxiety Disorder', 'Generalized Anxiety Disorder', 'Specific Phobia', 'c', 'beginner',
 'Generalized Anxiety Disorder (GAD) is characterized by persistent, uncontrollable worry about multiple areas lasting at least 6 months.'),

(3, 'In Bipolar I Disorder, a manic episode must last at least:',
 '2 days', '4 days', '7 days (or any duration if hospitalized)', '14 days', 'c', 'advanced',
 'A manic episode in Bipolar I must last at least 7 days (or less if hospitalization is required) with markedly elevated or irritable mood.'),

(3, 'Which disorder is characterized by positive symptoms such as hallucinations and delusions?',
 'Major Depressive Disorder', 'Bipolar II Disorder', 'Schizophrenia', 'Borderline Personality Disorder', 'c', 'beginner',
 'Schizophrenia is characterized by positive symptoms (hallucinations, delusions, disorganized speech) and negative symptoms (flat affect, avolition).'),

(3, 'Post-Traumatic Stress Disorder (PTSD) requires exposure to a traumatic event and symptoms lasting more than:',
 '2 weeks', '1 month', '3 months', '6 months', 'b', 'intermediate',
 'PTSD requires symptoms (intrusion, avoidance, negative cognitions/mood, hyperarousal) lasting more than 1 month following a traumatic event.'),

(3, 'Obsessive-Compulsive Disorder (OCD) is classified in the DSM-5 under:',
 'Anxiety Disorders', 'Obsessive-Compulsive and Related Disorders', 'Trauma- and Stressor-Related Disorders', 'Somatic Symptom Disorders', 'b', 'advanced',
 'In DSM-5, OCD was moved to its own category: Obsessive-Compulsive and Related Disorders, separate from Anxiety Disorders.');

-- ── CLINICAL PSYCHOLOGY QUESTIONS ────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(4, 'Cognitive-Behavioral Therapy (CBT) is based on the premise that:',
 'Unconscious conflicts drive behavior', 'Thoughts, feelings, and behaviors are interconnected', 'Environment alone determines behavior', 'Biological factors are the primary cause of disorders', 'b', 'beginner',
 'CBT is based on the cognitive model: thoughts (cognitions) influence emotions and behaviors. Changing maladaptive thoughts leads to behavioral change.'),

(4, 'The therapeutic technique of "systematic desensitization" is most associated with treating:',
 'Depression', 'Schizophrenia', 'Phobias and anxiety', 'Personality disorders', 'c', 'intermediate',
 'Systematic desensitization, developed by Joseph Wolpe, pairs progressive relaxation with gradual exposure to feared stimuli to treat phobias.'),

(4, 'Which type of validity refers to whether a test measures what it is intended to measure?',
 'Reliability', 'Construct validity', 'Criterion validity', 'Ecological validity', 'b', 'intermediate',
 'Construct validity refers to whether a test actually measures the theoretical construct it claims to measure (e.g., does an IQ test measure intelligence?).'),

(4, 'Person-centered therapy was developed by:',
 'Aaron Beck', 'Albert Ellis', 'Carl Rogers', 'Fritz Perls', 'c', 'beginner',
 'Carl Rogers developed Person-Centered Therapy, emphasizing unconditional positive regard, empathy, and congruence as therapeutic conditions.'),

(4, 'EMDR (Eye Movement Desensitization and Reprocessing) is primarily used to treat:',
 'Schizophrenia', 'PTSD and trauma', 'Bipolar disorder', 'ADHD', 'b', 'advanced',
 'EMDR uses bilateral stimulation (eye movements) while the patient recalls traumatic memories to process and reduce their emotional impact.'),

(4, 'In clinical assessment, a "structured interview" differs from an "unstructured interview" in that it:',
 'Is conducted by a psychiatrist only', 'Follows a predetermined set of questions', 'Focuses on projective tests', 'Takes longer to administer', 'b', 'intermediate',
 'Structured interviews follow a specific protocol with standardized questions, increasing reliability and reducing interviewer bias.');

-- ── SOCIAL PSYCHOLOGY QUESTIONS ───────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(5, 'Stanley Milgram\'s obedience experiments demonstrated that:',
 'People rarely follow authority under extreme conditions', 'People are resistant to peer pressure', 'A majority of participants obeyed authority to harmful levels', 'Social influence only works in small groups', 'c', 'intermediate',
 'Milgram found that ~65% of participants obeyed the experimenter to deliver what they believed were maximum (450V) electric shocks to another person.'),

(5, 'The "bystander effect" describes the phenomenon where:',
 'Observers always help in emergencies', 'Likelihood of helping decreases as the number of bystanders increases', 'People help more when alone with the victim', 'Groups are more likely to help than individuals', 'b', 'beginner',
 'The bystander effect shows that individuals are less likely to offer help in emergencies when other people are present (diffusion of responsibility).'),

(5, 'Which concept refers to the tendency to attribute others\' behaviors to internal (dispositional) factors while ignoring situational factors?',
 'Self-serving bias', 'Confirmation bias', 'Fundamental attribution error', 'Actor-observer bias', 'c', 'intermediate',
 'The Fundamental Attribution Error (also called correspondence bias) is the tendency to overestimate dispositional causes and underestimate situational causes of others\' behavior.'),

(5, 'Solomon Asch\'s conformity experiments showed that participants conformed to incorrect group answers about:',
 'Political opinions', 'Line length judgments', 'Mathematical problems', 'Color identification', 'b', 'intermediate',
 'Asch showed that ~75% of participants conformed at least once to the group\'s obviously wrong answers about which lines were equal in length.'),

(5, 'Cognitive dissonance theory, proposed by Leon Festinger, states that:',
 'People seek consistency between cognitions and reduce discomfort when inconsistencies exist', 'People prefer complexity in their beliefs', 'Attitudes always predict behavior', 'Social roles determine cognition', 'a', 'intermediate',
 'Cognitive dissonance occurs when two cognitions conflict, creating psychological discomfort that motivates attitude or behavior change to restore consistency.');

-- ── COGNITIVE PSYCHOLOGY QUESTIONS ────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(6, 'George Miller\'s "magical number 7 ± 2" refers to the limited capacity of:',
 'Long-term memory', 'Short-term (working) memory', 'Sensory memory', 'Implicit memory', 'b', 'intermediate',
 'Miller (1956) found working memory holds approximately 7 (±2) chunks of information simultaneously.'),

(6, 'Which memory system stores general knowledge and facts (e.g., the capital of France)?',
 'Episodic memory', 'Procedural memory', 'Semantic memory', 'Working memory', 'c', 'beginner',
 'Semantic memory is part of long-term declarative memory that stores general world knowledge, facts, and concepts.'),

(6, 'The "availability heuristic" causes people to:',
 'Judge probability based on how easily examples come to mind', 'Always overestimate rare events', 'Always underestimate common events', 'Rely on logical probability calculations', 'a', 'intermediate',
 'The availability heuristic leads people to assess probability based on how readily examples can be recalled, often leading to systematic biases.'),

(6, 'Encoding specificity principle suggests that memory retrieval is best when:',
 'Retrieval cues match encoding context', 'Information is rehearsed many times', 'Material is emotionally neutral', 'Learning occurs in formal settings', 'a', 'advanced',
 'Memory retrieval is enhanced when the conditions at retrieval match those present during encoding (context, mood, environment).'),

(6, 'Which type of long-term memory involves skills and habits (e.g., riding a bicycle)?',
 'Episodic memory', 'Semantic memory', 'Procedural memory', 'Prospective memory', 'c', 'beginner',
 'Procedural memory is implicit memory for skills, habits, and conditioned responses — often difficult to verbalize.');

-- ── BIOLOGICAL PSYCHOLOGY QUESTIONS ───────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(7, 'Which neurotransmitter is primarily associated with reward, motivation, and movement?',
 'Serotonin', 'Norepinephrine', 'Dopamine', 'GABA', 'c', 'beginner',
 'Dopamine plays a key role in reward pathways, motivation, motor control, and is implicated in disorders like schizophrenia and Parkinson\'s disease.'),

(7, 'The amygdala is most closely associated with:',
 'Language processing', 'Motor coordination', 'Fear and emotional processing', 'Visual perception', 'c', 'beginner',
 'The amygdala (part of the limbic system) processes emotions, especially fear, and plays a role in memory consolidation of emotionally significant events.'),

(7, 'Broca\'s area, located in the left frontal lobe, is responsible for:',
 'Speech comprehension', 'Speech production', 'Visual processing', 'Auditory processing', 'b', 'intermediate',
 'Broca\'s area controls the production of speech. Damage causes Broca\'s aphasia — telegraphic speech with intact comprehension.'),

(7, 'GABA (gamma-aminobutyric acid) is the brain\'s primary:',
 'Excitatory neurotransmitter', 'Inhibitory neurotransmitter', 'Neuromodulator', 'Endocannabinoid', 'b', 'intermediate',
 'GABA is the main inhibitory neurotransmitter in the CNS, reducing neural excitability. Benzodiazepines enhance GABA activity.'),

(7, 'The hypothalamus regulates all of the following EXCEPT:',
 'Hunger and thirst', 'Body temperature', 'Circadian rhythms', 'Complex reasoning', 'd', 'intermediate',
 'The hypothalamus regulates homeostatic functions (hunger, thirst, temperature, sleep) but complex reasoning is a frontal lobe function.');

-- ── PERSONALITY PSYCHOLOGY QUESTIONS ─────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(8, 'The "Big Five" personality traits (OCEAN) include all EXCEPT:',
 'Openness', 'Conscientiousness', 'Authoritarianism', 'Neuroticism', 'c', 'beginner',
 'The Big Five (OCEAN) are: Openness, Conscientiousness, Extraversion, Agreeableness, and Neuroticism. Authoritarianism is not one of them.'),

(8, 'According to Carl Jung, the "collective unconscious" contains:',
 'Personal repressed memories', 'Universal archetypes shared by all humans', 'Ego defense mechanisms', 'Conditioned behavioral responses', 'b', 'intermediate',
 'Jung proposed the collective unconscious — a layer of the unconscious shared across humanity containing universal patterns called archetypes (Shadow, Anima, Hero).'),

(8, 'Albert Bandura\'s concept of "self-efficacy" refers to:',
 'Objective ability to perform a task', 'Belief in one\'s capability to execute behaviors to produce outcomes', 'Internal locus of control', 'Intrinsic motivation', 'b', 'intermediate',
 'Self-efficacy is one\'s belief in their capacity to successfully perform a specific behavior or task in a given situation.'),

(8, 'Which personality disorder is characterized by instability in relationships, self-image, and intense fear of abandonment?',
 'Narcissistic Personality Disorder', 'Antisocial Personality Disorder', 'Borderline Personality Disorder', 'Histrionic Personality Disorder', 'c', 'intermediate',
 'Borderline Personality Disorder (BPD) features unstable relationships, self-image, and emotions, along with impulsivity and fear of abandonment.');

-- ── PSYCHOLOGICAL ASSESSMENT QUESTIONS ────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(9, 'The MMPI-2 (Minnesota Multiphasic Personality Inventory) is an example of a:',
 'Projective test', 'Intelligence test', 'Objective self-report personality test', 'Neuropsychological battery', 'c', 'intermediate',
 'The MMPI-2 is an objective (standardized, scored) self-report personality inventory widely used in clinical and forensic settings.'),

(9, 'The Rorschach Inkblot Test is classified as a:',
 'Objective personality test', 'Projective personality test', 'Cognitive assessment', 'Neuroimaging technique', 'b', 'beginner',
 'The Rorschach is a projective test where ambiguous stimuli (inkblots) elicit responses believed to reveal unconscious processes.'),

(9, 'Test reliability refers to:',
 'Whether a test measures what it claims to measure', 'The consistency of test scores across time and conditions', 'How well the test predicts future performance', 'The range of scores in a population', 'b', 'beginner',
 'Reliability is the consistency or stability of a test\'s results — a reliable test yields similar scores under similar conditions.'),

(9, 'Standard deviation in psychological testing represents:',
 'The average score of the group', 'The middle score of the distribution', 'The average distance of scores from the mean', 'The highest minus lowest score', 'c', 'intermediate',
 'Standard deviation measures the average amount by which individual scores deviate from the mean — an indicator of score spread/variability.');

-- ── I/O PSYCHOLOGY QUESTIONS ──────────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(10, 'Frederick Taylor\'s "Scientific Management" focused on:',
 'Employee well-being and motivation', 'Finding the one best way to perform tasks efficiently', 'Democratic leadership styles', 'Organizational culture development', 'b', 'intermediate',
 'Scientific Management (Taylorism) used systematic study of work to find the most efficient method, emphasizing time-and-motion studies and piece-rate pay.'),

(10, 'Which leadership theory suggests that leaders adapt their style based on the maturity/readiness of followers?',
 'Transformational Leadership Theory', 'Trait Theory', 'Situational Leadership Theory (Hersey & Blanchard)', 'Path-Goal Theory', 'c', 'advanced',
 'Situational Leadership Theory proposes that effective leaders adjust their style (directing, coaching, supporting, delegating) based on follower development level.'),

(10, 'Job satisfaction is most directly linked to which of Herzberg\'s "motivator" factors?',
 'Salary and pay', 'Company policy', 'Achievement and recognition', 'Working conditions', 'c', 'intermediate',
 'Herzberg\'s Two-Factor Theory distinguishes motivators (achievement, recognition, work itself) from hygiene factors (salary, policy, conditions) in job satisfaction.');

-- ── RESEARCH METHODS QUESTIONS ────────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(11, 'In a true experiment, the variable that the researcher manipulates is called the:',
 'Dependent variable', 'Control variable', 'Independent variable', 'Confounding variable', 'c', 'beginner',
 'The independent variable (IV) is deliberately manipulated by the researcher to observe its effect on the dependent variable (DV).'),

(11, 'A "double-blind" study means that:',
 'Only participants do not know group assignment', 'Only researchers do not know group assignment', 'Neither participants nor researchers know group assignments', 'The study uses two control groups', 'c', 'intermediate',
 'In double-blind studies, neither participants nor researchers know who is in the experimental vs. control group, minimizing expectancy effects.'),

(11, 'Which research design allows researchers to establish cause-and-effect relationships?',
 'Case study', 'Naturalistic observation', 'Survey research', 'True experimental design with random assignment', 'd', 'beginner',
 'Only true experiments with random assignment allow causal inferences, as randomization controls for confounding variables.'),

(11, 'The Hawthorne Effect refers to:',
 'Participants behaving differently because they know they are being observed', 'Regression to the mean in longitudinal studies', 'Experimenter bias in data collection', 'Practice effects in repeated testing', 'a', 'intermediate',
 'The Hawthorne Effect occurs when participants alter their behavior because they know they are being watched or are part of a study.');

-- ── STATISTICS QUESTIONS ──────────────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(12, 'A p-value of 0.03 in a hypothesis test means:',
 'There is a 3% chance the hypothesis is true', 'There is a 3% probability of obtaining results this extreme if the null hypothesis is true', 'The effect size is 3%', 'The study has 97% power', 'b', 'intermediate',
 'The p-value represents the probability of obtaining results as extreme as observed, assuming the null hypothesis is true. p < 0.05 is typically statistically significant.'),

(12, 'The correlation coefficient (r) ranges from:',
 '0 to 1', '-1 to 0', '-1 to +1', '0 to 100', 'c', 'beginner',
 'Pearson\'s r ranges from -1 (perfect negative correlation) to +1 (perfect positive correlation), with 0 indicating no linear relationship.'),

(12, 'Which statistical test compares the means of two independent groups?',
 'Chi-square test', 'Pearson correlation', 'Independent samples t-test', 'ANOVA', 'c', 'intermediate',
 'The independent samples t-test compares means between two separate groups to determine if they differ significantly.'),

(12, 'In a normal distribution, approximately what percentage of scores fall within one standard deviation of the mean?',
 '50%', '68%', '95%', '99.7%', 'b', 'intermediate',
 'In a normal (bell-curve) distribution: ~68% within ±1 SD, ~95% within ±2 SD, ~99.7% within ±3 SD (the empirical rule).');

-- ── ETHICS QUESTIONS ──────────────────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(13, 'The APA\'s ethical principle of "Beneficence and Nonmaleficence" requires psychologists to:',
 'Maintain confidentiality at all times', 'Strive to benefit clients and avoid harm', 'Obtain informed consent', 'Respect client autonomy', 'b', 'beginner',
 'Beneficence means actively doing good; Nonmaleficence means avoiding harm. Together, they require psychologists to maximize benefits and minimize risks.'),

(13, 'Informed consent requires that research participants:',
 'Sign a waiver releasing researchers from liability', 'Be told the study\'s full hypothesis before starting', 'Receive sufficient information to make a voluntary decision about participation', 'Be compensated financially', 'c', 'beginner',
 'Informed consent ensures participants receive adequate information about the study, understand it, and voluntarily agree to participate without coercion.'),

(13, 'Confidentiality in therapy may be ethically broken when:',
 'The client misses sessions', 'Insurance requires information', 'There is clear danger to the client or others (duty to protect)', 'The therapist disagrees with the client', 'c', 'intermediate',
 'Tarasoff v. Regents (1976) established the duty to warn/protect: confidentiality can be broken when a client poses serious danger to identifiable third parties.'),

(13, 'The APA ethical code regarding multiple relationships (dual relationships) states that:',
 'They are always strictly prohibited', 'They are acceptable if the client consents', 'Psychologists should avoid them when they could impair professional judgment or cause harm', 'Sexual relationships with former clients are permitted after 1 year', 'c', 'advanced',
 'APA Ethics Code 3.05 states psychologists should avoid multiple relationships that could impair objectivity, competence, or effectiveness, or exploit the client.'),

(13, 'Deception in psychological research is ethically permissible ONLY if:',
 'The study is conducted at a university', 'The IRB approves and thorough debriefing occurs afterward', 'Participants are not seriously harmed', 'The sample size is small', 'b', 'intermediate',
 'Deception requires IRB approval, should be used only when essential, no non-deceptive alternative exists, and must be followed by thorough debriefing.');

-- ── BOARD EXAM LEVEL QUESTIONS ────────────────────────────────
INSERT INTO questions (category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, explanation) VALUES
(3, 'A client presents with persistent depressive disorder (dysthymia) and has experienced a major depressive episode. This combination is called:',
 'Cyclothymia', 'Double depression', 'Bipolar II', 'Persistent Complex Bereavement Disorder', 'b', 'board_exam',
 'Double depression refers to the co-occurrence of dysthymia (persistent depressive disorder) with a superimposed major depressive episode.'),

(6, 'According to the levels-of-processing framework (Craik & Lockhart), which encoding leads to the best long-term retention?',
 'Structural encoding (physical features)', 'Phonemic encoding (sound)', 'Semantic encoding (meaning)', 'Maintenance rehearsal', 'c', 'board_exam',
 'Deep (semantic) processing — encoding meaning and connecting information to existing knowledge — produces stronger, more durable memory traces than shallow processing.'),

(4, 'The efficacy of dialectical behavior therapy (DBT) is most supported for which condition?',
 'Schizophrenia', 'Borderline Personality Disorder', 'Obsessive-Compulsive Disorder', 'Autism Spectrum Disorder', 'b', 'board_exam',
 'DBT, developed by Marsha Linehan, is the gold-standard treatment for BPD, combining CBT with mindfulness and distress tolerance skills.'),

(9, 'Which of the following represents an incremental validity argument for psychological testing?',
 'The test correlates well with itself across time', 'The test adds predictive power beyond what is already known', 'The test covers all aspects of a construct', 'The test norms are based on a large sample', 'b', 'board_exam',
 'Incremental validity refers to the degree to which a test improves prediction of a criterion beyond what is already available from other sources.'),

(11, 'In an A-B-A-B single-subject reversal design, the "B" phase represents:',
 'Baseline measurement', 'Treatment/intervention phase', 'Follow-up assessment', 'Placebo condition', 'b', 'board_exam',
 'In ABAB designs: A = baseline (no treatment), B = intervention/treatment. Reverting to baseline (second A) allows demonstration of experimental control.');
