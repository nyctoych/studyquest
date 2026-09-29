<template>
  <div class="boss-intro-scene gothic-panel animate-scale-up">
    <!-- Chapter Context & Act -->
    <div class="boss-act-strip">
      <span class="act-rune">⚡</span>
      <span>{{ actTitle || 'ACT CLIMAX' }} — {{ chapterStoryTitle || 'REGIONAL GUARDIAN' }}</span>
      <span class="act-rune">⚡</span>
    </div>

    <!-- Boss Altar Portrait -->
    <div class="boss-altar-area">
      <div class="boss-portrait-altar">
        <img
          :src="bossImg"
          :alt="bossName"
          class="boss-portrait-art pixel-art animate-boss-float"
        />
        <div class="boss-aura-ring"></div>
      </div>
    </div>

    <!-- Boss Identity & Lore -->
    <div class="boss-identity-card">
      <span class="boss-domain-badge">CHAPTER CLIMAX MASTERY TRIAL</span>
      <h1 class="boss-title-name">{{ bossName }}</h1>
      <span class="boss-guardian-title">{{ bossTitle || 'Corrupted Guardian of the Realm' }}</span>

      <!-- Introductory Dialogue from the Corrupted Guardian -->
      <div v-if="dialogueIntro" class="boss-dialogue-card">
        <DialogueBox
          :speaker="bossName"
          :speaker-title="bossTitle"
          :text="dialogueIntro"
          speaker-icon="💀"
        />
      </div>

      <!-- Relics at Stake -->
      <div class="boss-stakes-row">
        <div class="stake-card seal-stake">
          <span class="stake-icon">🔮</span>
          <div class="stake-info">
            <span class="stake-label">RECOVERABLE MIND SEAL</span>
            <strong class="stake-val">{{ mindSealName || 'Mind Seal of the Realm' }}</strong>
          </div>
        </div>

        <div class="stake-card shard-stake">
          <span class="stake-icon">💎</span>
          <div class="stake-info">
            <span class="stake-label">FRACTURE SHARD</span>
            <strong class="stake-val">{{ fractureShardName || 'Fracture Shard' }}</strong>
          </div>
        </div>
      </div>

      <!-- Boss Battle Stats & Rewards -->
      <div class="boss-stats-rewards-row">
        <div class="bs-stat-pill">
          <span class="bs-icon">🩸</span>
          <span>{{ maxHp || 300 }} HP</span>
        </div>
        <div class="bs-stat-pill">
          <span class="bs-icon">⭐</span>
          <span>+{{ xpReward || 500 }} XP</span>
        </div>
        <div class="bs-stat-pill">
          <span class="bs-icon">🪙</span>
          <span>+{{ coinReward || 300 }} COINS</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="boss-intro-actions">
        <button class="btn btn-gothic-stone btn-sm" @click="$emit('retreat')">
          ← CHAPTER SANCTUM
        </button>
        <button class="btn btn-gothic-crimson btn-lg engage-btn" @click="$emit('start')">
          <span>⚔️ ENGAGE GUARDIAN</span>
          <span class="btn-arrow">→</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import DialogueBox from './DialogueBox.vue'

defineProps({
  actTitle: { type: String, default: '' },
  chapterStoryTitle: { type: String, default: '' },
  bossName: { type: String, required: true },
  bossTitle: { type: String, default: '' },
  bossImg: { type: String, required: true },
  dialogueIntro: { type: String, default: '' },
  mindSealName: { type: String, default: '' },
  fractureShardName: { type: String, default: '' },
  maxHp: { type: Number, default: 300 },
  xpReward: { type: Number, default: 500 },
  coinReward: { type: Number, default: 300 },
})

defineEmits(['retreat', 'start'])
</script>

<style scoped>
.boss-intro-scene {
  max-width: 680px;
  margin: 0 auto;
  padding: 1.5rem;
  background: rgba(14, 10, 20, 0.98);
  border: 1px solid rgba(220, 38, 38, 0.45);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.85), inset 0 0 30px rgba(220, 38, 38, 0.1);
  border-radius: 6px;
  text-align: center;
}

.boss-act-strip {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.08em;
  margin-bottom: 1.25rem;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.4);
}

.boss-altar-area {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.boss-portrait-altar {
  position: relative;
  width: 140px;
  height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.boss-portrait-art {
  width: 110px;
  height: 110px;
  object-fit: contain;
  z-index: 2;
  filter: drop-shadow(0 0 16px rgba(220, 38, 38, 0.6));
}

.boss-aura-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px dashed rgba(220, 38, 38, 0.5);
  animation: spin 18s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.boss-domain-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #ef4444;
  letter-spacing: 0.1em;
  display: block;
  margin-bottom: 0.35rem;
}

.boss-title-name {
  font-family: 'Cinzel', serif;
  font-size: 1.85rem;
  font-weight: 700;
  color: #fca5a5;
  margin: 0.2rem 0;
  text-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
}

.boss-guardian-title {
  font-size: 0.85rem;
  color: #94a3b8;
  font-style: italic;
  display: block;
  margin-bottom: 1rem;
}

.boss-dialogue-card {
  text-align: left;
  margin: 1rem 0;
}

.boss-stakes-row {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
  margin: 1rem 0;
}

.stake-card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(20, 14, 28, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.45rem 0.85rem;
  border-radius: 4px;
}

.seal-stake {
  border-color: rgba(147, 51, 234, 0.4);
}

.shard-stake {
  border-color: rgba(56, 189, 248, 0.4);
}

.stake-icon {
  font-size: 1.1rem;
}

.stake-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.stake-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.45rem;
  color: #94a3b8;
}

.stake-val {
  font-size: 0.8rem;
  color: #f8fafc;
}

.boss-stats-rewards-row {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin: 1.25rem 0;
}

.bs-stat-pill {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(0, 0, 0, 0.5);
  padding: 0.35rem 0.75rem;
  border-radius: 3px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.boss-intro-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.engage-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
