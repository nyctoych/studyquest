<template>
  <div class="profile-page animate-slide-up">
    <!-- Header -->
    <div class="profile-header">
      <div>
        <span class="profile-tag">📜 Scholar Identity</span>
        <h1 class="page-title">Adventurer Profile</h1>
        <p class="page-subtitle">
          Your scholarly records, battle rating, achievements, and identity across StudyQuest.
        </p>
      </div>

      <button class="btn btn-primary save-btn" :disabled="saving" @click="saveProfile">
        {{ saving ? 'Saving...' : '💾 Save Profile' }}
      </button>
    </div>

    <!-- Main Studio Grid -->
    <div class="studio-grid">
      <!-- Left Column: Character Portrait & Identity -->
      <div class="glass-card avatar-preview-card">
        <div class="avatar-stage">
          <div class="avatar-glow-ring">
            <AvatarImg :size="190" />
          </div>
          <div class="avatar-class-lock-badge" :class="`theme-${(auth.characterClass || 'mage').toLowerCase()}`">
            <span class="lock-icon">🔒</span>
            <span class="class-title">{{ characterClassDisplay }}</span>
          </div>
          <div class="avatar-lock-hint">PROFILE AVATAR IS FIXED • BOUND TO CHOSEN CLASS</div>
        </div>

        <div class="avatar-identity">
          <h2 class="username-display">{{ auth.nickname || auth.user?.username || 'Hero' }}</h2>
          <div class="title-pill">
            <span>🏷️ {{ selectedTitle || 'Scholar Novice' }}</span>
          </div>
          <div class="level-badge-large">
            Level {{ auth.level || 1 }} Psychology Explorer
          </div>
        </div>

        <div class="avatar-quick-actions flex flex-col gap-2">
          <router-link to="/character" class="btn btn-secondary btn-sm flex items-center gap-1.5">
            <PixelEmoji emoji="shield" size="xs" /> Character Hall (Cosmetics) →
          </router-link>
          <router-link to="/character-selection" class="btn btn-outline btn-sm flex items-center justify-center gap-1.5">
            ⚔️ Switch Vessel / Class →
          </router-link>
          <button type="button" class="btn btn-ghost btn-sm flex items-center justify-center gap-1.5" @click="replayTutorial">
            ↺ Replay Onboarding Tutorial
          </button>
        </div>

        <!-- Academic Stats Overview Mini Card -->
        <hr class="card-divider" />
        <div class="academic-stats-box">
          <div class="stat-row">
            <span class="k">⭐ Total XP</span>
            <span class="v">{{ formatXP(auth.xp) }}</span>
          </div>
          <div class="stat-row">
            <span class="k">🪙 Coin Treasury</span>
            <span class="v">{{ formatPoints(auth.points || auth.coins) }}</span>
          </div>
          <div class="stat-row">
            <span class="k">🔥 Study Streak</span>
            <span class="v">{{ auth.streak }} Days</span>
          </div>
          <div class="stat-row">
            <span class="k">⚔️ Battle Rating</span>
            <span class="v">{{ auth.battleRating || 1200 }}</span>
          </div>
          <div class="stat-row">
            <span class="k">📧 Email</span>
            <span class="v text-truncate">{{ auth.user?.email }}</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Profile Credentials & Customization -->
      <div class="glass-card controls-card">
        <h3 class="controls-title">⚙️ Identity &amp; Records</h3>

        <!-- Nickname Input -->
        <div class="control-group">
          <label class="control-label" for="nickname-input">Scholar Nickname</label>
          <input
            id="nickname-input"
            v-model="nicknameVal"
            type="text"
            maxlength="30"
            class="form-input"
            placeholder="e.g. Sigmund, NeoScholar..."
          />
          <small class="field-hint">This name will be displayed in multiplayer arenas and on leaderboards.</small>
        </div>

        <!-- Title Selector -->
        <div class="control-group">
          <label class="control-label" for="title-select">Equipped Title</label>
          <select id="title-select" v-model="selectedTitle" class="form-select">
            <option value="Scholar Novice">Scholar Novice</option>
            <option value="Grand Scholar">Grand Scholar</option>
            <option value="Dragon Slayer">Dragon Slayer</option>
            <option value="Master of Quests">Master of Quests</option>
            <option value="Psychology Contender">Psychology Contender</option>
            <option value="Board Exam Candidate">Board Exam Candidate</option>
          </select>
        </div>

        <!-- Bio / Study Motto -->
        <div class="control-group">
          <label class="control-label" for="motto-input">Academic Motto / Study Bio</label>
          <textarea
            id="motto-input"
            v-model="studyBio"
            rows="3"
            class="form-textarea"
            placeholder="e.g. Master the mind, conquer the boards..."
          ></textarea>
        </div>

        <!-- Active Cosmetics Showcase -->
        <div class="control-group">
          <label class="control-label">Active Cosmetic Equipment</label>
          <div class="active-cosmetics-grid">
            <div
              v-for="cat in cosmeticSlots"
              :key="cat.slug"
              class="cosmetic-slot-pill"
            >
              <span class="c-slot-name">{{ cat.name }}:</span>
              <span class="c-slot-val">{{ getEquippedName(cat.slug) }}</span>
            </div>
          </div>
        </div>

        <div class="profile-actions-bottom">
          <button class="btn btn-primary" :disabled="saving" @click="saveProfile">
            {{ saving ? 'Saving Changes...' : '💾 Save Profile Details' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { profileService } from '../services/profileService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useTutorialStore } from '../stores/tutorial'
import { formatXP, formatPoints } from '../utils/formatters'
import AvatarImg from '../components/AvatarImg.vue'

const auth = useAuthStore()
const toast = useToastStore()
const tutorialStore = useTutorialStore()

function replayTutorial() {
  tutorialStore.replayTutorial()
}

const nicknameVal = ref('')
const selectedTitle = ref('Scholar Novice')
const studyBio = ref('')
const saving = ref(false)

const characterClassDisplay = computed(() => {
  const cls = (auth.characterClass || 'MAGE').toUpperCase()
  if (cls === 'MELEE') return 'MELEE • DUAL-WIELDER'
  if (cls === 'RANGE') return 'RANGE • ARCHER'
  return 'MAGE • NECROMANCER'
})

const cosmeticSlots = [
  { slug: 'character_skin', name: 'Skin' },
  { slug: 'aura', name: 'Aura' },
  { slug: 'back_effect', name: 'Back Effect' },
  { slug: 'hit_effect', name: 'Hit Effect' },
  { slug: 'finisher_effect', name: 'Finisher' }
]

function getEquippedName(slug) {
  const item = auth.equipped?.find(i => i.category_slug === slug || i.slot === slug)
  return item?.name || 'Default'
}

async function saveProfile() {
  saving.value = true
  try {
    const payload = {
      nickname: nicknameVal.value || auth.user?.username,
      title: selectedTitle.value,
      bio: studyBio.value
    }

    await profileService.update(payload)
    toast.add('✨ Profile updated successfully!', 'success')
    await auth.fetchUser()
  } catch (err) {
    console.error('Failed to save profile', err)
    toast.add('Failed to save profile', 'error')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  nicknameVal.value = auth.nickname || auth.user?.username || ''
  if (auth.user?.title) selectedTitle.value = auth.user.title
  if (auth.user?.bio) studyBio.value = auth.user.bio
})
</script>

<style scoped>
.profile-page {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  position: relative;
  z-index: 1;
}

.profile-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1.25rem;
  padding: 1.5rem 1.75rem;
  background: linear-gradient(180deg, #16141a 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.35);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.8);
  border-radius: 4px;
}

.profile-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.4rem;
}

.page-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1.5rem;
  color: #e2d9cc;
  letter-spacing: 0.03em;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
}

.page-subtitle {
  color: #a09789;
  font-size: 0.95rem;
  max-width: 650px;
  margin-top: 0.35rem;
  line-height: 1.45;
}

.save-btn {
  padding: 0.75rem 1.5rem;
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  background: linear-gradient(180deg, #8b1a1a 0%, #4a0e0e 100%);
  color: #f5efe6;
  border: 2px solid #b91c1c;
  box-shadow: 0 0 0 2px #050608;
  cursor: pointer;
}

.save-btn:hover {
  background: linear-gradient(180deg, #b91c1c 0%, #6b1414 100%);
}

.studio-grid {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 1.5rem;
  align-items: flex-start;
}

@media (max-width: 900px) {
  .studio-grid {
    grid-template-columns: 1fr;
  }
}

.avatar-preview-card {
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
}

.avatar-stage {
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.avatar-class-lock-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.85rem;
  margin-top: 0.75rem;
  border-radius: 4px;
  font-family: var(--font-pixel, monospace);
  font-size: 0.65rem;
  letter-spacing: 0.08em;
  background: rgba(0, 0, 0, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.avatar-class-lock-badge.theme-mage {
  border-color: rgba(168, 85, 247, 0.6);
  color: #d8b4fe;
  box-shadow: 0 0 12px rgba(168, 85, 247, 0.25);
}

.avatar-class-lock-badge.theme-melee {
  border-color: rgba(59, 130, 246, 0.6);
  color: #93c5fd;
  box-shadow: 0 0 12px rgba(59, 130, 246, 0.25);
}

.avatar-class-lock-badge.theme-range {
  border-color: rgba(34, 197, 94, 0.6);
  color: #86efac;
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.25);
}

.avatar-lock-hint {
  display: block;
  font-family: var(--font-pixel, monospace);
  font-size: 0.55rem;
  color: #8a8894;
  letter-spacing: 0.06em;
  margin-top: 0.35rem;
}

.avatar-glow-ring {
  position: relative;
  display: inline-flex;
  padding: 10px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 26, 26, 0.25) 0%, rgba(12, 13, 18, 0) 70%);
}

.avatar-identity {
  margin-bottom: 1.25rem;
  width: 100%;
}

.username-display {
  font-family: var(--font-pixel, monospace);
  font-size: 1.25rem;
  color: #f3ece2;
  margin-bottom: 0.5rem;
  word-break: break-word;
}

.title-pill {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: rgba(181, 148, 16, 0.15);
  border: 1px solid rgba(181, 148, 16, 0.4);
  border-radius: 20px;
  font-size: 0.85rem;
  color: #dfb826;
  margin-bottom: 0.75rem;
}

.level-badge-large {
  font-family: var(--font-pixel, monospace);
  font-size: 0.7rem;
  color: #a8b3cf;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.avatar-quick-actions {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  width: 100%;
  justify-content: center;
}

.card-divider {
  width: 100%;
  border: none;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin: 0.5rem 0 1.25rem 0;
}

.academic-stats-box {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
  padding: 0.35rem 0.5rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 4px;
}

.stat-row .k {
  color: #8c857b;
}

.stat-row .v {
  font-weight: 700;
  color: #e2d9cc;
}

.controls-card {
  padding: 1.75rem;
  background: linear-gradient(180deg, #141318 0%, #0c0d12 100%);
  border: 1px solid rgba(181, 148, 16, 0.3);
  box-shadow: 0 0 0 2px #050608, 0 8px 25px rgba(0, 0, 0, 0.7);
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.controls-title {
  font-family: var(--font-pixel, monospace);
  font-size: 1rem;
  color: #dfb826;
  margin-bottom: 0.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 0.75rem;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.control-label {
  font-family: var(--font-pixel, monospace);
  font-size: 0.75rem;
  color: #a09789;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.field-hint {
  color: #64748b;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-input, .form-select, .form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #090a0e;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 4px;
  color: #f1ede6;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  border-color: #b59410;
  box-shadow: 0 0 8px rgba(181, 148, 16, 0.25);
}

.active-cosmetics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.cosmetic-slot-pill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 4px;
  font-size: 0.82rem;
}

.c-slot-name {
  color: #8c857b;
}

.c-slot-val {
  color: #38bdf8;
  font-weight: 600;
}

.profile-actions-bottom {
  margin-top: 0.5rem;
}
</style>
