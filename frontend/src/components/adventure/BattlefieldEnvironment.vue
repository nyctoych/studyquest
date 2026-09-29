<template>
  <div
    class="battlefield-environment"
    :class="[
      `env-${environment.groundType}`,
      `lighting-${environment.lighting}`,
      { 'is-boss-env': isBoss }
    ]"
    :style="environmentStyles"
  >
    <!-- Background Image Layer with Pixel Filtering & Depth -->
    <div class="env-bg-layer" :style="bgLayerStyle">
      <!-- Fallback image tag for guaranteed loading and error recovery -->
      <img
        :src="resolveAsset(environment.backgroundAsset)"
        :alt="environment.name"
        class="env-bg-image"
        @error="onImageError"
      />
    </div>

    <!-- Sky Horizon & Atmospheric Fog Mist -->
    <div class="env-atmosphere">
      <div class="atmosphere-sky-tint"></div>
      <div class="atmosphere-depth-fog"></div>
    </div>

    <!-- Ground Plane / Battlefield Floor Layer -->
    <div class="env-ground-plane" :class="groundClass">
      <div class="ground-perspective-grid"></div>
      <div class="ground-shadow-horizon"></div>
    </div>

    <!-- Dynamic Ambient Particles Layer (Fireflies, Embers, Void Motes, Mist) -->
    <div class="env-particles-layer" aria-hidden="true">
      <div
        v-for="particle in activeParticles"
        :key="particle.id"
        class="ambient-particle"
        :class="particle.type"
        :style="particle.style"
      ></div>
    </div>

    <!-- Lighting Vignette & Color Grading Overlay -->
    <div class="env-lighting-overlay"></div>

    <!-- Slot for Battle HUD and Combat Arena (Sprites + UI) -->
    <div class="env-content-slot">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { resolveAsset } from '../../services/bossMapping'

const props = defineProps({
  environment: {
    type: Object,
    required: true
  },
  isBoss: {
    type: Boolean,
    default: false
  }
})

const imageLoadFailed = ref(false)

watch(() => props.environment?.backgroundAsset, () => {
  imageLoadFailed.value = false
})

function onImageError(e) {
  console.warn('[BattlefieldEnvironment] Image load failed:', props.environment?.backgroundAsset, e)
  imageLoadFailed.value = true
}

const environmentStyles = computed(() => {
  return {
    '--env-shadow-color': props.environment.shadowStyle || 'rgba(0, 0, 0, 0.65)'
  }
})

const bgLayerStyle = computed(() => {
  const raw = imageLoadFailed.value
    ? 'assets/adventure/battlefields/chapter-01-outdoor.jpg'
    : (props.environment.backgroundAsset || 'assets/adventure/battlefields/chapter-01-outdoor.jpg')
  const bg = resolveAsset(raw)

  return {
    backgroundImage: `url("${bg}")`
  }
})

const groundClass = computed(() => {
  return `ground-${props.environment.groundType || 'forest_grass'}`
})

// Generate subtle ambient atmospheric floating particles based on environment config
const activeParticles = ref([])

onMounted(() => {
  const effects = props.environment.ambientEffects || []
  const count = effects.includes('fireflies') || effects.includes('torch_embers') ? 16 : 10
  const particles = []

  const primaryEffect = effects[0] || 'subtle_mote'

  for (let i = 0; i < count; i++) {
    const left = Math.random() * 95 + 2
    const top = Math.random() * 70 + 15
    const duration = 3.5 + Math.random() * 4
    const delay = Math.random() * 3
    const size = 3 + Math.random() * 3

    particles.push({
      id: `p-${i}`,
      type: `particle-${primaryEffect}`,
      style: {
        left: `${left}%`,
        top: `${top}%`,
        width: `${size}px`,
        height: `${size}px`,
        animationDuration: `${duration}s`,
        animationDelay: `-${delay}s`
      }
    })
  }

  activeParticles.value = particles
})
</script>

<style scoped>
.battlefield-environment {
  position: relative;
  width: 100%;
  height: 100%;
  min-height: 400px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: #090514;
}

.is-boss-env {
  min-height: 440px;
}

/* ══════════════════════════════════════════════════════════════
   1. BACKGROUND ARTWORK LAYER
   ══════════════════════════════════════════════════════════════ */
.env-bg-layer {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center bottom;
  background-repeat: no-repeat;
  image-rendering: auto;
  z-index: 1;
  transition: background-image 0.6s ease-in-out;
}

/* Hidden visual image for browser preloading & fallback */
.env-bg-image {
  display: none;
}

/* ══════════════════════════════════════════════════════════════
   2. ATMOSPHERE & SKY TINT
   ══════════════════════════════════════════════════════════════ */
.env-atmosphere {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 2;
}

.atmosphere-sky-tint {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 45%;
  background: linear-gradient(180deg, rgba(12, 6, 24, 0.4) 0%, transparent 100%);
}

.lighting-torchlight_amber .atmosphere-sky-tint {
  background: linear-gradient(180deg, rgba(20, 10, 8, 0.45) 0%, transparent 100%);
}

.lighting-sanguine_crimson .atmosphere-sky-tint {
  background: linear-gradient(180deg, rgba(28, 5, 8, 0.55) 0%, transparent 100%);
}

.lighting-crypt_cyan_torch .atmosphere-sky-tint {
  background: linear-gradient(180deg, rgba(4, 14, 22, 0.55) 0%, transparent 100%);
}

.atmosphere-depth-fog {
  position: absolute;
  bottom: 25%;
  left: 0;
  right: 0;
  height: 80px;
  background: linear-gradient(180deg, transparent 0%, rgba(20, 10, 35, 0.18) 50%, transparent 100%);
  filter: blur(8px);
}

/* ══════════════════════════════════════════════════════════════
   3. GROUND PLANE / BATTLEFIELD FLOOR
   ══════════════════════════════════════════════════════════════ */
.env-ground-plane {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 38%;
  pointer-events: none;
  z-index: 3;
}

/* Subtle ground perspective shadow */
.ground-shadow-horizon {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  background: linear-gradient(
    180deg,
    transparent 0%,
    rgba(10, 5, 18, 0.15) 30%,
    rgba(10, 5, 18, 0.45) 100%
  );
}

/* Ground-specific lighting highlights */
.ground-forest_grass,
.ground-forest_moss_grass {
  background: linear-gradient(180deg, transparent 0%, rgba(18, 40, 18, 0.2) 60%, rgba(8, 20, 10, 0.45) 100%);
}

.ground-castle_courtyard_stone,
.ground-flagstone_gate,
.ground-cobblestone_courtyard {
  background: linear-gradient(180deg, transparent 0%, rgba(35, 25, 20, 0.2) 60%, rgba(18, 12, 12, 0.5) 100%);
}

.ground-polished_gothic_flagstone {
  background: linear-gradient(180deg, transparent 0%, rgba(25, 15, 35, 0.2) 60%, rgba(15, 6, 20, 0.55) 100%);
}

.ground-corrupted_bloodstone {
  background: linear-gradient(180deg, transparent 0%, rgba(45, 8, 14, 0.25) 50%, rgba(25, 4, 8, 0.6) 100%);
}

.ground-ancient_crypt_stone {
  background: linear-gradient(180deg, transparent 0%, rgba(8, 25, 35, 0.25) 50%, rgba(4, 12, 18, 0.6) 100%);
}

.ground-floating_void_platform,
.ground-obsidian_fracture_rock {
  background: linear-gradient(180deg, transparent 0%, rgba(30, 8, 45, 0.25) 50%, rgba(15, 4, 25, 0.6) 100%);
}

.ground-arcane_archive_wood_stone {
  background: linear-gradient(180deg, transparent 0%, rgba(12, 28, 42, 0.25) 50%, rgba(5, 14, 24, 0.6) 100%);
}

/* ══════════════════════════════════════════════════════════════
   4. AMBIENT ATMOSPHERIC PARTICLES
   ══════════════════════════════════════════════════════════════ */
.env-particles-layer {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 5;
  overflow: hidden;
}

.ambient-particle {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  opacity: 0.75;
  animation: float-ambient infinite ease-in-out;
}

/* Fireflies (Warm Gold / Green) */
.particle-fireflies {
  background: #fef08a;
  box-shadow: 0 0 8px #eab308, 0 0 14px #ca8a04;
}

/* Torch embers (Warm Orange / Red) */
.particle-torch_embers {
  background: #fdba74;
  box-shadow: 0 0 8px #f97316, 0 0 14px #ea580c;
  animation-name: ember-drift;
}

/* Candle dust / Golden motes */
.particle-candle_glow,
.particle-subtle_mote {
  background: #fed7aa;
  box-shadow: 0 0 6px #f59e0b;
}

/* Crypt / Cyan mist motes */
.particle-crypt_mist {
  background: #7dd3fc;
  box-shadow: 0 0 8px #0284c7;
}

/* Void sparks (Purple) */
.particle-void_sparks,
.particle-runic_shimmer {
  background: #d8b4fe;
  box-shadow: 0 0 8px #a855f7;
}

/* Crimson rifts */
.particle-crimson_embers,
.particle-crimson_sparks {
  background: #fca5a5;
  box-shadow: 0 0 8px #ef4444;
}

@keyframes float-ambient {
  0%, 100% {
    transform: translateY(0) translateX(0) scale(0.8);
    opacity: 0.2;
  }
  50% {
    transform: translateY(-22px) translateX(12px) scale(1.15);
    opacity: 0.85;
  }
}

@keyframes ember-drift {
  0% {
    transform: translateY(0) translateX(0);
    opacity: 0.8;
  }
  50% {
    transform: translateY(-30px) translateX(-15px);
    opacity: 0.5;
  }
  100% {
    transform: translateY(-60px) translateX(-25px);
    opacity: 0;
  }
}

/* ══════════════════════════════════════════════════════════════
   5. LIGHTING VIGNETTE OVERLAY
   ══════════════════════════════════════════════════════════════ */
.env-lighting-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 6;
  background: radial-gradient(
    ellipse at center,
    transparent 55%,
    rgba(6, 4, 12, 0.45) 85%,
    rgba(4, 2, 8, 0.8) 100%
  );
}

.is-boss-env .env-lighting-overlay {
  background: radial-gradient(
    ellipse at center,
    transparent 45%,
    rgba(14, 2, 8, 0.5) 80%,
    rgba(8, 1, 4, 0.85) 100%
  );
}

/* ══════════════════════════════════════════════════════════════
   6. CONTENT SLOT (HUD + ARENA)
   ══════════════════════════════════════════════════════════════ */
.env-content-slot {
  position: relative;
  z-index: 10;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  flex: 1;
}
</style>
