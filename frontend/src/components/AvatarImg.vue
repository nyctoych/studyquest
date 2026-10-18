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
    <div class="avatar-base-disc" :class="discClasses" :style="discStyle">
      <!-- Aura Ring (animated inside disc border) -->
      <div v-if="activeAura" class="aura-ring" :class="activeAura.effect_key"></div>

      <!-- Character Class Profile Avatar (Non-Changeable) -->
      <img
        :src="currentAvatarSrc"
        :alt="avatarAlt"
        class="avatar-profile-img"
        draggable="false"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { getProfileAvatarByClass, normalizeCharacterClass } from '../utils/avatar'

const auth = useAuthStore()

const props = defineProps({
  styleName:      { type: String, default: 'adventurer' }, // Retained for backwards compatibility
  seed:           { type: String, default: 'default' },    // Retained for backwards compatibility
  options:        { type: Object, default: () => ({}) },   // Retained for backwards compatibility
  size:           { type: Number, default: 48 },
  previewItem:    { type: Object, default: null },         // Live "Try On" item from shop
  equipped:       { type: Array,  default: null },         // Custom equipped list (defaults to auth store)
  character:      { type: [Object, String], default: null }, // Character object or class name
  characterClass: { type: String, default: '' },           // Explicit class name
  player:         { type: Object, default: null }          // Player data from leaderboard/dossier
})

// ── Resolve Character Class ─────────────────────────────────────────
const resolvedClass = computed(() => {
  if (props.characterClass) {
    return normalizeCharacterClass(props.characterClass)
  }
  if (props.character) {
    if (typeof props.character === 'string') return normalizeCharacterClass(props.character)
    if (props.character.class_type) return normalizeCharacterClass(props.character.class_type)
    if (props.character.key_name) return normalizeCharacterClass(props.character.key_name)
    if (props.character.name) return normalizeCharacterClass(props.character.name)
  }
  if (props.player) {
    if (props.player.character_class) return normalizeCharacterClass(props.player.character_class)
    if (props.player.class_type) return normalizeCharacterClass(props.player.class_type)
    if (props.player.character_key) return normalizeCharacterClass(props.player.character_key)
    if (props.player.selected_character?.class_type) return normalizeCharacterClass(props.player.selected_character.class_type)
    if (props.player.character?.class_type) return normalizeCharacterClass(props.player.character.class_type)
    if (props.player.selected_character_id === 2) return 'MELEE'
    if (props.player.selected_character_id === 3) return 'RANGE'
    if (props.player.selected_character_id === 1) return 'MAGE'
    // Prevent leaking the viewer's own class to competitors or leaderboard entries
    return 'MAGE'
  }
  if (props.options?.character_class) return normalizeCharacterClass(props.options.character_class)
  if (props.options?.class_type) return normalizeCharacterClass(props.options.class_type)

  // Auth user fallback (only when rendering current logged-in user)
  return auth.characterClass || 'MAGE'
})

// ── Official Non-Changeable Profile Avatar Image ───────────────────
// IF MAGE  => NecroProfile.png
// IF MELEE => MeleeProfile.png
// IF RANGE => ArcherProfile.png
const currentAvatarSrc = computed(() => {
  return getProfileAvatarByClass(resolvedClass.value)
})

const avatarAlt = computed(() => {
  if (resolvedClass.value === 'MELEE') return 'Dual-Wielder (Melee) Avatar'
  if (resolvedClass.value === 'RANGE') return 'Archer (Range) Avatar'
  return 'Necromancer (Mage) Avatar'
})

const discClasses = computed(() => {
  const cls = resolvedClass.value.toLowerCase()
  return [`class-disc-${cls}`]
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
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
  z-index: 2;
  transition: all 0.3s ease;
}

.avatar-base-disc.class-disc-mage {
  background: radial-gradient(circle at 50% 35%, #25123a 0%, #0d0617 85%);
  border: 2px solid rgba(168, 85, 247, 0.55);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.7), 0 0 10px rgba(168, 85, 247, 0.25);
}

.avatar-base-disc.class-disc-melee {
  background: radial-gradient(circle at 50% 35%, #152238 0%, #080f1c 85%);
  border: 2px solid rgba(59, 130, 246, 0.55);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.7), 0 0 10px rgba(59, 130, 246, 0.25);
}

.avatar-base-disc.class-disc-range {
  background: radial-gradient(circle at 50% 35%, #122d20 0%, #06150e 85%);
  border: 2px solid rgba(34, 197, 94, 0.55);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.7), 0 0 10px rgba(34, 197, 94, 0.25);
}

/* ── Avatar Profile Image ────────────────────────────────────────── */
.avatar-profile-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  display: block;
  position: relative;
  z-index: 2;
  transform: scale(1.16) translateY(5%);
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.6));
}

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
