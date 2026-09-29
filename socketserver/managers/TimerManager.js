const { log } = require('../utils/logger')

const QUESTION_TIME_MS = parseInt(process.env.BATTLE_QUESTION_TIME_SECONDS || '15') * 1000

class TimerManager {
  constructor(io, roomId, battleManager) {
    this.io            = io
    this.roomId        = roomId
    this.battleManager = battleManager
    this._timer        = null
    this._interval     = null
    this._currentIndex = null
  }

  startTimer(questionIndex) {
    this.stop()
    this._currentIndex = questionIndex

    const totalSeconds = parseInt(process.env.BATTLE_QUESTION_TIME_SECONDS || '15')
    let remaining = totalSeconds
    this.io.to(this.roomId).emit('battle:timer_tick', { remaining })

    this._interval = setInterval(() => {
      remaining--
      if (remaining >= 0) {
        this.io.to(this.roomId).emit('battle:timer_tick', { remaining })
      }
    }, 1000)

    this._timer = setTimeout(() => {
      this.stop()
      log(`[TIMER] ${this.roomId} — Question ${questionIndex} timed out`)
      this.io.to(this.roomId).emit('question_timeout', { index: questionIndex })
      this.battleManager.onQuestionTimeout(questionIndex)
    }, totalSeconds * 1000)
  }

  stop() {
    if (this._interval) {
      clearInterval(this._interval)
      this._interval = null
    }
    if (this._timer) {
      clearTimeout(this._timer)
      this._timer = null
    }
  }
}

module.exports = TimerManager
