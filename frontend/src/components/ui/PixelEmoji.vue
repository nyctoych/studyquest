<template>
  <span
    class="pixel-emoji-container"
    :class="[customClass, { 'is-decorative': isDecorative }]"
    :style="computedStyle"
    :role="isDecorative ? 'presentation' : 'img'"
    :aria-hidden="isDecorative ? 'true' : undefined"
    :aria-label="ariaLabelText"
  >
    <img
      v-if="resolvedSrc && !hasError"
      :src="resolvedSrc"
      :alt="ariaLabelText"
      class="pixel-emoji-img"
      :style="imgStyle"
      draggable="false"
      @error="handleImgError"
    />
    <span v-else class="pixel-emoji-fallback">✦</span>
  </span>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { getEmojiDef, EMOJI_SIZES } from '../../utils/emojiMap'

const props = defineProps({
  emoji: {
    type: String,
    required: true
  },
  size: {
    type: [Number, String],
    default: 'md'
  },
  label: {
    type: String,
    default: ''
  },
  ariaLabel: {
    type: String,
    default: ''
  },
  decorative: {
    type: Boolean,
    default: null
  },
  class: {
    type: [String, Object, Array],
    default: ''
  }
})

const def = computed(() => getEmojiDef(props.emoji))

const hasError = ref(false)
const fallbackSrc = ref('')

watch(() => props.emoji, () => {
  hasError.value = false
  fallbackSrc.value = ''
})

const resolvedSrc = computed(() => {
  if (fallbackSrc.value) return fallbackSrc.value
  return def.value?.path || ''
})

function handleImgError(e) {
  // Try alternative path before completely failing
  if (!fallbackSrc.value && def.value?.file) {
    const current = resolvedSrc.value
    if (current.startsWith('/assets/')) {
      fallbackSrc.value = './assets/dotto/' + def.value.file + '?v=2'
      return
    } else if (current.includes('/studyquest/assets/')) {
      fallbackSrc.value = '/assets/dotto/' + def.value.file + '?v=2'
      return
    } else {
      fallbackSrc.value = '/assets/dotto/' + def.value.file + '?v=2'
      return
    }
  }
  hasError.value = true
}

// Calculate dimension in px
const pixelDimension = computed(() => {
  if (typeof props.size === 'number') {
    return props.size
  }
  const sizeKey = String(props.size).toLowerCase()
  if (EMOJI_SIZES[sizeKey]) {
    return EMOJI_SIZES[sizeKey]
  }
  const parsed = parseInt(props.size, 10)
  return isNaN(parsed) ? EMOJI_SIZES.md : parsed
})

// Accessibility logic
const ariaLabelText = computed(() => {
  return props.ariaLabel || props.label || def.value?.label || ''
})

const isDecorative = computed(() => {
  if (props.decorative !== null) return props.decorative
  return !props.label && !props.ariaLabel
})

const customClass = computed(() => props.class)

const computedStyle = computed(() => {
  const s = pixelDimension.value
  return {
    width: `${s}px`,
    height: `${s}px`,
    minWidth: `${s}px`,
    minHeight: `${s}px`
  }
})

const imgStyle = computed(() => {
  return {
    width: '100%',
    height: '100%'
  }
})
</script>

<style scoped>
.pixel-emoji-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  vertical-align: middle;
  line-height: 1;
  position: relative;
  user-select: none;
  flex-shrink: 0;
}

.pixel-emoji-img {
  display: block;
  object-fit: contain;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
}

.pixel-emoji-fallback {
  color: var(--color-gold-ancient, #b59410);
  font-size: 0.85em;
}
</style>
