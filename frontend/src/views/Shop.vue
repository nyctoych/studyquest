<template>
  <div class="shop-container game-module-env module-env-market">
    <!-- ── Top Header & Wallet ── -->
    <div class="shop-header">
      <div class="header-left">
        <span class="shop-badge flex items-center gap-1.5"><PixelEmoji emoji="sparkles" size="xs" /> COSMETIC ARSENAL</span>
        <h1 class="shop-title">Scholar's Effect Boutique</h1>
        <p class="shop-desc">
          Unlock auras, battle effects, skins, and finishers. All cosmetics are purely visual — your skills decide the victor.
        </p>
      </div>
      <div class="wallet-badge flex items-center gap-2">
        <PixelEmoji emoji="coin" size="lg" />
        <div class="wallet-info">
          <span class="wallet-label">SCHOLAR COINS</span>
          <span class="wallet-amount">{{ auth.user?.coins || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- ── Main Shop Layout ── -->
    <div class="shop-layout">

      <!-- Left: Live Fitting Mirror / Preview Panel -->
      <div class="fitting-room-card">
        <div class="fitting-header">
          <span class="fitting-title">✦ Fitting Mirror</span>
          <span v-if="previewItem" class="try-on-badge">Previewing</span>
        </div>

        <!-- Avatar Preview (shown for skin, aura, back_effect) -->
        <div
          v-if="!isBattleOnlyPreview"
          class="avatar-mirror-stage"
        >
          <div class="avatar-glow">
            <AvatarImg
              :preview-item="previewItem"
              :equipped="equippedList"
              :size="avatarSize"
            />
          </div>
          <!-- Back Effect demo label -->
          <div v-if="previewItem?.category_slug === 'back_effect'" class="effect-demo-label">
            <span class="flex items-center gap-1.5"><PixelEmoji emoji="sparkles" size="xs" /> Back Effect Active</span>
          </div>
          <!-- Aura demo label -->
          <div v-if="previewItem?.category_slug === 'aura'" class="effect-demo-label">
            <span class="flex items-center gap-1.5"><PixelEmoji emoji="sparkles" size="xs" /> Aura Ring Active</span>
          </div>
        </div>

        <!-- Battle-Only Effect Demo Panel (hit_effect / finisher_effect) -->
        <div v-else class="battle-effect-demo">
          <div class="demo-arena" :class="previewItem?.effect_key">
            <div class="demo-arena-bg"></div>
            <div class="demo-fighter self-fighter">
              <AvatarImg
                :size="72"
              />
              <span class="fighter-label">YOU</span>
            </div>
            <div class="demo-vs">VS</div>
            <div class="demo-fighter opp-fighter">
              <div class="opp-avatar"><PixelEmoji emoji="sword" size="sm" /></div>
              <span class="fighter-label">OPP</span>
            </div>
            <!-- Hit Effect Burst -->
            <div v-if="previewItem?.category_slug === 'hit_effect'" class="hit-burst" :class="previewItem?.effect_key"></div>
            <!-- Finisher Overlay -->
            <div v-if="previewItem?.category_slug === 'finisher_effect'" class="finisher-overlay" :class="previewItem?.effect_key">
              <span class="finisher-text">VICTORY!</span>
            </div>
          </div>
          <p class="battle-only-note flex items-center justify-center gap-1.5"><PixelEmoji emoji="sword" size="xs" /> This effect activates in multiplayer battles</p>
        </div>

        <!-- Fitted Info -->
        <div class="fitting-meta">
          <h3 class="hero-name">{{ auth.user?.username || 'Scholar' }}</h3>
          <p class="hero-tag">Level {{ auth.level || 1 }} Psychology Contender</p>
        </div>

        <div class="equipped-slots-summary">
          <div v-if="previewItem" class="slot-row preview-row">
            <span class="slot-icon"><PixelEmoji :emoji="getCategoryIcon(previewItem.category_slug)" size="xs" /></span>
            <span class="slot-lbl">{{ previewItem.name }}</span>
            <span class="rarity-pill" :class="previewItem.rarity">{{ previewItem.rarity }}</span>
            <button class="btn-clear-preview" @click="previewItem = null">✕ Reset</button>
          </div>
          <div v-else class="slot-row hint-row">
            <span>Click an item below to preview it here</span>
          </div>

          <!-- Equipped Slots Summary -->
          <div class="equipped-slots-grid">
            <div
              v-for="slot in COSMETIC_SLOTS"
              :key="slot.slug"
              class="slot-chip"
              :class="{ active: equippedItems[slot.slug] }"
            >
              <PixelEmoji :emoji="slot.icon" size="xs" />
              <span class="slot-chip-label">{{ equippedItems[slot.slug]?.name || slot.label }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Category Filters & Items Grid -->
      <div class="boutique-main">
        <!-- Category Filter Tabs -->
        <div class="category-tabs">
          <button
            class="cat-btn flex items-center gap-1.5"
            :class="{ active: selectedCategory === null }"
            @click="filterCategory(null)"
          >
            <span class="cat-icon"><PixelEmoji emoji="shield" size="xs" /></span>
            <span>All</span>
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            class="cat-btn flex items-center gap-1.5"
            :class="{ active: selectedCategory === cat.slug }"
            @click="filterCategory(cat.slug)"
          >
            <span class="cat-icon"><PixelEmoji :emoji="getCategoryIcon(cat.slug)" size="xs" /></span>
            <span>{{ cat.name }}</span>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <span>Loading cosmetic effects…</span>
        </div>

        <!-- Items Showcase Grid -->
        <div v-else class="items-grid">
          <div
            v-for="item in items"
            :key="item.id"
            class="item-card"
            :class="[item.rarity, {
              owned: isOwned(item.id),
              equipped: isEquipped(item.id),
              previewing: previewItem?.id === item.id
            }]"
          >
            <!-- Card Top: category + rarity -->
            <div class="item-card-top">
              <span class="slot-tag flex items-center gap-1.5">
                <PixelEmoji :emoji="getCategoryIcon(item.category_slug)" size="xs" /> {{ item.category_name }}
              </span>
              <span class="rarity-badge" :class="item.rarity">{{ item.rarity }}</span>
            </div>

            <!-- Effect Visual Preview Area -->
            <div class="effect-preview-area" @click="previewOnAvatar(item)">
              <CosmeticEffectPreview :item="item" />
              <div class="try-on-overlay">
                <span>{{ previewItem?.id === item.id ? '✓ Previewing' : 'Preview' }}</span>
              </div>
            </div>

            <!-- Item Details -->
            <div class="item-body">
              <h3 class="item-name">{{ item.name }}</h3>
              <p class="item-desc">{{ item.description }}</p>
            </div>

            <!-- Footer: Price & Actions -->
            <div class="item-footer">
              <div class="cost-tag flex items-center gap-1">
                <PixelEmoji emoji="coin" size="xs" />
                <span class="cost-val">{{ item.coin_cost === 0 ? 'FREE' : item.coin_cost }}</span>
              </div>

              <div class="btn-group">
                <button
                  class="btn-try"
                  :class="{ active: previewItem?.id === item.id }"
                  @click.stop="previewOnAvatar(item)"
                >
                  {{ previewItem?.id === item.id ? '✓ Previewing' : 'Preview' }}
                </button>
                <button
                  v-if="isEquipped(item.id)"
                  class="btn-equipped"
                  @click.stop="handleUnequip(item)"
                >
                  Equipped ✓
                </button>
                <button
                  v-else-if="isOwned(item.id)"
                  class="btn-equip"
                  @click.stop="handleEquip(item.id)"
                >
                  Equip
                </button>
                <button
                  v-else
                  class="btn-buy"
                  :disabled="(auth.user?.coins || 0) < item.coin_cost"
                  @click.stop="handlePurchase(item)"
                >
                  Purchase
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import shopService from '../services/shopService'
import CosmeticEffectPreview from '../components/shop/CosmeticEffectPreview.vue'
import CoinIcon from '../components/common/CoinIcon.vue'
import AvatarImg from '../components/AvatarImg.vue'

const auth  = useAuthStore()
const toast = useToastStore()

// ── Dynamic Avatar Size for responsive mirror ─────────────────────────
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)
function handleResize() {
  windowWidth.value = window.innerWidth
}

const avatarSize = computed(() => {
  if (windowWidth.value <= 400) return 110
  if (windowWidth.value <= 480) return 125
  if (windowWidth.value <= 768) return 140
  return 160
})

// ── 5 Cosmetic Slot definitions ──────────────────────────────────────
const COSMETIC_SLOTS = [
  { slug: 'character_skin',  icon: 'person', label: 'No Skin' },
  { slug: 'aura',            icon: 'sparkles', label: 'No Aura' },
  { slug: 'back_effect',     icon: 'sparkles', label: 'No Back Effect' },
  { slug: 'hit_effect',      icon: 'lightning', label: 'No Hit Effect' },
  { slug: 'finisher_effect', icon: 'trophy', label: 'No Finisher' },
]

const BATTLE_ONLY_SLUGS = ['hit_effect', 'finisher_effect']

const items           = ref([])
const categories      = ref([])
const inventory       = ref([])
const equippedItems   = ref({})   // keyed by slot/category_slug
const selectedCategory = ref(null)
const previewItem     = ref(null)
const loading         = ref(true)

// Whether the current preview is a battle-only effect
const isBattleOnlyPreview = computed(() =>
  previewItem.value && BATTLE_ONLY_SLUGS.includes(previewItem.value.category_slug)
)

// Flat list of equipped items for AvatarImg :equipped prop
const equippedList = computed(() => Object.values(equippedItems.value))

async function loadShop(cat = null) {
  loading.value = true
  try {
    const res = await shopService.getItems(cat)
    if (res.success) {
      items.value      = res.items      || []
      categories.value = res.categories || []
    }
  } catch (e) {
    console.error('Shop load error:', e)
  } finally {
    loading.value = false
  }
}

async function loadInventory() {
  try {
    const res = await shopService.getInventory()
    if (res.success) {
      inventory.value    = res.inventory || []
      equippedItems.value = res.equipped  || {}
    }
  } catch (e) {
    console.error('Inventory load error:', e)
  }
}

onMounted(async () => {
  window.addEventListener('resize', handleResize)
  handleResize()
  await Promise.all([loadShop(), loadInventory()])
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

function filterCategory(catSlug) {
  selectedCategory.value = catSlug
  loadShop(catSlug)
}

function getCategoryIcon(slug) {
  const map = {
    character_skin:  'person',
    aura:            'sparkles',
    back_effect:     'sparkles',
    hit_effect:      'lightning',
    finisher_effect: 'trophy',
  }
  return map[slug] || 'gift'
}

function isOwned(itemId) {
  return inventory.value.some(inv => inv.id === itemId)
}

function isEquipped(itemId) {
  return Object.values(equippedItems.value).some(e => e?.id === itemId)
}

function previewOnAvatar(item) {
  previewItem.value = (previewItem.value?.id === item.id) ? null : item
}

async function handlePurchase(item) {
  try {
    const res = await shopService.purchase(item.id)
    if (res.success) {
      toast.success(`Purchased ${item.name}!`)
      await auth.fetchUser()
      await loadInventory()
    } else {
      toast.error(res.message || 'Purchase failed')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Purchase failed')
  }
}

async function handleEquip(itemId) {
  try {
    const res = await shopService.equip(itemId)
    if (res.success) {
      toast.success(res.message || 'Equipped!')
      previewItem.value = null
      await auth.fetchUser()
      await loadInventory()
    } else {
      toast.error(res.message || 'Equip failed')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Equip failed')
  }
}

async function handleUnequip(item) {
  try {
    const res = await shopService.unequip(item.category_slug)
    if (res.success) {
      toast.success(res.message || 'Unequipped')
      await auth.fetchUser()
      await loadInventory()
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Unequip failed')
  }
}
</script>

<style scoped>
/* ── Container ── */
.shop-container {
  padding: 0;
  max-width: 1320px;
  width: 100%;
  margin: 0 auto;
  color: #e2d9cc;
  min-width: 0;
  box-sizing: border-box;
  position: relative;
  z-index: 1;
}

/* ── Header ── */
.shop-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.35);
  border-radius: 4px;
  padding: 2rem 2.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 0 0 2px #050608, 0 10px 40px -5px rgba(0,0,0,0.85);
  min-width: 0;
  box-sizing: border-box;
  position: relative;
}

.shop-header::before {
  content: "❖";
  position: absolute;
  top: -8px;
  left: 20px;
  color: #b59410;
  font-size: 14px;
}

.shop-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #b59410;
  background: rgba(181, 148, 16, 0.12);
  padding: 0.3rem 0.8rem;
  border-radius: 2px;
  margin-bottom: 0.5rem;
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 1px #050608;
}

.shop-title {
  font-family: 'Cinzel', serif;
  font-size: 2.3rem;
  font-weight: 800;
  margin: 0 0 0.4rem;
  color: #e2d9cc;
  letter-spacing: 0.03em;
  text-shadow: 0 2px 10px rgba(0,0,0,0.9);
  word-break: break-word;
}

.shop-desc {
  color: #a09789;
  font-size: 0.95rem;
  max-width: 580px;
  margin: 0;
  line-height: 1.5;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
}

.wallet-badge {
  display: flex;
  align-items: center;
  gap: 1.2rem;
  background: #08090c;
  border: 1px solid rgba(181, 148, 16, 0.4);
  padding: 1.2rem 1.8rem;
  border-radius: 4px;
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(0,0,0,0.7);
  flex-shrink: 0;
  box-sizing: border-box;
}

.wallet-label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #8b8070;
  margin-bottom: 0.2rem;
}

.wallet-amount {
  font-family: 'Press Start 2P', monospace;
  font-size: 1.4rem;
  font-weight: 900;
  color: #e6c845;
  text-shadow: 0 0 14px rgba(230,200,69,0.45);
}

/* ── Layout Grid ── */
.shop-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 2rem;
  align-items: start;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

/* ── Fitting Room Card ── */
.fitting-room-card {
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 12px 40px rgba(0,0,0,0.8);
  border-radius: 4px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: calc(var(--hud-height, 60px) + 1.25rem);
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.fitting-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.fitting-title {
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #b59410;
  text-transform: uppercase;
}

.try-on-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  font-weight: 700;
  color: #e6c845;
  background: rgba(181, 148, 16, 0.15);
  padding: 0.25rem 0.6rem;
  border: 1px solid rgba(181, 148, 16, 0.35);
}

/* Avatar Stage */
.avatar-mirror-stage {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: radial-gradient(ellipse at center, rgba(139,26,26,0.2) 0%, #08090c 70%);
  border: 1px solid rgba(181, 148, 16, 0.25);
  border-radius: 4px;
  padding: 2rem 1rem;
  min-height: 200px;
  position: relative;
  overflow: hidden;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.avatar-glow {
  filter: drop-shadow(0 0 20px rgba(181, 148, 16, 0.35));
  transition: filter 0.3s ease;
}

.effect-demo-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #d4ccb8;
  background: rgba(0,0,0,0.6);
  padding: 0.3rem 0.75rem;
  border: 1px solid rgba(255,255,255,0.1);
  box-shadow: 0 0 0 1px #050608;
}

/* Battle-Only Demo Panel */
.battle-effect-demo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.demo-arena {
  width: 100%;
  border-radius: 4px;
  background: #08090c;
  border: 1px solid rgba(139, 26, 26, 0.4);
  box-shadow: 0 0 0 2px #050608;
  padding: 1.5rem 1rem;
  display: flex;
  align-items: center;
  justify-content: space-around;
  position: relative;
  overflow: hidden;
  min-height: 120px;
}

.demo-arena-bg {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, rgba(139,26,26,0.15) 0%, transparent 70%);
}

.demo-fighter {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
  z-index: 1;
}

.opp-avatar {
  font-size: 2.5rem;
  width: 72px;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(139,26,26,0.25);
  border: 2px solid rgba(230,57,70,0.4);
  border-radius: 4px;
}

.fighter-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8b8070;
}

.demo-vs {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.9rem;
  color: #b59410;
  z-index: 1;
  text-shadow: 0 0 8px rgba(181,148,16,0.6);
}

.hit-burst {
  position: absolute;
  right: 22%;
  top: 50%;
  transform: translate(50%, -50%);
  width: 60px;
  height: 60px;
  border-radius: 4px;
  z-index: 2;
  animation: hitBurstPop 1.2s ease-in-out infinite;
}
.hit-burst.hit-focus    { background: radial-gradient(circle, #ffffff 0%, rgba(255,255,255,0) 70%); box-shadow: 0 0 20px white; }
.hit-burst.hit-psych    { background: radial-gradient(circle, #8b1a1a 0%, rgba(139,26,26,0) 70%); box-shadow: 0 0 20px #e63946; }
.hit-burst.hit-mind     { background: radial-gradient(circle, #9333ea 0%, rgba(147,51,234,0) 70%); box-shadow: 0 0 20px #9333ea; }
.hit-burst.hit-collapse { background: radial-gradient(circle, #050608 0%, rgba(5,6,8,0) 70%); box-shadow: 0 0 24px #000, 0 0 48px rgba(139,26,26,0.6); }

@keyframes hitBurstPop {
  0%   { transform: translate(50%,-50%) scale(0.3); opacity: 0; }
  40%  { transform: translate(50%,-50%) scale(1.1); opacity: 1; }
  70%  { transform: translate(50%,-50%) scale(0.85); opacity: 0.9; }
  100% { transform: translate(50%,-50%) scale(0.3); opacity: 0; }
}

.finisher-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  z-index: 3;
  animation: finisherLoop 2s ease-in-out infinite;
}
.finisher-overlay.finish-flash    { background: rgba(255,255,255,0.12); border: 2px solid rgba(255,255,255,0.3); }
.finisher-overlay.finish-insight  { background: rgba(139,26,26,0.2); border: 2px solid rgba(230,57,70,0.4); }
.finisher-overlay.finish-enlighten{ background: rgba(181,148,16,0.15); border: 2px solid rgba(181,148,16,0.4); }
.finisher-overlay.finish-legend   { background: linear-gradient(135deg, rgba(139,26,26,0.2), rgba(147,51,234,0.2)); border: 2px solid rgba(147,51,234,0.5); }

.finisher-text {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  font-weight: 900;
  letter-spacing: 0.12em;
  color: #e6c845;
  text-shadow: 0 0 16px rgba(230,200,69,0.8);
}

@keyframes finisherLoop {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.battle-only-note {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #8b8070;
  text-align: center;
  margin: 0;
}

/* Fitting Meta */
.fitting-meta {
  text-align: center;
}

.hero-name {
  font-family: 'Cinzel', serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #f5efe6;
  margin: 0 0 0.2rem;
  letter-spacing: 0.03em;
}

.hero-tag {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #8b8070;
  margin: 0;
}

/* Equipped Slots Summary */
.equipped-slots-summary {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.slot-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.preview-row {
  background: rgba(181, 148, 16, 0.08);
  border: 1px solid rgba(181, 148, 16, 0.3);
  border-radius: 2px;
  padding: 0.5rem 0.75rem;
}

.hint-row {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #7b7365;
  text-align: center;
  justify-content: center;
}

.slot-icon { font-size: 1rem; }

.slot-lbl {
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  color: #e2d9cc;
  flex: 1;
}

.rarity-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 0.2rem 0.5rem;
}
.rarity-pill.common    { background: rgba(100,116,139,0.2); color: #94a3b8; border: 1px solid rgba(100,116,139,0.3); }
.rarity-pill.rare      { background: rgba(37,99,235,0.2); color: #93c5fd; border: 1px solid rgba(37,99,235,0.35); }
.rarity-pill.epic      { background: rgba(147,51,234,0.2); color: #c4b5fd; border: 1px solid rgba(147,51,234,0.35); }
.rarity-pill.legendary { background: rgba(181,148,16,0.2); color: #fde68a; border: 1px solid rgba(181,148,16,0.4); }

.btn-clear-preview {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  background: rgba(139,26,26,0.2);
  border: 1px solid rgba(230,57,70,0.4);
  color: #ff8585;
  padding: 0.25rem 0.6rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-clear-preview:hover { background: rgba(139,26,26,0.35); }

.equipped-slots-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem;
}

.slot-chip {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: #08090c;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 2px;
  padding: 0.4rem 0.6rem;
  font-family: 'VT323', monospace;
  font-size: 0.9rem;
  color: #8b8070;
  transition: all 0.2s;
}
.slot-chip.active {
  border-color: rgba(181,148,16,0.4);
  color: #e6c845;
  background: rgba(181,148,16,0.1);
}
.slot-chip-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 80px;
}

/* ── Boutique Main (right side) ── */
.boutique-main {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-width: 0;
  max-width: 100%;
  width: 100%;
  box-sizing: border-box;
}

/* ── Category Tabs ── */
.category-tabs {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  background: #08090c;
  padding: 0.35rem;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 0 0 2px #050608;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}

.cat-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: transparent;
  border: 1px solid transparent;
  color: #8b8070;
  padding: 0.55rem 1.1rem;
  border-radius: 2px;
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.cat-btn:hover { border-color: rgba(181,148,16,0.4); color: #e2d9cc; background: rgba(181,148,16,0.08); }
.cat-btn.active {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  border-color: #b91c1c;
  color: #f5efe6;
  box-shadow: 0 0 0 1px #050608;
}

.cat-icon { font-size: 1.05rem; }

/* Loading */
.loading-state {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: #8b8070;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  justify-content: center;
}
.loading-spinner {
  width: 24px; height: 24px;
  border: 3px solid rgba(181,148,16,0.2);
  border-top-color: #b59410;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Items Grid ── */
.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.25rem;
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
}

.item-card {
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border-radius: 4px;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 0 0 2px #050608;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
  position: relative;
}
.item-card:hover { transform: translateY(-3px); box-shadow: 0 0 0 2px #050608, 0 12px 32px rgba(0,0,0,0.8); }
.item-card.previewing { border-color: rgba(181,148,16,0.6); box-shadow: 0 0 0 2px #050608, 0 0 20px rgba(181,148,16,0.25); }
.item-card.owned:not(.equipped) { border-color: rgba(181,148,16,0.3); }
.item-card.equipped { border-color: #52b788; box-shadow: 0 0 0 2px #050608, 0 0 14px rgba(82,183,136,0.2); }

/* Rarity glow */
.item-card.rare      { border-color: rgba(37,99,235,0.35); }
.item-card.epic      { border-color: rgba(147,51,234,0.45); }
.item-card.legendary { border-color: rgba(181,148,16,0.5); box-shadow: 0 0 0 2px #050608, 0 0 18px rgba(181,148,16,0.2); }

.item-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.6rem 0.8rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.slot-tag {
  font-family: 'Cinzel', serif;
  font-size: 0.7rem;
  font-weight: 700;
  color: #8b8070;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.rarity-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 0.2rem 0.45rem;
  border-radius: 2px;
}
.rarity-badge.common    { background: rgba(100,116,139,0.2); color: #94a3b8; }
.rarity-badge.rare      { background: rgba(37,99,235,0.2); color: #93c5fd; }
.rarity-badge.epic      { background: rgba(147,51,234,0.25); color: #c4b5fd; }
.rarity-badge.legendary { background: rgba(181,148,16,0.2); color: #fde68a; }

/* Effect Preview Area */
.effect-preview-area {
  position: relative;
  height: 110px;
  background: #08090c;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.try-on-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.2s;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  font-weight: 700;
  color: #f5efe6;
  text-shadow: 0 2px 6px rgba(0,0,0,0.8);
}
.effect-preview-area:hover .try-on-overlay { opacity: 1; background: rgba(0,0,0,0.5); }

/* Item Body */
.item-body {
  padding: 0.75rem 0.85rem;
  flex: 1;
}

.item-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #f5efe6;
  margin: 0 0 0.3rem;
  letter-spacing: 0.02em;
}

.item-desc {
  font-family: 'VT323', monospace;
  font-size: 0.95rem;
  color: #8b8070;
  margin: 0;
  line-height: 1.45;
}

/* Item Footer */
.item-footer {
  padding: 0.7rem 0.85rem;
  border-top: 1px solid rgba(255,255,255,0.06);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.cost-tag {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-weight: 700;
  font-size: 0.85rem;
}

.cost-val { font-family: 'Press Start 2P', monospace; font-size: 0.7rem; color: #e6c845; }

.btn-group {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.btn-try, .btn-equip, .btn-equipped, .btn-buy {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.4rem 0.7rem;
  border-radius: 2px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  white-space: nowrap;
}

.btn-try {
  background: rgba(181,148,16,0.12);
  border: 1px solid rgba(181,148,16,0.3);
  color: #e6c845;
}
.btn-try:hover, .btn-try.active {
  background: rgba(181,148,16,0.25);
  border-color: rgba(181,148,16,0.6);
  color: #fde68a;
}

.btn-equip {
  background: rgba(45,106,79,0.2);
  border: 1px solid rgba(82,183,136,0.4);
  color: #52b788;
}
.btn-equip:hover { background: rgba(45,106,79,0.35); }

.btn-equipped {
  background: rgba(45,106,79,0.3);
  border: 1px solid rgba(82,183,136,0.5);
  color: #52b788;
  cursor: pointer;
}
.btn-equipped:hover { background: rgba(139,26,26,0.2); border-color: rgba(230,57,70,0.4); color: #ff8585; }

.btn-buy {
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  border: 1px solid #b91c1c;
  color: #f5efe6;
  font-weight: 800;
}
.btn-buy:hover { background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%); }
.btn-buy:disabled { opacity: 0.4; cursor: not-allowed; }

@media (max-width: 1024px) {
  .shop-container {
    padding: 1.5rem 0.5rem;
  }
  .shop-layout {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  .fitting-room-card {
    position: static;
  }
  .shop-header {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .shop-container {
    padding: 0;
  }
  .shop-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
    padding: 1.25rem 1rem;
    border-radius: 4px;
    margin-bottom: 1.25rem;
  }
  .shop-title {
    font-size: 1.6rem;
  }
  .wallet-badge {
    padding: 0.75rem 1rem;
    justify-content: space-between;
    width: 100%;
  }
  .wallet-amount {
    font-size: 1.1rem;
  }
  .category-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  .cat-btn {
    white-space: nowrap;
    flex-shrink: 0;
    padding: 0.45rem 0.85rem;
    font-size: 0.75rem;
  }
  .items-grid {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1rem;
  }
}

@media (max-width: 520px) {
  .items-grid {
    grid-template-columns: 1fr;
    gap: 0.85rem;
  }
  .item-footer {
    flex-direction: column;
    align-items: stretch;
    gap: 0.6rem;
  }
  .btn-group {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.4rem;
  }
}
</style>
