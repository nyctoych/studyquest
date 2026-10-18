import api from './api'

export const achievementService = {
  /**
   * Fetch all 30 cards, 32 milestones, and summary stats for the current player.
   */
  getAll: () => api.get('/achievements'),

  /**
   * Atomically claim a milestone reward.
   * @param {number} milestoneId
   */
  claim: (milestoneId) => api.post('/achievements/claim', { milestone_id: milestoneId }),

  /**
   * Atomically claim all unlocked and unclaimed milestones at once.
   */
  claimAll: () => api.post('/achievements/claim-all'),

  /**
   * Retrieve public achievement details by token.
   * @param {string} token
   */
  getPublicShare: (token) => api.get(`/achievements/public/${token}`)
}

export default achievementService
