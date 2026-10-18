/**
 * StudyQuest — Final Boss Post-Defeat Cutscene Script & Asset Configuration
 * Authoritative source of post-battle dialogue, emotional pacing, scene order, and narrative continuity.
 * Strictly adheres to StudyQuest_Final_Boss_Cutscene_Script.docx
 */

import { resolveAsset } from '../services/bossMapping'

export const CUTSCENE_ASSETS = {
  writerOrig: 'assets/FINALBOSS BACKSTORY/WriterOrig.png',
  scene1: {
    dungeonBg: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/BACKGROUND - FINAL BOSS DUNGEON.png',
    part1Fallback: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART1.png',
    part2: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART2.png',
    part3: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART3.png',
    part4: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART4.png',
    part5: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART5.png',
    part6: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART6.png',
    part7: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/1ST_SCENE_PART7.png',
    blackscreen: 'assets/FINALBOSS BACKSTORY/CUT SCENES/1ST SCENE/blackscreen.png'
  },
  scene2: {
    part1: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART1.png',
    part2: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART2.png',
    part3: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART3.png',
    part4: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART4.png',
    part5: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART5.png',
    part6: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART6.png',
    part7: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART7.png',
    part8: 'assets/FINALBOSS BACKSTORY/CUT SCENES/2ND SCENE/2ND_SCENE_PART8.png'
  }
}

/**
 * Preload all cutscene assets into browser cache to guarantee zero stutter
 */
export async function preloadCutsceneAssets() {
  const allPaths = [
    CUTSCENE_ASSETS.writerOrig,
    CUTSCENE_ASSETS.scene1.dungeonBg,
    CUTSCENE_ASSETS.scene1.part1Fallback,
    CUTSCENE_ASSETS.scene1.part2,
    CUTSCENE_ASSETS.scene1.part3,
    CUTSCENE_ASSETS.scene1.part4,
    CUTSCENE_ASSETS.scene1.part5,
    CUTSCENE_ASSETS.scene1.part6,
    CUTSCENE_ASSETS.scene1.part7,
    CUTSCENE_ASSETS.scene1.blackscreen,
    CUTSCENE_ASSETS.scene2.part1,
    CUTSCENE_ASSETS.scene2.part2,
    CUTSCENE_ASSETS.scene2.part3,
    CUTSCENE_ASSETS.scene2.part4,
    CUTSCENE_ASSETS.scene2.part5,
    CUTSCENE_ASSETS.scene2.part6,
    CUTSCENE_ASSETS.scene2.part7,
    CUTSCENE_ASSETS.scene2.part8
  ]

  const results = await Promise.all(
    allPaths.map(p => {
      return new Promise(resolve => {
        const resolved = resolveAsset(p)
        if (!resolved) return resolve({ path: p, ok: false })
        const img = new Image()
        img.onload = () => resolve({ path: p, ok: true })
        img.onerror = () => {
          console.warn(`[CutscenePreload] Asset failed to load: ${resolved}`)
          resolve({ path: p, ok: false })
        }
        img.src = resolved
      })
    })
  )

  const failed = results.filter(r => !r.ok)
  if (failed.length > 0) {
    console.warn(`[CutscenePreload] ${failed.length} assets failed preload:`, failed)
  }
  return results
}

/**
 * Explicit, structured scene & dialogue script sequence
 */
export const FINAL_BOSS_CUTSCENE_SEQUENCE = [
  // ── INITIAL REVEAL: ORIGINAL WRITER ARTWORK ──────────────────
  {
    id: 'initial_reveal',
    section: 'INITIAL_REVEAL',
    actTitle: 'ORIGIN OF THE CREATOR',
    sceneImage: CUTSCENE_ASSETS.scene1.dungeonBg,
    characterOverlay: CUTSCENE_ASSETS.writerOrig,
    lines: [
      {
        speaker: '',
        text: 'The Creator falls silent. The chamber stops shaking, loose pages settle upon the stones, and the original form of the author stands unveiled.'
      }
    ]
  },

  // ── SCENE SEQUENCE 1: AFTER THE CREATOR FALLS ────────────────
  // 1. FINAL BOSS DUNGEON.PNG
  {
    id: 'scene1_part1',
    section: 'SECTION_A',
    actTitle: 'AFTER THE CREATOR FALLS',
    sceneImage: CUTSCENE_ASSETS.scene1.dungeonBg,
    fallbackImage: CUTSCENE_ASSETS.scene1.part1Fallback,
    lines: [
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“It\'s over...”'
      },
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“But what was that thing?”'
      }
    ]
  },

  // 2. 1ST_SCENE_PART2
  {
    id: 'scene1_part2',
    section: 'SECTION_A',
    actTitle: 'AN UNEXPECTED ARRIVAL',
    sceneImage: CUTSCENE_ASSETS.scene1.part2,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Wanderer',
        text: '“That was never all of her.”'
      },
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“Merchant? What are you doing here?”'
      }
    ]
  },

  // 3. 1ST_SCENE_PART3
  {
    id: 'scene1_part3',
    section: 'SECTION_A',
    actTitle: 'APPROACHING THE OBSIDIAN ARCHIVE',
    sceneImage: CUTSCENE_ASSETS.scene1.part3,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Wanderer',
        text: '“I came because you were finally close enough to learn the truth.”'
      },
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“You know what that creature was?”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Wanderer',
        text: '“I do.”'
      }
    ]
  },

  // 4. 1ST_SCENE_PART4 — THE MERCHANT REVEALS HIS IDENTITY
  {
    id: 'scene1_part4',
    section: 'SECTION_B',
    actTitle: 'THE FATHER\'S REVELATION',
    sceneImage: CUTSCENE_ASSETS.scene1.part4,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“The girl who created this world... was my daughter.”'
      },
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“Your daughter?”'
      }
    ]
  },

  // 5. 1ST_SCENE_PART5
  {
    id: 'scene1_part5',
    section: 'SECTION_B',
    actTitle: 'MINDREALM\'S INCEPTION',
    sceneImage: CUTSCENE_ASSETS.scene1.part5,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“She loved to write. She could spend hours building worlds, inventing people, and imagining how their stories might end.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“Mindrealm began as a place where she could explore the things she couldn\'t easily put into words.”'
      }
    ]
  },

  // 6. 1ST_SCENE_PART6
  {
    id: 'scene1_part6',
    section: 'SECTION_B',
    actTitle: 'THE DEMAND FOR PERFECTION',
    sceneImage: CUTSCENE_ASSETS.scene1.part6,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“In her world, she could change a sentence. Rewrite a scene. Give a character another chance.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“But little by little, she began to demand that everything be right. Every choice. Every page. Every version of herself.”'
      }
    ]
  },

  // 7. 1ST_SCENE_PART7
  {
    id: 'scene1_part7',
    section: 'SECTION_B',
    actTitle: 'THE SHADOW AND THE WRITER',
    sceneImage: CUTSCENE_ASSETS.scene1.part7,
    lines: [
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“And The Creator was...”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“The part of her that believed mistakes made her worthless. The part that feared uncertainty and tried to control everything.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“But that shadow was not all she was.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“She was more than her fears. More than the world she created. More than the thing you just fought.”'
      }
    ]
  },

  // 8. FULL BLACK SCREEN TRANSITION (MANDATORY PAUSE)
  {
    id: 'scene1_blackscreen',
    section: 'INTERMISSION',
    actTitle: 'SILENCE IN THE ARCHIVE',
    sceneImage: CUTSCENE_ASSETS.scene1.blackscreen,
    isBlackscreen: true,
    lines: [
      {
        speaker: '',
        text: 'A profound stillness settles over the Black Archive. The ink rests, and the echoes of her memory begin to breathe...'
      }
    ]
  },

  // ── SCENE SEQUENCE 2: THE DAUGHTER'S STORY ───────────────────
  // 1. 2ND_SCENE_PART1 — ROOM FOR MISTAKES
  {
    id: 'scene2_part1',
    section: 'SECTION_C',
    actTitle: 'MEMORIES OF THE DAUGHTER',
    sceneImage: CUTSCENE_ASSETS.scene2.part1,
    lines: [
      {
        speaker: 'DAUGHTER',
        role: 'The Author',
        text: '“What if I made a world where nobody had to get everything right the first time?”'
      },
      {
        speaker: 'FATHER',
        role: 'Raven',
        text: '“Then I hope you leave room for them to make mistakes.”'
      }
    ]
  },

  // 2. 2ND_SCENE_PART2
  {
    id: 'scene2_part2',
    section: 'SECTION_C',
    actTitle: 'THE LIVING STORY',
    sceneImage: CUTSCENE_ASSETS.scene2.part2,
    lines: [
      {
        speaker: 'DAUGHTER',
        role: 'The Author',
        text: '“Of course. That\'s how the story gets interesting.”'
      }
    ]
  },

  // 3. 2ND_SCENE_PART3
  {
    id: 'scene2_part3',
    section: 'SECTION_C',
    actTitle: 'CHARACTERS WHO MATTER',
    sceneImage: CUTSCENE_ASSETS.scene2.part3,
    lines: [
      {
        speaker: 'DAUGHTER',
        role: 'The Author',
        text: '“I want this place to feel real. Even the characters who get things wrong should matter.”'
      }
    ]
  },

  // 4. 2ND_SCENE_PART4 — UNFINISHED PAGES
  {
    id: 'scene2_part4',
    section: 'SECTION_C',
    actTitle: 'THE ENDLESS REVISIONS',
    sceneImage: CUTSCENE_ASSETS.scene2.part4,
    lines: [
      {
        speaker: 'DAUGHTER',
        role: 'The Author',
        text: '“It\'s still not right.”'
      },
      {
        speaker: 'DAUGHTER',
        role: 'The Author',
        text: '“If I can just fix this part... maybe the rest will make sense.”'
      }
    ]
  },

  // 5. 2ND_SCENE_PART5 — THE FATHER'S REGRET
  {
    id: 'scene2_part5',
    section: 'SECTION_C',
    actTitle: 'WHAT WAS CARRIED',
    sceneImage: CUTSCENE_ASSETS.scene2.part5,
    lines: [
      {
        speaker: 'FATHER',
        role: 'Raven',
        text: '“I wish I had understood how much she was carrying. I wish I had known what to ask, and how to help.”'
      }
    ]
  },

  // 6. 2ND_SCENE_PART6 — THE FATHER'S LOSS (RESPECTFUL DISCLOSURE & GRIEF)
  {
    id: 'scene2_part6',
    section: 'SECTION_D',
    actTitle: 'THE FATHER\'S GRIEF',
    sceneImage: CUTSCENE_ASSETS.scene2.part6,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“There is one part of her story I cannot change.”',
        pauseAfterMs: 800
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“My daughter died by suicide.”',
        pauseAfterMs: 1400,
        isQuietMoment: true
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“I have spent a long time wondering what I missed, what I should have noticed, and what I wish I had said.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“I may never have every answer.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“But I know her life was more than the pain she carried. She was curious. She was creative. She made people laugh. She imagined worlds that mattered to her.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“The world you walked through came from her, but it cannot tell you everything about who she was.”'
      },
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“I\'m sorry.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“So am I.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“Defeating that shadow did not save her. It cannot undo what happened.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“But perhaps you can leave this place understanding that no one is only their worst moment, their fear, or their unfinished work.”'
      }
    ]
  },

  // 7. 2ND_SCENE_PART7 — THE UNFINISHED PAGE (THE END -> OR PERHAPS... THE BEGINNING)
  {
    id: 'scene2_part7',
    section: 'SECTION_E',
    actTitle: 'THE UNFINISHED PAGE',
    sceneImage: CUTSCENE_ASSETS.scene2.part7,
    hasUnfinishedOverlay: true,
    lines: [
      {
        speaker: 'PLAYER',
        role: 'Mindkeeper',
        text: '“What happens to Mindrealm now?”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“I don\'t know.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“Maybe it doesn\'t need another ending right now.”'
      }
    ]
  },

  // 8. 2ND_SCENE_PART8 — STORIES CONTINUED
  {
    id: 'scene2_part8',
    section: 'SECTION_E',
    actTitle: 'A STORY REMEMBERED',
    sceneImage: CUTSCENE_ASSETS.scene2.part8,
    lines: [
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“Some stories end unfinished.”'
      },
      {
        speaker: 'MERCHANT',
        role: 'The Father',
        text: '“Others continue in the people who remember them.”'
      }
    ]
  }
]
