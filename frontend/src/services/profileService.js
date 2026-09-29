import api from './api'

export const profileService = {
  get:    ()     => api.get('/profile'),
  update: (data) => api.put('/profile', data),
  stats:  ()     => api.get('/profile/stats'),
}
