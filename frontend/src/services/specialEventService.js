import api from './api'

export const specialEventService = {
  // ── PLAYER ENDPOINTS ──
  getTodayEvent() {
    return api.get('/special-event/today').then(res => res.data)
  },

  startAttempt(eventId, sessionNumber = 1) {
    return api.post(`/special-event/${eventId}/start`, { session_number: sessionNumber }).then(res => res.data)
  },

  getOverview(eventId) {
    return api.get(`/special-event/${eventId}/overview`).then(res => res.data)
  },

  getUserAnalytics() {
    return api.get('/special-event/analytics').then(res => res.data)
  },

  getAudit() {
    return api.get('/special-event/audit').then(res => res.data)
  },

  getAttempt(attemptId) {
    return api.get(`/special-event/attempts/${attemptId}`).then(res => res.data)
  },

  submitAnswer(attemptId, { questionId, selectedChoice = null, isMarked = null, responseTime = 0 }) {
    return api.post(`/special-event/attempts/${attemptId}/answer`, {
      question_id: questionId,
      selected_choice: selectedChoice,
      is_marked: isMarked,
      response_time: responseTime
    }).then(res => res.data)
  },

  toggleMark(attemptId, questionId, isMarked) {
    return api.post(`/special-event/attempts/${attemptId}/mark`, {
      question_id: questionId,
      is_marked: isMarked
    }).then(res => res.data)
  },

  finishAttempt(attemptId) {
    return api.post(`/special-event/attempts/${attemptId}/submit`).then(res => res.data)
  },

  disqualifyAttempt(attemptId, reason = 'Cheating') {
    return api.post(`/special-event/attempts/${attemptId}/disqualify`, { reason }).then(res => res.data)
  },

  recordTabSwitch(attemptId, count = 1) {
    return api.post(`/special-event/attempts/${attemptId}/tab-switch`, { count }).then(res => res.data)
  },

  getHistory() {
    return api.get('/special-event/history').then(res => res.data)
  },

  getReview(attemptId) {
    return api.get(`/special-event/attempts/${attemptId}/review`).then(res => res.data)
  },

  getBloom(attemptId) {
    return api.get(`/special-event/attempts/${attemptId}/bloom`).then(res => res.data)
  },

  getBloomHistory() {
    return api.get('/special-event/bloom/history').then(res => res.data)
  },

  getBloomDomains(attemptId) {
    return api.get(`/special-event/attempts/${attemptId}/bloom/domains`).then(res => res.data)
  },

  // ── ADMIN / DEVELOPER CONTROLS ──
  adminGenerate(date = '', force = false) {
    return api.post('/special-event/admin/generate', { date, force }).then(res => res.data)
  },

  adminGetEvents() {
    return api.get('/special-event/admin/events').then(res => res.data)
  },

  adminGetQuestions(eventId) {
    return api.get(`/special-event/admin/events/${eventId}/questions`).then(res => res.data)
  },

  adminApproveQuestion(questionId) {
    return api.post(`/special-event/admin/questions/${questionId}/approve`).then(res => res.data)
  },

  adminRejectQuestion(questionId) {
    return api.post(`/special-event/admin/questions/${questionId}/reject`).then(res => res.data)
  },

  adminReplaceQuestion(questionId) {
    return api.post(`/special-event/admin/questions/${questionId}/replace`).then(res => res.data)
  },

  adminPublishEvent(eventId) {
    return api.post(`/special-event/admin/events/${eventId}/publish`).then(res => res.data)
  },

  adminGetSettings() {
    return api.get('/special-event/admin/settings').then(res => res.data)
  },

  adminUpdateSettings(settings) {
    return api.post('/special-event/admin/settings', settings).then(res => res.data)
  }
}
