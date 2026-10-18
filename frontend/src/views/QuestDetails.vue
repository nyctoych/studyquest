<template>
  <div class="academic-reviewer-root" :class="{ 'is-morning': isMorning }">
    <!-- ── TOP ACADEMIC NAVIGATION BAR ── -->
    <header class="academic-navbar" :class="{ 'is-morning': isMorning }">
      <div class="navbar-inner">
        <!-- Subject Context -->
        <div class="nav-left">
          <div v-if="part" class="nav-breadcrumb">
            <span class="nav-domain-name">{{ part.domain_name }}</span>
            <span class="nav-divider">/</span>
            <span class="nav-part-seq">Part {{ part.part_number }} of 10</span>
          </div>
        </div>

        <!-- Center: Subtle Reading Progress Indicator -->
        <div v-if="part" class="nav-center">
          <div class="nav-progress-wrap" :title="`Subject Progress: Part ${part.part_number} of 10`">
            <div class="nav-progress-track">
              <div 
                class="nav-progress-fill" 
                :style="{ width: `${(part.part_number / 10) * 100}%` }"
              ></div>
            </div>
            <span class="nav-progress-text">{{ (part.part_number / 10) * 100 }}% of Domain</span>
          </div>
        </div>

        <!-- Right: Status, Rewards & Read Aloud Controls -->
        <div class="nav-right" v-if="part">

          <!-- Text-to-Speech (Read Aloud) Toolbar Control -->
          <div class="tts-toolbar-group" role="region" aria-label="Reviewer Read Aloud">
            <!-- Primary Read Aloud Toggle Button -->
            <button
              type="button"
              class="btn-tts-toggle"
              :class="{
                'is-active': studyHallTtsService.isPlaying.value,
                'is-paused': studyHallTtsService.isPaused.value,
                'is-unsupported': !studyHallTtsService.isSupported.value
              }"
              @click="handleToggleTTS"
              :disabled="!studyHallTtsService.isSupported.value"
              :title="!studyHallTtsService.isSupported.value
                ? 'Text-to-Speech is not supported in this browser'
                : studyHallTtsService.isPlaying.value
                  ? 'Turn Read Aloud OFF'
                  : 'Turn Read Aloud ON'"
              :aria-pressed="studyHallTtsService.isPlaying.value"
              id="studyhall-tts-toggle-btn"
            >
              <span class="tts-icon" aria-hidden="true">
                <template v-if="!studyHallTtsService.isSupported.value">🔇</template>
                <template v-else-if="studyHallTtsService.isPlaying.value && !studyHallTtsService.isPaused.value">🔊</template>
                <template v-else>🔈</template>
              </span>
              <span class="tts-label">Read Aloud</span>
              <span class="tts-state-badge" :class="{ 'state-on': studyHallTtsService.isPlaying.value && !studyHallTtsService.isPaused.value, 'state-paused': studyHallTtsService.isPaused.value }">
                {{ !studyHallTtsService.isSupported.value ? 'UNAVAILABLE' : studyHallTtsService.isPaused.value ? 'PAUSED' : studyHallTtsService.isPlaying.value ? 'ON' : 'OFF' }}
              </span>
            </button>

            <!-- Compact Playback Controls (Visible when active) -->
            <div v-if="studyHallTtsService.isPlaying.value" class="tts-playback-subcontrols animate-fade-in">
              <button
                v-if="!studyHallTtsService.isPaused.value"
                type="button"
                class="btn-tts-sub"
                @click="studyHallTtsService.pause()"
                title="Pause Read Aloud"
                aria-label="Pause Read Aloud"
              >
                ⏸ Pause
              </button>
              <button
                v-else
                type="button"
                class="btn-tts-sub"
                @click="studyHallTtsService.resume()"
                title="Resume Read Aloud"
                aria-label="Resume Read Aloud"
              >
                ▶ Resume
              </button>
              <button
                type="button"
                class="btn-tts-sub btn-tts-stop"
                @click="studyHallTtsService.stop()"
                title="Stop Read Aloud"
                aria-label="Stop Read Aloud"
              >
                ⏹ Stop
              </button>
            </div>
          </div>

          <!-- Subtle Status Indicator -->
          <div class="nav-status-indicator">
            <span v-if="part.user_status === 'mastered'" class="status-badge mastered">
              Mastered ({{ part.user_score }}%)
            </span>
            <span v-else-if="part.user_status === 'completed'" class="status-badge completed">
              Passed ({{ part.user_score }}%)
            </span>
            <span v-else-if="part.user_status === 'in_progress'" class="status-badge in-progress">
              In Progress
            </span>
            <span v-else class="status-badge available">
              Available
            </span>
          </div>

          <!-- Subtle Reward Footnote -->
          <span class="nav-reward-meta" title="Standard reward upon passing score">
            +{{ part.xp_reward }} XP · +{{ part.coin_reward }} Coins
          </span>
        </div>
      </div>
    </header>

    <!-- ── LOADING STATE ── -->
    <div v-if="loading" class="academic-loading-container">
      <div class="academic-spinner"></div>
      <p class="loading-label">Opening academic reviewer manuscript...</p>
    </div>

    <!-- ── LOCKED / ERROR STATE ── -->
    <div v-else-if="errorLocked" class="academic-locked-container">
      <div class="locked-card">
        <div class="locked-icon-shield">🔒</div>
        <h2 class="locked-heading">Curricular Part Restricted</h2>
        <p class="locked-message">
          {{ errorMessage || 'To maintain sequential academic learning, complete the preceding study part before accessing this module.' }}
        </p>
        <div class="locked-actions">
          <router-link :to="backTarget" class="academic-btn primary-btn">
            Return to Study Hall Shelves
          </router-link>
        </div>
      </div>
    </div>

    <!-- ── MAIN ACADEMIC REVIEWER DOCUMENT ── -->
    <main v-else-if="part" class="academic-document-layout">
      <article class="academic-reading-column">
        
        <!-- Document Header / Title Area -->
        <header class="document-header">
          <div class="document-pre-meta">
            <span class="meta-authority">DSM-5 CLINICAL PSYCHIATRY REVIEWER</span>
            <span class="meta-dot">·</span>
            <span class="meta-domain">SUBJECT {{ part.domain_id }}: {{ part.domain_name.toUpperCase() }}</span>
            <span class="meta-dot">·</span>
            <span class="meta-unit">PART {{ part.part_number }} OF 10</span>
          </div>

          <h1 class="document-title">
            Part {{ part.part_number }}: {{ part.title }}
          </h1>

          <p class="document-scope-lead">
            {{ part.description }}
          </p>

          <div class="document-header-footer">
            <span class="citation-tag">Standard Diagnostic Reference: DSM-5 Section II Criteria & Official Licensure Curricula</span>
            
            <div class="header-action-links">
              <!-- Secondary In-Document Read Aloud Pill -->
              <button
                type="button"
                class="btn-doc-read-aloud"
                :class="{
                  'is-active': studyHallTtsService.isPlaying.value,
                  'is-paused': studyHallTtsService.isPaused.value
                }"
                @click="handleToggleTTS"
                :title="studyHallTtsService.isPlaying.value ? 'Stop Read Aloud' : 'Listen to this lesson aloud'"
              >
                <span class="doc-tts-icon">{{ studyHallTtsService.isPlaying.value ? '🔊' : '🔈' }}</span>
                <span>{{ studyHallTtsService.isPlaying.value ? (studyHallTtsService.isPaused.value ? 'Read Aloud (Paused)' : 'Reading Aloud...') : 'Read Aloud' }}</span>
                <span v-if="studyHallTtsService.isPlaying.value && studyHallTtsService.totalChunks.value > 0" class="doc-tts-counter">
                  {{ studyHallTtsService.currentChunkIndex.value }}/{{ studyHallTtsService.totalChunks.value }}
                </span>
              </button>

              <a href="#assessment-challenge" class="jump-to-assessment-link">
                Skip to Knowledge Assessment ↓
              </a>
            </div>
          </div>

          <div class="header-divider-rule"></div>
        </header>

        <!-- ── SECTION 1: FOUNDATIONAL OVERVIEW ── -->
        <section class="manuscript-section" id="section-foundations">
          <h2 class="section-heading">1. Foundational Doctrine & Clinical Overview</h2>
          
          <div class="academic-prose">
            <p 
              v-for="(para, pIdx) in getParagraphs(part.overview)" 
              :key="'ov-' + pIdx" 
              class="academic-paragraph"
            >
              {{ para }}
            </p>
          </div>

          <div class="academic-callout-note">
            <div class="callout-lead"><strong>Licensure Examination Focus:</strong></div>
            <div class="callout-body">
              Pay rigorous attention to the operational behavioral thresholds and duration requirements that distinguish this category from related mood, anxiety, cognitive, or characterological phenomena.
            </div>
          </div>
        </section>

        <!-- ── SECTION 2: DSM-5 DIAGNOSTIC CRITERIA ── -->
        <section class="manuscript-section" id="section-criteria" v-if="part.diagnostic_criteria">
          <h2 class="section-heading">2. DSM-5 Diagnostic Criteria & Symptom Thresholds</h2>
          
          <div class="academic-prose">
            <p 
              v-for="(para, pIdx) in getParagraphs(part.diagnostic_criteria)" 
              :key="'crit-' + pIdx" 
              class="academic-paragraph"
            >
              {{ para }}
            </p>
          </div>
        </section>

        <!-- ── SECTION 3: SPECIFIERS & SEVERITY ── -->
        <section class="manuscript-section" id="section-specifiers" v-if="part.specifiers">
          <h2 class="section-heading">3. Subtypes, Specifiers & Severity Stratification</h2>
          
          <div class="academic-prose">
            <p 
              v-for="(para, pIdx) in getParagraphs(part.specifiers)" 
              :key="'spec-' + pIdx" 
              class="academic-paragraph"
            >
              {{ para }}
            </p>
          </div>
        </section>

        <!-- ── SECTION 4: DIFFERENTIAL DIAGNOSIS ── -->
        <section class="manuscript-section" id="section-differential" v-if="part.differential_diagnosis">
          <h2 class="section-heading">4. Differential Diagnosis & Clinical Boundaries</h2>
          
          <div class="academic-prose">
            <p 
              v-for="(para, pIdx) in getParagraphs(part.differential_diagnosis)" 
              :key="'diff-' + pIdx" 
              class="academic-paragraph"
            >
              {{ para }}
            </p>
          </div>
        </section>

        <!-- ── SECTION 5: KEY CONCEPTS & VOCABULARY ── -->
        <section class="manuscript-section" id="section-concepts" v-if="(part.key_concepts && part.key_concepts.length) || (part.important_terms && part.important_terms.length)">
          <h2 class="section-heading">5. Core Principles & Diagnostic Nomenclature</h2>

          <!-- Key Concepts -->
          <div v-if="part.key_concepts && part.key_concepts.length" class="subsection-block">
            <h3 class="subsection-title">Theoretical & Clinical Principles</h3>
            <div class="concepts-definition-list">
              <div 
                v-for="(concept, cIdx) in part.key_concepts" 
                :key="'concept-' + cIdx" 
                class="concept-def-row"
              >
                <div class="concept-def-term">
                  <strong>{{ concept.title }}</strong>
                </div>
                <div class="concept-def-text">
                  {{ concept.definition }}
                </div>
              </div>
            </div>
          </div>

          <!-- Board Exam Vocabulary Table -->
          <div v-if="part.important_terms && part.important_terms.length" class="subsection-block terms-block">
            <h3 class="subsection-title">Board Exam Vocabulary & Operational Definitions</h3>
            <div class="academic-table-container">
              <table class="academic-terms-table">
                <thead>
                  <tr>
                    <th style="width: 32%;">Psychiatric Term</th>
                    <th>Clinical & Diagnostic Definition</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(term, tIdx) in part.important_terms" :key="'term-' + tIdx">
                    <td class="term-cell-name"><strong>{{ term.term }}</strong></td>
                    <td class="term-cell-def">{{ term.definition }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ── SECTION 6: APPLIED CLINICAL VIGNETTE ── -->
        <section class="manuscript-section" id="section-vignette" v-if="part.clinical_example">
          <h2 class="section-heading">6. Applied Clinical Vignette & Case Formulation</h2>
          
          <div class="clinical-vignette-paper">
            <div class="vignette-header-bar">
              <span class="vignette-label">HYPOTHETICAL EDUCATIONAL CASE FORMULATION</span>
            </div>
            <div class="vignette-content-prose">
              <p 
                v-for="(para, pIdx) in getParagraphs(part.clinical_example)" 
                :key="'vign-' + pIdx" 
                class="academic-paragraph"
              >
                {{ para }}
              </p>
            </div>
          </div>

          <div class="vignette-takeaways-block">
            <h4 class="takeaways-heading">Clinical Implications & Diagnostic Reasoning</h4>
            <ul class="academic-bullet-list">
              <li>Differential diagnosis requires ruling out secondary physiological effects of medical illnesses, toxicities, or prescribed pharmacotherapies prior to establishing primary psychiatric diagnoses.</li>
              <li>Symptom presentation must satisfy comprehensive duration, clustering, and functional impairment benchmarks rather than relying upon isolated or transient complaints.</li>
              <li>Diagnostic formulations must be grounded in multi-informant corroboration and qualified clinical interviewing rather than brief self-report questionnaires.</li>
            </ul>
          </div>
        </section>

        <!-- ── SECTION 7: CURRICULAR SYNTHESIS & BOARD PEARLS ── -->
        <section class="manuscript-section" id="section-synthesis" v-if="part.summary_notes">
          <h2 class="section-heading">7. Curricular Synthesis & Licensure Board Pearls</h2>
          
          <div class="academic-prose">
            <p 
              v-for="(para, pIdx) in getParagraphs(part.summary_notes)" 
              :key="'sum-' + pIdx" 
              class="academic-paragraph"
            >
              {{ para }}
            </p>
          </div>
        </section>

        <!-- ── SECTION 8: PART KNOWLEDGE ASSESSMENT (SCORED CHALLENGE) ── -->
        <section class="manuscript-section assessment-section" id="assessment-challenge">
          <div class="assessment-header-banner">
            <h2 class="section-heading" style="margin-bottom: 6px;">8. Part Assessment & Knowledge Verification</h2>
            <p class="assessment-meta-info">
              Formal multiple-choice evaluation • Passing standard: <strong>{{ part.passing_score }}%</strong> • {{ part.questions?.length || 0 }} Questions • Rewards: <strong>+{{ part.xp_reward }} XP</strong> and <strong>+{{ part.coin_reward }} Coins</strong> upon completion.
            </p>
          </div>

          <!-- Questions List -->
          <div v-if="part.questions && part.questions.length" class="academic-questions-list">
            <div 
              v-for="(q, qIndex) in part.questions" 
              :key="q.id"
              class="academic-question-card"
              :class="{ 'is-answered': userAnswers[q.id] }"
            >
              <div class="question-meta-row">
                <span class="question-sequence">Question {{ qIndex + 1 }} of {{ part.questions.length }}</span>
                <span class="question-diff-tag">{{ q.difficulty || 'standard' }}</span>
              </div>

              <div class="question-stem-text">
                {{ q.question }}
              </div>

              <!-- Answer Choices -->
              <div class="choices-container">
                <button 
                  v-for="opt in ['A', 'B', 'C', 'D']" 
                  :key="opt"
                  type="button"
                  class="choice-row-btn"
                  :class="{ 
                    'selected': userAnswers[q.id] === opt,
                    'disabled': submitted
                  }"
                  @click="selectAnswer(q.id, opt)"
                  :disabled="submitted"
                >
                  <span class="choice-marker">{{ opt }}</span>
                  <span class="choice-text">{{ q['choice_' + opt.toLowerCase()] }}</span>
                </button>
              </div>

              <!-- Post-Grading Explanation -->
              <div 
                v-if="submitted && gradingReview[q.id]" 
                class="question-explanation-panel"
                :class="gradingReview[q.id].is_correct ? 'exp-correct' : 'exp-incorrect'"
              >
                <div class="exp-status-heading">
                  <span v-if="gradingReview[q.id].is_correct">✓ Correct Answer</span>
                  <span v-else>✗ Incorrect · Expected: Option {{ gradingReview[q.id].correct_answer }}</span>
                </div>
                <p class="exp-rationale-text">{{ gradingReview[q.id].explanation }}</p>
              </div>

            </div>
          </div>

          <!-- Assessment Action Bar -->
          <div class="assessment-action-panel">
            <div class="completion-tracker-text">
              <template v-if="!submitted">
                Answered: <strong>{{ answeredCount }} / {{ part.questions?.length || 0 }}</strong>
              </template>
              <template v-else-if="submissionResult">
                Score: <strong>{{ submissionResult.score }}%</strong> ({{ submissionResult.correct_count }}/{{ submissionResult.total_questions }} correct)
                <span 
                  class="assessment-inline-pill" 
                  :class="submissionResult.is_passed ? 'pill-passed' : 'pill-remediation'"
                >
                  {{ submissionResult.is_passed ? '✓ Standard Met (70%+)' : '✗ Remediation Needed (Requires 70%+)' }}
                </span>
              </template>
            </div>

            <div class="assessment-buttons">
              <button 
                v-if="!submitted"
                type="button"
                class="academic-btn primary-btn submit-btn"
                :disabled="answeredCount < (part.questions?.length || 0) || submitting"
                @click="submitAssessment"
              >
                {{ submitting ? 'Submitting & Grading...' : 'Submit Assessment for Grading' }}
              </button>

              <template v-else>
                <button 
                  type="button"
                  class="academic-btn secondary-btn"
                  @click="retryAssessment"
                >
                  {{ submissionResult?.is_passed ? 'Retake Assessment' : 'Retry Assessment' }}
                </button>

                <template v-if="submissionResult?.is_passed">
                  <router-link
                    v-if="submissionResult?.next_part"
                    :to="getPartRoute(submissionResult.next_part.id)"
                    class="academic-btn primary-btn"
                  >
                    Proceed to Part {{ submissionResult.next_part.part_number }} →
                  </router-link>

                  <router-link
                    v-else
                    :to="backTarget"
                    class="academic-btn primary-btn"
                  >
                    Curricular Subject Complete · Return to Shelves →
                  </router-link>
                </template>
              </template>
            </div>
          </div>

        </section>

        <!-- ── MANUSCRIPT FOOTER NAVIGATION ── -->
        <footer class="manuscript-footer-nav">
          <div class="footer-nav-grid">
            <!-- Previous Part Link Card -->
            <div class="footer-nav-col col-prev">
              <router-link
                v-if="part.prev_part"
                :to="getPartRoute(part.prev_part.id)"
                class="academic-nav-card card-prev"
                id="footer-prev-part-btn"
              >
                <span class="nav-card-dir">← Previous Unit</span>
                <span class="nav-card-title">Part {{ part.prev_part.part_number }}: {{ part.prev_part.title }}</span>
              </router-link>
              <div v-else class="nav-card-placeholder">
                <span class="nav-card-dir">Beginning of Subject</span>
                <span class="nav-card-title">Part 1 (Current Unit)</span>
              </div>
            </div>

            <!-- Next Part Link Card -->
            <div class="footer-nav-col col-next">
              <template v-if="part.next_part">
                <router-link
                  v-if="part.user_status === 'completed' || part.user_status === 'mastered'"
                  :to="getPartRoute(part.next_part.id)"
                  class="academic-nav-card card-next"
                  id="footer-next-part-btn"
                >
                  <span class="nav-card-dir">Next Unit →</span>
                  <span class="nav-card-title">Part {{ part.next_part.part_number }}: {{ part.next_part.title }}</span>
                </router-link>
                <div v-else class="academic-nav-card card-locked">
                  <span class="nav-card-dir">🔒 Unit Locked</span>
                  <span class="nav-card-title">Complete Part {{ part.part_number }} with 70%+ to unlock Part {{ part.next_part.part_number }}</span>
                </div>
              </template>
              <div v-else class="nav-card-placeholder">
                <span class="nav-card-dir">Subject Concluded</span>
                <span class="nav-card-title">Part 10 Mastery Reached</span>
              </div>
            </div>
          </div>
        </footer>

      </article>
    </main>

    <!-- ── RESTRAINED ACADEMIC RESULT MODAL ── -->
    <Teleport to="body">
      <transition name="modal-fade">
        <div 
          v-if="showResultModal && submissionResult" 
          class="academic-modal-overlay" 
          :class="{ 'is-morning': isMorning }"
          @click="showResultModal = false"
        >
          <div 
            class="academic-modal-box" 
            :class="{ 'is-morning': isMorning }"
            @click.stop
          >
            <!-- Close icon button -->
            <button 
              type="button" 
              class="modal-close-icon-btn" 
              @click="showResultModal = false"
              title="Close modal"
              aria-label="Close modal"
            >
              ✕
            </button>
            
            <!-- Passed State -->
            <div v-if="submissionResult.is_passed" class="modal-body pass-view">
              <div class="modal-status-pill">
                {{ submissionResult.is_mastered ? 'SUBJECT MASTERY ACHIEVED' : 'ASSESSMENT PASSED' }}
              </div>
              
              <h3 class="modal-heading">
                {{ submissionResult.is_mastered ? 'Excellence in Psychiatric Evaluation' : 'Curricular Unit Completed' }}
              </h3>

              <p class="modal-subheading">
                You achieved a score of <strong>{{ submissionResult.score }}%</strong> ({{ submissionResult.correct_count }} of {{ submissionResult.total_questions }} correct). Minimum required: {{ submissionResult.passing_score }}%.
              </p>

              <div class="modal-metrics-strip">
                <div class="metric-cell">
                  <span class="metric-val">+{{ submissionResult.xp_awarded }} XP</span>
                  <span class="metric-label">Experience</span>
                </div>
                <div class="metric-cell">
                  <span class="metric-val">+{{ submissionResult.coins_awarded }}</span>
                  <span class="metric-label">Coins</span>
                </div>
                <div class="metric-cell">
                  <span class="metric-val">{{ submissionResult.score }}%</span>
                  <span class="metric-label">Final Score</span>
                </div>
              </div>

              <div v-if="submissionResult.domain_completed" class="domain-completed-notice">
                <strong>Domain Mastery Unlocked:</strong> All 10 curricular parts of {{ part.domain_name }} have been completed. Bonus rewards have been credited to your transcript.
              </div>

              <div class="modal-footer-actions">
                <button type="button" class="academic-btn secondary-btn" @click="showResultModal = false">
                  Review Questions & Rationales
                </button>
                <button 
                  v-if="submissionResult.next_part"
                  type="button" 
                  class="academic-btn primary-btn"
                  @click="goToNextPart"
                >
                  Proceed to Part {{ submissionResult.next_part.part_number }} →
                </button>
                <router-link
                  v-else
                  :to="backTarget"
                  class="academic-btn primary-btn"
                >
                  Return to Shelves →
                </router-link>
              </div>
            </div>

            <!-- Failed State -->
            <div v-else class="modal-body fail-view">
              <div class="modal-status-pill fail-pill">
                REVIEW REQUIRED
              </div>

              <h3 class="modal-heading">
                Remediation Needed
              </h3>

              <p class="modal-subheading">
                Your score was <strong>{{ submissionResult.score }}%</strong>. A score of <strong>{{ submissionResult.passing_score }}%</strong> is required to verify competency and unlock the next part.
              </p>

              <div class="remediation-note">
                Please inspect the explanations provided below to understand the diagnostic rationales before attempting the examination again.
              </div>

              <div class="modal-footer-actions">
                <button type="button" class="academic-btn secondary-btn" @click="showResultModal = false">
                  Inspect Explanations
                </button>
                <button type="button" class="academic-btn primary-btn" @click="retryAssessment">
                  Retry Assessment
                </button>
              </div>
            </div>

          </div>
        </div>
      </transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import curriculumService from '../services/curriculumService'
import { studyHallTtsService } from '../services/studyHallTtsService'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const gameStore = useGameStore()
const isMorning = computed(() => gameStore.isMorning)

// State
const loading = ref(true)
const errorLocked = ref(false)
const errorMessage = ref('')
const part = ref(null)

// Assessment State
const userAnswers = ref({})
const submitting = ref(false)
const submitted = ref(false)
const submissionResult = ref(null)
const gradingReview = ref({})
const showResultModal = ref(false)
const startTime = ref(Date.now())

// Read Aloud (TTS) Handlers & Lifecycle Cleanup
function handleToggleTTS() {
  if (!part.value) return
  studyHallTtsService.toggle(part.value)
}

onBeforeUnmount(() => {
  studyHallTtsService.stop()
})

onUnmounted(() => {
  studyHallTtsService.stop()
})

onMounted(() => {
  loadPart()
})

watch(() => [route.params.partId, route.params.id, route.params.chapterId], () => {
  studyHallTtsService.stop()
  loadPart()
})

async function loadPart() {
  loading.value = true
  errorLocked.value = false
  errorMessage.value = ''
  userAnswers.value = {}
  submitted.value = false
  submissionResult.value = null
  gradingReview.value = {}
  startTime.value = Date.now()

  const rawId = route.params.partId || route.params.id
  const partId = parseInt(rawId)
  if (!partId) {
    loading.value = false
    errorLocked.value = true
    errorMessage.value = 'Invalid learning part identifier.'
    return
  }

  try {
    const res = await curriculumService.getPartDetails(partId)
    if (res?.error === 'locked') {
      errorLocked.value = true
      errorMessage.value = res.message
    } else if (res?.part) {
      part.value = res.part
    }
  } catch (err) {
    console.error('Failed to load part:', err)
    if (err.response?.status === 403) {
      errorLocked.value = true
      errorMessage.value = err.response?.data?.message || 'This part is currently locked.'
    } else {
      errorLocked.value = true
      errorMessage.value = 'The requested study part could not be loaded.'
    }
  } finally {
    loading.value = false
  }
}

// Navigation helpers
const backTarget = computed(() => {
  if (route.params.chapterId) {
    return `/studyhall/chapter/${route.params.chapterId}`
  }
  if (part.value?.domain_id) {
    return `/studyhall/chapter/${part.value.domain_id}`
  }
  return '/studyhall/shelves'
})

function getPartRoute(targetPartId) {
  if (!targetPartId) return '#'
  const chId = route.params.chapterId || part.value?.domain_id || 1
  return `/studyhall/chapter/${chId}/part/${targetPartId}`
}

// Paragraph splitting helper
function getParagraphs(rawText) {
  if (!rawText || typeof rawText !== 'string') return []
  return rawText
    .split(/\n\s*\n/)
    .map(p => p.trim())
    .filter(Boolean)
}

// Assessment interaction
function selectAnswer(questionId, choice) {
  if (submitted.value) return
  userAnswers.value[questionId] = choice
}

const answeredCount = computed(() => {
  return Object.keys(userAnswers.value).length
})

async function submitAssessment() {
  if (answeredCount.value < (part.value?.questions?.length || 0)) return
  studyHallTtsService.stop()
  submitting.value = true

  const timeSpent = Math.max(1, Math.round((Date.now() - startTime.value) / 1000))

  try {
    const res = await curriculumService.submitPartAssessment(
      part.value.id,
      userAnswers.value,
      timeSpent
    )

    if (res?.success) {
      submitted.value = true
      submissionResult.value = res

      // Index review details
      if (res.review) {
        const map = {}
        res.review.forEach(item => {
          map[item.question_id] = item
        })
        gradingReview.value = map
      }

      // Update user state in store
      if (res.user) {
        authStore.user = res.user
      }

      // Update current part status and score on pass
      if (res.is_passed && part.value) {
        part.value.user_status = res.is_mastered ? 'mastered' : 'completed'
        part.value.user_score = res.score
      }

      showResultModal.value = true
    }
  } catch (err) {
    console.error('Submission failed:', err)
    alert('Assessment grading error: ' + (err.response?.data?.message || err.message))
  } finally {
    submitting.value = false
  }
}

function retryAssessment() {
  submitted.value = false
  submissionResult.value = null
  gradingReview.value = {}
  userAnswers.value = {}
  showResultModal.value = false
  startTime.value = Date.now()
  
  // Smooth scroll to assessment section
  const el = document.getElementById('assessment-challenge')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

function goToNextPart() {
  studyHallTtsService.stop()
  showResultModal.value = false
  if (submissionResult.value?.next_part?.id) {
    router.push(getPartRoute(submissionResult.value.next_part.id))
  } else {
    router.push(backTarget.value)
  }
}
</script>

<style scoped>
/* ── ACADEMIC REVIEWER ROOT ── */
.academic-reviewer-root {
  min-height: 100vh;
  width: 100%;
  background-color: #0e081a;
  color: #222222;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  line-height: 1.7;
  padding-bottom: 6rem;
  box-sizing: border-box;
  transition: background-color 0.3s ease;
}

.academic-reviewer-root.is-morning,
:global([data-theme="morning"]) .academic-reviewer-root,
:global(.theme-morning) .academic-reviewer-root {
  background-color: #f7f3ec;
}

/* ── TOP ACADEMIC NAVBAR (DARK DEFAULT) ── */
.academic-navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: linear-gradient(180deg, rgba(16, 9, 30, 0.98) 0%, rgba(11, 7, 20, 0.98) 100%);
  border-bottom: 1px solid rgba(139, 92, 246, 0.3);
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

/* ── MORNING / DAY THEME OVERRIDES FOR ACADEMIC NAVBAR ── */
.academic-navbar.is-morning,
:global([data-theme="morning"]) .academic-navbar,
:global(.theme-morning) .academic-navbar {
  background: linear-gradient(180deg, rgba(254, 250, 242, 0.98) 0%, rgba(246, 239, 227, 0.98) 100%) !important;
  border-bottom: 1px solid rgba(184, 138, 66, 0.35) !important;
  box-shadow: 0 4px 18px rgba(143, 100, 45, 0.12) !important;
}

.academic-navbar.is-morning .nav-domain-name,
:global([data-theme="morning"]) .academic-navbar .nav-domain-name,
:global(.theme-morning) .academic-navbar .nav-domain-name {
  color: #2C1E14 !important;
}

.academic-navbar.is-morning .nav-divider,
:global([data-theme="morning"]) .academic-navbar .nav-divider,
:global(.theme-morning) .academic-navbar .nav-divider {
  color: #B59E7A !important;
}

.academic-navbar.is-morning .nav-part-seq,
:global([data-theme="morning"]) .academic-navbar .nav-part-seq,
:global(.theme-morning) .academic-navbar .nav-part-seq {
  color: #7A5B30 !important;
}

.academic-navbar.is-morning .nav-progress-track,
:global([data-theme="morning"]) .academic-navbar .nav-progress-track,
:global(.theme-morning) .academic-navbar .nav-progress-track {
  background-color: rgba(184, 138, 66, 0.22) !important;
}

.academic-navbar.is-morning .nav-progress-fill,
:global([data-theme="morning"]) .academic-navbar .nav-progress-fill,
:global(.theme-morning) .academic-navbar .nav-progress-fill {
  background: linear-gradient(90deg, #d97706, #f59e0b) !important;
}

.academic-navbar.is-morning .nav-progress-text,
:global([data-theme="morning"]) .academic-navbar .nav-progress-text,
:global(.theme-morning) .academic-navbar .nav-progress-text {
  color: #8C6A3E !important;
}

.academic-navbar.is-morning .btn-tts-toggle,
:global([data-theme="morning"]) .academic-navbar .btn-tts-toggle,
:global(.theme-morning) .academic-navbar .btn-tts-toggle {
  background: #FFFDF7 !important;
  border: 1px solid #D8C39E !important;
  color: #4A3525 !important;
  box-shadow: 0 2px 6px rgba(143, 100, 45, 0.1) !important;
}

.academic-navbar.is-morning .btn-tts-toggle:hover:not(:disabled),
:global([data-theme="morning"]) .academic-navbar .btn-tts-toggle:hover:not(:disabled),
:global(.theme-morning) .academic-navbar .btn-tts-toggle:hover:not(:disabled) {
  background: #FFF8EB !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
  box-shadow: 0 4px 10px rgba(143, 100, 45, 0.16) !important;
}

.academic-navbar.is-morning .btn-tts-toggle.is-active,
:global([data-theme="morning"]) .academic-navbar .btn-tts-toggle.is-active,
:global(.theme-morning) .academic-navbar .btn-tts-toggle.is-active {
  background: #FEF3C7 !important;
  border-color: #D97706 !important;
  color: #92400E !important;
  box-shadow: 0 0 10px rgba(217, 119, 6, 0.25) !important;
}

.academic-navbar.is-morning .btn-tts-toggle.is-paused,
:global([data-theme="morning"]) .academic-navbar .btn-tts-toggle.is-paused,
:global(.theme-morning) .academic-navbar .btn-tts-toggle.is-paused {
  background: #FFFBEB !important;
  border-color: #F59E0B !important;
  color: #B45309 !important;
}

.academic-navbar.is-morning .tts-state-badge,
:global([data-theme="morning"]) .academic-navbar .tts-state-badge,
:global(.theme-morning) .academic-navbar .tts-state-badge {
  background: rgba(140, 106, 62, 0.12) !important;
  color: #8C6A3E !important;
  border: 1px solid rgba(140, 106, 62, 0.25) !important;
}

.academic-navbar.is-morning .tts-state-badge.state-on,
:global([data-theme="morning"]) .academic-navbar .tts-state-badge.state-on,
:global(.theme-morning) .academic-navbar .tts-state-badge.state-on {
  background: rgba(16, 185, 129, 0.16) !important;
  color: #047857 !important;
  border-color: rgba(16, 185, 129, 0.4) !important;
}

.academic-navbar.is-morning .tts-state-badge.state-paused,
:global([data-theme="morning"]) .academic-navbar .tts-state-badge.state-paused,
:global(.theme-morning) .academic-navbar .tts-state-badge.state-paused {
  background: rgba(245, 158, 11, 0.18) !important;
  color: #B45309 !important;
  border-color: rgba(245, 158, 11, 0.45) !important;
}

.academic-navbar.is-morning .btn-tts-sub,
:global([data-theme="morning"]) .academic-navbar .btn-tts-sub,
:global(.theme-morning) .academic-navbar .btn-tts-sub {
  background: #FFFDF7 !important;
  border: 1px solid #D8C39E !important;
  color: #4A3525 !important;
}

.academic-navbar.is-morning .btn-tts-sub:hover,
:global([data-theme="morning"]) .academic-navbar .btn-tts-sub:hover,
:global(.theme-morning) .academic-navbar .btn-tts-sub:hover {
  background: #FFF8EB !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.academic-navbar.is-morning .btn-tts-stop:hover,
:global([data-theme="morning"]) .academic-navbar .btn-tts-stop:hover,
:global(.theme-morning) .academic-navbar .btn-tts-stop:hover {
  background: #FEE2E2 !important;
  border-color: #EF4444 !important;
  color: #B91C1C !important;
}

.academic-navbar.is-morning .btn-bgm-mute-toggle,
:global([data-theme="morning"]) .academic-navbar .btn-bgm-mute-toggle,
:global(.theme-morning) .academic-navbar .btn-bgm-mute-toggle {
  background: #EAD8B5 !important;
  border-color: #B88A42 !important;
  color: #3D2B1F !important;
}

.academic-navbar.is-morning .academic-nav-btn,
:global([data-theme="morning"]) .academic-navbar .academic-nav-btn,
:global(.theme-morning) .academic-navbar .academic-nav-btn {
  background: #FFFDF7 !important;
  border-color: #D8C39E !important;
  color: #4A3525 !important;
}

.academic-navbar.is-morning .status-badge.in-progress,
:global([data-theme="morning"]) .academic-navbar .status-badge.in-progress,
:global(.theme-morning) .academic-navbar .status-badge.in-progress {
  background-color: rgba(37, 99, 235, 0.12) !important;
  color: #1D4ED8 !important;
  border: 1px solid rgba(37, 99, 235, 0.35) !important;
}

.academic-navbar.is-morning .status-badge.mastered,
:global([data-theme="morning"]) .academic-navbar .status-badge.mastered,
:global(.theme-morning) .academic-navbar .status-badge.mastered {
  background-color: rgba(217, 119, 6, 0.14) !important;
  color: #B45309 !important;
  border: 1px solid rgba(217, 119, 6, 0.38) !important;
}

.academic-navbar.is-morning .status-badge.completed,
:global([data-theme="morning"]) .academic-navbar .status-badge.completed,
:global(.theme-morning) .academic-navbar .status-badge.completed {
  background-color: rgba(16, 185, 129, 0.14) !important;
  color: #047857 !important;
  border: 1px solid rgba(16, 185, 129, 0.38) !important;
}

.academic-navbar.is-morning .status-badge.available,
:global([data-theme="morning"]) .academic-navbar .status-badge.available,
:global(.theme-morning) .academic-navbar .status-badge.available {
  background-color: rgba(140, 106, 62, 0.1) !important;
  color: #7A5B30 !important;
  border: 1px solid rgba(140, 106, 62, 0.25) !important;
}

.academic-navbar.is-morning .nav-reward-meta,
:global([data-theme="morning"]) .academic-navbar .nav-reward-meta,
:global(.theme-morning) .academic-navbar .nav-reward-meta {
  color: #B45309 !important;
  font-weight: 700 !important;
}

.navbar-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 12px 210px 12px 125px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  min-height: 62px;
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .navbar-inner {
    padding-left: 100px;
    padding-right: 14px;
    gap: 10px;
  }
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.academic-nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background-color: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
  border: 1px solid rgba(139, 92, 246, 0.4);
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s ease;
}

.academic-nav-btn:hover {
  background-color: rgba(139, 92, 246, 0.22);
  border-color: #a855f7;
  color: #ffffff;
}

.nav-arrow {
  font-size: 14px;
  color: #a855f7;
}

.nav-breadcrumb {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #94a3b8;
}

.nav-domain-name {
  font-weight: 700;
  color: #f8fafc;
}

.nav-divider {
  color: #64748b;
}

.nav-part-seq {
  color: #cbd5e1;
}

.nav-center {
  flex: 1;
  max-width: 240px;
  display: flex;
  align-items: center;
}

@media (max-width: 860px) {
  .nav-center {
    display: none;
  }
}

.nav-progress-wrap {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-progress-track {
  width: 100%;
  height: 6px;
  background-color: rgba(255, 255, 255, 0.14);
  border-radius: 999px;
  overflow: hidden;
}

.nav-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b5cf6, #a855f7);
  transition: width 0.3s ease;
}

.nav-progress-text {
  font-size: 11px;
  color: #94a3b8;
  text-align: right;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* ── READ ALOUD (TTS) TOOLBAR CONTROLS ── */
.tts-toolbar-group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-tts-toggle {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 32px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(139, 92, 246, 0.4);
  border-radius: 6px;
  color: #e2e8f0;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-tts-toggle:hover:not(:disabled) {
  background: rgba(139, 92, 246, 0.22);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-1px);
}

.btn-tts-toggle:focus-visible {
  outline: 2px solid #a855f7;
  outline-offset: 2px;
}

.btn-tts-toggle.is-active {
  background: rgba(139, 92, 246, 0.32);
  border-color: #c084fc;
  color: #ffffff;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.45);
}

.btn-tts-toggle.is-paused {
  background: rgba(245, 158, 11, 0.2);
  border-color: #fbbf24;
}

.btn-tts-toggle:disabled,
.btn-tts-toggle.is-unsupported {
  opacity: 0.5;
  cursor: not-allowed;
  border-color: rgba(255, 255, 255, 0.15);
}

.tts-icon {
  font-size: 14px;
  line-height: 1;
}

.tts-label {
  line-height: 1;
}

.tts-state-badge {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.06em;
  padding: 2px 6px;
  border-radius: 3px;
  background: rgba(148, 163, 184, 0.2);
  color: #94a3b8;
  border: 1px solid rgba(148, 163, 184, 0.3);
  line-height: 1;
}

.tts-state-badge.state-on {
  background: rgba(52, 211, 153, 0.25);
  color: #34d399;
  border-color: rgba(52, 211, 153, 0.5);
  box-shadow: 0 0 6px rgba(52, 211, 153, 0.3);
  animation: ttsPulse 2s infinite ease-in-out;
}

.tts-state-badge.state-paused {
  background: rgba(251, 191, 36, 0.25);
  color: #fbbf24;
  border-color: rgba(251, 191, 36, 0.5);
  animation: none;
}

@keyframes ttsPulse {
  0%, 100% { opacity: 0.85; }
  50% { opacity: 1; box-shadow: 0 0 10px rgba(52, 211, 153, 0.6); }
}

.tts-playback-subcontrols {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.btn-tts-sub {
  height: 28px;
  padding: 0 9px;
  font-size: 11px;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: #e2e8f0;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.btn-tts-sub:hover {
  background: rgba(139, 92, 246, 0.25);
  border-color: #a855f7;
  color: #ffffff;
}

.btn-tts-stop:hover {
  background: rgba(239, 68, 68, 0.25);
  border-color: #ef4444;
  color: #fca5a5;
}

/* ── BGM MUTE TOGGLE IN ACADEMIC REVIEWER NAVBAR ── */
.btn-bgm-mute-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 32px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(139, 92, 246, 0.4);
  border-radius: 6px;
  color: #e2e8f0;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-bgm-mute-toggle:hover {
  background: rgba(139, 92, 246, 0.22);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-1px);
}

.btn-bgm-mute-toggle.is-muted {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.45);
  color: #fca5a5;
}

.btn-bgm-mute-toggle.is-muted:hover {
  background: rgba(239, 68, 68, 0.28);
  border-color: #ef4444;
}

.bgm-icon {
  font-size: 13px;
  line-height: 1;
}

.bgm-label {
  line-height: 1;
}

.status-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.status-badge.mastered {
  background-color: rgba(251, 191, 36, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.45);
}

.status-badge.completed {
  background-color: rgba(52, 211, 153, 0.15);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.45);
}

.status-badge.in-progress {
  background-color: rgba(96, 165, 250, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(96, 165, 250, 0.45);
}

.status-badge.available {
  background-color: rgba(148, 163, 184, 0.12);
  color: #cbd5e1;
  border: 1px solid rgba(148, 163, 184, 0.25);
}

.nav-reward-meta {
  font-size: 12px;
  color: #fbbf24;
  font-weight: 600;
  letter-spacing: 0.02em;
}

/* ── LOADING & ERROR STATES ── */
.academic-loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: 16px;
}

.academic-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #E5E5E5;
  border-top-color: #2457A7;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-label {
  font-size: 15px;
  color: #555555;
}

.academic-locked-container {
  max-width: 600px;
  margin: 60px auto;
  padding: 0 20px;
}

.locked-card {
  background-color: #FFFFFF;
  border: 1px solid #E5E5E5;
  border-radius: 8px;
  padding: 36px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.locked-icon-shield {
  font-size: 40px;
  margin-bottom: 16px;
}

.locked-heading {
  font-size: 22px;
  color: #222222;
  margin-bottom: 12px;
}

.locked-message {
  font-size: 15px;
  color: #555555;
  line-height: 1.6;
  margin-bottom: 24px;
}

/* ── MAIN READING DOCUMENT SHEET ── */
.academic-document-layout {
  width: 100%;
  background-color: #FFFFFF;
}

.academic-reading-column {
  max-width: 840px;
  margin: 0 auto;
  padding: 40px 24px 60px;
  background-color: #FFFFFF;
  box-sizing: border-box;
}

/* ── DOCUMENT HEADER ── */
.document-header {
  margin-bottom: 36px;
}

.document-pre-meta {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #737373;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.meta-dot {
  color: #CBD5E1;
}

.document-title {
  font-family: Georgia, "Times New Roman", Cambria, serif;
  font-size: 32px;
  font-weight: 700;
  color: #1A1A1A;
  line-height: 1.35;
  margin: 0 0 16px 0;
  letter-spacing: -0.01em;
}

@media (max-width: 640px) {
  .document-title {
    font-size: 26px;
  }
}

.document-scope-lead {
  font-size: 17px;
  line-height: 1.65;
  color: #4A4A4A;
  margin: 0 0 20px 0;
}

.document-header-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
}

.citation-tag {
  font-size: 12px;
  color: #737373;
  font-style: italic;
}

.header-action-links {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-doc-read-aloud {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background-color: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #1e293b;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.btn-doc-read-aloud:hover {
  background-color: #e2e8f0;
  border-color: #94a3b8;
  color: #0f172a;
}

.btn-doc-read-aloud.is-active {
  background-color: #f5f3ff;
  border-color: #8b5cf6;
  color: #6d28d9;
  box-shadow: 0 0 10px rgba(139, 92, 246, 0.2);
}

.btn-doc-read-aloud.is-paused {
  background-color: #fffbeb;
  border-color: #f59e0b;
  color: #b45309;
}

.doc-tts-icon {
  font-size: 13px;
  line-height: 1;
}

.doc-tts-counter {
  font-size: 10px;
  font-weight: 800;
  padding: 2px 5px;
  border-radius: 3px;
  background: #ede9fe;
  color: #7c3aed;
}

.btn-doc-read-aloud.is-paused .doc-tts-counter {
  background: #fef3c7;
  color: #b45309;
}

.jump-to-assessment-link {
  font-size: 13px;
  color: #2457A7;
  font-weight: 600;
  text-decoration: none;
}

.jump-to-assessment-link:hover {
  text-decoration: underline;
}

.header-divider-rule {
  height: 1px;
  background-color: #E5E5E5;
  margin-top: 8px;
}

/* ── MANUSCRIPT SECTIONS ── */
.manuscript-section {
  margin-bottom: 44px;
}

.section-heading {
  font-family: Georgia, "Times New Roman", Cambria, serif;
  font-size: 22px;
  font-weight: 700;
  color: #1A1A1A;
  margin: 0 0 18px 0;
  padding-bottom: 8px;
  border-bottom: 1px solid #F0F0F0;
  letter-spacing: -0.01em;
}

/* ── ACADEMIC PROSE & PARAGRAPHS ── */
.academic-prose {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.academic-paragraph {
  font-size: 16.5px;
  line-height: 1.78;
  color: #222222;
  margin: 0;
  text-align: left;
  white-space: pre-line;
}

/* ── UNDERSTATED ACADEMIC CALLOUT ── */
.academic-callout-note {
  margin-top: 24px;
  padding: 14px 18px;
  background-color: #F8FAFC;
  border-left: 3px solid #2457A7;
  border-radius: 0 6px 6px 0;
  font-size: 14.5px;
  line-height: 1.65;
  color: #334155;
}

.callout-lead {
  margin-bottom: 4px;
  color: #1E293B;
}

/* ── SECTION 5: CONCEPTS & DEFINITION LIST ── */
.subsection-block {
  margin-top: 28px;
}

.subsection-title {
  font-size: 17px;
  font-weight: 700;
  color: #333333;
  margin: 0 0 14px 0;
  padding-left: 8px;
  border-left: 3px solid #CBD5E1;
}

.concepts-definition-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 14px;
}

.concept-def-row {
  padding: 14px 18px;
  background-color: #FAFAFA;
  border: 1px solid #E5E5E5;
  border-radius: 6px;
}

.concept-def-term {
  font-size: 15px;
  color: #1A1A1A;
  margin-bottom: 6px;
}

.concept-def-text {
  font-size: 15px;
  line-height: 1.65;
  color: #444444;
}

/* ── ACADEMIC TERMS TABLE ── */
.academic-table-container {
  overflow-x: auto;
  border: 1px solid #E5E5E5;
  border-radius: 6px;
  margin-top: 14px;
}

.academic-terms-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14.5px;
}

.academic-terms-table th {
  background-color: #F7F7F7;
  color: #333333;
  font-weight: 700;
  padding: 12px 16px;
  border-bottom: 1px solid #E5E5E5;
  font-size: 13.5px;
  letter-spacing: 0.02em;
}

.academic-terms-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #F0F0F0;
  vertical-align: top;
  line-height: 1.6;
}

.academic-terms-table tr:last-child td {
  border-bottom: none;
}

.term-cell-name {
  color: #1A1A1A;
  background-color: #FCFCFC;
}

.term-cell-def {
  color: #444444;
}

/* ── CLINICAL VIGNETTE PAPER BLOCK ── */
.clinical-vignette-paper {
  background-color: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-left: 4px solid #2457A7;
  border-radius: 0 6px 6px 0;
  padding: 20px 24px;
  margin-bottom: 24px;
}

.vignette-header-bar {
  margin-bottom: 14px;
}

.vignette-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #2457A7;
}

.vignette-content-prose {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.vignette-takeaways-block {
  padding: 18px 20px;
  background-color: #FFFFFF;
  border: 1px solid #E5E5E5;
  border-radius: 6px;
}

.takeaways-heading {
  font-size: 15px;
  font-weight: 700;
  color: #222222;
  margin: 0 0 10px 0;
}

.academic-bullet-list {
  margin: 0;
  padding-left: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 15px;
  line-height: 1.65;
  color: #444444;
}

/* ── SECTION 8: KNOWLEDGE ASSESSMENT (SCORED) ── */
.assessment-section {
  padding-top: 24px;
  border-top: 2px solid #E5E5E5;
}

.assessment-header-banner {
  margin-bottom: 24px;
}

.assessment-meta-info {
  font-size: 14.5px;
  color: #555555;
  margin: 0;
}

.academic-questions-list {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.academic-question-card {
  padding: 24px;
  background-color: #FFFFFF;
  border: 1px solid #E5E5E5;
  border-radius: 8px;
  transition: border-color 0.15s ease;
}

.academic-question-card.is-answered {
  border-color: #CBD5E1;
}

.question-meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 12px;
  font-weight: 700;
  color: #737373;
}

.question-diff-tag {
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background-color: #F3F4F6;
  padding: 2px 8px;
  border-radius: 4px;
}

.question-stem-text {
  font-size: 16.5px;
  font-weight: 600;
  line-height: 1.6;
  color: #1A1A1A;
  margin-bottom: 20px;
}

.choices-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.choice-row-btn {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  background-color: #FFFFFF;
  border: 1px solid #D1D5DB;
  border-radius: 6px;
  color: #222222;
  font-size: 15px;
  line-height: 1.55;
  text-align: left;
  cursor: pointer;
  transition: all 0.15s ease;
}

.choice-row-btn:hover:not(:disabled) {
  background-color: #F8FAFC;
  border-color: #94A3B8;
}

.choice-row-btn.selected {
  background-color: #EFF6FF;
  border-color: #2457A7;
  color: #1E3A8A;
}

.choice-row-btn.disabled {
  cursor: default;
}

.choice-marker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  background-color: #F3F4F6;
  color: #374151;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 1px;
}

.choice-row-btn.selected .choice-marker {
  background-color: #2457A7;
  color: #FFFFFF;
}

.choice-text {
  flex: 1;
}

/* Post-Grading Explanation */
.question-explanation-panel {
  margin-top: 18px;
  padding: 16px;
  border-radius: 6px;
  font-size: 14.5px;
  line-height: 1.6;
}

.question-explanation-panel.exp-correct {
  background-color: #F0FDF4;
  border: 1px solid #BBF7D0;
  color: #166534;
}

.question-explanation-panel.exp-incorrect {
  background-color: #FEF2F2;
  border: 1px solid #FECACA;
  color: #991B1B;
}

.exp-status-heading {
  font-weight: 700;
  margin-bottom: 6px;
}

.exp-rationale-text {
  margin: 0;
}

/* Assessment Action Panel */
.assessment-action-panel {
  margin-top: 32px;
  padding: 20px 24px;
  background-color: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.completion-tracker-text {
  font-size: 14.5px;
  color: #4B5563;
}

.assessment-buttons {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

/* ── BUTTON STYLES ── */
.academic-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  font-size: 14.5px;
  font-weight: 600;
  border-radius: 6px;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 1px solid transparent;
}

.academic-btn.primary-btn {
  background-color: #2457A7;
  color: #FFFFFF;
  border-color: #1D4ED8;
}

.academic-btn.primary-btn:hover:not(:disabled) {
  background-color: #1E40AF;
  border-color: #1E3A8A;
}

.academic-btn.primary-btn:disabled {
  background-color: #94A3B8;
  border-color: #94A3B8;
  cursor: not-allowed;
  opacity: 0.7;
}

.academic-btn.secondary-btn {
  background-color: #FFFFFF;
  color: #374151;
  border-color: #D1D5DB;
}

.academic-btn.secondary-btn:hover {
  background-color: #F9FAFB;
  border-color: #9CA3AF;
  color: #111827;
}

.academic-btn.text-link {
  background-color: transparent;
  color: #2457A7;
  border-color: transparent;
  padding: 10px 12px;
}

.academic-btn.text-link:hover {
  text-decoration: underline;
}

/* ── FULL-BLEED APP WRAPPER OVERRIDES (PREVENTS SQUEEZING & PRESERVES HEADER) ── */
:global(.app-page-wrapper:has(.academic-reviewer-root)),
:global(.app-page-wrapper.has-hud:has(.academic-reviewer-root)) {
  max-width: none !important;
  margin: 0 !important;
  padding: 0 !important;
  background-color: transparent !important;
  width: 100% !important;
}

/* ── FOOTER NAVIGATION 3-COLUMN ACADEMIC GRID ── */
.manuscript-footer-nav {
  margin-top: 56px;
  padding-top: 28px;
  border-top: 1px solid #E5E5E5;
}

.footer-nav-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: stretch;
  gap: 16px;
}

@media (max-width: 768px) {
  .footer-nav-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

.academic-nav-card {
  display: flex;
  flex-direction: column;
  padding: 12px 18px;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.15s ease;
  height: 100%;
  box-sizing: border-box;
}

.academic-nav-card.card-prev {
  background-color: #FFFFFF;
  border: 1px solid #D1D5DB;
  color: #374151;
  text-align: left;
}

.academic-nav-card.card-prev:hover {
  background-color: #F8FAFC;
  border-color: #2457A7;
  color: #1D4ED8;
}

.academic-nav-card.card-next {
  background-color: #2457A7;
  border: 1px solid #1D4ED8;
  color: #FFFFFF;
  text-align: right;
}

.academic-nav-card.card-next:hover {
  background-color: #1E40AF;
  border-color: #1E3A8A;
}

.nav-card-dir {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.card-prev .nav-card-dir {
  color: #6B7280;
}

.card-next .nav-card-dir {
  color: #DBEAFE;
}

.nav-card-title {
  font-size: 13.5px;
  font-weight: 600;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.card-prev .nav-card-title {
  color: #1F2937;
}

.card-next .nav-card-title {
  color: #FFFFFF;
}

.nav-card-placeholder {
  display: flex;
  flex-direction: column;
  padding: 12px 18px;
  border-radius: 8px;
  background-color: #F9FAFB;
  border: 1px dashed #E5E7EB;
  color: #9CA3AF;
  font-size: 12px;
  height: 100%;
  box-sizing: border-box;
  justify-content: center;
}

.academic-nav-card.card-locked {
  display: flex;
  flex-direction: column;
  padding: 12px 18px;
  border-radius: 8px;
  background-color: #F9FAFB;
  border: 1px dashed #D1D5DB;
  color: #6B7280;
  text-align: right;
  height: 100%;
  box-sizing: border-box;
  justify-content: center;
  cursor: not-allowed;
  opacity: 0.9;
}

.academic-nav-card.card-locked .nav-card-dir {
  color: #B45309;
}

.academic-nav-card.card-locked .nav-card-title {
  color: #6B7280;
  font-size: 12.5px;
  font-weight: 500;
}

.academic-nav-card.card-locked.is-morning,
:global([data-theme="morning"]) .academic-nav-card.card-locked,
:global(.theme-morning) .academic-nav-card.card-locked {
  background-color: #FAF6EE !important;
  border-color: #D8C39E !important;
  color: #8C6A3E !important;
}

/* Assessment Inline Pills */
.assessment-inline-pill {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.03em;
  vertical-align: middle;
}

.assessment-inline-pill.pill-passed {
  background-color: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
}

.assessment-inline-pill.pill-remediation {
  background-color: #FEF2F2;
  color: #991B1B;
  border: 1px solid #FECACA;
}

/* ── RESTRAINED ACADEMIC MODAL ── */
.academic-modal-overlay {
  position: fixed;
  inset: 0;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  z-index: 99999;
  background-color: rgba(14, 8, 26, 0.7);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
}

.academic-modal-overlay.is-morning,
:global([data-theme="morning"]) .academic-modal-overlay,
:global(.theme-morning) .academic-modal-overlay {
  background-color: rgba(44, 30, 20, 0.45);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
}

.academic-modal-box {
  background-color: #FFFFFF;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.3);
  max-width: 540px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 32px;
  box-sizing: border-box;
  position: relative;
  animation: modalScaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalScaleIn {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(6px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-close-icon-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid #E5E7EB;
  background: #F9FAFB;
  color: #6B7280;
  font-size: 15px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  padding: 0;
}

.modal-close-icon-btn:hover {
  background: #F3F4F6;
  color: #111827;
  border-color: #D1D5DB;
}

/* Modal Morning Theme */
.academic-modal-box.is-morning,
:global([data-theme="morning"]) .academic-modal-box,
:global(.theme-morning) .academic-modal-box {
  background-color: #FFFDF8 !important;
  border: 1px solid #D8C39E !important;
  box-shadow: 0 20px 45px rgba(143, 100, 45, 0.22) !important;
  color: #2C1E14;
}

.academic-modal-box.is-morning .modal-close-icon-btn,
:global([data-theme="morning"]) .academic-modal-box .modal-close-icon-btn,
:global(.theme-morning) .academic-modal-box .modal-close-icon-btn {
  background: #FDF9F0;
  border-color: #E2D3BA;
  color: #7A5B30;
}

.academic-modal-box.is-morning .modal-close-icon-btn:hover,
:global([data-theme="morning"]) .academic-modal-box .modal-close-icon-btn:hover,
:global(.theme-morning) .academic-modal-box .modal-close-icon-btn:hover {
  background: #F7EEDB;
  border-color: #CDB793;
  color: #2C1E14;
}

.academic-modal-box.is-morning .modal-heading,
:global([data-theme="morning"]) .academic-modal-box .modal-heading,
:global(.theme-morning) .academic-modal-box .modal-heading {
  color: #2C1E14 !important;
}

.academic-modal-box.is-morning .modal-subheading,
:global([data-theme="morning"]) .academic-modal-box .modal-subheading,
:global(.theme-morning) .academic-modal-box .modal-subheading {
  color: #4A3525 !important;
}

.academic-modal-box.is-morning .modal-metrics-strip,
:global([data-theme="morning"]) .academic-modal-box .modal-metrics-strip,
:global(.theme-morning) .academic-modal-box .modal-metrics-strip {
  background-color: #FAF4E8 !important;
  border: 1px solid #E5D5B8 !important;
}

.academic-modal-box.is-morning .metric-val,
:global([data-theme="morning"]) .academic-modal-box .metric-val,
:global(.theme-morning) .academic-modal-box .metric-val {
  color: #8C5300 !important;
}

.academic-modal-box.is-morning .metric-label,
:global([data-theme="morning"]) .academic-modal-box .metric-label,
:global(.theme-morning) .academic-modal-box .metric-label {
  color: #7A5B30 !important;
}

.academic-modal-box.is-morning .remediation-note,
:global([data-theme="morning"]) .academic-modal-box .remediation-note,
:global(.theme-morning) .academic-modal-box .remediation-note {
  color: #5A422D !important;
}

.academic-modal-box.is-morning .academic-btn.secondary-btn,
:global([data-theme="morning"]) .academic-modal-box .academic-btn.secondary-btn,
:global(.theme-morning) .academic-modal-box .academic-btn.secondary-btn {
  background: #FAF5EA !important;
  border-color: #D8C39E !important;
  color: #4A3525 !important;
}

.academic-modal-box.is-morning .academic-btn.secondary-btn:hover,
:global([data-theme="morning"]) .academic-modal-box .academic-btn.secondary-btn:hover,
:global(.theme-morning) .academic-modal-box .academic-btn.secondary-btn:hover {
  background: #F4EAD3 !important;
  border-color: #B88A42 !important;
  color: #2C1E14 !important;
}

.academic-modal-box.is-morning .academic-btn.primary-btn,
:global([data-theme="morning"]) .academic-modal-box .academic-btn.primary-btn,
:global(.theme-morning) .academic-modal-box .academic-btn.primary-btn {
  background: #B45309 !important;
  border-color: #92400E !important;
  color: #FFFFFF !important;
}

.academic-modal-box.is-morning .academic-btn.primary-btn:hover,
:global([data-theme="morning"]) .academic-modal-box .academic-btn.primary-btn:hover,
:global(.theme-morning) .academic-modal-box .academic-btn.primary-btn:hover {
  background: #92400E !important;
}

.modal-status-pill {
  display: inline-block;
  padding: 3px 10px;
  background-color: #ECFDF5;
  color: #065F46;
  border: 1px solid #A7F3D0;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-bottom: 12px;
}

.modal-status-pill.fail-pill {
  background-color: #FEF2F2;
  color: #991B1B;
  border-color: #FECACA;
}

.modal-heading {
  font-family: Georgia, "Times New Roman", Cambria, serif;
  font-size: 24px;
  font-weight: 700;
  color: #1A1A1A;
  margin: 0 0 10px 0;
}

.modal-subheading {
  font-size: 15px;
  line-height: 1.6;
  color: #4B5563;
  margin: 0 0 20px 0;
}

.modal-metrics-strip {
  display: flex;
  gap: 12px;
  padding: 16px;
  background-color: #F8FAFC;
  border: 1px solid #E2E8F0;
  border-radius: 6px;
  margin-bottom: 20px;
}

.metric-cell {
  flex: 1;
  text-align: center;
  display: flex;
  flex-direction: column;
}

.metric-val {
  font-size: 18px;
  font-weight: 700;
  color: #1E293B;
}

.metric-label {
  font-size: 11px;
  text-transform: uppercase;
  color: #64748B;
  letter-spacing: 0.04em;
  margin-top: 2px;
}

.domain-completed-notice {
  padding: 12px 16px;
  background-color: #FEF3C7;
  border-left: 3px solid #D97706;
  font-size: 13.5px;
  line-height: 1.55;
  color: #78350F;
  margin-bottom: 20px;
}

.remediation-note {
  font-size: 14px;
  color: #4B5563;
  line-height: 1.55;
  margin-bottom: 20px;
}

.modal-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;
}

/* Modal Fade Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
