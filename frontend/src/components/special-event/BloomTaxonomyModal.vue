<template>
  <div class="bloom-modal-overlay" @click.self="$emit('close')">
    <div class="bloom-modal-card gothic-panel animate-scale-up" role="dialog" aria-modal="true">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="modal-header-left">
          <PixelEmoji emoji="crystal" size="md" />
          <div>
            <span class="modal-eyebrow">REVISED BLOOM'S TAXONOMY</span>
            <h2 class="modal-title">COGNITIVE PROCESS GUIDE</h2>
          </div>
        </div>
        <button class="close-btn" @click="$emit('close')" title="Close (ESC)">✕</button>
      </div>

      <!-- Navigation Tabs (All 6 Levels + Overview) -->
      <div class="bloom-tabs-bar">
        <button
          class="bloom-tab-btn"
          :class="{ active: selectedTab === 'overview' }"
          @click="selectedTab = 'overview'"
        >
          <span class="tab-icon">📖</span>
          <span>Overview</span>
        </button>

        <button
          v-for="lvl in levelsData"
          :key="lvl.key"
          class="bloom-tab-btn"
          :class="{ active: selectedTab === lvl.key, ['tab-' + lvl.key]: true }"
          @click="selectedTab = lvl.key"
        >
          <span class="tab-icon">{{ lvl.icon }}</span>
          <span>{{ lvl.shortName }}</span>
          <span v-if="getUserAccuracy(lvl.key) !== null" class="tab-acc-badge" :class="getAccClass(getUserAccuracy(lvl.key))">
            {{ getUserAccuracy(lvl.key) }}%
          </span>
        </button>
      </div>

      <!-- Modal Content Area -->
      <div class="modal-scroll-body">
        <!-- TAB 0: OVERVIEW & ARCHITECTURAL FOUNDATION -->
        <div v-if="selectedTab === 'overview'" class="tab-content overview-content animate-fade-in">
          <div class="overview-hero gothic-subpanel">
            <h3 class="overview-title">The Revised Bloom's Taxonomy in StudyQuest</h3>
            <p class="overview-intro">
              The Grand Examination does not merely score right and wrong answers. It tracks <strong>what mental operations</strong> you use to solve psychiatric dilemmas—ranging from recalling diagnostic durations to formulating complex clinical interventions.
            </p>
            <div class="tax-ladder-visual">
              <div
                v-for="lvl in levelsData"
                :key="lvl.key"
                class="ladder-step"
                :class="'step-' + lvl.key"
                @click="selectedTab = lvl.key"
              >
                <div class="step-num">Level {{ lvl.level }}</div>
                <div class="step-icon">{{ lvl.icon }}</div>
                <div class="step-name">{{ lvl.name }}</div>
                <div class="step-desc">{{ lvl.tagline }}</div>
              </div>
            </div>
          </div>

          <!-- Crucial Distinction: Bloom vs Difficulty (Section 60) -->
          <div class="crucial-distinction-card gothic-subpanel">
            <div class="distinction-header">
              <span class="callout-icon">⚖️</span>
              <h4 class="distinction-title">CRITICAL PRINCIPLE: BLOOM LEVEL ≠ DIFFICULTY</h4>
            </div>
            <p class="distinction-text">
              <strong>Question Difficulty</strong> and <strong>Bloom's Taxonomy Level</strong> are completely independent dimensions:
            </p>
            <div class="comparison-grid">
              <div class="compare-card">
                <span class="pill-demo remember">REMEMBER</span>
                <span class="diff-demo very-difficult">Very Difficult</span>
                <p class="compare-text">
                  Recalling an obscure neuroreceptor binding affinity or a rare specifier duration requirement is <em>Very Difficult</em>, yet the cognitive process remains <strong>pure retrieval</strong>.
                </p>
              </div>
              <div class="compare-card">
                <span class="pill-demo analyze">ANALYZE</span>
                <span class="diff-demo moderate">Moderate</span>
                <p class="compare-text">
                  Distinguishing obvious manic symptoms from major depressive symptoms in a clear vignette requires <strong>breaking down relationships</strong>, even though the difficulty is <em>Moderate</em>.
                </p>
              </div>
            </div>
          </div>

          <!-- Academic Assessment Note (Section 51 & 54) -->
          <div class="educational-note-box">
            <span class="note-icon">ℹ️</span>
            <div>
              <strong>Personal Learning Analytics:</strong>
              Bloom analytics describe <em>demonstrated performance on this assessment</em>. They do not evaluate permanent intelligence, capability, or cognitive limits. Use them to target specific Study Hall reviews.
            </div>
          </div>
        </div>

        <!-- TABS 1 TO 6: SPECIFIC BLOOM LEVELS -->
        <div v-else-if="currentLevelData" class="tab-content level-detail-content animate-fade-in">
          <!-- Level Header Banner -->
          <div class="level-header-banner gothic-subpanel" :class="'banner-' + currentLevelData.key">
            <div class="level-meta-left">
              <div class="level-badge-row">
                <span class="badge-level-order">LEVEL {{ currentLevelData.level }} OF 6</span>
                <span class="badge-order-type">{{ currentLevelData.category }}</span>
              </div>
              <h3 class="level-title-large">
                <span class="lvl-icon">{{ currentLevelData.icon }}</span>
                <span>{{ currentLevelData.name.toUpperCase() }}</span>
              </h3>
              <p class="level-tagline">{{ currentLevelData.tagline }}</p>
            </div>

            <!-- User's Score in this specific level if available -->
            <div v-if="currentUserLevelStats" class="level-user-score-box">
              <span class="user-score-label">TODAY'S ACCURACY</span>
              <div class="user-score-val" :class="getAccClass(currentUserLevelStats.accuracy)">
                {{ currentUserLevelStats.accuracy }}%
              </div>
              <span class="user-score-sub">
                {{ currentUserLevelStats.correct_count }} / {{ currentUserLevelStats.question_count }} items correct
              </span>
            </div>
          </div>

          <!-- Core Definition Card -->
          <div class="detail-section-card gothic-subpanel">
            <h4 class="section-subheading">
              <span class="heading-icon">🎯</span>
              <span>Cognitive Process Required</span>
            </h4>
            <p class="definition-paragraph">{{ currentLevelData.definition }}</p>

            <div class="action-verbs-container">
              <span class="verbs-label">KEY COGNITIVE ACTIONS:</span>
              <div class="verbs-chips">
                <span v-for="verb in currentLevelData.verbs" :key="verb" class="verb-chip">
                  {{ verb }}
                </span>
              </div>
            </div>
          </div>

          <!-- Clinical Board Example Vignette -->
          <div class="detail-section-card gothic-subpanel example-card">
            <h4 class="section-subheading">
              <span class="heading-icon">📜</span>
              <span>Authentic Psychiatric Board Vignette Example</span>
            </h4>
            <div class="example-box">
              <div class="example-stem">{{ currentLevelData.example.question }}</div>
              <div class="example-choices">
                <div
                  v-for="(choiceText, key) in currentLevelData.example.choices"
                  :key="key"
                  class="example-choice-item"
                  :class="{ correct: key === currentLevelData.example.correct }"
                >
                  <span class="choice-letter">{{ key }}.</span>
                  <span class="choice-desc">{{ choiceText }}</span>
                  <span v-if="key === currentLevelData.example.correct" class="correct-tag">✓ CORRECT</span>
                </div>
              </div>
              <div class="example-rationale">
                <strong>Cognitive Rationale:</strong> {{ currentLevelData.example.rationale }}
              </div>
            </div>
          </div>

          <!-- How to Study in Study Hall -->
          <div class="detail-section-card gothic-subpanel study-strategy-card">
            <h4 class="section-subheading">
              <span class="heading-icon">🛡️</span>
              <span>Study Hall Preparation Strategy</span>
            </h4>
            <p class="study-strategy-text">{{ currentLevelData.studyStrategy }}</p>
          </div>
        </div>
      </div>

      <!-- Modal Footer with Stepper -->
      <div class="modal-footer">
        <button
          class="btn-step prev"
          :disabled="selectedTab === 'overview'"
          @click="navigateLevel(-1)"
        >
          ◀ Previous Level
        </button>

        <span class="footer-indicator">
          {{ selectedTab === 'overview' ? 'Overview' : `Level ${currentLevelData?.level || 1} of 6` }}
        </span>

        <button
          v-if="selectedTab !== 'create'"
          class="btn-step next"
          @click="navigateLevel(1)"
        >
          Next Level ▶
        </button>
        <button
          v-else
          class="btn-step next"
          @click="$emit('close')"
        >
          Close Guide ✕
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  initialLevel: {
    type: String,
    default: 'overview'
  },
  bloomSummary: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close'])

const selectedTab = ref(props.initialLevel?.toLowerCase() || 'overview')

watch(() => props.initialLevel, (newVal) => {
  if (newVal) {
    selectedTab.value = newVal.toLowerCase()
  }
})

// Comprehensive Revised Bloom's Taxonomy Reference Data
const levelsData = [
  {
    key: 'remember',
    level: 1,
    name: 'Remember',
    shortName: 'Remember',
    icon: '📜',
    category: 'Foundational Knowledge Retrieval',
    tagline: 'Direct factual recall and verbatim criteria retention',
    definition: 'Questions requiring retrieval, recognition, and recall of previously learned facts, psychiatric terminology, DSM-5 duration thresholds, inclusion counts, diagnostic definitions, and directly stated clinical criteria from Study Hall.',
    verbs: ['Recall', 'Identify', 'List', 'Recognize', 'Name', 'State Duration', 'Enumerate'],
    example: {
      question: 'Under DSM-5 diagnostic criteria for Schizophreniform Disorder, what is the required duration of illness, including prodromal, active, and residual phases?',
      choices: {
        A: 'At least 1 day but less than 1 month',
        B: 'At least 1 month but less than 6 months',
        C: 'At least 6 months continuous duration',
        D: 'At least 2 years without remission'
      },
      correct: 'B',
      rationale: 'Requires pure retrieval of the explicit DSM-5 timeline threshold (1 to 6 months) separating Brief Psychotic Disorder from Schizophrenia.'
    },
    studyStrategy: 'Review the Diagnostic Criteria and Specifiers & Course sections in Study Hall. Focus on exact minimum timeframes (e.g., 2 weeks for MDD, 1 week for Mania, 6 months for GAD and Schizophrenia).'
  },
  {
    key: 'understand',
    level: 2,
    name: 'Understand',
    shortName: 'Understand',
    icon: '💡',
    category: 'Comprehension & Meaning Recognition',
    tagline: 'Translating concepts and explaining diagnostic features',
    definition: 'Questions requiring interpretation, explanation, classification, translation, comparison of concepts, summarization, or recognition of clinical meaning without necessarily having to apply it in a multi-step clinical problem.',
    verbs: ['Explain', 'Interpret', 'Summarize', 'Classify', 'Paraphrase', 'Illustrate', 'Contrast'],
    example: {
      question: 'Which of the following clinical statements best characterizes the psychological construct of "ideas of reference" as conceptualized in DSM-5 Psychopathology?',
      choices: {
        A: 'Fixed, unshakeable false beliefs of persecutory conspiracy unsupported by reality',
        B: 'Believing that innocuous casual events or external remarks hold special, direct personal significance',
        C: 'Sensory perceptions occurring in the clear absence of corresponding external physical stimuli',
        D: 'Pervasive detachment from social relationships and restricted emotional expression'
      },
      correct: 'B',
      rationale: 'Tests comprehension of psychopathological terminology, distinguishing ideas of reference from formal delusions or hallucinations.'
    },
    studyStrategy: 'Study the Overview and Important Terms glossaries in Study Hall. Paraphrase psychiatric definitions in your own words to verify true conceptual comprehension rather than mechanical memorization.'
  },
  {
    key: 'apply',
    level: 3,
    name: 'Apply',
    shortName: 'Apply',
    icon: '⚙️',
    category: 'Practical & Clinical Execution',
    tagline: 'Using knowledge in clinical patient vignettes',
    definition: 'Questions requiring the learner to use Study Hall psychiatric knowledge in a clinical or practical situation. Translates DSM-5 diagnostic criteria directly into patient presentation assessment to confirm a diagnosis, compute a symptom count, or determine illness course.',
    verbs: ['Execute', 'Implement', 'Apply Criteria', 'Calculate Count', 'Diagnose Vignette', 'Demonstrate'],
    example: {
      question: 'A 32-year-old software architect presents with 3 weeks of persistent depressed mood, severe insomnia, 6 kg unintentional weight loss, psychomotor agitation, feelings of worthlessness, and difficulty concentrating. Medical workup and substance screenings are negative. Which diagnosis applies?',
      choices: {
        A: 'Persistent Depressive Disorder (Dysthymia)',
        B: 'Major Depressive Disorder, Single Episode',
        C: 'Cyclothymic Disorder',
        D: 'Adjustment Disorder with Depressed Mood'
      },
      correct: 'B',
      rationale: 'Requires applying the 5-of-9 DSM-5 depression criteria across the 2-week threshold to diagnose an active Major Depressive Episode.'
    },
    studyStrategy: 'Examine the Clinical Examples and Case Scenarios in Study Hall. Practice tallying symptom criteria directly against patient histories and verifying duration criteria.'
  },
  {
    key: 'analyze',
    level: 4,
    name: 'Analyze',
    shortName: 'Analyze',
    icon: '🔍',
    category: 'Diagnostic Differentiation & Relationship Breakdown',
    tagline: 'Dissecting complex cases & differential diagnosis',
    definition: 'Questions requiring the learner to break down complex clinical case data, distinguish relationships between overlapping disorders, compare competing diagnostic possibilities, identify subtle psychopathological patterns, and isolate the most relevant differential clinical feature.',
    verbs: ['Differentiate', 'Distinguish', 'Deconstruct', 'Isolate', 'Compare Competing Diagnoses', 'Discriminate'],
    example: {
      question: 'A 28-year-old patient exhibits recurrent panic attacks with sudden surges of intense fear. However, the attacks occur strictly upon exposure to social gatherings and speaking in public, with intense fear of negative scrutiny. Which feature best differentiates this from Panic Disorder?',
      choices: {
        A: 'The absence of somatic symptoms such as palpitations or diaphoresis',
        B: 'The attacks are exclusively cued by feared social situations rather than being unexpected and uncued',
        C: 'The symptoms have persisted for less than six continuous months',
        D: 'The patient exhibits agoraphobic avoidance of open public transport'
      },
      correct: 'B',
      rationale: 'Requires analyzing the functional trigger of panic attacks: Panic Disorder requires recurring unexpected attacks, whereas Social Anxiety Disorder features panic attacks cued specifically by social evaluation.'
    },
    studyStrategy: 'Master the Differential Diagnosis sections in Study Hall. Look closely at the comparison tables and "Rule-Out" criteria distinguishing mimics (e.g., GAD vs OCD, Bipolar vs Borderline Personality).'
  },
  {
    key: 'evaluate',
    level: 5,
    name: 'Evaluate',
    shortName: 'Evaluate',
    icon: '⚖️',
    category: 'Judgment & Evidence Synthesis',
    tagline: 'Judging competing alternatives & best-supported decisions',
    definition: 'Questions requiring the learner to judge competing clinical alternatives, critique conflicting diagnostic hypotheses, determine the most defensible clinical interpretation, or select the best-supported conclusion when multiple valid pieces of information are present.',
    verbs: ['Judge', 'Critique', 'Prioritize', 'Defend', 'Determine Best-Supported', 'Weigh Evidence'],
    example: {
      question: 'A patient with chronic auditory hallucinations and flat affect for 2 years experiences a 3-week severe depressive episode. The psychotic symptoms persist with equal intensity after full resolution of the mood symptoms. When weighing competing diagnoses, which formulation is best supported?',
      choices: {
        A: 'Major Depressive Disorder with Psychotic Features',
        B: 'Schizoaffective Disorder, Depressive Type',
        C: 'Schizophrenia with Comorbid Major Depressive Episode',
        D: 'Bipolar I Disorder, Most Recent Episode Depressed'
      },
      correct: 'C',
      rationale: 'Evaluates the hierarchy of Schizoaffective vs Schizophrenia: Schizoaffective requires mood episodes to be present for the majority of total illness duration. Because mood symptoms were only 3 weeks in a 2-year illness, Schizophrenia with comorbid depression is the authoritative DSM-5 diagnosis.'
    },
    studyStrategy: 'Focus on Diagnostic Hierarchy, Specifiers, and Exclusion Criteria in Study Hall. Practice weighing conflicting clinical features when a patient presents with multiple overlapping symptom sets.'
  },
  {
    key: 'create',
    level: 6,
    name: 'Create',
    shortName: 'Create',
    icon: '🏗️',
    category: 'Clinical Formulation & Strategy Synthesis',
    tagline: 'Constructing comprehensive management & diagnostic formulations',
    definition: 'Questions requiring the learner to determine, synthesize, or construct the most appropriate comprehensive solution, diagnostic formulation, intervention strategy, management sequencing, or clinical trajectory from a complex, multi-layered psychiatric scenario.',
    verbs: ['Formulate', 'Construct', 'Design Strategy', 'Synthesize Plan', 'Integrate', 'Develop Trajectory'],
    example: {
      question: 'In formulating a comprehensive diagnostic and management roadmap for a patient presenting with rapid-cycling Bipolar II Disorder complicated by severe medication-induced hypothyroidism and active suicidal ideation, which integrated intervention strategy is most appropriate?',
      choices: {
        A: 'Initiate high-dose antidepressant monotherapy while discontinuing mood stabilizers',
        B: 'Stabilize endocrine function with levothyroxine, optimize mood stabilization (e.g. lithium or lamotrigine), and establish immediate crisis safety protocols',
        C: 'Rely solely on outpatient psychotherapy until thyroid antibodies normalize',
        D: 'Prescribe stimulants to target sluggish psychomotor speed and fatigue'
      },
      correct: 'B',
      rationale: 'Requires synthesizing biological (thyroid optimization), psychiatric (mood stabilizer selection), and safety (crisis mitigation) components into an integrated formulation plan.'
    },
    studyStrategy: 'Study comprehensive Case Formulations and Multi-Modal Treatment references in Study Hall. Focus on sequencing: safety first, ruling out organic etiology, and constructing structured stepped-care formulations.'
  }
]

const currentLevelData = computed(() => {
  return levelsData.find(l => l.key === selectedTab.value) || null
})

const currentUserLevelStats = computed(() => {
  if (!props.bloomSummary || !props.bloomSummary.length) return null
  return props.bloomSummary.find(s => s.bloom_level?.toLowerCase() === selectedTab.value) || null
})

function getUserAccuracy(levelKey) {
  if (!props.bloomSummary || !props.bloomSummary.length) return null
  const found = props.bloomSummary.find(s => s.bloom_level?.toLowerCase() === levelKey)
  return found ? Math.round(found.accuracy) : null
}

function getAccClass(acc) {
  if (acc === null || acc === undefined) return ''
  if (acc >= 80) return 'acc-high'
  if (acc >= 60) return 'acc-mid'
  return 'acc-low'
}

function navigateLevel(direction) {
  const tabsOrder = ['overview', 'remember', 'understand', 'apply', 'analyze', 'evaluate', 'create']
  const curIdx = tabsOrder.indexOf(selectedTab.value)
  const nextIdx = curIdx + direction
  if (nextIdx >= 0 && nextIdx < tabsOrder.length) {
    selectedTab.value = tabsOrder[nextIdx]
  }
}

function handleKeydown(e) {
  if (e.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.bloom-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(4, 2, 10, 0.85);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.bloom-modal-card {
  width: 100%;
  max-width: 860px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #130f24 0%, #0c0818 100%);
  border: 1.5px solid #8b5cf6;
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.95), 0 0 30px rgba(139, 92, 246, 0.25);
  overflow: hidden;
}

/* Modal Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: rgba(22, 16, 42, 0.95);
  border-bottom: 1.5px solid rgba(139, 92, 246, 0.35);
}

.modal-header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-eyebrow {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #a78bfa;
  letter-spacing: 1px;
}

.modal-title {
  font-family: 'Cinzel', serif;
  font-size: 1.3rem;
  font-weight: 800;
  color: #f8fafc;
  margin: 0.2rem 0 0;
  letter-spacing: 0.5px;
}

.close-btn {
  background: rgba(30, 20, 56, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #cbd5e1;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #ef4444;
  color: #fff;
  border-color: #ef4444;
}

/* Tabs Bar */
.bloom-tabs-bar {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.65rem 1.25rem;
  background: rgba(14, 10, 26, 0.95);
  border-bottom: 1px solid rgba(139, 92, 246, 0.2);
  overflow-x: auto;
  white-space: nowrap;
}

.bloom-tab-btn {
  background: rgba(26, 18, 50, 0.7);
  border: 1px solid rgba(139, 92, 246, 0.25);
  color: #c4b5fd;
  padding: 0.45rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  transition: all 0.2s ease;
}

.bloom-tab-btn:hover {
  background: rgba(46, 28, 92, 0.85);
  color: #fff;
  border-color: #a78bfa;
}

.bloom-tab-btn.active {
  background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
  color: #fff;
  border-color: #c084fc;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.4);
}

.tab-acc-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  background: rgba(0, 0, 0, 0.3);
}

.tab-acc-badge.acc-high { color: #34d399; }
.tab-acc-badge.acc-mid { color: #fbbf24; }
.tab-acc-badge.acc-low { color: #f87171; }

/* Scroll Body */
.modal-scroll-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.tab-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Overview Content */
.overview-hero {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  background: rgba(18, 14, 34, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 8px;
  padding: 1.25rem;
}

.overview-title {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0;
}

.overview-intro {
  font-size: 0.86rem;
  color: #cbd5e1;
  line-height: 1.55;
  margin: 0;
}

.tax-ladder-visual {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.ladder-step {
  background: rgba(10, 8, 22, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 6px;
  padding: 0.75rem 0.5rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.ladder-step:hover {
  transform: translateY(-2px);
  border-color: #a78bfa;
  background: rgba(30, 20, 60, 0.95);
  box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
}

.step-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.45rem;
  color: #a78bfa;
}

.step-icon {
  font-size: 1.4rem;
  margin: 0.15rem 0;
}

.step-name {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #f8fafc;
}

.step-desc {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1.2;
}

/* Crucial Distinction Card */
.crucial-distinction-card {
  background: rgba(20, 14, 38, 0.9);
  border: 1.5px solid rgba(245, 158, 11, 0.4);
  border-radius: 8px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.distinction-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.callout-icon {
  font-size: 1.2rem;
}

.distinction-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  margin: 0;
  letter-spacing: 0.5px;
}

.distinction-text {
  font-size: 0.84rem;
  color: #e2e8f0;
  margin: 0;
  line-height: 1.5;
}

.comparison-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 0.85rem;
}

.compare-card {
  background: rgba(10, 8, 20, 0.75);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 6px;
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.pill-demo {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  align-self: flex-start;
}

.pill-demo.remember {
  background: rgba(56, 189, 248, 0.2);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.4);
}

.pill-demo.analyze {
  background: rgba(244, 114, 182, 0.2);
  color: #f472b6;
  border: 1px solid rgba(244, 114, 182, 0.4);
}

.diff-demo {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
}

.diff-demo.very-difficult { color: #f87171; }
.diff-demo.moderate { color: #34d399; }

.compare-text {
  font-size: 0.8rem;
  color: #cbd5e1;
  margin: 0;
  line-height: 1.45;
}

/* Educational Note */
.educational-note-box {
  background: rgba(14, 10, 26, 0.85);
  border-left: 3px solid #8b5cf6;
  padding: 0.85rem 1rem;
  border-radius: 0 6px 6px 0;
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  font-size: 0.8rem;
  color: #cbd5e1;
  line-height: 1.5;
}

.note-icon {
  font-size: 1rem;
  flex-shrink: 0;
}

/* Level Detail Tab Content */
.level-header-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  background: linear-gradient(135deg, rgba(30, 20, 56, 0.95) 0%, rgba(14, 10, 28, 0.95) 100%);
  border: 1.5px solid rgba(139, 92, 246, 0.4);
  border-radius: 8px;
  padding: 1.25rem;
}

.level-meta-left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.level-badge-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.badge-level-order {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #a78bfa;
}

.badge-order-type {
  font-size: 0.72rem;
  color: #94a3b8;
  font-style: italic;
}

.level-title-large {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem;
  font-weight: 800;
  color: #f8fafc;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  letter-spacing: 0.5px;
}

.level-tagline {
  font-size: 0.85rem;
  color: #cbd5e1;
  margin: 0;
}

.level-user-score-box {
  background: rgba(10, 8, 20, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.3);
  padding: 0.75rem 1rem;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.user-score-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  color: #94a3b8;
}

.user-score-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0.2rem 0;
}

.user-score-val.acc-high { color: #34d399; }
.user-score-val.acc-mid { color: #fbbf24; }
.user-score-val.acc-low { color: #f87171; }

.user-score-sub {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Detail Section Card */
.detail-section-card {
  background: rgba(18, 14, 34, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 8px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.section-subheading {
  font-family: 'Cinzel', serif;
  font-size: 1.05rem;
  font-weight: 700;
  color: #f1f5f9;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  letter-spacing: 0.5px;
}

.heading-icon {
  font-size: 1rem;
}

.definition-paragraph {
  font-size: 0.86rem;
  color: #cbd5e1;
  line-height: 1.6;
  margin: 0;
}

.action-verbs-container {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.verbs-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #a78bfa;
}

.verbs-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.verb-chip {
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(168, 85, 247, 0.3);
  color: #c4b5fd;
  font-size: 0.75rem;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
}

/* Example Box */
.example-box {
  background: rgba(12, 9, 24, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 6px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.example-stem {
  font-size: 0.88rem;
  color: #f8fafc;
  line-height: 1.5;
  font-weight: 500;
}

.example-choices {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.example-choice-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(22, 16, 44, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 4px;
  padding: 0.45rem 0.75rem;
  font-size: 0.8rem;
  color: #cbd5e1;
}

.example-choice-item.correct {
  background: rgba(16, 185, 129, 0.2);
  border-color: #10b981;
  color: #f0fdf4;
}

.choice-letter {
  font-weight: 700;
  color: #a78bfa;
}

.correct-tag {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.48rem;
  background: #10b981;
  color: #fff;
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
}

.example-rationale {
  font-size: 0.8rem;
  color: #cbd5e1;
  line-height: 1.5;
  padding-top: 0.4rem;
  border-top: 1px dashed rgba(148, 163, 184, 0.2);
}

.study-strategy-text {
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.6;
  margin: 0;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: rgba(14, 10, 26, 0.95);
  border-top: 1px solid rgba(139, 92, 246, 0.3);
}

.btn-step {
  background: rgba(30, 22, 58, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #e2e8f0;
  padding: 0.5rem 0.95rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-step:hover:not(:disabled) {
  background: #8b5cf6;
  color: #fff;
  border-color: #c084fc;
}

.btn-step:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.footer-indicator {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #a78bfa;
}

@media (max-width: 640px) {
  .tax-ladder-visual {
    grid-template-columns: repeat(3, 1fr);
  }
  .modal-title {
    font-size: 1.05rem;
  }
  .level-title-large {
    font-size: 1.2rem;
  }
}
</style>
