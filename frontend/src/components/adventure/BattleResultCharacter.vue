<template>
  <div
    class="battle-result-character-container"
    :class="[
      `theme-${characterClass.toLowerCase()}`,
      `result-${result}`,
      `state-${lifecycleState.toLowerCase()}`,
      `size-${size}`
    ]"
    role="region"
    :aria-label="`${characterName} Battle Result`"
  >
    <!-- Result Character Pedestal / Combat Stage Ring -->
    <div class="result-pedestal">
      <div class="result-pedestal-glow" :class="result === 'victory' ? 'glow-victory' : 'glow-defeat'"></div>
      <div class="result-pedestal-ring"></div>
      <div class="result-pedestal-shadow"></div>
    </div>

    <!-- Character Sprite Frame -->
    <div class="result-character-wrapper" :style="wrapperStyle">
      <!-- Continuous looping victory GIF or full death GIF -> static death PNG -->
      <img
        v-if="displayedImageSrc"
        :src="displayedImageSrc"
        :alt="`${characterName} ${result === 'victory' ? 'Victory' : 'Defeat'}`"
        class="result-character-sprite pixel-art"
        :class="{
          'is-victory-loop': lifecycleState === 'RESULT_PLAYING_VICTORY',
          'is-death-anim': lifecycleState === 'RESULT_PLAYING_DEATH',
          'is-death-static': lifecycleState === 'RESULT_DEATH_STATIC'
        }"
      />

      <!-- Grounding particle aura -->
      <div v-if="result === 'victory'" class="victory-light-beams"></div>
      <div v-else class="defeat-dissolve-mist"></div>
    </div>

    <!-- Character Identification Pill -->
    <div class="result-character-nameplate font-pixel">
      <span class="char-icon">{{ classIcon }}</span>
      <span class="char-name">{{ characterName }}</span>
      <span class="char-role">{{ characterClass }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { resolveAsset } from '../../services/bossMapping'
import { getCharacterAnimationConfig } from '../../config/characterAnimations'

const props = defineProps({
  result: {
    type: String,
    required: true,
    validator: (v) => ['victory', 'defeat'].includes(v)
  },
  character: {
    type: Object,
    default: null
  },
  size: {
    type: String,
    default: 'lg',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v)
  }
})

const auth = useAuthStore()

// Dedicated Result Animation Lifecycle
// 'RESULT_INITIALIZING' | 'RESULT_PLAYING_DEATH' | 'RESULT_DEATH_STATIC' | 'RESULT_PLAYING_VICTORY' | 'RESULT_CLOSED'
const lifecycleState = ref('RESULT_INITIALIZING')
const activeImageSrc = ref('')
let deathTimer = null

const activeChar = computed(() => {
  if (props.character) return props.character
  if (auth.selectedCharacter) return auth.selectedCharacter
  if (auth.user?.selected_character) return auth.user.selected_character
  return null
})

const characterClass = computed(() => {
  const name = activeChar.value?.name?.toLowerCase() || ''
  if (name.includes('archer') || name.includes('range')) return 'RANGE'
  if (name.includes('dual') || name.includes('wielder') || name.includes('melee')) return 'MELEE'
  if (name.includes('necro') || name.includes('mage')) return 'MAGE'
  const raw = activeChar.value?.class_type || activeChar.value?.code || activeChar.value?.key_name || 'MAGE'
  return String(raw).toUpperCase()
})

const characterName = computed(() => {
  if (activeChar.value?.name) return activeChar.value.name
  if (characterClass.value === 'RANGE') return 'Archer'
  if (characterClass.value === 'MELEE') return 'Dual-Wielder'
  return 'Necromancer'
})

const classIcon = computed(() => {
  if (characterClass.value === 'RANGE') return '🏹'
  if (characterClass.value === 'MELEE') return '⚔️'
  return '🔮'
})

const animConfig = computed(() => {
  return getCharacterAnimationConfig(activeChar.value || characterClass.value)
})

const wrapperStyle = computed(() => {
  const scale = animConfig.value?.resultScale || animConfig.value?.scale || 1.15
  const offsetY = animConfig.value?.resultOffsetY ?? animConfig.value?.offsetY ?? 0
  return {
    transform: `scale(${scale}) translateY(${offsetY}px)`
  }
})

const displayedImageSrc = computed(() => {
  return activeImageSrc.value ? resolveAsset(activeImageSrc.value) : ''
})

function initAnimation() {
  if (deathTimer) {
    clearTimeout(deathTimer)
    deathTimer = null
  }

  const cfg = animConfig.value

  if (props.result === 'victory') {
    // ══════════════════════════════════════════════════════════════
    // VICTORY: Continuous looping victory GIF without static replacement
    // ══════════════════════════════════════════════════════════════
    lifecycleState.value = 'RESULT_PLAYING_VICTORY'
    activeImageSrc.value = cfg?.resultVictory || cfg?.idle || cfg?.sprite
  } else {
    // ══════════════════════════════════════════════════════════════
    // DEFEAT: Play full death GIF, then transition to static death PNG
    // ══════════════════════════════════════════════════════════════
    lifecycleState.value = 'RESULT_PLAYING_DEATH'
    const deathGif = cfg?.resultDeath || cfg?.sprite
    // Append single animation timestamp to force playback from frame 0
    const sep = deathGif.includes('?') ? '&' : '?'
    activeImageSrc.value = `${deathGif}${sep}anim_t=${Date.now()}`

    const duration = cfg?.deathDuration || 4400
    deathTimer = setTimeout(() => {
      lifecycleState.value = 'RESULT_DEATH_STATIC'
      activeImageSrc.value = cfg?.resultDeathStatic || cfg?.sprite
    }, duration)
  }
}

watch(() => [props.result, props.character?.id], () => {
  initAnimation()
})

onMounted(() => {
  initAnimation()
})

onUnmounted(() => {
  if (deathTimer) {
    clearTimeout(deathTimer)
    deathTimer = null
  }
  lifecycleState.value = 'RESULT_CLOSED'
})
</script>

<style scoped>
.battle-result-character-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin: 0.5rem auto 1.25rem;
  width: 100%;
  max-width: 280px;
  min-height: 200px;
}

.size-sm {
  max-width: 180px;
  min-height: 140px;
}

.size-md {
  max-width: 220px;
  min-height: 170px;
}

.size-lg {
  max-width: 280px;
  min-height: 210px;
}

.size-xl {
  max-width: 320px;
  min-height: 240px;
}

/* Pedestal */
.result-pedestal {
  position: absolute;
  bottom: 22px;
  left: 50%;
  transform: translateX(-50%);
  width: 180px;
  height: 48px;
  border-radius: 50%;
  z-index: 1;
}

.result-pedestal-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px solid rgba(245, 158, 11, 0.45);
  box-shadow: 0 0 16px rgba(245, 158, 11, 0.35);
}

.result-defeat .result-pedestal-ring {
  border-color: rgba(185, 28, 28, 0.5);
  box-shadow: 0 0 16px rgba(185, 28, 28, 0.4);
}

.result-pedestal-glow {
  position: absolute;
  inset: -10px;
  border-radius: 50%;
  filter: blur(12px);
  opacity: 0.7;
}

.glow-victory {
  background: radial-gradient(ellipse at center, rgba(234, 179, 8, 0.4) 0%, transparent 70%);
}

.glow-defeat {
  background: radial-gradient(ellipse at center, rgba(127, 29, 29, 0.5) 0%, transparent 70%);
}

.result-pedestal-shadow {
  position: absolute;
  bottom: -6px;
  left: 10%;
  width: 80%;
  height: 12px;
  background: rgba(0, 0, 0, 0.75);
  border-radius: 50%;
  filter: blur(4px);
}

/* Character Sprite Wrapper */
.result-character-wrapper {
  position: relative;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 180px;
  height: 180px;
  transition: transform 0.3s ease;
}

.result-character-sprite {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.85));
}

.result-character-sprite.is-victory-loop {
  animation: float-victory 3.2s ease-in-out infinite;
}

.result-character-sprite.is-death-anim {
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.9)) saturate(0.85);
}

.result-character-sprite.is-death-static {
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.95)) saturate(0.7) brightness(0.9);
}

@keyframes float-victory {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

/* Victory Beams & Defeat Mist */
.victory-light-beams {
  position: absolute;
  inset: -20px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, transparent 70%);
  pointer-events: none;
  animation: pulse-aura 2.4s ease-in-out infinite;
}

.defeat-dissolve-mist {
  position: absolute;
  bottom: -10px;
  left: 0;
  right: 0;
  height: 40px;
  background: radial-gradient(ellipse at center, rgba(17, 24, 39, 0.6) 0%, transparent 75%);
  pointer-events: none;
}

@keyframes pulse-aura {
  0%, 100% { opacity: 0.5; transform: scale(0.98); }
  50% { opacity: 0.9; transform: scale(1.05); }
}

/* Nameplate */
.result-character-nameplate {
  position: relative;
  z-index: 10;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(15, 12, 24, 0.95);
  border: 1px solid rgba(245, 158, 11, 0.4);
  border-radius: 4px;
  padding: 0.25rem 0.65rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
  font-size: 0.65rem;
  margin-top: 0.45rem;
}

.result-defeat .result-character-nameplate {
  border-color: rgba(239, 68, 68, 0.4);
}

.char-icon {
  font-size: 0.75rem;
}

.char-name {
  color: #f8fafc;
  font-family: 'Cinzel', serif;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.char-role {
  color: #fbbf24;
  font-size: 0.55rem;
  letter-spacing: 0.75px;
}

.theme-range .char-role { color: #2dd4bf; }
.theme-melee .char-role { color: #f87171; }
</style>
