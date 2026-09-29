import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  // ── PUBLIC ROUTES ─────────────────────────────────────────
  { path: '/',         component: () => import('../views/TitleScreen.vue'),  meta: { guest: true } },
  { path: '/login',    component: () => import('../views/Login.vue'),         meta: { guest: true } },
  { path: '/register', component: () => import('../views/Register.vue'),      meta: { guest: true } },

  // ── ONBOARDING ────────────────────────────────────────────
  { path: '/tutorial', component: () => import('../views/Tutorial.vue'),      meta: { auth: true } },

  // ── GAME WORLD / DASHBOARD ─────────────────────────────────
  { path: '/world',      component: () => import('../views/WorldHub.vue'),    meta: { auth: true } },
  { path: '/dashboard',  component: () => import('../views/WorldHub.vue'),    meta: { auth: true } },

  // ── PREVIOUS SIDE PANEL MODULES (Boss Quest merged into Quests) ──
  { path: '/practice',     component: () => import('../views/Practice.vue'),     meta: { auth: true } },
  // ── QUESTS (Study Paths & Curriculum Guide) ───────────────
  { path: '/quests',              component: () => import('../views/Quests.vue'),       meta: { auth: true } },
  { path: '/quests/create',       component: () => import('../views/CreateQuest.vue'),  meta: { auth: true } },
  { path: '/quests/:id',          component: () => import('../views/QuestDetails.vue'), meta: { auth: true } },
  { path: '/quests/part/:partId', alias: ['/quests/part/:id'], component: () => import('../views/QuestDetails.vue'), meta: { auth: true } },

  // ── ADVENTURE (RPG Stage Map & Storyline Battles) ──────────
  { path: '/adventure',                           component: () => import('../views/AdventureMap.vue'),     meta: { auth: true } },
  { path: '/adventure/:chapterId',               component: () => import('../views/AdventureMap.vue'),     meta: { auth: true } },
  { path: '/adventure/:chapterId/part/:partNum', component: () => import('../views/AdventurePart.vue'),     meta: { auth: true } },
  { path: '/adventure/:chapterId/boss',          component: () => import('../views/AdventureBoss.vue'),     meta: { auth: true } },

  { path: '/multiplayer',  component: () => import('../views/Multiplayer.vue'),  meta: { auth: true } },
  { path: '/arena',        component: () => import('../views/Multiplayer.vue'),  meta: { auth: true } },
  { path: '/matchmaking',  component: () => import('../views/Matchmaking.vue'),  meta: { auth: true } },
  { path: '/battle/:roomId', component: () => import('../views/MultiplayerBattle.vue'), meta: { auth: true } },

  { path: '/leaderboard',  component: () => import('../views/Leaderboard.vue'),  meta: { auth: true } },
  { path: '/records',      component: () => import('../views/Leaderboard.vue'),  meta: { auth: true } },

  { path: '/shop',         component: () => import('../views/Shop.vue'),         meta: { auth: true } },
  { path: '/market',       component: () => import('../views/Market.vue'),       meta: { auth: true } },

  { path: '/rewards',      component: () => import('../views/Rewards.vue'),      meta: { auth: true } },
  { path: '/chronicle',    component: () => import('../views/ChronicleHall.vue'), meta: { auth: true } },
  { path: '/achievements', component: () => import('../views/Achievements.vue'), meta: { auth: true } },

  { path: '/profile',      component: () => import('../views/Profile.vue'),      meta: { auth: true } },
  { path: '/character',    component: () => import('../views/CharacterHall.vue'), meta: { auth: true } },

  { path: '/settings',     component: () => import('../views/Settings.vue'),     meta: { auth: true } },

  // ── STUDY PATH ────────────────────────────────────────────
  { path: '/study',                            component: () => import('../views/StudyPath.vue'),     meta: { auth: true } },
  { path: '/study/:domainId',                  component: () => import('../views/StudyDomain.vue'),   meta: { auth: true } },
  { path: '/study/:domainId/part/:partNum',    component: () => import('../views/StudyPart.vue'),     meta: { auth: true } },

  // ── SPECIAL EVENT (DAILY 140-QUESTION EXAMINATION) ────────
  { path: '/special-event',                         component: () => import('../views/SpecialEventLanding.vue'), meta: { auth: true } },
  { path: '/special-event/exam',                    component: () => import('../views/SpecialEventExam.vue'),    meta: { auth: true } },
  { path: '/special-event/results/:attemptId',      component: () => import('../views/SpecialEventResults.vue'), meta: { auth: true } },
  { path: '/special-event/history',                 component: () => import('../views/SpecialEventHistory.vue'), meta: { auth: true } },
  { path: '/special-event/admin',                   component: () => import('../views/SpecialEventAdmin.vue'),   meta: { auth: true } },

  // ── LEGACY BOSS REDIRECT (merged into Quests) ──────────────
  { path: '/boss',        redirect: '/quests' },
  { path: '/boss/:id',    redirect: '/quests' },

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

  // Try to get user if not loaded yet
  if (!auth.user) {
    await auth.fetchUser()
  }

  // Auth required → redirect to login
  if (to.meta.auth && !auth.isLoggedIn) {
    return '/login'
  }

  // Logged-in guest pages → redirect to world (or tutorial for new players)
  if (to.meta.guest && auth.isLoggedIn) {
    return auth.isNewPlayer ? '/tutorial' : '/world'
  }

  // New player check — redirect to tutorial except when already going there
  if (to.meta.auth && auth.isLoggedIn && auth.isNewPlayer && to.path !== '/tutorial') {
    return '/tutorial'
  }
})

export default router
