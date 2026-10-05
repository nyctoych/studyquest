<template>
  <div class="campaign-completion-overlay animate-fade-in" @click.self="$emit('close')">
    <div class="campaign-completion-card gothic-panel animate-scale-up">
      <!-- Ornate Gothic Framing Header -->
      <div class="completion-header">
        <div class="crown-emblem">
          <PixelEmoji emoji="crown" size="lg" />
        </div>
        <div class="completion-titles">
          <span class="sq-kicker font-pixel">STUDYQUEST — THE APEX TRIUMPH</span>
          <h1 class="sq-main-title font-pixel">ADVENTURE COMPLETE</h1>
          <h2 class="sq-subtitle">THE MINDREALM HAS BEEN RESTORED</h2>
        </div>
      </div>

      <!-- Celestial Harmony Banner (The Mindrealm Restored) -->
      <div class="harmony-showcase">
        <div class="creator-peaceful-frame">
          <img
            :src="creatorSprite"
            alt="The Creator Restored"
            class="creator-restored-img pixel-art animate-boss-float"
          />
          <div class="celestial-halo"></div>
        </div>

        <div class="resolution-quote-card gothic-panel">
          <p class="resolution-quote font-pixel">
            "The archive... was never mine to control. Consciousness cannot be bound by rigid taxonomy. Through scientific rigor and compassionate understanding, you have healed the Fracture."
          </p>
          <span class="quote-author">— The Creator, Restored Architect of the Mindrealm</span>
        </div>
      </div>

      <!-- Campaign Metrics Grid -->
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-icon">📜</div>
          <div class="metric-info">
            <span class="metric-label">CHAPTERS CONQUERED</span>
            <span class="metric-val font-pixel">12 / 12 (100%)</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">⚔️</div>
          <div class="metric-info">
            <span class="metric-label">FINAL ENCOUNTER</span>
            <span class="metric-val font-pixel">THE CREATOR DEFEATED</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">🔮</div>
          <div class="metric-info">
            <span class="metric-label">MIND SEALS UNITED</span>
            <span class="metric-val font-pixel">12 SACRED SEALS</span>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon">💎</div>
          <div class="metric-info">
            <span class="metric-label">FRACTURE STATUS</span>
            <span class="metric-val font-pixel">TOTAL SYNTHESIS</span>
          </div>
        </div>
      </div>

      <!-- Rewards & Achievement Unlocked -->
      <div class="completion-rewards-section">
        <h4 class="sec-label font-pixel">APEX CAMPAIGN REWARDS CLAIMED</h4>
        <div class="rewards-row">
          <div class="reward-pill xp-reward font-pixel">
            <span>⭐</span>
            <span>+2,500 XP</span>
          </div>
          <div class="reward-pill coin-reward font-pixel">
            <span>🪙</span>
            <span>+1,500 COINS</span>
          </div>
          <div class="reward-pill badge-reward font-pixel">
            <span>🎖️</span>
            <span>TITLE: GRAND MINDKEEPER</span>
          </div>
          <div class="reward-pill ach-reward font-pixel">
            <span>🏆</span>
            <span>ACHIEVEMENT: ARCHIVE UNBOUND</span>
          </div>
        </div>
      </div>

      <!-- Navigation Destinations -->
      <div class="completion-nav-actions">
        <button class="btn btn-gothic-crimson btn-lg" @click="navigate('/world')">
          🏛️ RETURN TO WORLD HUB
        </button>
        <button class="btn btn-gothic-stone btn-lg" @click="navigate('/studyhall')">
          📜 GRAND STUDY HALL
        </button>
        <button class="btn btn-gothic-stone btn-lg" @click="navigate('/leaderboard')">
          🏆 HALL OF RECORDS
        </button>
        <button class="btn btn-gothic-stone btn-lg" @click="navigate('/adventure/12')">
          🗺️ CHAPTER 12 MAP
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import PixelEmoji from '../ui/PixelEmoji.vue'
import { resolveAsset, getFinalBoss } from '../../services/bossMapping'

const props = defineProps({
  rewards: {
    type: Object,
    default: () => ({ xp: 2500, coins: 1500 })
  },
  isFirstCompletion: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close'])
const router = useRouter()

const creatorMeta = computed(() => getFinalBoss())
const creatorSprite = computed(() => {
  return resolveAsset(creatorMeta.value?.happy || creatorMeta.value?.animated || 'assets/bosses/creator/The Creator - BATTLE STATE.png')
})

function navigate(path) {
  emit('close')
  router.push(path)
}
</script>

<style scoped>
.campaign-completion-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(8, 4, 16, 0.94);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow-y: auto;
}

.campaign-completion-card {
  width: 100%;
  max-width: 860px;
  background: radial-gradient(circle at top center, rgba(38, 16, 64, 0.98), rgba(12, 6, 22, 0.99));
  border: 2px solid var(--aged-gold, #c8aa6e);
  box-shadow: 0 0 50px rgba(168, 85, 247, 0.4), inset 0 0 30px rgba(200, 170, 110, 0.15);
  padding: 2.2rem;
  display: flex;
  flex-direction: column;
  gap: 1.6rem;
  position: relative;
}

.completion-header {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
}

.crown-emblem {
  filter: drop-shadow(0 0 16px rgba(245, 158, 11, 0.8));
  animation: float-crown 3s ease-in-out infinite;
}

@keyframes float-crown {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}

.sq-kicker {
  font-size: 0.85rem;
  color: #c084fc;
  letter-spacing: 0.25em;
  text-transform: uppercase;
}

.sq-main-title {
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 900;
  letter-spacing: 0.12em;
  background: linear-gradient(180deg, #fff7ed 0%, #fde047 50%, #ea580c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,0.8));
  margin: 0.1rem 0;
}

.sq-subtitle {
  font-size: clamp(1rem, 2vw, 1.35rem);
  color: #a7f3d0;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  font-family: var(--font-cinzel, 'Cinzel', serif);
  text-shadow: 0 0 14px rgba(52, 211, 153, 0.5);
  margin: 0;
}

/* Harmony Showcase */
.harmony-showcase {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  background: rgba(16, 8, 30, 0.7);
  border: 1px solid rgba(168, 85, 247, 0.35);
  padding: 1.2rem 1.6rem;
}

.creator-peaceful-frame {
  position: relative;
  width: 130px;
  height: 130px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.creator-restored-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 0 16px rgba(168, 85, 247, 0.7));
}

.celestial-halo {
  position: absolute;
  inset: -8px;
  border-radius: 50%;
  border: 2px dashed rgba(245, 158, 11, 0.6);
  animation: spin-halo 24s linear infinite;
}

@keyframes spin-halo {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.resolution-quote-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.resolution-quote {
  font-size: 0.95rem;
  line-height: 1.55;
  color: #f3e8ff;
  margin: 0;
  font-style: italic;
}

.quote-author {
  font-size: 0.78rem;
  color: #c084fc;
  letter-spacing: 0.08em;
  font-weight: 600;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.9rem;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  background: rgba(22, 12, 42, 0.7);
  border: 1px solid rgba(200, 170, 110, 0.3);
  padding: 0.8rem 1rem;
}

.metric-icon {
  font-size: 1.6rem;
}

.metric-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.metric-label {
  font-size: 0.68rem;
  color: #94a3b8;
  letter-spacing: 0.1em;
  font-weight: 700;
}

.metric-val {
  font-size: 0.95rem;
  color: #fde047;
  font-weight: 700;
}

/* Rewards Section */
.completion-rewards-section {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  background: rgba(14, 8, 26, 0.75);
  border: 1px solid rgba(168, 85, 247, 0.3);
  padding: 1rem 1.4rem;
}

.sec-label {
  font-size: 0.8rem;
  color: #e9d5ff;
  letter-spacing: 0.15em;
  margin: 0;
}

.rewards-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
}

.reward-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 700;
  border-radius: 4px;
}

.xp-reward {
  background: rgba(234, 88, 12, 0.25);
  border: 1px solid #f97316;
  color: #fdba74;
}

.coin-reward {
  background: rgba(234, 179, 8, 0.22);
  border: 1px solid #eab308;
  color: #fef08a;
}

.badge-reward {
  background: rgba(168, 85, 247, 0.22);
  border: 1px solid #a855f7;
  color: #e9d5ff;
}

.ach-reward {
  background: rgba(16, 185, 129, 0.22);
  border: 1px solid #10b981;
  color: #a7f3d0;
}

/* Nav Actions */
.completion-nav-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  justify-content: center;
  padding-top: 0.5rem;
}

.btn-gothic-crimson {
  background: linear-gradient(180deg, #b91c1c 0%, #7f1d1d 100%);
  color: #fff;
  border: 1px solid #f87171;
  font-family: var(--font-pixel, monospace);
  font-weight: 700;
  letter-spacing: 0.08em;
  cursor: pointer;
  padding: 0.8rem 1.4rem;
  box-shadow: 0 4px 14px rgba(185, 28, 28, 0.4);
  transition: transform 0.15s, box-shadow 0.15s;
}

.btn-gothic-crimson:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
}

.btn-gothic-stone {
  background: rgba(30, 24, 44, 0.85);
  color: #e2e8f0;
  border: 1px solid rgba(200, 170, 110, 0.5);
  font-family: var(--font-pixel, monospace);
  cursor: pointer;
  padding: 0.8rem 1.2rem;
  transition: background 0.15s, border-color 0.15s;
}

.btn-gothic-stone:hover {
  background: rgba(48, 38, 70, 0.95);
  border-color: #fde047;
  color: #fff;
}

@media (max-width: 768px) {
  .harmony-showcase {
    flex-direction: column;
    text-align: center;
  }
  .completion-nav-actions {
    flex-direction: column;
  }
}
</style>
