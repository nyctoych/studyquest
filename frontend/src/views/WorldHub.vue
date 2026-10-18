<template>
  <div
    class="rpg-world-hub"
    :class="[
      `dimension-${currentDimension}`,
      `theme-${currentTheme}`,
      { 'is-transitioning': isTransitioning }
    ]"
    tabindex="0"
    @keydown="onKeyDown"
  >


    <!-- ── 2. WELCOME PLAYER PILL (Left anchor below HUD) ─────── -->
    <div class="world-welcome-bar animate-slide-down">
      <div class="welcome-pill">
        <span class="welcome-greet">Welcome back,</span>
        <strong class="welcome-name">{{ auth.nickname || auth.user?.username || 'Scholar' }}</strong>!
        <span v-if="auth.streak > 0" class="welcome-streak flex items-center gap-1.5">
          <PixelEmoji emoji="fire" size="sm" /> {{ auth.streak }}-day streak
        </span>
      </div>
    </div>

    <!-- ── 3. WORLD CANVAS & PANORAMA ENVIRONMENT ─────────────── -->
    <div
      class="world-dimension-viewport"
      ref="viewportRef"
      :style="{
        backgroundImage: `url('${activeBackground}')`
      }"
      @touchstart="onTouchStart"
      @touchend="onTouchEnd"
    >
      <Transition :name="transitionAnimName">
        <div
          :key="`${currentDimension}-${currentTheme}`"
          class="dimension-scene"
          :class="`scene-${currentDimension}`"
          :style="{
            backgroundImage: `url('${activeBackground}')`
          }"
        >
          <!-- Background Image Layer -->
          <img
            :src="activeBackground"
            :alt="activeDimensionData.title"
            class="dimension-bg-img"
            draggable="false"
            @error="onImageError"
          />

          <!-- Atmospheric Lighting & Fog Overlays -->
          <div class="dimension-vignette"></div>
          <div v-if="currentTheme === 'night'" class="dimension-night-particles" aria-hidden="true">
            <div class="ambient-glow-core"></div>
          </div>
          <div v-else class="dimension-morning-sunbeam" aria-hidden="true"></div>

          <!-- Interactive Module Hotspots for this Dimension -->
          <div class="dimension-hotspots-layer">
            <div
              v-for="spot in activeDimensionData.hotspots"
              :key="spot.id"
              class="world-hotspot-building"
              :class="`spot-${spot.id}`"
              :style="spot.style"
              @click="handleSpotClick(spot)"
              @mouseenter="hoveredSpot = spot.id"
              @mouseleave="hoveredSpot = null"
            >
              <div class="building-hitbox">
                <!-- SPECIAL PHYSICAL PARCHMENT SCROLL ENTRANCE FOR HALL OF FAME -->
                <div
                  v-if="spot.id === 'hall-of-fame'"
                  class="physical-parchment-scroll-entrance"
                  :class="{ 'is-hovered': hoveredSpot === 'hall-of-fame' }"
                >
                  <!-- Top Wooden Roller Spindle -->
                  <div class="scroll-rod rod-top">
                    <div class="rod-finial finial-left">
                      <span class="finial-ball"></span>
                      <span class="finial-collar"></span>
                    </div>
                    <div class="rod-spindle">
                      <div class="rod-paper-roll"></div>
                    </div>
                    <div class="rod-finial finial-right">
                      <span class="finial-collar"></span>
                      <span class="finial-ball"></span>
                    </div>
                  </div>

                  <!-- Unrolled Ancient Parchment Body -->
                  <div class="scroll-body-sheet">
                    <!-- Deckled paper edge overlays -->
                    <div class="scroll-aged-overlay"></div>
                    <div class="scroll-edge-curl left"></div>
                    <div class="scroll-edge-curl right"></div>
                    <div class="scroll-crease crease-top"></div>
                    <div class="scroll-crease crease-mid"></div>

                    <!-- Wax Seal with Ribbon -->
                    <div class="scroll-wax-seal">
                      <div class="seal-stamp">
                        <PixelEmoji emoji="crown" size="xs" />
                      </div>
                      <div class="seal-ribbon"></div>
                    </div>

                    <!-- Inscribed Manuscript Text -->
                    <div class="scroll-manuscript-text">
                      <div class="scroll-crest-row">
                        <span class="scroll-flourish">❦</span>
                        <span class="scroll-crest-label font-pixel">{{ hofLatestBatch ? `${hofLatestBatch.batch_name.toUpperCase()} EXAM` : 'BLEPP BOARD OF HONOR' }}</span>
                        <span class="scroll-flourish">❦</span>
                      </div>
                      <div class="scroll-title-row">
                        <span class="scroll-star-glyph">✦</span>
                        <h4 class="scroll-title-text font-pixel">{{ spot.name.toUpperCase() }}</h4>
                        <span class="scroll-star-glyph">✦</span>
                      </div>

                      <!-- Real-Time Passer Inscription Display (1 by 1 displaying and fading) -->
                      <div
                        class="scroll-passer-stage"
                        @mouseenter="isHofTickerPaused = true"
                        @mouseleave="isHofTickerPaused = false"
                      >
                        <Transition name="passer-fade" mode="out-in">
                          <div
                            v-if="currentPasser"
                            :key="currentPasser.id || currentPasser.name"
                            class="passer-fading-card"
                          >
                            <div v-if="currentPasser.rank_badge" class="passer-rank-ribbon font-pixel">
                              👑 {{ currentPasser.rank_badge }}
                            </div>
                            <div v-else class="passer-player-tag font-pixel">
                              ✦ VERIFIED PASSER ✦
                            </div>
                            <div v-if="currentPasser.username" class="passer-username-display font-pixel">
                              <span class="handle-at">@</span>{{ currentPasser.username }}
                            </div>
                            <div class="passer-name-display font-pixel">{{ currentPasser.name.trim() }}<span class="passer-cred-badge font-pixel">, {{ currentPasser.credential || 'RPm' }}</span></div>
                            <div class="passer-school-sub">
                              {{ currentPasser.school }}
                            </div>
                          </div>
                          <div v-else class="passer-fading-card">
                            <p class="scroll-subtitle-text">
                              {{ spot.subtitle }}
                            </p>
                          </div>
                        </Transition>
                      </div>

                      <!-- Glowing Live Announcements Indicator -->
                      <div class="scroll-live-badge">
                        <span class="scroll-pulse-dot"></span>
                        <span class="font-pixel scroll-badge-label">
                          PRC VERIFIED · {{ hofPassers.length > 0 ? `${(hofCurrentPasserIndex % hofPassers.length) + 1}/${hofPassers.length}` : 'LIVE ROSTER' }}
                        </span>
                      </div>

                      <div class="scroll-action-hint font-pixel">
                        <span class="hint-pulse">▶</span>
                        <span>CLICK TO UNROLL ROSTER</span>
                        <span class="hint-pulse">◀</span>
                      </div>
                    </div>
                  </div>

                  <!-- Bottom Wooden Roller Spindle -->
                  <div class="scroll-rod rod-bottom">
                    <div class="rod-finial finial-left">
                      <span class="finial-ball"></span>
                      <span class="finial-collar"></span>
                    </div>
                    <div class="rod-spindle">
                      <div class="rod-paper-roll"></div>
                    </div>
                    <div class="rod-finial finial-right">
                      <span class="finial-collar"></span>
                      <span class="finial-ball"></span>
                    </div>
                  </div>

                  <!-- Hanging Royal Silk Ribbon Tails -->
                  <div class="scroll-ribbon-tails">
                    <div class="ribbon-tail tail-crimson"></div>
                    <div class="ribbon-tail tail-gold"></div>
                  </div>
                </div>

                <!-- Default building badge pill for other buildings -->
                <div v-else class="building-badge-pill" :class="[spot.pillClass, { 'is-locked-spot': isSpotLocked(spot) }]">
                  <span class="building-icon">
                    <PixelEmoji v-if="!isSpotLocked(spot)" :emoji="spot.icon" size="sm" />
                    <span v-else class="lock-icon-glyph">🔒</span>
                  </span>
                  <div class="building-text-col">
                    <span class="building-name">{{ spot.name }}</span>
                    <span v-if="isSpotLocked(spot)" class="building-sub lock-sub font-pixel">UNLOCKS AT LVL 15</span>
                    <span v-else-if="spot.subtitle" class="building-sub">{{ spot.subtitle }}</span>
                  </div>
                  <span v-if="isSpotLocked(spot)" class="badge-lock-tag font-pixel">LV.15</span>
                  <span v-else-if="spot.dotClass" class="badge-dot" :class="spot.dotClass"></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ── 4. HORIZONTAL WORLD NAVIGATION SIDE CONTROLS ───────── -->
    <!-- Left Travel Button: Available only on MAIN (Main -> Review) -->
    <div
      v-if="currentDimension === 'main'"
      class="world-side-nav side-nav-left animate-slide-right"
      :class="{ 'guide-pulse': showGuideModal }"
      @click="goToDimension('review')"
      role="button"
      title="Travel West to Training Grounds & Arena"
    >
      <div class="side-nav-card">
        <span class="nav-arrow-symbol">◀</span>
        <div class="nav-meta">
          <span class="nav-tag font-pixel">TRAVEL WEST</span>
          <span class="nav-dest font-pixel">REVIEW</span>
          <span class="nav-hint">Arena & Trials</span>
        </div>
      </div>
    </div>

    <!-- Right Travel Button: Available on REVIEW (Review -> Main) and MAIN (Main -> Others) -->
    <div
      v-if="currentDimension === 'review'"
      class="world-side-nav side-nav-right animate-slide-left"
      :class="{ 'guide-pulse': showGuideModal }"
      @click="goToDimension('main')"
      role="button"
      title="Return East to Central Town Hall"
    >
      <div class="side-nav-card">
        <div class="nav-meta text-right">
          <span class="nav-tag font-pixel">RETURN EAST</span>
          <span class="nav-dest font-pixel">MAIN HUB</span>
          <span class="nav-hint">Central Town Hall</span>
        </div>
        <span class="nav-arrow-symbol">▶</span>
      </div>
    </div>

    <div
      v-if="currentDimension === 'main'"
      class="world-side-nav side-nav-right animate-slide-left"
      :class="{ 'guide-pulse': showGuideModal }"
      @click="goToDimension('others')"
      role="button"
      title="Travel East to Marketplace & Castle Archives"
    >
      <div class="side-nav-card">
        <div class="nav-meta text-right">
          <span class="nav-tag font-pixel">TRAVEL EAST</span>
          <span class="nav-dest font-pixel">OTHERS</span>
          <span class="nav-hint">Market & Leaderboards</span>
        </div>
        <span class="nav-arrow-symbol">▶</span>
      </div>
    </div>

    <!-- Left Travel Button: Available only on OTHERS (Others -> Main) -->
    <div
      v-if="currentDimension === 'others'"
      class="world-side-nav side-nav-left animate-slide-right"
      :class="{ 'guide-pulse': showGuideModal }"
      @click="goToDimension('main')"
      role="button"
      title="Return West to Central Town Hall"
    >
      <div class="side-nav-card">
        <span class="nav-arrow-symbol">◀</span>
        <div class="nav-meta">
          <span class="nav-tag font-pixel">RETURN WEST</span>
          <span class="nav-dest font-pixel">MAIN HUB</span>
          <span class="nav-hint">Central Town Hall</span>
        </div>
      </div>
    </div>

    <!-- ── 5. FAST RPG ZONE BANNER NOTIFICATION ───────────────── -->
    <Transition name="zone-banner">
      <div v-if="showZoneBanner" class="rpg-zone-banner">
        <span class="zone-location-tag font-pixel">ENTERED REGION</span>
        <h2 class="zone-name font-pixel">{{ activeDimensionData.title.toUpperCase() }}</h2>
        <span class="zone-sub">{{ activeDimensionData.subtitle }}</span>
      </div>
    </Transition>

    <!-- ── 6. REALM NAVIGATION GUIDE FADING MODAL ─────────────── -->
    <Transition name="guide-fade">
      <div
        v-if="showGuideModal"
        class="realm-guide-overlay"
        @click.self="dismissGuideModal"
      >
        <div
          class="realm-guide-card gothic-panel"
          @click.stop
        >
          <div class="guide-corner tl"></div>
          <div class="guide-corner tr"></div>
          <div class="guide-corner bl"></div>
          <div class="guide-corner br"></div>

          <div class="guide-header-strip">
            <div class="guide-badge font-pixel">
              <span class="guide-icon">🧭</span>
              <span>REALM EXPLORATION GUIDE</span>
            </div>
            <button class="guide-close-btn" @click="dismissGuideModal" title="Close Guide">✕</button>
          </div>

          <h3 class="guide-headline font-pixel">DISCOVER ALL WORLD MODULES</h3>
          <p class="guide-subtext">
            Use the <strong>Left (◀)</strong> and <strong>Right (▶)</strong> side buttons to travel between sectors:
          </p>

          <div class="guide-sectors-display">
            <div
              class="guide-sector-box"
              :class="{ 'is-active': currentDimension === 'review' }"
              @click="goToDimension('review')"
              title="Click to view Review sector"
            >
              <div class="sector-head font-pixel">◀ WEST</div>
              <div class="sector-title font-pixel">REVIEW</div>
              <div class="sector-tags">⚡ Arena · 👑 Test Your Knowledge</div>
            </div>

            <div
              class="guide-sector-box"
              :class="{ 'is-active': currentDimension === 'main' }"
              @click="goToDimension('main')"
              title="Click to view Main Hub sector"
            >
              <div class="sector-head font-pixel">● CENTER</div>
              <div class="sector-title font-pixel">MAIN HUB</div>
              <div class="sector-tags">⚔️ Adventure · 📜 Study Hall · 📚 Vault · 🏆 Achievements · 👑 Hall of Fame</div>
            </div>

            <div
              class="guide-sector-box"
              :class="{ 'is-active': currentDimension === 'others' }"
              @click="goToDimension('others')"
              title="Click to view Others sector"
            >
              <div class="sector-head font-pixel">EAST ▶</div>
              <div class="sector-title font-pixel">OTHERS</div>
              <div class="sector-tags">🛒 Market · 👑 Leaderboards</div>
            </div>
          </div>

          <div class="guide-footer-row">
            <span class="guide-tip-note font-pixel">✦ Click arrows ◀ ▶ or use [← / →] arrow keys to travel ✦</span>
            <button class="btn btn-gothic-crimson btn-sm font-pixel guide-confirm-btn" @click="dismissGuideModal">
              GOT IT ▸
            </button>
          </div>

          <!-- Auto-fade progress countdown bar -->
          <div class="guide-timer-track">
            <div class="guide-timer-bar"></div>
          </div>
        </div>
      </div>
    </Transition>


  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import { useTransitionStore } from '../stores/transition'
import { useAdventureStore } from '../stores/adventure'
import { resolveAsset } from '../services/bossMapping'
import { WORLD_BACKGROUNDS, resolveWorldBg, resolveWorldBackground } from '../config/worldThemeConfig'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import { hallOfFameService } from '../services/hallOfFameService'
import { battleAudio } from '../services/battleAudio'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const game = useGameStore()
const transitionStore = useTransitionStore()
const adventure = useAdventureStore()

// ── THREE DIMENSIONS DEFINITION ─────────────────────────────
const DIMENSIONS = {
  review: {
    id: 'review',
    title: WORLD_BACKGROUNDS.review.title,
    subtitle: WORLD_BACKGROUNDS.review.subtitle,
    backgroundNight: WORLD_BACKGROUNDS.review.night,
    backgroundMorning: WORLD_BACKGROUNDS.review.morning,
    hotspots: [
      {
        id: 'arena',
        name: 'Arena',
        subtitle: 'Multiplayer Colosseum',
        path: '/multiplayer',
        icon: 'lightning',
        style: { top: '48%', left: '26%' },
        pillClass: 'spot-arena-pill'
      },
      {
        id: 'special-event',
        name: 'Test Your Knowledge',
        subtitle: 'Mock Board Exam Simulator',
        path: '/special-event',
        icon: 'crown',
        style: { top: '68%', left: '60%' },
        pillClass: 'special-event-pill',
        dotClass: 'trial-dot pulse'
      }
    ]
  },
  main: {
    id: 'main',
    title: WORLD_BACKGROUNDS.main.title,
    subtitle: WORLD_BACKGROUNDS.main.subtitle,
    backgroundNight: WORLD_BACKGROUNDS.main.night,
    backgroundMorning: WORLD_BACKGROUNDS.main.morning,
    hotspots: [
      {
        id: 'studyhall',
        name: 'Study Hall',
        subtitle: 'Curriculum & Archives',
        path: '/studyhall',
        icon: 'scroll',
        style: { top: '44%', left: '50%' },
        pillClass: 'studyhall-pill'
      },
      {
        id: 'adventure',
        name: 'Adventure Gate',
        subtitle: 'Storyline & Boss Battles',
        path: '/adventure',
        icon: 'sword',
        style: { top: '66%', left: '50%' },
        pillClass: 'adventure-pill',
        dotClass: 'pulse'
      },
      {
        id: 'vault',
        name: 'Vault',
        subtitle: 'Manuscript Archive',
        path: '/vault',
        icon: 'book',
        style: { top: '56%', left: '16%' },
        pillClass: 'vault-badge',
        dotClass: 'pulse vault-dot'
      },
      {
        id: 'achievements',
        name: 'Achievement Hall',
        subtitle: 'Trophies & Badges',
        path: '/achievements',
        icon: 'trophy',
        style: { top: '72%', left: '76%' },
        pillClass: 'achievements-pill',
        dotClass: 'pulse achievements-dot'
      },
      {
        id: 'hall-of-fame',
        name: 'Hall of Fame',
        subtitle: 'Celebrating Verified Board Passers',
        path: '/hall-of-fame',
        icon: 'crown',
        style: { top: '26%', left: '88%' },
        pillClass: 'hall-of-fame-pill',
        dotClass: 'pulse fame-dot'
      }
    ]
  },
  others: {
    id: 'others',
    title: WORLD_BACKGROUNDS.others.title,
    subtitle: WORLD_BACKGROUNDS.others.subtitle,
    backgroundNight: WORLD_BACKGROUNDS.others.night,
    backgroundMorning: WORLD_BACKGROUNDS.others.morning,
    hotspots: [
      {
        id: 'market',
        name: 'Market',
        subtitle: 'Merchant Bazaar',
        path: '/market',
        icon: 'cart',
        style: { top: '68%', left: '30%' },
        pillClass: 'market-pill'
      },
      {
        id: 'records',
        name: 'Leaderboards',
        subtitle: 'Leaderboard & Rankings',
        path: '/leaderboard',
        icon: 'crown',
        style: { top: '56%', left: '68%' },
        pillClass: 'records-pill'
      }
    ]
  }
}

// ── STATE ───────────────────────────────────────────────────
const currentDimension = ref('main') // default is always 'main'
const transitionDirection = ref('east') // 'east' | 'west'
const isTransitioning = ref(false)
const showZoneBanner = ref(false)
const showGuideModal = ref(false)
const hoveredSpot = ref(null)
const viewportRef = ref(null)
let guideTimer = null

const currentTheme = computed(() => game.theme || 'night')

const activeDimensionData = computed(() => DIMENSIONS[currentDimension.value] || DIMENSIONS.main)

const activeBackground = computed(() => {
  return resolveWorldBg(currentDimension.value, currentTheme.value)
})

const activeRoute = computed(() => route.path)

const transitionAnimName = computed(() => {
  return transitionDirection.value === 'west' ? 'slide-west' : 'slide-east'
})

// ── NAVIGATION BETWEEN 3 DIMENSIONS ─────────────────────────
let bannerTimeout = null

function triggerZoneBanner() {
  showZoneBanner.value = true
  if (bannerTimeout) clearTimeout(bannerTimeout)
  bannerTimeout = setTimeout(() => {
    showZoneBanner.value = false
  }, 1800)
}

function triggerGuideModal() {
  showGuideModal.value = true
  if (guideTimer) clearTimeout(guideTimer)
  guideTimer = setTimeout(() => {
    showGuideModal.value = false
  }, 3000)
}

function dismissGuideModal() {
  if (guideTimer) clearTimeout(guideTimer)
  showGuideModal.value = false
}

function goToDimension(target) {
  dismissGuideModal()
  if (isTransitioning.value || target === currentDimension.value) return

  try {
    battleAudio.playSelect()
  } catch {}

  // Enforce RPG rules: no direct Review <-> Others without routing through Main
  if (
    (currentDimension.value === 'review' && target === 'others') ||
    (currentDimension.value === 'others' && target === 'review')
  ) {
    // Force route through Main first
    target = 'main'
  }

  // Determine horizontal travel direction
  if (currentDimension.value === 'main') {
    transitionDirection.value = target === 'review' ? 'west' : 'east'
  } else if (currentDimension.value === 'review') {
    transitionDirection.value = 'east' // Returning east to main
  } else if (currentDimension.value === 'others') {
    transitionDirection.value = 'west' // Returning west to main
  }

  isTransitioning.value = true
  currentDimension.value = target

  triggerZoneBanner()

  setTimeout(() => {
    isTransitioning.value = false
  }, 450)
}

// Keyboard arrow navigation
function onKeyDown(e) {
  if (e.key === 'Escape' && showGuideModal.value) {
    dismissGuideModal()
    return
  }
  if (e.key === 'ArrowLeft') {
    dismissGuideModal()
    if (currentDimension.value === 'main') goToDimension('review')
    else if (currentDimension.value === 'others') goToDimension('main')
  } else if (e.key === 'ArrowRight') {
    dismissGuideModal()
    if (currentDimension.value === 'main') goToDimension('others')
    else if (currentDimension.value === 'review') goToDimension('main')
  }
}

// Mobile touch swipe gestures
let touchStartX = 0
function onTouchStart(e) {
  if (e.touches && e.touches.length === 1) {
    touchStartX = e.touches[0].clientX
  }
}
function onTouchEnd(e) {
  if (e.changedTouches && e.changedTouches.length === 1) {
    const dx = e.changedTouches[0].clientX - touchStartX
    if (Math.abs(dx) > 60) {
      if (dx > 0) {
        // Swiped right -> travel West
        if (currentDimension.value === 'main') goToDimension('review')
        else if (currentDimension.value === 'others') goToDimension('main')
      } else {
        // Swiped left -> travel East
        if (currentDimension.value === 'main') goToDimension('others')
        else if (currentDimension.value === 'review') goToDimension('main')
      }
    }
  }
}

// ── MODULE INTERACTION & TRAVEL ─────────────────────────────
function isSpotLocked(spot) {
  if (!spot) return false
  const spotId = typeof spot === 'object' ? spot.id : null
  const path = typeof spot === 'object' ? spot.path : spot
  if (spotId === 'arena' || path === '/multiplayer' || path === '/arena') {
    const userLevel = Number(auth.user?.level || 1)
    return userLevel < 15
  }
  return false
}

function handleSpotClick(spot) {
  if (isTransitioning.value) return
  const path = typeof spot === 'string' ? spot : spot.path
  const spotId = typeof spot === 'object' ? spot.id : null

  if (spotId === 'arena' || path === '/multiplayer' || path === '/arena') {
    const userLevel = Number(auth.user?.level || 1)
    if (userLevel < 15) {
      battleAudio.playError?.()
      toast.warning(`Colosseum Locked: You must reach Level 15 to enter the Arena. (Your Level: ${userLevel})`)
      return
    }
  }

  try {
    battleAudio.playSelect()
  } catch {}

  if (path === '/adventure') {
    const chId = adventure.currentUnlockedChapterId || Number(localStorage.getItem('sq_active_chapter')) || Number(localStorage.getItem('sq_unlocked_chapter')) || 1
    transitionStore.triggerTravel(`/adventure/${chId}`, router)
    return
  }
  transitionStore.triggerTravel(path, router)
}



// ── BACKGROUND PRELOADING & RESILIENCE ─────────────────────
function preloadAllWorldBackgrounds() {
  const imagesToPreload = [
    resolveWorldBg('main', 'night'),
    resolveWorldBg('main', 'morning'),
    resolveWorldBg('review', 'night'),
    resolveWorldBg('review', 'morning'),
    resolveWorldBg('others', 'night'),
    resolveWorldBg('others', 'morning'),
  ]
  imagesToPreload.forEach(src => {
    if (!src) return
    const img = new Image()
    img.src = src
  })
}

function onImageError(e) {
  console.warn('[WorldHub] Background image failed to load:', e?.target?.src)
}

// Watch for navigation directly back to /world to trigger guide
watch(
  () => route.path,
  (newPath) => {
    if (newPath === '/world') {
      triggerGuideModal()
    }
  }
)

// ── HALL OF FAME SCROLL REAL-TIME PASSER TICKER ─────────────
const hofPassers = ref([])
const hofLatestBatch = ref(null)
const hofCurrentPasserIndex = ref(0)
const isHofTickerPaused = ref(false)
let hofTickerTimer = null
let hofPollingTimer = null

const currentPasser = computed(() => {
  if (!hofPassers.value || hofPassers.value.length === 0) return null
  return hofPassers.value[hofCurrentPasserIndex.value % hofPassers.value.length]
})

async function fetchHofPassers() {
  try {
    const res = await hallOfFameService.getLatestPassers(50)
    if (res.data?.success && Array.isArray(res.data?.passers)) {
      hofPassers.value = res.data.passers
      hofLatestBatch.value = res.data.batch
    }
  } catch (err) {
    console.warn('Could not fetch latest board passers ticker:', err)
  }
}

function startHofTicker() {
  if (hofTickerTimer) clearInterval(hofTickerTimer)
  hofTickerTimer = setInterval(() => {
    if (!isHofTickerPaused.value && hofPassers.value.length > 1) {
      hofCurrentPasserIndex.value = (hofCurrentPasserIndex.value + 1) % hofPassers.value.length
    }
  }, 3600) // 3.6s per passer display and fade
}

// ── LIFECYCLE ───────────────────────────────────────────────
onMounted(() => {
  adventure.loadChapters()
  window.addEventListener('keydown', onKeyDown)
  preloadAllWorldBackgrounds()
  triggerZoneBanner()
  triggerGuideModal()

  // Real-time board passers ticker on physical parchment scroll
  fetchHofPassers().then(() => {
    startHofTicker()
  })
  hofPollingTimer = setInterval(fetchHofPassers, 30000)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onKeyDown)
  if (bannerTimeout) clearTimeout(bannerTimeout)
  if (guideTimer) clearTimeout(guideTimer)
  if (hofTickerTimer) clearInterval(hofTickerTimer)
  if (hofPollingTimer) clearInterval(hofPollingTimer)
})
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════
   1. WORLD HUB ROOT CONTAINER
   ══════════════════════════════════════════════════════════════ */
.rpg-world-hub {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: #080a0e;
  user-select: none;
  outline: none;
}

/* ══════════════════════════════════════════════════════════════
   2. TOP DIMENSION NAVIGATOR / WORLD BREADCRUMB
   ══════════════════════════════════════════════════════════════ */
.world-dimension-bar {
  position: absolute;
  top: calc(var(--hud-height, 60px) + 8px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 25;
  pointer-events: auto;
}

.dimension-nav-track {
  display: flex;
  align-items: center;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  box-shadow: var(--shadow-gothic);
  padding: 0.25rem 0.5rem;
  gap: 0.35rem;
}

.dimension-tab {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.75rem;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--text-muted);
}

.dimension-tab:hover {
  background: var(--gothic-raised);
  color: var(--text-primary);
}

.dimension-tab.active {
  background: var(--gothic-elevated);
  border: 1px solid var(--aged-gold);
  color: var(--aged-gold-shine);
  box-shadow: 0 0 10px rgba(184, 134, 11, 0.25);
}

.tab-main.active {
  border-color: var(--aged-gold-bright);
}

.dim-name {
  font-size: 8px;
  letter-spacing: 0.06em;
}

.dim-sub {
  font-family: var(--font-gothic);
  font-size: 0.75rem;
  opacity: 0.8;
  display: none;
}

@media (min-width: 900px) {
  .dim-sub { display: inline; }
}

.dim-arrow-icon {
  font-size: 8px;
  opacity: 0.7;
}

.dim-hub-dot {
  font-size: 10px;
  color: var(--aged-gold-bright);
}

.dim-track-connector {
  width: 16px;
  height: 2px;
  background: var(--gothic-border);
  transition: background 0.2s ease;
}

.dim-track-connector.lit {
  background: var(--aged-gold-dim);
}

/* ══════════════════════════════════════════════════════════════
   3. WELCOME PILL (Below Top Left HUD)
   ══════════════════════════════════════════════════════════════ */
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
  padding: 0.4rem 0.9rem;
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

/* ══════════════════════════════════════════════════════════════
   4. WORLD VIEWPORT & 3D DIMENSION TRANSITIONS
   ══════════════════════════════════════════════════════════════ */
.world-dimension-viewport {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
  z-index: 1;
}

.dimension-scene {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background-size: cover;
  background-position: center center;
  background-repeat: no-repeat;
}

.dimension-bg-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  image-rendering: pixelated;
  image-rendering: crisp-edges;
  filter: contrast(1.05) saturate(1.06);
  pointer-events: none;
  user-select: none;
  display: block;
  z-index: 1;
}

.dimension-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 65%, rgba(6, 7, 10, 0.45) 100%);
  pointer-events: none;
  z-index: 2;
}

.theme-morning .dimension-vignette {
  background: radial-gradient(ellipse at center, transparent 75%, rgba(120, 95, 60, 0.15) 100%);
}

.dimension-morning-sunbeam {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(254, 240, 138, 0.08) 0%, transparent 40%, rgba(217, 119, 6, 0.05) 100%);
  pointer-events: none;
}

/* ── 300-600ms HORIZONTAL SLIDE TRANSITION ───────────── */
.slide-east-enter-active,
.slide-east-leave-active,
.slide-west-enter-active,
.slide-west-leave-active {
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
}

.slide-east-enter-from {
  transform: translateX(35%);
  opacity: 0.1;
}

.slide-east-leave-to {
  transform: translateX(-35%);
  opacity: 0.1;
}

.slide-west-enter-from {
  transform: translateX(-35%);
  opacity: 0.1;
}

.slide-west-leave-to {
  transform: translateX(35%);
  opacity: 0.1;
}

/* ══════════════════════════════════════════════════════════════
   5. INTERACTIVE HOTSPOTS LAYER
   ══════════════════════════════════════════════════════════════ */
.dimension-hotspots-layer {
  position: absolute;
  inset: 0;
  z-index: 10;
  pointer-events: none;
}

.world-hotspot-building {
  position: absolute;
  pointer-events: auto;
  cursor: pointer;
  transform: translate(-50%, -50%);
}

.building-hitbox {
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.world-hotspot-building:hover .building-hitbox {
  transform: translateY(-5px) scale(1.04);
}

.building-badge-pill {
  position: relative;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  padding: 0.4rem 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  box-shadow: var(--shadow-gothic), inset 0 1px 0 rgba(255, 255, 255, 0.05);
  transition: all 0.18s ease;
}

.world-hotspot-building:hover .building-badge-pill {
  border-color: var(--aged-gold-bright);
  box-shadow: var(--shadow-gothic), 0 0 14px rgba(217, 119, 6, 0.3);
}

.building-badge-pill.is-locked-spot {
  border-color: rgba(239, 68, 68, 0.45);
  background: rgba(15, 23, 42, 0.88);
}

.world-hotspot-building:hover .building-badge-pill.is-locked-spot {
  border-color: rgba(239, 68, 68, 0.85);
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.35);
}

.lock-sub {
  color: #f87171 !important;
  font-weight: 700;
  letter-spacing: 0.05em;
  font-size: 7px;
}

.badge-lock-tag {
  font-size: 7px;
  padding: 0.1rem 0.3rem;
  border-radius: 3px;
  background: rgba(239, 68, 68, 0.25);
  border: 1px solid rgba(239, 68, 68, 0.5);
  color: #fca5a5;
  margin-left: 0.2rem;
}

.lock-icon-glyph {
  font-size: 0.95rem;
  line-height: 1;
}

.building-icon {
  font-size: 1.1rem;
  line-height: 1;
}

.building-text-col {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.building-name {
  font-family: var(--font-pixel);
  font-size: 8px;
  color: var(--bone-white);
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.building-sub {
  font-family: var(--font-gothic);
  font-size: 0.72rem;
  color: var(--text-muted);
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

.badge-dot.trial-dot {
  background: #f59e0b;
}

.badge-dot.vault-dot {
  background: #3b82f6;
}

.badge-dot.achievements-dot {
  background: #eab308;
}

.badge-dot.fame-dot {
  background: #fbbf24;
  box-shadow: 0 0 8px #fbbf24;
}

/* ══════════════════════════════════════════════════════════════
   PHYSICAL PARCHMENT SCROLL — HALL OF FAME CELESTIAL ENTRANCE
   ══════════════════════════════════════════════════════════════ */
.physical-parchment-scroll-entrance {
  position: relative;
  width: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  filter: drop-shadow(0 10px 26px rgba(0, 0, 0, 0.85)) drop-shadow(0 0 20px rgba(251, 191, 36, 0.5));
  animation: celestialScrollFloat 4.6s ease-in-out infinite;
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.25s ease;
  user-select: none;
}

.physical-parchment-scroll-entrance:hover {
  transform: translateY(-8px) scale(1.05);
  filter: drop-shadow(0 16px 36px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 30px rgba(251, 191, 36, 0.85));
}

@keyframes celestialScrollFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

/* Wooden Roller Spindles (Top and Bottom) */
.scroll-rod {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 6;
}

.scroll-rod.rod-top {
  margin-bottom: -5px;
}

.scroll-rod.rod-bottom {
  margin-top: -5px;
}

.rod-spindle {
  flex: 1;
  height: 15px;
  background: linear-gradient(180deg, #4a2109 0%, #7c3e16 35%, #4a2109 75%, #240e03 100%);
  border-top: 1px solid #a65824;
  border-bottom: 1px solid #140701;
  box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.25), 0 3px 8px rgba(0, 0, 0, 0.7);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Rolled Cylinder of Parchment Paper around Spindle */
.rod-paper-roll {
  width: 90%;
  height: 12px;
  border-radius: 6px;
  background: linear-gradient(180deg, #ffffff 0%, #f6e8cc 30%, #e5cf9d 65%, #9e7f47 100%);
  border-top: 1px solid #fffdf7;
  border-bottom: 1px solid #73592a;
  box-shadow: inset 0 2px 2px rgba(255, 255, 255, 0.8), 0 2px 4px rgba(0, 0, 0, 0.45);
}

/* Turned Wooden Finials (Spherical ends of wooden rods) */
.rod-finial {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  position: relative;
  z-index: 7;
}

.finial-ball {
  width: 17px;
  height: 17px;
  border-radius: 50%;
  background: radial-gradient(circle at 35% 30%, #ffd700 0%, #b45309 45%, #451a03 90%);
  border: 1px solid #290f02;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.7), inset 0 2px 3px rgba(255, 255, 255, 0.6);
  flex-shrink: 0;
}

.finial-collar {
  width: 5px;
  height: 11px;
  background: linear-gradient(180deg, #ffd700 0%, #b45309 50%, #78350f 100%);
  border: 1px solid #3d1704;
  flex-shrink: 0;
}

.finial-left {
  flex-direction: row;
  margin-right: -2px;
}

.finial-right {
  flex-direction: row;
  margin-left: -2px;
}

/* Unrolled Ancient Parchment Body Sheet */
.scroll-body-sheet {
  position: relative;
  width: 86%;
  min-height: 125px;
  background: linear-gradient(180deg, #fcf6e8 0%, #f7edd4 20%, #f0dcab 65%, #e1c487 100%);
  border-left: 2.5px solid #9c7b41;
  border-right: 2.5px solid #9c7b41;
  padding: 0.75rem 0.85rem 0.65rem;
  text-align: center;
  box-shadow:
    inset 0 10px 14px rgba(105, 68, 23, 0.28),
    inset 0 -10px 14px rgba(105, 68, 23, 0.35),
    0 10px 24px rgba(0, 0, 0, 0.6);
  z-index: 5;
}

.scroll-aged-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 50%, transparent 40%, rgba(130, 85, 30, 0.2) 100%);
  pointer-events: none;
}

/* Subtle Horizontal Creases in Ancient Parchment */
.scroll-crease {
  position: absolute;
  left: 6px;
  right: 6px;
  height: 1px;
  background: rgba(140, 95, 40, 0.16);
  box-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);
  pointer-events: none;
}

.scroll-crease.crease-top { top: 30%; }
.scroll-crease.crease-mid { top: 72%; }

/* Deckled / Curled Side Edges */
.scroll-edge-curl {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 8px;
  pointer-events: none;
}

.scroll-edge-curl.left {
  left: 0;
  background: linear-gradient(90deg, rgba(70, 42, 12, 0.3) 0%, transparent 100%);
}

.scroll-edge-curl.right {
  right: 0;
  background: linear-gradient(-90deg, rgba(70, 42, 12, 0.3) 0%, transparent 100%);
}

/* Wax Seal on Top Left of Parchment */
.scroll-wax-seal {
  position: absolute;
  top: -10px;
  left: -10px;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.seal-stamp {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: radial-gradient(circle at 35% 35%, #ef4444 0%, #b91c1c 45%, #7f1d1d 80%, #450a0a 100%);
  border: 1px solid #ff7b72;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.7), inset 0 2px 3px rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  transform: rotate(-10deg);
}

.seal-ribbon {
  width: 9px;
  height: 15px;
  background: linear-gradient(180deg, #b91c1c 0%, #7f1d1d 100%);
  clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 75%, 0 100%);
  margin-top: -3px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
}

/* Inscribed Manuscript Calligraphy */
.scroll-manuscript-text {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.scroll-crest-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  opacity: 0.85;
}

.scroll-flourish {
  font-size: 8px;
  color: #854d0e;
}

.scroll-crest-label {
  font-size: 6px;
  color: #78350f;
  letter-spacing: 0.1em;
}

.scroll-title-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.scroll-star-glyph {
  font-size: 9px;
  color: #b45309;
}

.scroll-title-text {
  font-size: 11px;
  color: #2b1404;
  letter-spacing: 0.09em;
  margin: 0;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.7);
  white-space: nowrap;
}

.scroll-subtitle-text {
  font-family: var(--font-gothic, serif);
  font-size: 0.78rem;
  font-style: italic;
  font-weight: 700;
  color: #523111;
  margin: 0;
  line-height: 1.2;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.45);
  white-space: nowrap;
}

/* Real-Time Passer Ticker Stage (1-by-1 displaying & fading) */
.scroll-passer-stage {
  width: 100%;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0.15rem 0 0.1rem;
  position: relative;
  overflow: hidden;
}

.passer-fading-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
  padding: 0.1rem 0.2rem;
}

.passer-rank-ribbon {
  font-size: 6.5px;
  color: #92400e;
  background: rgba(245, 158, 11, 0.2);
  border: 1px solid rgba(180, 83, 9, 0.35);
  border-radius: 2px;
  padding: 0.1rem 0.45rem;
  letter-spacing: 0.08em;
  margin-bottom: 0.15rem;
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
}

.passer-player-tag {
  font-size: 6px;
  color: #065f46;
  background: rgba(16, 185, 129, 0.14);
  border: 1px solid rgba(5, 150, 105, 0.35);
  border-radius: 2px;
  padding: 0.1rem 0.4rem;
  letter-spacing: 0.08em;
  margin-bottom: 0.15rem;
  display: inline-flex;
  align-items: center;
}

.passer-username-display {
  font-size: 8px;
  color: #92400e;
  letter-spacing: 0.08em;
  margin: 0.05rem 0 0.15rem;
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  font-weight: 700;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.6);
}

.passer-username-display .handle-at {
  color: #d97706;
  font-weight: 800;
}

.passer-name-display {
  font-size: 8.5px;
  color: #2b1404;
  font-weight: 700;
  letter-spacing: 0.05em;
  line-height: 1.25;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.7);
  max-width: 95%;
  word-break: break-word;
}

.passer-cred-badge {
  color: #78350f;
  font-size: 7.5px;
  margin-left: 0.15rem;
  white-space: nowrap;
}

.passer-school-sub {
  font-family: var(--font-gothic, serif);
  font-size: 0.72rem;
  font-style: italic;
  font-weight: 600;
  color: #5c3b1c;
  line-height: 1.15;
  margin-top: 0.12rem;
  max-width: 92%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Smooth 1-by-1 fade transition */
.passer-fade-enter-active,
.passer-fade-leave-active {
  transition: opacity 0.55s cubic-bezier(0.16, 1, 0.3, 1), transform 0.55s cubic-bezier(0.16, 1, 0.3, 1), filter 0.55s ease;
}

.passer-fade-enter-from {
  opacity: 0;
  transform: translateY(6px) scale(0.96);
  filter: blur(1.5px);
}

.passer-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
  filter: blur(1.5px);
}

/* Live Badge on the Scroll */
.scroll-live-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(180, 83, 9, 0.14);
  border: 1px solid rgba(180, 83, 9, 0.4);
  padding: 0.2rem 0.55rem;
  border-radius: 3px;
  margin-top: 0.25rem;
}

.scroll-pulse-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #059669;
  box-shadow: 0 0 6px #10b981;
  animation: radarPulse 1.6s infinite;
}

.scroll-badge-label {
  font-size: 6.5px;
  color: #6d2e0b;
  letter-spacing: 0.06em;
}

/* Interactive Action Hint */
.scroll-action-hint {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 6px;
  color: #92400e;
  letter-spacing: 0.08em;
  margin-top: 0.25rem;
  opacity: 0.9;
  transition: opacity 0.2s;
}

.physical-parchment-scroll-entrance:hover .scroll-action-hint {
  opacity: 1;
  color: #b45309;
}

.hint-pulse {
  font-size: 5px;
  animation: crimsonPulse 1.4s infinite;
}

/* Hanging Silk Ribbons from bottom */
.scroll-ribbon-tails {
  position: absolute;
  bottom: -20px;
  right: 28px;
  display: flex;
  gap: 4px;
  z-index: 3;
}

.ribbon-tail {
  width: 11px;
  height: 24px;
  clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.45);
}

.ribbon-tail.tail-crimson {
  background: linear-gradient(180deg, #dc2626 0%, #991b1b 100%);
}

.ribbon-tail.tail-gold {
  background: linear-gradient(180deg, #f59e0b 0%, #b45309 100%);
  height: 28px;
}

@keyframes crimsonPulse {
  0%, 100% { transform: scale(1); opacity: 0.8; }
  50%       { transform: scale(1.3); opacity: 1; }
}

/* ══════════════════════════════════════════════════════════════
   6. SIDE NAVIGATION TRAVEL CONTROLS
   ══════════════════════════════════════════════════════════════ */
.world-side-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 24;
  cursor: pointer;
  pointer-events: auto;
}

.side-nav-left {
  left: 1.5rem;
}

.side-nav-right {
  right: 1.5rem;
}

.side-nav-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-bone);
  padding: 0.65rem 1rem;
  box-shadow: var(--shadow-gothic);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.side-nav-left:hover .side-nav-card {
  transform: translateX(-4px);
  border-color: var(--aged-gold-bright);
  box-shadow: var(--shadow-gothic), 0 0 16px rgba(217, 119, 6, 0.25);
}

.side-nav-right:hover .side-nav-card {
  transform: translateX(4px);
  border-color: var(--aged-gold-bright);
  box-shadow: var(--shadow-gothic), 0 0 16px rgba(217, 119, 6, 0.25);
}

.nav-arrow-symbol {
  font-size: 1.25rem;
  color: var(--aged-gold-bright);
  transition: transform 0.2s ease;
}

.side-nav-left:hover .nav-arrow-symbol {
  transform: translateX(-3px);
}

.side-nav-right:hover .nav-arrow-symbol {
  transform: translateX(3px);
}

.nav-meta {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.nav-tag {
  font-size: 6px;
  color: var(--text-muted);
  letter-spacing: 0.08em;
}

.nav-dest {
  font-size: 8px;
  color: var(--aged-gold-shine);
  letter-spacing: 0.05em;
}

.nav-hint {
  font-family: var(--font-gothic);
  font-size: 0.75rem;
  color: var(--text-primary);
  opacity: 0.85;
}

/* ══════════════════════════════════════════════════════════════
   7. RPG ZONE BANNER
   ══════════════════════════════════════════════════════════════ */
.rpg-zone-banner {
  position: absolute;
  top: 18%;
  left: 50%;
  transform: translateX(-50%);
  background: var(--gothic-surface);
  border: 1px solid var(--aged-gold-dim);
  border-top: 2px solid var(--aged-gold-bright);
  padding: 0.6rem 1.8rem;
  text-align: center;
  box-shadow: var(--shadow-gothic), 0 0 20px rgba(184, 134, 11, 0.25);
  z-index: 30;
  pointer-events: none;
}

.zone-location-tag {
  font-size: 6px;
  color: var(--aged-gold-bright);
  letter-spacing: 0.15em;
  display: block;
  margin-bottom: 0.2rem;
}

.zone-name {
  font-size: 11px;
  color: var(--text-bright);
  letter-spacing: 0.08em;
  margin: 0;
}

.zone-sub {
  font-family: var(--font-gothic);
  font-size: 0.82rem;
  color: var(--text-muted);
}

.zone-banner-enter-active,
.zone-banner-leave-active {
  transition: all 0.3s ease;
}

.zone-banner-enter-from {
  opacity: 0;
  transform: translate(-50%, -12px);
}

.zone-banner-leave-to {
  opacity: 0;
  transform: translate(-50%, 8px);
}





/* ══════════════════════════════════════════════════════════════
   9. REALM NAVIGATION GUIDE FADING MODAL & PULSE
   ══════════════════════════════════════════════════════════════ */
.world-side-nav.guide-pulse .side-nav-card {
  animation: sideNavCardPulse 1.4s ease-in-out infinite alternate;
  border-color: #ffd700;
}

@keyframes sideNavCardPulse {
  0% {
    box-shadow: 0 0 8px rgba(255, 215, 0, 0.4), inset 0 0 6px rgba(255, 215, 0, 0.2);
    transform: scale(1);
  }
  100% {
    box-shadow: 0 0 24px rgba(255, 215, 0, 0.95), inset 0 0 12px rgba(255, 215, 0, 0.5);
    transform: scale(1.08);
  }
}

.realm-guide-overlay {
  position: fixed;
  inset: 0;
  z-index: 1500;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(5, 5, 12, 0.65);
  backdrop-filter: blur(4px);
  padding: 1.5rem;
}

.realm-guide-card {
  position: relative;
  max-width: 580px;
  width: 100%;
  background: radial-gradient(circle at 50% 0%, rgba(38, 26, 50, 0.98), rgba(13, 10, 20, 0.99));
  border: 2px solid var(--border-gold, #cba558);
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.85), 0 0 28px rgba(203, 165, 88, 0.25);
  border-radius: 8px;
  padding: 1.5rem 1.75rem 1.25rem;
  color: var(--text-bright, #fff);
  text-align: center;
  overflow: hidden;
}

.guide-corner {
  position: absolute;
  width: 10px;
  height: 10px;
  border-color: var(--gold-bright, #ffd700);
  border-style: solid;
  pointer-events: none;
}
.guide-corner.tl { top: 4px; left: 4px; border-width: 2px 0 0 2px; }
.guide-corner.tr { top: 4px; right: 4px; border-width: 2px 2px 0 0; }
.guide-corner.bl { bottom: 4px; left: 4px; border-width: 0 0 2px 2px; }
.guide-corner.br { bottom: 4px; right: 4px; border-width: 0 2px 2px 0; }

.guide-header-strip {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.8rem;
  border-bottom: 1px solid rgba(203, 165, 88, 0.25);
  padding-bottom: 0.5rem;
}

.guide-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 10px;
  color: var(--gold-bright, #ffd700);
  letter-spacing: 0.1em;
}

.guide-close-btn {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 4px;
  color: var(--text-muted, #999);
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
  padding: 3px 8px;
  transition: all 0.2s;
}

.guide-close-btn:hover {
  color: #fff;
  border-color: var(--crimson-accent, #ff4d6d);
  background: rgba(255, 77, 109, 0.15);
}

.guide-headline {
  font-size: 13px;
  color: var(--gold-bright, #ffe082);
  letter-spacing: 0.08em;
  margin: 0 0 0.5rem;
  text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
}

.guide-subtext {
  font-size: 0.95rem;
  color: #d0c8d8;
  line-height: 1.45;
  margin: 0 0 1.2rem;
  font-family: var(--font-gothic, serif);
}

.guide-subtext strong {
  color: #fff;
  font-weight: 700;
}

.guide-sectors-display {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.guide-sector-box {
  padding: 0.75rem 0.5rem;
  border-radius: 6px;
  background: rgba(20, 16, 28, 0.85);
  border: 1px solid rgba(203, 165, 88, 0.3);
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.guide-sector-box:hover {
  transform: translateY(-3px);
  border-color: var(--gold-bright, #ffd700);
  background: rgba(45, 32, 62, 0.95);
  box-shadow: 0 4px 16px rgba(255, 215, 0, 0.25);
}

.guide-sector-box.is-active {
  border-color: #ffd700;
  background: linear-gradient(180deg, rgba(72, 48, 92, 0.85), rgba(30, 20, 45, 0.95));
  box-shadow: 0 0 14px rgba(255, 215, 0, 0.35);
}

.sector-head {
  font-size: 9px;
  color: #a095ad;
  letter-spacing: 0.06em;
}

.guide-sector-box.is-active .sector-head {
  color: #ffd700;
  font-weight: bold;
}

.sector-title {
  font-size: 11px;
  color: #fff;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.sector-tags {
  font-size: 0.72rem;
  color: #b8b0c5;
  margin-top: 0.2rem;
  line-height: 1.2;
  font-family: var(--font-gothic, serif);
}

.guide-footer-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.8rem;
}

.guide-tip-note {
  font-size: 9px;
  color: #a99ec0;
  letter-spacing: 0.05em;
  text-align: left;
}

.guide-confirm-btn {
  padding: 0.4rem 1rem;
  font-size: 10px;
  cursor: pointer;
  border-radius: 4px;
}

/* Auto-fade timer progress indicator */
.guide-timer-track {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(255, 255, 255, 0.1);
}

.guide-timer-bar {
  height: 100%;
  width: 100%;
  background: linear-gradient(90deg, #ff4d6d, #ffd700);
  transform-origin: left;
  animation: guideTimerDrain 3s linear forwards;
}

@keyframes guideTimerDrain {
  0% { width: 100%; }
  100% { width: 0%; }
}

/* Smooth modal fade transitions */
.guide-fade-enter-active,
.guide-fade-leave-active {
  transition: opacity 0.45s ease;
}

.guide-fade-enter-active .realm-guide-card,
.guide-fade-leave-active .realm-guide-card {
  transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}

.guide-fade-enter-from,
.guide-fade-leave-to {
  opacity: 0;
}

.guide-fade-enter-from .realm-guide-card {
  transform: scale(0.92) translateY(16px);
}

.guide-fade-leave-to .realm-guide-card {
  transform: scale(0.95) translateY(12px);
}



/* ══════════════════════════════════════════════════════════════
   10. RESPONSIVE DESIGN
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 820px) {
  .world-side-nav {
    top: auto;
    bottom: 4.8rem;
    transform: none;
  }
  .side-nav-left { left: 0.75rem; }
  .side-nav-right { right: 0.75rem; }
  .side-nav-card {
    padding: 0.4rem 0.6rem;
    gap: 0.4rem;
  }
  .nav-hint { display: none; }
  .world-welcome-bar { display: none; }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — WORLD HUB (MATCHES REFERENCE)
   ══════════════════════════════════════════════════════════════ */
.theme-morning .welcome-pill,
[data-theme="morning"] .welcome-pill {
  background: #F5E8CC !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 4px 12px rgba(143, 100, 45, 0.12) !important;
}

.theme-morning .welcome-greet,
[data-theme="morning"] .welcome-greet {
  color: #77634F !important;
}

.theme-morning .welcome-name,
[data-theme="morning"] .welcome-name {
  color: #3D2B1F !important;
}

.theme-morning .welcome-streak,
[data-theme="morning"] .welcome-streak {
  color: #991b1b !important;
}

.theme-morning .building-badge-pill,
[data-theme="morning"] .building-badge-pill {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  box-shadow: 0 6px 18px rgba(143, 100, 45, 0.16) !important;
}

.theme-morning .building-name,
[data-theme="morning"] .building-name {
  color: #3D2B1F !important;
}

.theme-morning .building-sub,
[data-theme="morning"] .building-sub {
  color: #77634F !important;
}

.theme-morning .side-nav-card,
[data-theme="morning"] .side-nav-card {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  box-shadow: 0 6px 18px rgba(143, 100, 45, 0.16) !important;
}

.theme-morning .nav-dest,
[data-theme="morning"] .nav-dest {
  color: #3D2B1F !important;
}

.theme-morning .nav-sub,
[data-theme="morning"] .nav-sub {
  color: #77634F !important;
}

.theme-morning .nav-arrow-symbol,
[data-theme="morning"] .nav-arrow-symbol {
  color: #8F642D !important;
}

.theme-morning .realm-guide-card,
[data-theme="morning"] .realm-guide-card {
  background: #FFF4DF !important;
  border: 2px solid #B88A42 !important;
  box-shadow: 0 16px 40px rgba(143, 100, 45, 0.25) !important;
  color: #3D2B1F !important;
}

.theme-morning .guide-headline,
[data-theme="morning"] .guide-headline {
  color: #3D2B1F !important;
  text-shadow: none !important;
}

.theme-morning .guide-subtext,
[data-theme="morning"] .guide-subtext {
  color: #77634F !important;
}

.theme-morning .guide-subtext strong,
[data-theme="morning"] .guide-subtext strong {
  color: #3D2B1F !important;
}

.theme-morning .guide-sector-box,
[data-theme="morning"] .guide-sector-box {
  background: #F5E8CC !important;
  border: 1px solid #B88A42 !important;
}

.theme-morning .sector-title,
[data-theme="morning"] .sector-title {
  color: #3D2B1F !important;
}

.theme-morning .sector-head,
[data-theme="morning"] .sector-head {
  color: #77634F !important;
}

.theme-morning .guide-confirm-btn,
[data-theme="morning"] .guide-confirm-btn {
  background: #B88A42 !important;
  color: #FFFDF7 !important;
  border: 1px solid #8F642D !important;
}

.theme-morning .hall-of-fame-pill,
[data-theme="morning"] .hall-of-fame-pill {
  background: #FFF4DF !important;
  border: 1.5px solid #d97706 !important;
  box-shadow: 0 6px 18px rgba(217, 119, 6, 0.25) !important;
}

@media (max-width: 900px) {
  .spot-hall-of-fame {
    top: 24% !important;
    left: 84% !important;
  }
}

@media (max-width: 650px) {
  .spot-hall-of-fame {
    top: 22% !important;
    left: 80% !important;
    transform: translate(-50%, -50%) scale(0.92);
  }
}
</style>