/**
 * StudyQuest — The Creator (Final Boss) Centralized Animation Configuration
 * Authoritative asset paths, exact GIF durations, frame counts, and state resolution.
 */

import { resolveAsset } from '../services/bossMapping'

export const CREATOR_ANIMATION_CONFIG = {
  // Phase 1 (HP > 50% of Max HP)
  phase1: {
    intro: {
      key: 'intro',
      filename: 'The Creator - INTRO.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - INTRO.gif',
      duration: 3700, // 28 frames
      frames: 28,
      loop: false
    },
    idle: {
      key: 'phase1_idle',
      filename: 'The Creator - BATTLE STATE IDLE.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - BATTLE STATE IDLE.gif',
      duration: 800, // 2 frames
      frames: 2,
      loop: true
    },
    attack: {
      key: 'phase1_attack',
      filename: 'The Creator - Attack animation.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - Attack animation.gif',
      duration: 6200, // 14 frames
      frames: 14,
      loop: false
    },
    hit: {
      key: 'phase1_hit',
      filename: 'The Creator - HIT STATE.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - HIT STATE.gif',
      duration: 1400, // 2 frames
      frames: 2,
      loop: false
    }
  },

  // Phase 2 (HP <= 50% of Max HP)
  phase2: {
    transform: {
      key: 'transform',
      filename: 'The Creator - 2nd Phase Transformation.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2nd Phase Transformation.gif',
      duration: 5100, // 27 frames
      frames: 27,
      loop: false
    },
    idle: {
      key: 'phase2_idle',
      filename: 'The Creator - 2ND PHASE IDLE.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2ND PHASE IDLE.gif',
      duration: 2000, // 2 frames
      frames: 2,
      loop: true
    },
    attack: {
      key: 'phase2_attack',
      filename: 'The Creator - 2ND PHASE ATTACK.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2ND PHASE ATTACK.gif',
      duration: 4400, // 14 frames
      frames: 14,
      loop: false
    },
    hit: {
      key: 'phase2_hit',
      filename: 'The Creator - 2ND PHASE HIT STATE.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2ND PHASE HIT STATE.gif',
      duration: 1500, // 2 frames
      frames: 2,
      loop: false
    }
  },

  // Defeat Sequence (HP <= 0)
  defeat: {
    dialogue: {
      key: 'defeat_dialogue',
      filename: 'The Creator - 2ND PHASE DEFEATED DIALOGUE STATE.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2ND PHASE DEFEATED DIALOGUE STATE.gif',
      duration: 2600, // 8 frames
      frames: 8,
      loop: false
    },
    closing: {
      key: 'defeat_closing',
      filename: 'The Creator - 2ND PHASE cLOSING.gif',
      path: '/assets/boss/WRITER - FINAL BOSS/The Creator - 2ND PHASE cLOSING.gif',
      duration: 3300, // 15 frames
      frames: 15,
      loop: false
    }
  }
}

/**
 * Preload all Creator animations so transitions happen with zero latency or blank frames
 */
export async function preloadCreatorAnimations() {
  const assets = [
    CREATOR_ANIMATION_CONFIG.phase1.intro.path,
    CREATOR_ANIMATION_CONFIG.phase1.idle.path,
    CREATOR_ANIMATION_CONFIG.phase1.attack.path,
    CREATOR_ANIMATION_CONFIG.phase1.hit.path,
    CREATOR_ANIMATION_CONFIG.phase2.transform.path,
    CREATOR_ANIMATION_CONFIG.phase2.idle.path,
    CREATOR_ANIMATION_CONFIG.phase2.attack.path,
    CREATOR_ANIMATION_CONFIG.phase2.hit.path,
    CREATOR_ANIMATION_CONFIG.defeat.dialogue.path,
    CREATOR_ANIMATION_CONFIG.defeat.closing.path
  ]

  const promises = assets.map(url => {
    return new Promise(resolve => {
      const resolved = resolveAsset(url)
      if (!resolved) return resolve(false)
      const img = new Image()
      img.onload = () => resolve(true)
      img.onerror = () => {
        console.warn(`[CreatorPreload] Failed to load ${resolved}`)
        resolve(false)
      }
      img.src = resolved
    })
  })

  await Promise.all(promises)
}

export default CREATOR_ANIMATION_CONFIG
