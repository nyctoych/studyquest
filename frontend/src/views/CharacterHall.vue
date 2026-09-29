<template>
  <div class="char-hall game-module-env module-env-sanctuary">
    <div class="ch-bg"></div>
    <div class="ch-content">
      <div class="ch-header animate-slide-up">
        <router-link to="/world" class="btn btn-ghost btn-sm">← World</router-link>
        <h1 class="ch-title flex items-center gap-2"><PixelEmoji emoji="shield" size="sm" /> Character Hall</h1>
      </div>

      <div class="ch-body animate-slide-up">
        <!-- Character Preview -->
        <div class="char-preview-panel glass-card">
          <div class="char-avatar-wrap">
            <AvatarImg
              :size="120"
              class="char-avatar"
            />
            <!-- Equipped aura effect -->
            <div v-if="equippedAura" class="preview-aura" :class="equippedAura.effect_key"></div>
          </div>
          <div class="char-meta">
            <h2 class="char-nickname">{{ auth.nickname }}</h2>
            <p class="char-level">Level {{ auth.level }} Psychology Scholar</p>
            <div class="char-stats">
              <div class="cstat flex items-center gap-1">
                <span class="cstat-icon"><PixelEmoji emoji="star" size="xs" /></span>
                <span class="cstat-val">{{ auth.xp }} XP</span>
              </div>
              <div class="cstat flex items-center gap-1">
                <span class="cstat-icon"><PixelEmoji emoji="coin" size="xs" /></span>
                <span class="cstat-val">{{ auth.coins }}</span>
              </div>
              <div class="cstat flex items-center gap-1">
                <span class="cstat-icon"><PixelEmoji emoji="sword" size="xs" /></span>
                <span class="cstat-val">{{ auth.battleRating }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Cosmetics panel -->
        <div class="cosmetics-panel">
          <!-- Category tabs -->
          <div class="cat-tabs">
            <button
              v-for="cat in categories"
              :key="cat.id"
              class="cat-tab flex items-center gap-1.5"
              :class="{ active: activeCategory === cat.id }"
              @click="activeCategory = cat.id"
            >
              <PixelEmoji :emoji="cat.icon" size="xs" /> {{ cat.name }}
            </button>
          </div>

          <!-- Equipped in this category -->
          <div class="equipped-info">
            <span class="equipped-label">Equipped:</span>
            <span class="equipped-name">{{ currentEquipped?.name || 'None' }}</span>
            <button v-if="currentEquipped" class="btn btn-ghost btn-sm" @click="unequip(currentEquipped)">
              Unequip
            </button>
          </div>

          <!-- Items grid -->
          <div v-if="loadingItems" class="items-loading"><div class="loading-spinner"></div></div>
          <div v-else class="items-grid">
            <div
              v-for="item in categoryItems"
              :key="item.id"
              class="item-card"
              :class="{
                'item-equipped': isEquipped(item),
                ['rarity-border-' + item.rarity]: true,
              }"
              @click="toggleEquip(item)"
            >
              <div class="item-icon"><PixelEmoji :emoji="item.icon || 'sparkles'" size="sm" /></div>
              <div class="item-info">
                <span class="item-name">{{ item.name }}</span>
                <span class="item-rarity" :class="'rarity-' + item.rarity">{{ item.rarity }}</span>
              </div>
              <div v-if="isEquipped(item)" class="item-equipped-badge">✓</div>
            </div>
            <div v-if="categoryItems.length === 0" class="no-items">
              <p>No {{ activeCategory }} items owned yet.</p>
              <router-link to="/market" class="btn btn-ghost btn-sm">Visit Market →</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import AvatarImg from '../components/AvatarImg.vue'
import api from '../services/api'

const auth = useAuthStore()
const toast = useToastStore()

const categories = [
  { id: 'character_skin', name: 'Skin', icon: 'person' },
  { id: 'aura', name: 'Aura', icon: 'sparkles' },
  { id: 'back_effect', name: 'Back Effect', icon: 'sparkles' },
  { id: 'hit_effect', name: 'Hit Effect', icon: 'lightning' },
  { id: 'finisher_effect', name: 'Finisher', icon: 'fire' },
]

const activeCategory = ref('character_skin')
const ownedItems = ref([])
const loadingItems = ref(true)

const categoryItems = computed(() =>
  ownedItems.value.filter(i => i.category_slug === activeCategory.value)
)

const currentEquipped = computed(() =>
  auth.equipped.find(e => e.category_slug === activeCategory.value)
)

const equippedAura = computed(() => auth.equipped.find(e => e.category_slug === 'aura'))

onMounted(async () => {
  try {
    const res = await api.get('/shop/owned')
    if (res.data.success) ownedItems.value = res.data.items || []
  } catch { }
  loadingItems.value = false
})

function isEquipped(item) {
  return auth.equipped.some(e => e.id === item.id)
}

async function toggleEquip(item) {
  if (isEquipped(item)) {
    await unequip(item)
  } else {
    await equip(item)
  }
}

async function equip(item) {
  try {
    await api.post('/shop/equip', { item_id: item.id })
    await auth.fetchUser()
    toast.success(`${item.name} equipped!`)
  } catch {
    toast.error('Failed to equip item')
  }
}

async function unequip(item) {
  try {
    await api.post('/shop/unequip', { item_id: item.id })
    await auth.fetchUser()
    toast.info(`${item.name} unequipped`)
  } catch {
    toast.error('Failed to unequip item')
  }
}
</script>

<style scoped>
.char-hall { min-height: 100vh; position: relative; padding-top: 56px; }
.ch-bg { position: fixed; inset: 0; z-index: 0; background: radial-gradient(ellipse at 50% 20%, rgba(139,26,26,0.12) 0%, transparent 60%), #050608; }
.ch-content { position: relative; z-index: 1; max-width: 950px; margin: 0 auto; padding: 1.5rem 1.25rem; }
.ch-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.75rem; }
.ch-title { font-family: 'Cinzel', serif; font-size: 2.2rem; font-weight: 800; color: #e2d9cc; letter-spacing: 0.04em; text-shadow: 0 2px 10px rgba(0,0,0,0.9); }

.ch-body { display: grid; grid-template-columns: 300px 1fr; gap: 1.5rem; align-items: start; }

/* Preview */
.char-preview-panel { padding: 2rem 1.5rem; display: flex; flex-direction: column; align-items: center; gap: 1.25rem; text-align: center; background: linear-gradient(180deg, #141318 0%, #0c0d12 100%); border: 1px solid rgba(181, 148, 16, 0.35); box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0,0,0,0.7); border-radius: 4px; }
.char-avatar-wrap { position: relative; }
.char-avatar { border-radius: 4px; border: 2px solid rgba(181, 148, 16, 0.4); box-shadow: 0 0 0 2px #050608; }
.preview-aura { position: absolute; inset: -8px; border-radius: 4px; animation: auraRotate 3s linear infinite; pointer-events: none; }
@keyframes auraRotate { from { filter: hue-rotate(0deg) brightness(1.5); } to { filter: hue-rotate(360deg) brightness(1.5); } }
.char-nickname { font-family: 'Cinzel', serif; font-size: 1.35rem; font-weight: 700; color: #f5efe6; letter-spacing: 0.03em; }
.char-level { font-family: 'VT323', monospace; font-size: 1rem; color: #a09789; }
.char-stats { display: flex; gap: 1rem; margin-top: 0.5rem; }
.cstat { display: flex; align-items: center; gap: 0.35rem; }
.cstat-icon { font-size: 0.95rem; }
.cstat-val { font-family: 'Press Start 2P', monospace; font-size: 0.65rem; color: #e2d9cc; }

/* Cosmetics panel */
.cosmetics-panel { display: flex; flex-direction: column; gap: 1rem; }
.cat-tabs { display: flex; flex-wrap: wrap; gap: 0.4rem; background: #08090c; padding: 0.35rem; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 0 0 2px #050608; }
.cat-tab {
  padding: 0.5rem 0.875rem; border-radius: 2px; border: 1px solid transparent;
  background: transparent; color: #8b8070;
  font-family: 'Cinzel', serif; font-size: 0.8rem; font-weight: 700; letter-spacing: 0.04em;
  cursor: pointer; transition: all 0.2s;
}
.cat-tab.active, .cat-tab:hover {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%); border-color: #b91c1c; color: #f5efe6; box-shadow: 0 0 0 1px #050608;
}

.equipped-info { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #0c0d12; border: 1px solid rgba(181, 148, 16, 0.25); box-shadow: 0 0 0 1px #050608; }
.equipped-label { font-family: 'Cinzel', serif; font-size: 0.75rem; color: #b59410; text-transform: uppercase; }
.equipped-name { font-family: 'Cinzel', serif; font-weight: 700; font-size: 0.9rem; color: #e2d9cc; flex: 1; }

.items-loading { text-align: center; padding: 2rem; }
.loading-spinner { width: 32px; height: 32px; border: 3px solid rgba(255,255,255,0.07); border-top-color: #b59410; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto; }
@keyframes spin { to { transform: rotate(360deg); } }

.items-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; }
.item-card {
  position: relative;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%); border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px; padding: 0.875rem; cursor: pointer;
  transition: all 0.2s; display: flex; flex-direction: column; gap: 0.4rem;
}
.item-card:hover { transform: translateY(-2px); border-color: rgba(181, 148, 16, 0.5); }
.item-equipped { border-color: #8b1a1a; background: linear-gradient(180deg, #201015 0%, #0c0d12 100%); box-shadow: 0 0 0 2px #050608, 0 0 12px rgba(139, 26, 26, 0.4); }
.item-icon { font-size: 1.6rem; filter: drop-shadow(0 0 4px rgba(181, 148, 16, 0.4)); }
.item-name { font-family: 'Cinzel', serif; font-size: 0.82rem; font-weight: 700; color: #f5efe6; letter-spacing: 0.02em; }
.item-rarity { font-family: 'Press Start 2P', monospace; font-size: 0.55rem; text-transform: uppercase; }
.item-equipped-badge {
  position: absolute; top: 0.4rem; right: 0.4rem;
  background: #8b1a1a; border: 1px solid #b91c1c;
  border-radius: 2px; width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem; color: #fff;
}
.no-items { grid-column: 1/-1; text-align: center; padding: 2.5rem; font-family: 'VT323', monospace; font-size: 1.15rem; color: #8b8070; display: flex; flex-direction: column; gap: 0.75rem; align-items: center; }
.rarity-border-legendary { border-color: #b59410; }
.rarity-border-epic { border-color: #9333ea; }
.rarity-border-rare { border-color: #2563eb; }

@media (max-width: 768px) {
  .ch-body { grid-template-columns: 1fr; }
}
</style>
