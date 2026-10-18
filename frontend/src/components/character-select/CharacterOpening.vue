<template>
  <div class="character-opening-container gothic-panel">
    <div class="opening-header">
      <div class="header-left">
        <span class="anim-dot"></span>
        <span class="opening-title font-cinzel">CHARACTER REVEAL CINEMATIC</span>
      </div>
      <button class="replay-btn font-pixel" @click="replayAnimation" title="Replay opening sequence">
        ↺ REPLAY
      </button>
    </div>

    <!-- Media viewport -->
    <div class="opening-viewport" :class="`theme-${character.class_type.toLowerCase()}`">
      <!-- Loading indicator -->
      <div v-if="isLoading" class="opening-loader">
        <div class="rune-spinner"></div>
        <span class="loading-label font-cinzel">Channeling cognitive manifestation…</span>
      </div>

      <!-- Opening GIF / Animation Asset -->
      <img
        v-if="openingSrc && !hasFailed"
        :key="renderKey"
        :src="openingSrc"
        :alt="`${character.name} opening sequence`"
        class="opening-media pixel-art"
        @load="onMediaLoaded"
        @error="onMediaError"
      />

      <!-- Fallback Controlled Placeholder if asset failed -->
      <div v-else-if="hasFailed" class="fallback-cinematic">
        <div class="fallback-glow"></div>
        <img
          :src="character.sprite_path"
          :alt="character.name"
          class="fallback-sprite pixel-art animate-breathe"
        />
        <div class="fallback-pedestal"></div>
        <div class="fallback-caption font-cinzel">
          <span>{{ character.title }}</span>
        </div>
      </div>

      <!-- Overlay scanlines and cinematic vignettes -->
      <div class="cinematic-scanlines"></div>
      <div class="cinematic-vignette"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { resolveAsset } from '../../services/bossMapping'

const props = defineProps({
  character: {
    type: Object,
    required: true
  }
})

const isLoading = ref(true)
const hasFailed = ref(false)
const renderKey = ref(0)
const replayTimestamp = ref(Date.now())

const openingSrc = computed(() => {
  // Use explicit opening_gif_path or fallback to idle_asset_path or sprite_path
  const raw = props.character.opening_gif_path || props.character.idle_asset_path || props.character.sprite_path || ''
  if (!raw) return ''
  const resolved = resolveAsset(raw)
  if (resolved.toLowerCase().endsWith('.gif') || resolved.toLowerCase().includes('.gif?')) {
    const sep = resolved.includes('?') ? '&' : '?'
    return `${resolved}${sep}anim_t=${replayTimestamp.value}`
  }
  return resolved
})

watch(() => props.character?.id, () => {
  isLoading.value = true
  hasFailed.value = false
  replayTimestamp.value = Date.now()
  renderKey.value++
})

function onMediaLoaded() {
  isLoading.value = false
}

function onMediaError() {
  console.warn(`[CharacterOpening] Asset resolution fallback for character ${props.character.name}: ${openingSrc.value}`)
  isLoading.value = false
  hasFailed.value = true
}

function replayAnimation() {
  isLoading.value = true
  hasFailed.value = false
  replayTimestamp.value = Date.now()
  renderKey.value++
}
</script>

<style scoped>
.character-opening-container {
  background: rgba(10, 8, 16, 0.92);
  border: 1px solid rgba(168, 85, 247, 0.35);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.opening-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.85rem;
  background: rgba(16, 12, 26, 0.95);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.anim-dot {
  width: 6px;
  height: 6px;
  background: #a855f7;
  border-radius: 50%;
  animation: pulse-dot 1.8s infinite ease-in-out;
  box-shadow: 0 0 6px #a855f7;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 0.4; transform: scale(0.9); }
  50% { opacity: 1; transform: scale(1.2); }
}

.opening-title {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #d6d3d1;
}

.replay-btn {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #d6d3d1;
  font-size: 0.48rem;
  padding: 0.25rem 0.55rem;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}

.replay-btn:hover {
  background: rgba(168, 85, 247, 0.3);
  border-color: #c084fc;
  color: #ffffff;
}

/* Viewport */
.opening-viewport {
  position: relative;
  width: 100%;
  height: 180px;
  background: radial-gradient(circle at center, rgba(30, 20, 48, 0.8) 0%, rgba(8, 6, 14, 0.98) 85%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.opening-viewport.theme-melee {
  background: radial-gradient(circle at center, rgba(50, 18, 24, 0.8) 0%, rgba(10, 6, 8, 0.98) 85%);
}

.opening-viewport.theme-range {
  background: radial-gradient(circle at center, rgba(16, 42, 40, 0.8) 0%, rgba(6, 12, 12, 0.98) 85%);
}

.opening-media {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
  filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.8));
  z-index: 2;
  transition: opacity 0.3s ease;
}

/* Loader */
.opening-loader {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: rgba(10, 8, 16, 0.8);
  z-index: 4;
}

.rune-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(168, 85, 247, 0.2);
  border-top-color: #c084fc;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-label {
  font-size: 0.7rem;
  color: #a8a29e;
  letter-spacing: 0.05em;
}

/* Fallback Controlled Placeholder */
.fallback-cinematic {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  z-index: 2;
}

.fallback-sprite {
  max-height: 110px;
  object-fit: contain;
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.7));
  z-index: 2;
}

.animate-breathe {
  animation: sprite-breath 3s ease-in-out infinite;
}

@keyframes sprite-breath {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.fallback-pedestal {
  width: 90px;
  height: 16px;
  background: radial-gradient(ellipse at center, rgba(168, 85, 247, 0.4) 0%, transparent 75%);
  border-radius: 50%;
  margin-top: -6px;
  z-index: 1;
}

.fallback-caption {
  font-size: 0.75rem;
  color: #e2d9cc;
  margin-top: 0.35rem;
  letter-spacing: 0.06em;
}

/* Overlays */
.cinematic-scanlines {
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    0deg,
    rgba(0, 0, 0, 0.12) 0px,
    rgba(0, 0, 0, 0.12) 1px,
    transparent 1px,
    transparent 2px
  );
  pointer-events: none;
  z-index: 3;
}

.cinematic-vignette {
  position: absolute;
  inset: 0;
  box-shadow: inset 0 0 25px rgba(0, 0, 0, 0.85);
  pointer-events: none;
  z-index: 3;
}
</style>
