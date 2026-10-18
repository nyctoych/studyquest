<template>
  <div class="trial-timer-bar" :class="{ 'is-warning': isWarning, 'is-danger': isDanger }">
    <div class="timer-display">
      <span class="timer-icon">
        <PixelEmoji emoji="clock" size="sm" />
      </span>
      <span class="timer-label">TIME REMAINING:</span>
      <span class="timer-digits">{{ formattedTime }}</span>
    </div>

    <div class="timer-progress-track">
      <div
        class="timer-progress-fill"
        :style="{ width: percentRemaining + '%' }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  remainingSeconds: {
    type: Number,
    required: true
  },
  totalSeconds: {
    type: Number,
    default: 10800
  }
})

const emit = defineEmits(['time-up'])

const currentSeconds = ref(props.remainingSeconds)
let timerId = null

watch(() => props.remainingSeconds, (newVal) => {
  currentSeconds.value = newVal
})

const isWarning = computed(() => currentSeconds.value <= 600 && currentSeconds.value > 180)
const isDanger  = computed(() => currentSeconds.value <= 180)

const percentRemaining = computed(() => {
  if (props.totalSeconds <= 0) return 0
  return Math.min(100, Math.max(0, (currentSeconds.value / props.totalSeconds) * 100))
})

const formattedTime = computed(() => {
  const s = Math.max(0, currentSeconds.value)
  const hours = Math.floor(s / 3600)
  const minutes = Math.floor((s % 3600) / 60)
  const seconds = s % 60
  return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

onMounted(() => {
  timerId = setInterval(() => {
    if (currentSeconds.value > 0) {
      currentSeconds.value--
      if (currentSeconds.value === 0) {
        emit('time-up')
      }
    }
  }, 1000)
})

onUnmounted(() => {
  if (timerId) clearInterval(timerId)
})
</script>

<style scoped>
.trial-timer-bar {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  background: rgba(13, 10, 24, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 6px;
  padding: 0.5rem 0.85rem;
  box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.8), 0 2px 8px rgba(0, 0, 0, 0.5);
  min-width: 220px;
}

.timer-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #c4b5fd;
  text-shadow: 0 0 6px rgba(139, 92, 246, 0.6);
}

.timer-label {
  color: #94a3b8;
  letter-spacing: 0.5px;
}

.timer-digits {
  font-size: 0.8rem;
  color: #f1f5f9;
  letter-spacing: 1px;
  margin-left: auto;
  font-weight: bold;
}

.timer-progress-track {
  height: 6px;
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  overflow: hidden;
}

.timer-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b5cf6, #38bdf8);
  box-shadow: 0 0 8px rgba(56, 189, 248, 0.6);
  transition: width 0.3s ease;
}

/* Warnings */
.trial-timer-bar.is-warning .timer-digits {
  color: #fbbf24;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.7);
}
.trial-timer-bar.is-warning .timer-progress-fill {
  background: linear-gradient(90deg, #f59e0b, #fbbf24);
  box-shadow: 0 0 8px rgba(251, 191, 36, 0.7);
}

/* Critical Danger */
.trial-timer-bar.is-danger {
  border-color: rgba(239, 68, 68, 0.6);
  animation: pulse-danger 1.2s infinite;
}
.trial-timer-bar.is-danger .timer-digits {
  color: #ef4444;
  text-shadow: 0 0 8px rgba(239, 68, 68, 0.8);
}
.trial-timer-bar.is-danger .timer-progress-fill {
  background: linear-gradient(90deg, #b91c1c, #ef4444);
  box-shadow: 0 0 8px rgba(239, 68, 68, 0.8);
}

@keyframes pulse-danger {
  0%, 100% { box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.8), 0 0 10px rgba(239, 68, 68, 0.3); }
  50% { box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.8), 0 0 18px rgba(239, 68, 68, 0.7); }
}
</style>
