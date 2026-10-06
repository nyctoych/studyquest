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
  const finalBoss = ref({ status: 'locked' })

  // Battle state
  const activeBattleId = ref(null)
  const playerHP = ref(150)
  const playerMaxHP = ref(150)
  const playerLevel = ref(1)
  const characterDamage = ref(10)
  const characterCritDamage = ref(35)
  const enemyHP = ref(100)
  const enemyMaxHP = ref(100)
  const currentQuestionIndex = ref(0)
  const battleComplete = ref(false)
  const battleResult = ref(null)  // 'victory' | 'defeat'
  const lastDamageDealt = ref(0)
  const lastPlayerDamageTaken = ref(0)
  const lastDamageType = ref('normal')
  const battleDamageTarget = ref(null)

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
        if (res.data.final_boss) finalBoss.value = res.data.final_boss
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

  async function startBattleSession(params = {}) {
    try {
      const res = await api.post('/adventure/battle/start', {
        battle_mode: 'adventure',
        ...params
      })
      if (res.data.success) {
        activeBattleId.value = res.data.battle_id
        if (res.data.player_current_health !== undefined) {
          playerHP.value = Number(res.data.player_current_health)
        }
        if (res.data.player_max_health !== undefined) {
          playerMaxHP.value = Number(res.data.player_max_health)
        }
        if (res.data.player_level !== undefined) {
          playerLevel.value = Number(res.data.player_level)
        } else if (res.data.level !== undefined) {
          playerLevel.value = Number(res.data.level)
        }
        if (res.data.attack_power !== undefined) {
          characterDamage.value = Number(res.data.attack_power)
        } else if (res.data.character_damage !== undefined) {
          characterDamage.value = Number(res.data.character_damage)
        }
        if (res.data.critical_attack_power !== undefined) {
          characterCritDamage.value = Number(res.data.critical_attack_power)
        } else if (res.data.character_crit_damage !== undefined) {
          characterCritDamage.value = Number(res.data.character_crit_damage)
        }
        if (res.data.enemy_current_health !== undefined) {
          enemyHP.value = Number(res.data.enemy_current_health)
        }
        if (res.data.enemy_max_health !== undefined) {
          enemyMaxHP.value = Number(res.data.enemy_max_health)
        }
        currentQuestionIndex.value = 0
        battleComplete.value = false
        battleResult.value = null
        lastDamageDealt.value = 0
        lastPlayerDamageTaken.value = 0
        battleDamageTarget.value = null

        // Sync authoritative battle health to global healthStore
        try {
          const { useHealthStore } = await import('./health')
          useHealthStore().updateFromBattleAction(res.data)
        } catch (_) {}
      }
      return res.data
    } catch (e) {
      console.warn('[adventureStore] startBattleSession fallback:', e)
      const errData = e.response?.data || {}
      try {
        const { useHealthStore } = await import('./health')
        useHealthStore().updateFromBattleAction(errData)
      } catch (_) {}
      return {
        success: false,
        allowed: errData.allowed !== undefined ? errData.allowed : (e.response?.status !== 403),
        reason: errData.reason || null,
        current_hp: errData.current_hp !== undefined ? errData.current_hp : 0,
        required_hp: errData.required_hp !== undefined ? errData.required_hp : 1,
        max_hp: errData.max_hp || playerMaxHP.value || 150,
        health_regeneration: errData.health_regeneration || 50,
        seconds_until_next_regen: errData.seconds_until_next_regen || 0,
        last_regen_at: errData.last_regen_at || null,
        locked: Boolean(errData.locked),
        message: errData.message || 'Failed to start battle session'
      }
    }
  }

  async function submitBattleAction(params = {}) {
    try {
      let bId = params.battle_id || activeBattleId.value
      if (!bId) {
        const startRes = await startBattleSession({
          encounter_type: params.is_boss ? 'boss' : 'part',
          part_id: params.part_id,
          chapter_id: params.chapter_id,
          boss_id: params.boss_id
        })
        bId = startRes?.battle_id || activeBattleId.value
      }

      const payload = {
        battle_id: bId,
        question_id: params.question_id,
        question_index: params.question_index ?? currentQuestionIndex.value,
        selected_choice: params.selected_choice || params.key,
        critical_active: Boolean(params.critical_active || params.is_critical),
        is_critical: Boolean(params.critical_active || params.is_critical),
        expected_correct: params.expected_correct,
        expected_explanation: params.expected_explanation
      }
      const res = await api.post('/adventure/battle/action', payload)
      if (res.data.success) {
        // Authoritative server HP updates ONLY
        playerHP.value = Number(res.data.player_current_health)
        playerMaxHP.value = Number(res.data.player_max_health)
        if (res.data.attack_power !== undefined) {
          characterDamage.value = Number(res.data.attack_power)
        } else if (res.data.character_damage !== undefined) {
          characterDamage.value = Number(res.data.character_damage)
        }
        if (res.data.critical_attack_power !== undefined) {
          characterCritDamage.value = Number(res.data.critical_attack_power)
        } else if (res.data.character_crit_damage !== undefined) {
          characterCritDamage.value = Number(res.data.character_crit_damage)
        }
        enemyHP.value = Number(res.data.enemy_current_health)
        enemyMaxHP.value = Number(res.data.enemy_max_health)
        battleDamageTarget.value = res.data.damage_target
        lastDamageDealt.value = Number(res.data.damage_dealt || 0)
        lastPlayerDamageTaken.value = Number(res.data.player_damage_taken || 0)
        lastDamageType.value = res.data.damage_type || 'normal'

        if (res.data.battle_result) {
          battleComplete.value = true
          battleResult.value = res.data.battle_result
        }

        // Sync authoritative action health to global healthStore
        try {
          const { useHealthStore } = await import('./health')
          useHealthStore().updateFromBattleAction(res.data)
        } catch (_) {}
      }
      return res.data
    } catch (e) {
      console.warn('[adventureStore] submitBattleAction fallback:', e)
      return { success: false, message: e.response?.data?.message || 'Combat resolution error' }
    }
  }

  function resetBattle(customPlayerMaxHp, customEnemyMaxHp) {
    activeBattleId.value = null
    const maxHp = customEnemyMaxHp || currentPart.value?.enemy_max_hp || 100
    if (customPlayerMaxHp) {
      playerMaxHP.value = Number(customPlayerMaxHp)
    }
    enemyHP.value = maxHp
    enemyMaxHP.value = maxHp
    currentQuestionIndex.value = 0
    battleComplete.value = false
    battleResult.value = null
    lastDamageDealt.value = 0
    lastPlayerDamageTaken.value = 0
    battleDamageTarget.value = null
  }

  function advanceQuestion() {
    currentQuestionIndex.value++
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

  async function loadFinalBoss() {
    try {
      const res = await api.get('/adventure/final-boss')
      if (res.data?.state) finalBoss.value = res.data.state
      return res.data
    } catch (e) {
      const d = e.response?.data || {}
      return { success: false, locked: Boolean(d.locked), message: d.message || 'Failed to open the Black Archive' }
    }
  }

  async function completeFinalBossBattle(params = {}) {
    try {
      const res = await api.post('/adventure/final-boss/complete', { battle_id: params.battle_id || activeBattleId.value })
      if (res.data?.state) finalBoss.value = res.data.state
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to record final victory' }
    }
  }

  async function advanceFinalStory(stage) {
    try {
      const res = await api.post('/adventure/final-boss/story-stage', { stage })
      if (res.data?.state) finalBoss.value = res.data.state
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to save story progress' }
    }
  }

  async function completeFatherRevelation() {
    try {
      const res = await api.post('/adventure/final-boss/father-revelation')
      if (res.data?.state) finalBoss.value = res.data.state
      return res.data
    } catch (e) {
      return { success: false, message: e.response?.data?.message || 'Failed to record Father\'s Revelation' }
    }
  }

  return {
    finalBoss, loadFinalBoss, completeFinalBossBattle, advanceFinalStory, completeFatherRevelation,
    chapters, currentChapter, currentPart, questions, loading, error,
    currentUnlockedChapterId, allChaptersCompleted,
    recoveredMindSeals, discoveredFractureShards, blackArchiveUnlocked, storyFlags,
    activeBattleId, playerHP, playerMaxHP, enemyHP, enemyMaxHP,
    playerLevel, characterDamage, characterCritDamage,
    currentQuestionIndex, battleComplete, battleResult,
    lastDamageDealt, lastPlayerDamageTaken, lastDamageType, battleDamageTarget,
    currentQuestion, progress,
    loadChapters, loadChapter, loadPart, submitPartAnswers, completeBossBattle,
    startBattleSession, submitBattleAction,
    fetchStoryState, fetchBlackArchive, completeBlackArchivePhase,
    resetBattle, advanceQuestion,
  }
})

