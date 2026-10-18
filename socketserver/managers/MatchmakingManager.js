const { log } = require('../utils/logger')
const { getUserRating } = require('../config/database')

// In-memory queue: Map<socketId, queueEntry>
const queue = new Map()

const INITIAL_RATING_RANGE  = 500
const RANGE_WIDEN_INTERVAL  = 5   // seconds (faster widening for fluid matching)
const RANGE_WIDEN_AMOUNT    = 200
const MAX_RATING_RANGE      = 1500
const LEVEL_DIFF_MAX_INITIAL = 50
const LEVEL_DIFF_MAX_FINAL   = 100

class MatchmakingManager {
  constructor(io, roomManager) {
    this.io          = io
    this.roomManager = roomManager
    this._tickInterval = null
  }

  start() {
    // Run matchmaking tick every 1 second
    this._tickInterval = setInterval(() => this._tick(), 1000)
    log('Matchmaking engine started')
  }

  stop() {
    if (this._tickInterval) clearInterval(this._tickInterval)
  }

  /**
   * Add a player to the matchmaking queue.
   */
  async joinQueue(socket, data) {
    const { userId, username, level, categoryId, difficulty, avatar_style, avatar_seed, avatar_options, coinBet } = data

    if (!userId) {
      socket.emit('error', { message: 'Missing userId' })
      return
    }

    // Remove any existing entry for this socket
    queue.delete(socket.id)

    // Allowed ranked wager tiers: 100, 1000, 10000, 20000
    const validTiers = [100, 1000, 10000, 20000]
    const parsedBet = Number(coinBet)
    const effectiveBet = validTiers.includes(parsedBet) ? parsedBet : 100

    // Check if another socket with this same userId is already in queue (e.g. testing in 2 windows with same account)
    const existingSameUser = Array.from(queue.values()).find(e => e.userId === userId && e.socketId !== socket.id)
    const effectiveUserId = existingSameUser ? `${userId}_demo` : userId
    const effectiveUsername = existingSameUser ? `${username} (Demo)` : (username || 'Unknown')

    // Get current rating from DB
    const userInfo = await getUserRating(userId)

    const entry = {
      userId:         effectiveUserId,
      originalUserId: userId,
      username:       effectiveUsername,
      socketId:       socket.id,
      socket,
      level:          level || userInfo.level || 1,
      rating:         data.battle_rating || userInfo.battle_rating || 1200,
      avatar_style:   avatar_style || userInfo.avatar_style || 'adventurer',
      avatar_seed:    avatar_seed || userInfo.avatar_seed || 'default',
      avatar_options: avatar_options || userInfo.avatar_options || {},
      character_class: data.character_class || userInfo.character_class || 'MAGE',
      class_type:     data.class_type || userInfo.character_class || 'MAGE',
      selected_character_id: data.selected_character_id || userInfo.selected_character_id || 1,
      categoryId:     categoryId || null,
      difficulty:     difficulty || null,
      coinBet:        effectiveBet,
      joinedAt:       Date.now(),
      waitSeconds:    0,
    }

    queue.set(socket.id, entry)

    socket.emit('queue_joined', {
      position:      queue.size,
      estimatedWait: this._estimateWait(),
      rating:        entry.rating,
      coinBet:       entry.coinBet,
    })

    log(`[QUEUE] ${username} (${effectiveUserId}) joined from socket ${socket.id} with wager ${entry.coinBet} coins. Queue size: ${queue.size}`)

    // Immediately attempt a match
    this._tick()
  }

  leaveQueue(userIdOrSocketId, socketId = null) {
    for (const [sId, entry] of queue.entries()) {
      if (sId === userIdOrSocketId || sId === socketId || String(entry.userId) === String(userIdOrSocketId)) {
        queue.delete(sId)
        log(`[QUEUE] Socket ${sId} / user ${entry.userId} left queue. Size: ${queue.size}`)
      }
    }
  }

  isInQueue(userIdOrSocketId, socketId = null) {
    for (const [sId, entry] of queue.entries()) {
      if (sId === userIdOrSocketId || sId === socketId || String(entry.userId) === String(userIdOrSocketId)) {
        return true
      }
    }
    return false
  }

  /**
   * Main matchmaking tick — finds compatible pairs and creates battles.
   */
  async _tick() {
    if (queue.size < 2) return

    const entries = Array.from(queue.values())
    const now     = Date.now()

    // Update wait times
    entries.forEach(e => {
      e.waitSeconds = Math.floor((now - e.joinedAt) / 1000)
    })

    const matched = new Set()

    for (let i = 0; i < entries.length; i++) {
      if (matched.has(entries[i].socketId)) continue

      for (let j = i + 1; j < entries.length; j++) {
        if (matched.has(entries[j].socketId)) continue

        const a = entries[i]
        const b = entries[j]

        if (this._isCompatible(a, b)) {
          matched.add(a.socketId)
          matched.add(b.socketId)

          // Remove both from queue
          queue.delete(a.socketId)
          queue.delete(b.socketId)

          // Create battle room
          await this.roomManager.createRoom(a, b)
          break
        }
      }
    }
  }

  _isCompatible(a, b) {
    // Both players must wager the exact same coin bet tier
    if (a.coinBet !== b.coinBet) return false

    const waitA = a.waitSeconds
    const waitB = b.waitSeconds
    const minWait = Math.min(waitA, waitB)

    // Gradually widen rating range as wait time increases
    const widenSteps  = Math.floor(minWait / RANGE_WIDEN_INTERVAL)
    const ratingRange = Math.min(
      INITIAL_RATING_RANGE + widenSteps * RANGE_WIDEN_AMOUNT,
      MAX_RATING_RANGE
    )
    const levelRange = Math.min(
      LEVEL_DIFF_MAX_INITIAL + Math.floor(minWait / 5),
      LEVEL_DIFF_MAX_FINAL
    )

    const ratingDiff = Math.abs(a.rating - b.rating)
    const levelDiff  = Math.abs(a.level - b.level)

    if (ratingDiff > ratingRange) return false
    if (levelDiff  > levelRange)  return false

    // Topic/category compatibility — relax after 5s wait for smooth demonstration
    if (minWait < 5 && a.categoryId && b.categoryId && a.categoryId !== b.categoryId) {
      return false
    }

    return true
  }

  _estimateWait() {
    if (queue.size === 0) return 3
    if (queue.size === 1) return 2
    return 1
  }
}

module.exports = MatchmakingManager
