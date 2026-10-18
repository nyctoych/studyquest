<template>
  <div v-if="tutorial.isActive || tutorial.showCompletionModal" class="tutorial-overlay-system" :class="{ 'theme-morning': isMorning }">
    <!-- ══════════════════════════════════════════════════════════════
         1. ACTIVE STEP SPOTLIGHT CHAT BUBBLE
         ══════════════════════════════════════════════════════════════ -->
    <template v-if="tutorial.isActive && !tutorial.showCompletionModal">
      <!-- Subtle Backdrop with focused element glow -->
      <div class="spotlight-backdrop" @click="onBackdropClick">
        <!-- Target Element Glow Ring -->
        <div
          v-if="targetRect"
          class="target-element-glow animate-pulse-glow"
          :style="{
            left: `${targetRect.x - 4}px`,
            top: `${targetRect.y - 4}px`,
            width: `${targetRect.width + 8}px`,
            height: `${targetRect.height + 8}px`
          }"
        ></div>
      </div>

      <!-- Floating Speech Chat Bubble pointing at target element -->
      <aside
        class="tutorial-chat-bubble gothic-panel animate-scale-up"
        :class="`bubble-pos-${bubblePos.placement}`"
        :style="bubblePos.style"
        role="dialog"
        aria-labelledby="tut-step-heading"
      >
        <!-- Arrow Tail pointing directly at element -->
        <div
          v-if="bubblePos.placement !== 'none'"
          class="bubble-arrow-tail"
          :class="`tail-${bubblePos.placement}`"
          :style="{ left: `${bubblePos.arrowOffset}px` }"
        ></div>

        <!-- Header Row -->
        <div class="bubble-header-bar">
          <div class="step-badge font-pixel">
            <span class="step-icon">🧭</span>
            <span>{{ currentStepData.name }} ({{ tutorial.currentStep }}/{{ tutorial.totalSteps }})</span>
          </div>

          <button
            type="button"
            class="btn-close-bubble"
            @click="openSkipConfirm"
            title="Skip onboarding tour"
          >
            ✕
          </button>
        </div>

        <!-- Step Title -->
        <h3 id="tut-step-heading" class="bubble-heading font-pixel text-gold">
          {{ currentStepData.heading }}
        </h3>

        <!-- Concise, Non-Overwhelming Explanation -->
        <p class="bubble-explanation font-cinzel">
          {{ currentStepSummary }}
        </p>

        <!-- Footer Navigation -->
        <div class="bubble-footer-nav">
          <button
            v-if="tutorial.currentStep > 1"
            type="button"
            class="btn-nav-ghost font-pixel"
            @click="tutorial.prevStep()"
          >
            ◀ BACK
          </button>
          <div v-else class="nav-spacer"></div>

          <div class="step-dot-indicators">
            <span
              v-for="s in tutorial.steps"
              :key="s.step"
              class="dot"
              :class="{ active: s.step === tutorial.currentStep, passed: s.step < tutorial.currentStep }"
            ></span>
          </div>

          <button
            type="button"
            class="btn-nav-gold font-pixel animate-pulse-glow"
            @click="tutorial.nextStep()"
          >
            <span v-if="tutorial.currentStep < tutorial.totalSteps">NEXT ▶</span>
            <span v-else>FINISH 🏆</span>
          </button>
        </div>
      </aside>
    </template>

    <!-- ══════════════════════════════════════════════════════════════
         2. COMPLETION MODAL: "YOU'RE READY, MINDKEEPER!"
         ══════════════════════════════════════════════════════════════ -->
    <div v-if="tutorial.showCompletionModal" class="tutorial-completion-overlay">
      <div class="completion-dialog gothic-panel animate-scale-up" role="dialog" aria-modal="true">
        <div class="dialog-trophy-cluster">
          <span class="trophy-crest">🏆</span>
        </div>

        <h2 class="completion-title font-pixel text-gold">YOU'RE READY, MINDKEEPER!</h2>

        <p class="completion-message font-cinzel">
          You've explored the foundations of StudyQuest. Review your lessons in Study Hall,
          progress through Adventure Gate, track your achievements, and challenge your
          knowledge whenever you're ready. Your journey through Mindrealm starts now.
        </p>

        <div class="completion-rewards-summary font-pixel">
          <div class="rew-pill">
            <span class="rew-icon">⭐</span>
            <span>+100 EXP</span>
          </div>
          <div class="rew-pill">
            <span class="rew-icon">🪙</span>
            <span>+50 COINS</span>
          </div>
          <div class="rew-pill">
            <span class="rew-icon">❤️</span>
            <span>FULL 100% HEALTH</span>
          </div>
        </div>

        <div class="completion-actions font-pixel">
          <button
            type="button"
            class="btn btn-gothic-gold btn-md btn-enter-realm animate-pulse-glow"
            :disabled="tutorial.loading"
            @click="tutorial.finishTutorial()"
          >
            <span v-if="tutorial.loading">ENTERING REALM…</span>
            <span v-else>ENTER THE REALM 🏰</span>
          </button>

          <button
            type="button"
            class="btn btn-gothic-ghost btn-sm btn-revisit"
            @click="handleRevisit"
          >
            REVISIT TUTORIAL ↺
          </button>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         3. SKIP CONFIRMATION DIALOG
         ══════════════════════════════════════════════════════════════ -->
    <div v-if="tutorial.showSkipConfirm" class="tutorial-skip-dialog-overlay">
      <div class="skip-dialog gothic-panel animate-scale-up" role="dialog" aria-modal="true">
        <div class="skip-icon">⚠️</div>
        <h3 class="skip-title font-pixel text-gold">EXIT ONBOARDING TOUR?</h3>
        <p class="skip-desc font-cinzel">
          Are you sure you want to skip the remaining module tour? You will enter the Citadel Realm directly.
          You can safely replay the entire tutorial anytime from Settings or your Profile.
        </p>
        <div class="skip-actions font-pixel">
          <button
            type="button"
            class="btn btn-gothic-ghost btn-sm"
            @click="tutorial.showSkipConfirm = false"
          >
            CONTINUE TOUR
          </button>
          <button
            type="button"
            class="btn btn-gothic-crimson btn-sm"
            :disabled="tutorial.loading"
            @click="tutorial.skipTutorial()"
          >
            YES, ENTER REALM
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useTutorialStore } from '../../stores/tutorial'
import { useGameStore } from '../../stores/game'

const tutorial = useTutorialStore()
const gameStore = useGameStore()
const route = useRoute()

const isMorning = computed(() => gameStore.isMorning)
const currentStepData = computed(() => tutorial.currentStepData)

// Concise 2-3 sentence explanation for each module
const currentStepSummary = computed(() => {
  const step = currentStepData.value
  if (!step) return ''

  switch (step.id) {
    case 'studyhall':
      return 'The academic library of StudyQuest. Review DSM-5 psychopathology criteria across 12 subjects, use Read Aloud audio, and master concepts that power your battle questions.'
    case 'adventure':
      return 'The main story RPG campaign. Journey across 12 chapters and 120 stages by answering questions, solving dilemmas, and defeating chapter bosses!'
    case 'achievements':
      return 'Track your milestones across study, combat, and progression. Claim bonus coins, EXP, and shareable achievement codex cards!'
    case 'vault':
      return 'Your private study archive. Upload your personal notes, PDFs, and reviewers to study in distraction-free fullscreen reading mode.'
    case 'hall-of-fame':
      return 'The ceremonial honor roll for scholars who passed the official PRC Board Licensure Examination! Submit your passing verification claim here.'
    case 'shop':
      return "Raven's emporium of custom attack and splash spell effects, plus restorative Health Potions that heal 200 Adventure HP and Dodge Potions to safely skip difficult battle questions."
    case 'leaderboard':
      return 'The Hall of Records. Compare your Battle Rating (ELO) and Scholar Level rankings against fellow Mindkeepers across the realm.'
    case 'special-event':
      return "A full two-session mock Board Exam simulator with 450 questions and Bloom's Taxonomy analytics to evaluate your exam readiness."
    case 'multiplayer':
      return 'The Colosseum for real-time 1v1 PvP knowledge battles against fellow psychology students. Unlocks when you reach Level 15!'
    case 'settings':
      return 'Configure background music, SFX volume, Day/Night visual themes, and replay this onboarding tutorial anytime.'
    case 'profile':
      return 'Your scholar identity card. Inspect your character level, stats, equipped cosmetics, and titles in one place.'
    default:
      return step.purpose || ''
  }
})

const targetRect = ref(null)
let pollTimer = null

// Locate spotlight target element in DOM
function locateTarget() {
  if (!tutorial.isActive || tutorial.showCompletionModal) {
    targetRect.value = null
    return
  }

  const step = currentStepData.value
  if (!step) {
    targetRect.value = null
    return
  }

  // Attempt to select designated element(s)
  const selectors = step.selector.split(',').map(s => s.trim())
  let foundEl = null
  for (const sel of selectors) {
    const el = document.querySelector(sel)
    if (el && el.offsetParent !== null) {
      foundEl = el
      break
    }
  }

  if (!foundEl && step.fallbackSelector) {
    foundEl = document.querySelector(step.fallbackSelector)
  }

  if (foundEl) {
    const r = foundEl.getBoundingClientRect()
    if (r.width > 10 && r.height > 10) {
      targetRect.value = {
        x: Math.round(r.left),
        y: Math.round(r.top),
        width: Math.round(r.width),
        height: Math.round(r.height)
      }
      return
    }
  }

  targetRect.value = null
}

function scheduleTargetLocate() {
  clearInterval(pollTimer)
  locateTarget()

  let attempts = 0
  pollTimer = setInterval(() => {
    attempts++
    locateTarget()
    if (targetRect.value || attempts >= 8) {
      clearInterval(pollTimer)
    }
  }, 200)
}

// Compute Chat Bubble Position & Arrow Offset
const bubblePos = computed(() => {
  const rect = targetRect.value
  const bubbleWidth = 340
  const bubbleHeight = 170

  if (!rect) {
    return {
      style: {
        position: 'fixed',
        left: '50%',
        top: '50%',
        transform: 'translate(-50%, -50%)',
        width: `${Math.min(bubbleWidth, window.innerWidth - 32)}px`,
        zIndex: 9999
      },
      placement: 'none',
      arrowOffset: 50
    }
  }

  const vw = window.innerWidth
  const vh = window.innerHeight
  const targetCenterX = rect.x + rect.width / 2

  const actualWidth = Math.min(bubbleWidth, vw - 32)
  let left = targetCenterX - actualWidth / 2
  left = Math.max(16, Math.min(left, vw - actualWidth - 16))

  const spaceAbove = rect.y
  const spaceBelow = vh - (rect.y + rect.height)

  let placement = 'top'
  let style = {
    position: 'fixed',
    left: `${left}px`,
    width: `${actualWidth}px`,
    zIndex: 9999
  }

  if (spaceAbove >= bubbleHeight + 20) {
    placement = 'top'
    style.bottom = `${vh - rect.y + 12}px`
  } else if (spaceBelow >= bubbleHeight + 20) {
    placement = 'bottom'
    style.top = `${rect.y + rect.height + 12}px`
  } else {
    if (spaceAbove >= spaceBelow) {
      placement = 'top'
      style.top = `${Math.max(16, rect.y - bubbleHeight - 12)}px`
    } else {
      placement = 'bottom'
      style.top = `${Math.min(vh - bubbleHeight - 16, rect.y + rect.height + 12)}px`
    }
  }

  const arrowOffset = Math.max(24, Math.min(targetCenterX - left, actualWidth - 24))

  return {
    style,
    placement,
    arrowOffset
  }
})

function openSkipConfirm() {
  tutorial.showSkipConfirm = true
}

function handleRevisit() {
  tutorial.showCompletionModal = false
  tutorial.currentStep = 1
  tutorial.isActive = true
}

function onBackdropClick() {
  // Safe click on backdrop: does not unintentionally trigger underlying elements
}

// Key listeners for keyboard navigation
function onKeyDown(e) {
  if (!tutorial.isActive) return

  if (e.key === 'ArrowRight') {
    tutorial.nextStep()
  } else if (e.key === 'ArrowLeft') {
    tutorial.prevStep()
  } else if (e.key === 'Escape') {
    openSkipConfirm()
  }
}

watch(() => route.path, () => {
  nextTick(() => {
    scheduleTargetLocate()
  })
})

watch(() => tutorial.currentStep, () => {
  nextTick(() => {
    scheduleTargetLocate()
  })
})

watch(() => tutorial.isActive, (active) => {
  if (active) {
    nextTick(() => {
      scheduleTargetLocate()
    })
  } else {
    targetRect.value = null
    clearInterval(pollTimer)
  }
})

onMounted(() => {
  window.addEventListener('resize', locateTarget)
  window.addEventListener('scroll', locateTarget, { passive: true })
  window.addEventListener('keydown', onKeyDown)

  scheduleTargetLocate()
})

onUnmounted(() => {
  clearInterval(pollTimer)
  window.removeEventListener('resize', locateTarget)
  window.removeEventListener('scroll', locateTarget)
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<style scoped>
/* ── OVERLAY CONTAINER ───────────────────────────────────────── */
.tutorial-overlay-system {
  position: fixed;
  inset: 0;
  z-index: 9998;
  pointer-events: none;
  font-family: inherit;
}

/* ── BACKDROP & TARGET GLOW ──────────────────────────────────── */
.spotlight-backdrop {
  position: fixed;
  inset: 0;
  pointer-events: auto;
  z-index: 9990;
  background: rgba(4, 3, 8, 0.45);
}

.target-element-glow {
  position: fixed;
  border-radius: 6px;
  box-shadow: 0 0 0 3px #fbbf24, 0 0 25px rgba(251, 191, 36, 0.7);
  pointer-events: none;
  z-index: 9992;
}

/* ── SPEECH CHAT BUBBLE ──────────────────────────────────────── */
.tutorial-chat-bubble {
  position: fixed;
  z-index: 9999;
  pointer-events: auto;
  padding: 1rem 1.15rem;
  background: radial-gradient(circle at center, #1b162f 0%, #0b0916 100%);
  border: 2px solid #fbbf24;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85), 0 0 20px rgba(251, 191, 36, 0.3);
  border-radius: 8px;
  color: #f3ede2;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

/* Arrow Pointer Tail */
.bubble-arrow-tail {
  position: absolute;
  width: 0;
  height: 0;
  transform: translateX(-50%);
  pointer-events: none;
}

/* When bubble is ABOVE target, arrow points DOWN */
.bubble-arrow-tail.tail-top {
  bottom: -11px;
  border-left: 9px solid transparent;
  border-right: 9px solid transparent;
  border-top: 11px solid #141026;
  filter: drop-shadow(0 2px 2px rgba(251, 191, 36, 0.5));
}
.bubble-arrow-tail.tail-top::before {
  content: '';
  position: absolute;
  bottom: 0px;
  left: -11px;
  border-left: 11px solid transparent;
  border-right: 11px solid transparent;
  border-top: 13px solid #fbbf24;
  z-index: -1;
}

/* When bubble is BELOW target, arrow points UP */
.bubble-arrow-tail.tail-bottom {
  top: -11px;
  border-left: 9px solid transparent;
  border-right: 9px solid transparent;
  border-bottom: 11px solid #141026;
  filter: drop-shadow(0 -2px 2px rgba(251, 191, 36, 0.5));
}
.bubble-arrow-tail.tail-bottom::before {
  content: '';
  position: absolute;
  top: 0px;
  left: -11px;
  border-left: 11px solid transparent;
  border-right: 11px solid transparent;
  border-bottom: 13px solid #fbbf24;
  z-index: -1;
}

.bubble-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(251, 191, 36, 0.2);
  padding-bottom: 0.35rem;
}

.step-badge {
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  color: #fbbf24;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.btn-close-bubble {
  background: none;
  border: none;
  color: #9ca3af;
  font-size: 0.85rem;
  cursor: pointer;
  padding: 0 0.25rem;
  line-height: 1;
}
.btn-close-bubble:hover { color: #f87171; }

.bubble-heading {
  font-size: 0.95rem;
  margin: 0;
  line-height: 1.25;
}

.bubble-explanation {
  font-size: 0.84rem;
  line-height: 1.45;
  color: #e5e7eb;
  margin: 0;
}

.bubble-footer-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 0.5rem;
  margin-top: 0.15rem;
}

.nav-spacer { width: 50px; }

.btn-nav-ghost {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #d1d5db;
  padding: 0.25rem 0.55rem;
  font-size: 0.68rem;
  border-radius: 3px;
  cursor: pointer;
}
.btn-nav-ghost:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

.btn-nav-gold {
  background: #fbbf24;
  color: #0b0916;
  font-weight: 700;
  border: 1px solid #f59e0b;
  padding: 0.25rem 0.65rem;
  font-size: 0.68rem;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 0 10px rgba(251, 191, 36, 0.4);
}
.btn-nav-gold:hover { background: #f59e0b; color: #000; }

.step-dot-indicators { display: flex; gap: 3px; }
.dot {
  width: 5px; height: 5px;
  border-radius: 1px;
  background: rgba(255, 255, 255, 0.2);
}
.dot.passed { background: rgba(251, 191, 36, 0.5); }
.dot.active {
  background: #fbbf24;
  transform: scale(1.25);
  box-shadow: 0 0 5px rgba(251, 191, 36, 0.8);
}

/* ── COMPLETION & SKIP MODALS ────────────────────────────────── */
.tutorial-completion-overlay,
.tutorial-skip-dialog-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(4, 3, 8, 0.88);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  pointer-events: auto;
}

.completion-dialog,
.skip-dialog {
  width: 100%;
  max-width: 520px;
  padding: 2rem 1.75rem;
  text-align: center;
  background: radial-gradient(circle at center, #1c1530 0%, #0a0715 100%);
  border: 2px solid rgba(251, 191, 36, 0.8);
  box-shadow: 0 0 35px rgba(234, 179, 8, 0.35);
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.1rem;
}

.dialog-trophy-cluster { font-size: 3rem; }

.completion-title {
  font-size: 1.35rem;
  margin: 0;
}

.completion-message {
  font-size: 0.95rem;
  line-height: 1.55;
  color: #e5e7eb;
  margin: 0;
}

.completion-rewards-summary {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.65rem;
}

.rew-pill {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.75rem;
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid rgba(251, 191, 36, 0.35);
  border-radius: 4px;
  font-size: 0.75rem;
  color: #fef08a;
}

.completion-actions {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  width: 100%;
  margin-top: 0.25rem;
}

.btn-enter-realm { width: 100%; padding: 0.75rem; font-size: 0.92rem; }
.btn-revisit { width: 100%; }

.skip-icon { font-size: 2.4rem; }
.skip-title { font-size: 1.25rem; margin: 0; }
.skip-desc { font-size: 0.92rem; line-height: 1.5; color: #e5e7eb; margin: 0; }
.skip-actions { display: flex; gap: 1rem; width: 100%; justify-content: center; }

/* ── MORNING THEME ADJUSTMENTS ───────────────────────────────── */
.theme-morning .tutorial-chat-bubble,
.theme-morning .completion-dialog,
.theme-morning .skip-dialog {
  background: radial-gradient(circle at center, #ffffff 0%, #f3f4f6 100%);
  border-color: #d97706;
  color: #1f2937;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
}

.theme-morning .bubble-heading { color: #b45309; }
.theme-morning .bubble-explanation { color: #374151; }
.theme-morning .completion-message { color: #374151; }
</style>
