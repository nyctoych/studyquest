<template>
  <div class="special-event-results-page" :class="{ 'theme-morning': gameStore.isMorning }">
    <div class="results-bg-vignette"></div>

    <!-- Loading State -->
    <div v-if="loading" class="results-loader">
      <div class="loader-crest">
        <PixelEmoji emoji="crystal" size="game" />
      </div>
      <h2>CALCULATING TRIAL EVALUATION</h2>
      <p>Consulting authoritative diagnostic archives...</p>
    </div>

    <!-- Main Results View -->
    <div v-else-if="attempt" class="results-viewport animate-fade-in">
      <!-- Top Navigation -->
      <header class="results-top-nav">
        <!-- Left spacer to clear the fixed Back button -->
        <div class="nav-left-spacer" aria-hidden="true"></div>

        <div class="nav-location-badge">
          <span class="location-pulse-dot"></span>
          <button type="button" class="crumb-link" @click="goToCitadel">Citadel Hub</button>
          <span class="location-sep">/</span>
          <button type="button" class="crumb-link" @click="goToLanding">Test Your Knowledge</button>
          <span class="location-sep">/</span>
          <span class="location-crumb">Evaluation</span>
        </div>

        <div class="nav-actions-cluster">
          <button type="button" class="nav-action-btn" @click="goToHistory">
            <PixelEmoji emoji="book" size="xs" />
            <span>Mock Exam History</span>
          </button>
          <button type="button" class="nav-action-btn" @click="goToLanding">
            <PixelEmoji emoji="shield" size="xs" />
            <span>Session Hub</span>
          </button>
        </div>

        <!-- Right spacer to clear the fixed theme toggle -->
        <div class="nav-right-spacer" aria-hidden="true"></div>
      </header>

      <!-- 1. TOP RESULT HEADER & SCORE SUMMARY -->
      <section class="results-header-section gothic-panel">
        <!-- Disqualification Alert Banner -->
        <div v-if="attempt.status === 'disqualified'" class="disqualified-alert-banner animate-fade-in">
          <div class="disq-banner-left">
            <span class="disq-banner-icon">🚫</span>
            <div class="disq-banner-text">
              <div class="disq-pill">EXAMINATION TERMINATED & DISQUALIFIED</div>
              <h2 class="disq-heading">Reason: {{ attempt.disqualification_reason || 'Cheating' }}</h2>
              <p class="disq-message">
                This mock board examination was not finished. It was automatically terminated because the examinee changed tabs or departed the examination page 3 times, exceeding the 2-warning Anti-Cheating limit.
              </p>
            </div>
          </div>
          <div class="disq-banner-right">
            <span class="disq-tag red">Violations: {{ attempt.tab_switch_count || 3 }} / 3 (Limit Reached)</span>
            <span class="disq-tag dark">Result: Cheating</span>
          </div>
        </div>

        <div class="header-banner-row">
          <div class="header-badge-group">
            <span class="trial-tag" :class="{ 'text-red': attempt.status === 'disqualified' }">
              {{ attempt.status === 'disqualified' ? 'TEST YOUR KNOWLEDGE TERMINATED (DISQUALIFIED)' : 'TEST YOUR KNOWLEDGE COMPLETE' }}
            </span>
            <span class="session-tag" v-if="attempt.session_number">SESSION {{ attempt.session_number }}</span>
            <span class="trial-date">{{ attempt.event_date }}</span>
          </div>

          <!-- Quick Rewards Strip -->
          <div v-if="rewardsInfo && attempt.status !== 'disqualified'" class="rewards-compact-strip">
            <span class="reward-chip gold">
              <PixelEmoji emoji="star" size="xs" /> +{{ rewardsInfo.xp_gained || attempt.xp_awarded }} XP
            </span>
            <span class="reward-chip amber">
              <PixelEmoji emoji="coin" size="xs" /> +{{ rewardsInfo.coins_gained || attempt.coins_awarded }} Coins
            </span>
            <span v-if="rewardsInfo.is_perfect" class="reward-chip purple">
              <PixelEmoji emoji="crown" size="xs" /> PERFECT SCORE
            </span>
          </div>
        </div>

        <!-- 3-4 Primary Metric Stat Cards -->
        <div class="primary-metrics-grid">
          <!-- FINAL SCORE -->
          <div class="metric-card score-metric">
            <div class="metric-card-header">
              <span class="metric-icon">🏆</span>
              <span class="metric-label">SESSION SCORE</span>
            </div>
            <div class="metric-value-row">
              <span class="val-big" :class="{ 'text-red': attempt.status === 'disqualified' }">{{ attempt.score }}</span>
              <span class="val-denom">/ {{ totalQuestions }}</span>
            </div>
            <div class="metric-progress-track">
              <div class="metric-progress-fill" :style="{ width: attempt.percentage + '%' }" :class="getBarClass(attempt.percentage)"></div>
            </div>
          </div>

          <!-- ACCURACY -->
          <div class="metric-card accuracy-metric">
            <div class="metric-card-header">
              <span class="metric-icon">🎯</span>
              <span class="metric-label">ACCURACY</span>
            </div>
            <div class="metric-value-row">
              <span class="val-big pct" :class="attempt.status === 'disqualified' ? 'text-red' : getAccuracyClass(attempt.percentage)">{{ attempt.percentage }}%</span>
            </div>
            <span v-if="attempt.status === 'disqualified'" class="metric-sub-status status-disq">
              Disqualified (Cheating)
            </span>
            <span v-else class="metric-sub-status" :class="isPassed ? 'status-pass' : 'status-dev'">
              {{ isPassed ? 'Strong Mastery' : 'Developing Mastery' }}
            </span>
          </div>

          <!-- TIME ELAPSED -->
          <div class="metric-card time-metric">
            <div class="metric-card-header">
              <span class="metric-icon">⏳</span>
              <span class="metric-label">TIME</span>
            </div>
            <div class="metric-value-row">
              <span class="val-big time font-mono">{{ formatSeconds(attempt.total_time) }}</span>
            </div>
            <span class="metric-sub-status">
              Avg {{ evaluation?.average_response_time || 0 }}s / item
            </span>
          </div>

          <!-- OUTCOMES -->
          <div class="metric-card outcomes-metric">
            <div class="metric-card-header">
              <span class="metric-icon">⚖️</span>
              <span class="metric-label">OUTCOMES</span>
            </div>
            <div class="outcomes-stat-row">
              <div class="outcome-pill correct">
                <span class="num">{{ attempt.correct_count }}</span>
                <span class="lbl">Correct</span>
              </div>
              <div class="outcome-pill missed">
                <span class="num">{{ attempt.incorrect_count }}</span>
                <span class="lbl">Missed</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 2. OVERALL PERFORMANCE (Concise 1-2 sentence learning summary) -->
      <section class="performance-summary-section gothic-panel">
        <div class="summary-content">
          <div class="summary-lead">
            <span class="summary-eyebrow">PERFORMANCE SUMMARY</span>
            <p class="summary-text">{{ briefPerformanceSummary }}</p>
          </div>
          <!-- Strengths / Developing / Review Chips -->
          <div class="performance-chips-strip">
            <div v-if="cognitiveProfile.strongest_areas?.length" class="chip-group">
              <span class="chip-label text-green">STRONGER:</span>
              <span v-for="a in cognitiveProfile.strongest_areas" :key="a" class="status-chip chip-strong">{{ a }}</span>
            </div>
            <div v-if="cognitiveProfile.developing_areas?.length" class="chip-group">
              <span class="chip-label text-gold">DEVELOPING:</span>
              <span v-for="a in cognitiveProfile.developing_areas" :key="a" class="status-chip chip-dev">{{ a }}</span>
            </div>
            <div v-if="cognitiveProfile.review_needed_areas?.length" class="chip-group">
              <span class="chip-label text-purple">REVIEW:</span>
              <span v-for="a in cognitiveProfile.review_needed_areas" :key="a" class="status-chip chip-review">{{ a }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- 2.5 SUBJECT PERFORMANCE (Psychometrician Board Exam Subjects) -->
      <section v-if="subjectStats && subjectStats.length > 0" class="performance-summary-section gothic-panel">
        <div class="summary-content">
          <div class="summary-lead">
            <span class="summary-eyebrow">CORE BOARD SUBJECTS</span>
            <h3 class="section-title">PERFORMANCE BY EXAMINATION SUBJECT</h3>
          </div>
          <div class="subject-stats-grid">
            <div
              v-for="subj in subjectStats"
              :key="subj.subject_name"
              class="subject-stat-card"
            >
              <div class="subj-card-header">
                <span class="subj-name">{{ subj.subject_name }}</span>
                <span class="subj-acc-badge" :class="subj.accuracy >= 75 ? 'text-green' : 'text-amber'">
                  {{ subj.accuracy }}%
                </span>
              </div>
              <div class="subj-score-row">
                <span class="subj-score">{{ subj.correct_count }} / {{ subj.total_questions }} Items Correct</span>
              </div>
              <div class="metric-progress-track">
                <div class="metric-progress-fill" :style="{ width: subj.accuracy + '%' }" :class="getBarClass(subj.accuracy)"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- 3. BLOOM PERFORMANCE (6 Compact Horizontal Cards in 2x3 Grid) -->
      <section class="bloom-section gothic-panel">
        <div class="section-title-bar">
          <div class="title-left">
            <PixelEmoji emoji="crystal" size="xs" />
            <h2 class="section-title">BLOOM'S TAXONOMY PERFORMANCE</h2>
          </div>
          <button
            type="button"
            class="bloom-guide-btn"
            @click="openBloomModal('overview')"
            title="Learn how Bloom's Taxonomy classifies cognitive demand"
          >
            <span>📖 Bloom Guide</span>
          </button>
        </div>

        <div class="bloom-compact-grid">
          <div
            v-for="lvl in bloomSummary"
            :key="lvl.bloom_level"
            class="bloom-compact-card"
            :class="'level-' + lvl.bloom_level"
            @click="openBloomModal(lvl.bloom_level)"
            :title="`Click to explore ${lvl.display_name} definition`"
          >
            <div class="bloom-card-top-row">
              <div class="bloom-name-group">
                <span class="bloom-level-num">L{{ getBloomOrder(lvl.bloom_level) }}</span>
                <span class="bloom-level-name">{{ lvl.display_name }}</span>
              </div>
              <div class="bloom-acc-badge" :class="getAccuracyClass(lvl.accuracy)">
                {{ lvl.accuracy }}%
              </div>
            </div>

            <!-- Compact Progress Bar -->
            <div class="bloom-mini-track">
              <div
                class="bloom-mini-fill"
                :style="{ width: lvl.accuracy + '%' }"
                :class="getBarClass(lvl.accuracy)"
              ></div>
            </div>

            <div class="bloom-card-bot-row">
              <span class="bloom-status-tag" :class="getBloomStatusClass(lvl.accuracy)">
                {{ getBloomStatusLabel(lvl.accuracy) }}
              </span>
              <span class="bloom-counts-text">
                {{ lvl.correct_count }}/{{ lvl.question_count }} items
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- 4. PRIMARY REVIEW FOCUS (Highlighted Actionable Weakness) -->
      <section v-if="primaryWeaknessLevel" class="primary-focus-section gothic-panel">
        <div class="focus-layout">
          <div class="focus-icon-box">
            <PixelEmoji emoji="target" size="lg" />
          </div>
          <div class="focus-details">
            <div class="focus-tag-strip">
              <span class="focus-pill">PRIMARY REVIEW FOCUS</span>
              <span v-if="secondaryWeaknessLevel" class="focus-sub-pill">
                SECONDARY: {{ secondaryWeaknessLevel.toUpperCase() }}
              </span>
            </div>
            <h3 class="focus-heading">{{ primaryWeaknessLevel.toUpperCase() }}</h3>
            <p class="focus-desc">
              Your recent performance shows that this cognitive level needs more practice.
              {{ getBloomDescriptor(primaryWeaknessLevel) }}
            </p>
          </div>
          <div class="focus-action">
            <router-link
              v-if="topRecommendationLink"
              :to="topRecommendationLink"
              class="rpg-action-btn primary-study-btn"
            >
              <PixelEmoji emoji="book" size="xs" />
              <span>REVIEW IN STUDY HALL</span>
            </router-link>
            <button
              v-else
              class="rpg-action-btn primary-study-btn"
              @click="openBloomModal(primaryWeaknessLevel)"
            >
              <span>INSPECT LEVEL</span>
            </button>
          </div>
        </div>
      </section>

      <!-- 5. STUDY HALL RECOMMENDATIONS (Top 3 Initially + View All Toggle) -->
      <section v-if="bloomRecommendations.length > 0" class="recommendations-section gothic-panel">
        <div class="section-title-bar">
          <div class="title-left">
            <PixelEmoji emoji="book" size="xs" />
            <h2 class="section-title">STUDY HALL RECOMMENDATIONS</h2>
            <span class="recs-count-tag">
              Showing {{ displayedRecommendations.length }} of {{ bloomRecommendations.length }}
            </span>
          </div>

          <button
            v-if="bloomRecommendations.length > 3"
            class="toggle-all-recs-btn"
            @click="showAllRecs = !showAllRecs"
          >
            <span>{{ showAllRecs ? 'Show Top 3' : 'View All (' + bloomRecommendations.length + ')' }}</span>
            <span class="arrow">{{ showAllRecs ? '▲' : '▼' }}</span>
          </button>
        </div>

        <div class="recs-compact-grid">
          <div
            v-for="rec in displayedRecommendations"
            :key="rec.domain_id + '_' + rec.study_part_id"
            class="rec-compact-card"
          >
            <div class="rec-card-info">
              <span class="rec-domain-label">{{ rec.domain_name }}</span>
              <h4 class="rec-topic-title" :title="rec.part_title">
                Part {{ rec.part_number }}: {{ rec.part_title }}
              </h4>
              <div class="rec-tags-row">
                <span class="rec-missed-badge">{{ rec.missed_count }} missed</span>
                <span
                  v-for="b in (rec.bloom_levels || []).slice(0, 2)"
                  :key="b"
                  class="rec-bloom-badge"
                >
                  {{ b }}
                </span>
                <span
                  v-if="(rec.bloom_levels || []).length > 2"
                  class="rec-bloom-more"
                  :title="(rec.bloom_levels || []).slice(2).join(', ')"
                >
                  +{{ rec.bloom_levels.length - 2 }}
                </span>
              </div>
            </div>
            <router-link :to="rec.study_hall_link" class="rec-study-link">
              <span>Review</span> ↗
            </router-link>
          </div>
        </div>
      </section>

      <!-- 6. OPTIONAL DETAILS / ADVANCED ANALYTICS (Expandable Collapsible Accordions) -->
      <section class="advanced-analytics-section gothic-panel">
        <div class="section-title-bar clickable-accordion-header" @click="toggleAdvanced">
          <div class="title-left">
            <PixelEmoji emoji="gear" size="xs" />
            <h2 class="section-title">ADVANCED ANALYTICS & BREAKDOWNS</h2>
          </div>
          <button class="accordion-toggle-btn">
            <span>{{ isAdvancedOpen ? 'Collapse Details' : 'Expand Details' }}</span>
            <span class="accordion-icon">{{ isAdvancedOpen ? '▲' : '▼' }}</span>
          </button>
        </div>

        <!-- Collapsible Container -->
        <div v-show="isAdvancedOpen" class="advanced-panels-container animate-fade-in">
          <!-- Sub-accordion Tabs -->
          <div class="advanced-subtabs-bar">
            <button
              class="subtab-btn"
              :class="{ active: advancedSubtab === 'domains' }"
              @click="advancedSubtab = 'domains'"
            >
              Domain Performance
            </button>
            <button
              class="subtab-btn"
              :class="{ active: advancedSubtab === 'difficulty' }"
              @click="advancedSubtab = 'difficulty'"
            >
              Difficulty Breakdown
            </button>
            <button
              class="subtab-btn"
              :class="{ active: advancedSubtab === 'types' }"
              @click="advancedSubtab = 'types'"
            >
              Question Types
            </button>
            <button
              class="subtab-btn"
              :class="{ active: advancedSubtab === 'review' }"
              @click="advancedSubtab = 'review'"
            >
              Full {{ totalQuestions }} Items Review
            </button>
          </div>

          <!-- Panel A: Domain Performance -->
          <div v-if="advancedSubtab === 'domains'" class="subpanel-content">
            <div class="breakdown-list">
              <div v-for="d in domainStats" :key="d.domain_id" class="breakdown-row">
                <div class="breakdown-info">
                  <span class="breakdown-name">{{ d.name }}</span>
                  <span class="breakdown-counts">{{ d.correct }} / {{ d.total }} ({{ d.percentage }}%)</span>
                </div>
                <div class="breakdown-track">
                  <div
                    class="breakdown-fill"
                    :style="{ width: d.percentage + '%' }"
                    :class="getBarClass(d.percentage)"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel B: Difficulty Breakdown -->
          <div v-if="advancedSubtab === 'difficulty'" class="subpanel-content">
            <div class="breakdown-list">
              <div v-for="diff in difficultyStats" :key="diff.difficulty" class="breakdown-row">
                <div class="breakdown-info">
                  <span class="breakdown-name text-capitalize">{{ formatDifficulty(diff.difficulty) }}</span>
                  <span class="breakdown-counts">{{ diff.correct }} / {{ diff.total }} ({{ diff.percentage }}%)</span>
                </div>
                <div class="breakdown-track">
                  <div
                    class="breakdown-fill"
                    :style="{ width: diff.percentage + '%' }"
                    :class="getBarClass(diff.percentage)"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel C: Question Types -->
          <div v-if="advancedSubtab === 'types'" class="subpanel-content">
            <div class="breakdown-list">
              <div v-for="t in typeStats" :key="t.type" class="breakdown-row">
                <div class="breakdown-info">
                  <span class="breakdown-name text-capitalize">{{ formatQuestionType(t.type) }}</span>
                  <span class="breakdown-counts">{{ t.correct }} / {{ t.total }} ({{ t.percentage }}%)</span>
                </div>
                <div class="breakdown-track">
                  <div
                    class="breakdown-fill"
                    :style="{ width: t.percentage + '%' }"
                    :class="getBarClass(t.percentage)"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel D: Full Item Review -->
          <div v-if="advancedSubtab === 'review'" class="subpanel-content review-subpanel">
            <div v-if="loadingReview" class="review-loading">
              <PixelEmoji emoji="scroll" size="md" />
              <span>Loading {{ totalQuestions }} question explanations...</span>
            </div>

            <div v-else class="review-questions-list">
              <div
                v-for="q in reviewQuestions"
                :key="q.id"
                class="review-item-card"
                :class="{ 'is-correct': q.is_correct, 'is-incorrect': !q.is_correct }"
              >
                <div class="review-header">
                  <span class="q-num-badge">Item {{ q.index }} / {{ totalQuestions }}</span>
                  <span class="result-status-tag" :class="q.is_correct ? 'correct' : 'incorrect'">
                    {{ q.is_correct ? '✓ CORRECT' : '✕ INCORRECT' }}
                  </span>
                  <span class="review-domain-pill">{{ q.domain_name }} • {{ q.part_title }}</span>
                  <button
                    v-if="q.bloom_level"
                    type="button"
                    class="review-bloom-pill interactive-review-bloom"
                    :class="'bloom-' + q.bloom_level"
                    @click="openBloomModal(q.bloom_level)"
                  >
                    🧠 {{ formatBloomLevel(q.bloom_level) }} ℹ️
                  </button>
                  <router-link
                    v-if="q.study_hall_link"
                    :to="q.study_hall_link"
                    class="study-hall-source-link"
                  >
                    <PixelEmoji emoji="book" size="xs" /> Study Hall Source ↗
                  </router-link>
                </div>

                <div class="review-stem">{{ q.question_text }}</div>

                <div class="review-choices-grid">
                  <div
                    v-for="(text, key) in q.choices"
                    :key="key"
                    class="review-choice"
                    :class="{
                      'chosen': q.selected_choice === key,
                      'correct-target': q.correct_choice === key
                    }"
                  >
                    <span class="c-key">{{ key }}.</span>
                    <span class="c-text">{{ text }}</span>
                    <span v-if="q.correct_choice === key" class="badge-correct">CORRECT ANSWER</span>
                    <span v-if="q.selected_choice === key && q.correct_choice !== key" class="badge-wrong">YOUR ANSWER</span>
                  </div>
                </div>

                <!-- Explanation -->
                <div class="review-explanation-box">
                  <div class="exp-title flex items-center gap-1.5">
                    <PixelEmoji emoji="scroll" size="xs" /> STUDY HALL RATIONALE & CRITERIA
                  </div>
                  <div class="exp-text">{{ q.explanation }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Bloom's Taxonomy Cognitive Guide Modal -->
    <Teleport to="body">
      <BloomTaxonomyModal
        v-if="showBloomModal"
        :initial-level="selectedBloomLevel"
        :bloom-summary="bloomSummary"
        @close="closeBloomModal"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import { useTransitionStore } from '../stores/transition'
import { useToastStore } from '../stores/toast'
import { useGameStore } from '../stores/game'
import BloomTaxonomyModal from '../components/special-event/BloomTaxonomyModal.vue'

const route = useRoute()
const router = useRouter()
const transitionStore = useTransitionStore()
const toast = useToastStore()
const gameStore = useGameStore()

const attemptId = ref(Number(route.params.attemptId) || 0)
const loading = ref(true)
const attempt = ref(null)
const evaluation = ref(null)
const rewardsInfo = ref(null)
const bloomData = ref(null)

const showBloomModal = ref(false)
const selectedBloomLevel = ref('overview')

const showAllRecs = ref(false)
const isAdvancedOpen = ref(route.query.tab === 'review')
const advancedSubtab = ref(route.query.tab === 'review' ? 'review' : 'domains')
const loadingReview = ref(false)
const reviewQuestions = ref([])

function openBloomModal(level = 'overview') {
  selectedBloomLevel.value = (level || 'overview').toLowerCase()
  showBloomModal.value = true
}

function closeBloomModal() {
  showBloomModal.value = false
}

function toggleAdvanced() {
  isAdvancedOpen.value = !isAdvancedOpen.value
}

const isPassed = computed(() => (attempt.value?.percentage || 0) >= 75)
const totalQuestions = computed(() => reviewQuestions.value?.length || evaluation.value?.total_questions || attempt.value?.question_count || (attempt.value?.session_number === 2 ? 250 : 200))
const subjectStats = computed(() => evaluation.value?.subject_stats ? Object.values(evaluation.value.subject_stats) : [])

const domainStats = computed(() => evaluation.value?.domains || [])
const difficultyStats = computed(() => evaluation.value?.difficulties || [])
const typeStats = computed(() => evaluation.value?.question_types || [])

const bloomSummary = computed(() => {
  if (bloomData.value?.bloom_summary?.length) {
    return bloomData.value.bloom_summary
  }
  return fallbackBloomSummary.value
})

const cognitiveProfile = computed(() => {
  if (bloomData.value?.cognitive_profile) {
    return bloomData.value.cognitive_profile
  }
  return fallbackCognitiveProfile.value
})

const primaryWeaknessLevel = computed(() => {
  if (cognitiveProfile.value?.primary_review_focus) {
    return cognitiveProfile.value.primary_review_focus.toLowerCase()
  }
  // Fallback to lowest accuracy level in bloomSummary
  const sorted = [...bloomSummary.value].sort((a, b) => a.accuracy - b.accuracy)
  return sorted[0]?.bloom_level?.toLowerCase() || 'analyze'
})

const secondaryWeaknessLevel = computed(() => {
  if (cognitiveProfile.value?.secondary_review_focus) {
    return cognitiveProfile.value.secondary_review_focus.toLowerCase()
  }
  const sorted = [...bloomSummary.value].sort((a, b) => a.accuracy - b.accuracy)
  return sorted[1]?.bloom_level?.toLowerCase() || ''
})

const briefPerformanceSummary = computed(() => {
  const strong = cognitiveProfile.value?.strongest_areas || []
  const focus = primaryWeaknessLevel.value ? formatBloomLevel(primaryWeaknessLevel.value) : ''

  if (strong.length && focus) {
    return `Your strongest demonstrated performance was in ${strong.join(' and ')}. Your primary review focus is ${focus}.`
  } else if (focus) {
    return `Your current primary review focus is ${focus}. Reviewing corresponding Study Hall topics will strengthen formulation accuracy.`
  }
  return `Your ${totalQuestions.value}-item examination performance has been analyzed and recorded.`
})

const bloomRecommendations = computed(() => {
  if (bloomData.value?.study_hall_recommendations?.length) {
    return bloomData.value.study_hall_recommendations
  }
  return fallbackBloomRecommendations.value
})

const displayedRecommendations = computed(() => {
  if (showAllRecs.value) return bloomRecommendations.value
  return bloomRecommendations.value.slice(0, 3)
})

const topRecommendationLink = computed(() => {
  return bloomRecommendations.value[0]?.study_hall_link || ''
})

const fallbackBloomSummary = computed(() => {
  const levels = ['remember', 'understand', 'apply', 'analyze', 'evaluate', 'create']
  const map = {}
  levels.forEach(l => {
    map[l] = { bloom_level: l, display_name: formatBloomLevel(l), question_count: 0, correct_count: 0, incorrect_count: 0, accuracy: 0, average_response_time: 0 }
  })
  if (!reviewQuestions.value?.length) return Object.values(map)

  reviewQuestions.value.forEach(q => {
    const lvl = (q.bloom_level || 'apply').toLowerCase()
    if (!map[lvl]) {
      map[lvl] = { bloom_level: lvl, display_name: formatBloomLevel(lvl), question_count: 0, correct_count: 0, incorrect_count: 0, accuracy: 0, average_response_time: 0 }
    }
    map[lvl].question_count++
    if (q.is_correct) map[lvl].correct_count++
    else map[lvl].incorrect_count++
  })

  return Object.values(map).map(item => ({
    ...item,
    accuracy: item.question_count > 0 ? Math.round((item.correct_count / item.question_count) * 100) : 0,
    average_response_time: evaluation.value?.average_response_time || 0
  }))
})

const fallbackCognitiveProfile = computed(() => {
  const strongest = []
  const developing = []
  const review = []

  fallbackBloomSummary.value.forEach(lvl => {
    if (lvl.accuracy >= 75) strongest.push(lvl.display_name)
    else if (lvl.accuracy >= 60) developing.push(lvl.display_name)
    else review.push(lvl.display_name)
  })

  return {
    primary_review_focus: review[0] || developing[0] || 'Analyze',
    secondary_review_focus: review[1] || '',
    strongest_areas: strongest,
    developing_areas: developing,
    review_needed_areas: review
  }
})

const fallbackBloomRecommendations = computed(() => {
  const map = {}
  reviewQuestions.value.filter(q => !q.is_correct).forEach(q => {
    const key = q.domain_id + '_' + q.study_part_id
    if (!map[key]) {
      map[key] = {
        domain_id: q.domain_id,
        domain_name: q.domain_name,
        study_part_id: q.study_part_id,
        part_title: q.part_title,
        part_number: q.part_number || 1,
        study_hall_link: q.study_hall_link || `/study/${q.domain_id}/part/${q.part_number || 1}`,
        missed_count: 0,
        bloom_levels: []
      }
    }
    map[key].missed_count++
    const bName = formatBloomLevel(q.bloom_level)
    if (!map[key].bloom_levels.includes(bName)) {
      map[key].bloom_levels.push(bName)
    }
  })
  return Object.values(map).sort((a, b) => b.missed_count - a.missed_count)
})

function formatSeconds(sec) {
  if (!sec || sec < 0) return '00:00:00'
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function getAccuracyClass(pct) {
  if (pct >= 80) return 'text-green'
  if (pct >= 60) return 'text-gold'
  return 'text-purple'
}

function getBarClass(pct) {
  if (pct >= 80) return 'bar-green'
  if (pct >= 60) return 'bar-gold'
  return 'bar-purple'
}

function getBloomStatusLabel(pct) {
  if (pct >= 80) return 'Strong'
  if (pct >= 60) return 'Good'
  if (pct >= 45) return 'Developing'
  return 'Review'
}

function getBloomStatusClass(pct) {
  if (pct >= 80) return 'status-strong'
  if (pct >= 60) return 'status-good'
  if (pct >= 45) return 'status-dev'
  return 'status-review'
}

function formatBloomLevel(b) {
  const map = {
    remember: 'Remember',
    understand: 'Understand',
    apply: 'Apply',
    analyze: 'Analyze',
    evaluate: 'Evaluate',
    create: 'Create'
  }
  return map[b?.toLowerCase()] || (b ? (b.charAt(0).toUpperCase() + b.slice(1).toLowerCase()) : 'Apply')
}

function getBloomOrder(b) {
  const order = { remember: 1, understand: 2, apply: 3, analyze: 4, evaluate: 5, create: 6 }
  return order[b?.toLowerCase()] || 1
}

function getBloomDescriptor(b) {
  const map = {
    remember: 'Retrieval of facts, terminology, criteria durations, and foundational symptoms.',
    understand: 'Interpretation, explanation, and classification of clinical meaning.',
    apply: 'Applying psychiatric principles and diagnostic frameworks to clinical vignette cases.',
    analyze: 'Breaking down multi-factor cases, differential reasoning, and discriminating closely related conditions.',
    evaluate: 'Judging competing diagnostic interpretations and selecting the best defensible clinical conclusion.',
    create: 'Formulating or structuring the most integrated psychiatric care and diagnostic synthesis.'
  }
  return map[b?.toLowerCase()] || 'Cognitive demand under Revised Bloom\'s Taxonomy.'
}

function formatDifficulty(d) {
  const map = {
    moderate: 'Moderate (Tier 1)',
    difficult: 'Difficult (Tier 2)',
    very_difficult: 'Very Difficult (Tier 3)',
    board_style_case: 'Board Case (Tier 4)'
  }
  return map[d] || d
}

function formatQuestionType(t) {
  const map = {
    differential_diagnosis: 'Differential Diagnosis',
    clinical_application: 'Clinical Application',
    best_answer: 'Best Answer',
    case_analysis: 'Case Analysis',
    recall: 'Recall & Diagnostic Criteria',
    recognition: 'Criteria Recognition',
    exception: 'Clinical Exception'
  }
  return map[t] || t
}

async function loadResults() {
  if (!attemptId.value) {
    router.replace('/special-event')
    return
  }

  loading.value = true
  try {
    const [reviewData, bloomRes] = await Promise.all([
      specialEventService.getReview(attemptId.value),
      specialEventService.getBloom(attemptId.value).catch(() => null)
    ])
    if (reviewData.success) {
      attempt.value = reviewData.attempt
      reviewQuestions.value = reviewData.review_questions || []
      evaluation.value = synthesizeEvaluation(reviewQuestions.value, attempt.value)
    }
    if (bloomRes && bloomRes.success) {
      bloomData.value = bloomRes
    }
  } catch (err) {
    toast.error('Failed to load results: ' + (err.response?.data?.message || 'Error'))
    router.replace('/special-event')
  } finally {
    loading.value = false
    nextTick(() => {
      window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
      if (document.documentElement) document.documentElement.scrollTop = 0
      if (document.body) document.body.scrollTop = 0
    })
  }
}

function synthesizeEvaluation(questionsList, attemptData) {
  const domMap = {}
  const diffMap = {}
  const typeMap = {}

  questionsList.forEach(q => {
    if (!domMap[q.domain_id]) {
      domMap[q.domain_id] = { domain_id: q.domain_id, name: q.domain_name, total: 0, correct: 0 }
    }
    domMap[q.domain_id].total++
    if (q.is_correct) domMap[q.domain_id].correct++

    if (!diffMap[q.difficulty]) {
      diffMap[q.difficulty] = { difficulty: q.difficulty, total: 0, correct: 0 }
    }
    diffMap[q.difficulty].total++
    if (q.is_correct) diffMap[q.difficulty].correct++

    if (!typeMap[q.question_type]) {
      typeMap[q.question_type] = { type: q.question_type, total: 0, correct: 0 }
    }
    typeMap[q.question_type].total++
    if (q.is_correct) typeMap[q.question_type].correct++
  })

  const calcPct = items => items.map(item => ({
    ...item,
    percentage: item.total > 0 ? Math.round((item.correct / item.total) * 100) : 0
  }))

  return {
    domains: calcPct(Object.values(domMap)),
    difficulties: calcPct(Object.values(diffMap)),
    question_types: calcPct(Object.values(typeMap)),
    average_response_time: attemptData.total_time > 0 ? Math.round(attemptData.total_time / (questionsList?.length || attemptData.question_count || 200)) : 0
  }
}

function goToCitadel() {
  transitionStore.triggerTravel('/world', router)
}

function goToHistory() {
  router.push('/special-event/history')
}

function goToLanding() {
  router.push('/special-event')
}

onMounted(() => {
  window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
  if (document.documentElement) document.documentElement.scrollTop = 0
  if (document.body) document.body.scrollTop = 0
  loadResults()
})

watch(() => route.params.attemptId, (newId) => {
  if (newId) {
    attemptId.value = Number(newId) || 0
    window.scrollTo({ top: 0, left: 0, behavior: 'instant' })
    loadResults()
  }
})
</script>

<style scoped>
/* ── RESULTS PAGE CONTAINER (MINIMAL, GAMIFIED, SCAN-FRIENDLY) ── */
.special-event-results-page {
  position: relative;
  min-height: 100vh;
  width: 100%;
  color: #f1f5f9;
  background-color: #06040b;
  overflow-x: hidden;
  box-sizing: border-box;
  padding: 1.25rem 1.5rem 5rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

.results-bg-vignette {
  position: fixed;
  inset: 0;
  background-image: url('/assets/game/daily-trial-bg.jpg');
  background-size: cover;
  background-position: center center;
  background-attachment: fixed;
  opacity: 0.25;
  pointer-events: none;
  z-index: 0;
}

.results-viewport {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── TOP NAV & BREADCRUMBS ── */
.results-top-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 42px;
  margin-bottom: 0.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(168, 85, 247, 0.2);
  gap: 1rem;
  width: 100%;
}

.nav-left-spacer {
  width: 175px;
  flex-shrink: 0;
}

.nav-right-spacer {
  width: 115px;
  flex-shrink: 0;
}

.nav-location-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
  background: rgba(22, 14, 38, 0.75);
  border: 1px solid rgba(168, 85, 247, 0.35);
  padding: 0.45rem 1rem;
  border-radius: 9999px;
  font-size: 0.82rem;
  white-space: nowrap;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45);
}

.location-pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #a855f7;
  box-shadow: 0 0 10px #c084fc, 0 0 4px #a855f7;
  flex-shrink: 0;
  animation: pulsePurple 2s infinite ease-in-out;
}

@keyframes pulsePurple {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
    box-shadow: 0 0 10px #c084fc, 0 0 4px #a855f7;
  }
  50% {
    opacity: 0.6;
    transform: scale(0.85);
    box-shadow: 0 0 5px #c084fc, 0 0 2px #a855f7;
  }
}

.crumb-link {
  background: transparent;
  border: none;
  color: #c4b5fd;
  text-decoration: none;
  font-family: inherit;
  font-weight: 600;
  font-size: 0.82rem;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  transition: color 0.15s ease;
}

.crumb-link:hover {
  color: #f3e8ff;
  text-decoration: underline;
}

.location-sep {
  color: #64748b;
  font-size: 0.75rem;
  user-select: none;
}

.location-crumb {
  color: #f8fafc;
  font-weight: 700;
  font-size: 0.82rem;
  line-height: 1;
}

.nav-actions-cluster {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-left: auto;
}

.nav-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.95rem;
  background: rgba(22, 14, 38, 0.75);
  border: 1px solid rgba(168, 85, 247, 0.35);
  border-radius: 8px;
  color: #e2e8f0;
  font-family: inherit;
  font-size: 0.82rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.nav-action-btn:hover {
  background: rgba(45, 24, 82, 0.95);
  border-color: #c084fc;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6), 0 0 16px rgba(168, 85, 247, 0.4);
}

.nav-action-btn:active {
  transform: translateY(0);
}

@media (max-width: 992px) {
  .results-top-nav {
    flex-wrap: wrap;
    gap: 0.75rem;
  }
  .nav-left-spacer {
    width: 140px;
  }
  .nav-right-spacer {
    width: 90px;
  }
}

@media (max-width: 768px) {
  .results-top-nav {
    flex-direction: column;
    align-items: flex-start;
    padding-top: 48px;
  }
  .nav-left-spacer,
  .nav-right-spacer {
    display: none;
  }
  .nav-actions-cluster {
    margin-left: 0;
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}

/* ── GOTHIC PANEL COMMON ── */
.gothic-panel {
  background: rgba(16, 11, 28, 0.88);
  border: 1px solid rgba(168, 85, 247, 0.35);
  border-radius: 10px;
  padding: 1.35rem 1.6rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6);
}

/* ── 1. HEADER SECTION & PRIMARY METRICS ── */
.results-header-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.header-banner-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  border-bottom: 1px solid rgba(168, 85, 247, 0.2);
  padding-bottom: 0.75rem;
}

.header-badge-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.trial-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #c084fc;
  letter-spacing: 1px;
}

.trial-date {
  font-size: 0.8rem;
  color: #94a3b8;
}

.rewards-compact-strip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.reward-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.reward-chip.gold { background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #fde047; }
.reward-chip.amber { background: rgba(245, 158, 11, 0.15); border: 1px solid #f59e0b; color: #fbbf24; }
.reward-chip.purple { background: rgba(168, 85, 247, 0.15); border: 1px solid #a855f7; color: #e9d5ff; }

/* Metrics Grid: 4 clean cards */
.primary-metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

@media (max-width: 1024px) {
  .primary-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .primary-metrics-grid {
    grid-template-columns: 1fr;
  }
}

.metric-card {
  background: rgba(24, 16, 44, 0.65);
  border: 1px solid rgba(168, 85, 247, 0.25);
  border-radius: 8px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.metric-card-header {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.metric-icon {
  font-size: 0.9rem;
}

.metric-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.metric-value-row {
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.val-big {
  font-size: 1.75rem;
  font-weight: 800;
  color: #f8fafc;
  line-height: 1.1;
}

.val-denom {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 600;
}

.metric-sub-status {
  font-size: 0.72rem;
  color: #94a3b8;
}

.status-pass { color: #34d399; font-weight: 600; }
.status-dev { color: #fbbf24; font-weight: 600; }
.status-disq { color: #f87171; font-weight: 700; }

/* ── Disqualification Banner ── */
.disqualified-alert-banner {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.4);
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  flex-wrap: wrap;
}

.disq-banner-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  min-width: 280px;
}

.disq-banner-icon {
  font-size: 2.2rem;
  flex-shrink: 0;
}

.disq-banner-text {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.disq-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #f87171;
  letter-spacing: 0.5px;
}

.disq-heading {
  font-size: 1.15rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.disq-message {
  font-size: 0.85rem;
  color: #cbd5e1;
  margin: 0;
  line-height: 1.45;
}

.disq-banner-right {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  align-items: flex-end;
}

.disq-tag {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

.disq-tag.red {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.35);
}

.disq-tag.dark {
  background: rgba(15, 23, 42, 0.8);
  color: #f87171;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.metric-progress-track {
  width: 100%;
  height: 6px;
  background: rgba(14, 10, 24, 0.8);
  border-radius: 3px;
  overflow: hidden;
  margin-top: 0.2rem;
}

.metric-progress-fill {
  height: 100%;
  transition: width 0.6s ease;
}

.outcomes-stat-row {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.15rem;
}

.outcome-pill {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 0.35rem 0.5rem;
  border-radius: 4px;
  text-align: center;
}

.outcome-pill.correct {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.outcome-pill.correct .num { color: #34d399; font-size: 1.15rem; font-weight: 700; }
.outcome-pill.correct .lbl { color: #6ee7b7; font-size: 0.65rem; }

.outcome-pill.missed {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.outcome-pill.missed .num { color: #f87171; font-size: 1.15rem; font-weight: 700; }
.outcome-pill.missed .lbl { color: #fca5a5; font-size: 0.65rem; }

/* ── 2. OVERALL PERFORMANCE SECTION ── */
.performance-summary-section {
  padding: 1.15rem 1.6rem;
}

.summary-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.summary-lead {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-width: 650px;
}

.summary-eyebrow {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #c084fc;
  letter-spacing: 1px;
}

.summary-text {
  font-size: 0.88rem;
  color: #e2e8f0;
  line-height: 1.45;
  margin: 0;
}

.performance-chips-strip {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.85rem;
}

.chip-group {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.chip-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
}

.status-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
}

.chip-strong { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
.chip-dev { background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308; color: #fde047; }
.chip-review { background: rgba(168, 85, 247, 0.2); border: 1px solid #c084fc; color: #e9d5ff; }

/* ── 3. BLOOM SECTION (2x3 GRID) ── */
.section-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}

.title-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.68rem;
  color: #f1f5f9;
  letter-spacing: 0.5px;
  margin: 0;
}

.bloom-guide-btn {
  background: rgba(18, 12, 32, 0.8);
  border: 1px solid rgba(168, 85, 247, 0.35);
  color: #c4b5fd;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.bloom-guide-btn:hover {
  background: rgba(43, 26, 75, 0.95);
  color: #fff;
  border-color: #c084fc;
}

.bloom-compact-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

@media (max-width: 960px) {
  .bloom-compact-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .bloom-compact-grid {
    grid-template-columns: 1fr;
  }
}

.bloom-compact-card {
  background: rgba(24, 16, 44, 0.65);
  border: 1px solid rgba(168, 85, 247, 0.25);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.bloom-compact-card:hover {
  background: rgba(35, 24, 62, 0.85);
  border-color: #c084fc;
  transform: translateY(-2px);
}

.bloom-card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bloom-name-group {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.bloom-level-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #a855f7;
  background: rgba(168, 85, 247, 0.15);
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
}

.bloom-level-name {
  font-size: 0.85rem;
  font-weight: 700;
  color: #f8fafc;
}

.bloom-acc-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.68rem;
  font-weight: 700;
}

.bloom-mini-track {
  width: 100%;
  height: 5px;
  background: rgba(14, 10, 24, 0.8);
  border-radius: 3px;
  overflow: hidden;
}

.bloom-mini-fill {
  height: 100%;
  transition: width 0.5s ease;
}

.bloom-card-bot-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.72rem;
}

.bloom-status-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
}

.status-strong { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.status-good { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.status-dev { background: rgba(234, 179, 8, 0.15); color: #fde047; }
.status-review { background: rgba(168, 85, 247, 0.2); color: #c084fc; }

.bloom-counts-text {
  color: #64748b;
  font-size: 0.7rem;
}

/* ── 4. PRIMARY REVIEW FOCUS ── */
.primary-focus-section {
  background: linear-gradient(135deg, rgba(30, 20, 56, 0.85) 0%, rgba(14, 10, 26, 0.9) 100%);
  border: 1px solid rgba(168, 85, 247, 0.45);
}

.focus-layout {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.focus-icon-box {
  width: 58px;
  height: 58px;
  border-radius: 12px;
  background: rgba(147, 51, 234, 0.2);
  border: 1px solid rgba(192, 132, 252, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.focus-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.focus-tag-strip {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.focus-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #fde047;
  background: rgba(234, 179, 8, 0.15);
  border: 1px solid rgba(234, 179, 8, 0.3);
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
}

.focus-sub-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #94a3b8;
}

.focus-heading {
  font-family: 'Cinzel', serif;
  font-size: 1.6rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 1.5px;
  margin: 0;
  line-height: 1.1;
}

.focus-desc {
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.45;
  margin: 0;
  max-width: 780px;
}

.primary-study-btn {
  background: linear-gradient(135deg, #7e22ce 0%, #4338ca 100%);
  border: 1px solid #c084fc;
  color: #fff;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.68rem;
  padding: 0.85rem 1.25rem;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
  text-decoration: none;
  box-shadow: 0 0 15px rgba(126, 34, 206, 0.5);
  transition: all 0.2s;
}

.primary-study-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(168, 85, 247, 0.8);
}

/* ── 5. RECOMMENDATIONS SECTION ── */
.recs-count-tag {
  font-size: 0.72rem;
  color: #64748b;
  margin-left: 0.35rem;
}

.toggle-all-recs-btn {
  background: transparent;
  border: 1px solid rgba(168, 85, 247, 0.3);
  color: #c4b5fd;
  font-size: 0.74rem;
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.2s;
}

.toggle-all-recs-btn:hover {
  background: rgba(43, 26, 75, 0.8);
  color: #fff;
}

.recs-compact-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

@media (max-width: 1024px) {
  .recs-compact-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .recs-compact-grid {
    grid-template-columns: 1fr;
  }
}

.rec-compact-card {
  background: rgba(24, 16, 44, 0.65);
  border: 1px solid rgba(168, 85, 247, 0.25);
  border-radius: 8px;
  padding: 0.9rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.85rem;
  transition: all 0.2s;
  min-width: 0;
  overflow: hidden;
}

.rec-compact-card:hover {
  background: rgba(35, 24, 62, 0.85);
  border-color: #c084fc;
}

.rec-card-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
  flex: 1;
  overflow: hidden;
}

.rec-domain-label {
  font-size: 0.68rem;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rec-topic-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rec-tags-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.15rem;
  min-width: 0;
}

.rec-missed-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #f87171;
  background: rgba(239, 68, 68, 0.12);
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  white-space: nowrap;
}

.rec-bloom-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #c084fc;
  background: rgba(168, 85, 247, 0.15);
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  white-space: nowrap;
}

.rec-bloom-more {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #e9d5ff;
  background: rgba(168, 85, 247, 0.25);
  border: 1px solid rgba(168, 85, 247, 0.4);
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  white-space: nowrap;
  cursor: help;
}

.rec-study-link {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.3);
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.2s;
  flex-shrink: 0;
  align-self: center;
}

.rec-study-link:hover {
  background: rgba(56, 189, 248, 0.25);
  color: #fff;
  border-color: #38bdf8;
}

/* ── 6. ADVANCED ANALYTICS SECTION (ACCORDION) ── */
.clickable-accordion-header {
  cursor: pointer;
  margin-bottom: 0;
  user-select: none;
}

.accordion-toggle-btn {
  background: transparent;
  border: none;
  color: #c4b5fd;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.advanced-panels-container {
  margin-top: 1rem;
  border-top: 1px solid rgba(168, 85, 247, 0.25);
  padding-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.advanced-subtabs-bar {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.subtab-btn {
  background: rgba(18, 12, 32, 0.7);
  border: 1px solid rgba(168, 85, 247, 0.3);
  color: #94a3b8;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.subtab-btn.active {
  background: #7e22ce;
  border-color: #c084fc;
  color: #fff;
  font-weight: 600;
}

.subpanel-content {
  background: rgba(14, 10, 24, 0.6);
  border: 1px solid rgba(168, 85, 247, 0.2);
  border-radius: 8px;
  padding: 1rem;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.breakdown-row {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.breakdown-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.breakdown-name {
  color: #e2e8f0;
  font-weight: 600;
}

.breakdown-counts {
  color: #94a3b8;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
}

.breakdown-track {
  width: 100%;
  height: 6px;
  background: rgba(24, 16, 44, 0.8);
  border-radius: 3px;
  overflow: hidden;
}

.breakdown-fill {
  height: 100%;
  transition: width 0.5s ease;
}

/* ── REVIEW LIST (INSIDE ADVANCED ACCORDION) ── */
.review-subpanel {
  max-height: 700px;
  overflow-y: auto;
}

.review-questions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.review-item-card {
  background: rgba(18, 12, 34, 0.8);
  border: 1px solid rgba(168, 85, 247, 0.3);
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.review-item-card.is-correct {
  border-left: 4px solid #10b981;
}

.review-item-card.is-incorrect {
  border-left: 4px solid #ef4444;
}

.review-header {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.q-num-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #94a3b8;
}

.result-status-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  padding: 0.2rem 0.4rem;
  border-radius: 3px;
}

.result-status-tag.correct { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.result-status-tag.incorrect { background: rgba(239, 68, 68, 0.2); color: #f87171; }

.review-domain-pill {
  font-size: 0.74rem;
  color: #cbd5e1;
}

.review-bloom-pill {
  background: rgba(168, 85, 247, 0.2);
  border: 1px solid rgba(168, 85, 247, 0.4);
  color: #e9d5ff;
  font-size: 0.72rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
}

.study-hall-source-link {
  font-size: 0.72rem;
  color: #38bdf8;
  text-decoration: none;
  margin-left: auto;
}

.review-stem {
  font-size: 0.88rem;
  color: #f8fafc;
  line-height: 1.5;
}

.review-choices-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.4rem;
}

.review-choice {
  background: rgba(24, 16, 44, 0.5);
  border: 1px solid rgba(168, 85, 247, 0.2);
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
}

.review-choice.correct-target {
  background: rgba(16, 185, 129, 0.15);
  border-color: #10b981;
}

.review-choice.chosen:not(.correct-target) {
  background: rgba(239, 68, 68, 0.15);
  border-color: #ef4444;
}

.badge-correct {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #34d399;
}

.badge-wrong {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #f87171;
}

.review-explanation-box {
  background: rgba(12, 8, 20, 0.85);
  border: 1px dashed rgba(168, 85, 247, 0.3);
  border-radius: 6px;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.exp-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #fde047;
}

.exp-text {
  font-size: 0.8rem;
  color: #cbd5e1;
  line-height: 1.5;
}

/* ── COLOR UTILITIES ── */
.text-green { color: #34d399 !important; }
.text-gold { color: #fde047 !important; }
.text-purple { color: #c084fc !important; }

.bar-green { background: #10b981 !important; }
.bar-gold { background: #f59e0b !important; }
.bar-purple { background: #a855f7 !important; }

/* ── LOADER & ANIMATION ── */
.results-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 1rem;
  text-align: center;
  color: #c4b5fd;
}

.results-loader h2 {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.9rem;
  letter-spacing: 1px;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ── SUBJECT STATS GRID ── */
.subject-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.subject-stat-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subj-card-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
}

.subj-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: #f1f5f9;
}

.subj-acc-badge {
  font-size: 0.95rem;
  font-weight: 700;
}

.subj-score-row {
  font-size: 0.78rem;
  color: #94a3b8;
}

/* ── RESPONSIVE ── */
@media (max-width: 1050px) {
  .results-top-nav {
    flex-wrap: wrap;
    padding-top: 44px;
  }
  .nav-left-spacer, .nav-right-spacer {
    display: none;
  }
  .primary-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .bloom-compact-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .recs-compact-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .results-viewport {
    width: 95%;
  }
  .primary-metrics-grid {
    grid-template-columns: 1fr;
  }
  .bloom-compact-grid {
    grid-template-columns: 1fr;
  }
  .recs-compact-grid {
    grid-template-columns: 1fr;
  }
  .focus-layout {
    flex-direction: column;
    text-align: center;
  }
  .focus-action {
    width: 100%;
  }
  .primary-study-btn {
    width: 100%;
    justify-content: center;
  }
  .summary-content {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — SPECIAL EVENT RESULTS
   ══════════════════════════════════════════════════════════════ */
.special-event-results-page.theme-morning,
:global(.theme-morning) .special-event-results-page,
:global([data-theme="morning"]) .special-event-results-page {
  background-color: #F8F4EC !important;
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .results-bg-vignette,
:global(.theme-morning) .special-event-results-page .results-bg-vignette,
:global([data-theme="morning"]) .special-event-results-page .results-bg-vignette {
  background: radial-gradient(circle at 50% 8%, rgba(217, 163, 76, 0.18) 0%, rgba(248, 244, 236, 0.96) 75%) !important;
}

.special-event-results-page.theme-morning .results-top-nav,
:global(.theme-morning) .special-event-results-page .results-top-nav,
:global([data-theme="morning"]) .special-event-results-page .results-top-nav {
  border-bottom-color: rgba(184, 138, 66, 0.25) !important;
}

.special-event-results-page.theme-morning .nav-location-badge,
:global(.theme-morning) .special-event-results-page .nav-location-badge,
:global([data-theme="morning"]) .special-event-results-page .nav-location-badge {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  box-shadow: 0 2px 10px rgba(143, 100, 45, 0.12) !important;
}

.special-event-results-page.theme-morning .location-pulse-dot,
:global(.theme-morning) .special-event-results-page .location-pulse-dot,
:global([data-theme="morning"]) .special-event-results-page .location-pulse-dot {
  background: #d97706 !important;
  box-shadow: 0 0 8px #d97706 !important;
}

.special-event-results-page.theme-morning .crumb-link,
:global(.theme-morning) .special-event-results-page .crumb-link,
:global([data-theme="morning"]) .special-event-results-page .crumb-link {
  color: #8C6A3E !important;
}

.special-event-results-page.theme-morning .location-crumb,
:global(.theme-morning) .special-event-results-page .location-crumb,
:global([data-theme="morning"]) .special-event-results-page .location-crumb {
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .nav-action-btn,
:global(.theme-morning) .special-event-results-page .nav-action-btn,
:global([data-theme="morning"]) .special-event-results-page .nav-action-btn {
  background: #FFF8EB !important;
  border-color: #D8C39E !important;
  color: #4A3525 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

.special-event-results-page.theme-morning .nav-action-btn:hover,
:global(.theme-morning) .special-event-results-page .nav-action-btn:hover,
:global([data-theme="morning"]) .special-event-results-page .nav-action-btn:hover {
  background: #FFF2D6 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .gothic-panel,
:global(.theme-morning) .special-event-results-page .gothic-panel,
:global([data-theme="morning"]) .special-event-results-page .gothic-panel {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow: 0 6px 20px rgba(143, 100, 45, 0.1) !important;
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .metric-card,
:global(.theme-morning) .special-event-results-page .metric-card,
:global([data-theme="morning"]) .special-event-results-page .metric-card {
  background: #FAF3E5 !important;
  border: 1px solid #D9C39E !important;
}

.special-event-results-page.theme-morning .metric-label,
:global(.theme-morning) .special-event-results-page .metric-label,
:global([data-theme="morning"]) .special-event-results-page .metric-label {
  color: #7A5F43 !important;
}

.special-event-results-page.theme-morning .metric-val,
:global(.theme-morning) .special-event-results-page .metric-val,
:global([data-theme="morning"]) .special-event-results-page .metric-val {
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .review-item-card,
:global(.theme-morning) .special-event-results-page .review-item-card,
:global([data-theme="morning"]) .special-event-results-page .review-item-card {
  background: #FFFDF9 !important;
  border: 1.5px solid #D9C39E !important;
}

.special-event-results-page.theme-morning .review-stem,
:global(.theme-morning) .special-event-results-page .review-stem,
:global([data-theme="morning"]) .special-event-results-page .review-stem {
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .review-choice,
:global(.theme-morning) .special-event-results-page .review-choice,
:global([data-theme="morning"]) .special-event-results-page .review-choice {
  background: #FAF3E5 !important;
  border: 1px solid #D9C39E !important;
  color: #2C1E14 !important;
}

.special-event-results-page.theme-morning .review-choice.correct-target,
:global(.theme-morning) .special-event-results-page .review-choice.correct-target,
:global([data-theme="morning"]) .special-event-results-page .review-choice.correct-target {
  background: #DCFCE7 !important;
  border-color: #86EFAC !important;
  color: #166534 !important;
}

.special-event-results-page.theme-morning .q-num-badge,
:global(.theme-morning) .special-event-results-page .q-num-badge,
:global([data-theme="morning"]) .special-event-results-page .q-num-badge {
  background: #F3E7CF !important;
  color: #7A5F43 !important;
}
</style>
