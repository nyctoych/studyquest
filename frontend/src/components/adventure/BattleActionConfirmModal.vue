<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div
        v-if="isOpen"
        class="battle-confirm-backdrop"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title"
        @click.self="handleCancel"
      >
        <div
          class="battle-confirm-modal gothic-panel"
          :class="{ 'is-boss-theme': isBoss }"
        >
          <!-- Corner Runic Accents -->
          <div class="corner-rune top-left">✦</div>
          <div class="corner-rune top-right">✦</div>
          <div class="corner-rune bottom-left">✦</div>
          <div class="corner-rune bottom-right">✦</div>

          <!-- Header -->
          <div class="confirm-header">
            <div class="confirm-eyebrow font-pixel">
              <span class="pulse-dot">⚡</span>
              <span>COMBAT ACTION CONFIRMATION</span>
            </div>
            <h2 id="confirm-modal-title" class="confirm-title font-cinzel">
              EXECUTE ATTACK?
            </h2>
            <div v-if="targetName" class="confirm-target-row">
              <span class="target-label font-pixel">TARGET:</span>
              <span class="target-name font-cinzel">{{ targetName }}</span>
              <span v-if="targetTitle" class="target-title">({{ targetTitle }})</span>
            </div>
          </div>

          <!-- Question Context Snippet -->
          <div v-if="questionText" class="confirm-inquiry-box">
            <div class="inquiry-header font-pixel">
              <span>📜 ACTIVE INQUIRY</span>
            </div>
            <p class="inquiry-text">{{ questionText }}</p>
          </div>

          <!-- Selected Hypothesis Card -->
          <div class="confirm-choice-card">
            <div class="choice-card-header">
              <span class="staged-badge font-pixel">STAGED HYPOTHESIS</span>
              <span v-if="abilityName" class="ability-badge font-pixel">⚔️ {{ abilityName }}</span>
            </div>
            <div class="choice-card-body">
              <span class="choice-key-badge font-pixel">{{ selectedKey }}</span>
              <div class="choice-text-wrap">
                <span class="choice-text">{{ choiceText }}</span>
              </div>
            </div>
          </div>

          <!-- Prompt Warning Note -->
          <p class="confirm-prompt-note">
            Ready to unleash this knowledge strike? Confirming will commit your battle action.
          </p>

          <!-- Action Buttons Row -->
          <div class="confirm-actions-row">
            <button
              type="button"
              class="btn-confirm-cancel font-cinzel"
              @click="handleCancel"
            >
              <span class="btn-icon">✕</span>
              <span class="btn-label">CHANGE CHOICE</span>
              <kbd class="kbd-hint font-pixel">ESC</kbd>
            </button>

            <button
              type="button"
              class="btn-confirm-execute font-cinzel"
              @click="handleConfirm"
            >
              <span class="btn-icon">⚔️</span>
              <span class="btn-label">CONFIRM & ATTACK!</span>
              <kbd class="kbd-hint font-pixel">ENTER</kbd>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { watch, onMounted, onUnmounted } from 'vue'
import { battleAudio } from '../../services/battleAudio'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  targetName: {
    type: String,
    default: ''
  },
  targetTitle: {
    type: String,
    default: ''
  },
  questionText: {
    type: String,
    default: ''
  },
  selectedKey: {
    type: String,
    default: ''
  },
  choiceText: {
    type: String,
    default: ''
  },
  abilityName: {
    type: String,
    default: ''
  },
  isBoss: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['confirm', 'cancel'])

function handleKeyDown(e) {
  if (!props.isOpen) return
  if (e.key === 'Escape') {
    e.preventDefault()
    handleCancel()
  } else if (e.key === 'Enter') {
    e.preventDefault()
    handleConfirm()
  }
}

watch(() => props.isOpen, (open) => {
  if (open) {
    window.addEventListener('keydown', handleKeyDown)
  } else {
    window.removeEventListener('keydown', handleKeyDown)
  }
})

onMounted(() => {
  if (props.isOpen) {
    window.addEventListener('keydown', handleKeyDown)
  }
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})

function handleCancel() {
  battleAudio.playSelect()
  emit('cancel')
}

function handleConfirm() {
  emit('confirm')
}
</script>

<style scoped>
/* ── BACKDROP & TRANSITION ── */
.battle-confirm-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(6, 3, 14, 0.82);
  backdrop-filter: blur(7px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  box-sizing: border-box;
}

.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}

.confirm-fade-enter-from .battle-confirm-modal,
.confirm-fade-leave-to .battle-confirm-modal {
  transform: scale(0.92) translateY(10px);
}

/* ── MODAL CONTAINER ── */
.battle-confirm-modal {
  position: relative;
  width: 100%;
  max-width: 530px;
  background: linear-gradient(180deg, #170d2b 0%, #0d0618 100%);
  border: 2px solid rgba(251, 191, 36, 0.65);
  border-radius: 10px;
  box-shadow: 0 16px 50px rgba(0, 0, 0, 0.85), 0 0 28px rgba(251, 191, 36, 0.25);
  padding: 1.5rem 1.6rem 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  color: #f1f5f9;
  animation: modal-enter 0.25s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
  box-sizing: border-box;
}

/* Boss Variant Styling */
.battle-confirm-modal.is-boss-theme {
  border-color: rgba(239, 68, 68, 0.75);
  box-shadow: 0 16px 50px rgba(0, 0, 0, 0.9), 0 0 32px rgba(239, 68, 68, 0.35);
  background: linear-gradient(180deg, #200814 0%, #0d040a 100%);
}

@keyframes modal-enter {
  0% {
    opacity: 0;
    transform: scale(0.93) translateY(8px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* ── CORNER RUNES ── */
.corner-rune {
  position: absolute;
  font-size: 0.85rem;
  color: #fbbf24;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.8);
  pointer-events: none;
  line-height: 1;
}
.is-boss-theme .corner-rune {
  color: #f87171;
  text-shadow: 0 0 8px rgba(239, 68, 68, 0.8);
}
.corner-rune.top-left { top: 6px; left: 8px; }
.corner-rune.top-right { top: 6px; right: 8px; }
.corner-rune.bottom-left { bottom: 6px; left: 8px; }
.corner-rune.bottom-right { bottom: 6px; right: 8px; }

/* ── HEADER ── */
.confirm-header {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.75rem;
}

.confirm-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.55rem;
  color: #fbbf24;
  letter-spacing: 1px;
}
.pulse-dot {
  animation: pulse-icon 1s infinite alternate;
}
@keyframes pulse-icon {
  from { opacity: 0.6; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1.15); }
}

.confirm-title {
  margin: 0;
  font-size: 1.45rem;
  color: #ffffff;
  letter-spacing: 1.5px;
  font-weight: 700;
  text-shadow: 0 0 14px rgba(251, 191, 36, 0.45);
}
.is-boss-theme .confirm-title {
  color: #fee2e2;
  text-shadow: 0 0 16px rgba(239, 68, 68, 0.6);
}

.confirm-target-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.82rem;
  margin-top: 0.2rem;
}
.target-label {
  font-size: 0.52rem;
  color: #94a3b8;
}
.target-name {
  color: #f59e0b;
  font-weight: 600;
  letter-spacing: 0.5px;
}
.is-boss-theme .target-name {
  color: #f87171;
}
.target-title {
  color: #94a3b8;
  font-size: 0.75rem;
  font-style: italic;
}

/* ── ACTIVE INQUIRY BOX ── */
.confirm-inquiry-box {
  background: rgba(14, 8, 26, 0.75);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 6px;
  padding: 0.65rem 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  max-height: 110px;
  overflow-y: auto;
}
.confirm-inquiry-box::-webkit-scrollbar {
  width: 4px;
}
.confirm-inquiry-box::-webkit-scrollbar-thumb {
  background: rgba(168, 85, 247, 0.35);
  border-radius: 2px;
}
.inquiry-header {
  font-size: 0.5rem;
  color: #c4b5fd;
  letter-spacing: 0.5px;
}
.inquiry-text {
  margin: 0;
  font-size: 0.84rem;
  line-height: 1.45;
  color: #cbd5e1;
}

/* ── STAGED CHOICE CARD ── */
.confirm-choice-card {
  background: linear-gradient(180deg, rgba(88, 28, 135, 0.3) 0%, rgba(30, 20, 56, 0.6) 100%);
  border: 1.5px solid rgba(251, 191, 36, 0.55);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  box-shadow: 0 0 16px rgba(251, 191, 36, 0.15);
}
.is-boss-theme .confirm-choice-card {
  border-color: rgba(248, 113, 113, 0.55);
  background: linear-gradient(180deg, rgba(127, 29, 29, 0.3) 0%, rgba(45, 10, 20, 0.6) 100%);
  box-shadow: 0 0 16px rgba(239, 68, 68, 0.15);
}

.choice-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.staged-badge {
  font-size: 0.52rem;
  color: #fbbf24;
  letter-spacing: 0.5px;
}
.is-boss-theme .staged-badge {
  color: #f87171;
}
.ability-badge {
  font-size: 0.52rem;
  color: #67e8f9;
  background: rgba(6, 182, 212, 0.15);
  border: 1px solid rgba(6, 182, 212, 0.4);
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
}

.choice-card-body {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}
.choice-key-badge {
  font-size: 0.8rem;
  background: #7c3aed;
  color: #ffffff;
  border: 1.5px solid #fbbf24;
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  flex-shrink: 0;
  box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
}
.is-boss-theme .choice-key-badge {
  background: #dc2626;
  border-color: #fca5a5;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
}

.choice-text-wrap {
  flex: 1;
  max-height: 100px;
  overflow-y: auto;
}
.choice-text-wrap::-webkit-scrollbar {
  width: 4px;
}
.choice-text-wrap::-webkit-scrollbar-thumb {
  background: rgba(168, 85, 247, 0.35);
  border-radius: 2px;
}
.choice-text {
  font-size: 0.95rem;
  line-height: 1.45;
  color: #f8fafc;
  font-weight: 500;
}

/* ── PROMPT NOTE ── */
.confirm-prompt-note {
  margin: 0;
  font-size: 0.78rem;
  text-align: center;
  color: #94a3b8;
}

/* ── ACTION BUTTONS ROW ── */
.confirm-actions-row {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 0.75rem;
  margin-top: 0.2rem;
}

/* Cancel Button */
.btn-confirm-cancel {
  background: rgba(30, 20, 56, 0.8);
  border: 1.5px solid rgba(148, 163, 184, 0.35);
  border-radius: 6px;
  color: #cbd5e1;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.75rem 0.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  cursor: pointer;
  transition: all 0.2s ease;
  letter-spacing: 0.5px;
}
.btn-confirm-cancel:hover {
  background: rgba(51, 35, 90, 0.9);
  border-color: #94a3b8;
  color: #ffffff;
  transform: translateY(-1px);
}

/* Confirm & Attack Button */
.btn-confirm-execute {
  background: linear-gradient(135deg, #b91c1c 0%, #dc2626 50%, #f59e0b 100%);
  border: 1.5px solid #fbbf24;
  border-radius: 6px;
  color: #ffffff;
  font-size: 0.92rem;
  font-weight: 700;
  padding: 0.75rem 0.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
  letter-spacing: 1px;
  box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5), 0 0 12px rgba(251, 191, 36, 0.4);
  animation: attack-btn-pulse 1.8s ease-in-out infinite alternate;
}
.btn-confirm-execute:hover {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #fbbf24 100%);
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 6px 20px rgba(220, 38, 38, 0.7), 0 0 18px rgba(251, 191, 36, 0.6);
}
.btn-confirm-execute:active {
  transform: translateY(0) scale(1);
}

@keyframes attack-btn-pulse {
  from {
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.4), 0 0 10px rgba(251, 191, 36, 0.3);
  }
  to {
    box-shadow: 0 6px 22px rgba(220, 38, 38, 0.7), 0 0 20px rgba(251, 191, 36, 0.6);
  }
}

.kbd-hint {
  font-size: 0.48rem;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  opacity: 0.8;
  margin-left: 0.25rem;
}

@media (max-width: 500px) {
  .confirm-actions-row {
    grid-template-columns: 1fr;
  }
  .btn-confirm-execute {
    order: -1;
  }
}
</style>
