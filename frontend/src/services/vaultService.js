import api from './api'

export const vaultService = {
  /**
   * Fetch current user's vault reviewers
   */
  async getReviewers(params = {}) {
    const res = await api.get('/vault', { params })
    return res.data
  },

  /**
   * Upload a new reviewer file with progress tracking
   */
  async uploadReviewer(formData, onUploadProgress = null) {
    const res = await api.post('/vault/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress
    })
    return res.data
  },

  /**
   * Get single reviewer metadata
   */
  async getReviewer(id) {
    const res = await api.get(`/vault/${id}`)
    return res.data
  },

  /**
   * Update reviewer title / description
   */
  async updateMetadata(id, data) {
    const res = await api.put(`/vault/${id}`, data)
    return res.data
  },

  /**
   * Delete reviewer record and physical file
   */
  async deleteReviewer(id) {
    const res = await api.delete(`/vault/${id}`)
    return res.data
  },

  /**
   * Get parsed document structure for modal preview (DOCX, PPTX, TXT)
   */
  async getDocumentContent(id) {
    const res = await api.get(`/vault/${id}/content`)
    return res.data
  },

  /**
   * Persistently save reviewer content changes and text highlights
   */
  async saveContent(id, data) {
    const res = await api.put(`/vault/${id}/content`, data)
    return res.data
  },

  /**
   * Get public vault reviewers for another user (for Hall of Records profile)
   */
  async getUserVault(userId) {
    const res = await api.get(`/vault/user/${userId}`)
    return res.data
  },

  /**
   * Resolve secure preview URL (for iframe PDF stream)
   */
  getPreviewUrl(id) {
    const baseURL = api.defaults.baseURL || ''
    return `${baseURL}/vault/${id}/preview`
  },

  /**
   * Resolve secure download URL
   */
  getDownloadUrl(id) {
    const baseURL = api.defaults.baseURL || ''
    return `${baseURL}/vault/${id}/download`
  }
}

export default vaultService
