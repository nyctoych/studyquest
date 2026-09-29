import Phaser from 'phaser'

export default class BattleScene extends Phaser.Scene {
  constructor() {
    super('BattleScene')
    this.playerContainer = null
    this.opponentContainer = null
    this.playerData = null
    this.opponentData = null
  }

  init(data) {
    this.playerData = data.self || { username: 'You', hp: 100 }
    this.opponentData = data.opponent || { username: 'Opponent', hp: 100 }
  }

  create() {
    const { width, height } = this.scale

    // Arena Background
    this.createArena(width, height)

    // Platforms
    const p1Platform = this.add.ellipse(width * 0.25, height * 0.72, 170, 54, 0x10b981, 0.3)
    p1Platform.setStrokeStyle(2, 0x10b981, 0.9)

    const p2Platform = this.add.ellipse(width * 0.75, height * 0.72, 170, 54, 0x6366f1, 0.3)
    p2Platform.setStrokeStyle(2, 0x6366f1, 0.9)

    // Characters
    this.createPlayerCharacter(width * 0.25, height * 0.52)
    this.createOpponentCharacter(width * 0.75, height * 0.52)

    // Idle Bobbing Tweens
    this.tweens.add({
      targets: this.playerContainer,
      y: height * 0.52 - 10,
      duration: 1800,
      yoyo: true,
      repeat: -1,
      ease: 'Sine.easeInOut',
    })

    this.tweens.add({
      targets: this.opponentContainer,
      y: height * 0.52 - 10,
      duration: 2000,
      yoyo: true,
      repeat: -1,
      ease: 'Sine.easeInOut',
    })
  }

  createArena(width, height) {
    // Stars / psychic aura particles
    for (let i = 0; i < 45; i++) {
      const x = Phaser.Math.Between(0, width)
      const y = Phaser.Math.Between(0, height)
      const r = Phaser.Math.Between(1, 3)
      const alpha = Phaser.Math.FloatBetween(0.2, 0.8)
      const star = this.add.circle(x, y, r, 0x818cf8, alpha)
      this.tweens.add({
        targets: star,
        alpha: { from: alpha, to: 0.1 },
        duration: Phaser.Math.Between(1000, 3000),
        yoyo: true,
        repeat: -1,
      })
    }
  }

  createPlayerCharacter(x, y) {
    this.playerContainer = this.add.container(x, y)

    // Glowing aura
    const aura = this.add.circle(0, 0, 48, 0x10b981, 0.25)
    aura.setStrokeStyle(2, 0x34d399, 0.8)

    // Character Base Sprite / Badge
    const body = this.add.circle(0, 0, 38, 0x064e3b)
    const icon = this.add.text(0, -2, '🧙‍♂️', { fontSize: '38px' }).setOrigin(0.5)

    // Name tag
    const tagBg = this.add.rectangle(0, 52, 110, 22, 0x0f172a, 0.8).setStrokeStyle(1, 0x10b981)
    const nameText = this.add.text(0, 52, this.playerData.username || 'You', {
      fontFamily: 'Inter, sans-serif',
      fontSize: '11px',
      color: '#34d399',
      fontStyle: 'bold',
    }).setOrigin(0.5)

    this.playerContainer.add([aura, body, icon, tagBg, nameText])
  }

  createOpponentCharacter(x, y) {
    this.opponentContainer = this.add.container(x, y)

    // Glowing aura
    const aura = this.add.circle(0, 0, 48, 0x6366f1, 0.25)
    aura.setStrokeStyle(2, 0x818cf8, 0.8)

    // Character Base Sprite / Badge
    const body = this.add.circle(0, 0, 38, 0x312e81)
    const icon = this.add.text(0, -2, '⚔️', { fontSize: '36px' }).setOrigin(0.5)

    // Name tag
    const tagBg = this.add.rectangle(0, 52, 110, 22, 0x0f172a, 0.8).setStrokeStyle(1, 0x6366f1)
    const nameText = this.add.text(0, 52, this.opponentData.username || 'Rival', {
      fontFamily: 'Inter, sans-serif',
      fontSize: '11px',
      color: '#a5b4fc',
      fontStyle: 'bold',
    }).setOrigin(0.5)

    this.opponentContainer.add([aura, body, icon, tagBg, nameText])
  }

  // Animation triggers from Vue
  playerAttack() {
    if (!this.playerContainer) return
    const originalX = this.playerContainer.x

    this.tweens.add({
      targets: this.playerContainer,
      x: originalX + 70,
      duration: 150,
      yoyo: true,
      ease: 'Back.easeOut',
      onYoyo: () => {
        this.castPsychicProjectile(this.playerContainer.x, this.playerContainer.y, this.opponentContainer.x, this.opponentContainer.y, 0x10b981)
      },
    })
  }

  opponentAttack() {
    if (!this.opponentContainer) return
    const originalX = this.opponentContainer.x

    this.tweens.add({
      targets: this.opponentContainer,
      x: originalX - 70,
      duration: 150,
      yoyo: true,
      ease: 'Back.easeOut',
      onYoyo: () => {
        this.castPsychicProjectile(this.opponentContainer.x, this.opponentContainer.y, this.playerContainer.x, this.playerContainer.y, 0xf43f5e)
      },
    })
  }

  castPsychicProjectile(fromX, fromY, toX, toY, color) {
    const orb = this.add.circle(fromX, fromY, 14, color, 0.9)
    orb.setStrokeStyle(2, 0xffffff, 1)

    this.tweens.add({
      targets: orb,
      x: toX,
      y: toY,
      duration: 300,
      ease: 'Cubic.easeIn',
      onComplete: () => {
        orb.destroy()
        this.showDamageBurst(toX, toY, color)
      },
    })
  }

  showDamageBurst(x, y, color) {
    const burst = this.add.circle(x, y, 40, color, 0.6)
    this.tweens.add({
      targets: burst,
      scale: 1.8,
      alpha: 0,
      duration: 350,
      onComplete: () => burst.destroy(),
    })
  }

  playerHurt(dmg = 10) {
    if (!this.playerContainer) return
    this.cameras.main.shake(150, 0.01)
    this.showDamageNumber(this.playerContainer.x, this.playerContainer.y - 40, `-${dmg}`, '#ef4444')
  }

  opponentHurt(dmg = 10) {
    if (!this.opponentContainer) return
    this.showDamageNumber(this.opponentContainer.x, this.opponentContainer.y - 40, `-${dmg}`, '#fbbf24')
  }

  showDamageNumber(x, y, text, color) {
    const dmgText = this.add.text(x, y, text, {
      fontFamily: 'Inter, sans-serif',
      fontSize: '24px',
      color,
      fontStyle: 'bold',
      stroke: '#000000',
      strokeThickness: 4,
    }).setOrigin(0.5)

    this.tweens.add({
      targets: dmgText,
      y: y - 40,
      alpha: 0,
      duration: 800,
      ease: 'Power2',
      onComplete: () => dmgText.destroy(),
    })
  }
}
