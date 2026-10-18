<template>
  <div class="multiplayer-hub game-module-env module-env-arena" :class="{ 'theme-morning': gameStore.isMorning }">
    <!-- ══ LEVEL 15 LOCKED BARRIER ══ -->
    <div v-if="userLevel < 15 && !isTutorialPreview" class="arena-locked-screen flex flex-col items-center justify-center text-center">
      <div class="locked-card-box">
        <span class="locked-big-icon">🔒</span>
        <h1 class="locked-heading font-pixel text-gold">COLOSSEUM LOCKED</h1>
        <p class="locked-desc">
          Only seasoned scholars who have reached <strong>Level 15</strong> are permitted entry into the Board Exam PvP Arena.
        </p>
        <div class="level-progress-pill font-pixel">
          YOUR CURRENT LEVEL: {{ userLevel }} / 15
        </div>
        <p class="locked-sub-desc font-pixel">
          Earn EXP in Study Hall and Adventure Gate chapters to unlock multiplayer arena battles!
        </p>
        <button class="btn-return-realm font-pixel" @click="router.push('/world')">
          RETURN TO CITADEL REALM
        </button>
      </div>
    </div>

    <!-- ══ MAIN MULTIPLAYER ARENA CONTENT (LVL 15+ OR TUTORIAL PREVIEW) ══ -->
    <template v-else>
      <!-- Tutorial Preview Notification Banner -->
      <div v-if="isTutorialPreview" class="arena-preview-ribbon font-pixel">
        <span>⚔️ ONBOARDING PREVIEW: The Arena unlocks at Level 15. Clear Adventure chapters & Study Hall to access real-time 1v1 duels!</span>
      </div>

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
      <div class="action-card primary-action" @click="openBetModal">
        <div class="card-icon"><PixelEmoji emoji="lightning" size="xl" /></div>
        <div class="card-info">
          <h3>Ranked Matchmaking</h3>
          <p>Coin bet ranked duels! Choose your wager (100 to 20,000 coins). Win rating, XP, and battle pot!</p>
          <span class="play-btn">Select Wager & Duel →</span>
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
            <div class="matched-user-avatar-frame">
              <AvatarImg
                :character="item.opponent_class_type || (item.opponent_character_id === 2 ? 'MELEE' : item.opponent_character_id === 3 ? 'RANGE' : 'MAGE')"
                :player="{
                  character_class: item.opponent_class_type,
                  class_type: item.opponent_class_type,
                  selected_character_id: item.opponent_character_id,
                  avatar_style: item.opponent_avatar_style || 'adventurer',
                  avatar_seed: item.opponent_avatar_seed || item.opponent_username,
                  avatar_options: item.opponent_avatar_options || {},
                  username: item.opponent_username
                }"
                :size="44"
                class="opp-history-avatar"
              />
            </div>
            <div class="opponent-text-block">
              <div class="opponent-info">
                vs <strong class="opp-name">{{ item.opponent_username || 'Challenger' }}</strong>
                <span v-if="item.opponent_level" class="opp-lvl-tag font-pixel">Lv.{{ item.opponent_level }}</span>
              </div>
              <div class="score-info">
                Score: {{ item.correct_answers }}/{{ (item.correct_answers || 0) + (item.wrong_answers || 0) }} Correct
              </div>
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

    <!-- ══ RANKED COIN WAGER SELECTION MODAL ══ -->
    <Transition name="fade">
      <div v-if="showBetModal" class="bet-modal-backdrop" @click.self="showBetModal = false">
        <div class="bet-modal-card">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <span class="modal-badge font-pixel">⚔️ RANKED ARENA WAGER</span>
              <h2 class="font-pixel modal-title">Choose Your Coin Bet</h2>
            </div>
            <button class="modal-close-btn" @click="showBetModal = false">✕</button>
          </div>

          <p class="modal-subtitle font-pixel">
            Select your coin bet before entering matchmaking. You will be matched against opponents who wager the exact same amount.
          </p>

          <!-- Current Balance -->
          <div class="user-balance-bar">
            <span class="balance-label font-pixel">CURRENT BALANCE:</span>
            <span class="balance-amount font-pixel">
              🪙 {{ (auth.user?.coins || 0).toLocaleString() }} Coins
            </span>
          </div>

          <!-- 4 Wager Tiers (100, 1000, 10000, 20000) -->
          <div class="bet-tiers-grid">
            <div
              v-for="tier in betTiers"
              :key="tier.amount"
              class="bet-tier-card"
              :class="{
                'is-selected': selectedBet === tier.amount,
                'is-disabled': (auth.user?.coins || 0) < tier.amount
              }"
              @click="(auth.user?.coins || 0) >= tier.amount && (selectedBet = tier.amount)"
            >
              <div class="tier-top">
                <span class="tier-amount font-pixel">{{ tier.amount.toLocaleString() }}</span>
                <span class="tier-coin-icon">🪙</span>
              </div>
              <div class="tier-name font-pixel">{{ tier.label }}</div>
              <div class="tier-pot font-pixel">POT: {{ tier.pot.toLocaleString() }} COINS</div>
              <div class="tier-desc font-pixel">{{ tier.desc }}</div>
              <div v-if="(auth.user?.coins || 0) < tier.amount" class="insufficient-badge font-pixel">
                INSUFFICIENT COINS
              </div>
            </div>
          </div>

          <!-- Explicit Requirement Notice: ELO is standard, not based on coin bet -->
          <div class="elo-notice-box">
            <span class="elo-notice-icon">⚖️</span>
            <div class="elo-notice-text">
              <strong class="font-pixel text-gold">STANDARD RATING CALCULATION (ELO):</strong>
              <p class="font-pixel">
                ELO rating gain and deduction remain standard (based purely on match result and opponent rating) and are <u>NOT</u> calculated from or scaled by your coin bet.
              </p>
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="modal-actions">
            <button class="btn-cancel-modal font-pixel" @click="showBetModal = false">
              CANCEL
            </button>
            <button
              class="btn-confirm-bet font-pixel"
              :disabled="(auth.user?.coins || 0) < selectedBet"
              @click="confirmBetAndQueue"
            >
              ENTER QUEUE ({{ selectedBet.toLocaleString() }} COINS) →
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </template>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useGameStore } from '../stores/game'
import { useTransitionStore } from '../stores/transition'
import { useTutorialStore } from '../stores/tutorial'
import multiplayerService from '../services/multiplayerService'
import CoinIcon from '../components/common/CoinIcon.vue'
import AvatarImg from '../components/AvatarImg.vue'

const router = useRouter()
const auth = useAuthStore()
const gameStore = useGameStore()
const transitionStore = useTransitionStore()
const tutorialStore = useTutorialStore()

const userLevel = computed(() => Number(auth.user?.level || 1))
const isTutorialPreview = computed(() => tutorialStore.isActive && tutorialStore.currentStep === 9)

function navigateTo(path) {
  if (isTutorialPreview.value) {
    // Non-destructive preview: Matchmaking disabled during onboarding walkthrough
    return
  }
  transitionStore.triggerTravel(path, router)
}

const stats = ref(null)
const history = ref([])
const loadingHistory = ref(true)

onMounted(async () => {
  if (userLevel.value < 15) {
    loadingHistory.value = false
    return
  }

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

const showBetModal = ref(false)
const selectedBet = ref(100)

const betTiers = [
  { amount: 100, label: 'Novice Duel', pot: 200, desc: 'Casual wager for novices and warm-ups' },
  { amount: 1000, label: 'Scholar Clash', pot: 2000, desc: 'Standard wager for board exam candidates' },
  { amount: 10000, label: 'High Stakes', pot: 20000, desc: 'Heavy battle pot for confident scholars' },
  { amount: 20000, label: 'Colosseum Master', pot: 40000, desc: 'Maximum wager for apex psychology titans' },
]

function openBetModal() {
  const coins = auth.user?.coins || 0
  if (coins >= 1000 && selectedBet.value === 100) {
    selectedBet.value = 100
  }
  showBetModal.value = true
}

function confirmBetAndQueue() {
  if ((auth.user?.coins || 0) < selectedBet.value) return
  showBetModal.value = false
  router.push({ path: '/matchmaking', query: { mode: 'ranked', bet: selectedBet.value } })
}

function goToMatchmaking(mode) {
  if (mode === 'ranked') {
    openBetModal()
    return
  }
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

.history-details {
  display: flex;
  align-items: center;
  gap: 1.15rem;
  flex: 1;
  margin: 0 1.5rem;
}

.matched-user-avatar-frame {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  box-shadow: 0 0 12px rgba(0, 0, 0, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.opponent-text-block {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.opp-lvl-tag {
  font-size: 0.65rem;
  color: #ffd700;
  background: rgba(181, 148, 16, 0.2);
  border: 1px solid rgba(181, 148, 16, 0.4);
  padding: 0.1rem 0.4rem;
  border-radius: 2px;
  margin-left: 0.5rem;
  vertical-align: middle;
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

/* ── ARENA LOCKED SCREEN ── */
.arena-locked-screen {
  min-height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.locked-card-box {
  background: rgba(15, 23, 42, 0.92);
  border: 1px solid rgba(239, 68, 68, 0.4);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7), 0 0 25px rgba(239, 68, 68, 0.2);
  border-radius: 8px;
  padding: 3rem 2rem;
  max-width: 520px;
  width: 100%;
  backdrop-filter: blur(8px);
}

.locked-big-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 0.5rem;
  filter: drop-shadow(0 0 12px rgba(239, 68, 68, 0.4));
}

.locked-heading {
  font-size: 1.3rem;
  color: #ffd700;
  letter-spacing: 0.08em;
  margin-bottom: 0.75rem;
}

.locked-desc {
  font-size: 0.95rem;
  color: #cbd5e1;
  line-height: 1.6;
}

.level-progress-pill {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  border-radius: 20px;
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.5);
  color: #fca5a5;
  font-size: 0.85rem;
  letter-spacing: 0.06em;
  margin-top: 1.25rem;
}

.locked-sub-desc {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.85rem;
  line-height: 1.4;
}

.btn-return-realm {
  margin-top: 1.75rem;
  padding: 0.75rem 1.75rem;
  background: linear-gradient(180deg, #334155 0%, #1e293b 100%);
  border: 1px solid rgba(255, 215, 0, 0.4);
  color: #f8fafc;
  font-size: 0.8rem;
  letter-spacing: 0.06em;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-return-realm:hover {
  background: linear-gradient(180deg, #475569 0%, #334155 100%);
  border-color: #ffd700;
  color: #ffd700;
  box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
  transform: translateY(-2px);
}

.arena-preview-ribbon {
  background: rgba(234, 179, 8, 0.12);
  border: 1px solid rgba(234, 179, 8, 0.4);
  color: #fef08a;
  padding: 0.6rem 1rem;
  border-radius: 4px;
  font-size: 0.78rem;
  text-align: center;
  margin-bottom: 1rem;
}

/* ══════════════════════════════════════════════════════════════
   MORNING MODE / LIGHT THEME — MULTIPLAYER ARENA
   ══════════════════════════════════════════════════════════════ */
.multiplayer-hub.theme-morning,
:global(.theme-morning) .multiplayer-hub,
:global([data-theme="morning"]) .multiplayer-hub {
  color: #2C1E14;
}

.multiplayer-hub.theme-morning .hub-header,
:global(.theme-morning) .multiplayer-hub .hub-header,
:global([data-theme="morning"]) .multiplayer-hub .hub-header {
  background: linear-gradient(180deg, #FFFDF8 0%, #FFF3DC 100%) !important;
  border: 1.5px solid #C89F5B !important;
  box-shadow: 0 0 0 2px #EAD4AA, 0 10px 30px rgba(143, 100, 45, 0.12) !important;
}

.multiplayer-hub.theme-morning .hub-header::before,
:global(.theme-morning) .multiplayer-hub .hub-header::before,
:global([data-theme="morning"]) .multiplayer-hub .hub-header::before {
  color: #8F642D !important;
}

.multiplayer-hub.theme-morning .badge-tag,
:global(.theme-morning) .multiplayer-hub .badge-tag,
:global([data-theme="morning"]) .multiplayer-hub .badge-tag {
  background: rgba(185, 28, 28, 0.08) !important;
  color: #991b1b !important;
  border: 1px solid rgba(185, 28, 28, 0.35) !important;
  box-shadow: none !important;
}

.multiplayer-hub.theme-morning .hub-title,
:global(.theme-morning) .multiplayer-hub .hub-title,
:global([data-theme="morning"]) .multiplayer-hub .hub-title {
  color: #2C1E14 !important;
  text-shadow: none !important;
}

.multiplayer-hub.theme-morning .hub-subtitle,
:global(.theme-morning) .multiplayer-hub .hub-subtitle,
:global([data-theme="morning"]) .multiplayer-hub .hub-subtitle {
  color: #69543f !important;
}

.multiplayer-hub.theme-morning .user-pvp-card,
:global(.theme-morning) .multiplayer-hub .user-pvp-card,
:global([data-theme="morning"]) .multiplayer-hub .user-pvp-card {
  background: #FFFDF8 !important;
  border: 1.5px solid #C89F5B !important;
  box-shadow: 0 0 0 2px #EAD4AA, 0 4px 16px rgba(143, 100, 45, 0.12) !important;
}

.multiplayer-hub.theme-morning .rating-label,
:global(.theme-morning) .multiplayer-hub .rating-label,
:global([data-theme="morning"]) .multiplayer-hub .rating-label {
  color: #77634F !important;
}

.multiplayer-hub.theme-morning .rating-value,
:global(.theme-morning) .multiplayer-hub .rating-value,
:global([data-theme="morning"]) .multiplayer-hub .rating-value {
  color: #946800 !important;
  text-shadow: none !important;
}

.multiplayer-hub.theme-morning .stats-grid,
:global(.theme-morning) .multiplayer-hub .stats-grid,
:global([data-theme="morning"]) .multiplayer-hub .stats-grid {
  border-top: 1px solid rgba(184, 138, 66, 0.25) !important;
}

.multiplayer-hub.theme-morning .stat-box,
:global(.theme-morning) .multiplayer-hub .stat-box,
:global([data-theme="morning"]) .multiplayer-hub .stat-box {
  background: #FFF4DF !important;
  border: 1px solid #D8B980 !important;
  border-radius: 4px !important;
  padding: 0.35rem 0.6rem !important;
}

.multiplayer-hub.theme-morning .stat-num.win,
:global(.theme-morning) .multiplayer-hub .stat-num.win,
:global([data-theme="morning"]) .multiplayer-hub .stat-num.win {
  color: #15803d !important;
}

.multiplayer-hub.theme-morning .stat-num.loss,
:global(.theme-morning) .multiplayer-hub .stat-num.loss,
:global([data-theme="morning"]) .multiplayer-hub .stat-num.loss {
  color: #b91c1c !important;
}

.multiplayer-hub.theme-morning .stat-num.coins,
:global(.theme-morning) .multiplayer-hub .stat-num.coins,
:global([data-theme="morning"]) .multiplayer-hub .stat-num.coins {
  color: #b45309 !important;
}

.multiplayer-hub.theme-morning .stat-lbl,
:global(.theme-morning) .multiplayer-hub .stat-lbl,
:global([data-theme="morning"]) .multiplayer-hub .stat-lbl {
  color: #77634F !important;
}

/* Action Cards */
.multiplayer-hub.theme-morning .action-card,
:global(.theme-morning) .multiplayer-hub .action-card,
:global([data-theme="morning"]) .multiplayer-hub .action-card {
  background: linear-gradient(180deg, #FFFDF8 0%, #FFF4DF 100%) !important;
  border: 1.5px solid #D8C39E !important;
  box-shadow: 0 0 0 2px #EAD4AA, 0 8px 20px rgba(143, 100, 45, 0.1) !important;
}

.multiplayer-hub.theme-morning .action-card:hover,
:global(.theme-morning) .multiplayer-hub .action-card:hover,
:global([data-theme="morning"]) .multiplayer-hub .action-card:hover {
  background: linear-gradient(180deg, #FFFFFF 0%, #FFF8EB 100%) !important;
  border-color: #8F642D !important;
  box-shadow: 0 0 0 2px #D8B980, 0 12px 28px rgba(143, 100, 45, 0.2) !important;
  transform: translateY(-4px) !important;
}

.multiplayer-hub.theme-morning .primary-action,
:global(.theme-morning) .multiplayer-hub .primary-action,
:global([data-theme="morning"]) .multiplayer-hub .primary-action {
  border-color: rgba(220, 38, 38, 0.35) !important;
}

.multiplayer-hub.theme-morning .primary-action:hover,
:global(.theme-morning) .multiplayer-hub .primary-action:hover,
:global([data-theme="morning"]) .multiplayer-hub .primary-action:hover {
  border-color: #dc2626 !important;
  box-shadow: 0 0 0 2px #fca5a5, 0 12px 28px rgba(220, 38, 38, 0.2) !important;
}

.multiplayer-hub.theme-morning .secondary-action,
:global(.theme-morning) .multiplayer-hub .secondary-action,
:global([data-theme="morning"]) .multiplayer-hub .secondary-action {
  border-color: rgba(217, 119, 6, 0.35) !important;
}

.multiplayer-hub.theme-morning .secondary-action:hover,
:global(.theme-morning) .multiplayer-hub .secondary-action:hover,
:global([data-theme="morning"]) .multiplayer-hub .secondary-action:hover {
  border-color: #d97706 !important;
  box-shadow: 0 0 0 2px #fde68a, 0 12px 28px rgba(217, 119, 6, 0.2) !important;
}

.multiplayer-hub.theme-morning .tertiary-action,
:global(.theme-morning) .multiplayer-hub .tertiary-action,
:global([data-theme="morning"]) .multiplayer-hub .tertiary-action {
  border-color: rgba(180, 83, 9, 0.35) !important;
}

.multiplayer-hub.theme-morning .tertiary-action:hover,
:global(.theme-morning) .multiplayer-hub .tertiary-action:hover,
:global([data-theme="morning"]) .multiplayer-hub .tertiary-action:hover {
  border-color: #b45309 !important;
  box-shadow: 0 0 0 2px #fcd34d, 0 12px 28px rgba(180, 83, 9, 0.2) !important;
}

.multiplayer-hub.theme-morning .shop-action,
:global(.theme-morning) .multiplayer-hub .shop-action,
:global([data-theme="morning"]) .multiplayer-hub .shop-action {
  border-color: rgba(147, 51, 234, 0.35) !important;
}

.multiplayer-hub.theme-morning .shop-action:hover,
:global(.theme-morning) .multiplayer-hub .shop-action:hover,
:global([data-theme="morning"]) .multiplayer-hub .shop-action:hover {
  border-color: #9333ea !important;
  box-shadow: 0 0 0 2px #e9d5ff, 0 12px 28px rgba(147, 51, 234, 0.2) !important;
}

.multiplayer-hub.theme-morning .card-info h3,
:global(.theme-morning) .multiplayer-hub .card-info h3,
:global([data-theme="morning"]) .multiplayer-hub .card-info h3 {
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .card-info p,
:global(.theme-morning) .multiplayer-hub .card-info p,
:global([data-theme="morning"]) .multiplayer-hub .card-info p {
  color: #69543f !important;
}

.multiplayer-hub.theme-morning .play-btn,
:global(.theme-morning) .multiplayer-hub .play-btn,
:global([data-theme="morning"]) .multiplayer-hub .play-btn {
  color: #8F642D !important;
}

.multiplayer-hub.theme-morning .action-card:hover .play-btn,
:global(.theme-morning) .multiplayer-hub .action-card:hover .play-btn,
:global([data-theme="morning"]) .multiplayer-hub .action-card:hover .play-btn {
  color: #5C3E14 !important;
}

/* History Section */
.multiplayer-hub.theme-morning .section-title,
:global(.theme-morning) .multiplayer-hub .section-title,
:global([data-theme="morning"]) .multiplayer-hub .section-title {
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .history-card,
:global(.theme-morning) .multiplayer-hub .history-card,
:global([data-theme="morning"]) .multiplayer-hub .history-card {
  background: #FFFDF8 !important;
  border: 1px solid #D8C39E !important;
  box-shadow: 0 0 0 1px #EAD4AA, 0 2px 10px rgba(143, 100, 45, 0.08) !important;
}

.multiplayer-hub.theme-morning .history-card.won,
:global(.theme-morning) .multiplayer-hub .history-card.won,
:global([data-theme="morning"]) .multiplayer-hub .history-card.won {
  border-left: 4px solid #16a34a !important;
}

.multiplayer-hub.theme-morning .history-card.lost,
:global(.theme-morning) .multiplayer-hub .history-card.lost,
:global([data-theme="morning"]) .multiplayer-hub .history-card.lost {
  border-left: 4px solid #dc2626 !important;
}

.multiplayer-hub.theme-morning .history-card.draw,
:global(.theme-morning) .multiplayer-hub .history-card.draw,
:global([data-theme="morning"]) .multiplayer-hub .history-card.draw {
  border-left: 4px solid #d97706 !important;
}

.multiplayer-hub.theme-morning .outcome-badge.won,
:global(.theme-morning) .multiplayer-hub .outcome-badge.won,
:global([data-theme="morning"]) .multiplayer-hub .outcome-badge.won {
  background: rgba(22, 163, 74, 0.12) !important;
  color: #15803d !important;
  border: 1px solid #16a34a !important;
}

.multiplayer-hub.theme-morning .outcome-badge.lost,
:global(.theme-morning) .multiplayer-hub .outcome-badge.lost,
:global([data-theme="morning"]) .multiplayer-hub .outcome-badge.lost {
  background: rgba(220, 38, 38, 0.12) !important;
  color: #b91c1c !important;
  border: 1px solid #dc2626 !important;
}

.multiplayer-hub.theme-morning .outcome-badge.draw,
:global(.theme-morning) .multiplayer-hub .outcome-badge.draw,
:global([data-theme="morning"]) .multiplayer-hub .outcome-badge.draw {
  background: rgba(217, 119, 6, 0.12) !important;
  color: #b45309 !important;
  border: 1px solid #d97706 !important;
}

.multiplayer-hub.theme-morning .battle-date,
:global(.theme-morning) .multiplayer-hub .battle-date,
:global([data-theme="morning"]) .multiplayer-hub .battle-date {
  color: #77634F !important;
}

.multiplayer-hub.theme-morning .opp-name,
:global(.theme-morning) .multiplayer-hub .opp-name,
:global([data-theme="morning"]) .multiplayer-hub .opp-name {
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .score-info,
:global(.theme-morning) .multiplayer-hub .score-info,
:global([data-theme="morning"]) .multiplayer-hub .score-info {
  color: #69543f !important;
}

.multiplayer-hub.theme-morning .pos-rating,
:global(.theme-morning) .multiplayer-hub .pos-rating,
:global([data-theme="morning"]) .multiplayer-hub .pos-rating {
  color: #15803d !important;
}

.multiplayer-hub.theme-morning .neg-rating,
:global(.theme-morning) .multiplayer-hub .neg-rating,
:global([data-theme="morning"]) .multiplayer-hub .neg-rating {
  color: #b91c1c !important;
}

.multiplayer-hub.theme-morning .coins-earned,
:global(.theme-morning) .multiplayer-hub .coins-earned,
:global([data-theme="morning"]) .multiplayer-hub .coins-earned {
  color: #b45309 !important;
}

.multiplayer-hub.theme-morning .xp-earned,
:global(.theme-morning) .multiplayer-hub .xp-earned,
:global([data-theme="morning"]) .multiplayer-hub .xp-earned {
  color: #8F642D !important;
}

.multiplayer-hub.theme-morning .empty-state,
.multiplayer-hub.theme-morning .loading-state,
:global(.theme-morning) .multiplayer-hub .empty-state,
:global(.theme-morning) .multiplayer-hub .loading-state,
:global([data-theme="morning"]) .multiplayer-hub .empty-state,
:global([data-theme="morning"]) .multiplayer-hub .loading-state {
  background: #FFFDF8 !important;
  border: 1px solid #D8C39E !important;
  box-shadow: 0 0 0 1px #EAD4AA, 0 2px 10px rgba(143, 100, 45, 0.08) !important;
  color: #77634F !important;
}

@media (max-width: 768px) {
  .multiplayer-hub.theme-morning .history-rewards,
  :global(.theme-morning) .multiplayer-hub .history-rewards,
  :global([data-theme="morning"]) .multiplayer-hub .history-rewards {
    border-top: 1px solid rgba(184, 138, 66, 0.25) !important;
  }
}

/* Arena Locked Screen */
.multiplayer-hub.theme-morning .locked-card-box,
:global(.theme-morning) .multiplayer-hub .locked-card-box,
:global([data-theme="morning"]) .multiplayer-hub .locked-card-box {
  background: #FFFDF8 !important;
  border: 1.5px solid #C89F5B !important;
  box-shadow: 0 10px 40px rgba(143, 100, 45, 0.18), 0 0 25px rgba(220, 38, 38, 0.1) !important;
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .locked-heading,
:global(.theme-morning) .multiplayer-hub .locked-heading,
:global([data-theme="morning"]) .multiplayer-hub .locked-heading {
  color: #8F642D !important;
}

.multiplayer-hub.theme-morning .locked-desc,
:global(.theme-morning) .multiplayer-hub .locked-desc,
:global([data-theme="morning"]) .multiplayer-hub .locked-desc {
  color: #5C4433 !important;
}

.multiplayer-hub.theme-morning .level-progress-pill,
:global(.theme-morning) .multiplayer-hub .level-progress-pill,
:global([data-theme="morning"]) .multiplayer-hub .level-progress-pill {
  background: rgba(220, 38, 38, 0.08) !important;
  border: 1px solid rgba(220, 38, 38, 0.3) !important;
  color: #991b1b !important;
}

.multiplayer-hub.theme-morning .locked-sub-desc,
:global(.theme-morning) .multiplayer-hub .locked-sub-desc,
:global([data-theme="morning"]) .multiplayer-hub .locked-sub-desc {
  color: #77634F !important;
}

.multiplayer-hub.theme-morning .btn-return-realm,
:global(.theme-morning) .multiplayer-hub .btn-return-realm,
:global([data-theme="morning"]) .multiplayer-hub .btn-return-realm {
  background: linear-gradient(180deg, #FFF4DF 0%, #FFE9BF 100%) !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.15) !important;
}

.multiplayer-hub.theme-morning .btn-return-realm:hover,
:global(.theme-morning) .multiplayer-hub .btn-return-realm:hover,
:global([data-theme="morning"]) .multiplayer-hub .btn-return-realm:hover {
  background: linear-gradient(180deg, #FFFFFF 0%, #FFF0D0 100%) !important;
  border-color: #8F642D !important;
  color: #1F150D !important;
  box-shadow: 0 0 15px rgba(184, 138, 66, 0.3) !important;
}

.multiplayer-hub.theme-morning .arena-preview-ribbon,
:global(.theme-morning) .multiplayer-hub .arena-preview-ribbon,
:global([data-theme="morning"]) .multiplayer-hub .arena-preview-ribbon {
  background: #FFF4DF !important;
  border: 1px solid #D8B980 !important;
  color: #78350f !important;
}

/* ══════════════════════════════════════════════════════════════
   RANKED COIN WAGER MODAL STYLES
   ══════════════════════════════════════════════════════════════ */
.bet-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.bet-modal-card {
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 2px solid rgba(181, 148, 16, 0.45);
  box-shadow: 0 0 0 3px #050608, 0 25px 60px rgba(0, 0, 0, 0.95);
  border-radius: 6px;
  width: 100%;
  max-width: 640px;
  padding: 2.2rem 2.5rem;
  color: #e2d9cc;
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.8rem;
}

.modal-badge {
  display: inline-block;
  font-size: 0.7rem;
  letter-spacing: 0.08em;
  background: rgba(139, 26, 26, 0.3);
  color: #ff6b6b;
  border: 1px solid rgba(230, 57, 70, 0.5);
  padding: 0.25rem 0.6rem;
  border-radius: 2px;
  margin-bottom: 0.4rem;
}

.modal-title {
  font-size: 1.35rem;
  color: #ffd700;
  letter-spacing: 0.04em;
  margin: 0;
}

.modal-close-btn {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #8b8070;
  font-size: 1rem;
  cursor: pointer;
  border-radius: 3px;
  padding: 0.25rem 0.6rem;
  transition: all 0.2s;
}
.modal-close-btn:hover {
  color: #f5efe6;
  border-color: #ffd700;
}

.modal-subtitle {
  font-size: 0.78rem;
  color: #94a3b8;
  line-height: 1.5;
  margin: 0 0 1.2rem;
}

.user-balance-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(8, 9, 12, 0.9);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 1px #050608;
  border-radius: 4px;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1.5rem;
}

.balance-label {
  font-size: 0.75rem;
  color: #8b8070;
  letter-spacing: 0.05em;
}

.balance-amount {
  font-size: 0.9rem;
  color: #ffd700;
  font-weight: bold;
}

.bet-tiers-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.bet-tier-card {
  background: #08090c;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px;
  padding: 1.2rem 1.1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  display: flex;
  flex-direction: column;
}

.bet-tier-card:hover:not(.is-disabled) {
  border-color: #ffd700;
  transform: translateY(-2px);
  box-shadow: 0 0 0 2px #050608, 0 8px 20px rgba(181, 148, 16, 0.25);
}

.bet-tier-card.is-selected {
  border-color: #ffd700;
  background: linear-gradient(180deg, rgba(181, 148, 16, 0.15) 0%, rgba(8, 9, 12, 0.95) 100%);
  box-shadow: 0 0 0 2px #ffd700, 0 0 15px rgba(255, 215, 0, 0.25);
}

.bet-tier-card.is-disabled {
  opacity: 0.45;
  cursor: not-allowed;
  filter: grayscale(0.6);
}

.tier-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.4rem;
}

.tier-amount {
  font-size: 1.2rem;
  color: #ffd700;
  font-weight: bold;
}

.tier-coin-icon {
  font-size: 1.3rem;
}

.tier-name {
  font-size: 0.8rem;
  color: #f8fafc;
  margin-bottom: 0.35rem;
}

.tier-pot {
  font-size: 0.7rem;
  color: #4ade80;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}

.tier-desc {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1.4;
}

.insufficient-badge {
  margin-top: 0.6rem;
  font-size: 0.62rem;
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  border: 1px solid rgba(239, 68, 68, 0.4);
  padding: 0.2rem 0.4rem;
  border-radius: 2px;
  text-align: center;
}

.elo-notice-box {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.3);
  border-radius: 4px;
  padding: 0.9rem 1.1rem;
  margin-bottom: 1.6rem;
}

.elo-notice-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.elo-notice-text strong {
  font-size: 0.72rem;
  display: block;
  margin-bottom: 0.3rem;
  letter-spacing: 0.04em;
}

.elo-notice-text p {
  font-size: 0.7rem;
  color: #cbd5e1;
  line-height: 1.5;
  margin: 0;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.btn-cancel-modal {
  padding: 0.85rem 1.5rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #94a3b8;
  font-size: 0.75rem;
  cursor: pointer;
  border-radius: 3px;
  transition: all 0.2s;
}
.btn-cancel-modal:hover {
  color: #f8fafc;
  border-color: rgba(255, 255, 255, 0.4);
}

.btn-confirm-bet {
  flex: 1;
  padding: 0.85rem 1.5rem;
  background: linear-gradient(180deg, #b91c1c 0%, #7f1d1d 100%);
  border: 2px solid #ef4444;
  box-shadow: 0 0 0 2px #050608, 0 4px 15px rgba(220, 38, 38, 0.4);
  color: #fff;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  cursor: pointer;
  border-radius: 3px;
  transition: all 0.2s;
}
.btn-confirm-bet:hover:not(:disabled) {
  background: linear-gradient(180deg, #dc2626 0%, #991b1b 100%);
  border-color: #f87171;
  transform: translateY(-1px);
}
.btn-confirm-bet:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  filter: grayscale(0.5);
  box-shadow: none;
}

/* Light / Morning Mode support for Bet Modal */
.multiplayer-hub.theme-morning .bet-modal-card,
:global(.theme-morning) .bet-modal-card,
:global([data-theme="morning"]) .bet-modal-card {
  background: linear-gradient(180deg, #FFFDF8 0%, #FFF4DF 100%) !important;
  border: 2px solid #C89F5B !important;
  box-shadow: 0 0 0 3px #EAD4AA, 0 25px 60px rgba(143, 100, 45, 0.25) !important;
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .modal-title,
:global(.theme-morning) .modal-title,
:global([data-theme="morning"]) .modal-title {
  color: #946800 !important;
}

.multiplayer-hub.theme-morning .modal-subtitle,
:global(.theme-morning) .modal-subtitle,
:global([data-theme="morning"]) .modal-subtitle {
  color: #69543f !important;
}

.multiplayer-hub.theme-morning .user-balance-bar,
:global(.theme-morning) .user-balance-bar,
:global([data-theme="morning"]) .user-balance-bar {
  background: #FFFDF8 !important;
  border-color: #D8B980 !important;
}

.multiplayer-hub.theme-morning .bet-tier-card,
:global(.theme-morning) .bet-tier-card,
:global([data-theme="morning"]) .bet-tier-card {
  background: #FFFDF8 !important;
  border-color: #D8C39E !important;
}

.multiplayer-hub.theme-morning .bet-tier-card.is-selected,
:global(.theme-morning) .bet-tier-card.is-selected,
:global([data-theme="morning"]) .bet-tier-card.is-selected {
  border-color: #B88A42 !important;
  background: linear-gradient(180deg, #FFF0D0 0%, #FFFDF8 100%) !important;
  box-shadow: 0 0 0 2px #B88A42 !important;
}

.multiplayer-hub.theme-morning .tier-name,
:global(.theme-morning) .tier-name,
:global([data-theme="morning"]) .tier-name {
  color: #2C1E14 !important;
}

.multiplayer-hub.theme-morning .tier-pot,
:global(.theme-morning) .tier-pot,
:global([data-theme="morning"]) .tier-pot {
  color: #15803d !important;
}

.multiplayer-hub.theme-morning .tier-desc,
:global(.theme-morning) .tier-desc,
:global([data-theme="morning"]) .tier-desc {
  color: #69543f !important;
}

.multiplayer-hub.theme-morning .elo-notice-box,
:global(.theme-morning) .elo-notice-box,
:global([data-theme="morning"]) .elo-notice-box {
  background: #FFF4DF !important;
  border-color: #D8B980 !important;
}

.multiplayer-hub.theme-morning .elo-notice-text strong,
:global(.theme-morning) .elo-notice-text strong,
:global([data-theme="morning"]) .elo-notice-text strong {
  color: #946800 !important;
}

.multiplayer-hub.theme-morning .elo-notice-text p,
:global(.theme-morning) .elo-notice-text p,
:global([data-theme="morning"]) .elo-notice-text p {
  color: #5C4433 !important;
}

.multiplayer-hub.theme-morning .btn-cancel-modal,
:global(.theme-morning) .btn-cancel-modal,
:global([data-theme="morning"]) .btn-cancel-modal {
  border-color: #D8C39E !important;
  color: #77634F !important;
}
</style>
