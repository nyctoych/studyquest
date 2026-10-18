<template>
  <div
    class="vault-book-wrapper"
    :class="[
      `format-${book.file_type ? book.file_type.toLowerCase() : 'pdf'}`,
      { 'is-selected': isSelected }
    ]"
    @click="handleClick"
    @mouseenter="onHover"
    tabindex="0"
    role="button"
    :aria-label="`Book ${formattedBookNumber}: ${book.title}, format ${book.file_type}`"
    @keydown.enter="handleClick"
    @keydown.space.prevent="handleClick"
  >
    <!-- Shelf shadow beneath book -->
    <div class="book-shelf-shadow"></div>

    <!-- The 3D Grimoire Book Object -->
    <div
      class="vault-book"
      :style="bookThemeStyles"
    >
      <!-- Thick Grimoire Spine Edge (Left 3D effect) -->
      <div class="vault-spine-edge">
        <span class="spine-rib rib-1"></span>
        <span class="spine-rib rib-2"></span>
        <span class="spine-rib rib-3"></span>
        <span class="spine-book-number">BK. {{ formattedBookNumber }}</span>
      </div>

      <!-- Book Cover -->
      <div class="vault-cover">
        <!-- Metal corner brackets (Gothic filigree) -->
        <span class="corner-bracket corner-tl">❖</span>
        <span class="corner-bracket corner-tr">❖</span>
        <span class="corner-bracket corner-bl">❖</span>
        <span class="corner-bracket corner-br">❖</span>

        <!-- Embossed outer border -->
        <div class="cover-emboss-border">
          <!-- Top Tag: Book number & Format badge -->
          <div class="book-header-row">
            <span class="book-vault-tag">BOOK {{ formattedBookNumber }}</span>
            <span class="book-format-badge" :class="`badge-${book.file_type ? book.file_type.toLowerCase() : 'pdf'}`">
              {{ formatLabel }}
            </span>
          </div>

          <!-- Central Occult Sigil & Rune -->
          <div class="book-sigil-seal">
            <div class="sigil-ring">
              <span class="sigil-glyph">{{ formatSigil }}</span>
            </div>
            <div class="sigil-glow-pulse"></div>
          </div>

          <!-- Manuscript Title (Prominently displayed) -->
          <div class="book-title-block">
            <h3 class="book-reviewer-title" :title="book.title">
              {{ book.title }}
            </h3>
            <p v-if="book.description" class="book-desc-sub" :title="book.description">
              {{ book.description }}
            </p>
          </div>

          <!-- Metadata Footer -->
          <div class="book-footer-block">
            <div class="book-meta-row">
              <span class="meta-size-text">{{ formatFileSize(book.file_size) }}</span>
              <span class="meta-date-text">{{ formatDate(book.created_at) }}</span>
            </div>

            <!-- Reading Prompt / Action -->
            <div class="book-read-prompt">
              <span class="read-rune">✦</span>
              <span class="read-label">CONSULT REVIEWER</span>
              <span class="read-rune">✦</span>
            </div>
          </div>
        </div>

        <!-- Gold Bookmark Ribbon hanging from bottom -->
        <div class="bookmark-ribbon" :class="`ribbon-${book.file_type ? book.file_type.toLowerCase() : 'pdf'}`"></div>

        <!-- Owner Quick Actions (Hover Overlay) -->
        <div v-if="isOwner" class="owner-actions-overlay" @click.stop>
          <button
            class="owner-btn btn-edit"
            title="Edit Title & Description"
            @click.stop="$emit('edit', book)"
            aria-label="Edit Reviewer"
          >
            ✏️
          </button>
          <button
            class="owner-btn btn-delete"
            title="Delete Reviewer"
            @click.stop="$emit('delete', book)"
            aria-label="Delete Reviewer"
          >
            🗑️
          </button>
        </div>
      </div>

      <!-- Right Page Stack (Paper edges) -->
      <div class="vault-pages-stack">
        <div class="parchment-layer layer-1"></div>
        <div class="parchment-layer layer-2"></div>
        <div class="parchment-layer layer-3"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { battleAudio } from '../../services/battleAudio'

const props = defineProps({
  book: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  },
  isOwner: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['open', 'edit', 'delete', 'hover'])

// Padded book number (e.g. 01, 02, 12, 105)
const formattedBookNumber = computed(() => {
  const num = props.book.book_number || 1
  return num < 10 ? `0${num}` : `${num}`
})

// Format label (uppercase)
const formatLabel = computed(() => {
  return (props.book.file_type || 'PDF').toUpperCase()
})

// Sigil icon based on format
const formatSigil = computed(() => {
  const type = (props.book.file_type || '').toLowerCase()
  switch (type) {
    case 'pdf':  return '📜'
    case 'docx':
    case 'doc':  return '🪶'
    case 'pptx':
    case 'ppt':  return '🔮'
    case 'txt':  return '✒️'
    default:     return '📖'
  }
})

// Visual theme styling based on document format
const bookThemeStyles = computed(() => {
  const type = (props.book.file_type || '').toLowerCase()
  switch (type) {
    case 'pdf':
      // Crimson / Ruby Grimoire
      return {
        '--book-bg': 'linear-gradient(145deg, #1f0b18 0%, #360f24 45%, #150610 100%)',
        '--book-spine': 'linear-gradient(90deg, #12040d 0%, #29081a 50%, #150610 100%)',
        '--book-primary': '#f43f5e',
        '--book-accent': '#fb7185',
        '--book-glow': 'rgba(244, 63, 94, 0.45)',
        '--book-border': 'rgba(244, 63, 94, 0.35)',
        '--book-text': '#ffe4e6'
      }
    case 'docx':
    case 'doc':
      // Arcane Sapphire / Azure Grimoire
      return {
        '--book-bg': 'linear-gradient(145deg, #071529 0%, #0c274c 45%, #051020 100%)',
        '--book-spine': 'linear-gradient(90deg, #030a14 0%, #081d38 50%, #040e1b 100%)',
        '--book-primary': '#38bdf8',
        '--book-accent': '#7dd3fc',
        '--book-glow': 'rgba(56, 189, 248, 0.45)',
        '--book-border': 'rgba(56, 189, 248, 0.35)',
        '--book-text': '#e0f2fe'
      }
    case 'pptx':
    case 'ppt':
      // Amber / Solar Grimoire
      return {
        '--book-bg': 'linear-gradient(145deg, #241403 0%, #442408 45%, #190d02 100%)',
        '--book-spine': 'linear-gradient(90deg, #120901 0%, #331a04 50%, #160c02 100%)',
        '--book-primary': '#f59e0b',
        '--book-accent': '#fbbf24',
        '--book-glow': 'rgba(245, 158, 11, 0.45)',
        '--book-border': 'rgba(245, 158, 11, 0.35)',
        '--book-text': '#fef3c7'
      }
    case 'txt':
      // Emerald / Jade Manuscript
      return {
        '--book-bg': 'linear-gradient(145deg, #051a14 0%, #0a3327 45%, #03140f 100%)',
        '--book-spine': 'linear-gradient(90deg, #020d0a 0%, #07261d 50%, #03120e 100%)',
        '--book-primary': '#10b981',
        '--book-accent': '#34d399',
        '--book-glow': 'rgba(16, 185, 129, 0.45)',
        '--book-border': 'rgba(16, 185, 129, 0.35)',
        '--book-text': '#d1fae5'
      }
    default:
      // Purple / Violet Archive default
      return {
        '--book-bg': 'linear-gradient(145deg, #170b28 0%, #2c144d 45%, #10061e 100%)',
        '--book-spine': 'linear-gradient(90deg, #0b0414 0%, #200d38 50%, #0d0519 100%)',
        '--book-primary': '#c084fc',
        '--book-accent': '#e9d5ff',
        '--book-glow': 'rgba(192, 132, 252, 0.45)',
        '--book-border': 'rgba(192, 132, 252, 0.35)',
        '--book-text': '#faf5ff'
      }
  }
})

function formatFileSize(bytes) {
  if (!bytes || bytes <= 0) return '0 KB'
  const kb = bytes / 1024
  if (kb < 1024) return `${Math.round(kb)} KB`
  return `${(kb / 1024).toFixed(1)} MB`
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
  } catch {
    return dateStr
  }
}

function onHover() {
  emit('hover', props.book)
}

function handleClick() {
  try {
    battleAudio.playBookOpen()
  } catch {}
  emit('open', props.book)
}
</script>

<style scoped>
.vault-book-wrapper {
  position: relative;
  width: 100%;
  max-width: 250px;
  margin: 0 auto;
  perspective: 1000px;
  cursor: pointer;
  outline: none;
  transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.vault-book-wrapper:hover,
.vault-book-wrapper:focus-visible {
  transform: translateY(-8px) scale(1.02);
}

.vault-book-wrapper:active {
  transform: translateY(-2px) scale(0.99);
}

/* ── Shelf Shadow ────────────────────────────── */
.book-shelf-shadow {
  position: absolute;
  bottom: -10px;
  left: 8%;
  right: 8%;
  height: 16px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0) 75%);
  filter: blur(4px);
  pointer-events: none;
  z-index: 1;
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.vault-book-wrapper:hover .book-shelf-shadow {
  opacity: 0.6;
  transform: scale(1.1);
}

/* ── The 3D Grimoire Book Object ─────────────── */
.vault-book {
  position: relative;
  width: 100%;
  height: 350px;
  display: flex;
  border-radius: 8px 12px 12px 8px;
  box-shadow:
    0 15px 35px rgba(0, 0, 0, 0.7),
    0 5px 15px rgba(0, 0, 0, 0.5),
    inset 0 0 0 1px rgba(255, 255, 255, 0.05);
  transition: box-shadow 0.3s ease;
  z-index: 2;
}

.vault-book-wrapper:hover .vault-book {
  box-shadow:
    0 20px 45px rgba(0, 0, 0, 0.8),
    0 0 30px var(--book-glow),
    inset 0 0 0 1px var(--book-border);
}

/* ── Thick Grimoire Spine Edge (Left 3D effect) ─ */
.vault-spine-edge {
  position: relative;
  width: 28px;
  height: 100%;
  background: var(--book-spine);
  border-radius: 8px 0 0 8px;
  border-right: 2px solid rgba(0, 0, 0, 0.8);
  box-shadow:
    inset -3px 0 6px rgba(0, 0, 0, 0.8),
    inset 2px 0 4px rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 18px 0;
  flex-shrink: 0;
  overflow: hidden;
}

.spine-rib {
  width: 100%;
  height: 4px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.15) 0%, rgba(0, 0, 0, 0.6) 100%);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.spine-book-number {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  font-family: 'Cinzel', 'Trajan Pro', Georgia, serif;
  font-size: 0.65rem;
  letter-spacing: 0.15em;
  color: var(--book-accent);
  opacity: 0.85;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.9);
}

/* ── Book Cover ──────────────────────────────── */
.vault-cover {
  position: relative;
  flex: 1;
  height: 100%;
  background: var(--book-bg);
  border-radius: 0 10px 10px 0;
  padding: 12px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: inset 3px 0 6px rgba(0, 0, 0, 0.6);
}

/* Filigree Corner Brackets */
.corner-bracket {
  position: absolute;
  font-size: 0.85rem;
  color: var(--book-accent);
  opacity: 0.6;
  text-shadow: 0 0 6px var(--book-glow);
  pointer-events: none;
  z-index: 3;
}
.corner-tl { top: 6px; left: 6px; }
.corner-tr { top: 6px; right: 6px; }
.corner-bl { bottom: 6px; left: 6px; }
.corner-br { bottom: 6px; right: 6px; }

/* Embossed outer border */
.cover-emboss-border {
  position: relative;
  flex: 1;
  border: 1px solid var(--book-border);
  border-radius: 4px 6px 6px 4px;
  padding: 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background: radial-gradient(circle at 50% 25%, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
  box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.7);
}

/* Top Header Row */
.book-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 6px;
  border-bottom: 1px dashed rgba(255, 255, 255, 0.1);
}

.book-vault-tag {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--book-accent);
  text-shadow: 0 0 8px var(--book-glow);
}

.book-format-badge {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 2px 6px;
  border-radius: 3px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid var(--book-primary);
  color: var(--book-primary);
  text-transform: uppercase;
}

/* Sigil Seal */
.book-sigil-seal {
  position: relative;
  align-self: center;
  margin: 10px 0 6px;
  width: 58px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.sigil-ring {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: 1px dashed var(--book-accent);
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.45);
  box-shadow: 0 0 12px var(--book-glow), inset 0 0 8px rgba(0, 0, 0, 0.8);
  transition: transform 0.4s ease;
}

.vault-book-wrapper:hover .sigil-ring {
  transform: rotate(30deg) scale(1.06);
}

.sigil-glyph {
  font-size: 1.45rem;
  filter: drop-shadow(0 0 6px var(--book-glow));
}

.sigil-glow-pulse {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: radial-gradient(circle, var(--book-glow) 0%, transparent 70%);
  opacity: 0.4;
  animation: sigilPulse 2.4s infinite ease-in-out;
}

@keyframes sigilPulse {
  0%, 100% { transform: scale(0.9); opacity: 0.3; }
  50% { transform: scale(1.15); opacity: 0.65; }
}

/* Title Block */
.book-title-block {
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 4px 2px;
}

.book-reviewer-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.88rem;
  font-weight: 800;
  line-height: 1.3;
  color: var(--book-text, #ffffff) !important;
  margin: 0;
  text-shadow: 0 2px 5px rgba(0, 0, 0, 0.98), 0 0 10px rgba(0, 0, 0, 0.85) !important;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.book-desc-sub {
  font-size: 0.68rem;
  line-height: 1.25;
  color: #fde68a !important;
  margin: 4px 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.95) !important;
  font-weight: 600;
}

/* Footer Block */
.book-footer-block {
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  padding-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.book-meta-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.62rem;
  color: rgba(255, 255, 255, 0.5);
  font-family: monospace;
}

.book-read-prompt {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: var(--book-accent);
  opacity: 0.85;
  margin-top: 2px;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.vault-book-wrapper:hover .book-read-prompt {
  opacity: 1;
  transform: scale(1.04);
  text-shadow: 0 0 6px var(--book-glow);
}

.read-rune {
  font-size: 0.55rem;
}

/* ── Ribbon ─────────────────────────────────── */
.bookmark-ribbon {
  position: absolute;
  bottom: -14px;
  left: 36px;
  width: 14px;
  height: 22px;
  background: var(--book-primary);
  clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 75%, 0 100%);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.6);
  z-index: 4;
}

/* ── Owner Quick Actions Overlay ─────────────── */
.owner-actions-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  gap: 4px;
  opacity: 0;
  transform: translateY(-4px);
  transition: opacity 0.2s ease, transform 0.2s ease;
  z-index: 10;
}

.vault-book-wrapper:hover .owner-actions-overlay {
  opacity: 1;
  transform: translateY(0);
}

.owner-btn {
  width: 26px;
  height: 26px;
  border-radius: 4px;
  background: rgba(10, 8, 20, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
}

.owner-btn:hover {
  transform: scale(1.15);
  border-color: var(--book-accent);
}

.owner-btn.btn-delete:hover {
  background: rgba(220, 38, 38, 0.9);
  border-color: #f87171;
}

/* ── Right Page Stack (Paper edges) ──────────── */
.vault-pages-stack {
  position: relative;
  width: 12px;
  height: 96%;
  align-self: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  margin-right: -4px;
}

.parchment-layer {
  width: 100%;
  height: 32%;
  background: linear-gradient(90deg, #d4b886 0%, #ecdcb9 50%, #bc9f6d 100%);
  border-radius: 0 2px 2px 0;
  border-right: 1px solid rgba(0, 0, 0, 0.4);
  box-shadow: 1px 0 2px rgba(0, 0, 0, 0.3);
}

.layer-2 {
  opacity: 0.85;
  transform: scaleX(0.92);
}

.layer-3 {
  opacity: 0.7;
  transform: scaleX(0.85);
}
</style>
