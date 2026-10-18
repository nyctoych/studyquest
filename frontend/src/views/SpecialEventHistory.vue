<template>
  <div class="special-event-history-page" :class="{ 'theme-morning': gameStore.isMorning }">
    <div class="history-bg-vignette"></div>

    <div class="history-container animate-fade-in">
      <!-- Top Navigation -->
      <header class="history-top-nav">
        <div class="nav-left-spacer" aria-hidden="true"></div>

        <div class="nav-location-badge">
          <span class="location-pulse-dot"></span>
          <router-link to="/special-event" class="crumb-link">Test Your Knowledge</router-link>
          <span class="location-sep">/</span>
          <span class="location-crumb">Mock Exam History</span>
        </div>

        <div class="nav-actions-cluster">
          <router-link to="/special-event" class="nav-action-btn gate-btn">
            <PixelEmoji emoji="shield" size="xs" />
            <span>Today's Exam Gate</span>
          </router-link>
          <router-link to="/study" class="nav-action-btn study-btn">
            <PixelEmoji emoji="scroll" size="xs" />
            <span>Study Hall</span>
          </router-link>
        </div>

        <div class="nav-right-spacer" aria-hidden="true"></div>
      </header>

      <!-- Minimal Hero Header -->
      <section class="history-hero-minimal">
        <div class="hero-titles">
          <span class="hero-eyebrow">SCHOLAR EXAMINATION PORTFOLIO</span>
          <h1 class="hero-title">Mock Exam History & Analytics</h1>
          <p class="hero-desc">
            Track your licensure examination attempts, analyze cognitive strengths across Bloom's Taxonomy, and target lessons where review is needed.
          </p>
        </div>
      </section>

      <!-- Loading State -->
      <div v-if="loading" class="state-card loading-state">
        <div class="state-spinner">
          <PixelEmoji emoji="crystal" size="lg" />
        </div>
        <p>Loading examination records and performance analytics...</p>
      </div>

      <!-- Main History Dashboard -->
      <div v-else class="history-content-flow">
        <!-- 4-Card Primary KPI Metric Bar -->
        <section class="metrics-summary-grid">
          <div class="metric-box">
            <span class="m-label">TOTAL SESSIONS</span>
            <span class="m-value">{{ analyticsSummary?.total_completed_attempts || 0 }}</span>
            <span class="m-sub">S1: {{ analyticsSummary?.session_1_completed || 0 }} • S2: {{ analyticsSummary?.session_2_completed || 0 }}</span>
          </div>

          <div class="metric-box">
            <span class="m-label">AVERAGE ACCURACY</span>
            <span class="m-value" :class="avgAccuracy >= 75 ? 'text-green' : 'text-amber'">
              {{ avgAccuracy }}%
            </span>
            <span class="m-sub">Across all recorded items</span>
          </div>

          <div class="metric-box">
            <span class="m-label">BEST SESSION SCORE</span>
            <span class="m-value text-gold">
              {{ analyticsSummary?.best_score || 0 }}
            </span>
            <span class="m-sub">Highest individual session score</span>
          </div>

          <div class="metric-box">
            <span class="m-label">ITEMS ANSWERED</span>
            <span class="m-value text-cyan">
              {{ analyticsSummary?.total_questions_answered || 0 }}
            </span>
            <span class="m-sub">Validated answers recorded</span>
          </div>
        </section>

        <!-- Secondary Insight Strip -->
        <section class="insights-strip">
          <div class="insight-card">
            <div class="insight-header">
              <span class="insight-icon">🎯</span>
              <span class="insight-title">Domain Mastery Focus</span>
            </div>
            <div class="insight-body">
              <div class="insight-item">
                <span class="i-lbl">Strongest:</span>
                <span class="i-val text-green">{{ analyticsSummary?.strongest_subject || 'N/A' }}</span>
              </div>
              <div class="insight-item">
                <span class="i-lbl">Study Priority:</span>
                <span class="i-val text-amber">{{ analyticsSummary?.weakest_subject || 'N/A' }}</span>
              </div>
            </div>
          </div>

          <div class="insight-card">
            <div class="insight-header">
              <span class="insight-icon">🧠</span>
              <span class="insight-title">Cognitive Rigor (Bloom's Taxonomy)</span>
            </div>
            <div class="insight-body">
              <div class="insight-item">
                <span class="i-lbl">Highest Mastery:</span>
                <span class="i-val text-indigo">{{ analyticsSummary?.strongest_bloom || 'N/A' }}</span>
              </div>
              <div class="insight-item">
                <span class="i-lbl">Review Target:</span>
                <span class="i-val text-amber">{{ analyticsSummary?.weakest_bloom || 'N/A' }}</span>
              </div>
            </div>
          </div>
        </section>

        <!-- Two-Column Breakdown: Subject Mastery & Recommendations -->
        <div class="breakdown-grid-2">
          <!-- Subject Performance -->
          <section class="panel-card">
            <div class="panel-head">
              <h3 class="panel-title">Subject Mastery Breakdown</h3>
              <span class="panel-tag">Board Domains</span>
            </div>

            <div v-if="subjectsList.length === 0" class="empty-hint">
              No examination answers recorded yet.
            </div>
            <div v-else class="subject-bars-list">
              <div v-for="subj in subjectsList" :key="subj.subject_name" class="subj-progress-item">
                <div class="subj-prog-header">
                  <span class="subj-name">{{ subj.subject_name }}</span>
                  <span class="subj-acc" :class="subj.accuracy >= 75 ? 'text-green' : 'text-amber'">
                    {{ subj.accuracy }}% ({{ subj.correct_count }}/{{ subj.total_questions }})
                  </span>
                </div>
                <div class="progress-bar-track">
                  <div
                    class="progress-bar-fill"
                    :style="{ width: subj.accuracy + '%' }"
                    :class="subj.accuracy >= 75 ? 'bg-green' : 'bg-amber'"
                  ></div>
                </div>
              </div>
            </div>
          </section>

          <!-- Study Hall Recommendations -->
          <section class="panel-card">
            <div class="panel-head">
              <h3 class="panel-title">Recommended Study Hall Lessons</h3>
              <span class="panel-tag text-gold">Targeted Reviews</span>
            </div>

            <div v-if="recommendationsList.length === 0" class="empty-hint">
              Complete an examination session to receive personalized lesson recommendations based on missed concepts.
            </div>
            <div v-else class="recommendations-list">
              <div v-for="rec in recommendationsList" :key="rec.domain_id + '_' + rec.study_part_id" class="rec-item">
                <div class="rec-info">
                  <span class="rec-domain">{{ rec.domain_title }}</span>
                  <span class="rec-title">{{ rec.part_title }}</span>
                  <span class="rec-meta">Missed {{ rec.missed_count }} question(s) in this lesson</span>
                </div>
                <router-link :to="rec.study_hall_link" class="rec-action-btn">
                  Study Lesson →
                </router-link>
              </div>
            </div>
          </section>
        </div>

        <!-- Examination Archive Table -->
        <section class="panel-card archive-section">
          <div class="archive-header-row">
            <div>
              <span class="panel-eyebrow">HISTORICAL RECORDS</span>
              <h2 class="panel-title">Examination Archive</h2>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search date or ID..."
                class="search-input"
              />
              <select v-model="filterSession" class="filter-select">
                <option value="all">All Sessions</option>
                <option value="1">Session 1 (Day 1)</option>
                <option value="2">Session 2 (Day 2)</option>
              </select>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredAttempts.length === 0" class="history-empty">
            <PixelEmoji emoji="scroll" size="lg" />
            <h3>No Examination Attempts Found</h3>
            <p v-if="historyList.length === 0">
              You haven't completed any Test Your Knowledge mock board examination sessions yet.
            </p>
            <p v-else>
              No examinations match your search or filter criteria.
            </p>
            <router-link to="/special-event" class="btn-primary-enter">
              Enter Today's Exam Gate →
            </router-link>
          </div>

          <!-- Table of Past Attempts -->
          <div v-else class="attempts-table-wrapper">
            <table class="attempts-table">
              <thead>
                <tr>
                  <th>Cycle Date</th>
                  <th>Session</th>
                  <th>Answered</th>
                  <th>Score & Accuracy</th>
                  <th>Status</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="att in filteredAttempts" :key="att.id">
                  <td>
                    <div class="date-cell">
                      <span class="date-main">{{ formatDate(att.event_date) }}</span>
                      <span class="id-tag">#{{ att.id }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="session-pill" :class="'s' + att.session_number">
                      {{ att.session_number === 2 ? 'Session 2 (Day 2)' : 'Session 1 (Day 1)' }}
                    </span>
                  </td>
                  <td>
                    <span class="answered-txt">{{ att.correct_count + att.incorrect_count }} Items</span>
                  </td>
                  <td>
                    <div class="score-cell">
                      <span class="score-numbers">
                        <strong>{{ att.score }}</strong> / {{ att.session_number === 2 ? 250 : 200 }}
                      </span>
                      <span class="pct-chip" :class="att.percentage >= 75 ? 'passed' : 'failed'">
                        {{ att.percentage }}%
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="status-cell">
                      <span class="status-chip" :class="att.status">
                        {{ att.status === 'disqualified' ? 'DISQUALIFIED' : att.status.toUpperCase() }}
                      </span>
                      <span v-if="att.status === 'disqualified'" class="disq-reason-tag">
                        Reason: {{ att.disqualification_reason || 'Cheating' }}
                      </span>
                    </div>
                  </td>
                  <td class="text-right">
                    <div class="actions-group">
                      <router-link :to="`/special-event/results/${att.id}`" class="tbl-btn results-btn">
                        📊 Results
                      </router-link>
                      <button class="tbl-btn review-btn" @click="openAnswersReview(att.id)">
                        📝 Review Answers
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

    <!-- Review Previous Answers Modal -->
    <div v-if="reviewModalOpen" class="review-modal-backdrop" @click.self="reviewModalOpen = false">
      <div class="review-modal-card animate-scale-up">
        <div class="modal-head">
          <div>
            <span class="modal-eyebrow">IMMUTABLE ANSWER REVIEW</span>
            <h3 class="modal-title">Session {{ activeReviewAttemptId }} Questions & Answers</h3>
          </div>
          <button class="modal-close-btn" @click="reviewModalOpen = false">✕</button>
        </div>

        <div v-if="loadingReview" class="modal-loading">
          <PixelEmoji emoji="crystal" size="lg" />
          <p>Retrieving recorded answer sheets and diagnostic explanations...</p>
        </div>

        <div v-else class="modal-questions-scroll custom-scrollbar">
          <div
            v-for="(q, idx) in activeReviewQuestions"
            :key="q.id"
            class="review-question-card"
            :class="q.is_correct ? 'is-correct' : 'is-missed'"
          >
            <div class="review-q-head">
              <span class="q-index">ITEM {{ idx + 1 }}</span>
              <span class="q-subject">{{ q.subject_name || q.domain_name }}</span>
              <span class="q-bloom">Bloom: {{ (q.bloom_level || 'apply').toUpperCase() }}</span>
              <span class="q-result-badge" :class="q.is_correct ? 'correct' : 'incorrect'">
                {{ q.is_correct ? '✓ CORRECT' : '✗ INCORRECT' }}
              </span>
            </div>

            <p class="review-q-text">{{ q.question_text }}</p>

            <!-- Choices Grid -->
            <div class="review-choices-grid">
              <div
                v-for="ckey in ['A', 'B', 'C', 'D']"
                :key="ckey"
                class="review-choice-pill"
                :class="{
                  'correct-choice': q.correct_choice === ckey,
                  'user-selected': q.selected_choice === ckey,
                  'user-wrong': q.selected_choice === ckey && !q.is_correct
                }"
              >
                <span class="choice-letter">{{ ckey }}.</span>
                <span class="choice-txt">{{ q.choices[ckey] }}</span>
                <span v-if="q.correct_choice === ckey" class="choice-tag correct">CORRECT</span>
                <span v-if="q.selected_choice === ckey && !q.is_correct" class="choice-tag selected">YOUR ANSWER</span>
              </div>
            </div>

            <!-- Verified Explanation & Study Hall Link -->
            <div class="review-explanation-box">
              <strong>Verified Clinical Explanation:</strong>
              <p>{{ q.explanation }}</p>
              <div v-if="q.study_hall_link" class="source-grounding-line">
                <span>Source Grounding: </span>
                <router-link :to="q.study_hall_link" class="source-link" target="_blank">
                  Study Hall Lesson ({{ q.part_title || 'Reviewer' }}) ↗
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { specialEventService } from '../services/specialEventService'
import { useGameStore } from '../stores/game'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import { useToastStore } from '../stores/toast'

const gameStore = useGameStore()
const toast = useToastStore()

const loading = ref(true)
const historyList = ref([])
const analyticsData = ref(null)

const searchQuery = ref('')
const filterSession = ref('all')

const reviewModalOpen = ref(false)
const loadingReview = ref(false)
const activeReviewAttemptId = ref(null)
const activeReviewQuestions = ref([])

const analyticsSummary = computed(() => analyticsData.value?.summary || null)
const avgAccuracy = computed(() => analyticsSummary.value?.overall_average_accuracy || 0)
const subjectsList = computed(() => analyticsData.value?.subjects || [])
const recommendationsList = computed(() => analyticsData.value?.recommendations || [])

const filteredAttempts = computed(() => {
  return historyList.value.filter(a => {
    // Session filter
    if (filterSession.value !== 'all') {
      if (Number(a.session_number) !== Number(filterSession.value)) return false
    }
    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const matchDate = (a.event_date || '').toLowerCase().includes(q)
      const matchTitle = (a.event_title || '').toLowerCase().includes(q)
      const matchId = String(a.id).includes(q)
      if (!matchDate && !matchTitle && !matchId) return false
    }
    return true
  })
})

function formatDate(dateStr) {
  if (!dateStr) return 'N/A'
  const d = new Date(dateStr + 'T00:00:00')
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

async function loadHistoryAndAnalytics() {
  loading.value = true
  try {
    const [histRes, analyticsRes] = await Promise.all([
      specialEventService.getHistory(),
      specialEventService.getUserAnalytics()
    ])

    historyList.value = histRes.history || []
    if (analyticsRes.success) {
      analyticsData.value = analyticsRes
    }
  } catch (err) {
    toast.error('Failed to load examination history and analytics.')
  } finally {
    loading.value = false
  }
}

async function openAnswersReview(attemptId) {
  activeReviewAttemptId.value = attemptId
  reviewModalOpen.value = true
  loadingReview.value = true
  activeReviewQuestions.value = []

  try {
    const res = await specialEventService.getReview(attemptId)
    if (res.success && res.review_questions) {
      activeReviewQuestions.value = res.review_questions
    }
  } catch (err) {
    toast.error('Unable to retrieve question snapshots for review.')
  } finally {
    loadingReview.value = false
  }
}

onMounted(() => {
  loadHistoryAndAnalytics()
})
</script>

<style scoped>
.special-event-history-page {
  min-height: 100vh;
  background-color: #070a13;
  color: #f1f5f9;
  padding: 1.5rem 1.5rem 4rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  position: relative;
}

.history-bg-vignette {
  position: fixed;
  inset: 0;
  background: radial-gradient(circle at 50% 8%, rgba(37, 99, 235, 0.12) 0%, rgba(7, 10, 19, 0.96) 75%);
  pointer-events: none;
  z-index: 0;
}

.history-container {
  max-width: 1180px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

/* Nav Header */
.history-top-nav {
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

.crumb-link {
  color: #94a3b8;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.78rem;
  transition: color 0.15s ease;
}

.crumb-link:hover {
  color: #60a5fa;
}

.location-sep {
  color: #475569;
  font-size: 0.75rem;
}

.location-crumb {
  color: #f8fafc;
  font-weight: 600;
  font-size: 0.78rem;
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
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  background: rgba(30, 41, 59, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  color: #e2e8f0;
  font-size: 0.8rem;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s;
  cursor: pointer;
}

.nav-action-btn:hover {
  background: rgba(51, 65, 85, 0.9);
  color: #60a5fa;
  border-color: rgba(96, 165, 250, 0.4);
}

/* Hero Header */
.history-hero-minimal {
  margin-bottom: 2rem;
}

.hero-eyebrow {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #60a5fa;
  text-transform: uppercase;
  display: block;
  margin-bottom: 0.35rem;
}

.hero-title {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #ffffff;
  margin: 0 0 0.4rem 0;
}

.hero-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  margin: 0;
  max-width: 800px;
  line-height: 1.5;
}

/* 4-Card KPI Metric Bar */
.metrics-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.metric-box {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1.25rem 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.m-label {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #64748b;
  text-transform: uppercase;
}

.m-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
}

.m-sub {
  font-size: 0.75rem;
  color: #94a3b8;
}

/* Insights Strip */
.insights-strip {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.insight-card {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1rem 1.25rem;
}

.insight-header {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.65rem;
}

.insight-icon {
  font-size: 1rem;
}

.insight-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: #e2e8f0;
}

.insight-body {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.insight-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.i-lbl {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
}

.i-val {
  font-size: 0.95rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Two-Column Breakdown */
.breakdown-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.panel-card {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.5rem;
}

.panel-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.panel-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.panel-tag {
  font-size: 0.72rem;
  font-weight: 600;
  color: #94a3b8;
  background: rgba(30, 41, 59, 0.6);
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.empty-hint {
  font-size: 0.85rem;
  color: #64748b;
  padding: 1.5rem 0;
  text-align: center;
}

.subject-bars-list {
  display: flex;
  flex-direction: column;
  gap: 0.95rem;
}

.subj-progress-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.subj-prog-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
}

.subj-name {
  color: #e2e8f0;
  font-weight: 500;
}

.subj-acc {
  font-weight: 700;
}

.progress-bar-track {
  height: 6px;
  background: rgba(30, 41, 59, 0.6);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.4s ease;
}

.bg-green { background: #10b981; }
.bg-amber { background: #f59e0b; }

/* Recommendations */
.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.rec-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  gap: 1rem;
}

.rec-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  flex: 1;
  min-width: 0;
}

.rec-domain {
  font-size: 0.68rem;
  font-weight: 700;
  color: #60a5fa;
  text-transform: uppercase;
}

.rec-title {
  font-size: 0.88rem;
  font-weight: 600;
  color: #f8fafc;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rec-meta {
  font-size: 0.72rem;
  color: #94a3b8;
}

.rec-action-btn {
  display: inline-flex;
  align-items: center;
  background: rgba(30, 58, 138, 0.5);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: #93c5fd;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.2s;
}

.rec-action-btn:hover {
  background: #2563eb;
  color: #ffffff;
}

/* Archive Section & Table */
.archive-section {
  margin-top: 1rem;
}

.archive-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.panel-eyebrow {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #60a5fa;
  text-transform: uppercase;
  display: block;
  margin-bottom: 0.2rem;
}

.filter-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.search-input, .filter-select {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  color: #f1f5f9;
  font-size: 0.82rem;
  padding: 0.45rem 0.75rem;
}

.search-input { width: 190px; }
.search-input:focus, .filter-select:focus {
  outline: none;
  border-color: #3b82f6;
}

.attempts-table-wrapper {
  overflow-x: auto;
}

.attempts-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.attempts-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  color: #64748b;
  font-weight: 600;
  font-size: 0.72rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.attempts-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  vertical-align: middle;
}

.attempts-table tr:hover td {
  background: rgba(30, 41, 59, 0.25);
}

.date-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.date-main {
  font-weight: 600;
  color: #ffffff;
}

.id-tag {
  font-size: 0.7rem;
  color: #64748b;
}

.session-pill {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.session-pill.s1 {
  background: rgba(59, 130, 246, 0.15);
  color: #93c5fd;
  border: 1px solid rgba(59, 130, 246, 0.25);
}

.session-pill.s2 {
  background: rgba(168, 85, 247, 0.15);
  color: #d8b4fe;
  border: 1px solid rgba(168, 85, 247, 0.25);
}

.answered-txt {
  color: #cbd5e1;
}

.score-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.score-numbers {
  font-weight: 700;
  color: #ffffff;
}

.pct-chip {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.pct-chip.passed { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.pct-chip.failed { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

.status-chip {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  background: rgba(30, 41, 59, 0.6);
  color: #94a3b8;
}

.status-chip.completed {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.25);
}

.status-chip.disqualified {
  background: rgba(239, 68, 68, 0.18);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.4);
}

.status-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.disq-reason-tag {
  font-size: 0.68rem;
  font-weight: 700;
  color: #fca5a5;
}

.actions-group {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.5rem;
}

.tbl-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.results-btn {
  background: #3b82f6;
  color: #ffffff;
  border: none;
}

.results-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.review-btn {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e2e8f0;
}

.review-btn:hover {
  background: rgba(51, 65, 85, 0.9);
  color: #60a5fa;
  border-color: rgba(96, 165, 250, 0.4);
}

.text-right { text-align: right; }

/* Empty Table State */
.history-empty {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #94a3b8;
}

.history-empty h3 {
  font-size: 1.1rem;
  color: #ffffff;
  margin: 0.75rem 0 0.25rem 0;
}

.btn-primary-enter {
  display: inline-block;
  margin-top: 1rem;
  background: #3b82f6;
  color: #ffffff;
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.85rem;
}

/* Modal */
.review-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(8px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.review-modal-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-eyebrow {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #60a5fa;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0.2rem 0 0 0;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
}

.modal-close-btn:hover { color: #ffffff; }

.modal-questions-scroll {
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.review-question-card {
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 1.25rem;
}

.review-question-card.is-correct { border-left: 3px solid #10b981; }
.review-question-card.is-missed { border-left: 3px solid #ef4444; }

.review-q-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
  font-size: 0.72rem;
  flex-wrap: wrap;
}

.q-index { font-weight: 800; color: #94a3b8; }
.q-subject { color: #60a5fa; font-weight: 600; }
.q-bloom { color: #a78bfa; font-weight: 600; }

.q-result-badge {
  margin-left: auto;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}
.q-result-badge.correct { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.q-result-badge.incorrect { background: rgba(239, 68, 68, 0.15); color: #f87171; }

.review-q-text {
  font-size: 0.95rem;
  line-height: 1.55;
  color: #f8fafc;
  margin: 0 0 1rem 0;
}

.review-choices-grid {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  margin-bottom: 1rem;
}

.review-choice-pill {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 6px;
  padding: 0.55rem 0.85rem;
  font-size: 0.85rem;
}

.review-choice-pill.correct-choice {
  border-color: rgba(16, 185, 129, 0.4);
  background: rgba(16, 185, 129, 0.1);
}

.review-choice-pill.user-wrong {
  border-color: rgba(239, 68, 68, 0.4);
  background: rgba(239, 68, 68, 0.1);
}

.choice-letter { font-weight: 700; color: #94a3b8; }
.choice-txt { flex: 1; color: #e2e8f0; }

.choice-tag {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}
.choice-tag.correct { background: #10b981; color: #064e3b; }
.choice-tag.selected { background: #ef4444; color: #ffffff; }

.review-explanation-box {
  background: rgba(15, 23, 42, 0.5);
  border-radius: 6px;
  padding: 0.85rem 1rem;
  font-size: 0.82rem;
  line-height: 1.5;
  color: #cbd5e1;
}

.review-explanation-box strong { color: #f1f5f9; display: block; margin-bottom: 0.35rem; }
.review-explanation-box p { margin: 0 0 0.5rem 0; white-space: pre-line; }

.source-grounding-line {
  font-size: 0.75rem;
  color: #94a3b8;
  padding-top: 0.35rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.source-link { color: #60a5fa; text-decoration: none; font-weight: 600; }
.source-link:hover { text-decoration: underline; }

/* Utilities */
.text-cyan { color: #38bdf8; }
.text-green { color: #4ade80; }
.text-amber { color: #fbbf24; }
.text-gold { color: #fde047; }
.text-indigo { color: #818cf8; }

.state-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 3rem 2rem;
  text-align: center;
  margin: 2rem 0;
  color: #94a3b8;
}

.state-spinner {
  margin-bottom: 1rem;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(0.95); }
}

@media (max-width: 1024px) {
  .history-top-nav {
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
  .insights-strip, .breakdown-grid-2 {
    grid-template-columns: 1fr;
  }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — SPECIAL EVENT HISTORY
   ══════════════════════════════════════════════════════════════ */
.special-event-history-page.theme-morning,
:global(.theme-morning) .special-event-history-page,
:global([data-theme="morning"]) .special-event-history-page {
  background-color: #F8F4EC !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .history-bg-vignette,
:global(.theme-morning) .special-event-history-page .history-bg-vignette,
:global([data-theme="morning"]) .special-event-history-page .history-bg-vignette {
  background: radial-gradient(circle at 50% 8%, rgba(217, 163, 76, 0.18) 0%, rgba(248, 244, 236, 0.96) 75%) !important;
}

.special-event-history-page.theme-morning .history-top-nav,
:global(.theme-morning) .special-event-history-page .history-top-nav,
:global([data-theme="morning"]) .special-event-history-page .history-top-nav {
  border-bottom-color: rgba(184, 138, 66, 0.25) !important;
}

.special-event-history-page.theme-morning .nav-location-badge,
:global(.theme-morning) .special-event-history-page .nav-location-badge,
:global([data-theme="morning"]) .special-event-history-page .nav-location-badge {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  box-shadow: 0 2px 10px rgba(143, 100, 45, 0.12) !important;
}

.special-event-history-page.theme-morning .location-pulse-dot,
:global(.theme-morning) .special-event-history-page .location-pulse-dot,
:global([data-theme="morning"]) .special-event-history-page .location-pulse-dot {
  background: #d97706 !important;
  box-shadow: 0 0 8px #d97706 !important;
}

.special-event-history-page.theme-morning .crumb-link,
:global(.theme-morning) .special-event-history-page .crumb-link,
:global([data-theme="morning"]) .special-event-history-page .crumb-link {
  color: #8C6A3E !important;
}

.special-event-history-page.theme-morning .location-crumb,
:global(.theme-morning) .special-event-history-page .location-crumb,
:global([data-theme="morning"]) .special-event-history-page .location-crumb {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .nav-action-btn,
:global(.theme-morning) .special-event-history-page .nav-action-btn,
:global([data-theme="morning"]) .special-event-history-page .nav-action-btn {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  color: #4A3525 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

.special-event-history-page.theme-morning .nav-action-btn:hover,
:global(.theme-morning) .special-event-history-page .nav-action-btn:hover,
:global([data-theme="morning"]) .special-event-history-page .nav-action-btn:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .hero-eyebrow,
:global(.theme-morning) .special-event-history-page .hero-eyebrow,
:global([data-theme="morning"]) .special-event-history-page .hero-eyebrow {
  color: #B45309 !important;
}

.special-event-history-page.theme-morning .hero-title,
:global(.theme-morning) .special-event-history-page .hero-title,
:global([data-theme="morning"]) .special-event-history-page .hero-title {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .hero-desc,
:global(.theme-morning) .special-event-history-page .hero-desc,
:global([data-theme="morning"]) .special-event-history-page .hero-desc {
  color: #5D4736 !important;
}

.special-event-history-page.theme-morning .metric-box,
:global(.theme-morning) .special-event-history-page .metric-box,
:global([data-theme="morning"]) .special-event-history-page .metric-box {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 4px 14px rgba(143, 100, 45, 0.1) !important;
}

.special-event-history-page.theme-morning .m-label,
:global(.theme-morning) .special-event-history-page .m-label,
:global([data-theme="morning"]) .special-event-history-page .m-label {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .m-value,
:global(.theme-morning) .special-event-history-page .m-value,
:global([data-theme="morning"]) .special-event-history-page .m-value {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .m-sub,
:global(.theme-morning) .special-event-history-page .m-sub,
:global([data-theme="morning"]) .special-event-history-page .m-sub {
  color: #6C5440 !important;
}

/* Secondary Insight Cards */
.special-event-history-page.theme-morning .insight-card,
:global(.theme-morning) .special-event-history-page .insight-card,
:global([data-theme="morning"]) .special-event-history-page .insight-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 4px 14px rgba(143, 100, 45, 0.08) !important;
}

.special-event-history-page.theme-morning .insight-title,
:global(.theme-morning) .special-event-history-page .insight-title,
:global([data-theme="morning"]) .special-event-history-page .insight-title {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .i-lbl,
:global(.theme-morning) .special-event-history-page .i-lbl,
:global([data-theme="morning"]) .special-event-history-page .i-lbl {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .i-val,
:global(.theme-morning) .special-event-history-page .i-val,
:global([data-theme="morning"]) .special-event-history-page .i-val {
  color: #2C1E14 !important;
}

/* Panel Cards & Headers */
.special-event-history-page.theme-morning .panel-card,
:global(.theme-morning) .special-event-history-page .panel-card,
:global([data-theme="morning"]) .special-event-history-page .panel-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 6px 18px rgba(143, 100, 45, 0.08) !important;
}

.special-event-history-page.theme-morning .panel-head,
:global(.theme-morning) .special-event-history-page .panel-head,
:global([data-theme="morning"]) .special-event-history-page .panel-head {
  border-bottom: 1.5px solid rgba(184, 138, 66, 0.22) !important;
}

.special-event-history-page.theme-morning .panel-title,
:global(.theme-morning) .special-event-history-page .panel-title,
:global([data-theme="morning"]) .special-event-history-page .panel-title {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .panel-tag,
:global(.theme-morning) .special-event-history-page .panel-tag,
:global([data-theme="morning"]) .special-event-history-page .panel-tag {
  background: #F3E7CF !important;
  color: #7A5F43 !important;
  border: 1px solid #D5BF97 !important;
}

.special-event-history-page.theme-morning .panel-tag.text-gold,
:global(.theme-morning) .special-event-history-page .panel-tag.text-gold,
:global([data-theme="morning"]) .special-event-history-page .panel-tag.text-gold {
  background: #FDF3D6 !important;
  color: #9A6700 !important;
  border: 1px solid #E6CA85 !important;
}

.special-event-history-page.theme-morning .empty-hint,
:global(.theme-morning) .special-event-history-page .empty-hint,
:global([data-theme="morning"]) .special-event-history-page .empty-hint {
  color: #7A5F43 !important;
}

/* Subject Mastery Progress Bars */
.special-event-history-page.theme-morning .subj-name,
:global(.theme-morning) .special-event-history-page .subj-name,
:global([data-theme="morning"]) .special-event-history-page .subj-name {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .progress-bar-track,
:global(.theme-morning) .special-event-history-page .progress-bar-track,
:global([data-theme="morning"]) .special-event-history-page .progress-bar-track {
  background: #E8DBC3 !important;
}

.special-event-history-page.theme-morning .progress-bar-fill.bg-green,
:global(.theme-morning) .special-event-history-page .progress-bar-fill.bg-green,
:global([data-theme="morning"]) .special-event-history-page .progress-bar-fill.bg-green {
  background: #16A34A !important;
}

.special-event-history-page.theme-morning .progress-bar-fill.bg-amber,
:global(.theme-morning) .special-event-history-page .progress-bar-fill.bg-amber,
:global([data-theme="morning"]) .special-event-history-page .progress-bar-fill.bg-amber {
  background: #D97706 !important;
}

/* Recommended Study Hall Lessons */
.special-event-history-page.theme-morning .rec-item,
:global(.theme-morning) .special-event-history-page .rec-item,
:global([data-theme="morning"]) .special-event-history-page .rec-item {
  background: #FBF6EC !important;
  border: 1.5px solid #E2D2B8 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.05) !important;
}

.special-event-history-page.theme-morning .rec-domain,
:global(.theme-morning) .special-event-history-page .rec-domain,
:global([data-theme="morning"]) .special-event-history-page .rec-domain {
  color: #B45309 !important;
}

.special-event-history-page.theme-morning .rec-title,
:global(.theme-morning) .special-event-history-page .rec-title,
:global([data-theme="morning"]) .special-event-history-page .rec-title {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .rec-meta,
:global(.theme-morning) .special-event-history-page .rec-meta,
:global([data-theme="morning"]) .special-event-history-page .rec-meta {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .rec-action-btn,
:global(.theme-morning) .special-event-history-page .rec-action-btn,
:global([data-theme="morning"]) .special-event-history-page .rec-action-btn {
  background: #B88A42 !important;
  border: 1px solid #9E7431 !important;
  color: #FFFFFF !important;
  box-shadow: 0 2px 6px rgba(143, 100, 45, 0.2) !important;
}

.special-event-history-page.theme-morning .rec-action-btn:hover,
:global(.theme-morning) .special-event-history-page .rec-action-btn:hover,
:global([data-theme="morning"]) .special-event-history-page .rec-action-btn:hover {
  background: #C7974C !important;
  border-color: #B88A42 !important;
  color: #FFFFFF !important;
}

/* Examination Archive & Filter */
.special-event-history-page.theme-morning .panel-eyebrow,
:global(.theme-morning) .special-event-history-page .panel-eyebrow,
:global([data-theme="morning"]) .special-event-history-page .panel-eyebrow {
  color: #B45309 !important;
}

.special-event-history-page.theme-morning .search-input,
.special-event-history-page.theme-morning .filter-select,
:global(.theme-morning) .special-event-history-page .search-input,
:global(.theme-morning) .special-event-history-page .filter-select,
:global([data-theme="morning"]) .special-event-history-page .search-input,
:global([data-theme="morning"]) .special-event-history-page .filter-select {
  background: #FFFDF8 !important;
  border: 1.5px solid #C8AC7B !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .search-input:focus,
.special-event-history-page.theme-morning .filter-select:focus,
:global(.theme-morning) .special-event-history-page .search-input:focus,
:global(.theme-morning) .special-event-history-page .filter-select:focus,
:global([data-theme="morning"]) .special-event-history-page .search-input:focus,
:global([data-theme="morning"]) .special-event-history-page .filter-select:focus {
  border-color: #B88A42 !important;
}

.special-event-history-page.theme-morning .search-input::placeholder,
:global(.theme-morning) .special-event-history-page .search-input::placeholder,
:global([data-theme="morning"]) .special-event-history-page .search-input::placeholder {
  color: #9C8265 !important;
}

/* Attempts Table */
.special-event-history-page.theme-morning .attempts-table th,
:global(.theme-morning) .special-event-history-page .attempts-table th,
:global([data-theme="morning"]) .special-event-history-page .attempts-table th {
  background: #FAF3E5 !important;
  color: #7A5F43 !important;
  border-bottom: 1.5px solid #D9C39E !important;
}

.special-event-history-page.theme-morning .attempts-table td,
:global(.theme-morning) .special-event-history-page .attempts-table td,
:global([data-theme="morning"]) .special-event-history-page .attempts-table td {
  border-bottom: 1px solid #EADBCE !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .attempts-table tr:hover td,
:global(.theme-morning) .special-event-history-page .attempts-table tr:hover td,
:global([data-theme="morning"]) .special-event-history-page .attempts-table tr:hover td {
  background: #FFF7E7 !important;
}

.special-event-history-page.theme-morning .date-main,
:global(.theme-morning) .special-event-history-page .date-main,
:global([data-theme="morning"]) .special-event-history-page .date-main {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .id-tag,
:global(.theme-morning) .special-event-history-page .id-tag,
:global([data-theme="morning"]) .special-event-history-page .id-tag {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .session-pill.s1,
:global(.theme-morning) .special-event-history-page .session-pill.s1,
:global([data-theme="morning"]) .special-event-history-page .session-pill.s1 {
  background: #E0EDFD !important;
  color: #1D4ED8 !important;
  border: 1px solid #BFDBFE !important;
}

.special-event-history-page.theme-morning .session-pill.s2,
:global(.theme-morning) .special-event-history-page .session-pill.s2,
:global([data-theme="morning"]) .special-event-history-page .session-pill.s2 {
  background: #F3E8FF !important;
  color: #7E22CE !important;
  border: 1px solid #E9D5FF !important;
}

.special-event-history-page.theme-morning .answered-txt,
:global(.theme-morning) .special-event-history-page .answered-txt,
:global([data-theme="morning"]) .special-event-history-page .answered-txt {
  color: #5D4736 !important;
}

.special-event-history-page.theme-morning .score-numbers,
:global(.theme-morning) .special-event-history-page .score-numbers,
:global([data-theme="morning"]) .special-event-history-page .score-numbers {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .pct-chip.passed,
:global(.theme-morning) .special-event-history-page .pct-chip.passed,
:global([data-theme="morning"]) .special-event-history-page .pct-chip.passed {
  background: #DCFCE7 !important;
  color: #15803D !important;
  border: 1px solid #BBF7D0 !important;
}

.special-event-history-page.theme-morning .pct-chip.failed,
:global(.theme-morning) .special-event-history-page .pct-chip.failed,
:global([data-theme="morning"]) .special-event-history-page .pct-chip.failed {
  background: #FEF3C7 !important;
  color: #B45309 !important;
  border: 1px solid #FDE68A !important;
}

.special-event-history-page.theme-morning .status-chip,
:global(.theme-morning) .special-event-history-page .status-chip,
:global([data-theme="morning"]) .special-event-history-page .status-chip {
  background: #F3E7CF !important;
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .status-chip.completed,
:global(.theme-morning) .special-event-history-page .status-chip.completed,
:global([data-theme="morning"]) .special-event-history-page .status-chip.completed {
  background: #DCFCE7 !important;
  color: #15803D !important;
  border: 1px solid #BBF7D0 !important;
}

.special-event-history-page.theme-morning .status-chip.disqualified,
:global(.theme-morning) .special-event-history-page .status-chip.disqualified,
:global([data-theme="morning"]) .special-event-history-page .status-chip.disqualified {
  background: #FEE2E2 !important;
  color: #B91C1C !important;
  border: 1px solid #FECACA !important;
}

.special-event-history-page.theme-morning .disq-reason-tag,
:global(.theme-morning) .special-event-history-page .disq-reason-tag,
:global([data-theme="morning"]) .special-event-history-page .disq-reason-tag {
  color: #B91C1C !important;
}

.special-event-history-page.theme-morning .tbl-btn.results-btn,
:global(.theme-morning) .special-event-history-page .tbl-btn.results-btn,
:global([data-theme="morning"]) .special-event-history-page .tbl-btn.results-btn {
  background: #2563EB !important;
  color: #FFFFFF !important;
}

.special-event-history-page.theme-morning .tbl-btn.results-btn:hover,
:global(.theme-morning) .special-event-history-page .tbl-btn.results-btn:hover,
:global([data-theme="morning"]) .special-event-history-page .tbl-btn.results-btn:hover {
  background: #1D4ED8 !important;
}

.special-event-history-page.theme-morning .tbl-btn.review-btn,
:global(.theme-morning) .special-event-history-page .tbl-btn.review-btn,
:global([data-theme="morning"]) .special-event-history-page .tbl-btn.review-btn {
  background: #FFF8EB !important;
  border: 1.5px solid #C8AC7B !important;
  color: #3D2B1F !important;
}

.special-event-history-page.theme-morning .tbl-btn.review-btn:hover,
:global(.theme-morning) .special-event-history-page .tbl-btn.review-btn:hover,
:global([data-theme="morning"]) .special-event-history-page .tbl-btn.review-btn:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .history-empty,
:global(.theme-morning) .special-event-history-page .history-empty,
:global([data-theme="morning"]) .special-event-history-page .history-empty {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .history-empty h3,
:global(.theme-morning) .special-event-history-page .history-empty h3,
:global([data-theme="morning"]) .special-event-history-page .history-empty h3 {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .btn-primary-enter,
:global(.theme-morning) .special-event-history-page .btn-primary-enter,
:global([data-theme="morning"]) .special-event-history-page .btn-primary-enter {
  background: #B88A42 !important;
  color: #FFFFFF !important;
}

.special-event-history-page.theme-morning .state-card,
:global(.theme-morning) .special-event-history-page .state-card,
:global([data-theme="morning"]) .special-event-history-page .state-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  color: #7A5F43 !important;
}

/* Color utilities in morning mode */
.special-event-history-page.theme-morning .text-green,
:global(.theme-morning) .special-event-history-page .text-green,
:global([data-theme="morning"]) .special-event-history-page .text-green {
  color: #15803D !important;
}

.special-event-history-page.theme-morning .text-amber,
:global(.theme-morning) .special-event-history-page .text-amber,
:global([data-theme="morning"]) .special-event-history-page .text-amber {
  color: #B45309 !important;
}

.special-event-history-page.theme-morning .text-gold,
:global(.theme-morning) .special-event-history-page .text-gold,
:global([data-theme="morning"]) .special-event-history-page .text-gold {
  color: #9A6700 !important;
}

.special-event-history-page.theme-morning .text-cyan,
:global(.theme-morning) .special-event-history-page .text-cyan,
:global([data-theme="morning"]) .special-event-history-page .text-cyan {
  color: #0369A1 !important;
}

.special-event-history-page.theme-morning .text-indigo,
:global(.theme-morning) .special-event-history-page .text-indigo,
:global([data-theme="morning"]) .special-event-history-page .text-indigo {
  color: #4338CA !important;
}

/* Modal Review Morning Mode */
.special-event-history-page.theme-morning .review-modal-card,
:global(.theme-morning) .special-event-history-page .review-modal-card,
:global([data-theme="morning"]) .special-event-history-page .review-modal-card {
  background: #F8F4EC !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 20px 50px rgba(78, 52, 21, 0.25) !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .modal-head,
:global(.theme-morning) .special-event-history-page .modal-head,
:global([data-theme="morning"]) .special-event-history-page .modal-head {
  border-bottom: 1.5px solid #D9C39E !important;
}

.special-event-history-page.theme-morning .modal-eyebrow,
:global(.theme-morning) .special-event-history-page .modal-eyebrow,
:global([data-theme="morning"]) .special-event-history-page .modal-eyebrow {
  color: #B45309 !important;
}

.special-event-history-page.theme-morning .modal-title,
:global(.theme-morning) .special-event-history-page .modal-title,
:global([data-theme="morning"]) .special-event-history-page .modal-title {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .modal-close-btn,
:global(.theme-morning) .special-event-history-page .modal-close-btn,
:global([data-theme="morning"]) .special-event-history-page .modal-close-btn {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .modal-close-btn:hover,
:global(.theme-morning) .special-event-history-page .modal-close-btn:hover,
:global([data-theme="morning"]) .special-event-history-page .modal-close-btn:hover {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .review-question-card,
:global(.theme-morning) .special-event-history-page .review-question-card,
:global([data-theme="morning"]) .special-event-history-page .review-question-card {
  background: #FFFDF9 !important;
  border: 1px solid #D9C39E !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.05) !important;
}

.special-event-history-page.theme-morning .review-question-card.is-correct,
:global(.theme-morning) .special-event-history-page .review-question-card.is-correct,
:global([data-theme="morning"]) .special-event-history-page .review-question-card.is-correct {
  border-left: 4px solid #16A34A !important;
}

.special-event-history-page.theme-morning .review-question-card.is-missed,
:global(.theme-morning) .special-event-history-page .review-question-card.is-missed,
:global([data-theme="morning"]) .special-event-history-page .review-question-card.is-missed {
  border-left: 4px solid #DC2626 !important;
}

.special-event-history-page.theme-morning .q-index,
:global(.theme-morning) .special-event-history-page .q-index,
:global([data-theme="morning"]) .special-event-history-page .q-index {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .q-subject,
:global(.theme-morning) .special-event-history-page .q-subject,
:global([data-theme="morning"]) .special-event-history-page .q-subject {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .q-bloom,
:global(.theme-morning) .special-event-history-page .q-bloom,
:global([data-theme="morning"]) .special-event-history-page .q-bloom {
  color: #6B21A8 !important;
}

.special-event-history-page.theme-morning .review-q-text,
:global(.theme-morning) .special-event-history-page .review-q-text,
:global([data-theme="morning"]) .special-event-history-page .review-q-text {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .review-choice-pill,
:global(.theme-morning) .special-event-history-page .review-choice-pill,
:global([data-theme="morning"]) .special-event-history-page .review-choice-pill {
  background: #F9F3E8 !important;
  border: 1px solid #DCC7A4 !important;
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .review-choice-pill.correct-choice,
:global(.theme-morning) .special-event-history-page .review-choice-pill.correct-choice,
:global([data-theme="morning"]) .special-event-history-page .review-choice-pill.correct-choice {
  background: #DCFCE7 !important;
  border-color: #86EFAC !important;
  color: #14532D !important;
}

.special-event-history-page.theme-morning .review-choice-pill.user-wrong,
:global(.theme-morning) .special-event-history-page .review-choice-pill.user-wrong,
:global([data-theme="morning"]) .special-event-history-page .review-choice-pill.user-wrong {
  background: #FEE2E2 !important;
  border-color: #FCA5A5 !important;
  color: #7F1D1D !important;
}

.special-event-history-page.theme-morning .choice-letter,
:global(.theme-morning) .special-event-history-page .choice-letter,
:global([data-theme="morning"]) .special-event-history-page .choice-letter {
  color: #7A5F43 !important;
}

.special-event-history-page.theme-morning .choice-txt,
:global(.theme-morning) .special-event-history-page .choice-txt,
:global([data-theme="morning"]) .special-event-history-page .choice-txt {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .review-explanation-box,
:global(.theme-morning) .special-event-history-page .review-explanation-box,
:global([data-theme="morning"]) .special-event-history-page .review-explanation-box {
  background: #F3EAE0 !important;
  color: #3A2B1D !important;
  border: 1px solid #D8C5AE !important;
}

.special-event-history-page.theme-morning .review-explanation-box strong,
:global(.theme-morning) .special-event-history-page .review-explanation-box strong,
:global([data-theme="morning"]) .special-event-history-page .review-explanation-box strong {
  color: #2C1E14 !important;
}

.special-event-history-page.theme-morning .source-grounding-line,
:global(.theme-morning) .special-event-history-page .source-grounding-line,
:global([data-theme="morning"]) .special-event-history-page .source-grounding-line {
  color: #7A5F43 !important;
  border-top: 1px solid #D8C5AE !important;
}

.special-event-history-page.theme-morning .source-link,
:global(.theme-morning) .special-event-history-page .source-link,
:global([data-theme="morning"]) .special-event-history-page .source-link {
  color: #B45309 !important;
}
</style>
