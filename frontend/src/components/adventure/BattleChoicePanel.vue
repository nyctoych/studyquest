<template>
  <div class="battle-choice-panel gothic-panel" role="region" aria-label="Action Commands">
    <!-- Action Command Title Bar -->
    <div class="action-title-bar">
      <span class="action-icon">⚡</span>
      <span class="action-title-text">BATTLE ACTIONS & HYPOTHESES</span>
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
        <span v-if="answered && key === correctAnswer" class="action-state-icon correct-icon">✓</span>
        <span v-else-if="answered && key === selectedKey && key !== correctAnswer" class="action-state-icon wrong-icon">✕</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

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
  }
})

const emit = defineEmits(['select'])

const isLocked = ref(false)

// Reset lock when choices or answered state changes
watch(() => props.answered, (newVal) => {
  if (!newVal) {
    isLocked.value = false
  }
})

function selectChoice(key) {
  if (props.disabled || props.answered || isLocked.value) return
  isLocked.value = true
  emit('select', key)
}

function getChoiceStateClass(key) {
  if (!props.answered) {
    return {
      'state-selected': props.selectedKey === key
    }
  }

  const isTargetCorrect = key === props.correctAnswer
  const isTargetChosen = key === props.selectedKey

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
  overflow: hidden;
}

/* Action Title Bar */
.action-title-bar {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.5rem;
  margin-bottom: 0.65rem;
  flex-shrink: 0;
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

.action-choice-btn {
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
}
</style>
