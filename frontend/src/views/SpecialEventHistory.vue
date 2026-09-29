<template>
  <div class="special-event-history-page">
    <div class="history-bg-vignette"></div>

    <div class="history-container animate-fade-in">
      <!-- Nav Header -->
      <div class="history-top-nav">
        <button class="rpg-nav-back-btn" @click="goToGate">
          <span>◀ Trial Gate</span>
        </button>
        <button class="rpg-nav-back-btn" @click="goToCitadel">
          <span>Citadel Hub</span>
        </button>
      </div>

      <!-- Hero Header -->
      <div class="history-hero gothic-panel">
        <div class="hero-icon">
          <PixelEmoji emoji="book" size="game" />
        </div>
        <div>
          <span class="hero-tag">PERSONAL CHRONICLES</span>
          <h1 class="hero-title">TRIAL EXAMINATION HISTORY</h1>
          <p class="hero-desc">
            Your permanent record of completed Special Event trials and board-style mock examinations.
          </p>
        </div>
      </div>

      <!-- Loading / Empty / History List -->
      <div v-if="loading" class="history-loading gothic-panel">
        <PixelEmoji emoji="crystal" size="md" />
        <span>Consulting trial records...</span>
      </div>

      <div v-else-if="historyList.length === 0" class="history-empty gothic-panel">
        <PixelEmoji emoji="scroll" size="lg" />
        <h3>NO COMPLETED TRIALS RECORDED</h3>
        <p>You have not yet completed a daily Special Event examination.</p>
        <button class="btn-start-now" @click="goToGate">
          Enter Today's Trial ▶
        </button>
      </div>

      <div v-else class="history-list">
        <div
          v-for="item in historyList"
          :key="item.id"
          class="history-card gothic-panel"
        >
          <div class="card-left">
            <span class="card-date">{{ formatDate(item.event_date) }}</span>
            <h3 class="card-title">{{ item.event_title }}</h3>
            <div class="card-sub-info">
              <span>Time: {{ formatSeconds(item.total_time) }}</span>
              <span>•</span>
              <span>XP: +{{ item.xp_awarded }}</span>
              <span>•</span>
              <span>Coins: +{{ item.coins_awarded }}</span>
            </div>

            <!-- Bloom Historical Accuracy (Section 57) -->
            <div v-if="bloomByAttempt[item.id]" class="card-bloom-history-row">
              <span class="bloom-hist-label">Cognitive Accuracy:</span>
              <div class="bloom-hist-chips">
                <span
                  v-for="lvl in getLevelsList(bloomByAttempt[item.id])"
                  :key="lvl.level"
                  class="bloom-chip"
                >
                  <span class="chip-name">{{ lvl.shortName }}:</span>
                  <span class="chip-acc" :class="getAccClass(lvl.accuracy)">{{ lvl.accuracy }}%</span>
                </span>
              </div>
            </div>
          </div>

          <div class="card-middle">
            <div class="score-pill">
              <span class="score-val">{{ item.score }} / 140</span>
              <span class="pct-badge" :class="item.percentage >= 75 ? 'passed' : 'failed'">
                {{ item.percentage }}%
              </span>
            </div>
            <div class="counts-row">
              <span class="correct-text">{{ item.correct_count }} Correct</span>
              <span>/</span>
              <span class="incorrect-text">{{ item.incorrect_count }} Incorrect</span>
            </div>
          </div>

          <div class="card-right">
            <button class="review-btn bloom-btn" @click="openBloom(item.id)">
              <PixelEmoji emoji="crystal" size="xs" /> Cognitive Analytics
            </button>
            <button class="review-btn" @click="openReview(item.id)">
              <PixelEmoji emoji="scroll" size="xs" /> Full Review
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import { useTransitionStore } from '../stores/transition'

const router = useRouter()
const transitionStore = useTransitionStore()

const loading = ref(true)
const historyList = ref([])
const bloomByAttempt = ref({})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatSeconds(sec) {
  if (!sec || sec < 0) return '00:00:00'
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function getLevelsList(levelsObj) {
  if (!levelsObj) return []
  const order = [
    { key: 'remember', short: 'Rem' },
    { key: 'understand', short: 'Und' },
    { key: 'apply', short: 'App' },
    { key: 'analyze', short: 'Ana' },
    { key: 'evaluate', short: 'Eva' },
    { key: 'create', short: 'Cre' }
  ]
  return order.map(o => ({
    level: o.key,
    shortName: o.short,
    accuracy: levelsObj[o.key]?.accuracy ?? 0
  }))
}

function getAccClass(acc) {
  if (acc >= 80) return 'acc-high'
  if (acc >= 60) return 'acc-mid'
  return 'acc-low'
}

async function loadHistory() {
  loading.value = true
  try {
    const [res, bloomRes] = await Promise.all([
      specialEventService.getHistory(),
      specialEventService.getBloomHistory().catch(() => null)
    ])
    if (res.success) {
      historyList.value = res.history || []
    }
    if (bloomRes && bloomRes.success && bloomRes.history) {
      const map = {}
      bloomRes.history.forEach(item => {
        map[item.attempt_id] = item.levels
      })
      bloomByAttempt.value = map
    }
  } catch (err) {
    console.error('Failed to load history', err)
  } finally {
    loading.value = false
  }
}

function openReview(attemptId) {
  router.push(`/special-event/results/${attemptId}?tab=review`)
}

function openBloom(attemptId) {
  router.push(`/special-event/results/${attemptId}?tab=bloom`)
}

function goToGate() {
  router.push('/special-event')
}

function goToCitadel() {
  transitionStore.triggerTravel('/world', router)
}

onMounted(() => {
  loadHistory()
})
</script>

<style scoped>
.special-event-history-page {
  position: relative;
  min-height: 100vh;
  background: #080614;
  color: #f1f5f9;
  padding: 1.5rem 1rem 4rem;
}

.history-bg-vignette {
  position: fixed;
  inset: 0;
  background: radial-gradient(ellipse at 50% 20%, rgba(88, 28, 135, 0.12) 0%, rgba(8, 6, 20, 0.95) 75%, #04030a 100%);
  pointer-events: none;
  z-index: 0;
}

.history-container {
  position: relative;
  z-index: 1;
  max-width: 950px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.history-top-nav {
  display: flex;
  justify-content: space-between;
}

.rpg-nav-back-btn {
  background: rgba(22, 17, 39, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
}
.rpg-nav-back-btn:hover {
  background: rgba(43, 31, 75, 0.95);
  color: #fff;
}

.gothic-panel {
  background: rgba(16, 12, 30, 0.95);
  border: 1.5px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
}

.history-hero {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  background: linear-gradient(135deg, rgba(30, 20, 56, 0.95) 0%, rgba(13, 10, 26, 0.98) 100%);
}

.hero-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #a78bfa;
}

.hero-title {
  font-family: 'Cinzel', serif;
  font-size: 1.7rem;
  font-weight: 800;
  color: #f8fafc;
  letter-spacing: 1px;
  margin: 0.2rem 0;
}

.hero-desc {
  font-size: 0.85rem;
  color: #cbd5e1;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.history-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  transition: all 0.2s ease;
}
.history-card:hover {
  border-color: #8b5cf6;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(139, 92, 246, 0.25);
}

.card-left {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.card-date {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #a78bfa;
}

.card-title {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  color: #f1f5f9;
  margin: 0;
}

.card-sub-info {
  font-size: 0.75rem;
  color: #94a3b8;
  display: flex;
  gap: 0.4rem;
}

.card-middle {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.score-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.score-val {
  font-size: 1.3rem;
  font-weight: bold;
  color: #f8fafc;
}

.pct-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
}
.pct-badge.passed {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid #10b981;
}
.pct-badge.failed {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid #ef4444;
}

.counts-row {
  font-size: 0.72rem;
  color: #94a3b8;
  display: flex;
  gap: 0.3rem;
}
.correct-text { color: #34d399; }
.incorrect-text { color: #f87171; }

.review-btn {
  background: rgba(30, 24, 55, 0.9);
  border: 1px solid #8b5cf6;
  color: #f1f5f9;
  padding: 0.55rem 0.95rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}
.review-btn:hover {
  background: #8b5cf6;
  color: #fff;
}

.history-loading,
.history-empty {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem 1.5rem;
  color: #cbd5e1;
}

.btn-start-now {
  background: #8b5cf6;
  border: none;
  color: #fff;
  padding: 0.65rem 1.25rem;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  font-weight: 600;
}

/* Bloom History Row */
.card-bloom-history-row {
  margin-top: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.bloom-hist-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #a78bfa;
}

.bloom-hist-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.bloom-chip {
  background: rgba(18, 12, 36, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 4px;
  padding: 0.15rem 0.45rem;
  font-size: 0.72rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.chip-name {
  color: #94a3b8;
  font-size: 0.68rem;
}

.chip-acc {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  font-weight: 700;
}

.chip-acc.acc-high { color: #34d399; }
.chip-acc.acc-mid { color: #fbbf24; }
.chip-acc.acc-low { color: #f87171; }

.bloom-btn {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.35) 0%, rgba(67, 56, 202, 0.35) 100%);
  border-color: #a855f7;
  color: #e9d5ff;
}

.bloom-btn:hover {
  background: linear-gradient(135deg, #7c3aed 0%, #4338ca 100%);
  color: #fff;
}
</style>
