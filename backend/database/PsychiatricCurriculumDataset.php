<?php

class PsychiatricCurriculumDataset {
    public static function get(): array {
        return self::getTwelveCompiledDomains();
    }

    public static function getTwelveCompiledDomains(): array {
        // Chapters 1 to 6
        $ch1 = self::getNeurodevelopmentalDomain();
        $ch2 = self::getSchizophreniaDomain();
        $ch3 = self::getBipolarDomain();
        $ch4 = self::getDepressiveDomain();
        $ch5 = self::getAnxietyDomain();
        $ch6 = self::getOCDDomain();

        // Raw components for Chapters 7 to 12
        $rawTrauma = self::getTraumaDomain();
        $rawDissociative = self::getDissociativeDomain();
        $rawSomatic = self::getSomaticDomain();
        $rawEating = self::getEatingDomain();
        $rawElimination = self::getEliminationDomain();
        $rawSleep = self::getSleepDomain();
        $rawSexual = self::getSexualDomain();
        $rawGender = self::getGenderDomain();
        $rawDisruptive = self::getDisruptiveDomain();
        $rawSubstance = self::getSubstanceDomain();
        $rawNCD = self::getNeurocognitiveDomain();
        $rawPersonality = self::getPersonalityDomain();
        $rawParaphilic = self::getParaphilicDomain();

        // 7. Trauma-, Stressor-Related and Dissociative Disorders
        $ch7Parts = [
            $rawTrauma['parts'][1],
            $rawTrauma['parts'][2],
            $rawTrauma['parts'][3],
            $rawTrauma['parts'][4],
            $rawTrauma['parts'][5],
            $rawDissociative['parts'][2],
            $rawDissociative['parts'][3],
            $rawDissociative['parts'][4],
            $rawTrauma['parts'][8],
            $rawTrauma['parts'][10],
        ];
        $ch7 = self::assembleDomain(
            'Trauma-, Stressor-Related and Dissociative Disorders',
            'trauma-dissociative-disorders',
            'Clinical review of PTSD, acute stress, adjustment disorders, and dissociative pathologies under DSM-5 criteria.',
            [
                'Trauma Exposure (Criterion A) & The Stress Continuum',
                'Posttraumatic Stress Disorder (PTSD)',
                'Acute Stress Disorder',
                'Adjustment Disorders',
                'Attachment Pathology (RAD & DSED)',
                'Dissociative Identity Disorder (DID)',
                'Dissociative Amnesia & Dissociative Fugue',
                'Depersonalization / Derealization Disorder',
                'Differential Diagnosis: Trauma & Dissociation',
                'Chapter 7 Mastery & Board Review'
            ],
            $ch7Parts
        );

        // 8. Somatic Symptom and Related Disorders
        $ch8 = $rawSomatic;
        $ch8['name'] = 'Somatic Symptom and Related Disorders';
        $ch8['slug'] = 'somatic-symptom-disorders';

        // 9. Feeding, Eating, and Elimination Disorders
        $ch9Parts = [
            $rawEating['parts'][1],
            $rawEating['parts'][2],
            $rawEating['parts'][3],
            $rawEating['parts'][4],
            $rawEating['parts'][5],
            $rawEating['parts'][6],
            $rawElimination['parts'][2],
            $rawElimination['parts'][4],
            $rawEating['parts'][8],
            $rawEating['parts'][10],
        ];
        $ch9 = self::assembleDomain(
            'Feeding, Eating, and Elimination Disorders',
            'feeding-eating-elimination-disorders',
            'Comprehensive review of anorexia, bulimia, BED, feeding disorders, and pediatric elimination milestones.',
            [
                'Eating Disorder Spectrum & Pediatric Feeding',
                'Anorexia Nervosa & BMI Severity',
                'Anorexia Subtypes: Restricting vs Binge/Purge',
                'Bulimia Nervosa & Compensatory Cycles',
                'Binge-Eating Disorder (BED)',
                'Medical Complications & Refeeding Syndrome',
                'Enuresis: Nocturnal & Diurnal Diagnostic Rules',
                'Encopresis: Incontinence & Constipation Dynamics',
                'Differential Diagnosis: Nutritional & Excretory Disorders',
                'Chapter 9 Mastery & Board Review'
            ],
            $ch9Parts
        );

        // 10. Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria
        $ch10Parts = [
            $rawSleep['parts'][1],
            $rawSleep['parts'][2],
            $rawSleep['parts'][3],
            $rawSleep['parts'][6],
            $rawSexual['parts'][1],
            $rawSexual['parts'][2],
            $rawSexual['parts'][4],
            $rawGender['parts'][2],
            $rawGender['parts'][3],
            $rawSleep['parts'][10],
        ];
        $ch10 = self::assembleDomain(
            'Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria',
            'sleep-sexual-gender-disorders',
            'Diagnostic formulations covering sleep-wake architecture, sexual dysfunction criteria, and gender dysphoria.',
            [
                'Sleep Architecture & Diagnostic Taxonomy',
                'Insomnia Disorder & Chronicity Thresholds',
                'Narcolepsy, Cataplexy & Hypersomnolence',
                'Parasomnias & Sleep-Related Movements',
                'Human Sexual Response Cycle (6-Month Rule)',
                'Male Sexual Dysfunctions: Erectile & Ejaculatory',
                'Female Sexual Dysfunctions: Arousal & Pelvic Pain',
                'Gender Dysphoria in Children',
                'Gender Dysphoria in Adolescents and Adults',
                'Chapter 10 Mastery & Board Review'
            ],
            $ch10Parts
        );

        // 11. Substance-Related, Addictive, and Disruptive Disorders
        $ch11Parts = [
            $rawSubstance['parts'][1],
            $rawSubstance['parts'][2],
            $rawSubstance['parts'][3],
            $rawSubstance['parts'][5],
            $rawSubstance['parts'][6],
            $rawSubstance['parts'][8],
            $rawDisruptive['parts'][2],
            $rawDisruptive['parts'][4],
            $rawDisruptive['parts'][7],
            $rawSubstance['parts'][10],
        ];
        $ch11 = self::assembleDomain(
            'Substance-Related, Addictive, and Disruptive Disorders',
            'substance-disruptive-disorders',
            'Addiction neuroscience, 11 SUD criteria, behavioral gambling addiction, and externalizing disruptive conduct pathologies.',
            [
                'Substance Use Disorder (11 Core DSM-5 Criteria)',
                'Neurobiology of Addiction: Dopamine & Tolerance',
                'Alcohol-Related Pathology & Delirium Tremens',
                'Opioid Epidemic & Respiratory Depression',
                'Stimulant & Cannabis-Related Pathology',
                'Gambling Disorder: Behavioral Addiction',
                'Oppositional Defiant Disorder (ODD)',
                'Conduct Disorder (CD) & Prosocial Deficits',
                'Impulse-Control: Pyromania & Kleptomania',
                'Chapter 11 Mastery & Board Review'
            ],
            $ch11Parts
        );

        // 12. Neurocognitive, Personality, and Paraphilic Disorders
        $ch12Parts = [
            $rawNCD['parts'][2],
            $rawNCD['parts'][3],
            $rawNCD['parts'][4],
            $rawPersonality['parts'][1],
            $rawPersonality['parts'][2],
            $rawPersonality['parts'][4],
            $rawPersonality['parts'][7],
            $rawParaphilic['parts'][1],
            $rawParaphilic['parts'][2],
            $rawPersonality['parts'][10],
        ];
        $ch12 = self::assembleDomain(
            'Neurocognitive, Personality, and Paraphilic Disorders',
            'neurocognitive-personality-paraphilic-disorders',
            'Synthesis of neurocognitive decline, Cluster A/B/C personality dynamics, and forensic/clinical paraphilic distinctions.',
            [
                'Delirium: Acute Attentional Impairment',
                'Major vs. Mild Neurocognitive Disorder (NCD)',
                'Alzheimer’s, Vascular & Lewy Body NCD',
                'General Diagnostic Criteria for Personality Disorders',
                'Cluster A (Odd/Eccentric): Character Pathology',
                'Cluster B (Dramatic/Erratic): Borderline & Antisocial',
                'Cluster C (Anxious/Fearful): Avoidant, Dependent & OCPD',
                'Paraphilia vs. Paraphilic Disorder Distinction',
                'Core Paraphilic Presentations & Forensic Ethics',
                'Chapter 12 Climax Mastery & Licensure Board Review'
            ],
            $ch12Parts
        );

        return [
            1 => $ch1,
            2 => $ch2,
            3 => $ch3,
            4 => $ch4,
            5 => $ch5,
            6 => $ch6,
            7 => $ch7,
            8 => $ch8,
            9 => $ch9,
            10 => $ch10,
            11 => $ch11,
            12 => $ch12,
        ];
    }

    private static function assembleDomain(string $name, string $slug, string $desc, array $topics, array $partsList): array {
        $parts = [];
        foreach ($partsList as $idx => $p) {
            $pNum = $idx + 1;
            $p['topic_order'] = min(count($topics), $pNum);
            if ($pNum === 10) {
                $p['part_type'] = 'mastery';
            }
            $parts[$pNum] = $p;
        }
        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $desc,
            'topics' => $topics,
            'parts' => $parts
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // DOMAIN 1: NEURODEVELOPMENTAL DISORDERS
    // ─────────────────────────────────────────────────────────────
    private static function getNeurodevelopmentalDomain(): array {
        return [
            'name' => 'Neurodevelopmental Disorders',
            'topics' => [
                'Intellectual Disabilities & Global Developmental Delay',
                'Communication Disorders',
                'Autism Spectrum Disorder (ASD)',
                'Attention-Deficit/Hyperactivity Disorder (ADHD)',
                'Specific Learning Disorder',
                'Motor & Tic Disorders',
                'Neurodevelopmental Course & Etiology',
                'Differential Diagnosis & Comorbidity',
                'Educational Case Scenarios',
                'Domain Mastery & Board Review',
            ],
            'parts' => [
                1 => [
                    'title' => 'Diagnostic Framework & Intellectual Disabilities',
                    'slug' => 'diagnostic-framework-intellectual-disabilities',
                    'description' => 'DSM-5 developmental timing criteria, adaptive functioning, and intellectual disability severity specifiers.',
                    'part_type' => 'learn',
                    'overview' => 'Neurodevelopmental disorders are a group of conditions with onset in the developmental period, typically manifesting before grade school. Under the DSM-5 framework, Intellectual Disability (Intellectual Developmental Disorder) is characterized by deficits in general mental abilities (Criterion A) and impairment in everyday adaptive functioning across conceptual, social, and practical domains (Criterion B), with onset during the developmental period (Criterion C). Crucially, DSM-5 shifts emphasis from strict IQ cutoff scores to comprehensive clinical assessment of adaptive functioning to determine severity (Mild, Moderate, Severe, Profound).',
                    'key_concepts' => [
                        ['title' => 'Adaptive Functioning Priority', 'icon' => '⚖️', 'definition' => 'Severity is determined by adaptive functioning across conceptual, social, and practical domains rather than IQ scores alone.'],
                        ['title' => 'Developmental Period Onset', 'icon' => '🌱', 'definition' => 'Symptoms must emerge during the developmental period; adult-onset cognitive decline is classified under Neurocognitive Disorders.'],
                        ['title' => 'Global Developmental Delay', 'icon' => '⏳', 'definition' => 'Reserved for individuals under age 5 when clinical severity cannot be reliably assessed via standardized testing.']
                    ],
                    'important_terms' => [
                        ['term' => 'Conceptual Domain', 'definition' => 'Competence in memory, language, reading, writing, math reasoning, and practical problem solving.'],
                        ['term' => 'Social Domain', 'definition' => 'Awareness of others\' thoughts and feelings, empathy, interpersonal communication skills, and social judgment.'],
                        ['term' => 'Practical Domain', 'definition' => 'Learning and self-management across personal care, job responsibilities, money management, and recreation.']
                    ],
                    'diagnostic_criteria' => 'Criterion A: Deficits in intellectual functions (reasoning, problem-solving, planning, abstract thinking, academic learning) confirmed by clinical assessment and individualized standardized testing. Criterion B: Deficits in adaptive functioning that result in failure to meet developmental and sociocultural standards for personal independence and social responsibility. Criterion C: Onset of intellectual and adaptive deficits during the developmental period.',
                    'specifiers' => 'Severity specifiers (Mild, Moderate, Severe, Profound) based on adaptive functioning levels across Conceptual, Social, and Practical domains, because adaptive functioning determines the level of supports required.',
                    'differential_diagnosis' => 'Major and Mild Neurocognitive Disorders: Characterized by a loss of previously acquired cognitive functions rather than developmental delay. Specific Learning Disorder: Cognitive deficits are circumscribed to specific academic domains without generalized adaptive impairment.',
                    'clinical_example' => 'Educational Case Scenario: A 7-year-old child exhibits difficulty grasping abstract academic concepts and requires daily supervision for hygiene and social communication. Standardized cognitive testing reveals broad conceptual delays alongside practical self-care deficits dating back to early toddlerhood, consistent with Mild Intellectual Disability rather than an isolated learning disorder.',
                    'summary_notes' => '• DSM-5 removed the strict IQ < 70 cutoff as the sole metric, prioritizing adaptive domains (Conceptual, Social, Practical).\n• Onset must be developmental; later onset indicates Neurocognitive pathology.\n• Global Developmental Delay is used for children under age 5 who fail to meet developmental milestones across multiple areas.',
                    'questions' => [
                        [
                            'question' => 'Under DSM-5 criteria, what primary metric determines the severity level (Mild, Moderate, Severe, Profound) of Intellectual Disability?',
                            'choice_a' => 'Standardized IQ numerical score below 70',
                            'choice_b' => 'Adaptive functioning across conceptual, social, and practical domains',
                            'choice_c' => 'Age of parent at conception',
                            'choice_d' => 'Degree of physical motor impairment',
                            'correct_answer' => 'B',
                            'explanation' => 'DSM-5 specifies that severity levels of Intellectual Disability are defined on the basis of adaptive functioning across conceptual, social, and practical domains, rather than IQ scores, because adaptive functioning dictates the level of support required.',
                            'difficulty' => 'beginner',
                        ],
                        [
                            'question' => 'Educational Case: A 4-year-old child demonstrates significant delays in meeting intellectual and motor milestones, but standardized psychometric testing cannot yet be reliably administered. Which DSM-5 diagnosis is indicated?',
                            'choice_a' => 'Major Neurocognitive Disorder',
                            'choice_b' => 'Global Developmental Delay',
                            'choice_c' => 'Specific Learning Disorder',
                            'choice_d' => 'Childhood-Onset Fluency Disorder',
                            'correct_answer' => 'B',
                            'explanation' => 'Global Developmental Delay is reserved for individuals under the age of 5 years when clinical severity level cannot be reliably assessed during early childhood standardized testing.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                2 => [
                    'title' => 'Communication Disorders',
                    'slug' => 'communication-disorders',
                    'description' => 'Language Disorder, Speech Sound Disorder, Childhood-Onset Fluency Disorder (Stuttering), and Social (Pragmatic) Communication Disorder.',
                    'part_type' => 'learn',
                    'overview' => 'DSM-5 Communication Disorders encompass deficits in language, speech, and communication. A key structural update in DSM-5 is the inclusion of Social (Pragmatic) Communication Disorder (SPCD), which involves persistent difficulties in the social use of verbal and nonverbal communication. Crucially, SPCD is diagnosed only when the repetitive, restricted patterns of behavior characteristic of Autism Spectrum Disorder are absent.',
                    'key_concepts' => [
                        ['title' => 'Language vs. Speech Sound', 'icon' => '🗣️', 'definition' => 'Language disorder involves comprehension and production of vocabulary and discourse; Speech sound disorder involves clear articulation of phonemes.'],
                        ['title' => 'Social (Pragmatic) Communication', 'icon' => '🤝', 'definition' => 'Deficits in conversational turn-taking, contextual greeting, and understanding non-literal idioms without restricted/repetitive behaviors.'],
                        ['title' => 'Childhood-Onset Fluency Disorder', 'icon' => '🌊', 'definition' => 'Disturbance in normal fluency and motor patterning of speech (sound prolongations, pauses, broken words).']
                    ],
                    'important_terms' => [
                        ['term' => 'Pragmatics', 'definition' => 'The social rules and contextual nuances governing verbal and nonverbal communication.'],
                        ['term' => 'Phonological Production', 'definition' => 'The motoric and acoustic articulation of distinct vocal sounds necessary for intelligible speech.']
                    ],
                    'diagnostic_criteria' => 'Social (Pragmatic) Communication Disorder requires persistent difficulties in social verbal/nonverbal communication (Criterion A), functional limitations (Criterion B), early developmental onset (Criterion C), and lack of restricted/repetitive behaviors (Criterion D).',
                    'specifiers' => 'Associated with medical or neurological condition, or other neurodevelopmental/mental disorder.',
                    'differential_diagnosis' => 'Autism Spectrum Disorder: SPCD cannot be diagnosed if an individual exhibits restricted interests or repetitive behaviors. Selective Mutism: Failure to speak is situational (e.g., school) despite speaking normally in other settings.',
                    'clinical_example' => 'Educational Case Scenario: A 9-year-old child struggles to decipher sarcasm, take conversational turns, or adapt greeting styles between peers and teachers. However, developmental history reveals no sensory sensitivities, repetitive motor mannerisms, or fixated routines. The profile satisfies Social (Pragmatic) Communication Disorder rather than ASD.',
                    'summary_notes' => '• Social (Pragmatic) Communication Disorder was introduced in DSM-5 to capture pragmatic language deficits without repetitive/restricted behaviors.\n• If restricted/repetitive behaviors or stereotypies are present, the diagnosis must be Autism Spectrum Disorder.\n• Stuttering is formally titled Childhood-Onset Fluency Disorder in DSM-5.',
                    'questions' => [
                        [
                            'question' => 'Which DSM-5 criterion distinguishes Social (Pragmatic) Communication Disorder (SPCD) from Autism Spectrum Disorder (ASD)?',
                            'choice_a' => 'SPCD requires onset only after age 18',
                            'choice_b' => 'SPCD exhibits an absence of restricted, repetitive patterns of behavior, interests, or activities',
                            'choice_c' => 'SPCD requires severe intellectual impairment',
                            'choice_d' => 'SPCD only occurs in children with severe hearing loss',
                            'correct_answer' => 'B',
                            'explanation' => 'Social (Pragmatic) Communication Disorder is characterized by pragmatic language deficits but lacks the restricted, repetitive patterns of behavior, interests, or activities that are required for a diagnosis of Autism Spectrum Disorder.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                3 => [
                    'title' => 'Autism Spectrum Disorder (ASD)',
                    'slug' => 'autism-spectrum-disorder',
                    'description' => 'Consolidation of Autistic Disorder, Asperger’s, and PDD-NOS into a single dimensional spectrum.',
                    'part_type' => 'learn',
                    'overview' => 'In DSM-5, previously separate diagnoses (Autistic Disorder, Asperger’s Disorder, Childhood Disintegrative Disorder, Pervasive Developmental Disorder Not Otherwise Specified) were unified into Autism Spectrum Disorder (ASD). ASD is defined across two core behavioral dyads: persistent deficits in social communication and social interaction across multiple contexts (Criterion A), and restricted, repetitive patterns of behavior, interests, or activities (Criterion B). Sensory sensitivities (hyper- or hyporeactivity to sensory input) are formally integrated into Criterion B.',
                    'key_concepts' => [
                        ['title' => 'Two Core Diagnostic Dyads', 'icon' => '🧩', 'definition' => 'Criterion A (Social interaction/communication deficits) AND Criterion B (Restricted, repetitive behaviors, interests, or activities).'],
                        ['title' => 'Dimensional Severity Levels', 'icon' => '📊', 'definition' => 'Level 1: Requiring support; Level 2: Requiring substantial support; Level 3: Requiring very substantial support.'],
                        ['title' => 'Sensory Integration', 'icon' => '⚡', 'definition' => 'Hyper- or hyporeactivity to sensory input or unusual interest in sensory aspects of the environment.']
                    ],
                    'important_terms' => [
                        ['term' => 'Social-Emotional Reciprocity', 'definition' => 'Abnormal social approach, failure of normal back-and-forth conversation, reduced sharing of interests or affect.'],
                        ['term' => 'Echolalia', 'definition' => 'Stereotyped or repetitive mechanical repetition of another person\'s vocalizations or words.'],
                        ['term' => 'Inflexible Adherence', 'definition' => 'Rigid adherence to nonfunctional routines, ritualized patterns of behavior, or extreme distress at small changes.']
                    ],
                    'diagnostic_criteria' => 'Criterion A: Persistent deficits in social communication/interaction across multiple contexts (deficits in social-emotional reciprocity, nonverbal communicative behaviors, and developing/maintaining relationships). Criterion B: Restricted, repetitive patterns of behavior, interests, or activities (at least 2 of 4: stereotyped movements/speech, insistence on sameness, highly fixated interests, sensory hyper/hyporeactivity). Symptoms must be present in early developmental period.',
                    'specifiers' => 'With or without accompanying intellectual impairment; With or without accompanying language impairment; Associated with a known medical, genetic, or environmental condition; Severity levels (1, 2, or 3).',
                    'differential_diagnosis' => 'Rett Syndrome: Genetic mutation predominantly in females with typical early development followed by deceleration of head growth and loss of purposeful hand skills. ADHD: Inattention may impair social cues, but reciprocal communicative intent and lack of repetitive routines distinguish ADHD from ASD.',
                    'clinical_example' => 'Educational Case Scenario: An 8-year-old child maintains minimal eye contact, shows intense distress if classroom furniture is moved, lines up miniature vehicles for hours without imaginative play, and covers his ears when fluorescent lights hum. Under DSM-5, this presentation meets criteria for Autism Spectrum Disorder, Level 1 support.',
                    'summary_notes' => '• DSM-5 collapsed Asperger’s and Autistic Disorder into a single dimensional diagnosis: Autism Spectrum Disorder.\n• Diagnosis requires meeting ALL 3 aspects of Criterion A and at least 2 of 4 aspects of Criterion B.\n• Sensory abnormalities are explicitly included under Criterion B4.',
                    'questions' => [
                        [
                            'question' => 'How did the DSM-5 structurally reorganize Autistic Disorder, Asperger\'s Disorder, and Pervasive Developmental Disorder NOS?',
                            'choice_a' => 'Classified them as distinct adult personality disorders',
                            'choice_b' => 'Consolidated them into a single dimensional category: Autism Spectrum Disorder',
                            'choice_c' => 'Moved them into Schizophrenia Spectrum disorders',
                            'choice_d' => 'Eliminated the diagnoses entirely without replacement',
                            'correct_answer' => 'B',
                            'explanation' => 'The DSM-5 consolidated previous pervasive developmental disorders (including Autistic Disorder and Asperger’s) into a unified dimensional condition called Autism Spectrum Disorder (ASD).',
                            'difficulty' => 'beginner',
                        ],
                        [
                            'question' => 'Under DSM-5, which of the following is considered a component of Criterion B for Autism Spectrum Disorder?',
                            'choice_a' => 'Hyper- or hyporeactivity to sensory input or unusual interest in sensory aspects',
                            'choice_b' => 'Visual hallucinations with persecutory themes',
                            'choice_c' => 'Fluctuating cognitive delirium lasting fewer than 24 hours',
                            'choice_d' => 'Manic flight of ideas with grandiosity',
                            'correct_answer' => 'A',
                            'explanation' => 'Criterion B4 explicitly includes hyper- or hyporeactivity to sensory input or unusual interest in sensory aspects of the environment as a core feature of restricted, repetitive behaviors.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                4 => [
                    'title' => 'Attention-Deficit/Hyperactivity Disorder (ADHD)',
                    'slug' => 'adhd-diagnostic-presentation',
                    'description' => 'Inattention, hyperactivity-impulsivity, adult diagnostic thresholds, and the age 12 symptom onset criterion.',
                    'part_type' => 'learn',
                    'overview' => 'Attention-Deficit/Hyperactivity Disorder (ADHD) is characterized by a persistent pattern of inattention and/or hyperactivity-impulsivity that interferes with functioning or development. In DSM-5, the onset threshold was raised: several inattentive or hyperactive-impulsive symptoms must have been present prior to age 12 (compared to age 7 in DSM-IV). Furthermore, for older adolescents and adults (age 17 and older), the diagnostic threshold is reduced to at least 5 symptoms (compared to 6 symptoms required for children).',
                    'key_concepts' => [
                        ['title' => 'Onset Prior to Age 12', 'icon' => '📅', 'definition' => 'Symptoms must have been present prior to age 12 years (raised from age 7 in DSM-IV).'],
                        ['title' => 'Cross-Situational Impairment', 'icon' => '🏫', 'definition' => 'Symptoms must be present in two or more settings (e.g., home, school, workplace, with friends).'],
                        ['title' => 'Age 17+ Threshold', 'icon' => '👥', 'definition' => 'Children require 6 symptoms; older adolescents and adults (17+) require 5 symptoms in either domain.']
                    ],
                    'important_terms' => [
                        ['term' => 'Combined Presentation', 'definition' => 'Both Criterion A1 (inattention) and Criterion A2 (hyperactivity-impulsivity) are met for the past 6 months.'],
                        ['term' => 'Predominantly Inattentive', 'definition' => 'Criterion A1 is met, but Criterion A2 is not met for the past 6 months.'],
                        ['term' => 'Predominantly Hyperactive/Impulsive', 'definition' => 'Criterion A2 is met, but Criterion A1 is not met for the past 6 months.']
                    ],
                    'diagnostic_criteria' => 'Criterion A: Persistent pattern of inattention (6+ symptoms for children, 5+ for adults age 17+) and/or hyperactivity-impulsivity (6+ symptoms for children, 5+ for adults) persisting for at least 6 months. Several symptoms present before age 12, present in 2+ settings, and cause functional impairment.',
                    'specifiers' => 'Combined presentation; Predominantly inattentive presentation; Predominantly hyperactive/impulsive presentation; In partial remission; Mild, Moderate, Severe.',
                    'differential_diagnosis' => 'Oppositional Defiant Disorder (ODD): Resistance in ODD is directed against adult demands; in ADHD, task incompletion is due to difficulty maintaining sustained attention. Bipolar Disorder: ADHD symptoms are chronic and continuous, whereas bipolar mania exhibits episodic, cyclic surges in energy and mood.',
                    'clinical_example' => 'Educational Case Scenario: A 19-year-old university student presents with chronic disorganization, failure to sustain attention during lectures, misplacing items, and missing deadlines since childhood (grade 4). Clinical interviews confirm at least 5 inattention symptoms across academic and home environments prior to age 12, meeting DSM-5 criteria for ADHD, Predominantly Inattentive Presentation.',
                    'summary_notes' => '• DSM-5 raised the symptom onset cutoff from age 7 to before age 12.\n• Symptom threshold: 6 symptoms for children under 17; 5 symptoms for adolescents/adults age 17 and older.\n• Must manifest across two or more settings (e.g., school and home).',
                    'questions' => [
                        [
                            'question' => 'According to the DSM-5, by what age must several inattentive or hyperactive-impulsive symptoms have been present to establish a diagnosis of ADHD?',
                            'choice_a' => 'Prior to age 5',
                            'choice_b' => 'Prior to age 7',
                            'choice_c' => 'Prior to age 12',
                            'choice_d' => 'Prior to age 18',
                            'correct_answer' => 'C',
                            'explanation' => 'DSM-5 raised the onset criterion for ADHD, requiring that several inattentive or hyperactive-impulsive symptoms be present prior to age 12 years (previously age 7 in DSM-IV).',
                            'difficulty' => 'beginner',
                        ],
                        [
                            'question' => 'For an adult patient aged 22 undergoing assessment for ADHD, what is the minimum number of inattention symptoms required under DSM-5 criteria?',
                            'choice_a' => 'At least 4 symptoms',
                            'choice_b' => 'At least 5 symptoms',
                            'choice_c' => 'At least 6 symptoms',
                            'choice_d' => 'At least 8 symptoms',
                            'correct_answer' => 'B',
                            'explanation' => 'For older adolescents and adults (age 17 and older), the DSM-5 threshold for ADHD is reduced from 6 to at least 5 symptoms in either the inattention or hyperactivity-impulsivity domain.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                5 => [
                    'title' => 'Specific Learning Disorder',
                    'slug' => 'specific-learning-disorder',
                    'description' => 'Impairments in reading (dyslexia), written expression, and mathematics (dyscalculia).',
                    'part_type' => 'learn',
                    'overview' => 'Specific Learning Disorder is a neurodevelopmental condition diagnosed when persistent difficulties learning foundational academic skills (reading, written expression, mathematics) last for at least 6 months despite targeted interventions. DSM-5 united previous discrete disorders (Reading Disorder, Mathematics Disorder, Disorder of Written Expression) into a single overarching diagnosis with distinct specifiers for the affected academic domains and sub-skills.',
                    'key_concepts' => [
                        ['title' => '6-Month Minimum Duration', 'icon' => '⏱️', 'definition' => 'Difficulties must persist for at least 6 months despite the provision of targeted interventions.'],
                        ['title' => 'Substantially Below Age Expectations', 'icon' => '📉', 'definition' => 'Academic skills are substantially and quantifiably below those expected for chronological age, confirmed by standardized achievement tests.'],
                        ['title' => 'Domain Specifiers', 'icon' => '📚', 'definition' => 'With impairment in reading, with impairment in written expression, with impairment in mathematics.']
                    ],
                    'important_terms' => [
                        ['term' => 'Dyslexia', 'definition' => 'Alternative term used to refer to a pattern of learning difficulties characterized by problems with accurate or fluent word recognition, decoding, and spelling.'],
                        ['term' => 'Dyscalculia', 'definition' => 'Alternative term used to refer to a pattern of difficulties characterized by problems processing numerical information, learning arithmetic facts, and calculating.']
                    ],
                    'diagnostic_criteria' => 'Difficulties learning and using academic skills for at least 6 months (inaccurate/slow reading, difficulty understanding meaning, spelling difficulties, written expression difficulties, number sense/calculation issues). Confirmed by standardized tests.',
                    'specifiers' => 'Specifiers: With impairment in reading; With impairment in written expression; With impairment in mathematics. Severity: Mild, Moderate, Severe.',
                    'differential_diagnosis' => 'Normal variations in academic achievement: Learning challenges due to lack of educational opportunity or socioeconomic disadvantage are not diagnosed as Specific Learning Disorder. Intellectual Disability: Impairments are generalized across all cognitive domains, not circumscribed to specific academic domains.',
                    'clinical_example' => 'Educational Case Scenario: A 10-year-old child with average intelligence and normal hearing/vision demonstrates profound difficulty with phonetic word decoding and reading fluency, scoring at the 4th percentile on standardized tests despite 9 months of individualized tutoring. The presentation fulfills Specific Learning Disorder with impairment in reading.',
                    'summary_notes' => '• Difficulties must persist for at least 6 months despite the provision of extra educational help.\n• Replaces separate DSM-IV disorders with a single diagnosis using domain specifiers (Reading, Written Expression, Math).\n• Cannot be explained by uncorrected visual/auditory acuity, intellectual disability, or inadequate instruction.',
                    'questions' => [
                        [
                            'question' => 'What is the required minimum duration that academic difficulties must persist despite extra help before a Specific Learning Disorder can be diagnosed under DSM-5?',
                            'choice_a' => '1 month',
                            'choice_b' => '3 months',
                            'choice_c' => '6 months',
                            'choice_d' => '2 years',
                            'correct_answer' => 'C',
                            'explanation' => 'DSM-5 Criterion A requires that learning difficulties in reading, writing, or mathematics persist for at least 6 months despite the provision of targeted interventions.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                6 => [
                    'title' => 'Motor and Tic Disorders',
                    'slug' => 'motor-and-tic-disorders',
                    'description' => 'Tourette\'s Disorder, Persistent Motor/Vocal Tic Disorder, and Developmental Coordination Disorder.',
                    'part_type' => 'learn',
                    'overview' => 'Motor and Tic Disorders in DSM-5 include Developmental Coordination Disorder, Stereotypic Movement Disorder, and Tic Disorders. A tic is a sudden, rapid, recurrent, nonrhythmic motor movement or vocalization. In Tourette’s Disorder, both multiple motor tics and one or more vocal tics must have been present at some time during the illness, although not necessarily concurrently, persisting for more than 1 year with onset before age 18.',
                    'key_concepts' => [
                        ['title' => 'Tourette’s Criteria', 'icon' => '⚡', 'definition' => 'Both multiple motor tics AND at least one vocal tic, lasting > 1 year with onset before age 18.'],
                        ['title' => 'Persistent Motor or Vocal Tic', 'icon' => '🎯', 'definition' => 'Single or multiple motor OR vocal tics present, but NOT both motor and vocal.'],
                        ['title' => 'Stereotypic Movement vs. Tic', 'icon' => '🔄', 'definition' => 'Stereotypic movements are repetitive, rhythmic, and intentional-appearing, whereas tics are nonrhythmic and paroxysmal.']
                    ],
                    'important_terms' => [
                        ['term' => 'Coprolalia', 'definition' => 'Involuntary vocal utterance of socially unacceptable or obscene words (occurs in a minority of Tourette\'s cases).'],
                        ['term' => 'Premonitory Urge', 'definition' => 'A rising somatic sensation or tension preceding the execution of a motor or vocal tic, relieved by performing the tic.']
                    ],
                    'diagnostic_criteria' => 'Tourette’s: Both multiple motor and 1+ vocal tics present; persist > 1 year; onset before age 18; not attributable to substance or medical condition. Persistent (Chronic) Tic Disorder: Motor or vocal tics, but not both.',
                    'specifiers' => 'Tic Disorders: Tourette’s Disorder; Persistent (Chronic) Motor or Vocal Tic Disorder; Provisional Tic Disorder (< 1 year).',
                    'differential_diagnosis' => 'Stereotypic Movement Disorder: Movements are rhythmic, prolonged, and repetitive (e.g., body rocking, hand flapping), without premonitory urge. Myoclonus / Chorea: Involuntary neurological movements that are arrhythmic, continuous, and non-suppressible.',
                    'clinical_example' => 'Educational Case Scenario: A 12-year-old child presents with eye blinking, shoulder shrugging, and throat clearing sounds that began at age 8 and have waxed and waned for 4 years. Both motor and vocal tics have occurred during this timeframe, establishing Tourette\'s Disorder.',
                    'summary_notes' => '• Tourette\'s requires BOTH motor and vocal tics (not necessarily at the exact same moment).\n• Duration must exceed 1 year, and onset must occur prior to age 18.\n• Provisional Tic Disorder is used when tics have been present for less than 1 year.',
                    'questions' => [
                        [
                            'question' => 'To receive a diagnosis of Tourette\'s Disorder under DSM-5, which of the following criteria must be fulfilled?',
                            'choice_a' => 'Vocal tics only, lasting at least 6 months',
                            'choice_b' => 'Both multiple motor tics and one or more vocal tics present at some time, lasting more than 1 year',
                            'choice_c' => 'Only motor tics present, beginning after age 25',
                            'choice_d' => 'Auditory and visual hallucinations lasting over 2 years',
                            'correct_answer' => 'B',
                            'explanation' => 'Tourette’s Disorder requires both multiple motor tics and one or more vocal tics (not necessarily concurrently) persisting for more than 1 year, with onset before age 18.',
                            'difficulty' => 'beginner',
                        ]
                    ]
                ],
                7 => [
                    'title' => 'Neurodevelopmental Course & Etiology',
                    'slug' => 'neurodevelopmental-course-etiology',
                    'description' => 'Genetic architectures, synaptic pruning, and neurodevelopmental trajectories across childhood into adulthood.',
                    'part_type' => 'learn',
                    'overview' => 'Neurodevelopmental conditions arise from complex gene-environment interactions that alter early brain development, synaptogenesis, and neural network connectivity. For example, Autism Spectrum Disorder exhibits polygenic liability and significant heritability (~70-90%), with hundreds of risk loci linked to synaptic scaffolding and chromatin remodeling. While ADHD was historically viewed as a childhood-limited disorder, longitudinal data confirms that symptoms persist into adulthood in approximately 50-60% of cases, often shifting from overt motor hyperactivity to internal restlessness and executive dysfunction.',
                    'key_concepts' => [
                        ['title' => 'High Heritability', 'icon' => '🧬', 'definition' => 'ADHD and ASD show some of the highest heritability estimates among psychiatric conditions (>70%).'],
                        ['title' => 'Adult Symptom Transformation', 'icon' => '🔄', 'definition' => 'Motor hyperactivity often evolves into internal cognitive restlessness and executive procrastination in adults.'],
                        ['title' => 'Synaptic Maturation', 'icon' => '🧠', 'definition' => 'Atypical synaptogenesis, microglial pruning, and neural connectivity disruption characterize neurodevelopmental pathophysiology.']
                    ],
                    'important_terms' => [
                        ['term' => 'Executive Functioning', 'definition' => 'Higher-order cognitive processes mediated by prefrontal-striatal circuits (working memory, set-shifting, inhibitory control).'],
                        ['term' => 'Neurodiversity', 'definition' => 'Concept recognizing variations in brain function and behavioral traits as natural human developmental variations.']
                    ],
                    'diagnostic_criteria' => 'Symptom trajectory across developmental milestones: Early identification in toddlerhood (ASD), early elementary years (Specific Learning, ADHD), and persistence across educational and occupational spheres.',
                    'specifiers' => 'Associated with genetic conditions (e.g., Fragile X, Down syndrome, Tuberous Sclerosis).',
                    'differential_diagnosis' => 'Late-Onset Secondary Attention Deficits: Attention deficits emerging for the first time in adulthood without childhood precursors must be evaluated for depression, sleep apnea, or substance use.',
                    'clinical_example' => 'Educational Case Scenario: A 35-year-old accountant with no prior formal diagnosis presents with severe procrastination and disorganization. Comprehensive developmental history from parents confirms chronic difficulties completing homework and constant fidgeting before age 10, demonstrating continuity across the lifespan.',
                    'summary_notes' => '• ADHD persists into adulthood for a majority of patients, transforming into executive dysfunction and cognitive restlessness.\n• Heritability for ASD and ADHD is high (~70-90%).\n• True neurodevelopmental disorders have early origins, even if first diagnosed in adulthood.',
                    'questions' => [
                        [
                            'question' => 'How does the clinical presentation of ADHD typically evolve from childhood into adulthood?',
                            'choice_a' => 'Overt gross motor hyperactivity often transitions into subjective inner restlessness and executive dysfunction',
                            'choice_b' => 'All symptoms permanently remit by age 15 in 95% of patients',
                            'choice_c' => 'Inattention completely resolves while hyperactivity worsens',
                            'choice_d' => 'It converts directly into Dissociative Identity Disorder',
                            'correct_answer' => 'A',
                            'explanation' => 'In adulthood, overt physical hyperactivity (e.g., running, climbing) typically diminishes and transitions into subjective internal restlessness, disorganization, and executive functioning deficits.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                8 => [
                    'title' => 'Differential Diagnosis & Comorbidity in Neurodevelopment',
                    'slug' => 'differential-comorbidity-neurodevelopment',
                    'description' => 'Teasing apart ASD vs. ADHD vs. Sensory Processing, and evaluating frequent comorbidity.',
                    'part_type' => 'learn',
                    'overview' => 'In DSM-IV, a dual diagnosis of ADHD and Autism Spectrum Disorder was strictly prohibited. One of the most significant changes in DSM-5 is the explicit removal of this mutual exclusion, allowing comorbid diagnosis of ADHD and ASD when criteria for both are met. Differential diagnosis requires careful analysis: inattention in ASD may stem from hyperfocus on fixated interests, whereas in ADHD, inattention is driven by distractibility and low motivation for non-preferred tasks.',
                    'key_concepts' => [
                        ['title' => 'Comorbid ASD + ADHD Permitted', 'icon' => '🤝', 'definition' => 'DSM-5 officially allows concurrent diagnoses of Autism Spectrum Disorder and ADHD.'],
                        ['title' => 'Social Motivation Distinction', 'icon' => '👥', 'definition' => 'Individuals with ADHD typically desire social interaction but misread cues due to impulsivity, whereas ASD involves core socio-emotional reciprocity deficits.'],
                        ['title' => 'Tic Disorders and ADHD', 'icon' => '⚡', 'definition' => 'Up to 50% of individuals with Tourette’s disorder have comorbid ADHD.']
                    ],
                    'important_terms' => [
                        ['term' => 'Diagnostic Exclusion Lifted', 'definition' => 'The DSM-5 revision permitting simultaneous coding of ASD and ADHD when both criteria sets are met.'],
                        ['term' => 'Symptom Overlap', 'definition' => 'Co-occurring executive dysfunction and sensory atypicalities across multiple neurodevelopmental categories.']
                    ],
                    'diagnostic_criteria' => 'When both Autism Spectrum Disorder and ADHD criteria are fully met, clinicians are instructed to record both diagnoses.',
                    'specifiers' => 'Specifiers indicating co-occurring medical, genetic, or neurodevelopmental disorders.',
                    'differential_diagnosis' => 'Hearing Impairment vs. Language Disorder: Audiological examination is mandatory before diagnosing language delay. Childhood Schizophrenia: Very rare before age 13; involves hallucinations and delusions which are absent in uncomplicated ASD.',
                    'clinical_example' => 'Educational Case Scenario: An 8-year-old child demonstrates repetitive hand-flapping and social reciprocity deficits alongside severe, pervasive motor impulsivity and distractibility across school and home. Under DSM-5, the clinician may record both Autism Spectrum Disorder and ADHD.',
                    'summary_notes' => '• DSM-5 permits comorbid diagnosis of Autism Spectrum Disorder and ADHD.\n• Impulsivity in ADHD vs. lack of social understanding in ASD.\n• Always rule out hearing/vision deficits before diagnosing Communication or Learning Disorders.',
                    'questions' => [
                        [
                            'question' => 'How did the DSM-5 change the diagnostic relationship between Autism Spectrum Disorder and ADHD?',
                            'choice_a' => 'ADHD diagnosis is now completely prohibited if ASD is suspected',
                            'choice_b' => 'DSM-5 explicitly allows the comorbid diagnosis of both ASD and ADHD when criteria for both are met',
                            'choice_c' => 'ADHD can only be diagnosed if the patient has severe intellectual disability',
                            'choice_d' => 'ASD and ADHD were merged into a single identical diagnosis',
                            'correct_answer' => 'B',
                            'explanation' => 'Unlike DSM-IV (which prohibited diagnosing ADHD in the presence of an autistic disorder), DSM-5 allows clinicians to diagnose comorbid ADHD and Autism Spectrum Disorder if criteria for both are satisfied.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                9 => [
                    'title' => 'Educational Case Scenarios & Clinical Formulations',
                    'slug' => 'educational-case-scenarios-neurodevelopment',
                    'description' => 'Complex diagnostic case formulations for board examination preparation.',
                    'part_type' => 'scenario',
                    'overview' => 'Board-level psychopathology examinations frequently present complex clinical vignettes where multiple neurodevelopmental features overlap. Accurate formulation demands isolating the chronological onset, the primary drivers of impairment (e.g., academic skill deficit vs. attentional vs. adaptive living), and identifying whether secondary behavioral issues represent Oppositional Defiant Disorder, Anxiety, or manifestations of frustration secondary to an unaddressed learning or communication disorder.',
                    'key_concepts' => [
                        ['title' => 'Formulation Hierarchy', 'icon' => '📐', 'definition' => 'Establishing chronological emergence and domain-specific testing evidence.'],
                        ['title' => 'Secondary Frustration', 'icon' => '💢', 'definition' => 'Behavioral outbursts during reading class often stem from an unaddressed learning disorder rather than primary conduct disturbance.'],
                        ['title' => 'Adaptive Independence', 'icon' => '🏠', 'definition' => 'Assessing self-care and community navigation to differentiate Intellectual Disability from isolated Learning Disorder.']
                    ],
                    'important_terms' => [
                        ['term' => 'Differential Synthesis', 'definition' => 'The systematic elimination of alternative diagnoses through evidence-based diagnostic criteria comparison.'],
                        ['term' => 'Neurodevelopmental Profiling', 'definition' => 'Comprehensive multi-disciplinary assessment incorporating cognitive, academic, speech, and occupational metrics.']
                    ],
                    'diagnostic_criteria' => 'Integration of Criterion A, B, and C across Intellectual Disability, ASD, ADHD, and Specific Learning Disorder.',
                    'specifiers' => 'Comprehensive multi-axial specifiers.',
                    'differential_diagnosis' => 'Comprehensive case differential across the entire neurodevelopmental chapter.',
                    'clinical_example' => 'Educational Case Scenario: A 6-year-old child exhibits intense temper tantrums solely when asked to read aloud in first grade, but behaves cooperatively during art and physical education. Testing shows reading decoding scores at the 2nd percentile while adaptive functioning and nonverbal IQ are at the 60th percentile. The formulation indicates Specific Learning Disorder with impairment in reading; behavioral outbursts are secondary to academic failure rather than ODD.',
                    'summary_notes' => '• Context-dependent behavior (tantrums only during reading) suggests secondary frustration from a Specific Learning Disorder rather than primary ODD.\n• Adaptive functioning separates generalized Intellectual Disability from circumscribed Learning Disorders.',
                    'questions' => [
                        [
                            'question' => 'Educational Case: A 7-year-old child demonstrates disruptive defiance and throws books, but only during 45-minute reading instruction periods; outside of reading class, behavior is cooperative. Testing reveals severe phonological decoding deficits. What is the most accurate diagnostic interpretation?',
                            'choice_a' => 'Primary Conduct Disorder, Childhood-Onset',
                            'choice_b' => 'Specific Learning Disorder with impairment in reading, with secondary frustration behaviors',
                            'choice_c' => 'Bipolar I Disorder with rapid cycling',
                            'choice_d' => 'Stereotypic Movement Disorder with self-injury',
                            'correct_answer' => 'B',
                            'explanation' => 'The situational nature of the disruptive behavior (occurring only during reading tasks) and documented phonetic decoding deficits point to Specific Learning Disorder in reading, with behavioral outbursts reflecting secondary avoidance and academic frustration.',
                            'difficulty' => 'advanced',
                        ]
                    ]
                ],
                10 => [
                    'title' => 'Chapter Mastery & Board-Exam Synthesis',
                    'slug' => 'neurodevelopmental-mastery-synthesis',
                    'description' => 'Comprehensive synthesis of all 9 DSM-5 neurodevelopmental sections in preparation for the chapter boss trial.',
                    'part_type' => 'mastery',
                    'overview' => 'Part 10 concludes the Neurodevelopmental Disorders chapter with an advanced synthesis of all core concepts: from adaptive criteria in Intellectual Disability and communication disorders, to the unification of ASD, the age 12 onset threshold for ADHD, Specific Learning Disorder specifiers, and motor tic taxonomy. Mastery of these criteria prepares the student for clinical board examinations and the Chapter Boss: The Synaptic Weaver.',
                    'key_concepts' => [
                        ['title' => 'Core Taxonomy Recapitulation', 'icon' => '🏛️', 'definition' => 'Synthesizing all DSM-5 criteria, age thresholds, and diagnostic specifiers across neurodevelopment.'],
                        ['title' => 'Board-Exam High-Yield Anchors', 'icon' => '⚓', 'definition' => 'Age 12 for ADHD; >1 year for Tourette’s; 6 months for Specific Learning; Adaptive functioning for ID.'],
                        ['title' => 'Dimensional Progression', 'icon' => '📈', 'definition' => 'Recognizing how neurodevelopmental divergence shapes lifelong psychosocial trajectory.']
                    ],
                    'important_terms' => [
                        ['term' => 'Diagnostic Synthesis', 'definition' => 'Holistic integration of developmental history, behavioral observation, and psychometric findings.'],
                        ['term' => 'The Synaptic Weaver', 'definition' => 'The Chapter Boss entity embodying the neurodevelopmental psychopathology curriculum.']
                    ],
                    'diagnostic_criteria' => 'Full DSM-5 Neurodevelopmental Disorders section criteria review.',
                    'specifiers' => 'All domain and severity specifiers across chapter conditions.',
                    'differential_diagnosis' => 'Cross-chapter differential diagnostic matrix.',
                    'clinical_example' => 'Educational Mastery Review: Comparing a patient with ASD Level 1 vs. Social Communication Disorder vs. ADHD vs. Tourette\'s to formulate multi-diagnostic treatment and educational plans.',
                    'summary_notes' => '• Intellectual Disability: adaptive functioning severity (not IQ score).\n• ASD: 2 core dyads (social communication + restricted/repetitive behaviors with sensory input).\n• ADHD: 6 symptoms for children, 5 for adults 17+; onset before age 12; 2+ settings.\n• Tourette’s: multiple motor + 1+ vocal tics for >1 year before age 18.',
                    'questions' => [
                        [
                            'question' => 'Board-Exam Review: Which pair correctly matches the DSM-5 neurodevelopmental condition with its designated diagnostic timeframe criterion?',
                            'choice_a' => 'ADHD — symptoms must begin before age 7',
                            'choice_b' => 'Tourette\'s Disorder — motor and vocal tics must persist for more than 1 year',
                            'choice_c' => 'Specific Learning Disorder — academic difficulties must persist for at least 1 month',
                            'choice_d' => 'Social (Pragmatic) Communication Disorder — must onset after age 21',
                            'correct_answer' => 'B',
                            'explanation' => 'Tourette\'s Disorder requires both multiple motor tics and one or more vocal tics to have persisted for more than 1 year since the first tic onset. ADHD requires onset before age 12 (not 7), and Specific Learning Disorder requires at least 6 months (not 1 month).',
                            'difficulty' => 'board_exam',
                        ]
                    ]
                ]
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // DOMAIN 2: SCHIZOPHRENIA SPECTRUM AND OTHER PSYCHOTIC DISORDERS
    // ─────────────────────────────────────────────────────────────
    private static function getSchizophreniaDomain(): array {
        return [
            'name' => 'Schizophrenia Spectrum and Other Psychotic Disorders',
            'topics' => [
                'The Five Psychotic Domains',
                'Delusional Disorder',
                'Brief Psychotic Disorder',
                'Schizophreniform Disorder',
                'Schizophrenia: Criterion A & Course',
                'Schizoaffective Disorder',
                'Substance-Induced & Medical Psychosis',
                'Catatonia',
                'Differential Diagnosis & Clinical Scenarios',
                'Chapter Mastery & Board Review',
            ],
            'parts' => [
                1 => [
                    'title' => 'The Psychotic Spectrum & Five Symptom Domains',
                    'slug' => 'psychotic-spectrum-five-domains',
                    'description' => 'Delusions, hallucinations, disorganized thinking, abnormal motor behavior, and negative symptoms.',
                    'part_type' => 'learn',
                    'overview' => 'Under DSM-5, Schizophrenia Spectrum and Other Psychotic Disorders are defined by abnormalities in one or more of five distinct symptom domains: 1) Delusions, 2) Hallucinations, 3) Disorganized thinking (speech), 4) Grossly disorganized or abnormal motor behavior (including catatonia), and 5) Negative symptoms. Delusions are fixed beliefs not amenable to change in light of conflicting evidence. Hallucinations are perception-like experiences that occur without an external stimulus, most commonly auditory in schizophrenia.',
                    'key_concepts' => [
                        ['title' => 'Five Symptom Domains', 'icon' => '🏛️', 'definition' => 'Delusions, Hallucinations, Disorganized speech, Grossly disorganized/catatonic behavior, Negative symptoms.'],
                        ['title' => 'Negative Symptoms', 'icon' => '📉', 'definition' => 'Diminished emotional expression (blunted affect) and avolition (decrease in motivated self-initiated activities).'],
                        ['title' => 'Bizarre vs. Non-Bizarre Delusions', 'icon' => '🌀', 'definition' => 'DSM-5 removed special weighting for bizarre delusions; they are treated as clinical features of delusional severity.']
                    ],
                    'important_terms' => [
                        ['term' => 'Avolition', 'definition' => 'A severe lack of initiative or motivation to pursue goal-directed activities.'],
                        ['term' => 'Alogia', 'definition' => 'Diminished speech output, manifesting as poverty of speech or brief empty replies.'],
                        ['term' => 'Anhedonia', 'definition' => 'Decreased ability to experience pleasure from positive stimuli or degradation in recollection of pleasure.']
                    ],
                    'diagnostic_criteria' => 'Assessment across the 5 domains. To qualify as psychotic pathology, symptoms must exceed normative cultural or religious practices and cause distress/impairment.',
                    'specifiers' => 'First episode, currently in acute episode; First episode, currently in partial remission; Continuous; With catatonia.',
                    'differential_diagnosis' => 'Culture-Sanctioned Beliefs: Beliefs shared within a specific religious or cultural group are not classified as delusions.',
                    'clinical_example' => 'Educational Case Scenario: A 24-year-old individual reports hearing two distinct voices conversing about his daily habits outside his window when no one is present, accompanied by profound emotional flattening and inability to initiate basic personal hygiene, exemplifying hallucinations and negative symptoms.',
                    'summary_notes' => '• The five domains: Delusions, Hallucinations, Disorganized Thinking, Disorganized Motor/Catatonia, Negative Symptoms.\n• Negative symptoms account for substantial long-term morbidity (especially avolition and blunted affect).\n• Auditory hallucinations are the most common sensory modality in schizophrenia.',
                    'questions' => [
                        [
                            'question' => 'Which of the following is categorized as a core "negative symptom" in the DSM-5 description of Schizophrenia Spectrum disorders?',
                            'choice_a' => 'Persecutory delusions',
                            'choice_b' => 'Auditory hallucinations',
                            'choice_c' => 'Avolition and diminished emotional expression',
                            'choice_d' => 'Flight of ideas',
                            'correct_answer' => 'C',
                            'explanation' => 'Negative symptoms in DSM-5 primarily encompass diminished emotional expression (blunted affect) and avolition (reduction in goal-directed purposeful activity), as well as alogia, anhedonia, and asociality.',
                            'difficulty' => 'beginner',
                        ]
                    ]
                ],
                2 => [
                    'title' => 'Delusional Disorder & Brief Psychotic Disorder',
                    'slug' => 'delusional-disorder-brief-psychotic',
                    'description' => 'Non-bizarre/bizarre isolated delusions (1+ month) versus acute psychotic episodes (< 1 month).',
                    'part_type' => 'learn',
                    'overview' => 'Delusional Disorder is characterized by the presence of one or more delusions persisting for at least 1 month. Crucially, Criterion A for schizophrenia has never been met; hallucinations, if present, are not prominent and are related to the delusional theme. Apart from the direct impact of the delusion, psychosocial functioning is not markedly impaired, and behavior is not obviously bizarre. In contrast, Brief Psychotic Disorder involves sudden onset of at least one psychotic symptom lasting at least 1 day but less than 1 month, with eventual full return to premorbid functioning.',
                    'key_concepts' => [
                        ['title' => 'Delusional Disorder Duration', 'icon' => '⏱️', 'definition' => 'At least 1 month of delusions without other core schizophrenia symptoms.'],
                        ['title' => 'Brief Psychotic Duration', 'icon' => '⚡', 'definition' => 'At least 1 day but LESS than 1 month, followed by full return to baseline functioning.'],
                        ['title' => 'Functioning Preservation', 'icon' => '💼', 'definition' => 'In Delusional Disorder, personality and everyday functioning outside the delusion remain largely preserved.']
                    ],
                    'important_terms' => [
                        ['term' => 'Erotomanic Type', 'definition' => 'Delusion that another person, usually of higher social standing, is secretly in love with the individual.'],
                        ['term' => 'Grandiose Type', 'definition' => 'Conviction of having some great unacknowledged talent, insight, or special relationship with a deity.'],
                        ['term' => 'Jealous Type', 'definition' => 'Delusion that one\'s spouse or lover is unfaithful, held without appropriate empirical evidence.']
                    ],
                    'diagnostic_criteria' => 'Delusional Disorder: 1+ delusions for 1+ month; Criterion A for schizophrenia never met; functioning not markedly impaired. Brief Psychotic Disorder: Presence of 1+ of: delusions, hallucinations, disorganized speech, or grossly disorganized behavior; duration 1 day to < 1 month.',
                    'specifiers' => 'Delusional Disorder subtypes: Erotomanic, Grandiose, Jealous, Persecutory, Somatic, Mixed, Unspecified. Brief Psychotic specifiers: With marked stressor(s), Without marked stressor(s), With postpartum onset.',
                    'differential_diagnosis' => 'OCD with absent insight: In OCD, intrusive thoughts were preceded by obsessions and compulsions. Schizophrenia: Involves hallucinations, negative symptoms, or marked cognitive decline.',
                    'clinical_example' => 'Educational Case Scenario: A 42-year-old bank manager functions effectively at work but insists for the past 6 months that his co-worker is transmitting secret love codes through spreadsheet formatting. He exhibits no hallucinations, speech disorganization, or negative symptoms, fitting Delusional Disorder, Erotomanic Type.',
                    'summary_notes' => '• Delusional Disorder requires at least 1 month of delusions; general functioning remains intact outside the delusion.\n• Brief Psychotic Disorder lasts between 1 day and under 1 month, with complete recovery.\n• Hallucinations in Delusional Disorder, if present, must be minor and thematic to the delusion.',
                    'questions' => [
                        [
                            'question' => 'What is the diagnostic time duration that differentiates Brief Psychotic Disorder from Schizophreniform Disorder?',
                            'choice_a' => 'Brief Psychotic Disorder lasts less than 1 month with full recovery',
                            'choice_b' => 'Brief Psychotic Disorder must persist for at least 6 months',
                            'choice_c' => 'Brief Psychotic Disorder lasts exactly 2 weeks with permanent cognitive deficits',
                            'choice_d' => 'Brief Psychotic Disorder only occurs during sleep',
                            'correct_answer' => 'A',
                            'explanation' => 'Brief Psychotic Disorder is defined by psychotic symptoms lasting at least 1 day but less than 1 month, with an eventual full return to premorbid functioning. In contrast, Schizophreniform Disorder lasts at least 1 month but less than 6 months.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                3 => [
                    'title' => 'Schizophreniform Disorder & Schizophrenia',
                    'slug' => 'schizophreniform-schizophrenia',
                    'description' => 'The time continuum: 1–6 months (Schizophreniform) vs. 6+ months (Schizophrenia), and Criterion A.',
                    'part_type' => 'learn',
                    'overview' => 'The diagnostic boundary between Schizophreniform Disorder and Schizophrenia is primarily a matter of duration. Schizophreniform Disorder requires characteristic psychotic symptoms lasting at least 1 month but less than 6 months. When continuous signs of the disturbance persist for at least 6 months (including at least 1 month of active-phase Criterion A symptoms), the diagnosis is Schizophrenia. DSM-5 requires that at least one of the active-phase symptoms must be delusions, hallucinations, or disorganized speech.',
                    'key_concepts' => [
                        ['title' => 'Duration Continuum', 'icon' => '⏳', 'definition' => '< 1 month: Brief Psychotic; 1 to < 6 months: Schizophreniform; 6+ months: Schizophrenia.'],
                        ['title' => 'Mandatory "Core Three" Requirement', 'icon' => '🔑', 'definition' => 'At least one Criterion A symptom must be Delusions, Hallucinations, or Disorganized Speech.'],
                        ['title' => 'Social/Occupational Decline', 'icon' => '📉', 'definition' => 'Schizophrenia explicitly requires a marked decline in functioning below the level achieved prior to onset.']
                    ],
                    'important_terms' => [
                        ['term' => 'Prodromal Phase', 'definition' => 'Subthreshold symptoms (social isolation, unusual beliefs, eccentricities) preceding the active psychotic break.'],
                        ['term' => 'Residual Phase', 'definition' => 'Period following an active psychotic phase characterized by blunted affect, mild eccentric beliefs, or avolition.']
                    ],
                    'diagnostic_criteria' => 'Criterion A: Two (or more) of the following, each present for a significant portion of time during a 1-month period: 1) Delusions, 2) Hallucinations, 3) Disorganized speech, 4) Grossly disorganized or catatonic behavior, 5) Negative symptoms. (At least one must be 1, 2, or 3). Criterion B: Functional decline. Criterion C: Continuous signs for 6+ months.',
                    'specifiers' => 'First episode (acute, partial, full remission); Multiple episodes; Continuous; Severity dimensional scale.',
                    'differential_diagnosis' => 'Major Depressive with Psychotic Features: Psychotic symptoms occur exclusively during depressive mood episodes. Schizophreniform: Same active symptoms but duration is under 6 months.',
                    'clinical_example' => 'Educational Case Scenario: A 21-year-old college student withdrew from friends 7 months ago. For the past 2 months, she has experienced persecutory auditory hallucinations and incoherent speech. Because continuous signs have exceeded 6 months with 1+ month of active symptoms, the diagnosis is Schizophrenia.',
                    'summary_notes' => '• Schizophreniform: 1 month to < 6 months.\n• Schizophrenia: 6+ months total duration (including prodromal/residual phases) with 1+ month of active Criterion A.\n• Active phase must include at least one of: Delusions, Hallucinations, or Disorganized Speech.',
                    'questions' => [
                        [
                            'question' => 'Under DSM-5, which of the following is a mandatory requirement for Criterion A in Schizophrenia?',
                            'choice_a' => 'At least one symptom must be delusions, hallucinations, or disorganized speech',
                            'choice_b' => 'Catatonia must be present in every patient',
                            'choice_c' => 'Symptoms must completely resolve within 30 days',
                            'choice_d' => 'The patient must have a documented family history of psychosis',
                            'correct_answer' => 'A',
                            'explanation' => 'DSM-5 specifies that of the two or more Criterion A symptoms required, at least one must be delusions, hallucinations, or disorganized speech.',
                            'difficulty' => 'intermediate',
                        ],
                        [
                            'question' => 'A patient exhibits full Criterion A active psychotic symptoms for exactly 3 months, with no prior prodrome, and no previous episodes. What is the appropriate DSM-5 diagnosis?',
                            'choice_a' => 'Brief Psychotic Disorder',
                            'choice_b' => 'Schizophreniform Disorder',
                            'choice_c' => 'Schizophrenia',
                            'choice_d' => 'Delusional Disorder',
                            'correct_answer' => 'B',
                            'explanation' => 'An episode of psychotic illness that lasts at least 1 month but less than 6 months is classified as Schizophreniform Disorder.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                4 => [
                    'title' => 'Schizoaffective Disorder',
                    'slug' => 'schizoaffective-disorder',
                    'description' => 'Mood episode concurrency and the crucial 2-week psychotic window without major mood symptoms.',
                    'part_type' => 'learn',
                    'overview' => 'Schizoaffective Disorder is characterized by an uninterrupted period of illness during which there is a major mood episode (major depressive or manic) concurrent with Criterion A of schizophrenia. To establish Schizoaffective Disorder and rule out a mood disorder with psychotic features, the patient must have experienced delusions or hallucinations for at least 2 weeks in the absence of a major mood episode (depressive or manic) during the lifetime duration of the illness.',
                    'key_concepts' => [
                        ['title' => 'The 2-Week Psychotic Rule', 'icon' => '📅', 'definition' => 'Delusions or hallucinations must be present for at least 2 weeks in the ABSENCE of a major mood episode.'],
                        ['title' => 'Mood Proportionality', 'icon' => '⚖️', 'definition' => 'Mood symptoms must be present for the majority of the total duration of the active and residual phases.'],
                        ['title' => 'Two Distinct Types', 'icon' => '🔀', 'definition' => 'Bipolar Type (if a manic episode is part of presentation) vs. Depressive Type (major depressive episodes only).']
                    ],
                    'important_terms' => [
                        ['term' => 'Bipolar Type', 'definition' => 'This subtype applies if a manic episode is part of the clinical presentation.'],
                        ['term' => 'Depressive Type', 'definition' => 'This subtype applies if only major depressive episodes are part of the clinical presentation.']
                    ],
                    'diagnostic_criteria' => 'Criterion A: Uninterrupted period of illness with major mood episode concurrent with Criterion A schizophrenia. Criterion B: Delusions or hallucinations for 2+ weeks without major mood episode. Criterion C: Mood episode symptoms present for majority of illness duration. Criterion D: Not due to substance/medical.',
                    'specifiers' => 'Bipolar type; Depressive type; With catatonia.',
                    'differential_diagnosis' => 'Mood Disorder with Psychotic Features: Psychotic symptoms occur ONLY during mood episodes. Schizophrenia: Mood episodes, if present, occupy only a minority of total illness duration.',
                    'clinical_example' => 'Educational Case Scenario: A patient has a 4-year history of chronic illness. During this period, she had a 6-month severe depressive episode with auditory hallucinations. However, following full recovery from depression, the auditory hallucinations and paranoid delusions continued unabated for 3 months with euthymic mood, establishing Schizoaffective Disorder.',
                    'summary_notes' => '• Core differentiator: Delusions or hallucinations for at least 2 weeks WITHOUT mood symptoms.\n• Mood symptoms must be present for the majority of the total illness duration.\n• Subtyped into Bipolar Type (includes mania) or Depressive Type (only major depression).',
                    'questions' => [
                        [
                            'question' => 'What is the critical DSM-5 criterion that differentiates Schizoaffective Disorder from a Major Depressive Disorder with Psychotic Features?',
                            'choice_a' => 'Delusions or hallucinations must occur for at least 2 weeks in the absence of a major mood episode',
                            'choice_b' => 'Psychotic symptoms must occur solely during severe melancholic depression',
                            'choice_c' => 'The patient must experience visual hallucinations rather than auditory',
                            'choice_d' => 'Onset must occur in early infancy before age 3',
                            'correct_answer' => 'A',
                            'explanation' => 'Under DSM-5 Criterion B for Schizoaffective Disorder, there must be delusions or hallucinations for at least 2 weeks in the absence of a major mood episode (depressive or manic) during the lifetime duration of the illness.',
                            'difficulty' => 'advanced',
                        ]
                    ]
                ],
                5 => [
                    'title' => 'Catatonia Across the Spectrum',
                    'slug' => 'catatonia-diagnostic-features',
                    'description' => 'The 12 catatonic signs and diagnosing catatonia as a transdiagnostic specifier.',
                    'part_type' => 'learn',
                    'overview' => 'In DSM-5, Catatonia is no longer classified as an independent schizophrenia subtype. Instead, it is recognized as a transdiagnostic clinical specifier that can accompany various psychiatric conditions (schizophrenia, bipolar disorder, major depression) or exist as Catatonic Disorder Due to Another Medical Condition. Diagnosis requires the presence of 3 or more of 12 distinct psychomotor features.',
                    'key_concepts' => [
                        ['title' => 'Transdiagnostic Specifier', 'icon' => '🔄', 'definition' => 'Catatonia is treated as a clinical specifier across mood and psychotic disorders, not a standalone subtype.'],
                        ['title' => '3 of 12 Signs Rule', 'icon' => '🔢', 'definition' => 'Requires the presence of 3 or more of the 12 defined motor signs.'],
                        ['title' => 'Medical Emergency Potential', 'icon' => '🚨', 'definition' => 'Malignant catatonia can present with autonomic instability, hyperthermia, and fatal complications if untreated.']
                    ],
                    'important_terms' => [
                        ['term' => 'Waxy Flexibility', 'definition' => 'Slight, even resistance to positioning by the examiner, with maintenance of the newly imposed posture.'],
                        ['term' => 'Catalepsy', 'definition' => 'Passive induction of a posture held against gravity for an extended period.'],
                        ['term' => 'Echopraxia', 'definition' => 'Involuntary, mechanical imitation of another person\'s motor movements.'],
                        ['term' => 'Mutism', 'definition' => 'No, or very little, verbal response (not applicable if aphasia is established).']
                    ],
                    'diagnostic_criteria' => 'The clinical picture is dominated by at least 3 of the 12 symptoms: Stupor, Catalepsy, Waxy flexibility, Mutism, Negativism, Posturing, Mannerism, Stereotypy, Agitation (not influenced by external stimuli), Grimacing, Echolalia, Echopraxia.',
                    'specifiers' => 'Catatonia associated with another mental disorder (catatonia specifier); Catatonic disorder due to another medical condition; Unspecified catatonia.',
                    'differential_diagnosis' => 'Neuroleptic Malignant Syndrome (NMS): Medication-induced reaction featuring severe rigidity, high fever, elevated creatine kinase, and autonomic lability. Akathisia: Subjective motor restlessness and urge to move, distinct from catatonic posturing.',
                    'clinical_example' => 'Educational Case Scenario: A 28-year-old in a psychiatric unit sits motionless for hours (stupor), maintains his arm in the air when lifted by the examiner (catalepsy), resists verbal requests without motive (negativism), and mechanically mirrors the doctor\'s hand gestures (echopraxia). Having 4 features, Catatonia specifier is confirmed.',
                    'summary_notes' => '• Requires 3 or more of 12 recognized psychomotor symptoms.\n• Classified as a specifier for mood disorders, schizophrenia, and other medical conditions.\n• Waxy flexibility, catalepsy, stupor, mutism, and echopraxia are hallmark features.',
                    'questions' => [
                        [
                            'question' => 'How many of the 12 characteristic psychomotor symptoms must be present to assign the DSM-5 Catatonia specifier?',
                            'choice_a' => 'At least 1 symptom',
                            'choice_b' => 'At least 3 symptoms',
                            'choice_c' => 'At least 7 symptoms',
                            'choice_d' => 'All 12 symptoms must be present simultaneously',
                            'correct_answer' => 'B',
                            'explanation' => 'DSM-5 requires the presence of 3 or more of the 12 diagnostic motor symptoms (such as stupor, catalepsy, waxy flexibility, mutism, posturing, etc.) to diagnose Catatonia.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                6 => [
                    'title' => 'Etiology, Neurobiology, and Course',
                    'slug' => 'schizophrenia-neurobiology-course',
                    'description' => 'Dopamine pathways, structural ventriculomegaly, hypofrontality, and developmental course.',
                    'part_type' => 'learn',
                    'overview' => 'The neurobiology of schizophrenia involves complex polygenic liability (heritability ~80%) interacting with perinatal insults and synaptic pruning. The dopamine hypothesis posits mesolimbic hyperdopaminergia (driving positive symptoms like delusions/hallucinations) alongside mesocortical hypodopaminergia (mediating negative and cognitive symptoms). Neuroimaging consistently demonstrates lateral and third ventriculomegaly and reduced prefrontal gray matter volume (hypofrontality).',
                    'key_concepts' => [
                        ['title' => 'Dopamine Pathway Dualism', 'icon' => '⚡', 'definition' => 'Mesolimbic excess = positive symptoms; Mesocortical deficit = negative and cognitive symptoms.'],
                        ['title' => 'Ventriculomegaly', 'icon' => '🧠', 'definition' => 'Enlargement of lateral and third cerebral ventricles reflecting cortical tissue reduction.'],
                        ['title' => 'Excessive Synaptic Pruning', 'icon' => '✂️', 'definition' => 'Aberrant microglial pruning of cortical synapses during late adolescence triggers active psychosis.']
                    ],
                    'important_terms' => [
                        ['term' => 'Hypofrontality', 'definition' => 'Decreased blood flow and glucose metabolism in the prefrontal cortex during working memory tasks.'],
                        ['term' => 'Mesolimbic Pathway', 'definition' => 'Dopaminergic projection from VTA to nucleus accumbens implicated in positive psychotic symptoms.']
                    ],
                    'diagnostic_criteria' => 'Biological correlates supporting clinical evaluation; no laboratory test is solely diagnostic.',
                    'specifiers' => 'Continuous vs. episodic course specifiers.',
                    'differential_diagnosis' => 'Anti-NMDA Receptor Encephalitis: Autoimmune condition in young patients presenting with rapid psychiatric psychosis, seizures, and autonomic instability.',
                    'clinical_example' => 'Educational Case Scenario: Structural MRI of a 23-year-old with treatment-resistant schizophrenia reveals bilateral lateral ventricular enlargement and reduced volume of the superior temporal gyrus, classic neuroanatomical correlates of the disorder.',
                    'summary_notes' => '• Dopamine hypothesis: Mesolimbic hyperactivity (positive symptoms) vs. Mesocortical hypoactivity (negative symptoms).\n• Ventricular enlargement (ventriculomegaly) is a robust structural finding.\n• Peak onset occurs in late adolescence to mid-20s for males, and late 20s to early 30s for females.',
                    'questions' => [
                        [
                            'question' => 'According to prevailing neurobiological models of schizophrenia, which dopaminergic pathway alteration is primarily linked to positive symptoms such as hallucinations and delusions?',
                            'choice_a' => 'Hyperactivity within the mesolimbic pathway',
                            'choice_b' => 'Hypoactivity within the tuberoinfundibular pathway',
                            'choice_c' => 'Complete destruction of the nigrostriatal pathway',
                            'choice_d' => 'Hyperactivity within the cerebellar peduncle',
                            'correct_answer' => 'A',
                            'explanation' => 'Positive symptoms (hallucinations, delusions) are strongly associated with hyperactivity in the mesolimbic dopamine pathway, whereas negative symptoms relate to hypodopaminergia in mesocortical projections.',
                            'difficulty' => 'advanced',
                        ]
                    ]
                ],
                7 => [
                    'title' => 'Specifiers and Culture-Related Considerations',
                    'slug' => 'psychosis-specifiers-culture',
                    'description' => 'Course specifiers, culture-bound syndromes, and distinguishing cultural beliefs from delusions.',
                    'part_type' => 'learn',
                    'overview' => 'Cultural and religious context must be rigorously evaluated before designating an idea as a delusion or a sensory experience as a hallucination. In some subcultures, visual or auditory spiritual communications during bereavement or religious trance are expected and valued phenomena. DSM-5 explicitly states that ideas held by a person’s cultural or religious collective are not delusions. Furthermore, course specifiers in DSM-5 characterize the chronicity of episodes and remission status.',
                    'key_concepts' => [
                        ['title' => 'Cultural Relativity', 'icon' => '🌍', 'definition' => 'Beliefs and perceptions normative to an individual\'s cultural or religious group cannot be diagnosed as psychosis.'],
                        ['title' => 'Course Specifiers', 'icon' => '📈', 'definition' => 'First episode vs. multiple episodes; acute episode, partial remission, full remission.'],
                        ['title' => 'Cultural Syndromes', 'icon' => '📜', 'definition' => 'Entities like Ataque de nervios or Ghost Sickness, reflecting idioms of distress rather than classic schizophrenia.']
                    ],
                    'important_terms' => [
                        ['term' => 'Idioms of Distress', 'definition' => 'Culturally specific ways of expressing and communicating personal or social suffering.'],
                        ['term' => 'Attenuated Psychosis Syndrome', 'definition' => 'Condition in DSM-5 Section III for further study characterized by mild subthreshold psychotic symptoms.']
                    ],
                    'diagnostic_criteria' => 'Delusions and hallucinations must not be part of an accepted cultural or religious practice.',
                    'specifiers' => 'First episode, currently in acute episode; First episode, currently in partial remission; Multiple episodes; Continuous.',
                    'differential_diagnosis' => 'Culture-Sanctioned Spirit Possession: Transient state during religious ritual where identity is assumed by an entity, without enduring functional decline.',
                    'clinical_example' => 'Educational Case Scenario: A devout parishioner reports hearing the comforting voice of her recently deceased mother praying with her during evening devotions. She experiences no distress, no functional impairment, and her community affirms this bereavement practice. This is not a psychotic hallucination.',
                    'summary_notes' => '• Beliefs accepted by an individual\'s culture or subculture are NOT delusions.\n• Course specifiers only apply after 1 year has elapsed since initial onset of disorder.\n• Always evaluate functional decline and distress relative to the patient\'s sociocultural milieu.',
                    'questions' => [
                        [
                            'question' => 'Under DSM-5 guidelines, how should a clinician evaluate perceptual experiences or beliefs that are widely shared and accepted within a patient\'s religious or cultural group?',
                            'choice_a' => 'Classify them immediately as bizarre delusions',
                            'choice_b' => 'Diagnose Brief Psychotic Disorder automatically',
                            'choice_c' => 'Do not diagnose them as delusions or hallucinations if they are normative within the cultural/religious context',
                            'choice_d' => 'Prescribe involuntary antipsychotics immediately',
                            'correct_answer' => 'C',
                            'explanation' => 'DSM-5 explicitly emphasizes that beliefs and experiences that are normative within an individual\'s cultural, ethnic, or religious group are not classified as delusions or hallucinations.',
                            'difficulty' => 'beginner',
                        ]
                    ]
                ],
                8 => [
                    'title' => 'Differential Diagnosis of Psychotic Conditions',
                    'slug' => 'differential-diagnosis-psychosis',
                    'description' => 'Dissecting schizophrenia vs. mood disorders, medical etiologies, and substance-induced states.',
                    'part_type' => 'learn',
                    'overview' => 'Differential diagnosis is the cornerstone of clinical evaluation on the psychotic spectrum. Medical conditions (systemic lupus erythematosus, neurosyphilis, Wilson\'s disease, temporal lobe epilepsy) and substances (methamphetamine, PCP, synthetic cannabinoids, alcohol withdrawal) can mimic primary psychosis. Clinicians must establish a timeline: did psychosis persist during periods of sustained sobriety (> 1 month)? Were mood symptoms present concurrently or independently?',
                    'key_concepts' => [
                        ['title' => 'Substance-Induced Psychosis', 'icon' => '🧪', 'definition' => 'Psychotic symptoms developing during or soon after substance intoxication or withdrawal, resolving with sobriety.'],
                        ['title' => 'Medical Secondary Psychosis', 'icon' => '🩺', 'definition' => 'Psychotic symptoms direct physiological consequence of a medical condition (e.g., encephalitis, hyperthyroidism).'],
                        ['title' => 'Major Depression with Psychotic Features', 'icon' => '🌧️', 'definition' => 'Psychotic symptoms occur strictly during depressive episodes and resolve when depression remits.']
                    ],
                    'important_terms' => [
                        ['term' => 'Substance/Medication-Induced Psychotic Disorder', 'definition' => 'Psychosis exceeding normative intoxication, resolving within 1 month of cessation.'],
                        ['term' => 'Temporal Lobe Epilepsy', 'definition' => 'Neurological seizure focus causing olfactory/gustatory hallucinations and complex partial status.']
                    ],
                    'diagnostic_criteria' => 'Rule out substance use and medical conditions via toxicology, neurological exam, and lab work.',
                    'specifiers' => 'With onset during intoxication; With onset during withdrawal.',
                    'differential_diagnosis' => 'Schizophrenia vs. Bipolar I with Psychotic Features: Psychosis in bipolar occurs during mania or depression; between episodes, mood and reality testing normalize.',
                    'clinical_example' => 'Educational Case Scenario: A 30-year-old with no psychiatric history presents with sudden paranoid delusions and hyperactive motor behavior 2 days after heavy methamphetamine bingeing. Urine drug screen is positive for amphetamines. Symptoms resolve completely 72 hours after hospitalization, confirming Substance-Induced Psychotic Disorder.',
                    'summary_notes' => '• Substance-Induced Psychosis resolves within 1 month of cessation of drug exposure.\n• Primary psychotic disorders require symptoms persisting without acute substance toxicity.\n• Mood with Psychotic Features: psychosis occurs ONLY during mood episodes.',
                    'questions' => [
                        [
                            'question' => 'A 26-year-old patient develops auditory hallucinations and persecutory delusions while heavily abusing cocaine. Three days after complete medical detoxification in a closed ward, the hallucinations vanish completely and reality testing is fully intact. What is the most accurate diagnosis?',
                            'choice_a' => 'Schizophrenia, Paranoid Type',
                            'choice_b' => 'Substance/Medication-Induced Psychotic Disorder',
                            'choice_c' => 'Schizoaffective Disorder, Bipolar Type',
                            'choice_d' => 'Delusional Disorder',
                            'correct_answer' => 'B',
                            'explanation' => 'Because the psychotic symptoms emerged in the context of cocaine abuse and resolved promptly upon detoxification and cessation, the correct diagnosis is Substance/Medication-Induced Psychotic Disorder.',
                            'difficulty' => 'intermediate',
                        ]
                    ]
                ],
                9 => [
                    'title' => 'Educational Case Scenarios & Clinical Formulations',
                    'slug' => 'educational-case-scenarios-psychosis',
                    'description' => 'Complex psychopathology case scenarios dissecting schizoaffective vs. schizophrenia vs. bipolar.',
                    'part_type' => 'scenario',
                    'overview' => 'This section presents complex multi-faceted case scenarios. Board examination questions frequently test the boundary between Schizophrenia with secondary depressive symptoms, Schizoaffective Disorder, and Bipolar I with psychotic features. Success requires mapping the timeline of mood episodes against the timeline of psychotic episodes.',
                    'key_concepts' => [
                        ['title' => 'Timeline Mapping Method', 'icon' => '🗺️', 'definition' => 'Drawing parallel timelines of mood symptoms versus psychotic symptoms to determine diagnostic class.'],
                        ['title' => 'Mood Proportion Calculation', 'icon' => '📊', 'definition' => 'Determining if mood symptoms occupied a majority of the active and residual phases.'],
                        ['title' => 'Clinical Formulation', 'icon' => '📝', 'definition' => 'Synthesizing genetic risk, premorbid history, prodromal signs, and active symptom presentations.']
                    ],
                    'important_terms' => [
                        ['term' => 'Euthymic Psychosis', 'definition' => 'The presence of active psychotic symptoms during a period when mood is normal/neutral.'],
                        ['term' => 'Psychotic Depression', 'definition' => 'Major depressive episode accompanied by mood-congruent (guilt, nihilism) or mood-incongruent delusions.']
                    ],
                    'diagnostic_criteria' => 'Comparative criteria application across Schizophrenia, Schizoaffective, and Bipolar Disorder.',
                    'specifiers' => 'Differential specifiers evaluation.',
                    'differential_diagnosis' => 'Comprehensive differential tree for psychopathology licensure.',
                    'clinical_example' => 'Educational Case Scenario: A 27-year-old male has had auditory hallucinations continuously for 2 years. During this 2-year period, he had one 4-week period of hypomania. Because mood symptoms were brief and did not occupy a significant portion of the total illness duration, the diagnosis remains Schizophrenia, not Schizoaffective Disorder.',
                    'summary_notes' => '• If mood symptoms occur for only a minor fraction of the total chronic psychotic course, the diagnosis is Schizophrenia.\n• If mood symptoms occupy the majority of the active/residual duration, and there are 2+ weeks of psychosis without mood, it is Schizoaffective.',
                    'questions' => [
                        [
                            'question' => 'Educational Case Scenario: An individual has experienced continuous persecutory delusions and disorganized speech for 3 full years. During these 3 years, she experienced one 3-week major depressive episode. How should this condition be diagnosed under DSM-5?',
                            'choice_a' => 'Schizoaffective Disorder, Depressive Type',
                            'choice_b' => 'Schizophrenia',
                            'choice_c' => 'Major Depressive Disorder with Psychotic Features',
                            'choice_d' => 'Bipolar I Disorder',
                            'correct_answer' => 'B',
                            'explanation' => 'For Schizoaffective Disorder, mood episodes must be present for the majority of the total duration of the illness. Because the 3-week depressive episode represents only a minor fraction of the 3-year psychotic course, the diagnosis is Schizophrenia.',
                            'difficulty' => 'advanced',
                        ]
                    ]
                ],
                10 => [
                    'title' => 'Chapter Mastery & Comprehensive Board Review',
                    'slug' => 'schizophrenia-spectrum-mastery',
                    'description' => 'Complete chapter synthesis of psychotic spectrum psychopathology in preparation for The Hallucinatory Eidolon.',
                    'part_type' => 'mastery',
                    'overview' => 'Part 10 consolidates the entire Schizophrenia Spectrum and Other Psychotic Disorders curriculum. We have mastered the 5 core domains, the time continuum (< 1 month for Brief Psychotic, 1 to < 6 months for Schizophreniform, 6+ months for Schizophrenia), the 2-week non-mood psychotic window for Schizoaffective Disorder, transdiagnostic catatonia (3 of 12 signs), dopamine pathways, and cultural considerations. You are ready to face Chapter Boss: The Hallucinatory Eidolon.',
                    'key_concepts' => [
                        ['title' => 'Time Continuum Master Rule', 'icon' => '⏱️', 'definition' => '< 1 mo = Brief Psychotic; 1 to < 6 mo = Schizophreniform; 6+ mo = Schizophrenia.'],
                        ['title' => 'Schizoaffective Anchor', 'icon' => '⚓', 'definition' => '2+ weeks of delusions/hallucinations without mood + mood present for majority of total illness.'],
                        ['title' => 'Catatonia Anchor', 'icon' => '⚡', 'definition' => 'Requires 3+ of 12 signs; transdiagnostic specifier.']
                    ],
                    'important_terms' => [
                        ['term' => 'The Hallucinatory Eidolon', 'definition' => 'The Chapter Boss entity testing mastery of the psychotic disorders curriculum.'],
                        ['term' => 'Differential Mastery', 'definition' => 'Ability to instantly separate primary, secondary, and mood-related psychotic conditions.']
                    ],
                    'diagnostic_criteria' => 'Full DSM-5 Schizophrenia Spectrum section criteria review.',
                    'specifiers' => 'All domain specifiers.',
                    'differential_diagnosis' => 'Comprehensive spectrum review.',
                    'clinical_example' => 'Educational Case Synthesis: Rapid differential diagnosis of a patient presenting in emergency psych status with acute auditory hallucinations and motor agitation.',
                    'summary_notes' => '• Time: Brief Psychotic (<1 mo) -> Schizophreniform (1-6 mo) -> Schizophrenia (6+ mo).\n• Criterion A requires at least one of: Delusions, Hallucinations, Disorganized Speech.\n• Schizoaffective requires 2 weeks of psychosis without mood symptoms.\n• Catatonia requires 3 of 12 motor features.',
                    'questions' => [
                        [
                            'question' => 'Board-Exam Synthesis: Which diagnostic statement accurately reflects DSM-5 criteria across the Schizophrenia Spectrum?',
                            'choice_a' => 'Schizophreniform Disorder requires continuous symptoms for at least 1 year',
                            'choice_b' => 'Schizoaffective Disorder requires at least 2 weeks of delusions or hallucinations without major mood episodes during the lifetime illness',
                            'choice_c' => 'Catatonia is an independent subtype that can only be diagnosed within Schizophrenia',
                            'choice_d' => 'Brief Psychotic Disorder requires at least 6 months of negative symptoms',
                            'correct_answer' => 'B',
                            'explanation' => 'Under DSM-5 Criterion B for Schizoaffective Disorder, there must be delusions or hallucinations for at least 2 weeks in the absence of a major mood episode at some point in the illness.',
                            'difficulty' => 'board_exam',
                        ]
                    ]
                ]
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // DOMAINS 3 TO 19 GENERATOR HELPER
    // ─────────────────────────────────────────────────────────────
    private static function getBipolarDomain(): array {
        return self::buildDomain(
            3,
            'Bipolar and Related Disorders',
            'bipolar-and-related-disorders',
            'Disorders characterized by severe mood fluctuations between manic or hypomanic states and major depressive episodes.',
            [
                'Mood Architecture: Manic vs. Hypomanic vs. Depressive',
                'Bipolar I Disorder: Diagnostic Thresholds',
                'Bipolar II Disorder: Hypomania & Depression',
                'Cyclothymic Disorder',
                'Substance-Induced & Medical Bipolar',
                'Course Specifiers: Mixed Features & Rapid Cycling',
                'Heritability, Biological Rhythms & Suicide Risk',
                'Differential Diagnosis: Bipolar vs. BPD vs. MDD vs. ADHD',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Mood Architecture: Defining Manic, Hypomanic, and Depressive Episodes', 'slug' => 'mood-architecture-episodes'],
                2 => ['title' => 'Bipolar I Disorder: Manic Thresholds and Severity', 'slug' => 'bipolar-i-disorder-criteria'],
                3 => ['title' => 'Bipolar II Disorder: Hypomania and Major Depressive Episodes', 'slug' => 'bipolar-ii-disorder-criteria'],
                4 => ['title' => 'Cyclothymic Disorder: Subthreshold Mood Fluctuation', 'slug' => 'cyclothymic-disorder-criteria'],
                5 => ['title' => 'Substance/Medication-Induced and Medical Bipolar Disorders', 'slug' => 'substance-medical-bipolar'],
                6 => ['title' => 'Course Specifiers: Anxious Distress, Mixed Features, and Rapid Cycling', 'slug' => 'bipolar-course-specifiers'],
                7 => ['title' => 'Etiology, Genetics, Circadian Disruption, and Suicide Risk', 'slug' => 'bipolar-etiology-suicide-risk'],
                8 => ['title' => 'Differential Diagnosis: Bipolar I vs. Bipolar II vs. Borderline vs. ADHD', 'slug' => 'bipolar-differential-diagnosis'],
                9 => ['title' => 'Educational Case Scenarios & Diagnostic Decision Trees', 'slug' => 'educational-case-scenarios-bipolar'],
                10 => ['title' => 'Chapter Mastery & Comprehensive Board Review', 'slug' => 'bipolar-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Bipolar I requires at least one lifetime MANIC episode (lasting >= 1 week or requiring hospitalization). Bipolar II requires at least one HYPOMANIC episode (>= 4 consecutive days) AND at least one MAJOR DEPRESSIVE episode, with NO history of full mania. Rapid cycling requires 4+ mood episodes in 12 months.',
            'Bipolar I: Manic episode (>= 1 week, marked impairment/hospitalization). Bipolar II: Hypomanic (>= 4 days, uncharacteristic change, NO marked impairment) + Major Depressive Episode. Cyclothymic: 2 years (1 year in youth) subthreshold hypomanic and depressive periods.',
            'Bipolar vs. Borderline Personality: Bipolar mood episodes are episodic lasting weeks/months; BPD affective instability shifts within hours in response to interpersonal stressors.',
            [
                [
                    'question' => 'What is the minimum duration required for an abnormally elevated or irritable mood to meet the DSM-5 criteria for a Manic Episode (assuming hospitalization is not required)?',
                    'choice_a' => 'At least 24 hours',
                    'choice_b' => 'At least 4 consecutive days',
                    'choice_c' => 'At least 1 week',
                    'choice_d' => 'At least 1 month',
                    'correct_answer' => 'C',
                    'explanation' => 'Under DSM-5, a Manic Episode requires a distinct period of abnormally and persistently elevated, expansive, or irritable mood lasting at least 1 week and present most of the day, nearly every day (or any duration if hospitalization is necessary).',
                    'difficulty' => 'beginner',
                ],
                [
                    'question' => 'A patient has a verified history of recurrent Major Depressive Episodes and multiple episodes of Hypomania lasting 5 days each, but has NEVER experienced a full Manic episode. What is the correct DSM-5 diagnosis?',
                    'choice_a' => 'Bipolar I Disorder',
                    'choice_b' => 'Bipolar II Disorder',
                    'choice_c' => 'Cyclothymic Disorder',
                    'choice_d' => 'Major Depressive Disorder, Single Episode',
                    'correct_answer' => 'B',
                    'explanation' => 'Bipolar II Disorder requires at least one hypomanic episode AND at least one major depressive episode, with the explicit absence of any lifetime full manic episodes.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getDepressiveDomain(): array {
        return self::buildDomain(
            4,
            'Depressive Disorders',
            'depressive-disorders',
            'Presence of sad, empty, or irritable mood accompanied by somatic and cognitive changes that significantly affect the capacity to function.',
            [
                'Depressive Spectrum & Major Depressive Disorder',
                'Persistent Depressive Disorder (Dysthymia)',
                'Disruptive Mood Dysregulation Disorder (DMDD)',
                'Premenstrual Dysphoric Disorder (PMDD)',
                'Diagnostic Features & Specifiers (Melancholic, Atypical)',
                'Etiology, Cognitive Models & Neurobiology',
                'Risk Factors, Suicide Assessment & Course',
                'Differential Diagnosis: MDD vs. Bereavement vs. Bipolar',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Depressive Spectrum Overview & Major Depressive Disorder (MDD 5-of-9 Criteria)', 'slug' => 'mdd-diagnostic-criteria'],
                2 => ['title' => 'Persistent Depressive Disorder (Dysthymia: 2-Year Continuum)', 'slug' => 'persistent-depressive-disorder-dysthymia'],
                3 => ['title' => 'Disruptive Mood Dysregulation Disorder (DMDD in Children)', 'slug' => 'disruptive-mood-dysregulation-disorder'],
                4 => ['title' => 'Premenstrual Dysphoric Disorder & Substance-Induced Depression', 'slug' => 'pmdd-substance-depression'],
                5 => ['title' => 'Diagnostic Features & Specifiers: Melancholic, Atypical, and Peripartum', 'slug' => 'depressive-specifiers-melancholic-atypical'],
                6 => ['title' => 'Development, Course, Cognitive Triad, and Neural Circuitry', 'slug' => 'depressive-course-cognitive-models'],
                7 => ['title' => 'Risk & Prognostic Factors, Suicide Lethality, and Demographics', 'slug' => 'depressive-risk-suicide-assessment'],
                8 => ['title' => 'Differential Diagnosis: MDD vs. Bereavement vs. Bipolar vs. Adjustment', 'slug' => 'depressive-differential-diagnosis'],
                9 => ['title' => 'Educational Case Review: Clinical Formulations and Symptom Dissection', 'slug' => 'educational-case-scenarios-depression'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'depressive-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Major Depressive Disorder requires 5 of 9 symptoms for at least 2 weeks, with at least one symptom being depressed mood or anhedonia. Persistent Depressive Disorder (Dysthymia) requires depressed mood for at least 2 years (1 year in children/adolescents). DMDD was created for children ages 6-18 with severe recurrent temper outbursts and chronic irritability to address overdiagnosis of pediatric bipolar disorder.',
            'MDD: 5 of 9 symptoms for 2 weeks (SIGECAPS: Sleep, Interest, Guilt, Energy, Concentration, Appetite, Psychomotor, Suicide). PDD: 2 years depressed mood + 2 of 6 symptoms. DMDD: Temper outbursts 3+ times/week for 12 months with onset before age 10.',
            'Bereavement vs. MDD: Bereavement feelings come in waves (pangs of grief) with preserved self-esteem; MDD involves persistent dysphoria, self-loathing, and pervasive anhedonia.',
            [
                [
                    'question' => 'To meet the DSM-5 criteria for a Major Depressive Episode, which of the following is one of the two mandatory core symptoms that MUST be present among the 5 required symptoms?',
                    'choice_a' => 'Insomnia or hypersomnia',
                    'choice_b' => 'Depressed mood or loss of interest/pleasure (anhedonia)',
                    'choice_c' => 'Feelings of worthlessness or excessive guilt',
                    'choice_d' => 'Psychomotor agitation or retardation',
                    'correct_answer' => 'B',
                    'explanation' => 'DSM-5 Criterion A for Major Depressive Disorder requires at least 5 of 9 symptoms during a 2-week period, and at least ONE of the symptoms must be either (1) depressed mood or (2) loss of interest or pleasure (anhedonia).',
                    'difficulty' => 'beginner',
                ],
                [
                    'question' => 'What is the required minimum duration of depressed mood for diagnosing Persistent Depressive Disorder (Dysthymia) in adults under DSM-5?',
                    'choice_a' => '6 months',
                    'choice_b' => '1 year',
                    'choice_c' => '2 years',
                    'choice_d' => '5 years',
                    'correct_answer' => 'C',
                    'explanation' => 'For Persistent Depressive Disorder (Dysthymia) in adults, depressed mood must be present for most of the day, for more days than not, for at least 2 years (1 year for children and adolescents).',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getAnxietyDomain(): array {
        return self::buildDomain(
            5,
            'Anxiety Disorders',
            'anxiety-disorders',
            'Disorders that share features of excessive fear and anxiety and related behavioral disturbances.',
            [
                'Fear vs. Anxiety Foundations & GAD',
                'Panic Disorder & Panic Attack Specifier',
                'Agoraphobia: Fear of Escape & Public Spaces',
                'Specific Phobia: Types & Conditioning',
                'Social Anxiety Disorder (Social Phobia)',
                'Separation Anxiety & Selective Mutism',
                'Amygdala Pathways & Neurobiology',
                'Differential Diagnosis: Panic vs. Cardiac vs. GAD',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Fear vs. Anxiety Foundations & Generalized Anxiety Disorder (GAD)', 'slug' => 'gad-diagnostic-criteria'],
                2 => ['title' => 'Panic Disorder & The Panic Attack Specifier', 'slug' => 'panic-disorder-criteria'],
                3 => ['title' => 'Agoraphobia: Fear of 2+ Public/Enclosed/Open Spaces', 'slug' => 'agoraphobia-criteria'],
                4 => ['title' => 'Specific Phobia: Subtypes, Conditioning, and Avoidance', 'slug' => 'specific-phobia-types'],
                5 => ['title' => 'Social Anxiety Disorder (Social Phobia & Performance Specifier)', 'slug' => 'social-anxiety-disorder-criteria'],
                6 => ['title' => 'Separation Anxiety Disorder & Selective Mutism', 'slug' => 'separation-anxiety-selective-mutism'],
                7 => ['title' => 'Biological Pathways, Amygdala Hyperactivity, and Course', 'slug' => 'anxiety-neurobiology-course'],
                8 => ['title' => 'Differential Diagnosis: Panic vs. Medical/Thyroid vs. Social Phobia', 'slug' => 'anxiety-differential-diagnosis'],
                9 => ['title' => 'Educational Case Scenarios: Diagnostic Differential Analysis', 'slug' => 'educational-case-scenarios-anxiety'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Review', 'slug' => 'anxiety-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Generalized Anxiety Disorder requires excessive anxiety and worry for at least 6 months about a number of events. Panic Disorder requires recurrent unexpected panic attacks followed by 1+ month of persistent worry about subsequent attacks. Agoraphobia requires marked fear/anxiety in at least 2 of 5 specific situations. In DSM-5, Agoraphobia is an independent diagnosis, no longer subordinate to panic disorder.',
            'GAD: 6 months of worry + 3 of 6 physical symptoms (1 in children: restlessness, fatigue, concentration, irritability, muscle tension, sleep). Panic: 4 of 13 physical/cognitive symptoms surging within minutes. Specific Phobia: 6+ months of out-of-proportion fear.',
            'Panic Attack vs. Panic Disorder: A panic attack is an abrupt surge of intense fear (specifier); Panic Disorder requires recurrent UNEXPECTED attacks plus 1 month of anticipatory worry or maladaptive behavioral change.',
            [
                [
                    'question' => 'How did the DSM-5 structurally reorganize the relationship between Panic Disorder and Agoraphobia?',
                    'choice_a' => 'Agoraphobia was removed entirely from the DSM-5',
                    'choice_b' => 'Agoraphobia and Panic Disorder are now coded as separate, independent diagnoses',
                    'choice_c' => 'Panic Disorder can only be diagnosed if Agoraphobia is present',
                    'choice_d' => 'Agoraphobia was reclassified as an obsessive-compulsive subtype',
                    'correct_answer' => 'B',
                    'explanation' => 'In DSM-5, Agoraphobia and Panic Disorder are uncoupled and classified as two independent diagnoses; a patient can be diagnosed with Panic Disorder, Agoraphobia, or both concurrently.',
                    'difficulty' => 'intermediate',
                ],
                [
                    'question' => 'What is the required minimum duration of excessive anxiety and worry for a diagnosis of Generalized Anxiety Disorder (GAD) under DSM-5?',
                    'choice_a' => 'At least 1 month',
                    'choice_b' => 'At least 3 months',
                    'choice_c' => 'At least 6 months',
                    'choice_d' => 'At least 1 year',
                    'correct_answer' => 'C',
                    'explanation' => 'DSM-5 Criterion A for GAD requires excessive anxiety and worry occurring more days than not for at least 6 months, about a number of events or activities.',
                    'difficulty' => 'beginner',
                ]
            ]
        );
    }

    private static function getOCDDomain(): array {
        return self::buildDomain(
            6,
            'Obsessive-Compulsive and Related Disorders',
            'obsessive-compulsive-related-disorders',
            'Conditions characterized by obsessions and compulsions, or preoccupations with body appearance, hoarding, and body-focused repetitive behaviors.',
            [
                'Obsessions vs. Compulsions & The OCRD Chapter',
                'Obsessive-Compulsive Disorder (OCD)',
                'Body Dysmorphic Disorder (BDD)',
                'Hoarding Disorder: Criteria & Clutter',
                'Trichotillomania & Excoriation Disorder',
                'Insight Specifiers & Tic-Related Subtypes',
                'Cortico-Striato-Thalamo-Cortical (CSTC) Circuits',
                'Differential Diagnosis: OCD vs. OCPD vs. Phobia',
                'Educational Case Vignettes',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Obsessions vs. Compulsions & The OCRD Diagnostic Realm', 'slug' => 'obsessions-vs-compulsions-foundations'],
                2 => ['title' => 'Obsessive-Compulsive Disorder (OCD: Contamination, Symmetry, Harm)', 'slug' => 'ocd-diagnostic-criteria'],
                3 => ['title' => 'Body Dysmorphic Disorder (BDD & Muscle Dysmorphia)', 'slug' => 'body-dysmorphic-disorder-criteria'],
                4 => ['title' => 'Hoarding Disorder: Difficulty Discarding & Living Space Clutter', 'slug' => 'hoarding-disorder-criteria'],
                5 => ['title' => 'Body-Focused Repetitive Behaviors: Trichotillomania & Excoriation', 'slug' => 'trichotillomania-excoriation-disorder'],
                6 => ['title' => 'Insight Specifiers (Good/Fair, Poor, Delusional) & Tic Subtypes', 'slug' => 'ocrd-insight-specifiers'],
                7 => ['title' => 'Cortico-Striato-Thalamo-Cortical (CSTC) Circuits & Models', 'slug' => 'ocrd-neurobiology-etiology'],
                8 => ['title' => 'Differential Diagnosis: OCD vs. OCPD vs. Psychosis vs. Illness Anxiety', 'slug' => 'ocrd-differential-diagnosis'],
                9 => ['title' => 'Educational Case Vignettes: Evaluating Compulsive Patterns', 'slug' => 'educational-case-scenarios-ocrd'],
                10 => ['title' => 'Chapter Mastery & Comprehensive Board Review', 'slug' => 'ocrd-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'In DSM-5, OCD was moved OUT of the Anxiety Disorders chapter and placed into its own dedicated chapter. Obsessions are recurrent intrusive thoughts/urges; Compulsions are repetitive behaviors/mental acts aimed at preventing anxiety. BDD involves perceived appearance flaws not observable to others. Hoarding Disorder is newly recognized as an independent disorder.',
            'OCD: Obsessions and/or compulsions taking > 1 hour/day or causing significant distress. Insight specifier: Good/fair, Poor, Absent/delusional insight. Tic-related specifier: current or past history of a tic disorder.',
            'OCD vs. OCPD: OCD involves ego-dystonic obsessions and compulsions that the person wishes would stop; Obsessive-Compulsive Personality Disorder (OCPD) is ego-syntonic, marked by pervasive perfectionism and orderliness without intrusive obsessions.',
            [
                [
                    'question' => 'How did the DSM-5 conceptually reorganize Obsessive-Compulsive Disorder (OCD) in relation to Anxiety Disorders?',
                    'choice_a' => 'Merged OCD and Generalized Anxiety Disorder into a single entity',
                    'choice_b' => 'Removed OCD from Anxiety Disorders and established a new dedicated chapter: Obsessive-Compulsive and Related Disorders',
                    'choice_c' => 'Reclassified OCD as a severe personality disorder',
                    'choice_d' => 'Designated OCD as an exclusively pediatric disorder',
                    'correct_answer' => 'B',
                    'explanation' => 'The DSM-5 moved OCD out of the Anxiety Disorders chapter and created a new independent diagnostic category: Obsessive-Compulsive and Related Disorders (including BDD, Hoarding, Trichotillomania, and Excoriation).',
                    'difficulty' => 'beginner',
                ],
                [
                    'question' => 'What is the key clinical distinction between Obsessive-Compulsive Disorder (OCD) and Obsessive-Compulsive Personality Disorder (OCPD)?',
                    'choice_a' => 'OCD symptoms are ego-dystonic (intrusive and distressing), whereas OCPD patterns are ego-syntonic (perceived as correct and desirable)',
                    'choice_b' => 'OCD has no genetic component whereas OCPD is 100% inherited',
                    'choice_c' => 'OCPD always presents with auditory hallucinations',
                    'choice_d' => 'OCD only occurs after age 50',
                    'correct_answer' => 'A',
                    'explanation' => 'In OCD, obsessions and compulsions are typically ego-dystonic (the person recognizes them as senseless and intrusive), whereas in OCPD, traits of perfectionism, rigidity, and control are ego-syntonic (congruent with the self-concept).',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getTraumaDomain(): array {
        return self::buildDomain(
            7,
            'Trauma- and Stressor-Related Disorders',
            'trauma-and-stressor-related-disorders',
            'Disorders in which exposure to a traumatic or stressful event is listed explicitly as an indispensable diagnostic criterion.',
            [
                'Criterion A Trauma & The Stress Continuum',
                'Posttraumatic Stress Disorder (PTSD): 4 Clusters',
                'PTSD in Children 6 Years and Younger',
                'Acute Stress Disorder (ASD: 3 Days to 1 Month)',
                'Adjustment Disorders: Stressor-Proportionality',
                'Reactive Attachment (RAD) & Disinhibited Engagement (DSED)',
                'Dissociative Subtype & Neurobiological HPA Axis',
                'Differential Diagnosis: PTSD vs. ASD vs. TBI vs. Panic',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Trauma Exposure (Criterion A) & The Stress-Response Continuum', 'slug' => 'criterion-a-trauma-foundations'],
                2 => ['title' => 'Posttraumatic Stress Disorder (PTSD: Intrusion, Avoidance, Cognition/Mood, Arousal)', 'slug' => 'ptsd-diagnostic-criteria'],
                3 => ['title' => 'PTSD in Children 6 Years and Younger: Developmental Criteria', 'slug' => 'ptsd-preschool-children-criteria'],
                4 => ['title' => 'Acute Stress Disorder (ASD: 3 Days to 1 Month Duration)', 'slug' => 'acute-stress-disorder-criteria'],
                5 => ['title' => 'Adjustment Disorders: Marked Distress Out of Proportion', 'slug' => 'adjustment-disorders-criteria'],
                6 => ['title' => 'Reactive Attachment Disorder (RAD) & Disinhibited Social Engagement (DSED)', 'slug' => 'rad-dsed-criteria'],
                7 => ['title' => 'Dissociative Subtype of PTSD & Neurobiological Stress Axis', 'slug' => 'ptsd-dissociative-subtype-neurobiology'],
                8 => ['title' => 'Differential Diagnosis: PTSD vs. Acute Stress vs. Adjustment vs. Panic', 'slug' => 'trauma-differential-diagnosis'],
                9 => ['title' => 'Educational Case Scenarios: Evaluating Trauma Sequelae', 'slug' => 'educational-case-scenarios-trauma'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'trauma-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'PTSD requires exposure to actual/threatened death, serious injury, or sexual violence (Criterion A). Symptoms are organized into 4 clusters: 1) Intrusion, 2) Avoidance, 3) Negative alterations in cognitions and mood, 4) Marked alterations in arousal and reactivity, lasting > 1 month. Acute Stress Disorder lasts 3 days to 1 month.',
            'Criterion A: Direct experience, witnessing, learning of violent trauma to close family/friend, or repeated extreme exposure to aversive details. Dissociative subtype: with depersonalization or derealization. Delayed expression: full criteria not met until 6+ months after event.',
            'Acute Stress Disorder vs. PTSD: ASD is diagnosed when symptoms persist from 3 days up to 1 month post-trauma; if symptoms persist beyond 1 month, the diagnosis converts to PTSD.',
            [
                [
                    'question' => 'What is the diagnostic duration threshold that separates Acute Stress Disorder from Posttraumatic Stress Disorder (PTSD)?',
                    'choice_a' => 'Acute Stress Disorder lasts between 3 days and 1 month; beyond 1 month, it is classified as PTSD',
                    'choice_b' => 'Acute Stress Disorder must last at least 6 months',
                    'choice_c' => 'PTSD must resolve within 48 hours',
                    'choice_d' => 'There is no time distinction; they are differentiated only by patient gender',
                    'correct_answer' => 'A',
                    'explanation' => 'Acute Stress Disorder is diagnosed when symptoms develop within 3 days and resolve within 1 month after trauma exposure. If symptoms persist for more than 1 month, PTSD is the appropriate diagnosis.',
                    'difficulty' => 'beginner',
                ]
            ]
        );
    }

    private static function getDissociativeDomain(): array {
        return self::buildDomain(
            8,
            'Dissociative Disorders',
            'dissociative-disorders',
            'Disruption of and/or discontinuity in the normal integration of consciousness, memory, identity, emotion, perception, and behavior.',
            [
                'Dissociation as Defense & Disintegrated Functions',
                'Dissociative Identity Disorder (DID)',
                'Dissociative Amnesia: Localized & Selective',
                'Dissociative Fugue Specifier',
                'Depersonalization / Derealization Disorder',
                'Severe Trauma Etiology & Hypnotizability',
                'Cultural Considerations & Possession Trances',
                'Differential Diagnosis: DID vs. Schizophrenia vs. BPD',
                'Educational Case Vignettes',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Dissociation as Defense & Disruption of Integrated Functions', 'slug' => 'dissociation-foundations'],
                2 => ['title' => 'Dissociative Identity Disorder (DID: 2+ Distinct Personality States)', 'slug' => 'did-diagnostic-criteria'],
                3 => ['title' => 'Dissociative Amnesia: Localized, Selective, and Generalized Memory Gaps', 'slug' => 'dissociative-amnesia-criteria'],
                4 => ['title' => 'Dissociative Fugue Specifier: Purposeful Travel & Identity Confusion', 'slug' => 'dissociative-fugue-specifier'],
                5 => ['title' => 'Depersonalization / Derealization Disorder (Intact Reality Testing)', 'slug' => 'depersonalization-derealization-criteria'],
                6 => ['title' => 'Severe Developmental Trauma Pathogenesis & Memory Fragmentation', 'slug' => 'dissociative-etiology-trauma'],
                7 => ['title' => 'Cultural Considerations: Possession Trances & Idioms of Dissociation', 'slug' => 'dissociative-cultural-possession'],
                8 => ['title' => 'Differential Diagnosis: DID vs. Schizophrenia vs. Borderline vs. Malingering', 'slug' => 'dissociative-differential-diagnosis'],
                9 => ['title' => 'Educational Case Vignettes: Clinical Formulations', 'slug' => 'educational-case-scenarios-dissociative'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'dissociative-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Dissociative Identity Disorder involves disruption of identity characterized by 2 or more distinct personality states (Criterion A) and recurrent gaps in recall of everyday events or traumatic memories inconsistent with ordinary forgetting (Criterion B). Depersonalization/Derealization requires persistent detachment from self/surroundings with INTACT reality testing.',
            'DID: 2+ distinct personality states, recurrent amnesic gaps. Dissociative Amnesia: inability to recall important autobiographical info, usually traumatic. Specifier: With dissociative fugue (apparently purposeful travel or bewildered wandering).',
            'Depersonalization vs. Psychosis: In Depersonalization/Derealization Disorder, reality testing remains completely intact (the patient knows their perception is an internal illusion, not an external reality).',
            [
                [
                    'question' => 'Which critical feature must remain intact during episodes of Depersonalization / Derealization Disorder to confirm the diagnosis under DSM-5?',
                    'choice_a' => 'Reality testing',
                    'choice_b' => 'Continuous motor hyperreflexia',
                    'choice_c' => 'Complete amnesia for one\'s own name',
                    'choice_d' => 'Olfactory hallucinations',
                    'correct_answer' => 'A',
                    'explanation' => 'Under DSM-5 Criterion B for Depersonalization/Derealization Disorder, reality testing must remain intact throughout the perceptual detachment experiences, differentiating it from psychotic detachment.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getSomaticDomain(): array {
        return self::buildDomain(
            9,
            'Somatic Symptom and Related Disorders',
            'somatic-symptom-and-related-disorders',
            'Prominence of somatic symptoms associated with significant distress and impairment, characterized by abnormal thoughts, feelings, and behaviors.',
            [
                'From Somatoform to Somatic: Paradigm Shift',
                'Somatic Symptom Disorder (SSD)',
                'Illness Anxiety Disorder (IAD)',
                'Conversion Disorder (Functional Neurological Disorder)',
                'Psychological Factors Affecting Other Medical Conditions',
                'Factitious Disorder (Self vs. Another)',
                'Central Sensitization & La Belle Indifférence',
                'Differential Diagnosis: SSD vs. Medical vs. Malingering',
                'Educational Case Studies',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'The Conceptual Paradigm Shift: Excessive Cognitions and Behaviors', 'slug' => 'somatic-paradigm-shift'],
                2 => ['title' => 'Somatic Symptom Disorder (SSD: Distressing Symptoms + High Anxiety)', 'slug' => 'somatic-symptom-disorder-criteria'],
                3 => ['title' => 'Illness Anxiety Disorder (IAD: High Health Anxiety with Minimal Symptoms)', 'slug' => 'illness-anxiety-disorder-criteria'],
                4 => ['title' => 'Conversion Disorder (Functional Neurological Symptom Disorder)', 'slug' => 'conversion-disorder-criteria'],
                5 => ['title' => 'Psychological Factors Affecting Other Medical Conditions', 'slug' => 'psychological-factors-medical-criteria'],
                6 => ['title' => 'Factitious Disorder: Imposed on Self & Imposed on Another (Munchausen)', 'slug' => 'factitious-disorder-criteria'],
                7 => ['title' => 'Central Sensitization, Neurological Incompatibility, & Secondary Gain', 'slug' => 'somatic-neurobiology-mechanisms'],
                8 => ['title' => 'Differential Diagnosis: SSD vs. Organic Disease vs. Conversion vs. Malingering', 'slug' => 'somatic-differential-diagnosis'],
                9 => ['title' => 'Educational Case Studies: Navigating Diagnostic Pitfalls', 'slug' => 'educational-case-scenarios-somatic'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'somatic-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'DSM-5 replaced DSM-IV Somatization Disorder and Hypochondriasis. Somatic Symptom Disorder requires 1+ distressing somatic symptoms accompanied by excessive thoughts, feelings, or behaviors related to the symptoms (Criterion B). Illness Anxiety Disorder applies when somatic symptoms are absent or minimal, but preoccupation with having a serious illness is prominent. Conversion Disorder requires neurological incompatibility.',
            'SSD: Somatic symptoms + excessive thoughts/anxiety for 6+ months. IAD: Preoccupation with having a serious illness, minimal somatic symptoms, 6+ months. Conversion Disorder: Motor/sensory deficit incompatible with neurological anatomy.',
            'Factitious Disorder vs. Malingering: Factitious Disorder involves falsification of symptoms in the absence of obvious external rewards (driven by internal assumption of the sick role); Malingering involves intentional falsification for external gain (money, evading prosecution, drugs).',
            [
                [
                    'question' => 'What is the primary diagnostic distinction between Somatic Symptom Disorder (SSD) and Illness Anxiety Disorder (IAD) under DSM-5?',
                    'choice_a' => 'SSD involves prominent distressing somatic symptoms, whereas in IAD somatic symptoms are absent or very mild',
                    'choice_b' => 'SSD is only diagnosed in males, whereas IAD is only diagnosed in females',
                    'choice_c' => 'IAD requires neurological paralysis',
                    'choice_d' => 'SSD requires documented factitious symptom fabrication',
                    'correct_answer' => 'A',
                    'explanation' => 'In Somatic Symptom Disorder, significant somatic symptoms are present and distressing; in Illness Anxiety Disorder, somatic symptoms are absent or minimal, and the clinical focus is high health anxiety and preoccupation with having an undiagnosed serious disease.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getEatingDomain(): array {
        return self::buildDomain(
            10,
            'Feeding and Eating Disorders',
            'feeding-and-eating-disorders',
            'Persistent disturbance of eating or eating-related behavior that results in the altered consumption or absorption of food.',
            [
                'Eating Disorder Spectrum & ARFID',
                'Anorexia Nervosa: Restriction & BMI Tiers',
                'Anorexia Subtypes: Restricting vs. Binge/Purge',
                'Bulimia Nervosa: Compensatory Behaviors',
                'Binge-Eating Disorder (BED)',
                'Medical Sequelae & Refeeding Syndrome',
                'Sociocultural & Genetic Vulnerabilities',
                'Differential Diagnosis: Anorexia vs. Bulimia vs. OCD',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Eating Disorder Spectrum & Pediatric Feeding (Pica, Rumination, ARFID)', 'slug' => 'pediatric-feeding-arfid-criteria'],
                2 => ['title' => 'Anorexia Nervosa (Restriction, Fear of Weight Gain, Low Body Weight, BMI)', 'slug' => 'anorexia-nervosa-criteria'],
                3 => ['title' => 'Anorexia Subtypes: Restricting Type vs. Binge-Eating/Purging Type', 'slug' => 'anorexia-subtypes-criteria'],
                4 => ['title' => 'Bulimia Nervosa (Recurrent Bingeing & Compensatory Purging Cycles)', 'slug' => 'bulimia-nervosa-criteria'],
                5 => ['title' => 'Binge-Eating Disorder (BED: Objective Binges Without Compensation)', 'slug' => 'binge-eating-disorder-criteria'],
                6 => ['title' => 'Medical Complications, Electrolyte Abnormalities, & Refeeding Syndrome', 'slug' => 'eating-disorders-medical-sequelae'],
                7 => ['title' => 'Sociocultural Pressures, Perfectionism, & Neurobiological Vulnerabilities', 'slug' => 'eating-disorders-etiology-models'],
                8 => ['title' => 'Differential Diagnosis: Anorexia vs. Bulimia vs. BED vs. OCD', 'slug' => 'eating-disorders-differential-diagnosis'],
                9 => ['title' => 'Educational Case Scenarios: Diagnostic Differentiation', 'slug' => 'educational-case-scenarios-eating'],
                10 => ['title' => 'Chapter Mastery & Comprehensive Board Review', 'slug' => 'eating-disorders-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Anorexia Nervosa requires: 1) Restriction of energy intake leading to significantly low body weight, 2) Intense fear of gaining weight, 3) Disturbance in self-perceived body weight/shape. (Amenorrhea criterion was removed in DSM-5). Bulimia Nervosa requires recurrent binge eating and inappropriate compensatory behaviors (purging, fasting, laxatives) at least once a week for 3 months, at normal or above-normal weight. Binge-Eating Disorder involves binges without compensatory behaviors.',
            'Anorexia BMI severity: Mild (>=17), Moderate (16-16.99), Severe (15-15.99), Extreme (<15). Bulimia frequency: Mild (1-3 episodes/week), Moderate (4-7), Severe (8-13), Extreme (14+). BED frequency: Mild (1-3/week), Moderate (4-7), Severe (8-13), Extreme (14+).',
            'Anorexia Binge/Purge Type vs. Bulimia: In Anorexia Binge-Eating/Purging Type, the individual maintains a significantly low body weight (BMI < 18.5); in Bulimia Nervosa, the individual is at or above normal weight.',
            [
                [
                    'question' => 'How did the DSM-5 modify the diagnostic criteria for Anorexia Nervosa compared to DSM-IV?',
                    'choice_a' => 'Required the patient to be under age 10',
                    'choice_b' => 'Eliminated the mandatory amenorrhea (cessation of menses) criterion',
                    'choice_c' => 'Added a mandatory requirement for daily purging',
                    'choice_d' => 'Restricted the diagnosis solely to males',
                    'correct_answer' => 'B',
                    'explanation' => 'DSM-5 eliminated the requirement of amenorrhea (postmenarcheal absence of at least 3 consecutive menstrual cycles) for Anorexia Nervosa, allowing diagnosis in males, prepubescent females, and postmenopausal women.',
                    'difficulty' => 'intermediate',
                ],
                [
                    'question' => 'What is the critical clinical distinction between Bulimia Nervosa and Binge-Eating Disorder (BED)?',
                    'choice_a' => 'BED involves recurrent inappropriate compensatory purging behaviors',
                    'choice_b' => 'Binge-Eating Disorder involves recurrent bingeing WITHOUT regular compensatory purging behaviors',
                    'choice_c' => 'Bulimia occurs only in elderly populations',
                    'choice_d' => 'BED requires body weight below BMI 15',
                    'correct_answer' => 'B',
                    'explanation' => 'While both involve objective binge eating episodes, Binge-Eating Disorder is distinguished by the ABSENCE of recurrent inappropriate compensatory behaviors (such as self-induced vomiting, laxative abuse, or excessive fasting).',
                    'difficulty' => 'beginner',
                ]
            ]
        );
    }

    private static function getEliminationDomain(): array {
        return self::buildDomain(
            11,
            'Elimination Disorders',
            'elimination-disorders',
            'Inappropriate elimination of urine or feces, typically first diagnosed in childhood or adolescence.',
            [
                'Developmental Bladder & Bowel Milestones',
                'Enuresis: Criteria & Frequency Thresholds',
                'Enuresis Subtypes: Nocturnal vs. Diurnal',
                'Encopresis: Criteria & Developmental Gates',
                'Encopresis Subtypes: Overflow vs. Normal',
                'Physiological Factors & Vasopressin Rhythms',
                'Functional Consequences & Family Stigmatization',
                'Differential Diagnosis: Organic Disease (UTI, Spina Bifida)',
                'Educational Case Formulations',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Developmental Bladder and Bowel Control Milestones', 'slug' => 'elimination-developmental-milestones'],
                2 => ['title' => 'Enuresis: Diagnostic Criteria (Age 5+, 2x/Week for 3 Months)', 'slug' => 'enuresis-diagnostic-criteria'],
                3 => ['title' => 'Enuresis Subtypes: Nocturnal Only, Diurnal Only, and Mixed', 'slug' => 'enuresis-subtypes-criteria'],
                4 => ['title' => 'Encopresis: Diagnostic Criteria (Age 4+, 1x/Month for 3 Months)', 'slug' => 'encopresis-diagnostic-criteria'],
                5 => ['title' => 'Encopresis Subtypes: With Constipation & Overflow Incontinence', 'slug' => 'encopresis-subtypes-overflow'],
                6 => ['title' => 'Etiology: Nocturnal Polyuria, Stool Withholding, & Behavioral Traps', 'slug' => 'elimination-etiology-mechanisms'],
                7 => ['title' => 'Functional Impairment, Social Peer Stigmatization, & Family Stress', 'slug' => 'elimination-functional-consequences'],
                8 => ['title' => 'Differential Diagnosis: Organic Medical Conditions (UTI, Diabetes, Spina Bifida)', 'slug' => 'elimination-differential-diagnosis'],
                9 => ['title' => 'Educational Case Formulations: Identifying Functional vs. Organic', 'slug' => 'educational-case-scenarios-elimination'],
                10 => ['title' => 'Chapter Mastery & Board Review', 'slug' => 'elimination-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Enuresis is the repeated voiding of urine into bed or clothes, occurring at least twice a week for at least 3 consecutive months, in an individual with a chronological age of at least 5 years. Encopresis is the repeated passage of feces into inappropriate places, occurring at least once a month for at least 3 months, in an individual at least 4 years of age.',
            'Enuresis: Chronological age >= 5 years; at least 2x/week for 3 months. Subtypes: Nocturnal only, Diurnal only, Nocturnal and diurnal. Encopresis: Chronological age >= 4 years; at least 1x/month for 3 months. Subtypes: With constipation and overflow incontinence, Without constipation and overflow incontinence.',
            'Organic Rule-Out: Enuresis cannot be diagnosed if attributable to a urinary tract infection, diabetes mellitus, or diabetes insipidus. Encopresis cannot be diagnosed if due to Hirschsprung\'s disease or anal malformation.',
            [
                [
                    'question' => 'What is the minimum chronological age required under DSM-5 before a diagnosis of Enuresis can be assigned?',
                    'choice_a' => 'At least 2 years',
                    'choice_b' => 'At least 3 years',
                    'choice_c' => 'At least 5 years',
                    'choice_d' => 'At least 7 years',
                    'correct_answer' => 'C',
                    'explanation' => 'Under DSM-5 Criterion C for Enuresis, chronological age must be at least 5 years (or an equivalent developmental level).',
                    'difficulty' => 'beginner',
                ]
            ]
        );
    }

    private static function getSleepDomain(): array {
        return self::buildDomain(
            12,
            'Sleep-Wake Disorders',
            'sleep-wake-disorders',
            'Dissatisfaction regarding the quality, timing, and amount of sleep resulting in daytime distress and impairment in functioning.',
            [
                'Sleep Architecture (NREM vs. REM) & Pathology',
                'Insomnia Disorder: The 3-Month Rule',
                'Hypersomnolence & Narcolepsy (Cataplexy/Hypocretin)',
                'Breathing-Related Sleep Disorders (Apnea/Hypopnea)',
                'Circadian Rhythm Sleep-Wake Disorders',
                'Parasomnias: NREM Sleep Arousal Disorders',
                'REM Sleep Behavior Disorder & Nightmare Disorder',
                'Restless Legs Syndrome & Medical Differentials',
                'Educational Case Vignettes',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Sleep Architecture (NREM vs. REM) & Classification of Sleep Pathology', 'slug' => 'sleep-architecture-foundations'],
                2 => ['title' => 'Insomnia Disorder (Onset, Maintenance, Early Awakening; 3 Months)', 'slug' => 'insomnia-disorder-criteria'],
                3 => ['title' => 'Hypersomnolence Disorder & Narcolepsy (Cataplexy & Hypocretin Deficiency)', 'slug' => 'narcolepsy-hypersomnolence-criteria'],
                4 => ['title' => 'Breathing-Related Sleep Disorders (Obstructive Sleep Apnea, Hypoventilation)', 'slug' => 'breathing-related-sleep-disorders'],
                5 => ['title' => 'Circadian Rhythm Sleep-Wake Disorders (Delayed, Advanced, Shift Work)', 'slug' => 'circadian-rhythm-sleep-disorders'],
                6 => ['title' => 'Parasomnias: Non-REM Sleep Arousal Disorders (Sleepwalking, Sleep Terrors)', 'slug' => 'nrem-parasomnias-criteria'],
                7 => ['title' => 'REM Sleep Behavior Disorder & Nightmare Disorder (Motor Dreams vs. Dreams)', 'slug' => 'rem-sleep-behavior-nightmare-criteria'],
                8 => ['title' => 'Restless Legs Syndrome & Differential Diagnosis of Sleep Pathology', 'slug' => 'restless-legs-differential-diagnosis'],
                9 => ['title' => 'Educational Case Vignettes: Sleep Disorder Case Formulations', 'slug' => 'educational-case-scenarios-sleep'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'sleep-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Insomnia Disorder requires sleep difficulty at least 3 nights per week for at least 3 months despite adequate opportunity for sleep. Narcolepsy requires recurrent periods of an irrepressible need to sleep, accompanied by cataplexy, hypocretin-1 deficiency in CSF, or polysomnography showing SOREMPs. NREM Parasomnias feature amnesia and incomplete awakening; REM Sleep Behavior Disorder features acting out dreams during REM sleep.',
            'Insomnia: 3 nights/week for 3+ months. Narcolepsy: 3x/week for 3+ months + cataplexy or hypocretin deficiency. Restless Legs: Urge to move legs with unpleasant sensations, relieved by movement, 3 nights/week for 3 months.',
            'Sleep Terrors (NREM) vs. Nightmare Disorder (REM): Sleep Terrors occur during slow-wave NREM sleep, with intense autonomic arousal, motor agitation, and complete amnesia for the event upon morning awakening; Nightmares occur during REM sleep, and the individual awakens alert with vivid dream recall.',
            [
                [
                    'question' => 'A patient presents with recurrent daytime episodes of sudden bilateral loss of muscle tone (cataplexy) precipitated by laughter, and CSF analysis reveals hypocretin-1 deficiency. What is the DSM-5 diagnosis?',
                    'choice_a' => 'Insomnia Disorder',
                    'choice_b' => 'Narcolepsy',
                    'choice_c' => 'Restless Legs Syndrome',
                    'choice_d' => 'Non-REM Sleep Arousal Disorder',
                    'correct_answer' => 'B',
                    'explanation' => 'Narcolepsy in DSM-5 is defined by irrepressible sleep attacks accompanied by either cataplexy (sudden loss of muscle tone triggered by emotion) or laboratory-confirmed hypocretin-1 deficiency.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getSexualDomain(): array {
        return self::buildDomain(
            13,
            'Sexual Dysfunctions',
            'sexual-dysfunctions',
            'Clinically significant disturbance in a person\'s ability to respond sexually or experience sexual pleasure.',
            [
                'Human Sexual Response Cycle & DSM-5 Framework',
                'Male Disorders: Delayed Ejaculation & Erectile Disorder',
                'Premature Ejaculation & Male Hypoactive Sexual Desire',
                'Female Orgasmic & Sexual Interest/Arousal Disorders',
                'Genito-Pelvic Pain/Penetration Disorder',
                'Substance/Medication-Induced Sexual Dysfunction',
                'Specifiers: Lifelong vs. Acquired, Generalized vs. Situational',
                'Differential Diagnosis: Psychogenic vs. Organic Vascular',
                'Educational Case Studies',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'The Human Sexual Response Cycle & DSM-5 Diagnostic Framework (6-Month Rule)', 'slug' => 'sexual-response-framework'],
                2 => ['title' => 'Male Sexual Disorders: Delayed Ejaculation & Erectile Disorder', 'slug' => 'male-sexual-disorders-criteria'],
                3 => ['title' => 'Premature (Early) Ejaculation & Male Hypoactive Sexual Desire Disorder', 'slug' => 'premature-ejaculation-hypoactive-desire'],
                4 => ['title' => 'Female Disorders: Female Orgasmic & Sexual Interest/Arousal Disorder', 'slug' => 'female-sexual-disorders-criteria'],
                5 => ['title' => 'Genito-Pelvic Pain/Penetration Disorder (Dyspareunia/Vaginismus Fusion)', 'slug' => 'genito-pelvic-pain-penetration'],
                6 => ['title' => 'Substance/Medication-Induced Sexual Dysfunction (SSRI Effects & Alcohol)', 'slug' => 'substance-induced-sexual-dysfunction'],
                7 => ['title' => 'Specifiers: Lifelong vs. Acquired, Generalized vs. Situational, Severity', 'slug' => 'sexual-dysfunctions-specifiers'],
                8 => ['title' => 'Differential Diagnosis: Psychogenic vs. Organic Vascular/Endocrine Causes', 'slug' => 'sexual-dysfunctions-differential-diagnosis'],
                9 => ['title' => 'Educational Case Studies: Differentiating Multifactorial Dysfunctions', 'slug' => 'educational-case-scenarios-sexual'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Review', 'slug' => 'sexual-dysfunctions-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'In DSM-5, sexual dysfunctions require a minimum duration of approximately 6 months and must occur on 75-100% of sexual occasions, causing clinically significant distress. DSM-5 merged Vaginismus and Dyspareunia into Genito-Pelvic Pain/Penetration Disorder. All sexual dysfunctions require specifying: Lifelong vs. Acquired, and Generalized vs. Situational.',
            '6-month minimum duration criterion across 75-100% of encounters. Genito-Pelvic Pain/Penetration Disorder: Difficulty with penetration, pelvic pain, marked fear/anxiety, or pelvic floor muscle tensing.',
            'Organic vs. Psychogenic Erectile Disorder: Preservation of spontaneous nocturnal/morning penile erections suggests psychogenic etiology rather than vascular or neurological organic damage.',
            [
                [
                    'question' => 'Under DSM-5, what is the standard minimum symptom duration required before a diagnosis of Sexual Dysfunction (such as Erectile Disorder or Female Orgasmic Disorder) can be made?',
                    'choice_a' => 'At least 2 weeks',
                    'choice_b' => 'At least 1 month',
                    'choice_c' => 'Approximately 6 months',
                    'choice_d' => 'At least 2 years',
                    'correct_answer' => 'C',
                    'explanation' => 'To prevent overdiagnosis of transient sexual difficulties, DSM-5 instituted a standardized minimum duration requirement of approximately 6 months (occurring in 75–100% of occasions) for sexual dysfunctions.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getGenderDomain(): array {
        return self::buildDomain(
            14,
            'Gender Dysphoria',
            'gender-dysphoria',
            'Marked incongruence between one\'s experienced/expressed gender and natal assigned gender, accompanied by distress.',
            [
                'Conceptual Evolution: Identity vs. Dysphoric Incongruence',
                'Gender Dysphoria in Children: 6 of 8 Indicators',
                'Gender Dysphoria in Adolescents and Adults: 2 of 6',
                'Post-Transition Specifier: Medical/Surgical Context',
                'Developmental Trajectories & Persistence',
                'Psychosocial Stressors & Minority Stress Models',
                'Transcultural Perspectives & Stigma Considerations',
                'Differential Diagnosis: Nonconformity vs. Transvestic',
                'Educational Case Formulations',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Conceptual Evolution: Gender Identity vs. Dysphoric Incongruence in DSM-5', 'slug' => 'gender-dysphoria-conceptual-shift'],
                2 => ['title' => 'Gender Dysphoria in Children (Criterion A: 6 of 8 Indicators, 6-Month Duration)', 'slug' => 'gender-dysphoria-children-criteria'],
                3 => ['title' => 'Gender Dysphoria in Adolescents and Adults (Criterion A: 2 of 6 Indicators)', 'slug' => 'gender-dysphoria-adults-criteria'],
                4 => ['title' => 'Post-Transition Specifier: Living in Desired Gender with Interventions', 'slug' => 'gender-dysphoria-post-transition'],
                5 => ['title' => 'Developmental Trajectories, Desistance vs. Persistence in Adolescence', 'slug' => 'gender-dysphoria-developmental-course'],
                6 => ['title' => 'Psychosocial Stressors, Minority Stress Models, and Secondary Dysphoria', 'slug' => 'gender-dysphoria-minority-stress'],
                7 => ['title' => 'Transcultural Perspectives: Non-Western Gender Roles and Societal Stigma', 'slug' => 'gender-dysphoria-transcultural'],
                8 => ['title' => 'Differential Diagnosis: Nonconformity vs. Transvestic vs. Body Dysmorphic', 'slug' => 'gender-dysphoria-differential-diagnosis'],
                9 => ['title' => 'Educational Case Formulations: Navigating Developmental Presentations', 'slug' => 'educational-case-scenarios-gender'],
                10 => ['title' => 'Chapter Mastery & Board Review', 'slug' => 'gender-dysphoria-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'DSM-5 replaced "Gender Identity Disorder" with Gender Dysphoria, shifting diagnostic focus from gender variance itself to the clinically significant distress arising from incongruence between experienced gender and natal assigned sex. Children require 6 of 8 indicators for 6 months (must include strong desire to be of the other gender); Adolescents and Adults require 2 of 6 indicators for 6 months.',
            'Children: 6 of 8 indicators for at least 6 months. Adolescents/Adults: 2 of 6 indicators for at least 6 months. Specifier: Post-transition (individual has transitioned to full-time living in desired gender).',
            'Gender Dysphoria vs. Transvestic Disorder: In Transvestic Disorder (a paraphilic disorder), cross-dressing is accompanied by sexual arousal and does NOT involve a pervasive desire to live permanently as the other gender.',
            [
                [
                    'question' => 'How did the DSM-5 philosophically alter the diagnostic classification of Gender Dysphoria compared to the older DSM-IV "Gender Identity Disorder"?',
                    'choice_a' => 'It removed gender variance from personality disorders and focused diagnostic pathology on the distress caused by gender incongruence',
                    'choice_b' => 'It classified all cross-gender identification as a severe psychotic delusion',
                    'choice_c' => 'It made gender variance an automatic exclusion for therapy',
                    'choice_d' => 'It declared gender identity to be entirely unalterable',
                    'correct_answer' => 'A',
                    'explanation' => 'DSM-5 replaced "Gender Identity Disorder" with "Gender Dysphoria" to emphasize that gender nonconformity is not a mental disorder in itself; rather, clinical pathology is defined by the distress or functional impairment resulting from the incongruence.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getDisruptiveDomain(): array {
        return self::buildDomain(
            15,
            'Disruptive, Impulse-Control, and Conduct Disorders',
            'disruptive-impulse-control-conduct-disorders',
            'Problems in the self-control of emotions and behaviors that violate the rights of others or bring the individual into conflict with societal norms.',
            [
                'Externalizing Spectrum: Emotion vs. Behavior',
                'Oppositional Defiant Disorder (ODD: 3 Clusters)',
                'Intermittent Explosive Disorder (IED: Outbursts)',
                'Conduct Disorder (CD: 4 Symptom Groups)',
                'CD Subtypes: Childhood vs. Adolescent Onset',
                'Limited Prosocial Emotions Specifier (Callous-Unemotional)',
                'Pyromania & Kleptomania: Tension & Relief',
                'Developmental Trajectory: ODD -> CD -> ASPD',
                'Educational Case Vignettes',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Externalizing Spectrum: Emotional vs. Behavioral Dysregulation', 'slug' => 'disruptive-spectrum-foundations'],
                2 => ['title' => 'Oppositional Defiant Disorder (ODD: Angry/Irritable, Argumentative, Vindictiveness)', 'slug' => 'odd-diagnostic-criteria'],
                3 => ['title' => 'Intermittent Explosive Disorder (IED: Recurrent Aggressive Outbursts)', 'slug' => 'ied-diagnostic-criteria'],
                4 => ['title' => 'Conduct Disorder (CD: Aggression, Property Destruction, Deceit/Theft, Violations)', 'slug' => 'conduct-disorder-criteria'],
                5 => ['title' => 'Conduct Disorder Subtypes (Childhood-Onset vs. Adolescent-Onset) & Severity', 'slug' => 'conduct-disorder-subtypes'],
                6 => ['title' => 'Limited Prosocial Emotions Specifier (Callous-Unemotional Traits)', 'slug' => 'limited-prosocial-emotions-specifier'],
                7 => ['title' => 'Pyromania & Kleptomania: Pathological Tension, Pleasure, and Relief Cycles', 'slug' => 'pyromania-kleptomania-criteria'],
                8 => ['title' => 'Developmental Pathway: Progression from ODD to CD to Antisocial Pathology', 'slug' => 'disruptive-developmental-pathway'],
                9 => ['title' => 'Educational Case Vignettes: Differentiating Externalizing Pathology', 'slug' => 'educational-case-scenarios-disruptive'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'disruptive-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Oppositional Defiant Disorder (ODD) involves angry/irritable mood, argumentative/defiant behavior, or vindictiveness lasting at least 6 months (4 symptoms). Conduct Disorder involves persistent behavior violating basic rights of others or societal norms (aggression to people/animals, destruction of property, deceit/theft, serious rule violations). DSM-5 added the "With limited prosocial emotions" specifier for CD.',
            'ODD: 4 symptoms from 3 categories (Angry/Irritable, Argumentative/Defiant, Vindictiveness) for 6+ months. CD: 3 of 15 criteria in past 12 months with 1 in past 6 months. Childhood-onset (< age 10) vs. Adolescent-onset (no symptoms prior to age 10).',
            'Conduct Disorder vs. Antisocial Personality Disorder: Individuals must be under 18 for Conduct Disorder; individuals must be at least 18 years old with evidence of Conduct Disorder before age 15 to receive a diagnosis of Antisocial Personality Disorder.',
            [
                [
                    'question' => 'Which newly introduced DSM-5 specifier for Conduct Disorder characterizes youth who exhibit callous-unemotional traits, lack of empathy, and unconcern about performance?',
                    'choice_a' => 'With marked seasonal pattern',
                    'choice_b' => 'With limited prosocial emotions',
                    'choice_c' => 'With atypical bipolarity',
                    'choice_d' => 'With obsessive rigidity',
                    'correct_answer' => 'B',
                    'explanation' => 'DSM-5 added the specifier "With limited prosocial emotions" to Conduct Disorder for individuals who persistently display callousness, lack of remorse or guilt, lack of empathy, and unconcern about performance across multiple settings.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getSubstanceDomain(): array {
        return self::buildDomain(
            16,
            'Substance-Related and Addictive Disorders',
            'substance-related-and-addictive-disorders',
            'Pathological pattern of behaviors related to substance use, as well as behavioral gambling disorder.',
            [
                'The 11 Core Criteria of Substance Use Disorder (SUD)',
                'Tolerance, Withdrawal, and Dopamine Circuits',
                'Alcohol-Related Disorders (DTs & Wernicke-Korsakoff)',
                'Sedative, Hypnotic, and Anxiolytic Disorders',
                'Opioid-Related Disorders & Overdose Triad',
                'Stimulant & Cannabis-Related Disorders',
                'Hallucinogen, Inhalant & Tobacco Disorders',
                'Gambling Disorder: The Behavioral Addiction',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'The 11 Core Criteria of Substance Use Disorder (SUD) & Severity Stratification', 'slug' => 'substance-use-11-criteria'],
                2 => ['title' => 'Neurobiology of Addiction: Mesolimbic Dopamine, Tolerance, and Withdrawal', 'slug' => 'addiction-neurobiology-withdrawal'],
                3 => ['title' => 'Alcohol-Related Disorders (Use Disorder, Intoxication, Delirium Tremens, Wernicke’s)', 'slug' => 'alcohol-use-disorders-criteria'],
                4 => ['title' => 'Sedative, Hypnotic, and Anxiolytic-Related Disorders (Benzodiazepine Risks)', 'slug' => 'sedative-anxiolytic-disorders'],
                5 => ['title' => 'Opioid-Related Disorders (Use Disorder, Intoxication, Overdose Triad, Withdrawal)', 'slug' => 'opioid-use-disorders-criteria'],
                6 => ['title' => 'Stimulant-Related Disorders (Cocaine, Amphetamines) & Cannabis Disorders', 'slug' => 'stimulant-cannabis-disorders'],
                7 => ['title' => 'Tobacco, Hallucinogens, Inhalants, & Substance-Induced Mental Disorders', 'slug' => 'tobacco-hallucinogens-substance-induced'],
                8 => ['title' => 'Gambling Disorder: The Sole Non-Substance Behavioral Addiction in DSM-5', 'slug' => 'gambling-disorder-criteria'],
                9 => ['title' => 'Educational Case Scenarios: Dual Diagnosis & Substance-Induced vs. Primary', 'slug' => 'educational-case-scenarios-substance'],
                10 => ['title' => 'Chapter Mastery & Comprehensive Board Review', 'slug' => 'substance-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'DSM-5 unified previous separate categories of Substance Abuse and Substance Dependence into a single Substance Use Disorder (SUD) measured across 11 criteria. Severity is classified based on the number of criteria met: Mild (2-3), Moderate (4-5), Severe (6+). Craving was added as an 11th criterion. Gambling Disorder was included as the sole non-substance behavioral addiction.',
            '11 Criteria across 4 groupings: Impaired control (1-4), Social impairment (5-7), Risky use (8-9), Pharmacological criteria (10: Tolerance, 11: Withdrawal). Mild: 2-3 criteria; Moderate: 4-5 criteria; Severe: 6+ criteria. In early remission: 3-12 months without criteria (except craving).',
            'Tolerance vs. Addiction: Tolerance and withdrawal do NOT count toward a Substance Use Disorder if the patient is taking prescribed opioids or benzodiazepines under appropriate medical supervision.',
            [
                [
                    'question' => 'How many DSM-5 criteria must an individual satisfy within a 12-month period to be classified as having a "Severe" Substance Use Disorder?',
                    'choice_a' => 'At least 2 criteria',
                    'choice_b' => 'At least 4 criteria',
                    'choice_c' => '6 or more criteria',
                    'choice_d' => 'All 11 criteria must be present',
                    'correct_answer' => 'C',
                    'explanation' => 'Under DSM-5, Substance Use Disorder severity is determined by criteria count: Mild is 2–3 symptoms, Moderate is 4–5 symptoms, and Severe is 6 or more symptoms.',
                    'difficulty' => 'intermediate',
                ],
                [
                    'question' => 'Which non-substance behavioral condition was officially incorporated into the Substance-Related and Addictive Disorders chapter in DSM-5?',
                    'choice_a' => 'Internet Gaming Disorder',
                    'choice_b' => 'Compulsive Shopping Disorder',
                    'choice_c' => 'Gambling Disorder',
                    'choice_d' => 'Hypersexual Disorder',
                    'correct_answer' => 'C',
                    'explanation' => 'DSM-5 officially included Gambling Disorder in the Substance-Related and Addictive Disorders chapter based on evidence that gambling behaviors activate brain reward systems similar to drugs of abuse.',
                    'difficulty' => 'beginner',
                ]
            ]
        );
    }

    private static function getNeurocognitiveDomain(): array {
        return self::buildDomain(
            17,
            'Neurocognitive Disorders',
            'neurocognitive-disorders',
            'Disorders where primary clinical deficit is in cognitive function, representing a decline from a previous level of performance.',
            [
                'Six Cognitive Domains Framework',
                'Delirium: Attention, Awareness & Fluctuation',
                'Major vs. Mild Neurocognitive Disorder',
                'NCD Due to Alzheimer\'s Disease',
                'Vascular Neurocognitive Disorder',
                'NCD with Lewy Bodies & Frontotemporal NCD',
                'TBI, Prion, Parkinson\'s & Huntington\'s NCD',
                'Differential Diagnosis: Delirium vs. Dementia vs. Depression',
                'Educational Case Formulations',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Six Cognitive Domains: Executive, Memory, Language, Motor, Attention, Social', 'slug' => 'six-cognitive-domains-framework'],
                2 => ['title' => 'Delirium: Acute Disturbance in Attention and Awareness with Fluctuating Course', 'slug' => 'delirium-diagnostic-criteria'],
                3 => ['title' => 'Major vs. Mild Neurocognitive Disorder (IADL Independence Threshold)', 'slug' => 'major-vs-mild-ncd-criteria'],
                4 => ['title' => 'NCD Due to Alzheimer’s Disease (Insidious Onset & Cortical Memory Decline)', 'slug' => 'alzheimers-ncd-criteria'],
                5 => ['title' => 'Vascular Neurocognitive Disorder (Stepwise Decline & Infarctions)', 'slug' => 'vascular-ncd-criteria'],
                6 => ['title' => 'NCD with Lewy Bodies & Frontotemporal NCD (Hallucinations vs. Disinhibition)', 'slug' => 'lewy-bodies-frontotemporal-ncd'],
                7 => ['title' => 'NCD Due to Traumatic Brain Injury, Prion Disease, Parkinson’s, & Huntington’s', 'slug' => 'tbi-prion-parkinsons-ncd'],
                8 => ['title' => 'Differential Diagnosis: Delirium vs. Dementia vs. Depression (Pseudodementia)', 'slug' => 'ncd-differential-diagnosis'],
                9 => ['title' => 'Educational Case Formulations: Identifying Cognitive Trajectories', 'slug' => 'educational-case-scenarios-ncd'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Review', 'slug' => 'ncd-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'DSM-5 replaced Dementia with Major Neurocognitive Disorder, and introduced Mild Neurocognitive Disorder. The critical dividing line is Independence in Instrumental Activities of Daily Living (IADLs): in Mild NCD, independent everyday functioning is preserved; in Major NCD, cognitive deficits interfere with independence. Delirium is an acute, fluctuating disturbance in attention and awareness.',
            'Major NCD: Significant cognitive decline from previous baseline; deficits interfere with independence in everyday activities. Mild NCD: Modest cognitive decline; deficits do NOT interfere with capacity for independence. Delirium: Disturbance in attention and awareness developing over short period (hours/days), fluctuating in severity.',
            'Delirium vs. Dementia: Delirium has acute onset, fluctuating course, and primarily impairs attention and awareness; Major NCD (Dementia) has insidious, progressive onset and preserves alertness until late stages.',
            [
                [
                    'question' => 'What is the critical functional benchmark that distinguishes Major Neurocognitive Disorder from Mild Neurocognitive Disorder in the DSM-5?',
                    'choice_a' => 'Major NCD requires complete loss of speech (mutism)',
                    'choice_b' => 'In Major NCD, cognitive deficits interfere with independence in everyday activities (IADLs), whereas in Mild NCD independence is preserved',
                    'choice_c' => 'Mild NCD only occurs after age 90',
                    'choice_d' => 'Major NCD is strictly curable with antibiotics',
                    'correct_answer' => 'B',
                    'explanation' => 'The key clinical differentiator between Major and Mild NCD is that Major NCD impairs independence in everyday instrumental activities of daily living (such as paying bills or managing medications), while Mild NCD preserves independence.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getPersonalityDomain(): array {
        return self::buildDomain(
            18,
            'Personality Disorders',
            'personality-disorders',
            'Enduring pattern of inner experience and behavior that deviates markedly from cultural expectations, is pervasive and inflexible.',
            [
                'General Personality Disorder Criteria',
                'Cluster A: Paranoid Personality Disorder',
                'Cluster A: Schizoid & Schizotypal Disorders',
                'Cluster B: Antisocial Personality Disorder',
                'Cluster B: Borderline Personality Disorder',
                'Cluster B: Histrionic & Narcissistic Disorders',
                'Cluster C: Avoidant Personality Disorder',
                'Cluster C: Dependent & Obsessive-Compulsive (OCPD)',
                'Differential Diagnosis: Axis II Character vs. Axis I Syndrome',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'The Enduring Pattern: General Diagnostic Criteria for Personality Disorders', 'slug' => 'general-personality-criteria'],
                2 => ['title' => 'Cluster A (Odd/Eccentric): Paranoid Personality Disorder', 'slug' => 'paranoid-personality-criteria'],
                3 => ['title' => 'Cluster A (Odd/Eccentric): Schizoid and Schizotypal Personality Disorders', 'slug' => 'schizoid-schizotypal-criteria'],
                4 => ['title' => 'Cluster B (Dramatic/Erratic): Antisocial Personality Disorder', 'slug' => 'antisocial-personality-criteria'],
                5 => ['title' => 'Cluster B (Dramatic/Erratic): Borderline Personality Disorder (BPD)', 'slug' => 'borderline-personality-criteria'],
                6 => ['title' => 'Cluster B (Dramatic/Erratic): Histrionic and Narcissistic Personality Disorders', 'slug' => 'histrionic-narcissistic-criteria'],
                7 => ['title' => 'Cluster C (Anxious/Fearful): Avoidant Personality Disorder', 'slug' => 'avoidant-personality-criteria'],
                8 => ['title' => 'Cluster C (Anxious/Fearful): Dependent & Obsessive-Compulsive (OCPD)', 'slug' => 'dependent-ocpd-criteria'],
                9 => ['title' => 'Differential Diagnosis: Character Pathology vs. Episodic Clinical Syndromes', 'slug' => 'personality-differential-diagnosis'],
                10 => ['title' => 'Chapter Mastery & Comprehensive Board Review', 'slug' => 'personality-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'Personality disorders represent enduring, pervasive, and inflexible patterns across cognition, affectivity, interpersonal functioning, and impulse control, with onset traceable to adolescence or early adulthood. 10 disorders organized into 3 clusters: Cluster A (Odd/Eccentric: Paranoid, Schizoid, Schizotypal), Cluster B (Dramatic/Emotional: Antisocial, Borderline, Histrionic, Narcissistic), Cluster C (Anxious/Fearful: Avoidant, Dependent, OCPD).',
            'Cluster A: Odd, eccentric. Cluster B: Dramatic, emotional, erratic. Cluster C: Anxious, fearful. General criteria: Inflexible across personal/social situations, leading to distress/impairment, stable long duration.',
            'Schizoid vs. Avoidant Personality: Individuals with Schizoid Personality Disorder have NO desire for close relationships and prefer solitary activities; individuals with Avoidant Personality Disorder strongly desire close relationships but avoid them due to overwhelming fear of rejection and humiliation.',
            [
                [
                    'question' => 'What is the key interpersonal difference between Schizoid Personality Disorder and Avoidant Personality Disorder under DSM-5?',
                    'choice_a' => 'Schizoid individuals desire close friendships but avoid them due to social anxiety',
                    'choice_b' => 'Schizoid individuals exhibit detachment and lack desire for close relationships, whereas Avoidant individuals desire relationships but avoid them out of fear of criticism and rejection',
                    'choice_c' => 'Avoidant personality is classified under Cluster A, while Schizoid is Cluster C',
                    'choice_d' => 'Both conditions involve active persecutory delusions',
                    'correct_answer' => 'B',
                    'explanation' => 'Schizoid individuals genuinely prefer isolation and lack interest in forming relationships (social apathy), whereas Avoidant individuals yearn for social intimacy but isolate themselves because of extreme fear of rejection and inadequacy.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function getParaphilicDomain(): array {
        return self::buildDomain(
            19,
            'Paraphilic Disorders',
            'paraphilic-disorders',
            'Intense and persistent sexual interest other than sexual interest in genital stimulation with consenting adult partners that causes distress or harm.',
            [
                'Paraphilia vs. Paraphilic Disorder: Criterion A vs. B',
                'Voyeuristic & Exhibitionistic Disorders',
                'Frotteuristic Disorder: Nonconsenting Touching',
                'Sexual Masochism & Sexual Sadism Disorders',
                'Pedophilic Disorder: Age & Discrepancy Rules',
                'Fetishistic & Transvestic Disorders',
                'Controlled Environment & Remission Specifiers',
                'Legal, Forensic & Ethical Considerations',
                'Educational Case Scenarios',
                'Chapter Mastery & Board Review',
            ],
            [
                1 => ['title' => 'Paraphilia vs. Paraphilic Disorder: The Crucial Diagnostic Distinction', 'slug' => 'paraphilia-vs-disorder-distinction'],
                2 => ['title' => 'Voyeuristic Disorder & Exhibitionistic Disorder', 'slug' => 'voyeuristic-exhibitionistic-criteria'],
                3 => ['title' => 'Frotteuristic Disorder (Touching/Rubbing Against Nonconsenting Persons)', 'slug' => 'frotteuristic-disorder-criteria'],
                4 => ['title' => 'Sexual Masochism Disorder & Sexual Sadism Disorder', 'slug' => 'masochism-sadism-disorder-criteria'],
                5 => ['title' => 'Pedophilic Disorder (Criteria, Age 16+ & 5-Year Discrepancy Rule)', 'slug' => 'pedophilic-disorder-criteria'],
                6 => ['title' => 'Fetishistic Disorder & Transvestic Disorder', 'slug' => 'fetishistic-transvestic-criteria'],
                7 => ['title' => 'Course Specifiers (In a Controlled Environment, In Full Remission)', 'slug' => 'paraphilic-specifiers-course'],
                8 => ['title' => 'Legal, Forensic, and Ethical Boundaries in Clinical Assessment', 'slug' => 'paraphilic-forensic-ethics'],
                9 => ['title' => 'Educational Case Scenarios: Diagnostic Evaluation of Arousal Patterns', 'slug' => 'educational-case-scenarios-paraphilic'],
                10 => ['title' => 'Chapter Mastery & Board-Exam Synthesis', 'slug' => 'paraphilic-mastery-synthesis', 'part_type' => 'mastery'],
            ],
            'DSM-5 made an explicit distinction between a Paraphilia and a Paraphilic Disorder. A paraphilia is a necessary but not sufficient condition for having a paraphilic disorder. A paraphilia alone does not automatically justify clinical diagnosis. A Paraphilic Disorder requires Criterion A (specific paraphilic arousal focus for >= 6 months) AND Criterion B (the paraphilia causes personal distress, impairment, or involves harm/risk of harm to nonconsenting others).',
            'Criterion A: Specific erotic focus for at least 6 months. Criterion B: Personal distress/impairment, or acted upon with nonconsenting individuals (e.g., Pedophilic, Exhibitionistic, Frotteuristic, Voyeuristic). Specifiers: In a controlled environment; In full remission.',
            'Paraphilia vs. Paraphilic Disorder: An atypical sexual interest without personal distress, impairment, or harm to nonconsenting partners is a paraphilia, NOT a psychiatric disorder.',
            [
                [
                    'question' => 'What is the fundamental diagnostic distinction established in DSM-5 between a "Paraphilia" and a "Paraphilic Disorder"?',
                    'choice_a' => 'Paraphilias only occur in elderly populations',
                    'choice_b' => 'A paraphilia is an atypical sexual interest, whereas a Paraphilic Disorder requires that the interest causes personal distress, impairment, or harm to nonconsenting others',
                    'choice_c' => 'All atypical sexual interests are automatically designated as severe psychiatric disorders',
                    'choice_d' => 'Paraphilic Disorders are diagnosed strictly from blood tests',
                    'correct_answer' => 'B',
                    'explanation' => 'DSM-5 clearly distinguishes between a paraphilia (an atypical sexual interest) and a Paraphilic Disorder, which requires that the paraphilia causes personal distress, functional impairment, or involves nonconsenting individuals or harm to others.',
                    'difficulty' => 'intermediate',
                ]
            ]
        );
    }

    private static function buildDomain(int $domainId, string $name, string $slug, string $desc, array $topics, array $partsMeta, string $sampleOverview, string $sampleCriteria, string $sampleDiff, array $questions): array {
        $parts = [];
        foreach ($partsMeta as $num => $p) {
            $isMastery = ($num === 10);
            $partQuestions = [];
            if ($num === 1 && isset($questions[0])) {
                $partQuestions[] = $questions[0];
            }
            if ($num === 2 && isset($questions[1])) {
                $partQuestions[] = $questions[1];
            }
            if ($isMastery && !empty($questions)) {
                $partQuestions[] = $questions[count($questions) - 1];
            }
            // Ensure at least 1-2 questions for every part
            if (empty($partQuestions)) {
                $partQuestions = [
                    [
                        'question' => "Regarding {$p['title']} in {$name}: Which clinical principle best reflects DSM-5 diagnostic formulation?",
                        'choice_a' => 'Evidence-based evaluation of duration criteria, symptom thresholds, and functional impairment',
                        'choice_b' => 'Speculative intuition without empirical symptom verification',
                        'choice_c' => 'Assessing symptoms exclusively in a single 5-minute interview',
                        'choice_d' => 'Diagnosing condition without ruling out substance or medical etiologies',
                        'correct_answer' => 'A',
                        'explanation' => "Under the DSM-5 framework, {$name} diagnoses require systematic evaluation of standardized duration thresholds, clinical severity, and functional impairment while ruling out physiological and substance causes.",
                        'difficulty' => $num <= 3 ? 'beginner' : ($num <= 7 ? 'intermediate' : ($num <= 9 ? 'advanced' : 'board_exam')),
                    ]
                ];
            }

            $parts[$num] = [
                'title' => $p['title'],
                'slug' => $p['slug'],
                'description' => "Detailed educational reviewer covering {$p['title']} under DSM-5 criteria.",
                'part_type' => $p['part_type'] ?? ($isMastery ? 'mastery' : ($num === 9 ? 'scenario' : 'learn')),
                'topic_order' => min(count($topics), $num),
                'overview' => "{$p['title']} is a critical subject in {$name}. {$sampleOverview}",
                'key_concepts' => [
                    ['title' => 'Diagnostic Specification', 'icon' => '📜', 'definition' => "Core diagnostic features defining {$p['title']} according to DSM-5 criteria."],
                    ['title' => 'Clinical Presentation', 'icon' => '🔍', 'definition' => "Manifestations and symptom clusters observed in clinical assessment."],
                    ['title' => 'Etiological Considerations', 'icon' => '🧠', 'definition' => "Neurobiological, cognitive, and psychosocial contributing factors."]
                ],
                'important_terms' => [
                    ['term' => 'Diagnostic Threshold', 'definition' => 'The minimum number of symptoms and duration required to meet formal DSM-5 criteria.'],
                    ['term' => 'Functional Impairment', 'definition' => 'Clinically significant interference in social, occupational, or academic spheres of functioning.']
                ],
                'diagnostic_criteria' => $sampleCriteria,
                'specifiers' => 'Specifiers for severity (Mild, Moderate, Severe), course (in partial/full remission), and clinical subtypes.',
                'differential_diagnosis' => $sampleDiff,
                'clinical_example' => "Educational Case Scenario: A patient presents with persistent symptoms characteristic of {$p['title']}. Comprehensive psychiatric evaluation confirms symptoms meet DSM-5 duration and impairment thresholds without secondary organic etiology.",
                'summary_notes' => "• Review DSM-5 duration thresholds and core symptom count for {$p['title']}.\n• Differential diagnosis requires eliminating organic medical conditions and substance effects.\n• Always evaluate functional impairment across academic, occupational, and interpersonal domains.",
                'xp_reward' => $isMastery ? 100 : 50,
                'coin_reward' => $isMastery ? 50 : 25,
                'passing_score' => 70,
                'questions' => $partQuestions
            ];
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => $desc,
            'topics' => $topics,
            'parts' => $parts
        ];
    }
}
