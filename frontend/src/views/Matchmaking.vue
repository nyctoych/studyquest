<template>
  <div class="matchmaking-container">
    <div class="matchmaking-card animate-slide-up">
      <div class="card-header">
        <button class="back-link" @click="handleCancel">← Back to Arena</button>
        <span class="mode-tag">{{ mode === 'ranked' ? '⚡ RANKED DUEL' : '🎯 CASUAL PRACTICE' }}</span>
      </div>

      <!-- Radar Animation -->
      <div class="radar-box">
        <div class="radar-sweep" :class="{ matched: mm.matchStatus === 'matched' }"></div>
        <div class="radar-rings">
          <div class="ring ring-1"></div>
          <div class="ring ring-2"></div>
          <div class="ring ring-3"></div>
        </div>
        <div class="avatar-center">
          <span class="avatar-icon">🧠</span>
        </div>
      </div>

      <div class="status-content">
        <h2 v-if="mm.matchStatus === 'searching'" class="status-title">
          Searching for Opponent…
        </h2>
        <h2 v-else-if="mm.matchStatus === 'matched'" class="status-title matched">
          ⚔️ Opponent Found!
        </h2>
        <h2 v-else class="status-title">
          Ready to Match
        </h2>

        <p class="timer-display" v-if="mm.matchStatus === 'searching'">
          Time elapsed: <span class="time-count">{{ formattedTime }}</span>
        </p>

        <p class="status-desc" v-if="mm.matchStatus === 'searching'">
          Broadcasting across the LAN network to find a peer psychology student…
        </p>
        <p class="status-desc matched" v-else-if="mm.matchStatus === 'matched'">
          Loading duel arena with {{ mm.matchedRoom?.opponent?.username || 'Opponent' }}…
        </p>
      </div>

      <!-- Category Filter (Disabled once in queue) -->
      <div v-if="mm.matchStatus !== 'matched'" class="category-filter-box">
        <label class="filter-label">Focus Subject Category (Optional):</label>
        <select
          v-model="selectedCategory"
          class="filter-select"
          :disabled="mm.inQueue"
        >
          <option :value="null">All Board Exam Domains (Random Mix)</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.icon }} {{ cat.name }}
          </option>
        </select>
      </div>

      <!-- Actions -->
      <div class="card-actions">
        <button
          v-if="!mm.inQueue && mm.matchStatus !== 'matched'"
          class="btn-start"
          @click="startSearching"
        >
          Enter Matchmaking Queue
        </button>

        <button
          v-else-if="mm.inQueue"
          class="btn-cancel"
          @click="handleCancel"
        >
          Cancel Search
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMatchmakingStore } from '../stores/matchmaking'
import { useBattleStore } from '../stores/battle'
import multiplayerService from '../services/multiplayerService'

const route = useRoute()
const router = useRouter()
const mm = useMatchmakingStore()
const battle = useBattleStore()

const mode = ref(route.query.mode || 'ranked')
const categories = ref([])
const selectedCategory = ref(null)

const formattedTime = computed(() => {
  const m = Math.floor(mm.elapsedSeconds / 60)
  const s = mm.elapsedSeconds % 60
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
})

onMounted(async () => {
  try {
    const res = await multiplayerService.getCategories()
    if (res.success) {
      categories.value = res.categories || []
    }
  } catch (e) {
    console.error('Error fetching categories:', e)
  }

  // Auto-start searching on mount
  startSearching()
})

onUnmounted(() => {
  if (mm.inQueue && mm.matchStatus !== 'matched') {
    mm.leaveQueue()
  }
})

// When a match is found, initialize battle store and route to battle arena
watch(() => mm.matchedRoom, (room) => {
  if (room) {
    battle.initBattle(room)
    setTimeout(() => {
      router.push(`/battle/${room.roomId}`)
    }, 1500)
  }
})

function startSearching() {
  mm.joinQueue(selectedCategory.value)
}

function handleCancel() {
  mm.leaveQueue()
  router.push('/multiplayer')
}
</script>

<style scoped>
.matchmaking-container {
  min-height: calc(100vh - 120px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
  z-index: 1;
}

.matchmaking-card {
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(139, 26, 26, 0.4);
  box-shadow: 0 0 0 3px #050608, 0 20px 45px rgba(0, 0, 0, 0.95);
  border-radius: 4px;
  padding: 2.5rem;
  width: 100%;
  max-width: 540px;
  text-align: center;
  position: relative;
}

.matchmaking-card::before {
  content: "❖";
  position: absolute;
  top: 10px;
  left: 14px;
  color: #b59410;
  font-size: 12px;
}

.matchmaking-card::after {
  content: "❖";
  position: absolute;
  top: 10px;
  right: 14px;
  color: #b59410;
  font-size: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.back-link {
  background: none;
  border: none;
  color: #8b8070;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  cursor: pointer;
  transition: color 0.2s;
}

.back-link:hover { color: #f5efe6; }

.mode-tag {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  background: rgba(139, 26, 26, 0.2);
  color: #e63946;
  padding: 0.3rem 0.75rem;
  border-radius: 2px;
  border: 1px solid rgba(230, 57, 70, 0.4);
}

.radar-box {
  position: relative;
  width: 180px;
  height: 180px;
  margin: 0 auto 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.radar-rings .ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  border: 1px solid rgba(139, 26, 26, 0.35);
}

.ring-1 { width: 60px; height: 60px; }
.ring-2 { width: 120px; height: 120px; border-color: rgba(181, 148, 16, 0.3); }
.ring-3 { width: 180px; height: 180px; border-color: rgba(139, 26, 26, 0.2); }

.radar-sweep {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: conic-gradient(from 0deg, rgba(230, 57, 70, 0.35) 0deg, transparent 60deg);
  animation: spin 2.5s linear infinite;
}

.radar-sweep.matched {
  background: radial-gradient(circle, rgba(82, 183, 136, 0.4), transparent);
  animation: none;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.avatar-center {
  position: relative;
  z-index: 2;
  width: 58px;
  height: 58px;
  background: #08090c;
  border: 2px solid #b59410;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 15px rgba(181, 148, 16, 0.5);
}

.avatar-icon { font-size: 26px; }

.status-title {
  font-family: 'Cinzel', serif;
  font-size: 1.6rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
  color: #e2d9cc;
  letter-spacing: 0.03em;
}

.status-title.matched {
  color: #52b788;
  text-shadow: 0 0 10px rgba(82, 183, 136, 0.5);
}

.timer-display {
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  color: #a09789;
  margin: 0 0 0.8rem;
}

.time-count {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.95rem;
  color: #e6c845;
}

.status-desc {
  color: #7b7365;
  font-family: 'VT323', monospace;
  font-size: 1.05rem;
  margin: 0 0 1.5rem;
}

.status-desc.matched {
  color: #52b788;
  font-weight: 600;
}

.category-filter-box {
  margin: 1.5rem 0;
  text-align: left;
}

.filter-label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  color: #b59410;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
  text-transform: uppercase;
}

.filter-select {
  width: 100%;
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 0.7rem 1rem;
  color: #e2d9cc;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  outline: none;
}

.card-actions {
  margin-top: 2rem;
}

.btn-start, .btn-cancel {
  width: 100%;
  padding: 0.9rem 1.5rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-start {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border: 2px solid #b91c1c;
  box-shadow: 0 0 0 2px #050608, 0 4px 15px rgba(139, 26, 26, 0.5);
}

.btn-start:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%);
  border-color: #ef4444;
}

.btn-cancel {
  background: rgba(139, 26, 26, 0.2);
  color: #ff8585;
  border: 1px solid rgba(230, 57, 70, 0.4);
  box-shadow: 0 0 0 2px #050608;
}

.btn-cancel:hover {
  background: rgba(230, 57, 70, 0.3);
}
</style>
