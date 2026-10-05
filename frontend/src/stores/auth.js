import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

function loadStoredUser() {
  try {
    const raw = localStorage.getItem('sq_user')
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const user        = ref(loadStoredUser())
  const stats       = ref(null)
  const loading     = ref(false)
  const initialized = ref(false) // true after first session-restore attempt

  const isLoggedIn  = computed(() => !!user.value)
  const level       = computed(() => user.value?.level ?? 1)
  const xp          = computed(() => user.value?.xp ?? 0)
  const points      = computed(() => user.value?.points ?? 0)
  const coins       = computed(() => user.value?.coins ?? 0)
  const streak      = computed(() => user.value?.streak ?? 0)
  const battleRating = computed(() => user.value?.battle_rating ?? 1200)
  const nickname    = computed(() => user.value?.nickname ?? user.value?.username ?? 'Scholar')
  const avatarStyle = computed(() => user.value?.avatar_style ?? 'adventurer')
  const avatarSeed  = computed(() => user.value?.avatar_seed ?? 'default')
  const avatarOpts  = computed(() => user.value?.avatar_options ?? {})
  const equipped    = computed(() => user.value?.equipped ?? [])
  const isNewPlayer = computed(() => !!user.value?.is_new_player)
  const selectedCharacter = computed(() => user.value?.selected_character || null)

  async function fetchUser() {
    try {
      const res = await api.get('/auth/me')
      if (res.data.success && res.data.user) {
        user.value  = res.data.user
        stats.value = res.data.stats || null
        try { localStorage.setItem('sq_user', JSON.stringify(res.data.user)) } catch (_) {}

        // Authoritative Adventure health sync based on selected character and level
        const healthData = res.data.health || res.data.user?.adventure_health
        if (healthData) {
          try {
            const { useHealthStore } = await import('./health')
            useHealthStore().applyHealthData(healthData)
          } catch (_) {}
        }
        return true
      } else {
        user.value = null
        stats.value = null
        try { localStorage.removeItem('sq_user') } catch (_) {}
        return false
      }
    } catch {
      user.value = null
      stats.value = null
      try { localStorage.removeItem('sq_user') } catch (_) {}
      return false
    }
  }

  /** Call once at app startup to restore session from the server cookie. */
  async function init() {
    if (initialized.value) return
    await fetchUser()
    initialized.value = true
  }

  async function login(identifier, password) {
    loading.value = true
    try {
      const res = await api.post('/auth/login', { email: identifier, password })
      if (res.data.success) {
        user.value = res.data.user
        try { localStorage.setItem('sq_user', JSON.stringify(res.data.user)) } catch (_) {}
        await fetchUser()
        return { success: true, message: res.data.message }
      }
      return { success: false, message: res.data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Login failed' }
    } finally {
      loading.value = false
    }
  }

  async function register(username, email, password, nicknameVal) {
    loading.value = true
    try {
      const res = await api.post('/auth/register', { username, email, password, nickname: nicknameVal || username })
      if (res.data.success) {
        user.value = res.data.user
        try { localStorage.setItem('sq_user', JSON.stringify(res.data.user)) } catch (_) {}
        return { success: true, message: res.data.message }
      }
      return { success: false, message: res.data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Registration failed' }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (_) {}
    user.value  = null
    stats.value = null
    try { localStorage.removeItem('sq_user') } catch (_) {}
  }

  async function selectCharacter(characterId) {
    try {
      const res = await api.post('/characters/select', { character_id: characterId })
      if (res.data.success) {
        if (res.data.user) {
          user.value = { ...user.value, ...res.data.user }
        } else {
          user.value = { ...user.value, selected_character_id: characterId }
        }
        if (res.data.character) {
          user.value.selected_character = res.data.character
        }
        try { localStorage.setItem('sq_user', JSON.stringify(user.value)) } catch (_) {}
        try {
          const { useHealthStore } = await import('./health')
          const healthStore = useHealthStore()
          healthStore.syncFromUser(user.value)
          healthStore.fetchHealth(true)
        } catch (_) {}
        return { success: true, message: res.data.message }
      }
      return { success: false, message: res.data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to select character' }
    }
  }

  function updateUser(updatedUser) {
    user.value = { ...user.value, ...updatedUser }
    try { localStorage.setItem('sq_user', JSON.stringify(user.value)) } catch (_) {}
  }

  function updateStats(updatedStats) {
    stats.value = { ...stats.value, ...updatedStats }
  }

  return {
    user, stats, loading, initialized,
    isLoggedIn, level, xp, points, coins, streak, battleRating,
    nickname, avatarStyle, avatarSeed, avatarOpts, equipped, isNewPlayer,
    selectedCharacter,
    init, fetchUser, login, register, logout, selectCharacter, updateUser, updateStats,
  }
})
