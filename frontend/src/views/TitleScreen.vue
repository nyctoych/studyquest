<template>
  <div class="title-screen">
    <!-- Animated background -->
    <div class="title-bg">
      <div class="title-hero-img"></div>
      <div class="title-vignette"></div>
      <!-- Boss silhouettes in background -->
      <div class="title-boss title-boss-left">
        <img src="/assets/bosses/skull/happy.png" class="pixel-art boss-silhouette" alt="" />
      </div>
      <div class="title-boss title-boss-right">
        <img src="/assets/bosses/shadows/happy.png" class="pixel-art boss-silhouette" alt="" />
      </div>
    </div>

    <!-- Floating blood & void embers -->
    <div class="title-particles" aria-hidden="true">
      <div v-for="i in 25" :key="i" class="t-particle" :style="tParticleStyle(i)"></div>
    </div>

    <!-- Main content -->
    <div class="title-content animate-scale-up">
      <!-- Logo header -->
      <div class="title-logo">
        <div class="title-psi-badge">
          <span class="title-psi">Ψ</span>
        </div>
        <div>
          <h1 class="title-name">STUDYQUEST</h1>
          <p class="title-tagline">FORGE YOUR SOUL IN THE HALLS OF KNOWLEDGE</p>
        </div>
      </div>

      <!-- Gothic separator -->
      <div class="gothic-sep gothic-sep-crimson" style="width: 80%; max-width: 320px;"></div>

      <!-- Buttons -->
      <div class="title-buttons">
        <router-link to="/register" class="btn btn-gothic-crimson btn-lg title-btn-primary">
          ⚔️ ENTER THE SANCTUM
        </router-link>
        <router-link to="/login" class="btn btn-gothic-dark btn-lg title-btn">
          🔑 SCHOLAR LOGIN
        </router-link>
        <button class="btn btn-gothic-stone title-btn" @click="showHowToPlay = true">
          📜 GRIMOIRE OF TRIALS
        </button>
        <button class="btn btn-gothic-stone title-btn" @click="showCredits = true">
          🕯️ SANCTUM CREDITS
        </button>
      </div>

      <!-- Audio controls -->
      <div class="title-audio">
        <button class="audio-btn" :class="{ active: game.audioEnabled }" @click="game.setAudio(!game.audioEnabled)" :title="game.audioEnabled ? 'Mute sound FX' : 'Enable sound FX'">
          {{ game.audioEnabled ? '🔊' : '🔇' }}
        </button>
        <button class="audio-btn music-btn" :class="{ active: game.musicEnabled }" @click="game.setMusic(!game.musicEnabled)" :title="game.musicEnabled ? 'Mute background music' : 'Play background music'">
          {{ game.musicEnabled ? '🎵' : '🔇' }}
        </button>
      </div>
    </div>

    <!-- How To Play Modal (Grimoire) -->
    <Teleport to="body">
      <div v-if="showHowToPlay" class="modal-backdrop" @click.self="showHowToPlay = false">
        <div class="modal-box gothic-panel animate-scale-up">
          <button class="modal-close" @click="showHowToPlay = false">✕</button>
          <h2 class="modal-title">⚔️ GRIMOIRE OF TRIALS</h2>
          <div class="howto-steps">
            <div v-for="step in howToSteps" :key="step.icon" class="howto-step">
              <span class="howto-icon">{{ step.icon }}</span>
              <div>
                <strong>{{ step.title }}</strong>
                <p>{{ step.desc }}</p>
              </div>
            </div>
          </div>
          <button class="btn btn-gothic-crimson w-full" @click="showHowToPlay = false">I UNDERSTAND →</button>
        </div>
      </div>
    </Teleport>

    <!-- Credits Modal -->
    <Teleport to="body">
      <div v-if="showCredits" class="modal-backdrop" @click.self="showCredits = false">
        <div class="modal-box gothic-panel animate-scale-up">
          <button class="modal-close" @click="showCredits = false">✕</button>
          <h2 class="modal-title">🕯️ SANCTUM CREDITS</h2>
          <div class="credits-body">
            <p><strong style="color: var(--bone-white); font-family: var(--font-pixel);">STUDYQUEST</strong></p>
            <p>A Dark Fantasy Psychology Learning RPG designed for undergraduate board exam preparation.</p>
            <br />
            <p>Architecture: Vue.js · PHP · MySQL · Phaser · Pinia</p>
            <p>Theme: Gothic · Dark Fantasy · Pixel Retro RPG</p>
            <p>Sound & FX: Web Audio API & GSAP</p>
          </div>
          <button class="btn btn-gothic-stone w-full" @click="showCredits = false">CLOSE</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useGameStore } from '../stores/game'

const game = useGameStore()
const showHowToPlay = ref(false)
const showCredits = ref(false)

const howToSteps = [
  { icon: '🗺️', title: 'TRAVERSE THE REALMS', desc: 'Journey across the World Hub to unlock chapter dungeons and explore ancient study domains.' },
  { icon: '👹', title: 'CONQUER CHAPTER BOSSES', desc: 'Face sentient psychological horrors in 10-episode chapter climaxes to unlock forbidden mastery.' },
  { icon: '⚔️', title: 'ENGAGE IN SCHOLAR DUELS', desc: 'Test your retention against rival scholars in real-time turn-based multiplayer duels.' },
  { icon: '💎', title: 'COLLECT RELICS & COSMETICS', desc: 'Earn ancient gold, equip gothic pixel frames, unlock banners, and tend your mind sanctum.' }
]

function tParticleStyle(i) {
  const left = (i * 4) % 100
  const duration = 4 + (i % 6)
  const delay = (i * 0.3) % 4
  const size = 3 + (i % 4)
  const isCrimson = i % 2 === 0
  return {
    left: `${left}%`,
    bottom: '-10px',
    width: `${size}px`,
    height: `${size}px`,
    backgroundColor: isCrimson ? 'rgba(185, 28, 28, 0.7)' : 'rgba(230, 200, 69, 0.5)',
    boxShadow: isCrimson ? '0 0 6px #8b1a1a' : '0 0 6px #b59410',
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`
  }
}
</script>

<style scoped>
.title-screen {
  min-height: 100vh;
  width: 100%;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--gothic-void, #050608);
}

/* Background */
.title-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 0;
}
.title-hero-img {
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(ellipse at 50% 30%, rgba(139, 26, 26, 0.2) 0%, transparent 60%),
    radial-gradient(ellipse at 80% 80%, rgba(58, 20, 75, 0.25) 0%, transparent 50%),
    #050608;
}
.title-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 30%, rgba(5, 6, 8, 0.95) 90%);
}

.title-boss {
  position: absolute;
  bottom: -20px;
  pointer-events: none;
  opacity: 0.12;
}
.title-boss-left {
  left: 2%;
  transform: scaleX(-1);
}
.title-boss-right {
  right: 2%;
}
.boss-silhouette {
  width: 300px;
  height: auto;
  filter: brightness(0.2) saturate(0.2);
}

/* Embers / particles */
.title-particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
  z-index: 1;
}
.t-particle {
  position: absolute;
  animation: emberRise linear infinite;
}
@keyframes emberRise {
  0% { transform: translateY(0) scale(1); opacity: 0; }
  20% { opacity: 0.8; }
  80% { opacity: 0.8; }
  100% { transform: translateY(-100vh) scale(0.4); opacity: 0; }
}

/* Content */
.title-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.75rem;
  text-align: center;
  padding: 2.5rem 1.5rem;
  max-width: 520px;
  width: 100%;
  background: rgba(12, 13, 18, 0.75);
  border: 1px solid var(--color-stone-600, #3a3b4c);
  outline: 2px solid #050608;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.9), 0 0 30px rgba(139, 26, 26, 0.2);
}

/* Logo */
.title-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.9rem;
}
.title-psi-badge {
  width: 64px;
  height: 64px;
  background: #110708;
  border: 2px solid var(--color-crimson, #8b1a1a);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 20px rgba(185, 28, 28, 0.5);
  animation: psiThrob 2.8s ease-in-out infinite alternate;
}
@keyframes psiThrob {
  0% { transform: scale(1); box-shadow: 0 0 15px rgba(185, 28, 28, 0.4); }
  100% { transform: scale(1.05); box-shadow: 0 0 30px rgba(185, 28, 28, 0.8); }
}
.title-psi {
  font-size: 2.2rem;
  font-family: serif;
  color: var(--color-bone, #e2d9cc);
}
.title-name {
  font-family: var(--font-pixel, monospace);
  font-size: clamp(1.6rem, 5vw, 2.4rem);
  font-weight: 700;
  letter-spacing: 0.12em;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
  text-shadow: 0 0 15px rgba(185, 28, 28, 0.6);
}
.title-tagline {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone-dark, #8c8273);
  letter-spacing: 0.08em;
  margin-top: 0.4rem;
}

/* Buttons */
.title-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  width: 100%;
  max-width: 320px;
}
.title-btn-primary {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  padding: 0.85rem 1.5rem;
}
.title-btn {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  padding: 0.7rem 1.2rem;
}

/* Audio */
.title-audio {
  display: flex;
  gap: 0.5rem;
  position: absolute;
  top: 1.2rem;
  right: 1.2rem;
}
.audio-btn {
  width: 36px;
  height: 36px;
  background: #0f1017;
  border: 1px solid #3a3b4c;
  cursor: pointer;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-bone, #e2d9cc);
  transition: all 0.15s ease;
}
.audio-btn:hover, .audio-btn.active {
  background: var(--color-crimson, #8b1a1a);
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #fff;
}
.audio-btn.music-btn.active {
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.7);
  animation: pulseMusic 2s infinite ease-in-out;
}
@keyframes pulseMusic {
  0%, 100% {
    box-shadow: 0 0 8px rgba(185, 28, 28, 0.4);
    transform: scale(1);
  }
  50% {
    box-shadow: 0 0 16px rgba(239, 68, 68, 0.8);
    transform: scale(1.05);
  }
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(3, 4, 6, 0.85);
  z-index: 9998;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}
.modal-box {
  width: 100%;
  max-width: 480px;
  padding: 1.8rem;
  position: relative;
  max-height: 85vh;
  overflow-y: auto;
  background: #0f1017;
  border: 2px solid #3a3b4c;
  outline: 2px solid #050608;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95);
}
.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: #181924;
  border: 1px solid #3a3b4c;
  color: var(--color-bone-dark, #8c8273);
  width: 28px;
  height: 28px;
  cursor: pointer;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.modal-close:hover {
  background: var(--color-crimson, #8b1a1a);
  color: #fff;
}
.modal-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.85rem;
  color: var(--color-bone, #e2d9cc);
  margin-bottom: 1.25rem;
}

/* How to play */
.howto-steps {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-bottom: 1.5rem;
}
.howto-step {
  display: flex;
  gap: 0.85rem;
  align-items: flex-start;
  text-align: left;
  background: #151620;
  border: 1px solid #282a3a;
  padding: 0.6rem 0.8rem;
}
.howto-icon {
  font-size: 1.2rem;
  flex-shrink: 0;
}
.howto-step strong {
  display: block;
  margin-bottom: 0.2rem;
  color: var(--color-gold-ancient, #e6c845);
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
}
.howto-step p {
  font-size: 0.75rem;
  color: var(--color-bone-dark, #8c8273);
  font-family: var(--font-body, serif);
  margin: 0;
  line-height: 1.35;
}

/* Credits */
.credits-body {
  text-align: center;
  color: var(--color-bone-dark, #8c8273);
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.w-full { width: 100%; }

@media (max-width: 640px) {
  .title-audio { top: 0.8rem; right: 0.8rem; }
  .title-content { gap: 1.25rem; padding: 1.8rem 1rem; }
}
</style>
