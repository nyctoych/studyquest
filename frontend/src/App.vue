<template>
  <div id="app">
    <!-- Loading Screen (shown once per session) -->
    <LoadingScreen v-if="showLoading" @done="onLoadingDone" />

    <!-- Main App (shown after loading) -->
    <template v-else>
      <!-- Game HUD overlay (authenticated gameplay routes only) -->
      <GameHUD v-if="showHUD" />

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

    <!-- Game World Module Travel Transition Overlay -->
    <GameTransition />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import LoadingScreen from './views/LoadingScreen.vue'
import GameHUD from './components/GameHUD.vue'
import ToastContainer from './components/ToastContainer.vue'
import GameTransition from './components/transitions/GameTransition.vue'
import { useAuthStore } from './stores/auth'
import { useGameStore } from './stores/game'
import { bgmService } from './services/bgmService'

const route = useRoute()
const authStore = useAuthStore()
const gameStore = useGameStore()

// Loading screen shown once per browser session
const showLoading = ref(!sessionStorage.getItem('sq_loaded'))

// Routes where HUD should NOT appear
const NO_HUD_ROUTES = ['/', '/login', '/register', '/tutorial']
const showHUD = computed(() =>
  authStore.isLoggedIn &&
  !NO_HUD_ROUTES.includes(route.path) &&
  !route.path.startsWith('/battle/') &&
  !route.path.startsWith('/special-event/exam')
)

// Full-screen views that manage their own full-bleed canvas or custom internal layouts
const FULL_SCREEN_PREFIXES = ['/world', '/dashboard', '/battle', '/special-event/exam', '/studyhall']
const isFullScreenView = computed(() => {
  const p = route.path
  if (NO_HUD_ROUTES.includes(p)) return true
  if (FULL_SCREEN_PREFIXES.some(prefix => p.startsWith(prefix))) return true
  if (p.includes('final-boss')) return true
  if (p === '/adventure' || (p.startsWith('/adventure/') && !p.includes('/part/') && !p.includes('/boss'))) return true
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

onMounted(async () => {
  bgmService.init(gameStore.musicEnabled, route.path)
  await authStore.init()
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

/* ── APP PAGE WRAPPER (Top margin & responsive spacing for modules) ── */
.app-page-wrapper {
  width: 100%;
  min-height: 100vh;
  box-sizing: border-box;
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
</style>
