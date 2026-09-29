<template>
  <div class="special-event-results-page">
    <div class="results-bg-vignette"></div>

    <div v-if="loading" class="results-loader">
      <PixelEmoji emoji="crystal" size="game" />
      <h2>CALCULATING TRIAL EVALUATION</h2>
      <p>Consulting authoritative diagnostic archives...</p>
    </div>

    <div v-else-if="attempt" class="results-container animate-fade-in">
      <!-- Breadcrumb & Nav -->
      <div class="results-nav-bar">
        <button class="rpg-nav-back-btn" @click="goToCitadel">
          <span>◀ Citadel Hub</span>
        </button>
        <div class="results-nav-actions">
          <button class="nav-link-btn" @click="goToHistory">
            <PixelEmoji emoji="book" size="xs" /> Daily History
          </button>
          <button class="nav-link-btn" @click="goToLanding">
            <PixelEmoji emoji="shield" size="xs" /> Trial Gate
          </button>
        </div>
      </div>

      <!-- Hero Victory / Evaluation Banner -->
      <div class="results-hero gothic-panel">
        <div class="hero-crest">
          <PixelEmoji :emoji="isPassed ? 'trophy' : 'scroll'" size="game" />
        </div>
        <div class="hero-meta">
          <span class="trial-date-tag">{{ attempt.event_date }} • THE DAILY TRIAL</span>
          <h1 class="hero-title">{{ isPassed ? 'TRIAL SEALED WITH HONORS' : 'TRIAL EVALUATION CONCLUDED' }}</h1>
          <p class="hero-desc">
            Your answers across all 140 psychiatric examination items have been recorded into the Grand Archives.
          </p>
        </div>

        <!-- Rewards Awarded Pill -->
        <div v-if="rewardsInfo" class="rewards-awarded-pill">
          <div class="reward-item flex items-center gap-1.5">
            <PixelEmoji emoji="star" size="sm" />
            <span>+{{ rewardsInfo.xp_gained || attempt.xp_awarded }} XP</span>
          </div>
          <div class="reward-item flex items-center gap-1.5">
            <PixelEmoji emoji="coin" size="sm" />
            <span>+{{ rewardsInfo.coins_gained || attempt.coins_awarded }} Coins</span>
          </div>
          <div v-if="rewardsInfo.is_perfect" class="reward-item perfect flex items-center gap-1.5">
            <PixelEmoji emoji="crown" size="sm" />
            <span>PERFECT TRIAL 140/140</span>
          </div>
        </div>
      </div>

      <!-- Key Scoreboard Metrics -->
      <div class="scoreboard-grid">
        <div class="score-card primary">
          <span class="score-label">FINAL SCORE</span>
          <div class="score-main-val">{{ attempt.score }} <span class="denom">/ 140</span></div>
          <div class="score-progress-bar">
            <div class="bar-fill" :style="{ width: attempt.percentage + '%' }"></div>
          </div>
        </div>

        <div class="score-card">
          <span class="score-label">MASTERY ACCURACY</span>
          <div class="score-main-val pct" :class="getAccuracyClass(attempt.percentage)">
            {{ attempt.percentage }}%
          </div>
          <span class="score-subtext">{{ isPassed ? 'Meets Board Benchmark' : 'Below Passing Target' }}</span>
        </div>

        <div class="score-card">
          <span class="score-label">DIAGNOSTIC OUTCOMES</span>
          <div class="dual-stat-row">
            <div class="stat-col correct">
              <span class="val">{{ attempt.correct_count }}</span>
              <span class="lbl">Correct</span>
            </div>
            <div class="stat-col incorrect">
              <span class="val">{{ attempt.incorrect_count }}</span>
              <span class="lbl">Incorrect</span>
            </div>
          </div>
        </div>

        <div class="score-card">
          <span class="score-label">EXAMINATION TIME</span>
          <div class="score-main-val time">{{ formatSeconds(attempt.total_time) }}</div>
          <span class="score-subtext">Avg {{ evaluation?.average_response_time || 0 }}s / item</span>
        </div>
      </div>

      <!-- Analytics & Review Tabs -->
      <div class="results-analytics-section gothic-panel">
        <div class="analytics-tabs-header">
          <button
            class="tab-btn bloom-tab-btn"
            :class="{ active: activeTab === 'bloom' }"
            @click="activeTab = 'bloom'"
          >
            <PixelEmoji emoji="crystal" size="xs" /> Cognitive Performance
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'domains' }"
            @click="activeTab = 'domains'"
          >
            <PixelEmoji emoji="book" size="xs" /> Domain Performance
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'difficulty' }"
            @click="activeTab = 'difficulty'"
          >
            <PixelEmoji emoji="target" size="xs" /> Difficulty Breakdown
          </button>
          <button
            class="tab-btn"
            :class="{ active: activeTab === 'types' }"
            @click="activeTab = 'types'"
          >
            <PixelEmoji emoji="shield" size="xs" /> Question Types
          </button>
          <button
            class="tab-btn review-tab-btn"
            :class="{ active: activeTab === 'review' }"
            @click="loadReviewTab"
          >
            <PixelEmoji emoji="scroll" size="xs" /> Full Item Review (140)
          </button>
        </div>

        <!-- Tab 0: Cognitive Performance (Revised Bloom's Taxonomy) -->
        <div v-if="activeTab === 'bloom'" class="tab-pane bloom-pane">
          <div class="section-lead-row">
            <div>
              <h3 class="pane-title">COGNITIVE PERFORMANCE</h3>
              <p class="pane-desc">Proficiency evaluation across the six Revised Bloom's Taxonomy cognitive processes.</p>
            </div>
            <div class="lead-actions-row">
              <button
                type="button"
                class="btn-section-bloom-guide"
                @click="openBloomModal('overview')"
                title="Learn how Bloom's Taxonomy classifies cognitive reasoning"
              >
                📖 Bloom's Taxonomy Guide
              </button>
              <div class="bloom-assessment-tag">
                <PixelEmoji emoji="shield" size="xs" /> Current Assessment Only
              </div>
            </div>
          </div>

          <!-- Personal Cognitive Profile (Section 54 & 51) -->
          <div class="cognitive-profile-panel gothic-subpanel">
            <div class="profile-header">
              <div class="profile-title-area">
                <span class="profile-eyebrow">DIAGNOSTIC REASONING PROFILE</span>
                <h4 class="profile-title">YOUR COGNITIVE PROFILE</h4>
                <p class="profile-disclaimer">
                  *Describes demonstrated performance on this assessment only. Does not measure general intelligence or permanent ability.
                </p>
              </div>
              <button
                type="button"
                class="btn-bloom-header-guide"
                @click="openBloomModal('overview')"
                title="Open comprehensive Bloom's Taxonomy cognitive guide"
              >
                <PixelEmoji emoji="crystal" size="xs" />
                <span class="guide-text">What is Bloom's Taxonomy?</span>
                <span class="guide-badge">Click for Definitions ℹ️</span>
              </button>
            </div>

            <div class="profile-trio-grid">
              <div class="profile-tier-card strong">
                <div class="tier-label">
                  <span class="tier-icon">★</span>
                  <span>Strongest Demonstrated</span>
                </div>
                <div class="tier-badges">
                  <button
                    v-for="area in (cognitiveProfile.strongest_areas || [])"
                    :key="area"
                    type="button"
                    class="tier-pill interactive-tier-btn strong"
                    @click="openBloomModal(area)"
                    :title="`Click to explore Bloom's ${area} definition & DSM-5 examples`"
                  >
                    <span class="pill-name">{{ area }}</span>
                    <span class="pill-info-badge">ℹ️</span>
                  </button>
                  <span v-if="!cognitiveProfile.strongest_areas?.length" class="empty-tier-text">None identified</span>
                </div>
              </div>

              <div class="profile-tier-card developing">
                <div class="tier-label">
                  <span class="tier-icon">◆</span>
                  <span>Developing Performance</span>
                </div>
                <div class="tier-badges">
                  <button
                    v-for="area in (cognitiveProfile.developing_areas || [])"
                    :key="area"
                    type="button"
                    class="tier-pill interactive-tier-btn developing"
                    @click="openBloomModal(area)"
                    :title="`Click to explore Bloom's ${area} definition & DSM-5 examples`"
                  >
                    <span class="pill-name">{{ area }}</span>
                    <span class="pill-info-badge">ℹ️</span>
                  </button>
                  <span v-if="!cognitiveProfile.developing_areas?.length" class="empty-tier-text">None identified</span>
                </div>
              </div>

              <div class="profile-tier-card review">
                <div class="tier-label">
                  <span class="tier-icon">▲</span>
                  <span>Needs More Review</span>
                </div>
                <div class="tier-badges">
                  <button
                    v-for="area in (cognitiveProfile.review_needed_areas || [])"
                    :key="area"
                    type="button"
                    class="tier-pill interactive-tier-btn review"
                    @click="openBloomModal(area)"
                    :title="`Click to explore Bloom's ${area} definition & DSM-5 examples`"
                  >
                    <span class="pill-name">{{ area }}</span>
                    <span class="pill-info-badge">ℹ️</span>
                  </button>
                  <span v-if="!cognitiveProfile.review_needed_areas?.length" class="empty-tier-text">None identified</span>
                </div>
              </div>
            </div>

            <!-- Academic Interpretation Statement (Section 51) -->
            <div class="academic-interpretation-box">
              <div class="interp-heading">
                <PixelEmoji emoji="scroll" size="xs" /> Assessment Performance Interpretation
              </div>
              <p class="interp-body">{{ cognitiveProfile.interpretation }}</p>
            </div>

            <!-- Identified Cognitive Patterns (Section 56) -->
            <div v-if="cognitiveProfile.patterns?.length" class="cognitive-patterns-row">
              <span class="patterns-label">Assessment Pattern:</span>
              <span v-for="(pat, pIdx) in cognitiveProfile.patterns" :key="pIdx" class="pattern-item">
                “{{ pat }}”
              </span>
            </div>
          </div>

          <!-- Bloom 6-Level Progress Dashboard (Section 50 & 53) -->
          <div class="bloom-levels-dashboard">
            <div class="levels-dashboard-header">
              <div>
                <h4 class="levels-dashboard-heading">COGNITIVE LEVEL BREAKDOWN</h4>
                <p class="levels-dashboard-sub">Click any cognitive level below to view clinical definitions, action verbs, and DSM-5 vignette examples.</p>
              </div>
              <button
                type="button"
                class="btn-levels-quick-guide"
                @click="openBloomModal('overview')"
              >
                <span>Full Taxonomy Ladder</span> ↗
              </button>
            </div>

            <div class="bloom-grid">
              <div
                v-for="lvl in bloomSummary"
                :key="lvl.bloom_level"
                class="bloom-card gothic-subpanel clickable-bloom-card"
                :class="'level-' + lvl.bloom_level"
                @click="openBloomModal(lvl.bloom_level)"
                :title="`Click to inspect ${lvl.display_name || formatBloomLevel(lvl.bloom_level)} definitions & strategies`"
              >
                <div class="bloom-card-top">
                  <div class="bloom-title-col">
                    <span class="bloom-order-badge">Level {{ getBloomOrder(lvl.bloom_level) }}</span>
                    <h5 class="bloom-card-name">{{ lvl.display_name || formatBloomLevel(lvl.bloom_level) }}</h5>
                  </div>
                  <div class="bloom-card-actions">
                    <div class="bloom-accuracy-badge" :class="getAccuracyClass(lvl.accuracy)">
                      {{ lvl.accuracy }}%
                    </div>
                    <span class="card-inspect-pill">
                      Define ℹ️
                    </span>
                  </div>
                </div>

                <p class="bloom-skill-desc">
                  {{ getBloomDescriptor(lvl.bloom_level) }}
                </p>

                <!-- Retro RPG Pixel Progress Bar -->
                <div class="bloom-track-container">
                  <div class="bloom-track">
                    <div
                      class="bloom-fill"
                      :style="{ width: lvl.accuracy + '%' }"
                      :class="getBarClass(lvl.accuracy)"
                    ></div>
                  </div>
                </div>

                <!-- Stats Counters -->
                <div class="bloom-stats-row">
                  <div class="stat-cell">
                    <span class="stat-num">{{ lvl.question_count }}</span>
                    <span class="stat-tag">Items</span>
                  </div>
                  <div class="stat-cell correct">
                    <span class="stat-num">{{ lvl.correct_count }}</span>
                    <span class="stat-tag">Correct</span>
                  </div>
                  <div class="stat-cell incorrect">
                    <span class="stat-num">{{ lvl.incorrect_count }}</span>
                    <span class="stat-tag">Missed</span>
                  </div>
                  <div class="stat-cell time">
                    <span class="stat-num">{{ lvl.average_response_time }}s</span>
                    <span class="stat-tag">Avg Time</span>
                  </div>
                </div>

                <div class="bloom-card-footer-prompt">
                  <span class="prompt-text">View definition & DSM-5 example</span>
                  <span class="prompt-arrow">➔</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bloom + Study Hall Targeted Recommendations (Section 55) -->
          <div v-if="bloomRecommendations.length > 0" class="bloom-study-recommendations gothic-subpanel">
            <div class="recs-header">
              <div class="recs-title-col">
                <h4 class="recs-title">STUDY HALL REVIEW RECOMMENDATIONS</h4>
                <p class="recs-subtitle">
                  Direct academic links based on missed questions from your assessment performance.
                </p>
              </div>
            </div>

            <div class="recs-cards-grid">
              <div
                v-for="rec in bloomRecommendations"
                :key="rec.domain_id + '_' + rec.study_part_id"
                class="rec-card"
              >
                <div class="rec-card-main">
                  <span class="rec-domain-tag">{{ rec.domain_name }}</span>
                  <h5 class="rec-part-title">Part {{ rec.part_number }}: {{ rec.part_title }}</h5>
                  <div class="rec-meta-row">
                    <span class="rec-missed-pill">{{ rec.missed_count }} missed {{ rec.missed_count === 1 ? 'item' : 'items' }}</span>
                    <button
                      v-for="b in rec.bloom_levels"
                      :key="b"
                      type="button"
                      class="rec-bloom-pill interactive-rec-bloom"
                      @click="openBloomModal(b)"
                      :title="`Click to view Bloom's ${b} definition`"
                    >
                      {{ b }} ℹ️
                    </button>
                  </div>
                </div>
                <router-link :to="rec.study_hall_link" class="btn-review-study-hall">
                  Review in Study Hall ↗
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 1: Domain Performance -->
        <div v-if="activeTab === 'domains'" class="tab-pane">
          <div class="section-lead-row">
            <div>
              <h3 class="pane-title">Performance Across Psychiatric Domains</h3>
              <p class="pane-desc">Accuracy breakdown by DSM-5 diagnostic category.</p>
            </div>
            <div v-if="weakestDomains.length > 0" class="weak-domains-badge">
              <PixelEmoji emoji="sparkles" size="xs" /> Recommended Review:
              <strong>{{ weakestDomains.map(d => d.name).join(', ') }}</strong>
            </div>
          </div>

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

        <!-- Tab 2: Difficulty Breakdown -->
        <div v-if="activeTab === 'difficulty'" class="tab-pane">
          <h3 class="pane-title">Performance by Cognitive Difficulty Tier</h3>
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

        <!-- Tab 3: Question Types -->
        <div v-if="activeTab === 'types'" class="tab-pane">
          <h3 class="pane-title">Performance by Question Reasoning Style</h3>
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

        <!-- Tab 4: Full Question-by-Question Review -->
        <div v-if="activeTab === 'review'" class="tab-pane">
          <div v-if="loadingReview" class="review-loading">
            <PixelEmoji emoji="scroll" size="md" />
            <span>Loading 140 question explanations...</span>
          </div>

          <div v-else class="review-questions-list">
            <div
              v-for="q in reviewQuestions"
              :key="q.id"
              class="review-item-card"
              :class="{ 'is-correct': q.is_correct, 'is-incorrect': !q.is_correct }"
            >
              <div class="review-header">
                <span class="q-num-badge">Item {{ q.index }} / 140</span>
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
                  :title="`Click to view what ${formatBloomLevel(q.bloom_level)} means`"
                >
                  🧠 {{ formatBloomLevel(q.bloom_level) }} <span class="bloom-info-icon">ℹ️</span>
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
                  <PixelEmoji emoji="scroll" size="xs" /> DIAGNOSTIC RATIONALE & STUDY HALL CRITERIA
                </div>
                <div class="exp-text">{{ q.explanation }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import { useTransitionStore } from '../stores/transition'
import { useToastStore } from '../stores/toast'
import BloomTaxonomyModal from '../components/special-event/BloomTaxonomyModal.vue'

const route = useRoute()
const router = useRouter()
const transitionStore = useTransitionStore()
const toast = useToastStore()

const attemptId = ref(Number(route.params.attemptId) || 0)
const loading = ref(true)
const attempt = ref(null)

const showBloomModal = ref(false)
const selectedBloomLevel = ref('overview')

function openBloomModal(level = 'overview') {
  selectedBloomLevel.value = (level || 'overview').toLowerCase()
  showBloomModal.value = true
}

function closeBloomModal() {
  showBloomModal.value = false
}
const evaluation = ref(null)
const rewardsInfo = ref(null)
const activeTab = ref(route.query.tab ? route.query.tab : 'bloom')

const loadingReview = ref(false)
const reviewQuestions = ref([])
const bloomData = ref(null)

const isPassed = computed(() => (attempt.value?.percentage || 0) >= 75)

const domainStats = computed(() => evaluation.value?.domains || [])
const difficultyStats = computed(() => evaluation.value?.difficulties || [])
const typeStats = computed(() => evaluation.value?.question_types || [])
const weakestDomains = computed(() => evaluation.value?.weakest_domains || [])

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

const bloomRecommendations = computed(() => {
  if (bloomData.value?.study_hall_recommendations?.length) {
    return bloomData.value.study_hall_recommendations
  }
  return fallbackBloomRecommendations.value
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
    if (lvl.accuracy >= 80) strongest.push(lvl.display_name)
    else if (lvl.accuracy >= 60) developing.push(lvl.display_name)
    else review.push(lvl.display_name)
  })

  const parts = []
  if (strongest.length) parts.push(`Your strongest demonstrated performance in today's assessment was in ${strongest.join(' and ')} questions.`)
  if (developing.length) parts.push(`Your performance in ${developing.join(' and ')} was developing.`)
  if (review.length) parts.push(`Questions requiring ${review.join(' and ')} produced more errors, suggesting that reviewing related Study Hall topics may help strengthen higher-order clinical reasoning.`)
  else parts.push('All cognitive domains demonstrated solid proficiency on this assessment.')

  return {
    strongest_areas: strongest,
    developing_areas: developing,
    review_needed_areas: review,
    interpretation: parts.join(' '),
    patterns: ['Balanced cognitive performance across foundational and higher-order assessment questions.']
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
  return Object.values(map).sort((a, b) => b.missed_count - a.missed_count).slice(0, 8)
})

function formatSeconds(sec) {
  if (!sec || sec < 0) return '00:00:00'
  const h = Math.floor(sec / 3600)
  const m = Math.floor((sec % 3600) / 60)
  const s = sec % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function getAccuracyClass(pct) {
  if (pct >= 85) return 'high'
  if (pct >= 75) return 'pass'
  return 'low'
}

function getBarClass(pct) {
  if (pct >= 80) return 'bar-high'
  if (pct >= 60) return 'bar-mid'
  return 'bar-low'
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
    remember: 'Retrieval of previously learned facts, terminology, classifications, definitions, symptoms, or directly stated criteria.',
    understand: 'Interpretation, explanation, classification, comparison, summarization, or recognition of clinical meaning.',
    apply: 'Using Study Hall psychiatric knowledge directly in practical or clinical vignette situations.',
    analyze: 'Breaking down cases, distinguishing relationships, comparing competing diagnostic possibilities, and isolating core features.',
    evaluate: 'Judging competing clinical alternatives, selecting best-supported diagnostic conclusions from multi-factor evidence.',
    create: 'Determining or constructing the most appropriate clinical solution, intervention plan, or management strategy.'
  }
  return map[b?.toLowerCase()] || 'Cognitive reasoning under Revised Bloom\'s Taxonomy.'
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
  }
}

function synthesizeEvaluation(questionsList, attemptData) {
  const domMap = {}
  const diffMap = {}
  const typeMap = {}

  questionsList.forEach(q => {
    // Domains
    if (!domMap[q.domain_id]) {
      domMap[q.domain_id] = { domain_id: q.domain_id, name: q.domain_name, total: 0, correct: 0 }
    }
    domMap[q.domain_id].total++
    if (q.is_correct) domMap[q.domain_id].correct++

    // Difficulties
    if (!diffMap[q.difficulty]) {
      diffMap[q.difficulty] = { difficulty: q.difficulty, total: 0, correct: 0 }
    }
    diffMap[q.difficulty].total++
    if (q.is_correct) diffMap[q.difficulty].correct++

    // Types
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

  const domains = calcPct(Object.values(domMap))
  const difficulties = calcPct(Object.values(diffMap))
  const types = calcPct(Object.values(typeMap))

  const weakest = [...domains].sort((a, b) => a.percentage - b.percentage).slice(0, 3)

  return {
    domains,
    difficulties,
    question_types: types,
    weakest_domains: weakest,
    average_response_time: attemptData.total_time > 0 ? Math.round(attemptData.total_time / 140) : 0
  }
}

async function loadReviewTab() {
  activeTab.value = 'review'
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
  loadResults()
})
</script>

<style scoped>
.special-event-results-page {
  position: relative;
  min-height: 100vh;
  background: #080614;
  color: #f1f5f9;
  padding: 1.5rem 1rem 4rem;
}

.results-bg-vignette {
  position: fixed;
  inset: 0;
  background: radial-gradient(ellipse at 50% 20%, rgba(79, 70, 229, 0.15) 0%, rgba(8, 6, 20, 0.95) 75%, #04030a 100%);
  pointer-events: none;
  z-index: 0;
}

.results-container {
  position: relative;
  z-index: 1;
  max-width: 1050px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.results-nav-bar {
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

.results-nav-actions {
  display: flex;
  gap: 0.5rem;
}

.gothic-panel {
  background: rgba(16, 12, 30, 0.95);
  border: 1.5px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
}

/* Results Hero Banner */
.results-hero {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  background: linear-gradient(135deg, rgba(30, 20, 56, 0.95) 0%, rgba(13, 10, 26, 0.98) 100%);
}

.hero-crest {
  flex-shrink: 0;
}

.hero-meta {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.trial-date-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #a78bfa;
  letter-spacing: 1px;
}

.hero-title {
  font-family: 'Cinzel', serif;
  font-size: 1.8rem;
  font-weight: 800;
  color: #f8fafc;
  letter-spacing: 1.5px;
  margin: 0;
}

.hero-desc {
  font-size: 0.88rem;
  color: #cbd5e1;
}

.rewards-awarded-pill {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  background: rgba(22, 17, 42, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.4);
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
}

.reward-item {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fde047;
}
.reward-item.perfect {
  color: #fbbf24;
}

/* Scoreboard Grid */
.scoreboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.score-card {
  background: rgba(18, 14, 34, 0.92);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 8px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.score-card.primary {
  border-color: #8b5cf6;
  box-shadow: 0 0 15px rgba(139, 92, 246, 0.25);
}

.score-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.score-main-val {
  font-size: 2rem;
  font-weight: 800;
  color: #f8fafc;
}
.score-main-val .denom {
  font-size: 1.1rem;
  color: #64748b;
  font-weight: normal;
}

.score-main-val.pct.high { color: #34d399; }
.score-main-val.pct.pass { color: #60a5fa; }
.score-main-val.pct.low { color: #f43f5e; }

.score-progress-bar {
  height: 6px;
  background: rgba(30, 41, 59, 0.8);
  border-radius: 3px;
  overflow: hidden;
  margin-top: 0.3rem;
}

.score-progress-bar .bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b5cf6, #3b82f6);
}

.score-subtext {
  font-size: 0.75rem;
  color: #94a3b8;
}

.dual-stat-row {
  display: flex;
  gap: 1.5rem;
  margin-top: 0.2rem;
}

.stat-col {
  display: flex;
  flex-direction: column;
}
.stat-col.correct .val { color: #34d399; font-size: 1.5rem; font-weight: bold; }
.stat-col.incorrect .val { color: #f43f5e; font-size: 1.5rem; font-weight: bold; }
.stat-col .lbl { font-size: 0.65rem; color: #94a3b8; }

/* Analytics Section */
.results-analytics-section {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.analytics-tabs-header {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.5rem;
  overflow-x: auto;
}

.tab-btn {
  background: transparent;
  border: 1px solid transparent;
  color: #94a3b8;
  padding: 0.55rem 0.95rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-btn:hover {
  background: rgba(30, 24, 55, 0.8);
  color: #fff;
}

.tab-btn.active {
  background: #8b5cf6;
  color: #fff;
  border-color: #a78bfa;
}

.tab-pane {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-lead-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.pane-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  color: #f1f5f9;
  margin: 0;
}

.pane-desc {
  font-size: 0.8rem;
  color: #94a3b8;
}

.weak-domains-badge {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.72rem;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.breakdown-row {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  background: rgba(22, 17, 40, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 6px;
  padding: 0.75rem 0.95rem;
}

.breakdown-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.breakdown-name {
  color: #f1f5f9;
  font-weight: 600;
}

.breakdown-counts {
  color: #94a3b8;
}

.breakdown-track {
  height: 8px;
  background: rgba(30, 41, 59, 0.8);
  border-radius: 4px;
  overflow: hidden;
}

.breakdown-fill {
  height: 100%;
  transition: width 0.4s ease;
}
.breakdown-fill.bar-high { background: linear-gradient(90deg, #10b981, #34d399); }
.breakdown-fill.bar-mid  { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.breakdown-fill.bar-low  { background: linear-gradient(90deg, #ef4444, #f87171); }

/* Full Item Review */
.review-questions-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.review-item-card {
  background: rgba(20, 15, 38, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 8px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
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
  gap: 0.75rem;
  flex-wrap: wrap;
}

.q-num-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  background: rgba(139, 92, 246, 0.2);
  color: #c4b5fd;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.result-status-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  font-weight: bold;
}
.result-status-tag.correct { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.result-status-tag.incorrect { background: rgba(239, 68, 68, 0.2); color: #f87171; }

.review-domain-pill {
  font-size: 0.72rem;
  color: #94a3b8;
}

.study-hall-source-link {
  margin-left: auto;
  font-size: 0.72rem;
  color: #38bdf8;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.3rem;
  transition: color 0.15s;
}
.study-hall-source-link:hover {
  color: #7dd3fc;
  text-decoration: underline;
}

.review-stem {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #f8fafc;
}

.review-choices-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.5rem;
}

.review-choice {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.6rem 0.85rem;
  background: rgba(30, 24, 55, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 6px;
  font-size: 0.84rem;
  color: #cbd5e1;
}

.review-choice.correct-target {
  background: rgba(16, 185, 129, 0.15);
  border-color: #10b981;
  color: #f0fdf4;
}

.review-choice.chosen:not(.correct-target) {
  background: rgba(239, 68, 68, 0.15);
  border-color: #ef4444;
  color: #fef2f2;
}

.badge-correct {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  background: #10b981;
  color: #fff;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
}

.badge-wrong {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  background: #ef4444;
  color: #fff;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
}

.review-explanation-box {
  background: rgba(14, 10, 26, 0.9);
  border-left: 3px solid #8b5cf6;
  padding: 0.85rem 1rem;
  border-radius: 0 6px 6px 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.exp-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #c4b5fd;
}

.exp-text {
  font-size: 0.82rem;
  color: #cbd5e1;
  line-height: 1.55;
}

/* ════════════════════════════════════════════════════════════
   BLOOM'S TAXONOMY ANALYTICS & COGNITIVE PROFILE (SECT 44-62)
   ════════════════════════════════════════════════════════════ */
.bloom-tab-btn {
  background: rgba(139, 92, 246, 0.15);
  border-color: rgba(168, 85, 247, 0.45);
}

.bloom-tab-btn.active {
  background: linear-gradient(135deg, rgba(168, 85, 247, 0.3) 0%, rgba(99, 102, 241, 0.3) 100%);
  border-color: #c084fc;
  box-shadow: 0 0 12px rgba(192, 132, 252, 0.3);
}

.bloom-pane {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.bloom-assessment-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #a78bfa;
  background: rgba(46, 16, 101, 0.5);
  border: 1px solid rgba(168, 85, 247, 0.4);
  padding: 0.4rem 0.75rem;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.gothic-subpanel {
  background: rgba(18, 14, 34, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 8px;
  padding: 1.25rem;
}

/* Section Lead Actions */
.lead-actions-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-section-bloom-guide {
  background: rgba(30, 24, 55, 0.85);
  border: 1px solid rgba(168, 85, 247, 0.45);
  color: #d8b4fe;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-section-bloom-guide:hover {
  background: rgba(88, 28, 135, 0.7);
  border-color: #c084fc;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(168, 85, 247, 0.35);
}

/* Personal Cognitive Profile */
.cognitive-profile-panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  background: linear-gradient(180deg, rgba(26, 18, 50, 0.9) 0%, rgba(14, 10, 28, 0.95) 100%);
  border-color: rgba(168, 85, 247, 0.35);
}

.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
}

.profile-title-area {
  flex: 1;
  min-width: 250px;
}

.btn-bloom-header-guide {
  background: linear-gradient(135deg, rgba(88, 28, 135, 0.7) 0%, rgba(46, 16, 101, 0.85) 100%);
  border: 1px solid rgba(168, 85, 247, 0.5);
  border-radius: 8px;
  padding: 0.55rem 0.95rem;
  color: #e9d5ff;
  font-family: inherit;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 14px rgba(126, 34, 206, 0.3);
}

.btn-bloom-header-guide:hover {
  background: linear-gradient(135deg, rgba(126, 34, 206, 0.9) 0%, rgba(79, 70, 229, 0.9) 100%);
  border-color: #c084fc;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.45);
}

.guide-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  background: rgba(0, 0, 0, 0.4);
  color: #c4b5fd;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
  border: 1px solid rgba(168, 85, 247, 0.35);
}

.profile-eyebrow {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #a78bfa;
  letter-spacing: 1px;
}

.profile-title {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 800;
  color: #f1f5f9;
  margin: 0.25rem 0 0.2rem;
  letter-spacing: 0.5px;
}

.profile-disclaimer {
  font-size: 0.75rem;
  color: #94a3b8;
  font-style: italic;
  margin: 0;
}

.profile-trio-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.profile-tier-card {
  background: rgba(10, 8, 20, 0.75);
  border: 1.5px solid rgba(148, 163, 184, 0.2);
  border-radius: 6px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.profile-tier-card.strong {
  border-color: rgba(16, 185, 129, 0.5);
  background: rgba(6, 78, 59, 0.15);
}

.profile-tier-card.developing {
  border-color: rgba(245, 158, 11, 0.5);
  background: rgba(120, 53, 15, 0.15);
}

.profile-tier-card.review {
  border-color: rgba(239, 68, 68, 0.5);
  background: rgba(127, 29, 29, 0.15);
}

.tier-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.profile-tier-card.strong .tier-label { color: #34d399; }
.profile-tier-card.developing .tier-label { color: #fbbf24; }
.profile-tier-card.review .tier-label { color: #f87171; }

.tier-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

/* Interactive Bloom Tier Pills */
.tier-pill {
  font-family: inherit;
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.35);
  outline: none;
}

.tier-pill:hover {
  transform: translateY(-2px);
  filter: brightness(1.2);
}

.tier-pill:active {
  transform: translateY(0);
}

.tier-pill.strong {
  background: rgba(16, 185, 129, 0.25);
  border: 1px solid rgba(52, 211, 153, 0.5);
  color: #a7f3d0;
}
.tier-pill.strong:hover {
  background: rgba(16, 185, 129, 0.4);
  border-color: #34d399;
  box-shadow: 0 0 12px rgba(52, 211, 153, 0.45);
}

.tier-pill.developing {
  background: rgba(245, 158, 11, 0.25);
  border: 1px solid rgba(251, 191, 36, 0.5);
  color: #fef08a;
}
.tier-pill.developing:hover {
  background: rgba(245, 158, 11, 0.4);
  border-color: #fbbf24;
  box-shadow: 0 0 12px rgba(251, 191, 36, 0.45);
}

.tier-pill.review {
  background: rgba(239, 68, 68, 0.25);
  border: 1px solid rgba(248, 113, 113, 0.5);
  color: #fecaca;
  animation: pulse-review-glow 3s infinite ease-in-out;
}
.tier-pill.review:hover {
  background: rgba(239, 68, 68, 0.4);
  border-color: #f87171;
  box-shadow: 0 0 14px rgba(248, 113, 113, 0.55);
}

@keyframes pulse-review-glow {
  0%, 100% { box-shadow: 0 0 4px rgba(239, 68, 68, 0.2); }
  50% { box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
}

.pill-info-badge {
  font-size: 0.65rem;
  background: rgba(0, 0, 0, 0.35);
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
  font-weight: normal;
  opacity: 0.9;
}

.empty-tier-text {
  font-size: 0.75rem;
  color: #64748b;
  font-style: italic;
}

/* Academic Interpretation */
.academic-interpretation-box {
  background: rgba(14, 10, 26, 0.85);
  border-left: 3px solid #a855f7;
  padding: 0.85rem 1.15rem;
  border-radius: 0 6px 6px 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.interp-heading {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.56rem;
  color: #c4b5fd;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.interp-body {
  font-size: 0.86rem;
  color: #e2e8f0;
  line-height: 1.6;
  margin: 0;
}

.cognitive-patterns-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.8rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(139, 92, 246, 0.15);
}

.patterns-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #94a3b8;
}

.pattern-item {
  color: #cbd5e1;
  font-style: italic;
}

/* Bloom 6-Level Grid */
.bloom-levels-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.levels-dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.levels-dashboard-heading {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: 0.5px;
  margin: 0;
}

.levels-dashboard-sub {
  font-size: 0.76rem;
  color: #94a3b8;
  margin: 0.25rem 0 0;
}

.btn-levels-quick-guide {
  background: rgba(26, 18, 50, 0.7);
  border: 1px solid rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.btn-levels-quick-guide:hover {
  background: rgba(139, 92, 246, 0.25);
  color: #ffffff;
  border-color: #a78bfa;
  transform: translateY(-1px);
}

.bloom-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
  gap: 1rem;
}

.bloom-card {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: rgba(16, 12, 30, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 8px;
  padding: 1.15rem;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.clickable-bloom-card {
  cursor: pointer;
}

.clickable-bloom-card:hover {
  border-color: #a78bfa;
  box-shadow: 0 8px 24px rgba(139, 92, 246, 0.25);
  transform: translateY(-3px);
  background: rgba(24, 18, 48, 0.95);
}

.bloom-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.bloom-order-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #a78bfa;
}

.bloom-card-name {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0.2rem 0 0;
  letter-spacing: 0.5px;
}

.bloom-card-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
}

.bloom-accuracy-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
}

.card-inspect-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(168, 85, 247, 0.35);
  color: #c4b5fd;
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
  transition: all 0.2s ease;
}

.clickable-bloom-card:hover .card-inspect-pill {
  background: #7c3aed;
  color: #ffffff;
  border-color: #c084fc;
  box-shadow: 0 0 8px rgba(192, 132, 252, 0.5);
}

.bloom-accuracy-badge.high {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.4);
}

.bloom-accuracy-badge.pass {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  border: 1px solid rgba(96, 165, 250, 0.4);
}

.bloom-accuracy-badge.low {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(248, 113, 113, 0.4);
}

.bloom-skill-desc {
  font-size: 0.76rem;
  color: #94a3b8;
  line-height: 1.45;
  margin: 0;
  min-height: 2.2rem;
}

/* Pixel Progress Bar */
.bloom-track-container {
  display: flex;
  flex-direction: column;
}

.bloom-track {
  width: 100%;
  height: 10px;
  background: rgba(15, 10, 28, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 2px;
  overflow: hidden;
}

.bloom-fill {
  height: 100%;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  image-rendering: pixelated;
}

/* Counters Row */
.bloom-stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.35rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  text-align: center;
}

.stat-cell {
  display: flex;
  flex-direction: column;
}

.stat-cell .stat-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #f1f5f9;
}

.stat-cell.correct .stat-num { color: #34d399; }
.stat-cell.incorrect .stat-num { color: #f87171; }
.stat-cell.time .stat-num { color: #c4b5fd; }

.stat-cell .stat-tag {
  font-size: 0.62rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.bloom-card-footer-prompt {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 0.25rem;
  padding-top: 0.45rem;
  border-top: 1px dashed rgba(139, 92, 246, 0.2);
  font-size: 0.68rem;
  color: #a78bfa;
  font-weight: 500;
  transition: color 0.15s ease;
}

.clickable-bloom-card:hover .bloom-card-footer-prompt {
  color: #e9d5ff;
}

/* Bloom Study Hall Recommendations */
.bloom-study-recommendations {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.recs-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0;
  letter-spacing: 0.5px;
}

.recs-subtitle {
  font-size: 0.78rem;
  color: #94a3b8;
  margin: 0.25rem 0 0;
}

.recs-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 0.85rem;
}

.rec-card {
  background: rgba(12, 9, 24, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 0.9rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 0.75rem;
  transition: all 0.2s ease;
}

.rec-card:hover {
  border-color: rgba(168, 85, 247, 0.6);
  background: rgba(20, 15, 38, 0.9);
}

.rec-domain-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #a78bfa;
}

.rec-part-title {
  font-size: 0.88rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0.25rem 0 0.5rem;
}

.rec-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
}

.rec-missed-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.35);
  color: #fca5a5;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
}

.rec-bloom-pill {
  font-size: 0.68rem;
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(168, 85, 247, 0.25);
  color: #c4b5fd;
  padding: 0.15rem 0.45rem;
  border-radius: 3px;
}

.rec-bloom-pill.interactive-rec-bloom {
  cursor: pointer;
  border: 1px solid rgba(168, 85, 247, 0.35);
  transition: all 0.2s ease;
  font-family: inherit;
}

.rec-bloom-pill.interactive-rec-bloom:hover {
  background: rgba(139, 92, 246, 0.45);
  color: #ffffff;
  border-color: #c084fc;
  transform: translateY(-1px);
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.4);
}

.btn-review-study-hall {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #f8fafc;
  background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
  border: 1px solid #a78bfa;
  border-radius: 4px;
  padding: 0.45rem 0.75rem;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
}

.btn-review-study-hall:hover {
  background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
  box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
  color: #fff;
}

/* Item Review Bloom Pill */
.review-bloom-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  background: rgba(30, 20, 56, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #c4b5fd;
}

.review-bloom-pill.interactive-review-bloom {
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  transition: all 0.2s ease;
  outline: none;
}

.review-bloom-pill.interactive-review-bloom:hover {
  transform: translateY(-1px);
  filter: brightness(1.25);
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.4);
}

.bloom-info-icon {
  font-size: 0.5rem;
}

.review-bloom-pill.bloom-remember { border-color: #38bdf8; color: #7dd3fc; }
.review-bloom-pill.bloom-understand { border-color: #34d399; color: #6ee7b7; }
.review-bloom-pill.bloom-apply { border-color: #fbbf24; color: #fde68a; }
.review-bloom-pill.bloom-analyze { border-color: #f472b6; color: #fbcfe8; }
.review-bloom-pill.bloom-evaluate { border-color: #c084fc; color: #e9d5ff; }
.review-bloom-pill.bloom-create { border-color: #fb923c; color: #fed7aa; }
</style>
