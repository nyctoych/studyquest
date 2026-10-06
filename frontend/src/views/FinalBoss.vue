<template>
  <div
    class="final-boss-screen"
    :class="{
      'is-in-battle': phase === 'battle',
      'is-in-revelation': phase === 'father_revelation'
    }"
    :style="{ '--boss-accent': bossMeta.accentColor, '--boss-glow': bossMeta.accentGlow }"
  >
    <div class="boss-bg"></div>

    <div
      class="boss-content"
      :class="{
        'is-battle-mode': phase === 'battle',
        'is-revelation-mode': phase === 'father_revelation'
      }"
    >
      <!-- LOADING STATE -->
      <div v-if="phase === 'loading'" class="boss-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNSEALING THE BLACK ARCHIVE…</p>
      </div>

      <!-- LOCKED STATE -->
      <div v-else-if="phase === 'locked'" class="boss-locked-panel gothic-panel animate-scale-up">
        <div class="locked-icon-crest">
          <PixelEmoji emoji="lock" size="lg" />
        </div>
        <h2 class="locked-title">THE BLACK ARCHIVE IS SEALED</h2>
        <p class="locked-sub">
          {{ lockedMessage || 'You must conquer Chapter 12: The Mask of a Thousand Selves before stepping behind the written world.' }}
        </p>
        <button class="btn btn-gothic-crimson btn-lg" @click="goToAdventure">
          ← RETURN TO ADVENTURE
        </button>
      </div>

      <!-- THE FATHER'S REVELATION (PRE-BATTLE RESTORED SEQUENCE) -->
      <div v-else-if="phase === 'father_revelation'" class="father-revelation-scene gothic-panel animate-scale-up">
        <div class="fr-apex-tag">
          <span class="fr-key-icon">🗝</span>
          <span class="fr-tag-text font-pixel">THE BLACK ARCHIVE — APEX GATE</span>
        </div>

        <h1 class="fr-main-title font-pixel">THE FATHER'S REVELATION</h1>

        <p class="fr-subtitle">
          Before the final chamber unlocks, an unexpected figure steps from the shadows.
        </p>

        <div class="fr-portrait-frame">
          <img
            :src="resolveAsset('assets/game/market-npc-raven.png')"
            alt="Raven"
            class="fr-portrait-img pixel-art"
          />
        </div>

        <div class="fr-dialogue-box" @click="nextFatherDialogue" title="Click to advance dialogue">
          <div class="fr-speaker-row">
            <span class="fr-speaker-name font-pixel">{{ currentFatherDialogue.speaker }}</span>
            <span class="fr-speaker-role font-pixel">{{ currentFatherDialogue.role }}</span>
          </div>

          <div class="fr-dialogue-text font-pixel">
            “{{ currentFatherDialogue.text }}”
          </div>

          <div class="fr-dots-row">
            <span
              v-for="idx in 8"
              :key="idx"
              class="fr-dot"
              :class="{ 'is-active': fatherStep === (idx - 1) }"
            ></span>
          </div>
        </div>

        <div class="fr-actions-row">
          <button
            class="btn btn-gothic-crimson btn-lg fr-proceed-btn"
            :class="{ 'fr-unseal-pulse': fatherStep === 7 }"
            :disabled="submittingRevelation"
            @click.stop="nextFatherDialogue"
          >
            <span v-if="fatherStep < 7">NEXT DIALOGUE →</span>
            <span v-else>UNSEAL THE ARCHIVE & ENTER BATTLE →</span>
          </button>
          <span class="fr-click-hint font-pixel">✦ Click dialogue box or button to continue · [SPACE] / [ENTER] ✦</span>
        </div>

        <div v-if="alreadyRevealed" class="fr-skip-row">
          <button class="fr-skip-btn" @click.stop="proceedToCreatorIntro">
            SKIP TO CHAMBER ▸
          </button>
        </div>
      </div>

      <!-- CINEMATIC INTRO -->
      <div v-else-if="phase === 'intro'" class="boss-intro-scene gothic-panel animate-scale-up">
        <div class="boss-act-strip">
          <span class="act-rune">✦</span>
          <span>THE FINAL ENCOUNTER — BEYOND MINDREALM</span>
          <span class="act-rune">✦</span>
        </div>

        <div class="boss-altar-area">
          <div class="boss-portrait-altar">
            <img
              :src="resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif')"
              alt="The Creator"
              class="boss-portrait-art pixel-art animate-boss-float"
            />
            <div class="boss-aura-ring"></div>
          </div>
        </div>

        <div class="boss-identity-card">
          <span class="boss-domain-badge">APEX TRIAL — THE GENESIS ARCHIVE</span>
          <h1 class="boss-title-name">THE CREATOR</h1>
          <span class="boss-guardian-title">Author of Mindrealm & Origin of the Fracture</span>

          <div class="boss-dialogue-card">
            <p class="intro-dialogue-quote">
              "He wrote the world. He wrote the hero. He wrote the ending. But he could not write the choice."
            </p>
            <p class="intro-lore-body">
              {{ bossMeta.description }}
            </p>
          </div>

          <div class="boss-stakes-row">
            <div class="stake-card seal-stake">
              <span class="stake-icon">🔮</span>
              <div class="stake-info">
                <span class="stake-label">ULTIMATE RELIC</span>
                <strong class="stake-val">Mind Seal of Total Synthesis</strong>
              </div>
            </div>

            <div class="stake-card shard-stake">
              <span class="stake-icon">⚡</span>
              <div class="stake-info">
                <span class="stake-label">ROOT OF DISTORTION</span>
                <strong class="stake-val">Core of the Ancient Fracture</strong>
              </div>
            </div>
          </div>

          <div class="boss-stats-rewards-row">
            <div class="bs-stat-pill">
              <span class="bs-label">ARCHIVE ESSENCE:</span>
              <span class="bs-val">1,500 HP</span>
            </div>
            <div class="bs-stat-pill">
              <span class="bs-label">REWARDS:</span>
              <span class="bs-val text-gold">+2,500 XP · +1,500 GOLD</span>
            </div>
          </div>

          <div class="boss-intro-actions">
            <button class="btn btn-gothic-stone" @click="goToAdventure">
              ← MAP
            </button>
            <button class="btn btn-gothic-stone" @click="replayFatherRevelation">
              📜 FATHER'S REVELATION
            </button>
            <button class="btn btn-gothic-crimson btn-lg proceed-btn" @click="startFinalBossBattle">
              <span>CONFRONT THE CREATOR</span>
              <span class="btn-arrow">▸</span>
            </button>
          </div>
        </div>
      </div>

      <!-- BATTLE ARENA -->
      <div v-else-if="phase === 'battle'" class="boss-battle-arena animate-scale-up">
        <AdventureBattlefield
          v-if="!battleComplete"
          :is-boss="true"
          :is-final-boss="true"
          :initial-boss-phase="finalBossPhase"
          :chapter-id="12"
          :chapter-title="'The Black Archive'"
          :part-title="'THE FINAL ENCOUNTER — THE CREATOR'"
          :enemy-name="'The Creator'"
          :enemy-title="'Author of Mindrealm'"
          :boss-meta="bossMeta"
          :boss-hp="bossHP"
          :boss-max-hp="bossMaxHP"
          :player-hp="playerHP"
          :player-max-hp="playerMaxHP"
          :player-level="adventure.playerLevel"
          :attack-power="adventure.characterDamage"
          :critical-attack-power="adventure.characterCritDamage"
          :damage-dealt="dmgToEnemy"
          :damage-taken="dmgToPlayer"
          :damage-type="lastDmgType"
          :questions="questions"
          :current-q-index="currentQIdx"
          :current-q="currentQ"
          :answered="answered"
          :selected-key="selectedKey"
          :last-correct="lastCorrect"
          :is-last-question="currentQIdx >= questions.length - 1"
          :visual-phase="visualPhase"
          :scripted-speech="scriptedSpeech"
          :enemy-defeated="enemyDefeated"
          @answer="handleAnswer"
          @answered="onQuestionAnswered"
          @next="nextQuestion"
          @final-victory="onCreatorBattleDefeated"
        />

        <!-- Submitting Final Victory Notice -->
        <div v-if="submittingVictory" class="boss-submitting gothic-panel animate-scale-up">
          <div class="loading-spinner"></div>
          <p class="pixel-text">UNRAVELING THE BLACK ARCHIVE & COMMUNICATING WITH GENESIS…</p>
        </div>
      </div>

      <!-- DEFEAT STATE -->
      <div v-else-if="phase === 'defeat'" class="boss-defeat gothic-panel animate-scale-up">
        <BattleResultCharacter result="defeat" size="xl" />
        <h2 class="defeat-title font-pixel text-crimson">YOU ARE DEFEATED</h2>
        <p class="defeat-sub">
          Your strength has been exhausted. Recover at least 1 HP before returning to challenge The Creator.
        </p>

        <!-- Recovery Information (Rules 2, 9, 10, 25) -->
        <div class="defeat-final-recovery-box">
          <div class="rec-metric">
            <span class="rec-label font-pixel">HEALTH</span>
            <span class="rec-val text-red font-pixel">0 / {{ healthStore.maxHp }} HP</span>
          </div>
          <div class="rec-metric">
            <span class="rec-label font-pixel">RECOVERY</span>
            <span class="rec-val text-gold font-pixel">+{{ healthStore.regenRate }} HP / HR</span>
          </div>
          <div class="rec-metric">
            <span class="rec-label font-pixel">NEXT RECOVERY</span>
            <span class="rec-val text-teal font-pixel">{{ healthStore.formattedCountdown }}</span>
          </div>
        </div>

        <div class="defeat-actions">
          <button
            class="btn btn-gothic-crimson"
            :disabled="healthStore.isDefeated"
            @click="startFinalBossBattle"
          >
            {{ healthStore.isDefeated ? '🔒 RETRY LOCKED (0 HP)' : 'RE-ATTEMPT TRIAL' }}
          </button>
          <button class="btn btn-gothic-crimson" @click="goToShop">
            🧪 USE HEALTH POTION (+200 HP)
          </button>
          <button class="btn btn-gothic-stone" @click="goToAdventure">← RETURN TO MAP</button>
        </div>
      </div>

      <!-- STORY SCENE 1: CREATOR DEFEAT DIALOGUE -->
      <FinalStoryScene
        v-else-if="phase === 'creator_dialogue'"
        :title="'THE CREATOR — REALIZATION'"
        :background="resolveAsset('assets/adventure/battlefields/final-black-archive.jpg')"
        :tone="'archive'"
        :lines="creatorDefeatLines"
        @done="onCreatorDialogueDone"
      />

      <!-- STORY SCENE 2: WORLD COLLAPSE -->
      <FinalStoryScene
        v-else-if="phase === 'world_collapse'"
        :title="'THE BLACK ARCHIVE — COLLAPSE'"
        :background="resolveAsset('assets/adventure/battlefields/final-black-archive.jpg')"
        :tone="'archive'"
        :collapse="true"
        :fx-phase="3"
        :lines="worldCollapseLines"
        @done="onWorldCollapseDone"
      />

      <!-- STORY SCENE 3: MERCHANT (RAVEN) REVEAL -->
      <FinalStoryScene
        v-else-if="phase === 'merchant_reveal'"
        :title="'THE MARKETPLACE — THE MERCHANT\'S CONFESSION'"
        :background="resolveAsset('assets/adventure/battlefields/final-black-archive.jpg')"
        :tone="'calm'"
        :lines="merchantRevealLines"
        @done="onMerchantRevealDone"
      />

      <!-- STORY SCENE 4: QUIET EPILOGUE -->
      <FinalStoryScene
        v-else-if="phase === 'epilogue'"
        :title="'EPILOGUE — THE WRITING DESK'"
        :background="resolveAsset('assets/adventure/backgrounds/final-epilogue-room.jpg')"
        :tone="'epilogue'"
        :lines="epilogueLines"
        @done="onEpilogueDone"
      />

      <!-- FINAL CAMPAIGN COMPLETE SCREEN -->
      <div v-else-if="phase === 'campaign_complete'" class="campaign-complete-panel gothic-panel animate-scale-up">
        <div class="complete-header">
          <div class="complete-crest">🕊️</div>
          <span class="complete-eyebrow">CAMPAIGN COMPLETE</span>
          <h1 class="complete-title">THE LAST PAGE IS TURNED</h1>
          <p class="complete-tagline">
            The Black Archive rests in quiet memory. You walked every page of Mindrealm to its true conclusion.
          </p>
        </div>

        <div class="complete-relics-strip">
          <div class="complete-relic-card">
            <span class="relic-icon">🔮</span>
            <div class="relic-meta">
              <span class="relic-label">ULTIMATE RELIC SECURED</span>
              <strong class="relic-name">Mind Seal of Total Synthesis</strong>
            </div>
          </div>
          <div class="complete-relic-card">
            <span class="relic-icon">⚡</span>
            <div class="relic-meta">
              <span class="relic-label">FRACTURE HEALED</span>
              <strong class="relic-name">Core of the Ancient Fracture</strong>
            </div>
          </div>
        </div>

        <!-- Rewards Display -->
        <div class="complete-rewards-box">
          <span class="rewards-heading">CAMPAIGN REWARDS</span>
          <div class="rewards-grid">
            <div class="reward-pill">
              <span class="reward-icon">⭐</span>
              <span class="reward-val">+{{ completionResult?.rewards?.xp || 2500 }} XP</span>
            </div>
            <div class="reward-pill">
              <span class="reward-icon">🪙</span>
              <span class="reward-val">+{{ completionResult?.rewards?.coins || 1500 }} GOLD</span>
            </div>
            <div class="reward-pill">
              <span class="reward-icon">🖋️</span>
              <span class="reward-val">THE FINAL AUTHOR</span>
            </div>
            <div class="reward-pill">
              <span class="reward-icon">📖</span>
              <span class="reward-val">THE LAST PAGE</span>
            </div>
          </div>
          <p v-if="completionResult?.already_completed" class="replay-notice">
            Replay clear verified. The Black Archive and Citadel remain at peace.
          </p>
        </div>

        <!-- Actions -->
        <div class="complete-actions">
          <button class="btn btn-gothic-crimson btn-lg" @click="goToCitadel">
            🏰 RETURN TO CITADEL
          </button>
          <button class="btn btn-gothic-stone btn-lg" @click="goToChronicle">
            📜 VIEW CHRONICLE
          </button>
          <button class="btn btn-gothic-stone btn-lg" @click="goToAdventure">
            🗺️ ADVENTURE MAP
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAdventureStore } from '../stores/adventure'
import { useTransitionStore } from '../stores/transition'
import { useToastStore } from '../stores/toast'
import { useHealthStore } from '../stores/health'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import AdventureBattlefield from '../components/adventure/AdventureBattlefield.vue'
import BattleResultCharacter from '../components/adventure/BattleResultCharacter.vue'
import FinalStoryScene from '../components/adventure/FinalStoryScene.vue'
import { resolveAsset, getFinalBoss } from '../services/bossMapping'

const router = useRouter()
const auth = useAuthStore()
const adventure = useAdventureStore()
const transitionStore = useTransitionStore()
const toast = useToastStore()
const healthStore = useHealthStore()

const bossMeta = getFinalBoss()

// State Machine Phases:
// 'loading' | 'locked' | 'father_revelation' | 'intro' | 'battle' | 'defeat' | 'creator_dialogue' | 'world_collapse' | 'merchant_reveal' | 'epilogue' | 'campaign_complete'
const phase = ref('loading')
const lockedMessage = ref('')
const fatherStep = ref(0)
const alreadyRevealed = ref(false)
const submittingRevelation = ref(false)

const fatherDialogues = [
  {
    speaker: 'Raven',
    role: 'The Merchant',
    text: 'I knew you would make it here. From the moment you stepped into my shop, I recognized the gait. The expression. The questions you asked.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'I am his father.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'My son was a writer. He created the Mindrealm. At first, it was simply a story—a grand fantasy world filled with towers, riddles, and ancient guardians.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'But over time, he began putting more and more of himself into it. When life became difficult to manage, the stories became his sanctuary. A place where every problem had a classification. Where every pain could be given a name and mastered with logic.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'Those twelve guardians you faced were never monsters. They were fictional manifestations—symbolic reflections of themes he wrestled to understand: time, perception, extremes, loss, worry, control, identity.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'And then... he wrote you.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'You were not part of the first draft. When he realized he was trapped in his own endless revisions, he wrote a protagonist capable of traveling through every chapter, facing every guardian, and reaching this chamber.'
  },
  {
    speaker: 'Raven',
    role: 'The Father of the Creator',
    text: 'You were his way of writing an ending. The door is unlocked now. Go inside. Face the author.'
  }
]

const currentFatherDialogue = computed(() => fatherDialogues[fatherStep.value] || fatherDialogues[0])

async function nextFatherDialogue() {
  if (fatherStep.value < 7) {
    fatherStep.value++
  } else {
    await unsealArchiveAndEnterBattle()
  }
}

function replayFatherRevelation() {
  fatherStep.value = 0
  phase.value = 'father_revelation'
}

function proceedToCreatorIntro() {
  phase.value = 'intro'
}

async function unsealArchiveAndEnterBattle() {
  if (submittingRevelation.value) return
  submittingRevelation.value = true
  try {
    const res = await adventure.completeFatherRevelation()
    alreadyRevealed.value = true
    if (res?.new_achievements?.length) {
      toast.success('ACHIEVEMENT UNLOCKED: The Truth Behind The Merchant!')
    }
  } catch (err) {
    console.warn('Error completing father revelation:', err)
  } finally {
    submittingRevelation.value = false
  }

  // Atmospheric GameTransition
  transitionStore.destination = {
    title: 'UNSEALING THE BLACK ARCHIVE',
    subtitle: 'The door unlocks. Face the author of Mindrealm...',
    icon: 'castle',
    themeColor: 'crimson',
    type: 'gate'
  }
  transitionStore.isTransitioning = true
  await new Promise(resolve => setTimeout(resolve, 200))
  transitionStore.isCovered = true
  phase.value = 'intro'
  await new Promise(resolve => setTimeout(resolve, 150))
  transitionStore.isCovered = false
  await new Promise(resolve => setTimeout(resolve, 220))
  transitionStore.isTransitioning = false
}

// Combat State
const bossHP = ref(1500)
const bossMaxHP = ref(1500)
const playerHP = ref(150)
const playerMaxHP = ref(150)
const finalBossPhase = ref(1)
const questions = ref([])
const currentQIdx = ref(0)
const answered = ref(false)
const selectedKey = ref(null)
const lastCorrect = ref(false)
const battleComplete = ref(false)
const battleResult = ref(null)
const dmgToEnemy = ref(0)
const dmgToPlayer = ref(0)
const lastDmgType = ref('normal')
const enemyDefeated = ref(false)
const submittingVictory = ref(false)
const completionResult = ref(null)

// Dialogue & Presentation State
const scriptedSpeech = ref('')
const dialogueFlags = ref({
  firstHit: false,
  firstWrong: false,
  phase2: false,
  phase3: false,
  nearDefeat: false,
})

const currentQ = computed(() => questions.value[currentQIdx.value] || null)

// Visual Phase computed from HP% (1: >66%, 2: 33-66%, 3: <33%)
const visualPhase = computed(() => {
  if (bossMaxHP.value <= 0) return 1
  const pct = (bossHP.value / bossMaxHP.value) * 100
  if (pct > 66) return 1
  if (pct > 33) return 2
  return 3
})

function getCharacterBaseHealth() {
  const c = auth.selectedCharacter || auth.user?.selected_character
  if (c?.health) return Number(c.health)
  const code = (c?.code || c?.class_type || 'mage').toLowerCase()
  if (code === 'melee') return 200
  if (code === 'range') return 110
  return 150
}

// ── NARRATIVE SCENE SCRIPT DEFINITIONS ───────────────────────
const creatorDefeatLines = computed(() => [
  {
    speaker: 'The Creator',
    role: 'Author of Mindrealm',
    portrait: resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif'),
    text: 'The pen has fallen.'
  },
  {
    speaker: 'The Creator',
    role: 'Author of Mindrealm',
    portrait: resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif'),
    text: 'I spent so long trying to write a world where nothing hurt... where every grief was categorized, and every fear had a clean definition.'
  },
  {
    speaker: 'The Creator',
    role: 'Author of Mindrealm',
    portrait: resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif'),
    text: 'I believed if I wrote the perfect script, I could fix what was broken in me.'
  },
  {
    speaker: 'The Creator',
    role: 'Author of Mindrealm',
    portrait: resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif'),
    text: 'A story without choices is just a cage. You showed me that.'
  },
  {
    speaker: 'The Creator',
    role: 'Author of Mindrealm',
    portrait: resolveAsset('assets/BOSS/WRITER - FINAL BOSS/The Creator - Dialogue State.gif'),
    text: 'Look... the binding is coming apart. The ink is running free.'
  }
])

const worldCollapseLines = computed(() => [
  {
    text: 'The Black Archive begins to dissolve into loose pages and scattered ink.'
  },
  {
    text: 'The architecture of Mindrealm crumbles around you—towers of text and memory falling into white silence.'
  },
  {
    text: 'And in the unraveling script, you see the truth written across the margins:'
  },
  {
    text: 'You were written, too. The hero, the armor, the journey through twelve domains... all crafted by an author desperately trying to heal.'
  },
  {
    text: 'Yet every answer you chose was your own. The script guided you, but consciousness was yours.'
  }
])

const merchantRevealLines = computed(() => [
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'You found your way back here.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'I suppose you know now. That world wasn\'t a nightmare. It was my son\'s.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'He created Mindrealm when the world outside became too heavy to carry. He wrote rules, systems, guardians... trying to make sense of what he couldn\'t control.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'He kept rewriting the ending. He wrote himself into it as the Creator. And then... he wrote you.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'He left us before he could finish the manuscript. He died by suicide, leaving behind only these books, these questions, and a father who couldn\'t find the right words in time.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'I stayed in this market because every item, every page he imagined was still here. I wanted to keep his world alive. But a world frozen in ink isn\'t life.'
  },
  {
    speaker: 'Raven',
    role: 'Merchant & Father',
    portrait: resolveAsset('assets/game/market-npc-raven.png'),
    text: 'Thank you for walking his pages to the very end. You didn\'t just conquer his trial. You listened to him.'
  }
])

const epilogueLines = computed(() => [
  {
    text: 'A quiet study. A wooden writing desk touched by late afternoon light.'
  },
  {
    text: 'A chair stands empty, but the window is open to the breeze.'
  },
  {
    text: 'Mindrealm does not vanish—it rests.'
  },
  {
    text: 'The questions were real. The struggle was real. And though every line of the journey was once penned by someone else...'
  },
  {
    text: '...the choices were yours.'
  },
  {
    text: 'Some stories end unfinished. Others continue in the people who remember them.'
  }
])

// ── LIFECYCLE ────────────────────────────────────────────────
onMounted(async () => {
  const finalRes = await adventure.loadFinalBoss()
  if (!finalRes.success) {
    if (finalRes.locked) {
      phase.value = 'locked'
      lockedMessage.value = finalRes.message || 'You must defeat Chapter 12\'s normal boss: The Mask of a Thousand Selves before confronting The Creator.'
      return
    }
    toast.warning(finalRes.message || 'Failed to open the Black Archive.')
    router.push('/adventure')
    return
  }

  const state = finalRes.state || adventure.finalBoss
  alreadyRevealed.value = Boolean(state?.father_revelation_completed)

  // Father's Revelation is automatically displayed when "Enter the Black Archive" button is clicked
  fatherStep.value = 0
  phase.value = 'father_revelation'

  window.addEventListener('keydown', onGlobalKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', onGlobalKeyDown)
})

function onGlobalKeyDown(e) {
  if (phase.value !== 'father_revelation') return
  if (e.key === ' ' || e.key === 'Enter' || e.key === 'ArrowRight') {
    e.preventDefault()
    nextFatherDialogue()
  }
}

// ── BATTLE METHODS ───────────────────────────────────────────
async function startFinalBossBattle() {
  phase.value = 'loading'
  currentQIdx.value = 0
  answered.value = false
  selectedKey.value = null
  lastCorrect.value = false
  battleComplete.value = false
  battleResult.value = null
  enemyDefeated.value = false
  submittingVictory.value = false
  scriptedSpeech.value = ''
  dialogueFlags.value = {
    firstHit: false,
    firstWrong: false,
    phase2: false,
    phase3: false,
    nearDefeat: false,
  }

  if (healthStore.isDefeated) {
    toast.error('Your strength has been exhausted! Recover at least 1 HP before confronting The Creator.')
    return
  }

  const startRes = await adventure.startBattleSession({
    encounter_type: 'final_boss',
    mode: 'final_boss',
  })

  if (!startRes?.success) {
    if (startRes?.allowed === false || startRes?.reason === 'insufficient_health') {
      healthStore.applyHealthData(startRes)
      toast.error(startRes.message || 'Your health is 0. Recover at least 1 HP before entering battle.')
      phase.value = 'intro'
      return
    }
    if (startRes?.locked) {
      phase.value = 'locked'
      lockedMessage.value = startRes.message || 'The Black Archive remains sealed.'
      return
    }
    toast.error(startRes?.message || 'Failed to start battle with The Creator.')
    phase.value = 'intro'
    return
  }

  // Set authoritative battle state
  playerHP.value = Number(startRes.player_current_health ?? getCharacterBaseHealth())
  playerMaxHP.value = Number(startRes.player_max_health ?? getCharacterBaseHealth())
  bossHP.value = Number(startRes.enemy_current_health ?? 1500)
  bossMaxHP.value = Number(startRes.enemy_max_health ?? 1500)
  if (startRes.final_boss_phase) {
    finalBossPhase.value = Number(startRes.final_boss_phase)
  }
  questions.value = startRes.questions || []
  healthStore.updateFromBattleAction(startRes)

  phase.value = 'battle'
}

async function handleAnswer(payload) {
  const key = typeof payload === 'object' ? payload.key : payload
  const isCritical = typeof payload === 'object' ? Boolean(payload.isCritical) : false
  const resolve = typeof payload === 'object' ? payload.resolve : null

  selectedKey.value = key

  const serverRes = await adventure.submitBattleAction({
    battle_id: adventure.activeBattleId,
    question_id: currentQ.value?.id,
    question_index: currentQIdx.value,
    selected_choice: key,
    is_critical: isCritical,
    critical_active: isCritical,
    is_boss: true,
    is_final_boss: true,
  })

  if (resolve) {
    resolve(serverRes)
  }
}

async function onQuestionAnswered(serverRes) {
  answered.value = true
  lastCorrect.value = Boolean(serverRes?.is_correct ?? serverRes?.correct)

  if (serverRes) {
    healthStore.updateFromBattleAction(serverRes)
    if (serverRes.player_current_health !== undefined) playerHP.value = Number(serverRes.player_current_health)
    if (serverRes.player_max_health !== undefined) playerMaxHP.value = Number(serverRes.player_max_health)
    if (serverRes.enemy_current_health !== undefined) bossHP.value = Number(serverRes.enemy_current_health)
    if (serverRes.enemy_max_health !== undefined) bossMaxHP.value = Number(serverRes.enemy_max_health)
    if (serverRes.final_boss_phase !== undefined) finalBossPhase.value = Number(serverRes.final_boss_phase)
    dmgToEnemy.value = Number(serverRes.damage_dealt || 0)
    dmgToPlayer.value = Number(serverRes.player_damage_taken || 0)
    lastDmgType.value = serverRes.damage_type || 'normal'

    if (serverRes.correct_answer && currentQ.value) {
      currentQ.value.correct_answer = serverRes.correct_answer
    }
    if (serverRes.explanation && currentQ.value) {
      currentQ.value.explanation = serverRes.explanation
    }
  }

  // Trigger dialogue at key moments (used once)
  checkScriptedDialogue(lastCorrect.value)

  // Check victory / defeat conditions
  if (playerHP.value <= 0) {
    battleComplete.value = true
    battleResult.value = 'defeat'
    healthStore.currentHp = 0
    phase.value = 'defeat'
  } else if (bossHP.value <= 0 || serverRes?.battle_result === 'victory') {
    // Creator HP reached 0: AdventureBattlefield handles the cinematic defeat sequence
    // and temporary backstory placeholder before emitting @final-victory.
    enemyDefeated.value = true
  }
}

async function onCreatorBattleDefeated(serverRes) {
  battleComplete.value = true
  battleResult.value = 'victory'
  enemyDefeated.value = true
  await finalizeCreatorVictory()
}

function checkScriptedDialogue(isCorrect) {
  if (isCorrect && !dialogueFlags.value.firstHit && dmgToEnemy.value > 0) {
    dialogueFlags.value.firstHit = true
    scriptedSpeech.value = 'You remember what was written... but you are answering differently.'
    return
  }

  if (!isCorrect && !dialogueFlags.value.firstWrong) {
    dialogueFlags.value.firstWrong = true
    scriptedSpeech.value = 'The script asserts itself. Everything returns to the ink.'
    return
  }

  const hpPct = bossMaxHP.value > 0 ? (bossHP.value / bossMaxHP.value) * 100 : 100

  if (hpPct <= 15 && !dialogueFlags.value.nearDefeat) {
    dialogueFlags.value.nearDefeat = true
    scriptedSpeech.value = 'He wrote the world. He wrote the hero. He wrote the ending. But he could not write the choice.'
    return
  }

  if (hpPct <= 33 && !dialogueFlags.value.phase3) {
    dialogueFlags.value.phase3 = true
    scriptedSpeech.value = 'Phase III: The Last Page. The ink is drying. The archive cannot hold us both.'
    return
  }

  if (hpPct <= 66 && !dialogueFlags.value.phase2) {
    dialogueFlags.value.phase2 = true
    scriptedSpeech.value = 'Phase II: The Unfinished Script. I erased entire chapters... yet you still stand.'
    return
  }
}

async function finalizeCreatorVictory() {
  submittingVictory.value = true
  try {
    const res = await adventure.completeFinalBossBattle({ battle_id: adventure.activeBattleId })
    if (res.success) {
      completionResult.value = res
      if (res.first_clear) {
        toast.success('THE CREATOR IS DEFEATED. Mindrealm\'s true story begins to unfold.')
      }
    } else {
      console.warn('Victory submission status:', res.message)
    }
  } catch (err) {
    console.error('Error finalizing Final Boss battle:', err)
  } finally {
    submittingVictory.value = false
    // Transition to the narrative sequence
    phase.value = 'creator_dialogue'
  }
}

function nextQuestion() {
  if (battleComplete.value) return
  currentQIdx.value++
  answered.value = false
  selectedKey.value = null
}

// ── NARRATIVE TRANSITIONS ────────────────────────────────────
function onCreatorDialogueDone() {
  phase.value = 'world_collapse'
}

async function onWorldCollapseDone() {
  await adventure.advanceFinalStory('merchant_reveal')
  phase.value = 'merchant_reveal'
}

async function onMerchantRevealDone() {
  await adventure.advanceFinalStory('epilogue')
  phase.value = 'epilogue'
}

async function onEpilogueDone() {
  await adventure.advanceFinalStory('complete')
  phase.value = 'campaign_complete'
}

// ── NAVIGATION ACTIONS ───────────────────────────────────────
async function goToAdventure() {
  await transitionStore.triggerTravel('/adventure', router)
}

async function goToCitadel() {
  await transitionStore.triggerTravel('/world', router)
}

async function goToChronicle() {
  await transitionStore.triggerTravel('/chronicle', router)
}

function goToShop() {
  transitionStore.triggerTravel('/shop', router)
}
</script>

<style scoped>
.final-boss-screen {
  min-height: 100vh;
  position: relative;
  background: var(--gothic-void, #050608);
  user-select: none;
  overflow-y: auto;
  padding-top: calc(var(--hud-height, 60px) + 1.25rem);
  box-sizing: border-box;
}
.boss-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(ellipse at 50% 30%, var(--boss-glow, rgba(225, 29, 72, 0.25)) 0%, transparent 65%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.95) 0%, #050608 100%);
  pointer-events: none;
}
.final-boss-screen.is-in-revelation {
  padding-top: var(--hud-height, 60px);
}
.final-boss-screen.is-in-battle {
  padding-top: var(--hud-height, 60px);
  margin-top: 0;
}

/* Defeat Final Recovery Box */
.defeat-final-recovery-box {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  background: rgba(18, 5, 10, 0.95);
  border: 1px solid rgba(225, 29, 72, 0.5);
  border-radius: 6px;
  padding: 0.65rem;
  margin: 1rem 0;
  width: 100%;
  max-width: 480px;
}
.rec-metric {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
}
.rec-label {
  font-size: 0.48rem;
  color: #9ca3af;
  letter-spacing: 0.05em;
}
.rec-val {
  font-size: 0.65rem;
}
.text-red { color: #f87171; }
.text-gold { color: #fbbf24; }
.text-teal { color: #2dd4bf; }
.text-crimson { color: #f43f5e; }

.boss-content {
  position: relative;
  z-index: 1;
  max-width: 780px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
  box-sizing: border-box;
  transition: max-width 0.25s ease, padding 0.25s ease;
}

.boss-content.is-revelation-mode {
  max-width: min(1720px, 97vw);
  width: 97%;
  margin: 0 auto;
  padding: 0.35rem 1rem 0.85rem;
}

.boss-content.is-battle-mode {
  max-width: 100% !important;
  width: 100% !important;
  margin: 0 !important;
  padding: 0 0.5rem 0.25rem !important;
  box-sizing: border-box;
}

.boss-loading {
  text-align: center;
  padding: 5rem 1rem;
}
.loading-spinner {
  width: 44px;
  height: 44px;
  border: 3px solid #232533;
  border-top-color: var(--color-crimson-bright, #e11d48);
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

/* Locked warning panel */
.boss-locked-panel {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--color-crimson-bright, #e11d48);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}
.locked-icon-crest {
  width: 64px;
  height: 64px;
  background: #181924;
  border: 2px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
}
.locked-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.1rem;
  color: var(--color-crimson-bright, #e11d48);
}
.locked-sub {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  color: var(--color-bone-dark, #8c8273);
  max-width: 520px;
  line-height: 1.6;
}

/* ── THE FATHER'S REVELATION SCENE ─────────────────── */
.father-revelation-scene {
  background: rgba(13, 8, 22, 0.95);
  border: 1px solid rgba(168, 85, 247, 0.35);
  border-top: 3px solid #a855f7;
  padding: 0.75rem 2rem 0.85rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 12px 50px rgba(0, 0, 0, 0.9), 0 0 35px rgba(168, 85, 247, 0.18);
  position: relative;
  width: 100%;
  max-width: min(1620px, 98%);
  margin: 0.5rem auto 0.6rem;
  border-radius: 8px;
  box-sizing: border-box;
}

.fr-apex-tag {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 0.3rem;
}
.fr-key-icon {
  font-size: 0.85rem;
  color: #fbbf24;
}
.fr-tag-text {
  font-size: 0.62rem;
  color: #fbbf24;
  letter-spacing: 2px;
}

.fr-main-title {
  font-size: clamp(1.4rem, 2.4vw, 2.1rem);
  color: #f5f3ff;
  text-shadow: 0 0 18px rgba(192, 132, 252, 0.75), 0 0 40px rgba(168, 85, 247, 0.45);
  letter-spacing: 3px;
  margin: 0 0 0.25rem;
  text-align: center;
}

.fr-subtitle {
  font-family: var(--font-pixel, monospace);
  font-size: clamp(0.6rem, 0.95vw, 0.72rem);
  color: #c4b5fd;
  text-align: center;
  max-width: 1200px;
  line-height: 1.5;
  margin: 0 0 0.5rem;
}

.fr-portrait-frame {
  width: clamp(115px, 13vh, 145px);
  height: clamp(115px, 13vh, 145px);
  background: rgba(8, 4, 15, 0.95);
  border: 2px solid rgba(168, 85, 247, 0.5);
  box-shadow: 0 0 25px rgba(168, 85, 247, 0.35), inset 0 0 15px rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin-bottom: 0.55rem;
  border-radius: 4px;
}
.fr-portrait-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
}

.fr-dialogue-box {
  width: 100%;
  max-width: min(1500px, 98%);
  background: rgba(18, 12, 30, 0.92);
  border: 1px solid rgba(168, 85, 247, 0.35);
  border-radius: 6px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(168, 85, 247, 0.2);
  padding: 0.95rem 2rem 0.8rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 100px;
  text-align: left;
  cursor: pointer;
  box-sizing: border-box;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.fr-dialogue-box:hover {
  border-color: rgba(168, 85, 247, 0.6);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.7), 0 0 15px rgba(168, 85, 247, 0.2);
}

.fr-speaker-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.45rem;
}
.fr-speaker-name {
  color: #fbbf24;
  font-size: 0.9rem;
  letter-spacing: 1px;
}
.fr-speaker-role {
  color: #c084fc;
  font-size: 0.75rem;
  letter-spacing: 1px;
}

.fr-dialogue-text {
  font-family: var(--font-pixel, monospace);
  color: #f8fafc;
  font-size: clamp(0.82rem, 1.15vw, 0.98rem);
  line-height: 1.75;
  letter-spacing: 0.4px;
  margin-bottom: 0.55rem;
  min-height: 2.7rem;
}

.fr-dots-row {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.6rem;
  margin-top: auto;
  padding-top: 0.25rem;
}
.fr-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #3b1d60;
  transition: all 0.25s ease;
}
.fr-dot.is-active {
  background: #fbbf24;
  box-shadow: 0 0 10px #fbbf24, 0 0 20px rgba(251, 191, 36, 0.6);
  transform: scale(1.3);
}

.fr-actions-row {
  margin-top: 0.55rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}
.fr-proceed-btn {
  min-width: 290px;
  letter-spacing: 1.5px;
  font-family: var(--font-pixel, monospace);
  font-size: 0.76rem;
  background: linear-gradient(180deg, #5b1318 0%, #350c10 100%);
  border: 1px solid rgba(220, 38, 38, 0.5);
  color: #ffffff;
  padding: 0.7rem 2rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
  border-radius: 6px;
}
.fr-proceed-btn:hover:not(:disabled) {
  background: linear-gradient(180deg, #781a20 0%, #4c1015 100%);
  border-color: #ef4444;
  box-shadow: 0 0 20px rgba(220, 38, 38, 0.6);
  transform: translateY(-1px);
}
.fr-unseal-pulse {
  animation: frUnsealPulse 2s infinite alternate;
}
@keyframes frUnsealPulse {
  0% { box-shadow: 0 0 12px rgba(220, 38, 38, 0.4); }
  100% { box-shadow: 0 0 28px rgba(220, 38, 38, 0.85), 0 0 40px rgba(168, 85, 247, 0.45); }
}

.fr-click-hint {
  font-size: 0.52rem;
  color: #a78bfa;
  letter-spacing: 0.5px;
}

.fr-skip-row {
  margin-top: 0.25rem;
}
.fr-skip-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  letter-spacing: 1.5px;
  cursor: pointer;
  text-decoration: underline;
  transition: color 0.2s ease;
}
.fr-skip-btn:hover {
  color: #e2e8f0;
}

/* ── CINEMATIC INTRO ────────────────────────── */
.boss-intro-scene {
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--boss-accent, #e11d48);
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.85);
}
.boss-act-strip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 2px;
}
.boss-altar-area {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem 0;
}
.boss-portrait-altar {
  position: relative;
  width: 190px;
  height: 190px;
  background: radial-gradient(circle, #250812 30%, #0d060a 80%);
  border: 3px solid var(--boss-accent, #e11d48);
  outline: 3px solid #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 35px var(--boss-glow, rgba(225, 29, 72, 0.45));
}
.boss-portrait-art {
  width: 155px;
  height: 155px;
  object-fit: contain;
}
.boss-aura-ring {
  position: absolute;
  inset: -12px;
  border: 1px dashed var(--boss-accent, #e11d48);
  border-radius: 50%;
  animation: spinSlow 20s linear infinite;
  pointer-events: none;
  opacity: 0.5;
}
@keyframes spinSlow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.boss-identity-card {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}
.boss-domain-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--boss-accent, #e11d48);
  letter-spacing: 1.5px;
}
.boss-title-name {
  font-family: var(--font-pixel, monospace);
  font-size: 1.4rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
  line-height: 1.2;
}
.boss-guardian-title {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  font-style: italic;
  color: #fb7185;
}

.boss-dialogue-card {
  background: #14151f;
  border: 1px solid #282a3a;
  border-left: 3px solid var(--boss-accent, #e11d48);
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}
.intro-dialogue-quote {
  margin: 0;
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 1.05rem;
  line-height: 1.6;
  color: #f8fafc;
}
.intro-lore-body {
  margin: 0;
  font-size: 0.86rem;
  line-height: 1.55;
  color: var(--color-bone-dark, #8c8273);
}

.boss-stakes-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.stake-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #14151f;
  border: 1px solid #282a3a;
  padding: 0.75rem 1rem;
}
.stake-icon {
  font-size: 1.5rem;
}
.stake-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.stake-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: #94a3b8;
  letter-spacing: 1px;
}
.stake-val {
  font-size: 0.85rem;
  color: #e2d9cc;
}

.boss-stats-rewards-row {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.bs-stat-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
}
.bs-label {
  color: #94a3b8;
}
.bs-val {
  color: #e2d9cc;
}
.text-gold {
  color: var(--color-gold-ancient, #e6c845) !important;
}

.boss-intro-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

/* ── BATTLE ARENA ───────────────────────────── */
.boss-battle-arena {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.boss-submitting {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
}

/* ── DEFEAT PANEL ───────────────────────────── */
.boss-defeat {
  text-align: center;
  padding: 3rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--color-crimson, #8b1a1a);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}
.defeat-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.1rem;
  color: #f87171;
}
.defeat-sub {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  color: var(--color-bone-dark, #8c8273);
  max-width: 520px;
  line-height: 1.6;
}
.defeat-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
}

/* ── CAMPAIGN COMPLETE SCREEN ───────────────── */
.campaign-complete-panel {
  background: linear-gradient(180deg, #110815 0%, #08040a 100%);
  border: 2px solid #e11d48;
  outline: 3px solid #050608;
  box-shadow: 0 0 50px rgba(225, 29, 72, 0.45);
  padding: 2.5rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.75rem;
  text-align: center;
}
.complete-crest {
  font-size: 3.5rem;
  margin-bottom: 0.5rem;
}
.complete-eyebrow {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  letter-spacing: 3px;
  color: var(--color-gold-ancient, #e6c845);
}
.complete-title {
  font-family: var(--font-pixel, monospace);
  font-size: clamp(1.2rem, 3.5vw, 1.8rem);
  color: #f8fafc;
  margin: 0.5rem 0 0.8rem;
  line-height: 1.3;
}
.complete-tagline {
  font-family: var(--font-body, serif);
  font-size: 1rem;
  line-height: 1.6;
  color: #cbd5e1;
  max-width: 560px;
  margin: 0 auto;
}

.complete-relics-strip {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  width: 100%;
  max-width: 600px;
}
.complete-relic-card {
  display: flex;
  align-items: center;
  gap: 0.8rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.85rem 1rem;
  text-align: left;
}
.relic-icon {
  font-size: 1.6rem;
}
.relic-meta {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.relic-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: #94a3b8;
  letter-spacing: 1px;
}
.relic-name {
  font-size: 0.85rem;
  color: #f1f5f9;
}

.complete-rewards-box {
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(225, 29, 72, 0.35);
  padding: 1.25rem 1.5rem;
  width: 100%;
  max-width: 600px;
}
.rewards-heading {
  display: block;
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 2px;
  margin-bottom: 0.85rem;
}
.rewards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
}
.reward-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.5rem 0.75rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #f8fafc;
}
.replay-notice {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #94a3b8;
  margin: 0.85rem 0 0;
}

.complete-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  justify-content: center;
  margin-top: 0.5rem;
}

@media (max-width: 640px) {
  .boss-stakes-row,
  .complete-relics-strip {
    grid-template-columns: 1fr;
  }
}
</style>
