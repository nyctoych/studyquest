<?php
require_once __DIR__ . '/../config/database.php';

class CurriculumMigration {
    public static function run(): void {
        static $alreadyRun = false;
        if ($alreadyRun) return;
        $alreadyRun = true;

        $db = Database::getInstance();

        try {
            // 1. Create tables
            $sql = file_get_contents(__DIR__ . '/../../database/psychology_curriculum_migration.sql');
            $db->exec($sql);

            // 2. Check and Seed Domains
            $domainCount = (int)$db->query("SELECT COUNT(*) FROM psychology_domains")->fetchColumn();
            if ($domainCount < 12) {
                self::seedDomains($db);
            }

            // 3. Check and Seed Parts
            $partCount = (int)$db->query("SELECT COUNT(*) FROM psychology_parts")->fetchColumn();
            if ($partCount < 120) {
                self::seedParts($db);
            }

            // 4. Check and Seed Questions
            $qCount = (int)$db->query("SELECT COUNT(*) FROM learning_questions")->fetchColumn();
            if ($qCount < 20) {
                self::seedQuestions($db);
            }

            // 5. Seed Achievements if needed
            self::seedAchievements($db);

        } catch (Throwable $e) {
            error_log("CurriculumMigration Error: " . $e->getMessage());
        }
    }

    private static function seedDomains(PDO $db): void {
        $domains = [
            [
                'id' => 1,
                'name' => 'General Psychology',
                'slug' => 'general-psychology',
                'description' => 'Explore the foundational theories, historical milestones, and core systems of mind and behavior.',
                'icon' => '🧠',
                'color' => '#8b5cf6',
                'display_order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Biological Psychology',
                'slug' => 'biological-psychology',
                'description' => 'Uncover the neural, endocrine, and physiological architecture that generates human consciousness and action.',
                'icon' => '⚡',
                'color' => '#3b82f6',
                'display_order' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Cognitive Psychology',
                'slug' => 'cognitive-psychology',
                'description' => 'Understand how people perceive, attend, represent, remember, solve problems, and communicate.',
                'icon' => '💡',
                'color' => '#06b6d4',
                'display_order' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Developmental Psychology',
                'slug' => 'developmental-psychology',
                'description' => 'Trace physical, cognitive, socioemotional, and moral growth from conception through the entire lifespan.',
                'icon' => '🌱',
                'color' => '#10b981',
                'display_order' => 4,
            ],
            [
                'id' => 5,
                'name' => 'Social Psychology',
                'slug' => 'social-psychology',
                'description' => 'Examine how real or imagined presence of others influences attitudes, group dynamics, conformity, and altruism.',
                'icon' => '👥',
                'color' => '#f59e0b',
                'display_order' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Personality Psychology',
                'slug' => 'personality-psychology',
                'description' => 'Investigate individual differences, psychodynamic, trait, humanistic, and cognitive-behavioral personality theories.',
                'icon' => '🎭',
                'color' => '#ec4899',
                'display_order' => 6,
            ],
            [
                'id' => 7,
                'name' => 'Abnormal Psychology',
                'slug' => 'abnormal-psychology',
                'description' => 'Master diagnostic criteria, etiology, psychopathology, and clinical presentation under the DSM-5 framework.',
                'icon' => '🌪️',
                'color' => '#ef4444',
                'display_order' => 7,
            ],
            [
                'id' => 8,
                'name' => 'Clinical and Counseling Psychology',
                'slug' => 'clinical-counseling-psychology',
                'description' => 'Study therapeutic modalities, clinical interviewing, mental status assessment, and evidence-based interventions.',
                'icon' => '🩺',
                'color' => '#14b8a6',
                'display_order' => 8,
            ],
            [
                'id' => 9,
                'name' => 'Psychological Assessment and Testing',
                'slug' => 'psychological-assessment-testing',
                'description' => 'Psychometric theory, test construction, reliability, validity, item analysis, and psychodiagnostic batteries.',
                'icon' => '📊',
                'color' => '#6366f1',
                'display_order' => 9,
            ],
            [
                'id' => 10,
                'name' => 'Industrial / Organizational Psychology',
                'slug' => 'industrial-organizational-psychology',
                'description' => 'Apply psychological principles to job analysis, selection, training, leadership, motivation, and workplace wellness.',
                'icon' => '🏢',
                'color' => '#84cc16',
                'display_order' => 10,
            ],
            [
                'id' => 11,
                'name' => 'Research Methods and Statistics',
                'slug' => 'research-methods-statistics',
                'description' => 'Experimental designs, parametric and non-parametric statistical tests, hypothesis testing, and APA reporting.',
                'icon' => '📐',
                'color' => '#f97316',
                'display_order' => 11,
            ],
            [
                'id' => 12,
                'name' => 'Ethics and Professional Practice',
                'slug' => 'ethics-professional-practice',
                'description' => 'Code of ethics, confidentiality, informed consent, legal mandates, and ethical dilemma resolution in practice.',
                'icon' => '⚖️',
                'color' => '#a855f7',
                'display_order' => 12,
            ],
        ];

        $stmt = $db->prepare("
            INSERT INTO psychology_domains (id, name, slug, description, icon, color, display_order, total_parts, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 10, 'active')
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                slug = VALUES(slug),
                description = VALUES(description),
                icon = VALUES(icon),
                color = VALUES(color),
                display_order = VALUES(display_order)
        ");

        foreach ($domains as $d) {
            $stmt->execute([
                $d['id'], $d['name'], $d['slug'], $d['description'], $d['icon'], $d['color'], $d['display_order']
            ]);
        }
    }

    private static function seedParts(PDO $db): void {
        // Definitions of 10 parts for each of the 12 domains (120 parts total)
        $domainParts = [
            1 => [ // General Psychology
                1 => ['Introduction to Psychological Science & History', 'learn', 50, 25, 'Foundations of psychology as an empirical science, Wundt, James, structuralism, and functionalism.'],
                2 => ['Research Approaches & The Scientific Method', 'review', 50, 25, 'Formulating testable hypotheses, operational definitions, observation vs experiments.'],
                3 => ['Sensation, Perception & The Physical World', 'practice', 60, 30, 'Sensory transduction, thresholds, Weber-Fechner law, and sensory adaptation.'],
                4 => ['States of Consciousness & Sleep', 'scenario', 70, 35, 'Circadian rhythms, sleep architecture (NREM & REM), dreams, and altered states.'],
                5 => ['Principles of Conditioning & Learning', 'quiz', 80, 40, 'Classical conditioning (Pavlov), operant conditioning (Skinner), schedules of reinforcement.'],
                6 => ['Memory: Encoding, Retention & Retrieval', 'practice', 90, 45, 'Sensory, short-term, working, and long-term memory systems; serial position effects.'],
                7 => ['Thinking, Intelligence & Language', 'scenario', 100, 50, 'Problem-solving heuristics, Spearman’s g, Gardner’s multiple intelligences, language development.'],
                8 => ['Motivation, Emotion & Stress', 'review', 120, 60, 'Drive-reduction theory, Yerkes-Dodson law, Cannon-Bard vs James-Lange, Lazarus appraisal.'],
                9 => ['Lifespan Overview & Personality Foundations', 'challenge', 150, 75, 'Integrated biopsychosocial model across development and core personality structures.'],
                10 => ['General Psychology Mastery Challenge', 'mastery', 250, 100, 'Comprehensive board-exam level mastery challenge synthesizing all core domains of General Psychology.'],
            ],
            2 => [ // Biological Psychology
                1 => ['Neurons, Synaptic Transmission & Neurotransmitters', 'learn', 50, 25, 'Resting potential, action potential, myelin, synaptic vesicle release, and neurotransmitters.'],
                2 => ['Central & Peripheral Nervous System Architecture', 'review', 50, 25, 'Somatic vs autonomic divisions, sympathetic flight-or-fight, and parasympathetic rest-and-digest.'],
                3 => ['The Brain: Cerebral Cortex & Subcortical Structures', 'practice', 60, 30, 'Frontal, parietal, temporal, and occipital lobes; limbic system (hippocampus, amygdala).'],
                4 => ['Neuroplasticity, Recovery & Brain Lateralization', 'scenario', 70, 35, 'Hemispheric specialization, split-brain studies (Sperry/Gazzaniga), synaptic pruning.'],
                5 => ['The Endocrine System & Hormonal Influence', 'quiz', 80, 40, 'Hypothalamic-pituitary-adrenal (HPA) axis, thyroid, adrenal medulla, and behavioral modulation.'],
                6 => ['Biological Rhythms, Sleep & Arousal', 'practice', 90, 45, 'Suprachiasmatic nucleus (SCN), melatonin release, reticular activating system, and EEG wavebands.'],
                7 => ['Sensory Transduction & Visual/Auditory Pathways', 'scenario', 100, 50, 'Photoreceptors (rods & cones), optic chiasm, primary visual cortex, and tonotopic auditory mapping.'],
                8 => ['Motor Control & Neurobiology of Movement', 'review', 120, 60, 'Primary motor cortex, basal ganglia (dopaminergic pathways), cerebellum, and motor coordination.'],
                9 => ['Biological Foundations of Emotion, Stress & Immune Function', 'challenge', 150, 75, 'Psychoneuroimmunology, chronic cortisol secretion, sympathetic arousal, and emotional circuits.'],
                10 => ['Biopsychology Mastery Challenge', 'mastery', 250, 100, 'Comprehensive board-exam challenge on neural signaling, brain neuroanatomy, and physiological mechanisms.'],
            ],
            3 => [ // Cognitive Psychology
                1 => ['Foundations of Cognitive Psychology & Information Processing', 'learn', 50, 25, 'The cognitive revolution, computer metaphor of mind, mental chronometry, and reaction time.'],
                2 => ['Attention: Selective, Divided & Executive Control', 'review', 50, 25, 'Filter theories (Broadbent, Treisman), attentional bottleneck, Stroop effect, and cocktail party phenomenon.'],
                3 => ['Perception: Pattern Recognition & Object Identification', 'practice', 60, 30, 'Bottom-up vs top-down processing, Gestalt laws of perceptual organization, and agnosia.'],
                4 => ['Memory Architecture: Sensory, Working & Long-Term Memory', 'scenario', 70, 35, 'Atkinson-Shiffrin model, Baddeley’s multicomponent working memory model, phonological loop, and visuospatial sketchpad.'],
                5 => ['Knowledge Representation, Schemas & Semantic Networks', 'quiz', 80, 40, 'Collins & Quillian hierarchical networks, spreading activation, prototypes, and exemplar models.'],
                6 => ['Language Processing, Comprehension & Production', 'practice', 90, 45, 'Phonology, morphology, syntax, semantics, pragmatics, Broca’s vs Wernicke’s aphasias.'],
                7 => ['Problem Solving & Heuristics vs. Algorithms', 'scenario', 100, 50, 'Means-end analysis, analogical problem solving, mental sets, and functional fixedness.'],
                8 => ['Judgment, Decision-Making & Cognitive Biases', 'review', 120, 60, 'Availability heuristic, representativeness heuristic, framing effects, anchoring, and prospect theory.'],
                9 => ['Metacognition, Executive Function & Creativity', 'challenge', 150, 75, 'Self-regulated learning, convergent vs divergent thinking, and prefrontal cognitive control.'],
                10 => ['Cognitive Mastery Challenge', 'mastery', 250, 100, 'Advanced assessment spanning memory architecture, language processing, problem-solving, and cognitive heuristics.'],
            ],
            4 => [ // Developmental Psychology
                1 => ['Theories of Development: Piaget, Vygotsky & Erikson', 'learn', 50, 25, 'Stage theories, assimilation/accommodation, zone of proximal development, and psychosocial crises.'],
                2 => ['Prenatal Development, Genetics & Teratogens', 'review', 50, 25, 'Germinal, embryonic, and fetal stages; critical periods, maternal health, and environmental toxins.'],
                3 => ['Infancy: Motor Milestones, Perception & Attachment', 'practice', 60, 30, 'Neonatal reflexes, visual cliff, Ainsworth Strange Situation: secure, avoidant, resistant, disorganized.'],
                4 => ['Early Childhood: Cognitive Growth, Play & Language Acquisition', 'scenario', 70, 35, 'Preoperational thought, egocentrism, theory of mind development, and types of childhood play.'],
                5 => ['Middle Childhood: Peer Dynamics, Industry & Moral Reasoning', 'quiz', 80, 40, 'Erikson’s industry vs inferiority, concrete operational conservation, and Kohlberg’s moral levels.'],
                6 => ['Adolescence: Identity Formation, Puberty & Risk Taking', 'practice', 90, 45, 'Marcia’s identity statuses (diffusion, foreclosure, moratorium, achievement), imaginary audience.'],
                7 => ['Emerging & Young Adulthood: Career, Intimacy & Independence', 'scenario', 100, 50, 'Arnett’s emerging adulthood markers, Erikson’s intimacy vs isolation, and postformal thought.'],
                8 => ['Middle Adulthood: Generativity, Midlife Transitions & Cognitive Changes', 'review', 120, 60, 'Generativity vs stagnation, fluid vs crystallized intelligence trajectories, and work-family balance.'],
                9 => ['Late Adulthood: Integrity, Longevity, Dementia & End-of-Life', 'challenge', 150, 75, 'Integrity vs despair, successful aging, neurocognitive decline vs dementia, and Kübler-Ross stages.'],
                10 => ['Developmental Mastery Challenge', 'mastery', 250, 100, 'Comprehensive licensure-level examination covering biological, cognitive, and psychosocial lifespan trajectories.'],
            ],
            5 => [ // Social Psychology
                1 => ['Social Cognition, Attribution Theory & Fundamental Attribution Error', 'learn', 50, 25, 'Internal vs external attributions, Kelley’s covariation model, and actor-observer discrepancy.'],
                2 => ['The Self in a Social World: Self-Concept, Self-Esteem & Self-Serving Bias', 'review', 50, 25, 'Social comparison theory, self-handicapping, spotlight effect, and cultural self-construals.'],
                3 => ['Attitudes, Attitude Formation & Cognitive Dissonance', 'practice', 60, 30, 'Festinger’s cognitive dissonance, elaboration likelihood model (central vs peripheral route).'],
                4 => ['Conformity, Compliance & Obedience', 'scenario', 70, 35, 'Asch line studies, Milgram shock experiments, Zimbardo Stanford prison study, and compliance tactics.'],
                5 => ['Group Dynamics: Groupthink, Social Loafing & Deindividuation', 'quiz', 80, 40, 'Group polarization, Janis’s groupthink symptoms, social facilitation, and diffusion of responsibility.'],
                6 => ['Prejudice, Discrimination & Stereotype Threat', 'practice', 90, 45, 'In-group favoritism, minimal group paradigm, Steele’s stereotype threat, and contact hypothesis.'],
                7 => ['Aggression: Theories, Biological & Social Triggers', 'scenario', 100, 50, 'Frustration-aggression hypothesis, Bandura’s social learning, and media violence models.'],
                8 => ['Prosocial Behavior, Altruism & Bystander Intervention', 'review', 120, 60, 'Darley & Latané five-stage decision model, kin selection, and empathy-altruism hypothesis.'],
                9 => ['Interpersonal Attraction, Love & Close Relationships', 'challenge', 150, 75, 'Sternberg’s triangular theory of love, matching hypothesis, mere exposure effect, and attachment.'],
                10 => ['Social Psychology Mastery Challenge', 'mastery', 250, 100, 'Board-exam simulation focusing on social cognition, influence, group processes, and interpersonal behavior.'],
            ],
            6 => [ // Personality Psychology
                1 => ['Introduction to Personality & Measurement Paradigms', 'learn', 50, 25, 'Defining personality traits, state vs trait distinction, person-situation debate (Mischel).'],
                2 => ['Psychodynamic Perspectives: Freud & Defense Mechanisms', 'review', 50, 25, 'Id, ego, superego, psychosexual stages, repression, projection, sublimation, and reaction formation.'],
                3 => ['Neo-Freudian & Psychosocial Views: Jung, Adler & Horney', 'practice', 60, 30, 'Collective unconscious, archetypes, Adler’s inferiority complex & birth order, Horney’s basic anxiety.'],
                4 => ['Trait Theories: Allport, Cattell, Eysenck & The Big Five (OCEAN)', 'scenario', 70, 35, 'Cardinal/central/secondary traits, 16PF, Eysenck PEN model, and the Five Factor Model dimensions.'],
                5 => ['Humanistic Perspectives: Rogers & Maslow', 'quiz', 80, 40, 'Unconditional positive regard, congruence, hierarchy of needs, and self-actualization qualities.'],
                6 => ['Behavioral & Social-Cognitive Theories: Bandura', 'practice', 90, 45, 'Reciprocal determinism, self-efficacy beliefs, observational learning, and locus of control (Rotter).'],
                7 => ['Biological & Evolutionary Approaches to Personality', 'scenario', 100, 50, 'Twin and adoption studies, behavioral genetics, Gray’s reinforcement sensitivity theory (BIS/BAS).'],
                8 => ['Personality Assessment: Inventories vs. Projective Measures', 'review', 120, 60, 'Self-report inventories (MMPI, NEO-PI-R) vs projective tests (Rorschach, TAT); validity and reliability.'],
                9 => ['Cultural Influences on Personality & Narrative Identity', 'challenge', 150, 75, 'Individualism vs collectivism, McAdams’ three tiers of personality: traits, adaptations, and life stories.'],
                10 => ['Personality Mastery Challenge', 'mastery', 250, 100, 'Integrative mastery exam testing psychoanalytic, trait, humanistic, and cognitive-behavioral personality paradigms.'],
            ],
            7 => [ // Abnormal Psychology
                1 => ['Defining Abnormality, Stigma & The DSM-5 Framework', 'learn', 50, 25, 'The 4 Ds (Deviance, Distress, Dysfunction, Danger), categorical vs dimensional approaches.'],
                2 => ['Generalized Anxiety, Panic Disorder & Specific Phobias', 'review', 50, 25, 'Etiology, behavioral avoidance, catastrophic misinterpretation, and exposure hierarchies.'],
                3 => ['Obsessive-Compulsive & Related Disorders', 'practice', 60, 30, 'Obsessions vs compulsions, negative reinforcement cycle, BDD, trichotillomania, and ERP therapy.'],
                4 => ['Trauma- and Stressor-Related Disorders: PTSD & Acute Stress', 'scenario', 70, 35, 'Criterion A traumatic event, intrusive re-experiencing, hyperarousal, avoidance, and EMDR/CBT.'],
                5 => ['Depressive Disorders & Bipolar Disorders', 'quiz', 80, 40, 'MDD, Persistent Depressive Disorder (Dysthymia), Bipolar I vs Bipolar II, cyclothymia, and suicide risk.'],
                6 => ['Schizophrenia Spectrum & Other Psychotic Disorders', 'practice', 90, 45, 'Positive (delusions, hallucinations) vs negative symptoms (avolition, flat affect), dopamine hypothesis.'],
                7 => ['Personality Disorders: Clusters A, B, and C', 'scenario', 100, 50, 'Cluster A (Odd/Eccentric), Cluster B (Dramatic/Erratic - BPD, ASPD), Cluster C (Anxious/Fearful).'],
                8 => ['Somatic Symptom & Dissociative Disorders', 'review', 120, 60, 'Conversion disorder, Illness Anxiety Disorder, Dissociative Identity Disorder, and psychogenic amnesia.'],
                9 => ['Neurodevelopmental & Neurocognitive Disorders', 'challenge', 150, 75, 'ADHD subtypes, Autism Spectrum Disorder, Alzheimer’s, vascular dementia, and cognitive screening.'],
                10 => ['Abnormal Psychology Mastery Challenge', 'mastery', 250, 100, 'Comprehensive board exam simulation featuring differential diagnosis and clinical case vignettes.'],
            ],
            8 => [ // Clinical and Counseling Psychology
                1 => ['Roles, Models of Training & The Therapeutic Relationship', 'learn', 50, 25, 'Scientist-practitioner (Boulder) vs practitioner-scholar (Vail), therapeutic alliance, and core conditions.'],
                2 => ['Clinical Interviewing & Mental Status Examination (MSE)', 'review', 50, 25, 'Appearance, behavior, speech, mood/affect, thought process/content, perception, and insight/judgment.'],
                3 => ['Psychoanalytic & Psychodynamic Therapy Techniques', 'practice', 60, 30, 'Free association, dream interpretation, transference, countertransference, and working through defenses.'],
                4 => ['Cognitive-Behavioral Therapy (CBT) & REBT Techniques', 'scenario', 70, 35, 'Beck’s cognitive triad, identifying automatic thoughts, cognitive distortions, ABC model of Ellis.'],
                5 => ['Humanistic, Person-Centered & Experiential Therapies', 'quiz', 80, 40, 'Rogers’ unconditional positive regard, accurate empathy, genuineness, and Gestalt empty chair technique.'],
                6 => ['Systemic, Couple & Family Therapy Frameworks', 'practice', 90, 45, 'Bowen intergenerational systems, differentiation of self, Minuchin structural family therapy, genograms.'],
                7 => ['Third-Wave CBT: ACT & Dialectical Behavior Therapy (DBT)', 'scenario', 100, 50, 'Mindfulness, psychological flexibility, distress tolerance, emotion regulation, and interpersonal effectiveness.'],
                8 => ['Crisis Intervention, Suicide Risk Assessment & Safety Planning', 'review', 120, 60, 'Columbia-Suicide Severity Rating Scale (C-SSRS), lethal means restriction, and emergency protocol.'],
                9 => ['Evidence-Based Practice & Therapeutic Efficacy Evaluation', 'challenge', 150, 75, 'Randomized controlled trials, effect size, Dodo bird verdict, and client-outcome monitoring.'],
                10 => ['Clinical & Counseling Mastery Challenge', 'mastery', 250, 100, 'Board-level case management examination evaluating treatment planning, clinical interview, and ethics.'],
            ],
            9 => [ // Psychological Assessment and Testing
                1 => ['Psychometric Foundations: Norms & Standardization', 'learn', 50, 25, 'Standardized administration, norm-referenced vs criterion-referenced, raw to standard score conversion (z, T, IQ).'],
                2 => ['Reliability: Forms, Measurement Error & SEM', 'review', 50, 25, 'Test-retest, parallel forms, Cronbach’s alpha, split-half, inter-rater reliability, and standard error of measurement.'],
                3 => ['Validity: Content, Criterion & Construct Validity', 'practice', 60, 30, 'Face validity, concurrent/predictive validity, convergent/discriminant validity, and factor analysis.'],
                4 => ['Item Analysis: Difficulty, Discrimination & Distractors', 'scenario', 70, 35, 'Item difficulty index (p-value), item discrimination index (D), point-biserial correlation, and distractor plausibility.'],
                5 => ['Intellectual Assessment: Stanford-Binet & Wechsler Scales', 'quiz', 80, 40, 'WAIS-IV / WISC-V four-index model (VCI, PRI, WMI, PSI), FSIQ interpretation, and Flynn effect.'],
                6 => ['Objective Personality Inventories (MMPI, 16PF, NEO-PI)', 'practice', 90, 45, 'MMPI clinical & validity scales (L, F, K), profile elevation, code types, and trait profile reporting.'],
                7 => ['Projective Techniques: Rorschach, TAT & Sentence Completion', 'scenario', 100, 50, 'Exner Comprehensive System, thematic apperception scoring, and psychodynamic projection evaluation.'],
                8 => ['Neuropsychological & Educational Achievement Testing', 'review', 120, 60, 'Bender-Gestalt, Wisconsin Card Sorting Test, Halstead-Reitan battery, WJ-IV achievement measures.'],
                9 => ['Ethical Considerations in Testing, Bias & Cultural Fairness', 'challenge', 150, 75, 'Test security, informed consent, culture-fair testing, test accommodation, and test misuse prevention.'],
                10 => ['Psychological Assessment Mastery Challenge', 'mastery', 250, 100, 'Advanced psychometrics and battery interpretation assessment simulating professional board testing.'],
            ],
            10 => [ // Industrial / Organizational Psychology
                1 => ['Introduction to I/O Psychology & Scientist-Practitioner Model', 'learn', 50, 25, 'Hawthorne studies, human relations movement, industrial vs organizational subfields, and research ethics.'],
                2 => ['Job Analysis, Job Descriptions & Competency Modeling', 'review', 50, 25, 'Task-oriented vs worker-oriented methods, KSAOs, PAQ, O*NET database, and legal compliance.'],
                3 => ['Personnel Recruitment, Selection & Assessment Centers', 'practice', 60, 30, 'Structured interviews, situational judgment tests (SJTs), adverse impact, 4/5ths rule, assessment centers.'],
                4 => ['Employee Training, Needs Assessment & Instructional Design', 'scenario', 70, 35, 'Organizational, task, and person analysis; Kirkpatrick’s four levels of training evaluation (Reaction, Learning, Behavior, Results).'],
                5 => ['Performance Appraisal & 360-Degree Feedback', 'quiz', 80, 40, 'Graphic rating scales, BARS, BOS, appraisal biases (halo effect, leniency, central tendency), and multisource ratings.'],
                6 => ['Work Motivation Theories: Expectancy, Equity & Goal-Setting', 'practice', 90, 45, 'Vroom’s VIE theory, Adams’ equity theory, Locke & Latham’s goal-setting theory, Herzberg’s two-factor model.'],
                7 => ['Job Satisfaction, Organizational Commitment & Burnout', 'scenario', 100, 50, 'Affective, continuance, and normative commitment; job characteristics model (Hackman & Oldham); Maslach Burnout Inventory.'],
                8 => ['Leadership Theories: Transformational, Transactional & Situational', 'review', 120, 60, 'Fiedler’s contingency model, Bass transformational leadership, LMX theory, and servant leadership.'],
                9 => ['Organizational Culture, Climate & Change Management', 'challenge', 150, 75, 'Schein’s levels of culture (artifacts, espoused values, assumptions), Lewin’s 3-step change model, OD interventions.'],
                10 => ['I/O Psychology Mastery Challenge', 'mastery', 250, 100, 'Comprehensive board-exam challenge covering talent management, selection psychometrics, and organizational leadership.'],
            ],
            11 => [ // Research Methods and Statistics
                1 => ['The Scientific Method & Formulating Testable Hypotheses', 'learn', 50, 25, 'Operationalization, directional vs non-directional hypotheses, falsifiability, and variables in psychological inquiry.'],
                2 => ['Non-Experimental Research: Observational & Correlational Designs', 'review', 50, 25, 'Naturalistic observation, archival research, cross-sectional vs longitudinal surveys, and directionality problem.'],
                3 => ['Experimental Design: Independent, Dependent & Extraneous Variables', 'practice', 60, 30, 'Random assignment, control groups, internal vs external validity, threats to internal validity (history, maturation).'],
                4 => ['Quasi-Experimental & Single-Subject Designs', 'scenario', 70, 35, 'Non-equivalent control groups, interrupted time series, ABAB reversal designs, and multiple baseline studies.'],
                5 => ['Descriptive Statistics: Central Tendency, Dispersion & Normal Distribution', 'quiz', 80, 40, 'Mean, median, mode; variance, standard deviation, skewness, kurtosis, and empirical rule (68-95-99.7).'],
                6 => ['Inferential Statistics: Hypothesis Testing, Type I & Type II Errors', 'practice', 90, 45, 'Null vs alternative hypothesis, alpha levels, p-value interpretation, statistical power (1 - beta).'],
                7 => ['Parametric Tests: t-Tests and ANOVA', 'scenario', 100, 50, 'Independent and paired samples t-tests, one-way ANOVA, factorial ANOVA, main effects, interactions, post-hoc tests.'],
                8 => ['Non-Parametric Tests & Correlation', 'review', 120, 60, 'Chi-square test of independence, Mann-Whitney U, Wilcoxon signed-rank, Pearson r vs Spearman rho.'],
                9 => ['Effect Size, Statistical Power & APA Format Reporting', 'challenge', 150, 75, 'Cohen’s d, eta-squared, statistical reporting standards, confidence intervals, and research reproducibility.'],
                10 => ['Research Methods & Statistics Mastery Challenge', 'mastery', 250, 100, 'Rigorous licensure simulation featuring statistical test selection, study design critique, and data analysis interpretation.'],
            ],
            12 => [ // Ethics and Professional Practice
                1 => ['Core Ethical Principles of Psychological Practice', 'learn', 50, 25, 'Beneficence and nonmaleficence, fidelity and responsibility, integrity, justice, and respect for rights and dignity.'],
                2 => ['Informed Consent in Therapy, Research & Assessment', 'review', 50, 25, 'Elements of valid consent, disclosure of risks, assent in minors, and ongoing consent throughout treatment.'],
                3 => ['Confidentiality, Privilege & Mandatory Reporting Exceptions', 'practice', 60, 30, 'Limits of confidentiality, Tarasoff duty to warn/protect, child and elder abuse reporting, and court subpoenas.'],
                4 => ['Competence, Professional Boundaries & Multiple Relationships', 'scenario', 70, 35, 'Boundaries of competence, avoiding harmful dual relationships, sexual intimacies with clients, and gifts.'],
                5 => ['Ethics in Psychological Assessment & Test Security', 'quiz', 80, 40, 'Qualified test users, obsolete tests and results, test security maintenance, and releasing raw test data.'],
                6 => ['Research Ethics with Human Participants & Institutional Review Boards', 'practice', 90, 45, 'IRB approval, deceptive research justification, debriefing protocols, and humane animal care standards.'],
                7 => ['Ethical Advertising, Financial Arrangements & Record Keeping', 'scenario', 100, 50, 'Public statements, fee transparency, contingency fees prohibition, and legal retention of clinical records.'],
                8 => ['Resolving Ethical Dilemmas & Professional Conduct Committees', 'review', 120, 60, 'Ethical decision-making models, informal resolution vs formal reporting, and cooperation with ethics committees.'],
                9 => ['Licensure Regulations & Philippine Psychology Act (RA 10029)', 'challenge', 150, 75, 'Psychologist vs Psychometrician scope of practice, regulatory board requirements, CPD, and professional accountability.'],
                10 => ['Ethics & Professional Mastery Challenge', 'mastery', 250, 100, 'High-stakes board examination presenting complex real-world ethical dilemmas, legal statutes, and professional liability.'],
            ],
        ];

        $stmt = $db->prepare("
            INSERT INTO psychology_parts (
                domain_id, part_number, title, slug, description, part_type, 
                overview, key_concepts, important_terms, clinical_example, summary_notes,
                xp_reward, coin_reward, passing_score, is_published
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                slug = VALUES(slug),
                description = VALUES(description),
                part_type = VALUES(part_type),
                overview = VALUES(overview),
                key_concepts = VALUES(key_concepts),
                important_terms = VALUES(important_terms),
                clinical_example = VALUES(clinical_example),
                summary_notes = VALUES(summary_notes),
                xp_reward = VALUES(xp_reward),
                coin_reward = VALUES(coin_reward),
                passing_score = VALUES(passing_score)
        ");

        foreach ($domainParts as $domainId => $parts) {
            foreach ($parts as $partNum => $data) {
                $title = $data[0];
                $partType = $data[1];
                $xp = $data[2];
                $coins = $data[3];
                $desc = $data[4];
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

                // Educational notes and structured content
                $overview = "In this learning part, students explore {$title}. This module directly prepares candidates for professional licensure and undergraduate mastery through clear structural models and empirical findings.";

                $keyConcepts = json_encode([
                    [
                        'title' => 'Core Foundational Framework',
                        'definition' => "The primary theoretical paradigm underpinning {$title}, defining operational mechanisms and structural assumptions.",
                        'icon' => '🔬'
                    ],
                    [
                        'title' => 'Empirical Methodology',
                        'definition' => 'Standardized experimental and psychometric measurement techniques used to observe and validate this phenomenon.',
                        'icon' => '📊'
                    ],
                    [
                        'title' => 'Clinical & Applied Utility',
                        'definition' => 'Real-world translation into psychological evaluation, evidence-based intervention, and professional assessment.',
                        'icon' => '💡'
                    ]
                ]);

                $importantTerms = json_encode([
                    [
                        'term' => 'Operational Definition',
                        'definition' => 'A statement of the exact procedures used to define and measure a research or clinical variable.'
                    ],
                    [
                        'term' => 'Construct Validity',
                        'definition' => 'The degree to which an assessment instrument measures the theoretical concept it claims to measure.'
                    ],
                    [
                        'term' => 'Empirical Evidence',
                        'definition' => 'Information acquired by observation or experimentation that is capable of being verified or disproved.'
                    ]
                ]);

                $clinicalExample = "Case Application: A licensed practitioner reviews an evaluation scenario where {$title} principles guide the formulation of diagnostic hypotheses and structured interventions.";

                $summaryNotes = "Key Takeaway: Master the foundational definitions, distinguish between competing theoretical schools, and verify comprehension via the graded assessment.";

                $passingScore = 70;

                $stmt->execute([
                    $domainId, $partNum, $title, $slug, $desc, $partType,
                    $overview, $keyConcepts, $importantTerms, $clinicalExample, $summaryNotes,
                    $xp, $coins, $passingScore
                ]);
            }
        }
    }

    private static function seedQuestions(PDO $db): void {
        // High quality board-exam aligned questions for various parts
        $questions = [
            // Cognitive Psychology - Part 1 (Foundations)
            [
                'domain_slug' => 'cognitive-psychology',
                'part_num' => 1,
                'question' => 'Which historical development is widely recognized as marking the emergence of the Cognitive Revolution in psychology?',
                'choice_a' => 'The publication of Watson’s Behaviorist Manifesto in 1913',
                'choice_b' => 'The 1956 MIT Symposium on Information Theory featuring Chomsky, Miller, and Newell & Simon',
                'choice_c' => 'Sigmund Freud’s publication of The Interpretation of Dreams in 1900',
                'choice_d' => 'B.F. Skinner’s formulation of operant conditioning schedules in 1938',
                'correct_answer' => 'B',
                'explanation' => 'The 1956 MIT Symposium on Information Theory brought together key presentations by George Miller (short-term memory capacity), Noam Chomsky (generative linguistics), and Allen Newell & Herbert Simon (computer simulation of logic), marking the formal birth of cognitive psychology.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'cognitive-psychology',
                'part_num' => 1,
                'question' => 'In cognitive psychology, mental chronometry refers to the measurement of:',
                'choice_a' => 'Brain metabolism using radioactive glucose tracing',
                'choice_b' => 'The speed and duration of mental information processing using reaction times',
                'choice_c' => 'Age-related developmental changes in chronological intelligence',
                'choice_d' => 'The number of recall errors made during free-recall testing',
                'correct_answer' => 'B',
                'explanation' => 'Pioneered by Franciscus Donders, mental chronometry uses precise reaction-time measurements (such as choice reaction time minus simple reaction time) to infer the duration and sequence of discrete mental processes.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'cognitive-psychology',
                'part_num' => 1,
                'question' => 'A researcher compares reaction times when participants identify a letter presented in isolation versus within a real word. Participants respond significantly faster when the letter is in a word. What is this phenomenon called?',
                'choice_a' => 'The Stroop Effect',
                'choice_b' => 'The Word Superiority Effect',
                'choice_c' => 'The Cocktail Party Effect',
                'choice_d' => 'The Primacy Effect',
                'correct_answer' => 'B',
                'explanation' => 'The Word Superiority Effect (Reicher, 1969) demonstrates top-down perceptual processing: letters are recognized faster and more accurately when embedded in meaningful words than in isolation or nonsense strings.',
                'difficulty' => 'advanced',
                'is_scenario' => 1
            ],
            [
                'domain_slug' => 'cognitive-psychology',
                'part_num' => 2,
                'question' => 'According to Donald Broadbent’s Early Filter Model of selective attention, unattended sensory inputs are filtered out:',
                'choice_a' => 'After semantic analysis has extracted their complete meaning',
                'choice_b' => 'Before sensory registration can occur in the auditory cortex',
                'choice_c' => 'Immediately after the sensory buffer based purely on physical characteristics',
                'choice_d' => 'Only when cognitive load reaches maximum working memory capacity',
                'correct_answer' => 'C',
                'explanation' => 'Broadbent proposed an early bottleneck where sensory information enters a temporary buffer, and an all-or-none filter selects messages based solely on physical properties (such as pitch, location) before semantic processing occurs.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'cognitive-psychology',
                'part_num' => 2,
                'question' => 'In a dichotic listening study, a participant hearing their own name in the unattended ear notices it immediately. Which attentional model was specifically formulated to explain this finding?',
                'choice_a' => 'Broadbent’s Early Filter Model',
                'choice_b' => 'Treisman’s Attenuation Theory',
                'choice_c' => 'Atkinson-Shiffrin Multi-Store Model',
                'choice_d' => 'Gibson’s Ecological Theory',
                'correct_answer' => 'B',
                'explanation' => 'Treisman’s Attenuation Model replaced Broadbent’s rigid filter with an attenuator that dials down unattended stimuli. Stimuli with low threshold requirements (like one’s own name or danger signals) can still penetrate consciousness.',
                'difficulty' => 'intermediate',
                'is_scenario' => 1
            ],

            // General Psychology - Part 1
            [
                'domain_slug' => 'general-psychology',
                'part_num' => 1,
                'question' => 'Wilhelm Wundt is historically revered as the founder of modern experimental psychology primarily because he:',
                'choice_a' => 'Developed the first standardized intelligence test in Paris',
                'choice_b' => 'Established the first formal psychological laboratory in Leipzig in 1879',
                'choice_c' => 'Formulated the biological theory of evolution by natural selection',
                'choice_d' => 'Coined the term conditioned reflex during canine digestive studies',
                'correct_answer' => 'B',
                'explanation' => 'Wundt established the first dedicated psychological research laboratory at the University of Leipzig in Germany in 1879, marking the formal separation of psychology from philosophy.',
                'difficulty' => 'beginner',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'general-psychology',
                'part_num' => 1,
                'question' => 'Which psychological perspective argued that the human mind should be understood by analyzing its conscious experiences into basic structural elements via trained introspection?',
                'choice_a' => 'Functionalism',
                'choice_b' => 'Structuralism',
                'choice_c' => 'Behaviorism',
                'choice_d' => 'Gestalt Psychology',
                'correct_answer' => 'B',
                'explanation' => 'Structuralism, popularized in America by Edward Titchener (a student of Wundt), sought to deconstruct conscious experience into fundamental sensations and feelings through systematic introspection.',
                'difficulty' => 'beginner',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'general-psychology',
                'part_num' => 2,
                'question' => 'In an experimental investigation, the variable that the experimenter deliberately manipulates to observe its causal effect is designated the:',
                'choice_a' => 'Dependent Variable',
                'choice_b' => 'Independent Variable',
                'choice_c' => 'Confounding Variable',
                'choice_d' => 'Extraneous Variable',
                'correct_answer' => 'B',
                'explanation' => 'The independent variable (IV) is the experimental factor manipulated by the researcher to determine whether it produces changes in the dependent variable (DV).',
                'difficulty' => 'beginner',
                'is_scenario' => 0
            ],

            // Biological Psychology - Part 1
            [
                'domain_slug' => 'biological-psychology',
                'part_num' => 1,
                'question' => 'During an action potential, the rapid depolarization phase of the neuronal membrane is caused by the sudden influx of which ion?',
                'choice_a' => 'Potassium (K+)',
                'choice_b' => 'Sodium (Na+)',
                'choice_c' => 'Chloride (Cl-)',
                'choice_d' => 'Magnesium (Mg2+)',
                'correct_answer' => 'B',
                'explanation' => 'Upon reaching the excitation threshold (~ -55 mV), voltage-gated sodium channels open rapidly, allowing Na+ ions to rush into the intracellular fluid down both electrical and concentration gradients, producing depolarization.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'biological-psychology',
                'part_num' => 1,
                'question' => 'Which neurotransmitter is primarily implicated in muscle contraction at the neuromuscular junction, as well as memory encoding in Alzheimer’s pathology?',
                'choice_a' => 'Dopamine',
                'choice_b' => 'Acetylcholine (ACh)',
                'choice_c' => 'GABA',
                'choice_d' => 'Serotonin',
                'correct_answer' => 'B',
                'explanation' => 'Acetylcholine is critical for somatic motor stimulation at the neuromuscular junction, autonomic parasympathetic signaling, and hippocampal memory encoding. Degeneration of ACh-producing neurons is a hallmark of Alzheimer’s disease.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],

            // Abnormal Psychology - Part 1
            [
                'domain_slug' => 'abnormal-psychology',
                'part_num' => 1,
                'question' => 'When evaluating abnormal behavior, the standard 4 Ds of psychopathology include all of the following EXCEPT:',
                'choice_a' => 'Deviance',
                'choice_b' => 'Distress',
                'choice_c' => 'Dysfunction',
                'choice_d' => 'Delinquency',
                'correct_answer' => 'D',
                'explanation' => 'The four criteria commonly used to characterize psychological abnormality are Deviance, Distress, Dysfunction, and Danger. Delinquency is a legal/criminological construct rather than a core clinical criterion.',
                'difficulty' => 'beginner',
                'is_scenario' => 0
            ],
            [
                'domain_slug' => 'abnormal-psychology',
                'part_num' => 2,
                'question' => 'A 29-year-old client experiences recurrent, unexpected surges of intense fear with heart palpitations, trembling, dyspnea, and fear of impending doom, followed by a month of persistent worry about having another attack. What is the most accurate DSM-5 diagnosis?',
                'choice_a' => 'Generalized Anxiety Disorder',
                'choice_b' => 'Panic Disorder',
                'choice_c' => 'Agoraphobia',
                'choice_d' => 'Specific Phobia',
                'correct_answer' => 'B',
                'explanation' => 'Panic Disorder requires recurrent unexpected panic attacks accompanied by at least 1 month of persistent concern about additional attacks or maladaptive behavioral changes related to the attacks.',
                'difficulty' => 'intermediate',
                'is_scenario' => 1
            ],

            // Psychological Assessment - Part 1
            [
                'domain_slug' => 'psychological-assessment-testing',
                'part_num' => 1,
                'question' => 'If a test score has a standard z-score of +2.00, what would be the equivalent score on the standard Wechsler Full Scale IQ metric (Mean = 100, SD = 15)?',
                'choice_a' => '115',
                'choice_b' => '120',
                'choice_c' => '130',
                'choice_d' => '140',
                'correct_answer' => 'C',
                'explanation' => 'Standard score conversion formula: Standard Score = Mean + (z * SD). For Wechsler IQ: 100 + (2.00 * 15) = 100 + 30 = 130.',
                'difficulty' => 'intermediate',
                'is_scenario' => 0
            ],

            // Ethics - Part 1
            [
                'domain_slug' => 'ethics-professional-practice',
                'part_num' => 1,
                'question' => 'Under psychological codes of ethics, which core principle obligates professionals to strive to benefit those with whom they work and take care to do no harm?',
                'choice_a' => 'Integrity',
                'choice_b' => 'Beneficence and Nonmaleficence',
                'choice_c' => 'Justice',
                'choice_d' => 'Fidelity and Responsibility',
                'correct_answer' => 'B',
                'explanation' => 'Principle A of the APA Ethics Code (and mirrored in the PAP Code of Ethics) is Beneficence and Nonmaleficence, meaning psychologists safeguard the welfare and rights of those with whom they interact professionally.',
                'difficulty' => 'beginner',
                'is_scenario' => 0
            ]
        ];

        // Ensure we also generate base question sets for all parts that don't have explicit questions above
        $partSelectStmt = $db->query("
            SELECT p.id as part_id, p.part_number, p.title, d.slug as domain_slug
            FROM psychology_parts p
            JOIN psychology_domains d ON p.domain_id = d.id
        ");
        $allParts = $partSelectStmt->fetchAll(PDO::FETCH_ASSOC);

        $insertQStmt = $db->prepare("
            INSERT INTO learning_questions (
                part_id, question, choice_a, choice_b, choice_c, choice_d,
                correct_answer, explanation, difficulty, is_scenario, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        // Insert specific questions
        foreach ($questions as $q) {
            $partStmt = $db->prepare("
                SELECT p.id FROM psychology_parts p 
                JOIN psychology_domains d ON p.domain_id = d.id 
                WHERE d.slug = ? AND p.part_number = ?
            ");
            $partStmt->execute([$q['domain_slug'], $q['part_num']]);
            $partId = $partStmt->fetchColumn();

            if ($partId) {
                $insertQStmt->execute([
                    $partId,
                    $q['question'],
                    $q['choice_a'],
                    $q['choice_b'],
                    $q['choice_c'],
                    $q['choice_d'],
                    $q['correct_answer'],
                    $q['explanation'],
                    $q['difficulty'],
                    $q['is_scenario']
                ]);
            }
        }

        // Generate baseline standard assessment questions for parts lacking questions
        foreach ($allParts as $p) {
            $checkQ = $db->prepare("SELECT COUNT(*) FROM learning_questions WHERE part_id = ?");
            $checkQ->execute([$p['part_id']]);
            $existing = (int)$checkQ->fetchColumn();

            if ($existing < 3) {
                // Seed 3 standard review questions tailored to this part
                $title = $p['title'];
                $partNum = $p['part_number'];

                $sampleQuestions = [
                    [
                        'q' => "What is the primary conceptual focus of {$title} in modern psychology?",
                        'a' => "Analyzing systematic empirical evidence and theoretical models related to {$title}.",
                        'b' => "Dismissing previous literature in favor of subjective clinical impressions.",
                        'c' => "Limiting psychological inquiries strictly to non-measurable constructs.",
                        'd' => "Replacing diagnostic taxonomies with unverified anecdotal claims.",
                        'correct' => 'A',
                        'exp' => "{$title} emphasizes empirical scientific methodologies, validated measurements, and theoretical frameworks.",
                        'diff' => ($partNum >= 9 ? 'board_exam' : ($partNum >= 6 ? 'advanced' : 'intermediate')),
                        'scen' => 0
                    ],
                    [
                        'q' => "In clinical and experimental practice, how do psychologists validate findings regarding {$title}?",
                        'a' => "Through standardized assessment tools, controlled observation, and reproducible trials.",
                        'b' => "By relying solely on single-case unchecked self-reports.",
                        'c' => "Through commercial popularity rather than peer-reviewed scrutiny.",
                        'd' => "By assuming all constructs remain identical across unrelated cultures.",
                        'correct' => 'A',
                        'exp' => "Validity in psychological science demands standardized psychometric instrumentation, peer-reviewed evaluation, and replication.",
                        'diff' => ($partNum >= 9 ? 'board_exam' : 'intermediate'),
                        'scen' => 1
                    ],
                    [
                        'q' => "Which ethical or psychometric consideration is crucial when applying principles of {$title}?",
                        'a' => "Ensuring construct validity, cultural fairness, and client welfare protection.",
                        'b' => "Disregarding informed consent whenever testing speed is prioritized.",
                        'c' => "Treating all subscales as having zero standard error of measurement.",
                        'd' => "Publishing proprietary test items openly without user qualification.",
                        'correct' => 'A',
                        'exp' => "Professional licensure standards mandate strict adherence to psychometric integrity, cultural fairness, and ethical responsibilities.",
                        'diff' => 'board_exam',
                        'scen' => 0
                    ]
                ];

                foreach ($sampleQuestions as $sq) {
                    $insertQStmt->execute([
                        $p['part_id'],
                        $sq['q'],
                        $sq['a'],
                        $sq['b'],
                        $sq['c'],
                        $sq['d'],
                        $sq['correct'],
                        $sq['exp'],
                        $sq['diff'],
                        $sq['scen']
                    ]);
                }
            }
        }
    }

    private static function seedAchievements(PDO $db): void {
        $curriculumAchievements = [
            [
                'name' => 'First Step',
                'description' => 'Complete your first Psychology learning part.',
                'icon' => '👣',
                'category' => 'academic',
                'condition_type' => 'curriculum_parts_completed',
                'condition_value' => 1,
                'xp_reward' => 50,
                'point_reward' => 25,
                'rarity' => 'common'
            ],
            [
                'name' => 'Foundations Complete',
                'description' => 'Complete your first entire Psychology domain (all 10 parts).',
                'icon' => '🏛️',
                'category' => 'academic',
                'condition_type' => 'curriculum_domains_completed',
                'condition_value' => 1,
                'xp_reward' => 200,
                'point_reward' => 100,
                'rarity' => 'rare'
            ],
            [
                'name' => 'Cognitive Scholar',
                'description' => 'Attain mastery over the Cognitive Psychology domain.',
                'icon' => '💡',
                'category' => 'academic',
                'condition_type' => 'curriculum_domain_specific',
                'condition_value' => 3, // Cognitive domain ID
                'xp_reward' => 250,
                'point_reward' => 150,
                'rarity' => 'epic'
            ],
            [
                'name' => 'Full Reviewer',
                'description' => 'Complete 5 full Psychology domains in your study journey.',
                'icon' => '📚',
                'category' => 'academic',
                'condition_type' => 'curriculum_domains_completed',
                'condition_value' => 5,
                'xp_reward' => 400,
                'point_reward' => 250,
                'rarity' => 'epic'
            ],
            [
                'name' => 'Psychology Master',
                'description' => 'Complete all 12 Psychology domains in the StudyQuest board reviewer.',
                'icon' => '🏆',
                'category' => 'academic',
                'condition_type' => 'curriculum_domains_completed',
                'condition_value' => 12,
                'xp_reward' => 1000,
                'point_reward' => 500,
                'rarity' => 'legendary'
            ]
        ];

        $stmt = $db->prepare("
            INSERT INTO achievements (
                name, description, icon, category, condition_type, condition_value, 
                xp_reward, point_reward, rarity
            ) 
            SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?
            WHERE NOT EXISTS (SELECT 1 FROM achievements WHERE name = ?)
        ");

        foreach ($curriculumAchievements as $ach) {
            $stmt->execute([
                $ach['name'],
                $ach['description'],
                $ach['icon'],
                $ach['category'],
                $ach['condition_type'],
                $ach['condition_value'],
                $ach['xp_reward'],
                $ach['point_reward'],
                $ach['rarity'],
                $ach['name']
            ]);
        }
    }
}
