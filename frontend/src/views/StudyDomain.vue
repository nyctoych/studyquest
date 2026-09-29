<template>
  <div class="study-domain">
    <div class="sd-bg"></div>
    <div class="sd-content">
      <div v-if="loading" class="sd-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNSEALING DOCTRINES…</p>
      </div>
      <template v-else-if="domainData">
        <div class="sd-header gothic-panel animate-scale-up">
          <router-link to="/study" class="btn btn-gothic-stone btn-sm">← ARCHIVES</router-link>
          <div class="sd-header-body">
            <div class="sd-icon">{{ currentDomain?.icon || '📜' }}</div>
            <div>
              <div class="sd-source-tag">SOURCE BASIS: {{ currentDomain?.source_version || 'DSM-5' }}</div>
              <h1 class="sd-name">{{ currentDomain?.name }}</h1>
              <div class="sd-source-ref" v-if="currentDomain?.source_reference">
                {{ currentDomain.source_reference }}
              </div>
              <p class="sd-desc">{{ currentDomain?.description }}</p>
            </div>
          </div>
          <div class="sd-progress-bar">
            <div class="sd-progress-fill" :style="{ width: domainProgress + '%' }"></div>
          </div>
        </div>

        <!-- Educational Disclaimer -->
        <div class="edu-banner gothic-panel animate-scale-up">
          <span class="edu-icon">⚠️</span>
          <span class="edu-text">Educational Grimoire · 10 Sequential Psychiatric Units · Board Licensure Review Preparation</span>
        </div>

        <div class="sd-parts animate-scale-up">
          <router-link
            v-for="part in partsList"
            :key="part.id"
            :to="partPath(part)"
            class="sd-part-row gothic-panel"
            :class="getPartClass(part)"
          >
            <div class="part-num" :class="'ps-' + (part.user_status || part.part_status || 'locked')">{{ part.part_number }}</div>
            <div class="part-body">
              <span class="part-title">{{ part.title }}</span>
              <span class="part-status-txt">{{ statusLabel(part.user_status || part.part_status) }}</span>
            </div>
            <div class="part-rewards">
              <span class="part-xp">+{{ part.xp_reward }} XP</span>
              <span class="part-coins">+{{ part.coin_reward }} 🪙</span>
            </div>
            <span class="part-arr">{{ (part.user_status || part.part_status) === 'locked' ? '🔒' : '→' }}</span>
          </router-link>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api'

const route = useRoute()
const domainId = computed(() => Number(route.params.domainId))
const domainData = ref(null)
const loading = ref(true)

const currentDomain = computed(() => domainData.value?.domain || domainData.value || null)
const partsList = computed(() => domainData.value?.parts || domainData.value?.domain?.parts || [])

const domainProgress = computed(() => {
  const parts = partsList.value
  const completed = parts.filter(p => ['completed', 'mastered'].includes(p.user_status || p.part_status)).length
  return parts.length > 0 ? (completed / parts.length) * 100 : 0
})

onMounted(async () => {
  try {
    const res = await api.get(`/curriculum/domains/${domainId.value}`)
    if (res.data.success) {
      domainData.value = res.data.domain || res.data
    }
  } catch (err) {
    console.warn('Error fetching domain', err)
  }
  loading.value = false
})

function partPath(part) {
  if (part.part_status === 'locked') return '#'
  return `/study/${domainId.value}/part/${part.part_number}`
}

function getPartClass(part) {
  return {
    'part-available': ['available', 'in_progress'].includes(part.part_status),
    'part-completed': ['completed', 'mastered'].includes(part.part_status),
    'part-locked': !part.part_status || part.part_status === 'locked',
  }
}

function statusLabel(s) {
  const m = { available: 'START', in_progress: 'CONTINUE', completed: '✔ CONQUERED', mastered: '⭐ MASTERED', locked: 'LOCKED' }
  return m[s] || 'LOCKED'
}
</script>

<style scoped>
.study-domain {
  min-height: 100vh;
  position: relative;
  padding-top: 68px;
  background: var(--gothic-void, #050608);
  user-select: none;
}
.sd-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(ellipse at 50% 20%, rgba(139, 26, 26, 0.14) 0%, transparent 60%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.95) 0%, #050608 100%);
  pointer-events: none;
}
.sd-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
}
.sd-loading {
  text-align: center;
  padding: 4rem 1rem;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #232533;
  border-top-color: var(--color-crimson-bright, #b91c1c);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}
.pixel-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 0.08em;
}
@keyframes spin { to { transform: rotate(360deg); } }

.sd-header {
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.sd-header-body {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin: 0.75rem 0 1rem;
}
.sd-icon { font-size: 2.2rem; flex-shrink: 0; }
.sd-source-tag {
  display: inline-block;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #ea580c;
  background: #431407;
  border: 1px solid #c2410c;
  padding: 0.15rem 0.5rem;
  margin-bottom: 0.35rem;
  font-weight: 700;
  letter-spacing: 0.05em;
}
.sd-source-ref {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-gold-ancient, #e6c845);
  margin-top: 0.2rem;
}
.edu-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 1rem;
  background: rgba(30, 24, 15, 0.95);
  border: 1px solid #78350f;
  border-left: 3px solid #f59e0b;
  font-size: 0.8rem;
  color: #fef3c7;
  margin-bottom: 1.25rem;
}
.edu-icon { font-size: 1.1rem; line-height: 1; flex-shrink: 0; }
.edu-text { font-family: var(--font-body, serif); }
.sd-name {
  font-family: var(--font-body, serif);
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
}
.sd-desc {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  margin-top: 0.35rem;
  line-height: 1.5;
}
.sd-progress-bar {
  height: 8px;
  background: #06070a;
  border: 1px solid #282a3a;
  overflow: hidden;
}
.sd-progress-fill {
  height: 100%;
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 8px rgba(185, 28, 28, 0.8);
  transition: width 0.5s ease;
}

.sd-parts {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.sd-part-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.9rem 1.2rem;
  background: #0f1017;
  border: 1px solid #282a3a;
  text-decoration: none;
  transition: all 0.15s ease;
  cursor: pointer;
}
.sd-part-row:hover:not(.part-locked) {
  transform: translateX(4px);
  border-color: var(--color-stone-600, #3a3b4c);
  background: #151620;
}
.part-locked { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
.part-available { border-color: #384260; }
.part-completed { border-color: #234335; }

.part-num {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  background: #181924;
  border: 1px solid #3a3b4c;
  color: var(--color-bone-dark, #8c8273);
}
.ps-available, .ps-in_progress {
  background: #101c2b;
  border-color: #38bdf8;
  color: #7dd3fc;
}
.ps-completed, .ps-mastered {
  background: #0f2317;
  border-color: #22c55e;
  color: #86efac;
}

.part-body { flex: 1; min-width: 0; }
.part-title {
  display: block;
  font-family: var(--font-body, serif);
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
}
.part-status-txt {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: var(--color-bone-dark, #8c8273);
}
.part-rewards {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  flex-shrink: 0;
}
.part-xp {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
}
.part-coins {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
}
.part-arr {
  color: var(--color-bone-dark, #8c8273);
  font-size: 0.85rem;
  flex-shrink: 0;
  transition: transform 0.15s;
}
.sd-part-row:hover:not(.part-locked) .part-arr {
  transform: translateX(3px);
  color: var(--color-crimson-bright, #b91c1c);
}
</style>
