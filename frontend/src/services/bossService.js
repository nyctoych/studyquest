import api from './api'

export const bossService = {
  getAll:        ()           => api.get('/bosses'),
  getById:       (id)         => api.get(`/bosses/${id}`),
  start:         (bossId)     => api.post(`/bosses/${bossId}/start`),
  recordVictory: (bossId, data = {}) => api.post(`/bosses/${bossId}/victory`, data),
  recordDefeat:  (bossId, data = {}) => api.post(`/bosses/${bossId}/defeat`, data),
  getAttempt:    (attemptId)  => api.get(`/attempts/${attemptId}`),
  submitAnswer:  (attemptId, answer) => api.post(`/attempts/${attemptId}/answer`, { answer }),
  history:       ()           => api.get('/bosses/history'),
}
