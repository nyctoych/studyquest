<template>
  <div class="adventure-battlefield" :class="{ 'is-boss-encounter': isBoss }">
    <!-- Screen Flash on Impact -->
    <div v-if="screenFlash" class="battle-screen-flash" :class="flashType"></div>

    <!-- ══════════════════════════════════════════════════════════════
         ZONE 1: BATTLEFIELD / ENEMY & PLAYER COMBAT AREA (UPPER)
         Visually derived from the current Chapter's Adventure Map
         ══════════════════════════════════════════════════════════════ -->
    <div class="battlefield-stage">
      <BattlefieldEnvironment
        :environment="currentEnvironment"
        :is-boss="isBoss"
      >
        <!-- Minimal Top Battle HUD (Chapter, Part, Inquiry Index, NO PLAYER HP!) -->
        <div v-if="!isDefeatCinematicActive" class="stage-minimal-hud">
          <div class="hud-left">
            <span class="hud-chapter-badge">{{ isFinalBoss ? 'FINAL' : ('CH. ' + chapterId) }}</span>
            <span class="hud-encounter-title">{{ isFinalBoss ? 'CONFRONTATION WITH THE CREATOR' : (isBoss ? 'FINAL GUARDIAN TRIAL' : partTitle) }}</span>
            <span v-if="currentEnvironment?.name" class="hud-env-tag">{{ currentEnvironment.name }}</span>
          </div>
          <div class="hud-right">
            <span class="hud-inquiry-tag">
              INQUIRY {{ currentQIndex + 1 }}
            </span>
          </div>
        </div>

        <!-- Combat Arena: Enemy (Top-Right) & Player (Bottom-Left) -->
        <div class="combat-arena" ref="arenaRef">
          <!-- Final Boss only: Black Archive phase atmosphere (manuscripts / collapse) -->
          <ArchivePhaseFx v-if="isFinalBoss && !isDefeatCinematicActive" :phase="visualPhase" />

          <!-- Enemy Area: Upper-Right / Right Side -->
          <div class="combatant-slot slot-enemy" ref="enemySlotRef" :style="enemySlotStyles">
            <!-- Boss Battle Sprite (Actual Boss Assets from bossMapping.js) -->
            <BossBattleSprite
              v-if="isBoss"
              :boss-meta="bossMeta"
              :boss-name="enemyName"
              :boss-title="enemyTitle"
              :battle-state="enemyDefeated ? 'defeated' : enemyBattleState"
              :speech-text="currentEnemySpeech"
              :boss-hp="effectiveDisplayedEnemyHp"
              :boss-max-hp="effectiveEnemyMaxHp"
              :boss-hp-pct="displayedEnemyHpPct"
              :damage-dealt="floatingEnemyDmg"
              :damage-type="floatingDmgType"
              :is-enraged="isBossEnraged"
              :is-final-boss="isFinalBoss"
              :creator-phase="currentCreatorPhase"
              :creator-anim-state="creatorAnimState"
              :hide-nameplate="isDefeatCinematicActive"
            />

            <!-- Normal Minion Battle Sprite Placeholder -->
            <MinionBattleSprite
              v-else
              :enemy-name="enemyName"
              :enemy-title="enemyTitle"
              :battle-state="enemyBattleState"
              :speech-text="currentEnemySpeech"
              :enemy-hp="effectiveDisplayedEnemyHp"
              :enemy-max-hp="effectiveEnemyMaxHp"
              :enemy-hp-pct="displayedEnemyHpPct"
              :damage-dealt="floatingEnemyDmg"
              :damage-type="floatingDmgType"
            />
          </div>

          <!-- Traveling Attack Effect Projectile -->
          <div
            v-if="showAttackProjectile"
            class="arena-attack-projectile"
            :style="{
              left: `${projectileCoords.x}px`,
              top: `${projectileCoords.y}px`,
              transform: `translate(-50%, -50%) rotate(${projectileCoords.angle}deg)`
            }"
          >
            <img
              :src="attackEffectInstanceSrc || attackEffectGifUrl"
              :alt="activeAttackEffect?.name || 'Attack Effect'"
              class="projectile-sprite pixel-art"
              @error="handleAttackEffectError"
            />
          </div>

          <!-- Impact Splash Effect at Target -->
          <div
            v-if="showImpactSplash"
            class="arena-impact-splash"
            :class="{ 'is-crit-splash': wasLastCrit }"
            :style="{
              left: `${splashCoords.x}px`,
              top: `${splashCoords.y}px`
            }"
          >
            <img
              :src="splashEffectInstanceSrc || splashEffectGifUrl"
              :alt="activeSplashEffect?.name || 'Splash Effect'"
              class="splash-sprite pixel-art"
              @error="handleSplashEffectError"
            />
          </div>

          <!-- Center Combat FX Overlay (Slashes, Arcane Sparks) -->
          <div v-if="showAttackSlash" class="arena-slash-fx animate-slash-cut" :class="{ 'is-crit-slash': wasLastCrit }">
            <div class="slash-blade"></div>
            <div class="slash-glow"></div>
          </div>

          <!-- Player Area: Lower-Left / Lower-Middle Side -->
          <div class="combatant-slot slot-player" ref="playerSlotRef" :class="{ 'is-player-fading-out': isPlayerFadingOut }" :style="playerSlotStyles">
            <PlayerBattleSprite
              :character="selectedChar"
              :player-name="auth.nickname || auth.user?.username || 'Scholar Adventurer'"
              :player-level="displayPlayerLevel"
              :attack-power="displayAttackPower"
              :critical-attack-power="displayCriticalAttackPower"
              :battle-state="playerBattleState"
              :player-hp="displayedPlayerHp"
              :player-max-hp="displayedPlayerMaxHp"
              :damage-taken="floatingPlayerDmg"
              :damage-type="floatingPlayerDmgType"
            />
          </div>

          <!-- Skip Intro Button during Opening Animation -->
          <button
            v-if="!openingAnimationCompleted"
            type="button"
            class="btn-skip-intro font-pixel"
            @click="skipOpening"
          >
            SKIP INTRO ⏩
          </button>
        </div>
      </BattlefieldEnvironment>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         LOWER INTERACTIVE COMBAT ZONES:
         ZONE 2: QUESTION PANEL (BOTTOM-LEFT)
         ZONE 3: ANSWER ACTION CHOICES (BOTTOM-RIGHT)
         ══════════════════════════════════════════════════════════════ -->
    <div
      v-if="!isDefeatCinematicActive"
      class="battlefield-controls-split"
      :class="{
        'is-opening-hidden': !openingAnimationCompleted,
        'is-opening-visible': openingAnimationCompleted
      }"
    >
      <!-- Zone 2: Question Panel (Bottom-Left) -->
      <div class="controls-left-question">
        <BattleQuestionPanel
          v-if="currentQ"
          :question-text="currentQ.question"
          :question-index="currentQIndex"
          :total-questions="questions?.length || 1"
          :difficulty="currentQ.difficulty || 'standard'"
          :status-message="statusBannerText"
          :answered="answered"
          :is-correct="lastCorrect"
          :explanation="activeExplanation"
          :is-last-question="isLastQuestion"
          @next="onNextQuestion"
        />
      </div>

      <!-- Zone 3: Answer Choices / Actions (Bottom-Right) -->
      <div class="controls-right-actions">
        <BattleChoicePanel
          v-if="currentQ"
          :choices="currentQ.choices"
          :selected-key="pendingKey || selectedKey"
          :correct-answer="activeCorrectAnswer"
          :answered="answered"
          :disabled="!openingAnimationCompleted || isProcessingAnswer || answered"
          :is-critical-active="isCriticalActive"
          :crit-damage="critDamage"
          :base-damage="baseDamage"
          @select="onStageChoice"
          @toggle-critical="onToggleCritical"
        />
      </div>
    </div>

    <!-- Battle Action Confirmation Modal -->
    <BattleActionConfirmModal
      v-if="!isDefeatCinematicActive"
      :is-open="showConfirmModal"
      :target-name="enemyName"
      :target-title="enemyTitle"
      :question-text="currentQ?.question || ''"
      :selected-key="pendingKey || ''"
      :choice-text="pendingChoiceText"
      :is-boss="isBoss"
      :is-critical-active="isCriticalActive"
      :crit-damage="critDamage"
      :base-damage="baseDamage"
      @confirm="onConfirmAttack"
      @cancel="onCancelChoice"
    />

    <!-- ══════════════════════════════════════════════════════════════
         FINAL BOSS CINEMATIC DEFEAT & BACKSTORY PLACEHOLDER OVERLAY
         ══════════════════════════════════════════════════════════════ -->
    <div
      v-if="isDefeatCinematicActive"
      class="final-defeat-cinematic-screen"
      :class="`cinematic-stage-${defeatCinematicStage}`"
    >
      <!-- Centered Creator Container -->
      <div
        v-if="defeatCinematicStage === 'creator_dialogue' || defeatCinematicStage === 'creator_closing'"
        class="cinematic-centered-creator animate-scale-up"
      >
        <div class="cinematic-creator-aura"></div>
        <img
          :src="defeatCinematicGifSrc"
          alt="The Creator"
          class="cinematic-creator-art pixel-art"
        />
        <div class="cinematic-dialogue-tag font-pixel">
          {{ defeatCinematicStage === 'creator_dialogue' ? 'THE CREATOR — REALIZATION' : 'THE CREATOR — CLOSING' }}
        </div>
      </div>

      <!-- Pure Blackout Stage -->
      <div v-else-if="defeatCinematicStage === 'blackout'" class="cinematic-blackout-stage"></div>

      <!-- Temporary Backstory Placeholder Screen (Requirement 30, 31) -->
      <div
        v-else-if="defeatCinematicStage === 'backstory_placeholder'"
        class="cinematic-backstory-placeholder-panel gothic-panel animate-scale-up"
      >
        <div class="placeholder-crest font-pixel">✦ THE BLACK ARCHIVE ✦</div>
        <span class="placeholder-eyebrow font-pixel">UNWRITTEN ORIGIN</span>
        <h1 class="placeholder-title font-pixel">FINAL BOSS BACKSTORY</h1>
        <div class="placeholder-badge font-pixel">COMING SOON</div>

        <div class="placeholder-lore-card">
          <p class="placeholder-lore-quote font-pixel">
            “He wrote the world. He wrote the hero. He wrote the ending. But he could not write the choice.”
          </p>
          <p class="placeholder-lore-desc">
            The Creator’s quill has stilled. The memories that birthed Mindrealm—the author's sanctuary, his grief, and the first lines ever inscribed behind the veil—will be unveiled in the upcoming cinematic backstory animation.
          </p>
        </div>

        <div class="placeholder-action-row">
          <button
            type="button"
            class="btn btn-gothic-crimson btn-lg placeholder-continue-btn font-pixel"
            @click="onProceedFromPlaceholder"
          >
            PROCEED TO REVELATION ▸
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useAdventureStore } from '../../stores/adventure'
import { useGameStore } from '../../stores/game'
import { bgmService } from '../../services/bgmService'
import { battleAudio } from '../../services/battleAudio'
import { getRandomWrongSpeech } from '../../config/battleAssets'
import { resolveBattlefieldEnvironment, preloadBattlefieldAssets } from '../../config/battlefieldEnvironments'
import BattlefieldEnvironment from './BattlefieldEnvironment.vue'
import PlayerBattleSprite from './PlayerBattleSprite.vue'
import MinionBattleSprite from './MinionBattleSprite.vue'
import BossBattleSprite from './BossBattleSprite.vue'
import ArchivePhaseFx from './ArchivePhaseFx.vue'
import BattleQuestionPanel from './BattleQuestionPanel.vue'
import BattleChoicePanel from './BattleChoicePanel.vue'
import BattleActionConfirmModal from './BattleActionConfirmModal.vue'
import shopService from '../../services/shopService'
import { resolveAsset } from '../../services/bossMapping'
import {
  resolveEquippedAttackEffect,
  resolveEquippedSplashEffect,
  getCharacterDefaultAttackEffect,
  getDefaultSplashEffect
} from '../../config/cosmeticEffects'
import { getCharacterAnimationConfig, preloadCharacterAnimations } from '../../config/characterAnimations'
import { CREATOR_ANIMATION_CONFIG, preloadCreatorAnimations } from '../../config/creatorAnimationConfig'

const props = defineProps({
  isBoss: {
    type: Boolean,
    default: false
  },
  chapterId: {
    type: Number,
    default: 1
  },
  partNum: {
    type: [Number, String],
    default: 1
  },
  chapterTitle: {
    type: String,
    default: ''
  },
  partTitle: {
    type: String,
    default: ''
  },
  enemyName: {
    type: String,
    default: 'Fracture Specter'
  },
  enemyTitle: {
    type: String,
    default: 'Cognitive Aberration'
  },
  bossMeta: {
    type: Object,
    default: () => ({})
  },
  bossHp: {
    type: Number,
    default: 100
  },
  bossMaxHp: {
    type: Number,
    default: 100
  },
  playerHp: {
    type: Number,
    default: 150
  },
  playerMaxHp: {
    type: Number,
    default: 150
  },
  playerLevel: {
    type: [Number, String],
    default: null
  },
  attackPower: {
    type: [Number, String],
    default: null
  },
  criticalAttackPower: {
    type: [Number, String],
    default: null
  },
  enemyHp: {
    type: Number,
    default: null
  },
  enemyMaxHp: {
    type: Number,
    default: null
  },
  damageTaken: {
    type: Number,
    default: 0
  },
  damageDealt: {
    type: Number,
    default: 0
  },
  damageType: {
    type: String,
    default: 'normal'
  },
  questions: {
    type: Array,
    default: () => []
  },
  currentQIndex: {
    type: Number,
    default: 0
  },
  currentQ: {
    type: Object,
    default: null
  },
  answered: {
    type: Boolean,
    default: false
  },
  selectedKey: {
    type: String,
    default: null
  },
  lastCorrect: {
    type: Boolean,
    default: false
  },
  isLastQuestion: {
    type: Boolean,
    default: false
  },
  isFinalBoss: {
    type: Boolean,
    default: false
  },
  // Scripted enemy dialogue (e.g. The Creator's key-moment lines); empty string clears
  scriptedSpeech: {
    type: String,
    default: ''
  },
  // Final Boss presentation phase: 1 = The Author, 2 = The Unfinished Script, 3 = The Last Page
  visualPhase: {
    type: Number,
    default: 1
  },
  initialBossPhase: {
    type: Number,
    default: 1
  },
  // Locks the enemy into its defeated end-state
  enemyDefeated: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['answer', 'answered', 'next', 'final-victory'])

const auth = useAuthStore()
const adventure = useAdventureStore()
const game = useGameStore()

// Authoritative Character Attributes from Database
const selectedChar = computed(() => {
  return auth.selectedCharacter || auth.user?.selected_character || null
})

const charClass = computed(() => {
  const c = selectedChar.value?.code || selectedChar.value?.class_type || 'mage'
  return c.toLowerCase()
})

const charAnimConfig = computed(() => {
  return getCharacterAnimationConfig(selectedChar.value || charClass.value)
})

const baseDamage = computed(() => {
  if (props.attackPower) return Number(props.attackPower)
  if (adventure.characterDamage) return Number(adventure.characterDamage)
  if (selectedChar.value?.damage) return Number(selectedChar.value.damage)
  if (charClass.value === 'range') return 15
  return 10 // Mage: 10, Melee: 10
})

const critDamage = computed(() => {
  if (props.criticalAttackPower) return Number(props.criticalAttackPower)
  if (adventure.characterCritDamage) return Number(adventure.characterCritDamage)
  if (selectedChar.value?.crit_damage) return Number(selectedChar.value.crit_damage)
  if (charClass.value === 'range') return 50
  if (charClass.value === 'melee') return 20
  return 35 // Mage: 35
})

const displayPlayerLevel = computed(() => {
  if (props.playerLevel) return Number(props.playerLevel)
  if (adventure.playerLevel) return Number(adventure.playerLevel)
  return auth.level || auth.user?.level || 1
})

const displayAttackPower = computed(() => {
  if (props.attackPower) return Number(props.attackPower)
  if (adventure.characterDamage) return Number(adventure.characterDamage)
  return baseDamage.value
})

const displayCriticalAttackPower = computed(() => {
  if (props.criticalAttackPower) return Number(props.criticalAttackPower)
  if (adventure.characterCritDamage) return Number(adventure.characterCritDamage)
  return critDamage.value
})

const effectiveEnemyHp = computed(() => {
  if (props.enemyHp !== null && props.enemyHp !== undefined) return props.enemyHp
  return props.bossHp ?? 100
})

const effectiveEnemyMaxHp = computed(() => {
  if (props.enemyMaxHp !== null && props.enemyMaxHp !== undefined) return props.enemyMaxHp
  return props.bossMaxHp ?? 100
})

// ══════════════════════════════════════════════════════════════
// CENTRALIZED ENVIRONMENT RESOLUTION (Chapter -> Part -> Fallback)
// ══════════════════════════════════════════════════════════════
const currentEnvironment = computed(() => {
  return resolveBattlefieldEnvironment(props.chapterId, props.isFinalBoss ? 'final-boss' : props.partNum, props.isBoss, props.isFinalBoss)
})

watch(() => [props.chapterId, props.partNum, props.isBoss], () => {
  preloadBattlefieldAssets(props.chapterId, props.partNum)
}, { immediate: true })

// Responsive environmental positioning for combatant sprites
const playerSlotStyles = computed(() => {
  const pos = currentEnvironment.value?.playerPosition || { x: 18, y: 70 }
  const scale = currentEnvironment.value?.playerScale || 1.0
  return {
    left: `clamp(1.5rem, ${pos.x}%, 14rem)`,
    bottom: '32px',
    '--player-scale': scale
  }
})

const enemySlotStyles = computed(() => {
  const pos = currentEnvironment.value?.enemyPosition || { x: 74, y: 15 }
  const scale = currentEnvironment.value?.enemyScale || 1.0
  // Place enemies and bosses firmly on the upper part of the battlefield
  const topY = props.isBoss ? 8 : 12
  return {
    right: `clamp(1.5rem, ${100 - pos.x}%, 14rem)`,
    top: `${topY}px`,
    '--enemy-scale': scale
  }
})


// Battle State Machine
// 'INITIALIZING' | 'PLAYING_OPENING' | 'READY' | 'PROCESSING_ANSWER' | 'PLAYING_ATTACK' | 'APPLYING_DAMAGE' | 'PLAYING_WRONG_ANSWER_REACTION' | 'CRITICAL_BACKFIRE' | 'VICTORY' | 'DEFEAT'
const battleState = ref('PLAYING_OPENING')
const openingAnimationCompleted = ref(false)
const playerBattleState = ref('opening') // 'opening' | 'idle' | 'attacking' | 'recoil'
const enemyBattleState = ref('idle')    // 'idle' | 'hit' | 'laughing'
const currentEnemySpeech = ref('')
const isProcessingAnswer = ref(false)
const showAttackSlash = ref(false)
const screenFlash = ref(false)
const flashType = ref('correct')
let openingTimer = null
let creatorOpeningTimer = null
let playerOpeningTimer = null

// Final Boss The Creator Animation State Machine & Progression
const currentCreatorPhase = ref(props.initialBossPhase || 1)
const creatorAnimState = ref('idle') // 'intro' | 'idle' | 'attack' | 'hit' | 'transform' | 'defeated_dialogue' | 'closing'
const isTransformingPhase2 = ref(false)
const isDefeatCinematicActive = ref(false)
const defeatCinematicStage = ref('') // 'player_fade' | 'creator_dialogue' | 'creator_closing' | 'blackout' | 'backstory_placeholder'
const isPlayerFadingOut = ref(false)
const defeatCinematicGifSrc = ref('')
let lastFinalVictoryResult = null

// Final Boss State Machine Transitions
function triggerPhase2Transformation(serverResult) {
  isTransformingPhase2.value = true
  battleState.value = 'CREATOR_TRANSFORMING'
  isProcessingAnswer.value = true
  isCriticalActive.value = false
  currentEnemySpeech.value = ''

  creatorAnimState.value = 'transform'

  const transformDur = CREATOR_ANIMATION_CONFIG.phase2.transform.duration // 5100ms

  setTimeout(() => {
    currentCreatorPhase.value = 2
    creatorAnimState.value = 'idle'
    enemyBattleState.value = 'idle'
    isTransformingPhase2.value = false
    battleState.value = 'READY'
    isProcessingAnswer.value = false
    emit('answered', serverResult)
  }, transformDur)
}

function startFinalBossDefeatSequence(serverResult) {
  lastFinalVictoryResult = serverResult
  isDefeatCinematicActive.value = true
  battleState.value = 'CREATOR_DEFEAT_CINEMATIC'
  isProcessingAnswer.value = true
  isCriticalActive.value = false

  // Step 1: Smooth player fade out (1200ms)
  defeatCinematicStage.value = 'player_fade'
  isPlayerFadingOut.value = true

  setTimeout(() => {
    // Step 2: Full Black Screen & Creator Centered — Defeated Dialogue GIF (2600ms)
    defeatCinematicStage.value = 'creator_dialogue'
    defeatCinematicGifSrc.value = resolveAsset(CREATOR_ANIMATION_CONFIG.defeat.dialogue.path)

    const dialogueDur = CREATOR_ANIMATION_CONFIG.defeat.dialogue.duration // 2600ms
    setTimeout(() => {
      // Step 3: Creator Centered — Closing GIF (3300ms)
      defeatCinematicStage.value = 'creator_closing'
      defeatCinematicGifSrc.value = resolveAsset(CREATOR_ANIMATION_CONFIG.defeat.closing.path)

      const closingDur = CREATOR_ANIMATION_CONFIG.defeat.closing.duration // 3300ms
      setTimeout(() => {
        // Step 4: Pure Blackout (1200ms)
        defeatCinematicStage.value = 'blackout'
        defeatCinematicGifSrc.value = ''

        setTimeout(() => {
          // Step 5: Temporary Backstory Placeholder Screen
          defeatCinematicStage.value = 'backstory_placeholder'
        }, 1200)
      }, closingDur)
    }, dialogueDur)
  }, 1200)
}

function onProceedFromPlaceholder() {
  emit('final-victory', lastFinalVictoryResult)
}

// Cosmetic Attack Effect & Splash Effect References & State
const arenaRef = ref(null)
const playerSlotRef = ref(null)
const enemySlotRef = ref(null)

const equippedCosmetics = ref({})
const attackEffectAssetError = ref(false)
const splashEffectAssetError = ref(false)

const showAttackProjectile = ref(false)
const showImpactSplash = ref(false)
const projectileCoords = ref({ x: 0, y: 0, angle: 0, progress: 0 })
const splashCoords = ref({ x: 0, y: 0 })
const attackEffectInstanceSrc = ref('')
const splashEffectInstanceSrc = ref('')

const activeAttackEffect = computed(() => {
  return resolveEquippedAttackEffect(
    equippedCosmetics.value?.attack_effect,
    selectedChar.value || charClass.value
  )
})

const activeSplashEffect = computed(() => {
  return resolveEquippedSplashEffect(equippedCosmetics.value?.splash_effect)
})

const attackEffectGifUrl = computed(() => {
  if (attackEffectAssetError.value) {
    const def = getCharacterDefaultAttackEffect(selectedChar.value || charClass.value)
    return resolveAsset(def.gif_path)
  }
  return resolveAsset(activeAttackEffect.value?.gif_path)
})

const splashEffectGifUrl = computed(() => {
  if (splashEffectAssetError.value) {
    const def = getDefaultSplashEffect()
    return resolveAsset(def.gif_path)
  }
  return resolveAsset(activeSplashEffect.value?.gif_path)
})

function handleAttackEffectError(e) {
  console.warn('[AdventureBattlefield] Custom attack effect asset failed to load, falling back to character default:', e)
  attackEffectAssetError.value = true
}

function handleSplashEffectError(e) {
  console.warn('[AdventureBattlefield] Custom splash effect asset failed to load, falling back to default:', e)
  splashEffectAssetError.value = true
}

function launchAttackEffect(onImpact) {
  const arenaEl = arenaRef.value
  const playerEl = playerSlotRef.value
  const enemyEl = enemySlotRef.value

  let startX = 200
  let startY = 320
  let targetX = 650
  let targetY = 160

  if (arenaEl && playerEl && enemyEl) {
    const arenaRect = arenaEl.getBoundingClientRect()
    const pRect = playerEl.getBoundingClientRect()
    const eRect = enemyEl.getBoundingClientRect()

    // 1. Calculate launch position specifically at the purple attack indication (cast energy flare/hand)
    const flareEl = playerEl.querySelector('.cast-energy-flare')
    if (flareEl) {
      const fRect = flareEl.getBoundingClientRect()
      startX = (fRect.left + fRect.width * 0.5) - arenaRect.left
      startY = (fRect.top + fRect.height * 0.5) - arenaRect.top
    } else {
      // Fallback: character's casting wand/hand on upper-right of player sprite
      startX = (pRect.left + pRect.width * 0.68) - arenaRect.left
      startY = (pRect.top + pRect.height * 0.22) - arenaRect.top
    }

    targetX = (eRect.left + eRect.width * 0.5) - arenaRect.left
    targetY = (eRect.top + eRect.height * 0.45) - arenaRect.top
  }

  const dx = targetX - startX
  const dy = targetY - startY
  const angle = Math.atan2(dy, dx) * (180 / Math.PI)

  // Slower, dramatic travel duration so the animated effect is fully visible (1100ms)
  const travelDuration = 1100 // ms

  // Force fresh GIF rendering & animation playback cycle from frame 0
  const baseGif = resolveAsset(activeAttackEffect.value?.gif_path) || attackEffectGifUrl.value
  attackEffectInstanceSrc.value = baseGif ? `${baseGif}?t=${Date.now()}` : ''

  projectileCoords.value = {
    x: startX,
    y: startY,
    angle,
    progress: 0
  }
  showAttackProjectile.value = true

  const startTime = performance.now()

  function step(now) {
    const elapsed = now - startTime
    const p = Math.min(1, elapsed / travelDuration)
    // Smooth ease-out cubic
    const ease = 1 - Math.pow(1 - p, 3)

    projectileCoords.value = {
      x: startX + dx * ease,
      y: startY + dy * ease,
      angle,
      progress: ease
    }

    if (p < 1) {
      requestAnimationFrame(step)
    } else {
      showAttackProjectile.value = false
      splashCoords.value = { x: targetX, y: targetY }

      // Force fresh GIF animation for impact splash effect
      const baseSplash = resolveAsset(activeSplashEffect.value?.gif_path) || splashEffectGifUrl.value
      splashEffectInstanceSrc.value = baseSplash ? `${baseSplash}?t=${Date.now()}` : ''

      if (typeof onImpact === 'function') {
        onImpact()
      }
    }
  }

  requestAnimationFrame(step)
}

// Displayed Combatant Health Values (Decoupled from instant props for RPG timing)
const displayedPlayerHp = ref(Number(props.playerHp ?? 150))
const displayedEnemyHp = ref(Number(props.enemyHp ?? props.bossHp ?? 100))

const displayedPlayerMaxHp = computed(() => {
  return Number(props.playerMaxHp || 150)
})

const effectiveDisplayedEnemyHp = computed(() => {
  return Math.round(Number(displayedEnemyHp.value || 0))
})

const displayedEnemyHpPct = computed(() => {
  if (effectiveEnemyMaxHp.value > 0) {
    return Math.max(0, Math.min(100, (displayedEnemyHp.value / effectiveEnemyMaxHp.value) * 100))
  }
  return 100
})

// Synchronize displayed health only when in steady state
watch(() => props.playerHp, (val) => {
  if (!isProcessingAnswer.value && battleState.value === 'READY') {
    displayedPlayerHp.value = Number(val)
  }
})

watch(() => [props.enemyHp, props.bossHp], () => {
  if (!isProcessingAnswer.value && battleState.value === 'READY') {
    displayedEnemyHp.value = Number(props.enemyHp ?? props.bossHp ?? 100)
  }
})

// Floating Damage Floater States
const floatingEnemyDmg = ref(0)
const floatingDmgType = ref('normal')
const floatingPlayerDmg = ref(0)
const floatingPlayerDmgType = ref('normal')
const wasLastCrit = ref(false)

// Critical Damage Action Mode (Off by default for each question)
const isCriticalActive = ref(false)

function onToggleCritical() {
  if (!openingAnimationCompleted.value || battleState.value !== 'READY' || props.answered || isProcessingAnswer.value) return
  isCriticalActive.value = !isCriticalActive.value
  if (game.audioEnabled) {
    battleAudio.playSelect()
  }
}

// Boss 50% HP Enraged Mode calculation
const hasBossEnteredEnraged = ref(false)

watch(displayedEnemyHpPct, (pct) => {
  if (pct <= 50) {
    hasBossEnteredEnraged.value = true
  }
}, { immediate: true })

const isBossEnraged = computed(() => {
  return props.isBoss && (hasBossEnteredEnraged.value || displayedEnemyHpPct.value <= 50)
})

const hasPlayedEnrageSfx = ref(false)
watch(isBossEnraged, (enraged) => {
  if (enraged && !hasPlayedEnrageSfx.value) {
    hasPlayedEnrageSfx.value = true
    if (game.audioEnabled) {
      battleAudio.playEnraged()
    }
  }
}, { immediate: true })

// Status banner text displayed in the Question Panel header
const statusBannerText = computed(() => {
  if (!openingAnimationCompleted.value) return '⚔️ PREPARING FOR BATTLE…'
  if (battleState.value === 'PLAYING_ATTACK') return '⚡ EXECUTING ATTACK…'
  if (battleState.value === 'APPLYING_DAMAGE') return '💥 IMPACT CONFIRMED'
  if (battleState.value === 'CRITICAL_BACKFIRE') return '⚠️ CRITICAL BACKFIRE'
  if (isProcessingAnswer.value) return '🔮 ANALYZING ACTION…'
  return ''
})

// Lifecycle: Preload assets & execute Opening Animation sequence
onMounted(async () => {
  bgmService.setBattlefieldActive(true)

  // Fetch equipped cosmetics from market
  try {
    const res = await shopService.getEquipped()
    if (res.success && res.equipped) {
      equippedCosmetics.value = res.equipped
    }
  } catch (err) {
    console.warn('[AdventureBattlefield] Could not fetch equipped cosmetics:', err)
  }

  // Preload selected character assets (opening, attack, idle, sprite)
  try {
    await preloadCharacterAnimations(selectedChar.value || charClass.value)
  } catch (err) {
    console.warn('[AdventureBattlefield] Asset preloading fallback:', err)
  }

  // Preload and initialize Creator assets and intro for Final Boss (Requirements 2, 6, 7, 8)
  if (props.isFinalBoss) {
    try {
      await preloadCreatorAnimations()
    } catch (err) {
      console.warn('[AdventureBattlefield] Creator preloading fallback:', err)
    }

    if (currentCreatorPhase.value === 2) {
      // Reconnected directly into Phase 2
      openingAnimationCompleted.value = true
      creatorAnimState.value = 'idle'
      playerBattleState.value = 'idle'
      battleState.value = 'READY'
    } else if (props.currentQIndex > 0) {
      // Reconnected into an ongoing Phase 1 battle
      openingAnimationCompleted.value = true
      creatorAnimState.value = 'idle'
      playerBattleState.value = 'idle'
      battleState.value = 'READY'
    } else {
      // Phase 1: Display both The Creator's intro and the User Character's opening animation
      openingAnimationCompleted.value = false
      battleState.value = 'CREATOR_INTRO'
      creatorAnimState.value = 'intro'
      playerBattleState.value = 'opening'

      const introDur = CREATOR_ANIMATION_CONFIG.phase1.intro.duration // 3700ms
      const playerOpeningDur = charAnimConfig.value?.openingDuration || 9800

      // When Creator intro finishes, switch Creator to Phase 1 idle
      creatorOpeningTimer = setTimeout(() => {
        creatorAnimState.value = 'idle'
      }, introDur)

      // When player opening finishes, switch player to idle
      playerOpeningTimer = setTimeout(() => {
        playerBattleState.value = 'idle'
      }, playerOpeningDur)

      // Total opening sequence finishes when both have completed
      const totalDur = Math.max(introDur, playerOpeningDur)
      openingTimer = setTimeout(() => {
        creatorAnimState.value = 'idle'
        playerBattleState.value = 'idle'
        openingAnimationCompleted.value = true
        battleState.value = 'READY'
      }, totalDur)
    }
  } else if (props.currentQIndex === 0) {
    openingAnimationCompleted.value = false
    battleState.value = 'PLAYING_OPENING'
    playerBattleState.value = 'opening' // Shown immediately on character after clicking commence duel

    const duration = charAnimConfig.value?.openingDuration || 9800
    openingTimer = setTimeout(() => {
      openingAnimationCompleted.value = true
      playerBattleState.value = 'idle'
      battleState.value = 'READY'
    }, duration)
  } else {
    openingAnimationCompleted.value = true
    playerBattleState.value = 'idle'
    battleState.value = 'READY'
  }
})

function skipOpening() {
  if (!openingAnimationCompleted.value) {
    if (openingTimer) clearTimeout(openingTimer)
    if (creatorOpeningTimer) clearTimeout(creatorOpeningTimer)
    if (playerOpeningTimer) clearTimeout(playerOpeningTimer)
    openingAnimationCompleted.value = true
    playerBattleState.value = 'idle'
    if (props.isFinalBoss) {
      creatorAnimState.value = 'idle'
    }
    battleState.value = 'READY'
  }
}

onUnmounted(() => {
  bgmService.setBattlefieldActive(false)
  if (openingTimer) clearTimeout(openingTimer)
  if (creatorOpeningTimer) clearTimeout(creatorOpeningTimer)
  if (playerOpeningTimer) clearTimeout(playerOpeningTimer)
})

// Authoritative Answer & Explanation resolution from combat server
const authoritativeCorrectAnswer = ref(null)
const authoritativeExplanation = ref(null)

const activeCorrectAnswer = computed(() => {
  return authoritativeCorrectAnswer.value || props.currentQ?.correct_answer || props.currentQ?.correct || null
})

const activeExplanation = computed(() => {
  return authoritativeExplanation.value || props.currentQ?.explanation || ''
})

// Confirmation State
const pendingKey = ref(null)
const showConfirmModal = ref(false)

const pendingChoiceText = computed(() => {
  if (!pendingKey.value || !props.currentQ?.choices) return ''
  const choices = props.currentQ.choices
  if (Array.isArray(choices)) {
    for (let idx = 0; idx < choices.length; idx++) {
      const item = choices[idx]
      const k = (item && typeof item === 'object') ? (item.key || String.fromCharCode(65 + idx)) : String.fromCharCode(65 + idx)
      if (k.toUpperCase() === pendingKey.value.toUpperCase()) {
        return (item && typeof item === 'object') ? (item.text || item.label || '') : String(item)
      }
    }
    return ''
  }
  return choices[pendingKey.value] || choices[pendingKey.value.toLowerCase()] || choices[pendingKey.value.toUpperCase()] || ''
})

// Reset states when moving to next inquiry (Requirement 29: do NOT replay opening)
watch(() => props.currentQIndex, () => {
  if (isDefeatCinematicActive.value || isTransformingPhase2.value) return
  openingAnimationCompleted.value = true
  playerBattleState.value = 'idle'
  enemyBattleState.value = 'idle'
  if (props.isFinalBoss) {
    creatorAnimState.value = 'idle'
  }
  currentEnemySpeech.value = ''
  isProcessingAnswer.value = false
  showAttackSlash.value = false
  showAttackProjectile.value = false
  showImpactSplash.value = false
  screenFlash.value = false
  pendingKey.value = null
  showConfirmModal.value = false
  isCriticalActive.value = false
  floatingEnemyDmg.value = 0
  floatingPlayerDmg.value = 0
  wasLastCrit.value = false
  authoritativeCorrectAnswer.value = null
  authoritativeExplanation.value = null
  battleState.value = 'READY'
})

function onStageChoice(key) {
  if (!openingAnimationCompleted.value || battleState.value !== 'READY' || props.answered || isProcessingAnswer.value) return
  pendingKey.value = key
  showConfirmModal.value = true
  if (game.audioEnabled) {
    battleAudio.playSelect()
  }
}

function onCancelChoice() {
  showConfirmModal.value = false
  pendingKey.value = null
}

function onConfirmAttack() {
  if (!pendingKey.value || !openingAnimationCompleted.value || isProcessingAnswer.value || props.answered) return
  const key = pendingKey.value
  pendingKey.value = null
  showConfirmModal.value = false

  executeAttack(key)
}

async function executeAttack(key) {
  if (isProcessingAnswer.value || props.answered || !openingAnimationCompleted.value) return
  isProcessingAnswer.value = true

  const wasCritical = isCriticalActive.value
  wasLastCrit.value = wasCritical

  // Start authoritative server action in parallel with safety timeout
  let resolveServerPromise
  const serverPromise = new Promise((resolve) => {
    resolveServerPromise = resolve
    // Safety fallback: if server does not respond within 2500ms, proceed with local authoritative fallback
    setTimeout(() => resolve(null), 2500)
  })

  emit('answer', {
    key,
    isCritical: wasCritical,
    resolve: (serverResult) => {
      resolveServerPromise(serverResult)
    }
  })

  // 1. Wait for server validation
  let serverResult = await serverPromise
  if (!serverResult || !serverResult.success) {
    const correct = props.currentQ?.correct_answer || props.currentQ?.correct || props.currentQ?.answer
    const isCorr = String(key || '').trim().toUpperCase() === String(correct || '').trim().toUpperCase()
    const damageAmount = wasCritical ? critDamage.value : baseDamage.value
    const charDmg = isCorr ? damageAmount : 0
    const selfDmg = (!isCorr) ? damageAmount : 0
    const remainingEnemy = Math.max(0, displayedEnemyHp.value - charDmg)
    const remainingPlayer = Math.max(0, displayedPlayerHp.value - selfDmg)
    serverResult = {
      success: true,
      correct: isCorr,
      is_correct: isCorr,
      critical: wasCritical,
      critical_active: wasCritical,
      damage: damageAmount,
      damage_target: isCorr ? 'enemy' : 'player',
      damage_dealt: charDmg,
      player_damage_taken: selfDmg,
      player_hp: remainingPlayer,
      player_current_health: remainingPlayer,
      player_max_health: displayedPlayerMaxHp.value,
      enemy_hp: remainingEnemy,
      enemy_current_health: remainingEnemy,
      enemy_max_health: effectiveEnemyMaxHp.value,
      damage_type: isCorr ? (wasCritical ? 'critical' : 'normal') : (wasCritical ? 'critical_backfire' : 'normal_damage'),
      battle_result: remainingPlayer <= 0 ? 'defeat' : (remainingEnemy <= 0 ? 'victory' : null),
      correct_answer: correct || (isCorr ? key : null),
      explanation: props.currentQ?.explanation || ''
    }
  }

  // Populate authoritative correct answer and explanation for immediate feedback
  if (serverResult && serverResult.success) {
    if (serverResult.correct_answer) {
      authoritativeCorrectAnswer.value = serverResult.correct_answer
    } else if (serverResult.is_correct) {
      authoritativeCorrectAnswer.value = key
    }
    if (serverResult.explanation) {
      authoritativeExplanation.value = serverResult.explanation
    }
  }

  const isCorrect = Boolean(serverResult.is_correct ?? serverResult.correct)
  const dmgDealt = Number(serverResult.damage_dealt ?? (isCorrect ? serverResult.damage : 0) ?? 0)
  const dmgTaken = Number(serverResult.player_damage_taken ?? (!isCorrect ? serverResult.damage : 0) ?? 0)
  const targetPlayerHp = Math.max(0, Number(serverResult.player_current_health ?? serverResult.player_hp ?? displayedPlayerHp.value))
  const targetEnemyHp = Math.max(0, Number(serverResult.enemy_current_health ?? serverResult.enemy_hp ?? displayedEnemyHp.value))

  const cfg = charAnimConfig.value

  if (isCorrect) {
    // ══════════════════════════════════════════════════════════════
    // SUCCESSFUL ATTACK (NORMAL OR CRITICAL CORRECT):
    // 1. Archer Attacking.gif starts & plays FULL attack animation
    // ══════════════════════════════════════════════════════════════
    battleState.value = 'PLAYING_ATTACK'
    playerBattleState.value = 'attacking'

    if (wasCritical) {
      flashType.value = 'critical'
      screenFlash.value = true
      setTimeout(() => { screenFlash.value = false }, 350)
    }

    if (game.audioEnabled) {
      battleAudio.playAttack()
    }

    const attackDuration = cfg?.attackDuration || 1600

    // 1. Wait for character's attack animation (GIF) to finish completely to the end FIRST
    setTimeout(() => {
      // Character finished attack animation -> transition player state
      if (charClass.value === 'range') {
        playerBattleState.value = 'attack_finished'
      } else {
        playerBattleState.value = 'idle'
      }

      // 2. NOW show and launch equipped Attack Effect projectile towards enemy
      launchAttackEffect(() => {
        // 3. On impact at enemy position: display equipped Splash Effect & apply damage
        battleState.value = 'APPLYING_DAMAGE'
        showImpactSplash.value = true
        showAttackSlash.value = true

        if (game.audioEnabled) {
          battleAudio.playHit()
        }

        floatingEnemyDmg.value = dmgDealt
        floatingDmgType.value = wasCritical ? 'critical' : 'normal'
        displayedEnemyHp.value = targetEnemyHp

        if (props.isFinalBoss) {
          creatorAnimState.value = 'hit'
          enemyBattleState.value = 'hit'
        } else {
          enemyBattleState.value = 'hit'
        }

        setTimeout(() => {
          showImpactSplash.value = false
          showAttackSlash.value = false
          if (!props.isFinalBoss) {
            enemyBattleState.value = 'idle'
          }
        }, 500)

        const hitDuration = props.isFinalBoss
          ? (currentCreatorPhase.value === 2
              ? CREATOR_ANIMATION_CONFIG.phase2.hit.duration
              : CREATOR_ANIMATION_CONFIG.phase1.hit.duration
            )
          : 700

        // 4. Enemy reaction window concludes, return battlefield to ready state or next phase
        setTimeout(() => {
          if (props.isFinalBoss) {
            creatorAnimState.value = 'idle'
          }
          playerBattleState.value = 'idle'
          enemyBattleState.value = 'idle'
          isCriticalActive.value = false

          if (props.isFinalBoss) {
            if (targetEnemyHp <= 0) {
              // Final Boss Defeat Sequence (Requirements 21-31)
              startFinalBossDefeatSequence(serverResult)
            } else if (currentCreatorPhase.value === 1 && targetEnemyHp <= (effectiveEnemyMaxHp.value * 0.50)) {
              // Phase 2 Transformation (Requirements 10-13)
              triggerPhase2Transformation(serverResult)
            } else {
              battleState.value = 'READY'
              isProcessingAnswer.value = false
              emit('answered', serverResult)
            }
          } else {
            emit('answered', serverResult)

            if (targetPlayerHp <= 0) {
              battleState.value = 'DEFEAT'
            } else if (targetEnemyHp <= 0) {
              battleState.value = 'VICTORY'
            } else {
              battleState.value = 'READY'
              isProcessingAnswer.value = false
            }
          }
        }, hitDuration)
      })
    }, attackDuration)

  } else {
    // ══════════════════════════════════════════════════════════════
    // WRONG ANSWER:
    // ══════════════════════════════════════════════════════════════
    if (props.isFinalBoss) {
      // The Creator performs boss attack animation (Requirements 5, 9, 14, 17)
      battleState.value = 'CREATOR_ATTACKING'
      creatorAnimState.value = 'attack'
      enemyBattleState.value = 'attack'

      const attackDuration = currentCreatorPhase.value === 2
        ? CREATOR_ANIMATION_CONFIG.phase2.attack.duration // 4400ms
        : CREATOR_ANIMATION_CONFIG.phase1.attack.duration // 6200ms

      if (game.audioEnabled) {
        battleAudio.playAttack()
      }

      // Midway through Creator attack, damage connects to player
      const hitImpactDelay = Math.round(attackDuration * 0.45)
      setTimeout(() => {
        playerBattleState.value = 'recoil'
        flashType.value = wasCritical ? 'critical-backfire' : 'wrong'
        screenFlash.value = true
        setTimeout(() => { screenFlash.value = false }, 350)

        if (game.audioEnabled) {
          battleAudio.playHit()
        }

        floatingPlayerDmg.value = dmgTaken
        floatingPlayerDmgType.value = wasCritical ? 'critical' : 'damage'
        displayedPlayerHp.value = targetPlayerHp
      }, hitImpactDelay)

      // Creator returns to idle upon completing full attack GIF
      setTimeout(() => {
        creatorAnimState.value = 'idle'
        enemyBattleState.value = 'idle'
        playerBattleState.value = 'idle'
        isCriticalActive.value = false

        emit('answered', serverResult)

        if (targetPlayerHp <= 0) {
          battleState.value = 'DEFEAT'
        } else {
          battleState.value = 'READY'
          isProcessingAnswer.value = false
        }
      }, attackDuration)

    } else if (wasCritical) {
      // Critical backfire: player takes their own crit damage
      battleState.value = 'CRITICAL_BACKFIRE'
      playerBattleState.value = 'recoil'
      enemyBattleState.value = 'laughing'
      flashType.value = 'critical-backfire'
      screenFlash.value = true

      const critMockLines = [
        "Ha! You risked too much.",
        "That gamble backfired.",
        "Your confidence betrayed you.",
        "You should have known better.",
        "You gave me an opening."
      ]
      currentEnemySpeech.value = critMockLines[Math.floor(Math.random() * critMockLines.length)]

      if (game.audioEnabled) {
        battleAudio.playMock()
      }

      setTimeout(() => {
        screenFlash.value = false
      }, 350)

      floatingPlayerDmg.value = dmgTaken
      floatingPlayerDmgType.value = 'critical'
      displayedPlayerHp.value = targetPlayerHp

      // Reaction duration then complete turn
      setTimeout(() => {
        playerBattleState.value = 'idle'
        enemyBattleState.value = 'idle'
        isCriticalActive.value = false

        emit('answered', serverResult)

        if (targetPlayerHp <= 0) {
          battleState.value = 'DEFEAT'
        } else if (targetEnemyHp <= 0) {
          battleState.value = 'VICTORY'
        } else {
          battleState.value = 'READY'
          isProcessingAnswer.value = false
        }
      }, 1200)

    } else {
      // Normal wrong: damage to player, 0 damage to enemy, enemy mock quote
      battleState.value = 'PLAYING_WRONG_ANSWER_REACTION'
      playerBattleState.value = 'recoil'
      enemyBattleState.value = 'laughing'
      flashType.value = 'wrong'
      screenFlash.value = true

      currentEnemySpeech.value = getRandomWrongSpeech(props.chapterId, props.isBoss)

      if (game.audioEnabled) {
        battleAudio.playMock()
      }

      setTimeout(() => {
        screenFlash.value = false
      }, 350)

      floatingPlayerDmg.value = dmgTaken
      floatingPlayerDmgType.value = 'damage'
      displayedPlayerHp.value = targetPlayerHp

      // Reaction duration then complete turn
      setTimeout(() => {
        playerBattleState.value = 'idle'
        enemyBattleState.value = 'idle'
        isCriticalActive.value = false

        emit('answered', serverResult)

        if (targetPlayerHp <= 0) {
          battleState.value = 'DEFEAT'
        } else if (targetEnemyHp <= 0) {
          battleState.value = 'VICTORY'
        } else {
          battleState.value = 'READY'
          isProcessingAnswer.value = false
        }
      }, 1200)
    }
  }
}

let scriptedSpeechTimer = null
watch(() => props.scriptedSpeech, (text) => {
  if (scriptedSpeechTimer) clearTimeout(scriptedSpeechTimer)
  if (!text) return
  currentEnemySpeech.value = text
  scriptedSpeechTimer = setTimeout(() => {
    if (currentEnemySpeech.value === text) currentEnemySpeech.value = ''
  }, 5200)
})

onUnmounted(() => {
  if (scriptedSpeechTimer) clearTimeout(scriptedSpeechTimer)
})

function onNextQuestion() {
  currentEnemySpeech.value = ''
  isCriticalActive.value = false
  floatingEnemyDmg.value = 0
  floatingPlayerDmg.value = 0
  showAttackProjectile.value = false
  showImpactSplash.value = false
  showAttackSlash.value = false
  authoritativeCorrectAnswer.value = null
  authoritativeExplanation.value = null
  playerBattleState.value = 'idle'
  enemyBattleState.value = 'idle'
  battleState.value = 'READY'
  emit('next')
}
</script>

<style scoped>
.adventure-battlefield {
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  margin: 0;
  height: auto;
  min-height: auto;
  gap: 0.55rem;
  padding: 0 0.5rem 0.25rem;
  position: relative;
  box-sizing: border-box;
}

/* Screen Flash overlay */
.battle-screen-flash {
  position: fixed;
  inset: 0;
  z-index: 100;
  pointer-events: none;
  animation: flash-fade 0.35s ease-out forwards;
}

.battle-screen-flash.correct {
  background: radial-gradient(circle, rgba(52, 211, 153, 0.3) 0%, transparent 75%);
}

.battle-screen-flash.critical {
  background: radial-gradient(circle, rgba(251, 191, 36, 0.45) 0%, rgba(239, 68, 68, 0.3) 50%, transparent 80%);
}

.battle-screen-flash.critical-backfire {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.55) 0%, rgba(185, 28, 28, 0.35) 60%, transparent 80%);
}

.battle-screen-flash.wrong {
  background: radial-gradient(circle, rgba(239, 68, 68, 0.35) 0%, transparent 75%);
}

.arena-slash-fx.is-crit-slash .slash-blade {
  background: linear-gradient(90deg, transparent, #fbbf24, #f59e0b, #ffffff, transparent);
  box-shadow: 0 0 25px rgba(251, 191, 36, 0.9), 0 0 15px rgba(239, 68, 68, 0.8);
  height: 6px;
}

@keyframes flash-fade {
  0% { opacity: 1; }
  100% { opacity: 0; }
}

/* ══════════════════════════════════════════════════════════════
   ZONE 1: BATTLEFIELD / ENEMY AREA (UPPER)
   ══════════════════════════════════════════════════════════════ */
.battlefield-stage {
  position: relative;
  width: 100%;
  min-height: 380px;
  padding-bottom: 0;
  background: #090514;
  border: 2px solid rgba(168, 85, 247, 0.45);
  border-radius: 10px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.75), inset 0 0 40px rgba(76, 29, 149, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.is-boss-encounter .battlefield-stage {
  min-height: 400px;
  padding-bottom: 0;
}

/* Minimal Top HUD */
.stage-minimal-hud {
  position: relative;
  z-index: 20;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.45rem 0.85rem;
  background: rgba(10, 6, 20, 0.88);
  border-bottom: 1px solid rgba(139, 92, 246, 0.3);
  backdrop-filter: blur(6px);
}

.hud-left {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.hud-chapter-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  background: #7c3aed;
  color: #fff;
  padding: 0.2rem 0.5rem;
  border-radius: 3px;
  box-shadow: 0 0 8px rgba(124, 58, 237, 0.5);
}

.hud-encounter-title {
  font-family: 'Cinzel', serif;
  font-size: 0.92rem;
  font-weight: 700;
  color: #f1f5f9;
  letter-spacing: 0.5px;
}

.hud-env-tag {
  font-family: 'Cinzel', serif;
  font-size: 0.74rem;
  color: #c084fc;
  border-left: 1px solid rgba(168, 85, 247, 0.45);
  padding-left: 0.6rem;
  letter-spacing: 0.5px;
}

.hud-inquiry-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.54rem;
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.35);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

/* Combat Arena: Positioning Pokemon-Style (Enemy Top-Right, Player Bottom-Left) */
.combat-arena {
  position: relative;
  flex: 1;
  display: flex;
  width: 100%;
  min-height: 330px;
  padding-bottom: 0.5rem;
  overflow: visible;
}

.is-boss-encounter .combat-arena {
  min-height: 350px;
  padding-bottom: 0.65rem;
}

/* Enemy in Upper-Right / Right Side */
.slot-enemy {
  position: absolute;
  top: 25px;
  right: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--enemy-scale, 1));
  transform-origin: top center;
  z-index: 25;
}

.is-boss-encounter .slot-enemy {
  top: 10px;
}

/* Player in Lower-Left / Lower-Middle */
.slot-player {
  position: absolute;
  bottom: 32px;
  left: clamp(1.5rem, 10vw, 12rem);
  transform: scale(var(--player-scale, 1));
  transform-origin: bottom center;
  z-index: 12;
}

/* Center Combat Slash Effect */
.arena-slash-fx {
  position: absolute;
  top: 25%;
  right: clamp(6rem, 20vw, 20rem);
  width: 180px;
  height: 180px;
  pointer-events: none;
  z-index: 30;
}

/* Traveling Attack Effect Projectile */
.arena-attack-projectile {
  position: absolute;
  z-index: 45;
  pointer-events: none;
  width: 700px;
  height: 450px;
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 25px rgba(56, 189, 248, 0.85)) drop-shadow(0 0 45px rgba(168, 85, 247, 0.5));
  will-change: left, top, transform;
}

.projectile-sprite {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
}

/* Impact Splash Effect at Target */
.arena-impact-splash {
  position: absolute;
  z-index: 50;
  pointer-events: none;
  transform: translate(-50%, -50%);
  width: 220px;
  height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 16px rgba(251, 191, 36, 0.75));
  animation: splashPulse 0.45s ease-out forwards;
}

.arena-impact-splash.is-crit-splash {
  filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.95)) drop-shadow(0 0 35px rgba(251, 191, 36, 0.9));
}

.splash-sprite {
  width: 100%;
  height: 100%;
  object-fit: contain;
  image-rendering: pixelated;
}

@keyframes splashPulse {
  0% { transform: translate(-50%, -50%) scale(0.6); opacity: 0; }
  20% { transform: translate(-50%, -50%) scale(1.15); opacity: 1; }
  75% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1.1); opacity: 0; }
}

.slash-blade {
  position: absolute;
  top: 50%;
  left: -20%;
  width: 140%;
  height: 4px;
  background: #ffffff;
  box-shadow: 0 0 16px #38bdf8, 0 0 30px #a855f7;
  transform: rotate(-35deg);
  border-radius: 2px;
}

.slash-glow {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle, rgba(56, 189, 248, 0.4) 0%, transparent 65%);
}

.animate-slash-cut {
  animation: slash-surge 0.4s ease-out forwards;
}

@keyframes slash-surge {
  0% { transform: scale(0.2) rotate(-15deg); opacity: 0; }
  40% { transform: scale(1.1) rotate(0deg); opacity: 1; }
  100% { transform: scale(1.4) rotate(10deg); opacity: 0; }
}

/* ══════════════════════════════════════════════════════════════
   ZONES 2 & 3: SPLIT BOTTOM CONTROLS
   QUESTION (LEFT) | ANSWER CHOICES (RIGHT)
   ══════════════════════════════════════════════════════════════ */
.battlefield-controls-split {
  display: grid;
  grid-template-columns: 46% 54%;
  gap: 0.65rem;
  width: 100%;
  height: 260px;
  min-height: 220px;
  flex-shrink: 0;
  transition: opacity 0.65s cubic-bezier(0.16, 1, 0.3, 1), transform 0.65s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Invisible first during character entrance opening */
.battlefield-controls-split.is-opening-hidden {
  opacity: 0 !important;
  transform: translateY(28px) scale(0.98);
  pointer-events: none;
  visibility: hidden;
}

/* Animates smoothly into view once the opening is done */
.battlefield-controls-split.is-opening-visible {
  opacity: 1 !important;
  transform: translateY(0) scale(1);
  pointer-events: auto;
  visibility: visible;
  animation: controls-reveal 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes controls-reveal {
  0% {
    opacity: 0;
    transform: translateY(28px) scale(0.98);
  }
  60% {
    opacity: 0.9;
    transform: translateY(-2px) scale(1.005);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Skip Intro Button */
.btn-skip-intro {
  position: absolute;
  top: 14px;
  right: 18px;
  z-index: 40;
  background: rgba(14, 10, 26, 0.88);
  color: #c084fc;
  border: 1px solid rgba(168, 85, 247, 0.45);
  border-radius: 4px;
  padding: 0.35rem 0.75rem;
  font-size: 0.55rem;
  letter-spacing: 0.08em;
  cursor: pointer;
  transition: all 0.2s ease;
  backdrop-filter: blur(4px);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
}

.btn-skip-intro:hover {
  background: rgba(88, 28, 135, 0.95);
  color: #ffffff;
  border-color: #a855f7;
  transform: scale(1.05);
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.6);
}

.controls-left-question {
  height: 100%;
}

.controls-right-actions {
  height: 100%;
}

/* ══════════════════════════════════════════════════════════════
   RESPONSIVE DESIGN (TABLET & MOBILE)
   ══════════════════════════════════════════════════════════════ */
@media (max-width: 860px), (max-height: 650px) {
  .adventure-battlefield {
    height: auto;
    min-height: auto;
  }

  .battlefield-controls-split {
    grid-template-columns: 1fr;
    gap: 0.75rem;
    height: auto;
  }

  .slot-enemy {
    right: 2%;
    top: 10px;
    transform: scale(0.9);
  }

  .slot-player {
    left: 2%;
    bottom: 45px;
    transform: scale(0.9);
  }
}

@media (max-width: 580px) {
  .battlefield-stage {
    min-height: 300px;
  }

  .slot-enemy {
    right: -10px;
    top: 5px;
    transform: scale(0.8);
  }

  .slot-player {
    left: -10px;
    bottom: 35px;
    transform: scale(0.8);
  }

  .hud-encounter-title {
    font-size: 0.8rem;
  }
}

/* ══════════════════════════════════════════════════════════════
   FINAL BOSS CINEMATIC DEFEAT & BACKSTORY PLACEHOLDER
   ══════════════════════════════════════════════════════════════ */
.slot-player.is-player-fading-out {
  opacity: 0 !important;
  transition: opacity 1.2s ease-in-out !important;
  pointer-events: none !important;
}

.final-defeat-cinematic-screen {
  position: absolute;
  inset: 0;
  z-index: 999;
  background: #000000;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow: hidden;
  animation: cinematic-fade-in 0.8s ease-out forwards;
}

@keyframes cinematic-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

.cinematic-centered-creator {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.cinematic-creator-aura {
  position: absolute;
  width: 280px;
  height: 280px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.25) 0%, rgba(147, 51, 234, 0.15) 50%, transparent 75%);
  filter: blur(20px);
  animation: creator-aura-pulse 3s infinite ease-in-out;
  pointer-events: none;
}

@keyframes creator-aura-pulse {
  0%, 100% { transform: scale(0.95); opacity: 0.6; }
  50% { transform: scale(1.15); opacity: 0.9; }
}

.cinematic-creator-art {
  width: clamp(220px, 34vw, 360px);
  height: clamp(220px, 34vw, 360px);
  object-fit: contain;
  filter: drop-shadow(0 0 25px rgba(225, 29, 72, 0.7));
  image-rendering: pixelated;
  z-index: 2;
}

.cinematic-dialogue-tag {
  margin-top: 1.25rem;
  padding: 0.4rem 1.1rem;
  background: rgba(15, 6, 22, 0.9);
  border: 1px solid #e11d48;
  border-radius: 4px;
  color: #fb7185;
  font-size: 0.65rem;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  box-shadow: 0 0 16px rgba(225, 29, 72, 0.45);
  z-index: 2;
}

.cinematic-blackout-stage {
  width: 100%;
  height: 100%;
  background: #000000;
}

.cinematic-backstory-placeholder-panel {
  max-width: 620px;
  width: 90%;
  background: radial-gradient(ellipse at top, #1c0d18 0%, #0a040b 100%);
  border: 2px solid #e11d48;
  border-radius: 8px;
  padding: 2rem 1.75rem;
  text-align: center;
  box-shadow: 0 0 40px rgba(225, 29, 72, 0.45), inset 0 0 30px rgba(15, 6, 22, 0.9);
  z-index: 10;
}

.placeholder-crest {
  font-size: 0.62rem;
  color: #f43f5e;
  letter-spacing: 0.15em;
  margin-bottom: 0.4rem;
}

.placeholder-eyebrow {
  font-size: 0.65rem;
  color: #fbbf24;
  letter-spacing: 0.2em;
  display: block;
  margin-bottom: 0.35rem;
}

.placeholder-title {
  font-size: clamp(1.1rem, 2.8vw, 1.6rem);
  color: #fff;
  letter-spacing: 0.08em;
  margin: 0.25rem 0 0.75rem 0;
  text-shadow: 0 0 16px rgba(225, 29, 72, 0.8);
}

.placeholder-badge {
  display: inline-block;
  padding: 0.3rem 0.85rem;
  background: rgba(225, 29, 72, 0.2);
  border: 1px solid #f43f5e;
  border-radius: 20px;
  color: #fecdd3;
  font-size: 0.58rem;
  letter-spacing: 0.15em;
  margin-bottom: 1.25rem;
}

.placeholder-lore-card {
  background: rgba(0, 0, 0, 0.55);
  border: 1px solid rgba(225, 29, 72, 0.3);
  border-radius: 6px;
  padding: 1rem 1.2rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.placeholder-lore-quote {
  font-size: 0.62rem;
  color: #fca5a5;
  line-height: 1.5;
  margin-bottom: 0.75rem;
  font-style: italic;
}

.placeholder-lore-desc {
  font-size: 0.78rem;
  color: #cbd5e1;
  line-height: 1.55;
  margin: 0;
  font-family: inherit;
}

.placeholder-action-row {
  display: flex;
  justify-content: center;
}

.placeholder-continue-btn {
  letter-spacing: 0.08em;
  padding: 0.8rem 1.8rem;
  font-size: 0.75rem;
  box-shadow: 0 0 20px rgba(225, 29, 72, 0.5);
  transition: all 0.2s ease;
  cursor: pointer;
}

.placeholder-continue-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 30px rgba(225, 29, 72, 0.8);
}
</style>
