<template>
  <Transition name="bubble-pop">
    <div v-if="text" class="enemy-speech-bubble" role="dialog" aria-live="polite">
      <!-- Retro Pixel Speech Bubble Frame -->
      <div class="bubble-inner">
        <div class="bubble-speaker-tag">
          <span class="bubble-icon">💀</span>
          <span class="bubble-speaker-name">{{ speakerName }}</span>
        </div>
        <p class="bubble-text">“{{ text }}”</p>
      </div>
      <!-- Directional pointer tail pointing toward enemy -->
      <div class="bubble-tail"></div>
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  text: {
    type: String,
    default: ''
  },
  speakerName: {
    type: String,
    default: 'Enemy'
  }
})
</script>

<style scoped>
.enemy-speech-bubble {
  position: absolute;
  top: 12px;
  right: calc(100% + 14px);
  z-index: 50;
  pointer-events: none;
  filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.85));
  white-space: normal;
}

.bubble-inner {
  background: #0d0819;
  background: linear-gradient(180deg, #1d1233 0%, #0d0819 100%);
  border: 2px solid #c084fc;
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.55), inset 0 0 10px rgba(147, 51, 234, 0.25);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  max-width: 300px;
  min-width: 200px;
  position: relative;
}

.bubble-speaker-tag {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #f87171;
  text-transform: uppercase;
  margin-bottom: 0.4rem;
  letter-spacing: 0.5px;
}

.bubble-icon {
  font-size: 0.7rem;
}

.bubble-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  line-height: 1.65;
  color: #fdf4ff;
  margin: 0;
  text-shadow: 0 1px 3px #000;
  word-break: break-word;
}

/* Speech Bubble Tail pointing right toward enemy */
.bubble-tail {
  position: absolute;
  top: 24px;
  right: -10px;
  width: 0;
  height: 0;
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-left: 10px solid #c084fc;
}

.bubble-tail::after {
  content: '';
  position: absolute;
  top: -6px;
  right: 2px;
  width: 0;
  height: 0;
  border-top: 6px solid transparent;
  border-bottom: 6px solid transparent;
  border-left: 8px solid #0d0819;
}

/* Pop In Transition from the right */
.bubble-pop-enter-active {
  animation: bubble-pop-in 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.bubble-pop-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.bubble-pop-leave-to {
  opacity: 0;
  transform: scale(0.9) translateX(12px);
}

@keyframes bubble-pop-in {
  0% {
    opacity: 0;
    transform: scale(0.4) translateX(25px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateX(0);
  }
}

@media (max-width: 860px) {
  .enemy-speech-bubble {
    top: 8px;
    right: calc(100% + 8px);
  }

  .bubble-inner {
    max-width: 220px;
    min-width: 170px;
    padding: 0.55rem 0.8rem;
  }

  .bubble-text {
    font-size: 0.54rem;
    line-height: 1.5;
  }
}

@media (max-width: 580px) {
  .enemy-speech-bubble {
    right: calc(100% + 4px);
    top: 4px;
  }

  .bubble-inner {
    max-width: 170px;
    min-width: 140px;
    padding: 0.45rem 0.65rem;
  }

  .bubble-speaker-tag {
    font-size: 0.46rem;
  }

  .bubble-text {
    font-size: 0.48rem;
  }
}
</style>
