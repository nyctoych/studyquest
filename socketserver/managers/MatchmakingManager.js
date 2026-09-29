const { log } = require('../utils/logger')
const { getUserRating } = require('../config/database')

// In-memory queue: Map<userId, queueEntry>
const queue = new Map()

const INITIAL_RATING_RANGE  = 500
const RANGE_WIDEN_INTERVAL  = 10  // seconds
const RANGE_WIDEN_AMOUNT    = 100
const MAX_RATING_RANGE      = 1000
const LEVEL_DIFF_MAX_INITIAL = 25
const LEVEL_DIFF_MAX_FINAL   = 50

class MatchmakingManager {
  constructor(io, roomManager) {
    this.io          = io
    this.roomManager = roomManager
    this._tickInterval = null
  }

  start() {
    // Run matchmaking tick every 2 seconds
    this._tickInterval = setInterval(() => this._tick(), 2000)
    log('Matchmaking engine started')
  }

  stop() {
    if (this._tickInterval) clearInterval(this._tickInterval)
  }

  /**
   * Add a player to the matchmaking queue.
   */
  async joinQueue(socket, data) {
    const { userId, username, level, categoryId, difficulty } = data

    if (!userId) {
      socket.emit('error', { message: 'Missing userId' })
      return
    }

    // Remove any existing entry for this user
    this.leaveQueue(userId)

    // Get current rating from DB
    const userInfo = await getUserRating(userId)

    const entry = {
      userId,
      username:       username || 'Unknown',
      socketId:       socket.id,
      socket,
      level:          level || userInfo.level || 1,
      rating:         data.battle_rating || userInfo.battle_rating || 1200,
      categoryId:     categoryId || null,
      difficulty:     difficulty || null,
      joinedAt:       Date.now(),
      waitSeconds:    0,
    }

    queue.set(userId, entry)

    socket.emit('queue_joined', {
      position:      queue.size,
      estimatedWait: this._estimateWait(),
      rating:        entry.rating,
    })

    log(`[QUEUE] ${username} (${userId}) joined. Queue size: ${queue.size}`)

    // Immediately attempt a match
    this._tick()
  }

  leaveQueue(userId) {
    if (queue.has(userId)) {
      queue.delete(userId)
      log(`[QUEUE] User ${userId} left queue. Size: ${queue.size}`)
    }
  }

  isInQueue(userId) {
    return queue.has(userId)
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
      if (matched.has(entries[i].userId)) continue

      for (let j = i + 1; j < entries.length; j++) {
        if (matched.has(entries[j].userId)) continue

        const a = entries[i]
        const b = entries[j]

        if (this._isCompatible(a, b)) {
          matched.add(a.userId)
          matched.add(b.userId)

          // Remove from queue
          queue.delete(a.userId)
          queue.delete(b.userId)

          // Create battle room
          await this.roomManager.createRoom(a, b)
          break
        }
      }
    }
  }

  _isCompatible(a, b) {
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
      LEVEL_DIFF_MAX_INITIAL + Math.floor(minWait / 10),
      LEVEL_DIFF_MAX_FINAL
    )

    const ratingDiff = Math.abs(a.rating - b.rating)
    const levelDiff  = Math.abs(a.level - b.level)

    if (ratingDiff > ratingRange) return false
    if (levelDiff  > levelRange)  return false

    // Topic/category compatibility — relax after 30s wait
    if (minWait < 30 && a.categoryId && b.categoryId && a.categoryId !== b.categoryId) {
      return false
    }

    return true
  }

  _estimateWait() {
    if (queue.size === 0) return 5
    if (queue.size === 1) return 3
    return 1
  }
}

module.exports = MatchmakingManager
