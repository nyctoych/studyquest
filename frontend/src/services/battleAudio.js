/**
 * StudyQuest — Web Audio Synthesizer for Retro RPG Battle Sounds & Academic SFX
 * Provides zero-dependency, local synthesized SFX for attacks, hits, wrong answers,
 * UI clicks, library navigation, and market purchases.
 * Gracefully silences if Web Audio is unsupported or audio is disabled.
 */

let audioCtx = null
let sfxEnabled = true
let masterVolume = 0.8

function getAudioContext() {
  if (typeof window === 'undefined') return null
  if (!sfxEnabled || masterVolume <= 0) return null
  if (!audioCtx) {
    const AudioContext = window.AudioContext || window.webkitAudioContext
    if (AudioContext) {
      audioCtx = new AudioContext()
    }
  }
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume().catch(() => {})
  }
  return audioCtx
}

const baseAudio = {
  setSfxEnabled(val) {
    sfxEnabled = Boolean(val)
  },
  setVolume(vol) {
    masterVolume = Math.max(0, Math.min(1, Number(vol) || 0))
  },
  getSfxEnabled() {
    return sfxEnabled
  },
  getVolume() {
    return masterVolume
  },

  /**
   * Sound when selecting an answer action / UI click
   */
  playSelect() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'square'
      osc.frequency.setValueAtTime(440, ctx.currentTime)
      osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08)
      gain.gain.setValueAtTime(0.06 * masterVolume, ctx.currentTime)
      gain.gain.linearRampToValueAtTime(0, ctx.currentTime + 0.08)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start()
      osc.stop(ctx.currentTime + 0.08)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when player attacks on correct answer (Sword slash / runic spell)
   */
  playAttack() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sawtooth'
      osc.frequency.setValueAtTime(800, now)
      osc.frequency.exponentialRampToValueAtTime(150, now + 0.18)
      gain.gain.setValueAtTime(0.12 * masterVolume, now)
      gain.gain.linearRampToValueAtTime(0, now + 0.18)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.18)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when enemy is hit by correct answer (Impact thud + sparkle)
   */
  playHit() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc1 = ctx.createOscillator()
      const gain1 = ctx.createGain()
      osc1.type = 'triangle'
      osc1.frequency.setValueAtTime(220, now)
      osc1.frequency.exponentialRampToValueAtTime(40, now + 0.25)
      gain1.gain.setValueAtTime(0.2 * masterVolume, now)
      gain1.gain.linearRampToValueAtTime(0, now + 0.25)
      osc1.connect(gain1)
      gain1.connect(ctx.destination)
      osc1.start(now)
      osc1.stop(now + 0.25)

      const osc2 = ctx.createOscillator()
      const gain2 = ctx.createGain()
      osc2.type = 'sine'
      osc2.frequency.setValueAtTime(587.33, now + 0.08) // D5
      osc2.frequency.setValueAtTime(880, now + 0.16)    // A5
      gain2.gain.setValueAtTime(0.08 * masterVolume, now + 0.08)
      gain2.gain.linearRampToValueAtTime(0, now + 0.35)
      osc2.connect(gain2)
      gain2.connect(ctx.destination)
      osc2.start(now + 0.08)
      osc2.stop(now + 0.35)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when enemy mocks / laughs on wrong answer (Mocking discordant staccato)
   */
  playMock() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const notes = [311.13, 277.18, 233.08] // Eb4, C#4, Bb3
      notes.forEach((freq, idx) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.type = 'square'
        osc.frequency.setValueAtTime(freq, now + idx * 0.1)
        gain.gain.setValueAtTime(0.08 * masterVolume, now + idx * 0.1)
        gain.gain.linearRampToValueAtTime(0, now + (idx + 1) * 0.1)
        osc.connect(gain)
        gain.connect(ctx.destination)
        osc.start(now + idx * 0.1)
        osc.stop(now + (idx + 1) * 0.1)
      })
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound on trial victory
   */
  playVictory() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const chord = [523.25, 659.25, 783.99, 1046.50] // C Major
      chord.forEach((freq, i) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.type = 'triangle'
        osc.frequency.setValueAtTime(freq, now + i * 0.08)
        gain.gain.setValueAtTime(0.1 * masterVolume, now + i * 0.08)
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.8)
        osc.connect(gain)
        gain.connect(ctx.destination)
        osc.start(now + i * 0.08)
        osc.stop(now + 0.8)
      })
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when boss enters 50% HP Enraged Phase
   */
  playEnraged() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sawtooth'
      osc.frequency.setValueAtTime(140, now)
      osc.frequency.exponentialRampToValueAtTime(45, now + 0.6)
      gain.gain.setValueAtTime(0.18 * masterVolume, now)
      gain.gain.linearRampToValueAtTime(0, now + 0.6)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.6)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when entering the Study Hall Library
   */
  playLibraryEnter() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const chord = [261.63, 329.63, 392.00, 523.25] // C Major warm chord
      chord.forEach((freq, i) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.type = 'sine'
        osc.frequency.setValueAtTime(freq, now)
        osc.frequency.exponentialRampToValueAtTime(freq * 1.5, now + 0.4)
        gain.gain.setValueAtTime(0.08 * masterVolume, now)
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5)
        osc.connect(gain)
        gain.connect(ctx.destination)
        osc.start(now + i * 0.04)
        osc.stop(now + 0.5)
      })
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when opening a grimoire/volume book
   */
  playBookOpen() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'triangle'
      osc.frequency.setValueAtTime(320, now)
      osc.frequency.exponentialRampToValueAtTime(540, now + 0.15)
      gain.gain.setValueAtTime(0.07 * masterVolume, now)
      gain.gain.linearRampToValueAtTime(0, now + 0.15)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.15)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound when turning a page in the Study Hall reading room
   */
  playPageTurn() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(480, now)
      osc.frequency.exponentialRampToValueAtTime(260, now + 0.1)
      gain.gain.setValueAtTime(0.05 * masterVolume, now)
      gain.gain.linearRampToValueAtTime(0, now + 0.1)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.1)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound for collecting or spending coins
   */
  playCoin() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(987.77, now) // B5
      osc.frequency.setValueAtTime(1318.51, now + 0.08) // E6
      gain.gain.setValueAtTime(0.12 * masterVolume, now)
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.3)
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound on completing a purchase
   */
  playPurchase() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const freqs = [659.25, 783.99, 1046.50]
      freqs.forEach((freq, idx) => {
        const osc = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.type = 'triangle'
        osc.frequency.setValueAtTime(freq, now + idx * 0.07)
        gain.gain.setValueAtTime(0.09 * masterVolume, now + idx * 0.07)
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35)
        osc.connect(gain)
        gain.connect(ctx.destination)
        osc.start(now + idx * 0.07)
        osc.stop(now + 0.35)
      })
    } catch {
      // Suppressed
    }
  },

  /**
   * Sound on equipping cosmetic item
   */
  playEquip() {
    try {
      const ctx = getAudioContext()
      if (!ctx) return
      const now = ctx.currentTime
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sawtooth'
      osc.frequency.setValueAtTime(440, now)
      osc.frequency.exponentialRampToValueAtTime(880, now + 0.12)
      gain.gain.setValueAtTime(0.08 * masterVolume, now)
      gain.gain.linearRampToValueAtTime(0, now + 0.12)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(now)
      osc.stop(now + 0.12)
    } catch {
      // Suppressed
    }
  }
}

/**
 * Proxy wrapper: prevents ANY unknown audio method call from throwing a runtime TypeError
 */
export const battleAudio = new Proxy(baseAudio, {
  get(target, prop) {
    if (prop in target) {
      return target[prop]
    }
    // Return a safe no-op function for any unmapped sound method
    return () => {}
  }
})

export default battleAudio
