<template>
  <div class="battle-page">
    <!-- Battle Top HUD -->
    <div class="battle-hud">
      <!-- Player 1 (Self) -->
      <div class="hud-player self">
        <div class="avatar-badge">
          <span>🧙‍♂️</span>
        </div>
        <div class="player-meta">
          <div class="name-row">
            <span class="player-name">{{ battle.self.username || 'You' }}</span>
            <span class="player-rating">★ {{ battle.self.rating }}</span>
          </div>
          <div class="hp-bar-container">
            <div class="hp-bar-fill" :style="{ width: `${battle.self.hp}%` }"></div>
          </div>
          <div class="hp-text">{{ battle.self.hp }} / 100 HP</div>
        </div>
      </div>

      <!-- Center Question Timer / Progress -->
      <div class="hud-center">
        <div class="round-counter">
          Question {{ battle.questionIndex + 1 }} / {{ battle.totalQuestions }}
        </div>
        <div class="timer-badge" :class="{ urgent: battle.timeRemaining <= 5 }">
          ⏱️ {{ battle.timeRemaining }}s
        </div>
        <div class="mode-pill">{{ battle.battleMode.toUpperCase() }}</div>
      </div>

      <!-- Player 2 (Opponent) -->
      <div class="hud-player opponent">
        <div class="player-meta text-right">
          <div class="name-row justify-end">
            <span class="player-rating">★ {{ battle.opponent.rating }}</span>
            <span class="player-name">{{ battle.opponent.username || 'Opponent' }}</span>
          </div>
          <div class="hp-bar-container">
            <div class="hp-bar-fill opp" :style="{ width: `${battle.opponent.hp}%` }"></div>
          </div>
          <div class="hp-text">{{ battle.opponent.hp }} / 100 HP</div>
        </div>
        <div class="avatar-badge opp">
          <span>⚔️</span>
        </div>
      </div>
    </div>

    <!-- Ready Screen Overlay (Before Battle Starts) -->
    <div v-if="battle.status === 'waiting' || battle.status === 'starting'" class="ready-overlay">
      <div class="ready-card">
        <h2>⚔️ Board Exam Psychology Duel</h2>
        <p>Both candidates must confirm readiness to initialize the duel.</p>

        <div class="ready-status-row">
          <div class="ready-pill" :class="{ confirmed: battle.self.isReady }">
            {{ battle.self.username }}: {{ battle.self.isReady ? 'READY ✓' : 'WAITING…' }}
          </div>
          <div class="ready-pill" :class="{ confirmed: battle.opponent.isReady }">
            {{ battle.opponent.username }}: {{ battle.opponent.isReady ? 'READY ✓' : 'WAITING…' }}
          </div>
        </div>

        <button
          v-if="!battle.self.isReady"
          class="btn-ready"
          @click="battle.setReady()"
        >
          I'm Ready!
        </button>
        <div v-else class="waiting-prompt">
          Waiting for {{ battle.opponent.username }} to ready up…
        </div>

        <div v-if="battle.status === 'starting'" class="countdown-banner">
          Battle begins in {{ battle.timeRemaining }}…
        </div>
      </div>
    </div>

    <!-- Phaser 4 Arena Container -->
    <div class="phaser-container-wrapper">
      <div id="phaser-battle-container" class="phaser-screen"></div>
    </div>

    <!-- Question & Answers Card (When Battle is in Progress) -->
    <div v-if="battle.status === 'in_progress' && battle.currentQuestion" class="question-panel animate-slide-up">
      <div class="question-header">
        <span class="cat-tag">{{ battle.currentQuestion.category_name || 'General Psychology' }}</span>
        <span class="diff-tag">{{ battle.currentQuestion.difficulty || 'Board Exam' }}</span>
      </div>

      <p class="question-text">{{ battle.currentQuestion.question }}</p>

      <div class="choices-grid">
        <button
          v-for="choiceKey in ['a', 'b', 'c', 'd']"
          :key="choiceKey"
          class="choice-btn"
          :class="{
            selected: battle.selectedAnswer === choiceKey,
            correct: battle.roundFeedback && battle.roundFeedback.correctAnswer === choiceKey,
            wrong: battle.roundFeedback && battle.selectedAnswer === choiceKey && !battle.roundFeedback.selfCorrect,
            disabled: battle.answerSubmitted
          }"
          :disabled="battle.answerSubmitted"
          @click="handleSelectAnswer(choiceKey)"
        >
          <span class="choice-letter">{{ choiceKey.toUpperCase() }}</span>
          <span class="choice-content">{{ battle.currentQuestion['choice_' + choiceKey] }}</span>
        </button>
      </div>

      <!-- Round Feedback & Clinical Explanation -->
      <div v-if="battle.roundFeedback" class="feedback-banner" :class="{ success: battle.roundFeedback.selfCorrect }">
        <div class="feedback-title">
          {{ battle.roundFeedback.selfCorrect ? '✨ Correct Diagnosis!' : '❌ Incorrect Selection!' }}
        </div>
        <p class="feedback-explanation">
          {{ battle.roundFeedback.explanation }}
        </p>
      </div>
    </div>

    <!-- Game Over / Victory / Defeat Modal -->
    <div v-if="battle.status === 'ended' && battle.battleResult" class="game-over-modal">
      <!-- Finisher Effect Overlay (shown when player wins and has finisher equipped) -->
      <div
        v-if="battleOutcome === 'won' && equippedEffects.finisher_effect"
        class="finisher-fx-overlay"
        :class="equippedEffects.finisher_effect.effect_key"
      >
        <div class="finisher-rays-wrap">
          <div v-for="i in 12" :key="i" class="finisher-ray" :style="{ transform: `rotate(${(i-1)*30}deg)` }"></div>
        </div>
        <div class="finisher-title-text">{{ equippedEffects.finisher_effect.name }}</div>
      </div>

      <div class="modal-card" :class="battleOutcome">
        <div class="outcome-icon">
          {{ battleOutcome === 'won' ? '👑' : battleOutcome === 'lost' ? '💀' : '🤝' }}
        </div>
        <h2 class="outcome-title">
          {{ battleOutcome === 'won' ? 'VICTORY!' : battleOutcome === 'lost' ? 'DEFEAT' : 'STALEMATE (DRAW)' }}
        </h2>
        <p class="outcome-subtitle">
          {{ battleOutcome === 'won' ? 'Outstanding psychiatric mastery!' : 'Review your clinical concepts and try again.' }}
        </p>

        <div class="rewards-box">
          <div class="reward-row" v-if="playerRewards">
            <span>Battle Rating (ELO):</span>
            <strong :class="playerRewards.ratingChange >= 0 ? 'pos' : 'neg'">
              {{ playerRewards.ratingChange >= 0 ? '+' : '' }}{{ playerRewards.ratingChange }}
            </strong>
          </div>
          <div class="reward-row" v-if="playerRewards">
            <span>Gold Coins:</span>
            <strong class="gold">+{{ playerRewards.coinsEarned }} <CoinIcon :size="15" /></strong>
          </div>
          <div class="reward-row" v-if="playerRewards">
            <span>Experience XP:</span>
            <strong class="xp">+{{ playerRewards.xpEarned }} XP</strong>
          </div>
        </div>

        <button class="btn-return" @click="returnToHub">
          Return to Arena Hub
        </button>
      </div>
    </div>

    <!-- Hit Effect Overlay (flash on opponent side when self answered correctly) -->
    <Transition name="hit-fx">
      <div
        v-if="showHitEffect"
        class="hit-fx-overlay"
        :class="equippedEffects.hit_effect?.effect_key"
      ></div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useBattleStore } from '../stores/battle'
import CoinIcon from '../components/common/CoinIcon.vue'
import shopService from '../services/shopService'
import { bgmService } from '../services/bgmService'

const router = useRouter()
const battle = useBattleStore()
let phaserGame = null

// Equipped cosmetic effects for this session
const equippedEffects = ref({})
const showHitEffect   = ref(false)

const battleOutcome = computed(() => {
  return battle.battleResult?.playerResults?.[battle.self.userId]?.outcome || 'draw'
})

const playerRewards = computed(() => {
  return battle.battleResult?.playerResults?.[battle.self.userId] || null
})

async function loadEquippedEffects() {
  try {
    const res = await shopService.getEquipped()
    if (res.success) {
      equippedEffects.value = res.equipped || {}
    }
  } catch (e) {
    // Not critical — battle works without cosmetics
    console.warn('Could not load equipped cosmetics:', e)
  }
}

onMounted(async () => {
  bgmService.setBattlefieldActive(true)

  // Load equipped effects
  await loadEquippedEffects()

  // Dynamically import Phaser only when on the battle page
  // This prevents Phaser's performance observer from running on all other pages
  try {
    const { createBattleGame } = await import('../phaser/battle/BattleGame')
    phaserGame = createBattleGame('phaser-battle-container', {
      self: battle.self,
      opponent: battle.opponent,
    })
  } catch (e) {
    console.warn('[MultiplayerBattle] Failed to initialize Phaser arena:', e)
  }
})

onUnmounted(() => {
  bgmService.setBattlefieldActive(false)
  if (phaserGame) {
    phaserGame.destroy(true)
    phaserGame = null
  }
  battle.reset()
})

// Trigger Phaser battle animations + hit effects on round result
watch(() => battle.roundFeedback, (feedback) => {
  if (!feedback || !phaserGame) return
  const scene = phaserGame.scene.getScene('BattleScene')

  if (feedback.selfCorrect) {
    if (scene) {
      scene.playerAttack()
      setTimeout(() => scene.opponentHurt(feedback.damageDealtToOpp || 10), 300)
    }
    // Trigger hit effect cosmetic overlay
    if (equippedEffects.value.hit_effect) {
      showHitEffect.value = true
      setTimeout(() => { showHitEffect.value = false }, 700)
    }
  }
  if (feedback.oppCorrect) {
    if (scene) {
      scene.opponentAttack()
      setTimeout(() => scene.playerHurt(feedback.damageTaken || 10), 300)
    }
  }
})

function handleSelectAnswer(choice) {
  battle.submitAnswer(choice)
}

function returnToHub() {
  router.push('/multiplayer')
}
</script>

<style scoped>
.battle-page {
  min-height: calc(100vh - 80px);
  padding: 1.5rem;
  max-width: 1100px;
  margin: 0 auto;
  color: #e2d9cc;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ══ GOTHIC BATTLE HUD ══ */
.battle-hud {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(180deg, #14131b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, 0 10px 28px rgba(0, 0, 0, 0.8), inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  padding: 1.2rem 2rem;
  position: relative;
}

.hud-player {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 260px;
}

.hud-player.opponent {
  justify-content: flex-end;
}

.avatar-badge {
  width: 52px;
  height: 52px;
  border-radius: 4px;
  background: #0d1210;
  border: 2px solid #34d399;
  box-shadow: 0 0 0 2px #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.avatar-badge.opp {
  background: #190c10;
  border-color: #b91c1c;
}

.player-meta {
  flex: 1;
}

.name-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.player-name {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.05em;
  color: #f3ede2;
}

.player-rating {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
}

.hp-bar-container {
  height: 12px;
  background: #09090e;
  border: 2px solid #050608;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.9);
  border-radius: 0;
  overflow: hidden;
  margin-bottom: 0.3rem;
}

.hp-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #059669, #10b981);
  box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
  transition: width 0.4s ease;
}

.hp-bar-fill.opp {
  background: linear-gradient(90deg, #8b1a1a, #dc2626);
  box-shadow: 0 0 8px rgba(220, 38, 38, 0.6);
}

.hp-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #9ca3af;
}

.text-right { text-align: right; }
.justify-end { justify-content: flex-end; }

.hud-center {
  text-align: center;
  padding: 0 1rem;
}

.round-counter {
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  color: #8c887b;
  letter-spacing: 0.05em;
}

.timer-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.4rem;
  color: #e6c845;
  margin: 0.3rem 0;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.4);
}

.timer-badge.urgent {
  color: #ef4444;
  text-shadow: 0 0 14px rgba(239, 68, 68, 0.8);
  animation: pulse 0.8s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.mode-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  letter-spacing: 0.1em;
  background: rgba(181, 148, 16, 0.15);
  color: #e6c845;
  border: 1px solid rgba(181, 148, 16, 0.35);
  padding: 0.2rem 0.6rem;
  border-radius: 2px;
  display: inline-block;
}

/* ══ READY MODAL ══ */
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

.ready-status-row {
  display: flex;
  gap: 1rem;
  margin: 1.8rem 0;
}

.ready-pill {
  flex: 1;
  padding: 0.8rem;
  background: #09090e;
  border: 2px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #71717a;
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
  font-family: 'Cinzel', serif;
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

.countdown-banner {
  margin-top: 1.5rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 1.1rem;
  color: #e6c845;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.5);
}

/* ══ PHASER CANVAS FRAME ══ */
.phaser-container-wrapper {
  background: #050608;
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  overflow: hidden;
  height: 380px;
}

.phaser-screen {
  width: 100%;
  height: 100%;
}

/* ══ QUESTION PANEL ══ */
.question-panel {
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  padding: 1.8rem;
}

.question-header {
  display: flex;
  gap: 0.6rem;
  margin-bottom: 1rem;
}

.cat-tag, .diff-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.7rem;
  border-radius: 2px;
  border: 1px solid;
}

.cat-tag {
  background: rgba(181, 148, 16, 0.12);
  color: #e6c845;
  border-color: rgba(181, 148, 16, 0.35);
}

.diff-tag {
  background: rgba(139, 26, 26, 0.15);
  color: #f87171;
  border-color: rgba(139, 26, 26, 0.4);
}

.question-text {
  font-size: 1.15rem;
  font-weight: 500;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  color: #f3ede2;
}

.choices-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.choice-btn {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  background: #0e0e16;
  border: 2px solid #222436;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 4px;
  padding: 1rem 1.2rem;
  color: #d1d5db;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s;
  font-family: inherit;
}

.choice-btn:hover:not(.disabled) {
  border-color: #b59410;
  background: #161724;
  color: #f3ede2;
}

.choice-btn.selected {
  border-color: #e6c845;
  background: rgba(181, 148, 16, 0.15);
}

.choice-btn.correct {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.2);
  color: #a7f3d0;
}

.choice-btn.wrong {
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.2);
  color: #fca5a5;
}

.choice-letter {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 2px;
  background: #181926;
  border: 1px solid #32354a;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #e6c845;
}

.feedback-banner {
  margin-top: 1.5rem;
  padding: 1rem 1.2rem;
  background: rgba(220, 38, 38, 0.15);
  border: 2px solid #8b1a1a;
  border-radius: 2px;
}

.feedback-banner.success {
  background: rgba(16, 185, 129, 0.15);
  border-color: #10b981;
}

.feedback-title {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0.3rem;
}

.feedback-explanation {
  font-size: 0.9rem;
  color: #9ca3af;
  line-height: 1.4;
  margin: 0;
}

/* ══ GAME OVER MONOLITH MODAL ══ */
.game-over-modal {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.94);
  backdrop-filter: blur(10px);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-card {
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border-radius: 4px;
  padding: 3rem;
  text-align: center;
  max-width: 480px;
  width: 90%;
  border: 2px solid;
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.95);
}

.modal-card.won {
  border-color: #b59410;
  box-shadow: 0 0 0 2px #050608, 0 0 35px rgba(181, 148, 16, 0.35);
}

.modal-card.lost {
  border-color: #8b1a1a;
  box-shadow: 0 0 0 2px #050608, 0 0 35px rgba(139, 26, 26, 0.35);
}

.modal-card.draw {
  border-color: #4b5563;
  box-shadow: 0 0 0 2px #050608, 0 0 35px rgba(75, 85, 99, 0.35);
}

.outcome-icon {
  font-size: 50px;
  margin-bottom: 1rem;
}

.outcome-title {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin: 0 0 0.5rem;
  color: #f3ede2;
}

.outcome-subtitle {
  font-family: 'VT323', monospace;
  color: #8c887b;
  margin: 0 0 2rem;
  font-size: 1.2rem;
}

.rewards-box {
  background: #0a0a0f;
  border: 1px solid #202230;
  border-radius: 2px;
  padding: 1.2rem;
  margin-bottom: 2rem;
}

.reward-row {
  display: flex;
  justify-content: space-between;
  padding: 0.6rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
}

.reward-row:last-child { border-bottom: none; }
.pos { color: #34d399; }
.neg { color: #f87171; }
.gold { color: #e6c845; }
.xp { color: #c084fc; }

.btn-return {
  background: #181926;
  color: #e6c845;
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608;
  padding: 0.9rem 2rem;
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  border-radius: 2px;
  cursor: pointer;
  width: 100%;
  transition: all 0.2s;
}

.btn-return:hover {
  background: #25283d;
  color: #fef08a;
  transform: translateY(-2px);
}

/* ══ HIT EFFECT OVERLAY ══ */
.hit-fx-overlay {
  position: fixed;
  top: 30%;
  right: 15%;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  pointer-events: none;
  z-index: 900;
  animation: hitFxPop 0.7s ease-out forwards;
}

.hit-fx-overlay.hit-focus    { background: radial-gradient(circle, #ffffff 0%, rgba(255,255,255,0) 70%); box-shadow: 0 0 40px white; }
.hit-fx-overlay.hit-psych    { background: radial-gradient(circle, #b91c1c 0%, rgba(185,28,28,0) 70%); box-shadow: 0 0 40px #b91c1c; }
.hit-fx-overlay.hit-mind     { background: radial-gradient(circle, #a855f7 0%, rgba(168,85,247,0) 70%); box-shadow: 0 0 40px #9333ea; }
.hit-fx-overlay.hit-collapse { background: radial-gradient(circle, #050608 0%, transparent 70%); box-shadow: 0 0 60px rgba(185,28,28,0.8); }

@keyframes hitFxPop {
  0%   { transform: scale(0.2); opacity: 0; }
  35%  { transform: scale(1.3); opacity: 1; }
  60%  { transform: scale(0.95); opacity: 0.9; }
  100% { transform: scale(0.2); opacity: 0; }
}

.hit-fx-enter-active  { animation: hitFxPop 0.7s ease-out forwards; }
.hit-fx-leave-active  { opacity: 0; }

/* ══ FINISHER EFFECT OVERLAY ══ */
.finisher-fx-overlay {
  position: absolute;
  inset: 0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-bottom: 2rem;
  flex-direction: column;
  z-index: 5;
  pointer-events: none;
  animation: finisherFade 1.2s ease-out;
}

.finisher-fx-overlay.finish-flash     { background: rgba(255,255,255,0.08); border: 2px solid rgba(255,255,255,0.2); }
.finisher-fx-overlay.finish-insight   { background: rgba(181, 148, 16, 0.1); border: 2px solid rgba(181, 148, 16, 0.3); }
.finisher-fx-overlay.finish-enlighten { background: rgba(230, 200, 69, 0.1); border: 2px solid rgba(230, 200, 69, 0.3); }
.finisher-fx-overlay.finish-legend    {
  background: linear-gradient(135deg, rgba(139, 26, 26, 0.15), rgba(181, 148, 16, 0.15));
  border: 2px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 60px rgba(181, 148, 16, 0.3);
}

.finisher-rays-wrap {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: finisherRaysSpin 1.2s ease-out;
  overflow: hidden;
  border-radius: 4px;
}

.finisher-ray {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 3px;
  height: 200px;
  transform-origin: 0 0;
  border-radius: 2px;
}
.finish-flash     .finisher-ray { background: linear-gradient(to top, rgba(255,255,255,0.5), transparent); }
.finish-insight   .finisher-ray { background: linear-gradient(to top, rgba(181, 148, 16, 0.6), transparent); }
.finish-enlighten .finisher-ray { background: linear-gradient(to top, rgba(230, 200, 69, 0.7), transparent); }
.finish-legend    .finisher-ray { background: linear-gradient(to top, rgba(185, 28, 28, 0.7), transparent); }

.finisher-title-text {
  position: relative;
  z-index: 1;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  letter-spacing: 0.12em;
  color: #e6c845;
  text-shadow: 0 0 12px rgba(230, 200, 69, 0.7);
  text-transform: uppercase;
  animation: finisherTextFade 1.2s ease-out;
}

@keyframes finisherFade {
  0%   { opacity: 0; transform: scale(0.94); }
  30%  { opacity: 1; transform: scale(1.02); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes finisherRaysSpin {
  0%   { transform: rotate(0deg); opacity: 0; }
  20%  { opacity: 1; }
  100% { transform: rotate(45deg); opacity: 0.6; }
}

@keyframes finisherTextFade {
  0%   { opacity: 0; transform: translateY(8px); }
  40%  { opacity: 1; transform: translateY(0); }
  100% { opacity: 1; }
}
</style>
