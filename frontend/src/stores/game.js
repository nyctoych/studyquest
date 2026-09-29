import { defineStore } from 'pinia'
import { ref } from 'vue'
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

  return {
    loadingComplete, loadingProgress, audioEnabled, musicEnabled, masterVolume, worldReady,
    setLoadingComplete, setAudio, setMusic, setMasterVolume, setLoadingProgress,
  }
})
