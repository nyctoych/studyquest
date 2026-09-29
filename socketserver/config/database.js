const mysql2 = require('mysql2/promise')
require('dotenv').config()

let pool = null

function getPool() {
  if (!pool) {
    pool = mysql2.createPool({
      host:     process.env.DB_HOST || 'localhost',
      port:     parseInt(process.env.DB_PORT || '3306'),
      database: process.env.DB_NAME || 'studyquest',
      user:     process.env.DB_USER || 'root',
      password: process.env.DB_PASS || '',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
    })
  }
  return pool
}

/**
 * Fetch N approved questions for a battle.
 * Returns full rows including correct_answer (server-side only — never sent to clients).
 */
async function fetchQuestionsForBattle(count = 10, categoryId = null, difficulty = null) {
  const db = getPool()
  let sql = `SELECT q.id, q.question, q.choice_a, q.choice_b, q.choice_c, q.choice_d,
                    q.correct_answer, q.difficulty, q.explanation,
                    qc.name AS category_name, qc.slug AS category_slug
             FROM questions q
             JOIN question_categories qc ON q.category_id = qc.id
             WHERE q.status = 'approved'`
  const params = []

  if (categoryId) {
    sql += ` AND q.category_id = ?`
    params.push(categoryId)
  }
  if (difficulty) {
    sql += ` AND q.difficulty = ?`
    params.push(difficulty)
  }
  sql += ` ORDER BY RAND() LIMIT ?`
  params.push(count)

  const [rows] = await db.execute(sql, params)
  return rows
}

/**
 * Get a user's current battle_rating and level from the database.
 */
async function getUserRating(userId) {
  const db = getPool()
  const [rows] = await db.execute(
    'SELECT battle_rating, level FROM users WHERE id = ?',
    [userId]
  )
  return rows[0] || { battle_rating: 1200, level: 1 }
}

module.exports = { getPool, fetchQuestionsForBattle, getUserRating }
