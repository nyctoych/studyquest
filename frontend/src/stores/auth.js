import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

import necroProfileImg from '../assets/PROFILE-AVATAR/NecroProfile.png'
import meleeProfileImg from '../assets/PROFILE-AVATAR/MeleeProfile.png'
import archerProfileImg from '../assets/PROFILE-AVATAR/ArcherProfile.png'

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
  const points      = computed(() => user.value?.coins ?? user.value?.points ?? 0)
  const coins       = computed(() => user.value?.coins ?? user.value?.points ?? 0)
  const streak      = computed(() => user.value?.streak ?? 0)
  const battleRating = computed(() => user.value?.battle_rating ?? 1200)
  const nickname    = computed(() => user.value?.nickname ?? user.value?.username ?? 'Scholar')
  const avatarStyle = computed(() => user.value?.avatar_style ?? 'adventurer')
  const avatarSeed  = computed(() => user.value?.avatar_seed ?? 'default')
  const avatarOpts  = computed(() => user.value?.avatar_options ?? {})
  const equipped    = computed(() => user.value?.equipped ?? [])
  const isNewPlayer = computed(() => !!user.value?.is_new_player)
  const selectedCharacter = computed(() => user.value?.selected_character || null)

  const xpInLevel = computed(() => {
    if (stats.value?.xp_in_level !== undefined && stats.value?.xp_in_level !== null) {
      return Number(stats.value.xp_in_level)
    }
    const lvl = level.value || 1
    const totalXp = xp.value || 0
    let xpUsed = 0
    for (let l = 1; l < lvl; l++) {
      xpUsed += l * 1000
    }
    return Math.max(0, totalXp - xpUsed)
  })

  const xpForNextLevel = computed(() => {
    if (stats.value?.xp_for_next_level !== undefined && stats.value?.xp_for_next_level !== null) {
      return Number(stats.value.xp_for_next_level)
    }
    const lvl = level.value || 1
    return lvl * 1000
  })

  const xpPercent = computed(() => {
    const req = xpForNextLevel.value || 1000
    const cur = xpInLevel.value || 0
    return Math.min(100, Math.max(0, Math.round((cur / req) * 100)))
  })

  const characterClass = computed(() => {
    const raw = user.value?.selected_character?.class_type || 
                user.value?.selected_character?.key_name || 
                (user.value?.selected_character_id === 2 ? 'MELEE' : user.value?.selected_character_id === 3 ? 'RANGE' : 'MAGE')
    const s = String(raw || '').trim().toUpperCase()
    if (s.includes('MELE') || s.includes('DUAL')) return 'MELEE'
    if (s.includes('RANG') || s.includes('ARCH')) return 'RANGE'
    return 'MAGE'
  })

  const profileAvatar = computed(() => {
    if (characterClass.value === 'MELEE') return meleeProfileImg
    if (characterClass.value === 'RANGE') return archerProfileImg
    return necroProfileImg
  })

  async function fetchUser() {
    try {
      const res = await api.get('/auth/me')
      if (res.data?.success && res.data?.user) {
        user.value  = res.data.user
        stats.value = res.data.stats || null
        try { localStorage.setItem('sq_user', JSON.stringify(res.data.user)) } catch (_) {}
        if (res.data.user?.is_new_player) {
          try { localStorage.setItem('studyquest_theme', 'night') } catch (_) {}
        }

        // Authoritative Adventure health sync based on selected character and level
        const healthData = res.data.health || res.data.user?.adventure_health
        if (healthData) {
          try {
            const { useHealthStore } = await import('./health')
            useHealthStore().applyHealthData(healthData)
          } catch (_) {}
        }

        // Authoritative Battlefield preferences sync
        try {
          const { useBattlefieldPreferencesStore } = await import('./battlefieldPreferences')
          useBattlefieldPreferencesStore().initFromUser(res.data.user)
        } catch (_) {}
        return true
      } else {
        user.value = null
        stats.value = null
        try { localStorage.removeItem('sq_user') } catch (_) {}
        return false
      }
    } catch (err) {
      // ONLY clear session if server explicitly responded with 401 (Not authenticated)
      if (err?.response?.status === 401) {
        user.value = null
        stats.value = null
        try { localStorage.removeItem('sq_user') } catch (_) {}
        return false
      }
      // If the request was aborted by a rapid reload, network cancel, or transient error,
      // preserve the existing cached user so the player is not bounced to /login.
      if (user.value) {
        return true
      }
      return false
    }
  }

  let initPromise = null
  /** Call once at app startup to restore session from the server cookie. */
  async function init() {
    if (initialized.value) return
    if (!initPromise) {
      initPromise = fetchUser().finally(() => {
        initialized.value = true
        initPromise = null
      })
    }
    await initPromise
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
        try { localStorage.setItem('studyquest_theme', 'night') } catch (_) {}
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
    try { localStorage.setItem('studyquest_theme', 'night') } catch (_) {}
    try {
      const { useBattlefieldPreferencesStore } = await import('./battlefieldPreferences')
      useBattlefieldPreferencesStore().clearSession()
    } catch (_) {}
    try {
      const { useWelcomeModalStore } = await import('./welcomeModal')
      useWelcomeModalStore().clearSession()
    } catch (_) {}
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
        try {
          const { useAttributeAllocationStore } = await import('./attributeAllocation')
          const allocStore = useAttributeAllocationStore()
          await allocStore.fetchAllocations(characterId)
        } catch (_) {}
        return { success: true, message: res.data.message }
      }
      return { success: false, message: res.data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to select character' }
    }
  }

  async function updatePassword(currentPassword, newPassword) {
    try {
      const res = await api.post('/auth/password/update', { currentPassword, newPassword })
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to update password' }
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
    xpInLevel, xpForNextLevel, xpPercent,
    nickname, avatarStyle, avatarSeed, avatarOpts, equipped, isNewPlayer,
    selectedCharacter, characterClass, profileAvatar,
    init, fetchUser, login, register, logout, selectCharacter, updateUser, updateStats,
    updatePassword,
  }
})
