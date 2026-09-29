<template>
  <div class="special-event-exam-page">
    <div class="exam-bg-vignette"></div>

    <!-- Loading Screen -->
    <div v-if="loading" class="exam-loader-screen">
      <div class="loader-content">
        <PixelEmoji emoji="crystal" size="game" />
        <h2 class="loader-title">SEALING EXAMINATION CHAMBER</h2>
        <p class="loader-sub">Preparing 140 snapshot questions from the Study Hall archives...</p>
      </div>
    </div>

    <!-- Active Exam Interface -->
    <div v-else-if="attempt && currentQuestion" class="exam-viewport">
      <!-- Top Exam Bar -->
      <header class="exam-header-bar gothic-bar">
        <div class="header-left">
          <div class="trial-tag flex items-center gap-1.5">
            <PixelEmoji emoji="shield" size="xs" />
            <span>DAILY TRIAL</span>
          </div>
          <div class="question-tracker">
            <span class="q-label">Question</span>
            <span class="q-current">{{ currentIndex + 1 }}</span>
            <span class="q-total">/ {{ totalQuestions }}</span>
          </div>
        </div>

        <!-- Center: Server Authoritative Timer -->
        <div class="header-center">
          <SpecialEventTimer
            :remaining-seconds="attempt.remaining_seconds"
            :total-seconds="attempt.total_allowed_seconds || 8400"
            @time-up="onTimeUp"
          />
        </div>

        <!-- Right: Progress Counters & Actions -->
        <div class="header-right">
          <div class="progress-pill-group">
            <div class="stat-badge answered" title="Answered Questions">
              <span class="dot"></span>
              <span>{{ answeredCount }} Answered</span>
            </div>
            <div class="stat-badge pending" title="Unanswered Questions">
              <span class="dot"></span>
              <span>{{ unansweredCount }} Pending</span>
            </div>
            <div class="stat-badge marked" title="Marked for Review">
              <span class="dot"></span>
              <span>{{ markedCount }} Marked</span>
            </div>
          </div>

          <button
            class="drawer-toggle-btn"
            :class="{ active: showNavDrawer }"
            @click="showNavDrawer = !showNavDrawer"
            title="Question Grid Navigator"
          >
            <PixelEmoji emoji="scroll" size="xs" />
            <span class="btn-text">Grid ({{ totalQuestions }})</span>
          </button>
        </div>
      </header>

      <!-- Main Layout: Question Canvas & Navigator Drawer -->
      <main class="exam-main-canvas">
        <div class="exam-container">
          <!-- Question Card -->
          <div class="question-container gothic-panel animate-fade-in" :key="currentQuestion.id">
            <!-- Question Metadata Header -->
            <div class="question-meta-bar">
              <div class="meta-tags-left">
                <span class="domain-tag flex items-center gap-1">
                  <PixelEmoji emoji="book" size="xs" />
                  {{ currentQuestion.domain_name || 'Psychopathology' }}
                </span>
                <span class="part-tag" v-if="currentQuestion.part_title">
                  {{ currentQuestion.part_title }}
                </span>
              </div>
              <div class="meta-tags-right">
                <span class="difficulty-pill" :class="currentQuestion.difficulty">
                  {{ formatDifficulty(currentQuestion.difficulty) }}
                </span>
                <span class="type-pill">
                  {{ formatQuestionType(currentQuestion.question_type) }}
                </span>
              </div>
            </div>

            <!-- Question Stem Text -->
            <div class="question-stem-box">
              <p class="question-stem-text">
                {{ currentQuestion.question_text }}
              </p>
            </div>

            <!-- Choices A, B, C, D -->
            <div class="choices-container">
              <div
                v-for="choiceKey in ['A', 'B', 'C', 'D']"
                :key="choiceKey"
                class="choice-item"
                :class="{
                  'is-selected': currentSelection === choiceKey,
                  'is-saving': savingAnswer && currentSelection === choiceKey
                }"
                @click="selectChoice(choiceKey)"
              >
                <div class="choice-indicator">
                  <span class="choice-key">{{ choiceKey }}</span>
                </div>
                <div class="choice-text">
                  {{ currentQuestion['choice_' + choiceKey.toLowerCase()] }}
                </div>
                <div v-if="currentSelection === choiceKey" class="selected-checkmark">
                  ✓
                </div>
              </div>
            </div>

            <!-- Immediate Bloom Feedback Box (Section 52: Active only when enabled) -->
            <div v-if="immediateFeedback && immediateFeedback.immediate" class="immediate-feedback-box gothic-subpanel">
              <div class="feedback-badge-row">
                <span class="recorded-tag">ANSWER RECORDED</span>
                <span class="result-tag" :class="immediateFeedback.is_correct ? 'correct' : 'incorrect'">
                  {{ immediateFeedback.question_result }}
                </span>
              </div>
              <div class="feedback-body-grid">
                <div class="feedback-field">
                  <span class="fb-lbl">Bloom Level:</span>
                  <span class="fb-val-pill">{{ immediateFeedback.bloom_level }}</span>
                </div>
                <div class="feedback-field skill">
                  <span class="fb-lbl">Cognitive Skill:</span>
                  <span class="fb-desc">{{ immediateFeedback.cognitive_skill }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Exam Controls -->
          <footer class="exam-footer-controls gothic-bar">
            <div class="controls-left">
              <button
                class="ctrl-btn prev-btn"
                :disabled="currentIndex === 0"
                @click="prevQuestion"
              >
                ◀ Previous
              </button>
              <button
                class="ctrl-btn mark-btn"
                :class="{ 'is-marked': isCurrentMarked }"
                @click="toggleCurrentMark"
              >
                <span class="star-icon">★</span>
                <span>{{ isCurrentMarked ? 'Marked for Review' : 'Mark for Review' }}</span>
              </button>
            </div>

            <div class="controls-right">
              <button
                v-if="currentIndex < totalQuestions - 1"
                class="ctrl-btn next-btn"
                @click="nextQuestion"
              >
                Next ▶
              </button>
              <button
                class="ctrl-btn submit-btn"
                @click="showSubmitModal = true"
              >
                <PixelEmoji emoji="shield" size="xs" />
                <span>Submit Examination</span>
              </button>
            </div>
          </footer>
        </div>

        <!-- Sliding / Side Navigator Drawer -->
        <Transition name="drawer">
          <aside v-if="showNavDrawer" class="exam-side-drawer">
            <div class="drawer-header">
              <span class="drawer-title flex items-center gap-1.5">
                <PixelEmoji emoji="scroll" size="xs" /> 140 TRIAL ITEMS
              </span>
              <button class="drawer-close" @click="showNavDrawer = false">✕</button>
            </div>
            <SpecialEventNavigator
              :questions="questions"
              :current-index="currentIndex"
              :answers="userAnswers"
              @select="onJumpToQuestion"
            />
          </aside>
        </Transition>
      </main>

      <!-- Submit Confirmation Modal -->
      <SpecialEventSubmitModal
        v-if="showSubmitModal"
        :total-questions="totalQuestions"
        :answered-count="answeredCount"
        :unanswered-count="unansweredCount"
        :marked-count="markedCount"
        :is-submitting="isSubmitting"
        @close="showSubmitModal = false"
        @confirm="submitFinalExam"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import SpecialEventTimer from '../components/special-event/SpecialEventTimer.vue'
import SpecialEventNavigator from '../components/special-event/SpecialEventNavigator.vue'
import SpecialEventSubmitModal from '../components/special-event/SpecialEventSubmitModal.vue'
import { useToastStore } from '../stores/toast'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()

const loading = ref(true)
const attemptId = ref(Number(route.query.attemptId) || 0)
const attempt = ref(null)
const questions = ref([])
const userAnswers = ref({})
const currentIndex = ref(0)
const showNavDrawer = ref(false)
const showSubmitModal = ref(false)
const isSubmitting = ref(false)
const savingAnswer = ref(false)
const immediateFeedback = ref(null)

// Track per-question response time
let questionStartTime = Date.now()

const totalQuestions = computed(() => questions.value.length)
const currentQuestion = computed(() => questions.value[currentIndex.value] || null)

const currentSelection = computed(() => {
  if (!currentQuestion.value) return null
  return userAnswers.value[currentQuestion.value.id]?.selected_choice || null
})

const isCurrentMarked = computed(() => {
  if (!currentQuestion.value) return false
  return !!userAnswers.value[currentQuestion.value.id]?.is_marked
})

const answeredCount = computed(() => {
  return Object.values(userAnswers.value).filter(a => a.selected_choice).length
})

const markedCount = computed(() => {
  return Object.values(userAnswers.value).filter(a => a.is_marked).length
})

const unansweredCount = computed(() => {
  return Math.max(0, totalQuestions.value - answeredCount.value)
})

function formatDifficulty(d) {
  const map = {
    moderate: 'Moderate',
    difficult: 'Difficult',
    very_difficult: 'Very Difficult',
    board_style_case: 'Board Case'
  }
  return map[d] || d || 'Difficult'
}

function formatQuestionType(t) {
  const map = {
    differential_diagnosis: 'Differential Diagnosis',
    clinical_application: 'Clinical Application',
    best_answer: 'Best Answer',
    case_analysis: 'Case Analysis',
    recall: 'Recall & Criteria',
    recognition: 'Criteria Recognition',
    exception: 'Clinical Exception'
  }
  return map[t] || t || 'Clinical Reasoning'
}

async function loadAttempt() {
  if (!attemptId.value) {
    toast.error('No attempt specified.')
    router.replace('/special-event')
    return
  }

  loading.value = true
  try {
    const res = await specialEventService.getAttempt(attemptId.value)
    if (res.success) {
      if (res.is_completed) {
        // Already completed
        router.replace(`/special-event/results/${attemptId.value}`)
        return
      }

      attempt.value = res.attempt
      questions.value = res.questions
      userAnswers.value = res.answers || {}

      // Find first unanswered question or resume from start
      const firstUnanswered = questions.value.findIndex(q => !userAnswers.value[q.id]?.selected_choice)
      if (firstUnanswered > 0) {
        currentIndex.value = firstUnanswered
      }
      questionStartTime = Date.now()
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load examination trial.')
    router.replace('/special-event')
  } finally {
    loading.value = false
  }
}

async function selectChoice(choiceKey) {
  if (!currentQuestion.value || !attemptId.value) return
  const qId = currentQuestion.value.id

  const elapsed = Math.round((Date.now() - questionStartTime) / 1000)
  questionStartTime = Date.now()

  // Optimistic local update
  if (!userAnswers.value[qId]) {
    userAnswers.value[qId] = { selected_choice: choiceKey, is_marked: false }
  } else {
    userAnswers.value[qId].selected_choice = choiceKey
  }

  savingAnswer.value = true
  try {
    const res = await specialEventService.submitAnswer(attemptId.value, {
      questionId: qId,
      selectedChoice: choiceKey,
      isMarked: userAnswers.value[qId].is_marked,
      responseTime: elapsed
    })
    if (res && res.feedback && res.feedback.immediate) {
      immediateFeedback.value = res.feedback
    } else {
      immediateFeedback.value = null
    }
  } catch (err) {
    toast.error('Answer submission failed: ' + (err.response?.data?.message || 'Network issue'))
  } finally {
    savingAnswer.value = false
  }
}

async function toggleCurrentMark() {
  if (!currentQuestion.value || !attemptId.value) return
  const qId = currentQuestion.value.id
  const nextMarkState = !isCurrentMarked.value

  if (!userAnswers.value[qId]) {
    userAnswers.value[qId] = { selected_choice: null, is_marked: nextMarkState }
  } else {
    userAnswers.value[qId].is_marked = nextMarkState
  }

  try {
    await specialEventService.toggleMark(attemptId.value, qId, nextMarkState)
  } catch (err) {
    toast.error('Could not update review mark.')
  }
}

function prevQuestion() {
  if (currentIndex.value > 0) {
    immediateFeedback.value = null
    currentIndex.value--
    questionStartTime = Date.now()
  }
}

function nextQuestion() {
  if (currentIndex.value < totalQuestions.value - 1) {
    immediateFeedback.value = null
    currentIndex.value++
    questionStartTime = Date.now()
  }
}

function onJumpToQuestion(idx) {
  immediateFeedback.value = null
  currentIndex.value = idx
  questionStartTime = Date.now()
  if (window.innerWidth <= 1024) {
    showNavDrawer.value = false
  }
}

async function submitFinalExam() {
  isSubmitting.value = true
  try {
    const res = await specialEventService.finishAttempt(attemptId.value)
    if (res.success) {
      toast.success('Trial submitted and sealed!')
      router.push(`/special-event/results/${attemptId.value}`)
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to submit examination.')
  } finally {
    isSubmitting.value = false
    showSubmitModal.value = false
  }
}

function onTimeUp() {
  toast.error('Time limit reached! Sealing examination.')
  submitFinalExam()
}

onMounted(() => {
  loadAttempt()
})
</script>

<style scoped>
.special-event-exam-page {
  position: relative;
  min-height: 100vh;
  background: #080512;
  color: #f1f5f9;
  display: flex;
  flex-direction: column;
}

.exam-bg-vignette {
  position: fixed;
  inset: 0;
  background: radial-gradient(ellipse at 50% 10%, rgba(88, 28, 135, 0.15) 0%, rgba(8, 5, 18, 0.95) 70%, #030206 100%);
  pointer-events: none;
  z-index: 0;
}

.exam-loader-screen {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem;
  z-index: 10;
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.loader-title {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem;
  letter-spacing: 2px;
  color: #c4b5fd;
}

.loader-sub {
  font-size: 0.85rem;
  color: #94a3b8;
}

.exam-viewport {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Header Bar */
.exam-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.65rem 1.25rem;
  background: rgba(14, 10, 26, 0.95);
  border-bottom: 2px solid rgba(139, 92, 246, 0.4);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
  position: sticky;
  top: 0;
  z-index: 20;
  gap: 1rem;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.trial-tag {
  background: rgba(139, 92, 246, 0.25);
  border: 1px solid rgba(139, 92, 246, 0.5);
  color: #c4b5fd;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
}

.question-tracker {
  font-family: 'Cinzel', serif;
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.q-label {
  font-size: 0.85rem;
  color: #94a3b8;
}
.q-current {
  font-size: 1.4rem;
  font-weight: 800;
  color: #f8fafc;
}
.q-total {
  font-size: 0.85rem;
  color: #64748b;
}

.header-center {
  flex: 1;
  max-width: 380px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-pill-group {
  display: flex;
  gap: 0.4rem;
}

.stat-badge {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(26, 20, 48, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.2);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.65rem;
  color: #cbd5e1;
}

.stat-badge .dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}
.stat-badge.answered .dot { background: #3b82f6; }
.stat-badge.pending .dot { background: #64748b; }
.stat-badge.marked .dot { background: #f59e0b; }

.drawer-toggle-btn {
  background: rgba(43, 31, 75, 0.9);
  border: 1px solid #8b5cf6;
  color: #e2e8f0;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.72rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}
.drawer-toggle-btn:hover,
.drawer-toggle-btn.active {
  background: #8b5cf6;
  color: #fff;
}

/* Main Canvas */
.exam-main-canvas {
  flex: 1;
  display: flex;
  padding: 1.5rem 1rem 3rem;
  max-width: 1300px;
  margin: 0 auto;
  width: 100%;
  gap: 1.5rem;
  position: relative;
}

.exam-container {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
}

.gothic-panel {
  background: rgba(16, 12, 30, 0.95);
  border: 1.5px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.75rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Question Meta Bar */
.question-meta-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.2);
  padding-bottom: 0.85rem;
}

.meta-tags-left,
.meta-tags-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.domain-tag {
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #c4b5fd;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 600;
}

.part-tag {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #94a3b8;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  font-size: 0.72rem;
}

.difficulty-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  text-transform: uppercase;
}
.difficulty-pill.moderate { background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; color: #93c5fd; }
.difficulty-pill.difficult { background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fde047; }
.difficulty-pill.very_difficult { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
.difficulty-pill.board_style_case { background: rgba(168, 85, 247, 0.25); border: 1px solid #a855f7; color: #e9d5ff; }

.type-pill {
  font-size: 0.7rem;
  color: #cbd5e1;
  background: rgba(51, 65, 85, 0.4);
  border: 1px solid rgba(148, 163, 184, 0.25);
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
}

/* Question Stem */
.question-stem-box {
  padding: 0.5rem 0;
}

.question-stem-text {
  font-size: 1.05rem;
  line-height: 1.7;
  color: #f8fafc;
  letter-spacing: 0.2px;
}

/* Choices */
.choices-container {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.choice-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(24, 18, 44, 0.7);
  border: 1.5px solid rgba(71, 85, 105, 0.4);
  border-radius: 8px;
  padding: 1rem 1.15rem;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.choice-item:hover {
  background: rgba(43, 31, 75, 0.85);
  border-color: #8b5cf6;
  transform: translateX(3px);
}

.choice-item.is-selected {
  background: rgba(91, 33, 182, 0.35);
  border-color: #a78bfa;
  box-shadow: 0 0 15px rgba(139, 92, 246, 0.4), inset 0 0 10px rgba(139, 92, 246, 0.2);
}

.choice-indicator {
  width: 34px;
  height: 34px;
  border-radius: 6px;
  background: rgba(30, 24, 55, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}

.choice-key {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #c4b5fd;
}

.choice-item.is-selected .choice-indicator {
  background: #8b5cf6;
  border-color: #c4b5fd;
  box-shadow: 0 0 10px rgba(139, 92, 246, 0.7);
}

.choice-item.is-selected .choice-key {
  color: #fff;
}

.choice-text {
  flex: 1;
  font-size: 0.92rem;
  line-height: 1.5;
  color: #e2e8f0;
}

.selected-checkmark {
  color: #34d399;
  font-size: 1.2rem;
  font-weight: bold;
}

/* Footer Controls */
.exam-footer-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1rem 1.25rem;
  background: rgba(14, 10, 26, 0.95);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 8px;
}

.controls-left,
.controls-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.ctrl-btn {
  padding: 0.65rem 1.15rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.prev-btn,
.next-btn {
  background: rgba(30, 24, 55, 0.85);
  border-color: rgba(139, 92, 246, 0.4);
  color: #f1f5f9;
}
.prev-btn:hover:not(:disabled),
.next-btn:hover:not(:disabled) {
  background: rgba(55, 45, 95, 0.95);
  border-color: #a78bfa;
}
.prev-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.mark-btn {
  background: rgba(30, 25, 45, 0.8);
  border-color: rgba(245, 158, 11, 0.3);
  color: #fbbf24;
}
.mark-btn:hover {
  background: rgba(245, 158, 11, 0.15);
  border-color: #f59e0b;
}
.mark-btn.is-marked {
  background: rgba(245, 158, 11, 0.25);
  border-color: #fbbf24;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
}

.submit-btn {
  background: linear-gradient(135deg, #7c3aed 0%, #4338ca 100%);
  border-color: #a78bfa;
  color: #fff;
  box-shadow: 0 0 15px rgba(124, 58, 237, 0.5);
}
.submit-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 0 25px rgba(124, 58, 237, 0.8);
}

/* Side Drawer */
.exam-side-drawer {
  width: 320px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: rgba(18, 14, 34, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
}

.drawer-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #c4b5fd;
}

.drawer-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1rem;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .exam-side-drawer {
    position: fixed;
    right: 0;
    top: 60px;
    bottom: 0;
    z-index: 100;
    width: 300px;
    background: #0d0a1b;
    box-shadow: -5px 0 30px rgba(0, 0, 0, 0.9);
    padding: 1rem;
  }
}

/* Immediate Bloom Feedback Box (Section 52) */
.immediate-feedback-box {
  margin-top: 1rem;
  background: rgba(22, 16, 44, 0.95);
  border: 1.5px solid rgba(168, 85, 247, 0.4);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  animation: fadeIn 0.3s ease;
}

.feedback-badge-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.recorded-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #a78bfa;
  letter-spacing: 0.5px;
}

.result-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

.result-tag.correct {
  background: rgba(16, 185, 129, 0.25);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.5);
}

.result-tag.incorrect {
  background: rgba(239, 68, 68, 0.25);
  color: #f87171;
  border: 1px solid rgba(248, 113, 113, 0.5);
}

.feedback-body-grid {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  font-size: 0.82rem;
}

.feedback-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.feedback-field.skill {
  align-items: flex-start;
}

.fb-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #94a3b8;
  flex-shrink: 0;
}

.fb-val-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  background: rgba(139, 92, 246, 0.3);
  color: #e9d5ff;
  border: 1px solid rgba(168, 85, 247, 0.5);
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  text-transform: uppercase;
}

.fb-desc {
  font-size: 0.82rem;
  color: #cbd5e1;
  line-height: 1.45;
}
</style>
