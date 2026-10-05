<template>
  <div class="archive-phase-fx" :class="`phase-${phase}`" aria-hidden="true">
    <!-- Atmospheric ink haze, intensifies each phase -->
    <div class="fx-haze"></div>

    <!-- Drifting / falling manuscript pages -->
    <span
      v-for="p in pages"
      :key="p.id"
      class="fx-page"
      :style="p.style"
    ></span>

    <!-- Phase 3: the world tears -->
    <div v-if="phase >= 3" class="fx-tear fx-tear-a"></div>
    <div v-if="phase >= 3" class="fx-tear fx-tear-b"></div>
    <div v-if="phase >= 2" class="fx-vignette"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // 1 = The Author, 2 = The Unfinished Script, 3 = The Last Page
  phase: { type: Number, default: 1 }
})

// Deterministic pseudo-random so pages do not re-roll (and restart) on every render
function seeded(i, salt) {
  const x = Math.sin(i * 12.9898 + salt * 78.233) * 43758.5453
  return x - Math.floor(x)
}

const pageCount = computed(() => (props.phase >= 3 ? 26 : props.phase === 2 ? 15 : 6))

const pages = computed(() => {
  const list = []
  for (let i = 0; i < pageCount.value; i++) {
    const left = Math.round(seeded(i, 1) * 100)
    const size = 14 + Math.round(seeded(i, 2) * 18)
    const dur = (props.phase >= 3 ? 4.5 : 9) + seeded(i, 3) * (props.phase >= 3 ? 4 : 8)
    const delay = -seeded(i, 4) * dur
    const sway = 20 + Math.round(seeded(i, 5) * 60)
    list.push({
      id: `${props.phase}-${i}`,
      style: {
        left: `${left}%`,
        width: `${size}px`,
        height: `${Math.round(size * 1.3)}px`,
        animationDuration: `${dur.toFixed(2)}s`,
        animationDelay: `${delay.toFixed(2)}s`,
        '--sway': `${sway}px`,
        opacity: props.phase >= 3 ? 0.8 : 0.55
      }
    })
  }
  return list
})
</script>

<style scoped>
.archive-phase-fx {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
  z-index: 1;
}

.fx-haze {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 50% 30%, rgba(88, 28, 135, 0.10) 0%, transparent 65%);
  transition: background 2.4s ease;
}
.phase-2 .fx-haze {
  background: radial-gradient(ellipse at 50% 30%, rgba(124, 58, 237, 0.22) 0%, rgba(24, 6, 40, 0.35) 70%);
}
.phase-3 .fx-haze {
  background: radial-gradient(ellipse at 50% 25%, rgba(251, 191, 36, 0.16) 0%, rgba(10, 2, 20, 0.6) 75%);
}

.fx-page {
  position: absolute;
  top: -12%;
  background: linear-gradient(135deg, #f4ecd6 0%, #cfc3a2 100%);
  border: 1px solid rgba(0, 0, 0, 0.45);
  box-shadow: 0 0 6px rgba(251, 191, 36, 0.25);
  clip-path: polygon(0 4%, 92% 0, 100% 90%, 8% 100%);
  animation-name: fx-page-fall;
  animation-timing-function: linear;
  animation-iteration-count: infinite;
}
.phase-1 .fx-page { filter: brightness(0.35) saturate(0.4); }
.phase-2 .fx-page { filter: brightness(0.6) saturate(0.7); }

@keyframes fx-page-fall {
  0%   { transform: translate3d(0, 0, 0) rotate(0deg); }
  25%  { transform: translate3d(var(--sway), 28vh, 0) rotate(70deg); }
  50%  { transform: translate3d(calc(var(--sway) * -1), 56vh, 0) rotate(160deg); }
  75%  { transform: translate3d(var(--sway), 84vh, 0) rotate(250deg); }
  100% { transform: translate3d(0, 112vh, 0) rotate(340deg); }
}

.fx-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 45%, rgba(4, 0, 12, 0.7) 100%);
  animation: fx-vignette-breathe 5s ease-in-out infinite;
}
@keyframes fx-vignette-breathe {
  0%, 100% { opacity: 0.75; }
  50% { opacity: 1; }
}

.fx-tear {
  position: absolute;
  width: 3px;
  background: linear-gradient(180deg, transparent, #fbbf24, #fff7d6, transparent);
  box-shadow: 0 0 14px rgba(251, 191, 36, 0.85);
  animation: fx-tear-flicker 2.6s steps(2, end) infinite;
}
.fx-tear-a { left: 31%; top: 6%; height: 52%; transform: rotate(8deg); }
.fx-tear-b { left: 66%; top: 14%; height: 44%; transform: rotate(-12deg); animation-delay: 1.1s; }
@keyframes fx-tear-flicker {
  0%, 60%, 100% { opacity: 0; }
  62%, 90% { opacity: 0.9; }
}

@media (prefers-reduced-motion: reduce) {
  .fx-page, .fx-vignette, .fx-tear { animation: none; }
}
</style>
