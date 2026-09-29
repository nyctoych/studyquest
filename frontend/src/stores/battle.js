import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import socketService from '../services/socketService'
import { useAuthStore } from './auth'

export const useBattleStore = defineStore('battle', () => {
  const roomId = ref('')
  const battleMode = ref('ranked')
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

  const isSelfTurnOver = computed(() => answerSubmitted.value)

  function initBattle(roomData) {
    const auth = useAuthStore()
    roomId.value = roomData.roomId
    battleMode.value = roomData.mode || 'ranked'
    totalQuestions.value = roomData.totalQuestions || 10
    status.value = 'waiting'
    battleResult.value = null
    roundFeedback.value = null

    // Determine who is self and who is opponent
    const isPlayer1 = roomData.player1.userId === auth.user?.id
    const pSelf = isPlayer1 ? roomData.player1 : roomData.player2
    const pOpp  = isPlayer1 ? roomData.player2 : roomData.player1

    self.value = {
      userId: pSelf.userId,
      username: pSelf.username,
      avatar_style: pSelf.avatar_style || 'adventurer',
      avatar_seed: pSelf.avatar_seed || 'self',
      avatar_options: pSelf.avatar_options || {},
      hp: 100,
      maxHp: 100,
      isReady: false,
      correctCount: 0,
      wrongCount: 0,
      rating: pSelf.battle_rating || 1200,
    }

    opponent.value = {
      userId: pOpp.userId,
      username: pOpp.username,
      avatar_style: pOpp.avatar_style || 'adventurer',
      avatar_seed: pOpp.avatar_seed || 'opponent',
      avatar_options: pOpp.avatar_options || {},
      hp: 100,
      maxHp: 100,
      isReady: false,
      correctCount: 0,
      wrongCount: 0,
      rating: pOpp.battle_rating || 1200,
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

  function submitAnswer(choice) {
    if (answerSubmitted.value || status.value !== 'in_progress') return

    selectedAnswer.value = choice
    answerSubmitted.value = true

    socketService.emit('battle:answer', {
      roomId: roomId.value,
      userId: self.value.userId,
      questionId: currentQuestion.value?.id,
      choice,
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
    sock.off('battle:round_result')
    sock.off('battle:game_over')
    sock.off('battle:opponent_disconnected')
    sock.off('battle:opponent_reconnected')

    sock.on('battle:player_ready', ({ userId }) => {
      if (userId === opponent.value.userId) {
        opponent.value.isReady = true
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
    isSelfTurnOver,
    initBattle,
    setReady,
    submitAnswer,
    reset,
  }
})
