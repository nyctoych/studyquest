<template>
  <div class="adv-chapter">
    <div class="adv-bg"></div>

    <div class="adv-chapter-content">
      <!-- Loading -->
      <div v-if="loading" class="chapter-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNSEALING CHAPTER ARCHIVES…</p>
      </div>

      <template v-else-if="chapterData">
        <!-- Chapter header -->
        <div class="chapter-header gothic-panel animate-scale-up">
          <router-link to="/adventure" class="back-btn btn btn-gothic-stone btn-sm">← MAP</router-link>
          <div class="ch-header-body">
            <div class="ch-icon">
              {{ chapterData.chapter?.icon || '📜' }}
            </div>
            <div>
              <p class="ch-chapter-label">CHAPTER {{ chapterData.chapter?.display_order }}</p>
              <h1 class="ch-name">{{ chapterData.chapter?.name }}</h1>
              <p class="ch-desc">{{ chapterData.chapter?.description }}</p>
            </div>
          </div>

          <!-- Chapter progress bar -->
          <div class="ch-progress-bar">
            <div class="ch-progress-fill" :style="{ width: chapterProgress + '%' }"></div>
          </div>
          <p class="ch-progress-text">
            {{ chapterData.chapter?.parts_completed || 0 }} / {{ chapterData.chapter?.total_parts || 10 }} EPISODES CONQUERED
            <template v-if="chapterData.chapter?.boss_defeated"> · 👑 CHAPTER BOSS BANISHED</template>
          </p>
        </div>

        <!-- Parts list -->
        <div class="parts-list animate-scale-up">
          <router-link
            v-for="part in chapterData.parts"
            :key="part.id"
            :to="partPath(part)"
            class="part-row"
            :class="getPartClass(part)"
          >
            <div class="part-num-badge" :class="'part-status-' + (part.part_status || 'locked')">
              {{ part.part_number === 10 ? '👑' : part.part_number }}
            </div>
            <div class="part-info">
              <span class="part-title">
                {{ part.part_number === 10 ? '⚔️ CHAPTER BOSS — ' : '' }}{{ part.title }}
              </span>
              <span class="part-status-text">{{ partStatusText(part) }}</span>
            </div>
            <div class="part-rewards">
              <span class="part-xp">+{{ part.xp_reward }} XP</span>
            </div>
            <div class="part-arrow">{{ part.part_status === 'locked' ? '🔒' : '→' }}</div>
          </router-link>
        </div>
      </template>

      <div v-else class="chapter-error gothic-panel">
        <p>Chapter archives not found.</p>
        <router-link to="/adventure" class="btn btn-gothic-stone">← RETURN TO MAP</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAdventureStore } from '../stores/adventure'

const route = useRoute()
const adventure = useAdventureStore()

const chapterData = computed(() => adventure.currentChapter)
const loading = computed(() => adventure.loading)
const chapterId = computed(() => Number(route.params.chapterId))

const chapterProgress = computed(() => {
  const ch = chapterData.value?.chapter
  if (!ch) return 0
  const parts = ch.parts_completed || 0
  const total = ch.total_parts || 10
  return Math.min(100, (parts / total) * 100)
})

onMounted(() => adventure.loadChapter(chapterId.value))

function partPath(part) {
  if (part.part_status === 'locked') return '#'
  if (part.part_number === 10) return `/adventure/${chapterId.value}/boss`
  return `/adventure/${chapterId.value}/part/${part.part_number}`
}

function getPartClass(part) {
  return {
    'part-available': part.part_status === 'available' || part.part_status === 'in_progress',
    'part-completed': part.part_status === 'completed' || part.part_status === 'mastered',
    'part-locked': part.part_status === 'locked' || !part.part_status,
    'part-boss': part.part_number === 10,
  }
}

function partStatusText(part) {
  const map = { available: 'UNLOCKED', in_progress: 'IN PROGRESS', completed: '✔ CONQUERED', mastered: '⭐ MASTERED', locked: 'LOCKED' }
  return map[part.part_status] || 'LOCKED'
}
</script>

<style scoped>
.adv-chapter {
  min-height: 100vh;
  position: relative;
  padding-top: 68px;
  background: var(--gothic-void, #050608);
  user-select: none;
}
.adv-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(ellipse at 50% 20%, rgba(139, 26, 26, 0.15) 0%, transparent 60%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.95) 0%, #050608 100%);
  pointer-events: none;
}
.adv-chapter-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
}

.chapter-loading {
  text-align: center;
  padding: 4rem 1rem;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #232533;
  border-top-color: var(--color-crimson-bright, #b91c1c);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}
.pixel-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 0.08em;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Header */
.chapter-header {
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.back-btn { margin-bottom: 1rem; display: inline-flex; }
.ch-header-body { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
.ch-icon { font-size: 2.2rem; flex-shrink: 0; }
.ch-chapter-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-crimson-bright, #b91c1c);
  letter-spacing: 0.12em;
  margin: 0 0 0.25rem 0;
}
.ch-name {
  font-family: var(--font-body, serif);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
}
.ch-desc {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  margin-top: 0.35rem;
  line-height: 1.5;
}
.ch-progress-bar {
  height: 8px;
  background: #06070a;
  border: 1px solid #282a3a;
  overflow: hidden;
  margin-bottom: 0.4rem;
}
.ch-progress-fill {
  height: 100%;
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 8px rgba(185, 28, 28, 0.8);
  transition: width 0.5s ease;
}
.ch-progress-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0;
}

/* Parts list */
.parts-list { display: flex; flex-direction: column; gap: 0.6rem; }
.part-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1.2rem;
  background: #0f1017;
  border: 1px solid #282a3a;
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
}
.part-row:hover:not(.part-locked) {
  transform: translateX(4px);
  border-color: var(--color-stone-600, #3a3b4c);
  background: #151620;
}
.part-locked { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
.part-available { border-color: #384260; }
.part-completed { border-color: #234335; }
.part-boss {
  border-color: var(--color-crimson, #8b1a1a);
  background: #140809;
}
.part-boss:hover { border-color: var(--color-crimson-bright, #b91c1c) !important; background: #1c0b0c !important; }

.part-num-badge {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  background: #181924;
  border: 1px solid #3a3b4c;
  color: var(--color-bone-dark, #8c8273);
}
.part-status-available, .part-status-in_progress {
  background: #101c2b;
  border-color: #38bdf8;
  color: #7dd3fc;
}
.part-status-completed, .part-status-mastered {
  background: #0f2317;
  border-color: #22c55e;
  color: #86efac;
}

.part-info { flex: 1; min-width: 0; }
.part-title {
  display: block;
  font-family: var(--font-body, serif);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
}
.part-boss .part-title { color: #fca5a5; }
.part-status-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: var(--color-bone-dark, #8c8273);
}
.part-rewards { flex-shrink: 0; }
.part-xp {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-gold-ancient, #e6c845);
}
.part-arrow {
  color: var(--color-bone-dark, #8c8273);
  font-size: 0.85rem;
  flex-shrink: 0;
  transition: transform 0.15s;
}
.part-row:hover:not(.part-locked) .part-arrow {
  transform: translateX(3px);
  color: var(--color-crimson-bright, #b91c1c);
}

.chapter-error {
  text-align: center;
  padding: 3rem;
  color: var(--color-bone-dark, #8c8273);
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
}
</style>
