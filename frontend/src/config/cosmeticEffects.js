/**
 * cosmeticEffects.js
 * Centralized configuration and asset mapping for Attack Effects and Splash Effects.
 * 
 * Strict rules:
 * 1. Attack Effects travel from player to enemy.
 * 2. Splash Effects appear upon impact at the enemy location.
 * 3. Default attack effects are free and automatically available per character:
 *    - Mage / Necromancer -> Default Orb
 *    - Range / Archer -> Default Arrow
 *    - Melee / Dual-Wielder -> Default Melee
 * 4. Default splash effect is free and automatically available -> Default Splash.
 * 5. Visual cosmetics only - no stat/arithmetic alterations.
 */

import { resolveAsset } from '../services/bossMapping.js'

export const COSMETIC_EFFECTS = [
  // ════════════════════════════════════════════════════════════════
  // 1. ATTACK EFFECTS: MAGE (NECROMANCER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-orb',
    effect_key: 'default-orb',
    name: 'Default Arcane Orb',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.png',
    gif_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.gif',
    description: 'A focused sphere of twilight arcane essence, pulsing with dark wisdom.'
  },
  {
    id: 'elite-orb',
    effect_key: 'elite-orb',
    name: 'Elite Phantom Orb',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'rare',
    coin_cost: 3000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/elite orb.png',
    gif_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/elite orb.gif',
    description: 'A spectral orb surrounded by swirling soul vapors.'
  },
  {
    id: 'epic-orb',
    effect_key: 'epic-orb',
    name: 'Epic Void Orb',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'epic',
    coin_cost: 6000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/epic orb.png',
    gif_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/epic orb.gif',
    description: 'A dense nexus of abyssal gravity that distorts the battlefield.'
  },
  {
    id: 'mythical-orb',
    effect_key: 'mythical-orb',
    name: 'Mythic Nebula Orb',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'legendary',
    coin_cost: 12000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/mythical orb.png',
    gif_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/mythical orb.gif',
    description: 'An ancient cosmological singularity burning with radiant astral fury.'
  },

  // ════════════════════════════════════════════════════════════════
  // 2. ATTACK EFFECTS: RANGE (ARCHER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-arrow',
    effect_key: 'default-arrow',
    name: 'Default Swift Arrow',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.png',
    gif_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.gif',
    description: 'A sharp, aerodynamic fletched arrow loosed with deadly scholastic precision.'
  },
  {
    id: 'elite-arrow',
    effect_key: 'elite-arrow',
    name: 'Elite Falcon Arrow',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'rare',
    coin_cost: 3000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/elite arrow.png',
    gif_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/elite arrow.gif',
    description: 'Infused with swift wind currents that whistle across the chamber.'
  },
  {
    id: 'epic-arrow',
    effect_key: 'epic-arrow',
    name: 'Epic Glacial Arrow',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'epic',
    coin_cost: 6000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow epicc.png',
    gif_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow epicc.gif',
    description: 'Forged from perpetual frost, trailing crystallizing ice vapor.'
  },
  {
    id: 'myth-arrow',
    effect_key: 'myth-arrow',
    name: 'Mythic Solar Arrow',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'legendary',
    coin_cost: 12000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow myth.png',
    gif_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow myth.gif',
    description: 'A blinding shaft of golden luminescence that pierces all shadows.'
  },

  // ════════════════════════════════════════════════════════════════
  // 3. ATTACK EFFECTS: MELEE (DUAL-WIELDER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-melee',
    effect_key: 'default-melee',
    name: 'Default Crescent Cleave',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/default melee.png',
    gif_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/default melee.gif',
    description: 'A fierce crescent wave cut through the air by twin balanced blades.'
  },
  {
    id: 'elite-melee',
    effect_key: 'elite-melee',
    name: 'Elite Tempest Slash',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'rare',
    coin_cost: 3000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.png',
    gif_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.gif',
    description: 'A dual-cyclone blade strike that lacerates the space before the wielder.'
  },
  {
    id: 'epic-melee',
    effect_key: 'epic-melee',
    name: 'Epic Melee Slash',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'epic',
    coin_cost: 6000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/epic melee.png',
    gif_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/epic melee.gif',
    description: 'An epic tempestuous crescent strike cleaving space and shattering defenses.'
  },
  {
    id: 'mythical-melee',
    effect_key: 'mythical-melee',
    name: 'Mythical Melee Cleave',
    category_slug: 'attack_effect',
    type: 'attack_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'mythical',
    coin_cost: 12000,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/mythical melee.png',
    gif_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/mythical melee.gif',
    description: 'A legendary dual-blade vortex unleashing mythical crimson fury.'
  },

  // ════════════════════════════════════════════════════════════════
  // 4. SPLASH EFFECTS: RANGE (ARCHER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-arrow-splash',
    effect_key: 'default-arrow-splash',
    name: 'Default Arrow Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/ARCHER (RANGE)/default arrow SPLASHH.gif',
    description: 'A sharp, kinetic arrow impact dispersing crisp piercing energy.'
  },
  {
    id: 'elite-arrow-splash',
    effect_key: 'elite-arrow-splash',
    name: 'Elite Arrow Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'rare',
    coin_cost: 3500,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/ARCHER (RANGE)/elite arrow SPLASHH.gif',
    description: 'An elite piercing burst scattering concentrated emerald and azure force.'
  },
  {
    id: 'epic-arrow-splash',
    effect_key: 'epic-arrow-splash',
    name: 'Epic Arrow Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'epic',
    coin_cost: 7000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/ARCHER (RANGE)/epic arrow SPLASHH.gif',
    description: 'An explosive shockwave detonation that tears into target vitals.'
  },
  {
    id: 'mythical-arrow-splash',
    effect_key: 'mythical-arrow-splash',
    name: 'Mythical Arrow Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'range',
    character_name: 'Archer',
    rarity: 'mythical',
    coin_cost: 12000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/ARCHER (RANGE)/mythical arrow SPLASHH.gif',
    description: 'A supreme celestial arrow detonation burning with mythical solar energy.'
  },

  // ════════════════════════════════════════════════════════════════
  // 5. SPLASH EFFECTS: MELEE (DUAL-WIELDER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-melee-splash',
    effect_key: 'default-melee-splash',
    name: 'Default Melee Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/default melee SPLASH.gif',
    description: 'A direct blade impact cross-slash slicing through enemy guard.'
  },
  {
    id: 'elite-melee-splash',
    effect_key: 'elite-melee-splash',
    name: 'Elite Melee Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'rare',
    coin_cost: 3500,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/elite melee SPLASH.gif',
    description: 'A swift double-crescent blade flurry erupting upon point-blank impact.'
  },
  {
    id: 'epic-melee-splash',
    effect_key: 'epic-melee-splash',
    name: 'Epic Melee Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'epic',
    coin_cost: 7000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/epic melee SPLASHH.gif',
    description: 'A tempestuous multi-blade detonation that pulverizes surrounding matter.'
  },
  {
    id: 'mythical-melee-splash',
    effect_key: 'mythical-melee-splash',
    name: 'Mythical Melee Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'melee',
    character_name: 'Dual-Wielder',
    rarity: 'mythical',
    coin_cost: 12000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/DOUBLE WIELDER (MELEE)/mythical melee SPLASHH.gif',
    description: 'A godlike dual-blade cleave fracturing reality with legendary crimson fury.'
  },

  // ════════════════════════════════════════════════════════════════
  // 6. SPLASH EFFECTS: MAGE (NECROMANCER)
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-orb-splash',
    effect_key: 'default-orb-splash',
    name: 'Default Orb Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/default orb SPLASHH.gif',
    description: 'A shadowy orb explosion collapsing into an occult violet singularity.'
  },
  {
    id: 'elite-orb-splash',
    effect_key: 'elite-orb-splash',
    name: 'Elite Orb Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'rare',
    coin_cost: 3500,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/elite orb SPLASHH.gif',
    description: 'An intense occult pulse radiating spectral flames across the impact point.'
  },
  {
    id: 'epic-orb-splash',
    effect_key: 'epic-orb-splash',
    name: 'Epic Orb Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'epic',
    coin_cost: 7000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/epic orb SPLASHH.gif',
    description: 'A devastating void star supernova discharging destructive mystic shocks.'
  },
  {
    id: 'mythical-orb-splash',
    effect_key: 'mythical-orb-splash',
    name: 'Mythical Orb Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'mage',
    character_name: 'Necromancer',
    rarity: 'mythical',
    coin_cost: 12000,
    is_default: false,
    png_path: '',
    gif_path: 'assets/SPLASH EFFECTS/NECROMANCER (MAGE)/mythical orb SPLASHH.gif',
    description: 'An eldritch cosmological rupture exploding with transcendent soul power.'
  }
]

// ════════════════════════════════════════════════════════════════
// Centralized Character Default Mappings
// ════════════════════════════════════════════════════════════════
export const CHARACTER_DEFAULT_ATTACK_EFFECTS = {
  mage: 'default-orb',
  range: 'default-arrow',
  melee: 'default-melee'
}

export const CHARACTER_DEFAULT_SPLASH_EFFECTS = {
  mage: 'default-orb-splash',
  range: 'default-arrow-splash',
  melee: 'default-melee-splash'
}

export const DEFAULT_SPLASH_EFFECT_ID = 'default-orb-splash'

/**
 * Normalize character string to 'mage' | 'range' | 'melee'
 */
export function normalizeCharacterClass(character) {
  if (!character) return 'mage'
  const key = typeof character === 'object'
    ? (character.class_type || character.key_name || character.name || 'mage')
    : String(character)
  const lower = key.toLowerCase()
  if (lower.includes('range') || lower.includes('archer')) return 'range'
  if (lower.includes('melee') || lower.includes('dual')) return 'melee'
  return 'mage'
}

/**
 * Returns the character-specific default attack effect object
 */
export function getCharacterDefaultAttackEffect(characterClass) {
  const norm = normalizeCharacterClass(characterClass)
  const effectId = CHARACTER_DEFAULT_ATTACK_EFFECTS[norm] || 'default-orb'
  return COSMETIC_EFFECTS.find(e => e.id === effectId) || COSMETIC_EFFECTS[0]
}

/**
 * Returns the character-specific default splash effect object
 */
export function getCharacterDefaultSplashEffect(characterClass) {
  const norm = normalizeCharacterClass(characterClass)
  const effectId = CHARACTER_DEFAULT_SPLASH_EFFECTS[norm] || 'default-orb-splash'
  return COSMETIC_EFFECTS.find(e => e.id === effectId) || COSMETIC_EFFECTS.find(e => e.type === 'splash_effect')
}

/**
 * Returns the default splash effect object
 */
export function getDefaultSplashEffect(characterClass = 'mage') {
  return getCharacterDefaultSplashEffect(characterClass)
}

/**
 * Resolves an effect's PNG or GIF asset path
 */
export function resolveEffectPath(relPath) {
  if (!relPath) return ''
  return resolveAsset(relPath)
}

/**
 * Safely resolves the currently equipped Attack Effect with character default fallback.
 * Never returns null!
 */
export function resolveEquippedAttackEffect(equippedItemOrId, characterClass) {
  const fallback = getCharacterDefaultAttackEffect(characterClass)
  if (!equippedItemOrId) return fallback

  let effectId = ''
  if (typeof equippedItemOrId === 'object') {
    effectId = equippedItemOrId.effect_key || equippedItemOrId.id || ''
  } else {
    effectId = String(equippedItemOrId)
  }

  const found = COSMETIC_EFFECTS.find(e => e.id === effectId || e.effect_key === effectId)
  if (!found || found.type !== 'attack_effect') {
    return fallback
  }

  // If item is character-restricted and doesn't match, fallback
  const norm = normalizeCharacterClass(characterClass)
  if (found.character_class !== 'all' && found.character_class !== norm) {
    return fallback
  }

  return found
}

/**
 * Safely resolves the currently equipped Splash Effect with character default fallback.
 * Never returns null!
 */
export function resolveEquippedSplashEffect(equippedItemOrId, characterClass = 'mage') {
  const norm = normalizeCharacterClass(characterClass)
  const fallback = getCharacterDefaultSplashEffect(norm)
  if (!equippedItemOrId) return fallback

  let effectId = ''
  if (typeof equippedItemOrId === 'object') {
    effectId = equippedItemOrId.effect_key || equippedItemOrId.id || ''
  } else {
    effectId = String(equippedItemOrId)
  }

  if (effectId === 'default-splash') {
    return fallback
  }

  const found = COSMETIC_EFFECTS.find(e => e.id === effectId || e.effect_key === effectId)
  if (!found || found.type !== 'splash_effect') {
    return fallback
  }

  // If item is character-restricted and doesn't match character, fallback
  if (found.character_class !== 'all' && found.character_class !== norm) {
    return fallback
  }

  return found
}

export default {
  COSMETIC_EFFECTS,
  CHARACTER_DEFAULT_ATTACK_EFFECTS,
  CHARACTER_DEFAULT_SPLASH_EFFECTS,
  DEFAULT_SPLASH_EFFECT_ID,
  normalizeCharacterClass,
  getCharacterDefaultAttackEffect,
  getCharacterDefaultSplashEffect,
  getDefaultSplashEffect,
  resolveEffectPath,
  resolveEquippedAttackEffect,
  resolveEquippedSplashEffect
}
