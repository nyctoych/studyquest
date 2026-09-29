<template>
  <div class="achievements-page game-module-env module-env-records animate-slide-up">
    <!-- Header -->
    <div class="ach-header">
      <div>
        <span class="ach-tag flex items-center gap-1.5"><PixelEmoji emoji="trophy" size="xs" /> Hall of Fame</span>
        <h1 class="page-title">Achievements &amp; Milestones</h1>
        <p class="page-subtitle">
          Unlock trophies by reaching study milestones, maintaining daily streaks, vanquishing bosses, and mastering curriculum domains.
        </p>
      </div>

      <!-- Achievement summary score card -->
      <div class="glass-card score-card">
        <div class="score-icon"><PixelEmoji emoji="shield" size="lg" /></div>
        <div class="score-data">
          <span class="score-val">{{ unlockedCount }} / {{ achievements.length }}</span>
          <span class="score-lbl">Trophies Unlocked</span>
        </div>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="ach-category-tabs">
      <button
        v-for="cat in categories"
        :key="cat.id"
        class="cat-tab-btn flex items-center gap-1.5"
        :class="{ active: activeCategory === cat.id }"
        @click="activeCategory = cat.id"
      >
        <PixelEmoji :emoji="cat.icon" size="xs" />
        <span>{{ cat.name }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading your achievements...</p>
    </div>

    <!-- Achievements Grid -->
    <div v-else class="ach-grid">
      <div
        v-for="ach in filteredAchievements"
        :key="ach.id"
        class="glass-card ach-card"
        :class="{
          'ach-unlocked': isUnlocked(ach),
          'ach-locked': !isUnlocked(ach)
        }"
      >
        <div class="ach-card-top">
          <div class="ach-badge-icon" :class="{ 'badge-glow': isUnlocked(ach) }">
            <PixelEmoji :emoji="getAchievementIcon(ach)" size="md" />
          </div>
          <div class="ach-status-tag" :class="isUnlocked(ach) ? 'tag-unlocked' : 'tag-locked'">
            <span v-if="isUnlocked(ach)">✓ Unlocked</span>
            <span v-else class="flex items-center gap-1"><PixelEmoji emoji="lock" size="xs" /> Locked</span>
          </div>
        </div>

        <div class="ach-info">
          <h3 class="ach-title">{{ ach.name }}</h3>
          <p class="ach-desc">{{ ach.description }}</p>
        </div>

        <!-- Progress bar for locked achievements -->
        <div v-if="!isUnlocked(ach)" class="ach-progress-wrap">
          <div class="ach-progress-labels">
            <span>Progress</span>
            <span>{{ ach.current_progress || 0 }} / {{ ach.target_progress || 1 }}</span>
          </div>
          <div class="progress-bar-wrap">
            <div
              class="progress-bar-fill"
              :style="{ width: getProgressPercent(ach) + '%' }"
            ></div>
          </div>
        </div>

        <!-- Rewards Chips -->
        <div class="ach-card-footer">
          <div class="ach-rewards">
            <span class="reward-pill xp flex items-center gap-1"><PixelEmoji emoji="star" size="xs" /> +{{ ach.xp_reward || 100 }} XP</span>
            <span class="reward-pill points flex items-center gap-1"><PixelEmoji emoji="coin" size="xs" /> +{{ ach.points_reward || 25 }} Coins</span>
          </div>

          <button
            v-if="isUnlocked(ach) && !ach.is_claimed"
            class="btn btn-success btn-sm"
            @click="claimAchievement(ach)"
          >
            Claim
          </button>
          <span v-else-if="isUnlocked(ach) && ach.is_claimed" class="claimed-text">
            ✓ Claimed
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { achievementService } from '../services/achievementService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const auth = useAuthStore()
const toast = useToastStore()

const achievements = ref([])
const activeCategory = ref('all')
const loading = ref(true)

const categories = [
  { id: 'all', name: 'All Trophies', icon: 'trophy' },
  { id: 'quests', name: 'Quests', icon: 'sword' },
  { id: 'streak', name: 'Streaks', icon: 'fire' },
  { id: 'boss', name: 'Boss Battles', icon: 'ogre' },
  { id: 'master', name: 'Mastery', icon: 'crown' }
]

const defaultAchievements = [
  { id: 1, name: 'First Steps of the Scholar', category: 'quests', icon: 'scroll', description: 'Complete your first academic quest.', xp_reward: 100, points_reward: 30, unlocked: true, is_claimed: true, target_progress: 1, current_progress: 1 },
  { id: 2, name: 'Quest Novice', category: 'quests', icon: 'sword', description: 'Complete 10 academic quests.', xp_reward: 250, points_reward: 75, unlocked: false, is_claimed: false, target_progress: 10, current_progress: 4 },
  { id: 3, name: 'Ignited Passion', category: 'streak', icon: 'fire', description: 'Maintain a 3-day daily study streak.', xp_reward: 150, points_reward: 50, unlocked: true, is_claimed: false, target_progress: 3, current_progress: 3 },
  { id: 4, name: 'Unstoppable Momentum', category: 'streak', icon: 'lightning', description: 'Maintain a 7-day daily study streak.', xp_reward: 400, points_reward: 120, unlocked: false, is_claimed: false, target_progress: 7, current_progress: 3 },
  { id: 5, name: 'Giant Slayer', category: 'boss', icon: 'sword', description: 'Defeat your first Academic Boss in the Arena.', xp_reward: 300, points_reward: 100, unlocked: false, is_claimed: false, target_progress: 1, current_progress: 0 },
  { id: 7, name: 'Grandmaster of Wisdom', category: 'master', icon: 'crown', description: 'Reach Character Level 10.', xp_reward: 1000, points_reward: 350, unlocked: false, is_claimed: false, target_progress: 10, current_progress: auth.level || 1 }
]

function getCategoryIcon(cat) {
  if (cat === 'quests') return 'sword'
  if (cat === 'streak') return 'fire'
  if (cat === 'boss') return 'ogre'
  if (cat === 'master') return 'crown'
  return 'trophy'
}

const achievementIconMap = {
  1: '⭐', 2: '📜', 3: '⚔️', 4: '🏆', 5: '👑',
  6: '⬆️', 7: '💎', 8: '🔥', 9: '🌟', 10: '🏅',
  11: '🐉', 12: '⚔️', 13: '🌱', 14: '🌷', 15: '💰', 16: '✨',
  'First Quest': '⭐', 'Quest Apprentice': '📜', 'Quest Knight': '⚔️',
  'Quest Master': '🏆', 'Quest Legend': '👑', 'Level Up!': '⬆️',
  'Level 10': '💎', '3 Day Streak': '🔥', 'Week Warrior': '🌟',
  'Month Master': '🏅', 'Dragon Slayer': '🐉', 'Boss Slayer': '⚔️',
  'Rich Student': '💰',
  'Epic Scholar': '✨',
}

function getAchievementIcon(ach) {
  if (!ach) return '🏆'
  const icon = ach.icon
  if (icon && !icon.includes('ƒ') && !icon.includes('Ô') && !icon.includes('º') && !icon.includes('É') && !icon.includes('Å') && !icon.includes('œ') && !icon.includes('¿')) {
    return icon
  }
  return achievementIconMap[ach.id] || achievementIconMap[ach.name] || getCategoryIcon(ach.category) || '🏆'
}

function isUnlocked(ach) {
  return ach.unlocked || ach.is_unlocked || ach.unlocked_at !== null && ach.unlocked_at !== undefined
}

function getProgressPercent(ach) {
  if (!ach.target_progress) return 0
  const cur = ach.current_progress || 0
  return Math.min(100, Math.round((cur / ach.target_progress) * 100))
}

const unlockedCount = computed(() => {
  return achievements.value.filter(a => isUnlocked(a)).length
})

const filteredAchievements = computed(() => {
  if (activeCategory.value === 'all') return achievements.value
  return achievements.value.filter(a => a.category === activeCategory.value)
})

async function fetchAchievements() {
  loading.value = true
  try {
    const res = await achievementService.getAll()
    const list = res.data?.achievements || res.data?.data
    if (res.data && res.data.success && list?.length) {
      achievements.value = list
    } else {
      achievements.value = defaultAchievements
    }
  } catch (err) {
    achievements.value = defaultAchievements
  } finally {
    loading.value = false
  }
}

async function claimAchievement(ach) {
  ach.is_claimed = true
  toast.add(`🏆 Claimed ${ach.name}! +${ach.xp_reward} XP and +${ach.points_reward} Coins!`, 'success')
  await auth.fetchUser()
}

onMounted(() => {
  fetchAchievements()
})
</script>

<style scoped>
.achievements-page {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  position: relative;
  z-index: 1;
}

.ach-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.25rem;
  padding: 1.5rem 1.75rem;
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.8);
  border-radius: 4px;
}

.ach-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.4rem;
}

.page-title {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 800;
  color: #e2d9cc;
  letter-spacing: 0.03em;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.page-subtitle {
  color: #a09789;
  font-size: 0.95rem;
  max-width: 650px;
  margin-top: 0.35rem;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  letter-spacing: 0.03em;
  line-height: 1.45;
}

.score-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1.4rem;
  border-radius: 4px;
  border: 1px solid rgba(181, 148, 16, 0.4);
  background: #08090c;
  box-shadow: 0 0 0 2px #050608;
}

.score-icon { font-size: 2rem; filter: drop-shadow(0 0 6px rgba(181, 148, 16, 0.5)); }
.score-val { display: block; font-family: 'Press Start 2P', monospace; font-size: 1.05rem; color: #e6c845; }
.score-lbl { font-family: 'Cinzel', serif; font-size: 0.7rem; color: #8b8070; text-transform: uppercase; letter-spacing: 0.08em; }

.ach-category-tabs {
  display: flex;
  gap: 0.5rem;
  background: #08090c;
  padding: 0.35rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608;
  overflow-x: auto;
}

.cat-tab-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.1rem;
  background: transparent;
  border: 1px solid transparent;
  color: #8b8070;
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.cat-tab-btn:hover {
  color: #e2d9cc;
  background: rgba(255, 255, 255, 0.04);
}

.cat-tab-btn.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border-color: #b91c1c;
  box-shadow: 0 0 0 1px #050608;
}

.ach-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.ach-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.5rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
  transition: transform 0.25s ease, border-color 0.25s ease;
}

.ach-card:hover {
  transform: translateY(-3px);
}

.ach-card.ach-unlocked {
  border-color: rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(181, 148, 16, 0.2);
}

.ach-card.ach-locked {
  opacity: 0.6;
  filter: grayscale(0.7);
}

.ach-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.1rem;
}

.ach-badge-icon {
  width: 52px;
  height: 52px;
  background: #08090c;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 0 2px #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
}

.badge-glow {
  background: radial-gradient(circle, rgba(181, 148, 16, 0.25) 0%, #08090c 100%);
  border-color: #b59410;
  filter: drop-shadow(0 0 6px rgba(181, 148, 16, 0.4));
}

.ach-status-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.6rem;
  box-shadow: 0 0 0 1px #050608;
}

.tag-unlocked { background: rgba(45, 106, 79, 0.3); color: #52b788; border: 1px solid #52b788; }
.tag-locked { background: rgba(20, 20, 25, 0.6); color: #8b8070; border: 1px solid rgba(255, 255, 255, 0.08); }

.ach-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f5efe6;
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}

.ach-desc {
  font-family: 'VT323', monospace;
  font-size: 1.05rem;
  color: #a09789;
  line-height: 1.4;
  margin-bottom: 1rem;
}

.ach-progress-wrap {
  margin-bottom: 1rem;
}

.ach-progress-labels {
  display: flex;
  justify-content: space-between;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8b8070;
  margin-bottom: 0.4rem;
}

.progress-bar-wrap {
  width: 100%;
  height: 8px;
  background: #050608;
  border: 1px solid #1c1e29;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b1a1a, #b91c1c);
  transition: width 0.3s;
}

.ach-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 0.85rem;
}

.ach-rewards {
  display: flex;
  gap: 0.4rem;
}

.reward-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.3rem 0.5rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.reward-pill.xp { background: rgba(181, 148, 16, 0.15); color: #e6c845; border-color: rgba(181, 148, 16, 0.4); }
.reward-pill.points { background: rgba(147, 51, 234, 0.15); color: #c084fc; border-color: rgba(147, 51, 234, 0.4); }

.claimed-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #52b788;
}

@media (max-width: 768px) {
  .ach-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  .page-title {
    font-size: 1.6rem;
  }
  .score-card {
    justify-content: space-between;
  }
  .ach-grid {
    grid-template-columns: 1fr;
  }
  .ach-card {
    padding: 1.15rem;
  }
}

@media (max-width: 480px) {
  .ach-card-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  .ach-card-footer .btn {
    width: 100%;
  }
}
</style>
