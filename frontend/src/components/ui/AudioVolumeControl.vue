<template>
  <div
    class="audio-volume-widget"
    :class="{ 'is-morning': gameStore.isMorning, 'is-muted': isMuted }"
    ref="widgetRef"
  >
    <!-- Compact Control Pill on the Navigation Bar -->
    <div class="volume-pill-strip">
      <!-- Quick Decrease Volume Button (-) -->
      <button
        type="button"
        class="vol-step-btn vol-step-minus"
        @click.stop="handleDecrease"
        :title="'Decrease Audio Volume (-10%) · Currently ' + displayedVolumeText"
        :disabled="effectiveVolume <= 0 || !gameStore.musicEnabled"
        aria-label="Decrease Volume"
      >
        <span class="step-symbol">−</span>
      </button>

      <!-- Center Display & Popover Trigger Button -->
      <button
        type="button"
        class="vol-center-trigger"
        @click.stop="togglePopover"
        :title="'Click to open volume slider or mute/unmute · Current: ' + displayedVolumeText"
        :aria-expanded="isPopoverOpen"
        aria-label="Audio Volume Control"
      >
        <span class="vol-icon">{{ volumeIcon }}</span>
        <span class="vol-text font-pixel">{{ displayedVolumeText }}</span>
        <span class="vol-chevron" :class="{ 'is-flipped': isPopoverOpen }">▾</span>
      </button>

      <!-- Quick Increase Volume Button (+) -->
      <button
        type="button"
        class="vol-step-btn vol-step-plus"
        @click.stop="handleIncrease"
        :title="'Increase Audio Volume (+10%) · Currently ' + displayedVolumeText"
        :disabled="effectiveVolume >= 100 && gameStore.musicEnabled"
        aria-label="Increase Volume"
      >
        <span class="step-symbol">+</span>
      </button>
    </div>

    <!-- Floating RPG Volume Popover Modal -->
    <Transition name="vol-popover-anim">
      <div
        v-if="isPopoverOpen"
        class="volume-popover-card animate-scale-up"
        :class="{ 'is-morning': gameStore.isMorning }"
        @click.stop
      >
        <!-- Popover Header -->
        <div class="popover-header">
          <div class="popover-title-group">
            <span class="popover-rune">✦</span>
            <span class="popover-title font-pixel">AUDIO VOLUME</span>
          </div>
          <!-- Quick Mute / Unmute Button -->
          <button
            type="button"
            class="popover-mute-btn font-pixel"
            :class="{ 'is-muted-active': isMuted }"
            @click="handleToggleMute"
            :title="isMuted ? 'Unmute Audio' : 'Mute Audio'"
          >
            {{ isMuted ? '🔇 UNMUTE' : '🎵 MUTE' }}
          </button>
        </div>

        <!-- Slider Section -->
        <div class="popover-slider-section">
          <div class="slider-meta-row">
            <span class="slider-label">BGM &amp; Sound Volume</span>
            <span class="slider-percent-badge font-pixel">{{ isMuted ? 'MUTED' : effectiveVolume + '%' }}</span>
          </div>

          <div class="slider-track-wrap">
            <input
              type="range"
              min="0"
              max="100"
              step="5"
              :value="isMuted ? 0 : effectiveVolume"
              @input="onSliderInput"
              class="volume-range-input"
              aria-label="Volume Slider"
            />
            <div
              class="slider-fill-bar"
              :style="{ width: (isMuted ? 0 : effectiveVolume) + '%' }"
            ></div>
          </div>
        </div>

        <!-- Quick Volume Preset Chips -->
        <div class="popover-presets-row">
          <button
            v-for="preset in [0, 25, 50, 75, 100]"
            :key="preset"
            type="button"
            class="preset-chip font-pixel"
            :class="{
              'is-active': (preset === 0 && isMuted) || (!isMuted && effectiveVolume === preset)
            }"
            @click="setPreset(preset)"
          >
            {{ preset === 0 ? 'MUTE' : preset + '%' }}
          </button>
        </div>

        <!-- SFX Option Row -->
        <div class="popover-sfx-row">
          <div class="sfx-info">
            <span class="sfx-title font-pixel">CLICK SFX</span>
            <span class="sfx-desc">Tactile clicks &amp; combat feedback</span>
          </div>
          <button
            type="button"
            class="sfx-toggle-pill font-pixel"
            :class="{ active: gameStore.audioEnabled }"
            @click="gameStore.setAudio(!gameStore.audioEnabled)"
          >
            {{ gameStore.audioEnabled ? 'ON ⚡' : 'OFF ✕' }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useGameStore } from '../../stores/game'
import { battleAudio } from '../../services/battleAudio'

const gameStore = useGameStore()
const widgetRef = ref(null)
const isPopoverOpen = ref(false)

const isMuted = computed(() => {
  return !gameStore.musicEnabled || gameStore.masterVolume <= 0
})

const effectiveVolume = computed(() => {
  return gameStore.masterVolume
})

const displayedVolumeText = computed(() => {
  if (isMuted.value) return 'MUTED'
  return `${gameStore.masterVolume}%`
})

const volumeIcon = computed(() => {
  if (isMuted.value) return '🔇'
  const vol = gameStore.masterVolume
  if (vol <= 30) return '🔈'
  if (vol <= 70) return '🔉'
  return '🔊'
})

function togglePopover() {
  try {
    battleAudio.playSelect()
  } catch {}
  isPopoverOpen.value = !isPopoverOpen.value
}

function handleIncrease() {
  try {
    battleAudio.playSelect()
  } catch {}
  gameStore.increaseVolume(10)
}

function handleDecrease() {
  try {
    battleAudio.playSelect()
  } catch {}
  gameStore.decreaseVolume(10)
}

function handleToggleMute() {
  try {
    battleAudio.playSelect()
  } catch {}
  if (isMuted.value) {
    gameStore.setMusic(true)
    if (gameStore.masterVolume === 0) {
      gameStore.setMasterVolume(50)
    }
  } else {
    gameStore.setMusic(false)
  }
}

function onSliderInput(e) {
  const val = Number(e.target.value) || 0
  if (val === 0) {
    gameStore.setMusic(false)
    gameStore.setMasterVolume(0)
  } else {
    if (!gameStore.musicEnabled) {
      gameStore.setMusic(true)
    }
    gameStore.setMasterVolume(val)
  }
}

function setPreset(val) {
  try {
    battleAudio.playSelect()
  } catch {}
  if (val === 0) {
    gameStore.setMusic(false)
  } else {
    if (!gameStore.musicEnabled) {
      gameStore.setMusic(true)
    }
    gameStore.setMasterVolume(val)
  }
}

// Click outside handling to dismiss popover cleanly
function onClickOutside(e) {
  if (!isPopoverOpen.value) return
  if (widgetRef.value && !widgetRef.value.contains(e.target)) {
    isPopoverOpen.value = false
  }
}

function onKeyDown(e) {
  if (e.key === 'Escape' && isPopoverOpen.value) {
    isPopoverOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('click', onClickOutside)
  window.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('click', onClickOutside)
  window.removeEventListener('keydown', onKeyDown)
})
</script>

<style scoped>
.audio-volume-widget {
  position: relative;
  display: inline-flex;
  user-select: none;
  font-family: 'Cinzel', Georgia, serif;
}

/* ── COMPACT NAV PILL STRIP ──────────────────────────────────── */
.volume-pill-strip {
  display: inline-flex;
  align-items: center;
  height: 38px;
  background: rgba(14, 8, 26, 0.92);
  border: 1px solid rgba(139, 92, 246, 0.5);
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(139, 92, 246, 0.2);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  overflow: hidden;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.volume-pill-strip:hover {
  border-color: #a855f7;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.65), 0 0 16px rgba(168, 85, 247, 0.4);
}

/* Step Buttons (- and +) */
.vol-step-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 100%;
  background: rgba(255, 255, 255, 0.04);
  border: none;
  color: #c4b5fd;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}

.vol-step-btn:hover:not(:disabled) {
  background: rgba(168, 85, 247, 0.25);
  color: #ffffff;
}

.vol-step-btn:active:not(:disabled) {
  background: rgba(168, 85, 247, 0.4);
  transform: scale(0.92);
}

.vol-step-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.vol-step-minus {
  border-right: 1px solid rgba(139, 92, 246, 0.3);
}

.vol-step-plus {
  border-left: 1px solid rgba(139, 92, 246, 0.3);
}

/* Center Display Button */
.vol-center-trigger {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 100%;
  padding: 0 12px;
  background: transparent;
  border: none;
  color: #f1f5f9;
  font-family: inherit;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: background 0.15s ease;
  white-space: nowrap;
}

.vol-center-trigger:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
}

.vol-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.vol-text {
  font-size: 0.75rem;
  letter-spacing: 0.04em;
}

.vol-chevron {
  font-size: 0.7rem;
  color: #a78bfa;
  transition: transform 0.2s ease;
}

.vol-chevron.is-flipped {
  transform: rotate(180deg);
}

/* Muted state styling for Dark Mode */
.audio-volume-widget.is-muted .volume-pill-strip {
  border-color: rgba(239, 68, 68, 0.5);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5), 0 0 12px rgba(239, 68, 68, 0.2);
}

.audio-volume-widget.is-muted .vol-center-trigger {
  color: #fca5a5;
}

.audio-volume-widget.is-muted .vol-chevron {
  color: #ef4444;
}

/* ── MORNING THEME VARIANT ───────────────────────────────────── */
.audio-volume-widget.is-morning .volume-pill-strip {
  background: #EAD8B5;
  border-color: #B88A42;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.2);
}

.audio-volume-widget.is-morning .volume-pill-strip:hover {
  background: #FFFDF7;
  border-color: #735022;
}

.audio-volume-widget.is-morning .vol-step-btn {
  background: rgba(90, 60, 20, 0.05);
  color: #3D2B1F;
  border-color: rgba(184, 138, 66, 0.4);
}

.audio-volume-widget.is-morning .vol-step-btn:hover:not(:disabled) {
  background: rgba(184, 138, 66, 0.2);
  color: #1c130d;
}

.audio-volume-widget.is-morning .vol-center-trigger {
  color: #3D2B1F;
}

.audio-volume-widget.is-morning .vol-chevron {
  color: #735022;
}

.audio-volume-widget.is-morning.is-muted .volume-pill-strip {
  background: #fed7aa;
  border-color: #ea580c;
}

.audio-volume-widget.is-morning.is-muted .vol-center-trigger {
  color: #7c2d12;
}

/* ── FLOATING POPOVER MODAL ──────────────────────────────────── */
.volume-popover-card {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 290px;
  background: rgba(14, 8, 26, 0.96);
  border: 1px solid rgba(168, 85, 247, 0.6);
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.8), 0 0 24px rgba(139, 92, 246, 0.35);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  z-index: 10000;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.volume-popover-card.is-morning {
  background: #FAF5E9;
  border-color: #B88A42;
  box-shadow: 0 16px 36px rgba(90, 60, 20, 0.3);
  color: #3D2B1F;
}

/* Popover Header */
.popover-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 10px;
}

.volume-popover-card.is-morning .popover-header {
  border-bottom-color: rgba(184, 138, 66, 0.3);
}

.popover-title-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

.popover-rune {
  color: #c084fc;
  font-size: 0.8rem;
}

.volume-popover-card.is-morning .popover-rune {
  color: #b45309;
}

.popover-title {
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  color: #e2e8f0;
}

.volume-popover-card.is-morning .popover-title {
  color: #3D2B1F;
}

.popover-mute-btn {
  font-size: 0.65rem;
  padding: 4px 8px;
  border-radius: 4px;
  background: rgba(168, 85, 247, 0.15);
  border: 1px solid rgba(168, 85, 247, 0.4);
  color: #d8b4fe;
  cursor: pointer;
  transition: all 0.15s ease;
}

.popover-mute-btn:hover {
  background: rgba(168, 85, 247, 0.3);
  color: #ffffff;
}

.popover-mute-btn.is-muted-active {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.6);
  color: #fca5a5;
}

.volume-popover-card.is-morning .popover-mute-btn {
  background: rgba(184, 138, 66, 0.15);
  border-color: #B88A42;
  color: #3D2B1F;
}

/* Slider Section */
.popover-slider-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.slider-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.75rem;
}

.slider-label {
  color: #94a3b8;
  font-size: 0.72rem;
}

.volume-popover-card.is-morning .slider-label {
  color: #6B5B4D;
}

.slider-percent-badge {
  font-size: 0.75rem;
  color: #a855f7;
  font-weight: 700;
}

.volume-popover-card.is-morning .slider-percent-badge {
  color: #b45309;
}

.slider-track-wrap {
  position: relative;
  display: flex;
  align-items: center;
  height: 20px;
}

.volume-range-input {
  width: 100%;
  height: 8px;
  appearance: none;
  -webkit-appearance: none;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 4px;
  outline: none;
  cursor: pointer;
  position: relative;
  z-index: 2;
}

.volume-popover-card.is-morning .volume-range-input {
  background: rgba(90, 60, 20, 0.15);
}

.volume-range-input::-webkit-slider-thumb {
  appearance: none;
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #c084fc;
  border: 2px solid #ffffff;
  box-shadow: 0 0 10px rgba(192, 132, 252, 0.8);
  cursor: pointer;
  transition: transform 0.1s ease;
}

.volume-range-input::-webkit-slider-thumb:hover {
  transform: scale(1.15);
}

.volume-popover-card.is-morning .volume-range-input::-webkit-slider-thumb {
  background: #b45309;
  box-shadow: 0 0 10px rgba(180, 83, 9, 0.6);
}

/* Preset Chips */
.popover-presets-row {
  display: flex;
  gap: 6px;
  justify-content: space-between;
}

.preset-chip {
  flex: 1;
  padding: 5px 0;
  text-align: center;
  font-size: 0.62rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 6px;
  color: #cbd5e1;
  cursor: pointer;
  transition: all 0.15s ease;
}

.preset-chip:hover {
  background: rgba(168, 85, 247, 0.2);
  color: #ffffff;
  border-color: #a855f7;
}

.preset-chip.is-active {
  background: rgba(168, 85, 247, 0.4);
  border-color: #c084fc;
  color: #ffffff;
  font-weight: 700;
  box-shadow: 0 0 8px rgba(168, 85, 247, 0.4);
}

.volume-popover-card.is-morning .preset-chip {
  background: rgba(90, 60, 20, 0.06);
  border-color: rgba(184, 138, 66, 0.35);
  color: #3D2B1F;
}

.volume-popover-card.is-morning .preset-chip.is-active {
  background: #EAD8B5;
  border-color: #735022;
  color: #1c130d;
}

/* SFX Option Row */
.popover-sfx-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 10px;
  border-top: 1px solid rgba(139, 92, 246, 0.2);
}

.volume-popover-card.is-morning .popover-sfx-row {
  border-top-color: rgba(184, 138, 66, 0.25);
}

.sfx-info {
  display: flex;
  flex-direction: column;
}

.sfx-title {
  font-size: 0.68rem;
  color: #e2e8f0;
}

.volume-popover-card.is-morning .sfx-title {
  color: #3D2B1F;
}

.sfx-desc {
  font-size: 0.62rem;
  color: #94a3b8;
}

.volume-popover-card.is-morning .sfx-desc {
  color: #6B5B4D;
}

.sfx-toggle-pill {
  padding: 4px 10px;
  font-size: 0.65rem;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s ease;
}

.sfx-toggle-pill.active {
  background: rgba(168, 85, 247, 0.35);
  border-color: #a855f7;
  color: #ffffff;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
}

/* Transitions */
.vol-popover-anim-enter-active,
.vol-popover-anim-leave-active {
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.vol-popover-anim-enter-from,
.vol-popover-anim-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}

@media (max-width: 600px) {
  .volume-pill-strip {
    height: 34px;
  }
  .vol-step-btn {
    width: 26px;
  }
  .vol-center-trigger {
    padding: 0 8px;
    font-size: 0.7rem;
    gap: 4px;
  }
  .volume-popover-card {
    width: 260px;
    right: -20px;
  }
}
</style>
