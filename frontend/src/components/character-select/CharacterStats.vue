<template>
  <div class="character-stats-panel gothic-panel">
    <div class="stats-header">
      <span class="stats-rune">◆</span>
      <span class="stats-heading">BASE ATTRIBUTES</span>
      <span class="stats-rune">◆</span>
    </div>

    <div class="stats-grid">
      <!-- Health -->
      <div class="stat-card stat-health">
        <div class="stat-meta">
          <div class="stat-label-wrap">
            <span class="stat-icon">❤️</span>
            <span class="stat-title">HEALTH</span>
          </div>
          <span class="stat-val font-pixel">{{ character.health }}</span>
        </div>
        <div class="stat-track">
          <div class="stat-bar health-bar" :style="{ width: healthPercent + '%' }">
            <div class="bar-shine"></div>
          </div>
        </div>
        <div class="stat-tier-hint font-cinzel">{{ healthDescriptor }}</div>
      </div>

      <!-- Damage -->
      <div class="stat-card stat-damage">
        <div class="stat-meta">
          <div class="stat-label-wrap">
            <span class="stat-icon">⚔️</span>
            <span class="stat-title">DAMAGE</span>
          </div>
          <span class="stat-val font-pixel">{{ character.damage }}</span>
        </div>
        <div class="stat-track">
          <div class="stat-bar damage-bar" :style="{ width: damagePercent + '%' }">
            <div class="bar-shine"></div>
          </div>
        </div>
        <div class="stat-tier-hint font-cinzel">{{ damageDescriptor }}</div>
      </div>

      <!-- Crit Damage -->
      <div class="stat-card stat-crit">
        <div class="stat-meta">
          <div class="stat-label-wrap">
            <span class="stat-icon">⚡</span>
            <span class="stat-title">CRIT DAMAGE</span>
          </div>
          <span class="stat-val font-pixel">{{ character.crit_damage }}</span>
        </div>
        <div class="stat-track">
          <div class="stat-bar crit-bar" :style="{ width: critPercent + '%' }">
            <div class="bar-shine"></div>
          </div>
        </div>
        <div class="stat-tier-hint font-cinzel">{{ critDescriptor }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  character: {
    type: Object,
    required: true
  }
})

// Normalized percentages for maximum scale bars
// Max health in game is ~220, max damage ~20, max crit ~60
const healthPercent = computed(() => Math.min(100, Math.round((props.character.health / 220) * 100)))
const damagePercent = computed(() => Math.min(100, Math.round((props.character.damage / 20) * 100)))
const critPercent = computed(() => Math.min(100, Math.round((props.character.crit_damage / 60) * 100)))

const healthDescriptor = computed(() => {
  if (props.character.health >= 200) return 'Juggernaut Vitality'
  if (props.character.health >= 140) return 'Balanced Constitution'
  return 'Fragile Veil'
})

const damageDescriptor = computed(() => {
  if (props.character.damage >= 15) return 'Piercing Volley'
  return 'Standard Strike'
})

const critDescriptor = computed(() => {
  if (props.character.crit_damage >= 45) return 'Lethal Precision'
  if (props.character.crit_damage >= 30) return 'Arcane Surge'
  return 'Steady Impact'
})
</script>

<style scoped>
.character-stats-panel {
  background: rgba(12, 9, 20, 0.88);
  border: 1px solid rgba(168, 85, 247, 0.35);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7), inset 0 0 16px rgba(168, 85, 247, 0.1);
  border-radius: 4px;
  padding: 1rem 1.15rem;
  backdrop-filter: blur(8px);
}

.stats-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  margin-bottom: 0.9rem;
  padding-bottom: 0.45rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.stats-heading {
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  color: #e2d9cc;
}

.stats-rune {
  color: #c084fc;
  font-size: 0.65rem;
}

.stats-grid {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.stat-card {
  display: flex;
  flex-direction: column;
  gap: 0.28rem;
}

.stat-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-label-wrap {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.stat-icon {
  font-size: 0.85rem;
}

.stat-title {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #a09789;
}

.stat-val {
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.stat-health .stat-val { color: #f87171; text-shadow: 0 0 8px rgba(239, 68, 68, 0.5); }
.stat-damage .stat-val { color: #fbbf24; text-shadow: 0 0 8px rgba(245, 158, 11, 0.5); }
.stat-crit .stat-val   { color: #c084fc; text-shadow: 0 0 8px rgba(168, 85, 247, 0.5); }

/* Progress track */
.stat-track {
  width: 100%;
  height: 8px;
  background: rgba(18, 14, 26, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 2px;
  overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.8);
}

.stat-bar {
  height: 100%;
  position: relative;
  transition: width 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.bar-shine {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50%;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.35) 0%, transparent 100%);
}

.health-bar {
  background: linear-gradient(90deg, #991b1b 0%, #dc2626 70%, #ef4444 100%);
  box-shadow: 0 0 8px rgba(220, 38, 38, 0.6);
}

.damage-bar {
  background: linear-gradient(90deg, #b45309 0%, #d97706 70%, #f59e0b 100%);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.6);
}

.crit-bar {
  background: linear-gradient(90deg, #6b21a8 0%, #9333ea 70%, #c084fc 100%);
  box-shadow: 0 0 8px rgba(192, 132, 252, 0.6);
}

.stat-tier-hint {
  font-size: 0.65rem;
  color: #78716c;
  letter-spacing: 0.04em;
  font-style: italic;
  text-align: right;
}
</style>
