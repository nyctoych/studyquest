<template>
  <div class="tutorial-screen" :class="{ 'is-battle-active': phase === 'battle' }">
    <!-- Background -->
    <div class="tut-bg">
      <div class="tut-bg-img"></div>
      <div class="tut-bg-overlay"></div>
    </div>

    <!-- Ambient Particles -->
    <div class="tut-particles" aria-hidden="true">
      <div v-for="i in 15" :key="i" class="tut-particle" :style="tutParticleStyle(i)"></div>
    </div>

    <!-- Top Action Bar (Skip Tutorial anytime) -->
    <div class="tut-top-bar">
      <div class="tut-phase-badge font-pixel">
        <span class="badge-icon">🛡️</span>
        <span>{{ phase === 'intro' ? 'PROLOGUE' : (phase === 'battle' ? 'BATTLEFIELD BASICS' : 'COMPLETE') }}</span>
      </div>

      <button
        type="button"
        class="btn-skip-full-tutorial font-pixel"
        @click="showSkipModal = true"
        title="Skip tutorial and enter World Hub"
      >
        SKIP ONBOARDING ✕
      </button>
    </div>

    <div class="tut-content" :class="{ 'is-battle': phase === 'battle' }">

      <!-- ══════════════════════════════════════════════════════════════
           PHASE 1: PROLOGUE INTRO SLIDES
           ══════════════════════════════════════════════════════════════ -->
      <template v-if="phase === 'intro'">
        <div class="tut-panel glass-card-glow animate-slide-up">
          <div class="tut-intro-header">
            <div class="tut-psi">Ψ</div>
            <div>
              <h1 class="tut-title">Welcome to StudyQuest</h1>
              <p class="tut-subtitle">Your Psychology RPG Adventure Begins</p>
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
            <button v-if="introSlide > 0" class="btn btn-ghost font-pixel" @click="introSlide--">◀ Back</button>
            <button v-if="introSlide < introSlides.length - 1" class="btn btn-primary font-pixel" @click="introSlide++">
              Next ▶
            </button>
            <button v-else class="btn btn-gothic-gold btn-lg font-pixel animate-pulse-glow" @click="startBattlefieldTutorial">
              ⚔️ ENTER BATTLEFIELD TUTORIAL
            </button>
          </div>
        </div>
      </template>

      <!-- ══════════════════════════════════════════════════════════════
           PHASE 2: BATTLEFIELD TUTORIAL WITH INTERACTIVE BUBBLE CHAT
           ══════════════════════════════════════════════════════════════ -->
      <template v-else-if="phase === 'battle'">
        <div class="tut-battle-wrapper animate-slide-up">
          <!-- Turn-Based RPG Battlefield -->
          <AdventureBattlefield
            v-if="!battleComplete"
            :is-boss="false"
            :chapter-id="1"
            :part-num="1"
            :chapter-title="'TRAINING SANCTUM'"
            :part-title="'Cognito Bot Trial'"
            :enemy-name="'Cognito Bot'"
            :enemy-title="'Automaton of Memory'"
            :player-hp="playerHP"
            :player-max-hp="playerMaxHP"
            :player-level="auth.user?.level || 1"
            :enemy-hp="enemyHP"
            :enemy-max-hp="enemyMaxHP"
            :damage-dealt="dmgAmount"
            :damage-taken="playerDmgAmount"
            :questions="tutorialQuestions"
            :current-q-index="currentQ"
            :current-q="currentQuestion"
            :answered="answered"
            :selected-key="selectedAnswer"
            :last-correct="lastCorrect"
            :is-last-question="currentQ >= tutorialQuestions.length - 1"
            :is-tutorial="true"
            @answer="handleBattleAnswer"
            @answered="onBattleAnswered"
            @next="nextQuestion"
            @opening-complete="onOpeningComplete"
          />

          <!-- ── INTERACTIVE POINTER CHAT BUBBLE OVERLAY ─────────── -->
          <div v-if="bfGuideActive && !battleComplete" class="bf-guide-overlay animate-fade-in">
            <!-- Pulsating highlight around focused element -->
            <div
              v-if="bfSpotlightRect"
              class="bf-target-glow animate-pulse-glow"
              :style="{
                left: `${bfSpotlightRect.x - 4}px`,
                top: `${bfSpotlightRect.y - 4}px`,
                width: `${bfSpotlightRect.width + 8}px`,
                height: `${bfSpotlightRect.height + 8}px`
              }"
            ></div>

            <!-- Focused Speech Chat Bubble pointing at target element -->
            <div
              class="bf-speech-bubble gothic-panel animate-scale-up"
              :class="`bubble-pos-${bfBubblePos.placement}`"
              :style="bfBubblePos.style"
              role="dialog"
            >
              <!-- Arrow Tail pointing at element -->
              <div
                v-if="bfBubblePos.placement !== 'none'"
                class="bubble-tail"
                :class="`tail-${bfBubblePos.placement}`"
                :style="{ left: `${bfBubblePos.arrowOffset}px` }"
              ></div>

              <!-- Top Bar -->
              <div class="bubble-header-row">
                <span class="bubble-step-badge font-pixel">
                  {{ bfStepMeta.badge }}
                </span>
                <button
                  type="button"
                  class="bubble-close-btn"
                  @click="skipToPracticeTrial"
                  title="Close guide and practice battle"
                >
                  ✕
                </button>
              </div>

              <!-- Title -->
              <h4 class="bubble-title font-pixel text-gold">
                {{ bfStepMeta.title }}
              </h4>

              <!-- Concise Friendly Explanation -->
              <p class="bubble-desc font-cinzel">
                {{ bfStepMeta.text }}
              </p>

              <!-- Footer Navigation -->
              <div class="bubble-footer-row">
                <button
                  v-if="bfCurrentStep > 1"
                  type="button"
                  class="btn-bubble-ghost font-pixel"
                  @click="prevBfStep"
                >
                  ◀ BACK
                </button>
                <div v-else class="bubble-spacer"></div>

                <div class="bubble-dots">
                  <span
                    v-for="s in 5"
                    :key="s"
                    class="b-dot"
                    :class="{ active: bfCurrentStep === s }"
                  ></span>
                </div>

                <button
                  v-if="bfCurrentStep < 5"
                  type="button"
                  class="btn-bubble-gold font-pixel"
                  @click="nextBfStep"
                >
                  NEXT ▶
                </button>
                <button
                  v-else
                  type="button"
                  class="btn-bubble-gold font-pixel animate-pulse-glow"
                  @click="skipToPracticeTrial"
                >
                  TRY IT ⚔️
                </button>
              </div>
            </div>
          </div>

          <!-- ── BATTLE COMPLETE VICTORY DIALOG ─────────────────── -->
          <div v-else-if="battleComplete && battleResult === 'victory'" class="battle-victory-simple gothic-panel animate-scale-up">
            <span class="victory-trophy">🏆</span>
            <h3 class="victory-title font-pixel text-gold">BATTLEFIELD BASICS COMPLETE!</h3>
            <p class="victory-desc font-cinzel">
              You understand the battlefield controls, your combat stats, and answer selection.
              Let's explore the rest of Mindrealm!
            </p>
            <button
              class="btn btn-gothic-gold btn-md font-pixel animate-pulse-glow"
              @click="proceedToModuleTour"
              :disabled="loading"
            >
              <span v-if="loading">INITIALIZING TOUR…</span>
              <span v-else>EXPLORE THE REALM 🏰</span>
            </button>
          </div>
        </div>
      </template>

    </div>

    <!-- ── SKIP ONBOARDING CONFIRMATION MODAL ─────────────────────── -->
    <div v-if="showSkipModal" class="tut-skip-overlay">
      <div class="tut-skip-card gothic-panel animate-scale-up">
        <div class="skip-skull">⚠️</div>
        <h3 class="skip-title font-pixel text-gold">SKIP ONBOARDING TUTORIAL?</h3>
        <p class="skip-desc font-cinzel">
          Are you sure you want to exit the tutorial? You will enter the Citadel Realm directly.
          You can replay the tutorial at any time through Settings or your Profile.
        </p>
        <div class="skip-actions font-pixel">
          <button class="btn btn-gothic-ghost btn-sm" @click="showSkipModal = false">
            KEEP TRAINING
          </button>
          <button class="btn btn-gothic-crimson btn-sm" @click="handleSkipAll" :disabled="loading">
            YES, ENTER REALM
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useHealthStore } from '../stores/health'
import { useTutorialStore } from '../stores/tutorial'
import AdventureBattlefield from '../components/adventure/AdventureBattlefield.vue'

const router = useRouter()
const auth = useAuthStore()
const healthStore = useHealthStore()
const toast = useToastStore()
const tutorialStore = useTutorialStore()

// Phase management: 'intro' | 'battle'
const phase = ref('intro')
const introSlide = ref(0)
const loading = ref(false)
const showSkipModal = ref(false)

// Battlefield sub-guide state: 1 to 5
const bfGuideActive = ref(false)
const bfGuideDismissed = ref(false)
const bfCurrentStep = ref(1)
const bfSpotlightRect = ref(null)
let bfOpeningSafetyTimeout = null

// Intro slides
const introSlides = [
  {
    icon: '🧠',
    title: 'Welcome, Psychology Scholar!',
    text: 'StudyQuest is an immersive dark-fantasy Psychology RPG. Master clinical criteria, theoretical principles, and board-exam questions to conquer the Mindrealm!',
  },
  {
    icon: '⚔️',
    title: 'Turn-Based RPG Combat',
    text: 'Every battle is powered by your knowledge! Answer psychology inquiries correctly to unleash devastating attacks. Wrong answers deal damage back to you.',
  },
  {
    icon: '🏛️',
    title: 'Explore the Citadel Realm',
    text: 'The realm contains 11 dedicated modules: Study Hall, Adventure Gate, Achievement Hall, Vault, Hall of Fame, Market, Leaderboards, Test Your Knowledge, Multiplayer, Settings, and Profile.',
  },
  {
    icon: '🛡️',
    title: 'Foundational Training',
    text: 'First, we will inspect your combat attributes and battlefield controls, followed by a live trial encounter against Cognito Bot!',
  },
]

// Character calculations
const charName = computed(() => auth.user?.selected_character?.name || 'Scholar')
const charClass = computed(() => auth.user?.selected_character?.class_type || 'MAGE')

const baseDamage = computed(() => {
  const c = auth.user?.selected_character
  return Number(c?.damage || (charClass.value === 'RANGE' ? 15 : 10))
})

const critDamage = computed(() => {
  const c = auth.user?.selected_character
  return Number(c?.crit_damage || (charClass.value === 'RANGE' ? 50 : (charClass.value === 'MELEE' ? 20 : 35)))
})

// Battle State
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
const battleComplete = ref(false)
const dmgAmount = ref(10)
const playerDmgAmount = ref(15)

// Embedded tutorial questions
const tutorialQuestions = [
  {
    id: 'tut-1',
    question: 'Which cognitive process involves the encoding, storage, and retrieval of information over time?',
    choices: {
      A: 'Memory',
      B: 'Motivation',
      C: 'Emotion',
      D: 'Perception',
    },
    correct_answer: 'A',
    explanation: 'Memory is the fundamental cognitive mechanism through which sensory information is encoded, consolidated in storage, and subsequently retrieved.',
  },
  {
    id: 'tut-2',
    question: 'Who established the first experimental psychology laboratory in Leipzig (1879), earning the title "Father of Modern Psychology"?',
    choices: {
      A: 'Sigmund Freud',
      B: 'William James',
      C: 'Wilhelm Wundt',
      D: 'B.F. Skinner',
    },
    correct_answer: 'C',
    explanation: 'Wilhelm Wundt founded the first dedicated laboratory for psychological research in 1879, formally separating psychology from philosophy and biology.',
  },
]

const currentQuestion = computed(() => tutorialQuestions[currentQ.value] || null)

// Step metadata: clean, simple, not overwhelming
const bfStepMeta = computed(() => {
  switch (bfCurrentStep.value) {
    case 1:
      return {
        badge: '🛡️ CHARACTER (1/5)',
        title: 'Player Status & HP',
        text: `Here is your ${charName.value} (${charClass.value}). Correct answers attack with your base damage (${baseDamage.value} DMG). In Adventure, HP carries over between stages—heal with Potions or rest if wounded!`
      }
    case 2:
      return {
        badge: '📜 QUESTIONS (2/5)',
        title: 'Questions & Hypotheses',
        text: 'Read each inquiry and select your answer (A, B, C, or D). Reason carefully through the concepts—correct answers damage the enemy, while mistakes damage you!'
      }
    case 3:
      return {
        badge: '💥 CRITICAL SWITCH (3/5)',
        title: 'Critical Mode Toggle',
        text: `When ON, correct answers deal massive Critical Damage (${critDamage.value} DMG)! But wrong answers deal critical backfire damage to yourself. Switch it ON or OFF anytime.`
      }
    case 4:
      return {
        badge: '⏩ SPEED SWITCH (4/5)',
        title: 'Skip Animation Toggle',
        text: 'Turn this ON to bypass battle animations for swift study pacing. Damage calculations, HP, and match outcomes stay 100% the same.'
      }
    case 5:
      return {
        badge: '⚡ INPUT SWITCH (5/5)',
        title: 'Auto-Confirm Answer',
        text: 'When ON, tapping an answer attacks immediately without asking to confirm. When OFF, you can double-check your choice before striking.'
      }
    default:
      return { badge: 'TUTORIAL', title: '', text: '' }
  }
})

// Battlefield Guide Step Selectors
const BF_STEP_SELECTORS = {
  1: '.slot-player, .player-nameplate',
  2: '.controls-left-question, .battle-question-panel',
  3: '.crit-toggle-wrapper, .crit-switch-badge',
  4: '.toggle-skip-anim',
  5: '.toggle-auto-confirm'
}

function updateBfSpotlight() {
  const sel = BF_STEP_SELECTORS[bfCurrentStep.value]
  if (!sel) {
    bfSpotlightRect.value = null
    return
  }
  const el = document.querySelector(sel)
  if (el && el.offsetParent !== null) {
    const r = el.getBoundingClientRect()
    bfSpotlightRect.value = {
      x: Math.round(r.left),
      y: Math.round(r.top),
      width: Math.round(r.width),
      height: Math.round(r.height)
    }
  } else {
    bfSpotlightRect.value = null
  }
}

// Compute Chat Bubble Position & Arrow Offset
const bfBubblePos = computed(() => {
  const rect = bfSpotlightRect.value
  const bubbleWidth = 340
  const bubbleHeight = 170

  if (!rect) {
    return {
      style: {
        position: 'fixed',
        left: '50%',
        top: '50%',
        transform: 'translate(-50%, -50%)',
        width: `${Math.min(bubbleWidth, window.innerWidth - 32)}px`,
        zIndex: 9999
      },
      placement: 'none',
      arrowOffset: 50
    }
  }

  const vw = window.innerWidth
  const vh = window.innerHeight
  const targetCenterX = rect.x + rect.width / 2

  const actualWidth = Math.min(bubbleWidth, vw - 32)
  let left = targetCenterX - actualWidth / 2
  left = Math.max(16, Math.min(left, vw - actualWidth - 16))

  const spaceAbove = rect.y
  const spaceBelow = vh - (rect.y + rect.height)

  let placement = 'top'
  let style = {
    position: 'fixed',
    left: `${left}px`,
    width: `${actualWidth}px`,
    zIndex: 9999
  }

  // If there's room above the element, place bubble above it
  if (spaceAbove >= bubbleHeight + 20) {
    placement = 'top'
    style.bottom = `${vh - rect.y + 12}px`
  } else if (spaceBelow >= bubbleHeight + 20) {
    placement = 'bottom'
    style.top = `${rect.y + rect.height + 12}px`
  } else {
    if (spaceAbove >= spaceBelow) {
      placement = 'top'
      style.top = `${Math.max(16, rect.y - bubbleHeight - 12)}px`
    } else {
      placement = 'bottom'
      style.top = `${Math.min(vh - bubbleHeight - 16, rect.y + rect.height + 12)}px`
    }
  }

  // Arrow offset relative to bubble container
  const arrowOffset = Math.max(24, Math.min(targetCenterX - left, actualWidth - 24))

  return {
    style,
    placement,
    arrowOffset
  }
})

function nextBfStep() {
  if (bfCurrentStep.value < 5) {
    bfCurrentStep.value++
    nextTick(updateBfSpotlight)
  }
}

function prevBfStep() {
  if (bfCurrentStep.value > 1) {
    bfCurrentStep.value--
    nextTick(updateBfSpotlight)
  }
}

function skipToPracticeTrial() {
  bfGuideActive.value = false
  bfGuideDismissed.value = true
  bfSpotlightRect.value = null
  if (bfOpeningSafetyTimeout) {
    clearTimeout(bfOpeningSafetyTimeout)
    bfOpeningSafetyTimeout = null
  }
  toast.info('Defeat Cognito Bot to finish your training!')
}

function onOpeningComplete() {
  if (bfOpeningSafetyTimeout) {
    clearTimeout(bfOpeningSafetyTimeout)
    bfOpeningSafetyTimeout = null
  }
  if (phase.value === 'battle' && !battleComplete.value && !bfGuideDismissed.value) {
    bfGuideActive.value = true
    bfCurrentStep.value = 1
    nextTick(() => {
      setTimeout(updateBfSpotlight, 350)
    })
  }
}

function startBattlefieldTutorial() {
  phase.value = 'battle'
  battleComplete.value = false
  battleResult.value = null
  currentQ.value = 0
  const max = Number(healthStore.maxHp || auth.selectedCharacter?.health || (charClass.value === 'MELEE' ? 200 : (charClass.value === 'RANGE' ? 110 : 150)))
  playerMaxHP.value = max
  playerHP.value = max
  enemyHP.value = enemyMaxHP.value
  answered.value = false
  selectedAnswer.value = null
  lastCorrect.value = false
  lastExplanation.value = ''

  // Only display guides AFTER entrance/opening animation completes
  bfGuideActive.value = false
  bfGuideDismissed.value = false
  bfCurrentStep.value = 1
  bfSpotlightRect.value = null

  if (bfOpeningSafetyTimeout) clearTimeout(bfOpeningSafetyTimeout)
  bfOpeningSafetyTimeout = setTimeout(() => {
    if (phase.value === 'battle' && !battleComplete.value && !bfGuideActive.value && !bfGuideDismissed.value) {
      onOpeningComplete()
    }
  }, 11000)
}

function handleBattleAnswer({ key, isCritical, resolve }) {
  const q = currentQuestion.value
  const isCorr = String(key || '').trim().toUpperCase() === String(q?.correct_answer || '').trim().toUpperCase()
  const dmg = isCorr ? (isCritical ? 20 : 10) : 0
  const selfDmg = !isCorr ? (isCritical ? 30 : 15) : 0

  const nextEnemyHp = Math.max(0, enemyHP.value - dmg)
  const nextPlayerHp = Math.max(0, playerHP.value - selfDmg)

  const serverResult = {
    success: true,
    correct: isCorr,
    is_correct: isCorr,
    critical: isCritical,
    critical_active: isCritical,
    damage: isCorr ? dmg : selfDmg,
    damage_dealt: dmg,
    player_damage_taken: selfDmg,
    player_hp: nextPlayerHp,
    player_current_health: nextPlayerHp,
    player_max_health: playerMaxHP.value,
    enemy_hp: nextEnemyHp,
    enemy_current_health: nextEnemyHp,
    enemy_max_health: enemyMaxHP.value,
    damage_type: isCorr ? (isCritical ? 'critical' : 'normal') : (isCritical ? 'critical_backfire' : 'normal_damage'),
    correct_answer: q?.correct_answer,
    explanation: q?.explanation
  }

  if (resolve) {
    resolve(serverResult)
  }
}

function onBattleAnswered(serverRes) {
  answered.value = true
  lastCorrect.value = Boolean(serverRes?.is_correct)
  lastExplanation.value = serverRes?.explanation || ''
  selectedAnswer.value = serverRes?.is_correct ? serverRes?.correct_answer : null

  if (serverRes) {
    if (serverRes.player_current_health !== undefined) playerHP.value = Number(serverRes.player_current_health)
    if (serverRes.enemy_current_health !== undefined) enemyHP.value = Number(serverRes.enemy_current_health)
    dmgAmount.value = Number(serverRes.damage_dealt || 0)
    playerDmgAmount.value = Number(serverRes.player_damage_taken || 0)
  }

  if (enemyHP.value <= 0) {
    battleComplete.value = true
    battleResult.value = 'victory'
  } else if (playerHP.value <= 0) {
    // Tutorial safety: restore player HP
    setTimeout(() => {
      playerHP.value = playerMaxHP.value
      toast.info('Your health has been restored! Try again, Scholar.')
    }, 3800)
  }
}

function nextQuestion() {
  if (currentQ.value < tutorialQuestions.length - 1) {
    currentQ.value++
    answered.value = false
    selectedAnswer.value = null
    lastExplanation.value = ''
  } else {
    battleComplete.value = true
    battleResult.value = 'victory'
  }
}

// Proceed to Phase 2: Guided Module Tour starting with Study Hall (Section 4.G)
async function proceedToModuleTour() {
  loading.value = true
  try {
    await tutorialStore.completeBattlefieldPhase()
  } catch (err) {
    console.error('Error proceeding to module tour:', err)
  } finally {
    loading.value = false
  }
}

// Skip entire onboarding
async function handleSkipAll() {
  loading.value = true
  showSkipModal.value = false
  await tutorialStore.skipTutorial()
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

onMounted(() => {
  window.addEventListener('resize', updateBfSpotlight)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateBfSpotlight)
  if (bfOpeningSafetyTimeout) {
    clearTimeout(bfOpeningSafetyTimeout)
    bfOpeningSafetyTimeout = null
  }
})
</script>

<style scoped>
.tutorial-screen {
  min-height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  padding: 1.5rem 1rem 2rem;
  overflow-x: hidden;
  color: #e2d9cc;
}

.tutorial-screen.is-battle-active {
  padding: 0.5rem 0.5rem 1rem;
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

/* ── Top Bar ─────────────────────────────── */
.tut-top-bar {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 1100px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.tut-phase-badge {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  color: #fbbf24;
  padding: 0.3rem 0.75rem;
  background: rgba(251, 191, 36, 0.08);
  border: 1px solid rgba(251, 191, 36, 0.3);
  border-radius: 3px;
}

.btn-skip-full-tutorial {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  font-size: 0.72rem;
  letter-spacing: 0.05em;
  padding: 0.35rem 0.75rem;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-skip-full-tutorial:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  border-color: #ef4444;
}

.tut-content {
  position: relative; z-index: 2;
  width: 100%; max-width: 640px;
  margin: 0 auto;
  transition: max-width 0.3s ease;
}

.tut-content.is-battle {
  max-width: 1100px;
}

/* ── Intro panel ─────────────────── */
.tut-panel {
  padding: 2.25rem 2rem;
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

/* ── Battle wrapper ──────────────── */
.tut-battle-wrapper {
  width: 100%;
  position: relative;
}

/* ── Interactive Speech Bubble Overlay ── */
.bf-guide-overlay {
  position: fixed;
  inset: 0;
  z-index: 9990;
  pointer-events: none;
}

.bf-target-glow {
  position: fixed;
  border-radius: 6px;
  box-shadow: 0 0 0 3px #fbbf24, 0 0 25px rgba(251, 191, 36, 0.7);
  pointer-events: none;
  z-index: 9992;
}

/* ── Speech Chat Bubble ─────────── */
.bf-speech-bubble {
  position: fixed;
  z-index: 9995;
  pointer-events: auto;
  padding: 1rem 1.15rem;
  background: radial-gradient(circle at center, #1b162f 0%, #0b0916 100%);
  border: 2px solid #fbbf24;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85), 0 0 20px rgba(251, 191, 36, 0.3);
  border-radius: 8px;
  color: #f3ede2;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

/* Arrow Pointer Tail */
.bubble-tail {
  position: absolute;
  width: 0;
  height: 0;
  transform: translateX(-50%);
  pointer-events: none;
}

/* When bubble is ABOVE target, arrow points DOWN */
.bubble-tail.tail-top {
  bottom: -11px;
  border-left: 9px solid transparent;
  border-right: 9px solid transparent;
  border-top: 11px solid #141026;
  filter: drop-shadow(0 2px 2px rgba(251, 191, 36, 0.5));
}
.bubble-tail.tail-top::before {
  content: '';
  position: absolute;
  bottom: 0px;
  left: -11px;
  border-left: 11px solid transparent;
  border-right: 11px solid transparent;
  border-top: 13px solid #fbbf24;
  z-index: -1;
}

/* When bubble is BELOW target, arrow points UP */
.bubble-tail.tail-bottom {
  top: -11px;
  border-left: 9px solid transparent;
  border-right: 9px solid transparent;
  border-bottom: 11px solid #141026;
  filter: drop-shadow(0 -2px 2px rgba(251, 191, 36, 0.5));
}
.bubble-tail.tail-bottom::before {
  content: '';
  position: absolute;
  top: 0px;
  left: -11px;
  border-left: 11px solid transparent;
  border-right: 11px solid transparent;
  border-bottom: 13px solid #fbbf24;
  z-index: -1;
}

.bubble-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(251, 191, 36, 0.2);
  padding-bottom: 0.35rem;
}

.bubble-step-badge {
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  color: #fbbf24;
}

.bubble-close-btn {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 0.85rem;
  cursor: pointer;
  padding: 0 0.25rem;
  line-height: 1;
}
.bubble-close-btn:hover { color: #f87171; }

.bubble-title {
  font-size: 0.95rem;
  margin: 0;
  line-height: 1.25;
}

.bubble-desc {
  font-size: 0.84rem;
  line-height: 1.45;
  color: #e5e7eb;
  margin: 0;
}

.bubble-footer-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 0.5rem;
  margin-top: 0.15rem;
}

.bubble-spacer { width: 50px; }

.btn-bubble-ghost {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #d1d5db;
  padding: 0.25rem 0.55rem;
  font-size: 0.68rem;
  border-radius: 3px;
  cursor: pointer;
}
.btn-bubble-ghost:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.btn-bubble-gold {
  background: #fbbf24;
  color: #0b0916;
  font-weight: 700;
  border: 1px solid #f59e0b;
  padding: 0.25rem 0.65rem;
  font-size: 0.68rem;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
}
.btn-bubble-gold:hover { background: #f59e0b; color: #000; }

.bubble-dots { display: flex; gap: 4px; }
.b-dot {
  width: 6px; height: 6px;
  border-radius: 1px;
  background: rgba(255, 255, 255, 0.2);
}
.b-dot.active {
  background: #fbbf24;
  transform: scale(1.2);
  box-shadow: 0 0 5px rgba(251, 191, 36, 0.8);
}

/* ── Simple Victory Card ─────────── */
.battle-victory-simple {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.75rem;
  text-align: center;
  background: radial-gradient(circle at center, rgba(30, 20, 50, 0.98) 0%, rgba(10, 8, 16, 0.99) 100%);
  border: 2px solid rgba(234, 179, 8, 0.8);
  box-shadow: 0 0 35px rgba(234, 179, 8, 0.35);
  border-radius: 6px;
  gap: 1rem;
  margin: 3rem auto;
  max-width: 480px;
}

.victory-trophy { font-size: 3rem; }

.victory-title {
  font-size: 1.2rem;
  letter-spacing: 0.08em;
  margin: 0;
}

.victory-desc {
  font-size: 0.92rem;
  color: #d1d5db;
  line-height: 1.5;
  margin: 0;
}

/* ── Skip Modal ──────────────────── */
.tut-skip-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(4, 3, 8, 0.88);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.tut-skip-card {
  width: 100%;
  max-width: 480px;
  padding: 1.75rem;
  text-align: center;
  background: radial-gradient(circle at center, #1c1530 0%, #0a0715 100%);
  border: 2px solid rgba(251, 191, 36, 0.8);
  border-radius: 6px;
  box-shadow: 0 0 35px rgba(234, 179, 8, 0.35);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.1rem;
}

.skip-skull { font-size: 2.4rem; }

.skip-title { font-size: 1.25rem; margin: 0; }

.skip-desc {
  font-size: 0.92rem;
  line-height: 1.5;
  color: #e5e7eb;
  margin: 0;
}

.skip-actions {
  display: flex;
  gap: 1rem;
  width: 100%;
  justify-content: center;
}

@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
</style>
