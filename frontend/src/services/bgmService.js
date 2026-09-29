/**
 * StudyQuest — Global Background Music Service
 * Comprehensive RPG Audio Zone Director:
 * 1. 'auth'        -> BG-MUSIC-LANDINGPAGE-AUTHENTICATION.mp3 (Landing Page, Login, Register)
 * 2. 'world'       -> BG-MUSIC-LANDINGPAGE.mp3 (World Hub, Quests, Shop, Leaderboard, Profile, etc.)
 * 3. 'adventure'   -> BG-MUSIC-ADVENTURE.mp3 (Adventure Realm, Chapter Map & Story Exploration)
 * 4. 'battlefield' -> BG-MUSIC-BATTLEFIELD.mp3 (Active Combat in Adventure Trials, Boss Battles & Multiplayer Arena)
 * 
 * Rules:
 * - Every time leaving/entering or switching modules, the background music resets to 0:00 and restarts.
 */

import { resolveAsset } from './bossMapping'

const TRACK_CONFIGS = {
  auth: {
    src: resolveAsset('assets/MUSIC/BG-MUSIC-LANDINGPAGE-AUTHENTICATION.mp3'),
    volume: 0.45
  },
  world: {
    src: resolveAsset('assets/MUSIC/BG-MUSIC-LANDINGPAGE.mp3'),
    volume: 0.45
  },
  adventure: {
    src: resolveAsset('assets/MUSIC/BG-MUSIC-ADVENTURE.mp3'),
    volume: 0.45
  },
  battlefield: {
    src: resolveAsset('assets/MUSIC/BG-MUSIC-BATTLEFIELD.mp3'),
    volume: 0.40 // Balanced slightly lower to ensure synthesized combat SFX are punchy
  }
}

function safeResetTime(audio) {
  if (!audio) return
  try {
    audio.currentTime = 0
  } catch (e) {
    // Ignore if not seekable yet
  }
}

class BGMService {
  constructor() {
    this.audioInstances = {
      auth: null,
      world: null,
      adventure: null,
      battlefield: null
    }
    this.currentRoute = '/'
    this.currentModule = null
    this.isBattlefieldActive = false
    this.musicEnabled = true
    this.initialized = false
    this.interactionListenersAttached = false
    this.masterVolume = 1.0
  }

  getModule(p) {
    if (!p || p === '/') return 'landing'
    if (p === '/login' || p === '/register') return 'auth'
    if (p === '/world' || p === '/dashboard') return 'world'
    if (p.includes('/part/') || p.includes('/boss') || p.startsWith('/battle/')) return 'battlefield'
    if (p === '/adventure' || p.startsWith('/adventure/')) return 'adventure'
    if (p.startsWith('/quests')) return 'quests'
    if (p.startsWith('/study')) return 'study'
    if (p.startsWith('/practice')) return 'practice'
    if (p.startsWith('/multiplayer') || p.startsWith('/arena') || p.startsWith('/matchmaking')) return 'multiplayer'
    if (p.startsWith('/shop') || p.startsWith('/market')) return 'shop'
    if (p.startsWith('/leaderboard') || p.startsWith('/records')) return 'leaderboard'
    if (p.startsWith('/rewards')) return 'rewards'
    if (p.startsWith('/chronicle')) return 'chronicle'
    if (p.startsWith('/achievements')) return 'achievements'
    if (p.startsWith('/profile') || p.startsWith('/character')) return 'profile'
    if (p.startsWith('/settings')) return 'settings'
    if (p.startsWith('/special-event/exam')) return 'special-event-exam'
    if (p.startsWith('/special-event')) return 'special-event'
    return p.split('/')[1] || 'world'
  }

  init(initialEnabled = true, initialRoute = '/') {
    this.currentRoute = initialRoute || '/'
    this.currentModule = this.getModule(this.currentRoute)
    this.musicEnabled = Boolean(initialEnabled)

    if (this.initialized) {
      this.syncPlayback({ resetToBeginning: false })
      return
    }
    this.initialized = true

    try {
      for (const [key, cfg] of Object.entries(TRACK_CONFIGS)) {
        const audio = new Audio(cfg.src)
        audio.loop = true
        audio.volume = cfg.volume * this.masterVolume
        this.audioInstances[key] = audio
      }
    } catch (err) {
      console.warn('[BGM] Audio init warning:', err)
    }

    this.attachInteractionListeners()
    this.syncPlayback({ resetToBeginning: false })
  }

  attachInteractionListeners() {
    if (this.interactionListenersAttached || typeof window === 'undefined') return
    this.interactionListenersAttached = true

    const onUserInteract = () => {
      this.syncPlayback({ resetToBeginning: false })
      this.detachInteractionListeners(onUserInteract)
    }

    this._onUserInteract = onUserInteract
    window.addEventListener('click', onUserInteract, { passive: true })
    window.addEventListener('keydown', onUserInteract, { passive: true })
    window.addEventListener('touchstart', onUserInteract, { passive: true })
  }

  detachInteractionListeners(handler) {
    const fn = handler || this._onUserInteract
    if (!fn || typeof window === 'undefined') return
    window.removeEventListener('click', fn)
    window.removeEventListener('keydown', fn)
    window.removeEventListener('touchstart', fn)
    this.interactionListenersAttached = false
  }

  getActiveTrackType() {
    // 1. Combat Battlefield has top priority when active
    if (this.isBattlefieldActive) {
      return 'battlefield'
    }

    const p = this.currentRoute || '/'

    // 2. Landing Page & Authentication
    if (p === '/' || p === '/login' || p === '/register') {
      return 'auth'
    }

    // 3. Active Multiplayer Battle route fallback
    if (p.startsWith('/battle/')) {
      return 'battlefield'
    }

    // 4. Adventure Realm & Chapter Stage Map exploration
    if (p === '/adventure' || p.startsWith('/adventure/')) {
      return 'adventure'
    }

    // 5. General Game World (World Hub, Quests, Shop, Profile, Study Hall, Leaderboards, etc.)
    return 'world'
  }

  syncPlayback({ resetToBeginning = false } = {}) {
    if (!this.musicEnabled) {
      this.pauseAll()
      return
    }

    const targetKey = this.getActiveTrackType()

    // Pause all other zone tracks and reset them
    for (const [key, audio] of Object.entries(this.audioInstances)) {
      if (key !== targetKey && audio) {
        if (!audio.paused) {
          audio.pause()
        }
        safeResetTime(audio)
      }
    }

    // Play target zone track
    if (targetKey && this.audioInstances[targetKey]) {
      const targetAudio = this.audioInstances[targetKey]
      if (resetToBeginning) {
        safeResetTime(targetAudio)
      }
      if (targetAudio.paused || resetToBeginning) {
        const promise = targetAudio.play()
        if (promise !== undefined) {
          promise.catch(() => {
            // Autoplay blocked by browser policy until user interacts
          })
        }
      }
    }
  }

  pauseAll() {
    for (const audio of Object.values(this.audioInstances)) {
      if (audio && !audio.paused) {
        audio.pause()
      }
      safeResetTime(audio)
    }
  }

  setMusicEnabled(enabled) {
    this.musicEnabled = Boolean(enabled)
    this.syncPlayback({ resetToBeginning: false })
  }

  setBattlefieldActive(isActive) {
    const wasActive = this.isBattlefieldActive
    this.isBattlefieldActive = Boolean(isActive)
    const stateChanged = wasActive !== this.isBattlefieldActive
    this.syncPlayback({ resetToBeginning: stateChanged })
  }

  updateRoute(path) {
    const oldModule = this.currentModule
    const newModule = this.getModule(path)
    this.currentRoute = path || '/'
    this.currentModule = newModule

    if (path?.startsWith('/battle/')) {
      this.isBattlefieldActive = true
    } else if (!path?.includes('/part/') && !path?.includes('/boss')) {
      this.isBattlefieldActive = false
    }

    const moduleChanged = oldModule !== null && oldModule !== newModule
    this.syncPlayback({ resetToBeginning: moduleChanged })
  }

  setMasterVolume(vol) {
    this.masterVolume = Math.max(0, Math.min(1, vol))
    for (const [key, audio] of Object.entries(this.audioInstances)) {
      if (audio) {
        const baseVol = TRACK_CONFIGS[key]?.volume || 0.45
        audio.volume = baseVol * this.masterVolume
      }
    }
  }
}

export const bgmService = new BGMService()
export default bgmService
