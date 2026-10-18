<template>
  <section class="home-about" id="about">
    <div class="about-container">
      <!-- Section Header -->
      <div class="about-header">
        <div class="about-pill">
          <span class="pill-dot"></span>
          <span>ACADEMIC FOUNDATIONS × RPG MECHANICS</span>
        </div>
        <h2 class="about-heading">
          MORE THAN A GAME.<br />
          <span class="heading-gradient">A JOURNEY THROUGH KNOWLEDGE.</span>
        </h2>
        <p class="about-lead">
          StudyQuest brings academic learning and role-playing game mechanics together in one connected experience. Explore structured reviewers, face knowledge-based challenges, track your progress, and build your character as you advance through Mindrealm.
        </p>
      </div>

      <!-- 4 Pillars Grid -->
      <div class="pillars-grid">
        <div
          v-for="(pillar, idx) in pillars"
          :key="pillar.title"
          class="pillar-card"
          :class="`pillar-${idx + 1}`"
        >
          <div class="card-glow"></div>
          <div class="card-header-row">
            <span class="pillar-icon">{{ pillar.icon }}</span>
            <span class="pillar-tag">{{ pillar.tag }}</span>
          </div>
          <h3 class="pillar-title">{{ pillar.title }}</h3>
          <p class="pillar-desc">{{ pillar.desc }}</p>
          <div class="pillar-footer">
            <span class="pillar-highlight">{{ pillar.highlight }}</span>
          </div>
        </div>
      </div>

      <!-- Mindrealm Philosophy Callout -->
      <div class="philosophy-box">
        <div class="philosophy-psi">Ψ</div>
        <div class="philosophy-content">
          <h4>Engineered for Board-Examination Candidates & Undergraduate Scholars</h4>
          <p>
            Traditional rote memorization often leads to study fatigue. StudyQuest transforms review sessions into an epic odyssey where active retrieval practice fuels your combat actions, and diagnostic performance tracking prepares you for critical licensure milestones.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const pillars = [
  {
    icon: '📖',
    tag: 'CURRICULUM ARCHIVE',
    title: 'Structured Academic Reviewers',
    desc: 'Access organized psychology domains, foundational chapters, and comprehensive glossaries designed in accordance with board examination frameworks.',
    highlight: 'Curated by academic domain & chapter'
  },
  {
    icon: '⚔️',
    tag: 'ACTIVE RETRIEVAL',
    title: 'Knowledge-Based Battles',
    desc: 'Defeat minions and chapter bosses where your attacks, spells, and defenses are powered by the accuracy and speed of your conceptual answers.',
    highlight: 'Combat driven by factual mastery'
  },
  {
    icon: '🛡️',
    tag: 'HERO DEVELOPMENT',
    title: 'Character RPG Progression',
    desc: 'Level up your scholar, allocate attribute points across INT, AGI, and VIT, unlock legendary titles, and claim market gear to enhance your abilities.',
    highlight: 'Tangible milestones for study effort'
  },
  {
    icon: '🎯',
    tag: 'EXAM READINESS',
    title: 'Mock Examinations & Analytics',
    desc: 'Confront 140-item diagnostic mock examinations and monitor cognitive mastery across Bloom\'s taxonomy, tracking your readiness for professional licensure.',
    highlight: 'Detailed question history & analysis'
  }
]
</script>

<style scoped>
.home-about {
  padding: 6rem 1.5rem;
  position: relative;
  border-top: 1px solid;
  border-bottom: 1px solid;
  transition: background-color 0.3s ease;
}

.home-view.theme- .home-about {
  background: #0a0c12;
  border-color: #1f1a26;
}

.home-view.theme- .home-about {
  background: #f1f5f9;
  border-color: #e2e8f0;
}

.about-container {
  max-width: 1280px;
  margin: 0 auto;
}

/* Header */
.about-header {
  text-align: center;
  max-width: 800px;
  margin: 0 auto 4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.about-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 1.25rem;
  border: 1px solid;
}

.home-view.theme- .about-pill {
  background: rgba(42, 128, 136, 0.15);
  border-color: rgba(42, 128, 136, 0.4);
  color: #2a8088;
}

.home-view.theme- .about-pill {
  background: #f0fdfa;
  border-color: #99f6e4;
  color: #0f766e;
}

.pill-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: currentColor;
}

.about-heading {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.75rem;
  line-height: 1.45;
  margin-bottom: 1.25rem;
  font-weight: 800;
}

.home-view.theme- .about-heading {
  color: #f0e8d0;
}

.home-view.theme- .about-heading {
  color: #0f172a;
}

.heading-gradient {
  color: #c0392b;
}

.home-view.theme- .heading-gradient {
  color: #b91c1c;
}

.about-lead {
  font-size: 1.1rem;
  line-height: 1.7;
}

.home-view.theme- .about-lead {
  color: #a8967a;
}

.home-view.theme- .about-lead {
  color: #475569;
}

/* 4 Pillars Grid */
.pillars-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  margin-bottom: 3.5rem;
}

.pillar-card {
  position: relative;
  border-radius: 14px;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  border: 1px solid;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  overflow: hidden;
}

.home-view.theme- .pillar-card {
  background: #11141e;
  border-color: #242430;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}

.home-view.theme- .pillar-card:hover {
  transform: translateY(-6px);
  border-color: #4a3a2a;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
}

.home-view.theme- .pillar-card {
  background: #ffffff;
  border-color: #e2e8f0;
  box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
}

.home-view.theme- .pillar-card:hover {
  transform: translateY(-6px);
  border-color: #cbd5e1;
  box-shadow: 0 12px 25px rgba(15, 23, 42, 0.1);
}

.card-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.pillar-icon {
  font-size: 2rem;
}

.pillar-tag {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.home-view.theme- .pillar-tag {
  background: rgba(255, 255, 255, 0.06);
  color: #d4a017;
}

.home-view.theme- .pillar-tag {
  background: #f1f5f9;
  color: #b45309;
}

.pillar-title {
  font-size: 1.15rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  line-height: 1.35;
}

.home-view.theme- .pillar-title {
  color: #f0e8d0;
}

.home-view.theme- .pillar-title {
  color: #0f172a;
}

.pillar-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  margin-bottom: 1.25rem;
  flex-grow: 1;
}

.home-view.theme- .pillar-desc {
  color: #a8967a;
}

.home-view.theme- .pillar-desc {
  color: #64748b;
}

.pillar-footer {
  padding-top: 1rem;
  border-top: 1px solid;
}

.home-view.theme- .pillar-footer {
  border-top-color: #1a1d2a;
}

.home-view.theme- .pillar-footer {
  border-top-color: #f1f5f9;
}

.pillar-highlight {
  font-size: 0.75rem;
  font-weight: 700;
}

.home-view.theme- .pillar-highlight {
  color: #c9b99a;
}

.home-view.theme- .pillar-highlight {
  color: #334155;
}

/* Philosophy Callout Box */
.philosophy-box {
  display: flex;
  align-items: center;
  gap: 2rem;
  border-radius: 16px;
  padding: 2rem 2.5rem;
  border: 1px solid;
}

.home-view.theme- .philosophy-box {
  background: linear-gradient(135deg, rgba(20, 22, 32, 0.9) 0%, rgba(13, 15, 23, 0.95) 100%);
  border-color: #3d2a48;
}

.home-view.theme- .philosophy-box {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border-color: #cbd5e1;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
}

.philosophy-psi {
  font-family: 'Press Start 2P', monospace;
  font-size: 2.5rem;
  color: #c0392b;
  min-width: 60px;
  text-align: center;
}

.philosophy-content h4 {
  font-size: 1.15rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.home-view.theme- .philosophy-content h4 {
  color: #f0e8d0;
}

.home-view.theme- .philosophy-content h4 {
  color: #0f172a;
}

.philosophy-content p {
  font-size: 0.92rem;
  line-height: 1.6;
}

.home-view.theme- .philosophy-content p {
  color: #a8967a;
}

.home-view.theme- .philosophy-content p {
  color: #475569;
}

/* Responsive */
@media (max-width: 1100px) {
  .pillars-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .home-about {
    padding: 4rem 1rem;
  }
  .about-heading {
    font-size: 1.35rem;
  }
  .pillars-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
  .philosophy-box {
    flex-direction: column;
    text-align: center;
    padding: 1.5rem;
    gap: 1rem;
  }
}
</style>
