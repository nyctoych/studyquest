import necroProfileImg from '../assets/PROFILE-AVATAR/NecroProfile.png'
import meleeProfileImg from '../assets/PROFILE-AVATAR/MeleeProfile.png'
import archerProfileImg from '../assets/PROFILE-AVATAR/ArcherProfile.png'

export { necroProfileImg, meleeProfileImg, archerProfileImg }

/**
 * Normalizes any character class representation into 'MAGE' | 'MELEE' | 'RANGE'
 */
export function normalizeCharacterClass(val) {
  const s = String(val || '').trim().toUpperCase()
  if (s.includes('MELE') || s.includes('DUAL') || s === '2') return 'MELEE'
  if (s.includes('RANG') || s.includes('ARCH') || s === '3') return 'RANGE'
  return 'MAGE'
}

/**
 * Returns the official, non-changeable profile avatar image asset for the character class:
 * - MAGE  => NecroProfile.png
 * - MELEE => MeleeProfile.png
 * - RANGE => ArcherProfile.png
 */
export function getProfileAvatarByClass(classVal) {
  const norm = normalizeCharacterClass(classVal)
  if (norm === 'MELEE') return meleeProfileImg
  if (norm === 'RANGE') return archerProfileImg
  return necroProfileImg
}
