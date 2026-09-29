<template>
  <div class="study-part">
    <!-- Gothic Atmosphere Backdrop -->
    <div class="sp-bg">
      <img :src="bgImageUrl" class="sp-bg-img" alt="Backdrop" />
      <div class="sp-bg-overlay"></div>
    </div>
    <div class="sp-content">

      <div v-if="loading" class="loading-center">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNSEALING GRIMOIRE…</p>
      </div>
      <div v-else-if="error" class="error-box gothic-panel animate-scale-up">
        <div class="error-icon">🔒</div>
        <h3 class="error-title">{{ error }}</h3>
        <router-link :to="'/study/' + domainId" class="btn btn-gothic-stone">← RETURN TO DOMAIN</router-link>
      </div>

      <!-- PHASE: LEARN ─────────────────────────────── -->
      <template v-else-if="phase === 'learn'">
        <div class="sp-learn gothic-panel animate-scale-up">
          <div class="spl-header">
            <router-link :to="'/study/' + domainId" class="btn btn-gothic-stone btn-sm">← DOMAIN</router-link>
            <div class="spl-meta">
              <span class="spl-badge-dsm">DSM-5</span>
              <span class="spl-domain">{{ part?.domain_name || 'PSYCHIATRIC DISORDERS' }}</span>
              <span class="spl-sep">·</span>
              <span>PART {{ part?.part_number }}</span>
            </div>
          </div>

          <h2 class="spl-title">{{ part?.title }}</h2>
          <div class="spl-source-ref" v-if="part?.source_reference">
            <span class="sr-label">Source Basis:</span>
            <span class="sr-val">{{ part.source_reference }}</span>
          </div>

          <!-- Educational Disclaimer per Requirement 28 -->
          <div class="edu-disclaimer-panel">
            <span class="edu-icon">⚠️</span>
            <span class="edu-text">
              <strong>Educational Reviewer &amp; Licensure Exam Preparation:</strong>
              This material is designed solely for Psychology undergraduate study. It does not provide medical advice or clinical diagnostic tools.
            </span>
          </div>

          <div class="gothic-sep gothic-sep-crimson" style="margin: 0.5rem 0 1.25rem;"></div>

          <div class="spl-body">
            <!-- 1. Doctrine Overview -->
            <div v-if="part?.overview" class="spl-section">
              <h4 class="spl-sec-title">DOCTRINE OVERVIEW</h4>
              <p class="spl-sec-text">{{ part.overview }}</p>
            </div>

            <!-- 2. Diagnostic Criteria & Features -->
            <div v-if="criteriaList.length" class="spl-section criteria-section">
              <h4 class="spl-sec-title">📋 DIAGNOSTIC CRITERIA &amp; FEATURES (DSM-5)</h4>
              <div class="criteria-list">
                <div v-for="(crit, cIdx) in criteriaList" :key="'crit-' + cIdx" class="criteria-item">
                  <span class="crit-bullet">❖</span>
                  <span class="crit-text">{{ crit }}</span>
                </div>
              </div>
            </div>

            <!-- 3. Specifiers & Subtypes -->
            <div v-if="specifiersList.length" class="spl-section specifiers-section">
              <h4 class="spl-sec-title">🏷️ SPECIFIERS &amp; CLINICAL SUBTYPES</h4>
              <div class="criteria-list">
                <div v-for="(spec, sIdx) in specifiersList" :key="'spec-' + sIdx" class="criteria-item">
                  <span class="crit-bullet">◈</span>
                  <span class="crit-text">{{ spec }}</span>
                </div>
              </div>
            </div>

            <!-- 4. Differential Diagnosis & Distinctions -->
            <div v-if="differentialList.length" class="spl-section diff-section">
              <h4 class="spl-sec-title">⚖️ DIFFERENTIAL DIAGNOSIS &amp; BOUNDARIES</h4>
              <div class="criteria-list">
                <div v-for="(diff, dIdx) in differentialList" :key="'diff-' + dIdx" class="criteria-item">
                  <span class="crit-bullet">⚔️</span>
                  <span class="crit-text">{{ diff }}</span>
                </div>
              </div>
            </div>

            <!-- 5. Foundational Runes / Key Terms -->
            <div v-if="keyConcepts.length" class="spl-section">
              <h4 class="spl-sec-title">🔑 KEY CONCEPTS &amp; TERMINOLOGY</h4>
              <div class="concepts-grid">
                <div v-for="(c, cIdx) in keyConcepts" :key="cIdx" class="concept-pill">
                  <div class="concept-head">
                    <span class="c-icon">{{ c.icon || '🔮' }}</span>
                    <strong class="c-term">{{ c.title }}</strong>
                  </div>
                  <p v-if="c.definition" class="c-def">{{ c.definition }}</p>
                </div>
              </div>
            </div>

            <!-- 6. Additional Structured Sections from Curriculum -->
            <div v-for="(sec, secIdx) in additionalSections" :key="'sec-' + secIdx" class="spl-section structured-curriculum-sec">
              <h4 class="spl-sec-title">{{ sec.title?.toUpperCase() || 'CURRICULUM REVIEW' }}</h4>
              <p class="spl-sec-text">{{ sec.content }}</p>
            </div>

            <!-- 7. Educational Case Scenario -->
            <div v-if="part?.clinical_example" class="spl-section clinical">
              <h4 class="spl-sec-title">🏥 EDUCATIONAL CASE SCENARIO</h4>
              <p class="spl-sec-text">{{ part.clinical_example }}</p>
            </div>

            <!-- 8. Quick Review Summary -->
            <div v-if="part?.summary_notes" class="spl-section">
              <h4 class="spl-sec-title">📝 QUICK REVIEW SUMMARY</h4>
              <p class="spl-sec-text">{{ part.summary_notes }}</p>
            </div>
          </div>

          <button class="btn btn-gothic-crimson btn-lg take-quiz-btn" @click="phase = 'quiz'">
            📝 ATTEMPT RETENTION TRIAL →
          </button>
        </div>
      </template>

      <!-- PHASE: QUIZ ──────────────────────────────── -->
      <template v-else-if="phase === 'quiz'">
        <div class="sp-quiz animate-scale-up">
          <!-- Progress -->
          <div class="quiz-progress-bar">
            <div class="quiz-progress-fill" :style="{ width: quizProgress + '%' }"></div>
          </div>
          <p class="quiz-counter">RUNIC QUERY {{ currentIdx + 1 }} / {{ questions.length }}</p>

          <div v-if="currentQ" class="quiz-card gothic-panel animate-scale-up">
            <p class="quiz-q">{{ currentQ.question }}</p>
            <div class="quiz-choices">
              <button
                v-for="(text, key) in currentQ.choices" :key="key"
                class="quiz-choice"
                :class="choiceClass(key)"
                :disabled="answered"
                @click="submitAnswer(key)"
              >
                <span class="qc-key">{{ key }}</span>
                <span class="qc-text">{{ text }}</span>
              </button>
            </div>

            <div v-if="answered && currentQ.explanation" class="quiz-explanation animate-scale-up">
              <span :class="lastCorrect ? 'correct-badge' : 'wrong-badge'">
                {{ lastCorrect ? '✔ REASONING:' : '✖ IN ERROR:' }}
              </span>
              <span class="exp-text">{{ currentQ.explanation }}</span>
            </div>

            <button v-if="answered" class="btn btn-gothic-crimson next-btn" @click="nextQuestion">
              {{ currentIdx < questions.length - 1 ? 'NEXT QUERY →' : 'FINALIZE TRIAL →' }}
            </button>
          </div>
        </div>
      </template>

      <!-- PHASE: RESULT ────────────────────────────── -->
      <template v-else-if="phase === 'result' && resultData">
        <div class="sp-result gothic-panel animate-scale-up">
          <div class="result-icon">{{ resultData.is_passed ? '🏆' : '📖' }}</div>
          <h2 class="result-title" :class="resultData.is_passed ? 'pass-color' : 'fail-color'">
            {{ resultData.is_passed ? 'DOCTRINE MASTERED!' : 'RETREAT & STUDY AGAIN' }}
          </h2>
          <div class="result-score">
            <span class="score-big">{{ resultData.correct }}</span>
            <span class="score-of">/ {{ resultData.total }} CORRECT</span>
          </div>
          <div class="result-rewards">
            <div class="rw">⭐ +{{ resultData.xp_earned }} XP</div>
            <div class="rw">🪙 +{{ resultData.coins_earned }} COINS</div>
          </div>
          <div class="result-actions">
            <router-link :to="'/study/' + domainId" class="btn btn-gothic-stone">← DOMAIN</router-link>
            <button v-if="!resultData.is_passed" class="btn btn-gothic-crimson" @click="retryQuiz">↺ RETRY TRIAL</button>
            <router-link v-else :to="'/study/' + domainId" class="btn btn-gothic-crimson">CONTINUE ODYSSEY →</router-link>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { resolveAsset } from '../services/bossMapping'
import api from '../services/api'

const route = useRoute()
const auth = useAuthStore()
const domainId = computed(() => Number(route.params.domainId))
const partNum = computed(() => Number(route.params.partNum))

const part = ref(null)
const questions = ref([])
const loading = ref(true)
const error = ref('')
const phase = ref('learn')

const currentIdx = ref(0)
const answered = ref(false)
const selectedKey = ref(null)
const lastCorrect = ref(false)
const answers = ref([])
const resultData = ref(null)
const startTime = ref(Date.now())

const currentQ = computed(() => questions.value[currentIdx.value] || null)
const quizProgress = computed(() =>
  questions.value.length > 0 ? ((currentIdx.value + 1) / questions.value.length) * 100 : 0
)
const bgImageUrl = computed(() => resolveAsset('assets/game/gothic_adventure_map.jpg'))

const keyConcepts = computed(() => {
  let kc = part.value?.key_concepts
  if (!kc) return []
  if (typeof kc === 'string') {
    try {
      kc = JSON.parse(kc)
    } catch {
      return []
    }
  }
  if (!Array.isArray(kc)) return []
  return kc.map(item => {
    if (typeof item === 'string') {
      try {
        const parsed = JSON.parse(item)
        if (typeof parsed === 'object' && parsed !== null) {
          return {
            title: parsed.title || parsed.term || parsed.name || '',
            definition: parsed.definition || parsed.desc || parsed.description || '',
            icon: parsed.icon || '🔮'
          }
        }
      } catch {
        return { title: item, definition: '', icon: '🔮' }
      }
      return { title: item, definition: '', icon: '🔮' }
    }
    if (typeof item === 'object' && item !== null) {
      return {
        title: item.title || item.term || item.name || '',
        definition: item.definition || item.desc || item.description || '',
        icon: item.icon || '🔮'
      }
    }
    return { title: String(item), definition: '', icon: '🔮' }
  })
})

function normalizeList(val) {
  if (!val) return []
  if (Array.isArray(val)) return val
  if (typeof val === 'string') {
    try {
      const parsed = JSON.parse(val)
      if (Array.isArray(parsed)) return parsed
    } catch {
      if (val.includes('\n')) {
        return val.split('\n').map(s => s.trim().replace(/^[•\-\*❖◈⚔️]\s*/, '')).filter(Boolean)
      }
    }
    return [val]
  }
  return [String(val)]
}

const criteriaList = computed(() => normalizeList(part.value?.diagnostic_criteria))
const specifiersList = computed(() => normalizeList(part.value?.specifiers))
const differentialList = computed(() => normalizeList(part.value?.differential_diagnosis))
const additionalSections = computed(() => {
  const secs = part.value?.sections || []
  return secs.filter(s => !['diagnostic_criteria', 'specifiers', 'differential_diagnosis'].includes(s.section_type))
})

onMounted(async () => {
  try {
    const res = await api.get(`/adventure/chapters/${domainId.value}/parts/${partNum.value}`)
    if (res.data.success) {
      part.value = res.data.part
      questions.value = res.data.questions || []
    } else {
      error.value = res.data.message || 'This part is locked'
    }
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load part'
  }
  loading.value = false
})

function choiceClass(key) {
  if (!answered.value) return ''
  const correct = currentQ.value?.correct_answer
  if (key === correct) return 'correct'
  if (key === selectedKey.value && key !== correct) return 'wrong'
  return 'dimmed'
}

function submitAnswer(key) {
  if (answered.value) return
  answered.value = true
  selectedKey.value = key
  lastCorrect.value = key === currentQ.value?.correct_answer
  answers.value.push({ question_id: currentQ.value?.id, chosen: key })
}

function nextQuestion() {
  if (currentIdx.value < questions.value.length - 1) {
    currentIdx.value++
    answered.value = false
    selectedKey.value = null
  } else {
    finishQuiz()
  }
}

async function finishQuiz() {
  const timeSpent = Math.floor((Date.now() - startTime.value) / 1000)
  try {
    const res = await api.post(`/adventure/parts/${part.value.id}/submit`, {
      answers: answers.value, time_spent: timeSpent
    })
    if (res.data.success) {
      resultData.value = res.data
      await auth.fetchUser()
    }
  } catch { }
  phase.value = 'result'
}

function retryQuiz() {
  currentIdx.value = 0
  answered.value = false
  selectedKey.value = null
  answers.value = []
  resultData.value = null
  startTime.value = Date.now()
  phase.value = 'quiz'
}
</script>

<style scoped>
.study-part {
  min-height: 100vh;
  position: relative;
  padding-top: 68px;
  background: var(--gothic-void, #050608);
  user-select: none;
}
.sp-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}
.sp-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  filter: brightness(0.42) contrast(1.15) saturate(0.85);
  transform: scale(1.02);
}
.sp-bg-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 20%, rgba(139, 26, 26, 0.18) 0%, transparent 60%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.8) 0%, #050608 95%);
}
.sp-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
}

.loading-center {
  text-align: center;
  padding: 4rem 1rem;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #232533;
  border-top-color: var(--color-crimson-bright, #b91c1c);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}
.pixel-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 0.08em;
}
@keyframes spin { to { transform: rotate(360deg); } }

.error-box {
  text-align: center;
  padding: 2.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  background: #0f1017;
  border: 2px solid var(--color-crimson-bright, #b91c1c);
}
.error-icon { font-size: 2.8rem; }
.error-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.85rem;
  color: var(--color-bone, #e2d9cc);
}

/* Learn */
.sp-learn {
  padding: 1.75rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.spl-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.spl-meta {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone-dark, #8c8273);
}
.spl-sep { color: #3a3b4c; }
.spl-domain { color: var(--color-gold-ancient, #e6c845); }
.spl-badge-dsm {
  background: #7c2d12;
  color: #fed7aa;
  padding: 0.15rem 0.45rem;
  border-radius: 2px;
  font-size: 0.55rem;
  font-family: var(--font-pixel, monospace);
  font-weight: 700;
  border: 1px solid #ea580c;
}
.spl-source-ref {
  font-size: 0.72rem;
  font-family: var(--font-pixel, monospace);
  color: var(--color-gold-ancient, #e6c845);
  margin-top: 0.25rem;
}
.spl-source-ref .sr-label {
  color: var(--color-bone-dark, #8c8273);
  margin-right: 0.35rem;
}
.edu-disclaimer-panel {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  background: rgba(30, 24, 15, 0.95);
  border: 1px solid #78350f;
  border-left: 3px solid #f59e0b;
  padding: 0.6rem 0.85rem;
  margin-top: 0.75rem;
  font-size: 0.78rem;
  color: #fef3c7;
  line-height: 1.4;
}
.edu-icon { font-size: 1rem; line-height: 1; flex-shrink: 0; }
.edu-text strong { color: #fde68a; display: block; margin-bottom: 0.15rem; font-family: var(--font-pixel, monospace); font-size: 0.62rem; }
.criteria-section {
  background: rgba(22, 24, 38, 0.92);
  border: 1px solid #2e3352;
  border-left: 3px solid #6366f1;
  padding: 0.85rem 1rem;
}
.specifiers-section {
  background: rgba(26, 23, 34, 0.92);
  border: 1px solid #3d2b4f;
  border-left: 3px solid #a855f7;
  padding: 0.85rem 1rem;
}
.diff-section {
  background: rgba(34, 20, 24, 0.92);
  border: 1px solid #4a2128;
  border-left: 3px solid #ef4444;
  padding: 0.85rem 1rem;
}
.criteria-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.4rem;
}
.criteria-item {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.5;
}
.crit-bullet {
  color: var(--color-gold-ancient, #e6c845);
  font-size: 0.85rem;
  line-height: 1.3;
  flex-shrink: 0;
}
.spl-title {
  font-family: var(--font-body, serif);
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
}
.spl-body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  max-height: 52vh;
  overflow-y: auto;
  padding-right: 0.4rem;
}
.spl-sec-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0 0 0.35rem 0;
}
.spl-sec-text {
  font-family: var(--font-body, serif);
  font-size: 0.9rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.6;
  margin: 0;
}
.concepts-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.6rem;
}
.concept-pill {
  background: rgba(20, 21, 32, 0.92);
  border: 1px solid #282a3a;
  border-left: 3px solid var(--color-crimson-bright, #b91c1c);
  padding: 0.85rem 1rem;
  transition: all 0.2s ease;
}
.concept-pill:hover {
  background: rgba(26, 28, 42, 0.96);
  border-color: #3f425c;
  border-left-color: var(--color-gold-ancient, #e6c845);
}
.concept-head {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  margin-bottom: 0.35rem;
}
.c-icon {
  font-size: 1.15rem;
  line-height: 1;
}
.c-term {
  display: block;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 0.04em;
}
.c-def {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
  line-height: 1.5;
}
.clinical {
  background: #13141e;
  border-left: 3px solid #38bdf8;
  padding: 0.75rem 0.9rem;
}
.take-quiz-btn { width: 100%; justify-content: center; }

/* Quiz */
.sp-quiz {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.quiz-progress-bar {
  height: 6px;
  background: #06070a;
  border: 1px solid #282a3a;
  overflow: hidden;
}
.quiz-progress-fill {
  height: 100%;
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 6px rgba(185, 28, 28, 0.8);
  transition: width 0.3s ease;
}
.quiz-counter {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
  text-align: right;
  margin: 0;
}
.quiz-card {
  padding: 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.quiz-q {
  font-family: var(--font-body, serif);
  font-size: 0.98rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.6;
  margin: 0 0 1.25rem 0;
}
.quiz-choices {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.quiz-choice {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 0.9rem;
  background: #14151f;
  border: 1px solid #2a2d3d;
  color: var(--color-bone, #e2d9cc);
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}
.quiz-choice:hover:not(:disabled) {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: #1e1215;
}
.quiz-choice.correct {
  border-color: #22c55e;
  background: rgba(34, 197, 94, 0.15);
  color: #86efac;
}
.quiz-choice.wrong {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(185, 28, 28, 0.2);
  color: #fca5a5;
}
.quiz-choice.dimmed { opacity: 0.3; }
.qc-key {
  width: 22px;
  height: 22px;
  background: #090a0e;
  border: 1px solid #333647;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  flex-shrink: 0;
}
.qc-text {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
}

.quiz-explanation {
  margin-top: 1.25rem;
  padding: 0.85rem;
  background: #141520;
  border: 1px solid #282a3a;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.correct-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #22c55e;
}
.wrong-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.exp-text {
  font-family: var(--font-body, serif);
  font-size: 0.82rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.5;
}
.next-btn {
  margin-top: 1.25rem;
  width: 100%;
  justify-content: center;
}

/* Result */
.sp-result {
  text-align: center;
  padding: 2.5rem 1.5rem;
  max-width: 480px;
  margin: 0 auto;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.result-icon { font-size: 3.5rem; margin-bottom: 0.75rem; }
.result-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.15rem;
  margin-bottom: 1rem;
}
.pass-color { color: var(--color-gold-ancient, #e6c845); }
.fail-color { color: var(--color-crimson-bright, #b91c1c); }
.result-score {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
}
.score-big {
  font-family: var(--font-pixel, monospace);
  font-size: 2.2rem;
  color: #22c55e;
}
.score-of {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: var(--color-bone-dark, #8c8273);
}
.result-rewards {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}
.rw {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone, #e2d9cc);
  background: #141520;
  border: 1px solid #282a3a;
  padding: 0.4rem 0.8rem;
}
.result-actions {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

@media (max-width: 580px) {
  .quiz-choices { grid-template-columns: 1fr; }
  .result-actions { flex-direction: column; width: 100%; }
  .result-actions .btn { width: 100%; text-align: center; }
}
</style>

