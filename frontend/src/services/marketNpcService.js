/**
 * marketNpcService.js
 * Centralized dialogue engine and state controller for the Market NPC Shopkeeper ("Raven").
 * Handles context-aware speech lines, priority overrides, anti-spam cooldowns, and mood states.
 */

import { reactive } from 'vue'

const DIALOGUES = {
  enter_market: [
    "Welcome, traveler. Take a look at what I've gathered.",
    "Back again? Perhaps something here has caught your eye.",
    "The Mindrealm yields strange wonders. Browse at your leisure.",
    "Step in from the dark. My wares are fresh from the deeper realms."
  ],

  view_item: [
    "Ah, you've noticed that one.",
    "That piece has quite a story behind it.",
    "Interested in this? Take a closer look.",
    "A rare craft. Not many can appreciate its nuances."
  ],

  select_item: [
    "That one carries a strange kind of glow.",
    "A fine choice to inspect.",
    "Curious about this relic, scholar?",
    "Every effect here has witnessed grand battles."
  ],

  purchase_success: [
    "Excellent choice. Another treasure finds a new owner.",
    "You've made a fine purchase. May it serve you well in the Mindrealm.",
    "A marvelous acquisition. Your coin was well spent.",
    "Wear it proudly. It suits your aura."
  ],

  insufficient_coins: [
    "Hmm... you're a little short on Coins.",
    "Come back when your purse is heavier, traveler.",
    "Even in the Mindrealm, merchants require honest coin.",
    "A tempting piece, but your vault cannot cover the toll just yet."
  ],

  purchase_failed: [
    "The transaction could not be settled.",
    "The ledger resists this trade. Try once more shortly."
  ],

  already_owned: [
    "You already possess that one in your collection.",
    "There's no need to buy another. You own this relic already.",
    "A fine artifact you already claimed from me."
  ],

  equip: [
    "Looking good. That should suit your character nicely.",
    "A formidable presence! Ready for the trials ahead.",
    "Fitted and attuned to your soul."
  ],

  unequip: [
    "Putting it away for now?",
    "Back into your archives it goes.",
    "Stored safely until you call upon it once more."
  ],

  category_change: {
    all: [
      "Here is everything currently in stock.",
      "The full collection, from cloaks to battle bursts."
    ],
    attack_effect: [
      "Projectiles imbued with elemental resonance.",
      "Choose a strike that suits your class."
    ],
    splash_effect: [
      "Impact bursts that scatter cognitive energy.",
      "Make every strike leave a visual mark."
    ],
    potions: [
      "Potions of rejuvenation and restorative vitality.",
      "A quick draft of this will keep your spirits high in Adventure."
    ],
    potion: [
      "Potions of rejuvenation and restorative vitality.",
      "A quick draft of this will keep your spirits high in Adventure."
    ]
  },

  view_potion: [
    "A little vitality never hurts.",
    "Brewed from restorative herbs found in the calm corners of the Mindrealm."
  ],

  select_potion: [
    "That should keep you standing.",
    "An elixir of vital resilience. Never venture into deep trials without one."
  ],

  potion_purchase_success: [
    "Good choice. You may need it.",
    "Vitality restored. May your resolve hold firm."
  ],

  potion_insufficient_coins: [
    "Even healing has a price.",
    "A life-saving brew, but your purse falls short."
  ],

  potion_already_full: [
    "That one cannot be used right now.",
    "Your vitality is already brimming at full health."
  ],

  search_items: [
    "Seeking something specific among my shelves?",
    "Filter through the dust. You might uncover a hidden gem."
  ],

  filter_owned: [
    "Inspecting your personal spoils, I see.",
    "Your private hoard grows with each passing trial."
  ],

  filter_affordable: [
    "Wares strictly within your current means.",
    "Everything here can be yours right now."
  ],

  leave_market: [
    "Come back when you're ready for another bargain.",
    "May your studies be sharp and your battles victorious.",
    "I'll be here when you return, scholar."
  ]
}

// Story-stage greetings. Stage is derived server-side (merchant_stage in /adventure/story-state).
// Clues are subtle and only make sense in retrospect after the final reveal.
const STAGE_ENTER_DIALOGUES = {
  clues: [
    "Another traveler who walks as if the road were already written.",
    "Some of these pages are older than the shelves that hold them.",
    "You remind me of someone. Never mind. Browse."
  ],
  unusual: [
    "I have seen this world's edges fray before. Hold on to your choices, scholar.",
    "Careful with the manuscripts you find. Some were never meant to be read twice.",
    "Funny. The deeper you go, the more it feels like I am the one being read."
  ],
  vanished: [
    "The stall is quiet. A ledger lies open, and the ink is still wet.",
    "Raven is not here. Only a lantern, burning low beside the till."
  ],
  father: [
    "You know who I am now. The shelves are yours to browse, as always.",
    "She would have appreciated that you came back. I keep this stall in her memory.",
    "Some stories end unfinished. I am grateful you stayed to face her other side and hear her story.",
    "Take what you need. I could not change how her story ended, but I can still keep this door open."
  ]
}

// Priority levels (higher number = higher priority to override active speech)
const PRIORITY = {
  enter_market: 1,
  hover_item: 1,
  search_items: 1,
  category_change: 2,
  view_item: 2,
  select_item: 3,
  equip: 4,
  unequip: 4,
  insufficient_coins: 5,
  purchase_failed: 5,
  purchase_success: 6,
  leave_market: 6
}

class MarketNpcService {
  constructor() {
    this.state = reactive({
      npcName: 'RAVEN',
      npcRole: 'Item Merchant',
      currentDialogue: "Welcome, traveler. Take a look at what I've gathered.",
      npcState: 'idle', // 'idle' | 'talking' | 'happy' | 'thinking' | 'surprised'
      isSpeaking: false,
      lastAction: null,
      lastTargetId: null,
      lastActionTime: 0,
      recentHistory: []
    })

    this._dialogueTimer = null
    this._stateTimer = null
    this.stage = 'normal'
  }

  /** Set story stage from server value; unknown values fall back to normal. */
  setStage(stage) {
    this.stage = STAGE_ENTER_DIALOGUES[stage] ? stage : 'normal'
    if (this.stage === 'father') {
      this.state.npcRole = 'The Father of the Creator'
    } else {
      this.state.npcRole = 'Item Merchant'
    }
  }

  get dialogue() {
    return this.state.currentDialogue
  }

  get isSpeaking() {
    return this.state.isSpeaking
  }

  get npcState() {
    return this.state.npcState
  }

  /**
   * Main reaction entry point
   * @param {string} action - Action key from DIALOGUES
   * @param {object} context - Optional details (item, category, etc.)
   */
  react(action, context = {}) {
    const now = Date.now()
    const targetId = context.item?.id || context.category || null

    // Anti-spam rules:
    // 1. Same action + same target within 2.5s -> ignore (unless high-priority transaction)
    const priority = PRIORITY[action] || 2
    if (priority < 4) {
      if (this.state.lastAction === action && this.state.lastTargetId === targetId && (now - this.state.lastActionTime) < 2500) {
        return
      }
      // General action debounce for low priority
      if ((now - this.state.lastActionTime) < 600) {
        return
      }
    }

    const isPotion = Boolean(
      context.item?.type === 'potion' ||
      context.item?.category_slug === 'potion' ||
      context.item?.category_slug === 'potions' ||
      context.item?.effect_key === 'health-potion'
    )

    let effectiveAction = action
    if (isPotion) {
      if (action === 'view_item' && DIALOGUES.view_potion) effectiveAction = 'view_potion'
      else if (action === 'select_item' && DIALOGUES.select_potion) effectiveAction = 'select_potion'
      else if (action === 'purchase_success' && DIALOGUES.potion_purchase_success) effectiveAction = 'potion_purchase_success'
      else if (action === 'insufficient_coins' && DIALOGUES.potion_insufficient_coins) effectiveAction = 'potion_insufficient_coins'
      else if ((action === 'already_full' || action === 'already_owned') && DIALOGUES.potion_already_full) effectiveAction = 'potion_already_full'
    }

    let line = ''

    if (effectiveAction === 'category_change' && context.category) {
      const catKey = context.category
      const catPool = DIALOGUES.category_change[catKey] || DIALOGUES.category_change.all
      line = this._pickRandom(catPool)
    } else if (effectiveAction === 'enter_market' && STAGE_ENTER_DIALOGUES[this.stage]) {
      line = this._pickRandom(STAGE_ENTER_DIALOGUES[this.stage])
    } else if (DIALOGUES[effectiveAction]) {
      const pool = DIALOGUES[effectiveAction]
      if (Array.isArray(pool)) {
        line = this._pickRandom(pool)
      } else {
        line = String(pool)
      }
    }

    if (!line) return

    // Update state tracking
    this.state.lastAction = action
    this.state.lastTargetId = targetId
    this.state.lastActionTime = now

    // Set NPC animation mood
    if (action === 'purchase_success') {
      this.state.npcState = 'happy'
    } else if (action === 'insufficient_coins') {
      this.state.npcState = 'thinking'
    } else if (action === 'purchase_failed') {
      this.state.npcState = 'surprised'
    } else {
      this.state.npcState = 'talking'
    }

    // Set dialogue text
    this.state.currentDialogue = line
    this.state.isSpeaking = true

    // Clear previous timers
    if (this._dialogueTimer) clearTimeout(this._dialogueTimer)
    if (this._stateTimer) clearTimeout(this._stateTimer)

    // Stop mouth/talking animation after 1.8s
    this._stateTimer = setTimeout(() => {
      this.state.npcState = 'idle'
    }, 1800)

    // Dialogue duration scales with line length (minimum 3.2s, max 6s)
    const duration = Math.min(6000, Math.max(3200, line.length * 60))
    this._dialogueTimer = setTimeout(() => {
      this.state.isSpeaking = false
    }, duration)
  }

  _pickRandom(array) {
    if (!array || array.length === 0) return ''
    if (array.length === 1) return array[0]

    // Avoid immediate repeat
    const eligible = array.filter(item => !this.state.recentHistory.includes(item))
    const pool = eligible.length > 0 ? eligible : array
    const chosen = pool[Math.floor(Math.random() * pool.length)]

    this.state.recentHistory.push(chosen)
    if (this.state.recentHistory.length > 3) {
      this.state.recentHistory.shift()
    }
    return chosen
  }
}

export const marketNpc = new MarketNpcService()
export default marketNpc
