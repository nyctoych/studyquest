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
        <div class="avatar-center" :class="{ 'has-matched-avatar': mm.matchStatus === 'matched' && mm.matchedRoom?.opponent }">
          <AvatarImg
            v-if="mm.matchStatus === 'matched' && mm.matchedRoom?.opponent"
            :character="mm.matchedRoom.opponent.character_class || mm.matchedRoom.opponent.class_type"
            :player="mm.matchedRoom.opponent"
            :size="52"
          />
          <span v-else class="avatar-icon">🧠</span>
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

        <!-- Ranked Bet Info Pill while Searching -->
        <div v-if="mm.matchStatus === 'searching'" class="active-bet-pill">
          <div class="active-bet-row font-pixel">
            <span>🪙 WAGER: {{ selectedBet.toLocaleString() }} COINS</span>
            <span class="active-pot-text">POT: {{ (selectedBet * 2).toLocaleString() }}</span>
          </div>
          <div class="active-elo-subtext font-pixel">
            Rating deduction/gain remains standard ELO (not based on coin bet)
          </div>
        </div>

        <p class="status-desc" v-if="mm.matchStatus === 'searching'">
          Broadcasting across LAN network for opponents wagering {{ selectedBet.toLocaleString() }} coins…
        </p>
        <p class="status-desc matched" v-else-if="mm.matchStatus === 'matched'">
          Loading duel arena with {{ mm.matchedRoom?.opponent?.username || 'Opponent' }}…
        </p>

        <!-- Matched Opponent Avatar Card Pill -->
        <div v-if="mm.matchStatus === 'matched' && mm.matchedRoom?.opponent" class="matched-opponent-pill">
          <div class="matched-opp-avatar-wrap">
            <AvatarImg
              :character="mm.matchedRoom.opponent.character_class || mm.matchedRoom.opponent.class_type"
              :player="mm.matchedRoom.opponent"
              :size="42"
            />
          </div>
          <div class="matched-opp-details font-pixel">
            <span class="matched-opp-tag">MATCHED OPPONENT</span>
            <span class="matched-opp-name">{{ mm.matchedRoom.opponent.username }}</span>
            <span class="matched-opp-rating">★ {{ mm.matchedRoom.opponent.rating || 1200 }} ELO (Lv.{{ mm.matchedRoom.opponent.level || 1 }})</span>
          </div>
        </div>
      </div>

      <!-- Coin Bet Tier Selector (Before Queueing) -->
      <div v-if="mm.matchStatus !== 'matched'" class="coin-bet-selector-box">
        <div class="bet-selector-header">
          <label class="filter-label">Ranked Coin Wager:</label>
          <span class="user-balance-pill font-pixel">
            Balance: 🪙 {{ (auth.user?.coins || 0).toLocaleString() }}
          </span>
        </div>

        <div class="bet-tiers-mini-grid">
          <button
            v-for="amount in validTiers"
            :key="amount"
            type="button"
            class="tier-mini-btn font-pixel"
            :class="{
              'is-active': selectedBet === amount,
              'is-disabled': (auth.user?.coins || 0) < amount
            }"
            :disabled="mm.inQueue || (auth.user?.coins || 0) < amount"
            @click="selectedBet = amount"
          >
            <span class="tier-mini-amount">{{ amount.toLocaleString() }}</span>
            <span class="tier-mini-label">🪙 Pot: {{ (amount * 2).toLocaleString() }}</span>
            <span v-if="(auth.user?.coins || 0) < amount" class="tier-insufficient-text">Low</span>
          </button>
        </div>

        <!-- Warning if selected bet exceeds balance -->
        <div v-if="!hasSufficientCoins" class="insufficient-warning font-pixel">
          ⚠️ Insufficient coins for a {{ selectedBet.toLocaleString() }} wager. Please choose a lower wager.
        </div>

        <!-- ELO Notice -->
        <div class="elo-mini-notice font-pixel">
          ⚖️ Note: ELO rating loss/gain is standard (K=32 performance) and not based on coin bet.
        </div>
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
          :disabled="!hasSufficientCoins"
          @click="startSearching"
        >
          {{ hasSufficientCoins ? `Enter Queue (${selectedBet.toLocaleString()} Coins)` : 'Insufficient Coins' }}
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
import { useAuthStore } from '../stores/auth'
import { useMatchmakingStore } from '../stores/matchmaking'
import { useBattleStore } from '../stores/battle'
import multiplayerService from '../services/multiplayerService'
import AvatarImg from '../components/AvatarImg.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const mm = useMatchmakingStore()
const battle = useBattleStore()

const mode = ref(route.query.mode || 'ranked')
const categories = ref([])
const selectedCategory = ref(null)

const validTiers = [100, 1000, 10000, 20000]
const parsedQueryBet = Number(route.query.bet)
const initialBet = validTiers.includes(parsedQueryBet) ? parsedQueryBet : 100
const selectedBet = ref(initialBet)

const hasSufficientCoins = computed(() => {
  const coins = auth.user?.coins || 0
  return coins >= selectedBet.value
})

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

  // If user has enough coins and arrived from wager selection, auto-start searching
  if (hasSufficientCoins.value) {
    startSearching()
  }
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
  if (!hasSufficientCoins.value) return
  mm.joinQueue(selectedCategory.value, null, selectedBet.value)
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
  margin-bottom: 0.75rem;
}

.avatar-center.has-matched-avatar {
  border-color: #52b788;
  box-shadow: 0 0 20px rgba(82, 183, 136, 0.6);
  border-radius: 50%;
  overflow: hidden;
}

.matched-opponent-pill {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(8, 9, 12, 0.95);
  border: 1.5px solid rgba(82, 183, 136, 0.5);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.85);
  border-radius: 6px;
  padding: 0.75rem 1.25rem;
  margin: 0 auto 1.5rem;
  max-width: 420px;
  text-align: left;
}

.matched-opp-avatar-wrap {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  box-shadow: 0 0 10px rgba(82, 183, 136, 0.4);
}

.matched-opp-details {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.matched-opp-tag {
  font-size: 0.6rem;
  color: #52b788;
  letter-spacing: 0.08em;
}

.matched-opp-name {
  font-size: 0.85rem;
  color: #f8fafc;
  font-weight: bold;
}

.matched-opp-rating {
  font-size: 0.68rem;
  color: #ffd700;
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

.btn-start:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  filter: grayscale(0.6);
  box-shadow: none;
}

/* Active Wager Pill while searching */
.active-bet-pill {
  background: rgba(8, 9, 12, 0.85);
  border: 1px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 1px #050608;
  border-radius: 4px;
  padding: 0.75rem 1rem;
  margin: 0 auto 1.25rem;
  max-width: 440px;
}

.active-bet-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: #ffd700;
  margin-bottom: 0.3rem;
}

.active-pot-text {
  color: #4ade80;
  font-weight: bold;
}

.active-elo-subtext {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1.4;
  text-align: center;
}

/* Bet Selector in Matchmaking Card */
.coin-bet-selector-box {
  margin: 1.25rem 0;
  text-align: left;
}

.bet-selector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.4rem;
}

.user-balance-pill {
  font-size: 0.7rem;
  color: #ffd700;
}

.bet-tiers-mini-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
  margin-bottom: 0.6rem;
}

.tier-mini-btn {
  background: #08090c;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 1px #050608;
  border-radius: 3px;
  padding: 0.6rem 0.3rem;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  transition: all 0.2s;
  color: #e2d9cc;
}

.tier-mini-btn:hover:not(.is-disabled):not(:disabled) {
  border-color: #ffd700;
  transform: translateY(-2px);
}

.tier-mini-btn.is-active {
  border-color: #ffd700;
  background: linear-gradient(180deg, rgba(181, 148, 16, 0.2) 0%, rgba(8, 9, 12, 0.95) 100%);
  box-shadow: 0 0 0 1px #ffd700, 0 0 10px rgba(255, 215, 0, 0.2);
}

.tier-mini-btn.is-disabled,
.tier-mini-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  filter: grayscale(0.5);
}

.tier-mini-amount {
  font-size: 0.85rem;
  font-weight: bold;
  color: #ffd700;
}

.tier-mini-label {
  font-size: 0.58rem;
  color: #4ade80;
}

.tier-insufficient-text {
  font-size: 0.55rem;
  color: #f87171;
  background: rgba(239, 68, 68, 0.2);
  padding: 0.1rem 0.3rem;
  border-radius: 2px;
}

.insufficient-warning {
  font-size: 0.65rem;
  color: #f87171;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.elo-mini-notice {
  font-size: 0.62rem;
  color: #94a3b8;
  background: rgba(30, 41, 59, 0.4);
  border: 1px solid rgba(148, 163, 184, 0.2);
  padding: 0.4rem 0.6rem;
  border-radius: 2px;
  line-height: 1.4;
}
</style>
