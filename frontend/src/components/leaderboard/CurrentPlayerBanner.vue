<template>
  <aside
    v-if="userRankData"
    class="current-player-banner"
    aria-label="Your Current Ranking Standing"
  >
    <div class="banner-inner">
      <!-- Left: Rank Badge & Avatar -->
      <div class="player-summary-group">
        <div class="user-rank-crest">
          <span class="rank-prefix">YOUR RANK</span>
          <span class="rank-val">#{{ userRankNumber }}</span>
        </div>

        <AvatarImg
          :style-name="userRankData.avatar_style || auth.avatarStyle || 'adventurer'"
          :seed="userRankData.avatar_seed || auth.avatarSeed || 'default'"
          :options="userRankData.avatar_options || auth.avatarOpts || {}"
          :size="38"
          class="banner-avatar"
        />

        <div class="banner-identity">
          <div class="banner-name-row">
            <span class="banner-name">{{ userRankData.nickname || auth.nickname || auth.user?.username }}</span>
            <span class="banner-you-pill">YOU</span>
          </div>
          <span class="banner-title">{{ scholarTitle }}</span>
        </div>
      </div>

      <!-- Center: Key Stats -->
      <div class="banner-stats-group">
        <div class="bstat-chip">
          <span class="bstat-lbl">LEVEL</span>
          <span class="bstat-val">Lv. {{ userRankData.level || auth.level }}</span>
        </div>

        <div class="bstat-chip">
          <span class="bstat-lbl">{{ rankingType === 'rating' ? 'BATTLE RATING' : 'TOTAL XP' }}</span>
          <span class="bstat-val text-gold">
            <PixelEmoji :emoji="rankingType === 'rating' ? 'lightning' : 'star'" size="xs" />
            {{ rankingType === 'rating' ? formatNumber(userRankData.battle_rating || auth.battleRating) : formatNumber(userRankData.xp || auth.xp) }}
          </span>
        </div>

        <div class="bstat-chip">
          <span class="bstat-lbl">WIN RATE</span>
          <span class="bstat-val text-green">{{ winRate }}%</span>
        </div>

        <div class="bstat-chip">
          <span class="bstat-lbl">DUELS</span>
          <span class="bstat-val">{{ totalBattles }}</span>
        </div>
      </div>

      <!-- Right: Jump to position or Arena CTA -->
      <div class="banner-action-group">
        <button
          v-if="isInVisibleList"
          type="button"
          class="jump-rank-btn btn btn-ghost btn-sm"
          @click="$emit('jump-to-user')"
        >
          <span>Locate in Hall</span>
          <span>↓</span>
        </button>
        <router-link
          v-else
          to="/multiplayer"
          class="play-duel-btn btn btn-primary btn-sm"
        >
          <PixelEmoji emoji="sword" size="xs" />
          <span>Climb Rank</span>
        </router-link>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import PixelEmoji from '../ui/PixelEmoji.vue'
import AvatarImg from '../AvatarImg.vue'
import { getScholarTitle, calculateWinRate, formatNumber } from '../../utils/scholarTitles'

const props = defineProps({
  userRankData: {
    type: Object,
    default: null
  },
  userRankNumber: {
    type: [Number, String],
    default: '-'
  },
  rankingType: {
    type: String,
    default: 'rating'
  },
  isInVisibleList: {
    type: Boolean,
    default: false
  }
})

defineEmits(['jump-to-user'])

const auth = useAuthStore()

const scholarTitle = computed(() => {
  const lvl = props.userRankData?.level || auth.level
  const rtg = props.userRankData?.battle_rating || auth.battleRating
  return getScholarTitle(lvl, rtg)
})

const totalBattles = computed(() => {
  const u = props.userRankData
  if (!u) return 0
  return u.total_battles || (u.wins || 0) + (u.losses || 0)
})

const winRate = computed(() => {
  const u = props.userRankData
  if (!u) return 0
  return calculateWinRate(u.wins, totalBattles.value)
})
</script>

<style scoped>
.current-player-banner {
  position: sticky;
  bottom: 1rem;
  left: 0;
  right: 0;
  max-width: 1040px;
  margin: 0 auto;
  z-index: 10;
  pointer-events: none;
}

.banner-inner {
  pointer-events: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.65rem 1.25rem;
  background: rgba(16, 17, 26, 0.95);
  border: 1px solid #b59410;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.85), 0 0 15px rgba(181, 148, 16, 0.2);
  backdrop-filter: blur(12px);
  border-radius: 4px;
}

/* Left Group */
.player-summary-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.user-rank-crest {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: rgba(181, 148, 16, 0.15);
  border: 1px solid rgba(181, 148, 16, 0.4);
  padding: 0.2rem 0.6rem;
  min-width: 65px;
}

.rank-prefix {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: #b59410;
  letter-spacing: 0.05em;
}

.rank-val {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  color: #fff;
  font-weight: 800;
  text-shadow: 0 0 8px rgba(230, 200, 69, 0.5);
}

.banner-avatar {
  border-radius: 50%;
  border: 1px solid #b59410;
}

.banner-identity {
  display: flex;
  flex-direction: column;
}

.banner-name-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.banner-name {
  font-family: 'Cinzel', serif;
  font-size: 1rem;
  font-weight: 700;
  color: #f7f3eb;
}

.banner-you-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  background: #dc2626;
  color: #fff;
  padding: 0.1rem 0.35rem;
}

.banner-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #b59410;
}

/* Center Stats */
.banner-stats-group {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.bstat-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.bstat-lbl {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: #8b8070;
  letter-spacing: 0.04em;
}

.bstat-val {
  font-family: var(--font-pixel, monospace);
  font-size: 0.8rem;
  color: #f0e6d8;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}
.text-gold { color: #e6c845; }
.text-green { color: #34d399; }

/* Right action */
.banner-action-group {
  display: flex;
  align-items: center;
}

.jump-rank-btn, .play-duel-btn {
  font-size: 0.68rem;
  padding: 0.4rem 0.85rem;
  white-space: nowrap;
}

@media (max-width: 860px) {
  .banner-stats-group {
    display: none;
  }
}
@media (max-width: 540px) {
  .banner-inner {
    padding: 0.5rem 0.75rem;
  }
  .banner-title {
    display: none;
  }
}
</style>
