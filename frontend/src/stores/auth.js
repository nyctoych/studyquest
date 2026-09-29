import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const stats = ref(null)
  const loading = ref(false)

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

  async function fetchUser() {
    try {
      const res = await api.get('/auth/me')
      if (res.data.success) {
        user.value  = res.data.user
        stats.value = res.data.stats
      }
    } catch {
      user.value = null
    }
  }

  async function login(identifier, password) {
    loading.value = true
    try {
      const res = await api.post('/auth/login', { email: identifier, password })
      if (res.data.success) {
        user.value = res.data.user
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
    await api.post('/auth/logout')
    user.value  = null
    stats.value = null
  }

  function updateUser(updatedUser) {
    user.value = { ...user.value, ...updatedUser }
  }

  function updateStats(updatedStats) {
    stats.value = { ...stats.value, ...updatedStats }
  }

  return {
    user, stats, loading,
    isLoggedIn, level, xp, points, coins, streak, battleRating,
    nickname, avatarStyle, avatarSeed, avatarOpts, equipped, isNewPlayer,
    fetchUser, login, register, logout, updateUser, updateStats,
  }
})
