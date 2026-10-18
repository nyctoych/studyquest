import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { bgmService } from '../services/bgmService'
import { battleAudio } from '../services/battleAudio'

export const useGameStore = defineStore('game', () => {
  // Loading screen state
  const loadingComplete = ref(false)
  const loadingProgress = ref(0)

  // Load saved preferences from localStorage
  const savedSettings = (() => {
    try {
      const s = localStorage.getItem('studyquest_settings')
      return s ? JSON.parse(s) : null
    } catch {
      return null
    }
  })()

  // Audio preferences
  const audioEnabled = ref(savedSettings?.sfxEnabled ?? true)
  const musicEnabled = ref(savedSettings?.musicEnabled ?? true)
  const masterVolume = ref(typeof savedSettings?.volume === 'number' ? savedSettings.volume : 80) // 0-100

  // Apply initial volume settings to services
  bgmService.setMasterVolume(masterVolume.value / 100)
  battleAudio.setVolume(masterVolume.value / 100)
  battleAudio.setSfxEnabled(audioEnabled.value)

  // World state
  const worldReady = ref(false)

  function setLoadingComplete() {
    loadingComplete.value = true
    worldReady.value = true
  }

  function setAudio(val) {
    audioEnabled.value = Boolean(val)
    battleAudio.setSfxEnabled(audioEnabled.value)
    persistSettings()
  }

  function setMusic(val) {
    musicEnabled.value = Boolean(val)
    bgmService.setMusicEnabled(musicEnabled.value)
    persistSettings()
  }

  function setMasterVolume(val) {
    const num = Math.max(0, Math.min(100, Math.round(Number(val) || 0)))
    masterVolume.value = num
    const normalized = num / 100
    bgmService.setMasterVolume(normalized)
    battleAudio.setVolume(normalized)
    persistSettings()
  }

  function increaseVolume(step = 10) {
    if (!musicEnabled.value) {
      musicEnabled.value = true
      bgmService.setMusicEnabled(true)
    }
    const next = Math.min(100, masterVolume.value + step)
    setMasterVolume(next)
  }

  function decreaseVolume(step = 10) {
    const next = Math.max(0, masterVolume.value - step)
    setMasterVolume(next)
    if (next === 0) {
      setMusic(false)
    }
  }

  function toggleMute() {
    setMusic(!musicEnabled.value)
  }

  function persistSettings() {
    try {
      const current = JSON.parse(localStorage.getItem('studyquest_settings') || '{}')
      current.sfxEnabled = audioEnabled.value
      current.musicEnabled = musicEnabled.value
      current.volume = masterVolume.value
      localStorage.setItem('studyquest_settings', JSON.stringify(current))
    } catch (e) {
      // Ignore localStorage errors
    }
  }

  function setLoadingProgress(val) {
    loadingProgress.value = Math.min(100, Math.max(0, val))
  }

  // Routes where light mode / morning theme must NEVER be applied (pre-realm routes)
  const LIGHT_MODE_FORBIDDEN_ROUTES = ['/', '/login', '/register', '/character-selection', '/tutorial']

  // Current session & route context for theme gatekeeper
  const initialUser = (() => {
    try {
      const raw = localStorage.getItem('sq_user')
      return raw ? JSON.parse(raw) : null
    } catch {
      return null
    }
  })()

  // User's saved theme preference (persisted in localStorage, default is always 'night')
  const initialSavedTheme = (() => {
    try {
      const savedTheme = localStorage.getItem('studyquest_theme')
      const hash = (typeof window !== 'undefined' ? (window.location.hash || '').toLowerCase() : '')
      const isPublicThemeable = hash.includes('/home') || hash.includes('/terms') || hash.includes('/privacy')
      if (savedTheme === 'morning') {
        if (isPublicThemeable) return 'morning'
        if (initialUser && !(initialUser.is_new_player === 1 || initialUser.is_new_player === true || initialUser.is_new_player === '1')) {
          return 'morning'
        }
      }
      return 'night'
    } catch {
      return 'night'
    }
  })()

  const savedTheme = ref(initialSavedTheme)

  const initialRoutePath = (() => {
    try {
      if (typeof window !== 'undefined') {
        const hash = (window.location.hash || '').replace(/^#/, '').split('?')[0]
        return hash || '/'
      }
      return '/'
    } catch {
      return '/'
    }
  })()

  const currentRoutePath = ref(initialRoutePath)
  const currentIsLoggedIn = ref(!!initialUser)
  const currentIsNewPlayer = ref(!!(initialUser && (initialUser.is_new_player === 1 || initialUser.is_new_player === true || initialUser.is_new_player === '1')))

  // Light mode can be applied on /home and public info pages, or when logged in and user has accessed the realm (not on pre-realm routes)
  const canUseLightMode = computed(() => {
    const p = (currentRoutePath.value || '/').toLowerCase().split('?')[0]
    if (p === '/home' || p === '/terms' || p === '/privacy') return true
    if (!currentIsLoggedIn.value) return false
    if (currentIsNewPlayer.value) return false
    return !LIGHT_MODE_FORBIDDEN_ROUTES.includes(p)
  })

  // Effective active theme state
  const isMorning = computed(() => canUseLightMode.value && savedTheme.value === 'morning')
  const theme = computed(() => isMorning.value ? 'morning' : 'night')

  function applyTheme(t) {
    if (typeof document !== 'undefined') {
      document.documentElement.setAttribute('data-theme', t)
      if (t === 'morning') {
        document.documentElement.classList.add('theme-morning')
        document.documentElement.classList.remove('theme-night')
        document.body.classList.add('theme-morning')
        document.body.classList.remove('theme-night')
      } else {
        document.documentElement.classList.add('theme-night')
        document.documentElement.classList.remove('theme-morning')
        document.body.classList.add('theme-night')
        document.body.classList.remove('theme-morning')
      }
    }
  }

  function applyEffectiveTheme() {
    applyTheme(isMorning.value ? 'morning' : 'night')
  }

  function syncThemeContext(routePath, loggedIn, isNewPlayer) {
    if (typeof routePath === 'string') {
      currentRoutePath.value = routePath
    }
    if (typeof loggedIn === 'boolean') {
      currentIsLoggedIn.value = loggedIn
    }
    if (typeof isNewPlayer === 'boolean') {
      currentIsNewPlayer.value = isNewPlayer
    }
    applyEffectiveTheme()
  }

  function setTheme(newTheme) {
    // Only allow setting morning if user has accessed the realm and is in a valid state
    const targetTheme = (newTheme === 'morning' && canUseLightMode.value) ? 'morning' : 'night'
    savedTheme.value = targetTheme
    try {
      localStorage.setItem('studyquest_theme', targetTheme)
    } catch (e) {
      // Ignore localStorage error
    }
    applyEffectiveTheme()
  }

  function toggleTheme() {
    // Cannot toggle into light mode unless already in the realm
    if (!canUseLightMode.value) {
      setTheme('night')
      return
    }
    setTheme(savedTheme.value === 'night' ? 'morning' : 'night')
  }

  // Initial apply on store creation
  applyEffectiveTheme()

  return {
    loadingComplete, loadingProgress, audioEnabled, musicEnabled, masterVolume, worldReady,
    theme, savedTheme, isMorning, canUseLightMode,
    setLoadingComplete, setAudio, setMusic, setMasterVolume, setLoadingProgress,
    increaseVolume, decreaseVolume, toggleMute,
    setTheme, toggleTheme, applyTheme, syncThemeContext, applyEffectiveTheme,
  }
})
