<template>
  <div
    v-if="store.isOpen && store.hasPending"
    class="attribute-modal-backdrop"
    :class="{ 'theme-morning': game.isMorning }"
    role="dialog"
    aria-modal="true"
    aria-labelledby="attr-modal-title"
    tabindex="-1"
    @keydown="handleKeydown"
  >
    <div class="attribute-modal-panel gothic-panel animate-scale-up">
      <!-- Ornate Gothic Corner Runes -->
      <div class="modal-corner tl" aria-hidden="true">✦</div>
      <div class="modal-corner tr" aria-hidden="true">✦</div>
      <div class="modal-corner bl" aria-hidden="true">✦</div>
      <div class="modal-corner br" aria-hidden="true">✦</div>

      <!-- Close button (only available if user wants to allocate later, e.g. from Character Hall) -->
      <button
        type="button"
        class="modal-close-btn"
        @click="closeModal"
        title="Decide later"
        aria-label="Close"
      >
        ✕
      </button>

      <!-- ── MODAL HEADER ── -->
      <div class="modal-header">
        <div class="milestone-badge-pill font-pixel animate-pulse">
          <span class="badge-icon">⭐</span>
          <span>LEVEL {{ currentMilestoneLevel }} MILESTONE</span>
          <span v-if="remainingCount > 1" class="pending-queue-tag">
            (+{{ remainingCount - 1 }} pending)
          </span>
        </div>
        <h2 id="attr-modal-title" class="modal-main-title font-cinzel">
          ATTRIBUTE MILESTONE REACHED!
        </h2>
        <p class="modal-sub-text">
          You reached Level {{ currentMilestoneLevel }}. Choose one attribute to permanently empower your character.
        </p>
      </div>

      <!-- ── CHARACTER PREVIEW ── -->
      <div class="char-preview-banner">
        <div class="char-preview-left">
          <div class="char-sprite-box">
            <img
              v-if="characterSprite"
              :src="characterSprite"
              :alt="characterName"
              class="char-sprite-img pixel-art"
            />
            <div v-else class="char-sprite-fallback">
              <PixelEmoji emoji="shield" size="md" />
            </div>
            <div class="sprite-glow"></div>
          </div>
          <div class="char-meta-text">
            <div class="char-class-tag font-pixel">{{ characterClass }}</div>
            <h3 class="char-name font-cinzel">{{ characterName }}</h3>
            <div class="char-level-indicator">
              <span class="font-pixel">Level {{ userLevel }}</span>
              <span class="char-title-sub font-cinzel">{{ characterTitle }}</span>
            </div>
          </div>
        </div>

        <div class="current-stats-strip">
          <div class="cstat-item" :class="{ 'stat-boosted': selectedAttribute === 'attack_damage' }">
            <span class="cstat-lbl">
              <PixelEmoji emoji="sword" size="xs" /> ATK DMG
            </span>
            <span class="cstat-val font-pixel">{{ currentDamage }}</span>
            <span v-if="selectedAttribute === 'attack_damage'" class="projected-delta font-pixel animate-bounce-subtle">
              ➔ {{ currentDamage + 10 }}
            </span>
          </div>

          <div class="cstat-item" :class="{ 'stat-boosted': selectedAttribute === 'health' }">
            <span class="cstat-lbl">
              <PixelEmoji emoji="heart" size="xs" /> MAX HP
            </span>
            <span class="cstat-val font-pixel">{{ currentHealth }}</span>
            <span v-if="selectedAttribute === 'health'" class="projected-delta font-pixel animate-bounce-subtle">
              ➔ {{ currentHealth + 25 }}
            </span>
          </div>

          <div class="cstat-item" :class="{ 'stat-boosted': selectedAttribute === 'critical_damage' }">
            <span class="cstat-lbl">
              <PixelEmoji emoji="lightning" size="xs" /> CRIT DMG
            </span>
            <span class="cstat-val font-pixel">{{ currentCrit }}</span>
            <span v-if="selectedAttribute === 'critical_damage'" class="projected-delta font-pixel animate-bounce-subtle">
              ➔ {{ currentCrit + 15 }}
            </span>
          </div>
        </div>
      </div>

      <!-- ── ATTRIBUTE CARDS SELECTION (3 CHOICES) ── -->
      <div class="attribute-cards-grid" role="radiogroup" aria-label="Attribute Options">
        <!-- Card 1: Attack Damage -->
        <div
          class="attr-card card-damage"
          :class="{ selected: selectedAttribute === 'attack_damage' }"
          role="radio"
          :aria-checked="selectedAttribute === 'attack_damage'"
          tabindex="0"
          @click="selectAttribute('attack_damage')"
          @keydown.enter.prevent="selectAttribute('attack_damage')"
          @keydown.space.prevent="selectAttribute('attack_damage')"
        >
          <div class="card-radio-indicator">
            <span v-if="selectedAttribute === 'attack_damage'" class="radio-check">✓</span>
          </div>
          <div class="card-header">
            <div class="card-icon-wrap icon-damage">
              <PixelEmoji emoji="sword" size="sm" />
            </div>
            <div class="card-headings">
              <h4 class="card-title font-cinzel">ATTACK DAMAGE</h4>
              <span class="bonus-badge badge-damage font-pixel">+10 Attack Damage</span>
            </div>
          </div>
          <p class="card-description">
            Increase your character's Attack Damage by +10. Deal more damage with normal attacks.
          </p>
          <div class="card-footer-metric">
            <span class="metric-label">Normal Strike:</span>
            <span class="metric-values font-pixel">
              <span class="cur-val">{{ currentDamage }}</span>
              <span class="arrow">➔</span>
              <span class="proj-val highlight-damage">{{ currentDamage + 10 }}</span>
            </span>
          </div>
        </div>

        <!-- Card 2: Health -->
        <div
          class="attr-card card-health"
          :class="{ selected: selectedAttribute === 'health' }"
          role="radio"
          :aria-checked="selectedAttribute === 'health'"
          tabindex="0"
          @click="selectAttribute('health')"
          @keydown.enter.prevent="selectAttribute('health')"
          @keydown.space.prevent="selectAttribute('health')"
        >
          <div class="card-radio-indicator">
            <span v-if="selectedAttribute === 'health'" class="radio-check">✓</span>
          </div>
          <div class="card-header">
            <div class="card-icon-wrap icon-health">
              <PixelEmoji emoji="heart" size="sm" />
            </div>
            <div class="card-headings">
              <h4 class="card-title font-cinzel">HEALTH</h4>
              <span class="bonus-badge badge-health font-pixel">+25 Maximum Health</span>
            </div>
          </div>
          <p class="card-description">
            Increase your character's Maximum Health by +25. Become more durable in battle.
          </p>
          <div class="card-footer-metric">
            <span class="metric-label">Max Vitality:</span>
            <span class="metric-values font-pixel">
              <span class="cur-val">{{ currentHealth }}</span>
              <span class="arrow">➔</span>
              <span class="proj-val highlight-health">{{ currentHealth + 25 }}</span>
            </span>
          </div>
        </div>

        <!-- Card 3: Critical Damage -->
        <div
          class="attr-card card-crit"
          :class="{ selected: selectedAttribute === 'critical_damage' }"
          role="radio"
          :aria-checked="selectedAttribute === 'critical_damage'"
          tabindex="0"
          @click="selectAttribute('critical_damage')"
          @keydown.enter.prevent="selectAttribute('critical_damage')"
          @keydown.space.prevent="selectAttribute('critical_damage')"
        >
          <div class="card-radio-indicator">
            <span v-if="selectedAttribute === 'critical_damage'" class="radio-check">✓</span>
          </div>
          <div class="card-header">
            <div class="card-icon-wrap icon-crit">
              <PixelEmoji emoji="lightning" size="sm" />
            </div>
            <div class="card-headings">
              <h4 class="card-title font-cinzel">CRITICAL DAMAGE</h4>
              <span class="bonus-badge badge-crit font-pixel">+15 Critical Damage</span>
            </div>
          </div>
          <p class="card-description">
            Increase your character's Critical Damage by +15. Make critical attacks more powerful.
          </p>
          <div class="card-footer-metric">
            <span class="metric-label">Critical Surge:</span>
            <span class="metric-values font-pixel">
              <span class="cur-val">{{ currentCrit }}</span>
              <span class="arrow">➔</span>
              <span class="proj-val highlight-crit">{{ currentCrit + 15 }}</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Error / Notice Banner -->
      <div v-if="errorMessage" class="modal-error-banner font-pixel" role="alert">
        ⚠️ {{ errorMessage }}
      </div>

      <!-- ── CONFIRMATION ACTIONS ── -->
      <div class="modal-actions-bar">
        <div class="allocation-progress-hint font-cinzel">
          <span>ALLOCATIONS: {{ store.completedCount }} / 10 COMPLETED</span>
        </div>

        <div class="buttons-group">
          <button
            type="button"
            class="btn-decide-later font-cinzel"
            :disabled="store.submitting"
            @click="closeModal"
          >
            DECIDE LATER
          </button>

          <button
            type="button"
            class="btn-confirm-attribute font-cinzel"
            :class="{ 'btn-ready': selectedAttribute, 'is-loading': store.submitting }"
            :disabled="!selectedAttribute || store.submitting"
            @click="confirmAllocation"
          >
            <span v-if="store.submitting" class="submitting-spinner-wrap">
              <span class="mini-spinner"></span>
              <span>COMMITTING ENHANCEMENT…</span>
            </span>
            <span v-else class="confirm-content">
              <span>✦</span>
              <span>CONFIRM ATTRIBUTE</span>
              <span>✦</span>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useAttributeAllocationStore } from '../../stores/attributeAllocation'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { useToastStore } from '../../stores/toast'

const store = useAttributeAllocationStore()
const auth = useAuthStore()
const game = useGameStore()
const toast = useToastStore()

const selectedAttribute = ref(null)
const errorMessage = ref(null)

const userLevel = computed(() => auth.level || 1)
const currentMilestoneLevel = computed(() => store.activeMilestone || store.nextPendingMilestone || null)
const remainingCount = computed(() => store.pendingMilestones.length)

// Character Data
const character = computed(() => store.character || auth.selectedCharacter || {})
const characterName = computed(() => character.value?.name || 'Scholar')
const characterClass = computed(() => character.value?.class_type || 'MAGE')
const characterTitle = computed(() => character.value?.title || 'Psychology Scholar')
const characterSprite = computed(() => character.value?.sprite_path || character.value?.idle_asset_path || null)

// Current Stats
const currentDamage = computed(() => store.scaledStats?.damage ?? store.scaledStats?.attack_power ?? 10)
const currentHealth = computed(() => store.scaledStats?.max_health ?? store.scaledStats?.health ?? 150)
const currentCrit = computed(() => store.scaledStats?.crit_damage ?? store.scaledStats?.critical_attack_power ?? 35)

// Reset selection when active milestone changes
watch(() => store.activeMilestone, () => {
  selectedAttribute.value = null
  errorMessage.value = null
})

// Auto-sync if modal opens or character changes
watch(() => [store.isOpen, auth.user?.selected_character_id], ([isOpen, charId]) => {
  if (isOpen && charId && store.character?.id !== charId) {
    store.fetchAllocations(charId)
  }
})

// Focus trapping & keyboard shortcuts
function handleKeydown(e) {
  if (e.key === '1') {
    selectAttribute('attack_damage')
  } else if (e.key === '2') {
    selectAttribute('health')
  } else if (e.key === '3') {
    selectAttribute('critical_damage')
  } else if (e.key === 'Escape' && !store.submitting) {
    closeModal()
  }
}

function selectAttribute(type) {
  selectedAttribute.value = type
  errorMessage.value = null
}

function closeModal() {
  store.closeModal()
  selectedAttribute.value = null
  errorMessage.value = null
}

async function confirmAllocation() {
  if (!selectedAttribute.value || store.submitting || !currentMilestoneLevel.value || !store.hasPending) return
  errorMessage.value = null

  const milestone = currentMilestoneLevel.value
  const attr = selectedAttribute.value

  const result = await store.claimAllocation(milestone, attr)

  if (result.success) {
    const attrName = attr === 'attack_damage' ? 'Attack Damage (+10)' : attr === 'health' ? 'Maximum Health (+25)' : 'Critical Damage (+15)'
    toast.success(`Milestone Level ${milestone}: Permanently increased ${attrName}!`)

    selectedAttribute.value = null

    // If there is another pending milestone, it will stay open for the next one in ascending order
    if (store.hasPending) {
      toast.info(`Next eligible milestone ready: Level ${store.activeMilestone}!`)
    }
  } else {
    errorMessage.value = result.message || 'Failed to allocate attribute.'
    toast.error(result.message || 'Allocation failed. Please try again.')
  }
}

onMounted(() => {
  if (auth.isLoggedIn) {
    store.fetchAllocations()
  }
})
</script>

<style scoped>
.attribute-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(4, 3, 8, 0.88);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  overflow-y: auto;
}

.attribute-modal-panel {
  position: relative;
  width: 100%;
  max-width: 820px;
  background: linear-gradient(180deg, rgba(22, 17, 36, 0.98) 0%, rgba(12, 9, 20, 0.99) 100%);
  border: 1px solid rgba(212, 175, 55, 0.45);
  box-shadow: 0 0 35px rgba(0, 0, 0, 0.85), 0 0 20px rgba(181, 148, 16, 0.2);
  border-radius: 8px;
  padding: 2rem 2.25rem;
  color: #f3efe6;
}

/* Morning Mode Overrides */
.theme-morning .attribute-modal-backdrop {
  background: rgba(220, 215, 205, 0.75);
}
.theme-morning .attribute-modal-panel {
  background: linear-gradient(180deg, #fdfbf7 0%, #f4ede2 100%);
  border: 1px solid rgba(181, 148, 16, 0.6);
  box-shadow: 0 15px 45px rgba(80, 60, 40, 0.25);
  color: #2b251d;
}
.theme-morning .modal-sub-text {
  color: #635746;
}
.theme-morning .char-preview-banner {
  background: rgba(235, 226, 212, 0.85);
  border-color: rgba(181, 148, 16, 0.35);
}
.theme-morning .char-title-sub {
  color: #7a6b56;
}
.theme-morning .cstat-item {
  background: rgba(248, 243, 235, 0.9);
  border-color: rgba(181, 148, 16, 0.3);
}
.theme-morning .cstat-lbl {
  color: #6d5f4c;
}
.theme-morning .attr-card {
  background: rgba(255, 255, 255, 0.85);
  border-color: rgba(181, 148, 16, 0.3);
  color: #2b251d;
}
.theme-morning .card-description {
  color: #55493a;
}
.theme-morning .card-footer-metric {
  background: rgba(243, 235, 222, 0.85);
  border-top-color: rgba(181, 148, 16, 0.2);
}
.theme-morning .btn-decide-later {
  background: rgba(220, 210, 195, 0.7);
  color: #5a4b37;
  border-color: rgba(181, 148, 16, 0.4);
}

/* Corner Runes */
.modal-corner {
  position: absolute;
  font-size: 1.1rem;
  color: #d4af37;
  line-height: 1;
  user-select: none;
  opacity: 0.85;
}
.modal-corner.tl { top: 8px; left: 10px; }
.modal-corner.tr { top: 8px; right: 10px; }
.modal-corner.bl { bottom: 8px; left: 10px; }
.modal-corner.br { bottom: 8px; right: 10px; }

/* Close Button */
.modal-close-btn {
  position: absolute;
  top: 14px;
  right: 18px;
  background: transparent;
  border: none;
  color: #a09789;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
  z-index: 2;
}
.modal-close-btn:hover {
  color: #ffd700;
  background: rgba(255, 255, 255, 0.08);
}

/* Modal Header */
.modal-header {
  text-align: center;
  margin-bottom: 1.5rem;
}
.milestone-badge-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(212, 175, 55, 0.16);
  border: 1px solid rgba(212, 175, 55, 0.5);
  color: #ffd700;
  padding: 0.3rem 0.85rem;
  border-radius: 20px;
  font-size: 0.72rem;
  letter-spacing: 0.08em;
  margin-bottom: 0.6rem;
}
.pending-queue-tag {
  color: #a3e635;
  font-weight: 700;
}
.modal-main-title {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: #f7eed8;
  margin: 0.25rem 0 0.5rem;
  text-shadow: 0 0 16px rgba(212, 175, 55, 0.4);
}
.modal-sub-text {
  font-size: 0.95rem;
  color: #b5a999;
  max-width: 620px;
  margin: 0 auto;
  line-height: 1.45;
}

/* Character Preview Banner */
.char-preview-banner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  background: rgba(10, 8, 16, 0.65);
  border: 1px solid rgba(212, 175, 55, 0.25);
  border-radius: 6px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.75rem;
}
.char-preview-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.char-sprite-box {
  position: relative;
  width: 68px;
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle, rgba(212, 175, 55, 0.18) 0%, rgba(0,0,0,0.4) 80%);
  border: 1px solid rgba(212, 175, 55, 0.35);
  border-radius: 6px;
  overflow: hidden;
}
.char-sprite-img {
  width: 58px;
  height: 58px;
  object-fit: contain;
  image-rendering: pixelated;
  z-index: 1;
}
.char-meta-text .char-class-tag {
  font-size: 0.65rem;
  color: #ffd700;
  letter-spacing: 0.08em;
}
.char-meta-text .char-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #fff;
  margin: 0.1rem 0;
}
.char-level-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #a89f91;
}
.char-title-sub {
  font-style: italic;
  font-size: 0.8rem;
}

/* Stats Strip */
.current-stats-strip {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.cstat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(20, 16, 32, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  padding: 0.4rem 0.75rem;
  min-width: 90px;
  transition: all 0.25s ease;
}
.cstat-item.stat-boosted {
  border-color: #a3e635;
  background: rgba(163, 230, 53, 0.12);
  box-shadow: 0 0 12px rgba(163, 230, 53, 0.25);
}
.cstat-lbl {
  font-size: 0.68rem;
  color: #9f9686;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.cstat-val {
  font-size: 0.95rem;
  font-weight: 700;
  color: #fff;
}
.projected-delta {
  font-size: 0.72rem;
  color: #a3e635;
  font-weight: 700;
  margin-top: 2px;
}

/* Attribute Cards Grid */
.attribute-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.15rem;
  margin-bottom: 1.5rem;
}
@media (max-width: 768px) {
  .attribute-cards-grid {
    grid-template-columns: 1fr;
  }
}

.attr-card {
  position: relative;
  background: rgba(16, 12, 26, 0.8);
  border: 2px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 1.25rem 1.1rem;
  cursor: pointer;
  transition: all 0.22s ease-in-out;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  outline: none;
}
.attr-card:hover {
  transform: translateY(-3px);
  border-color: rgba(212, 175, 55, 0.6);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
}
.attr-card:focus-visible {
  border-color: #ffd700;
  box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.4);
}

/* Selected Card Highlighting */
.attr-card.selected {
  transform: translateY(-4px);
}
.attr-card.card-damage.selected {
  border-color: #f87171;
  background: linear-gradient(180deg, rgba(239, 68, 68, 0.16) 0%, rgba(20, 10, 15, 0.95) 100%);
  box-shadow: 0 0 24px rgba(239, 68, 68, 0.35);
}
.attr-card.card-health.selected {
  border-color: #34d399;
  background: linear-gradient(180deg, rgba(52, 211, 153, 0.16) 0%, rgba(10, 20, 15, 0.95) 100%);
  box-shadow: 0 0 24px rgba(52, 211, 153, 0.35);
}
.attr-card.card-crit.selected {
  border-color: #fbbf24;
  background: linear-gradient(180deg, rgba(251, 191, 36, 0.16) 0%, rgba(20, 18, 10, 0.95) 100%);
  box-shadow: 0 0 24px rgba(251, 191, 36, 0.35);
}

/* Radio Indicator */
.card-radio-indicator {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  transition: all 0.2s;
}
.selected .card-radio-indicator {
  border-color: #a3e635;
  background: #a3e635;
  color: #0c0914;
}

/* Card Header */
.card-header {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
}
.card-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.icon-damage { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); }
.icon-health { background: rgba(52, 211, 153, 0.2); border: 1px solid rgba(52, 211, 153, 0.4); }
.icon-crit { background: rgba(251, 191, 36, 0.2); border: 1px solid rgba(251, 191, 36, 0.4); }

.card-title {
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin: 0 0 0.3rem;
  color: #f7eed8;
}
.bonus-badge {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  letter-spacing: 0.05em;
}
.badge-damage { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
.badge-health { background: rgba(52, 211, 153, 0.25); color: #6ee7b7; }
.badge-crit { background: rgba(251, 191, 36, 0.25); color: #fde68a; }

.card-description {
  font-size: 0.85rem;
  line-height: 1.42;
  color: #a69c8e;
  margin-bottom: 1rem;
}

/* Card Footer Metric */
.card-footer-metric {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(0, 0, 0, 0.35);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 4px;
  padding: 0.5rem 0.65rem;
  font-size: 0.78rem;
}
.metric-label {
  color: #8c8273;
}
.metric-values {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.82rem;
}
.cur-val { color: #d1c8b8; }
.arrow { color: #73695b; font-size: 0.7rem; }
.proj-val { font-weight: 700; }
.highlight-damage { color: #f87171; }
.highlight-health { color: #34d399; }
.highlight-crit { color: #fbbf24; }

/* Error Banner */
.modal-error-banner {
  background: rgba(239, 68, 68, 0.18);
  border: 1px solid rgba(239, 68, 68, 0.6);
  color: #fca5a5;
  padding: 0.6rem 1rem;
  border-radius: 4px;
  font-size: 0.75rem;
  margin-bottom: 1rem;
  text-align: center;
}

/* Modal Actions Bar */
.modal-actions-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(212, 175, 55, 0.25);
}
.allocation-progress-hint {
  font-size: 0.82rem;
  letter-spacing: 0.05em;
  color: #b5a999;
}
.buttons-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

/* Buttons */
.btn-decide-later {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #b5a999;
  padding: 0.75rem 1.25rem;
  border-radius: 4px;
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-decide-later:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
}

.btn-confirm-attribute {
  background: #3e3223;
  border: 1px solid #735e29;
  color: #8c7e68;
  padding: 0.75rem 1.75rem;
  border-radius: 4px;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  cursor: not-allowed;
  transition: all 0.22s ease-in-out;
}
.btn-confirm-attribute.btn-ready {
  background: linear-gradient(180deg, #d4af37 0%, #aa820a 100%);
  border-color: #ffd700;
  color: #0c0914;
  box-shadow: 0 0 16px rgba(212, 175, 55, 0.5);
  cursor: pointer;
}
.btn-confirm-attribute.btn-ready:hover:not(:disabled) {
  background: linear-gradient(180deg, #ffe066 0%, #c4950d 100%);
  box-shadow: 0 0 24px rgba(212, 175, 55, 0.7);
  transform: translateY(-1px);
}
.btn-confirm-attribute.is-loading {
  cursor: wait;
  opacity: 0.85;
}
.confirm-content {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.submitting-spinner-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.mini-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(0,0,0,0.3);
  border-top-color: #000;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-bounce-subtle {
  animation: bounceSubtle 1.4s infinite;
}
@keyframes bounceSubtle {
  0%, 100% { transform: translateX(0); }
  50% { transform: translateX(3px); }
}
</style>
