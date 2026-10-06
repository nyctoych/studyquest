<template>
  <div class="boss-battle-sprite-container" :class="[battleStateClass, { 'is-enraged-mode': isEnragedMode }]">
    <!-- Speech Bubble for Boss Mocking / Underestimation -->
    <EnemySpeechBubble
      v-if="speechText"
      :text="speechText"
      :speaker-name="bossName"
    />

    <!-- Colossal Boss Ground Pedestal -->
    <div class="battle-pedestal boss-pedestal" :style="{ '--accent': bossAccent }">
      <div class="pedestal-ring-outer"></div>
      <div class="pedestal-ring-inner"></div>
      <div class="pedestal-shadow"></div>
      <div class="boss-aura-flare"></div>
    </div>

    <!-- Boss Asset Sprite Wrapper -->
    <div class="boss-sprite-wrapper" :class="[animClass, { 'is-creator-sprite-wrapper': isFinalBoss }]">
      <img
        :src="activeBossSprite"
        :alt="bossName"
        class="boss-real-sprite pixel-art"
        :class="{ 'animate-boss-hover': (!isFinalBoss || creatorAnimState === 'idle'), 'is-creator-sprite': isFinalBoss }"
        :style="{ filter: hitFilter }"
      />

      <!-- Slash Impact Effect on Correct Answer (non-Final Boss default slash) -->
      <div v-if="battleState === 'hit' && !isFinalBoss" class="boss-hit-slash"></div>
    </div>

    <!-- Retro Boss Nameplate with HP Gauge Bar -->
    <div v-if="!hideNameplate" class="boss-nameplate gothic-panel" :style="{ borderColor: bossAccent }">
      <div class="nameplate-row">
        <span class="boss-crest-icon">👑</span>
        <span class="boss-display-name">{{ bossName }}</span>
      </div>

      <!-- Boss HP Bar (Pokemon Style) -->
      <div class="boss-hp-bar-container">
        <span class="boss-hp-label">HP</span>
        <div class="boss-hp-track">
          <div
            class="boss-hp-fill"
            :class="{ 'hp-enraged': isEnragedMode, 'hp-warn': bossHpPct <= 50 && bossHpPct > 20, 'hp-crit': bossHpPct <= 20 }"
            :style="{ width: `${displayHpPct}%` }"
          ></div>
        </div>
        <span class="boss-hp-val">{{ Math.round(displayHpPct) }}%</span>
      </div>

      <div class="boss-sub-row">
        <span class="boss-title-tag">{{ bossTitle }}</span>
        <span class="boss-reaction-status" :class="[battleState, { 'state-enraged': isEnragedMode }]">
          {{ bossReactionLabel }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { resolveAsset } from '../../services/bossMapping'
import { CREATOR_ANIMATION_CONFIG } from '../../config/creatorAnimationConfig'
import EnemySpeechBubble from './EnemySpeechBubble.vue'

const props = defineProps({
  bossMeta: {
    type: Object,
    default: () => ({})
  },
  bossName: {
    type: String,
    default: 'Sanctum Guardian'
  },
  bossTitle: {
    type: String,
    default: 'Corrupted Archon'
  },
  battleState: {
    type: String,
    default: 'idle' // 'idle' | 'hit' | 'laughing'
  },
  speechText: {
    type: String,
    default: ''
  },
  bossHpPct: {
    type: Number,
    default: 100
  },
  isEnraged: {
    type: Boolean,
    default: false
  },
  isFinalBoss: {
    type: Boolean,
    default: false
  },
  creatorPhase: {
    type: Number,
    default: 1
  },
  creatorAnimState: {
    type: String,
    default: 'idle' // 'intro' | 'idle' | 'attack' | 'hit' | 'transform' | 'defeated_dialogue' | 'closing'
  },
  hideNameplate: {
    type: Boolean,
    default: false
  }
})

// Latch enraged mode: once boss life drops to 50% or lower, it stays angry until the battle ends!
const hasEnteredEnragedMode = ref(false)

watch(() => props.bossHpPct, (pct) => {
  if (pct <= 50) {
    hasEnteredEnragedMode.value = true
  }
}, { immediate: true })

watch(() => props.isEnraged, (enraged) => {
  if (enraged) {
    hasEnteredEnragedMode.value = true
  }
}, { immediate: true })

const isEnragedMode = computed(() => {
  return hasEnteredEnragedMode.value || props.isEnraged || props.bossHpPct <= 50
})

const displayHpPct = computed(() => {
  return Math.max(0, Math.min(100, props.bossHpPct))
})

const bossAccent = computed(() => {
  if (isEnragedMode.value) return '#ef4444'
  return props.bossMeta?.accentColor || '#e11d48'
})

function getAngryGifAsset(m) {
  if (!m) return ''
  // 1. Explicit animatedAngry property
  if (m.animatedAngry) return m.animatedAngry
  // 2. Any key containing 'angry' and ending with '.gif'
  for (const k of Object.keys(m)) {
    const val = m[k]
    if (typeof val === 'string' && val.toLowerCase().includes('angry') && val.toLowerCase().endsWith('.gif')) {
      return val
    }
  }
  // 3. Any key containing 'angry'
  for (const k of Object.keys(m)) {
    const val = m[k]
    if (typeof val === 'string' && val.toLowerCase().includes('angry')) {
      return val
    }
  }
  return m.angry || m.animated || m.happy || ''
}

const activeBossSprite = computed(() => {
  if (props.isFinalBoss) {
    const p = props.creatorPhase === 2 ? 'phase2' : 'phase1'
    switch (props.creatorAnimState) {
      case 'intro':
        return resolveAsset(CREATOR_ANIMATION_CONFIG.phase1.intro.path)
      case 'transform':
        return resolveAsset(CREATOR_ANIMATION_CONFIG.phase2.transform.path)
      case 'defeated_dialogue':
        return resolveAsset(CREATOR_ANIMATION_CONFIG.defeat.dialogue.path)
      case 'closing':
        return resolveAsset(CREATOR_ANIMATION_CONFIG.defeat.closing.path)
      case 'attack':
        return resolveAsset(CREATOR_ANIMATION_CONFIG[p].attack.path)
      case 'hit':
        return resolveAsset(CREATOR_ANIMATION_CONFIG[p].hit.path)
      case 'idle':
      default:
        return resolveAsset(CREATOR_ANIMATION_CONFIG[p].idle.path)
    }
  }

  const m = props.bossMeta
  if (!m) return ''

  // Defeated end-state (e.g. The Creator): static defeated pose, never restarts idle
  if (props.battleState === 'defeated' && m.defeatedSprite) {
    return resolveAsset(m.defeatedSprite)
  }
  // Boss-specific hit / dialogue animations when the asset set provides them
  if (props.battleState === 'hit' && m.animatedHit) {
    return resolveAsset(m.animatedHit)
  }
  if (props.battleState === 'laughing' && m.animatedTaunt) {
    return resolveAsset(m.animatedTaunt)
  }

  // When boss HP drops to 50% or lower, shift to angry mode (angry GIF)!
  // Also shift when mocking / laughing (skipped for bosses that keep their idle stance)
  if (!m.keepIdleWhenEnraged && (isEnragedMode.value || props.battleState === 'laughing')) {
    const angryAsset = getAngryGifAsset(m)
    return resolveAsset(angryAsset)
  }

  // Normal mode (> 50% HP): idle / happy animated GIF
  const defaultAsset = m.animated || m.animatedGif || m.happy || m.idle || m.sprite
  return resolveAsset(defaultAsset)
})

const battleStateClass = computed(() => `state-${props.battleState}`)

const animClass = computed(() => {
  if (props.isFinalBoss) {
    if (props.creatorAnimState === 'hit') return 'anim-boss-hit'
    return 'anim-boss-idle'
  }
  if (props.battleState === 'defeated') return 'anim-boss-defeated'
  if (props.battleState === 'hit') return 'anim-boss-hit'
  if (props.battleState === 'laughing') return 'anim-boss-laugh'
  return 'anim-boss-idle'
})

const hitFilter = computed(() => {
  if (props.isFinalBoss) {
    if (props.creatorAnimState === 'hit') return 'brightness(1.4) saturate(1.3) drop-shadow(0 0 20px #e11d48)'
    if (props.creatorPhase === 2) return 'drop-shadow(0 0 16px rgba(225, 29, 72, 0.7)) drop-shadow(0 8px 18px rgba(0, 0, 0, 0.8))'
    return 'drop-shadow(0 8px 18px rgba(0, 0, 0, 0.65))'
  }
  if (props.battleState === 'defeated') return 'grayscale(0.55) brightness(0.85) drop-shadow(0 0 18px rgba(167, 139, 250, 0.6))'
  if (props.battleState === 'hit' && props.bossMeta?.keepIdleWhenEnraged) return 'brightness(1.35) drop-shadow(0 0 18px rgba(251, 191, 36, 0.75))'
  if (props.battleState === 'hit') return 'brightness(2.2) saturate(1.8) drop-shadow(0 0 20px #ef4444)'
  if (isEnragedMode.value) return 'drop-shadow(0 0 16px rgba(239, 68, 68, 0.75)) drop-shadow(0 8px 18px rgba(0, 0, 0, 0.8))'
  return 'drop-shadow(0 8px 18px rgba(0, 0, 0, 0.65))'
})

const bossReactionLabel = computed(() => {
  if (props.isFinalBoss) {
    if (props.creatorAnimState === 'intro') return '✒ MANIFESTING'
    if (props.creatorAnimState === 'transform') return '✒ UNSEALING PHASE II'
    if (props.creatorAnimState === 'attack') return '✒ REWRITING REALITY'
    if (props.creatorAnimState === 'hit') return '✒ FRACTURED INK'
    if (props.creatorPhase === 2) return 'PHASE II — THE UNWRITTEN'
    return 'PHASE I — THE AUTHOR'
  }
  const custom = props.bossMeta?.reactionLabels
  if (custom) {
    if (props.battleState === 'defeated') return custom.defeated
    if (props.battleState === 'hit') return custom.hit
    if (props.battleState === 'laughing') return custom.laughing
    if (isEnragedMode.value) return custom.enraged
    return props.bossMeta.roleLabel || 'GUARDIAN'
  }
  if (props.battleState === 'hit') return '💥 FRACTURED'
  if (props.battleState === 'laughing') return '🔥 ENRAGED MOCKERY'
  if (isEnragedMode.value) return '🔥 ENRAGED'
  return 'GUARDIAN'
})
</script>

<style scoped>
.boss-battle-sprite-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  width: 250px;
  height: 200px;
}

/* Colossal Boss Pedestal */
.boss-pedestal {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 210px;
  height: 52px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(147, 51, 234, 0.45) 0%, rgba(15, 6, 22, 0.95) 70%);
  border: 2px solid var(--accent, #e11d48);
  box-shadow: 0 0 28px rgba(225, 29, 72, 0.45), inset 0 0 16px rgba(147, 51, 234, 0.6);
  z-index: 1;
}

.pedestal-ring-inner {
  position: absolute;
  inset: 6px;
  border-radius: 50%;
  border: 1.5px dashed rgba(251, 113, 133, 0.5);
}

.pedestal-shadow {
  position: absolute;
  bottom: -8px;
  left: 10%;
  width: 80%;
  height: 18px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  filter: blur(5px);
}

.boss-aura-flare {
  position: absolute;
  inset: -15px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(225, 29, 72, 0.25) 0%, transparent 70%);
  animation: aura-pulse 3s infinite ease-in-out;
}

@keyframes aura-pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 0.9; }
}

/* Boss Sprite Wrapper */
.boss-sprite-wrapper {
  position: relative;
  z-index: 5;
  margin-bottom: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.boss-real-sprite {
  width: 160px;
  height: 160px;
  object-fit: contain;
  transition: transform 0.25s ease, filter 0.2s ease;
}

/* Idle Hover */
.animate-boss-hover {
  animation: boss-hover 3s ease-in-out infinite;
}

@keyframes boss-hover {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

/* Hit Shake Animation on Correct Answer */
.anim-boss-hit {
  animation: boss-hit-shake 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes boss-hit-shake {
  10%, 90% { transform: translate3d(-6px, 0, 0) scale(0.96); }
  20%, 80% { transform: translate3d(8px, -4px, 0) scale(0.96); }
  30%, 50%, 70% { transform: translate3d(-10px, 4px, 0) scale(0.97); }
  40%, 60% { transform: translate3d(10px, -2px, 0) scale(0.98); }
}

/* Laugh / Rage Animation on Wrong Answer */
.anim-boss-laugh {
  animation: boss-rage-laugh 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) infinite;
}

@keyframes boss-rage-laugh {
  0%, 100% {
    transform: translateY(0) scale(1);
  }
  30% {
    transform: translateY(-16px) scale(1.14) rotate(-3deg);
  }
  70% {
    transform: translateY(-10px) scale(1.12) rotate(3deg);
  }
}

/* Hit Slash */
.boss-hit-slash {
  position: absolute;
  inset: -20px;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.9) 0%, rgba(225, 29, 72, 0.6) 40%, transparent 70%);
  animation: boss-slash 0.5s ease-out;
  pointer-events: none;
}

@keyframes boss-slash {
  0% { transform: scale(0.3) rotate(30deg); opacity: 1; }
  100% { transform: scale(1.8) rotate(30deg); opacity: 0; }
}

/* Boss Nameplate */
.boss-nameplate {
  position: absolute;
  bottom: -24px;
  left: 50%;
  transform: translateX(-50%);
  width: max-content;
  min-width: 250px;
  max-width: 380px;
  background: rgba(18, 6, 16, 0.96);
  border: 1.5px solid #e11d48;
  border-radius: 6px;
  padding: 0.4rem 0.85rem;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 12px rgba(225, 29, 72, 0.35);
  z-index: 10;
  box-sizing: border-box;
}

.nameplate-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.boss-crest-icon {
  font-size: 0.8rem;
}

.boss-display-name {
  font-family: 'Cinzel', serif;
  font-size: clamp(0.72rem, 1vw, 0.86rem);
  font-weight: 800;
  color: #fdf2f8;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

/* Boss HP Bar */
.boss-hp-bar-container {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0.25rem 0 0.15rem;
}

.boss-hp-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.44rem;
  font-weight: 700;
  color: #fbbf24;
  letter-spacing: 0.5px;
}

.boss-hp-track {
  flex: 1;
  height: 6px;
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.boss-hp-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
  transition: width 0.4s ease, background 0.3s ease;
  border-radius: 2px;
}

.boss-hp-fill.hp-warn {
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.boss-hp-fill.hp-enraged,
.boss-hp-fill.hp-crit {
  background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%);
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.85);
}

.boss-hp-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.42rem;
  color: #e2e8f0;
  min-width: 32px;
  text-align: right;
}

.boss-sub-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.2rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.46rem;
}

.boss-title-tag {
  color: #f472b6;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 220px;
}

.boss-reaction-status {
  color: #cbd5e1;
}
.boss-reaction-status.state-hit { color: #f87171; }
.boss-reaction-status.state-laughing { color: #fbbf24; }
.boss-reaction-status.state-enraged {
  color: #ef4444;
  font-weight: 700;
  text-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
  animation: pulse-enraged-tag 1.2s infinite ease-in-out;
}

@keyframes pulse-enraged-tag {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(1.05); }
}

/* Enraged Mode Styles (<= 50% HP) */
.boss-battle-sprite-container.is-enraged-mode .boss-aura-flare {
  background: radial-gradient(ellipse at center, rgba(239, 68, 68, 0.6) 0%, rgba(185, 28, 28, 0.3) 40%, transparent 75%) !important;
  animation: enraged-aura-pulse 1.3s infinite ease-in-out !important;
}

.boss-battle-sprite-container.is-enraged-mode .boss-pedestal {
  border-color: #ef4444 !important;
  box-shadow: 0 0 35px rgba(239, 68, 68, 0.65), inset 0 0 20px rgba(185, 28, 28, 0.8) !important;
}

.boss-battle-sprite-container.is-enraged-mode .boss-real-sprite {
  filter: drop-shadow(0 0 16px rgba(239, 68, 68, 0.7)) drop-shadow(0 8px 20px rgba(0, 0, 0, 0.8));
}

@keyframes enraged-aura-pulse {
  0%, 100% { transform: scale(1); opacity: 0.6; }
  50% { transform: scale(1.15); opacity: 1; filter: drop-shadow(0 0 16px #ef4444); }
}
/* The Creator: defeated pose dissolves into ink/pages */
.anim-boss-defeated {
  animation: boss-ink-dissolve 3.4s ease-in forwards;
}

@keyframes boss-ink-dissolve {
  0% { opacity: 1; transform: translateY(0) scale(1); }
  70% { opacity: 0.75; transform: translateY(6px) scale(1.01); }
  100% { opacity: 0.35; transform: translateY(14px) scale(0.98); filter: blur(1.5px); }
}

.is-creator-sprite-wrapper {
  overflow: visible;
}

.is-creator-sprite {
  width: clamp(170px, 24vw, 240px) !important;
  height: clamp(170px, 24vw, 240px) !important;
  object-fit: contain !important;
}
</style>
