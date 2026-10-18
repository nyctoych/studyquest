const { log } = require('../utils/logger')
const { saveMatchResult } = require('../utils/phpApi')
const { getPool } = require('../config/database')

const DAMAGE_PER_CORRECT = 10
const XP_WIN  = 150
const XP_LOSS = 50
const XP_DRAW = 75
const COINS_WIN  = 50
const COINS_LOSS = 10
const COINS_DRAW = 25
const K_FACTOR = 32

class BattleManager {
  constructor(io, room, roomManager) {
    this.io          = io
    this.room        = room
    this.roomManager = roomManager
    this.answerTracker = {}   // questionIndex → { userId: answered }
    this.battleEnded = false
  }

  /**
   * Send a question to both players (without correct_answer).
   */
  sendQuestion(index) {
    const { room, io } = this
    if (index >= room.questions.length) {
      this._endBattle('questions_exhausted')
      return
    }

    room.currentIndex = index
    const q = room.questions[index]

    // Reset answer tracker for this question
    this.answerTracker[index] = {}

    const questionDeadline = Date.now() + (parseInt(process.env.BATTLE_QUESTION_TIME_SECONDS || '15') * 1000)

    io.to(room.roomId).emit('question_started', {
      index,
      total:    room.questions.length,
      deadline: questionDeadline,
      question: {
        id:            q.id,
        question:      q.question,
        choice_a:      q.choice_a,
        choice_b:      q.choice_b,
        choice_c:      q.choice_c,
        choice_d:      q.choice_d,
        difficulty:    q.difficulty,
        category_name: q.category_name,
      },
    })

    io.to(room.roomId).emit('battle:question', {
      index,
      total:     room.questions.length,
      timeLimit: parseInt(process.env.BATTLE_QUESTION_TIME_SECONDS || '15'),
      question: {
        id:            q.id,
        question:      q.question,
        choice_a:      q.choice_a,
        choice_b:      q.choice_b,
        choice_c:      q.choice_c,
        choice_d:      q.choice_d,
        difficulty:    q.difficulty,
        category_name: q.category_name,
      },
    })

    log(`[BATTLE] ${room.roomId} — Question ${index + 1}/${room.questions.length} sent`)
  }

  /**
   * Process a player's answer submission with Adventure Gate arithmetic and effect resolution.
   */
  submitAnswer(userId, questionId, answer, isCritical = false) {
    const { room, io } = this

    if (this.battleEnded) return
    if (room.status !== 'in_progress') return

    const player = room.players[userId]
    if (!player) return

    const currentQuestion = room.questions[room.currentIndex]

    // Validate question matches current
    if (currentQuestion.id !== questionId) {
      player.socket.emit('error', { message: 'Answer submitted for wrong question' })
      return
    }

    // Prevent double-answering
    if (this.answerTracker[room.currentIndex]?.[userId]) {
      return
    }

    const opponent = this._getOpponent(userId)

    // Server-side validation
    const isCorrect = String(answer).trim().toLowerCase() === String(currentQuestion.correct_answer).trim().toLowerCase()

    let damageAmount = 0
    let attacker = null
    let target = null
    let damageType = 'normal'

    if (isCorrect) {
      // ══════════════════════════════════════════════════════════════
      // CORRECT ANSWER:
      // Answering player attacks opponent!
      // ══════════════════════════════════════════════════════════════
      attacker = player
      target = opponent
      damageAmount = isCritical ? (player.crit_damage || 35) : (player.damage || 10)
      damageType = isCritical ? 'critical' : 'normal'
      player.correct++
      if (opponent) {
        opponent.hp = Math.max(0, opponent.hp - damageAmount)
      }
    } else {
      // ══════════════════════════════════════════════════════════════
      // INCORRECT ANSWER:
      // Opposing player becomes the attacker!
      // ══════════════════════════════════════════════════════════════
      attacker = opponent || player
      target = player
      damageAmount = isCritical ? (player.crit_damage || 35) : (opponent?.damage || player.damage || 10)
      damageType = isCritical ? 'critical_backfire' : 'normal_damage'
      player.wrong++
      player.hp = Math.max(0, player.hp - damageAmount)
    }

    const attemptId = `${room.roomId}_q${room.currentIndex}_u${userId}`

    if (!this.answerTracker[room.currentIndex]) {
      this.answerTracker[room.currentIndex] = {}
    }

    this.answerTracker[room.currentIndex][userId] = {
      attemptId,
      isCorrect,
      damage: damageAmount,
      isCritical,
      damageType,
      attackerId: attacker?.userId,
      targetId: target?.userId,
    }

    // Record question attempt for psychology learning analytics
    try {
      getPool().execute(
        `INSERT INTO question_attempts (user_id, question_id, battle_id, is_correct, response_time, difficulty)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [userId, currentQuestion.id, room.roomId, isCorrect ? 1 : 0, 0, currentQuestion.difficulty || 'intermediate']
      ).catch(err => log(`[BATTLE] Attempt recording notice: ${err.message}`))
    } catch (e) {
      log(`[BATTLE] Attempt recording err: ${e.message}`)
    }

    const hpState = this._getHpState()

    // Broadcast authoritative combat action to both players
    const combatAction = {
      attemptId,
      questionIndex: room.currentIndex,
      answeringUserId: userId,
      isCorrect,
      isCritical,
      attackerId: attacker?.userId,
      targetId: target?.userId,
      damage: damageAmount,
      damageType,
      attackEffect: attacker?.equipped?.attack_effect || null,
      splashEffect: attacker?.equipped?.splash_effect || null,
      attackerClass: attacker?.character_class || attacker?.class_type || 'MAGE',
      targetClass: target?.character_class || target?.class_type || 'MAGE',
      attackerName: attacker?.username,
      targetName: target?.username,
      hp: hpState,
    }
    io.to(room.roomId).emit('battle:combat_action', combatAction)

    // Send answer_result to the answering player
    player.socket.emit('answer_result', {
      attemptId,
      correct:        isCorrect,
      correct_answer: currentQuestion.correct_answer,
      explanation:    currentQuestion.explanation,
      damage:         damageAmount,
      isCritical,
    })

    // Broadcast HP update to both players
    io.to(room.roomId).emit('hp_update', hpState)

    log(`[BATTLE] ${room.roomId} — ${player.username} answered ${isCorrect ? 'CORRECT' : 'WRONG'} | Dmg: ${damageAmount} (${damageType}) | Attacker: ${attacker?.username} → Target: ${target?.username} | HP: ${JSON.stringify(hpState)}`)

    // Check if either player is defeated (HP clamped at 0)
    if (player.hp <= 0 || (opponent && opponent.hp <= 0)) {
      const winnerId = (opponent && opponent.hp <= 0) ? player.userId : opponent?.userId
      const playerResults = {}
      for (const p of Object.values(room.players)) {
        const tr = this.answerTracker[room.currentIndex]?.[p.userId]
        playerResults[p.userId] = {
          correct: tr?.isCorrect ?? false,
          damageDealt: tr?.damage ?? 0,
        }
      }
      io.to(room.roomId).emit('battle:round_result', {
        correctAnswer: currentQuestion.correct_answer,
        explanation: currentQuestion.explanation,
        hp: hpState,
        playerResults,
      })

      // Slight delay for animation completion
      setTimeout(() => this._endBattle('hp_depleted', winnerId), 1500)
      return
    }

    // Check if all players answered this question
    const allAnswered = Object.values(room.players).every(
      p => this.answerTracker[room.currentIndex]?.[p.userId]
    )

    if (allAnswered) {
      // Advance to next question after delay
      const timerMgr = this.roomManager.getTimerManager(room.roomId)
      if (timerMgr) timerMgr.stop()

      const playerResults = {}
      for (const p of Object.values(room.players)) {
        const tr = this.answerTracker[room.currentIndex]?.[p.userId]
        playerResults[p.userId] = {
          correct: tr?.isCorrect ?? false,
          damageDealt: tr?.damage ?? 0,
        }
      }

      io.to(room.roomId).emit('battle:round_result', {
        correctAnswer: currentQuestion.correct_answer,
        explanation: currentQuestion.explanation,
        hp: hpState,
        playerResults,
      })

      setTimeout(() => {
        if (!this.battleEnded) {
          const nextIndex = room.currentIndex + 1
          if (nextIndex >= room.questions.length) {
            this._endBattle('questions_exhausted')
          } else {
            this.sendQuestion(nextIndex)
            timerMgr && timerMgr.startTimer(nextIndex)
          }
        }
      }, 2500)
    }
  }

  /**
   * Called by TimerManager when question time expires.
   */
  onQuestionTimeout(questionIndex) {
    if (this.battleEnded) return
    const { room, io } = this
    if (room.currentIndex !== questionIndex) return

    if (!this.answerTracker[questionIndex]) {
      this.answerTracker[questionIndex] = {}
    }

    // Close question — players who didn't answer get it wrong automatically
    for (const player of Object.values(room.players)) {
      if (!this.answerTracker[questionIndex][player.userId]) {
        const opponent = this._getOpponent(player.userId)
        const damageAmount = opponent?.damage || player.damage || 10
        player.hp = Math.max(0, player.hp - damageAmount)
        player.wrong++

        const attemptId = `${room.roomId}_q${questionIndex}_u${player.userId}_timeout`
        this.answerTracker[questionIndex][player.userId] = {
          attemptId,
          isCorrect: false,
          damage: damageAmount,
          isCritical: false,
          damageType: 'normal_damage',
          attackerId: opponent?.userId,
          targetId: player.userId,
        }

        const hpState = this._getHpState()

        const combatAction = {
          attemptId,
          questionIndex,
          answeringUserId: player.userId,
          isCorrect: false,
          isCritical: false,
          attackerId: opponent?.userId,
          targetId: player.userId,
          damage: damageAmount,
          damageType: 'normal_damage',
          attackEffect: opponent?.equipped?.attack_effect || null,
          splashEffect: opponent?.equipped?.splash_effect || null,
          attackerClass: opponent?.character_class || opponent?.class_type || 'MAGE',
          targetClass: player?.character_class || player?.class_type || 'MAGE',
          attackerName: opponent?.username,
          targetName: player?.username,
          hp: hpState,
        }
        io.to(room.roomId).emit('battle:combat_action', combatAction)

        player.socket.emit('answer_result', {
          attemptId,
          correct: false,
          correct_answer: room.questions[questionIndex].correct_answer,
          explanation: room.questions[questionIndex].explanation,
          damage: damageAmount,
          timeout: true,
        })
      }
    }

    const hpState = this._getHpState()
    io.to(room.roomId).emit('hp_update', hpState)

    // Check if any player is defeated
    const playersList = Object.values(room.players)
    const p1 = playersList[0]
    const p2 = playersList[1]
    if (p1 && p2 && (p1.hp <= 0 || p2.hp <= 0)) {
      let winnerId = null
      if (p1.hp <= 0 && p2.hp > 0) winnerId = p2.userId
      else if (p2.hp <= 0 && p1.hp > 0) winnerId = p1.userId

      const q = room.questions[questionIndex]
      const playerResults = {}
      for (const p of playersList) {
        const tr = this.answerTracker[questionIndex]?.[p.userId]
        playerResults[p.userId] = {
          correct: tr?.isCorrect ?? false,
          damageDealt: tr?.damage ?? 0,
        }
      }
      io.to(room.roomId).emit('battle:round_result', {
        correctAnswer: q.correct_answer,
        explanation: q.explanation,
        hp: hpState,
        playerResults,
      })
      setTimeout(() => this._endBattle('hp_depleted', winnerId), 1500)
      return
    }

    const q = room.questions[questionIndex]
    const playerResults = {}
    for (const p of Object.values(room.players)) {
      const tr = this.answerTracker[questionIndex]?.[p.userId]
      playerResults[p.userId] = {
        correct: tr?.isCorrect ?? false,
        damageDealt: tr?.damage ?? 0,
      }
    }

    io.to(room.roomId).emit('battle:round_result', {
      correctAnswer: q.correct_answer,
      explanation: q.explanation,
      hp: hpState,
      playerResults,
    })

    // Move to next question
    const nextIndex = questionIndex + 1
    if (nextIndex >= room.questions.length) {
      setTimeout(() => this._endBattle('questions_exhausted'), 1500)
    } else {
      setTimeout(() => {
        const timerMgr = this.roomManager.getTimerManager(room.roomId)
        this.sendQuestion(nextIndex)
        timerMgr && timerMgr.startTimer(nextIndex)
      }, 2000)
    }
  }

  /**
   * End the battle, calculate results, and persist to PHP.
   */
  async _endBattle(reason, winnerUserId = null) {
    if (this.battleEnded) return
    this.battleEnded = true

    const { room, io } = this

    // Stop timer
    const timerMgr = this.roomManager.getTimerManager(room.roomId)
    if (timerMgr) timerMgr.stop()

    const players  = Object.values(room.players)
    const [p1, p2] = players

    // Determine outcome
    let outcome = {}  // userId → 'won'|'lost'|'draw'

    if (reason === 'hp_depleted' && winnerUserId) {
      outcome[winnerUserId] = 'won'
      const loserId = players.find(p => p.userId !== winnerUserId)?.userId
      if (loserId) outcome[loserId] = 'lost'

    } else if (reason === 'disconnect_timeout') {
      // Handled externally by DisconnectManager
      outcome[winnerUserId] = 'won'
      const loserId = players.find(p => p.userId !== winnerUserId)?.userId
      if (loserId) outcome[loserId] = 'lost'

    } else {
      // Questions exhausted → compare HP
      if (p1.hp > p2.hp) {
        outcome[p1.userId] = 'won'
        outcome[p2.userId] = 'lost'
      } else if (p2.hp > p1.hp) {
        outcome[p2.userId] = 'won'
        outcome[p1.userId] = 'lost'
      } else {
        outcome[p1.userId] = 'draw'
        outcome[p2.userId] = 'draw'
      }
    }

    // Calculate standard ELO changes (Rating deduction/gain is NOT based on coin bet)
    const eloChanges = this._calculateElo(p1, p2, outcome[p1.userId])

    // Ranked coin bet: winner gains bet amount, loser loses bet amount, draw 0
    const coinBet = this.room.coinBet || 100

    const playersPayload = players.map(p => {
      const oc        = outcome[p.userId]
      const xp        = oc === 'won' ? XP_WIN : oc === 'lost' ? XP_LOSS : XP_DRAW
      const coins     = oc === 'won' ? coinBet : oc === 'lost' ? -coinBet : 0
      const ratingChg = eloChanges[p.userId]?.change ?? 0
      const newRating = Math.max(800, p.rating + ratingChg)

      return {
        user_id:        p.userId,
        slot:           p.slot,
        hp_end:         p.hp,
        correct_answers: p.correct,
        wrong_answers:  p.wrong,
        outcome:        oc,
        xp_earned:      xp,
        coins_earned:   coins,
        rating_after:   newRating,
        rating_before:  p.rating,
      }
    })

    // Emit battle results to each player
    for (const p of players) {
      const oc = outcome[p.userId]
      const pd = playersPayload.find(x => x.user_id === p.userId)
      const oppPd = playersPayload.find(x => x.user_id !== p.userId)

      const eventName = oc === 'won' ? 'battle_won' : oc === 'lost' ? 'battle_lost' : 'battle_draw'

      p.socket.emit(eventName, {
        outcome:       oc,
        reason,
        my_hp:         p.hp,
        opponent_hp:   this._getOpponent(p.userId)?.hp ?? 0,
        correct:       p.correct,
        wrong:         p.wrong,
        xp_earned:     pd.xp_earned,
        coins_earned:  pd.coins_earned,
        coin_bet:      coinBet,
        rating_change: pd.rating_after - pd.rating_before,
        new_rating:    pd.rating_after,
        opponent:      { username: this._getOpponent(p.userId)?.username, hp: oppPd?.hp_end },
      })
    }

    const playerResultsMap = {}
    for (const p of players) {
      const oc = outcome[p.userId]
      const pd = playersPayload.find(x => x.user_id === p.userId)
      playerResultsMap[p.userId] = {
        outcome: oc,
        ratingChange: pd ? (pd.rating_after - pd.rating_before) : 0,
        coinsEarned: pd ? pd.coins_earned : 0,
        xpEarned: pd ? pd.xp_earned : 0,
        correct: p.correct,
        wrong: p.wrong,
        hp: p.hp,
      }
    }

    io.to(room.roomId).emit('battle:game_over', {
      reason,
      winnerId: winnerUserId,
      coinBet,
      pot: coinBet * 2,
      playerResults: playerResultsMap,
    })

    // Persist to PHP backend
    try {
      await saveMatchResult({
        room_id:      room.roomId,
        battle_mode:  'ranked',
        question_ids: room.questions.map(q => q.id),
        players:      playersPayload,
      })
      log(`[BATTLE] ${room.roomId} — Results saved to PHP backend`)
    } catch (err) {
      log(`[BATTLE] ${room.roomId} — Error saving results: ${err.message}`)
    }

    // Close room after delay
    setTimeout(() => this.roomManager.closeRoom(room.roomId), 5000)
  }

  // Called by DisconnectManager for forfeit
  async forfeit(disconnectedUserId) {
    const winner = Object.values(this.room.players).find(p => p.userId !== disconnectedUserId)
    if (winner) {
      this.io.to(this.room.roomId).emit('opponent_disconnected', { disconnected: true, grace_expired: true })
      await this._endBattle('disconnect_timeout', winner.userId)
    }
  }

  _getOpponent(userId) {
    return Object.values(this.room.players).find(p => p.userId !== userId) || null
  }

  _getHpState() {
    const state = {}
    for (const p of Object.values(this.room.players)) {
      state[p.userId] = p.hp
    }
    return state
  }

  _calculateElo(p1, p2, p1Outcome) {
    const expected1 = 1 / (1 + Math.pow(10, (p2.rating - p1.rating) / 400))
    const expected2 = 1 - expected1

    let actual1, actual2
    if (p1Outcome === 'won') { actual1 = 1; actual2 = 0 }
    else if (p1Outcome === 'lost') { actual1 = 0; actual2 = 1 }
    else { actual1 = 0.5; actual2 = 0.5 }

    const change1 = Math.round(K_FACTOR * (actual1 - expected1))
    const change2 = Math.round(K_FACTOR * (actual2 - expected2))

    return {
      [p1.userId]: { change: change1, new: Math.max(800, p1.rating + change1) },
      [p2.userId]: { change: change2, new: Math.max(800, p2.rating + change2) },
    }
  }
}

module.exports = BattleManager
