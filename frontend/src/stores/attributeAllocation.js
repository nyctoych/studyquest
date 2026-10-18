import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from './auth'
import { useHealthStore } from './health'

export const useAttributeAllocationStore = defineStore('attributeAllocation', () => {
  const allocations = ref([])
  const totals = ref({
    attack_damage: 0,
    health: 0,
    critical_damage: 0,
    count: 0,
    completed_count: 0,
    total_max: 10
  })
  const pendingMilestones = ref([])
  const character = ref(null)
  const scaledStats = ref(null)
  const isOpen = ref(false)
  const activeMilestone = ref(null)
  const loading = ref(false)
  const submitting = ref(false)

  const hasPending = computed(() => pendingMilestones.value.length > 0)
  const completedCount = computed(() => totals.value?.completed_count ?? allocations.value.length)
  const nextPendingMilestone = computed(() => pendingMilestones.value.length > 0 ? pendingMilestones.value[0] : null)

  async function fetchAllocations(characterId = null) {
    loading.value = true
    try {
      const url = characterId ? `/characters/attribute-allocations?character_id=${characterId}` : '/characters/attribute-allocations'
      const res = await api.get(url)
      if (res.data?.success) {
        allocations.value = res.data.allocations || []
        totals.value = res.data.allocation_totals || totals.value
        pendingMilestones.value = res.data.pending_milestones || []
        character.value = res.data.character || null
        scaledStats.value = res.data.scaled_stats || null

        // Sync with activeMilestone if not set or if current one is no longer pending
        if (hasPending.value && (!activeMilestone.value || !pendingMilestones.value.includes(activeMilestone.value))) {
          activeMilestone.value = pendingMilestones.value[0]
        } else if (!hasPending.value) {
          activeMilestone.value = null
          isOpen.value = false
        }
        return res.data
      }
    } catch (e) {
      console.warn('[attributeAllocationStore] fetchAllocations error:', e)
    } finally {
      loading.value = false
    }
    return null
  }

  async function claimAllocation(milestoneLevel, attributeType, characterId = null) {
    submitting.value = true
    try {
      const payload = {
        milestone_level: milestoneLevel,
        attribute_type: attributeType
      }
      if (characterId) payload.character_id = characterId

      const res = await api.post('/characters/attribute-allocations/claim', payload)
      if (res.data?.success) {
        totals.value = res.data.totals || totals.value
        pendingMilestones.value = res.data.pending_milestones || []
        scaledStats.value = res.data.scaled_stats || null
        if (res.data.character) character.value = res.data.character

        // Re-fetch full allocation list
        await fetchAllocations(characterId)

        // Sync auth and health stores
        const auth = useAuthStore()
        await auth.fetchUser()

        const health = useHealthStore()
        await health.fetchHealth(true)

        // If there are still pending milestones, automatically advance to the next in ascending order
        if (pendingMilestones.value.length > 0) {
          activeMilestone.value = pendingMilestones.value[0]
        } else {
          activeMilestone.value = null
          isOpen.value = false
        }

        return { success: true, data: res.data }
      }
      return { success: false, message: res.data?.message || 'Failed to claim attribute allocation' }
    } catch (e) {
      const msg = e.response?.data?.message || 'Attribute allocation failed'
      return { success: false, message: msg }
    } finally {
      submitting.value = false
    }
  }

  function openModal(milestoneLevel = null) {
    if (milestoneLevel !== null) {
      // If a specific milestone is requested, check if it's actually pending or eligible
      if (pendingMilestones.value.includes(Number(milestoneLevel))) {
        activeMilestone.value = Number(milestoneLevel)
        isOpen.value = true
        return
      }
    }

    if (pendingMilestones.value.length > 0) {
      activeMilestone.value = pendingMilestones.value[0]
      isOpen.value = true
    } else {
      activeMilestone.value = null
      isOpen.value = false
    }
  }

  function closeModal() {
    isOpen.value = false
  }

  function checkAndOpenIfPending(routePath = '') {
    // Avoid interrupting active question answer sequences or exams
    if (
      routePath.startsWith('/special-event/exam') ||
      routePath.startsWith('/battle/') ||
      routePath.includes('/battle-action')
    ) {
      return false
    }

    if (hasPending.value && !isOpen.value) {
      openModal()
      return true
    } else if (!hasPending.value && isOpen.value) {
      isOpen.value = false
    }
    return false
  }

  return {
    allocations,
    totals,
    pendingMilestones,
    character,
    scaledStats,
    isOpen,
    activeMilestone,
    loading,
    submitting,
    hasPending,
    completedCount,
    nextPendingMilestone,
    fetchAllocations,
    claimAllocation,
    openModal,
    closeModal,
    checkAndOpenIfPending,
  }
})
