/**
 * StudyQuest — Centralized Dotto Pixel Emoji Mapping
 *
 * Maps standard/browser Unicode emojis and semantic keys to pixel-art assets.
 * Strictly adheres to pixel-art styling (image-rendering: pixelated).
 * Never silently falls back to OS-rendered Unicode emojis.
 */

export const EMOJI_SIZES = {
  xs: 12,
  sm: 16,
  md: 20,
  lg: 24,
  xl: 32,
  game: 48
}

// Dynamically compute base directory for Dotto pixel emoji assets to support root and subpaths
export function getDottoBaseUrl() {
  if (typeof window !== 'undefined' && window.location) {
    const pathname = window.location.pathname || '/'
    const dir = pathname.substring(0, pathname.lastIndexOf('/') + 1) || '/'
    return `${dir}assets/dotto/`.replace(/\/+/g, '/')
  }
  return './assets/dotto/'
}

export const emojiMap = {
  // ── COMBAT & ADVENTURE ──
  '⚔️': { name: 'sword', file: 'sword.svg', label: 'Combat / Battle', category: 'combat' },
  '⚔':  { name: 'sword', file: 'sword.svg', label: 'Combat / Battle', category: 'combat' },
  'sword': { name: 'sword', file: 'sword.svg', label: 'Combat / Battle', category: 'combat' },

  '🛡️': { name: 'shield', file: 'shield.svg', label: 'Shield / Defense', category: 'combat' },
  '🛡':  { name: 'shield', file: 'shield.svg', label: 'Shield / Defense', category: 'combat' },
  'shield': { name: 'shield', file: 'shield.svg', label: 'Shield / Defense', category: 'combat' },

  '🗡️': { name: 'dagger', file: 'sword.svg', label: 'Dagger', category: 'combat' },
  '🗡':  { name: 'dagger', file: 'sword.svg', label: 'Dagger', category: 'combat' },

  '🏹': { name: 'bow', file: 'target.svg', label: 'Bow & Arrow', category: 'combat' },

  '🎯': { name: 'target', file: 'target.svg', label: 'Target / Objective', category: 'combat' },
  'target': { name: 'target', file: 'target.svg', label: 'Target / Objective', category: 'combat' },

  '⚡': { name: 'lightning', file: 'lightning.svg', label: 'Lightning / Energy', category: 'combat' },
  'lightning': { name: 'lightning', file: 'lightning.svg', label: 'Lightning / Energy', category: 'combat' },
  'zap': { name: 'lightning', file: 'lightning.svg', label: 'Lightning / Energy', category: 'combat' },

  '💀': { name: 'skull', file: 'skull.svg', label: 'Defeat / Nightmare', category: 'combat' },
  'skull': { name: 'skull', file: 'skull.svg', label: 'Defeat / Nightmare', category: 'combat' },

  '👹': { name: 'ogre', file: 'ogre.svg', label: 'Boss Aberration', category: 'combat' },
  'ogre': { name: 'ogre', file: 'ogre.svg', label: 'Boss Aberration', category: 'combat' },

  '❤️': { name: 'heart', file: 'heart.svg', label: 'Health / HP', category: 'combat' },
  '❤':  { name: 'heart', file: 'heart.svg', label: 'Health / HP', category: 'combat' },
  'heart': { name: 'heart', file: 'heart.svg', label: 'Health / HP', category: 'combat' },

  '🩸': { name: 'blood', file: 'blood.svg', label: 'Health / Blood', category: 'combat' },
  'blood': { name: 'blood', file: 'blood.svg', label: 'Health / Blood', category: 'combat' },

  '🧪': { name: 'potion', file: 'potion.svg', label: 'Potion / Elixir', category: 'combat' },
  'potion': { name: 'potion', file: 'potion.svg', label: 'Potion / Elixir', category: 'combat' },

  '💥': { name: 'sparkles', file: 'sparkles.svg', label: 'Hit Impact', category: 'combat' },

  // ── PROGRESSION, STATS & ECONOMY ──
  '⭐': { name: 'star', file: 'star.svg', label: 'Star / Mastery / XP', category: 'progression' },
  'star': { name: 'star', file: 'star.svg', label: 'Star / Mastery / XP', category: 'progression' },

  '🌟': { name: 'star', file: 'star.svg', label: 'Glowing Star', category: 'progression' },
  '✨': { name: 'sparkles', file: 'sparkles.svg', label: 'Sparkles / Reward', category: 'progression' },
  'sparkles': { name: 'sparkles', file: 'sparkles.svg', label: 'Sparkles / Reward', category: 'progression' },

  '🏆': { name: 'trophy', file: 'trophy.svg', label: 'Trophy / Victory', category: 'progression' },
  'trophy': { name: 'trophy', file: 'trophy.svg', label: 'Trophy / Victory', category: 'progression' },

  '🥇': { name: 'trophy', file: 'trophy.svg', label: '1st Place Trophy', category: 'progression' },
  '🥈': { name: 'trophy', file: 'trophy.svg', label: '2nd Place Trophy', category: 'progression' },
  '🥉': { name: 'trophy', file: 'trophy.svg', label: '3rd Place Trophy', category: 'progression' },
  '🎖️': { name: 'trophy', file: 'trophy.svg', label: 'Medal', category: 'progression' },
  '🎖':  { name: 'trophy', file: 'trophy.svg', label: 'Medal', category: 'progression' },
  '🏅': { name: 'trophy', file: 'trophy.svg', label: 'Medal', category: 'progression' },

  '👑': { name: 'crown', file: 'crown.svg', label: 'Crown / Grandmaster Rank', category: 'progression' },
  'crown': { name: 'crown', file: 'crown.svg', label: 'Crown / Grandmaster Rank', category: 'progression' },

  '🔥': { name: 'fire', file: 'fire.svg', label: 'Study Streak', category: 'progression' },
  'fire': { name: 'fire', file: 'fire.svg', label: 'Study Streak', category: 'progression' },

  '🪙': { name: 'coin', file: 'coin.svg', label: 'Ancient Gold Coin', category: 'economy' },
  'coin': { name: 'coin', file: 'coin.svg', label: 'Ancient Gold Coin', category: 'economy' },

  '💰': { name: 'money-bag', file: 'money-bag.svg', label: 'Treasury / Pouch', category: 'economy' },
  'money-bag': { name: 'money-bag', file: 'money-bag.svg', label: 'Treasury / Pouch', category: 'economy' },

  '💎': { name: 'gem', file: 'gem.svg', label: 'Soul Gem', category: 'economy' },
  'gem': { name: 'gem', file: 'gem.svg', label: 'Soul Gem', category: 'economy' },

  '🎁': { name: 'gift', file: 'gift.svg', label: 'Gift / Reward Chest', category: 'economy' },
  'gift': { name: 'gift', file: 'gift.svg', label: 'Gift / Reward Chest', category: 'economy' },

  '🛒': { name: 'cart', file: 'cart.svg', label: 'Shop / Market', category: 'economy' },
  '🛍️': { name: 'cart', file: 'cart.svg', label: 'Shop / Market', category: 'economy' },
  '🛍':  { name: 'cart', file: 'cart.svg', label: 'Shop / Market', category: 'economy' },
  'cart': { name: 'cart', file: 'cart.svg', label: 'Shop / Market', category: 'economy' },

  '🔒': { name: 'lock', file: 'lock.svg', label: 'Locked Stage', category: 'status' },
  'lock': { name: 'lock', file: 'lock.svg', label: 'Locked Stage', category: 'status' },

  '🔓': { name: 'unlock', file: 'unlock.svg', label: 'Unlocked Stage', category: 'status' },
  'unlock': { name: 'unlock', file: 'unlock.svg', label: 'Unlocked Stage', category: 'status' },

  // ── PSYCHOLOGY & STUDY PATH ──
  '📜': { name: 'scroll', file: 'scroll.svg', label: 'Quests / Curriculum', category: 'study' },
  'scroll': { name: 'scroll', file: 'scroll.svg', label: 'Quests / Curriculum', category: 'study' },

  '📚': { name: 'books', file: 'books.svg', label: 'Psychology Tome Archives', category: 'study' },
  'books': { name: 'books', file: 'books.svg', label: 'Psychology Tome Archives', category: 'study' },

  '📖': { name: 'book', file: 'book.svg', label: 'Open Study Book', category: 'study' },
  'book': { name: 'book', file: 'book.svg', label: 'Open Study Book', category: 'study' },

  '🧠': { name: 'brain', file: 'brain.svg', label: 'Psychology / Cognitive Domain', category: 'study' },
  'brain': { name: 'brain', file: 'brain.svg', label: 'Psychology / Cognitive Domain', category: 'study' },

  '💡': { name: 'bulb', file: 'bulb.svg', label: 'Insight / Hint', category: 'study' },
  'bulb': { name: 'bulb', file: 'bulb.svg', label: 'Insight / Hint', category: 'study' },

  '🔮': { name: 'crystal-ball', file: 'crystal-ball.svg', label: 'Diagnostic Intuition', category: 'study' },
  'crystal-ball': { name: 'crystal-ball', file: 'crystal-ball.svg', label: 'Diagnostic Intuition', category: 'study' },

  '📊': { name: 'chart', file: 'chart.svg', label: 'Psychometrics / Assessment', category: 'study' },
  'chart': { name: 'chart', file: 'chart.svg', label: 'Psychometrics / Assessment', category: 'study' },

  '🧬': { name: 'potion', file: 'potion.svg', label: 'Biological Foundations', category: 'study' },
  '🩺': { name: 'brain', file: 'brain.svg', label: 'Clinical Psychology', category: 'study' },

  // ── WORLD & NATURE ──
  '🏰': { name: 'castle', file: 'castle.svg', label: 'Citadel / World Hub', category: 'world' },
  'castle': { name: 'castle', file: 'castle.svg', label: 'Citadel / World Hub', category: 'world' },

  '🏛️': { name: 'hall', file: 'hall.svg', label: 'Hall of Records', category: 'world' },
  '🏛':  { name: 'hall', file: 'hall.svg', label: 'Hall of Records', category: 'world' },
  'hall': { name: 'hall', file: 'hall.svg', label: 'Hall of Records', category: 'world' },

  '🎭': { name: 'mask', file: 'mask.svg', label: 'Theater / Persona Mask', category: 'world' },
  'mask': { name: 'mask', file: 'mask.svg', label: 'Theater / Persona Mask', category: 'world' },

  '🌱': { name: 'seedling', file: 'seedling.svg', label: 'Mind Garden', category: 'nature' },
  'seedling': { name: 'seedling', file: 'seedling.svg', label: 'Mind Garden', category: 'nature' },

  '🌻': { name: 'sunflower', file: 'sunflower.svg', label: 'Bloom / Sunflower', category: 'nature' },
  'sunflower': { name: 'sunflower', file: 'sunflower.svg', label: 'Bloom / Sunflower', category: 'nature' },

  '🌹': { name: 'rose', file: 'rose.svg', label: 'Rose of Insight', category: 'nature' },
  'rose': { name: 'rose', file: 'rose.svg', label: 'Rose of Insight', category: 'nature' },

  '🌸': { name: 'rose', file: 'rose.svg', label: 'Blossom', category: 'nature' },

  // ── USER, STATUS & NAVIGATION ──
  '👤': { name: 'person', file: 'person.svg', label: 'Scholar / Avatar', category: 'user' },
  'person': { name: 'person', file: 'person.svg', label: 'Scholar / Avatar', category: 'user' },

  '⚙️': { name: 'gear', file: 'gear.svg', label: 'Settings', category: 'system' },
  '⚙':  { name: 'gear', file: 'gear.svg', label: 'Settings', category: 'system' },
  'gear': { name: 'gear', file: 'gear.svg', label: 'Settings', category: 'system' },

  '⚠️': { name: 'warning', file: 'warning.svg', label: 'Warning', category: 'system' },
  'warning': { name: 'warning', file: 'warning.svg', label: 'Warning', category: 'system' },

  '✅': { name: 'check', file: 'check.svg', label: 'Completed', category: 'system' },
  '✔️': { name: 'check', file: 'check.svg', label: 'Completed', category: 'system' },
  '✓':  { name: 'check', file: 'check.svg', label: 'Completed', category: 'system' },
  'check': { name: 'check', file: 'check.svg', label: 'Completed', category: 'system' },

  '❌': { name: 'cross', file: 'cross.svg', label: 'Failed', category: 'system' },
  '✕':  { name: 'cross', file: 'cross.svg', label: 'Failed', category: 'system' },
  'cross': { name: 'cross', file: 'cross.svg', label: 'Failed', category: 'system' },

  '⏱️': { name: 'timer', file: 'timer.svg', label: 'Exam Timer', category: 'system' },
  '⏱':  { name: 'timer', file: 'timer.svg', label: 'Exam Timer', category: 'system' },
  '⏳': { name: 'timer', file: 'timer.svg', label: 'Time Elapsed', category: 'system' },
  'timer': { name: 'timer', file: 'timer.svg', label: 'Exam Timer', category: 'system' },

  '🎉': { name: 'sparkles', file: 'sparkles.svg', label: 'Celebration', category: 'system' },
  'ghost': { name: 'ghost', file: 'ghost.svg', label: 'Shadow Minion', category: 'combat' },
  '👻': { name: 'ghost', file: 'ghost.svg', label: 'Shadow Minion', category: 'combat' }
}

/**
 * Resolves a given Unicode emoji or semantic key to its Dotto asset definition.
 * @param {string} emojiOrKey 
 * @returns {object} { name, file, path, label, category }
 */
export function getEmojiDef(emojiOrKey) {
  if (!emojiOrKey) return null
  const clean = String(emojiOrKey).trim()
  const baseUrl = getDottoBaseUrl()
  
  // Exact match
  if (emojiMap[clean]) {
    const item = emojiMap[clean]
    return {
      ...item,
      path: baseUrl + item.file
    }
  }

  // Without variation selector \ufe0f
  const stripped = clean.replace(/\ufe0f/g, '')
  if (emojiMap[stripped]) {
    const item = emojiMap[stripped]
    return {
      ...item,
      path: baseUrl + item.file
    }
  }

  // Fallback to sparkles / arcane star if unknown
  return {
    name: 'sparkles',
    file: 'sparkles.svg',
    path: baseUrl + 'sparkles.svg',
    label: clean,
    category: 'fallback'
  }
}
