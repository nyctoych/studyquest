import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSeriousModeStore = defineStore('seriousMode', () => {
  const isSeriousMode = ref(false)
  const showWelcomeModal = ref(false)
  const currentSeriousModule = ref(null) // 'studyhall' | 'vault' | 'special-event' | null
  let modalTimer = null

  // Serious Side routes: Study Hall, Vault, Test Your Knowledge (special-event)
  function isSeriousRoute(path) {
    if (!path || typeof path !== 'string') return false
    const clean = path.split('?')[0].replace(/^#/, '')
    return (
      clean.startsWith('/studyhall') ||
      clean.startsWith('/study') ||
      clean.startsWith('/quests/part') ||
      clean.startsWith('/quests/') ||
      clean.startsWith('/vault') ||
      clean.startsWith('/special-event')
    )
  }

  function getModuleName(path) {
    if (!path) return null
    const clean = path.split('?')[0].replace(/^#/, '')
    if (
      clean.startsWith('/studyhall') ||
      clean.startsWith('/study') ||
      clean.startsWith('/quests/part') ||
      clean.startsWith('/quests/')
    ) return 'studyhall'
    if (clean.startsWith('/vault')) return 'vault'
    if (clean.startsWith('/special-event')) return 'special-event'
    return null
  }

  function activateModal() {
    clearModalTimer()
    showWelcomeModal.value = true
    modalTimer = setTimeout(() => {
      dismissWelcomeModal()
    }, 2000)
  }

  function dismissWelcomeModal() {
    clearModalTimer()
    showWelcomeModal.value = false
  }

  function clearModalTimer() {
    if (modalTimer) {
      clearTimeout(modalTimer)
      modalTimer = null
    }
  }

  function handleRouteChange(toPath, fromPath) {
    const toIsSerious = isSeriousRoute(toPath)
    const fromIsSerious = isSeriousRoute(fromPath)
    const toModule = getModuleName(toPath)

    if (toIsSerious) {
      isSeriousMode.value = true
      currentSeriousModule.value = toModule

      if (typeof document !== 'undefined') {
        document.body.classList.add('serious-mode-active')
      }

      // If entering Serious Side from outside (Realm, quests, character, etc.)
      // or if fresh entry (not marked in current session context)
      const enteredContext = sessionStorage.getItem('sq_serious_session')
      if (!fromIsSerious || !enteredContext) {
        sessionStorage.setItem('sq_serious_session', '1')
        activateModal()
      }
    } else {
      // Exiting Serious Side -> return to Realm / normal mode
      isSeriousMode.value = false
      currentSeriousModule.value = null
      sessionStorage.removeItem('sq_serious_session')
      dismissWelcomeModal()

      if (typeof document !== 'undefined') {
        document.body.classList.remove('serious-mode-active')
      }
    }
  }

  function exitSeriousMode() {
    isSeriousMode.value = false
    currentSeriousModule.value = null
    sessionStorage.removeItem('sq_serious_session')
    dismissWelcomeModal()
    if (typeof document !== 'undefined') {
      document.body.classList.remove('serious-mode-active')
    }
  }

  return {
    isSeriousMode,
    showWelcomeModal,
    currentSeriousModule,
    isSeriousRoute,
    getModuleName,
    handleRouteChange,
    activateModal,
    dismissWelcomeModal,
    exitSeriousMode
  }
})
