<template>
  <div class="vault-modal-overlay" @click.self="$emit('close')">
    <div
      class="vault-edit-modal glass-card animate-scale-up"
      role="dialog"
      aria-modal="true"
      aria-labelledby="edit-modal-title"
    >
      <button
        type="button"
        class="modal-close-btn"
        @click="$emit('close')"
        :disabled="isSubmitting"
        aria-label="Close modal"
      >
        ✕
      </button>

      <div class="modal-header">
        <span class="header-herald-tag">✏️ EDIT METADATA</span>
        <h2 id="edit-modal-title" class="modal-title">UPDATE BOOK {{ formattedBookNumber }}</h2>
        <p class="modal-subtitle">{{ book.original_filename }}</p>
      </div>

      <form @submit.prevent="handleSave" class="modal-form-body">
        <div class="form-group">
          <label for="edit-title" class="form-label">Reviewer Title <span class="required">*</span></label>
          <input
            id="edit-title"
            v-model="editTitle"
            type="text"
            required
            maxlength="200"
            class="vault-input"
            :disabled="isSubmitting"
          />
        </div>

        <div class="form-group">
          <label for="edit-desc" class="form-label">Description</label>
          <textarea
            id="edit-desc"
            v-model="editDescription"
            rows="3"
            maxlength="500"
            class="vault-textarea"
            :disabled="isSubmitting"
          ></textarea>
        </div>

        <div v-if="errorMessage" class="error-banner">
          <span>⚠️ {{ errorMessage }}</span>
        </div>

        <div class="modal-actions-row">
          <button
            type="button"
            class="btn btn-gothic-stone"
            @click="$emit('close')"
            :disabled="isSubmitting"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="btn btn-gothic-crimson"
            :disabled="isSubmitting || !editTitle.trim()"
          >
            <span v-if="isSubmitting">Saving…</span>
            <span v-else>Save Changes</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import vaultService from '../../services/vaultService'
import Swal from 'sweetalert2'

const props = defineProps({
  book: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'updated'])

const editTitle = ref(props.book.title || '')
const editDescription = ref(props.book.description || '')
const isSubmitting = ref(false)
const errorMessage = ref('')

const formattedBookNumber = computed(() => {
  const num = props.book.book_number || 1
  return num < 10 ? `0${num}` : `${num}`
})

async function handleSave() {
  if (!editTitle.value.trim() || isSubmitting.value) return

  isSubmitting.value = true
  errorMessage.value = ''

  try {
    const res = await vaultService.updateMetadata(props.book.id, {
      title: editTitle.value.trim(),
      description: editDescription.value.trim()
    })

    if (res.success) {
      Swal.fire({
        title: 'Metadata Updated!',
        text: 'Reviewer title and description have been revised.',
        icon: 'success',
        timer: 1800,
        showConfirmButton: false,
        background: '#120d24',
        color: '#f3e8ff'
      })
      emit('updated', res.book)
      emit('close')
    } else {
      errorMessage.value = res.message || 'Failed to update metadata.'
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Server error occurred.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.vault-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(3, 2, 7, 0.85);
  backdrop-filter: blur(8px);
  z-index: 160;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.vault-edit-modal {
  position: relative;
  width: 100%;
  max-width: 480px;
  background: linear-gradient(145deg, rgba(20, 12, 36, 0.96) 0%, rgba(10, 6, 20, 0.98) 100%);
  border: 1px solid rgba(139, 92, 246, 0.35);
  border-radius: 12px;
  padding: 1.75rem;
  color: #ede9fe;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9);
}

.modal-close-btn {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 30px;
  height: 30px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  color: #c4b5fd;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-header {
  text-align: center;
  margin-bottom: 1.25rem;
}

.header-herald-tag {
  display: inline-block;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #fbbf24;
  margin-bottom: 6px;
}

.modal-title {
  font-family: 'Cinzel', Georgia, serif;
  font-size: 1.2rem;
  font-weight: 800;
  color: #faf5ff;
  margin: 0 0 4px;
}

.modal-subtitle {
  font-size: 0.76rem;
  color: #a78bfa;
  font-family: monospace;
  margin: 0;
}

.modal-form-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #e9d5ff;
}

.required {
  color: #f43f5e;
}

.vault-input,
.vault-textarea {
  width: 100%;
  background: rgba(10, 6, 20, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 6px;
  padding: 8px 12px;
  color: #faf5ff;
  font-size: 0.86rem;
  outline: none;
}
.vault-input:focus,
.vault-textarea:focus {
  border-color: #a855f7;
}

.error-banner {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  font-size: 0.78rem;
  padding: 8px;
  border-radius: 6px;
}

.modal-actions-row {
  display: flex;
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
}

.btn-gothic-crimson {
  background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);
  border: 1px solid #a855f7;
  color: #faf5ff;
  padding: 8px 18px;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}
.btn-gothic-crimson:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
