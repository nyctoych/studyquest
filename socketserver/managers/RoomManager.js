const { log } = require('../utils/logger')
const { fetchQuestionsForBattle } = require('../config/database')
const BattleManager    = require('./BattleManager')
const TimerManager     = require('./TimerManager')
const DisconnectManager = require('./DisconnectManager')

// In-memory rooms: Map<roomId, roomState>
const rooms = new Map()

let _battleCounter = 10000

function generateRoomId() {
  return `BATTLE-${++_battleCounter}`
}

class RoomManager {
  constructor(io) {
    this.io              = io
    this.battleManagers  = new Map()  // roomId → BattleManager
    this.timerManagers   = new Map()  // roomId → TimerManager
    this.disconnectMgr   = new DisconnectManager(io, this)
  }

  async createRoom(playerA, playerB) {
    const roomId = generateRoomId()
    const questionsCount = parseInt(process.env.BATTLE_QUESTIONS_COUNT || '10')

    // Determine preferred category (prefer matching topics)
    const categoryId = (playerA.categoryId === playerB.categoryId) ? playerA.categoryId : null
    
    // Calculate level-appropriate difficulty for the match
    const avgLevel = Math.round(((playerA.level || 1) + (playerB.level || 1)) / 2)
    let matchDifficulty = (playerA.difficulty === playerB.difficulty && playerA.difficulty) ? playerA.difficulty : null
    if (!matchDifficulty) {
      if (avgLevel <= 5) matchDifficulty = 'beginner'
      else if (avgLevel <= 15) matchDifficulty = 'intermediate'
      else if (avgLevel <= 25) matchDifficulty = 'advanced'
      else matchDifficulty = 'board_exam'
    }

    // Fetch questions (server-side — includes correct answers)
    let questions = await fetchQuestionsForBattle(questionsCount, categoryId, matchDifficulty)
    if (questions.length < questionsCount) {
      // Fallback: fetch without difficulty filter if pool needs filling
      questions = await fetchQuestionsForBattle(questionsCount, categoryId)
    }
    if (questions.length < questionsCount) {
      // Fallback: fetch any approved psychology questions
      questions = await fetchQuestionsForBattle(questionsCount)
    }

    if (questions.length === 0) {
      log(`[ROOM] ERROR: No questions available for room ${roomId}`)
      playerA.socket.emit('error', { message: 'No questions available for battle. Contact admin.' })
      playerB.socket.emit('error', { message: 'No questions available for battle. Contact admin.' })
      return
    }

    const room = {
      roomId,
      status:           'waiting',  // waiting → starting → in_progress → completed
      players: {
        [playerA.userId]: {
          userId:   playerA.userId,
          username: playerA.username,
          socketId: playerA.socketId,
          socket:   playerA.socket,
          hp:       100,
          score:    0,
          correct:  0,
          wrong:    0,
          ready:    false,
          slot:     'player1',
          rating:   playerA.rating,
          level:    playerA.level,
        },
        [playerB.userId]: {
          userId:   playerB.userId,
          username: playerB.username,
          socketId: playerB.socketId,
          socket:   playerB.socket,
          hp:       100,
          score:    0,
          correct:  0,
          wrong:    0,
          ready:    false,
          slot:     'player2',
          rating:   playerB.rating,
          level:    playerB.level,
        },
      },
      questions,       // Full question objects (with correct_answer) — server only
      currentIndex:    0,
      startedAt:       null,
      endedAt:         null,
    }

    rooms.set(roomId, room)

    // Join players to Socket.IO room channel
    playerA.socket.join(roomId)
    playerB.socket.join(roomId)

    // Associate socket → room for disconnect tracking
    playerA.socket.data.roomId = roomId
    playerA.socket.data.userId = playerA.userId
    playerB.socket.data.roomId = roomId
    playerB.socket.data.userId = playerB.userId

    // Notify both players (only send safe data — no correct answers)
    const safeOpponentA = this._safePlayerData(room.players[playerB.userId])
    const safeOpponentB = this._safePlayerData(room.players[playerA.userId])

    const p1Payload = {
      userId: playerA.userId,
      username: playerA.username,
      battle_rating: playerA.rating,
      level: playerA.level,
    }
    const p2Payload = {
      userId: playerB.userId,
      username: playerB.username,
      battle_rating: playerB.rating,
      level: playerB.level,
    }

    const matchPayloadA = {
      roomId,
      mode: 'ranked',
      totalQuestions: questions.length,
      questionsCount: questions.length,
      player1: p1Payload,
      player2: p2Payload,
      opponent: safeOpponentA,
    }

    const matchPayloadB = {
      roomId,
      mode: 'ranked',
      totalQuestions: questions.length,
      questionsCount: questions.length,
      player1: p1Payload,
      player2: p2Payload,
      opponent: safeOpponentB,
    }

    playerA.socket.emit('match_found', matchPayloadA)
    playerA.socket.emit('match:found', matchPayloadA)
    playerB.socket.emit('match_found', matchPayloadB)
    playerB.socket.emit('match:found', matchPayloadB)

    log(`[ROOM] Created ${roomId}: ${playerA.username} vs ${playerB.username} (${questions.length} questions)`)

    // Create battle manager for this room
    const battleMgr = new BattleManager(this.io, room, this)
    this.battleManagers.set(roomId, battleMgr)

    // Create timer manager
    const timerMgr = new TimerManager(this.io, roomId, battleMgr)
    this.timerManagers.set(roomId, timerMgr)
  }

  getRoom(roomId) {
    return rooms.get(roomId) || null
  }

  getRoomByUserId(userId) {
    for (const room of rooms.values()) {
      if (room.players[userId]) return room
    }
    return null
  }

  getBattleManager(roomId) {
    return this.battleManagers.get(roomId) || null
  }

  getTimerManager(roomId) {
    return this.timerManagers.get(roomId) || null
  }

  playerReady(roomId, userId) {
    const room = rooms.get(roomId)
    if (!room || !room.players[userId]) return

    room.players[userId].ready = true
    log(`[ROOM] ${room.players[userId].username} is ready in ${roomId}`)

    // Notify both players
    this.io.to(roomId).emit('opponent_ready', { userId })
    this.io.to(roomId).emit('battle:player_ready', { userId })

    // Check if both ready
    const allReady = Object.values(room.players).every(p => p.ready)
    if (allReady) {
      this._startBattle(room)
    }
  }

  _startBattle(room) {
    room.status    = 'starting'
    room.startedAt = Date.now()

    // 3-second countdown
    let countdown = 3
    this.io.to(room.roomId).emit('battle_starting', { countdown })
    this.io.to(room.roomId).emit('battle:countdown', { seconds: countdown })

    const interval = setInterval(() => {
      countdown--
      if (countdown <= 0) {
        clearInterval(interval)
        room.status = 'in_progress'
        this.io.to(room.roomId).emit('battle_starting', { countdown: 0 })
        this.io.to(room.roomId).emit('battle:countdown', { seconds: 0 })

        // Start first question
        const battleMgr = this.battleManagers.get(room.roomId)
        const timerMgr  = this.timerManagers.get(room.roomId)
        if (battleMgr && timerMgr) {
          battleMgr.sendQuestion(0)
          timerMgr.startTimer(0)
        }
      } else {
        this.io.to(room.roomId).emit('battle_starting', { countdown })
        this.io.to(room.roomId).emit('battle:countdown', { seconds: countdown })
      }
    }, 1000)
  }

  updatePlayerSocket(roomId, userId, socket) {
    const room = rooms.get(roomId)
    if (room && room.players[userId]) {
      room.players[userId].socket   = socket
      room.players[userId].socketId = socket.id
      socket.join(roomId)
      socket.data.roomId = roomId
      socket.data.userId = userId
    }
  }

  closeRoom(roomId) {
    const timerMgr = this.timerManagers.get(roomId)
    if (timerMgr) timerMgr.stop()

    this.battleManagers.delete(roomId)
    this.timerManagers.delete(roomId)
    rooms.delete(roomId)
    log(`[ROOM] Closed ${roomId}`)
  }

  _safePlayerData(player) {
    return {
      userId:   player.userId,
      username: player.username,
      rating:   player.rating,
      level:    player.level,
      slot:     player.slot,
    }
  }
}

module.exports = RoomManager
