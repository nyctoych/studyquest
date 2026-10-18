<template>
  <div class="mindkeeper-overlay" @click.self="$emit('close')">
    <div
      class="mindkeeper-modal glass-card animate-scale-up"
      role="dialog"
      aria-modal="true"
      aria-labelledby="mindkeeper-profile-title"
    >
      <!-- Corner Filigree -->
      <span class="corner-filigree cf-tl">❖</span>
      <span class="corner-filigree cf-tr">❖</span>
      <span class="corner-filigree cf-bl">❖</span>
      <span class="corner-filigree cf-br">❖</span>

      <!-- Close Button -->
      <button
        type="button"
        class="mindkeeper-close-btn"
        @click="$emit('close')"
        aria-label="Close Mindkeeper record"
      >
        ✕
      </button>

      <!-- Loading State -->
      <div v-if="loading" class="mindkeeper-loading">
        <div class="ancient-rune-spinner">❖</div>
        <p class="loading-label">CONSULTING MINDKEEPER RECORD…</p>
        <span class="loading-sub">Retrieving sacred scrolls from the Hall of Records</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="mindkeeper-error">
        <span class="error-rune">⚠️</span>
        <p>{{ error }}</p>
        <button class="btn btn-gothic-stone btn-sm" @click="$emit('close')">Return</button>
      </div>

      <!-- Profile Content Container -->
      <div v-else class="mindkeeper-profile-body">
        <!-- ── SECTION 1: HEADER & CHARACTER SHOWCASE ─────────── -->
        <header class="mindkeeper-hero-header">
          <!-- Character Stage -->
          <div class="character-portrait-stage">
            <div class="char-halo-glow"></div>
            <img
              v-if="characterSprite"
              :src="characterSprite"
              :alt="characterName"
              class="character-hero-sprite"
            />
            <AvatarImg
              v-else
              :character="characterClass"
              :player="profile"
              :style-name="profile.avatar_style || 'adventurer'"
              :seed="profile.avatar_seed || profile.username"
              :size="88"
              class="character-fallback-avatar"
            />
            <div class="character-class-badge">
              <span class="class-type-tag">{{ characterClass }}</span>
            </div>
          </div>

          <!-- Hero Identity Info -->
          <div class="hero-identity-text">
            <div class="identity-tag-row">
              <span class="record-tag">📜 MINDKEEPER RECORD</span>
              <span v-if="rank" class="rank-pill">RANK #{{ rank }}</span>
              <span v-if="isSelf" class="you-pill">YOU</span>
            </div>

            <h2 id="mindkeeper-profile-title" class="hero-display-name">
              {{ profile.nickname || profile.username }}
            </h2>

            <div class="hero-titles-row">
              <span class="character-title-label text-gold">{{ characterName }}</span>
              <span class="title-sep">•</span>
              <span class="scholar-title-string">{{ scholarTitle }}</span>
            </div>

            <div class="hero-level-badges">
              <span class="level-chip">LEVEL {{ profile.level || 1 }}</span>
              <span class="rating-chip">⚡ {{ profile.battle_rating || 1200 }} RATING</span>
              <span class="tier-chip" :style="{ color: rankTier.color }">{{ rankTier.name }} Tier</span>
            </div>
          </div>
        </header>

        <!-- ── SECTION 2: ADVENTURE PROGRESSION ───────────────── -->
        <section class="profile-section-card progression-card">
          <div class="section-title-row">
            <span class="sec-icon">⚔️</span>
            <h3 class="sec-title">ADVENTURE PROGRESSION</h3>
          </div>

          <div class="adventure-progress-display">
            <div class="adv-badge-block">
              <span class="adv-label">CURRENT CHAPTER</span>
              <strong class="adv-chapter-num">CHAPTER {{ adventure.chapter_number }}</strong>
              <span class="adv-chapter-name">{{ adventure.chapter_name }}</span>
            </div>

            <div class="adv-divider-rune">✦</div>

            <div class="adv-badge-block">
              <span class="adv-label">CURRENT PART</span>
              <strong class="adv-part-num">PART {{ adventure.part_number }}</strong>
              <span class="adv-part-meta">of {{ adventure.total_parts }} parts</span>
            </div>
          </div>
        </section>

        <!-- ── SECTION 3: UNLOCKED ACHIEVEMENTS ───────────────── -->
        <section class="profile-section-card achievements-card">
          <div class="section-title-row">
            <span class="sec-icon">🏆</span>
            <h3 class="sec-title">UNLOCKED ACHIEVEMENTS ({{ achievements.length }})</h3>
          </div>

          <div v-if="achievements.length === 0" class="empty-achievements-note">
            <span>No sacred milestones unlocked yet.</span>
          </div>

          <div v-else class="achievements-scroll-grid">
            <div
              v-for="ach in achievements"
              :key="ach.id"
              class="achievement-mini-badge"
              :class="`rarity-${ach.rarity || 'common'}`"
              :title="ach.description"
            >
              <span class="ach-icon">{{ ach.icon || '🏆' }}</span>
              <div class="ach-text-wrap">
                <strong class="ach-name">{{ ach.name }}</strong>
                <span class="ach-rarity-tag">{{ (ach.rarity || 'Common').toUpperCase() }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- ── SECTION 4: VAULT REVIEWERS (AS BOOKS) ──────────── -->
        <section class="profile-section-card vault-card">
          <div class="section-title-row">
            <span class="sec-icon">🔮</span>
            <h3 class="sec-title">PERSONAL VAULT ({{ vaultBooks.length }})</h3>
            <span class="sec-hint">Click any book to read this Mindkeeper's reviewer</span>
          </div>

          <!-- Empty Vault State for another user -->
          <div v-if="vaultBooks.length === 0" class="empty-profile-vault-state">
            <span class="empty-vault-rune">📜</span>
            <p class="empty-vault-text">
              No personal manuscripts have been added to this Mindkeeper's Vault.
            </p>
          </div>

          <!-- Vault Books Grid -->
          <div v-else class="profile-vault-books-grid">
            <VaultBook
              v-for="b in vaultBooks"
              :key="b.id"
              :book="b"
              :is-owner="isSelf"
              @open="openBookViewer"
            />
          </div>
        </section>
      </div>

      <!-- Nested 3/4-Size Reviewer Modal when reading a book -->
      <VaultReviewerModal
        v-if="selectedReviewerBook"
        :book="selectedReviewerBook"
        @close="selectedReviewerBook = null"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../../services/api'
import AvatarImg from '../AvatarImg.vue'
import VaultBook from '../vault/VaultBook.vue'
import VaultReviewerModal from '../vault/VaultReviewerModal.vue'
import { getScholarTitle, getRankTier } from '../../utils/scholarTitles'

const props = defineProps({
  userId: {
    type: [Number, String],
    required: true
  },
  rank: {
    type: [Number, String],
    default: null
  }
})

defineEmits(['close'])

const loading = ref(true)
const error = ref('')

const isSelf = ref(false)
const profile = ref({})
const character = ref(null)
const adventure = ref({ chapter_number: 1, chapter_name: '', part_number: 1, total_parts: 10 })
const achievements = ref([])
const vaultBooks = ref([])

const selectedReviewerBook = ref(null)

const scholarTitle = computed(() => {
  return getScholarTitle(profile.value.level || 1, profile.value.battle_rating || 1200)
})

const rankTier = computed(() => {
  return getRankTier(profile.value.battle_rating || 1200)
})

const characterName = computed(() => {
  if (!character.value) return 'Necromancer'
  return character.value.name || 'Scholar'
})

const characterClass = computed(() => {
  if (!character.value) return 'MAGE'
  return (character.value.class_type || 'MAGE').toUpperCase()
})

const characterSprite = computed(() => {
  if (!character.value) return ''
  return character.value.sprite_path || character.value.idle_asset || ''
})

async function fetchMindkeeperRecord() {
  loading.value = true
  error.value = ''

  try {
    const res = await api.get(`/profile/mindkeeper/${props.userId}`)
    if (res.data.success) {
      isSelf.value = res.data.is_self
      profile.value = res.data.profile || {}
      character.value = res.data.character
      adventure.value = res.data.adventure || { chapter_number: 1, part_number: 1, total_parts: 10 }
      achievements.value = res.data.achievements || []
      vaultBooks.value = res.data.vault || []
    } else {
      error.value = res.data.message || 'Mindkeeper record could not be loaded.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Server error loading Mindkeeper record.'
  } finally {
    loading.value = false
  }
}

function openBookViewer(book) {
  selectedReviewerBook.value = book
}

onMounted(() => {
  fetchMindkeeperRecord()
})
</script>

<style scoped>
.mindkeeper-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 2, 7, 0.88);
  backdrop-filter: blur(10px);
  z-index: 130;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 1.5rem;
  padding-top: calc(var(--hud-height, 68px) + 1.75rem);
  padding-bottom: 2.5rem;
  overflow-y: auto;
}

.mindkeeper-modal {
  position: relative;
  width: min(85vw, 1150px);
  margin-top: 1.25rem;
  margin-bottom: 2rem;
  max-height: calc(100vh - var(--hud-height, 68px) - 5rem);
  background: linear-gradient(145deg, rgba(18, 11, 32, 0.98) 0%, rgba(9, 5, 18, 0.99) 100%);
  border: 1px solid rgba(139, 92, 246, 0.45);
  box-shadow:
    0 30px 80px rgba(0, 0, 0, 0.95),
    0 0 50px rgba(139, 92, 246, 0.25),
    inset 0 0 25px rgba(139, 92, 246, 0.08);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  color: #ede9fe;
}

/* Corner filigree */
.corner-filigree {
  position: absolute;
  font-size: 0.95rem;
  color: #c4b5fd;
  opacity: 0.7;
  pointer-events: none;
  z-index: 5;
}
.cf-tl { top: 8px; left: 8px; }
.cf-tr { top: 8px; right: 8px; }
.cf-bl { bottom: 8px; left: 8px; }
.cf-br { bottom: 8px; right: 8px; }

/* Close Button */
.mindkeeper-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 6px;
  color: #c4b5fd;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  transition: all 0.2s ease;
}
.mindkeeper-close-btn:hover {
  background: rgba(239, 68, 68, 0.3);
  border-color: #f87171;
  color: #fee2e2;
}

/* Loading & Error */
.mindkeeper-loading,
.mindkeeper-error {
  padding: 4rem 2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.ancient-rune-spinner {
  font-size: 2.2rem;
  color: #c084fc;
  animation: spinRune 2s infinite linear;
}
@keyframes spinRune {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-label {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  color: #e9d5ff;
}
.loading-sub {
  font-size: 0.75rem;
  color: #a78bfa;
}

/* Profile Body Scroll */
.mindkeeper-profile-body {
  overflow-y: auto;
  padding: 2rem 2.25rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── SECTION 1: HERO HEADER ──────────────────── */
.mindkeeper-hero-header {
  display: flex;
  align-items: center;
  gap: 1.75rem;
  background: rgba(20, 12, 38, 0.75);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.25rem 1.75rem;
}

.character-portrait-stage {
  position: relative;
  width: 96px;
  height: 96px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.char-halo-glow {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(168, 85, 247, 0.4) 0%, transparent 70%);
  animation: haloPulse 3s infinite ease-in-out;
}
@keyframes haloPulse {
  0%, 100% { transform: scale(0.95); opacity: 0.4; }
  50% { transform: scale(1.1); opacity: 0.7; }
}

.character-hero-sprite {
  max-width: 90px;
  max-height: 90px;
  object-fit: contain;
  filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.5));
  z-index: 2;
}

.character-fallback-avatar {
  border-radius: 50%;
  border: 2px solid #a855f7;
  z-index: 2;
}

.character-class-badge {
  position: absolute;
  bottom: -6px;
  background: rgba(10, 6, 20, 0.9);
  border: 1px solid #c084fc;
  border-radius: 10px;
  padding: 2px 8px;
  font-size: 0.62rem;
  font-weight: 800;
  color: #e9d5ff;
  letter-spacing: 0.08em;
  z-index: 3;
}

.hero-identity-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.identity-tag-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.record-tag {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #fbbf24;
}

.rank-pill {
  font-size: 0.65rem;
  font-weight: 800;
  background: rgba(245, 158, 11, 0.2);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #fef3c7;
  padding: 1px 6px;
  border-radius: 4px;
}

.you-pill {
  font-size: 0.62rem;
  font-weight: 800;
  background: rgba(16, 185, 129, 0.2);
  border: 1px solid #10b981;
  color: #6ee7b7;
  padding: 1px 6px;
  border-radius: 4px;
}

.hero-display-name {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.55rem;
  font-weight: 800;
  color: #faf5ff;
  margin: 0;
  text-shadow: 0 0 12px rgba(192, 132, 252, 0.5);
}

.hero-titles-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
}

.title-sep {
  color: rgba(139, 92, 246, 0.4);
}

.scholar-title-string {
  color: #c4b5fd;
}

.hero-level-badges {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.level-chip,
.rating-chip,
.tier-chip {
  font-size: 0.72rem;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 4px;
  background: rgba(10, 6, 20, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.15);
}
.level-chip { border-color: rgba(168, 85, 247, 0.5); color: #e9d5ff; }
.rating-chip { border-color: rgba(245, 158, 11, 0.5); color: #fde68a; }

/* ── COMMON SECTION CARD ─────────────────────── */
.profile-section-card {
  background: rgba(16, 10, 30, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 10px;
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.section-title-row {
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px dashed rgba(139, 92, 246, 0.25);
  padding-bottom: 8px;
}

.sec-icon { font-size: 1.1rem; }

.sec-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.84rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #faf5ff;
  margin: 0;
}

.sec-hint {
  margin-left: auto;
  font-size: 0.7rem;
  color: #a78bfa;
  font-style: italic;
}

/* ── SECTION 2: ADVENTURE PROGRESSION ────────── */
.adventure-progress-display {
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 0.5rem 1rem;
}

.adv-badge-block {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-align: center;
}

.adv-label {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #a78bfa;
}

.adv-chapter-num,
.adv-part-num {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.2rem;
  font-weight: 800;
  color: #fbbf24;
  text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
}

.adv-chapter-name {
  font-size: 0.75rem;
  color: #e9d5ff;
  max-width: 320px;
}

.adv-part-meta {
  font-size: 0.72rem;
  color: #c4b5fd;
}

.adv-divider-rune {
  font-size: 1rem;
  color: #a855f7;
  opacity: 0.6;
}

/* ── SECTION 3: ACHIEVEMENTS ─────────────────── */
.achievements-scroll-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
  max-height: 180px;
  overflow-y: auto;
  padding-right: 4px;
}

.achievement-mini-badge {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(10, 6, 20, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  padding: 8px 10px;
}
.achievement-mini-badge.rarity-rare { border-color: rgba(56, 189, 248, 0.4); }
.achievement-mini-badge.rarity-epic { border-color: rgba(168, 85, 247, 0.5); }
.achievement-mini-badge.rarity-legendary { border-color: rgba(245, 158, 11, 0.6); }

.ach-icon { font-size: 1.3rem; }

.ach-text-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
}

.ach-name {
  font-size: 0.78rem;
  color: #faf5ff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ach-rarity-tag {
  font-size: 0.62rem;
  color: #a78bfa;
}

.empty-achievements-note {
  padding: 1rem;
  text-align: center;
  font-size: 0.8rem;
  color: #a78bfa;
}

/* ── SECTION 4: VAULT REVIEWERS ──────────────── */
.empty-profile-vault-state {
  padding: 2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.empty-vault-rune { font-size: 2.2rem; }

.empty-vault-text {
  font-size: 0.84rem;
  color: #c4b5fd;
  margin: 0;
}

.profile-vault-books-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
  padding: 0.5rem 0;
}

/* ── Responsive ──────────────────────────────── */
@media (max-width: 800px) {
  .mindkeeper-overlay {
    padding-top: calc(var(--hud-height, 60px) + 1.25rem);
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-bottom: 1.5rem;
  }
  .mindkeeper-modal {
    width: 95vw;
    margin-top: 0.75rem;
    margin-bottom: 1.5rem;
    max-height: calc(100vh - var(--hud-height, 60px) - 3rem);
  }
  .mindkeeper-profile-body {
    padding: 1.25rem;
  }
  .mindkeeper-hero-header {
    flex-direction: column;
    text-align: center;
  }
  .adventure-progress-display {
    flex-direction: column;
    gap: 1rem;
  }
  .sec-hint {
    display: none;
  }
}
</style>
