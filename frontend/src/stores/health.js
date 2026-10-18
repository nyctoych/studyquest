import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'
import { useAuthStore } from './auth'

/**
 * useHealthStore / Adventure Health Store
 * Centralized, single source of truth for StudyQuest Adventure Health:
 * - Persistent Adventure HP carry-over
 * - Strict 0-HP Adventure Lockout / Defeat gating (HP >= 1 required to battle)
 * - Authoritative passive regeneration (+50 HP/hour)
 * - Visual live countdown timer
 * - Health Potion instant restoration (+200 HP)
 * - HUD health state synchronization
 * - Strict isolation from Arena
 */
function getInitialHealthState() {
  try {
    const raw = localStorage.getItem('sq_user')
    if (raw) {
      const u = JSON.parse(raw)
      const lvl = Math.max(1, Math.min(50, Number(u?.level || 1)))
      const c = u?.selected_character
      let baseHealth = 150
      let baseDmg = 10
      let baseCrit = 35
      let name = 'Scholar'
      if (c) {
        if (c.health) baseHealth = Number(c.health)
        if (c.damage) baseDmg = Number(c.damage)
        if (c.crit_damage) baseCrit = Number(c.crit_damage)
        if (c.name) name = c.name
      } else {
        const code = (u?.character_code || u?.character_class || 'mage').toLowerCase()
        if (code === 'melee') { baseHealth = 200; baseDmg = 10; baseCrit = 20; name = 'Dual-Wielder' }
        else if (code === 'range') { baseHealth = 110; baseDmg = 15; baseCrit = 50; name = 'Archer' }
        else { baseHealth = 150; baseDmg = 10; baseCrit = 35; name = 'Necromancer' }
      }
      const bonus = (lvl - 1) * 0.10
      const scaledMax = u?.adventure_max_hp ? Number(u.adventure_max_hp) : Math.round(baseHealth * (1 + bonus))
      const scaledDmg = Math.round(baseDmg * (1 + bonus))
      const scaledCrit = Math.round(baseCrit * (1 + bonus))
      const current = (u?.adventure_current_hp !== undefined && u?.adventure_current_hp !== null)
        ? Number(u.adventure_current_hp)
        : (u?.adventure_health?.current_hp !== undefined ? Number(u.adventure_health.current_hp) : scaledMax)
      return {
        currentHp: current,
        maxHp: scaledMax,
        attackPower: scaledDmg,
        criticalAttackPower: scaledCrit,
        characterName: name,
        characterLevel: lvl
      }
    }
  } catch (_) {}
  return {
    currentHp: 150,
    maxHp: 150,
    attackPower: 10,
    criticalAttackPower: 35,
    characterName: 'Scholar',
    characterLevel: 1
  }
}

export const useHealthStore = defineStore('adventureHealth', () => {
  const initStats = getInitialHealthState()
  const currentHp = ref(initStats.currentHp)
  const maxHp = ref(initStats.maxHp)
  const regenRate = ref(1)
  const regenIntervalSeconds = ref(60)
  const secondsUntilNextRegen = ref(0)
  const lastRegenAt = ref(null)
  const attackPower = ref(initStats.attackPower)
  const criticalAttackPower = ref(initStats.criticalAttackPower)
  const characterName = ref(initStats.characterName)
  const characterLevel = ref(initStats.characterLevel)
  const loading = ref(false)
  const initialized = ref(false)

  let timerId = null

  // Minimum required health to enter Adventure is strictly 1 HP
  const isDefeated = computed(() => currentHp.value < 1)
  const canBattle = computed(() => currentHp.value >= 1)
  const isRecovering = computed(() => currentHp.value < maxHp.value)

  // Recovery progress of the current 1-minute tick (0% to 100%)
  const regenProgressPercent = computed(() => {
    if (!isRecovering.value) return 100
    const rem = Math.max(0, Math.min(60, Number(secondsUntilNextRegen.value || 0)))
    const elapsed = 60 - rem
    return Math.min(100, Math.max(0, Math.round((elapsed / 60) * 100)))
  })

  const hpPercent = computed(() => {
    if (!maxHp.value || maxHp.value <= 0) return 0
    return Math.max(0, Math.min(100, Math.round((currentHp.value / maxHp.value) * 100)))
  })

  // Visual status (Rule 34 & 35):
  // HP > 50% -> normal state
  // HP <= 50% -> warning state
  // HP <= 1 (and > 0) -> critical state
  // HP = 0 -> defeated state
  const statusState = computed(() => {
    if (currentHp.value < 1) return 'defeated'
    if (currentHp.value <= 1) return 'critical'
    if (hpPercent.value <= 50) return 'warning'
    return 'normal'
  })

  const formattedCountdown = computed(() => {
    const s = Math.max(0, Math.floor(secondsUntilNextRegen.value))
    const m = Math.floor(s / 60)
    const sec = s % 60
    if (m >= 60) {
      const h = Math.floor(m / 60)
      const remM = m % 60
      return `${String(h).padStart(2, '0')}:${String(remM).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
    }
    return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
  })

  function startTimer() {
    if (timerId) clearInterval(timerId)
    timerId = setInterval(() => {
      if (currentHp.value < maxHp.value) {
        if (secondsUntilNextRegen.value > 0) {
          secondsUntilNextRegen.value--
        }
        if (secondsUntilNextRegen.value <= 0) {
          // Gained 1 HP from 1-minute tick: advance locally and sync with authoritative server
          currentHp.value = Math.min(maxHp.value, currentHp.value + 1)
          secondsUntilNextRegen.value = 60
          fetchHealth(true)
        }
      }
    }, 1000)
  }

  function stopTimer() {
    if (timerId) {
      clearInterval(timerId)
      timerId = null
    }
  }

  function applyHealthData(h) {
    if (!h) return
    if (h.current_hp !== undefined) currentHp.value = Number(h.current_hp)
    if (h.max_hp !== undefined) maxHp.value = Number(h.max_hp)
    if (h.regen_rate !== undefined) regenRate.value = Number(h.regen_rate)
    if (h.seconds_until_next_regen !== undefined) secondsUntilNextRegen.value = Number(h.seconds_until_next_regen)
    if (h.last_regen_at !== undefined) lastRegenAt.value = h.last_regen_at
    if (h.attack_power !== undefined) attackPower.value = Number(h.attack_power)
    if (h.critical_attack_power !== undefined) criticalAttackPower.value = Number(h.critical_attack_power)
    if (h.character_name) characterName.value = h.character_name
    if (h.character_level) characterLevel.value = Number(h.character_level)

    // Sync auth store user properties if present
    try {
      const auth = useAuthStore()
      if (auth.user) {
        auth.user.adventure_current_hp = currentHp.value
        auth.user.adventure_max_hp = maxHp.value
        try { localStorage.setItem('sq_user', JSON.stringify(auth.user)) } catch (_) {}
      }
    } catch (_) {}

    startTimer()
  }

  function syncFromUser(u) {
    if (!u) return
    const lvl = Math.max(1, Math.min(50, Number(u?.level || 1)))
    characterLevel.value = lvl
    const c = u?.selected_character
    let baseHealth = 150
    let baseDmg = 10
    let baseCrit = 35
    if (c) {
      if (c.health) baseHealth = Number(c.health)
      if (c.damage) baseDmg = Number(c.damage)
      if (c.crit_damage) baseCrit = Number(c.crit_damage)
      if (c.name) characterName.value = c.name
    } else {
      const code = (u?.character_code || u?.character_class || 'mage').toLowerCase()
      if (code === 'melee') { baseHealth = 200; baseDmg = 10; baseCrit = 20; characterName.value = 'Dual-Wielder' }
      else if (code === 'range') { baseHealth = 110; baseDmg = 15; baseCrit = 50; characterName.value = 'Archer' }
      else { baseHealth = 150; baseDmg = 10; baseCrit = 35; characterName.value = 'Necromancer' }
    }
    const bonus = (lvl - 1) * 0.10
    const allocDmg = Number(u?.allocated_bonuses?.attack_damage || c?.allocated_bonuses?.attack_damage || 0)
    const allocHp = Number(u?.allocated_bonuses?.health || c?.allocated_bonuses?.health || 0)
    const allocCrit = Number(u?.allocated_bonuses?.critical_damage || c?.allocated_bonuses?.critical_damage || 0)

    const serverMax = (u?.adventure_max_hp !== undefined && u?.adventure_max_hp !== null)
      ? Number(u.adventure_max_hp)
      : (u?.adventure_health?.max_hp !== undefined ? Number(u.adventure_health.max_hp) : null)

    const scaledMax = serverMax || (Math.round(baseHealth * (1 + bonus)) + allocHp)
    maxHp.value = scaledMax
    attackPower.value = Math.round(baseDmg * (1 + bonus)) + allocDmg
    criticalAttackPower.value = Math.round(baseCrit * (1 + bonus)) + allocCrit
    if (u?.adventure_current_hp !== undefined && u?.adventure_current_hp !== null) {
      currentHp.value = Math.min(scaledMax, Number(u.adventure_current_hp))
    } else {
      currentHp.value = scaledMax
    }
  }

  async function fetchHealth(force = false) {
    if (loading.value && !force) return
    loading.value = true
    try {
      const res = await api.get('/adventure/health')
      if (res.data?.success && res.data?.health) {
        applyHealthData(res.data.health)
        initialized.value = true
      }
    } catch (e) {
      console.warn('[useHealthStore] Failed to fetch health:', e)
    } finally {
      loading.value = false
    }
  }

  function updateFromBattleAction(data) {
    if (!data) return
    const hp = (data.player_current_health !== undefined) ? data.player_current_health :
               (data.current_hp !== undefined) ? data.current_hp :
               (data.player_hp !== undefined) ? data.player_hp : null
    if (hp !== null) {
      currentHp.value = Number(hp)
    }
    if (data.player_max_health || data.max_hp || data.max_health) {
      maxHp.value = Number(data.player_max_health || data.max_hp || data.max_health)
    }
    if (data.attack_power) attackPower.value = Number(data.attack_power)
    if (data.critical_attack_power) criticalAttackPower.value = Number(data.critical_attack_power)

    try {
      const auth = useAuthStore()
      if (auth.user) {
        auth.user.adventure_current_hp = currentHp.value
        auth.user.adventure_max_hp = maxHp.value
        try { localStorage.setItem('sq_user', JSON.stringify(auth.user)) } catch (_) {}
      }
    } catch (_) {}
  }

  function updateFromPotion(data) {
    if (!data) return
    if (data.current_hp !== undefined) currentHp.value = Number(data.current_hp)
    if (data.max_hp !== undefined) maxHp.value = Number(data.max_hp)
    try {
      const auth = useAuthStore()
      if (auth.user) {
        auth.user.adventure_current_hp = currentHp.value
        auth.user.adventure_max_hp = maxHp.value
        try { localStorage.setItem('sq_user', JSON.stringify(auth.user)) } catch (_) {}
      }
    } catch (_) {}
  }

  return {
    currentHp,
    maxHp,
    regenRate,
    secondsUntilNextRegen,
    lastRegenAt,
    attackPower,
    criticalAttackPower,
    characterName,
    characterLevel,
    isDefeated,
    canBattle,
    isRecovering,
    regenProgressPercent,
    regenIntervalSeconds,
    hpPercent,
    statusState,
    formattedCountdown,
    applyHealthData,
    syncFromUser,
    fetchHealth,
    updateFromBattleAction,
    updateFromPotion,
    startTimer,
    stopTimer,
  }
})
