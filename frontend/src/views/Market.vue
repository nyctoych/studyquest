<template>
  <div class="market-screen game-module-env module-env-market">
    <div class="mkt-bg"></div>
    <div class="mkt-content">
      <!-- Header -->
      <div class="mkt-header animate-slide-up">
        <router-link to="/world" class="btn btn-ghost btn-sm">← World</router-link>
        <div class="mkt-header-body">
          <h1 class="mkt-title flex items-center gap-2"><PixelEmoji emoji="cart" size="sm" /> Market</h1>
          <p class="mkt-sub">Spend your coins on cosmetics</p>
        </div>
        <div class="mkt-balance glass-card flex items-center gap-2">
          <span class="mkt-balance-icon"><PixelEmoji emoji="coin" size="sm" /></span>
          <span class="mkt-balance-val">{{ auth.coins.toLocaleString() }}</span>
        </div>
      </div>

      <!-- Category tabs -->
      <div class="mkt-tabs animate-slide-up">
        <button
          v-for="cat in categories" :key="cat.id"
          class="mkt-tab flex items-center gap-1.5" :class="{ active: activeCategory === cat.id }"
          @click="activeCategory = cat.id"
        >
          <PixelEmoji :emoji="cat.icon" size="xs" /> {{ cat.name }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="mkt-loading"><div class="loading-spinner"></div></div>

      <!-- Items grid -->
      <div v-else class="mkt-items-grid animate-slide-up">
        <div
          v-for="item in filteredItems"
          :key="item.id"
          class="mkt-item-card"
          :class="['rarity-' + item.rarity, { 'owned': isOwned(item) }]"
        >
          <div class="mkt-item-rarity">{{ item.rarity }}</div>
          <div class="mkt-item-icon"><PixelEmoji :emoji="item.icon || 'sparkles'" size="sm" /></div>
          <div class="mkt-item-name">{{ item.name }}</div>
          <div class="mkt-item-category">{{ item.category_name }}</div>

          <div class="mkt-item-price flex items-center gap-1" v-if="!isOwned(item)">
            <span class="price-icon"><PixelEmoji emoji="coin" size="xs" /></span>
            <span class="price-val">{{ item.price?.toLocaleString() }}</span>
          </div>

          <button
            v-if="isOwned(item)"
            class="btn btn-ghost btn-sm mkt-buy-btn owned-btn"
            @click="goEquip(item)"
          >
            ✓ Owned → Equip
          </button>
          <button
            v-else
            class="btn btn-gold btn-sm mkt-buy-btn"
            :disabled="auth.coins < item.price || purchasing === item.id"
            @click="purchase(item)"
          >
            {{ purchasing === item.id ? 'Purchasing...' : 'Buy' }}
          </button>
        </div>
        <div v-if="filteredItems.length === 0" class="mkt-empty">
          <p>No items in this category yet.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import api from '../services/api'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const categories = [
  { id: 'all', name: 'All', icon: 'shield' },
  { id: 'character_skin', name: 'Skins', icon: 'person' },
  { id: 'aura', name: 'Auras', icon: 'sparkles' },
  { id: 'back_effect', name: 'Back Effects', icon: 'sparkles' },
  { id: 'hit_effect', name: 'Hit Effects', icon: 'lightning' },
  { id: 'finisher_effect', name: 'Finishers', icon: 'fire' },
]

const activeCategory = ref('all')
const items = ref([])
const ownedIds = ref([])
const loading = ref(true)
const purchasing = ref(null)

const filteredItems = computed(() =>
  activeCategory.value === 'all' ? items.value :
  items.value.filter(i => i.category_slug === activeCategory.value)
)

onMounted(async () => {
  try {
    const [shopRes, ownedRes] = await Promise.all([
      api.get('/shop/items'),
      api.get('/shop/owned'),
    ])
    items.value = shopRes.data.items || []
    ownedIds.value = (ownedRes.data.items || []).map(i => i.id)
  } catch { }
  loading.value = false
})

function isOwned(item) {
  return ownedIds.value.includes(item.id)
}

async function purchase(item) {
  if (auth.coins < item.price) {
    toast.error('Not enough coins!')
    return
  }
  purchasing.value = item.id
  try {
    const res = await api.post('/shop/purchase', { item_id: item.id })
    if (res.data.success) {
      ownedIds.value.push(item.id)
      await auth.fetchUser()
      toast.success(`🎉 ${item.name} purchased!`)
    } else {
      toast.error(res.data.message || 'Purchase failed')
    }
  } catch {
    toast.error('Purchase failed')
  } finally {
    purchasing.value = null
  }
}

function goEquip(item) {
  router.push('/character')
}
</script>

<style scoped>
.market-screen { min-height: 100vh; position: relative; padding-top: 56px; }
.mkt-bg { position: fixed; inset: 0; z-index: 0; background: radial-gradient(ellipse at 50% 0%, rgba(139,26,26,0.12) 0%, transparent 60%), #050608; }
.mkt-content { position: relative; z-index: 1; max-width: 1000px; margin: 0 auto; padding: 1.5rem 1.25rem; display: flex; flex-direction: column; gap: 1.5rem; }
.mkt-header { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
.mkt-header-body { flex: 1; }
.mkt-title { font-family: 'Cinzel', serif; font-size: 2.2rem; font-weight: 800; color: #e2d9cc; letter-spacing: 0.04em; text-shadow: 0 2px 10px rgba(0,0,0,0.9); }
.mkt-sub { font-family: 'VT323', monospace; font-size: 1.1rem; color: #a09789; }
.mkt-balance { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.1rem; background: #08090c; border: 1px solid rgba(181, 148, 16, 0.35); box-shadow: 0 0 0 2px #050608; border-radius: 4px; }
.mkt-balance-icon { font-size: 1.25rem; }
.mkt-balance-val { font-family: 'Press Start 2P', monospace; font-weight: 800; font-size: 0.85rem; color: #e6c845; }

.mkt-tabs { display: flex; flex-wrap: wrap; gap: 0.4rem; background: #08090c; padding: 0.35rem; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 0 0 2px #050608; }
.mkt-tab {
  padding: 0.5rem 0.875rem; border-radius: 2px;
  border: 1px solid transparent; background: transparent;
  color: #8b8070; font-family: 'Cinzel', serif; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.04em;
  cursor: pointer; transition: all 0.2s;
}
.mkt-tab.active, .mkt-tab:hover { background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%); border-color: #b91c1c; color: #f5efe6; box-shadow: 0 0 0 1px #050608; }

.mkt-loading { text-align: center; padding: 3rem; }
.loading-spinner { width: 36px; height: 36px; border: 3px solid rgba(255,255,255,0.07); border-top-color: #b59410; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }

.mkt-items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 1rem; }
.mkt-item-card {
  position: relative;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%); border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 0 0 2px #050608, 0 6px 20px rgba(0,0,0,0.7);
  border-radius: 4px; padding: 1.25rem 1rem; text-align: center;
  display: flex; flex-direction: column; gap: 0.4rem; align-items: center;
  transition: all 0.25s;
}
.mkt-item-card:hover { transform: translateY(-3px); border-color: rgba(181, 148, 16, 0.4); box-shadow: 0 0 0 2px #050608, 0 10px 30px rgba(0,0,0,0.8); }
.mkt-item-card.owned { opacity: 0.8; }
.mkt-item-rarity { font-family: 'Press Start 2P', monospace; font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 0.2rem 0.5rem; background: rgba(0,0,0,0.6); color: #8b8070; box-shadow: 0 0 0 1px #050608; }
.mkt-item-card.rarity-legendary .mkt-item-rarity { background: rgba(181,148,16,0.2); color: #e6c845; border: 1px solid #b59410; }
.mkt-item-card.rarity-epic .mkt-item-rarity { background: rgba(147,51,234,0.2); color: #c084fc; border: 1px solid #9333ea; }
.mkt-item-card.rarity-rare .mkt-item-rarity { background: rgba(37,99,235,0.2); color: #93c5fd; border: 1px solid #2563eb; }
.mkt-item-card.rarity-legendary { border-color: rgba(181,148,16,0.4); }
.mkt-item-card.rarity-epic { border-color: rgba(147,51,234,0.4); }
.mkt-item-card.rarity-rare { border-color: rgba(37,99,235,0.4); }
.mkt-item-icon { font-size: 2.5rem; margin: 0.35rem 0; filter: drop-shadow(0 0 6px rgba(181, 148, 16, 0.4)); }
.mkt-item-name { font-family: 'Cinzel', serif; font-size: 0.92rem; font-weight: 700; color: #f5efe6; letter-spacing: 0.02em; }
.mkt-item-category { font-family: 'VT323', monospace; font-size: 0.85rem; color: #8b8070; }
.mkt-item-price { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.35rem; }
.price-icon { font-size: 1rem; }
.price-val { font-family: 'Press Start 2P', monospace; font-weight: 800; font-size: 0.75rem; color: #e6c845; }
.mkt-buy-btn { margin-top: 0.5rem; width: 100%; font-family: 'Press Start 2P', monospace; font-size: 0.65rem; }
.owned-btn { color: #52b788 !important; border-color: rgba(82,183,136,0.4) !important; font-family: 'Press Start 2P', monospace; }
.mkt-empty { grid-column: 1/-1; text-align: center; padding: 3rem; font-family: 'VT323', monospace; font-size: 1.25rem; color: #8b8070; }
</style>
