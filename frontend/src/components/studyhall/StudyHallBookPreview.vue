<template>
  <div class="book-preview-backdrop" @click.self="$emit('close')">
    <div
      class="book-preview-card animate-scale-up"
      :style="{
        '--preview-primary': book.style?.primaryColor || '#8b5cf6',
        '--preview-accent': book.style?.accentColor || '#fbbf24',
        '--preview-glow': book.style?.borderGlow || 'rgba(139, 92, 246, 0.4)'
      }"
    >
      <!-- Close button -->
      <button class="preview-close-btn" @click="$emit('close')" aria-label="Close preview">✕</button>

      <!-- Grimoire Header -->
      <div class="preview-header">
        <div class="preview-sigil-badge">
          <span>{{ book.style?.sigil || '❖' }}</span>
        </div>
        <div class="preview-meta">
          <div class="preview-badge-row">
            <span class="preview-vol-tag">VOLUME {{ book.roman }} · CHAPTER {{ book.chapterNumberPadded }}</span>
            <span class="preview-source-tag">SOURCE: {{ book.sourceVersion || 'DSM-5' }}</span>
          </div>
          <h2 class="preview-title">{{ book.title }}</h2>
          <span class="preview-moniker">{{ book.style?.moniker }}</span>
        </div>
      </div>

      <div class="preview-sep"></div>

      <!-- Description from database -->
      <div class="preview-body">
        <p class="preview-description">{{ book.description }}</p>

        <!-- Progress Overview -->
        <div class="preview-stats-panel">
          <div class="stat-col">
            <span class="stat-label">DOCTRINES MASTERED</span>
            <span class="stat-val">{{ book.completedPages }} / {{ book.totalPages }} PAGES</span>
          </div>
          <div class="stat-col">
            <span class="stat-label">ARCHIVE PROGRESS</span>
            <span class="stat-val highlight-val">{{ book.percentage }}%</span>
          </div>
          <div class="stat-col" v-if="book.masteryScore > 0">
            <span class="stat-label">RETENTION SCORE</span>
            <span class="stat-val">{{ Math.round(book.masteryScore) }}%</span>
          </div>
        </div>

        <div class="preview-progress-track">
          <div class="preview-progress-fill" :style="{ width: book.percentage + '%' }"></div>
        </div>
      </div>

      <!-- Locked Warning or Action -->
      <div v-if="book.isLocked" class="preview-locked-notice">
        <span class="locked-icon">🔒</span>
        <p>More knowledge must be uncovered before this volume can be opened.</p>
      </div>

      <!-- Footer Buttons -->
      <div class="preview-actions">
        <button class="btn btn-gothic-stone" @click="$emit('close')">
          ← RETURN TO SHELVES
        </button>
        <button
          v-if="!book.isLocked"
          class="btn btn-gothic-crimson open-volume-btn"
          @click="$emit('open', book)"
        >
          📖 OPEN BOOK →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  book: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'open'])
</script>

<style scoped>
.book-preview-backdrop {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(3, 2, 7, 0.82);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  animation: fadeIn 0.2s ease-out;
}

.book-preview-card {
  position: relative;
  width: 100%;
  max-width: 580px;
  background: #0d0b18;
  border: 2px solid var(--preview-primary, #8b5cf6);
  border-radius: 8px;
  padding: 1.75rem;
  box-shadow:
    0 16px 48px rgba(0, 0, 0, 0.95),
    0 0 30px var(--preview-glow, rgba(139, 92, 246, 0.4)),
    inset 0 0 20px rgba(0, 0, 0, 0.8);
  color: #f1f5f9;
}

.preview-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 4px;
  color: #94a3b8;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.preview-close-btn:hover {
  background: rgba(239, 68, 68, 0.25);
  border-color: #ef4444;
  color: #ffffff;
}

.preview-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.preview-sigil-badge {
  width: 54px;
  height: 54px;
  border-radius: 8px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, rgba(0, 0, 0, 0.8) 100%);
  border: 2px solid var(--preview-accent, #fbbf24);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: var(--preview-accent, #fbbf24);
  box-shadow: 0 0 14px var(--preview-glow);
  flex-shrink: 0;
}

.preview-meta {
  flex: 1;
}

.preview-badge-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: var(--preview-accent, #fbbf24);
  margin-bottom: 0.25rem;
}

.preview-source-tag {
  background: rgba(255, 255, 255, 0.08);
  padding: 0.1rem 0.4rem;
  border-radius: 3px;
  color: #cbd5e1;
}

.preview-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.3;
  margin: 0 0 0.25rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
}

.preview-moniker {
  font-size: 0.75rem;
  font-style: italic;
  color: #a78bfa;
}

.preview-sep {
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, var(--preview-primary) 50%, transparent 100%);
  margin: 1.25rem 0;
  opacity: 0.7;
}

.preview-description {
  font-size: 0.85rem;
  line-height: 1.6;
  color: #cbd5e1;
  margin-bottom: 1.25rem;
}

.preview-stats-panel {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
  background: rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}

.stat-col {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.stat-label {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #94a3b8;
}

.stat-val {
  font-size: 0.95rem;
  font-weight: 800;
  color: #ffffff;
}

.highlight-val {
  color: var(--preview-accent, #fbbf24);
}

.preview-progress-track {
  width: 100%;
  height: 8px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.preview-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--preview-primary) 0%, var(--preview-accent) 100%);
  box-shadow: 0 0 10px var(--preview-glow);
}

.preview-locked-notice {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: rgba(220, 38, 38, 0.15);
  border: 1px solid rgba(220, 38, 38, 0.35);
  border-radius: 6px;
  color: #fca5a5;
  font-size: 0.8rem;
  margin-bottom: 1.25rem;
}

.preview-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.open-volume-btn {
  box-shadow: 0 0 18px var(--preview-glow);
  font-size: 0.9rem;
  padding: 0.65rem 1.5rem;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@media (max-width: 640px) {
  .book-preview-card {
    padding: 1.25rem;
  }
  .preview-actions {
    flex-direction: column-reverse;
    width: 100%;
  }
  .preview-actions button {
    width: 100%;
  }
}
</style>
