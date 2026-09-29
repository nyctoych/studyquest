<template>
  <div class="submit-modal-overlay" @click.self="$emit('close')">
    <div class="submit-modal-card gothic-panel animate-scale-up">
      <div class="modal-header">
        <div class="flex items-center gap-2">
          <PixelEmoji emoji="shield" size="md" />
          <h2 class="modal-title">FINAL TRIAL SUBMISSION</h2>
        </div>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="modal-body">
        <p class="summary-lead">
          Are you ready to seal your examination answers? Once submitted, the trial is completed and your score will be authoritative.
        </p>

        <!-- Stats Grid -->
        <div class="stats-summary-grid">
          <div class="summary-cell">
            <span class="cell-label">TOTAL QUESTIONS</span>
            <span class="cell-val">{{ totalQuestions }}</span>
          </div>
          <div class="summary-cell answered">
            <span class="cell-label">ANSWERED</span>
            <span class="cell-val">{{ answeredCount }}</span>
          </div>
          <div class="summary-cell" :class="{ warning: unansweredCount > 0 }">
            <span class="cell-label">UNANSWERED</span>
            <span class="cell-val">{{ unansweredCount }}</span>
          </div>
          <div class="summary-cell marked">
            <span class="cell-label">MARKED FOR REVIEW</span>
            <span class="cell-val">{{ markedCount }}</span>
          </div>
        </div>

        <!-- Warning if unanswered questions exist -->
        <div v-if="unansweredCount > 0" class="warning-callout flex items-center gap-2">
          <PixelEmoji emoji="cross" size="sm" />
          <span>
            <strong>Attention Scholar:</strong> You have <strong>{{ unansweredCount }}</strong> unanswered question(s). Unanswered questions will receive zero points.
          </span>
        </div>

        <div v-else class="success-callout flex items-center gap-2">
          <PixelEmoji emoji="sparkles" size="sm" />
          <span>All 140 questions have been addressed. Excellent discipline.</span>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-cancel" @click="$emit('close')">
          Return to Examination
        </button>
        <button
          class="btn-confirm-submit"
          :disabled="isSubmitting"
          @click="$emit('confirm')"
        >
          <span v-if="!isSubmitting">Seal & Submit Trial</span>
          <span v-else>Submitting to Grand Archives...</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  totalQuestions: {
    type: Number,
    default: 140
  },
  answeredCount: {
    type: Number,
    default: 0
  },
  unansweredCount: {
    type: Number,
    default: 0
  },
  markedCount: {
    type: Number,
    default: 0
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
})

defineEmits(['close', 'confirm'])
</script>

<style scoped>
.submit-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(4, 2, 10, 0.85);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.submit-modal-card {
  width: 100%;
  max-width: 520px;
  background: #110e20;
  border: 2px solid #8b5cf6;
  border-radius: 10px;
  box-shadow: 0 0 30px rgba(139, 92, 246, 0.4), 0 20px 40px rgba(0, 0, 0, 0.9);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(139, 92, 246, 0.3);
  padding-bottom: 0.85rem;
}

.modal-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  color: #f1f5f9;
  letter-spacing: 1px;
  font-weight: 700;
  margin: 0;
}

.close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.25rem;
}
.close-btn:hover {
  color: #f43f5e;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-lead {
  font-size: 0.85rem;
  color: #cbd5e1;
  line-height: 1.45;
}

.stats-summary-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.summary-cell {
  background: rgba(22, 18, 42, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 6px;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-cell .cell-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.summary-cell .cell-val {
  font-size: 1.35rem;
  font-weight: bold;
  color: #f8fafc;
}

.summary-cell.answered .cell-val {
  color: #60a5fa;
}

.summary-cell.marked .cell-val {
  color: #fbbf24;
}

.summary-cell.warning {
  border-color: rgba(244, 63, 94, 0.5);
  background: rgba(244, 63, 94, 0.1);
}
.summary-cell.warning .cell-val {
  color: #f43f5e;
}

.warning-callout {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  border-radius: 6px;
  padding: 0.75rem;
  font-size: 0.78rem;
  line-height: 1.4;
}

.success-callout {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #6ee7b7;
  border-radius: 6px;
  padding: 0.75rem;
  font-size: 0.78rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  border-top: 1px solid rgba(139, 92, 246, 0.2);
  padding-top: 1rem;
}

.btn-cancel {
  background: rgba(30, 27, 50, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #cbd5e1;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-cancel:hover {
  background: rgba(50, 44, 82, 0.9);
  color: #fff;
}

.btn-confirm-submit {
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
  border: 1px solid #a78bfa;
  color: #fff;
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 0 15px rgba(124, 58, 237, 0.5);
  transition: all 0.2s;
}
.btn-confirm-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 0 20px rgba(124, 58, 237, 0.8);
}
.btn-confirm-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.animate-scale-up {
  animation: scaleUp 0.2s ease-out;
}
@keyframes scaleUp {
  from { opacity: 0; transform: scale(0.92); }
  to { opacity: 1; transform: scale(1); }
}
</style>
