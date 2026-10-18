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

    <!-- ── FULLSCREEN PLAIN-WHITE READING VIEW ── -->
    <div v-if="isFullscreenReading && resolvedChapter" class="reading-room-fullscreen-view" role="dialog" aria-modal="true">
      <header class="fullscreen-toolbar">
        <div class="toolbar-left">
          <span class="toolbar-badge">STUDY HALL VOLUME</span>
          <span class="toolbar-meta">VOLUME {{ resolvedChapter.roman }} · CHAPTER {{ resolvedChapter.chapterNumberPadded }}</span>
        </div>
        <div class="toolbar-center">
          <h2 class="toolbar-title">{{ resolvedChapter.title }}</h2>
        </div>
        <div class="toolbar-right">
          <!-- Audio Volume Control Widget (Increase/Decrease/Slider/Mute) -->
          <AudioVolumeControl id="reading-room-volume-widget" />

          <button type="button" class="btn-exit-white-reader" @click="isFullscreenReading = false">
            🗗 NORMAL VIEW
          </button>
        </div>
      </header>

      <div class="fullscreen-white-scroll">
        <div class="white-grimoire-sheet">
          <div class="white-doc-banner">
            <span class="white-doc-source">SOURCE: {{ resolvedChapter.sourceVersion || 'DSM-5' }}</span>
            <h1 class="white-doc-heading">{{ resolvedChapter.title }}</h1>
            <p class="white-doc-sub">{{ resolvedChapter.style?.moniker }}</p>
            <p class="white-doc-summary">{{ resolvedChapter.description }}</p>
          </div>

          <div class="white-doctrines-list">
            <h3 class="white-list-title">PSYCHIATRIC CURRICULAR UNITS (10 SEQUENTIAL DOCTRINES)</h3>
            <div
              v-for="page in pagesList"
              :key="page.id"
              class="white-page-row"
              :class="{ 'is-unlocked': !page.isLocked }"
              @click="!page.isLocked && handleSelectPage(page)"
            >
              <div class="white-page-num">
                PAGE {{ page.pageNumberPadded }}
              </div>
              <div class="white-page-info">
                <h4 class="white-page-title">{{ page.title }}</h4>
                <span class="white-page-category">{{ page.categoryLabel }}</span>
                <p class="white-page-desc">{{ page.description }}</p>
              </div>
              <div class="white-page-status">
                <span v-if="page.isCompleted" class="status-badge passed">✓ COMPLETED</span>
                <span v-else-if="!page.isLocked" class="status-badge available">READ →</span>
                <span v-else class="status-badge locked">🔒 LOCKED</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useGameStore } from '../../stores/game'
import { useTransitionStore } from '../../stores/transition'
import { curriculumService } from '../../services/curriculumService'
import { resolveChapterBook, resolveStudyPart } from '../../services/studyHallResolver'
import { resolveAsset } from '../../services/bossMapping'
import { CHARACTER_ANIMATIONS } from '../../config/characterAnimations'
import { battleAudio } from '../../services/battleAudio'
import StudyHallPage from '../../components/studyhall/StudyHallPage.vue'
import AudioVolumeControl from '../../components/ui/AudioVolumeControl.vue'

const isFullscreenReading = ref(false)

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
const gameStore = useGameStore()
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

/* ── FULLSCREEN READING VIEW (STUDY HALL READING ROOM) ── */
.reading-fullscreen-toggle {
  background: rgba(139, 92, 246, 0.25) !important;
  border-color: rgba(139, 92, 246, 0.45) !important;
  color: #ddd6fe !important;
  font-weight: 700 !important;
}
.reading-fullscreen-toggle:hover {
  background: rgba(139, 92, 246, 0.45) !important;
  color: #ffffff !important;
}

.reading-room-fullscreen-view {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  z-index: 999999;
  background: #ffffff !important;
  color: #111827 !important;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.fullscreen-toolbar {
  height: 60px;
  padding: 0 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-shrink: 0;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.toolbar-badge {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 3px 8px;
  border-radius: 4px;
  background: #ede9fe;
  color: #6d28d9;
  border: 1px solid #ddd6fe;
}

.toolbar-meta {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.toolbar-center {
  flex: 1;
  text-align: center;
  overflow: hidden;
}

.toolbar-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toolbar-right {
  display: flex;
  align-items: center;
}

.btn-exit-white-reader {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: #3b82f6;
  border: 1px solid #2563eb;
  border-radius: 6px;
  color: #ffffff;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-exit-white-reader:hover {
  background: #2563eb;
}

.btn-reading-music-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #334155;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.btn-reading-music-toggle:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.btn-reading-music-toggle.is-muted {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

.btn-reading-music-toggle.is-muted:hover {
  background: #fee2e2;
}

.fullscreen-white-scroll {
  flex: 1;
  overflow-y: auto;
  background: #f8fafc;
  padding: 2.5rem 1.5rem;
  display: flex;
  justify-content: center;
}

.white-grimoire-sheet {
  max-width: 900px;
  width: 100%;
  background: #ffffff !important;
  color: #111827 !important;
  box-shadow: 0 4px 25px rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 3rem 3.5rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.white-doc-banner {
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 1.25rem;
}

.white-doc-source {
  font-size: 0.74rem;
  font-weight: 700;
  color: #6d28d9;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  display: block;
  margin-bottom: 0.4rem;
}

.white-doc-heading {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.4rem;
}

.white-doc-sub {
  font-size: 0.95rem;
  color: #475569;
  font-style: italic;
  margin: 0 0 0.75rem;
}

.white-doc-summary {
  font-size: 0.96rem;
  line-height: 1.65;
  color: #334155;
  margin: 0;
}

.white-list-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: 0.04em;
  margin: 0 0 1rem;
}

.white-doctrines-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.white-page-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.white-page-row.is-unlocked {
  cursor: pointer;
}
.white-page-row.is-unlocked:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.white-page-num {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.85rem;
  font-weight: 800;
  color: #3b82f6;
  white-space: nowrap;
  width: 72px;
}

.white-page-info {
  flex: 1;
  min-width: 0;
}

.white-page-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.98rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.2rem;
}

.white-page-category {
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: block;
  margin-bottom: 0.25rem;
}

.white-page-desc {
  font-size: 0.88rem;
  line-height: 1.5;
  color: #475569;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.white-page-status {
  white-space: nowrap;
}

.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 4px;
}
.status-badge.passed {
  background: #dcfce7;
  color: #15803d;
}
.status-badge.available {
  background: #dbeafe;
  color: #1d4ed8;
}
.status-badge.locked {
  background: #f1f5f9;
  color: #94a3b8;
}
</style>
