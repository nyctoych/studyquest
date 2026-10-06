<template>
  <div class="player-battle-sprite-container" :class="[battleStateClass, `theme-${activeClass.toLowerCase()}`]">
    <!-- Battle Ground Pedestal / Ring -->
    <div class="battle-pedestal player-pedestal">
      <div class="pedestal-ring-outer"></div>
      <div class="pedestal-ring-inner"></div>
      <div class="pedestal-shadow"></div>
    </div>

    <!-- Player Character Sprite Presentation -->
    <div class="player-sprite-wrapper" :class="animClass" :style="characterScaleStyle">
      <!-- Real Character Battle Sprite (Animated GIF or PNG) -->
      <img
        v-if="activeSpriteSrc && !spriteError"
        :key="spriteRenderKey"
        :src="activeSpriteSrc"
        :alt="playerName"
        class="player-custom-sprite pixel-art"
        @error="onImageError"
      />

      <!-- Emergency Fallback: Dark Fantasy Scholar (Gothic Pixel Battler SVG) -->
      <div v-else class="player-scholar-placeholder">
        <!-- Floating Arcane Grimoire / Tome of Knowledge -->
        <div class="floating-grimoire">
          <span class="grimoire-gem">🔮</span>
        </div>

        <!-- Pixel-Art Style Scholar Battler -->
        <svg class="scholar-svg" viewBox="0 0 100 120" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="cowlGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#3b1d6e" />
              <stop offset="50%" stop-color="#241247" />
              <stop offset="100%" stop-color="#120824" />
            </linearGradient>
            <linearGradient id="goldTrim" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#fbbf24" />
              <stop offset="100%" stop-color="#d97706" />
            </linearGradient>
            <filter id="runeGlow" x="-20%" y="-20%" width="140%" height="140%">
              <feGaussianBlur stdDeviation="3" result="blur" />
              <feComposite in="SourceGraphic" in2="blur" operator="over" />
            </filter>
          </defs>

          <ellipse cx="50" cy="112" rx="30" ry="6" fill="rgba(0, 0, 0, 0.45)" />
          <path d="M 28,45 Q 15,85 24,110 Q 50,115 76,110 Q 85,85 72,45 Z" fill="url(#cowlGrad)" stroke="#1a0b36" stroke-width="2" />
          <path d="M 25,108 Q 50,114 75,108" fill="none" stroke="url(#goldTrim)" stroke-width="3" stroke-linecap="round" />
          <path d="M 32,55 Q 50,75 68,55" fill="none" stroke="url(#goldTrim)" stroke-width="2" />
          <path d="M 26,45 L 35,32 L 65,32 L 74,45 L 68,65 L 32,65 Z" fill="#2d1554" stroke="#4c1d95" stroke-width="2" />
          <path d="M 34,35 Q 50,14 66,35 Q 70,50 64,52 Q 50,56 36,52 Q 30,50 34,35 Z" fill="url(#cowlGrad)" stroke="#581c87" stroke-width="2" />
          <path d="M 50,14 Q 52,8 54,6 Q 52,10 50,14 Z" fill="#3b1d6e" />

          <g filter="url(#runeGlow)" opacity="0.85">
            <circle cx="50" cy="72" r="8" fill="none" stroke="#c084fc" stroke-width="1.5" stroke-dasharray="2,2" />
            <path d="M 50,66 L 50,78 M 44,72 L 56,72" stroke="#a855f7" stroke-width="1.5" />
            <circle cx="50" cy="72" r="2" fill="#e9d5ff" />
          </g>

          <g class="quill-hand">
            <circle cx="76" cy="56" r="5" fill="#fbcfe8" />
            <path d="M 77,54 L 94,36" stroke="#fbbf24" stroke-width="3" stroke-linecap="round" />
            <polygon points="94,36 91,42 97,39" fill="#f59e0b" />
            <path d="M 80,51 Q 86,40 92,36" stroke="#e0e7ff" stroke-width="2" fill="none" />
            <circle cx="95" cy="35" r="3.5" fill="#38bdf8" filter="url(#runeGlow)" />
          </g>
        </svg>
      </div>

      <!-- Spell Casting Arc / Energy Pulse during attack -->
      <div v-if="battleState === 'attacking' || battleState === 'attack_finished'" class="cast-energy-flare" :class="`flare-${activeClass.toLowerCase()}`"></div>
    </div>

    <!-- Retro Player Battle Tag with Health Bar (Gothic RPG Style) -->
    <div class="player-nameplate gothic-panel" :class="{ 'is-taking-damage': isDamaged }">
      <div class="nameplate-row">
        <span class="player-icon-badge">{{ classIcon }}</span>
        <div class="name-class-wrap">
          <span class="player-display-name">{{ playerName }}</span>
          <span class="player-level-badge font-pixel">LV. {{ displayLevel }}</span>
        </div>
      </div>

      <!-- Player HP Bar Container -->
      <div class="player-hp-bar-container">
        <div class="hp-info-row">
          <span class="player-hp-label font-pixel">HP</span>
          <span class="player-hp-num font-pixel">{{ Math.round(Number(playerHp || 0)) }} / {{ playerMaxHp || 150 }}</span>
        </div>
        <div class="player-hp-track">
          <div
            class="player-hp-fill"
            :class="{ 'hp-warn': hpPercent <= 50 && hpPercent > 20, 'hp-crit': hpPercent <= 20 }"
            :style="{ width: `${hpPercent}%` }"
          ></div>
        </div>
      </div>

      <!-- Player Attack Statistics Directly Below HP Information -->
      <div class="player-combat-stats font-pixel">
        <div class="stat-stat-row stat-attack">
          <span class="stat-label">⚔ ATTACK POWER</span>
          <span class="stat-value">{{ displayAttackPower }}</span>
        </div>
        <div class="stat-stat-row stat-crit">
          <span class="stat-label">✦ CRITICAL ATTACK POWER</span>
          <span class="stat-value">{{ displayCritPower }}</span>
        </div>
      </div>

      <div class="player-sub-row">
        <span class="player-role-tag font-pixel">{{ roleTagLabel }}</span>
        <span class="player-status-tag font-pixel" :class="battleState">
          {{ statusLabel }}
        </span>
      </div>

      <!-- Floating Damage Floater Indicator -->
      <div v-if="floatingText" class="player-damage-floater font-pixel" :class="floatingType">
        {{ floatingText }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { resolveAsset } from '../../services/bossMapping'
import { getCharacterAnimationConfig } from '../../config/characterAnimations'

const auth = useAuthStore()

const props = defineProps({
  character: {
    type: Object,
    default: null
  },
  playerName: {
    type: String,
    default: 'Scholar'
  },
  playerLevel: {
    type: [Number, String],
    default: null
  },
  attackPower: {
    type: [Number, String],
    default: null
  },
  criticalAttackPower: {
    type: [Number, String],
    default: null
  },
  battleState: {
    type: String,
    default: 'idle' // 'opening' | 'idle' | 'ready' | 'attacking' | 'recoil' | 'hit'
  },
  playerHp: {
    type: Number,
    default: 150
  },
  playerMaxHp: {
    type: Number,
    default: 150
  },
  damageTaken: {
    type: Number,
    default: 0
  },
  damageType: {
    type: String,
    default: 'none'
  }
})

const spriteError = ref(false)
const isDamaged = ref(false)
const floatingText = ref('')
const floatingType = ref('')
const spriteRenderKey = ref(0)
const spriteTimestamp = ref(Date.now())

const hpPercent = computed(() => {
  if (props.playerMaxHp <= 0) return 0
  return Math.max(0, Math.min(100, (props.playerHp / props.playerMaxHp) * 100))
})

watch(() => props.playerHp, (newHp, oldHp) => {
  if (oldHp !== undefined && newHp < oldHp) {
    const diff = oldHp - newHp
    floatingText.value = `-${diff} HP`
    floatingType.value = (props.damageType === 'critical_reflected' || props.damageType === 'critical' || props.damageType === 'critical_backfire') ? 'backfire' : 'damage'
    isDamaged.value = true
    setTimeout(() => {
      isDamaged.value = false
    }, 600)
    setTimeout(() => {
      floatingText.value = ''
    }, 1300)
  }
})

watch(() => props.damageTaken, (val) => {
  if (val > 0) {
    floatingText.value = `-${val} HP`
    floatingType.value = (props.damageType === 'critical_reflected' || props.damageType === 'critical' || props.damageType === 'critical_backfire') ? 'backfire' : 'damage'
    isDamaged.value = true
    setTimeout(() => {
      isDamaged.value = false
    }, 600)
    setTimeout(() => {
      floatingText.value = ''
    }, 1300)
  }
})

// Restart GIF animation when entering key action states (opening or attacking)
watch(() => props.battleState, (newState, oldState) => {
  if (newState !== oldState) {
    const s = (newState || '').toLowerCase()
    // Only force DOM remount on opening or attacking to guarantee frame-0 start
    if (s === 'opening' || s === 'attacking') {
      spriteRenderKey.value++
      spriteTimestamp.value = Date.now()
    }
    spriteError.value = false
  }
}, { immediate: true })

// Active character either passed as prop or from logged-in user profile
const activeChar = computed(() => {
  if (props.character) return props.character
  if (auth.selectedCharacter) return auth.selectedCharacter
  if (auth.user?.selected_character) return auth.user.selected_character
  return null
})

const activeClass = computed(() => {
  if (activeChar.value?.name?.toLowerCase().includes('archer')) return 'RANGE'
  if (activeChar.value?.name?.toLowerCase().includes('dual')) return 'MELEE'
  if (activeChar.value?.name?.toLowerCase().includes('necro')) return 'MAGE'
  const raw = activeChar.value?.class_type || activeChar.value?.code || activeChar.value?.key_name || 'MAGE'
  return String(raw).toUpperCase()
})

const displayLevel = computed(() => {
  if (props.playerLevel !== null && props.playerLevel !== undefined && props.playerLevel !== '') {
    return Number(props.playerLevel)
  }
  return auth.level || auth.user?.level || 1
})

const displayAttackPower = computed(() => {
  if (props.attackPower !== null && props.attackPower !== undefined && props.attackPower !== '') {
    return Number(props.attackPower)
  }
  if (activeChar.value?.damage) return Number(activeChar.value.damage)
  if (activeClass.value === 'RANGE') return 15
  return 10
})

const displayCritPower = computed(() => {
  if (props.criticalAttackPower !== null && props.criticalAttackPower !== undefined && props.criticalAttackPower !== '') {
    return Number(props.criticalAttackPower)
  }
  if (activeChar.value?.crit_damage) return Number(activeChar.value.crit_damage)
  if (activeClass.value === 'RANGE') return 50
  if (activeClass.value === 'MELEE') return 20
  return 35
})

// Centralized Per-Character Configuration
const animConfig = computed(() => {
  return getCharacterAnimationConfig(activeChar.value || activeClass.value)
})

const characterScaleStyle = computed(() => {
  const scale = animConfig.value?.scale || 1.0
  const offsetY = animConfig.value?.offsetY || 0
  return {
    transform: `scale(${scale}) translateY(${offsetY}px)`
  }
})

// Dynamically select sprite based on battleState:
// 'opening' -> config.opening || c.opening_gif_path
// 'attacking' -> config.attack || c.attack_asset_path
// 'idle' / 'ready' -> config.idle || c.idle_asset_path
const resolvedSprite = computed(() => {
  const cfg = animConfig.value
  const c = activeChar.value

  const state = (props.battleState || 'idle').toLowerCase()

  if (state === 'opening') {
    if (activeClass.value === 'MAGE') {
      return resolveAsset('/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer OPENING.gif')
    }
    if (activeClass.value === 'RANGE') {
      return resolveAsset('/assets/CHARACTERS/ARCHER (RANGE)/Archerr OPENING.gif')
    }
    if (activeClass.value === 'MELEE') {
      return resolveAsset('/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png')
    }
    const raw = cfg?.opening || c?.opening_gif_path || cfg?.sprite || c?.sprite_path || ''
    return resolveAsset(raw)
  }
  if (state === 'attacking') {
    if (activeClass.value === 'MAGE') {
      return resolveAsset('/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Attack.gif')
    }
    if (activeClass.value === 'RANGE') {
      return resolveAsset('/assets/CHARACTERS/ARCHER (RANGE)/Archerr ATTACKING.gif')
    }
    if (activeClass.value === 'MELEE') {
      return resolveAsset('/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png')
    }
    const raw = cfg?.attack || c?.attack_asset_path || cfg?.sprite || c?.sprite_path || ''
    return resolveAsset(raw)
  }
  if (state === 'attack_finished') {
    if (activeClass.value === 'MAGE') {
      return resolveAsset('/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif')
    }
    if (activeClass.value === 'RANGE') {
      return resolveAsset('/assets/CHARACTERS/ARCHER (RANGE)/Archer Attack1.png')
    }
    if (activeClass.value === 'MELEE') {
      return resolveAsset('/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png')
    }
    const raw = cfg?.attackStatic || cfg?.sprite || c?.sprite_path || ''
    return resolveAsset(raw)
  }
  if (state === 'recoil' || state === 'hit') {
    if (activeClass.value === 'MAGE') {
      return resolveAsset('/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif')
    }
    if (activeClass.value === 'RANGE') {
      return resolveAsset('/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif')
    }
    if (activeClass.value === 'MELEE') {
      return resolveAsset('/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png')
    }
    const raw = cfg?.idle || c?.idle_asset_path || cfg?.sprite || c?.sprite_path || ''
    return resolveAsset(raw)
  }
  // Default idle / ready
  if (activeClass.value === 'MAGE') {
    return resolveAsset('/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif')
  }
  if (activeClass.value === 'RANGE') {
    return resolveAsset('/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif')
  }
  if (activeClass.value === 'MELEE') {
    return resolveAsset('/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png')
  }
  const raw = cfg?.idle || c?.idle_asset_path || cfg?.sprite || c?.sprite_path || ''
  return resolveAsset(raw)
})

// Cache-bust query to force GIF reset from frame 0 during attack/opening
const activeSpriteSrc = computed(() => {
  const base = resolvedSprite.value
  if (!base) return ''
  const state = (props.battleState || '').toLowerCase()
  if (base.toLowerCase().includes('.gif') && (state === 'opening' || state === 'attacking')) {
    const sep = base.includes('?') ? '&' : '?'
    return `${base}${sep}anim_t=${spriteTimestamp.value}`
  }
  return base
})

watch(() => props.character?.id, () => {
  spriteError.value = false
})

function onImageError() {
  console.warn(`[PlayerBattleSprite] Could not load ${resolvedSprite.value}, falling back.`)
  spriteError.value = true
}

const classIcon = computed(() => {
  switch (activeClass.value) {
    case 'MAGE': return '🔮'
    case 'MELEE': return '⚔️'
    case 'RANGE': return '🏹'
    default: return '🧙‍♂️'
  }
})

const roleTagLabel = computed(() => {
  if (activeChar.value?.name && activeChar.value?.class_type) {
    return `${activeChar.value.name.toUpperCase()} — ${activeChar.value.class_type}`
  }
  return activeClass.value || 'SCHOLAR'
})

const battleStateClass = computed(() => `state-${props.battleState}`)

const animClass = computed(() => {
  const s = (props.battleState || '').toLowerCase()
  if (s === 'opening') return 'anim-player-opening'
  if (s === 'attacking') return 'anim-player-attack'
  if (s === 'attack_finished') return 'anim-player-attack-finished'
  if (s === 'recoil' || s === 'hit') return 'anim-player-recoil'
  return 'anim-player-idle'
})

const statusLabel = computed(() => {
  const s = (props.battleState || '').toLowerCase()
  if (s === 'opening') return '⚔️ PREPARING'
  if (s === 'attacking') return '⚡ STRIKING'
  if (s === 'attack_finished') return '⚡ RECOVERING'
  if (s === 'recoil') return '💭 ANALYZING'
  if (s === 'hit') return '💥 HIT'
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

.theme-melee .player-pedestal {
  background: radial-gradient(ellipse at center, rgba(153, 27, 27, 0.45) 0%, rgba(20, 10, 14, 0.9) 70%);
  border-color: rgba(239, 68, 68, 0.55);
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.4), inset 0 0 12px rgba(153, 27, 27, 0.6);
}

.theme-range .player-pedestal {
  background: radial-gradient(ellipse at center, rgba(19, 78, 74, 0.45) 0%, rgba(8, 16, 16, 0.9) 70%);
  border-color: rgba(45, 212, 191, 0.55);
  box-shadow: 0 0 20px rgba(45, 212, 191, 0.4), inset 0 0 12px rgba(19, 78, 74, 0.6);
}

.pedestal-ring-inner {
  position: absolute;
  inset: 5px;
  border-radius: 50%;
  border: 1px dashed rgba(192, 132, 252, 0.4);
}

.theme-melee .pedestal-ring-inner { border-color: rgba(248, 113, 113, 0.4); }
.theme-range .pedestal-ring-inner { border-color: rgba(94, 234, 212, 0.4); }

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
  margin-bottom: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: transform 0.25s ease;
}

.player-custom-sprite {
  width: 155px;
  height: 155px;
  object-fit: contain;
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.7));
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
  0%, 100% { transform: translateY(0) rotate(-6deg); }
  50% { transform: translateY(-8px) rotate(4deg); }
}

/* Opening Intro Entrance Animation */
.anim-player-opening {
  animation: player-entrance 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes player-entrance {
  0% {
    opacity: 0;
    transform: translateY(18px) scale(0.92);
    filter: brightness(1.8) drop-shadow(0 0 16px rgba(168, 85, 247, 0.8));
  }
  60% {
    opacity: 1;
    transform: translateY(-4px) scale(1.04);
    filter: brightness(1.2) drop-shadow(0 0 8px rgba(168, 85, 247, 0.4));
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: brightness(1) drop-shadow(0 0 0 transparent);
  }
}

/* Idle Animation */
.anim-player-idle {
  animation: player-breathe 2.8s ease-in-out infinite;
}

@keyframes player-breathe {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

/* Attack Animation */
.anim-player-attack {
  animation: player-thrust 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes player-thrust {
  0% { transform: translateX(0) translateY(0) scale(1); }
  35% { transform: translateX(36px) translateY(-14px) scale(1.08); }
  70% { transform: translateX(20px) translateY(-6px) scale(1.03); }
  100% { transform: translateX(0) translateY(0) scale(1); }
}

/* Post-Attack Stance (e.g. Archer Attack1.png / Necromancer Attack.png) */
.anim-player-attack-finished {
  animation: player-settle 0.35s ease-out forwards;
}

@keyframes player-settle {
  0% { transform: translateX(6px) translateY(-2px); }
  100% { transform: translateX(0) translateY(0); }
}

/* Recoil Animation (On wrong answer - player is unaffected physically, slight cognitive pause) */
.anim-player-recoil {
  animation: player-shake 0.45s ease-in-out;
}

@keyframes player-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  50% { transform: translateX(3px); }
  75% { transform: translateX(-2px); }
}

/* Flare */
.cast-energy-flare {
  position: absolute;
  top: 25px;
  right: -10px;
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: radial-gradient(circle, #e9d5ff 0%, #c084fc 40%, rgba(168, 85, 247, 0.75) 65%, transparent 80%);
  filter: drop-shadow(0 0 12px #c084fc) drop-shadow(0 0 20px #a855f7);
  animation: cast-flare 0.9s ease-out forwards;
  pointer-events: none;
  z-index: 20;
}

.flare-melee {
  background: radial-gradient(circle, #fee2e2 0%, #f87171 40%, rgba(239, 68, 68, 0.75) 65%, transparent 80%);
  filter: drop-shadow(0 0 12px #f87171) drop-shadow(0 0 20px #ef4444);
}

.flare-range {
  background: radial-gradient(circle, #ccfbf1 0%, #2dd4bf 40%, rgba(45, 212, 191, 0.75) 65%, transparent 80%);
  filter: drop-shadow(0 0 12px #2dd4bf) drop-shadow(0 0 20px #14b8a6);
}

@keyframes cast-flare {
  0% { transform: scale(0.2); opacity: 0; }
  30% { transform: scale(1.3); opacity: 1; filter: blur(1px) drop-shadow(0 0 16px #c084fc); }
  60% { transform: scale(1.6); opacity: 0.9; filter: blur(2px) drop-shadow(0 0 24px #a855f7); }
  100% { transform: scale(2.2); opacity: 0; }
}

/* Player Nameplate */
.player-nameplate {
  position: absolute;
  bottom: -28px;
  left: 50%;
  transform: translateX(-50%);
  width: 205px;
  background: rgba(14, 10, 26, 0.96);
  border: 1.5px solid rgba(168, 85, 247, 0.65);
  border-radius: 6px;
  padding: 0.35rem 0.55rem;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 10px rgba(168, 85, 247, 0.25);
  z-index: 10;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.player-nameplate.is-taking-damage {
  border-color: #ef4444 !important;
  box-shadow: 0 0 16px rgba(239, 68, 68, 0.8) !important;
  animation: nameplate-hurt 0.45s ease-in-out;
}

@keyframes nameplate-hurt {
  0%, 100% { transform: translateX(-50%); }
  25% { transform: translateX(calc(-50% - 4px)); }
  50% { transform: translateX(calc(-50% + 4px)); }
  75% { transform: translateX(calc(-50% - 2px)); }
}

.theme-melee .player-nameplate {
  border-color: rgba(239, 68, 68, 0.65);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 10px rgba(239, 68, 68, 0.25);
}

.theme-range .player-nameplate {
  border-color: rgba(45, 212, 191, 0.65);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8), 0 0 10px rgba(45, 212, 191, 0.25);
}

.nameplate-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.player-icon-badge {
  font-size: 0.85rem;
}

.name-class-wrap {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  flex: 1;
  overflow: hidden;
  gap: 0.35rem;
}

.player-display-name {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.75px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.player-class-sub {
  font-size: 0.42rem;
  color: #fbbf24;
  letter-spacing: 0.5px;
  flex-shrink: 0;
}

/* Player HP Bar */
.player-hp-bar-container {
  margin: 0.2rem 0 0.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.hp-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.42rem;
}

.player-hp-label {
  font-weight: 700;
  color: #38bdf8;
  letter-spacing: 0.5px;
}

.player-hp-num {
  color: #e2e8f0;
  letter-spacing: 0.3px;
}

.player-hp-track {
  width: 100%;
  height: 5px;
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
  position: relative;
}

.player-hp-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
  transition: width 0.4s ease, background 0.3s ease;
  border-radius: 2px;
}

.player-hp-fill.hp-warn {
  background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
}

.player-hp-fill.hp-crit {
  background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%);
  box-shadow: 0 0 6px rgba(239, 68, 68, 0.85);
}

/* Player Level Badge */
.player-level-badge {
  font-size: 0.44rem;
  color: #fbbf24;
  letter-spacing: 0.5px;
  flex-shrink: 0;
  padding: 0.04rem 0.22rem;
  background: rgba(251, 191, 36, 0.12);
  border: 1px solid rgba(251, 191, 36, 0.35);
  border-radius: 3px;
  text-shadow: 0 0 4px rgba(251, 191, 36, 0.5);
}

/* Player Attack Statistics Block directly below HP */
.player-combat-stats {
  display: flex;
  flex-direction: column;
  gap: 0.16rem;
  margin: 0.22rem 0 0.18rem;
  padding: 0.2rem 0.32rem;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.stat-stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.44rem;
  line-height: 1.2;
}

.stat-stat-row.stat-attack .stat-label {
  color: #f59e0b;
  letter-spacing: 0.4px;
}

.stat-stat-row.stat-attack .stat-value {
  color: #fef08a;
  font-weight: 700;
  text-shadow: 0 0 4px rgba(245, 158, 11, 0.5);
}

.stat-stat-row.stat-crit .stat-label {
  color: #c084fc;
  letter-spacing: 0.4px;
}

.stat-stat-row.stat-crit .stat-value {
  color: #f5d0fe;
  font-weight: 700;
  text-shadow: 0 0 4px rgba(192, 132, 252, 0.5);
}

.player-sub-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.1rem;
  font-size: 0.4rem;
}

.player-role-tag {
  color: #c084fc;
  letter-spacing: 0.04em;
  max-width: 110px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.theme-melee .player-role-tag { color: #f87171; }
.theme-range .player-role-tag { color: #5eead4; }

.player-status-tag {
  color: #34d399;
}
.player-status-tag.state-attacking { color: #fbbf24; }
.player-status-tag.state-recoil { color: #f87171; }

/* Floating Damage Indicator */
.player-damage-floater {
  position: absolute;
  top: -24px;
  right: 0;
  font-size: 0.65rem;
  color: #ef4444;
  text-shadow: 0 0 8px rgba(239, 68, 68, 0.9), 0 0 2px #000;
  pointer-events: none;
  animation: floater-rise 1.1s ease-out forwards;
}

.player-damage-floater.backfire {
  color: #f87171;
  text-shadow: 0 0 10px rgba(220, 38, 38, 1), 0 0 4px #7f1d1d;
}

@keyframes floater-rise {
  0% {
    opacity: 0;
    transform: translateY(6px) scale(0.85);
  }
  20% {
    opacity: 1;
    transform: translateY(0) scale(1.15);
  }
  80% {
    opacity: 1;
    transform: translateY(-12px) scale(1);
  }
  100% {
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
  }
}
</style>
