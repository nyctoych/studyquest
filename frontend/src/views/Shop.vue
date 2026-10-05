<template>
  <div class="market-view-root game-module-env module-env-market">
    <!-- ── Pixel Art Shop Background & Atmospheric Lighting ── -->
    <div class="market-backdrop-wrap">
      <img
        :src="bgImage"
        alt="Mindrealm Dark Fantasy Shop"
        class="market-bg-img"
      />
      <div class="market-ambient-vignette"></div>
      <div class="market-candle-glow glow-counter"></div>
      <div class="market-candle-glow glow-shelves"></div>
      <div class="market-window-glow"></div>
    </div>

    <!-- ── Main In-World Market Stage ── -->
    <div class="market-stage-container">
      <!-- Top Sub-Bar (Local Balance & Quick Navigation) -->
      <header class="market-nav-bar">
        <button class="btn-return-world" @click="handleLeaveMarket">
          <span class="back-arrow">‹</span>
          <span class="back-text">WORLD HUB</span>
        </button>

        <div class="shop-title-emblem">
          <span class="emblem-star">✦</span>
          <h1 class="shop-title-text">THE MYSTIC MARKET</h1>
          <span class="emblem-star">✦</span>
        </div>

        <div class="market-header-pills flex items-center gap-3">
          <!-- Adventure Health & Recovery Status Pill -->
          <div class="market-hp-pill" :title="`Adventure Health: ${adventureHp} / ${adventureMaxHp} (+50 HP / hour)`">
            <span class="hp-heart-icon">💖</span>
            <div class="hp-pill-content">
              <span class="hp-amount font-pixel">{{ adventureHp }} / {{ adventureMaxHp }}</span>
              <span class="hp-regen-tag font-pixel">+50 HP/hr</span>
            </div>
          </div>

          <div class="market-wallet-pill">
            <PixelEmoji emoji="coin" size="xs" />
            <span class="wallet-amount">{{ (auth.user?.coins ?? auth.coins ?? 0).toLocaleString() }}</span>
            <span class="wallet-currency">COINS</span>
          </div>
        </div>
      </header>

      <!-- ── 2-Column RPG Layout ── -->
      <div class="market-main-grid">
        <!-- ── LEFT: NPC Shopkeeper "Raven" ── -->
        <section class="market-npc-column">
          <MarketNPC />
        </section>

        <!-- ── RIGHT/CENTER: Gothic Shop Showcase Frame ── -->
        <main class="market-showcase-column">
          <div class="gothic-shop-frame">
            <!-- Ornate Outer Frame Corners -->
            <div class="frame-edge edge-tl"></div>
            <div class="frame-edge edge-tr"></div>
            <div class="frame-edge edge-bl"></div>
            <div class="frame-edge edge-br"></div>

            <!-- Category Tabs Across Top of Frame (Attack Effects & Splash Effects only) -->
            <nav class="category-tabs-strip">
              <button
                class="category-tab"
                :class="{ active: selectedCategory === null }"
                @click="handleSelectCategory(null)"
              >
                <span class="tab-icon">✦</span>
                <span class="tab-name">All Effects</span>
              </button>

              <button
                class="category-tab"
                :class="{ active: selectedCategory === 'attack_effect' }"
                @click="handleSelectCategory('attack_effect')"
              >
                <span class="tab-icon">⚔️</span>
                <span class="tab-name">Attack Effects</span>
              </button>

              <button
                class="category-tab"
                :class="{ active: selectedCategory === 'splash_effect' }"
                @click="handleSelectCategory('splash_effect')"
              >
                <span class="tab-icon">💥</span>
                <span class="tab-name">Splash Effects</span>
              </button>

              <button
                class="category-tab"
                :class="{ active: selectedCategory === 'potions' || selectedCategory === 'potion' }"
                @click="handleSelectCategory('potions')"
              >
                <span class="tab-icon">🧪</span>
                <span class="tab-name">Potions</span>
              </button>
            </nav>

            <!-- Search & Status Filter Sub-Bar -->
            <div class="showcase-filter-deck">
              <!-- Search Goods -->
              <div class="search-input-wrap">
                <span class="search-rune">🔍</span>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Search battle effects..."
                  class="shop-search-input"
                  @input="onSearchInput"
                />
                <button
                  v-if="searchQuery"
                  class="btn-clear-search"
                  @click="searchQuery = ''"
                >
                  ✕
                </button>
              </div>

              <!-- Ownership / Affordability Filter Pills -->
              <div class="status-filter-pills">
                <button
                  v-for="tab in filterTabs"
                  :key="tab.id"
                  class="filter-pill"
                  :class="{ active: activeFilter === tab.id }"
                  @click="handleSelectFilter(tab.id)"
                >
                  {{ tab.label }}
                </button>
              </div>
            </div>

            <!-- Items Loading State -->
            <div v-if="loading" class="showcase-loading-box">
              <div class="loading-spinner-rune"></div>
              <p class="loading-text">Inspecting Raven's Battle Relics...</p>
            </div>

            <!-- Items Showcase Viewport -->
            <div v-else class="showcase-scroll-viewport">
              <div v-if="filteredItems.length === 0" class="empty-inventory-box">
                <span class="empty-icon">🔮</span>
                <h3 class="empty-title">No Effects Found</h3>
                <p class="empty-sub">Raven has no battle effects matching your current filters.</p>
                <button class="btn-reset-filters" @click="resetFilters">Reset Filters</button>
              </div>

              <div v-else class="market-effects-container">
                <!-- ══ ATTACK EFFECTS SECTION ══ -->
                <section v-if="(!selectedCategory || selectedCategory === 'attack_effect') && attackEffectItems.length > 0" class="market-effect-section">
                  <div class="market-section-header">
                    <div class="header-left">
                      <span class="section-icon">⚔️</span>
                      <h2 class="section-title font-pixel">ATTACK EFFECTS</h2>
                    </div>
                    <span class="section-badge font-pixel">{{ userClass.toUpperCase() }} • {{ attackEffectItems.length }} EFFECTS</span>
                  </div>
                  <p class="section-sub font-pixel">Visual projectiles launched from your {{ userClass.toUpperCase() }} character toward the enemy.</p>

                  <div class="goods-grid">
                    <article
                      v-for="item in attackEffectItems"
                      :key="item.id"
                      class="shop-item-card"
                      :class="[
                        item.rarity || 'common',
                        {
                          'is-owned': isOwned(item.id),
                          'is-equipped': isEquipped(item.id),
                          'is-selected': selectedItem?.id === item.id
                        }
                      ]"
                      @click="openItemModal(item)"
                      @mouseenter="hoveredCardId = item.id; onItemHover(item)"
                      @mouseleave="hoveredCardId = null"
                    >
                      <div class="card-inner-halo"></div>

                      <div class="card-head">
                        <span class="slot-badge">{{ item.character_class ? item.character_class.toUpperCase() : 'ATTACK' }}</span>
                        <span class="rarity-tag" :class="item.rarity">{{ item.rarity }}</span>
                      </div>

                      <div class="item-visual-frame">
                        <CosmeticEffectPreview
                          :item="item"
                          :is-hovered="hoveredCardId === item.id"
                          :autoplay="selectedItem?.id === item.id"
                        />
                      </div>

                      <div class="item-title-meta">
                        <h3 class="item-name" :title="item.name">{{ item.name }}</h3>
                        <p class="item-category-label">{{ item.description }}</p>
                      </div>

                      <div class="card-footer">
                        <div v-if="!isOwned(item.id)" class="price-pill flex items-center gap-1">
                          <PixelEmoji emoji="coin" size="xs" />
                          <span class="price-val">{{ item.coin_cost === 0 ? 'FREE' : item.coin_cost.toLocaleString() }}</span>
                        </div>

                        <div class="action-wrap">
                          <span v-if="isEquipped(item.id)" class="badge-equipped flex items-center gap-1">
                            <span class="badge-dot">●</span> EQUIPPED
                          </span>
                          <button
                            v-else-if="isOwned(item.id)"
                            class="btn-card-action btn-card-owned"
                            @click.stop="quickEquip(item.id)"
                          >
                            EQUIP
                          </button>
                          <button
                            v-else
                            class="btn-card-action btn-card-buy"
                            :class="{ 'btn-short': (auth.user?.coins ?? auth.coins ?? 0) < item.coin_cost }"
                            @click.stop="openItemModal(item)"
                          >
                            BUY
                          </button>
                        </div>
                      </div>
                    </article>
                  </div>
                </section>

                <!-- ══ SPLASH EFFECTS SECTION ══ -->
                <section v-if="(!selectedCategory || selectedCategory === 'splash_effect') && splashEffectItems.length > 0" class="market-effect-section mt-8">
                  <div class="market-section-header">
                    <div class="header-left">
                      <span class="section-icon">💥</span>
                      <h2 class="section-title font-pixel">SPLASH EFFECTS</h2>
                    </div>
                    <span class="section-badge font-pixel">{{ splashEffectItems.length }} EFFECTS</span>
                  </div>
                  <p class="section-sub font-pixel">Visual impact detonation when your attack reaches the enemy.</p>

                  <div class="goods-grid">
                    <article
                      v-for="item in splashEffectItems"
                      :key="item.id"
                      class="shop-item-card"
                      :class="[
                        item.rarity || 'common',
                        {
                          'is-owned': isOwned(item.id),
                          'is-equipped': isEquipped(item.id),
                          'is-selected': selectedItem?.id === item.id
                        }
                      ]"
                      @click="openItemModal(item)"
                      @mouseenter="hoveredCardId = item.id; onItemHover(item)"
                      @mouseleave="hoveredCardId = null"
                    >
                      <div class="card-inner-halo"></div>

                      <div class="card-head">
                        <span class="slot-badge">SPLASH</span>
                        <span class="rarity-tag" :class="item.rarity">{{ item.rarity }}</span>
                      </div>

                      <div class="item-visual-frame">
                        <CosmeticEffectPreview
                          :item="item"
                          :is-hovered="hoveredCardId === item.id"
                          :autoplay="selectedItem?.id === item.id"
                        />
                      </div>

                      <div class="item-title-meta">
                        <h3 class="item-name" :title="item.name">{{ item.name }}</h3>
                        <p class="item-category-label">{{ item.description }}</p>
                      </div>

                      <div class="card-footer">
                        <div v-if="!isOwned(item.id)" class="price-pill flex items-center gap-1">
                          <PixelEmoji emoji="coin" size="xs" />
                          <span class="price-val">{{ item.coin_cost === 0 ? 'FREE' : item.coin_cost.toLocaleString() }}</span>
                        </div>

                        <div class="action-wrap">
                          <span v-if="isEquipped(item.id)" class="badge-equipped flex items-center gap-1">
                            <span class="badge-dot">●</span> EQUIPPED
                          </span>
                          <button
                            v-else-if="isOwned(item.id)"
                            class="btn-card-action btn-card-owned"
                            @click.stop="quickEquip(item.id)"
                          >
                            EQUIP
                          </button>
                          <button
                            v-else
                            class="btn-card-action btn-card-buy"
                            :class="{ 'btn-short': (auth.user?.coins ?? auth.coins ?? 0) < item.coin_cost }"
                            @click.stop="openItemModal(item)"
                          >
                            BUY
                          </button>
                        </div>
                      </div>
                    </article>
                  </div>
                </section>

                <!-- ══ POTIONS SECTION ══ -->
                <section v-if="(!selectedCategory || selectedCategory === 'potions' || selectedCategory === 'potion') && potionItems.length > 0" class="market-effect-section mt-8">
                  <div class="market-section-header">
                    <div class="header-left">
                      <span class="section-icon">🧪</span>
                      <h2 class="section-title font-pixel">POTIONS</h2>
                    </div>
                    <span class="section-badge font-pixel potion-badge">INSTANT VITALITY • {{ potionItems.length }} POTION</span>
                  </div>
                  <p class="section-sub font-pixel">Restorative draughts that immediately heal your persistent Adventure HP.</p>

                  <div class="goods-grid">
                    <article
                      v-for="item in potionItems"
                      :key="item.id"
                      class="shop-item-card potion-card"
                      :class="[
                        item.rarity || 'rare',
                        {
                          'is-selected': selectedItem?.id === item.id
                        }
                      ]"
                      @click="openItemModal(item)"
                      @mouseenter="hoveredCardId = item.id; onItemHover(item)"
                      @mouseleave="hoveredCardId = null"
                    >
                      <div class="card-inner-halo potion-halo"></div>

                      <div class="card-head">
                        <span class="slot-badge potion-slot">POTION</span>
                        <span class="rarity-tag" :class="item.rarity">{{ item.rarity }}</span>
                      </div>

                      <div class="item-visual-frame potion-frame">
                        <CosmeticEffectPreview
                          :item="item"
                          :is-hovered="hoveredCardId === item.id"
                          :autoplay="selectedItem?.id === item.id"
                        />
                      </div>

                      <div class="item-title-meta">
                        <h3 class="item-name font-pixel" :title="item.name">{{ item.name }}</h3>
                        <p class="item-category-label font-pixel">Restores 200 HP.</p>
                        <div class="potion-restore-pill font-pixel">+200 HEALTH</div>
                      </div>

                      <div class="card-footer">
                        <div class="price-pill flex items-center gap-1">
                          <PixelEmoji emoji="coin" size="xs" />
                          <span class="price-val">{{ item.coin_cost === 0 ? 'FREE' : item.coin_cost.toLocaleString() }}</span>
                        </div>

                        <div class="action-wrap">
                          <button
                            class="btn-card-action btn-card-buy btn-potion-buy font-pixel"
                            :class="{ 'btn-short': (auth.user?.coins ?? auth.coins ?? 0) < item.coin_cost }"
                            @click.stop="openItemModal(item)"
                          >
                            PURCHASE
                          </button>
                        </div>
                      </div>
                    </article>
                  </div>
                </section>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>

    <!-- ── Item Detail & Purchase Confirmation Modal ── -->
    <MarketItemDetailsModal
      v-if="selectedItem"
      :item="selectedItem"
      :is-owned="isOwned(selectedItem.id)"
      :is-equipped="isEquipped(selectedItem.id)"
      :user-coins="auth.user?.coins ?? auth.coins ?? 0"
      :equipped-list="equippedList"
      :processing="processingAction"
      @close="closeItemModal"
      @purchase="handlePurchase"
      @equip="handleEquip"
      @unequip="handleUnequip"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTransitionStore } from '../stores/transition'
import { useHealthStore } from '../stores/health'
import shopService from '../services/shopService'
import api from '../services/api'
import marketNpc from '../services/marketNpcService'
import battleAudio from '../services/battleAudio'
import { resolveAsset } from '../services/bossMapping'
import { normalizeCharacterClass } from '../config/cosmeticEffects'
import MarketNPC from '../components/market/MarketNPC.vue'
import MarketItemDetailsModal from '../components/market/MarketItemDetailsModal.vue'
import CosmeticEffectPreview from '../components/shop/CosmeticEffectPreview.vue'
import PixelEmoji from '../components/ui/PixelEmoji.vue'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()
const healthStore = useHealthStore()

// ── Background Asset ──
const bgImage = computed(() => resolveAsset('assets/game/market-shop-bg.jpg'))

// ── Shop Data State ──
const items = ref([])
const categories = ref([])
const inventory = ref([])
const equippedItems = ref({}) // Keyed by category_slug
const selectedCategory = ref(null)
const searchQuery = ref('')
const activeFilter = ref('all') // 'all' | 'owned' | 'unowned' | 'affordable'
const loading = ref(true)
const selectedItem = ref(null)
const hoveredCardId = ref(null)
const processingAction = ref(false)
const healthData = ref(null)
const adventureHp = computed(() => healthStore.currentHp)
const adventureMaxHp = computed(() => healthStore.maxHp)

// Filter tab options
const filterTabs = [
  { id: 'all', label: 'All' },
  { id: 'owned', label: 'Owned' },
  { id: 'unowned', label: 'Not Owned' },
  { id: 'affordable', label: 'Affordable' }
]

// Flat array of equipped items for avatar previews
const equippedList = computed(() => Object.values(equippedItems.value).filter(Boolean))

// ── Player Character & Class ──
const userCharacter = computed(() => auth.selectedCharacter || auth.user?.selected_character || null)
const userClass = computed(() => {
  const char = userCharacter.value
  const raw = char?.code || char?.class_type || auth.user?.character_class || 'mage'
  return normalizeCharacterClass(raw)
})

// ── Filtered Items List ──
const filteredItems = computed(() => {
  let list = items.value

  // Category filter
  if (selectedCategory.value) {
    list = list.filter(item => item.category_slug === selectedCategory.value)
  }

  // Character filter: Attack Effects are displayed STRICTLY based on user's character!
  list = list.filter(item => {
    if (item.character_class && item.character_class !== 'all') {
      return normalizeCharacterClass(item.character_class) === userClass.value
    }
    return true
  })

  // Search query filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(
      item =>
        item.name.toLowerCase().includes(q) ||
        (item.description && item.description.toLowerCase().includes(q)) ||
        (item.category_name && item.category_name.toLowerCase().includes(q))
    )
  }

  // Status pills filter
  const userCoins = auth.user?.coins ?? auth.coins ?? 0
  if (activeFilter.value === 'owned') {
    list = list.filter(item => isOwned(item.id))
  } else if (activeFilter.value === 'unowned') {
    list = list.filter(item => !isOwned(item.id))
  } else if (activeFilter.value === 'affordable') {
    list = list.filter(item => !isOwned(item.id) && item.coin_cost <= userCoins)
  }

  return list
})

const attackEffectItems = computed(() => {
  return filteredItems.value.filter(item => item.category_slug === 'attack_effect')
})

const splashEffectItems = computed(() => {
  return filteredItems.value.filter(item => item.category_slug === 'splash_effect')
})

const potionItems = computed(() => {
  return filteredItems.value.filter(
    item => item.category_slug === 'potions' || item.category_slug === 'potion' || item.type === 'potion'
  )
})

// ── Helpers ──
function isOwned(itemId) {
  const item = items.value.find(i => i.id === itemId)
  // Potions are instant consumables, not owned permanently
  if (item && (item.type === 'potion' || item.category_slug === 'potion' || item.category_slug === 'potions')) {
    return false
  }
  if (item && (item.is_default || item.coin_cost === 0)) return true
  return inventory.value.some(inv => inv.id === itemId)
}

function isEquipped(itemId) {
  return Object.values(equippedItems.value).some(e => e?.id === itemId)
}

function getCategoryIconSymbol(slug) {
  const map = {
    attack_effect: '⚔️',
    splash_effect: '💥',
    potions: '🧪',
    potion: '🧪'
  }
  return map[slug] || '✦'
}

function getCategoryShortName(slug) {
  const map = {
    attack_effect: 'Attack',
    splash_effect: 'Splash',
    potions: 'Potions',
    potion: 'Potion'
  }
  return map[slug] || 'Effect'
}

// ── Data Fetching ──
async function loadShop() {
  loading.value = true
  try {
    const res = await shopService.getItems()
    if (res.success) {
      // Focus strictly on attack_effect, splash_effect, and potions
      items.value = (res.items || []).filter(
        item => item.category_slug === 'attack_effect' || item.category_slug === 'splash_effect' || item.category_slug === 'potions' || item.category_slug === 'potion' || item.type === 'potion'
      )
      categories.value = [
        { id: 'attack_effect', slug: 'attack_effect', name: 'Attack Effects', icon: '⚔️' },
        { id: 'splash_effect', slug: 'splash_effect', name: 'Splash Effects', icon: '💥' },
        { id: 'potions', slug: 'potions', name: 'Potions', icon: '🧪' }
      ]
    }
  } catch (err) {
    console.error('Failed to load shop wares:', err)
  } finally {
    loading.value = false
  }
}

async function loadInventory() {
  try {
    const res = await shopService.getInventory()
    if (res.success) {
      inventory.value = res.inventory || []
      equippedItems.value = res.equipped || {}
    }
  } catch (err) {
    console.error('Failed to load player inventory:', err)
  }
}

async function loadHealth() {
  try {
    const res = await shopService.getHealth()
    if (res.success && res.health) {
      healthData.value = res.health
      if (auth.user) {
        auth.user.adventure_current_hp = res.health.current_hp
      }
    }
  } catch (err) {
    console.warn('Could not load adventure health:', err)
  }
}

onMounted(async () => {
  // Ensure user character is loaded
  if (!auth.selectedCharacter && !auth.user?.selected_character) {
    try {
      await auth.fetchUser()
    } catch (e) {
      console.warn('Could not fetch user profile:', e)
    }
  }

  // Story-aware merchant stage (server-derived; falls back to normal on failure)
  try {
    const storyRes = await api.get('/adventure/story-state')
    marketNpc.setStage(storyRes.data?.merchant_stage || 'normal')
  } catch (_) {
    marketNpc.setStage('normal')
  }

  // Greet player upon entering the Market
  marketNpc.react('enter_market')

  await Promise.all([loadShop(), loadInventory(), loadHealth()])
})

// ── Actions & NPC Interactions ──
function handleSelectCategory(catSlug) {
  selectedCategory.value = catSlug
  battleAudio.playSelect()
  marketNpc.react('category_change', { category: catSlug || 'all' })
}

function handleSelectFilter(filterId) {
  activeFilter.value = filterId
  battleAudio.playSelect()
  if (filterId === 'owned') {
    marketNpc.react('filter_owned')
  } else if (filterId === 'affordable') {
    marketNpc.react('filter_affordable')
  }
}

function onSearchInput() {
  if (searchQuery.value.length > 2) {
    marketNpc.react('search_items')
  }
}

function resetFilters() {
  selectedCategory.value = null
  activeFilter.value = 'all'
  searchQuery.value = ''
  battleAudio.playSelect()
}

function onItemHover(item) {
  marketNpc.react('view_item', { item })
}

function openItemModal(item) {
  selectedItem.value = item
  battleAudio.playCoin()
  marketNpc.react('select_item', { item })
}

function closeItemModal() {
  selectedItem.value = null
  battleAudio.playSelect()
}

// Quick Equip directly from card
async function quickEquip(itemId) {
  battleAudio.playSelect()
  await handleEquip(itemId)
}

// ── Transaction Handlers ──
async function handlePurchase(item) {
  const currentCoins = auth.user?.coins ?? auth.coins ?? 0
  const isPotion = Boolean(
    item.type === 'potion' ||
    item.category_slug === 'potion' ||
    item.category_slug === 'potions' ||
    item.effect_key === 'health-potion'
  )

  if (currentCoins < item.coin_cost) {
    marketNpc.react('insufficient_coins', { item })
    toast.error(isPotion ? "Even healing has a price. Insufficient Scholar Coins!" : "You don't have enough Scholar Coins!")
    return
  }

  processingAction.value = true
  try {
    const res = await shopService.purchase(item.id)
    if (res.success) {
      battleAudio.playPurchase()
      marketNpc.react('purchase_success', { item })
      if (isPotion) {
        toast.success(`Health Potion Purchased! +${res.healed_amount ?? 200} HP (${res.current_hp} / ${res.max_hp} HP)`)
        healthStore.updateFromPotion(res)
        if (!healthData.value) healthData.value = {}
        healthData.value.current_hp = res.current_hp
        healthData.value.max_hp = res.max_hp
        if (auth.user) {
          auth.user.adventure_current_hp = res.current_hp
          if (res.new_coin_balance !== undefined) {
            auth.user.coins = res.new_coin_balance
          }
        }
      } else {
        toast.success(`Acquired ${item.name}!`)
      }
      await auth.fetchUser()
      await loadInventory()
      selectedItem.value = null
    } else {
      if (res.error_code === 'ALREADY_FULL_HEALTH') {
        marketNpc.react('already_full', { item })
        toast.info(res.message || 'Your Adventure Health is already at maximum!')
      } else if (res.error_code === 'INSUFFICIENT_COINS') {
        marketNpc.react('insufficient_coins', { item })
        toast.error(res.message || 'Even healing has a price.')
      } else {
        marketNpc.react('purchase_failed')
        toast.error(res.message || 'Purchase failed')
      }
    }
  } catch (err) {
    const errData = err.response?.data
    if (errData?.error_code === 'ALREADY_FULL_HEALTH') {
      marketNpc.react('already_full', { item })
      toast.info(errData.message || 'Your Adventure Health is already at maximum!')
    } else if (errData?.error_code === 'INSUFFICIENT_COINS') {
      marketNpc.react('insufficient_coins', { item })
      toast.error(errData.message || 'Even healing has a price.')
    } else {
      marketNpc.react('purchase_failed')
      toast.error(errData?.message || 'Transaction could not be completed.')
    }
  } finally {
    processingAction.value = false
  }
}

async function handleEquip(itemId) {
  processingAction.value = true
  try {
    const res = await shopService.equip(itemId)
    if (res.success) {
      battleAudio.playEquip()
      const item = items.value.find(i => i.id === itemId)
      marketNpc.react('equip', { item })
      toast.success(res.message || 'Cosmetic equipped!')
      await auth.fetchUser()
      await loadInventory()
      if (selectedItem.value && selectedItem.value.id === itemId) {
        selectedItem.value = null
      }
    } else {
      toast.error(res.message || 'Equip failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Could not equip cosmetic.')
  } finally {
    processingAction.value = false
  }
}

async function handleUnequip(item) {
  processingAction.value = true
  try {
    const res = await shopService.unequip(item.category_slug)
    if (res.success) {
      battleAudio.playSelect()
      marketNpc.react('unequip', { item })
      toast.success(res.message || 'Cosmetic unequipped.')
      await auth.fetchUser()
      await loadInventory()
      if (selectedItem.value) {
        selectedItem.value = null
      }
    } else {
      toast.error(res.message || 'Unequip failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Could not unequip cosmetic.')
  } finally {
    processingAction.value = false
  }
}

function handleLeaveMarket() {
  marketNpc.react('leave_market')
  transitionStore.triggerTravel('/world', router)
}
</script>

<style scoped>
.market-view-root {
  position: relative;
  width: 100%;
  height: 100vh;
  max-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #06040b;
  overflow: hidden;
  user-select: none;
  padding-top: var(--hud-height, 56px);
  box-sizing: border-box;
}

/* ── Pixel Art Backdrop ── */
.market-backdrop-wrap {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.market-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center bottom;
  image-rendering: pixelated;
  opacity: 0.92;
  transform: scale(1.01);
}

.market-ambient-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 30% 60%, transparent 20%, rgba(6, 4, 12, 0.7) 70%, rgba(3, 2, 7, 0.96) 100%),
    linear-gradient(to top, rgba(4, 2, 8, 0.96) 0%, transparent 45%, rgba(4, 2, 8, 0.6) 100%);
}

.market-candle-glow {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.16) 0%, transparent 70%);
  pointer-events: none;
  animation: candleFlicker 3s infinite alternate ease-in-out;
}
.glow-counter {
  bottom: 25%;
  left: 20%;
  width: 320px;
  height: 320px;
}
.glow-shelves {
  top: 30%;
  left: 10%;
  width: 250px;
  height: 250px;
  animation-delay: 1.2s;
}

.market-window-glow {
  position: absolute;
  top: 25%;
  left: 65%;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.14) 0%, transparent 70%);
  pointer-events: none;
}

/* ── Stage Container ── */
.market-stage-container {
  position: relative;
  z-index: 10;
  flex: 1;
  min-height: 0;
  height: calc(100vh - var(--hud-height, 56px));
  max-height: calc(100vh - var(--hud-height, 56px));
  display: flex;
  flex-direction: column;
  padding: 0.6rem 1.5rem 0.85rem;
  width: 100%;
  max-width: 1600px;
  margin: 0 auto;
  box-sizing: border-box;
  overflow: hidden;
}

/* ── Top Nav Sub-Bar ── */
.market-nav-bar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.45rem 1.25rem;
  background: rgba(14, 10, 24, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 6px;
  backdrop-filter: blur(8px);
  margin-bottom: 0.65rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.8);
}

.btn-return-world {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.85rem;
  background: rgba(46, 16, 101, 0.5);
  border: 1px solid rgba(168, 85, 247, 0.4);
  border-radius: 4px;
  color: #e9d5ff;
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-return-world:hover {
  background: rgba(88, 28, 135, 0.8);
  border-color: #c084fc;
  color: #ffffff;
  transform: translateX(-2px);
}
.back-arrow {
  font-size: 1.1rem;
  line-height: 1;
}

.shop-title-emblem {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}
.emblem-star {
  color: #fbbf24;
  font-size: 0.85rem;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.8);
}
.shop-title-text {
  font-family: Georgia, 'Cinzel', serif;
  font-size: clamp(1rem, 2vw, 1.35rem);
  font-weight: 900;
  letter-spacing: 0.18em;
  color: #faf5ff;
  margin: 0;
  text-shadow: 0 0 14px rgba(168, 85, 247, 0.6), 0 2px 4px rgba(0, 0, 0, 0.9);
}

.market-header-pills {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.market-hp-pill {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.85rem;
  background: rgba(35, 12, 18, 0.9);
  border: 1px solid rgba(239, 68, 68, 0.45);
  border-radius: 4px;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
}
.hp-heart-icon {
  font-size: 0.85rem;
  display: inline-block;
}
.hp-pill-content {
  display: flex;
  flex-direction: column;
  line-height: 1.15;
}
.hp-amount {
  font-weight: 700;
  color: #fca5a5;
  font-size: 0.76rem;
  letter-spacing: 0.02em;
}
.hp-regen-tag {
  font-size: 0.58rem;
  color: #86efac;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-shadow: 0 0 4px rgba(34, 197, 94, 0.5);
}

.market-wallet-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.85rem;
  background: rgba(26, 18, 43, 0.9);
  border: 1px solid rgba(251, 191, 36, 0.4);
  border-radius: 4px;
  box-shadow: 0 0 10px rgba(251, 191, 36, 0.15);
}
.wallet-amount {
  font-family: Georgia, 'Cinzel', serif;
  font-weight: 800;
  color: #fbbf24;
  font-size: 0.95rem;
  letter-spacing: 0.05em;
}
.wallet-currency {
  font-size: 0.65rem;
  font-weight: 700;
  color: #e2d9cc;
  letter-spacing: 0.1em;
}

/* ── 2-Column Main Grid ── */
.market-main-grid {
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: minmax(280px, 340px) 1fr;
  gap: 1.25rem;
  align-items: stretch;
  overflow: hidden;
}

.market-npc-column {
  flex-shrink: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  overflow: hidden;
}

.market-showcase-column {
  min-width: 0;
  width: 100%;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* ── Grand Gothic Shop Frame ── */
.gothic-shop-frame {
  position: relative;
  background: rgba(14, 10, 24, 0.92);
  border: 2px solid #581c87;
  border-radius: 8px;
  box-shadow:
    0 12px 36px rgba(0, 0, 0, 0.95),
    0 0 24px rgba(88, 28, 135, 0.3),
    inset 0 0 20px rgba(26, 14, 46, 0.8);
  backdrop-filter: blur(10px);
  padding: 0.85rem 1.15rem 1rem;
  height: 100%;
  max-height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

/* Ornate Corner Accents */
.frame-edge {
  position: absolute;
  width: 8px;
  height: 8px;
  border-color: #fbbf24;
  pointer-events: none;
}
.edge-tl { top: -2px; left: -2px; border-top: 2px solid #fbbf24; border-left: 2px solid #fbbf24; }
.edge-tr { top: -2px; right: -2px; border-top: 2px solid #fbbf24; border-right: 2px solid #fbbf24; }
.edge-bl { bottom: -2px; left: -2px; border-bottom: 2px solid #fbbf24; border-left: 2px solid #fbbf24; }
.edge-br { bottom: -2px; right: -2px; border-bottom: 2px solid #fbbf24; border-right: 2px solid #fbbf24; }

/* ── Category Tabs Strip ── */
.category-tabs-strip {
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  padding-bottom: 0.6rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  margin-bottom: 0.6rem;
}

.category-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.85rem;
  background: rgba(26, 18, 43, 0.6);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 4px;
  color: #cbd5e1;
  font-family: inherit;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.category-tab:hover {
  background: rgba(46, 16, 101, 0.8);
  border-color: #a855f7;
  color: #ffffff;
  transform: translateY(-1px);
}
.category-tab.active {
  background: linear-gradient(180deg, #4c1d95 0%, #2e1065 100%);
  border-color: #fbbf24;
  color: #fbbf24;
  box-shadow: 0 0 14px rgba(251, 191, 36, 0.25), inset 0 0 8px rgba(251, 191, 36, 0.15);
}
.tab-icon {
  font-size: 0.85rem;
}

/* ── Filter / Search Deck ── */
.showcase-filter-deck {
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}

.search-input-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 340px;
  display: flex;
  align-items: center;
}
.search-rune {
  position: absolute;
  left: 0.75rem;
  font-size: 0.8rem;
  color: #a855f7;
  pointer-events: none;
}
.shop-search-input {
  width: 100%;
  padding: 0.4rem 2rem 0.4rem 2.2rem;
  background: rgba(6, 4, 12, 0.7);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 4px;
  color: #f1f5f9;
  font-family: inherit;
  font-size: 0.8rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.shop-search-input:focus {
  border-color: #c084fc;
  box-shadow: 0 0 10px rgba(192, 132, 252, 0.3);
}
.shop-search-input::placeholder {
  color: #64748b;
}
.btn-clear-search {
  position: absolute;
  right: 0.5rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.2rem;
}

.status-filter-pills {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.filter-pill {
  padding: 0.25rem 0.7rem;
  background: rgba(18, 12, 30, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  color: #94a3b8;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-pill:hover {
  color: #e2e8f0;
  border-color: rgba(168, 85, 247, 0.4);
}
.filter-pill.active {
  background: rgba(88, 28, 135, 0.6);
  border-color: #fbbf24;
  color: #fbbf24;
}

/* ── Showcase Scroll Viewport (ONLY PRODUCTS SCROLLABLE) ── */
.showcase-scroll-viewport {
  flex: 1;
  min-height: 0;
  width: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 0.5rem;
  overscroll-behavior: contain;
}

/* Custom Gothic Scrollbar for Products */
.showcase-scroll-viewport::-webkit-scrollbar {
  width: 7px;
}
.showcase-scroll-viewport::-webkit-scrollbar-track {
  background: rgba(10, 7, 18, 0.6);
  border-radius: 4px;
}
.showcase-scroll-viewport::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7c3aed 0%, #4c1d95 100%);
  border-radius: 4px;
  border: 1px solid rgba(168, 85, 247, 0.4);
}
.showcase-scroll-viewport::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #9333ea 0%, #6b21a8 100%);
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.6);
}

.goods-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 0.85rem;
  padding-bottom: 1rem;
}

/* ── Item Card ── */
.shop-item-card {
  position: relative;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #171128 0%, #0d0917 100%);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 0.75rem;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.8);
  cursor: pointer;
  transition: transform 0.22s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 0.22s, box-shadow 0.22s;
  overflow: hidden;
}

.shop-item-card:hover {
  transform: translateY(-3px);
  border-color: #c084fc;
  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.9),
    0 0 16px rgba(168, 85, 247, 0.35);
}

.shop-item-card.is-selected {
  border-color: #fbbf24;
  box-shadow: 0 0 20px rgba(251, 191, 36, 0.4);
}

/* Rarity Accents */
.shop-item-card.legendary {
  border-color: rgba(251, 191, 36, 0.5);
}
.shop-item-card.legendary:hover {
  border-color: #fbbf24;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.9), 0 0 22px rgba(251, 191, 36, 0.45);
}

.shop-item-card.epic {
  border-color: rgba(168, 85, 247, 0.5);
}
.shop-item-card.rare {
  border-color: rgba(59, 130, 246, 0.45);
}

/* Card Inner Glow */
.card-inner-halo {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 50px;
  background: radial-gradient(ellipse at 50% 0%, rgba(139, 92, 246, 0.15) 0%, transparent 80%);
  pointer-events: none;
}

/* Card Head */
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  z-index: 2;
}

.slot-badge {
  font-size: 0.65rem;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.rarity-tag {
  font-size: 0.6rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  padding: 0.1rem 0.4rem;
  border-radius: 2px;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid currentColor;
}
.rarity-tag.common { color: #94a3b8; }
.rarity-tag.rare { color: #60a5fa; }
.rarity-tag.epic { color: #c084fc; }
.rarity-tag.legendary { color: #fbbf24; }

/* Visual Preview Frame */
.item-visual-frame {
  position: relative;
  width: 100%;
  aspect-ratio: 1.1 / 1;
  background: radial-gradient(circle at center, rgba(30, 20, 50, 0.7) 0%, rgba(8, 5, 14, 0.9) 80%);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin-bottom: 0.65rem;
}

/* Item Title & Meta */
.item-title-meta {
  text-align: center;
  margin-bottom: 0.65rem;
  flex: 1;
}

.item-name {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 800;
  color: #faf5ff;
  margin: 0 0 0.2rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.04em;
}

.item-category-label {
  font-size: 0.68rem;
  color: #94a3b8;
  margin: 0;
}

/* Card Footer */
.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.price-pill {
  font-size: 0.75rem;
  font-weight: 800;
  color: #fbbf24;
}

.action-wrap {
  margin-left: auto;
}

.btn-card-action {
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  font-family: inherit;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  cursor: pointer;
  transition: all 0.18s ease;
  border: 1px solid transparent;
}

.btn-card-buy {
  background: linear-gradient(180deg, #6b21a8 0%, #4c1d95 100%);
  border-color: #a855f7;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(107, 33, 168, 0.4);
}
.btn-card-buy:hover {
  background: linear-gradient(180deg, #7e22ce 0%, #581c87 100%);
  box-shadow: 0 4px 12px rgba(168, 85, 247, 0.6);
  transform: translateY(-1px);
}
.btn-card-buy.btn-short {
  border-color: #713f12;
  color: #fde047;
}

.btn-card-owned {
  background: rgba(16, 185, 129, 0.12);
  border-color: rgba(168, 85, 247, 0.35);
  color: #cbd5e1;
}
.btn-card-owned:hover {
  background: rgba(16, 185, 129, 0.25);
  border-color: #34d399;
  color: #ecfdf5;
}

.badge-equipped {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #34d399;
  padding: 0.2rem 0.5rem;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 3px;
  box-shadow: 0 0 10px rgba(52, 211, 153, 0.2);
}
.badge-dot {
  font-size: 0.55rem;
  color: #10b981;
}

/* Loading & Empty States */
.showcase-loading-box,
.empty-inventory-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1.5rem;
  text-align: center;
}

.loading-spinner-rune {
  width: 44px;
  height: 44px;
  border: 3px solid #3b1d60;
  border-top-color: #fbbf24;
  border-radius: 50%;
  animation: spinRune 0.85s linear infinite;
  margin-bottom: 1rem;
}
.loading-text {
  font-family: Georgia, 'Cinzel', serif;
  color: #fbbf24;
  font-size: 0.9rem;
  letter-spacing: 0.1em;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}
.empty-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 1.15rem;
  color: #f1f5f9;
  margin: 0 0 0.35rem;
}
.empty-sub {
  font-size: 0.8rem;
  color: #94a3b8;
  margin: 0 0 1.25rem;
}

.btn-reset-filters {
  padding: 0.45rem 1.25rem;
  background: rgba(46, 16, 101, 0.7);
  border: 1px solid #a855f7;
  border-radius: 4px;
  color: #f1f5f9;
  font-family: inherit;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-reset-filters:hover {
  background: #6b21a8;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.5);
}

@keyframes spinRune {
  to { transform: rotate(360deg); }
}

@keyframes candleFlicker {
  0% { transform: scale(0.96); opacity: 0.14; }
  100% { transform: scale(1.05); opacity: 0.22; }
}

/* ── Market Effect Sections ── */
.market-effects-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.market-effect-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.market-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.85rem;
  background: linear-gradient(90deg, rgba(30, 27, 75, 0.75) 0%, rgba(15, 23, 42, 0.4) 100%);
  border-left: 3px solid #fbbf24;
  border-radius: 4px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-icon {
  font-size: 1.1rem;
}

.section-title {
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #fbbf24;
  margin: 0;
}

.section-badge {
  font-size: 0.65rem;
  color: #94a3b8;
  letter-spacing: 0.08em;
}

.section-sub {
  font-size: 0.68rem;
  color: #cbd5e1;
  margin: 0;
  padding-left: 0.5rem;
  opacity: 0.85;
}

/* ── Responsive Behavior ── */
@media (max-width: 1024px) {
  .goods-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  }
}

@media (max-width: 900px) {
  .market-view-root {
    height: auto;
    max-height: none;
    overflow-y: auto;
  }
  .market-stage-container {
    height: auto;
    max-height: none;
    overflow: visible;
  }
  .market-main-grid {
    grid-template-columns: 1fr;
    overflow: visible;
  }
  .market-npc-column {
    position: static;
    height: auto;
    overflow: visible;
  }
  .market-showcase-column {
    height: auto;
    overflow: visible;
  }
  .gothic-shop-frame {
    height: auto;
    max-height: none;
    overflow: visible;
  }
  .showcase-scroll-viewport {
    max-height: 520px;
    overflow-y: auto;
  }
}

@media (max-width: 640px) {
  .market-stage-container {
    padding: 0.75rem 0.75rem 2.5rem;
  }
  .market-nav-bar {
    padding: 0.5rem 0.75rem;
  }
  .back-text {
    display: none;
  }
  .goods-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 0.65rem;
  }
  .category-tabs-strip {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 0.65rem;
  }
  .category-tab {
    white-space: nowrap;
  }
}

/* ── Potion Wares Styling ── */
.potion-badge {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
}

.potion-card {
  border-color: rgba(239, 68, 68, 0.35) !important;
  background: linear-gradient(180deg, rgba(30, 10, 15, 0.9) 0%, rgba(15, 6, 9, 0.95) 100%) !important;
}

.potion-card:hover {
  border-color: rgba(248, 113, 113, 0.8) !important;
  box-shadow: 0 0 25px rgba(239, 68, 68, 0.35), inset 0 0 15px rgba(239, 68, 68, 0.15) !important;
}

.potion-halo {
  background: radial-gradient(circle at center, rgba(239, 68, 68, 0.25) 0%, transparent 70%);
}

.potion-slot {
  background: rgba(220, 38, 38, 0.25) !important;
  border-color: rgba(239, 68, 68, 0.5) !important;
  color: #fca5a5 !important;
}

.potion-restore-pill {
  display: inline-block;
  margin-top: 4px;
  font-size: 0.68rem;
  letter-spacing: 0.05em;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(220, 38, 38, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.45);
  color: #fecaca;
  font-weight: 700;
  text-shadow: 0 0 6px rgba(239, 68, 68, 0.6);
}

.btn-potion-buy {
  background: linear-gradient(180deg, #dc2626 0%, #991b1b 100%) !important;
  border-color: #ef4444 !important;
  color: #ffffff !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.btn-potion-buy:hover:not(:disabled) {
  background: linear-gradient(180deg, #ef4444 0%, #b91c1c 100%) !important;
  box-shadow: 0 0 16px rgba(239, 68, 68, 0.6) !important;
}
</style>
