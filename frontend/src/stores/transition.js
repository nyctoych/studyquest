import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useTransitionStore = defineStore('transition', () => {
  const isTransitioning = ref(false)
  const isCovered = ref(false)
  const transitionType = ref('dissolve')
  const navigationLocked = ref(false)

  // Information about the travel destination
  const destination = ref({
    title: '',
    subtitle: '',
    icon: 'castle',
    themeColor: 'crimson'
  })

  // Destination metadata mapping
  const DESTINATION_META = {
    '/adventure/final-boss': {
      title: 'ENTERING THE BLACK ARCHIVE',
      subtitle: 'Beyond the Mask, a door that was never written...',
      icon: 'castle',
      themeColor: 'crimson',
      type: 'gate'
    },
    '/world': {
      title: 'RETURNING TO CITADEL',
      subtitle: 'The sanctuary realm awaits...',
      icon: 'castle',
      themeColor: 'gold',
      type: 'world-return'
    },
    '/dashboard': {
      title: 'RETURNING TO CITADEL',
      subtitle: 'The sanctuary realm awaits...',
      icon: 'castle',
      themeColor: 'gold',
      type: 'world-return'
    },
    '/adventure': {
      title: 'ENTERING MINDREALM ADVENTURE',
      subtitle: 'Journeying across the 12 psychological chapters...',
      icon: 'sword',
      themeColor: 'crimson',
      type: 'gate'
    },
    '/multiplayer': {
      title: 'ENTERING THE ARENA',
      subtitle: 'Preparing real-time 1v1 psychological duels...',
      icon: 'lightning',
      themeColor: 'gold',
      type: 'arena'
    },
    '/arena': {
      title: 'ENTERING THE ARENA',
      subtitle: 'Preparing real-time 1v1 psychological duels...',
      icon: 'lightning',
      themeColor: 'gold',
      type: 'arena'
    },
    '/matchmaking': {
      title: 'SEEKING CHALLENGERS',
      subtitle: 'Scanning the realm for board exam contenders...',
      icon: 'target',
      themeColor: 'crimson',
      type: 'arena'
    },
    '/quests': {
      title: 'OPENING CURRICULUM ARCHIVES',
      subtitle: 'Accessing psychology syllabus & learning paths...',
      icon: 'scroll',
      themeColor: 'gold',
      type: 'library'
    },
    '/study': {
      title: 'ENTERING STUDY HALL',
      subtitle: 'Mastering psychological concepts and theories...',
      icon: 'books',
      themeColor: 'violet',
      type: 'library'
    },
    '/studyhall': {
      title: 'ENTERING STUDY HALL',
      subtitle: 'The Grand Library of Psychiatric Knowledge...',
      icon: 'books',
      themeColor: 'violet',
      type: 'library'
    },
    '/studyhall/shelves': {
      title: 'ARCHIVE OF THE TWELVE VOLUMES',
      subtitle: 'Approaching the ancient grimoire shelves...',
      icon: 'books',
      themeColor: 'violet',
      type: 'library'
    },
    '/studyhall/reading-room': {
      title: 'READING ROOM & GRIMOIRE',
      subtitle: 'Opening psychiatric case records...',
      icon: 'book',
      themeColor: 'violet',
      type: 'library'
    },
    '/practice': {
      title: 'OPENING PRACTICE SANCTUM',
      subtitle: 'Adaptive drills and board exam review...',
      icon: 'target',
      themeColor: 'gold',
      type: 'library'
    },
    '/leaderboard': {
      title: 'ENTERING HALL OF RECORDS',
      subtitle: 'Chronicles of the realm’s highest scholars...',
      icon: 'trophy',
      themeColor: 'gold',
      type: 'records'
    },
    '/records': {
      title: 'ENTERING HALL OF RECORDS',
      subtitle: 'Chronicles of the realm’s highest scholars...',
      icon: 'trophy',
      themeColor: 'gold',
      type: 'records'
    },
    '/shop': {
      title: 'ENTERING MIDNIGHT MARKET',
      subtitle: 'Merchant stalls, avatars, and gothic wares...',
      icon: 'cart',
      themeColor: 'gold',
      type: 'market'
    },
    '/market': {
      title: 'ENTERING MIDNIGHT MARKET',
      subtitle: 'Merchant stalls, avatars, and gothic wares...',
      icon: 'cart',
      themeColor: 'gold',
      type: 'market'
    },
    '/character': {
      title: 'APPROACHING CHARACTER HALL',
      subtitle: 'Consulting scholar attributes and loadouts...',
      icon: 'person',
      themeColor: 'crimson',
      type: 'sanctuary'
    },
    '/profile': {
      title: 'APPROACHING SCHOLAR CHAMBER',
      subtitle: 'Reviewing personal mastery & achievements...',
      icon: 'person',
      themeColor: 'gold',
      type: 'sanctuary'
    },
    '/achievements': {
      title: 'ENTERING HALL OF HONORS',
      subtitle: 'Trophies and milestones of your psychological journey...',
      icon: 'trophy',
      themeColor: 'gold',
      type: 'records'
    },
    '/chronicle': {
      title: 'ENTERING CHRONICLE HALL',
      subtitle: 'Reviewing recent scholar deeds and realm activities...',
      icon: 'book',
      themeColor: 'cyan',
      type: 'records'
    },
    '/rewards': {
      title: 'APPROACHING TREASURY',
      subtitle: 'Claiming earned coins, streaks, and gifts...',
      icon: 'coin',
      themeColor: 'gold',
      type: 'market'
    },
    '/settings': {
      title: 'OPENING CONFIGURATION RUNE',
      subtitle: 'Sound, preferences, and display...',
      icon: 'gear',
      themeColor: 'stone',
      type: 'dissolve'
    }
  }

  function getMetaForPath(path) {
    if (!path) return null
    for (const [prefix, meta] of Object.entries(DESTINATION_META)) {
      if (path === prefix || path.startsWith(prefix + '/')) {
        return meta
      }
    }
    return {
      title: 'TRAVELING THE REALM',
      subtitle: 'Venturing forth...',
      icon: 'castle',
      themeColor: 'crimson',
      type: 'gate'
    }
  }

  // Active transition promise resolver
  let resolveTransition = null

  /**
   * Triggers an immersive travel transition to target route
   */
  async function triggerTravel(targetPath, router) {
    if (navigationLocked.value) return false
    navigationLocked.value = true

    const meta = getMetaForPath(targetPath)
    destination.value = meta
    transitionType.value = meta.type
    isTransitioning.value = true

    // Check for prefers-reduced-motion
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    const enterDuration = prefersReducedMotion ? 100 : 200
    const exitDuration = prefersReducedMotion ? 100 : 220

    // Step 1: Shutter / curtain closes over the screen
    await new Promise(resolve => setTimeout(resolve, enterDuration))
    isCovered.value = true

    // Step 2: Route changes while the screen is completely covered
    try {
      await router.push(targetPath)
    } catch (err) {
      console.warn('Navigation error in transition:', err)
    }

    // Step 3: Brief atmospheric hold while destination mounts and settles
    await new Promise(resolve => setTimeout(resolve, 80))

    // Step 4: Curtain opens to reveal destination
    isCovered.value = false
    await new Promise(resolve => setTimeout(resolve, exitDuration))

    isTransitioning.value = false
    navigationLocked.value = false
    return true
  }

  return {
    isTransitioning,
    isCovered,
    transitionType,
    destination,
    navigationLocked,
    getMetaForPath,
    triggerTravel
  }
})
