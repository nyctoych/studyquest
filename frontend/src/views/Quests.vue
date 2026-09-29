<template>
  <div class="psychology-quests-page game-module-env module-env-library animate-slide-up">
    <!-- Header Section -->
    <div class="page-header-row">
      <div class="header-left">
        <div class="title-with-badge">
          <h1 class="page-title-main">Psychology Study Paths</h1>
          <span class="exam-pill flex items-center gap-1.5"><PixelEmoji emoji="book" size="xs" /> Board Exam Reviewer</span>
        </div>
        <p class="page-subtitle-main">
          Master the official 12 Psychology domains through 10 sequential learning parts, practice challenges, and licensure mastery assessments.
        </p>
      </div>

      <!-- Action Area -->
      <div class="header-actions">
        <button 
          class="btn btn-secondary btn-sm flex items-center gap-1.5"
          @click="showCustomTasks = !showCustomTasks"
          :title="showCustomTasks ? 'Return to Psychology Curriculum' : 'View Custom Quests Archive'"
        >
          <PixelEmoji emoji="scroll" size="xs" /> {{ showCustomTasks ? 'Study Roadmap' : 'Custom Tasks Archive' }}
        </button>
      </div>
    </div>

    <!-- Custom Tasks Archive Modal/View if toggled -->
    <div v-if="showCustomTasks" class="custom-archive-panel glass-card animate-fade-in">
      <div class="archive-header">
        <div>
          <h3>Custom Academic Tasks Archive</h3>
          <p>Personal tasks previously forged manually.</p>
        </div>
        <router-link to="/quests/create" class="btn btn-primary btn-sm">
          + New Custom Task
        </router-link>
      </div>
      <div v-if="customQuests.length === 0" class="empty-archive">
        <p>No custom tasks in archive. Follow the primary Psychology Study Path below!</p>
      </div>
      <div v-else class="archive-list">
        <div v-for="q in customQuests" :key="q.id" class="archive-item">
          <div class="archive-info">
            <span class="archive-title">{{ q.title }}</span>
            <span class="archive-meta">{{ q.subject || 'General' }} • {{ q.difficulty }}</span>
          </div>
          <router-link :to="`/quests/${q.id}`" class="btn btn-secondary btn-xs">View</router-link>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading your Psychology Study Path...</p>
    </div>

    <!-- Main 2-Column Layout (Codédex-inspired Roadmap & Journey Sidebar) -->
    <div v-else class="curriculum-layout-grid">
      
      <!-- LEFT COLUMN: Vertical Connected Roadmap -->
      <div class="roadmap-column">
        
        <!-- Filter and Search Toolbar -->
        <div class="glass-card roadmap-toolbar">
          <div class="search-box">
            <span class="search-icon"><PixelEmoji emoji="target" size="xs" /></span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search domains or topics (e.g. Cognitive, Memory, Freud)..."
              class="search-input"
            />
            <button v-if="searchQuery" @click="searchQuery = ''" class="clear-search">✕</button>
          </div>

          <div class="filter-tabs">
            <button 
              class="filter-tab"
              :class="{ active: domainFilter === 'all' }"
              @click="domainFilter = 'all'"
            >
              All (12)
            </button>
            <button 
              class="filter-tab"
              :class="{ active: domainFilter === 'active' }"
              @click="domainFilter = 'active'"
            >
              In Progress
            </button>
            <button 
              class="filter-tab"
              :class="{ active: domainFilter === 'completed' }"
              @click="domainFilter = 'completed'"
            >
              Mastered
            </button>
          </div>
        </div>

        <!-- Vertical Timeline Roadmap -->
        <div class="roadmap-timeline">
          
          <div 
            v-for="(domain, dIndex) in filteredDomains" 
            :key="domain.id"
            class="roadmap-chapter-item"
            :class="{ 'expanded': expandedDomainId === domain.id, 'completed': domain.is_completed }"
          >
            <!-- Timeline connector line -->
            <div class="timeline-spine">
              <div 
                class="step-badge"
                :class="{
                  'badge-completed': domain.is_completed,
                  'badge-active': domain.completed_parts > 0 && !domain.is_completed,
                  'badge-default': domain.completed_parts === 0
                }"
              >
                <span v-if="domain.is_completed">✓</span>
                <span v-else>{{ dIndex + 1 }}</span>
              </div>
              <div v-if="dIndex < filteredDomains.length - 1" class="spine-line"></div>
            </div>

            <!-- Domain Card -->
            <div class="domain-card glass-card" :style="{ '--domain-color': domain.color }">
              
              <!-- Card Header (Click to toggle expansion) -->
              <div class="domain-header-bar" @click="toggleDomain(domain.id)">
                <div class="domain-identity">
                  <div class="domain-icon-box" :style="{ backgroundColor: domain.color + '22', borderColor: domain.color }">
                    <span class="domain-icon-glyph"><PixelEmoji :emoji="domain.icon" size="sm" /></span>
                  </div>
                  <div class="domain-meta">
                    <div class="domain-chapter-tag">Chapter {{ String(dIndex + 1).padStart(2, '0') }}</div>
                    <h2 class="domain-title">{{ domain.name }}</h2>
                    <p class="domain-desc">{{ domain.description }}</p>
                  </div>
                </div>

                <div class="domain-right-status">
                  <!-- Progress Pill -->
                  <div class="progress-pill-group">
                    <div class="progress-text">
                      <strong>{{ domain.completed_parts }} / 10</strong> Parts
                    </div>
                    <div class="mini-progress-bar">
                      <div 
                        class="mini-fill"
                        :style="{ width: `${(domain.completed_parts / 10) * 100}%`, backgroundColor: domain.color }"
                      ></div>
                    </div>
                    <span v-if="domain.is_completed" class="mastery-tag flex items-center gap-1"><PixelEmoji emoji="trophy" size="xs" /> Domain Master</span>
                  </div>

                  <!-- Quick Review Chapter Button -->
                  <button 
                    v-if="domain.is_completed || domain.completed_parts > 0"
                    class="btn btn-secondary btn-xs review-chapter-btn flex items-center gap-1"
                    @click.stop="reviewChapter(domain)"
                    title="Review Chapter from Part 1"
                  >
                    <PixelEmoji emoji="book" size="xs" /> Review Chapter
                  </button>

                  <!-- Expand Arrow -->
                  <button class="expand-btn" :aria-label="expandedDomainId === domain.id ? 'Collapse' : 'Expand'" @click.stop="toggleDomain(domain.id)">
                    <span class="arrow-icon" :class="{ 'rotate': expandedDomainId === domain.id }">▾</span>
                  </button>
                </div>
              </div>

              <!-- Expanded 10-Part Checklist / Exercises -->
              <transition name="drawer">
                <div v-if="expandedDomainId === domain.id" class="domain-parts-drawer">
                  
                  <!-- Drawer Loading state -->
                  <div v-if="loadingParts" class="drawer-loading">
                    <div class="spinner-small"></div>
                    <span>Loading domain curriculum...</span>
                  </div>

                  <!-- 10 Parts List -->
                  <div v-else class="parts-checklist">
                    <div 
                      v-for="part in currentDomainParts" 
                      :key="part.id"
                      class="part-row"
                      :class="[
                        `status-${part.user_status}`,
                        { 
                          'is-mastery-challenge': part.part_number === 10,
                          'is-clickable': part.user_status !== 'locked'
                        }
                      ]"
                      @click="handlePartClick(part)"
                    >
                      <!-- Part Number Badge -->
                      <div class="part-number-col">
                        <span class="part-num-bubble" :class="part.user_status">
                          <span v-if="part.user_status === 'completed' || part.user_status === 'mastered'">✓</span>
                          <span v-else-if="part.user_status === 'locked'"><PixelEmoji emoji="lock" size="xs" /></span>
                          <span v-else>{{ part.part_number }}</span>
                        </span>
                      </div>

                      <!-- Part Info -->
                      <div class="part-details-col">
                        <div class="part-top-line">
                          <span class="part-type-pill" :class="part.part_type">
                            {{ formatPartType(part.part_type) }}
                          </span>
                          <span class="part-reward-pill flex items-center gap-1.5">
                            <PixelEmoji emoji="star" size="xs" /> +{{ part.xp_reward }} XP • <PixelEmoji emoji="coin" size="xs" /> +{{ part.coin_reward }}
                          </span>
                          <span v-if="part.user_status === 'mastered'" class="mastered-pill flex items-center gap-1">
                            <PixelEmoji emoji="star" size="xs" /> Mastered ({{ part.user_score }}%)
                          </span>
                        </div>

                        <h3 class="part-title">
                          {{ part.title }}
                        </h3>
                        <p class="part-desc">{{ part.description }}</p>

                        <div v-if="part.lock_reason" class="lock-hint flex items-center gap-1">
                          <PixelEmoji emoji="lock" size="xs" /> <span>{{ part.lock_reason }}</span>
                        </div>
                      </div>

                      <!-- Part Action Button -->
                      <div class="part-action-col" @click.stop>
                        <router-link
                          v-if="part.user_status === 'completed' || part.user_status === 'mastered'"
                          :to="`/quests/part/${part.id}`"
                          class="btn btn-secondary btn-sm review-btn"
                        >
                          Review ({{ part.user_score }}%)
                        </router-link>

                        <router-link
                          v-else-if="part.user_status === 'in_progress'"
                          :to="`/quests/part/${part.id}`"
                          class="btn btn-primary btn-sm continue-glow-btn"
                        >
                          ▶ Continue
                        </router-link>

                        <router-link
                          v-else-if="part.user_status === 'available'"
                          :to="`/quests/part/${part.id}`"
                          class="btn btn-primary btn-sm start-btn"
                        >
                          Start
                        </router-link>

                        <button
                          v-else
                          class="btn btn-locked btn-sm flex items-center justify-center gap-1"
                          disabled
                          :title="part.lock_reason"
                        >
                          <PixelEmoji emoji="lock" size="xs" /> Locked
                        </button>
                      </div>

                    </div>
                  </div>

                </div>
              </transition>

            </div>

          </div>

        </div>

      </div>

      <!-- RIGHT COLUMN: Codédex-style Journey Sidebar -->
      <div class="sidebar-column">
        
        <!-- User Identity Card -->
        <div class="glass-card user-hero-card">
          <div class="user-hero-top">
            <div class="avatar-wrapper">
              <span class="avatar-large"><PixelEmoji emoji="ogre" size="lg" /></span>
              <span class="user-level-badge">Lv. {{ authStore.user?.level || 1 }}</span>
            </div>
            <div class="user-hero-info">
              <h3 class="user-name">{{ authStore.user?.username || 'Psychology Scholar' }}</h3>
              <p class="user-role">Board Licensure Candidate</p>
              <div class="streak-pill flex items-center gap-1.5">
                <PixelEmoji emoji="fire" size="xs" /> <strong>{{ authStore.user?.streak || 0 }} Day</strong> Study Streak
              </div>
            </div>
          </div>
        </div>

        <!-- Today's Study Recommendation Card -->
        <div class="glass-card today-study-card" v-if="todayStudy">
          <div class="today-card-header">
            <div class="today-badge flex items-center gap-1"><PixelEmoji emoji="target" size="xs" /> TODAY'S FOCUS</div>
            <span class="rec-reason">{{ todayStudy.reason }}</span>
          </div>

          <div class="today-study-content">
            <div class="today-domain-tag flex items-center gap-1.5" :style="{ color: todayStudy.domain_color }">
              <PixelEmoji :emoji="todayStudy.domain_icon" size="xs" /> {{ todayStudy.domain_name }}
            </div>
            <h4 class="today-part-title">Part {{ todayStudy.part_number }}: {{ todayStudy.part_title }}</h4>
            <p class="today-part-desc">
              Continue through your sequential learning path to earn XP, coins, and unlock the next part.
            </p>

            <router-link :to="`/quests/part/${todayStudy.part_id}`" class="btn btn-primary btn-block continue-study-btn flex items-center justify-center gap-2">
              <PixelEmoji emoji="lightning" size="xs" /> Resume Part {{ todayStudy.part_number }} →
            </router-link>
          </div>
        </div>

        <!-- Overall Psychology Progress Card -->
        <div class="glass-card progress-overview-card">
          <h3 class="sidebar-title">Overall Reviewer Progress</h3>
          
          <div class="progress-big-number">
            <span class="percent-val">{{ summary.overall_percentage }}%</span>
            <span class="percent-sub">Board Prep Complete</span>
          </div>

          <div class="full-progress-bar">
            <div class="full-fill" :style="{ width: `${summary.overall_percentage}%` }"></div>
          </div>

          <div class="stats-row">
            <div class="stat-box">
              <div class="stat-number">{{ summary.total_parts_completed }} / {{ summary.total_parts_curriculum }}</div>
              <div class="stat-label">Parts Done</div>
            </div>
            <div class="stat-box">
              <div class="stat-number">{{ summary.completed_domains_count }} / 12</div>
              <div class="stat-label">Domains Mastered</div>
            </div>
            <div class="stat-box">
              <div class="stat-number">{{ summary.average_mastery }}%</div>
              <div class="stat-label">Avg. Mastery</div>
            </div>
          </div>
        </div>

        <!-- Domain Mastery Badges Showcase -->
        <div class="glass-card badges-card">
          <div class="badges-header">
            <h3 class="sidebar-title">Domain Badges</h3>
            <span class="badge-count">{{ summary.completed_domains_count }} / 12</span>
          </div>
          <p class="badges-sub">Complete all 10 parts of a domain to unlock its official Mastery Badge.</p>

          <div class="badges-grid">
            <div 
              v-for="domain in domains" 
              :key="domain.id"
              class="badge-icon-item"
              :class="{ 'unlocked': domain.is_completed }"
              :title="domain.is_completed ? `${domain.name} (Mastered! 🏆)` : `${domain.name} (${domain.completed_parts}/10 parts)`"
            >
              <div class="badge-ring" :style="{ borderColor: domain.is_completed ? domain.color : '#334155' }">
                <span class="badge-glyph"><PixelEmoji :emoji="domain.icon" size="sm" /></span>
              </div>
              <span class="badge-domain-name">{{ domain.name.split(' ')[0] }}</span>
              <span v-if="domain.is_completed" class="badge-check">✓</span>
            </div>
          </div>
        </div>

        <!-- Study Strategy Tip -->
        <div class="glass-card study-tip-card">
          <div class="tip-icon"><PixelEmoji emoji="bulb" size="md" /></div>
          <div class="tip-content">
            <h4>Licensure Study Tip</h4>
            <p>Aim for at least 90% on each Part Challenge to earn the <strong>Mastered</strong> distinction and bonus coins for the shop!</p>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import curriculumService from '../services/curriculumService'
import api from '../services/api'

const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const loadingParts = ref(false)
const domains = ref([])
const summary = ref({
  total_parts_completed: 0,
  total_parts_curriculum: 120,
  overall_percentage: 0,
  completed_domains_count: 0,
  average_mastery: 0
})
const todayStudy = ref(null)
const expandedDomainId = ref(null)
const currentDomainParts = ref([])
const searchQuery = ref('')
const domainFilter = ref('all')

// Custom tasks compatibility
const showCustomTasks = ref(false)
const customQuests = ref([])

onMounted(async () => {
  await loadCurriculum()
  loadCustomQuests()
})

async function loadCurriculum() {
  loading.value = true
  try {
    const data = await curriculumService.getOverview()
    domains.value = data.domains || []
    summary.value = data.summary || summary.value
    todayStudy.value = data.today_recommendation

    // Automatically expand the domain containing the active today's recommendation or first domain
    if (todayStudy.value?.domain_id) {
      await expandDomain(todayStudy.value.domain_id)
    } else if (domains.value.length > 0) {
      await expandDomain(domains.value[0].id)
    }
  } catch (err) {
    console.error('Failed to load curriculum overview:', err)
  } finally {
    loading.value = false
  }
}

async function toggleDomain(domainId) {
  if (expandedDomainId.value === domainId) {
    expandedDomainId.value = null
    currentDomainParts.value = []
  } else {
    await expandDomain(domainId)
  }
}

function handlePartClick(part) {
  if (part.user_status === 'locked') return
  router.push(`/quests/part/${part.id}`)
}

async function reviewChapter(domain) {
  if (domain.first_part_id) {
    router.push(`/quests/part/${domain.first_part_id}`)
    return
  }
  try {
    const res = await curriculumService.getDomainDetails(domain.id)
    const firstPart = res?.domain?.parts?.[0]
    if (firstPart?.id) {
      router.push(`/quests/part/${firstPart.id}`)
    } else {
      await expandDomain(domain.id)
    }
  } catch (err) {
    await expandDomain(domain.id)
  }
}

async function expandDomain(domainId) {
  expandedDomainId.value = domainId
  loadingParts.value = true
  try {
    const res = await curriculumService.getDomainDetails(domainId)
    if (res?.domain?.parts) {
      currentDomainParts.value = res.domain.parts
    }
  } catch (err) {
    console.error('Failed to load domain details:', err)
  } finally {
    loadingParts.value = false
  }
}

async function loadCustomQuests() {
  try {
    const res = await api.get('/quests')
    if (res.data?.quests) {
      customQuests.value = res.data.quests
    }
  } catch (err) {
    // Silent fail if none
  }
}

const filteredDomains = computed(() => {
  let list = domains.value

  // Text search
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    list = list.filter(d => 
      d.name.toLowerCase().includes(q) || 
      d.description?.toLowerCase().includes(q)
    )
  }

  // Status tabs
  if (domainFilter.value === 'active') {
    list = list.filter(d => d.completed_parts > 0 && !d.is_completed)
  } else if (domainFilter.value === 'completed') {
    list = list.filter(d => d.is_completed)
  }

  return list
})

function formatPartType(type) {
  const map = {
    learn: '📖 Learn',
    review: '📝 Review',
    practice: '💡 Practice',
    scenario: '🩺 Scenario',
    quiz: '⚡ Quiz',
    challenge: '⚔️ Challenge',
    mastery: '🏆 Mastery Challenge'
  }
  return map[type] || 'Study Part'
}
</script>

<style scoped>
.psychology-quests-page {
  max-width: 1380px;
  margin: 0 auto;
  padding-bottom: 4rem;
  color: #e2d9cc;
}

/* ══ HEADER ══ */
.page-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.title-with-badge {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title-main {
  font-family: 'Cinzel', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: #f3ede2;
  letter-spacing: 0.05em;
  margin: 0;
  text-shadow: 0 0 15px rgba(181, 148, 16, 0.2);
}

.exam-pill {
  background: rgba(181, 148, 16, 0.15);
  color: #e6c845;
  border: 1px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 0 1px #050608;
  padding: 0.35rem 0.85rem;
  border-radius: 2px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  letter-spacing: 0.05em;
}

.page-subtitle-main {
  color: #9ca3af;
  font-family: 'VT323', monospace;
  font-size: 1.25rem;
  margin-top: 0.5rem;
  max-width: 780px;
  line-height: 1.4;
}

/* ══ CUSTOM ARCHIVE ══ */
.custom-archive-panel {
  padding: 1.5rem;
  margin-bottom: 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}
.archive-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.archive-header h3 {
  margin: 0;
  color: #f3ede2;
  font-family: 'Cinzel', serif;
  font-size: 1.2rem;
}
.archive-header p {
  margin: 0;
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
}
.archive-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.archive-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #0d0e15;
  border: 1px solid #1f202c;
  box-shadow: 0 0 0 1px #050608;
  padding: 0.75rem 1rem;
  border-radius: 2px;
}
.archive-title {
  font-weight: 600;
  color: #f3ede2;
}
.archive-meta {
  color: #8c887b;
  font-size: 0.8rem;
  margin-left: 0.75rem;
}

/* ══ LOADING ══ */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 2rem;
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.3rem;
}
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(181, 148, 16, 0.2);
  border-top-color: #e6c845;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ══ LAYOUT GRID ══ */
.curriculum-layout-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 380px;
  gap: 2rem;
  align-items: start;
}

@media (max-width: 1024px) {
  .curriculum-layout-grid {
    grid-template-columns: 1fr;
  }
}

/* ══ ROADMAP TOOLBAR ══ */
.roadmap-toolbar {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
  flex-wrap: wrap;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 260px;
}
.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
  color: #8c887b;
}
.search-input {
  width: 100%;
  padding: 0.65rem 1rem 0.65rem 2.4rem;
  background: #09090e;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  color: #f3ede2;
  font-size: 0.9rem;
  font-family: inherit;
}
.search-input:focus {
  outline: none;
  border-color: #b59410;
  box-shadow: 0 0 0 1px #b59410;
}
.clear-search {
  position: absolute;
  right: 0.8rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #8c887b;
  cursor: pointer;
}

.filter-tabs {
  display: flex;
  background: #09090e;
  border: 1px solid #202230;
  border-radius: 2px;
  padding: 0.25rem;
  gap: 0.25rem;
}
.filter-tab {
  background: none;
  border: none;
  color: #8c887b;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.5rem 0.85rem;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-tab.active {
  background: #1e1b29;
  color: #e6c845;
  border: 1px solid rgba(181, 148, 16, 0.4);
  box-shadow: 0 0 8px rgba(181, 148, 16, 0.25);
}

/* ══ TIMELINE VERTICAL SPINE ══ */
.roadmap-timeline {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.roadmap-chapter-item {
  display: flex;
  gap: 1.25rem;
  position: relative;
}

.timeline-spine {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  width: 44px;
  flex-shrink: 0;
}

.step-badge {
  width: 40px;
  height: 40px;
  border-radius: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.75rem;
  z-index: 2;
  transition: all 0.3s;
  box-shadow: 0 0 0 2px #050608;
}
.step-badge.badge-default {
  background: #0e0e16;
  border: 2px solid #222436;
  color: #71717a;
}
.step-badge.badge-active {
  background: #1c1825;
  border: 2px solid #b59410;
  color: #e6c845;
  box-shadow: 0 0 0 2px #050608, 0 0 15px rgba(181, 148, 16, 0.5);
}
.step-badge.badge-completed {
  background: #0d1a15;
  border: 2px solid #10b981;
  color: #34d399;
}

.spine-line {
  width: 2px;
  background: linear-gradient(to bottom, #252330, rgba(37, 35, 48, 0.4));
  flex: 1;
  margin-top: 4px;
}

/* ══ DOMAIN MONOLITH CARD ══ */
.domain-card {
  flex: 1;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
  overflow: hidden;
  transition: all 0.25s ease;
}
.domain-card:hover {
  border-color: #b59410;
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(0, 0, 0, 0.8), inset 0 0 0 1px rgba(181, 148, 16, 0.3);
}

.domain-header-bar {
  padding: 1.4rem 1.6rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  gap: 1.5rem;
  user-select: none;
}

.domain-identity {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.domain-icon-box {
  width: 52px;
  height: 52px;
  border-radius: 2px;
  border: 2px solid #2a2c3f;
  box-shadow: 0 0 0 1px #050608;
  background: #0c0d14;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
}

.domain-chapter-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  color: #e6c845;
  margin-bottom: 0.3rem;
}

.domain-title {
  font-family: 'Cinzel', serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0;
}

.domain-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  margin: 0.35rem 0 0 0;
  line-height: 1.4;
  max-width: 560px;
}

.domain-right-status {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.progress-pill-group {
  text-align: right;
  min-width: 120px;
}
.progress-text {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8c887b;
  margin-bottom: 0.3rem;
}
.progress-text strong {
  color: #e6c845;
}

.mini-progress-bar {
  width: 110px;
  height: 8px;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 0;
  overflow: hidden;
  margin-left: auto;
}
.mini-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b1a1a, #e6c845);
  transition: width 0.4s ease;
}
.mastery-tag {
  display: inline-block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #e6c845;
  background: rgba(181, 148, 16, 0.15);
  border: 1px solid rgba(181, 148, 16, 0.4);
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
  margin-top: 0.3rem;
}

.expand-btn {
  background: #0d0e15;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608;
  width: 34px;
  height: 34px;
  border-radius: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e6c845;
  cursor: pointer;
  transition: all 0.2s;
}
.arrow-icon {
  display: inline-block;
  font-size: 1.1rem;
  transition: transform 0.25s ease;
}
.arrow-icon.rotate {
  transform: rotate(180deg);
}

/* ══ EXPANDED DRAWER ══ */
.domain-parts-drawer {
  border-top: 2px solid #1f202d;
  background: #08080d;
  padding: 1.25rem 1.6rem 1.6rem 1.6rem;
}

.drawer-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem 0;
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.2rem;
}
.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(181, 148, 16, 0.2);
  border-top-color: #e6c845;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.parts-checklist {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.part-row {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1rem 1.25rem;
  background: #0f1018;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  transition: all 0.2s;
}
.part-row.is-clickable {
  cursor: pointer;
}
.part-row:hover {
  background: #151622;
  border-color: #b59410;
}
.part-row.is-clickable:hover {
  border-color: #e6c845;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), 0 0 8px rgba(230, 200, 69, 0.15);
}
.part-row.is-mastery-challenge {
  border: 1px solid rgba(181, 148, 16, 0.4);
  background: rgba(181, 148, 16, 0.08);
}

.review-chapter-btn {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.35rem 0.65rem;
  background: rgba(181, 148, 16, 0.15);
  border: 1px solid rgba(181, 148, 16, 0.5);
  color: #e6c845;
  white-space: nowrap;
  transition: all 0.2s;
}
.review-chapter-btn:hover {
  background: rgba(181, 148, 16, 0.35);
  border-color: #e6c845;
  color: #fff;
}

.part-num-bubble {
  width: 32px;
  height: 32px;
  border-radius: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}
.part-num-bubble.completed,
.part-num-bubble.mastered {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid #10b981;
}
.part-num-bubble.available,
.part-num-bubble.in_progress {
  background: rgba(181, 148, 16, 0.2);
  color: #e6c845;
  border: 1px solid #b59410;
}
.part-num-bubble.locked {
  background: #09090e;
  color: #52525b;
  border: 1px solid #1f202c;
}

.part-details-col {
  flex: 1;
}
.part-top-line {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.3rem;
  flex-wrap: wrap;
}
.part-type-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
  background: #181926;
  border: 1px solid #2b2d42;
  color: #9ca3af;
}
.part-type-pill.mastery {
  background: rgba(181, 148, 16, 0.15);
  color: #e6c845;
  border: 1px solid rgba(181, 148, 16, 0.4);
}
.part-reward-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #e6c845;
}
.mastered-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #34d399;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
}

.part-title {
  font-family: 'Cinzel', serif;
  font-size: 1rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0;
}
.part-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  margin: 0.25rem 0 0 0;
  line-height: 1.4;
}
.lock-hint {
  font-size: 0.75rem;
  color: #f87171;
  margin-top: 0.35rem;
}

.part-action-col {
  flex-shrink: 0;
}
.btn-locked {
  background: #0c0d14;
  color: #52525b;
  border: 1px solid #202230;
  cursor: not-allowed;
}
.continue-glow-btn {
  box-shadow: 0 0 12px rgba(181, 148, 16, 0.4);
}

/* ══ SIDEBAR HUB ══ */
.sidebar-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sidebar-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #f3ede2;
  margin: 0 0 1rem 0;
}

/* User Hero Card */
.user-hero-card {
  padding: 1.4rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}
.user-hero-top {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}
.avatar-wrapper {
  position: relative;
}
.avatar-large {
  font-size: 2.5rem;
  display: block;
}
.user-level-badge {
  position: absolute;
  bottom: -4px;
  right: -6px;
  background: #8b1a1a;
  color: #fde8e8;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.15rem 0.4rem;
  border-radius: 2px;
  border: 1px solid #050608;
}
.user-name {
  font-family: 'Cinzel', serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0;
}
.user-role {
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  color: #8c887b;
  margin: 0.2rem 0 0.5rem 0;
}
.streak-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
}

/* Today's Study Card */
.today-study-card {
  padding: 1.5rem;
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 0 20px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
  background: linear-gradient(135deg, rgba(139, 26, 26, 0.15) 0%, rgba(10, 10, 15, 0.9) 100%);
}
.today-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}
.today-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  color: #e6c845;
}
.rec-reason {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #8c887b;
}
.today-domain-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #e6c845;
  margin-bottom: 0.4rem;
}
.today-part-title {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0 0 0.5rem 0;
  line-height: 1.35;
}
.today-part-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.4;
  margin-bottom: 1.25rem;
}
.continue-study-btn {
  font-family: 'Cinzel', serif;
  font-weight: 700;
  letter-spacing: 0.04em;
}

/* Progress Overview */
.progress-overview-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}
.progress-big-number {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
.percent-val {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.8rem;
  color: #e6c845;
}
.percent-sub {
  font-family: 'VT323', monospace;
  font-size: 1.2rem;
  color: #8c887b;
}
.full-progress-bar {
  height: 10px;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 0;
  overflow: hidden;
  margin-bottom: 1.25rem;
}
.full-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b1a1a, #b59410);
  transition: width 0.5s ease;
}
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  border-top: 1px solid #202230;
  padding-top: 1rem;
}
.stat-box {
  text-align: center;
}
.stat-number {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.95rem;
  color: #f3ede2;
}
.stat-label {
  font-family: 'VT323', monospace;
  font-size: 0.9rem;
  color: #8c887b;
  text-transform: uppercase;
  margin-top: 0.2rem;
}

/* Badges Showcase */
.badges-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.2);
  border-radius: 4px;
}
.badges-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.badges-header .sidebar-title {
  margin: 0;
}
.badge-count {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
}
.badges-sub {
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
  color: #8c887b;
  margin: 0.4rem 0 1.2rem 0;
}
.badges-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.85rem;
}
.badge-icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  opacity: 0.35;
  transition: all 0.25s;
  position: relative;
}
.badge-icon-item.unlocked {
  opacity: 1;
}
.badge-ring {
  width: 44px;
  height: 44px;
  border-radius: 2px;
  border: 2px solid #2b2d42;
  box-shadow: 0 0 0 1px #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0b0c12;
  margin-bottom: 0.35rem;
  transition: transform 0.2s;
}
.badge-icon-item.unlocked .badge-ring {
  border-color: #b59410;
  box-shadow: 0 0 0 1px #050608, 0 0 10px rgba(181, 148, 16, 0.3);
}
.badge-icon-item.unlocked:hover .badge-ring {
  transform: scale(1.1);
}
.badge-glyph {
  font-size: 1.3rem;
}
.badge-domain-name {
  font-size: 0.68rem;
  color: #8c887b;
  max-width: 60px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.badge-check {
  position: absolute;
  top: -2px;
  right: 10px;
  background: #10b981;
  color: #fff;
  font-size: 0.6rem;
  font-weight: 800;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Tip */
.study-tip-card {
  padding: 1.25rem;
  display: flex;
  gap: 1rem;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
}
.tip-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}
.tip-content h4 {
  margin: 0 0 0.3rem 0;
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  color: #f3ede2;
}
.tip-content p {
  margin: 0;
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.4;
}

/* Drawer Transitions */
.drawer-enter-active,
.drawer-leave-active {
  transition: all 0.3s ease;
  max-height: 1200px;
  opacity: 1;
  overflow: hidden;
}
.drawer-enter-from,
.drawer-leave-to {
  max-height: 0;
  opacity: 0;
  padding-top: 0;
  padding-bottom: 0;
}

/* Responsive Enhancements */
@media (max-width: 768px) {
  .page-header-row {
    flex-direction: column;
    align-items: flex-start;
  }
  .domain-header-bar {
    padding: 1.15rem 1rem;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .domain-right-status {
    width: 100%;
    justify-content: space-between;
  }
  .progress-pill-group {
    text-align: left;
  }
  .mini-progress-bar {
    margin-left: 0;
  }
  .domain-parts-drawer {
    padding: 1rem 0.85rem;
  }
  .part-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 0.85rem 1rem;
  }
  .part-action-col {
    width: 100%;
  }
  .part-action-col .btn {
    width: 100%;
    justify-content: center;
  }
  .roadmap-toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  .search-box {
    min-width: 100%;
  }
  .filter-tabs {
    width: 100%;
    justify-content: space-around;
  }
  .filter-tab {
    flex: 1;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .roadmap-chapter-item {
    gap: 0.75rem;
  }
  .timeline-spine {
    width: 36px;
  }
  .step-badge {
    width: 34px;
    height: 34px;
    font-size: 0.85rem;
  }
  .domain-icon-box {
    width: 42px;
    height: 42px;
    font-size: 1.3rem;
  }
  .domain-title {
    font-size: 1.1rem;
  }
  .badges-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
