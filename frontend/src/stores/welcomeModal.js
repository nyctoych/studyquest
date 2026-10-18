import { defineStore } from 'pinia'
import { ref } from 'vue'

const SESSION_KEY_PENDING = 'sq_welcome_modal_pending'
const SESSION_KEY_ACKNOWLEDGED = 'sq_welcome_modal_acknowledged'

/**
 * Manages the post-login Immersive Experience Welcome Modal.
 * Strictly session-scoped: displayed once per successful login session,
 * never during normal route navigation, module visits, or session restoration.
 */
export const useWelcomeModalStore = defineStore('welcomeModal', () => {
  const isOpen = ref(false)

  /**
   * Check whether the modal is currently pending for this session
   * and has NOT yet been acknowledged.
   */
  function isPending() {
    try {
      const pending = sessionStorage.getItem(SESSION_KEY_PENDING) === '1'
      const acknowledged = sessionStorage.getItem(SESSION_KEY_ACKNOWLEDGED) === '1'
      return pending && !acknowledged
    } catch {
      return false
    }
  }

  /**
   * Called immediately upon successful authentication in Login.vue (or Register.vue).
   * Marks that the upcoming post-login transition has a pending welcome modal.
   */
  function markPendingForNewLogin() {
    try {
      sessionStorage.setItem(SESSION_KEY_PENDING, '1')
      sessionStorage.removeItem(SESSION_KEY_ACKNOWLEDGED)
    } catch (_) {}
  }

  /**
   * Check if modal should open when entering the authenticated game experience.
   */
  function checkAndOpen() {
    if (isPending()) {
      isOpen.value = true
    }
  }

  /**
   * Called when the user clicks 'ENTER THE REALM' or 'CONTINUE WITHOUT FULLSCREEN',
   * or presses Escape to dismiss. Records acknowledgment for this session.
   */
  function acknowledgeAndClose() {
    isOpen.value = false
    try {
      sessionStorage.setItem(SESSION_KEY_ACKNOWLEDGED, '1')
      sessionStorage.removeItem(SESSION_KEY_PENDING)
    } catch (_) {}
  }

  /**
   * Clears state on logout.
   */
  function clearSession() {
    isOpen.value = false
    try {
      sessionStorage.removeItem(SESSION_KEY_PENDING)
      sessionStorage.removeItem(SESSION_KEY_ACKNOWLEDGED)
    } catch (_) {}
  }

  return {
    isOpen,
    isPending,
    markPendingForNewLogin,
    checkAndOpen,
    acknowledgeAndClose,
    clearSession
  }
})

export default useWelcomeModalStore
