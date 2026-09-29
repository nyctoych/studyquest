require('dotenv').config()

const express    = require('express')
const http       = require('http')
const { Server } = require('socket.io')
const cors       = require('cors')
const { log }    = require('./utils/logger')

const RoomManager        = require('./managers/RoomManager')
const MatchmakingManager = require('./managers/MatchmakingManager')

// ── Server Setup ──────────────────────────────────────────────
const app    = express()
const server = http.createServer(app)

const PORT        = parseInt(process.env.PORT || '3001')
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*'

app.use(cors())
app.use(express.json())

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', uptime: process.uptime(), port: PORT })
})

// ── Socket.IO ─────────────────────────────────────────────────
const io = new Server(server, {
  cors: {
    origin:  CORS_ORIGIN,
    methods: ['GET', 'POST'],
    credentials: true,
  },
  transports: ['websocket', 'polling'],
  pingTimeout:  30000,
  pingInterval: 10000,
})

// ── Managers ──────────────────────────────────────────────────
const roomManager        = new RoomManager(io)
const matchmakingManager = new MatchmakingManager(io, roomManager)
matchmakingManager.start()

// ── Socket Event Handlers ─────────────────────────────────────
io.on('connection', (socket) => {
  log(`[CONNECT] Socket ${socket.id}`)

  // ── JOIN MATCHMAKING ──────────────────────────────────────
  const handleJoinQueue = async (data) => {
    try {
      const uid = data?.userId || socket.data?.userId
      log(`[EVENT] join_matchmaking/queue:join from socket ${socket.id} user ${uid}`)
      await matchmakingManager.joinQueue(socket, data)
    } catch (err) {
      log(`[ERROR] join_matchmaking: ${err.message}`)
      socket.emit('error', { message: 'Failed to join matchmaking' })
    }
  }
  socket.on('join_matchmaking', handleJoinQueue)
  socket.on('queue:join', handleJoinQueue)

  // ── LEAVE MATCHMAKING ─────────────────────────────────────
  const handleLeaveQueue = (data) => {
    const userId = data?.userId || socket.data?.userId
    if (userId) {
      matchmakingManager.leaveQueue(userId)
      socket.emit('queue_left', {})
      socket.emit('queue:left', {})
    }
  }
  socket.on('leave_matchmaking', handleLeaveQueue)
  socket.on('queue:leave', handleLeaveQueue)

  // ── PLAYER READY ──────────────────────────────────────────
  const handlePlayerReady = (data) => {
    const { roomId, userId } = data || {}
    if (!roomId || !userId) return

    socket.data.roomId = roomId
    socket.data.userId = userId

    roomManager.playerReady(roomId, userId)
  }
  socket.on('ready_for_battle', handlePlayerReady)
  socket.on('battle:ready', handlePlayerReady)

  // ── SUBMIT ANSWER ─────────────────────────────────────────
  const handleSubmitAnswer = (data) => {
    const { roomId, questionId, userId } = data || {}
    const answer = data?.answer || data?.choice
    if (!roomId || !questionId || !answer || !userId) {
      socket.emit('error', { message: 'Invalid answer payload' })
      return
    }

    const battleMgr = roomManager.getBattleManager(roomId)
    if (battleMgr) {
      battleMgr.submitAnswer(userId, questionId, answer)
    }
  }
  socket.on('submit_answer', handleSubmitAnswer)
  socket.on('battle:answer', handleSubmitAnswer)

  // ── RECONNECT TO BATTLE ───────────────────────────────────
  socket.on('reconnect_to_battle', (data) => {
    const { roomId, userId } = data || {}
    if (!roomId || !userId) return

    const room = roomManager.getRoom(roomId)
    if (room && room.players[userId]) {
      roomManager.disconnectMgr.onReconnect(socket, userId, roomId)
    } else {
      socket.emit('error', { message: 'Battle room not found or expired' })
    }
  })

  // ── LEAVE BATTLE ──────────────────────────────────────────
  const handleLeaveBattle = (data) => {
    const { roomId, userId } = data || socket.data || {}
    if (roomId && userId) {
      const battleMgr = roomManager.getBattleManager(roomId)
      if (battleMgr) {
        battleMgr.forfeit(userId)
      }
    }
  }
  socket.on('leave_battle', handleLeaveBattle)
  socket.on('battle:leave', handleLeaveBattle)

  // ── DISCONNECT ────────────────────────────────────────────
  socket.on('disconnect', (reason) => {
    log(`[DISCONNECT] Socket ${socket.id} — ${reason}`)

    // Remove from matchmaking if in queue
    if (socket.data?.userId) {
      matchmakingManager.leaveQueue(socket.data.userId)
    }

    // Handle battle disconnect
    roomManager.disconnectMgr.onDisconnect(socket)
  })
})

// ── Start Server ──────────────────────────────────────────────
server.listen(PORT, '0.0.0.0', () => {
  log(`StudyQuest Socket.IO Server running on port ${PORT}`)
  log(`CORS origin: ${CORS_ORIGIN}`)
  log(`PHP API URL: ${process.env.PHP_API_URL}`)
  log('Ready for LAN multiplayer connections!')
})

// Graceful shutdown
process.on('SIGINT', () => {
  log('Shutting down...')
  matchmakingManager.stop()
  server.close(() => process.exit(0))
})
