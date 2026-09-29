import api from './api'

export const questService = {
  getAll:   (params = {}) => api.get('/quests', { params }),
  getById:  (id)          => api.get(`/quests/${id}`),
  create:   (data)        => api.post('/quests', data),
  update:   (id, data)    => api.put(`/quests/${id}`, data),
  complete: (id)          => api.post(`/quests/${id}/complete`),
  delete:   (id)          => api.delete(`/quests/${id}`),
  today:    ()            => api.get('/quests/today'),
  toggleSubtask: (questId, subtaskId) => api.post(`/quests/${questId}/subtasks/${subtaskId}`),
}
