/**
 * StudyQuest — Adventure Battlefield Environments Configuration
 * 
 * Centralized environmental configuration connecting Adventure Chapter Maps
 * to localized encounter battlefields.
 * 
 * Hierarchy & Resolution:
 * 1. Part-specific battlefield variant
 * 2. Chapter default battlefield
 * 3. Global Adventure emergency fallback
 */

import { resolveAsset } from '../services/bossMapping'

export const BATTLEFIELD_ENVIRONMENTS = {
  chapter1: {
    chapterId: 1,
    name: 'Wilderness of Awakening',
    defaultBackground: 'assets/adventure/battlefields/chapter-01-outdoor.jpg',
    groundType: 'forest_grass',
    lighting: 'dusk_purple',
    weather: 'clear_mist',
    ambientEffects: ['fireflies', 'gentle_mist'],
    shadowStyle: 'rgba(10, 24, 12, 0.65)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0,
    parts: {
      1: {
        environmentId: 'ch1_part1_forest',
        name: 'Forest Glade',
        backgroundAsset: 'assets/adventure/battlefields/chapter-01-forest.jpg',
        groundType: 'forest_moss_grass',
        lighting: 'dusk_purple',
        ambientEffects: ['fireflies'],
        playerPosition: { x: 17, y: 71 },
        enemyPosition: { x: 75, y: 27 }
      },
      2: {
        environmentId: 'ch1_part2_path',
        name: 'Forest Path',
        backgroundAsset: 'assets/adventure/battlefields/chapter-01-path.jpg',
        groundType: 'dirt_stone_path',
        lighting: 'dusk_purple',
        ambientEffects: ['fireflies', 'dust_motes'],
        playerPosition: { x: 18, y: 70 },
        enemyPosition: { x: 74, y: 26 }
      },
      3: {
        environmentId: 'ch1_part3_ruins',
        name: 'Ancient Stone Clearing',
        backgroundAsset: 'assets/adventure/battlefields/chapter-01-ruins.jpg',
        groundType: 'mossy_ruin_stones',
        lighting: 'dusk_purple',
        ambientEffects: ['stone_dust', 'fireflies'],
        playerPosition: { x: 19, y: 69 },
        enemyPosition: { x: 73, y: 25 }
      }
    }
  },

  chapter2: {
    chapterId: 2,
    name: 'The Castle Barbican',
    defaultBackground: 'assets/adventure/battlefields/chapter-02-castle-exterior.jpg',
    groundType: 'castle_courtyard_stone',
    lighting: 'torchlight_amber',
    weather: 'overcast_dusk',
    ambientEffects: ['torch_embers', 'night_breeze'],
    shadowStyle: 'rgba(15, 12, 22, 0.7)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0,
    parts: {
      1: {
        environmentId: 'ch2_part1_gate',
        name: 'Portcullis Gatehouse',
        backgroundAsset: 'assets/adventure/battlefields/chapter-02-gate.jpg',
        groundType: 'flagstone_gate',
        lighting: 'torchlight_amber',
        ambientEffects: ['torch_embers'],
        playerPosition: { x: 18, y: 70 },
        enemyPosition: { x: 75, y: 27 }
      },
      2: {
        environmentId: 'ch2_part2_courtyard',
        name: 'Fortress Courtyard',
        backgroundAsset: 'assets/adventure/battlefields/chapter-02-courtyard.jpg',
        groundType: 'cobblestone_courtyard',
        lighting: 'torchlight_amber',
        ambientEffects: ['torch_embers'],
        playerPosition: { x: 19, y: 69 },
        enemyPosition: { x: 73, y: 25 }
      }
    }
  },

  chapter3: {
    chapterId: 3,
    name: 'The Grand Citadel Hall',
    defaultBackground: 'assets/adventure/battlefields/chapter-03-citadel-sanctuary.jpg?v=2',
    groundType: 'polished_gothic_flagstone',
    lighting: 'gothic_candlelight',
    weather: 'indoor',
    ambientEffects: ['candle_glow', 'vault_dust'],
    shadowStyle: 'rgba(12, 6, 18, 0.75)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter4: {
    chapterId: 4,
    name: 'The Sanguine Throne',
    defaultBackground: 'assets/adventure/battlefields/chapter-04-sanguine-throne.jpg?v=2',
    groundType: 'corrupted_bloodstone',
    lighting: 'sanguine_crimson',
    weather: 'corrupted_air',
    ambientEffects: ['crimson_embers', 'dark_tendrils'],
    shadowStyle: 'rgba(28, 4, 10, 0.8)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter5: {
    chapterId: 5,
    name: 'The Forgotten Catacombs',
    defaultBackground: 'assets/adventure/battlefields/chapter-05-catacomb-crypt.jpg?v=2',
    groundType: 'ancient_crypt_stone',
    lighting: 'crypt_cyan_torch',
    weather: 'subterranean_damp',
    ambientEffects: ['crypt_mist', 'cold_embers'],
    shadowStyle: 'rgba(4, 12, 18, 0.78)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter6: {
    chapterId: 6,
    name: 'The Shattered Inner Realm',
    defaultBackground: 'assets/adventure/battlefields/chapter-06-astral-rift.jpg?v=2',
    groundType: 'floating_void_platform',
    lighting: 'astral_violet',
    weather: 'dimensional_rift',
    ambientEffects: ['void_sparks', 'gravity_debris'],
    shadowStyle: 'rgba(18, 4, 28, 0.75)',
    playerPosition: { x: 19, y: 69 },
    enemyPosition: { x: 73, y: 25 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter7: {
    chapterId: 7,
    name: 'The Ruined District',
    defaultBackground: 'assets/adventure/battlefields/chapter-07-ruined-district.jpg?v=2',
    groundType: 'ruined_cobblestone_rubble',
    lighting: 'ruined_violet_mist',
    weather: 'ash_mist',
    ambientEffects: ['ash_particles', 'drifting_fog'],
    shadowStyle: 'rgba(14, 8, 22, 0.75)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter8: {
    chapterId: 8,
    name: 'The Forbidden Archive',
    defaultBackground: 'assets/adventure/battlefields/chapter-08-arcane-library.jpg?v=2',
    groundType: 'arcane_archive_wood_stone',
    lighting: 'arcane_blue_gold',
    weather: 'vault_enchanted',
    ambientEffects: ['floating_pages', 'arcane_sparks'],
    shadowStyle: 'rgba(6, 14, 24, 0.75)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter9: {
    chapterId: 9,
    name: 'The Dream Realm',
    defaultBackground: 'assets/adventure/battlefields/chapter-09-dream-dais.jpg?v=2',
    groundType: 'astral_crystal_dais',
    lighting: 'celestial_dream',
    weather: 'astral_nebula',
    ambientEffects: ['dream_motes', 'stardust'],
    shadowStyle: 'rgba(22, 6, 26, 0.7)',
    playerPosition: { x: 19, y: 69 },
    enemyPosition: { x: 73, y: 25 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter10: {
    chapterId: 10,
    name: 'The Fractured Realm',
    defaultBackground: 'assets/adventure/battlefields/chapter-10-fractured-abyss.jpg?v=2',
    groundType: 'obsidian_fracture_rock',
    lighting: 'crimson_fracture',
    weather: 'singularity_storm',
    ambientEffects: ['crimson_sparks', 'rift_distortion'],
    shadowStyle: 'rgba(30, 4, 10, 0.82)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter11: {
    chapterId: 11,
    name: 'The Black Archive',
    defaultBackground: 'assets/adventure/battlefields/chapter-11-vault-archive.jpg?v=2',
    groundType: 'monolithic_obsidian_floor',
    lighting: 'monolithic_violet',
    weather: 'deep_sanctuary',
    ambientEffects: ['runic_shimmer', 'void_pulses'],
    shadowStyle: 'rgba(10, 6, 20, 0.85)',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0
  },

  chapter12: {
    chapterId: 12,
    name: "The Creator's Domain",
    defaultBackground: 'assets/adventure/battlefields/chapter-12-genesis-singularity.jpg?v=2',
    groundType: 'genesis_singularity_core',
    lighting: 'genesis_gold_void',
    weather: 'cosmic_genesis',
    ambientEffects: ['genesis_rings', 'singularity_motes'],
    shadowStyle: 'rgba(20, 10, 4, 0.85)',
    playerPosition: { x: 19, y: 68 },
    enemyPosition: { x: 73, y: 25 },
    playerScale: 1.0,
    enemyScale: 1.15
  }
}

/**
 * THE BLACK ARCHIVE — dedicated Final Boss battlefield (The Creator).
 * Deepest layer of Mindrealm; independent from any chapter environment.
 */
export const FINAL_BLACK_ARCHIVE_ENVIRONMENT = {
  environmentId: 'final_black_archive',
  chapterId: 0,
  partId: 'final-boss',
  name: 'The Black Archive',
  backgroundAsset: 'assets/adventure/battlefields/final-black-archive.jpg?v=1',
  foregroundAsset: null,
  groundType: 'monolithic_obsidian_floor',
  lighting: 'monolithic_violet',
  weather: 'deep_sanctuary',
  playerPosition: { x: 18, y: 70 },
  enemyPosition: { x: 73, y: 24 },
  playerScale: 1.0,
  enemyScale: 1.15,
  shadowStyle: 'rgba(8, 4, 16, 0.88)',
  ambientEffects: ['runic_shimmer', 'void_pulses'],
  bossVariant: true
}

/**
 * Global Emergency Fallback Battlefield
 */
export const DEFAULT_BATTLEFIELD_FALLBACK = {
  environmentId: 'default_forest_clearing',
  chapterId: 1,
  partId: 1,
  name: 'Mindrealm Perimeter Glade',
  backgroundAsset: '/assets/adventure/battlefields/chapter-01-outdoor.jpg',
  foregroundAsset: null,
  groundType: 'forest_grass',
  lighting: 'dusk_purple',
  weather: 'clear_mist',
  playerPosition: { x: 18, y: 70 },
  enemyPosition: { x: 74, y: 26 },
  playerScale: 1.0,
  enemyScale: 1.0,
  shadowStyle: 'rgba(10, 24, 12, 0.65)',
  ambientEffects: ['fireflies'],
  bossVariant: false
}

/**
 * Resolves the appropriate battlefield environment based on Chapter ID and Part ID.
 * 
 * Strict Fallback Priority:
 * 1. Part-specific battlefield
 * 2. Chapter battlefield
 * 3. Global emergency fallback
 * 
 * Never returns a blank battlefield or an unrelated chapter environment.
 * 
 * @param {number|string} chapterId - Chapter number (1-12)
 * @param {number|string|null} partId - Part number (e.g. 1-10) or 'boss'
 * @param {boolean} isBoss - Whether encounter is a boss climax trial
 * @returns {Object} Complete environment configuration
 */
export function resolveBattlefieldEnvironment(chapterId, partId = null, isBoss = false, isFinalBoss = false) {
  // The Creator's Black Archive is a dedicated final environment (never a chapter battlefield)
  if (isFinalBoss) {
    return { ...FINAL_BLACK_ARCHIVE_ENVIRONMENT }
  }
  const chNum = Number(chapterId) || 1
  const key = `chapter${chNum}`
  const chapterConfig = BATTLEFIELD_ENVIRONMENTS[key] || BATTLEFIELD_ENVIRONMENTS.chapter1

  const pNum = Number(partId) || (partId === 'boss' ? 'boss' : 1)

  // 1. Check for Part-specific environment override
  if (!isBoss && chapterConfig.parts && chapterConfig.parts[pNum]) {
    const partConf = chapterConfig.parts[pNum]
    return {
      environmentId: partConf.environmentId || `${key}_part${pNum}`,
      chapterId: chNum,
      partId: pNum,
      name: partConf.name || `${chapterConfig.name} - Part ${pNum}`,
      backgroundAsset: partConf.backgroundAsset || chapterConfig.defaultBackground,
      foregroundAsset: partConf.foregroundAsset || null,
      groundType: partConf.groundType || chapterConfig.groundType,
      lighting: partConf.lighting || chapterConfig.lighting,
      weather: partConf.weather || chapterConfig.weather,
      playerPosition: partConf.playerPosition || chapterConfig.playerPosition,
      enemyPosition: partConf.enemyPosition || chapterConfig.enemyPosition,
      playerScale: partConf.playerScale || chapterConfig.playerScale || 1.0,
      enemyScale: partConf.enemyScale || chapterConfig.enemyScale || 1.0,
      shadowStyle: partConf.shadowStyle || chapterConfig.shadowStyle,
      ambientEffects: partConf.ambientEffects || chapterConfig.ambientEffects || [],
      bossVariant: false
    }
  }

  // 2. Check for Chapter default environment
  if (chapterConfig.defaultBackground) {
    return {
      environmentId: isBoss ? `${key}_boss_arena` : `${key}_default`,
      chapterId: chNum,
      partId: isBoss ? 'boss' : pNum,
      name: isBoss ? `${chapterConfig.name} - Guardian Trial Arena` : chapterConfig.name,
      backgroundAsset: chapterConfig.defaultBackground,
      foregroundAsset: null,
      groundType: chapterConfig.groundType,
      lighting: chapterConfig.lighting,
      weather: chapterConfig.weather,
      playerPosition: chapterConfig.playerPosition,
      enemyPosition: isBoss ? { x: 73, y: 24 } : chapterConfig.enemyPosition,
      playerScale: chapterConfig.playerScale || 1.0,
      enemyScale: isBoss ? (chapterConfig.enemyScale ? chapterConfig.enemyScale * 1.1 : 1.15) : (chapterConfig.enemyScale || 1.0),
      shadowStyle: chapterConfig.shadowStyle,
      ambientEffects: chapterConfig.ambientEffects || [],
      bossVariant: isBoss
    }
  }

  // 3. Global emergency fallback
  return {
    ...DEFAULT_BATTLEFIELD_FALLBACK,
    chapterId: chNum,
    partId: pNum,
    bossVariant: isBoss
  }
}

/**
 * Preload the current battlefield background and next part/chapter background
 * for immediate latency-free battle transitions.
 */
export function preloadBattlefieldAssets(chapterId, partId = null) {
  const env = resolveBattlefieldEnvironment(chapterId, partId)
  if (env && env.backgroundAsset) {
    const img = new Image()
    img.src = resolveAsset(env.backgroundAsset)
  }

  // Preload next part or next chapter if available
  const nextPartNum = (Number(partId) || 1) + 1
  const nextEnv = resolveBattlefieldEnvironment(chapterId, nextPartNum)
  if (nextEnv && nextEnv.backgroundAsset && nextEnv.backgroundAsset !== env.backgroundAsset) {
    const nextImg = new Image()
    nextImg.src = resolveAsset(nextEnv.backgroundAsset)
  }
}
