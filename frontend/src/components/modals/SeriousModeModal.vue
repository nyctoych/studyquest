<template>
  <Transition name="serious-fade">
    <div
      v-if="seriousStore.showWelcomeModal"
      class="serious-modal-overlay"
      :class="{ 'theme-morning': gameStore.isMorning }"
      @click="seriousStore.dismissWelcomeModal"
      role="alertdialog"
      aria-live="polite"
      aria-label="Serious Mode Activated"
    >
      <div
        class="serious-modal-card"
        :class="{ 'theme-morning': gameStore.isMorning }"
        @click.stop
      >
        <!-- Restrained Seal / Icon -->
        <div class="serious-icon-container">
          <span class="serious-rune">🏛️</span>
          <div class="serious-rune-pulse"></div>
        </div>

        <!-- Notification Content -->
        <div class="serious-content-block">
          <div class="serious-pill-tag">
            <span class="pill-dot"></span>
            <span>ACADEMIC FOCUS STATE</span>
          </div>

          <h3 class="serious-title">SERIOUS MODE ACTIVATED</h3>

          <p class="serious-description">
            Focus on your learning, Mindkeeper. You're now in the Serious Side.
          </p>
        </div>

        <!-- 2-Second Dismiss Timer Track -->
        <div class="serious-timer-bar">
          <div class="serious-timer-fill"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { onUnmounted } from 'vue'
import { useSeriousModeStore } from '../../stores/seriousMode'
import { useGameStore } from '../../stores/game'

const seriousStore = useSeriousModeStore()
const gameStore = useGameStore()

onUnmounted(() => {
  seriousStore.dismissWelcomeModal()
})
</script>

<style scoped>
/* ── Overlay: unobtrusive, non-disruptive backdrop ───────────────── */
.serious-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 5rem;
  background: rgba(4, 2, 8, 0.45);
  backdrop-filter: blur(4px);
  cursor: pointer;
  pointer-events: auto;
}

/* ── Card: restrained, dignified gothic-academic notification ────── */
.serious-modal-card {
  position: relative;
  width: min(92vw, 440px);
  background: linear-gradient(135deg, rgba(22, 16, 38, 0.96) 0%, rgba(12, 8, 24, 0.98) 100%);
  border: 1px solid rgba(167, 139, 250, 0.45);
  box-shadow:
    0 16px 40px rgba(0, 0, 0, 0.8),
    0 0 30px rgba(139, 92, 246, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem 1.75rem 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  cursor: default;
  overflow: hidden;
}

/* ── Restrained Icon / Seal ──────────────────────────────────────── */
.serious-icon-container {
  position: relative;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.25) 0%, rgba(16, 10, 30, 0.8) 100%);
  border: 1px solid rgba(192, 132, 252, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.85rem;
}

.serious-rune {
  font-size: 1.6rem;
  filter: drop-shadow(0 0 8px rgba(192, 132, 252, 0.6));
}

.serious-rune-pulse {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  border: 1px solid rgba(192, 132, 252, 0.3);
  animation: pulseRune 2s infinite ease-out;
}

@keyframes pulseRune {
  0% { transform: scale(1); opacity: 0.8; }
  100% { transform: scale(1.35); opacity: 0; }
}

/* ── Pill Tag ────────────────────────────────────────────────────── */
.serious-pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.2rem 0.65rem;
  border-radius: 999px;
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 0.6rem;
}

.pill-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #a78bfa;
  box-shadow: 0 0 6px #a78bfa;
}

/* ── Title & Description ─────────────────────────────────────────── */
.serious-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #f5f3ff;
  margin: 0 0 0.45rem;
  text-shadow: 0 2px 10px rgba(139, 92, 246, 0.4);
}

.serious-description {
  font-family: 'Inter', -apple-system, sans-serif;
  font-size: 0.86rem;
  line-height: 1.45;
  color: #ddd6fe;
  margin: 0;
}

/* ── 2-Second Timer Progress Bar ─────────────────────────────────── */
.serious-timer-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: rgba(255, 255, 255, 0.08);
  overflow: hidden;
}

.serious-timer-fill {
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #8b5cf6, #c084fc);
  transform-origin: left;
  animation: countdownProgress 2s linear forwards;
}

@keyframes countdownProgress {
  from { transform: scaleX(1); }
  to { transform: scaleX(0); }
}

/* ── Morning / Light Mode Adaptations ────────────────────────────── */
.serious-modal-overlay.theme-morning,
:global(.theme-morning) .serious-modal-overlay,
:global([data-theme="morning"]) .serious-modal-overlay {
  background: rgba(70, 50, 30, 0.35) !important;
}

.serious-modal-card.theme-morning,
:global(.theme-morning) .serious-modal-card,
:global([data-theme="morning"]) .serious-modal-card {
  background: linear-gradient(135deg, #FFFDF9 0%, #F9F3E8 100%) !important;
  border: 1.5px solid #D9C39E !important;
  box-shadow:
    0 16px 40px rgba(143, 100, 45, 0.18),
    0 0 25px rgba(217, 163, 76, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
}

.serious-modal-card.theme-morning .serious-icon-container,
:global(.theme-morning) .serious-modal-card .serious-icon-container,
:global([data-theme="morning"]) .serious-modal-card .serious-icon-container {
  background: radial-gradient(circle, #FDF3D6 0%, #F5E5C9 100%) !important;
  border: 1.5px solid #D9A74A !important;
  box-shadow: 0 4px 12px rgba(184, 138, 66, 0.22) !important;
}

.serious-modal-card.theme-morning .serious-rune,
:global(.theme-morning) .serious-modal-card .serious-rune,
:global([data-theme="morning"]) .serious-modal-card .serious-rune {
  filter: drop-shadow(0 0 6px rgba(217, 163, 76, 0.4)) !important;
}

.serious-modal-card.theme-morning .serious-rune-pulse,
:global(.theme-morning) .serious-modal-card .serious-rune-pulse,
:global([data-theme="morning"]) .serious-modal-card .serious-rune-pulse {
  border-color: rgba(217, 163, 76, 0.45) !important;
}

.serious-modal-card.theme-morning .serious-pill-tag,
:global(.theme-morning) .serious-modal-card .serious-pill-tag,
:global([data-theme="morning"]) .serious-modal-card .serious-pill-tag {
  background: #F3E7CF !important;
  border: 1px solid #D5BF97 !important;
  color: #8C6228 !important;
}

.serious-modal-card.theme-morning .pill-dot,
:global(.theme-morning) .serious-modal-card .pill-dot,
:global([data-theme="morning"]) .serious-modal-card .pill-dot {
  background: #D97706 !important;
  box-shadow: 0 0 6px #D97706 !important;
}

.serious-modal-card.theme-morning .serious-title,
:global(.theme-morning) .serious-modal-card .serious-title,
:global([data-theme="morning"]) .serious-modal-card .serious-title {
  color: #2C1E14 !important;
  text-shadow: none !important;
}

.serious-modal-card.theme-morning .serious-description,
:global(.theme-morning) .serious-modal-card .serious-description,
:global([data-theme="morning"]) .serious-modal-card .serious-description {
  color: #5D4736 !important;
}

.serious-modal-card.theme-morning .serious-timer-bar,
:global(.theme-morning) .serious-modal-card .serious-timer-bar,
:global([data-theme="morning"]) .serious-modal-card .serious-timer-bar {
  background: #E8DBC3 !important;
}

.serious-modal-card.theme-morning .serious-timer-fill,
:global(.theme-morning) .serious-modal-card .serious-timer-fill,
:global([data-theme="morning"]) .serious-modal-card .serious-timer-fill {
  background: linear-gradient(90deg, #B88A42, #E6AF52) !important;
}

/* ── Transitions ─────────────────────────────────────────────────── */
.serious-fade-enter-active,
.serious-fade-leave-active {
  transition: opacity 0.28s ease, transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.serious-fade-enter-from {
  opacity: 0;
  transform: translateY(-16px) scale(0.97);
}

.serious-fade-leave-to {
  opacity: 0;
  transform: translateY(-12px) scale(0.98);
}
</style>
