import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

function getCacheKey(userId) {
  return `sq_battle_prefs_${userId}`
}

function loadCachedPrefs(userId) {
  if (!userId) return null
  try {
    const raw = localStorage.getItem(getCacheKey(userId))
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

function saveCachedPrefs(userId, prefs) {
  if (!userId) return
  try {
    localStorage.setItem(getCacheKey(userId), JSON.stringify(prefs))
  } catch (_) {}
}

export const useBattlefieldPreferencesStore = defineStore('battlefieldPreferences', () => {
  const skipAnimation = ref(false)
  const autoConfirm   = ref(false)
  const criticalMode  = ref(false)
  const loaded        = ref(false)
  const currentUserId = ref(null)

  function applyPrefs(prefs, userId) {
    if (!prefs) return
    if (prefs.skip_animation !== undefined) skipAnimation.value = Boolean(prefs.skip_animation)
    if (prefs.skipAnimation !== undefined)  skipAnimation.value = Boolean(prefs.skipAnimation)

    if (prefs.auto_confirm !== undefined)   autoConfirm.value   = Boolean(prefs.auto_confirm)
    if (prefs.autoConfirm !== undefined)    autoConfirm.value   = Boolean(prefs.autoConfirm)

    if (prefs.critical_mode !== undefined)  criticalMode.value  = Boolean(prefs.critical_mode)
    if (prefs.criticalMode !== undefined)   criticalMode.value  = Boolean(prefs.criticalMode)

    loaded.value = true
    if (userId) {
      saveCachedPrefs(userId, {
        skip_animation: skipAnimation.value,
        auto_confirm:   autoConfirm.value,
        critical_mode:  criticalMode.value,
      })
    }
  }

  /**
   * Initialize preferences for an authenticated user account.
   * Loads from user object or account-scoped cache first to prevent UI flicker,
   * then reconciles with server.
   */
  async function initFromUser(user) {
    if (!user || !user.id) return
    const userId = Number(user.id)

    // Check account change
    if (currentUserId.value !== userId) {
      currentUserId.value = userId
      loaded.value = false
    }

    // 1. Check account-scoped localStorage cache
    const cached = loadCachedPrefs(userId)
    if (cached) {
      applyPrefs(cached, userId)
    }

    // 2. Check server-provided preferences in user payload
    if (user.battlefield_preferences) {
      applyPrefs(user.battlefield_preferences, userId)
    } else {
      // 3. Reconcile with server preferences endpoint
      await fetchPreferences(userId)
    }
  }

  async function fetchPreferences(userId) {
    const targetId = userId || currentUserId.value
    if (!targetId) return

    try {
      const res = await api.get('/battlefield/preferences')
      if (res.data?.success && res.data?.preferences) {
        applyPrefs(res.data.preferences, targetId)
      }
    } catch (err) {
      console.warn('[BattlefieldPreferences] Could not sync preferences from server:', err)
    }
  }

  async function setSkipAnimation(value) {
    const val = Boolean(value)
    skipAnimation.value = val
    if (currentUserId.value) {
      saveCachedPrefs(currentUserId.value, {
        skip_animation: skipAnimation.value,
        auto_confirm:   autoConfirm.value,
        critical_mode:  criticalMode.value,
      })
    }
    try {
      await api.post('/battlefield/preferences', { skip_animation: val })
    } catch (err) {
      console.error('[BattlefieldPreferences] Failed to persist skip_animation:', err)
    }
  }

  async function setAutoConfirm(value) {
    const val = Boolean(value)
    autoConfirm.value = val
    if (currentUserId.value) {
      saveCachedPrefs(currentUserId.value, {
        skip_animation: skipAnimation.value,
        auto_confirm:   autoConfirm.value,
        critical_mode:  criticalMode.value,
      })
    }
    try {
      await api.post('/battlefield/preferences', { auto_confirm: val })
    } catch (err) {
      console.error('[BattlefieldPreferences] Failed to persist auto_confirm:', err)
    }
  }

  async function setCriticalMode(value) {
    const val = Boolean(value)
    criticalMode.value = val
    if (currentUserId.value) {
      saveCachedPrefs(currentUserId.value, {
        skip_animation: skipAnimation.value,
        auto_confirm:   autoConfirm.value,
        critical_mode:  criticalMode.value,
      })
    }
    try {
      await api.post('/battlefield/preferences', { critical_mode: val })
    } catch (err) {
      console.error('[BattlefieldPreferences] Failed to persist critical_mode:', err)
    }
  }

  /**
   * Reset in-memory session on logout.
   * Does NOT reset or delete server preferences or other users' caches.
   */
  function clearSession() {
    skipAnimation.value = false
    autoConfirm.value   = false
    criticalMode.value  = false
    currentUserId.value = null
    loaded.value        = false
  }

  return {
    skipAnimation,
    autoConfirm,
    criticalMode,
    loaded,
    currentUserId,
    initFromUser,
    fetchPreferences,
    setSkipAnimation,
    setAutoConfirm,
    setCriticalMode,
    clearSession,
  }
})
