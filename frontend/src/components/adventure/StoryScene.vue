<template>
  <div class="story-scene-container gothic-panel animate-scale-up">
    <!-- Chapter & Act Banner Header -->
    <div class="story-scene-header">
      <div class="scene-act-tag">
        <span class="act-rune">⚡</span>
        <span class="act-title">{{ actTitle || 'ACT I — THE AWAKENING' }}</span>
      </div>
      <div class="scene-phase-badge" :class="'phase-' + (phase || 'discovery').toLowerCase()">
        <span class="phase-icon">{{ phaseIcon }}</span>
        <span class="phase-text">{{ phaseLabel }}</span>
      </div>
    </div>

    <div class="scene-title-group">
      <span class="scene-chapter-context">{{ chapterTitle }}</span>
      <h2 class="scene-part-title">
        <span class="part-number-highlight">PART {{ partNum }}:</span> {{ storyTitle || partTitle }}
      </h2>
    </div>

    <div class="gothic-sep gothic-sep-crimson" style="margin: 0.75rem 0 1.25rem;"></div>

    <!-- Enemy / Aberration Manifestation Badge -->
    <div class="scene-enemy-strip">
      <div class="enemy-avatar-glyph">
        <span>💀</span>
      </div>
      <div class="enemy-meta">
        <span class="enemy-label">CORRUPTED MANIFESTATION</span>
        <h4 class="enemy-name">{{ enemyName || 'Echo of the Fracture' }}</h4>
        <p v-if="enemyFlavor" class="enemy-sub">{{ enemyFlavor }}</p>
      </div>
      <div class="enemy-hp-badge">
        <span class="hp-icon">🩸</span>
        <span class="hp-val">{{ enemyHp || 100 }} HP</span>
      </div>
    </div>

    <!-- Scene Narrative Text -->
    <div class="scene-narrative-box">
      <p class="scene-intro-text">{{ storyIntro }}</p>
    </div>

    <!-- Dialogue Box with NPC / Guardian -->
    <div v-if="dialogueText" class="scene-dialogue-wrap">
      <DialogueBox
        :speaker="dialogueSpeaker"
        :speaker-title="speakerTitle"
        :text="dialogueText"
        :speaker-icon="speakerIcon"
      />
    </div>

    <!-- Academic Connection & Fracture Clue -->
    <div v-if="academicConnection" class="scene-clue-card">
      <div class="clue-head">
        <span class="clue-icon">🔮</span>
        <strong class="clue-title">MINDKEEPER'S INSIGHT — ACADEMIC RELEVANCE</strong>
      </div>
      <p class="clue-text">{{ academicConnection }}</p>
    </div>

    <div v-if="bossName" class="scene-boss-foreshadowing">
      <span class="foreshadow-icon">👁️</span>
      <span class="foreshadow-text">The regional distortion builds toward <strong>{{ bossName }}</strong>. Defeat this challenge to weaken the Fracture!</span>
    </div>

    <!-- Action Buttons -->
    <div class="scene-actions">
      <button class="btn btn-gothic-stone btn-sm" @click="$emit('retreat')">
        ← MAP
      </button>
      <button class="btn btn-gothic-crimson btn-lg proceed-btn" @click="$emit('proceed')">
        <span>📖 CONSULT GRIMOIRE (LAYER 2)</span>
        <span class="btn-arrow">→</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import DialogueBox from './DialogueBox.vue'

const props = defineProps({
  actTitle: { type: String, default: '' },
  chapterTitle: { type: String, default: '' },
  partNum: { type: Number, required: true },
  partTitle: { type: String, default: '' },
  storyTitle: { type: String, default: '' },
  phase: { type: String, default: 'discovery' },
  storyIntro: { type: String, required: true },
  dialogueSpeaker: { type: String, default: 'Archivist' },
  speakerTitle: { type: String, default: '' },
  dialogueText: { type: String, default: '' },
  academicConnection: { type: String, default: '' },
  enemyName: { type: String, default: '' },
  enemyFlavor: { type: String, default: '' },
  enemyHp: { type: Number, default: 100 },
  bossName: { type: String, default: '' },
  speakerIcon: { type: String, default: '🔮' },
})

defineEmits(['proceed', 'retreat'])

const phaseIcon = computed(() => {
  switch (props.phase?.toLowerCase()) {
    case 'discovery': return '🔍'
    case 'investigation': return '📜'
    case 'escalation': return '⚡'
    case 'revelation': return '👁️'
    case 'trial': return '👑'
    default: return '🔮'
  }
})

const phaseLabel = computed(() => {
  switch (props.phase?.toLowerCase()) {
    case 'discovery': return 'PHASE: DISCOVERY'
    case 'investigation': return 'PHASE: INVESTIGATION'
    case 'escalation': return 'PHASE: ESCALATION'
    case 'revelation': return 'PHASE: BOSS REVELATION'
    case 'trial': return 'PHASE: FINAL TRIAL'
    default: return 'PHASE: CHAPTER EXPLORATION'
  }
})
</script>

<style scoped>
.story-scene-container {
  max-width: 720px;
  margin: 0 auto;
  padding: 1.5rem;
  background: rgba(14, 10, 18, 0.96);
  border: 1px solid rgba(220, 38, 38, 0.35);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8), inset 0 0 30px rgba(220, 38, 38, 0.08);
  border-radius: 6px;
}

.story-scene-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.scene-act-tag {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.05em;
  text-shadow: 0 0 6px rgba(251, 191, 36, 0.4);
}

.scene-phase-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.6rem;
  border-radius: 3px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  text-transform: uppercase;
}

.phase-discovery {
  background: rgba(2, 132, 199, 0.2);
  border: 1px solid rgba(56, 189, 248, 0.4);
  color: #38bdf8;
}

.phase-investigation {
  background: rgba(217, 119, 6, 0.2);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #fbbf24;
}

.phase-escalation {
  background: rgba(220, 38, 38, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.4);
  color: #f87171;
}

.phase-revelation {
  background: rgba(147, 51, 234, 0.2);
  border: 1px solid rgba(192, 132, 252, 0.4);
  color: #c084fc;
}

.phase-trial {
  background: rgba(234, 179, 8, 0.25);
  border: 1px solid rgba(253, 224, 71, 0.5);
  color: #fef08a;
}

.scene-title-group {
  margin-bottom: 0.5rem;
}

.scene-chapter-context {
  display: block;
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.2rem;
}

.scene-part-title {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0;
  line-height: 1.3;
}

.part-number-highlight {
  color: #f59e0b;
}

.scene-enemy-strip {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(26, 14, 20, 0.85);
  border: 1px solid rgba(220, 38, 38, 0.25);
  padding: 0.6rem 0.85rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.enemy-avatar-glyph {
  font-size: 1.4rem;
  flex-shrink: 0;
}

.enemy-meta {
  flex: 1;
}

.enemy-label {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #ef4444;
  letter-spacing: 0.05em;
}

.enemy-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  color: #fca5a5;
  margin: 0.1rem 0 0;
}

.enemy-sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0.1rem 0 0;
  font-style: italic;
}

.enemy-hp-badge {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #f87171;
  background: rgba(127, 29, 29, 0.3);
  padding: 0.3rem 0.6rem;
  border-radius: 3px;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.scene-intro-text {
  font-size: 0.92rem;
  line-height: 1.6;
  color: #cbd5e1;
  margin-bottom: 0.75rem;
}

.scene-clue-card {
  background: linear-gradient(135deg, rgba(30, 27, 75, 0.5), rgba(15, 23, 42, 0.7));
  border: 1px solid rgba(99, 102, 241, 0.35);
  border-radius: 4px;
  padding: 0.85rem;
  margin: 0.85rem 0;
}

.clue-head {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 0.35rem;
}

.clue-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #818cf8;
  letter-spacing: 0.04em;
}

.clue-text {
  font-size: 0.82rem;
  line-height: 1.5;
  color: #e2e8f0;
  margin: 0;
}

.scene-boss-foreshadowing {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.78rem;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.08);
  border-left: 3px solid #f59e0b;
  padding: 0.5rem 0.75rem;
  margin: 0.75rem 0 1rem;
}

.scene-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.25rem;
  gap: 1rem;
}

.proceed-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
