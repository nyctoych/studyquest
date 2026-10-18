<template>
  <div class="board-exam-simulation-page">
    <!-- Loading Screen -->
    <div v-if="loading" class="exam-loader-screen">
      <div class="loader-paper-card">
        <div class="loader-spinner"></div>
        <h2 class="loader-title">ENTERING BOARD EXAMINATION SESSION</h2>
        <p class="loader-sub">Preparing official questionnaires and OMR answer sheets...</p>
      </div>
    </div>

    <!-- Active Board Exam Layout -->
    <div v-else-if="attempt && currentQuestion" class="board-exam-layout">
      <!-- ── TOP OFFICIAL PROCTOR BAR ── -->
      <header class="board-top-bar">
        <div class="bar-inner">
          <div class="bar-left">
            <div class="exam-emblem">⚖️</div>
            <div class="exam-header-titles">
              <span class="exam-republic">REPUBLIC OF THE PHILIPPINES • BOARD OF PSYCHOLOGY</span>
              <h1 class="exam-main-title">
                PSYCHOMETRICIAN LICENSURE EXAMINATION (SIMULATION)
              </h1>
              <span class="session-tag" v-if="attempt.session_title">
                {{ attempt.session_title }}
              </span>
            </div>
          </div>

          <div class="bar-center">
            <div class="official-clock-card">
              <span class="clock-icon">⏱️</span>
              <span class="clock-label">TIME REMAINING (SUBJECT):</span>
              <SpecialEventTimer
                :remaining-seconds="activeSubjectRemainingSeconds"
                :total-seconds="10800"
                @time-up="onSubjectTimeUp"
              />
            </div>
          </div>

          <div class="bar-right">
            <div class="proctor-pill" :class="{ 'warning-strike': tabSwitchCount > 0 }">
              <span class="proctor-status-dot"></span>
              <span class="proctor-name">PROCTOR ACTIVE</span>
              <span class="proctor-strikes">
                ({{ tabSwitchCount }}/2 Warnings Used)
              </span>
            </div>

            <!-- Audio Volume Control Widget (Increase/Decrease/Slider/Mute) -->
            <AudioVolumeControl id="exam-bgm-volume-widget" />

            <button class="btn-top-submit" @click="showSubmitModal = true">
              Submit Examination
            </button>
          </div>
        </div>
      </header>

      <!-- ── MAIN SPLIT: 3/4 QUESTIONNAIRE SHEET + 1/4 BUBBLE SHEET ── -->
      <main class="board-exam-split-canvas">
        <!-- ═════════ LEFT: QUESTIONNAIRE SHEET (3/4 WIDTH) ═════════ -->
        <section class="questionnaire-column">
          <div class="questionnaire-paper" :key="currentQuestion.id">
            <!-- Paper Header -->
            <div class="paper-header">
              <div class="paper-header-top">
                <div class="booklet-meta-left">
                  <span class="confidential-stamp">CONFIDENTIAL • TEST BOOKLET</span>
                  <span class="subject-headline">
                    SUBJECT: {{ currentQuestion.subject_name || currentQuestion.domain_name || 'PSYCHOLOGICAL SCIENCE' }}
                  </span>
                </div>
                <div class="booklet-meta-right">
                  <span class="set-indicator">BOOKLET SET A</span>
                  <span class="session-code">
                    {{ attempt.session_number === 2 ? 'DAY 2 — SESSION 2' : 'DAY 1 — SESSION 1' }}
                  </span>
                </div>
              </div>

              <div class="paper-instructions-strip">
                <span class="instruct-title">GENERAL INSTRUCTIONS:</span>
                <span class="instruct-body">
                  Read each question carefully. Select the best answer for each item. Mark your choice on the <strong>Answer Sheet (Bubble Sheet)</strong> on the right. <em>Once you have started, you cannot go back anymore unless you submit.</em>
                </span>
              </div>
            </div>

            <!-- Paper Body: Single Active Item -->
            <div class="paper-body">
              <div class="item-meta-bar">
                <div class="item-number-heading">
                  <span class="item-prefix">QUESTION</span>
                  <span class="item-num">{{ currentItemSubjectNo }}</span>
                  <span class="item-denom">OF {{ currentSubjectTotal }}</span>
                  <span class="item-overall-sub">(Overall Item #{{ currentIndex + 1 }})</span>
                </div>

                <div class="item-action-tags">
                  <button
                    type="button"
                    class="btn-flag-paper"
                    :class="{ flagged: isCurrentMarked }"
                    @click="toggleCurrentMark"
                    title="Flag question for review"
                  >
                    <span class="flag-icon">★</span>
                    <span>{{ isCurrentMarked ? 'Flagged for Review' : 'Flag Question' }}</span>
                  </button>
                </div>
              </div>

              <!-- Question Stem -->
              <div class="paper-stem-box">
                <p class="paper-stem-text">
                  {{ currentQuestion.question_text }}
                </p>
              </div>

              <!-- Choices (A, B, C, D) Printed Options -->
              <div class="paper-choices-list">
                <div
                  v-for="choiceKey in ['A', 'B', 'C', 'D']"
                  :key="choiceKey"
                  class="paper-choice-item"
                  :class="{
                    'is-selected': currentSelection === choiceKey,
                    'is-saving': savingAnswer && currentSelection === choiceKey
                  }"
                  @click="selectChoice(choiceKey, null, 'questionnaire')"
                >
                  <div class="choice-letter-badge">
                    <span>{{ choiceKey }}</span>
                  </div>
                  <div class="choice-text-wrap">
                    {{ currentQuestion['choice_' + choiceKey.toLowerCase()] }}
                  </div>
                  <div v-if="currentSelection === choiceKey" class="choice-shaded-check">
                    <span class="pencil-dot">●</span> Shaded on Bubble Sheet
                  </div>
                </div>
              </div>

              <!-- Immediate Feedback (if active) -->
              <div v-if="immediateFeedback && immediateFeedback.immediate" class="paper-feedback-box">
                <span class="fb-tag" :class="immediateFeedback.is_correct ? 'correct' : 'incorrect'">
                  {{ immediateFeedback.question_result }}
                </span>
                <span class="fb-skill">{{ immediateFeedback.cognitive_skill }}</span>
              </div>
            </div>

            <!-- Paper Footer: SUBJECT-AWARE NAVIGATION -->
            <footer class="paper-footer">
              <button
                type="button"
                class="paper-nav-btn back-btn"
                :disabled="currentIndex === 0 || isFirstQuestionOfSubject"
                @click="prevQuestion"
              >
                ◀ Back
              </button>

              <div class="paper-pagination-display">
                <span class="page-label">Subject Question</span>
                <span class="page-current">{{ currentItemSubjectNo }}</span>
                <span class="page-sep">/</span>
                <span class="page-total">{{ currentSubjectTotal }}</span>
                <span class="page-overall-tag">(Exam {{ currentIndex + 1 }}/{{ totalQuestions }})</span>
              </div>

              <!-- When on the last question of current subject and next subject exists -->
              <button
                v-if="isLastQuestionOfSubject && hasNextSubject"
                type="button"
                class="paper-nav-btn next-subject-btn"
                @click="openSubjectTransitionModal"
              >
                <span>Proceed to Next Subject ▶</span>
              </button>

              <!-- When on the very last question of entire examination -->
              <button
                v-else-if="currentIndex >= totalQuestions - 1"
                type="button"
                class="paper-nav-btn submit-exam-trigger-btn"
                @click="showSubmitModal = true"
              >
                <span>Submit Examination ⚖️</span>
              </button>

              <!-- Standard next question within current subject -->
              <button
                v-else
                type="button"
                class="paper-nav-btn next-btn"
                @click="nextQuestion"
              >
                Next ▶
              </button>
            </footer>
          </div>
        </section>

        <!-- ═════════ RIGHT: BUBBLE SHEET (1/4 WIDTH) ═════════ -->
        <aside class="bubble-sheet-column">
          <div class="omr-card">
            <!-- Bubble Sheet Header -->
            <div class="omr-card-header">
              <div class="omr-header-banner">
                <span class="omr-barcode">||||||||||||||||||||||||</span>
                <div class="omr-header-text">
                  <span class="omr-doc-type">OFFICIAL ANSWER SHEET</span>
                  <h3 class="omr-doc-title">OMR BUBBLE SHEET</h3>
                </div>
              </div>

              <!-- Subject Tabs: Each Subject has its own bubble sheet (locked until previous finished) -->
              <div class="subject-tabs-rack" v-if="subjects.length > 0">
                <div class="subject-tabs-label">SUBJECT BUBBLE SHEETS:</div>
                <div class="subject-tabs-buttons">
                  <button
                    v-for="(subj, sIdx) in subjects"
                    :key="subj.name"
                    type="button"
                    class="subj-tab-btn"
                    :class="{
                      active: activeSubjectIndex === sIdx,
                      'is-locked': !isSubjectUnlocked(sIdx)
                    }"
                    @click="selectSubjectTab(sIdx)"
                    :title="!isSubjectUnlocked(sIdx) ? `Subject Locked: Finish Subject ${sIdx} first` : ''"
                  >
                    <span class="subj-tab-name">
                      <span v-if="!isSubjectUnlocked(sIdx)" class="tab-lock-icon">🔒</span>
                      {{ subj.name }}
                    </span>
                    <span class="subj-tab-metric">
                      <template v-if="isSubjectUnlocked(sIdx)">
                        {{ getSubjectShadedCount(subj) }}/{{ subj.questions.length }}
                      </template>
                      <template v-else>
                        Locked
                      </template>
                    </span>
                  </button>
                </div>
              </div>

              <!-- Active Subject Indicator Banner -->
              <div class="active-subject-strip" v-if="currentSubject">
                <div class="strip-left">
                  <span class="strip-title">{{ currentSubject.name }}</span>
                  <span class="strip-items-range">
                    Items 1 - {{ currentSubject.questions.length }}
                  </span>
                </div>
                <div class="strip-progress">
                  <strong>{{ getSubjectShadedCount(currentSubject) }}</strong> / {{ currentSubject.questions.length }} Shaded
                </div>
              </div>

              <div class="omr-instructions-pill">
                <span>Shade bubble completely with No. 2 pencil:</span>
                <span class="sample-bubbles">
                  <span class="bubble-sample filled">A</span> Correct
                  <span class="bubble-sample hollow">B</span> Unshaded
                </span>
              </div>
            </div>

            <!-- Scrollable OMR Bubble Grid for Active Subject -->
            <div class="omr-card-scroll" ref="bubbleScrollContainer">
              <div class="omr-grid-table">
                <div class="omr-grid-header-row">
                  <span class="col-item-no">NO.</span>
                  <span class="col-bubbles-header">SHADE ONE CHOICE</span>
                </div>

                <div
                  v-for="q in (currentSubject ? currentSubject.questions : questions)"
                  :key="q.id"
                  :id="'bubble-row-' + q.globalIndex"
                  class="omr-item-row"
                  :class="{
                    'is-active': currentIndex === q.globalIndex,
                    'is-shaded': !!userAnswers[q.id]?.selected_choice,
                    'is-flagged': !!userAnswers[q.id]?.is_marked
                  }"
                >
                  <div
                    class="omr-num-badge"
                    @click="jumpToQuestion(q.globalIndex)"
                    title="Jump to this question on questionnaire"
                  >
                    <span class="num-digit">{{ q.subjectQuestionNo }}</span>
                    <span v-if="userAnswers[q.id]?.is_marked" class="flag-icon-star" title="Flagged">★</span>
                  </div>

                  <div class="omr-bubble-cluster">
                    <button
                      v-for="choiceKey in ['A', 'B', 'C', 'D']"
                      :key="choiceKey"
                      type="button"
                      class="scantron-bubble"
                      :class="{
                        'is-shaded': userAnswers[q.id]?.selected_choice === choiceKey
                      }"
                      @click="bubbleChoice(q, choiceKey)"
                      :title="`Shade (${choiceKey}) for Item ${q.subjectQuestionNo}`"
                    >
                      <span class="bubble-char">{{ choiceKey }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bubble Sheet Bottom Summary -->
            <div class="omr-card-footer">
              <div class="omr-summary-row">
                <div class="stat-bubble-item">
                  <span class="stat-num text-blue">{{ answeredCount }}</span>
                  <span class="stat-lbl">Shaded</span>
                </div>
                <div class="stat-bubble-item">
                  <span class="stat-num text-slate">{{ unansweredCount }}</span>
                  <span class="stat-lbl">Pending</span>
                </div>
                <div class="stat-bubble-item">
                  <span class="stat-num text-amber">{{ markedCount }}</span>
                  <span class="stat-lbl">Flagged</span>
                </div>
              </div>

              <button class="btn-omr-submit" @click="showSubmitModal = true">
                Submit Answer Sheet
              </button>
            </div>
          </div>
        </aside>
      </main>

      <!-- ── SUBMIT CONFIRMATION MODAL ── -->
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

      <!-- ── SUBJECT TRANSITION / COMPLETION MODAL ── -->
      <div v-if="showSubjectTransitionModal && nextSubject" class="subject-modal-overlay animate-fade-in">
        <div class="subject-modal-card animate-scale-up">
          <div class="sub-modal-emblem">⚖️</div>
          <div class="sub-modal-stamp">PRC BOARD EXAMINATION • SECTION TRANSITION</div>
          <h2 class="sub-modal-title">
            {{ currentSubjectUnshadedCount === 0 ? 'Subject Completed!' : 'Subject Transition Confirmation' }}
          </h2>
          <p class="sub-modal-desc">
            You are finishing <strong>{{ currentSubject?.name }}</strong> and advancing to the next booklet section: <strong>{{ nextSubject?.name }}</strong>.
          </p>

          <!-- Subject Status Summary Card -->
          <div class="sub-modal-status-box" :class="currentSubjectUnshadedCount === 0 ? 'status-complete' : 'status-pending'">
            <div class="status-box-header">
              <span class="status-subject-name">{{ currentSubject?.name }}</span>
              <span class="status-tag" :class="currentSubjectUnshadedCount === 0 ? 'tag-complete' : 'tag-pending'">
                {{ currentSubjectUnshadedCount === 0 ? '✓ All Items Shaded' : `${currentSubjectUnshadedCount} Unshaded Item(s)` }}
              </span>
            </div>
            <div class="status-metrics-row">
              <div class="status-metric">
                <span class="sm-val text-blue">{{ getSubjectShadedCount(currentSubject) }}</span>
                <span class="sm-lbl">Shaded Bubbles</span>
              </div>
              <div class="status-metric">
                <span class="sm-val" :class="currentSubjectUnshadedCount === 0 ? 'text-green' : 'text-amber'">
                  {{ currentSubjectUnshadedCount }}
                </span>
                <span class="sm-lbl">Unshaded Items</span>
              </div>
              <div class="status-metric">
                <span class="sm-val">{{ currentSubject?.questions.length }}</span>
                <span class="sm-lbl">Total Subject Items</span>
              </div>
            </div>
          </div>

          <!-- Advisory if unshaded items remain -->
          <div v-if="currentSubjectUnshadedCount > 0" class="sub-modal-warning-callout">
            <span class="warn-icon">⚠️</span>
            <p class="warn-text">
              In the Licensure Examination, leaving questions unshaded is discouraged. You still have <strong>{{ currentSubjectUnshadedCount }} unshaded item(s)</strong> in this subject. Would you like to review them first, or proceed to <strong>{{ nextSubject?.name }}</strong>?
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="sub-modal-actions">
            <button
              v-if="currentSubjectUnshadedCount > 0"
              type="button"
              class="sub-modal-btn secondary"
              @click="reviewUnshadedInCurrentSubject"
            >
              🔍 Review Unshaded Items
            </button>
            <button
              type="button"
              class="sub-modal-btn primary"
              @click="proceedToNextSubject"
            >
              {{ currentSubjectUnshadedCount === 0 ? `Begin Next Subject: ${nextSubject?.name} ▶` : `Confirm & Proceed to Next Subject ▶` }}
            </button>
            <button
              type="button"
              class="sub-modal-btn text-dismiss"
              @click="showSubjectTransitionModal = false"
            >
              Cancel & Stay on Current Item
            </button>
          </div>
        </div>
      </div>

      <!-- ── ANTI-CHEATING PROCTOR WARNING MODAL (STRIKES 1 & 2) ── -->
      <div v-if="showWarningModal" class="proctor-modal-overlay animate-fade-in">
        <div class="proctor-modal-card animate-shake">
          <div class="proctor-icon-circle" :class="tabSwitchCount === 2 ? 'critical' : 'warning'">
            <span v-if="tabSwitchCount === 1">⚠️</span>
            <span v-else>🚨</span>
          </div>

          <div class="proctor-badge" :class="tabSwitchCount === 2 ? 'badge-critical' : 'badge-warning'">
            {{ tabSwitchCount === 1 ? 'PROCTORING WARNING 1 OF 2' : 'FINAL WARNING (2 OF 2)' }}
          </div>

          <h2 class="proctor-title">
            {{ tabSwitchCount === 1 ? 'Tab Switch / Departure Detected' : 'CRITICAL: Final Warning' }}
          </h2>

          <div class="proctor-body">
            <p class="proctor-desc">
              You navigated away or switched tabs from the active board examination window.
            </p>
            <div class="proctor-limit-box" :class="tabSwitchCount === 2 ? 'box-danger' : ''">
              <div class="strike-tracker">
                <span class="strike-dot filled">● Warning 1</span>
                <span class="strike-dot" :class="{ filled: tabSwitchCount >= 2 }">● Warning 2</span>
                <span class="strike-dot limit">✕ 3rd Violation: Disqualified</span>
              </div>
              <p class="proctor-warning-text" v-if="tabSwitchCount === 1">
                You have <strong>1 warning remaining</strong>. If you switch tabs or leave this window again, the <strong>3rd violation will automatically end the test for Cheating</strong>.
              </p>
              <p class="proctor-warning-text text-danger" v-else>
                <strong>ATTENTION:</strong> This is your <strong>FINAL WARNING</strong>. Any subsequent tab change or window departure will immediately terminate this examination and mark it as <strong>"Cheating"</strong>.
              </p>
            </div>
          </div>

          <div class="proctor-actions">
            <button class="proctor-ack-btn" :class="tabSwitchCount === 2 ? 'btn-urgent' : ''" @click="acknowledgeWarning">
              I Understand & Resume Examination
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── DISQUALIFICATION TERMINATION SCREEN (STRIKE 3) ── -->
    <div v-if="isDisqualified" class="disqualified-full-screen animate-fade-in">
      <div class="disqualified-card">
        <div class="disq-icon-wrap">
          <span class="disq-ban-icon">🚫</span>
        </div>

        <div class="disq-badge">EXAMINATION TERMINATED</div>
        <h1 class="disq-title">TEST NOT FINISHED: DISQUALIFIED</h1>

        <div class="disq-reason-callout">
          <span class="reason-tag">REASON FOR TERMINATION</span>
          <div class="reason-val">{{ disqualificationReason || 'Cheating' }}</div>
          <p class="reason-sub">
            Exceeded maximum allowed warnings (3 tab switch or window departures detected).
          </p>
        </div>

        <div class="disq-summary-grid">
          <div class="disq-stat">
            <span class="ds-lbl">Violations Recorded</span>
            <span class="ds-val text-red">{{ tabSwitchCount }} / 3 (Limit Reached)</span>
          </div>
          <div class="disq-stat">
            <span class="ds-lbl">Outcome</span>
            <span class="ds-val text-red">Disqualified</span>
          </div>
          <div class="disq-stat">
            <span class="ds-lbl">Final Score</span>
            <span class="ds-val">0 / {{ totalQuestions }}</span>
          </div>
        </div>

        <p class="disq-notice">
          Your examination session has been automatically sealed and permanently recorded as unfinished due to <strong>Cheating</strong>. This attempt cannot be resumed.
        </p>

        <div class="disq-btn-group">
          <button class="disq-btn secondary" @click="router.push('/special-event')">
            Return to Examination Hub
          </button>
          <button class="disq-btn primary" @click="router.push(`/special-event/results/${attemptId}`)">
            View Examination Record →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import SpecialEventTimer from '../components/special-event/SpecialEventTimer.vue'
import SpecialEventSubmitModal from '../components/special-event/SpecialEventSubmitModal.vue'
import { useToastStore } from '../stores/toast'
import { useGameStore } from '../stores/game'
import AudioVolumeControl from '../components/ui/AudioVolumeControl.vue'

const route = useRoute()
const router = useRouter()
const toast = useToastStore()
const gameStore = useGameStore()

const loading = ref(true)
const attemptId = ref(Number(route.query.attemptId) || 0)
const attempt = ref(null)
const questions = ref([])
const userAnswers = ref({})
const currentIndex = ref(0)
const activeSubjectIndex = ref(0)
const bubbleScrollContainer = ref(null)

const showSubmitModal = ref(false)
const showSubjectTransitionModal = ref(false)
const maxUnlockedSubjectIndex = ref(0)
const isSubmitting = ref(false)
const savingAnswer = ref(false)
const immediateFeedback = ref(null)

// Anti-Cheating Detector State
const tabSwitchCount = ref(0)
const isDisqualified = ref(false)
const disqualificationReason = ref('Cheating')
const showWarningModal = ref(false)
const examEnded = ref(false)
let lastViolationTime = 0

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

// Group questions by subject so each subject has its own bubble sheet with subject-based numbering
const subjects = computed(() => {
  const map = new Map()
  questions.value.forEach((q, idx) => {
    const sName = q.subject_name || q.domain_name || 'Psychological Science'
    if (!map.has(sName)) {
      map.set(sName, {
        name: sName,
        questions: [],
        startIndex: idx
      })
    }
    const subjObj = map.get(sName)
    const localNo = subjObj.questions.length + 1
    subjObj.questions.push({
      ...q,
      globalIndex: idx,
      subjectQuestionNo: localNo
    })
  })
  return Array.from(map.values())
})

const currentSubject = computed(() => {
  return subjects.value[activeSubjectIndex.value] || subjects.value[0] || null
})

const currentItemSubjectNo = computed(() => {
  if (!currentSubject.value) return currentIndex.value + 1
  const found = currentSubject.value.questions.find(q => q.globalIndex === currentIndex.value)
  return found?.subjectQuestionNo || (currentIndex.value - currentSubject.value.startIndex + 1)
})

const currentSubjectTotal = computed(() => {
  return currentSubject.value?.questions?.length || totalQuestions.value
})

const isFirstQuestionOfSubject = computed(() => {
  if (!currentSubject.value) return currentIndex.value === 0
  return currentIndex.value === currentSubject.value.startIndex
})

const isLastQuestionOfSubject = computed(() => {
  if (!currentSubject.value) return currentIndex.value === totalQuestions.value - 1
  return currentIndex.value === currentSubject.value.startIndex + currentSubject.value.questions.length - 1
})

const hasNextSubject = computed(() => {
  return activeSubjectIndex.value < subjects.value.length - 1
})

const nextSubject = computed(() => {
  if (hasNextSubject.value) {
    return subjects.value[activeSubjectIndex.value + 1] || null
  }
  return null
})

const currentSubjectUnshadedCount = computed(() => {
  if (!currentSubject.value) return 0
  const shaded = getSubjectShadedCount(currentSubject.value)
  return Math.max(0, currentSubject.value.questions.length - shaded)
})

function isSubjectUnlocked(sIdx) {
  if (sIdx <= 0) return true
  if (sIdx <= maxUnlockedSubjectIndex.value) return true
  const subj = subjects.value[sIdx]
  if (subj && subj.questions.some(q => userAnswers.value[q.id]?.selected_choice)) {
    return true
  }
  return false
}

function unlockSubject(sIdx) {
  if (sIdx > maxUnlockedSubjectIndex.value) {
    maxUnlockedSubjectIndex.value = sIdx
    try {
      localStorage.setItem(`studyquest_unlocked_subject_${attemptId.value}`, String(sIdx))
    } catch (e) {}
  }
}

// ── 3 HOURS PER SUBJECT TIMER ──
const SUBJECT_TIME_LIMIT_SECONDS = 180 * 60 // 10,800 seconds (3 hours)
const subjectTimerTick = ref(Date.now())

function getSubjectStartTime(sIdx) {
  if (sIdx === 0) {
    if (attempt.value?.started_at) {
      const serverStarted = new Date(attempt.value.started_at.replace(/-/g, '/')).getTime()
      if (!isNaN(serverStarted)) return serverStarted
    }
    const localStart = localStorage.getItem(`studyquest_subj_start_${attemptId.value}_0`)
    if (localStart) return parseInt(localStart, 10)
    const now = Date.now()
    try {
      localStorage.setItem(`studyquest_subj_start_${attemptId.value}_0`, String(now))
    } catch (e) {}
    return now
  }

  const saved = localStorage.getItem(`studyquest_subj_start_${attemptId.value}_${sIdx}`)
  if (saved) return parseInt(saved, 10)
  return null
}

const activeSubjectRemainingSeconds = computed(() => {
  const _tick = subjectTimerTick.value
  const sIdx = activeSubjectIndex.value
  const startTime = getSubjectStartTime(sIdx)

  if (!startTime) {
    return SUBJECT_TIME_LIMIT_SECONDS
  }

  const elapsedSec = Math.floor((Date.now() - startTime) / 1000)
  return Math.max(0, SUBJECT_TIME_LIMIT_SECONDS - elapsedSec)
})

function onSubjectTimeUp() {
  if (hasNextSubject.value) {
    toast.warning(`Time limit of 3 hours for ${currentSubject.value?.name || 'Subject'} has expired! Transitioning to next subject.`)
    proceedToNextSubject()
  } else {
    toast.error('Time limit of 3 hours for this subject has expired! Submitting examination.')
    submitFinalExam()
  }
}

function getSubjectShadedCount(subj) {
  if (!subj || !subj.questions) return 0
  return subj.questions.filter(q => userAnswers.value[q.id]?.selected_choice).length
}

function selectSubjectTab(idx) {
  if (!isSubjectUnlocked(idx)) {
    const prevSubj = subjects.value[idx - 1]
    const unshaded = prevSubj ? (prevSubj.questions.length - getSubjectShadedCount(prevSubj)) : 0
    toast.warning(`Subject ${idx + 1} (${subjects.value[idx]?.name || ''}) is locked! You must finish Subject ${idx} (${prevSubj?.name || ''}) first (${unshaded} unshaded items remaining).`)
    return
  }
  activeSubjectIndex.value = idx
  const subj = subjects.value[idx]
  if (subj && subj.questions.length > 0) {
    if (currentIndex.value < subj.startIndex || currentIndex.value >= subj.startIndex + subj.questions.length) {
      jumpToQuestion(subj.startIndex)
    }
  }
}

function syncSubjectToCurrentIndex() {
  const sIdx = subjects.value.findIndex(s =>
    currentIndex.value >= s.startIndex && currentIndex.value < s.startIndex + s.questions.length
  )
  if (sIdx !== -1 && activeSubjectIndex.value !== sIdx) {
    activeSubjectIndex.value = sIdx
  }
}

function formatDifficulty(d) {
  const map = {
    moderate: 'Moderate',
    difficult: 'Difficult',
    very_difficult: 'Very Difficult',
    board_style_case: 'Board Case'
  }
  return map[d] || d || 'Standard'
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
      if (res.attempt?.status === 'disqualified') {
        attempt.value = res.attempt
        isDisqualified.value = true
        examEnded.value = true
        disqualificationReason.value = res.attempt.disqualification_reason || 'Cheating'
        tabSwitchCount.value = res.attempt.tab_switch_count || 3
        questions.value = res.questions || []
        loading.value = false
        return
      }

      if (res.is_completed) {
        router.replace(`/special-event/results/${attemptId.value}`)
        return
      }

      attempt.value = res.attempt
      questions.value = res.questions
      userAnswers.value = res.answers || {}
      tabSwitchCount.value = Number(res.attempt?.tab_switch_count) || 0

      // Calculate highest unlocked subject based on storage and recorded answers
      let highestUnlocked = 0
      try {
        const saved = parseInt(localStorage.getItem(`studyquest_unlocked_subject_${attemptId.value}`) || '0', 10)
        if (!isNaN(saved)) {
          highestUnlocked = Math.max(highestUnlocked, saved)
        }
      } catch (e) {}

      subjects.value.forEach((s, idx) => {
        if (s.questions.some(q => res.answers?.[q.id]?.selected_choice)) {
          highestUnlocked = Math.max(highestUnlocked, idx)
        }
      })
      maxUnlockedSubjectIndex.value = highestUnlocked

      // Find first unanswered question within unlocked subjects
      let targetIndex = 0
      const firstUnanswered = questions.value.findIndex(q => !userAnswers.value[q.id]?.selected_choice)
      if (firstUnanswered !== -1) {
        const subjIdx = subjects.value.findIndex(s => firstUnanswered >= s.startIndex && firstUnanswered < s.startIndex + s.questions.length)
        if (subjIdx !== -1 && subjIdx <= maxUnlockedSubjectIndex.value) {
          targetIndex = firstUnanswered
        } else {
          targetIndex = subjects.value[maxUnlockedSubjectIndex.value]?.startIndex || 0
        }
      }
      currentIndex.value = targetIndex
      syncSubjectToCurrentIndex()
      questionStartTime = Date.now()
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to load examination trial.')
    router.replace('/special-event')
  } finally {
    loading.value = false
  }
}

async function selectChoice(choiceKey, targetQId = null, source = 'questionnaire') {
  if (isDisqualified.value || examEnded.value) return
  const qId = targetQId || (currentQuestion.value ? currentQuestion.value.id : null)
  if (!qId || !attemptId.value) return

  const elapsed = Math.round((Date.now() - questionStartTime) / 1000)
  questionStartTime = Date.now()

  // Re-clicking the already selected choice acts as erasure
  const currentVal = userAnswers.value[qId]?.selected_choice || null
  const isErasing = (currentVal === choiceKey)
  const nextChoice = isErasing ? null : choiceKey

  // Optimistic local update
  if (!userAnswers.value[qId]) {
    userAnswers.value[qId] = { selected_choice: nextChoice, is_marked: false }
  } else {
    userAnswers.value[qId].selected_choice = nextChoice
  }

  savingAnswer.value = true
  try {
    const res = await specialEventService.submitAnswer(attemptId.value, {
      questionId: qId,
      selectedChoice: nextChoice,
      isMarked: userAnswers.value[qId].is_marked,
      responseTime: elapsed
    })
    if (res && res.feedback && res.feedback.immediate && nextChoice !== null) {
      immediateFeedback.value = res.feedback
    } else {
      immediateFeedback.value = null
    }

    if (isErasing) {
      toast.info('Answer erased.')
    }
  } catch (err) {
    toast.error('Answer submission failed: ' + (err.response?.data?.message || 'Network issue'))
  } finally {
    savingAnswer.value = false
  }

  // When user clicked an answer option on questionnaire, auto next the question
  if (!isErasing && source === 'questionnaire') {
    setTimeout(() => {
      nextQuestion()
    }, 280)
  }
}

async function bubbleChoice(q, choiceKey) {
  if (isDisqualified.value || examEnded.value) return
  const targetSubjIdx = subjects.value.findIndex(s => q.globalIndex >= s.startIndex && q.globalIndex < s.startIndex + s.questions.length)
  if (targetSubjIdx !== -1 && !isSubjectUnlocked(targetSubjIdx)) {
    toast.warning(`Subject ${targetSubjIdx + 1} (${subjects.value[targetSubjIdx]?.name}) is locked. Please finish Subject 1 first.`)
    return
  }
  if (currentIndex.value !== q.globalIndex) {
    currentIndex.value = q.globalIndex
    questionStartTime = Date.now()
  }
  await selectChoice(choiceKey, q.id, 'bubble_sheet')
}

async function toggleCurrentMark() {
  if (!currentQuestion.value || !attemptId.value || isDisqualified.value || examEnded.value) return
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
    toast.error('Could not update review flag.')
  }
}

function prevQuestion() {
  if (isFirstQuestionOfSubject.value) return
  if (currentIndex.value > 0) {
    immediateFeedback.value = null
    currentIndex.value--
    questionStartTime = Date.now()
  }
}

function nextQuestion() {
  if (isLastQuestionOfSubject.value && hasNextSubject.value) {
    openSubjectTransitionModal()
    return
  }
  if (currentIndex.value < totalQuestions.value - 1) {
    immediateFeedback.value = null
    currentIndex.value++
    questionStartTime = Date.now()
  }
}

function jumpToQuestion(idx) {
  const targetSubjIdx = subjects.value.findIndex(s => idx >= s.startIndex && idx < s.startIndex + s.questions.length)
  if (targetSubjIdx !== -1 && !isSubjectUnlocked(targetSubjIdx)) {
    toast.warning(`Subject ${targetSubjIdx + 1} (${subjects.value[targetSubjIdx]?.name}) is locked. Please finish Subject 1 first.`)
    return
  }
  immediateFeedback.value = null
  currentIndex.value = idx
  questionStartTime = Date.now()
}

function openSubjectTransitionModal() {
  showSubjectTransitionModal.value = true
}

function proceedToNextSubject() {
  const nextIdx = activeSubjectIndex.value + 1
  if (nextIdx < subjects.value.length) {
    try {
      localStorage.setItem(`studyquest_subj_start_${attemptId.value}_${nextIdx}`, String(Date.now()))
    } catch (e) {}
    unlockSubject(nextIdx)
    activeSubjectIndex.value = nextIdx
    const nextSubj = subjects.value[nextIdx]
    if (nextSubj && nextSubj.questions.length > 0) {
      currentIndex.value = nextSubj.startIndex
      questionStartTime = Date.now()
    }
    showSubjectTransitionModal.value = false
    toast.success(`Commencing Subject ${nextIdx + 1}: ${nextSubj.name}! (3 Hours Allocated)`)
  }
}

function reviewUnshadedInCurrentSubject() {
  showSubjectTransitionModal.value = false
  if (!currentSubject.value) return
  const firstUnshaded = currentSubject.value.questions.find(q => !userAnswers.value[q.id]?.selected_choice)
  if (firstUnshaded) {
    jumpToQuestion(firstUnshaded.globalIndex)
    toast.info(`Reviewing unshaded Item #${firstUnshaded.subjectQuestionNo}`)
  }
}

// Watch question change to sync subject tab & auto-scroll bubble row into view
watch(currentIndex, (newIdx) => {
  syncSubjectToCurrentIndex()
  nextTick(() => {
    const el = document.getElementById('bubble-row-' + newIdx)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  })
})

async function submitFinalExam() {
  isSubmitting.value = true
  try {
    const res = await specialEventService.finishAttempt(attemptId.value)
    if (res.success) {
      toast.success('Examination submitted and sealed!')
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

// ── ANTI-CHEATING DETECTOR & PROCTORING HANDLERS ──
async function handleAntiCheatingViolation(source = 'tab_switch') {
  if (loading.value || isDisqualified.value || examEnded.value || showSubmitModal.value) return
  if (!attempt.value || attempt.value.status !== 'in_progress') return

  const now = Date.now()
  if (now - lastViolationTime < 1500) return
  lastViolationTime = now

  tabSwitchCount.value++
  const currentCount = tabSwitchCount.value

  try {
    await specialEventService.recordTabSwitch(attemptId.value, currentCount)
  } catch (e) {
    console.error('Failed to log tab switch to server', e)
  }

  if (currentCount < 3) {
    showWarningModal.value = true
  } else {
    // 3rd violation: automatically end test and mark as Cheating
    examEnded.value = true
    isDisqualified.value = true
    showWarningModal.value = false
    disqualificationReason.value = 'Cheating'
    try {
      await specialEventService.disqualifyAttempt(attemptId.value, 'Cheating')
      toast.error('Examination ended: Disqualified for Cheating.')
    } catch (e) {
      console.error('Failed to disqualify attempt on server', e)
    }
  }
}

function acknowledgeWarning() {
  showWarningModal.value = false
  questionStartTime = Date.now()
}

function handleVisibilityChange() {
  if (document.hidden) {
    handleAntiCheatingViolation('tab_switch')
  }
}

function handleWindowBlur() {
  if (!document.hasFocus() || document.hidden) {
    handleAntiCheatingViolation('window_blur')
  }
}

function handleBeforeUnload(e) {
  if (!examEnded.value && !isDisqualified.value && attempt.value && attempt.value.status === 'in_progress') {
    e.preventDefault()
    e.returnValue = 'Leaving or exiting this examination page will count as a proctoring violation!'
    return e.returnValue
  }
}

let subjectTimerInterval = null

onMounted(() => {
  loadAttempt()
  subjectTimerInterval = setInterval(() => {
    subjectTimerTick.value = Date.now()
  }, 1000)
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('blur', handleWindowBlur)
  window.addEventListener('beforeunload', handleBeforeUnload)
})

onUnmounted(() => {
  if (subjectTimerInterval) clearInterval(subjectTimerInterval)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('blur', handleWindowBlur)
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>

<style scoped>
/* ── BOARD EXAM SIMULATION PAGE (OFFICIAL WHITE PAPER THEME) ── */
.board-exam-simulation-page {
  min-height: 100vh;
  background-color: #eef2f6;
  color: #0f172a;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  display: flex;
  flex-direction: column;
}

/* ── LOADER SCREEN ── */
.exam-loader-screen {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: #f1f5f9;
}

.loader-paper-card {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 3rem 2.5rem;
  text-align: center;
  max-width: 480px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.loader-spinner {
  width: 44px;
  height: 44px;
  border: 4px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loader-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: 0.02em;
}

.loader-sub {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
}

/* ── TOP PROCTOR BAR (OFFICIAL WHITE / CLEAN DESK HEADER) ── */
.board-top-bar {
  background: #ffffff;
  border-bottom: 2px solid #cbd5e1;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
  position: sticky;
  top: 0;
  z-index: 50;
}

.bar-inner {
  max-width: 1720px;
  margin: 0 auto;
  padding: 0.75rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}

.bar-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.exam-emblem {
  font-size: 1.75rem;
}

.exam-header-titles {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.exam-republic {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #64748b;
  text-transform: uppercase;
}

.exam-main-title {
  font-size: 0.98rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.01em;
}

.session-tag {
  font-size: 0.72rem;
  font-weight: 700;
  color: #2563eb;
}

/* Center Clock */
.bar-center {
  display: flex;
  align-items: center;
}

.official-clock-card {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
}

.clock-icon {
  font-size: 1.1rem;
}

.clock-label {
  font-size: 0.72rem;
  font-weight: 800;
  color: #475569;
  letter-spacing: 0.04em;
}

:deep(.trial-timer-bar) {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  min-width: auto !important;
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  gap: 0.5rem !important;
}

:deep(.trial-timer-bar .timer-label) {
  display: none !important;
}

:deep(.trial-timer-bar .timer-icon) {
  display: none !important;
}

:deep(.trial-timer-bar .timer-digits) {
  font-family: 'JetBrains Mono', 'Courier New', Courier, monospace !important;
  font-size: 1.15rem !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  text-shadow: none !important;
  letter-spacing: 0.05em !important;
}

:deep(.trial-timer-bar .timer-progress-track) {
  width: 90px !important;
  height: 6px !important;
  background: #e2e8f0 !important;
  border: none !important;
  border-radius: 3px !important;
}

:deep(.trial-timer-bar .timer-progress-fill) {
  background: #2563eb !important;
  box-shadow: none !important;
}

:deep(.trial-timer-bar.is-warning .timer-digits) {
  color: #d97706 !important;
}

:deep(.trial-timer-bar.is-danger .timer-digits) {
  color: #dc2626 !important;
}

/* Right Controls */
.bar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.proctor-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  font-size: 0.74rem;
  font-weight: 700;
}

.proctor-pill.warning-strike {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

.proctor-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
}

.proctor-pill.warning-strike .proctor-status-dot {
  background: #ef4444;
}

.btn-top-submit {
  background: #0f172a;
  color: #ffffff;
  border: none;
  padding: 0.55rem 1.15rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-top-submit:hover {
  background: #1e293b;
  transform: translateY(-1px);
}

.exam-music-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #334155;
  padding: 0.42rem 0.85rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
}

.exam-music-toggle-btn:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  color: #0f172a;
  transform: translateY(-1px);
}

.exam-music-toggle-btn.is-muted {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

.exam-music-toggle-btn.is-muted:hover {
  background: #fee2e2;
  border-color: #f87171;
}

.exam-music-icon {
  font-size: 0.85rem;
  line-height: 1;
}

.exam-music-label {
  line-height: 1;
}

/* ── 3/4 & 1/4 SPLIT CANVAS ── */
.board-exam-split-canvas {
  max-width: 1720px;
  width: 100%;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
  box-sizing: border-box;
  display: grid;
  grid-template-columns: 3fr 1fr;
  gap: 1.5rem;
  align-items: start;
}

/* ══════════════════════════════════════════════════════════════
   LEFT: QUESTIONNAIRE SHEET (3/4 WIDTH)
══════════════════════════════════════════════════════════════ */
.questionnaire-column {
  width: 100%;
}

.questionnaire-paper {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Paper Header */
.paper-header {
  border-bottom: 2px solid #0f172a;
  padding: 1.5rem 2rem 1.25rem;
  background: #ffffff;
}

.paper-header-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px dashed #cbd5e1;
}

.booklet-meta-left {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.confidential-stamp {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 3px;
  width: fit-content;
}

.subject-headline {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.booklet-meta-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.2rem;
}

.set-indicator {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.85rem;
  font-weight: 800;
  color: #0f172a;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
}

.session-code {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
}

.paper-instructions-strip {
  padding-top: 0.85rem;
  font-size: 0.82rem;
  color: #334155;
  line-height: 1.5;
  display: flex;
  gap: 0.5rem;
}

.instruct-title {
  font-weight: 800;
  color: #0f172a;
  white-space: nowrap;
}

/* Paper Body */
.paper-body {
  padding: 2.25rem 2.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  min-height: 520px;
}

.item-meta-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.item-number-heading {
  display: flex;
  align-items: baseline;
  gap: 0.45rem;
}

.item-prefix {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #64748b;
}

.item-num {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 1.85rem;
  font-weight: 900;
  color: #0f172a;
  line-height: 1;
}

.item-denom {
  font-size: 0.9rem;
  font-weight: 600;
  color: #94a3b8;
}

.item-action-tags {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.paper-tag {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
}

.paper-tag.bloom {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.paper-tag.diff {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #dbeafe;
}

.btn-flag-paper {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #b45309;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.3rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.15s;
}

.btn-flag-paper:hover {
  background: #fef3c7;
}

.btn-flag-paper.flagged {
  background: #f59e0b;
  color: #ffffff;
  border-color: #d97706;
}

/* Question Stem (Official Printed Type) */
.paper-stem-box {
  background: #fafafa;
  border-left: 4px solid #0f172a;
  padding: 1.25rem 1.5rem;
  border-radius: 0 6px 6px 0;
}

.paper-stem-text {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 1.15rem;
  line-height: 1.75;
  color: #0f172a;
  margin: 0;
  white-space: pre-line;
}

/* Printed Choices List */
.paper-choices-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.paper-choice-item {
  display: flex;
  align-items: center;
  gap: 1.15rem;
  padding: 1.15rem 1.35rem;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
  position: relative;
}

.paper-choice-item:hover {
  border-color: #94a3b8;
  background: #f8fafc;
}

.paper-choice-item.is-selected {
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

.choice-letter-badge {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  border: 2px solid #64748b;
  background: #ffffff;
  color: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: Georgia, 'Times New Roman', serif;
  font-weight: 800;
  font-size: 0.95rem;
  flex-shrink: 0;
  transition: all 0.15s;
}

.paper-choice-item.is-selected .choice-letter-badge {
  background: #1e293b;
  border-color: #0f172a;
  color: #ffffff;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
}

.choice-text-wrap {
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 1.05rem;
  color: #1e293b;
  line-height: 1.55;
  flex: 1;
}

.choice-shaded-check {
  font-size: 0.74rem;
  font-weight: 700;
  color: #2563eb;
  background: #dbeafe;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  white-space: nowrap;
}

.pencil-dot {
  color: #1e293b;
  font-size: 0.85rem;
}

/* Feedback box */
.paper-feedback-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.82rem;
}

.fb-tag {
  font-weight: 800;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.72rem;
}
.fb-tag.correct { background: #dcfce7; color: #15803d; }
.fb-tag.incorrect { background: #fee2e2; color: #b91c1c; }

/* ── QUESTIONNAIRE FOOTER: ONLY BACK AND NEXT BUTTONS ── */
.paper-footer {
  border-top: 2px solid #e2e8f0;
  padding: 1.25rem 2rem;
  background: #fafafa;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.paper-nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.75rem 1.75rem;
  background: #0f172a;
  border: 1px solid #0f172a;
  border-radius: 6px;
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.paper-nav-btn:hover:not(:disabled) {
  background: #1e293b;
  transform: translateY(-1px);
}

.paper-nav-btn:disabled {
  background: #e2e8f0;
  border-color: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
}

.paper-pagination-display {
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
  font-family: Georgia, 'Times New Roman', serif;
}

.page-label {
  font-size: 0.82rem;
  color: #64748b;
  font-weight: 600;
}

.page-current {
  font-size: 1.15rem;
  font-weight: 900;
  color: #0f172a;
}

.page-sep {
  color: #94a3b8;
}

.page-total {
  font-size: 0.95rem;
  font-weight: 700;
  color: #64748b;
}

/* ══════════════════════════════════════════════════════════════
   RIGHT: BUBBLE SHEET (1/4 WIDTH)
══════════════════════════════════════════════════════════════ */
.bubble-sheet-column {
  width: 100%;
  position: sticky;
  top: 75px;
}

.omr-card {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: calc(100vh - 100px);
}

/* OMR Header */
.omr-card-header {
  border-bottom: 2px solid #0f172a;
  padding: 1rem 1.15rem 0.85rem;
  background: #fafafa;
}

.omr-header-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.omr-header-text {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.omr-doc-type {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #64748b;
}

.omr-doc-title {
  font-size: 0.88rem;
  font-weight: 900;
  color: #0f172a;
  margin: 0;
  letter-spacing: 0.02em;
}

.omr-barcode {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.75rem;
  font-weight: 900;
  color: #334155;
  letter-spacing: -2px;
}

/* Subject Tabs for Bubble Sheets */
.subject-tabs-rack {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 0.75rem;
}

.subject-tabs-label {
  font-size: 0.62rem;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 0.05em;
}

.subject-tabs-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.subj-tab-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem 0.65rem;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s;
  text-align: left;
}

.subj-tab-btn:hover {
  background: #e2e8f0;
}

.subj-tab-btn.active {
  background: #0f172a;
  border-color: #0f172a;
  color: #ffffff;
}

.subj-tab-name {
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 170px;
}

.subj-tab-metric {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.1rem 0.4rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
}

.subj-tab-btn:not(.active) .subj-tab-metric {
  background: #e2e8f0;
  color: #475569;
}

/* Active Subject Strip */
.active-subject-strip {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 0.45rem 0.65rem;
  border-radius: 6px;
  margin-bottom: 0.65rem;
}

.strip-left {
  display: flex;
  flex-direction: column;
}

.strip-title {
  font-size: 0.74rem;
  font-weight: 800;
  color: #1e40af;
}

.strip-items-range {
  font-size: 0.64rem;
  color: #3b82f6;
  font-weight: 600;
}

.strip-progress {
  font-size: 0.72rem;
  color: #1e40af;
}

.omr-instructions-pill {
  font-size: 0.64rem;
  color: #475569;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding-top: 0.4rem;
  border-top: 1px dashed #e2e8f0;
}

.sample-bubbles {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 600;
}

.bubble-sample {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.55rem;
  font-weight: 800;
}

.bubble-sample.filled {
  background: #1e293b;
  color: #ffffff;
}

.bubble-sample.hollow {
  border: 1px solid #64748b;
  color: #475569;
  background: #ffffff;
}

/* OMR Bubble Grid (Scrollable) */
.omr-card-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0.85rem;
  background: #ffffff;
}

.omr-grid-table {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.omr-grid-header-row {
  display: flex;
  align-items: center;
  padding: 0.35rem 0.5rem;
  font-size: 0.62rem;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e2e8f0;
}

.col-item-no {
  width: 52px;
}

.col-bubbles-header {
  flex: 1;
  text-align: center;
}

.omr-item-row {
  display: flex;
  align-items: center;
  padding: 0.35rem 0.5rem;
  border-radius: 6px;
  transition: all 0.15s;
  border: 1px solid transparent;
}

.omr-item-row:hover {
  background: #f8fafc;
}

/* Active Highlight Row */
.omr-item-row.is-active {
  background: #fef9c3; /* Yellow highlighter mark */
  border-color: #fde047;
}

.omr-num-badge {
  width: 52px;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
  flex-shrink: 0;
}

.num-digit {
  font-size: 0.8rem;
  font-weight: 700;
  color: #1e293b;
}

.flag-icon-star {
  color: #f59e0b;
  font-size: 0.8rem;
}

.omr-bubble-cluster {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Scantron OMR Bubble */
.scantron-bubble {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1.5px solid #64748b;
  background: #ffffff;
  color: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 0;
  transition: all 0.15s ease-in-out;
}

.scantron-bubble:hover {
  border-color: #0f172a;
  background: #f1f5f9;
}

/* Shaded Pencil Lead Fill */
.scantron-bubble.is-shaded {
  background: #1e293b; /* Graphite No. 2 pencil dark lead */
  border-color: #0f172a;
  color: #ffffff;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.4);
}

.bubble-char {
  font-size: 0.72rem;
  font-weight: 800;
  font-family: Arial, sans-serif;
  line-height: 1;
}

/* OMR Card Footer */
.omr-card-footer {
  border-top: 2px solid #e2e8f0;
  padding: 0.85rem 1rem;
  background: #fafafa;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.omr-summary-row {
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.stat-bubble-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.1rem;
}

.stat-num {
  font-size: 1.1rem;
  font-weight: 900;
}

.stat-lbl {
  font-size: 0.65rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.text-blue { color: #2563eb; }
.text-slate { color: #475569; }
.text-amber { color: #d97706; }

.btn-omr-submit {
  width: 100%;
  padding: 0.65rem 1rem;
  background: #0f172a;
  border: none;
  border-radius: 6px;
  color: #ffffff;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-omr-submit:hover {
  background: #1e293b;
}

/* ── MODALS (ANTI-CHEATING & DISQUALIFICATION OVERLAYS) ── */
.proctor-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 2, 8, 0.88);
  backdrop-filter: blur(8px);
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.proctor-modal-card {
  width: 100%;
  max-width: 500px;
  background: #0d1222;
  border: 1px solid rgba(239, 68, 68, 0.45);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9), 0 0 35px rgba(239, 68, 68, 0.2);
  border-radius: 16px;
  padding: 2rem 2.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1.15rem;
  color: #f1f5f9;
}

.proctor-icon-circle {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
}

.proctor-icon-circle.warning {
  background: rgba(245, 158, 11, 0.15);
  border: 2px solid rgba(245, 158, 11, 0.4);
}

.proctor-icon-circle.critical {
  background: rgba(239, 68, 68, 0.18);
  border: 2px solid rgba(239, 68, 68, 0.6);
  animation: pulse-border 1.5s infinite;
}

.proctor-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.3rem 0.8rem;
  border-radius: 999px;
  text-transform: uppercase;
}

.badge-warning {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.4);
}

.badge-critical {
  background: rgba(239, 68, 68, 0.25);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.6);
}

.proctor-title {
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 1.25;
}

.proctor-desc {
  font-size: 0.92rem;
  color: #cbd5e1;
  margin: 0 0 0.85rem 0;
  line-height: 1.5;
}

.proctor-limit-box {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1.15rem;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  width: 100%;
}

.proctor-limit-box.box-danger {
  border-color: rgba(239, 68, 68, 0.4);
  background: rgba(239, 68, 68, 0.07);
}

.strike-tracker {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 700;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.strike-dot {
  color: #64748b;
}

.strike-dot.filled {
  color: #f59e0b;
}

.strike-dot.limit {
  color: #ef4444;
}

.proctor-warning-text {
  font-size: 0.84rem;
  color: #cbd5e1;
  line-height: 1.55;
  margin: 0;
}

.proctor-warning-text.text-danger {
  color: #fca5a5;
}

.proctor-ack-btn {
  width: 100%;
  padding: 0.85rem 1.5rem;
  background: #3b82f6;
  border: none;
  border-radius: 8px;
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.proctor-ack-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.proctor-ack-btn.btn-urgent {
  background: #ef4444;
}

.proctor-ack-btn.btn-urgent:hover {
  background: #dc2626;
}

/* ── DISQUALIFICATION SCREEN ── */
.disqualified-full-screen {
  position: fixed;
  inset: 0;
  background: rgba(3, 4, 10, 0.96);
  backdrop-filter: blur(14px);
  z-index: 100001;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  color: #f1f5f9;
}

.disqualified-card {
  width: 100%;
  max-width: 580px;
  background: #090c16;
  border: 2px solid rgba(239, 68, 68, 0.65);
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.95), 0 0 50px rgba(239, 68, 68, 0.35);
  border-radius: 18px;
  padding: 2.25rem 2.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}

.disq-icon-wrap {
  width: 72px;
  height: 72px;
  background: rgba(239, 68, 68, 0.15);
  border: 2px solid rgba(239, 68, 68, 0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
}

.disq-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #f87171;
  background: rgba(239, 68, 68, 0.2);
  padding: 0.25rem 0.85rem;
  border-radius: 999px;
  border: 1px solid rgba(239, 68, 68, 0.4);
}

.disq-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.01em;
}

.disq-reason-callout {
  width: 100%;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.35);
  border-radius: 10px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.reason-tag {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #f87171;
  text-transform: uppercase;
}

.reason-val {
  font-size: 1.6rem;
  font-weight: 900;
  color: #fca5a5;
  letter-spacing: 0.02em;
}

.reason-sub {
  font-size: 0.82rem;
  color: #e2e8f0;
  margin: 0.2rem 0 0 0;
  line-height: 1.45;
}

.disq-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  width: 100%;
}

.disq-stat {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.ds-lbl {
  font-size: 0.65rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
}

.ds-val {
  font-size: 1rem;
  font-weight: 800;
  color: #ffffff;
}

.text-red {
  color: #f87171 !important;
}

.disq-notice {
  font-size: 0.84rem;
  color: #94a3b8;
  line-height: 1.5;
  margin: 0;
}

.disq-btn-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
  width: 100%;
  margin-top: 0.5rem;
}

.disq-btn {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.disq-btn.secondary {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #cbd5e1;
}

.disq-btn.secondary:hover {
  background: rgba(51, 65, 85, 0.8);
  color: #ffffff;
}

.disq-btn.primary {
  background: #ef4444;
  border: none;
  color: #ffffff;
}

.disq-btn.primary:hover {
  background: #dc2626;
  transform: translateY(-1px);
}

@keyframes pulse-border {
  0%, 100% { border-color: rgba(239, 68, 68, 0.6); box-shadow: 0 0 10px rgba(239, 68, 68, 0.3); }
  50% { border-color: rgba(239, 68, 68, 1); box-shadow: 0 0 25px rgba(239, 68, 68, 0.6); }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-6px); }
  40%, 80% { transform: translateX(6px); }
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
}

/* ── RESPONSIVE ADAPTATIONS ── */
@media (max-width: 1200px) {
  .board-exam-split-canvas {
    grid-template-columns: 2.8fr 1.2fr;
  }
}

@media (max-width: 1024px) {
  .board-exam-split-canvas {
    grid-template-columns: 1fr;
  }
  .bubble-sheet-column {
    position: static;
  }
  .omr-card {
    max-height: 500px;
  }
}

/* ── SUBJECT PROGRESSION & TRANSITION STYLES ── */
.paper-nav-btn.next-subject-btn {
  background: #1e3a8a;
  border-color: #1e40af;
  color: #ffffff;
  padding: 0.75rem 2rem;
  font-weight: 800;
  box-shadow: 0 4px 14px rgba(30, 58, 138, 0.3);
  animation: pulse-proceed 2s infinite ease-in-out;
}

.paper-nav-btn.next-subject-btn:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(29, 78, 216, 0.4);
}

.paper-nav-btn.submit-exam-trigger-btn {
  background: #0f172a;
  border-color: #0f172a;
  color: #fbbf24;
  padding: 0.75rem 2rem;
  font-weight: 800;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.25);
}

.paper-nav-btn.submit-exam-trigger-btn:hover {
  background: #1e293b;
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.35);
}

@keyframes pulse-proceed {
  0%, 100% {
    box-shadow: 0 4px 14px rgba(30, 58, 138, 0.3);
  }
  50% {
    box-shadow: 0 6px 22px rgba(30, 64, 175, 0.55);
  }
}

/* Locked Subject Tab */
.subj-tab-btn.is-locked {
  opacity: 0.65;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  cursor: not-allowed;
}

.subj-tab-btn.is-locked:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.tab-lock-icon {
  margin-right: 0.3rem;
  font-size: 0.75rem;
}

/* ── SUBJECT TRANSITION MODAL ── */
.subject-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.subject-modal-card {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 16px;
  max-width: 540px;
  width: 100%;
  padding: 2.25rem 2rem;
  text-align: center;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.1rem;
}

.sub-modal-emblem {
  font-size: 2.5rem;
}

.sub-modal-stamp {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
}

.sub-modal-title {
  font-size: 1.35rem;
  font-weight: 900;
  color: #0f172a;
  margin: 0;
}

.sub-modal-desc {
  font-size: 0.9rem;
  color: #475569;
  line-height: 1.55;
  margin: 0;
}

.sub-modal-status-box {
  width: 100%;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.sub-modal-status-box.status-complete {
  border-color: #86efac;
  background: #f0fdf4;
}

.sub-modal-status-box.status-pending {
  border-color: #fed7aa;
  background: #fffbeb;
}

.status-box-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding-bottom: 0.5rem;
}

.status-subject-name {
  font-weight: 800;
  font-size: 0.84rem;
  color: #0f172a;
  text-align: left;
}

.status-tag {
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

.tag-complete {
  background: #dcfce7;
  color: #15803d;
}

.tag-pending {
  background: #fef3c7;
  color: #b45309;
}

.status-metrics-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.status-metric {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
}

.sm-val {
  font-size: 1.2rem;
  font-weight: 900;
  font-family: Georgia, serif;
}

.sm-lbl {
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.text-amber {
  color: #d97706 !important;
}

.text-green {
  color: #16a34a !important;
}

.sub-modal-warning-callout {
  width: 100%;
  background: #fef3c7;
  border: 1px solid #fde68a;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  text-align: left;
}

.warn-icon {
  font-size: 1.25rem;
  line-height: 1;
}

.warn-text {
  font-size: 0.8rem;
  color: #92400e;
  line-height: 1.45;
  margin: 0;
}

.sub-modal-actions {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  width: 100%;
  margin-top: 0.5rem;
}

.sub-modal-btn {
  width: 100%;
  padding: 0.85rem 1.25rem;
  border-radius: 8px;
  font-size: 0.92rem;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.sub-modal-btn.primary {
  background: #1e3a8a;
  border: 1px solid #1e40af;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
}

.sub-modal-btn.primary:hover {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.sub-modal-btn.secondary {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #1e293b;
}

.sub-modal-btn.secondary:hover {
  background: #f1f5f9;
}

.sub-modal-btn.text-dismiss {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 0.82rem;
  padding: 0.4rem;
}

.sub-modal-btn.text-dismiss:hover {
  color: #0f172a;
}
</style>
