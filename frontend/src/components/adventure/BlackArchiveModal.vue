<template>
  <div class="black-archive-backdrop" @click.self="$emit('close')">
    <div class="black-archive-modal gothic-panel animate-scale-up">
      <!-- Loading State -->
      <div v-if="loading" class="archive-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">COMMUNING WITH THE BLACK ARCHIVE…</p>
      </div>

      <!-- Main Gate Modal Content -->
      <div v-else class="archive-content">
        <!-- Header -->
        <div class="archive-header">
          <div class="archive-tag">
            <span>🗝️ THE BLACK ARCHIVE — FINAL ENCOUNTER</span>
          </div>
          <button class="archive-close-btn" type="button" @click="$emit('close')">✕</button>
        </div>

        <!-- Status Badge Strip -->
        <div class="status-strip">
          <span
            class="status-pill"
            :class="{
              'pill-locked': finalBossState.status === 'locked',
              'pill-unlocked': finalBossState.status === 'unlocked',
              'pill-completed': finalBossState.status === 'completed'
            }"
          >
            STATUS: {{ (finalBossState.status || 'LOCKED').toUpperCase() }}
          </span>
          <span class="gate-rule-hint">
            {{ finalBossState.status === 'locked' ? 'Requirement: Defeat Chapter 12 Guardian' : 'Encounter: The Creator' }}
          </span>
        </div>

        <!-- LOCKED VIEW -->
        <div v-if="finalBossState.status === 'locked'" class="archive-locked-view">
          <div class="locked-crest">🔒</div>
          <h2 class="archive-view-title">THE GENESIS GATE IS SEALED</h2>
          <p class="archive-view-desc">
            Behind the twelve chapters lies a hidden domain not found in standard taxonomies.
            You must defeat Chapter 12's guardian—<strong>The Mask of a Thousand Selves</strong>—before the Black Archive will open.
          </p>
          <div class="archive-actions">
            <button class="btn btn-gothic-crimson" @click="goToChapter12">
              GO TO CHAPTER 12 →
            </button>
            <button class="btn btn-gothic-stone" @click="$emit('close')">
              CLOSE
            </button>
          </div>
        </div>

        <!-- COMPLETED VIEW -->
        <div v-else-if="finalBossState.status === 'completed'" class="archive-completed-view">
          <div class="completed-crest">🕊️</div>
          <span class="victory-eyebrow">APEX CONQUEST CONFIRMED</span>
          <h2 class="archive-view-title">THE MINDREALM IS RESTORED</h2>
          <p class="archive-view-desc">
            The ink has dried and the manuscript rests in quiet remembrance. You have walked every page of Mindrealm,
            uncovered the mortal author's truth, and freed the realm from rigid grief.
          </p>
          <div class="archive-relics-box">
            <div class="relic-item">
              <span class="relic-glyph">🔮</span>
              <span class="relic-text">Mind Seal of Total Synthesis</span>
            </div>
            <div class="relic-item">
              <span class="relic-glyph">⚡</span>
              <span class="relic-text">Core of the Ancient Fracture</span>
            </div>
          </div>
          <div class="archive-actions">
            <button class="btn btn-gothic-crimson btn-lg" @click="enterFinalBoss">
              RE-ENTER THE ARCHIVE (REPLAY) ▸
            </button>
            <button class="btn btn-gothic-stone" @click="$emit('close')">
              CLOSE
            </button>
          </div>
        </div>

        <!-- UNLOCKED / READY VIEW -->
        <div v-else class="archive-unlocked-view">
          <div class="creator-preview-card">
            <div class="creator-portrait-box">
              <img
                :src="resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif')"
                alt="The Creator"
                class="creator-img pixel-art animate-boss-float"
              />
              <div class="creator-ring"></div>
            </div>

            <div class="creator-meta">
              <span class="creator-badge">THE ARCHITECT</span>
              <h2 class="creator-name">THE CREATOR</h2>
              <h4 class="creator-title">The Alternate Side of the Author & Origin of the Fracture</h4>
              <p class="creator-quote">
                "She created the world to understand it... but the alternate side demanded perfection and control."
              </p>
            </div>
          </div>

          <div class="encounter-briefing">
            <div class="briefing-item">
              <span class="briefing-label">TRIAL TYPE</span>
              <span class="briefing-val">Apex Confrontation</span>
            </div>
            <div class="briefing-item">
              <span class="briefing-label">ESSENCE</span>
              <span class="briefing-val">1,500 HP</span>
            </div>
            <div class="briefing-item">
              <span class="briefing-label">LOCATION</span>
              <span class="briefing-val">Deepest Chamber of Black Archive</span>
            </div>
          </div>

          <div class="archive-actions">
            <button class="btn btn-gothic-stone" @click="$emit('close')">
              CANCEL
            </button>
            <button class="btn btn-gothic-crimson btn-lg proceed-btn" @click="enterFinalBoss">
              <span>ENTER THE BLACK ARCHIVE</span>
              <span class="btn-arrow">▸</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdventureStore } from '../../stores/adventure'
import { useTransitionStore } from '../../stores/transition'
import { resolveAsset } from '../../services/bossMapping'

const emit = defineEmits(['close'])

const router = useRouter()
const adventure = useAdventureStore()
const transitionStore = useTransitionStore()

const loading = ref(true)
const finalBossState = ref({
  status: 'locked',
  unlocked: false,
  completed: false,
})

async function loadState() {
  loading.value = true
  try {
    const res = await adventure.loadFinalBoss()
    if (res?.state) {
      finalBossState.value = res.state
    } else if (res?.locked) {
      finalBossState.value = { status: 'locked', unlocked: false, completed: false }
    }
  } catch (e) {
    console.warn('Failed to load final boss state:', e)
  } finally {
    loading.value = false
  }
}

async function enterFinalBoss() {
  emit('close')
  await transitionStore.triggerTravel('/adventure/final-boss', router)
}

async function goToChapter12() {
  emit('close')
  await transitionStore.triggerTravel('/adventure/12', router)
}

onMounted(() => {
  loadState()
})
</script>

<style scoped>
.black-archive-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.88);
  backdrop-filter: blur(8px);
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.black-archive-modal {
  max-width: 680px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  background: #0f0a14;
  border: 2px solid #e11d48;
  outline: 3px solid #050608;
  box-shadow: 0 15px 60px rgba(0, 0, 0, 0.95), inset 0 0 35px rgba(225, 29, 72, 0.15);
  border-radius: 4px;
  padding: 1.5rem;
}

.archive-loading {
  text-align: center;
  padding: 4rem 1rem;
}
.loading-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid #232533;
  border-top-color: #e11d48;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.pixel-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 1px;
}

.archive-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}
.archive-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: #fb7185;
  letter-spacing: 1px;
}
.archive-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
}
.archive-close-btn:hover {
  color: #fff;
}

.status-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.5rem 0.85rem;
  margin-bottom: 1.25rem;
}
.status-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  letter-spacing: 1px;
  padding: 0.25rem 0.5rem;
  border-radius: 2px;
}
.pill-locked {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid #ef4444;
  color: #fca5a5;
}
.pill-unlocked {
  background: rgba(244, 63, 94, 0.25);
  border: 1px solid #f43f5e;
  color: #ffe4e6;
}
.pill-completed {
  background: rgba(16, 185, 129, 0.2);
  border: 1px solid #10b981;
  color: #6ee7b7;
}
.gate-rule-hint {
  font-size: 0.78rem;
  color: #94a3b8;
}

/* Views */
.archive-locked-view,
.archive-completed-view {
  text-align: center;
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}
.locked-crest,
.completed-crest {
  font-size: 3rem;
}
.victory-eyebrow {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 2px;
}
.archive-view-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.15rem;
  color: #f8fafc;
  margin: 0;
  line-height: 1.3;
}
.archive-view-desc {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  line-height: 1.6;
  color: #cbd5e1;
  max-width: 520px;
  margin: 0;
}

.archive-relics-box {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
  margin: 0.5rem 0;
}
.relic-item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.4rem 0.8rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #f8fafc;
}

/* Unlocked view */
.creator-preview-card {
  display: flex;
  gap: 1.25rem;
  align-items: center;
  background: rgba(20, 10, 18, 0.85);
  border: 1px solid rgba(225, 29, 72, 0.4);
  padding: 1.25rem;
  margin-bottom: 1.25rem;
}
.creator-portrait-box {
  position: relative;
  width: 95px;
  height: 95px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.creator-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
  z-index: 2;
  filter: drop-shadow(0 0 12px rgba(225, 29, 72, 0.6));
}
.creator-ring {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px dashed rgba(225, 29, 72, 0.5);
  animation: spin 15s linear infinite;
}
.creator-meta {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.creator-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: #e11d48;
  letter-spacing: 1.5px;
}
.creator-name {
  font-family: var(--font-pixel, monospace);
  font-size: 1.15rem;
  color: #f8fafc;
  margin: 0;
}
.creator-title {
  font-family: var(--font-body, serif);
  font-size: 0.82rem;
  font-style: italic;
  color: #fb7185;
  margin: 0;
}
.creator-quote {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.88rem;
  line-height: 1.45;
  color: #cbd5e1;
  margin: 0.3rem 0 0;
}

.encounter-briefing {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.85rem;
  margin-bottom: 1.5rem;
}
.briefing-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.briefing-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: #94a3b8;
  letter-spacing: 1px;
}
.briefing-val {
  font-size: 0.82rem;
  color: #f1f5f9;
  font-weight: 500;
}

.archive-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  align-items: center;
}

@media (max-width: 600px) {
  .creator-preview-card {
    flex-direction: column;
    text-align: center;
  }
  .encounter-briefing {
    grid-template-columns: 1fr;
  }
}
</style>
