<template>
  <header class="study-hall-hud">
    <!-- Back / Navigation Button -->
    <div class="hud-left">
      <button
        v-if="backAction"
        class="hud-back-btn btn-pixel-frame"
        @click="backAction"
        :title="backLabel"
      >
        <span class="back-arrow">‹</span>
        <span class="back-text">{{ backLabel }}</span>
      </button>
      <router-link
        v-else-if="backRoute"
        :to="backRoute"
        class="hud-back-btn btn-pixel-frame"
        :title="backLabel"
      >
        <span class="back-arrow">‹</span>
        <span class="back-text">{{ backLabel }}</span>
      </router-link>

      <!-- Breadcrumb indicator -->
      <div class="hud-phase-badge">
        <span class="phase-pillar-icon">🏛️</span>
        <span class="phase-label">{{ phaseTitle }}</span>
      </div>
    </div>

    <!-- Center Lore Tag (Subtle) -->
    <div class="hud-center-lore" v-if="loreText">
      <span class="lore-rune">✦</span>
      <span class="lore-quote">{{ loreText }}</span>
      <span class="lore-rune">✦</span>
    </div>

    <!-- Right Player Identity & Currencies -->
    <div class="hud-right">
      <!-- Player Nickname & Level -->
      <div class="player-identity-chip">
        <div class="player-char-thumb" v-if="characterSprite">
          <img :src="characterSprite" :alt="auth.nickname" class="char-thumb-img" />
        </div>
        <div class="player-text-block">
          <span class="player-name">{{ auth.nickname || auth.user?.username || 'Scholar' }}</span>
          <span class="player-level">Lv.{{ auth.level || 1 }}</span>
        </div>
      </div>

      <!-- Streak -->
      <div class="hud-stat-chip streak-chip" v-if="auth.streak > 0" title="Daily Reading Streak">
        <span class="stat-icon">🔥</span>
        <span class="stat-value">{{ auth.streak }}</span>
      </div>

      <!-- Coins -->
      <div class="hud-stat-chip coins-chip" title="Mindrealm Coins">
        <span class="stat-icon">🪙</span>
        <span class="stat-value">{{ auth.points || 0 }}</span>
      </div>

      <!-- XP -->
      <div class="hud-stat-chip xp-chip" title="Knowledge XP">
        <span class="stat-icon">⭐</span>
        <span class="stat-value">{{ auth.xp || 0 }}</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { CHARACTER_ANIMATIONS } from '../../config/characterAnimations'

const props = defineProps({
  phaseTitle: {
    type: String,
    default: 'MINDREALM LIBRARY'
  },
  loreText: {
    type: String,
    default: ''
  },
  backRoute: {
    type: String,
    default: null
  },
  backLabel: {
    type: String,
    default: 'WORLD HUB'
  },
  backAction: {
    type: Function,
    default: null
  }
})

const auth = useAuthStore()

const characterSprite = computed(() => {
  const key = auth.characterKey || auth.user?.character_key || 'mage'
  const config = CHARACTER_ANIMATIONS[key] || CHARACTER_ANIMATIONS.mage
  return config?.idle || config?.sprite || ''
})
</script>

<style scoped>
.study-hall-hud {
  position: relative;
  z-index: 40;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1.25rem;
  background: rgba(8, 7, 14, 0.78);
  border-bottom: 2px solid rgba(139, 92, 246, 0.28);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.7), inset 0 -1px 0 rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(8px);
  user-select: none;
}

.hud-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.hud-back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem;
  background: rgba(22, 18, 38, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.4);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 2px 8px rgba(0, 0, 0, 0.5);
  color: #e2e8f0;
  font-family: inherit;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-decoration: none;
  cursor: pointer;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.hud-back-btn:hover {
  background: rgba(139, 92, 246, 0.35);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateX(-2px);
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.4);
}

.back-arrow {
  font-size: 1.1rem;
  line-height: 1;
}

.hud-phase-badge {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.75rem;
  background: rgba(46, 16, 101, 0.4);
  border: 1px solid rgba(168, 85, 247, 0.3);
  border-radius: 4px;
}

.phase-label {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #c084fc;
  text-transform: uppercase;
}

.hud-center-lore {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
  font-size: 0.8rem;
  font-style: italic;
  letter-spacing: 0.04em;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.8);
}

.lore-rune {
  color: #eab308;
  font-size: 0.65rem;
  opacity: 0.8;
}

.hud-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.player-identity-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.65rem;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
}

.player-char-thumb {
  width: 24px;
  height: 24px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 4px;
  border: 1px solid rgba(139, 92, 246, 0.4);
}

.char-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  image-rendering: pixelated;
}

.player-text-block {
  display: flex;
  align-items: baseline;
  gap: 0.35rem;
}

.player-name {
  font-size: 0.75rem;
  font-weight: 700;
  color: #f1f5f9;
}

.player-level {
  font-size: 0.65rem;
  font-weight: 800;
  color: #fbbf24;
}

.hud-stat-chip {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.25rem 0.6rem;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #f8fafc;
}

.streak-chip {
  border-color: rgba(249, 115, 22, 0.35);
  color: #fdba74;
}

.coins-chip {
  border-color: rgba(234, 179, 8, 0.35);
  color: #fde047;
}

.xp-chip {
  border-color: rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
}

@media (max-width: 900px) {
  .hud-center-lore {
    display: none;
  }
}

@media (max-width: 640px) {
  .study-hall-hud {
    padding: 0.5rem 0.75rem;
  }
  .back-text {
    display: none;
  }
  .phase-label {
    display: none;
  }
}
</style>
