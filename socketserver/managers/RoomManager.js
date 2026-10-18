const { log } = require('../utils/logger')
const { fetchQuestionsForBattle, getUserCombatProfile } = require('../config/database')
const { getCombatProfile } = require('../utils/phpApi')
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

    const coinBet = playerA.coinBet || 100
    const pot = coinBet * 2

    // Fetch authoritative combat stats and equipped effects for both players
    const origIdA = playerA.originalUserId || playerA.userId
    const origIdB = playerB.originalUserId || playerB.userId

    let profileA = await getCombatProfile(origIdA)
    if (!profileA) profileA = await getUserCombatProfile(origIdA)

    let profileB = await getCombatProfile(origIdB)
    if (!profileB) profileB = await getUserCombatProfile(origIdB)

    const statsA = profileA?.stats || { max_health: 150, damage: 10, crit_damage: 35 }
    const statsB = profileB?.stats || { max_health: 150, damage: 10, crit_damage: 35 }
    const equippedA = profileA?.equipped || {}
    const equippedB = profileB?.equipped || {}
    const charA = profileA?.character || null
    const charB = profileB?.character || null

    const room = {
      roomId,
      mode:             'ranked',
      coinBet,
      pot,
      status:           'waiting',  // waiting → starting → in_progress → completed
      players: {
        [playerA.userId]: {
          userId:   playerA.userId,
          username: playerA.username,
          socketId: playerA.socketId,
          socket:   playerA.socket,
          hp:       statsA.max_health || 150,
          max_health: statsA.max_health || 150,
          damage:   statsA.damage || 10,
          crit_damage: statsA.crit_damage || 35,
          stats:    statsA,
          character: charA,
          equipped: equippedA,
          score:    0,
          correct:  0,
          wrong:    0,
          ready:    false,
          slot:     'player1',
          rating:   playerA.rating,
          level:    statsA.level || playerA.level,
          character_class: charA?.class_type || playerA.character_class || 'MAGE',
          class_type: charA?.class_type || playerA.character_class || 'MAGE',
          selected_character_id: charA?.id || playerA.selected_character_id || 1,
          avatar_style: playerA.avatar_style || 'adventurer',
          avatar_seed: playerA.avatar_seed || 'self',
          avatar_options: playerA.avatar_options || {},
        },
        [playerB.userId]: {
          userId:   playerB.userId,
          username: playerB.username,
          socketId: playerB.socketId,
          socket:   playerB.socket,
          hp:       statsB.max_health || 150,
          max_health: statsB.max_health || 150,
          damage:   statsB.damage || 10,
          crit_damage: statsB.crit_damage || 35,
          stats:    statsB,
          character: charB,
          equipped: equippedB,
          score:    0,
          correct:  0,
          wrong:    0,
          ready:    false,
          slot:     'player2',
          rating:   playerB.rating,
          level:    statsB.level || playerB.level,
          character_class: charB?.class_type || playerB.character_class || 'MAGE',
          class_type: charB?.class_type || playerB.character_class || 'MAGE',
          selected_character_id: charB?.id || playerB.selected_character_id || 1,
          avatar_style: playerB.avatar_style || 'adventurer',
          avatar_seed: playerB.avatar_seed || 'opponent',
          avatar_options: playerB.avatar_options || {},
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
      level: statsA.level || playerA.level,
      hp: statsA.max_health || 150,
      max_health: statsA.max_health || 150,
      damage: statsA.damage || 10,
      crit_damage: statsA.crit_damage || 35,
      character: charA,
      equipped: equippedA,
      character_class: charA?.class_type || playerA.character_class || 'MAGE',
      class_type: charA?.class_type || playerA.character_class || 'MAGE',
      selected_character_id: charA?.id || playerA.selected_character_id || 1,
      avatar_style: playerA.avatar_style || 'adventurer',
      avatar_seed: playerA.avatar_seed || 'self',
      avatar_options: playerA.avatar_options || {},
    }
    const p2Payload = {
      userId: playerB.userId,
      username: playerB.username,
      battle_rating: playerB.rating,
      level: statsB.level || playerB.level,
      hp: statsB.max_health || 150,
      max_health: statsB.max_health || 150,
      damage: statsB.damage || 10,
      crit_damage: statsB.crit_damage || 35,
      character: charB,
      equipped: equippedB,
      character_class: charB?.class_type || playerB.character_class || 'MAGE',
      class_type: charB?.class_type || playerB.character_class || 'MAGE',
      selected_character_id: charB?.id || playerB.selected_character_id || 1,
      avatar_style: playerB.avatar_style || 'adventurer',
      avatar_seed: playerB.avatar_seed || 'opponent',
      avatar_options: playerB.avatar_options || {},
    }

    const matchPayloadA = {
      roomId,
      mode: 'ranked',
      coinBet,
      pot,
      totalQuestions: questions.length,
      questionsCount: questions.length,
      self: p1Payload,
      player1: p1Payload,
      player2: p2Payload,
      opponent: safeOpponentA,
    }

    const matchPayloadB = {
      roomId,
      mode: 'ranked',
      coinBet,
      pot,
      totalQuestions: questions.length,
      questionsCount: questions.length,
      self: p2Payload,
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
      hp:       player.hp,
      max_health: player.max_health || 150,
      damage:   player.damage || 10,
      crit_damage: player.crit_damage || 35,
      character: player.character,
      equipped: player.equipped,
      character_class: player.character_class || 'MAGE',
      class_type: player.character_class || 'MAGE',
      selected_character_id: player.selected_character_id || 1,
      avatar_style: player.avatar_style || 'adventurer',
      avatar_seed: player.avatar_seed || 'opponent',
      avatar_options: player.avatar_options || {},
    }
  }
}

module.exports = RoomManager
