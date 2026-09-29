<template>
  <div class="ach-hall">
    <div class="ach-bg"></div>
    <div class="ach-content">
      <div class="ach-header animate-slide-up">
        <router-link to="/world" class="btn btn-ghost btn-sm">← World</router-link>
        <div>
          <h1 class="ach-title">🎖️ Achievement Hall</h1>
          <p class="ach-sub">{{ unlocked }}/{{ total }} Achievements Unlocked</p>
        </div>
      </div>

      <div v-if="loading" class="ach-loading"><div class="loading-spinner"></div></div>

      <div v-else class="ach-grid animate-slide-up">
        <div
          v-for="ach in achievements"
          :key="ach.id"
          class="ach-card"
          :class="{ 'ach-locked': !ach.earned_at, ['rarity-border-' + ach.rarity]: ach.earned_at }"
        >
          <div class="ach-icon" :class="{ locked: !ach.earned_at }">
            {{ ach.earned_at ? ach.icon : '🔒' }}
          </div>
          <div class="ach-body">
            <span class="ach-name">{{ ach.name }}</span>
            <span class="ach-desc">{{ ach.description }}</span>
            <span v-if="ach.earned_at" class="ach-earned">{{ formatDate(ach.earned_at) }}</span>
            <div v-if="ach.rarity" class="ach-rarity" :class="'rarity-' + ach.rarity">{{ ach.rarity }}</div>
          </div>
          <div v-if="ach.earned_at" class="ach-badge">✓</div>
        </div>
        <div v-if="achievements.length === 0" class="ach-empty">
          <p>Start playing to unlock achievements!</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'

const achievements = ref([])
const loading = ref(true)
const unlocked = computed(() => achievements.value.filter(a => !!a.earned_at).length)
const total = computed(() => achievements.value.length)

onMounted(async () => {
  try {
    const res = await api.get('/achievements')
    if (res.data.success) achievements.value = res.data.achievements || []
  } catch { }
  loading.value = false
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<style scoped>
.ach-hall { min-height: 100vh; position: relative; padding-top: 56px; }
.ach-bg { position: fixed; inset: 0; z-index: 0; background: radial-gradient(ellipse at 50% 0%, rgba(139,26,26,0.12) 0%, transparent 60%), #050608; }
.ach-content { position: relative; z-index: 1; max-width: 900px; margin: 0 auto; padding: 1.5rem 1.25rem; display: flex; flex-direction: column; gap: 1.75rem; }
.ach-header { display: flex; align-items: center; gap: 1.25rem; }
.ach-title { font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 800; color: #e2d9cc; letter-spacing: 0.04em; text-shadow: 0 2px 10px rgba(0,0,0,0.9); }
.ach-sub { font-family: 'Press Start 2P', monospace; font-size: 0.7rem; color: #b59410; margin-top: 0.25rem; }
.ach-loading { text-align: center; padding: 3rem; }
.loading-spinner { width: 36px; height: 36px; border: 3px solid rgba(255,255,255,0.07); border-top-color: #b59410; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }

.ach-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
.ach-card {
  display: flex; align-items: center; gap: 0.875rem; padding: 1.1rem 1.25rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.25);
  box-shadow: 0 0 0 2px #050608, 0 6px 20px rgba(0,0,0,0.7);
  border-radius: 4px; position: relative; transition: all 0.25s;
}
.ach-card:hover { transform: translateY(-3px); border-color: rgba(230, 200, 69, 0.6); box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(181, 148, 16, 0.3); }
.ach-locked { opacity: 0.5; filter: grayscale(0.8); }
.ach-icon { font-size: 2rem; flex-shrink: 0; filter: drop-shadow(0 0 6px rgba(181, 148, 16, 0.4)); }
.ach-icon.locked { filter: grayscale(1); }
.ach-body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.25rem; }
.ach-name { font-family: 'Cinzel', serif; font-size: 0.95rem; font-weight: 700; color: #f5efe6; letter-spacing: 0.03em; }
.ach-desc { font-family: 'VT323', monospace; font-size: 0.95rem; color: #a09789; line-height: 1.35; }
.ach-earned { font-family: 'Press Start 2P', monospace; font-size: 0.58rem; color: #52b788; }
.ach-rarity { font-family: 'Press Start 2P', monospace; font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 0.2rem; }
.ach-badge { position: absolute; top: 0.6rem; right: 0.6rem; width: 22px; height: 22px; background: rgba(45,106,79,0.3); border: 1px solid #52b788; box-shadow: 0 0 0 1px #050608; border-radius: 2px; display: flex; align-items: center; justify-content: center; font-family: 'Press Start 2P', monospace; font-size: 0.6rem; color: #52b788; }
.rarity-border-legendary { border-color: #b59410; box-shadow: 0 0 0 2px #050608, 0 0 15px rgba(181, 148, 16, 0.35); }
.rarity-border-epic { border-color: #9333ea; box-shadow: 0 0 0 2px #050608, 0 0 15px rgba(147, 51, 234, 0.35); }
.rarity-border-rare { border-color: #2563eb; }
.ach-empty { grid-column: 1/-1; text-align: center; padding: 3rem; font-family: 'VT323', monospace; font-size: 1.25rem; color: #8b8070; }
</style>
