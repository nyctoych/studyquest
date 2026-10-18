<template>
  <div
    class="ranking-row-card"
    :class="{
      'is-current-user': isCurrentUser,
      'is-top-ten': rank <= 10
    }"
    @click="$emit('inspect', player, rank)"
  >
    <!-- Left rank insignia badge -->
    <div class="row-rank-cell">
      <div class="rank-insignia">
        <span class="rank-hash">#</span>
        <span class="rank-digits">{{ formatRank(rank) }}</span>
      </div>
    </div>

    <!-- Character Avatar & Name -->
    <div class="row-player-cell">
      <div class="avatar-frame">
        <AvatarImg
          :character="player.character_class || player.class_type || player.character_key"
          :player="player"
          :style-name="player.avatar_style || 'adventurer'"
          :seed="player.avatar_seed || player.username"
          :options="player.avatar_options || {}"
          :size="42"
          class="row-avatar"
        />
      </div>

      <div class="player-text-block">
        <div class="name-line">
          <strong class="player-display-name">{{ displayName }}</strong>
          <span v-if="isCurrentUser" class="you-badge-compact">YOU</span>
        </div>
        <div class="scholar-title-line">
          <span class="title-string">{{ scholarTitle }}</span>
        </div>
      </div>
    </div>

    <!-- Scholar Level -->
    <div class="row-level-cell">
      <span class="level-chip">Lv. {{ player.level || 1 }}</span>
      <span class="tier-pill" :style="{ color: rankTier.color, borderColor: rankTier.border }">
        {{ rankTier.name }}
      </span>
    </div>

    <!-- Primary Metric (Rating or XP) -->
    <div class="row-metric-cell">
      <template v-if="rankingType === 'rating'">
        <div class="metric-main-val">
          <PixelEmoji emoji="lightning" size="xs" />
          <span class="metric-num">{{ formatNumber(player.battle_rating || 1200) }}</span>
        </div>
        <div class="metric-sub-val">
          <span>{{ player.wins || 0 }}W - {{ player.losses || 0 }}L</span>
        </div>
      </template>
      <template v-else>
        <div class="metric-main-val">
          <PixelEmoji emoji="star" size="xs" />
          <span class="metric-num">{{ formatNumber(player.xp || 0) }}</span>
          <span class="xp-tag">XP</span>
        </div>
        <div class="metric-sub-val">
          <span>Rating: {{ player.battle_rating || 1200 }}</span>
        </div>
      </template>
    </div>

    <!-- Win Rate Meter -->
    <div class="row-winrate-cell">
      <div class="winrate-num-wrap">
        <span class="winrate-percent">{{ winRate }}%</span>
        <span class="winrate-lbl">WIN RATE</span>
      </div>
      <div class="winrate-bar-track">
        <div class="winrate-bar-fill" :style="{ width: winRate + '%' }"></div>
      </div>
    </div>

    <!-- Total Battles -->
    <div class="row-battles-cell">
      <span class="battles-icon"><PixelEmoji emoji="sword" size="xs" /></span>
      <span class="battles-count">{{ totalBattles }}</span>
      <span class="battles-lbl">Duels</span>
    </div>

    <!-- Inspect Action Chevron -->
    <div class="row-action-cell">
      <span class="inspect-btn" title="Inspect Champion Dossier">›</span>
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
    required: true
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
  return props.player.nickname || props.player.username || 'Scholar'
})

const isCurrentUser = computed(() => {
  return props.currentUserId && Number(props.player.id) === Number(props.currentUserId)
})

const scholarTitle = computed(() => {
  return getScholarTitle(props.player.level, props.player.battle_rating)
})

const rankTier = computed(() => {
  return getRankTier(props.player.battle_rating)
})

const totalBattles = computed(() => {
  return props.player.total_battles || (props.player.wins || 0) + (props.player.losses || 0)
})

const winRate = computed(() => {
  return calculateWinRate(props.player.wins, totalBattles.value)
})

function formatRank(r) {
  return r < 10 ? `0${r}` : `${r}`
}
</script>

<style scoped>
.ranking-row-card {
  display: grid;
  grid-template-columns: 80px 2.2fr 1.1fr 1.3fr 1.2fr 1fr 40px;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1.25rem;
  background: rgba(14, 15, 23, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-left: 3px solid rgba(181, 148, 16, 0.25);
  backdrop-filter: blur(8px);
  margin-bottom: 0.5rem;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  cursor: pointer;
}

.ranking-row-card:hover {
  background: rgba(28, 25, 36, 0.85);
  border-color: rgba(230, 200, 69, 0.3);
  border-left-color: #e6c845;
  transform: translateX(4px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
}

/* Current user card highlight */
.ranking-row-card.is-current-user {
  background: linear-gradient(90deg, rgba(80, 20, 20, 0.5) 0%, rgba(30, 20, 35, 0.75) 100%) !important;
  border-color: rgba(230, 200, 69, 0.5);
  border-left: 4px solid #e6c845 !important;
  box-shadow: 0 0 20px rgba(230, 200, 69, 0.15);
}

.ranking-row-card.is-top-ten {
  border-left-color: rgba(230, 200, 69, 0.5);
}

/* Rank column */
.row-rank-cell {
  display: flex;
  align-items: center;
}

.rank-insignia {
  font-family: var(--font-pixel, monospace);
  font-size: 0.85rem;
  color: #c5b9a8;
  letter-spacing: 0.05em;
  display: flex;
  align-items: baseline;
  gap: 1px;
}

.rank-hash {
  color: #7a7062;
  font-size: 0.7rem;
}

.is-top-ten .rank-digits {
  color: #e6c845;
  font-weight: bold;
}

/* Player Column */
.row-player-cell {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
}

.avatar-frame {
  position: relative;
  flex-shrink: 0;
}

.row-avatar {
  border-radius: 50%;
  border: 1px solid rgba(181, 148, 16, 0.3);
}



.player-text-block {
  min-width: 0;
}

.name-line {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.player-display-name {
  font-family: 'Cinzel', serif;
  font-size: 1rem;
  color: #f7f3eb;
  letter-spacing: 0.03em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.you-badge-compact {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  background: #dc2626;
  color: #fff;
  padding: 0.1rem 0.35rem;
  letter-spacing: 0.04em;
}

.scholar-title-line {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #b59410;
  letter-spacing: 0.04em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Level Column */
.row-level-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.level-chip {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: #f7f3eb;
}

.tier-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.85;
}

/* Metric Column */
.row-metric-cell {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.metric-main-val {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-family: var(--font-pixel, monospace);
}

.metric-num {
  font-size: 0.88rem;
  color: #e6c845;
  font-weight: bold;
}

.xp-tag {
  font-size: 0.6rem;
  color: #9c9182;
}

.metric-sub-val {
  font-size: 0.72rem;
  color: #8b8070;
  font-family: var(--font-gothic, serif);
}

/* Win Rate Column */
.row-winrate-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.winrate-num-wrap {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}

.winrate-percent {
  font-family: var(--font-pixel, monospace);
  font-size: 0.78rem;
  color: #34d399;
}

.winrate-lbl {
  font-size: 0.6rem;
  color: #7a7062;
  font-family: var(--font-pixel, monospace);
}

.winrate-bar-track {
  width: 100%;
  max-width: 90px;
  height: 3px;
  background: rgba(255, 255, 255, 0.08);
  overflow: hidden;
}

.winrate-bar-fill {
  height: 100%;
  background: #34d399;
}

/* Battles Column */
.row-battles-cell {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: #c5b9a8;
}

.battles-lbl {
  font-size: 0.65rem;
  color: #7a7062;
}

/* Action cell */
.row-action-cell {
  text-align: right;
}

.inspect-btn {
  font-size: 1.4rem;
  color: #7a7062;
  line-height: 1;
  transition: all 0.2s;
}

.ranking-row-card:hover .inspect-btn {
  color: #e6c845;
  transform: translateX(3px);
}

@media (max-width: 960px) {
  .ranking-row-card {
    grid-template-columns: 60px 1.8fr 1fr 1.2fr 30px;
  }
  .row-winrate-cell,
  .row-battles-cell {
    display: none;
  }
}

@media (max-width: 640px) {
  .ranking-row-card {
    grid-template-columns: 45px 1fr 1fr 30px;
    padding: 0.65rem 0.75rem;
    gap: 0.5rem;
  }
  .row-level-cell {
    display: none;
  }
  .player-display-name {
    font-size: 0.88rem;
  }
  .scholar-title-line {
    display: none;
  }
  .metric-num {
    font-size: 0.78rem;
  }
}
</style>
