/**
 * StudyQuest — Centralized Battle Assets Configuration
 * Defines placeholders, boss mapping hooks, and enemy dialogue reactions for Adventure.
 * Future custom sprites can be plugged in here without altering battlefield components.
 */

export const battleAssets = {
  // Player Battle Sprite Placeholder
  playerPlaceholder: {
    id: 'scholar_default',
    name: 'Scholar Adventurer',
    type: 'placeholder',
    // Future sprite sheet / asset path can be placed here:
    spriteUrl: '',
    role: 'Scholar of the Mind',
    defaultScale: 1.0,
    idleAnimation: 'anim-player-idle',
    attackAnimation: 'anim-player-attack',
  },

  // Minion / Normal Level Enemy Placeholder
  minionPlaceholder: {
    id: 'fracture_specter',
    name: 'Fracture Specter',
    title: 'Cognitive Aberration',
    type: 'placeholder',
    // Future sprite sheet / asset path can be placed here:
    spriteUrl: '',
    defaultScale: 1.0,
    idleAnimation: 'anim-minion-idle',
    hitAnimation: 'anim-minion-hit',
    laughAnimation: 'anim-minion-laugh',
  },

  // Enemy Speech Bubble Dialogues (Triggered on Wrong Answers)
  dialogues: {
    generalWrong: [
      "Ha! Too easy.",
      "You're not ready.",
      "Heh... you hesitated.",
      "That answer was far too weak.",
      "You still have much to learn.",
      "I knew you would fail.",
      "Pathetic attempt at reasoning.",
      "Your hypothesis crumbles into dust!",
      "Is that the best your mind can muster?",
      "False criteria! The archives reject you.",
      "Did you even consult the grimoire?",
      "A flawed deduction. Try harder, scholar!"
    ],

    // Specific Chapter Boss Voice Lines
    bossWrong: {
      1: [ // Brain - The First Mind
        "Your synapses misfire, scholar!",
        "A primitive logic loop. Predictable.",
        "Your cognitive baseline is inadequate.",
        "Ha! My primordial pathways outthink you."
      ],
      2: [ // Eye - The Veiled Reality
        "Your vision is clouded by illusions!",
        "You cannot pierce the veil with such guesswork.",
        "You mistake a phantom for reality!",
        "Blind to the true differential diagnosis!"
      ],
      3: [ // Skull - The Dual Crown
        "Your mood swings into total failure!",
        "Neither manic nor depressive... just wrong!",
        "The pendulum swings against you, mortal!",
        "An oscillation of pure error!"
      ],
      4: [ // Shadows - Wisp
        "Swallowed by anxiety and doubt!",
        "Your fears cloud your clinical judgment.",
        "Tremble before the panic of the unknown!",
        "Hesitation is the signature of the doomed."
      ],
      5: [ // Heart
        "Your empathy cannot compensate for ignorance!",
        "A fractured pulse of flawed deduction.",
        "Emotion without diagnostic criteria is worthless!"
      ],
      6: [ // Mask
        "The persona slips... revealing your confusion!",
        "Behind my mask is the truth you failed to grasp.",
        "You cannot unmask this diagnostic secret!"
      ],
      7: [ // Clock
        "Time runs out for your feeble reasoning!",
        "Chronological criteria violated! How careless.",
        "Your diagnostic timeline has collapsed!"
      ],
      8: [ // Book
        "Did you forget the most sacred DSM criteria?",
        "Torn pages and broken citations. Inadmissible!",
        "The Grand Grimoire rejects your flawed answer!"
      ],
      9: [ // Purple Brain
        "Neurochemical chaos overwhelms your intellect!",
        "A toxic misinterpretation of clinical pharmacology!",
        "Dopamine pathways refuse your incorrect choice!"
      ],
      10: [ // Wing
        "You plummet from the heights of reasoning!",
        "Your thesis cannot take flight on broken facts.",
        "Grounded by basic conceptual errors!"
      ],
      11: [ // Hat
        "No illusion can hide your lack of preparation!",
        "A pathetic trick that fails the board exam!",
        "My Arcane seal stands unbroken against such answers."
      ],
      12: [ // Black Archive
        "The Ancient Fracture consumes your failed logic!",
        "Total cognitive collapse. The darkness laughs at you!",
        "You are far from ready for the final synthesis."
      ]
    }
  }
}

/**
 * Returns a randomized wrong-answer speech bubble line for the current enemy.
 */
export function getRandomWrongSpeech(chapterId = 1, isBoss = false) {
  const d = battleAssets.dialogues
  if (isBoss && d.bossWrong && d.bossWrong[chapterId]?.length) {
    const list = d.bossWrong[chapterId]
    return list[Math.floor(Math.random() * list.length)]
  }
  const general = d.generalWrong
  return general[Math.floor(Math.random() * general.length)]
}

export default battleAssets
