<template>
  <div class="boss-battle-page">
    <!-- Battle Navigation Top Bar -->
    <div class="battle-nav-bar">
      <router-link to="/boss" class="flee-btn">← Flee Battle</router-link>
      <div class="battle-title-tag">
        ⚔️ Boss Encounter: <strong>{{ boss?.name || 'Academic Guardian' }}</strong>
      </div>
      <div class="turn-counter">
        Round {{ currentQuestionIndex + 1 }} / {{ questions.length || 5 }}
      </div>
    </div>

    <!-- Health Bars Overlay -->
    <div class="glass-card battle-hud-card">
      <!-- Player HP Bar -->
      <div class="hud-unit player-hud">
        <div class="hud-info">
          <span class="hud-name">🧙‍♂️ {{ auth.user?.username || 'Hero' }}</span>
          <span class="hud-hp-num">{{ playerHP }} / {{ playerMaxHP }} HP</span>
        </div>
        <div class="hp-bar-outer">
          <div
            class="hp-bar-fill player-fill"
            :style="{ width: (playerHP / playerMaxHP * 100) + '%' }"
          ></div>
        </div>
      </div>

      <!-- VS Badge -->
      <div class="vs-badge">VS</div>

      <!-- Boss HP Bar -->
      <div class="hud-unit boss-hud">
        <div class="hud-info">
          <span class="hud-name">👾 {{ boss?.name || 'Boss' }}</span>
          <span class="hud-hp-num">{{ bossHP }} / {{ bossMaxHP }} HP</span>
        </div>
        <div class="hp-bar-outer">
          <div
            class="hp-bar-fill boss-fill"
            :style="{ width: (bossHP / bossMaxHP * 100) + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Phaser Canvas Container -->
    <div class="phaser-viewport glass-card">
      <div id="phaser-boss-container" ref="phaserContainer" class="phaser-canvas-wrapper"></div>
    </div>

    <!-- Interactive Battle Quiz Console -->
    <div v-if="!battleOver" class="glass-card quiz-console">
      <!-- Question Header & Timer -->
      <div class="quiz-top">
        <div class="subject-chip">{{ currentQuestion?.subject || boss?.subject || 'Question' }}</div>
        <div class="timer-badge" :class="{ 'timer-urgent': questionTimeLeft <= 5 }">
          ⏱️ {{ questionTimeLeft }}s Remaining
        </div>
      </div>

      <!-- Question Text -->
      <h3 class="question-text">
        {{ currentQuestion?.question_text || currentQuestion?.question || 'Solve this academic query to strike the boss!' }}
      </h3>

      <!-- Answer Choices -->
      <div class="answers-grid">
        <button
          v-for="(choice, idx) in currentQuestionChoices"
          :key="idx"
          class="choice-btn"
          :class="{
            'selected': selectedAnswer === choice.key,
            'correct-choice': answered && String(choice.key).toUpperCase() === String(currentQuestion?.correct_answer || '').toUpperCase(),
            'wrong-choice': answered && selectedAnswer === choice.key && String(choice.key).toUpperCase() !== String(currentQuestion?.correct_answer || '').toUpperCase()
          }"
          :disabled="answered"
          @click="selectChoice(choice.key)"
        >
          <span class="choice-key">{{ choice.key }}</span>
          <span class="choice-label">{{ choice.text }}</span>
        </button>
      </div>

      <!-- Action Abilities Bar -->
      <div class="action-abilities-bar">
        <button
          class="ability-btn strike-btn"
          :class="{ active: chosenAbility === 'strike' }"
          :disabled="answered"
          @click="chosenAbility = 'strike'"
        >
          <span class="ability-icon">⚔️</span>
          <span class="ability-info">
            <strong>Arcane Strike</strong>
            <small>Standard Damage (30 HP)</small>
          </span>
        </button>

        <button
          class="ability-btn spell-btn"
          :class="{ active: chosenAbility === 'crit' }"
          :disabled="answered"
          @click="chosenAbility = 'crit'"
        >
          <span class="ability-icon">⚡</span>
          <span class="ability-info">
            <strong>Critical Surge</strong>
            <small>High Risk, 60 HP (15s limit)</small>
          </span>
        </button>

        <button
          class="ability-btn shield-btn"
          :class="{ active: chosenAbility === 'heal' }"
          :disabled="answered"
          @click="chosenAbility = 'heal'"
        >
          <span class="ability-icon">🛡️</span>
          <span class="ability-info">
            <strong>Barrier Heal</strong>
            <small>Heals 25 HP on Correct</small>
          </span>
        </button>
      </div>
    </div>

    <!-- Battle Over Modal (Victory / Defeat) -->
    <div v-if="battleOver" class="modal-backdrop">
      <div class="glass-card result-modal-card animate-slide-up" :class="battleResult">
        <div class="result-icon">
          {{ battleResult === 'victory' ? '🏆' : '💀' }}
        </div>

        <h2 class="result-title">
          {{ battleResult === 'victory' ? 'VICTORY ACHIEVED!' : 'DEFEATED IN COMBAT' }}
        </h2>

        <p class="result-sub">
          {{ battleResult === 'victory'
            ? `You have vanquished ${boss?.name} and claimed the wisdom spoils!`
            : 'The boss proved too powerful this time. Review your notes and challenge again!'
          }}
        </p>

        <!-- Rewards Box on Victory -->
        <div v-if="battleResult === 'victory'" class="victory-rewards-box">
          <h3>Spoils of War</h3>
          <div class="reward-tags">
            <span class="reward-pill xp">⭐ +{{ boss?.xp_reward || 300 }} XP</span>
            <span class="reward-pill coins">🪙 +{{ boss?.points_reward || boss?.point_reward || 150 }} Coins</span>
            <span v-if="boss?.badge_reward" class="reward-pill badge">🏅 {{ boss?.badge_reward }}</span>
          </div>
        </div>

        <div class="result-modal-actions">
          <router-link to="/boss" class="btn btn-secondary">
            Return to Arena
          </router-link>
          <button v-if="battleResult === 'victory'" class="btn btn-primary" @click="returnToDashboard">
            Dashboard
          </button>
          <button v-else class="btn btn-primary" @click="retryBattle">
            Retry Battle
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { bossService } from '../services/bossService'
import { questionService } from '../services/questionService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
// BossGame loaded lazily to keep Phaser out of the main bundle

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const boss = ref(null)
const questions = ref([])
const currentQuestionIndex = ref(0)
const phaserContainer = ref(null)
let phaserGame = null

const playerMaxHP = ref(100)
const playerHP = ref(100)
const bossMaxHP = ref(100)
const bossHP = ref(100)

const chosenAbility = ref('strike')
const selectedAnswer = ref(null)
const answered = ref(false)
const questionTimeLeft = ref(25)
let questionTimer = null

const battleOver = ref(false)
const battleResult = ref('victory') // 'victory' or 'defeat'

const defaultQuestions = [
  {
    id: 1,
    question_text: 'Who established the first formal psychology laboratory in Leipzig in 1879, marking the birth of modern experimental psychology?',
    option_a: 'Sigmund Freud',
    option_b: 'William James',
    option_c: 'Wilhelm Wundt',
    option_d: 'John B. Watson',
    correct_answer: 'C',
    explanation: 'Wilhelm Wundt is recognized as the father of modern psychology for founding the first laboratory in 1879.',
    subject: 'General Psychology'
  },
  {
    id: 2,
    question_text: 'Which psychological perspective emphasizes unconscious conflicts and early childhood experiences as primary determiners of behavior?',
    option_a: 'Behavioral perspective',
    option_b: 'Psychoanalytic perspective',
    option_c: 'Humanistic perspective',
    option_d: 'Cognitive perspective',
    correct_answer: 'B',
    explanation: 'Freud psychoanalytic perspective posits that unconscious drives and early conflicts shape personality and behavior.',
    subject: 'Theories of Personality'
  },
  {
    id: 3,
    question_text: 'In classical conditioning, a previously neutral stimulus that comes to trigger a conditioned response after repeated pairings is the:',
    option_a: 'Unconditioned stimulus (UCS)',
    option_b: 'Conditioned stimulus (CS)',
    option_c: 'Unconditioned response (UCR)',
    option_d: 'Operant reinforcer',
    correct_answer: 'B',
    explanation: 'A conditioned stimulus (CS) is initially neutral but evokes a conditioned response following associative pairing with an unconditioned stimulus.',
    subject: 'Learning and Behavior'
  },
  {
    id: 4,
    question_text: 'Which lobe of the cerebral cortex contains the primary visual cortex responsible for processing visual sensory stimuli?',
    option_a: 'Temporal lobe',
    option_b: 'Frontal lobe',
    option_c: 'Occipital lobe',
    option_d: 'Parietal lobe',
    correct_answer: 'C',
    explanation: 'The occipital lobe located at the posterior of the brain contains the primary visual reception and interpretation cortex.',
    subject: 'Biological Psychology'
  },
  {
    id: 5,
    question_text: 'According to Jean Piaget, in which developmental stage does a child first acquire conservation of volume and mass?',
    option_a: 'Sensorimotor stage',
    option_b: 'Preoperational stage',
    option_c: 'Concrete operational stage',
    option_d: 'Formal operational stage',
    correct_answer: 'C',
    explanation: 'Conservation—understanding that quantity remains constant despite perceptual transformations—is mastered during concrete operations (ages 7-11).',
    subject: 'Developmental Psychology'
  }
]

const currentQuestion = computed(() => {
  return questions.value[currentQuestionIndex.value] || defaultQuestions[0]
})

const currentQuestionChoices = computed(() => {
  const q = currentQuestion.value
  if (!q) return []
  return [
    { key: 'A', text: q.option_a || q.choice_a || 'Option A' },
    { key: 'B', text: q.option_b || q.choice_b || 'Option B' },
    { key: 'C', text: q.option_c || q.choice_c || 'Option C' },
    { key: 'D', text: q.option_d || q.choice_d || 'Option D' },
  ]
})

function startQuestionTimer() {
  if (questionTimer) clearInterval(questionTimer)
  questionTimeLeft.value = boss.value?.time_per_question || 25

  questionTimer = setInterval(() => {
    if (questionTimeLeft.value > 0) {
      questionTimeLeft.value--
    } else {
      // Time ran out = automatic wrong answer
      handleTimeout()
    }
  }, 1000)
}

function handleTimeout() {
  if (answered.value) return
  toast.add('⏱️ Time expired! The boss strikes!', 'error')
  processAnswer(false)
}

function selectChoice(key) {
  if (answered.value) return
  selectedAnswer.value = key
  answered.value = true
  if (questionTimer) clearInterval(questionTimer)

  const isCorrect = String(key).trim().toUpperCase() === String(currentQuestion.value?.correct_answer || '').trim().toUpperCase()

  // Record attempt for student learning analytics
  if (currentQuestion.value?.id) {
    const elapsed = Math.max(1, (boss.value?.time_per_question || 25) - questionTimeLeft.value)
    questionService.submitAttempt(currentQuestion.value.id, key, elapsed)
      .catch(() => {})
  }

  processAnswer(isCorrect)
}

function processAnswer(isCorrect) {
  const scene = phaserGame?.scene?.getScene('BossScene')

  if (isCorrect) {
    let dmg = 30
    let isCrit = chosenAbility.value === 'crit'

    if (chosenAbility.value === 'crit') {
      dmg = 60
    } else if (chosenAbility.value === 'heal') {
      playerHP.value = Math.min(playerMaxHP.value, playerHP.value + 25)
      toast.add('🛡️ Barrier Healed +25 HP!', 'info')
    }

    bossHP.value = Math.max(0, bossHP.value - dmg)
    if (scene) scene.playPlayerAttack(dmg, isCrit)
    toast.add(`💥 Correct! Dealt ${dmg} damage to ${boss.value?.name}!`, 'success')

    if (bossHP.value <= 0) {
      setTimeout(() => triggerBattleOver('victory'), 1200)
      return
    }
  } else {
    const bossDmg = 25
    playerHP.value = Math.max(0, playerHP.value - bossDmg)
    if (scene) scene.playBossAttack(bossDmg)
    toast.add(`❌ Incorrect! ${boss.value?.name} dealt ${bossDmg} damage!`, 'error')

    if (playerHP.value <= 0) {
      setTimeout(() => triggerBattleOver('defeat'), 1200)
      return
    }
  }

  // Move to next question after delay
  setTimeout(() => {
    if (currentQuestionIndex.value < questions.value.length - 1) {
      currentQuestionIndex.value++
      selectedAnswer.value = null
      answered.value = false
      startQuestionTimer()
    } else {
      // Questions finished
      if (bossHP.value < playerHP.value) {
        triggerBattleOver('victory')
      } else {
        triggerBattleOver('defeat')
      }
    }
  }, 1800)
}

async function triggerBattleOver(result) {
  battleOver.value = true
  battleResult.value = result
  if (questionTimer) clearInterval(questionTimer)

  const scene = phaserGame?.scene?.getScene('BossScene')
  if (result === 'victory' && scene) {
    scene.playBossDefeat()
  }

  if (result === 'victory') {
    try {
      const res = await bossService.recordVictory(boss.value.id, {
        player_hp: playerHP.value
      })
      if (res.data?.success) {
        const xpEarned = res.data.rewards?.xp ?? boss.value?.xp_reward ?? 300
        const coinsEarned = res.data.rewards?.points ?? boss.value?.points_reward ?? boss.value?.point_reward ?? 150
        toast.add(`🏆 Victory! +${xpEarned} XP and +${coinsEarned} Coins awarded!`, 'success')
      }
      await auth.fetchUser()
    } catch (e) {
      console.error('Victory sync error', e)
      toast.add('Failed to sync victory rewards with server', 'error')
    }
  } else if (result === 'defeat') {
    try {
      await bossService.recordDefeat(boss.value.id, {
        boss_hp: bossHP.value
      })
    } catch (e) {
      console.error('Defeat sync error', e)
    }
  }
}

function retryBattle() {
  battleOver.value = false
  playerHP.value = playerMaxHP.value
  bossHP.value = bossMaxHP.value
  currentQuestionIndex.value = 0
  selectedAnswer.value = null
  answered.value = false

  const scene = phaserGame?.scene?.getScene('BossScene')
  if (scene?.bossContainer) {
    scene.bossContainer.setScale(1).setAlpha(1)
  }

  startQuestionTimer()
}

function returnToDashboard() {
  router.push('/dashboard')
}

onMounted(async () => {
  const bossId = route.params.id
  try {
    const res = await bossService.getById(bossId)
    if (res.data && res.data.success) {
      boss.value = res.data.boss || res.data.data
      boss.value.points_reward = boss.value.points_reward || boss.value.point_reward || 150
      boss.value.xp_reward = boss.value.xp_reward || 300
      bossMaxHP.value = boss.value.max_hp || boss.value.hp || 100
      bossHP.value = bossMaxHP.value

      if (boss.value.questions && boss.value.questions.length > 0) {
        questions.value = boss.value.questions
      } else {
        questions.value = defaultQuestions
      }
    } else {
      boss.value = { id: bossId, name: 'Knowledge Golem', max_hp: 100, xp_reward: 350, points_reward: 150 }
      questions.value = defaultQuestions
    }
  } catch (err) {
    boss.value = { id: bossId, name: 'Knowledge Golem', max_hp: 100, xp_reward: 350, points_reward: 150 }
    questions.value = defaultQuestions
  }

  // Initialize Phaser (lazy loaded to prevent Phaser running on all pages)
  if (phaserContainer.value) {
    try {
      const { createBossGame } = await import('../phaser/boss/BossGame')
      phaserGame = createBossGame('phaser-boss-container', boss.value)
    } catch (e) {
      console.warn('[BossBattle] Phaser init failed:', e)
    }
  }

  startQuestionTimer()
})

onUnmounted(() => {
  if (questionTimer) clearInterval(questionTimer)
  if (phaserGame) {
    phaserGame.destroy(true)
    phaserGame = null
  }
})
</script>

<style scoped>
.boss-battle-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  max-width: 950px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.battle-nav-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.25rem;
}

.flee-btn {
  font-family: 'Press Start 2P', monospace;
  color: #ff6b6b;
  text-decoration: none;
  font-size: 0.7rem;
  padding: 0.5rem 0.85rem;
  background: rgba(139, 26, 26, 0.2);
  border: 1px solid rgba(230, 57, 70, 0.5);
  box-shadow: 0 0 0 2px #050608;
  transition: all 0.2s;
  letter-spacing: 0.05em;
}

.flee-btn:hover {
  background: rgba(230, 57, 70, 0.3);
  border-color: #ef4444;
  transform: translateX(-2px);
}

.battle-title-tag {
  font-family: 'Cinzel', serif;
  font-size: 1rem;
  color: #e2d9cc;
  letter-spacing: 0.06em;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
}

.battle-title-tag strong {
  color: #b59410;
  text-shadow: 0 0 8px rgba(181, 148, 16, 0.5);
}

.turn-counter {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #b59410;
  background: rgba(181, 148, 16, 0.12);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608;
  padding: 0.45rem 0.8rem;
}

.battle-hud-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.25rem 1.75rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(139, 26, 26, 0.4);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  position: relative;
}

.battle-hud-card::before {
  content: "❖";
  position: absolute;
  top: -8px;
  left: 24px;
  color: #b59410;
  font-size: 12px;
}

.battle-hud-card::after {
  content: "❖";
  position: absolute;
  top: -8px;
  right: 24px;
  color: #b59410;
  font-size: 12px;
}

.hud-unit {
  flex: 1;
}

.hud-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.hud-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.hud-hp-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
}

.player-hud .hud-name { color: #52b788; text-shadow: 0 0 6px rgba(82, 183, 136, 0.4); }
.player-hud .hud-hp-num { color: #52b788; }
.boss-hud .hud-name { color: #ff6b6b; text-shadow: 0 0 6px rgba(255, 107, 107, 0.4); }
.boss-hud .hud-hp-num { color: #ff6b6b; }

.hp-bar-outer {
  width: 100%;
  height: 14px;
  background: #050608;
  border-radius: 0;
  overflow: hidden;
  border: 2px solid #1c1e29;
  box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.9);
}

.hp-bar-fill {
  height: 100%;
  transition: width 0.35s ease;
}

.player-fill {
  background: linear-gradient(90deg, #2d6a4f, #52b788);
  box-shadow: 0 0 8px rgba(82, 183, 136, 0.6);
}

.boss-fill {
  background: linear-gradient(90deg, #8b1a1a, #e63946);
  box-shadow: 0 0 10px rgba(230, 57, 70, 0.7);
}

.vs-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.9rem;
  color: #b59410;
  text-shadow: 0 0 8px rgba(181, 148, 16, 0.6);
}

.phaser-viewport {
  padding: 0.5rem;
  overflow: hidden;
  border-radius: 4px;
  background: #050608;
  border: 2px solid rgba(139, 26, 26, 0.4);
  box-shadow: 0 0 0 2px #050608, 0 0 25px rgba(139, 26, 26, 0.25);
  display: flex;
  justify-content: center;
  align-items: center;
}

.phaser-canvas-wrapper {
  width: 100%;
  min-height: 380px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.quiz-console {
  padding: 1.75rem;
  background: linear-gradient(180deg, #16151c 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #050608, 0 10px 30px rgba(0, 0, 0, 0.8);
  position: relative;
}

.quiz-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 0.75rem;
}

.subject-chip {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.timer-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #e2d9cc;
  background: rgba(12, 13, 18, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 2px #050608;
  padding: 0.4rem 0.8rem;
  transition: all 0.3s;
}

.timer-urgent {
  background: rgba(139, 26, 26, 0.3);
  color: #ff6b6b;
  border-color: #ef4444;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.question-text {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f5efe6;
  line-height: 1.5;
  margin-bottom: 1.5rem;
  letter-spacing: 0.02em;
}

.answers-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 650px) {
  .answers-grid {
    grid-template-columns: 1fr;
  }
}

.choice-btn {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1.1rem;
  background: #111218;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  color: #e2d9cc;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  letter-spacing: 0.03em;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s;
}

.choice-btn:hover:not(:disabled) {
  background: rgba(139, 26, 26, 0.2);
  border-color: rgba(230, 57, 70, 0.5);
  transform: translateY(-2px);
}

.choice-key {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.3);
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #b59410;
  flex-shrink: 0;
}

.correct-choice {
  background: rgba(45, 106, 79, 0.3) !important;
  border-color: #52b788 !important;
  color: #95d5b2 !important;
}

.wrong-choice {
  background: rgba(139, 26, 26, 0.3) !important;
  border-color: #e63946 !important;
  color: #ff8585 !important;
}

.action-abilities-bar {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 1.25rem;
}

.ability-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.95rem;
  background: #0d0e14;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  color: #a09789;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s;
}

.ability-btn.active {
  border-color: #b59410;
  background: rgba(181, 148, 16, 0.12);
  color: #e2d9cc;
}

.ability-icon { font-size: 1.3rem; }
.ability-info strong { display: block; font-family: 'Cinzel', serif; font-size: 0.85rem; letter-spacing: 0.04em; }
.ability-info small { display: block; font-family: 'VT323', monospace; font-size: 0.85rem; color: #7b7365; }

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.92);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.5rem;
}

.result-modal-card {
  max-width: 480px;
  width: 100%;
  padding: 2.25rem;
  text-align: center;
  background: #0f1015;
  box-shadow: 0 0 0 3px #050608, 0 15px 40px rgba(0, 0, 0, 0.95);
  position: relative;
}

.result-modal-card.victory {
  border: 2px solid #b59410;
  box-shadow: 0 0 0 3px #050608, 0 0 35px rgba(181, 148, 16, 0.35);
}

.result-modal-card.defeat {
  border: 2px solid #8b1a1a;
  box-shadow: 0 0 0 3px #050608, 0 0 35px rgba(139, 26, 26, 0.45);
}

.result-icon {
  font-size: 3.5rem;
  margin-bottom: 0.75rem;
}

.result-title {
  font-family: 'Cinzel', serif;
  font-size: 1.7rem;
  font-weight: 800;
  color: #e2d9cc;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.result-sub {
  font-size: 0.95rem;
  color: #a59e92;
  line-height: 1.5;
  margin-bottom: 1.5rem;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
}

.victory-rewards-box {
  background: #08090c;
  border: 1px dashed rgba(181, 148, 16, 0.35);
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.victory-rewards-box h3 {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.6rem;
}

.reward-tags {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.reward-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  padding: 0.4rem 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.reward-pill.xp { background: rgba(181, 148, 16, 0.15); color: #e6c845; border-color: rgba(181, 148, 16, 0.4); }
.reward-pill.coins { background: rgba(147, 51, 234, 0.15); color: #c084fc; border-color: rgba(147, 51, 234, 0.4); }
.reward-pill.badge { background: rgba(82, 183, 136, 0.15); color: #74c69d; border-color: rgba(82, 183, 136, 0.4); }

.result-modal-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

@media (max-width: 650px) {
  .battle-hud-card {
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
  }
  .hud-unit {
    width: 100%;
  }
  .vs-badge {
    font-size: 0.9rem;
  }
  .action-abilities-bar {
    grid-template-columns: 1fr;
  }
  .question-text {
    font-size: 1.05rem;
  }
  .quiz-console {
    padding: 1.15rem;
  }
  .phaser-canvas-wrapper {
    min-height: 250px;
  }
  .result-modal-card {
    padding: 1.5rem 1rem;
  }
  .result-modal-actions {
    flex-direction: column;
    width: 100%;
  }
  .result-modal-actions .btn {
    width: 100%;
  }
}
</style>
