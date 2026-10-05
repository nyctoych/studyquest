<template>
  <div class="study-hall-phase phase-3-reading-room">
    <!-- Dedicated Phase 3 Reading Room Background -->
    <div class="reading-backdrop-wrap">
      <img
        :src="bgImage"
        alt="Ancient Reading Sanctuary Desk"
        class="reading-bg-img"
      />
      <div class="reading-ambient-vignette"></div>
      <div class="candle-halo candle-center"></div>
      <div class="candle-halo candle-left"></div>
      <div class="candle-halo candle-right"></div>
    </div>

    <!-- Main Reading Stage -->
    <main class="reading-sanctuary-stage animate-scale-up">
      <!-- Loading State -->
      <div v-if="loading" class="reading-loading">
        <div class="loading-spinner"></div>
        <p class="pixel-text">UNROLLING VOLUME PARCHMENT…</p>
      </div>

      <!-- Main Open Grimoire on the Reading Desk -->
      <div v-else-if="resolvedChapter" class="open-grimoire-desk">
        <!-- Desk Ambient Shadow -->
        <div class="grimoire-desk-shadow"></div>

        <!-- The Open Book Spread -->
        <div class="open-grimoire-spread">
          <!-- Grimoire Header Spine Bookmark Banner -->
          <div class="spread-top-arch">
            <div class="volume-herald-block">
              <button class="btn btn-gothic-stone btn-sm back-shelves-btn" @click="handleBackToShelves">
                ← ARCHIVE SHELVES
              </button>
              <span class="herald-vol-tag">VOLUME {{ resolvedChapter.roman }} · CHAPTER {{ resolvedChapter.chapterNumberPadded }}</span>
              <h1 class="herald-title">{{ resolvedChapter.title }}</h1>
              <p class="herald-moniker">{{ resolvedChapter.style?.moniker }}</p>
            </div>

            <!-- Progress & Source Tag -->
            <div class="herald-meta-badges">
              <span class="source-basis-tag">SOURCE: {{ resolvedChapter.sourceVersion || 'DSM-5' }}</span>
              <div class="herald-progress-badge">
                <span class="hp-count"><strong>{{ completedPagesCount }}</strong> / {{ pagesList.length }} PAGES UNLOCKED</span>
                <div class="hp-bar">
                  <div class="hp-fill" :style="{ width: chapterProgressPercent + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Academic / Educational Banner -->
          <div class="grimoire-edu-banner">
            <span class="edu-icon">⚠️</span>
            <span class="edu-text">
              Educational Grimoire · 10 Sequential Psychiatric Units · DSM-5 Diagnostic Criteria &amp; Differential Review
            </span>
          </div>

          <!-- Open Book Pages Two-Page / Grid Layout -->
          <div class="book-pages-container">
            <!-- Left Leaf Header / Intro -->
            <div class="grimoire-left-leaf">
              <div class="leaf-spine-emboss"></div>
              <div class="leaf-content">
                <div class="leaf-heading-row">
                  <span class="leaf-roman">FOLIO I</span>
                  <span class="leaf-subhead">FOUNDATIONAL DOCTRINES (PAGES 1–5)</span>
                </div>

                <div class="pages-list">
                  <StudyHallPage
                    v-for="p in leftPages"
                    :key="p.id"
                    :page="p"
                    @select="handleSelectPage"
                  />
                </div>
              </div>
            </div>

            <!-- Central Grimoire Gutter / Binding Crease -->
            <div class="grimoire-center-gutter">
              <div class="gutter-crease"></div>
              <div class="gutter-ribbon"></div>
              <div class="gutter-stitches">
                <span class="g-stitch"></span>
                <span class="g-stitch"></span>
                <span class="g-stitch"></span>
                <span class="g-stitch"></span>
              </div>
            </div>

            <!-- Right Leaf Header / Advanced Units -->
            <div class="grimoire-right-leaf">
              <div class="leaf-content">
                <div class="leaf-heading-row">
                  <span class="leaf-roman">FOLIO II</span>
                  <span class="leaf-subhead">APPLIED &amp; MASTERY DOCTRINES (PAGES 6–10)</span>
                </div>

                <div class="pages-list">
                  <StudyHallPage
                    v-for="p in rightPages"
                    :key="p.id"
                    :page="p"
                    @select="handleSelectPage"
                  />
                </div>
              </div>
              <div class="leaf-spine-emboss right-emboss"></div>
            </div>
          </div>
        </div>

        <!-- Character presence seated / standing by the reading desk -->
        <div class="desk-character-presence" v-if="characterSprite">
          <img
            :src="characterSprite"
            :alt="auth.nickname"
            class="desk-char-sprite"
          />
          <div class="desk-char-shadow"></div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useTransitionStore } from '../../stores/transition'
import { curriculumService } from '../../services/curriculumService'
import { resolveChapterBook, resolveStudyPart } from '../../services/studyHallResolver'
import { resolveAsset } from '../../services/bossMapping'
import { CHARACTER_ANIMATIONS } from '../../config/characterAnimations'
import { battleAudio } from '../../services/battleAudio'
import StudyHallPage from '../../components/studyhall/StudyHallPage.vue'

const props = defineProps({
  chapterId: {
    type: [Number, String],
    default: null
  },
  onSelectPart: {
    type: Function,
    default: null
  },
  onBackToShelves: {
    type: Function,
    default: null
  }
})

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const transitionStore = useTransitionStore()

const activeChapterId = computed(() => {
  return Number(props.chapterId || route?.params?.chapterId || 1)
})

const bgImage = computed(() => resolveAsset('assets/game/study-hall-reading-room.jpg'))
const rawDomain = ref(null)
const rawParts = ref([])
const loading = ref(true)

const characterSprite = computed(() => {
  const key = auth.characterKey || auth.user?.character_key || 'mage'
  const config = CHARACTER_ANIMATIONS[key] || CHARACTER_ANIMATIONS.mage
  return config?.idle || config?.sprite || ''
})

async function loadChapterData() {
  loading.value = true
  try {
    const cid = activeChapterId.value
    const res = await curriculumService.getDomainDetails(cid)
    if (res?.success) {
      rawDomain.value = res.domain || res
      rawParts.value = res.parts || res.domain?.parts || []
    }
  } catch (err) {
    console.warn('Error loading reading room volume:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadChapterData()
})

watch(() => activeChapterId.value, () => {
  loadChapterData()
})

const resolvedChapter = computed(() => {
  if (!rawDomain.value) return null
  return resolveChapterBook(rawDomain.value, activeChapterId.value - 1)
})

const pagesList = computed(() => {
  return rawParts.value.map(p => resolveStudyPart(p, rawDomain.value))
})

const leftPages = computed(() => {
  return pagesList.value.slice(0, 5)
})

const rightPages = computed(() => {
  return pagesList.value.slice(5, 10)
})

const completedPagesCount = computed(() => {
  return pagesList.value.filter(p => p.isCompleted).length
})

const chapterProgressPercent = computed(() => {
  const total = pagesList.value.length
  return total > 0 ? Math.round((completedPagesCount.value / total) * 100) : 0
})

function handleSelectPage(page) {
  battleAudio.playPageTurn()

  if (props.onSelectPart) {
    transitionStore.playPhaseTransition(
      {
        title: `PAGE ${page.pageNumberPadded}: ${page.title}`,
        subtitle: 'Unfolding reviewer parchment...',
        icon: 'scroll',
        themeColor: 'crimson',
        type: 'library'
      },
      () => props.onSelectPart(page.partNumber)
    )
  } else {
    transitionStore.triggerTravel(
      `/studyhall/chapter/${activeChapterId.value}/part/${page.id || page.partNumber}`,
      router
    )
  }
}

function handleBackToShelves() {
  if (props.onBackToShelves) {
    transitionStore.playPhaseTransition(
      {
        title: 'RETURNING TO ARCHIVE SHELVES',
        subtitle: 'Closing volume and stepping back...',
        icon: 'books',
        themeColor: 'violet',
        type: 'library'
      },
      props.onBackToShelves
    )
  } else {
    transitionStore.triggerTravel('/studyhall/shelves', router)
  }
}
</script>

<style scoped>
.study-hall-phase {
  position: relative;
  width: 100%;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #08060e;
  overflow-x: hidden;
  user-select: none;
}

/* Backdrop */
.reading-backdrop-wrap {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
}

.reading-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  image-rendering: pixelated;
  opacity: 0.9;
}

.reading-ambient-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at 50% 65%, transparent 20%, rgba(5, 3, 10, 0.7) 70%, rgba(3, 2, 7, 0.95) 100%),
    linear-gradient(to top, rgba(3, 2, 6, 0.96) 0%, transparent 45%, rgba(3, 2, 6, 0.6) 100%);
}

.candle-halo {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.22) 0%, transparent 70%);
  pointer-events: none;
}

.candle-center {
  bottom: 25%;
  left: 50%;
  transform: translateX(-50%);
  width: 380px;
  height: 240px;
}

.candle-left {
  bottom: 30%;
  left: 32%;
  width: 180px;
  height: 180px;
}

.candle-right {
  bottom: 30%;
  right: 32%;
  width: 180px;
  height: 180px;
}

/* Stage */
.reading-sanctuary-stage {
  position: relative;
  z-index: 10;
  flex: 1;
  padding: calc(var(--hud-height, 56px) + 1.25rem) 2rem 4rem;
  max-width: 100%;
  margin: 0;
  width: 100%;
}

.back-shelves-btn {
  margin-bottom: 0.65rem;
  display: inline-flex;
}

.reading-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  min-height: 50vh;
  color: #fbbf24;
}

/* The Open Grimoire Desk Spread */
.open-grimoire-desk {
  position: relative;
  width: 100%;
}

.grimoire-desk-shadow {
  position: absolute;
  bottom: -20px;
  left: 5%;
  right: 5%;
  height: 40px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.85) 0%, transparent 75%);
  pointer-events: none;
}

.open-grimoire-spread {
  background: #120e24;
  border: 2px solid rgba(139, 92, 246, 0.4);
  border-radius: 8px;
  box-shadow:
    0 24px 60px rgba(0, 0, 0, 0.95),
    0 0 35px rgba(139, 92, 246, 0.25),
    inset 0 0 30px rgba(0, 0, 0, 0.8);
  overflow: hidden;
}

/* Top Herald */
.spread-top-arch {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1.25rem;
  padding: 1.5rem 2rem;
  background: linear-gradient(180deg, rgba(22, 17, 43, 0.95) 0%, rgba(13, 10, 27, 0.95) 100%);
  border-bottom: 2px solid rgba(139, 92, 246, 0.3);
}

.volume-herald-block {
  flex: 1;
  min-width: 280px;
}

.herald-vol-tag {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  color: #fbbf24;
  display: block;
  margin-bottom: 0.25rem;
}

.herald-title {
  font-family: Georgia, 'Cinzel', serif;
  font-size: clamp(1.2rem, 3vw, 1.85rem);
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.25rem;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.9);
}

.herald-moniker {
  font-size: 0.82rem;
  font-style: italic;
  color: #a78bfa;
  margin: 0;
}

.herald-meta-badges {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.source-basis-tag {
  font-size: 0.68rem;
  font-weight: 800;
  color: #94a3b8;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
}

.herald-progress-badge {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.3rem;
}

.hp-count {
  font-size: 0.72rem;
  color: #e2e8f0;
}

.hp-bar {
  width: 140px;
  height: 6px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 3px;
  overflow: hidden;
}

.hp-fill {
  height: 100%;
  background: linear-gradient(90deg, #8b5cf6 0%, #fbbf24 100%);
  box-shadow: 0 0 8px #fbbf24;
}

/* Educational Banner */
.grimoire-edu-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 2rem;
  background: rgba(245, 158, 11, 0.12);
  border-bottom: 1px solid rgba(245, 158, 11, 0.25);
  font-size: 0.75rem;
  color: #fde68a;
}

/* Book Pages Container */
.book-pages-container {
  display: flex;
  background: #17132a;
  min-height: 650px;
}

.grimoire-left-leaf,
.grimoire-right-leaf {
  flex: 1;
  position: relative;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(20, 16, 38, 0.9) 0%, rgba(14, 11, 28, 0.95) 100%);
}

.leaf-content {
  position: relative;
  z-index: 2;
}

.leaf-heading-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px dashed rgba(139, 92, 246, 0.3);
}

.leaf-roman {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.78rem;
  font-weight: 800;
  color: #fbbf24;
  letter-spacing: 0.1em;
}

.leaf-subhead {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #94a3b8;
}

.pages-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

/* Central Gutter / Binding */
.grimoire-center-gutter {
  width: 24px;
  position: relative;
  background: linear-gradient(90deg, #0b0817 0%, #05040a 50%, #0b0817 100%);
  box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-around;
  flex-shrink: 0;
}

.gutter-crease {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 2px;
  background: rgba(0, 0, 0, 0.9);
}

.gutter-ribbon {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 4px;
  background: #b91c1c;
  opacity: 0.5;
}

.gutter-stitches {
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  height: 80%;
}

.g-stitch {
  width: 6px;
  height: 2px;
  background: #e2d9c8;
  opacity: 0.4;
}

/* Character Presence */
.desk-character-presence {
  position: absolute;
  bottom: -20px;
  right: -25px;
  z-index: 15;
  pointer-events: none;
  opacity: 0.9;
}

.desk-char-sprite {
  width: 76px;
  height: 76px;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.85));
}

.desk-char-shadow {
  width: 50px;
  height: 10px;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 50%;
  margin: -6px auto 0;
}

@media (max-width: 960px) {
  .book-pages-container {
    flex-direction: column;
  }
  .grimoire-center-gutter {
    display: none;
  }
  .desk-character-presence {
    display: none;
  }
}
</style>
