/**
 * Countdown timer utility (Vanilla JS)
 * Returns reactive time remaining for a deadline
 */
export function createCountdown(deadline) {
  function getTimeLeft() {
    const now  = new Date().getTime()
    const end  = new Date(deadline).getTime()
    const diff = end - now

    if (diff <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true }

    const days    = Math.floor(diff / (1000 * 60 * 60 * 24))
    const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    const seconds = Math.floor((diff % (1000 * 60)) / 1000)

    return { days, hours, minutes, seconds, expired: false }
  }

  return { getTimeLeft }
}

/**
 * Study timer (simple stopwatch)
 */
export class StudyTimer {
  constructor() {
    this.startTime   = null
    this.elapsed     = 0
    this.intervalId  = null
    this.running     = false
    this.onTick      = null
  }

  start(onTick) {
    if (this.running) return
    this.running   = true
    this.startTime = Date.now() - this.elapsed
    this.onTick    = onTick
    this.intervalId = setInterval(() => {
      this.elapsed = Date.now() - this.startTime
      if (this.onTick) this.onTick(this.getFormatted())
    }, 1000)
  }

  pause() {
    this.running = false
    clearInterval(this.intervalId)
  }

  reset() {
    this.pause()
    this.elapsed = 0
  }

  getFormatted() {
    const ms      = this.elapsed
    const hours   = Math.floor(ms / 3600000)
    const minutes = Math.floor((ms % 3600000) / 60000)
    const seconds = Math.floor((ms % 60000) / 1000)
    return {
      hours,
      minutes,
      seconds,
      display: `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`,
    }
  }
}
