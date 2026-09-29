/**
 * StudyQuest — Web Audio Synthesizer for Retro RPG Battle Sounds
 * Provides zero-dependency, local synthesized SFX for attack, hit, wrong, and button clicks.
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

export const battleAudio = {
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
      // Audio error suppressed
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

      // Fast frequency sweep for slash
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
      // Audio error suppressed
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

      // Impact bass
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

      // Chime resonance for victory of knowledge
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
      // Audio error suppressed
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

      // Two descending mocking tones
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
      // Audio error suppressed
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
      // Audio error suppressed
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

      // Deep rumble roar
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
      // Audio error suppressed
    }
  }
}

export default battleAudio
