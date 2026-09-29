<template>
  <div class="chronicle-hall">
    <div class="ch-bg"></div>
    <div class="ch-content">
      <div class="ch-header animate-slide-up">
        <router-link to="/world" class="btn btn-ghost btn-sm">← World</router-link>
        <div>
          <h1 class="ch-title">📜 Chronicle Hall</h1>
          <p class="ch-sub">What scholars are doing in the world</p>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="ch-loading"><div class="loading-spinner"></div></div>

      <!-- Empty -->
      <div v-else-if="posts.length === 0" class="ch-empty glass-card animate-slide-up">
        <div>📜</div>
        <h3>The Chronicle is quiet...</h3>
        <p>Be the first to achieve something great!</p>
      </div>

      <!-- Feed -->
      <div v-else class="ch-feed animate-slide-up">
        <div v-for="post in posts" :key="post.id" class="post-card glass-card">
          <!-- Avatar + name -->
          <div class="post-author">
            <AvatarImg :style-name="post.avatar_style || 'adventurer'" :seed="post.avatar_seed || post.nickname" :size="36" class="post-avatar" />
            <div class="post-author-info">
              <span class="post-nickname">{{ post.nickname }}</span>
              <span class="post-level">Lv.{{ post.level }}</span>
            </div>
            <div class="post-type-badge" :class="'ptype-' + post.post_type">
              {{ postTypeLabel(post.post_type) }}
            </div>
          </div>

          <!-- Content -->
          <div class="post-body">
            <p class="post-title">{{ post.title }}</p>
            <p v-if="post.body" class="post-text">{{ post.body }}</p>
          </div>

          <!-- Footer -->
          <div class="post-footer">
            <span class="post-time">{{ timeAgo(post.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Load more -->
      <button v-if="hasMore && !loading" class="btn btn-ghost load-more-btn" @click="loadMore">
        Load More
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AvatarImg from '../components/AvatarImg.vue'
import api from '../services/api'

const posts = ref([])
const loading = ref(true)
const page = ref(1)
const hasMore = ref(true)

const POST_TYPE_LABELS = {
  achievement: '🏅 Achievement',
  adventure_victory: '⚔️ Battle Victory',
  boss_victory: '👑 Boss Defeated',
  multiplayer_win: '🏟️ Arena Win',
  level_up: '⬆️ Level Up',
  chapter_complete: '📖 Chapter Complete',
}

function postTypeLabel(type) {
  return POST_TYPE_LABELS[type] || type
}

function timeAgo(dateStr) {
  if (!dateStr) return ''
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'Just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

async function loadFeed(p = 1) {
  loading.value = true
  try {
    const res = await api.get('/chronicle?page=' + p)
    if (res.data.success) {
      const newPosts = res.data.posts || []
      if (p === 1) posts.value = newPosts
      else posts.value = [...posts.value, ...newPosts]
      hasMore.value = newPosts.length === 20
    }
  } catch { }
  loading.value = false
}

async function loadMore() {
  page.value++
  await loadFeed(page.value)
}

onMounted(() => loadFeed())
</script>

<style scoped>
.chronicle-hall { min-height: 100vh; position: relative; padding-top: 56px; }
.ch-bg { position: fixed; inset: 0; z-index: 0; background: radial-gradient(ellipse at 50% 0%, rgba(139,26,26,0.12) 0%, transparent 60%), #050608; }
.ch-content { position: relative; z-index: 1; max-width: 760px; margin: 0 auto; padding: 1.5rem 1.25rem; display: flex; flex-direction: column; gap: 1.25rem; }
.ch-header { display: flex; align-items: center; gap: 1.25rem; }
.ch-title { font-family: 'Cinzel', serif; font-size: 2rem; font-weight: 800; color: #e2d9cc; letter-spacing: 0.04em; text-shadow: 0 2px 10px rgba(0,0,0,0.9); }
.ch-sub { font-family: 'VT323', monospace; font-size: 1.05rem; color: #a09789; }

.ch-loading { text-align: center; padding: 3rem; }
.loading-spinner { width: 36px; height: 36px; border: 3px solid rgba(255,255,255,0.07); border-top-color: #b59410; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }
.ch-empty { text-align: center; padding: 3rem; display: flex; flex-direction: column; gap: 0.75rem; align-items: center; color: #8b8070; font-size: 3rem; background: #0c0d12; border: 1px solid rgba(181, 148, 16, 0.2); box-shadow: 0 0 0 2px #050608; }
.ch-empty h3 { font-family: 'Cinzel', serif; font-size: 1.3rem; color: #e2d9cc; }
.ch-empty p { font-family: 'VT323', monospace; font-size: 1.1rem; }

.ch-feed { display: flex; flex-direction: column; gap: 1rem; }
.post-card { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; background: linear-gradient(180deg, #141318 0%, #0c0d12 100%); border: 1px solid rgba(255, 255, 255, 0.08); box-shadow: 0 0 0 2px #050608, 0 6px 20px rgba(0,0,0,0.7); border-radius: 4px; transition: transform 0.2s; }
.post-card:hover { transform: translateY(-2px); border-color: rgba(181, 148, 16, 0.4); }
.post-author { display: flex; align-items: center; gap: 0.75rem; }
.post-avatar { border-radius: 4px; border: 1px solid rgba(181, 148, 16, 0.3); flex-shrink: 0; box-shadow: 0 0 0 1px #050608; }
.post-author-info { flex: 1; }
.post-nickname { display: block; font-family: 'Cinzel', serif; font-size: 0.95rem; font-weight: 700; color: #f5efe6; letter-spacing: 0.03em; }
.post-level { font-family: 'Press Start 2P', monospace; font-size: 0.6rem; color: #b59410; }
.post-type-badge { font-family: 'Press Start 2P', monospace; font-size: 0.6rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 2px; background: rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.08); color: #8b8070; white-space: nowrap; }
.ptype-boss_victory { background: rgba(139,26,26,0.3); border-color: #e63946; color: #ff6b6b; }
.ptype-adventure_victory { background: rgba(45,106,79,0.3); border-color: #52b788; color: #52b788; }
.ptype-multiplayer_win { background: rgba(181,148,16,0.2); border-color: #b59410; color: #e6c845; }
.ptype-achievement { background: rgba(126,34,206,0.25); border-color: #9333ea; color: #c084fc; }
.ptype-level_up { background: rgba(181,148,16,0.25); border-color: #e6c845; color: #e6c845; }

.post-body { }
.post-title { font-family: 'Cinzel', serif; font-size: 1rem; font-weight: 700; color: #e2d9cc; letter-spacing: 0.02em; }
.post-text { font-family: 'VT323', monospace; font-size: 1.05rem; color: #a09789; margin-top: 0.25rem; line-height: 1.45; }

.post-footer { display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.04); padding-top: 0.5rem; }
.post-time { font-family: 'Press Start 2P', monospace; font-size: 0.58rem; color: #7b7365; }

.load-more-btn { align-self: center; margin-top: 0.75rem; font-family: 'Press Start 2P', monospace; font-size: 0.7rem; }
</style>
