<template>
  <div class="ranking-selector-root">
    <!-- Outer Gothic Slate Container -->
    <div class="selector-capsule">
      <div class="selector-corner tl"></div>
      <div class="selector-corner tr"></div>
      <div class="selector-corner bl"></div>
      <div class="selector-corner br"></div>

      <!-- Mode Buttons -->
      <div class="tabs-group" role="tablist" aria-label="Ranking Categories">
        <button
          type="button"
          role="tab"
          :aria-selected="modelValue === 'rating'"
          class="mode-btn mode-btn-rating"
          :class="{ active: modelValue === 'rating' }"
          @click="$emit('update:modelValue', 'rating')"
        >
          <span class="mode-icon-wrap">
            <PixelEmoji emoji="lightning" size="sm" />
          </span>
          <span class="mode-label-group">
            <span class="mode-title">BATTLE RATING</span>
            <span class="mode-subtitle">ELO / PvP Mastery</span>
          </span>
          <span v-if="modelValue === 'rating'" class="mode-active-gem">◆</span>
        </button>

        <button
          type="button"
          role="tab"
          :aria-selected="modelValue === 'level'"
          class="mode-btn mode-btn-level"
          :class="{ active: modelValue === 'level' }"
          @click="$emit('update:modelValue', 'level')"
        >
          <span class="mode-icon-wrap">
            <PixelEmoji emoji="star" size="sm" />
          </span>
          <span class="mode-label-group">
            <span class="mode-title">SCHOLAR LEVEL</span>
            <span class="mode-subtitle">Mastery XP & Lore</span>
          </span>
          <span v-if="modelValue === 'level'" class="mode-active-gem">◆</span>
        </button>
      </div>

      <!-- Quick Filter / Search input -->
      <div class="search-wrap">
        <span class="search-icon"><PixelEmoji emoji="target" size="xs" /></span>
        <input
          :value="searchQuery"
          @input="$emit('update:searchQuery', $event.target.value)"
          type="text"
          placeholder="Filter champion by name…"
          class="search-input"
          aria-label="Filter champions"
        />
        <button
          v-if="searchQuery"
          @click="$emit('update:searchQuery', '')"
          class="clear-search-btn"
          aria-label="Clear filter"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Lore explanation ribbon below selector -->
    <transition name="fade-fast" mode="out-in">
      <div :key="modelValue" class="ranking-explanation-bar">
        <span class="explanation-icon">✦</span>
        <span v-if="modelValue === 'rating'" class="explanation-text">
          <strong>Battle Rating Ledger:</strong> Ranked by competitive psychiatric arena duel performance, victories, and ELO rating.
        </span>
        <span v-else class="explanation-text">
          <strong>Scholar Level Chronicle:</strong> Ranked by accumulated study curriculum XP, cognitive mastery, and academic perseverance.
        </span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import PixelEmoji from '../ui/PixelEmoji.vue'

defineProps({
  modelValue: {
    type: String,
    default: 'rating'
  },
  searchQuery: {
    type: String,
    default: ''
  }
})

defineEmits(['update:modelValue', 'update:searchQuery'])
</script>

<style scoped>
.ranking-selector-root {
  margin-bottom: 2.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

/* Outer frame */
.selector-capsule {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  width: 100%;
  max-width: 920px;
  background: rgba(14, 15, 22, 0.85);
  border: 1px solid rgba(181, 148, 16, 0.35);
  padding: 0.5rem 0.75rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.7), inset 0 0 15px rgba(0, 0, 0, 0.5);
}

.selector-corner {
  position: absolute;
  width: 6px;
  height: 6px;
  background: #b59410;
}
.selector-corner.tl { top: -2px; left: -2px; }
.selector-corner.tr { top: -2px; right: -2px; }
.selector-corner.bl { bottom: -2px; left: -2px; }
.selector-corner.br { bottom: -2px; right: -2px; }

/* Tabs group */
.tabs-group {
  display: flex;
  gap: 0.5rem;
  flex: 1;
}

.mode-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.65rem 1.4rem;
  background: rgba(20, 22, 32, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #9c9182;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  overflow: hidden;
}

.mode-btn:hover:not(.active) {
  background: rgba(40, 28, 48, 0.5);
  border-color: rgba(230, 200, 69, 0.3);
  color: #f0e6d8;
  transform: translateY(-1px);
}

.mode-btn.active {
  background: linear-gradient(135deg, #4a1515 0%, #200808 100%);
  border-color: #dc2626;
  color: #fff;
  box-shadow: 0 0 16px rgba(220, 38, 38, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.mode-btn-level.active {
  background: linear-gradient(135deg, #442a0a 0%, #1e1304 100%);
  border-color: #e6c845;
  box-shadow: 0 0 16px rgba(230, 200, 69, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.15);
}

.mode-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 6px currentColor);
}

.mode-label-group {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.mode-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  font-weight: 700;
}

.mode-subtitle {
  font-size: 0.75rem;
  opacity: 0.75;
  font-family: var(--font-gothic, serif);
}

.mode-active-gem {
  color: #e6c845;
  font-size: 0.7rem;
  margin-left: auto;
  animation: pulseRune 2s infinite ease-in-out;
}

/* Search bar */
.search-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #08090d;
  border: 1px solid rgba(181, 148, 16, 0.25);
  padding: 0.45rem 0.85rem;
  min-width: 240px;
}

.search-input {
  background: transparent;
  border: none;
  color: #f7f3eb;
  font-family: var(--font-gothic, serif);
  font-size: 0.95rem;
  outline: none;
  width: 100%;
}

.search-input::placeholder {
  color: #7a7062;
  font-style: italic;
}

.clear-search-btn {
  background: transparent;
  border: none;
  color: #8b8070;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 0 0.2rem;
}
.clear-search-btn:hover {
  color: #ff6b6b;
}

/* Explanation banner */
.ranking-explanation-bar {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.45rem 1.25rem;
  background: rgba(10, 11, 16, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  font-size: 0.88rem;
  color: #c9bea9;
}

.explanation-icon {
  color: #e6c845;
  font-size: 0.85rem;
}

.explanation-text strong {
  color: #f0e6d8;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  letter-spacing: 0.04em;
  margin-right: 0.35rem;
}

.fade-fast-enter-active,
.fade-fast-leave-active {
  transition: opacity 0.18s ease;
}
.fade-fast-enter-from,
.fade-fast-leave-to {
  opacity: 0;
}

@media (max-width: 860px) {
  .selector-capsule {
    flex-direction: column;
    align-items: stretch;
  }
  .tabs-group {
    flex-direction: column;
  }
  .search-wrap {
    min-width: 100%;
  }
}
</style>
