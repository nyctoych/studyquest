/**
 * StudyQuest — Scholar Titles & Ranking Tier Utilities
 * Pure lore-friendly presentation helpers that enrich existing level and ELO data.
 */

export function getScholarTitle(level = 1, rating = 1200) {
  const lvl = Number(level) || 1
  const rtg = Number(rating) || 1200

  // High ELO honors
  if (rtg >= 1500) return 'Grandmaster Mindkeeper'
  if (rtg >= 1400) return 'High Arcane Champion'

  // Level-based scholar progression
  if (lvl >= 50) return 'Sovereign of Mindrealm'
  if (lvl >= 40) return 'Grand Theorist'
  if (lvl >= 30) return 'Master Clinician'
  if (lvl >= 20) return 'Arcane Inquirer'
  if (lvl >= 10) return 'Mindkeeper Adept'
  if (lvl >= 5)  return 'Acolyte of Insight'
  return 'Initiate Scholar'
}

export function getRankTier(rating = 1200) {
  const r = Number(rating) || 1200
  if (r >= 1500) return { name: 'Master', color: '#c084fc', border: '#a855f7', icon: 'crown' }
  if (r >= 1400) return { name: 'Diamond', color: '#38bdf8', border: '#0284c7', icon: 'gem' }
  if (r >= 1300) return { name: 'Platinum', color: '#34d399', border: '#059669', icon: 'shield' }
  if (r >= 1200) return { name: 'Gold', color: '#fbbf24', border: '#d97706', icon: 'trophy' }
  if (r >= 1100) return { name: 'Silver', color: '#cbd5e1', border: '#64748b', icon: 'shield' }
  return { name: 'Bronze', color: '#f97316', border: '#c2410c', icon: 'target' }
}

export function calculateWinRate(wins = 0, total = 0) {
  const w = Number(wins) || 0
  const t = Number(total) || 0
  if (!t || t <= 0) return 0
  return Math.min(100, Math.max(0, Math.round((w / t) * 100)))
}

export function formatNumber(val = 0) {
  return Number(val || 0).toLocaleString()
}
