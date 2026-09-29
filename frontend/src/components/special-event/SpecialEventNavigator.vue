<template>
  <div class="trial-navigator">
    <div class="nav-header">
      <div class="nav-title flex items-center gap-2">
        <PixelEmoji emoji="scroll" size="sm" />
        <span>QUESTION NAVIGATOR</span>
      </div>
      <div class="nav-legend">
        <span class="legend-item"><span class="legend-box answered"></span> Answered</span>
        <span class="legend-item"><span class="legend-box marked"></span> Marked</span>
        <span class="legend-item"><span class="legend-box unanswered"></span> Pending</span>
      </div>
    </div>

    <!-- Quick filter chips -->
    <div class="nav-filter-tabs">
      <button
        class="filter-chip"
        :class="{ active: activeFilter === 'all' }"
        @click="activeFilter = 'all'"
      >
        All ({{ totalCount }})
      </button>
      <button
        class="filter-chip"
        :class="{ active: activeFilter === 'answered' }"
        @click="activeFilter = 'answered'"
      >
        Answered ({{ answeredCount }})
      </button>
      <button
        class="filter-chip"
        :class="{ active: activeFilter === 'unanswered' }"
        @click="activeFilter = 'unanswered'"
      >
        Pending ({{ unansweredCount }})
      </button>
      <button
        class="filter-chip"
        :class="{ active: activeFilter === 'marked' }"
        @click="activeFilter = 'marked'"
      >
        Marked ({{ markedCount }})
      </button>
    </div>

    <!-- 140 Question Grid -->
    <div class="nav-grid custom-scrollbar">
      <button
        v-for="(q, idx) in questions"
        :key="q.id"
        v-show="isItemVisible(q.id)"
        class="nav-grid-btn"
        :class="{
          'is-current': currentIndex === idx,
          'is-answered': isAnswered(q.id),
          'is-marked': isMarked(q.id)
        }"
        @click="$emit('select', idx)"
        :title="`Question ${idx + 1}`"
      >
        <span class="num">{{ idx + 1 }}</span>
        <span v-if="isMarked(q.id)" class="mark-flag">★</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  questions: {
    type: Array,
    required: true
  },
  currentIndex: {
    type: Number,
    default: 0
  },
  answers: {
    type: Object,
    default: () => ({})
  }
})

defineEmits(['select'])

const activeFilter = ref('all')

const totalCount = computed(() => props.questions.length)

const answeredCount = computed(() => {
  return props.questions.filter(q => isAnswered(q.id)).length
})

const markedCount = computed(() => {
  return props.questions.filter(q => isMarked(q.id)).length
})

const unansweredCount = computed(() => {
  return totalCount.value - answeredCount.value
})

function isAnswered(questionId) {
  const ans = props.answers[questionId]
  return !!(ans && ans.selected_choice)
}

function isMarked(questionId) {
  const ans = props.answers[questionId]
  return !!(ans && ans.is_marked)
}

function isItemVisible(questionId) {
  if (activeFilter.value === 'all') return true
  if (activeFilter.value === 'answered') return isAnswered(questionId)
  if (activeFilter.value === 'unanswered') return !isAnswered(questionId)
  if (activeFilter.value === 'marked') return isMarked(questionId)
  return true
}
</script>

<style scoped>
.trial-navigator {
  background: rgba(15, 12, 28, 0.95);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 8px;
  padding: 0.85rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.nav-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.2);
  padding-bottom: 0.5rem;
}

.nav-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e2e8f0;
  letter-spacing: 0.5px;
}

.nav-legend {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.65rem;
  color: #94a3b8;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.legend-box {
  width: 9px;
  height: 9px;
  border-radius: 2px;
}
.legend-box.answered {
  background: #3b82f6;
}
.legend-box.marked {
  background: #f59e0b;
}
.legend-box.unanswered {
  background: rgba(51, 65, 85, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.nav-filter-tabs {
  display: flex;
  gap: 0.35rem;
  overflow-x: auto;
  padding-bottom: 2px;
}

.filter-chip {
  background: rgba(30, 27, 50, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.25);
  color: #94a3b8;
  font-size: 0.62rem;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.filter-chip:hover {
  background: rgba(50, 44, 82, 0.9);
  color: #f1f5f9;
}

.filter-chip.active {
  background: #8b5cf6;
  color: #fff;
  border-color: #a78bfa;
  box-shadow: 0 0 8px rgba(139, 92, 246, 0.5);
}

.nav-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
  gap: 0.4rem;
  max-height: 280px;
  overflow-y: auto;
  padding-right: 0.35rem;
}

.nav-grid-btn {
  position: relative;
  height: 34px;
  background: rgba(30, 27, 50, 0.7);
  border: 1px solid rgba(71, 85, 105, 0.4);
  border-radius: 4px;
  color: #cbd5e1;
  font-size: 0.65rem;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.nav-grid-btn:hover {
  transform: translateY(-1px);
  border-color: #a78bfa;
  background: rgba(55, 48, 90, 0.85);
}

.nav-grid-btn.is-answered {
  background: rgba(37, 99, 235, 0.35);
  border-color: #3b82f6;
  color: #93c5fd;
}

.nav-grid-btn.is-marked {
  border-color: #f59e0b;
  box-shadow: inset 0 0 6px rgba(245, 158, 11, 0.3);
}

.nav-grid-btn.is-marked .mark-flag {
  position: absolute;
  top: 1px;
  right: 2px;
  font-size: 0.55rem;
  color: #fbbf24;
}

.nav-grid-btn.is-current {
  border-color: #f43f5e;
  background: rgba(244, 63, 94, 0.25);
  color: #fff;
  box-shadow: 0 0 8px rgba(244, 63, 94, 0.6);
  transform: scale(1.05);
  z-index: 2;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.5);
  border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(139, 92, 246, 0.4);
  border-radius: 3px;
}
</style>
