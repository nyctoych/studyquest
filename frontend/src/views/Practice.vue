<template>
  <div class="practice-page game-module-env module-env-library animate-slide-up">
    <!-- Header with Readiness Overview -->
    <div class="practice-header glass-card">
      <div class="header-main">
        <div class="header-title-group">
          <div class="mode-badge">
            <span class="mode-icon"><PixelEmoji :emoji="mode === 'board_exam' ? 'hall' : 'target'" size="xs" /></span>
            <span class="mode-name">{{ mode === 'board_exam' ? 'Board Exam Preparation' : 'Adaptive Practice' }}</span>
          </div>
          <h1>Psychology Adaptive Mastery</h1>
          <p class="subtitle">
            Personalized board-exam readiness engine dynamically adjusting to your performance and response time.
          </p>
        </div>

        <div class="mode-switcher">
          <button 
            class="switch-btn flex items-center gap-1.5" 
            :class="{ active: mode === 'practice' }"
            @click="switchMode('practice')"
          >
            <PixelEmoji emoji="target" size="xs" /> Adaptive Practice
          </button>
          <button 
            class="switch-btn board-btn flex items-center gap-1.5" 
            :class="{ active: mode === 'board_exam' }"
            @click="switchMode('board_exam')"
          >
            <PixelEmoji emoji="hall" size="xs" /> Board Exam Mode
          </button>
        </div>
      </div>

      <!-- Live Adaptive Status Bar -->
      <div class="adaptive-status-bar" v-if="adaptiveProfile">
        <div class="status-col">
          <div class="status-label">Account Level</div>
          <div class="status-value highlight-lvl">Lv.{{ adaptiveProfile.user_level }}</div>
          <div class="status-sub">Baseline: {{ formatTier(adaptiveProfile.baseline_difficulty) }}</div>
        </div>

        <div class="status-col">
          <div class="status-label">Adaptive Difficulty</div>
          <div class="status-value">
            <span class="tier-pill" :class="'tier-' + adaptiveProfile.adaptive_difficulty">
              {{ formatTier(adaptiveProfile.adaptive_difficulty) }}
            </span>
          </div>
          <div class="status-sub">{{ adaptiveProfile.adaptive_state }}</div>
        </div>

        <div class="status-col mastery-col">
          <div class="mastery-header">
            <span class="status-label">Demonstrated Mastery</span>
            <span class="mastery-percent">{{ Math.round(adaptiveProfile.mastery_score) }}%</span>
          </div>
          <div class="mastery-track">
            <div 
              class="mastery-fill" 
              :style="{ width: Math.min(100, Math.max(5, adaptiveProfile.mastery_score)) + '%' }"
              :class="getMasteryColorClass(adaptiveProfile.mastery_score)"
            ></div>
          </div>
          <div class="status-sub">Recent Accuracy: {{ Math.round(adaptiveProfile.recent_accuracy) }}%</div>
        </div>

        <div class="status-col streak-col">
          <div class="status-label">Current Streak</div>
          <div class="status-value fire-val flex items-center gap-1"><PixelEmoji emoji="fire" size="xs" /> {{ adaptiveProfile.current_streak }}</div>
          <div class="status-sub">Avg Time: {{ adaptiveProfile.avg_response_time }}s</div>
        </div>
      </div>

      <!-- User-Facing Dynamic Adaptive Explanation -->
      <div class="explanation-callout" v-if="adaptiveProfile?.user_explanation">
        <span class="callout-icon"><PixelEmoji emoji="bulb" size="sm" /></span>
        <span class="callout-text">{{ adaptiveProfile.user_explanation }}</span>
      </div>
    </div>

    <!-- Targeted Weak Area Study Recommendation (Requirements 21 & 23) -->
    <div class="weak-recommendation-card glass-card animate-slide-up" v-if="sessionState === 'idle' && (adaptiveProfile?.weakest_topic || adaptiveProfile?.weakest_category)">
      <div class="rec-card-content">
        <div class="rec-badge-group">
          <span class="rec-badge-tag"><PixelEmoji emoji="target" size="xs" /> WEAK AREA TARGET</span>
          <h3 class="rec-topic-title">
            {{ adaptiveProfile.weakest_topic?.topic_name || adaptiveProfile.weakest_category?.category_name }}
          </h3>
          <p class="rec-topic-desc">
            Current Accuracy: {{ Math.round(adaptiveProfile.weakest_topic?.accuracy || adaptiveProfile.weakest_category?.accuracy || 0) }}%
            <span v-if="adaptiveProfile.weakest_domain_name"> · {{ adaptiveProfile.weakest_domain_name }}</span>
            <span v-if="adaptiveProfile.weakest_part_title"> ({{ adaptiveProfile.weakest_part_title }})</span>
          </p>
        </div>
        <router-link
          :to="adaptiveProfile.study_url || '/study'"
          class="btn btn-primary rec-review-btn flex items-center gap-1.5"
        >
          <PixelEmoji emoji="book" size="xs" /> [ 📖 REVIEW IN STUDY HALL ]
        </router-link>
      </div>
    </div>

    <!-- Configuration Filter Bar (When not actively in session) -->
    <div class="filter-card glass-card" v-if="sessionState === 'idle'">
      <div class="filter-grid">
        <div class="filter-item">
          <label>Psychology Domain</label>
          <select v-model="selectedCategory" @change="onCategoryChange" class="custom-select">
            <option :value="null">Smart Adaptive Mix (Recommended)</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ getCategoryIcon(cat) }} {{ cat.name }}
            </option>
          </select>
        </div>

        <div class="filter-item" v-if="selectedCategory && topics.length > 0">
          <label>Subtopic Focus</label>
          <select v-model="selectedTopic" class="custom-select">
            <option :value="null">All Topics in Domain</option>
            <option v-for="top in topics" :key="top.id" :value="top.id">
              {{ top.name }}
            </option>
          </select>
        </div>

        <div class="filter-item">
          <label>Questions per Session</label>
          <div class="count-selector">
            <button 
              v-for="cnt in [5, 10, 15]" 
              :key="cnt"
              class="cnt-btn"
              :class="{ active: questionCount === cnt }"
              @click="questionCount = cnt"
            >
              {{ cnt }} Qs
            </button>
          </div>
        </div>
      </div>

      <div class="filter-actions">
        <button class="btn btn-primary start-btn flex items-center justify-center gap-2" @click="startSession" :disabled="loadingQuestions">
          <span v-if="loadingQuestions" class="spinner"></span>
          <span v-else class="flex items-center gap-1.5"><PixelEmoji emoji="lightning" size="xs" /> Begin Adaptive Session</span>
        </button>
      </div>
    </div>

    <!-- Active Question Card -->
    <div class="session-container" v-if="sessionState === 'active' && currentQuestion">
      <div class="question-header-bar">
        <div class="q-progress-indicator">
          <span>Question {{ currentIndex + 1 }} of {{ questions.length }}</span>
          <div class="q-dots">
            <span 
              v-for="(q, idx) in questions" 
              :key="idx" 
              class="q-dot"
              :class="{
                active: idx === currentIndex,
                answered: answeredResults[idx] !== undefined,
                correct: answeredResults[idx]?.is_correct === true,
                wrong: answeredResults[idx]?.is_correct === false
              }"
            ></span>
          </div>
        </div>

        <div class="q-timer-badge" :class="{ 'warning-time': responseSeconds >= 20 }">
          <span class="timer-icon"><PixelEmoji emoji="timer" size="xs" /></span>
          <span>{{ responseSeconds }}s</span>
        </div>
      </div>

      <div class="glass-card question-card animate-fade-in">
        <div class="question-meta-row">
          <div class="category-tag">
            <span>{{ getCategoryIcon(currentQuestion) }}</span>
            <span>{{ currentQuestion.category_name }}</span>
            <span class="topic-crumb" v-if="currentQuestion.topic_name">› {{ currentQuestion.topic_name }}</span>
          </div>

          <div class="tier-pill" :class="'tier-' + currentQuestion.difficulty">
            {{ formatTier(currentQuestion.difficulty) }}
          </div>
        </div>

        <h2 class="question-text">{{ currentQuestion.question_text }}</h2>

        <!-- Answer Choices -->
        <div class="choices-grid">
          <button 
            v-for="choice in currentQuestion.choices" 
            :key="choice.key"
            class="choice-btn"
            :class="{
              selected: selectedChoice === choice.key,
              correct: hasSubmitted && choice.key === currentFeedback?.correct_answer,
              incorrect: hasSubmitted && selectedChoice === choice.key && !currentFeedback?.is_correct,
              disabled: hasSubmitted
            }"
            @click="handleSelectChoice(choice.key)"
            :disabled="hasSubmitted"
          >
            <div class="choice-key">{{ choice.key }}</div>
            <div class="choice-text">{{ choice.text }}</div>
            <div class="choice-status-icon" v-if="hasSubmitted">
              <span v-if="choice.key === currentFeedback?.correct_answer"><PixelEmoji emoji="check" size="xs" /></span>
              <span v-else-if="selectedChoice === choice.key && !currentFeedback?.is_correct"><PixelEmoji emoji="cross" size="xs" /></span>
            </div>
          </button>
        </div>

        <!-- Immediate Learning Feedback & Rationales -->
        <div class="feedback-panel animate-slide-up" v-if="hasSubmitted && currentFeedback">
          <div class="feedback-banner" :class="currentFeedback.is_correct ? 'banner-correct' : 'banner-incorrect'">
            <span class="feedback-icon"><PixelEmoji :emoji="currentFeedback.is_correct ? 'sparkles' : 'bulb'" size="sm" /></span>
            <div class="feedback-header-text">
              <strong>{{ currentFeedback.is_correct ? 'Correct Answer!' : 'Concept Reinforcement Needed' }}</strong>
              <span class="xp-earned" v-if="currentFeedback.xp_earned">+{{ currentFeedback.xp_earned }} XP</span>
            </div>
          </div>

          <div class="feedback-body">
            <h4 class="explanation-title">Psychological Rationale & Analysis</h4>
            <p class="explanation-text">{{ currentFeedback.explanation }}</p>
            
            <div class="source-ref" v-if="currentQuestion.source_reference">
              <span class="ref-label flex items-center gap-1"><PixelEmoji emoji="book" size="xs" /> Reference:</span>
              <span class="ref-text">{{ currentQuestion.source_reference }}</span>
            </div>

            <!-- Dynamic Adaptive Notice -->
            <div class="adaptive-adjustment-notice" v-if="currentFeedback.user_explanation">
              <span class="notice-icon"><PixelEmoji emoji="lightning" size="xs" /></span>
              <span class="notice-msg">{{ currentFeedback.user_explanation }}</span>
            </div>
          </div>

          <div class="feedback-actions">
            <button class="btn btn-primary next-btn flex items-center justify-center gap-2" @click="advanceNextQuestion">
              <span>{{ currentIndex + 1 < questions.length ? 'Next Question →' : 'Complete Session' }}</span>
              <PixelEmoji emoji="hall" size="xs" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- End of Session Summary Modal / View -->
    <div class="summary-container glass-card animate-slide-up" v-if="sessionState === 'completed' && sessionSummary">
      <div class="summary-header">
        <div class="summary-trophy"><PixelEmoji emoji="trophy" size="xl" /></div>
        <h2>Session Performance Summary</h2>
        <p class="summary-sub">Your psychology cognitive profile has been updated based on this session.</p>
      </div>

      <div class="summary-stats-grid">
        <div class="sum-card">
          <div class="sum-num text-success">{{ sessionSummary.accuracy }}%</div>
          <div class="sum-label">Session Accuracy</div>
          <div class="sum-sub">{{ sessionSummary.questions_correct }} / {{ sessionSummary.questions_answered }} Correct</div>
        </div>

        <div class="sum-card">
          <div class="sum-num text-indigo">{{ sessionSummary.avg_response_time }}s</div>
          <div class="sum-label">Avg Response Time</div>
          <div class="sum-sub flex items-center justify-center gap-1">
            <PixelEmoji :emoji="sessionSummary.avg_response_time <= 10 ? 'lightning' : 'target'" size="xs" />
            {{ sessionSummary.avg_response_time <= 10 ? 'Rapid Fluency' : 'Deliberate Analysis' }}
          </div>
        </div>

        <div class="sum-card">
          <div class="sum-num text-purple">{{ Math.round(sessionSummary.mastery_score) }}%</div>
          <div class="sum-label">Demonstrated Mastery</div>
          <div class="sum-sub">{{ sessionSummary.adaptive_state }}</div>
        </div>

        <div class="sum-card">
          <div class="sum-num tier-text">
            <span class="tier-pill" :class="'tier-' + sessionSummary.adaptive_difficulty">
              {{ formatTier(sessionSummary.adaptive_difficulty) }}
            </span>
          </div>
          <div class="sum-label">Adaptive Tier</div>
          <div class="sum-sub">Account Level {{ sessionSummary.current_level }}</div>
        </div>
      </div>

      <!-- Domain Strengths & Weaknesses -->
      <div class="domains-analysis-grid">
        <div class="domain-box strong-box" v-if="sessionSummary.strongest_category">
          <div class="box-icon"><PixelEmoji emoji="star" size="md" /></div>
          <div class="box-content">
            <span class="box-title">Strongest Domain</span>
            <strong class="domain-name">{{ sessionSummary.strongest_category }}</strong>
            <span class="domain-score" v-if="sessionSummary.strongest_cat_acc">{{ sessionSummary.strongest_cat_acc }}% Accuracy</span>
          </div>
        </div>

        <div class="domain-box weak-box" v-if="sessionSummary.weakest_category || sessionSummary.weakest_topic">
          <div class="box-icon"><PixelEmoji emoji="target" size="md" /></div>
          <div class="box-content">
            <span class="box-title">Focus Area (Recommended Practice)</span>
            <strong class="domain-name">{{ sessionSummary.weakest_topic || sessionSummary.weakest_category }}</strong>
            <span class="domain-score" v-if="sessionSummary.weakest_cat_acc">{{ sessionSummary.weakest_cat_acc }}% Accuracy</span>
            <router-link
              v-if="sessionSummary.study_url || sessionSummary.weakest_domain_id"
              :to="sessionSummary.study_url || ('/study/' + sessionSummary.weakest_domain_id + (sessionSummary.weakest_part_number ? '/part/' + sessionSummary.weakest_part_number : ''))"
              class="sum-review-link"
            >
              [ 📖 REVIEW IN STUDY HALL ]
            </router-link>
          </div>
        </div>
      </div>

      <!-- Recommendation Callout -->
      <div class="summary-recommendation" v-if="sessionSummary.recommendation">
        <div class="rec-icon"><PixelEmoji emoji="scroll" size="md" /></div>
        <div class="rec-text">
          <strong>StudyQuest Learning Recommendation:</strong>
          <p>{{ sessionSummary.recommendation }}</p>
        </div>
      </div>

      <div class="summary-actions">
        <router-link
          v-if="sessionSummary.study_url || sessionSummary.weakest_domain_id"
          :to="sessionSummary.study_url || ('/study/' + sessionSummary.weakest_domain_id + (sessionSummary.weakest_part_number ? '/part/' + sessionSummary.weakest_part_number : ''))"
          class="btn btn-primary flex items-center justify-center gap-1.5"
        >
          <PixelEmoji emoji="book" size="xs" /> [ 📖 REVIEW IN STUDY HALL ]
        </router-link>
        <button class="btn btn-primary flex items-center justify-center gap-1.5" @click="startTargetedPractice" v-if="sessionSummary.weakest_category">
          <PixelEmoji emoji="target" size="xs" /> Practice Weak Domain Next
        </button>
        <button class="btn btn-secondary flex items-center justify-center gap-1.5" @click="resetSession">
          <PixelEmoji emoji="lightning" size="xs" /> Start Another Session
        </button>
        <router-link to="/dashboard" class="btn btn-outline flex items-center justify-center gap-1.5">
          <PixelEmoji emoji="chart" size="xs" /> Back to Dashboard
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adaptiveService } from '../services/adaptiveService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

// State
const mode = ref('practice') // 'practice' or 'board_exam'
const sessionState = ref('idle') // 'idle' | 'active' | 'completed'
const categories = ref([])
const topics = ref([])
const selectedCategory = ref(null)
const selectedTopic = ref(null)
const questionCount = ref(10)

const adaptiveProfile = ref(null)
const questions = ref([])
const currentIndex = ref(0)
const selectedChoice = ref(null)
const hasSubmitted = ref(false)
const currentFeedback = ref(null)
const answeredResults = ref([])
const sessionSummary = ref(null)
const loadingQuestions = ref(false)

// Timer
const responseSeconds = ref(0)
let timerInterval = null

const currentQuestion = computed(() => {
  return questions.value[currentIndex.value] || null
})

function formatTier(tier) {
  if (!tier) return 'Intermediate'
  return tier.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function getMasteryColorClass(score) {
  if (score >= 90) return 'mastery-gold'
  if (score >= 75) return 'mastery-green'
  if (score >= 60) return 'mastery-blue'
  if (score >= 40) return 'mastery-amber'
  return 'mastery-red'
}

const categoryIconMap = {
  1: '🧠', 2: '🧬', 3: '💡', 4: '🌱', 5: '👥',
  6: '🎭', 7: '⚡', 8: '🩺', 9: '🤝', 10: '🏢',
  11: '📋', 12: '🔬', 13: '📊', 14: '📐', 15: '⚖️',
  16: '📜', 17: '🎯', 18: '🔥', 19: '👁️', 20: '❤️',
}

function getCategoryIcon(item) {
  if (!item) return '🧠'
  const icon = item.icon || item.category_icon
  const id = item.category_id || item.id
  if (icon && !icon.includes('ƒ') && !icon.includes('Ô') && !icon.includes('º') && !icon.includes('Â') && !icon.includes('Ã')) {
    return icon
  }
  return categoryIconMap[id] || '🧠'
}

async function loadInitialData() {
  try {
    const [profRes, catRes] = await Promise.all([
      adaptiveService.getProfile(),
      adaptiveService.getCategories()
    ])

    if (profRes.data?.success) {
      adaptiveProfile.value = profRes.data.profile || profRes.data.data
    }
    if (catRes.data?.success) {
      categories.value = catRes.data.categories || []
    }

    // Check URL query parameters for pre-selected category / topic (e.g. from Dashboard CTA)
    if (route.query.category_id) {
      selectedCategory.value = parseInt(route.query.category_id)
      await loadTopics(selectedCategory.value)
    }
    if (route.query.topic_id) {
      selectedTopic.value = parseInt(route.query.topic_id)
    }
    if (route.query.mode) {
      mode.value = route.query.mode
    }
  } catch (err) {
    console.error('Failed to load practice profile:', err)
  }
}

async function loadTopics(categoryId) {
  if (!categoryId) {
    topics.value = []
    selectedTopic.value = null
    return
  }
  try {
    const res = await adaptiveService.getTopics(categoryId)
    if (res.data?.success) {
      topics.value = res.data.topics || []
    }
  } catch (e) {
    topics.value = []
  }
}

async function onCategoryChange() {
  selectedTopic.value = null
  if (selectedCategory.value) {
    await loadTopics(selectedCategory.value)
  } else {
    topics.value = []
  }
}

function switchMode(newMode) {
  mode.value = newMode
  if (sessionState.value === 'active') {
    if (confirm('Switching modes will restart your current session. Continue?')) {
      resetSession()
    }
  }
}

async function startSession() {
  loadingQuestions.value = true
  try {
    const res = await adaptiveService.getPracticeQuestions({
      count: questionCount.value,
      category_id: selectedCategory.value,
      topic_id: selectedTopic.value,
      mode: mode.value,
    })

    if (res.data?.success && res.data.questions?.length > 0) {
      questions.value = res.data.questions
      currentIndex.value = 0
      answeredResults.value = []
      sessionState.value = 'active'
      startQuestionTimer()
    } else {
      toast.error('No questions available for this selection. Try choosing All Domains.')
    }
  } catch (e) {
    toast.error('Failed to load questions. Please check connection.')
  } finally {
    loadingQuestions.value = false
  }
}

function startQuestionTimer() {
  clearInterval(timerInterval)
  responseSeconds.value = 0
  timerInterval = setInterval(() => {
    responseSeconds.value++
  }, 1000)
}

async function handleSelectChoice(choiceKey) {
  if (hasSubmitted.value || !currentQuestion.value) return
  selectedChoice.value = choiceKey
  hasSubmitted.value = true
  clearInterval(timerInterval)

  const elapsed = Math.max(1, responseSeconds.value)

  try {
    const res = await adaptiveService.submitAttempt({
      question_id: currentQuestion.value.id,
      selected_answer: choiceKey,
      response_time: elapsed,
    })

    if (res.data?.success) {
      currentFeedback.value = res.data
      answeredResults.value[currentIndex.value] = {
        is_correct: res.data.is_correct,
        response_time: elapsed,
      }

      // Update local profile with latest server authoritative metrics
      if (adaptiveProfile.value) {
        adaptiveProfile.value.mastery_score = res.data.mastery_score
        adaptiveProfile.value.adaptive_difficulty = res.data.adaptive_difficulty
        adaptiveProfile.value.adaptive_state = res.data.adaptive_state
        adaptiveProfile.value.current_streak = res.data.streak
        adaptiveProfile.value.user_explanation = res.data.user_explanation
      }
    }
  } catch (err) {
    console.error('Error submitting attempt:', err)
    toast.error('Error recording attempt')
  }
}

async function advanceNextQuestion() {
  if (currentIndex.value + 1 < questions.value.length) {
    currentIndex.value++
    selectedChoice.value = null
    hasSubmitted.value = false
    currentFeedback.value = null
    startQuestionTimer()
  } else {
    // Complete session and fetch summary
    clearInterval(timerInterval)
    sessionState.value = 'completed'
    await fetchSessionSummary()
  }
}

async function fetchSessionSummary() {
  try {
    const res = await adaptiveService.getSessionSummary(questions.value.length)
    if (res.data?.success) {
      sessionSummary.value = res.data.summary
    }
  } catch (err) {
    console.error('Failed to fetch session summary:', err)
  }
}

function resetSession() {
  clearInterval(timerInterval)
  sessionState.value = 'idle'
  questions.value = []
  currentIndex.value = 0
  selectedChoice.value = null
  hasSubmitted.value = false
  currentFeedback.value = null
  answeredResults.value = []
  sessionSummary.value = null
}

function startTargetedPractice() {
  const weakCat = categories.value.find(c => c.name === sessionSummary.value?.weakest_category)
  if (weakCat) {
    selectedCategory.value = weakCat.id
    onCategoryChange().then(() => {
      resetSession()
      startSession()
    })
  } else {
    resetSession()
    startSession()
  }
}

onMounted(async () => {
  await loadInitialData()
})

onUnmounted(() => {
  clearInterval(timerInterval)
})
</script>

<style scoped>
.practice-page {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  max-width: 1040px;
  margin: 0 auto;
  padding-bottom: 3rem;
  position: relative;
  z-index: 1;
}

/* Header */
.practice-header {
  padding: 1.75rem 2rem;
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(0, 0, 0, 0.6);
  border-radius: 4px;
  position: relative;
}

.practice-header::before {
  content: "❖";
  position: absolute;
  top: -8px;
  left: 20px;
  color: #b59410;
  font-size: 14px;
}

.header-main {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.mode-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  background: rgba(181, 148, 16, 0.12);
  border: 1px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 1px #050608;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: #e6c845;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.header-title-group h1 {
  font-family: 'Cinzel', serif;
  font-size: 2rem;
  font-weight: 800;
  color: #e2d9cc;
  margin: 0 0 0.4rem;
  letter-spacing: 0.03em;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.subtitle {
  color: #a09789;
  font-size: 0.95rem;
  margin: 0;
  max-width: 650px;
  font-family: 'VT323', monospace;
  letter-spacing: 0.04em;
  line-height: 1.45;
}

.mode-switcher {
  display: flex;
  background: #08090c;
  padding: 0.35rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  gap: 0.35rem;
}

.switch-btn {
  background: transparent;
  border: 1px solid transparent;
  padding: 0.55rem 1.1rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #8b8070;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.2s ease;
}

.switch-btn.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border-color: #b91c1c;
  box-shadow: 0 0 0 1px #050608, 0 2px 10px rgba(139, 26, 26, 0.5);
}

.switch-btn.board-btn.active {
  background: linear-gradient(180deg, #b59410 0%, #6b5708 100%);
  color: #050608;
  font-weight: 800;
  border-color: #e6c845;
  box-shadow: 0 0 0 1px #050608, 0 2px 10px rgba(181, 148, 16, 0.5);
}

/* Adaptive Status Bar */
.adaptive-status-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.status-col {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.status-label {
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8b8070;
  font-weight: 700;
}

.status-value {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.1rem;
  color: #e2d9cc;
}

.highlight-lvl {
  color: #b59410;
  text-shadow: 0 0 6px rgba(181, 148, 16, 0.4);
}

.fire-val {
  color: #e63946;
  text-shadow: 0 0 6px rgba(230, 57, 70, 0.4);
}

.status-sub {
  font-family: 'VT323', monospace;
  font-size: 0.85rem;
  color: #7b7365;
}

.mastery-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mastery-percent {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #e2d9cc;
}

.mastery-track {
  width: 100%;
  height: 10px;
  background: #050608;
  border: 1px solid #1c1e29;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.8);
  margin: 0.35rem 0;
}

.mastery-fill {
  height: 100%;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.mastery-gold   { background: linear-gradient(90deg, #b59410, #e6c845); box-shadow: 0 0 6px rgba(230, 200, 69, 0.5); }
.mastery-green  { background: linear-gradient(90deg, #2d6a4f, #52b788); box-shadow: 0 0 6px rgba(82, 183, 136, 0.5); }
.mastery-blue   { background: linear-gradient(90deg, #1d4ed8, #60a5fa); box-shadow: 0 0 6px rgba(96, 165, 250, 0.5); }
.mastery-amber  { background: linear-gradient(90deg, #c2410c, #f97316); box-shadow: 0 0 6px rgba(249, 115, 22, 0.5); }
.mastery-red    { background: linear-gradient(90deg, #8b1a1a, #e63946); box-shadow: 0 0 6px rgba(230, 57, 70, 0.5); }

/* Explanation Callout */
.explanation-callout {
  margin-top: 1.25rem;
  padding: 0.75rem 1.15rem;
  background: rgba(181, 148, 16, 0.08);
  border-left: 3px solid #b59410;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.callout-icon { font-size: 1.1rem; flex-shrink: 0; }
.callout-text { font-family: 'VT323', monospace; font-size: 1.05rem; color: #d4ccb8; }

/* Filter Card */
.filter-card {
  padding: 1.75rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
}

.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.filter-item label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #b59410;
  margin-bottom: 0.5rem;
}

.custom-select {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 1px #050608;
  color: #e2d9cc;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  outline: none;
  cursor: pointer;
}

.count-selector {
  display: flex;
  gap: 0.5rem;
}

.cnt-btn {
  flex: 1;
  padding: 0.75rem 0.5rem;
  background: #08090c;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 1px #050608;
  color: #8b8070;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cnt-btn.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border-color: #b91c1c;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
}

.start-btn {
  padding: 0.85rem 2rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border: 2px solid #b91c1c;
  box-shadow: 0 0 0 2px #050608, 0 4px 15px rgba(139, 26, 26, 0.6);
  cursor: pointer;
  letter-spacing: 0.05em;
  transition: all 0.2s ease;
}

.start-btn:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%);
  border-color: #ef4444;
  transform: translateY(-2px);
}

/* Question Header Bar */
.question-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.q-progress-indicator {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #a09789;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
}

.q-dots {
  display: flex;
  gap: 0.35rem;
}

.q-dot {
  width: 10px;
  height: 10px;
  background: #1c1e29;
  border: 1px solid #050608;
  transition: all 0.3s ease;
}

.q-dot.active { background: #b59410; transform: scale(1.2); }
.q-dot.correct { background: #52b788; }
.q-dot.wrong { background: #e63946; }

.q-timer-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: #08090c;
  padding: 0.4rem 0.85rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 2px #050608;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #a09789;
}

.q-timer-badge.warning-time {
  border-color: rgba(230, 57, 70, 0.6);
  color: #ff6b6b;
  animation: pulse 1s infinite;
}

/* Question Card */
.question-card {
  padding: 2.25rem;
  background: linear-gradient(180deg, #16151c 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #050608, 0 10px 30px rgba(0, 0, 0, 0.8);
  border-radius: 4px;
}

.question-meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.category-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #b59410;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.topic-crumb {
  color: #8b8070;
  font-weight: 600;
}

.question-text {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  line-height: 1.6;
  color: #f5efe6;
  margin-bottom: 2rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

/* Choices Grid */
.choices-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.9rem;
  margin-bottom: 1.5rem;
}

.choice-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  background: #111218;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  color: #e2d9cc;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
  width: 100%;
}

.choice-btn:hover:not(:disabled) {
  background: rgba(139, 26, 26, 0.2);
  border-color: rgba(230, 57, 70, 0.5);
  transform: translateY(-2px);
}

.choice-key {
  width: 32px;
  height: 32px;
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  flex-shrink: 0;
  color: #b59410;
}

.choice-text {
  flex: 1;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  line-height: 1.45;
  letter-spacing: 0.02em;
}

.choice-btn.selected {
  border-color: #b59410;
  background: rgba(181, 148, 16, 0.15);
}

.choice-btn.correct {
  border-color: #52b788 !important;
  background: rgba(45, 106, 79, 0.3) !important;
  color: #95d5b2 !important;
}

.choice-btn.correct .choice-key {
  background: #2d6a4f;
  color: #95d5b2;
}

.choice-btn.incorrect {
  border-color: #e63946 !important;
  background: rgba(139, 26, 26, 0.3) !important;
  color: #ff8585 !important;
}

.choice-btn.incorrect .choice-key {
  background: #8b1a1a;
  color: #ff8585;
}

/* Feedback Panel */
.feedback-panel {
  margin-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 1.75rem;
}

.feedback-banner {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1.25rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 0 0 2px #050608;
}

.banner-correct {
  background: rgba(45, 106, 79, 0.25);
  border: 1px solid #52b788;
  color: #95d5b2;
}

.banner-incorrect {
  background: rgba(139, 26, 26, 0.25);
  border: 1px solid #e63946;
  color: #ff8585;
}

.feedback-header-text {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
  font-family: 'Cinzel', serif;
}

.xp-earned {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #e6c845;
}

.explanation-title {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #b59410;
  margin: 0 0 0.5rem;
}

.explanation-text {
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  line-height: 1.5;
  color: #d4ccb8;
  margin: 0 0 1rem;
}

.source-ref {
  display: flex;
  gap: 0.5rem;
  font-size: 0.82rem;
  color: #7b7365;
  margin-bottom: 1.25rem;
}

.ref-label { font-weight: 600; font-family: 'Cinzel', serif; }

.adaptive-adjustment-notice {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.65rem 1rem;
  background: rgba(181, 148, 16, 0.1);
  border: 1px solid rgba(181, 148, 16, 0.3);
  margin-bottom: 1.5rem;
}

.notice-icon { color: #e6c845; }
.notice-msg { font-family: 'VT323', monospace; font-size: 1.05rem; color: #e2d9cc; font-weight: 600; }

.feedback-actions {
  display: flex;
  justify-content: flex-end;
}

.next-btn {
  padding: 0.85rem 2rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border: 2px solid #b91c1c;
  box-shadow: 0 0 0 2px #050608;
  cursor: pointer;
}

.next-btn:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%);
}

/* Summary View */
.summary-container {
  padding: 3rem 2.5rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 2px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 3px #050608, 0 15px 40px rgba(0, 0, 0, 0.95);
  border-radius: 4px;
  text-align: center;
}

.summary-trophy {
  font-size: 3.5rem;
  margin-bottom: 0.75rem;
}

.summary-header h2 {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 800;
  color: #e2d9cc;
  margin: 0 0 0.5rem;
  letter-spacing: 0.04em;
}

.summary-sub {
  color: #a09789;
  font-size: 1.1rem;
  font-family: 'VT323', monospace;
  margin-bottom: 2.25rem;
}

.summary-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.sum-card {
  background: #08090c;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  padding: 1.5rem 1rem;
}

.sum-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.8rem;
  font-weight: 900;
  margin-bottom: 0.4rem;
}

.sum-label {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: #8b8070;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.35rem;
}

.sum-sub {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #7b7365;
}

.text-success { color: #52b788; text-shadow: 0 0 6px rgba(82, 183, 136, 0.4); }
.text-indigo  { color: #b59410; text-shadow: 0 0 6px rgba(181, 148, 16, 0.4); }
.text-purple  { color: #c084fc; }

.domains-analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
  text-align: left;
}

.domain-box {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 0 0 2px #050608;
}

.strong-box {
  background: rgba(45, 106, 79, 0.15);
  border: 1px solid rgba(82, 183, 136, 0.35);
}

.weak-box {
  background: rgba(139, 26, 26, 0.15);
  border: 1px solid rgba(230, 57, 70, 0.35);
}

.box-icon { font-size: 1.75rem; }
.box-content { display: flex; flex-direction: column; }
.box-title { font-family: 'Cinzel', serif; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: #8b8070; font-weight: 700; }
.domain-name { font-family: 'Cinzel', serif; font-size: 1.1rem; color: #f5efe6; font-weight: 700; margin: 0.15rem 0; }
.domain-score { font-family: 'Press Start 2P', monospace; font-size: 0.65rem; color: #a09789; }

.summary-recommendation {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: rgba(181, 148, 16, 0.08);
  border-left: 4px solid #b59410;
  box-shadow: 0 0 0 2px #050608;
  text-align: left;
  margin-bottom: 2.5rem;
}

.rec-icon { font-size: 1.5rem; }
.rec-text strong { display: block; font-family: 'Cinzel', serif; color: #e6c845; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.25rem; }
.rec-text p { margin: 0; color: #d4ccb8; font-family: 'VT323', monospace; font-size: 1.15rem; }

.summary-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Tier Pills */
.tier-pill {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.04em;
}

.tier-beginner     { background: rgba(45, 106, 79, 0.25); color: #52b788; border: 1px solid rgba(82, 183, 136, 0.5); }
.tier-intermediate { background: rgba(29, 78, 216, 0.25); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.5); }
.tier-advanced     { background: rgba(126, 34, 206, 0.25); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.5); }
.tier-board_exam   { background: rgba(181, 148, 16, 0.25); color: #e6c845; border: 1px solid rgba(230, 200, 69, 0.5); }

@media (max-width: 768px) {
  .practice-header { padding: 1.25rem; }
  .header-main { flex-direction: column; gap: 1rem; }
  .header-title-group h1 { font-size: 1.5rem; }
  .mode-switcher { width: 100%; }
  .switch-btn { flex: 1; text-align: center; font-size: 0.75rem; padding: 0.5rem 0.6rem; }
  .question-card { padding: 1.25rem; }
  .question-text { font-size: 1.1rem; line-height: 1.5; margin-bottom: 1.25rem; }
  .choice-btn { padding: 0.85rem 1rem; font-size: 0.9rem; }
  .filter-actions { width: 100%; }
  .start-btn { width: 100%; }
  .summary-container { padding: 1.5rem 1rem; }
  .domains-analysis-grid { grid-template-columns: 1fr; }
  .summary-actions { flex-direction: column; width: 100%; }
  .summary-actions .btn { width: 100%; }
}

@media (max-width: 520px) {
  .adaptive-status-bar {
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }
  .summary-stats-grid {
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
  }
  .question-meta-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
  }
  .q-progress-indicator {
    font-size: 0.8rem;
  }
  .q-dots {
    display: none;
  }
}

@media (max-width: 380px) {
  .adaptive-status-bar {
    grid-template-columns: 1fr;
  }
  .summary-stats-grid {
    grid-template-columns: 1fr;
  }
}

.weak-recommendation-card {
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.5rem;
  background: rgba(30, 24, 15, 0.92);
  border: 1px solid #78350f;
  border-left: 4px solid #f59e0b;
}
.rec-card-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  flex-wrap: wrap;
}
.rec-badge-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 0.35rem;
}
.rec-topic-title {
  font-size: 1.2rem;
  font-weight: 700;
  color: #fef3c7;
  margin: 0 0 0.25rem 0;
}
.rec-topic-desc {
  font-size: 0.85rem;
  color: #d4d4d8;
  margin: 0;
}
.rec-review-btn {
  white-space: nowrap;
  font-weight: 600;
  text-decoration: none;
}
.sum-review-link {
  display: inline-block;
  margin-top: 0.5rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  color: #e6c845;
  text-decoration: none;
  border: 1px solid #78350f;
  background: rgba(40, 25, 15, 0.85);
  padding: 0.25rem 0.5rem;
}
.sum-review-link:hover {
  background: #78350f;
  color: #fff;
}
</style>
