<template>
  <div class="study-path">
    <div class="sp-bg"></div>
    <div class="sp-content">
      <!-- Header -->
      <div class="sp-header gothic-panel animate-scale-up">
        <router-link to="/world" class="btn btn-gothic-stone btn-sm">← WORLD HUB</router-link>
        <div>
          <div class="sp-source-tag">SOURCE BASIS: DSM-5</div>
          <h1 class="sp-title">📚 PSYCHIATRIC STUDY HALL</h1>
          <p class="sp-sub">Review of Mental Disorders &amp; Psychopathology based on DSM-5 Criteria</p>
        </div>
      </div>

      <!-- Educational Disclaimer -->
      <div class="edu-banner gothic-panel animate-scale-up">
        <span class="edu-icon">⚠️</span>
        <span class="edu-text">Educational Reviewer for Psychology Undergraduate Students · Not for clinical diagnosis or self-assessment.</span>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="sp-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNROLLING PARCHMENTS…</p>
      </div>

      <!-- Domain grid -->
      <div v-else class="domain-grid animate-scale-up">
        <router-link
          v-for="domain in domains"
          :key="domain.id"
          :to="'/study/' + domain.id"
          class="domain-card gothic-panel"
        >
          <div class="dc-icon">{{ domain.icon || '📜' }}</div>
          <div class="dc-body">
            <div class="dc-tag-row">
              <span class="dc-source-pill">{{ domain.source_version || 'DSM-5' }}</span>
              <h3 class="dc-name">{{ domain.name }}</h3>
            </div>
            <p class="dc-desc">{{ domain.description }}</p>
            <div class="dc-progress">
              <div class="dc-bar">
                <div class="dc-bar-fill" :style="{ width: domainProgress(domain) + '%' }"></div>
              </div>
              <span class="dc-progress-text">{{ domain.completed_parts || 0 }}/{{ domain.total_parts || 10 }} DOCTRINES</span>
            </div>
          </div>
          <div class="dc-arrow">→</div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const domains = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await api.get('/curriculum/overview')
    if (res.data.success) domains.value = res.data.domains || []
  } catch (err) {
    console.warn('Error fetching curriculum', err)
  }
  loading.value = false
})

function domainProgress(d) {
  const comp = d.completed_parts || 0
  const total = d.total_parts || 10
  return total > 0 ? Math.min(100, (comp / total) * 100) : 0
}
</script>

<style scoped>
.study-path {
  min-height: 100vh;
  position: relative;
  padding-top: 68px;
  background: var(--gothic-void, #050608);
  user-select: none;
}
.sp-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(ellipse at 30% 20%, rgba(139, 26, 26, 0.12) 0%, transparent 55%),
    radial-gradient(ellipse at 70% 80%, rgba(58, 20, 75, 0.18) 0%, transparent 50%),
    #050608;
  pointer-events: none;
}
.sp-content {
  position: relative;
  z-index: 1;
  max-width: 1000px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.sp-header {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.25rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.9);
}
.sp-source-tag {
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
}
.edu-icon { font-size: 1.1rem; line-height: 1; flex-shrink: 0; }
.edu-text { font-family: var(--font-body, serif); }
.dc-tag-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}
.dc-source-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  background: #7c2d12;
  color: #fed7aa;
  padding: 0.1rem 0.35rem;
  border: 1px solid #ea580c;
  border-radius: 2px;
  font-weight: 700;
}
.sp-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.15rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0 0 0.25rem 0;
}
.sp-sub {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  margin: 0;
}
.sp-loading {
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

.domain-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}
.domain-card {
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: #0f1017;
  border: 1px solid #2a2d3d;
  text-decoration: none;
  transition: all 0.2s ease;
  cursor: pointer;
}
.domain-card:hover {
  transform: translateY(-3px);
  border-color: var(--color-crimson-bright, #b91c1c);
  background: #151622;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.8), 0 0 12px rgba(185, 28, 28, 0.35);
}
.dc-icon {
  font-size: 2.2rem;
  flex-shrink: 0;
}
.dc-body {
  flex: 1;
  min-width: 0;
}
.dc-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0 0 0.25rem 0;
}
.dc-desc {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: var(--color-bone-dark, #8c8273);
  margin: 0 0 0.6rem 0;
  line-height: 1.4;
}
.dc-progress {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.dc-bar {
  height: 6px;
  background: #06070a;
  border: 1px solid #282a3a;
  overflow: hidden;
}
.dc-bar-fill {
  height: 100%;
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 6px rgba(185, 28, 28, 0.8);
  transition: width 0.4s ease;
}
.dc-progress-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: var(--color-gold-ancient, #e6c845);
}
.dc-arrow {
  font-size: 1.1rem;
  color: var(--color-bone-dark, #8c8273);
  transition: transform 0.15s ease, color 0.15s ease;
}
.domain-card:hover .dc-arrow {
  transform: translateX(3px);
  color: var(--color-crimson-bright, #b91c1c);
}

@media (max-width: 600px) {
  .sp-header { flex-direction: column; align-items: flex-start; }
}
</style>
