<template>
  <div class="black-archive-backdrop" @click.self="$emit('close')">
    <div class="black-archive-modal gothic-panel animate-scale-up">
      <!-- Loading State -->
      <div v-if="loading" class="archive-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNSEALING THE BLACK ARCHIVE…</p>
      </div>

      <!-- Victory Over Creator -->
      <div v-else-if="creatorDefeated" class="archive-victory-state">
        <div class="victory-crest">🕊️</div>
        <span class="victory-tag">APEX CLIMAX ACHIEVED</span>
        <h1 class="victory-title">THE MINDREALM IS RESTORED</h1>
        <p class="victory-lore">
          The rigid taxonomies of the Creator dissolve into organic harmony. Through accumulated psychiatric discernment, you have healed the Fracture and freed the twelve guardians.
        </p>
        <div class="mind-seals-unison-strip">
          <span class="unison-label">12 MIND SEALS IN HARMONY</span>
          <div class="seals-glyph-row">
            <span v-for="i in 12" :key="i" class="seal-glyph-item">🔮</span>
          </div>
        </div>
        <button class="btn btn-gothic-crimson btn-lg" @click="$emit('close')">
          RETURN IN TRIUMPH
        </button>
      </div>

      <!-- Active Phase Encounter -->
      <div v-else class="archive-battle-wrapper">
        <!-- Header -->
        <div class="archive-header">
          <div class="archive-tag">
            <span>🗝️ THE BLACK ARCHIVE — APEX ENCOUNTER</span>
          </div>
          <button class="archive-close-btn" @click="$emit('close')">✕</button>
        </div>

        <div class="phases-progress-dots">
          <div
            v-for="p in 4"
            :key="p"
            class="phase-dot"
            :class="{
              'phase-active': p === currentPhase,
              'phase-cleared': p < currentPhase
            }"
          >
            <span class="dot-num">PHASE {{ p }}</span>
          </div>
        </div>

        <!-- Phase Info & Creator Manifestation -->
        <div class="creator-arena-card">
          <div class="creator-portrait-box">
            <img
              :src="resolveAsset(currentPhaseData?.asset || 'assets/bosses/hat/2happyhat.png')"
              :alt="currentPhaseData?.name"
              class="creator-img pixel-art animate-boss-float"
            />
            <div class="creator-ring"></div>
          </div>

          <div class="creator-meta">
            <h2 class="creator-phase-name">{{ currentPhaseData?.name }}</h2>
            <h4 class="creator-phase-title">{{ currentPhaseData?.title }}</h4>
            <p class="creator-desc">{{ currentPhaseData?.description }}</p>

            <!-- HP Bar -->
            <div class="creator-hp-bar-wrap">
              <div class="hp-label-row">
                <span>CREATOR ESSENCE</span>
                <span>{{ creatorHP }} / {{ currentPhaseData?.boss_hp || 500 }} HP</span>
              </div>
              <div class="hp-bar-track">
                <div class="hp-bar-fill" :style="{ width: hpPercent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dialogue Box -->
        <div class="creator-dialogue-wrap">
          <DialogueBox
            speaker="The Creator"
            speaker-title="Architect of the Fracture"
            :text="currentPhaseData?.dialogue_intro || 'Why resist the perfect taxonomy?!'"
            speaker-icon="🗝️"
          />
        </div>

        <!-- Question Challenge (Layer 3 of Black Archive) -->
        <div v-if="currentQuestion" class="archive-question-box">
          <div class="q-domain-strip">
            <span class="q-domain-label">DOMAINS SYNTHESIS:</span>
            <span class="q-domain-val">{{ currentQuestion.domain || 'Psychiatric Comprehensive' }}</span>
          </div>
          <h3 class="q-text">{{ currentQuestion.question }}</h3>

          <div class="choices-grid">
            <button
              v-for="(val, key) in currentQuestion.choices"
              :key="key"
              class="choice-btn"
              :class="{
                'choice-selected': selectedAnswer === key,
                'choice-correct': answered && key === currentQuestion.correct_answer,
                'choice-wrong': answered && selectedAnswer === key && key !== currentQuestion.correct_answer,
              }"
              :disabled="answered"
              @click="chooseAnswer(key)"
            >
              <span class="choice-key">{{ key }}</span>
              <span class="choice-text">{{ val }}</span>
            </button>
          </div>

          <div v-if="answered" class="q-feedback animate-scale-up">
            <p v-if="isCorrect" class="feedback-text text-emerald-400">
              ⚡ Correct insight! You deal {{ damageDealt }} damage to the Creator!
            </p>
            <p v-else class="feedback-text text-rose-400">
              ❌ The Creator counter-attacks! The correct response was {{ currentQuestion.correct_answer }}.
            </p>
            <p v-if="currentQuestion.explanation" class="feedback-expl">
              {{ currentQuestion.explanation }}
            </p>
            <button class="btn btn-gothic-crimson btn-sm next-q-btn" @click="advanceQuestion">
              CONTINUE →
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DialogueBox from './DialogueBox.vue'
import { resolveAsset } from '../../services/bossMapping'
import api from '../../services/api'

const emit = defineEmits(['close', 'victory'])

const loading = ref(true)
const creatorDefeated = ref(false)
const currentPhase = ref(1)
const currentPhaseData = ref(null)
const questions = ref([])
const qIdx = ref(0)
const creatorHP = ref(500)
const selectedAnswer = ref(null)
const answered = ref(false)
const isCorrect = ref(false)
const damageDealt = ref(50)

const currentQuestion = computed(() => questions.value[qIdx.value] || null)
const hpPercent = computed(() => {
  const max = currentPhaseData.value?.boss_hp || 500
  return Math.max(0, (creatorHP.value / max) * 100)
})

async function loadArchiveData() {
  loading.value = true
  try {
    const res = await api.get('/adventure/black-archive')
    if (res.data.success) {
      creatorDefeated.value = Boolean(res.data.creator_defeated)
      currentPhase.value = res.data.current_phase || 1
      currentPhaseData.value = res.data.phase_data
      questions.value = res.data.questions || []
      creatorHP.value = res.data.phase_data?.boss_hp || 500
      qIdx.value = 0
    }
  } catch (e) {
    console.error('Failed to load Black Archive', e)
  } finally {
    loading.value = false
  }
}

function chooseAnswer(key) {
  if (answered.value) return
  selectedAnswer.value = key
  answered.value = true
  isCorrect.value = (key === currentQuestion.value?.correct_answer)

  if (isCorrect.value) {
    const dmg = Math.ceil((currentPhaseData.value?.boss_hp || 500) / 4)
    damageDealt.value = dmg
    creatorHP.value = Math.max(0, creatorHP.value - dmg)
  }
}

async function advanceQuestion() {
  if (creatorHP.value <= 0) {
    // Phase defeated! Submit phase complete
    await submitPhaseComplete()
    return
  }

  // Next question
  if (qIdx.value < questions.value.length - 1) {
    qIdx.value++
    answered.value = false
    selectedAnswer.value = null
  } else {
    // Loop questions with shuffle
    qIdx.value = 0
    answered.value = false
    selectedAnswer.value = null
  }
}

async function submitPhaseComplete() {
  loading.value = true
  try {
    const res = await api.post('/adventure/black-archive/complete-phase', {
      phase: currentPhase.value,
      player_hp: 100,
      boss_hp: 0
    })
    if (res.data.success) {
      if (res.data.is_final_victory) {
        creatorDefeated.value = true
        emit('victory')
      } else {
        await loadArchiveData()
      }
    }
  } catch (e) {
    console.error('Error completing phase', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadArchiveData()
})
</script>

<style scoped>
.black-archive-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(8px);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.black-archive-modal {
  max-width: 760px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  background: rgba(12, 8, 16, 0.98);
  border: 1px solid rgba(225, 29, 72, 0.5);
  box-shadow: 0 15px 60px rgba(0, 0, 0, 0.95), inset 0 0 35px rgba(225, 29, 72, 0.12);
  border-radius: 6px;
  padding: 1.5rem;
}

.archive-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.archive-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fb7185;
  letter-spacing: 0.08em;
}

.archive-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
}

.phases-progress-dots {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.phase-dot {
  flex: 1;
  text-align: center;
  padding: 0.35rem 0.25rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #64748b;
}

.phase-active {
  background: rgba(225, 29, 72, 0.25);
  border-color: #fb7185;
  color: #fb7185;
}

.phase-cleared {
  background: rgba(16, 185, 129, 0.2);
  border-color: #34d399;
  color: #34d399;
}

.creator-arena-card {
  display: flex;
  gap: 1.25rem;
  align-items: center;
  background: rgba(20, 10, 18, 0.85);
  border: 1px solid rgba(225, 29, 72, 0.3);
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.creator-portrait-box {
  position: relative;
  width: 90px;
  height: 90px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.creator-img {
  width: 75px;
  height: 75px;
  object-fit: contain;
  z-index: 2;
  filter: drop-shadow(0 0 12px rgba(225, 29, 72, 0.6));
}

.creator-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px dashed rgba(225, 29, 72, 0.5);
  animation: spin 15s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.creator-meta {
  flex: 1;
}

.creator-phase-name {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  color: #fca5a5;
  margin: 0;
}

.creator-phase-title {
  font-size: 0.78rem;
  color: #e11d48;
  font-style: italic;
  margin: 0.15rem 0 0.4rem;
}

.creator-desc {
  font-size: 0.82rem;
  line-height: 1.45;
  color: #cbd5e1;
  margin: 0 0 0.6rem;
}

.creator-hp-bar-wrap {
  margin-top: 0.5rem;
}

.hp-label-row {
  display: flex;
  justify-content: space-between;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #f87171;
  margin-bottom: 0.25rem;
}

.hp-bar-track {
  height: 8px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(225, 29, 72, 0.4);
  border-radius: 2px;
  overflow: hidden;
}

.hp-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #e11d48, #fb7185);
  transition: width 0.3s ease;
}

.archive-question-box {
  background: rgba(14, 10, 18, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  padding: 1rem;
  margin-top: 1rem;
}

.q-domain-strip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.5rem;
}

.q-domain-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #94a3b8;
}

.q-domain-val {
  font-size: 0.75rem;
  color: #fbbf24;
  font-weight: 600;
}

.q-text {
  font-size: 0.95rem;
  line-height: 1.5;
  color: #f8fafc;
  margin: 0 0 1rem;
}

.choices-grid {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.choice-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(26, 18, 30, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 4px;
  padding: 0.65rem 0.85rem;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.choice-btn:hover:not(:disabled) {
  background: rgba(225, 29, 72, 0.15);
  border-color: rgba(225, 29, 72, 0.45);
}

.choice-key {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  width: 20px;
}

.choice-text {
  font-size: 0.85rem;
  color: #e2e8f0;
}

.choice-correct {
  background: rgba(16, 185, 129, 0.2) !important;
  border-color: #10b981 !important;
}

.choice-wrong {
  background: rgba(239, 68, 68, 0.2) !important;
  border-color: #ef4444 !important;
}

.q-feedback {
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 4px;
}

.feedback-text {
  font-weight: 600;
  font-size: 0.85rem;
  margin: 0 0 0.35rem;
}

.feedback-expl {
  font-size: 0.78rem;
  line-height: 1.45;
  color: #cbd5e1;
  margin: 0 0 0.5rem;
}

.archive-victory-state {
  text-align: center;
  padding: 2rem 1rem;
}

.victory-crest {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

.victory-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.1em;
}

.victory-title {
  font-family: 'Cinzel', serif;
  font-size: 2rem;
  color: #f8fafc;
  margin: 0.35rem 0 1rem;
}

.victory-lore {
  max-width: 580px;
  margin: 0 auto 1.5rem;
  font-size: 0.92rem;
  line-height: 1.6;
  color: #cbd5e1;
}

.mind-seals-unison-strip {
  margin-bottom: 2rem;
}

.unison-label {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #c084fc;
  margin-bottom: 0.5rem;
}

.seals-glyph-row {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  font-size: 1.4rem;
}
</style>
