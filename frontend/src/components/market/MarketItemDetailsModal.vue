<!--
  MarketItemDetailsModal.vue
  Gothic RPG-style detail and purchase confirmation modal for the StudyQuest Market.
  Displays live visual previews, coin comparison, ownership state, and triggers NPC reactions.
-->
<template>
  <div v-if="item" class="item-modal-backdrop" @click.self="emit('close')">
    <div class="item-modal-box animate-scale-up" :class="item.rarity">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-tag">
          <span class="category-pill">{{ getCategoryName(item.category_slug) }}</span>
          <span class="rarity-badge" :class="item.rarity">{{ item.rarity }}</span>
        </div>
        <button class="btn-close-modal" @click="emit('close')">✕</button>
      </div>

      <!-- Item Preview Stage -->
      <div class="preview-stage-container">
        <!-- Attack & Splash Effect Preview -->
        <div v-if="isEffectType" class="effect-modal-stage">
          <div class="effect-modal-preview-box">
            <CosmeticEffectPreview :item="item" :autoplay="true" />
          </div>
          <p class="battle-note font-pixel">
            {{ item.category_slug === 'attack_effect' ? '⚔️ Visual attack effect travels toward target upon attack' : '💥 Visual splash effect detonates upon impact' }}
          </p>
        </div>

        <!-- Avatar Preview for Skin, Aura, Back Effect -->
        <div v-else-if="!isBattleOnly" class="avatar-stage">
          <AvatarImg
            :preview-item="item"
            :equipped="equippedList"
            :size="140"
          />
          <div class="preview-ground-shadow"></div>
        </div>

        <!-- Combat Demo for Hit & Finisher Effects -->
        <div v-else class="battle-preview-stage">
          <div class="demo-arena" :class="item.effect_key">
            <div class="fighter-left">
              <AvatarImg :size="64" />
              <span class="tag-you">YOU</span>
            </div>
            <div class="demo-vs-text">VS</div>
            <div class="fighter-right">
              <div class="opp-box">⚔️</div>
              <span class="tag-opp">ENEMY</span>
            </div>
            <!-- Hit Effect Demo -->
            <div v-if="item.category_slug === 'hit_effect'" class="hit-burst-anim" :class="item.effect_key"></div>
            <!-- Finisher Effect Demo -->
            <div v-if="item.category_slug === 'finisher_effect'" class="finisher-burst-anim" :class="item.effect_key">
              <span class="finisher-banner">VICTORY</span>
            </div>
          </div>
          <p class="battle-note">Activates during multiplayer battle strikes</p>
        </div>
      </div>

      <!-- Item Information -->
      <div class="item-info-section">
        <h2 class="item-title">{{ item.name }}</h2>
        <p class="item-desc">{{ item.description || 'A mystical artifact sourced from the depths of the Mindrealm.' }}</p>
      </div>

      <!-- Economy & Ownership Ledger -->
      <div class="ledger-box">
        <div class="ledger-row">
          <span class="ledger-label">PRICE</span>
          <div class="ledger-value price-val flex items-center gap-1.5">
            <PixelEmoji emoji="coin" size="xs" />
            <span>{{ item.coin_cost === 0 ? 'FREE' : item.coin_cost.toLocaleString() }}</span>
          </div>
        </div>
        <div class="ledger-row">
          <span class="ledger-label">STATUS</span>
          <span v-if="isPotion" class="status-pill potion-status">
            +200 HP RECOVERY
          </span>
          <span v-else class="status-pill" :class="{ 'owned': isOwned, 'equipped': isEquipped }">
            {{ isEquipped ? 'EQUIPPED' : (isOwned ? 'OWNED' : 'AVAILABLE') }}
          </span>
        </div>
        <div v-if="!isOwned || isPotion" class="ledger-row coins-status">
          <span class="ledger-label">YOUR COINS</span>
          <span class="user-coins-val" :class="{ 'short': userCoins < item.coin_cost }">
            {{ userCoins.toLocaleString() }}
            <span v-if="userCoins < item.coin_cost" class="short-diff">
              (-{{ (item.coin_cost - userCoins).toLocaleString() }})
            </span>
          </span>
        </div>
      </div>

      <!-- Modal Action Buttons -->
      <div class="modal-actions">
        <!-- Potion state (Instant consumable) -->
        <button
          v-if="isPotion"
          class="btn-action btn-buy-confirm btn-potion-confirm"
          :disabled="userCoins < item.coin_cost || processing"
          @click="emit('purchase', item)"
        >
          <span v-if="processing">Brewing Vitality...</span>
          <span v-else-if="userCoins < item.coin_cost">Insufficient Coins ({{ item.coin_cost }} needed)</span>
          <span v-else>Purchase Health Potion (+200 HP)</span>
        </button>

        <!-- Equipped state -->
        <button
          v-else-if="isEquipped"
          class="btn-action btn-unequip"
          :disabled="processing"
          @click="emit('unequip', item)"
        >
          {{ processing ? 'Attuning...' : 'Unequip Cosmetic' }}
        </button>

        <!-- Owned but not equipped -->
        <button
          v-else-if="isOwned"
          class="btn-action btn-equip"
          :disabled="processing"
          @click="emit('equip', item.id)"
        >
          {{ processing ? 'Attuning...' : 'Equip Cosmetic' }}
        </button>

        <!-- Unowned: Buy button -->
        <button
          v-else
          class="btn-action btn-buy-confirm"
          :disabled="userCoins < item.coin_cost || processing"
          @click="emit('purchase', item)"
        >
          <span v-if="processing">Transacting with Raven...</span>
          <span v-else-if="userCoins < item.coin_cost">Insufficient Coins</span>
          <span v-else>Purchase for {{ item.coin_cost === 0 ? 'FREE' : item.coin_cost }} Coins</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AvatarImg from '../AvatarImg.vue'
import PixelEmoji from '../ui/PixelEmoji.vue'
import CosmeticEffectPreview from '../shop/CosmeticEffectPreview.vue'

const props = defineProps({
  item: { type: Object, default: null },
  isOwned: { type: Boolean, default: false },
  isEquipped: { type: Boolean, default: false },
  userCoins: { type: Number, default: 0 },
  equippedList: { type: Array, default: () => [] },
  processing: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'purchase', 'equip', 'unequip'])

const isPotion = computed(() => {
  return props.item && (
    props.item.type === 'potion' ||
    ['potion', 'potions'].includes(props.item.category_slug) ||
    props.item.effect_key === 'health-potion'
  )
})

const isEffectType = computed(() => {
  return props.item && (
    ['attack_effect', 'splash_effect', 'potion', 'potions'].includes(props.item.category_slug) ||
    props.item.type === 'potion' ||
    isPotion.value
  )
})

const isBattleOnly = computed(() => {
  return props.item && ['hit_effect', 'finisher_effect'].includes(props.item.category_slug)
})

function getCategoryName(slug) {
  const map = {
    attack_effect: 'Attack Effect',
    splash_effect: 'Splash Effect',
    potions: 'Potions',
    potion: 'Potion',
    character_skin: 'Character Skin',
    aura: 'Aura',
    back_effect: 'Back Effect',
    hit_effect: 'Hit Effect',
    finisher_effect: 'Finisher Effect'
  }
  return map[slug] || slug
}
</script>

<style scoped>
.item-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(3, 2, 8, 0.85);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.item-modal-box {
  position: relative;
  width: 100%;
  max-width: 480px;
  background: linear-gradient(180deg, #151024 0%, #0d0917 100%);
  border: 2px solid #7c3aed;
  border-radius: 8px;
  box-shadow:
    0 16px 40px rgba(0, 0, 0, 0.95),
    0 0 30px rgba(124, 58, 237, 0.25),
    inset 0 0 20px rgba(139, 92, 246, 0.1);
  padding: 1.5rem;
  color: #f1f5f9;
}

.item-modal-box.legendary {
  border-color: #fbbf24;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 35px rgba(251, 191, 36, 0.35);
}

.item-modal-box.epic {
  border-color: #a855f7;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 30px rgba(168, 85, 247, 0.3);
}

.item-modal-box.rare {
  border-color: #3b82f6;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 30px rgba(59, 130, 246, 0.25);
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.header-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.category-pill {
  font-size: 0.7rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #c084fc;
  font-weight: 700;
}

.rarity-badge {
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  padding: 0.15rem 0.55rem;
  border-radius: 3px;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid currentColor;
}
.rarity-badge.common { color: #94a3b8; }
.rarity-badge.rare { color: #60a5fa; }
.rarity-badge.epic { color: #c084fc; }
.rarity-badge.legendary { color: #fbbf24; }

.btn-close-modal {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.15rem;
  cursor: pointer;
  padding: 0.25rem;
  line-height: 1;
  transition: color 0.2s ease;
}
.btn-close-modal:hover {
  color: #f1f5f9;
}

/* Preview Stage */
.preview-stage-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: radial-gradient(circle at center, rgba(46, 16, 101, 0.4) 0%, rgba(10, 7, 18, 0.8) 75%);
  border: 1px solid rgba(139, 92, 246, 0.25);
  border-radius: 6px;
  margin-bottom: 1.25rem;
  min-height: 180px;
}

.effect-modal-stage {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.effect-modal-preview-box {
  width: 180px;
  height: 120px;
  background: rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.preview-ground-shadow {
  width: 120px;
  height: 14px;
  border-radius: 50%;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.7) 0%, transparent 70%);
  margin-top: 4px;
}

.battle-preview-stage {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.demo-arena {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-around;
  width: 100%;
  max-width: 320px;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

.demo-vs-text {
  font-family: Georgia, 'Cinzel', serif;
  font-weight: 800;
  color: #f59e0b;
  font-size: 0.85rem;
}

.fighter-left, .fighter-right {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.opp-box {
  width: 64px;
  height: 64px;
  border-radius: 4px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px dashed rgba(239, 68, 68, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.tag-you, .tag-opp {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #94a3b8;
}

.battle-note {
  font-size: 0.7rem;
  color: #94a3b8;
  margin: 0.5rem 0 0;
  text-align: center;
}

/* Item Info */
.item-info-section {
  text-align: center;
  margin-bottom: 1.25rem;
}

.item-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #faf5ff;
  margin: 0 0 0.35rem;
}

.item-desc {
  font-size: 0.82rem;
  line-height: 1.5;
  color: #cbd5e1;
  margin: 0;
}

/* Ledger Box */
.ledger-box {
  background: rgba(10, 7, 18, 0.7);
  border: 1px solid rgba(139, 92, 246, 0.2);
  border-radius: 4px;
  padding: 0.75rem 1rem;
  margin-bottom: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ledger-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8rem;
}

.ledger-label {
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  color: #94a3b8;
  font-weight: 700;
}

.price-val {
  font-weight: 800;
  color: #fbbf24;
}

.status-pill {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  padding: 0.1rem 0.5rem;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.08);
  color: #94a3b8;
}
.status-pill.owned {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.35);
}
.status-pill.equipped {
  background: rgba(139, 92, 246, 0.2);
  color: #c084fc;
  border: 1px solid rgba(139, 92, 246, 0.45);
}

.user-coins-val {
  font-weight: 700;
  color: #f1f5f9;
}
.user-coins-val.short {
  color: #f87171;
}

.short-diff {
  font-size: 0.72rem;
  color: #ef4444;
  margin-left: 0.25rem;
}

/* Actions */
.modal-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-action {
  flex: 1;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
}

.btn-buy-confirm {
  background: linear-gradient(180deg, #7c3aed 0%, #581c87 100%);
  border-color: #a855f7;
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4);
}
.btn-buy-confirm:hover:not(:disabled) {
  background: linear-gradient(180deg, #8b5cf6 0%, #6b21a8 100%);
  box-shadow: 0 6px 20px rgba(168, 85, 247, 0.6);
  transform: translateY(-1px);
}
.btn-buy-confirm:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  box-shadow: none;
  background: #332b47;
  border-color: #4c3f68;
}

.btn-equip {
  background: linear-gradient(180deg, #059669 0%, #065f46 100%);
  border-color: #10b981;
  color: #ecfdf5;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
}
.btn-equip:hover:not(:disabled) {
  background: linear-gradient(180deg, #10b981 0%, #047857 100%);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
  transform: translateY(-1px);
}

.btn-unequip {
  background: rgba(30, 27, 46, 0.85);
  border-color: rgba(168, 85, 247, 0.35);
  color: #cbd5e1;
}
.btn-unequip:hover:not(:disabled) {
  background: rgba(46, 38, 74, 0.95);
  color: #f1f5f9;
}
</style>
