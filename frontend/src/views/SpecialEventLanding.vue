<template>
  <div class="special-event-landing-page" :class="{ 'theme-morning': gameStore.isMorning }">
    <div class="academic-bg-vignette"></div>

    <div class="exam-hub-container animate-fade-in">
      <!-- Top Navigation -->
      <header class="hub-top-nav">
        <div class="nav-left-spacer" aria-hidden="true"></div>

        <div class="nav-location-badge">
          <span class="location-pulse-dot"></span>
          <span class="location-sector">MINDREALM // SECTOR IX</span>
          <span class="location-sep">•</span>
          <span class="location-crumb">Test Your Knowledge</span>
        </div>

        <div class="nav-actions-cluster">
          <button class="nav-action-btn history-btn" @click="goToHistory">
            <PixelEmoji emoji="book" size="xs" />
            <span>Mock Exam History</span>
          </button>
          <button class="nav-action-btn study-btn" @click="goToStudyHall">
            <PixelEmoji emoji="scroll" size="xs" />
            <span>Study Hall Reviewers</span>
          </button>
        </div>

        <div class="nav-right-spacer" aria-hidden="true"></div>
      </header>

      <!-- Hero Header Section (Minimal & Informative) -->
      <section class="exam-hero-card">
        <div class="hero-top-row">
          <span class="hero-eyebrow">MOCK PSYCHOMETRICIAN BOARD EXAM SIMULATOR</span>
          <span class="hero-cycle-pill">
            <PixelEmoji emoji="clock" size="xs" />
            <span>Cycle: {{ eventDateFormatted }}</span>
          </span>
        </div>

        <h1 class="exam-title-main">TEST YOUR KNOWLEDGE</h1>
        <p class="exam-hero-lead">
          Challenge your readiness with a structured, two-session mock board exam. All 450 questions are grounded strictly in your Study Hall reviewers.
        </p>

        <!-- Concise Inline Specs Strip -->
        <div class="hero-specs-strip">
          <div class="spec-chip">
            <span class="spec-label">Total Items</span>
            <span class="spec-value">450 Questions</span>
          </div>
          <span class="spec-sep">•</span>
          <div class="spec-chip">
            <span class="spec-label">Structure</span>
            <span class="spec-value">2 Sessions</span>
          </div>
          <span class="spec-sep">•</span>
          <div class="spec-chip">
            <span class="spec-label">Rigor</span>
            <span class="spec-value text-cyan">Bloom's Taxonomy (6 Levels)</span>
          </div>
          <span class="spec-sep">•</span>
          <div class="spec-chip">
            <span class="spec-label">Passing Standard</span>
            <span class="spec-value text-green">75.00%</span>
          </div>
        </div>
      </section>

      <!-- Loading State -->
      <div v-if="loading" class="state-card loading-state">
        <div class="state-spinner">
          <PixelEmoji emoji="crystal" size="lg" />
        </div>
        <p class="state-msg">Preparing today's mock examination sessions...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="state-card error-state">
        <PixelEmoji emoji="cross" size="lg" />
        <p class="state-msg">{{ error }}</p>
        <button class="academic-btn primary" @click="loadTodayEvent">Retry</button>
      </div>

      <!-- Main Two-Session Interface -->
      <div v-else class="exam-content-flow">
        <!-- Full Exam Completed Summary Banner -->
        <div v-if="fullExamSummary" class="full-exam-banner animate-slide-up">
          <div class="banner-left">
            <div class="banner-crest">
              <PixelEmoji emoji="trophy" size="lg" />
            </div>
            <div class="banner-body">
              <div class="banner-badge">EXAMINATION COMPLETE</div>
              <h3 class="banner-title">Combined 450-Item Evaluation</h3>
              <p class="banner-desc">Both examination sessions submitted. Your cumulative performance across all 4 domains is evaluated below.</p>
            </div>
          </div>

          <div class="banner-right">
            <div class="banner-metric">
              <span class="b-lbl">TOTAL SCORE</span>
              <span class="b-val">{{ fullExamSummary.total_score }} / {{ fullExamSummary.total_questions }}</span>
            </div>
            <div class="banner-metric">
              <span class="b-lbl">ACCURACY</span>
              <span class="b-val" :class="fullExamSummary.passed ? 'text-green' : 'text-amber'">
                {{ fullExamSummary.percentage }}%
              </span>
            </div>
            <div class="banner-metric">
              <span class="b-lbl">RESULT</span>
              <span class="b-val" :class="fullExamSummary.passed ? 'text-green' : 'text-amber'">
                {{ fullExamSummary.passed ? 'PASSED (≥ 75%)' : 'NEEDS REVIEW (< 75%)' }}
              </span>
            </div>
            <button class="banner-cta-btn" @click="goToHistory">
              View Analytics →
            </button>
          </div>
        </div>

        <!-- TWO-SESSION CARDS GRID -->
        <section class="sessions-grid">
          <!-- SESSION 1 CARD -->
          <div class="session-card" :class="['status-' + session1.status]">
            <div class="session-card-header">
              <div class="session-badge-row">
                <span class="session-pill">SESSION 1 • DAY 1</span>
                <span class="status-badge" :class="session1.status">
                  {{ formatStatus(session1.status) }}
                </span>
              </div>
              <h3 class="session-title">Theories of Personality & Abnormal Psychology</h3>
              <div class="session-meta-line">
                <span>200 Items</span>
                <span class="dot-sep">•</span>
                <span>3 Hours per Subject, a total of 6 Hours</span>
                <span class="dot-sep">•</span>
                <span class="text-indigo">Study Hall Grounded</span>
              </div>
            </div>

            <!-- Subject Chips -->
            <div class="session-chips-row">
              <div class="subject-chip">
                <span class="chip-name">Theories of Personality & Dev Psych</span>
                <span class="chip-badge">100 Items</span>
              </div>
              <div class="subject-chip">
                <span class="chip-name">Abnormal Psychology</span>
                <span class="chip-badge">100 Items</span>
              </div>
            </div>

            <!-- Session 1 Action Footprint -->
            <div class="session-card-footer">
              <!-- Available State -->
              <div v-if="session1.status === 'available'" class="action-wrap">
                <button
                  class="btn-exam primary"
                  :disabled="startingSession === 1"
                  @click="handleStartSession(1)"
                >
                  <span v-if="startingSession !== 1">Begin Session 1 (200 Items) →</span>
                  <span v-else>Initializing Session 1...</span>
                </button>
              </div>

              <!-- In Progress State -->
              <div v-else-if="session1.status === 'in_progress'" class="action-wrap in-progress-wrap">
                <div class="progress-info-row">
                  <span class="prog-stat">
                    Answered: <strong>{{ session1.attempt?.summary?.answered_count || 0 }} / 200</strong>
                  </span>
                  <span class="prog-stat" v-if="session1.attempt?.remaining_seconds">
                    ⏱️ Time Remaining: <strong>{{ formatSeconds(session1.attempt.remaining_seconds) }}</strong>
                  </span>
                </div>
                <button class="btn-exam resume" @click="handleResumeSession(session1.attempt.id)">
                  Resume Examination ▶
                </button>
              </div>

              <!-- Disqualified State -->
              <div v-else-if="session1.status === 'disqualified'" class="action-wrap disqualified-wrap">
                <div class="score-display">
                  <span class="score-lbl text-red font-bold">TERMINATED • DISQUALIFIED</span>
                  <span class="score-val text-red">Reason: Cheating</span>
                  <span class="score-sub text-muted">Exceeded maximum tab switch warnings</span>
                </div>
                <div class="action-btn-duo">
                  <button class="btn-exam outline danger" @click="goToResults(session1.attempt?.id)">
                    📊 View Record
                  </button>
                </div>
              </div>

              <!-- Completed State -->
              <div v-else-if="session1.status === 'completed' || session1.status === 'timed_out'" class="action-wrap completed-wrap">
                <div class="score-display">
                  <span class="score-lbl">Session 1 Score</span>
                  <span class="score-val">{{ session1.attempt?.score }} / 200</span>
                  <span class="score-pct" :class="session1.attempt?.percentage >= 75 ? 'text-green' : 'text-amber'">
                    ({{ session1.attempt?.percentage }}%)
                  </span>
                </div>
                <div class="action-btn-duo">
                  <button class="btn-exam outline" @click="goToResults(session1.attempt.id)">
                    📊 Results
                  </button>
                  <button class="btn-exam outline" @click="goToReview(session1.attempt.id)">
                    📝 Review Answers
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- SESSION 2 CARD -->
          <div class="session-card" :class="['status-' + session2.status]">
            <div class="session-card-header">
              <div class="session-badge-row">
                <span class="session-pill">SESSION 2 • DAY 2</span>
                <span class="status-badge" :class="session2.status">
                  {{ formatStatus(session2.status) }}
                </span>
              </div>
              <h3 class="session-title">Industrial-Organizational Psychology & Assessment</h3>
              <div class="session-meta-line">
                <span>250 Items</span>
                <span class="dot-sep">•</span>
                <span>3 Hours per Subject, a total of 6 Hours</span>
                <span class="dot-sep">•</span>
                <span class="text-indigo">Study Hall Grounded</span>
              </div>
            </div>

            <!-- Subject Chips -->
            <div class="session-chips-row">
              <div class="subject-chip">
                <span class="chip-name">Industrial and Organizational Psych</span>
                <span class="chip-badge">100 Items</span>
              </div>
              <div class="subject-chip">
                <span class="chip-name">Psychological Assessment</span>
                <span class="chip-badge">150 Items</span>
              </div>
            </div>

            <!-- Session 2 Action Footprint -->
            <div class="session-card-footer">
              <!-- Locked State -->
              <div v-if="session2.status === 'locked'" class="action-wrap locked-wrap">
                <div class="locked-note">
                  <span class="lock-icon">🔒</span>
                  <span>{{ session2.lock_reason || 'Complete Session 1 before beginning Session 2.' }}</span>
                </div>
                <button class="btn-exam disabled" disabled>
                  Session 2 Locked
                </button>
              </div>

              <!-- Available State -->
              <div v-else-if="session2.status === 'available'" class="action-wrap">
                <button
                  class="btn-exam primary"
                  :disabled="startingSession === 2"
                  @click="handleStartSession(2)"
                >
                  <span v-if="startingSession !== 2">Begin Session 2 (250 Items) →</span>
                  <span v-else>Initializing Session 2...</span>
                </button>
              </div>

              <!-- In Progress State -->
              <div v-else-if="session2.status === 'in_progress'" class="action-wrap in-progress-wrap">
                <div class="progress-info-row">
                  <span class="prog-stat">
                    Answered: <strong>{{ session2.attempt?.summary?.answered_count || 0 }} / 250</strong>
                  </span>
                  <span class="prog-stat" v-if="session2.attempt?.remaining_seconds">
                    ⏱️ Time Remaining: <strong>{{ formatSeconds(session2.attempt.remaining_seconds) }}</strong>
                  </span>
                </div>
                <button class="btn-exam resume" @click="handleResumeSession(session2.attempt.id)">
                  Resume Examination ▶
                </button>
              </div>

              <!-- Disqualified State -->
              <div v-else-if="session2.status === 'disqualified'" class="action-wrap disqualified-wrap">
                <div class="score-display">
                  <span class="score-lbl text-red font-bold">TERMINATED • DISQUALIFIED</span>
                  <span class="score-val text-red">Reason: Cheating</span>
                  <span class="score-sub text-muted">Exceeded maximum tab switch warnings</span>
                </div>
                <div class="action-btn-duo">
                  <button class="btn-exam outline danger" @click="goToResults(session2.attempt?.id)">
                    📊 View Record
                  </button>
                </div>
              </div>

              <!-- Completed State -->
              <div v-else-if="session2.status === 'completed' || session2.status === 'timed_out'" class="action-wrap completed-wrap">
                <div class="score-display">
                  <span class="score-lbl">Session 2 Score</span>
                  <span class="score-val">{{ session2.attempt?.score }} / 250</span>
                  <span class="score-pct" :class="session2.attempt?.percentage >= 75 ? 'text-green' : 'text-amber'">
                    ({{ session2.attempt?.percentage }}%)
                  </span>
                </div>
                <div class="action-btn-duo">
                  <button class="btn-exam outline" @click="goToResults(session2.attempt.id)">
                    📊 Results
                  </button>
                  <button class="btn-exam outline" @click="goToReview(session2.attempt.id)">
                    📝 Review Answers
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- STREAMLINED BLUEPRINT BREAKDOWN -->
        <section class="blueprint-minimal-card">
          <div class="blueprint-header-row">
            <div>
              <span class="blueprint-eyebrow">CURRICULUM SPECIFICATIONS</span>
              <h3 class="blueprint-title">450-Question Blueprint & Subject Allocation</h3>
            </div>
            <button class="btn-audit" @click="viewCoverageAudit">
              <PixelEmoji emoji="shield" size="xs" />
              <span>Verify Coverage Audit</span>
            </button>
          </div>

          <div class="blueprint-grid-4">
            <div class="blueprint-domain-card">
              <span class="domain-tag">SESSION 1</span>
              <h4 class="domain-title">Theories of Personality</h4>
              <p class="domain-sub">Developmental milestones & personality structures</p>
              <div class="domain-footer">
                <span class="domain-count">100 Items</span>
                <span class="domain-weight">22.2% of Exam</span>
              </div>
            </div>

            <div class="blueprint-domain-card">
              <span class="domain-tag">SESSION 1</span>
              <h4 class="domain-title">Abnormal Psychology</h4>
              <p class="domain-sub">DSM-5 criteria & clinical case formulations</p>
              <div class="domain-footer">
                <span class="domain-count">100 Items</span>
                <span class="domain-weight">22.2% of Exam</span>
              </div>
            </div>

            <div class="blueprint-domain-card">
              <span class="domain-tag">SESSION 2</span>
              <h4 class="domain-title">I/O Psychology</h4>
              <p class="domain-sub">Workplace dynamics & occupational impairment</p>
              <div class="domain-footer">
                <span class="domain-count">100 Items</span>
                <span class="domain-weight">22.2% of Exam</span>
              </div>
            </div>

            <div class="blueprint-domain-card highlight">
              <span class="domain-tag">SESSION 2</span>
              <h4 class="domain-title">Psychological Assessment</h4>
              <p class="domain-sub">Psychometrics, test construction & validity</p>
              <div class="domain-footer">
                <span class="domain-count">150 Items</span>
                <span class="domain-weight">33.3% of Exam</span>
              </div>
            </div>
          </div>

          <div class="blueprint-fineprint">
            <span>ℹ️ 100% grounded in Study Hall reviewers. All questions are server-authoritative with immutable answer archiving.</span>
          </div>
        </section>
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
import { useGameStore } from '../stores/game'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const transitionStore = useTransitionStore()
const toast = useToastStore()
const gameStore = useGameStore()

const loading = ref(true)
const startingSession = ref(null)
const error = ref('')
const eventData = ref(null)
const sessionsList = ref([])
const fullExamSummary = ref(null)

const session1 = computed(() => {
  return sessionsList.value.find(s => s.session_number === 1) || {
    session_number: 1,
    status: 'available',
    duration_minutes: 200,
    target_questions: 200,
    attempt: null
  }
})

const session2 = computed(() => {
  return sessionsList.value.find(s => s.session_number === 2) || {
    session_number: 2,
    status: 'locked',
    duration_minutes: 200,
    target_questions: 250,
    attempt: null
  }
})

const eventDateFormatted = computed(() => {
  if (!eventData.value?.event_date) return 'Today'
  const d = new Date(eventData.value.event_date + 'T00:00:00')
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
})

function formatStatus(status) {
  switch (status) {
    case 'available': return 'AVAILABLE'
    case 'in_progress': return 'IN PROGRESS'
    case 'completed': return 'COMPLETED'
    case 'timed_out': return 'TIMED OUT'
    case 'disqualified': return 'DISQUALIFIED (CHEATING)'
    case 'locked': return 'LOCKED'
    default: return status?.toUpperCase() || 'READY'
  }
}

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
      sessionsList.value = res.sessions || []
      fullExamSummary.value = res.full_exam_summary || null
    } else {
      error.value = res.message || 'Today\'s examination is not available.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load Test Your Knowledge examination.'
  } finally {
    loading.value = false
  }
}

async function handleStartSession(sessionNumber) {
  if (!eventData.value?.id) return

  const sessionName = sessionNumber === 1 ? 'Session 1 (Day 1 — 200 Questions)' : 'Session 2 (Day 2 — 250 Questions)'
  const duration = '3 hours per subject, a total of 6 hours'

  const confirm = await Swal.fire({
    title: `Begin ${sessionName}?`,
    html: `
      <div style="text-align: left; font-size: 0.95rem; line-height: 1.6; color: #cbd5e1;">
        <p>• <strong>Duration:</strong> 3 hours per subject, a total of 6 hours</p>
        <p>• <strong>Sequence:</strong> First subject must be completed first before proceeding to the second subject.</p>
        <p>• <strong>Answering:</strong> Click an option to answer and automatically advance to the next question. Re-click any shaded choice to erase.</p>
        <p>• <strong>Navigation:</strong> You can review and flag items before final submission.</p>
        <p>• <strong>Strict Examination Policy:</strong> Once you start, you cannot go back anymore unless you submit your examination.</p>
        <p>• <strong>Finality:</strong> Once submitted, the session is immutable and results are archived.</p>
        <div style="margin-top: 14px; padding: 12px; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; color: #fca5a5;">
          <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; color: #f87171; margin-bottom: 4px;">
            <span>⚠️</span> ANTI-CHEATING PROCTOR ACTIVE
          </div>
          <div style="font-size: 0.85rem; line-height: 1.5;">
            Changing tabs, minimizing the browser window, or exiting the examination is strictly prohibited. <strong>Once started, you cannot go back anymore unless you submit.</strong> You are granted <strong>2 warnings only</strong>. On the <strong>3rd violation</strong>, the test will automatically terminate and be marked as <strong>"Cheating"</strong>.
          </div>
        </div>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, Begin Examination',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#64748b',
    background: '#0f172a',
    color: '#f8fafc'
  })

  if (!confirm.isConfirmed) return

  startingSession.value = sessionNumber
  try {
    const res = await specialEventService.startAttempt(eventData.value.id, sessionNumber)
    if (res.success && res.attempt_id) {
      transitionStore.triggerTravel(`/special-event/exam?attemptId=${res.attempt_id}`, router)
    }
  } catch (err) {
    Swal.fire({
      title: 'Cannot Start Session',
      text: err.response?.data?.message || 'Failed to begin examination session.',
      icon: 'error',
      background: '#0f172a',
      color: '#f8fafc'
    })
  } finally {
    startingSession.value = null
  }
}

function handleResumeSession(attemptId) {
  if (!attemptId) return
  transitionStore.triggerTravel(`/special-event/exam?attemptId=${attemptId}`, router)
}

function goToResults(attemptId) {
  if (!attemptId) return
  router.push(`/special-event/results/${attemptId}`)
}

function goToReview(attemptId) {
  if (!attemptId) return
  router.push(`/special-event/results/${attemptId}?tab=review`)
}

function goToHistory() {
  router.push('/special-event/history')
}

function goToStudyHall() {
  router.push('/study')
}

function goBackToWorld() {
  transitionStore.triggerTravel('/world', router)
}

async function viewCoverageAudit() {
  try {
    const res = await specialEventService.getAudit()
    if (res.success && res.audit_report) {
      Swal.fire({
        title: 'Study Hall Coverage Audit',
        html: `
          <div style="text-align: left; font-size: 0.9rem; color: #cbd5e1; max-height: 400px; overflow-y: auto;">
            <p><strong>Total Study Hall Parts Indexed:</strong> ${res.audit_report.total_study_parts_indexed}</p>
            <p><strong>Total Content Sections:</strong> ${res.audit_report.total_content_sections}</p>
            <hr style="border-color: #334155; margin: 12px 0;" />
            ${res.audit_report.subject_audits.map(s => `
              <div style="margin-bottom: 10px;">
                <strong>${s.subject_name}:</strong> <span style="color: #4ade80;">${s.status}</span><br/>
                <small>${s.available_study_parts} Study Parts • Coverage: ${s.coverage_percentage}%</small>
              </div>
            `).join('')}
          </div>
        `,
        icon: 'info',
        confirmButtonText: 'Close',
        background: '#0f172a',
        color: '#f8fafc'
      })
    }
  } catch (err) {
    toast.error('Unable to load coverage audit report.')
  }
}

onMounted(() => {
  loadTodayEvent()
})
</script>

<style scoped>
.special-event-landing-page {
  min-height: 100vh;
  background-color: #070a13;
  color: #f1f5f9;
  padding: 1.5rem 1.5rem 4rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  position: relative;
}

.academic-bg-vignette {
  position: fixed;
  inset: 0;
  background: radial-gradient(circle at 50% 8%, rgba(37, 99, 235, 0.12) 0%, rgba(7, 10, 19, 0.96) 75%);
  pointer-events: none;
  z-index: 0;
}

.exam-hub-container {
  max-width: 1180px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* Nav Header */
.hub-top-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 42px;
  margin-bottom: 1.75rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  gap: 1rem;
}

.nav-left-spacer {
  width: 195px;
  flex-shrink: 0;
}

.nav-right-spacer {
  width: 105px;
  flex-shrink: 0;
}

.nav-location-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(30, 41, 59, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.78rem;
  white-space: nowrap;
}

.location-pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #3b82f6;
  box-shadow: 0 0 8px #3b82f6;
}

.location-sector {
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #94a3b8;
  font-size: 0.72rem;
}

.location-sep {
  color: #475569;
}

.location-crumb {
  color: #f8fafc;
  font-weight: 600;
}

.nav-actions-cluster {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-left: auto;
}

.nav-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.85rem;
  background: rgba(30, 41, 59, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  color: #e2e8f0;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-action-btn:hover {
  background: rgba(51, 65, 85, 0.9);
  color: #60a5fa;
  border-color: rgba(96, 165, 250, 0.4);
}

/* ── Hero Section (Minimal, Clean, Informative) ── */
.exam-hero-card {
  background: linear-gradient(180deg, rgba(23, 37, 84, 0.25) 0%, rgba(15, 23, 42, 0.4) 100%);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 12px;
  padding: 1.75rem 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.hero-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.hero-eyebrow {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #60a5fa;
  text-transform: uppercase;
}

.hero-cycle-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #f1f5f9;
}

.exam-title-main {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #ffffff;
  margin: 0 0 0.5rem 0;
}

.exam-hero-lead {
  font-size: 0.95rem;
  color: #cbd5e1;
  line-height: 1.5;
  max-width: 820px;
  margin: 0 0 1.25rem 0;
}

.hero-specs-strip {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.85rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.82rem;
}

.spec-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.spec-label {
  color: #94a3b8;
}

.spec-value {
  font-weight: 600;
  color: #f8fafc;
}

.spec-sep {
  color: #475569;
}

/* ── Completed Banner ── */
.full-exam-banner {
  background: linear-gradient(135deg, rgba(6, 78, 59, 0.35) 0%, rgba(15, 23, 42, 0.85) 100%);
  border: 1px solid rgba(16, 185, 129, 0.35);
  border-radius: 12px;
  padding: 1.5rem 1.75rem;
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex: 1;
  min-width: 300px;
}

.banner-crest {
  flex-shrink: 0;
}

.banner-badge {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #34d399;
  margin-bottom: 0.25rem;
}

.banner-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.25rem 0;
}

.banner-desc {
  font-size: 0.85rem;
  color: #cbd5e1;
  margin: 0;
}

.banner-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.banner-metric {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.b-lbl {
  font-size: 0.68rem;
  font-weight: 600;
  letter-spacing: 0.06em;
  color: #94a3b8;
}

.b-val {
  font-size: 1.2rem;
  font-weight: 800;
  color: #f8fafc;
}

.banner-cta-btn {
  background: #10b981;
  border: none;
  color: #064e3b;
  font-weight: 700;
  font-size: 0.85rem;
  padding: 0.65rem 1.25rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.banner-cta-btn:hover {
  background: #34d399;
  transform: translateY(-1px);
}

/* ── Sessions Grid ── */
.sessions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.session-card {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 1.25rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.session-card:hover {
  border-color: rgba(96, 165, 250, 0.3);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);
}

.session-badge-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.session-pill {
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #60a5fa;
  background: rgba(30, 58, 138, 0.4);
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.status-badge {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  text-transform: uppercase;
}

.status-badge.available { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.status-badge.in_progress { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.status-badge.completed { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.status-badge.disqualified { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }
.status-badge.locked { background: rgba(100, 116, 139, 0.15); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.3); }

.session-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.35rem 0;
  line-height: 1.3;
}

.session-meta-line {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8rem;
  color: #94a3b8;
}

.dot-sep {
  color: #475569;
}

.session-chips-row {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.subject-chip {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(30, 41, 59, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 0.5rem 0.85rem;
  border-radius: 6px;
  font-size: 0.82rem;
}

.chip-name {
  color: #e2e8f0;
  font-weight: 500;
}

.chip-badge {
  font-size: 0.72rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(15, 23, 42, 0.6);
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.session-card-footer {
  margin-top: 0.5rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.action-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn-exam {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: none;
}

.btn-exam.primary {
  background: #3b82f6;
  color: #ffffff;
}

.btn-exam.primary:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-exam.resume {
  background: #f59e0b;
  color: #78350f;
  font-weight: 700;
}

.btn-exam.resume:hover {
  background: #fbbf24;
}

.btn-exam.disabled {
  background: rgba(51, 65, 85, 0.4);
  color: #64748b;
  cursor: not-allowed;
}

.btn-exam.outline {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
}

.btn-exam.outline:hover {
  background: rgba(51, 65, 85, 0.9);
  color: #60a5fa;
  border-color: rgba(96, 165, 250, 0.4);
}

.btn-exam.outline.danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
  color: #fca5a5;
}

.btn-exam.outline.danger:hover {
  background: rgba(239, 68, 68, 0.3);
  color: #ffffff;
  border-color: rgba(239, 68, 68, 0.6);
}

.disqualified-wrap .score-display {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.25);
  flex-direction: column;
  gap: 0.2rem;
  align-items: flex-start;
}

.text-red {
  color: #f87171 !important;
}

.text-muted {
  color: #94a3b8;
  font-size: 0.75rem;
}

.action-btn-duo {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.progress-info-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
  color: #cbd5e1;
}

.score-display {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  background: rgba(30, 41, 59, 0.5);
  padding: 0.5rem 0.85rem;
  border-radius: 6px;
}

.score-lbl {
  font-size: 0.75rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
}

.score-val {
  font-size: 1.25rem;
  font-weight: 800;
  color: #ffffff;
}

.score-pct {
  font-size: 0.95rem;
  font-weight: 700;
}

.locked-note {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #94a3b8;
  background: rgba(30, 41, 59, 0.35);
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
}

/* ── Streamlined Blueprint ── */
.blueprint-minimal-card {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.75rem;
}

.blueprint-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.blueprint-eyebrow {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #60a5fa;
  text-transform: uppercase;
  display: block;
  margin-bottom: 0.2rem;
}

.blueprint-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.btn-audit {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #cbd5e1;
  font-size: 0.78rem;
  padding: 0.4rem 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-audit:hover {
  background: rgba(51, 65, 85, 0.9);
  color: #60a5fa;
}

.blueprint-grid-4 {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.blueprint-domain-card {
  background: rgba(30, 41, 59, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.blueprint-domain-card.highlight {
  border-color: rgba(96, 165, 250, 0.25);
  background: rgba(30, 58, 138, 0.15);
}

.domain-tag {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #60a5fa;
}

.domain-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0;
}

.domain-sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.domain-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.78rem;
  margin-top: auto;
}

.domain-count {
  font-weight: 700;
  color: #ffffff;
}

.domain-weight {
  color: #60a5fa;
  font-weight: 600;
}

.blueprint-fineprint {
  font-size: 0.75rem;
  color: #64748b;
  padding-top: 0.5rem;
}

/* States */
.state-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 3rem 2rem;
  text-align: center;
  margin: 2rem 0;
}

.state-spinner {
  margin-bottom: 1rem;
  animation: pulse 1.5s infinite;
}

.state-msg {
  color: #94a3b8;
  font-size: 0.95rem;
  margin: 0;
}

/* Utilities */
.text-cyan { color: #38bdf8; }
.text-green { color: #4ade80; }
.text-amber { color: #fbbf24; }
.text-indigo { color: #818cf8; }

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(0.95); }
}

@media (max-width: 1024px) {
  .hub-top-nav {
    flex-wrap: wrap;
    padding-top: 44px;
  }
  .nav-left-spacer, .nav-right-spacer {
    display: none;
  }
  .nav-location-badge {
    order: 1;
    width: 100%;
    justify-content: center;
  }
  .nav-actions-cluster {
    order: 2;
    width: 100%;
    justify-content: center;
    margin-left: 0;
  }
  .sessions-grid {
    grid-template-columns: 1fr;
  }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — SPECIAL EVENT LANDING
   ══════════════════════════════════════════════════════════════ */
.special-event-landing-page.theme-morning,
:global(.theme-morning) .special-event-landing-page,
:global([data-theme="morning"]) .special-event-landing-page {
  background-color: #F8F4EC !important;
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .academic-bg-vignette,
:global(.theme-morning) .special-event-landing-page .academic-bg-vignette,
:global([data-theme="morning"]) .special-event-landing-page .academic-bg-vignette {
  background: radial-gradient(circle at 50% 8%, rgba(217, 163, 76, 0.18) 0%, rgba(248, 244, 236, 0.96) 75%) !important;
}

/* Nav Header */
.special-event-landing-page.theme-morning .hub-top-nav,
:global(.theme-morning) .special-event-landing-page .hub-top-nav,
:global([data-theme="morning"]) .special-event-landing-page .hub-top-nav {
  border-bottom-color: rgba(184, 138, 66, 0.25) !important;
}

.special-event-landing-page.theme-morning .nav-location-badge,
:global(.theme-morning) .special-event-landing-page .nav-location-badge,
:global([data-theme="morning"]) .special-event-landing-page .nav-location-badge {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  box-shadow: 0 2px 10px rgba(143, 100, 45, 0.12) !important;
}

.special-event-landing-page.theme-morning .location-pulse-dot,
:global(.theme-morning) .special-event-landing-page .location-pulse-dot,
:global([data-theme="morning"]) .special-event-landing-page .location-pulse-dot {
  background: #d97706 !important;
  box-shadow: 0 0 8px #d97706 !important;
}

.special-event-landing-page.theme-morning .location-sector,
:global(.theme-morning) .special-event-landing-page .location-sector,
:global([data-theme="morning"]) .special-event-landing-page .location-sector {
  color: #8C6A3E !important;
}

.special-event-landing-page.theme-morning .location-sep,
:global(.theme-morning) .special-event-landing-page .location-sep,
:global([data-theme="morning"]) .special-event-landing-page .location-sep {
  color: #B59E7A !important;
}

.special-event-landing-page.theme-morning .location-crumb,
:global(.theme-morning) .special-event-landing-page .location-crumb,
:global([data-theme="morning"]) .special-event-landing-page .location-crumb {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .nav-action-btn,
:global(.theme-morning) .special-event-landing-page .nav-action-btn,
:global([data-theme="morning"]) .special-event-landing-page .nav-action-btn {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  color: #4A3525 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

.special-event-landing-page.theme-morning .nav-action-btn:hover,
:global(.theme-morning) .special-event-landing-page .nav-action-btn:hover,
:global([data-theme="morning"]) .special-event-landing-page .nav-action-btn:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
  box-shadow: 0 4px 14px rgba(143, 100, 45, 0.18) !important;
}

/* Hero Section */
.special-event-landing-page.theme-morning .exam-hero-card,
:global(.theme-morning) .special-event-landing-page .exam-hero-card,
:global([data-theme="morning"]) .special-event-landing-page .exam-hero-card {
  background: linear-gradient(180deg, #FFFDF8 0%, #FAF3E5 100%) !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 8px 24px rgba(143, 100, 45, 0.12) !important;
}

.special-event-landing-page.theme-morning .hero-eyebrow,
:global(.theme-morning) .special-event-landing-page .hero-eyebrow,
:global([data-theme="morning"]) .special-event-landing-page .hero-eyebrow {
  color: #B45309 !important;
}

.special-event-landing-page.theme-morning .hero-cycle-pill,
:global(.theme-morning) .special-event-landing-page .hero-cycle-pill,
:global([data-theme="morning"]) .special-event-landing-page .hero-cycle-pill {
  background: #F5E8CC !important;
  border: 1px solid #C8AC7B !important;
  color: #5D432A !important;
}

.special-event-landing-page.theme-morning .exam-title-main,
:global(.theme-morning) .special-event-landing-page .exam-title-main,
:global([data-theme="morning"]) .special-event-landing-page .exam-title-main {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .exam-hero-lead,
:global(.theme-morning) .special-event-landing-page .exam-hero-lead,
:global([data-theme="morning"]) .special-event-landing-page .exam-hero-lead {
  color: #5D4736 !important;
}

.special-event-landing-page.theme-morning .hero-specs-strip,
:global(.theme-morning) .special-event-landing-page .hero-specs-strip,
:global([data-theme="morning"]) .special-event-landing-page .hero-specs-strip {
  border-top-color: #E2CEAA !important;
}

.special-event-landing-page.theme-morning .spec-label,
:global(.theme-morning) .special-event-landing-page .spec-label,
:global([data-theme="morning"]) .special-event-landing-page .spec-label {
  color: #7A5F43 !important;
}

.special-event-landing-page.theme-morning .spec-value,
:global(.theme-morning) .special-event-landing-page .spec-value,
:global([data-theme="morning"]) .special-event-landing-page .spec-value {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .spec-value.text-cyan,
:global(.theme-morning) .special-event-landing-page .spec-value.text-cyan,
:global([data-theme="morning"]) .special-event-landing-page .spec-value.text-cyan {
  color: #0284C7 !important;
}

.special-event-landing-page.theme-morning .spec-value.text-green,
:global(.theme-morning) .special-event-landing-page .spec-value.text-green,
:global([data-theme="morning"]) .special-event-landing-page .spec-value.text-green {
  color: #16A34A !important;
}

.special-event-landing-page.theme-morning .spec-sep,
:global(.theme-morning) .special-event-landing-page .spec-sep,
:global([data-theme="morning"]) .special-event-landing-page .spec-sep {
  color: #B59E7A !important;
}

/* Full Exam Banner */
.special-event-landing-page.theme-morning .full-exam-banner,
:global(.theme-morning) .special-event-landing-page .full-exam-banner,
:global([data-theme="morning"]) .special-event-landing-page .full-exam-banner {
  background: linear-gradient(135deg, #FFFDF5 0%, #F5ECDA 100%) !important;
  border: 1.5px solid #C8AC7B !important;
  box-shadow: 0 8px 24px rgba(143, 100, 45, 0.15) !important;
}

.special-event-landing-page.theme-morning .banner-badge,
:global(.theme-morning) .special-event-landing-page .banner-badge,
:global([data-theme="morning"]) .special-event-landing-page .banner-badge {
  background: #DCFCE7 !important;
  color: #166534 !important;
  border: 1px solid #86EFAC !important;
}

.special-event-landing-page.theme-morning .banner-title,
:global(.theme-morning) .special-event-landing-page .banner-title,
:global([data-theme="morning"]) .special-event-landing-page .banner-title {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .banner-desc,
:global(.theme-morning) .special-event-landing-page .banner-desc,
:global([data-theme="morning"]) .special-event-landing-page .banner-desc {
  color: #5D4736 !important;
}

.special-event-landing-page.theme-morning .banner-metric,
:global(.theme-morning) .special-event-landing-page .banner-metric,
:global([data-theme="morning"]) .special-event-landing-page .banner-metric {
  background: #FFF8EB !important;
  border: 1px solid #D9C39E !important;
  border-radius: 6px;
  padding: 0.35rem 0.65rem;
}

.special-event-landing-page.theme-morning .b-lbl,
:global(.theme-morning) .special-event-landing-page .b-lbl,
:global([data-theme="morning"]) .special-event-landing-page .b-lbl {
  color: #7A5F43 !important;
}

.special-event-landing-page.theme-morning .b-val,
:global(.theme-morning) .special-event-landing-page .b-val,
:global([data-theme="morning"]) .special-event-landing-page .b-val {
  color: #2C1E14 !important;
}

/* Session Cards */
.special-event-landing-page.theme-morning .session-card,
:global(.theme-morning) .special-event-landing-page .session-card,
:global([data-theme="morning"]) .special-event-landing-page .session-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 6px 20px rgba(143, 100, 45, 0.1) !important;
}

.special-event-landing-page.theme-morning .session-card:hover,
:global(.theme-morning) .special-event-landing-page .session-card:hover,
:global([data-theme="morning"]) .special-event-landing-page .session-card:hover {
  border-color: #B88A42 !important;
  box-shadow: 0 10px 28px rgba(143, 100, 45, 0.18) !important;
}

.special-event-landing-page.theme-morning .session-pill,
:global(.theme-morning) .special-event-landing-page .session-pill,
:global([data-theme="morning"]) .special-event-landing-page .session-pill {
  background: #F3E7CF !important;
  color: #7A5F43 !important;
  border: 1px solid #D5BF97 !important;
}

.special-event-landing-page.theme-morning .session-title,
:global(.theme-morning) .special-event-landing-page .session-title,
:global([data-theme="morning"]) .special-event-landing-page .session-title {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .session-meta-line,
:global(.theme-morning) .special-event-landing-page .session-meta-line,
:global([data-theme="morning"]) .special-event-landing-page .session-meta-line {
  color: #6C5440 !important;
}

.special-event-landing-page.theme-morning .session-meta-line .text-indigo,
:global(.theme-morning) .special-event-landing-page .session-meta-line .text-indigo,
:global([data-theme="morning"]) .special-event-landing-page .session-meta-line .text-indigo {
  color: #2563EB !important;
}

.special-event-landing-page.theme-morning .dot-sep,
:global(.theme-morning) .special-event-landing-page .dot-sep,
:global([data-theme="morning"]) .special-event-landing-page .dot-sep {
  color: #B59E7A !important;
}

/* Status Badges */
.special-event-landing-page.theme-morning .status-badge.available,
:global(.theme-morning) .special-event-landing-page .status-badge.available,
:global([data-theme="morning"]) .special-event-landing-page .status-badge.available {
  background: #EFF6FF !important;
  color: #1D4ED8 !important;
  border: 1px solid #BFDBFE !important;
}

.special-event-landing-page.theme-morning .status-badge.in_progress,
:global(.theme-morning) .special-event-landing-page .status-badge.in_progress,
:global([data-theme="morning"]) .special-event-landing-page .status-badge.in_progress {
  background: #FEF3C7 !important;
  color: #B45309 !important;
  border: 1px solid #FDE68A !important;
}

.special-event-landing-page.theme-morning .status-badge.completed,
:global(.theme-morning) .special-event-landing-page .status-badge.completed,
:global([data-theme="morning"]) .special-event-landing-page .status-badge.completed {
  background: #DCFCE7 !important;
  color: #15803D !important;
  border: 1px solid #86EFAC !important;
}

.special-event-landing-page.theme-morning .status-badge.disqualified,
:global(.theme-morning) .special-event-landing-page .status-badge.disqualified,
:global([data-theme="morning"]) .special-event-landing-page .status-badge.disqualified {
  background: #FEE2E2 !important;
  color: #B91C1C !important;
  border: 1px solid #FCA5A5 !important;
}

.special-event-landing-page.theme-morning .status-badge.locked,
:global(.theme-morning) .special-event-landing-page .status-badge.locked,
:global([data-theme="morning"]) .special-event-landing-page .status-badge.locked {
  background: #F3F4F6 !important;
  color: #4B5563 !important;
  border: 1px solid #E5E7EB !important;
}

/* Subject Chips */
.special-event-landing-page.theme-morning .subject-chip,
:global(.theme-morning) .special-event-landing-page .subject-chip,
:global([data-theme="morning"]) .special-event-landing-page .subject-chip {
  background: #F7EFE1 !important;
  border-color: #DCC7A4 !important;
}

.special-event-landing-page.theme-morning .chip-name,
:global(.theme-morning) .special-event-landing-page .chip-name,
:global([data-theme="morning"]) .special-event-landing-page .chip-name {
  color: #3D2B1F !important;
}

.special-event-landing-page.theme-morning .chip-badge,
:global(.theme-morning) .special-event-landing-page .chip-badge,
:global([data-theme="morning"]) .special-event-landing-page .chip-badge {
  background: #EEDCBA !important;
  color: #5C4128 !important;
}

/* Session Footer & Buttons */
.special-event-landing-page.theme-morning .session-card-footer,
:global(.theme-morning) .special-event-landing-page .session-card-footer,
:global([data-theme="morning"]) .special-event-landing-page .session-card-footer {
  border-top-color: #E5D5B8 !important;
}

.special-event-landing-page.theme-morning .btn-exam.primary,
:global(.theme-morning) .special-event-landing-page .btn-exam.primary,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.primary {
  background: linear-gradient(135deg, #B88A42 0%, #996E2E 100%) !important;
  color: #FFFFFF !important;
  border: 1px solid #7D571C !important;
  box-shadow: 0 4px 14px rgba(143, 100, 45, 0.25) !important;
}

.special-event-landing-page.theme-morning .btn-exam.primary:hover:not(:disabled),
:global(.theme-morning) .special-event-landing-page .btn-exam.primary:hover:not(:disabled),
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #C7974C 0%, #A87A34 100%) !important;
  box-shadow: 0 6px 18px rgba(143, 100, 45, 0.35) !important;
}

.special-event-landing-page.theme-morning .btn-exam.outline,
:global(.theme-morning) .special-event-landing-page .btn-exam.outline,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.outline {
  background: #FFFDF8 !important;
  border: 1.5px solid #C8AC7B !important;
  color: #3D2B1F !important;
}

.special-event-landing-page.theme-morning .btn-exam.outline:hover,
:global(.theme-morning) .special-event-landing-page .btn-exam.outline:hover,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.outline:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .btn-exam.outline.danger,
:global(.theme-morning) .special-event-landing-page .btn-exam.outline.danger,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.outline.danger {
  background: #FEF2F2 !important;
  border: 1.5px solid #FCA5A5 !important;
  color: #B91C1C !important;
}

.special-event-landing-page.theme-morning .btn-exam.outline.danger:hover,
:global(.theme-morning) .special-event-landing-page .btn-exam.outline.danger:hover,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.outline.danger:hover {
  background: #FEE2E2 !important;
  border-color: #EF4444 !important;
  color: #991B1B !important;
}

.special-event-landing-page.theme-morning .btn-exam.resume,
:global(.theme-morning) .special-event-landing-page .btn-exam.resume,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.resume {
  background: linear-gradient(135deg, #D97706 0%, #B45309 100%) !important;
  color: #FFFFFF !important;
}

.special-event-landing-page.theme-morning .btn-exam.disabled,
:global(.theme-morning) .special-event-landing-page .btn-exam.disabled,
:global([data-theme="morning"]) .special-event-landing-page .btn-exam.disabled {
  background: #E5E7EB !important;
  border: 1px solid #D1D5DB !important;
  color: #6B7280 !important;
}

.special-event-landing-page.theme-morning .score-display,
:global(.theme-morning) .special-event-landing-page .score-display,
:global([data-theme="morning"]) .special-event-landing-page .score-display {
  background: #F3E7CF !important;
  border: 1px solid #D5BF97 !important;
}

.special-event-landing-page.theme-morning .score-lbl,
:global(.theme-morning) .special-event-landing-page .score-lbl,
:global([data-theme="morning"]) .special-event-landing-page .score-lbl {
  color: #7A5F43 !important;
}

.special-event-landing-page.theme-morning .score-val,
:global(.theme-morning) .special-event-landing-page .score-val,
:global([data-theme="morning"]) .special-event-landing-page .score-val {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .disqualified-wrap,
:global(.theme-morning) .special-event-landing-page .disqualified-wrap,
:global([data-theme="morning"]) .special-event-landing-page .disqualified-wrap {
  background: #FEF2F2 !important;
  border: 1px solid #FECACA !important;
  border-radius: 8px;
  padding: 0.75rem;
}

.special-event-landing-page.theme-morning .disqualified-wrap .score-display,
:global(.theme-morning) .special-event-landing-page .disqualified-wrap .score-display,
:global([data-theme="morning"]) .special-event-landing-page .disqualified-wrap .score-display {
  background: #FFF5F5 !important;
  border-color: #FED7D7 !important;
}

.special-event-landing-page.theme-morning .disqualified-wrap .text-red,
:global(.theme-morning) .special-event-landing-page .disqualified-wrap .text-red,
:global([data-theme="morning"]) .special-event-landing-page .disqualified-wrap .text-red {
  color: #B91C1C !important;
}

.special-event-landing-page.theme-morning .disqualified-wrap .text-muted,
:global(.theme-morning) .special-event-landing-page .disqualified-wrap .text-muted,
:global([data-theme="morning"]) .special-event-landing-page .disqualified-wrap .text-muted {
  color: #7F1D1D !important;
}

.special-event-landing-page.theme-morning .locked-note,
:global(.theme-morning) .special-event-landing-page .locked-note,
:global([data-theme="morning"]) .special-event-landing-page .locked-note {
  background: #F3F4F6 !important;
  border: 1px solid #E5E7EB !important;
  color: #4B5563 !important;
}

.special-event-landing-page.theme-morning .progress-info-row,
:global(.theme-morning) .special-event-landing-page .progress-info-row,
:global([data-theme="morning"]) .special-event-landing-page .progress-info-row {
  color: #5D4736 !important;
}

/* Blueprint Section */
.special-event-landing-page.theme-morning .blueprint-minimal-card,
:global(.theme-morning) .special-event-landing-page .blueprint-minimal-card,
:global([data-theme="morning"]) .special-event-landing-page .blueprint-minimal-card {
  background: linear-gradient(180deg, #FFFDF8 0%, #FAF3E5 100%) !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 8px 24px rgba(143, 100, 45, 0.12) !important;
}

.special-event-landing-page.theme-morning .blueprint-eyebrow,
:global(.theme-morning) .special-event-landing-page .blueprint-eyebrow,
:global([data-theme="morning"]) .special-event-landing-page .blueprint-eyebrow {
  color: #B45309 !important;
}

.special-event-landing-page.theme-morning .blueprint-title,
:global(.theme-morning) .special-event-landing-page .blueprint-title,
:global([data-theme="morning"]) .special-event-landing-page .blueprint-title {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .btn-audit,
:global(.theme-morning) .special-event-landing-page .btn-audit,
:global([data-theme="morning"]) .special-event-landing-page .btn-audit {
  background: #FFF8EB !important;
  border: 1.5px solid #C8AC7B !important;
  color: #4A3525 !important;
}

.special-event-landing-page.theme-morning .btn-audit:hover,
:global(.theme-morning) .special-event-landing-page .btn-audit:hover,
:global([data-theme="morning"]) .special-event-landing-page .btn-audit:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .blueprint-domain-card,
:global(.theme-morning) .special-event-landing-page .blueprint-domain-card,
:global([data-theme="morning"]) .special-event-landing-page .blueprint-domain-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #DCC7A4 !important;
}

.special-event-landing-page.theme-morning .domain-tag,
:global(.theme-morning) .special-event-landing-page .domain-tag,
:global([data-theme="morning"]) .special-event-landing-page .domain-tag {
  color: #7A5F43 !important;
  background: #F3E7CF !important;
  border: 1px solid #D5BF97 !important;
  border-radius: 4px;
  padding: 0.1rem 0.35rem;
}

.special-event-landing-page.theme-morning .domain-title,
:global(.theme-morning) .special-event-landing-page .domain-title,
:global([data-theme="morning"]) .special-event-landing-page .domain-title {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .domain-sub,
:global(.theme-morning) .special-event-landing-page .domain-sub,
:global([data-theme="morning"]) .special-event-landing-page .domain-sub {
  color: #6C5440 !important;
}

.special-event-landing-page.theme-morning .domain-footer,
:global(.theme-morning) .special-event-landing-page .domain-footer,
:global([data-theme="morning"]) .special-event-landing-page .domain-footer {
  border-top-color: #E5D5B8 !important;
}

.special-event-landing-page.theme-morning .domain-count,
:global(.theme-morning) .special-event-landing-page .domain-count,
:global([data-theme="morning"]) .special-event-landing-page .domain-count {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .domain-weight,
:global(.theme-morning) .special-event-landing-page .domain-weight,
:global([data-theme="morning"]) .special-event-landing-page .domain-weight {
  color: #B45309 !important;
}

.special-event-landing-page.theme-morning .blueprint-fineprint,
:global(.theme-morning) .special-event-landing-page .blueprint-fineprint,
:global([data-theme="morning"]) .special-event-landing-page .blueprint-fineprint {
  color: #7A5F43 !important;
}

/* Security Footer */
.special-event-landing-page.theme-morning .exam-security-footer,
:global(.theme-morning) .special-event-landing-page .exam-security-footer,
:global([data-theme="morning"]) .special-event-landing-page .exam-security-footer {
  background: #FAF3E5 !important;
  border: 1.5px solid #D9C39E !important;
}

.special-event-landing-page.theme-morning .sec-pill,
:global(.theme-morning) .special-event-landing-page .sec-pill,
:global([data-theme="morning"]) .special-event-landing-page .sec-pill {
  background: #DCFCE7 !important;
  color: #166534 !important;
  border: 1px solid #86EFAC !important;
}

.special-event-landing-page.theme-morning .sec-title,
:global(.theme-morning) .special-event-landing-page .sec-title,
:global([data-theme="morning"]) .special-event-landing-page .sec-title {
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .sec-desc,
:global(.theme-morning) .special-event-landing-page .sec-desc,
:global([data-theme="morning"]) .special-event-landing-page .sec-desc {
  color: #5D4736 !important;
}

.special-event-landing-page.theme-morning .state-card,
:global(.theme-morning) .special-event-landing-page .state-card,
:global([data-theme="morning"]) .special-event-landing-page .state-card {
  background: #FAF3E5 !important;
  border-color: #D9C39E !important;
  color: #2C1E14 !important;
}

.special-event-landing-page.theme-morning .state-msg,
:global(.theme-morning) .special-event-landing-page .state-msg,
:global([data-theme="morning"]) .special-event-landing-page .state-msg {
  color: #5D4736 !important;
}
</style>
