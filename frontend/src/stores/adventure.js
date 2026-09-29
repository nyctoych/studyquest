import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAdventureStore = defineStore('adventure', () => {
  const chapters = ref([])
  const currentChapter = ref(null)
  const currentPart = ref(null)
  const questions = ref([])
  const loading = ref(false)
  const error = ref(null)
  const currentUnlockedChapterId = ref(Number(localStorage.getItem('sq_unlocked_chapter')) || 1)
  const allChaptersCompleted = ref(false)
  const recoveredMindSeals = ref([])
  const discoveredFractureShards = ref([])
  const blackArchiveUnlocked = ref(false)
  const storyFlags = ref({})

  // Battle state
  const playerHP = ref(100)
  const playerMaxHP = ref(100)
  const enemyHP = ref(100)
  const enemyMaxHP = ref(100)
  const currentQuestionIndex = ref(0)
  const battleComplete = ref(false)
  const battleResult = ref(null)  // 'victory' | 'defeat'

  const currentQuestion = computed(() =>
    questions.value[currentQuestionIndex.value] || null
  )
  const progress = computed(() =>
    questions.value.length > 0 ? (currentQuestionIndex.value / questions.value.length) * 100 : 0
  )

  async function loadChapters() {
    loading.value = true
    error.value = null
    try {
      const res = await api.get('/adventure/chapters')
      if (res.data.success) {
        chapters.value = res.data.chapters || []
        if (res.data.current_unlocked_chapter_id) {
          currentUnlockedChapterId.value = res.data.current_unlocked_chapter_id
          localStorage.setItem('sq_unlocked_chapter', String(res.data.current_unlocked_chapter_id))
        }
        allChaptersCompleted.value = Boolean(res.data.all_chapters_completed)
        recoveredMindSeals.value = res.data.recovered_mind_seals || []
        discoveredFractureShards.value = res.data.discovered_fracture_shards || []
        blackArchiveUnlocked.value = Boolean(res.data.black_archive_unlocked)
        storyFlags.value = res.data.story_flags || {}
        return res.data
      }
    } catch (e) {
      error.value = 'Failed to load chapters'
    } finally {
      loading.value = false
    }
  }

  async function loadChapter(chapterId) {
    loading.value = true
    error.value = null
    try {
      const res = await api.get(`/adventure/chapters/${chapterId}`)
      if (res.data.success) {
        currentChapter.value = res.data
        return res.data
      } else {
        error.value = res.data.message
        return res.data
      }
    } catch (e) {
      const data = e.response?.data || {}
      error.value = data.message || 'Failed to load chapter'
      return {
        success: false,
        locked: Boolean(data.locked),
        message: data.message || 'Chapter access restricted',
        required_chapter_order: data.required_chapter_order || null,
        required_chapter_name: data.required_chapter_name || null
      }
    } finally {
      loading.value = false
    }
  }

  async function loadPart(chapterId, partNum) {
    loading.value = true
    error.value = null
    try {
      const res = await api.get(`/adventure/chapters/${chapterId}/parts/${partNum}`)
      if (res.data.success) {
        currentPart.value = res.data.part
        questions.value = res.data.questions || []
        resetBattle()
        return res.data
      } else {
        error.value = res.data.message
        return res.data
      }
    } catch (e) {
      const data = e.response?.data || {}
      error.value = data.message || 'Failed to load part'
      return {
        success: false,
        locked: Boolean(data.locked),
        message: data.message || 'Failed to load part'
      }
    } finally {
      loading.value = false
    }
  }

  async function submitPartAnswers(partId, answers, timeSpent) {
    try {
      const res = await api.post(`/adventure/parts/${partId}/submit`, {
        answers,
        time_spent: timeSpent,
      })
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Submission failed' }
    }
  }

  async function completeBossBattle(chapterId, bossId, battleData) {
    try {
      const res = await api.post(`/adventure/chapters/${chapterId}/boss/complete`, {
        boss_id: bossId,
        player_hp: battleData.player_hp,
        boss_hp: battleData.boss_hp,
        answers: battleData.answers || [],
      })
      if (res.data.success) {
        // Refresh chapters in background
        loadChapters()
      }
      return res.data
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message || 'Boss victory recording failed'
      }
    }
  }

  function resetBattle() {
    const maxHp = currentPart.value?.enemy_max_hp || 100
    playerHP.value = 100
    playerMaxHP.value = 100
    enemyHP.value = maxHp
    enemyMaxHP.value = maxHp
    currentQuestionIndex.value = 0
    battleComplete.value = false
    battleResult.value = null
  }

  function advanceQuestion() {
    currentQuestionIndex.value++
  }

  function dealDamageToEnemy(amount) {
    enemyHP.value = Math.max(0, enemyHP.value - amount)
  }

  function dealDamageToPlayer(amount) {
    playerHP.value = Math.max(0, playerHP.value - amount)
  }

  async function fetchStoryState() {
    try {
      const res = await api.get('/adventure/story-state')
      if (res.data.success) {
        recoveredMindSeals.value = res.data.recovered_mind_seals || []
        discoveredFractureShards.value = res.data.discovered_fracture_shards || []
        blackArchiveUnlocked.value = Boolean(res.data.black_archive_unlocked)
        storyFlags.value = res.data.story_flags || {}
      }
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to fetch story state' }
    }
  }

  async function fetchBlackArchive() {
    try {
      const res = await api.get('/adventure/black-archive')
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to access Black Archive' }
    }
  }

  async function completeBlackArchivePhase(phaseNumber, answers, timeSpent) {
    try {
      const res = await api.post('/adventure/black-archive/complete-phase', {
        phase_number: phaseNumber,
        answers,
        time_spent: timeSpent,
      })
      if (res.data.success) {
        await loadChapters()
      }
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to record phase victory' }
    }
  }

  return {
    chapters, currentChapter, currentPart, questions, loading, error,
    currentUnlockedChapterId, allChaptersCompleted,
    recoveredMindSeals, discoveredFractureShards, blackArchiveUnlocked, storyFlags,
    playerHP, playerMaxHP, enemyHP, enemyMaxHP,
    currentQuestionIndex, battleComplete, battleResult,
    currentQuestion, progress,
    loadChapters, loadChapter, loadPart, submitPartAnswers, completeBossBattle,
    fetchStoryState, fetchBlackArchive, completeBlackArchivePhase,
    resetBattle, advanceQuestion, dealDamageToEnemy, dealDamageToPlayer,
  }
})

