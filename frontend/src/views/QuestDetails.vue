<template>
  <div class="part-study-room animate-slide-up">
    <!-- Top Navigation Bar / Breadcrumbs -->
    <div class="study-top-bar">
      <router-link to="/quests" class="back-link">
        ← Back to Study Paths
      </router-link>

      <div class="top-nav-meta" v-if="part">
        <span class="domain-tag-pill" :style="{ backgroundColor: part.domain_color + '22', color: part.domain_color, borderColor: part.domain_color }">
          {{ part.domain_icon }} {{ part.domain_name }}
        </span>
        <span class="part-seq-pill">
          Part {{ part.part_number }} of 10
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Preparing Psychology study materials...</p>
    </div>

    <!-- Error / Locked State -->
    <div v-else-if="errorLocked" class="glass-card locked-warning-card">
      <div class="locked-icon">🔒</div>
      <h2>Learning Part Locked</h2>
      <p>{{ errorMessage || 'You must complete the previous learning part before accessing this module.' }}</p>
      <router-link to="/quests" class="btn btn-primary" style="margin-top: 1.5rem">
        Return to Study Path Roadmap
      </router-link>
    </div>

    <!-- Main Study Room Content -->
    <div v-else-if="part" class="study-container">
      
      <!-- Hero Banner -->
      <div class="glass-card part-hero-card" :style="{ '--domain-color': part.domain_color }">
        <div class="hero-header-row">
          <div class="hero-left">
            <div class="part-type-tag" :class="part.part_type">
              {{ formatPartType(part.part_type) }}
            </div>
            <h1 class="part-title-hero">
              Part {{ part.part_number }}: {{ part.title }}
            </h1>
            <p class="part-description-hero">
              {{ part.description }}
            </p>
          </div>

          <!-- Bounty & Status Box -->
          <div class="hero-bounty-box">
            <div class="bounty-badge">
              <span class="bounty-icon">⭐</span>
              <div>
                <span class="bounty-amount">+{{ part.xp_reward }} XP</span>
                <span class="bounty-sub">+{{ part.coin_reward }} Coins</span>
              </div>
            </div>

            <div class="status-indicator">
              <span v-if="part.user_status === 'mastered'" class="status-pill mastered">
                ⭐ Mastered ({{ part.user_score }}%)
              </span>
              <span v-else-if="part.user_status === 'completed'" class="status-pill completed">
                ✓ Passed ({{ part.user_score }}%)
              </span>
              <span v-else-if="part.user_status === 'in_progress'" class="status-pill in_progress">
                ⚡ In Progress
              </span>
              <span v-else class="status-pill available">
                ▶ Available
              </span>
            </div>
          </div>
        </div>

        <!-- Stage Navigation Tabs -->
        <div class="stage-nav-tabs">
          <button 
            class="stage-tab-btn"
            :class="{ active: activeStage === 'learn' }"
            @click="activeStage = 'learn'"
          >
            <span class="stage-step-num">1</span>
            <span class="stage-step-label">📖 Learn & Overview</span>
          </button>

          <button 
            class="stage-tab-btn"
            :class="{ active: activeStage === 'clinical' }"
            @click="activeStage = 'clinical'"
          >
            <span class="stage-step-num">2</span>
            <span class="stage-step-label">🩺 Applied Scenarios</span>
          </button>

          <button 
            class="stage-tab-btn"
            :class="{ active: activeStage === 'practice' }"
            @click="activeStage = 'practice'"
          >
            <span class="stage-step-num">3</span>
            <span class="stage-step-label">💡 Knowledge Check</span>
          </button>

          <button 
            class="stage-tab-btn challenge-tab"
            :class="{ active: activeStage === 'assessment' }"
            @click="activeStage = 'assessment'"
          >
            <span class="stage-step-num">4</span>
            <span class="stage-step-label">⚔️ Part Challenge</span>
            <span class="challenge-pill">Scored</span>
          </button>
        </div>
      </div>

      <!-- STAGE 1: Learn & Overview -->
      <div v-if="activeStage === 'learn'" class="stage-content-section animate-fade-in">
        
        <!-- Overview Card -->
        <div class="glass-card study-module-card">
          <div class="module-title-row">
            <span class="module-icon">📚</span>
            <div>
              <h2 class="module-title">Foundational Overview</h2>
              <p class="module-subtitle">Core empirical findings and structural mechanisms</p>
            </div>
          </div>
          <div class="overview-body">
            <p>{{ part.overview }}</p>
            <div class="exam-notice-box">
              <span class="notice-icon">🎯</span>
              <div>
                <strong>Licensure Board Focus:</strong>
                <span> Pay special attention to distinguishing between empirical definitions and operationalizing behavioral variables under this topic.</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Key Concepts Grid -->
        <div class="glass-card study-module-card" v-if="part.key_concepts && part.key_concepts.length">
          <div class="module-title-row">
            <span class="module-icon">🔬</span>
            <div>
              <h2 class="module-title">Key Psychological Concepts</h2>
              <p class="module-subtitle">Core principles examined in standard undergraduate and board curricula</p>
            </div>
          </div>

          <div class="concepts-grid">
            <div 
              v-for="(concept, cIdx) in part.key_concepts" 
              :key="cIdx"
              class="concept-card"
            >
              <div class="concept-icon-box">{{ concept.icon || '📌' }}</div>
              <h3 class="concept-name">{{ concept.title }}</h3>
              <p class="concept-def">{{ concept.definition }}</p>
            </div>
          </div>
        </div>

        <!-- Important Terms Dictionary -->
        <div class="glass-card study-module-card" v-if="part.important_terms && part.important_terms.length">
          <div class="module-title-row">
            <span class="module-icon">📖</span>
            <div>
              <h2 class="module-title">Board Exam Vocabulary & Definitions</h2>
              <p class="module-subtitle">Key terms frequently tested in multiple-choice exams</p>
            </div>
          </div>

          <div class="terms-table-wrapper">
            <div class="terms-grid">
              <div 
                v-for="(term, tIdx) in part.important_terms" 
                :key="tIdx"
                class="term-row"
              >
                <div class="term-name">
                  <strong>{{ term.term }}</strong>
                </div>
                <div class="term-def">
                  {{ term.definition }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Next Stage Action -->
        <div class="stage-footer-actions">
          <button class="btn btn-primary btn-lg" @click="activeStage = 'clinical'">
            Next: Review Applied Scenarios →
          </button>
        </div>

      </div>

      <!-- STAGE 2: Applied & Clinical Scenarios -->
      <div v-else-if="activeStage === 'clinical'" class="stage-content-section animate-fade-in">
        
        <div class="glass-card study-module-card">
          <div class="module-title-row">
            <span class="module-icon">🩺</span>
            <div>
              <h2 class="module-title">Clinical & Applied Vignette</h2>
              <p class="module-subtitle">How this principle presents in clinical practice, counseling, and laboratory testing</p>
            </div>
          </div>

          <div class="clinical-case-box">
            <div class="case-badge">CASE SCENARIO</div>
            <p class="case-text">{{ part.clinical_example }}</p>
          </div>

          <div class="case-takeaways">
            <h4>Clinical Insights & Board Implications</h4>
            <ul class="takeaway-list">
              <li>Differential diagnosis requires ruling out physiological or sensory impairments before attributing behaviors solely to cognitive or personality traits.</li>
              <li>Always verify that assessment instruments demonstrate robust construct and predictive validity for the specific target population.</li>
              <li>In counseling and therapy settings, treatment planning must align directly with the client's current developmental stage and psychosocial environment.</li>
            </ul>
          </div>
        </div>

        <!-- Next Stage Action -->
        <div class="stage-footer-actions">
          <button class="btn btn-secondary btn-lg" @click="activeStage = 'learn'">
            ← Previous: Overview
          </button>
          <button class="btn btn-primary btn-lg" @click="activeStage = 'practice'">
            Next: Knowledge Check →
          </button>
        </div>

      </div>

      <!-- STAGE 3: Knowledge Check -->
      <div v-else-if="activeStage === 'practice'" class="stage-content-section animate-fade-in">
        
        <div class="glass-card study-module-card">
          <div class="module-title-row">
            <span class="module-icon">💡</span>
            <div>
              <h2 class="module-title">Self-Review Checklist</h2>
              <p class="module-subtitle">Verify your retention of the core topics before starting the scored challenge</p>
            </div>
          </div>

          <div class="review-checklist">
            <div class="checklist-item" @click="toggleCheck(1)">
              <div class="checkbox-circle" :class="{ checked: checkedItems.includes(1) }">
                <span v-if="checkedItems.includes(1)">✓</span>
              </div>
              <div class="check-text">
                <strong>Foundational Models:</strong> I can clearly articulate the primary theoretical framework behind {{ part.title }}.
              </div>
            </div>

            <div class="checklist-item" @click="toggleCheck(2)">
              <div class="checkbox-circle" :class="{ checked: checkedItems.includes(2) }">
                <span v-if="checkedItems.includes(2)">✓</span>
              </div>
              <div class="check-text">
                <strong>Key Terminology:</strong> I understand all operational definitions and distinctions listed in the vocabulary section.
              </div>
            </div>

            <div class="checklist-item" @click="toggleCheck(3)">
              <div class="checkbox-circle" :class="{ checked: checkedItems.includes(3) }">
                <span v-if="checkedItems.includes(3)">✓</span>
              </div>
              <div class="check-text">
                <strong>Applied Case Analysis:</strong> I can identify practical examples and clinical applications of this psychological phenomenon.
              </div>
            </div>
          </div>

          <div class="challenge-ready-banner">
            <div class="banner-icon">⚔️</div>
            <div>
              <h4>Ready for the Scored Part Challenge?</h4>
              <p>Passing score is <strong>{{ part.passing_score }}%</strong>. Scoring 90%+ awards the <strong>⭐ Mastered</strong> badge and bonus rewards!</p>
            </div>
          </div>
        </div>

        <div class="stage-footer-actions">
          <button class="btn btn-secondary btn-lg" @click="activeStage = 'clinical'">
            ← Previous: Applied Scenarios
          </button>
          <button class="btn btn-primary btn-lg" @click="activeStage = 'assessment'">
            Begin Scored Challenge →
          </button>
        </div>

      </div>

      <!-- STAGE 4: Scored Part Challenge Assessment -->
      <div v-else-if="activeStage === 'assessment'" class="stage-content-section animate-fade-in">
        
        <!-- Challenge Card Header -->
        <div class="glass-card study-module-card">
          <div class="module-title-row">
            <span class="module-icon">⚔️</span>
            <div>
              <h2 class="module-title">Part {{ part.part_number }} Assessment Challenge</h2>
              <p class="module-subtitle">
                {{ part.questions?.length || 0 }} Questions • Passing Score: {{ part.passing_score }}% • Reward: +{{ part.xp_reward }} XP & +{{ part.coin_reward }} Coins
              </p>
            </div>
          </div>

          <!-- Questions Container -->
          <div v-if="part.questions && part.questions.length" class="questions-list">
            <div 
              v-for="(q, qIndex) in part.questions" 
              :key="q.id"
              class="question-block"
              :class="{ 'answered': userAnswers[q.id] }"
            >
              <div class="question-header">
                <span class="q-number">Question {{ qIndex + 1 }} of {{ part.questions.length }}</span>
                <span class="q-diff-badge" :class="q.difficulty">{{ q.difficulty }}</span>
                <span v-if="q.is_scenario" class="scenario-badge">Scenario</span>
              </div>

              <div class="q-text">
                {{ q.question }}
              </div>

              <!-- Options Grid -->
              <div class="options-grid">
                <button 
                  v-for="opt in ['A', 'B', 'C', 'D']" 
                  :key="opt"
                  class="option-choice-btn"
                  :class="{ 
                    'selected': userAnswers[q.id] === opt,
                    'disabled': submitted
                  }"
                  @click="selectAnswer(q.id, opt)"
                  :disabled="submitted"
                >
                  <span class="option-letter">{{ opt }}</span>
                  <span class="option-content">{{ q['choice_' + opt.toLowerCase()] }}</span>
                </button>
              </div>

              <!-- Instant Explanation (Visible after grading) -->
              <div 
                v-if="submitted && gradingReview[q.id]" 
                class="q-explanation-box"
                :class="gradingReview[q.id].is_correct ? 'correct-exp' : 'wrong-exp'"
              >
                <div class="exp-title">
                  <span v-if="gradingReview[q.id].is_correct">✅ Correct!</span>
                  <span v-else>❌ Incorrect (Correct: Option {{ gradingReview[q.id].correct_answer }})</span>
                </div>
                <p class="exp-text">{{ gradingReview[q.id].explanation }}</p>
              </div>

            </div>
          </div>

          <!-- Assessment Actions Bar -->
          <div class="assessment-submit-bar">
            <div class="answer-count-tracker">
              <strong>{{ answeredCount }} / {{ part.questions?.length || 0 }}</strong> Questions Answered
            </div>

            <button 
              v-if="!submitted"
              class="btn btn-primary btn-lg submit-challenge-btn"
              :disabled="answeredCount < (part.questions?.length || 0) || submitting"
              @click="submitAssessment"
            >
              {{ submitting ? 'Grading Challenge...' : '⚔️ Submit Assessment' }}
            </button>

            <template v-else>
              <button 
                class="btn btn-secondary btn-lg"
                @click="retryAssessment"
              >
                🔄 {{ submissionResult?.is_passed ? 'Retake Challenge' : 'Retry Challenge' }}
              </button>

              <router-link
                v-if="submissionResult?.next_part"
                :to="`/quests/part/${submissionResult.next_part.id}`"
                class="btn btn-primary btn-lg next-part-btn"
              >
                Next: Part {{ submissionResult.next_part.part_number }} →
              </router-link>

              <router-link
                v-else
                to="/quests"
                class="btn btn-primary btn-lg next-part-btn"
              >
                🏆 Domain Completed! Return to Roadmap →
              </router-link>
            </template>
          </div>

        </div>

      </div>

      <!-- CELEBRATION / RESULT MODAL -->
      <transition name="modal">
        <div v-if="showResultModal && submissionResult" class="modal-backdrop" @click="showResultModal = false">
          <div class="modal-card glass-card" @click.stop>
            
            <!-- Passed State -->
            <div v-if="submissionResult.is_passed" class="result-modal-content pass-mode">
              <div class="result-trophy">{{ submissionResult.is_mastered ? '⭐' : '🏆' }}</div>
              
              <h2 class="result-title">
                {{ submissionResult.is_mastered ? 'Part Mastered!' : 'Part Completed!' }}
              </h2>
              <p class="result-subtitle">
                You passed with a score of <strong>{{ submissionResult.score }}%</strong> ({{ submissionResult.correct_count }}/{{ submissionResult.total_questions }} correct).
              </p>

              <!-- Rewards Claimed Box -->
              <div class="claimed-rewards-box">
                <div class="claimed-reward-item">
                  <span class="claimed-icon">⭐</span>
                  <div class="claimed-info">
                    <span class="claimed-val">+{{ submissionResult.xp_awarded }} XP</span>
                    <span class="claimed-label">Experience</span>
                  </div>
                </div>

                <div class="claimed-reward-item">
                  <span class="claimed-icon">🪙</span>
                  <div class="claimed-info">
                    <span class="claimed-val">+{{ submissionResult.coins_awarded }} Coins</span>
                    <span class="claimed-label">StudyQuest Coins</span>
                  </div>
                </div>
              </div>

              <!-- Domain Completion Bonus if triggered -->
              <div v-if="submissionResult.domain_completed" class="domain-completed-banner animate-glow">
                <span class="domain-banner-icon">🏛️</span>
                <div>
                  <h4>DOMAIN COMPLETE!</h4>
                  <p>You have mastered all 10 parts of {{ part.domain_name }}! +500 XP and +300 Coins awarded.</p>
                </div>
              </div>

              <!-- Achievements Unlocked -->
              <div v-if="submissionResult.new_achievements && submissionResult.new_achievements.length" class="achievements-banner">
                <span class="ach-icon">🎖️</span>
                <div>
                  <strong>Achievement Unlocked:</strong>
                  <span> {{ submissionResult.new_achievements[0].name }} — {{ submissionResult.new_achievements[0].description }}</span>
                </div>
              </div>

              <div class="modal-actions">
                <button class="btn btn-secondary" @click="showResultModal = false">
                  Review Rationales
                </button>
                <button 
                  v-if="submissionResult.next_part"
                  class="btn btn-primary next-step-btn"
                  @click="goToNextPart"
                >
                  Proceed to Part {{ submissionResult.next_part.part_number }} →
                </button>
                <router-link
                  v-else
                  to="/quests"
                  class="btn btn-primary next-step-btn"
                >
                  Return to Roadmap →
                </router-link>
              </div>
            </div>

            <!-- Failed State -->
            <div v-else class="result-modal-content fail-mode">
              <div class="result-trophy">📚</div>
              <h2 class="result-title">Review & Retry</h2>
              <p class="result-subtitle">
                You scored <strong>{{ submissionResult.score }}%</strong>. A minimum score of <strong>{{ submissionResult.passing_score }}%</strong> is required to unlock the next part.
              </p>

              <div class="fail-tip-box">
                <span>💡 Don't worry! Review the detailed rationales below and retry whenever you're ready.</span>
              </div>

              <div class="modal-actions">
                <button class="btn btn-secondary" @click="showResultModal = false">
                  Inspect Explanations
                </button>
                <button class="btn btn-primary" @click="retryAssessment">
                  🔄 Retry Now
                </button>
              </div>
            </div>

          </div>
        </div>
      </transition>

      <!-- Bottom Part Navigator (Prev / Roadmap / Next) -->
      <div class="part-nav-footer">
        <router-link
          v-if="part.prev_part"
          :to="`/quests/part/${part.prev_part.id}`"
          class="btn btn-secondary part-nav-btn flex items-center gap-1.5"
        >
          ← Part {{ part.prev_part.part_number }}: {{ part.prev_part.title }}
        </router-link>
        <div v-else class="part-nav-spacer"></div>

        <router-link to="/quests" class="btn btn-secondary part-nav-btn flex items-center gap-1.5">
          🗺️ Return to Chapter Roadmap
        </router-link>

        <router-link
          v-if="part.next_part"
          :to="`/quests/part/${part.next_part.id}`"
          class="btn btn-primary part-nav-btn flex items-center gap-1.5"
        >
          Part {{ part.next_part.part_number }}: {{ part.next_part.title }} →
        </router-link>
        <div v-else class="part-nav-spacer"></div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import curriculumService from '../services/curriculumService'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const errorLocked = ref(false)
const errorMessage = ref('')
const part = ref(null)
const activeStage = ref('learn')

// Self-check
const checkedItems = ref([])

// Assessment
const userAnswers = ref({})
const submitting = ref(false)
const submitted = ref(false)
const submissionResult = ref(null)
const gradingReview = ref({})
const showResultModal = ref(false)
const startTime = ref(Date.now())

onMounted(() => {
  loadPart()
})

watch(() => [route.params.partId, route.params.id], () => {
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
  checkedItems.value = []
  activeStage.value = 'learn'
  startTime.value = Date.now()

  const rawId = route.params.partId || route.params.id
  const partId = parseInt(rawId)
  if (!partId) {
    loading.value = false
    errorLocked.value = true
    errorMessage.value = 'Invalid part identifier'
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
      errorMessage.value = 'Part could not be found or loaded.'
    }
  } finally {
    loading.value = false
  }
}

function selectAnswer(questionId, choice) {
  if (submitted.value) return
  userAnswers.value[questionId] = choice
}

const answeredCount = computed(() => {
  return Object.keys(userAnswers.value).length
})

function toggleCheck(id) {
  const idx = checkedItems.value.indexOf(id)
  if (idx > -1) {
    checkedItems.value.splice(idx, 1)
  } else {
    checkedItems.value.push(id)
  }
}

async function submitAssessment() {
  if (answeredCount.value < (part.value?.questions?.length || 0)) return
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

      // Index review details for fast lookup
      if (res.review) {
        const map = {}
        res.review.forEach(item => {
          map[item.question_id] = item
        })
        gradingReview.value = map
      }

      // Update user state
      if (res.user) {
        authStore.user = res.user
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
  activeStage.value = 'assessment'
}

function goToNextPart() {
  showResultModal.value = false
  if (submissionResult.value?.next_part?.id) {
    router.push(`/quests/part/${submissionResult.value.next_part.id}`)
  } else {
    router.push('/quests')
  }
}

function formatPartType(type) {
  const map = {
    learn: '📖 Foundations',
    review: '📝 Comprehensive Review',
    practice: '💡 Skills Practice',
    scenario: '🩺 Clinical Scenario',
    quiz: '⚡ Diagnostic Quiz',
    challenge: '⚔️ Topic Challenge',
    mastery: '🏆 Domain Mastery Challenge'
  }
  return map[type] || 'Psychology Study Part'
}
</script>

<style scoped>
.part-study-room {
  max-width: 1140px;
  margin: 0 auto;
  padding-bottom: 5rem;
  color: #e2d9cc;
}

/* ══ TOP BAR ══ */
.study-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.75rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.back-link {
  color: #e6c845;
  font-family: 'Cinzel', serif;
  font-weight: 700;
  text-decoration: none;
  font-size: 0.95rem;
  letter-spacing: 0.04em;
  transition: color 0.2s;
}
.back-link:hover {
  color: #fef08a;
  text-shadow: 0 0 8px rgba(230, 200, 69, 0.4);
}

.top-nav-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.domain-tag-pill {
  padding: 0.35rem 0.85rem;
  border-radius: 2px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  border: 1px solid;
  box-shadow: 0 0 0 1px #050608;
}

.part-seq-pill {
  background: #0d0e15;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608;
  color: #8c887b;
  padding: 0.35rem 0.85rem;
  border-radius: 2px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}

/* ══ LOADING ══ */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6rem 2rem;
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.3rem;
}
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(181, 148, 16, 0.2);
  border-top-color: #e6c845;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ══ LOCKED CARD ══ */
.locked-warning-card {
  text-align: center;
  padding: 4rem 2rem;
  max-width: 600px;
  margin: 3rem auto;
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border: 2px solid #8b1a1a;
  box-shadow: 0 0 0 2px #050608, 0 0 25px rgba(139, 26, 26, 0.3);
  border-radius: 4px;
}
.locked-icon {
  font-size: 3.5rem;
  margin-bottom: 1rem;
}
.locked-warning-card h2 {
  color: #f3ede2;
  font-family: 'Cinzel', serif;
  margin-bottom: 0.5rem;
}
.locked-warning-card p {
  color: #9ca3af;
  line-height: 1.5;
}

/* ══ PART HERO ══ */
.part-hero-card {
  padding: 2rem;
  margin-bottom: 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}

.hero-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.hero-left {
  flex: 1;
  min-width: 300px;
}

.part-type-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
  letter-spacing: 0.08em;
  margin-bottom: 0.6rem;
}

.part-title-hero {
  font-family: 'Cinzel', serif;
  font-size: 1.85rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
}

.part-description-hero {
  font-size: 0.95rem;
  color: #9ca3af;
  line-height: 1.5;
  margin: 0;
  max-width: 680px;
}

.hero-bounty-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.75rem;
}

.bounty-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #09090e;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608, 0 0 12px rgba(181, 148, 16, 0.15);
  padding: 0.6rem 1.2rem;
  border-radius: 2px;
}
.bounty-icon {
  font-size: 1.6rem;
}
.bounty-amount {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  color: #e6c845;
}
.bounty-sub {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #34d399;
  margin-top: 0.2rem;
}

.status-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.8rem;
  border-radius: 2px;
  box-shadow: 0 0 0 1px #050608;
}
.status-pill.mastered {
  background: rgba(181, 148, 16, 0.2);
  color: #e6c845;
  border: 1px solid #b59410;
}
.status-pill.completed {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid #10b981;
}
.status-pill.in_progress {
  background: rgba(181, 148, 16, 0.15);
  color: #e6c845;
  border: 1px solid #b59410;
}
.status-pill.available {
  background: #09090e;
  color: #71717a;
  border: 1px solid #202230;
}

/* ══ STAGE NAVIGATION TABS ══ */
.stage-nav-tabs {
  display: flex;
  gap: 0.75rem;
  border-top: 2px solid #1f202d;
  padding-top: 1.5rem;
  flex-wrap: wrap;
}

.stage-tab-btn {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: #0d0e15;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  padding: 0.65rem 1.25rem;
  border-radius: 2px;
  color: #8c887b;
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}
.stage-tab-btn:hover {
  background: #151622;
  color: #f3ede2;
  border-color: #b59410;
}
.stage-tab-btn.active {
  background: #1c1825;
  border-color: #b59410;
  color: #e6c845;
  box-shadow: 0 0 0 1px #050608, 0 0 15px rgba(181, 148, 16, 0.4);
}
.stage-step-num {
  width: 22px;
  height: 22px;
  border-radius: 2px;
  background: #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: inherit;
}
.challenge-tab .challenge-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  background: rgba(181, 148, 16, 0.3);
  color: #e6c845;
  padding: 0.1rem 0.4rem;
  border-radius: 2px;
}

/* ══ STUDY MODULE CARDS ══ */
.study-module-card {
  padding: 2rem;
  margin-bottom: 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.module-title-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.module-icon {
  font-size: 2rem;
}
.module-title {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0;
}
.module-subtitle {
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  color: #8c887b;
  margin: 0.2rem 0 0 0;
}

.overview-body {
  color: #d1d5db;
  line-height: 1.7;
  font-size: 1.05rem;
}
.exam-notice-box {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  background: rgba(181, 148, 16, 0.08);
  border-left: 4px solid #b59410;
  padding: 1rem 1.25rem;
  border-radius: 0 2px 2px 0;
  margin-top: 1.5rem;
  font-size: 0.9rem;
  color: #fef08a;
}
.notice-icon {
  font-size: 1.3rem;
}

/* ══ CONCEPTS GRID ══ */
.concepts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}
.concept-card {
  background: #0d0e15;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1.4rem;
  transition: transform 0.2s, border-color 0.2s;
}
.concept-card:hover {
  transform: translateY(-2px);
  border-color: #b59410;
}
.concept-icon-box {
  font-size: 1.8rem;
  margin-bottom: 0.75rem;
}
.concept-name {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0 0 0.5rem 0;
}
.concept-def {
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.5;
  margin: 0;
}

/* ══ TERMS ══ */
.terms-grid {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
.term-row {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.5rem;
  padding: 1rem 1.25rem;
  background: #0d0e15;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
}
@media (max-width: 768px) {
  .term-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
}
.term-name {
  color: #e6c845;
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 0.95rem;
}
.term-def {
  color: #cbd5e1;
  font-size: 0.9rem;
  line-height: 1.5;
}

/* ══ CLINICAL VIGNETTE ══ */
.clinical-case-box {
  background: #0a0a0f;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1.5rem;
  margin-bottom: 1.75rem;
}
.case-badge {
  display: inline-block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  padding: 0.2rem 0.6rem;
  border-radius: 2px;
  margin-bottom: 0.85rem;
}
.case-text {
  font-size: 1.05rem;
  line-height: 1.7;
  color: #f3ede2;
  margin: 0;
}
.case-takeaways h4 {
  color: #f3ede2;
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  margin-bottom: 0.85rem;
}
.takeaway-list {
  padding-left: 1.25rem;
  color: #9ca3af;
  line-height: 1.7;
  font-size: 0.92rem;
}

/* ══ REVIEW CHECKLIST ══ */
.review-checklist {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 2rem;
}
.checklist-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #0d0e15;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  padding: 1.25rem;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}
.checklist-item:hover {
  background: #141520;
  border-color: #b59410;
}
.checkbox-circle {
  width: 28px;
  height: 28px;
  border-radius: 2px;
  border: 2px solid #2b2d42;
  box-shadow: 0 0 0 1px #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 800;
  color: #ffffff;
  flex-shrink: 0;
  transition: all 0.2s;
}
.checkbox-circle.checked {
  background: #10b981;
  border-color: #10b981;
}
.check-text {
  color: #e2e8f0;
  font-size: 0.95rem;
  line-height: 1.4;
}
.challenge-ready-banner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  background: rgba(181, 148, 16, 0.1);
  border: 1px solid rgba(181, 148, 16, 0.3);
  padding: 1.25rem 1.5rem;
  border-radius: 2px;
}
.banner-icon {
  font-size: 2.2rem;
}
.challenge-ready-banner h4 {
  color: #f3ede2;
  font-family: 'Cinzel', serif;
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
}
.challenge-ready-banner p {
  color: #9ca3af;
  margin: 0;
  font-size: 0.9rem;
}

/* ══ QUESTION BLOCKS ══ */
.questions-list {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  margin-bottom: 2rem;
}
.question-block {
  background: #0d0e15;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1.75rem;
  transition: border-color 0.2s;
}
.question-block.answered {
  border-color: #b59410;
}
.question-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.q-number {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #8c887b;
}
.q-diff-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
  text-transform: capitalize;
}
.q-diff-badge.beginner { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.q-diff-badge.intermediate { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
.q-diff-badge.advanced { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.q-diff-badge.board_exam { background: rgba(220, 38, 38, 0.2); color: #f87171; }
.scenario-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  background: rgba(181, 148, 16, 0.2);
  color: #e6c845;
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
}

.q-text {
  font-size: 1.05rem;
  color: #f3ede2;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  font-weight: 500;
}

.options-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.option-choice-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-align: left;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 0.9rem 1.25rem;
  color: #cbd5e1;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}
.option-choice-btn:hover:not(.disabled) {
  background: #141520;
  border-color: #b59410;
}
.option-choice-btn.selected {
  background: rgba(181, 148, 16, 0.15);
  border-color: #e6c845;
  color: #f3ede2;
  box-shadow: 0 0 10px rgba(181, 148, 16, 0.3);
}
.option-letter {
  width: 30px;
  height: 30px;
  border-radius: 2px;
  background: #141520;
  border: 1px solid #2b2d42;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  flex-shrink: 0;
  color: #e6c845;
}
.option-choice-btn.selected .option-letter {
  background: #b59410;
  color: #050608;
  border-color: #e6c845;
}
.option-content {
  flex: 1;
  line-height: 1.4;
}

/* ══ EXPLANATION ══ */
.q-explanation-box {
  margin-top: 1.25rem;
  padding: 1.15rem 1.4rem;
  border-radius: 2px;
}
.q-explanation-box.correct-exp {
  background: rgba(16, 185, 129, 0.1);
  border-left: 4px solid #10b981;
}
.q-explanation-box.wrong-exp {
  background: rgba(220, 38, 38, 0.1);
  border-left: 4px solid #dc2626;
}
.exp-title {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 0.95rem;
  margin-bottom: 0.4rem;
}
.correct-exp .exp-title { color: #34d399; }
.wrong-exp .exp-title { color: #f87171; }
.exp-text {
  margin: 0;
  font-size: 0.9rem;
  color: #9ca3af;
  line-height: 1.5;
}

/* ══ ASSESSMENT SUBMIT BAR ══ */
.assessment-submit-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 2px solid #1f202d;
  flex-wrap: wrap;
  gap: 1rem;
}
.answer-count-tracker {
  color: #8c887b;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}
.answer-count-tracker strong {
  color: #e6c845;
}
.submit-challenge-btn {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  padding: 0.85rem 2rem;
  letter-spacing: 0.04em;
}

/* ══ STAGE ACTIONS ══ */
.stage-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

/* ══ MODAL ══ */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.94);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 1.5rem;
}
.modal-card {
  width: 100%;
  max-width: 520px;
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 20px 40px rgba(0, 0, 0, 0.95), 0 0 25px rgba(181, 148, 16, 0.25);
  padding: 2.5rem;
  border-radius: 4px;
  text-align: center;
  position: relative;
}

.result-trophy {
  font-size: 3.5rem;
  margin-bottom: 0.75rem;
}
.result-title {
  font-family: 'Cinzel', serif;
  font-size: 1.8rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0 0 0.5rem 0;
}
.result-subtitle {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  margin: 0 0 1.75rem 0;
  line-height: 1.5;
}
.claimed-rewards-box {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}
.claimed-reward-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.claimed-icon {
  font-size: 1.8rem;
}
.claimed-info {
  text-align: left;
}
.claimed-val {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.9rem;
  color: #e6c845;
}
.claimed-label {
  font-size: 0.75rem;
  color: #8c887b;
}

.domain-completed-banner {
  background: rgba(181, 148, 16, 0.12);
  border: 1px solid #b59410;
  border-radius: 2px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-align: left;
  margin-bottom: 1.5rem;
}
.domain-banner-icon {
  font-size: 2rem;
}
.domain-completed-banner h4 {
  color: #e6c845;
  font-family: 'Cinzel', serif;
  margin: 0 0 0.2rem 0;
  font-size: 1rem;
}
.domain-completed-banner p {
  color: #cbd5e1;
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.4;
}

.achievements-banner {
  background: rgba(181, 148, 16, 0.12);
  border: 1px solid #b59410;
  padding: 0.85rem 1rem;
  border-radius: 2px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.85rem;
  color: #f3ede2;
  text-align: left;
  margin-bottom: 1.5rem;
}
.ach-icon {
  font-size: 1.4rem;
}

.fail-tip-box {
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.3);
  padding: 1rem;
  border-radius: 2px;
  font-size: 0.9rem;
  color: #fca5a5;
  margin-bottom: 1.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}
.next-step-btn {
  font-family: 'Cinzel', serif;
  font-weight: 700;
}

@media (max-width: 768px) {
  .part-study-room {
    padding-bottom: 3rem;
  }
  .part-hero-card {
    padding: 1.25rem;
    margin-bottom: 1.25rem;
  }
  .hero-header-row {
    flex-direction: column;
    gap: 1.25rem;
    align-items: stretch;
  }
  .hero-left {
    min-width: unset;
  }
  .part-title-hero {
    font-size: 1.35rem;
  }
  .hero-bounty-box {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
  }
  .stage-nav-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
  }
  .stage-tab-btn {
    padding: 0.6rem 0.5rem;
    font-size: 0.82rem;
    justify-content: flex-start;
  }
  .study-module-card {
    padding: 1.25rem;
    margin-bottom: 1.25rem;
  }
  .module-title {
    font-size: 1.15rem;
  }
  .module-icon {
    font-size: 1.5rem;
  }
  .overview-body {
    font-size: 0.95rem;
  }
  .assessment-submit-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .submit-challenge-btn {
    width: 100%;
  }
  .stage-footer-actions {
    flex-direction: column;
    width: 100%;
  }
  .stage-footer-actions .btn {
    width: 100%;
  }
  .modal-card {
    padding: 1.5rem 1rem;
  }
  .claimed-rewards-box {
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
  }
  .modal-actions {
    flex-direction: column;
    width: 100%;
  }
  .modal-actions .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .stage-nav-tabs {
    grid-template-columns: 1fr;
  }
  .concepts-grid {
    grid-template-columns: 1fr;
  }
  .hero-bounty-box {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .bounty-badge {
    width: 100%;
    justify-content: space-between;
  }
  .study-top-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .top-nav-meta {
    width: 100%;
    justify-content: space-between;
  }
}

/* ── BOTTOM PART NAVIGATOR ── */
.part-nav-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 2rem;
  padding: 1.5rem 0 2rem 0;
  border-top: 1px solid #1f202d;
  flex-wrap: wrap;
}
.part-nav-btn {
  font-size: 0.85rem;
  padding: 0.6rem 1.1rem;
}
.part-nav-spacer {
  flex: 1;
}
</style>
