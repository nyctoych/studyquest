<template>
  <div class="player-battle-sprite-container" :class="battleStateClass">
    <!-- Battle Ground Pedestal / Ring -->
    <div class="battle-pedestal player-pedestal">
      <div class="pedestal-ring-outer"></div>
      <div class="pedestal-ring-inner"></div>
      <div class="pedestal-shadow"></div>
    </div>

    <!-- Player Character Sprite Placeholder -->
    <div class="player-sprite-wrapper" :class="animClass">
      <!-- If a real custom sprite asset is configured later in battleAssets, use it -->
      <img
        v-if="spriteSrc"
        :src="spriteSrc"
        :alt="playerName"
        class="player-custom-sprite pixel-art"
      />

      <!-- Default Dark Fantasy Scholar Placeholder (Gothic Pixel Battler SVG) -->
      <div v-else class="player-scholar-placeholder">
        <!-- Floating Arcane Grimoire / Tome of Knowledge -->
        <div class="floating-grimoire">
          <span class="grimoire-gem">🔮</span>
        </div>

        <!-- Pixel-Art Style Scholar Battler (Viewed from 3/4 Back/Side facing enemy) -->
        <svg class="scholar-svg" viewBox="0 0 100 120" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <!-- Cloak Gradient -->
            <linearGradient id="cowlGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#3b1d6e" />
              <stop offset="50%" stop-color="#241247" />
              <stop offset="100%" stop-color="#120824" />
            </linearGradient>
            <!-- Golden Trim -->
            <linearGradient id="goldTrim" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#fbbf24" />
              <stop offset="100%" stop-color="#d97706" />
            </linearGradient>
            <!-- Arcane Rune Glow -->
            <filter id="runeGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
          </defs>

          <!-- Shadow beneath character -->
          <ellipse cx="50" cy="112" rx="30" ry="6" fill="rgba(0, 0, 0, 0.45)" />

          <!-- Back flowing cloak -->
          <path d="M 28,45 Q 15,85 24,110 Q 50,115 76,110 Q 85,85 72,45 Z" fill="url(#cowlGrad)" stroke="#1a0b36" stroke-width="2" />
          
          <!-- Ornate Gold Cloak Trim -->
          <path d="M 25,108 Q 50,114 75,108" fill="none" stroke="url(#goldTrim)" stroke-width="3" stroke-linecap="round" />
          <path d="M 32,55 Q 50,75 68,55" fill="none" stroke="url(#goldTrim)" stroke-width="2" />

          <!-- Shoulders & Upper Robes -->
          <path d="M 26,45 L 35,32 L 65,32 L 74,45 L 68,65 L 32,65 Z" fill="#2d1554" stroke="#4c1d95" stroke-width="2" />

          <!-- Scholar's Hood / Cowl (Seen from behind/3/4) -->
          <path d="M 34,35 Q 50,14 66,35 Q 70,50 64,52 Q 50,56 36,52 Q 30,50 34,35 Z" fill="url(#cowlGrad)" stroke="#581c87" stroke-width="2" />
          <!-- Hood peak tip -->
          <path d="M 50,14 Q 52,8 54,6 Q 52,10 50,14 Z" fill="#3b1d6e" />

          <!-- Arcane Mind Sigil on Back of Cloak -->
          <g filter="url(#runeGlow)" opacity="0.85">
            <circle cx="50" cy="72" r="8" fill="none" stroke="#c084fc" stroke-width="1.5" stroke-dasharray="2,2" />
            <path d="M 50,66 L 50,78 M 44,72 L 56,72" stroke="#a855f7" stroke-width="1.5" />
            <circle cx="50" cy="72" r="2" fill="#e9d5ff" />
          </g>

          <!-- Scholar's Left Hand wielding Glowing Quill / Wand pointing toward enemy -->
          <g class="quill-hand">
            <circle cx="76" cy="56" r="5" fill="#fbcfe8" />
            <!-- Glowing Runic Quill -->
            <path d="M 77,54 L 94,36" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" />
            <polygon points="94,36 91,42 97,39" fill="#f59e0b" />
            <!-- Feather fringe -->
            <path d="M 80,51 Q 86,40 92,36" stroke="#e0e7ff" stroke-width="2" fill="none" />
            <!-- Magic spark tip -->
            <circle cx="95" cy="35" r="3.5" fill="#38bdf8" filter="url(#runeGlow)" />
          </g>
        </svg>

        <!-- Spell Casting Arc / Energy Pulse -->
        <div v-if="battleState === 'attacking'" class="cast-energy-flare"></div>
      </div>
    </div>

    <!-- Retro Player Battle Tag (Pokemon-Style Nameplate without HP bar) -->
    <div class="player-nameplate gothic-panel">
      <div class="nameplate-row">
        <span class="player-icon-badge">🧙‍♂️</span>
        <span class="player-display-name">{{ playerName }}</span>
      </div>
      <div class="player-sub-row">
        <span class="player-role-tag">SCHOLAR OF MIND</span>
        <span class="player-status-tag" :class="battleState">
          {{ statusLabel }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { battleAssets } from '../../config/battleAssets'

const props = defineProps({
  playerName: {
    type: String,
    default: 'Scholar'
  },
  battleState: {
    type: String,
    default: 'idle' // 'idle' | 'attacking' | 'recoil'
  }
})

const spriteSrc = computed(() => {
  return battleAssets.playerPlaceholder.spriteUrl || ''
})

const battleStateClass = computed(() => `state-${props.battleState}`)

const animClass = computed(() => {
  if (props.battleState === 'attacking') return 'anim-player-attack'
  if (props.battleState === 'recoil') return 'anim-player-recoil'
  return 'anim-player-idle'
})

const statusLabel = computed(() => {
  if (props.battleState === 'attacking') return '⚡ ATTACKING'
  if (props.battleState === 'recoil') return '💭 ANALYZING'
  return 'READY'
})
</script>

<style scoped>
.player-battle-sprite-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  width: 220px;
  height: 220px;
}

/* Battle Pedestal (Ground combat ring) */
.player-pedestal {
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 190px;
  height: 52px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(88, 28, 135, 0.45) 0%, rgba(15, 10, 30, 0.9) 70%);
  border: 2px solid rgba(168, 85, 247, 0.55);
  box-shadow: 0 0 20px rgba(139, 92, 246, 0.4), inset 0 0 12px rgba(88, 28, 135, 0.6);
  z-index: 1;
}

.pedestal-ring-inner {
  position: absolute;
  inset: 5px;
  border-radius: 50%;
  border: 1px dashed rgba(192, 132, 252, 0.4);
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

/* Player Sprite Wrapper */
.player-sprite-wrapper {
  position: relative;
  z-index: 5;
  margin-bottom: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.25s ease;
}

.player-custom-sprite {
  width: 140px;
  height: 140px;
  object-fit: contain;
}

/* Scholar SVG Placeholder */
.player-scholar-placeholder {
  width: 140px;
  height: 160px;
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.scholar-svg {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.5));
}

/* Floating Arcane Grimoire */
.floating-grimoire {
  position: absolute;
  top: 15px;
  left: 5px;
  font-size: 1.1rem;
  animation: grimoire-hover 2.4s ease-in-out infinite;
  filter: drop-shadow(0 0 8px rgba(168, 85, 247, 0.7));
  z-index: 6;
}

@keyframes grimoire-hover {
  0%, 100% {
    transform: translateY(0) rotate(-6deg);
  }
  50% {
    transform: translateY(-8px) rotate(4deg);
  }
}

/* Idle Animation */
.anim-player-idle {
  animation: player-breathe 2.8s ease-in-out infinite;
}

@keyframes player-breathe {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

/* Attack Animation (Leaps forward towards enemy with runic surge) */
.anim-player-attack {
  animation: player-thrust 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes player-thrust {
  0% {
    transform: translateX(0) translateY(0) scale(1);
  }
  35% {
    transform: translateX(36px) translateY(-14px) scale(1.08);
  }
  70% {
    transform: translateX(20px) translateY(-6px) scale(1.03);
  }
  100% {
    transform: translateX(0) translateY(0) scale(1);
  }
}

/* Recoil / Puzzled Animation (Slight pause on wrong answer) */
.anim-player-recoil {
  animation: player-shake 0.45s ease-in-out;
}

@keyframes player-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-6px); }
  50% { transform: translateX(4px); }
  75% { transform: translateX(-3px); }
}

/* Energy Flare during attack */
.cast-energy-flare {
  position: absolute;
  top: 25px;
  right: -10px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: radial-gradient(circle, #38bdf8 0%, rgba(168, 85, 247, 0.6) 50%, transparent 70%);
  animation: cast-flare 0.5s ease-out;
  pointer-events: none;
}

@keyframes cast-flare {
  0% { transform: scale(0.2); opacity: 0; }
  50% { transform: scale(1.3); opacity: 1; filter: blur(2px); }
  100% { transform: scale(1.8); opacity: 0; }
}

/* Player Nameplate (Classic Pokemon style name badge, NO HP bar!) */
.player-nameplate {
  position: absolute;
  bottom: -38px;
  left: 50%;
  transform: translateX(-50%);
  width: 175px;
  background: rgba(14, 10, 26, 0.96);
  border: 1.5px solid rgba(168, 85, 247, 0.65);
  border-radius: 6px;
  padding: 0.35rem 0.6rem;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 10px rgba(168, 85, 247, 0.25);
  z-index: 10;
}

.nameplate-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.player-icon-badge {
  font-size: 0.8rem;
}

.player-display-name {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.75px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.player-sub-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.2rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.46rem;
}

.player-role-tag {
  color: #c084fc;
}

.player-status-tag {
  color: #34d399;
}
.player-status-tag.state-attacking { color: #fbbf24; }
.player-status-tag.state-recoil { color: #f87171; }
</style>
