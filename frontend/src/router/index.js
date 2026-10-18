import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTutorialStore } from '../stores/tutorial'

const routes = [
  // ── PUBLIC ROUTES ─────────────────────────────────────────
  { path: '/Home', alias: ['/home'], component: () => import('../views/Home.vue') },
  { path: '/', component: () => import('../views/TitleScreen.vue'), meta: { guest: true } },
  { path: '/login', component: () => import('../views/Login.vue'), meta: { guest: true } },
  { path: '/register', component: () => import('../views/Register.vue'), meta: { guest: true } },
  { path: '/terms', component: () => import('../views/TermsOfService.vue') },
  { path: '/privacy', component: () => import('../views/PrivacyPolicy.vue') },
  { path: '/share/achievement/:token', component: () => import('../views/PublicAchievementShare.vue'), meta: { guest: true } },

  // ── ONBOARDING & CHARACTER SELECTION ──────────────────────
  { path: '/character-selection', component: () => import('../views/CharacterSelection.vue'), meta: { auth: true } },
  { path: '/tutorial', component: () => import('../views/Tutorial.vue'), meta: { auth: true } },

  // ── GAME WORLD / DASHBOARD ─────────────────────────────────
  { path: '/world', component: () => import('../views/WorldHub.vue'), meta: { auth: true } },
  { path: '/dashboard', component: () => import('../views/WorldHub.vue'), meta: { auth: true } },

  // ── PREVIOUS SIDE PANEL MODULES (Boss Quest merged into Quests) ──
  { path: '/practice', component: () => import('../views/Practice.vue'), meta: { auth: true } },
  // ── QUESTS (Study Paths & Curriculum Guide) ───────────────
  { path: '/quests', component: () => import('../views/Quests.vue'), meta: { auth: true } },
  { path: '/quests/create', component: () => import('../views/CreateQuest.vue'), meta: { auth: true } },
  { path: '/quests/:id', component: () => import('../views/QuestDetails.vue'), meta: { auth: true } },
  { path: '/quests/part/:partId', alias: ['/quests/part/:id'], component: () => import('../views/QuestDetails.vue'), meta: { auth: true } },

  // ── ADVENTURE (RPG Stage Map & Storyline Battles) ──────────
  { path: '/adventure', component: () => import('../views/AdventureMap.vue'), meta: { auth: true } },
  { path: '/adventure/final-boss', component: () => import('../views/FinalBoss.vue'), meta: { auth: true } },
  { path: '/adventure/:chapterId', component: () => import('../views/AdventureMap.vue'), meta: { auth: true } },
  { path: '/adventure/:chapterId/part/:partNum', component: () => import('../views/AdventurePart.vue'), meta: { auth: true } },
  { path: '/adventure/:chapterId/boss', component: () => import('../views/AdventureBoss.vue'), meta: { auth: true } },
  { path: '/adventure/:chapterId/final-boss', component: () => import('../views/FinalBoss.vue'), meta: { auth: true } },

  { path: '/multiplayer', component: () => import('../views/Multiplayer.vue'), meta: { auth: true } },
  { path: '/arena', component: () => import('../views/Multiplayer.vue'), meta: { auth: true } },
  { path: '/matchmaking', component: () => import('../views/Matchmaking.vue'), meta: { auth: true } },
  { path: '/battle/:roomId', component: () => import('../views/MultiplayerBattle.vue'), meta: { auth: true } },

  { path: '/leaderboard', component: () => import('../views/Leaderboard.vue'), meta: { auth: true } },
  { path: '/records', component: () => import('../views/Leaderboard.vue'), meta: { auth: true } },

  { path: '/shop', component: () => import('../views/Shop.vue'), meta: { auth: true } },
  { path: '/market', component: () => import('../views/Shop.vue'), meta: { auth: true } },

  { path: '/rewards', component: () => import('../views/Rewards.vue'), meta: { auth: true } },
  { path: '/chronicle', redirect: '/world' },
  { path: '/achievements', component: () => import('../views/Achievements.vue'), meta: { auth: true } },
  { path: '/hall-of-fame', component: () => import('../views/HallOfFame.vue'), meta: { auth: true } },
  { path: '/fame', redirect: '/hall-of-fame' },

  { path: '/profile', component: () => import('../views/Profile.vue'), meta: { auth: true } },
  { path: '/character', component: () => import('../views/CharacterHall.vue'), meta: { auth: true } },

  { path: '/settings', component: () => import('../views/Settings.vue'), meta: { auth: true } },

  // ── VAULT (PERSONAL REVIEWER ARCHIVE) ─────────────────────
  { path: '/vault', component: () => import('../views/Vault.vue'), meta: { auth: true } },

  // ── STUDY HALL (MULTI-PHASE IMMERSIVE LIBRARY) ───────────
  { path: '/studyhall', component: () => import('../views/studyhall/StudyHallLibrary.vue'), meta: { auth: true } },
  { path: '/studyhall/shelves', component: () => import('../views/studyhall/StudyHallShelves.vue'), meta: { auth: true } },
  { path: '/studyhall/reading-room', component: () => import('../views/studyhall/StudyHallReadingRoom.vue'), props: true, meta: { auth: true } },
  { path: '/studyhall/chapter/:chapterId', component: () => import('../views/studyhall/StudyHallReadingRoom.vue'), props: true, meta: { auth: true } },
  { path: '/studyhall/chapter/:chapterId/part/:partId', component: () => import('../views/QuestDetails.vue'), props: true, meta: { auth: true } },
  { path: '/study', redirect: '/studyhall' },
  { path: '/study/:domainId', redirect: to => `/studyhall/chapter/${to.params.domainId}` },
  { path: '/study/:domainId/part/:partNum', redirect: to => `/studyhall/chapter/${to.params.domainId}/part/${to.params.partNum}` },

  // ── SPECIAL EVENT (DAILY 140-QUESTION EXAMINATION) ────────
  { path: '/special-event', component: () => import('../views/SpecialEventLanding.vue'), meta: { auth: true } },
  { path: '/special-event/exam', component: () => import('../views/SpecialEventExam.vue'), meta: { auth: true } },
  { path: '/special-event/results/:attemptId', component: () => import('../views/SpecialEventResults.vue'), meta: { auth: true } },
  { path: '/special-event/history', component: () => import('../views/SpecialEventHistory.vue'), meta: { auth: true } },
  { path: '/special-event/admin', component: () => import('../views/SpecialEventAdmin.vue'), meta: { auth: true } },

  // ── LEGACY BOSS REDIRECT (merged into Quests) ──────────────
  { path: '/boss', redirect: '/quests' },
  { path: '/boss/:id', redirect: '/quests' },

  // ── CATCH-ALL ─────────────────────────────────────────────
  { path: '/:pathMatch(.*)*', redirect: '/world' },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// Navigation guard
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Restore session from server before checking route permissions
  if (!auth.initialized) {
    await auth.init()
  }

  // Auth required → redirect to login
  if (to.meta.auth && !auth.isLoggedIn) {
    return '/login'
  }

  // Logged-in guest pages → redirect
  if (to.meta.guest && auth.isLoggedIn) {
    const hasChar = !!(auth.user?.selected_character_id)
    if (!hasChar) return '/character-selection'
    return auth.isNewPlayer ? '/tutorial' : '/world'
  }

  // Mandatory character selection before tutorial (Section 5)
  if (to.meta.auth && auth.isLoggedIn) {
    const hasChar = !!(auth.user?.selected_character_id)
    if (!hasChar && to.path !== '/character-selection') {
      return '/character-selection'
    }

    const tutorialStore = useTutorialStore()

    // If tutorial is currently in tour phase, allow navigation to the guided module tour routes
    const isTourActive = tutorialStore.phase === 'tour' && !tutorialStore.isCompleted
    const allowedTourRoutes = [
      '/studyhall', '/studyhall/shelves', '/studyhall/reading-room',
      '/adventure',
      '/achievements',
      '/vault',
      '/hall-of-fame',
      '/shop',
      '/leaderboard', '/records',
      '/special-event',
      '/multiplayer',
      '/settings',
      '/profile'
    ]

    if (auth.isNewPlayer && hasChar && to.path !== '/tutorial' && to.path !== '/character-selection') {
      if (!isTourActive || !allowedTourRoutes.some(r => to.path === r || to.path.startsWith(r))) {
        return '/tutorial'
      }
    }

    // Arena access requirement: Level 15 (Permit preview during tutorial tour Step 9)
    const isArenaRoute = ['/multiplayer', '/arena', '/matchmaking'].includes(to.path) || to.path.startsWith('/battle/')
    if (isArenaRoute) {
      const isMultiplayerTutorialPreview = isTourActive && to.path === '/multiplayer' && tutorialStore.currentStep === 9
      const userLevel = Number(auth.user?.level || 1)
      if (userLevel < 15 && !isMultiplayerTutorialPreview) {
        const toast = useToastStore()
        toast.warning(`Colosseum Locked: You must reach Level 15 to enter the Arena. (Your Level: ${userLevel})`)
        return '/world'
      }
    }
  }
})

export default router
