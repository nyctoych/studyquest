<template>
  <div class="character-confirm-backdrop" @click.self="$emit('cancel')">
    <div class="confirm-modal-panel gothic-panel animate-scale-up" :class="`theme-${character.class_type.toLowerCase()}`">
      <!-- Corner Rune Accents -->
      <div class="modal-corner tl">✦</div>
      <div class="modal-corner tr">✦</div>
      <div class="modal-corner bl">✦</div>
      <div class="modal-corner br">✦</div>

      <div class="confirm-modal-header">
        <span class="sub-heading font-pixel">VESSEL SELECTION</span>
        <h2 class="main-heading font-cinzel">YOU HAVE CHOSEN</h2>
      </div>

      <div class="chosen-character-hero">
        <div class="chosen-sprite-wrapper">
          <img
            :src="character.sprite_path"
            :alt="character.name"
            class="chosen-sprite pixel-art"
          />
          <div class="chosen-pedestal"></div>
        </div>

        <div class="chosen-details">
          <span class="chosen-class font-pixel">{{ character.class_type }}</span>
          <h3 class="chosen-name font-cinzel">{{ character.name }}</h3>
          <p class="chosen-title font-cinzel">{{ character.title }}</p>

          <!-- Attributes Summary -->
          <div class="chosen-stats-list">
            <div class="stat-badge">
              <span class="stat-icon">❤️</span>
              <span class="stat-name">Health</span>
              <span class="stat-num font-pixel">{{ character.health }}</span>
            </div>
            <div class="stat-badge">
              <span class="stat-icon">⚔️</span>
              <span class="stat-name">Damage</span>
              <span class="stat-num font-pixel">{{ character.damage }}</span>
            </div>
            <div class="stat-badge">
              <span class="stat-icon">⚡</span>
              <span class="stat-name">Crit Dmg</span>
              <span class="stat-num font-pixel">{{ character.crit_damage }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="confirm-warning-notice">
        <span class="notice-icon">⚠️</span>
        <p class="notice-text">
          This choice forges your combat identity for the trials ahead. Are you ready to enter the Mindrealm?
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="confirm-actions">
        <button
          type="button"
          class="btn-cancel font-cinzel"
          :disabled="isSubmitting"
          @click="$emit('cancel')"
        >
          ← CHANGE CHARACTER
        </button>

        <button
          type="button"
          class="btn-confirm font-cinzel"
          :disabled="isSubmitting"
          @click="$emit('confirm')"
        >
          <span v-if="isSubmitting" class="submitting-text">INSCRIBING SOUL PACT…</span>
          <span v-else class="confirm-label">⚔️ CONFIRM CHARACTER</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  character: {
    type: Object,
    required: true
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
})

defineEmits(['confirm', 'cancel'])
</script>

<style scoped>
.character-confirm-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(4, 3, 8, 0.88);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  z-index: 1000;
}

.confirm-modal-panel {
  position: relative;
  max-width: 520px;
  width: 100%;
  background: linear-gradient(180deg, rgba(22, 16, 32, 0.98) 0%, rgba(10, 8, 16, 0.99) 100%);
  border: 2px solid rgba(168, 85, 247, 0.6);
  border-radius: 6px;
  padding: 2rem 1.8rem;
  box-shadow: 0 16px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(168, 85, 247, 0.35);
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.theme-melee.confirm-modal-panel {
  border-color: rgba(239, 68, 68, 0.6);
  box-shadow: 0 16px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(239, 68, 68, 0.35);
}

.theme-range.confirm-modal-panel {
  border-color: rgba(45, 212, 191, 0.6);
  box-shadow: 0 16px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(45, 212, 191, 0.35);
}

/* Corner runes */
.modal-corner {
  position: absolute;
  color: #c084fc;
  font-size: 0.8rem;
  opacity: 0.7;
}

.theme-melee .modal-corner { color: #f87171; }
.theme-range .modal-corner { color: #5eead4; }

.modal-corner.tl { top: 8px; left: 8px; }
.modal-corner.tr { top: 8px; right: 8px; }
.modal-corner.bl { bottom: 8px; left: 8px; }
.modal-corner.br { bottom: 8px; right: 8px; }

/* Header */
.confirm-modal-header {
  text-align: center;
}

.sub-heading {
  font-size: 0.55rem;
  color: #a8a29e;
  letter-spacing: 0.16em;
}

.main-heading {
  font-size: 1.6rem;
  font-weight: 800;
  color: #f5efe6;
  letter-spacing: 0.08em;
  margin-top: 0.2rem;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8);
}

/* Hero Section */
.chosen-character-hero {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  background: rgba(14, 10, 22, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  padding: 1.1rem;
}

.chosen-sprite-wrapper {
  position: relative;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.chosen-sprite {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.8));
  z-index: 2;
}

.chosen-pedestal {
  position: absolute;
  bottom: 4px;
  width: 90px;
  height: 16px;
  background: radial-gradient(ellipse at center, rgba(168, 85, 247, 0.5) 0%, transparent 75%);
  border-radius: 50%;
  z-index: 1;
}

.theme-melee .chosen-pedestal {
  background: radial-gradient(ellipse at center, rgba(239, 68, 68, 0.5) 0%, transparent 75%);
}

.theme-range .chosen-pedestal {
  background: radial-gradient(ellipse at center, rgba(45, 212, 191, 0.5) 0%, transparent 75%);
}

.chosen-details {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex: 1;
}

.chosen-class {
  font-size: 0.55rem;
  letter-spacing: 0.12em;
  color: #c084fc;
}

.theme-melee .chosen-class { color: #f87171; }
.theme-range .chosen-class { color: #5eead4; }

.chosen-name {
  font-size: 1.35rem;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.04em;
  line-height: 1.1;
}

.chosen-title {
  font-size: 0.76rem;
  color: #a8a29e;
  letter-spacing: 0.04em;
  margin-bottom: 0.5rem;
}

.chosen-stats-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.stat-badge {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(22, 17, 34, 0.9);
  padding: 0.25rem 0.5rem;
  border-radius: 3px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.7rem;
}

.stat-name {
  color: #a8a29e;
  font-size: 0.65rem;
}

.stat-num {
  font-size: 0.6rem;
  color: #e2d9cc;
}

/* Notice */
.confirm-warning-notice {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 0.9rem;
  background: rgba(30, 20, 15, 0.5);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 3px;
}

.notice-icon {
  font-size: 1rem;
}

.notice-text {
  font-size: 0.76rem;
  color: #fed7aa;
  line-height: 1.4;
  margin: 0;
}

/* Actions */
.confirm-actions {
  display: flex;
  gap: 0.85rem;
  margin-top: 0.5rem;
}

.btn-cancel {
  flex: 1;
  padding: 0.75rem 1rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1.5px solid rgba(255, 255, 255, 0.18);
  color: #d6d3d1;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.35);
}

.btn-confirm {
  flex: 1.6;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(180deg, #7e22ce 0%, #581c87 100%);
  border: 1.5px solid #a855f7;
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(126, 34, 206, 0.4);
  transition: all 0.2s;
}

.theme-melee .btn-confirm {
  background: linear-gradient(180deg, #b91c1c 0%, #7f1d1d 100%);
  border-color: #ef4444;
  box-shadow: 0 4px 16px rgba(185, 28, 28, 0.4);
}

.theme-range .btn-confirm {
  background: linear-gradient(180deg, #0f766e 0%, #115e59 100%);
  border-color: #14b8a6;
  box-shadow: 0 4px 16px rgba(15, 118, 110, 0.4);
}

.btn-confirm:hover:not(:disabled) {
  transform: translateY(-2px);
  filter: brightness(1.15);
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.6);
}

.btn-confirm:disabled,
.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .chosen-character-hero {
    flex-direction: column;
    text-align: center;
  }
  .confirm-actions {
    flex-direction: column-reverse;
  }
}
</style>
