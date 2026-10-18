<template>
  <div
    class="reviewer-modal-overlay"
    :class="{ 'is-fullscreen-overlay': isFullscreenReading }"
    @click.self="handleAttemptClose"
    @keydown.esc="handleAttemptClose"
  >
    <div
      class="reviewer-modal-container"
      :class="{
        'is-fullscreen-reading': isFullscreenReading,
        'is-txt-mode': fileType === 'txt'
      }"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="`reviewer-title-${book.id}`"
    >
      <!-- Corner Filigree Accents (Hidden in Fullscreen Reading) -->
      <template v-if="!isFullscreenReading">
        <span class="corner-filigree cf-tl">❖</span>
        <span class="corner-filigree cf-tr">❖</span>
        <span class="corner-filigree cf-bl">❖</span>
        <span class="corner-filigree cf-br">❖</span>
      </template>

      <!-- ── TOP BAR: Header, Format Badge, Mode Controls & Close ───── -->
      <header class="reviewer-header" :class="{ 'fullscreen-header': isFullscreenReading }">
        <div class="header-left-meta">
          <span class="header-book-pill">
            <span class="pill-rune">📖</span>
            <span>BOOK {{ formattedBookNumber }}</span>
          </span>
          <span class="header-format-pill" :class="`pill-${fileType}`">
            {{ fileType.toUpperCase() }}
          </span>
          <span v-if="hasUnsavedChanges" class="header-unsaved-pill" title="You have unsaved changes">
            ● UNSAVED EDITS
          </span>
        </div>

        <div class="header-title-block">
          <h2 :id="`reviewer-title-${book.id}`" class="header-main-title" :title="book.title">
            {{ book.title }}
          </h2>
          <span v-if="book.description && !isFullscreenReading" class="header-desc-inline" :title="book.description">
            {{ book.description }}
          </span>
        </div>

        <!-- Toolbar Actions: Fullscreen, Edit, Highlight, Save, Close -->
        <div class="header-right-actions">
          <!-- 1. FULLSCREEN READING TOGGLE -->
          <button
            type="button"
            class="btn-reviewer-action btn-fullscreen-toggle"
            :class="{ active: isFullscreenReading }"
            @click="toggleFullscreenReading"
            :title="isFullscreenReading ? 'Exit Fullscreen Reading (Normal View)' : 'Read on Plain White Fullscreen Surface'"
          >
            <span class="action-icon">{{ isFullscreenReading ? '🗗' : '⛶' }}</span>
            <span class="action-label">{{ isFullscreenReading ? 'NORMAL VIEW' : 'FULLSCREEN READING' }}</span>
          </button>

          <!-- 2. OWNER ACTIONS: Highlight (TXT only), Edit, Save -->
          <template v-if="isOwner">
            <!-- Highlight Tool with Color Palette (TXT format only) -->
            <div v-if="fileType === 'txt'" class="highlight-tool-wrapper">
              <button
                type="button"
                class="btn-reviewer-action btn-highlight-trigger"
                :class="{ active: showHighlightPalette }"
                @click="showHighlightPalette = !showHighlightPalette"
                title="Select text and highlight"
              >
                <span class="action-icon">🖍️</span>
                <span class="action-label">HIGHLIGHT</span>
              </button>

              <!-- Color Palette Dropdown -->
              <div v-if="showHighlightPalette" class="highlight-palette-popup">
                <span class="palette-title">Select Color:</span>
                <div class="palette-swatches">
                  <button
                    v-for="color in HIGHLIGHT_COLORS"
                    :key="color.name"
                    type="button"
                    class="swatch-btn"
                    :class="{ selected: activeHighlightColor === color.value }"
                    :style="{ backgroundColor: color.value }"
                    :title="`Highlight ${color.name}`"
                    @click="applyHighlightColor(color.value)"
                  ></button>
                </div>
                <button
                  type="button"
                  class="btn-apply-highlight"
                  @click="highlightCurrentSelection"
                >
                  Highlight Selected Text
                </button>
              </div>
            </div>

            <!-- Save Changes Button (when highlights or content dirty) -->
            <button
              v-if="hasUnsavedChanges"
              type="button"
              class="btn-reviewer-action btn-save-action"
              :class="{ 'is-dirty': hasUnsavedChanges }"
              :disabled="saving"
              @click="saveChanges"
              title="Save changes and persistent highlights into Vault"
            >
              <span class="action-icon">{{ saving ? '⏳' : '💾' }}</span>
              <span class="action-label">{{ saving ? 'SAVING…' : 'SAVE CHANGES' }}</span>
            </button>
          </template>

          <!-- Close Modal Button -->
          <button
            type="button"
            class="header-close-btn"
            @click="handleAttemptClose"
            aria-label="Close reviewer window"
            title="Close Reviewer"
          >
            ✕
          </button>
        </div>
      </header>

      <!-- ── MAIN STAGE: Document Surface ───────────────────────────── -->
      <main
        class="reviewer-preview-stage"
        :class="{
          'is-fullscreen-stage': isFullscreenReading,
          'is-white-reading-stage': isFullscreenReading || fileType === 'txt'
        }"
      >
        <!-- Loading State -->
        <div v-if="loading" class="preview-loading-state">
          <div class="ancient-rune-spinner">❖</div>
          <p class="loading-label">CONSULTING MANUSCRIPT ARCHIVE…</p>
          <span class="loading-sub">Decrypting text and layout from the Vault</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="preview-error-state">
          <span class="error-rune">⚠️</span>
          <h3>Manuscript Preview Error</h3>
          <p>{{ error }}</p>
          <a
            :href="downloadUrl"
            class="btn btn-gothic-stone btn-sm"
            target="_blank"
            rel="noopener noreferrer"
          >
            ⬇️ Download Original Manuscript
          </a>
        </div>

        <!-- 1. PDF PREVIEW -->
        <div
          v-else-if="fileType === 'pdf'"
          class="preview-pdf-wrapper"
          :class="{ 'fullscreen-pdf': isFullscreenReading }"
        >
          <iframe
            :src="pdfPreviewUrl"
            class="pdf-viewer-iframe"
            title="PDF Document Viewer"
            loading="lazy"
          ></iframe>
        </div>

        <!-- 2. TXT PREVIEW (ALWAYS Plain White Document Background!) -->
        <div
          v-else-if="fileType === 'txt'"
          class="preview-txt-white-stage"
          :class="{ 'fullscreen-txt': isFullscreenReading }"
        >
          <div class="txt-white-document-sheet">
            <!-- Sheet Header / Breadcrumb -->
            <div class="sheet-doc-header">
              <span class="sheet-doc-tag">PLAIN TEXT DOCUMENT</span>
              <span class="sheet-doc-filename">{{ book.original_filename }}</span>
              <span class="sheet-doc-stats">{{ currentTxtLines.length }} lines · UTF-8</span>
            </div>

            <!-- EDITING MODE: Plain White Editable Text Surface -->
            <div v-if="isEditing" class="txt-editor-container">
              <div class="editor-toolbar-hint">
                <span>Type directly to modify text. Select any text passage and click <strong>HIGHLIGHT</strong> above to apply persistent annotations.</span>
              </div>
              <textarea
                v-model="editableText"
                class="txt-plain-textarea"
                rows="24"
                placeholder="Enter or modify reviewer text..."
                @input="markDirty"
                aria-label="Editable Reviewer Text"
              ></textarea>
            </div>

            <!-- READING MODE: Plain White Document Reading Surface with Highlights -->
            <div
              v-else
              ref="txtReadingContainer"
              class="txt-reading-surface"
              @mouseup="checkTextSelection"
            >
              <div
                v-for="(line, idx) in currentTxtLines"
                :key="idx"
                class="txt-paper-line"
              >
                <span class="line-number-cell">{{ idx + 1 }}</span>
                <span
                  class="line-text-cell"
                  v-html="renderHighlightedLine(line)"
                ></span>
              </div>

              <div v-if="currentTxtLines.length === 0" class="empty-doc-hint">
                (This text document is currently blank. Click "Edit Reviewer" to add notes.)
              </div>
            </div>

            <!-- Active Highlights List & Remove Options (if any) -->
            <div v-if="highlights.length > 0" class="highlights-summary-box">
              <div class="hl-summary-header">
                <span class="hl-summary-title">PERSISTENT HIGHLIGHTS ({{ highlights.length }})</span>
                <span class="hl-summary-sub">Saved into your Vault reviewer copy</span>
              </div>
              <div class="hl-badges-list">
                <div
                  v-for="hl in highlights"
                  :key="hl.id"
                  class="hl-chip"
                  :style="{ backgroundColor: hl.color || '#fef08a' }"
                >
                  <span class="hl-chip-text" :title="hl.text">"{{ truncate(hl.text, 40) }}"</span>
                  <button
                    v-if="isOwner"
                    type="button"
                    class="hl-chip-remove"
                    @click="removeHighlight(hl.id)"
                    title="Remove this highlight"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. DOC / DOCX PREVIEW -->
        <div
          v-else-if="fileType === 'docx' || fileType === 'doc'"
          class="preview-docx-wrapper"
          :class="{
            'fullscreen-white-docx': isFullscreenReading,
            'white-paper-docx': isFullscreenReading
          }"
        >
          <!-- When structured paragraphs are available -->
          <div
            v-if="docxParagraphs.length > 0"
            :class="isFullscreenReading ? 'docx-white-paper-sheet' : 'docx-parchment-reader'"
          >
            <div class="docx-header-banner">
              <span class="docx-banner-icon">🪶</span>
              <span class="docx-banner-title">DOCUMENT TRANSCRIPTION</span>
              <span class="docx-banner-count">{{ docxParagraphs.length }} paragraphs</span>
            </div>

            <!-- Editable review transcription copy if in edit mode -->
            <div v-if="isEditing" class="docx-edit-copy-container">
              <div class="editor-toolbar-hint">
                <span>Editing Review Copy: Changes are saved to your personal reviewer transcript in the Vault without corrupting the original file.</span>
              </div>
              <textarea
                v-model="editableText"
                class="txt-plain-textarea"
                rows="24"
                placeholder="Edit review transcription text..."
                @input="markDirty"
              ></textarea>
            </div>

            <!-- Reading mode paragraphs -->
            <div v-else class="docx-paragraphs-container">
              <div
                v-for="(p, pIdx) in docxParagraphs"
                :key="pIdx"
                class="docx-paragraph"
                :class="{ 'is-heading': p.is_heading }"
              >
                <h4
                  v-if="p.is_heading"
                  class="docx-heading-text"
                >{{ p.text }}</h4>
                <p
                  v-else
                  class="docx-body-text"
                >{{ p.text }}</p>
              </div>
            </div>
          </div>

          <!-- Fallback for legacy binary .doc or unextracted -->
          <div v-else class="preview-fallback-card">
            <span class="fallback-icon">📜</span>
            <h3 class="fallback-title">{{ book.original_filename }}</h3>
            <p class="fallback-desc">
              {{ docxMessage || 'This Word manuscript is securely stored in your personal Vault.' }}
            </p>
            <div class="fallback-meta-pill">
              <span>Format: {{ fileType.toUpperCase() }}</span>
              <span>•</span>
              <span>Size: {{ formatFileSize(book.file_size) }}</span>
            </div>
            <a
              :href="downloadUrl"
              class="btn btn-gothic-crimson"
              target="_blank"
              rel="noopener noreferrer"
            >
              ⬇️ Download Full Word Document
            </a>
          </div>
        </div>

        <!-- 4. PPT / PPTX PREVIEW -->
        <div
          v-else-if="fileType === 'pptx' || fileType === 'ppt'"
          class="preview-pptx-wrapper"
          :class="{ 'fullscreen-pptx': isFullscreenReading }"
        >
          <div v-if="pptxSlides.length > 0" class="pptx-slide-stage">
            <!-- Slide Deck Controller -->
            <div class="slide-deck-controls" :class="{ 'white-deck-controls': isFullscreenReading }">
              <button
                type="button"
                class="slide-nav-btn prev-btn"
                @click="currentSlideIndex = Math.max(0, currentSlideIndex - 1)"
                :disabled="currentSlideIndex <= 0"
                aria-label="Previous Slide"
              >
                ◀ Previous Slide
              </button>

              <span class="slide-counter-pill">
                SLIDE {{ currentSlideIndex + 1 }} OF {{ pptxSlides.length }}
              </span>

              <button
                type="button"
                class="slide-nav-btn next-btn"
                @click="currentSlideIndex = Math.min(pptxSlides.length - 1, currentSlideIndex + 1)"
                :disabled="currentSlideIndex >= pptxSlides.length - 1"
                aria-label="Next Slide"
              >
                Next Slide ▶
              </button>
            </div>

            <!-- Active Slide Card -->
            <div
              class="active-slide-card"
              :class="{ 'white-slide-card': isFullscreenReading }"
            >
              <div class="slide-corner-tag">
                SLIDE #{{ currentSlide.slide_number || (currentSlideIndex + 1) }}
              </div>

              <!-- Slide Title -->
              <h3 class="slide-title-text">
                {{ currentSlide.title || `Slide ${currentSlideIndex + 1}` }}
              </h3>

              <!-- Slide Bullets -->
              <div v-if="currentSlide.bullets && currentSlide.bullets.length > 0" class="slide-bullets-list">
                <div
                  v-for="(b, bIdx) in currentSlide.bullets"
                  :key="bIdx"
                  class="slide-bullet-item"
                >
                  <span class="bullet-rune">✦</span>
                  <span class="bullet-text">{{ b }}</span>
                </div>
              </div>

              <!-- Fallback to raw content lines if no bullets -->
              <div v-else-if="currentSlide.content && currentSlide.content.length > 1" class="slide-bullets-list">
                <div
                  v-for="(c, cIdx) in currentSlide.content.slice(1)"
                  :key="cIdx"
                  class="slide-bullet-item"
                >
                  <span class="bullet-rune">•</span>
                  <span class="bullet-text">{{ c }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Fallback -->
          <div v-else class="preview-fallback-card">
            <span class="fallback-icon">🔮</span>
            <h3 class="fallback-title">{{ book.original_filename }}</h3>
            <p class="fallback-desc">
              {{ pptxMessage || 'Presentation slides are archived securely. Download to view the original presentation.' }}
            </p>
            <a
              :href="downloadUrl"
              class="btn btn-gothic-crimson"
              target="_blank"
              rel="noopener noreferrer"
            >
              ⬇️ Download Presentation File
            </a>
          </div>
        </div>

        <!-- 5. General Fallback -->
        <div v-else class="preview-fallback-card">
          <span class="fallback-icon">📖</span>
          <h3 class="fallback-title">{{ book.original_filename }}</h3>
          <p class="fallback-desc">
            Direct in-browser reading is not supported for this file type. Download the manuscript to inspect with an external viewer.
          </p>
          <a
            :href="downloadUrl"
            class="btn btn-gothic-crimson"
            target="_blank"
            rel="noopener noreferrer"
          >
            ⬇️ Download Manuscript
          </a>
        </div>
      </main>

      <!-- ── BOTTOM BAR: Metadata, Download & Close Actions ─────────── -->
      <footer class="reviewer-footer" :class="{ 'fullscreen-footer': isFullscreenReading }">
        <div class="footer-metadata-left">
          <span class="meta-item">
            <span class="meta-icon">📅</span>
            <span>Uploaded: {{ formatDate(book.created_at) }}</span>
          </span>
          <span v-if="book.updated_at && book.updated_at !== book.created_at" class="meta-item">
            <span class="meta-dot">•</span>
            <span class="meta-icon">🕒</span>
            <span>Modified: {{ formatDate(book.updated_at) }}</span>
          </span>
          <span class="meta-dot">•</span>
          <span class="meta-item">
            <span class="meta-icon">💾</span>
            <span>Size: {{ formatFileSize(book.file_size) }}</span>
          </span>
          <span class="meta-dot">•</span>
          <span class="meta-item">
            <span class="meta-icon">📁</span>
            <span class="meta-filename" :title="book.original_filename">{{ book.original_filename }}</span>
          </span>
        </div>

        <div class="footer-actions-right">
          <a
            :href="downloadUrl"
            class="btn-download-manuscript"
            target="_blank"
            rel="noopener noreferrer"
            title="Download Manuscript to Local Machine"
          >
            <span class="download-icon">⬇️</span>
            <span>Download</span>
          </a>
          <button
            type="button"
            class="btn-close-manuscript"
            @click="handleAttemptClose"
          >
            Close
          </button>
        </div>
      </footer>
    </div>

    <!-- ── UNSAVED CHANGES CONFIRMATION MODAL ───────────────────────── -->
    <div v-if="showUnsavedPrompt" class="unsaved-prompt-overlay" @click.stop>
      <div class="unsaved-prompt-card">
        <div class="unsaved-card-icon">⚠️</div>
        <h3 class="unsaved-card-title">UNSAVED CHANGES</h3>
        <p class="unsaved-card-message">
          You have unsaved changes in this reviewer manuscript. Do you wish to save your edits, discard them, or continue editing?
        </p>

        <div class="unsaved-card-actions">
          <button
            type="button"
            class="btn-unsaved btn-save"
            :disabled="saving"
            @click="handleUnsavedSave"
          >
            {{ saving ? 'Saving…' : 'Save Changes' }}
          </button>
          <button
            type="button"
            class="btn-unsaved btn-discard"
            @click="handleUnsavedDiscard"
          >
            Discard Changes
          </button>
          <button
            type="button"
            class="btn-unsaved btn-continue"
            @click="handleUnsavedContinue"
          >
            Continue Editing
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import vaultService from '../../services/vaultService'
import Swal from 'sweetalert2'

const props = defineProps({
  book: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const auth = useAuthStore()

// State
const loading = ref(true)
const saving = ref(false)
const error = ref('')

// Fullscreen reading mode
const isFullscreenReading = ref(false)

// Editing & Unsaved Changes
const isEditing = ref(false)
const hasUnsavedChanges = ref(false)
const showUnsavedPrompt = ref(false)
const pendingActionAfterUnsaved = ref(null) // 'close' | 'cancelEdit'

// Highlights State
const HIGHLIGHT_COLORS = [
  { name: 'Yellow', value: '#fef08a' },
  { name: 'Green',  value: '#bbf7d0' },
  { name: 'Blue',   value: '#bfdbfe' },
  { name: 'Pink',   value: '#fbcfe8' }
]
const activeHighlightColor = ref('#fef08a')
const showHighlightPalette = ref(false)
const highlights = ref([]) // array of { id, text, color, createdAt }
const selectedTextCache = ref('')

// Content State
const txtLines = ref([])
const rawText = ref('')
const editableText = ref('')
const originalSavedContent = ref('')
const originalSavedHighlights = ref([])

// DOCX parsed state
const docxParagraphs = ref([])
const docxMessage = ref('')

// PPTX parsed state
const pptxSlides = ref([])
const currentSlideIndex = ref(0)
const pptxMessage = ref('')

const isOwner = computed(() => {
  if (props.book?.is_owner === false) return false
  if (props.book?.is_owner === true) return true
  if (auth.user?.id && props.book?.user_id) {
    return Number(auth.user.id) === Number(props.book.user_id)
  }
  return true
})

const fileType = computed(() => {
  return (props.book.file_type || '').toLowerCase()
})

const formattedBookNumber = computed(() => {
  const num = props.book.book_number || 1
  return num < 10 ? `0${num}` : `${num}`
})

const currentSlide = computed(() => {
  return pptxSlides.value[currentSlideIndex.value] || {}
})

const currentTxtLines = computed(() => {
  if (isEditing.value) {
    return editableText.value.split('\n')
  }
  return txtLines.value
})

const pdfPreviewUrl = computed(() => {
  return vaultService.getPreviewUrl(props.book.id)
})

const downloadUrl = computed(() => {
  return vaultService.getDownloadUrl(props.book.id)
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

function truncate(str, max = 36) {
  if (!str) return ''
  return str.length > max ? str.substring(0, max) + '…' : str
}

// ── Fullscreen Reading Mode Handlers ──────────────────────────────
function toggleFullscreenReading() {
  isFullscreenReading.value = !isFullscreenReading.value
}

// ── Editing Handlers ──────────────────────────────────────────────
function startEditing() {
  if (!isOwner.value) return
  isEditing.value = true
  if (!editableText.value) {
    editableText.value = rawText.value || txtLines.value.join('\n')
  }
}

function markDirty() {
  hasUnsavedChanges.value = true
}

function attemptCancelEditing() {
  if (hasUnsavedChanges.value) {
    pendingActionAfterUnsaved.value = 'cancelEdit'
    showUnsavedPrompt.value = true
  } else {
    isEditing.value = false
    editableText.value = originalSavedContent.value
  }
}

function handleAttemptClose() {
  if (hasUnsavedChanges.value) {
    pendingActionAfterUnsaved.value = 'close'
    showUnsavedPrompt.value = true
  } else {
    emit('close')
  }
}

// ── Unsaved Prompt Dialog Handlers ────────────────────────────────
async function handleUnsavedSave() {
  const success = await saveChanges()
  if (success) {
    showUnsavedPrompt.value = false
    if (pendingActionAfterUnsaved.value === 'close') {
      emit('close')
    } else {
      isEditing.value = false
    }
    pendingActionAfterUnsaved.value = null
  }
}

function handleUnsavedDiscard() {
  // Revert all changes
  editableText.value = originalSavedContent.value
  rawText.value = originalSavedContent.value
  txtLines.value = originalSavedContent.value.split('\n')
  highlights.value = JSON.parse(JSON.stringify(originalSavedHighlights.value))
  hasUnsavedChanges.value = false
  showUnsavedPrompt.value = false

  if (pendingActionAfterUnsaved.value === 'close') {
    emit('close')
  } else {
    isEditing.value = false
  }
  pendingActionAfterUnsaved.value = null
}

function handleUnsavedContinue() {
  showUnsavedPrompt.value = false
  pendingActionAfterUnsaved.value = null
}

// ── Highlighting Handlers (Strictly for .txt format) ───────────────
function checkTextSelection() {
  if (fileType.value !== 'txt') return
  try {
    const sel = window.getSelection()
    const text = sel ? sel.toString().trim() : ''
    if (text) {
      selectedTextCache.value = text
    }
  } catch {
    // Ignore selection error
  }
}

function applyHighlightColor(color) {
  activeHighlightColor.value = color
}

function highlightCurrentSelection() {
  if (fileType.value !== 'txt') return
  checkTextSelection()
  const text = selectedTextCache.value
  if (!text) {
    Swal.fire({
      title: 'Select Text First',
      text: 'Please select a text passage inside the manuscript before applying a highlight.',
      icon: 'info',
      timer: 2000,
      showConfirmButton: false,
      background: '#120d24',
      color: '#f3e8ff'
    })
    return
  }

  // Add highlight entry
  const newHl = {
    id: 'hl_' + Date.now() + '_' + Math.random().toString(36).substring(2, 6),
    text: text,
    color: activeHighlightColor.value,
    createdAt: Date.now()
  }

  highlights.value.push(newHl)
  hasUnsavedChanges.value = true
  showHighlightPalette.value = false
  selectedTextCache.value = ''

  Swal.fire({
    title: 'Text Highlighted',
    text: `"${truncate(text, 30)}" highlighted. Click Save Changes to seal permanently.`,
    icon: 'success',
    timer: 1500,
    showConfirmButton: false,
    background: '#120d24',
    color: '#f3e8ff'
  })
}

function removeHighlight(hlId) {
  highlights.value = highlights.value.filter(h => h.id !== hlId)
  hasUnsavedChanges.value = true
}

// Safely renders line text with applied highlights wrapped in <mark> (TXT only)
function renderHighlightedLine(line) {
  if (!line) return '&nbsp;'
  let escaped = String(line)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  // Only apply highlights on .txt format
  if (fileType.value !== 'txt' || highlights.value.length === 0) return escaped

  // Apply each highlight to the line safely
  for (const hl of highlights.value) {
    if (!hl.text) continue
    const escapedHlText = String(hl.text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
    if (!escapedHlText) continue

    const color = hl.color || '#fef08a'
    // Case-insensitive replacement of matching passage
    const regex = new RegExp(`(${escapeRegex(escapedHlText)})`, 'gi')
    escaped = escaped.replace(regex, `<mark class="vault-highlight" style="background-color: ${color}; color: #111827; padding: 1px 3px; border-radius: 2px;">$1</mark>`)
  }

  return escaped
}

function escapeRegex(string) {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

// ── Save Changes to Backend ───────────────────────────────────────
async function saveChanges() {
  if (!isOwner.value) return false

  saving.value = true
  try {
    const payload = {
      content: editableText.value || rawText.value,
      highlights: highlights.value
    }

    const res = await vaultService.saveContent(props.book.id, payload)
    if (res.success) {
      hasUnsavedChanges.value = false
      originalSavedContent.value = payload.content
      originalSavedHighlights.value = JSON.parse(JSON.stringify(highlights.value))
      rawText.value = payload.content
      txtLines.value = payload.content.split('\n')
      isEditing.value = false

      if (res.book) {
        emit('updated', res.book)
      }

      Swal.fire({
        title: 'Manuscript Sealed',
        text: 'Reviewer edits and persistent highlights saved into the Vault.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false,
        background: '#120d24',
        color: '#f3e8ff'
      })
      return true
    } else {
      Swal.fire({
        title: 'Save Error',
        text: res.message || 'Could not save reviewer revisions.',
        icon: 'error',
        background: '#120d24',
        color: '#f3e8ff'
      })
      return false
    }
  } catch (err) {
    Swal.fire({
      title: 'Save Failed',
      text: err.response?.data?.message || 'Server error while saving reviewer.',
      icon: 'error',
      background: '#120d24',
      color: '#f3e8ff'
    })
    return false
  } finally {
    saving.value = false
  }
}

// ── Initial Mount Data Fetch ──────────────────────────────────────
onMounted(async () => {
  try {
    loading.value = true
    const res = await vaultService.getDocumentContent(props.book.id)
    if (res.success) {
      // Load saved persistent highlights (only for txt format)
      if (fileType.value === 'txt' && Array.isArray(res.highlights)) {
        highlights.value = res.highlights
        originalSavedHighlights.value = JSON.parse(JSON.stringify(res.highlights))
      }

      const doc = res.document || {}
      if (doc.type === 'txt') {
        const textContent = res.edited_text || doc.raw_text || (doc.lines ? doc.lines.join('\n') : '')
        rawText.value = textContent
        editableText.value = textContent
        originalSavedContent.value = textContent
        txtLines.value = textContent.split('\n')
      } else if (doc.type === 'docx' && doc.paragraphs) {
        docxParagraphs.value = doc.paragraphs
        const fullDocxText = doc.paragraphs.map(p => p.text).join('\n\n')
        rawText.value = res.edited_text || fullDocxText
        editableText.value = res.edited_text || fullDocxText
        originalSavedContent.value = editableText.value
      } else if (doc.type === 'pptx' && doc.slides) {
        pptxSlides.value = doc.slides
        currentSlideIndex.value = 0
      } else {
        docxMessage.value = doc.message || ''
        pptxMessage.value = doc.message || ''
      }
    } else {
      error.value = res.message || 'Unable to parse manuscript document.'
    }
  } catch (err) {
    if (fileType.value !== 'pdf') {
      error.value = err.response?.data?.message || 'Could not load manuscript preview from archive.'
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* ── Overlay: fixed viewport backdrop ────────────────────────────── */
.reviewer-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 1, 6, 0.88);
  backdrop-filter: blur(12px);
  z-index: 150;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.reviewer-modal-overlay.is-fullscreen-overlay {
  padding: 0 !important;
  background: #ffffff !important;
  backdrop-filter: none;
  z-index: 999999 !important;
}

/* ── Reviewer Modal Container ────────────────────────────────────── */
.reviewer-modal-container {
  position: relative;
  width: min(76vw, 1280px);
  height: min(78vh, 880px);
  background: linear-gradient(145deg, rgba(16, 10, 30, 0.98) 0%, rgba(8, 4, 18, 0.99) 100%);
  border: 1px solid rgba(139, 92, 246, 0.4);
  box-shadow:
    0 30px 80px rgba(0, 0, 0, 0.95),
    0 0 50px rgba(139, 92, 246, 0.25),
    inset 0 0 30px rgba(139, 92, 246, 0.06);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  color: #ede9fe;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ── FULLSCREEN READING MODE STYLING ─────────────────────────────── */
/* Genuine plain white background, black text, zero decorative game panels */
.reviewer-modal-container.is-fullscreen-reading {
  position: fixed !important;
  inset: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  max-width: 100vw !important;
  max-height: 100vh !important;
  border-radius: 0 !important;
  border: none !important;
  background: #ffffff !important;
  color: #111827 !important;
  box-shadow: none !important;
  z-index: 999999 !important;
}

/* Corner Filigree */
.corner-filigree {
  position: absolute;
  font-size: 1rem;
  color: #c4b5fd;
  opacity: 0.7;
  pointer-events: none;
  z-index: 5;
}
.cf-tl { top: 8px; left: 8px; }
.cf-tr { top: 8px; right: 8px; }
.cf-bl { bottom: 8px; left: 8px; }
.cf-br { bottom: 8px; right: 8px; }

/* ── TOP BAR HEADER ──────────────────────────────────────────────── */
.reviewer-header {
  height: 62px;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: rgba(10, 6, 20, 0.92);
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
  z-index: 4;
}

.reviewer-header.fullscreen-header {
  background: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
  color: #0f172a !important;
}

.header-left-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.header-book-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.45);
  border-radius: 4px;
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #f5f3ff;
}

.fullscreen-header .header-book-pill {
  background: #ede9fe !important;
  border-color: #c4b5fd !important;
  color: #5b21b6 !important;
}

.header-format-pill {
  font-family: monospace;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  padding: 3px 8px;
  border-radius: 4px;
  background: rgba(56, 189, 248, 0.2);
  color: #7dd3fc;
  border: 1px solid rgba(56, 189, 248, 0.4);
}

.fullscreen-header .header-format-pill {
  background: #e0f2fe !important;
  color: #0369a1 !important;
  border-color: #bae6fd !important;
}

.header-unsaved-pill {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  padding: 3px 8px;
  border-radius: 4px;
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.4);
  animation: pulseUnsaved 2s infinite ease-in-out;
}

@keyframes pulseUnsaved {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.55; }
}

.header-title-block {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.header-main-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  color: #f5f3ff !important;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.fullscreen-header .header-main-title {
  color: #0f172a !important;
}

.header-desc-inline {
  font-size: 0.75rem;
  color: #a78bfa;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ── ACTION BUTTONS IN TOP BAR ───────────────────────────────────── */
.header-right-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-reviewer-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 11px;
  border-radius: 6px;
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  cursor: pointer;
  transition: all 0.18s ease;
  white-space: nowrap;
  border: 1px solid transparent;
}

/* Fullscreen toggle */
.btn-fullscreen-toggle {
  background: rgba(139, 92, 246, 0.2);
  border-color: rgba(139, 92, 246, 0.45);
  color: #ddd6fe;
}
.btn-fullscreen-toggle:hover {
  background: rgba(139, 92, 246, 0.38);
  color: #ffffff;
}
.btn-fullscreen-toggle.active {
  background: #3b82f6 !important;
  border-color: #2563eb !important;
  color: #ffffff !important;
}

/* Highlight button & palette */
.highlight-tool-wrapper {
  position: relative;
}

.btn-highlight-trigger {
  background: rgba(245, 158, 11, 0.2);
  border-color: rgba(245, 158, 11, 0.45);
  color: #fde68a;
}
.btn-highlight-trigger:hover,
.btn-highlight-trigger.active {
  background: rgba(245, 158, 11, 0.35);
  color: #ffffff;
}

.highlight-palette-popup {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 220px;
  background: #18112e;
  border: 1px solid rgba(192, 132, 252, 0.4);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.8);
  border-radius: 8px;
  padding: 10px 12px;
  z-index: 100;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.palette-title {
  font-size: 0.72rem;
  font-weight: 700;
  color: #c4b5fd;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.palette-swatches {
  display: flex;
  gap: 8px;
}

.swatch-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  transition: transform 0.15s ease;
}
.swatch-btn:hover {
  transform: scale(1.15);
}
.swatch-btn.selected {
  border-color: #ffffff;
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.8);
}

.btn-apply-highlight {
  background: #7c3aed;
  border: 1px solid #9333ea;
  color: #ffffff;
  border-radius: 4px;
  padding: 5px 8px;
  font-size: 0.74rem;
  font-weight: 700;
  cursor: pointer;
}
.btn-apply-highlight:hover {
  background: #6d28d9;
}

/* Edit trigger */
.btn-edit-trigger {
  background: rgba(16, 185, 129, 0.2);
  border-color: rgba(16, 185, 129, 0.45);
  color: #a7f3d0;
}
.btn-edit-trigger:hover {
  background: rgba(16, 185, 129, 0.35);
  color: #ffffff;
}

/* Save Changes */
.btn-save-action {
  background: #059669;
  border-color: #10b981;
  color: #ffffff;
  font-weight: 800;
}
.btn-save-action:hover:not(:disabled) {
  background: #047857;
}
.btn-save-action.is-dirty {
  background: #ea580c;
  border-color: #f97316;
  animation: pulseSave 2s infinite ease-in-out;
}
@keyframes pulseSave {
  0%, 100% { box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(234, 88, 12, 0); }
}

/* Cancel Action */
.btn-cancel-action {
  background: rgba(100, 116, 139, 0.25);
  border-color: rgba(100, 116, 139, 0.45);
  color: #cbd5e1;
}
.btn-cancel-action:hover {
  background: rgba(100, 116, 139, 0.45);
  color: #ffffff;
}

.header-close-btn {
  background: transparent;
  border: none;
  color: #c4b5fd;
  font-size: 1.1rem;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}
.header-close-btn:hover {
  background: rgba(239, 68, 68, 0.25);
  color: #f87171;
}

/* ── MAIN STAGE ──────────────────────────────────────────────────── */
.reviewer-preview-stage {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: #06030b;
  display: flex;
  flex-direction: column;
}

.reviewer-preview-stage.is-white-reading-stage {
  background: #f1f5f9 !important;
}

.reviewer-preview-stage.is-fullscreen-stage {
  background: #ffffff !important;
}

/* Loading & Error States */
.preview-loading-state,
.preview-error-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 2rem;
  text-align: center;
}

.ancient-rune-spinner {
  font-size: 2.2rem;
  color: #c084fc;
  animation: spinRune 2s infinite linear;
}
@keyframes spinRune {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-label {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  color: #e9d5ff;
  margin: 0;
}
.loading-sub {
  font-size: 0.75rem;
  color: #a78bfa;
}

/* ── 1. PDF PREVIEW ──────────────────────────────────────────────── */
.preview-pdf-wrapper {
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.preview-pdf-wrapper.fullscreen-pdf {
  background: #ffffff;
}

.pdf-viewer-iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: #ffffff;
}

/* ── 2. TXT PREVIEW (ALWAYS PLAIN WHITE DOCUMENT SURFACE) ─────────── */
.preview-txt-white-stage {
  width: 100%;
  height: 100%;
  overflow-y: auto;
  padding: 2rem 1.5rem;
  background: #f1f5f9;
}

.preview-txt-white-stage.fullscreen-txt {
  padding: 2.5rem 2rem;
  background: #ffffff !important;
}

.txt-white-document-sheet {
  max-width: 880px;
  margin: 0 auto;
  background: #ffffff !important;
  color: #111827 !important;
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 2.5rem 3rem;
  min-height: 100%;
  display: flex;
  flex-direction: column;
}

.fullscreen-txt .txt-white-document-sheet {
  box-shadow: none;
  border: none;
  max-width: 960px;
  padding: 2rem 3rem 4rem;
}

.sheet-doc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e2e8f0;
  margin-bottom: 1.5rem;
  font-family: 'Inter', -apple-system, sans-serif;
}

.sheet-doc-tag {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #475569;
  text-transform: uppercase;
}

.sheet-doc-filename {
  font-family: monospace;
  font-size: 0.8rem;
  color: #0f172a;
  font-weight: 600;
  flex: 1;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sheet-doc-stats {
  font-size: 0.72rem;
  color: #64748b;
  font-family: monospace;
}

/* Editor Container */
.txt-editor-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}

.editor-toolbar-hint {
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 0.78rem;
  color: #475569;
  line-height: 1.4;
}

.txt-plain-textarea {
  width: 100%;
  flex: 1;
  min-height: 55vh;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  padding: 1.25rem;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.94rem;
  line-height: 1.65;
  color: #0f172a !important;
  background: #ffffff !important;
  resize: vertical;
  outline: none;
  box-sizing: border-box;
}
.txt-plain-textarea:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

/* Reading Surface */
.txt-reading-surface {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.92rem;
  line-height: 1.7;
  color: #0f172a !important;
  flex: 1;
}

.txt-paper-line {
  display: flex;
  align-items: flex-start;
  gap: 16px;
}

.line-number-cell {
  width: 38px;
  text-align: right;
  color: #94a3b8;
  user-select: none;
  font-size: 0.76rem;
  flex-shrink: 0;
  padding-top: 2px;
}

.line-text-cell {
  flex: 1;
  color: #0f172a !important;
  white-space: pre-wrap;
  word-break: break-word;
}

.empty-doc-hint {
  padding: 3rem;
  text-align: center;
  color: #94a3b8;
  font-style: italic;
}

/* Highlights Summary Box */
.highlights-summary-box {
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 1px dashed #cbd5e1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.hl-summary-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.hl-summary-title {
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #475569;
}

.hl-summary-sub {
  font-size: 0.72rem;
  color: #64748b;
}

.hl-badges-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.hl-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #0f172a;
  border: 1px solid rgba(0, 0, 0, 0.15);
}

.hl-chip-text {
  font-weight: 600;
}

.hl-chip-remove {
  background: transparent;
  border: none;
  color: #475569;
  cursor: pointer;
  font-size: 0.75rem;
  padding: 0 2px;
  border-radius: 2px;
}
.hl-chip-remove:hover {
  background: rgba(0, 0, 0, 0.15);
  color: #dc2626;
}

/* ── 3. DOCX PREVIEW ─────────────────────────────────────────────── */
.preview-docx-wrapper {
  width: 100%;
  height: 100%;
  overflow-y: auto;
  padding: 1.5rem;
  background: radial-gradient(circle at 50% 30%, #0d1628 0%, #050a14 100%);
}

.preview-docx-wrapper.fullscreen-white-docx {
  background: #ffffff !important;
  padding: 2.5rem 2rem;
}

.docx-parchment-reader {
  max-width: 860px;
  margin: 0 auto;
  background: rgba(12, 20, 38, 0.9);
  border: 1px solid rgba(56, 189, 248, 0.35);
  border-radius: 8px;
  padding: 1.75rem 2.25rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
}

.docx-white-paper-sheet {
  max-width: 920px;
  margin: 0 auto;
  background: #ffffff !important;
  color: #111827 !important;
  padding: 2.5rem 3.5rem;
  border-radius: 4px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.fullscreen-white-docx .docx-header-banner {
  border-bottom: 2px solid #e2e8f0;
}
.fullscreen-white-docx .docx-banner-title {
  color: #0f172a !important;
}
.fullscreen-white-docx .docx-heading-text {
  color: #0f172a !important;
  border-left-color: #2563eb !important;
  text-shadow: none !important;
}
.fullscreen-white-docx .docx-body-text {
  color: #1e293b !important;
}

.docx-header-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-bottom: 1rem;
  border-bottom: 1px dashed rgba(56, 189, 248, 0.3);
  margin-bottom: 1.5rem;
}

.docx-banner-icon { font-size: 1.3rem; }
.docx-banner-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #7dd3fc;
  flex: 1;
}
.docx-banner-count {
  font-size: 0.72rem;
  color: #94a3b8;
  font-family: monospace;
}

.docx-paragraphs-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.docx-heading-text {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.15rem;
  font-weight: 700;
  color: #38bdf8;
  margin: 1rem 0 0.25rem;
  border-left: 3px solid #38bdf8;
  padding-left: 10px;
}

.docx-body-text {
  font-size: 0.94rem;
  line-height: 1.75;
  color: #e2e8f0;
  margin: 0;
  text-align: justify;
}

/* ── 4. PPTX PREVIEW ─────────────────────────────────────────────── */
.preview-pptx-wrapper {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  background: radial-gradient(circle at 50% 30%, #221504 0%, #0a0601 100%);
}

.preview-pptx-wrapper.fullscreen-pptx {
  background: #f8fafc !important;
}

.pptx-slide-stage {
  max-width: 920px;
  width: 100%;
  margin: 0 auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.slide-deck-controls {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 14px;
  background: rgba(20, 12, 4, 0.8);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 6px;
}

.slide-deck-controls.white-deck-controls {
  background: #ffffff !important;
  border-color: #cbd5e1 !important;
}

.slide-nav-btn {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.35);
  color: #fbbf24;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 4px;
  cursor: pointer;
}
.white-deck-controls .slide-nav-btn {
  background: #f1f5f9 !important;
  border-color: #cbd5e1 !important;
  color: #0f172a !important;
}

.slide-counter-pill {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #fef3c7;
}
.white-deck-controls .slide-counter-pill {
  color: #0f172a !important;
}

.active-slide-card {
  position: relative;
  flex: 1;
  background: linear-gradient(145deg, rgba(30, 18, 6, 0.92) 0%, rgba(14, 8, 2, 0.96) 100%);
  border: 2px solid rgba(245, 158, 11, 0.45);
  border-radius: 10px;
  padding: 2.25rem 2.5rem;
  display: flex;
  flex-direction: column;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.8);
  overflow-y: auto;
}

.active-slide-card.white-slide-card {
  background: #ffffff !important;
  border-color: #cbd5e1 !important;
  color: #111827 !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
}
.white-slide-card .slide-title-text {
  color: #0f172a !important;
  border-bottom-color: #e2e8f0 !important;
  text-shadow: none !important;
}
.white-slide-card .slide-bullet-item {
  color: #1e293b !important;
}

.slide-corner-tag {
  position: absolute;
  top: 14px;
  right: 18px;
  font-size: 0.68rem;
  font-family: monospace;
  font-weight: 700;
  color: #fbbf24;
  opacity: 0.75;
}
.white-slide-card .slide-corner-tag {
  color: #64748b !important;
}

.slide-title-text {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.35rem;
  font-weight: 800;
  color: #fef3c7;
  margin: 0 0 1.5rem;
  border-bottom: 2px solid rgba(245, 158, 11, 0.4);
  padding-bottom: 10px;
}

.slide-bullets-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.slide-bullet-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.95rem;
  line-height: 1.6;
  color: #fde68a;
}
.bullet-rune { color: #f59e0b; font-size: 0.78rem; margin-top: 4px; }
.bullet-text { flex: 1; }

/* ── Fallback Card ───────────────────────────────────────────────── */
.preview-fallback-card {
  max-width: 520px;
  margin: auto;
  background: rgba(20, 12, 36, 0.9);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 2.5rem 2rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.fallback-icon { font-size: 3rem; }
.fallback-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.15rem;
  color: #faf5ff;
  margin: 0;
}
.fallback-desc {
  font-size: 0.84rem;
  line-height: 1.5;
  color: #c4b5fd;
  margin: 0;
}
.fallback-meta-pill {
  display: flex;
  gap: 8px;
  font-size: 0.72rem;
  color: #a78bfa;
  font-family: monospace;
}

/* ── BOTTOM BAR FOOTER ───────────────────────────────────────────── */
.reviewer-footer {
  height: 58px;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: rgba(10, 6, 20, 0.94);
  border-top: 1px solid rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
  z-index: 4;
}

.reviewer-footer.fullscreen-footer {
  background: #f8fafc !important;
  border-top: 1px solid #e2e8f0 !important;
  color: #475569 !important;
}

.footer-metadata-left {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.74rem;
  color: #c4b5fd;
  overflow: hidden;
}
.fullscreen-footer .footer-metadata-left {
  color: #64748b !important;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}
.meta-icon { font-size: 0.75rem; }
.meta-filename {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  font-family: monospace;
}
.meta-dot { color: rgba(139, 92, 246, 0.5); }
.fullscreen-footer .meta-dot { color: #cbd5e1 !important; }

.footer-actions-right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-download-manuscript,
.btn-close-manuscript {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  height: 36px !important;
  min-height: 36px !important;
  padding: 0 16px !important;
  border-radius: 6px !important;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
  font-size: 0.82rem !important;
  font-weight: 600 !important;
  line-height: 1 !important;
  text-decoration: none !important;
  cursor: pointer !important;
  box-sizing: border-box !important;
  transition: all 0.2s ease !important;
  margin: 0 !important;
}

.btn-download-manuscript {
  background: rgba(124, 58, 237, 0.22) !important;
  border: 1px solid rgba(139, 92, 246, 0.45) !important;
  color: #c4b5fd !important;
}
.btn-download-manuscript:hover {
  background: rgba(124, 58, 237, 0.45) !important;
  border-color: #a855f7 !important;
  color: #ffffff !important;
}

.btn-close-manuscript {
  background: rgba(30, 20, 50, 0.85) !important;
  border: 1px solid rgba(139, 92, 246, 0.35) !important;
  color: #c4b5fd !important;
}
.btn-close-manuscript:hover {
  background: rgba(45, 30, 75, 0.95) !important;
  border-color: rgba(139, 92, 246, 0.6) !important;
  color: #ffffff !important;
}

.fullscreen-footer .btn-download-manuscript {
  background: #ede9fe !important;
  border-color: #c4b5fd !important;
  color: #5b21b6 !important;
}
.fullscreen-footer .btn-download-manuscript:hover {
  background: #ddd6fe !important;
  color: #4c1d95 !important;
}

.fullscreen-footer .btn-close-manuscript {
  background: #e2e8f0 !important;
  border-color: #cbd5e1 !important;
  color: #0f172a !important;
}
.fullscreen-footer .btn-close-manuscript:hover {
  background: #cbd5e1 !important;
  color: #020617 !important;
}

.download-icon {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 0.85rem !important;
  line-height: 1 !important;
  padding: 0 !important;
  margin: 0 !important;
}

/* ── UNSAVED PROMPT MODAL ────────────────────────────────────────── */
.unsaved-prompt-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000000;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.unsaved-prompt-card {
  width: min(90vw, 420px);
  background: #18112e;
  border: 1px solid rgba(239, 68, 68, 0.5);
  border-radius: 10px;
  padding: 1.75rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
}

.unsaved-card-icon {
  font-size: 2.2rem;
}

.unsaved-card-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #fee2e2;
  margin: 0;
}

.unsaved-card-message {
  font-size: 0.86rem;
  line-height: 1.5;
  color: #e2e8f0;
  margin: 0;
}

.unsaved-card-actions {
  display: flex;
  flex-direction: column;
  width: 100%;
  gap: 8px;
  margin-top: 8px;
}

.btn-unsaved {
  padding: 10px 14px;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 1px solid transparent;
}

.btn-unsaved.btn-save {
  background: #059669;
  color: #ffffff;
}
.btn-unsaved.btn-save:hover { background: #047857; }

.btn-unsaved.btn-discard {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.5);
  color: #fca5a5;
}
.btn-unsaved.btn-discard:hover { background: rgba(239, 68, 68, 0.35); }

.btn-unsaved.btn-continue {
  background: rgba(148, 163, 184, 0.2);
  border-color: rgba(148, 163, 184, 0.4);
  color: #e2e8f0;
}
.btn-unsaved.btn-continue:hover { background: rgba(148, 163, 184, 0.35); }

/* ── RESPONSIVE BREAKPOINTS ──────────────────────────────────────── */
@media (max-width: 900px) {
  .reviewer-modal-container {
    width: 94vw;
    height: 88vh;
  }
  .header-main-title {
    font-size: 0.92rem;
  }
  .header-desc-inline {
    display: none;
  }
  .action-label {
    display: none;
  }
}

@media (max-width: 600px) {
  .reviewer-modal-overlay {
    padding: 0.5rem;
  }
  .reviewer-modal-container {
    width: 98vw;
    height: 94vh;
    border-radius: 8px;
  }
  .reviewer-header {
    padding: 0 0.75rem;
    height: 54px;
  }
  .reviewer-footer {
    padding: 0 0.75rem;
    height: 52px;
  }
  .meta-dot, .meta-filename {
    display: none;
  }
}
</style>
