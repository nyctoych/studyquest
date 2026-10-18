<template>
  <div id="app">
    <!-- Loading Screen (shown once per session) -->
    <LoadingScreen v-if="showLoading" @done="onLoadingDone" />

    <!-- Main App (shown after loading) -->
    <template v-else>
      <!-- Game HUD overlay (authenticated gameplay routes only, hidden in serious mode) -->
      <GameHUD v-if="showHUD" />

      <!-- Serious Mode Return Button (Replaces navigation header in serious mode) -->
      <button
        v-if="isSeriousModeActive && authStore.isLoggedIn && route.path !== '/studyhall/shelves' && !route.path.startsWith('/special-event/exam')"
        type="button"
        class="serious-return-btn animate-fade-in"
        :class="{ 'is-morning': gameStore.isMorning }"
        @click="handleSeriousReturn"
        :title="returnButtonLabel === 'Back' ? 'Back' : 'Return to Realm'"
        id="serious-mode-return-btn"
      >
        <span class="serious-return-arrow">◀</span>
        <span class="serious-return-label">{{ returnButtonLabel }}</span>
      </button>

      <!-- Serious Mode Top Actions (Theme Toggle & Music Mute Toggle) -->
      <div
        v-if="isSeriousModeActive && authStore.isLoggedIn && route.path !== '/studyhall/shelves' && !route.path.startsWith('/special-event/exam')"
        class="serious-nav-actions animate-fade-in"
      >
        <!-- Serious Mode Audio Volume Control (Increase/Decrease/Slider/Mute) -->
        <AudioVolumeControl id="serious-mode-volume-widget" />

        <!-- Serious Mode Theme Toggle -->
        <button
          type="button"
          class="serious-theme-toggle-btn"
          :class="{ 'is-morning': gameStore.isMorning }"
          @click="gameStore.toggleTheme()"
          :title="gameStore.isMorning ? 'Switch to Night Mode (☾)' : 'Switch to Morning Mode (☀)'"
          id="serious-mode-theme-toggle-btn"
        >
          <span class="serious-theme-icon">{{ gameStore.isMorning ? '☀' : '☾' }}</span>
          <span class="serious-theme-label">{{ gameStore.isMorning ? 'DAY' : 'NIGHT' }}</span>
        </button>
      </div>

      <!-- Page content wrapper -->
      <main
        class="app-page-wrapper"
        :class="{
          'has-hud': showHUD,
          'full-screen-view': isFullScreenView
        }"
      >
        <router-view v-slot="{ Component, route }">
          <transition :name="getTransition(route)" mode="out-in">
            <component :is="Component" :key="getViewKey(route)" />
          </transition>
        </router-view>
      </main>
    </template>

    <!-- Global Toast Notifications -->
    <ToastContainer />

    <!-- Milestone Attribute Allocation Modal -->
    <AttributeAllocationModal />

    <!-- Game World Module Travel Transition Overlay -->
    <GameTransition />

    <!-- Post-Login Immersive Experience Welcome Modal -->
    <ImmersiveWelcomeModal />

    <!-- Serious Mode Welcome Notification Modal -->
    <SeriousModeModal />

    <!-- Global Tutorial Onboarding Spotlight Overlay -->
    <TutorialSpotlightOverlay />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoadingScreen from './views/LoadingScreen.vue'
import GameHUD from './components/GameHUD.vue'
import ToastContainer from './components/ToastContainer.vue'
import GameTransition from './components/transitions/GameTransition.vue'
import AttributeAllocationModal from './components/character/AttributeAllocationModal.vue'
import ImmersiveWelcomeModal from './components/modals/ImmersiveWelcomeModal.vue'
import SeriousModeModal from './components/modals/SeriousModeModal.vue'
import TutorialSpotlightOverlay from './components/tutorial/TutorialSpotlightOverlay.vue'
import AudioVolumeControl from './components/ui/AudioVolumeControl.vue'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'
import { useAttributeAllocationStore } from './stores/attributeAllocation'
import { useWelcomeModalStore } from './stores/welcomeModal'
import { useSeriousModeStore } from './stores/seriousMode'
import { useTransitionStore } from './stores/transition'
import { useTutorialStore } from './stores/tutorial'
import { bgmService } from './services/bgmService'
import { studyHallTtsService } from './services/studyHallTtsService'
import { battleAudio } from './services/battleAudio'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const gameStore = useGameStore()
const attributeAllocationStore = useAttributeAllocationStore()
const welcomeModalStore = useWelcomeModalStore()
const seriousModeStore = useSeriousModeStore()
const transitionStore = useTransitionStore()
const tutorialStore = useTutorialStore()

// Watch routes to manage Serious Mode lifecycle and clean up background speech
watch(
  () => route.path,
  (newPath, oldPath) => {
    seriousModeStore.handleRouteChange(newPath, oldPath)
    if (oldPath && oldPath !== newPath && studyHallTtsService.isPlaying.value) {
      studyHallTtsService.stop()
    }
  },
  { immediate: true }
)

// Watch for pending post-login immersive experience welcome modal

// Watch for pending post-login immersive experience welcome modal
watch(
  () => [authStore.isLoggedIn, route.path],
  ([loggedIn, path]) => {
    // Only open if logged in and navigating into the game realm (not on guest/login/register pages)
    if (loggedIn && !['/', '/login', '/register'].includes(path)) {
      welcomeModalStore.checkAndOpen()
    }
  },
  { immediate: true }
)

// Watch for pending attribute milestones on level up or navigation
watch(
  () => [authStore.level, route.path, authStore.isLoggedIn],
  async ([lvl, path, loggedIn]) => {
    if (loggedIn) {
      if (!attributeAllocationStore.scaledStats) {
        await attributeAllocationStore.fetchAllocations(authStore.user?.selected_character_id)
      }
      attributeAllocationStore.checkAndOpenIfPending(path)
    }
  },
  { immediate: true }
)

// Loading screen shown once per browser session
const showLoading = ref(!sessionStorage.getItem('sq_loaded'))

// Serious Mode state & navigation
const isSeriousModeActive = computed(() => {
  return seriousModeStore.isSeriousMode || seriousModeStore.isSeriousRoute(route.path)
})

const isSeriousSubRoute = computed(() => {
  const p = route.path
  return (
    p.startsWith('/studyhall/chapter') ||
    p.startsWith('/studyhall/reading') ||
    p.startsWith('/studyhall/shelves') ||
    p.startsWith('/quests/part') ||
    p.startsWith('/quests/') ||
    p.startsWith('/special-event/history') ||
    p.startsWith('/special-event/admin') ||
    p.startsWith('/special-event/results')
  )
})

const returnButtonLabel = computed(() => {
  return isSeriousSubRoute.value ? 'Back' : 'RETURN TO REALM'
})

function handleSeriousReturn() {
  // If at top-level module (Study Hall entrance, Vault, Special Event), always return directly to the Realm (/world)
  if (!isSeriousSubRoute.value) {
    transitionStore.triggerTravel('/world', router)
    return
  }

  // If inside a specific part, return to its chapter
  if (route.params.chapterId && route.path.includes('/part/')) {
    transitionStore.triggerTravel(`/studyhall/chapter/${route.params.chapterId}`, router)
    return
  }

  // If inside a chapter or legacy quest, return to the chapter shelves
  if (
    route.path.startsWith('/studyhall/chapter') || 
    route.path.startsWith('/studyhall/reading') ||
    route.path.startsWith('/quests/part') ||
    route.path.startsWith('/quests/')
  ) {
    transitionStore.triggerTravel('/studyhall/shelves', router)
  } else if (route.path === '/studyhall/shelves') {
    transitionStore.triggerTravel('/studyhall', router)
  } else if (
    route.path.startsWith('/special-event/history') ||
    route.path.startsWith('/special-event/admin') ||
    route.path.startsWith('/special-event/results')
  ) {
    transitionStore.triggerTravel('/special-event', router)
  } else {
    transitionStore.triggerTravel('/world', router)
  }
}

// Routes where HUD should NOT appear (plus Serious Mode modules: Study Hall, Vault, Daily Trial)
const NO_HUD_ROUTES = ['/', '/login', '/register', '/tutorial', '/character-selection']
const showHUD = computed(() =>
  authStore.isLoggedIn &&
  !NO_HUD_ROUTES.includes(route.path) &&
  !route.path.startsWith('/battle/') &&
  !route.path.startsWith('/special-event/exam') &&
  !route.path.startsWith('/share/') &&
  !route.path.startsWith('/quests/part') &&
  !route.path.startsWith('/studyhall') &&
  !isSeriousModeActive.value
)

// Full-screen views that manage their own full-bleed canvas or custom internal layouts
const FULL_SCREEN_PREFIXES = ['/world', '/dashboard', '/battle', '/special-event', '/studyhall', '/achievements', '/share', '/vault', '/quests/part', '/quests/', '/adventure', '/shop', '/market']
const isFullScreenView = computed(() => {
  const p = route.path
  if (NO_HUD_ROUTES.includes(p)) return true
  if (FULL_SCREEN_PREFIXES.some(prefix => p.startsWith(prefix))) return true
  if (p.includes('final-boss')) return true
  if (p.startsWith('/adventure')) return true
  if (p === '/shop' || p === '/market') return true
  return false
})

function onLoadingDone() {
  sessionStorage.setItem('sq_loaded', '1')
  showLoading.value = false
}

// Different transitions for different route types
function getTransition(r) {
  if (r?.path?.startsWith('/adventure') || r?.path?.startsWith('/battle')) {
    return 'battle'
  }
  return 'page'
}

function getViewKey(r) {
  const p = r?.path || ''
  // For the adventure chapter map (/adventure and /adventure/:chapterId), keep the same component instance
  // so switching chapters is fast, seamless, and does not flash or remount Chapter 1
  if (p === '/adventure' || (p.startsWith('/adventure/') && !p.includes('/part/') && !p.includes('/boss'))) {
    return '/adventure'
  }
  return p
}

// Watch routes to manage background music state across the app
watch(() => route.path, (p) => {
  bgmService.updateRoute(p)
}, { immediate: true })

// Sync game store theme context (light mode gatekeeper) with active route, auth state, and new player status
watch(
  [() => route.path, () => authStore.isLoggedIn, () => authStore.isNewPlayer],
  ([newPath, loggedIn, isNew]) => {
    gameStore.syncThemeContext(newPath, loggedIn, isNew)
  },
  { immediate: true }
)

onMounted(async () => {
  battleAudio.initGlobalClickListener()
  bgmService.init(gameStore.musicEnabled, route.path)
  await authStore.init()
  if (authStore.isLoggedIn) {
    await tutorialStore.fetchStatus()
  }
})
</script>

<style>
@import './assets/css/fonts.css';
@import './assets/css/gothic-pixel.css';
@import './assets/css/fire-sparkles.css';
@import './assets/css/environments.css';

/* ── GLOBAL RESET ──────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  /* HUD Height token */
  --hud-height:        60px;

  /* Gothic Void — core color system (boss-art-inspired) */
  --color-bg:          var(--gothic-void);
  --color-surface:     var(--gothic-surface);
  --color-surface2:    var(--gothic-raised);
  --color-border:      var(--gothic-border);
  --color-text:        var(--text-primary);
  --color-text-h:      var(--text-heading);
  --color-muted:       var(--text-muted);

  /* Legacy brand aliases → gothic equivalents */
  --quest-purple:      var(--arcane-mid);
  --quest-violet:      var(--arcane);
  --quest-light:       var(--bone-white);
  --quest-dark:        var(--arcane-deep);
  --gold:              var(--aged-gold);
  --gold-light:        var(--aged-gold-bright);
  --gold-dark:         var(--aged-gold-dim);
  --danger:            var(--crimson-bright);
  --success:           #2a6a3a;
  --cyan:              var(--shadow-teal-bright);

  /* Legacy glow aliases */
  --glow-purple:       var(--glow-crimson);
  --glow-danger:       var(--glow-crimson);

  /* Fonts — gothic system */
  --font-display:      var(--font-pixel);
  --font-body:         var(--font-gothic);

  font-family: var(--font-gothic);
  font-size: 16px;
  color: var(--text-primary);
  background: var(--gothic-void);
  -webkit-font-smoothing: antialiased;
  color-scheme: dark;
}

@media (max-width: 650px) {
  :root {
    --hud-height: 54px;
  }
}

html, body {
  min-height: 100vh;
  width: 100%;
  overflow-x: hidden;
  background: var(--gothic-void);
}

#app {
  min-height: 100vh;
  width: 100%;
  position: relative;
  background: var(--gothic-void);
}

/* Force dark gothic void background across adventure & battlefield views */
html:has(.adv-part),
body:has(.adv-part),
#app:has(.adv-part),
html:has(.adv-boss-screen),
body:has(.adv-boss-screen),
#app:has(.adv-boss-screen),
html:has(.rpg-adventure-screen),
body:has(.rpg-adventure-screen),
#app:has(.rpg-adventure-screen),
html:has(.market-view-root),
body:has(.market-view-root),
#app:has(.market-view-root),
html:has(.study-part),
body:has(.study-part),
#app:has(.study-part) {
  background: #050608 !important;
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

/* ── APP PAGE WRAPPER (Top margin & responsive spacing for modules) ── */
.app-page-wrapper {
  width: 100%;
  min-height: 100vh;
  box-sizing: border-box;
}

.app-page-wrapper.full-screen-view,
.app-page-wrapper.full-screen-view.has-hud,
.app-page-wrapper:has(.adv-part),
.app-page-wrapper:has(.adv-boss-screen),
.app-page-wrapper:has(.rpg-adventure-screen),
.app-page-wrapper:has(.market-view-root),
.app-page-wrapper:has(.study-part) {
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
  padding-bottom: 0 !important;
  margin-bottom: 0 !important;
  width: 100% !important;
  min-height: 100vh !important;
  min-height: 100dvh !important;
}

.app-page-wrapper.has-hud:not(.full-screen-view) {
  padding-top: calc(var(--hud-height) + 1.25rem);
  padding-bottom: 3.5rem;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
  max-width: 1380px;
  margin: 0 auto;
}

@media (max-width: 768px) {
  .app-page-wrapper.has-hud:not(.full-screen-view) {
    padding-top: calc(var(--hud-height) + 0.85rem);
    padding-left: 0.85rem;
    padding-right: 0.85rem;
    padding-bottom: 2rem;
  }
}

/* ── TYPOGRAPHY (Gothic) ───────────────────────────────────── */
h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-pixel);
  color: var(--text-heading);
  line-height: 1.3;
}
p, span, li, td, th {
  font-family: var(--font-gothic);
}
/* Pixel headings — apply Press Start 2P with careful sizing */
h1 { font-size: var(--px-xl); letter-spacing: 0.05em; }
h2 { font-size: var(--px-lg); }
h3 { font-size: var(--px-md); }
h4 { font-size: var(--px-base); }

/* ── BUTTON SYSTEM (Gothic Pixel) ──────────────────────────── */
/* Legacy .btn classes now map to gothic pixel buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  cursor: pointer;
  border: none;
  outline: none;
  text-decoration: none;
  letter-spacing: 0.06em;
  line-height: 1.6;
  transition: background 0.1s, box-shadow 0.1s, transform 0.05s;
  image-rendering: pixelated;
}
.btn:disabled { opacity: 0.35; cursor: not-allowed; }

/* Primary (crimson — battle/action) */
.btn-primary {
  background: var(--crimson-deep);
  color: var(--bone-white);
  border: 2px solid var(--crimson);
  box-shadow: 2px 2px 0 var(--crimson-void);
}
.btn-primary:hover:not(:disabled) {
  background: var(--crimson);
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 3px 3px 0 var(--crimson-void), var(--glow-crimson);
}
.btn-primary:active:not(:disabled) { transform: translateY(1px); box-shadow: 1px 1px 0 var(--crimson-void); }

/* Gold (rewards/confirm) */
.btn-gold {
  background: var(--gothic-surface);
  color: var(--aged-gold-bright);
  border: 2px solid var(--aged-gold);
  box-shadow: 2px 2px 0 #0a0800;
}
.btn-gold:hover:not(:disabled) {
  background: var(--aged-gold-dim);
  color: var(--aged-gold-shine);
  transform: translateY(-1px);
  box-shadow: 3px 3px 0 #0a0800, var(--glow-gold);
}

/* Ghost */
.btn-ghost {
  background: transparent;
  color: var(--text-muted);
  border: 1px solid var(--gothic-border-lit);
}
.btn-ghost:hover:not(:disabled) {
  background: rgba(255,255,255,0.04);
  color: var(--text-primary);
  border-color: var(--bone-dim);
}

/* Danger */
.btn-danger {
  background: var(--crimson-void);
  color: #ff7070;
  border: 2px solid var(--blood-red);
  box-shadow: 2px 2px 0 rgba(0,0,0,0.5);
}
.btn-danger:hover:not(:disabled) {
  background: var(--crimson-deep);
  box-shadow: var(--glow-crimson);
}

/* Sizes */
.btn-lg  { padding: 1rem 2rem;    font-size: var(--px-sm); }
.btn-sm  { padding: 0.4rem 0.8rem; font-size: var(--px-xs); }
.btn-icon { padding: 0.6rem; }

/* ── GOTHIC PANEL (replaces .glass-card) ───────────────────── */
.glass-card {
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border);
}
.glass-card-glow {
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  box-shadow: var(--shadow-gothic);
}

/* ── HP BARS (pixel-segmented style) ──────────────────────── */
.hp-bar-wrap {
  width: 100%;
  height: 14px;
  background: #0a0808;
  border: 2px solid var(--gothic-border);
  overflow: hidden;
  background-image: repeating-linear-gradient(
    90deg, transparent, transparent 9px,
    rgba(0,0,0,0.4) 9px, rgba(0,0,0,0.4) 10px
  );
}
.hp-bar-fill {
  height: 100%;
  transition: width 0.4s steps(20, end);
  position: relative;
  background: #2a6a3a;
}
.hp-bar-fill::after {
  content: '';
  position: absolute;
  inset: 2px;
  background: linear-gradient(90deg, #3a8a4a, #5aaa6a);
}
.hp-bar-fill.enemy {
  background: var(--crimson-deep);
}
.hp-bar-fill.enemy::after {
  background: linear-gradient(90deg, var(--crimson), var(--crimson-bright));
}
.hp-bar-fill.low {
  background: #3a2a08;
}
.hp-bar-fill.low::after {
  background: linear-gradient(90deg, var(--aged-gold-dim), var(--aged-gold));
}

/* ── XP BAR (thin pixel) ───────────────────────────────────── */
.xp-bar-wrap {
  width: 100%;
  height: 8px;
  background: #080808;
  border: 1px solid var(--gothic-border);
  overflow: hidden;
}
.xp-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--arcane-deep), var(--arcane-mid));
  transition: width 0.5s steps(15, end);
}

/* ── FORM ELEMENTS (gothic) ─────────────────────────────────── */
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label {
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  color: var(--text-muted);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #0d0f17;
  border: 1px solid var(--gothic-border-lit);
  color: var(--text-primary);
  font-family: var(--font-gothic);
  font-size: 1rem;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.form-input:focus {
  border-color: var(--bone-dim);
  box-shadow: 0 0 0 2px rgba(122,106,85,0.15);
}
.form-input::placeholder { color: var(--text-faded); font-style: italic; }

/* ── RARITY (gothic system) ─────────────────────────────────── */
.rarity-common    { color: var(--rarity-common); }
.rarity-rare      { color: var(--rarity-rare-t); }
.rarity-epic      { color: var(--rarity-epic-t); }
.rarity-legendary { color: var(--rarity-legendary-t); }

/* ── PAGE TRANSITIONS (gothic pixel) ───────────────────────── */
.page-enter-active { animation: pageEnterGothic 0.3s ease-out; }
.page-leave-active { animation: pageLeaveGothic 0.25s ease-in; }

.battle-enter-active { animation: battleEnterGothic 0.4s ease-out; }
.battle-leave-active { animation: battleLeaveGothic 0.3s ease-in; }
.battle-leave-to { opacity: 0; }

@keyframes pageEnterGothic {
  from { opacity: 0; transform: translateY(12px); }
  to   { opacity: 1; transform: translateY(0); }
}
@keyframes pageLeaveGothic {
  from { opacity: 1; }
  to   { opacity: 0; transform: translateY(-8px); }
}
@keyframes battleEnterGothic {
  from { opacity: 0; transform: scale(0.97); filter: brightness(0.5); }
  to   { opacity: 1; transform: scale(1); filter: brightness(1); }
}
@keyframes battleLeaveGothic {
  from { opacity: 1; transform: scale(1); }
  to   { opacity: 0; transform: scale(1.03); }
}

/* ── SCROLLBAR (gothic crimson) ─────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--gothic-void); }
::-webkit-scrollbar-thumb { background: var(--crimson-deep); border-radius: 0; }
::-webkit-scrollbar-thumb:hover { background: var(--crimson); }

/* ── UTILITY ────────────────────────────────────────────────── */
.text-gradient {
  background: linear-gradient(135deg, var(--bone-white), var(--aged-gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.glow-text { text-shadow: var(--glow-crimson); }

/* Legacy animate classes → gothic equivalents */
.animate-float       { animation: bossIdleFloat 3s ease-in-out infinite; }
.animate-pulse-glow  { animation: crimsonThrob 2s ease-in-out infinite; }
.animate-slide-up    { animation: slideUpGothic 0.35s ease-out; }
.animate-slide-down  { animation: slideDownGothic 0.35s ease-out; }
.animate-scale-up    { animation: scaleUpGothic 0.3s ease-out; }

/* Legacy keyframes kept for compatibility */
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50%       { transform: translateY(-8px); }
}
@keyframes shimmer {
  0%   { background-position: -200% 0; }
  100% { background-position:  200% 0; }
}

/* ── SERIOUS MODE RETURN BUTTON ────────────────────────────── */
.serious-return-btn {
  position: fixed;
  top: 14px;
  left: 18px;
  z-index: 1000;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 16px;
  background: rgba(14, 8, 26, 0.88);
  border: 1px solid rgba(139, 92, 246, 0.45);
  border-radius: 8px;
  color: #e2e8f0;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-decoration: none;
  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(139, 92, 246, 0.2);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
}

.serious-return-btn:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateX(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.serious-return-btn:active {
  transform: translateX(0);
}

.serious-return-arrow {
  color: #fbbf24;
  font-size: 0.85rem;
  line-height: 1;
  transition: transform 0.15s ease;
}

.serious-return-btn:hover .serious-return-arrow {
  transform: translateX(-2px);
}

.serious-return-label {
  line-height: 1;
}

.serious-return-btn.is-morning {
  background: #EAD8B5;
  border-color: #B88A42;
  color: #3D2B1F;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.serious-return-btn.is-morning:hover {
  background: #FFFDF7;
  border-color: #D97706;
  color: #2C1E14;
}

.serious-return-btn.is-morning .serious-return-arrow {
  color: #B45309;
}

@media (max-width: 600px) {
  .serious-return-btn {
    top: 10px;
    left: 12px;
    height: 34px;
    padding: 0 12px;
    font-size: 0.72rem;
    gap: 6px;
  }
}

/* ── SERIOUS MODE TOP ACTIONS (THEME & MUSIC TOGGLES) ── */
.serious-nav-actions {
  position: fixed;
  top: 14px;
  right: 18px;
  z-index: 1000;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.serious-music-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 16px;
  background: rgba(14, 8, 26, 0.88);
  border: 1px solid rgba(139, 92, 246, 0.45);
  border-radius: 8px;
  color: #e2e8f0;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-decoration: none;
  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(139, 92, 246, 0.2);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
}

.serious-music-toggle-btn:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.serious-music-toggle-btn:active {
  transform: translateY(0);
}

.serious-music-toggle-btn.is-muted {
  background: rgba(45, 10, 10, 0.88);
  border-color: rgba(239, 68, 68, 0.5);
  color: #fca5a5;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(239, 68, 68, 0.2);
}

.serious-music-toggle-btn.is-muted:hover {
  background: rgba(70, 15, 15, 0.96);
  border-color: #ef4444;
  color: #ffffff;
}

.serious-music-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.serious-music-label {
  line-height: 1;
}

.serious-music-toggle-btn.is-morning {
  background: #EAD8B5;
  border-color: #B88A42;
  color: #3D2B1F;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.serious-music-toggle-btn.is-morning:hover {
  background: #FFFDF7;
  border-color: #735022;
  color: #1c130d;
}

.serious-music-toggle-btn.is-morning.is-muted {
  background: #fed7aa;
  border-color: #ea580c;
  color: #7c2d12;
}

/* ── SERIOUS MODE THEME TOGGLE BUTTON ─────────────────────────── */
.serious-theme-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 16px;
  background: rgba(14, 8, 26, 0.88);
  border: 1px solid rgba(139, 92, 246, 0.45);
  border-radius: 8px;
  color: #e2e8f0;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-decoration: none;
  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(139, 92, 246, 0.2);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
}

.serious-theme-toggle-btn:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.serious-theme-toggle-btn:active {
  transform: translateY(0);
}

.serious-theme-icon {
  color: #fbbf24;
  font-size: 0.95rem;
  line-height: 1;
}

.serious-theme-label {
  line-height: 1;
}

.serious-theme-toggle-btn.is-morning {
  background: #EAD8B5;
  border-color: #B88A42;
  color: #3D2B1F;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.serious-theme-toggle-btn.is-morning:hover {
  background: #FFFDF7;
  border-color: #735022;
  color: #1c130d;
}

.serious-theme-toggle-btn.is-morning .serious-theme-icon {
  color: #b45309;
}

@media (max-width: 600px) {
  .serious-nav-actions {
    top: 10px;
    right: 12px;
    gap: 6px;
  }
  .serious-music-toggle-btn,
  .serious-theme-toggle-btn {
    height: 34px;
    padding: 0 10px;
    font-size: 0.72rem;
    gap: 6px;
  }
}
</style>
