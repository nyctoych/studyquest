<template>
  <section class="home-features" id="features">
    <div class="features-container">
      <!-- Section Header -->
      <div class="features-header">
        <div class="features-pill">
          <span class="pill-badge">COMPREHENSIVE PLATFORM</span>
        </div>
        <h2 class="features-title">
          EVERY WEAPON IN YOUR<br />
          <span class="text-crimson">ACADEMIC ARSENAL</span>
        </h2>
        <p class="features-sub">
          Explore the core modules of Mindrealm engineered to transform complex undergraduate psychology workloads into daily quests, RPG battles, and board-examination preparation.
        </p>

        <!-- Category Filter Tabs -->
        <div class="filter-tabs">
          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="filter-tab"
            :class="{ 'active': activeCategory === cat.id }"
            @click="activeCategory = cat.id"
          >
            <span>{{ cat.icon }}</span>
            <span>{{ cat.name }}</span>
            <span class="cat-count">{{ getCategoryCount(cat.id) }}</span>
          </button>
        </div>
      </div>

      <!-- Features Grid (10 Modules) -->
      <div class="features-grid">
        <div
          v-for="feature in filteredFeatures"
          :key="feature.id"
          class="feature-card"
          @click="selectedFeature = feature"
        >
          <!-- Card Header -->
          <div class="feature-card-header">
            <div class="feature-icon-wrap" :style="{ background: feature.iconBg }">
              <span class="feature-icon">{{ feature.icon }}</span>
            </div>
            <span class="feature-cat-badge">{{ feature.categoryLabel }}</span>
          </div>

          <!-- Feature Name & Description -->
          <h3 class="feature-name">{{ feature.name }}</h3>
          <p class="feature-desc">{{ feature.desc }}</p>

          <!-- Feature Meta & Highlights -->
          <div class="feature-meta">
            <span class="meta-tag">{{ feature.tag }}</span>
            <span class="meta-action">Learn More ↗</span>
          </div>
        </div>
      </div>

      <!-- Feature Detail Modal -->
      <Teleport to="body">
        <div
          v-if="selectedFeature"
          class="feature-modal-backdrop"
          @click.self="selectedFeature = null"
        >
          <div class="feature-modal-card">
            <button
              class="modal-close-btn"
              @click="selectedFeature = null"
              aria-label="Close dialog"
            >✕</button>

            <div class="modal-header">
              <div class="modal-icon-badge" :style="{ background: selectedFeature.iconBg }">
                <span>{{ selectedFeature.icon }}</span>
              </div>
              <div>
                <span class="modal-cat-tag">{{ selectedFeature.categoryLabel }}</span>
                <h3 class="modal-title">{{ selectedFeature.name }}</h3>
              </div>
            </div>

            <p class="modal-body-desc">{{ selectedFeature.detailedDesc }}</p>

            <div class="modal-highlights-box">
              <h4>Module Highlights & Key Features</h4>
              <ul>
                <li v-for="(hl, i) in selectedFeature.highlights" :key="i">
                  {{ hl }}
                </li>
              </ul>
            </div>

            <div class="modal-footer">
              <button class="btn-modal-close" @click="selectedFeature = null">
                Close Module Details
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'

const activeCategory = ref('all')
const selectedFeature = ref(null)

const categories = [
  { id: 'all', name: 'All Modules', icon: '⚡' },
  { id: 'academic', name: 'Academic & Review', icon: '📚' },
  { id: 'rpg', name: 'RPG & Adventure', icon: '⚔️' },
  { id: 'community', name: 'Progression & Community', icon: '🏆' }
]

// 10 Core Modules strictly as requested
const features = [
  {
    id: 'studyhall',
    category: 'academic',
    categoryLabel: 'ACADEMIC CORE',
    name: 'Study Hall',
    icon: '🏛️',
    iconBg: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
    desc: 'A structured academic learning environment with subjects, reviewers, chapter navigation, and learning progress.',
    detailedDesc: 'Study Hall acts as your central scholastic sanctuary. It houses syllabus-aligned subjects, chapter manuscripts, essential glossary terminology, text-to-speech audio recitations, and individual chapter completion tracking.',
    tag: 'Curriculum & Reviewers',
    highlights: [
      'Comprehensive psychology domain chapters and reading manuscripts',
      'Text-to-speech (TTS) accessibility for audio-guided study sessions',
      'Important vocabulary glossaries and conceptual flashcard tables',
      'Granular chapter completion progress saved across study sessions'
    ]
  },
  {
    id: 'adventure',
    category: 'rpg',
    categoryLabel: 'FANTASY RPG',
    name: 'Adventure Gate',
    icon: '⚔️',
    iconBg: 'linear-gradient(135deg, #c0392b, #8b1a1a)',
    desc: 'A fantasy RPG progression experience that connects knowledge challenges with story chapters, battles, minions, and bosses.',
    detailedDesc: 'Advance across an interactive world stage map. Each chapter challenges your cognitive retention through minion skirmishes and culminates in tactical battles against terrifying domain bosses.',
    tag: 'Boss Fights & Stages',
    highlights: [
      'Visual chapter maps spanning multiple thematic dimensions',
      'Turn-based tactical combat where question accuracy triggers attacks',
      'Cinematic boss encounters with unique combat mechanics and phases',
      'Loot drops, potions, and gold rewards upon trial completion'
    ]
  },
  {
    id: 'test-knowledge',
    category: 'academic',
    categoryLabel: 'ASSESSMENT',
    name: 'Test Your Knowledge',
    icon: '🎯',
    iconBg: 'linear-gradient(135deg, #059669, #047857)',
    desc: 'A structured mock-examination experience based on existing Study Hall reviewers, with question history and performance analysis where implemented.',
    detailedDesc: 'Evaluate your conceptual grasp through timed assessments drawn directly from your studied curriculum. Review detailed answer keys, rationale explanations, and question response histories.',
    tag: 'Daily 140-Item Mock Exam',
    highlights: [
      'Simulated board-examination format with 140 diagnostic items',
      'Cognitive taxonomy evaluation (Bloom’s Taxonomy analytics)',
      'Historic performance logs and retake diagnostics',
      'Detailed rationale breakdowns for every answer option'
    ]
  },
  {
    id: 'vault',
    category: 'academic',
    categoryLabel: 'PERSONAL ARCHIVE',
    name: 'Vault',
    icon: '📜',
    iconBg: 'linear-gradient(135deg, #d97706, #b45309)',
    desc: 'A personal archive for uploaded study documents and reviewers, with supported viewing and editing features.',
    detailedDesc: 'Your personal sanctum repository. Upload PDF and DOCX study manuscripts, extract structured flashcards, edit notes, and organize custom review collections securely.',
    tag: 'Document Repository',
    highlights: [
      'Upload and archive personal psychology review files',
      'Embedded document viewer with searchable text navigation',
      'Personal notes and custom study reviewer generation',
      'Private cloud storage tied directly to your scholar account'
    ]
  },
  {
    id: 'character',
    category: 'rpg',
    categoryLabel: 'ATTRIBUTES & STATS',
    name: 'Character Progression',
    icon: '🛡️',
    iconBg: 'linear-gradient(135deg, #7c3aed, #6d28d9)',
    desc: 'A character-development system that includes levels, attributes, and supported milestone allocations.',
    detailedDesc: 'Every correct answer and completed quest earns XP toward your scholar level. Distribute milestone attribute points across INT, AGI, and VIT to optimize combat effectiveness.',
    tag: 'Level & Milestone Allocations',
    highlights: [
      'Custom class selection (Archer, Dual-Wielder, Necromancer)',
      'Attribute distribution system (Intelligence, Agility, Vitality)',
      'Equipment slots for academic relics, tomes, and gear',
      'Visual avatar customization with unique portrait titles'
    ]
  },
  {
    id: 'achievement',
    category: 'community',
    categoryLabel: 'MILESTONES',
    name: 'Achievement Hall',
    icon: '🏅',
    iconBg: 'linear-gradient(135deg, #e11d48, #be123c)',
    desc: 'A place to monitor eligible achievements, claim supported rewards, and share earned accomplishments.',
    detailedDesc: 'Track milestones across study streaks, total questions answered, bosses conquered, and flawless exam attempts. Claim gold tokens, badges, and generate verifiable public share tokens.',
    tag: 'Milestone Rewards & Tokens',
    highlights: [
      'Comprehensive achievement badges across academic & combat domains',
      'Claimable token and consumable rewards upon unlocking milestones',
      'Verifiable public achievement share links to showcase progress',
      'Visual trophy display in your Sanctum profile'
    ]
  },
  {
    id: 'hall-of-fame',
    category: 'community',
    categoryLabel: 'LICENSURE HONORS',
    name: 'Hall of Fame',
    icon: '👑',
    iconBg: 'linear-gradient(135deg, #b45309, #78350f)',
    desc: 'A recognition space for verified board-examination passers, subject to the application\'s verification and publication process.',
    detailedDesc: 'An honorary memorial scroll celebrating StudyQuest scholars who have successfully passed their official professional licensure examination. Upholds complete privacy choices.',
    tag: 'Verified Passers & Privacy',
    highlights: [
      'Administrative verification protocol for official licensure passers',
      'Granular privacy control: Full Legal Name vs. 🔒 Privacy Masked',
      'Permanent honorary inscription on the Mindrealm public scroll',
      'Inspiring testimonial archive for upcoming candidates'
    ]
  },
  {
    id: 'market',
    category: 'rpg',
    categoryLabel: 'SANCTUM BAZAAR',
    name: 'Market',
    icon: '🏪',
    iconBg: 'linear-gradient(135deg, #0d9488, #0f766e)',
    desc: 'A place to obtain supported in-game items, consumables, and cosmetic effects.',
    detailedDesc: 'Exchange gold earned from academic quests and battles for helpful combat potions, resurrection draughts, cosmetic avatar flair, and study buffs.',
    tag: 'Potions & Consumables',
    highlights: [
      'Health and mana restorative potions for strenuous boss battles',
      'Study focus buffs that enhance in-game streak multipliers',
      'Exclusive avatar frames and cosmetic titles',
      '100% earnable via in-game academic achievement (no pay-to-win)'
    ]
  },
  {
    id: 'leaderboard',
    category: 'community',
    categoryLabel: 'COMPETITION',
    name: 'Leaderboards',
    icon: '📊',
    iconBg: 'linear-gradient(135deg, #4f46e5, #4338ca)',
    desc: 'A place to view the rankings and competition metrics supported by StudyQuest.',
    detailedDesc: 'Compare your academic dedication and combat prowess with peers. Leaderboards track study streaks, total XP, diagnostic exam scores, and PvP arena ratings.',
    tag: 'Global & Regional Rankings',
    highlights: [
      'Real-time ranking tables across level, streak, and combat rating',
      'Division badges from Bronze Initiate to Grand Archon',
      'Weekly ranking resets with bonus sanctum rewards',
      'Personal rank highlights and peer comparison analytics'
    ]
  },
  {
    id: 'multiplayer',
    category: 'rpg',
    categoryLabel: 'REAL-TIME PVP',
    name: 'Multiplayer Arena',
    icon: '⚔️',
    iconBg: 'linear-gradient(135deg, #9333ea, #7e22ce)',
    desc: 'A competitive learning experience using the application\'s existing multiplayer system.',
    detailedDesc: 'Enter synchronous real-time trivia battles against fellow scholars. Answer curriculum questions head-to-head with speed and precision to deal damage and rise in arena rank.',
    tag: 'Real-Time Trivia Duels',
    highlights: [
      'Live WebSockets-powered matchmaking with fellow candidates',
      'Simultaneous multiple-choice question resolution and timer pressure',
      'Combat animations reflecting real-time answer speed',
      'Rating points and competitive PvP glory'
    ]
  }
]

const filteredFeatures = computed(() => {
  if (activeCategory.value === 'all') return features
  return features.filter(f => f.category === activeCategory.value)
})

function getCategoryCount(catId) {
  if (catId === 'all') return features.length
  return features.filter(f => f.category === catId).length
}
</script>

<style scoped>
.home-features {
  padding: 6rem 1.5rem;
  position: relative;
  transition: background-color 0.3s ease;
}

.home-view.theme- .home-features {
  background: #080a0e;
}

.home-view.theme- .home-features {
  background: #f8fafc;
}

.features-container {
  max-width: 1280px;
  margin: 0 auto;
}

/* Header */
.features-header {
  text-align: center;
  max-width: 820px;
  margin: 0 auto 3.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.features-pill {
  margin-bottom: 1.25rem;
}

.pill-badge {
  display: inline-block;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  background: rgba(192, 57, 43, 0.15);
  color: #c0392b;
  border: 1px solid rgba(192, 57, 43, 0.3);
}

.home-view.theme- .pill-badge {
  background: #fee2e2;
  color: #b91c1c;
  border-color: #fca5a5;
}

.features-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.75rem;
  line-height: 1.4;
  margin-bottom: 1.25rem;
  font-weight: 800;
}

.home-view.theme- .features-title {
  color: #f0e8d0;
}

.home-view.theme- .features-title {
  color: #0f172a;
}

.text-crimson {
  color: #c0392b;
}

.home-view.theme- .text-crimson {
  color: #b91c1c;
}

.features-sub {
  font-size: 1.05rem;
  line-height: 1.7;
  margin-bottom: 2rem;
}

.home-view.theme- .features-sub {
  color: #a8967a;
}

.home-view.theme- .features-sub {
  color: #475569;
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
  justify-content: center;
}

.filter-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  border: 1px solid;
  cursor: pointer;
  font-size: 0.82rem;
  font-weight: 700;
  transition: all 0.2s ease;
}

.home-view.theme- .filter-tab {
  background: #141620;
  border-color: #2a1f30;
  color: #a8967a;
}

.home-view.theme- .filter-tab:hover {
  border-color: #4a3a2a;
  color: #f0e8d0;
}

.home-view.theme- .filter-tab.active {
  background: #200808;
  border-color: #c0392b;
  color: #ffffff;
  box-shadow: 0 0 12px rgba(192, 57, 43, 0.3);
}

.home-view.theme- .filter-tab {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #64748b;
}

.home-view.theme- .filter-tab:hover {
  border-color: #94a3b8;
  color: #0f172a;
}

.home-view.theme- .filter-tab.active {
  background: #0f172a;
  border-color: #0f172a;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
}

.cat-count {
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  border-radius: 9999px;
  background: rgba(150, 150, 150, 0.2);
}

/* Grid */
.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}

.feature-card {
  border-radius: 14px;
  padding: 1.75rem;
  border: 1px solid;
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  position: relative;
}

.home-view.theme- .feature-card {
  background: #0d0f17;
  border-color: #242430;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.home-view.theme- .feature-card:hover {
  transform: translateY(-5px);
  border-color: #c0392b;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
}

.home-view.theme- .feature-card {
  background: #ffffff;
  border-color: #e2e8f0;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
}

.home-view.theme- .feature-card:hover {
  transform: translateY(-5px);
  border-color: #cbd5e1;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
}

.feature-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.feature-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.feature-icon {
  font-size: 1.35rem;
}

.feature-cat-badge {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.home-view.theme- .feature-cat-badge {
  background: rgba(255, 255, 255, 0.05);
  color: #d4a017;
}

.home-view.theme- .feature-cat-badge {
  background: #f1f5f9;
  color: #b45309;
}

.feature-name {
  font-size: 1.2rem;
  font-weight: 800;
  margin-bottom: 0.65rem;
  line-height: 1.3;
}

.home-view.theme- .feature-name {
  color: #f0e8d0;
}

.home-view.theme- .feature-name {
  color: #0f172a;
}

.feature-desc {
  font-size: 0.88rem;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  flex-grow: 1;
}

.home-view.theme- .feature-desc {
  color: #a8967a;
}

.home-view.theme- .feature-desc {
  color: #64748b;
}

.feature-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid;
}

.home-view.theme- .feature-meta {
  border-top-color: #1a1d2a;
}

.home-view.theme- .feature-meta {
  border-top-color: #f1f5f9;
}

.meta-tag {
  font-size: 0.75rem;
  font-weight: 700;
}

.home-view.theme- .meta-tag {
  color: #c9b99a;
}

.home-view.theme- .meta-tag {
  color: #334155;
}

.meta-action {
  font-size: 0.75rem;
  font-weight: 700;
  color: #c0392b;
  transition: transform 0.15s;
}

.home-view.theme- .meta-action {
  color: #dc2626;
}

.feature-card:hover .meta-action {
  transform: translateX(3px);
}

/* Feature Modal */
.feature-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(6px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.feature-modal-card {
  max-width: 580px;
  width: 100%;
  border-radius: 16px;
  padding: 2.25rem;
  position: relative;
  border: 1px solid;
  animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.home-view.theme- .feature-modal-card,
.feature-modal-card {
  background: #0d0f17;
  border-color: #3d2a48;
  color: #d4c4a8;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
}

.home-view.theme- .feature-modal-card {
  background: #ffffff;
  border-color: #e2e8f0;
  color: #334155;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
}

@keyframes modalPop {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-close-btn {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
  padding: 0.25rem;
}

.modal-close-btn:hover {
  opacity: 1;
}

.modal-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.modal-icon-badge {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
}

.modal-cat-tag {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #c0392b;
}

.home-view.theme- .modal-cat-tag {
  color: #dc2626;
}

.modal-title {
  font-size: 1.4rem;
  font-weight: 800;
  margin-top: 0.25rem;
}

.home-view.theme- .modal-title,
.modal-title {
  color: #f0e8d0;
}

.home-view.theme- .modal-title {
  color: #0f172a;
}

.modal-body-desc {
  font-size: 0.95rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.modal-highlights-box {
  border-radius: 10px;
  padding: 1.25rem;
  margin-bottom: 1.75rem;
  border: 1px solid;
}

.home-view.theme- .modal-highlights-box,
.modal-highlights-box {
  background: #141620;
  border-color: #242430;
}

.home-view.theme- .modal-highlights-box {
  background: #f8fafc;
  border-color: #e2e8f0;
}

.modal-highlights-box h4 {
  font-size: 0.85rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  letter-spacing: 0.04em;
}

.home-view.theme- .modal-highlights-box h4,
.modal-highlights-box h4 {
  color: #d4a017;
}

.home-view.theme- .modal-highlights-box h4 {
  color: #b45309;
}

.modal-highlights-box ul {
  padding-left: 1.25rem;
}

.modal-highlights-box li {
  font-size: 0.88rem;
  line-height: 1.6;
  margin-bottom: 0.4rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
}

.btn-modal-close {
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  background: #c0392b;
  color: #ffffff;
  border: none;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-modal-close:hover {
  background: #d43f30;
}

/* Responsive */
@media (max-width: 1024px) {
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 680px) {
  .home-features {
    padding: 4rem 1rem;
  }
  .features-title {
    font-size: 1.35rem;
  }
  .features-grid {
    grid-template-columns: 1fr;
  }
  .filter-tabs {
    flex-direction: column;
    width: 100%;
  }
  .filter-tab {
    width: 100%;
    justify-content: center;
  }
}
</style>
