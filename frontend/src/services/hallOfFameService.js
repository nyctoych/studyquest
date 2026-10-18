import api from './api'

export const hallOfFameService = {
  /**
   * Fetch public approved announcements for the live scroll.
   * @param {number|null} batchId
   */
  getAnnouncements: (batchId = null) => {
    const params = batchId ? { batch_id: batchId } : {}
    return api.get('/hall-of-fame/announcements', { params })
  },

  /**
   * Fetch latest board exam passers ticker stream (approved users + official PRC roster).
   * @param {number} limit
   */
  getLatestPassers: (limit = 50) => api.get('/hall-of-fame/latest-passers', { params: { limit } }),

  /**
   * Fetch supported official PRC batches and metadata.
   */
  getBatches: () => api.get('/hall-of-fame/batches'),

  /**
   * Fetch authenticated user's submitted passing claims.
   */
  getMyClaims: () => api.get('/hall-of-fame/my-claims'),

  /**
   * Submit a board exam passing verification claim.
   * @param {Object} payload { batch_id, submitted_full_name, display_preference, consent_confirmed }
   */
  submitClaim: (payload) => api.post('/hall-of-fame/claim', payload),

  /**
   * Admin: Get verification review queue.
   * @param {string} status
   */
  getAdminQueue: (status = 'ALL') => api.get('/hall-of-fame/admin/queue', { params: { status } }),

  /**
   * Admin: Decide on a claim (APPROVE, REJECT, NEEDS_CORRECTION).
   * @param {Object} payload { claim_id, decision, notes }
   */
  adminDecide: (payload) => api.post('/hall-of-fame/admin/decide', payload),

  /**
   * Admin: Import official examinee roster text.
   * @param {Object} payload { batch_id, raw_roster }
   */
  adminImport: (payload) => api.post('/hall-of-fame/admin/import', payload),

  /**
   * Client-side masking preview algorithm matching backend implementation.
   * @param {string} fullName
   */
  maskNamePreview: (fullName) => {
    if (!fullName || typeof fullName !== 'string') return ''
    const parts = fullName.trim().split(/(\s+|-)/)
    return parts.map(part => {
      if (!part.trim() || part === '-' || /^[,\.]$/.test(part)) return part
      const clean = part.replace(/[,\.]/g, '')
      const len = clean.length
      let masked = ''
      if (len <= 2) {
        masked = clean[0] + '*'
      } else if (len === 3) {
        masked = clean[0] + '*' + clean[len - 1]
      } else if (len === 4) {
        masked = clean[0] + '**' + clean[len - 1]
      } else {
        masked = clean[0] + '*'.repeat(len - 2) + clean[len - 1]
      }
      if (part.endsWith('.')) masked += '.'
      if (part.endsWith(',')) masked += ','
      return masked
    }).join('')
  }
}

export default hallOfFameService
