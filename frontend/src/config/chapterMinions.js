/**
 * StudyQuest — Centralized Chapter Minion Configuration
 * 
 * Maps each of the 12 Adventure Chapters to exactly ONE unique regional minion asset.
 * Enforces:
 * 1. Parts 1–9 of each Chapter use that Chapter's assigned minion.
 * 2. Part 10 is ALWAYS the Chapter Boss (never a minion).
 * 3. The Creator (Final Boss) is NEVER a minion.
 * 4. The assignment applies to ALL users and ALL character classes universally.
 * 5. Pixel-art rendering parameters (sharp edges, aspect ratio, custom scale/offset).
 * 6. Non-stigmatizing, environmental fantasy lore (creatures of each realm, NOT monsters representing disorders).
 */

import { resolveAsset } from '../services/bossMapping'

export const CHAPTER_MINIONS = {
  // Chapter 1: Neurodevelopmental Disorders — Theme: Growth, Development, Gradual Progression
  1: {
    chapterId: 1,
    name: 'Plant Minion',
    fileName: 'plantMinion.png',
    title: 'Verdant Sprout of Development',
    iconEmoji: '🌱',
    theme: 'growth, development, progression, gradual change',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Keeper of Growth',
    bossAsset: 'CLOCK',
    lore: 'A nascent botanical sprite thriving among the unfolding clockwork pathways of the Cradle of Thought.'
  },

  // Chapter 2: Schizophrenia Spectrum & Other Psychotic Disorders — Theme: Uncertainty, Perception, Illusion
  2: {
    chapterId: 2,
    name: 'Ghost Minion',
    fileName: 'ghostMinion.png',
    title: 'Mirrored Phantasm of Perception',
    iconEmoji: '👻',
    theme: 'uncertainty, perception, illusion, ambiguity',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Veiled Witness',
    bossAsset: 'EYE',
    lore: 'An ethereal phantasm drifting through the fog-shrouded mirrors of the City Behind the Veil.'
  },

  // Chapter 3: Bipolar and Related Disorders — Theme: Flux, Instability, Changing States
  3: {
    chapterId: 3,
    name: 'Slime Minion',
    fileName: 'SlimeMinion.png',
    title: 'Shifting Ooze of Flux',
    iconEmoji: '🧪',
    theme: 'flux, instability, changing states, contrasting conditions',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Mind in Flux',
    bossAsset: 'BRAIN',
    lore: 'A gelatinous creature of fluctuating viscosity native to the oscillates between extremes in the Kingdom of Two Suns.'
  },

  // Chapter 4: Depressive Disorders — Theme: Emptiness, Absence, Quietness, Fading Presence
  4: {
    chapterId: 4,
    name: 'Spirit Minion',
    fileName: 'spiritMinion.png',
    title: 'Quiet Wisp of the Hollow',
    iconEmoji: '✨',
    theme: 'emptiness, absence, quietness, fading presence, emotional weight',
    scale: 1.05,
    width: 125,
    height: 140,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Hollow Heart',
    bossAsset: 'HEART ORGAN',
    lore: 'A quiet, drifting luminescent wisp wandering the solemn halls of the Hollow Sanctum.'
  },

  // Chapter 5: Anxiety Disorders — Theme: Webs, Entrapment, Anticipation, Interconnected Worries
  5: {
    chapterId: 5,
    name: 'Spider Minion',
    fileName: 'spiderMinion.png',
    title: 'Webweaver of Entangled Worry',
    iconEmoji: '🕷️',
    theme: 'webs, entrapment, interconnected worries, anticipation',
    scale: 1.05,
    width: 145,
    height: 125,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Archive of Worry',
    bossAsset: 'BOOK',
    lore: 'A cautious arachnid spinner weaving complex silk threads across the Archive of Worry.'
  },

  // Chapter 6: Obsessive-Compulsive and Related Disorders — Theme: Repetition, Loops, Cycles, Rituals
  6: {
    chapterId: 6,
    name: 'Circle Minion',
    fileName: 'circleMinion.png',
    title: 'Cyclic Ward of Repetition',
    iconEmoji: '🔮',
    theme: 'repetition, loops, rituals, cycles',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Unending Ritual',
    bossAsset: 'MAGIC HAT',
    lore: 'A circular harmonic anomaly revolving in an unbroken geometric sequence within the Spire of Infinite Circles.'
  },

  // Chapter 7: Trauma- / Stressor-Related & Dissociative Disorders — Theme: Remnants, Fragmented Memories
  7: {
    chapterId: 7,
    name: 'Skeleton Minion',
    fileName: 'skeletonMinion.png',
    title: 'Fragmented Remnant of the Shattered',
    iconEmoji: '💀',
    theme: 'remnants, fragmented memories, absence, loss',
    scale: 1.05,
    width: 125,
    height: 140,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Shattered Shadow',
    bossAsset: 'SHADOWS',
    lore: 'An ancient relic sentinel reconstructed from fragmented memory shards in the Shattered Realm.'
  },

  // Chapter 8: Somatic Symptom and Related Disorders — Theme: Bodily Movement, Sensation, Tension
  8: {
    chapterId: 8,
    name: 'Snake Minion',
    fileName: 'snakeMinion.png',
    title: 'Serpentine Pulse of Sensation',
    iconEmoji: '🐍',
    theme: 'bodily movement, sensation, tension, physical awareness',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Body That Speaks',
    bossAsset: 'SKULL FACE',
    lore: 'A coiled serpentine guardian sensitive to every kinetic vibration along the Chamber of Physical Resonance.'
  },

  // Chapter 9: Feeding, Eating, and Elimination Disorders — Theme: Food, Physical Body, Environmental Tone
  9: {
    chapterId: 9,
    name: 'Pumpkin Minion',
    fileName: 'pumpkinMinion.png',
    title: 'Harvester Sprite of the Forbidden',
    iconEmoji: '🎃',
    theme: 'food, physical body themes, environmental tone',
    scale: 0.95,
    width: 140,
    height: 140,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Forbidden Tome',
    bossAsset: 'BOOK',
    lore: 'An autumn gourd sprite rooted in the forbidden soil surrounding the Vault of Ancient Sustenance.'
  },

  // Chapter 10: Sleep-Wake, Sexual Dysfunctions, Gender Dysphoria — Theme: Nighttime, Sleep, Dreams
  10: {
    chapterId: 10,
    name: 'Bat Minion',
    fileName: 'batMinion.png',
    title: 'Nocturnal Wing of the Dreamer',
    iconEmoji: '🦇',
    theme: 'nighttime, sleep, dreams, hidden/internal worlds',
    scale: 1.0,
    width: 140,
    height: 130,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Dreamer',
    bossAsset: 'SINGLE WING',
    lore: 'A silent nocturnal flyer gliding between dream spires in the Realm of the Sleeping Singularity.'
  },

  // Chapter 11: Substance-Related, Addictive, and Disruptive Disorders — Theme: Foreign Influence, Loss of Control
  11: {
    chapterId: 11,
    name: 'Dark Alien Minion',
    fileName: 'darkalienMinion.png',
    title: 'Void Entity of the Hungering Mind',
    iconEmoji: '👾',
    theme: 'foreign influence, loss of control, disconnected behavior',
    scale: 1.0,
    width: 135,
    height: 135,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Hungering Mind',
    bossAsset: 'PURPLE BRAIN',
    lore: 'A strange otherworldly entity born from foreign void interference in the Caverns of Compulsion.'
  },

  // Chapter 12: Neurocognitive, Personality, and Paraphilic Disorders — Theme: Outward Persona, Identity
  12: {
    chapterId: 12,
    name: 'Bear Minion',
    fileName: 'bearMinion.png',
    title: 'Masked Ursa of the Many Selves',
    iconEmoji: '🐻',
    theme: 'outward persona, recognizable presence, identity/performance',
    scale: 1.0,
    width: 140,
    height: 140,
    offsetY: 0,
    offsetX: 0,
    bossName: 'The Mask of a Thousand Selves',
    bossAsset: 'MASK',
    lore: 'A stalwart masked ursine sentry standing watch before the Citadel of the Thousand Personas.'
  }
}

/**
 * Normalizes a chapter identifier to a valid integer between 1 and 12.
 * @param {number|string} chapterId 
 * @returns {number}
 */
export function normalizeChapterId(chapterId) {
  const num = parseInt(chapterId, 10)
  if (isNaN(num) || num < 1) return 1
  if (num > 12) return 12
  return num
}

/**
 * Returns the centralized minion configuration for the specified Chapter (1–12).
 * Universal across all users and all character classes.
 * @param {number|string} chapterId 
 * @returns {object}
 */
export function getChapterMinion(chapterId) {
  const normId = normalizeChapterId(chapterId)
  return CHAPTER_MINIONS[normId] || CHAPTER_MINIONS[1]
}

/**
 * Resolves the absolute / environment-aware URL for the designated minion image asset.
 * @param {number|string} chapterId 
 * @returns {string}
 */
export function getMinionAssetUrl(chapterId) {
  const minion = getChapterMinion(chapterId)
  if (!minion?.fileName) return ''
  return resolveAsset(`assets/MINIONS/${minion.fileName}`)
}

/**
 * Verifies whether the encounter is a Chapter Boss or Final Boss (which must NEVER use a minion).
 * @param {number|string} partNum 
 * @param {boolean} isBossFlag 
 * @param {boolean} isFinalBossFlag 
 * @returns {boolean}
 */
export function isBossEncounter(partNum, isBossFlag = false, isFinalBossFlag = false) {
  if (isFinalBossFlag) return true
  if (isBossFlag) return true
  const p = parseInt(partNum, 10)
  return p === 10 || partNum === 'boss'
}

/**
 * Preloads all 12 minion assets in the background for zero-latency battle starts.
 */
export function preloadMinionAssets() {
  if (typeof window === 'undefined') return
  Object.values(CHAPTER_MINIONS).forEach(m => {
    try {
      const img = new Image()
      img.src = resolveAsset(`assets/MINIONS/${m.fileName}`)
    } catch (e) {
      // Ignored non-critical preloading errors
    }
  })
}

export default CHAPTER_MINIONS
