<template>
  <div class="vault-world-page">
    <!-- Atmospheric Archive Environment Backdrop -->
    <div class="vault-backdrop-environment" aria-hidden="true">
      <img
        src="/assets/game/study-hall-shelves.jpg"
        alt=""
        class="vault-bg-image"
      />
      <!-- Atmospheric Vignette & Arcane Shimmers -->
      <div class="vault-ambient-vignette"></div>
      <div class="vault-purple-mist"></div>
      <div class="vault-lantern-glow glow-left"></div>
      <div class="vault-lantern-glow glow-right"></div>

      <!-- Floating Dust / Arcane Motes -->
      <div class="vault-magic-particles">
        <span class="mote m-1"></span>
        <span class="mote m-2"></span>
        <span class="mote m-3"></span>
        <span class="mote m-4"></span>
        <span class="mote m-5"></span>
      </div>
    </div>

    <!-- Main Content Container -->
    <div class="vault-content-container">
      <!-- ── TOP BAR: Navigation & Inscription Breadcrumb ──────── -->
      <header class="vault-top-nav animate-slide-down">
        <div class="vault-top-nav-spacer"></div>

        <div class="vault-sanctuary-pill">
          <span class="pill-icon">🔮</span>
          <span>PERSONAL SCHOLAR ARCHIVE</span>
        </div>

        <div class="vault-top-nav-spacer"></div>
      </header>

      <!-- ── HERO BANNER: The Vault Title & Lore ───────────────── -->
      <div class="vault-hero-banner animate-fade-in">
        <h1 class="vault-title">THE VAULT</h1>
        <p class="vault-subtitle">
          Your personal archive of forbidden notes, treasured reviewers, and manuscripts.
        </p>
        <p class="vault-lore-line">
          ✦ Every sealed grimoire preserves personal wisdom within the Mindrealm. ✦
        </p>
      </div>

      <!-- ── CONTROL DECK: Search, Filter, Sort & Add Reviewer ─── -->
      <div class="vault-control-deck animate-slide-down">
        <!-- Search Input -->
        <div class="vault-search-box">
          <span class="search-rune">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search manuscripts by title, topic, or file..."
            class="vault-search-input"
            aria-label="Search personal vault manuscripts"
          />
          <button v-if="searchQuery" @click="searchQuery = ''" class="clear-search-btn">✕</button>
        </div>

        <!-- Format Filters -->
        <div class="vault-filter-tabs">
          <button
            class="filter-tab-btn"
            :class="{ active: filterFormat === 'all' }"
            @click="filterFormat = 'all'"
          >
            ALL ({{ allBooksCount }})
          </button>
          <button
            class="filter-tab-btn tab-pdf"
            :class="{ active: filterFormat === 'pdf' }"
            @click="filterFormat = 'pdf'"
          >
            PDF ({{ pdfCount }})
          </button>
          <button
            class="filter-tab-btn tab-docx"
            :class="{ active: filterFormat === 'docs' }"
            @click="filterFormat = 'docs'"
          >
            DOCS ({{ docCount }})
          </button>
          <button
            class="filter-tab-btn tab-pptx"
            :class="{ active: filterFormat === 'ppt' }"
            @click="filterFormat = 'ppt'"
          >
            PPT ({{ pptCount }})
          </button>
          <button
            class="filter-tab-btn tab-txt"
            :class="{ active: filterFormat === 'txt' }"
            @click="filterFormat = 'txt'"
          >
            TXT ({{ txtCount }})
          </button>
        </div>

        <!-- Sort Selector -->
        <div class="vault-sort-selector">
          <label for="vault-sort-select" class="sort-label">Sort:</label>
          <select id="vault-sort-select" v-model="sortBy" class="sort-select">
            <option value="newest">Newest First</option>
            <option value="oldest">Oldest First</option>
            <option value="book_number">Book Number (Asc)</option>
            <option value="title">Title (A-Z)</option>
          </select>
        </div>

        <!-- Primary Action: [ + ADD REVIEWER ] -->
        <button
          type="button"
          class="btn-add-reviewer animate-pulse-glow"
          @click="showUploadModal = true"
          aria-label="Add new reviewer manuscript to Vault"
        >
          <span class="add-icon">+</span>
          <span class="add-text">ADD REVIEWER</span>
        </button>
      </div>

      <!-- ── MAIN ARCHIVE STAGE ────────────────────────────────── -->
      <!-- Loading State -->
      <div v-if="loading" class="vault-loading-state glass-card animate-scale-up">
        <div class="loading-rune-spinner">❖</div>
        <h3 class="loading-title">OPENING VAULT ARCHIVE…</h3>
        <p class="loading-sub">Unsealing personal reviewer manuscripts</p>
      </div>

      <!-- Empty Vault State -->
      <div v-else-if="allBooks.length === 0" class="vault-empty-state glass-card animate-scale-up">
        <div class="empty-grimoire-icon">📖</div>
        <h2 class="empty-title">YOUR VAULT IS EMPTY</h2>
        <p class="empty-lore">
          No personal manuscripts have been sealed here yet. Inscribe your first study reviewer to begin your collection.
        </p>
        <button
          type="button"
          class="btn-add-reviewer btn-empty-action"
          @click="showUploadModal = true"
        >
          <span class="add-icon">+</span>
          <span class="add-text">INSERT FIRST REVIEWER</span>
        </button>
      </div>

      <!-- No Filtered Results State -->
      <div v-else-if="filteredBooks.length === 0" class="vault-empty-state glass-card animate-scale-up">
        <div class="empty-grimoire-icon">🔍</div>
        <h3 class="empty-title">NO MATCHING MANUSCRIPTS FOUND</h3>
        <p class="empty-lore">
          No books match your current search query or format filter in this wing of the Vault.
        </p>
        <button
          type="button"
          class="btn btn-gothic-stone"
          @click="resetFilters"
        >
          Clear Filters
        </button>
      </div>

      <!-- Continuous Bookshelf Layout -->
      <main v-else class="vault-shelves-stage animate-fade-in">
        <div
          v-for="(tier, tIdx) in bookTiers"
          :key="tIdx"
          class="bookcase-tier"
        >
          <!-- Tier Header -->
          <div class="tier-arch-header">
            <span class="tier-rune">❖</span>
            <span class="tier-title">VAULT ARCHIVE TIER · SECTION {{ tIdx + 1 }}</span>
            <span class="tier-rune">❖</span>
          </div>

          <!-- Books Row on the Shelf -->
          <div class="tier-books-row">
            <VaultBook
              v-for="b in tier"
              :key="b.id"
              :book="b"
              :is-owner="true"
              @open="openReviewerModal"
              @edit="openEditModal"
              @delete="confirmDelete"
            />
          </div>

          <!-- Physical Wooden Shelf Plank -->
          <div class="shelf-wooden-plank">
            <div class="plank-bevel-top"></div>
            <div class="plank-wood-grain"></div>
            <div class="plank-shadow-under"></div>
          </div>
        </div>
      </main>

      <!-- Character subtle presence browsing their Vault -->
      <div class="vault-character-presence" v-if="characterSprite">
        <img
          :src="characterSprite"
          :alt="auth.nickname"
          class="vault-char-sprite"
        />
        <div class="vault-char-shadow"></div>
      </div>
    </div>

    <!-- ── MODALS ─────────────────────────────────────────────── -->
    <!-- 1. Upload Manuscript Modal -->
    <VaultUploadModal
      v-if="showUploadModal"
      :max-file-label="maxFileLabel"
      @close="showUploadModal = false"
      @uploaded="onBookUploaded"
    />

    <!-- 2. 3/4-Size Reviewer Reading Modal -->
    <VaultReviewerModal
      v-if="activeReadingBook"
      :book="activeReadingBook"
      @close="activeReadingBook = null"
      @updated="onBookUpdated"
    />

    <!-- 3. Edit Metadata Modal -->
    <VaultEditMetadataModal
      v-if="editingBook"
      :book="editingBook"
      @close="editingBook = null"
      @updated="onBookUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTransitionStore } from '../stores/transition'
import vaultService from '../services/vaultService'
import { CHARACTER_ANIMATIONS } from '../config/characterAnimations'
import { battleAudio } from '../services/battleAudio'
import Swal from 'sweetalert2'

import VaultBook from '../components/vault/VaultBook.vue'
import VaultUploadModal from '../components/vault/VaultUploadModal.vue'
import VaultReviewerModal from '../components/vault/VaultReviewerModal.vue'
import VaultEditMetadataModal from '../components/vault/VaultEditMetadataModal.vue'

const router = useRouter()
const auth = useAuthStore()
const transitionStore = useTransitionStore()

// State
const loading = ref(true)
const allBooks = ref([])
const maxFileLabel = ref('25 MB')
const searchQuery = ref('')
const filterFormat = ref('all')
const sortBy = ref('newest')

// Modal states
const showUploadModal = ref(false)
const activeReadingBook = ref(null)
const editingBook = ref(null)

// Character sprite
const characterSprite = computed(() => {
  const key = auth.characterKey || auth.user?.character_key || 'mage'
  const config = CHARACTER_ANIMATIONS[key] || CHARACTER_ANIMATIONS.mage
  return config?.idle || config?.sprite || ''
})

// Counts
const allBooksCount = computed(() => allBooks.value.length)

const pdfCount = computed(() => {
  return allBooks.value.filter(b => (b.file_type || '').toLowerCase() === 'pdf').length
})

const docCount = computed(() => {
  return allBooks.value.filter(b => ['doc', 'docx'].includes((b.file_type || '').toLowerCase())).length
})

const pptCount = computed(() => {
  return allBooks.value.filter(b => ['ppt', 'pptx'].includes((b.file_type || '').toLowerCase())).length
})

const txtCount = computed(() => {
  return allBooks.value.filter(b => (b.file_type || '').toLowerCase() === 'txt').length
})

// Filtered and sorted books list
const filteredBooks = computed(() => {
  let list = [...allBooks.value]

  // Filter format
  if (filterFormat.value !== 'all') {
    if (filterFormat.value === 'docs') {
      list = list.filter(b => ['doc', 'docx'].includes((b.file_type || '').toLowerCase()))
    } else if (filterFormat.value === 'ppt') {
      list = list.filter(b => ['ppt', 'pptx'].includes((b.file_type || '').toLowerCase()))
    } else {
      list = list.filter(b => (b.file_type || '').toLowerCase() === filterFormat.value)
    }
  }

  // Search filter
  const query = searchQuery.value.trim().toLowerCase()
  if (query) {
    list = list.filter(b => {
      const title = (b.title || '').toLowerCase()
      const desc = (b.description || '').toLowerCase()
      const orig = (b.original_filename || '').toLowerCase()
      const bNum = `book ${b.book_number}`.toLowerCase()
      return title.includes(query) || desc.includes(query) || orig.includes(query) || bNum.includes(query)
    })
  }

  // Sorting
  switch (sortBy.value) {
    case 'oldest':
      list.sort((a, b) => new Date(a.created_at) - new Date(b.created_at) || a.id - b.id)
      break
    case 'book_number':
      list.sort((a, b) => (a.book_number || 0) - (b.book_number || 0))
      break
    case 'title':
      list.sort((a, b) => (a.title || '').localeCompare(b.title || ''))
      break
    case 'newest':
    default:
      list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at) || b.id - a.id)
      break
  }

  return list
})

// Split continuous books into physical shelf rows (tiers of e.g. 5-6 books per shelf)
const bookTiers = computed(() => {
  const books = filteredBooks.value
  const tiers = []
  const perShelf = 5 // Books per shelf plank
  for (let i = 0; i < books.length; i += perShelf) {
    tiers.push(books.slice(i, i + perShelf))
  }
  return tiers
})

// Load user vault manuscripts
async function loadVault() {
  loading.value = true
  try {
    const res = await vaultService.getReviewers()
    if (res.success) {
      allBooks.value = res.books || []
      if (res.max_file_label) {
        maxFileLabel.value = res.max_file_label
      }
    }
  } catch (err) {
    console.error('Failed to load personal vault:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadVault()
})

function resetFilters() {
  searchQuery.value = ''
  filterFormat.value = 'all'
}

function navigateBack() {
  transitionStore.triggerTravel('/world', router)
}

function openReviewerModal(book) {
  activeReadingBook.value = book
}

function openEditModal(book) {
  editingBook.value = book
}

function onBookUploaded(newBook) {
  // Prepend to books list dynamically without reloading page
  allBooks.value.unshift(newBook)
}

function onBookUpdated(updatedBook) {
  const idx = allBooks.value.findIndex(b => b.id === updatedBook.id)
  if (idx !== -1) {
    allBooks.value[idx] = updatedBook
  }
}

async function confirmDelete(book) {
  const result = await Swal.fire({
    title: `Delete Book ${String(book.book_number).padStart(2, '0')}?`,
    html: `Are you sure you want to banish <strong>${book.title}</strong> from your Vault?<br><span style="font-size:0.8rem; color:#f87171;">This manuscript and physical file will be permanently removed.</span>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Banish Manuscript',
    cancelButtonText: 'Keep in Vault',
    background: '#120d24',
    color: '#f3e8ff',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#475569'
  })

  if (result.isConfirmed) {
    try {
      const res = await vaultService.deleteReviewer(book.id)
      if (res.success) {
        allBooks.value = allBooks.value.filter(b => b.id !== book.id)
        if (activeReadingBook.value?.id === book.id) {
          activeReadingBook.value = null
        }
        Swal.fire({
          title: 'Manuscript Banished',
          text: 'The reviewer has been deleted from your personal Vault.',
          icon: 'success',
          timer: 1600,
          showConfirmButton: false,
          background: '#120d24',
          color: '#f3e8ff'
        })
      }
    } catch (err) {
      Swal.fire({
        title: 'Deletion Failed',
        text: err.response?.data?.message || 'Could not delete reviewer.',
        icon: 'error',
        background: '#120d24',
        color: '#f3e8ff'
      })
    }
  }
}
</script>

<style scoped>
.vault-world-page {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #05030a;
  overflow-x: hidden;
  user-select: none;
}

/* ── Environment Backdrop ─────────────────────── */
.vault-backdrop-environment {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  z-index: 0;
  pointer-events: none;
}

.vault-bg-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  filter: brightness(0.38) contrast(1.15) saturate(1.1);
  transform: scale(1.02);
}

.vault-ambient-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 45%, transparent 20%, rgba(3, 2, 8, 0.75) 70%, rgba(2, 1, 6, 0.96) 100%);
}

.vault-purple-mist {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 80%, rgba(139, 92, 246, 0.12) 0%, transparent 65%);
}

.vault-lantern-glow {
  position: absolute;
  width: 320px;
  height: 320px;
  border-radius: 50%;
  filter: blur(60px);
  pointer-events: none;
  opacity: 0.35;
}
.glow-left {
  top: 15%;
  left: 5%;
  background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%);
}
.glow-right {
  top: 25%;
  right: 5%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.35) 0%, transparent 70%);
}

/* Floating Magic Dust */
.vault-magic-particles .mote {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(192, 132, 252, 0.7);
  box-shadow: 0 0 6px #c084fc;
  animation: floatMote 8s infinite linear;
}
.m-1 { top: 20%; left: 25%; animation-duration: 9s; }
.m-2 { top: 55%; left: 75%; animation-duration: 11s; }
.m-3 { top: 75%; left: 35%; animation-duration: 8s; }
.m-4 { top: 35%; left: 85%; animation-duration: 12s; }
.m-5 { top: 80%; left: 65%; animation-duration: 10s; }

@keyframes floatMote {
  0% { transform: translateY(0) scale(1); opacity: 0; }
  25% { opacity: 0.8; }
  75% { opacity: 0.6; }
  100% { transform: translateY(-120px) scale(0.6); opacity: 0; }
}

/* ── Content Container ────────────────────────── */
.vault-content-container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.25rem 2rem 4rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Top Navigation Bar ───────────────────────── */
.vault-top-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.vault-top-nav-spacer {
  min-width: 170px;
}
@media (max-width: 768px) {
  .vault-top-nav-spacer {
    display: none;
  }
}

.vault-sanctuary-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  color: #fbbf24;
  padding: 5px 14px;
  background: rgba(245, 158, 11, 0.1);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 20px;
}

.vault-owner-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  background: rgba(18, 12, 32, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 6px 12px;
}
.badge-title {
  color: #a78bfa;
  font-size: 0.68rem;
  font-weight: 700;
}
.badge-name {
  color: #faf5ff;
}

/* ── Hero Banner ──────────────────────────────── */
.vault-hero-banner {
  text-align: center;
  padding: 0.5rem 0 0.5rem;
}

.vault-title {
  font-family: 'Cinzel', 'Trajan Pro', Georgia, serif;
  font-size: 2.3rem;
  font-weight: 900;
  letter-spacing: 0.18em;
  color: #faf5ff !important;
  margin: 0 0 6px;
  text-shadow:
    0 2px 10px rgba(0, 0, 0, 0.95),
    0 0 25px rgba(245, 158, 11, 0.65),
    0 0 50px rgba(217, 119, 6, 0.4) !important;
}

.vault-subtitle {
  font-size: 0.95rem;
  color: #fef3c7 !important;
  margin: 0 0 4px;
  opacity: 1 !important;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.95), 0 0 12px rgba(0, 0, 0, 0.8) !important;
  font-weight: 600;
}

.vault-lore-line {
  font-size: 0.75rem;
  color: #fcd34d !important;
  letter-spacing: 0.08em;
  margin: 0;
  opacity: 1 !important;
  text-shadow: 0 1px 6px rgba(0, 0, 0, 0.95), 0 0 10px rgba(245, 158, 11, 0.5) !important;
  font-weight: 700;
}

/* ── Control Deck ─────────────────────────────── */
.vault-control-deck {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: rgba(15, 9, 28, 0.88);
  border: 1px solid rgba(139, 92, 246, 0.3);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
  border-radius: 8px;
  padding: 10px 16px;
}

.vault-search-box {
  position: relative;
  flex: 1;
  min-width: 240px;
  max-width: 360px;
}

.search-rune {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.8rem;
  pointer-events: none;
  opacity: 0.7;
}

.vault-search-input {
  width: 100%;
  background: rgba(8, 4, 16, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 6px;
  padding: 7px 30px 7px 30px;
  font-size: 0.8rem;
  color: #faf5ff;
  outline: none;
}
.vault-search-input:focus {
  border-color: #a855f7;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
}

.clear-search-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  color: #c4b5fd;
  cursor: pointer;
  font-size: 0.75rem;
}

/* Filter Tabs */
.vault-filter-tabs {
  display: flex;
  gap: 6px;
}

.filter-tab-btn {
  background: rgba(10, 6, 20, 0.7);
  border: 1px solid rgba(139, 92, 246, 0.25);
  color: #c4b5fd;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.filter-tab-btn:hover {
  background: rgba(30, 20, 50, 0.8);
  color: #faf5ff;
}
.filter-tab-btn.active {
  background: rgba(124, 58, 237, 0.35);
  border-color: #a855f7;
  color: #faf5ff;
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.4);
}

/* Sort */
.vault-sort-selector {
  display: flex;
  align-items: center;
  gap: 6px;
}

.sort-label {
  font-size: 0.72rem;
  color: #a78bfa;
}

.sort-select {
  background: rgba(8, 4, 16, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.25);
  color: #ede9fe;
  font-size: 0.74rem;
  padding: 5px 8px;
  border-radius: 4px;
  outline: none;
  cursor: pointer;
}

/* [ + ADD REVIEWER ] Button */
.btn-add-reviewer {
  display: flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);
  border: 1px solid #c084fc;
  color: #faf5ff;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.45);
  transition: all 0.2s ease;
}
.btn-add-reviewer:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 22px rgba(168, 85, 247, 0.65);
}
.add-icon {
  font-size: 1rem;
  font-weight: 900;
}

/* ── States ───────────────────────────────────── */
.vault-loading-state,
.vault-empty-state {
  margin: 3rem auto;
  max-width: 540px;
  padding: 3rem 2rem;
  text-align: center;
  background: rgba(18, 12, 32, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.loading-rune-spinner {
  font-size: 2.4rem;
  color: #c084fc;
  animation: spinRune 2s infinite linear;
}
@keyframes spinRune {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-title,
.empty-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.25rem;
  font-weight: 800;
  color: #faf5ff;
  margin: 0;
}

.empty-grimoire-icon {
  font-size: 3.5rem;
}

.empty-lore,
.loading-sub {
  font-size: 0.84rem;
  color: #c4b5fd;
  line-height: 1.5;
  margin: 0;
}

.btn-empty-action {
  margin-top: 8px;
  padding: 10px 22px;
}

/* ── Continuous Shelves Stage ─────────────────── */
.vault-shelves-stage {
  display: flex;
  flex-direction: column;
  gap: 3.5rem;
  margin-top: 1rem;
}

.bookcase-tier {
  position: relative;
  display: flex;
  flex-direction: column;
}

.tier-arch-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 1.25rem;
}

.tier-rune {
  color: #c084fc;
  font-size: 0.8rem;
  opacity: 0.7;
}

.tier-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.16em;
  color: #e9d5ff;
  text-shadow: 0 0 8px rgba(168, 85, 247, 0.4);
}

.tier-books-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
  align-items: flex-end;
  padding: 0 1rem;
  z-index: 2;
}

/* Physical Wooden Shelf Plank */
.shelf-wooden-plank {
  position: relative;
  width: 100%;
  height: 22px;
  margin-top: -6px;
  border-radius: 4px;
  background: linear-gradient(180deg, #3d2412 0%, #23140a 50%, #150b05 100%);
  border-top: 2px solid #6b4324;
  border-bottom: 2px solid #0d0703;
  box-shadow: 0 6px 14px rgba(0, 0, 0, 0.8);
  z-index: 1;
}

.plank-bevel-top {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.05) 100%);
}

.plank-wood-grain {
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(90deg, rgba(255, 255, 255, 0.015) 0px, rgba(255, 255, 255, 0.015) 20px, transparent 20px, transparent 40px);
}

.plank-shadow-under {
  position: absolute;
  bottom: -10px;
  left: 0;
  right: 0;
  height: 10px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
}

/* Character subtle presence */
.vault-character-presence {
  position: fixed;
  bottom: 12px;
  right: 28px;
  pointer-events: none;
  opacity: 0.85;
  z-index: 3;
}

.vault-char-sprite {
  height: 120px;
  filter: drop-shadow(0 0 12px rgba(168, 85, 247, 0.4));
}

/* ── Responsive ───────────────────────────────── */
@media (max-width: 900px) {
  .vault-content-container {
    padding: 1rem 1rem 4rem;
  }
  .tier-books-row {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
  }
  .vault-title {
    font-size: 1.8rem;
  }
  .vault-character-presence {
    display: none;
  }
}

@media (max-width: 600px) {
  .vault-control-deck {
    flex-direction: column;
    align-items: stretch;
  }
  .vault-search-box {
    max-width: 100%;
  }
  .vault-filter-tabs {
    flex-wrap: wrap;
    justify-content: center;
  }
  .tier-books-row {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}
</style>
