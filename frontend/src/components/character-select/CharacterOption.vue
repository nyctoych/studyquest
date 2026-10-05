<template>
  <button
    class="character-option-card"
    :class="[
      `class-${character.class_type.toLowerCase()}`,
      { 'is-selected': isSelected }
    ]"
    :aria-pressed="isSelected"
    :aria-label="`Select ${character.name}, class ${character.class_type}`"
    @click="$emit('select', character)"
  >
    <!-- Background Rune Glyph -->
    <div class="card-glyph-bg"></div>

    <!-- Active Indicator / Ribbon -->
    <div v-if="isSelected" class="selected-ribbon">
      <span class="ribbon-text font-pixel">ACTIVE</span>
    </div>

    <!-- Character Sprite Thumbnail / Miniature Battler -->
    <div class="option-sprite-frame">
      <img
        :src="spriteUrl"
        :alt="character.name"
        class="option-sprite pixel-art"
        @error="onImageError"
      />
      <div class="sprite-pedestal"></div>
    </div>

    <!-- Character Labeling -->
    <div class="option-meta">
      <div class="class-tag font-pixel">{{ character.class_type }}</div>
      <div class="option-name font-cinzel">{{ character.name }}</div>
      <div class="option-stats-preview font-pixel">
        <span class="hp-txt">❤️ {{ character.health }}</span>
        <span class="dmg-txt">⚔️ {{ character.damage }}</span>
      </div>
    </div>

    <!-- Active Selection Glow Ring -->
    <div class="glow-edge"></div>
  </button>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  character: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

defineEmits(['select'])

const imgFailed = ref(false)

const spriteUrl = computed(() => {
  if (imgFailed.value) {
    return '/assets/CHARACTERS/' + props.character.name + '.png'
  }
  return props.character.sprite_path || ''
})

function onImageError(e) {
  if (!imgFailed.value) {
    imgFailed.value = true
    console.warn(`[CharacterOption] Failed loading sprite at ${props.character.sprite_path}, trying fallback.`)
  }
}
</script>

<style scoped>
.character-option-card {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  padding: 1.1rem 0.9rem;
  background: linear-gradient(180deg, rgba(20, 16, 28, 0.92) 0%, rgba(12, 10, 18, 0.96) 100%);
  border: 1.5px solid rgba(255, 255, 255, 0.12);
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  outline: none;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6), inset 0 0 10px rgba(0, 0, 0, 0.5);
  overflow: hidden;
  text-align: center;
}

/* Hover State */
.character-option-card:hover {
  transform: translateY(-3px);
  border-color: rgba(216, 180, 254, 0.5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 12px rgba(168, 85, 247, 0.25);
}

/* Focus State */
.character-option-card:focus-visible {
  border-color: #fbbf24;
  box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.4), 0 8px 24px rgba(0, 0, 0, 0.8);
}

/* Selected State */
.character-option-card.is-selected {
  transform: translateY(-2px);
  background: linear-gradient(180deg, rgba(40, 24, 60, 0.95) 0%, rgba(18, 12, 30, 0.98) 100%);
  border-color: #c084fc;
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.45), inset 0 0 15px rgba(168, 85, 247, 0.2);
}

/* Class Variants */
.character-option-card.class-melee.is-selected {
  border-color: #f87171;
  box-shadow: 0 0 20px rgba(239, 68, 68, 0.45), inset 0 0 15px rgba(239, 68, 68, 0.2);
  background: linear-gradient(180deg, rgba(50, 18, 24, 0.95) 0%, rgba(20, 10, 14, 0.98) 100%);
}

.character-option-card.class-range.is-selected {
  border-color: #2dd4bf;
  box-shadow: 0 0 20px rgba(45, 212, 191, 0.45), inset 0 0 15px rgba(45, 212, 191, 0.2);
  background: linear-gradient(180deg, rgba(16, 42, 40, 0.95) 0%, rgba(10, 22, 22, 0.98) 100%);
}

/* Ribbon */
.selected-ribbon {
  position: absolute;
  top: 6px;
  right: 6px;
  background: #a855f7;
  color: #ffffff;
  padding: 0.18rem 0.45rem;
  border-radius: 2px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
  z-index: 5;
}

.class-melee .selected-ribbon { background: #dc2626; }
.class-range .selected-ribbon { background: #0d9488; }

.ribbon-text {
  font-size: 0.45rem;
  letter-spacing: 0.08em;
}

/* Sprite Frame */
.option-sprite-frame {
  position: relative;
  width: 90px;
  height: 90px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.6rem;
}

.option-sprite {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.7));
  transition: transform 0.3s ease;
  z-index: 2;
}

.character-option-card:hover .option-sprite {
  transform: scale(1.08);
}

.character-option-card.is-selected .option-sprite {
  transform: scale(1.12);
  filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.35)) drop-shadow(0 4px 8px rgba(0, 0, 0, 0.8));
}

.sprite-pedestal {
  position: absolute;
  bottom: 0px;
  width: 65px;
  height: 12px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.75) 0%, transparent 80%);
  border-radius: 50%;
  z-index: 1;
}

/* Meta */
.option-meta {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: center;
}

.class-tag {
  font-size: 0.55rem;
  letter-spacing: 0.12em;
  color: #c084fc;
}

.class-melee .class-tag { color: #f87171; }
.class-range .class-tag { color: #5eead4; }

.option-name {
  font-size: 1rem;
  font-weight: 700;
  color: #f5efe6;
  letter-spacing: 0.05em;
  line-height: 1.2;
}

.option-stats-preview {
  display: flex;
  gap: 0.55rem;
  font-size: 0.52rem;
  margin-top: 0.2rem;
  color: #a8a29e;
}

.hp-txt { color: #fca5a5; }
.dmg-txt { color: #fde047; }
</style>
