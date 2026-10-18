<template>
  <div class="home-view" :class="`theme-${currentTheme}`">
    <!-- Top Modern Responsive Navigation Bar -->
    <HomeNavbar />

    <!-- Main Landing Page Content -->
    <main class="home-main">
      <!-- Hero Section -->
      <HomeHero />

      <!-- About Section -->
      <HomeAbout />

      <!-- Features Section (10 core modules) -->
      <HomeFeatures />

      <!-- How It Works Section (4 progressive steps) -->
      <HomeHowItWorks />

      <!-- Call-To-Action Banner -->
      <HomeCta />
    </main>

    <!-- Footer -->
    <HomeFooter
      @open-terms="activeLegalModal = 'terms'"
      @open-privacy="activeLegalModal = 'privacy'"
    />

    <!-- Quick Legal Modal for Terms & Privacy -->
    <HomeLegalModal
      :active-type="activeLegalModal"
      @close="activeLegalModal = null"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGameStore } from '../stores/game'
import HomeNavbar from '../components/home/HomeNavbar.vue'
import HomeHero from '../components/home/HomeHero.vue'
import HomeAbout from '../components/home/HomeAbout.vue'
import HomeFeatures from '../components/home/HomeFeatures.vue'
import HomeHowItWorks from '../components/home/HomeHowItWorks.vue'
import HomeCta from '../components/home/HomeCta.vue'
import HomeFooter from '../components/home/HomeFooter.vue'
import HomeLegalModal from '../components/home/HomeLegalModal.vue'

const gameStore = useGameStore()
const currentTheme = computed(() => gameStore.theme || 'night')
const activeLegalModal = ref(null)

onMounted(() => {
  // Sync document title and meta description for SEO best practices
  document.title = 'StudyQuest — Your Knowledge. Your Quest. Your Legacy.'
  const metaDesc = document.querySelector('meta[name="description"]')
  if (metaDesc) {
    metaDesc.setAttribute('content', 'Turn your learning journey into an adventure with StudyQuest. Explore academic reviewers, challenge your knowledge, progress through a fantasy RPG world, and prepare for your next academic milestone.')
  }
})
</script>

<style scoped>
.home-view {
  min-height: 100vh;
  width: 100%;
  position: relative;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  transition: background-color 0.3s ease, color 0.3s ease;
  overflow-x: hidden;
}

/* Night / Dark Mode Aesthetics (Medieval Dark Fantasy) */
.home-view.theme-night {
  background-color: #080a0e;
  color: #d4c4a8;
}

/* Morning / Light Mode Aesthetics (Crisp, Modern, Educational) */
.home-view.theme-morning {
  background-color: #f8fafc;
  color: #334155;
}

.home-main {
  width: 100%;
  display: flex;
  flex-direction: column;
}

/* Reduced Motion Accessibility */
@media (prefers-reduced-motion: reduce) {
  *, ::before, ::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}
</style>
