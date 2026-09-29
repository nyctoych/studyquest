import axios from 'axios'

// Automatically resolve backend URL relative to current host and path
const pathSegments = typeof window !== 'undefined' ? window.location.pathname.split('/').filter(Boolean) : []
const basePath = pathSegments.length > 0 && pathSegments[0].toLowerCase() === 'studyquest' ? `/${pathSegments[0]}` : '/StudyQuest'
const BASE_URL = typeof window !== 'undefined' ? `${window.location.origin}${basePath}/backend/index.php` : 'http://localhost/StudyQuest/backend/index.php'


const api = axios.create({
  baseURL: BASE_URL,
  withCredentials: true, // send session cookie
  headers: { 'Content-Type': 'application/json' },
})

// Request interceptor
api.interceptors.request.use(config => config, error => Promise.reject(error))

// Response interceptor — handle 401 globally
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Redirect to login if not already there
      if (!window.location.hash.includes('/login')) {
        window.location.hash = '/login'
      }
    }
    return Promise.reject(error)
  }
)

export default api
