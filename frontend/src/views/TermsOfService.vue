<template>
  <div class="legal-page" :class="`theme-${currentTheme}`">
    <header class="legal-header">
      <div class="legal-header-inner">
        <router-link to="/Home" class="legal-brand">
          <div class="brand-psi">Ψ</div>
          <div class="brand-text">
            <span class="brand-title">STUDYQUEST</span>
            <span class="brand-subtitle">TERMS OF SERVICE</span>
          </div>
        </router-link>
        <div class="legal-header-actions">
          <button class="theme-btn" @click="gameStore.toggleTheme()" :title="gameStore.isMorning ? 'Switch to Night Mode' : 'Switch to Morning Mode'">
            <span>{{ gameStore.isMorning ? '☀ Morning' : '☾ Night' }}</span>
          </button>
          <router-link to="/Home" class="back-link">
            ← Return to Home
          </router-link>
        </div>
      </div>
    </header>

    <main class="legal-container">
      <div class="legal-card">
        <div class="legal-badge">ACADEMIC & PLATFORM TERMS</div>
        <h1 class="legal-title">StudyQuest Terms of Service</h1>
        <p class="legal-effective">Last Updated: October 2026 · Mindrealm Academic Codex</p>

        <div class="legal-divider"></div>

        <section class="legal-section">
          <h2>1. Acceptance of Terms</h2>
          <p>
            By accessing or creating an account on StudyQuest ("the Platform"), you agree to be bound by these Terms of Service. StudyQuest is an immersive, gamified learning platform engineered to assist undergraduate scholars and board-examination candidates in mastering psychology curricula, academic reviewers, and cognitive retention through structured RPG progression.
          </p>
        </section>

        <section class="legal-section">
          <h2>2. Academic Purpose & Educational Scope</h2>
          <p>
            StudyQuest provides academic reviewers, question banks, chapter manuscripts, and mock examinations designed for educational self-assessment. While our content is meticulously curated against official undergraduate syllabi and board-examination frameworks (including General Psychology, Theories of Personality, Psychological Assessment, Abnormal Psychology, and Industrial-Organizational Psychology), StudyQuest is an independent review aid.
          </p>
          <p>
            Achievement of high in-game rankings, XP, or high mock examination scores does not constitute official state licensure, board certification, or professional accreditation, which remain under the sole jurisdiction of authorized regulatory bodies (such as the Professional Regulation Commission).
          </p>
        </section>

        <section class="legal-section">
          <h2>3. Scholar Accounts & Security</h2>
          <p>
            When registering for StudyQuest, you agree to provide an authentic email address and password. You are solely responsible for maintaining the confidentiality of your sanctum credentials. Each account is personal and non-transferable. StudyQuest reserves the right to suspend accounts engaged in credential-sharing, abusive scripting, or intentional disruptions of service.
          </p>
        </section>

        <section class="legal-section">
          <h2>4. Academic Integrity & Examination Honor Code</h2>
          <p>
            StudyQuest's "Test Your Knowledge" and "Daily Examination" modules are built on authentic cognitive retrieval. Scholars are expected to adhere to standard academic integrity principles:
          </p>
          <ul>
            <li>No unauthorized scraping, automated answer injection, or bot scripting during timed trials or examinations.</li>
            <li>No exploitation of software anomalies or network tampering in Multiplayer Arena battles.</li>
            <li>Respectful discourse in competitive channels and leaderboard displays.</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>5. Vault & Scholar-Uploaded Materials</h2>
          <p>
            The StudyQuest Vault provides personal archive capabilities for scholars to upload, organize, and view study documents and custom reviewers. You retain ownership of materials you upload. You warrant that your uploaded materials do not infringe upon third-party copyrights, proprietary institutional test keys, or unlawful content. StudyQuest reserves the right to remove flagged copyrighted content.
          </p>
        </section>

        <section class="legal-section">
          <h2>6. Hall of Fame & Passers Verification Protocol</h2>
          <p>
            The Hall of Fame is an honorary sanctum dedicated to verified board-examination passers. Applications for the Hall of Fame undergo administrative verification. To uphold scholar dignity and statutory privacy protections, applicants may select their display preference:
          </p>
          <ul>
            <li><strong>Full Legal Name:</strong> Displays the scholar's verified legal name, rating, and examination year on the public announcement scroll.</li>
            <li><strong>Privacy Masked:</strong> Protects scholar identity by masking the personal surname while celebrating their academic milestone.</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>7. In-Game Currency & Virtual Items</h2>
          <p>
            Gold, XP, potions, avatar cosmetics, and consumables acquired in StudyQuest are virtual tokens earned through academic diligence or in-game market exchange. Virtual items possess no real-world monetary value and may not be sold or traded for external currency.
          </p>
        </section>

        <section class="legal-section">
          <h2>8. Modifications to Service</h2>
          <p>
            StudyQuest continuously evolves with updated syllabus coverage, improved combat balance, and enhanced learning analytics. We reserve the right to modify or refine platform modules to enhance educational efficacy and stability.
          </p>
        </section>

        <div class="legal-footer-nav">
          <router-link to="/Home" class="btn-primary">Return to StudyQuest Home</router-link>
          <router-link to="/privacy" class="btn-secondary">View Privacy Policy</router-link>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGameStore } from '../stores/game'

const gameStore = useGameStore()
const currentTheme = computed(() => gameStore.theme || 'night')
</script>

<style scoped>
.legal-page {
  min-height: 100vh;
  transition: background-color 0.3s ease, color 0.3s ease;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  padding-bottom: 4rem;
}

/* Night / Dark theme (default) */
.legal-page.theme-night {
  background-color: #080a0e;
  color: #d4c4a8;
}

.legal-page.theme-night .legal-header {
  background: rgba(13, 15, 23, 0.85);
  border-bottom: 1px solid #2a1f30;
}

.legal-page.theme-night .legal-card {
  background: #0d0f17;
  border: 1px solid #2a1f30;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.legal-page.theme-night .legal-title {
  color: #f0e8d0;
}

.legal-page.theme-night .legal-section h2 {
  color: #e8d8b8;
  border-bottom: 1px solid #242430;
}

.legal-page.theme-night .legal-divider {
  background: #2a1f30;
}

.legal-page.theme-night .theme-btn {
  background: #1a1d2a;
  color: #e8d8b8;
  border: 1px solid #3d2a48;
}

.legal-page.theme-night .back-link {
  color: #d4a017;
}

/* Morning / Light theme */
.legal-page.theme-morning {
  background-color: #f8fafc;
  color: #334155;
}

.legal-page.theme-morning .legal-header {
  background: rgba(255, 255, 255, 0.9);
  border-bottom: 1px solid #e2e8f0;
}

.legal-page.theme-morning .legal-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.legal-page.theme-morning .legal-title {
  color: #0f172a;
}

.legal-page.theme-morning .legal-section h2 {
  color: #1e293b;
  border-bottom: 1px solid #f1f5f9;
}

.legal-page.theme-morning .legal-divider {
  background: #e2e8f0;
}

.legal-page.theme-morning .theme-btn {
  background: #f1f5f9;
  color: #0f172a;
  border: 1px solid #cbd5e1;
}

.legal-page.theme-morning .back-link {
  color: #b45309;
}

/* Common Layout */
.legal-header {
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(12px);
  padding: 1rem 1.5rem;
}

.legal-header-inner {
  max-width: 1000px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.legal-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.brand-psi {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #c0392b, #8b1a1a);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 1rem;
  font-weight: bold;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  font-weight: bold;
  letter-spacing: 0.05em;
  color: inherit;
}

.brand-subtitle {
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  opacity: 0.7;
}

.legal-header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.theme-btn {
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.back-link {
  text-decoration: none;
  font-weight: 600;
  font-size: 0.85rem;
  transition: opacity 0.2s;
}

.back-link:hover {
  text-decoration: underline;
}

.legal-container {
  max-width: 900px;
  margin: 2rem auto;
  padding: 0 1.5rem;
}

.legal-card {
  border-radius: 12px;
  padding: 2.5rem;
}

.legal-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  background: rgba(192, 57, 43, 0.15);
  color: #c0392b;
  margin-bottom: 1rem;
}

.legal-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  line-height: 1.2;
}

.legal-effective {
  font-size: 0.85rem;
  opacity: 0.7;
  margin-bottom: 1.5rem;
}

.legal-divider {
  height: 1px;
  width: 100%;
  margin: 1.5rem 0;
}

.legal-section {
  margin-bottom: 2rem;
  line-height: 1.7;
}

.legal-section h2 {
  font-size: 1.2rem;
  font-weight: 700;
  padding-bottom: 0.5rem;
  margin-bottom: 0.75rem;
}

.legal-section p {
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.legal-section ul {
  padding-left: 1.5rem;
  margin-bottom: 0.75rem;
}

.legal-section li {
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.legal-footer-nav {
  display: flex;
  gap: 1rem;
  margin-top: 3rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(150, 150, 150, 0.2);
  flex-wrap: wrap;
}

.btn-primary {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: #c0392b;
  color: #fff;
  border-radius: 6px;
  font-weight: 700;
  text-decoration: none;
  font-size: 0.9rem;
  transition: transform 0.15s, background-color 0.15s;
}

.btn-primary:hover {
  background: #d43f30;
  transform: translateY(-2px);
}

.btn-secondary {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  background: rgba(150, 150, 150, 0.15);
  color: inherit;
  border-radius: 6px;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.9rem;
  transition: background-color 0.15s;
}

.btn-secondary:hover {
  background: rgba(150, 150, 150, 0.25);
}

@media (max-width: 640px) {
  .legal-card {
    padding: 1.5rem;
  }
  .legal-title {
    font-size: 1.5rem;
  }
  .legal-header-inner {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  .legal-header-actions {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
