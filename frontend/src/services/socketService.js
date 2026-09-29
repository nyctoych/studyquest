import { io } from 'socket.io-client'

class SocketService {
  constructor() {
    this.socket = null
  }

  connect() {
    if (this.socket?.connected) return this.socket

    // If socket exists but is disconnected, force reconnect
    if (this.socket) {
      if (this.socket.disconnected) {
        this.socket.connect()
      }
      return this.socket
    }

    // Derive socket URL dynamically based on current LAN host
    const host = typeof window !== 'undefined' ? window.location.hostname : 'localhost'
    const socketUrl = import.meta.env.VITE_SOCKET_URL || `http://${host}:3001`

    this.socket = io(socketUrl, {
      transports: ['websocket', 'polling'],
      autoConnect: true,
      reconnection: true,
      reconnectionAttempts: 15,
      reconnectionDelay: 1000,
      reconnectionDelayMax: 5000,
      timeout: 10000,
    })

    this.socket.on('connect', () => {
      console.log('[SocketService] Connected to multiplayer server:', this.socket.id)
    })

    this.socket.on('disconnect', (reason) => {
      console.log('[SocketService] Disconnected from server:', reason)
    })

    let errCount = 0
    this.socket.on('connect_error', () => {
      errCount++
      if (errCount === 1) {
        console.warn('[SocketService] Multiplayer server offline or unreachable. Battles require the Node.js server.')
      }
    })

    return this.socket
  }

  disconnect() {
    if (this.socket) {
      this.socket.disconnect()
      this.socket = null
    }
  }

  emit(event, data) {
    const sock = this.connect()
    if (!sock) return

    if (sock.connected) {
      sock.emit(event, data)
    } else {
      // If connection handshake is in progress, buffer and flush on connect
      sock.once('connect', () => {
        console.log(`[SocketService] Connection established, emitted queued '${event}'`)
        sock.emit(event, data)
      })
    }
  }

  on(event, callback) {
    const sock = this.connect()
    if (sock) {
      sock.on(event, callback)
    }
  }

  off(event, callback) {
    if (this.socket) {
      if (callback) {
        this.socket.off(event, callback)
      } else {
        this.socket.removeAllListeners(event)
      }
    }
  }

  get isConnected() {
    return !!this.socket?.connected
  }

  get id() {
    return this.socket?.id || null
  }
}

export const socketService = new SocketService()
export default socketService
