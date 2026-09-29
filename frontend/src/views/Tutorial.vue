<template>
  <div class="tutorial-screen">
    <!-- Background -->
    <div class="tut-bg">
      <div class="tut-bg-img"></div>
      <div class="tut-bg-overlay"></div>
    </div>

    <!-- Particles -->
    <div class="tut-particles" aria-hidden="true">
      <div v-for="i in 15" :key="i" class="tut-particle" :style="tutParticleStyle(i)"></div>
    </div>

    <div class="tut-content">

      <!-- PHASE: INTRO ─────────────────────────────────────── -->
      <template v-if="phase === 'intro'">
        <div class="tut-panel glass-card-glow animate-slide-up">
          <div class="tut-intro-header">
            <div class="tut-psi">Ψ</div>
            <div>
              <h1 class="tut-title">Welcome to StudyQuest</h1>
              <p class="tut-subtitle">Your Psychology adventure begins now</p>
            </div>
          </div>

          <div class="intro-slides">
            <div v-for="(slide, i) in introSlides" :key="i" class="intro-slide" :class="{ active: introSlide === i }">
              <div class="slide-icon">{{ slide.icon }}</div>
              <h3>{{ slide.title }}</h3>
              <p>{{ slide.text }}</p>
            </div>
          </div>

          <div class="slide-dots">
            <button v-for="(_, i) in introSlides" :key="i" class="slide-dot"
              :class="{ active: introSlide === i }" @click="introSlide = i" />
          </div>

          <div class="tut-nav">
            <button v-if="introSlide > 0" class="btn btn-ghost" @click="introSlide--">← Back</button>
            <button v-if="introSlide < introSlides.length - 1" class="btn btn-primary" @click="introSlide++">
              Next →
            </button>
            <button v-else class="btn btn-gold btn-lg" @click="phase = 'battle'">
              ⚔️ Start Training!
            </button>
          </div>
        </div>
      </template>

      <!-- PHASE: BATTLE TUTORIAL ───────────────────────────── -->
      <template v-else-if="phase === 'battle'">
        <div class="tut-battle-screen animate-slide-up">
          <!-- Battle header -->
          <div class="tut-battle-header glass-card">
            <span class="battle-label">TRAINING BATTLE</span>
            <span class="battle-step">Question {{ currentQ + 1 }} / {{ tutorialQuestions.length }}</span>
          </div>

          <!-- Characters -->
          <div class="tut-characters">
            <!-- Player side -->
            <div class="tut-combatant player-side">
              <div class="combatant-label">YOU</div>
              <AvatarImg :style-name="auth.avatarStyle" :seed="auth.avatarSeed" :options="auth.avatarOpts" :size="80" class="combatant-avatar" :class="{ shake: playerShake, bounce: playerBounce }" />
              <div class="combatant-name">{{ auth.nickname }}</div>
              <div class="tut-hp-bar">
                <div class="tut-hp-fill" :style="{ width: playerHPPercent + '%' }"></div>
              </div>
              <span class="hp-text">{{ playerHP }} / {{ playerMaxHP }} HP</span>
            </div>

            <!-- VS -->
            <div class="battle-vs">VS</div>

            <!-- Enemy side -->
            <div class="tut-combatant enemy-side">
              <div class="combatant-label">ENEMY</div>
              <div class="enemy-sprite" :class="{ shake: enemyShake, defeated: enemyHP <= 0 }">
                🤖
              </div>
              <div class="combatant-name">Cognito Bot</div>
              <div class="tut-hp-bar enemy-bar">
                <div class="tut-hp-fill enemy-fill" :style="{ width: enemyHPPercent + '%' }"></div>
              </div>
              <span class="hp-text">{{ enemyHP }} / {{ enemyMaxHP }} HP</span>
            </div>
          </div>

          <!-- Instruction hint -->
          <div v-if="hint" class="tut-hint animate-slide-up">
            <span class="hint-arrow">💡</span> {{ hint }}
          </div>

          <!-- Question card -->
          <div v-if="!battleResult && currentQuestion" class="tut-question-card glass-card animate-slide-up">
            <p class="question-text">{{ currentQuestion.question }}</p>
            <div class="question-choices">
              <button
                v-for="(text, key) in currentQuestion.choices"
                :key="key"
                class="choice-btn"
                :class="choiceClass(key)"
                :disabled="answered"
                @click="submitAnswer(key)"
              >
                <span class="choice-key">{{ key }}</span>
                <span class="choice-text">{{ text }}</span>
              </button>
            </div>

            <!-- Explanation after answering -->
            <div v-if="answered && lastExplanation" class="answer-explanation animate-slide-up">
              <span :class="lastCorrect ? 'correct-icon' : 'wrong-icon'">
                {{ lastCorrect ? '✅' : '❌' }}
              </span>
              {{ lastExplanation }}
            </div>

            <button v-if="answered && !battleResult" class="btn btn-primary btn-lg next-q-btn" @click="nextQuestion">
              Next Question →
            </button>
          </div>

          <!-- Damage flash -->
          <Transition name="damage">
            <div v-if="showDamage" class="damage-number" :class="{ 'player-dmg': !lastCorrect }">
              {{ lastCorrect ? '-' + dmgAmount : '-' + playerDmgAmount }}
            </div>
          </Transition>
        </div>
      </template>

      <!-- PHASE: COMPLETE ─────────────────────────────────── -->
      <template v-else-if="phase === 'complete'">
        <div class="tut-complete glass-card-glow animate-slide-up">
          <div class="complete-trophy">🏆</div>
          <h2 class="complete-title">Tutorial Complete!</h2>
          <p class="complete-sub">You're ready to begin your Psychology journey!</p>

          <div class="complete-rewards">
            <div class="reward-item">
              <span class="reward-icon">⭐</span>
              <span class="reward-val">+{{ xpReward }} XP</span>
            </div>
            <div class="reward-item">
              <span class="reward-icon">💰</span>
              <span class="reward-val">+{{ coinReward }} Coins</span>
            </div>
          </div>

          <button class="btn btn-gold btn-lg" @click="enterWorld" :disabled="loading">
            <span v-if="loading">Entering World…</span>
            <span v-else">🗺️ Enter the World!</span>
          </button>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import AvatarImg from '../components/AvatarImg.vue'
import api from '../services/api'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

// Phase management
const phase = ref('intro')
const introSlide = ref(0)
const loading = ref(false)

// Rewards
const xpReward = 100
const coinReward = 50

// Tutorial intro slides
const introSlides = [
  {
    icon: '🧠',
    title: 'Welcome, Psychology Scholar!',
    text: 'StudyQuest is a Psychology learning adventure game. Study, battle, and master Psychology concepts to become a Psychology champion!',
  },
  {
    icon: '⚔️',
    title: 'How Battles Work',
    text: 'Battles are powered by your knowledge! Answer Psychology questions correctly to attack your enemy. Wrong answers let the enemy strike back.',
  },
  {
    icon: '📚',
    title: 'Explore the World',
    text: 'The World Hub is your home base. Visit the Study Hall, Adventure Gate, Arena, and more to grow your Psychology mastery.',
  },
  {
    icon: '🏆',
    title: 'Ready to Train?',
    text: 'Let\'s practice in a training battle! Answer 2 questions to defeat Cognito Bot and earn your first rewards.',
  },
]

// Battle state
const playerHP = ref(100)
const playerMaxHP = ref(100)
const enemyHP = ref(20)
const enemyMaxHP = ref(20)
const currentQ = ref(0)
const answered = ref(false)
const selectedAnswer = ref(null)
const lastCorrect = ref(false)
const lastExplanation = ref('')
const battleResult = ref(null)
const playerShake = ref(false)
const playerBounce = ref(false)
const enemyShake = ref(false)
const showDamage = ref(false)
const dmgAmount = ref(10)
const playerDmgAmount = ref(15)
const hint = ref('Answer the Psychology question below to attack!')

const playerHPPercent = computed(() => (playerHP.value / playerMaxHP.value) * 100)
const enemyHPPercent = computed(() => (enemyHP.value / enemyMaxHP.value) * 100)

// Embedded tutorial questions (no API needed for tutorial)
const tutorialQuestions = [
  {
    id: 'tut-1',
    question: 'Which process involves the encoding, storage, and retrieval of information?',
    choices: {
      A: 'Memory',
      B: 'Motivation',
      C: 'Emotion',
      D: 'Perception',
    },
    correct_answer: 'A',
    explanation: 'Memory is the cognitive process that enables the encoding, storage, and retrieval of information.',
  },
  {
    id: 'tut-2',
    question: 'Who is considered the father of modern Psychology?',
    choices: {
      A: 'Sigmund Freud',
      B: 'William James',
      C: 'Wilhelm Wundt',
      D: 'B.F. Skinner',
    },
    correct_answer: 'C',
    explanation: 'Wilhelm Wundt established the first experimental psychology laboratory in 1879, earning him the title "Father of Modern Psychology."',
  },
]

const currentQuestion = computed(() => tutorialQuestions[currentQ.value] || null)

function choiceClass(key) {
  if (!answered.value) return {}
  const correct = currentQuestion.value?.correct_answer
  if (key === correct) return { correct: true }
  if (key === selectedAnswer.value && key !== correct) return { wrong: true }
  return { dimmed: true }
}

async function submitAnswer(key) {
  if (answered.value) return
  answered.value = true
  selectedAnswer.value = key
  const correct = currentQuestion.value?.correct_answer
  lastCorrect.value = key === correct
  lastExplanation.value = currentQuestion.value?.explanation || ''
  hint.value = ''

  if (lastCorrect.value) {
    // Player attacks enemy
    dmgAmount.value = 10
    enemyHP.value = Math.max(0, enemyHP.value - dmgAmount.value)
    enemyShake.value = true
    showDamage.value = true
    setTimeout(() => { enemyShake.value = false; showDamage.value = false }, 600)
    if (enemyHP.value <= 0) {
      setTimeout(() => { battleResult.value = 'victory'; phase.value = 'complete' }, 1000)
    }
  } else {
    // Enemy attacks player
    playerDmgAmount.value = 15
    playerHP.value = Math.max(0, playerHP.value - playerDmgAmount.value)
    playerShake.value = true
    setTimeout(() => { playerShake.value = false }, 500)
    hint.value = 'Oops! Study the explanation and try the next question!'
  }
}

function nextQuestion() {
  if (currentQ.value < tutorialQuestions.length - 1) {
    currentQ.value++
    answered.value = false
    selectedAnswer.value = null
    lastExplanation.value = ''
    hint.value = 'Answer the Psychology question to attack!'
  } else {
    phase.value = 'complete'
  }
}

async function enterWorld() {
  loading.value = true
  try {
    await api.post('/adventure/tutorial/complete')
    await auth.fetchUser()
    toast.success('🎉 Tutorial complete! Welcome to StudyQuest!')
    router.push('/world')
  } catch {
    router.push('/world')
  } finally {
    loading.value = false
  }
}

function tutParticleStyle(i) {
  return {
    left: ((i * 19 + 7) % 100) + '%',
    top: ((i * 23 + 11) % 100) + '%',
    width: (2 + i % 3) + 'px',
    height: (2 + i % 3) + 'px',
    animationDelay: (i * 0.4) + 's',
    animationDuration: (4 + i % 5) + 's',
    opacity: 0.12 + (i % 5) * 0.07,
  }
}
</script>

<style scoped>
.tutorial-screen {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  position: relative;
  padding: 2rem 1rem;
  overflow-x: hidden;
  color: #e2d9cc;
}

.tut-bg { position: fixed; inset: 0; z-index: 0; }
.tut-bg-img {
  position: absolute; inset: 0;
  background: url('/assets/hero/title_hero.jpg') center / cover no-repeat;
  opacity: 0.05;
}
.tut-bg-overlay {
  position: absolute; inset: 0;
  background: radial-gradient(circle at 50% 30%, #121018 0%, #050608 100%);
}
.tut-particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; overflow: hidden; }
.tut-particle {
  position: absolute; border-radius: 2px;
  background: radial-gradient(circle, rgba(230, 200, 69, 0.6), rgba(181, 148, 16, 0.1));
  animation: tutParticleFloat ease-in-out infinite;
}
@keyframes tutParticleFloat {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-18px) scale(1.2); }
}

.tut-content {
  position: relative; z-index: 2;
  width: 100%; max-width: 640px;
  margin: 0 auto;
  padding-top: 1rem;
}

/* ── Intro panel ─────────────────── */
.tut-panel {
  padding: 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.9), inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}
.tut-intro-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.75rem; }
.tut-psi {
  width: 60px; height: 60px;
  background: #09090e;
  border: 2px solid #b59410;
  box-shadow: 0 0 0 1px #050608, 0 0 15px rgba(181, 148, 16, 0.3);
  border-radius: 4px; display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; font-family: 'Cinzel', serif; color: #e6c845; flex-shrink: 0;
}
.tut-title {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem;
  font-weight: 700;
  color: #f3ede2;
  letter-spacing: 0.05em;
  text-shadow: 0 0 12px rgba(181, 148, 16, 0.2);
}
.tut-subtitle {
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  color: #8c887b;
}

.intro-slides { min-height: 140px; }
.intro-slide { display: none; text-align: center; padding: 1rem 0; }
.intro-slide.active { display: block; animation: slideUp 0.3s ease; }
.slide-icon { font-size: 3rem; margin-bottom: 0.75rem; }
.intro-slide h3 {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #f3ede2;
}
.intro-slide p { color: #9ca3af; font-size: 0.95rem; line-height: 1.6; }

.slide-dots { display: flex; justify-content: center; gap: 0.5rem; margin: 1.5rem 0; }
.slide-dot {
  width: 10px; height: 10px; border-radius: 2px;
  background: #181926; border: 1px solid #2b2d42; cursor: pointer; transition: all 0.2s;
}
.slide-dot.active { background: #b59410; border-color: #e6c845; transform: scale(1.2); }

.tut-nav { display: flex; justify-content: flex-end; gap: 0.75rem; }

/* ── Battle screen ─────────────────── */
.tut-battle-screen { display: flex; flex-direction: column; gap: 1.25rem; }
.tut-battle-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px;
}
.battle-label {
  font-family: 'Press Start 2P', monospace;
  font-weight: 700;
  font-size: 0.75rem;
  color: #e6c845;
  letter-spacing: 0.08em;
}
.battle-step {
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  color: #8c887b;
}

/* Characters */
.tut-characters {
  display: flex; align-items: center; justify-content: space-between;
  gap: 1rem; padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}
.tut-combatant { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; flex: 1; }
.combatant-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  font-weight: 700;
  color: #8c887b;
  letter-spacing: 0.08em;
}
.combatant-avatar {
  border-radius: 4px;
  border: 2px solid #34d399;
  box-shadow: 0 0 0 1px #050608;
  transition: transform 0.1s;
}
.combatant-avatar.shake { animation: shakeAnim 0.4s ease; }
.combatant-avatar.bounce { animation: bounceAnim 0.4s ease; }
.combatant-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #f3ede2;
}

.enemy-sprite { font-size: 3.5rem; transition: all 0.3s; }
.enemy-sprite.shake { animation: shakeAnim 0.4s ease; }
.enemy-sprite.defeated { filter: grayscale(1) opacity(0.3); transform: scale(0.8); }

.tut-hp-bar {
  width: 100%; height: 10px;
  background: #09090e;
  border: 1px solid #202230;
  border-radius: 0;
  overflow: hidden;
}
.tut-hp-fill {
  height: 100%;
  background: linear-gradient(90deg, #059669, #10b981);
  transition: width 0.4s ease;
}
.enemy-bar .tut-hp-fill {
  background: linear-gradient(90deg, #8b1a1a, #dc2626);
}
.hp-text {
  font-size: 0.65rem;
  color: #8c887b;
  font-family: 'Press Start 2P', monospace;
}

.battle-vs {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: #e6c845;
  flex-shrink: 0;
}

/* Hint */
.tut-hint {
  background: rgba(181, 148, 16, 0.08);
  border: 1px solid rgba(181, 148, 16, 0.3);
  border-radius: 2px;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  color: #fef08a;
  display: flex; gap: 0.5rem;
}

/* Question */
.tut-question-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}
.question-text {
  font-size: 1.05rem;
  color: #f3ede2;
  line-height: 1.6;
  margin-bottom: 1.25rem;
  font-weight: 500;
}
.question-choices { display: grid; grid-template-columns: 1fr 1fr; gap: 0.625rem; }
.choice-btn {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.75rem 1rem; border-radius: 2px;
  background: #09090e; border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  color: #cbd5e1; cursor: pointer; text-align: left;
  transition: all 0.2s; font-size: 0.875rem;
  font-family: inherit;
}
.choice-btn:hover:not(:disabled) {
  background: #141520; border-color: #b59410; color: #f3ede2;
}
.choice-btn:disabled { cursor: default; }
.choice-btn.correct {
  background: rgba(16, 185, 129, 0.2);
  border-color: #10b981;
  color: #34d399;
}
.choice-btn.wrong {
  background: rgba(220, 38, 38, 0.2);
  border-color: #dc2626;
  color: #fca5a5;
}
.choice-btn.dimmed { opacity: 0.35; }
.choice-key {
  width: 26px; height: 26px; border-radius: 2px; flex-shrink: 0;
  background: #181926; border: 1px solid #32354a;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Press Start 2P', monospace; font-weight: 700; font-size: 0.7rem;
  color: #e6c845;
}

.answer-explanation {
  margin-top: 1rem; padding: 0.75rem 1rem;
  background: #09090e; border: 1px solid #202230; border-radius: 2px;
  font-size: 0.85rem; color: #9ca3af; line-height: 1.5;
  display: flex; gap: 0.5rem; align-items: flex-start;
}
.correct-icon { color: #34d399; flex-shrink: 0; }
.wrong-icon { color: #f87171; flex-shrink: 0; }

.next-q-btn { margin-top: 1rem; width: 100%; justify-content: center; }

/* Damage number */
.damage-number {
  position: fixed; top: 30%; right: 30%;
  font-family: 'Press Start 2P', monospace; font-size: 1.6rem; font-weight: 900;
  color: #ef4444; text-shadow: 0 0 15px rgba(239, 68, 68, 0.8);
  pointer-events: none; z-index: 100;
}
.damage-number.player-dmg { color: #e6c845; text-shadow: 0 0 15px rgba(230, 200, 69, 0.8); }
.damage-enter-active { animation: dmgFloat 0.6s ease; }
.damage-leave-active { animation: dmgFade 0.3s ease; }
@keyframes dmgFloat { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-40px); } }
@keyframes dmgFade { to { opacity: 0; } }

/* Complete */
.tut-complete {
  text-align: center;
  padding: 3rem 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 0 25px rgba(181, 148, 16, 0.25);
  border-radius: 4px;
}
.complete-trophy { font-size: 4rem; margin-bottom: 1rem; animation: trophyBounce 0.8s ease; }
@keyframes trophyBounce {
  0% { transform: scale(0); } 60% { transform: scale(1.2); } 100% { transform: scale(1); }
}
.complete-title {
  font-family: 'Cinzel', serif;
  font-size: 2rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.5rem;
}
.complete-sub {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  margin-bottom: 2rem;
}
.complete-rewards { display: flex; justify-content: center; gap: 1.5rem; margin-bottom: 2rem; }
.reward-item { display: flex; align-items: center; gap: 0.5rem; }
.reward-icon { font-size: 1.5rem; }
.reward-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 1rem;
  color: #e6c845;
}

/* Animations */
@keyframes shakeAnim {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-6px); }
  75% { transform: translateX(6px); }
}
@keyframes bounceAnim {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

@media (max-width: 480px) {
  .question-choices { grid-template-columns: 1fr; }
  .tut-characters { flex-direction: column; gap: 0.75rem; }
  .battle-vs { font-size: 1rem; }
  .enemy-sprite { font-size: 2.5rem; }
}
</style>
