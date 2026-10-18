<template>
  <div class="gothic-dialogue-box">
    <div class="dialogue-portrait-side" v-if="avatar || speakerIcon">
      <div class="dialogue-avatar-frame">
        <img v-if="avatar" :src="avatar" :alt="speaker" class="dialogue-avatar-img pixel-art" />
        <span v-else class="dialogue-avatar-glyph">{{ speakerIcon || '📜' }}</span>
      </div>
    </div>

    <div class="dialogue-content-side">
      <div class="dialogue-header">
        <span class="dialogue-speaker-name">{{ speaker || 'Scholar of the Mindrealm' }}</span>
        <span v-if="speakerTitle" class="dialogue-speaker-title">{{ speakerTitle }}</span>
      </div>

      <div class="dialogue-body">
        <p class="dialogue-quote">“{{ text }}”</p>
      </div>

      <div v-if="actionText" class="dialogue-footer">
        <button class="btn btn-gothic-crimson btn-xs dialogue-action-btn" @click="$emit('advance')">
          <span>{{ actionText }}</span>
          <span class="btn-arrow">→</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  speaker: {
    type: String,
    default: 'Archivist'
  },
  speakerTitle: {
    type: String,
    default: ''
  },
  text: {
    type: String,
    required: true
  },
  avatar: {
    type: String,
    default: ''
  },
  speakerIcon: {
    type: String,
    default: '🔮'
  },
  actionText: {
    type: String,
    default: ''
  }
})

defineEmits(['advance'])
</script>

<style scoped>
.gothic-dialogue-box {
  display: flex;
  gap: 1rem;
  background: linear-gradient(135deg, rgba(20, 16, 26, 0.95), rgba(10, 8, 14, 0.98));
  border: 1px solid rgba(180, 83, 9, 0.35);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6), inset 0 0 15px rgba(245, 158, 11, 0.05);
  border-radius: 4px;
  padding: 1rem;
  margin: 0.75rem 0;
  position: relative;
}

.gothic-dialogue-box::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(to bottom, #d97706, #991b1b);
}

.dialogue-portrait-side {
  flex-shrink: 0;
}

.dialogue-avatar-frame {
  width: 52px;
  height: 52px;
  border: 2px solid rgba(217, 119, 6, 0.5);
  background: #08060a;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 0 8px rgba(217, 119, 6, 0.3);
}

.dialogue-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.dialogue-avatar-glyph {
  font-size: 1.5rem;
}

.dialogue-content-side {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.dialogue-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 0.35rem;
}

.dialogue-speaker-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #f59e0b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-shadow: 0 0 6px rgba(245, 158, 11, 0.4);
}

.dialogue-speaker-title {
  font-size: 0.7rem;
  color: #94a3b8;
  font-style: italic;
}

.dialogue-body {
  margin-bottom: 0.35rem;
}

.dialogue-quote {
  font-size: 0.85rem;
  line-height: 1.45;
  color: #e2e8f0;
  font-style: italic;
  margin: 0;
}

.dialogue-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.25rem;
}

.dialogue-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.6rem;
}

.btn-arrow {
  transition: transform 0.2s ease;
}

.dialogue-action-btn:hover .btn-arrow {
  transform: translateX(3px);
}

</style>

<style>
/* ══════════════════════════════════════════════════════════════
   DIALOGUE BOX — MORNING / LIGHT MODE STYLES (UNSCOPED GLOBAL)
   ══════════════════════════════════════════════════════════════ */
[data-theme="morning"] .gothic-dialogue-box,
.theme-morning .gothic-dialogue-box {
  background: #FAF2E1 !important;
  border: 1.5px solid #D4C3A3 !important;
  box-shadow: 0 4px 14px rgba(143, 100, 45, 0.08) !important;
}

[data-theme="morning"] .gothic-dialogue-box::before,
.theme-morning .gothic-dialogue-box::before {
  background: linear-gradient(to bottom, #D97706, #B45309) !important;
}

[data-theme="morning"] .dialogue-avatar-frame,
.theme-morning .dialogue-avatar-frame {
  background: #F3E4C8 !important;
  border: 2px solid #B88A42 !important;
  box-shadow: none !important;
}

[data-theme="morning"] .dialogue-speaker-name,
.theme-morning .dialogue-speaker-name {
  color: #8F642D !important;
  text-shadow: none !important;
}

[data-theme="morning"] .dialogue-speaker-title,
.theme-morning .dialogue-speaker-title {
  color: #77634F !important;
}

[data-theme="morning"] .dialogue-quote,
.theme-morning .dialogue-quote {
  color: #2D1E12 !important;
  font-weight: 500 !important;
}
</style>
