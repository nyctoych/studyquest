<template>
  <div
    class="study-hall-page-item"
    :class="[
      `page-status-${page.status}`,
      { 'is-locked': page.isLocked, 'is-turning': isTurning }
    ]"
    @click="handlePageClick"
    role="button"
    :tabindex="page.isLocked ? -1 : 0"
    @keydown.enter="handlePageClick"
    @keydown.space.prevent="handlePageClick"
    :aria-label="`Page ${page.partNumber}: ${page.title}, status: ${page.status}`"
  >
    <!-- Parchment Edge & Binding Stitch -->
    <div class="page-binding-stitch">
      <span class="stitch-dot dot-1"></span>
      <span class="stitch-dot dot-2"></span>
      <span class="stitch-dot dot-3"></span>
    </div>

    <!-- Page Body -->
    <div class="page-parchment-body">
      <!-- Top Header Row -->
      <div class="page-top-line">
        <span class="page-leaf-tag">PAGE {{ page.pageNumberPadded }}</span>
        <div class="page-rewards-pills">
          <span class="reward-pill xp-pill">+{{ page.xpReward }} XP</span>
          <span class="reward-pill coin-pill">+{{ page.coinReward }} 🪙</span>
        </div>
      </div>

      <!-- Part / Page Title -->
      <h4 class="page-title" :title="page.title">
        {{ page.title }}
      </h4>

      <!-- Page Excerpt / Description -->
      <p class="page-desc" v-if="page.description">
        {{ page.description }}
      </p>

      <!-- Bottom Status & Action -->
      <div class="page-bottom-row">
        <!-- Status indicator -->
        <div class="page-status-badge">
          <span v-if="page.isMastered" class="badge-status-page mastered">
            ⭐ Mastered ({{ page.score }}%)
          </span>
          <span v-else-if="page.isCompleted" class="badge-status-page completed">
            ✓ Mastered
          </span>
          <span v-else-if="page.status === 'in_progress'" class="badge-status-page in-progress">
            ⚡ In Progress
          </span>
          <span v-else-if="page.isLocked" class="badge-status-page locked">
            🔒 Locked
          </span>
          <span v-else class="badge-status-page available">
            ▶ Available
          </span>
        </div>

        <!-- Open button -->
        <button
          class="btn-turn-page"
          :disabled="page.isLocked"
          @click.stop="handlePageClick"
        >
          <span class="turn-arrow">{{ page.isLocked ? '🔒' : 'TURN PAGE →' }}</span>
        </button>
      </div>

      <!-- Lock Overlay if locked -->
      <div v-if="page.isLocked" class="page-sealed-wax">
        <div class="wax-seal">
          <span>🔒</span>
        </div>
        <span class="wax-label">{{ page.lockReason || 'Sealed Volume' }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { battleAudio } from '../../services/battleAudio'

const props = defineProps({
  page: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['select'])
const isTurning = ref(false)

function handlePageClick() {
  if (props.page.isLocked) return

  battleAudio.playPageTurn()
  isTurning.value = true

  setTimeout(() => {
    emit('select', props.page)
    isTurning.value = false
  }, 220)
}
</script>

<style scoped>
.study-hall-page-item {
  position: relative;
  display: flex;
  background: #f4ece1;
  color: #1c1917;
  border-radius: 4px;
  border: 1px solid #d6c7b2;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4), inset 0 0 16px rgba(180, 150, 110, 0.25);
  cursor: pointer;
  user-select: none;
  transition: transform 0.22s ease, box-shadow 0.22s ease;
  overflow: hidden;
  outline: none;
}

.study-hall-page-item:focus-visible {
  box-shadow: 0 0 0 2px #d97706, 0 8px 20px rgba(0, 0, 0, 0.6);
}

.study-hall-page-item:hover:not(.is-locked) {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.6), inset 0 0 10px rgba(180, 150, 110, 0.4);
}

/* Page Turning Animation */
.is-turning {
  transform-origin: left center;
  animation: pageTurnAnim 0.3s ease-in forwards;
}

@keyframes pageTurnAnim {
  0% {
    transform: rotateY(0deg) scale(1);
    opacity: 1;
  }
  50% {
    transform: rotateY(-35deg) scale(1.02);
    box-shadow: -10px 10px 24px rgba(0, 0, 0, 0.8);
    opacity: 0.9;
  }
  100% {
    transform: rotateY(-60deg) scale(0.98);
    opacity: 0.2;
  }
}

/* Binding stitches on left */
.page-binding-stitch {
  width: 14px;
  background: #e6dac8;
  border-right: 1px dashed rgba(120, 80, 40, 0.4);
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  align-items: center;
  padding: 0.75rem 0;
  flex-shrink: 0;
}

.stitch-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #78350f;
  opacity: 0.6;
}

/* Page Body */
.page-parchment-body {
  flex: 1;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 140px;
}

.page-top-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.4rem;
}

.page-leaf-tag {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #78350f;
  text-transform: uppercase;
}

.page-rewards-pills {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.reward-pill {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.1rem 0.35rem;
  border-radius: 3px;
  background: rgba(0, 0, 0, 0.06);
}

.xp-pill {
  color: #5b21b6;
}

.coin-pill {
  color: #854d0e;
}

.page-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.9rem;
  font-weight: 800;
  color: #292524;
  line-height: 1.35;
  margin: 0 0 0.35rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.page-desc {
  font-size: 0.75rem;
  color: #57534e;
  line-height: 1.4;
  margin: 0 0 0.65rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.page-bottom-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid rgba(120, 80, 40, 0.18);
  padding-top: 0.5rem;
  margin-top: auto;
}

.badge-status-page {
  font-size: 0.62rem;
  font-weight: 800;
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
}

.badge-status-page.mastered {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #f59e0b;
}

.badge-status-page.completed {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #10b981;
}

.badge-status-page.in-progress {
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #3b82f6;
}

.badge-status-page.available {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #9ca3af;
}

.badge-status-page.locked {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #ef4444;
}

.btn-turn-page {
  background: #78350f;
  color: #fef3c7;
  border: none;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 0.35rem 0.65rem;
  border-radius: 3px;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.btn-turn-page:hover:not(:disabled) {
  background: #92400e;
  transform: translateX(2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.btn-turn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Sealed Wax Lock */
.is-locked {
  filter: grayscale(0.5);
  background: #e7dfd5;
}

.page-sealed-wax {
  position: absolute;
  inset: 0;
  background: rgba(40, 30, 20, 0.55);
  backdrop-filter: blur(1.5px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  z-index: 5;
}

.wax-seal {
  width: 36px;
  height: 36px;
  background: radial-gradient(circle, #991b1b 0%, #450a0a 100%);
  border: 2px solid #ef4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
  font-size: 1rem;
}

.wax-label {
  font-size: 0.65rem;
  font-weight: 800;
  color: #fecaca;
  letter-spacing: 0.08em;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
}
</style>
