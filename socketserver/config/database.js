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
    `SELECT u.battle_rating, u.level, u.selected_character_id, u.avatar_style, u.avatar_seed, u.avatar_options,
            COALESCE(c.class_type, CASE WHEN u.selected_character_id = 2 THEN 'MELEE' WHEN u.selected_character_id = 3 THEN 'RANGE' ELSE 'MAGE' END) AS character_class,
            c.name AS character_name, c.profile_avatar_path
     FROM users u
     LEFT JOIN characters c ON u.selected_character_id = c.id
     WHERE u.id = ?`,
    [userId]
  )
  return rows[0] || { battle_rating: 1200, level: 1, character_class: 'MAGE', selected_character_id: 1 }
}

/**
 * Get a user's authoritative character combat stats and equipped cosmetics directly from DB.
 */
async function getUserCombatProfile(userId) {
  const db = getPool()
  // 1. Get user and character
  const [uRows] = await db.execute(
    `SELECT u.id, u.username, u.battle_rating, u.level, u.selected_character_id, u.avatar_style, u.avatar_seed, u.avatar_options,
            c.id AS char_id, c.key_name, c.name AS character_name, c.class_type, c.health AS base_health, c.damage AS base_damage, c.crit_damage AS base_crit,
            c.sprite_path, c.attack_asset_path, c.idle_asset_path, c.profile_avatar_path
     FROM users u
     LEFT JOIN characters c ON u.selected_character_id = c.id
     WHERE u.id = ?`,
    [userId]
  )
  const u = uRows[0] || {}
  const level = Math.max(1, Math.min(50, parseInt(u.level || 1)))
  const baseHealth = parseInt(u.base_health || 150)
  const baseDamage = parseInt(u.base_damage || 10)
  const baseCrit   = parseInt(u.base_crit || 35)

  const bonusPercent = (level - 1) * 0.10
  const scaledHealth = Math.round(baseHealth * (1.0 + bonusPercent))
  const scaledDamage = Math.round(baseDamage * (1.0 + bonusPercent))
  const scaledCrit   = Math.round(baseCrit * (1.0 + bonusPercent))

  // 2. Attribute allocations
  let allocatedDamage = 0
  let allocatedHealth = 0
  let allocatedCrit   = 0

  try {
    const [allocRows] = await db.execute(
      `SELECT attribute_type, bonus_value FROM user_attribute_allocations WHERE user_id = ?`,
      [userId]
    )
    for (const a of allocRows) {
      const val = parseInt(a.bonus_value || 0)
      if (a.attribute_type === 'attack_damage') allocatedDamage += val
      else if (a.attribute_type === 'health') allocatedHealth += val
      else if (a.attribute_type === 'critical_damage') allocatedCrit += val
    }
  } catch (e) {
    // Ignore allocation table error if empty
  }

  const finalMaxHealth = scaledHealth + allocatedHealth
  const finalDamage    = scaledDamage + allocatedDamage
  const finalCrit      = scaledCrit + allocatedCrit

  // 3. Equipped cosmetic effects
  const equipped = {}
  try {
    const [eqRows] = await db.execute(
      `SELECT ue.slot, i.id, i.effect_key, i.name, i.png_path, i.gif_path, i.character_class, ic.slug AS category_slug
       FROM user_equipped_items ue
       JOIN items i ON ue.item_id = i.id
       JOIN item_categories ic ON i.category_id = ic.id
       WHERE ue.user_id = ?`,
      [userId]
    )
    for (const row of eqRows) {
      equipped[row.slot] = row
    }
  } catch (e) {
    // Ignore if not found
  }

  const classType = (u.class_type || 'MAGE').toUpperCase()
  // Resolve default attack effect if none equipped
  if (!equipped.attack_effect) {
    if (classType === 'RANGE') {
      equipped.attack_effect = {
        effect_key: 'default-arrow',
        name: 'Default Swift Arrow',
        gif_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.gif',
        png_path: 'assets/ATTACK EFFECTS/ARCHER (RANGE)/arrow default.png',
        character_class: 'range',
      }
    } else if (classType === 'MELEE') {
      equipped.attack_effect = {
        effect_key: 'default-slash',
        name: 'Default Twin Slash',
        gif_path: 'assets/ATTACK EFFECTS/DUAL-WIELDER (MELEE)/normal attack.gif',
        png_path: 'assets/ATTACK EFFECTS/DUAL-WIELDER (MELEE)/normal attack.png',
        character_class: 'melee',
      }
    } else {
      equipped.attack_effect = {
        effect_key: 'default-orb',
        name: 'Default Arcane Orb',
        gif_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.gif',
        png_path: 'assets/ATTACK EFFECTS/NECROMANCER (MAGE)/default orb.png',
        character_class: 'mage',
      }
    }
  }

  // Resolve default splash effect if none equipped
  if (!equipped.splash_effect) {
    equipped.splash_effect = {
      effect_key: 'default-splash',
      name: 'Default Arcane Impact',
      gif_path: 'assets/SPLASH EFFECTS/DEFAULT SPLASH/default splash.gif',
      png_path: 'assets/SPLASH EFFECTS/DEFAULT SPLASH/default splash.png',
      character_class: 'all',
    }
  }

  return {
    user_id: userId,
    stats: {
      level,
      max_health: finalMaxHealth,
      damage: finalDamage,
      crit_damage: finalCrit,
      scaled_health: scaledHealth,
      scaled_damage: scaledDamage,
      scaled_crit_damage: scaledCrit,
      allocated_health: allocatedHealth,
      allocated_damage: allocatedDamage,
      allocated_crit_damage: allocatedCrit,
    },
    character: {
      id: u.char_id || 1,
      key_name: u.key_name || 'mage',
      name: u.character_name || 'Necromancer',
      class_type: classType,
      sprite_path: u.sprite_path,
      attack_asset_path: u.attack_asset_path,
      idle_asset_path: u.idle_asset_path,
      profile_avatar_path: u.profile_avatar_path,
    },
    equipped,
  }
}

module.exports = { getPool, fetchQuestionsForBattle, getUserRating, getUserCombatProfile }
