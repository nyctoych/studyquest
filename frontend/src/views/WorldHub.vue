<template>
  <div class="rpg-world-hub">
    <!-- Top Greeting Pill (Anchored to top viewport below HUD) -->
    <div class="world-welcome-bar animate-slide-down">
      <div class="welcome-pill">
        <span class="welcome-greet">Welcome back,</span>
        <strong class="welcome-name">{{ auth.nickname || auth.user?.username }}</strong>!
        <span v-if="auth.streak > 0" class="welcome-streak flex items-center gap-1.5"><PixelEmoji emoji="fire" size="sm" /> {{ auth.streak }}-day streak</span>
      </div>
    </div>

    <!-- Town Scene Canvas / Backdrop -->
    <!-- Horizontally draggable panorama scene container -->
    <div
      class="town-scene-wrapper"
      ref="townSceneRef"
      @mousedown="onDragStart"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
    >
      <!-- Fixed canvas that keeps background image and hotspots in 100% lockstep -->
      <div class="town-scene-canvas" ref="canvasRef">
        <img
          src="/assets/game/gothic_world_hub.jpg"
          alt="Realm Citadel"
          class="town-scene-img"
          draggable="false"
        />

        <!-- Ambient light and vignette layers -->
        <div class="scene-vignette"></div>
        <div class="portal-pulse-glow"></div>
        <div class="fountain-shimmer-glow"></div>

        <!-- Atmospheric Animated Fire, Torch Sparkles & Colosseum Braziers -->
        <div class="fire-fx-container">
          <!-- 1. Colosseum Brazier 1 (Left balustrade) -->
          <div class="fire-point fire-gold-brazier" style="top: 55.6%; left: 5.8%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="fire-star-sparkle sp-3"></div>
            <div class="fire-star-sparkle sp-4"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
            <div class="ember-spark e-4"></div>
            <div class="ember-spark e-5"></div>
          </div>

          <!-- 2. Colosseum Brazier 2 (Mid balustrade) -->
          <div class="fire-point fire-gold-brazier" style="top: 55.6%; left: 21.6%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="fire-star-sparkle sp-3"></div>
            <div class="fire-star-sparkle sp-4"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
            <div class="ember-spark e-4"></div>
            <div class="ember-spark e-5"></div>
          </div>

          <!-- 3. Colosseum Brazier 3 (Right balustrade) -->
          <div class="fire-point fire-gold-brazier" style="top: 56.6%; left: 31.6%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="fire-star-sparkle sp-3"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
            <div class="ember-spark e-4"></div>
          </div>

          <!-- 4. Colosseum Brazier 4 (Entrance pillar) -->
          <div class="fire-point fire-gold-brazier" style="top: 58.0%; left: 39.1%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="fire-star-sparkle sp-3"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
          </div>

          <!-- 5. Street Lantern Post (Left foreground) -->
          <div class="fire-point fire-lantern" style="top: 72.8%; left: 14.8%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
          </div>

          <!-- 6. Ground Stone Lantern (Bottom Left) -->
          <div class="fire-point fire-lantern" style="top: 77.2%; left: 6.9%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
          </div>

          <!-- 7. Central Archway Torch -->
          <div class="fire-point fire-gold" style="top: 67.8%; left: 47.7%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
          </div>

          <!-- 8. Cathedral Right Entrance Torch -->
          <div class="fire-point fire-gold" style="top: 62.0%; left: 62.4%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
            <div class="ember-spark e-3"></div>
          </div>

          <!-- 9. Marketplace Street Lantern -->
          <div class="fire-point fire-lantern" style="top: 73.6%; left: 76.5%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="fire-star-sparkle sp-2"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
          </div>

          <!-- 10. Marketplace Right Lantern -->
          <div class="fire-point fire-lantern" style="top: 75.8%; left: 82.2%;">
            <div class="fire-halo"></div>
            <div class="fire-flame-core"></div>
            <div class="fire-star-sparkle sp-1"></div>
            <div class="ember-spark e-1"></div>
            <div class="ember-spark e-2"></div>
          </div>
        </div>

        <!-- Interactive Hotspots on the Town Map -->
        <div class="town-hotspots-layer">
          <!-- 1. ADVENTURE (Center Palace Portal - Stage map & boss battles) -->
          <div
            class="town-building spot-adventure"
            @click="handleSpotClick('/adventure')"
            @mouseenter="hoveredSpot = 'adventure'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="portal-swirl-fx"></div>
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="sword" size="sm" /></span>
                <span class="building-name">Adventure Gate</span>
                <span class="badge-dot pulse"></span>
              </div>
            </div>
          </div>

          <!-- 2. STUDY HALL (Psychology Study Paths & Quests) -->
          <div
            class="town-building spot-quests"
            @click="handleSpotClick('/studyhall')"
            @mouseenter="hoveredSpot = 'quests'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="scroll" size="sm" /></span>
                <span class="building-name">Study Hall</span>
              </div>
            </div>
          </div>

          <!-- 3. ARENA (Multiplayer Colosseum) -->
          <div
            class="town-building spot-multiplayer"
            @click="handleSpotClick('/multiplayer')"
            @mouseenter="hoveredSpot = 'multiplayer'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="lightning" size="sm" /></span>
                <span class="building-name">Arena</span>
              </div>
            </div>
          </div>

          <!-- 4. MARKET (Cosmetics Boutique & Bazaar) -->
          <div
            class="town-building spot-shop"
            @click="handleSpotClick('/market')"
            @mouseenter="hoveredSpot = 'shop'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="cart" size="sm" /></span>
                <span class="building-name">Market</span>
              </div>
            </div>
          </div>

          <!-- 5. HALL OF RECORDS (Leaderboards) -->
          <div
            class="town-building spot-leaderboard"
            @click="handleSpotClick('/leaderboard')"
            @mouseenter="hoveredSpot = 'leaderboard'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="trophy" size="sm" /></span>
                <span class="building-name">Hall of Records</span>
              </div>
            </div>
          </div>

          <!-- 6. ACHIEVEMENT HALL (Achievements & Milestones) -->
          <div
            class="town-building spot-achievements"
            @click="handleSpotClick('/achievements')"
            @mouseenter="hoveredSpot = 'achievements'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="shield" size="sm" /></span>
                <span class="building-name">Achievement Hall</span>
              </div>
            </div>
          </div>

          <!-- 7. CHRONICLE HALL (Social Posts / World Activity) -->
          <div
            class="town-building spot-chronicle"
            @click="handleSpotClick('/chronicle')"
            @mouseenter="hoveredSpot = 'chronicle'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="book" size="sm" /></span>
                <span class="building-name">Chronicle Hall</span>
              </div>
            </div>
          </div>

          <!-- 8. SETTINGS (System Configuration) -->
          <div
            class="town-building spot-settings"
            @click="handleSpotClick('/settings')"
            @mouseenter="hoveredSpot = 'settings'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill">
                <span class="building-icon"><PixelEmoji emoji="gear" size="sm" /></span>
                <span class="building-name">Settings</span>
              </div>
            </div>
          </div>

          <!-- 9. SPECIAL EVENT (The Daily Trial / Ancient Examination Chamber) -->
          <div
            class="town-building spot-special-event"
            @click="handleSpotClick('/special-event')"
            @mouseenter="hoveredSpot = 'special-event'"
            @mouseleave="hoveredSpot = null"
          >
            <div class="building-hitbox">
              <div class="building-badge-pill special-event-pill">
                <span class="building-icon"><PixelEmoji emoji="crown" size="sm" /></span>
                <span class="building-name">Daily Trial</span>
                <span class="badge-dot pulse trial-dot"></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Navigation Dock: All Modules (Horizontally scrollable/draggable on mobile) -->
    <nav
      class="world-module-dock animate-slide-up"
      ref="dockRef"
      aria-label="Game Modules"
      @mousedown="onDockDragStart"
      @touchstart="onDockTouchStart"
      @touchmove="onDockTouchMove"
      @touchend="onDockTouchEnd"
    >
      <div
        v-for="mod in modulesList"
        :key="mod.path"
        class="module-dock-item"
        :class="{ 'active': activeRoute === mod.path }"
        :title="mod.name"
        @click="navigateModule(mod.path)"
      >
        <span class="dock-icon"><PixelEmoji :emoji="mod.icon" size="sm" /></span>
        <span class="dock-name">{{ mod.name }}</span>
      </div>
    </nav>

    <!-- Bottom-Left World Chat Terminal (Desktop only) -->
    <div class="bottom-chat-terminal" :class="{ 'chat-expanded': chatExpanded }">
      <div class="chat-controls">
        <button class="chat-ctrl-btn shield-btn" @click="handleSpotClick('/leaderboard')" title="Leaderboards">
          <PixelEmoji emoji="shield" size="xs" />
        </button>
        <button class="chat-ctrl-btn bubble-btn" @click="chatExpanded = !chatExpanded" title="Toggle Chat">
          <PixelEmoji emoji="scroll" size="xs" />
        </button>
        <div class="chat-time-stamp">
          <span>{{ timeDisplay }}</span>
        </div>
      </div>

      <div class="chat-feed" @click="chatExpanded = !chatExpanded">
        <div v-for="(msg, idx) in visibleMessages" :key="idx" class="chat-line">
          <span class="chat-tag" :class="'tag-' + msg.type">[{{ msg.channel }}]</span>
          <span class="chat-text"> {{ msg.content }}</span>
        </div>
      </div>

      <div v-if="chatExpanded" class="chat-input-row" @click.stop>
        <input
          v-model="newMsgText"
          type="text"
          placeholder="Type message to students..."
          class="chat-input"
          @keyup.enter="sendChatMessage"
        />
        <button class="chat-send-btn" @click="sendChatMessage">Send</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTransitionStore } from '../stores/transition'
import { useAdventureStore } from '../stores/adventure'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const transitionStore = useTransitionStore()
const adventure = useAdventureStore()

const hoveredSpot = ref(null)
const chatExpanded = ref(false)
const newMsgText = ref('')
const timeDisplay = ref('')
const townSceneRef = ref(null)
const canvasRef = ref(null)
const dockRef = ref(null)

const activeRoute = computed(() => route.path)

// ── PANORAMA DRAG-SCROLL (Town Scene) ────────────────────────
let dragActive = false
let dragStartX = 0
let dragScrollLeft = 0
let hasPanned = false

function isMobilePanorama() {
  return window.innerWidth <= 1100
}

function onDragStart(e) {
  if (e.button !== 0) return
  const el = townSceneRef.value
  if (!el || !isMobilePanorama()) return
  dragActive = true
  hasPanned = false
  dragStartX = e.clientX
  dragScrollLeft = el.scrollLeft
  el.style.cursor = 'grabbing'
  el.style.userSelect = 'none'

  window.addEventListener('mousemove', onDragMove)
  window.addEventListener('mouseup', onDragEnd)
}

function onDragMove(e) {
  if (!dragActive) return
  const el = townSceneRef.value
  if (!el) return
  const dx = e.clientX - dragStartX
  if (Math.abs(dx) > 5) {
    hasPanned = true
  }
  el.scrollLeft = dragScrollLeft - dx
}

function onDragEnd() {
  if (!dragActive) return
  dragActive = false
  const el = townSceneRef.value
  if (el) {
    el.style.cursor = ''
    el.style.userSelect = ''
  }
  window.removeEventListener('mousemove', onDragMove)
  window.removeEventListener('mouseup', onDragEnd)
}

// Touch events for town scene
let touchDragStartX = 0
let touchDragScrollLeft = 0

function onTouchStart(e) {
  const el = townSceneRef.value
  if (!el || !isMobilePanorama() || e.touches.length !== 1) return
  touchDragStartX = e.touches[0].clientX
  touchDragScrollLeft = el.scrollLeft
  hasPanned = false
}

function onTouchMove(e) {
  const el = townSceneRef.value
  if (!el || !isMobilePanorama() || e.touches.length !== 1) return
  const dx = e.touches[0].clientX - touchDragStartX
  if (Math.abs(dx) > 5) {
    hasPanned = true
  }
  el.scrollLeft = touchDragScrollLeft - dx
}

function onTouchEnd() {}

function handleSpotClick(path) {
  if (hasPanned) return
  if (path === '/adventure') {
    const chId = adventure.currentUnlockedChapterId || Number(localStorage.getItem('sq_active_chapter')) || Number(localStorage.getItem('sq_unlocked_chapter')) || 1
    transitionStore.triggerTravel(`/adventure/${chId}`, router)
    return
  }
  transitionStore.triggerTravel(path, router)
}

// ── DOCK DRAG-SCROLL ─────────────────────────────────────────
let dockDragActive = false
let dockStartX = 0
let dockScrollLeft = 0
let dockHasMoved = false

function onDockDragStart(e) {
  if (e.button !== 0) return
  const el = dockRef.value
  if (!el) return
  dockDragActive = true
  dockHasMoved = false
  dockStartX = e.clientX
  dockScrollLeft = el.scrollLeft
  el.style.cursor = 'grabbing'
  el.style.userSelect = 'none'

  window.addEventListener('mousemove', onDockDragMove)
  window.addEventListener('mouseup', onDockDragEnd)
}

function onDockDragMove(e) {
  if (!dockDragActive) return
  const el = dockRef.value
  if (!el) return
  const dx = e.clientX - dockStartX
  if (Math.abs(dx) > 4) {
    dockHasMoved = true
  }
  el.scrollLeft = dockScrollLeft - dx
}

function onDockDragEnd() {
  if (!dockDragActive) return
  dockDragActive = false
  const el = dockRef.value
  if (el) {
    el.style.cursor = ''
    el.style.userSelect = ''
  }
  window.removeEventListener('mousemove', onDockDragMove)
  window.removeEventListener('mouseup', onDockDragEnd)
}

let dockTouchStartX = 0
let dockTouchScrollLeft = 0

function onDockTouchStart(e) {
  const el = dockRef.value
  if (!el || e.touches.length !== 1) return
  dockTouchStartX = e.touches[0].clientX
  dockTouchScrollLeft = el.scrollLeft
  dockHasMoved = false
}

function onDockTouchMove(e) {
  const el = dockRef.value
  if (!el || e.touches.length !== 1) return
  const dx = e.touches[0].clientX - dockTouchStartX
  if (Math.abs(dx) > 4) {
    dockHasMoved = true
  }
  el.scrollLeft = dockTouchScrollLeft - dx
}

function onDockTouchEnd() {}

function navigateModule(path) {
  if (dockHasMoved) return
  if (path === '/adventure') {
    const chId = adventure.currentUnlockedChapterId || Number(localStorage.getItem('sq_active_chapter')) || Number(localStorage.getItem('sq_unlocked_chapter')) || 1
    transitionStore.triggerTravel(`/adventure/${chId}`, router)
    return
  }
  transitionStore.triggerTravel(path, router)
}

const modulesList = [
  { name: 'Study Hall',        path: '/studyhall',     icon: 'scroll' },
  { name: 'Adventure Gate',    path: '/adventure',     icon: 'sword' },
  { name: 'The Daily Trial',   path: '/special-event', icon: 'crown' },
  { name: 'Arena',             path: '/multiplayer',   icon: 'lightning' },
  { name: 'Market',            path: '/market',        icon: 'cart' },
  { name: 'Hall of Records',   path: '/leaderboard',   icon: 'trophy' },
  { name: 'Achievement Hall',  path: '/achievements',  icon: 'shield' },
  { name: 'Chronicle Hall',    path: '/chronicle',     icon: 'book' },
  { name: 'Settings',          path: '/settings',      icon: 'gear' }
]

const messages = ref([
  { type: 'sys', channel: 'StudyQuest', content: "Welcome to the Psychology Game World! Explore Quests, Practice, and Multiplayer." },
  { type: 'world', channel: 'Scholar', content: "Preparing for the Psychological Assessment board exam!" },
  { type: 'sys', channel: 'System', content: "Daily quest resets at midnight. Keep up your study streak!" }
])

const visibleMessages = computed(() => {
  return chatExpanded.value ? messages.value : messages.value.slice(-2)
})

function centerScene() {
  const el = townSceneRef.value
  if (el && isMobilePanorama() && el.scrollWidth > el.clientWidth) {
    el.scrollLeft = (el.scrollWidth - el.clientWidth) / 2
  }
}

function handleResize() {
  if (isMobilePanorama()) {
    centerScene()
  }
}

onMounted(() => {
  const d = new Date()
  timeDisplay.value = `${d.getMonth() + 1}/${d.getDate()} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`

  // Center town scene panorama on mobile/tablet so cathedral/adventure is front and center
  setTimeout(centerScene, 60)
  window.addEventListener('resize', handleResize)
  adventure.loadChapters()
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('mousemove', onDragMove)
  window.removeEventListener('mouseup', onDragEnd)
  window.removeEventListener('mousemove', onDockDragMove)
  window.removeEventListener('mouseup', onDockDragEnd)
})

function sendChatMessage() {
  if (!newMsgText.value.trim()) return
  messages.value.push({
    type: 'player',
    channel: auth.nickname || auth.user?.username || 'Scholar',
    content: newMsgText.value.trim()
  })
  newMsgText.value = ''
}
</script>

<style scoped>
/* ── WORLD HUB ROOT ──────────────────────────── */
.rpg-world-hub {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: var(--gothic-void);
  user-select: none;
}

/* ── TOWN SCENE (Horizontally draggable on mobile) ─── */
.town-scene-wrapper {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  cursor: default;
}

.town-scene-canvas {
  position: relative;
  width: 100%;
  height: 100%;
}

.town-scene-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 46%;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  filter: contrast(1.05) saturate(1.08);
  pointer-events: none;
  user-select: none;
  -webkit-user-drag: none;
  display: block;
}

/* ── ATMOSPHERE OVERLAYS ──────────────────────── */
.scene-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at center, transparent 65%, rgba(6,7,10,0.55) 100%),
    linear-gradient(180deg, rgba(6,7,10,0.4) 0%, transparent 20%, transparent 80%, rgba(6,7,10,0.7) 100%);
  pointer-events: none;
}

/* Crimson portal void energy */
.portal-pulse-glow {
  position: absolute;
  top: 59%;
  left: 58%;
  transform: translate(-50%, -50%);
  width: 130px;
  height: 170px;
  background: radial-gradient(ellipse, rgba(185,28,28,0.4) 0%, rgba(185,28,28,0.1) 50%, transparent 80%);
  animation: crimsonPulse 3s ease-in-out infinite;
  pointer-events: none;
}
@keyframes crimsonPulse {
  0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.6; }
  50%       { transform: translate(-50%, -50%) scale(1.2); opacity: 1; }
}

/* Cyan/Runic candle shimmer at foreground fountain */
.fountain-shimmer-glow {
  position: absolute;
  top: 80%;
  left: 38%;
  transform: translate(-50%, -50%);
  width: 180px;
  height: 100px;
  background: radial-gradient(ellipse, rgba(56,189,248,0.25) 0%, transparent 70%);
  animation: fountainGlow 2.5s ease-in-out infinite alternate;
  pointer-events: none;
}
@keyframes fountainGlow {
  0%   { opacity: 0.3; }
  100% { opacity: 0.75; }
}

/* ── GOTHIC WELCOME BAR (Pinned below HUD) ───── */
.world-welcome-bar {
  position: absolute;
  top: calc(var(--hud-height, 60px) + 8px);
  left: 1.25rem;
  z-index: 20;
  pointer-events: auto;
}
.welcome-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-bone);
  border-left: 3px solid var(--aged-gold-dim);
  padding: 0.4rem 1rem;
  box-shadow: var(--shadow-gothic);
}
.welcome-greet {
  font-family: var(--font-gothic);
  font-style: italic;
  color: var(--text-muted);
  font-size: 0.88rem;
}
.welcome-name {
  font-family: var(--font-pixel);
  font-size: 7px;
  color: var(--aged-gold-bright);
  letter-spacing: 0.05em;
}
.welcome-streak {
  background: var(--crimson-void);
  border: 1px solid var(--crimson-deep);
  padding: 0.1rem 0.5rem;
  font-family: var(--font-pixel);
  font-size: 6px;
  color: #ff8060;
}

/* ── TOWN HOTSPOTS LAYER ─────────────────────── */
.town-hotspots-layer {
  position: absolute;
  inset: 0;
  z-index: 10;
  pointer-events: none;
}
.town-building {
  position: absolute;
  pointer-events: auto;
  cursor: pointer;
}
.building-hitbox {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s;
}
.town-building:hover .building-hitbox {
  transform: translateY(-4px);
}

/* ── GOTHIC PIXEL BUILDING BADGES ────────────── */
.building-badge-pill {
  position: relative;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  padding: 0.3rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  box-shadow: var(--shadow-gothic), inset 0 1px 0 rgba(255,255,255,0.04);
  transition: border-color 0.15s;
}
.town-building:hover .building-badge-pill {
  border-color: var(--bone-dim);
  box-shadow: var(--shadow-gothic), 0 0 12px rgba(200,185,154,0.15);
}
.building-icon {
  font-size: 1rem;
}
.building-name {
  font-family: var(--font-pixel);
  font-size: 7px;
  color: var(--bone-white);
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.badge-dot {
  width: 8px;
  height: 8px;
  background: var(--crimson-bright);
  border: 1px solid var(--gothic-void);
}
.badge-dot.pulse {
  animation: crimsonPulse 1.8s infinite;
}

.portal-swirl-fx,
.fountain-spray-fx {
  display: none;
}

/* ── HOTSPOT COORDINATES (aligned with gothic landmarks) ─── */
.spot-adventure {
  top: 57%; left: 58%;
  transform: translate(-50%, -50%);
  width: 200px; height: 180px;
}
.spot-adventure .building-badge-pill {
  margin-top: 110px;
  border-color: var(--crimson-deep);
}
.spot-adventure:hover .building-badge-pill { border-color: var(--crimson); }

.spot-quests {
  top: 54%; left: 45%;
  width: 140px; height: 120px;
}
.spot-quests .building-badge-pill { margin-top: 65px; border-color: var(--aged-gold-dim); }
.spot-quests:hover .building-badge-pill { border-color: var(--aged-gold); }

.spot-multiplayer {
  top: 48%; left: 16%;
  width: 200px; height: 180px;
}
.spot-multiplayer .building-badge-pill { margin-top: 110px; border-color: var(--shadow-teal); }

.spot-shop {
  top: 68%; left: 71%;
  width: 160px; height: 140px;
}
.spot-shop .building-badge-pill { margin-top: 85px; border-color: var(--arcane-dim); }

.spot-leaderboard {
  top: 32%; left: 82%;
  width: 160px; height: 120px;
}
.spot-leaderboard .building-badge-pill { margin-top: 65px; border-color: var(--aged-gold-dim); }

.spot-chronicle {
  top: 78%; left: 34%;
  transform: translate(-50%, -50%);
  width: 150px; height: 110px;
}
.spot-chronicle .building-badge-pill { margin-top: 70px; border-color: var(--shadow-teal); }

.spot-settings {
  top: 82%; left: 20%;
  width: 130px; height: 90px;
}
.spot-settings .building-badge-pill { margin-top: 45px; border-color: var(--ash-grey); }

.spot-special-event {
  top: 38%; left: 34%;
  width: 170px; height: 130px;
}
.spot-special-event .building-badge-pill {
  margin-top: 65px;
  border-color: #8b5cf6;
  background: rgba(26, 16, 48, 0.92);
}
.spot-special-event:hover .building-badge-pill {
  border-color: #c4b5fd;
  box-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
}
.trial-dot {
  background: #a855f7 !important;
}

/* ── GOTHIC MODULE DOCK (bottom nav) ─────────── */
.world-module-dock {
  position: absolute;
  bottom: 0.8rem;
  right: 1.2rem;
  display: flex;
  align-items: center;
  gap: 2px;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  border-top: 2px solid var(--gothic-border-bone);
  padding: 0.3rem 0.5rem;
  box-shadow: var(--shadow-gothic);
  z-index: 25;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  cursor: default;
}
.world-module-dock::-webkit-scrollbar { display: none; }

.module-dock-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.1rem;
  padding: 0.35rem 0.55rem;
  text-decoration: none;
  border: 1px solid transparent;
  transition: background 0.1s, border-color 0.1s;
  flex-shrink: 0;
  cursor: pointer;
}
.module-dock-item:hover,
.module-dock-item.active {
  background: var(--gothic-raised);
  border-color: var(--gothic-border-lit);
}
.dock-icon { font-size: 1.1rem; }
.dock-name {
  font-family: var(--font-pixel);
  font-size: 5px;
  color: var(--text-muted);
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.module-dock-item:hover .dock-name,
.module-dock-item.active .dock-name {
  color: var(--bone-white);
}

/* ── GOTHIC CHRONICLE TERMINAL (chat) ───────── */
.bottom-chat-terminal {
  position: absolute;
  bottom: 0.8rem;
  left: 1.2rem;
  width: 360px;
  max-width: calc(100vw - 2rem);
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  border-top: 2px solid var(--crimson-deep);
  padding: 0.4rem 0.6rem;
  box-shadow: var(--shadow-gothic);
  z-index: 25;
  transition: max-height 0.2s;
}
.bottom-chat-terminal.chat-expanded {
  width: 420px;
  max-height: 240px;
}
.chat-controls {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.25rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid var(--gothic-border);
}
.chat-ctrl-btn {
  width: 26px;
  height: 26px;
  background: var(--gothic-raised);
  border: 1px solid var(--gothic-border-lit);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.8rem;
  transition: background 0.1s;
}
.chat-ctrl-btn:hover { background: var(--gothic-elevated); }
.chat-time-stamp {
  margin-left: auto;
  font-family: var(--font-mono);
  font-size: 0.7rem;
  color: var(--text-muted);
  background: var(--gothic-void);
  padding: 0.1rem 0.4rem;
}
.chat-feed {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.chat-line {
  font-family: var(--font-mono);
  font-size: 0.74rem;
  line-height: 1.3;
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.chat-tag {
  font-weight: 700;
  font-size: 0.7rem;
}
.tag-sys    { color: var(--crimson-bright); }
.tag-world  { color: var(--aged-gold); }
.tag-player { color: var(--bone-white); }
.chat-input-row {
  margin-top: 0.4rem;
  display: flex;
  gap: 0.35rem;
}
.chat-input {
  flex: 1;
  background: var(--gothic-void);
  border: 1px solid var(--gothic-border-lit);
  color: var(--text-primary);
  font-family: var(--font-mono);
  font-size: 0.78rem;
  padding: 0.3rem 0.5rem;
  outline: none;
}
.chat-input:focus { border-color: var(--bone-dim); }
.chat-send-btn {
  background: var(--crimson-deep);
  border: 1px solid var(--crimson);
  color: var(--bone-white);
  font-family: var(--font-pixel);
  font-size: 6px;
  padding: 0 0.7rem;
  cursor: pointer;
  transition: background 0.1s;
}
.chat-send-btn:hover { background: var(--crimson); }

/* ── RESPONSIVE ───────────────────────────────── */

/* Tablet & Mobile (<= 1100px): panorama mode — town scene becomes wide & horizontally draggable */
@media (max-width: 1100px) {
  .rpg-world-hub {
    overflow-x: hidden;
    overflow-y: hidden;
  }

  /* Make the town scene scrollable horizontally as a wide panorama */
  .town-scene-wrapper {
    overflow-x: auto;
    overflow-y: hidden;
    width: 100%;
    height: 100%;
    cursor: grab;
    touch-action: pan-x;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .town-scene-wrapper::-webkit-scrollbar { display: none; }
  .town-scene-wrapper:active { cursor: grabbing; }

  /* Fixed canvas width: keeps entire pixel art city & hotspot coordinates in perfect sync */
  .town-scene-canvas {
    width: 1200px;
    min-width: 1200px;
    height: 100%;
    flex-shrink: 0;
  }

  .town-scene-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 46%;
  }

  .town-hotspots-layer {
    width: 100%;
    height: 100%;
  }

  /* Dock: bottom full width, horizontal draggable scroll */
  .world-module-dock {
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-bottom: none;
    border-top: 2px solid var(--gothic-border-bone);
    padding: 0.3rem 0.6rem;
    justify-content: flex-start;
    gap: 0;
    cursor: grab;
    touch-action: pan-x;
  }
  .world-module-dock:active { cursor: grabbing; }
  .bottom-chat-terminal { display: none; }

  .world-welcome-bar {
    top: calc(var(--hud-height, 60px) + 8px);
    left: 0.75rem;
  }
}

/* Mobile phones (<= 650px) */
@media (max-width: 650px) {
  .world-welcome-bar {
    top: calc(var(--hud-height, 54px) + 6px);
    left: 0.5rem;
  }
  .welcome-greet { font-size: 0.78rem; }
  .welcome-name { font-size: 6px; }

  .module-dock-item { padding: 0.3rem 0.45rem; }
  .dock-icon { font-size: 1rem; }
  .dock-name { font-size: 5px; }
}

@media (max-width: 420px) {
  .dock-name { display: none; }
  .module-dock-item { padding: 0.35rem 0.5rem; }
}
</style>