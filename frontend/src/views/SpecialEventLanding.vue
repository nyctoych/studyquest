<template>
  <div class="special-event-landing-page">
    <div class="landing-bg-overlay"></div>

    <div class="landing-container custom-container animate-fade-in">
      <!-- Breadcrumbs / Top Bar -->
      <div class="landing-top-nav">
        <button class="rpg-nav-back-btn" @click="goBackToWorld">
          <span>◀ Citadel Hub</span>
        </button>
        <div class="top-nav-links">
          <button class="nav-link-btn" @click="goToHistory">
            <PixelEmoji emoji="book" size="xs" /> Event History
          </button>
          <button class="nav-link-btn admin-link" @click="goToAdmin">
            <PixelEmoji emoji="gear" size="xs" /> Chamber Controls
          </button>
        </div>
      </div>

      <!-- Main Atmospheric Header -->
      <div class="trial-hero-banner">
        <div class="banner-crest">
          <PixelEmoji emoji="shield" size="game" />
        </div>
        <div class="banner-text">
          <span class="banner-subtitle">STUDYQUEST GRAND EXAMINATION</span>
          <h1 class="banner-title">THE DAILY TRIAL</h1>
          <p class="banner-desc">
            The Obsidian Chamber of Assessment opens once each day. 140 rigorous clinical vignettes and psychopathology questions derived strictly from the Study Hall archives.
          </p>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="trial-loading-card glass-panel">
        <PixelEmoji emoji="sparkles" size="md" />
        <span>Attuning with the Mindrealm Examination Chamber...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="trial-error-card glass-panel">
        <PixelEmoji emoji="cross" size="md" />
        <span>{{ error }}</span>
        <button class="rpg-retry-btn" @click="loadTodayEvent">Retry</button>
      </div>

      <!-- Active Event Content -->
      <div v-else class="trial-content-grid">
        <!-- Left: Today's Trial Specifications & Instructions -->
        <div class="trial-specs-card gothic-panel">
          <div class="specs-header flex items-center gap-2">
            <PixelEmoji emoji="scroll" size="sm" />
            <h2>CHAMBER SPECIFICATIONS</h2>
          </div>

          <div class="specs-list">
            <div class="spec-row">
              <span class="spec-label">EXAMINATION DATE</span>
              <span class="spec-value highlight">{{ eventDateFormatted }}</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">TOTAL TRIAL ITEMS</span>
              <span class="spec-value">140 Questions</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">TIME ALLOTMENT</span>
              <span class="spec-value">{{ eventData?.duration_minutes || 140 }} Minutes</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">ACADEMIC SOURCE</span>
              <span class="spec-value">Study Hall (DSM-5 Standard)</span>
            </div>
            <div class="spec-row">
              <span class="spec-label">EVALUATION AUTHORITY</span>
              <span class="spec-value">Server-Authoritative Diagnostic Scoring</span>
            </div>
          </div>

          <!-- Today's 4 Assigned Study Hall Subjects -->
          <div v-if="eventData?.assigned_domains && eventData.assigned_domains.length > 0" class="assigned-domains-box">
            <h3 class="assigned-domains-title flex items-center gap-1.5">
              <PixelEmoji emoji="book" size="xs" /> TODAY'S 4 SUBJECTS (35 items each)
            </h3>
            <div class="domains-grid">
              <div v-for="(dom, idx) in eventData.assigned_domains" :key="dom.id || idx" class="domain-card-pill">
                <span class="domain-badge">0{{ idx + 1 }}</span>
                <div class="domain-detail">
                  <span class="domain-title">{{ dom.name }}</span>
                  <span class="domain-quota">{{ dom.question_count || 35 }} Questions</span>
                </div>
              </div>
            </div>
          </div>

          <div class="rules-box">
            <h3 class="rules-title flex items-center gap-1.5">
              <PixelEmoji emoji="target" size="xs" /> TRIAL PROTOCOLS
            </h3>
            <ul class="rules-list">
              <li>Each question presents 4 distinct options (A, B, C, D) with exactly one defensibly correct answer.</li>
              <li>Timer runs continuously on the server even if you close or refresh your browser.</li>
              <li>Answers are not revealed during the active trial to preserve authentic examination conditions.</li>
              <li>You may navigate forward, backward, jump to any question, or mark questions for review before final submission.</li>
              <li>A comprehensive breakdown by psychiatric domain, question type, and difficulty is generated upon completion.</li>
            </ul>
          </div>
        </div>

        <!-- Right: Player Status & Entrance Gate -->
        <div class="trial-gate-card gothic-panel">
          <div class="gate-status-header">
            <PixelEmoji emoji="crystal" size="md" />
            <h2 class="gate-title">TRIAL STATUS</h2>
          </div>

          <!-- Scenario A: Never started today -->
          <div v-if="!userAttempt" class="gate-action-box">
            <div class="gate-crest-orb">
              <div class="orb-pulse"></div>
              <PixelEmoji emoji="sword" size="xl" />
            </div>
            <p class="gate-prompt">
              The 140 questions of today's examination are prepared in the archives. Enter when you are ready for a focused evaluation session.
            </p>
            <button
              class="enter-trial-btn"
              :disabled="starting"
              @click="handleStartTrial"
            >
              <span v-if="!starting" class="flex items-center justify-center gap-2">
                <PixelEmoji emoji="sword" size="sm" /> BEGIN THE DAILY TRIAL
              </span>
              <span v-else>Entering Examination Chamber...</span>
            </button>
          </div>

          <!-- Scenario B: In Progress -->
          <div v-else-if="userAttempt.status === 'in_progress'" class="gate-action-box in-progress">
            <div class="gate-crest-orb warning">
              <PixelEmoji emoji="clock" size="xl" />
            </div>
            <div class="in-progress-badge">TRIAL IN PROGRESS</div>
            <p class="gate-prompt">
              You have an active examination session underway. Your answers and timer are preserved.
            </p>
            <div class="active-stats-pill">
              <span>Answered: <strong>{{ userAttempt.summary?.answered_count || 0 }} / 140</strong></span>
              <span>Remaining: <strong>{{ formatSeconds(userAttempt.remaining_seconds) }}</strong></span>
            </div>
            <button class="resume-trial-btn" @click="resumeTrial">
              RESUME EXAMINATION ▶
            </button>
          </div>

          <!-- Scenario C: Completed Today -->
          <div v-else class="gate-action-box completed">
            <div class="gate-crest-orb success">
              <PixelEmoji emoji="trophy" size="xl" />
            </div>
            <div class="completed-badge">TRIAL SEALED & COMPLETED</div>
            <div class="score-display-box">
              <div class="score-number">{{ userAttempt.score }} / 140</div>
              <div class="score-pct">{{ userAttempt.percentage }}% Mastery</div>
            </div>
            <div class="completed-actions">
              <button class="results-btn" @click="viewResults">
                <PixelEmoji emoji="scroll" size="xs" /> Performance Analytics
              </button>
              <button class="review-btn" @click="viewReview">
                <PixelEmoji emoji="book" size="xs" /> Review All Questions
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import { useTransitionStore } from '../stores/transition'
import { useToastStore } from '../stores/toast'

const router = useRouter()
const transitionStore = useTransitionStore()
const toast = useToastStore()

const loading = ref(true)
const starting = ref(false)
const error = ref('')
const eventData = ref(null)
const userAttempt = ref(null)

const eventDateFormatted = computed(() => {
  if (!eventData.value?.event_date) return 'Today'
  const d = new Date(eventData.value.event_date + 'T00:00:00')
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

function formatSeconds(sec) {
  if (!sec || sec < 0) return '00:00:00'
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

async function loadTodayEvent() {
  loading.value = true
  error.value = ''
  try {
    const res = await specialEventService.getTodayEvent()
    if (res.has_event) {
      eventData.value = res.event
      userAttempt.value = res.user_attempt
    } else {
      error.value = res.message || 'No published event for today.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load Special Event.'
  } finally {
    loading.value = false
  }
}

async function handleStartTrial() {
  if (!eventData.value?.id) return
  starting.value = true
  try {
    const res = await specialEventService.startAttempt(eventData.value.id)
    if (res.success && res.attempt_id) {
      transitionStore.triggerTravel(`/special-event/exam?attemptId=${res.attempt_id}`, router)
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to start trial.')
  } finally {
    starting.value = false
  }
}

function resumeTrial() {
  if (!userAttempt.value?.id) return
  transitionStore.triggerTravel(`/special-event/exam?attemptId=${userAttempt.value.id}`, router)
}

function viewResults() {
  if (!userAttempt.value?.id) return
  router.push(`/special-event/results/${userAttempt.value.id}`)
}

function viewReview() {
  if (!userAttempt.value?.id) return
  router.push(`/special-event/results/${userAttempt.value.id}?tab=review`)
}

function goToHistory() {
  router.push('/special-event/history')
}

function goToAdmin() {
  router.push('/special-event/admin')
}

function goBackToWorld() {
  transitionStore.triggerTravel('/world', router)
}

onMounted(() => {
  loadTodayEvent()
})
</script>

<style scoped>
.special-event-landing-page {
  position: relative;
  min-height: 100vh;
  padding: 1.5rem 1rem 4rem;
  color: #f1f5f9;
  background: radial-gradient(circle at 50% 20%, #1e1136 0%, #0a0715 80%, #05030a 100%);
  overflow-x: hidden;
}

.landing-bg-overlay {
  position: fixed;
  inset: 0;
  background-image: radial-gradient(rgba(139, 92, 246, 0.08) 1px, transparent 1px);
  background-size: 24px 24px;
  pointer-events: none;
  z-index: 0;
}

.landing-container {
  position: relative;
  z-index: 1;
  max-width: 1050px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.landing-top-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.rpg-nav-back-btn,
.nav-link-btn {
  background: rgba(22, 17, 39, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s ease;
}

.rpg-nav-back-btn:hover,
.nav-link-btn:hover {
  background: rgba(43, 31, 75, 0.95);
  color: #fff;
  border-color: #a78bfa;
}

.nav-link-btn.admin-link {
  border-color: rgba(234, 179, 8, 0.4);
  color: #fde047;
}

.top-nav-links {
  display: flex;
  gap: 0.5rem;
}

/* Trial Hero Banner */
.trial-hero-banner {
  background: linear-gradient(135deg, rgba(30, 20, 56, 0.9) 0%, rgba(13, 10, 26, 0.95) 100%);
  border: 2px solid rgba(139, 92, 246, 0.45);
  border-radius: 12px;
  padding: 2rem 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7), inset 0 0 20px rgba(139, 92, 246, 0.15);
}

.banner-crest {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-text {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.banner-subtitle {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #a78bfa;
  letter-spacing: 1.5px;
}

.banner-title {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 800;
  letter-spacing: 2px;
  color: #f8fafc;
  text-shadow: 0 0 15px rgba(139, 92, 246, 0.6);
  margin: 0;
}

.banner-desc {
  font-size: 0.88rem;
  color: #cbd5e1;
  line-height: 1.5;
  max-width: 720px;
}

/* Content Grid */
.trial-content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 850px) {
  .trial-hero-banner {
    flex-direction: column;
    text-align: center;
  }
  .trial-content-grid {
    grid-template-columns: 1fr;
  }
}

.gothic-panel {
  background: rgba(18, 14, 34, 0.92);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.specs-header,
.gate-status-header {
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.75rem;
}

.specs-header h2,
.gate-status-header h2 {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #e2e8f0;
  letter-spacing: 0.5px;
  margin: 0;
}

.specs-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.spec-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.4rem 0.5rem;
  background: rgba(29, 23, 53, 0.5);
  border-radius: 4px;
  font-size: 0.75rem;
}

.spec-label {
  color: #94a3b8;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
}

.spec-value {
  color: #f1f5f9;
  font-weight: 600;
}

.spec-value.highlight {
  color: #c4b5fd;
}

.assigned-domains-box {
  background: rgba(18, 13, 36, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.4);
  border-radius: 8px;
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.assigned-domains-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #c4b5fd;
  letter-spacing: 0.5px;
}

.domains-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.5rem;
}

.domain-card-pill {
  background: rgba(29, 21, 56, 0.7);
  border: 1px solid rgba(167, 139, 250, 0.25);
  border-radius: 6px;
  padding: 0.5rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.55rem;
  transition: all 0.2s ease;
}

.domain-card-pill:hover {
  background: rgba(43, 31, 75, 0.85);
  border-color: #a78bfa;
}

.domain-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #a78bfa;
  background: rgba(139, 92, 246, 0.2);
  padding: 0.2rem 0.35rem;
  border-radius: 4px;
}

.domain-detail {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.domain-title {
  font-size: 0.75rem;
  color: #f1f5f9;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.domain-quota {
  font-size: 0.65rem;
  color: #34d399;
  font-family: 'Press Start 2P', monospace;
}

.rules-box {
  background: rgba(13, 9, 25, 0.85);
  border: 1px dashed rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 1rem;
}

.rules-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #fbbf24;
  margin-bottom: 0.65rem;
}

.rules-list {
  list-style: square inside;
  font-size: 0.78rem;
  color: #94a3b8;
  line-height: 1.6;
}

/* Gate Card Actions */
.gate-action-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1.15rem;
  padding: 1.25rem 0.5rem;
}

.gate-crest-orb {
  position: relative;
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, rgba(13, 10, 26, 0.9) 70%);
  border: 2px solid #8b5cf6;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 25px rgba(139, 92, 246, 0.4);
}

.gate-crest-orb.warning {
  border-color: #f59e0b;
  box-shadow: 0 0 25px rgba(245, 158, 11, 0.4);
}

.gate-crest-orb.success {
  border-color: #10b981;
  box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);
}

.gate-prompt {
  font-size: 0.84rem;
  color: #cbd5e1;
  line-height: 1.5;
  max-width: 380px;
}

.enter-trial-btn {
  width: 100%;
  max-width: 360px;
  background: linear-gradient(135deg, #7c3aed 0%, #4338ca 100%);
  border: 1px solid #c4b5fd;
  color: #fff;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.72rem;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 0 20px rgba(124, 58, 237, 0.6);
  transition: all 0.25s ease;
}

.enter-trial-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 0 30px rgba(124, 58, 237, 0.9);
}

.enter-trial-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.resume-trial-btn {
  width: 100%;
  max-width: 360px;
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border: 1px solid #fde68a;
  color: #fff;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.72rem;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 0 20px rgba(217, 119, 6, 0.6);
  transition: all 0.25s ease;
}

.in-progress-badge {
  background: rgba(245, 158, 11, 0.2);
  border: 1px solid #f59e0b;
  color: #fbbf24;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
}

.completed-badge {
  background: rgba(16, 185, 129, 0.2);
  border: 1px solid #10b981;
  color: #6ee7b7;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
}

.active-stats-pill {
  display: flex;
  gap: 1.5rem;
  background: rgba(25, 20, 45, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.3);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.75rem;
  color: #cbd5e1;
}

.score-display-box {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.score-number {
  font-size: 2.2rem;
  font-weight: 800;
  color: #f8fafc;
  letter-spacing: 1px;
}

.score-pct {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #34d399;
}

.completed-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: center;
}

.results-btn,
.review-btn {
  background: rgba(30, 24, 55, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #f1f5f9;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}

.results-btn:hover {
  background: #8b5cf6;
  border-color: #a78bfa;
}

.review-btn:hover {
  background: #3b82f6;
  border-color: #60a5fa;
}

.trial-loading-card,
.trial-error-card {
  padding: 3rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: #c4b5fd;
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 10px;
}

.rpg-retry-btn {
  background: #8b5cf6;
  color: #fff;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 6px;
  cursor: pointer;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
