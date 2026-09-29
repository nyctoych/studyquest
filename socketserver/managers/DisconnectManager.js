const { log } = require('../utils/logger')

const GRACE_PERIOD_MS = parseInt(process.env.DISCONNECT_GRACE_SECONDS || '30') * 1000

class DisconnectManager {
  constructor(io, roomManager) {
    this.io          = io
    this.roomManager = roomManager
    this.graceTimers = new Map()  // userId → timer
  }

  /**
   * Called when a player's socket disconnects.
   */
  onDisconnect(socket) {
    const { roomId, userId } = socket.data
    if (!roomId || !userId) return

    const room = this.roomManager.getRoom(roomId)
    if (!room || room.status === 'completed') return

    log(`[DISCONNECT] User ${userId} disconnected from ${roomId}. Grace: ${GRACE_PERIOD_MS / 1000}s`)

    // Notify opponent
    this.io.to(roomId).emit('opponent_disconnected', {
      userId,
      grace_seconds: GRACE_PERIOD_MS / 1000,
      grace_expired: false,
    })

    // Start grace period
    const timer = setTimeout(async () => {
      this.graceTimers.delete(userId)
      const currentRoom = this.roomManager.getRoom(roomId)
      if (!currentRoom || currentRoom.status === 'completed') return

      log(`[DISCONNECT] Grace expired for ${userId} in ${roomId}. Forfeiting.`)

      const battleMgr = this.roomManager.getBattleManager(roomId)
      if (battleMgr) {
        await battleMgr.forfeit(userId)
      }
    }, GRACE_PERIOD_MS)

    this.graceTimers.set(userId, { timer, roomId })
  }

  /**
   * Called when a player reconnects within the grace period.
   */
  onReconnect(socket, userId, roomId) {
    const graceEntry = this.graceTimers.get(userId)
    if (graceEntry) {
      clearTimeout(graceEntry.timer)
      this.graceTimers.delete(userId)
      log(`[DISCONNECT] User ${userId} reconnected to ${roomId} within grace period`)
    }

    // Restore socket in room
    this.roomManager.updatePlayerSocket(roomId, userId, socket)

    // Send current battle state to reconnecting player
    const room = this.roomManager.getRoom(roomId)
    if (!room) {
      socket.emit('error', { message: 'Battle room no longer exists' })
      return
    }

    const hpState = {}
    for (const p of Object.values(room.players)) {
      hpState[p.userId] = p.hp
    }

    // Notify room
    this.io.to(roomId).emit('opponent_reconnected', { userId })

    // Send state to reconnecting client
    const currentQ = room.questions[room.currentIndex]
    socket.emit('battle_state_restored', {
      roomId,
      status:       room.status,
      currentIndex: room.currentIndex,
      total:        room.questions.length,
      hp:           hpState,
      question: currentQ ? {
        id:            currentQ.id,
        question:      currentQ.question,
        choice_a:      currentQ.choice_a,
        choice_b:      currentQ.choice_b,
        choice_c:      currentQ.choice_c,
        choice_d:      currentQ.choice_d,
        difficulty:    currentQ.difficulty,
        category_name: currentQ.category_name,
      } : null,
    })
  }
}

module.exports = DisconnectManager
