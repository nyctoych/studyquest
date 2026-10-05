<template>
  <header class="navbar">
    <div class="navbar-left">
      <!-- Mobile hamburger toggle -->
      <button class="mobile-menu-btn" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Toggle menu">
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
        <span class="hamburger-bar"></span>
      </button>

      <div>
        <h2 class="page-title">{{ pageTitle }}</h2>
        <p class="page-subtitle" v-if="pageSubtitle">{{ pageSubtitle }}</p>
      </div>
    </div>

    <div class="navbar-right">
      <!-- Health (Adventure HP) -->
      <router-link
        to="/shop"
        class="navbar-stat health-chip flex items-center gap-1.5"
        :class="['state-' + healthStore.statusState]"
        :title="healthTooltip"
      >
        <PixelEmoji emoji="heart" size="sm" />
        <span class="stat-value font-pixel">{{ healthStore.currentHp }} / {{ healthStore.maxHp }}</span>
        <span v-if="healthStore.isDefeated" class="health-recovering-tag">RECOVERING</span>
      </router-link>

      <!-- Points -->
      <div class="navbar-stat points-chip flex items-center gap-1.5" title="Your Points">
        <PixelEmoji emoji="coin" size="sm" />
        <span class="stat-value">{{ formatPoints(auth.points) }}</span>
      </div>
      <!-- XP -->
      <div class="navbar-stat xp-chip flex items-center gap-1.5" title="Total XP">
        <PixelEmoji emoji="star" size="sm" />
        <span class="stat-value">{{ formatXP(auth.xp) }} XP</span>
      </div>
      <!-- Streak -->
      <div class="navbar-stat streak-chip flex items-center gap-1.5" title="Study Streak" v-if="auth.streak > 0">
        <PixelEmoji emoji="fire" size="sm" />
        <span class="stat-value">{{ auth.streak }}</span>
      </div>
      <!-- Avatar -->
      <router-link to="/profile" class="navbar-avatar" title="My Profile">
        <AvatarImg
          :style-name="auth.avatarStyle"
          :seed="auth.avatarSeed"
          :options="auth.avatarOpts"
          :size="38"
        />
        <span class="avatar-level">Lv.{{ auth.level }}</span>
      </router-link>
    </div>

    <!-- Mobile Drawer Navigation (Teleported to body so it never breaks layout/stacking context) -->
    <Teleport to="body">
      <transition name="drawer-fade">
        <div v-if="mobileMenuOpen" class="mobile-drawer-overlay" @click.self="mobileMenuOpen = false">
          <div class="mobile-drawer animate-slide-in">
            <div class="drawer-header">
              <div class="drawer-logo">
                <span class="logo-icon">✦</span>
                <span class="logo-text">StudyQuest</span>
              </div>
              <button class="close-drawer-btn" @click="mobileMenuOpen = false" aria-label="Close menu">✕</button>
            </div>

            <nav class="drawer-nav">
              <router-link
                v-for="item in navItems"
                :key="item.path"
                :to="item.path"
                class="drawer-nav-item flex items-center gap-2"
                :class="{ active: isActive(item.path) }"
                @click="mobileMenuOpen = false"
              >
                <span class="drawer-icon"><PixelEmoji :emoji="item.icon" size="sm" /></span>
                <span class="drawer-label">{{ item.label }}</span>
              </router-link>
            </nav>

            <div class="drawer-bottom">
              <router-link to="/settings" class="drawer-nav-item flex items-center gap-2" @click="mobileMenuOpen = false">
                <span class="drawer-icon"><PixelEmoji emoji="gear" size="sm" /></span>
                <span class="drawer-label">Settings</span>
              </router-link>
              <button class="drawer-nav-item logout-btn flex items-center gap-2" @click="handleLogout">
                <span class="drawer-icon"><PixelEmoji emoji="hall" size="sm" /></span>
                <span class="drawer-label">Logout</span>
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </header>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useHealthStore } from '../stores/health'
import { formatXP, formatPoints } from '../utils/formatters'
import AvatarImg from './AvatarImg.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const healthStore = useHealthStore()

const mobileMenuOpen = ref(false)

const navItems = [
  { path: '/world',        icon: 'castle', label: 'Main World' },
  { path: '/studyhall',    icon: 'scroll', label: 'Study Hall' },
  { path: '/adventure',    icon: 'sword',  label: 'Adventure Gate' },
  { path: '/arena',        icon: 'lightning', label: 'Arena' },
  { path: '/market',       icon: 'cart', label: 'Market' },
  { path: '/leaderboard',  icon: 'trophy', label: 'Hall of Records' },
  { path: '/achievements', icon: 'shield', label: 'Achievements' },
  { path: '/profile',      icon: 'person', label: 'Profile' },
]

const pageTitles = {
  '/world': { title: 'Citadel Hub', sub: 'The Central Bastion of Mindrealm' },
  '/dashboard': { title: 'Citadel Hub', sub: 'The Central Bastion of Mindrealm' },
  '/studyhall': { title: 'Study Hall', sub: 'Grand Library of Psychiatric Knowledge' },
  '/studyhall/shelves': { title: 'Study Hall — Shelves', sub: 'Select a volume of knowledge' },
  '/studyhall/reading-room': { title: 'Study Hall — Reading Room', sub: 'Explore chapters and clinical pages' },
  '/adventure': { title: 'Adventure Gate', sub: 'Journey through the 12 Fractured Domains' },
  '/arena': { title: 'Arena', sub: 'Battle fellow scholars in real-time PvP' },
  '/multiplayer': { title: 'Arena', sub: 'Battle fellow scholars in real-time PvP' },
  '/market': { title: 'The Raven Market', sub: 'Equip Attack & Splash Effects for battle' },
  '/shop': { title: 'The Raven Market', sub: 'Equip Attack & Splash Effects for battle' },
  '/leaderboard': { title: 'Hall of Records', sub: 'Grand Hall of Champions and Scholars' },
  '/records': { title: 'Hall of Records', sub: 'Grand Hall of Champions and Scholars' },
  '/special-event': { title: 'The Daily Trial', sub: '140-Question Board-Exam Assessment' },
  '/quests': { title: 'Psychology Study Paths', sub: 'Follow your structured board-exam reviewer' },
  '/quests/create': { title: 'Forge Quest', sub: 'Create a new study challenge' },
  '/rewards': { title: 'Reward Shop', sub: 'Spend your hard-earned points' },
  '/chronicle': { title: 'Chronicle Hall', sub: 'Scholarly accomplishments & world records' },
  '/achievements': { title: 'Achievements', sub: 'Your milestone collection' },
  '/profile': { title: 'Adventurer Profile', sub: 'Your student identity and statistics' },
  '/settings': { title: 'Settings', sub: 'Manage your account preferences' },
}

const pageTitle = computed(() => {
  if (route.path.startsWith('/quests/') && route.path !== '/quests/create') {
    return 'Psychology Study Room'
  }
  if (route.path.startsWith('/boss/')) {
    return 'Boss Battle'
  }
  return pageTitles[route.path]?.title ?? 'StudyQuest'
})

const pageSubtitle = computed(() => {
  if (route.path.startsWith('/quests/') && route.path !== '/quests/create') {
    return 'Interactive learning, clinical scenarios & scored challenge'
  }
  return pageTitles[route.path]?.sub ?? ''
})

const isActive = (path) => route.path.startsWith(path)

const healthTooltip = computed(() => {
  if (healthStore.isDefeated) {
    return `YOU ARE DEFEATED (${healthStore.currentHp} / ${healthStore.maxHp} HP)\n+${healthStore.regenRate} HP / Hour\nNext Recovery in: ${healthStore.formattedCountdown}\nClick to visit Grand Bazaar for Health Potion (+200 HP)`
  }
  return `Adventure Health: ${healthStore.currentHp} / ${healthStore.maxHp} HP\nRegeneration: +${healthStore.regenRate} HP / Hour\nNext Recovery in: ${healthStore.formattedCountdown}\nClick to visit Grand Bazaar for Health Potion`
})

onMounted(() => {
  healthStore.fetchHealth()
})

async function handleLogout() {
  mobileMenuOpen.value = false
  await auth.logout()
  toast.success('See you next time! 👋')
  router.push('/login')
}
</script>

<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  background: rgba(13, 17, 23, 0.9);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 50;
  gap: 1rem;
}

@media (max-width: 768px) {
  .navbar {
    padding: 0.75rem 1rem;
  }
}

.navbar-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mobile-menu-btn {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
}

.hamburger-bar {
  width: 20px;
  height: 2px;
  background: #cbd5e1;
  border-radius: 2px;
}

@media (max-width: 1024px) {
  .mobile-menu-btn {
    display: flex;
  }
}

.page-title {
  font-family: 'Outfit', sans-serif;
  font-size: 1.4rem;
  font-weight: 800;
  color: white;
  line-height: 1;
}

@media (max-width: 640px) {
  .page-title {
    font-size: 1.05rem;
  }
  .page-subtitle {
    display: none;
  }
  .navbar-right {
    gap: 0.4rem;
  }
  .navbar-stat {
    padding: 0.3rem 0.5rem;
    font-size: 0.78rem;
  }
}

@media (max-width: 520px) {
  .navbar-right .xp-chip {
    display: none;
  }
}

@media (max-width: 400px) {
  .navbar-right .streak-chip {
    display: none;
  }
  .navbar {
    padding: 0.6rem 0.75rem;
  }
  .avatar-level {
    display: none;
  }
}

.navbar-stat {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.75rem;
  border-radius: 999px;
  font-family: 'Outfit', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  white-space: nowrap;
}

.points-chip {
  background: rgba(245,158,11,0.15);
  border: 1px solid rgba(245,158,11,0.3);
  color: #fbbf24;
}

/* Health Chip */
.health-chip {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
}
.health-chip:hover {
  border-color: rgba(239, 68, 68, 0.8);
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.25);
}
.health-chip.state-normal {
  background: rgba(34, 197, 94, 0.15);
  border-color: rgba(34, 197, 94, 0.4);
  color: #86efac;
}
.health-chip.state-warning {
  background: rgba(245, 158, 11, 0.15);
  border-color: rgba(245, 158, 11, 0.4);
  color: #fde047;
}
.health-chip.state-critical {
  background: rgba(239, 68, 68, 0.25);
  border-color: rgba(239, 68, 68, 0.85);
  color: #fca5a5;
  animation: navPulseCritical 1.2s infinite ease-in-out;
}
.health-chip.state-defeated {
  background: rgba(185, 28, 28, 0.3);
  border-color: rgba(220, 38, 38, 0.9);
  color: #f87171;
}

.health-recovering-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  background: rgba(239, 68, 68, 0.3);
  border: 1px solid rgba(239, 68, 68, 0.7);
  padding: 1px 4px;
  border-radius: 4px;
  color: #fca5a5;
  letter-spacing: 0.04em;
}

@keyframes navPulseCritical {
  0%, 100% { box-shadow: 0 0 4px rgba(239, 68, 68, 0.3); }
  50% { box-shadow: 0 0 12px rgba(239, 68, 68, 0.7); }
}

.xp-chip {
  background: rgba(124,32,240,0.15);
  border: 1px solid rgba(124,32,240,0.3);
  color: #b980fd;
}

.streak-chip {
  background: rgba(249,115,22,0.15);
  border: 1px solid rgba(249,115,22,0.3);
  color: #fb923c;
}

.stat-value { font-size: 0.9rem; }

.navbar-avatar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  padding: 0.25rem 0.75rem 0.25rem 0.25rem;
  transition: all 0.2s;
}

.navbar-avatar:hover {
  border-color: rgba(124,32,240,0.4);
  background: rgba(124,32,240,0.1);
}

.avatar-level {
  font-family: 'Outfit', sans-serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #b980fd;
}

/* Mobile Drawer */
.mobile-drawer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  height: 100dvh;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 99999;
  display: flex;
}

.mobile-drawer {
  position: relative;
  width: 290px;
  max-width: 85vw;
  height: 100%;
  background: #0d1117;
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  padding: 1.25rem;
  box-shadow: 10px 0 35px rgba(0, 0, 0, 0.85);
  overflow: hidden;
}

.drawer-fade-enter-active,
.drawer-fade-leave-active {
  transition: opacity 0.25s ease;
}
.drawer-fade-enter-from,
.drawer-fade-leave-to {
  opacity: 0;
}
.drawer-fade-enter-active .mobile-drawer {
  animation: slideInLeft 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes slideInLeft {
  from { transform: translateX(-100%); }
  to { transform: translateX(0); }
}

.drawer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--color-border);
}

.drawer-logo {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.drawer-logo .logo-icon {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #6b10d8, #b980fd);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  color: white;
}

.drawer-logo .logo-text {
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 1.1rem;
  color: #fff;
}

.close-drawer-btn {
  background: none;
  border: none;
  color: var(--color-muted);
  font-size: 1.2rem;
  cursor: pointer;
}

.drawer-nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  overflow-y: auto;
  padding-right: 0.25rem;
}

.drawer-nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  color: var(--color-muted);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.2s;
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
}

.drawer-nav-item:hover, .drawer-nav-item.active {
  background: rgba(124, 32, 240, 0.15);
  color: #fff;
}

.drawer-bottom {
  border-top: 1px solid var(--color-border);
  padding-top: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.logout-btn {
  color: #ef4444 !important;
}
</style>
