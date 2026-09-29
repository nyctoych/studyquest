/**
 * Formatting utilities for StudyQuest
 */

// Format XP with commas
export const formatXP = (xp) => Number(xp || 0).toLocaleString()

// Format points
export const formatPoints = (pts) => Number(pts || 0).toLocaleString()

// Format deadline date
export const formatDate = (date) => {
  if (!date) return 'No deadline'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
  })
}

// Format deadline helper
export const formatDeadline = (date) => {
  if (!date) return 'No deadline'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

// Check if date is past deadline
export const isPastDeadline = (date) => {
  if (!date) return false
  return new Date(date).getTime() < new Date().getTime()
}

// Relative time (e.g. "2 days left", "overdue")
export const relativeDeadline = (deadline) => {
  if (!deadline) return null
  const now  = new Date()
  const end  = new Date(deadline)
  const diff = end - now
  const days = Math.ceil(diff / (1000 * 60 * 60 * 24))

  if (days < 0)  return { text: `${Math.abs(days)}d overdue`, urgent: true }
  if (days === 0) return { text: 'Due today!', urgent: true }
  if (days === 1) return { text: 'Due tomorrow', urgent: true }
  if (days <= 3)  return { text: `${days} days left`, urgent: true }
  return { text: `${days} days left`, urgent: false }
}

// Level progress percentage
export const levelProgress = (xpInLevel, xpForNext) => {
  if (!xpForNext) return 0
  return Math.min(100, Math.round((xpInLevel / xpForNext) * 100))
}

// Capitalize
export const capitalize = (str) =>
  str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

// Difficulty emoji
export const diffEmoji = (diff) => ({
  easy: '🟢', medium: '🟡', hard: '🔴', epic: '💜', legendary: '⚡',
}[diff] || '⚪')

// Priority emoji
export const priorityEmoji = (p) => ({
  low: '🔵', medium: '🟡', high: '🟠', urgent: '🔴',
}[p] || '⚪')

// Subject color
export const subjectColor = (subject) => {
  const colors = {
    Math: '#60a5fa', Mathematics: '#60a5fa',
    Science: '#4ade80', Biology: '#4ade80', Chemistry: '#34d399', Physics: '#a3e635',
    English: '#f472b6', Literature: '#e879f9',
    History: '#fb923c', Social: '#fbbf24',
    Technology: '#818cf8', Programming: '#6366f1',
    General: '#94a3b8',
  }
  return colors[subject] || '#94a3b8'
}

// Duration string (e.g. "2h 30m")
export const formatDuration = (hours) => {
  if (!hours) return '0m'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  if (h === 0) return `${m}m`
  if (m === 0) return `${h}h`
  return `${h}h ${m}m`
}

// Time remaining for plants
export const timeRemaining = (seconds) => {
  if (seconds <= 0) return 'Ready!'
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  if (h > 0) return `${h}h ${m}m`
  return `${m}m`
}
