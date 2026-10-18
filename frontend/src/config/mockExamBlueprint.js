/**
 * mockExamBlueprint.js
 * Centralized mock board examination blueprint configuration.
 * Configures the two-session structure, subject breakdown, and item counts.
 */
export const MOCK_EXAM_BLUEPRINT = {
  title: 'TEST YOUR KNOWLEDGE',
  subtitle: 'Daily Psychometrician Board Examination Simulator',
  description: 'Challenge your understanding with a structured, two-session mock board examination. Every question is grounded in your Study Hall reviewers, helping you identify knowledge gaps, strengthen academic reasoning, and track your progress over time.',
  disclaimer: 'Mock examination blueprint based on the Psychometrician Board Examination simulation format. Question counts reflect the configured simulation blueprint, not an assertion of official PRC examination weightings.',
  totalQuestions: 450,
  sessions: [
    {
      session_number: 1,
      name: 'Session 1 — Examination Day 1',
      day_label: 'Examination Day 1',
      target_questions: 200,
      default_duration_minutes: 200,
      description: 'Focuses on foundational personality systems, developmental milestones, and clinical psychopathology.',
      subjects: [
        {
          code: 'top_dev',
          name: 'Theories of Personality / Developmental Psychology',
          short_name: 'Theories of Personality & Dev Psych',
          target_questions: 100,
          proportion_percentage: 22.22,
          session_proportion: 50.0,
          icon: '📜',
          color: '#8b5cf6'
        },
        {
          code: 'abnormal_psych',
          name: 'Abnormal Psychology',
          short_name: 'Abnormal Psychology',
          target_questions: 100,
          proportion_percentage: 22.22,
          session_proportion: 50.0,
          icon: '🧠',
          color: '#ec4899'
        }
      ]
    },
    {
      session_number: 2,
      name: 'Session 2 — Examination Day 2',
      day_label: 'Examination Day 2',
      target_questions: 250,
      default_duration_minutes: 250,
      description: 'Evaluates industrial-organizational psychology principles and comprehensive psychological assessment.',
      subjects: [
        {
          code: 'io_psych',
          name: 'Industrial and Organizational Psychology',
          short_name: 'Industrial & Organizational Psych',
          target_questions: 100,
          proportion_percentage: 22.22,
          session_proportion: 40.0,
          icon: '🏢',
          color: '#f97316'
        },
        {
          code: 'psych_assessment',
          name: 'Psychological Assessment',
          short_name: 'Psychological Assessment',
          target_questions: 150,
          proportion_percentage: 33.33,
          session_proportion: 60.0,
          icon: '📋',
          color: '#06b6d4'
        }
      ]
    }
  ]
}

export function getSessionConfig(sessionNumber) {
  return MOCK_EXAM_BLUEPRINT.sessions.find(s => s.session_number === Number(sessionNumber)) || MOCK_EXAM_BLUEPRINT.sessions[0]
}
