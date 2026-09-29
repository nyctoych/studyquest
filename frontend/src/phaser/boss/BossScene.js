import Phaser from 'phaser'
import { getBossForChapter, resolveAsset } from '../../services/bossMapping'

export default class BossScene extends Phaser.Scene {
  constructor() {
    super('BossScene')
    this.bossSprite = null
    this.playerSprite = null
    this.bossData = null
    this.particles = null
    this.emitter = null
    this.eventsEmitter = null
  }

  init(data) {
    const raw = data.boss || { name: 'Boss', max_hp: 100 }
    this.eventsEmitter = data.eventsEmitter || null
    const meta = getBossForChapter(raw.id || raw.chapterId || raw.chapter_id || raw.domain_id || 1)
    this.bossData = {
      ...meta,
      ...raw,
      name: raw.name || meta.name,
      spriteUrl: resolveAsset(raw.sprite || meta.sprite || meta.happy || meta.idle)
    }
  }

  preload() {
    if (this.bossData?.spriteUrl) {
      this.load.image(`boss_sprite_${this.bossData.id}`, this.bossData.spriteUrl)
    }
  }

  create() {
    const { width, height } = this.scale

    // Arena Background Gradient / Stars
    this.createArenaBackground(width, height)

    // Boss Platform
    const bossPlatform = this.add.ellipse(width * 0.75, height * 0.65, 200, 60, 0x1e1b4b, 0.6)
    bossPlatform.setStrokeStyle(2, 0x6366f1, 0.8)

    // Player Platform
    const playerPlatform = this.add.ellipse(width * 0.25, height * 0.7, 160, 50, 0x064e3b, 0.6)
    playerPlatform.setStrokeStyle(2, 0x10b981, 0.8)

    // Boss Character Graphic
    this.createBossGraphic(width * 0.75, height * 0.45)

    // Player Character Graphic
    this.createPlayerGraphic(width * 0.25, height * 0.52)

    // Floating idle animation for boss
    this.tweens.add({
      targets: this.bossContainer,
      y: height * 0.45 - 15,
      duration: 2000,
      yoyo: true,
      repeat: -1,
      ease: 'Sine.easeInOut'
    })

    // Floating idle animation for player
    this.tweens.add({
      targets: this.playerContainer,
      y: height * 0.52 - 8,
      duration: 1600,
      yoyo: true,
      repeat: -1,
      ease: 'Sine.easeInOut'
    })

    // Spell particles manager
    this.createParticleEffects()
  }

  createArenaBackground(w, h) {
    // Ambient dark glowing dungeon/void arena
    const bg = this.add.graphics()
    bg.fillGradientStyle(0x0f172a, 0x0f172a, 0x1e1b4b, 0x090d16, 1)
    bg.fillRect(0, 0, w, h)

    // Floating ambient magical sparks
    for (let i = 0; i < 35; i++) {
      const x = Phaser.Math.Between(0, w)
      const y = Phaser.Math.Between(0, h)
      const r = Phaser.Math.FloatBetween(1, 2.5)
      const alpha = Phaser.Math.FloatBetween(0.2, 0.7)
      const spark = this.add.circle(x, y, r, 0xa5b4fc, alpha)

      this.tweens.add({
        targets: spark,
        y: y - Phaser.Math.Between(20, 60),
        alpha: 0,
        duration: Phaser.Math.Between(2500, 5000),
        yoyo: true,
        repeat: -1,
        ease: 'Sine.easeInOut'
      })
    }
  }

  createBossGraphic(x, y) {
    this.bossContainer = this.add.container(x, y)

    // Boss aura glow with boss specific accent color
    const rawAccent = this.bossData?.accentColor || '#ec4899'
    const accentHex = parseInt(rawAccent.replace('#', '0x'), 16) || 0xec4899

    const aura = this.add.circle(0, 0, 90, accentHex, 0.25)
    this.tweens.add({
      targets: aura,
      scale: 1.25,
      alpha: 0.05,
      duration: 1500,
      yoyo: true,
      repeat: -1
    })

    const textureKey = `boss_sprite_${this.bossData?.id}`

    // Name text
    const nameText = this.add.text(0, 85, this.bossData.name || 'Boss Entity', {
      fontFamily: 'Outfit, sans-serif',
      fontSize: '16px',
      fontStyle: 'bold',
      color: '#f8fafc',
      stroke: '#000000',
      strokeThickness: 3
    }).setOrigin(0.5)

    if (this.textures.exists(textureKey)) {
      const sprite = this.add.image(0, -10, textureKey)
      const maxDim = Math.max(sprite.width, sprite.height)
      if (maxDim > 180) {
        sprite.setScale(160 / maxDim)
      } else if (maxDim < 80) {
        sprite.setScale(120 / maxDim)
      }
      this.bossSprite = sprite
      this.bossContainer.add([aura, sprite, nameText])
    } else {
      // Dynamic fallback load or procedural render
      const bossBody = this.add.graphics()
      bossBody.fillStyle(accentHex, 1)
      bossBody.fillCircle(0, 0, 60)
      bossBody.fillStyle(0x6366f1, 1)
      bossBody.fillCircle(0, 0, 48)

      const eyeL = this.add.ellipse(-18, -8, 14, 8, 0xffffff, 1)
      const eyeR = this.add.ellipse(18, -8, 14, 8, 0xffffff, 1)
      const pupilL = this.add.circle(-18, -8, 4, 0xef4444, 1)
      const pupilR = this.add.circle(18, -8, 4, 0xef4444, 1)

      const crown = this.add.graphics()
      crown.fillStyle(0xf59e0b, 1)
      crown.fillTriangle(-35, -35, 0, -75, 35, -35)

      this.bossContainer.add([aura, bossBody, crown, eyeL, eyeR, pupilL, pupilR, nameText])

      // Try dynamic texture load
      if (this.bossData?.spriteUrl) {
        this.load.image(textureKey, this.bossData.spriteUrl)
        this.load.once('complete', () => {
          if (this.bossContainer) {
            bossBody.destroy()
            crown.destroy()
            eyeL.destroy()
            eyeR.destroy()
            pupilL.destroy()
            pupilR.destroy()
            const sprite = this.add.image(0, -10, textureKey)
            const maxDim = Math.max(sprite.width, sprite.height)
            if (maxDim > 180) {
              sprite.setScale(160 / maxDim)
            } else if (maxDim < 80) {
              sprite.setScale(120 / maxDim)
            }
            this.bossSprite = sprite
            this.bossContainer.add(sprite)
            this.bossContainer.bringToTop(nameText)
          }
        })
        this.load.start()
      }
    }
  }

  createPlayerGraphic(x, y) {
    this.playerContainer = this.add.container(x, y)

    // Player aura
    const playerAura = this.add.circle(0, 0, 55, 0x10b981, 0.2)

    // Player Body (Mage / Hero Orb Shield)
    const body = this.add.graphics()
    body.fillStyle(0x059669, 1)
    body.fillCircle(0, 0, 40)
    body.fillStyle(0x10b981, 1)
    body.fillCircle(0, 0, 32)

    // Wizard hat / crest
    const hat = this.add.graphics()
    hat.fillStyle(0x3b82f6, 1)
    hat.fillTriangle(-25, -20, 0, -55, 25, -20)

    // Hero Face
    const eyeL = this.add.circle(-10, -5, 4, 0xffffff, 1)
    const eyeR = this.add.circle(10, -5, 4, 0xffffff, 1)

    const label = this.add.text(0, 52, 'Hero', {
      fontFamily: 'Outfit, sans-serif',
      fontSize: '14px',
      fontStyle: 'bold',
      color: '#34d399',
      stroke: '#000000',
      strokeThickness: 3
    }).setOrigin(0.5)

    this.playerContainer.add([playerAura, body, hat, eyeL, eyeR, label])
  }

  createParticleEffects() {
    // Generate simple texture for spell particles
    const g = this.make.graphics({ x: 0, y: 0, add: false })
    g.fillStyle(0xffffff, 1)
    g.fillCircle(8, 8, 8)
    g.generateTexture('spark_particle', 16, 16)
  }

  // Animation: Player attacks Boss with magic spell
  playPlayerAttack(damage, isCrit = false) {
    const { width, height } = this.scale

    // Spawn spell projectile from Player to Boss
    const spell = this.add.circle(this.playerContainer.x + 20, this.playerContainer.y - 10, 16, isCrit ? 0xf59e0b : 0x06b6d4, 1)
    spell.setStrokeStyle(3, 0xffffff, 1)

    this.tweens.add({
      targets: spell,
      x: this.bossContainer.x,
      y: this.bossContainer.y,
      duration: 400,
      ease: 'Power2',
      onComplete: () => {
        spell.destroy()
        this.playBossHit(damage, isCrit)
      }
    })
  }

  // Boss hit reaction & damage number
  playBossHit(damage, isCrit) {
    // Screen shake
    this.cameras.main.shake(180, isCrit ? 0.015 : 0.008)

    // Boss flash & shake
    this.tweens.add({
      targets: this.bossContainer,
      x: this.bossContainer.x + 15,
      duration: 60,
      yoyo: true,
      repeat: 3,
      onStart: () => {
        this.bossContainer.setAlpha(0.6)
      },
      onComplete: () => {
        this.bossContainer.setAlpha(1)
      }
    })

    // Floating damage text
    const dmgText = this.add.text(
      this.bossContainer.x + Phaser.Math.Between(-20, 20),
      this.bossContainer.y - 40,
      `-${damage}${isCrit ? ' CRIT!' : ''}`,
      {
        fontFamily: 'Outfit, sans-serif',
        fontSize: isCrit ? '26px' : '20px',
        fontStyle: '800',
        color: isCrit ? '#fbbf24' : '#ef4444',
        stroke: '#000000',
        strokeThickness: 4
      }
    ).setOrigin(0.5)

    this.tweens.add({
      targets: dmgText,
      y: dmgText.y - 50,
      alpha: 0,
      duration: 900,
      ease: 'Power1',
      onComplete: () => dmgText.destroy()
    })
  }

  // Animation: Boss attacks Player
  playBossAttack(damage) {
    // Boss lunges forward
    this.tweens.add({
      targets: this.bossContainer,
      x: this.bossContainer.x - 40,
      duration: 150,
      yoyo: true,
      ease: 'Power1',
      onYoyo: () => {
        // Dark boss fireball
        const darkBall = this.add.circle(this.bossContainer.x - 30, this.bossContainer.y, 20, 0xef4444, 0.9)
        darkBall.setStrokeStyle(3, 0x7f1d1d, 1)

        this.tweens.add({
          targets: darkBall,
          x: this.playerContainer.x,
          y: this.playerContainer.y,
          duration: 350,
          onComplete: () => {
            darkBall.destroy()
            this.playPlayerHit(damage)
          }
        })
      }
    })
  }

  // Player hit reaction
  playPlayerHit(damage) {
    this.cameras.main.shake(150, 0.01)

    this.tweens.add({
      targets: this.playerContainer,
      x: this.playerContainer.x - 12,
      duration: 60,
      yoyo: true,
      repeat: 3,
      onStart: () => this.playerContainer.setAlpha(0.6),
      onComplete: () => this.playerContainer.setAlpha(1)
    })

    const dmgText = this.add.text(
      this.playerContainer.x,
      this.playerContainer.y - 35,
      `-${damage}`,
      {
        fontFamily: 'Outfit, sans-serif',
        fontSize: '20px',
        fontStyle: '800',
        color: '#ef4444',
        stroke: '#000000',
        strokeThickness: 4
      }
    ).setOrigin(0.5)

    this.tweens.add({
      targets: dmgText,
      y: dmgText.y - 45,
      alpha: 0,
      duration: 800,
      ease: 'Power1',
      onComplete: () => dmgText.destroy()
    })
  }

  // Boss defeat explosion
  playBossDefeat() {
    this.tweens.add({
      targets: this.bossContainer,
      scaleX: 1.5,
      scaleY: 1.5,
      alpha: 0,
      duration: 1200,
      ease: 'Power2'
    })
  }
}
