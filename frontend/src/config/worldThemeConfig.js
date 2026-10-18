/**
 * StudyQuest — Centralized World & Module Theme Configuration
 * Supports 3-Dimensional World System (Review, Main, Others)
 * and Global Night Mode ↔ Morning Mode environmental resolution.
 */

import { resolveAsset } from '../services/bossMapping'

export const WORLD_BACKGROUNDS = {
  review: {
    night: 'assets/backgrounds/world/review/review-night.png',
    morning: 'assets/backgrounds/world/review/review-morning.png',
    title: 'Training Grounds',
    subtitle: 'Outside Arena & Ancient Trials'
  },
  main: {
    night: 'assets/backgrounds/world/main/main-night.png',
    morning: 'assets/backgrounds/world/main/main-morning.png',
    title: 'Central Citadel',
    subtitle: 'Town Hall & Core Academy'
  },
  others: {
    night: 'assets/backgrounds/world/others/others-night.png',
    morning: 'assets/backgrounds/world/others/others-morning.png',
    title: 'The Outskirts',
    subtitle: 'Merchant Bazaar & Castle Records'
  }
}

export const MODULE_BACKGROUNDS = {
  dailyTrial: {
    night: 'assets/game/daily-trial-bg.jpg',
    morning: 'assets/game/daily-trial-morning.jpg'
  },
  hallOfRecords: {
    night: 'assets/game/hall_of_records_bg.jpg',
    morning: 'assets/game/hall_of_records_morning.jpg'
  },
  market: {
    night: 'assets/game/market-shop-bg.jpg',
    morning: 'assets/game/market-shop-morning.jpg'
  },
  studyHallLibrary: {
    night: 'assets/game/study-hall-library.jpg',
    morning: 'assets/game/study-hall-library-morning.jpg'
  },
  studyHallShelves: {
    night: 'assets/game/study-hall-shelves.jpg',
    morning: 'assets/game/study-hall-shelves-morning.jpg'
  },
  studyHallReadingRoom: {
    night: 'assets/game/study-hall-reading-room.jpg',
    morning: 'assets/game/study-hall-reading-room-morning.jpg'
  }
}

export function resolveWorldBg(dimension = 'main', mode = 'night') {
  const dim = WORLD_BACKGROUNDS[dimension] || WORLD_BACKGROUNDS.main
  const asset = mode === 'morning' ? dim.morning : dim.night
  return resolveAsset(asset)
}

export function resolveModuleBg(moduleKey, mode = 'night') {
  const mod = MODULE_BACKGROUNDS[moduleKey]
  if (!mod) return ''
  const asset = mode === 'morning' ? mod.morning : mod.night
  return resolveAsset(asset)
}

export const resolveWorldBackground = resolveWorldBg
