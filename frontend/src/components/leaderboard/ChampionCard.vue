<template>
  <div
    class="podium-card"
    :class="[
      `rank-${rank}`,
      {
        'is-champion': rank === 1,
        'is-empty': !player,
        'is-current-user': isCurrentUser
      }
    ]"
    @click="player && $emit('inspect', player, rank)"
  >
    <!-- Divine light rays for Rank #1 -->
    <div v-if="rank === 1 && player" class="champion-light-rays"></div>

    <!-- Pedestal Crown / Trophy Header -->
    <div class="podium-crest-badge">
      <div class="crest-icon-box">
        <PixelEmoji v-if="rank === 1" emoji="crown" size="sm" />
        <PixelEmoji v-else-if="rank === 2" emoji="trophy" size="sm" />
        <PixelEmoji v-else emoji="shield" size="sm" />
      </div>
      <span class="crest-rank-title">{{ rankTitle }}</span>
    </div>

    <!-- Card Interior Frame -->
    <div class="card-pedestal-body">
      <!-- Active player state -->
      <template v-if="player">
        <!-- Avatar on circular pedestal disc -->
        <div class="avatar-disc-pedestal">
          <div class="pedestal-halo"></div>
          <AvatarImg
            :character="player.character_class || player.class_type || player.character_key"
            :player="player"
            :style-name="player.avatar_style || 'adventurer'"
            :seed="player.avatar_seed || player.username"
            :options="player.avatar_options || {}"
            :size="rank === 1 ? 84 : 68"
            class="podium-avatar"
          />
          <div class="rank-number-tag">#{{ rank }}</div>
        </div>

        <!-- Name & YOU badge -->
        <div class="player-identity">
          <div class="name-row">
            <h3 class="player-name" :title="displayName">{{ displayName }}</h3>
            <span v-if="isCurrentUser" class="you-pill">YOU</span>
          </div>
          <div class="scholar-title-text">{{ scholarTitle }}</div>
        </div>

        <!-- Level & Tier Badge -->
        <div class="level-tier-row">
          <span class="level-badge">Lv. {{ player.level || 1 }}</span>
          <span class="tier-badge" :style="{ borderColor: rankTier.border, color: rankTier.color }">
            <PixelEmoji :emoji="rankTier.icon" size="xs" />
            <span>{{ rankTier.name }}</span>
          </span>
        </div>

        <!-- Primary Ranking Stat Hero Block -->
        <div class="primary-stat-box">
          <template v-if="rankingType === 'rating'">
            <div class="stat-glyph-val">
              <PixelEmoji emoji="lightning" size="xs" />
              <span class="stat-big-number">{{ formatNumber(player.battle_rating || 1200) }}</span>
              <span class="stat-unit">ELO</span>
            </div>
            <div class="stat-sub-detail">
              <span>{{ player.wins || 0 }}W - {{ player.losses || 0 }}L</span>
            </div>
          </template>
          <template v-else>
            <div class="stat-glyph-val">
              <PixelEmoji emoji="star" size="xs" />
              <span class="stat-big-number">{{ formatNumber(player.xp || 0) }}</span>
              <span class="stat-unit">XP</span>
            </div>
            <div class="stat-sub-detail">
              <span>Rating: {{ player.battle_rating || 1200 }} ELO</span>
            </div>
          </template>
        </div>

        <!-- Performance Metrics Footer -->
        <div class="card-metrics-grid">
          <div class="metric-cell">
            <span class="metric-lbl">WIN RATE</span>
            <div class="win-rate-row">
              <span class="metric-val highlight-win">{{ winRate }}%</span>
              <div class="mini-rate-track">
                <div class="mini-rate-fill" :style="{ width: winRate + '%' }"></div>
              </div>
            </div>
          </div>
          <div class="metric-cell">
            <span class="metric-lbl">TOTAL DUELS</span>
            <span class="metric-val">{{ player.total_battles || (player.wins || 0) + (player.losses || 0) }}</span>
          </div>
        </div>

        <!-- Inspect Prompt Button -->
        <div class="inspect-cue">
          <span>Click to inspect record 🔍</span>
        </div>
      </template>

      <!-- Empty / Awaiting Challenger state -->
      <template v-else>
        <div class="empty-pedestal-slot">
          <div class="empty-rune-circle">
            <span>❖</span>
          </div>
          <h4 class="empty-title">AWAITING CHALLENGER</h4>
          <p class="empty-hint">Compete in the Arena to claim this pedestal.</p>
          <router-link to="/multiplayer" class="claim-pedestal-btn btn btn-ghost btn-sm" @click.stop>
            Claim Rank
          </router-link>
        </div>
      </template>
    </div>

    <!-- Solid Gothic Carved Stone Base -->
    <div class="stone-pedestal-base">
      <div class="stone-corner l"></div>
      <div class="stone-plaque">
        <span class="roman-rank">{{ romanRank }}</span>
      </div>
      <div class="stone-corner r"></div>
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
    type: Number,
    required: true
  },
  rankingType: {
    type: String,
    default: 'rating'
  },
  currentUserId: {
    type: [Number, String],
    default: null
  }
})

defineEmits(['inspect'])

const displayName = computed(() => {
  if (!props.player) return ''
  return props.player.nickname || props.player.username || 'Scholar'
})

const isCurrentUser = computed(() => {
  return props.player && props.currentUserId && Number(props.player.id) === Number(props.currentUserId)
})

const scholarTitle = computed(() => {
  if (!props.player) return ''
  return getScholarTitle(props.player.level, props.player.battle_rating)
})

const rankTier = computed(() => {
  if (!props.player) return { name: 'Bronze', color: '#f97316', border: '#c2410c', icon: 'target' }
  return getRankTier(props.player.battle_rating)
})

const winRate = computed(() => {
  if (!props.player) return 0
  const total = props.player.total_battles || (props.player.wins || 0) + (props.player.losses || 0)
  return calculateWinRate(props.player.wins, total)
})

const rankTitle = computed(() => {
  if (props.rank === 1) return 'GRAND CHAMPION'
  if (props.rank === 2) return 'RUNNER-UP'
  return 'THIRD PLACE'
})

const romanRank = computed(() => {
  if (props.rank === 1) return 'RANK I'
  if (props.rank === 2) return 'RANK II'
  return 'RANK III'
})
</script>

<style scoped>
.podium-card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: rgba(14, 15, 22, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(12px);
  cursor: pointer;
  transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
  user-select: none;
}

.podium-card:hover:not(.is-empty) {
  transform: translateY(-6px);
}

/* Rank-specific styling */
.rank-1 {
  border-color: #e6c845;
  box-shadow: 0 0 35px rgba(230, 200, 69, 0.25), 0 20px 50px rgba(0, 0, 0, 0.9);
  z-index: 3;
}
.rank-1:hover:not(.is-empty) {
  box-shadow: 0 0 50px rgba(230, 200, 69, 0.4), 0 24px 60px rgba(0, 0, 0, 0.95);
}

.rank-2 {
  border-color: #cbd5e1;
  box-shadow: 0 0 25px rgba(203, 213, 225, 0.15), 0 16px 40px rgba(0, 0, 0, 0.8);
  z-index: 2;
}
.rank-2:hover:not(.is-empty) {
  box-shadow: 0 0 35px rgba(203, 213, 225, 0.3), 0 20px 48px rgba(0, 0, 0, 0.85);
}

.rank-3 {
  border-color: #d97706;
  box-shadow: 0 0 25px rgba(217, 119, 6, 0.15), 0 16px 40px rgba(0, 0, 0, 0.8);
  z-index: 1;
}
.rank-3:hover:not(.is-empty) {
  box-shadow: 0 0 35px rgba(217, 119, 6, 0.3), 0 20px 48px rgba(0, 0, 0, 0.85);
}

/* Current user state */
.is-current-user {
  outline: 2px solid #b59410;
  outline-offset: 3px;
}

/* Light ray effect for #1 */
.champion-light-rays {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 280px;
  height: 280px;
  background: radial-gradient(circle, rgba(230, 200, 69, 0.25) 0%, rgba(181, 148, 16, 0.08) 50%, transparent 75%);
  pointer-events: none;
  filter: blur(20px);
  z-index: 0;
}

/* Crest Badge Header */
.podium-crest-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.6rem 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(8, 9, 13, 0.9);
  position: relative;
  z-index: 1;
}

.rank-1 .podium-crest-badge {
  background: linear-gradient(180deg, rgba(80, 55, 10, 0.8) 0%, rgba(20, 16, 8, 0.9) 100%);
  border-bottom-color: rgba(230, 200, 69, 0.4);
}
.rank-2 .podium-crest-badge {
  background: linear-gradient(180deg, rgba(50, 60, 75, 0.8) 0%, rgba(14, 18, 24, 0.9) 100%);
  border-bottom-color: rgba(203, 213, 225, 0.3);
}
.rank-3 .podium-crest-badge {
  background: linear-gradient(180deg, rgba(70, 35, 10, 0.8) 0%, rgba(22, 12, 6, 0.9) 100%);
  border-bottom-color: rgba(217, 119, 6, 0.3);
}

.crest-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 6px currentColor);
}

.crest-rank-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  letter-spacing: 0.12em;
  font-weight: 700;
}
.rank-1 .crest-rank-title { color: #e6c845; }
.rank-2 .crest-rank-title { color: #cbd5e1; }
.rank-3 .crest-rank-title { color: #d97706; }

/* Body Content */
.card-pedestal-body {
  padding: 1.25rem 1.1rem 0.85rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  position: relative;
  z-index: 1;
  flex: 1;
}

/* Avatar Disc */
.avatar-disc-pedestal {
  position: relative;
  margin: 0.25rem 0 0.5rem;
}

.pedestal-halo {
  position: absolute;
  inset: -8px;
  border-radius: 50%;
  pointer-events: none;
}
.rank-1 .pedestal-halo {
  background: radial-gradient(circle, rgba(230, 200, 69, 0.3) 0%, transparent 70%);
}
.rank-2 .pedestal-halo {
  background: radial-gradient(circle, rgba(203, 213, 225, 0.2) 0%, transparent 70%);
}
.rank-3 .pedestal-halo {
  background: radial-gradient(circle, rgba(217, 119, 6, 0.2) 0%, transparent 70%);
}

.podium-avatar {
  border-radius: 50%;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7);
}

.rank-number-tag {
  position: absolute;
  bottom: -4px;
  right: -4px;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  font-weight: bold;
  border: 1px solid #000;
}
.rank-1 .rank-number-tag { background: #e6c845; color: #1a1200; box-shadow: 0 0 8px #e6c845; }
.rank-2 .rank-number-tag { background: #cbd5e1; color: #0f172a; box-shadow: 0 0 8px #cbd5e1; }
.rank-3 .rank-number-tag { background: #d97706; color: #fff; box-shadow: 0 0 8px #d97706; }

/* Identity */
.player-identity {
  text-align: center;
  width: 100%;
}

.name-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.player-name {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 800;
  color: #f7f3eb;
  letter-spacing: 0.04em;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 190px;
}
.rank-1 .player-name {
  color: #fff;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.4);
}

.you-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  background: #dc2626;
  color: #fff;
  padding: 0.15rem 0.4rem;
  border: 1px solid #ef4444;
  letter-spacing: 0.05em;
}

.scholar-title-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: #b59410;
  margin-top: 0.2rem;
  letter-spacing: 0.05em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Level and tier */
.level-tier-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.level-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e2d9cc;
  padding: 0.2rem 0.5rem;
}

.tier-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  border: 1px solid;
  padding: 0.2rem 0.45rem;
  background: rgba(0, 0, 0, 0.4);
}

/* Primary Stat Hero Box */
.primary-stat-box {
  width: 100%;
  padding: 0.65rem 0.75rem;
  background: rgba(10, 11, 16, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  text-align: center;
}

.rank-1 .primary-stat-box {
  background: linear-gradient(180deg, rgba(40, 30, 8, 0.6) 0%, rgba(15, 12, 5, 0.7) 100%);
  border-color: rgba(230, 200, 69, 0.3);
}

.stat-glyph-val {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.stat-big-number {
  font-family: var(--font-pixel, monospace);
  font-size: 1.15rem;
  font-weight: 800;
  color: #e6c845;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.35);
}

.stat-unit {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: #9c9182;
}

.stat-sub-detail {
  font-size: 0.75rem;
  color: #a89f91;
  margin-top: 0.2rem;
  font-family: var(--font-gothic, serif);
}

/* Metrics Grid */
.card-metrics-grid {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.metric-cell {
  background: rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 0.45rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.metric-lbl {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #8b8070;
  letter-spacing: 0.04em;
  margin-bottom: 0.2rem;
}

.metric-val {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: #f0e6d8;
}

.metric-val.highlight-win {
  color: #34d399;
}

.win-rate-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  width: 100%;
}

.mini-rate-track {
  width: 80%;
  height: 3px;
  background: rgba(255, 255, 255, 0.1);
  overflow: hidden;
}

.mini-rate-fill {
  height: 100%;
  background: #34d399;
}

/* Inspect Cue */
.inspect-cue {
  font-size: 0.72rem;
  color: #8b8070;
  opacity: 0.65;
  transition: opacity 0.2s;
}
.podium-card:hover .inspect-cue {
  opacity: 1;
  color: #e6c845;
}

/* Empty State */
.empty-pedestal-slot {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2.5rem 1rem;
  gap: 0.6rem;
}

.empty-rune-circle {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  font-size: 1.2rem;
}

.empty-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: #8b8070;
  margin: 0;
  letter-spacing: 0.06em;
}

.empty-hint {
  font-size: 0.82rem;
  color: #64748b;
  max-width: 160px;
  line-height: 1.3;
}

.claim-pedestal-btn {
  margin-top: 0.5rem;
}

/* Carved Stone Base */
.stone-pedestal-base {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: linear-gradient(180deg, #181922 0%, #0d0e14 100%);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.5rem;
}

.rank-1 .stone-pedestal-base {
  background: linear-gradient(180deg, #241d0f 0%, #120e06 100%);
  border-top-color: rgba(230, 200, 69, 0.3);
}

.stone-corner {
  position: absolute;
  width: 4px;
  height: 4px;
  background: #b59410;
}
.stone-corner.l { left: 4px; }
.stone-corner.r { right: 4px; }

.stone-plaque {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.15em;
  color: #bfa888;
}
.rank-1 .stone-plaque { color: #e6c845; }
.rank-2 .stone-plaque { color: #cbd5e1; }
.rank-3 .stone-plaque { color: #d97706; }
</style>
