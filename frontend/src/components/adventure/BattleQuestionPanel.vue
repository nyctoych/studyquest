<template>
  <div class="battle-question-panel gothic-panel" role="region" aria-label="Question Prompt">
    <!-- Header Prompt Bar (Classic RPG "What will the Scholar do?") -->
    <div class="panel-prompt-bar">
      <div class="prompt-left">
        <span class="prompt-icon">⚔️</span>
        <span class="prompt-title">KNOWLEDGE INQUIRY {{ questionIndex + 1 }}</span>
      </div>
      <div v-if="difficulty" class="prompt-badge" :class="'diff-' + difficulty.toLowerCase()">
        {{ formatDifficulty(difficulty) }}
      </div>
    </div>

    <!-- Question Text & Clinical Vignette Container -->
    <div class="question-body-scroll">
      <p class="question-stem-text">
        {{ questionText }}
      </p>

      <!-- Immediate Clinical Rationale & Explanation upon answer -->
      <Transition name="fade-rationale">
        <div v-if="answered && explanation" class="clinical-rationale-box" :class="isCorrect ? 'correct-rationale' : 'wrong-rationale'">
          <div class="rationale-header">
            <span class="rationale-tag">
              {{ isCorrect ? '✔ DOCTRINE CONFIRMED' : '✖ DIAGNOSTIC FRACTURE' }}
            </span>
          </div>
          <p class="rationale-text">{{ explanation }}</p>
        </div>
      </Transition>
    </div>

    <!-- Question Footer: Next Inquiry Action Button -->
    <div v-if="answered" class="panel-action-footer">
      <button
        type="button"
        class="btn btn-next-inquiry"
        :class="{ 'btn-last-inquiry': isLastQuestion }"
        @click="$emit('next')"
      >
        <span>{{ isLastQuestion ? 'CONCLUDE TRIAL' : 'NEXT INQUIRY' }}</span>
        <span class="btn-arrow">➔</span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  questionText: {
    type: String,
    required: true
  },
  questionIndex: {
    type: Number,
    default: 0
  },
  totalQuestions: {
    type: Number,
    default: 1
  },
  difficulty: {
    type: String,
    default: 'standard'
  },
  answered: {
    type: Boolean,
    default: false
  },
  isCorrect: {
    type: Boolean,
    default: false
  },
  explanation: {
    type: String,
    default: ''
  },
  isLastQuestion: {
    type: Boolean,
    default: false
  }
})

defineEmits(['next'])

function formatDifficulty(diff) {
  const map = {
    standard: 'TIER 1 • STANDARD',
    moderate: 'TIER 1 • MODERATE',
    difficult: 'TIER 2 • ADVANCED',
    very_difficult: 'TIER 3 • EXPERT',
    board_style_case: 'TIER 4 • BOARD CASE'
  }
  return map[diff?.toLowerCase()] || diff?.toUpperCase() || 'STANDARD'
}
</script>

<style scoped>
.battle-question-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 220px;
  background: linear-gradient(180deg, rgba(20, 14, 38, 0.96) 0%, rgba(12, 8, 24, 0.98) 100%);
  border: 2px solid rgba(168, 85, 247, 0.5);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7), inset 0 0 10px rgba(139, 92, 246, 0.2);
  border-radius: 8px;
  padding: 0.85rem 1.15rem;
  overflow: hidden;
  position: relative;
}

/* Header prompt bar */
.panel-prompt-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.5rem;
  margin-bottom: 0.65rem;
  flex-shrink: 0;
}

.prompt-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.prompt-icon {
  font-size: 0.8rem;
}

.prompt-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #c4b5fd;
  letter-spacing: 0.5px;
}

.prompt-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.46rem;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(168, 85, 247, 0.4);
  color: #e9d5ff;
}

.prompt-badge.diff-moderate { border-color: #38bdf8; color: #7dd3fc; }
.prompt-badge.diff-difficult { border-color: #fbbf24; color: #fde68a; }
.prompt-badge.diff-very_difficult { border-color: #f472b6; color: #fbcfe8; }
.prompt-badge.diff-board_style_case { border-color: #c084fc; color: #f5d0fe; }

/* Question text scroll container */
.question-body-scroll {
  flex: 1;
  overflow-y: auto;
  padding-right: 0.35rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.question-body-scroll::-webkit-scrollbar {
  width: 5px;
}
.question-body-scroll::-webkit-scrollbar-thumb {
  background: rgba(168, 85, 247, 0.4);
  border-radius: 3px;
}

.question-stem-text {
  font-size: 0.95rem;
  line-height: 1.6;
  color: #f8fafc;
  margin: 0;
  font-weight: 500;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
}

/* Clinical Rationale Box upon answering */
.clinical-rationale-box {
  background: rgba(10, 8, 20, 0.9);
  border-radius: 6px;
  padding: 0.65rem 0.85rem;
  margin-top: 0.4rem;
  border-left: 3px solid;
  animation: rationale-slide 0.3s ease-out;
}

.clinical-rationale-box.correct-rationale {
  border-left-color: #10b981;
  background: rgba(6, 78, 59, 0.25);
}

.clinical-rationale-box.wrong-rationale {
  border-left-color: #ef4444;
  background: rgba(127, 29, 29, 0.25);
}

.rationale-header {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  margin-bottom: 0.3rem;
}

.correct-rationale .rationale-tag { color: #34d399; }
.wrong-rationale .rationale-tag { color: #f87171; }

.rationale-text {
  font-size: 0.82rem;
  line-height: 1.5;
  color: #e2e8f0;
  margin: 0;
}

/* Next Inquiry Footer */
.panel-action-footer {
  margin-top: 0.65rem;
  padding-top: 0.5rem;
  border-top: 1px dashed rgba(139, 92, 246, 0.2);
  display: flex;
  justify-content: flex-end;
  flex-shrink: 0;
}

.btn-next-inquiry {
  background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
  border: 1px solid #c084fc;
  color: #ffffff;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.56rem;
  padding: 0.55rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.4);
  transition: all 0.2s ease;
}

.btn-next-inquiry:hover {
  background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
  transform: translateY(-2px);
  box-shadow: 0 0 18px rgba(168, 85, 247, 0.65);
}

.btn-last-inquiry {
  background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
  border-color: #f87171;
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.5);
}
.btn-last-inquiry:hover {
  background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.7);
}

.btn-arrow {
  font-size: 0.75rem;
}

/* Transitions */
.fade-rationale-enter-active {
  animation: rationale-slide 0.3s ease-out;
}
@keyframes rationale-slide {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 640px) {
  .battle-question-panel {
    min-height: 180px;
    padding: 0.75rem;
  }
  .question-stem-text {
    font-size: 0.88rem;
  }
}
</style>
