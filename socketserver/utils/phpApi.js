const axios = require('axios')
require('dotenv').config()

const PHP_API_URL      = process.env.PHP_API_URL || 'http://localhost/StudyQuest/backend/index.php'
const INTERNAL_TOKEN   = process.env.INTERNAL_TOKEN || 'studyquest_socket_secret_2024'

/**
 * Save match result to PHP backend.
 * PHP will update ratings, XP, coins, and record history.
 */
async function saveMatchResult(payload) {
  const url = `${PHP_API_URL}/multiplayer/match/save`
  const response = await axios.post(url, payload, {
    headers: {
      'Content-Type':    'application/json',
      'X-Internal-Token': INTERNAL_TOKEN,
    },
    timeout: 10000,
  })
  return response.data
}

/**
 * Fetch combat profile for a user from PHP backend
 * Returns authoritative scaled stats, character details, and equipped effects.
 */
async function getCombatProfile(userId) {
  try {
    const url = `${PHP_API_URL}/multiplayer/combat-profile?user_id=${userId}`
    const response = await axios.get(url, {
      headers: {
        'X-Internal-Token': INTERNAL_TOKEN,
      },
      timeout: 5000,
    })
    return response.data?.success ? response.data : null
  } catch (e) {
    return null
  }
}

module.exports = { saveMatchResult, getCombatProfile }
