<template>
  <section class="home-how" id="how-it-works">
    <div class="how-container">
      <!-- Section Header -->
      <div class="how-header">
        <div class="how-pill">
          <span>THE SCHOLAR'S WORKFLOW</span>
        </div>
        <h2 class="how-title">
          HOW STUDYQUEST<br />
          <span class="text-gold">UNFOLDS YOUR JOURNEY</span>
        </h2>
        <p class="how-subtitle">
          An intuitive four-step loop that connects structured academic reading with fantasy RPG battles and rigorous examination diagnostics.
        </p>
      </div>

      <!-- Step Cards Pipeline -->
      <div class="steps-grid">
        <div
          v-for="step in steps"
          :key="step.num"
          class="step-card"
        >
          <!-- Step Counter Badge -->
          <div class="step-num-badge">
            <span class="step-num">{{ step.num }}</span>
          </div>

          <!-- Step Content -->
          <div class="step-content">
            <div class="step-icon-row">
              <span class="step-icon">{{ step.icon }}</span>
              <span class="step-module-tag">{{ step.module }}</span>
            </div>
            <h3 class="step-action">{{ step.action }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
          </div>

          <!-- Visual Progress Accent Line -->
          <div class="step-bottom-bar" :style="{ background: step.accent }"></div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const steps = [
  {
    num: '01',
    action: 'LEARN',
    module: 'STUDY HALL',
    icon: '📖',
    accent: '#3b82f6',
    desc: 'Study relevant lessons and reviewers through Study Hall. Navigate chapters, listen with audio recitations, and digest core concepts.'
  },
  {
    num: '02',
    action: 'CHALLENGE YOURSELF',
    module: 'ADVENTURE & EXAMS',
    icon: '⚔️',
    accent: '#ef4444',
    desc: 'Apply what you have learned through supported knowledge-based challenges, minion skirmishes, domain bosses, and mock examinations.'
  },
  {
    num: '03',
    action: 'PROGRESS',
    module: 'CHARACTER REALM',
    icon: '🛡️',
    accent: '#10b981',
    desc: 'Advance through the RPG world, complete daily quest bounties, level up your scholar, and allocate attribute milestones across INT, AGI, and VIT.'
  },
  {
    num: '04',
    action: 'TRACK YOUR JOURNEY',
    module: 'ANALYTICS & FAME',
    icon: '🏆',
    accent: '#f59e0b',
    desc: 'Review your progress, achievements, supported rankings, Bloom\'s taxonomy diagnostics, and prepare for permanent induction into the Hall of Fame.'
  }
]
</script>

<style scoped>
.home-how {
  padding: 6rem 1.5rem;
  position: relative;
  border-top: 1px solid;
  border-bottom: 1px solid;
  transition: background-color 0.3s ease;
}

.home-view.theme- .home-how {
  background: #0a0c12;
  border-color: #1f1a26;
}

.home-view.theme- .home-how {
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.how-container {
  max-width: 1280px;
  margin: 0 auto;
}

/* Header */
.how-header {
  text-align: center;
  max-width: 780px;
  margin: 0 auto 4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.how-pill {
  display: inline-block;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 1.25rem;
  border: 1px solid;
}

.home-view.theme- .how-pill {
  background: rgba(212, 160, 23, 0.15);
  border-color: rgba(212, 160, 23, 0.3);
  color: #d4a017;
}

.home-view.theme- .how-pill {
  background: #fef3c7;
  border-color: #fde68a;
  color: #92400e;
}

.how-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.75rem;
  line-height: 1.45;
  margin-bottom: 1.25rem;
  font-weight: 800;
}

.home-view.theme- .how-title {
  color: #f0e8d0;
}

.home-view.theme- .how-title {
  color: #0f172a;
}

.text-gold {
  color: #d4a017;
}

.home-view.theme- .text-gold {
  color: #b45309;
}

.how-subtitle {
  font-size: 1.05rem;
  line-height: 1.7;
}

.home-view.theme- .how-subtitle {
  color: #a8967a;
}

.home-view.theme- .how-subtitle {
  color: #475569;
}

/* Steps Grid */
.steps-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.step-card {
  position: relative;
  border-radius: 14px;
  padding: 2rem 1.5rem 1.75rem;
  border: 1px solid;
  display: flex;
  flex-direction: column;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  overflow: hidden;
}

.home-view.theme- .step-card {
  background: #11141e;
  border-color: #242430;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.home-view.theme- .step-card:hover {
  transform: translateY(-6px);
  border-color: #3d2a48;
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.5);
}

.home-view.theme- .step-card {
  background: #ffffff;
  border-color: #e2e8f0;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.home-view.theme- .step-card:hover {
  transform: translateY(-6px);
  border-color: #cbd5e1;
  box-shadow: 0 12px 25px rgba(15, 23, 42, 0.08);
}

.step-num-badge {
  position: absolute;
  top: 1rem;
  right: 1.25rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 1.25rem;
  font-weight: 800;
  opacity: 0.25;
}

.home-view.theme- .step-num-badge {
  color: #f0e8d0;
}

.home-view.theme- .step-num-badge {
  color: #0f172a;
}

.step-content {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.step-icon-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 1rem;
}

.step-icon {
  font-size: 1.75rem;
}

.step-module-tag {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.home-view.theme- .step-module-tag {
  background: rgba(255, 255, 255, 0.06);
  color: #d4a017;
}

.home-view.theme- .step-module-tag {
  background: #f1f5f9;
  color: #b45309;
}

.step-action {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  margin-bottom: 0.85rem;
  line-height: 1.4;
}

.home-view.theme- .step-action {
  color: #f0e8d0;
}

.home-view.theme- .step-action {
  color: #0f172a;
}

.step-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  margin-bottom: 1.25rem;
  flex-grow: 1;
}

.home-view.theme- .step-desc {
  color: #a8967a;
}

.home-view.theme- .step-desc {
  color: #64748b;
}

.step-bottom-bar {
  height: 4px;
  width: 100%;
  border-radius: 2px;
  margin-top: auto;
}

/* Responsive */
@media (max-width: 1100px) {
  .steps-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 680px) {
  .home-how {
    padding: 4rem 1rem;
  }
  .how-title {
    font-size: 1.35rem;
  }
  .steps-grid {
    grid-template-columns: 1fr;
  }
}
</style>
