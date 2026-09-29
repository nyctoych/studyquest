<template>
  <div class="dashboard animate-slide-up">

    <!-- Hero Banner -->
    <div class="hero-banner">
      <div class="hero-content">
        <p class="hero-greeting">{{ greeting }}, <strong>{{ auth.user?.username }}</strong>!</p>
        <h1 class="hero-title">Quest Explorer! <span class="sparkle">✨</span></h1>
        <p class="hero-sub">Progress isn't about being perfect, it's about showing up. Keep going!</p>
        <!-- XP Progress -->
        <div class="hero-xp">
          <div class="xp-label">
            <span>Lv.{{ auth.level }}</span>
            <span>{{ xpInLevel }} / {{ xpForNext }} XP</span>
          </div>
          <div class="progress-bar-wrap" style="height:10px">
            <div class="progress-bar-fill" :style="{ width: xpPercent + '%' }"></div>
          </div>
        </div>
      </div>
      <div class="hero-avatar">
        <AvatarImg :style-name="auth.avatarStyle" :seed="auth.avatarSeed" :options="auth.avatarOpts" :size="130" />
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card xp-card">
        <div class="stat-icon">⭐</div>
        <div class="stat-info">
          <div class="stat-value">{{ formatXP(auth.xp) }}</div>
          <div class="stat-label">Total XP</div>
          <div class="stat-sub">Next: {{ xpForNext - xpInLevel }} XP</div>
        </div>
      </div>
      <div class="stat-card pts-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
          <div class="stat-value">{{ formatPoints(auth.points) }}</div>
          <div class="stat-label">Points</div>
          <div class="stat-sub">Shop &amp; Rewards</div>
        </div>
      </div>
      <div class="stat-card streak-card">
        <div class="stat-icon">🔥</div>
        <div class="stat-info">
          <div class="stat-value">{{ auth.streak }}</div>
          <div class="stat-label">Day Streak</div>
          <div class="stat-sub">Keep it up!</div>
        </div>
      </div>
      <div class="stat-card ach-card">
        <div class="stat-icon">🏆</div>
        <div class="stat-info">
          <div class="stat-value">{{ stats?.achievements ?? 0 }}</div>
          <div class="stat-label">Achievements</div>
          <router-link to="/achievements" class="stat-sub link">View all →</router-link>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">

      <!-- Today's Study Path & Quests -->
      <div class="glass-card dash-card today-study-dash-card">
        <div class="card-header">
          <h3>🎯 Today's Study Hall</h3>
          <router-link to="/study" class="view-all">All Domains →</router-link>
        </div>

        <div v-if="loading" class="loading-dots">Loading…</div>

        <!-- Active Psychology Module Recommendation -->
        <div v-else-if="todayStudy" class="dash-study-box" :style="{ '--dom-color': todayStudy.domain_color || '#a855f7' }">
          <div class="dash-study-header">
            <span class="dash-dom-tag" :style="{ color: todayStudy.domain_color || '#a855f7' }">
              {{ todayStudy.domain_icon || '🧠' }} {{ todayStudy.domain_name }}
            </span>
            <span class="dash-rec-tag">{{ todayStudy.reason }}</span>
          </div>

          <h4 class="dash-part-title">Part {{ todayStudy.part_number }}: {{ todayStudy.part_title }}</h4>
          <p class="dash-part-desc">Review and master DSM-5 diagnostic criteria and psychopathology principles.</p>

          <router-link :to="`/study/${todayStudy.domain_id}/part/${todayStudy.part_number}`" class="btn btn-primary btn-sm dash-resume-btn">
            ⚡ Study Part {{ todayStudy.part_number }} →
          </router-link>

          <!-- Secondary custom tasks if any exist -->
          <div v-if="todayQuests.length > 0" class="custom-sub-tasks">
            <div class="custom-sub-header">Custom Academic Tasks ({{ todayQuests.length }})</div>
            <div v-for="quest in todayQuests.slice(0, 2)" :key="quest.id" class="mini-quest-item">
              <span class="mini-quest-title">{{ quest.title }}</span>
              <span class="mini-quest-xp">+{{ quest.xp_reward }} XP</span>
            </div>
          </div>
        </div>

        <!-- Fallback if no study item and no quests -->
        <div v-else class="empty-state" style="padding:2rem">
          <div class="empty-state-icon">🧠</div>
          <p style="color:var(--color-muted)">Start your Psychology study journey!</p>
          <router-link to="/quests" class="btn btn-primary btn-sm">Explore Study Paths →</router-link>
        </div>
      </div>

      <!-- Streak Calendar -->
      <div class="glass-card dash-card">
        <div class="card-header">
          <h3>📅 Study Streak</h3>
          <span class="streak-badge">🔥 {{ auth.streak }} days</span>
        </div>
        <div class="streak-week">
          <div v-for="(day, i) in weekDays" :key="i" class="streak-day">
            <div class="day-label">{{ day.label }}</div>
            <div class="day-dot" :class="{ active: day.active, today: day.isToday }">
              {{ day.active ? '🔥' : '○' }}
            </div>
          </div>
        </div>
        <div class="streak-message" v-if="auth.streak >= 3">
          <span class="streak-fire">🔥</span>
          <strong>{{ auth.streak }} days in a row!</strong>
          <span> You're on fire!</span>
        </div>
      </div>

      <!-- Psychology Adaptive Readiness & Weak Topic Engine -->
      <div class="glass-card dash-card readiness-card">
        <div class="card-header">
          <div class="header-left">
            <span class="readiness-tag">PSYCHOLOGY COGNITIVE ENGINE</span>
            <h3>🧠 Your Psychology Readiness</h3>
          </div>
          <span class="tier-pill" :class="'tier-' + (adaptiveProfile?.adaptive_difficulty || 'intermediate')">
            {{ formatTier(adaptiveProfile?.adaptive_difficulty || 'intermediate') }}
          </span>
        </div>

        <div class="readiness-body" v-if="adaptiveProfile">
          <!-- Level vs Adaptive Difficulty -->
          <div class="readiness-stats-bar">
            <div class="r-stat">
              <span class="r-label">Account Level</span>
              <strong class="r-val">Lv.{{ auth.level || 1 }}</strong>
              <span class="r-sub">Baseline: {{ formatTier(adaptiveProfile.baseline_difficulty) }}</span>
            </div>
            <div class="r-stat">
              <span class="r-label">Demonstrated Mastery</span>
              <strong class="r-val">{{ Math.round(adaptiveProfile.mastery_score) }}%</strong>
              <span class="r-sub">{{ adaptiveProfile.adaptive_state }}</span>
            </div>
            <div class="r-stat">
              <span class="r-label">Recent Accuracy</span>
              <strong class="r-val text-success">{{ Math.round(adaptiveProfile.recent_accuracy) }}%</strong>
              <span class="r-sub">🔥 {{ adaptiveProfile.current_streak }} streak</span>
            </div>
          </div>

          <!-- Mastery Progress Bar -->
          <div class="mastery-bar-container">
            <div class="mastery-track">
              <div 
                class="mastery-fill" 
                :style="{ width: Math.min(100, Math.max(5, adaptiveProfile.mastery_score)) + '%' }"
                :class="getMasteryColorClass(adaptiveProfile.mastery_score)"
              ></div>
            </div>
          </div>

          <!-- Adaptive System Explanation Callout -->
          <div class="adaptive-explanation-badge" v-if="adaptiveProfile.user_explanation">
            <span class="ae-icon">💡</span>
            <p class="ae-text">{{ adaptiveProfile.user_explanation }}</p>
          </div>

          <!-- Domains Breakdown: Strong vs Needs Practice -->
          <div class="domains-row">
            <div class="domain-pill-group strong-group" v-if="adaptiveProfile.strongest_category">
              <span class="group-label">🌟 Strong Area:</span>
              <span class="group-item strong-item">
                {{ adaptiveProfile.strongest_category.category_name }}
                <span class="acc-chip" v-if="adaptiveProfile.strongest_category.accuracy">
                  {{ Math.round(adaptiveProfile.strongest_category.accuracy) }}%
                </span>
              </span>
            </div>

            <div class="domain-pill-group weak-group" v-if="adaptiveProfile.weakest_category || adaptiveProfile.weakest_topic">
              <span class="group-label">⚠️ Needs Practice:</span>
              <span class="group-item weak-item">
                {{ adaptiveProfile.weakest_topic ? adaptiveProfile.weakest_topic.topic_name : adaptiveProfile.weakest_category?.category_name }}
                <span class="acc-chip" v-if="adaptiveProfile.weakest_category?.accuracy">
                  {{ Math.round(adaptiveProfile.weakest_category.accuracy) }}%
                </span>
              </span>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="readiness-actions">
            <router-link 
              :to="adaptiveProfile.weakest_category ? `/practice?category_id=${adaptiveProfile.weakest_category.category_id}` : '/practice'"
              class="btn btn-primary btn-sm"
            >
              🎯 Practice {{ adaptiveProfile.weakest_topic?.topic_name || adaptiveProfile.weakest_category?.category_name || 'Weak Area' }}
            </router-link>
            <router-link to="/practice?mode=board_exam" class="btn btn-warning btn-sm board-btn">
              🏛️ Board Exam Prep
            </router-link>
            <router-link to="/practice" class="btn btn-secondary btn-sm">
              ⚡ Adaptive Drill
            </router-link>
          </div>
        </div>

        <div v-else class="weak-topic-empty">
          <div class="readiness-icon">🧠</div>
          <div class="readiness-info">
            <strong>Psychology Cognitive Engine Active</strong>
            <p class="empty-sub">Take an adaptive practice quiz to evaluate your Psychology baseline and uncover weak domains.</p>
          </div>
          <div class="readiness-actions">
            <router-link to="/practice" class="btn btn-primary btn-sm">🎯 Start Adaptive Practice</router-link>
          </div>
        </div>
      </div>

      <!-- Psychology Boss Quest Promo -->
      <div class="glass-card dash-card boss-promo">
        <div class="boss-promo-content">
          <div class="boss-tag">PSYCHOLOGY ARENA</div>
          <h3>👹 Psychology Bosses</h3>
          <p>Defeat guardians of Cognitive, Biological, Abnormal, Assessment, and Ethics domains to earn massive XP!</p>
          <router-link to="/boss" class="btn btn-primary">⚔️ Enter Boss Arena</router-link>
        </div>
        <div class="boss-promo-emoji">🧠</div>
      </div>

      <!-- Quick Stats -->
      <div class="glass-card dash-card">
        <div class="card-header">
          <h3>📊 Quest Stats</h3>
        </div>
        <div class="quest-stats">
          <div class="qs-row">
            <span class="qs-label">✅ Completed</span>
            <span class="qs-val success">{{ stats?.quests?.completed ?? 0 }}</span>
          </div>
          <div class="qs-row">
            <span class="qs-label">⏳ In Progress</span>
            <span class="qs-val">{{ stats?.quests?.in_progress ?? 0 }}</span>
          </div>
          <div class="qs-row">
            <span class="qs-label">📋 Pending</span>
            <span class="qs-val">{{ stats?.quests?.pending ?? 0 }}</span>
          </div>
          <div class="qs-row">
            <span class="qs-label">⚔️ Boss Wins</span>
            <span class="qs-val gold">{{ stats?.boss_wins ?? 0 }}</span>
          </div>
          <div class="qs-row">
            <span class="qs-label">🔥 Best Streak</span>
            <span class="qs-val streak">{{ auth.user?.longest_streak ?? 0 }} days</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { questService } from '../services/questService'
import { profileService } from '../services/profileService'
import { questionService } from '../services/questionService'
import { adaptiveService } from '../services/adaptiveService'
import { curriculumService } from '../services/curriculumService'
import { formatXP, formatPoints } from '../utils/formatters'
import AvatarImg from '../components/AvatarImg.vue'

const auth    = useAuthStore()
const toast   = useToastStore()
const stats   = ref(null)
const todayQuests = ref([])
const todayStudy = ref(null)
const weakArea = ref(null)
const adaptiveProfile = ref(null)
const loading = ref(true)

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Good morning'
  if (h < 17) return 'Good afternoon'
  return 'Good evening'
})

const xpInLevel  = computed(() => auth.stats?.xp_in_level  ?? 0)
const xpForNext  = computed(() => auth.stats?.xp_for_next_level ?? 1000)
const xpPercent  = computed(() => Math.min(100, Math.round((xpInLevel.value / xpForNext.value) * 100)))

const cognitiveTier = computed(() => {
  const lv = auth.level || 1
  if (lv <= 5) return { name: 'Tier 1: Foundations (Lv 1–5)', class: 'tier-beginner' }
  if (lv <= 15) return { name: 'Tier 2: Concept Application (Lv 6–15)', class: 'tier-intermediate' }
  if (lv <= 25) return { name: 'Tier 3: Advanced Analysis (Lv 16–25)', class: 'tier-advanced' }
  return { name: 'Tier 4: Board-Exam Level (Lv 26+)', class: 'tier-board' }
})

function formatTier(tier) {
  if (!tier) return 'Intermediate'
  return tier.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function getMasteryColorClass(score) {
  if (score >= 90) return 'mastery-gold'
  if (score >= 75) return 'mastery-green'
  if (score >= 60) return 'mastery-blue'
  if (score >= 40) return 'mastery-amber'
  return 'mastery-red'
}

const weekDays = computed(() => {
  const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']
  const today = new Date().getDay()
  // Simplified: mark today and streak days
  return days.map((label, i) => ({
    label,
    active: i < Math.min(auth.streak, 7),
    isToday: i === (today === 0 ? 6 : today - 1),
  }))
})

async function loadData() {
  loading.value = true
  try {
    const [statsRes, questsRes, weakRes, adaptRes, currTodayRes] = await Promise.all([
      profileService.stats(),
      questService.today(),
      questionService.getWeakAreas().catch(() => ({ data: { data: null } })),
      adaptiveService.getProfile().catch(() => ({ data: { profile: null } })),
      curriculumService.getTodayRecommendation().catch(() => ({ recommendation: null }))
    ])
    stats.value           = statsRes.data.stats
    todayQuests.value     = questsRes.data.quests ?? []
    todayStudy.value      = currTodayRes?.recommendation || questsRes.data?.today_study || null
    weakArea.value        = weakRes?.data?.data ?? null
    adaptiveProfile.value = adaptRes?.data?.profile || adaptRes?.data?.data || null
    auth.updateStats(statsRes.data.stats)
  } catch (e) {
    toast.error('Failed to load dashboard data')
  } finally {
    loading.value = false
  }
}

async function completeQuest(quest) {
  if (quest.status === 'completed') return
  try {
    const res = await questService.complete(quest.id)
    if (res.data.success) {
      quest.status = 'completed'
      const { xp_reward, point_reward, streak, new_achievements, user } = res.data
      auth.updateUser(user)
      toast.success(`⭐ +${xp_reward} XP  💰 +${point_reward} Points — Quest Complete! 🎉`)
      if (new_achievements?.length) {
        new_achievements.forEach(a => {
          setTimeout(() => toast.success(`🏆 Achievement Unlocked: ${a.name}!`, 6000), 1000)
        })
      }
    }
  } catch {
    toast.error('Failed to complete quest')
  }
}

onMounted(async () => {
  await loadData()
})
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #e2d9cc;
}

/* ══ HERO CITADEL BANNER ══ */
.hero-banner {
  background: linear-gradient(135deg, #181622 0%, #0c0d14 60%, #1a121d 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.9), 0 0 25px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  padding: 2rem 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  position: relative;
  overflow: hidden;
}
.hero-banner::before {
  content: '';
  position: absolute;
  top: -50%; left: -20%;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(181, 148, 16, 0.12) 0%, transparent 70%);
  border-radius: 50%;
}

.hero-greeting {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.25rem;
  margin-bottom: 0.25rem;
}
.hero-greeting strong {
  color: #e6c845;
}
.hero-title {
  font-family: 'Cinzel', serif;
  font-size: 2.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.5rem;
  letter-spacing: 0.04em;
  text-shadow: 0 0 15px rgba(181, 148, 16, 0.25);
}
.sparkle { display: inline-block; animation: float 2s ease-in-out infinite; }
.hero-sub {
  color: #9ca3af;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  max-width: 420px;
  margin-bottom: 1.25rem;
}

.hero-xp { max-width: 340px; }
.xp-label {
  display: flex; justify-content: space-between;
  font-size: 0.7rem; font-weight: 700; font-family: 'Press Start 2P', monospace;
  color: #e6c845;
  margin-bottom: 0.4rem;
}

.progress-bar-wrap {
  height: 10px;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 0;
  overflow: hidden;
}
.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b1a1a, #e6c845);
  box-shadow: 0 0 8px rgba(230, 200, 69, 0.5);
  transition: width 0.4s ease;
}

.hero-avatar {
  flex-shrink: 0;
  filter: drop-shadow(0 0 20px rgba(181, 148, 16, 0.35));
  animation: float 3s ease-in-out infinite;
}

/* ══ STATS MONOLITH GRID ══ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.stat-card {
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease, border-color 0.2s ease;
  cursor: default;
}
.stat-card:hover {
  transform: translateY(-3px);
  border-color: #b59410;
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 15px rgba(181, 148, 16, 0.25);
}

.xp-card     { border-color: rgba(181, 148, 16, 0.35); }
.pts-card    { border-color: rgba(230, 200, 69, 0.35); }
.streak-card { border-color: rgba(220, 38, 38, 0.35); }
.ach-card    { border-color: rgba(16, 185, 129, 0.35); }

.stat-icon { font-size: 2rem; }
.stat-value {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.25rem;
  font-weight: 800;
  color: #f3ede2;
}
.stat-label {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  color: #e6c845;
  font-weight: 700;
}
.stat-sub {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #8c887b;
  margin-top: 0.1rem;
}
.stat-sub.link { color: #e6c845; text-decoration: none; }
.stat-sub.link:hover { text-decoration: underline; }

/* ══ DASHBOARD CONTENT GRID ══ */
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.dash-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.card-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1.25rem;
}
.card-header h3 {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f3ede2;
}
.view-all {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #e6c845;
  text-decoration: none;
}
.view-all:hover { text-decoration: underline; }
.streak-badge {
  background: rgba(220, 38, 38, 0.15);
  border: 1px solid rgba(220, 38, 38, 0.4);
  color: #fca5a5;
  border-radius: 2px;
  padding: 0.2rem 0.75rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}

/* Quest list */
.quest-list { display: flex; flex-direction: column; gap: 0.75rem; }
.quest-item {
  display: flex; align-items: center; gap: 0.875rem;
  padding: 0.75rem; border-radius: 2px;
  background: #09090e; border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  transition: all 0.2s;
}
.quest-item:hover { background: #141520; border-color: #b59410; }
.quest-check { font-size: 1.2rem; cursor: pointer; transition: transform 0.15s; }
.quest-check:hover { transform: scale(1.2); }
.quest-check.done { cursor: default; }
.quest-info { flex: 1; min-width: 0; }
.quest-title {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  font-size: 0.95rem;
  color: #f3ede2;
  display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.quest-meta { display: flex; gap: 0.5rem; margin-top: 0.3rem; align-items: center; }
.xp-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #e6c845;
}
.line-through { text-decoration: line-through; }
.opacity-50 { opacity: 0.4; }

/* Streak */
.streak-week { display: flex; justify-content: space-between; margin-bottom: 1rem; }
.streak-day { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
.day-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8c887b;
}
.day-dot {
  width: 36px; height: 36px; border-radius: 2px;
  background: #09090e; border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  display: flex; align-items: center; justify-content: center; font-size: 1rem; transition: all 0.2s;
}
.day-dot.active { background: rgba(220, 38, 38, 0.2); border-color: #dc2626; color: #f87171; }
.day-dot.today { box-shadow: 0 0 0 1px #050608, 0 0 12px rgba(220, 38, 38, 0.6); }
.streak-message {
  text-align: center; padding: 0.75rem;
  background: #09090e; border: 1px solid #252330;
  border-radius: 2px;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  color: #e6c845;
}

/* Boss promo */
.boss-promo {
  background: linear-gradient(135deg, rgba(139, 26, 26, 0.2), #0c0d14);
  border-color: #8b1a1a;
  display: flex; align-items: center; justify-content: space-between;
}
.boss-promo h3 {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.5rem;
}
.boss-promo p {
  font-size: 0.85rem;
  color: #9ca3af;
  margin-bottom: 1rem;
  max-width: 240px;
}
.boss-promo-emoji { font-size: 4rem; animation: float 3s ease-in-out infinite; }
.boss-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #f87171;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 0.35rem;
}

/* Readiness Card */
.readiness-card {
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  padding: 1.5rem;
}

.readiness-tag {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  color: #e6c845;
  margin-bottom: 0.25rem;
}

.tier-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.25rem 0.65rem;
  border-radius: 2px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.tier-beginner { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
.tier-intermediate { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }
.tier-advanced { background: rgba(181, 148, 16, 0.2); color: #e6c845; border: 1px solid rgba(181, 148, 16, 0.4); }
.tier-board, .tier-board_exam { background: rgba(220, 38, 38, 0.2); color: #f87171; border: 1px solid rgba(220, 38, 38, 0.4); }

.readiness-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.readiness-stats-bar {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 0.75rem 1rem;
}

.r-stat { display: flex; flex-direction: column; }
.r-label {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #8c887b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.r-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.95rem;
  font-weight: 800;
  color: #e6c845;
}
.r-sub { font-size: 0.72rem; color: #8c887b; }

.mastery-bar-container { width: 100%; }
.mastery-track {
  width: 100%;
  height: 8px;
  background: #09090e;
  border: 1px solid #202230;
  border-radius: 0;
  overflow: hidden;
}
.mastery-fill {
  height: 100%;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.mastery-gold   { background: linear-gradient(90deg, #b59410, #e6c845); }
.mastery-green  { background: linear-gradient(90deg, #059669, #10b981); }
.mastery-blue   { background: linear-gradient(90deg, #2563eb, #3b82f6); }
.mastery-amber  { background: linear-gradient(90deg, #d97706, #f59e0b); }
.mastery-red    { background: linear-gradient(90deg, #8b1a1a, #dc2626); }

.adaptive-explanation-badge {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: rgba(181, 148, 16, 0.08);
  border-left: 3px solid #b59410;
  border-radius: 2px;
  padding: 0.6rem 0.85rem;
}
.ae-icon { font-size: 1.05rem; flex-shrink: 0; }
.ae-text { margin: 0; font-size: 0.85rem; color: #fef08a; line-height: 1.4; }

.domains-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.domain-pill-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
}
.group-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #8c887b;
}
.group-item {
  padding: 0.25rem 0.65rem;
  border-radius: 2px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}
.strong-item { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.weak-item { background: rgba(220, 38, 38, 0.15); color: #f87171; border: 1px solid rgba(220, 38, 38, 0.3); }
.acc-chip {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  background: #050608;
  padding: 0.1rem 0.35rem;
  border-radius: 2px;
}

.readiness-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.25rem;
}
.readiness-actions .btn {
  font-size: 0.8rem;
  padding: 0.45rem 0.9rem;
}
.board-btn {
  background: linear-gradient(135deg, #8b1a1a, #b91c1c) !important;
  border-color: #e02424 !important;
  color: #fde8e8 !important;
}

.weak-topic-empty {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 0.5rem 0;
}
.readiness-icon { font-size: 2.25rem; }
.readiness-info strong {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  color: #f3ede2;
  margin-bottom: 0.25rem;
}
.empty-sub { font-size: 0.85rem; color: #9ca3af; line-height: 1.45; margin-bottom: 0.5rem; }

/* Quest stats */
.quest-stats { display: flex; flex-direction: column; gap: 0.75rem; }
.qs-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #1f202d; }
.qs-label { font-family: 'Cinzel', serif; font-size: 0.85rem; color: #8c887b; }
.qs-val { font-family: 'Press Start 2P', monospace; font-weight: 700; font-size: 0.85rem; color: #f3ede2; }
.qs-val.success { color: #34d399; }
.qs-val.gold { color: #e6c845; }
.qs-val.streak { color: #f87171; }
.dash-study-box {
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1.25rem;
}
.dash-study-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.dash-dom-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  font-weight: 700;
  color: #e6c845;
}
.dash-rec-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #8c887b;
  background: #141520;
  padding: 0.15rem 0.5rem;
  border-radius: 2px;
}
.dash-part-title {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0 0 0.35rem 0;
}
.dash-part-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}
.dash-resume-btn {
  font-family: 'Cinzel', serif;
  font-weight: 700;
}
.custom-sub-tasks {
  margin-top: 1rem;
  padding-top: 0.75rem;
  border-top: 1px solid #202230;
}
.custom-sub-header {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8c887b;
  text-transform: uppercase;
  margin-bottom: 0.4rem;
}
.mini-quest-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #cbd5e1;
  padding: 0.25rem 0;
}
.mini-quest-xp {
  font-family: 'Press Start 2P', monospace;
  color: #e6c845;
  font-size: 0.65rem;
}

/* Responsive Media Queries */
@media (max-width: 1200px) { 
  .stats-grid { grid-template-columns: repeat(2, 1fr); } 
}

@media (max-width: 1024px) { 
  .dashboard-grid { grid-template-columns: 1fr; } 
}

@media (max-width: 768px) {
  .hero-banner {
    flex-direction: column-reverse;
    text-align: center;
    padding: 1.75rem 1.25rem;
    gap: 1.5rem;
  }
  .hero-title {
    font-size: 1.65rem;
  }
  .hero-sub {
    margin: 0 auto 1.25rem auto;
  }
  .hero-xp {
    max-width: 100%;
  }
  .hero-avatar {
    margin: 0 auto;
  }
  .dash-card {
    padding: 1.25rem;
  }
}

@media (max-width: 600px) {
  .stats-grid { 
    grid-template-columns: 1fr; 
  }
  .readiness-stats-bar {
    grid-template-columns: 1fr;
    gap: 0.65rem;
  }
  .streak-week {
    gap: 0.25rem;
  }
  .day-dot {
    width: 32px;
    height: 32px;
    font-size: 0.85rem;
  }
}

@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
</style>
