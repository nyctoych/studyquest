<template>
  <div class="character-story-panel gothic-panel">
    <!-- Header: Title & Class badge -->
    <div class="story-header">
      <div class="class-pill" :class="`class-${character.class_type.toLowerCase()}`">
        <span class="pill-rune">✦</span>
        <span>{{ character.class_type }}</span>
      </div>
      <h2 class="character-lore-title">{{ character.title }}</h2>
      <p class="character-lore-subtitle">{{ character.name }}</p>
    </div>

    <!-- Opening Quote / Persona Inscription -->
    <div class="story-quote-card" :class="`theme-${character.class_type.toLowerCase()}`">
      <div class="quote-mark">“</div>
      <p class="quote-text">{{ character.opening_message }}</p>
    </div>

    <!-- Story Navigation Tabs -->
    <div class="story-tabs">
      <button
        class="story-tab-btn"
        :class="{ active: activeTab === 'intro' }"
        @click="activeTab = 'intro'"
      >
        <span>OVERVIEW</span>
      </button>
      <button
        class="story-tab-btn"
        :class="{ active: activeTab === 'lore' }"
        @click="activeTab = 'lore'"
      >
        <span>MINDREALM LORE</span>
      </button>
      <button
        class="story-tab-btn"
        :class="{ active: activeTab === 'motivation' }"
        @click="activeTab = 'motivation'"
      >
        <span>MOTIVATION</span>
      </button>
    </div>

    <!-- Tab Content Area -->
    <div class="story-body-wrap">
      <transition name="fade-slide" mode="out-in">
        <!-- Overview / Intro -->
        <div v-if="activeTab === 'intro'" key="intro" class="story-content-section">
          <h4 class="section-label">Role in the Fracture</h4>
          <p class="story-paragraph highlight-text">{{ character.short_intro }}</p>
          <p class="story-paragraph secondary-text">{{ character.description }}</p>
        </div>

        <!-- Lore -->
        <div v-else-if="activeTab === 'lore'" key="lore" class="story-content-section">
          <h4 class="section-label">Origin & Cognitive Roots</h4>
          <p class="story-paragraph lore-narrative">{{ character.lore }}</p>
        </div>

        <!-- Motivation -->
        <div v-else-if="activeTab === 'motivation'" key="motivation" class="story-content-section">
          <h4 class="section-label">Drive & Resolution</h4>
          <p class="story-paragraph motivation-narrative">{{ character.motivation }}</p>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  character: {
    type: Object,
    required: true
  }
})

const activeTab = ref('intro')

// Reset to intro whenever character changes
watch(() => props.character?.id, () => {
  activeTab.value = 'intro'
})
</script>

<style scoped>
.character-story-panel {
  background: rgba(10, 8, 16, 0.9);
  border: 1px solid rgba(168, 85, 247, 0.4);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(168, 85, 247, 0.12);
  border-radius: 4px;
  padding: 1.25rem 1.35rem;
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.story-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 0.65rem;
}

.class-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.2rem 0.6rem;
  border-radius: 2px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  letter-spacing: 0.08em;
  margin-bottom: 0.45rem;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.class-mage {
  background: rgba(88, 28, 135, 0.4);
  color: #d8b4fe;
  border-color: rgba(168, 85, 247, 0.6);
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.25);
}

.class-melee {
  background: rgba(153, 27, 27, 0.4);
  color: #fca5a5;
  border-color: rgba(239, 68, 68, 0.6);
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.25);
}

.class-range {
  background: rgba(19, 78, 74, 0.4);
  color: #5eead4;
  border-color: rgba(45, 212, 191, 0.6);
  box-shadow: 0 0 10px rgba(45, 212, 191, 0.25);
}

.pill-rune {
  font-size: 0.65rem;
}

.character-lore-title {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 800;
  color: #f5efe6;
  letter-spacing: 0.04em;
  line-height: 1.25;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.character-lore-subtitle {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #a8a29e;
  letter-spacing: 0.08em;
}

/* Quote Card */
.story-quote-card {
  position: relative;
  padding: 0.65rem 0.85rem 0.65rem 1.6rem;
  background: rgba(18, 14, 28, 0.8);
  border-left: 3px solid #c084fc;
  border-radius: 2px;
}

.story-quote-card.theme-melee { border-left-color: #ef4444; }
.story-quote-card.theme-range { border-left-color: #2dd4bf; }

.quote-mark {
  position: absolute;
  top: -6px;
  left: 6px;
  font-family: 'Cinzel', serif;
  font-size: 1.6rem;
  color: rgba(255, 255, 255, 0.2);
  line-height: 1;
}

.quote-text {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-style: italic;
  color: #e2d9cc;
  line-height: 1.45;
  letter-spacing: 0.02em;
}

/* Tabs */
.story-tabs {
  display: flex;
  gap: 0.35rem;
  background: rgba(15, 11, 23, 0.95);
  padding: 0.25rem;
  border-radius: 3px;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.story-tab-btn {
  flex: 1;
  padding: 0.4rem 0.5rem;
  background: transparent;
  border: 1px solid transparent;
  color: #8b8070;
  font-family: 'Cinzel', serif;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  cursor: pointer;
  transition: all 0.2s ease;
  border-radius: 2px;
  text-align: center;
}

.story-tab-btn:hover {
  color: #f5efe6;
  background: rgba(255, 255, 255, 0.05);
}

.story-tab-btn.active {
  background: linear-gradient(180deg, rgba(88, 28, 135, 0.8) 0%, rgba(59, 7, 100, 0.9) 100%);
  border-color: rgba(192, 132, 252, 0.5);
  color: #ffffff;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
}

/* Body / Narrative */
.story-body-wrap {
  min-height: 130px;
}

.story-content-section {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.section-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.52rem;
  color: #c084fc;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.story-paragraph {
  font-family: 'Inter', -apple-system, sans-serif;
  font-size: 0.84rem;
  line-height: 1.55;
  color: #d6d3d1;
}

.highlight-text {
  color: #f5efe6;
  font-weight: 500;
}

.secondary-text {
  color: #a8a29e;
  font-size: 0.8rem;
}

.lore-narrative, .motivation-narrative {
  color: #e7e5e4;
  text-align: justify;
}

/* Transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.22s ease-out;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
