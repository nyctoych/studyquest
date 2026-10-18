<template>
  <div class="legal-page" :class="`theme-${currentTheme}`">
    <header class="legal-header">
      <div class="legal-header-inner">
        <router-link to="/Home" class="legal-brand">
          <div class="brand-psi">Ψ</div>
          <div class="brand-text">
            <span class="brand-title">STUDYQUEST</span>
            <span class="brand-subtitle">PRIVACY POLICY</span>
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
        <div class="legal-badge">PRIVACY & DATA PROTECTION</div>
        <h1 class="legal-title">StudyQuest Privacy Policy</h1>
        <p class="legal-effective">Last Updated: October 2026 · Scholar Data Security Standard</p>

        <div class="legal-divider"></div>

        <section class="legal-section">
          <h2>1. Introduction</h2>
          <p>
            StudyQuest ("we", "our", or "the Platform") respects the privacy and confidentiality of students, educators, and board-examination candidates. This Privacy Policy details how we collect, store, protect, and handle your information across our educational RPG services.
          </p>
        </section>

        <section class="legal-section">
          <h2>2. Information We Collect</h2>
          <p>We collect only information necessary to deliver and personalize your learning experience:</p>
          <ul>
            <li><strong>Account Credentials:</strong> Username, email address, hashed password (using industry-standard bcrypt encryption), and chosen character class (Archer, Dual-Wielder, or Necromancer).</li>
            <li><strong>Academic Progress & Assessment Metrics:</strong> Chapter completion logs in Study Hall, Bloom's taxonomy performance analytics, quiz and mock examination answers, and study streaks.</li>
            <li><strong>Game Mechanics & Progression:</strong> XP earnings, level allocations, virtual items acquired in the Market, and battle results against Adventure Gate bosses.</li>
            <li><strong>Personal Vault Archives:</strong> Reviewer notes, study documents, or flashcards uploaded to your private Vault storage.</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>3. Hall of Fame & Name Privacy Controls</h2>
          <p>
            StudyQuest features an honorary Hall of Fame recognizing verified board-examination passers. In compliance with student privacy expectations, every applicant retains granular control over their public visibility:
          </p>
          <ul>
            <li><strong>Full Legal Name Preference:</strong> If selected, your verified full name, examination date, and passing milestone are published on the public Sanctum scroll.</li>
            <li><strong>Privacy Masked Preference:</strong> If selected, your surname and identifying details are obscured with the "🔒 PRIVACY MASKED" protocol, celebrating your academic achievement without exposing personal records.</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>4. Data Storage, Security & Hashing</h2>
          <p>
            We implement technical and organizational measures to protect your scholar records from unauthorized access, alteration, or disclosure:
          </p>
          <ul>
            <li>All passwords are encrypted with one-way bcrypt hashing before database storage. Plaintext passwords are never recorded or logged.</li>
            <li>API communications between the Vue client and PHP backend utilize secure headers, token validation, and prepared SQL queries to prevent injection vulnerabilities.</li>
            <li>Local client storage (localStorage) is utilized exclusively for persistent client-side configuration, including your Morning/Night visual theme preference (<code>studyquest_theme</code>) and sound volume preferences (<code>studyquest_settings</code>).</li>
          </ul>
        </section>

        <section class="legal-section">
          <h2>5. Non-Disclosure & Third-Party Protections</h2>
          <p>
            StudyQuest will never sell, lease, or monetize scholar learning data, test responses, or contact information to commercial advertising networks or third-party brokers.
          </p>
        </section>

        <section class="legal-section">
          <h2>6. Scholar Rights</h2>
          <p>
            You possess the right to inspect, correct, or request deletion of your account records and uploaded Vault study notes at any time through your Profile Settings or by submitting a sanctum support ticket.
          </p>
        </section>

        <div class="legal-footer-nav">
          <router-link to="/Home" class="btn-primary">Return to StudyQuest Home</router-link>
          <router-link to="/terms" class="btn-secondary">View Terms of Service</router-link>
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
  background: linear-gradient(135deg, #2a8088, #0d3035);
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
  background: rgba(42, 128, 136, 0.15);
  color: #2a8088;
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
  background: #2a8088;
  color: #fff;
  border-radius: 6px;
  font-weight: 700;
  text-decoration: none;
  font-size: 0.9rem;
  transition: transform 0.15s, background-color 0.15s;
}

.btn-primary:hover {
  background: #369fa9;
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
