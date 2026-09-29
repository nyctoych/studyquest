<template>
  <div class="gothic-loading" :class="{ 'fade-out': fadingOut }">
    <!-- Atmospheric dark background -->
    <div class="gl-bg-layer"></div>

    <!-- Pixel dust particles -->
    <div class="gl-particles">
      <div v-for="i in 18" :key="i" class="gl-particle" :style="particleStyle(i)"></div>
    </div>

    <!-- Boss silhouette — Brain boss as atmospheric centerpiece -->
    <div class="gl-boss-silhouette">
      <img src="/assets/bosses/brain/angry.png" class="gl-boss-img pixel-art" alt="The Devouring Mind" />
    </div>

    <!-- Content -->
    <div class="gl-content animate-scale-up">
      <!-- Gothic title -->
      <div class="gl-logo">
        <div class="gl-psi-frame">
          <span class="gl-psi">Ψ</span>
        </div>
        <h1 class="gl-title">STUDYQUEST</h1>
        <p class="gl-subtitle">— A Psychology Dark Fantasy RPG —</p>
      </div>

      <!-- Gothic separator -->
      <div class="gl-sep">
        <span class="gl-sep-rune">✦</span>
      </div>

      <!-- Boot messages — gothic scroll style -->
      <div class="gl-messages">
        <transition-group name="boot-msg" tag="div" class="gl-msg-list">
          <div v-for="msg in visibleMessages" :key="msg" class="gl-msg">
            <span class="gl-msg-glyph">▶</span>
            <span class="gl-msg-text">{{ msg }}</span>
          </div>
        </transition-group>
      </div>

      <!-- Pixel progress bar -->
      <div class="gl-bar-wrap">
        <div class="gl-bar-fill" :style="{ width: progress + '%' }"></div>
        <!-- Segment overlay -->
        <div class="gl-bar-segments"></div>
      </div>
      <p class="gl-pct">{{ Math.round(progress) }}%</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['done'])

const progress = ref(0)
const fadingOut = ref(false)
const visibleMessages = ref([])

const MESSAGES = [
  'Awakening the dark archive...',
  'Summoning psychology domains...',
  'Loading character soul data...',
  'Entering the mind realm...',
  'The darkness is ready.',
]

function particleStyle(i) {
  const size = 2 + (i % 3)
  return {
    width: size + 'px',
    height: size + 'px',
    left: ((i * 19 + 3) % 100) + '%',
    top: ((i * 29 + 7) % 100) + '%',
    animationDelay: (i * 0.35) + 's',
    animationDuration: (4 + (i % 4)) + 's',
    opacity: 0.2 + (i % 4) * 0.12,
  }
}

onMounted(async () => {
  const steps = [
    { target: 20,  delay: 250,  msg: MESSAGES[0] },
    { target: 45,  delay: 380,  msg: MESSAGES[1] },
    { target: 65,  delay: 350,  msg: MESSAGES[2] },
    { target: 85,  delay: 300,  msg: MESSAGES[3] },
    { target: 100, delay: 350,  msg: MESSAGES[4] },
  ]

  for (const step of steps) {
    await new Promise(r => setTimeout(r, step.delay))
    visibleMessages.value.push(step.msg)
    await animateTo(step.target)
  }

  await new Promise(r => setTimeout(r, 500))
  fadingOut.value = true
  await new Promise(r => setTimeout(r, 700))
  emit('done')
})

function animateTo(target) {
  return new Promise(resolve => {
    const start = progress.value
    const diff = target - start
    const duration = 350
    const startTime = performance.now()
    function step(now) {
      const elapsed = now - startTime
      const t = Math.min(1, elapsed / duration)
      progress.value = start + diff * easeOut(t)
      if (t < 1) requestAnimationFrame(step)
      else resolve()
    }
    requestAnimationFrame(step)
  })
}

function easeOut(t) { return 1 - Math.pow(1 - t, 2) }
</script>

<style scoped>
/* ── LOADING SCREEN ROOT ──────────────── */
.gothic-loading {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gothic-void);
  transition: opacity 0.7s ease;
  overflow: hidden;
}
.gothic-loading.fade-out { opacity: 0; pointer-events: none; }

/* ── ATMOSPHERIC BACKGROUND ──────────── */
.gl-bg-layer {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 40%, rgba(139,26,26,0.08) 0%, transparent 60%),
    radial-gradient(ellipse at 30% 70%, rgba(13,47,74,0.06) 0%, transparent 50%),
    var(--gothic-void);
}

/* ── PIXEL PARTICLES ──────────────────── */
.gl-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}
.gl-particle {
  position: absolute;
  background: var(--gothic-border-lit);
  image-rendering: pixelated;
  animation: pixelDrift ease-in-out infinite;
}

/* ── BOSS SILHOUETTE ──────────────────── */
.gl-boss-silhouette {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}
.gl-boss-img {
  width: min(380px, 60vw);
  height: auto;
  opacity: 0.08;
  filter: brightness(0.3) saturate(0);
  animation: bossIdleFloat 6s ease-in-out infinite;
}

/* ── CONTENT PANEL ────────────────────── */
.gl-content {
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  text-align: center;
  padding: 2.5rem 2rem;
  max-width: 500px;
  width: 100%;
}

/* ── GOTHIC LOGO ──────────────────────── */
.gl-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.gl-psi-frame {
  width: 72px;
  height: 72px;
  border: 2px solid var(--crimson);
  background: var(--gothic-surface);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 2px 2px 0 var(--crimson-void), var(--glow-crimson);
  animation: crimsonThrob 2.5s ease-in-out infinite;
}
.gl-psi-frame::before,
.gl-psi-frame::after {
  content: '';
  position: absolute;
  width: 8px;
  height: 8px;
  background: var(--crimson);
}
.gl-psi-frame::before { top: -4px; left: -4px; }
.gl-psi-frame::after  { bottom: -4px; right: -4px; }

.gl-psi {
  font-size: 2rem;
  font-family: serif;
  color: var(--bone-white);
  text-shadow: var(--glow-bone);
}

.gl-title {
  font-family: var(--font-pixel) !important;
  font-size: var(--px-2xl) !important;
  letter-spacing: 0.12em;
  color: var(--bone-white);
  text-shadow: 2px 2px 0 var(--gothic-void), var(--glow-bone);
  line-height: 1.4;
}
@media (max-width: 480px) { .gl-title { font-size: var(--px-xl) !important; } }

.gl-subtitle {
  font-family: var(--font-gothic);
  font-style: italic;
  font-size: 0.9rem;
  color: var(--text-muted);
  letter-spacing: 0.06em;
}

/* ── GOTHIC SEPARATOR ─────────────────── */
.gl-sep {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}
.gl-sep::before, .gl-sep::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--gothic-border-lit));
}
.gl-sep::after { background: linear-gradient(90deg, var(--gothic-border-lit), transparent); }
.gl-sep-rune {
  color: var(--crimson);
  font-size: 12px;
  animation: runePulse 2s ease-in-out infinite;
}

/* ── BOOT MESSAGES ────────────────────── */
.gl-messages {
  min-height: 5rem;
  width: 100%;
  text-align: left;
}
.gl-msg-list { display: flex; flex-direction: column; gap: 0.35rem; }
.gl-msg {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--font-mono);
  font-size: 0.8rem;
  color: var(--text-muted);
}
.gl-msg-glyph {
  color: var(--crimson);
  font-size: 0.55rem;
  flex-shrink: 0;
}
.gl-msg-text { color: var(--bone-dim); }

.boot-msg-enter-active { transition: all 0.3s ease; }
.boot-msg-enter-from { opacity: 0; transform: translateX(-8px); }

/* ── PIXEL PROGRESS BAR ───────────────── */
.gl-bar-wrap {
  width: 100%;
  height: 12px;
  background: #0a0808;
  border: 2px solid var(--gothic-border);
  position: relative;
  overflow: hidden;
}
.gl-bar-fill {
  height: 100%;
  background: var(--crimson-deep);
  position: relative;
  transition: width 0.2s ease;
}
.gl-bar-fill::after {
  content: '';
  position: absolute;
  inset: 2px;
  background: linear-gradient(90deg, var(--crimson), var(--crimson-bright));
  box-shadow: 0 0 6px var(--crimson);
}
.gl-bar-segments {
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    90deg,
    transparent, transparent 11px,
    rgba(0,0,0,0.5) 11px, rgba(0,0,0,0.5) 12px
  );
  pointer-events: none;
}

.gl-pct {
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  color: var(--text-muted);
  letter-spacing: 0.1em;
}
</style>
