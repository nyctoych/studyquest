import api from './api'

export const adaptiveService = {
  /**
   * Get student's current adaptive learning profile, mastery score, and readiness.
   */
  getProfile: () => api.get('/questions/adaptive-profile'),

  /**
   * Fetch level-adaptive practice questions.
   * @param {Object} params - { count, category_id, topic_id, mode: 'practice' | 'board_exam' }
   */
  getPracticeQuestions: ({ count = 10, category_id = null, topic_id = null, mode = 'practice' } = {}) => {
    const params = new URLSearchParams()
    if (count) params.append('count', count)
    if (category_id) params.append('category_id', category_id)
    if (topic_id) params.append('topic_id', topic_id)
    if (mode) params.append('mode', mode)
    return api.get(`/questions/practice?${params.toString()}`)
  },

  /**
   * Submit an answer to a question and trigger server-side adaptive recalculation.
   */
  submitAttempt: ({ question_id, selected_answer, response_time = 0, battle_id = null }) => {
    return api.post('/questions/attempt', {
      question_id,
      selected_answer,
      response_time,
      battle_id,
    })
  },

  /**
   * Get personalized session summary after practicing.
   */
  getSessionSummary: (count = 10) => api.get(`/questions/session-summary?count=${count}`),

  /**
   * Get weak areas and category accuracies.
   */
  getWeakAreas: () => api.get('/questions/weak-areas'),

  /**
   * Get official psychology categories.
   */
  getCategories: () => api.get('/questions/categories'),

  /**
   * Get subtopics for a category.
   */
  getTopics: (categoryId) => api.get(`/questions/topics?category_id=${categoryId}`),
}

export default adaptiveService
