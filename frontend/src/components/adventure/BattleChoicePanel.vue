<template>
  <div class="battle-choice-panel gothic-panel" role="region" aria-label="Action Commands">
    <!-- Action Command Title Bar with Upper-Right Battlefield Toggle Group (Section 1, 2, 8) -->
    <div class="action-title-bar">
      <div class="action-title-left">
        <span class="action-icon">⚡</span>
        <span class="action-title-text">BATTLE ACTIONS & HYPOTHESES</span>
      </div>

      <!-- Battlefield Toggle Group (Section 1, 2, 8) -->
      <div class="battlefield-toggle-group" role="toolbar" aria-label="Battlefield Controls">
        <!-- 1. Skip Animation Toggle (Excluded from Arena) -->
        <div
          v-if="!isArena"
          class="battle-toggle-badge toggle-skip-anim"
          :class="{
            'is-active': activeSkipAnim,
            'is-disabled': disabled || isLocked || answered
          }"
          @click="toggleSkipAnimation"
          role="switch"
          :aria-checked="activeSkipAnim"
          tabindex="0"
          @keydown.space.prevent="toggleSkipAnimation"
          @keydown.enter.prevent="toggleSkipAnimation"
          title="Skip Animation: Bypass combat animations, hit reactions, and visual waiting time"
        >
          <span class="toggle-icon">⏩</span>
          <span class="toggle-label font-pixel">Skip Animation</span>
          <div class="toggle-track">
            <div class="toggle-knob"></div>
          </div>
        </div>

        <!-- 2. Auto-Confirm Answer Toggle (Excluded from Arena) -->
        <div
          v-if="!isArena"
          class="battle-toggle-badge toggle-auto-confirm"
          :class="{
            'is-active': activeAutoConfirm,
            'is-disabled': disabled || isLocked || answered
          }"
          @click="toggleAutoConfirm"
          role="switch"
          :aria-checked="activeAutoConfirm"
          tabindex="0"
          @keydown.space.prevent="toggleAutoConfirm"
          @keydown.enter.prevent="toggleAutoConfirm"
          title="Auto-Confirm Answer: Submit selected answer choice immediately without confirmation modal"
        >
          <span class="toggle-icon">⚡</span>
          <span class="toggle-label font-pixel">Auto-Confirm Answer</span>
          <div class="toggle-track">
            <div class="toggle-knob"></div>
          </div>
        </div>

        <!-- 3. Critical Mode Switch / Toggle -->
        <div class="crit-toggle-wrapper">
          <!-- Speech Bubble Notification on left of toggle (ONLY when turned ON) -->
          <Transition name="crit-bubble-pop">
            <div v-if="showCritBubble" class="crit-speech-bubble" role="status">
              <span class="bubble-icon">💥</span>
              <span class="bubble-text"><strong>CRITICAL ON!</strong> Correct → Crit to enemy. Wrong → Crit to self!</span>
              <div class="bubble-arrow-right"></div>
            </div>
          </Transition>

          <div
            class="battle-toggle-badge crit-switch-badge"
            :class="{
              'is-active': activeCrit,
              'is-disabled': disabled || isLocked || answered
            }"
            @click="toggleCritical"
            role="switch"
            :aria-checked="activeCrit"
            tabindex="0"
            @keydown.space.prevent="toggleCritical"
            @keydown.enter.prevent="toggleCritical"
            title="Toggle Critical Mode: Higher damage if correct, but wrong answers deal critical backfire to you!"
          >
            <span class="toggle-icon">💥</span>
            <span class="toggle-label font-pixel">{{ activeCrit ? 'CRIT ON' : 'CRIT OFF' }}</span>
            <div class="toggle-track crit-track">
              <div class="toggle-knob crit-knob"></div>
            </div>
          </div>
        </div>

        <!-- 4. Dodge Potion Consumable Toggle (Action Control) - STRICTLY EXCLUDED FROM TUTORIAL & ARENA -->
        <div
          v-if="!isTutorialMode && !isArena"
          class="battle-toggle-badge toggle-dodge-potion"
          :class="{
            'is-active': isDodgePotionActive,
            'is-disabled': disabled || isLocked || answered || isDodgeInFlight,
            'is-depleted': totalDodgeCharges <= 0
          }"
          @click="triggerDodgePotion"
          role="button"
          :aria-disabled="disabled || isLocked || answered || isDodgeInFlight || totalDodgeCharges <= 0"
          tabindex="0"
          @keydown.space.prevent="triggerDodgePotion"
          @keydown.enter.prevent="triggerDodgePotion"
          :title="`Dodge Potion: Skip current question without damage (${dodgeDisplayCount} available)`"
        >
          <img src="/assets/POTIONS/dodge%20potion.png" alt="Dodge Potion" class="potion-toggle-icon" />
          <span class="toggle-label font-pixel">
            DODGE POTION
            <span class="potion-count-pill font-pixel">{{ dodgeDisplayCount }}</span>
          </span>
          <div class="toggle-track potion-track">
            <div class="toggle-knob potion-knob"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2x2 Action Command Buttons Grid (Pokemon Fight/Bag/Pokemon/Run inspired, but Answer = Action) -->
    <div class="action-choices-grid">
      <button
        v-for="(text, key) in choices"
        :key="key"
        type="button"
        class="action-choice-btn"
        :class="getChoiceStateClass(key)"
        :disabled="disabled || isLocked"
        @click="selectChoice(key)"
      >
        <!-- Runic Action Key Badge [ A ], [ B ], [ C ], [ D ] -->
        <span class="action-key-badge">{{ key }}</span>

        <!-- Choice Text Prompt -->
        <span class="action-choice-text">{{ text }}</span>

        <!-- State Icon Badge -->
        <span v-if="!answered && isKeySelected(key)" class="action-staged-tag font-pixel">⚡ STAGED</span>
        <span v-else-if="answered && isKeyCorrect(key)" class="action-state-icon correct-icon">✓</span>
        <span v-else-if="answered && isKeySelected(key) && !isKeyCorrect(key)" class="action-state-icon wrong-icon">✕</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useBattlefieldPreferencesStore } from '../../stores/battlefieldPreferences'
import { useAdventureStore } from '../../stores/adventure'
import { useToastStore } from '../../stores/toast'

const props = defineProps({
  choices: {
    type: Object,
    required: true
  },
  selectedKey: {
    type: String,
    default: null
  },
  correctAnswer: {
    type: String,
    default: null
  },
  answered: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  isCriticalActive: {
    type: Boolean,
    default: null
  },
  skipAnimation: {
    type: Boolean,
    default: null
  },
  autoConfirm: {
    type: Boolean,
    default: null
  },
  critDamage: {
    type: Number,
    default: 35
  },
  baseDamage: {
    type: Number,
    default: 10
  },
  isTutorial: {
    type: Boolean,
    default: false
  },
  isArena: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'select',
  'toggle-critical',
  'update:isCriticalActive',
  'toggle-skip-animation',
  'update:skipAnimation',
  'toggle-auto-confirm',
  'update:autoConfirm',
  'use-dodge-potion'
])

const route = useRoute()
const battlePrefs = useBattlefieldPreferencesStore()
const adventureStore = useAdventureStore()
const toast = useToastStore()

const isLocked = ref(false)
const showCritBubble = ref(false)
let bubbleTimer = null

// Dodge Potion Action & State
const isDodgeInFlight = ref(false)
const isDodgePotionActive = ref(false)

const isTutorialMode = computed(() => {
  if (props.isTutorial) return true
  try {
    const p = route?.path || ''
    if (p === '/tutorial' || p.startsWith('/tutorial/')) return true
  } catch (_) {}
  return false
})

const dailyCharges = computed(() => Number(adventureStore.dodgePotionState?.daily_charges ?? 3))
const maxDailyCharges = computed(() => Number(adventureStore.dodgePotionState?.daily_max ?? 3))
const purchasedStock = computed(() => Number(adventureStore.dodgePotionState?.purchased_stock ?? 0))
const totalDodgeCharges = computed(() => dailyCharges.value + purchasedStock.value)

const dodgeDisplayCount = computed(() => {
  if (purchasedStock.value > 0) {
    return `${dailyCharges.value}/${maxDailyCharges.value} (+${purchasedStock.value})`
  }
  return `${dailyCharges.value}/${maxDailyCharges.value}`
})

onMounted(() => {
  if (!isTutorialMode.value) {
    adventureStore.fetchDodgePotionStatus()
  }
})

function triggerDodgePotion() {
  if (props.disabled || isLocked.value || props.answered || isDodgeInFlight.value) return

  if (totalDodgeCharges.value <= 0) {
    toast.error('No Dodge Potions remaining! Visit the Market to purchase more for 200 coins.')
    return
  }

  isDodgeInFlight.value = true
  isDodgePotionActive.value = true

  emit('use-dodge-potion', {
    onSuccess: () => {
      isDodgeInFlight.value = false
      isDodgePotionActive.value = false
    },
    onError: (msg) => {
      isDodgeInFlight.value = false
      isDodgePotionActive.value = false
      if (msg) toast.error(msg)
    }
  })
}

const activeCrit = computed(() => {
  if (props.isCriticalActive !== null && props.isCriticalActive !== undefined) {
    return !!props.isCriticalActive
  }
  return !!battlePrefs.criticalMode
})

const activeSkipAnim = computed(() => {
  if (props.skipAnimation !== null && props.skipAnimation !== undefined) {
    return !!props.skipAnimation
  }
  return !!battlePrefs.skipAnimation
})

const activeAutoConfirm = computed(() => {
  if (props.autoConfirm !== null && props.autoConfirm !== undefined) {
    return !!props.autoConfirm
  }
  return !!battlePrefs.autoConfirm
})

function toggleCritical() {
  if (props.disabled || props.answered || isLocked.value) return
  const nextVal = !activeCrit.value
  battlePrefs.setCriticalMode(nextVal)
  emit('toggle-critical', nextVal)
  emit('update:isCriticalActive', nextVal)

  if (nextVal) {
    showCritBubble.value = true
    if (bubbleTimer) clearTimeout(bubbleTimer)
    bubbleTimer = setTimeout(() => {
      showCritBubble.value = false
    }, 3200)
  } else {
    showCritBubble.value = false
  }
}

function toggleSkipAnimation() {
  if (props.disabled || isLocked.value) return
  const nextVal = !activeSkipAnim.value
  battlePrefs.setSkipAnimation(nextVal)
  emit('toggle-skip-animation', nextVal)
  emit('update:skipAnimation', nextVal)
}

function toggleAutoConfirm() {
  if (props.disabled || isLocked.value) return
  const nextVal = !activeAutoConfirm.value
  battlePrefs.setAutoConfirm(nextVal)
  emit('toggle-auto-confirm', nextVal)
  emit('update:autoConfirm', nextVal)
}

watch(() => props.selectedKey, (val) => {
  if (!val && !props.answered) {
    isLocked.value = false
  }
})

// Unlock when transitioning away from answered (new question)
// CRITICAL: NEVER automatically reset toggle preferences!
watch(() => props.answered, (newVal) => {
  if (!newVal) {
    isLocked.value = false
  } else {
    showCritBubble.value = false
  }
})

onUnmounted(() => {
  if (bubbleTimer) clearTimeout(bubbleTimer)
})

function selectChoice(key) {
  if (props.disabled || props.answered || isLocked.value) return
  isLocked.value = true
  emit('select', key)
}

function isKeyCorrect(key) {
  if (!props.correctAnswer) return false
  return String(key || '').trim().toUpperCase() === String(props.correctAnswer || '').trim().toUpperCase()
}

function isKeySelected(key) {
  if (!props.selectedKey) return false
  return String(key || '').trim().toUpperCase() === String(props.selectedKey || '').trim().toUpperCase()
}

function getChoiceStateClass(key) {
  if (!props.answered) {
    return {
      'state-selected': isKeySelected(key)
    }
  }

  const isTargetCorrect = isKeyCorrect(key)
  const isTargetChosen = isKeySelected(key)

  if (isTargetCorrect) {
    return 'state-correct'
  }
  if (isTargetChosen && !isTargetCorrect) {
    return 'state-wrong'
  }
  return 'state-dimmed'
}
</script>

<style scoped>
.battle-choice-panel {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 220px;
  background: linear-gradient(180deg, rgba(22, 14, 40, 0.96) 0%, rgba(14, 8, 26, 0.98) 100%);
  border: 2px solid rgba(168, 85, 247, 0.5);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7), inset 0 0 10px rgba(139, 92, 246, 0.2);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  overflow: visible;
  position: relative;
}

/* Action Title Bar with Toggle */
.action-title-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 0.5rem;
  margin-bottom: 0.65rem;
  flex-shrink: 0;
  position: relative;
}

.action-title-left {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.action-icon {
  font-size: 0.75rem;
  color: #fbbf24;
}

.action-title-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #c4b5fd;
  letter-spacing: 0.5px;
}

/* Critical Toggle Switch & Speech Bubble (Section 10) */
.crit-toggle-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

/* Speech Bubble Notification on left of toggle */
.crit-speech-bubble {
  position: absolute;
  right: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: linear-gradient(135deg, #450a0a 0%, #1c0a0a 100%);
  border: 1.5px solid #ef4444;
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.45), 0 0 10px rgba(0, 0, 0, 0.8);
  border-radius: 6px;
  padding: 0.35rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  white-space: nowrap;
  z-index: 50;
  pointer-events: none;
}

.crit-speech-bubble .bubble-icon {
  font-size: 0.8rem;
  animation: pulse-crit 1s infinite alternate;
}

.crit-speech-bubble .bubble-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.45rem;
  color: #fecaca;
  letter-spacing: 0.3px;
}

.bubble-arrow-right {
  position: absolute;
  right: -6px;
  top: 50%;
  transform: translateY(-50%);
  width: 0;
  height: 0;
  border-top: 5px solid transparent;
  border-bottom: 5px solid transparent;
  border-left: 6px solid #ef4444;
}

/* Speech bubble transition */
.crit-bubble-pop-enter-active {
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.crit-bubble-pop-leave-active {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.crit-bubble-pop-enter-from {
  opacity: 0;
  transform: translateY(-50%) scale(0.85) translateX(8px);
}
.crit-bubble-pop-leave-to {
  opacity: 0;
  transform: translateY(-50%) scale(0.9);
}

@keyframes pulse-crit {
  0% { transform: scale(1); }
  100% { transform: scale(1.2); }
}

/* Battlefield Toggle Group (Section 1, 2, 8) */
.battlefield-toggle-group {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.battle-toggle-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(18, 12, 34, 0.9);
  border: 1.5px solid rgba(139, 92, 246, 0.4);
  border-radius: 20px;
  padding: 0.18rem 0.45rem;
  cursor: pointer;
  user-select: none;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
}

.battle-toggle-badge:focus-visible {
  box-shadow: 0 0 0 2px #c084fc, 0 0 8px rgba(192, 132, 252, 0.6);
}

.battle-toggle-badge:hover:not(.is-disabled) {
  border-color: #a855f7;
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.35);
}

.battle-toggle-badge.is-disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.toggle-icon {
  font-size: 0.55rem;
  line-height: 1;
}

.toggle-label {
  font-size: 0.43rem;
  color: #94a3b8;
  letter-spacing: 0.3px;
  white-space: nowrap;
  transition: color 0.2s ease;
}

.toggle-track {
  width: 24px;
  height: 12px;
  background: rgba(30, 20, 50, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.4);
  border-radius: 10px;
  position: relative;
  transition: all 0.2s ease;
}

.toggle-knob {
  width: 8px;
  height: 8px;
  background: #94a3b8;
  border-radius: 50%;
  position: absolute;
  top: 1px;
  left: 2px;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Skip Animation Switch */
.toggle-skip-anim.is-active {
  background: rgba(38, 26, 12, 0.95);
  border-color: #f59e0b;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
}
.toggle-skip-anim.is-active .toggle-label {
  color: #fcd34d;
  text-shadow: 0 0 6px rgba(245, 158, 11, 0.6);
}
.toggle-skip-anim.is-active .toggle-track {
  background: #78350f;
  border-color: #f59e0b;
}
.toggle-skip-anim.is-active .toggle-knob {
  background: #fbbf24;
  left: 12px;
  box-shadow: 0 0 6px #f59e0b;
}

/* Auto-Confirm Answer Switch */
.toggle-auto-confirm.is-active {
  background: rgba(6, 32, 24, 0.95);
  border-color: #10b981;
  box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
}
.toggle-auto-confirm.is-active .toggle-label {
  color: #6ee7b7;
  text-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
}
.toggle-auto-confirm.is-active .toggle-track {
  background: #064e3b;
  border-color: #10b981;
}
.toggle-auto-confirm.is-active .toggle-knob {
  background: #34d399;
  left: 12px;
  box-shadow: 0 0 6px #10b981;
}

/* Critical Mode Switch */
.crit-switch-badge.is-active {
  background: rgba(40, 10, 15, 0.95);
  border-color: #ef4444;
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.5);
}
.crit-switch-badge.is-active .toggle-label {
  color: #f87171;
  text-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
}
.crit-switch-badge.is-active .crit-track {
  background: #7f1d1d;
  border-color: #ef4444;
}
.crit-switch-badge.is-active .crit-knob {
  background: #f87171;
  left: 12px;
  box-shadow: 0 0 6px #ef4444;
}

/* Dodge Potion Consumable Badge */
.toggle-dodge-potion {
  border-color: rgba(56, 189, 248, 0.45);
  background: rgba(10, 22, 36, 0.9);
}
.toggle-dodge-potion:hover:not(.is-disabled) {
  border-color: #38bdf8;
  box-shadow: 0 0 10px rgba(56, 189, 248, 0.4);
}
.potion-toggle-icon {
  width: 13px;
  height: 13px;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 0 3px rgba(56, 189, 248, 0.6));
}
.potion-count-pill {
  font-size: 0.44rem;
  padding: 0.08rem 0.28rem;
  border-radius: 4px;
  background: rgba(6, 182, 212, 0.2);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.4);
  margin-left: 0.25rem;
  letter-spacing: 0.2px;
}
.toggle-dodge-potion.is-active {
  background: rgba(8, 38, 58, 0.98);
  border-color: #06b6d4;
  box-shadow: 0 0 14px rgba(6, 182, 212, 0.55);
}
.toggle-dodge-potion.is-active .toggle-label {
  color: #67e8f9;
  text-shadow: 0 0 6px rgba(6, 182, 212, 0.7);
}
.potion-track {
  background: rgba(15, 30, 45, 0.9);
  border-color: rgba(56, 189, 248, 0.4);
}
.potion-knob {
  background: #38bdf8;
}
.toggle-dodge-potion.is-active .potion-track {
  background: #155e75;
  border-color: #06b6d4;
}
.toggle-dodge-potion.is-active .potion-knob {
  background: #67e8f9;
  left: 12px;
  box-shadow: 0 0 6px #06b6d4;
}
.toggle-dodge-potion.is-depleted {
  opacity: 0.55;
}
.toggle-dodge-potion.is-depleted .potion-count-pill {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-color: rgba(239, 68, 68, 0.35);
}

/* 2x2 Action Command Buttons Grid */
.action-choices-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-rows: 1fr 1fr;
  gap: 0.55rem;
  flex: 1;
}

/* Individual Action Command Button */
.action-choice-btn {
  background: rgba(30, 20, 56, 0.75);
  border: 1.5px solid rgba(139, 92, 246, 0.4);
  border-radius: 6px;
  padding: 0.65rem 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-align: left;
  cursor: pointer;
  position: relative;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
  overflow: hidden;
  max-height: 110px;
  overflow-y: auto;
}

.action-choice-btn::-webkit-scrollbar {
  width: 4px;
}
.action-choice-btn::-webkit-scrollbar-thumb {
  background: rgba(168, 85, 247, 0.35);
  border-radius: 2px;
}

/* Action Key Badge [ A ] */
.action-key-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  background: var(--choice-key-bg, rgba(139, 92, 246, 0.3));
  border: 1px solid var(--choice-key-border, rgba(168, 85, 247, 0.5));
  color: var(--choice-key-color, #fbbf24);
  padding: 0.3rem 0.5rem;
  border-radius: 4px;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

/* Action Choice Text */
.action-choice-text {
  font-size: 0.88rem;
  line-height: 1.5;
  color: var(--choice-text-color, #f1f5f9);
  font-weight: 500;
  flex: 1;
  word-break: normal;
  overflow-wrap: break-word;
}

/* Staged / Selected State */
.action-staged-tag {
  font-size: 0.5rem;
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.2);
  border: 1px solid #fbbf24;
  padding: 0.15rem 0.3rem;
  border-radius: 3px;
  flex-shrink: 0;
}

/* Hover & Focus State */
.action-choice-btn:not(:disabled):hover {
  background: rgba(88, 28, 135, 0.5);
  border-color: #c084fc;
  transform: translateY(-2px);
  box-shadow: 0 0 14px rgba(168, 85, 247, 0.5);
}

.action-choice-btn:not(:disabled):hover .action-key-badge {
  background: #7c3aed;
  color: #ffffff;
  border-color: #f59e0b;
}

.action-choice-btn:not(:disabled):active {
  transform: translateY(0);
}

/* Selected State */
.action-choice-btn.state-selected {
  background: rgba(124, 58, 237, 0.45);
  border-color: #fbbf24;
  box-shadow: 0 0 16px rgba(251, 191, 36, 0.5);
}

/* Correct Answer State */
.action-choice-btn.state-correct {
  background: rgba(6, 78, 59, 0.85) !important;
  border-color: #10b981 !important;
  color: #ffffff !important;
  box-shadow: 0 0 18px rgba(52, 211, 153, 0.6) !important;
  animation: correct-pulse 1.2s infinite alternate;
}

.action-choice-btn.state-correct .action-key-badge {
  background: #059669;
  border-color: #34d399;
  color: #ffffff;
}

@keyframes correct-pulse {
  from { box-shadow: 0 0 10px rgba(52, 211, 153, 0.4); }
  to { box-shadow: 0 0 22px rgba(52, 211, 153, 0.8); }
}

/* Wrong Answer State */
.action-choice-btn.state-wrong {
  background: rgba(127, 29, 29, 0.85) !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  box-shadow: 0 0 18px rgba(239, 68, 68, 0.6) !important;
}

.action-choice-btn.state-wrong .action-key-badge {
  background: #b91c1c;
  border-color: #f87171;
  color: #ffffff;
}

/* Dimmed non-selected options */
.action-choice-btn.state-dimmed {
  opacity: 0.45;
  filter: grayscale(0.5);
}

.action-choice-btn:disabled {
  cursor: default;
}

/* State Icon Badge */
.action-state-icon {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  padding: 0.15rem 0.35rem;
  border-radius: 3px;
  flex-shrink: 0;
}
.correct-icon { color: #34d399; }
.wrong-icon { color: #f87171; }

/* Responsive adjustments */
@media (max-width: 900px) {
  .action-choices-grid {
    grid-template-columns: 1fr;
    grid-template-rows: repeat(4, auto);
  }
  .action-choice-btn {
    padding: 0.55rem 0.65rem;
  }
  .crit-speech-bubble {
    right: 0;
    top: calc(100% + 6px);
    transform: none;
  }
  .bubble-arrow-right {
    display: none;
  }
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME STYLES
   ══════════════════════════════════════════════════════════════ */
:global(.theme-morning) .battle-choice-panel,
:global([data-theme="morning"]) .battle-choice-panel {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  box-shadow: 0 12px 36px rgba(143, 100, 45, 0.22) !important;
}

:global(.theme-morning) .action-title-bar,
:global([data-theme="morning"]) .action-title-bar {
  border-bottom: 1px solid rgba(184, 151, 88, 0.35);
}

:global(.theme-morning) .action-title-text,
:global([data-theme="morning"]) .action-title-text {
  color: #8F642D;
  font-weight: bold;
}

:global(.theme-morning) .crit-switch-badge,
:global([data-theme="morning"]) .crit-switch-badge {
  background: #F5E8CC;
  border: 1.5px solid rgba(184, 138, 66, 0.4);
}

:global(.theme-morning) .crit-switch-label,
:global([data-theme="morning"]) .crit-switch-label {
  color: #77634F;
}

:global(.theme-morning) .crit-switch-track,
:global([data-theme="morning"]) .crit-switch-track {
  background: #EAD8B5;
  border: 1px solid #B88A42;
}

:global(.theme-morning) .crit-switch-knob,
:global([data-theme="morning"]) .crit-switch-knob {
  background: #8F642D;
}

:global(.theme-morning) .action-choice-btn,
:global([data-theme="morning"]) .action-choice-btn {
  background: #FFFDF7 !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 4px 12px rgba(143, 100, 45, 0.08) !important;
}

:global(.theme-morning) .action-choice-btn:hover:not(:disabled),
:global([data-theme="morning"]) .action-choice-btn:hover:not(:disabled) {
  background: #ffffff;
  border-color: #b45309;
  box-shadow: 0 4px 14px rgba(180, 83, 9, 0.22);
}

:global(.theme-morning) .action-key-badge,
:global([data-theme="morning"]) .action-key-badge {
  background: rgba(180, 83, 9, 0.12);
  border: 1px solid rgba(180, 83, 9, 0.4);
  color: #92400e;
  font-weight: bold;
}

:global(.theme-morning) .action-choice-text,
:global([data-theme="morning"]) .action-choice-text {
  color: #2b2216;
  font-weight: 500;
}

:global(.theme-morning) .choice-staged,
:global(.theme-morning) .choice-selected,
:global([data-theme="morning"]) .choice-staged,
:global([data-theme="morning"]) .choice-selected {
  background: linear-gradient(180deg, #fef5dd 0%, #fae8b5 100%) !important;
  border-color: #b45309 !important;
  box-shadow: 0 0 16px rgba(180, 83, 9, 0.35) !important;
}

:global(.theme-morning) .action-choice-btn::-webkit-scrollbar-thumb,
:global([data-theme="morning"]) .action-choice-btn::-webkit-scrollbar-thumb {
  background: rgba(184, 151, 88, 0.45);
}
</style>

<style>
/* Unscoped morning mode rules for battle choices */
[data-theme="morning"] .battle-choice-panel .action-choice-text,
.theme-morning .battle-choice-panel .action-choice-text,
[data-theme="morning"] .action-choice-text,
.theme-morning .action-choice-text {
  color: #3D2B1F !important;
  font-weight: 500 !important;
}

[data-theme="morning"] .battle-choice-panel .action-key-badge,
.theme-morning .battle-choice-panel .action-key-badge,
[data-theme="morning"] .action-key-badge,
.theme-morning .action-key-badge {
  background: #EAD8B5 !important;
  color: #8F642D !important;
  border: 1px solid #B88A42 !important;
}

[data-theme="morning"] .battle-choice-panel .action-choice-btn,
.theme-morning .battle-choice-panel .action-choice-btn,
[data-theme="morning"] .action-choice-btn,
.theme-morning .action-choice-btn {
  background: #FFFDF7 !important;
  border-color: #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .toggle-dodge-potion,
.theme-morning .toggle-dodge-potion {
  background: #FFFDF7 !important;
  border-color: #0284c7 !important;
}
[data-theme="morning"] .toggle-dodge-potion .toggle-label,
.theme-morning .toggle-dodge-potion .toggle-label {
  color: #0369a1 !important;
}
[data-theme="morning"] .potion-count-pill,
.theme-morning .potion-count-pill {
  background: rgba(2, 132, 199, 0.12) !important;
  color: #0284c7 !important;
  border-color: rgba(2, 132, 199, 0.35) !important;
}
</style>
