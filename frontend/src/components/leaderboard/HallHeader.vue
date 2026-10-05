<template>
  <header class="hall-header-root animate-slide-down">
    <!-- Top breadcrumb / navigation bar -->
    <div class="hall-top-bar">
      <router-link to="/world" class="hall-nav-back btn btn-ghost btn-sm">
        <span class="back-arrow">‹</span>
        <span>World Hub</span>
      </router-link>

      <div class="hall-archivist-badge">
        <span class="archivist-rune">❖</span>
        <span class="archivist-name">Mindrealm Archivist:</span>
        <span class="archivist-quote">"Names here are written by deeds, not promises."</span>
      </div>

      <router-link to="/multiplayer" class="hall-nav-arena btn btn-primary btn-sm">
        <PixelEmoji emoji="sword" size="xs" />
        <span>Enter Arena</span>
      </router-link>
    </div>

    <!-- Main Grand Monumental Title Crest -->
    <div class="hall-crest-monument">
      <!-- Ornamental Wings / Halo -->
      <div class="monument-aura"></div>

      <!-- Center Heraldic Icon Frame -->
      <div class="hall-crest-frame">
        <div class="crest-corner tl"></div>
        <div class="crest-corner tr"></div>
        <div class="crest-corner bl"></div>
        <div class="crest-corner br"></div>
        <div class="crest-gem">
          <PixelEmoji emoji="trophy" size="game" />
        </div>
      </div>

      <!-- Title & Subtitle with Cinematic Gothic Typography -->
      <div class="hall-title-block">
        <div class="hall-eyebrow flex items-center justify-center gap-2">
          <span class="star-dec">✦</span>
          <span>IMPERIAL ARCHIVE OF MINDREALM</span>
          <span class="star-dec">✦</span>
        </div>
        <h1 class="hall-main-title">HALL OF RECORDS</h1>
        <p class="hall-sub-title">THE CHRONICLE OF MINDBOUND CHAMPIONS</p>
        <p class="hall-lore-desc">
          Those who master knowledge leave their names inscribed forever upon the golden pillars of Mindrealm.
        </p>
      </div>
    </div>
  </header>
</template>

<script setup>
import PixelEmoji from '../ui/PixelEmoji.vue'
</script>

<style scoped>
.hall-header-root {
  position: relative;
  margin-bottom: 2rem;
  z-index: 2;
}

/* Top bar with quick navigation */
.hall-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.6rem 1rem;
  background: rgba(10, 11, 16, 0.75);
  border: 1px solid rgba(181, 148, 16, 0.25);
  border-radius: 4px;
  backdrop-filter: blur(8px);
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
}

.hall-nav-back, .hall-nav-arena {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  padding: 0.4rem 0.8rem;
  white-space: nowrap;
}

.hall-nav-back:hover {
  border-color: rgba(230, 200, 69, 0.6);
  color: #fff;
  transform: translateX(-2px);
}

.hall-nav-arena:hover {
  transform: translateX(2px);
}

.back-arrow {
  font-size: 1.1rem;
  line-height: 1;
}

.hall-archivist-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: #bfa888;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.archivist-rune {
  color: #e6c845;
  font-size: 0.8rem;
  animation: pulseRune 2.5s infinite ease-in-out;
}

.archivist-name {
  color: #e6c845;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  letter-spacing: 0.04em;
}

.archivist-quote {
  font-style: italic;
  color: #d1c5b4;
}

/* Monumental Crest Section */
.hall-crest-monument {
  text-align: center;
  position: relative;
  padding: 1.5rem 1rem 0.5rem;
}

.monument-aura {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 320px;
  height: 140px;
  background: radial-gradient(ellipse at 50% 40%, rgba(230, 200, 69, 0.22) 0%, rgba(139, 26, 26, 0.12) 50%, transparent 80%);
  pointer-events: none;
  filter: blur(12px);
}

.hall-crest-frame {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 76px;
  height: 76px;
  margin: 0 auto 1.25rem;
  background: radial-gradient(circle, #2a1414 0%, #0d0910 100%);
  border: 2px solid #b59410;
  box-shadow: 0 0 25px rgba(230, 200, 69, 0.35), inset 0 0 15px rgba(139, 26, 26, 0.5);
}

.crest-corner {
  position: absolute;
  width: 6px;
  height: 6px;
  background: #e6c845;
}
.crest-corner.tl { top: -2px; left: -2px; }
.crest-corner.tr { top: -2px; right: -2px; }
.crest-corner.bl { bottom: -2px; left: -2px; }
.crest-corner.br { bottom: -2px; right: -2px; }

.crest-gem {
  display: flex;
  align-items: center;
  justify-content: center;
  filter: drop-shadow(0 0 10px rgba(230, 200, 69, 0.6));
  animation: crestFloat 3.5s ease-in-out infinite;
}

.hall-eyebrow {
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  color: #b59410;
  letter-spacing: 0.18em;
  margin-bottom: 0.5rem;
  text-shadow: 0 0 12px rgba(181, 148, 16, 0.4);
}

.star-dec {
  color: #e6c845;
  font-size: 0.8rem;
}

.hall-main-title {
  font-family: 'Cinzel', serif;
  font-size: clamp(2.2rem, 5vw, 3.4rem);
  font-weight: 900;
  letter-spacing: 0.08em;
  color: #f7f3eb;
  margin: 0 0 0.35rem;
  line-height: 1.1;
  text-shadow:
    0 2px 4px #000,
    0 0 25px rgba(230, 200, 69, 0.4),
    0 0 50px rgba(139, 26, 26, 0.3);
}

.hall-sub-title {
  font-family: var(--font-pixel, monospace);
  font-size: clamp(0.72rem, 1.8vw, 0.88rem);
  color: #e6c845;
  letter-spacing: 0.12em;
  margin: 0 0 0.75rem;
  text-shadow: 0 0 10px rgba(230, 200, 69, 0.3);
}

.hall-lore-desc {
  max-width: 620px;
  margin: 0 auto;
  font-family: var(--font-gothic, 'Crimson Text', serif);
  font-size: 1.1rem;
  color: #c5b9a8;
  line-height: 1.45;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

@keyframes crestFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

@keyframes pulseRune {
  0%, 100% { opacity: 0.6; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.15); }
}

@media (max-width: 768px) {
  .hall-top-bar {
    flex-wrap: wrap;
    justify-content: center;
  }
  .hall-archivist-badge {
    order: 3;
    width: 100%;
    justify-content: center;
    font-size: 0.78rem;
  }
  .hall-crest-frame {
    width: 62px;
    height: 62px;
    margin-bottom: 0.85rem;
  }
  .hall-main-title {
    font-size: 1.85rem;
  }
}
</style>
