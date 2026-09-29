import { defineStore } from 'pinia'
import { ref } from 'vue'

let toastId = 0

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(message, type = 'info', duration = 4000) {
    const id = ++toastId
    toasts.value.push({ id, message, type })
    setTimeout(() => remove(id), duration)
    return id
  }

  function remove(id) {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  const success = (msg, dur) => add(msg, 'success', dur)
  const error   = (msg, dur) => add(msg, 'error', dur)
  const warning = (msg, dur) => add(msg, 'warning', dur)
  const info    = (msg, dur) => add(msg, 'info', dur)

  return { toasts, add, remove, success, error, warning, info }
})
