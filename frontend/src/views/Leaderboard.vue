<template>
  <div class="hall-of-records-page">
    <!-- Dedicated Grand Hall Background Environment -->
    <div class="hall-bg-environment" aria-hidden="true">
      <img
        src="/assets/game/hall_of_records_bg.jpg"
        alt=""
        class="hall-bg-image"
        @error="onBgError"
      />
      <!-- Atmospheric Gradient & Vignette Overlays (keeps artwork visible while ensuring readability) -->
      <div class="hall-bg-overlay-radial"></div>
      <div class="hall-bg-overlay-linear"></div>
      <div class="hall-bg-vignette"></div>

      <!-- Subtle Ambient Magical Embers / Particles -->
      <div class="ambient-ember ember-1"></div>
      <div class="ambient-ember ember-2"></div>
      <div class="ambient-ember ember-3"></div>
      <div class="ambient-ember ember-4"></div>
    </div>

    <!-- Main Content Container (Proper Max-Width & Vertical Rhythm) -->
    <main class="hall-content-container" ref="hallContainerRef">
      <!-- 1. Grand Monumental Hall Header -->
      <HallHeader />

      <!-- 2. Gamified Ranking Mode Selector + Search -->
      <RankingModeSelector
        v-model="currentType"
        v-model:searchQuery="searchQuery"
        @update:modelValue="onTypeChange"
      />

      <!-- Loading State -->
      <div v-if="loading" class="hall-loading-state glass-card animate-scale-up">
        <div class="loading-rune-spinner">❖</div>
        <h3 class="loading-title">CONSULTING THE ANCIENT ARCHIVE…</h3>
        <p class="loading-sub">Retrieving the inscribed deeds of Mindrealm contenders</p>
      </div>

      <!-- Main Ranking Content -->
      <div v-else class="hall-main-content">
        <!-- 3. TOP 3 CHAMPIONS PODIUM (Always visible unless filtered out) -->
        <ChampionPodium
          :champions="top3Champions"
          :ranking-type="currentType"
          :current-user-id="auth.user?.id"
          @inspect="openDossier"
        />

        <!-- 4. CHAMPION ROSTER (RANKS 4 ONWARD) -->
        <RankingRoster
          :players="rosterPlayers"
          :start-index="3"
          :ranking-type="currentType"
          :current-user-id="auth.user?.id"
          :has-search-query="Boolean(searchQuery.trim())"
          @inspect="openDossier"
        />
      </div>

      <!-- 5. Current Player Sticky Standing Banner -->
      <CurrentPlayerBanner
        v-if="userRankData"
        :user-rank-data="userRankData"
        :user-rank-number="currentUserRankNumber"
        :ranking-type="currentType"
        :is-in-visible-list="isCurrentUserInList"
        @jump-to-user="jumpToUserPosition"
        @inspect="openDossier"
      />

      <!-- 6. Mindkeeper Record Profile Inspection Modal -->
      <MindkeeperProfileModal
        v-if="selectedPlayer"
        :user-id="selectedPlayer.id"
        :rank="selectedRank"
        @close="closeDossier"
      />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useAuthStore } from '../stores/auth'
import multiplayerService from '../services/multiplayerService'
import HallHeader from '../components/leaderboard/HallHeader.vue'
import RankingModeSelector from '../components/leaderboard/RankingModeSelector.vue'
import ChampionPodium from '../components/leaderboard/ChampionPodium.vue'
import RankingRoster from '../components/leaderboard/RankingRoster.vue'
import CurrentPlayerBanner from '../components/leaderboard/CurrentPlayerBanner.vue'
import MindkeeperProfileModal from '../components/leaderboard/MindkeeperProfileModal.vue'

const auth = useAuthStore()

// State
const currentType = ref('rating')
const searchQuery = ref('')
const leaderboard = ref([])
const loading = ref(true)
const myRankNumber = ref(null)

// Inspection modal state
const selectedPlayer = ref(null)
const selectedRank = ref(1)

const hallContainerRef = ref(null)

// Fetch server leaderboard data (Preserves exact backend formulas & order)
async function fetchLeaderboard(type = 'rating') {
  loading.value = true
  try {
    const res = await multiplayerService.getLeaderboard(type)
    if (res.success) {
      leaderboard.value = res.leaderboard || []
    }
  } catch (e) {
    console.error('Failed to load Hall of Records leaderboard:', e)
  } finally {
    loading.value = false
  }
}

// Fetch current user's individual rank if available
async function fetchMyRank() {
  try {
    const res = await multiplayerService.getMyRank()
    if (res.success && res.rank) {
      myRankNumber.value = res.rank
    }
  } catch {
    // Non-blocking
  }
}

onMounted(async () => {
  await Promise.all([
    fetchLeaderboard(currentType.value),
    fetchMyRank()
  ])
})

function onTypeChange(type) {
  currentType.value = type
  fetchLeaderboard(type)
}

// Filtered list based on search query without altering order
const filteredLeaderboard = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return leaderboard.value

  return leaderboard.value.filter(p => {
    const name = (p.nickname || p.username || '').toLowerCase()
    return name.includes(query)
  })
})

// Top 3 Podium champions
const top3Champions = computed(() => {
  return filteredLeaderboard.value.slice(0, 3)
})

// Roster players (Rank 4+)
const rosterPlayers = computed(() => {
  return filteredLeaderboard.value.slice(3)
})

// Current user standing detection
const userRankData = computed(() => {
  if (!auth.user) return null
  const currentInList = leaderboard.value.find(p => Number(p.id) === Number(auth.user.id))
  return currentInList || auth.user
})

const currentUserRankNumber = computed(() => {
  if (!auth.user) return '-'
  const idx = leaderboard.value.findIndex(p => Number(p.id) === Number(auth.user.id))
  if (idx >= 0) return idx + 1
  return myRankNumber.value || '-'
})

const isCurrentUserInList = computed(() => {
  if (!auth.user) return false
  return leaderboard.value.some(p => Number(p.id) === Number(auth.user.id))
})

// Modal controls
function openDossier(player, rank) {
  selectedPlayer.value = player
  selectedRank.value = rank
}

function closeDossier() {
  selectedPlayer.value = null
}

// Jump to user position in page
function jumpToUserPosition() {
  nextTick(() => {
    const el = document.querySelector('.highlight-user, .is-current-user')
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  })
}

// Fallback if background image fails
function onBgError(e) {
  // Graceful fallback to forbidden archive if needed
  if (!e.target.src.includes('chapter-08')) {
    e.target.src = '/assets/adventure/backgrounds/chapter-08-forbidden-archive.jpg'
  }
}
</script>

<style scoped>
.hall-of-records-page {
  position: relative;
  min-height: 100vh;
  width: 100%;
  color: #e2d9cc;
  padding-bottom: 5rem;
  overflow-x: hidden;
}

/* ── Full-Bleed Atmospheric Background ── */
.hall-bg-environment {
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.hall-bg-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  filter: brightness(0.65) contrast(1.1) saturate(1.1);
  transform: scale(1.02);
  transition: transform 10s ease-out;
}

/* Multi-layered overlays to guarantee text readability while preserving artwork */
.hall-bg-overlay-radial {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    ellipse at 50% 25%,
    rgba(20, 14, 28, 0.45) 0%,
    rgba(8, 9, 14, 0.8) 60%,
    rgba(4, 5, 8, 0.95) 100%
  );
}

.hall-bg-overlay-linear {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(5, 6, 10, 0.7) 0%,
    rgba(10, 11, 16, 0.5) 30%,
    rgba(8, 9, 13, 0.85) 75%,
    rgba(4, 4, 7, 0.98) 100%
  );
}

.hall-bg-vignette {
  position: absolute;
  inset: 0;
  box-shadow: inset 0 0 120px rgba(0, 0, 0, 0.9);
}

/* Floating ambient embers */
.ambient-ember {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #e6c845;
  box-shadow: 0 0 10px #e6c845, 0 0 20px #dc2626;
  opacity: 0.6;
  animation: floatEmber 6s infinite ease-in-out;
}
.ember-1 { top: 20%; left: 15%; animation-duration: 7s; }
.ember-2 { top: 35%; right: 18%; animation-duration: 9s; animation-delay: 1.5s; }
.ember-3 { top: 60%; left: 22%; animation-duration: 8s; animation-delay: 3s; }
.ember-4 { top: 75%; right: 25%; animation-duration: 11s; animation-delay: 2s; }

@keyframes floatEmber {
  0%, 100% { transform: translateY(0) scale(1); opacity: 0.3; }
  50% { transform: translateY(-30px) scale(1.4); opacity: 0.8; }
}

/* ── Content Container (Proper Max-Width & Layering) ── */
.hall-content-container {
  position: relative;
  z-index: 1;
  max-width: 1240px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem;
}

/* Loading Box */
.hall-loading-state {
  max-width: 600px;
  margin: 4rem auto;
  padding: 3rem 2rem;
  text-align: center;
  background: rgba(14, 15, 23, 0.88);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.9);
}

.loading-rune-spinner {
  font-size: 2.2rem;
  color: #e6c845;
  display: inline-block;
  margin-bottom: 1.25rem;
  animation: runeSpin 3s infinite linear;
}

.loading-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  color: #f7f3eb;
  letter-spacing: 0.08em;
  margin: 0 0 0.5rem;
}

.loading-sub {
  font-size: 1rem;
  color: #8b8070;
  margin: 0;
  font-family: var(--font-gothic, serif);
}

@keyframes runeSpin {
  0% { transform: rotate(0deg) scale(1); }
  50% { transform: rotate(180deg) scale(1.15); }
  100% { transform: rotate(360deg) scale(1); }
}

@media (max-width: 768px) {
  .hall-content-container {
    padding: 1rem 0.75rem;
  }
}
</style>
