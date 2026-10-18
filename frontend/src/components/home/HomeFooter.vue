<template>
  <footer class="home-footer">
    <div class="footer-container">
      <div class="footer-main">
        <!-- Brand & Description -->
        <div class="footer-brand-col">
          <router-link to="/Home" class="footer-brand" @click="scrollToTop">
            <div class="footer-psi-badge">
              <span class="footer-psi">Ψ</span>
            </div>
            <div class="brand-text">
              <span class="footer-brand-name">STUDYQUEST</span>
              <span class="footer-brand-tag">MINDREALM RPG</span>
            </div>
          </router-link>
          <p class="footer-desc">
            An immersive gamified learning platform that combines undergraduate psychology review, fantasy RPG progression, tactical boss battles, and board-examination preparation.
          </p>
          <div class="footer-credits-note">
            <span>Designed for psychology licensure candidates & undergraduate scholars.</span>
          </div>
        </div>

        <!-- Quick Navigation -->
        <div class="footer-nav-col">
          <h4 class="footer-col-title">NAVIGATION</h4>
          <ul class="footer-links-list">
            <li><router-link to="/Home" @click="scrollToTop">Home</router-link></li>
            <li><a href="#about" @click.prevent="scrollTo('#about')">About StudyQuest</a></li>
            <li><a href="#features" @click.prevent="scrollTo('#features')">Explore Features</a></li>
            <li><a href="#how-it-works" @click.prevent="scrollTo('#how-it-works')">How It Works</a></li>
            <li><a href="#cta" @click.prevent="scrollTo('#cta')">Embark on Quest</a></li>
          </ul>
        </div>

        <!-- Scholar Sanctum & Auth -->
        <div class="footer-nav-col">
          <h4 class="footer-col-title">SCHOLAR SANCTUM</h4>
          <ul class="footer-links-list">
            <li><router-link to="/login">Scholar Login</router-link></li>
            <li><router-link to="/register">Register Account</router-link></li>
            <li><router-link to="/world">Sanctum Realm Hub</router-link></li>
            <li><button class="footer-btn-link" @click="handleGetStarted">Existing Title Screen</button></li>
          </ul>
        </div>

        <!-- Governance & Legal -->
        <div class="footer-nav-col">
          <h4 class="footer-col-title">GOVERNANCE & TRUST</h4>
          <ul class="footer-links-list">
            <li>
              <router-link to="/terms">Terms of Service</router-link>
            </li>
            <li>
              <router-link to="/privacy">Privacy Policy</router-link>
            </li>
            <li>
              <button class="footer-btn-link" @click="$emit('open-terms')">
                Quick Terms Modal
              </button>
            </li>
            <li>
              <button class="footer-btn-link" @click="$emit('open-privacy')">
                Quick Privacy Modal
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <p class="copyright-text">
          © 2026 StudyQuest. All rights reserved. Built with Vue.js, PHP, & Tailored Pixel Art.
        </p>
        <div class="footer-tagline">
          <span>FORGE YOUR SOUL IN THE HALLS OF KNOWLEDGE</span>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { useRouter } from 'vue-router'

defineEmits(['open-terms', 'open-privacy'])

const router = useRouter()
const EXISTING_LANDING_ROUTE = '/'

function handleGetStarted() {
  router.push(EXISTING_LANDING_ROUTE)
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
</script>

<style scoped>
.home-footer {
  border-top: 1px solid;
  padding: 5rem 1.5rem 3rem;
  transition: background-color 0.3s ease;
}

.home-view.theme- .home-footer {
  background: #05070a;
  border-color: #1a1622;
}

.home-view.theme- .home-footer {
  background: #ffffff;
  border-color: #e2e8f0;
}

.footer-container {
  max-width: 1280px;
  margin: 0 auto;
}

.footer-main {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 3rem;
  margin-bottom: 4rem;
}

/* Brand Col */
.footer-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  margin-bottom: 1.25rem;
}

.footer-psi-badge {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: linear-gradient(135deg, #c0392b, #8b1a1a);
  display: flex;
  align-items: center;
  justify-content: center;
}

.footer-psi {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.05rem;
  color: #ffffff;
  font-weight: bold;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.footer-brand-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.home-view.theme- .footer-brand-name {
  color: #f0e8d0;
}

.home-view.theme- .footer-brand-name {
  color: #0f172a;
}

.footer-brand-tag {
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  font-weight: 700;
  opacity: 0.7;
}

.home-view.theme- .footer-brand-tag {
  color: #d4a017;
}

.home-view.theme- .footer-brand-tag {
  color: #b45309;
}

.footer-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  max-width: 380px;
  margin-bottom: 1.25rem;
}

.home-view.theme- .footer-desc {
  color: #a8967a;
}

.home-view.theme- .footer-desc {
  color: #64748b;
}

.footer-credits-note {
  font-size: 0.78rem;
  opacity: 0.7;
}

.home-view.theme- .footer-credits-note {
  color: #7a6a55;
}

.home-view.theme- .footer-credits-note {
  color: #94a3b8;
}

/* Nav Cols */
.footer-col-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 1.25rem;
}

.home-view.theme- .footer-col-title {
  color: #d4a017;
}

.home-view.theme- .footer-col-title {
  color: #b45309;
}

.footer-links-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-links-list a,
.footer-btn-link {
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  background: transparent;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: left;
  transition: color 0.15s;
}

.home-view.theme- .footer-links-list a,
.home-view.theme- .footer-btn-link {
  color: #a8967a;
}

.home-view.theme- .footer-links-list a:hover,
.home-view.theme- .footer-btn-link:hover {
  color: #ffffff;
}

.home-view.theme- .footer-links-list a,
.home-view.theme- .footer-btn-link {
  color: #64748b;
}

.home-view.theme- .footer-links-list a:hover,
.home-view.theme- .footer-btn-link:hover {
  color: #0f172a;
}

/* Bottom Bar */
.footer-bottom {
  padding-top: 2rem;
  border-top: 1px solid;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.home-view.theme- .footer-bottom {
  border-top-color: #1a1622;
}

.home-view.theme- .footer-bottom {
  border-top-color: #f1f5f9;
}

.copyright-text {
  font-size: 0.8rem;
}

.home-view.theme- .copyright-text {
  color: #7a6a55;
}

.home-view.theme- .copyright-text {
  color: #94a3b8;
}

.footer-tagline {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  letter-spacing: 0.06em;
  opacity: 0.7;
}

.home-view.theme- .footer-tagline {
  color: #c0392b;
}

.home-view.theme- .footer-tagline {
  color: #dc2626;
}

@media (max-width: 992px) {
  .footer-main {
    grid-template-columns: repeat(2, 1fr);
    gap: 2.5rem;
  }
}

@media (max-width: 640px) {
  .home-footer {
    padding: 3.5rem 1rem 2rem;
  }
  .footer-main {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}
</style>
