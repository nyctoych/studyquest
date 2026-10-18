<template>
  <div
    v-if="store.isOpen"
    class="immersive-modal-backdrop"
    :class="{ 'theme-morning': game.isMorning }"
    role="dialog"
    aria-modal="true"
    aria-labelledby="welcome-modal-heading"
    tabindex="-1"
    @keydown="handleKeydown"
  >
    <!-- Background atmospheric vignette -->
    <div class="immersive-modal-overlay" @click.self="handleDismiss"></div>

    <!-- Main Cinematic Modal Panel -->
    <div class="immersive-modal-panel gothic-panel animate-scale-up">
      <!-- Ornate Gothic Corner Runes -->
      <div class="modal-corner tl" aria-hidden="true">✦</div>
      <div class="modal-corner tr" aria-hidden="true">✦</div>
      <div class="modal-corner bl" aria-hidden="true">✦</div>
      <div class="modal-corner br" aria-hidden="true">✦</div>

      <!-- Top Crest & Sub-Tag -->
      <div class="modal-crest-wrap">
        <div class="modal-crest-badge font-pixel">
          <span class="crest-sparkle">✨</span>
          <span>MINDREALM GATEWAY • IMMERSIVE EXPERIENCE</span>
          <span class="crest-sparkle">✨</span>
        </div>
      </div>

      <!-- Main Title & Subtitle -->
      <header class="modal-header">
        <h2 id="welcome-modal-heading" class="modal-title font-cinzel">
          BEFORE YOU ENTER THE REALM
        </h2>
        <p class="modal-subtitle font-cinzel">
          Experience Mindrealm at its fullest.
        </p>
      </header>

      <!-- Feature Recommendation Cards Grid -->
      <div class="recommendations-grid">
        <!-- 1. Fullscreen Feature Card -->
        <div class="rec-card rec-card-fullscreen">
          <div class="rec-icon-box">
            <!-- Sharp Gothic / Pixel Fullscreen Icon -->
            <svg class="rec-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 8V4C3 3.44772 3.44772 3 4 3H8M16 3H20C20.5523 3 21 3.44772 21 4V8M21 16V20C21 20.5523 20.5523 21 20 21H16M8 21H4C3.44772 21 3 20.5523 3 20V16" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
              <rect x="7" y="7" width="10" height="10" rx="1.5" stroke="currentColor" stroke-width="1.8" stroke-dasharray="2 2"/>
            </svg>
            <div class="icon-glow-ring"></div>
          </div>
          <div class="rec-content">
            <h3 class="rec-title font-cinzel">PLAY IN FULLSCREEN</h3>
            <p class="rec-desc">Enjoy a more immersive view of the Realm.</p>
          </div>
        </div>

        <!-- 2. Headphones Feature Card -->
        <div class="rec-card rec-card-headphones">
          <div class="rec-icon-box">
            <!-- Gothic / Pixel Over-Ear Headphones Icon -->
            <svg class="rec-svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 14C3 9.02944 7.02944 5 12 5C16.9706 5 21 9.02944 21 14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
              <rect x="2" y="13" width="5" height="7" rx="2.5" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
              <rect x="17" y="13" width="5" height="7" rx="2.5" fill="currentColor" fill-opacity="0.2" stroke="currentColor" stroke-width="2"/>
              <path d="M7 16H8M16 16H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <div class="icon-glow-ring"></div>
          </div>
          <div class="rec-content">
            <h3 class="rec-title font-cinzel">USE HEADPHONES</h3>
            <p class="rec-desc">Experience the atmosphere and audio details more clearly.</p>
          </div>
        </div>
      </div>

      <!-- Non-intrusive Fullscreen Warning (if browser denies request) -->
      <div v-if="fullscreenNotice" class="fullscreen-notice font-pixel" role="alert">
        <span>⚠</span> {{ fullscreenNotice }}
      </div>

      <!-- Action Buttons Row -->
      <footer class="modal-actions-wrap">
        <!-- Primary Action: Enter with Fullscreen -->
        <button
          type="button"
          class="btn-enter-realm font-cinzel"
          :disabled="isEntering"
          @click="handleEnterWithFullscreen"
        >
          <span class="btn-shine"></span>
          <span class="btn-text">
            <span class="btn-sword-icon">⚔️</span>
            <span>ENTER THE REALM</span>
          </span>
        </button>

        <!-- Secondary Action: Continue Windowed (with countdown timer) -->
        <button
          type="button"
          class="btn-continue-windowed font-cinzel"
          :disabled="isEntering"
          @click="handleContinueWithoutFullscreen"
        >
          CONTINUE WITHOUT FULLSCREEN ({{ countdownSeconds }}s)
        </button>

        <!-- Subtle 3-Second Auto-Close Progress Bar -->
        <div class="auto-timer-bar" title="Auto-closing in 3 seconds">
          <div class="auto-timer-fill"></div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useWelcomeModalStore } from '../../stores/welcomeModal'
import { useGameStore } from '../../stores/game'
import { useToastStore } from '../../stores/toast'

const store = useWelcomeModalStore()
const game = useGameStore()
const toast = useToastStore()

const isEntering = ref(false)
const fullscreenNotice = ref('')
const countdownSeconds = ref(3)

let timerInterval = null
let autoCloseTimeout = null

function clearTimers() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
  if (autoCloseTimeout) {
    clearTimeout(autoCloseTimeout)
    autoCloseTimeout = null
  }
}

/**
 * Start 3-second auto-close countdown.
 */
function startCountdown() {
  clearTimers()
  countdownSeconds.value = 3

  timerInterval = setInterval(() => {
    if (countdownSeconds.value > 1) {
      countdownSeconds.value--
    } else {
      countdownSeconds.value = 0
    }
  }, 1000)

  autoCloseTimeout = setTimeout(() => {
    clearTimers()
    handleContinueWithoutFullscreen()
  }, 3000)
}

watch(
  () => store.isOpen,
  (open) => {
    if (open) {
      startCountdown()
    } else {
      clearTimers()
    }
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  clearTimers()
})

/**
 * Handle Primary Action: Request Fullscreen and proceed into Realm.
 * Directly triggered by user click gesture.
 */
async function handleEnterWithFullscreen() {
  if (isEntering.value) return
  clearTimers()
  isEntering.value = true
  fullscreenNotice.value = ''

  try {
    const docEl = document.documentElement
    const requestFs =
      docEl.requestFullscreen ||
      docEl.webkitRequestFullscreen ||
      docEl.mozRequestFullScreen ||
      docEl.msRequestFullscreen

    const isAlreadyFs = !!(
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.mozFullScreenElement ||
      document.msFullscreenElement
    )

    if (requestFs && !isAlreadyFs) {
      // Direct user action call
      const result = requestFs.call(docEl)
      if (result && typeof result.then === 'function') {
        await result.catch((err) => {
          console.warn('Fullscreen could not be granted:', err)
          fullscreenNotice.value = 'Fullscreen could not be enabled in this browser.'
          toast?.info?.('Entering Realm in windowed mode.')
        })
      }
    }
  } catch (err) {
    console.warn('Fullscreen request error:', err)
    fullscreenNotice.value = 'Fullscreen mode is unavailable on your device.'
  } finally {
    // Proceed into realm and record session acknowledgment
    store.acknowledgeAndClose()
    isEntering.value = false
  }
}

/**
 * Handle Secondary Action / Auto-close: Dismiss modal and continue in windowed mode.
 */
function handleContinueWithoutFullscreen() {
  clearTimers()
  store.acknowledgeAndClose()
}

/**
 * Dismiss via overlay click or escape key (same as continuing without fullscreen)
 */
function handleDismiss() {
  handleContinueWithoutFullscreen()
}

function handleKeydown(e) {
  if (e.key === 'Escape') {
    e.preventDefault()
    handleContinueWithoutFullscreen()
  }
}
</script>

<style scoped>
/* ── BACKDROP & OVERLAY ─────────────────────────────────────────── */
.immersive-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  overflow-y: auto;
  outline: none;
}

.immersive-modal-overlay {
  position: fixed;
  inset: 0;
  background: radial-gradient(circle at center, rgba(10, 11, 16, 0.85) 0%, rgba(3, 4, 7, 0.95) 100%);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  animation: fadeInOverlay 0.3s ease-out;
}

@keyframes fadeInOverlay {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* ── MODAL PANEL ─────────────────────────────────────────────────── */
.immersive-modal-panel {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 580px;
  background: linear-gradient(180deg, #12131a 0%, #090a0f 100%);
  border: 1px solid rgba(212, 175, 55, 0.45);
  box-shadow:
    0 0 0 2px #050608,
    0 24px 64px rgba(0, 0, 0, 0.92),
    0 0 35px rgba(212, 175, 55, 0.18);
  border-radius: 8px;
  padding: 2.2rem 2.2rem 2rem 2.2rem;
  color: #f3ede2;
  text-align: center;
}

/* ── CORNER ORNAMENTS ───────────────────────────────────────────── */
.modal-corner {
  position: absolute;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  color: #ffd700;
  opacity: 0.85;
  user-select: none;
  pointer-events: none;
}
.modal-corner.tl { top: 8px; left: 8px; }
.modal-corner.tr { top: 8px; right: 8px; }
.modal-corner.bl { bottom: 8px; left: 8px; }
.modal-corner.br { bottom: 8px; right: 8px; }

/* ── CREST & HEADERS ────────────────────────────────────────────── */
.modal-crest-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 0.9rem;
}

.modal-crest-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(181, 148, 16, 0.12);
  border: 1px solid rgba(212, 175, 55, 0.35);
  border-radius: 4px;
  padding: 0.35rem 0.75rem;
  color: #f1cf54;
  font-size: 0.62rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  font-weight: 700;
}

.modal-header {
  margin-bottom: 1.5rem;
}

.modal-title {
  font-size: 1.7rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.05em;
  line-height: 1.25;
  margin: 0 0 0.4rem 0;
  text-shadow:
    0 2px 8px rgba(0, 0, 0, 0.9),
    0 0 20px rgba(212, 175, 55, 0.25);
}

.modal-subtitle {
  font-size: 1rem;
  font-weight: 600;
  color: #d4af37;
  letter-spacing: 0.04em;
  margin: 0;
}

/* ── RECOMMENDATIONS GRID ────────────────────────────────────────── */
.recommendations-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.75rem;
  text-align: left;
}

@media (max-width: 540px) {
  .recommendations-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
}

.rec-card {
  display: flex;
  align-items: flex-start;
  gap: 0.9rem;
  background: rgba(18, 20, 28, 0.85);
  border: 1px solid rgba(212, 175, 55, 0.25);
  border-radius: 6px;
  padding: 1.1rem 1rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
  transition: all 0.25s ease;
}

.rec-card:hover {
  border-color: rgba(255, 215, 0, 0.55);
  background: rgba(25, 28, 40, 0.95);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.6), 0 0 16px rgba(212, 175, 55, 0.15);
  transform: translateY(-1px);
}

.rec-icon-box {
  position: relative;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.15) 0%, rgba(139, 26, 26, 0.15) 100%);
  border: 1px solid rgba(212, 175, 55, 0.4);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f6e05e;
}

.rec-svg-icon {
  width: 24px;
  height: 24px;
}

.rec-content {
  flex: 1;
  min-width: 0;
}

.rec-title {
  font-size: 0.82rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.04em;
  margin: 0 0 0.3rem 0;
  line-height: 1.25;
}

.rec-desc {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 0.78rem;
  line-height: 1.4;
  color: #a89f8f;
  margin: 0;
}

/* ── FULLSCREEN NOTICE ───────────────────────────────────────────── */
.fullscreen-notice {
  background: rgba(185, 28, 28, 0.2);
  border: 1px solid #b91c1c;
  color: #fca5a5;
  font-size: 0.65rem;
  padding: 0.45rem 0.75rem;
  border-radius: 4px;
  margin-bottom: 1.25rem;
}

/* ── ACTION BUTTONS ──────────────────────────────────────────────── */
.modal-actions-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: center;
  width: 100%;
}

.btn-enter-realm {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #d4af37 0%, #b59410 50%, #997810 100%);
  color: #0b0c10;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.85rem 1.75rem;
  border-radius: 4px;
  border: 1px solid #ffe066;
  box-shadow:
    0 0 18px rgba(212, 175, 55, 0.5),
    0 4px 14px rgba(0, 0, 0, 0.6);
  cursor: pointer;
  overflow: hidden;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-enter-realm:hover:not(:disabled) {
  background: linear-gradient(135deg, #f5d76e 0%, #d4af37 50%, #b59410 100%);
  transform: translateY(-2px);
  box-shadow:
    0 0 28px rgba(212, 175, 55, 0.8),
    0 6px 20px rgba(0, 0, 0, 0.75);
}

.btn-enter-realm:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-text {
  display: inline-flex;
  align-items: center;
  gap: 0.55rem;
}

.btn-sword-icon {
  font-size: 1.15rem;
}

.btn-continue-windowed {
  background: none;
  border: none;
  color: #9f9483;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  padding: 0.4rem 1rem;
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 4px;
  transition: color 0.2s ease;
}

.btn-continue-windowed:hover:not(:disabled) {
  color: #f3ede2;
}

.btn-continue-windowed:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── 3-SECOND TIMER BAR ─────────────────────────────────────────── */
.auto-timer-bar {
  width: 100%;
  max-width: 260px;
  height: 3px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 2px;
  overflow: hidden;
  margin-top: 0.15rem;
}

.auto-timer-fill {
  height: 100%;
  background: linear-gradient(90deg, #b59410 0%, #ffd700 100%);
  width: 100%;
  animation: countdownShrink 3s linear forwards;
}

@keyframes countdownShrink {
  from { width: 100%; }
  to { width: 0%; }
}

/* ── ANIMATION ───────────────────────────────────────────────────── */
.animate-scale-up {
  animation: modalScaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes modalScaleUp {
  from {
    opacity: 0;
    transform: scale(0.93) translateY(12px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* ══════════════════════════════════════════════════════════════════
   MORNING / LIGHT MODE THEME OVERRIDES
   ══════════════════════════════════════════════════════════════════ */
:global([data-theme="morning"] .immersive-modal-panel),
.theme-morning .immersive-modal-panel {
  background: linear-gradient(180deg, #fffbf2 0%, #f4ebd9 100%) !important;
  border: 2px solid #c9aa6d !important;
  box-shadow: 0 12px 48px rgba(90, 60, 20, 0.25), 0 0 0 1px #8c6708 !important;
  color: #120e07 !important;
}

:global([data-theme="morning"] .modal-title),
.theme-morning .modal-title {
  color: #120e07 !important;
  text-shadow: none !important;
}

:global([data-theme="morning"] .modal-subtitle),
.theme-morning .modal-subtitle {
  color: #7c5208 !important;
}

:global([data-theme="morning"] .rec-card),
.theme-morning .rec-card {
  background: #ffffff !important;
  border: 1.5px solid #d4c4ab !important;
  box-shadow: 0 2px 8px rgba(90, 60, 20, 0.08) !important;
}

:global([data-theme="morning"] .rec-card:hover),
.theme-morning .rec-card:hover {
  border-color: #b88e1a !important;
  background: #fffdf7 !important;
}

:global([data-theme="morning"] .rec-title),
.theme-morning .rec-title {
  color: #120e07 !important;
}

:global([data-theme="morning"] .rec-desc),
.theme-morning .rec-desc {
  color: #55442e !important;
}

:global([data-theme="morning"] .rec-icon-box),
.theme-morning .rec-icon-box {
  background: #fdf5e2 !important;
  border-color: #c9aa6d !important;
  color: #7c5208 !important;
}

:global([data-theme="morning"] .btn-enter-realm),
.theme-morning .btn-enter-realm {
  background: linear-gradient(135deg, #d4af37 0%, #b59410 100%) !important;
  border-color: #8c6708 !important;
  color: #120e07 !important;
}

:global([data-theme="morning"] .btn-continue-windowed),
.theme-morning .btn-continue-windowed {
  color: #5e482f !important;
}

:global([data-theme="morning"] .btn-continue-windowed:hover),
.theme-morning .btn-continue-windowed:hover {
  color: #120e07 !important;
}

:global([data-theme="morning"] .auto-timer-bar),
.theme-morning .auto-timer-bar {
  background: rgba(0, 0, 0, 0.1) !important;
}

:global([data-theme="morning"] .auto-timer-fill),
.theme-morning .auto-timer-fill {
  background: linear-gradient(90deg, #b88e1a 0%, #7c5208 100%) !important;
}
</style>
