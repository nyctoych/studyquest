<!--
  MarketNPC.vue
  Permanent NPC Shopkeeper ("Raven") for the StudyQuest Market module.
  Renders the merchant sprite, grounded counter presence, nameplate, and animated dialogue bubble.
-->
<template>
  <aside class="market-npc-container" :class="[npcState, { 'is-talking': isSpeaking }]">
    <!-- Countertop Ambience & Light Glow Behind NPC -->
    <div class="npc-aura-glow" :class="npcState"></div>

    <!-- NPC Character Bust & Counter Setup -->
    <div class="npc-stage-wrap">
      <div class="npc-portrait-frame">
        <img
          :src="npcSpriteUrl"
          alt="Raven - Item Merchant"
          class="npc-sprite"
          :class="{
            'npc-bob': npcState === 'talking',
            'npc-happy': npcState === 'happy',
            'npc-surprised': npcState === 'surprised'
          }"
        />
        <div class="npc-counter-rim"></div>
      </div>

      <!-- Shopkeeper Nameplate -->
      <div class="npc-nameplate">
        <div class="nameplate-ornament-left">✦</div>
        <div class="nameplate-text-wrap">
          <span class="npc-name">{{ marketNpc.state.npcName }}</span>
          <span class="npc-role">{{ marketNpc.state.npcRole }}</span>
        </div>
        <div class="nameplate-ornament-right">✦</div>
      </div>
    </div>

    <!-- Gothic Dialogue Speech Bubble -->
    <div
      class="npc-dialogue-bubble"
      :class="{ 'visible': dialogueText, 'pulse': isSpeaking }"
    >
      <div class="bubble-corner corner-tl"></div>
      <div class="bubble-corner corner-tr"></div>
      <div class="bubble-corner corner-bl"></div>
      <div class="bubble-corner corner-br"></div>

      <p class="dialogue-text">
        <span class="quote-mark">“</span>{{ dialogueText }}<span class="quote-mark">”</span>
      </p>

      <div class="dialogue-arrow-gem">▼</div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import marketNpc from '../../services/marketNpcService'
import { resolveAsset } from '../../services/bossMapping'

const npcSpriteUrl = computed(() => {
  return resolveAsset('assets/game/market-npc-raven.png')
})

const dialogueText = computed(() => marketNpc.state.currentDialogue)
const isSpeaking = computed(() => marketNpc.state.isSpeaking)
const npcState = computed(() => marketNpc.state.npcState)
</script>

<style scoped>
.market-npc-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  max-width: 380px;
  user-select: none;
  z-index: 15;
}

/* Ambient backlight behind Raven */
.npc-aura-glow {
  position: absolute;
  top: 15%;
  left: 50%;
  transform: translateX(-50%);
  width: 280px;
  height: 280px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(168, 85, 247, 0.22) 0%, rgba(91, 33, 182, 0.08) 55%, transparent 75%);
  pointer-events: none;
  filter: blur(10px);
  transition: all 0.5s ease;
}

.npc-aura-glow.happy {
  background: radial-gradient(circle, rgba(234, 179, 8, 0.3) 0%, rgba(168, 85, 247, 0.15) 60%, transparent 75%);
}

.npc-aura-glow.thinking {
  background: radial-gradient(circle, rgba(59, 130, 246, 0.25) 0%, transparent 75%);
}

/* NPC Stage and Bust */
.npc-stage-wrap {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

.npc-portrait-frame {
  position: relative;
  width: clamp(170px, 18vw, 240px);
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  overflow: hidden;
}

.npc-sprite {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center bottom;
  image-rendering: pixelated;
  filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.85));
  animation: npcIdleBreath 4.5s ease-in-out infinite alternate;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.npc-sprite.npc-bob {
  animation: npcTalkingBounce 0.32s ease-in-out infinite alternate;
}

.npc-sprite.npc-happy {
  transform: scale(1.04) translateY(-3px);
  filter: drop-shadow(0 0 16px rgba(251, 191, 36, 0.55)) drop-shadow(0 10px 20px rgba(0, 0, 0, 0.9));
}

.npc-sprite.npc-surprised {
  transform: scale(0.98) translateY(2px);
}

/* Wooden Countertop Edge Under Raven */
.npc-counter-rim {
  position: absolute;
  bottom: 0;
  left: -10%;
  right: -10%;
  height: 14px;
  background: linear-gradient(to bottom, #452a1a 0%, #2b170c 100%);
  border-top: 2px solid #7c4a27;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  z-index: 2;
}

/* Shopkeeper Nameplate */
.npc-nameplate {
  position: relative;
  z-index: 5;
  margin-top: -6px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.3rem 1.25rem;
  background: linear-gradient(180deg, #1b1429 0%, #0e0a17 100%);
  border: 1px solid #7c3aed;
  border-radius: 4px;
  box-shadow:
    0 4px 14px rgba(0, 0, 0, 0.8),
    inset 0 0 10px rgba(139, 92, 246, 0.2);
}

.nameplate-ornament-left,
.nameplate-ornament-right {
  font-size: 0.65rem;
  color: #fbbf24;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.6);
}

.nameplate-text-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.npc-name {
  font-family: Georgia, 'Cinzel', serif;
  font-size: 0.92rem;
  font-weight: 800;
  letter-spacing: 0.18em;
  color: #f3e8ff;
  text-shadow: 0 0 10px rgba(168, 85, 247, 0.8), 0 2px 4px rgba(0, 0, 0, 0.9);
}

.npc-role {
  font-size: 0.62rem;
  letter-spacing: 0.12em;
  color: #c084fc;
  font-weight: 600;
  text-transform: uppercase;
}

/* Gothic Pixel Dialogue Speech Bubble */
.npc-dialogue-bubble {
  position: relative;
  width: 100%;
  margin-top: 0.75rem;
  padding: 0.75rem 1rem 0.9rem;
  background: rgba(14, 10, 24, 0.92);
  border: 1px solid rgba(168, 85, 247, 0.45);
  border-radius: 6px;
  box-shadow:
    0 8px 24px rgba(0, 0, 0, 0.85),
    0 0 18px rgba(139, 92, 246, 0.15),
    inset 0 0 14px rgba(88, 28, 135, 0.25);
  backdrop-filter: blur(8px);
  transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.25s ease;
}

.npc-dialogue-bubble.pulse {
  border-color: rgba(216, 180, 254, 0.8);
  box-shadow:
    0 8px 28px rgba(0, 0, 0, 0.9),
    0 0 24px rgba(168, 85, 247, 0.35),
    inset 0 0 16px rgba(139, 92, 246, 0.3);
  transform: translateY(-2px);
}

/* Ornate corner pixels */
.bubble-corner {
  position: absolute;
  width: 5px;
  height: 5px;
  border-color: #fbbf24;
  pointer-events: none;
}
.corner-tl { top: 2px; left: 2px; border-top: 2px solid #fbbf24; border-left: 2px solid #fbbf24; }
.corner-tr { top: 2px; right: 2px; border-top: 2px solid #fbbf24; border-right: 2px solid #fbbf24; }
.corner-bl { bottom: 2px; left: 2px; border-bottom: 2px solid #fbbf24; border-left: 2px solid #fbbf24; }
.corner-br { bottom: 2px; right: 2px; border-bottom: 2px solid #fbbf24; border-right: 2px solid #fbbf24; }

.dialogue-text {
  margin: 0;
  font-family: Georgia, 'Cinzel', serif;
  font-size: clamp(0.85rem, 1.2vw, 0.95rem);
  line-height: 1.55;
  color: #f1f5f9;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
  text-align: center;
}

.quote-mark {
  color: #a855f7;
  font-weight: 700;
  margin: 0 0.15rem;
}

/* Down gem arrow indicating merchant voice */
.dialogue-arrow-gem {
  position: absolute;
  bottom: -9px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.7rem;
  color: #c084fc;
  text-shadow: 0 0 8px rgba(192, 132, 252, 0.8);
  pointer-events: none;
}

/* Keyframe Animations */
@keyframes npcIdleBreath {
  0% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(-2px) scale(1.008); }
  100% { transform: translateY(0px) scale(1); }
}

@keyframes npcTalkingBounce {
  0% { transform: translateY(0px); }
  100% { transform: translateY(-3px); }
}

@media (max-width: 900px) {
  .market-npc-container {
    max-width: 100%;
    margin-bottom: 1.5rem;
  }
  .npc-portrait-frame {
    width: 170px;
  }
  .npc-dialogue-bubble {
    max-width: 500px;
    margin-top: 0.75rem;
  }
}
</style>
