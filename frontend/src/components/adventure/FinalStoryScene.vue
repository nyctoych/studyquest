<template>
  <div
    class="fss"
    :class="[`tone-${tone}`, { 'is-collapsing': collapse }]"
    role="dialog"
    :aria-label="title || 'Story scene'"
    @click="advance"
  >
    <div class="fss-bg" :style="{ backgroundImage: background ? `url('${background}')` : 'none' }"></div>
    <ArchivePhaseFx v-if="fxPhase" :phase="fxPhase" />
    <div class="fss-vignette"></div>

    <div v-if="title" class="fss-title">{{ title }}</div>

    <button v-if="allowSkip" class="fss-skip" type="button" @click.stop="finish">SKIP ▸▸</button>

    <!-- Speaker portrait -->
    <transition name="fss-portrait" mode="out-in">
      <img
        v-if="current?.portrait"
        :key="current.portrait"
        :src="current.portrait"
        :alt="current.speaker || ''"
        class="fss-portrait pixel-art"
        :class="{ 'is-fading': current.fade }"
      />
    </transition>

    <!-- Dialogue / narration box -->
    <div class="fss-box" :class="{ 'is-narration': !current?.speaker }">
      <div v-if="current?.speaker" class="fss-speaker">{{ current.speaker }}<span v-if="current.role"> — {{ current.role }}</span></div>
      <p class="fss-text" :class="{ 'is-italic': !current?.speaker }">{{ shown }}</p>
      <div class="fss-foot">
        <span class="fss-hint">{{ revealing ? 'Click to reveal' : (isLast ? 'Click to continue' : 'Click to advance') }}</span>
        <button class="fss-next" type="button" @click.stop="advance">{{ isLast && !revealing ? continueLabel : '▸' }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import ArchivePhaseFx from './ArchivePhaseFx.vue'

const props = defineProps({
  lines: { type: Array, required: true },       // [{ speaker?, role?, text, portrait?, fade? }]
  background: { type: String, default: '' },
  title: { type: String, default: '' },
  fxPhase: { type: Number, default: 0 },        // 0 = none, 1..3 = ArchivePhaseFx
  collapse: { type: Boolean, default: false },  // heavier desaturation / shake for the collapse scene
  tone: { type: String, default: 'archive' },   // 'archive' | 'calm' | 'epilogue'
  allowSkip: { type: Boolean, default: true },
  continueLabel: { type: String, default: 'CONTINUE ▸' }
})
const emit = defineEmits(['done'])

const index = ref(0)
const shown = ref('')
const revealing = ref(false)
let timer = null

const current = computed(() => props.lines[index.value] || null)
const isLast = computed(() => index.value >= props.lines.length - 1)

function stopTimer() {
  if (timer) { clearInterval(timer); timer = null }
}

function startReveal() {
  stopTimer()
  const full = current.value?.text || ''
  shown.value = ''
  if (!full) { revealing.value = false; return }
  revealing.value = true
  let i = 0
  timer = setInterval(() => {
    i += 2
    shown.value = full.slice(0, i)
    if (i >= full.length) {
      shown.value = full
      revealing.value = false
      stopTimer()
    }
  }, 22)
}

function advance() {
  if (revealing.value) {
    stopTimer()
    shown.value = current.value?.text || ''
    revealing.value = false
    return
  }
  if (isLast.value) return finish()
  index.value++
}

let finished = false
function finish() {
  if (finished) return
  finished = true
  stopTimer()
  emit('done')
}

function onKey(e) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    advance()
  }
}

watch(index, startReveal)
onMounted(() => {
  startReveal()
  window.addEventListener('keydown', onKey)
})
onUnmounted(() => {
  stopTimer()
  window.removeEventListener('keydown', onKey)
})
</script>

<style scoped>
.fss {
  position: fixed;
  inset: 0;
  z-index: 60;
  background: #030305;
  overflow: hidden;
  cursor: pointer;
  user-select: none;
  animation: fss-in 0.9s ease both;
}
@keyframes fss-in { from { opacity: 0; } to { opacity: 1; } }

.fss-bg {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  filter: saturate(0.85) brightness(0.8);
  transition: filter 1.6s ease;
}
.tone-calm .fss-bg { filter: saturate(0.7) brightness(0.62) sepia(0.15); }
.tone-epilogue .fss-bg { filter: saturate(0.9) brightness(0.9); }
.is-collapsing .fss-bg { filter: saturate(0.3) brightness(0.55) contrast(1.15); animation: fss-shake 3.4s ease-in-out infinite; }
@keyframes fss-shake {
  0%, 100% { transform: scale(1.04) translate(0, 0); }
  25% { transform: scale(1.05) translate(-3px, 2px); }
  50% { transform: scale(1.04) translate(2px, -2px); }
  75% { transform: scale(1.06) translate(-2px, -1px); }
}
.fss-vignette {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(ellipse at 50% 45%, transparent 35%, rgba(0, 0, 0, 0.78) 100%);
}

.fss-title {
  position: absolute;
  top: 1.4rem;
  left: 50%;
  transform: translateX(-50%);
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  letter-spacing: 3px;
  color: rgba(226, 217, 204, 0.72);
  text-align: center;
  padding: 0.4rem 1rem;
  border-top: 1px solid rgba(226, 217, 204, 0.25);
  border-bottom: 1px solid rgba(226, 217, 204, 0.25);
  z-index: 3;
}
.fss-skip {
  position: absolute;
  top: 1.2rem;
  right: 1.2rem;
  z-index: 4;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  letter-spacing: 1px;
  color: rgba(226, 217, 204, 0.7);
  background: rgba(0, 0, 0, 0.55);
  border: 1px solid rgba(226, 217, 204, 0.3);
  padding: 0.45rem 0.7rem;
  cursor: pointer;
}
.fss-skip:hover { color: #fff; border-color: rgba(255, 255, 255, 0.6); }

.fss-portrait {
  position: absolute;
  left: 50%;
  bottom: 38%;
  transform: translateX(-50%);
  height: min(46vh, 380px);
  max-width: 80vw;
  object-fit: contain;
  z-index: 2;
  filter: drop-shadow(0 12px 28px rgba(0, 0, 0, 0.85));
}
.fss-portrait.is-fading { opacity: 0.55; filter: drop-shadow(0 0 22px rgba(255, 255, 255, 0.25)) blur(0.4px); }
.fss-portrait-enter-active, .fss-portrait-leave-active { transition: opacity 0.5s ease; }
.fss-portrait-enter-from, .fss-portrait-leave-to { opacity: 0; }

.fss-box {
  position: absolute;
  left: 50%;
  bottom: 4vh;
  transform: translateX(-50%);
  width: min(760px, 92vw);
  min-height: 150px;
  z-index: 3;
  padding: 1rem 1.3rem 0.8rem;
  background: linear-gradient(180deg, rgba(10, 10, 16, 0.86), rgba(5, 5, 9, 0.94));
  border: 2px solid rgba(226, 217, 204, 0.28);
  border-top-color: rgba(185, 28, 28, 0.7);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}
.tone-calm .fss-box { border-top-color: rgba(230, 200, 69, 0.55); }
.tone-epilogue .fss-box { border-top-color: rgba(226, 217, 204, 0.5); }
.fss-box.is-narration { text-align: center; }
.fss-speaker {
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  letter-spacing: 1.5px;
  color: var(--color-gold-ancient, #e6c845);
}
.fss-text {
  margin: 0;
  flex: 1;
  font-family: var(--font-body, Georgia, serif);
  font-size: clamp(0.92rem, 2.1vw, 1.08rem);
  line-height: 1.7;
  color: #e9e1d4;
  white-space: pre-line;
}
.fss-text.is-italic { font-style: italic; color: #d4cbbb; }
.fss-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.fss-hint {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  letter-spacing: 1px;
  color: rgba(226, 217, 204, 0.45);
}
.fss-next {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: #fff;
  background: rgba(139, 26, 26, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.25);
  padding: 0.4rem 0.8rem;
  cursor: pointer;
}
.tone-calm .fss-next, .tone-epilogue .fss-next { background: rgba(60, 60, 78, 0.85); }
.fss-next:hover { filter: brightness(1.25); }
</style>
