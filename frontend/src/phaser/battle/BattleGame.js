import Phaser from 'phaser'
import BattleScene from './BattleScene'

export function createBattleGame(containerId, initialData) {
  const config = {
    type: Phaser.AUTO,
    parent: containerId,
    width: 800,
    height: 380,
    transparent: true,
    backgroundColor: 'rgba(0,0,0,0)',
    scale: {
      mode: Phaser.Scale.FIT,
      autoCenter: Phaser.Scale.CENTER_BOTH,
    },
    audio: {
      // Use Web Audio context only if it's available and already unlocked
      // This prevents the "Cannot read properties of undefined (reading 'startTime')"
      // error in Phaser 4's performance reporter when Web Audio is not ready
      noAudio: false,
      disableWebAudio: false,
    },
    physics: {
      default: 'arcade',
      arcade: {
        gravity: { y: 0 },
        debug: false,
      },
    },
    scene: [BattleScene],
    // Suppress Phaser's verbose banner in production
    banner: false,
  }

  let game = null
  try {
    game = new Phaser.Game(config)
    game.scene.start('BattleScene', initialData)
  } catch (e) {
    console.warn('[BattleGame] Phaser initialization warning:', e.message)
  }

  return game
}

export default createBattleGame
