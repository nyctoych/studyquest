/**
 * StudyQuest — Adventure Chapter Environments Configuration
 * 
 * Every chapter has its own unique, continuous background environment.
 * The world visually connects each chapter to the next:
 * Ch 1: Wilderness / Outside -> Ch 2: Castle Entrance / Barbican -> Ch 3: Castle Interior
 * -> Ch 4: Deep Castle Throne -> Ch 5: Subterranean Catacombs -> Ch 6: Fractured Inner Realm
 * -> Ch 7: Ruined District -> Ch 8: Forbidden Archive -> Ch 9: Dream Realm
 * -> Ch 10: Fractured Realm -> Ch 11: The Black Archive -> Ch 12: The Creator's Domain
 */

export const chapterBackgrounds = {
  1: '/assets/adventure/backgrounds/chapter-01-world.jpg',
  2: '/assets/adventure/backgrounds/chapter-02-castle-entrance.jpg',
  3: '/assets/adventure/backgrounds/chapter-03-castle-interior.jpg',
  4: '/assets/adventure/backgrounds/chapter-04-deep-castle.jpg',
  5: '/assets/adventure/backgrounds/chapter-05-catacombs.jpg',
  6: '/assets/adventure/backgrounds/chapter-06-fractured-realm.jpg',
  7: '/assets/adventure/backgrounds/chapter-07-ruined-district.jpg',
  8: '/assets/adventure/backgrounds/chapter-08-forbidden-archive.jpg',
  9: '/assets/adventure/backgrounds/chapter-09-dream-realm.jpg',
  10: '/assets/adventure/backgrounds/chapter-10-fractured-world.jpg',
  11: '/assets/adventure/backgrounds/chapter-11-black-archive.jpg',
  12: '/assets/adventure/backgrounds/chapter-12-creator-domain.jpg'
}

export const CHAPTER_ENVIRONMENTS = {
  1: {
    chapterId: 1,
    name: 'Wilderness of Awakening',
    subtitle: 'Outer Mindrealm Perimeter',
    landmark: 'Distant Citadel of Mind',
    bgUrl: chapterBackgrounds[1],
    themeColor: '#e6c845',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 65%, rgba(6, 7, 10, 0.55) 100%)',
    bossGlow: { top: '28%', left: '82.5%', color: 'rgba(239, 68, 68, 0.35)' },
    torches: [
      { id: 't1', top: '78.9%', left: '10.2%', type: 'fire-gold' },
      { id: 't2', top: '60.3%', left: '42.3%', type: 'fire-gold' },
      { id: 't3', top: '44.2%', left: '37.1%', type: 'fire-gold' },
      { id: 't4', top: '32.6%', left: '71.0%', type: 'fire-crimson-brazier' },
      { id: 't5', top: '38.2%', left: '84.1%', type: 'fire-crimson-brazier' }
    ],
    nodePositions: {
      1:  { top: '85%', left: '13%' },
      2:  { top: '78%', left: '20%' },
      3:  { top: '72%', left: '28%' },
      4:  { top: '65%', left: '35%' },
      5:  { top: '60%', left: '42%' },
      6:  { top: '66%', left: '50%' },
      7:  { top: '60%', left: '58%' },
      8:  { top: '52%', left: '66%' },
      9:  { top: '45%', left: '73%' },
      10: { top: '38%', left: '78%' },
      boss: { top: '21%', left: 'calc(82.5% - 52px)' }
    }
  },

  2: {
    chapterId: 2,
    name: 'The Castle Barbican',
    subtitle: 'Fortress Gate & Outer Ramparts',
    landmark: 'Great Portcullis & Drawbridge',
    bgUrl: chapterBackgrounds[2],
    themeColor: '#f97316',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 60%, rgba(10, 7, 14, 0.6) 100%)',
    bossGlow: { top: '26%', left: '84%', color: 'rgba(249, 115, 22, 0.35)' },
    torches: [
      { id: 't1', top: '54.5%', left: '39.9%', type: 'fire-gold' },
      { id: 't2', top: '57.2%', left: '53.5%', type: 'fire-gold' },
      { id: 't3', top: '48.3%', left: '59.9%', type: 'fire-gold' },
      { id: 't4', top: '61.8%', left: '76.2%', type: 'fire-gold' },
      { id: 't5', top: '49.6%', left: '70.0%', type: 'fire-gold' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '12%' },
      2:  { top: '76%', left: '19%' },
      3:  { top: '68%', left: '27%' },
      4:  { top: '61%', left: '34%' },
      5:  { top: '56%', left: '42%' },
      6:  { top: '62%', left: '50%' },
      7:  { top: '56%', left: '59%' },
      8:  { top: '48%', left: '68%' },
      9:  { top: '40%', left: '76%' },
      10: { top: '33%', left: '82%' },
      boss: { top: '20%', left: 'calc(84% - 52px)' }
    }
  },

  3: {
    chapterId: 3,
    name: 'The Grand Citadel Hall',
    subtitle: 'Gothic Castle Interior',
    landmark: 'Stained Glass Sanctum',
    bgUrl: chapterBackgrounds[3],
    themeColor: '#dc2626',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 62%, rgba(14, 6, 8, 0.65) 100%)',
    bossGlow: { top: '25%', left: '84%', color: 'rgba(220, 38, 38, 0.4)' },
    torches: [
      { id: 't1', top: '59.6%', left: '93.7%', type: 'fire-gold' },
      { id: 't2', top: '56.0%', left: '13.3%', type: 'fire-gold' },
      { id: 't3', top: '66.9%', left: '4.7%', type: 'fire-gold' },
      { id: 't4', top: '31.7%', left: '69.7%', type: 'fire-gold' },
      { id: 't5', top: '31.7%', left: '83.0%', type: 'fire-gold' }
    ],
    nodePositions: {
      1:  { top: '83%', left: '13%' },
      2:  { top: '75%', left: '21%' },
      3:  { top: '68%', left: '29%' },
      4:  { top: '62%', left: '36%' },
      5:  { top: '57%', left: '44%' },
      6:  { top: '62%', left: '52%' },
      7:  { top: '55%', left: '61%' },
      8:  { top: '47%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '20%', left: 'calc(84% - 52px)' }
    }
  },

  4: {
    chapterId: 4,
    name: 'The Sanguine Throne',
    subtitle: 'Deep Corrupted Royal Chambers',
    landmark: 'Ruined High Throne of Secrets',
    bgUrl: chapterBackgrounds[4],
    themeColor: '#b91c1c',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 60%, rgba(18, 5, 8, 0.7) 100%)',
    bossGlow: { top: '24%', left: '83%', color: 'rgba(185, 28, 28, 0.45)' },
    torches: [
      { id: 't1', top: '58.5%', left: '39.0%', type: 'fire-gold' },
      { id: 't2', top: '44.5%', left: '65.2%', type: 'fire-violet-rift' },
      { id: 't3', top: '60.5%', left: '83.2%', type: 'fire-violet-rift' },
      { id: 't4', top: '34.1%', left: '33.9%', type: 'fire-crimson-brazier' },
      { id: 't5', top: '35.5%', left: '82.7%', type: 'fire-crimson-brazier' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '13%' },
      2:  { top: '76%', left: '21%' },
      3:  { top: '69%', left: '29%' },
      4:  { top: '62%', left: '37%' },
      5:  { top: '56%', left: '45%' },
      6:  { top: '61%', left: '53%' },
      7:  { top: '54%', left: '61%' },
      8:  { top: '46%', left: '70%' },
      9:  { top: '39%', left: '77%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '19%', left: 'calc(83% - 52px)' }
    }
  },

  5: {
    chapterId: 5,
    name: 'The Forgotten Catacombs',
    subtitle: 'Subterranean Crypt Architecture',
    landmark: 'Sealed Vault of the Ancients',
    bgUrl: chapterBackgrounds[5],
    themeColor: '#38bdf8',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 60%, rgba(5, 10, 16, 0.75) 100%)',
    bossGlow: { top: '24%', left: '84%', color: 'rgba(56, 189, 248, 0.35)' },
    torches: [
      { id: 't1', top: '47.5%', left: '29.5%', type: 'fire-gold' },
      { id: 't2', top: '55.7%', left: '44.1%', type: 'fire-gold' },
      { id: 't3', top: '61.9%', left: '49.0%', type: 'fire-gold' },
      { id: 't4', top: '44.9%', left: '56.0%', type: 'fire-gold' },
      { id: 't5', top: '50.7%', left: '61.4%', type: 'fire-gold' },
      { id: 't6', top: '34.0%', left: '78.8%', type: 'fire-gold' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '14%' },
      2:  { top: '77%', left: '22%' },
      3:  { top: '70%', left: '30%' },
      4:  { top: '63%', left: '37%' },
      5:  { top: '57%', left: '45%' },
      6:  { top: '62%', left: '54%' },
      7:  { top: '54%', left: '62%' },
      8:  { top: '47%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '19%', left: 'calc(84% - 52px)' }
    }
  },

  6: {
    chapterId: 6,
    name: 'The Shattered Inner Realm',
    subtitle: 'Corrupted Dimensional Fracture',
    landmark: 'Floating Void Platforms',
    bgUrl: chapterBackgrounds[6],
    themeColor: '#a855f7',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 58%, rgba(12, 4, 18, 0.75) 100%)',
    bossGlow: { top: '25%', left: '83%', color: 'rgba(168, 85, 247, 0.45)' },
    torches: [
      { id: 't1', top: '65.8%', left: '56.0%', type: 'fire-crimson-brazier' },
      { id: 't2', top: '45.3%', left: '43.5%', type: 'fire-crimson-brazier' },
      { id: 't3', top: '30.7%', left: '71.2%', type: 'fire-crimson-brazier' },
      { id: 't4', top: '17.3%', left: '82.3%', type: 'fire-crimson-brazier' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '13%' },
      2:  { top: '76%', left: '21%' },
      3:  { top: '69%', left: '29%' },
      4:  { top: '62%', left: '37%' },
      5:  { top: '56%', left: '45%' },
      6:  { top: '61%', left: '53%' },
      7:  { top: '54%', left: '61%' },
      8:  { top: '46%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '20%', left: 'calc(83% - 52px)' }
    }
  },

  7: {
    chapterId: 7,
    name: 'The Ruined District',
    subtitle: 'Damaged Civilization of Mind',
    landmark: 'Shattered Cathedral Spires',
    bgUrl: chapterBackgrounds[7],
    themeColor: '#c084fc',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 60%, rgba(14, 8, 20, 0.7) 100%)',
    bossGlow: { top: '26%', left: '83%', color: 'rgba(192, 132, 252, 0.4)' },
    torches: [
      { id: 't1', top: '85.6%', left: '21.7%', type: 'fire-violet-rift' },
      { id: 't2', top: '77.2%', left: '31.0%', type: 'fire-violet-rift' },
      { id: 't3', top: '69.4%', left: '39.4%', type: 'fire-violet-rift' },
      { id: 't4', top: '59.1%', left: '50.7%', type: 'fire-violet-rift' },
      { id: 't5', top: '56.3%', left: '53.8%', type: 'fire-violet-rift' }
    ],
    nodePositions: {
      1:  { top: '85%', left: '12%' },
      2:  { top: '77%', left: '20%' },
      3:  { top: '70%', left: '28%' },
      4:  { top: '63%', left: '36%' },
      5:  { top: '57%', left: '44%' },
      6:  { top: '62%', left: '52%' },
      7:  { top: '55%', left: '60%' },
      8:  { top: '47%', left: '69%' },
      9:  { top: '39%', left: '77%' },
      10: { top: '33%', left: '82%' },
      boss: { top: '20%', left: 'calc(83% - 52px)' }
    }
  },

  8: {
    chapterId: 8,
    name: 'The Forbidden Archive',
    subtitle: 'Ancient Arcane Athenaeum',
    landmark: 'The Great Runic Vault Portal',
    bgUrl: chapterBackgrounds[8],
    themeColor: '#38bdf8',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 62%, rgba(5, 12, 18, 0.7) 100%)',
    bossGlow: { top: '25%', left: '85%', color: 'rgba(56, 189, 248, 0.45)' },
    torches: [
      { id: 't1', top: '28.5%', left: '78.2%', type: 'fire-gold' },
      { id: 't2', top: '38.9%', left: '90.2%', type: 'fire-gold' },
      { id: 't3', top: '57.0%', left: '39.0%', type: 'fire-gold' },
      { id: 't4', top: '73.0%', left: '15.8%', type: 'fire-gold' },
      { id: 't5', top: '48.5%', left: '56.5%', type: 'fire-gold' }
    ],
    nodePositions: {
      1:  { top: '83%', left: '13%' },
      2:  { top: '75%', left: '21%' },
      3:  { top: '68%', left: '29%' },
      4:  { top: '61%', left: '37%' },
      5:  { top: '56%', left: '45%' },
      6:  { top: '61%', left: '53%' },
      7:  { top: '54%', left: '61%' },
      8:  { top: '46%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '20%', left: 'calc(85% - 52px)' }
    }
  },

  9: {
    chapterId: 9,
    name: 'The Dream Realm',
    subtitle: 'Surreal Celestial Drift',
    landmark: 'The Astral Mirror Dais',
    bgUrl: chapterBackgrounds[9],
    themeColor: '#ec4899',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 58%, rgba(14, 5, 20, 0.7) 100%)',
    bossGlow: { top: '24%', left: '84%', color: 'rgba(236, 72, 153, 0.45)' },
    torches: [
      { id: 't1', top: '11.9%', left: '66.3%', type: 'fire-cyan-crystal' },
      { id: 't2', top: '56.7%', left: '76.0%', type: 'fire-cyan-crystal' },
      { id: 't3', top: '27.1%', left: '31.2%', type: 'fire-cyan-crystal' },
      { id: 't4', top: '86.3%', left: '27.9%', type: 'fire-violet-rift' },
      { id: 't5', top: '38.3%', left: '82.1%', type: 'fire-cyan-crystal' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '14%' },
      2:  { top: '76%', left: '22%' },
      3:  { top: '69%', left: '30%' },
      4:  { top: '62%', left: '37%' },
      5:  { top: '56%', left: '45%' },
      6:  { top: '61%', left: '53%' },
      7:  { top: '54%', left: '62%' },
      8:  { top: '46%', left: '71%' },
      9:  { top: '38%', left: '78%' },
      10: { top: '31%', left: '83%' },
      boss: { top: '19%', left: 'calc(84% - 52px)' }
    }
  },

  10: {
    chapterId: 10,
    name: 'The Fractured Realm',
    subtitle: 'Severe Reality Collapse',
    landmark: 'Crimson Storm Singularity',
    bgUrl: chapterBackgrounds[10],
    themeColor: '#ef4444',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 55%, rgba(20, 4, 8, 0.8) 100%)',
    bossGlow: { top: '25%', left: '83%', color: 'rgba(239, 68, 68, 0.5)' },
    torches: [
      { id: 't1', top: '48.9%', left: '17.0%', type: 'fire-crimson-brazier' },
      { id: 't2', top: '45.3%', left: '43.5%', type: 'fire-crimson-brazier' },
      { id: 't3', top: '28.3%', left: '77.9%', type: 'fire-crimson-brazier' },
      { id: 't4', top: '17.3%', left: '82.3%', type: 'fire-crimson-brazier' },
      { id: 't5', top: '62.9%', left: '86.0%', type: 'fire-crimson-brazier' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '13%' },
      2:  { top: '76%', left: '21%' },
      3:  { top: '69%', left: '29%' },
      4:  { top: '62%', left: '37%' },
      5:  { top: '56%', left: '45%' },
      6:  { top: '61%', left: '53%' },
      7:  { top: '54%', left: '61%' },
      8:  { top: '46%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '19%', left: 'calc(83% - 52px)' }
    }
  },

  11: {
    chapterId: 11,
    name: 'The Black Archive',
    subtitle: 'Monolithic Obsidian Sanctuary',
    landmark: 'The Colossal Iron Vault',
    bgUrl: chapterBackgrounds[11],
    themeColor: '#8b5cf6',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 56%, rgba(8, 6, 14, 0.8) 100%)',
    bossGlow: { top: '26%', left: '84%', color: 'rgba(139, 92, 246, 0.5)' },
    torches: [
      { id: 't1', top: '20.7%', left: '75.9%', type: 'fire-crimson-brazier' },
      { id: 't2', top: '30.5%', left: '87.6%', type: 'fire-crimson-brazier' },
      { id: 't3', top: '58.3%', left: '28.1%', type: 'fire-crimson-brazier' },
      { id: 't4', top: '52.7%', left: '38.5%', type: 'fire-crimson-brazier' },
      { id: 't5', top: '36.8%', left: '60.0%', type: 'fire-crimson-brazier' }
    ],
    nodePositions: {
      1:  { top: '83%', left: '14%' },
      2:  { top: '76%', left: '22%' },
      3:  { top: '69%', left: '30%' },
      4:  { top: '62%', left: '38%' },
      5:  { top: '56%', left: '46%' },
      6:  { top: '60%', left: '54%' },
      7:  { top: '53%', left: '62%' },
      8:  { top: '46%', left: '70%' },
      9:  { top: '39%', left: '78%' },
      10: { top: '32%', left: '83%' },
      boss: { top: '20%', left: 'calc(84% - 52px)' }
    }
  },

  12: {
    chapterId: 12,
    name: "The Creator's Domain",
    subtitle: 'The Genesis Singularity',
    landmark: 'Heart of the Fracture & Throne',
    bgUrl: chapterBackgrounds[12],
    themeColor: '#f59e0b',
    ambientVignette: 'radial-gradient(ellipse at center, transparent 52%, rgba(10, 5, 20, 0.75) 100%)',
    bossGlow: { top: '33%', left: '67%', color: 'rgba(245, 158, 11, 0.55)' },
    torches: [
      { id: 't1', top: '21.6%', left: '75.7%', type: 'fire-violet-rift' },
      { id: 't2', top: '50.2%', left: '46.2%', type: 'fire-cyan-crystal' },
      { id: 't3', top: '63.0%', left: '70.8%', type: 'fire-cyan-crystal' },
      { id: 't4', top: '15.6%', left: '16.6%', type: 'fire-gold' },
      { id: 't5', top: '55.1%', left: '53.1%', type: 'fire-gold' }
    ],
    nodePositions: {
      1:  { top: '84%', left: '12%' },
      2:  { top: '76%', left: '19%' },
      3:  { top: '68%', left: '27%' },
      4:  { top: '62%', left: '34%' },
      5:  { top: '57%', left: '41%' },
      6:  { top: '62%', left: '48%' },
      7:  { top: '56%', left: '56%' },
      8:  { top: '50%', left: '63%' },
      9:  { top: '44%', left: '70%' },
      10: { top: '38%', left: '76%' },
      boss: { top: '28%', left: 'calc(67% - 52px)' }
    }
  }
}

/**
 * Returns environment metadata for a specific chapter ID or display order.
 */
export function getChapterEnvironment(chapterNumOrId) {
  const num = Number(chapterNumOrId) || 1
  return CHAPTER_ENVIRONMENTS[num] || CHAPTER_ENVIRONMENTS[1]
}

/**
 * Preload adjacent chapter background images to ensure instantaneous transitions.
 */
export function preloadAdjacentBackgrounds(currentChapterNum) {
  const num = Number(currentChapterNum) || 1
  const targets = [num - 1, num + 1].filter(n => n >= 1 && n <= 12)
  targets.forEach(n => {
    const bg = chapterBackgrounds[n]
    if (bg) {
      const img = new Image()
      img.src = bg
    }
  })
}
