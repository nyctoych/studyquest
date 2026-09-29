<template>
  <div class="leaderboard-container game-module-env module-env-records">
    <div class="leaderboard-header">
      <div class="header-tag flex items-center justify-center gap-1.5"><PixelEmoji emoji="trophy" size="xs" /> LAN Board Exam Rankings</div>
      <h1 class="header-title">Psychology Champions</h1>
      <p class="header-desc">
        Top contenders preparing for the licensure exam ranked by battle rating, wins, and knowledge streak.
      </p>

      <!-- Tabs -->
      <div class="leaderboard-tabs">
        <button
          class="tab-btn flex items-center gap-1.5"
          :class="{ active: currentType === 'rating' }"
          @click="changeTab('rating')"
        >
          <PixelEmoji emoji="lightning" size="xs" /> Battle Rating (ELO)
        </button>
        <button
          class="tab-btn flex items-center gap-1.5"
          :class="{ active: currentType === 'level' }"
          @click="changeTab('level')"
        >
          <PixelEmoji emoji="star" size="xs" /> Scholar Level
        </button>
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="table-card">
      <div v-if="loading" class="loading-box">
        Loading rankings…
      </div>

      <div v-else-if="leaderboard.length === 0" class="empty-box">
        No contenders found yet. Play your first match to claim a rank!
      </div>

      <table v-else class="ranking-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Candidate</th>
            <th>Level</th>
            <th>ELO Rating</th>
            <th>Win Rate</th>
            <th>Total Battles</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, idx) in leaderboard"
            :key="row.id"
            :class="{ 'highlight-user': row.id === auth.user?.id }"
          >
            <td class="rank-col">
              <span v-if="idx === 0" class="crown gold flex items-center gap-1"><PixelEmoji emoji="crown" size="xs" /> 1</span>
              <span v-else-if="idx === 1" class="crown silver flex items-center gap-1"><PixelEmoji emoji="trophy" size="xs" /> 2</span>
              <span v-else-if="idx === 2" class="crown bronze flex items-center gap-1"><PixelEmoji emoji="shield" size="xs" /> 3</span>
              <span v-else class="rank-num">#{{ idx + 1 }}</span>
            </td>
            <td class="candidate-col">
              <span class="user-avatar"><PixelEmoji emoji="person" size="xs" /></span>
              <strong class="candidate-name">{{ row.username }}</strong>
              <span v-if="row.id === auth.user?.id" class="you-badge">YOU</span>
            </td>
            <td>Lvl {{ row.level }}</td>
            <td class="rating-col">{{ row.battle_rating || 1200 }}</td>
            <td>
              {{ calculateWinRate(row.wins, row.total_battles) }}%
            </td>
            <td>{{ row.total_battles || 0 }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import multiplayerService from '../services/multiplayerService'

const auth = useAuthStore()
const currentType = ref('rating')
const leaderboard = ref([])
const loading = ref(true)

async function fetchLeaderboard(type = 'rating') {
  loading.value = true
  try {
    const res = await multiplayerService.getLeaderboard(type)
    if (res.success) {
      leaderboard.value = res.leaderboard || []
    }
  } catch (e) {
    console.error('Failed to load leaderboard:', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchLeaderboard(currentType.value)
})

function changeTab(type) {
  currentType.value = type
  fetchLeaderboard(type)
}

function calculateWinRate(wins, total) {
  if (!total || total === 0) return 0
  return Math.round(((wins || 0) / total) * 100)
}
</script>

<style scoped>
.leaderboard-container {
  padding: 0;
  max-width: 1100px;
  margin: 0 auto;
  color: #e2d9cc;
  position: relative;
  z-index: 1;
}

.leaderboard-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.header-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #b59410;
  background: rgba(181, 148, 16, 0.12);
  padding: 0.35rem 0.9rem;
  margin-bottom: 0.8rem;
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 1px #050608;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.header-title {
  font-family: 'Cinzel', serif;
  font-size: 2.6rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
  color: #e2d9cc;
  letter-spacing: 0.04em;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.9), 0 0 20px rgba(181, 148, 16, 0.25);
}

.header-desc {
  color: #a09789;
  max-width: 620px;
  margin: 0 auto 1.75rem;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  letter-spacing: 0.03em;
  line-height: 1.45;
}

.leaderboard-tabs {
  display: inline-flex;
  gap: 0.5rem;
  background: #08090c;
  padding: 0.35rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
}

.tab-btn {
  padding: 0.65rem 1.4rem;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  background: none;
  border: 1px solid transparent;
  color: #8b8070;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border-color: #b91c1c;
  box-shadow: 0 0 0 1px #050608;
}

.table-card {
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #050608, 0 12px 35px rgba(0, 0, 0, 0.85);
  border-radius: 4px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  position: relative;
}

.table-card::before {
  content: "❖";
  position: absolute;
  top: 8px;
  left: 12px;
  color: #b59410;
  font-size: 11px;
}

.table-card::after {
  content: "❖";
  position: absolute;
  top: 8px;
  right: 12px;
  color: #b59410;
  font-size: 11px;
}

.ranking-table {
  width: 100%;
  min-width: 540px;
  border-collapse: collapse;
  text-align: left;
}

.ranking-table th {
  padding: 1.25rem 1.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #b59410;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  white-space: nowrap;
}

.ranking-table td {
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  letter-spacing: 0.03em;
  white-space: nowrap;
}

.ranking-table tr:hover {
  background: rgba(139, 26, 26, 0.12);
}

.highlight-user {
  background: rgba(181, 148, 16, 0.12) !important;
  border-left: 3px solid #b59410;
}

.crown.gold { color: #e6c845; font-weight: 800; text-shadow: 0 0 8px rgba(230, 200, 69, 0.5); }
.crown.silver { color: #cbd5e1; font-weight: 800; text-shadow: 0 0 6px rgba(203, 213, 225, 0.4); }
.crown.bronze { color: #d97706; font-weight: 800; text-shadow: 0 0 6px rgba(217, 119, 6, 0.4); }
.rank-num { font-family: 'Press Start 2P', monospace; font-size: 0.7rem; color: #8b8070; }

.candidate-col {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.candidate-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  letter-spacing: 0.04em;
  color: #f5efe6;
}

.user-avatar { font-size: 1.2rem; filter: drop-shadow(0 0 4px rgba(181, 148, 16, 0.4)); }

.you-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  background: #8b1a1a;
  border: 1px solid #b91c1c;
  color: #fff;
  padding: 0.2rem 0.45rem;
}

.rating-col {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  color: #e6c845;
  text-shadow: 0 0 6px rgba(230, 200, 69, 0.4);
}

.loading-box, .empty-box {
  padding: 3rem;
  text-align: center;
  font-family: 'VT323', monospace;
  font-size: 1.25rem;
  color: #8b8070;
}

@media (max-width: 768px) {
  .leaderboard-container {
    padding: 0;
  }
  .leaderboard-header {
    margin-bottom: 1.5rem;
  }
  .header-title {
    font-size: 1.8rem;
  }
  .leaderboard-tabs {
    width: 100%;
    display: flex;
  }
  .tab-btn {
    flex: 1;
    padding: 0.5rem 0.25rem;
    font-size: 0.75rem;
  }
  .ranking-table th, .ranking-table td {
    padding: 0.85rem 1rem;
    font-size: 0.95rem;
  }
}
</style>
