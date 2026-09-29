<template>
  <div class="multiplayer-hub game-module-env module-env-arena">
    <!-- Header Banner -->
    <div class="hub-header">
      <div class="header-content">
        <div class="badge-tag flex items-center gap-1.5"><PixelEmoji emoji="sword" size="xs" /> Board Exam PvP Arena</div>
        <h1 class="hub-title">Psychology Multiplayer Battle</h1>
        <p class="hub-subtitle">
          Test your diagnostic, theoretical, and clinical mastery against fellow psychology students in real-time 1v1 duels!
        </p>
      </div>

      <!-- User PvP Stat Card -->
      <div class="user-pvp-card">
        <div class="rating-display">
          <span class="rating-label">BATTLE RATING (ELO)</span>
          <span class="rating-value">{{ stats?.battle_rating || auth.user?.battle_rating || 1200 }}</span>
        </div>
        <div class="stats-grid">
          <div class="stat-box">
            <span class="stat-num win">{{ stats?.wins || auth.user?.wins || 0 }}</span>
            <span class="stat-lbl">Wins</span>
          </div>
          <div class="stat-box">
            <span class="stat-num loss">{{ stats?.losses || auth.user?.losses || 0 }}</span>
            <span class="stat-lbl">Losses</span>
          </div>
          <div class="stat-box">
            <span class="stat-num coins flex items-center gap-1"><PixelEmoji emoji="coin" size="xs" /> {{ auth.user?.coins || 0 }}</span>
            <span class="stat-lbl">Coins</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Navigation Action Cards -->
    <div class="action-cards-grid">
      <!-- Ranked Queue Card -->
      <div class="action-card primary-action" @click="goToMatchmaking('ranked')">
        <div class="card-icon"><PixelEmoji emoji="lightning" size="xl" /></div>
        <div class="card-info">
          <h3>Ranked Matchmaking</h3>
          <p>Find an opponent close to your rating level. Win rating points, XP, and gold coins!</p>
          <span class="play-btn">Find Match →</span>
        </div>
      </div>

      <!-- Practice / Casual Card -->
      <div class="action-card secondary-action" @click="goToMatchmaking('casual')">
        <div class="card-icon"><PixelEmoji emoji="target" size="xl" /></div>
        <div class="card-info">
          <h3>Casual Practice</h3>
          <p>Sharpen your board exam recall with zero risk to your rating.</p>
          <span class="play-btn">Quick Play →</span>
        </div>
      </div>

      <!-- Leaderboard Card -->
      <div class="action-card tertiary-action" @click="navigateTo('/leaderboard')">
        <div class="card-icon"><PixelEmoji emoji="trophy" size="xl" /></div>
        <div class="card-info">
          <h3>Psychology Leaderboard</h3>
          <p>See the top-ranking psychology board exam candidates on the LAN network.</p>
          <span class="play-btn">View Rankings →</span>
        </div>
      </div>

      <!-- Cosmetic Shop Card -->
      <div class="action-card shop-action" @click="navigateTo('/shop')">
        <div class="card-icon"><PixelEmoji emoji="cart" size="xl" /></div>
        <div class="card-info">
          <h3>Cosmetic Shop</h3>
          <p>Spend battle coins on unique avatar crowns, borders, and scholar robes.</p>
          <span class="play-btn">Browse Items →</span>
        </div>
      </div>
    </div>

    <!-- Match History Section -->
    <div class="history-section">
      <h2 class="section-title flex items-center gap-2"><PixelEmoji emoji="scroll" size="sm" /> Recent Battle History</h2>
      
      <div v-if="loadingHistory" class="loading-state">
        Loading battle history…
      </div>

      <div v-else-if="history.length === 0" class="empty-state">
        <p>No battles recorded yet. Jump into matchmaking to start your journey!</p>
      </div>

      <div v-else class="history-list">
        <div
          v-for="item in history"
          :key="item.match_id"
          class="history-card"
          :class="item.outcome"
        >
          <div class="history-outcome">
            <span class="outcome-badge" :class="item.outcome">
              {{ item.outcome.toUpperCase() }}
            </span>
            <span class="battle-date">{{ formatDate(item.created_at) }}</span>
          </div>

          <div class="history-details">
            <div class="opponent-info">
              vs <strong class="opp-name">{{ item.opponent_username || 'Challenger' }}</strong>
            </div>
            <div class="score-info">
              Score: {{ item.correct_answers }}/{{ (item.correct_answers || 0) + (item.wrong_answers || 0) }} Correct
            </div>
          </div>

          <div class="history-rewards">
            <span v-if="item.rating_change" :class="item.rating_change >= 0 ? 'pos-rating' : 'neg-rating'">
              {{ item.rating_change >= 0 ? '+' : '' }}{{ item.rating_change }} ELO
            </span>
            <span v-if="item.coins_earned" class="coins-earned">+{{ item.coins_earned }} <CoinIcon :size="14" /></span>
            <span v-if="item.xp_earned" class="xp-earned">+{{ item.xp_earned }} XP</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useTransitionStore } from '../stores/transition'
import multiplayerService from '../services/multiplayerService'
import CoinIcon from '../components/common/CoinIcon.vue'

const router = useRouter()
const auth = useAuthStore()
const transitionStore = useTransitionStore()

function navigateTo(path) {
  transitionStore.triggerTravel(path, router)
}

const stats = ref(null)
const history = ref([])
const loadingHistory = ref(true)

onMounted(async () => {
  try {
    const statsRes = await multiplayerService.getStats()
    if (statsRes.success) {
      stats.value = statsRes.stats
    }
  } catch (e) {
    console.error('Error loading stats:', e)
  }

  try {
    const histRes = await multiplayerService.getHistory()
    if (histRes.success) {
      history.value = histRes.history || []
    }
  } catch (e) {
    console.error('Error loading history:', e)
  } finally {
    loadingHistory.value = false
  }
})

function goToMatchmaking(mode) {
  router.push({ path: '/matchmaking', query: { mode } })
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.multiplayer-hub {
  padding: 0;
  max-width: 1200px;
  margin: 0 auto;
  color: #e2d9cc;
  position: relative;
  z-index: 1;
}

.hub-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(139, 26, 26, 0.4);
  border-radius: 4px;
  padding: 2.25rem 2.5rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 0 0 2px #050608, 0 10px 30px rgba(0, 0, 0, 0.85);
  position: relative;
}

.hub-header::before {
  content: "❖";
  position: absolute;
  top: -8px;
  left: 20px;
  color: #b59410;
  font-size: 14px;
}

.badge-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  background: rgba(139, 26, 26, 0.2);
  color: #e63946;
  padding: 0.35rem 0.85rem;
  border-radius: 2px;
  margin-bottom: 0.8rem;
  border: 1px solid rgba(230, 57, 70, 0.4);
  box-shadow: 0 0 0 1px #050608;
}

.hub-title {
  font-family: 'Cinzel', serif;
  font-size: 2.3rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
  color: #e2d9cc;
  letter-spacing: 0.03em;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.hub-subtitle {
  color: #a09789;
  max-width: 560px;
  font-size: 1rem;
  line-height: 1.5;
  margin: 0;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
}

.user-pvp-card {
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px;
  padding: 1.5rem 2rem;
  text-align: center;
  min-width: 260px;
}

.rating-label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  color: #8b8070;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 0.3rem;
}

.rating-value {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.8rem;
  font-weight: 900;
  color: #e6c845;
  text-shadow: 0 0 15px rgba(230, 200, 69, 0.4);
}

.stats-grid {
  display: flex;
  justify-content: space-around;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.stat-box {
  display: flex;
  flex-direction: column;
}

.stat-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  font-weight: 700;
}

.stat-num.win { color: #52b788; }
.stat-num.loss { color: #e63946; }
.stat-num.coins { color: #e6c845; }

.stat-lbl {
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  color: #7b7365;
  margin-top: 0.2rem;
}

.action-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.action-card {
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
  padding: 1.8rem;
  cursor: pointer;
  transition: all 0.25s ease;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.action-card:hover {
  transform: translateY(-4px);
  border-color: rgba(181, 148, 16, 0.5);
  box-shadow: 0 0 0 2px #050608, 0 12px 30px rgba(0, 0, 0, 0.85);
}

.primary-action {
  border-color: rgba(139, 26, 26, 0.45);
}
.primary-action:hover {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px #050608, 0 12px 30px rgba(139, 26, 26, 0.4);
}

.card-icon {
  font-size: 2.2rem;
  margin-bottom: 1rem;
  filter: drop-shadow(0 0 6px rgba(181, 148, 16, 0.4));
}

.card-info h3 {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
  color: #f5efe6;
  letter-spacing: 0.03em;
}

.card-info p {
  color: #a09789;
  font-family: 'VT323', monospace;
  font-size: 1.05rem;
  line-height: 1.4;
  margin: 0 0 1.2rem;
}

.play-btn {
  display: inline-block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  font-weight: 600;
  color: #b59410;
}

.section-title {
  font-family: 'Cinzel', serif;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.2rem;
  color: #e2d9cc;
  letter-spacing: 0.03em;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.history-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #08090c;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  padding: 1rem 1.5rem;
  border-radius: 4px;
}

.history-card.won { border-left: 4px solid #52b788; }
.history-card.lost { border-left: 4px solid #e63946; }
.history-card.draw { border-left: 4px solid #b59410; }

.outcome-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  font-weight: 800;
  padding: 0.25rem 0.55rem;
  border-radius: 2px;
  margin-right: 0.8rem;
}

.outcome-badge.won { background: rgba(45, 106, 79, 0.25); color: #52b788; border: 1px solid #52b788; }
.outcome-badge.lost { background: rgba(139, 26, 26, 0.25); color: #ff8585; border: 1px solid #e63946; }
.outcome-badge.draw { background: rgba(181, 148, 16, 0.2); color: #e6c845; border: 1px solid #b59410; }

.battle-date {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #7b7365;
}

.opp-name {
  font-family: 'Cinzel', serif;
  color: #f5efe6;
}

.score-info {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #a09789;
}

.history-rewards {
  display: flex;
  gap: 1rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  font-weight: 700;
}

.pos-rating { color: #52b788; }
.neg-rating { color: #e63946; }
.coins-earned { color: #e6c845; }
.xp-earned { color: #b59410; }

.empty-state, .loading-state {
  padding: 2.5rem;
  text-align: center;
  color: #8b8070;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  background: #08090c;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .multiplayer-hub {
    padding: 1rem 0.5rem;
  }
  .hub-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
    padding: 1.25rem;
  }
  .hub-title {
    font-size: 1.6rem;
  }
  .user-pvp-card {
    min-width: unset;
    width: 100%;
    padding: 1.25rem 1rem;
  }
  .action-cards-grid {
    grid-template-columns: 1fr;
    margin-bottom: 2rem;
  }
  .history-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
  }
  .history-rewards {
    width: 100%;
    justify-content: space-between;
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding-top: 0.5rem;
  }
}
</style>
