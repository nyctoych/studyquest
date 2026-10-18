import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from './auth'
import { useToastStore } from './toast'
import router from '../router'

export const TUTORIAL_MODULE_STEPS = [
  {
    step: 1,
    id: 'studyhall',
    name: 'Study Hall',
    route: '/studyhall',
    selector: '.entrance-herald, .btn-enter-library, .phase-1-library',
    fallbackSelector: '.library-entrance-stage',
    heading: 'Study Hall: Academic Learning Sanctuary',
    purpose: "The player's academic learning resource and source of knowledge for supported study and examination questions.",
    canDo: "Explore 12 foundational psychology subjects, browse DSM-5 psychopathology criteria, navigate chapters and parts, and use Text-to-Speech (Read Aloud) and fullscreen reading mode.",
    whatYouGain: "Deep conceptual mastery, structured review material, and core domain knowledge that powers questions in Test Your Knowledge and Adventure Gate.",
    practicalTip: "Review unfamiliar concepts and clinical criteria in Study Hall before attempting high-stakes assessments or challenging boss trials."
  },
  {
    step: 2,
    id: 'adventure',
    name: 'Adventure Gate',
    route: '/adventure',
    selector: '.map-viewport-stage, .stage-nodes-layer, .rpg-adventure-screen',
    fallbackSelector: '.map-landscape-wrapper',
    heading: 'Adventure Gate: Story Campaign & Combat Trials',
    purpose: "The main story-based learning and combat progression module across 12 immersive chapters.",
    canDo: "Battle through sequential chapter stages (1-1 to 1-10) by answering psychology questions, face unique chapter bosses, and challenge the final Creator encounter.",
    whatYouGain: "Story progression, unlockable chapter environments, valuable coins, EXP, and access to subsequent stages by demonstrating knowledge under combat pressure.",
    practicalTip: "Each completed part unlocks the next stage. Adventure HP persists between encounters, so manage your health and visit the Market for potions if wounded."
  },
  {
    step: 3,
    id: 'achievements',
    name: 'Achievement Hall',
    route: '/achievements',
    selector: '.overview-stats-grid, .ach-hero-header, .achievements-chamber',
    fallbackSelector: '.ach-hero-header',
    heading: 'Achievement Hall: Grand Pantheon of Trials',
    purpose: "Track and claim monumental achievements earned through all supported StudyQuest activities.",
    canDo: "Track your progress across tiered milestones in Adventure, Study Hall, Progression, and Multiplayer arenas, claim coin and EXP rewards, and share achievements.",
    whatYouGain: "Permanent milestones of honor, bonus currency and experience rewards, and prestigious shareable achievement posters.",
    practicalTip: "Check back often! Use the 'Claim All' button to collect rewards across all completed milestones with a single tap."
  },
  {
    step: 4,
    id: 'vault',
    name: 'Vault',
    route: '/vault',
    selector: '.vault-control-deck, .vault-hero-banner, .vault-content-container',
    fallbackSelector: '.vault-hero-banner',
    heading: 'The Vault: Personal Scholar Archive',
    purpose: "Your private repository of uploaded personal study notes, PDFs, DOCX files, and custom reviewers.",
    canDo: "Upload study materials, search and organize documents, and read them in a clean, high-contrast fullscreen reader surface.",
    whatYouGain: "A centralized, distraction-free study space for your personal academic notes and licensure prep documents.",
    practicalTip: "You can keep all your reviewer documents organized here and switch into clean fullscreen white-background reading mode for long study sessions."
  },
  {
    step: 5,
    id: 'hall-of-fame',
    name: 'Hall of Fame',
    route: '/hall-of-fame',
    selector: '.ceremonial-banner, #hof-verify-btn, .fame-cta-banner',
    fallbackSelector: '.fame-container',
    heading: 'Hall of Fame: Licensure Passers Honor Roll',
    purpose: "Celebrate verified StudyQuest scholars who have passed the official Philippine Psychometrician Board Examination.",
    canDo: "Browse the ceremonial announcement scroll of verified passers, view batch honors, and submit your passing verification claim.",
    whatYouGain: "Public realm recognition for conquering the real-world PRC Board Exam and inspiring future examinees.",
    practicalTip: "Submissions undergo verification before appearing on the public roll. You can choose whether to display your full name or anonymous handle."
  },
  {
    step: 6,
    id: 'shop',
    name: 'Market',
    route: '/shop',
    selector: '.category-tabs-strip, .market-npc-column, .gothic-shop-frame',
    fallbackSelector: '.market-stage-container',
    heading: 'The Market: Raven’s Emporium of Effects & Potions',
    purpose: "Browse and purchase in-game cosmetic combat effects, restorative Health Potions, and tactical Dodge Potions.",
    canDo: "Browse custom Attack Effects, Splash Effects, Health Potions, and Dodge Potions, preview animations with sound, and equip gear to your character.",
    whatYouGain: "Personalize your combat identity with epic spell slashes, restore 200 Adventure HP with Health Potions, and skip difficult battle questions with Dodge Potions.",
    practicalTip: "Health Potions restore Adventure HP up to your maximum HP, while Dodge Potions allow you to bypass perilous battle questions without taking damage."
  },
  {
    step: 7,
    id: 'leaderboard',
    name: 'Leaderboards',
    route: '/leaderboard',
    selector: '.hall-content-container, .ranking-selector, .hall-main-content',
    fallbackSelector: '.hall-content-container',
    heading: 'Leaderboards: Hall of Records',
    purpose: "Track competitive standings and compare your accomplishments against fellow Mindkeepers.",
    canDo: "Switch between Battle Rating (ELO) and Scholar Level rankings, search player handles, and inspect champion dossier cards.",
    whatYouGain: "Clear visibility into your competitive standing and academic benchmark rankings across the realm.",
    practicalTip: "Win matches in the Arena to climb the Battle Rating ladder, or complete daily study quests and battles to increase your Scholar Level."
  },
  {
    step: 8,
    id: 'special-event',
    name: 'Test Your Knowledge',
    route: '/special-event',
    selector: '.exam-hero-card, .hub-top-nav, .special-event-landing-page',
    fallbackSelector: '.exam-hub-container',
    heading: 'Test Your Knowledge: Mock Board Exam Simulator',
    purpose: "A rigorous, two-session mock Psychometrician Board Examination aligned with PRC blueprints and DSM-5 criteria.",
    canDo: "Simulate real exam conditions across 450 comprehensive questions spanning 6 Bloom’s Taxonomy cognitive levels, and review detailed answer breakdowns.",
    whatYouGain: "Diagnostic insight into your clinical cognitive strengths, cognitive-level mastery breakdown, and personalized reviewer recommendations.",
    practicalTip: "Take the mock exams after reviewing Study Hall chapters. Review missed questions in History to reinforce your weak areas."
  },
  {
    step: 9,
    id: 'multiplayer',
    name: 'Multiplayer',
    route: '/multiplayer',
    selector: '.locked-card-box, .arena-locked-screen, .hub-header, .action-cards-grid',
    fallbackSelector: '.multiplayer-hub',
    heading: 'Multiplayer: Board Exam PvP Arena',
    purpose: "Real-time competitive 1v1 knowledge duels against other psychology students across the nation.",
    canDo: "Engage in synchronized turn-based battle questions, test your recall speed under pressure, and climb the competitive ladder (unlocks at Level 15).",
    whatYouGain: "Competitive thrill, ELO rating gains, bonus coins, and arena combat achievements.",
    practicalTip: "The Arena unlocks at Level 15! Level up first by clearing Adventure Gate chapters and completing daily study quests."
  },
  {
    step: 10,
    id: 'settings',
    name: 'Settings',
    route: '/settings',
    selector: '.settings-layout, .settings-sections-card, .settings-page',
    fallbackSelector: '.settings-layout',
    heading: 'Settings: System Configuration',
    purpose: "Customize audio feedback, visual themes, study notifications, and security preferences.",
    canDo: "Toggle background music, combat sound effects, adjust master volume, switch between Day and Night modes, and replay this tutorial.",
    whatYouGain: "An optimized, comfortable gaming and studying environment tailored to your preferences.",
    practicalTip: "You can replay this tutorial at any time right here from the Settings menu or from your Profile!"
  },
  {
    step: 11,
    id: 'profile',
    name: 'Profile',
    route: '/profile',
    selector: '.avatar-preview-card, .studio-grid, .profile-page',
    fallbackSelector: '.studio-grid',
    heading: 'Adventurer Profile: Scholar Identity & Records',
    purpose: "View your character class, level, stats, equipped cosmetics, and scholarly identity.",
    canDo: "Inspect your Level, XP, Coin Treasury, Battle Rating, chosen Title, and navigate to Character Hall for cosmetic equipment.",
    whatYouGain: "A complete overview of your character's progression and personal achievements across StudyQuest.",
    practicalTip: "As you level up and achieve milestones, you can earn new Titles to proudly display beneath your adventurer handle."
  }
]

export const useTutorialStore = defineStore('tutorial', () => {
  const auth = useAuthStore()
  const toast = useToastStore()

  const isActive = ref(false) // Guided tour overlay is active
  const phase = ref('battlefield') // 'intro' | 'battlefield' | 'tour' | 'completed'
  const currentStep = ref(1) // 1..11 for tour
  const battlefieldCompleted = ref(false)
  const isCompleted = ref(false)
  const isSkipped = ref(false)
  const lastCompletedStep = ref(0)
  const loading = ref(false)
  const showSkipConfirm = ref(false)
  const showCompletionModal = ref(false)

  const steps = TUTORIAL_MODULE_STEPS

  const currentStepData = computed(() => {
    return steps.find(s => s.step === currentStep.value) || steps[0]
  })

  const totalSteps = computed(() => steps.length)

  // Status fetch from backend
  async function fetchStatus() {
    if (!auth.isLoggedIn) return null
    loading.value = true
    try {
      const res = await api.get('/tutorial/status')
      if (res.data?.success && res.data?.data) {
        const d = res.data.data
        phase.value = d.tutorial_phase || 'battlefield'
        currentStep.value = Math.max(1, Number(d.tutorial_step || 1))
        battlefieldCompleted.value = Boolean(d.battlefield_completed)
        isCompleted.value = Boolean(d.tutorial_completed)
        isSkipped.value = Boolean(d.tutorial_skipped)
        lastCompletedStep.value = Number(d.last_completed_step || 0)

        // If user is in tour phase and tutorial is not completed/skipped, activate overlay
        if (phase.value === 'tour' && !isCompleted.value && !isSkipped.value) {
          isActive.value = true
        } else {
          isActive.value = false
        }
        return d
      }
    } catch (err) {
      console.warn('[TutorialStore] Error fetching status:', err)
    } finally {
      loading.value = false
    }
    return null
  }

  // Save progress
  async function saveProgress(newPhase, newStep, isBfCompleted = null) {
    if (!auth.isLoggedIn) return
    try {
      await api.post('/tutorial/progress', {
        phase: newPhase ?? phase.value,
        step: newStep ?? currentStep.value,
        battlefield_completed: isBfCompleted !== null ? (isBfCompleted ? 1 : 0) : (battlefieldCompleted.value ? 1 : 0),
        last_completed_step: Math.max(lastCompletedStep.value, newStep ?? currentStep.value)
      })
    } catch (err) {
      console.warn('[TutorialStore] Error saving progress:', err)
    }
  }

  // Complete Battlefield phase and start Phase 2 Guided Tour
  async function completeBattlefieldPhase() {
    battlefieldCompleted.value = true
    phase.value = 'tour'
    currentStep.value = 1
    isActive.value = true
    await saveProgress('tour', 1, 1)
    await router.push(steps[0].route)
  }

  // Navigation: Next Step in Module Tour
  async function nextStep() {
    if (currentStep.value < totalSteps.value) {
      currentStep.value++
      lastCompletedStep.value = Math.max(lastCompletedStep.value, currentStep.value)
      await saveProgress('tour', currentStep.value)
      const nextModule = currentStepData.value
      if (router.currentRoute.value.path !== nextModule.route) {
        await router.push(nextModule.route)
      }
    } else {
      // Reached the end of Step 11 (Profile) → show completion modal
      showCompletionModal.value = true
    }
  }

  // Navigation: Previous Step in Module Tour
  async function prevStep() {
    if (currentStep.value > 1) {
      currentStep.value--
      await saveProgress('tour', currentStep.value)
      const prevModule = currentStepData.value
      if (router.currentRoute.value.path !== prevModule.route) {
        await router.push(prevModule.route)
      }
    }
  }

  // Finish tour completely
  async function finishTutorial() {
    loading.value = true
    try {
      const res = await api.post('/tutorial/complete')
      if (res.data?.success) {
        isActive.value = false
        phase.value = 'completed'
        isCompleted.value = true
        showCompletionModal.value = false
        if (auth.user) {
          auth.user.is_new_player = 0
        }
        await auth.fetchUser()
        toast.success("🎉 You're ready, Mindkeeper! Welcome to Mindrealm!")
        await router.push('/world')
      }
    } catch (err) {
      console.error('[TutorialStore] Error completing tutorial:', err)
      isActive.value = false
      showCompletionModal.value = false
      await router.push('/world')
    } finally {
      loading.value = false
    }
  }

  // Skip tutorial
  async function skipTutorial() {
    loading.value = true
    try {
      await api.post('/tutorial/skip')
      isActive.value = false
      phase.value = 'completed'
      isSkipped.value = true
      isCompleted.value = true
      showSkipConfirm.value = false
      showCompletionModal.value = false
      if (auth.user) {
        auth.user.is_new_player = 0
      }
      await auth.fetchUser()
      toast.info('Tutorial skipped. You can replay it anytime from Settings or Profile.')
      await router.push('/world')
    } catch (err) {
      console.error('[TutorialStore] Error skipping tutorial:', err)
      isActive.value = false
      await router.push('/world')
    } finally {
      loading.value = false
    }
  }

  // Replay tutorial from Settings / Profile
  async function replayTutorial() {
    loading.value = true
    try {
      await api.post('/tutorial/reset')
      phase.value = 'battlefield'
      currentStep.value = 0
      battlefieldCompleted.value = false
      isCompleted.value = false
      isSkipped.value = false
      isActive.value = false
      toast.info('Replaying StudyQuest tutorial...')
      await router.push('/tutorial')
    } catch (err) {
      console.error('[TutorialStore] Error resetting tutorial:', err)
      await router.push('/tutorial')
    } finally {
      loading.value = false
    }
  }

  return {
    isActive,
    phase,
    currentStep,
    battlefieldCompleted,
    isCompleted,
    isSkipped,
    lastCompletedStep,
    loading,
    showSkipConfirm,
    showCompletionModal,
    steps,
    currentStepData,
    totalSteps,
    fetchStatus,
    saveProgress,
    completeBattlefieldPhase,
    nextStep,
    prevStep,
    finishTutorial,
    skipTutorial,
    replayTutorial
  }
})
