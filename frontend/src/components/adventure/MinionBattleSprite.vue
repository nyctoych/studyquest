<template>
  <div class="minion-battle-sprite-container" :class="battleStateClass">
    <!-- Speech Bubble for Mocking / Underestimation -->
    <EnemySpeechBubble
      v-if="speechText"
      :text="speechText"
      :speaker-name="enemyName"
    />

    <!-- Ground Pedestal / Dark Rune Circle -->
    <div class="battle-pedestal enemy-pedestal">
      <div class="pedestal-ring-outer"></div>
      <div class="pedestal-ring-inner"></div>
      <div class="pedestal-shadow"></div>
    </div>

    <!-- Minion Sprite Container -->
    <div class="minion-sprite-wrapper" :class="animClass">
      <!-- Custom Minion Sprite if configured in battleAssets -->
      <img
        v-if="spriteSrc"
        :src="spriteSrc"
        :alt="enemyName"
        class="minion-custom-sprite pixel-art"
      />

      <!-- Default Dark Fantasy Fracture Specter Placeholder SVG -->
      <div v-else class="minion-specter-placeholder">
        <svg class="specter-svg" viewBox="0 0 120 130" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <!-- Shadow Aberration Gradient -->
            <radialGradient id="specterGlow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" stop-color="#dc2626" stop-opacity="0.85" />
              <stop offset="50%" stop-color="#7f1d1d" stop-opacity="0.6" />
              <stop offset="100%" stop-color="#1e0505" stop-opacity="0" />
            </radialGradient>
            <linearGradient id="specterBodyGrad" x1="0%" y1="0%" x2="0%" y2="100%">
              <stop offset="0%" stop-color="#450a0a" />
              <stop offset="40%" stop-color="#2a0606" />
              <stop offset="100%" stop-color="#0a0202" />
            </linearGradient>
            <filter id="aberrationGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
          </defs>

          <!-- Outer Dark Aura -->
          <circle cx="60" cy="60" r="45" fill="url(#specterGlow)" />

          <!-- Phantom Tendrils / Spikes -->
          <g class="specter-tendrils" opacity="0.9">
            <path d="M 60,15 Q 75,2 85,12 Q 78,25 70,30 Z" fill="#991b1b" />
            <path d="M 60,15 Q 45,2 35,12 Q 42,25 50,30 Z" fill="#991b1b" />
            <path d="M 22,60 Q 6,48 10,70 Q 22,68 25,65 Z" fill="#7f1d1d" />
            <path d="M 98,60 Q 114,48 110,70 Q 98,68 95,65 Z" fill="#7f1d1d" />
          </g>

          <!-- Shadow Core / Shroud Body -->
          <path d="M 35,35 Q 60,20 85,35 Q 96,65 88,95 Q 60,118 32,95 Q 24,65 35,35 Z" fill="url(#specterBodyGrad)" stroke="#b91c1c" stroke-width="2" />

          <!-- Fractured Mind Core (Cracked crystal in chest) -->
          <polygon points="60,52 70,66 60,80 50,66" fill="#ef4444" filter="url(#aberrationGlow)" />
          <line x1="56" y1="58" x2="64" y2="74" stroke="#fef2f2" stroke-width="1.5" />

          <!-- Glowing Crimson Aberration Eyes -->
          <g class="specter-eyes" filter="url(#aberrationGlow)">
            <!-- Left Eye -->
            <ellipse cx="48" cy="44" rx="4.5" ry="3" fill="#fca5a5" transform="rotate(-15 48 44)" />
            <circle cx="49" cy="44" r="1.5" fill="#ffffff" />
            <!-- Right Eye -->
            <ellipse cx="72" cy="44" rx="4.5" ry="3" fill="#fca5a5" transform="rotate(15 72 44)" />
            <circle cx="71" cy="44" r="1.5" fill="#ffffff" />
          </g>

          <!-- Mocking Smile (Opens widely during laugh animation) -->
          <path
            v-if="battleState === 'laughing'"
            d="M 44,58 Q 60,74 76,58 Q 60,82 44,58 Z"
            fill="#dc2626"
            stroke="#fecaca"
            stroke-width="1"
          />
          <path
            v-else
            d="M 47,60 Q 60,68 73,60"
            fill="none"
            stroke="#ef4444"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>

        <!-- Hit Impact Slash Graphic -->
        <div v-if="battleState === 'hit'" class="hit-slash-spark"></div>
      </div>
    </div>

    <!-- Retro Enemy Nameplate (Pokemon style, NO player HP!) -->
    <div class="enemy-nameplate gothic-panel">
      <div class="nameplate-row">
        <span class="enemy-icon-badge">🦇</span>
        <span class="enemy-display-name">{{ enemyName }}</span>
      </div>

      <!-- Enemy HP Bar (Pokemon Style) -->
      <div class="enemy-hp-bar-container">
        <span class="enemy-hp-label">HP</span>
        <div class="enemy-hp-track">
          <div
            class="enemy-hp-fill"
            :class="{ 'hp-warn': displayHpPct <= 50 && displayHpPct > 20, 'hp-crit': displayHpPct <= 20 }"
            :style="{ width: `${displayHpPct}%` }"
          ></div>
        </div>
        <span class="enemy-hp-val">{{ Math.round(displayHpPct) }}%</span>
      </div>

      <div class="enemy-sub-row">
        <span class="enemy-title-tag">{{ enemyTitle }}</span>
        <span class="enemy-reaction-status" :class="battleState">
          {{ enemyReactionLabel }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { battleAssets } from '../../config/battleAssets'
import EnemySpeechBubble from './EnemySpeechBubble.vue'

const props = defineProps({
  enemyName: {
    type: String,
    default: 'Fracture Specter'
  },
  enemyTitle: {
    type: String,
    default: 'Cognitive Aberration'
  },
  battleState: {
    type: String,
    default: 'idle' // 'idle' | 'hit' | 'laughing'
  },
  speechText: {
    type: String,
    default: ''
  },
  enemyHpPct: {
    type: Number,
    default: 100
  }
})

const displayHpPct = computed(() => {
  return Math.max(0, Math.min(100, props.enemyHpPct ?? 100))
})

const spriteSrc = computed(() => {
  return battleAssets.minionPlaceholder.spriteUrl || ''
})

const battleStateClass = computed(() => `state-${props.battleState}`)

const animClass = computed(() => {
  if (props.battleState === 'hit') return 'anim-minion-hit'
  if (props.battleState === 'laughing') return 'anim-minion-laugh'
  return 'anim-minion-idle'
})

const enemyReactionLabel = computed(() => {
  if (props.battleState === 'hit') return '💥 IMPACT'
  if (props.battleState === 'laughing') return '😈 MOCKING'
  return 'HOSTILE'
})
</script>

<style scoped>
.minion-battle-sprite-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  width: 195px;
  height: 175px;
}

/* Enemy Pedestal */
.enemy-pedestal {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 165px;
  height: 44px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(185, 28, 28, 0.45) 0%, rgba(20, 5, 10, 0.9) 70%);
  border: 2px solid rgba(239, 68, 68, 0.55);
  box-shadow: 0 0 20px rgba(220, 38, 38, 0.4), inset 0 0 12px rgba(127, 29, 29, 0.6);
  z-index: 1;
}

.pedestal-ring-inner {
  position: absolute;
  inset: 5px;
  border-radius: 50%;
  border: 1px dashed rgba(248, 113, 113, 0.4);
}

.pedestal-shadow {
  position: absolute;
  bottom: -6px;
  left: 10%;
  width: 80%;
  height: 14px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  filter: blur(4px);
}

/* Minion Sprite Wrapper */
.minion-sprite-wrapper {
  position: relative;
  z-index: 5;
  margin-bottom: 6px;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.2s ease;
}

.minion-custom-sprite {
  width: 125px;
  height: 125px;
  object-fit: contain;
}

.minion-specter-placeholder {
  width: 150px;
  height: 160px;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.specter-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 6px 14px rgba(220, 38, 38, 0.45));
}

/* Idle Animation */
.anim-minion-idle {
  animation: minion-hover 2.6s ease-in-out infinite;
}

@keyframes minion-hover {
  0%, 100% {
    transform: translateY(0) scale(1);
  }
  50% {
    transform: translateY(-8px) scale(1.02);
  }
}

/* Hit Animation on Correct Answer (Flash & Shake) */
.anim-minion-hit {
  animation: minion-hit-shake 0.55s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
  filter: brightness(2) drop-shadow(0 0 16px #ef4444) !important;
}

@keyframes minion-hit-shake {
  10%, 90% { transform: translate3d(-4px, 0, 0); }
  20%, 80% { transform: translate3d(6px, -2px, 0); }
  30%, 50%, 70% { transform: translate3d(-8px, 3px, 0); }
  40%, 60% { transform: translate3d(8px, -1px, 0); }
}

/* Laugh / Mocking Animation on Wrong Answer */
.anim-minion-laugh {
  animation: minion-mock-bounce 0.65s cubic-bezier(0.175, 0.885, 0.32, 1.275) infinite;
}

@keyframes minion-mock-bounce {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  25% {
    transform: translateY(-12px) rotate(-6deg) scale(1.08);
  }
  75% {
    transform: translateY(-8px) rotate(6deg) scale(1.08);
  }
}

/* Hit Slash Spark Overlay */
.hit-slash-spark {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(254, 202, 202, 0.9) 0%, rgba(239, 68, 68, 0.5) 45%, transparent 70%);
  animation: slash-spark 0.45s ease-out;
  pointer-events: none;
}

@keyframes slash-spark {
  0% { transform: scale(0.4) rotate(45deg); opacity: 1; }
  100% { transform: scale(1.6) rotate(45deg); opacity: 0; }
}

/* Enemy Nameplate */
.enemy-nameplate {
  position: absolute;
  bottom: -24px;
  left: 50%;
  transform: translateX(-50%);
  width: max-content;
  min-width: 220px;
  max-width: 320px;
  background: rgba(18, 8, 14, 0.96);
  border: 1.5px solid rgba(239, 68, 68, 0.6);
  border-radius: 6px;
  padding: 0.38rem 0.75rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8), 0 0 10px rgba(239, 68, 68, 0.3);
  z-index: 10;
  box-sizing: border-box;
}

.nameplate-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.enemy-icon-badge {
  font-size: 0.75rem;
}

.enemy-display-name {
  font-family: 'Cinzel', serif;
  font-size: clamp(0.7rem, 0.95vw, 0.82rem);
  font-weight: 700;
  color: #f8fafc;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

.enemy-sub-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.15rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.44rem;
}

.enemy-title-tag {
  color: #fca5a5;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 170px;
}

.enemy-reaction-status {
  color: #94a3b8;
}
.enemy-reaction-status.state-hit { color: #f87171; }
.enemy-reaction-status.state-laughing { color: #fbbf24; }

/* Enemy HP Bar */
.enemy-hp-bar-container {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0.2rem 0 0.15rem;
}

.enemy-hp-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.42rem;
  font-weight: 700;
  color: #ef4444;
  letter-spacing: 0.5px;
}

.enemy-hp-track {
  flex: 1;
  height: 5px;
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.enemy-hp-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
  transition: width 0.4s ease, background 0.3s ease;
  border-radius: 2px;
}

.enemy-hp-fill.hp-warn {
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.enemy-hp-fill.hp-crit {
  background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%);
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.85);
}

.enemy-hp-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.4rem;
  color: #e2e8f0;
  min-width: 28px;
  text-align: right;
}
</style>
