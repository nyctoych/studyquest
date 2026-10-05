<template>
  <section class="ranking-roster-section" aria-label="Ranks 4 and above">
    <!-- Header title for Roster -->
    <div class="roster-banner-header">
      <div class="banner-line left"></div>
      <div class="banner-title-box">
        <PixelEmoji emoji="scroll" size="xs" />
        <span>CHAMPION ROSTER (RANKS 4+)</span>
        <PixelEmoji emoji="scroll" size="xs" />
      </div>
      <div class="banner-line right"></div>
    </div>

    <!-- Table-like column headers for large screens -->
    <div class="roster-column-headers">
      <span class="col-hdr rank-hdr">RANK</span>
      <span class="col-hdr player-hdr">CANDIDATE / SCHOLAR</span>
      <span class="col-hdr level-hdr">TIER / LEVEL</span>
      <span class="col-hdr metric-hdr">{{ rankingType === 'rating' ? 'ELO RATING' : 'TOTAL XP' }}</span>
      <span class="col-hdr winrate-hdr">WIN RATE</span>
      <span class="col-hdr battles-hdr">DUELS</span>
      <span class="col-hdr action-hdr"></span>
    </div>

    <!-- List of cards -->
    <div v-if="players.length > 0" class="roster-list animate-slide-up">
      <RankingRowCard
        v-for="(player, idx) in players"
        :key="player.id"
        :player="player"
        :rank="startIndex + idx + 1"
        :ranking-type="rankingType"
        :current-user-id="currentUserId"
        @inspect="onInspect"
      />
    </div>

    <!-- Empty state for roster -->
    <div v-else class="roster-empty-card">
      <div class="empty-icon"><PixelEmoji emoji="target" size="md" /></div>
      <h4 class="empty-roster-title">NO ADDITIONAL CANDIDATES IN THIS TIER</h4>
      <p class="empty-roster-desc">
        {{ hasSearchQuery ? 'No scholars match the search filter.' : 'All registered contenders are currently featured on the Grand Podium.' }}
      </p>
    </div>
  </section>
</template>

<script setup>
import PixelEmoji from '../ui/PixelEmoji.vue'
import RankingRowCard from './RankingRowCard.vue'

defineProps({
  players: {
    type: Array,
    default: () => []
  },
  startIndex: {
    type: Number,
    default: 3
  },
  rankingType: {
    type: String,
    default: 'rating'
  },
  currentUserId: {
    type: [Number, String],
    default: null
  },
  hasSearchQuery: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['inspect'])

function onInspect(player, rank) {
  emit('inspect', player, rank)
}
</script>

<style scoped>
.ranking-roster-section {
  max-width: 1040px;
  margin: 0 auto 3rem;
  position: relative;
  z-index: 2;
}

/* Banner header */
.roster-banner-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.banner-line {
  flex: 1;
  max-width: 220px;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(181, 148, 16, 0.4), transparent);
}

.banner-title-box {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  color: #bfa888;
  letter-spacing: 0.14em;
  padding: 0.35rem 1rem;
  background: rgba(10, 11, 16, 0.65);
  border: 1px solid rgba(181, 148, 16, 0.2);
}

/* Column headers */
.roster-column-headers {
  display: grid;
  grid-template-columns: 80px 2.2fr 1.1fr 1.3fr 1.2fr 1fr 40px;
  gap: 1rem;
  padding: 0.5rem 1.25rem;
  margin-bottom: 0.35rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: #b59410;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.roster-list {
  display: flex;
  flex-direction: column;
}

/* Empty roster card */
.roster-empty-card {
  padding: 2.5rem 1.5rem;
  text-align: center;
  background: rgba(14, 15, 23, 0.6);
  border: 1px dashed rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.empty-icon {
  margin-bottom: 0.25rem;
  opacity: 0.7;
}

.empty-roster-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  color: #c5b9a8;
  letter-spacing: 0.05em;
  margin: 0;
}

.empty-roster-desc {
  font-size: 0.95rem;
  color: #7a7062;
  margin: 0;
  max-width: 480px;
}

@media (max-width: 960px) {
  .roster-column-headers {
    grid-template-columns: 60px 1.8fr 1fr 1.2fr 30px;
  }
  .winrate-hdr,
  .battles-hdr {
    display: none;
  }
}

@media (max-width: 640px) {
  .roster-column-headers {
    display: none;
  }
}
</style>
