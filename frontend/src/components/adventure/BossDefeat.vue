<template>
  <div class="boss-defeat-card gothic-panel animate-scale-up">
    <!-- Purification Crest -->
    <div class="defeat-crest">
      <div class="crest-glyph">✨</div>
      <span class="crest-title">REGION PURIFIED — GUARDIAN RESTORED</span>
      <h1 class="crest-boss-name">{{ bossName }}</h1>
    </div>

    <!-- Restored Guardian Portrait (Happy / Peaceful) -->
    <div class="restored-boss-frame">
      <img
        :src="bossImg"
        :alt="bossName"
        class="restored-boss-img pixel-art animate-boss-float"
      />
      <div class="restored-halo"></div>
    </div>

    <!-- Boss Gratitude & Defeat Dialogue -->
    <div v-if="dialogueDefeat" class="restoration-dialogue-wrap">
      <DialogueBox
        :speaker="bossName + ' (Restored)'"
        speaker-title="Guardian of the Mindrealm"
        :text="dialogueDefeat"
        speaker-icon="🕊️"
      />
    </div>

    <!-- Narrative Region Restoration -->
    <div v-if="restorationText" class="region-restoration-box">
      <h5 class="restoration-head">MINDREALM RESTORATION</h5>
      <p class="restoration-text">{{ restorationText }}</p>
    </div>

    <!-- Mind Seal & Fracture Shard Claim Area -->
    <div class="relics-claimed-section">
      <div v-if="mindSealRecovered" class="claimed-card seal-claimed animate-scale-up">
        <span class="claimed-icon">🔮</span>
        <div class="claimed-info">
          <span class="claimed-tag">MIND SEAL RESTORED!</span>
          <strong class="claimed-name">{{ mindSealRecovered }}</strong>
          <span class="claimed-sub">Preserved in your ancient Mindkeeper codex</span>
        </div>
      </div>

      <div v-if="fractureShardRecovered" class="claimed-card shard-claimed animate-scale-up">
        <span class="claimed-icon">💎</span>
        <div class="claimed-info">
          <span class="claimed-tag">FRACTURE SHARD ACQUIRED!</span>
          <strong class="claimed-name">{{ fractureShardRecovered }}</strong>
          <span class="claimed-sub">Clue toward the origin of the anomaly</span>
        </div>
      </div>
    </div>

    <!-- Rewards Row (XP + Coins) -->
    <div v-if="isFirstCompletion" class="victory-rewards-row">
      <div class="v-reward-pill xp-pill">
        <span class="v-icon">⭐</span>
        <span>+{{ rewards?.xp || 500 }} XP</span>
      </div>
      <div class="v-reward-pill coin-pill">
        <span class="v-icon">🪙</span>
        <span>+{{ rewards?.coins || 300 }} COINS</span>
      </div>
    </div>
    <div v-else class="replay-notice">
      <span class="replay-tag">REPLAY VICTORY</span>
      <span class="replay-desc">Regional balance reaffirmed. Rewards granted on initial conquest.</span>
    </div>

    <!-- Next Chapter / Black Archive Transition Button -->
    <div class="defeat-actions">
      <button v-if="blackArchiveUnlocked" class="btn btn-gothic-crimson btn-lg black-archive-btn" @click="$emit('openBlackArchive')">
        <span>🗝️ ENTER THE BLACK ARCHIVE</span>
        <span class="btn-arrow">→</span>
      </button>

      <button v-else-if="nextChapterUnlocked" class="btn btn-gothic-crimson btn-lg next-ch-btn" @click="$emit('nextChapter')">
        <span>CONTINUE TO CHAPTER {{ nextChapterOrder }} →</span>
      </button>

      <button v-else class="btn btn-gothic-stone btn-lg" @click="$emit('returnMap')">
        🗺️ RETURN TO MAP
      </button>
    </div>
  </div>
</template>

<script setup>
import DialogueBox from './DialogueBox.vue'

defineProps({
  bossName: { type: String, required: true },
  bossImg: { type: String, required: true },
  dialogueDefeat: { type: String, default: '' },
  restorationText: { type: String, default: '' },
  mindSealRecovered: { type: String, default: '' },
  fractureShardRecovered: { type: String, default: '' },
  isFirstCompletion: { type: Boolean, default: true },
  rewards: { type: Object, default: () => ({ xp: 500, coins: 300 }) },
  nextChapterUnlocked: { type: Boolean, default: false },
  nextChapterOrder: { type: [Number, String], default: 2 },
  nextChapterName: { type: String, default: '' },
  blackArchiveUnlocked: { type: Boolean, default: false },
})

defineEmits(['nextChapter', 'openBlackArchive', 'returnMap'])
</script>

<style scoped>
.boss-defeat-card {
  max-width: 680px;
  margin: 0 auto;
  padding: 1.75rem;
  background: rgba(12, 10, 18, 0.98);
  border: 1px solid rgba(245, 158, 11, 0.45);
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.9), inset 0 0 30px rgba(245, 158, 11, 0.1);
  border-radius: 6px;
  text-align: center;
}

.defeat-crest {
  margin-bottom: 1rem;
}

.crest-glyph {
  font-size: 2rem;
  margin-bottom: 0.25rem;
}

.crest-title {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.08em;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.5);
}

.crest-boss-name {
  font-family: 'Cinzel', serif;
  font-size: 1.75rem;
  color: #f8fafc;
  margin: 0.25rem 0 0;
}

.restored-boss-frame {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0.75rem auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.restored-boss-img {
  width: 90px;
  height: 90px;
  object-fit: contain;
  z-index: 2;
  filter: drop-shadow(0 0 14px rgba(245, 158, 11, 0.6));
}

.restored-halo {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px dashed rgba(245, 158, 11, 0.5);
  animation: spin 20s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.restoration-dialogue-wrap {
  text-align: left;
  margin: 1rem 0;
}

.region-restoration-box {
  background: rgba(20, 16, 26, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  padding: 0.85rem;
  margin: 0.85rem 0 1.25rem;
  text-align: left;
}

.restoration-head {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #10b981;
  letter-spacing: 0.05em;
  margin: 0 0 0.35rem;
}

.restoration-text {
  font-size: 0.85rem;
  line-height: 1.55;
  color: #cbd5e1;
  font-style: italic;
  margin: 0;
}

.relics-claimed-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1rem 0;
}

.claimed-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: rgba(18, 14, 28, 0.9);
  border-radius: 4px;
  padding: 0.75rem 1rem;
  text-align: left;
}

.seal-claimed {
  border: 1px solid rgba(168, 85, 247, 0.45);
}

.shard-claimed {
  border: 1px solid rgba(56, 189, 248, 0.45);
}

.claimed-icon {
  font-size: 1.6rem;
  flex-shrink: 0;
}

.claimed-info {
  display: flex;
  flex-direction: column;
}

.claimed-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #fbbf24;
}

.claimed-name {
  font-size: 0.95rem;
  color: #f8fafc;
  margin: 0.1rem 0;
}

.claimed-sub {
  font-size: 0.72rem;
  color: #94a3b8;
}

.victory-rewards-row {
  display: flex;
  justify-content: center;
  gap: 1.25rem;
  margin: 1.25rem 0;
}

.v-reward-pill {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  padding: 0.4rem 0.85rem;
  border-radius: 4px;
}

.xp-pill {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.4);
  color: #fbbf24;
}

.coin-pill {
  background: rgba(234, 179, 8, 0.15);
  border: 1px solid rgba(234, 179, 8, 0.4);
  color: #fef08a;
}

.replay-notice {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0.75rem 0;
}

.defeat-actions {
  margin-top: 1.5rem;
}

.black-archive-btn,
.next-ch-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
