<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast" tag="div" class="toast-stack">
        <div
          v-for="toast in toastStore.toasts"
          :key="toast.id"
          class="toast"
          :class="`toast-${toast.type}`"
          @click="toastStore.remove(toast.id)"
        >
          <span class="toast-icon">{{ icons[toast.type] }}</span>
          <div class="toast-body">
            <p class="toast-message">{{ toast.message }}</p>
          </div>
          <button class="toast-close" @click.stop="toastStore.remove(toast.id)">×</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToastStore } from '../stores/toast'

const toastStore = useToastStore()

const icons = {
  success: '✅',
  error:   '❌',
  warning: '⚠️',
  info:    '💡',
}
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  pointer-events: none;
}

.toast-stack {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: rgba(17, 24, 39, 0.97);
  backdrop-filter: blur(16px);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  min-width: 300px;
  max-width: 400px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5);
  pointer-events: all;
  cursor: pointer;
}

.toast-success { border-left: 3px solid #22c55e; }
.toast-error   { border-left: 3px solid #ef4444; }
.toast-warning { border-left: 3px solid #f97316; }
.toast-info    { border-left: 3px solid #7c20f0; }

.toast-icon { font-size: 1.1rem; flex-shrink: 0; }

.toast-body { flex: 1; min-width: 0; }

.toast-message {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text);
  line-height: 1.4;
}

.toast-close {
  background: none;
  border: none;
  color: var(--color-muted);
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0;
  flex-shrink: 0;
}
.toast-close:hover { color: white; }

/* TransitionGroup animations */
.toast-enter-active { animation: toastIn 0.3s ease-out; }
.toast-leave-active { animation: toastOut 0.2s ease-in forwards; }

@keyframes toastIn {
  from { opacity: 0; transform: translateX(100%); }
  to   { opacity: 1; transform: translateX(0); }
}
@keyframes toastOut {
  from { opacity: 1; transform: translateX(0); }
  to   { opacity: 0; transform: translateX(100%); }
}
</style>
