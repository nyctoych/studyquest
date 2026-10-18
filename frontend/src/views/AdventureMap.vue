<template>
  <div class="rpg-adventure-screen">
    <!-- Persistent Defeat Status Ribbon (Rule 22) -->
    <div v-if="healthStore.isDefeated" class="adv-map-defeat-ribbon animate-slide-down">
      <div class="ribbon-inner">
        <span class="ribbon-skull">💀</span>
        <div class="ribbon-info">
          <strong class="font-pixel">ADVENTURE UNAVAILABLE — YOU ARE DEFEATED</strong>
          <span class="ribbon-sub">HP: 0 / {{ healthStore.maxHp }} • +1 HP / Min • Next Recovery: {{ healthStore.formattedCountdown }} • Recover at least 1 HP to battle</span>
        </div>
        <button class="btn btn-gothic-crimson btn-xs ribbon-btn" @click="goToMarketForPotion">
          🧪 RESTORE (+200 HP)
        </button>
      </div>
    </div>

    <!-- Map Landscape Canvas / Backdrop — Gothic pixel art adventure environment -->
    <div class="map-landscape-wrapper">
      <div class="map-viewport-stage">
        <Transition name="env-crossfade">
          <img
            :key="activeEnvironment.bgUrl"
            :src="activeEnvironment.bgUrl"
            :alt="activeEnvironment.name"
            class="adventure-map-img"
          />
        </Transition>
        <!-- Subtle edge atmosphere vignette & boss glow -->
        <div class="map-vignette" :style="{ background: activeEnvironment.ambientVignette }"></div>
        <div
          class="boss-sanctuary-glow"
          :class="{ 'glow-active': canFightBoss || activeBossDefeated }"
          :style="{
            top: activeEnvironment.bossGlow?.top || '28%',
            left: activeEnvironment.bossGlow?.left || '82.5%'
          }"
        ></div>

        <!-- Interactive 10 Episodes on the Map + CHAPTER CLIMAX BOSS -->
        <Transition name="nodes-fade" mode="out-in">
          <div :key="activeChapterNum" class="stage-nodes-layer">
            <!-- 10 Regular Episodes along the path -->
          <div
            v-for="ep in regularEpisodes"
            :key="ep.partNum"
            class="stage-node-wrapper"
            :class="['node-pos-' + ep.partNum, { 'node-unlocked': ep.unlocked, 'node-locked': !ep.unlocked, 'node-current': ep.isCurrent }]"
            :style="getNodeStyle(ep.partNum)"
            @click="selectEpisode(ep)"
          >
            <!-- Stage Number Tag (e.g. 1-1, 1-2, ... 1-10) -->
            <div class="stage-pill-tag" :class="{ 'tag-current': ep.isCurrent }">
              <span>{{ ep.code }}</span>
            </div>

            <!-- Stone Totem Monolith -->
            <div class="totem-monolith">
              <div class="totem-stone-body">
                <div class="totem-gem-eye" :class="{ 'gem-active': ep.unlocked }"></div>
                <span v-if="!ep.unlocked" class="node-padlock">
                  <PixelEmoji emoji="lock" size="xs" />
                </span>
              </div>
              <!-- Hex Stone Base -->
              <div class="totem-hex-pedestal"></div>
            </div>

            <!-- Completion status indicator (replaces star rating) -->
            <div v-if="ep.isCompleted" class="stage-status-indicator status-completed">
              <PixelEmoji emoji="check" size="xs" />
              <span class="status-label">CLEARED</span>
            </div>
            <div v-else-if="ep.unlocked && ep.isCurrent" class="stage-status-indicator status-ready">
              <span class="status-pulse-dot"></span>
              <span class="status-label">READY</span>
            </div>
          </div>

          <!-- 11th Episode: CHAPTER BOSS (Climax Sanctuary on Upper Terrace) -->
          <div
            class="stage-node-wrapper boss-node-wrapper node-pos-boss"
            :class="{ 'node-unlocked': bossEpisode.unlocked, 'node-locked': !bossEpisode.unlocked, 'node-current': bossEpisode.isCurrent }"
            :style="getBossNodeStyle()"
            @click="selectEpisode(bossEpisode)"
          >
            <!-- Boss Stage Pill Tag -->
            <div class="stage-pill-tag boss-pill-tag" :class="{ 'tag-boss-ready': bossEpisode.isCurrent, 'tag-boss-defeated': activeBossDefeated }">
              <PixelEmoji :emoji="bossEpisode.unlocked ? (activeBossDefeated ? 'crown' : 'ogre') : 'lock'" size="xs" />
              <span>{{ bossEpisode.unlocked ? (activeBossDefeated ? 'DEFEATED' : 'BOSS READY') : 'LOCKED' }}</span>
            </div>

            <!-- Boss Standing Unit (Pure transparent silhouette, no artificial box/rectangle) -->
            <div class="boss-standing-sprite-unit">
              <div class="boss-sprite-aura" :class="{ 'aura-active': bossEpisode.unlocked }"></div>

              <div class="boss-sprite-container" :class="{ 'boss-sprite-locked': !bossEpisode.unlocked }">
                <img
                  :src="currentBoss.sprite || currentBoss.animated || currentBoss.happy"
                  :alt="currentBoss.name"
                  class="boss-sprite-img pixel-art animate-boss-float"
                  @error="onBossImgError"
                />
                <!-- Floating pixel lock glyph without any square/rectangular background -->
                <div v-if="!bossEpisode.unlocked" class="boss-floating-lock-glyph">
                  <PixelEmoji emoji="lock" size="sm" />
                </div>
              </div>

              <!-- Dark Runic Hex Pedestal -->
              <div class="boss-hex-pedestal"></div>
            </div>

            <!-- Boss completion status indicator (replaces star rating) -->
            <div v-if="activeBossDefeated" class="stage-status-indicator status-completed boss-status-indicator">
              <PixelEmoji emoji="crown" size="xs" />
              <span class="status-label">RESTORED</span>
            </div>
            <div v-else-if="bossEpisode.unlocked && bossEpisode.isCurrent" class="stage-status-indicator status-ready boss-status-indicator">
              <span class="status-pulse-dot"></span>
              <span class="status-label">READY</span>
            </div>
          </div>

          <!-- 12th Episode (Chapter 12 Exclusive): FINAL BOSS — THE CREATOR (The Black Archive Void Singularity) -->
          <div
            v-if="activeChapterNum === 12"
            class="stage-node-wrapper final-boss-node-wrapper node-pos-final-boss"
            :class="{
              'node-unlocked': finalBossState === 'unlocked' || finalBossState === 'completed',
              'node-locked': finalBossState === 'locked',
              'node-current': finalBossState === 'unlocked',
              'node-completed': finalBossState === 'completed'
            }"
            :style="getFinalBossNodeStyle()"
            @click="openFinalBossEncounter"
          >
            <!-- Final Boss Stage Pill Tag -->
            <div
              class="stage-pill-tag final-boss-pill-tag"
              :class="{
                'tag-fb-ready': finalBossState === 'unlocked',
                'tag-fb-completed': finalBossState === 'completed',
                'tag-fb-locked': finalBossState === 'locked'
              }"
            >
              <PixelEmoji :emoji="finalBossState === 'completed' ? 'crown' : (finalBossState === 'unlocked' ? 'sparkles' : 'lock')" size="xs" />
              <span>{{ finalBossState === 'completed' ? 'CREATOR PACIFIED' : (finalBossState === 'unlocked' ? 'FINAL BOSS: THE CREATOR' : 'FINAL BOSS LOCKED') }}</span>
            </div>

            <!-- The Creator Unit with Portal Aura & Floating Sprite -->
            <div class="final-boss-unit">
              <div
                class="final-boss-portal-aura"
                :class="{
                  'aura-active': finalBossState === 'unlocked',
                  'aura-completed': finalBossState === 'completed'
                }"
              ></div>

              <div class="boss-sprite-container" :class="{ 'boss-sprite-locked': finalBossState === 'locked' }">
                <img
                  :src="resolveAsset(finalBossMeta.animated || finalBossMeta.idle || finalBossMeta.sprite)"
                  :alt="finalBossMeta.name"
                  class="boss-sprite-img pixel-art animate-boss-float final-boss-sprite-img"
                  @error="onBossImgError"
                />
                <div v-if="finalBossState === 'locked'" class="boss-floating-lock-glyph">
                  <PixelEmoji emoji="lock" size="sm" />
                </div>
              </div>

              <!-- Void Singularity Pedestal -->
              <div class="final-boss-pedestal" :class="{ 'pedestal-active': finalBossState === 'unlocked' }">
                <span class="fb-pedestal-label">THE BLACK ARCHIVE</span>
              </div>
            </div>

            <!-- Final Boss completion status indicator (replaces star rating) -->
            <div v-if="finalBossState === 'completed'" class="stage-status-indicator status-completed boss-status-indicator">
              <PixelEmoji emoji="crown" size="xs" />
              <span class="status-label">PACIFIED</span>
            </div>
            <div v-else-if="finalBossState === 'unlocked'" class="stage-status-indicator status-ready boss-status-indicator">
              <span class="status-pulse-dot"></span>
              <span class="status-label">READY</span>
            </div>
          </div>
        </div>
      </Transition>
    </div>

      <!-- Newly Unlocked Chapter Celebration Banner -->
      <Transition name="banner-fade">
        <div v-if="showUnlockBanner" class="chapter-unlocked-banner animate-scale-up">
          <PixelEmoji emoji="sparkles" size="sm" />
          <span class="banner-text">✨ CHAPTER {{ activeChapterNum }} UNLOCKED!</span>
          <PixelEmoji emoji="sparkles" size="sm" />
        </div>
      </Transition>

      <!-- Top Overlay: Chapter Switcher (Center) -->
      <div class="map-top-bar">
        <!-- Chapter Switcher in Center -->
        <div class="chapter-center-col">
          <div class="chapter-nav-pill-wrap">
            <div class="chapter-nav-pill" @click="showChapterSelector = true">
              <button class="ch-nav-btn" @click.stop="prevChapter" :disabled="currentChapterIndex <= 0">◀</button>
              <div class="ch-nav-info">
                <div class="ch-nav-num-row">
                  <span class="ch-nav-num">CHAPTER {{ activeChapterNum }}</span>
                  <span v-if="activeChapterStoryTitle" class="ch-nav-story-highlight">"{{ activeChapterStoryTitle }}"</span>
                  <span class="ch-status-badge-inline" :class="'ch-badge-' + activeChapterStatus">
                    <PixelEmoji :emoji="chapterStatusEmoji(activeChapterStatus)" size="xs" />
                    <span>{{ chapterStatusLabel(activeChapterStatus) }}</span>
                  </span>
                </div>
                <span class="ch-nav-title">{{ activeChapterTitle }}</span>
                <span v-if="activeChapterActTitle" class="ch-nav-act-tag">{{ activeChapterActTitle }}</span>
              </div>
              <button class="ch-nav-btn" @click.stop="nextChapter" :disabled="currentChapterIndex >= (chapters.length - 1)">▶</button>
            </div>
            <div class="ch-extra-btns">
              <button class="story-prologue-btn" @click="showChapterIntroModal = true" title="View Chapter Story Prologue">
                <span>📜 LORE</span>
              </button>
              <button class="all-chapters-btn" @click="showChapterSelector = true" title="View All Chapters">
                <PixelEmoji emoji="scroll" size="xs" />
                <span>CHAPTERS</span>
              </button>
            </div>
          </div>

          <!-- Locked Chapter Banner (Visible when exploring locked domain - positioned below chapter title) -->
          <div v-if="isViewedChapterLocked" class="locked-chapter-banner animate-slide-down">
            <div class="locked-banner-inner">
              <PixelEmoji emoji="lock" size="xs" />
              <span class="locked-banner-text">
                CHAPTER {{ activeChapterNum }} IS LOCKED — Complete Chapter {{ lockedRequiredOrder }} to unlock this chapter.
              </span>
              <button class="btn btn-gothic-crimson btn-xs return-curr-ch-btn" @click="goToCurrentUnlockedChapter">
                GO TO CURRENT CHAPTER →
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ── CHAPTER SELECTOR MODAL ───────────────────────── -->
    <div v-if="showChapterSelector" class="modal-backdrop rpg-modal-backdrop" @click.self="showChapterSelector = false">
      <div class="stage-modal-card rpg-stage-modal chapter-selector-modal animate-scale-up">
        <div class="stage-modal-header">
          <div class="modal-badge-group">
            <span class="stage-code-badge">
              <PixelEmoji emoji="scroll" size="xs" /> PSYCHOLOGY CHRONICLES
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="showChapterSelector = false">✕</button>
        </div>

        <h3 class="stage-modal-title modal-stage-name">PSYCHOLOGY ADVENTURE CHAPTERS</h3>
        <p class="stage-topic-desc">Select a chapter domain to view progress or replay completed trials.</p>

        <div class="chapters-grid-list">
          <div
            v-for="(ch, idx) in chapters"
            :key="ch.id"
            class="chapter-grid-card"
            :class="{
              'active-card': idx === currentChapterIndex,
              'locked-card': ch.chapter_status === 'locked',
              'completed-card': ch.chapter_status === 'completed' || ch.chapter_status === 'mastered'
            }"
            @click="selectChapterFromList(idx)"
          >
            <div class="ch-card-top">
              <span class="ch-card-num">CHAPTER {{ ch.display_order }}</span>
              <span class="ch-card-status" :class="'status-' + ch.chapter_status">
                <PixelEmoji :emoji="chapterStatusEmoji(ch.chapter_status)" size="xs" />
                <span>{{ chapterStatusLabel(ch.chapter_status) }}</span>
              </span>
            </div>
            <span v-if="ch.act_title" class="ch-card-act-pill">{{ ch.act_title }}</span>
            <h4 class="ch-card-name">{{ ch.name }}</h4>
            <span v-if="ch.chapter_story_title" class="ch-card-story-italic">"{{ ch.chapter_story_title }}"</span>

            <div v-if="ch.chapter_status === 'locked'" class="ch-lock-req">
              <PixelEmoji emoji="lock" size="xs" />
              <span>Complete Chapter {{ ch.required_chapter_order || (ch.display_order - 1) }} to unlock.</span>
            </div>
            <div v-else class="ch-card-meta">
              <span class="ch-parts-count">{{ ch.parts_completed || 0 }}/10 Episodes</span>
              <span v-if="ch.boss_defeated" class="ch-boss-check">👑 Restored (Seal Recovered)</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── STAGE DETAILS & BATTLE CONFIRMATION MODAL ───── -->
    <div v-if="selectedEpisodeData" class="modal-backdrop rpg-modal-backdrop" @click.self="selectedEpisodeData = null">
      <div class="stage-modal-card rpg-stage-modal animate-scale-up" :class="{ 'boss-modal-card': selectedEpisodeData.isBoss }">
        <div class="stage-modal-header" :class="{ 'boss-modal-header': selectedEpisodeData.isBoss }">
          <div class="modal-badge-group">
            <span class="stage-code-badge modal-stage-code" :class="{ 'boss-code-badge': selectedEpisodeData.isBoss }">
              {{ selectedEpisodeData.code }} {{ selectedEpisodeData.isBoss ? '— CHAPTER CLIMAX' : '' }}
            </span>
            <span v-if="selectedEpisodeData.isCompleted" class="stage-status-badge completed-badge">
              <PixelEmoji emoji="crown" size="xs" /> COMPLETED
            </span>
            <span v-else-if="!selectedEpisodeData.unlocked" class="stage-status-badge locked-badge">
              <PixelEmoji emoji="lock" size="xs" /> LOCKED
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="selectedEpisodeData = null">✕</button>
        </div>

        <h3 class="stage-modal-title modal-stage-name">
          {{ isViewedChapterLocked ? (selectedEpisodeData.isBoss ? 'BOSS LOCKED' : 'CHAPTER LOCKED') : selectedEpisodeData.title }}
        </h3>
        <!-- Story Narrative Phase and Clue -->
        <div v-if="selectedEpisodeData.narrativePhase && !isViewedChapterLocked" class="modal-story-phase-row">
          <span class="modal-phase-pill" :class="'phase-' + selectedEpisodeData.narrativePhase.toLowerCase()">
            {{ selectedEpisodeData.narrativePhase.toUpperCase() }}
          </span>
          <span v-if="selectedEpisodeData.storyTitle" class="modal-story-title-sub">"{{ selectedEpisodeData.storyTitle }}"</span>
        </div>
        <p class="stage-modal-desc stage-topic-desc">
          {{ isViewedChapterLocked ? ('Complete Chapter ' + lockedRequiredOrder + ' to unlock this chapter.') : selectedEpisodeData.description }}
        </p>
        <div v-if="selectedEpisodeData.fractureClue && !isViewedChapterLocked" class="modal-fracture-clue">
          <span class="clue-tag">🔮 FRACTURE CLUE:</span> {{ selectedEpisodeData.fractureClue }}
        </div>

        <!-- Regional Foe Preview for Non-Boss Trials -->
        <div v-if="!selectedEpisodeData.isBoss && currentChapterMinion && !isViewedChapterLocked" class="modal-regional-minion-row">
          <div class="minion-preview-avatar">
            <img :src="currentChapterMinionImg" :alt="currentChapterMinion.name" class="minion-preview-thumb pixel-art" />
          </div>
          <div class="minion-preview-text">
            <div class="minion-preview-tag font-pixel">{{ currentChapterMinion.iconEmoji }} REGIONAL FOE</div>
            <div class="minion-preview-name">{{ currentChapterMinion.name }}</div>
            <div class="minion-preview-lore">{{ currentChapterMinion.title }}</div>
          </div>
        </div>

        <!-- Rewards preview -->
        <div class="stage-rewards-preview">
          <h4 class="rewards-sec-title">ESTIMATED REWARDS</h4>
          <div class="rewards-chips-row">
            <div class="reward-chip xp-chip">
              <PixelEmoji emoji="sparkles" size="xs" />
              <span>+{{ selectedEpisodeData.xpReward }} XP</span>
            </div>
            <div class="reward-chip coin-chip">
              <PixelEmoji emoji="coin" size="xs" />
              <span>+{{ selectedEpisodeData.coinReward }} Ancient Gold</span>
            </div>
          </div>
        </div>

        <!-- Locked notice -->
        <div v-if="!selectedEpisodeData.unlocked" class="stage-lock-notice">
          <PixelEmoji emoji="lock" size="xs" />
          <span v-if="isViewedChapterLocked">
            <template v-if="selectedEpisodeData.isBoss">
              Complete Chapter {{ lockedRequiredOrder }} and all preceding trials to unlock this boss battle.
            </template>
            <template v-else>
              Complete Chapter {{ lockedRequiredOrder }} to unlock this chapter.
            </template>
          </span>
          <span v-else-if="selectedEpisodeData.isBoss">
            Complete all 10 preceding parts of Chapter {{ activeChapterNum }} to unlock this boss battle.
          </span>
          <span v-else>
            Complete Part {{ selectedEpisodeData.partNum - 1 }} to unlock this episode.
          </span>
        </div>

        <!-- Defeat Warning Box (when 0 HP) -->
        <div v-if="healthStore.isDefeated" class="stage-defeat-alert-box animate-scale-up">
          <div class="defeat-alert-header">
            <span class="defeat-alert-skull">💀</span>
            <div class="defeat-alert-title-wrap">
              <h4 class="defeat-alert-title font-pixel">YOU ARE DEFEATED</h4>
              <p class="defeat-alert-subtitle">Your strength has been exhausted. Recover at least 1 HP before returning to battle.</p>
            </div>
          </div>
          <div class="defeat-stats-grid">
            <div class="stat-cell">
              <span class="cell-label font-pixel">CURRENT HP</span>
              <span class="cell-val text-red font-pixel">0 / {{ healthStore.maxHp }}</span>
            </div>
            <div class="stat-cell">
              <span class="cell-label font-pixel">RECOVERY</span>
              <span class="cell-val text-gold font-pixel">+1 HP / MIN</span>
            </div>
            <div class="stat-cell">
              <span class="cell-label font-pixel">NEXT RECOVERY</span>
              <span class="cell-val text-teal font-pixel">{{ healthStore.formattedCountdown }}</span>
            </div>
          </div>
          <button class="btn btn-gothic-crimson btn-sm w-full mt-2" @click="goToMarketForPotion">
            🧪 RESTORE WITH HEALTH POTION (+200 HP)
          </button>
        </div>

        <div class="stage-modal-actions">
          <button class="btn btn-gothic-stone" @click="selectedEpisodeData = null">← RETURN</button>
          <button
            v-if="healthStore.isDefeated"
            class="btn btn-gothic-crimson btn-lg enter-battle-btn btn-exhausted"
            @click="showDefeatModal = true"
          >
            💀 YOU ARE DEFEATED
          </button>
          <button
            v-else-if="selectedEpisodeData.unlocked"
            class="btn btn-gothic-crimson btn-lg enter-battle-btn"
            @click="launchBattle"
          >
            {{ selectedEpisodeData.isCompleted ? '⚔️ REPLAY TRIAL' : '⚔️ ENTER BATTLE' }}
          </button>
          <button
            v-else-if="isViewedChapterLocked"
            class="btn btn-gothic-crimson btn-lg"
            @click="goToCurrentUnlockedChapter"
          >
            GO TO CURRENT CHAPTER →
          </button>
          <button v-else class="btn btn-gothic-stone btn-lg" disabled>
            🔒 LOCKED
          </button>
        </div>
      </div>
    </div>

    <!-- ── RECOVERED MIND SEALS & RELICS MODAL ───────────── -->
    <div v-if="showSealsModal" class="modal-backdrop rpg-modal-backdrop" @click.self="showSealsModal = false">
      <div class="stage-modal-card rpg-stage-modal seals-modal animate-scale-up">
        <div class="stage-modal-header">
          <div class="modal-badge-group">
            <span class="stage-code-badge">
              🔮 MINDKEEPER CODEX
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="showSealsModal = false">✕</button>
        </div>
        <h3 class="stage-modal-title modal-stage-name">RECOVERED MIND SEALS & FRACTURE SHARDS</h3>
        <p class="stage-topic-desc">Corrupted guardians restored through psychological mastery return their ancient Mind Seals.</p>
        <div class="seals-grid">
          <div v-for="ch in chapters" :key="ch.id" class="seal-relic-card" :class="{ 'seal-recovered': ch.boss_defeated }">
            <div class="seal-icon-box">
              <span>{{ ch.boss_defeated ? '🔮' : '🔒' }}</span>
            </div>
            <div class="seal-card-text">
              <div class="seal-card-top-row">
                <span class="seal-ch-num">CHAPTER {{ ch.display_order }}</span>
                <span class="seal-status-tag" :class="ch.boss_defeated ? 'tag-restored' : 'tag-distorted'">
                  {{ ch.boss_defeated ? 'RESTORED' : 'DISTORTED' }}
                </span>
              </div>
              <strong class="seal-name">{{ ch.mind_seal_name || ('Mind Seal of Ch ' + ch.display_order) }}</strong>
              <div class="seal-shard-info" v-if="ch.fracture_shard_name && ch.boss_defeated">
                <span class="shard-icon">💎</span>
                <span class="shard-name">{{ ch.fracture_shard_name }}</span>
              </div>
              <span class="shard-hint" v-else>Defeat corrupted guardian to recover</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── CHAPTER STORY PROLOGUE MODAL ─────────────────── -->
    <ChapterIntro
      v-if="showChapterIntroModal"
      :chapter="activeChapterDetails || activeChapter"
      @close="showChapterIntroModal = false"
      @enter="showChapterIntroModal = false"
    />

    <!-- ── BLACK ARCHIVE ENCOUNTER MODAL ────────────────── -->
    <BlackArchiveModal
      v-if="showBlackArchiveModal"
      @close="showBlackArchiveModal = false"
    />

    <!-- ── DEFEAT & HEALTH RECOVERY MODAL (Rules 9, 10, 22) ── -->
    <div v-if="showDefeatModal" class="modal-backdrop rpg-modal-backdrop" @click.self="showDefeatModal = false">
      <div class="stage-modal-card rpg-stage-modal defeat-recovery-modal animate-scale-up">
        <div class="stage-modal-header">
          <div class="modal-badge-group">
            <span class="stage-code-badge badge-crimson">
              💀 DEFEAT STATE
            </span>
          </div>
          <button class="close-modal-btn modal-close-btn" @click="showDefeatModal = false">✕</button>
        </div>

        <div class="defeat-hero-icon-wrap">
          <span class="defeat-skull-art">💀</span>
        </div>

        <h3 class="stage-modal-title modal-stage-name text-crimson text-center">YOU ARE DEFEATED</h3>
        <p class="stage-topic-desc text-center">
          Your strength has been exhausted. Recover at least 1 HP before returning to battle.
        </p>

        <div class="defeat-recovery-dashboard">
          <div class="recovery-metric-card">
            <span class="metric-label font-pixel">CURRENT HEALTH</span>
            <div class="metric-val text-red font-pixel">
              {{ healthStore.currentHp }} / {{ healthStore.maxHp }} HP
            </div>
            <div class="health-mini-bar-track">
              <div class="health-mini-bar-fill" :style="{ width: healthStore.hpPercent + '%' }"></div>
            </div>
          </div>

          <div class="recovery-metric-card">
            <span class="metric-label font-pixel">HEALTH RECOVERY</span>
            <div class="metric-val text-gold font-pixel">
              +1 HP / MINUTE
            </div>
            <span class="metric-sub">Passive recovery runs continuously (online & offline)</span>
          </div>

          <div class="recovery-metric-card">
            <span class="metric-label font-pixel">NEXT RECOVERY</span>
            <div class="metric-val text-teal font-pixel">
              {{ healthStore.formattedCountdown }}
            </div>
            <span class="metric-sub">Authoritative server checkpoint synchronization</span>
          </div>
        </div>

        <div class="stage-modal-actions mt-4">
          <button class="btn btn-gothic-stone" @click="showDefeatModal = false">
            ← CLOSE
          </button>
          <button class="btn btn-gothic-crimson btn-lg" @click="goToMarketForPotion">
            🧪 RESTORE WITH HEALTH POTION (+200 HP)
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAdventureStore } from '../stores/adventure'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTransitionStore } from '../stores/transition'
import api from '../services/api'
import PixelEmoji from '../components/ui/PixelEmoji.vue'
import { getBossForChapter, getFinalBoss, resolveAsset } from '../services/bossMapping'
import { getChapterMinion, getMinionAssetUrl } from '../config/chapterMinions'
import ChapterIntro from '../components/adventure/ChapterIntro.vue'
import BlackArchiveModal from '../components/adventure/BlackArchiveModal.vue'
import { getChapterEnvironment, preloadAdjacentBackgrounds } from '../config/chapterEnvironments'
import { useHealthStore } from '../stores/health'

const route = useRoute()
const router = useRouter()
const adventure = useAdventureStore()
const auth = useAuthStore()
const toast = useToastStore()
const transitionStore = useTransitionStore()
const healthStore = useHealthStore()

const showDefeatModal = ref(false)

const advHp = computed(() => healthStore.currentHp)
const advMaxHp = computed(() => healthStore.maxHp)

async function loadAdvHealth() {
  await healthStore.fetchHealth()
}

function goToMarketForPotion() {
  selectedEpisodeData.value = null
  showDefeatModal.value = false
  transitionStore.triggerTravel('/shop', router)
}

const ALL_CHAPTERS_FALLBACK = [
  { id: 1, display_order: 1, name: 'Neurodevelopmental Disorders', chapter_status: 'available' },
  { id: 2, display_order: 2, name: 'Schizophrenia Spectrum and Other Psychotic Disorders', chapter_status: 'available' },
  { id: 3, display_order: 3, name: 'Bipolar and Related Disorders', chapter_status: 'locked' },
  { id: 4, display_order: 4, name: 'Depressive Disorders', chapter_status: 'locked' },
  { id: 5, display_order: 5, name: 'Anxiety Disorders', chapter_status: 'locked' },
  { id: 6, display_order: 6, name: 'Obsessive-Compulsive and Related Disorders', chapter_status: 'locked' },
  { id: 7, display_order: 7, name: 'Trauma-, Stressor-Related and Dissociative Disorders', chapter_status: 'locked' },
  { id: 8, display_order: 8, name: 'Somatic Symptom and Related Disorders', chapter_status: 'locked' },
  { id: 9, display_order: 9, name: 'Feeding, Eating, and Elimination Disorders', chapter_status: 'locked' },
  { id: 10, display_order: 10, name: 'Sleep-Wake, Sexual Dysfunctions, and Gender Dysphoria', chapter_status: 'locked' },
  { id: 11, display_order: 11, name: 'Substance-Related, Addictive, and Disruptive Disorders', chapter_status: 'locked' },
  { id: 12, display_order: 12, name: 'Neurocognitive, Personality, and Paraphilic Disorders', chapter_status: 'locked' }
]

function resolveInitialChapterIndex() {
  const targetId = Number(route.params.chapterId || route.query.chapter)
  const list = adventure.chapters?.length ? adventure.chapters : ALL_CHAPTERS_FALLBACK
  if (targetId) {
    const idx = list.findIndex(c => c.id === targetId || c.display_order === targetId)
    if (idx !== -1) return idx
    if (targetId >= 1 && targetId <= 12) return targetId - 1
  }
  const savedActive = Number(localStorage.getItem('sq_active_chapter'))
  if (savedActive) {
    const idx = list.findIndex(c => c.id === savedActive || c.display_order === savedActive)
    if (idx !== -1) return idx
    if (savedActive >= 1 && savedActive <= 12) return savedActive - 1
  }
  const unlocked = Number(adventure.currentUnlockedChapterId || localStorage.getItem('sq_unlocked_chapter'))
  if (unlocked) {
    const idx = list.findIndex(c => c.id === unlocked || c.display_order === unlocked)
    if (idx !== -1) return idx
    if (unlocked >= 1 && unlocked <= 12) return unlocked - 1
  }
  return 0
}

const chapters = ref(adventure.chapters?.length ? adventure.chapters : ALL_CHAPTERS_FALLBACK)
const currentChapterIndex = ref(resolveInitialChapterIndex())
const selectedDiff = ref('normal')
const selectedEpisodeData = ref(null)
const showChapterSelector = ref(false)
const showUnlockBanner = ref(false)
const showSealsModal = ref(false)
const showChapterIntroModal = ref(false)
const showBlackArchiveModal = ref(false)

// Active Chapter Data from API
const activeChapterDetails = ref(null)
const activeParts = ref([])
const activeBoss = ref(null)
const canFightBoss = ref(false)

const activeChapter = computed(() => chapters.value[currentChapterIndex.value] || chapters.value[0])
const activeChapterNum = computed(() => activeChapter.value?.display_order || (currentChapterIndex.value + 1))
const activeChapterTitle = computed(() => activeChapter.value?.name || 'General Psychology')
const activeChapterStatus = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.chapter_status || activeChapter.value?.chapter_status || 'available'
  }
  return activeChapter.value?.chapter_status || 'available'
})
const activeBossDefeated = computed(() => Boolean(activeChapterDetails.value?.boss_defeated || activeChapter.value?.boss_defeated))
const currentBoss = computed(() => getBossForChapter(activeChapterNum.value))

const activeEnvironment = computed(() => {
  return getChapterEnvironment(activeChapterNum.value)
})

function getNodeStyle(partNum) {
  const pos = activeEnvironment.value?.nodePositions?.[partNum]
  if (!pos) return {}
  return {
    top: pos.top,
    left: pos.left
  }
}

function getBossNodeStyle() {
  const pos = activeEnvironment.value?.nodePositions?.boss
  if (!pos) return {}
  return {
    top: pos.top,
    left: pos.left
  }
}

const finalBossMeta = computed(() => getFinalBoss())

function getFinalBossNodeStyle() {
  return {
    top: '19%',
    left: 'calc(80% - 52px)'
  }
}

function openFinalBossEncounter() {
  if (finalBossState.value === 'locked') {
    toast.error('The Black Archive remains sealed! Defeat Chapter 12 Boss first.')
    return
  }
  showBlackArchiveModal.value = true
}

function openQuestModal() {
  if (activeChapterNum.value >= 12 && activeBossDefeated.value) {
    openFinalBossEncounter()
  } else if (canFightBoss.value && bossEpisode.value?.unlocked) {
    selectEpisode(bossEpisode.value)
  } else {
    const nextPart = regularEpisodes.value.find(e => !e.isCompleted)
    if (nextPart) selectEpisode(nextPart)
  }
}

const activeChapterStoryTitle = computed(() => {
  return activeChapterDetails.value?.chapter_story_title || activeChapterDetails.value?.story_title || activeChapter.value?.chapter_story_title || ''
})

const activeChapterActTitle = computed(() => {
  return activeChapterDetails.value?.act_title || activeChapter.value?.act_title || ''
})

const currentChapterMinion = computed(() => {
  return getChapterMinion(activeChapterNum.value)
})

const currentChapterMinionImg = computed(() => {
  return getMinionAssetUrl(activeChapterNum.value)
})

const recoveredSealsCount = computed(() => {
  return adventure.recoveredMindSeals?.length || chapters.value.filter(c => c.boss_defeated).length
})

const blackArchiveUnlocked = computed(() => {
  return adventure.finalBoss?.unlocked || adventure.blackArchiveUnlocked || adventure.allChaptersCompleted || (chapters.value.length >= 12 && chapters.value.every(c => c.boss_defeated))
})

const finalBossState = computed(() => {
  if (adventure.finalBoss?.status) return adventure.finalBoss.status
  if (blackArchiveUnlocked.value) return 'unlocked'
  return 'locked'
})

const isViewedChapterLocked = computed(() => {
  return activeChapterStatus.value === 'locked'
})

const lockedRequiredOrder = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.required_chapter_order || activeChapter.value?.required_chapter_order || Math.max(1, activeChapterNum.value - 1)
  }
  return activeChapter.value?.required_chapter_order || Math.max(1, activeChapterNum.value - 1)
})

const lockedRequiredName = computed(() => {
  if (activeChapterDetails.value && (activeChapterDetails.value.id === activeChapter.value?.id || activeChapterDetails.value.display_order === activeChapter.value?.display_order)) {
    return activeChapterDetails.value.required_chapter_name || `Chapter ${lockedRequiredOrder.value}`
  }
  return `Chapter ${lockedRequiredOrder.value}`
})

function onBossImgError(e) {
  if (e?.target) {
    e.target.src = resolveAsset('assets/bosses/brain/2happybrain.png')
  }
}

// 10 Regular Episodes for the active chapter
const regularEpisodes = computed(() => {
  const ch = activeChapterNum.value
  const chId = activeChapter.value?.id
  const isChDetailsMatch = activeChapterDetails.value && (activeChapterDetails.value.id === chId || activeChapterDetails.value.display_order === ch)
  const parts = isChDetailsMatch ? (activeParts.value || []) : []
  const isChLocked = isViewedChapterLocked.value

  // If backend provided parts for THIS active chapter, use them
  if (parts.length > 0) {
    return parts.map(p => {
      const isComplete = !isChLocked && (p.part_status === 'completed' || p.part_status === 'mastered')
      const isAvailable = !isChLocked && (p.part_status === 'available' || p.part_status === 'in_progress' || isComplete)
      return {
        id: p.id,
        partNum: p.part_number,
        code: `${ch}-${p.part_number}`,
        title: p.story_title ? `Part ${p.part_number}: ${p.story_title}` : p.title,
        storyTitle: p.story_title,
        narrativePhase: p.narrative_phase,
        fractureClue: p.fracture_clue,
        description: p.story_intro || p.description || p.overview || 'Master foundational psychology runes.',
        unlocked: isAvailable,
        isCompleted: isComplete,
        isCurrent: !isChLocked && (p.part_status === 'available' || p.part_status === 'in_progress'),
        xpReward: p.xp_reward || (2000 + (ch - 1) * 1000),
        coinReward: p.coin_reward || 25,
      }
    })
  }

  // Fallback 10 items if loading
  const fallback = []
  for (let i = 1; i <= 10; i++) {
    fallback.push({
      id: i,
      partNum: i,
      code: `${ch}-${i}`,
      title: `Episode ${i}`,
      description: 'Psychological scientific inquiry trial.',
      unlocked: !isChLocked && (i === 1),
      isCompleted: false,
      isCurrent: !isChLocked && (i === 1),
      xpReward: 2000 + (ch - 1) * 1000,
      coinReward: 25 + i * 5,
    })
  }
  return fallback
})

// The 11th node is the Climax Chapter Boss
const bossEpisode = computed(() => {
  const ch = activeChapterNum.value
  const isChLocked = isViewedChapterLocked.value
  const isUnlocked = !isChLocked && (canFightBoss.value || activeBossDefeated.value)
  return {
    id: 'boss',
    isBoss: true,
    code: `${ch} BOSS`,
    title: activeBoss.value?.name || currentBoss.value?.name || 'CHAPTER ABERRATION',
    description: activeBoss.value?.flavor_text || currentBoss.value?.description || 'The climax guardian testing all 10 preceding lessons of this chapter.',
    unlocked: isUnlocked,
    isCompleted: !isChLocked && activeBossDefeated.value,
    isCurrent: !isChLocked && canFightBoss.value && !activeBossDefeated.value,
    xpReward: activeBoss.value?.xp_reward || (6000 + (ch - 1) * 3000),
    coinReward: activeBoss.value?.point_reward || 300,
  }
})

const completedPartsCount = computed(() => {
  return regularEpisodes.value.filter(e => e.isCompleted).length
})

const activeObjective = computed(() => {
  const ch = activeChapterNum.value
  if (isViewedChapterLocked.value) {
    return {
      title: `Locked Domain — Chapter ${ch}`,
      sub: `Complete Chapter ${lockedRequiredOrder.value} to Unlock`
    }
  }
  if (activeBossDefeated.value) {
    if (ch >= 12) {
      if (finalBossState.value === 'completed') {
        return {
          title: 'The Creator Pacified!',
          sub: 'Mindrealm Restored — Campaign Complete'
        }
      }
      return {
        title: 'The Final Climax Beckons!',
        sub: 'Confront The Creator in The Black Archive'
      }
    }
    return {
      title: `Chapter ${ch} Conquered!`,
      sub: `Proceed to Chapter ${ch + 1}`
    }
  }
  if (canFightBoss.value) {
    return {
      title: `Defeat Chapter ${ch} Boss`,
      sub: 'Conquer the Aberration Sanctuary'
    }
  }
  const nextPart = regularEpisodes.value.find(e => !e.isCompleted)
  return {
    title: `Clear Episode ${ch}-${nextPart?.partNum || 1}`,
    sub: `Chapter ${ch} Progression`
  }
})

function chapterStatusEmoji(status) {
  switch (status) {
    case 'completed':
    case 'mastered':
      return 'crown'
    case 'boss_available':
      return 'ogre'
    case 'in_progress':
      return 'target'
    case 'available':
      return 'sword'
    case 'locked':
    default:
      return 'lock'
  }
}

function chapterStatusLabel(status) {
  switch (status) {
    case 'completed': return 'COMPLETED'
    case 'mastered':  return 'MASTERED'
    case 'boss_available': return 'BOSS READY'
    case 'in_progress': return 'IN PROGRESS'
    case 'available':   return 'AVAILABLE'
    case 'locked':
    default:
      return 'LOCKED'
  }
}

let isMounted = false

onMounted(() => {
  isMounted = true

  // Sync target chapter immediately without waiting
  const targetId = Number(route.params.chapterId || route.query.chapter)
  if (targetId) {
    const idx = chapters.value.findIndex(c => c.id === targetId || c.display_order === targetId)
    if (idx !== -1 && idx !== currentChapterIndex.value) {
      currentChapterIndex.value = idx
    } else if (targetId >= 1 && targetId <= 12 && (targetId - 1) !== currentChapterIndex.value) {
      currentChapterIndex.value = targetId - 1
    }
  }

  // Load active chapter data immediately for the target chapter
  loadActiveChapterData()
  loadAdvHealth()

  // Load all chapters in background and re-verify index
  loadAllChapters().then(() => {
    if (!isMounted || !route.path.startsWith('/adventure')) return
    const freshTarget = Number(route.params.chapterId || route.query.chapter)
    if (freshTarget) {
      const idx = chapters.value.findIndex(c => c.id === freshTarget || c.display_order === freshTarget)
      if (idx !== -1 && idx !== currentChapterIndex.value) {
        currentChapterIndex.value = idx
        loadActiveChapterData()
      }
    }
  })

  // Celebration banner check
  if (route.query.unlocked === 'true') {
    showUnlockBanner.value = true
    setTimeout(() => { showUnlockBanner.value = false }, 4500)
  }
})

onUnmounted(() => {
  isMounted = false
  activeChapterRequestId++
})

watch(() => [route.params.chapterId, route.query.chapter], ([newParam, newQuery]) => {
  if (!isMounted || !route.path.startsWith('/adventure')) return
  const target = Number(newParam || newQuery)
  if (target) {
    const idx = chapters.value.findIndex(c => c.id === target || c.display_order === target)
    if (idx !== -1 && idx !== currentChapterIndex.value) {
      currentChapterIndex.value = idx
      loadActiveChapterData()
    }
  }
})

async function loadAllChapters() {
  try {
    const res = await adventure.loadChapters()
    if (adventure.chapters?.length) {
      chapters.value = adventure.chapters
    }
  } catch (err) {
    console.warn('Using default chapter list', err)
  }
}

let activeChapterRequestId = 0

async function loadActiveChapterData() {
  if (!isMounted || !route.path.startsWith('/adventure')) return
  const ch = activeChapter.value
  if (!ch) return

  const reqId = ++activeChapterRequestId
  const data = await adventure.loadChapter(ch.id)
  if (!isMounted || reqId !== activeChapterRequestId || !route.path.startsWith('/adventure')) {
    return
  }

  if (data?.success) {
    activeChapterDetails.value = data.chapter
    activeParts.value = data.parts || []
    activeBoss.value = data.boss
    canFightBoss.value = Boolean(data.can_fight_boss && !isViewedChapterLocked.value)
  }

  // Preload adjacent environments for instant, seamless transitions
  preloadAdjacentBackgrounds(activeChapterNum.value)

  // Sync route path and query cleanly only if still on adventure map and route differs
  if (isMounted && route.path.startsWith('/adventure')) {
    const targetPath = `/adventure/${ch.id}`
    const targetQueryChapter = String(ch.display_order)
    if (route.path !== targetPath || route.query.chapter !== targetQueryChapter) {
      const query = { ...route.query }
      delete query.unlocked
      router.replace({
        path: targetPath,
        query: { ...query, chapter: ch.display_order }
      }).catch(() => {})
    }
  }
}

function switchChapter(idx) {
  if (!isMounted || !route.path.startsWith('/adventure')) return
  if (idx < 0 || idx >= chapters.value.length) return
  currentChapterIndex.value = idx
  activeParts.value = []
  activeChapterDetails.value = null
  const ch = chapters.value[idx]
  if (ch?.id) {
    localStorage.setItem('sq_active_chapter', String(ch.id))
    const targetPath = `/adventure/${ch.id}`
    const targetQueryChapter = String(ch.display_order)
    if (route.path !== targetPath || route.query.chapter !== targetQueryChapter) {
      const query = { ...route.query }
      delete query.unlocked
      router.replace({
        path: targetPath,
        query: { ...query, chapter: ch.display_order }
      }).catch(() => {})
    }
  }
  loadActiveChapterData()
}

async function selectChapterFromList(idx) {
  showChapterSelector.value = false
  switchChapter(idx)
}

function prevChapter() {
  if (currentChapterIndex.value > 0) {
    switchChapter(currentChapterIndex.value - 1)
  }
}

function nextChapter() {
  if (currentChapterIndex.value < chapters.value.length - 1) {
    switchChapter(currentChapterIndex.value + 1)
  }
}

function goToCurrentUnlockedChapter() {
  selectedEpisodeData.value = null
  const unlockedIdx = chapters.value.findIndex(c => c.chapter_status === 'in_progress' || c.chapter_status === 'available' || c.chapter_status === 'boss_available')
  if (unlockedIdx !== -1) {
    switchChapter(unlockedIdx)
  } else {
    const targetId = adventure.currentUnlockedChapterId || 1
    const idx = chapters.value.findIndex(c => c.id === targetId || c.display_order === targetId)
    switchChapter(idx !== -1 ? idx : 0)
  }
}

function goToLatestChapter() {
  goToCurrentUnlockedChapter()
}

function setDifficulty(diff) {
  if (diff !== 'normal') {
    toast.info(`${diff.toUpperCase()} mode unlocks after conquering Chapter ${activeChapterNum.value} Boss!`)
    return
  }
  selectedDiff.value = diff
}

function selectEpisode(ep) {
  selectedEpisodeData.value = ep
}

function launchBattle() {
  const ep = selectedEpisodeData.value
  if (!ep || !ep.unlocked || isViewedChapterLocked.value) {
    return
  }
  if (healthStore.isDefeated) {
    toast.error('Your strength has been exhausted! Recover at least 1 HP before returning to battle.')
    showDefeatModal.value = true
    return
  }
  selectedEpisodeData.value = null
  const chId = activeChapter.value?.id || 1

  if (ep.isBoss) {
    transitionStore.triggerTravel(`/adventure/${chId}/boss`, router)
  } else {
    transitionStore.triggerTravel(`/adventure/${chId}/part/${ep.partNum}`, router)
  }
}

function openBonusModal() {
  toast.info('Bonus Stage: Complete daily episodes to earn bonus reviewer drop cards!')
}
</script>

<style scoped>
/* ── ADVENTURE MAP ROOT ──────────────────────────── */
.rpg-adventure-screen {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background: var(--gothic-void, #050608);
  user-select: none;
  font-family: var(--font-pixel, 'Press Start 2P', monospace);
}

.map-landscape-wrapper {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: #0d0612;
}

.map-viewport-stage {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100vw;
  height: 56.25vw;
  min-height: 100vh;
  min-width: 177.78vh;
  overflow: hidden;
}

.adventure-map-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: fill;
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  filter: contrast(1.05) saturate(1.05);
}

/* Environment Crossfade Animation — Seamless blending with zero black-flash */
.env-crossfade-enter-active,
.env-crossfade-leave-active {
  position: absolute;
  inset: 0;
  transition: opacity 0.35s ease-in-out;
}
.env-crossfade-enter-from,
.env-crossfade-leave-to {
  opacity: 0;
}

/* Stage Nodes Fade Transition between Chapters */
.nodes-fade-enter-active,
.nodes-fade-leave-active {
  transition: opacity 0.22s ease-in-out;
}
.nodes-fade-enter-from,
.nodes-fade-leave-to {
  opacity: 0;
}

.map-vignette {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse at center, transparent 65%, rgba(6, 7, 10, 0.55) 100%),
    linear-gradient(180deg, rgba(6, 7, 10, 0.4) 0%, transparent 18%, transparent 82%, rgba(6, 7, 10, 0.7) 100%);
  pointer-events: none;
  transition: background 0.6s ease-in-out;
}

.boss-sanctuary-glow {
  position: absolute;
  top: 28%;
  left: 82.5%;
  transform: translate(-50%, -50%);
  width: 220px;
  height: 180px;
  background: radial-gradient(ellipse, rgba(239, 68, 68, 0.28) 0%, rgba(185, 28, 28, 0.08) 50%, transparent 80%);
  animation: bossSanctuaryPulse 3s ease-in-out infinite alternate;
  pointer-events: none;
}
.boss-sanctuary-glow.glow-active {
  background: radial-gradient(ellipse, rgba(239, 68, 68, 0.45) 0%, rgba(234, 179, 8, 0.15) 50%, transparent 80%);
}
@keyframes bossSanctuaryPulse {
  0%   { transform: translate(-50%, -50%) scale(0.95); opacity: 0.6; }
  100% { transform: translate(-50%, -50%) scale(1.15); opacity: 1; }
}

/* Celebration Banner */
.chapter-unlocked-banner {
  position: absolute;
  top: 135px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 50;
  background: rgba(185, 28, 28, 0.95);
  border: 2px solid var(--color-gold-ancient, #e6c845);
  padding: 0.6rem 1.4rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.9), 0 0 20px rgba(230, 200, 69, 0.5);
}
.banner-text {
  font-family: var(--font-pixel, monospace);
  font-size: 0.72rem;
  color: #fff;
  letter-spacing: 1.5px;
}
.banner-fade-enter-active,
.banner-fade-leave-active {
  transition: all 0.5s ease;
}
.banner-fade-enter-from,
.banner-fade-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-20px);
}

/* ── TOP BAR ─────────────────────────────────────── */
.map-top-bar {
  position: absolute;
  top: 72px;
  left: 1.2rem;
  right: 1.2rem;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  z-index: 20;
  pointer-events: none;
}



.adv-health-recovery-badge {
  border-left: 3px solid #ef4444;
  border-color: rgba(239, 68, 68, 0.4);
}
.health-crest-icon {
  font-size: 0.85rem;
}
.health-regen-sub {
  color: #86efac !important;
  font-size: 0.58rem !important;
  font-weight: 700;
  text-shadow: 0 0 4px rgba(34, 197, 94, 0.6);
}

.chapter-nav-pill-wrap {
  pointer-events: auto;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.chapter-nav-pill {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(10, 11, 15, 0.95);
  border: 2px solid var(--color-crimson-bright, #8b1a1a);
  padding: 0.35rem 0.9rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.8), 0 0 10px rgba(139, 26, 26, 0.3);
  cursor: pointer;
  transition: border-color 0.15s ease;
}
.chapter-nav-pill:hover {
  border-color: var(--color-crimson-bright, #b91c1c);
}
.all-chapters-btn {
  background: rgba(14, 15, 22, 0.95);
  border: 1px solid #3a3b4c;
  color: var(--color-bone, #e2d9cc);
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  padding: 0.55rem 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.all-chapters-btn:hover {
  border-color: var(--color-gold-ancient, #b59410);
  background: #1c1d29;
}
.ch-nav-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--color-stone-600, #3a3b4c);
  width: 24px;
  height: 24px;
  color: var(--color-bone, #e2d9cc);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.65rem;
  transition: all 0.15s ease;
}
.ch-nav-btn:hover:not(:disabled) {
  background: var(--color-crimson, #8b1a1a);
  color: #fff;
  border-color: var(--color-crimson-bright, #b91c1c);
}
.ch-nav-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}
.ch-nav-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.15rem;
}
.ch-nav-num-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}
.ch-nav-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  font-weight: 700;
  color: var(--color-crimson-bright, #b91c1c);
  letter-spacing: 1px;
}
.ch-status-badge-inline {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  padding: 0.1rem 0.35rem;
  background: #161722;
  border: 1px solid #2e3040;
}
.ch-badge-completed {
  border-color: var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}
.ch-badge-boss_available {
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #ef4444;
}
.ch-nav-title {
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-bone, #e2d9cc);
  letter-spacing: 0.5px;
}

.chapter-center-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
  pointer-events: none;
  flex: 0 0 auto;
}

/* Locked Chapter Banner */
.locked-chapter-banner {
  pointer-events: auto;
  max-width: min(92vw, 760px);
  z-index: 22;
}
.locked-banner-inner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: rgba(22, 8, 8, 0.96);
  border: 1px solid var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.85), 0 0 12px rgba(185, 28, 28, 0.35);
  padding: 0.35rem 0.85rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.62rem;
  color: #fca5a5;
  backdrop-filter: blur(6px);
  border-radius: 4px;
}
.locked-banner-text {
  letter-spacing: 0.4px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}
.return-curr-ch-btn {
  font-size: 0.58rem;
  padding: 0.22rem 0.55rem;
  margin-left: 0.4rem;
  white-space: nowrap;
  cursor: pointer;
}

.quest-objective-banner {
  pointer-events: auto;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(14, 15, 22, 0.95);
  border: 2px solid #3a3225;
  border-left: 3px solid var(--color-gold-ancient, #b59410);
  padding: 0.35rem 0.8rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.8);
  transition: transform 0.15s ease, border-color 0.15s ease;
}
.quest-objective-banner:hover {
  transform: translateY(-2px);
  border-color: var(--color-gold-ancient, #b59410);
}
.objective-icon-box {
  width: 28px;
  height: 28px;
  background: #181924;
  border: 1px solid #3a3b4c;
  display: flex;
  align-items: center;
  justify-content: center;
}
.objective-text-group {
  display: flex;
  flex-direction: column;
}
.objective-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-gold-ancient, #e6c845);
}
.objective-sub {
  font-family: var(--font-body, serif);
  font-size: 0.7rem;
  color: var(--color-bone-dark, #8c8273);
}
.quill-scroll-deco {
  margin-left: 0.2rem;
}

/* ── STAGE NODES LAYER ───────────────────────────── */
.stage-nodes-layer {
  position: absolute;
  inset: 0;
  z-index: 10;
  pointer-events: none;
}

.stage-node-wrapper {
  position: absolute;
  pointer-events: auto;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.stage-node-wrapper:hover {
  transform: translateY(-6px) scale(1.08);
}
.stage-node-wrapper.node-locked {
  filter: grayscale(0.4) brightness(0.75);
  opacity: 0.85;
}
.stage-node-wrapper.node-current {
  animation: currentGothicPulse 2.2s infinite ease-in-out;
}
@keyframes currentGothicPulse {
  0%, 100% {
    transform: scale(1);
    filter: drop-shadow(0 0 6px rgba(185, 28, 28, 0.4));
  }
  50% {
    transform: scale(1.08);
    filter: drop-shadow(0 0 14px rgba(185, 28, 28, 0.85));
  }
}

/* Precise Node Coordinates along the winding gothic road & bridge */
.node-pos-1  { top: 89%; left: 13%; }
.node-pos-2  { top: 87%; left: 21%; }
.node-pos-3  { top: 84%; left: 29%; }
.node-pos-4  { top: 83%; left: 37%; }
.node-pos-5  { top: 74%; left: 42%; }
.node-pos-6  { top: 73%; left: 51%; }
.node-pos-7  { top: 63%; left: 50%; }
.node-pos-8  { top: 59%; left: 58%; }
.node-pos-9  { top: 55%; left: 66%; }
.node-pos-10 { top: 50%; left: 72%; }
.node-pos-boss { top: 32%; left: calc(82.5% - 52px); }

/* Stage Pill Tag */
.stage-pill-tag {
  background: rgba(12, 13, 18, 0.95);
  border: 1px solid var(--color-stone-600, #3a3b4c);
  padding: 0.15rem 0.45rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.8);
  margin-bottom: 4px;
}
.tag-current {
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #fff;
  background: rgba(30, 8, 8, 0.95);
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.6);
}
.boss-pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #ff9999;
  background: rgba(45, 10, 10, 0.95);
  box-shadow: 0 0 12px rgba(185, 28, 28, 0.7);
}
.tag-boss-ready {
  border-color: #22c55e;
  color: #86efac;
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.6);
}
.tag-boss-defeated {
  border-color: var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}

/* Stone Totem Monolith */
.totem-monolith {
  position: relative;
  width: 44px;
  height: 60px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
}
.totem-stone-body {
  position: relative;
  width: 32px;
  height: 44px;
  background: linear-gradient(180deg, #232533 0%, #151620 60%, #0d0e15 100%);
  border: 2px solid #3d4052;
  border-top-color: #555973;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.9), inset 0 1px 2px rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}
.totem-gem-eye {
  width: 6px;
  height: 6px;
  background: #2a2d3d;
  z-index: 2;
  transition: all 0.3s ease;
}
.totem-gem-eye.gem-active {
  background: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 0 8px #ef4444, 0 0 12px #991b1b;
}
.node-padlock {
  position: absolute;
  z-index: 3;
}
.totem-hex-pedestal {
  width: 42px;
  height: 10px;
  background: linear-gradient(180deg, #1b1c26 0%, #0c0d12 100%);
  border: 1.5px solid #2e3040;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.8);
  margin-top: -2px;
}

/* Stage Status Indicator (Replaces Star Rating) */
.stage-status-indicator {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  padding: 0.12rem 0.35rem;
  border-radius: 3px;
  font-family: var(--font-pixel, monospace);
  font-size: 0.42rem;
  letter-spacing: 0.05em;
  line-height: 1;
  margin-top: 0.2rem;
  user-select: none;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.9));
}

.stage-status-indicator.status-completed {
  background: rgba(16, 185, 129, 0.2);
  border: 1px solid rgba(16, 185, 129, 0.6);
  color: #34d399;
}

.stage-status-indicator.status-ready {
  background: rgba(245, 158, 11, 0.2);
  border: 1px solid rgba(245, 158, 11, 0.6);
  color: #fbbf24;
}

.status-pulse-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #fbbf24;
  box-shadow: 0 0 4px #f59e0b;
  animation: pulseDot 1.5s infinite ease-in-out;
}

@keyframes pulseDot {
  0%, 100% { opacity: 0.4; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.2); }
}

.boss-status-indicator {
  font-size: 0.45rem;
  padding: 0.15rem 0.45rem;
}

/* Boss Unit (Pure transparent silhouette, no artificial box or rectangular background) */
.boss-node-wrapper {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
}
.boss-standing-sprite-unit {
  position: relative;
  width: 105px;
  height: 125px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  background: transparent;
  border: none;
  box-shadow: none;
}
.boss-sprite-aura {
  position: absolute;
  top: 35%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 95px;
  height: 95px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(185, 28, 28, 0.35) 0%, rgba(185, 28, 28, 0.08) 55%, transparent 75%);
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.boss-sprite-aura.aura-active {
  opacity: 1;
  animation: crimsonThrob 2.5s infinite ease-in-out;
}
.boss-sprite-container {
  position: relative;
  width: 85px;
  height: 85px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  box-shadow: none;
}
.boss-sprite-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
  background: transparent;
  border: none;
  box-shadow: none;
  filter: drop-shadow(0 0 10px rgba(185, 28, 28, 0.5));
  transition: filter 0.25s ease, transform 0.25s ease;
}
.boss-node-wrapper:hover .boss-sprite-img {
  filter: drop-shadow(0 0 18px rgba(220, 38, 38, 0.9)) drop-shadow(0 0 6px rgba(255, 120, 120, 0.6));
  transform: scale(1.08);
}
.boss-sprite-locked .boss-sprite-img {
  filter: grayscale(0.65) brightness(0.65) drop-shadow(0 0 8px rgba(0, 0, 0, 0.8));
}
.boss-floating-lock-glyph {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
  background: transparent;
  border: none;
  box-shadow: none;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 8px rgba(185, 28, 28, 0.7));
}
.boss-hex-pedestal {
  width: 78px;
  height: 16px;
  background: linear-gradient(180deg, #2a0e0e 0%, #100505 100%);
  border: 2px solid #8b1a1a;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.9);
}

/* ── BOTTOM BAR ──────────────────────────────────── */
.map-bottom-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 72px;
  background: linear-gradient(0deg, rgba(5, 6, 8, 0.98) 0%, rgba(10, 11, 15, 0.85) 80%, transparent 100%);
  border-top: 2px solid #232533;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  z-index: 20;
}
.bonus-stage-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: none;
  border: none;
  cursor: pointer;
  gap: 0.2rem;
  transition: transform 0.15s ease;
}
.bonus-stage-btn:hover {
  transform: translateY(-2px) scale(1.05);
}
.bonus-gem-emblem {
  position: relative;
  width: 38px;
  height: 38px;
  background: radial-gradient(circle, #8b1a1a 20%, #4a0d0d 80%);
  border: 2px solid #b91c1c;
  box-shadow: 0 4px 10px rgba(139, 26, 26, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
}
.bonus-stage-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  color: var(--color-bone, #e2d9cc);
}
.chapter-bottom-summary {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.2rem;
}
.cbs-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
  color: var(--color-bone, #e2d9cc);
}
.cbs-prog {
  font-family: var(--font-body, serif);
  font-size: 0.75rem;
  color: var(--color-gold-ancient, #e6c845);
}

.difficulty-tabs-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.diff-tab {
  background: rgba(18, 19, 26, 0.95);
  border: 1px solid #303342;
  padding: 0.35rem 0.8rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.diff-tab:hover {
  border-color: #555973;
}
.diff-tab.active {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(35, 12, 14, 0.95);
  box-shadow: 0 0 10px rgba(185, 28, 28, 0.4);
}
.diff-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: var(--color-bone, #e2d9cc);
}
.diff-tab.active .diff-name {
  color: #fff;
}
.diff-prog {
  font-family: var(--font-body, serif);
  font-size: 0.65rem;
  color: var(--color-bone-dark, #8c8273);
}

/* ── MODALS ──────────────────────────────────────── */
.modal-backdrop,
.rpg-modal-backdrop {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(3, 4, 6, 0.85);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  box-sizing: border-box;
}

.stage-modal-card,
.rpg-stage-modal {
  width: 94%;
  max-width: 480px;
  max-height: 88vh;
  background: #0f1017;
  border: 2px solid var(--color-stone-600, #3a3b4c);
  border-top: 3px solid var(--color-crimson-bright, #b91c1c);
  outline: 2px solid #050608;
  padding: 1.25rem;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 25px rgba(0, 0, 0, 0.8);
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  z-index: 10000;
  box-sizing: border-box;
}
.boss-modal-card {
  border-color: var(--color-crimson-bright, #b91c1c);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.95), 0 0 30px rgba(185, 28, 28, 0.45);
}

.stage-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #232533;
  padding-bottom: 0.5rem;
  flex-shrink: 0;
}
.modal-badge-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.stage-code-badge,
.modal-stage-code {
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  background: #1c1d29;
  border: 1px solid #3a3b4c;
  color: var(--color-bone, #e2d9cc);
  padding: 0.2rem 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.boss-code-badge {
  background: var(--color-crimson, #8b1a1a);
  border-color: var(--color-crimson-bright, #b91c1c);
  color: #fff;
}
.stage-status-badge {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  padding: 0.2rem 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}
.completed-badge {
  background: #19160d;
  border: 1px solid var(--color-gold-ancient, #b59410);
  color: var(--color-gold-ancient, #e6c845);
}
.locked-badge {
  background: #151620;
  border: 1px solid #3a3b4c;
  color: var(--color-bone-dark, #8c8273);
}

.close-modal-btn,
.modal-close-btn {
  background: none;
  border: none;
  color: var(--color-bone-dark, #8c8273);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.2rem 0.4rem;
  line-height: 1;
  transition: color 0.15s ease;
}
.close-modal-btn:hover,
.modal-close-btn:hover {
  color: #fff;
}

.stage-modal-title,
.modal-stage-name {
  font-family: var(--font-pixel, monospace);
  font-size: 0.95rem;
  color: var(--color-bone, #e2d9cc);
  line-height: 1.35;
  margin: 0;
}
.stage-modal-desc,
.stage-topic-desc {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone-dark, #8c8273);
  line-height: 1.55;
  margin: 0;
}

.stage-rewards-preview {
  background: #14151f;
  border: 1px solid #282a3a;
  padding: 0.75rem 1rem;
}
.rewards-sec-title {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-gold-ancient, #e6c845);
  margin: 0 0 0.4rem 0;
}
.rewards-chips-row {
  display: flex;
  gap: 1rem;
}
.reward-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.6rem;
}
.xp-chip { color: var(--color-gold-ancient, #e6c845); }
.coin-chip { color: var(--color-bone, #e2d9cc); }

.stage-lock-notice {
  background: #18090a;
  border: 1px solid var(--color-crimson, #8b1a1a);
  padding: 0.6rem 0.8rem;
  font-family: var(--font-body, serif);
  font-size: 0.8rem;
  color: #fca5a5;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stage-modal-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-top: 0.5rem;
}

/* Chapter Selector Modal */
.chapter-selector-modal {
  max-width: 620px;
}
.chapters-grid-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  max-height: 55vh;
  overflow-y: auto;
  padding-right: 0.3rem;
}
.chapter-grid-card {
  background: #141520;
  border: 1.5px solid #2e3040;
  padding: 0.75rem 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.15s ease;
}
.chapter-grid-card:hover:not(.locked-card) {
  border-color: var(--color-crimson-bright, #b91c1c);
  transform: translateY(-2px);
}
.chapter-grid-card.active-card {
  border-color: var(--color-crimson-bright, #b91c1c);
  background: rgba(35, 12, 14, 0.9);
}
.chapter-grid-card.completed-card {
  border-color: var(--color-gold-ancient, #b59410);
}
.chapter-grid-card.locked-card {
  opacity: 0.65;
  cursor: not-allowed;
  filter: grayscale(0.5);
}
.ch-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.ch-card-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  color: var(--color-crimson-bright, #b91c1c);
}
.ch-card-status {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
}
.status-completed { color: var(--color-gold-ancient, #e6c845); }
.status-boss_available { color: #ef4444; }
.status-in_progress { color: #38bdf8; }
.status-available { color: #22c55e; }
.status-locked { color: #94a3b8; }
.ch-card-name {
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: var(--color-bone, #e2d9cc);
  margin: 0;
}
.ch-lock-req {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-family: var(--font-body, serif);
  font-size: 0.72rem;
  color: #94a3b8;
}
.ch-card-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--font-pixel, monospace);
  font-size: 0.48rem;
  color: var(--color-bone-dark, #8c8273);
}
.ch-boss-check {
  color: var(--color-gold-ancient, #e6c845);
}

/* Locked warning modal */
.locked-modal {
  text-align: center;
  align-items: center;
}
.lock-req-box {
  background: #180a0a;
  border: 1px solid var(--color-crimson, #8b1a1a);
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-body, serif);
  font-size: 0.85rem;
  color: #fca5a5;
  width: 100%;
  box-sizing: border-box;
}

/* ── DARK FANTASY STORY CAMPAIGN STYLES ── */
.top-left-badges-group {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.6rem;
}
.mind-seals-codex-medal {
  cursor: pointer;
  border-color: rgba(168, 85, 247, 0.45);
  background: rgba(18, 10, 28, 0.92);
  transition: transform 0.2s ease, border-color 0.2s ease;
}
.mind-seals-codex-medal:hover {
  transform: translateY(-2px);
  border-color: rgba(216, 180, 254, 0.8);
  box-shadow: 0 0 15px rgba(168, 85, 247, 0.4);
}
.seal-crest-icon {
  background: rgba(168, 85, 247, 0.2);
  border-color: #a855f7;
}
.seal-icon-symbol {
  font-size: 1.1rem;
}
.ch-nav-story-highlight {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.76rem;
  color: #fbbf24;
  margin-left: 0.4rem;
}
.ch-nav-act-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #c084fc;
  letter-spacing: 1px;
  display: block;
  margin-top: 2px;
}
.ch-extra-btns {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.story-prologue-btn {
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(168, 85, 247, 0.5);
  color: #e9d5ff;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  padding: 0.45rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}
.story-prologue-btn:hover {
  background: rgba(139, 92, 246, 0.4);
  border-color: #c084fc;
}
.top-right-group {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.6rem;
}
.black-archive-top-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #180512 0%, #4a0429 50%, #0d0107 100%);
  border: 1px solid #f43f5e;
  box-shadow: 0 0 15px rgba(244, 63, 94, 0.45);
  color: #ffe4e6;
  font-family: var(--font-pixel, monospace);
  font-size: 0.68rem;
  padding: 0.5rem 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  letter-spacing: 0.5px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.black-archive-top-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 0 25px rgba(244, 63, 94, 0.8);
}
.black-archive-top-btn.is-locked {
  background: linear-gradient(135deg, #101017 0%, #1c1c28 100%);
  border-color: #3f3f50;
  box-shadow: none;
  color: #94a3b8;
}
.black-archive-top-btn.is-completed {
  background: linear-gradient(135deg, #051412 0%, #064e3b 100%);
  border-color: #10b981;
  box-shadow: 0 0 15px rgba(16, 185, 129, 0.35);
  color: #a7f3d0;
}
.ba-status-badge {
  font-size: 0.52rem;
  padding: 0.15rem 0.35rem;
  border-radius: 2px;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid currentColor;
}
.ch-card-act-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #c084fc;
  margin-bottom: 0.2rem;
  display: inline-block;
}
.ch-card-story-italic {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.82rem;
  color: #fbbf24;
  margin-bottom: 0.4rem;
  display: block;
}
.modal-story-phase-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.modal-phase-pill {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  padding: 0.2rem 0.45rem;
  border-radius: 3px;
  letter-spacing: 0.5px;
}
.phase-discovery { background: rgba(59, 130, 246, 0.2); color: #93c5fd; border: 1px solid #3b82f6; }
.phase-investigation { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid #f59e0b; }
.phase-escalation { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid #ef4444; }
.phase-revelation { background: rgba(168, 85, 247, 0.2); color: #e9d5ff; border: 1px solid #a855f7; }
.phase-trial { background: rgba(234, 179, 8, 0.2); color: #fde047; border: 1px solid #eab308; }
.modal-story-title-sub {
  font-family: var(--font-body, serif);
  font-style: italic;
  font-size: 0.88rem;
  color: #e2e8f0;
}
.modal-fracture-clue {
  background: rgba(147, 51, 234, 0.12);
  border-left: 2px solid #a855f7;
  padding: 0.4rem 0.6rem;
  font-size: 0.82rem;
  color: #e9d5ff;
  margin-top: 0.5rem;
  border-radius: 2px;
}
.modal-fracture-clue .clue-tag {
  font-weight: bold;
  color: #c084fc;
}

/* Seals Modal */
.seals-modal {
  max-width: 680px;
}
.seals-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
  max-height: 52vh;
  overflow-y: auto;
  padding-right: 0.3rem;
  margin-top: 1rem;
}
.seal-relic-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(12, 10, 16, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem;
  border-radius: 4px;
  opacity: 0.6;
}
.seal-relic-card.seal-recovered {
  opacity: 1;
  border-color: rgba(168, 85, 247, 0.6);
  background: rgba(25, 15, 35, 0.95);
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.25);
}
.seal-icon-box {
  font-size: 1.5rem;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.4);
  border-radius: 4px;
}
.seal-card-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}
.seal-card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.seal-ch-num {
  font-family: var(--font-pixel, monospace);
  font-size: 0.58rem;
  color: #94a3b8;
}
.seal-status-tag {
  font-family: var(--font-pixel, monospace);
  font-size: 0.52rem;
  padding: 0.15rem 0.35rem;
  border-radius: 2px;
}
.tag-restored { background: rgba(34, 197, 94, 0.2); color: #86efac; border: 1px solid #22c55e; }
.tag-distorted { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.seal-name {
  font-family: var(--font-body, serif);
  font-size: 0.88rem;
  color: #f1f5f9;
}
.seal-shard-info {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.76rem;
  color: #38bdf8;
}
.shard-hint {
  font-size: 0.72rem;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 680px) {
  .chapters-grid-list { grid-template-columns: 1fr; }
  .seals-grid { grid-template-columns: 1fr; }
  .map-top-bar { top: 60px; left: 0.6rem; right: 0.6rem; }
  .ch-nav-pill { padding: 0.3rem 0.5rem; gap: 0.4rem; }
  .chapter-bottom-summary { display: none; }
}

/* ── FINAL BOSS NODE (CHAPTER 12 EXCLUSIVE) ───────────────── */
.final-boss-node-wrapper {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  z-index: 25;
}
.node-pos-final-boss {
  top: 19%;
  left: calc(80% - 52px);
}
.final-boss-unit {
  position: relative;
  width: 120px;
  height: 135px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
}
.final-boss-portal-aura {
  position: absolute;
  top: 35%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 125px;
  height: 125px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.45) 0%, rgba(245, 158, 11, 0.25) 45%, rgba(139, 92, 246, 0.1) 70%, transparent 80%);
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.4s ease;
}
.final-boss-portal-aura.aura-active {
  opacity: 1;
  animation: creatorVortexPulse 2.8s infinite ease-in-out;
}
.final-boss-portal-aura.aura-completed {
  opacity: 0.9;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.4) 0%, rgba(16, 185, 129, 0.2) 50%, transparent 75%);
}
@keyframes creatorVortexPulse {
  0%, 100% {
    transform: translate(-50%, -50%) scale(1) rotate(0deg);
    filter: drop-shadow(0 0 15px rgba(225, 29, 72, 0.6));
  }
  50% {
    transform: translate(-50%, -50%) scale(1.15) rotate(180deg);
    filter: drop-shadow(0 0 25px rgba(245, 158, 11, 0.85));
  }
}
.final-boss-sprite-img {
  width: 95px;
  height: 95px;
  filter: drop-shadow(0 0 14px rgba(225, 29, 72, 0.8));
}
.final-boss-pill-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  white-space: nowrap;
}
.tag-fb-ready {
  border-color: #fb7185;
  color: #fff;
  background: linear-gradient(90deg, rgba(136, 19, 55, 0.95), rgba(159, 18, 57, 0.95));
  box-shadow: 0 0 16px rgba(225, 29, 72, 0.85);
  animation: finalBossPillPulse 2s infinite ease-in-out;
}
@keyframes finalBossPillPulse {
  0%, 100% { box-shadow: 0 0 12px rgba(225, 29, 72, 0.7); transform: scale(1); }
  50% { box-shadow: 0 0 22px rgba(251, 113, 133, 1); transform: scale(1.05); }
}
.tag-fb-completed {
  border-color: #f59e0b;
  color: #fde68a;
  background: rgba(30, 20, 5, 0.95);
  box-shadow: 0 0 12px rgba(245, 158, 11, 0.7);
}
.tag-fb-locked {
  border-color: #4b5563;
  color: #9ca3af;
  background: rgba(17, 24, 39, 0.95);
}
.final-boss-pedestal {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2px 8px;
  background: linear-gradient(180deg, #2b1117 0%, #0d0406 100%);
  border: 1.5px solid #881337;
  border-radius: 4px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.85);
  margin-top: -4px;
}
.final-boss-pedestal.pedestal-active {
  border-color: #f59e0b;
  box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
}
.fb-pedestal-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.5rem;
  letter-spacing: 0.5px;
  color: #fb7185;
}

/* ── DEFEAT RIBBON & RECOVERY MODAL STYLES (Rules 9, 10, 22) ── */
.adv-map-defeat-ribbon {
  position: absolute;
  top: 60px;
  left: 0;
  right: 0;
  z-index: 45;
  background: linear-gradient(90deg, rgba(136, 19, 55, 0.95) 0%, rgba(30, 7, 13, 0.95) 50%, rgba(136, 19, 55, 0.95) 100%);
  border-top: 1px solid rgba(244, 63, 94, 0.6);
  border-bottom: 1.5px solid rgba(244, 63, 94, 0.8);
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.85);
  padding: 0.4rem 1.25rem;
}
.ribbon-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}
.ribbon-skull {
  font-size: 1.4rem;
  filter: drop-shadow(0 0 6px rgba(244, 63, 94, 0.8));
}
.ribbon-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  flex: 1;
}
.ribbon-info strong {
  font-size: 0.62rem;
  color: #fecdd3;
  letter-spacing: 0.05em;
}
.ribbon-sub {
  font-size: 0.72rem;
  color: #fda4af;
  font-family: 'Outfit', sans-serif;
}
.ribbon-btn {
  white-space: nowrap;
  box-shadow: 0 0 8px rgba(225, 29, 72, 0.5);
}

/* Defeat Alert inside Stage Modal */
.stage-defeat-alert-box {
  background: rgba(30, 8, 14, 0.95);
  border: 1.5px solid rgba(225, 29, 72, 0.7);
  box-shadow: 0 0 16px rgba(225, 29, 72, 0.25);
  border-radius: 6px;
  padding: 0.75rem;
  margin-top: 0.75rem;
}
.defeat-alert-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.5rem;
}
.defeat-alert-skull {
  font-size: 1.3rem;
}
.defeat-alert-title {
  font-size: 0.65rem;
  color: #f43f5e;
  margin: 0;
  letter-spacing: 0.06em;
}
.defeat-alert-subtitle {
  font-size: 0.75rem;
  color: #fecdd3;
  margin: 0.15rem 0 0;
  line-height: 1.3;
}
.defeat-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.4rem;
  margin-top: 0.5rem;
}
.stat-cell {
  background: rgba(15, 4, 7, 0.8);
  border: 1px solid rgba(225, 29, 72, 0.35);
  border-radius: 4px;
  padding: 0.35rem 0.4rem;
  text-align: center;
}
.cell-label {
  display: block;
  font-size: 0.45rem;
  color: #9ca3af;
  margin-bottom: 0.2rem;
  letter-spacing: 0.04em;
}
.cell-val {
  font-size: 0.6rem;
}
.text-red { color: #f87171; }
.text-gold { color: #fbbf24; }
.text-teal { color: #2dd4bf; }
.text-crimson { color: #f43f5e; }

.btn-exhausted {
  background: #3f0d18 !important;
  border-color: #881337 !important;
  color: #fda4af !important;
  box-shadow: none !important;
}

/* Defeat & Recovery Modal */
.defeat-recovery-modal {
  max-width: 520px;
}
.defeat-hero-icon-wrap {
  text-align: center;
  font-size: 2.8rem;
  margin: 0.5rem 0;
  filter: drop-shadow(0 0 16px rgba(244, 63, 94, 0.75));
}
.defeat-recovery-dashboard {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1.25rem 0 0.5rem;
}
.recovery-metric-card {
  background: rgba(20, 6, 12, 0.85);
  border: 1px solid rgba(225, 29, 72, 0.4);
  border-radius: 6px;
  padding: 0.65rem 0.85rem;
}
.metric-label {
  font-size: 0.52rem;
  color: #9ca3af;
  display: block;
  margin-bottom: 0.25rem;
  letter-spacing: 0.05em;
}
.metric-val {
  font-size: 0.85rem;
  margin-bottom: 0.25rem;
}
.metric-sub {
  font-size: 0.72rem;
  color: #94a3b8;
  display: block;
}
.health-mini-bar-track {
  width: 100%;
  height: 6px;
  background: rgba(0, 0, 0, 0.6);
  border: 1px solid rgba(225, 29, 72, 0.5);
  border-radius: 999px;
  overflow: hidden;
  margin-top: 0.35rem;
}
.health-mini-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #e11d48, #22c55e);
  transition: width 0.3s ease;
}
.badge-crimson {
  background: rgba(225, 29, 72, 0.25);
  border-color: rgba(225, 29, 72, 0.7);
  color: #fecdd3;
}

/* Regional Minion Encounter Preview */
.modal-regional-minion-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: rgba(18, 6, 14, 0.75);
  border: 1px solid rgba(225, 29, 72, 0.35);
  border-radius: 8px;
  padding: 0.6rem 0.85rem;
  margin: 0.85rem 0 0.5rem;
  box-shadow: inset 0 0 12px rgba(0, 0, 0, 0.6);
}
.minion-preview-avatar {
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.25) 0%, rgba(10, 3, 7, 0.8) 70%);
  border: 1px solid rgba(244, 63, 94, 0.4);
  border-radius: 8px;
  flex-shrink: 0;
}
.minion-preview-thumb {
  max-width: 44px;
  max-height: 44px;
  object-fit: contain;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.8));
}
.minion-preview-text {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}
.minion-preview-tag {
  font-size: 0.46rem;
  color: #f43f5e;
  letter-spacing: 0.08em;
}
.minion-preview-name {
  font-family: 'Cinzel', serif;
  font-size: 0.88rem;
  font-weight: 700;
  color: #f8fafc;
  line-height: 1.1;
}
.minion-preview-lore {
  font-size: 0.72rem;
  color: #cbd5e1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

<style>
/* ══════════════════════════════════════════════════════════════
   ADVENTURE MAP — MORNING / LIGHT MODE STYLES
   Preserves the map background artwork 100% while styling UI.
   ══════════════════════════════════════════════════════════════ */


[data-theme="morning"] .mind-seals-codex-medal,
.theme-morning .mind-seals-codex-medal {
  border-left-color: #8F642D !important;
}

[data-theme="morning"] .adv-health-recovery-badge,
.theme-morning .adv-health-recovery-badge {
  border-left: 3px solid #16a34a !important;
  border-color: #B88A42 !important;
}


[data-theme="morning"] .adv-health-recovery-badge .health-regen-sub,
.theme-morning .adv-health-recovery-badge .health-regen-sub {
  color: #166534 !important;
  text-shadow: none !important;
}

/* Chapter Switcher Center Pill */
[data-theme="morning"] .chapter-nav-pill,
.theme-morning .chapter-nav-pill {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  box-shadow: 0 4px 16px rgba(143, 100, 45, 0.15) !important;
}

[data-theme="morning"] .ch-nav-num,
.theme-morning .ch-nav-num {
  color: #991b1b !important;
}

[data-theme="morning"] .ch-nav-story-highlight,
.theme-morning .ch-nav-story-highlight {
  color: #8F642D !important;
}

[data-theme="morning"] .ch-nav-title,
.theme-morning .ch-nav-title {
  color: #3D2B1F !important;
}

[data-theme="morning"] .ch-nav-act-tag,
.theme-morning .ch-nav-act-tag {
  color: #8F642D !important;
}

[data-theme="morning"] .ch-status-badge-inline,
.theme-morning .ch-status-badge-inline {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
  color: #8F642D !important;
}

[data-theme="morning"] .ch-badge-completed,
.theme-morning .ch-badge-completed {
  color: #166534 !important;
  border-color: #16a34a !important;
}

[data-theme="morning"] .ch-badge-boss_available,
.theme-morning .ch-badge-boss_available {
  color: #991b1b !important;
  border-color: #dc2626 !important;
}

[data-theme="morning"] .ch-nav-btn,
.theme-morning .ch-nav-btn {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .ch-nav-btn:hover:not(:disabled),
.theme-morning .ch-nav-btn:hover:not(:disabled) {
  background: #B88A42 !important;
  color: #FFFDF7 !important;
}

[data-theme="morning"] .story-prologue-btn,
.theme-morning .story-prologue-btn,
[data-theme="morning"] .all-chapters-btn,
.theme-morning .all-chapters-btn {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.12) !important;
}

[data-theme="morning"] .story-prologue-btn:hover,
.theme-morning .story-prologue-btn:hover,
[data-theme="morning"] .all-chapters-btn:hover,
.theme-morning .all-chapters-btn:hover {
  background: #EAD8B5 !important;
  border-color: #8F642D !important;
  color: #8F642D !important;
}

/* Black Archive Top Button */
[data-theme="morning"] .black-archive-top-btn,
.theme-morning .black-archive-top-btn {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 4px 12px rgba(143, 100, 45, 0.15) !important;
}

[data-theme="morning"] .black-archive-top-btn.is-completed,
.theme-morning .black-archive-top-btn.is-completed {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .black-archive-top-btn.is-locked,
.theme-morning .black-archive-top-btn.is-locked {
  background: #EAD8B5 !important;
  border-color: #c4b59d !important;
  color: #77634F !important;
}

[data-theme="morning"] .black-archive-top-btn .ba-key-text,
.theme-morning .black-archive-top-btn .ba-key-text {
  color: #3D2B1F !important;
}

[data-theme="morning"] .black-archive-top-btn .ba-status-badge,
.theme-morning .black-archive-top-btn .ba-status-badge {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
  color: #166534 !important;
}

/* Quest Objective Banner */
[data-theme="morning"] .quest-objective-banner,
.theme-morning .quest-objective-banner {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  border-left: 3px solid #8F642D !important;
  box-shadow: 0 4px 12px rgba(143, 100, 45, 0.15) !important;
}

[data-theme="morning"] .quest-objective-banner .objective-title,
.theme-morning .quest-objective-banner .objective-title {
  color: #8F642D !important;
}

[data-theme="morning"] .quest-objective-banner .objective-sub,
.theme-morning .quest-objective-banner .objective-sub {
  color: #77634F !important;
}

[data-theme="morning"] .quest-objective-banner .objective-icon-box,
.theme-morning .quest-objective-banner .objective-icon-box {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
  color: #8F642D !important;
}

/* Modals */
[data-theme="morning"] .rpg-stage-modal,
.theme-morning .rpg-stage-modal,
[data-theme="morning"] .stage-modal-card,
.theme-morning .stage-modal-card {
  background: #FFF4DF !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 12px 36px rgba(143, 100, 45, 0.22) !important;
}

[data-theme="morning"] .stage-modal-title,
.theme-morning .stage-modal-title {
  color: #3D2B1F !important;
}

[data-theme="morning"] .stage-topic-desc,
.theme-morning .stage-topic-desc {
  color: #77634F !important;
}

[data-theme="morning"] .chapter-grid-card,
.theme-morning .chapter-grid-card {
  background: #FFFDF7 !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

[data-theme="morning"] .chapter-grid-card .ch-card-num,
.theme-morning .chapter-grid-card .ch-card-num {
  color: #8F642D !important;
}

[data-theme="morning"] .chapter-grid-card .ch-card-name,
.theme-morning .chapter-grid-card .ch-card-name {
  color: #3D2B1F !important;
}

[data-theme="morning"] .ch-card-act-pill,
.theme-morning .ch-card-act-pill {
  background: #EAD8B5 !important;
  color: #8F642D !important;
  border: 1px solid #B88A42 !important;
}

[data-theme="morning"] .seal-relic-card,
.theme-morning .seal-relic-card {
  background: #FFFDF7 !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .seal-relic-card .seal-name,
.theme-morning .seal-relic-card .seal-name {
  color: #3D2B1F !important;
}

/* Stage Modal Header & Badges */
[data-theme="morning"] .stage-code-badge,
.theme-morning .stage-code-badge,
[data-theme="morning"] .modal-stage-code,
.theme-morning .modal-stage-code {
  background: #EAD8B5 !important;
  border: 1px solid #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .modal-close-btn,
.theme-morning .modal-close-btn,
[data-theme="morning"] .close-modal-btn,
.theme-morning .close-modal-btn {
  color: #77634F !important;
}

[data-theme="morning"] .modal-close-btn:hover,
.theme-morning .modal-close-btn:hover,
[data-theme="morning"] .close-modal-btn:hover,
.theme-morning .close-modal-btn:hover {
  color: #2D1E12 !important;
}

/* Stage Modal Narrative Phase & Subtitle */
[data-theme="morning"] .modal-story-title-sub,
.theme-morning .modal-story-title-sub {
  color: #5A4535 !important;
}

[data-theme="morning"] .modal-phase-pill.phase-discovery,
.theme-morning .modal-phase-pill.phase-discovery {
  background: #EFF6FF !important;
  color: #1D4ED8 !important;
  border: 1px solid #3B82F6 !important;
}

[data-theme="morning"] .modal-phase-pill.phase-investigation,
.theme-morning .modal-phase-pill.phase-investigation {
  background: #FEF3C7 !important;
  color: #B45309 !important;
  border: 1px solid #D97706 !important;
}

[data-theme="morning"] .modal-phase-pill.phase-escalation,
.theme-morning .modal-phase-pill.phase-escalation {
  background: #FEE2E2 !important;
  color: #B91C1C !important;
  border: 1px solid #EF4444 !important;
}

[data-theme="morning"] .modal-phase-pill.phase-revelation,
.theme-morning .modal-phase-pill.phase-revelation {
  background: #F3E8FF !important;
  color: #6B21A8 !important;
  border: 1px solid #A855F7 !important;
}

[data-theme="morning"] .modal-phase-pill.phase-trial,
.theme-morning .modal-phase-pill.phase-trial {
  background: #FEF9C3 !important;
  color: #854D0E !important;
  border: 1px solid #CA8A04 !important;
}

[data-theme="morning"] .modal-fracture-clue,
.theme-morning .modal-fracture-clue {
  background: #F3E8FF !important;
  border-left: 3px solid #7E22CE !important;
  color: #581C87 !important;
}

[data-theme="morning"] .modal-fracture-clue .clue-tag,
.theme-morning .modal-fracture-clue .clue-tag {
  color: #6B21A8 !important;
}

/* Stage Modal Regional Foe Row */
[data-theme="morning"] .modal-regional-minion-row,
.theme-morning .modal-regional-minion-row {
  background: #FAF2E1 !important;
  border: 1.5px solid #D4C3A3 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

[data-theme="morning"] .modal-regional-minion-row .minion-preview-avatar,
.theme-morning .modal-regional-minion-row .minion-preview-avatar {
  background: #F3E4C8 !important;
  border: 1.5px solid #C8B28C !important;
}

[data-theme="morning"] .modal-regional-minion-row .minion-preview-tag,
.theme-morning .modal-regional-minion-row .minion-preview-tag {
  color: #B91C1C !important;
}

[data-theme="morning"] .modal-regional-minion-row .minion-preview-name,
.theme-morning .modal-regional-minion-row .minion-preview-name {
  color: #2D1E12 !important;
}

[data-theme="morning"] .modal-regional-minion-row .minion-preview-lore,
.theme-morning .modal-regional-minion-row .minion-preview-lore {
  color: #5A4535 !important;
}

/* Stage Modal Estimated Rewards Preview */
[data-theme="morning"] .stage-rewards-preview,
.theme-morning .stage-rewards-preview {
  background: #FAF2E1 !important;
  border: 1.5px solid #D4C3A3 !important;
  box-shadow: 0 2px 8px rgba(143, 100, 45, 0.08) !important;
}

[data-theme="morning"] .stage-rewards-preview .rewards-sec-title,
.theme-morning .stage-rewards-preview .rewards-sec-title {
  color: #8F642D !important;
}

[data-theme="morning"] .stage-rewards-preview .reward-chip,
.theme-morning .stage-rewards-preview .reward-chip {
  background: #EEDFC3 !important;
  border: 1px solid #CDBA93 !important;
  color: #2D1E12 !important;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
}

[data-theme="morning"] .stage-rewards-preview .reward-chip.xp-chip,
.theme-morning .stage-rewards-preview .reward-chip.xp-chip {
  color: #92400E !important;
  font-weight: 700 !important;
}

[data-theme="morning"] .stage-rewards-preview .reward-chip.coin-chip,
.theme-morning .stage-rewards-preview .reward-chip.coin-chip {
  color: #3D2B1F !important;
  font-weight: 700 !important;
}

[data-theme="morning"] .stage-lock-notice,
.theme-morning .stage-lock-notice {
  background: #FEF2F2 !important;
  border: 1px solid #FCA5A5 !important;
  color: #991B1B !important;
}

[data-theme="morning"] .stage-modal-actions .btn-gothic-stone,
.theme-morning .stage-modal-actions .btn-gothic-stone {
  background: #EAD8B5 !important;
  border: 1.5px solid #B88A42 !important;
  color: #3D2B1F !important;
}

[data-theme="morning"] .stage-modal-actions .btn-gothic-stone:hover,
.theme-morning .stage-modal-actions .btn-gothic-stone:hover {
  background: #DFCDA5 !important;
  color: #1F140A !important;
}
</style>
