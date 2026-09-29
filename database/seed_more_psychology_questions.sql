USE studyquest;

INSERT INTO questions 
(category_id, question, choice_a, choice_b, choice_c, choice_d, correct_answer, difficulty, recommended_level_min, recommended_level_max, explanation, source_reference, status)
VALUES
-- General Psychology (Cat 1)
(1, 'Which early school of psychology, led by Edward Titchener, aimed to analyze the basic elements of conscious mental experience through introspection?',
 'Functionalism', 'Structuralism', 'Psychoanalysis', 'Gestalt psychology',
 'b', 'beginner', 1, 5,
 'Structuralism, championed by Edward Titchener based on Wundt work, sought to dissect conscious experience into elemental sensations and feelings.', 'Schultz & Schultz, History of Modern Psychology', 'approved'),

(1, 'William James functionalism was heavily influenced by which scientific theory?',
 'Darwin theory of evolution by natural selection', 'Newton laws of universal gravitation', 'Mendel laws of genetic inheritance', 'Dalton atomic theory',
 'a', 'intermediate', 6, 15,
 'William James founded functionalism on Darwin evolutionary theory, proposing that consciousness evolved because it serves an adaptive function.', 'James, The Principles of Psychology', 'approved'),

-- Biological Psychology (Cat 2)
(2, 'The electrical signal that travels down the axon of a neuron following the influx of sodium ions is known as the:',
 'Graded potential', 'Action potential', 'Resting membrane potential', 'Synaptic vesicle delay',
 'b', 'beginner', 1, 5,
 'The action potential is the rapid, all-or-none depolarizing electrochemical nerve impulse propagating along the axon.', 'Bear, Connors, & Paradiso, Neuroscience: Exploring the Brain', 'approved'),

(2, 'Which endocrine gland, situated at the base of the brain, is frequently referred to as the "master gland" because it directs hormonal secretions of other glands?',
 'Adrenal gland', 'Thyroid gland', 'Pituitary gland', 'Pancreas',
 'c', 'beginner', 1, 5,
 'The pituitary gland releases trophic hormones that regulate other endocrine glands throughout the body under hypothalamic control.', 'Pinel, Biopsychology', 'approved'),

(2, 'A lesion specifically destroying the suprachiasmatic nucleus (SCN) of the anterior hypothalamus would severely disrupt which biological rhythm?',
 'Ultradian digestive rhythms', 'Infradian reproductive cycles', 'Circadian sleep-wake cycles', 'Respiratory tidal volumes',
 'c', 'advanced', 16, 25,
 'The suprachiasmatic nucleus (SCN) functions as the master biological circadian pacemaker in mammals, regulating melatonin secretion and sleep cycles.', 'Moore & Eichler (1972); Carlson, Physiology of Behavior', 'approved'),

-- Cognitive Psychology (Cat 3)
(3, 'Mental shortcuts or "rules of thumb" used to make swift judgments under uncertainty, though occasionally prone to systematic errors, are termed:',
 'Algorithms', 'Heuristics', 'Syllogisms', 'Phonemes',
 'b', 'beginner', 1, 5,
 'Heuristics are cognitive rules of thumb that simplify complex problem-solving and decision-making under uncertainty.', 'Kahneman & Tversky (1974), Judgment under Uncertainty: Heuristics and Biases', 'approved'),

(3, 'When estimating the frequency of an event based on how readily recent or vivid examples come to mind, an individual is employing the:',
 'Representativeness heuristic', 'Availability heuristic', 'Anchoring and adjustment heuristic', 'Simulation heuristic',
 'b', 'intermediate', 6, 15,
 'The availability heuristic judges probability based on the cognitive ease with which instances can be brought to mind.', 'Tversky & Kahneman (1973)', 'approved'),

(3, 'The inability to envision that a familiar object can be utilized in novel ways beyond its customary design is known as:',
 'Mental set', 'Functional fixedness', 'Confirmation bias', 'Cognitive dissonance',
 'b', 'intermediate', 6, 15,
 'Functional fixedness is a cognitive block against using an object in a new way that is required to solve a problem.', 'Duncker (1945); Sternberg, Cognitive Psychology', 'approved'),

-- Developmental Psychology (Cat 4)
(4, 'According to Lev Vygotsky, the difference between what a learner can achieve independently versus what can be accomplished with skilled guidance is termed the:',
 'Stage of concrete operations', 'Zone of Proximal Development (ZPD)', 'Sensorimotor plateau', 'Epigenetic landscape',
 'b', 'beginner', 1, 5,
 'Vygotsky Zone of Proximal Development (ZPD) defines the range of tasks a child can master with scaffolding from more capable peers or adults.', 'Vygotsky, Mind in Society', 'approved'),

(4, 'According to Lawrence Kohlberg stages of moral development, individuals in the postconventional level base their ethical judgments on:',
 'Fear of punishment and obedience to authority', 'Social norms, peer conformity, and upholding law and order', 'Universal ethical principles and human rights transcending laws', 'Egoistic reciprocity and personal gain',
 'c', 'intermediate', 6, 15,
 'Postconventional morality is characterized by autonomous moral principles based on universal justice, human rights, and social contracts.', 'Kohlberg, The Philosophy of Moral Development', 'approved'),

(4, 'In Mary Ainsworth Strange Situation protocol, an infant who exhibits confused, contradictory reactions upon mother return (e.g., freezing, approaching while looking away) is classified as:',
 'Secure', 'Insecure-Avoidant', 'Insecure-Resistant', 'Disorganized/Disoriented',
 'd', 'advanced', 16, 25,
 'Disorganized/disoriented attachment, identified by Main and Solomon, features contradictory, apprehensive behaviors reflecting fear of the caregiver.', 'Main & Solomon (1990); Bowlby, Attachment and Loss', 'approved'),

-- Social Psychology (Cat 5)
(5, 'In Solomon Asch famous conformity studies, what percentage of participants conformed to the group unanimous incorrect line judgment on at least one critical trial?',
 'Approximately 10%', 'Approximately 35%', 'Approximately 75%', '100%',
 'c', 'intermediate', 6, 15,
 'Asch found that about 75% of participants conformed at least once to the majority erroneous judgment, showing the power of normative social influence.', 'Asch (1951), Effects of group pressure upon modification and distortion of judgments', 'approved'),

(5, 'The phenomenon in which bystanders are less likely to intervene in an emergency as the number of witnesses increases is primarily explained by:',
 'Pluralistic ignorance and diffusion of responsibility', 'Reciprocal altruism and groupthink', 'Deindividuation and cognitive dissonance', 'In-group favoritism and out-group homogeneity',
 'a', 'intermediate', 6, 15,
 'The bystander effect occurs through diffusion of responsibility (each assumes another will act) and pluralistic ignorance (interpreting inaction as safety).', 'Darley & Latané (1968), Bystander intervention in emergencies', 'approved'),

(5, 'The Stanford Prison Experiment, conducted by Philip Zimbardo in 1971, demonstrated how powerful which factor is in dictating human behavior?',
 'Genetic predisposition toward aggression', 'Social roles and situational context', 'Parental attachment styles', 'Classical operant extinction',
 'b', 'intermediate', 6, 15,
 'Zimbardo experiment showed that social roles and institutional contexts can dramatically overpower individual morality.', 'Haney, Banks, & Zimbardo (1973)', 'approved'),

-- Personality Psychology & Theories (Cat 6 & 16)
(6, 'Gordon Allport categorized personality traits into which hierarchical framework?',
 'Surface traits and Source traits', 'Cardinal, Central, and Secondary traits', 'Extraversion, Neuroticism, and Psychoticism', 'Id, Ego, and Superego dispositions',
 'b', 'beginner', 1, 5,
 'Allport classified personality traits into pervasive Cardinal traits, common Central building blocks, and peripheral Secondary traits.', 'Allport, Pattern and Growth in Personality', 'approved'),

(16, 'In Freudian psychoanalysis, which component of personality operates strictly according to the "pleasure principle," demanding immediate gratification?',
 'The Id', 'The Ego', 'The Superego', 'The Ego Ideal',
 'a', 'beginner', 1, 5,
 'The Id is the primitive, unconscious reservoir of psychic energy and instincts driven entirely by the pleasure principle.', 'Freud, The Ego and the Id', 'approved'),

(16, 'According to Alfred Adler individual psychology, what is the primary driving force in human personality development?',
 'Sexual libido and pleasure seeking', 'Striving for superiority and overcoming feelings of inferiority', 'Collective archetypes and synchronicity', 'Observational imitation and self-efficacy',
 'b', 'intermediate', 6, 15,
 'Adler posited that individuals are fundamentally motivated by the drive to overcome perceived weaknesses and strive for competence and superiority.', 'Adler, The Practice and Theory of Individual Psychology', 'approved'),

-- Abnormal & Clinical Psychology (Cat 7 & 8)
(7, 'Which personality disorder is characterized by a pervasive pattern of instability in interpersonal relationships, self-image, and affects, alongside marked impulsivity and frantic efforts to avoid abandonment?',
 'Histrionic Personality Disorder', 'Borderline Personality Disorder', 'Avoidant Personality Disorder', 'Dependent Personality Disorder',
 'b', 'intermediate', 6, 15,
 'Borderline Personality Disorder (BPD) features affective lability, fears of abandonment, identity disturbance, chronic emptiness, and impulsivity.', 'DSM-5-TR, Cluster B Personality Disorders', 'approved'),

(7, 'A patient presenting with auditory hallucinations, delusions of reference, and affective flattening without significant depressive or manic episodes for 7 months satisfies the diagnostic criteria for:',
 'Brief Psychotic Disorder', 'Schizophreniform Disorder', 'Schizophrenia', 'Schizotypal Personality Disorder',
 'c', 'advanced', 16, 25,
 'Schizophrenia requires active-phase psychotic symptoms accompanied by social/occupational decline lasting for at least 6 months continuous duration.', 'DSM-5-TR, Schizophrenia Spectrum and Other Psychotic Disorders', 'approved'),

(8, 'In Dialectical Behavior Therapy (DBT), developed by Marsha Linehan for Borderline Personality Disorder, which four core skill modules are taught?',
 'Free association, dream analysis, hypnosis, and transference', 'Mindfulness, distress tolerance, emotion regulation, and interpersonal effectiveness', 'Systematic desensitization, flooding, biofeedback, and progressive relaxation', 'Cognitive defusion, values clarification, acceptance, and somatic experiencing',
 'b', 'advanced', 16, 25,
 'DBT synthesizes CBT with Zen dialectics across four modules: Mindfulness, Distress Tolerance, Emotion Regulation, and Interpersonal Effectiveness.', 'Linehan, Cognitive-Behavioral Treatment of Borderline Personality Disorder', 'approved'),

-- Psychological Assessment & Testing (Cat 11 & 14)
(11, 'Which widely used projective test consists of 10 standardized inkblot stimulus cards developed to assess underlying personality dynamics and perceptual style?',
 'Thematic Apperception Test (TAT)', 'Rorschach Inkblot Test', 'Rotter Incomplete Sentences Blank', 'House-Tree-Person Test',
 'b', 'beginner', 1, 5,
 'Hermann Rorschach designed the 10 inkblot plates (5 black-and-white, 2 black-and-red, 3 polychromatic) in 1921.', 'Rorschach, Psychodiagnostik; Exner Comprehensive System', 'approved'),

(14, 'A psychometrician computes item difficulty (p-value) during test construction. An item with a p-value of p = .85 indicates that:',
 '85% of test-takers answered the item incorrectly (very hard item)', '85% of test-takers answered the item correctly (relatively easy item)', 'The item correlates at r = .85 with the total test score', 'The item discriminates poorly between high and low scorers',
 'b', 'intermediate', 6, 15,
 'In item analysis, item difficulty p represents the proportion answering correctly: higher p values indicate easier items.', 'Cohen & Swerdlik, Psychological Testing and Assessment', 'approved'),

(14, 'What is the standard score mean and standard deviation for Wechsler Adult Intelligence Scale (WAIS-IV) subtest scaled scores?',
 'Mean = 100, SD = 15', 'Mean = 50, SD = 10', 'Mean = 10, SD = 3', 'Mean = 0, SD = 1',
 'c', 'advanced', 16, 25,
 'WAIS-IV individual subtest scaled scores have a standardized mean of 10 and SD of 3; composite index and IQ scores have mean of 100 and SD of 15.', 'Wechsler, WAIS-IV Administration and Scoring Manual', 'approved'),

-- Research Methods & Statistics (Cat 12 & 13)
(12, 'When neither the study participants nor the experimenters administering the intervention know which condition participants receive, the study is using a:',
 'Single-blind control', 'Double-blind procedure', 'Quasi-experimental control', 'Cross-sectional baseline',
 'b', 'beginner', 1, 5,
 'A double-blind procedure prevents both participant placebo expectancy effects and researcher experimenter bias.', 'Gravetter & Forzano, Research Methods for the Behavioral Sciences', 'approved'),

(13, 'Which measure of central tendency is the most appropriate and robust to report when summarizing a severely skewed distribution of income data?',
 'Arithmetic mean', 'Median', 'Mode', 'Geometric standard variance',
 'b', 'beginner', 1, 5,
 'The median is resistant to extreme outliers and is the preferred measure of central tendency for skewed distributions.', 'Howell, Statistical Methods for Psychology', 'approved'),

(13, 'A Pearson correlation coefficient of r = -0.88 between study hours and academic anxiety indicates:',
 'A weak inverse relationship between the two variables', 'A strong negative linear relationship where higher study hours associate with lower anxiety', 'That studying directly causes an immediate reduction in anxiety symptoms', 'A measurement artifact resulting from restricted range',
 'b', 'intermediate', 6, 15,
 'A correlation of -0.88 reflects a strong inverse linear relationship; correlation signifies association, not causation.', 'Gravetter & Wallnau, Statistics for the Behavioral Sciences', 'approved'),

(13, 'A researcher performs a one-way ANOVA with three groups and finds F(2, 57) = 5.82, p < .01. To determine precisely which group means differ significantly from each other, the researcher must conduct:',
 'A Chi-Square goodness-of-fit test', 'Post-hoc pairwise multiple comparisons (e.g., Tukey HSD)', 'A factor rotation analysis', 'Multiple bivariate regressions',
 'b', 'advanced', 16, 25,
 'An omnibus ANOVA indicates at least one group differs; post-hoc tests (Tukey HSD, Bonferroni) control family-wise error to identify specific pairs.', 'Field, Discovering Statistics Using IBM SPSS Statistics', 'approved'),

-- Learning & Behavior (Cat 17)
(17, 'The gradual weakening and eventual disappearance of a conditioned response when the conditioned stimulus is repeatedly presented without the unconditioned stimulus is called:',
 'Spontaneous recovery', 'Extinction', 'Stimulus generalization', 'Higher-order conditioning',
 'b', 'beginner', 1, 5,
 'Extinction occurs when the CS is uncoupled from the UCS over repeated trials, leading to cessation of the CR.', 'Pavlov, Conditioned Reflexes', 'approved'),

(17, 'A child tantrums in a supermarket, and the parent buys a candy bar to stop the crying immediately. From the parent perspective, buying candy is maintained by:',
 'Positive reinforcement', 'Negative reinforcement', 'Positive punishment', 'Negative punishment',
 'b', 'intermediate', 6, 15,
 'The parent behavior removes the aversive crying stimulus, increasing future likelihood of giving candy—a textbook example of negative reinforcement.', 'Skinner, Science and Human Behavior', 'approved'),

-- Sensation, Perception & Health Psychology (Cat 19 & 20)
(19, 'The minimum amount of physical energy necessary for a sensory system to detect a stimulus at least 50% of the time is defined as the:',
 'Difference threshold', 'Absolute threshold', 'Just Noticeable Difference (JND)', 'Weber constant',
 'b', 'beginner', 1, 5,
 'The absolute threshold is the minimum stimulus intensity detectable 50% of the time according to psychophysical standards.', 'Fechner, Elemente der Psychophysik; Goldstein, Sensation and Perception', 'approved'),

(20, 'According to Hans Selye General Adaptation Syndrome (GAS), what is the correct chronological sequence of physiological stress response stages?',
 'Resistance -> Alarm -> Exhaustion', 'Alarm reaction -> Resistance -> Exhaustion', 'Exhaustion -> Alarm -> Resistance', 'Appraisal -> Coping -> Adaptation',
 'b', 'intermediate', 6, 15,
 'Selye GAS outlines three sequential stages: Alarm reaction (fight-or-flight), Resistance (sustained endocrine adaptation), and Exhaustion (depletion).', 'Selye, The Stress of Life', 'approved'),

-- Industrial/Organizational Psychology (Cat 10)
(10, 'The phenomenon wherein workers improve their productivity simply because they are aware they are being observed by researchers is known as the:',
 'Pygmalion effect', 'Hawthorne effect', 'Barnum effect', 'Flynn effect',
 'b', 'beginner', 1, 5,
 'The Hawthorne effect refers to behavioral changes resulting from the novelty of being observed and receiving special attention.', 'Mayo (1933); Roethlisberger & Dickson', 'approved'),

(10, 'In personnel selection, if a test has high criterion validity and high selection ratio with low base rate, the Taylor-Russell tables are utilized to determine the test:',
 'Incremental utility and hit rate improvement', 'Face validity index', 'Factorial invariance', 'Standardized internal consistency',
 'a', 'board_exam', 26, 99,
 'Taylor-Russell tables assess the proportion of successful employees selected (utility gain) given validities, selection ratios, and base rates.', 'Cascio & Aguinis, Applied Psychology in Human Resource Management', 'approved'),

-- Ethics & Professional Practice (Cat 15)
(15, 'When a psychologist provides forensic expert testimony in a courtroom, what is the psychologist primary role and duty?',
 'To advocate zealously for the retaining party legal victory', 'To maintain strict client confidentiality regardless of court orders', 'To assist the trier of fact through objective, impartial scientific testimony within competence limits', 'To perform character evaluations of opposing counsel',
 'c', 'board_exam', 26, 99,
 'Specialty Guidelines for Forensic Psychology mandate that forensic psychologists remain impartial, objective educators to the court rather than legal advocates.', 'APA Specialty Guidelines for Forensic Psychology (2013)', 'approved');
