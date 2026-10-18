<?php
/**
 * MockExamBlueprint.php
 * Centralized blueprint for the Test Your Knowledge — Daily Psychometrician Board Exam Simulator.
 * Configures the two-session structure, subject allocation, item counts, and scheduling rules.
 */
class MockExamBlueprint {
    public const TOTAL_QUESTIONS = 450;

    public const SESSIONS = [
        1 => [
            'session_number' => 1,
            'name' => 'Session 1 — Examination Day 1',
            'day_label' => 'Day 1',
            'target_questions' => 200,
            'default_duration_minutes' => 200,
            'description' => 'Focuses on foundational personality systems, developmental milestones, and clinical psychopathology.',
            'subjects' => [
                [
                    'code' => 'top_dev',
                    'name' => 'Theories of Personality / Developmental Psychology',
                    'short_name' => 'Theories of Personality & Dev Psych',
                    'target_questions' => 100,
                    'proportion_percentage' => 22.22, // 100 / 450
                    'session_proportion' => 50.0, // 100 / 200
                    'icon' => '📜',
                    'color' => '#8b5cf6',
                    // Maps to Study Hall domains & parts
                    'mapped_domains' => [1, 7, 9, 10, 11, 12],
                    'focus_areas' => [
                        'Personality Disorders & Cluster A/B/C Trait Structures (Study Hall Domain 12)',
                        'Neurodevelopmental Milestones & Intellectual Disabilities (Study Hall Domain 1)',
                        'Childhood Trauma, Reactive Attachment & Developmental Criteria (Study Hall Domain 7)',
                        'Pediatric Feeding & Elimination Developmental Milestones (Study Hall Domain 9)',
                        'Gender Dysphoria Across Lifespan: Children vs Adolescents/Adults (Study Hall Domain 10)',
                        'Disruptive Behavior & Conduct Trajectories: Childhood vs Adolescent Onset (Study Hall Domain 11)'
                    ]
                ],
                [
                    'code' => 'abnormal_psych',
                    'name' => 'Abnormal Psychology',
                    'short_name' => 'Abnormal Psychology',
                    'target_questions' => 100,
                    'proportion_percentage' => 22.22, // 100 / 450
                    'session_proportion' => 50.0, // 100 / 200
                    'icon' => '🧠',
                    'color' => '#ec4899',
                    // Maps to all primary DSM-5 clinical syndromes in Study Hall
                    'mapped_domains' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
                    'focus_areas' => [
                        'Schizophrenia Spectrum & Psychotic Disorders (Domain 2)',
                        'Bipolar & Related Disorders (Domain 3)',
                        'Depressive Disorders & Mood Episodes (Domain 4)',
                        'Anxiety Disorders & Phobic Conditions (Domain 5)',
                        'Obsessive-Compulsive & Related Disorders (Domain 6)',
                        'Trauma-, Stressor-Related & Dissociative Disorders (Domain 7)',
                        'Somatic Symptom & Functional Neurological Disorders (Domain 8)',
                        'Eating & Feeding Pathology (Domain 9)',
                        'Sleep-Wake & Sexual Dysfunctions (Domain 10)',
                        'Substance-Related & Addictive Disorders (Domain 11)',
                        'Neurocognitive & Paraphilic Disorders (Domain 12)'
                    ]
                ]
            ]
        ],
        2 => [
            'session_number' => 2,
            'name' => 'Session 2 — Examination Day 2',
            'day_label' => 'Day 2',
            'target_questions' => 250,
            'default_duration_minutes' => 250,
            'description' => 'Evaluates industrial-organizational psychology principles and comprehensive psychological assessment.',
            'subjects' => [
                [
                    'code' => 'io_psych',
                    'name' => 'Industrial and Organizational Psychology',
                    'short_name' => 'Industrial & Organizational Psych',
                    'target_questions' => 100,
                    'proportion_percentage' => 22.22, // 100 / 450
                    'session_proportion' => 40.0, // 100 / 250
                    'icon' => '🏢',
                    'color' => '#f97316',
                    // Mapped to Study Hall occupational, vocational, workplace impairment, and organizational criteria
                    'mapped_domains' => [1, 4, 5, 6, 7, 10, 11],
                    'focus_areas' => [
                        'Occupational Impairment & Workplace Accommodations (DSM-5 Criterion B/C)',
                        'Shift-Work Sleep Disorder & Work Fatigue Syndromes (Study Hall Domain 10)',
                        'Workplace Substance Abuse & Occupational Safety Protocols (Study Hall Domain 11)',
                        'Vocational Performance, Executive Functioning & Adult ADHD in Workplaces (Study Hall Domain 1)',
                        'Occupational Burnout, Work-Related Panic & Workplace Phobias (Study Hall Domain 5)',
                        'Critical Incident Stress, Organizational Trauma & Workplace PTSD (Study Hall Domain 7)'
                    ]
                ],
                [
                    'code' => 'psych_assessment',
                    'name' => 'Psychological Assessment',
                    'short_name' => 'Psychological Assessment',
                    'target_questions' => 150,
                    'proportion_percentage' => 33.33, // 150 / 450 (1/3 of exam, NOT 40%)
                    'session_proportion' => 60.0, // 150 / 250
                    'icon' => '📋',
                    'color' => '#06b6d4',
                    // Mapped to Study Hall diagnostic assessments, psychometric criteria, differential tools, and clinical batteries
                    'mapped_domains' => [1, 2, 7, 8, 11, 12],
                    'focus_areas' => [
                        'Intellectual & Cognitive Assessment: WAIS/WISC, Stanford-Binet, Adaptive Scales (Study Hall Domain 1)',
                        'Neurocognitive Screening: MMSE, MoCA, Clock-Drawing Test, IADL Independence (Study Hall Domain 12)',
                        'Clinical Interviewing, Diagnostic Thresholds & Differential Formulation (Across Domains)',
                        'Psychometric Reliability, Validity & Standard Error in Diagnostic Decisions (Study Hall Archives)',
                        'Forensic Assessment, Symptom Validity & Malingering Differentiation (Study Hall Domain 8 & 12)',
                        'Behavioral Rating Scales, Self-Report Inventories & Symptom Monitoring (Study Hall Archives)'
                    ]
                ]
            ]
        ]
    ];

    /**
     * Get blueprint specification for a given session.
     */
    public static function getSessionBlueprint(int $sessionNumber): ?array {
        return self::SESSIONS[$sessionNumber] ?? null;
    }

    /**
     * Get all blueprint subjects flattened.
     */
    public static function getAllSubjects(): array {
        $subjects = [];
        foreach (self::SESSIONS as $s) {
            foreach ($s['subjects'] as $sub) {
                $sub['session_number'] = $s['session_number'];
                $subjects[$sub['code']] = $sub;
            }
        }
        return $subjects;
    }

    public static function getSession(int $sessionNumber): ?array {
        return self::getSessionBlueprint($sessionNumber);
    }

    public static function getBlueprint(): array {
        return self::getBlueprintSummary();
    }

    /**
     * Get summary metadata of the full examination format.
     */
    public static function getBlueprintSummary(): array {
        return [
            'title' => 'TEST YOUR KNOWLEDGE',
            'subtitle' => 'Daily Psychometrician Board Examination Simulator',
            'total_questions' => self::TOTAL_QUESTIONS,
            'sessions_count' => 2,
            'disclaimer' => 'Mock examination blueprint based on the Psychometrician Board Examination simulation format. Question counts reflect the configured simulation blueprint, not an assertion of official PRC examination weightings.',
            'sessions' => array_values(self::SESSIONS)
        ];
    }
}
