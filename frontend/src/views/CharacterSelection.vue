<template>
  <div class="character-selection-screen" :class="`ambient-theme-${selectedCharacter?.class_type?.toLowerCase() || 'mage'}`">
    <!-- Atmospheric Ambient Background & Lighting Shift -->
    <div class="selection-bg">
      <div class="bg-gradient-mesh"></div>
      <div class="bg-mist-layer"></div>
      <div class="bg-vignette"></div>
      <div class="bg-particle-dust" aria-hidden="true">
        <span v-for="i in 18" :key="i" class="dust-speck" :style="dustStyle(i)"></span>
      </div>
    </div>

    <!-- Header: Mindrealm Title & Gothic Inscription -->
    <header class="selection-header animate-slide-up">
      <div class="realm-crest-mark">Ψ</div>
      <div class="header-titles">
        <span class="header-eyebrow font-pixel">MINDREALM INITIATION</span>
        <h1 class="header-main-title font-cinzel">CHOOSE YOUR CHARACTER</h1>
        <p class="header-sub-text">Inscribe your soul into a vessel capable of confronting the Fracture</p>
      </div>
    </header>

    <!-- Main Character Selection Stage Area -->
    <main v-if="selectedCharacter" class="selection-stage-grid animate-fade-in">
      <!-- ── LEFT ZONE: CHARACTER CLASS SELECTION CARDS ── -->
      <section class="stage-col col-roster" aria-label="Character Roster">
        <div class="roster-heading-wrap">
          <span class="roster-eyebrow font-pixel">AVAILABLE VESSELS</span>
          <h2 class="roster-title font-cinzel">THREE PATHS</h2>
        </div>

        <div class="roster-cards-stack">
          <CharacterOption
            v-for="char in characters"
            :key="char.id"
            :character="char"
            :is-selected="selectedCharacter.id === char.id"
            @select="onSelectCharacter"
          />
        </div>

        <div class="roster-lore-note">
          <span class="note-icon">⚖️</span>
          <p class="note-text font-cinzel">
            Each class wields unique attributes and perspectives on the subconscious realm.
          </p>
        </div>
      </section>

      <!-- ── CENTER ZONE: LARGE CINEMATIC CHARACTER PREVIEW ── -->
      <section class="stage-col col-preview" aria-label="Active Character Showcase">
        <CharacterPreview :character="selectedCharacter" />

        <!-- Confirm CTA Button under character -->
        <div class="preview-cta-wrap">
          <button
            type="button"
            class="btn-choose-vessel font-cinzel"
            :class="`theme-${selectedCharacter.class_type.toLowerCase()}`"
            @click="openConfirmationModal"
          >
            <span class="cta-rune">✦</span>
            <span class="cta-label">CONFIRM {{ selectedCharacter.name.toUpperCase() }}</span>
            <span class="cta-rune">✦</span>
          </button>
        </div>
      </section>

      <!-- ── RIGHT ZONE: STATS, OPENING CINEMATIC & LORE ── -->
      <section class="stage-col col-details" aria-label="Character Lore and Attributes">
        <!-- RPG Stats Indicator -->
        <CharacterStats :character="selectedCharacter" />

        <!-- Opening GIF Preview with Replay -->
        <CharacterOpening :character="selectedCharacter" />

        <!-- Character Story & Motivation Tabs -->
        <CharacterStoryPanel :character="selectedCharacter" />
      </section>
    </main>

    <!-- Loading Fallback -->
    <div v-else class="selection-loading-state">
      <div class="loading-sigil"></div>
      <p class="loading-text font-cinzel">Summoning Vessels of the Mindrealm…</p>
    </div>

    <!-- Confirmation Modal Dialog (Section 15) -->
    <CharacterConfirmation
      v-if="showConfirmModal && selectedCharacter"
      :character="selectedCharacter"
      :is-submitting="isSubmitting"
      @confirm="executeCharacterConfirmation"
      @cancel="showConfirmModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTransitionStore } from '../stores/transition'
import api from '../services/api'

import CharacterOption from '../components/character-select/CharacterOption.vue'
import CharacterPreview from '../components/character-select/CharacterPreview.vue'
import CharacterStats from '../components/character-select/CharacterStats.vue'
import CharacterStoryPanel from '../components/character-select/CharacterStoryPanel.vue'
import CharacterOpening from '../components/character-select/CharacterOpening.vue'
import CharacterConfirmation from '../components/character-select/CharacterConfirmation.vue'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()

// State
const characters = ref([])
const selectedCharacter = ref(null)
const showConfirmModal = ref(false)
const isSubmitting = ref(false)

// Fallback initial data if server takes time
const FALLBACK_CHARACTERS = [
  {
    id: 1,
    key_name: 'mage',
    name: 'Necromancer',
    class_type: 'MAGE',
    title: 'The Umbral Psychomancer',
    health: 150,
    damage: 10,
    crit_damage: 35,
    sprite_path: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer.png',
    opening_gif_path: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer OPENING.gif',
    attack_asset_path: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer Attack.gif',
    idle_asset_path: '/assets/CHARACTERS/NECROMANCER (MAGE)/Necromancer IDLE STATE.gif',
    profile_avatar_path: '/assets/PROFILE-AVATAR/NecroProfile.png',
    short_intro: 'A dark scholar of the subconscious who commands necrotic insight and cognitive runes.',
    lore: 'Hailing from the Catacombs of Memory, the Psychomancer walks the shadowed boundary of human consciousness. While others fear the Fracture that shattered the Mindrealm, the Mage recognizes an unraveling of repressed truths and dormant cognitive potential.',
    motivation: 'To pierce the obsidian veil of the Black Archive, reconstruct the shattered Psyche, and prove that true understanding transcends mortal dread.',
    opening_message: 'Consciousness is a veil cast over the void. The Fracture cries out for one who does not fear the shadows within.',
    description: 'Specializes in high critical potency and arcane knowledge manipulation, uncovering deeper cognitive truths beneath traumatic distortions.',
    status: 'active'
  },
  {
    id: 2,
    key_name: 'melee',
    name: 'Dual-Wielder',
    class_type: 'MELEE',
    title: 'The Twin-Blade Executor',
    health: 200,
    damage: 10,
    crit_damage: 20,
    sprite_path: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Dual-wielder.png',
    opening_gif_path: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee OPENING.gif',
    attack_asset_path: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee Attack.gif',
    idle_asset_path: '/assets/CHARACTERS/DUAL-WIELDER (MELEE)/Melee IDLE.gif',
    profile_avatar_path: '/assets/PROFILE-AVATAR/MeleeProfile.png',
    short_intro: 'A vanguard of pure discipline who cleaves mental distortion with twin obsidian blades.',
    lore: 'When the Fracture ripped open the emotional core of the Mindrealm, raw neurosis and cognitive dissonance assumed monstrous physical forms. The Dual-Wielder was once a Guardian of the Cognitive Citadel who refused surrender to existential despair.',
    motivation: 'To shatter the idols of cognitive distortion with unrelenting force, guarding vulnerable seekers and restoring equilibrium through indomitable willpower.',
    opening_message: 'Doubt is a phantom that vanishes before unwavering resolve. Stand firm, and let our twin blades carve the path forward.',
    description: 'Boasts supreme durability and grounded resilience, standing unwavering against overwhelming psychological assaults.',
    status: 'active'
  },
  {
    id: 3,
    key_name: 'range',
    name: 'Archer',
    class_type: 'RANGE',
    title: 'The Starlight Marksman',
    health: 110,
    damage: 15,
    crit_damage: 50,
    sprite_path: '/assets/CHARACTERS/ARCHER (RANGE)/Archer.png',
    opening_gif_path: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr OPENING.gif',
    attack_asset_path: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr ATTACKING.gif',
    idle_asset_path: '/assets/CHARACTERS/ARCHER (RANGE)/Archerr IDLE.gif',
    profile_avatar_path: '/assets/PROFILE-AVATAR/ArcherProfile.png',
    short_intro: 'A keen observer attuned to synaptic signals, piercing fallacies from afar with unerring focus.',
    lore: 'From the silent canopy of the Lucid Woodlands, the Ranger observes the subtlest tremors across the neural web. Possessing an acute sensory threshold and unmatched metacognitive precision, their arrows are woven from pure focused attention.',
    motivation: 'To spot the subtle lies whispered by the Fracture, strike down cognitive biases from afar, and illuminate the forgotten pathways of the Mindrealm.',
    opening_message: 'A restless mind misses the target; a focused spirit never falters. From the perimeter of shadow, we shall bring piercing clarity.',
    description: 'Excels at piercing offensive precision and massive critical strikes, dismantling complex mental fallacies from great distances.',
    status: 'active'
  }
]

onMounted(async () => {
  try {
    const res = await api.get('/characters')
    if (res.data?.success && res.data.characters?.length) {
      characters.value = res.data.characters
    } else {
      characters.value = FALLBACK_CHARACTERS
    }
  } catch (err) {
    console.warn('[CharacterSelection] Backend fetch failed, using official initial values', err)
    characters.value = FALLBACK_CHARACTERS
  }

  // Set default selection: if user already has a selection or default to Mage
  if (auth.user?.selected_character_id) {
    const matched = characters.value.find(c => c.id === auth.user.selected_character_id)
    selectedCharacter.value = matched || characters.value[0]
  } else {
    selectedCharacter.value = characters.value[0]
  }
})

function onSelectCharacter(char) {
  if (selectedCharacter.value?.id === char.id) return
  selectedCharacter.value = char
}

function openConfirmationModal() {
  showConfirmModal.value = true
}

async function executeCharacterConfirmation() {
  if (!selectedCharacter.value) return
  isSubmitting.value = true

  try {
    const res = await auth.selectCharacter(selectedCharacter.value.id)
    if (res.success) {
      toast.success(`${selectedCharacter.value.name} (${selectedCharacter.value.class_type}) selected! Your journey begins!`)
      showConfirmModal.value = false

      // Transition smoothly into Tutorial (for new player) or World Hub (for existing)
      const target = auth.isNewPlayer ? '/tutorial' : '/world'
      const navigated = await transitionStore.triggerTravel(target, router)
      if (!navigated) {
        await router.push(target)
      }
    } else {
      toast.error(res.message || 'Failed to confirm character.')
    }
  } catch (err) {
    console.error('[CharacterSelection] Error confirming character:', err)
    toast.error('A connection error occurred. Please try again.')
  } finally {
    isSubmitting.value = false
  }
}

// Visual particle style helper
function dustStyle(i) {
  const left = (i * 5.5 + (i % 3) * 7) % 100
  const top = (i * 7 + (i % 5) * 11) % 100
  const duration = 6 + (i % 5) * 3
  const delay = (i % 4) * 1.5
  return {
    left: `${left}%`,
    top: `${top}%`,
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`
  }
}
</script>

<style scoped>
.character-selection-screen {
  position: relative;
  min-height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  padding: 1.5rem 2rem 2.5rem;
  background: #050408;
  color: #f5efe6;
  overflow-x: hidden;
  box-sizing: border-box;
  transition: background 0.8s ease;
}

/* Atmospheric Background */
.selection-bg {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}

.bg-gradient-mesh {
  position: absolute;
  inset: 0;
  transition: background 0.8s ease;
}

.ambient-theme-mage .bg-gradient-mesh {
  background:
    radial-gradient(ellipse at 50% 10%, rgba(126, 34, 206, 0.22) 0%, transparent 60%),
    radial-gradient(circle at 10% 50%, rgba(88, 28, 135, 0.15) 0%, transparent 45%),
    radial-gradient(circle at 90% 60%, rgba(59, 7, 100, 0.2) 0%, transparent 50%),
    #07050d;
}

.ambient-theme-melee .bg-gradient-mesh {
  background:
    radial-gradient(ellipse at 50% 10%, rgba(185, 28, 28, 0.22) 0%, transparent 60%),
    radial-gradient(circle at 10% 50%, rgba(153, 27, 27, 0.15) 0%, transparent 45%),
    radial-gradient(circle at 90% 60%, rgba(127, 29, 29, 0.2) 0%, transparent 50%),
    #0a0405;
}

.ambient-theme-range .bg-gradient-mesh {
  background:
    radial-gradient(ellipse at 50% 10%, rgba(15, 118, 110, 0.22) 0%, transparent 60%),
    radial-gradient(circle at 10% 50%, rgba(19, 78, 74, 0.15) 0%, transparent 45%),
    radial-gradient(circle at 90% 60%, rgba(17, 94, 89, 0.2) 0%, transparent 50%),
    #030908;
}

.bg-vignette {
  position: absolute;
  inset: 0;
  box-shadow: inset 0 0 100px rgba(0, 0, 0, 0.85);
}

.bg-mist-layer {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 250px;
  background: linear-gradient(0deg, rgba(6, 4, 10, 0.85) 0%, transparent 100%);
}

.dust-speck {
  position: absolute;
  width: 2px;
  height: 2px;
  background: rgba(255, 255, 255, 0.4);
  border-radius: 50%;
  animation: float-speck linear infinite;
}

@keyframes float-speck {
  0% { transform: translateY(0) scale(0.8); opacity: 0; }
  50% { opacity: 0.6; }
  100% { transform: translateY(-80px) scale(1.2); opacity: 0; }
}

/* Header */
.selection-header {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.25rem;
  margin-bottom: 2rem;
  text-align: center;
}

.realm-crest-mark {
  font-size: 2.2rem;
  color: #a855f7;
  text-shadow: 0 0 16px rgba(168, 85, 247, 0.6);
  line-height: 1;
}

.ambient-theme-melee .realm-crest-mark {
  color: #ef4444;
  text-shadow: 0 0 16px rgba(239, 68, 68, 0.6);
}

.ambient-theme-range .realm-crest-mark {
  color: #2dd4bf;
  text-shadow: 0 0 16px rgba(45, 212, 191, 0.6);
}

.header-titles {
  display: flex;
  flex-direction: column;
}

.header-eyebrow {
  font-size: 0.58rem;
  letter-spacing: 0.22em;
  color: #c084fc;
  margin-bottom: 0.2rem;
}

.ambient-theme-melee .header-eyebrow { color: #f87171; }
.ambient-theme-range .header-eyebrow { color: #5eead4; }

.header-main-title {
  font-size: 2.4rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  color: #ffffff;
  line-height: 1.1;
  text-shadow: 0 4px 16px rgba(0, 0, 0, 0.9);
}

.header-sub-text {
  font-size: 0.85rem;
  color: #a8a29e;
  letter-spacing: 0.04em;
  margin-top: 0.35rem;
}

/* 3-Column Layout (Section 28) */
.selection-stage-grid {
  position: relative;
  z-index: 10;
  display: grid;
  grid-template-columns: 280px minmax(360px, 1fr) 380px;
  gap: 2rem;
  max-width: 1440px;
  margin: 0 auto;
  width: 100%;
  align-items: start;
}

.stage-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Col 1: Roster */
.roster-heading-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.roster-eyebrow {
  font-size: 0.52rem;
  letter-spacing: 0.14em;
  color: #a8a29e;
}

.roster-title {
  font-size: 1.15rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #f5efe6;
}

.roster-cards-stack {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.roster-lore-note {
  display: flex;
  align-items: flex-start;
  gap: 0.55rem;
  padding: 0.75rem 0.9rem;
  background: rgba(18, 14, 26, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 4px;
}

.note-icon {
  font-size: 0.95rem;
  line-height: 1;
}

.note-text {
  font-size: 0.72rem;
  color: #a8a29e;
  line-height: 1.45;
  margin: 0;
}

/* Col 2: Center Preview */
.col-preview {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.preview-cta-wrap {
  width: 100%;
  display: flex;
  justify-content: center;
  margin-top: 1rem;
}

.btn-choose-vessel {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.85rem;
  padding: 1.1rem 2.5rem;
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #ffffff;
  border-radius: 4px;
  cursor: pointer;
  outline: none;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.8);
}

.theme-mage.btn-choose-vessel {
  background: linear-gradient(180deg, #7e22ce 0%, #581c87 100%);
  border: 2px solid #c084fc;
  box-shadow: 0 0 25px rgba(168, 85, 247, 0.5), 0 8px 30px rgba(0, 0, 0, 0.8);
}

.theme-melee.btn-choose-vessel {
  background: linear-gradient(180deg, #b91c1c 0%, #7f1d1d 100%);
  border: 2px solid #f87171;
  box-shadow: 0 0 25px rgba(239, 68, 68, 0.5), 0 8px 30px rgba(0, 0, 0, 0.8);
}

.theme-range.btn-choose-vessel {
  background: linear-gradient(180deg, #0f766e 0%, #115e59 100%);
  border: 2px solid #2dd4bf;
  box-shadow: 0 0 25px rgba(45, 212, 191, 0.5), 0 8px 30px rgba(0, 0, 0, 0.8);
}

.btn-choose-vessel:hover {
  transform: translateY(-3px) scale(1.02);
  filter: brightness(1.2);
}

.btn-choose-vessel:active {
  transform: translateY(0) scale(0.99);
}

.cta-rune {
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Col 3: Details */
.col-details {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

/* Loading State */
.selection-loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.25rem;
  min-height: 400px;
}

.loading-sigil {
  width: 48px;
  height: 48px;
  border: 3px solid rgba(168, 85, 247, 0.2);
  border-top-color: #c084fc;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

.loading-text {
  font-size: 1.1rem;
  color: #d6d3d1;
  letter-spacing: 0.08em;
}

/* Responsive Adaptations (Section 31) */
@media (max-width: 1200px) {
  .selection-stage-grid {
    grid-template-columns: 240px 1fr 320px;
    gap: 1.25rem;
  }
}

@media (max-width: 992px) {
  .selection-stage-grid {
    grid-template-columns: 1fr;
    max-width: 650px;
  }

  .col-preview {
    order: 1;
  }

  .col-roster {
    order: 2;
  }

  .roster-cards-stack {
    flex-direction: row;
  }

  .col-details {
    order: 3;
  }

  .preview-cta-wrap {
    order: 4;
  }
}

@media (max-width: 600px) {
  .character-selection-screen {
    padding: 1rem 0.75rem 2rem;
  }

  .header-main-title {
    font-size: 1.8rem;
  }

  .roster-cards-stack {
    flex-direction: column;
  }

  .btn-choose-vessel {
    width: 100%;
    font-size: 0.95rem;
    padding: 0.9rem 1.5rem;
  }
}
</style>
