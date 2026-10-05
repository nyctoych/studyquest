<template>
  <div class="study-hall-phase phase-1-library">
    <!-- Dedicated Phase 1 Background -->
    <div class="library-backdrop-wrap">
      <img
        :src="bgImage"
        alt="Ancient Dark-Fantasy Library Entrance"
        class="library-bg-img"
      />
      <!-- Atmospheric Lighting & Shimmer Vignette -->
      <div class="library-ambient-vignette"></div>
      <div class="library-candle-glow glow-left"></div>
      <div class="library-candle-glow glow-right"></div>
      <div class="library-portal-glow"></div>

      <!-- Floating Dust / Magic Motes -->
      <div class="magic-dust-field">
        <span class="mote m-1"></span>
        <span class="mote m-2"></span>
        <span class="mote m-3"></span>
        <span class="mote m-4"></span>
        <span class="mote m-5"></span>
        <span class="mote m-6"></span>
      </div>
    </div>

    <!-- Main Entrance Scene Content -->
    <main class="library-entrance-stage animate-scale-up">
      <!-- Title & Lore Banner -->
      <div class="entrance-herald">
        <div class="herald-rune-pill">
          <span class="herald-icon">🏛️</span>
          <span>ANCIENT KNOWLEDGE SANCTUARY</span>
        </div>
        <h1 class="entrance-title">THE STUDY HALL</h1>
        <p class="entrance-sub">
          Review of Mental Disorders &amp; Psychopathology based on DSM-5 Criteria
        </p>
        <p class="entrance-lore-line">
          ✦ Within these halls lie the records of the Mindrealm. ✦
        </p>
      </div>

      <!-- Character Presence (subtly standing in entrance hall) -->
      <div class="entrance-character-presence" v-if="characterSprite">
        <div class="char-aura-halo"></div>
        <img
          :src="characterSprite"
          :alt="auth.nickname"
          class="entrance-char-sprite"
        />
        <div class="char-floor-shadow"></div>
      </div>

      <!-- Prominent Gothic ENTER LIBRARY Button -->
      <div class="enter-interaction-wrap">
        <button
          class="btn-enter-library animate-pulse-glow"
          @click="handleEnter"
          @mouseenter="onHoverButton"
          aria-label="Enter Library Shelves"
        >
          <span class="btn-gothic-border-outer"></span>
          <span class="btn-gothic-border-inner"></span>
          <span class="btn-corner-rune cr-tl">✦</span>
          <span class="btn-corner-rune cr-tr">✦</span>
          <span class="btn-corner-rune cr-bl">✦</span>
          <span class="btn-corner-rune cr-br">✦</span>

          <span class="enter-btn-core">
            <span class="btn-rune-left">⚡</span>
            <span class="btn-label-text">ENTER LIBRARY</span>
            <span class="btn-rune-right">⚡</span>
          </span>
        </button>

        <!-- Quick Summary Badge -->
        <div class="entrance-meta-pill">
          <span>12 Official Grimoires</span>
          <span class="meta-dot">•</span>
          <span>120 Psychiatric Doctrines</span>
          <span class="meta-dot">•</span>
          <span>DSM-5 Criteria</span>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useTransitionStore } from '../../stores/transition'
import { CHARACTER_ANIMATIONS } from '../../config/characterAnimations'
import { resolveAsset } from '../../services/bossMapping'
import { battleAudio } from '../../services/battleAudio'

const props = defineProps({
  onEnterPhase2: {
    type: Function,
    default: null
  }
})

const router = useRouter()
const auth = useAuthStore()
const transitionStore = useTransitionStore()

const bgImage = computed(() => resolveAsset('assets/game/study-hall-library.jpg'))

const characterSprite = computed(() => {
  const key = auth.characterKey || auth.user?.character_key || 'mage'
  const config = CHARACTER_ANIMATIONS[key] || CHARACTER_ANIMATIONS.mage
  return config?.idle || config?.sprite || ''
})

function onHoverButton() {
  try {
    battleAudio.playSelect()
  } catch {}
}

async function handleEnter() {
  try {
    battleAudio.playLibraryEnter()
  } catch {}

  if (props.onEnterPhase2) {
    transitionStore.playPhaseTransition(
      {
        title: 'ARCHIVE OF THE TWELVE VOLUMES',
        subtitle: 'Approaching the ancient grimoire shelves...',
        icon: 'books',
        themeColor: 'violet',
        type: 'library'
      },
      props.onEnterPhase2
    )
  } else {
    const traveled = await transitionStore.triggerTravel('/studyhall/shelves', router)
    if (!traveled) {
      await router.push('/studyhall/shelves')
    }
  }
}
</script>

<style scoped>
.study-hall-phase {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #05040a;
  overflow: hidden;
  user-select: none;
}

/* Background Layers */
.library-backdrop-wrap {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  overflow: hidden;
}

.library-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  image-rendering: pixelated;
  transform: scale(1.02);
  transition: transform 6s ease-out;
}

.library-ambient-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 50%, transparent 35%, rgba(4, 3, 8, 0.6) 75%, rgba(2, 1, 5, 0.9) 100%),
    linear-gradient(to top, rgba(3, 2, 6, 0.9) 0%, transparent 40%, rgba(3, 2, 6, 0.5) 100%);
  pointer-events: none;
}

.library-candle-glow {
  position: absolute;
  width: 250px;
  height: 250px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.18) 0%, transparent 70%);
  pointer-events: none;
  animation: candleFlicker 3s infinite alternate ease-in-out;
}

.glow-left {
  top: 30%;
  left: 15%;
}

.glow-right {
  top: 30%;
  right: 15%;
  animation-delay: 1.5s;
}

.library-portal-glow {
  position: absolute;
  bottom: 25%;
  left: 50%;
  transform: translateX(-50%);
  width: 400px;
  height: 260px;
  background: radial-gradient(ellipse at center, rgba(139, 92, 246, 0.2) 0%, transparent 70%);
  pointer-events: none;
}

/* Floating Magic Dust */
.magic-dust-field {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.mote {
  position: absolute;
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: #fbbf24;
  box-shadow: 0 0 6px #fbbf24;
  opacity: 0.6;
  animation: floatMote 7s infinite ease-in-out;
}

.m-1 { top: 60%; left: 30%; animation-duration: 6s; }
.m-2 { top: 40%; left: 70%; animation-duration: 8s; animation-delay: 1s; }
.m-3 { top: 75%; left: 55%; animation-duration: 5s; animation-delay: 2s; }
.m-4 { top: 50%; left: 20%; animation-duration: 9s; animation-delay: 3s; }
.m-5 { top: 35%; left: 45%; animation-duration: 7s; animation-delay: 0.5s; }
.m-6 { top: 80%; left: 80%; animation-duration: 6.5s; animation-delay: 2.5s; }

@keyframes floatMote {
  0% { transform: translateY(0) scale(0.8); opacity: 0.2; }
  50% { transform: translateY(-30px) scale(1.2); opacity: 0.8; }
  100% { transform: translateY(-60px) scale(0.8); opacity: 0; }
}

@keyframes candleFlicker {
  0% { transform: scale(0.96); opacity: 0.14; }
  100% { transform: scale(1.05); opacity: 0.22; }
}

/* Main Entrance Stage */
.library-entrance-stage {
  position: relative;
  z-index: 10;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: calc(var(--hud-height, 56px) + 0.75rem) 1.5rem 2rem;
  min-height: calc(100vh - var(--hud-height, 56px));
}

.entrance-herald {
  text-align: center;
  max-width: 720px;
}

.herald-rune-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.85rem;
  background: rgba(46, 16, 101, 0.45);
  border: 1px solid rgba(168, 85, 247, 0.35);
  border-radius: 20px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  color: #c084fc;
  box-shadow: 0 0 16px rgba(168, 85, 247, 0.2);
  margin-bottom: 0.75rem;
}

.entrance-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: clamp(2rem, 5vw, 3.25rem);
  font-weight: 900;
  letter-spacing: 0.12em;
  color: #f8fafc;
  text-shadow:
    0 0 20px rgba(168, 85, 247, 0.6),
    0 4px 10px rgba(0, 0, 0, 0.9);
  margin: 0 0 0.5rem;
}

.entrance-sub {
  font-size: clamp(0.85rem, 2vw, 1rem);
  color: #cbd5e1;
  line-height: 1.5;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
}

.entrance-lore-line {
  font-size: 0.82rem;
  font-style: italic;
  letter-spacing: 0.06em;
  color: #fbbf24;
  opacity: 0.85;
  margin: 0.75rem 0 0;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
}

/* Character Presence - Increased to 3x of original size (288px) */
.entrance-character-presence {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0.5rem auto 1rem;
}

.char-aura-halo {
  position: absolute;
  width: 270px;
  height: 270px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, transparent 70%);
  animation: pulseHalo 4s infinite alternate ease-in-out;
}

.entrance-char-sprite {
  position: relative;
  z-index: 2;
  width: 288px;
  height: 288px;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.95));
}

.char-floor-shadow {
  position: absolute;
  bottom: 4px;
  width: 180px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.8);
}

@keyframes pulseHalo {
  0% { transform: scale(0.9); opacity: 0.4; }
  100% { transform: scale(1.15); opacity: 0.8; }
}

/* Enter Button */
.enter-interaction-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.btn-enter-library {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 1rem 3rem;
  background: linear-gradient(180deg, #2e1065 0%, #17072e 100%);
  border: none;
  cursor: pointer;
  user-select: none;
  outline: none;
  border-radius: 4px;
  box-shadow:
    0 8px 28px rgba(0, 0, 0, 0.8),
    0 0 24px rgba(168, 85, 247, 0.4),
    inset 0 0 14px rgba(255, 255, 255, 0.1);
  transition: all 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.btn-gothic-border-outer {
  position: absolute;
  inset: -3px;
  border: 2px solid #a855f7;
  border-radius: 6px;
  pointer-events: none;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.6);
}

.btn-gothic-border-inner {
  position: absolute;
  inset: 2px;
  border: 1px solid rgba(251, 191, 36, 0.5);
  border-radius: 2px;
  pointer-events: none;
}

.btn-corner-rune {
  position: absolute;
  font-size: 0.75rem;
  color: #fbbf24;
  line-height: 1;
  pointer-events: none;
}

.cr-tl { top: -6px; left: -6px; }
.cr-tr { top: -6px; right: -6px; }
.cr-bl { bottom: -6px; left: -6px; }
.cr-br { bottom: -6px; right: -6px; }

.enter-btn-core {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.btn-label-text {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: 0.18em;
  color: #ffffff;
  text-shadow:
    0 0 10px rgba(255, 255, 255, 0.6),
    0 2px 4px rgba(0, 0, 0, 0.8);
}

.btn-rune-left,
.btn-rune-right {
  color: #fbbf24;
  font-size: 1.1rem;
  filter: drop-shadow(0 0 6px #fbbf24);
  transition: transform 0.25s ease;
}

.btn-enter-library:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow:
    0 14px 36px rgba(0, 0, 0, 0.9),
    0 0 32px rgba(168, 85, 247, 0.65),
    inset 0 0 20px rgba(255, 255, 255, 0.2);
}

.btn-enter-library:hover .btn-rune-left {
  transform: translateX(-4px) scale(1.15);
}

.btn-enter-library:hover .btn-rune-right {
  transform: translateX(4px) scale(1.15);
}

.btn-enter-library:active {
  transform: translateY(1px) scale(0.98);
}

.btn-enter-library:focus-visible {
  outline: 2px solid #fbbf24;
  outline-offset: 6px;
}

.entrance-meta-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.06em;
  background: rgba(15, 23, 42, 0.6);
  padding: 0.35rem 0.85rem;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.meta-dot {
  color: #a855f7;
}

@media (max-width: 640px) {
  .btn-enter-library {
    padding: 0.85rem 2rem;
  }
  .btn-label-text {
    font-size: 1.05rem;
    letter-spacing: 0.12em;
  }
}
</style>
