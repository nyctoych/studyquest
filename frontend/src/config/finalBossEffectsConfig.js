/**
 * StudyQuest — Final Boss (The Creator) Attack Effects & Splash Effects Configuration
 * 
 * Strict requirements:
 * 1. Boss-exclusive assets: Never purchasable, equippable, or selectable in Market.
 * 2. Phase 1 (HP > 50%): Uses Phase 1 Attack Effect and Splash Effect.
 * 3. Phase 2 (HP <= 50%): Randomly selects exactly ONE of three patterns:
 *    - Standard:   Attack Effect + 2nd Phase Splash Effect
 *    - Meteor:     Meteor Attack Effect + Meteor Splash Effect
 *    - Earthquake: Earthquake Attack Effect + Earthquake Splash Effect
 * 4. One wrong answer = exactly one attack pattern (attack effect first, matching splash effect second).
 * 5. Player equipped effects continue working normally against The Creator when player attacks.
 */

import { resolveAsset } from '../services/bossMapping.js'

export const FINAL_BOSS_EFFECTS_CONFIG = {
  // Phase 1 (The Creator above 50% HP)
  phase1: {
    key: 'phase1',
    name: 'Genesis Wordcraft',
    attack: {
      name: '1st Phase Attack Effect',
      filename: 'FINAL BOSS 1ST PHASE V2 ATTACK EFFECT.gif',
      path: 'assets/ATTACK EFFECTS/FINAL BOSS (TATLO)/1ST PHASE/FINAL BOSS 1ST PHASE V2 ATTACK EFFECT.gif',
      duration: 4200,
      frames: 34
    },
    splash: {
      name: '1st Phase Splash Effect',
      filename: 'FINAL BOSS 1ST PHASE SPLASH EFFECT.gif',
      path: 'assets/SPLASH EFFECTS/FINAL BOSS (TATLO)/1ST PHASE/FINAL BOSS 1ST PHASE SPLASH EFFECT.gif',
      duration: 3200,
      frames: 14
    }
  },

  // Phase 2 (The Creator at or below 50% HP) — 3 Randomized Attack Patterns
  phase2Patterns: {
    standard: {
      key: 'standard',
      name: 'Script Unraveling (Standard)',
      attack: {
        name: 'Final Boss Attack Effect',
        filename: 'FINAL BOSS ATTACK EFFECT.gif',
        path: 'assets/ATTACK EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS ATTACK EFFECT.gif',
        duration: 2700,
        frames: 16
      },
      splash: {
        name: '2nd Phase Splash Effect',
        filename: 'FINAL BOSS 2ND PHASE SPLASH EFFECT.gif',
        path: 'assets/SPLASH EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS 2ND PHASE SPLASH EFFECT.gif',
        duration: 3800,
        frames: 32
      }
    },

    meteor: {
      key: 'meteor',
      name: 'Astral Script Collapse (Meteor)',
      attack: {
        name: 'Meteor Attack Effect',
        filename: 'FINAL BOSS - METEOR 2ND PHASE ATTACK EFFECT.gif',
        path: 'assets/ATTACK EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS - METEOR 2ND PHASE ATTACK EFFECT.gif',
        duration: 1300,
        frames: 13
      },
      splash: {
        name: 'Meteor Splash Effect',
        filename: 'FINAL BOSS - METEOR 2ND PHASE SPLASH EFFECT.gif',
        path: 'assets/SPLASH EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS - METEOR 2ND PHASE SPLASH EFFECT.gif',
        duration: 3900,
        frames: 35
      }
    },

    earthquake: {
      key: 'earthquake',
      name: 'Manuscript Shatter (Earthquake)',
      attack: {
        name: 'Earthquake Attack Effect',
        filename: 'FINAL BOSS - EARTHQUAKE 2ND PHASE ATTACK EFFECT.gif',
        path: 'assets/ATTACK EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS - EARTHQUAKE 2ND PHASE ATTACK EFFECT.gif',
        duration: 3200,
        frames: 23
      },
      splash: {
        name: 'Earthquake Splash Effect',
        filename: 'FINAL BOSS - EARTHQUAKE 2ND PHASE SPLASH EFFECT.gif',
        path: 'assets/SPLASH EFFECTS/FINAL BOSS (TATLO)/2ND PHASE/FINAL BOSS - EARTHQUAKE 2ND PHASE SPLASH EFFECT.gif',
        duration: 1100,
        frames: 11
      }
    }
  }
}

/**
 * Returns the designated Phase 1 attack pattern
 */
export function getFinalBossPhase1Pattern() {
  return FINAL_BOSS_EFFECTS_CONFIG.phase1
}

/**
 * Randomly selects exactly ONE of the three Phase 2 attack patterns
 * Keys: 'standard' | 'meteor' | 'earthquake'
 */
export function getRandomFinalBossPhase2Pattern() {
  const patterns = Object.values(FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns)
  const randomIndex = Math.floor(Math.random() * patterns.length)
  return patterns[randomIndex]
}

/**
 * Selects the authoritative attack pattern based on current battle phase
 * @param {number} phase - 1 or 2
 * @param {string|null} patternKey - Optional specific key for testing or deterministic replay
 */
export function resolveBossAttackPattern(phase, patternKey = null) {
  if (phase === 2) {
    if (patternKey && FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns[patternKey]) {
      return FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns[patternKey]
    }
    return getRandomFinalBossPhase2Pattern()
  }
  return getFinalBossPhase1Pattern()
}

/**
 * Preloads all Final Boss attack and splash effect assets
 */
export async function preloadFinalBossEffects() {
  const assetPaths = [
    FINAL_BOSS_EFFECTS_CONFIG.phase1.attack.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase1.splash.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.standard.attack.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.standard.splash.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.meteor.attack.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.meteor.splash.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.earthquake.attack.path,
    FINAL_BOSS_EFFECTS_CONFIG.phase2Patterns.earthquake.splash.path
  ]

  if (typeof window === 'undefined') return

  const promises = assetPaths.map(relPath => {
    return new Promise(resolve => {
      const img = new Image()
      img.onload = () => resolve({ path: relPath, success: true })
      img.onerror = () => resolve({ path: relPath, success: false })
      img.src = resolveAsset(relPath)
    })
  })

  return Promise.all(promises)
}

export default {
  FINAL_BOSS_EFFECTS_CONFIG,
  getFinalBossPhase1Pattern,
  getRandomFinalBossPhase2Pattern,
  resolveBossAttackPattern,
  preloadFinalBossEffects
}
