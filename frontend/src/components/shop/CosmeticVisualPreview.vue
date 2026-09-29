<template>
  <div class="cosmetic-preview" :class="[item.rarity, item.effect_key]">
    <!-- ACTUAL ACCESSORY SVG ARTWORK -->

    <!-- 1. Reading Glasses -->
    <svg v-if="item.name === 'Reading Glasses'" viewBox="0 0 160 100" class="accessory-svg">
      <defs>
        <linearGradient id="glassesFrame" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f6d365" />
          <stop offset="100%" stop-color="#fda085" />
        </linearGradient>
        <radialGradient id="lensReflect" cx="30%" cy="30%" r="70%">
          <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6" />
          <stop offset="50%" stop-color="#60a5fa" stop-opacity="0.15" />
          <stop offset="100%" stop-color="#1e3a8a" stop-opacity="0.3" />
        </radialGradient>
      </defs>
      <!-- Temple arms -->
      <path d="M 22 45 Q 8 40 2 25" stroke="#f6d365" stroke-width="4" fill="none" stroke-linecap="round" />
      <path d="M 138 45 Q 152 40 158 25" stroke="#f6d365" stroke-width="4" fill="none" stroke-linecap="round" />
      <!-- Left Lens -->
      <circle cx="50" cy="50" r="28" fill="url(#lensReflect)" stroke="url(#glassesFrame)" stroke-width="4.5" />
      <path d="M 36 38 A 20 20 0 0 1 54 34" stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.8" />
      <!-- Right Lens -->
      <circle cx="110" cy="50" r="28" fill="url(#lensReflect)" stroke="url(#glassesFrame)" stroke-width="4.5" />
      <path d="M 96 38 A 20 20 0 0 1 114 34" stroke="#ffffff" stroke-width="2.5" fill="none" stroke-linecap="round" opacity="0.8" />
      <!-- Bridge -->
      <path d="M 78 47 Q 80 41 82 47" stroke="#f6d365" stroke-width="4" fill="none" stroke-linecap="round" />
      <!-- Center joint bar -->
      <line x1="77" y1="46" x2="83" y2="46" stroke="#f6d365" stroke-width="3" />
    </svg>

    <!-- 2. Graduation Cap -->
    <svg v-else-if="item.name === 'Graduation Cap'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="capGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#1e1b4b" />
          <stop offset="100%" stop-color="#0f172a" />
        </linearGradient>
        <linearGradient id="tasselGold" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#fbbf24" />
          <stop offset="100%" stop-color="#f59e0b" />
        </linearGradient>
      </defs>
      <!-- Skull cap base -->
      <path d="M 52 56 Q 80 72 108 56 L 104 76 Q 80 92 56 76 Z" fill="#0f172a" stroke="#312e81" stroke-width="2" />
      <!-- Mortarboard Diamond Top -->
      <polygon points="80,18 148,46 80,74 12,46" fill="url(#capGrad)" stroke="#6366f1" stroke-width="3.5" />
      <polygon points="80,24 140,46 80,68 20,46" fill="#1e293b" opacity="0.6" />
      <!-- Center button -->
      <circle cx="80" cy="46" r="5" fill="url(#tasselGold)" />
      <!-- Tassel string & fringe -->
      <path d="M 80 46 Q 105 52 118 68 Q 124 78 122 92" fill="none" stroke="url(#tasselGold)" stroke-width="2.5" />
      <!-- Tassel fringe bundle -->
      <rect x="117" y="86" width="10" height="18" rx="2" fill="url(#tasselGold)" />
      <line x1="120" y1="94" x2="120" y2="104" stroke="#d97706" stroke-width="1" />
      <line x1="124" y1="94" x2="124" y2="104" stroke="#d97706" stroke-width="1" />
    </svg>

    <!-- 3. Gaming Headset -->
    <svg v-else-if="item.name === 'Gaming Headset'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="headbandGrad" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" stop-color="#0f172a" />
          <stop offset="50%" stop-color="#334155" />
          <stop offset="100%" stop-color="#0f172a" />
        </linearGradient>
        <radialGradient id="rgbCyan" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#38bdf8" />
          <stop offset="100%" stop-color="#0284c7" />
        </radialGradient>
      </defs>
      <!-- Headband Arc -->
      <path d="M 32 62 A 52 52 0 0 1 128 62" fill="none" stroke="url(#headbandGrad)" stroke-width="9" stroke-linecap="round" />
      <path d="M 40 50 A 44 44 0 0 1 120 50" fill="none" stroke="#64748b" stroke-width="2.5" stroke-dasharray="4,4" />
      <!-- Left Ear Cup -->
      <rect x="18" y="52" width="22" height="38" rx="8" fill="#1e293b" stroke="#38bdf8" stroke-width="2.5" />
      <circle cx="29" cy="71" r="7" fill="url(#rgbCyan)" opacity="0.9" />
      <!-- Right Ear Cup -->
      <rect x="120" y="52" width="22" height="38" rx="8" fill="#1e293b" stroke="#38bdf8" stroke-width="2.5" />
      <circle cx="131" cy="71" r="7" fill="url(#rgbCyan)" opacity="0.9" />
      <!-- Mic Boom -->
      <path d="M 28 82 Q 32 102 54 100" fill="none" stroke="#64748b" stroke-width="3" stroke-linecap="round" />
      <rect x="52" y="96" width="10" height="7" rx="3" fill="#38bdf8" />
    </svg>

    <!-- 4. Crown of Wisdom -->
    <svg v-else-if="item.name === 'Crown of Wisdom'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="crownGold" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#fde047" />
          <stop offset="50%" stop-color="#f59e0b" />
          <stop offset="100%" stop-color="#b45309" />
        </linearGradient>
      </defs>
      <!-- Crown Base & Spikes -->
      <path d="M 22 84 L 18 36 L 48 58 L 80 20 L 112 58 L 142 36 L 138 84 Z" fill="url(#crownGold)" stroke="#fef08a" stroke-width="2" />
      <!-- Crown Base Rim -->
      <rect x="20" y="80" width="120" height="12" rx="4" fill="#d97706" stroke="#fde047" stroke-width="1.5" />
      <!-- Jewels -->
      <circle cx="80" cy="46" r="6" fill="#ef4444" stroke="#ffffff" stroke-width="1" />
      <polygon points="48,64 45,70 51,70" fill="#38bdf8" />
      <polygon points="112,64 109,70 115,70" fill="#38bdf8" />
      <circle cx="18" cy="34" r="3.5" fill="#fde047" />
      <circle cx="80" cy="18" r="4.5" fill="#fde047" />
      <circle cx="142" cy="34" r="3.5" fill="#fde047" />
      <!-- Bottom Rim Jewels -->
      <circle cx="40" cy="86" r="2.5" fill="#10b981" />
      <circle cx="80" cy="86" r="3" fill="#ef4444" />
      <circle cx="120" cy="86" r="2.5" fill="#3b82f6" />
    </svg>

    <!-- 5. Library Aura (Background) -->
    <svg v-else-if="item.name === 'Library Aura'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="libraryBg" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#1e1b4b" />
          <stop offset="100%" stop-color="#0f172a" />
        </linearGradient>
      </defs>
      <rect x="8" y="8" width="144" height="94" rx="10" fill="url(#libraryBg)" stroke="#38bdf8" stroke-width="1.5" />
      <!-- Book Shelves -->
      <rect x="20" y="24" width="120" height="6" rx="2" fill="#78350f" />
      <rect x="20" y="60" width="120" height="6" rx="2" fill="#78350f" />
      <rect x="20" y="94" width="120" height="6" rx="2" fill="#78350f" />
      <!-- Books on Shelf 1 -->
      <rect x="26" y="30" width="8" height="30" rx="1" fill="#ef4444" />
      <rect x="36" y="34" width="10" height="26" rx="1" fill="#3b82f6" />
      <rect x="48" y="28" width="7" height="32" rx="1" fill="#10b981" />
      <rect x="57" y="32" width="12" height="28" rx="1" fill="#f59e0b" />
      <rect x="71" y="30" width="9" height="30" rx="1" fill="#8b5cf6" />
      <rect x="82" y="36" width="11" height="24" rx="1" fill="#ec4899" />
      <rect x="95" y="29" width="8" height="31" rx="1" fill="#06b6d4" />
      <rect x="105" y="33" width="10" height="27" rx="1" fill="#eab308" />
      <!-- Books on Shelf 2 -->
      <rect x="26" y="66" width="10" height="28" rx="1" fill="#6366f1" />
      <rect x="38" y="68" width="8" height="26" rx="1" fill="#14b8a6" />
      <rect x="48" y="64" width="11" height="30" rx="1" fill="#f97316" />
      <!-- Magical study aura particles -->
      <circle cx="80" cy="50" r="14" fill="#38bdf8" opacity="0.15" />
      <text x="73" y="55" font-size="16" fill="#fbbf24">✦</text>
    </svg>

    <!-- 6. Cosmic Mind (Background) -->
    <svg v-else-if="item.name === 'Cosmic Mind'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <radialGradient id="cosmosGrad" cx="50%" cy="50%" r="60%">
          <stop offset="0%" stop-color="#818cf8" stop-opacity="0.8" />
          <stop offset="40%" stop-color="#4c1d95" stop-opacity="0.9" />
          <stop offset="100%" stop-color="#090d16" />
        </radialGradient>
      </defs>
      <rect x="8" y="8" width="144" height="94" rx="10" fill="url(#cosmosGrad)" stroke="#a855f7" stroke-width="1.5" />
      <!-- Orbit ring -->
      <ellipse cx="80" cy="55" rx="55" ry="18" fill="none" stroke="#c084fc" stroke-width="1.8" stroke-dasharray="6,4" transform="rotate(-15, 80, 55)" opacity="0.7" />
      <!-- Core psychic sphere -->
      <circle cx="80" cy="55" r="16" fill="#a855f7" stroke="#ffffff" stroke-width="2" />
      <circle cx="80" cy="55" r="8" fill="#e9d5ff" />
      <!-- Star sparks -->
      <circle cx="34" cy="30" r="2" fill="#fff" />
      <circle cx="128" cy="35" r="1.5" fill="#fff" />
      <circle cx="120" cy="80" r="2" fill="#fff" />
      <circle cx="45" cy="78" r="1.5" fill="#fff" />
      <text x="72" y="32" font-size="12" fill="#fbcfe8">✨</text>
    </svg>

    <!-- 7. Golden Hour (Background) -->
    <svg v-else-if="item.name === 'Golden Hour'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="sunsetGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#f59e0b" />
          <stop offset="45%" stop-color="#ea580c" />
          <stop offset="100%" stop-color="#4c0519" />
        </linearGradient>
      </defs>
      <rect x="8" y="8" width="144" height="94" rx="10" fill="url(#sunsetGrad)" stroke="#fbbf24" stroke-width="1.5" />
      <!-- Sun -->
      <circle cx="80" cy="58" r="22" fill="#fef08a" opacity="0.9" />
      <line x1="80" y1="26" x2="80" y2="34" stroke="#fef08a" stroke-width="2.5" stroke-linecap="round" />
      <line x1="50" y1="58" x2="42" y2="58" stroke="#fef08a" stroke-width="2.5" stroke-linecap="round" />
      <line x1="110" y1="58" x2="118" y2="58" stroke="#fef08a" stroke-width="2.5" stroke-linecap="round" />
      <!-- Academy Silhouette Mountain -->
      <polygon points="12,102 60,68 100,85 148,60 148,102" fill="#1e1b4b" opacity="0.8" />
    </svg>

    <!-- 8. Classic Waves (Hair) -->
    <svg v-else-if="item.name === 'Classic Waves'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="hairWaves" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#3b82f6" />
          <stop offset="100%" stop-color="#1d4ed8" />
        </linearGradient>
      </defs>
      <path d="M 40 75 Q 35 30 80 25 Q 125 30 120 75 Q 110 50 80 45 Q 50 50 40 75 Z" fill="url(#hairWaves)" stroke="#93c5fd" stroke-width="2" />
      <path d="M 45 70 Q 30 85 45 95 Q 60 90 55 75" fill="url(#hairWaves)" />
      <path d="M 115 70 Q 130 85 115 95 Q 100 90 105 75" fill="url(#hairWaves)" />
      <path d="M 60 38 Q 80 34 100 38" stroke="#bfdbfe" stroke-width="2.5" fill="none" stroke-linecap="round" />
    </svg>

    <!-- 9. Scholar Bun (Hair) -->
    <svg v-else-if="item.name === 'Scholar Bun'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="hairBun" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#475569" />
          <stop offset="100%" stop-color="#1e293b" />
        </linearGradient>
      </defs>
      <!-- Bun on top -->
      <circle cx="80" cy="30" r="18" fill="url(#hairBun)" stroke="#94a3b8" stroke-width="2" />
      <!-- Chopstick / Pen Hairpin -->
      <line x1="55" y1="40" x2="105" y2="20" stroke="#f59e0b" stroke-width="3.5" stroke-linecap="round" />
      <!-- Base hair cap -->
      <path d="M 44 80 Q 40 45 80 42 Q 120 45 116 80 Z" fill="url(#hairBun)" stroke="#64748b" stroke-width="2" />
    </svg>

    <!-- 10. Wild Quiff (Hair) -->
    <svg v-else-if="item.name === 'Wild Quiff'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="quiffGrad" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" stop-color="#f59e0b" />
          <stop offset="100%" stop-color="#b45309" />
        </linearGradient>
      </defs>
      <path d="M 44 82 L 40 55 Q 55 45 65 30 Q 80 15 95 18 Q 115 25 105 45 L 118 55 L 116 82 Z" fill="url(#quiffGrad)" stroke="#fde68a" stroke-width="2" />
      <path d="M 65 35 Q 85 24 95 40" stroke="#fef08a" stroke-width="2.5" fill="none" stroke-linecap="round" />
    </svg>

    <!-- 11. Long Flowing (Hair) -->
    <svg v-else-if="item.name === 'Long Flowing'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <linearGradient id="longGrad" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stop-color="#8b5cf6" />
          <stop offset="100%" stop-color="#4c1d95" />
        </linearGradient>
      </defs>
      <path d="M 46 45 Q 80 30 114 45 Q 128 75 125 105 Q 110 95 108 75 Q 80 65 52 75 Q 50 95 35 105 Q 32 75 46 45 Z" fill="url(#longGrad)" stroke="#c084fc" stroke-width="2" />
      <path d="M 58 52 Q 80 46 102 52" stroke="#e9d5ff" stroke-width="2" fill="none" />
    </svg>

    <!-- 12. Crimson Scholar (Skin) -->
    <svg v-else-if="item.name === 'Crimson Scholar'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <radialGradient id="crimsonAura" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#f43f5e" />
          <stop offset="100%" stop-color="#881337" />
        </radialGradient>
      </defs>
      <circle cx="80" cy="55" r="42" fill="url(#crimsonAura)" stroke="#fda4af" stroke-width="3" />
      <circle cx="80" cy="55" r="28" fill="#e11d48" stroke="#fff" stroke-width="1.5" />
      <text x="68" y="62" font-size="24">⚔️</text>
    </svg>

    <!-- 13. Midnight Sage (Skin) -->
    <svg v-else-if="item.name === 'Midnight Sage'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <radialGradient id="sageAura" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#a855f7" />
          <stop offset="100%" stop-color="#3b0764" />
        </radialGradient>
      </defs>
      <circle cx="80" cy="55" r="42" fill="url(#sageAura)" stroke="#d8b4fe" stroke-width="3" />
      <circle cx="80" cy="55" r="28" fill="#9333ea" stroke="#fff" stroke-width="1.5" />
      <text x="68" y="63" font-size="24">🔮</text>
    </svg>

    <!-- 14. Emerald Intellect (Skin) -->
    <svg v-else-if="item.name === 'Emerald Intellect'" viewBox="0 0 160 110" class="accessory-svg">
      <defs>
        <radialGradient id="emeraldAura" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#10b981" />
          <stop offset="100%" stop-color="#064e3b" />
        </radialGradient>
      </defs>
      <circle cx="80" cy="55" r="42" fill="url(#emeraldAura)" stroke="#6ee7b7" stroke-width="3" />
      <circle cx="80" cy="55" r="28" fill="#059669" stroke="#fff" stroke-width="1.5" />
      <text x="68" y="63" font-size="24">💎</text>
    </svg>

    <!-- Generic Fallback -->
    <div v-else class="generic-accessory-box">
      <span class="fallback-icon">{{ item.icon || '🎁' }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  item: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.cosmetic-preview {
  width: 100%;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.6));
  border-radius: 12px;
  overflow: hidden;
  position: relative;
  box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.4);
}

.cosmetic-preview.common { border: 1px solid rgba(148, 163, 184, 0.2); }
.cosmetic-preview.rare { border: 1px solid rgba(56, 189, 248, 0.35); box-shadow: inset 0 0 15px rgba(56, 189, 248, 0.15); }
.cosmetic-preview.epic { border: 1px solid rgba(168, 85, 247, 0.35); box-shadow: inset 0 0 15px rgba(168, 85, 247, 0.15); }
.cosmetic-preview.legendary { border: 1px solid rgba(251, 191, 36, 0.4); box-shadow: inset 0 0 20px rgba(251, 191, 36, 0.2); }

.accessory-svg {
  width: 100%;
  height: 100%;
  max-width: 150px;
  max-height: 100px;
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.5));
  transition: transform 0.25s ease;
}

.cosmetic-preview:hover .accessory-svg {
  transform: scale(1.08);
}

.generic-accessory-box {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
}
</style>
