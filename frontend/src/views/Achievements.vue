<template>
  <div class="achievements-full-page game-module-env">
    <!-- Main Scrollable Chamber -->
    <div class="achievements-chamber">
      <!-- ── HERO / OVERVIEW HEADER ───────────────────────────────── -->
      <header class="ach-hero-header">
        <div class="hero-top-row">
          <div class="hero-title-group">
            <span class="hall-crest-tag">
              <PixelEmoji emoji="crown" size="xs" /> HALL OF ACHIEVEMENTS • MINDREALM CHRONICLES
            </span>
            <h1 class="hall-main-title">Grand Pantheon of Trials</h1>
            <p class="hall-subtitle">
              Thirty legendary codex cards immortalizing your heroic journey across the 12 Adventure Gate Chapters, the 12 Study Hall Disciplines, Character Progression, and Multiplayer Arenas.
            </p>
          </div>

          <!-- Quick Return & Claim All Actions -->
          <div class="hero-action-group">
            <button
              type="button"
              class="btn-claim-all"
              :class="{ 'has-claimable': summary.unclaimed_count > 0 }"
              :disabled="summary.unclaimed_count === 0 || isClaimingAll"
              @click="handleClaimAll"
              :title="summary.unclaimed_count > 0 ? `Claim all ${summary.unclaimed_count} claimable achievements at once` : 'No claimable achievements right now'"
            >
              <PixelEmoji emoji="sparkles" size="xs" />
              <span v-if="isClaimingAll">Claiming All...</span>
              <span v-else-if="summary.unclaimed_count > 0">Claim All ({{ summary.unclaimed_count }})</span>
              <span v-else>Claim All</span>
            </button>

            <router-link to="/world" class="btn-return-world">
              ← World Hub
            </router-link>
          </div>
        </div>

        <!-- ── STATS & PROGRESS OVERVIEW BAR ──────────────────────── -->
        <div class="overview-stats-grid">
          <!-- Total Cards -->
          <div class="stat-card">
            <div class="stat-icon-wrap gold">
              <PixelEmoji emoji="shield" size="md" />
            </div>
            <div class="stat-info">
              <span class="stat-num">{{ summary.total_cards }}</span>
              <span class="stat-label">Total Cards</span>
            </div>
            <span class="stat-sub-badge">{{ summary.total_cards }} Cards Total</span>
          </div>

          <!-- Total Milestones -->
          <div class="stat-card">
            <div class="stat-icon-wrap purple">
              <PixelEmoji emoji="star" size="md" />
            </div>
            <div class="stat-info">
              <span class="stat-num">{{ summary.total_milestones }}</span>
              <span class="stat-label">Claimable Milestones</span>
            </div>
            <span class="stat-sub-badge">{{ summary.total_milestones }} Milestones Total</span>
          </div>

          <!-- Claim Available -->
          <div class="stat-card" :class="{ 'stat-highlight': summary.unclaimed_count > 0 }">
            <div class="stat-icon-wrap yellow">
              <PixelEmoji emoji="sparkles" size="md" />
            </div>
            <div class="stat-info">
              <span class="stat-num text-gold">{{ summary.unclaimed_count }}</span>
              <span class="stat-label">Claim Available</span>
            </div>
            <button
              v-if="summary.unclaimed_count > 0"
              type="button"
              class="stat-claim-all-btn pulse"
              :disabled="isClaimingAll"
              @click="handleClaimAll"
            >
              ⚡ Claim All ({{ summary.unclaimed_count }})
            </button>
            <span v-else class="stat-sub-badge">Up to date</span>
          </div>

          <!-- Claimed -->
          <div class="stat-card">
            <div class="stat-icon-wrap green">
              <PixelEmoji emoji="trophy" size="md" />
            </div>
            <div class="stat-info">
              <span class="stat-num text-green">{{ summary.claimed_count }}</span>
              <span class="stat-label">Claimed</span>
            </div>
            <span class="stat-sub-badge">Honors Bestowed</span>
          </div>

          <!-- Locked -->
          <div class="stat-card">
            <div class="stat-icon-wrap gray">
              <PixelEmoji emoji="book" size="md" />
            </div>
            <div class="stat-info">
              <span class="stat-num text-gray">{{ summary.locked_count }}</span>
              <span class="stat-label">Locked</span>
            </div>
            <span class="stat-sub-badge">Awaiting Mastery</span>
          </div>
        </div>

        <!-- Global Completion Progress Bar -->
        <div class="global-progress-block">
          <div class="global-progress-labels">
            <span class="global-title">Pantheon Completion Progress</span>
            <span class="global-stat">
              {{ summary.claimed_count }} of {{ summary.total_milestones }} Milestones Claimed
              <strong class="global-pct">({{ summary.completion_percentage }}%)</strong>
            </span>
          </div>
          <div class="global-track">
            <div
              class="global-fill"
              :style="{ width: `${summary.completion_percentage}%` }"
            ></div>
          </div>
        </div>
      </header>

      <!-- ── FILTERS, SEARCH & TABS ──────────────────────────────── -->
      <section class="controls-toolbar">
        <!-- Category Filter Tabs -->
        <div class="category-tabs-scroll">
          <button
            v-for="cat in categoryList"
            :key="cat.id"
            type="button"
            class="cat-tab"
            :class="{ active: selectedCategory === cat.id }"
            @click="selectedCategory = cat.id"
          >
            <PixelEmoji :emoji="cat.icon" size="xs" />
            <span class="cat-tab-name">{{ cat.name }}</span>
            <span class="cat-tab-count">({{ getCategoryCount(cat.id) }})</span>
          </button>
        </div>

        <!-- Filter Controls Row (Status, Search, Sorting) -->
        <div class="filter-controls-row">
          <!-- Status Filter -->
          <div class="filter-group">
            <label class="filter-label">Status:</label>
            <select v-model="selectedStatus" class="select-filter">
              <option value="all">All Statuses ({{ cards.length }})</option>
              <option value="available">Claim Available ★</option>
              <option value="claimed">Claimed ✓</option>
              <option value="locked">Locked 🔒</option>
            </select>
          </div>

          <!-- Sort Filter -->
          <div class="filter-group">
            <label class="filter-label">Sort:</label>
            <select v-model="selectedSort" class="select-filter">
              <option value="default">Default Order</option>
              <option value="available_first">Available First</option>
              <option value="name">Alphabetical</option>
            </select>
          </div>

          <!-- Search Input -->
          <div class="search-input-wrap">
            <span class="search-icon">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search chapters, domains, milestones..."
              class="search-input"
            />
            <button
              v-if="searchQuery"
              type="button"
              class="clear-search-btn"
              @click="searchQuery = ''"
            >
              ✕
            </button>
          </div>
        </div>
      </section>

      <!-- ── LOADING & ERROR STATES ─────────────────────────────── -->
      <div v-if="loading" class="hall-loading-state">
        <div class="gothic-spinner"></div>
        <p class="loading-msg">Consulting the Grand Codex of Achievements...</p>
      </div>

      <div v-else-if="error" class="hall-error-state">
        <p class="error-msg">{{ error }}</p>
        <button type="button" class="btn-retry" @click="loadAchievements">
          Retry Synchronization
        </button>
      </div>

      <!-- ── ACHIEVEMENT CARDS GRID (EXACTLY 30 CARDS) ──────────── -->
      <section v-else class="cards-grid-section">
        <div v-if="filteredCards.length === 0" class="no-results-state">
          <p class="no-results-title">No Achievements Found</p>
          <p class="no-results-sub">
            Try adjusting your search query or switching category filters.
          </p>
          <button type="button" class="btn-reset-filters" @click="resetFilters">
            Reset Filters
          </button>
        </div>

        <div v-else class="cards-grid">
          <AchievementCard
            v-for="card in filteredCards"
            :key="card.id"
            :card="card"
            :claiming-id="claimingMilestoneId"
            @claim="handleClaimMilestone"
            @share="handleOpenShare"
          />
        </div>
      </section>
    </div>

    <!-- ── TIKTOK & SOCIAL SHARING MODAL ───────────────────────── -->
    <AchievementShareModal
      :is-open="isShareModalOpen"
      :card="activeShareCard"
      :milestone="activeShareMilestone"
      @close="isShareModalOpen = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { achievementService } from '../services/achievementService'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import { useToastStore } from '../stores/toast'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import AchievementCard from '../components/achievements/AchievementCard.vue'
import AchievementShareModal from '../components/achievements/AchievementShareModal.vue'
import Swal from 'sweetalert2'

const auth = useAuthStore()
const gameStore = useGameStore()
const toast = useToastStore()

const loading = ref(true)
const error = ref('')
const claimingMilestoneId = ref(null)
const isClaimingAll = ref(false)

// Raw server data
const cards = ref([])
const summary = ref({
  total_cards: 32,
  total_milestones: 32,
  claimed_count: 0,
  unclaimed_count: 0,
  locked_count: 0,
  completion_percentage: 0,
  total_xp_claimed: 0,
  total_coins_claimed: 0
})

// Filter states
const selectedCategory = ref('all')
const selectedStatus = ref('all')
const selectedSort = ref('default')
const searchQuery = ref('')

// Share Modal State
const isShareModalOpen = ref(false)
const activeShareCard = ref(null)
const activeShareMilestone = ref(null)

// Categories definition
const categoryList = [
  { id: 'all', name: 'All Cards', icon: 'shield' },
  { id: 'adventure', name: 'Adventure Gate', icon: 'sword' },
  { id: 'studyhall', name: 'Study Hall', icon: 'book' },
  { id: 'progression', name: 'Progression', icon: 'crown' },
  { id: 'multiplayer', name: 'Multiplayer Arena', icon: 'trophy' }
]

function getCategoryCount(catId) {
  if (catId === 'all') return cards.value.length
  return cards.value.filter(c => c.category === catId).length
}

function resetFilters() {
  selectedCategory.value = 'all'
  selectedStatus.value = 'all'
  selectedSort.value = 'default'
  searchQuery.value = ''
}

// Filter and Sort Cards
const filteredCards = computed(() => {
  let list = [...cards.value]

  // Category Filter
  if (selectedCategory.value !== 'all') {
    list = list.filter(c => c.category === selectedCategory.value)
  }

  // Status Filter
  if (selectedStatus.value === 'available') {
    list = list.filter(c => c.milestones.some(m => m.status === 'unlocked'))
  } else if (selectedStatus.value === 'claimed') {
    list = list.filter(c => c.milestones.every(m => m.status === 'claimed'))
  } else if (selectedStatus.value === 'locked') {
    list = list.filter(c => c.milestones.every(m => m.status === 'locked'))
  }

  // Search Filter
  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    list = list.filter(c => {
      const matchCard = c.title.toLowerCase().includes(q) ||
        (c.subtitle && c.subtitle.toLowerCase().includes(q))
      const matchMilestone = c.milestones.some(m =>
        m.title.toLowerCase().includes(q) ||
        m.description.toLowerCase().includes(q)
      )
      return matchCard || matchMilestone
    })
  }

  // Sorting
  if (selectedSort.value === 'available_first') {
    list.sort((a, b) => {
      const aAvail = a.milestones.some(m => m.status === 'unlocked') ? 1 : 0
      const bAvail = b.milestones.some(m => m.status === 'unlocked') ? 1 : 0
      return bAvail - aAvail
    })
  } else if (selectedSort.value === 'name') {
    list.sort((a, b) => a.title.localeCompare(b.title))
  } else {
    // Default order
    list.sort((a, b) => a.display_order - b.display_order)
  }

  return list
})

/**
 * Load authoritative achievement data from server.
 */
async function loadAchievements() {
  loading.value = true
  error.value = ''

  try {
    const res = await achievementService.getAll()
    if (res.data?.success) {
      cards.value = res.data.cards || []
      summary.value = res.data.summary || summary.value
    } else {
      error.value = res.data?.message || 'Failed to retrieve achievements.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Error connecting to achievement service.'
  } finally {
    loading.value = false
  }
}

/**
 * Atomically claim reward for a specific milestone.
 */
async function handleClaimMilestone(milestone) {
  if (claimingMilestoneId.value) return
  claimingMilestoneId.value = milestone.id

  try {
    const res = await achievementService.claim(milestone.id)

    if (res.data?.success) {
      // 1. Update local milestone object
      milestone.status = 'claimed'
      milestone.is_claimed = true

      // 2. Refresh summary stats and re-fetch to ensure complete synchronization
      await loadAchievements()

      // 3. Update auth user balance
      if (auth.fetchUser) {
        await auth.fetchUser()
      }

      // 4. SweetAlert2 Reward Celebration Modal with instant Share option
      Swal.fire({
        icon: 'success',
        title: 'Trial Conquered!',
        html: `
          <div style="font-family:'Cinzel',serif;font-size:1.15rem;color:#f3ede2;margin-bottom:12px;">
            <strong>${milestone.title}</strong>
          </div>
          <div style="display:flex;justify-content:center;gap:16px;margin:16px 0;">
            <div style="background:rgba(147,51,234,0.2);border:1px solid #c084fc;padding:8px 16px;border-radius:4px;color:#d8b4fe;font-family:'Cinzel',serif;font-weight:bold;">
              ⭐ +${res.data.xp_reward} XP
            </div>
            <div style="background:rgba(234,179,8,0.2);border:1px solid #facc15;padding:8px 16px;border-radius:4px;color:#fde047;font-family:'Cinzel',serif;font-weight:bold;">
              🪙 +${res.data.coins_reward} Coins
            </div>
          </div>
          <p style="font-family:'VT323',monospace;font-size:1.1rem;color:#a09789;margin-top:10px;">
            Your heroic deeds have been etched into the archives of Mindrealm!
          </p>
        `,
        showCancelButton: true,
        confirmButtonText: 'CONFIRM',
        confirmButtonColor: '#b59410',
        cancelButtonText: 'SHARE',
        cancelButtonColor: '#785a15',
        reverseButtons: true,
        background: '#0d0e14',
        color: '#f5efe6'
      }).then((result) => {
        if (result.dismiss === Swal.DismissReason.cancel) {
          const targetCard = cards.value.find(c => c.milestones?.some(m => m.id === milestone.id))
          if (targetCard) {
            handleOpenShare({ card: targetCard, milestone })
          }
        }
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Claim Denied',
        text: res.data?.message || 'Could not verify milestone eligibility.',
        confirmButtonColor: '#b91c1c',
        background: '#0d0e14',
        color: '#f5efe6'
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Claim Failed',
      text: err.response?.data?.message || 'Server error processing claim transaction.',
      confirmButtonColor: '#b91c1c',
      background: '#0d0e14',
      color: '#f5efe6'
    })
  } finally {
    claimingMilestoneId.value = null
  }
}

/**
 * Atomically claim all unlocked and unclaimed milestone rewards at once.
 */
async function handleClaimAll() {
  if (isClaimingAll.value) return
  if (!summary.value || summary.value.unclaimed_count <= 0) {
    toast?.warning?.('No claimable achievements available at this time.')
    return
  }

  isClaimingAll.value = true

  try {
    const res = await achievementService.claimAll()

    if (res.data?.success) {
      // 1. Refresh achievements and summary
      await loadAchievements()

      // 2. Refresh auth store so header XP/coins reflect immediately
      if (auth.fetchUser) {
        await auth.fetchUser()
      }

      // 3. Build celebratory details HTML
      const claimedItems = res.data.claimed_milestones || []
      const milestonesListHtml = claimedItems.length > 0
        ? `<div style="max-height:160px;overflow-y:auto;background:rgba(0,0,0,0.45);border:1px solid rgba(212,175,55,0.3);border-radius:6px;padding:8px 12px;margin:12px 0;text-align:left;font-family:'VT323',monospace;font-size:1.05rem;">
            ${claimedItems.map(m => `<div style="padding:4px 0;color:#e8dcc4;border-bottom:1px dashed rgba(255,255,255,0.1);display:flex;justify-content:space-between;align-items:center;">
              <span><span style="color:#fde047;margin-right:6px;">✓</span>${m.title}</span>
              <span style="color:#ffd700;font-weight:bold;margin-left:12px;white-space:nowrap;">+${m.xp_reward} XP &bull; +${m.coins_reward} C</span>
            </div>`).join('')}
          </div>`
        : ''

      const levelUpHtml = res.data.user?.leveled_up
        ? `<div style="background:rgba(212,175,55,0.2);border:1px solid #ffd700;color:#ffd700;padding:8px 12px;border-radius:4px;font-family:'Cinzel',serif;font-weight:bold;margin-top:12px;">
            🎉 HEROIC ASCENSION! You reached Level ${res.data.user.level}!
          </div>`
        : ''

      Swal.fire({
        icon: 'success',
        title: 'Grand Harvest Conquered!',
        html: `
          <div style="font-family:'Cinzel',serif;font-size:1.15rem;color:#f3ede2;margin-bottom:8px;">
            <strong>${res.data.claimed_count} ${res.data.claimed_count === 1 ? 'Milestone' : 'Milestones'} Claimed!</strong>
          </div>
          <div style="display:flex;justify-content:center;gap:16px;margin:14px 0;">
            <div style="background:rgba(147,51,234,0.2);border:1px solid #c084fc;padding:8px 16px;border-radius:4px;color:#d8b4fe;font-family:'Cinzel',serif;font-weight:bold;">
              ⭐ +${res.data.total_xp} Total XP
            </div>
            <div style="background:rgba(234,179,8,0.2);border:1px solid #facc15;padding:8px 16px;border-radius:4px;color:#fde047;font-family:'Cinzel',serif;font-weight:bold;">
              🪙 +${res.data.total_coins} Total Coins
            </div>
          </div>
          ${milestonesListHtml}
          ${levelUpHtml}
          <p style="font-family:'VT323',monospace;font-size:1.1rem;color:#a09789;margin-top:10px;">
            Your monumental feats have been woven into the everlasting annals of Mindrealm!
          </p>
        `,
        confirmButtonText: 'CONFIRM',
        confirmButtonColor: '#b59410',
        background: '#0d0e14',
        color: '#f5efe6'
      })
    } else {
      Swal.fire({
        icon: 'info',
        title: 'No Rewards Available',
        text: res.data?.message || 'No claimable achievements were found.',
        confirmButtonText: 'CONFIRM',
        confirmButtonColor: '#b59410',
        background: '#0d0e14',
        color: '#f5efe6'
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Claim All Failed',
      text: err.response?.data?.message || 'Server error claiming all achievements.',
      confirmButtonText: 'CONFIRM',
      confirmButtonColor: '#b91c1c',
      background: '#0d0e14',
      color: '#f5efe6'
    })
  } finally {
    isClaimingAll.value = false
  }
}

/**
 * Open Share Modal for card or specific milestone.
 * Strictly requires the achievement to be claimed first!
 */
function handleOpenShare({ card, milestone }) {
  const target = milestone || card?.milestones?.[0]
  if (target?.status !== 'claimed' && card?.status !== 'claimed') {
    Swal.fire({
      icon: 'info',
      title: 'Claim Reward First',
      text: 'You must claim your achievement reward before you can share your achievement.',
      confirmButtonColor: '#b59410',
      background: '#0d0e14',
      color: '#f5efe6'
    })
    return
  }
  activeShareCard.value = card
  activeShareMilestone.value = target
  isShareModalOpen.value = true
}

onMounted(() => {
  loadAchievements()
})
</script>

<style scoped>
/* Full bleed, zero margin container with HUD top clearance */
.achievements-full-page {
  width: 100%;
  min-height: 100vh;
  margin: 0;
  padding-top: calc(var(--hud-height, 65px) + 0.75rem);
  padding-bottom: 2rem;
  box-sizing: border-box;
  background: radial-gradient(circle at 50% 0%, #15131b 0%, #0a0b0e 65%, #040507 100%);
  color: #f3ede2;
  position: relative;
  overflow-x: hidden;
}

.achievements-chamber {
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  padding: 1rem 1.5rem 3rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

@media (max-width: 768px) {
  .achievements-full-page {
    padding-top: calc(var(--hud-height, 65px) + 0.5rem);
  }
  .achievements-chamber {
    padding: 0.75rem 1rem 2.5rem;
  }
}

/* ── HERO HEADER ─────────────────────────────────────────────────── */
.ach-hero-header {
  background: linear-gradient(180deg, rgba(20, 18, 25, 0.95) 0%, rgba(10, 11, 15, 0.98) 100%);
  border: 1px solid rgba(212, 175, 55, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 12px 32px rgba(0, 0, 0, 0.85);
  border-radius: 8px;
  padding: 1.5rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.hero-top-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.hero-title-group {
  flex: 1;
  min-width: 280px;
}

.hall-crest-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 800;
  color: #b59410;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 0.4rem;
}

.hall-main-title {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 1.2;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8), 0 0 20px rgba(212, 175, 55, 0.2);
}

.hall-subtitle {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 0.95rem;
  font-weight: 500;
  color: #c8bcab;
  margin: 0.45rem 0 0 0;
  max-width: 900px;
  line-height: 1.45;
}

.hero-action-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-claim-all {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 0.55rem 1.25rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(30, 32, 42, 0.7);
  color: #7a7164;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.btn-claim-all.has-claimable {
  background: linear-gradient(135deg, #d4af37 0%, #b59410 50%, #997810 100%);
  color: #0d0e14;
  border: 1px solid #ffe066;
  box-shadow: 0 0 16px rgba(212, 175, 55, 0.45), 0 4px 12px rgba(0, 0, 0, 0.5);
  font-weight: 900;
  animation: pulseClaimButton 2.2s infinite ease-in-out;
}

.btn-claim-all.has-claimable:hover:not(:disabled) {
  background: linear-gradient(135deg, #f5d76e 0%, #d4af37 50%, #b59410 100%);
  transform: translateY(-1px);
  box-shadow: 0 0 24px rgba(212, 175, 55, 0.8), 0 6px 16px rgba(0, 0, 0, 0.6);
}

.btn-claim-all:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
}

.stat-claim-all-btn {
  background: linear-gradient(135deg, #b59410 0%, #997810 100%);
  border: 1px solid #ffd700;
  color: #ffffff;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.03em;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 0.25rem;
  align-self: flex-start;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
}

.stat-claim-all-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #d4af37 0%, #b59410 100%);
  transform: translateY(-1px);
  box-shadow: 0 0 12px rgba(212, 175, 55, 0.5);
}

.stat-claim-all-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

@keyframes pulseGold {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.75; transform: scale(1.02); }
}

@keyframes pulseClaimButton {
  0%, 100% {
    box-shadow: 0 0 12px rgba(212, 175, 55, 0.4), 0 4px 12px rgba(0, 0, 0, 0.5);
  }
  50% {
    box-shadow: 0 0 24px rgba(255, 215, 0, 0.8), 0 4px 16px rgba(0, 0, 0, 0.7);
  }
}

.btn-return-world {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #e6c845;
  text-decoration: none;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.35);
  padding: 0.55rem 1.1rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-return-world:hover {
  background: rgba(212, 175, 55, 0.25);
  color: #fff;
  border-color: #ffd700;
}

/* ── OVERVIEW STATS GRID ─────────────────────────────────────────── */
.overview-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: #08090d;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 1px #050608;
  border-radius: 6px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  position: relative;
  overflow: hidden;
}

.stat-card.stat-highlight {
  border-color: rgba(212, 175, 55, 0.5);
  background: linear-gradient(135deg, rgba(30, 25, 12, 0.6) 0%, #08090d 100%);
}

.stat-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-icon-wrap.gold { background: rgba(212, 175, 55, 0.15); border-color: rgba(212, 175, 55, 0.4); }
.stat-icon-wrap.purple { background: rgba(147, 51, 234, 0.15); border-color: rgba(147, 51, 234, 0.4); }
.stat-icon-wrap.yellow { background: rgba(234, 179, 8, 0.15); border-color: rgba(234, 179, 8, 0.4); }
.stat-icon-wrap.green { background: rgba(72, 187, 120, 0.15); border-color: rgba(72, 187, 120, 0.4); }
.stat-icon-wrap.gray { background: rgba(113, 128, 150, 0.15); border-color: rgba(113, 128, 150, 0.4); }

.stat-info {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.stat-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.3rem;
  color: #ffffff;
}

.stat-num.text-gold { color: #f6e05e; }
.stat-num.text-green { color: #68d391; }
.stat-num.text-gray { color: #a0aec0; }

.stat-label {
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 700;
  color: #8b8070;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-sub-badge {
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  font-weight: 700;
  color: #6a6050;
  text-transform: uppercase;
}

.stat-sub-badge.pulse {
  color: #ffd700;
  animation: pulseGold 2s infinite ease-in-out;
}

/* Global Progress Bar */
.global-progress-block {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.global-progress-labels {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.8rem;
}

.global-title {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  color: #b59410;
  text-transform: uppercase;
}

.global-stat {
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  color: #e2d9cc;
}

.global-pct {
  color: #ffd700;
  margin-left: 0.35rem;
}

.global-track {
  width: 100%;
  height: 10px;
  background: #06070a;
  border: 1px solid rgba(212, 175, 55, 0.4);
  border-radius: 4px;
  overflow: hidden;
}

.global-fill {
  height: 100%;
  background: linear-gradient(90deg, #b59410 0%, #ffd700 50%, #48bb78 100%);
  border-radius: 3px;
  transition: width 0.5s ease;
}

/* ── CONTROLS TOOLBAR ───────────────────────────────────────────── */
.controls-toolbar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.category-tabs-scroll {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.35rem;
}

.cat-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #0c0d12;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.65rem 1.15rem;
  border-radius: 4px;
  color: #8b8070;
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.cat-tab:hover {
  background: #14151e;
  color: #e2d9cc;
}

.cat-tab.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  border-color: #b91c1c;
  color: #f5efe6;
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.4);
}

.cat-tab-count {
  font-size: 0.72rem;
  opacity: 0.75;
}

.filter-controls-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.filter-label {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  color: #8b8070;
  text-transform: uppercase;
}

.select-filter {
  background: #0a0b0f;
  border: 1px solid rgba(212, 175, 55, 0.3);
  color: #f3ede2;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  padding: 0.45rem 0.85rem;
  border-radius: 4px;
  cursor: pointer;
}

.select-filter:focus {
  outline: none;
  border-color: #ffd700;
}

.search-input-wrap {
  flex: 1;
  min-width: 240px;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  font-size: 0.85rem;
  pointer-events: none;
  opacity: 0.6;
}

.search-input {
  width: 100%;
  background: #0a0b0f;
  border: 1px solid rgba(212, 175, 55, 0.3);
  color: #f3ede2;
  padding: 0.45rem 2rem 0.45rem 2.2rem;
  border-radius: 4px;
  font-size: 0.85rem;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: #ffd700;
}

.clear-search-btn {
  position: absolute;
  right: 0.6rem;
  background: none;
  border: none;
  color: #8b8070;
  cursor: pointer;
}

/* ── CARDS GRID ─────────────────────────────────────────────────── */
.cards-grid-section {
  width: 100%;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
}

@media (max-width: 480px) {
  .cards-grid {
    grid-template-columns: 1fr;
  }
}

/* Loading & Empty States */
.hall-loading-state,
.hall-error-state,
.no-results-state {
  text-align: center;
  padding: 4rem 1rem;
  background: #090a0d;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 6px;
}

.gothic-spinner {
  width: 42px;
  height: 42px;
  border: 3px solid rgba(212, 175, 55, 0.2);
  border-top-color: #ffd700;
  border-radius: 50%;
  margin: 0 auto 1.25rem;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-msg {
  font-family: 'Cinzel', serif;
  font-size: 1.05rem;
  color: #ecd485;
}

.error-msg {
  color: #fc8181;
  font-family: 'Cinzel', serif;
  margin-bottom: 1rem;
}

.btn-retry,
.btn-reset-filters {
  background: #785a15;
  border: 1px solid #ffd700;
  color: #fff;
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.6rem 1.2rem;
  border-radius: 4px;
  cursor: pointer;
}

.no-results-title {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  color: #f3ede2;
  margin: 0 0 0.5rem 0;
}

.no-results-sub {
  color: #8b8070;
  font-size: 0.9rem;
  margin-bottom: 1.25rem;
}

/* ══════════════════════════════════════════════════════════════════
   MORNING / LIGHT MODE THEME OVERRIDES (CRISP & 100% READABLE)
   ══════════════════════════════════════════════════════════════════ */
:global([data-theme="morning"] .achievements-full-page) {
  background: radial-gradient(circle at 50% 0%, #faf5ec 0%, #f0e6d5 65%, #e8dbc5 100%) !important;
  color: #120e07 !important;
}

:global([data-theme="morning"] .ach-hero-header) {
  background: #ffffff !important;
  border: 2px solid #c9aa6d !important;
  box-shadow: 0 4px 20px rgba(90, 60, 20, 0.1) !important;
}

:global([data-theme="morning"] .hall-crest-tag) {
  color: #7c5208 !important;
}

:global([data-theme="morning"] .hall-main-title) {
  color: #120e07 !important;
  text-shadow: none !important;
}

:global([data-theme="morning"] .hall-subtitle) {
  color: #3d3020 !important;
  font-weight: 600 !important;
}

:global([data-theme="morning"] .stat-card) {
  background: #ffffff !important;
  border: 1.5px solid #d4c4ab !important;
  box-shadow: 0 2px 8px rgba(90, 60, 20, 0.08) !important;
}

:global([data-theme="morning"] .stat-card.stat-highlight) {
  background: #fffdf5 !important;
  border-color: #b88e1a !important;
}

:global([data-theme="morning"] .stat-num) {
  color: #120e07 !important;
}

:global([data-theme="morning"] .stat-num.text-gold) {
  color: #7a5408 !important;
}

:global([data-theme="morning"] .stat-num.text-green) {
  color: #155724 !important;
}

:global([data-theme="morning"] .stat-num.text-gray) {
  color: #495057 !important;
}

:global([data-theme="morning"] .stat-label) {
  color: #453522 !important;
  font-weight: 700 !important;
}

:global([data-theme="morning"] .stat-sub-badge) {
  color: #6a5338 !important;
  font-weight: 700 !important;
}

:global([data-theme="morning"] .global-title) {
  color: #7c5208 !important;
}

:global([data-theme="morning"] .global-stat) {
  color: #120e07 !important;
}

:global([data-theme="morning"] .global-track) {
  background: #ded0bc !important;
  border: 1.5px solid #c9aa6d !important;
}

:global([data-theme="morning"] .cat-tab) {
  background: #ffffff !important;
  border: 1.5px solid #d4c4ab !important;
  color: #3d3020 !important;
}

:global([data-theme="morning"] .cat-tab:hover) {
  background: #fdf6e4 !important;
  color: #120e07 !important;
}

:global([data-theme="morning"] .cat-tab.active) {
  background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 100%) !important;
  border-color: #991b1b !important;
  color: #ffffff !important;
}

:global([data-theme="morning"] .filter-label) {
  color: #453522 !important;
  font-weight: 700 !important;
}

:global([data-theme="morning"] .select-filter),
:global([data-theme="morning"] .search-input) {
  background: #ffffff !important;
  border: 1.5px solid #c9aa6d !important;
  color: #120e07 !important;
}

:global([data-theme="morning"] .btn-return-world) {
  background: #fdf5e2 !important;
  border: 1.5px solid #b88e1a !important;
  color: #4a3404 !important;
  font-weight: 800 !important;
}

:global([data-theme="morning"] .btn-claim-all.has-claimable) {
  background: linear-gradient(135deg, #d4af37 0%, #b59410 100%) !important;
  color: #120e07 !important;
  border-color: #8c6708 !important;
}

:global([data-theme="morning"] .btn-claim-all:not(.has-claimable)) {
  background: #f0e6d5 !important;
  border: 1px solid #c9aa6d !important;
  color: #6a5a44 !important;
}

:global([data-theme="morning"] .stat-claim-all-btn) {
  background: linear-gradient(135deg, #b88e1a 0%, #8c6708 100%) !important;
  border-color: #ffd700 !important;
  color: #ffffff !important;
}
</style>
