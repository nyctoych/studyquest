<template>
  <nav class="home-nav" :class="{ 'scrolled': isScrolled }">
    <div class="nav-container">
      <!-- Brand Logo -->
      <router-link to="/Home" class="nav-logo" @click="scrollToTop">
        <div class="logo-psi-badge">
          <span class="logo-psi">Ψ</span>
        </div>
        <div class="logo-text">
          <span class="logo-name">STUDYQUEST</span>
          <span class="logo-tag">MINDREALM RPG</span>
        </div>
      </router-link>

      <!-- Desktop Nav Links -->
      <div class="nav-links">
        <router-link to="/Home" class="nav-link" active-class="active" @click="scrollToTop">Home</router-link>
        <a href="#about" class="nav-link" @click.prevent="scrollTo('#about')">About</a>
        <a href="#features" class="nav-link" @click.prevent="scrollTo('#features')">Features</a>
        <a href="#how-it-works" class="nav-link" @click.prevent="scrollTo('#how-it-works')">How It Works</a>
      </div>

      <!-- Actions & Theme Toggle -->
      <div class="nav-actions">
        <!-- Theme Toggle -->
        <button
          type="button"
          class="theme-toggle-btn"
          @click="toggleTheme"
          :title="gameStore.isMorning ? 'Switch to Night Mode (Dark)' : 'Switch to Morning Mode (Light)'"
          aria-label="Toggle visual theme"
        >
          <span class="theme-icon">{{ gameStore.isMorning ? '☀' : '☾' }}</span>
          <span class="theme-text">{{ gameStore.isMorning ? 'Day' : 'Night' }}</span>
        </button>

        <!-- Auth Links -->
        <template v-if="!authStore.isLoggedIn">
          <router-link to="/login" class="nav-btn btn-ghost">LOGIN</router-link>
          <router-link to="/register" class="nav-btn btn-outline">REGISTER</router-link>
        </template>
        <template v-else>
          <router-link to="/world" class="nav-btn btn-user-pill" title="Return to your Sanctum World">
            <span class="user-lvl">Lv.{{ authStore.level }}</span>
            <span class="user-nick">{{ authStore.nickname }}</span>
          </router-link>
        </template>

        <!-- Primary GET STARTED Button -->
        <button
          type="button"
          class="nav-btn btn-get-started"
          @click="handleGetStarted"
          id="nav-get-started-btn"
        >
          <span class="btn-text">GET STARTED</span>
          <span class="btn-arrow">→</span>
        </button>

        <!-- Mobile Menu Toggle Button -->
        <button
          type="button"
          class="mobile-menu-btn"
          @click="mobileMenuOpen = !mobileMenuOpen"
          :aria-expanded="mobileMenuOpen"
          aria-label="Toggle navigation menu"
        >
          <span class="hamburger-bar" :class="{ 'open': mobileMenuOpen }"></span>
          <span class="hamburger-bar" :class="{ 'open': mobileMenuOpen }"></span>
          <span class="hamburger-bar" :class="{ 'open': mobileMenuOpen }"></span>
        </button>
      </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <transition name="mobile-slide">
      <div v-if="mobileMenuOpen" class="mobile-drawer" @click.self="mobileMenuOpen = false">
        <div class="mobile-drawer-content">
          <div class="mobile-nav-links">
            <router-link to="/Home" class="mobile-nav-link" @click="mobileMenuOpen = false; scrollToTop()">
              🏰 Home
            </router-link>
            <a href="#about" class="mobile-nav-link" @click="mobileMenuOpen = false; scrollTo('#about')">
              📜 About StudyQuest
            </a>
            <a href="#features" class="mobile-nav-link" @click="mobileMenuOpen = false; scrollTo('#features')">
              ⚔️ Explore Features
            </a>
            <a href="#how-it-works" class="mobile-nav-link" @click="mobileMenuOpen = false; scrollTo('#how-it-works')">
              🗺️ How It Works
            </a>
            <a href="#cta" class="mobile-nav-link" @click="mobileMenuOpen = false; scrollTo('#cta')">
              ✨ Embark on Quest
            </a>
          </div>

          <div class="mobile-divider"></div>

          <!-- Mobile Theme & Auth Controls -->
          <div class="mobile-nav-actions">
            <button
              type="button"
              class="mobile-theme-btn"
              @click="toggleTheme"
            >
              <span>{{ gameStore.isMorning ? '☀ Morning / Light' : '☾ Night / Dark' }}</span>
              <span class="mobile-toggle-badge">Toggle</span>
            </button>

            <template v-if="!authStore.isLoggedIn">
              <div class="mobile-auth-grid">
                <router-link to="/login" class="mobile-btn-auth" @click="mobileMenuOpen = false">
                  🔑 Scholar Login
                </router-link>
                <router-link to="/register" class="mobile-btn-auth highlight" @click="mobileMenuOpen = false">
                  📜 Register Sanctum
                </router-link>
              </div>
            </template>
            <template v-else>
              <router-link to="/world" class="mobile-user-link" @click="mobileMenuOpen = false">
                <span>🛡️ Enter Realm ({{ authStore.nickname }})</span>
                <span class="lvl-badge">Lv. {{ authStore.level }}</span>
              </router-link>
            </template>

            <!-- Mobile Get Started -->
            <button
              type="button"
              class="mobile-btn-get-started"
              @click="mobileMenuOpen = false; handleGetStarted()"
            >
              ⚔️ GET STARTED NOW →
            </button>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'

const router = useRouter()
const authStore = useAuthStore()
const gameStore = useGameStore()

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)

// CRITICAL REQUIREMENT: Navigates directly to the existing landing page route ('/')
const EXISTING_LANDING_ROUTE = '/'

function handleGetStarted() {
  router.push(EXISTING_LANDING_ROUTE)
}

function toggleTheme() {
  gameStore.toggleTheme()
}

function handleScroll() {
  if (typeof window !== 'undefined') {
    isScrolled.value = window.scrollY > 20
  }
}

function scrollToTop() {
  if (typeof window !== 'undefined') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

function scrollTo(selector) {
  if (typeof document !== 'undefined') {
    const el = document.querySelector(selector)
    if (el) {
      const topOffset = 80
      const elementPosition = el.getBoundingClientRect().top
      const offsetPosition = elementPosition + window.pageYOffset - topOffset
      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
      })
    }
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('scroll', handleScroll, { passive: true })
    handleScroll()
  }
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleScroll)
  }
})
</script>

<style scoped>
.home-nav {
  position: sticky;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 100;
  transition: all 0.25s ease;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

/* Night / Dark styling */
:deep(.home-view.theme-night) .home-nav,
.home-nav {
  background: rgba(8, 10, 14, 0.88);
  border-bottom: 1px solid rgba(42, 31, 48, 0.7);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

:deep(.home-view.theme-night) .home-nav.scrolled,
.home-nav.scrolled {
  background: rgba(8, 10, 14, 0.96);
  border-bottom-color: rgba(61, 42, 72, 0.9);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.6);
}

/* Morning / Light styling */
:deep(.home-view.theme-morning) .home-nav {
  background: rgba(255, 255, 255, 0.9);
  border-bottom: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
}

:deep(.home-view.theme-morning) .home-nav.scrolled {
  background: rgba(255, 255, 255, 0.98);
  border-bottom-color: rgba(203, 213, 225, 1);
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
}

.nav-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0.85rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

/* Logo */
.nav-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  cursor: pointer;
}

.logo-psi-badge {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #c0392b, #8b1a1a);
  border: 1px solid #e74c3c;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 12px rgba(192, 57, 43, 0.4);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.nav-logo:hover .logo-psi-badge {
  transform: scale(1.05) rotate(-3deg);
  box-shadow: 0 0 18px rgba(192, 57, 43, 0.6);
}

.logo-psi {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.1rem;
  color: #ffffff;
  font-weight: bold;
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.95rem;
  letter-spacing: 0.06em;
  font-weight: 800;
  transition: color 0.2s;
}

:deep(.home-view.theme-night) .logo-name,
.logo-name {
  color: #f0e8d0;
  text-shadow: 0 0 10px rgba(240, 232, 208, 0.2);
}

:deep(.home-view.theme-morning) .logo-name {
  color: #0f172a;
  text-shadow: none;
}

.logo-tag {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  opacity: 0.7;
}

:deep(.home-view.theme-night) .logo-tag,
.logo-tag {
  color: #d4a017;
}

:deep(.home-view.theme-morning) .logo-tag {
  color: #b45309;
}

/* Nav Links */
.nav-links {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-link {
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  transition: all 0.2s ease;
  position: relative;
}

:deep(.home-view.theme-night) .nav-link,
.nav-link {
  color: #d4c4a8;
}

:deep(.home-view.theme-night) .nav-link:hover,
.nav-link:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

:deep(.home-view.theme-morning) .nav-link {
  color: #475569;
}

:deep(.home-view.theme-morning) .nav-link:hover {
  color: #0f172a;
  background: rgba(15, 23, 42, 0.05);
}

/* Nav Actions */
.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Theme Toggle */
.theme-toggle-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  border-radius: 9999px;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 700;
  transition: all 0.2s ease;
}

:deep(.home-view.theme-night) .theme-toggle-btn,
.theme-toggle-btn {
  background: #141620;
  border: 1px solid #2a1f30;
  color: #e8d8b8;
}

:deep(.home-view.theme-night) .theme-toggle-btn:hover,
.theme-toggle-btn:hover {
  border-color: #d4a017;
  background: #1e2133;
  box-shadow: 0 0 10px rgba(212, 160, 23, 0.25);
}

:deep(.home-view.theme-morning) .theme-toggle-btn {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #0f172a;
}

:deep(.home-view.theme-morning) .theme-toggle-btn:hover {
  border-color: #f59e0b;
  background: #e2e8f0;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
}

.theme-icon {
  font-size: 0.95rem;
}

/* Buttons */
.nav-btn {
  padding: 0.45rem 1rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  cursor: pointer;
  border: none;
}

.btn-ghost {
  background: transparent;
}

:deep(.home-view.theme-night) .btn-ghost,
.btn-ghost {
  color: #e8d8b8;
}

:deep(.home-view.theme-night) .btn-ghost:hover,
.btn-ghost:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

:deep(.home-view.theme-morning) .btn-ghost {
  color: #334155;
}

:deep(.home-view.theme-morning) .btn-ghost:hover {
  color: #0f172a;
  background: rgba(15, 23, 42, 0.06);
}

.btn-outline {
  border: 1px solid;
}

:deep(.home-view.theme-night) .btn-outline,
.btn-outline {
  border-color: #4a3a2a;
  color: #f0e8d0;
  background: rgba(20, 22, 32, 0.6);
}

:deep(.home-view.theme-night) .btn-outline:hover,
.btn-outline:hover {
  border-color: #d4a017;
  color: #ffd700;
  transform: translateY(-1px);
}

:deep(.home-view.theme-morning) .btn-outline {
  border-color: #cbd5e1;
  color: #0f172a;
  background: #ffffff;
}

:deep(.home-view.theme-morning) .btn-outline:hover {
  border-color: #0f172a;
  transform: translateY(-1px);
}

/* User Pill (for logged in) */
.btn-user-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  border: 1px solid;
}

:deep(.home-view.theme-night) .btn-user-pill,
.btn-user-pill {
  background: #141620;
  border-color: #3d2a48;
  color: #d4c4a8;
}

:deep(.home-view.theme-morning) .btn-user-pill {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #0f172a;
}

.user-lvl {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #d4a017;
}

.user-nick {
  font-weight: 700;
  font-size: 0.82rem;
}

/* Primary GET STARTED Button */
.btn-get-started {
  background: linear-gradient(135deg, #c0392b 0%, #8b1a1a 100%);
  color: #ffffff;
  border: 1px solid #e74c3c;
  box-shadow: 0 2px 10px rgba(192, 57, 43, 0.4);
  gap: 0.4rem;
  position: relative;
  overflow: hidden;
}

.btn-get-started:hover {
  background: linear-gradient(135deg, #d43f30 0%, #9c1f1f 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 18px rgba(192, 57, 43, 0.6);
}

.btn-get-started:active {
  transform: translateY(0);
}

.btn-arrow {
  transition: transform 0.2s ease;
}

.btn-get-started:hover .btn-arrow {
  transform: translateX(3px);
}

/* Mobile Menu Toggle */
.mobile-menu-btn {
  display: none;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  flex-direction: column;
  gap: 5px;
}

.hamburger-bar {
  width: 22px;
  height: 2px;
  border-radius: 2px;
  transition: all 0.3s ease;
}

:deep(.home-view.theme-night) .hamburger-bar,
.hamburger-bar {
  background: #e8d8b8;
}

:deep(.home-view.theme-morning) .hamburger-bar {
  background: #0f172a;
}

/* Mobile Drawer */
.mobile-drawer {
  position: fixed;
  top: 65px;
  left: 0;
  width: 100%;
  height: calc(100vh - 65px);
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  z-index: 99;
  display: flex;
  justify-content: flex-end;
}

.mobile-drawer-content {
  width: 82%;
  max-width: 340px;
  height: 100%;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  overflow-y: auto;
}

:deep(.home-view.theme-night) .mobile-drawer-content,
.mobile-drawer-content {
  background: #0d0f17;
  border-left: 1px solid #2a1f30;
}

:deep(.home-view.theme-morning) .mobile-drawer-content {
  background: #ffffff;
  border-left: 1px solid #e2e8f0;
}

.mobile-nav-links {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mobile-nav-link {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  text-decoration: none;
  transition: background-color 0.2s;
}

:deep(.home-view.theme-night) .mobile-nav-link,
.mobile-nav-link {
  color: #d4c4a8;
}

:deep(.home-view.theme-night) .mobile-nav-link:hover,
.mobile-nav-link:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #ffffff;
}

:deep(.home-view.theme-morning) .mobile-nav-link {
  color: #334155;
}

:deep(.home-view.theme-morning) .mobile-nav-link:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.mobile-divider {
  height: 1px;
}

:deep(.home-view.theme-night) .mobile-divider,
.mobile-divider {
  background: #2a1f30;
}

:deep(.home-view.theme-morning) .mobile-divider {
  background: #e2e8f0;
}

.mobile-nav-actions {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.mobile-theme-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid;
  cursor: pointer;
  font-weight: 700;
  font-size: 0.85rem;
}

:deep(.home-view.theme-night) .mobile-theme-btn,
.mobile-theme-btn {
  background: #141620;
  border-color: #2a1f30;
  color: #e8d8b8;
}

:deep(.home-view.theme-morning) .mobile-theme-btn {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #0f172a;
}

.mobile-toggle-badge {
  font-size: 0.75rem;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  background: rgba(212, 160, 23, 0.2);
  color: #d4a017;
}

.mobile-auth-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.mobile-btn-auth {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.65rem 0.5rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  text-decoration: none;
  border: 1px solid;
  text-align: center;
}

:deep(.home-view.theme-night) .mobile-btn-auth,
.mobile-btn-auth {
  border-color: #3d2a48;
  color: #d4c4a8;
  background: #141620;
}

:deep(.home-view.theme-morning) .mobile-btn-auth {
  border-color: #cbd5e1;
  color: #334155;
  background: #f8fafc;
}

.mobile-btn-auth.highlight {
  border-color: #c0392b;
  color: #ff6b6b;
}

.mobile-user-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid #3d2a48;
  background: #141620;
  color: #e8d8b8;
  text-decoration: none;
  font-weight: 700;
}

.lvl-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #d4a017;
}

.mobile-btn-get-started {
  padding: 0.85rem;
  border-radius: 8px;
  background: linear-gradient(135deg, #c0392b, #8b1a1a);
  border: 1px solid #e74c3c;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.9rem;
  letter-spacing: 0.05em;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(192, 57, 43, 0.4);
}

/* Animations */
.mobile-slide-enter-active,
.mobile-slide-leave-active {
  transition: opacity 0.25s ease;
}

.mobile-slide-enter-from,
.mobile-slide-leave-to {
  opacity: 0;
}

/* Media Queries */
@media (max-width: 992px) {
  .nav-links {
    display: none;
  }
}

@media (max-width: 768px) {
  .theme-toggle-btn {
    display: none;
  }
  .btn-ghost,
  .btn-outline,
  .btn-user-pill {
    display: none;
  }
  .mobile-menu-btn {
    display: flex;
  }
  .btn-get-started {
    padding: 0.4rem 0.75rem;
    font-size: 0.75rem;
  }
}
</style>
