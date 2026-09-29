<template>
  <div class="adv-part" :class="{ 'is-in-battle': phase === 'battle' }">
    <!-- Gothic Atmosphere Backdrop -->
    <div class="adv-part-bg">
      <img :src="bgImageUrl" class="adv-part-bg-img" alt="Backdrop" />
      <div class="adv-part-bg-overlay"></div>
    </div>

    <div class="adv-part-content" :class="{ 'is-battle-mode': phase === 'battle' }">

      <!-- Loading -->
      <div v-if="loading" class="part-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">COMMUNING WITH THE ARCHIVES…</p>
      </div>

      <!-- Error / Locked -->
      <div v-else-if="error" class="part-error gothic-panel animate-scale-up">
        <div class="error-icon">🔒</div>
        <h3 class="error-title">{{ error }}</h3>
        <router-link :to="'/adventure/' + chapterId" class="btn btn-gothic-stone">← RETURN TO CHAPTER</router-link>
      </div>

      <!-- PHASE: STORY INTRO (LAYER 1) ────────────────── -->
      <template v-else-if="phase === 'story'">
        <StoryScene
          :act-title="part?.act_title || 'ACT I — THE AWAKENING'"
          :chapter-title="part?.chapter_story_title ? (part?.domain_name + ' — ' + part?.chapter_story_title) : (part?.domain_name || 'PSYCHOPATHOLOGY SANCTUM')"
          :part-num="Number(partNum)"
          :part-title="part?.title"
          :story-title="part?.story_title"
          :phase="part?.narrative_phase || 'discovery'"
          :story-intro="part?.story_intro || 'A subtle rift in the Mindrealm destabilizes the foundational concepts of this domain.'"
          :dialogue-speaker="part?.dialogue_speaker || 'Archivist of the Mind'"
          :speaker-title="part?.speaker_title || 'Keeper of Knowledge'"
          :dialogue-text="part?.dialogue_text"
          :academic-connection="part?.academic_connection"
          :enemy-name="part?.enemy_name || 'Manifestation of the Fracture'"
          :enemy-flavor="part?.enemy_flavor"
          :enemy-hp="Number(part?.enemy_max_hp) || 100"
          :boss-name="part?.boss_name"
          @proceed="phase = 'learn'"
          @retreat="$router.push('/adventure/' + chapterId)"
        />
      </template>

      <!-- PHASE: LEARN ──────────────────────────────────── -->
      <template v-else-if="phase === 'learn'">
        <div class="phase-learn gothic-panel animate-scale-up">
          <div class="learn-header">
            <span class="learn-tag">GRIMOIRE REPOSITORY</span>
            <h2 class="learn-title">{{ part?.title }}</h2>
          </div>

          <div class="gothic-sep gothic-sep-gold" style="margin: 0.5rem 0 1.25rem;"></div>

          <div class="learn-body">
            <!-- Overview -->
            <div v-if="part?.overview" class="learn-section">
              <h4 class="sec-title">DOCTRINE OVERVIEW</h4>
              <p class="sec-desc">{{ part.overview }}</p>
            </div>

            <!-- Key concepts -->
            <div v-if="keyConcepts.length" class="learn-section">
              <h4 class="sec-title">🔑 FOUNDATIONAL RUNES</h4>
              <div class="key-concepts-grid">
                <div v-for="(concept, cIdx) in keyConcepts" :key="cIdx" class="concept-card">
                  <div class="concept-card-head">
                    <span class="concept-rune-icon">{{ concept.icon || '🔮' }}</span>
                    <strong class="concept-term">{{ concept.title }}</strong>
                  </div>
                  <p v-if="concept.definition" class="concept-def">{{ concept.definition }}</p>
                </div>
              </div>
            </div>

            <!-- Clinical example -->
            <div v-if="part?.clinical_example" class="learn-section clinical-box">
              <h4 class="sec-title">🏥 CLINICAL CASE STUDY</h4>
              <p class="sec-desc">{{ part.clinical_example }}</p>
            </div>

            <!-- Summary notes -->
            <div v-if="part?.summary_notes" class="learn-section">
              <h4 class="sec-title">📝 SCHOLAR COMPENDIUM</h4>
              <p class="sec-desc">{{ part.summary_notes }}</p>
            </div>
          </div>

          <button class="btn btn-gothic-crimson btn-lg learn-ready-btn" @click="startBattle">
            ⚔️ COMMENCE DUEL!
          </button>
        </div>
      </template>

      <!-- PHASE: BATTLE ─────────────────────────────────── -->
      <template v-else-if="phase === 'battle'">
        <div class="phase-battle animate-scale-up">
          <!-- Classic Turn-Based RPG Battlefield (Pokemon-inspired layout) -->
          <AdventureBattlefield
            v-if="!adventure.battleComplete"
            :is-boss="false"
            :chapter-id="chapterId"
            :part-num="partNum"
            :chapter-title="part?.domain_name || 'PSYCHOPATHOLOGY SANCTUM'"
            :part-title="part?.title || ('Part ' + partNum)"
            :enemy-name="part?.enemy_name || 'Fracture Specter'"
            :enemy-title="part?.enemy_flavor || 'Cognitive Aberration'"
            :boss-hp="adventure.enemyHP"
            :boss-max-hp="adventure.enemyMaxHP"
            :questions="adventure.questions"
            :current-q-index="adventure.currentQuestionIndex"
            :current-q="currentQ"
            :answered="answered"
            :selected-key="selectedKey"
            :last-correct="lastCorrect"
            :is-last-question="isLastQuestion"
            @answer="handleAnswer"
            @next="nextQuestion"
          />

          <!-- Battle complete (victory) -->
          <div v-if="adventure.battleComplete && adventure.battleResult === 'victory'" class="battle-victory gothic-panel animate-scale-up">
            <div class="victory-emoji">🏆</div>
            <h3 class="victory-title">SPECTER BANISHED!</h3>
            <p class="victory-desc">{{ part?.story_after || 'You have cleansed this trial of falsehood.' }}</p>
            <button class="btn btn-gothic-crimson btn-lg" @click="claimRewards">
              CLAIM SANCTUM REWARDS →
            </button>
          </div>

          <!-- Battle complete (defeat) -->
          <div v-if="adventure.battleComplete && adventure.battleResult === 'defeat'" class="battle-defeat gothic-panel animate-scale-up">
            <div class="defeat-emoji">💀</div>
            <h3 class="defeat-title">CONSCIOUSNESS FRACTURED</h3>
            <p class="defeat-desc">The psychological aberration proved too intense. Study the compendium and try again.</p>
            <div class="defeat-btn-row">
              <button class="btn btn-gothic-crimson" @click="retryBattle">RETRY TRIAL</button>
              <router-link :to="'/adventure/' + chapterId" class="btn btn-gothic-stone">← CHAPTER MAP</router-link>
            </div>
          </div>
        </div>
      </template>

      <!-- PHASE: REWARD ─────────────────────────────────── -->
      <template v-else-if="phase === 'reward'">
        <div class="phase-reward gothic-panel animate-scale-up" v-if="rewardData">
          <div class="reward-header">
            <PixelEmoji emoji="crown" size="lg" />
            <h2 class="reward-title">EPISODE MASTERED!</h2>
          </div>

          <!-- Story Outro / Escalation narrative -->
          <div v-if="part?.story_after" class="reward-story-outro animate-scale-up">
            <div class="outro-tag">⚡ NARRATIVE EQUILIBRIUM RESTORED</div>
            <p class="outro-text">{{ part.story_after }}</p>
            <div v-if="part?.fracture_clue" class="outro-clue">
              <span class="clue-label">FRACTURE RESONANCE:</span> {{ part.fracture_clue }}
            </div>
          </div>

          <div class="reward-score">
            <span class="score-num">{{ rewardData.correct }}</span>
            <span class="score-sep">/</span>
            <span class="score-total">{{ rewardData.total }}</span>
            <span class="score-label">ACCURACY</span>
          </div>
          <div class="reward-items">
            <div class="reward-item-card">
              <span class="ri-icon"><PixelEmoji emoji="star" size="sm" /></span>
              <span class="ri-amount">+{{ rewardData.xp_earned }} XP</span>
            </div>
            <div class="reward-item-card">
              <span class="ri-icon"><PixelEmoji emoji="coin" size="sm" /></span>
              <span class="ri-amount">+{{ rewardData.coins_earned }} COINS</span>
            </div>
          </div>

          <!-- If Part 10 or boss unlocked, celebrate boss unsealed -->
          <div v-if="rewardData.boss_unlocked || partNum === 10" class="boss-unsealed-callout animate-scale-up">
            <div class="callout-head">
              <PixelEmoji emoji="ogre" size="md" />
              <span class="callout-title">CHAPTER CLIMAX TRIAL UNSEALED!</span>
            </div>
            <p class="callout-sub">All 10 foundational episodes cleared. The guardian {{ part?.boss_name || 'Sanctum Guardian' }} now awaits your psychological challenge!</p>
            <router-link :to="'/adventure/' + chapterId + '/boss'" class="btn btn-gothic-crimson btn-lg callout-boss-btn">
              ⚔️ CONFRONT {{ (part?.boss_name || 'GUARDIAN').toUpperCase() }} →
            </router-link>
          </div>

          <router-link :to="'/adventure/' + chapterId" class="btn btn-gothic-stone btn-lg" style="margin-top: 0.5rem;">
            CONTINUE ODYSSEY →
          </router-link>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAdventureStore } from '../stores/adventure'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { resolveAsset } from '../services/bossMapping'
import AvatarImg from '../components/AvatarImg.vue'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import StoryScene from '../components/adventure/StoryScene.vue'
import AdventureBattlefield from '../components/adventure/AdventureBattlefield.vue'

const route = useRoute()
const adventure = useAdventureStore()
const auth = useAuthStore()
const toast = useToastStore()

const chapterId = computed(() => Number(route.params.chapterId))
const partNum = computed(() => Number(route.params.partNum))

const phase = ref('story')
const loading = computed(() => adventure.loading)
const error = computed(() => adventure.error)
const part = computed(() => adventure.currentPart)
const rewardData = ref(null)

// Battle state
const answered = ref(false)
const selectedKey = ref(null)
const lastCorrect = ref(false)
const showDamage = ref(false)
const dmgToEnemy = ref(20)
const dmgToPlayer = ref(15)
const answers = ref([])
const battleStartTime = ref(Date.now())

const currentQ = computed(() => adventure.currentQuestion)
const isLastQuestion = computed(() => {
  const qCount = adventure.questions?.length || 1
  return adventure.currentQuestionIndex >= qCount - 1
})
const playerHPPercent = computed(() => Math.max(0, (adventure.playerHP / adventure.playerMaxHP) * 100))
const enemyHPPercent = computed(() => Math.max(0, (adventure.enemyHP / adventure.enemyMaxHP) * 100))
const bgImageUrl = computed(() => resolveAsset('assets/game/gothic_adventure_map.jpg'))

const keyConcepts = computed(() => {
  let kc = part.value?.key_concepts
  if (!kc) return []
  if (typeof kc === 'string') {
    try {
      kc = JSON.parse(kc)
    } catch {
      return []
    }
  }
  if (!Array.isArray(kc)) return []
  return kc.map(item => {
    if (typeof item === 'string') {
      try {
        const parsed = JSON.parse(item)
        if (typeof parsed === 'object' && parsed !== null) {
          return {
            title: parsed.title || parsed.term || parsed.name || '',
            definition: parsed.definition || parsed.desc || parsed.description || '',
            icon: parsed.icon || '🔮'
          }
        }
      } catch {
        return { title: item, definition: '', icon: '🔮' }
      }
      return { title: item, definition: '', icon: '🔮' }
    }
    if (typeof item === 'object' && item !== null) {
      return {
        title: item.title || item.term || item.name || '',
        definition: item.definition || item.desc || item.description || '',
        icon: item.icon || '🔮'
      }
    }
    return { title: String(item), definition: '', icon: '🔮' }
  })
})

onMounted(async () => {
  const data = await adventure.loadPart(chapterId.value, partNum.value)
  if (!data) return
})

function startBattle() {
  adventure.resetBattle()
  phase.value = 'battle'
  answered.value = false
  selectedKey.value = null
  answers.value = []
  battleStartTime.value = Date.now()
}

function choiceClass(key) {
  if (!answered.value) return ''
  const correct = currentQ.value?.correct_answer
  if (key === correct) return 'correct'
  if (key === selectedKey.value && key !== correct) return 'wrong'
  return 'dimmed'
}

function handleAnswer(key) {
  if (answered.value) return
  answered.value = true
  selectedKey.value = key
  const correct = currentQ.value?.correct_answer
  lastCorrect.value = key === correct

  // Track answer
  answers.value.push({
    question_id: currentQ.value?.id,
    chosen: key,
  })

  // Knowledge Battle: Player takes NO damage!
  // Enemy HP is updated ONLY on correct answers to track defeat progress:
  if (lastCorrect.value) {
    const qCount = adventure.questions?.length || 1
    const correctCount = answers.value.filter(a => {
      const q = adventure.questions.find(item => item.id === a.question_id)
      return q && q.correct_answer === a.chosen
    }).length

    const targetHp = correctCount >= qCount
      ? 0
      : Math.max(0, Math.round(adventure.enemyMaxHP * (1 - (correctCount / qCount))))
    const dmg = Math.max(1, adventure.enemyHP - targetHp)
    dmgToEnemy.value = dmg
    adventure.enemyHP = targetHp
  } else {
    // Incorrect answer: enemy takes NO damage, HP remains untouched
    dmgToEnemy.value = 0
  }
}

function nextQuestion() {
  const qCount = adventure.questions?.length || 1
  const isFinal = adventure.currentQuestionIndex >= qCount - 1

  if (isFinal) {
    // Grade answers: Knowledge battle!
    const correctCount = answers.value.filter(a => {
      const q = adventure.questions.find(item => item.id === a.question_id)
      return q && q.correct_answer === a.chosen
    }).length
    const score = Math.round((correctCount / qCount) * 100)

    // Victory if score >= 60% (e.g. 2 of 3 questions correct)
    const isWin = score >= 60

    if (isWin) {
      adventure.enemyHP = 0
      adventure.battleResult = 'victory'
    } else {
      adventure.battleResult = 'defeat'
    }
    adventure.battleComplete = true
    return
  }

  adventure.advanceQuestion()
  answered.value = false
  selectedKey.value = null
}

async function claimRewards() {
  const timeSpent = Math.floor((Date.now() - battleStartTime.value) / 1000)
  const data = await adventure.submitPartAnswers(part.value?.id, answers.value, timeSpent)
  if (data?.success) {
    rewardData.value = data
    await auth.fetchUser()
    phase.value = 'reward'
  } else {
    toast.error('Failed to save progress. Try again.')
  }
}

function retryBattle() {
  startBattle()
}
</script>

<style scoped>
.adv-part {
  min-height: 100vh;
  position: relative;
  padding-top: 68px;
  background: var(--gothic-void, #050608);
  user-select: none;
}
.adv-part-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  pointer-events: none;
}
.adv-part-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  filter: brightness(0.42) contrast(1.15) saturate(0.85);
  transform: scale(1.02);
}
.adv-part-bg-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 30%, rgba(139, 26, 26, 0.18) 0%, transparent 60%),
    radial-gradient(ellipse at 50% 90%, rgba(10, 11, 15, 0.8) 0%, #050608 95%);
}
.adv-part-content {
  position: relative;
  z-index: 1;
  max-width: 700px;
  margin: 0 auto;
  padding: 1.5rem 1.25rem 3rem;
  transition: max-width 0.25s ease, padding 0.25s ease;
}

.adv-part.is-in-battle {
  padding-top: 56px;
}

.adv-part-content.is-battle-mode {
  max-width: 100% !important;
  width: 100% !important;
  margin: 0 !important;
  padding: 0.25rem 0.75rem 2rem !important;
}

.part-loading, .part-error {
  text-align: center;
  padding: 4rem 1rem;
}
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #232533;
  border-top-color: var(--color-crimson-bright, #b91c1c);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 1.25rem;
}
.pixel-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 0.08em;
}
@keyframes spin { to { transform: rotate(360deg); } }
.error-icon { font-size: 2.8rem; margin-bottom: 0.75rem; }
.error-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.9rem;
  color: var(--color-crimson-bright, #b91c1c);
  margin-bottom: 1.25rem;
}

/* ── STORY PHASE ────────────────────────────── */
.phase-story {
  padding: 1.75rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.story-header {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: center;
}
.story-domain-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 0.06em;
}
.story-part-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-bone-dark, #8c8273);
}
.story-body { margin-bottom: 1.5rem; }
.story-enemy-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}
.enemy-skull { font-size: 1.2rem; }
.enemy-name-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.85rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.story-intro-text {
  font-family: var(--font-body, serif);
  color: var(--color-bone, #e2d9cc);
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 1rem;
}
.story-dialogue {
  background: #151620;
  border-left: 3px solid var(--color-crimson-bright, #b91c1c);
  padding: 0.85rem 1rem;
}
.dialogue-speaker {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-gold-ancient, #e6c845);
  margin-bottom: 0.3rem;
  display: block;
}
.dialogue-text {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: var(--color-bone-dark, #8c8273);
  font-style: italic;
  margin: 0;
}
.story-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

/* ── LEARN PHASE ────────────────────────────── */
.phase-learn {
  padding: 1.75rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.learn-header { margin-bottom: 0.5rem; }
.learn-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 0.1em;
}
.learn-title {
  font-family: var(--font-body, serif);
  font-size: 1.35rem;
  font-weight: 700;
  color: var(--color-bone, #e2d9cc);
  margin: 0.25rem 0 0 0;
}
.learn-body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
  max-height: 50vh;
  overflow-y: auto;
  padding-right: 0.5rem;
}
.sec-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0 0 0.4rem 0;
}
.sec-desc {
  font-family: var(--font-body, serif);
  font-size: 0.9rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.6;
  margin: 0;
}
.key-concepts-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.6rem;
}
.concept-card {
  background: rgba(20, 21, 32, 0.92);
  border: 1px solid #282a3a;
  border-left: 3px solid var(--color-crimson-bright, #b91c1c);
  padding: 0.85rem 1rem;
  transition: all 0.2s ease;
}
.concept-card:hover {
  background: rgba(26, 28, 42, 0.96);
  border-color: #3f425c;
  border-left-color: var(--color-gold-ancient, #e6c845);
}
.concept-card-head {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  margin-bottom: 0.35rem;
}
.concept-rune-icon {
  font-size: 1.15rem;
  line-height: 1;
}
.concept-term {
  display: block;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: var(--color-gold-ancient, #e6c845);
  letter-spacing: 0.04em;
}
.concept-def {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
  line-height: 1.5;
}
.clinical-box {
  background: #13141e;
  border-left: 3px solid #38bdf8;
  padding: 0.75rem 0.9rem;
}
.learn-ready-btn {
  width: 100%;
  justify-content: center;
}

/* ── BATTLE PHASE ───────────────────────────── */
.phase-battle {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.battle-hud {
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8);
}
.battle-combatant {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}
.enemy-side {
  flex-direction: row;
  justify-content: flex-end;
}
.combatant-avatar-box {
  width: 44px;
  height: 44px;
  background: #181924;
  border: 2px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
}
.enemy-avatar-box {
  border-color: var(--color-crimson, #8b1a1a);
  background: #160a0b;
}
.enemy-icon-glyph { font-size: 1.3rem; }
.bat-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  flex: 1;
  max-width: 160px;
}
.bat-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: var(--color-bone, #e2d9cc);
}
.enemy-name-hud {
  color: var(--color-crimson-bright, #b91c1c);
}
.hp-bar-wrap {
  width: 100%;
  height: 9px;
  background: #06070a;
  border: 1px solid #282a3a;
  position: relative;
  overflow: hidden;
}
.hp-bar-fill {
  height: 100%;
  transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.player-fill {
  background: #22c55e;
  box-shadow: 0 0 6px rgba(34, 197, 94, 0.6);
}
.enemy-fill {
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 6px rgba(185, 28, 28, 0.8);
}
.bat-hp {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: var(--color-bone-dark, #8c8273);
}
.battle-q-counter {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
  padding: 0 0.5rem;
}

.damage-pop {
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
.damage-pop.correct {
  color: #22c55e;
  text-shadow: 0 0 16px rgba(34, 197, 94, 0.9);
}
.dmg-enter-active { animation: dmgFloatUp 0.7s ease forwards; }
@keyframes dmgFloatUp {
  0% { opacity: 1; transform: translateX(-50%) translateY(0); }
  100% { opacity: 0; transform: translateX(-50%) translateY(-40px); }
}

.battle-question-card {
  padding: 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.9);
}
.q-difficulty-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: var(--color-gold-ancient, #e6c845);
  margin-bottom: 0.5rem;
}
.q-text {
  font-family: var(--font-body, serif);
  font-size: 0.98rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.6;
  margin: 0 0 1.25rem 0;
}
.q-choices {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.q-choice {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 0.9rem;
  background: #14151f;
  border: 1px solid #2a2d3d;
  color: var(--color-bone, #e2d9cc);
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}
.q-choice:hover:not(:disabled) {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: #1e1215;
}
.q-choice.correct {
  border-color: #22c55e;
  background: rgba(34, 197, 94, 0.15);
  color: #86efac;
}
.q-choice.wrong {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(185, 28, 28, 0.2);
  color: #fca5a5;
}
.q-choice.dimmed { opacity: 0.3; }
.choice-key {
  width: 22px;
  height: 22px;
  background: #090a0e;
  border: 1px solid #333647;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  flex-shrink: 0;
}
.choice-text {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
}

.q-explanation {
  margin-top: 1.25rem;
  padding: 0.85rem;
  background: #141520;
  border: 1px solid #282a3a;
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
  font-size: 0.82rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.5;
}
.next-btn {
  margin-top: 1.25rem;
  width: 100%;
  justify-content: center;
}

.battle-victory, .battle-defeat {
  text-align: center;
  padding: 2.5rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
}
.battle-victory {
  border-color: var(--color-gold-ancient, #b59410);
}
.victory-emoji, .defeat-emoji { font-size: 3.5rem; margin-bottom: 0.75rem; }
.victory-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.1rem;
  color: var(--color-gold-ancient, #e6c845);
  margin-bottom: 0.5rem;
}
.victory-desc, .defeat-desc {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  margin-bottom: 1.25rem;
}
.defeat-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.1rem;
  color: var(--color-crimson-bright, #b91c1c);
  margin-bottom: 0.5rem;
}
.defeat-btn-row {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

/* ── REWARD PHASE ───────────────────────────── */
.phase-reward {
  text-align: center;
  padding: 2.5rem 1.5rem;
  background: #0f1017;
  border: 2px solid var(--color-gold-ancient, #b59410);
  outline: 2px solid #050608;
}
.reward-header { margin-bottom: 1.25rem; }
.reward-star { font-size: 3rem; display: block; margin-bottom: 0.5rem; }
.reward-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.2rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0;
}
.reward-score {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.4rem;
  margin-bottom: 1.5rem;
}
.score-num {
  font-family: var(--font-pixel, monospace);
  font-size: 2rem;
  color: #22c55e;
}
.score-sep {
  font-family: var(--font-pixel, monospace);
  font-size: 1.2rem;
  color: var(--color-bone-dark, #8c8273);
}
.score-total {
  font-family: var(--font-pixel, monospace);
  font-size: 1.2rem;
  color: var(--color-bone, #e2d9cc);
}
.score-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone-dark, #8c8273);
  margin-left: 0.3rem;
}
.reward-items {
  display: flex;
  justify-content: center;
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}
.reward-item-card {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #141520;
  border: 1px solid #282a3a;
  padding: 0.5rem 1rem;
}
.ri-icon { font-size: 1.1rem; }
.ri-amount {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  color: var(--color-bone, #e2d9cc);
}

.boss-unsealed-callout {
  background: rgba(185, 28, 28, 0.12);
  border: 2px solid var(--color-crimson-bright, #b91c1c);
  outline: 1px solid #050608;
  padding: 1.25rem;
  margin: 1.25rem 0 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
  box-shadow: 0 0 20px rgba(185, 28, 28, 0.4);
}
.callout-head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.callout-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: var(--color-crimson-bright, #ef4444);
  letter-spacing: 1px;
}
.callout-sub {
  font-family: var(--font-body, serif);
  font-size: 0.82rem;
  color: var(--color-bone-dark, #8c8273);
  max-width: 440px;
  line-height: 1.5;
  margin: 0;
}
.callout-boss-btn {
  margin-top: 0.5rem;
  width: 100%;
  max-width: 320px;
}

.reward-story-outro {
  background: rgba(18, 12, 24, 0.85);
  border: 1px solid rgba(220, 38, 38, 0.3);
  padding: 1rem 1.25rem;
  margin: 1rem 0 1.25rem;
  border-radius: 4px;
  text-align: left;
}
.outro-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  color: #f59e0b;
  letter-spacing: 1px;
  margin-bottom: 0.5rem;
}
.outro-text {
  font-family: var(--font-body, serif);
  font-size: 0.95rem;
  color: #e2e8f0;
  line-height: 1.6;
  font-style: italic;
  margin-bottom: 0.5rem;
}
.outro-clue {
  font-size: 0.82rem;
  color: #c084fc;
  background: rgba(147, 51, 234, 0.12);
  padding: 0.4rem 0.6rem;
  border-radius: 3px;
  border-left: 2px solid #a855f7;
}
.clue-label {
  font-weight: bold;
  color: #e9d5ff;
}

@media (max-width: 580px) {
  .q-choices { grid-template-columns: 1fr; }
}
</style>
