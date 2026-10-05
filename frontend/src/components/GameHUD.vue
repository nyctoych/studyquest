<template>
  <header class="game-hud" :class="{ 'hud-transparent': isTransparentRoute, 'hud-in-adventure': isAdventureRoute }">
    <!-- Left: Avatar & Player Info OR Back Button -->
    <div class="hud-left">
      <!-- Back Button when in Sub-page (e.g. Adventure Map, Study, etc.) -->
      <button
        v-if="showBackBtn"
        class="rpg-back-btn"
        @click="goBack"
        title="Back"
      >
        <span class="rpg-back-arrow">◀</span>
      </button>

      <!-- Player Avatar Unit (Always on /world, or compact in subroutes) -->
      <div v-if="!showBackBtn || !isMobile" class="rpg-player-unit" @click="navigateTo('/profile')">
        <!-- Ornate Circular Avatar -->
        <div class="rpg-avatar-frame">
          <AvatarImg
            :style-name="auth.avatarStyle"
            :seed="auth.avatarSeed"
            :options="auth.avatarOpts"
            :size="46"
          />
          <!-- Level Badge -->
          <div class="rpg-level-badge">
            <span>{{ auth.level }}</span>
          </div>
          <!-- Equipped Aura Effect -->
          <div class="hud-avatar-equipped-aura" v-if="equippedAura" :class="equippedAura.effect_key"></div>
        </div>

        <!-- Name Pill & Level/Title -->
        <div class="rpg-name-group">
          <div class="rpg-name-pill">
            <span class="rpg-player-name">{{ auth.nickname || auth.user?.username || 'Scholar' }}</span>
          </div>
          <div class="rpg-sub-badge">
            <span>Level {{ auth.level }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Center / Right: Real Resources & Status -->
    <div class="hud-resources">
      <!-- Health (Adventure HP) -->
      <div
        class="rpg-res-pill res-health"
        :class="['state-' + healthStore.statusState]"
        :title="healthTooltip"
        @click="onHealthPillClick"
      >
        <span class="res-icon health-icon"><PixelEmoji emoji="heart" size="sm" /></span>
        <span class="res-label font-pixel">HP</span>
        <span class="res-val">{{ healthStore.currentHp }} / {{ healthStore.maxHp }}</span>
        <span v-if="healthStore.isDefeated" class="health-status-badge">RECOVERING</span>
      </div>

      <!-- Streak -->
      <div class="rpg-res-pill res-streak" v-if="auth.streak > 0" title="Daily Study Streak">
        <span class="res-icon"><PixelEmoji emoji="fire" size="sm" /></span>
        <span class="res-val">{{ auth.streak }}d</span>
      </div>

      <!-- Coins / Points -->
      <div class="rpg-res-pill res-gold" title="Study Coins">
        <span class="res-icon gold-coin-icon"><PixelEmoji emoji="coin" size="sm" /></span>
        <span class="res-val">{{ formatCoins(auth.points || auth.coins) }}</span>
        <button class="res-add-btn" @click="navigateTo('/rewards')" title="Rewards Shop">+</button>
      </div>

      <!-- Battle Rating -->
      <div class="rpg-res-pill res-rating" title="Battle Rating">
        <span class="res-icon"><PixelEmoji emoji="lightning" size="sm" /></span>
        <span class="res-val">{{ auth.battleRating }}</span>
      </div>

      <!-- Quick Actions -->
      <div class="hud-quick-actions">
        <!-- Settings Link -->
        <button class="action-icon-btn" @click="navigateTo('/settings')" title="Settings">
          <span class="act-icon"><PixelEmoji emoji="gear" size="sm" /></span>
        </button>

        <!-- Dropdown Menu Trigger -->
        <button
          class="rpg-dropdown-btn"
          @click="menuOpen = !menuOpen"
          :aria-expanded="menuOpen"
          title="Menu"
        >
          <span class="dropdown-chevron">{{ menuOpen ? '▲' : '▼' }}</span>
        </button>
      </div>
    </div>

    <!-- Dropdown Menu Modal -->
    <Transition name="hud-menu">
      <div v-if="menuOpen" class="hud-mini-menu glass-card" @click.stop>
        <div class="menu-header">
          <span class="menu-title flex items-center gap-1.5"><PixelEmoji emoji="gear" size="sm" /> Game System</span>
          <button class="menu-close-btn" @click="menuOpen = false">✕</button>
        </div>
        <button class="menu-item text-left w-full flex items-center gap-2" @click="navigateTo('/world')">
          <PixelEmoji emoji="castle" size="sm" /> Realm Citadel
        </button>
        <button class="menu-item text-left w-full flex items-center gap-2" @click="navigateTo('/character')">
          <PixelEmoji emoji="shield" size="sm" /> Hero Sanctuary
        </button>
        <button class="menu-item text-left w-full flex items-center gap-2" @click="navigateTo('/special-event')">
          <PixelEmoji emoji="crown" size="sm" /> Special Event: Daily Trial
        </button>
        <button class="menu-item text-left w-full flex items-center gap-2" @click="navigateTo('/shop')">
          <PixelEmoji emoji="cart" size="sm" /> Grand Bazaar
        </button>
        <button class="menu-item text-left w-full flex items-center gap-2" @click="navigateTo('/settings')">
          <PixelEmoji emoji="gear" size="sm" /> Settings & Audio
        </button>
        <div class="menu-divider"></div>
        <button class="menu-item audio-toggle flex items-center gap-2" @click="game.setAudio(!game.audioEnabled)">
          <PixelEmoji :emoji="game.audioEnabled ? 'sparkles' : 'cross'" size="sm" /> Sound FX: {{ game.audioEnabled ? 'ON' : 'OFF' }}
        </button>
        <button class="menu-item audio-toggle flex items-center gap-2" @click="game.setMusic(!game.musicEnabled)">
          <PixelEmoji :emoji="game.musicEnabled ? 'sparkles' : 'cross'" size="sm" /> BGM Music: {{ game.musicEnabled ? 'ON' : 'OFF' }}
        </button>
        <div class="menu-divider"></div>
        <button class="menu-item menu-logout flex items-center gap-2" @click="handleLogout">
          <PixelEmoji emoji="hall" size="sm" /> Exit Game
        </button>
      </div>
    </Transition>

    <!-- Click-outside overlay -->
    <div v-if="menuOpen" class="hud-overlay" @click="menuOpen = false"></div>
  </header>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import { useToastStore } from '../stores/toast'
import { useTransitionStore } from '../stores/transition'
import { useHealthStore } from '../stores/health'
import AvatarImg from './AvatarImg.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const game = useGameStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()
const healthStore = useHealthStore()

const menuOpen = ref(false)
const windowWidth = ref(window.innerWidth)

function onResize() {
  windowWidth.value = window.innerWidth
}
onMounted(() => window.addEventListener('resize', onResize))
onUnmounted(() => window.removeEventListener('resize', onResize))

const isMobile = computed(() => windowWidth.value < 768)

// Show Back button when on sub-pages
const showBackBtn = computed(() => route.path !== '/world')
const isAdventureRoute = computed(() => route.path.startsWith('/adventure'))

const isTransparentRoute = computed(() =>
  route.path.startsWith('/battle/') ||
  route.path.startsWith('/adventure') ||
  route.path === '/world'
)

const equippedAura = computed(() =>
  auth.equipped?.find(e => e.category_slug === 'aura')
)

function formatCoins(c) {
  if (!c) return '0'
  if (c >= 1000000) return (c / 1000000).toFixed(1) + 'M'
  if (c >= 1000) return (c / 1000).toFixed(1) + 'K'
  return c.toLocaleString()
}

function navigateTo(path) {
  menuOpen.value = false
  transitionStore.triggerTravel(path, router)
}

function goBack() {
  if (route.path.startsWith('/adventure/') && route.params.id) {
    transitionStore.triggerTravel('/adventure', router)
  } else {
    transitionStore.triggerTravel('/world', router)
  }
}

async function handleLogout() {
  menuOpen.value = false
  await auth.logout()
  toast.info('Farewell, traveler!')
  transitionStore.triggerTravel('/', router)
}

const healthTooltip = computed(() => {
  if (healthStore.isDefeated) {
    return `YOU ARE DEFEATED (${healthStore.currentHp} / ${healthStore.maxHp} HP)\n+${healthStore.regenRate} HP / Hour\nNext Recovery in: ${healthStore.formattedCountdown}\nClick to visit Grand Bazaar for Health Potion (+200 HP)`
  }
  return `Adventure Health: ${healthStore.currentHp} / ${healthStore.maxHp} HP\nRegeneration: +${healthStore.regenRate} HP / Hour\nNext Recovery in: ${healthStore.formattedCountdown}\nClick to visit Grand Bazaar for Health Potion`
})

function onHealthPillClick() {
  navigateTo('/shop')
}

// Fetch health on mount and sync on navigation
onMounted(() => {
  if (auth.user) {
    healthStore.syncFromUser(auth.user)
  }
  healthStore.fetchHealth()
})

// React immediately if auth.user changes (e.g. login, level up, character selection)
watch(() => auth.user, (u) => {
  if (u) {
    healthStore.syncFromUser(u)
  }
}, { deep: true })

// Keep health fresh on route change (e.g., returning from battles, market, etc.)
watch(() => route.path, () => {
  healthStore.fetchHealth()
})
</script>

<style scoped>
/* ── GOTHIC PIXEL HUD ────────────────────────────── */
.game-hud {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1rem;
  background: rgba(8, 10, 14, 0.96);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid var(--gothic-border);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  pointer-events: auto;
  user-select: none;
  transition: opacity 0.3s;
}
.game-hud.hud-transparent {
  border-bottom-color: transparent;
}

/* ── LEFT GROUP ──────────────────────────────────── */
.hud-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

/* Gothic Back Button */
.rpg-back-btn {
  width: 40px;
  height: 40px;
  background: var(--gothic-surface);
  border: 2px solid var(--gothic-border-lit);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s, border-color 0.1s;
  image-rendering: pixelated;
}
.rpg-back-btn:hover {
  background: var(--gothic-raised);
  border-color: var(--bone-dim);
}
.rpg-back-btn:active { transform: translateY(1px); }
.rpg-back-arrow {
  color: var(--bone-white);
  font-size: 1rem;
}

/* ── PLAYER UNIT ─────────────────────────────────── */
.rpg-player-unit {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: opacity 0.15s;
}
.rpg-player-unit:hover { opacity: 0.85; }

/* Gothic Square Avatar Frame */
.rpg-avatar-frame {
  position: relative;
  width: 44px;
  height: 44px;
  border: 2px solid var(--aged-gold-dim);
  background: var(--gothic-deep);
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Level Badge — pixel corner */
.rpg-level-badge {
  position: absolute;
  top: -6px;
  left: -6px;
  background: var(--crimson-deep);
  border: 2px solid var(--crimson);
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
}
.rpg-level-badge span {
  font-family: var(--font-pixel);
  font-size: 6px;
  color: var(--bone-white);
}

/* Name group */
.rpg-name-group {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.rpg-name-pill {
  display: flex;
  align-items: center;
  background: rgba(22, 14, 38, 0.9);
  border: 1px solid rgba(168, 85, 247, 0.5);
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.2);
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}
.rpg-player-name {
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.04em;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
  line-height: 1.15;
}
.rpg-sub-badge {
  background: rgba(10, 6, 20, 0.95);
  border: 1px solid rgba(251, 191, 36, 0.4);
  padding: 0.1rem 0.45rem;
  border-radius: 3px;
  align-self: flex-start;
}
.rpg-sub-badge span {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #fbbf24;
  letter-spacing: 0.05em;
}

/* ── RESOURCES ──────────────────────────────────── */
.hud-resources {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

/* Resource Pills — gothic pixel bars */
.rpg-res-pill {
  display: flex;
  align-items: center;
  background: var(--gothic-deep);
  border: 1px solid var(--gothic-border);
  height: 34px;
  padding: 0 0.5rem;
  gap: 0.3rem;
  transition: border-color 0.15s;
}
.rpg-res-pill:hover { border-color: var(--gothic-border-lit); }
.res-streak { border-color: var(--crimson-deep); }
.res-gold   { border-color: var(--aged-gold-dim); }
.res-rating { border-color: var(--shadow-teal); }

/* Health Pill */
.res-health {
  border-color: rgba(239, 68, 68, 0.45);
  background: rgba(24, 10, 16, 0.95);
  position: relative;
  cursor: pointer;
  user-select: none;
  padding: 0 0.55rem;
  gap: 0.35rem;
}
.res-health:hover {
  border-color: rgba(239, 68, 68, 0.85);
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
}

.res-health .res-label {
  font-family: var(--font-pixel);
  font-size: 6px;
  color: #f87171;
  letter-spacing: 0.05em;
}

/* Health states (Rules 34 & 35) */
.res-health.state-normal {
  border-color: rgba(34, 197, 94, 0.55);
  box-shadow: 0 0 6px rgba(34, 197, 94, 0.15);
}
.res-health.state-normal .res-val {
  color: #86efac;
}
.res-health.state-normal .res-label {
  color: #4ade80;
}

.res-health.state-warning {
  border-color: rgba(245, 158, 11, 0.65);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.2);
}
.res-health.state-warning .res-val {
  color: #fde047;
}
.res-health.state-warning .res-label {
  color: #f59e0b;
}

.res-health.state-critical {
  border-color: rgba(239, 68, 68, 0.9);
  background: rgba(45, 8, 12, 0.95);
  animation: hpPulseCritical 1.2s infinite ease-in-out;
}
.res-health.state-critical .res-val {
  color: #fca5a5;
}
.res-health.state-critical .res-label {
  color: #ef4444;
}

.res-health.state-defeated {
  border-color: rgba(185, 28, 28, 0.95);
  background: rgba(35, 6, 10, 0.95);
}
.res-health.state-defeated .res-val {
  color: #f87171;
}
.res-health.state-defeated .res-label {
  color: #dc2626;
}

.health-status-badge {
  font-family: var(--font-pixel);
  font-size: 5px;
  background: rgba(239, 68, 68, 0.25);
  border: 1px solid rgba(239, 68, 68, 0.6);
  color: #fca5a5;
  padding: 1px 3px;
  border-radius: 2px;
  letter-spacing: 0.04em;
  animation: hpPulseRecovering 1.8s infinite ease-in-out;
}

@keyframes hpPulseCritical {
  0%, 100% { box-shadow: 0 0 6px rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 0 14px rgba(239, 68, 68, 0.85); }
}

@keyframes hpPulseRecovering {
  0%, 100% { opacity: 0.75; }
  50% { opacity: 1; }
}

@media (max-width: 640px) {
  .res-health .res-label { display: none; }
  .res-health .res-val { font-size: 6px; }
  .res-health .health-status-badge { display: none; }
}

.res-icon { font-size: 1rem; }
.res-val {
  font-family: var(--font-pixel);
  font-size: 7px;
  color: var(--text-primary);
  min-width: 36px;
  letter-spacing: 0.04em;
}
.res-gold .res-val { color: var(--aged-gold-bright); }
.res-streak .res-val { color: #ff8060; }

/* Pixel + button */
.res-add-btn {
  width: 22px;
  height: 22px;
  background: var(--gothic-surface);
  border: 1px solid var(--aged-gold-dim);
  color: var(--aged-gold-bright);
  font-size: 1rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.1s;
  line-height: 1;
}
.res-add-btn:hover { background: var(--aged-gold-dim); }

/* Quick action icons */
.hud-quick-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.action-icon-btn {
  position: relative;
  width: 34px;
  height: 34px;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: background 0.1s, border-color 0.1s;
}
.action-icon-btn:hover {
  background: var(--gothic-raised);
  border-color: var(--bone-dim);
}
.red-dot {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 8px;
  height: 8px;
  background: var(--crimson-bright);
  border: 1px solid var(--gothic-void);
}

/* Dropdown Toggle */
.rpg-dropdown-btn {
  width: 40px;
  height: 34px;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s, border-color 0.1s;
}
.rpg-dropdown-btn:hover {
  background: var(--gothic-raised);
  border-color: var(--bone-dim);
}
.dropdown-chevron {
  color: var(--text-muted);
  font-size: 0.75rem;
}

/* ── GOTHIC MINI MENU ────────────────────────────── */
.hud-mini-menu {
  position: absolute;
  top: 64px;
  right: 1rem;
  width: 220px;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  border-top: 2px solid var(--crimson);
  box-shadow: var(--shadow-gothic);
  padding: 0.75rem;
  z-index: 102;
}
.menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--gothic-border);
  margin-bottom: 0.5rem;
}
.menu-title {
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  color: var(--text-muted);
  letter-spacing: 0.06em;
}
.menu-close-btn {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 0.9rem;
  cursor: pointer;
  transition: color 0.1s;
}
.menu-close-btn:hover { color: var(--text-primary); }

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.6rem;
  font-family: var(--font-gothic);
  font-size: 0.9rem;
  color: var(--text-primary);
  text-decoration: none;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: background 0.1s, color 0.1s;
}
.menu-item:hover {
  background: var(--gothic-raised);
  color: var(--bone-white);
}
.menu-divider {
  height: 1px;
  background: var(--gothic-border);
  margin: 0.4rem 0;
}
.menu-logout { color: #ff7070; }
.menu-logout:hover { background: var(--crimson-void); color: #ffaaaa; }

/* Click-outside overlay */
.hud-overlay {
  position: fixed;
  inset: 0;
  z-index: 101;
}

/* Aura ring */
.hud-avatar-equipped-aura {
  position: absolute;
  inset: -4px;
  border: 2px solid var(--aged-gold);
  animation: crimsonThrob 2s ease-in-out infinite;
  pointer-events: none;
}

/* Menu transitions */
.hud-menu-enter-active, .hud-menu-leave-active {
  transition: opacity 0.15s, transform 0.15s;
}
.hud-menu-enter-from, .hud-menu-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

/* Responsive */
@media (max-width: 900px) {
  .rpg-res-pill.res-gems { display: none; }
}
@media (max-width: 650px) {
  .game-hud { padding: 0 0.5rem; height: 54px; }
  .rpg-avatar-frame { width: 38px; height: 38px; }
  .rpg-name-group { display: none; }
}
</style>