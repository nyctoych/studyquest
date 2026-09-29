import api from './api'

export const multiplayerService = {
  async getQuestions(params = {}) {
    const res = await api.get('/multiplayer/questions', { params })
    return res.data
  },

  async getCategories() {
    const res = await api.get('/multiplayer/questions/categories')
    return res.data
  },

  async getHistory() {
    const res = await api.get('/multiplayer/history')
    return res.data
  },

  async getStats() {
    const res = await api.get('/multiplayer/stats')
    return res.data
  },

  async getLeaderboard(type = 'rating') {
    const res = await api.get('/multiplayer/leaderboard', { params: { type } })
    return res.data
  },

  async getMyRank() {
    const res = await api.get('/multiplayer/my-rank')
    return res.data
  }
}

export default multiplayerService
