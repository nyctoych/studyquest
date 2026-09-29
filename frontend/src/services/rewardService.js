import api from './api'

export const rewardService = {
  getAll:    (category = '') => api.get('/rewards', { params: { category } }),
  purchase:  (id)            => api.post(`/rewards/${id}/purchase`),
  inventory: ()              => api.get('/rewards/inventory'),
  useItem:   (id)            => api.post(`/rewards/${id}/use`),
}
