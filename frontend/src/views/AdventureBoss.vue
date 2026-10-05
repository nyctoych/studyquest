<template>
  <div class="adv-boss-screen" :class="{ 'is-in-battle': phase === 'battle' }" :style="{ '--boss-accent': bossMeta.accentColor, '--boss-glow': bossMeta.accentGlow }">
    <div class="boss-bg"></div>

    <div class="boss-content" :class="{ 'is-battle-mode': phase === 'battle' }">

      <!-- Loading -->
      <div v-if="loading" class="boss-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">SUMMONING ABERRATION…</p>
      </div>

      <!-- Parts Incomplete / Locked Boss Warning -->
      <div v-else-if="partsIncomplete" class="boss-locked-panel gothic-panel animate-scale-up">
        <div class="locked-icon-crest">
          <PixelEmoji emoji="lock" size="lg" />
        </div>
        <h2 class="locked-title">{{ isFinalBoss ? "THE CREATOR'S DOMAIN SEALED" : "CHAMBER RUNES UNFULFILLED" }}</h2>
        <p class="locked-sub">
          {{ lockedMessage || (isFinalBoss ? "The final gate into the Black Archive remains sealed. You must defeat Chapter 12's normal boss: The Mask of a Thousand Selves before confronting The Creator." : "The sanctuary seal remains unbroken. You must conquer all 10 preliminary episodes of this chapter before the Aberration awakens.") }}
        </p>
        <div v-if="!isFinalBoss" class="locked-parts-status">
          <span class="status-num">{{ partsCompletedCount }} / 10 EPISODES COMPLETED</span>
        </div>
        <router-link :to="'/adventure/' + (isFinalBoss ? '12' : chapterId)" class="btn btn-gothic-crimson btn-lg">
          ← RETURN TO CHAPTER MAP
        </router-link>
      </div>

      <!-- PHASE: CINEMATIC INTRO ─────────────────────────── -->
      <template v-else-if="phase === 'intro'">
        <BossIntro
          :act-title="bossData?.chapter?.act_title || (isFinalBoss ? 'APEX CONFRONTATION' : ('ACT ' + toRoman(bossData?.chapter?.act_number || 1)))"
          :chapter-story-title="bossData?.chapter?.chapter_story_title || bossData?.chapter?.name"
          :boss-name="boss?.name || bossMeta.name"
          :boss-title="boss?.story_title || bossMeta.title || (isFinalBoss ? 'Architect of the Mindrealm & Origin of the Fracture' : 'Guardian of the Mindrealm')"
          :boss-img="bossMeta.animated || bossMeta.happy"
          :dialogue-intro="boss?.dialogue_intro || boss?.flavor_text || bossMeta.description"
          :mind-seal-name="boss?.mind_seal_name || bossData?.chapter?.mind_seal_name"
          :fracture-shard-name="boss?.fracture_shard_name || bossData?.chapter?.fracture_shard_name"
          :max-hp="Number(boss?.max_hp) || (isFinalBoss ? 500 : 300)"
          :xp-reward="Number(boss?.xp_reward) || (isFinalBoss ? 2500 : 500)"
          :coin-reward="Number(boss?.point_reward) || (isFinalBoss ? 1500 : 300)"
          @retreat="goToMap"
          @start="startBossBattle"
        />
      </template>

      <!-- PHASE: BATTLE ─────────────────────────────────── -->
      <template v-else-if="phase === 'battle'">
        <div class="boss-battle-arena animate-scale-up">
          <!-- Classic Turn-Based RPG Battlefield (Pokemon-inspired layout with Chapter Boss / Final Boss) -->
          <AdventureBattlefield
            v-if="!battleComplete"
            :is-boss="true"
            :is-final-boss="isFinalBoss"
            :chapter-id="chapterId"
            :chapter-title="isFinalBoss ? 'Deepest Chamber of the Black Archive' : (bossData?.chapter?.name || 'Sanctum')"
            :part-title="isFinalBoss ? 'APEX TRIAL — CONFRONTATION WITH THE CREATOR' : 'CLIMAX GUARDIAN TRIAL'"
            :enemy-name="boss?.name || bossMeta.name"
            :enemy-title="boss?.story_title || bossMeta.title"
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
            @answer="handleAnswer"
            @answered="onQuestionAnswered"
            @next="nextBossQuestion"
          />

          <!-- Submitting Victory Notice -->
          <div v-if="battleComplete && battleResult === 'victory' && submittingVictory" class="boss-submitting gothic-panel animate-scale-up">
            <div class="loading-spinner"></div>
            <p class="pixel-text">{{ isFinalBoss ? 'COMMUNING WITH GENESIS ARCHIVE & HEALING THE FRACTURE…' : 'COMMUNING WITH ARCHIVES & UNLOCKING DESTINY…' }}</p>
          </div>

          <!-- VICTORY STATE ──────────────────────────────────── -->
          <BossDefeat
            v-else-if="battleComplete && battleResult === 'victory'"
            :is-final-boss="isFinalBoss"
            :boss-name="boss?.name || bossMeta.name"
            :boss-img="bossMeta.animated || bossMeta.happy"
            :dialogue-defeat="completionResult?.dialogue_defeat || boss?.dialogue_defeat || (isFinalBoss ? 'The archive... was never mine to control. Consciousness cannot be bound by rigid taxonomy.' : 'You have freed my consciousness from the Fracture with your psychological insight.')"
            :restoration-text="completionResult?.restoration_text || boss?.restoration_text || (isFinalBoss ? 'The Genesis Singularity steadies. The shattered fragments of the Mindrealm fuse into enduring cognitive harmony.' : 'The cognitive distortion fades as the Mindrealm region returns to equilibrium.')"
            :mind-seal-recovered="completionResult?.mind_seal_recovered || boss?.mind_seal_name || bossData?.chapter?.mind_seal_name"
            :fracture-shard-recovered="completionResult?.fracture_shard_recovered || boss?.fracture_shard_name || bossData?.chapter?.fracture_shard_name"
            :is-first-completion="completionResult?.is_first_completion ?? true"
            :rewards="completionResult?.rewards || { xp: boss?.xp_reward || (isFinalBoss ? 2500 : 500), coins: boss?.point_reward || (isFinalBoss ? 1500 : 300) }"
            :next-chapter-unlocked="Boolean(completionResult?.next_chapter_unlocked && completionResult?.next_chapter_id)"
            :next-chapter-order="completionResult?.next_chapter_order || (chapterOrder + 1)"
            :next-chapter-name="completionResult?.next_chapter_name || ''"
            :black-archive-unlocked="Boolean(completionResult?.black_archive_unlocked || completionResult?.all_chapters_completed)"
            :final-boss-unlocked="Boolean(completionResult?.final_boss_unlocked)"
            @next-chapter="continueToNextChapter"
            @open-black-archive="showBlackArchive = true"
            @open-final-boss="goToFinalBoss"
            @open-campaign-complete="showCampaignCompleteModal = true"
            @return-map="goToMap"
          />

          <!-- DEFEAT STATE ──────────────────────────────────── -->
          <div v-if="battleComplete && battleResult === 'defeat'" class="boss-defeat gothic-panel animate-scale-up">
            <!-- Primary Animated Battle Result Character (Player Character Defeat: Death GIF -> Static PNG) -->
            <BattleResultCharacter result="defeat" size="xl" />
            <h2 class="defeat-title font-pixel text-crimson">{{ isFinalBoss ? 'THE ARCHIVE OVERWHELMED YOU' : 'YOU ARE DEFEATED' }}</h2>
            <p class="defeat-sub">{{ isFinalBoss ? 'The Creator\'s cognitive synthesis demands flawless psychiatric reasoning. Recover and prepare to challenge the singularity again.' : 'Your strength has been exhausted. Recover at least 1 HP before returning to battle.' }}</p>

            <!-- Recovery Information (Rules 2, 9, 10) -->
            <div class="defeat-boss-recovery-box">
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
                @click="retryBoss"
              >
                {{ healthStore.isDefeated ? '🔒 RETRY LOCKED (0 HP)' : 'RE-ATTEMPT TRIAL' }}
              </button>
              <button class="btn btn-gothic-crimson" @click="goToShop">
                🧪 USE HEALTH POTION (+200 HP)
              </button>
              <router-link :to="'/adventure/' + (isFinalBoss ? '12' : chapterId)" class="btn btn-gothic-stone">← CHAPTER SANCTUM</router-link>
            </div>
          </div>
        </div>
      </template>

      <!-- Black Archive Apex Encounter Modal -->
      <BlackArchiveModal v-if="showBlackArchive" @close="showBlackArchive = false" />

      <!-- Campaign Completion Apex Victory Modal -->
      <CampaignCompletionModal
        v-if="showCampaignCompleteModal"
        :results="completionResult"
        @close="showCampaignCompleteModal = false"
        @navigate="onCampaignNavigate"
      />

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useAdventureStore } from '../stores/adventure'
import { useTransitionStore } from '../stores/transition'
import { useToastStore } from '../stores/toast'
import { useHealthStore } from '../stores/health'
import AvatarImg from '../components/AvatarImg.vue'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import api from '../services/api'
import { getBossForChapter, getFinalBoss } from '../services/bossMapping'
import BossIntro from '../components/adventure/BossIntro.vue'
import BossDefeat from '../components/adventure/BossDefeat.vue'
import BlackArchiveModal from '../components/adventure/BlackArchiveModal.vue'
import CampaignCompletionModal from '../components/adventure/CampaignCompletionModal.vue'
import AdventureBattlefield from '../components/adventure/AdventureBattlefield.vue'
import BattleResultCharacter from '../components/adventure/BattleResultCharacter.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const adventure = useAdventureStore()
const transitionStore = useTransitionStore()
const toast = useToastStore()
const healthStore = useHealthStore()

const isFinalBoss = computed(() => route.path.includes('/final-boss'))
const chapterId = computed(() => Number(route.params.chapterId) || 12)
const bossMeta = computed(() => isFinalBoss.value ? getFinalBoss() : getBossForChapter(chapterId.value))

const phase = ref('intro')
const loading = ref(true)
const partsIncomplete = ref(false)
const lockedMessage = ref('')
const showBlackArchive = ref(false)
const showCampaignCompleteModal = ref(false)
const partsCompletedCount = ref(0)
const boss = ref(null)
const questions = ref([])
const bossData = ref(null)
const chapterOrder = ref(1)

// Battle state
const playerHP = ref(100)
const playerMaxHP = ref(100)
const bossHP = ref(300)
const bossMaxHP = ref(300)
const currentQIdx = ref(0)
const answered = ref(false)
const selectedKey = ref(null)
const lastCorrect = ref(false)
const battleComplete = ref(false)
const battleResult = ref(null)
const showDmg = ref(false)
const dmgToEnemy = ref(30)
const dmgToPlayer = ref(25)
const answers = ref([])
const bossAttacking = ref(false)

// Completion state
const submittingVictory = ref(false)
const completionResult = ref(null)

const currentQ = computed(() => questions.value[currentQIdx.value] || null)
const playerHPPct = computed(() => Math.max(0, (playerHP.value / playerMaxHP.value) * 100))
const bossHPPct = computed(() => Math.max(0, (bossHP.value / bossMaxHP.value) * 100))

function toRoman(num) {
  const map = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI', 7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII' }
  return map[num] || String(num || 1)
}

onMounted(async () => {
  if (isFinalBoss.value) {
    chapterOrder.value = 12
    const finalRes = await adventure.loadFinalBoss()
    if (!finalRes.success) {
      if (finalRes.locked) {
        partsIncomplete.value = true
        lockedMessage.value = finalRes.message || 'You must conquer Chapter 12: The Mask of a Thousand Selves before challenging The Creator.'
        loading.value = false
        return
      }
      toast.warning(finalRes.message || 'Failed to load Final Boss encounter.')
      router.push('/adventure/12')
      return
    }
    bossData.value = {
      chapter: finalRes.chapter || { name: 'Deepest Chamber of the Black Archive', act_title: 'THE APEX CONFRONTATION', act_number: 12 },
      boss: finalRes.boss
    }
    boss.value = finalRes.boss
    questions.value = finalRes.questions || []
    loading.value = false
    return
  }

  try {
    const res = await api.get(`/adventure/chapters/${chapterId.value}`)
    if (res.data.success) {
      bossData.value = res.data
      boss.value = res.data.boss
      chapterOrder.value = Number(res.data.chapter?.display_order) || 1
      partsCompletedCount.value = Number(res.data.chapter?.parts_completed) || 0

      // Server-side requirement: Parts 1 to 10 must be complete to challenge boss
      if (res.data.can_fight_boss === false && !res.data.chapter?.boss_defeated) {
        partsIncomplete.value = true
        loading.value = false
        return
      }

      // Requirement 19: Boss questions must come from the CURRENT chapter/domain.
      if (res.data.boss?.questions && res.data.boss.questions.length > 0) {
        questions.value = res.data.boss.questions
      } else {
        try {
          const bRes = await api.get(`/adventure/chapters/${chapterId.value}/boss`)
          if (bRes.data.success && bRes.data.questions?.length > 0) {
            questions.value = bRes.data.questions
          }
        } catch {
          const parts = res.data.parts || []
          const lastPart = parts[parts.length - 1]
          if (lastPart) {
            const pRes = await api.get(`/adventure/chapters/${chapterId.value}/parts/${lastPart.part_number}`)
            questions.value = pRes.data.questions || []
          }
        }
      }
    }
  } catch (err) {
    if (err.response?.status === 403 && err.response?.data?.locked) {
      toast.warning(err.response.data.message || 'Chapter is currently locked.')
      router.push('/adventure')
      return
    }
    console.warn('Fallback boss questions', err)
  }

  if (!questions.value.length) {
    questions.value = [
      {
        question: 'Under DSM-5 diagnostic frameworks, which core requirement distinguishes a clinical mental disorder from normative developmental stress?',
        choices: {
          A: 'Subjective distress and clinically significant impairment across social or occupational domains',
          B: 'Brief existential distress resolving spontaneously within forty-eight hours',
          C: 'Mild temporary deviations in emotional reactivity during major positive transitions',
          D: 'Idiosyncratic cultural expressions sanctioned by community traditions'
        },
        correct_answer: 'A',
        explanation: 'DSM-5 specifies that a mental disorder involves clinically significant disturbance producing distress or impairment in social, occupational, or other important areas of functioning.'
      },
      {
        question: 'In differential diagnosis within psychopathology, what is the primary clinical reason for ruling out substance/medication-induced etiology first?',
        choices: {
          A: 'Substance effects can closely mimic psychiatric syndromes and require targeted medical stabilization',
          B: 'Primary psychiatric disorders cannot co-occur with any form of substance use',
          C: 'Laboratory toxicology screening automatically invalidates any future psychiatric review',
          D: 'Medications only alter mood and have no documented influence on psychotic symptoms'
        },
        correct_answer: 'A',
        explanation: 'DSM-5 emphasizes that substance-induced physiological alterations must be ruled out because intoxication or withdrawal can produce symptoms mimicking primary psychiatric disorders.'
      },
      {
        question: 'What is the diagnostic significance of clinical specifiers in DSM-5 psychiatric classifications?',
        choices: {
          A: 'They describe homogeneous subgroups sharing specific features, severity, or course trajectories',
          B: 'They eliminate the need to evaluate primary diagnostic criteria',
          C: 'They indicate that the patient possesses multiple unrelated psychiatric categories',
          D: 'They serve exclusively as billing codes without clinical descriptive value'
        },
        correct_answer: 'A',
        explanation: 'Specifiers provide opportunity to define a more homogeneous subgrouping of individuals with the disorder who share certain features and convey information relevant to treatment and course.'
      }
    ]
  }

  loading.value = false
})

const lastDmgType = ref('normal')

function getCharacterBaseHealth() {
  const c = auth.selectedCharacter || auth.user?.selected_character
  if (c?.health) return Number(c.health)
  const code = (c?.code || c?.class_type || 'mage').toLowerCase()
  if (code === 'melee') return 200
  if (code === 'range') return 110
  return 150
}

async function startBossBattle() {
  if (healthStore.isDefeated) {
    toast.error('Your strength has been exhausted! Recover at least 1 HP before challenging the boss.')
    return
  }

  const charBaseHealth = getCharacterBaseHealth()
  const userLvl = Math.max(1, Math.min(50, Number(auth.level || 1)))
  const charScaledMaxHP = Math.round(charBaseHealth * (1 + (userLvl - 1) * 0.10))
  bossHP.value = boss.value?.max_hp || 300
  bossMaxHP.value = boss.value?.max_hp || 300
  playerMaxHP.value = healthStore.maxHp || charScaledMaxHP
  playerHP.value = healthStore.currentHp || charScaledMaxHP
  currentQIdx.value = 0
  battleComplete.value = false
  battleResult.value = null
  completionResult.value = null
  dmgToEnemy.value = 0
  dmgToPlayer.value = 0
  lastDmgType.value = 'normal'

  // Start backend server-authoritative battle session
  const startRes = await adventure.startBattleSession({
    encounter_type: 'boss',
    encounter_id: boss.value?.id || 1,
    chapter_id: chapterId.value,
    is_boss: true
  })

  if (startRes && startRes.allowed === false) {
    healthStore.applyHealthData(startRes)
    toast.error(startRes.message || 'Your health is 0. Recover at least 1 HP to battle.')
    phase.value = 'intro'
    return
  }

  phase.value = 'battle'

  if (startRes && startRes.success) {
    if (startRes.player_current_health !== undefined) playerHP.value = Number(startRes.player_current_health)
    if (startRes.player_max_health !== undefined) playerMaxHP.value = Number(startRes.player_max_health)
    if (startRes.enemy_current_health !== undefined) bossHP.value = Number(startRes.enemy_current_health)
    if (startRes.enemy_max_health !== undefined) bossMaxHP.value = Number(startRes.enemy_max_health)
    healthStore.updateFromBattleAction(startRes)
  }
}

async function handleAnswer(payload) {
  const key = typeof payload === 'object' ? payload.key : payload
  const isCritical = typeof payload === 'object' ? Boolean(payload.isCritical) : false
  const resolve = typeof payload === 'object' ? payload.resolve : null

  selectedKey.value = key
  answers.value.push({ 
    question_id: currentQ.value?.id, 
    chosen: key, 
    is_critical: isCritical,
    critical_active: isCritical 
  })

  // Submit to server-authoritative combat API
  const serverRes = await adventure.submitBattleAction({
    battle_id: adventure.activeBattleId,
    question_id: currentQ.value?.id,
    question_index: currentQIdx.value,
    selected_choice: key,
    expected_correct: currentQ.value?.correct_answer || currentQ.value?.correct,
    expected_explanation: currentQ.value?.explanation,
    is_critical: isCritical,
    critical_active: isCritical,
    chapter_id: chapterId.value,
    is_boss: true
  })

  // Hand off authoritative server result to AdventureBattlefield so it plays the animation BEFORE applying damage!
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

  // Check victory / defeat conditions after animations and damage application have completed
  if (playerHP.value <= 0) {
    battleComplete.value = true
    battleResult.value = 'defeat'
    healthStore.currentHp = 0
  } else if (bossHP.value <= 0) {
    battleComplete.value = true
    battleResult.value = 'victory'
    await finalizeBossVictory()
  }
}

async function finalizeBossVictory() {
  submittingVictory.value = true
  try {
    const battleData = {
      player_hp: 100, // Preserved nominal value for backend compatibility
      boss_hp: 0,
      answers: answers.value
    }

    if (isFinalBoss.value) {
      const res = await adventure.completeFinalBossBattle(battleData)
      if (res.success) {
        completionResult.value = res
        showCampaignCompleteModal.value = true
        toast.success('THE CAMPAIGN IS CONQUERED! The Creator has been pacified.')
      } else {
        toast.warning(res.message || 'Victory recording issue')
      }
    } else {
      const res = await adventure.completeBossBattle(chapterId.value, boss.value?.id || 1, battleData)
      if (res.success) {
        completionResult.value = res
        if (res.is_first_completion) {
          toast.success(`Chapter Completed! +${res.rewards?.xp || 500} XP awarded!`)
        }
      } else {
        toast.warning(res.message || 'Victory recording issue')
      }
    }
  } catch (err) {
    console.error('Error finalizing victory', err)
  } finally {
    submittingVictory.value = false
  }
}

function choiceClass(key) {
  if (!answered.value) return ''
  const correct = currentQ.value.correct_answer || currentQ.value.correct
  if (key === correct) return 'correct'
  if (key === selectedKey.value && !lastCorrect.value) return 'wrong'
  return 'dimmed'
}

async function nextBossQuestion() {
  if (battleComplete.value) return

  const isFinal = currentQIdx.value >= questions.value.length - 1

  if (isFinal) {
    const correctCount = answers.value.filter(a => {
      const q = questions.value.find(item => item.id === a.question_id)
      return q && (q.correct_answer || q.correct) === a.chosen
    }).length
    const totalQ = questions.value.length || 1
    const score = Math.round((correctCount / totalQ) * 100)

    // Knowledge Battle: Victory based on demonstration of diagnostic reasoning
    const isWin = score >= 60

    battleComplete.value = true
    if (isWin) {
      bossHP.value = 0
      battleResult.value = 'victory'
      await finalizeBossVictory()
    } else {
      battleResult.value = 'defeat'
    }
    return
  }

  currentQIdx.value++
  answered.value = false
  selectedKey.value = null
}

function retryBoss() {
  if (healthStore.isDefeated) {
    toast.error('Your health is 0! Recover at least 1 HP before challenging the boss again.')
    return
  }
  startBossBattle()
}

function goToShop() {
  transitionStore.triggerTravel('/shop', router)
}

async function continueToNextChapter() {
  const nextId = completionResult.value?.next_chapter_id
  if (nextId) {
    await transitionStore.triggerTravel(`/adventure/${nextId}?unlocked=true`, router)
  } else {
    await transitionStore.triggerTravel('/adventure', router)
  }
}

async function goToFinalBoss() {
  await transitionStore.triggerTravel('/adventure/final-boss', router)
}

async function onCampaignNavigate(dest) {
  showCampaignCompleteModal.value = false
  await transitionStore.triggerTravel(dest, router)
}

async function goToMap() {
  await transitionStore.triggerTravel(`/adventure/${isFinalBoss.value ? 12 : chapterId.value}`, router)
}
</script>

<style scoped>
.adv-boss-screen {
  min-height: 100vh;
  position: relative;
  padding-top: 0;
  background: var(--gothic-void, #050608);
  user-select: none;
}

/* Defeat Boss Recovery Box */
.defeat-boss-recovery-box {
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

.boss-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  background:
    radial-gradient(ellipse at 50% 30%, var(--boss-glow, rgba(139, 26, 26, 0.2)) 0%, transparent 60%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.9) 0%, #050608 100%);
  pointer-events: none;
}
.adv-boss-screen.is-in-battle {
  padding-top: 0;
  margin-top: -0.75rem;
}

.boss-content {
  position: relative;
  z-index: 1;
  max-width: 720px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
  transition: max-width 0.25s ease, padding 0.25s ease;
}

.adv-boss.is-in-battle {
  padding-top: 0;
  min-height: auto;
  box-sizing: border-box;
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
  border-top-color: var(--color-crimson-bright, #b91c1c);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
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
  border-top: 3px solid var(--color-crimson-bright, #b91c1c);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
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
  color: var(--color-crimson-bright, #b91c1c);
}
.locked-sub {
  font-family: var(--font-body, serif);
  font-size: 0.9rem;
  color: var(--color-bone-dark, #8c8273);
  max-width: 480px;
  line-height: 1.6;
}
.locked-parts-status {
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  color: var(--color-gold-ancient, #e6c845);
  background: #161722;
  padding: 0.4rem 0.8rem;
  border: 1px solid #2e3040;
}

/* ── CINEMATIC INTRO ────────────────────────── */
.boss-intro {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}
.boss-portrait-wrap {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
}
.boss-altar-frame {
  position: relative;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, #250808 30%, #0d0606 80%);
  border: 3px solid var(--boss-accent, #8b1a1a);
  outline: 3px solid #050608;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 35px var(--boss-glow, rgba(139, 26, 26, 0.45));
}
.boss-portrait-img {
  width: 150px;
  height: 150px;
  object-fit: contain;
}
.boss-aura-ring {
  position: absolute;
  inset: -12px;
  border: 1px dashed var(--boss-accent, #8b1a1a);
  border-radius: 50%;
  animation: spinSlow 20s linear infinite;
  pointer-events: none;
  opacity: 0.5;
}
@keyframes spinSlow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.boss-intro-info {
  width: 100%;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--boss-accent, #8b1a1a);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.boss-intro-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--boss-accent, #8b1a1a);
  letter-spacing: 1px;
}
.boss-name {
  font-family: var(--font-pixel, monospace);
  font-size: 1.25rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.3;
}
.boss-flavor {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.6;
}
.boss-stats {
  display: flex;
  gap: 1.5rem;
}
.boss-stat {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.bs-val {
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: var(--color-bone, #e2d9cc);
}
.boss-rewards-preview {
  background: #14151f;
  border: 1px solid #282a3a;
  padding: 0.75rem 1rem;
}
.preview-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0 0 0.4rem 0;
}
.reward-preview-row {
  display: flex;
  gap: 1.25rem;
}
.r-item {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-bone, #e2d9cc);
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
.boss-battle-header {
  padding: 1.1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8);
}
.bh-player, .bh-enemy {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}
.bh-enemy {
  flex-direction: row-reverse;
}
.player-avatar-box {
  width: 48px;
  height: 48px;
  background: #181924;
  border: 2px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
}
.boss-avatar-box {
  width: 52px;
  height: 52px;
  background: #18090a;
  border: 2px solid var(--color-crimson, #8b1a1a);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.4);
}
.boss-hud-sprite {
  width: 46px;
  height: 46px;
  object-fit: contain;
}
.hp-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex: 1;
  max-width: 180px;
}
.bh-enemy .hp-info {
  align-items: flex-end;
}
.bh-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-bone, #e2d9cc);
}
.boss-name-hud {
  color: var(--color-crimson-bright, #b91c1c);
}
.hp-bar-wrap {
  width: 100%;
  height: 10px;
  background: #181924;
  border: 1px solid #2e3040;
  overflow: hidden;
}
.hp-bar-fill {
  height: 100%;
  transition: width 0.3s ease;
}
.player-fill {
  background: linear-gradient(90deg, #15803d, #22c55e);
}
.enemy-fill {
  background: linear-gradient(90deg, #b91c1c, #ef4444);
}
.bh-hp {
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: var(--color-bone-dark, #8c8273);
}
.bh-vs {
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  color: var(--color-crimson-bright, #b91c1c);
  padding: 0 0.75rem;
}

.boss-q-counter {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 1px;
}

.boss-question-card {
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.boss-q-text {
  font-family: var(--font-body, serif);
  font-size: 1rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.6;
}
.boss-choices {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.boss-choice {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #161722;
  border: 1.5px solid #2e3040;
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}
.boss-choice:hover:not(:disabled) {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: #1d1822;
  transform: translateY(-1px);
}
.boss-choice.correct {
  border-color: #22c55e !important;
  background: rgba(34, 197, 94, 0.15) !important;
}
.boss-choice.wrong {
  border-color: var(--color-crimson-bright, #b91c1c) !important;
  background: rgba(185, 28, 28, 0.2) !important;
}
.boss-choice.dimmed {
  opacity: 0.45;
}
.bc-key {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-gold-ancient, #e6c845);
  background: #0e0f16;
  border: 1px solid #303344;
  padding: 0.2rem 0.45rem;
}
.bc-text {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.4;
}

.boss-explanation {
  background: #13141e;
  border-left: 3px solid var(--color-crimson-bright, #b91c1c);
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.correct-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #22c55e;
}
.wrong-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.exp-text {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.5;
}
.next-btn {
  margin-top: 1.25rem;
  width: 100%;
  justify-content: center;
}

/* Damage popup */
.boss-dmg-pop {
  position: fixed;
  top: 35%;
  left: 55%;
  transform: translateX(-50%);
  font-family: var(--font-pixel, monospace);
  font-size: 1.8rem;
  color: var(--color-crimson-bright, #b91c1c);
  text-shadow: 0 0 16px rgba(185, 28, 28, 0.9);
  pointer-events: none;
  z-index: 200;
}
.boss-dmg-pop.correct {
  color: #22c55e;
  text-shadow: 0 0 16px rgba(34, 197, 94, 0.9);
}
.dmg-enter-active { animation: dmgFloatUp 0.8s ease forwards; }
@keyframes dmgFloatUp {
  0% { opacity: 1; transform: translateX(-50%) translateY(0); }
  100% { opacity: 0; transform: translateX(-50%) translateY(-50px); }
}

.boss-submitting {
  text-align: center;
  padding: 2.5rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
}

/* ── CHAPTER COMPLETION SEQUENCE ────────────── */
.boss-victory {
  text-align: center;
  padding: 2.5rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-gold-ancient, #b59410);
  outline: 2px solid #050608;
  box-shadow: 0 12px 35px rgba(0, 0, 0, 0.9), 0 0 30px rgba(181, 148, 16, 0.25);
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  align-items: center;
}

.chapter-complete-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}
.chapter-complete-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  letter-spacing: 2px;
  color: var(--color-gold-ancient, #e6c845);
}
.chapter-roman-numeral {
  font-family: var(--font-pixel, monospace);
  font-size: 1.35rem;
  color: #fff;
  letter-spacing: 1.5px;
  text-shadow: 0 0 12px rgba(230, 200, 69, 0.6);
}
.chapter-name-title {
  font-family: var(--font-body, serif);
  font-size: 1.15rem;
  color: var(--color-bone, #e2d9cc);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.boss-defeated-crest {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  background: rgba(185, 28, 28, 0.12);
  border: 1px solid var(--color-crimson-bright, #b91c1c);
  padding: 0.75rem 1.5rem;
  width: 100%;
  max-width: 440px;
  box-sizing: border-box;
}
.crest-crown {
  filter: drop-shadow(0 0 8px rgba(230, 200, 69, 0.8));
}
.boss-defeated-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: var(--color-crimson-bright, #ef4444);
  letter-spacing: 1.5px;
}
.boss-name-sub {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: var(--color-bone-dark, #8c8273);
}

.completion-rewards-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}
.rewards-heading {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 1px;
}
.rewards-pills-row {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}
.r-pill {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  padding: 0.45rem 1rem;
  border: 1px solid #3a3b4c;
  background: #141520;
}
.xp-pill {
  color: var(--color-gold-ancient, #e6c845);
  border-color: #554411;
  background: #19160d;
}
.coin-pill {
  color: var(--color-gold-ancient, #facc15);
  border-color: #665014;
  background: #1a170e;
}

.replay-notice-box {
  background: #141520;
  border: 1px dashed #3a3b4c;
  padding: 0.6rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
  max-width: 440px;
}
.replay-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
}
.replay-text {
  font-family: var(--font-body, serif);
  font-size: 0.75rem;
  color: var(--color-bone-dark, #8c8273);
}

.completion-achievements-box {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  max-width: 440px;
}
.ach-unlocked-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #1a1810;
  border: 1px solid var(--color-gold-ancient, #b59410);
  padding: 0.5rem 0.8rem;
  text-align: left;
}
.ach-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
  display: block;
}
.ach-desc {
  font-family: var(--font-body, serif);
  font-size: 0.75rem;
  color: var(--color-bone, #e2d9cc);
}

/* Next Chapter Preview Card */
.next-chapter-preview-card {
  background: #141522;
  border: 2px solid var(--color-crimson-bright, #b91c1c);
  outline: 1px solid #050608;
  padding: 1.25rem 1.5rem;
  width: 100%;
  max-width: 480px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 0 20px rgba(185, 28, 28, 0.35);
}
.next-label-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.next-title-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-crimson-bright, #ef4444);
  letter-spacing: 1.5px;
}
.next-chapter-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.82rem;
  color: #fff;
  line-height: 1.4;
}
.next-chapter-desc {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.5;
}
.next-continue-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  width: 100%;
  margin-top: 0.5rem;
  cursor: pointer;
}
.next-continue-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(185, 28, 28, 0.7);
}

/* Journey Complete Card */
.journey-complete-card {
  border-color: var(--color-gold-ancient, #e6c845);
  box-shadow: 0 0 35px rgba(230, 200, 69, 0.4);
}
.chapter-complete-tagline {
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: var(--color-crimson-bright, #ef4444);
  letter-spacing: 2px;
}
.journey-complete-text {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  color: var(--color-bone, #e2d9cc);
  max-width: 440px;
  line-height: 1.6;
}
.master-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: var(--color-gold-ancient, #e6c845);
  background: #201a0e;
  border: 1.5px solid var(--color-gold-ancient, #b59410);
  padding: 0.45rem 1rem;
}
.victory-action-btns {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  width: 100%;
  max-width: 380px;
}

/* Defeat */
.boss-defeat {
  text-align: center;
  padding: 2.5rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  background: #0f1017;
  border: 2px solid var(--color-crimson-bright, #b91c1c);
  outline: 2px solid #050608;
}
.defeat-icon { font-size: 3rem; }
.defeat-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.1rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.defeat-sub {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  max-width: 420px;
}
.defeat-actions {
  display: flex;
  gap: 1rem;
  margin-top: 0.5rem;
}

@media (max-width: 580px) {
  .boss-choices { grid-template-columns: 1fr; }
  .boss-intro-actions { flex-direction: column; width: 100%; }
  .boss-intro-actions .btn { width: 100%; text-align: center; }
  .defeat-actions { flex-direction: column; width: 100%; }
  .defeat-actions .btn { width: 100%; text-align: center; }
}

@media (max-width: 480px) {
  .boss-choices { grid-template-columns: 1fr; }
  .boss-portrait-img { max-height: 220px; }
}
</style>
