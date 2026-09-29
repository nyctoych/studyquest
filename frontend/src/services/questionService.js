import api from './api'

export const questionService = {
  getCategories: () => api.get('/questions/categories'),
  getTopics: (categoryId) => api.get(`/questions/topics?category_id=${categoryId}`),
  getPractice: (count = 10, categoryId = null) => {
    let url = `/questions/practice?count=${count}`
    if (categoryId) url += `&category_id=${categoryId}`
    return api.get(url)
  },
  submitAttempt: (questionId, selectedAnswer, responseTime = 0, battleId = null) => {
    return api.post('/questions/attempt', {
      question_id: questionId,
      selected_answer: selectedAnswer,
      response_time: responseTime,
      battle_id: battleId,
    })
  },
  getAdaptiveProfile: () => api.get('/questions/adaptive-profile'),
  getSessionSummary: (count = 10) => api.get(`/questions/session-summary?count=${count}`),
  getWeakAreas: () => api.get('/questions/weak-areas'),
  getAnalytics: () => api.get('/questions/analytics'),
  generateAI: (data) => api.post('/questions/generate-ai', data),
  reviewQuestion: (id, status) => api.post(`/questions/${id}/review`, { status }),
}

export default questionService
