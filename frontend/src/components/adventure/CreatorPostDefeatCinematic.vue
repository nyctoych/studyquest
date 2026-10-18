<template>
  <div class="creator-post-defeat-cinematic" :class="{ 'is-fading-out': isFadingOut }">
    <!-- ══════════════════════════════════════════════════════════════
         ZONE 1: UPPER MATCH CONTAINER (SCENE ARTWORK & CHARACTERS)
         Visually occupies the exact match container area of the boss battle
         ══════════════════════════════════════════════════════════════ -->
    <div class="cinematic-match-container">
      <div class="cinematic-viewport">
        <!-- Background Scene Image -->
        <transition name="scene-crossfade" mode="out-in">
          <div
            :key="currentSceneKey"
            class="cinematic-scene-frame"
            :class="{ 'is-blackscreen': currentScene?.isBlackscreen }"
          >
            <img
              :src="currentSceneImgSrc"
              :alt="currentScene?.actTitle || 'Scene Artwork'"
              class="cinematic-scene-asset"
              :class="{
                'is-blackscreen-img': currentScene?.isBlackscreen,
                'has-character-overlay': Boolean(currentScene?.characterOverlay)
              }"
              @error="handleImageError"
            />
          </div>
        </transition>

        <!-- Initial Reveal: Original Writer Artwork (WriterOrig.png) -->
        <transition name="writer-reveal">
          <div
            v-if="isInitialReveal && writerOrigSrc"
            class="cinematic-writer-container"
          >
            <div class="writer-radiance-aura"></div>
            <img
              :src="writerOrigSrc"
              alt="The Writer (Original Form)"
              class="cinematic-writer-sprite pixel-art"
            />
            <span class="writer-status-tag font-pixel">✦ THE AUTHOR UNVEILED ✦</span>
          </div>
        </transition>

        <!-- Story Section E Overlay: THE END -> Or perhaps... the beginning. -->
        <div v-if="showUnfinishedOverlay" class="cinematic-unfinished-overlay animate-scale-up">
          <h2 class="unfinished-the-end font-pixel" :class="{ 'is-faded': showBeginningText }">
            THE END
          </h2>
          <p v-if="showBeginningText" class="unfinished-beginning font-pixel animate-fade-in">
            Or perhaps... the beginning.
          </p>
        </div>

        <!-- Atmospheric Vignette & Frame Borders -->
        <div class="cinematic-vignette-layer"></div>

        <!-- Upper Match HUD: Act Title & Scene Progression Indicator -->
        <div class="cinematic-top-hud">
          <div class="hud-act-badge">
            <span class="act-star">✦</span>
            <span class="act-name font-pixel">{{ currentScene?.actTitle || 'THE DAUGHTER\'S STORY' }}</span>
          </div>
          <div class="hud-scene-counter font-pixel">
            SCENE {{ currentSceneIndex + 1 }} / {{ totalScenesCount }}
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         ZONE 2: DIALOGUE INTERFACE (DIRECTLY BELOW MATCH CONTAINER)
         Never floats over the artwork; sits visually connected below it
         ══════════════════════════════════════════════════════════════ -->
    <div
      class="cinematic-dialogue-panel"
      @click="handleAdvanceClick"
      title="Click or press [SPACE] to advance"
    >
      <!-- Speaker Header Bar -->
      <div class="cinematic-speaker-bar">
        <div v-if="currentLine?.speaker" class="speaker-tag" :class="speakerBadgeClass">
          <span class="speaker-icon">{{ speakerBadgeIcon }}</span>
          <span class="speaker-name font-pixel">{{ currentLine.speaker }}</span>
          <span v-if="currentLine.role" class="speaker-role font-pixel">· {{ currentLine.role }}</span>
        </div>
        <div v-else class="narration-tag font-pixel">
          <span class="narration-icon">📜</span>
          <span>CHRONICLE OF MINDREALM</span>
        </div>

        <div class="dialogue-keyboard-hint font-pixel">
          <span v-if="revealing">Click to reveal line · [SPACE]</span>
          <span v-else-if="isStoryComplete">Click to complete · [SPACE]</span>
          <span v-else>Click to advance · [SPACE]</span>
        </div>
      </div>

      <!-- Dialogue Text Body -->
      <div class="cinematic-text-area">
        <p
          class="cinematic-text-paragraph"
          :class="{
            'is-narration': !currentLine?.speaker,
            'is-quiet': currentLine?.isQuietMoment
          }"
        >
          {{ displayedText }}
          <span v-if="revealing" class="typing-cursor">▌</span>
        </p>
      </div>

      <!-- Dialogue Footer: Step Dots and Advance Action Button -->
      <div class="cinematic-footer-bar">
        <!-- Progressive Scene Dots -->
        <div class="scene-progress-dots" aria-hidden="true">
          <span
            v-for="(sc, sIdx) in totalScenesCount"
            :key="sIdx"
            class="prog-dot"
            :class="{
              'is-active': sIdx === currentSceneIndex,
              'is-done': sIdx < currentSceneIndex
            }"
          ></span>
        </div>

        <!-- Advance Button -->
        <button
          type="button"
          class="btn cinematic-continue-btn"
          :class="isStoryComplete && !revealing ? 'btn-gothic-gold btn-pulse' : 'btn-gothic-crimson'"
          @click.stop="handleAdvanceClick"
        >
          <span v-if="revealing">REVEAL ⏩</span>
          <span v-else-if="isStoryComplete">REMEMBER HER STORY 🕊️</span>
          <span v-else>CONTINUE ▸</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { resolveAsset } from '../../services/bossMapping'
import {
  FINAL_BOSS_CUTSCENE_SEQUENCE,
  CUTSCENE_ASSETS,
  preloadCutsceneAssets
} from '../../config/finalBossCutsceneScript'

const emit = defineEmits(['complete', 'scene-change'])

// Sequence State
const currentSceneIndex = ref(0)
const currentLineIndex = ref(0)
const displayedText = ref('')
const revealing = ref(false)
const isFadingOut = ref(false)

// Story Section E Overlay State
const showBeginningText = ref(false)
const overlayTimer = ref(null)

let typewriterInterval = null
let pauseTimeout = null

const scenes = FINAL_BOSS_CUTSCENE_SEQUENCE
const totalScenesCount = computed(() => scenes.length)
const currentScene = computed(() => scenes[currentSceneIndex.value] || null)
const currentLine = computed(() => currentScene.value?.lines?.[currentLineIndex.value] || null)

const currentSceneKey = computed(() => {
  return `${currentScene.value?.id || 'scene'}_${currentSceneIndex.value}`
})

const isInitialReveal = computed(() => {
  return currentScene.value?.id === 'initial_reveal'
})

const writerOrigSrc = computed(() => {
  return resolveAsset(CUTSCENE_ASSETS.writerOrig)
})

const currentSceneImgSrc = computed(() => {
  if (!currentScene.value) return ''
  return resolveAsset(currentScene.value.sceneImage)
})

const isStoryComplete = computed(() => {
  const isLastScene = currentSceneIndex.value >= totalScenesCount.value - 1
  const isLastLine = currentLineIndex.value >= (currentScene.value?.lines?.length || 1) - 1
  return isLastScene && isLastLine
})

const showUnfinishedOverlay = computed(() => {
  return Boolean(currentScene.value?.hasUnfinishedOverlay)
})

// Speaker appearance badges
const speakerBadgeClass = computed(() => {
  const spk = (currentLine.value?.speaker || '').toUpperCase()
  if (spk === 'PLAYER') return 'speaker-player'
  if (spk === 'MERCHANT' || spk === 'FATHER') return 'speaker-merchant'
  if (spk === 'DAUGHTER') return 'speaker-daughter'
  return 'speaker-default'
})

const speakerBadgeIcon = computed(() => {
  const spk = (currentLine.value?.speaker || '').toUpperCase()
  if (spk === 'PLAYER') return '🗡️'
  if (spk === 'MERCHANT' || spk === 'FATHER') return '📜'
  if (spk === 'DAUGHTER') return '✍️'
  return '✦'
})

function stopTypewriter() {
  if (typewriterInterval) {
    clearInterval(typewriterInterval)
    typewriterInterval = null
  }
}

function stopPauseTimeout() {
  if (pauseTimeout) {
    clearTimeout(pauseTimeout)
    pauseTimeout = null
  }
}

function startTypewriter() {
  stopTypewriter()
  stopPauseTimeout()

  const target = currentLine.value?.text || ''
  displayedText.value = ''

  if (!target) {
    revealing.value = false
    return
  }

  revealing.value = true
  let charIdx = 0

  typewriterInterval = setInterval(() => {
    charIdx += 2
    displayedText.value = target.slice(0, charIdx)

    if (charIdx >= target.length) {
      displayedText.value = target
      revealing.value = false
      stopTypewriter()

      // Handle Section E overlay sequence
      if (showUnfinishedOverlay.value) {
        triggerSectionEOverlay()
      }
    }
  }, 22)
}

function triggerSectionEOverlay() {
  if (overlayTimer.value) clearTimeout(overlayTimer.value)
  showBeginningText.value = false
  overlayTimer.value = setTimeout(() => {
    showBeginningText.value = true
  }, 1800)
}

function handleAdvanceClick() {
  if (isFadingOut.value) return

  // If currently revealing text, instant finish
  if (revealing.value) {
    stopTypewriter()
    displayedText.value = currentLine.value?.text || ''
    revealing.value = false
    if (showUnfinishedOverlay.value) {
      triggerSectionEOverlay()
    }
    return
  }

  // If there are more dialogue lines in current scene, advance dialogue
  const linesCount = currentScene.value?.lines?.length || 0
  if (currentLineIndex.value < linesCount - 1) {
    currentLineIndex.value++
    startTypewriter()
    return
  }

  // If there are more scenes, advance to next scene
  if (currentSceneIndex.value < totalScenesCount.value - 1) {
    currentSceneIndex.value++
    currentLineIndex.value = 0
    emit('scene-change', {
      sceneIndex: currentSceneIndex.value,
      sceneId: currentScene.value?.id
    })
    startTypewriter()
    return
  }

  // Narrative finished: Execute final respectful fade and emit complete
  finishCinematic()
}

function finishCinematic() {
  if (isFadingOut.value) return
  isFadingOut.value = true
  stopTypewriter()
  stopPauseTimeout()

  setTimeout(() => {
    emit('complete')
  }, 1200)
}

function onGlobalKey(e) {
  if (e.key === ' ' || e.key === 'Enter') {
    e.preventDefault()
    handleAdvanceClick()
  }
}

function handleImageError(e) {
  if (currentScene.value?.fallbackImage) {
    const fallback = resolveAsset(currentScene.value.fallbackImage)
    if (e.target.src !== fallback) {
      console.warn(`[Cutscene] Missing asset ${e.target.src}, falling back to ${fallback}`)
      e.target.src = fallback
    }
  }
}

watch(currentSceneIndex, () => {
  if (showUnfinishedOverlay.value) {
    triggerSectionEOverlay()
  } else {
    showBeginningText.value = false
  }
})

onMounted(async () => {
  window.addEventListener('keydown', onGlobalKey)
  preloadCutsceneAssets().catch(err => {
    console.warn('[Cutscene] Non-critical preload warning:', err)
  })
  startTypewriter()
})

onUnmounted(() => {
  stopTypewriter()
  stopPauseTimeout()
  if (overlayTimer.value) clearTimeout(overlayTimer.value)
  window.removeEventListener('keydown', onGlobalKey)
})
</script>

<style scoped>
.creator-post-defeat-cinematic {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  margin: 0 auto;
  gap: 0.75rem;
  padding: 0;
  box-sizing: border-box;
  animation: cinematic-fade-in 0.8s ease both;
  transition: opacity 1.2s ease, filter 1.2s ease;
}

.creator-post-defeat-cinematic.is-fading-out {
  opacity: 0;
  filter: brightness(0);
}

@keyframes cinematic-fade-in {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ══════════════════════════════════════════════════════════════
   ZONE 1: UPPER MATCH CONTAINER (THE ARTWORK VIEWPORT)
   ══════════════════════════════════════════════════════════════ */
.cinematic-match-container {
  width: 100%;
  border-radius: 8px;
  background: #05060a;
  border: 2px solid rgba(225, 29, 72, 0.4);
  border-top: 3px solid var(--color-crimson-bright, #e11d48);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.95), 0 0 30px rgba(225, 29, 72, 0.15);
  overflow: hidden;
  position: relative;
  box-sizing: border-box;
}

.cinematic-viewport {
  position: relative;
  width: 100%;
  aspect-ratio: 3000 / 1280;
  min-height: 520px;
  max-height: clamp(600px, 82vh, 960px);
  background: #020204;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cinematic-scene-frame {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #000;
}

.cinematic-scene-frame.is-blackscreen {
  background: #000;
}

.cinematic-scene-asset {
  width: 100%;
  height: 100%;
  max-height: 100%;
  object-fit: cover;
  object-position: center;
  image-rendering: auto;
  filter: saturate(0.96) brightness(0.92);
  transition: filter 1.2s ease;
}

.cinematic-scene-asset.has-character-overlay {
  filter: saturate(0.8) brightness(0.65) blur(1px);
}

.cinematic-scene-asset.is-blackscreen-img {
  opacity: 0.1;
}

/* Initial Reveal: WriterOrig.png overlay */
.cinematic-writer-container {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  z-index: 5;
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: none;
}

.writer-radiance-aura {
  position: absolute;
  width: 480px;
  height: 480px;
  left: 50%;
  top: 45%;
  transform: translate(-50%, -50%);
  background: radial-gradient(circle, rgba(244, 114, 182, 0.35) 0%, rgba(168, 85, 247, 0.2) 50%, transparent 75%);
  filter: blur(28px);
  animation: aura-pulse 3.5s ease-in-out infinite alternate;
}

@keyframes aura-pulse {
  from { transform: translate(-50%, -50%) scale(0.92); opacity: 0.7; }
  to { transform: translate(-50%, -50%) scale(1.1); opacity: 1; }
}

.cinematic-writer-sprite {
  height: clamp(380px, 50vh, 620px);
  width: auto;
  aspect-ratio: 1 / 1;
  object-fit: contain;
  filter: drop-shadow(0 14px 30px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 25px rgba(244, 114, 182, 0.45));
  animation: writer-float 4.2s ease-in-out infinite alternate;
}

@keyframes writer-float {
  from { transform: translateY(0); }
  to { transform: translateY(-8px); }
}

.writer-status-tag {
  margin-top: 0.6rem;
  font-size: 0.65rem;
  letter-spacing: 2px;
  color: #fbcfe8;
  background: rgba(15, 10, 20, 0.85);
  border: 1px solid rgba(244, 114, 182, 0.5);
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.8);
}

/* Story Section E Overlay: THE END -> Or perhaps... the beginning. */
.cinematic-unfinished-overlay {
  position: absolute;
  inset: 0;
  z-index: 6;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: radial-gradient(ellipse at center, rgba(3, 4, 8, 0.65) 0%, rgba(3, 4, 8, 0.9) 100%);
  pointer-events: none;
  gap: 1.25rem;
}

.unfinished-the-end {
  font-size: clamp(2.2rem, 4vw, 3.4rem);
  letter-spacing: 8px;
  color: #f5f3ff;
  text-shadow: 0 0 25px rgba(255, 255, 255, 0.75), 0 0 50px rgba(225, 29, 72, 0.6);
  margin: 0;
  transition: opacity 2.2s ease, transform 2.2s ease;
}

.unfinished-the-end.is-faded {
  opacity: 0.35;
  transform: scale(0.95);
}

.unfinished-beginning {
  font-size: clamp(1.1rem, 2.2vw, 1.6rem);
  letter-spacing: 4px;
  color: var(--color-gold-ancient, #fbbf24);
  text-shadow: 0 0 20px rgba(251, 191, 36, 0.7);
  margin: 0;
}

/* Vignette Layer */
.cinematic-vignette-layer {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(ellipse at center, transparent 40%, rgba(0, 0, 0, 0.8) 100%);
  box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.9);
}

/* Top HUD */
.cinematic-top-hud {
  position: absolute;
  top: 0.75rem;
  left: 0.85rem;
  right: 0.85rem;
  z-index: 7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  pointer-events: none;
}

.hud-act-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(10, 11, 18, 0.85);
  border: 1px solid rgba(225, 29, 72, 0.45);
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
}

.act-star {
  color: var(--color-crimson-bright, #e11d48);
  font-size: 0.75rem;
}

.act-name {
  font-size: 0.62rem;
  letter-spacing: 2px;
  color: #f1f5f9;
}

.hud-scene-counter {
  font-size: 0.58rem;
  letter-spacing: 1.5px;
  color: #94a3b8;
  background: rgba(10, 11, 18, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.3);
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
}

/* ══════════════════════════════════════════════════════════════
   ZONE 2: DIALOGUE INTERFACE (DIRECTLY BELOW MATCH CONTAINER)
   ══════════════════════════════════════════════════════════════ */
.cinematic-dialogue-panel {
  width: 100%;
  border-radius: 8px;
  background: linear-gradient(180deg, rgba(15, 17, 26, 0.96) 0%, rgba(8, 10, 16, 0.98) 100%);
  border: 2px solid rgba(226, 217, 204, 0.28);
  border-top: 3px solid var(--color-crimson-bright, #e11d48);
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.85);
  padding: 1rem 1.4rem 0.9rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  cursor: pointer;
  user-select: none;
  min-height: 160px;
}

.cinematic-dialogue-panel:hover {
  border-color: rgba(226, 217, 204, 0.4);
}

.cinematic-speaker-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.speaker-tag {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  font-size: 0.65rem;
  letter-spacing: 1.5px;
}

.speaker-player {
  background: rgba(14, 116, 144, 0.25);
  border: 1px solid rgba(56, 189, 248, 0.5);
  color: #38bdf8;
}

.speaker-merchant {
  background: rgba(180, 83, 9, 0.25);
  border: 1px solid rgba(251, 191, 36, 0.5);
  color: #fbbf24;
}

.speaker-daughter {
  background: rgba(190, 24, 93, 0.25);
  border: 1px solid rgba(244, 114, 182, 0.5);
  color: #f472b6;
}

.speaker-default {
  background: rgba(55, 65, 81, 0.25);
  border: 1px solid rgba(156, 163, 175, 0.4);
  color: #e2e8f0;
}

.narration-tag {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.62rem;
  letter-spacing: 1.5px;
  color: #cbd5e1;
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(148, 163, 184, 0.35);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

.speaker-role {
  font-size: 0.55rem;
  opacity: 0.85;
}

.dialogue-keyboard-hint {
  font-size: 0.52rem;
  color: #94a3b8;
  letter-spacing: 1px;
}

.cinematic-text-area {
  flex: 1;
  min-height: 58px;
  display: flex;
  align-items: center;
}

.cinematic-text-paragraph {
  margin: 0;
  font-family: var(--font-body, Georgia, serif);
  font-size: clamp(0.96rem, 1.8vw, 1.15rem);
  line-height: 1.75;
  color: #f1f5f9;
  white-space: pre-line;
}

.cinematic-text-paragraph.is-narration {
  font-style: italic;
  color: #cbd5e1;
}

.cinematic-text-paragraph.is-quiet {
  color: #e2e8f0;
}

.typing-cursor {
  display: inline-block;
  color: var(--color-crimson-bright, #e11d48);
  animation: blink-cursor 0.7s infinite;
  margin-left: 2px;
}

@keyframes blink-cursor {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}

.cinematic-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  margin-top: 0.25rem;
}

.scene-progress-dots {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.prog-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(148, 163, 184, 0.25);
  transition: all 0.3s ease;
}

.prog-dot.is-active {
  background: var(--color-crimson-bright, #e11d48);
  box-shadow: 0 0 8px rgba(225, 29, 72, 0.8);
  transform: scale(1.3);
}

.prog-dot.is-done {
  background: var(--color-gold-ancient, #fbbf24);
  opacity: 0.7;
}

.cinematic-continue-btn {
  padding: 0.45rem 1.1rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  letter-spacing: 1.5px;
  border-radius: 4px;
}

.btn-gothic-crimson {
  background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 100%);
  color: #fff;
  border: 1px solid rgba(239, 68, 68, 0.6);
  box-shadow: 0 4px 15px rgba(153, 27, 27, 0.5);
}

.btn-gothic-crimson:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #991b1b 100%);
  border-color: #f87171;
}

.btn-gothic-gold {
  background: linear-gradient(180deg, #d97706 0%, #b45309 100%);
  color: #fff;
  border: 1px solid rgba(251, 191, 36, 0.7);
  box-shadow: 0 4px 18px rgba(217, 119, 6, 0.6);
}

.btn-pulse {
  animation: gold-pulse 1.8s infinite alternate;
}

@keyframes gold-pulse {
  from { box-shadow: 0 0 10px rgba(251, 191, 36, 0.4); transform: scale(1); }
  to { box-shadow: 0 0 20px rgba(251, 191, 36, 0.85); transform: scale(1.03); }
}

/* Transitions */
.scene-crossfade-enter-active, .scene-crossfade-leave-active {
  transition: opacity 0.8s ease;
}
.scene-crossfade-enter-from, .scene-crossfade-leave-to {
  opacity: 0;
}

.writer-reveal-enter-active {
  transition: opacity 1.2s ease, transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.writer-reveal-leave-active {
  transition: opacity 0.6s ease;
}
.writer-reveal-enter-from {
  opacity: 0;
  transform: translate(-50%, -46%) scale(0.92);
}
.writer-reveal-leave-to {
  opacity: 0;
}

@media (max-width: 1024px) {
  .cinematic-viewport {
    min-height: 380px;
    max-height: 65vh;
  }
}

@media (max-width: 768px) {
  .cinematic-viewport {
    min-height: 240px;
    max-height: none;
    aspect-ratio: 3000 / 1280;
  }
  .cinematic-dialogue-panel {
    padding: 0.85rem 1rem;
  }
  .dialogue-keyboard-hint {
    display: none;
  }
}
</style>
