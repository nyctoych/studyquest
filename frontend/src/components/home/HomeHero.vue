<template>
  <section class="home-hero" id="hero">
    <!-- Atmospheric Ambient Background -->
    <div class="hero-bg-fx" aria-hidden="true">
      <div class="glow-orb orb-primary"></div>
      <div class="glow-orb orb-secondary"></div>
      <div class="subtle-grid-pattern"></div>
    </div>

    <div class="hero-container">
      <!-- Left Column: Copy & CTAs -->
      <div class="hero-content">
        <!-- Mission Pill Tag -->
        <div class="hero-badge animate-fade-in-1">
          <span class="badge-icon">✨</span>
          <span class="badge-text">THE GAMIFIED PSYCHOLOGY LEARNING REALM</span>
        </div>

        <!-- Headline -->
        <h1 class="hero-headline animate-fade-in-2">
          YOUR KNOWLEDGE.<br />
          <span class="text-accent-crimson">YOUR QUEST.</span><br />
          <span class="text-accent-gold">YOUR LEGACY.</span>
        </h1>

        <!-- Subtitle -->
        <p class="hero-subtitle animate-fade-in-3">
          Turn your learning journey into an adventure. Explore academic reviewers, challenge your knowledge, progress through a fantasy RPG world, and prepare for your next academic milestone with StudyQuest.
        </p>

        <!-- CTAs -->
        <div class="hero-ctas animate-fade-in-4">
          <!-- Primary CTA: Navigates to existing landing page ('/') -->
          <button
            type="button"
            class="cta-btn btn-primary-quest"
            @click="handleGetStarted"
            id="hero-get-started-btn"
          >
            <span class="btn-icon">⚔️</span>
            <span class="btn-label">GET STARTED</span>
            <span class="btn-arrow">→</span>
          </button>

          <!-- Secondary CTA: Scrolls smoothly to features -->
          <a
            href="#features"
            class="cta-btn btn-secondary-explore"
            @click.prevent="scrollToFeatures"
            id="hero-explore-features-btn"
          >
            <span class="btn-icon">📜</span>
            <span class="btn-label">EXPLORE FEATURES</span>
          </a>
        </div>

        <!-- Trust & Value Badges -->
        <div class="hero-metrics animate-fade-in-4">
          <div class="metric-item">
            <span class="metric-num">5+</span>
            <span class="metric-label">Psychology Domains</span>
          </div>
          <div class="metric-divider"></div>
          <div class="metric-item">
            <span class="metric-num">140</span>
            <span class="metric-label">Daily Mock Trial Items</span>
          </div>
          <div class="metric-divider"></div>
          <div class="metric-item">
            <span class="metric-num">100%</span>
            <span class="metric-label">RPG Gamified Mastery</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Interactive Character & Gamified Showcase -->
      <div class="hero-visual animate-fade-in-3">
        <!-- Interactive Card Frame -->
        <div class="character-card-frame" :class="`frame-class-${activeClass.id}`">
          <!-- Card Header & Window Controls -->
          <div class="card-top-bar">
            <div class="card-dots">
              <span class="dot red"></span>
              <span class="dot yellow"></span>
              <span class="dot green"></span>
            </div>
            <div class="card-archetype-tag">
              <span class="archetype-dot"></span>
              <span class="card-label">SCHOLAR ARCHETYPE</span>
            </div>
          </div>

          <!-- Class Tabs (Archer, Melee, Necromancer) -->
          <div class="class-selector-tabs">
            <button
              v-for="cls in classes"
              :key="cls.id"
              type="button"
              class="class-tab"
              :class="{ 'active': activeClass.id === cls.id }"
              @click="activeClass = cls"
            >
              <span class="tab-emoji">{{ cls.icon }}</span>
              <span class="tab-name">{{ cls.name }}</span>
            </button>
          </div>

          <!-- Character Showcase Centerpiece -->
          <div class="character-stage">
            <!-- Stage Ambient Halo Backlight -->
            <div class="stage-ambient-halo" :class="`halo-${activeClass.id}`"></div>
            
            <!-- Runic Arc Ring -->
            <div class="stage-runic-ring" :class="`ring-${activeClass.id}`">
              <div class="ring-dashed"></div>
            </div>

            <!-- Animated Sprite & Ground Pedestal -->
            <div class="sprite-wrap">
              <img
                :src="activeClass.idleGif"
                :alt="activeClass.name"
                class="pixel-art hero-character-sprite"
                loading="eager"
              />
              <div class="actor-ground-pedestal" :class="`pedestal-${activeClass.id}`">
                <div class="pedestal-rune-ring"></div>
              </div>
              <div class="actor-ground-shadow"></div>
            </div>

            <!-- Class Details Box -->
            <div class="class-info-box">
              <div class="class-title-row">
                <span class="class-name">{{ activeClass.name }}</span>
                <span class="class-role-badge" :class="`role-${activeClass.id}`">{{ activeClass.role }}</span>
              </div>
              <p class="class-desc">{{ activeClass.desc }}</p>

              <!-- Attribute Stat Meters -->
              <div class="class-stats-grid">
                <div class="stat-row">
                  <span class="stat-name">INT</span>
                  <div class="stat-bar-track">
                    <div class="stat-bar-fill stat-int" :style="{ width: activeClass.stats.int + '%' }"></div>
                  </div>
                  <span class="stat-val">{{ activeClass.stats.int }}</span>
                </div>
                <div class="stat-row">
                  <span class="stat-name">AGI</span>
                  <div class="stat-bar-track">
                    <div class="stat-bar-fill stat-agi" :style="{ width: activeClass.stats.agi + '%' }"></div>
                  </div>
                  <span class="stat-val">{{ activeClass.stats.agi }}</span>
                </div>
                <div class="stat-row">
                  <span class="stat-name">VIT</span>
                  <div class="stat-bar-track">
                    <div class="stat-bar-fill stat-vit" :style="{ width: activeClass.stats.vit + '%' }"></div>
                  </div>
                  <span class="stat-val">{{ activeClass.stats.vit }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// CRITICAL REQUIREMENT: Navigates directly to the existing landing page route ('/')
const EXISTING_LANDING_ROUTE = '/'

function handleGetStarted() {
  router.push(EXISTING_LANDING_ROUTE)
}

function scrollToFeatures() {
  if (typeof document !== 'undefined') {
    const el = document.getElementById('features')
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

// 3 Authentic StudyQuest Character Classes
const classes = [
  {
    id: 'melee',
    name: 'Dual-Wielder',
    role: 'Melee Vanguard',
    icon: '🗡️',
    idleGif: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee IDLE.gif',
    desc: 'Excels in rapid knowledge recall and relentless mental stamina to cut through complex conceptual trials.',
    stats: { int: 75, agi: 95, vit: 85 }
  },
  {
    id: 'archer',
    name: 'Archer',
    role: 'Range Sentinel',
    icon: '🏹',
    idleGif: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif',
    desc: 'Strikes down diagnostic board exam questions with surgical precision and analytical focus.',
    stats: { int: 85, agi: 90, vit: 70 }
  },
  {
    id: 'necromancer',
    name: 'Necromancer',
    role: 'Arcane Scholar',
    icon: '🔮',
    idleGif: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif',
    desc: 'Harnesses deep theoretical mastery to resurrect forgotten chapters and conquer terrifying domain bosses.',
    stats: { int: 98, agi: 65, vit: 75 }
  }
]

const activeClass = ref(classes[0])
</script>

<style scoped>
.home-hero {
  position: relative;
  overflow: hidden;
  padding: 4.5rem 1.5rem 5.5rem;
}

/* Background Gradients & Glows */
.hero-bg-fx {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 0;
}

.glow-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  opacity: 0.22;
  transition: opacity 0.3s;
}

.home-view.theme- .orb-primary {
  top: 10%;
  right: 15%;
  width: 480px;
  height: 480px;
  background: radial-gradient(circle, #8b1a1a 0%, rgba(139, 26, 26, 0) 70%);
}

.home-view.theme- .orb-secondary {
  bottom: 10%;
  left: 10%;
  width: 420px;
  height: 420px;
  background: radial-gradient(circle, #4a2d6f 0%, rgba(74, 45, 111, 0) 70%);
}

.home-view.theme- .orb-primary {
  top: 10%;
  right: 15%;
  width: 450px;
  height: 450px;
  background: radial-gradient(circle, #fca5a5 0%, rgba(252, 165, 165, 0) 70%);
  opacity: 0.35;
}

.home-view.theme- .orb-secondary {
  bottom: 10%;
  left: 10%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, #fed7aa 0%, rgba(254, 215, 170, 0) 70%);
  opacity: 0.4;
}

.subtle-grid-pattern {
  position: absolute;
  inset: 0;
  background-size: 32px 32px;
  opacity: 0.05;
}

.home-view.theme- .subtle-grid-pattern {
  background-image: linear-gradient(to right, #ffffff 1px, transparent 1px),
                    linear-gradient(to bottom, #ffffff 1px, transparent 1px);
}

.home-view.theme- .subtle-grid-pattern {
  background-image: linear-gradient(to right, #000000 1px, transparent 1px),
                    linear-gradient(to bottom, #000000 1px, transparent 1px);
}

.hero-container {
  max-width: 1280px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  align-items: center;
  gap: 3.5rem;
}

/* Left Column */
.hero-content {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

/* Badge */
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 1.5rem;
  border: 1px solid;
}

.home-view.theme- .hero-badge {
  background: rgba(192, 57, 43, 0.15);
  border-color: rgba(231, 76, 60, 0.35);
  color: #ff8585;
  box-shadow: 0 0 15px rgba(192, 57, 43, 0.2);
}

.home-view.theme- .hero-badge {
  background: #fef2f2;
  border-color: #fecaca;
  color: #dc2626;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
}

/* Headline */
.hero-headline {
  font-family: 'Press Start 2P', monospace;
  font-size: 2.2rem;
  line-height: 1.35;
  letter-spacing: 0.02em;
  margin-bottom: 1.5rem;
  font-weight: 800;
}

.home-view.theme- .hero-headline {
  color: #f0e8d0;
  text-shadow: 0 2px 15px rgba(0, 0, 0, 0.8);
}

.home-view.theme- .hero-headline {
  color: #0f172a;
}

.text-accent-crimson {
  color: #c0392b;
  text-shadow: 0 0 20px rgba(192, 57, 43, 0.4);
}

.text-accent-gold {
  color: #d4a017;
  text-shadow: 0 0 20px rgba(212, 160, 23, 0.4);
}

/* Subtitle */
.hero-subtitle {
  font-size: 1.15rem;
  line-height: 1.7;
  margin-bottom: 2.25rem;
  max-width: 620px;
}

.home-view.theme- .hero-subtitle {
  color: #a8967a;
}

.home-view.theme- .hero-subtitle {
  color: #475569;
}

/* CTAs */
.hero-ctas {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 3rem;
  flex-wrap: wrap;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.85rem 1.65rem;
  border-radius: 10px;
  font-weight: 800;
  font-size: 0.95rem;
  letter-spacing: 0.04em;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  border: none;
}

/* Primary Quest CTA */
.btn-primary-quest {
  background: linear-gradient(135deg, #c0392b 0%, #8b1a1a 100%);
  color: #ffffff;
  border: 1px solid #e74c3c;
  box-shadow: 0 4px 20px rgba(192, 57, 43, 0.45);
}

.btn-primary-quest:hover {
  background: linear-gradient(135deg, #d43f30 0%, #9c1f1f 100%);
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 8px 28px rgba(192, 57, 43, 0.6);
}

.btn-primary-quest:active {
  transform: translateY(-1px);
}

/* Secondary Explore CTA */
.home-view.theme- .btn-secondary-explore {
  background: rgba(20, 22, 32, 0.85);
  border: 1px solid #4a3a2a;
  color: #e8d8b8;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.home-view.theme- .btn-secondary-explore:hover {
  background: #1e2133;
  border-color: #d4a017;
  color: #ffd700;
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(212, 160, 23, 0.25);
}

.home-view.theme- .btn-secondary-explore {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
}

.home-view.theme- .btn-secondary-explore:hover {
  background: #f8fafc;
  border-color: #0f172a;
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(15, 23, 42, 0.1);
}

/* Metrics Row */
.hero-metrics {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid;
}

.home-view.theme- .hero-metrics {
  border-top-color: #242430;
}

.home-view.theme- .hero-metrics {
  border-top-color: #e2e8f0;
}

.metric-item {
  display: flex;
  flex-direction: column;
}

.metric-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.25rem;
  font-weight: 800;
}

.home-view.theme- .metric-num {
  color: #d4a017;
}

.home-view.theme- .metric-num {
  color: #b45309;
}

.metric-label {
  font-size: 0.75rem;
  font-weight: 600;
  opacity: 0.8;
  margin-top: 0.25rem;
}

.home-view.theme- .metric-label {
  color: #a8967a;
}

.home-view.theme- .metric-label {
  color: #64748b;
}

.metric-divider {
  width: 1px;
  height: 28px;
}

.home-view.theme- .metric-divider {
  background: #2a1f30;
}

.home-view.theme- .metric-divider {
  background: #e2e8f0;
}
/* Right Column: Character Showcase Card */
.hero-visual {
  position: relative;
  display: flex;
  justify-content: center;
  width: 100%;
}

.character-card-frame {
  width: 100%;
  max-width: 460px;
  border-radius: 18px;
  padding: 1.6rem 1.5rem;
  position: relative;
  border: 2px solid;
  transition: all 0.3s ease;
  z-index: 2;
}

.home-view.theme- .character-card-frame {
  background: linear-gradient(180deg, #141620 0%, #0d0f17 100%);
  border-color: #3d2a48;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.home-view.theme- .character-card-frame {
  background: #ffffff;
  border-color: #cbd5e1;
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08), 0 2px 4px rgba(15, 23, 42, 0.04);
}

/* Card Top Bar */
.card-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  position: relative;
  z-index: 2;
}

.card-dots {
  display: flex;
  gap: 6px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.dot.red { background: #ef4444; }
.dot.yellow { background: #f59e0b; }
.dot.green { background: #10b981; }

.card-archetype-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  border: 1px solid;
}

.home-view.theme- .card-archetype-tag {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: #d4a017;
}

.home-view.theme- .card-archetype-tag {
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #b45309;
}

.archetype-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
}

.card-label {
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
}

/* Class Tabs */
.class-selector-tabs {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  position: relative;
  z-index: 2;
}

.class-tab {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.55rem 0.35rem;
  border-radius: 10px;
  border: 1px solid;
  cursor: pointer;
  font-size: 0.78rem;
  font-weight: 700;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.home-view.theme- .class-tab {
  background: #1a1d2a;
  border-color: #2a1f30;
  color: #a8967a;
}

.home-view.theme- .class-tab:hover {
  border-color: #4a3a2a;
  color: #f0e8d0;
  transform: translateY(-1px);
}

.home-view.theme- .class-tab.active {
  background: #24142e;
  border-color: #c0392b;
  color: #ffffff;
  box-shadow: 0 0 14px rgba(192, 57, 43, 0.4);
}

.home-view.theme- .class-tab {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}

.home-view.theme- .class-tab:hover {
  border-color: #cbd5e1;
  color: #0f172a;
  transform: translateY(-1px);
}

.home-view.theme- .class-tab.active {
  background: #fee2e2;
  border-color: #ef4444;
  color: #991b1b;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
}

/* Character Stage Showcase */
.character-stage {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  width: 100%;
}

/* Stage Ambient Aura Glow */
.stage-ambient-halo {
  position: absolute;
  top: 15px;
  left: 50%;
  transform: translateX(-50%);
  width: 240px;
  height: 240px;
  border-radius: 50%;
  filter: blur(48px);
  opacity: 0.45;
  pointer-events: none;
  z-index: 1;
  transition: all 0.5s ease;
}

.halo-melee {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.8) 0%, rgba(185, 28, 28, 0.25) 50%, transparent 70%);
}

.halo-archer {
  background: radial-gradient(circle, rgba(45, 212, 191, 0.8) 0%, rgba(13, 148, 136, 0.25) 50%, transparent 70%);
}

.halo-necromancer {
  background: radial-gradient(circle, rgba(168, 85, 247, 0.8) 0%, rgba(109, 40, 217, 0.25) 50%, transparent 70%);
}

/* Runic Arc Ring */
.stage-runic-ring {
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 210px;
  height: 210px;
  border-radius: 50%;
  border: 1px solid rgba(255, 255, 255, 0.08);
  pointer-events: none;
  z-index: 2;
  animation: haloRingRotate 50s linear infinite;
}

@keyframes haloRingRotate {
  from { transform: translateX(-50%) rotate(0deg); }
  to { transform: translateX(-50%) rotate(360deg); }
}

.ring-dashed {
  position: absolute;
  inset: 10px;
  border-radius: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.16);
}

/* Sprite Container */
.sprite-wrap {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 220px;
  width: 100%;
  margin-bottom: 0.75rem;
  z-index: 3;
}

.hero-character-sprite {
  height: 180px;
  width: auto;
  object-fit: contain;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  filter: drop-shadow(0 14px 22px rgba(0, 0, 0, 0.8));
  transition: transform 0.25s ease;
  animation: spriteBounce 3s ease-in-out infinite alternate;
  z-index: 4;
}

@keyframes spriteBounce {
  0% { transform: translateY(0); }
  100% { transform: translateY(-7px); }
}

/* Ground Pedestal */
.actor-ground-pedestal {
  position: absolute;
  bottom: 8px;
  width: 175px;
  height: 26px;
  border-radius: 50%;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid;
  transition: all 0.3s ease;
}

.pedestal-melee {
  background: radial-gradient(ellipse at center, rgba(35, 10, 10, 0.95) 0%, rgba(13, 15, 23, 0.95) 75%);
  border-color: rgba(239, 68, 68, 0.55);
  box-shadow: 0 0 16px rgba(239, 68, 68, 0.4);
}

.pedestal-archer {
  background: radial-gradient(ellipse at center, rgba(13, 48, 53, 0.95) 0%, rgba(13, 15, 23, 0.95) 75%);
  border-color: rgba(45, 212, 191, 0.55);
  box-shadow: 0 0 16px rgba(45, 212, 191, 0.4);
}

.pedestal-necromancer {
  background: radial-gradient(ellipse at center, rgba(28, 14, 45, 0.95) 0%, rgba(13, 15, 23, 0.95) 75%);
  border-color: rgba(168, 85, 247, 0.55);
  box-shadow: 0 0 16px rgba(168, 85, 247, 0.4);
}

.pedestal-rune-ring {
  width: 82%;
  height: 58%;
  border-radius: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.25);
}

.actor-ground-shadow {
  position: absolute;
  bottom: 2px;
  width: 165px;
  height: 16px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.85) 0%, transparent 75%);
  border-radius: 50%;
  filter: blur(4px);
  z-index: 1;
}

/* Class Info Box */
.class-info-box {
  width: 100%;
  border-radius: 12px;
  padding: 1.15rem;
  border: 1px solid;
  position: relative;
  z-index: 3;
}

.home-view.theme- .class-info-box {
  background: #080a0e;
  border-color: #242430;
}

.home-view.theme- .class-info-box {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.class-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.45rem;
}

.class-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  font-weight: 700;
}

.home-view.theme- .class-name {
  color: #f0e8d0;
}

.home-view.theme- .class-name {
  color: #0f172a;
}

.class-role-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 6px;
}

.role-melee {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
}

.role-archer {
  background: rgba(45, 212, 191, 0.15);
  color: #2dd4bf;
}

.role-necromancer {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
}

.class-desc {
  font-size: 0.82rem;
  line-height: 1.55;
  margin-bottom: 0.95rem;
}

.home-view.theme- .class-desc {
  color: #a8967a;
}

.home-view.theme- .class-desc {
  color: #64748b;
}

/* Stats Grid */
.class-stats-grid {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.stat-row {
  display: grid;
  grid-template-columns: 34px 1fr 30px;
  align-items: center;
  gap: 0.65rem;
  font-size: 0.72rem;
  font-weight: 800;
}

.stat-bar-track {
  height: 7px;
  border-radius: 4px;
  overflow: hidden;
}

.home-view.theme- .stat-bar-track {
  background: #1a1d2a;
}

.home-view.theme- .stat-bar-track {
  background: #e2e8f0;
}

.stat-bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.4s ease;
}

.stat-int { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.stat-agi { background: linear-gradient(90deg, #10b981, #34d399); }
.stat-vit { background: linear-gradient(90deg, #ef4444, #f87171); }

.stat-val {
  text-align: right;
  font-family: monospace;
  font-weight: 700;
}



/* Entrance Animations */
.animate-fade-in-1 {
  animation: fadeIn 0.6s ease-out forwards;
}

.animate-fade-in-2 {
  animation: fadeIn 0.8s ease-out 0.15s forwards;
  opacity: 0;
}

.animate-fade-in-3 {
  animation: fadeIn 0.8s ease-out 0.3s forwards;
  opacity: 0;
}

.animate-fade-in-4 {
  animation: fadeIn 0.8s ease-out 0.45s forwards;
  opacity: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .hero-container {
    grid-template-columns: 1fr;
    gap: 3rem;
  }
  .hero-content {
    align-items: center;
    text-align: center;
  }
  .hero-subtitle {
    max-width: 100%;
  }
  .hero-ctas {
    justify-content: center;
  }
  .hero-metrics {
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .home-hero {
    padding: 3rem 1rem 4rem;
  }
  .hero-headline {
    font-size: 1.45rem;
    line-height: 1.4;
  }
  .hero-subtitle {
    font-size: 0.95rem;
  }
  .cta-btn {
    width: 100%;
    justify-content: center;
  }
  .pill-top-right {
    right: 0;
    top: -25px;
  }
  .pill-bottom-left {
    left: 0;
    bottom: -25px;
  }
}
</style>
