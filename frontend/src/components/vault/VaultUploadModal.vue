<template>
  <div class="vault-modal-overlay" @click.self="handleClose">
    <div
      class="vault-upload-modal glass-card animate-scale-up"
      role="dialog"
      aria-modal="true"
      aria-labelledby="upload-modal-title"
    >
      <!-- Corner runes -->
      <span class="modal-corner corner-tl">❖</span>
      <span class="modal-corner corner-tr">❖</span>
      <span class="modal-corner corner-bl">❖</span>
      <span class="modal-corner corner-br">❖</span>

      <!-- Close Button -->
      <button
        type="button"
        class="modal-close-btn"
        @click="handleClose"
        :disabled="isUploading"
        aria-label="Close modal"
      >
        ✕
      </button>

      <!-- Header -->
      <div class="modal-header">
        <div class="header-herald-tag">
          <span class="herald-rune">📜</span>
          <span>PERSONAL ARCHIVE INSCRIPTION</span>
        </div>
        <h2 id="upload-modal-title" class="modal-title">SEAL REVIEWER INTO VAULT</h2>
        <p class="modal-subtitle">
          Inscribe your personal study manuscript into the archives of Mindrealm.
        </p>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitUpload" class="modal-form-body">
        <!-- Reviewer Title (Optional: clean title derived from filename if blank) -->
        <div class="form-group">
          <label for="reviewer-title" class="form-label">
            <span>Reviewer Title</span>
            <span class="label-hint">(Optional — auto-generated from filename if empty)</span>
          </label>
          <input
            id="reviewer-title"
            v-model="title"
            type="text"
            maxlength="200"
            placeholder="e.g. Psychopathology Reviewer"
            class="vault-input"
            :disabled="isUploading"
          />
        </div>

        <!-- Description (Optional) -->
        <div class="form-group">
          <label for="reviewer-desc" class="form-label">
            <span>Description</span>
            <span class="label-hint">(Optional notes or topics)</span>
          </label>
          <textarea
            id="reviewer-desc"
            v-model="description"
            rows="2"
            maxlength="500"
            placeholder="e.g. Midterm summary covering DSM-5 clinical criteria and syndromes"
            class="vault-textarea"
            :disabled="isUploading"
          ></textarea>
        </div>

        <!-- Drag & Drop Manuscript Upload Area -->
        <div class="form-group">
          <label class="form-label">
            <span>Manuscript Document</span>
            <span class="label-required">*</span>
          </label>

          <div
            class="vault-dropzone"
            :class="{
              'is-dragover': isDragOver,
              'has-file': Boolean(selectedFile),
              'is-disabled': isUploading
            }"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
            @click="triggerFileInput"
          >
            <input
              ref="fileInputRef"
              type="file"
              class="hidden-file-input"
              accept=".pdf,.docx,.doc,.pptx,.ppt,.txt"
              @change="onFileSelected"
              :disabled="isUploading"
            />

            <!-- Selected File Preview -->
            <div v-if="selectedFile" class="selected-file-display" @click.stop>
              <div class="file-icon-box">
                <span class="file-icon">{{ getFileIcon(selectedFile.name) }}</span>
              </div>
              <div class="file-info-text">
                <strong class="file-name" :title="selectedFile.name">{{ selectedFile.name }}</strong>
                <span class="file-size-badge">{{ formatBytes(selectedFile.size) }}</span>
              </div>
              <button
                type="button"
                class="remove-file-btn"
                @click.stop="clearFile"
                :disabled="isUploading"
                title="Choose different file"
              >
                ✕
              </button>
            </div>

            <!-- Empty Dropzone State -->
            <div v-else class="dropzone-empty-state">
              <div class="dropzone-icon-circle">
                <span class="dropzone-icon">📥</span>
              </div>
              <p class="dropzone-primary-text">
                <strong>Click to select</strong> or drag and drop manuscript
              </p>
              <p class="dropzone-secondary-text">
                Supported formats: <strong>PPT, PPTX, DOC, DOCX, TXT, PDF</strong>
              </p>
              <p class="dropzone-limit-text">
                Maximum manuscript size: <strong>{{ maxFileLabel }}</strong>
              </p>
            </div>
          </div>
        </div>

        <!-- Supported Formats Badges -->
        <div class="formats-pill-strip">
          <span class="format-pill pdf">PDF</span>
          <span class="format-pill docx">DOC / DOCX</span>
          <span class="format-pill pptx">PPT / PPTX</span>
          <span class="format-pill txt">TXT</span>
        </div>

        <!-- Upload Progress & Status -->
        <div v-if="isUploading" class="upload-progress-container animate-fade-in">
          <div class="progress-bar-track">
            <div
              class="progress-bar-fill"
              :style="{ width: `${uploadProgress}%` }"
            ></div>
          </div>
          <div class="progress-meta-row">
            <span class="progress-status-text">
              <span class="spinner-inline">❖</span>
              {{ uploadProgress < 100 ? `Sealing manuscript into Vault... ${uploadProgress}%` : 'Verifying grimoire seal...' }}
            </span>
          </div>
        </div>

        <!-- Error Message Banner -->
        <div v-if="errorMessage" class="error-banner animate-shake">
          <span class="err-icon">⚠️</span>
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Action Buttons -->
        <div class="modal-actions-row">
          <button
            type="button"
            class="btn btn-gothic-stone"
            @click="handleClose"
            :disabled="isUploading"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="btn btn-gothic-crimson"
            :disabled="!selectedFile || isUploading"
          >
            <span v-if="isUploading">Sealing Manuscript…</span>
            <span v-else>+ Seal Into Vault</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import vaultService from '../../services/vaultService'
import Swal from 'sweetalert2'

const props = defineProps({
  maxFileLabel: {
    type: String,
    default: '25 MB'
  }
})

const emit = defineEmits(['close', 'uploaded'])

const title = ref('')
const description = ref('')
const selectedFile = ref(null)
const isDragOver = ref(false)
const isUploading = ref(false)
const uploadProgress = ref(0)
const errorMessage = ref('')
const fileInputRef = ref(null)

const ALLOWED_EXTS = ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'txt']
const MAX_BYTES = 25 * 1024 * 1024

function triggerFileInput() {
  if (isUploading.value) return
  if (fileInputRef.value) {
    fileInputRef.value.click()
  }
}

function onDragOver() {
  if (!isUploading.value) isDragOver.value = true
}

function onDragLeave() {
  isDragOver.value = false
}

function onDrop(e) {
  isDragOver.value = false
  if (isUploading.value) return
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    validateAndSetFile(files[0])
  }
}

function onFileSelected(e) {
  const files = e.target?.files
  if (files && files.length > 0) {
    validateAndSetFile(files[0])
  }
}

function validateAndSetFile(file) {
  errorMessage.value = ''
  const ext = file.name.split('.').pop()?.toLowerCase() || ''

  if (!ALLOWED_EXTS.includes(ext)) {
    errorMessage.value = `Unsupported file format (.${ext}). Only PPT, PPTX, DOC, DOCX, TXT, and PDF are allowed.`
    return
  }

  if (file.size > MAX_BYTES) {
    errorMessage.value = `This reviewer exceeds the allowed file size (${props.maxFileLabel}).`
    return
  }

  selectedFile.value = file

  // Auto-fill title with clean name if title is currently empty
  if (!title.value.trim()) {
    const baseName = file.name.replace(/\.[^/.]+$/, '')
    const cleaned = baseName.replace(/[_\-\.]+/g, ' ').trim()
    title.value = cleaned.replace(/\b\w/g, c => c.toUpperCase())
  }
}

function clearFile() {
  selectedFile.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function getFileIcon(filename) {
  const ext = filename.split('.').pop()?.toLowerCase() || ''
  switch (ext) {
    case 'pdf':  return '📜'
    case 'docx':
    case 'doc':  return '🪶'
    case 'pptx':
    case 'ppt':  return '🔮'
    case 'txt':  return '✒️'
    default:     return '📖'
  }
}

function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const kb = bytes / 1024
  if (kb < 1024) return `${Math.round(kb)} KB`
  return `${(kb / 1024).toFixed(1)} MB`
}

function handleClose() {
  if (isUploading.value) return
  emit('close')
}

async function submitUpload() {
  if (!selectedFile.value || isUploading.value) return

  errorMessage.value = ''
  isUploading.value = true
  uploadProgress.value = 0

  const formData = new FormData()
  formData.append('file', selectedFile.value)
  if (title.value.trim()) {
    formData.append('title', title.value.trim())
  }
  if (description.value.trim()) {
    formData.append('description', description.value.trim())
  }

  try {
    const res = await vaultService.uploadReviewer(formData, (progressEvent) => {
      if (progressEvent.total) {
        uploadProgress.value = Math.min(95, Math.round((progressEvent.loaded * 100) / progressEvent.total))
      }
    })

    uploadProgress.value = 100

    if (res.success) {
      Swal.fire({
        title: 'Reviewer Added to Vault!',
        html: `<strong>${res.book.title}</strong> has been inscribed as <strong>Book ${String(res.book.book_number).padStart(2, '0')}</strong>.`,
        icon: 'success',
        background: '#120d24',
        color: '#f3e8ff',
        confirmButtonColor: '#7c3aed',
        customClass: {
          popup: 'swal2-gothic-popup'
        }
      })

      emit('uploaded', res.book)
      emit('close')
    } else {
      errorMessage.value = res.message || 'Failed to seal manuscript.'
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Server error occurred during manuscript upload.'
  } finally {
    isUploading.value = false
  }
}
</script>

<style scoped>
.vault-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 2, 7, 0.85);
  backdrop-filter: blur(8px);
  z-index: 120;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.vault-upload-modal {
  position: relative;
  width: 100%;
  max-width: 580px;
  background: linear-gradient(145deg, rgba(20, 12, 36, 0.96) 0%, rgba(10, 6, 20, 0.98) 100%);
  border: 1px solid rgba(139, 92, 246, 0.35);
  box-shadow:
    0 25px 60px rgba(0, 0, 0, 0.9),
    0 0 35px rgba(139, 92, 246, 0.25),
    inset 0 0 20px rgba(139, 92, 246, 0.08);
  border-radius: 12px;
  padding: 2rem;
  color: #ede9fe;
}

/* Corner filigree */
.modal-corner {
  position: absolute;
  font-size: 0.9rem;
  color: #c4b5fd;
  opacity: 0.7;
  pointer-events: none;
}
.corner-tl { top: 8px; left: 8px; }
.corner-tr { top: 8px; right: 8px; }
.corner-bl { bottom: 8px; left: 8px; }
.corner-br { bottom: 8px; right: 8px; }

/* Close Button */
.modal-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  color: #c4b5fd;
  font-size: 0.95rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
.modal-close-btn:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.25);
  border-color: #f87171;
  color: #fee2e2;
}

/* Header */
.modal-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.header-herald-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  color: #fbbf24;
  padding: 3px 10px;
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 20px;
  margin-bottom: 8px;
}

.modal-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #faf5ff;
  margin: 0 0 6px;
  text-shadow: 0 0 12px rgba(192, 132, 252, 0.5);
}

.modal-subtitle {
  font-size: 0.8rem;
  color: #c4b5fd;
  margin: 0;
  opacity: 0.85;
}

/* Form */
.modal-form-body {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  color: #e9d5ff;
}

.label-hint {
  font-size: 0.7rem;
  font-weight: 400;
  color: #a78bfa;
  opacity: 0.75;
}

.label-required {
  color: #f43f5e;
}

.vault-input,
.vault-textarea {
  width: 100%;
  background: rgba(10, 6, 20, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 9px 12px;
  color: #faf5ff;
  font-size: 0.86rem;
  outline: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.vault-input:focus,
.vault-textarea:focus {
  border-color: #a855f7;
  box-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
}

.vault-textarea {
  resize: vertical;
  min-height: 56px;
}

/* Dropzone */
.vault-dropzone {
  position: relative;
  border: 2px dashed rgba(139, 92, 246, 0.35);
  border-radius: 8px;
  background: rgba(15, 9, 28, 0.6);
  padding: 1.5rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.25s ease;
}

.vault-dropzone:hover:not(.is-disabled) {
  border-color: #a855f7;
  background: rgba(22, 13, 42, 0.75);
}

.vault-dropzone.is-dragover {
  border-color: #c084fc;
  background: rgba(30, 16, 58, 0.9);
  box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
}

.hidden-file-input {
  display: none;
}

.dropzone-icon-circle {
  width: 44px;
  height: 44px;
  margin: 0 auto 8px;
  border-radius: 50%;
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(139, 92, 246, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
}

.dropzone-primary-text {
  font-size: 0.84rem;
  color: #ede9fe;
  margin: 0 0 4px;
}

.dropzone-secondary-text {
  font-size: 0.72rem;
  color: #c4b5fd;
  margin: 0 0 4px;
}

.dropzone-limit-text {
  font-size: 0.68rem;
  color: #a78bfa;
  opacity: 0.8;
  margin: 0;
}

/* Selected File Display */
.selected-file-display {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(20, 12, 38, 0.95);
  border: 1px solid rgba(168, 85, 247, 0.5);
  border-radius: 6px;
  padding: 10px 14px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.file-icon-box {
  font-size: 1.4rem;
}

.file-info-text {
  flex: 1;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
}

.file-name {
  font-size: 0.84rem;
  color: #faf5ff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-size-badge {
  font-size: 0.68rem;
  color: #c4b5fd;
  font-family: monospace;
}

.remove-file-btn {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  width: 26px;
  height: 26px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  transition: all 0.2s ease;
}
.remove-file-btn:hover {
  background: rgba(239, 68, 68, 0.5);
  color: #fff;
}

/* Formats Strip */
.formats-pill-strip {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.format-pill {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 3px 8px;
  border-radius: 4px;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #c4b5fd;
}
.format-pill.pdf { border-color: rgba(244, 63, 94, 0.5); color: #fb7185; }
.format-pill.docx { border-color: rgba(56, 189, 248, 0.5); color: #7dd3fc; }
.format-pill.pptx { border-color: rgba(245, 158, 11, 0.5); color: #fbbf24; }
.format-pill.txt { border-color: rgba(16, 185, 129, 0.5); color: #34d399; }

/* Progress */
.upload-progress-container {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.progress-bar-track {
  width: 100%;
  height: 8px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid rgba(139, 92, 246, 0.3);
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #7c3aed 0%, #c084fc 100%);
  transition: width 0.2s ease;
}

.progress-meta-row {
  display: flex;
  justify-content: center;
  font-size: 0.72rem;
  color: #c4b5fd;
}

.spinner-inline {
  display: inline-block;
  animation: spinRune 1.6s infinite linear;
}

@keyframes spinRune {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Error Banner */
.error-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  font-size: 0.78rem;
  padding: 8px 12px;
  border-radius: 6px;
}

/* Actions */
.modal-actions-row {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 6px;
}

.btn-gothic-stone {
  background: rgba(30, 20, 50, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #c4b5fd;
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-gothic-stone:hover:not(:disabled) {
  background: rgba(45, 30, 75, 0.9);
  color: #faf5ff;
}

.btn-gothic-crimson {
  background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);
  border: 1px solid #a855f7;
  color: #faf5ff;
  padding: 8px 20px;
  border-radius: 6px;
  font-weight: 700;
  letter-spacing: 0.04em;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.35);
  transition: all 0.2s ease;
}
.btn-gothic-crimson:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(124, 58, 237, 0.55);
}
.btn-gothic-crimson:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>
