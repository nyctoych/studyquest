/**
 * StudyQuest — Centralized Character Animation Configuration
 * Authoritative asset paths, durations, impact timings, and scaling
 * for Mage (Necromancer), Melee (Dual-Wielder), and Range (Archer).
 */

export const CHARACTER_ANIMATIONS = {
  mage: {
    id: 1,
    key: 'mage',
    name: 'Necromancer',
    classType: 'MAGE',
    // Assets from /assets/CHARACTERS/NECROMANCER (MAGE)/
    sprite: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer.png',
    fallbackSprite: '/assets/CHARACTERS/NECROMANCER (MAGE)/necromance.png',
    idle: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif',
    opening: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer OPENING.gif',
    attack: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Attack.gif',
    attackStatic: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Attack.png',
    death: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Death.gif',
    victory: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer VICTORY.gif',
    // Result screen animations & poses
    resultVictory: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer VICTORY.gif',
    resultDeath: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Death.gif',
    resultDeathStatic: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Death.png',
    // Timings (ms) - exact length of full GIF animations
    openingDuration: 9800, // Exact 27 frames (9800ms) plays completely from start to end
    attackDuration: 8700,  // Exact 21 frames (8700ms) plays completely from start to end
    impactTiming: 2200,    // Staff thrust / spell release window during casting motion
    deathDuration: 4400,   // Exact 18 frames (4400ms) plays completely before static death.png
    victoryDuration: 2700, // Exact 12 frames (2700ms) continuous loop
    // Visual alignments
    scale: 1.05,
    offsetY: 0,
    resultScale: 1.25,
    resultOffsetY: 0,
    baseHealth: 150,
    damage: 10,
    critDamage: 35
  },

  melee: {
    id: 2,
    key: 'melee',
    name: 'Dual-Wielder',
    classType: 'MELEE',
    // Assets from /assets/CHARACTERS/DUAL-WIELDER (MELEE)/
    sprite: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png',
    fallbackSprite: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png',
    idle: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee IDLE.gif',
    idleStatic: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee IDLE.png',
    opening: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee OPENING.gif',
    attack: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee Attack.gif',
    attackStatic: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png',
    hit: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee HIT.gif',
    death: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee DEATH.gif',
    deathStatic: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee DEATH.png',
    victory: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee VICTORY .gif',
    // Result screen animations & poses
    resultVictory: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee VICTORY .gif',
    resultDeath: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee DEATH.gif',
    resultDeathStatic: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee DEATH.png',
    // Timings (ms) - exact length of full GIF animations
    openingDuration: 6800, // Exact 39 frames (6800ms) plays completely from start to end
    attackDuration: 1900,  // Exact 14 frames (1900ms) plays completely from start to end
    impactTiming: 1200,    // Slash impact occurs during rapid dual-blade frames (1050-1300ms)
    hitDuration: 600,      // Exact 3 frames (600ms) hit reaction
    deathDuration: 3800,   // Exact 9 frames (3800ms) plays completely before static Melee DEATH.png
    victoryDuration: 4800, // Exact 12 frames (4800ms) continuous loop
    // Visual alignments
    scale: 1.08,
    offsetY: 2,
    resultScale: 1.25,
    resultOffsetY: 2,
    baseHealth: 200,
    damage: 10,
    critDamage: 20
  },

  range: {
    id: 3,
    key: 'range',
    name: 'Archer',
    classType: 'RANGE',
    // Assets from /assets/CHARACTERS/ARCHER (RANGE)/
    sprite: '/assets/CHARACTERS/ARCHER (RANGE)/Archer.png',
    fallbackSprite: '/assets/CHARACTERS/ARCHER (RANGE)/Archer.png',
    idle: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif',
    opening: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr OPENING.gif',
    // Requirement 4 & 5: Archerr ATTACKING.gif and Archer Attack1.png
    attack: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr ATTACKING.gif',
    attackStatic: '/assets/CHARACTERS/ARCHER (RANGE)/Archer Attack1.png',
    death: '/assets/CHARACTERS/ARCHER (RANGE)/Archer death.gif',
    victory: '/assets/CHARACTERS/ARCHER (RANGE)/Archer VICTORY.gif',
    // Result screen animations & poses
    resultVictory: '/assets/CHARACTERS/ARCHER (RANGE)/Archer VICTORY.gif',
    resultDeath: '/assets/CHARACTERS/ARCHER (RANGE)/Archer death.gif',
    resultDeathStatic: '/assets/CHARACTERS/ARCHER (RANGE)/Archer death.png',
    // Timings (ms)
    openingDuration: 11200,
    attackDuration: 1600, // Exact sum of 5 frames in Archer Attacking.gif (1600ms)
    impactTiming: 1600,   // Damage reflects only after the arrow release animation finishes
    deathDuration: 2800,  // Exact 10 frames (2800ms) plays completely before static death.png
    victoryDuration: 7800,// Exact 26 frames (7800ms) continuous loop
    // Visual alignments
    scale: 1.02,
    offsetY: 0,
    resultScale: 1.25,
    resultOffsetY: 0,
    baseHealth: 110,
    damage: 15,
    critDamage: 50
  }
}

/**
 * Resolves animation config by class string ('mage', 'range', 'melee') or character object
 */
export function getCharacterAnimationConfig(character) {
  if (!character) return CHARACTER_ANIMATIONS.mage

  let code = 'mage'
  if (typeof character === 'string') {
    code = character.toLowerCase()
  } else if (character.code) {
    code = String(character.code).toLowerCase()
  } else if (character.class_type) {
    code = String(character.class_type).toLowerCase()
  } else if (character.key_name) {
    code = String(character.key_name).toLowerCase()
  } else if (character.name) {
    const n = character.name.toLowerCase()
    if (n.includes('archer') || n.includes('range')) code = 'range'
    else if (n.includes('wielder') || n.includes('melee')) code = 'melee'
    else code = 'mage'
  }

  return CHARACTER_ANIMATIONS[code] || CHARACTER_ANIMATIONS.mage
}

/**
 * Preload an image asset
 */
export function preloadImageAsset(url) {
  return new Promise((resolve) => {
    if (!url) return resolve(false)
    const img = new Image()
    img.onload = () => resolve(true)
    img.onerror = () => {
      console.warn(`[Preload] Could not load image: ${url}`)
      resolve(false)
    }
    img.src = url
  })
}

/**
 * Preloads all battle animation assets for a specific character
 */
export async function preloadCharacterAnimations(character) {
  const config = getCharacterAnimationConfig(character)
  if (!config) return

  const assets = [
    config.opening,
    config.idle,
    config.attack,
    config.attackStatic,
    config.resultVictory,
    config.resultDeath,
    config.resultDeathStatic,
    config.sprite
  ].filter(Boolean)

  await Promise.all(assets.map(preloadImageAsset))
}

export default CHARACTER_ANIMATIONS
