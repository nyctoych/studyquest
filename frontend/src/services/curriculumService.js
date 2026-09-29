import api from './api'

export const curriculumService = {
  // Fetch all 12 domains with user progress
  async getDomains() {
    const res = await api.get('/curriculum/domains')
    return res.data
  },

  // Fetch single domain with all 10 parts
  async getDomainDetails(domainId) {
    const res = await api.get(`/curriculum/domains/${domainId}`)
    return res.data
  },

  // Fetch part content, concepts, and assessment questions
  async getPartDetails(partId) {
    const res = await api.get(`/curriculum/parts/${partId}`)
    return res.data
  },

  // Submit assessment answers
  async submitPartAssessment(partId, answers, timeSpent = 0) {
    const res = await api.post(`/curriculum/parts/${partId}/submit`, {
      answers,
      time_spent: timeSpent
    })
    return res.data
  },

  // Get student curriculum overview (summary, badges, next study part)
  async getOverview() {
    const res = await api.get('/curriculum/overview')
    return res.data
  },

  // Get active part recommendation for today
  async getTodayRecommendation() {
    const res = await api.get('/curriculum/today')
    return res.data
  }
}

export default curriculumService
