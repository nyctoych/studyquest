<template>
  <div class="rpg-adventure-screen">
    <!-- Map Landscape Canvas / Backdrop — Gothic pixel art adventure environment -->
    <div class="map-landscape-wrapper">
      <div class="map-viewport-stage">
        <Transition name="env-crossfade">
          <img
            :key="activeEnvironment.bgUrl"
            :src="activeEnvironment.bgUrl"
            :alt="activeEnvironment.name"
            class="adventure-map-img"
          />
        </Transition>
        <!-- Subtle edge atmosphere vignette & boss glow -->
        <div class="map-vignette" :style="{ background: activeEnvironment.ambientVignette }"></div>
        <div
          class="boss-sanctuary-glow"
          :class="{ 'glow-active': canFightBoss || activeBossDefeated }"
          :style="{
            top: activeEnvironment.bossGlow?.top || '28%',
            left: activeEnvironment.bossGlow?.left || '82.5%'
          }"
        ></div>

        <!-- Interactive 10 Episodes on the Map + CHAPTER CLIMAX BOSS -->
        <Transition name="nodes-fade" mode="out-in">
          <div :key="activeChapterNum" class="stage-nodes-layer">
            <!-- 10 Regular Episodes along the path -->
          <div
            v-for="ep in regularEpisodes"
            :key="ep.partNum"
            class="stage-node-wrapper"
            :class="['node-pos-' + ep.partNum, { 'node-unlocked': ep.unlocked, 'node-locked': !ep.unlocked, 'node-current': ep.isCurrent }]"
            :style="getNodeStyle(ep.partNum)"
            @click="selectEpisode(ep)"
          >
            <!-- Stage Number Tag (e.g. 1-1, 1-2, ... 1-10) -->
            <div class="stage-pill-tag" :class="{ 'tag-current': ep.isCurrent }">
              <span>{{ ep.code }}</span>
            </div>

            <!-- Stone Totem Monolith -->
            <div class="totem-monolith">
              <div class="totem-stone-body">
                <div class="totem-gem-eye" :class="{ 'gem-active': ep.unlocked }"></div>
                <span v-if="!ep.unlocked" class="node-padlock">
                  <PixelEmoji emoji="lock" size="xs" />
                </span>
              </div>
              <!-- Hex Stone Base -->
              <div class="totem-hex-pedestal"></div>
            </div>

            <!-- 3-Star Rating (⭐⭐⭐) below pedestal -->
            <div class="stage-stars-row">
              <span v-for="s in 3" :key="s" class="stage-star" :class="{ 'star-lit': s <= ep.stars }">
                <PixelEmoji emoji="star" size="xs" />
              </span>
            </div>
          </div>

          <!-- 11th Episode: CHAPTER BOSS (Climax Sanctuary on Upper Terrace) -->
          <div
            class="stage-node-wrapper boss-node-wrapper node-pos-boss"
            :class="{ 'node-unlocked': bossEpisode.unlocked, 'node-locked': !bossEpisode.unlocked, 'node-current': bossEpisode.isCurrent }"
            :style="getBossNodeStyle()"
            @click="selectEpisode(bossEpisode)"
          >
            <!-- Boss Stage Pill Tag -->
            <div class="stage-pill-tag boss-pill-tag" :class="{ 'tag-boss-ready': bossEpisode.isCurrent, 'tag-boss-defeated': activeBossDefeated }">
              <PixelEmoji :emoji="bossEpisode.unlocked ? (activeBossDefeated ? 'crown' : 'ogre') : 'lock'" size="xs" />
              <span>{{ bossEpisode.unlocked ? (activeBossDefeated ? 'DEFEATED' : 'BOSS READY') : 'LOCKED' }}</span>
            </div>

            <!-- Boss Standing Unit (Pure transparent silhouette, no artificial box/rectangle) -->
            <div class="boss-standing-sprite-unit">
              <div class="boss-sprite-aura" :class="{ 'aura-active': bossEpisode.unlocked }"></div>

              <div class="boss-sprite-container" :class="{ 'boss-sprite-locked': !bossEpisode.unlocked }">
                <img
                  :src="currentBoss.sprite || currentBoss.animated || currentBoss.happy"
                  :alt="currentBoss.name"
                  class="boss-sprite-img pixel-art animate-boss-float"
                  @error="onBossImgError"
                />
                <!-- Floating pixel lock glyph without any square/rectangular background -->
                <div v-if="!bossEpisode.unlocked" class="boss-floating-lock-glyph">
                  <PixelEmoji emoji="lock" size="sm" />
                </div>
              </div>

              <!-- Dark Runic Hex Pedestal -->
              <div class="boss-hex-pedestal"></div>
            </div>

            <!-- Boss 3-Star Rating -->
            <div class="stage-stars-row boss-stars-row">
              <span v-for="s in 3" :key="s" class="stage-star" :class="{ 'star-lit': s <= bossEpisode.stars }">
                <PixelEmoji emoji="star" size="xs" />
              </span>
            </div>
          </div>
        </div>
      </Transition>
    </div>

      <!-- Newly Unlocked Chapter Celebration Banner -->
      <Transition name="banner-fade">
        <div v-if="showUnlockBanner" class="chapter-unlocked-banner animate-scale-up">
          <PixelEmoji emoji="sparkles" size="sm" />
          <span class="banner-text">✨ CHAPTER {{ activeChapterNum }} UNLOCKED!</span>
          <PixelEmoji emoji="sparkles" size="sm" />
        </div>
      </Transition>

      <!-- Top Overlay: Total Stars (Top Left), Chapter Switcher (Center), Quest Objective (Top Right) -->
      <div class="map-top-bar">
        <!-- Top Left Badges: Stars & Mind Seals -->
        <div class="top-left-badges-group">
          <!-- Total Stars Medal -->
          <div class="total-stars-medal">
            <div class="star-crest-icon">
              <PixelEmoji emoji="star" size="sm" />
            </div>
            <div class="star-crest-info">
              <span class="stars-fraction">{{ totalStarsEarned }}/36</span>
              <span class="stars-label">Stars</span>
            </div>
          </div>

          <!-- Mind Seals Codex Medal -->
          <div class="total-stars-medal mind-seals-codex-medal" @click="showSealsModal = true" title="View Recovered Mind Seals">
            <div class="star-crest-icon seal-crest-icon">
              <span class="seal-icon-symbol">🔮</span>
            </div>
            <div class="star-crest-info">
              <span class="stars-fraction">{{ recoveredSealsCount }}/12</span>
              <span class="stars-label">Mind Seals</span>
            </div>
          </div>
        </div>

        <!-- Chapter Switcher in Center -->
        <div class="chapter-center-col">
          <div class="chapter-nav-pill-wrap">
            <div class="chapter-nav-pill" @click="showChapterSelector = true">
              <button class="ch-nav-btn" @click.stop="prevChapter" :disabled="currentChapterIndex <= 0">◀</button>
              <div class="ch-nav-info">
                <div class="ch-nav-num-row">
                  <span class="ch-nav-num">CHAPTER {{ activeChapterNum }}</span>
                  <span v-if="activeChapterStoryTitle" class="ch-nav-story-highlight">"{{ activeChapterStoryTitle }}"</span>
                  <span class="ch-status-badge-inline" :class="'ch-badge-' + activeChapterStatus">
                    <PixelEmoji :emoji="chapterStatusEmoji(activeChapterStatus)" size="xs" />
                    <span>{{ chapterStatusLabel(activeChapterStatus) }}</span>
                  </span>
                </div>
                <span class="ch-nav-title">{{ activeChapterTitle }}</span>
                <span v-if="activeChapterActTitle" class="ch-nav-act-tag">{{ activeChapterActTitle }}</span>
              </div>
              <button class="ch-nav-btn" @click.stop="nextChapter" :disabled="currentChapterIndex >= (chapters.length - 1)">▶</button>
            </div>
            <div class="ch-extra-btns">
              <button class="story-prologue-btn" @click="showChapterIntroModal = true" title="View Chapter Story Prologue">
                <span>📜 LORE</span>
              </button>
              <button class="all-chapters-btn" @click="showChapterSelector = true" title="View All Chapters">
                <PixelEmoji emoji="scroll" size="xs" />
                <span>CHAPTERS</span>
              </button>
            </div>
          </div>

          <!-- Locked Chapter Banner (Visible when exploring locked domain - positioned below chapter title) -->
          <div v-if="isViewedChapterLocked" class="locked-chapter-banner animate-slide-down">
            <div class="locked-banner-inner">
              <PixelEmoji emoji="lock" size="xs" />
              <span class="locked-banner-text">
                CHAPTER {{ activeChapterNum }} IS LOCKED — Complete Chapter {{ lockedRequiredOrder }} to unlock this chapter.
              </span>
              <button class="btn btn-gothic-crimson btn-xs return-curr-ch-btn" @click="goToCurrentUnlockedChapter">
                GO TO CURRENT CHAPTER →
              </button>
            </div>
          </div>
        </div>

        <!-- Top Right: Black Archive (if unlocked) & Quest Objective -->
        <div class="top-right-group">
          <!-- Black Archive Portal Button -->
          <button
            v-if="blackArchiveUnlocked"
            class="black-archive-top-btn animate-pulse"
            @click="showBlackArchiveModal = true"
            title="Confront The Creator in The Black Archive"
          >
            <span class="ba-key-icon">🗝️</span>
            <span class="ba-key-text">THE BLACK ARCHIVE</span>
          </button>

          <!-- Quest Objective Banner -->
          <div class="quest-objective-banner" @click="openQuestModal">
            <div class="objective-icon-box">
              <PixelEmoji emoji="sword" size="sm" />
            </div>
            <div class="objective-text-group">
              <span class="objective-title">{{ activeObjective.title }}</span>
              <span class="objective-sub">{{ activeObjective.sub }}</span>
            </div>
            <div class="quill-scroll-deco">
              <PixelEmoji emoji="scroll" size="sm" />
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ── CHAPTER SELECTOR MODAL ───────────────────────── -->
    <div v-if="showChapterSelector" class="modal-backdrop rpg-modal-backdrop" @click.self="showChapterSelector = false">
      <div class="stage-modal-card rpg-stage-modal chapter-selector-modal animate-scale-up">
        <div class="stage-modal-header">
          <div class="modal-badge-group">
            <span class="stage-code-badge">
              <PixelEmoji emoji="scroll" size="xs" /> PSYCHOLOGY CHRONICLES
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="showChapterSelector = false">✕</button>
        </div>

        <h3 class="stage-modal-title modal-stage-name">PSYCHOLOGY ADVENTURE CHAPTERS</h3>
        <p class="stage-topic-desc">Select a chapter domain to view progress or replay completed trials.</p>

        <div class="chapters-grid-list">
          <div
            v-for="(ch, idx) in chapters"
            :key="ch.id"
            class="chapter-grid-card"
            :class="{
              'active-card': idx === currentChapterIndex,
              'locked-card': ch.chapter_status === 'locked',
              'completed-card': ch.chapter_status === 'completed' || ch.chapter_status === 'mastered'
            }"
            @click="selectChapterFromList(idx)"
          >
            <div class="ch-card-top">
              <span class="ch-card-num">CHAPTER {{ ch.display_order }}</span>
              <span class="ch-card-status" :class="'status-' + ch.chapter_status">
                <PixelEmoji :emoji="chapterStatusEmoji(ch.chapter_status)" size="xs" />
                <span>{{ chapterStatusLabel(ch.chapter_status) }}</span>
              </span>
            </div>
            <span v-if="ch.act_title" class="ch-card-act-pill">{{ ch.act_title }}</span>
            <h4 class="ch-card-name">{{ ch.name }}</h4>
            <span v-if="ch.chapter_story_title" class="ch-card-story-italic">"{{ ch.chapter_story_title }}"</span>

            <div v-if="ch.chapter_status === 'locked'" class="ch-lock-req">
              <PixelEmoji emoji="lock" size="xs" />
              <span>Complete Chapter {{ ch.required_chapter_order || (ch.display_order - 1) }} to unlock.</span>
            </div>
            <div v-else class="ch-card-meta">
              <span class="ch-parts-count">{{ ch.parts_completed || 0 }}/10 Episodes</span>
              <span v-if="ch.boss_defeated" class="ch-boss-check">👑 Restored (Seal Recovered)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STAGE DETAILS & BATTLE CONFIRMATION MODAL ───── -->
    <div v-if="selectedEpisodeData" class="modal-backdrop rpg-modal-backdrop" @click.self="selectedEpisodeData = null">
      <div class="stage-modal-card rpg-stage-modal animate-scale-up" :class="{ 'boss-modal-card': selectedEpisodeData.isBoss }">
        <div class="stage-modal-header" :class="{ 'boss-modal-header': selectedEpisodeData.isBoss }">
          <div class="modal-badge-group">
            <span class="stage-code-badge modal-stage-code" :class="{ 'boss-code-badge': selectedEpisodeData.isBoss }">
              {{ selectedEpisodeData.code }} {{ selectedEpisodeData.isBoss ? '— CHAPTER CLIMAX' : '' }}
            </span>
            <span v-if="selectedEpisodeData.isCompleted" class="stage-status-badge completed-badge">
              <PixelEmoji emoji="crown" size="xs" /> COMPLETED
            </span>
            <span v-else-if="!selectedEpisodeData.unlocked" class="stage-status-badge locked-badge">
              <PixelEmoji emoji="lock" size="xs" /> LOCKED
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="selectedEpisodeData = null">✕</button>
        </div>

        <h3 class="stage-modal-title modal-stage-name">
          {{ isViewedChapterLocked ? (selectedEpisodeData.isBoss ? 'BOSS LOCKED' : 'CHAPTER LOCKED') : selectedEpisodeData.title }}
        </h3>
        <!-- Story Narrative Phase and Clue -->
        <div v-if="selectedEpisodeData.narrativePhase && !isViewedChapterLocked" class="modal-story-phase-row">
          <span class="modal-phase-pill" :class="'phase-' + selectedEpisodeData.narrativePhase.toLowerCase()">
            {{ selectedEpisodeData.narrativePhase.toUpperCase() }}
          </span>
          <span v-if="selectedEpisodeData.storyTitle" class="modal-story-title-sub">"{{ selectedEpisodeData.storyTitle }}"</span>
        </div>
        <p class="stage-modal-desc stage-topic-desc">
          {{ isViewedChapterLocked ? ('Complete Chapter ' + lockedRequiredOrder + ' to unlock this chapter.') : selectedEpisodeData.description }}
        </p>
        <div v-if="selectedEpisodeData.fractureClue && !isViewedChapterLocked" class="modal-fracture-clue">
          <span class="clue-tag">🔮 FRACTURE CLUE:</span> {{ selectedEpisodeData.fractureClue }}
        </div>

        <!-- Rewards preview -->
        <div class="stage-rewards-preview">
          <h4 class="rewards-sec-title">ESTIMATED REWARDS</h4>
          <div class="rewards-chips-row">
            <div class="reward-chip xp-chip">
              <PixelEmoji emoji="star" size="xs" />
              <span>+{{ selectedEpisodeData.xpReward }} XP</span>
            </div>
            <div class="reward-chip coin-chip">
              <PixelEmoji emoji="coin" size="xs" />
              <span>+{{ selectedEpisodeData.coinReward }} Ancient Gold</span>
            </div>
          </div>
        </div>

        <!-- Locked notice -->
        <div v-if="!selectedEpisodeData.unlocked" class="stage-lock-notice">
          <PixelEmoji emoji="lock" size="xs" />
          <span v-if="isViewedChapterLocked">
            <template v-if="selectedEpisodeData.isBoss">
              Complete Chapter {{ lockedRequiredOrder }} and all preceding trials to unlock this boss battle.
            </template>
            <template v-else>
              Complete Chapter {{ lockedRequiredOrder }} to unlock this chapter.
            </template>
          </span>
          <span v-else-if="selectedEpisodeData.isBoss">
            Complete all 10 preceding parts of Chapter {{ activeChapterNum }} to unlock this boss battle.
          </span>
          <span v-else>
            Complete Part {{ selectedEpisodeData.partNum - 1 }} to unlock this episode.
          </span>
        </div>

        <div class="stage-modal-actions">
          <button class="btn btn-gothic-stone" @click="selectedEpisodeData = null">← RETURN</button>
          <button
            v-if="selectedEpisodeData.unlocked"
            class="btn btn-gothic-crimson btn-lg enter-battle-btn"
            @click="launchBattle"
          >
            {{ selectedEpisodeData.isCompleted ? '⚔️ REPLAY TRIAL' : '⚔️ ENTER BATTLE' }}
          </button>
          <button
            v-else-if="isViewedChapterLocked"
            class="btn btn-gothic-crimson btn-lg"
            @click="goToCurrentUnlockedChapter"
          >
            GO TO CURRENT CHAPTER →
          </button>
          <button v-else class="btn btn-gothic-stone btn-lg" disabled>
            🔒 LOCKED
          </button>
        </div>
      </div>
    </div>

    <!-- ── RECOVERED MIND SEALS & RELICS MODAL ───────────── -->
    <div v-if="showSealsModal" class="modal-backdrop rpg-modal-backdrop" @click.self="showSealsModal = false">
      <div class="stage-modal-card rpg-stage-modal seals-modal animate-scale-up">
        <div class="stage-modal-header">
          <div class="modal-badge-group">
            <span class="stage-code-badge">
              🔮 MINDKEEPER CODEX
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="showSealsModal = false">✕</button>
        </div>
        <h3 class="stage-modal-title modal-stage-name">RECOVERED MIND SEALS & FRACTURE SHARDS</h3>
        <p class="stage-topic-desc">Corrupted guardians restored through psychological mastery return their ancient Mind Seals.</p>
        <div class="seals-grid">
          <div v-for="ch in chapters" :key="ch.id" class="seal-relic-card" :class="{ 'seal-recovered': ch.boss_defeated }">
            <div class="seal-icon-box">
              <span>{{ ch.boss_defeated ? '🔮' : '🔒' }}</span>
            </div>
            <div class="seal-card-text">
              <div class="seal-card-top-row">
                <span class="seal-ch-num">CHAPTER {{ ch.display_order }}</span>
                <span class="seal-status-tag" :class="ch.boss_defeated ? 'tag-restored' : 'tag-distorted'">
                  {{ ch.boss_defeated ? 'RESTORED' : 'DISTORTED' }}
                </span>
              </div>
              <strong class="seal-name">{{ ch.mind_seal_name || ('Mind Seal of Ch ' + ch.display_order) }}</strong>
              <div class="seal-shard-info" v-if="ch.fracture_shard_name && ch.boss_defeated">
                <span class="shard-icon">💎</span>
                <span class="shard-name">{{ ch.fracture_shard_name }}</span>
              </div>
              <span class="shard-hint" v-else>Defeat corrupted guardian to recover</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── CHAPTER STORY PROLOGUE MODAL ─────────────────── -->
    <ChapterIntro
      v-if="showChapterIntroModal"
      :chapter="activeChapterDetails || activeChapter"
      @close="showChapterIntroModal = false"
      @enter="showChapterIntroModal = false"
    />

    <!-- ── BLACK ARCHIVE ENCOUNTER MODAL ────────────────── -->
    <BlackArchiveModal
      v-if="showBlackArchiveModal"
      @close="showBlackArchiveModal = false"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdventureStore } from '../stores/adventure'
import { useToastStore } from '../stores/toast'
import { useTransitionStore } from '../stores/transition'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import { getBossForChapter, resolveAsset } from '../services/bossMapping'
import ChapterIntro from '../components/adventure/ChapterIntro.vue'
import BlackArchiveModal from '../components/adventure/BlackArchiveModal.vue'
import { getChapterEnvironment, preloadAdjacentBackgrounds } from '../config/chapterEnvironments'

const route = useRoute()
const router = useRouter()
const adventure = useAdventureStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()

const ALL_CHAPTERS_FALLBACK = [
  { id: 1, display_order: 1, name: 'Neurodevelopmental Disorders', chapter_status: 'available' },
  { id: 2, display_order: 2, name: 'Schizophrenia Spectrum and Other Psychotic Disorders', chapter_status: 'available' },
  { id: 3, display_order: 3, name: 'Bipolar and Related Disorders', chapter_status: 'locked' },
  { id: 4, display_order: 4, name: 'Depressive Disorders', chapter_status: 'locked' },
  { id: 5, display_order: 5, name: 'Anxiety Disorders', chapter_status: 'locked' },
  { id: 6, display_order: 6, name: 'Obsessive-Compulsive and Related Disorders', chapter_status: 'locked' },
  { id: 7, display_order: 7, name: 'Trauma-, Stressor-Related and Dissociative Disorders', chapter_status: 'locked' },
  { id: 8, display_order: 8, name: 'Somatic Symptom and Related Disorders', chapter_status: 'locked' },
  { id: 9, display_order: 9, name: 'Feeding, Eating, and Elimination Disorders', chapter_status: 'locked' },
  { id: 10, display_order: 10, name: 'Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria', chapter_status: 'locked' },
  { id: 11, display_order: 11, name: 'Substance-Related, Addictive, and Disruptive Disorders', chapter_status: 'locked' },
  { id: 12, display_order: 12, name: 'Neurocognitive, Personality, and Paraphilic Disorders', chapter_status: 'locked' }
]

function resolveInitialChapterIndex() {
  const targetId = Number(route.params.chapterId || route.query.chapter)
  const list = adventure.chapters?.length ? adventure.chapters : ALL_CHAPTERS_FALLBACK
  if (targetId) {
    const idx = list.findIndex(c => c.id === targetId || c.display_order === targetId)
    if (idx !== -1) return idx
    if (targetId >= 1 && targetId <= 12) return targetId - 1
  }
  const savedActive = Number(localStorage.getItem('sq_active_chapter'))
  if (savedActive) {
    const idx = list.findIndex(c => c.id === savedActive || c.display_order === savedActive)
    if (idx !== -1) return idx
    if (savedActive >= 1 && savedActive <= 12) return savedActive - 1
  }
  const unlocked = Number(adventure.currentUnlockedChapterId || localStorage.getItem('sq_unlocked_chapter'))
  if (unlocked) {
    const idx = list.findIndex(c => c.id === unlocked || c.display_order === unlocked)
    if (idx !== -1) return idx
    if (unlocked >= 1 && unlocked <= 12) return unlocked - 1
  }
  return 0
}

const chapters = ref(adventure.chapters?.length ? adventure.chapters : ALL_CHAPTERS_FALLBACK)
const currentChapterIndex = ref(resolveInitialChapterIndex())
const selectedDiff = ref('normal')
const selectedEpisodeData = ref(null)
const showChapterSelector = ref(false)
const showUnlockBanner = ref(false)
const showSealsModal = ref(false)
const showChapterIntroModal = ref(false)
const showBlackArchiveModal = ref(false)

// Active Chapter Data from API
const activeChapterDetails = ref(null)
const activeParts = ref([])
const activeBoss = ref(null)
const canFightBoss = ref(false)

const activeChapter = computed(() => chapters.value[currentChapterIndex.value] || chapters.value[0])
const activeChapterNum = computed(() => activeChapter.value?.display_order || (currentChapterIndex.value + 1))
const activeChapterTitle = computed(() => activeChapter.value?.name || 'General Psychology')
const activeChapterStatus = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.chapter_status || activeChapter.value?.chapter_status || 'available'
  }
  return activeChapter.value?.chapter_status || 'available'
})
const activeBossDefeated = computed(() => Boolean(activeChapterDetails.value?.boss_defeated || activeChapter.value?.boss_defeated))
const currentBoss = computed(() => getBossForChapter(activeChapterNum.value))

const activeEnvironment = computed(() => {
  return getChapterEnvironment(activeChapterNum.value)
})

function getNodeStyle(partNum) {
  const pos = activeEnvironment.value?.nodePositions?.[partNum]
  if (!pos) return {}
  return {
    top: pos.top,
    left: pos.left
  }
}

function getBossNodeStyle() {
  const pos = activeEnvironment.value?.nodePositions?.boss
  if (!pos) return {}
  return {
    top: pos.top,
    left: pos.left
  }
}

const activeChapterStoryTitle = computed(() => {
  return activeChapterDetails.value?.chapter_story_title || activeChapterDetails.value?.story_title || activeChapter.value?.chapter_story_title || ''
})

const activeChapterActTitle = computed(() => {
  return activeChapterDetails.value?.act_title || activeChapter.value?.act_title || ''
})

const recoveredSealsCount = computed(() => {
  return adventure.recoveredMindSeals?.length || chapters.value.filter(c => c.boss_defeated).length
})

const blackArchiveUnlocked = computed(() => {
  return adventure.blackArchiveUnlocked || adventure.allChaptersCompleted || (chapters.value.length >= 12 && chapters.value.every(c => c.boss_defeated))
})

const isViewedChapterLocked = computed(() => {
  return activeChapterStatus.value === 'locked'
})

const lockedRequiredOrder = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.required_chapter_order || activeChapter.value?.required_chapter_order || Math.max(1, activeChapterNum.value - 1)
  }
  return activeChapter.value?.required_chapter_order || Math.max(1, activeChapterNum.value - 1)
})

const lockedRequiredName = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.required_chapter_name || `Chapter ${lockedRequiredOrder.value}`
  }
  return `Chapter ${lockedRequiredOrder.value}`
})

function onBossImgError(e) {
  if (e?.target) {
    e.target.src = resolveAsset('assets/bosses/brain/2happybrain.png')
  }
}

// 10 Regular Episodes for the active chapter
const regularEpisodes = computed(() => {
  const ch = activeChapterNum.value
  const chId = activeChapter.value?.id
  const isChDetailsMatch = activeChapterDetails.value && (activeChapterDetails.value.id === chId || activeChapterDetails.value.display_order === ch)
  const parts = isChDetailsMatch ? (activeParts.value || []) : []
  const isChLocked = isViewedChapterLocked.value

  // If backend provided parts for THIS active chapter, use them
  if (parts.length > 0) {
    return parts.map(p => {
      const isComplete = !isChLocked && (p.part_status === 'completed' || p.part_status === 'mastered')
      const isAvailable = !isChLocked && (p.part_status === 'available' || p.part_status === 'in_progress' || isComplete)
      return {
        id: p.id,
        partNum: p.part_number,
        code: `${ch}-${p.part_number}`,
        title: p.story_title ? `Part ${p.part_number}: ${p.story_title}` : p.title,
        storyTitle: p.story_title,
        narrativePhase: p.narrative_phase,
        fractureClue: p.fracture_clue,
        description: p.story_intro || p.description || p.overview || 'Master foundational psychology runes.',
        unlocked: isAvailable,
        isCompleted: isComplete,
        isCurrent: !isChLocked && (p.part_status === 'available' || p.part_status === 'in_progress'),
        stars: isComplete ? 3 : 0,
        xpReward: p.xp_reward || 50,
        coinReward: p.coin_reward || 25,
      }
    })
  }

  // Fallback 10 items if loading
  const fallback = []
  for (let i = 1; i <= 10; i++) {
    fallback.push({
      id: i,
      partNum: i,
      code: `${ch}-${i}`,
      title: `Episode ${i}`,
      description: 'Psychological scientific inquiry trial.',
      unlocked: !isChLocked && (i === 1),
      isCompleted: false,
      isCurrent: !isChLocked && (i === 1),
      stars: 0,
      xpReward: 50 + i * 10,
      coinReward: 25 + i * 5,
    })
  }
  return fallback
})

// The 11th node is the Climax Chapter Boss
const bossEpisode = computed(() => {
  const ch = activeChapterNum.value
  const isChLocked = isViewedChapterLocked.value
  const isUnlocked = !isChLocked && (canFightBoss.value || activeBossDefeated.value)
  return {
    id: 'boss',
    isBoss: true,
    code: `${ch} BOSS`,
    title: activeBoss.value?.name || currentBoss.value?.name || 'CHAPTER ABERRATION',
    description: activeBoss.value?.flavor_text || currentBoss.value?.description || 'The climax guardian testing all 10 preceding lessons of this chapter.',
    unlocked: isUnlocked,
    isCompleted: !isChLocked && activeBossDefeated.value,
    isCurrent: !isChLocked && canFightBoss.value && !activeBossDefeated.value,
    stars: (!isChLocked && activeBossDefeated.value) ? 3 : 0,
    xpReward: activeBoss.value?.xp_reward || 500,
    coinReward: activeBoss.value?.point_reward || 300,
  }
})

const completedPartsCount = computed(() => {
  return regularEpisodes.value.filter(e => e.isCompleted).length
})

const totalStarsEarned = computed(() => {
  let s = 0
  regularEpisodes.value.forEach(e => s += e.stars)
  s += bossEpisode.value.stars
  return s
})

const activeObjective = computed(() => {
  const ch = activeChapterNum.value
  if (isViewedChapterLocked.value) {
    return {
      title: `Locked Domain — Chapter ${ch}`,
      sub: `Complete Chapter ${lockedRequiredOrder.value} to Unlock`
    }
  }
  if (activeBossDefeated.value) {
    return {
      title: `Chapter ${ch} Conquered!`,
      sub: `Proceed to Chapter ${ch + 1}`
    }
  }
  if (canFightBoss.value) {
    return {
      title: `Defeat Chapter ${ch} Boss`,
      sub: 'Conquer the Aberration Sanctuary'
    }
  }
  const nextPart = regularEpisodes.value.find(e => !e.isCompleted)
  return {
    title: `Clear Episode ${ch}-${nextPart?.partNum || 1}`,
    sub: `Chapter ${ch} Progression`
  }
})

function chapterStatusEmoji(status) {
  switch (status) {
    case 'completed':
    case 'mastered':
      return 'crown'
    case 'boss_available':
      return 'ogre'
    case 'in_progress':
      return 'target'
    case 'available':
      return 'sword'
    case 'locked':
    default:
      return 'lock'
  }
}

function chapterStatusLabel(status) {
  switch (status) {
    case 'completed': return 'COMPLETED'
    case 'mastered':  return 'MASTERED'
    case 'boss_available': return 'BOSS READY'
    case 'in_progress': return 'IN PROGRESS'
    case 'available':   return 'AVAILABLE'
    case 'locked':
    default:
      return 'LOCKED'
  }
}

onMounted(() => {
  // Sync target chapter immediately without waiting
  const targetId = Number(route.params.chapterId || route.query.chapter)
  if (targetId) {
    const idx = chapters.value.findIndex(c => c.id === targetId || c.display_order === targetId)
    if (idx !== -1 && idx !== currentChapterIndex.value) {
      currentChapterIndex.value = idx
    } else if (targetId >= 1 && targetId <= 12 && (targetId - 1) !== currentChapterIndex.value) {
      currentChapterIndex.value = targetId - 1
    }
  }

  // Load active chapter data immediately for the target chapter
  loadActiveChapterData()

  // Load all chapters in background and re-verify index
  loadAllChapters().then(() => {
    const freshTarget = Number(route.params.chapterId || route.query.chapter)
    if (freshTarget) {
      const idx = chapters.value.findIndex(c => c.id === freshTarget || c.display_order === freshTarget)
      if (idx !== -1 && idx !== currentChapterIndex.value) {
        currentChapterIndex.value = idx
        loadActiveChapterData()
      }
    }
  })

  // Celebration banner check
  if (route.query.unlocked === 'true') {
    showUnlockBanner.value = true
    setTimeout(() => { showUnlockBanner.value = false }, 4500)
  }
})

watch(() => [route.params.chapterId, route.query.chapter], ([newParam, newQuery]) => {
  const target = Number(newParam || newQuery)
  if (target) {
    const idx = chapters.value.findIndex(c => c.id === target || c.display_order === target)
    if (idx !== -1 && idx !== currentChapterIndex.value) {
      currentChapterIndex.value = idx
      loadActiveChapterData()
    }
  }
})

async function loadAllChapters() {
  try {
    const res = await adventure.loadChapters()
    if (adventure.chapters?.length) {
      chapters.value = adventure.chapters
    }
  } catch (err) {
    console.warn('Using default chapter list', err)
  }
}

let activeChapterRequestId = 0

async function loadActiveChapterData() {
  const ch = activeChapter.value
  if (!ch) return

  const reqId = ++activeChapterRequestId
  const data = await adventure.loadChapter(ch.id)
  if (reqId !== activeChapterRequestId) {
    return
  }

  if (data?.success) {
    activeChapterDetails.value = data.chapter
    activeParts.value = data.parts || []
    activeBoss.value = data.boss
    canFightBoss.value = Boolean(data.can_fight_boss && !isViewedChapterLocked.value)
  }

  // Preload adjacent environments for instant, seamless transitions
  preloadAdjacentBackgrounds(activeChapterNum.value)

  // Sync route path and query cleanly without loop
  const query = { ...route.query }
  delete query.unlocked
  router.replace({
    path: `/adventure/${ch.id}`,
    query: { ...query, chapter: ch.display_order }
  }).catch(() => {})
}

function switchChapter(idx) {
  if (idx < 0 || idx >= chapters.value.length) return
  currentChapterIndex.value = idx
  activeParts.value = []
  activeChapterDetails.value = null
  const ch = chapters.value[idx]
  if (ch?.id) {
    localStorage.setItem('sq_active_chapter', String(ch.id))
    const query = { ...route.query }
    delete query.unlocked
    router.replace({
      path: `/adventure/${ch.id}`,
      query: { ...query, chapter: ch.display_order }
    }).catch(() => {})
  }
  loadActiveChapterData()
}

async function selectChapterFromList(idx) {
  showChapterSelector.value = false
  switchChapter(idx)
}

function prevChapter() {
  if (currentChapterIndex.value > 0) {
    switchChapter(currentChapterIndex.value - 1)
  }
}

function nextChapter() {
  if (currentChapterIndex.value < chapters.value.length - 1) {
    switchChapter(currentChapterIndex.value + 1)
  }
}

function goToCurrentUnlockedChapter() {
  selectedEpisodeData.value = null
  const unlockedIdx = chapters.value.findIndex(c => c.chapter_status === 'in_progress' || c.chapter_status === 'available' || c.chapter_status === 'boss_available')
  if (unlockedIdx !== -1) {
    switchChapter(unlockedIdx)
  } else {
    const targetId = adventure.currentUnlockedChapterId || 1
    const idx = chapters.value.findIndex(c => c.id === targetId || c.display_order === targetId)
    switchChapter(idx !== -1 ? idx : 0)
  }
}

function goToLatestChapter() {
  goToCurrentUnlockedChapter()
}

function setDifficulty(diff) {
  if (diff !== 'normal') {
    toast.info(`${diff.toUpperCase()} mode unlocks after conquering Chapter ${activeChapterNum.value} Boss!`)
    return
  }
  selectedDiff.value = diff
}

function selectEpisode(ep) {
  selectedEpisodeData.value = ep
}

function launchBattle() {
  const ep = selectedEpisodeData.value
  if (!ep || !ep.unlocked || isViewedChapterLocked.value) {
    return
  }
  selectedEpisodeData.value = null
  const chId = activeChapter.value?.id || 1

  if (ep.isBoss) {
    transitionStore.triggerTravel(`/adventure/${chId}/boss`, router)
  } else {
    transitionStore.triggerTravel(`/adventure/${chId}/part/${ep.partNum}`, router)
  }
}

function openBonusModal() {
  toast.info('Bonus Stage: Complete daily episodes to earn bonus reviewer drop cards!')
}

function openQuestModal() {
  toast.info(`Objective: ${activeObjective.value.title} — ${activeObjective.value.sub}`)
}
</script>

<style scoped>
/* ── ADVENTURE MAP ROOT ──────────────────────────── */
.rpg-adventure-screen {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: var(--gothic-void, #050608);
  user-select: none;
  font-family: var(--font-pixel, 'Press Start 2P', monospace);
}

.map-landscape-wrapper {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: #0d0612;
}

.map-viewport-stage {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100vw;
  height: 56.25vw;
  min-height: 100vh;
  min-width: 177.78vh;
  overflow: hidden;
}

.adventure-map-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: fill;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  filter: contrast(1.05) saturate(1.05);
}

/* Environment Crossfade Animation — Seamless blending with zero black-flash */
.env-crossfade-enter-active,
.env-crossfade-leave-active {
  position: absolute;
  inset: 0;
  transition: opacity 0.35s ease-in-out;
}
.env-crossfade-enter-from,
.env-crossfade-leave-to {
  opacity: 0;
}

/* Stage Nodes Fade Transition between Chapters */
.nodes-fade-enter-active,
.nodes-fade-leave-active {
  transition: opacity 0.22s ease-in-out;
}
.nodes-fade-enter-from,
.nodes-fade-leave-to {
  opacity: 0;
}

.map-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at center, transparent 65%, rgba(6, 7, 10, 0.55) 100%),
    linear-gradient(180deg, rgba(6, 7, 10, 0.4) 0%, transparent 18%, transparent 82%, rgba(6, 7, 10, 0.7) 100%);
  pointer-events: none;
  transition: background 0.6s ease-in-out;
}

.boss-sanctuary-glow {
  position: absolute;
  top: 28%;
  left: 82.5%;
  transform: translate(-50%, -50%);
  width: 220px;
  height: 180px;
  background: radial-gradient(ellipse, rgba(239, 68, 68, 0.28) 0%, rgba(185, 28, 28, 0.08) 50%, transparent 80%);
  animation: bossSanctuaryPulse 3s ease-in-out infinite alternate;
  pointer-events: none;
}
.boss-sanctuary-glow.glow-active {
  background: radial-gradient(ellipse, rgba(239, 68, 68, 0.45) 0%, rgba(234, 179, 8, 0.15) 50%, transparent 80%);
}
@keyframes bossSanctuaryPulse {
  0%   { transform: translate(-50%, -50%) scale(0.95); opacity: 0.6; }
  100% { transform: translate(-50%, -50%) scale(1.15); opacity: 1; }
}

/* Celebration Banner */
.chapter-unlocked-banner {
  position: absolute;
  top: 135px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 50;
  background: rgba(185, 28, 28, 0.95);
  border: 2px solid var(--color-gold-ancient, #e6c845);
  padding: 0.6rem 1.4rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.9), 0 0 20px rgba(230, 200, 69, 0.5);
}
.banner-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  color: #fff;
  letter-spacing: 1.5px;
}
.banner-fade-enter-active,
.banner-fade-leave-active {
  transition: all 0.5s ease;
}
.banner-fade-enter-from,
.banner-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-20px);
}

/* ── TOP BAR ─────────────────────────────────────── */
.map-top-bar {
  position: absolute;
  top: 72px;
  left: 1.2rem;
  right: 1.2rem;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  z-index: 20;
  pointer-events: none;
}

.total-stars-medal {
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(14, 15, 22, 0.95);
  border: 2px solid #3a3225;
  border-left: 3px solid var(--color-gold-ancient, #b59410);
  padding: 0.35rem 0.8rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
}
.star-crest-icon {
  display: flex;
  align-items: center;
}
.star-crest-info {
  display: flex;
  flex-direction: column;
}
.stars-fraction {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--color-gold-ancient, #e6c845);
}
.stars-label {
  font-family: var(--font-body, serif);
  font-size: 0.7rem;
  color: var(--color-bone-dark, #8c8273);
}

.chapter-nav-pill-wrap {
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.chapter-nav-pill {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(10, 11, 15, 0.95);
  border: 2px solid var(--color-crimson-bright, #8b1a1a);
  padding: 0.35rem 0.9rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.8), 0 0 10px rgba(139, 26, 26, 0.3);
  cursor: pointer;
  transition: border-color 0.15s ease;
}
.chapter-nav-pill:hover {
  border-color: var(--color-crimson-bright, #b91c1c);
}
.all-chapters-btn {
  background: rgba(14, 15, 22, 0.95);
  border: 1px solid #3a3b4c;
  color: var(--color-bone, #e2d9cc);
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  padding: 0.55rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.all-chapters-btn:hover {
  border-color: var(--color-gold-ancient, #b59410);
  background: #1c1d29;
}
.ch-nav-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--color-stone-600, #3a3b4c);
  width: 24px;
  height: 24px;
  color: var(--color-bone, #e2d9cc);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  transition: all 0.15s ease;
}
.ch-nav-btn:hover:not(:disabled) {
  background: var(--color-crimson, #8b1a1a);
  color: #fff;
  border-color: var(--color-crimson-bright, #b91c1c);
}
.ch-nav-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}
.ch-nav-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
}
.ch-nav-num-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}
.ch-nav-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  font-weight: 700;
  color: var(--color-crimson-bright, #b91c1c);
  letter-spacing: 1px;
}
.ch-status-badge-inline {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  padding: 0.1rem 0.35rem;
  background: #161722;
  border: 1px solid #2e3040;
}
.ch-badge-completed {
  border-color: var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}
.ch-badge-boss_available {
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #ef4444;
}
.ch-nav-title {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-bone, #e2d9cc);
  letter-spacing: 0.5px;
}

.chapter-center-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
  pointer-events: none;
  flex: 0 0 auto;
}

/* Locked Chapter Banner */
.locked-chapter-banner {
  pointer-events: auto;
  max-width: min(92vw, 760px);
  z-index: 22;
}
.locked-banner-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: rgba(22, 8, 8, 0.96);
  border: 1px solid var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.85), 0 0 12px rgba(185, 28, 28, 0.35);
  padding: 0.35rem 0.85rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: #fca5a5;
  backdrop-filter: blur(6px);
  border-radius: 4px;
}
.locked-banner-text {
  letter-spacing: 0.4px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}
.return-curr-ch-btn {
  font-size: 0.58rem;
  padding: 0.22rem 0.55rem;
  margin-left: 0.4rem;
  white-space: nowrap;
  cursor: pointer;
}

.quest-objective-banner {
  pointer-events: auto;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(14, 15, 22, 0.95);
  border: 2px solid #3a3225;
  border-left: 3px solid var(--color-gold-ancient, #b59410);
  padding: 0.35rem 0.8rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
  transition: transform 0.15s ease, border-color 0.15s ease;
}
.quest-objective-banner:hover {
  transform: translateY(-2px);
  border-color: var(--color-gold-ancient, #b59410);
}
.objective-icon-box {
  width: 28px;
  height: 28px;
  background: #181924;
  border: 1px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
}
.objective-text-group {
  display: flex;
  flex-direction: column;
}
.objective-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
}
.objective-sub {
  font-family: var(--font-body, serif);
  font-size: 0.7rem;
  color: var(--color-bone-dark, #8c8273);
}
.quill-scroll-deco {
  margin-left: 0.2rem;
}

/* ── STAGE NODES LAYER ───────────────────────────── */
.stage-nodes-layer {
  position: absolute;
  inset: 0;
  z-index: 10;
  pointer-events: none;
}

.stage-node-wrapper {
  position: absolute;
  pointer-events: auto;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.stage-node-wrapper:hover {
  transform: translateY(-6px) scale(1.08);
}
.stage-node-wrapper.node-locked {
  filter: grayscale(0.4) brightness(0.75);
  opacity: 0.85;
}
.stage-node-wrapper.node-current {
  animation: currentGothicPulse 2.2s infinite ease-in-out;
}
@keyframes currentGothicPulse {
  0%, 100% {
    transform: scale(1);
    filter: drop-shadow(0 0 6px rgba(185, 28, 28, 0.4));
  }
  50% {
    transform: scale(1.08);
    filter: drop-shadow(0 0 14px rgba(185, 28, 28, 0.85));
  }
}

/* Precise Node Coordinates along the winding gothic road & bridge */
.node-pos-1  { top: 85%; left: 13%; }
.node-pos-2  { top: 78%; left: 20%; }
.node-pos-3  { top: 72%; left: 28%; }
.node-pos-4  { top: 65%; left: 35%; }
.node-pos-5  { top: 60%; left: 42%; }
.node-pos-6  { top: 66%; left: 50%; }
.node-pos-7  { top: 60%; left: 58%; }
.node-pos-8  { top: 52%; left: 66%; }
.node-pos-9  { top: 45%; left: 73%; }
.node-pos-10 { top: 38%; left: 78%; }
.node-pos-boss { top: 21%; left: calc(82.5% - 52px); }

/* Stage Pill Tag */
.stage-pill-tag {
  background: rgba(12, 13, 18, 0.95);
  border: 1px solid var(--color-stone-600, #3a3b4c);
  padding: 0.15rem 0.45rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
  margin-bottom: 4px;
}
.tag-current {
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #fff;
  background: rgba(30, 8, 8, 0.95);
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.6);
}
.boss-pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #ff9999;
  background: rgba(45, 10, 10, 0.95);
  box-shadow: 0 0 12px rgba(185, 28, 28, 0.7);
}
.tag-boss-ready {
  border-color: #22c55e;
  color: #86efac;
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.6);
}
.tag-boss-defeated {
  border-color: var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}

/* Stone Totem Monolith */
.totem-monolith {
  position: relative;
  width: 44px;
  height: 60px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
}
.totem-stone-body {
  position: relative;
  width: 32px;
  height: 44px;
  background: linear-gradient(180deg, #232533 0%, #151620 60%, #0d0e15 100%);
  border: 2px solid #3d4052;
  border-top-color: #555973;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.9), inset 0 1px 2px rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}
.totem-gem-eye {
  width: 6px;
  height: 6px;
  background: #2a2d3d;
  z-index: 2;
  transition: all 0.3s ease;
}
.totem-gem-eye.gem-active {
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 8px #ef4444, 0 0 12px #991b1b;
}
.node-padlock {
  position: absolute;
  z-index: 3;
}
.totem-hex-pedestal {
  width: 42px;
  height: 10px;
  background: linear-gradient(180deg, #1b1c26 0%, #0c0d12 100%);
  border: 1.5px solid #2e3040;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.8);
  margin-top: -2px;
}

/* Stars Row */
.stage-stars-row {
  display: flex;
  align-items: center;
  gap: 1px;
  margin-top: 3px;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.9));
}
.stage-star {
  opacity: 0.2;
  filter: grayscale(1);
}
.stage-star.star-lit {
  opacity: 1;
  filter: grayscale(0) drop-shadow(0 0 4px rgba(230, 200, 69, 0.8));
}

/* Boss Unit (Pure transparent silhouette, no artificial box or rectangular background) */
.boss-node-wrapper {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
.boss-standing-sprite-unit {
  position: relative;
  width: 105px;
  height: 125px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  background: transparent;
  border: none;
  box-shadow: none;
}
.boss-sprite-aura {
  position: absolute;
  top: 35%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 95px;
  height: 95px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.35) 0%, rgba(185, 28, 28, 0.08) 55%, transparent 75%);
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.boss-sprite-aura.aura-active {
  opacity: 1;
  animation: crimsonThrob 2.5s infinite ease-in-out;
}
.boss-sprite-container {
  position: relative;
  width: 85px;
  height: 85px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  box-shadow: none;
}
.boss-sprite-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
  background: transparent;
  border: none;
  box-shadow: none;
  filter: drop-shadow(0 0 10px rgba(185, 28, 28, 0.5));
  transition: filter 0.25s ease, transform 0.25s ease;
}
.boss-node-wrapper:hover .boss-sprite-img {
  filter: drop-shadow(0 0 18px rgba(220, 38, 38, 0.9)) drop-shadow(0 0 6px rgba(255, 120, 120, 0.6));
  transform: scale(1.08);
}
.boss-sprite-locked .boss-sprite-img {
  filter: grayscale(0.65) brightness(0.65) drop-shadow(0 0 8px rgba(0, 0, 0, 0.8));
}
.boss-floating-lock-glyph {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
  background: transparent;
  border: none;
  box-shadow: none;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 8px rgba(185, 28, 28, 0.7));
}
.boss-hex-pedestal {
  width: 78px;
  height: 16px;
  background: linear-gradient(180deg, #2a0e0e 0%, #100505 100%);
  border: 2px solid #8b1a1a;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.9);
}

/* ── BOTTOM BAR ──────────────────────────────────── */
.map-bottom-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 72px;
  background: linear-gradient(0deg, rgba(5, 6, 8, 0.98) 0%, rgba(10, 11, 15, 0.85) 80%, transparent 100%);
  border-top: 2px solid #232533;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  z-index: 20;
}
.bonus-stage-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: none;
  border: none;
  cursor: pointer;
  gap: 0.2rem;
  transition: transform 0.15s ease;
}
.bonus-stage-btn:hover {
  transform: translateY(-2px) scale(1.05);
}
.bonus-gem-emblem {
  position: relative;
  width: 38px;
  height: 38px;
  background: radial-gradient(circle, #8b1a1a 20%, #4a0d0d 80%);
  border: 2px solid #b91c1c;
  box-shadow: 0 4px 10px rgba(139, 26, 26, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}
.bonus-stage-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: var(--color-bone, #e2d9cc);
}
.chapter-bottom-summary {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
}
.cbs-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-bone, #e2d9cc);
}
.cbs-prog {
  font-family: var(--font-body, serif);
  font-size: 0.75rem;
  color: var(--color-gold-ancient, #e6c845);
}

.difficulty-tabs-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.diff-tab {
  background: rgba(18, 19, 26, 0.95);
  border: 1px solid #303342;
  padding: 0.35rem 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.diff-tab:hover {
  border-color: #555973;
}
.diff-tab.active {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(35, 12, 14, 0.95);
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.4);
}
.diff-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
}
.diff-tab.active .diff-name {
  color: #fff;
}
.diff-prog {
  font-family: var(--font-body, serif);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
}

/* ── MODALS ──────────────────────────────────────── */
.modal-backdrop,
.rpg-modal-backdrop {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(3, 4, 6, 0.85);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  box-sizing: border-box;
}

.stage-modal-card,
.rpg-stage-modal {
  width: 94%;
  max-width: 480px;
  max-height: 88vh;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--color-crimson-bright, #b91c1c);
  outline: 2px solid #050608;
  padding: 1.25rem;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 25px rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  z-index: 10000;
  box-sizing: border-box;
}
.boss-modal-card {
  border-color: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 30px rgba(185, 28, 28, 0.45);
}

.stage-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #232533;
  padding-bottom: 0.5rem;
  flex-shrink: 0;
}
.modal-badge-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.stage-code-badge,
.modal-stage-code {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  background: #1c1d29;
  border: 1px solid #3a3b4c;
  color: var(--color-bone, #e2d9cc);
  padding: 0.2rem 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.boss-code-badge {
  background: var(--color-crimson, #8b1a1a);
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #fff;
}
.stage-status-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  padding: 0.2rem 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.completed-badge {
  background: #19160d;
  border: 1px solid var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}
.locked-badge {
  background: #151620;
  border: 1px solid #3a3b4c;
  color: var(--color-bone-dark, #8c8273);
}

.close-modal-btn,
.modal-close-btn {
  background: none;
  border: none;
  color: var(--color-bone-dark, #8c8273);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.2rem 0.4rem;
  line-height: 1;
  transition: color 0.15s ease;
}
.close-modal-btn:hover,
.modal-close-btn:hover {
  color: #fff;
}

.stage-modal-title,
.modal-stage-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.35;
  margin: 0;
}
.stage-modal-desc,
.stage-topic-desc {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.55;
  margin: 0;
}

.stage-rewards-preview {
  background: #14151f;
  border: 1px solid #282a3a;
  padding: 0.75rem 1rem;
}
.rewards-sec-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0 0 0.4rem 0;
}
.rewards-chips-row {
  display: flex;
  gap: 1rem;
}
.reward-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
}
.xp-chip { color: var(--color-gold-ancient, #e6c845); }
.coin-chip { color: var(--color-bone, #e2d9cc); }

.stage-lock-notice {
  background: #18090a;
  border: 1px solid var(--color-crimson, #8b1a1a);
  padding: 0.6rem 0.8rem;
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: #fca5a5;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stage-modal-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

/* Chapter Selector Modal */
.chapter-selector-modal {
  max-width: 620px;
}
.chapters-grid-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  max-height: 55vh;
  overflow-y: auto;
  padding-right: 0.3rem;
}
.chapter-grid-card {
  background: #141520;
  border: 1.5px solid #2e3040;
  padding: 0.75rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.chapter-grid-card:hover:not(.locked-card) {
  border-color: var(--color-crimson-bright, #b91c1c);
  transform: translateY(-2px);
}
.chapter-grid-card.active-card {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(35, 12, 14, 0.9);
}
.chapter-grid-card.completed-card {
  border-color: var(--color-gold-ancient, #b59410);
}
.chapter-grid-card.locked-card {
  opacity: 0.65;
  cursor: not-allowed;
  filter: grayscale(0.5);
}
.ch-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.ch-card-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.ch-card-status {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
}
.status-completed { color: var(--color-gold-ancient, #e6c845); }
.status-boss_available { color: #ef4444; }
.status-in_progress { color: #38bdf8; }
.status-available { color: #22c55e; }
.status-locked { color: #94a3b8; }
.ch-card-name {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
}
.ch-lock-req {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-family: var(--font-body, serif);
  font-size: 0.72rem;
  color: #94a3b8;
}
.ch-card-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: var(--color-bone-dark, #8c8273);
}
.ch-boss-check {
  color: var(--color-gold-ancient, #e6c845);
}

/* Locked warning modal */
.locked-modal {
  text-align: center;
  align-items: center;
}
.lock-req-box {
  background: #180a0a;
  border: 1px solid var(--color-crimson, #8b1a1a);
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: #fca5a5;
  width: 100%;
  box-sizing: border-box;
}

/* ── DARK FANTASY STORY CAMPAIGN STYLES ── */
.top-left-badges-group {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.6rem;
}
.mind-seals-codex-medal {
  cursor: pointer;
  border-color: rgba(168, 85, 247, 0.45);
  background: rgba(18, 10, 28, 0.92);
  transition: transform 0.2s ease, border-color 0.2s ease;
}
.mind-seals-codex-medal:hover {
  transform: translateY(-2px);
  border-color: rgba(216, 180, 254, 0.8);
  box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
}
.seal-crest-icon {
  background: rgba(168, 85, 247, 0.2);
  border-color: #a855f7;
}
.seal-icon-symbol {
  font-size: 1.1rem;
}
.ch-nav-story-highlight {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.76rem;
  color: #fbbf24;
  margin-left: 0.4rem;
}
.ch-nav-act-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #c084fc;
  letter-spacing: 1px;
  display: block;
  margin-top: 2px;
}
.ch-extra-btns {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.story-prologue-btn {
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(168, 85, 247, 0.5);
  color: #e9d5ff;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  padding: 0.45rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}
.story-prologue-btn:hover {
  background: rgba(139, 92, 246, 0.4);
  border-color: #c084fc;
}
.top-right-group {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.6rem;
}
.black-archive-top-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #180512 0%, #4a0429 50%, #0d0107 100%);
  border: 1px solid #f43f5e;
  box-shadow: 0 0 15px rgba(244, 63, 94, 0.45);
  color: #ffe4e6;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  padding: 0.5rem 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.black-archive-top-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 0 25px rgba(244, 63, 94, 0.8);
}
.ch-card-act-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #c084fc;
  margin-bottom: 0.2rem;
  display: inline-block;
}
.ch-card-story-italic {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.82rem;
  color: #fbbf24;
  margin-bottom: 0.4rem;
  display: block;
}
.modal-story-phase-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.modal-phase-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
  letter-spacing: 0.5px;
}
.phase-discovery { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid #3b82f6; }
.phase-investigation { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid #f59e0b; }
.phase-escalation { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid #ef4444; }
.phase-revelation { background: rgba(168, 85, 247, 0.2); color: #e9d5ff; border: 1px solid #a855f7; }
.phase-trial { background: rgba(234, 179, 8, 0.2); color: #fde047; border: 1px solid #eab308; }
.modal-story-title-sub {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.88rem;
  color: #e2e8f0;
}
.modal-fracture-clue {
  background: rgba(147, 51, 234, 0.12);
  border-left: 2px solid #a855f7;
  padding: 0.4rem 0.6rem;
  font-size: 0.82rem;
  color: #e9d5ff;
  margin-top: 0.5rem;
  border-radius: 2px;
}
.modal-fracture-clue .clue-tag {
  font-weight: bold;
  color: #c084fc;
}

/* Seals Modal */
.seals-modal {
  max-width: 680px;
}
.seals-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  max-height: 52vh;
  overflow-y: auto;
  padding-right: 0.3rem;
  margin-top: 1rem;
}
.seal-relic-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(12, 10, 16, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem;
  border-radius: 4px;
  opacity: 0.6;
}
.seal-relic-card.seal-recovered {
  opacity: 1;
  border-color: rgba(168, 85, 247, 0.6);
  background: rgba(25, 15, 35, 0.95);
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.25);
}
.seal-icon-box {
  font-size: 1.5rem;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 4px;
}
.seal-card-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.seal-card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.seal-ch-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #94a3b8;
}
.seal-status-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  padding: 0.15rem 0.35rem;
  border-radius: 2px;
}
.tag-restored { background: rgba(34, 197, 94, 0.2); color: #86efac; border: 1px solid #22c55e; }
.tag-distorted { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.seal-name {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: #f1f5f9;
}
.seal-shard-info {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.76rem;
  color: #38bdf8;
}
.shard-hint {
  font-size: 0.72rem;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 680px) {
  .chapters-grid-list { grid-template-columns: 1fr; }
  .seals-grid { grid-template-columns: 1fr; }
  .map-top-bar { top: 60px; left: 0.6rem; right: 0.6rem; }
  .ch-nav-pill { padding: 0.3rem 0.5rem; gap: 0.4rem; }
  .chapter-bottom-summary { display: none; }
}
</style>
