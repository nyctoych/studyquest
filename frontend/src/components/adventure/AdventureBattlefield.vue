<template>
  <div class="adventure-battlefield" :class="{ 'is-boss-encounter': isBoss }">
    <!-- Screen Flash on Impact -->
    <div v-if="screenFlash" class="battle-screen-flash" :class="flashType"></div>

    <!-- ══════════════════════════════════════════════════════════════
         ZONE 1: BATTLEFIELD / ENEMY & PLAYER COMBAT AREA (UPPER)
         Visually derived from the current Chapter's Adventure Map
         ══════════════════════════════════════════════════════════════ -->
    <div class="battlefield-stage">
      <BattlefieldEnvironment
        :environment="currentEnvironment"
        :is-boss="isBoss"
      >
        <!-- Minimal Top Battle HUD (Chapter, Part, Inquiry Index, NO PLAYER HP!) -->
        <div class="stage-minimal-hud">
          <div class="hud-left">
            <span class="hud-chapter-badge">CH. {{ chapterId }}</span>
            <span class="hud-encounter-title">{{ isBoss ? 'FINAL GUARDIAN TRIAL' : partTitle }}</span>
            <span v-if="currentEnvironment?.name" class="hud-env-tag">{{ currentEnvironment.name }}</span>
          </div>
          <div class="hud-right">
            <span class="hud-inquiry-tag">
              INQUIRY {{ currentQIndex + 1 }} / {{ questions.length }}
            </span>
          </div>
        </div>

        <!-- Combat Arena: Enemy (Top-Right) & Player (Bottom-Left) -->
        <div class="combat-arena">
          <!-- Enemy Area: Upper-Right / Right Side -->
          <div class="combatant-slot slot-enemy" :style="enemySlotStyles">
            <!-- Boss Battle Sprite (Actual Boss Assets from bossMapping.js) -->
            <BossBattleSprite
              v-if="isBoss"
              :boss-meta="bossMeta"
              :boss-name="enemyName"
              :boss-title="enemyTitle"
              :battle-state="enemyBattleState"
              :speech-text="currentEnemySpeech"
              :boss-hp-pct="bossHpPct"
              :is-enraged="isBossEnraged"
            />

            <!-- Normal Minion Battle Sprite Placeholder -->
            <MinionBattleSprite
              v-else
              :enemy-name="enemyName"
              :enemy-title="enemyTitle"
              :battle-state="enemyBattleState"
              :speech-text="currentEnemySpeech"
              :enemy-hp-pct="bossHpPct"
            />
          </div>

          <!-- Center Combat FX Overlay (Slashes, Arcane Sparks) -->
          <div v-if="showAttackSlash" class="arena-slash-fx animate-slash-cut">
            <div class="slash-blade"></div>
            <div class="slash-glow"></div>
          </div>

          <!-- Player Area: Lower-Left / Lower-Middle Side -->
          <div class="combatant-slot slot-player" :style="playerSlotStyles">
            <PlayerBattleSprite
              :player-name="auth.nickname || auth.user?.username || 'Scholar Adventurer'"
              :battle-state="playerBattleState"
            />
          </div>
        </div>
      </BattlefieldEnvironment>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         LOWER INTERACTIVE COMBAT ZONES:
         ZONE 2: QUESTION PANEL (BOTTOM-LEFT)
         ZONE 3: ANSWER ACTION CHOICES (BOTTOM-RIGHT)
         ══════════════════════════════════════════════════════════════ -->
    <div class="battlefield-controls-split">
      <!-- Zone 2: Question Panel (Bottom-Left) -->
      <div class="controls-left-question">
        <BattleQuestionPanel
          v-if="currentQ"
          :question-text="currentQ.question"
          :question-index="currentQIndex"
          :total-questions="questions.length"
          :difficulty="currentQ.difficulty || 'standard'"
          :answered="answered"
          :is-correct="lastCorrect"
          :explanation="currentQ.explanation"
          :is-last-question="isLastQuestion"
          @next="onNextQuestion"
        />
      </div>

      <!-- Zone 3: Answer Choices / Actions (Bottom-Right) -->
      <div class="controls-right-actions">
        <BattleChoicePanel
          v-if="currentQ"
          :choices="currentQ.choices"
          :selected-key="selectedKey"
          :correct-answer="currentQ.correct_answer || currentQ.correct"
          :answered="answered"
          :disabled="isProcessingAnswer"
          @select="onSelectChoice"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { bgmService } from '../../services/bgmService'
import { battleAudio } from '../../services/battleAudio'
import { getRandomWrongSpeech } from '../../config/battleAssets'
import { resolveBattlefieldEnvironment, preloadBattlefieldAssets } from '../../config/battlefieldEnvironments'
import BattlefieldEnvironment from './BattlefieldEnvironment.vue'
import PlayerBattleSprite from './PlayerBattleSprite.vue'
import MinionBattleSprite from './MinionBattleSprite.vue'
import BossBattleSprite from './BossBattleSprite.vue'
import BattleQuestionPanel from './BattleQuestionPanel.vue'
import BattleChoicePanel from './BattleChoicePanel.vue'

onMounted(() => {
  bgmService.setBattlefieldActive(true)
})

onUnmounted(() => {
  bgmService.setBattlefieldActive(false)
})

const props = defineProps({
  isBoss: {
    type: Boolean,
    default: false
  },
  chapterId: {
    type: Number,
    default: 1
  },
  partNum: {
    type: [Number, String],
    default: 1
  },
  chapterTitle: {
    type: String,
    default: ''
  },
  partTitle: {
    type: String,
    default: ''
  },
  enemyName: {
    type: String,
    default: 'Fracture Specter'
  },
  enemyTitle: {
    type: String,
    default: 'Cognitive Aberration'
  },
  bossMeta: {
    type: Object,
    default: () => ({})
  },
  bossHp: {
    type: Number,
    default: 100
  },
  bossMaxHp: {
    type: Number,
    default: 100
  },
  questions: {
    type: Array,
    required: true
  },
  currentQIndex: {
    type: Number,
    default: 0
  },
  currentQ: {
    type: Object,
    default: null
  },
  answered: {
    type: Boolean,
    default: false
  },
  selectedKey: {
    type: String,
    default: null
  },
  lastCorrect: {
    type: Boolean,
    default: false
  },
  isLastQuestion: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['answer', 'next'])

const auth = useAuthStore()
const game = useGameStore()

// ══════════════════════════════════════════════════════════════
// CENTRALIZED ENVIRONMENT RESOLUTION (Chapter -> Part -> Fallback)
// ══════════════════════════════════════════════════════════════
const currentEnvironment = computed(() => {
  return resolveBattlefieldEnvironment(props.chapterId, props.partNum, props.isBoss)
})

watch(() => [props.chapterId, props.partNum, props.isBoss], () => {
  preloadBattlefieldAssets(props.chapterId, props.partNum)
}, { immediate: true })

// Responsive environmental positioning for combatant sprites
const playerSlotStyles = computed(() => {
  const pos = currentEnvironment.value?.playerPosition || { x: 18, y: 70 }
  const scale = currentEnvironment.value?.playerScale || 1.0
  return {
    left: `clamp(1.5rem, ${pos.x}%, 14rem)`,
    bottom: '48px',
    '--player-scale': scale
  }
})

const enemySlotStyles = computed(() => {
  const pos = currentEnvironment.value?.enemyPosition || { x: 74, y: 26 }
  const scale = currentEnvironment.value?.enemyScale || 1.0
  return {
    right: `clamp(1.5rem, ${100 - pos.x}%, 14rem)`,
    top: `${pos.y}px`,
    '--enemy-scale': scale
  }
})

// Battle States
const playerBattleState = ref('idle') // 'idle' | 'attacking' | 'recoil'
const enemyBattleState = ref('idle')  // 'idle' | 'hit' | 'laughing'
const currentEnemySpeech = ref('')
const isProcessingAnswer = ref(false)
const showAttackSlash = ref(false)
const screenFlash = ref(false)
const flashType = ref('correct')

// Boss 50% HP Enraged Mode calculation
const hasBossEnteredEnraged = ref(false)

const bossHpPct = computed(() => {
  if (props.bossMaxHp > 0) {
    return Math.max(0, Math.min(100, (props.bossHp / props.bossMaxHp) * 100))
  }
  return 100
})

watch(bossHpPct, (pct) => {
  if (pct <= 50) {
    hasBossEnteredEnraged.value = true
  }
}, { immediate: true })

const isBossEnraged = computed(() => {
  return props.isBoss && (hasBossEnteredEnraged.value || bossHpPct.value <= 50)
})

// Trigger enrage sound once when entering 50% HP threshold
const hasPlayedEnrageSfx = ref(false)
watch(isBossEnraged, (enraged) => {
  if (enraged && !hasPlayedEnrageSfx.value) {
    hasPlayedEnrageSfx.value = true
    if (game.audioEnabled) {
      battleAudio.playEnraged()
    }
  }
}, { immediate: true })

// Reset states when moving to next inquiry
watch(() => props.currentQIndex, () => {
  playerBattleState.value = 'idle'
  enemyBattleState.value = 'idle'
  currentEnemySpeech.value = ''
  isProcessingAnswer.value = false
  showAttackSlash.value = false
  screenFlash.value = false
})

function onSelectChoice(key) {
  if (props.answered || isProcessingAnswer.value) return
  isProcessingAnswer.value = true

  // Play selection SFX
  if (game.audioEnabled) {
    battleAudio.playSelect()
  }

  const correct = props.currentQ.correct_answer || props.currentQ.correct
  const isCorrect = key === correct

  if (isCorrect) {
    // ══════════════════════════════════════
    // CORRECT ANSWER: KNOWLEDGE ATTACK
    // ══════════════════════════════════════
    playerBattleState.value = 'attacking'
    enemyBattleState.value = 'hit'
    showAttackSlash.value = true
    flashType.value = 'correct'
    screenFlash.value = true

    if (game.audioEnabled) {
      battleAudio.playAttack()
      setTimeout(() => {
        battleAudio.playHit()
      }, 150)
    }

    setTimeout(() => {
      screenFlash.value = false
      showAttackSlash.value = false
    }, 450)

    setTimeout(() => {
      playerBattleState.value = 'idle'
      enemyBattleState.value = 'idle'
      isProcessingAnswer.value = false
    }, 900)

  } else {
    // ══════════════════════════════════════
    // WRONG ANSWER: ENEMY LAUGH & MOCKERY
    // (PLAYER TAKES ZERO DAMAGE)
    // ══════════════════════════════════════
    playerBattleState.value = 'recoil'
    enemyBattleState.value = 'laughing'
    flashType.value = 'wrong'
    screenFlash.value = true

    // Retrieve randomized game-like mocking line
    currentEnemySpeech.value = getRandomWrongSpeech(props.chapterId, props.isBoss)

    if (game.audioEnabled) {
      battleAudio.playMock()
    }

    setTimeout(() => {
      screenFlash.value = false
    }, 350)

    setTimeout(() => {
      playerBattleState.value = 'idle'
    }, 800)

    // Keep speech bubble visible for player to read, then dismiss
    setTimeout(() => {
      enemyBattleState.value = 'idle'
      isProcessingAnswer.value = false
    }, 1400)
  }

  // Forward event to parent for score recording
  emit('answer', key)
}

function onNextQuestion() {
  currentEnemySpeech.value = ''
  emit('next')
}
</script>

<style scoped>
.adventure-battlefield {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  margin: 0;
  min-height: calc(100vh - 70px);
  height: auto;
  gap: 0.65rem;
  padding: 0.25rem 0.5rem 1.5rem;
  position: relative;
  box-sizing: border-box;
}

/* Screen Flash overlay */
.battle-screen-flash {
  position: fixed;
  inset: 0;
  z-index: 100;
  pointer-events: none;
  animation: flash-fade 0.35s ease-out forwards;
}

.battle-screen-flash.correct {
  background: radial-gradient(circle, rgba(52, 211, 153, 0.3) 0%, transparent 75%);
}

.battle-screen-flash.wrong {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.35) 0%, transparent 75%);
}

@keyframes flash-fade {
  0% { opacity: 1; }
  100% { opacity: 0; }
}

/* ══════════════════════════════════════════════════════════════
   ZONE 1: BATTLEFIELD / ENEMY AREA (UPPER)
   ══════════════════════════════════════════════════════════════ */
.battlefield-stage {
  position: relative;
  width: 100%;
  min-height: 400px;
  background: #090514;
  border: 2px solid rgba(168, 85, 247, 0.45);
  border-radius: 10px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.75), inset 0 0 40px rgba(76, 29, 149, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.is-boss-encounter .battlefield-stage {
  min-height: 440px;
}

/* Minimal Top HUD */
.stage-minimal-hud {
  position: relative;
  z-index: 20;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.65rem 1rem;
  background: rgba(10, 6, 20, 0.88);
  border-bottom: 1px solid rgba(139, 92, 246, 0.3);
  backdrop-filter: blur(6px);
}

.hud-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.hud-chapter-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  background: #7c3aed;
  color: #fff;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  box-shadow: 0 0 8px rgba(124, 58, 237, 0.5);
}

.hud-encounter-title {
  font-family: 'Cinzel', serif;
  font-size: 0.92rem;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: 0.5px;
}

.hud-env-tag {
  font-family: 'Cinzel', serif;
  font-size: 0.74rem;
  color: #c084fc;
  border-left: 1px solid rgba(168, 85, 247, 0.45);
  padding-left: 0.6rem;
  letter-spacing: 0.5px;
}

.hud-inquiry-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.35);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

/* Combat Arena: Positioning Pokemon-Style (Enemy Top-Right, Player Bottom-Left) */
.combat-arena {
  position: relative;
  flex: 1;
  display: flex;
  width: 100%;
  min-height: 350px;
  padding-bottom: 2rem;
  overflow: visible;
}

.is-boss-encounter .combat-arena {
  min-height: 390px;
  padding-bottom: 3rem;
}

/* Enemy in Upper-Right / Right Side */
.slot-enemy {
  position: absolute;
  top: 25px;
  right: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--enemy-scale, 1));
  transform-origin: top center;
  z-index: 25;
}

.is-boss-encounter .slot-enemy {
  top: 15px;
}

/* Player in Lower-Left / Lower-Middle */
.slot-player {
  position: absolute;
  bottom: 48px;
  left: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--player-scale, 1));
  transform-origin: bottom center;
  z-index: 12;
}

/* Center Combat Slash Effect */
.arena-slash-fx {
  position: absolute;
  top: 25%;
  right: clamp(6rem, 20vw, 20rem);
  width: 180px;
  height: 180px;
  pointer-events: none;
  z-index: 30;
}

.slash-blade {
  position: absolute;
  top: 50%;
  left: -20%;
  width: 140%;
  height: 4px;
  background: #ffffff;
  box-shadow: 0 0 16px #38bdf8, 0 0 30px #a855f7;
  transform: rotate(-35deg);
  border-radius: 2px;
}

.slash-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(56, 189, 248, 0.4) 0%, transparent 65%);
}

.animate-slash-cut {
  animation: slash-surge 0.4s ease-out forwards;
}

@keyframes slash-surge {
  0% { transform: scale(0.2) rotate(-15deg); opacity: 0; }
  40% { transform: scale(1.1) rotate(0deg); opacity: 1; }
  100% { transform: scale(1.4) rotate(10deg); opacity: 0; }
}

/* ══════════════════════════════════════════════════════════════
   ZONES 2 & 3: SPLIT BOTTOM CONTROLS
   QUESTION (LEFT) | ANSWER CHOICES (RIGHT)
   ══════════════════════════════════════════════════════════════ */
.battlefield-controls-split {
  display: grid;
  grid-template-columns: 46% 54%;
  gap: 0.65rem;
  width: 100%;
  height: 260px;
  min-height: 220px;
  flex-shrink: 0;
}

.controls-left-question {
  height: 100%;
}

.controls-right-actions {
  height: 100%;
}

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE DESIGN (TABLET & MOBILE)
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 860px) {
  .battlefield-controls-split {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .slot-enemy {
    right: 2%;
    top: 10px;
    transform: scale(0.9);
  }

  .slot-player {
    left: 2%;
    bottom: 45px;
    transform: scale(0.9);
  }
}

@media (max-width: 580px) {
  .battlefield-stage {
    min-height: 300px;
  }

  .slot-enemy {
    right: -10px;
    top: 5px;
    transform: scale(0.8);
  }

  .slot-player {
    left: -10px;
    bottom: 35px;
    transform: scale(0.8);
  }

  .hud-encounter-title {
    font-size: 0.8rem;
  }
}
</style>
