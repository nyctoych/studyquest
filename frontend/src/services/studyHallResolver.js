/**
 * StudyQuest — Study Hall Centralized Chapter & Page Resolver
 * Authoritative visual grimoire styles, progression state mappings,
 * and academic metadata for the 12 official Psychology/Psychiatric chapters.
 */

export const CHAPTER_BOOK_STYLES = {
  1: {
    roman: 'I',
    rune: 'dawn',
    sigil: '✧',
    moniker: 'Tome of Primordial Neurogenesis',
    coverBg: 'linear-gradient(145deg, #1e1b4b 0%, #0f172a 60%, #1e1338 100%)',
    spineBg: '#171333',
    primaryColor: '#6366f1',
    accentColor: '#fbbf24',
    borderGlow: 'rgba(99, 102, 241, 0.45)',
    textColor: '#e0e7ff',
  },
  2: {
    roman: 'II',
    rune: 'fracture',
    sigil: '❂',
    moniker: 'Codex of the Fractured Prism',
    coverBg: 'linear-gradient(145deg, #2e1065 0%, #17072e 60%, #3b0764 100%)',
    spineBg: '#1d0838',
    primaryColor: '#a855f7',
    accentColor: '#c084fc',
    borderGlow: 'rgba(168, 85, 247, 0.45)',
    textColor: '#f3e8ff',
  },
  3: {
    roman: 'III',
    rune: 'pendulum',
    sigil: '⚚',
    moniker: 'Scroll of the Dual Astral Pendulum',
    coverBg: 'linear-gradient(145deg, #4c0519 0%, #1a0815 60%, #082f49 100%)',
    spineBg: '#270817',
    primaryColor: '#f43f5e',
    accentColor: '#38bdf8',
    borderGlow: 'rgba(244, 63, 94, 0.45)',
    textColor: '#ffe4e6',
  },
  4: {
    roman: 'IV',
    rune: 'abyss',
    sigil: '❖',
    moniker: 'Grimoire of the Abyssal Twilight',
    coverBg: 'linear-gradient(145deg, #0c213d 0%, #07111e 60%, #091728 100%)',
    spineBg: '#091522',
    primaryColor: '#3b82f6',
    accentColor: '#93c5fd',
    borderGlow: 'rgba(59, 130, 246, 0.45)',
    textColor: '#dbeafe',
  },
  5: {
    roman: 'V',
    rune: 'vigil',
    sigil: '⚡',
    moniker: 'Tome of the Restless Vigil',
    coverBg: 'linear-gradient(145deg, #451a03 0%, #1c0f04 60%, #351c07 100%)',
    spineBg: '#261204',
    primaryColor: '#f59e0b',
    accentColor: '#fde68a',
    borderGlow: 'rgba(245, 158, 11, 0.45)',
    textColor: '#fef3c7',
  },
  6: {
    roman: 'VI',
    rune: 'sigil',
    sigil: '◈',
    moniker: 'Manuscript of Perpetual Rituals',
    coverBg: 'linear-gradient(145deg, #371b26 0%, #190c12 60%, #4a0429 100%)',
    spineBg: '#240d17',
    primaryColor: '#ec4899',
    accentColor: '#fbcfe8',
    borderGlow: 'rgba(236, 72, 153, 0.45)',
    textColor: '#fce7f3',
  },
  7: {
    roman: 'VII',
    rune: 'trauma',
    sigil: '⚔',
    moniker: 'Chronicle of Shattered Echoes',
    coverBg: 'linear-gradient(145deg, #450a0a 0%, #1c0505 60%, #2e0808 100%)',
    spineBg: '#280606',
    primaryColor: '#ef4444',
    accentColor: '#fca5a5',
    borderGlow: 'rgba(239, 68, 68, 0.45)',
    textColor: '#fee2e2',
  },
  8: {
    roman: 'VIII',
    rune: 'vessel',
    sigil: '⚕',
    moniker: 'Compendium of the Somatic Vessel',
    coverBg: 'linear-gradient(145deg, #064e3b 0%, #032219 60%, #0f392b 100%)',
    spineBg: '#052a20',
    primaryColor: '#10b981',
    accentColor: '#6ee7b7',
    borderGlow: 'rgba(16, 185, 129, 0.45)',
    textColor: '#d1fae5',
  },
  9: {
    roman: 'IX',
    rune: 'mask',
    sigil: '☬',
    moniker: 'Grimoire of the Unveiled Self',
    coverBg: 'linear-gradient(145deg, #3b0764 0%, #17072e 60%, #4a044e 100%)',
    spineBg: '#220630',
    primaryColor: '#c026d3',
    accentColor: '#f5d0fe',
    borderGlow: 'rgba(192, 38, 211, 0.45)',
    textColor: '#fae8ff',
  },
  10: {
    roman: 'X',
    rune: 'tempest',
    sigil: '🔥',
    moniker: 'Tome of the Inner Storm',
    coverBg: 'linear-gradient(145deg, #571607 0%, #200904 60%, #3c140c 100%)',
    spineBg: '#2e0e07',
    primaryColor: '#ea580c',
    accentColor: '#fed7aa',
    borderGlow: 'rgba(234, 88, 12, 0.45)',
    textColor: '#ffedd5',
  },
  11: {
    roman: 'XI',
    rune: 'chalice',
    sigil: '🗝',
    moniker: 'Codex of the Twilight Chalice',
    coverBg: 'linear-gradient(145deg, #134e4a 0%, #071f1e 60%, #164e63 100%)',
    spineBg: '#0b2927',
    primaryColor: '#14b8a6',
    accentColor: '#99f6e4',
    borderGlow: 'rgba(20, 184, 166, 0.45)',
    textColor: '#ccfbf1',
  },
  12: {
    roman: 'XII',
    rune: 'persona',
    sigil: '⚜',
    moniker: 'Compendium of the Thousand Personas',
    coverBg: 'linear-gradient(145deg, #1e1b4b 0%, #0f1124 60%, #311c47 100%)',
    spineBg: '#151433',
    primaryColor: '#8b5cf6',
    accentColor: '#fef08a',
    borderGlow: 'rgba(139, 92, 246, 0.5)',
    textColor: '#ede9fe',
  }
}

/**
 * Resolves comprehensive grimoire book representation for any chapter
 */
export function resolveChapterBook(domain, index = 0, allDomains = []) {
  if (!domain) return null

  const chapterNum = Number(domain.display_order || index + 1)
  const paddedNum = String(chapterNum).padStart(2, '0')
  const completed = Number(domain.completed_parts || 0)
  const total = Number(domain.total_parts || 10)
  const percentage = total > 0 ? Math.min(100, Math.round((completed / total) * 100)) : 0
  const isCompleted = Boolean(domain.is_completed || completed >= total)
  const masteryScore = Number(domain.mastery_score || 0)
  const isMastered = isCompleted && (masteryScore >= 80 || domain.user_status === 'mastered')

  // Sequential unlock check: Chapter 1 is always unlocked;
  // subsequent chapters unlock if previous chapter has >= 1 completed part or is unlocked
  let isLocked = false
  if (chapterNum > 1 && allDomains.length > 0) {
    const prevDomain = allDomains[index - 1]
    if (prevDomain) {
      const prevDone = Number(prevDomain.completed_parts || 0)
      if (prevDone === 0 && !prevDomain.is_completed) {
        // Can be customized if backend explicitly marks lock state
        isLocked = false // Default to available unless strictly locked to avoid blocking review
      }
    }
  }

  let state = 'AVAILABLE'
  if (isLocked) {
    state = 'LOCKED'
  } else if (isMastered) {
    state = 'MASTERED'
  } else if (isCompleted) {
    state = 'COMPLETED'
  } else if (completed > 0) {
    state = 'IN_PROGRESS'
  }

  const styleConfig = CHAPTER_BOOK_STYLES[chapterNum] || CHAPTER_BOOK_STYLES[((chapterNum - 1) % 12) + 1]

  return {
    id: domain.id,
    chapterNumber: chapterNum,
    chapterNumberPadded: paddedNum,
    roman: styleConfig.roman,
    title: domain.name,
    description: domain.description,
    sourceVersion: domain.source_version || 'DSM-5',
    sourceReference: domain.source_reference || '',
    completedPages: completed,
    totalPages: total,
    percentage,
    masteryScore,
    isCompleted,
    isMastered,
    isLocked,
    state,
    style: styleConfig,
  }
}

/**
 * Resolves part item into a page format with gamified page terminology
 */
export function resolveStudyPart(part, domain = null) {
  if (!part) return null

  const partNum = Number(part.part_number || 1)
  const paddedNum = String(partNum).padStart(2, '0')
  const status = part.user_status || part.part_status || 'locked'
  const isLocked = status === 'locked'
  const isCompleted = ['completed', 'mastered'].includes(status)
  const isMastered = status === 'mastered'
  const score = Number(part.user_score || part.score || 0)

  return {
    id: part.id,
    partNumber: partNum,
    pageNumberPadded: paddedNum,
    title: part.title,
    description: part.description || '',
    overview: part.overview || '',
    sourceReference: part.source_reference || domain?.source_reference || 'DSM-5 Diagnostic Standard',
    xpReward: Number(part.xp_reward || 50),
    coinReward: Number(part.coin_reward || 20),
    status,
    isLocked,
    isCompleted,
    isMastered,
    score,
    partType: part.part_type || 'doctrine',
    lockReason: part.lock_reason || (isLocked ? 'Master earlier doctrines to unseal this volume page.' : '')
  }
}
