<template>
  <div
    class="avatar-wrapper"
    :class="wrapperClasses"
    :style="wrapperStyle"
  >
    <!-- ══ BACK EFFECT LAYER (behind everything) ══ -->
    <div
      v-if="activeBackEffect"
      class="back-effect-layer"
      :class="activeBackEffect.effect_key"
    >
      <!-- Wings Light -->
      <svg v-if="activeBackEffect.effect_key === 'wings-light'" viewBox="0 0 200 120" class="back-svg">
        <defs>
          <radialGradient id="wingGlowL" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#ffffff" stop-opacity="0.9"/>
            <stop offset="100%" stop-color="#e0e7ff" stop-opacity="0.3"/>
          </radialGradient>
        </defs>
        <path d="M100 60 Q60 20 20 40 Q10 60 30 80 Q60 90 100 70Z" fill="url(#wingGlowL)" stroke="#c7d2fe" stroke-width="1"/>
        <path d="M100 60 Q140 20 180 40 Q190 60 170 80 Q140 90 100 70Z" fill="url(#wingGlowL)" stroke="#c7d2fe" stroke-width="1"/>
        <line x1="100" y1="60" x2="30" y2="55" stroke="white" stroke-width="0.5" opacity="0.5"/>
        <line x1="100" y1="68" x2="170" y2="55" stroke="white" stroke-width="0.5" opacity="0.5"/>
      </svg>
      <!-- Shadow Wings -->
      <svg v-else-if="activeBackEffect.effect_key === 'wings-dark'" viewBox="0 0 200 120" class="back-svg">
        <defs>
          <radialGradient id="wingGlowD" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#3b0764" stop-opacity="0.95"/>
            <stop offset="100%" stop-color="#1a0030" stop-opacity="0.5"/>
          </radialGradient>
        </defs>
        <path d="M100 60 Q55 15 12 38 Q5 60 22 85 Q55 95 100 72Z" fill="url(#wingGlowD)" stroke="#a855f7" stroke-width="1.5"/>
        <path d="M100 60 Q145 15 188 38 Q195 60 178 85 Q145 95 100 72Z" fill="url(#wingGlowD)" stroke="#a855f7" stroke-width="1.5"/>
      </svg>
      <!-- Crimson Cape -->
      <svg v-else-if="activeBackEffect.effect_key === 'cape-crimson'" viewBox="0 0 160 140" class="back-svg">
        <defs>
          <linearGradient id="capeGrad" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#dc2626"/>
            <stop offset="100%" stop-color="#450a0a"/>
          </linearGradient>
        </defs>
        <path d="M50 10 Q80 5 110 10 L125 130 Q80 145 35 130Z" fill="url(#capeGrad)" stroke="#fca5a5" stroke-width="1"/>
        <path d="M60 10 L55 130" stroke="#ef4444" stroke-width="0.5" opacity="0.4"/>
        <path d="M80 8 L80 135" stroke="#ef4444" stroke-width="0.5" opacity="0.4"/>
        <path d="M100 10 L105 130" stroke="#ef4444" stroke-width="0.5" opacity="0.4"/>
      </svg>
      <!-- Galaxy Trail -->
      <svg v-else-if="activeBackEffect.effect_key === 'cape-galaxy'" viewBox="0 0 160 140" class="back-svg galaxy-svg">
        <defs>
          <linearGradient id="galaxyGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#312e81"/>
            <stop offset="50%" stop-color="#4f46e5"/>
            <stop offset="100%" stop-color="#7c3aed"/>
          </linearGradient>
        </defs>
        <path d="M50 10 Q80 5 110 10 L125 130 Q80 145 35 130Z" fill="url(#galaxyGrad)" stroke="#818cf8" stroke-width="1.5" opacity="0.85"/>
        <circle cx="65" cy="40" r="1.5" fill="white" opacity="0.9"/>
        <circle cx="95" cy="55" r="1" fill="white" opacity="0.7"/>
        <circle cx="75" cy="80" r="2" fill="white" opacity="0.8"/>
        <circle cx="100" cy="95" r="1" fill="white" opacity="0.6"/>
        <circle cx="60" cy="110" r="1.5" fill="#a5b4fc" opacity="0.9"/>
      </svg>
    </div>

    <!-- ══ BASE CIRCULAR DISC (Avatar + Aura Ring) ══ -->
    <div class="avatar-base-disc" :style="discStyle">
      <!-- Aura Ring (animated inside disc border) -->
      <div v-if="activeAura" class="aura-ring" :class="activeAura.effect_key"></div>

      <!-- Local Gothic / Pixel-Art Scholar Character Portrait -->
      <svg
        :width="size"
        :height="size"
        viewBox="0 0 100 100"
        class="scholar-svg-portrait"
        aria-label="Scholar Adventurer"
      >
        <defs>
          <radialGradient :id="'discBgGrad-' + componentId" cx="50%" cy="40%" r="60%">
            <stop offset="0%" :stop-color="portraitColors.bgCenter"/>
            <stop offset="100%" :stop-color="portraitColors.bgOuter"/>
          </radialGradient>
          <filter :id="'pixelGlow-' + componentId" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="0" stdDeviation="1.5" :flood-color="portraitColors.eyeGlow" flood-opacity="0.8"/>
          </filter>
        </defs>

        <!-- Disc background fill -->
        <circle cx="50" cy="50" r="48" :fill="'url(#discBgGrad-' + componentId + ')'"/>

        <!-- Shadow cowl back -->
        <path d="M22 88 C20 70, 26 50, 34 38 C42 26, 58 26, 66 38 C74 50, 80 70, 78 88 Z" :fill="portraitColors.cowlDark"/>

        <!-- Robe / Shoulders -->
        <path d="M12 98 C18 78, 32 72, 50 72 C68 72, 82 78, 88 98 Z" :fill="portraitColors.robeBase"/>
        <path d="M38 72 L50 86 L62 72 Z" :fill="portraitColors.robeTrim"/>

        <!-- Brooch / Amulet of Wisdom -->
        <polygon points="50,83 54,88 50,93 46,88" :fill="portraitColors.gemColor" stroke="#ffffff" stroke-width="0.6"/>

        <!-- Face / Head (Skin Tone) -->
        <ellipse cx="50" cy="52" rx="19" ry="22" :fill="portraitColors.skinColor"/>

        <!-- Scholar Cowl / Hood Overlay Frame -->
        <path d="M28 42 C30 25, 42 16, 50 16 C58 16, 70 25, 72 42 C72 58, 68 66, 68 66 C68 66, 62 56, 50 56 C38 56, 32 66, 32 66 C32 66, 28 58, 28 42 Z" :fill="portraitColors.cowlMain"/>
        <path d="M29 42 C30 26, 42 18, 50 18 C58 18, 70 26, 71 42" fill="none" :stroke="portraitColors.cowlTrim" stroke-width="1.5"/>

        <!-- Gothic Scholar Forelock -->
        <path d="M38 42 Q46 44 48 50 Q43 45 38 45 Z" :fill="portraitColors.hairColor"/>
        <path d="M62 42 Q54 44 52 50 Q57 45 62 45 Z" :fill="portraitColors.hairColor"/>

        <!-- Piercing Scholar Eyes -->
        <ellipse cx="43" cy="52" rx="3.5" ry="2" :fill="portraitColors.eyeSclera"/>
        <ellipse cx="57" cy="52" rx="3.5" ry="2" :fill="portraitColors.eyeSclera"/>
        <circle cx="43" cy="52" r="1.8" :fill="portraitColors.eyeColor" :filter="'url(#pixelGlow-' + componentId + ')'"/>
        <circle cx="57" cy="52" r="1.8" :fill="portraitColors.eyeColor" :filter="'url(#pixelGlow-' + componentId + ')'"/>
        <circle cx="42" cy="51" r="0.6" fill="#ffffff"/>
        <circle cx="56" cy="51" r="0.6" fill="#ffffff"/>

        <!-- Eyebrows -->
        <path d="M39 48 Q44 46 47 48" fill="none" :stroke="portraitColors.hairColor" stroke-width="1" stroke-linecap="round"/>
        <path d="M61 48 Q56 46 53 48" fill="none" :stroke="portraitColors.hairColor" stroke-width="1" stroke-linecap="round"/>

        <!-- Nose -->
        <path d="M50 52 L49 57 L52 57" fill="none" :stroke="portraitColors.shadowSkin" stroke-width="1" stroke-linecap="round"/>

        <!-- Mouth -->
        <path d="M47 62 Q50 63 53 62" fill="none" :stroke="portraitColors.shadowSkin" stroke-width="1.2" stroke-linecap="round"/>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed, getCurrentInstance } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const instance = getCurrentInstance()
const componentId = instance?.uid ?? Math.floor(Math.random() * 100000)

const props = defineProps({
  styleName:   { type: String, default: 'adventurer' }, // Retained for backwards compatibility
  seed:        { type: String, default: 'default' },    // Retained for backwards compatibility
  options:     { type: Object, default: () => ({}) },   // Retained for backwards compatibility
  size:        { type: Number, default: 48 },
  previewItem: { type: Object, default: null },         // Live "Try On" item from shop
  equipped:    { type: Array,  default: null }          // Custom equipped list (defaults to auth store)
})

// ── Full active items list (preview overrides equipped slot) ────────
const activeItems = computed(() => {
  const base = [...(props.equipped ?? auth.equipped ?? [])]
  const preview = props.previewItem
  if (!preview) return base

  // Replace item in same category slot with preview
  const idx = base.findIndex(i => i.category_slug === preview.category_slug)
  if (idx >= 0) {
    const copy = [...base]
    copy[idx] = preview
    return copy
  }
  return [...base, preview]
})

// ── Category helpers ────────────────────────────────────────────────
function getByCategory(slug) {
  if (props.previewItem?.category_slug === slug) return props.previewItem
  return activeItems.value.find(i => i.category_slug === slug) || null
}

const activeSkin       = computed(() => getByCategory('character_skin'))
const activeAura       = computed(() => getByCategory('aura'))
const activeBackEffect = computed(() => getByCategory('back_effect'))

// ── Local Palette based on equipped character skin ─────────────────
const portraitColors = computed(() => {
  const skinKey = activeSkin.value?.effect_key || 'skin-default'

  switch (skinKey) {
    case 'skin-crimson':
      return {
        bgCenter:    '#2c0f14',
        bgOuter:     '#120406',
        skinColor:   '#c25b3a',
        shadowSkin:  '#8a371c',
        cowlDark:    '#25080c',
        cowlMain:    '#4a0d14',
        cowlTrim:    '#c0392b',
        robeBase:    '#32090e',
        robeTrim:    '#8b1a1a',
        gemColor:    '#ff4444',
        hairColor:   '#180507',
        eyeSclera:   '#ffdede',
        eyeColor:    '#e74c3c',
        eyeGlow:     '#ff2222',
      }
    case 'skin-midnight':
      return {
        bgCenter:    '#211636',
        bgOuter:     '#0c0618',
        skinColor:   '#8b7bb0',
        shadowSkin:  '#59477e',
        cowlDark:    '#180f28',
        cowlMain:    '#2e1f4d',
        cowlTrim:    '#a855f7',
        robeBase:    '#1f1435',
        robeTrim:    '#6b21a8',
        gemColor:    '#c084fc',
        hairColor:   '#130a21',
        eyeSclera:   '#f3e8ff',
        eyeColor:    '#a855f7',
        eyeGlow:     '#c084fc',
      }
    case 'skin-auric':
      return {
        bgCenter:    '#362810',
        bgOuter:     '#161004',
        skinColor:   '#d4a853',
        shadowSkin:  '#997327',
        cowlDark:    '#241a08',
        cowlMain:    '#4d3810',
        cowlTrim:    '#f59e0b',
        robeBase:    '#33250a',
        robeTrim:    '#b45309',
        gemColor:    '#fbbf24',
        hairColor:   '#1f1604',
        eyeSclera:   '#fef3c7',
        eyeColor:    '#f59e0b',
        eyeGlow:     '#fbbf24',
      }
    case 'skin-default':
    default:
      return {
        bgCenter:    '#1c2538',
        bgOuter:     '#0b101c',
        skinColor:   '#f2d3b1',
        shadowSkin:  '#c79d74',
        cowlDark:    '#0e1422',
        cowlMain:    '#182136',
        cowlTrim:    '#4f6b9c',
        robeBase:    '#131a2b',
        robeTrim:    '#2b3d63',
        gemColor:    '#60a5fa',
        hairColor:   '#0d111c',
        eyeSclera:   '#ffffff',
        eyeColor:    '#38bdf8',
        eyeGlow:     '#38bdf8',
      }
  }
})

// ── Wrapper classes ────────────────────────────────────────────────
const wrapperClasses = computed(() => {
  const classes = []
  if (activeAura.value) classes.push('has-aura')
  if (activeBackEffect.value) classes.push('has-back-effect')
  if (activeSkin.value) classes.push(`skin-${activeSkin.value.effect_key || 'default'}`)
  return classes
})

// ── Sizes ──────────────────────────────────────────────────────────
const wrapperStyle = computed(() => ({
  width: props.size + 'px',
  height: props.size + 'px'
}))

const discStyle = computed(() => ({
  width: props.size + 'px',
  height: props.size + 'px'
}))
</script>

<style scoped>
/* ── Wrapper ─────────────────────────────────────────────────────── */
.avatar-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

/* ── Base circular disc ───────────────────────────────────────────── */
.avatar-base-disc {
  border-radius: 50%;
  overflow: hidden;
  position: relative;
  background: linear-gradient(135deg, #1a2236, #243352);
  border: 2px solid rgba(124, 32, 240, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
  z-index: 2;
}

/* ── Avatar Image ──────────────────────────────────────────────────── */
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  position: relative;
  z-index: 2;
}

.avatar-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
}

/* ══════════════════════════════════════════════════
   BACK EFFECT LAYER — positioned behind avatar disc
   ══════════════════════════════════════════════════ */
.back-effect-layer {
  position: absolute;
  inset: -35% -50%;
  z-index: 1;
  pointer-events: none;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: backEffectFadeIn 0.4s ease;
}

@keyframes backEffectFadeIn {
  from { opacity: 0; transform: scaleX(0.7) scaleY(0.8); }
  to   { opacity: 1; transform: scaleX(1) scaleY(1); }
}

.back-svg {
  width: 100%;
  height: 100%;
}

/* Wings Light — gentle float animation */
.wings-light {
  animation: backEffectFadeIn 0.4s ease, wingFloat 3s ease-in-out infinite;
}
@keyframes wingFloat {
  0%, 100% { transform: translateY(0) scaleX(1); }
  50%       { transform: translateY(-4%) scaleX(1.04); }
}

/* Shadow Wings — dark pulse */
.wings-dark {
  animation: backEffectFadeIn 0.4s ease, darkPulse 2.5s ease-in-out infinite;
  filter: drop-shadow(0 0 12px rgba(168, 85, 247, 0.7));
}
@keyframes darkPulse {
  0%, 100% { filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.6)); }
  50%       { filter: drop-shadow(0 0 22px rgba(168, 85, 247, 1)); }
}

/* Crimson Cape — sway */
.cape-crimson {
  animation: backEffectFadeIn 0.4s ease, capeSway 2s ease-in-out infinite;
  filter: drop-shadow(0 4px 12px rgba(220, 38, 38, 0.5));
}
@keyframes capeSway {
  0%, 100% { transform: rotate(-1.5deg); }
  50%       { transform: rotate(1.5deg); }
}

/* Galaxy Trail — shimmer */
.cape-galaxy {
  animation: backEffectFadeIn 0.4s ease, galaxyShimmer 3s ease-in-out infinite;
  filter: drop-shadow(0 4px 16px rgba(99, 102, 241, 0.8));
}
@keyframes galaxyShimmer {
  0%, 100% { filter: drop-shadow(0 4px 14px rgba(99, 102, 241, 0.7)); }
  50%       { filter: drop-shadow(0 4px 28px rgba(139, 92, 246, 1)); }
}

/* ══════════════════════════════════════════════════
   AURA RING — glows inside the disc border
   ══════════════════════════════════════════════════ */
.aura-ring {
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  z-index: 0;
  pointer-events: none;
}

/* Psychic Pulse — violet */
.aura-ring.aura-psychic {
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.6),
              0 0 16px rgba(139, 92, 246, 0.5),
              0 0 32px rgba(139, 92, 246, 0.25);
  animation: auraPulse 2s ease-in-out infinite;
  border: 2px solid rgba(139, 92, 246, 0.8);
}
@keyframes auraPulse {
  0%, 100% { box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.6), 0 0 16px rgba(139, 92, 246, 0.5); }
  50%       { box-shadow: 0 0 0 5px rgba(139, 92, 246, 0.8), 0 0 32px rgba(139, 92, 246, 0.8), 0 0 60px rgba(139, 92, 246, 0.4); }
}

/* Flame Ward — orange */
.aura-ring.aura-flame {
  box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.7),
              0 0 20px rgba(251, 146, 60, 0.6),
              0 0 40px rgba(239, 68, 68, 0.3);
  animation: auraFlame 1.5s ease-in-out infinite;
  border: 2px solid rgba(251, 146, 60, 0.9);
}
@keyframes auraFlame {
  0%, 100% { box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.7), 0 0 18px rgba(251, 146, 60, 0.5); }
  33%       { box-shadow: 0 0 0 5px rgba(239, 68, 68, 0.8), 0 0 28px rgba(239, 68, 68, 0.7), 0 0 52px rgba(251, 146, 60, 0.4); }
  66%       { box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.7), 0 0 22px rgba(245, 158, 11, 0.6); }
}

/* Glacial Mind — ice blue */
.aura-ring.aura-ice {
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.6),
              0 0 20px rgba(56, 189, 248, 0.5),
              0 0 40px rgba(14, 165, 233, 0.25);
  animation: auraIce 2.5s ease-in-out infinite;
  border: 2px solid rgba(56, 189, 248, 0.8);
}
@keyframes auraIce {
  0%, 100% { box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.6), 0 0 16px rgba(56, 189, 248, 0.4); }
  50%       { box-shadow: 0 0 0 6px rgba(186, 230, 253, 0.7), 0 0 30px rgba(56, 189, 248, 0.8), 0 0 55px rgba(14, 165, 233, 0.35); }
}

/* Thunder Sovereign — gold spark */
.aura-ring.aura-thunder {
  box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.7),
              0 0 20px rgba(250, 204, 21, 0.6),
              0 0 40px rgba(245, 158, 11, 0.3);
  animation: auraThunder 0.8s ease-in-out infinite;
  border: 2px solid rgba(250, 204, 21, 0.9);
}
@keyframes auraThunder {
  0%, 100% { box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.7), 0 0 18px rgba(250, 204, 21, 0.5); opacity: 1; }
  20%       { box-shadow: 0 0 0 6px rgba(250, 204, 21, 0.9), 0 0 36px rgba(250, 204, 21, 1), 0 0 70px rgba(250, 204, 21, 0.5); opacity: 0.9; }
  40%       { box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8), 0 0 24px rgba(250, 204, 21, 0.7); opacity: 1; }
}

/* ── Disc border updates when aura is active ─────────────────────── */
.has-aura .avatar-base-disc {
  border-width: 3px;
}

/* ── aura disc colors ────────────────────────────────────────────── */
.has-aura.aura-psychic-active  .avatar-base-disc { border-color: rgba(139, 92, 246, 0.8); }
.has-aura.aura-flame-active    .avatar-base-disc { border-color: rgba(251, 146, 60, 0.8); }
.has-aura.aura-ice-active      .avatar-base-disc { border-color: rgba(56, 189, 248, 0.8); }
.has-aura.aura-thunder-active  .avatar-base-disc { border-color: rgba(250, 204, 21, 0.8); }
</style>
