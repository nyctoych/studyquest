import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import socketService from '../services/socketService'
import { useAuthStore } from './auth'

export const useBattleStore = defineStore('battle', () => {
  const roomId = ref('')
  const battleMode = ref('ranked')
  const coinBet = ref(100)
  const pot = ref(200)
  const status = ref('waiting') // 'waiting' | 'ready' | 'starting' | 'in_progress' | 'ended'
  
  const self = ref({
    userId: null,
    username: '',
    avatar_style: 'adventurer',
    avatar_seed: 'default',
    avatar_options: {},
    hp: 100,
    maxHp: 100,
    isReady: false,
    correctCount: 0,
    wrongCount: 0,
    rating: 1200,
  })

  const opponent = ref({
    userId: null,
    username: 'Opponent',
    avatar_style: 'adventurer',
    avatar_seed: 'opponent',
    avatar_options: {},
    hp: 100,
    maxHp: 100,
    isReady: false,
    correctCount: 0,
    wrongCount: 0,
    rating: 1200,
  })

  const currentQuestion = ref(null)
  const questionIndex = ref(0)
  const totalQuestions = ref(10)
  const timeRemaining = ref(15)
  const maxQuestionTime = ref(15)
  const selectedAnswer = ref(null)
  const answerSubmitted = ref(false)
  const roundFeedback = ref(null) // { correct: bool, correctAnswer: 'a'|'b'|'c'|'d', explanation: '' }
  const battleResult = ref(null) // final outcome data

  const lastCombatAction = ref(null)

  const isSelfTurnOver = computed(() => answerSubmitted.value)

  function initBattle(roomData) {
    const auth = useAuthStore()
    roomId.value = roomData.roomId
    battleMode.value = roomData.mode || 'ranked'
    coinBet.value = roomData.coinBet || 100
    pot.value = roomData.pot || (coinBet.value * 2)
    totalQuestions.value = roomData.totalQuestions || 10
    status.value = 'waiting'
    battleResult.value = null
    roundFeedback.value = null
    lastCombatAction.value = null

    // Determine who is self and who is opponent
    const isPlayer1 = roomData.player1.userId === auth.user?.id
    const pSelf = isPlayer1 ? roomData.player1 : roomData.player2
    const pOpp  = isPlayer1 ? roomData.player2 : roomData.player1

    const selfMaxHp = pSelf.max_health || pSelf.hp || 150
    const oppMaxHp  = pOpp.max_health || pOpp.hp || 150

    self.value = {
      userId: pSelf.userId,
      username: pSelf.username,
      character_class: pSelf.character?.class_type || pSelf.character_class || pSelf.class_type || auth.characterClass || 'MAGE',
      class_type: pSelf.character?.class_type || pSelf.character_class || pSelf.class_type || auth.characterClass || 'MAGE',
      selected_character_id: pSelf.character?.id || pSelf.selected_character_id || auth.user?.selected_character_id || 1,
      character: pSelf.character || null,
      equipped: pSelf.equipped || {},
      avatar_style: pSelf.avatar_style || 'adventurer',
      avatar_seed: pSelf.avatar_seed || 'self',
      avatar_options: pSelf.avatar_options || {},
      hp: Number(pSelf.hp || selfMaxHp),
      maxHp: Number(selfMaxHp),
      damage: Number(pSelf.damage || 10),
      crit_damage: Number(pSelf.crit_damage || 35),
      isReady: false,
      correctCount: 0,
      wrongCount: 0,
      rating: pSelf.battle_rating || 1200,
      level: pSelf.level || 1,
    }

    opponent.value = {
      userId: pOpp.userId,
      username: pOpp.username,
      character_class: pOpp.character?.class_type || pOpp.character_class || pOpp.class_type || 'MAGE',
      class_type: pOpp.character?.class_type || pOpp.character_class || pOpp.class_type || 'MAGE',
      selected_character_id: pOpp.character?.id || pOpp.selected_character_id || 1,
      character: pOpp.character || null,
      equipped: pOpp.equipped || {},
      avatar_style: pOpp.avatar_style || 'adventurer',
      avatar_seed: pOpp.avatar_seed || 'opponent',
      avatar_options: pOpp.avatar_options || {},
      hp: Number(pOpp.hp || oppMaxHp),
      maxHp: Number(oppMaxHp),
      damage: Number(pOpp.damage || 10),
      crit_damage: Number(pOpp.crit_damage || 35),
      isReady: false,
      correctCount: 0,
      wrongCount: 0,
      rating: pOpp.battle_rating || 1200,
      level: pOpp.level || 1,
    }

    setupSocketListeners()
  }

  function setReady() {
    self.value.isReady = true
    socketService.emit('battle:ready', {
      roomId: roomId.value,
      userId: self.value.userId,
    })
  }

  function submitAnswer(choice, isCritical = false) {
    if (answerSubmitted.value || status.value !== 'in_progress') return

    selectedAnswer.value = choice
    answerSubmitted.value = true

    socketService.emit('battle:answer', {
      roomId: roomId.value,
      userId: self.value.userId,
      questionId: currentQuestion.value?.id,
      choice,
      isCritical: Boolean(isCritical),
      timeTakenMs: (maxQuestionTime.value - timeRemaining.value) * 1000,
    })
  }

  function setupSocketListeners() {
    // Ensure connected and get the underlying socket instance for listeners
    const sock = socketService.connect()

    // Clear old listeners first
    sock.off('battle:player_ready')
    sock.off('battle:countdown')
    sock.off('battle:question')
    sock.off('battle:timer_tick')
    sock.off('battle:combat_action')
    sock.off('hp_update')
    sock.off('battle:round_result')
    sock.off('battle:game_over')
    sock.off('battle:opponent_disconnected')
    sock.off('battle:opponent_reconnected')

    sock.on('battle:player_ready', ({ userId }) => {
      if (userId === opponent.value.userId) {
        opponent.value.isReady = true
      }
    })

    sock.on('battle:combat_action', (action) => {
      lastCombatAction.value = action
      if (action.hp) {
        if (action.hp[self.value.userId] !== undefined) {
          self.value.hp = action.hp[self.value.userId]
        }
        if (action.hp[opponent.value.userId] !== undefined) {
          opponent.value.hp = action.hp[opponent.value.userId]
        }
      }
    })

    sock.on('hp_update', (hpState) => {
      if (hpState) {
        if (hpState[self.value.userId] !== undefined) {
          self.value.hp = hpState[self.value.userId]
        }
        if (hpState[opponent.value.userId] !== undefined) {
          opponent.value.hp = hpState[opponent.value.userId]
        }
      }
    })

    sock.on('battle:countdown', ({ seconds }) => {
      status.value = 'starting'
      timeRemaining.value = seconds
    })

    sock.on('battle:question', (qData) => {
      status.value = 'in_progress'
      currentQuestion.value = qData.question
      questionIndex.value = qData.index
      totalQuestions.value = qData.total || totalQuestions.value
      timeRemaining.value = qData.timeLimit || 15
      maxQuestionTime.value = qData.timeLimit || 15
      selectedAnswer.value = null
      answerSubmitted.value = false
      roundFeedback.value = null
    })

    sock.on('battle:timer_tick', ({ remaining }) => {
      timeRemaining.value = remaining
    })

    sock.on('battle:round_result', (result) => {
      roundFeedback.value = {
        correctAnswer: result.correctAnswer,
        explanation: result.explanation,
        selfCorrect: result.playerResults[self.value.userId]?.correct,
        oppCorrect: result.playerResults[opponent.value.userId]?.correct,
        damageDealtToOpp: result.playerResults[self.value.userId]?.damageDealt || 0,
        damageTaken: result.playerResults[opponent.value.userId]?.damageDealt || 0,
      }

      // Update HP
      if (result.hp[self.value.userId] !== undefined) {
        self.value.hp = result.hp[self.value.userId]
      }
      if (result.hp[opponent.value.userId] !== undefined) {
        opponent.value.hp = result.hp[opponent.value.userId]
      }
    })

    sock.on('battle:game_over', (finalData) => {
      status.value = 'ended'
      battleResult.value = finalData
      if (finalData.coinBet) coinBet.value = finalData.coinBet
      if (finalData.pot) pot.value = finalData.pot
      const auth = useAuthStore()
      auth.fetchUser() // refresh coins, level, rating
    })

    sock.on('battle:opponent_disconnected', () => {
      // Show notification/status
    })

    sock.on('battle:opponent_reconnected', () => {
      // Resume
    })
  }

  function reset() {
    roomId.value = ''
    status.value = 'waiting'
    currentQuestion.value = null
    roundFeedback.value = null
    battleResult.value = null
    selectedAnswer.value = null
    answerSubmitted.value = false
  }

  return {
    roomId,
    battleMode,
    coinBet,
    pot,
    status,
    self,
    opponent,
    currentQuestion,
    questionIndex,
    totalQuestions,
    timeRemaining,
    maxQuestionTime,
    selectedAnswer,
    answerSubmitted,
    roundFeedback,
    battleResult,
    lastCombatAction,
    isSelfTurnOver,
    initBattle,
    setReady,
    submitAnswer,
    reset,
  }
})
