<template>
  <div class="battle-choice-panel gothic-panel" role="region" aria-label="Action Commands">
    <!-- Action Command Title Bar with Upper-Right Critical Mode Switch (Section 10) -->
    <div class="action-title-bar">
      <div class="action-title-left">
        <span class="action-icon">⚡</span>
        <span class="action-title-text">BATTLE ACTIONS & HYPOTHESES</span>
      </div>

      <!-- Critical Mode Switch / Toggle (Upper-Right) -->
      <div class="crit-toggle-wrapper">
        <!-- Speech Bubble Notification on left of toggle (ONLY when turned ON) -->
        <Transition name="crit-bubble-pop">
          <div v-if="showCritBubble" class="crit-speech-bubble" role="status">
            <span class="bubble-icon">💥</span>
            <span class="bubble-text"><strong>CRITICAL ON!</strong> Correct → Crit to enemy. Wrong → Crit to self!</span>
            <div class="bubble-arrow-right"></div>
          </div>
        </Transition>

        <div
          class="crit-switch-badge"
          :class="{
            'is-active': activeCrit,
            'is-disabled': disabled || isLocked || answered
          }"
          @click="toggleCritical"
          role="switch"
          :aria-checked="activeCrit"
          tabindex="0"
          @keydown.space.prevent="toggleCritical"
          @keydown.enter.prevent="toggleCritical"
          title="Toggle Critical Mode: Higher damage if correct, but wrong answers deal critical backfire to you!"
        >
          <span class="crit-switch-label font-pixel">{{ activeCrit ? 'CRIT ON' : 'CRIT OFF' }}</span>
          <div class="crit-switch-track">
            <div class="crit-switch-knob"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2x2 Action Command Buttons Grid (Pokemon Fight/Bag/Pokemon/Run inspired, but Answer = Action) -->
    <div class="action-choices-grid">
      <button
        v-for="(text, key) in choices"
        :key="key"
        type="button"
        class="action-choice-btn"
        :class="getChoiceStateClass(key)"
        :disabled="disabled || isLocked"
        @click="selectChoice(key)"
      >
        <!-- Runic Action Key Badge [ A ], [ B ], [ C ], [ D ] -->
        <span class="action-key-badge">{{ key }}</span>

        <!-- Choice Text Prompt -->
        <span class="action-choice-text">{{ text }}</span>

        <!-- State Icon Badge -->
        <span v-if="!answered && isKeySelected(key)" class="action-staged-tag font-pixel">⚡ STAGED</span>
        <span v-else-if="answered && isKeyCorrect(key)" class="action-state-icon correct-icon">✓</span>
        <span v-else-if="answered && isKeySelected(key) && !isKeyCorrect(key)" class="action-state-icon wrong-icon">✕</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'

const props = defineProps({
  choices: {
    type: Object,
    required: true
  },
  selectedKey: {
    type: String,
    default: null
  },
  correctAnswer: {
    type: String,
    default: null
  },
  answered: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  isCriticalActive: {
    type: Boolean,
    default: false
  },
  critDamage: {
    type: Number,
    default: 35
  },
  baseDamage: {
    type: Number,
    default: 10
  }
})

const emit = defineEmits(['select', 'toggle-critical', 'update:isCriticalActive'])

const isLocked = ref(false)
const showCritBubble = ref(false)
let bubbleTimer = null

const activeCrit = computed(() => !!props.isCriticalActive)

function toggleCritical() {
  if (props.disabled || props.answered || isLocked.value) return
  const nextVal = !activeCrit.value
  emit('toggle-critical', nextVal)
  emit('update:isCriticalActive', nextVal)

  if (nextVal) {
    showCritBubble.value = true
    if (bubbleTimer) clearTimeout(bubbleTimer)
    bubbleTimer = setTimeout(() => {
      showCritBubble.value = false
    }, 3200)
  } else {
    showCritBubble.value = false
  }
}

watch(() => props.selectedKey, (val) => {
  if (!val && !props.answered) {
    isLocked.value = false
  }
})

// Reset toggle to OFF after a critical attempt resolves (Section 10)
watch(() => props.answered, (newVal) => {
  if (!newVal) {
    isLocked.value = false
  } else {
    showCritBubble.value = false
    if (activeCrit.value) {
      emit('toggle-critical', false)
      emit('update:isCriticalActive', false)
    }
  }
})

onUnmounted(() => {
  if (bubbleTimer) clearTimeout(bubbleTimer)
})

function selectChoice(key) {
  if (props.disabled || props.answered || isLocked.value) return
  isLocked.value = true
  emit('select', key)
}

function isKeyCorrect(key) {
  if (!props.correctAnswer) return false
  return String(key || '').trim().toUpperCase() === String(props.correctAnswer || '').trim().toUpperCase()
}

function isKeySelected(key) {
  if (!props.selectedKey) return false
  return String(key || '').trim().toUpperCase() === String(props.selectedKey || '').trim().toUpperCase()
}

function getChoiceStateClass(key) {
  if (!props.answered) {
    return {
      'state-selected': isKeySelected(key)
    }
  }

  const isTargetCorrect = isKeyCorrect(key)
  const isTargetChosen = isKeySelected(key)

  if (isTargetCorrect) {
    return 'state-correct'
  }
  if (isTargetChosen && !isTargetCorrect) {
    return 'state-wrong'
  }
  return 'state-dimmed'
}
</script>

<style scoped>
.battle-choice-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 220px;
  background: linear-gradient(180deg, rgba(22, 14, 40, 0.96) 0%, rgba(14, 8, 26, 0.98) 100%);
  border: 2px solid rgba(168, 85, 247, 0.5);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7), inset 0 0 10px rgba(139, 92, 246, 0.2);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  overflow: visible;
  position: relative;
}

/* Action Title Bar with Toggle */
.action-title-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.5rem;
  margin-bottom: 0.65rem;
  flex-shrink: 0;
  position: relative;
}

.action-title-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.action-icon {
  font-size: 0.75rem;
  color: #fbbf24;
}

.action-title-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #c4b5fd;
  letter-spacing: 0.5px;
}

/* Critical Toggle Switch & Speech Bubble (Section 10) */
.crit-toggle-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

/* Speech Bubble Notification on left of toggle */
.crit-speech-bubble {
  position: absolute;
  right: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: linear-gradient(135deg, #450a0a 0%, #1c0a0a 100%);
  border: 1.5px solid #ef4444;
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.45), 0 0 10px rgba(0, 0, 0, 0.8);
  border-radius: 6px;
  padding: 0.35rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
  z-index: 50;
  pointer-events: none;
}

.crit-speech-bubble .bubble-icon {
  font-size: 0.8rem;
  animation: pulse-crit 1s infinite alternate;
}

.crit-speech-bubble .bubble-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.45rem;
  color: #fecaca;
  letter-spacing: 0.3px;
}

.bubble-arrow-right {
  position: absolute;
  right: -6px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-top: 5px solid transparent;
  border-bottom: 5px solid transparent;
  border-left: 6px solid #ef4444;
}

/* Speech bubble transition */
.crit-bubble-pop-enter-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.crit-bubble-pop-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.crit-bubble-pop-enter-from {
  opacity: 0;
  transform: translateY(-50%) scale(0.85) translateX(8px);
}
.crit-bubble-pop-leave-to {
  opacity: 0;
  transform: translateY(-50%) scale(0.9);
}

@keyframes pulse-crit {
  0% { transform: scale(1); }
  100% { transform: scale(1.2); }
}

/* Game-styled Switch */
.crit-switch-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(18, 12, 34, 0.9);
  border: 1.5px solid rgba(139, 92, 246, 0.4);
  border-radius: 20px;
  padding: 0.2rem 0.45rem;
  cursor: pointer;
  user-select: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.crit-switch-badge:hover:not(.is-disabled) {
  border-color: #a855f7;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.35);
}

.crit-switch-badge.is-active {
  background: rgba(40, 10, 15, 0.95);
  border-color: #ef4444;
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.5);
}

.crit-switch-badge.is-disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.crit-switch-label {
  font-size: 0.46rem;
  color: #94a3b8;
  letter-spacing: 0.4px;
  transition: color 0.2s ease;
}

.crit-switch-badge.is-active .crit-switch-label {
  color: #f87171;
  text-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
}

.crit-switch-track {
  width: 28px;
  height: 14px;
  background: rgba(30, 20, 50, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.4);
  border-radius: 10px;
  position: relative;
  transition: all 0.2s ease;
}

.crit-switch-badge.is-active .crit-switch-track {
  background: #7f1d1d;
  border-color: #ef4444;
}

.crit-switch-knob {
  width: 10px;
  height: 10px;
  background: #94a3b8;
  border-radius: 50%;
  position: absolute;
  top: 1px;
  left: 2px;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.crit-switch-badge.is-active .crit-switch-knob {
  background: #f87171;
  left: 14px;
  box-shadow: 0 0 6px #ef4444;
}

/* 2x2 Action Command Buttons Grid */
.action-choices-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr 1fr;
  gap: 0.55rem;
  flex: 1;
}

/* Individual Action Command Button */
.action-choice-btn {
  background: rgba(30, 20, 56, 0.75);
  border: 1.5px solid rgba(139, 92, 246, 0.4);
  border-radius: 6px;
  padding: 0.65rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-align: left;
  cursor: pointer;
  position: relative;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  overflow: hidden;
  max-height: 110px;
  overflow-y: auto;
}

.action-choice-btn::-webkit-scrollbar {
  width: 4px;
}
.action-choice-btn::-webkit-scrollbar-thumb {
  background: rgba(168, 85, 247, 0.35);
  border-radius: 2px;
}

/* Action Key Badge [ A ] */
.action-key-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  background: rgba(139, 92, 246, 0.3);
  border: 1px solid rgba(168, 85, 247, 0.5);
  color: #fbbf24;
  padding: 0.3rem 0.5rem;
  border-radius: 4px;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

/* Action Choice Text */
.action-choice-text {
  font-size: 0.88rem;
  line-height: 1.5;
  color: #f1f5f9;
  font-weight: 500;
  flex: 1;
  word-break: normal;
  overflow-wrap: break-word;
}

/* Staged / Selected State */
.action-staged-tag {
  font-size: 0.5rem;
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.2);
  border: 1px solid #fbbf24;
  padding: 0.15rem 0.3rem;
  border-radius: 3px;
  flex-shrink: 0;
}

/* Hover & Focus State */
.action-choice-btn:not(:disabled):hover {
  background: rgba(88, 28, 135, 0.5);
  border-color: #c084fc;
  transform: translateY(-2px);
  box-shadow: 0 0 14px rgba(168, 85, 247, 0.5);
}

.action-choice-btn:not(:disabled):hover .action-key-badge {
  background: #7c3aed;
  color: #ffffff;
  border-color: #f59e0b;
}

.action-choice-btn:not(:disabled):active {
  transform: translateY(0);
}

/* Selected State */
.action-choice-btn.state-selected {
  background: rgba(124, 58, 237, 0.45);
  border-color: #fbbf24;
  box-shadow: 0 0 16px rgba(251, 191, 36, 0.5);
}

/* Correct Answer State */
.action-choice-btn.state-correct {
  background: rgba(6, 78, 59, 0.85) !important;
  border-color: #10b981 !important;
  color: #ffffff !important;
  box-shadow: 0 0 18px rgba(52, 211, 153, 0.6) !important;
  animation: correct-pulse 1.2s infinite alternate;
}

.action-choice-btn.state-correct .action-key-badge {
  background: #059669;
  border-color: #34d399;
  color: #ffffff;
}

@keyframes correct-pulse {
  from { box-shadow: 0 0 10px rgba(52, 211, 153, 0.4); }
  to { box-shadow: 0 0 22px rgba(52, 211, 153, 0.8); }
}

/* Wrong Answer State */
.action-choice-btn.state-wrong {
  background: rgba(127, 29, 29, 0.85) !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.6) !important;
}

.action-choice-btn.state-wrong .action-key-badge {
  background: #b91c1c;
  border-color: #f87171;
  color: #ffffff;
}

/* Dimmed non-selected options */
.action-choice-btn.state-dimmed {
  opacity: 0.45;
  filter: grayscale(0.5);
}

.action-choice-btn:disabled {
  cursor: default;
}

/* State Icon Badge */
.action-state-icon {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  flex-shrink: 0;
}
.correct-icon { color: #34d399; }
.wrong-icon { color: #f87171; }

/* Responsive adjustments */
@media (max-width: 900px) {
  .action-choices-grid {
    grid-template-columns: 1fr;
    grid-template-rows: repeat(4, auto);
  }
  .action-choice-btn {
    padding: 0.55rem 0.65rem;
  }
  .crit-speech-bubble {
    right: 0;
    top: calc(100% + 6px);
    transform: none;
  }
  .bubble-arrow-right {
    display: none;
  }
}
</style>
