<template>
  <header
    class="game-hud"
    :class="{
      'hud-transparent': isTransparentRoute,
      'hud-in-adventure': isAdventureRoute,
      'is-morning-hud': game.isMorning,
      'theme-morning': game.isMorning
    }"
  >
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

        <!-- Name Pill & Level/Title / EXP Progress -->
        <div class="rpg-name-group">
          <div class="rpg-name-pill">
            <span class="rpg-player-name">{{ auth.nickname || auth.user?.username || 'Scholar' }}</span>
          </div>
          <div class="rpg-level-xp-row">
            <div class="rpg-sub-badge">
              <span>Level {{ auth.level }}</span>
            </div>
            <!-- EXP Progress Bar beside the level -->
            <div
              class="rpg-xp-pill"
              :title="`Experience: ${auth.xpInLevel} / ${auth.xpForNextLevel} XP (${auth.xpPercent}%)`"
            >
              <div class="rpg-xp-track">
                <div class="rpg-xp-fill" :style="{ width: `${auth.xpPercent}%` }"></div>
              </div>
              <span class="rpg-xp-text">{{ auth.xpInLevel }}/{{ auth.xpForNextLevel }} XP</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Center Navigation Header Title (Mystic Market) -->
    <div v-if="isMarketRoute" class="hud-center-title animate-fade-in">
      <span class="hud-title-star">✦</span>
      <span class="hud-title-text font-pixel">Mystic Market</span>
      <span class="hud-title-star">✦</span>
    </div>

    <!-- Center / Right: Real Resources & Status -->
    <div class="hud-resources">
      <!-- Health (Adventure HP) -->
      <div
        class="rpg-res-pill res-health"
        :class="['state-' + healthStore.statusState, { 'is-recovering': healthStore.isRecovering }]"
        :title="healthTooltip"
        @click="onHealthPillClick"
      >
        <span class="res-icon health-icon"><PixelEmoji emoji="heart" size="sm" /></span>
        <span class="res-label font-pixel">HP</span>
        <span class="res-val">{{ healthStore.currentHp }} / {{ healthStore.maxHp }}</span>
        
        <!-- Live Recovery Tag with real-time countdown -->
        <span v-if="healthStore.isDefeated" class="health-status-badge" :title="`Recovering: +1 HP in ${healthStore.formattedCountdown}`">
          REC {{ healthStore.formattedCountdown }}
        </span>
        <span v-else-if="healthStore.isRecovering" class="health-status-badge regen-tick" :title="`Recovering: +1 HP in ${healthStore.formattedCountdown}`">
          +1 in {{ healthStore.formattedCountdown }}
        </span>

        <!-- Live Health Recovery Progress Bar across bottom of pill -->
        <div v-if="healthStore.isRecovering" class="hud-hp-regen-track" :title="`Progress to next +1 HP: ${healthStore.regenProgressPercent}%`">
          <div class="hud-hp-regen-fill" :style="{ width: `${healthStore.regenProgressPercent}%` }"></div>
        </div>
      </div>

      <!-- Streak -->
      <div class="rpg-res-pill res-streak" v-if="auth.streak > 0" title="Daily Study Streak">
        <span class="res-icon"><PixelEmoji emoji="fire" size="sm" /></span>
        <span class="res-val">{{ auth.streak }}d</span>
      </div>

      <!-- Coins / Mystic Market -->
      <div
        class="rpg-res-pill res-gold"
        title="Scholar Coins (Click to visit Mystic Market)"
        @click="navigateTo('/market')"
      >
        <span class="res-icon gold-coin-icon"><PixelEmoji emoji="coin" size="sm" /></span>
        <span class="res-val">{{ formatCoins(auth.coins) }}</span>
      </div>

      <!-- Battle Rating -->
      <div class="rpg-res-pill res-rating" title="Battle Rating">
        <span class="res-icon"><PixelEmoji emoji="lightning" size="sm" /></span>
        <span class="res-val">{{ auth.battleRating }}</span>
      </div>

      <!-- Quick Actions -->
      <div class="hud-quick-actions">
        <!-- Daily Chest (1,500 EXP + 300 Gold, once per day) -->
        <button
          id="hud-daily-chest-btn"
          type="button"
          class="rpg-chest-btn"
          :class="{ 'is-ready': chestCanClaim, 'is-claimed': !chestCanClaim, 'is-morning': game.isMorning, 'is-busy': chestBusy }"
          :title="chestTooltip"
          :disabled="chestBusy"
          @click="claimDailyChest"
        >
          <span class="chest-icon" aria-hidden="true">{{ chestCanClaim ? '🎁' : '📦' }}</span>
          <span class="chest-label">{{ chestCanClaim ? 'CLAIM' : chestCountdown }}</span>
          <span v-if="chestCanClaim" class="chest-ping" aria-hidden="true"></span>
        </button>

        <!-- Global Theme Toggle (NIGHT ☾ ↔ MORNING ☀) -->
        <button
          class="rpg-theme-toggle-btn"
          :class="{ 'is-morning': game.isMorning }"
          @click="game.toggleTheme()"
          :title="game.isMorning ? 'Switch to Night Mode (☾)' : 'Switch to Morning Mode (☀)'"
        >
          <span class="theme-icon">{{ game.isMorning ? '☀' : '☾' }}</span>
          <span class="theme-label">{{ game.isMorning ? 'DAY' : 'NIGHT' }}</span>
        </button>

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
          <PixelEmoji emoji="crown" size="sm" /> Test Your Knowledge
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
import { useAdventureStore } from '../stores/adventure'
import AvatarImg from './AvatarImg.vue'
import api from '../services/api'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const game = useGameStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()
const healthStore = useHealthStore()
const adventure = useAdventureStore()

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
const isMarketRoute = computed(() => route.path === '/market' || route.path === '/shop')

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
  if (route.path.startsWith('/adventure')) {
    if (route.path.includes('/final-boss')) {
      adventure.abandonActiveBattle()
      transitionStore.triggerTravel('/adventure', router)
    } else if (route.path.includes('/part/') || route.path.includes('/boss')) {
      adventure.abandonActiveBattle()
      const chId = route.params.chapterId || 1
      transitionStore.triggerTravel(`/adventure/${chId}`, router)
    } else {
      transitionStore.triggerTravel('/world', router)
    }
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
    return `YOU ARE DEFEATED (${healthStore.currentHp} / ${healthStore.maxHp} HP)\nRecovery Rate: +1 HP / Minute\nNext +1 HP in: ${healthStore.formattedCountdown} (Progress: ${healthStore.regenProgressPercent}%)\nRecover at least 1 HP to battle!\nClick to visit Grand Bazaar for Health Potion (+200 HP)`
  }
  if (healthStore.isRecovering) {
    return `Adventure Health: ${healthStore.currentHp} / ${healthStore.maxHp} HP\nRecovery Rate: +1 HP / Minute\nNext +1 HP in: ${healthStore.formattedCountdown} (Progress: ${healthStore.regenProgressPercent}%)\nClick to visit Grand Bazaar for Health Potion`
  }
  return `Adventure Health: ${healthStore.currentHp} / ${healthStore.maxHp} HP (Full Health)\nClick to visit Grand Bazaar for Health Potion`
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

// ── Daily Chest ─────────────────────────────────────────────
const chestCanClaim = ref(false)
const chestBusy = ref(false)
const chestSecondsLeft = ref(0)
let chestTimer = null

const chestCountdown = computed(() => {
  const s = Math.max(0, chestSecondsLeft.value)
  const h = String(Math.floor(s / 3600)).padStart(2, '0')
  const m = String(Math.floor((s % 3600) / 60)).padStart(2, '0')
  const sec = String(s % 60).padStart(2, '0')
  return `${h}:${m}:${sec}`
})

const chestTooltip = computed(() =>
  chestCanClaim.value
    ? 'Daily Chest ready! Click to collect +1,500 EXP and +300 Gold'
    : `Daily Chest collected. Next chest in ${chestCountdown.value}`
)

function startChestTimer() {
  if (chestTimer) clearInterval(chestTimer)
  chestTimer = setInterval(() => {
    if (chestSecondsLeft.value > 0) {
      chestSecondsLeft.value -= 1
      if (chestSecondsLeft.value <= 0) fetchChestStatus()
    }
  }, 1000)
}

async function fetchChestStatus() {
  if (!auth.isLoggedIn) return
  try {
    const res = await api.get('/daily-chest/status')
    if (res.data?.success) {
      chestCanClaim.value = !!res.data.can_claim
      chestSecondsLeft.value = Number(res.data.seconds_until_reset) || 0
    }
  } catch (_) {}
}

async function claimDailyChest() {
  if (chestBusy.value) return
  if (!chestCanClaim.value) {
    toast.info(`Daily Chest already collected. Next one in ${chestCountdown.value}`)
    return
  }
  chestBusy.value = true
  try {
    const res = await api.post('/daily-chest/claim')
    if (res.data?.success) {
      chestCanClaim.value = false
      chestSecondsLeft.value = Number(res.data.seconds_until_reset) || 0
      if (res.data.user) auth.updateUser(res.data.user)
      toast.success(res.data.message || 'Daily Chest collected! +1,500 EXP and +300 Gold')
      if (res.data.leveled_up) toast.success(`Level up! You are now level ${res.data.new_level}`)
    }
  } catch (e) {
    const data = e.response?.data
    if (data) {
      chestCanClaim.value = !!data.can_claim
      chestSecondsLeft.value = Number(data.seconds_until_reset) || chestSecondsLeft.value
    }
    toast.error(data?.message || 'Could not open the Daily Chest. Try again.')
  } finally {
    chestBusy.value = false
  }
}

onMounted(() => {
  fetchChestStatus()
  startChestTimer()
})
onUnmounted(() => { if (chestTimer) clearInterval(chestTimer) })
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
  background: var(--hud-bg);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1.5px solid var(--hud-border);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  pointer-events: auto;
  user-select: none;
  transition: opacity 0.3s;
}
.game-hud.hud-transparent {
  border-bottom-color: transparent;
}

/* ── CENTER NAVIGATION HEADER TITLE (MYSTIC MARKET) ── */
.hud-center-title {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 0.55rem;
  pointer-events: none;
  z-index: 10;
}
.hud-title-star {
  font-size: 0.8rem;
  color: #fbbf24;
  text-shadow: 0 0 10px rgba(251, 191, 36, 0.7);
  animation: starPulse 2.5s infinite ease-in-out;
}
.hud-title-text {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--text-heading, #faf5ff);
  text-transform: uppercase;
  margin: 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9), 0 0 16px rgba(168, 85, 247, 0.45);
}
:global(.theme-morning) .hud-title-text,
:global(.is-morning) .hud-title-text {
  color: #3e2415 !important;
  text-shadow: 0 1px 2px rgba(255, 255, 255, 0.6);
}
@keyframes starPulse {
  0%, 100% { opacity: 0.75; transform: scale(0.92); }
  50% { opacity: 1; transform: scale(1.15); }
}
@media (max-width: 900px) {
  .hud-center-title {
    display: none;
  }
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
  border: 2px solid var(--hud-border);
  background: var(--hud-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: border-color 0.15s, background-color 0.15s;
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
  background: var(--hud-name-pill-bg);
  border: 1px solid var(--hud-name-pill-border);
  box-shadow: none;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}
.rpg-player-name {
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--hud-name-color);
  letter-spacing: 0.04em;
  text-shadow: none;
  line-height: 1.15;
}
.rpg-level-xp-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.rpg-sub-badge {
  background: var(--hud-sub-badge-bg);
  border: 1px solid var(--hud-sub-badge-border);
  padding: 0.12rem 0.45rem;
  border-radius: 3px;
  display: inline-flex;
  align-items: center;
  height: 20px;
  box-sizing: border-box;
}
.rpg-sub-badge span {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: var(--hud-sub-badge-color);
  letter-spacing: 0.05em;
  line-height: 1;
}

/* EXP Progress Bar Pill beside Level */
.rpg-xp-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: var(--hud-sub-badge-bg);
  border: 1px solid var(--hud-sub-badge-border);
  padding: 0.12rem 0.45rem;
  border-radius: 3px;
  height: 20px;
  box-sizing: border-box;
  cursor: default;
  transition: all 0.2s ease;
}
.rpg-xp-pill:hover {
  border-color: #a855f7;
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.35);
}
.rpg-xp-track {
  width: 52px;
  height: 6px;
  background: rgba(0, 0, 0, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 2px;
  overflow: hidden;
  position: relative;
}
.rpg-xp-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #a855f7, #c084fc);
  border-radius: 1px;
  transition: width 0.4s ease-out;
  box-shadow: 0 0 6px rgba(168, 85, 247, 0.5);
}
.rpg-xp-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.42rem;
  color: var(--hud-sub-badge-color, #fbbf24);
  letter-spacing: 0.04em;
  white-space: nowrap;
  line-height: 1;
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
.res-gold {
  border-color: var(--aged-gold-dim);
  cursor: pointer;
  user-select: none;
  transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
}
.res-gold:hover {
  border-color: var(--aged-gold-bright, #e6c845);
  box-shadow: 0 0 10px rgba(230, 200, 69, 0.4);
  transform: translateY(-1px);
}
.res-gold:active {
  transform: translateY(0);
}
.res-rating { border-color: var(--shadow-teal); }

/* Health Pill */
.res-health {
  border-color: var(--hud-health-border);
  background: var(--hud-health-bg);
  position: relative;
  cursor: pointer;
  user-select: none;
  padding: 0 0.55rem;
  gap: 0.35rem;
  transition: border-color 0.15s, background-color 0.15s;
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
  border-color: var(--hud-health-border);
  background: var(--hud-health-bg);
  box-shadow: none;
}
.res-health.state-normal .res-val {
  color: var(--hud-health-val-color);
}
.res-health.state-normal .res-label {
  color: var(--hud-health-lbl-color);
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
  white-space: nowrap;
}

.health-status-badge.regen-tick {
  background: rgba(16, 185, 129, 0.2);
  border-color: rgba(16, 185, 129, 0.6);
  color: #6ee7b7;
}

/* Live Health Recovery Progress Bar across bottom of pill */
.hud-hp-regen-track {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(0, 0, 0, 0.6);
  overflow: hidden;
  pointer-events: none;
}
.hud-hp-regen-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #34d399);
  transition: width 0.9s linear;
  box-shadow: 0 0 6px rgba(16, 185, 129, 0.8);
}
.res-health.state-defeated .hud-hp-regen-fill {
  background: linear-gradient(90deg, #ef4444, #fbbf24);
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.8);
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

/* ── DAILY CHEST BUTTON ── */
.rpg-chest-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  height: 34px;
  padding: 0 0.6rem;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  cursor: pointer;
  font-family: var(--font-pixel);
  font-size: 8px;
  letter-spacing: 0.05em;
  color: var(--text-muted, #94a3b8);
  transition: all 0.15s ease;
  user-select: none;
  font-variant-numeric: tabular-nums;
}
.rpg-chest-btn .chest-icon { font-size: 14px; line-height: 1; }
.rpg-chest-btn .chest-label { font-weight: 700; line-height: 1; }
.rpg-chest-btn.is-ready {
  color: #fde68a;
  border-color: #f59e0b;
  background: linear-gradient(180deg, rgba(120, 72, 8, 0.65), rgba(60, 34, 4, 0.85));
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.55);
  animation: chestPulse 1.8s ease-in-out infinite;
}
.rpg-chest-btn.is-ready .chest-icon { animation: chestWiggle 1.8s ease-in-out infinite; }
.rpg-chest-btn.is-ready:hover { transform: translateY(-1px); border-color: #fbbf24; }
.rpg-chest-btn.is-claimed { opacity: 0.8; cursor: default; }
.rpg-chest-btn.is-busy { opacity: 0.6; cursor: wait; }
.rpg-chest-btn .chest-ping {
  position: absolute;
  top: -3px;
  right: -3px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #ef4444;
  box-shadow: 0 0 6px #ef4444;
  animation: chestPing 1.2s ease-in-out infinite;
}
.rpg-chest-btn.is-morning { background: #fdf6e7; border-color: #d4a017; color: #8c6a3e; }
.rpg-chest-btn.is-morning.is-ready {
  background: linear-gradient(180deg, #fff3c4, #fde68a);
  color: #92400e;
  border-color: #d97706;
  box-shadow: 0 0 10px rgba(217, 119, 6, 0.4);
}
@keyframes chestPulse {
  0%, 100% { box-shadow: 0 0 6px rgba(245, 158, 11, 0.4); }
  50% { box-shadow: 0 0 14px rgba(245, 158, 11, 0.85); }
}
@keyframes chestWiggle {
  0%, 80%, 100% { transform: rotate(0deg); }
  85% { transform: rotate(-12deg); }
  90% { transform: rotate(10deg); }
  95% { transform: rotate(-6deg); }
}
@keyframes chestPing {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.4); opacity: 0.6; }
}
.rpg-theme-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  height: 34px;
  padding: 0 0.55rem;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  cursor: pointer;
  font-family: var(--font-pixel);
  font-size: 8px;
  color: var(--aged-gold-bright);
  transition: all 0.15s ease;
  user-select: none;
}
.rpg-theme-toggle-btn:hover {
  background: var(--gothic-raised);
  border-color: var(--aged-gold);
  transform: translateY(-1px);
}
.rpg-theme-toggle-btn.is-morning {
  background: #fdf6e7;
  border-color: #d4a017;
  color: #b45309;
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.25);
}
.theme-icon {
  font-size: 11px;
  line-height: 1;
}
.theme-label {
  letter-spacing: 0.05em;
  font-weight: 700;
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
  .rpg-xp-text { display: none; }
}
@media (max-width: 650px) {
  .game-hud { padding: 0 0.5rem; height: 54px; }
  .rpg-avatar-frame { width: 38px; height: 38px; }
  .rpg-name-group { display: none; }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — GAME HUD (MATCHES REFERENCE)
   ══════════════════════════════════════════════════════════════ */
.game-hud.is-morning-hud,
.game-hud.theme-morning {
  background: #FFF4DF !important;
  border-bottom: 1.5px solid #B88A42 !important;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.15) !important;
}

.is-morning-hud .rpg-res-pill,
.theme-morning .rpg-res-pill {
  background: #FFF4DF !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
}

.is-morning-hud .res-val,
.theme-morning .res-val {
  color: #3D2B1F !important;
}

.is-morning-hud .res-health,
.theme-morning .res-health {
  background: #FFF4DF !important;
  border: 1px solid #B88A42 !important;
}

.is-morning-hud .res-health.state-normal,
.theme-morning .res-health.state-normal {
  background: #FFF4DF !important;
  border-color: #B88A42 !important;
}

.is-morning-hud .res-health .res-val,
.is-morning-hud .res-health.state-normal .res-val,
.theme-morning .res-health .res-val {
  color: #166534 !important;
}

.is-morning-hud .res-health .res-label,
.is-morning-hud .res-health.state-normal .res-label,
.theme-morning .res-health .res-label {
  color: #166534 !important;
}

.is-morning-hud .rpg-avatar-frame,
.theme-morning .rpg-avatar-frame {
  background: #FFF4DF !important;
  border-color: #B88A42 !important;
}

.is-morning-hud .rpg-name-pill,
.theme-morning .rpg-name-pill {
  background: #FFF4DF !important;
  border: 1px solid #B88A42 !important;
  box-shadow: none !important;
}

.is-morning-hud .rpg-player-name,
.theme-morning .rpg-player-name {
  color: #3D2B1F !important;
  text-shadow: none !important;
}

.is-morning-hud .rpg-sub-badge,
.theme-morning .rpg-sub-badge,
.is-morning-hud .rpg-xp-pill,
.theme-morning .rpg-xp-pill {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
}

.is-morning-hud .rpg-sub-badge span,
.theme-morning .rpg-sub-badge span,
.is-morning-hud .rpg-xp-text,
.theme-morning .rpg-xp-text {
  color: #3D2B1F !important;
}

.is-morning-hud .rpg-xp-track,
.theme-morning .rpg-xp-track {
  background: #D4C3A3 !important;
  border-color: #B88A42 !important;
}

.is-morning-hud .rpg-xp-fill,
.theme-morning .rpg-xp-fill {
  background: linear-gradient(90deg, #B88A42, #E5A93C) !important;
  box-shadow: none !important;
}

.is-morning-hud .rpg-back-btn,
.theme-morning .rpg-back-btn,
.is-morning-hud .action-icon-btn,
.theme-morning .action-icon-btn,
.is-morning-hud .rpg-dropdown-btn,
.theme-morning .rpg-dropdown-btn {
  background: #FFF4DF !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
}

.is-morning-hud .rpg-back-arrow,
.theme-morning .rpg-back-arrow,
.is-morning-hud .dropdown-chevron,
.theme-morning .dropdown-chevron,
.is-morning-hud .action-icon-btn .act-icon,
.theme-morning .action-icon-btn .act-icon {
  color: #3D2B1F !important;
}

.is-morning-hud .rpg-theme-toggle-btn,
.theme-morning .rpg-theme-toggle-btn {
  background: #FFF4DF !important;
  border: 1px solid #B88A42 !important;
  color: #8F642D !important;
}

.is-morning-hud .hud-mini-menu,
.theme-morning .hud-mini-menu {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  box-shadow: 0 12px 32px rgba(143, 100, 45, 0.22) !important;
  color: #3D2B1F !important;
}

.is-morning-hud .menu-item,
.theme-morning .menu-item {
  color: #3D2B1F !important;
}

.is-morning-hud .menu-item:hover,
.theme-morning .menu-item:hover {
  background: #EAD8B5 !important;
  color: #8F642D !important;
}
</style>