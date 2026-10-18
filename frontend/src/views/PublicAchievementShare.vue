<template>
  <div class="public-share-page">
    <!-- Header / Brand Bar -->
    <header class="public-share-header">
      <div class="brand-crest">
        <span class="crest-symbol">✦</span>
        <span class="crest-title">STUDYQUEST</span>
      </div>
      <router-link to="/login" class="btn-portal-enter">
        Enter Mindrealm ↗
      </router-link>
    </header>

    <!-- Main Content Container -->
    <main class="public-share-content">
      <!-- Loading State -->
      <div v-if="loading" class="public-share-loading">
        <div class="gothic-spinner"></div>
        <p>Deciphering Chronicle Records...</p>
      </div>

      <!-- Error / Not Found State -->
      <div v-else-if="error" class="public-share-error">
        <div class="error-icon">📜</div>
        <h2>Archive Seal Not Found</h2>
        <p>{{ error }}</p>
        <router-link to="/" class="btn-return-home">
          Return to Gates
        </router-link>
      </div>

      <!-- Verified Achievement Showcase Card -->
      <div v-else-if="shareData" class="achievement-showcase-card animate-card-appear">
        <!-- Card Top: Verification Ribbon -->
        <div class="verification-ribbon">
          <span class="ribbon-icon">🛡️</span>
          <span class="ribbon-text">OFFICIALLY VERIFIED GAMEPLAY ACHIEVEMENT</span>
        </div>

        <!-- Trophy & Icon Section -->
        <div class="hero-icon-container">
          <div class="hero-icon-glow"></div>
          <div class="hero-icon-box">
            <span class="hero-icon-emoji">🏆</span>
          </div>
        </div>

        <!-- Player Attribution -->
        <div class="scholar-badge">
          <span class="scholar-icon">⚔️</span>
          <span class="scholar-name">{{ shareData.player_name }}</span>
          <span class="scholar-level">Level {{ shareData.player_level }}</span>
        </div>

        <!-- Card Title & Category -->
        <div class="achievement-titles">
          <span class="category-pill">{{ shareData.category?.toUpperCase() }}</span>
          <h1 class="main-card-title">{{ shareData.card_title }}</h1>
          <h2 v-if="shareData.milestone_title !== shareData.card_title" class="milestone-sub-title">
            Milestone: {{ shareData.milestone_title }}
          </h2>
          <p class="achievement-desc">{{ shareData.description }}</p>
        </div>

        <!-- Rewards & Status Stamp -->
        <div class="rewards-stamp-row">
          <div class="reward-pill xp">
            <span>⭐ +{{ shareData.xp_reward }} XP</span>
          </div>
          <div class="reward-pill coins">
            <span>🪙 +{{ shareData.coins_reward }} Coins</span>
          </div>

          <div class="status-stamp" :class="{ 'claimed-stamp': shareData.is_claimed }">
            <span v-if="shareData.is_claimed">✓ CLAIMED &amp; RECORDED</span>
            <span v-else>★ UNLOCKED IN REALM</span>
          </div>
        </div>

        <!-- Date Timestamp -->
        <div class="timestamp-row">
          <span>Achieved on: {{ formatDate(shareData.claimed_at || shareData.unlocked_at) }}</span>
        </div>

        <!-- Call to Action Banner -->
        <div class="join-banner">
          <p class="join-text">Conquer psych domains and claim your own glory in StudyQuest.</p>
          <router-link to="/register" class="btn-claim-glory">
            Begin Your Quest Free
          </router-link>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="public-share-footer">
      <span>STUDYQUEST • Gamified Mastery of Cognitive Science &amp; Clinical Knowledge</span>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { achievementService } from '../services/achievementService'
import { useGameStore } from '../stores/game'

const route = useRoute()
const gameStore = useGameStore()

const loading = ref(true)
const error = ref('')
const shareData = ref(null)

function formatDate(dateStr) {
  if (!dateStr) return 'Active Season'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
  } catch {
    return dateStr
  }
}

async function loadPublicData() {
  const token = route.params.token
  if (!token) {
    error.value = 'Missing achievement verification token.'
    loading.value = false
    return
  }

  try {
    const res = await achievementService.getPublicShare(token)
    if (res.data?.success && res.data?.data) {
      shareData.value = res.data.data
    } else {
      error.value = res.data?.message || 'Shared achievement could not be located.'
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'This shared achievement was not found or has been revoked.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPublicData()
})
</script>

<style scoped>
.public-share-page {
  min-height: 100vh;
  background: radial-gradient(circle at center top, #16141f 0%, #0c0d12 60%, #050608 100%);
  color: #f5efe6;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.5rem;
  box-sizing: border-box;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.public-share-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 800px;
  margin: 0 auto;
  width: 100%;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(212, 175, 55, 0.2);
}

.brand-crest {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-weight: 800;
  font-size: 1.25rem;
  color: #ffd700;
  letter-spacing: 0.1em;
}

.btn-portal-enter {
  font-family: 'Cinzel', serif;
  font-size: 0.8rem;
  font-weight: 700;
  color: #e6c845;
  text-decoration: none;
  border: 1px solid rgba(212, 175, 55, 0.4);
  padding: 0.45rem 0.95rem;
  border-radius: 4px;
  background: rgba(212, 175, 55, 0.08);
  transition: all 0.2s;
}

.btn-portal-enter:hover {
  background: rgba(212, 175, 55, 0.2);
  color: #fff;
}

.public-share-content {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 0;
  flex: 1;
}

.public-share-loading,
.public-share-error {
  text-align: center;
  font-family: 'Cinzel', serif;
}

.gothic-spinner {
  width: 36px;
  height: 36px;
  border: 3px solid rgba(212, 175, 55, 0.2);
  border-top-color: #ffd700;
  border-radius: 50%;
  margin: 0 auto 1rem;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-return-home {
  display: inline-block;
  margin-top: 1rem;
  font-family: 'Cinzel', serif;
  color: #e6c845;
  text-decoration: none;
  border: 1px solid #785a15;
  padding: 0.5rem 1rem;
  border-radius: 4px;
}

/* Card Showcase */
.achievement-showcase-card {
  max-width: 620px;
  width: 100%;
  background: linear-gradient(180deg, #13141a 0%, #0a0b0e 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  position: relative;
}

.verification-ribbon {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(212, 175, 55, 0.12);
  border: 1px solid rgba(212, 175, 55, 0.35);
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  font-family: 'Cinzel', serif;
  font-size: 0.68rem;
  font-weight: 800;
  color: #ecd485;
  letter-spacing: 0.08em;
  margin-bottom: 1.5rem;
}

.hero-icon-container {
  position: relative;
  display: inline-block;
  margin-bottom: 1.25rem;
}

.hero-icon-glow {
  position: absolute;
  inset: -15px;
  background: radial-gradient(circle, rgba(255, 215, 0, 0.3) 0%, transparent 70%);
  border-radius: 50%;
}

.hero-icon-box {
  width: 88px;
  height: 88px;
  background: #08090d;
  border: 2px solid #ffd700;
  box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.hero-icon-emoji {
  font-size: 2.75rem;
}

.scholar-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: #191b24;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.35rem 0.85rem;
  border-radius: 4px;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.scholar-name {
  color: #ffffff;
}

.scholar-level {
  color: #e6c845;
  font-size: 0.75rem;
}

.category-pill {
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  font-weight: 800;
  color: #b59410;
  letter-spacing: 0.1em;
  display: block;
  margin-bottom: 0.35rem;
}

.main-card-title {
  font-family: 'Cinzel', serif;
  font-size: 1.85rem;
  font-weight: 800;
  color: #f3ede2;
  margin: 0;
  line-height: 1.25;
}

.milestone-sub-title {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #ecd485;
  margin: 0.4rem 0 0 0;
}

.achievement-desc {
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  color: #b5aa99;
  max-width: 480px;
  margin: 0.85rem auto 0;
  line-height: 1.35;
}

.rewards-stamp-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin: 1.5rem 0 1rem;
}

.reward-pill {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.3rem 0.65rem;
  border-radius: 4px;
}

.reward-pill.xp {
  background: rgba(147, 51, 234, 0.15);
  color: #d8b4fe;
  border: 1px solid rgba(147, 51, 234, 0.4);
}

.reward-pill.coins {
  background: rgba(234, 179, 8, 0.15);
  color: #fde047;
  border: 1px solid rgba(234, 179, 8, 0.4);
}

.status-stamp {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  background: rgba(234, 179, 8, 0.2);
  color: #ffd700;
  border: 1px solid #ffd700;
}

.status-stamp.claimed-stamp {
  background: rgba(72, 187, 120, 0.2);
  color: #68d391;
  border-color: #68d391;
}

.timestamp-row {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  color: #7a7060;
  margin-bottom: 1.5rem;
}

.join-banner {
  padding-top: 1.25rem;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
}

.join-text {
  font-size: 0.85rem;
  color: #a09789;
  margin-bottom: 0.85rem;
}

.btn-claim-glory {
  display: inline-block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #0b0c10;
  background: linear-gradient(180deg, #ffd700 0%, #b8860b 100%);
  border: 1px solid #ffe066;
  padding: 0.75rem 1.4rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: bold;
  box-shadow: 0 0 12px rgba(255, 215, 0, 0.4);
  transition: all 0.2s;
}

.btn-claim-glory:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 20px rgba(255, 215, 0, 0.65);
}

.public-share-footer {
  text-align: center;
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  color: #5c5344;
  padding-top: 1.5rem;
}

/* Morning Mode Theme */
:global([data-theme="morning"] .public-share-page) {
  background: radial-gradient(circle at center top, #faf6ee 0%, #ede4d3 100%);
  color: #2c2416;
}

:global([data-theme="morning"] .achievement-showcase-card) {
  background: #fbf7ee;
  border-color: #c9a84d;
  box-shadow: 0 8px 30px rgba(120, 90, 40, 0.15);
}

:global([data-theme="morning"] .main-card-title) {
  color: #2c2416;
}

:global([data-theme="morning"] .achievement-desc) {
  color: #5a4b37;
}

:global([data-theme="morning"] .scholar-badge) {
  background: #ede3d1;
  color: #2c2416;
}
</style>
