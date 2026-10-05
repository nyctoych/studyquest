<template>
  <section class="champion-podium-section" aria-label="Top 3 Champions Podium">
    <!-- Section Title Banner -->
    <div class="podium-banner-header">
      <div class="banner-line left"></div>
      <div class="banner-title-box">
        <PixelEmoji emoji="crown" size="xs" />
        <span>MINDBOUND TRIUMVIRATE</span>
        <PixelEmoji emoji="crown" size="xs" />
      </div>
      <div class="banner-line right"></div>
    </div>

    <!-- 0 Players Empty State -->
    <div v-if="allEmpty" class="podium-empty-world animate-slide-up">
      <div class="empty-hall-crest">
        <PixelEmoji emoji="scroll" size="xl" />
      </div>
      <h3 class="empty-hall-heading">THE CHRONICLE IS CURRENTLY SILENT</h3>
      <p class="empty-hall-sub">
        No champions have yet inscribed their deeds into the Hall of Records.
        Enter the battle arena or study the curriculum to claim the First Pedestal!
      </p>
      <div class="empty-actions">
        <router-link to="/multiplayer" class="btn btn-primary btn-lg">
          <PixelEmoji emoji="sword" size="xs" />
          <span>⚔ Enter the Arena</span>
        </router-link>
        <router-link to="/practice" class="btn btn-ghost btn-lg">
          <PixelEmoji emoji="target" size="xs" />
          <span>Practice Psychology</span>
        </router-link>
      </div>
    </div>

    <!-- 3-Pedestal Olympus Stage Layout -->
    <div v-else class="podium-grid">
      <!-- Pedestal #2: Runner Up (Left) -->
      <div class="podium-column column-rank-2 animate-scale-up" style="animation-delay: 0.1s;">
        <ChampionCard
          :player="rank2Player"
          :rank="2"
          :ranking-type="rankingType"
          :current-user-id="currentUserId"
          @inspect="onInspect"
        />
        <div class="podium-pedestal-riser riser-silver"></div>
      </div>

      <!-- Pedestal #1: Grand Champion (Center Elevated) -->
      <div class="podium-column column-rank-1 animate-scale-up" style="animation-delay: 0.02s;">
        <ChampionCard
          :player="rank1Player"
          :rank="1"
          :ranking-type="rankingType"
          :current-user-id="currentUserId"
          @inspect="onInspect"
        />
        <div class="podium-pedestal-riser riser-gold"></div>
      </div>

      <!-- Pedestal #3: Third Place (Right) -->
      <div class="podium-column column-rank-3 animate-scale-up" style="animation-delay: 0.18s;">
        <ChampionCard
          :player="rank3Player"
          :rank="3"
          :ranking-type="rankingType"
          :current-user-id="currentUserId"
          @inspect="onInspect"
        />
        <div class="podium-pedestal-riser riser-bronze"></div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import PixelEmoji from '../ui/PixelEmoji.vue'
import ChampionCard from './ChampionCard.vue'

const props = defineProps({
  champions: {
    type: Array,
    default: () => []
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

const emit = defineEmits(['inspect'])

const rank1Player = computed(() => props.champions[0] || null)
const rank2Player = computed(() => props.champions[1] || null)
const rank3Player = computed(() => props.champions[2] || null)

const allEmpty = computed(() => {
  return !rank1Player.value && !rank2Player.value && !rank3Player.value
})

function onInspect(player, rank) {
  emit('inspect', player, rank)
}
</script>

<style scoped>
.champion-podium-section {
  margin-bottom: 3.5rem;
  position: relative;
  z-index: 2;
}

/* Banner header */
.podium-banner-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
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
  font-size: 0.72rem;
  color: #b59410;
  letter-spacing: 0.16em;
  padding: 0.35rem 1rem;
  background: rgba(10, 11, 16, 0.65);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
}

/* Grid layout for podium */
.podium-grid {
  display: grid;
  grid-template-columns: 1fr 1.15fr 1fr;
  align-items: end;
  gap: 1.5rem;
  max-width: 1040px;
  margin: 0 auto;
}

.podium-column {
  display: flex;
  flex-direction: column;
}

/* Elevation for Rank 1 */
.column-rank-1 {
  order: 2;
  transform: translateY(-24px);
}
.column-rank-2 {
  order: 1;
}
.column-rank-3 {
  order: 3;
}

/* Pedestal risers creating physical depth underneath cards */
.podium-pedestal-riser {
  width: 90%;
  margin: 0 auto;
  height: 18px;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  box-shadow: 0 12px 25px rgba(0, 0, 0, 0.9);
  position: relative;
}

.riser-gold {
  height: 28px;
  background: linear-gradient(180deg, #2b1f0a 0%, #140d04 100%);
  border: 1px solid rgba(230, 200, 69, 0.4);
  border-top: none;
}
.riser-silver {
  height: 18px;
  background: linear-gradient(180deg, #1c222c 0%, #0c0f14 100%);
  border: 1px solid rgba(203, 213, 225, 0.3);
  border-top: none;
}
.riser-bronze {
  height: 14px;
  background: linear-gradient(180deg, #28180c 0%, #110a05 100%);
  border: 1px solid rgba(217, 119, 6, 0.3);
  border-top: none;
}

/* Empty state */
.podium-empty-world {
  max-width: 680px;
  margin: 0 auto;
  padding: 3.5rem 2rem;
  text-align: center;
  background: rgba(14, 15, 22, 0.85);
  border: 1px solid rgba(181, 148, 16, 0.3);
  backdrop-filter: blur(10px);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8);
}

.empty-hall-crest {
  display: inline-flex;
  margin-bottom: 1.25rem;
  filter: drop-shadow(0 0 15px rgba(181, 148, 16, 0.4));
}

.empty-hall-heading {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  color: #f7f3eb;
  letter-spacing: 0.06em;
  margin: 0 0 0.75rem;
}

.empty-hall-sub {
  font-size: 1.05rem;
  color: #a89f91;
  max-width: 520px;
  margin: 0 auto 2rem;
  line-height: 1.5;
}

.empty-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

@media (max-width: 860px) {
  .podium-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .column-rank-1 {
    order: 1;
    transform: none;
  }
  .column-rank-2 {
    order: 2;
  }
  .column-rank-3 {
    order: 3;
  }
  .podium-pedestal-riser {
    display: none;
  }
}
</style>
