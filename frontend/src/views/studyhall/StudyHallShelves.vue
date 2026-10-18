<template>
  <div class="study-hall-phase phase-2-shelves">
    <!-- Dedicated Phase 2 Shelves Background -->
    <div class="shelves-backdrop-wrap">
      <img
        :src="bgImage"
        alt="Ancient Deep Library Archive Shelves"
        class="shelves-bg-img"
      />
      <div class="shelves-ambient-vignette"></div>
      <div class="shelves-lantern-glow glow-1"></div>
      <div class="shelves-lantern-glow glow-2"></div>
    </div>

    <!-- Shelves Control & Focus Bar -->
    <header class="shelves-control-deck animate-slide-down">
      <div class="deck-left-group">
        <!-- Back Button on the side of search bar -->
        <button
          type="button"
          class="shelves-back-btn"
          @click="handleBackToLibrary"
          title="Back to Study Hall Entrance"
          id="shelves-back-btn"
        >
          <span class="shelves-back-arrow">◀</span>
          <span class="shelves-back-label">Back</span>
        </button>

        <!-- Search the Archives -->
        <div class="archive-search-box">
          <span class="search-rune">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search the Archives (e.g. Neurodevelopmental, Bipolar, Trauma)..."
            class="archive-search-input"
            aria-label="Search Archives"
          />
          <button v-if="searchQuery" @click="searchQuery = ''" class="clear-search-btn">✕</button>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="archive-filter-tabs">
        <button
          class="filter-btn"
          :class="{ active: filterTab === 'all' }"
          @click="filterTab = 'all'"
        >
          ALL VOLUMES ({{ resolvedBooks.length }})
        </button>
        <button
          class="filter-btn"
          :class="{ active: filterTab === 'in_progress' }"
          @click="filterTab = 'in_progress'"
        >
          IN PROGRESS ({{ countInProgress }})
        </button>
        <button
          class="filter-btn"
          :class="{ active: filterTab === 'mastered' }"
          @click="filterTab = 'mastered'"
        >
          MASTERED ({{ countMastered }})
        </button>
      </div>

      <!-- Overall Archive Preservation Metric & Theme Toggle -->
      <div class="deck-right-group">
        <div class="archive-progress-pill">
          <span class="ap-label">PRESERVED:</span>
          <div class="ap-bar">
            <div class="ap-fill" :style="{ width: overallProgress + '%' }"></div>
          </div>
          <span class="ap-val">{{ overallProgress }}%</span>
        </div>

        <!-- Audio Volume Control Widget (Increase/Decrease/Slider/Mute) -->
        <AudioVolumeControl id="shelves-volume-widget" />

        <!-- Global Theme Toggle (NIGHT ☾ ↔ MORNING ☀) -->
        <button
          type="button"
          class="shelves-theme-toggle"
          :class="{ 'is-morning': game.isMorning }"
          @click="game.toggleTheme()"
          :title="game.isMorning ? 'Switch to Night Mode (☾)' : 'Switch to Day Mode (☀)'"
          id="shelves-theme-toggle-btn"
        >
          <span class="theme-icon">{{ game.isMorning ? '☀' : '☾' }}</span>
          <span class="theme-label">{{ game.isMorning ? 'DAY' : 'NIGHT' }}</span>
        </button>
      </div>
    </header>

    <!-- Today's Focus Banner if available -->
    <div v-if="todayFocus" class="archive-focus-banner animate-fade-in">
      <div class="focus-herald">
        <span class="focus-icon">⭐</span>
        <div class="focus-text">
          <span class="focus-tag">CURRENT ARCHIVE FOCUS</span>
          <strong class="focus-title">{{ todayFocus.part?.title || todayFocus.domain?.name }}</strong>
          <span class="focus-sub" v-if="todayFocus.domain?.name && todayFocus.part?.title">
            {{ todayFocus.domain.name }} · Part {{ todayFocus.part.part_number }}
          </span>
        </div>
      </div>
      <button class="btn btn-gothic-crimson btn-sm" @click="handleOpenFocus">
        OPEN CURRENT PAGE →
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="shelves-loading">
      <div class="loading-spinner"></div>
      <p class="pixel-text">CONSULTING ANCIENT SHELVES…</p>
    </div>

    <!-- Shelves Grid (Books organized along physical bookcases) -->
    <main v-else class="shelves-stage animate-scale-up">
      <!-- Empty state if search returns nothing -->
      <div v-if="filteredBooks.length === 0" class="shelves-empty-state">
        <span class="empty-icon">📜</span>
        <h3>No Volumes Found</h3>
        <p>No grimoires match your search query in this wing of the library.</p>
        <button class="btn btn-gothic-stone btn-sm" @click="searchQuery = ''; filterTab = 'all'">
          Clear Search Filter
        </button>
      </div>

      <!-- Shelves Display -->
      <div v-else class="shelves-structure">
        <!-- Shelf Section 1: Volumes 1 to 6 -->
        <div class="bookcase-tier" v-if="shelfOneBooks.length">
          <div class="tier-arch-header">
            <span class="tier-rune">❖</span>
            <span class="tier-title">UPPER ARCHIVE TIER · CLINICAL SYNDROMES</span>
            <span class="tier-rune">❖</span>
          </div>

          <div class="tier-books-row">
            <StudyHallBook
              v-for="b in shelfOneBooks"
              :key="b.id"
              :book="b"
              :is-selected="selectedBook?.id === b.id"
              @select="onSelectBook"
            />
          </div>
          <!-- Physical Wooden Shelf Plank -->
          <div class="shelf-wooden-plank">
            <div class="plank-bevel-top"></div>
            <div class="plank-wood-grain"></div>
            <div class="plank-shadow-under"></div>
          </div>
        </div>

        <!-- Shelf Section 2: Volumes 7 to 12 -->
        <div class="bookcase-tier" v-if="shelfTwoBooks.length">
          <div class="tier-arch-header">
            <span class="tier-rune">❖</span>
            <span class="tier-title">LOWER ARCHIVE TIER · TRAUMA, SOMATIC &amp; SYSTEMIC DISORDERS</span>
            <span class="tier-rune">❖</span>
          </div>

          <div class="tier-books-row">
            <StudyHallBook
              v-for="b in shelfTwoBooks"
              :key="b.id"
              :book="b"
              :is-selected="selectedBook?.id === b.id"
              @select="onSelectBook"
            />
          </div>
          <!-- Physical Wooden Shelf Plank -->
          <div class="shelf-wooden-plank">
            <div class="plank-bevel-top"></div>
            <div class="plank-wood-grain"></div>
            <div class="plank-shadow-under"></div>
          </div>
        </div>
      </div>

      <!-- Character subtle presence browsing the shelves -->
      <div class="shelves-character-presence" v-if="characterSprite">
        <img
          :src="characterSprite"
          :alt="auth.nickname"
          class="shelves-char-sprite"
        />
        <div class="shelves-char-shadow"></div>
      </div>
    </main>

    <!-- Book Preview / Inspection Modal -->
    <StudyHallBookPreview
      v-if="inspectingBook"
      :book="inspectingBook"
      @close="inspectingBook = null"
      @open="handleOpenBook"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useTransitionStore } from '../../stores/transition'
import { useGameStore } from '../../stores/game'
import { curriculumService } from '../../services/curriculumService'
import { resolveChapterBook } from '../../services/studyHallResolver'
import { resolveAsset } from '../../services/bossMapping'
import { CHARACTER_ANIMATIONS } from '../../config/characterAnimations'
import { battleAudio } from '../../services/battleAudio'
import StudyHallBook from '../../components/studyhall/StudyHallBook.vue'
import StudyHallBookPreview from '../../components/studyhall/StudyHallBookPreview.vue'
import AudioVolumeControl from '../../components/ui/AudioVolumeControl.vue'

const props = defineProps({
  onSelectChapter: {
    type: Function,
    default: null
  },
  onBackToPhase1: {
    type: Function,
    default: null
  }
})

const router = useRouter()
const auth = useAuthStore()
const transitionStore = useTransitionStore()
const game = useGameStore()

const bgImage = computed(() => resolveAsset('assets/game/study-hall-shelves.jpg'))
const rawDomains = ref([])
const todayFocus = ref(null)
const loading = ref(true)
const searchQuery = ref('')
const filterTab = ref('all')
const inspectingBook = ref(null)
const selectedBook = ref(null)

const characterSprite = computed(() => {
  const key = auth.characterKey || auth.user?.character_key || 'mage'
  const config = CHARACTER_ANIMATIONS[key] || CHARACTER_ANIMATIONS.mage
  return config?.idle || config?.sprite || ''
})

onMounted(async () => {
  try {
    const [overviewRes, todayRes] = await Promise.allSettled([
      curriculumService.getOverview(),
      curriculumService.getTodayRecommendation()
    ])

    if (overviewRes.status === 'fulfilled' && overviewRes.value?.success) {
      rawDomains.value = overviewRes.value.domains || []
    }

    if (todayRes.status === 'fulfilled' && todayRes.value?.success) {
      todayFocus.value = todayRes.value.recommendation || null
    }
  } catch (err) {
    console.warn('Error fetching Study Hall shelves:', err)
  } finally {
    loading.value = false
  }
})

const resolvedBooks = computed(() => {
  return rawDomains.value.map((d, idx) => resolveChapterBook(d, idx, rawDomains.value))
})

const filteredBooks = computed(() => {
  let list = resolvedBooks.value

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(b =>
      b.title.toLowerCase().includes(q) ||
      b.description.toLowerCase().includes(q) ||
      b.style.moniker.toLowerCase().includes(q)
    )
  }

  // Tab filter
  if (filterTab.value === 'in_progress') {
    list = list.filter(b => b.completedPages > 0 && !b.isCompleted)
  } else if (filterTab.value === 'mastered') {
    list = list.filter(b => b.isMastered || b.isCompleted)
  }

  return list
})

const shelfOneBooks = computed(() => {
  // If filtered, show first half or all if small
  if (filteredBooks.value.length <= 6) return filteredBooks.value
  return filteredBooks.value.slice(0, Math.ceil(filteredBooks.value.length / 2))
})

const shelfTwoBooks = computed(() => {
  if (filteredBooks.value.length <= 6) return []
  return filteredBooks.value.slice(Math.ceil(filteredBooks.value.length / 2))
})

const countInProgress = computed(() => {
  return resolvedBooks.value.filter(b => b.completedPages > 0 && !b.isCompleted).length
})

const countMastered = computed(() => {
  return resolvedBooks.value.filter(b => b.isMastered || b.isCompleted).length
})

const overallProgress = computed(() => {
  const books = resolvedBooks.value
  if (!books.length) return 0
  const totalPages = books.reduce((acc, b) => acc + b.totalPages, 0)
  const completedPages = books.reduce((acc, b) => acc + b.completedPages, 0)
  return totalPages > 0 ? Math.round((completedPages / totalPages) * 100) : 0
})

function onSelectBook(book) {
  if (book.isLocked) {
    inspectingBook.value = book
    return
  }
  inspectingBook.value = book
}

function handleOpenBook(book) {
  inspectingBook.value = null
  selectedBook.value = book
  battleAudio.playBookOpen()

  if (props.onSelectChapter) {
    transitionStore.playPhaseTransition(
      {
        title: `UNSEALING VOLUME ${book.roman}`,
        subtitle: `${book.title}...`,
        icon: 'scroll',
        themeColor: 'gold',
        type: 'library'
      },
      () => props.onSelectChapter(book.id)
    )
  } else {
    transitionStore.triggerTravel(`/studyhall/chapter/${book.id}`, router)
  }
}

function handleOpenFocus() {
  if (!todayFocus.value?.domain?.id) return
  const domainId = todayFocus.value.domain.id
  const partNum = todayFocus.value.part?.part_number || 1

  transitionStore.triggerTravel(`/studyhall/chapter/${domainId}/part/${partNum}`, router)
}

function handleBackToLibrary() {
  if (props.onBackToPhase1) {
    transitionStore.playPhaseTransition(
      {
        title: 'RETURNING TO ENTRANCE',
        subtitle: 'Stepping back to the grand library hall...',
        icon: 'castle',
        themeColor: 'violet',
        type: 'library'
      },
      props.onBackToPhase1
    )
  } else {
    transitionStore.triggerTravel('/studyhall', router)
  }
}
</script>

<style scoped>
.study-hall-phase {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #06040b;
  overflow-x: hidden;
  user-select: none;
  padding-top: 0;
}

/* Backdrop */
.shelves-backdrop-wrap {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.shelves-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  image-rendering: pixelated;
  opacity: 0.85;
}

.shelves-ambient-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 40%, transparent 20%, rgba(4, 3, 8, 0.75) 75%, rgba(2, 1, 5, 0.95) 100%),
    linear-gradient(to top, rgba(3, 2, 6, 0.95) 0%, transparent 50%, rgba(3, 2, 6, 0.7) 100%);
}

.shelves-lantern-glow {
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.15) 0%, transparent 70%);
}

.glow-1 { top: 25%; left: 10%; }
.glow-2 { top: 25%; right: 10%; }

/* Controls Deck Navigation Header */
.shelves-control-deck {
  position: sticky;
  top: 0;
  z-index: 100;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(180deg, rgba(16, 9, 30, 0.98) 0%, rgba(11, 7, 20, 0.98) 100%);
  border-bottom: 1px solid rgba(139, 92, 246, 0.3);
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.deck-left-group {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 280px;
  max-width: 560px;
}

.shelves-back-btn {
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
  white-space: nowrap;
  flex-shrink: 0;
}

.shelves-back-btn:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateX(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.shelves-back-btn:active {
  transform: translateX(0);
}

.shelves-back-arrow {
  color: #fbbf24;
  font-size: 0.85rem;
  line-height: 1;
}

.shelves-back-label {
  line-height: 1;
}

.archive-search-box {
  position: relative;
  flex: 1;
  min-width: 180px;
  display: flex;
  align-items: center;
}

.search-rune {
  position: absolute;
  left: 0.75rem;
  font-size: 0.85rem;
  color: #a78bfa;
  pointer-events: none;
}

.archive-search-input {
  width: 100%;
  height: 38px;
  box-sizing: border-box;
  padding: 0 2rem 0 2.2rem;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 6px;
  color: #f1f5f9;
  font-family: inherit;
  font-size: 0.82rem;
  outline: none;
  transition: all 0.2s ease;
}

.archive-search-input:focus {
  border-color: #a855f7;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.4);
}

.clear-search-btn {
  position: absolute;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 0.85rem;
}

.archive-filter-tabs {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.filter-btn {
  padding: 0.45rem 0.85rem;
  background: rgba(22, 18, 38, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  color: #94a3b8;
  font-family: inherit;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  color: #f1f5f9;
  border-color: rgba(168, 85, 247, 0.4);
}

.filter-btn.active {
  background: rgba(139, 92, 246, 0.3);
  border-color: #a855f7;
  color: #ffffff;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.4);
}

.deck-right-group {
  display: flex;
  align-items: center;
  gap: 12px;
}

.archive-progress-pill {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.4rem 0.85rem;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(251, 191, 36, 0.3);
  border-radius: 20px;
}

.ap-label {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #fbbf24;
}

.ap-bar {
  width: 70px;
  height: 6px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 3px;
  overflow: hidden;
}

.ap-fill {
  height: 100%;
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
  box-shadow: 0 0 6px #f59e0b;
}

.ap-val {
  font-size: 0.72rem;
  font-weight: 800;
  color: #ffffff;
}

.shelves-theme-toggle {
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
  white-space: nowrap;
}

.shelves-theme-toggle:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.shelves-theme-toggle:active {
  transform: translateY(0);
}

.shelves-theme-toggle .theme-icon {
  font-size: 0.95rem;
  line-height: 1;
  color: #fbbf24;
}

.shelves-theme-toggle .theme-label {
  line-height: 1;
}

.shelves-theme-toggle.is-morning {
  background: #EAD8B5;
  border-color: #B88A42;
  color: #3D2B1F;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.shelves-theme-toggle.is-morning:hover {
  background: #FFFDF7;
  border-color: #735022;
  color: #1c130d;
}

.shelves-theme-toggle.is-morning .theme-icon {
  color: #b45309;
}

/* ── SHELVES MUSIC TOGGLE BUTTON ── */
.shelves-music-toggle {
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

.shelves-music-toggle:hover {
  background: rgba(28, 16, 52, 0.96);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

.shelves-music-toggle:active {
  transform: translateY(0);
}

.shelves-music-toggle.is-muted {
  background: rgba(45, 10, 10, 0.88);
  border-color: rgba(239, 68, 68, 0.5);
  color: #fca5a5;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(239, 68, 68, 0.2);
}

.shelves-music-toggle.is-muted:hover {
  background: rgba(70, 15, 15, 0.96);
  border-color: #ef4444;
  color: #ffffff;
}

.shelves-music-toggle .music-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.shelves-music-toggle .music-label {
  line-height: 1;
}

.shelves-music-toggle.is-morning {
  background: #EAD8B5;
  border-color: #B88A42;
  color: #3D2B1F;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.shelves-music-toggle.is-morning:hover {
  background: #FFFDF7;
  border-color: #735022;
  color: #1c130d;
}

.shelves-music-toggle.is-morning.is-muted {
  background: #fed7aa;
  border-color: #ea580c;
  color: #7c2d12;
}

/* Today's Focus Banner */
.archive-focus-banner {
  position: relative;
  z-index: 15;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin: 1rem 1.5rem 0.5rem;
  padding: 0.85rem 1.25rem;
  background: linear-gradient(90deg, rgba(76, 29, 149, 0.45) 0%, rgba(30, 27, 75, 0.6) 100%);
  border: 1px solid rgba(168, 85, 247, 0.4);
  border-radius: 6px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6);
}

.focus-herald {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.focus-icon {
  font-size: 1.5rem;
}

.focus-text {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.focus-tag {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #fbbf24;
}

.focus-title {
  font-size: 0.95rem;
  color: #ffffff;
}

.focus-sub {
  font-size: 0.75rem;
  color: #cbd5e1;
}

/* Loading & Empty State */
.shelves-loading {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  min-height: 50vh;
  color: #a78bfa;
}

.shelves-empty-state {
  text-align: center;
  padding: 4rem 1.5rem;
  color: #cbd5e1;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

/* Shelves Structure */
.shelves-stage {
  position: relative;
  z-index: 10;
  flex: 1;
  padding: 1.5rem 2rem 4rem;
  max-width: 100%;
  width: 100%;
  margin: 0;
}

.bookcase-tier {
  margin-bottom: 3.5rem;
}

.tier-arch-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.2);
}

.tier-rune {
  color: #fbbf24;
  font-size: 0.85rem;
  text-shadow: 0 0 8px rgba(245, 158, 11, 0.6);
}

.tier-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.88rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  color: #fef08a;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.95), 0 0 12px rgba(245, 158, 11, 0.4);
}

.tier-books-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  align-items: flex-end;
  padding: 0 1rem 0.5rem;
}

/* Physical Wooden Shelf Plank */
.shelf-wooden-plank {
  position: relative;
  width: 100%;
  height: 22px;
  background: linear-gradient(180deg, #451a03 0%, #270f03 60%, #170701 100%);
  border-top: 2px solid #92400e;
  border-bottom: 2px solid #000000;
  border-radius: 2px;
  box-shadow:
    0 8px 16px rgba(0, 0, 0, 0.9),
    inset 0 1px 1px rgba(255, 255, 255, 0.2);
  margin-top: -2px;
}

.plank-bevel-top {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: rgba(251, 191, 36, 0.15);
}

.plank-shadow-under {
  position: absolute;
  bottom: -16px;
  left: 2%;
  right: 2%;
  height: 16px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.85) 0%, transparent 100%);
  pointer-events: none;
}

/* Character Presence */
.shelves-character-presence {
  position: fixed;
  bottom: 1.5rem;
  right: 2.5rem;
  z-index: 15;
  pointer-events: none;
  opacity: 0.85;
}

.shelves-char-sprite {
  width: 72px;
  height: 72px;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.8));
}

.shelves-char-shadow {
  width: 48px;
  height: 8px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  margin: -4px auto 0;
}

@media (max-width: 900px) {
  .shelves-control-deck {
    flex-direction: column;
    align-items: stretch;
  }
  .archive-search-box {
    max-width: 100%;
  }
  .archive-filter-tabs {
    overflow-x: auto;
    padding-bottom: 0.25rem;
  }
  .shelves-character-presence {
    display: none;
  }
}
</style>
