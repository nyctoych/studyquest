<template>
  <div
    v-if="transitionStore.isTransitioning"
    class="game-world-transition-overlay"
    :class="[
      `theme-${transitionStore.destination.themeColor || 'crimson'}`,
      `type-${transitionStore.transitionType || 'gate'}`,
      {
        'is-covered': transitionStore.isCovered,
        'is-morning-mode': gameStore.isMorning
      }
    ]"
    aria-live="polite"
    aria-busy="true"
  >
    <!-- Pixel Shutter Curtain (Multiple interlocking pixel-art darkness blinds) -->
    <div class="pixel-shutter-curtain">
      <div class="shutter-strip strip-1"></div>
      <div class="shutter-strip strip-2"></div>
      <div class="shutter-strip strip-3"></div>
      <div class="shutter-strip strip-4"></div>
      <div class="shutter-strip strip-5"></div>
    </div>

    <!-- Atmospheric Particle & Rune Energy Field -->
    <div class="transition-energy-field">
      <div class="energy-radial-halo"></div>
      <div class="pixel-dust-particles">
        <span class="p-dust d-1"></span>
        <span class="p-dust d-2"></span>
        <span class="p-dust d-3"></span>
        <span class="p-dust d-4"></span>
      </div>
    </div>

    <!-- Central Gothic Travel Emblem & Destination Banner -->
    <div class="travel-destination-card animate-scale-up">
      <div class="emblem-halo-box">
        <div class="pixel-icon-frame">
          <PixelEmoji
            :emoji="transitionStore.destination.icon"
            size="game"
            ariaLabel="Destination icon"
          />
        </div>
      </div>

      <div class="travel-text-group">
        <h2 class="destination-title">{{ transitionStore.destination.title }}</h2>
        <p class="destination-sub">{{ transitionStore.destination.subtitle }}</p>
      </div>

      <!-- Retro pixel loading dots -->
      <div class="pixel-travel-dots">
        <span class="dot d-1"></span>
        <span class="dot d-2"></span>
        <span class="dot d-3"></span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTransitionStore } from '../../stores/transition'
import { useGameStore } from '../../stores/game'

const transitionStore = useTransitionStore()
const gameStore = useGameStore()
</script>

<style scoped>
.game-world-transition-overlay {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: all;
  cursor: wait;
  user-select: none;
  overflow: hidden;
}

/* ── PIXEL SHUTTER CURTAIN ──────────────────────── */
.pixel-shutter-curtain {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  z-index: 1;
}

.shutter-strip {
  flex: 1;
  background: #050608;
  border-bottom: 2px solid #1a151e;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

.shutter-strip:nth-child(even) {
  transform-origin: right;
  background: #08090d;
}

.is-covered .shutter-strip {
  transform: scaleX(1);
}

/* ── ENERGY FIELD ───────────────────────────────── */
.transition-energy-field {
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.25s ease;
}

.is-covered .transition-energy-field {
  opacity: 1;
}

.energy-radial-halo {
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(40px);
}

.theme-crimson .energy-radial-halo {
  background: radial-gradient(circle, rgba(185, 28, 28, 0.35) 0%, transparent 70%);
}

.theme-gold .energy-radial-halo {
  background: radial-gradient(circle, rgba(181, 148, 16, 0.35) 0%, transparent 70%);
}

.theme-violet .energy-radial-halo {
  background: radial-gradient(circle, rgba(139, 92, 246, 0.35) 0%, transparent 70%);
}

.theme-emerald .energy-radial-halo {
  background: radial-gradient(circle, rgba(34, 197, 94, 0.35) 0%, transparent 70%);
}

.theme-stone .energy-radial-halo {
  background: radial-gradient(circle, rgba(100, 116, 139, 0.3) 0%, transparent 70%);
}

/* ── CENTRAL DESTINATION CARD ────────────────────── */
.travel-destination-card {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.2rem;
  padding: 2.2rem 3rem;
  background: rgba(10, 11, 16, 0.95);
  border: 2px solid var(--color-stone-600, #3a3b4c);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.95);
  opacity: 0;
  transform: scale(0.92);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.theme-crimson .travel-destination-card {
  border-color: var(--color-crimson, #8b1a1a);
  box-shadow: 0 0 30px rgba(185, 28, 28, 0.4);
}

.theme-gold .travel-destination-card {
  border-color: var(--color-gold-ancient, #b59410);
  box-shadow: 0 0 30px rgba(181, 148, 16, 0.4);
}

.theme-violet .travel-destination-card {
  border-color: #8b5cf6;
  box-shadow: 0 0 30px rgba(139, 92, 246, 0.4);
}

.theme-emerald .travel-destination-card {
  border-color: #22c55e;
  box-shadow: 0 0 30px rgba(34, 197, 94, 0.4);
}

.is-covered .travel-destination-card {
  opacity: 1;
  transform: scale(1);
}

.emblem-halo-box {
  width: 72px;
  height: 72px;
  background: #151620;
  border: 2px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.8);
}

.theme-crimson .emblem-halo-box { border-color: #b91c1c; }
.theme-gold .emblem-halo-box { border-color: #e6c845; }
.theme-violet .emblem-halo-box { border-color: #a78bfa; }
.theme-emerald .emblem-halo-box { border-color: #4ade80; }

.travel-text-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.4rem;
}

.destination-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 1.5px;
  color: #fff;
  margin: 0;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.theme-crimson .destination-title { color: #fca5a5; }
.theme-gold .destination-title { color: #fde047; }
.theme-violet .destination-title { color: #c4b5fd; }
.theme-emerald .destination-title { color: #86efac; }

.destination-sub {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  margin: 0;
  letter-spacing: 0.5px;
  font-style: italic;
}

/* ── PIXEL TRAVEL DOTS ───────────────────────────── */
.pixel-travel-dots {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dot {
  width: 6px;
  height: 6px;
  background: #555973;
  animation: dotPulse 1.2s infinite ease-in-out;
}

.theme-crimson .dot { background: #b91c1c; }
.theme-gold .dot { background: #e6c845; }
.theme-violet .dot { background: #a78bfa; }

.dot.d-1 { animation-delay: 0s; }
.dot.d-2 { animation-delay: 0.2s; }
.dot.d-3 { animation-delay: 0.4s; }

@keyframes dotPulse {
  0%, 100% { transform: scale(1); opacity: 0.3; }
  50% { transform: scale(1.5); opacity: 1; }
}

/* ── MORNING MODE (LIGHT MODE) STYLES ───────────────── */
.is-morning-mode .shutter-strip {
  background: #F5E8CC !important;
  border-bottom: 2px solid #B88A42 !important;
}

.is-morning-mode .shutter-strip:nth-child(even) {
  background: #EAD8B5 !important;
}

.is-morning-mode .energy-radial-halo {
  background: radial-gradient(circle, rgba(184, 138, 66, 0.28) 0%, transparent 70%) !important;
}

.is-morning-mode .travel-destination-card {
  background: #FFF4DF !important;
  border: 2px solid #B88A42 !important;
  box-shadow: 0 10px 40px rgba(143, 100, 45, 0.28), inset 0 0 15px rgba(255, 255, 255, 0.6) !important;
}

.is-morning-mode .emblem-halo-box {
  background: #EAD8B5 !important;
  border: 2px solid #B88A42 !important;
  box-shadow: inset 0 0 10px rgba(143, 100, 45, 0.25) !important;
}

.is-morning-mode .destination-title {
  color: #3D2B1F !important;
  text-shadow: none !important;
}

.is-morning-mode .destination-sub {
  color: #77634F !important;
}

.is-morning-mode .dot {
  background: #8F642D !important;
}

/* Reduced Motion preference */
@media (prefers-reduced-motion: reduce) {
  .shutter-strip {
    transition: opacity 0.12s ease !important;
    transform: none !important;
    opacity: 0;
  }
  .is-covered .shutter-strip {
    opacity: 1 !important;
  }
  .travel-destination-card {
    transition: opacity 0.12s ease !important;
    transform: none !important;
  }
}
</style>
