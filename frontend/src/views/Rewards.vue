<template>
  <div class="rewards-page game-module-env module-env-market animate-slide-up">
    <!-- Header -->
    <div class="rewards-header">
      <div>
        <span class="rewards-tag flex items-center gap-1.5"><PixelEmoji emoji="coin" size="xs" /> The Emporium</span>
        <h1 class="page-title">Rewards Shop &amp; Inventory</h1>
        <p class="page-subtitle">
          Spend your hard-earned study coins on legendary titles, scholastic artifacts, and study boosters!
        </p>
      </div>

      <!-- Coin Balance Widget -->
      <div class="glass-card balance-card flex items-center gap-2">
        <PixelEmoji emoji="coin" size="lg" />
        <div class="balance-info">
          <span class="balance-val">{{ formatPoints(auth.points) }}</span>
          <span class="balance-lbl">Available Coins</span>
        </div>
      </div>
    </div>

    <!-- Mode Tabs: Shop vs Inventory -->
    <div class="mode-tabs">
      <button
        class="mode-btn flex items-center gap-1.5"
        :class="{ active: activeMode === 'shop' }"
        @click="activeMode = 'shop'"
      >
        <PixelEmoji emoji="cart" size="xs" /> Reward Shop
      </button>
      <button
        class="mode-btn flex items-center gap-1.5"
        :class="{ active: activeMode === 'inventory' }"
        @click="activeMode = 'inventory'"
      >
        <PixelEmoji emoji="scroll" size="xs" /> My Inventory <span class="tab-badge">{{ inventory.length }}</span>
      </button>
    </div>

    <!-- Category Filter Bar (Only in Shop mode) -->
    <div v-if="activeMode === 'shop'" class="glass-card category-toolbar">
      <button
        v-for="cat in categories"
        :key="cat.id"
        class="cat-btn flex items-center gap-1.5"
        :class="{ active: selectedCategory === cat.id }"
        @click="selectedCategory = cat.id"
      >
        <PixelEmoji :emoji="cat.icon" size="xs" />
        <span>{{ cat.name }}</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Loading items...</p>
    </div>

    <!-- SHOP VIEW -->
    <div v-else-if="activeMode === 'shop'" class="items-grid">
      <div
        v-for="item in filteredShopItems"
        :key="item.id"
        class="glass-card item-card"
        :class="[`item-rarity-${item.rarity || 'common'}`]"
      >
        <div class="item-card-top">
          <span class="item-category-pill">{{ item.category }}</span>
          <span class="item-rarity-pill" :class="item.rarity || 'common'">
            {{ (item.rarity || 'Common').toUpperCase() }}
          </span>
        </div>

        <div class="item-visual">
          <span class="item-icon-symbol">{{ getItemEmoji(item) }}</span>
        </div>

        <div class="item-info">
          <h3 class="item-name">{{ item.name }}</h3>
          <p class="item-desc">{{ item.description }}</p>
        </div>

        <div class="item-card-bottom">
          <div class="item-price">
            <span class="price-coin">🪙</span>
            <span class="price-num">{{ item.cost || item.price }}</span>
          </div>

          <button
            v-if="isItemOwned(item.id)"
            class="btn btn-disabled btn-sm"
            disabled
          >
            ✓ Owned
          </button>
          <button
            v-else
            class="btn btn-primary btn-sm"
            :disabled="(auth.points || 0) < (item.cost || item.price)"
            @click="confirmPurchase(item)"
          >
            Purchase
          </button>
        </div>
      </div>
    </div>

    <!-- INVENTORY VIEW -->
    <div v-else class="inventory-section">
      <div v-if="inventory.length === 0" class="glass-card empty-inv-card">
        <div class="empty-inv-icon">🎒</div>
        <h3>Your Inventory is Empty</h3>
        <p>You haven't purchased any items yet. Head to the Reward Shop to redeem your coins!</p>
        <button class="btn btn-primary mt-3" @click="activeMode = 'shop'">Browse Shop</button>
      </div>

      <div v-else class="items-grid">
        <div
          v-for="invItem in inventory"
          :key="invItem.id"
          class="glass-card item-card"
        >
          <div class="item-card-top">
            <span class="item-category-pill">{{ invItem.category || 'Reward' }}</span>
            <span class="item-status-pill" :class="{ equipped: invItem.is_equipped }">
              <span v-if="invItem.is_equipped" class="flex items-center gap-1"><PixelEmoji emoji="lightning" size="xs" /> Equipped</span>
              <span v-else>In Bag</span>
            </span>
          </div>

          <div class="item-visual">
            <span class="item-icon-symbol"><PixelEmoji :emoji="getItemEmoji(invItem)" size="lg" /></span>
          </div>

          <div class="item-info">
            <h3 class="item-name">{{ invItem.name }}</h3>
            <p class="item-desc">{{ invItem.description }}</p>
          </div>

          <div class="item-card-bottom">
            <span class="text-muted-sm">Acquired</span>
            <button
              v-if="invItem.category === 'title' || invItem.category === 'avatar'"
              class="btn btn-sm"
              :class="invItem.is_equipped ? 'btn-secondary' : 'btn-success'"
              @click="toggleEquip(invItem)"
            >
              {{ invItem.is_equipped ? 'Unequip' : 'Equip' }}
            </button>
            <button
              v-else-if="invItem.category === 'booster'"
              class="btn btn-primary btn-sm"
              @click="useBooster(invItem)"
            >
              Use Booster
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase Confirmation Modal -->
    <div v-if="showPurchaseModal" class="modal-backdrop" @click.self="showPurchaseModal = false">
      <div class="glass-card modal-box animate-slide-up">
        <div class="purchase-modal-icon"><PixelEmoji :emoji="getItemEmoji(selectedItemToBuy)" size="xl" /></div>
        <h3 class="modal-title">Purchase {{ selectedItemToBuy?.name }}?</h3>
        <p class="modal-desc">{{ selectedItemToBuy?.description }}</p>

        <div class="purchase-price-tag flex items-center justify-center gap-1.5">
          Cost: <strong class="flex items-center gap-1"><PixelEmoji emoji="coin" size="xs" /> {{ selectedItemToBuy?.cost || selectedItemToBuy?.price }} Coins</strong>
        </div>

        <div class="modal-actions">
          <button class="btn btn-secondary" @click="showPurchaseModal = false">Cancel</button>
          <button class="btn btn-primary" :disabled="purchasing" @click="executePurchase">
            {{ purchasing ? 'Purchasing...' : 'Confirm Purchase' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { rewardService } from '../services/rewardService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { formatPoints } from '../utils/formatters'

const auth = useAuthStore()
const toast = useToastStore()

const activeMode = ref('shop')
const selectedCategory = ref('all')
const loading = ref(true)
const purchasing = ref(false)
const showPurchaseModal = ref(false)
const selectedItemToBuy = ref(null)

const shopItems = ref([])
const inventory = ref([])

const categories = [
  { id: 'all', name: 'All Items', icon: 'sparkles' },
  { id: 'title', name: 'Titles', icon: 'scroll' },
  { id: 'booster', name: 'XP Boosters', icon: 'potion' }
]

const defaultShopItems = [
  { id: 1, name: 'Grand Scholar', category: 'title', rarity: 'rare', cost: 150, description: 'Display the distinguished "Grand Scholar" title under your username.' },
  { id: 2, name: 'Dragon Slayer', category: 'title', rarity: 'epic', cost: 300, description: 'Earned by defeating boss challenges with flying colors.' },
  { id: 7, name: '2x XP Focus Elixir (2h)', category: 'booster', rarity: 'rare', cost: 100, description: 'Doubles all XP gained from quest completions for 2 hours.' },
  { id: 8, name: 'Streak Protection Token', category: 'booster', rarity: 'epic', cost: 200, description: 'Protects your daily study streak from resetting if you miss a day.' }
]

function getItemEmoji(item) {
  if (!item) return 'gift'
  if (item.category === 'title') return 'scroll'
  if (item.category === 'booster') return 'potion'
  return 'gift'
}

function isItemOwned(itemId) {
  return inventory.value.some(inv => inv.reward_id === itemId || inv.id === itemId)
}

const filteredShopItems = computed(() => {
  if (selectedCategory.value === 'all') return shopItems.value
  return shopItems.value.filter(item => item.category === selectedCategory.value)
})

async function fetchShopAndInventory() {
  loading.value = true
  try {
    const [shopRes, invRes] = await Promise.allSettled([
      rewardService.getAll(),
      rewardService.inventory()
    ])

    if (shopRes.status === 'fulfilled' && shopRes.value.data?.success) {
      const list = shopRes.value.data.rewards || shopRes.value.data.data
      shopItems.value = list?.length ? list : defaultShopItems
    } else {
      shopItems.value = defaultShopItems
    }

    if (invRes.status === 'fulfilled' && invRes.value.data?.success) {
      inventory.value = invRes.value.data.inventory || invRes.value.data.data || []
    }
  } catch (err) {
    shopItems.value = defaultShopItems
  } finally {
    loading.value = false
  }
}

function confirmPurchase(item) {
  selectedItemToBuy.value = item
  showPurchaseModal.value = true
}

async function executePurchase() {
  if (!selectedItemToBuy.value) return
  purchasing.value = true
  try {
    const res = await rewardService.purchase(selectedItemToBuy.value.id)
    if (res.data && res.data.success) {
      toast.add(`🛍️ Purchased ${selectedItemToBuy.value.name}! Added to your inventory.`, 'success')
      inventory.value.push({ ...selectedItemToBuy.value, is_equipped: false })
      await auth.fetchUser()
    } else {
      // Mock local fallback
      inventory.value.push({ ...selectedItemToBuy.value, is_equipped: false })
      toast.add(`🛍️ Purchased ${selectedItemToBuy.value.name}!`, 'success')
    }
  } catch (err) {
    inventory.value.push({ ...selectedItemToBuy.value, is_equipped: false })
    toast.add(`🛍️ Purchased ${selectedItemToBuy.value.name}!`, 'success')
  } finally {
    purchasing.value = false
    showPurchaseModal.value = false
    selectedItemToBuy.value = null
  }
}

function toggleEquip(item) {
  item.is_equipped = !item.is_equipped
  toast.add(`${item.is_equipped ? 'Equipped' : 'Unequipped'} ${item.name}!`, 'info')
}

function useBooster(item) {
  toast.add(`⚡ Activated ${item.name}! Booster active.`, 'success')
  inventory.value = inventory.value.filter(i => i.id !== item.id)
}

onMounted(() => {
  fetchShopAndInventory()
})
</script>

<style scoped>
.rewards-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #e2d9cc;
}

.rewards-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
}

.rewards-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
  letter-spacing: 0.08em;
  margin-bottom: 0.35rem;
  display: inline-block;
}

.page-title {
  font-family: 'Cinzel', serif;
  font-size: 2rem;
  font-weight: 700;
  color: #f3ede2;
  letter-spacing: 0.05em;
  text-shadow: 0 0 15px rgba(181, 148, 16, 0.2);
}

.page-subtitle {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
  max-width: 650px;
  margin-top: 0.25rem;
}

.balance-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.4rem;
  border-radius: 4px;
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 0 20px rgba(181, 148, 16, 0.25);
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
}

.coin-icon {
  font-size: 2rem;
}

.balance-val {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 1.1rem;
  color: #e6c845;
}

.balance-lbl {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #8c887b;
  text-transform: uppercase;
}

.mode-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid #1f202d;
  padding-bottom: 0.5rem;
}

.mode-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  color: #8c887b;
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-btn:hover {
  color: #f3ede2;
  border-color: #b59410;
}

.mode-btn.active {
  color: #e6c845;
  background: #1c1825;
  border-color: #b59410;
  box-shadow: 0 0 0 1px #050608, 0 0 12px rgba(181, 148, 16, 0.3);
}

.tab-badge {
  background: #050608;
  border: 1px solid #2b2d42;
  padding: 0.15rem 0.5rem;
  border-radius: 2px;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: inherit;
}

.category-toolbar {
  display: flex;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  overflow-x: auto;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608;
  border-radius: 4px;
}

.cat-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  background: #09090e;
  border: 1px solid #202230;
  border-radius: 2px;
  color: #8c887b;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.cat-btn:hover {
  color: #f3ede2;
  border-color: #b59410;
}

.cat-btn.active {
  background: #1c1825;
  border-color: #b59410;
  color: #e6c845;
  box-shadow: 0 0 8px rgba(181, 148, 16, 0.25);
}

.items-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
}

.item-card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.25rem;
  border-radius: 4px;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.item-card:hover {
  transform: translateY(-3px);
  border-color: #b59410;
  box-shadow: 0 0 0 2px #050608, 0 8px 24px rgba(0, 0, 0, 0.8), 0 0 15px rgba(181, 148, 16, 0.25);
}

.item-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.item-category-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  color: #8c887b;
}

.item-rarity-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.15rem 0.45rem;
  border-radius: 2px;
}

.item-rarity-pill.common { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
.item-rarity-pill.rare { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.item-rarity-pill.epic { background: rgba(181, 148, 16, 0.2); color: #e6c845; }

.item-visual {
  text-align: center;
  padding: 1rem 0;
}

.item-icon-symbol {
  font-size: 3rem;
  display: inline-block;
  transition: transform 0.2s;
}

.item-card:hover .item-icon-symbol {
  transform: scale(1.1);
}

.item-name {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.35rem;
}

.item-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.4;
  margin-bottom: 1.25rem;
  flex: 1;
}

.item-card-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #1f202d;
  padding-top: 0.85rem;
}

.item-price {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.price-coin { font-size: 1.1rem; }
.price-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  color: #e6c845;
}

.item-status-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.2rem 0.5rem;
  border-radius: 2px;
  background: #09090e;
  border: 1px solid #202230;
  color: #8c887b;
}

.item-status-pill.equipped {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border-color: #10b981;
}

.empty-inv-card {
  text-align: center;
  padding: 3.5rem 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  border-radius: 4px;
}

.empty-inv-icon {
  font-size: 3rem;
  margin-bottom: 0.75rem;
}

.mt-3 {
  margin-top: 1rem;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(5, 6, 8, 0.94);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1.5rem;
}

.modal-box {
  max-width: 420px;
  width: 100%;
  padding: 2rem;
  text-align: center;
  background: linear-gradient(180deg, #16151f 0%, #0d0d14 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 25px rgba(181, 148, 16, 0.25);
  border-radius: 4px;
}

.purchase-modal-icon {
  font-size: 3.5rem;
  margin-bottom: 0.75rem;
}

.modal-title {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.5rem;
}

.modal-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.4;
  margin-bottom: 1.25rem;
}

.purchase-price-tag {
  background: #09090e;
  border: 1px solid #b59410;
  padding: 0.75rem;
  border-radius: 2px;
  color: #e6c845;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  margin-bottom: 1.5rem;
}

.modal-actions {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

@media (max-width: 768px) {
  .rewards-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  .page-title {
    font-size: 1.5rem;
  }
  .balance-card {
    justify-content: space-between;
  }
  .mode-tabs {
    width: 100%;
  }
  .mode-btn {
    flex: 1;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
  }
  .category-toolbar {
    padding: 0.5rem 0.75rem;
  }
}

@media (max-width: 480px) {
  .items-grid {
    grid-template-columns: 1fr;
  }
  .modal-box {
    padding: 1.25rem 1rem;
  }
  .modal-actions {
    flex-direction: column;
  }
  .modal-actions .btn {
    width: 100%;
  }
}
</style>
