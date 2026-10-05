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

import { resolveAsset } from '../services/bossMapping'

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
    coin_cost: 300,
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
    coin_cost: 600,
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
    coin_cost: 1200,
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
    coin_cost: 300,
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
    coin_cost: 600,
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
    coin_cost: 1200,
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
    rarity: 'epic',
    coin_cost: 500,
    is_default: false,
    png_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.png',
    gif_path: 'assets/ATTACK EFFECTS/DOUBLE-WIELDER (MELEE)/elite melee.gif',
    description: 'A dual-cyclone blade strike that lacerates the space before the wielder.'
  },

  // ════════════════════════════════════════════════════════════════
  // 4. SPLASH EFFECTS: UNIVERSAL COMBAT IMPACTS
  // ════════════════════════════════════════════════════════════════
  {
    id: 'default-splash',
    effect_key: 'default-splash',
    name: 'Default Blade Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'all',
    rarity: 'common',
    coin_cost: 0,
    is_default: true,
    png_path: 'assets/SPLASH EFFECTS/default splash.png',
    gif_path: 'assets/SPLASH EFFECTS/default splash.gif',
    description: 'A clean, resounding cross-cut impact scattering luminous azure sparks.'
  },
  {
    id: 'crimson-slash',
    effect_key: 'crimson-slash',
    name: 'Crimson Claw Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'all',
    rarity: 'rare',
    coin_cost: 350,
    is_default: false,
    png_path: 'assets/SPLASH EFFECTS/crimson slash.png',
    gif_path: 'assets/SPLASH EFFECTS/crimson slash.gif',
    description: 'A savage triple-claw impact bursting with sanguine embers and blood light.'
  },
  {
    id: 'arcane-burst',
    effect_key: 'arcane-burst',
    name: 'Arcane Nova Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'all',
    rarity: 'epic',
    coin_cost: 700,
    is_default: false,
    png_path: 'assets/SPLASH EFFECTS/arcane burst.png',
    gif_path: 'assets/SPLASH EFFECTS/arcane burst.gif',
    description: 'An explosive eight-pointed arcane star detonating with mystic rings.'
  },
  {
    id: 'lightning-strike',
    effect_key: 'lightning-strike',
    name: 'Thunderbolt Splash',
    category_slug: 'splash_effect',
    type: 'splash_effect',
    character_class: 'all',
    rarity: 'legendary',
    coin_cost: 1200,
    is_default: false,
    png_path: 'assets/SPLASH EFFECTS/lightning strike.png',
    gif_path: 'assets/SPLASH EFFECTS/lightning strike.gif',
    description: 'A devastating electric thunderbolt slamming the impact point with shockwaves.'
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

export const DEFAULT_SPLASH_EFFECT_ID = 'default-splash'

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
 * Returns the default splash effect object
 */
export function getDefaultSplashEffect() {
  return COSMETIC_EFFECTS.find(e => e.id === DEFAULT_SPLASH_EFFECT_ID) || COSMETIC_EFFECTS.find(e => e.type === 'splash_effect')
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
 * Safely resolves the currently equipped Splash Effect with universal default fallback.
 * Never returns null!
 */
export function resolveEquippedSplashEffect(equippedItemOrId) {
  const fallback = getDefaultSplashEffect()
  if (!equippedItemOrId) return fallback

  let effectId = ''
  if (typeof equippedItemOrId === 'object') {
    effectId = equippedItemOrId.effect_key || equippedItemOrId.id || ''
  } else {
    effectId = String(equippedItemOrId)
  }

  const found = COSMETIC_EFFECTS.find(e => e.id === effectId || e.effect_key === effectId)
  if (!found || found.type !== 'splash_effect') {
    return fallback
  }

  return found
}

export default {
  COSMETIC_EFFECTS,
  CHARACTER_DEFAULT_ATTACK_EFFECTS,
  DEFAULT_SPLASH_EFFECT_ID,
  normalizeCharacterClass,
  getCharacterDefaultAttackEffect,
  getDefaultSplashEffect,
  resolveEffectPath,
  resolveEquippedAttackEffect,
  resolveEquippedSplashEffect
}
