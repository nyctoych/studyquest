<template>
  <div v-if="player" class="dossier-overlay" @click.self="$emit('close')">
    <div class="dossier-modal glass-card animate-scale-up" role="dialog" aria-modal="true" aria-labelledby="dossier-title">
      <!-- Ornamental Header Brackets -->
      <div class="dossier-corner tl"></div>
      <div class="dossier-corner tr"></div>
      <div class="dossier-corner bl"></div>
      <div class="dossier-corner br"></div>

      <!-- Close Button -->
      <button type="button" class="close-btn" @click="$emit('close')" aria-label="Close modal">✕</button>

      <!-- Modal Title -->
      <div class="dossier-header">
        <div class="dossier-tag flex items-center justify-center gap-1.5">
          <PixelEmoji emoji="scroll" size="xs" />
          <span>MINDBOUND RECORD DOSSIER</span>
        </div>
        <h2 id="dossier-title" class="dossier-hero-name">{{ displayName }}</h2>
        <p class="dossier-title-text">{{ scholarTitle }}</p>
      </div>

      <!-- Hero Character Showcase -->
      <div class="dossier-character-stage">
        <div class="character-halo"></div>
        <AvatarImg
          :character="player.character_class || player.class_type || player.character_key"
          :player="player"
          :style-name="player.avatar_style || 'adventurer'"
          :seed="player.avatar_seed || player.username"
          :options="player.avatar_options || {}"
          :size="108"
          class="dossier-avatar"
        />
        <div class="dossier-rank-badge">
          <span class="badge-prefix">RANK</span>
          <span class="badge-number">#{{ rank }}</span>
        </div>
      </div>

      <!-- Core Stats Grid -->
      <div class="dossier-stats-grid">
        <div class="stat-card">
          <span class="scard-lbl">SCHOLAR LEVEL</span>
          <div class="scard-val-row">
            <PixelEmoji emoji="star" size="xs" />
            <span class="scard-val">Lv. {{ player.level || 1 }}</span>
          </div>
          <span class="scard-sub">{{ formatNumber(player.xp || 0) }} Total XP</span>
        </div>

        <div class="stat-card">
          <span class="scard-lbl">BATTLE RATING</span>
          <div class="scard-val-row">
            <PixelEmoji emoji="lightning" size="xs" />
            <span class="scard-val text-gold">{{ formatNumber(player.battle_rating || 1200) }}</span>
          </div>
          <span class="scard-sub" :style="{ color: rankTier.color }">{{ rankTier.name }} Tier</span>
        </div>

        <div class="stat-card">
          <span class="scard-lbl">WIN RATE</span>
          <div class="scard-val-row">
            <PixelEmoji emoji="sparkles" size="xs" />
            <span class="scard-val text-green">{{ winRate }}%</span>
          </div>
          <span class="scard-sub">{{ player.wins || 0 }} Victories</span>
        </div>

        <div class="stat-card">
          <span class="scard-lbl">TOTAL DUELS</span>
          <div class="scard-val-row">
            <PixelEmoji emoji="sword" size="xs" />
            <span class="scard-val">{{ totalBattles }}</span>
          </div>
          <span class="scard-sub">{{ player.losses || 0 }} Defeats</span>
        </div>
      </div>

      <!-- Win Rate Visual Progress Bar -->
      <div class="dossier-progress-block">
        <div class="progress-lbl-row">
          <span>Competitive Duel Efficiency</span>
          <span class="text-green">{{ player.wins || 0 }} W / {{ player.losses || 0 }} L ({{ winRate }}%)</span>
        </div>
        <div class="dossier-bar-track">
          <div class="dossier-bar-fill" :style="{ width: winRate + '%' }"></div>
        </div>
      </div>

      <!-- Lore Footer -->
      <div class="dossier-footer">
        <p class="dossier-lore-line">
          <em>"Recorded in the sacred annals of Mindrealm. Deeds etched in stone endure."</em>
        </p>
        <button type="button" class="btn btn-primary btn-sm close-action-btn" @click="$emit('close')">
          Return to Hall
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import PixelEmoji from '../ui/PixelEmoji.vue'
import AvatarImg from '../AvatarImg.vue'
import { getScholarTitle, getRankTier, calculateWinRate, formatNumber } from '../../utils/scholarTitles'

const props = defineProps({
  player: {
    type: Object,
    default: null
  },
  rank: {
    type: [Number, String],
    default: 1
  }
})

defineEmits(['close'])

const displayName = computed(() => {
  if (!props.player) return ''
  return props.player.nickname || props.player.username || 'Scholar'
})

const scholarTitle = computed(() => {
  if (!props.player) return ''
  return getScholarTitle(props.player.level, props.player.battle_rating)
})

const rankTier = computed(() => {
  if (!props.player) return { name: 'Bronze', color: '#f97316', border: '#c2410c' }
  return getRankTier(props.player.battle_rating)
})

const totalBattles = computed(() => {
  if (!props.player) return 0
  return props.player.total_battles || (props.player.wins || 0) + (props.player.losses || 0)
})

const winRate = computed(() => {
  if (!props.player) return 0
  return calculateWinRate(props.player.wins, totalBattles.value)
})
</script>

<style scoped>
.dossier-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 4, 8, 0.85);
  backdrop-filter: blur(10px);
  z-index: 100;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 1.5rem;
  padding-top: calc(var(--hud-height, 68px) + 1.75rem);
  padding-bottom: 2.5rem;
  overflow-y: auto;
}

.dossier-modal {
  position: relative;
  width: 100%;
  max-width: 520px;
  margin-top: 1.25rem;
  margin-bottom: 2rem;
  max-height: calc(100vh - var(--hud-height, 68px) - 5rem);
  background: linear-gradient(180deg, #161722 0%, #0d0e15 100%);
  border: 1px solid #b59410;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.9), 0 0 30px rgba(181, 148, 16, 0.25);
  padding: 2rem;
  color: #e2d9cc;
}

.dossier-corner {
  position: absolute;
  width: 8px;
  height: 8px;
  background: #e6c845;
}
.dossier-corner.tl { top: -2px; left: -2px; }
.dossier-corner.tr { top: -2px; right: -2px; }
.dossier-corner.bl { bottom: -2px; left: -2px; }
.dossier-corner.br { bottom: -2px; right: -2px; }

.close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #8b8070;
  font-size: 1rem;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.close-btn:hover {
  color: #fff;
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.2);
}

.dossier-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.dossier-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: #b59410;
  letter-spacing: 0.14em;
  margin-bottom: 0.4rem;
}

.dossier-hero-name {
  font-family: 'Cinzel', serif;
  font-size: 1.8rem;
  font-weight: 800;
  color: #fff;
  margin: 0;
  text-shadow: 0 0 15px rgba(230, 200, 69, 0.35);
}

.dossier-title-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: #e6c845;
  margin-top: 0.25rem;
}

/* Character showcase stage */
.dossier-character-stage {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  margin-bottom: 1.75rem;
}

.character-halo {
  position: absolute;
  width: 140px;
  height: 140px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(230, 200, 69, 0.25) 0%, transparent 70%);
  pointer-events: none;
}

.dossier-avatar {
  border-radius: 50%;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.8);
}

.dossier-rank-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #18140c;
  border: 1px solid #b59410;
  padding: 0.25rem 0.75rem;
  margin-top: -12px;
  position: relative;
  z-index: 1;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7);
}

.badge-prefix {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #b59410;
}

.badge-number {
  font-family: var(--font-pixel, monospace);
  font-size: 0.85rem;
  color: #fff;
  font-weight: bold;
}

/* Stats Grid */
.dossier-stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: rgba(8, 9, 13, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
}

.scard-lbl {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #8b8070;
  letter-spacing: 0.06em;
  margin-bottom: 0.35rem;
}

.scard-val-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.2rem;
}

.scard-val {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  color: #f7f3eb;
  font-weight: bold;
}
.text-gold { color: #e6c845; }
.text-green { color: #34d399; }

.scard-sub {
  font-size: 0.75rem;
  color: #8b8070;
  font-family: var(--font-gothic, serif);
}

/* Progress Block */
.dossier-progress-block {
  margin-bottom: 1.5rem;
  padding: 0.75rem 1rem;
  background: rgba(8, 9, 13, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.progress-lbl-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.78rem;
  color: #a89f91;
  margin-bottom: 0.4rem;
}

.dossier-bar-track {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.1);
  overflow: hidden;
}

.dossier-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #34d399);
}

/* Footer */
.dossier-footer {
  text-align: center;
}

.dossier-lore-line {
  font-size: 0.85rem;
  color: #8b8070;
  margin: 0 0 1rem;
}

.close-action-btn {
  width: 100%;
}
</style>
