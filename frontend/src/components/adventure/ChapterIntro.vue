<template>
  <div class="chapter-intro-modal-backdrop" @click.self="$emit('close')">
    <div class="chapter-intro-card gothic-panel animate-scale-up">
      <!-- Act Banner -->
      <div class="intro-act-tag">
        <span class="act-rune">⚡</span>
        <span>{{ chapter?.act_title || 'ACT I — THE AWAKENING' }}</span>
        <span class="act-rune">⚡</span>
      </div>

      <div class="intro-titles">
        <span class="intro-chapter-num">CHAPTER {{ chapter?.display_order || chapter?.chapter_number || 1 }}</span>
        <h1 class="intro-story-title">{{ chapter?.chapter_story_title || chapter?.story_title || 'THE CRADLE OF THOUGHT' }}</h1>
        <h3 class="intro-domain-name">{{ chapter?.name || chapter?.title }}</h3>
      </div>

      <div class="gothic-sep gothic-sep-crimson" style="margin: 0.75rem 0 1.25rem;"></div>

      <!-- Boss & Mind Seal Preview Row -->
      <div class="intro-boss-preview-row">
        <div class="intro-boss-portrait-frame">
          <img
            :src="bossAsset"
            :alt="chapter?.boss_name || 'Corrupted Guardian'"
            class="intro-boss-img pixel-art animate-boss-float"
          />
          <div class="intro-boss-ring"></div>
        </div>

        <div class="intro-boss-lore">
          <span class="lore-tag">CORRUPTED REGIONAL GUARDIAN</span>
          <h4 class="lore-boss-name">{{ chapter?.boss_name || 'Guardian' }}</h4>
          <span class="lore-boss-title">{{ chapter?.boss_story_title || 'Corrupted Archon' }}</span>

          <div class="intro-relics-strip">
            <div class="relic-item seal-relic">
              <span class="relic-icon">🔮</span>
              <div class="relic-text">
                <span class="relic-label">MIND SEAL</span>
                <span class="relic-val">{{ chapter?.mind_seal_name || 'Seal of Knowledge' }}</span>
              </div>
            </div>

            <div class="relic-item shard-relic">
              <span class="relic-icon">💎</span>
              <div class="relic-text">
                <span class="relic-label">FRACTURE SHARD</span>
                <span class="relic-val">{{ chapter?.fracture_shard_name || 'Shard of Anomaly' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Setting Atmosphere & Story Intro -->
      <div class="intro-narrative-box">
        <h5 class="narrative-heading">ENVIRONMENT & DISTURBANCE</h5>
        <p class="narrative-setting">{{ chapter?.setting_description }}</p>
        <p class="narrative-body">{{ chapter?.story_intro }}</p>
      </div>

      <!-- Actions -->
      <div class="intro-actions">
        <button class="btn btn-gothic-stone btn-sm" @click="$emit('close')">
          CLOSE
        </button>
        <button class="btn btn-gothic-crimson btn-lg enter-btn" @click="$emit('enter')">
          <span>ENTER THE REGION</span>
          <span class="btn-arrow">→</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { resolveAsset, getBossForChapter } from '../../services/bossMapping'

const props = defineProps({
  chapter: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'enter'])

const bossAsset = computed(() => {
  if (props.chapter?.boss_portrait) {
    return resolveAsset(props.chapter.boss_portrait)
  }
  const order = props.chapter?.display_order || props.chapter?.chapter_number || 1
  const meta = getBossForChapter(order)
  return resolveAsset(meta?.happy || meta?.animated || '')
})
</script>

<style scoped>
.chapter-intro-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.chapter-intro-card {
  max-width: 680px;
  width: 100%;
  background: rgba(14, 10, 18, 0.98);
  border: 1px solid rgba(217, 119, 6, 0.45);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.9), inset 0 0 30px rgba(217, 119, 6, 0.08);
  border-radius: 6px;
  padding: 1.75rem;
  max-height: 90vh;
  overflow-y: auto;
}

.intro-act-tag {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #fbbf24;
  letter-spacing: 0.08em;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
  margin-bottom: 0.5rem;
}

.intro-titles {
  text-align: center;
}

.intro-chapter-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #f59e0b;
  letter-spacing: 0.1em;
}

.intro-story-title {
  font-family: 'Cinzel', serif;
  font-size: 1.6rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0.25rem 0;
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
}

.intro-domain-name {
  font-size: 0.85rem;
  color: #94a3b8;
  font-weight: normal;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.intro-boss-preview-row {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  background: rgba(22, 14, 28, 0.8);
  border: 1px solid rgba(147, 51, 234, 0.3);
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1.25rem;
}

.intro-boss-portrait-frame {
  position: relative;
  width: 72px;
  height: 72px;
  border: 2px solid rgba(147, 51, 234, 0.5);
  background: #09050d;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  flex-shrink: 0;
}

.intro-boss-img {
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.intro-boss-lore {
  flex: 1;
}

.lore-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #c084fc;
}

.lore-boss-name {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  color: #f8fafc;
  margin: 0.15rem 0;
}

.lore-boss-title {
  font-size: 0.75rem;
  color: #a855f7;
  font-style: italic;
  display: block;
  margin-bottom: 0.5rem;
}

.intro-relics-strip {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.relic-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(0, 0, 0, 0.4);
  padding: 0.3rem 0.6rem;
  border-radius: 3px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.relic-icon {
  font-size: 0.9rem;
}

.relic-text {
  display: flex;
  flex-direction: column;
}

.relic-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.45rem;
  color: #94a3b8;
}

.relic-val {
  font-size: 0.72rem;
  color: #e2e8f0;
  font-weight: 600;
}

.intro-narrative-box {
  background: rgba(10, 8, 14, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.narrative-heading {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #f59e0b;
  margin: 0 0 0.5rem;
}

.narrative-setting {
  font-size: 0.85rem;
  line-height: 1.5;
  color: #cbd5e1;
  font-style: italic;
  margin-bottom: 0.65rem;
}

.narrative-body {
  font-size: 0.88rem;
  line-height: 1.6;
  color: #e2e8f0;
  margin: 0;
}

.intro-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.enter-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
