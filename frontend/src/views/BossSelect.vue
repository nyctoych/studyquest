<template>
  <div class="boss-select-page animate-slide-up">
    <!-- Header -->
    <div class="arena-header">
      <div class="arena-title-wrap">
        <span class="arena-tag">⚔️ Psychology Boss Arena</span>
        <h1 class="page-title">Psychology Board-Exam Arena</h1>
        <p class="page-subtitle">
          Vanquish psychology guardians across Cognitive, Developmental, Biological, Abnormal, Psychometrics, and Ethics disciplines to earn massive XP and Coins!
        </p>
      </div>

      <!-- Player Battle Stats Pill -->
      <div class="glass-card player-battle-stats">
        <div class="stat-mini">
          <span class="lbl">Player Level</span>
          <span class="val">Lv. {{ auth.level }}</span>
        </div>
        <div class="stat-mini">
          <span class="lbl">Battle HP</span>
          <span class="val text-green">100 / 100</span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Summoning bosses...</p>
    </div>

    <!-- Boss Grid -->
    <div v-else class="boss-grid">
      <div
        v-for="boss in bosses"
        :key="boss.id"
        class="glass-card boss-card"
        :class="{
          'boss-locked': isBossLocked(boss),
          [`boss-element-${boss.element || 'arcane'}`]: true
        }"
      >
        <!-- Locked Badge Overlay -->
        <div v-if="isBossLocked(boss)" class="locked-overlay">
          <div class="lock-icon">🔒</div>
          <span class="lock-text">Requires Level {{ boss.min_level || boss.required_level || 5 }}</span>
        </div>

        <!-- Boss Avatar / Icon Frame -->
        <div class="boss-visual-frame">
          <div class="boss-icon-circle">
            <img
              v-if="getBossImage(boss)"
              :src="getBossImage(boss)"
              class="boss-pixel-img"
              :alt="boss.name"
            />
            <span v-else class="boss-emoji">{{ getBossEmoji(boss) }}</span>
          </div>
          <div class="boss-difficulty-pill" :class="boss.difficulty || 'hard'">
            {{ (boss.difficulty || 'Medium').toUpperCase() }}
          </div>
        </div>

        <!-- Boss Info -->
        <div class="boss-card-info">
          <div class="boss-subject-tag">{{ boss.subject || 'General Knowledge' }}</div>
          <h3 class="boss-name">{{ boss.name }}</h3>
          <p class="boss-desc">{{ boss.description || 'A fearsome guardian of academic wisdom.' }}</p>

          <!-- Boss Stats Bar -->
          <div class="boss-specs">
            <div class="spec-col">
              <span class="spec-k">Health</span>
              <span class="spec-v text-red">❤️ {{ boss.max_hp || boss.hp }} HP</span>
            </div>
            <div class="spec-col">
              <span class="spec-k">Questions</span>
              <span class="spec-v text-blue">❓ {{ boss.questions_count || 5 }} Rounds</span>
            </div>
            <div class="spec-col">
              <span class="spec-k">Time Limit</span>
              <span class="spec-v text-yellow">⏱️ {{ boss.time_per_question || 25 }}s</span>
            </div>
          </div>

          <!-- Boss Loot Preview -->
          <div class="boss-loot-box">
            <span class="loot-label">Victory Spoils:</span>
            <div class="loot-chips">
              <span class="loot-chip xp">⭐ +{{ boss.xp_reward || 300 }} XP</span>
              <span class="loot-chip pts">🪙 +{{ boss.points_reward || boss.point_reward || 150 }} Coins</span>
              <span v-if="boss.badge_reward" class="loot-chip badge">🏆 {{ boss.badge_reward }}</span>
            </div>
          </div>
        </div>

        <!-- Challenge Button -->
        <div class="boss-card-actions">
          <button
            v-if="!isBossLocked(boss)"
            class="btn btn-primary challenge-btn"
            @click="challengeBoss(boss.id)"
          >
            <span>⚔️ Challenge Boss</span>
          </button>
          <button v-else class="btn btn-disabled" disabled>
            <span>🔒 Locked (Lv. {{ boss.min_level || 5 }})</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { bossService } from '../services/bossService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { getBossForChapter } from '../services/bossMapping'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const bosses = ref([])
const loading = ref(true)

function getBossImage(boss, index) {
  const chapterIdx = ((boss.id || index || 0) % 11) + 1
  const meta = getBossForChapter(chapterIdx)
  return meta?.animatedGif || meta?.image || null
}

function getBossEmoji(boss) {
  if (boss.icon) return boss.icon
  const name = (boss.name || '').toLowerCase()
  if (name.includes('amnesia') || name.includes('memory') || name.includes('cognitive')) return '🧠'
  if (name.includes('sphinx') || name.includes('schema') || name.includes('personality')) return '🦁'
  if (name.includes('neuro') || name.includes('bio')) return '🧬'
  if (name.includes('diagnostic') || name.includes('chimera') || name.includes('abnormal')) return '👾'
  if (name.includes('psychometrics') || name.includes('phantom') || name.includes('stat')) return '📊'
  if (name.includes('ethics') || name.includes('archon')) return '⚖️'
  return boss.icon || '💀'
}

function isBossLocked(boss) {
  const minLv = boss.min_level || boss.required_level || 1
  return (auth.level || 1) < minLv
}

async function fetchBosses() {
  loading.value = true
  try {
    const res = await bossService.getAll()
    if (res.data && res.data.success) {
      bosses.value = res.data.bosses || res.data.data || []
    }
  } catch (err) {
    console.error('Failed to load bosses', err)
    toast.add('Failed to load bosses list', 'error')
  } finally {
    loading.value = false
  }
}

function challengeBoss(bossId) {
  router.push(`/boss/${bossId}`)
}

onMounted(() => {
  fetchBosses()
})
</script>

<style scoped>
.boss-select-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  position: relative;
  z-index: 1;
}

.arena-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.5rem;
  padding: 1.5rem 1.75rem;
  background: linear-gradient(180deg, rgba(20, 14, 18, 0.85) 0%, rgba(10, 10, 14, 0.95) 100%);
  border: 1px solid rgba(139, 26, 26, 0.4);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.8), inset 0 0 15px rgba(139, 26, 26, 0.15);
  position: relative;
}

.arena-header::before {
  content: "❖";
  position: absolute;
  top: -8px;
  left: 20px;
  color: #b59410;
  font-size: 14px;
  text-shadow: 0 0 6px rgba(181, 148, 16, 0.6);
}

.arena-title-wrap {
  flex: 1;
  min-width: 300px;
}

.arena-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #e63946;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  margin-bottom: 0.5rem;
  text-shadow: 0 0 10px rgba(230, 57, 70, 0.5);
}

.page-title {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 800;
  color: #e2d9cc;
  letter-spacing: 0.04em;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(139, 26, 26, 0.3);
}

.page-subtitle {
  color: #a09789;
  font-size: 0.95rem;
  margin-top: 0.5rem;
  max-width: 680px;
  line-height: 1.5;
  font-family: 'VT323', monospace;
  letter-spacing: 0.05em;
}

.player-battle-stats {
  display: flex;
  gap: 1.5rem;
  padding: 1rem 1.5rem;
  background: rgba(12, 13, 18, 0.9);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #0a0c10, 0 4px 15px rgba(0, 0, 0, 0.6);
}

.stat-mini {
  display: flex;
  flex-direction: column;
}

.stat-mini .lbl {
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  color: #8b8070;
  text-transform: uppercase;
}

.stat-mini .val {
  font-family: 'Press Start 2P', monospace;
  font-size: 1rem;
  color: #e2d9cc;
  margin-top: 0.25rem;
}

.text-green { color: #52b788 !important; text-shadow: 0 0 8px rgba(82, 183, 136, 0.4); }
.text-red { color: #e63946 !important; text-shadow: 0 0 8px rgba(230, 57, 70, 0.4); }
.text-blue { color: #64b5f6 !important; }
.text-yellow { color: #e6c845 !important; text-shadow: 0 0 8px rgba(230, 200, 69, 0.4); }

.boss-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.75rem;
}

.boss-card {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.5rem;
  border-radius: 4px;
  border: 1px solid rgba(139, 26, 26, 0.35);
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(0, 0, 0, 0.7);
  overflow: hidden;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

.boss-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, #8b1a1a, transparent);
}

.boss-card:hover:not(.boss-locked) {
  transform: translateY(-4px);
  border-color: rgba(230, 57, 70, 0.7);
  box-shadow: 0 0 0 2px #050608, 0 12px 30px rgba(139, 26, 26, 0.4), inset 0 0 25px rgba(139, 26, 26, 0.2);
}

.boss-locked {
  opacity: 0.55;
  filter: grayscale(0.8);
}

.locked-overlay {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(5, 6, 8, 0.9);
  padding: 0.35rem 0.75rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #b59410;
  border: 1px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 2px #050608;
  z-index: 2;
}

.boss-visual-frame {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.boss-icon-circle {
  width: 78px;
  height: 78px;
  border-radius: 4px;
  background: radial-gradient(circle, rgba(139, 26, 26, 0.35) 0%, rgba(10, 10, 14, 0.95) 100%);
  border: 2px solid rgba(139, 26, 26, 0.5);
  box-shadow: 0 0 0 2px #050608, inset 0 0 15px rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
}

.boss-pixel-img {
  width: 90%;
  height: 90%;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 0 8px rgba(139, 26, 26, 0.6));
}

.boss-emoji {
  font-size: 2.25rem;
  filter: drop-shadow(0 0 8px rgba(230, 57, 70, 0.5));
}

.boss-difficulty-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  padding: 0.35rem 0.65rem;
  background: rgba(139, 26, 26, 0.25);
  color: #ff6b6b;
  border: 1px solid rgba(230, 57, 70, 0.4);
  box-shadow: 0 0 0 1px #050608;
}

.boss-subject-tag {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: #b59410;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.35rem;
}

.boss-name {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: #f5efe6;
  margin-bottom: 0.5rem;
  letter-spacing: 0.03em;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
}

.boss-desc {
  font-size: 0.88rem;
  color: #a59e92;
  line-height: 1.45;
  margin-bottom: 1.25rem;
  font-family: 'VT323', monospace;
  letter-spacing: 0.03em;
}

.boss-specs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  background: rgba(8, 9, 12, 0.7);
  padding: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.6);
  margin-bottom: 1.25rem;
}

.spec-col {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.spec-k {
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  color: #7b7365;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.spec-v {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  margin-top: 0.35rem;
}

.boss-loot-box {
  background: rgba(10, 11, 15, 0.8);
  border: 1px dashed rgba(181, 148, 16, 0.3);
  padding: 0.75rem;
  margin-bottom: 1.25rem;
}

.loot-label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  font-weight: 700;
  color: #b59410;
  letter-spacing: 0.06em;
  margin-bottom: 0.4rem;
}

.loot-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.loot-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.55rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.loot-chip.xp { background: rgba(181, 148, 16, 0.15); color: #e6c845; border-color: rgba(181, 148, 16, 0.4); }
.loot-chip.pts { background: rgba(147, 51, 234, 0.15); color: #c084fc; border-color: rgba(147, 51, 234, 0.4); }
.loot-chip.badge { background: rgba(82, 183, 136, 0.15); color: #74c69d; border-color: rgba(82, 183, 136, 0.4); }

.challenge-btn {
  width: 100%;
  padding: 0.8rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border: 2px solid #b91c1c;
  box-shadow: 0 0 0 2px #050608, 0 4px 15px rgba(139, 26, 26, 0.6);
  cursor: pointer;
  transition: all 0.2s ease;
  letter-spacing: 0.05em;
}

.challenge-btn:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%);
  border-color: #ef4444;
  box-shadow: 0 0 0 2px #050608, 0 6px 20px rgba(230, 57, 70, 0.7);
  transform: translateY(-2px);
}

.btn-disabled {
  width: 100%;
  padding: 0.8rem;
  background: rgba(20, 20, 25, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  color: #656055;
  cursor: not-allowed;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}

@media (max-width: 768px) {
  .arena-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  .arena-title-wrap {
    min-width: unset;
  }
  .page-title {
    font-size: 1.6rem;
  }
  .player-battle-stats {
    justify-content: space-around;
  }
}

@media (max-width: 480px) {
  .boss-grid {
    grid-template-columns: 1fr;
  }
  .boss-card {
    padding: 1.1rem;
  }
  .player-battle-stats {
    flex-wrap: wrap;
    gap: 0.75rem;
  }
}
</style>
