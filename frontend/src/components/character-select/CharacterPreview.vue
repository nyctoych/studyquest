<template>
  <div class="character-preview-stage" :class="`theme-${character.class_type.toLowerCase()}`">
    <!-- Atmospheric Ambient Backlight Halo -->
    <div class="stage-ambient-halo"></div>

    <!-- Runic Arc / Halo Circle behind character -->
    <div class="runic-halo-ring">
      <div class="halo-inner-dashed"></div>
    </div>

    <!-- Main Character Presentation Wrapper -->
    <transition name="char-reveal" mode="out-in">
      <div
        :key="character.id"
        class="character-actor-container"
        :style="characterScaleStyle"
      >
        <!-- Atmospheric Ground Pedestal & Shadow -->
        <div class="actor-ground-shadow"></div>
        <div class="actor-ground-pedestal">
          <div class="pedestal-rune-ring"></div>
        </div>

        <!-- Large Pixel Art Sprite -->
        <img
          :src="character.sprite_path"
          :alt="character.name"
          class="character-large-sprite pixel-art"
          @error="onSpriteError"
        />

        <!-- Floating Particles / Aura effect -->
        <div class="actor-aura-effect" :class="`aura-${character.class_type.toLowerCase()}`"></div>
      </div>
    </transition>

    <!-- Character Nameplate & Class Banner -->
    <div class="character-identity-banner gothic-panel">
      <div class="banner-crest">
        <span class="crest-icon">{{ classCrestIcon }}</span>
      </div>
      <div class="banner-text">
        <span class="banner-class font-pixel">{{ character.class_type }}</span>
        <h1 class="banner-name font-cinzel">{{ character.name }}</h1>
        <p class="banner-epithet font-cinzel">{{ character.title }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  character: {
    type: Object,
    required: true
  }
})

// Centralized Per-Character Scale & Offset configurations (Section 42 & 43)
const CLASS_SCALE_CONFIG = {
  MAGE: { scale: 1.0, offsetY: 0 },
  MELEE: { scale: 1.04, offsetY: 2 },
  RANGE: { scale: 0.98, offsetY: 0 }
}

const characterScaleStyle = computed(() => {
  const config = CLASS_SCALE_CONFIG[props.character.class_type] || { scale: 1.0, offsetY: 0 }
  return {
    transform: `scale(${config.scale}) translateY(${config.offsetY}px)`
  }
})

const classCrestIcon = computed(() => {
  switch (props.character.class_type) {
    case 'MAGE': return '🔮'
    case 'MELEE': return '⚔️'
    case 'RANGE': return '🏹'
    default: return '✦'
  }
})

function onSpriteError(e) {
  console.warn(`[CharacterPreview] Image failed to load for ${props.character.name}: ${props.character.sprite_path}`)
}
</script>

<style scoped>
.character-preview-stage {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  min-height: 480px;
  width: 100%;
  padding-bottom: 2rem;
  overflow: visible;
}

/* Ambient backlight that shifts subtly with class */
.stage-ambient-halo {
  position: absolute;
  top: 15%;
  left: 50%;
  transform: translateX(-50%);
  width: 380px;
  height: 380px;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.45;
  pointer-events: none;
  transition: all 0.6s ease;
  z-index: 1;
}

.theme-mage .stage-ambient-halo {
  background: radial-gradient(circle, rgba(168, 85, 247, 0.7) 0%, rgba(88, 28, 135, 0.4) 50%, transparent 70%);
}

.theme-melee .stage-ambient-halo {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.7) 0%, rgba(153, 27, 27, 0.4) 50%, transparent 70%);
}

.theme-range .stage-ambient-halo {
  background: radial-gradient(circle, rgba(45, 212, 191, 0.7) 0%, rgba(19, 78, 74, 0.4) 50%, transparent 70%);
}

/* Runic Halo */
.runic-halo-ring {
  position: absolute;
  top: 10%;
  left: 50%;
  transform: translateX(-50%);
  width: 320px;
  height: 320px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.08);
  pointer-events: none;
  z-index: 2;
  animation: halo-slow-rotate 60s linear infinite;
}

@keyframes halo-slow-rotate {
  from { transform: translateX(-50%) rotate(0deg); }
  to { transform: translateX(-50%) rotate(360deg); }
}

.halo-inner-dashed {
  position: absolute;
  inset: 12px;
  border-radius: 50%;
  border: 1px dashed rgba(168, 85, 247, 0.25);
}

.theme-melee .halo-inner-dashed { border-color: rgba(239, 68, 68, 0.25); }
.theme-range .halo-inner-dashed { border-color: rgba(45, 212, 191, 0.25); }

/* Actor Container */
.character-actor-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 340px;
  width: 320px;
  z-index: 5;
  margin-bottom: 1.5rem;
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Ground shadow and pedestal (Section 43: Character Grounding) */
.actor-ground-shadow {
  position: absolute;
  bottom: 0px;
  width: 200px;
  height: 24px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.85) 0%, transparent 75%);
  border-radius: 50%;
  filter: blur(5px);
  z-index: 1;
}

.actor-ground-pedestal {
  position: absolute;
  bottom: 6px;
  width: 220px;
  height: 36px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(18, 14, 28, 0.95) 0%, rgba(8, 6, 12, 0.95) 75%);
  border: 1.5px solid rgba(168, 85, 247, 0.45);
  box-shadow: 0 0 16px rgba(168, 85, 247, 0.3), inset 0 0 10px rgba(0, 0, 0, 0.8);
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
}

.theme-melee .actor-ground-pedestal {
  border-color: rgba(239, 68, 68, 0.45);
  box-shadow: 0 0 16px rgba(239, 68, 68, 0.3);
}

.theme-range .actor-ground-pedestal {
  border-color: rgba(45, 212, 191, 0.45);
  box-shadow: 0 0 16px rgba(45, 212, 191, 0.3);
}

.pedestal-rune-ring {
  width: 85%;
  height: 60%;
  border-radius: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.2);
}

/* Large character sprite (Section 6: Large Sprite Showcase) */
.character-large-sprite {
  max-width: 280px;
  max-height: 280px;
  object-fit: contain;
  z-index: 4;
  filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.9));
  animation: char-idle-sway 4s ease-in-out infinite;
}

@keyframes char-idle-sway {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6px);
  }
}

/* Identity banner at the foot of preview */
.character-identity-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.65rem 1.4rem;
  background: rgba(14, 10, 22, 0.95);
  border: 1.5px solid rgba(168, 85, 247, 0.5);
  border-radius: 4px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 16px rgba(168, 85, 247, 0.2);
  z-index: 10;
}

.theme-melee .character-identity-banner {
  border-color: rgba(239, 68, 68, 0.5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 16px rgba(239, 68, 68, 0.2);
}

.theme-range .character-identity-banner {
  border-color: rgba(45, 212, 191, 0.5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 16px rgba(45, 212, 191, 0.2);
}

.banner-crest {
  font-size: 1.8rem;
  filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.3));
}

.banner-text {
  display: flex;
  flex-direction: column;
}

.banner-class {
  font-size: 0.55rem;
  letter-spacing: 0.16em;
  color: #c084fc;
}

.theme-melee .banner-class { color: #f87171; }
.theme-range .banner-class { color: #5eead4; }

.banner-name {
  font-size: 1.45rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.06em;
  line-height: 1.1;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.9);
}

.banner-epithet {
  font-size: 0.72rem;
  color: #a8a29e;
  letter-spacing: 0.06em;
}

/* Reveal transition */
.char-reveal-enter-active,
.char-reveal-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.char-reveal-enter-from {
  opacity: 0;
  transform: scale(0.92) translateY(12px);
  filter: blur(4px);
}

.char-reveal-leave-to {
  opacity: 0;
  transform: scale(1.05) translateY(-8px);
  filter: blur(4px);
}
</style>
