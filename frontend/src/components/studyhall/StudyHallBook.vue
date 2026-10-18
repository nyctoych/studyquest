<template>
  <div
    class="study-hall-book-wrapper"
    :class="[
      `state-${book.state.toLowerCase()}`,
      { 'is-selected': isSelected, 'is-locked': book.isLocked }
    ]"
    @click="handleClick"
    @mouseenter="onHover"
    :tabindex="book.isLocked ? -1 : 0"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
    role="button"
    :aria-label="`Chapter ${book.chapterNumber}: ${book.title}, ${book.completedPages} of ${book.totalPages} pages completed`"
  >
    <!-- Shelf shadow beneath book -->
    <div class="book-shelf-shadow"></div>

    <!-- The 3D Grimoire Book Object -->
    <div
      class="grimoire-book"
      :style="{
        '--book-bg': book.style.coverBg,
        '--book-spine': book.style.spineBg,
        '--book-primary': book.style.primaryColor,
        '--book-accent': book.style.accentColor,
        '--book-glow': book.style.borderGlow,
        '--book-text': book.style.textColor
      }"
    >
      <!-- Thick Grimoire Spine Edge (Left 3D effect) -->
      <div class="grimoire-spine-edge">
        <span class="spine-rib rib-1"></span>
        <span class="spine-rib rib-2"></span>
        <span class="spine-rib rib-3"></span>
        <span class="spine-roman">{{ book.roman }}</span>
      </div>

      <!-- Book Cover -->
      <div class="grimoire-cover">
        <!-- Metal corner brackets (Gothic filigree) -->
        <span class="corner-bracket corner-tl">❖</span>
        <span class="corner-bracket corner-tr">❖</span>
        <span class="corner-bracket corner-bl">❖</span>
        <span class="corner-bracket corner-br">❖</span>

        <!-- Embossed outer border -->
        <div class="cover-emboss-border">
          <!-- Top Tag: Chapter number & Roman -->
          <div class="book-header-row">
            <span class="book-vol-tag">VOL. {{ book.roman }}</span>
            <span class="book-ch-num">CH. {{ book.chapterNumberPadded }}</span>
          </div>

          <!-- Central Occult Sigil & Rune -->
          <div class="book-sigil-seal">
            <div class="sigil-ring">
              <span class="sigil-glyph">{{ book.style.sigil }}</span>
            </div>
            <div class="sigil-glow-pulse"></div>
          </div>

          <!-- Academic Title -->
          <div class="book-title-block">
            <h3 class="book-academic-title" :title="book.title">
              {{ book.title }}
            </h3>
            <p class="book-moniker-sub">
              {{ book.style.moniker }}
            </p>
          </div>

          <!-- Progress / Status Footer -->
          <div class="book-footer-block">
            <!-- Progress Bar -->
            <div class="book-progress-bar">
              <div
                class="book-progress-fill"
                :style="{ width: book.percentage + '%' }"
              ></div>
            </div>

            <div class="book-progress-meta">
              <span class="page-count-text">
                <strong>{{ book.completedPages }}</strong> / {{ book.totalPages }} PAGES
              </span>

              <!-- Status badge -->
              <span v-if="book.isMastered" class="badge-status badge-mastered">
                ⭐ MASTERED
              </span>
              <span v-else-if="book.isCompleted" class="badge-status badge-completed">
                ✓ COMPLETE
              </span>
              <span v-else-if="book.completedPages > 0" class="badge-status badge-progress">
                ⚡ IN PROGRESS
              </span>
              <span v-else-if="!book.isLocked" class="badge-status badge-unopened">
                📖 UNREAD
              </span>
            </div>
          </div>
        </div>

        <!-- Gold Bookmark Ribbon hanging from bottom -->
        <div class="bookmark-ribbon" :class="{ 'ribbon-mastered': book.isMastered }"></div>

        <!-- Lock Overlay if locked -->
        <div v-if="book.isLocked" class="book-locked-overlay">
          <div class="lock-chain-wrap">
            <span class="lock-chain-icon">⛓️</span>
            <span class="lock-sigil">🔒</span>
          </div>
          <span class="lock-label">SEALED ARCHIVE</span>
        </div>
      </div>

      <!-- Right Page Stack (Paper edges) -->
      <div class="grimoire-pages-stack">
        <div class="parchment-layer layer-1"></div>
        <div class="parchment-layer layer-2"></div>
        <div class="parchment-layer layer-3"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { battleAudio } from '../../services/battleAudio'

const props = defineProps({
  book: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['select', 'hover'])

function onHover() {
  emit('hover', props.book)
}

function handleClick() {
  battleAudio.playBookOpen()
  emit('select', props.book)
}
</script>

<style scoped>
.study-hall-book-wrapper {
  position: relative;
  width: 100%;
  max-width: 260px;
  margin: 0 auto;
  perspective: 1000px;
  cursor: pointer;
  user-select: none;
  transition: transform 0.25s cubic-bezier(0.2, 0.8, 0.2, 1);
  outline: none;
}

.study-hall-book-wrapper:focus-visible {
  filter: drop-shadow(0 0 12px #a855f7);
}

.book-shelf-shadow {
  position: absolute;
  bottom: -10px;
  left: 10%;
  width: 80%;
  height: 14px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.75) 0%, transparent 75%);
  pointer-events: none;
  transition: all 0.3s ease;
}

/* Grimoire Structure */
.grimoire-book {
  position: relative;
  display: flex;
  height: 340px;
  background: #09090b;
  border-radius: 4px 10px 10px 4px;
  box-shadow:
    -5px 8px 20px rgba(0, 0, 0, 0.8),
    0 2px 4px rgba(0, 0, 0, 0.6),
    inset 0 0 15px rgba(0, 0, 0, 0.6);
  transform: rotateY(-3deg);
  transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.3s ease;
  overflow: visible;
}

/* Spine */
.grimoire-spine-edge {
  width: 22px;
  background: var(--book-spine, #110e20);
  border-right: 1px solid rgba(0, 0, 0, 0.8);
  border-radius: 4px 0 0 4px;
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  align-items: center;
  padding: 1rem 0;
  box-shadow: inset 3px 0 6px rgba(255, 255, 255, 0.08), inset -3px 0 6px rgba(0, 0, 0, 0.8);
  flex-shrink: 0;
}

.spine-rib {
  width: 14px;
  height: 4px;
  background: rgba(0, 0, 0, 0.7);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 2px;
}

.spine-roman {
  font-size: 0.65rem;
  font-weight: 800;
  color: var(--book-accent, #fbbf24);
  letter-spacing: 0.1em;
  transform: rotate(90deg);
  opacity: 0.85;
}

/* Cover */
.grimoire-cover {
  flex: 1;
  position: relative;
  background: var(--book-bg);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-left: none;
  border-radius: 0 8px 8px 0;
  padding: 0.85rem;
  display: flex;
  flex-direction: column;
  box-shadow: inset 0 0 35px rgba(0, 0, 0, 0.65);
}

.corner-bracket {
  position: absolute;
  font-size: 0.6rem;
  color: var(--book-accent, #fbbf24);
  opacity: 0.65;
  line-height: 1;
  pointer-events: none;
}

.corner-tl { top: 6px; left: 6px; }
.corner-tr { top: 6px; right: 6px; }
.corner-bl { bottom: 6px; left: 6px; }
.corner-br { bottom: 6px; right: 6px; }

.cover-emboss-border {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  padding: 0.65rem 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.5);
}

.book-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: var(--book-accent, #fde68a);
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
  padding-bottom: 0.35rem;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
}

.book-vol-tag {
  opacity: 1;
}

/* Sigil */
.book-sigil-seal {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0.5rem 0;
}

.sigil-ring {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 2px solid var(--book-accent, #fbbf24);
  background: radial-gradient(circle, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.9) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 14px var(--book-glow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.sigil-glyph {
  font-size: 1.25rem;
  color: var(--book-accent, #fbbf24);
  filter: drop-shadow(0 0 4px var(--book-primary));
}

/* Title */
.book-title-block {
  text-align: center;
  margin: 0.25rem 0;
}

.book-academic-title {
  font-size: 0.78rem;
  font-weight: 800;
  line-height: 1.25;
  color: #ffffff;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.95), 0 0 8px rgba(0, 0, 0, 0.8);
}

.book-moniker-sub {
  font-size: 0.62rem;
  font-style: italic;
  color: var(--book-accent, #fde68a);
  opacity: 1;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin: 0;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.95);
}

/* Footer */
.book-footer-block {
  margin-top: 0.5rem;
}

.book-progress-bar {
  width: 100%;
  height: 6px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.4rem;
}

.book-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--book-primary) 0%, var(--book-accent) 100%);
  box-shadow: 0 0 6px var(--book-glow);
  transition: width 0.4s ease;
}

.book-progress-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.62rem;
  color: #cbd5e1;
}

.badge-status {
  padding: 0.15rem 0.4rem;
  border-radius: 3px;
  font-weight: 800;
  font-size: 0.58rem;
  letter-spacing: 0.05em;
}

.badge-mastered {
  background: rgba(234, 179, 8, 0.25);
  border: 1px solid #eab308;
  color: #fef08a;
  box-shadow: 0 0 8px rgba(234, 179, 8, 0.4);
}

.badge-completed {
  background: rgba(16, 185, 129, 0.25);
  border: 1px solid #10b981;
  color: #a7f3d0;
}

.badge-progress {
  background: rgba(59, 130, 246, 0.25);
  border: 1px solid #3b82f6;
  color: #bfdbfe;
}

.badge-unopened {
  background: rgba(148, 163, 184, 0.15);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #94a3b8;
}

/* Bookmark Ribbon */
.bookmark-ribbon {
  position: absolute;
  bottom: -14px;
  right: 18px;
  width: 14px;
  height: 24px;
  background: #b91c1c;
  clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
  z-index: 2;
}

.ribbon-mastered {
  background: #eab308;
}

/* Paper Stack */
.grimoire-pages-stack {
  width: 10px;
  background: repeating-linear-gradient(
    to bottom,
    #e2d9c8,
    #e2d9c8 2px,
    #c4b59d 3px,
    #c4b59d 4px
  );
  border: 1px solid rgba(0, 0, 0, 0.7);
  border-left: none;
  border-radius: 0 3px 3px 0;
  box-shadow: inset 1px 0 2px rgba(0, 0, 0, 0.4);
  flex-shrink: 0;
}

/* Hover & Focus State */
.study-hall-book-wrapper:hover {
  transform: translateY(-8px) scale(1.02);
}

.study-hall-book-wrapper:hover .grimoire-book {
  transform: rotateY(0deg);
  box-shadow:
    0 14px 28px rgba(0, 0, 0, 0.9),
    0 0 20px var(--book-glow);
}

.study-hall-book-wrapper:hover .sigil-ring {
  transform: scale(1.08) rotate(15deg);
  box-shadow: 0 0 20px var(--book-glow);
}

.study-hall-book-wrapper:hover .book-shelf-shadow {
  transform: scale(1.15);
  opacity: 0.85;
}

/* Locked State */
.state-locked .grimoire-book {
  filter: grayscale(0.65) brightness(0.65);
}

.book-locked-overlay {
  position: absolute;
  inset: 0;
  background: rgba(5, 5, 10, 0.75);
  backdrop-filter: blur(2px);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  border-radius: 0 8px 8px 0;
  z-index: 10;
}

.lock-chain-wrap {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 1.5rem;
}

.lock-label {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.15em;
  color: #f87171;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.9);
}
</style>
