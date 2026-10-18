<template>
  <div
    class="glass-card achievement-card"
    :class="[
      `cat-${card.category}`,
      card.status === 'claimed' ? 'card-claimed' : (card.status === 'unlocked' ? 'card-unlocked' : 'card-locked'),
      { 'tiered-card': card.is_multi_milestone }
    ]"
  >
    <!-- Fixed-Height Card Header -->
    <div class="card-header-row">
      <div class="badge-avatar-wrap" :class="{ 'glow-icon': card.status !== 'locked' }">
        <PixelEmoji :emoji="getEmoji(card.icon)" size="md" />
      </div>

      <div class="card-meta">
        <div class="card-category-tag">
          <span class="category-name">{{ formatCategory(card.category) }}</span>
          <span v-if="card.is_multi_milestone" class="multi-pill">2 Milestones</span>
        </div>
        <h3 class="card-title" :title="card.title">{{ card.title }}</h3>
        <p v-if="card.subtitle" class="card-subtitle" :title="card.subtitle">{{ card.subtitle }}</p>
        <p v-else class="card-subtitle placeholder-sub">&nbsp;</p>
      </div>

      <!-- Share Button on header ONLY available after claiming -->
      <button
        v-if="card.status === 'claimed'"
        type="button"
        class="card-share-trigger"
        title="Share Claimed Achievement"
        @click="$emit('share', { card, milestone: card.milestones[0] })"
      >
        <PixelEmoji emoji="sparkles" size="xs" />
        <span class="share-text">Share</span>
      </button>
    </div>

    <!-- Milestones Section (Equal height milestone blocks) -->
    <div class="milestones-container">
      <div
        v-for="(m, idx) in card.milestones"
        :key="m.id"
        class="milestone-block"
        :class="[
          `milestone-${m.status}`,
          { 'is-tiered': card.is_multi_milestone }
        ]"
      >
        <!-- Milestone Head: Title and Status Pill -->
        <div class="milestone-head">
          <div class="milestone-title-group">
            <span v-if="card.is_multi_milestone" class="milestone-index-badge">
              M{{ idx + 1 }}
            </span>
            <h4 class="milestone-title" :title="m.title">{{ m.title }}</h4>
          </div>

          <!-- Status Badge -->
          <div class="milestone-status-badge">
            <span v-if="m.status === 'claimed'" class="status-inner claimed">
              ✓ CLAIMED
            </span>
            <span v-else-if="m.status === 'unlocked'" class="status-inner available">
              ★ READY
            </span>
            <span v-else class="status-inner locked">
              🔒 LOCKED
            </span>
          </div>
        </div>

        <!-- Milestone Description (Clamped to 2 lines with fixed height) -->
        <p class="milestone-desc" :title="m.description">{{ m.description }}</p>

        <!-- Progress Bar Section (Fixed height) -->
        <div class="milestone-progress-wrap">
          <div class="progress-info-row">
            <span class="progress-label">
              {{ m.status === 'locked' ? getLockExplanation(m) : 'Milestone Progress' }}
            </span>
            <span class="progress-fraction">
              {{ m.current_progress }} / {{ m.target_progress }}
              <span class="percent-tag">({{ m.progress_percent }}%)</span>
            </span>
          </div>
          <div class="progress-track">
            <div
              class="progress-fill"
              :class="{ 'fill-complete': m.progress_percent >= 100 }"
              :style="{ width: `${m.progress_percent}%` }"
            ></div>
          </div>
        </div>

        <!-- Milestone Footer: Rewards & Claim Button (Fixed height) -->
        <div class="milestone-footer">
          <div class="milestone-rewards-preview">
            <span class="reward-chip xp">
              ⭐ +{{ m.xp_reward }} XP
            </span>
            <span class="reward-chip coins">
              🪙 +{{ m.coins_reward }} Coins
            </span>
          </div>

          <div class="milestone-actions">
            <!-- Individual Share if tiered and claimed -->
            <button
              v-if="card.is_multi_milestone && m.status === 'claimed'"
              type="button"
              class="btn-icon-share"
              title="Share this specific milestone"
              @click="$emit('share', { card, milestone: m })"
            >
              <PixelEmoji emoji="sparkles" size="xs" />
            </button>

            <!-- Claim Reward Button -->
            <button
              v-if="m.status === 'unlocked'"
              type="button"
              class="btn-claim-reward pulse-gold"
              :disabled="claimingId === m.id"
              @click="$emit('claim', m)"
            >
              <span v-if="claimingId === m.id" class="claiming-spinner">...</span>
              <span v-else>CLAIM</span>
            </button>

            <div v-else-if="m.status === 'claimed'" class="claimed-indicator">
              <span class="claimed-check">✓</span>
              <span class="claimed-label">CLAIMED</span>
            </div>

            <div v-else class="locked-indicator">
              <span class="locked-icon">🔒</span>
              <span class="locked-label">UNMET</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import PixelEmoji from '../ui/PixelEmoji.vue'

const props = defineProps({
  card: {
    type: Object,
    required: true
  },
  claimingId: {
    type: Number,
    default: null
  }
})

defineEmits(['claim', 'share'])

function formatCategory(cat) {
  switch (cat) {
    case 'adventure': return 'Adventure Gate'
    case 'studyhall': return 'Study Hall'
    case 'progression': return 'Progression'
    case 'multiplayer': return 'Multiplayer Arena'
    default: return 'Achievement'
  }
}

function getEmoji(icon) {
  if (!icon) return 'trophy'
  const mapping = {
    scroll: 'scroll',
    crown: 'crown',
    shield: 'shield',
    star: 'star',
    sword: 'sword',
    fire: 'fire',
    book: 'book',
    crystal: 'crystal',
    trophy: 'trophy',
    target: 'target'
  }
  return mapping[icon] || 'trophy'
}

function getLockExplanation(m) {
  switch (m.condition_type) {
    case 'adventure_chapter':
      return `Defeat Chapter ${m.condition_value} Boss`
    case 'adventure_campaign':
      return 'Complete all 12 Adventure Chapters'
    case 'studyhall_domain':
      return `Master lessons in Domain ${m.condition_value}`
    case 'studyhall_all':
      return 'Complete all 12 Study Hall Subjects'
    case 'level':
      return `Reach Player Level ${m.condition_value}`
    case 'multiplayer_wins':
      return `Win ${m.condition_value} Verified Matches`
    default:
      return 'Requirement pending'
  }
}
</script>

<style scoped>
/* ── BASE CARD CONTAINER (EQUAL HEIGHT & PRESENTABLE) ────────────── */
.achievement-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.25rem;
  border-radius: 8px;
  background: linear-gradient(180deg, #18161f 0%, #0d0e13 100%);
  border: 1.5px solid rgba(212, 175, 55, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(0, 0, 0, 0.7);
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
  position: relative;
  box-sizing: border-box;
  width: 100%;
}

.achievement-card:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 215, 0, 0.6);
  box-shadow: 0 0 0 2px #050608, 0 12px 30px rgba(0, 0, 0, 0.85);
}

.card-unlocked {
  border-color: rgba(255, 215, 0, 0.55);
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(212, 175, 55, 0.2);
}

.card-claimed {
  border-color: rgba(72, 187, 120, 0.45);
}

.card-locked {
  opacity: 0.9;
}

/* ── FIXED-HEIGHT CARD HEADER (ALIGNED ACROSS ALL CARDS) ────────── */
.card-header-row {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  min-height: 100px; /* Exact uniform height for all card headers! */
  margin-bottom: 0.85rem;
  position: relative;
}

.badge-avatar-wrap {
  width: 48px;
  height: 48px;
  border-radius: 6px;
  background: #090a0d;
  border: 1.5px solid rgba(255, 255, 255, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 0 0 1px #050608;
  margin-top: 2px;
}

.glow-icon {
  border-color: #ffd700;
  background: linear-gradient(135deg, rgba(45, 36, 15, 0.9), #090a0d);
  box-shadow: 0 0 14px rgba(255, 215, 0, 0.35);
}

.card-meta {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.card-category-tag {
  height: 18px;
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.2rem;
}

.category-name {
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #e6c845;
}

.multi-pill {
  font-family: 'Cinzel', serif;
  font-size: 0.62rem;
  font-weight: 800;
  text-transform: uppercase;
  background: rgba(147, 51, 234, 0.25);
  border: 1px solid #c084fc;
  color: #e9d5ff;
  padding: 0.05rem 0.4rem;
  border-radius: 3px;
  letter-spacing: 0.05em;
}

.card-title {
  font-family: 'Cinzel', serif;
  font-size: 1.05rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 1.25;
  min-height: 44px; /* Exactly 2 lines height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
}

.card-subtitle {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 0.82rem;
  font-weight: 600;
  color: #c4b7a2;
  margin: 0.2rem 0 0 0;
  line-height: 1.25;
  min-height: 32px; /* Exactly 2 lines height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.placeholder-sub {
  visibility: hidden;
}

.card-share-trigger {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(212, 175, 55, 0.18);
  border: 1px solid #e6c845;
  color: #fce99f;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  letter-spacing: 0.04em;
  flex-shrink: 0;
}

.card-share-trigger:hover {
  background: rgba(255, 215, 0, 0.35);
  color: #ffffff;
  border-color: #ffd700;
}

/* ── MILESTONES CONTAINER ────────────────────────────────────────── */
.milestones-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  flex: 1;
}

/* ── MILESTONE BLOCK (FIXED SECTION HEIGHTS) ─────────────────────── */
.milestone-block {
  padding: 0.85rem 1rem;
  background: #101218;
  border: 1.5px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 200px; /* Uniform height for clean grid alignment! */
  gap: 0.5rem;
  box-sizing: border-box;
}

.milestone-block.is-tiered {
  border-left: 4px solid #ffd700;
  background: #12141c;
}

.milestone-block.milestone-unlocked {
  border-color: rgba(255, 215, 0, 0.55);
  background: #171610;
}

.milestone-block.milestone-claimed {
  border-color: rgba(72, 187, 120, 0.45);
  background: #0f1612;
}

/* Head */
.milestone-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  min-height: 28px;
}

.milestone-title-group {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex: 1;
  min-width: 0;
}

.milestone-index-badge {
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  font-weight: 800;
  color: #ffd700;
  background: #251c05;
  border: 1px solid #785a15;
  padding: 0.1rem 0.35rem;
  border-radius: 3px;
  flex-shrink: 0;
}

.milestone-title {
  font-family: 'Cinzel', serif;
  font-size: 0.88rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Status Pill */
.milestone-status-badge .status-inner {
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  font-weight: 800;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  letter-spacing: 0.04em;
  white-space: nowrap;
  display: inline-block;
}

.status-inner.claimed {
  background: rgba(72, 187, 120, 0.2);
  color: #68d391;
  border: 1px solid #48bb78;
}

.status-inner.available {
  background: rgba(234, 179, 8, 0.25);
  color: #ffd700;
  border: 1px solid #ffd700;
  animation: pulseGold 2s infinite ease-in-out;
}

.status-inner.locked {
  background: rgba(74, 85, 104, 0.35);
  color: #cbd5e0;
  border: 1px solid #718096;
}

/* Description */
.milestone-desc {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  font-size: 0.82rem;
  font-weight: 500;
  color: #dcd3c4;
  margin: 0;
  line-height: 1.35;
  min-height: 42px; /* Uniform 2 lines */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Progress Wrap */
.milestone-progress-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-height: 34px;
}

.progress-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.72rem;
}

.progress-label {
  color: #c9bcab;
  font-family: 'Cinzel', serif;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

.progress-fraction {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #ffd700;
}

.percent-tag {
  color: #a0aec0;
  font-size: 0.55rem;
}

.progress-track {
  width: 100%;
  height: 7px;
  background: #08090d;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #b59410 0%, #ffd700 100%);
  border-radius: 2px;
  transition: width 0.4s ease;
}

.progress-fill.fill-complete {
  background: linear-gradient(90deg, #2e8540 0%, #48bb78 100%);
}

/* Footer (Rewards & Action) */
.milestone-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  min-height: 38px;
  padding-top: 0.35rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.milestone-rewards-preview {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.reward-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

.reward-chip.xp {
  background: rgba(147, 51, 234, 0.22);
  color: #e9d5ff;
  border: 1px solid #c084fc;
}

.reward-chip.coins {
  background: rgba(234, 179, 8, 0.22);
  color: #fef08a;
  border: 1px solid #facc15;
}

.milestone-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-icon-share {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
  padding: 0.35rem;
  border-radius: 4px;
  cursor: pointer;
}

.btn-claim-reward {
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  color: #120e03;
  background: linear-gradient(180deg, #ffd700 0%, #c49000 100%);
  border: 1.5px solid #fff275;
  padding: 0.45rem 0.95rem;
  border-radius: 4px;
  cursor: pointer;
  letter-spacing: 0.05em;
  box-shadow: 0 0 12px rgba(255, 215, 0, 0.5);
  transition: all 0.2s;
}

.btn-claim-reward:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 0 18px rgba(255, 215, 0, 0.75);
}

.claimed-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 800;
  color: #48bb78;
}

.locked-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  font-weight: 700;
  color: #a0aec0;
}

@keyframes pulseGold {
  0%, 100% { box-shadow: 0 0 8px rgba(255, 215, 0, 0.4); }
  50% { box-shadow: 0 0 18px rgba(255, 215, 0, 0.7); }
}

/* ══════════════════════════════════════════════════════════════════
   MORNING / LIGHT MODE THEME OVERRIDES (CRISP & 100% READABLE)
   ══════════════════════════════════════════════════════════════════ */
:global([data-theme="morning"] .achievement-card) {
  background: #ffffff !important;
  border: 2px solid #c9aa6d !important;
  box-shadow: 0 4px 18px rgba(90, 60, 20, 0.12) !important;
}

:global([data-theme="morning"] .card-title) {
  color: #120e07 !important;
  text-shadow: none !important;
}

:global([data-theme="morning"] .card-subtitle) {
  color: #3d3020 !important;
  font-weight: 600 !important;
}

:global([data-theme="morning"] .category-name) {
  color: #7c5208 !important;
}

:global([data-theme="morning"] .badge-avatar-wrap) {
  background: #f7eed8 !important;
  border-color: #c9aa6d !important;
}

:global([data-theme="morning"] .card-share-trigger) {
  background: #fdf5e2 !important;
  border: 1.5px solid #b88e1a !important;
  color: #4a3404 !important;
}

/* Milestone block morning overrides */
:global([data-theme="morning"] .milestone-block) {
  background: #f6efe2 !important;
  border: 1.5px solid #d4c4ab !important;
}

:global([data-theme="morning"] .milestone-block.milestone-claimed) {
  background: #eef7ee !important;
  border: 1.5px solid #6cb273 !important;
}

:global([data-theme="morning"] .milestone-block.milestone-unlocked) {
  background: #fef7de !important;
  border: 1.5px solid #d8b02c !important;
}

:global([data-theme="morning"] .milestone-block.is-tiered) {
  border-left: 4px solid #b88e1a !important;
}

:global([data-theme="morning"] .milestone-title) {
  color: #120e07 !important;
  font-weight: 800 !important;
}

:global([data-theme="morning"] .milestone-desc) {
  color: #2b2114 !important;
  font-weight: 600 !important;
}

:global([data-theme="morning"] .progress-label) {
  color: #453522 !important;
  font-weight: 700 !important;
}

:global([data-theme="morning"] .progress-fraction) {
  color: #7a5408 !important;
  font-weight: 800 !important;
}

:global([data-theme="morning"] .percent-tag) {
  color: #5a4730 !important;
}

:global([data-theme="morning"] .progress-track) {
  background: #ded0bc !important;
  border-color: #c4b197 !important;
}

:global([data-theme="morning"] .status-inner.claimed) {
  background: #c3e6cb !important;
  color: #0f441c !important;
  border: 1.5px solid #3c8f4e !important;
}

:global([data-theme="morning"] .status-inner.available) {
  background: #ffeeba !important;
  color: #6d4a00 !important;
  border: 1.5px solid #d39e00 !important;
}

:global([data-theme="morning"] .status-inner.locked) {
  background: #e2e4e7 !important;
  color: #2b2f33 !important;
  border: 1.5px solid #9aa0a6 !important;
}

:global([data-theme="morning"] .reward-chip.xp) {
  background: #f0e2fc !important;
  color: #3b0e68 !important;
  border: 1.5px solid #9959e0 !important;
}

:global([data-theme="morning"] .reward-chip.coins) {
  background: #fef0c7 !important;
  color: #593c00 !important;
  border: 1.5px solid #d4a528 !important;
}

:global([data-theme="morning"] .claimed-indicator) {
  color: #155724 !important;
  font-weight: 800 !important;
}

:global([data-theme="morning"] .locked-indicator) {
  color: #495057 !important;
  font-weight: 700 !important;
}

:global([data-theme="morning"] .btn-claim-reward) {
  background: linear-gradient(180deg, #ffc700 0%, #b8860b 100%) !important;
  border: 1.5px solid #8c6304 !important;
  color: #120e03 !important;
}

:global([data-theme="morning"] .milestone-footer) {
  border-top-color: #d8c8af !important;
}
</style>
