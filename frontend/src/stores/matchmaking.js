import { defineStore } from 'pinia'
import { ref } from 'vue'
import socketService from '../services/socketService'
import { useAuthStore } from './auth'

export const useMatchmakingStore = defineStore('matchmaking', () => {
  const inQueue = ref(false)
  const elapsedSeconds = ref(0)
  const preferredCategory = ref(null)
  const preferredDifficulty = ref(null)
  const coinBet = ref(100)
  const matchedRoom = ref(null)
  const matchStatus = ref('idle') // 'idle' | 'searching' | 'matched'
  let timerInterval = null

  function startTimer() {
    elapsedSeconds.value = 0
    clearInterval(timerInterval)
    timerInterval = setInterval(() => {
      elapsedSeconds.value++
    }, 1000)
  }

  function stopTimer() {
    clearInterval(timerInterval)
    timerInterval = null
  }

  function joinQueue(categoryId = null, difficulty = null, bet = 100) {
    const auth = useAuthStore()
    if (!auth.user) return

    const validTiers = [100, 1000, 10000, 20000]
    const chosenBet = validTiers.includes(Number(bet)) ? Number(bet) : 100
    coinBet.value = chosenBet

    socketService.connect()
    preferredCategory.value = categoryId
    preferredDifficulty.value = difficulty
    inQueue.value = true
    matchStatus.value = 'searching'
    startTimer()

    setupSocketListeners()

    socketService.emit('queue:join', {
      userId: auth.user.id,
      username: auth.user.username,
      avatar_style: auth.user.avatar_style,
      avatar_seed: auth.user.avatar_seed,
      avatar_options: auth.user.avatar_options,
      character_class: auth.characterClass,
      class_type: auth.characterClass,
      selected_character_id: auth.user.selected_character_id,
      battle_rating: auth.user.battle_rating || 1200,
      categoryId,
      difficulty,
      coinBet: chosenBet,
    })
  }

  function leaveQueue() {
    const auth = useAuthStore()
    if (auth.user) {
      socketService.emit('queue:leave', { userId: auth.user.id })
    }
    inQueue.value = false
    matchStatus.value = 'idle'
    matchedRoom.value = null
    stopTimer()
  }

  function setupSocketListeners() {
    const sock = socketService.connect()

    sock.on('match:found', (data) => {
      stopTimer()
      inQueue.value = false
      matchStatus.value = 'matched'
      matchedRoom.value = data
    })

    sock.on('match:cancelled', () => {
      leaveQueue()
    })
  }

  return {
    inQueue,
    coinBet,
    elapsedSeconds,
    preferredCategory,
    preferredDifficulty,
    matchedRoom,
    matchStatus,
    joinQueue,
    leaveQueue,
  }
})
