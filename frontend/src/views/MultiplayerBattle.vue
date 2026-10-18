<template>
  <div class="adventure-battlefield arena-battlefield-wrapper">
    <!-- Screen Flash on Impact -->
    <div v-if="screenFlash" class="battle-screen-flash" :class="flashType"></div>

    <!-- ══════════════════════════════════════════════════════════════
         ZONE 1: BATTLEFIELD STAGE (ARENA COLOSSEUM)
         Uses Adventure Gate BattlefieldEnvironment and Layout
         ══════════════════════════════════════════════════════════════ -->
    <div class="battlefield-stage">
      <BattlefieldEnvironment
        :environment="currentEnvironment"
        :is-boss="false"
      >
        <!-- Minimal Top Battle HUD: Arena Match Stats, Timer & Pot -->
        <div class="stage-minimal-hud">
          <div class="hud-left">
            <span class="hud-chapter-badge">ARENA DUEL</span>
            <span class="hud-encounter-title">
              {{ battle.currentQuestion?.category_name || 'PSYCHOLOGICAL DUEL' }}
            </span>
            <span class="hud-env-tag">{{ battle.battleMode.toUpperCase() }}</span>
            <span class="wager-hud-badge font-pixel">
              🪙 POT: {{ (battle.pot || (battle.coinBet ? battle.coinBet * 2 : 200)).toLocaleString() }}
            </span>
          </div>
          <div class="hud-right">
            <span
              class="timer-badge font-pixel"
              :class="{ urgent: battle.timeRemaining <= 5 }"
            >
              ⏱️ {{ battle.timeRemaining }}s
            </span>
            <span class="hud-inquiry-tag">
              QUESTION {{ battle.questionIndex + 1 }} / {{ battle.totalQuestions }}
            </span>
          </div>
        </div>

        <!-- Combat Arena: Opponent (Upper-Right) & Local Player (Lower-Left) -->
        <div class="combat-arena" ref="arenaRef">
          <!-- Opponent Area: Upper-Right Side -->
          <div
            class="combatant-slot slot-enemy slot-opponent"
            ref="opponentSlotRef"
            :style="opponentSlotStyles"
          >
            <PlayerBattleSprite
              :character="oppCharacter"
              :player-name="battle.opponent.username || 'Opponent'"
              :player-level="battle.opponent.level || 1"
              :attack-power="battle.opponent.damage || 10"
              :critical-attack-power="battle.opponent.crit_damage || 35"
              :battle-state="oppBattleState"
              :player-hp="battle.opponent.hp"
              :player-max-hp="battle.opponent.maxHp || 150"
              :damage-taken="floatingOppDmg"
              :damage-type="floatingOppDmgType"
              :is-opponent="true"
            />
          </div>

          <!-- Traveling Attack Effect Projectile Layer -->
          <div
            v-if="showAttackProjectile"
            class="arena-attack-projectile"
            :style="{
              left: `${projectileCoords.x}px`,
              top: `${projectileCoords.y}px`,
              transform: `translate(-50%, -50%) rotate(${projectileCoords.angle}deg)`
            }"
          >
            <img
              :src="activeProjectileGif"
              :alt="activeEffectName"
              class="projectile-sprite pixel-art"
              @error="handleProjectileError"
            />
          </div>

          <!-- Impact Splash Effect at Target -->
          <div
            v-if="showImpactSplash"
            class="arena-impact-splash"
            :class="{ 'is-crit-splash': wasLastCrit }"
            :style="{
              left: `${splashCoords.x}px`,
              top: `${splashCoords.y}px`
            }"
          >
            <img
              :src="activeSplashGif"
              :alt="activeSplashName"
              class="splash-sprite pixel-art"
              @error="handleSplashError"
            />
          </div>

          <!-- Center Combat FX Overlay (Slashes) -->
          <div
            v-if="showAttackSlash"
            class="arena-slash-fx animate-slash-cut"
            :class="{ 'is-crit-slash': wasLastCrit }"
          >
            <div class="slash-blade"></div>
            <div class="slash-glow"></div>
          </div>

          <!-- Local Player Area: Lower-Left Side -->
          <div
            class="combatant-slot slot-player"
            ref="playerSlotRef"
            :style="playerSlotStyles"
          >
            <PlayerBattleSprite
              :character="selfCharacter"
              :player-name="battle.self.username || 'You'"
              :player-level="battle.self.level || 1"
              :attack-power="battle.self.damage || 10"
              :critical-attack-power="battle.self.crit_damage || 35"
              :battle-state="selfBattleState"
              :player-hp="battle.self.hp"
              :player-max-hp="battle.self.maxHp || 150"
              :damage-taken="floatingSelfDmg"
              :damage-type="floatingSelfDmgType"
              :is-opponent="false"
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
          v-if="battle.currentQuestion"
          :question-text="battle.currentQuestion.question"
          :question-index="battle.questionIndex"
          :total-questions="battle.totalQuestions"
          :difficulty="battle.currentQuestion.difficulty || 'board_exam'"
          :answered="battle.answerSubmitted"
          :is-correct="battle.roundFeedback ? Boolean(battle.roundFeedback.selfCorrect) : false"
          :explanation="battle.roundFeedback ? battle.roundFeedback.explanation : ''"
          :is-last-question="battle.questionIndex + 1 >= battle.totalQuestions"
          :show-next-button="false"
        />
      </div>

      <!-- Zone 3: Answer Choices & Critical Mode (Bottom-Right) -->
      <div class="controls-right-actions">
        <BattleChoicePanel
          v-if="battle.currentQuestion"
          :choices="currentChoices"
          :selected-key="stagedChoice || battle.selectedAnswer"
          :correct-answer="battle.roundFeedback?.correctAnswer"
          :answered="battle.answerSubmitted"
          :disabled="battle.status !== 'in_progress' || battle.answerSubmitted"
          :is-critical-active="battlePrefs.criticalMode"
          :crit-damage="battle.self.crit_damage || 35"
          :base-damage="battle.self.damage || 10"
          :is-arena="true"
          @select="handleSelectChoice"
          @toggle-critical="battlePrefs.setCriticalMode(!battlePrefs.criticalMode)"
        />

        <!-- Manual Confirmation Action Bar for Arena -->
        <div
          v-if="stagedChoice && !battle.answerSubmitted && battle.status === 'in_progress'"
          class="confirm-stage-bar animate-fade-in"
        >
          <span class="staged-prompt font-pixel">STAGED DIAGNOSIS: [{{ stagedChoice.toUpperCase() }}]</span>
          <button
            type="button"
            class="btn-confirm-action font-pixel"
            @click="confirmStagedAnswer"
          >
            CONFIRM DIAGNOSIS ⚔️
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         READY SCREEN OVERLAY (BEFORE BATTLE STARTS)
         ══════════════════════════════════════════════════════════════ -->
    <div v-if="battle.status === 'waiting' || battle.status === 'starting'" class="ready-overlay">
      <div class="ready-card">
        <h2>⚔️ Board Exam Psychology Duel</h2>
        <p>Both candidates must confirm readiness to initialize the duel.</p>

        <div class="ready-status-row">
          <div class="ready-pill" :class="{ confirmed: battle.self.isReady }">
            <AvatarImg :size="28" />
            <span>{{ battle.self.username || 'You' }}: {{ battle.self.isReady ? 'READY ✓' : 'WAITING…' }}</span>
          </div>
          <div class="ready-pill" :class="{ confirmed: battle.opponent.isReady }">
            <AvatarImg
              :character="battle.opponent.character_class || battle.opponent.class_type"
              :player="battle.opponent"
              :size="28"
            />
            <span>{{ battle.opponent.username || 'Opponent' }}: {{ battle.opponent.isReady ? 'READY ✓' : 'WAITING…' }}</span>
          </div>
        </div>

        <button
          v-if="!battle.self.isReady"
          class="btn-ready font-cinzel"
          @click="battle.setReady()"
        >
          I'm Ready!
        </button>
        <div v-else class="waiting-prompt font-pixel">
          Waiting for {{ battle.opponent.username || 'Opponent' }} to ready up…
        </div>

        <div v-if="battle.status === 'starting'" class="countdown-banner font-pixel">
          Battle begins in {{ battle.timeRemaining }}…
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         GAME OVER / VICTORY / DEFEAT MODAL
         ══════════════════════════════════════════════════════════════ -->
    <div v-if="battle.status === 'ended' && battle.battleResult" class="game-over-modal">
      <div class="modal-card" :class="battleOutcome">
        <div class="outcome-icon">
          {{ battleOutcome === 'won' ? '👑' : battleOutcome === 'lost' ? '💀' : '🤝' }}
        </div>
        <h2 class="outcome-title font-cinzel">
          {{ battleOutcome === 'won' ? 'VICTORY!' : battleOutcome === 'lost' ? 'DEFEAT' : 'STALEMATE (DRAW)' }}
        </h2>
        <p class="outcome-subtitle">
          {{ battleOutcome === 'won' ? 'Outstanding psychiatric mastery!' : 'Review your clinical concepts and try again.' }}
        </p>

        <div class="rewards-box">
          <div class="reward-row wager-info-row">
            <span>Ranked Bet & Pot:</span>
            <strong class="gold">
              {{ (battle.coinBet || 100).toLocaleString() }} Bet / {{ (battle.pot || (battle.coinBet ? battle.coinBet * 2 : 200)).toLocaleString() }} Pot
            </strong>
          </div>
          <div class="reward-row" v-if="playerRewards">
            <span>Battle Rating (ELO):</span>
            <strong :class="playerRewards.ratingChange >= 0 ? 'pos' : 'neg'">
              {{ playerRewards.ratingChange >= 0 ? '+' : '' }}{{ playerRewards.ratingChange }}
            </strong>
          </div>
          <div class="reward-row" v-if="playerRewards">
            <span>Coin Wager Result:</span>
            <strong :class="playerRewards.coinsEarned > 0 ? 'pos gold' : playerRewards.coinsEarned < 0 ? 'neg' : 'gold'">
              {{ playerRewards.coinsEarned > 0 ? '+' : '' }}{{ playerRewards.coinsEarned }} <CoinIcon :size="15" />
            </strong>
          </div>
          <div class="reward-row" v-if="playerRewards">
            <span>Experience XP:</span>
            <strong class="xp">+{{ playerRewards.xpEarned }} XP</strong>
          </div>

          <div class="elo-disclaimer-note font-pixel">
            ⚖️ ELO rating change is calculated solely from standard rating formula (independent of coin bet).
          </div>
        </div>

        <button class="btn-return font-cinzel" @click="returnToHub">
          Return to Arena Hub
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useBattleStore } from '../stores/battle'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import { useBattlefieldPreferencesStore } from '../stores/battlefieldPreferences'
import { bgmService } from '../services/bgmService'
import { battleAudio } from '../services/battleAudio'
import { resolveAsset } from '../services/bossMapping'
import {
  resolveEquippedAttackEffect,
  resolveEquippedSplashEffect,
  getCharacterDefaultAttackEffect,
  getDefaultSplashEffect
} from '../config/cosmeticEffects'

import BattlefieldEnvironment from '../components/adventure/BattlefieldEnvironment.vue'
import PlayerBattleSprite from '../components/adventure/PlayerBattleSprite.vue'
import BattleQuestionPanel from '../components/adventure/BattleQuestionPanel.vue'
import BattleChoicePanel from '../components/adventure/BattleChoicePanel.vue'
import AvatarImg from '../components/AvatarImg.vue'
import CoinIcon from '../components/common/CoinIcon.vue'

const router = useRouter()
const battle = useBattleStore()
const auth = useAuthStore()
const game = useGameStore()
const battlePrefs = useBattlefieldPreferencesStore()

// Staged answer choice for manual confirmation
const stagedChoice = ref(null)

// Animation & Combat state
const arenaRef = ref(null)
const playerSlotRef = ref(null)
const opponentSlotRef = ref(null)

const selfBattleState = ref('idle')
const oppBattleState = ref('idle')
const floatingSelfDmg = ref(0)
const floatingSelfDmgType = ref('damage')
const floatingOppDmg = ref(0)
const floatingOppDmgType = ref('damage')

// Projectile & Splash states
const showAttackProjectile = ref(false)
const showImpactSplash = ref(false)
const showAttackSlash = ref(false)
const screenFlash = ref(false)
const flashType = ref('correct')
const wasLastCrit = ref(false)

const projectileCoords = ref({ x: 0, y: 0, angle: 0, progress: 0 })
const splashCoords = ref({ x: 0, y: 0 })
const activeProjectileGif = ref('')
const activeSplashGif = ref('')
const activeEffectName = ref('Attack Effect')
const activeSplashName = ref('Splash Effect')

// Track processed combat action attempt IDs to prevent duplicate playback
const processedAttemptIds = new Set()

// Environment configuration for the Arena duel
const currentEnvironment = computed(() => {
  return {
    environmentId: 'arena_mindrealm_colosseum',
    name: 'Mindrealm Arena Duel',
    backgroundAsset: '/assets/adventure/battlefields/chapter-01-outdoor.jpg',
    groundType: 'stone_arena',
    lighting: 'dusk_purple',
    weather: 'clear',
    playerPosition: { x: 18, y: 70 },
    enemyPosition: { x: 74, y: 26 },
    playerScale: 1.0,
    enemyScale: 1.0,
    shadowStyle: 'rgba(10, 10, 20, 0.7)',
    ambientEffects: ['fireflies'],
    bossVariant: false
  }
})

const playerSlotStyles = computed(() => {
  const pos = currentEnvironment.value?.playerPosition || { x: 18, y: 70 }
  const scale = currentEnvironment.value?.playerScale || 1.0
  return {
    left: `clamp(1.5rem, ${pos.x}%, 14rem)`,
    bottom: '32px',
    '--player-scale': scale
  }
})

const opponentSlotStyles = computed(() => {
  const pos = currentEnvironment.value?.enemyPosition || { x: 74, y: 26 }
  const scale = currentEnvironment.value?.enemyScale || 1.0
  return {
    right: `clamp(1.5rem, ${100 - pos.x}%, 14rem)`,
    top: '25px',
    '--enemy-scale': scale
  }
})

// Character data for Player & Opponent
const selfCharacter = computed(() => {
  if (battle.self.character) return battle.self.character
  return auth.selectedCharacter || null
})

const oppCharacter = computed(() => {
  if (battle.opponent.character) return battle.opponent.character
  return {
    name: battle.opponent.username || 'Opponent',
    class_type: battle.opponent.character_class || battle.opponent.class_type || 'MAGE',
    key_name: String(battle.opponent.character_class || battle.opponent.class_type || 'mage').toLowerCase(),
    profile_avatar_path: battle.opponent.avatar_seed || null
  }
})

// Convert Question choices to format expected by BattleChoicePanel
const currentChoices = computed(() => {
  if (!battle.currentQuestion) return {}
  return {
    a: battle.currentQuestion.choice_a || '',
    b: battle.currentQuestion.choice_b || '',
    c: battle.currentQuestion.choice_c || '',
    d: battle.currentQuestion.choice_d || '',
  }
})

const battleOutcome = computed(() => {
  return battle.battleResult?.playerResults?.[battle.self.userId]?.outcome || 'draw'
})

const playerRewards = computed(() => {
  return battle.battleResult?.playerResults?.[battle.self.userId] || null
})

function handleSelectChoice(choiceKey) {
  if (battle.answerSubmitted || battle.status !== 'in_progress') return
  if (game.audioEnabled) {
    battleAudio.playSelect()
  }
  stagedChoice.value = choiceKey
}

function confirmStagedAnswer() {
  if (!stagedChoice.value || battle.answerSubmitted || battle.status !== 'in_progress') return
  const choice = stagedChoice.value
  stagedChoice.value = null
  battle.submitAnswer(choice, battlePrefs.criticalMode)
}

watch(() => battle.questionIndex, () => {
  stagedChoice.value = null
  showAttackProjectile.value = false
  showImpactSplash.value = false
  showAttackSlash.value = false
  screenFlash.value = false
  selfBattleState.value = 'idle'
  oppBattleState.value = 'idle'
  floatingSelfDmg.value = 0
  floatingOppDmg.value = 0
})

// ══════════════════════════════════════════════════════════════
// COMBAT ACTION ANIMATION EXECUTION
// ══════════════════════════════════════════════════════════════
watch(() => battle.lastCombatAction, (action) => {
  if (!action || !action.attemptId) return
  if (processedAttemptIds.has(action.attemptId)) return
  processedAttemptIds.add(action.attemptId)
  playCombatAnimation(action)
})

function playCombatAnimation(action) {
  const isAttackerSelf = (action.attackerId === battle.self.userId)

  // 1. Set attacker battle sprite state to 'attacking'
  if (isAttackerSelf) {
    selfBattleState.value = 'attacking'
  } else {
    oppBattleState.value = 'attacking'
  }

  // 2. Resolve attacker effect pair (Custom equipped effect or character default)
  const attackerData = isAttackerSelf ? battle.self : battle.opponent
  const attackerClass = attackerData.character_class || attackerData.class_type || 'MAGE'
  const attackerEquipped = attackerData.equipped || {}

  const rawAttackEff = action.attackEffect || attackerEquipped.attack_effect
  const rawSplashEff = action.splashEffect || attackerEquipped.splash_effect

  const resolvedAttackEff = resolveEquippedAttackEffect(rawAttackEff, attackerClass)
  const resolvedSplashEff = resolveEquippedSplashEffect(rawSplashEff, attackerClass)

  const attackGif = resolveAsset(resolvedAttackEff?.gif_path)
  const splashGif = resolveAsset(resolvedSplashEff?.gif_path)

  activeProjectileGif.value = attackGif ? `${attackGif}?t=${Date.now()}` : ''
  activeSplashGif.value = splashGif ? `${splashGif}?t=${Date.now()}` : ''
  activeEffectName.value = resolvedAttackEff?.name || 'Attack Effect'
  activeSplashName.value = resolvedSplashEff?.name || 'Splash Effect'

  wasLastCrit.value = Boolean(action.isCritical)

  // 3. Compute launch coordinates
  const arenaEl = arenaRef.value
  const pEl = playerSlotRef.value
  const oEl = opponentSlotRef.value

  let startX = 220
  let startY = 320
  let targetX = 680
  let targetY = 160

  if (arenaEl && pEl && oEl) {
    const arenaRect = arenaEl.getBoundingClientRect()
    const pRect = pEl.getBoundingClientRect()
    const oRect = oEl.getBoundingClientRect()

    const pCenter = {
      x: (pRect.left + pRect.width * 0.65) - arenaRect.left,
      y: (pRect.top + pRect.height * 0.35) - arenaRect.top
    }
    const oCenter = {
      x: (oRect.left + oRect.width * 0.35) - arenaRect.left,
      y: (oRect.top + oRect.height * 0.35) - arenaRect.top
    }

    if (isAttackerSelf) {
      startX = pCenter.x
      startY = pCenter.y
      targetX = oCenter.x
      targetY = oCenter.y
    } else {
      startX = oCenter.x
      startY = oCenter.y
      targetX = pCenter.x
      targetY = pCenter.y
    }
  }

  const dx = targetX - startX
  const dy = targetY - startY
  const angle = Math.atan2(dy, dx) * (180 / Math.PI)

  projectileCoords.value = {
    x: startX,
    y: startY,
    angle,
    progress: 0
  }
  showAttackProjectile.value = true

  if (game.audioEnabled) {
    battleAudio.playAttack()
  }

  // 4. Projectile travel animation (360ms)
  const travelDuration = 360
  const startTime = performance.now()

  function step(now) {
    const elapsed = now - startTime
    const p = Math.min(1, elapsed / travelDuration)
    const ease = 1 - Math.pow(1 - p, 3)

    projectileCoords.value = {
      x: startX + dx * ease,
      y: startY + dy * ease,
      angle,
      progress: ease
    }

    if (p < 1) {
      requestAnimationFrame(step)
    } else {
      // 5. Impact phase
      showAttackProjectile.value = false
      splashCoords.value = { x: targetX, y: targetY }
      showImpactSplash.value = true
      showAttackSlash.value = true

      flashType.value = action.isCritical ? 'critical' : (action.isCorrect ? 'correct' : 'wrong')
      screenFlash.value = true

      if (game.audioEnabled) {
        if (action.isCritical) battleAudio.playCrit()
        else battleAudio.playHit()
      }

      // Damage reaction on target
      if (isAttackerSelf) {
        floatingOppDmg.value = action.damage
        floatingOppDmgType.value = action.isCritical ? 'critical' : 'damage'
        oppBattleState.value = 'hit'
      } else {
        floatingSelfDmg.value = action.damage
        floatingSelfDmgType.value = action.isCritical ? 'critical' : 'damage'
        selfBattleState.value = 'hit'
      }

      // Reset effects after clean durations
      setTimeout(() => {
        screenFlash.value = false
        showAttackSlash.value = false
      }, 350)

      setTimeout(() => {
        showImpactSplash.value = false
      }, 420)

      setTimeout(() => {
        if (isAttackerSelf) {
          selfBattleState.value = (battle.self.hp <= 0) ? 'death' : 'idle'
          oppBattleState.value = (battle.opponent.hp <= 0) ? 'death' : 'idle'
        } else {
          oppBattleState.value = (battle.opponent.hp <= 0) ? 'death' : 'idle'
          selfBattleState.value = (battle.self.hp <= 0) ? 'death' : 'idle'
        }
      }, 650)

      setTimeout(() => {
        floatingSelfDmg.value = 0
        floatingOppDmg.value = 0
      }, 1200)
    }
  }

  requestAnimationFrame(step)
}

function handleProjectileError() {
  const def = getCharacterDefaultAttackEffect(auth.characterClass || 'mage')
  activeProjectileGif.value = resolveAsset(def?.gif_path)
}

function handleSplashError() {
  const def = getDefaultSplashEffect(auth.characterClass || 'mage')
  activeSplashGif.value = resolveAsset(def?.gif_path)
}

function returnToHub() {
  router.push('/multiplayer')
}

onMounted(() => {
  bgmService.setBattlefieldActive(true)
})

onUnmounted(() => {
  bgmService.setBattlefieldActive(false)
  battle.reset()
})
</script>

<style scoped>
.arena-battlefield-wrapper {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  gap: 0.65rem;
  padding: 0.5rem;
  box-sizing: border-box;
}

/* Screen Flash Overlay */
.battle-screen-flash {
  position: fixed;
  inset: 0;
  z-index: 100;
  pointer-events: none;
  animation: flash-fade 0.35s ease-out forwards;
}

.battle-screen-flash.correct {
  background: radial-gradient(circle, rgba(52, 211, 153, 0.35) 0%, transparent 75%);
}

.battle-screen-flash.critical {
  background: radial-gradient(circle, rgba(251, 191, 36, 0.45) 0%, rgba(239, 68, 68, 0.35) 50%, transparent 80%);
}

.battle-screen-flash.wrong {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.4) 0%, transparent 75%);
}

@keyframes flash-fade {
  0% { opacity: 1; }
  100% { opacity: 0; }
}

/* Stage & Minimal HUD */
.battlefield-stage {
  position: relative;
  width: 100%;
  border-radius: 8px;
  overflow: visible;
}

.stage-minimal-hud {
  position: absolute;
  top: 10px;
  left: 14px;
  right: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 35;
  pointer-events: none;
}

.hud-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.hud-right {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.hud-chapter-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #38bdf8;
  background: rgba(14, 165, 233, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.4);
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
}

.hud-encounter-title {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: 0.5px;
}

.hud-env-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #c084fc;
  border-left: 1px solid rgba(168, 85, 247, 0.45);
  padding-left: 0.6rem;
}

.wager-hud-badge {
  font-size: 0.58rem;
  color: #4ade80;
  background: rgba(8, 9, 12, 0.85);
  border: 1px solid rgba(74, 222, 128, 0.4);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

.timer-badge {
  font-size: 0.85rem;
  color: #e6c845;
  background: rgba(12, 10, 20, 0.85);
  border: 1px solid rgba(230, 200, 69, 0.4);
  padding: 0.25rem 0.65rem;
  border-radius: 4px;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.4);
}

.timer-badge.urgent {
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.6);
  text-shadow: 0 0 14px rgba(239, 68, 68, 0.8);
  animation: pulse 0.8s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.08); }
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

/* Combat Arena */
.combat-arena {
  position: relative;
  display: flex;
  width: 100%;
  min-height: 350px;
  padding-bottom: 0.5rem;
  overflow: visible;
}

.combatant-slot {
  position: absolute;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
}

.slot-player {
  position: absolute;
  bottom: 32px;
  left: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--player-scale, 1));
  transform-origin: bottom center;
  z-index: 15;
}

.slot-enemy {
  position: absolute;
  top: 25px;
  right: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--enemy-scale, 1));
  transform-origin: top center;
  z-index: 14;
}

/* Projectile Animation Layer */
.arena-attack-projectile {
  position: absolute;
  z-index: 45;
  pointer-events: none;
  width: 700px;
  height: 450px;
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 25px rgba(56, 189, 248, 0.85)) drop-shadow(0 0 45px rgba(168, 85, 247, 0.5));
  will-change: left, top, transform;
}

.projectile-sprite {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
}

/* Splash Impact Layer */
.arena-impact-splash {
  position: absolute;
  z-index: 50;
  pointer-events: none;
  transform: translate(-50%, -50%);
  width: 240px;
  height: 240px;
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 18px rgba(251, 191, 36, 0.75));
  animation: splashPulse 0.35s ease-out forwards;
}

.arena-impact-splash.is-crit-splash {
  filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.95)) drop-shadow(0 0 35px rgba(251, 191, 36, 0.9));
}

.splash-sprite {
  width: 100%;
  height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
}

@keyframes splashPulse {
  0% { transform: translate(-50%, -50%) scale(0.7); opacity: 0; }
  15% { transform: translate(-50%, -50%) scale(1.1); opacity: 1; }
  80% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1.05); opacity: 0; }
}

/* Center Slash Cut Effect */
.arena-slash-fx {
  position: absolute;
  top: 30%;
  left: 40%;
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
  animation: slash-anim 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slash-anim {
  0% { opacity: 0; transform: scale(0.6) rotate(-15deg); }
  35% { opacity: 1; transform: scale(1.15) rotate(0deg); }
  100% { opacity: 0; transform: scale(1.3) rotate(10deg); }
}

/* Lower Interactive Combat Zones (Split Layout) */
.battlefield-controls-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
  width: 100%;
  min-height: 290px;
}

.controls-left-question,
.controls-right-actions {
  display: flex;
  flex-direction: column;
  height: 100%;
}

/* Manual Confirmation Action Bar */
.confirm-stage-bar {
  margin-top: 0.5rem;
  background: rgba(14, 16, 26, 0.95);
  border: 1px solid rgba(230, 200, 69, 0.45);
  box-shadow: 0 0 15px rgba(230, 200, 69, 0.2);
  border-radius: 4px;
  padding: 0.75rem 1.2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.staged-prompt {
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.05em;
}

.btn-confirm-action {
  background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 100%);
  color: #fef08a;
  border: 1px solid #f59e0b;
  padding: 0.5rem 1.2rem;
  font-size: 0.65rem;
  border-radius: 3px;
  cursor: pointer;
  letter-spacing: 0.06em;
  transition: all 0.2s ease;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.3);
}

.btn-confirm-action:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #991b1b 100%);
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 0 14px rgba(245, 158, 11, 0.5);
}

/* Ready Screen Overlay */
.ready-overlay {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.92);
  backdrop-filter: blur(8px);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ready-card {
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 12px 35px rgba(0, 0, 0, 0.9), 0 0 25px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  padding: 2.5rem;
  text-align: center;
  max-width: 480px;
  width: 90%;
}

.ready-card h2 {
  font-family: 'Cinzel', serif;
  color: #f3ede2;
  font-size: 1.3rem;
  margin-bottom: 0.5rem;
}

.ready-card p {
  color: #9ca3af;
  font-size: 0.9rem;
}

.ready-status-row {
  display: flex;
  gap: 1rem;
  margin: 1.8rem 0;
}

.ready-pill {
  flex: 1;
  padding: 0.6rem 0.8rem;
  background: #09090e;
  border: 2px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #71717a;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.65rem;
}

.ready-pill.confirmed {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border-color: #10b981;
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.3);
}

.btn-ready {
  background: #8b1a1a;
  color: #fde8e8;
  border: 2px solid #e02424;
  box-shadow: 0 0 0 2px #050608, 0 4px 12px rgba(139, 26, 26, 0.4);
  padding: 0.9rem 2.5rem;
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-ready:hover {
  background: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 0 0 2px #050608, 0 6px 18px rgba(220, 38, 38, 0.5);
}

.waiting-prompt {
  font-size: 0.65rem;
  color: #9ca3af;
}

.countdown-banner {
  margin-top: 1.5rem;
  font-size: 1rem;
  color: #e6c845;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.5);
}

/* Game Over Modal */
.game-over-modal {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.9);
  backdrop-filter: blur(10px);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.modal-card {
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border: 2px solid #282838;
  box-shadow: 0 0 0 3px #050608, 0 20px 50px rgba(0, 0, 0, 0.95);
  border-radius: 4px;
  padding: 2.5rem;
  text-align: center;
  max-width: 520px;
  width: 100%;
}

.modal-card.won {
  border-color: #b59410;
  box-shadow: 0 0 0 3px #050608, 0 0 30px rgba(181, 148, 16, 0.35);
}

.modal-card.lost {
  border-color: #8b1a1a;
  box-shadow: 0 0 0 3px #050608, 0 0 30px rgba(139, 26, 26, 0.35);
}

.outcome-icon {
  font-size: 3.5rem;
  margin-bottom: 0.5rem;
}

.outcome-title {
  font-size: 1.9rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.3rem;
  letter-spacing: 0.05em;
}

.modal-card.won .outcome-title { color: #e6c845; text-shadow: 0 0 15px rgba(230, 200, 69, 0.4); }
.modal-card.lost .outcome-title { color: #ef4444; text-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

.outcome-subtitle {
  color: #9ca3af;
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}

.rewards-box {
  background: #09090e;
  border: 1px solid #202230;
  padding: 1.2rem;
  border-radius: 4px;
  margin-bottom: 1.5rem;
  text-align: left;
}

.reward-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #14141e;
  font-size: 0.88rem;
  color: #d1d5db;
}

.reward-row:last-child {
  border-bottom: none;
}

.reward-row strong.pos { color: #34d399; }
.reward-row strong.neg { color: #ef4444; }
.reward-row strong.gold { color: #e6c845; }
.reward-row strong.xp { color: #a78bfa; }

.elo-disclaimer-note {
  font-size: 0.55rem;
  color: #94a3b8;
  margin-top: 0.75rem;
  line-height: 1.4;
}

.btn-return {
  background: #8b1a1a;
  color: #fde8e8;
  border: 1px solid #e02424;
  padding: 0.8rem 2rem;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  border-radius: 2px;
  cursor: pointer;
  width: 100%;
  transition: all 0.2s;
}

.btn-return:hover {
  background: #b91c1c;
}

/* Responsive adjustments */
@media (max-width: 860px) {
  .battlefield-controls-split {
    grid-template-columns: 1fr;
  }
  .slot-enemy {
    right: 2%;
    top: 10px;
    transform: scale(0.9);
  }
  .slot-player {
    left: 2%;
    bottom: 35px;
    transform: scale(0.9);
  }
}
</style>
