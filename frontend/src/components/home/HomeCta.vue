<template>
  <section class="home-cta" id="cta">
    <div class="cta-container">
      <div class="cta-banner">
        <!-- Glowing Atmospheric Corner Accents -->
        <div class="cta-corner tl"></div>
        <div class="cta-corner tr"></div>
        <div class="cta-corner bl"></div>
        <div class="cta-corner br"></div>

        <div class="cta-content">
          <div class="cta-badge">
            <span>⚔️ EMBARK UPON MINDREALM</span>
          </div>

          <h2 class="cta-heading">
            YOUR NEXT QUEST<br />
            <span class="text-crimson">STARTS HERE.</span>
          </h2>

          <p class="cta-text">
            Enter StudyQuest and discover a different way to experience your learning journey.
          </p>

          <!-- Primary GET STARTED Button -->
          <div class="cta-action-wrap">
            <button
              type="button"
              class="btn-cta-get-started"
              @click="handleGetStarted"
              id="cta-get-started-btn"
            >
              <span class="btn-icon">⚔️</span>
              <span class="btn-label">GET STARTED</span>
              <span class="btn-arrow">→</span>
            </button>
          </div>

          <div class="cta-perks">
            <div class="perk-item">
              <span class="perk-check">✓</span>
              <span>Syllabus-Aligned Psychology Curriculum</span>
            </div>
            <div class="perk-item">
              <span class="perk-check">✓</span>
              <span>140-Item Diagnostic Mock Examinations</span>
            </div>
            <div class="perk-item">
              <span class="perk-check">✓</span>
              <span>Free Public Scholar Access</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

// CRITICAL REQUIREMENT: Navigates directly to the existing landing page route ('/')
const EXISTING_LANDING_ROUTE = '/'

function handleGetStarted() {
  router.push(EXISTING_LANDING_ROUTE)
}
</script>

<style scoped>
.home-cta {
  padding: 6rem 1.5rem;
  position: relative;
  overflow: hidden;
}

.cta-container {
  max-width: 1100px;
  margin: 0 auto;
}

.cta-banner {
  border-radius: 24px;
  padding: 4.5rem 2.5rem;
  position: relative;
  text-align: center;
  border: 2px solid;
  overflow: hidden;
}

.home-view.theme- .cta-banner {
  background: radial-gradient(circle at top, #200808 0%, #0d0f17 80%);
  border-color: #3d2a48;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.05);
}

.home-view.theme- .cta-banner {
  background: radial-gradient(circle at top, #fee2e2 0%, #ffffff 80%);
  border-color: #cbd5e1;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
}

.cta-corner {
  position: absolute;
  width: 14px;
  height: 14px;
  border: 2px solid #c0392b;
}

.cta-corner.tl { top: 12px; left: 12px; border-right: none; border-bottom: none; }
.cta-corner.tr { top: 12px; right: 12px; border-left: none; border-bottom: none; }
.cta-corner.bl { bottom: 12px; left: 12px; border-right: none; border-top: none; }
.cta-corner.br { bottom: 12px; right: 12px; border-left: none; border-top: none; }

.cta-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.cta-badge {
  display: inline-block;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 1.5rem;
  border: 1px solid;
}

.home-view.theme- .cta-badge {
  background: rgba(192, 57, 43, 0.2);
  border-color: rgba(231, 76, 60, 0.4);
  color: #ff8585;
}

.home-view.theme- .cta-badge {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #dc2626;
}

.cta-heading {
  font-family: 'Press Start 2P', monospace;
  font-size: 2.1rem;
  line-height: 1.35;
  margin-bottom: 1.25rem;
  font-weight: 800;
}

.home-view.theme- .cta-heading {
  color: #f0e8d0;
}

.home-view.theme- .cta-heading {
  color: #0f172a;
}

.text-crimson {
  color: #c0392b;
}

.home-view.theme- .text-crimson {
  color: #b91c1c;
}

.cta-text {
  font-size: 1.15rem;
  line-height: 1.7;
  margin-bottom: 2.5rem;
}

.home-view.theme- .cta-text {
  color: #a8967a;
}

.home-view.theme- .cta-text {
  color: #475569;
}

.cta-action-wrap {
  margin-bottom: 2.5rem;
}

.btn-cta-get-started {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.1rem 2.5rem;
  border-radius: 12px;
  background: linear-gradient(135deg, #c0392b 0%, #8b1a1a 100%);
  color: #ffffff;
  border: 2px solid #e74c3c;
  font-size: 1.05rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  cursor: pointer;
  box-shadow: 0 8px 30px rgba(192, 57, 43, 0.5);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-cta-get-started:hover {
  background: linear-gradient(135deg, #d43f30 0%, #9c1f1f 100%);
  transform: translateY(-4px) scale(1.02);
  box-shadow: 0 12px 35px rgba(192, 57, 43, 0.7);
}

.btn-cta-get-started:active {
  transform: translateY(-1px);
}

.btn-arrow {
  transition: transform 0.2s;
}

.btn-cta-get-started:hover .btn-arrow {
  transform: translateX(4px);
}

.cta-perks {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.75rem;
  flex-wrap: wrap;
}

.perk-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.home-view.theme- .perk-item {
  color: #c9b99a;
}

.home-view.theme- .perk-item {
  color: #475569;
}

.perk-check {
  font-weight: 800;
  color: #10b981;
}

@media (max-width: 680px) {
  .home-cta {
    padding: 3.5rem 1rem;
  }
  .cta-banner {
    padding: 3rem 1.25rem;
  }
  .cta-heading {
    font-size: 1.45rem;
  }
  .cta-text {
    font-size: 0.95rem;
  }
  .btn-cta-get-started {
    width: 100%;
    justify-content: center;
  }
  .cta-perks {
    flex-direction: column;
    gap: 0.75rem;
  }
}
</style>
