<!--
  CosmeticEffectPreview.vue
  Renders a visual card preview for each cosmetic item in the Shop grid.
  Uses CSS animations to demonstrate what each effect looks like.
-->
<template>
  <div class="effect-preview" :class="[item.category_slug, item.effect_key, item.rarity]">

    <!-- ══ ATTACK EFFECT & SPLASH EFFECT (PNG STATIC -> GIF HOVER, OR DIRECT GIF FOR SPLASH) ══ -->
    <div
      v-if="isEffectType"
      class="cosmetic-effect-asset-preview"
      :class="{
        'is-hovered': shouldPlayGif,
        'is-splash': isSplashEffect,
        'is-potion': item.category_slug === 'potion' || item.category_slug === 'potions' || item.type === 'potion',
        'is-attack': item.category_slug === 'attack_effect' || (!item.category_slug && item.type !== 'potion' && !isSplashEffect) || item.type === 'attack_effect'
      }"
      @mouseenter="onHover(true)"
      @mouseleave="onHover(false)"
      @click="onClick"
      @touchstart.passive="toggleMobilePreview"
    >
      <div class="effect-img-container">
        <!-- Direct GIF display for splash effects (no PNG, no hover required) -->
        <img
          v-if="isSplashEffect && hasGif"
          :src="gifSrc"
          :alt="`${item.name} animation`"
          class="effect-asset-img effect-gif pixel-art is-direct-splash"
        />
        <!-- Static PNG default (for attack effects/potions centered when not hovering, or if GIF is missing) -->
        <img
          v-else-if="hasPng && (!shouldPlayGif || !hasGif)"
          :src="pngSrc"
          :alt="item.name"
          class="effect-asset-img effect-png pixel-art"
          loading="lazy"
        />
        <!-- Animated GIF on Hover or when Clicked, or as fallback if PNG is missing -->
        <img
          v-else-if="hasGif"
          :src="gifSrc"
          :alt="`${item.name} animation`"
          class="effect-asset-img effect-gif pixel-art"
        />
        <!-- Safe Fallback if both assets missing -->
        <div v-else class="effect-asset-fallback">
          <span class="fallback-icon">✨</span>
        </div>
      </div>

      <!-- Point / Hover Prompt (hidden for splash effects) -->
      <div v-if="hasGif && !isSplashEffect" class="effect-hover-indicator font-pixel">
        <span class="indicator-icon">{{ shouldPlayGif ? '⚡' : '🔮' }}</span>
        <span class="indicator-text">{{ shouldPlayGif ? 'ANIMATING' : 'HOVER TO PREVIEW' }}</span>
      </div>
    </div>

    <!-- ══ CHARACTER SKIN ══ -->
    <div v-else-if="item.category_slug === 'character_skin'" class="skin-preview">
      <div class="skin-disc" :style="skinDiscStyle">
        <span class="skin-icon">🧬</span>
      </div>
      <div class="skin-glow" :style="skinGlowStyle"></div>
    </div>

    <!-- ══ AURA ══ -->
    <div v-else-if="item.category_slug === 'aura'" class="aura-preview">
      <div class="aura-disc" :class="item.effect_key">
        <span class="aura-center-icon">✨</span>
        <div class="aura-ring-demo" :class="item.effect_key"></div>
      </div>
    </div>

    <!-- ══ BACK EFFECT ══ -->
    <div v-else-if="item.category_slug === 'back_effect'" class="back-preview">
      <div class="back-silhouette">
        <div class="back-effect-demo" :class="item.effect_key">
          <!-- Wings Light -->
          <svg v-if="item.effect_key === 'wings-light'" viewBox="0 0 100 60" class="wing-svg">
            <path d="M50 30 Q30 10 8 20 Q4 30 14 42 Q30 46 50 35Z" fill="rgba(224,231,255,0.7)" stroke="#c7d2fe" stroke-width="1"/>
            <path d="M50 30 Q70 10 92 20 Q96 30 86 42 Q70 46 50 35Z" fill="rgba(224,231,255,0.7)" stroke="#c7d2fe" stroke-width="1"/>
          </svg>
          <!-- Shadow Wings -->
          <svg v-else-if="item.effect_key === 'wings-dark'" viewBox="0 0 100 60" class="wing-svg">
            <path d="M50 30 Q28 8 5 18 Q2 30 12 44 Q30 48 50 36Z" fill="rgba(91,33,182,0.6)" stroke="#a855f7" stroke-width="1.5"/>
            <path d="M50 30 Q72 8 95 18 Q98 30 88 44 Q70 48 50 36Z" fill="rgba(91,33,182,0.6)" stroke="#a855f7" stroke-width="1.5"/>
          </svg>
          <!-- Crimson Cape -->
          <svg v-else-if="item.effect_key === 'cape-crimson'" viewBox="0 0 70 80" class="cape-svg">
            <path d="M20 8 Q35 4 50 8 L58 72 Q35 80 12 72Z" fill="rgba(220,38,38,0.75)" stroke="#f87171" stroke-width="1"/>
          </svg>
          <!-- Galaxy Trail -->
          <svg v-else-if="item.effect_key === 'cape-galaxy'" viewBox="0 0 70 80" class="cape-svg">
            <defs>
              <linearGradient id="galDemoG" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#4f46e5"/>
                <stop offset="100%" stop-color="#7c3aed"/>
              </linearGradient>
            </defs>
            <path d="M20 8 Q35 4 50 8 L58 72 Q35 80 12 72Z" fill="url(#galDemoG)" stroke="#818cf8" stroke-width="1.5"/>
            <circle cx="32" cy="25" r="1.5" fill="white" opacity="0.9"/>
            <circle cx="44" cy="38" r="1" fill="white" opacity="0.7"/>
            <circle cx="30" cy="55" r="1.5" fill="#a5b4fc" opacity="0.9"/>
          </svg>
        </div>
        <!-- Character placeholder -->
        <div class="char-silhouette">👤</div>
      </div>
    </div>

    <!-- ══ HIT EFFECT ══ -->
    <div v-else-if="item.category_slug === 'hit_effect'" class="hit-preview">
      <div class="hit-burst-demo" :class="item.effect_key">
        <div class="hit-inner"></div>
        <div class="hit-ring hit-ring-1"></div>
        <div class="hit-ring hit-ring-2"></div>
      </div>
      <div class="hit-sparks">
        <span v-for="i in 6" :key="i" class="spark" :style="sparkStyle(i)">✦</span>
      </div>
    </div>

    <!-- ══ FINISHER EFFECT ══ -->
    <div v-else-if="item.category_slug === 'finisher_effect'" class="finisher-preview">
      <div class="finisher-burst" :class="item.effect_key">
        <div class="finisher-rays">
          <div v-for="i in 8" :key="i" class="ray" :style="rayStyle(i)"></div>
        </div>
        <div class="finisher-crown">🏆</div>
      </div>
    </div>

    <!-- Fallback -->
    <div v-else class="default-preview">
      <span class="default-icon">{{ item.icon || '🎁' }}</span>
    </div>

    <!-- Legendary Sparkle overlay -->
    <div v-if="item.rarity === 'legendary'" class="legendary-sparkles">
      <span class="lg-spark" style="top:15%;left:20%;animation-delay:0s">✦</span>
      <span class="lg-spark" style="top:25%;right:18%;animation-delay:0.4s">✧</span>
      <span class="lg-spark" style="bottom:20%;left:30%;animation-delay:0.8s">✦</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { resolveAsset } from '../../services/bossMapping'
import { COSMETIC_EFFECTS } from '../../config/cosmeticEffects'

const props = defineProps({
  item: { type: Object, required: true },
  isHovered: { type: Boolean, default: false },
  autoplay: { type: Boolean, default: false }
})

const localHovered = ref(false)
const isClicked = ref(false)

const shouldPlayGif = computed(() => {
  return Boolean(props.autoplay || props.isHovered || localHovered.value || isClicked.value)
})

const isSplashEffect = computed(() => {
  const cat = props.item?.category_slug
  const type = props.item?.type
  return cat === 'splash_effect' || type === 'splash_effect' || String(props.item?.effect_key || '').includes('splash')
})

const isEffectType = computed(() => {
  const cat = props.item?.category_slug
  const type = props.item?.type
  return cat === 'attack_effect' || cat === 'splash_effect' || type === 'attack_effect' || type === 'splash_effect' || isSplashEffect.value || Boolean(props.item?.png_path) || Boolean(props.item?.gif_path)
})

// Match catalog metadata if missing on raw DB item
const catalogMeta = computed(() => {
  return COSMETIC_EFFECTS.find(
    e => e.id === props.item?.effect_key || e.id === props.item?.id || e.name === props.item?.name
  )
})

const ASSET_VERSION = 'v=20261005_c3'

const pngSrc = computed(() => {
  const path = props.item?.png_path || catalogMeta.value?.png_path
  if (path) {
    const base = resolveAsset(path)
    return base.includes('?') ? `${base}&${ASSET_VERSION}` : `${base}?${ASSET_VERSION}`
  }
  return ''
})

const gifSrc = computed(() => {
  const path = props.item?.gif_path || catalogMeta.value?.gif_path
  if (path) {
    const base = resolveAsset(path)
    return base.includes('?') ? `${base}&${ASSET_VERSION}` : `${base}?${ASSET_VERSION}`
  }
  return ''
})

const hasPng = computed(() => Boolean(pngSrc.value))
const hasGif = computed(() => Boolean(gifSrc.value))

onMounted(() => {
  if (gifSrc.value) {
    const img = new Image()
    img.src = gifSrc.value
  }
})

function onHover(state) {
  localHovered.value = state
}

function onClick(e) {
  isClicked.value = !isClicked.value
}

function toggleMobilePreview() {
  onClick()
}

const SKIN_COLORS = {
  'skin-default':  { bg: '#f2d3b1', glow: 'rgba(242,211,177,0.4)' },
  'skin-crimson':  { bg: '#c25b3a', glow: 'rgba(194,91,58,0.5)' },
  'skin-midnight': { bg: '#4c2d7c', glow: 'rgba(76,45,124,0.55)' },
  'skin-auric':    { bg: '#c8973a', glow: 'rgba(200,151,58,0.55)' },
}

const skinColors = computed(() => {
  return SKIN_COLORS[props.item?.effect_key] || SKIN_COLORS['skin-default']
})

const skinDiscStyle = computed(() => ({ background: skinColors.value.bg }))
const skinGlowStyle = computed(() => ({ boxShadow: `0 0 40px ${skinColors.value.glow}` }))

function sparkStyle(i) {
  const angle = (i - 1) * 60
  const dist  = 30
  const x = Math.cos((angle * Math.PI) / 180) * dist
  const y = Math.sin((angle * Math.PI) / 180) * dist
  return {
    position: 'absolute',
    left: `calc(50% + ${x}px)`,
    top: `calc(50% + ${y}px)`,
    transform: 'translate(-50%,-50%)',
    animationDelay: `${(i - 1) * 0.18}s`,
    fontSize: '0.55rem',
  }
}

function rayStyle(i) {
  const angle = ((i - 1) * 45) - 90
  return {
    transform: `rotate(${angle}deg)`,
    transformOrigin: '0 0',
    position: 'absolute',
    left: '50%',
    top: '50%',
  }
}
</script>

<style scoped>
/* ── Wrapper ── */
.effect-preview {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* ══ ATTACK EFFECT & SPLASH EFFECT PREVIEW CONTAINER ══ */
.cosmetic-effect-asset-preview {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  cursor: pointer;
  user-select: none;
}

.effect-img-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin: 0 auto;
}

.effect-asset-img {
  image-rendering: pixelated;
  filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.4));
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.25s ease;
}

/* Static PNG preview (centerized horizontally & vertically when not hovering) */
.effect-asset-img.effect-png {
  max-width: 82px;
  max-height: 82px;
  width: auto;
  height: auto;
  object-fit: contain;
  object-position: center;
  margin: auto;
  display: block;
  transform: none !important;
}

/* Animated GIF preview on hover */
.effect-asset-img.effect-gif {
  max-width: 92%;
  max-height: 92%;
  width: auto;
  height: auto;
  object-fit: contain;
  object-position: center;
  margin: auto;
  display: block;
}

/* Attack effect GIF scaled smoothly on hover without displacement */
.cosmetic-effect-asset-preview.is-attack .effect-asset-img.effect-gif {
  transform: scale(1.05);
  transform-origin: center center;
  filter: drop-shadow(0 0 14px rgba(251, 191, 36, 0.75));
}

.cosmetic-effect-asset-preview.is-splash .effect-asset-img {
  filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.4));
}

.cosmetic-effect-asset-preview.is-splash:hover .effect-asset-img,
.cosmetic-effect-asset-preview.is-splash.is-hovered .effect-asset-img {
  transform: scale(1.12);
  filter: drop-shadow(0 0 16px rgba(239, 68, 68, 0.7));
}

.cosmetic-effect-asset-preview.is-potion .effect-asset-img {
  filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.45));
}

.cosmetic-effect-asset-preview.is-potion:hover .effect-asset-img,
.cosmetic-effect-asset-preview.is-potion.is-hovered .effect-asset-img {
  transform: scale(1.12) !important;
  filter: drop-shadow(0 0 18px rgba(248, 113, 113, 0.8));
}

.effect-asset-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
.effect-asset-fallback .fallback-icon {
  font-size: 2rem;
  opacity: 0.5;
}

.effect-hover-indicator {
  position: absolute;
  bottom: 6px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 5;
  pointer-events: none;
  font-size: 0.52rem;
  letter-spacing: 0.08em;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(15, 23, 42, 0.82);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 2px 7px;
  border-radius: 4px;
  white-space: nowrap;
  backdrop-filter: blur(4px);
  transition: all 0.2s ease;
}

.cosmetic-effect-asset-preview.is-hovered .effect-hover-indicator {
  color: #fbbf24;
  border-color: rgba(251, 191, 36, 0.4);
  background: rgba(251, 191, 36, 0.15);
  box-shadow: 0 0 8px rgba(251, 191, 36, 0.25);
}

/* ══ CHARACTER SKIN ══ */
.skin-preview {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.skin-disc {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.8rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.4);
  border: 2px solid rgba(255,255,255,0.2);
  position: relative;
  z-index: 1;
  animation: skinBob 3s ease-in-out infinite;
}

.skin-glow {
  position: absolute;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  animation: skinGlowPulse 2s ease-in-out infinite;
}

@keyframes skinBob {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

@keyframes skinGlowPulse {
  0%, 100% { opacity: 0.5; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.15); }
}

/* ══ AURA ══ */
.aura-preview {
  display: flex;
  align-items: center;
  justify-content: center;
}

.aura-disc {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: rgba(15,23,42,0.8);
  border: 2px solid rgba(99,102,241,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.aura-center-icon { font-size: 1.8rem; z-index: 1; }

.aura-ring-demo {
  position: absolute;
  inset: -5px;
  border-radius: 50%;
  border: 3px solid transparent;
  animation: auraDemoSpin 2s ease-in-out infinite;
}

/* Aura colors */
.aura-ring-demo.aura-psychic {
  border-color: rgba(139,92,246,0.8);
  box-shadow: 0 0 16px rgba(139,92,246,0.7), inset 0 0 8px rgba(139,92,246,0.3);
}
.aura-ring-demo.aura-flame {
  border-color: rgba(251,146,60,0.9);
  box-shadow: 0 0 18px rgba(251,146,60,0.8), inset 0 0 8px rgba(251,146,60,0.3);
}
.aura-ring-demo.aura-ice {
  border-color: rgba(56,189,248,0.8);
  box-shadow: 0 0 16px rgba(56,189,248,0.7), inset 0 0 8px rgba(56,189,248,0.3);
}
.aura-ring-demo.aura-thunder {
  border-color: rgba(250,204,21,0.9);
  box-shadow: 0 0 20px rgba(250,204,21,0.9), inset 0 0 8px rgba(250,204,21,0.4);
}

@keyframes auraDemoSpin {
  0%, 100% { transform: scale(1); opacity: 0.7; }
  50% { transform: scale(1.12); opacity: 1; }
}

/* ══ BACK EFFECT ══ */
.back-preview {
  display: flex;
  align-items: center;
  justify-content: center;
}

.back-silhouette {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.back-effect-demo {
  position: absolute;
  inset: -20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.wing-svg, .cape-svg {
  width: 90px;
  height: 64px;
}

.char-silhouette {
  font-size: 2.2rem;
  position: relative;
  z-index: 2;
  filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));
}

/* Wing float animations */
.wings-light .wing-svg { animation: wingFloat 2.5s ease-in-out infinite; filter: drop-shadow(0 0 8px rgba(199,210,254,0.6)); }
.wings-dark .wing-svg  { animation: wingFloat 2s ease-in-out infinite; filter: drop-shadow(0 0 10px rgba(168,85,247,0.8)); }
.cape-crimson .cape-svg { animation: capeSway 1.8s ease-in-out infinite; filter: drop-shadow(0 4px 8px rgba(220,38,38,0.5)); }
.cape-galaxy .cape-svg  { animation: capeSway 2.2s ease-in-out infinite; filter: drop-shadow(0 4px 12px rgba(99,102,241,0.7)); }

@keyframes wingFloat { 0%, 100% { transform: translateY(0) scaleX(1); } 50% { transform: translateY(-5%) scaleX(1.06); } }
@keyframes capeSway  { 0%, 100% { transform: rotate(-2deg); } 50% { transform: rotate(2deg); } }

/* ══ HIT EFFECT ══ */
.hit-preview {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hit-burst-demo {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: hitPop 1.4s ease-in-out infinite;
}
.hit-burst-demo.hit-focus    { background: radial-gradient(circle, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0) 70%); }
.hit-burst-demo.hit-psych    { background: radial-gradient(circle, rgba(129,140,248,0.95) 0%, rgba(99,102,241,0) 70%); }
.hit-burst-demo.hit-mind     { background: radial-gradient(circle, rgba(168,85,247,0.95) 0%, rgba(139,92,246,0) 70%); }
.hit-burst-demo.hit-collapse { background: radial-gradient(circle, rgba(30,41,59,1) 0%, rgba(15,23,42,0) 70%); box-shadow: 0 0 18px rgba(99,102,241,0.6); }

.hit-inner { width: 20px; height: 20px; border-radius: 50%; background: white; opacity: 0.8; }
.hit-burst-demo.hit-psych .hit-inner    { background: #c7d2fe; }
.hit-burst-demo.hit-mind .hit-inner     { background: #e9d5ff; }
.hit-burst-demo.hit-collapse .hit-inner { background: #1e293b; box-shadow: 0 0 10px rgba(99,102,241,0.8); }

.hit-ring {
  position: absolute;
  border-radius: 50%;
  border: 2px solid currentColor;
  animation: hitRingExpand 1.4s ease-out infinite;
}
.hit-ring-1 { width: 40px; height: 40px; animation-delay: 0s; }
.hit-ring-2 { width: 60px; height: 60px; animation-delay: 0.25s; }
.hit-burst-demo.hit-focus    .hit-ring { color: rgba(255,255,255,0.6); }
.hit-burst-demo.hit-psych    .hit-ring { color: rgba(129,140,248,0.6); }
.hit-burst-demo.hit-mind     .hit-ring { color: rgba(168,85,247,0.6); }
.hit-burst-demo.hit-collapse .hit-ring { color: rgba(99,102,241,0.5); }

@keyframes hitPop {
  0%, 100% { transform: scale(0.85); opacity: 0.6; }
  40% { transform: scale(1.05); opacity: 1; }
  70% { transform: scale(0.95); opacity: 0.9; }
}
@keyframes hitRingExpand {
  0%   { transform: scale(0.5); opacity: 0.9; }
  100% { transform: scale(2); opacity: 0; }
}

.hit-sparks {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.spark {
  color: #fbbf24;
  font-size: 0.55rem;
  animation: sparkFloat 1.4s ease-in-out infinite;
  display: block;
}

@keyframes sparkFloat {
  0%, 100% { opacity: 0; transform: translate(-50%,-50%) scale(0.5); }
  50% { opacity: 1; transform: translate(-50%,-50%) scale(1); }
}

/* ══ FINISHER EFFECT ══ */
.finisher-preview {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}

.finisher-burst {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
}
.finisher-burst.finish-flash    { filter: drop-shadow(0 0 12px rgba(255,255,255,0.5)); }
.finisher-burst.finish-insight  { filter: drop-shadow(0 0 14px rgba(99,102,241,0.7)); }
.finisher-burst.finish-enlighten{ filter: drop-shadow(0 0 16px rgba(245,158,11,0.7)); }
.finisher-burst.finish-legend   { filter: drop-shadow(0 0 18px rgba(139,92,246,0.8)); }

.finisher-rays {
  position: absolute;
  inset: 0;
  animation: raysRotate 4s linear infinite;
}
.ray {
  width: 2px;
  height: 30px;
  border-radius: 2px;
  position: absolute;
}
.finish-flash     .ray { background: rgba(255,255,255,0.6); }
.finish-insight   .ray { background: rgba(129,140,248,0.7); }
.finish-enlighten .ray { background: rgba(251,191,36,0.8); }
.finish-legend    .ray { background: linear-gradient(to top, rgba(139,92,246,0.8), transparent); }

@keyframes raysRotate { to { transform: rotate(360deg); } }

.finisher-crown {
  font-size: 2.2rem;
  position: relative;
  z-index: 1;
  animation: crownBob 1.5s ease-in-out infinite;
}
@keyframes crownBob {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-4px) scale(1.08); }
}

/* ══ LEGENDARY SPARKLES ══ */
.legendary-sparkles {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.lg-spark {
  position: absolute;
  font-size: 0.7rem;
  color: #fde68a;
  text-shadow: 0 0 6px rgba(251,191,36,0.8);
  animation: lgSparkle 2s ease-in-out infinite;
}

@keyframes lgSparkle {
  0%, 100% { opacity: 0; transform: scale(0.5) rotate(0deg); }
  50% { opacity: 1; transform: scale(1.2) rotate(180deg); }
}

/* ══ DEFAULT ══ */
.default-preview { display: flex; align-items: center; justify-content: center; }
.default-icon { font-size: 2.5rem; }
</style>
