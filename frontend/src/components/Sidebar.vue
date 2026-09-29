<template>
  <aside class="sidebar" :class="{ 'sidebar-collapsed': collapsed }">
    <!-- Logo -->
    <div class="sidebar-logo">
      <div class="logo-icon">✦</div>
      <div v-if="!collapsed" class="logo-text">
        <span class="logo-main">StudyQuest</span>
        <span class="logo-sub">Small Steps. Big Dreams.</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        :class="{ active: isActive(item.path) }"
        :title="collapsed ? item.label : ''"
      >
        <span class="nav-icon">{{ item.icon }}</span>
        <span v-if="!collapsed" class="nav-label">{{ item.label }}</span>
        <span v-if="!collapsed && item.badge" class="nav-badge">{{ item.badge }}</span>
      </router-link>
    </nav>

    <!-- Bottom links -->
    <div class="sidebar-bottom">
      <router-link to="/settings" class="nav-item" :class="{ active: isActive('/settings') }">
        <span class="nav-icon">⚙️</span>
        <span v-if="!collapsed" class="nav-label">Settings</span>
      </router-link>
      <button class="nav-item logout-btn" @click="handleLogout">
        <span class="nav-icon">🚪</span>
        <span v-if="!collapsed" class="nav-label">Logout</span>
      </button>
    </div>

    <!-- Collapse toggle -->
    <button class="collapse-btn" @click="collapsed = !collapsed" :title="collapsed ? 'Expand' : 'Collapse'">
      {{ collapsed ? '›' : '‹' }}
    </button>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const route   = useRoute()
const router  = useRouter()
const auth    = useAuthStore()
const toast   = useToastStore()
const collapsed = ref(false)

const navItems = [
  { path: '/dashboard',    icon: '📊', label: 'Dashboard' },
  { path: '/practice',     icon: '🎯', label: 'Practice' },
  { path: '/quests',       icon: '⚔️',  label: 'Quests' },
  { path: '/multiplayer',  icon: '⚡', label: 'Multiplayer' },
  { path: '/leaderboard',  icon: '🏆', label: 'Leaderboard' },
  { path: '/shop',         icon: '🛍️', label: 'Shop' },
  { path: '/rewards',      icon: '💰', label: 'Rewards' },
  { path: '/chronicle',    icon: '📜', label: 'Chronicles' },
  { path: '/boss',         icon: '👹', label: 'Boss Quest' },
  { path: '/achievements', icon: '🎖️', label: 'Achievements' },
  { path: '/profile',      icon: '👤', label: 'Profile' },
]

const isActive = (path) => route.path.startsWith(path)

async function handleLogout() {
  await auth.logout()
  toast.success('See you next time! 👋')
  router.push('/login')
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  left: 0; top: 0; bottom: 0;
  width: var(--sidebar-width);
  background: var(--color-sidebar);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  z-index: 100;
  transition: width 0.3s ease;
  overflow: hidden;
}

.sidebar-collapsed { width: 72px; }

/* Logo */
.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1.25rem 1rem;
  border-bottom: 1px solid var(--color-border);
  min-height: 80px;
}

.logo-icon {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, #6b10d8, #b980fd);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 900;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(124,32,240,0.4);
}

.logo-main {
  display: block;
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 1.05rem;
  background: linear-gradient(135deg, #b980fd, #7c20f0);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  white-space: nowrap;
}

.logo-sub {
  display: block;
  font-size: 0.65rem;
  color: var(--color-muted);
  white-space: nowrap;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
  overflow-x: hidden;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.875rem;
  border-radius: 10px;
  color: var(--color-muted);
  font-family: 'Outfit', sans-serif;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  cursor: pointer;
  border: none;
  background: transparent;
  width: 100%;
  transition: all 0.2s ease;
  white-space: nowrap;
  text-align: left;
}

.nav-item:hover {
  color: var(--color-text);
  background: rgba(255,255,255,0.06);
}

.nav-item.active {
  color: white;
  background: linear-gradient(135deg, rgba(107,16,216,0.3), rgba(155,75,248,0.2));
  border: 1px solid rgba(124,32,240,0.3);
}

.nav-icon { font-size: 1.1rem; flex-shrink: 0; width: 20px; text-align: center; }

.nav-badge {
  margin-left: auto;
  background: linear-gradient(135deg, #6b10d8, #9b4bf8);
  color: white;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
}

.logout-btn { color: #ef4444 !important; }
.logout-btn:hover { background: rgba(239,68,68,0.1) !important; }

/* Bottom */
.sidebar-bottom {
  padding: 0.75rem;
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

/* Collapse */
.collapse-btn {
  position: absolute;
  right: -12px; top: 50%;
  transform: translateY(-50%);
  width: 24px; height: 24px;
  background: #1a2236;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  color: var(--color-muted);
  cursor: pointer;
  font-size: 0.85rem;
  display: flex; align-items: center; justify-content: center;
  z-index: 101;
  transition: all 0.2s;
}
.collapse-btn:hover { color: white; border-color: rgba(124,32,240,0.5); }

@media (max-width: 1024px) {
  .sidebar { display: none !important; }
}
</style>
