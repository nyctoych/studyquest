import Phaser from 'phaser'
import BossScene from './BossScene'

export function createBossGame(containerId, bossData) {
  const config = {
    type: Phaser.AUTO,
    parent: containerId,
    width: 800,
    height: 380,
    transparent: true,
    scale: {
      mode: Phaser.Scale.FIT,
      autoCenter: Phaser.Scale.CENTER_BOTH,
    },
    physics: {
      default: 'arcade',
      arcade: {
        gravity: { y: 0 },
        debug: false
      }
    },
    scene: [BossScene]
  }

  const game = new Phaser.Game(config)

  // Pass initial boss data into scene
  game.scene.start('BossScene', { boss: bossData })

  return game
}
