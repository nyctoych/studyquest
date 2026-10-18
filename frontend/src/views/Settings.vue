<template>
  <div class="settings-page animate-slide-up">
    <!-- Header -->
    <div class="settings-header">
      <div>
        <span class="settings-tag">⚙️ System Configuration</span>
        <h1 class="page-title">Settings &amp; Preferences</h1>
        <p class="page-subtitle">Configure audio feedback, visual themes, study notifications, and account credentials.</p>
      </div>
    </div>

    <div class="settings-layout">
      <!-- Left Column: Navigation sections -->
      <div class="glass-card settings-sections-card">
        <!-- Audio & SFX -->
        <div class="settings-section">
          <h3 class="section-heading">🎵 Audio &amp; Sound FX</h3>
          <p class="section-desc">Manage combat fanfare, quest completion sounds, and ambient atmospheric audio.</p>

          <div class="toggle-row">
            <div class="toggle-info">
              <strong>Sound Effects (SFX)</strong>
              <small>Plays sounds on quest completes, level ups, and combat hits.</small>
            </div>
            <label class="switch">
              <input type="checkbox" v-model="settings.sfxEnabled" @change="onSfxToggle" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="toggle-row">
            <div class="toggle-info">
              <strong>Atmospheric Background Music</strong>
              <small>Dynamic RPG music across Landing, World Hub, Adventure, and Battlefield.</small>
            </div>
            <label class="switch">
              <input type="checkbox" v-model="settings.musicEnabled" @change="onMusicToggle" />
              <span class="slider"></span>
            </label>
          </div>

          <div class="volume-row">
            <div class="vol-label-row">
              <span class="vol-label">Master Volume</span>
              <span class="vol-percent">{{ settings.volume }}%</span>
            </div>
            <input
              type="range"
              min="0"
              max="100"
              v-model.number="settings.volume"
              class="range-slider"
              @input="onVolumeInput"
              @change="onVolumeChange"
            />
          </div>
        </div>

        <hr class="card-divider" />

        <!-- Notification & Study Reminders -->
        <div class="settings-section">
          <h3 class="section-heading">🔔 Study Notifications</h3>
          <p class="section-desc">Keep your streak intact with timely quest reminders.</p>

          <div class="toggle-row">
            <div class="toggle-info">
              <strong>Daily Streak Protection Alert</strong>
              <small>Receive reminders 2 hours before midnight if no quests are done.</small>
            </div>
            <label class="switch">
              <input type="checkbox" v-model="settings.streakReminder" @change="saveSettings" />
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <hr class="card-divider" />

        <!-- Security & Password -->
        <div class="settings-section">
          <h3 class="section-heading">🔐 Account &amp; Password</h3>
          <p class="section-desc">Update your security password.</p>

          <form @submit.prevent="updatePassword" class="password-form">
            <div class="form-group">
              <label class="form-label" for="curr-pass">Current Password</label>
              <input
                id="curr-pass"
                type="password"
                v-model="passForm.currentPassword"
                class="form-input"
                placeholder="••••••••"
                required
              />
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="new-pass">New Password</label>
                <input
                  id="new-pass"
                  type="password"
                  v-model="passForm.newPassword"
                  class="form-input"
                  placeholder="Min 6 characters"
                  required
                />
              </div>
              <div class="form-group flex-1">
                <label class="form-label" for="confirm-new-pass">Confirm Password</label>
                <input
                  id="confirm-new-pass"
                  type="password"
                  v-model="passForm.confirmPassword"
                  class="form-input"
                  placeholder="Confirm new password"
                  required
                />
              </div>
            </div>

            <button type="submit" class="btn btn-secondary btn-sm" :disabled="updatingPass">
              {{ updatingPass ? 'Updating...' : 'Update Password' }}
            </button>
          </form>
        </div>


      </div>

      <!-- Right Column: System Info & Danger Zone -->
      <div class="settings-side-col">
        <div class="glass-card info-card">
          <h3 class="side-title">✦ StudyQuest System</h3>
          <div class="sys-specs">
            <div class="spec-line">
              <span class="k">Version</span>
              <span class="v">v1.0.0 (Release)</span>
            </div>
            <div class="spec-line">
              <span class="k">Engine</span>
              <span class="v">Vue 3 + Phaser 4 + PHP</span>
            </div>
            <div class="spec-line">
              <span class="k">State</span>
              <span class="v text-green">● Connected</span>
            </div>
            <div class="spec-line">
              <span class="k">Database</span>
              <span class="v">MySQL (studyquest)</span>
            </div>
          </div>
        </div>

        <div class="glass-card tutorial-card">
          <h3 class="side-title text-gold font-pixel">🧭 Onboarding &amp; Tutorial</h3>
          <p class="section-desc" style="margin-bottom: 0.75rem;">
            Revisit the Battlefield basics, attribute allocations, and complete 11-module interactive tour of Mindrealm anytime.
          </p>
          <button type="button" class="btn btn-gothic-gold w-full font-pixel" @click="replayTutorial">
            ↺ REPLAY ONBOARDING TUTORIAL
          </button>
        </div>

        <div class="glass-card danger-card">
          <h3 class="side-title text-danger">⚠️ Danger Zone</h3>
          <p class="danger-desc">
            Logging out will clear your active session. You can log back in anytime to resume your quest progress.
          </p>
          <button class="btn btn-danger w-full" @click="handleLogout">
            🚪 Logout of StudyQuest
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'
import { useGameStore } from '../stores/game'
import { useTutorialStore } from '../stores/tutorial'
import { battleAudio } from '../services/battleAudio'

const router = useRouter()
const auth   = useAuthStore()
const toast  = useToastStore()
const gameStore = useGameStore()
const tutorialStore = useTutorialStore()

function replayTutorial() {
  tutorialStore.replayTutorial()
}

const settings = ref({
  sfxEnabled: gameStore.audioEnabled,
  musicEnabled: gameStore.musicEnabled,
  volume: gameStore.masterVolume,
  streakReminder: true
})

const passForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const updatingPass = ref(false)

function onSfxToggle() {
  gameStore.setAudio(settings.value.sfxEnabled)
  if (settings.value.sfxEnabled) {
    battleAudio.playSelect()
  }
}

function onMusicToggle() {
  gameStore.setMusic(settings.value.musicEnabled)
}

function onVolumeInput() {
  gameStore.setMasterVolume(settings.value.volume)
}

function onVolumeChange() {
  gameStore.setMasterVolume(settings.value.volume)
  if (settings.value.sfxEnabled) {
    battleAudio.playSelect()
  }
}

function saveSettings() {
  gameStore.setAudio(settings.value.sfxEnabled)
  gameStore.setMusic(settings.value.musicEnabled)
  gameStore.setMasterVolume(settings.value.volume)
  localStorage.setItem('studyquest_settings', JSON.stringify(settings.value))
  toast.add('⚙️ Settings saved!', 'info')
}

async function updatePassword() {
  if (passForm.value.newPassword !== passForm.value.confirmPassword) {
    toast.add('New passwords do not match!', 'error')
    return
  }
  if (passForm.value.newPassword.length < 6) {
    toast.add('Password must be at least 6 runes long!', 'error')
    return
  }

  updatingPass.value = true
  const res = await auth.updatePassword(passForm.value.currentPassword, passForm.value.newPassword)
  updatingPass.value = false

  if (res?.success) {
    toast.success('🔒 Password updated successfully!')
    passForm.value = { currentPassword: '', newPassword: '', confirmPassword: '' }
  } else {
    toast.error(res?.message || 'Failed to update password')
  }
}

async function handleLogout() {
  await auth.logout()
  toast.add('Logged out. See you next quest!', 'info')
  router.push('/login')
}

onMounted(() => {
  const saved = localStorage.getItem('studyquest_settings')
  if (saved) {
    try {
      settings.value = { ...settings.value, ...JSON.parse(saved) }
    } catch (e) {}
  }
  // Synchronize with active gameStore
  settings.value.sfxEnabled = gameStore.audioEnabled
  settings.value.musicEnabled = gameStore.musicEnabled
  settings.value.volume = gameStore.masterVolume
})
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #e2d9cc;
}

.settings-header {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.settings-tag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
  color: #e6c845;
  letter-spacing: 0.08em;
  margin-bottom: 0.35rem;
}

.page-title {
  font-family: 'Cinzel', serif;
  font-size: 2rem;
  font-weight: 700;
  color: #f3ede2;
  letter-spacing: 0.05em;
  text-shadow: 0 0 15px rgba(181, 148, 16, 0.2);
}

.page-subtitle {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.15rem;
}

.settings-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.5rem;
  align-items: flex-start;
}

@media (max-width: 900px) {
  .settings-layout {
    grid-template-columns: 1fr;
  }
}

.settings-sections-card {
  padding: 2rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.settings-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.section-heading {
  font-family: 'Cinzel', serif;
  font-size: 1.2rem;
  font-weight: 700;
  color: #f3ede2;
}

.section-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  margin-bottom: 0.5rem;
}

.toggle-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
}

.toggle-info strong {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  color: #f3ede2;
}

.toggle-info small {
  display: block;
  font-size: 0.8rem;
  color: #8c887b;
}

.volume-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding: 0.85rem;
  background: rgba(15, 16, 23, 0.7);
  border: 1px solid #2e3040;
  border-radius: 4px;
}

.vol-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.vol-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #e6c845;
}

.vol-percent {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.62rem;
  color: #fbbf24;
}

.range-slider {
  width: 100%;
  height: 8px;
  background: #10111a;
  border: 1px solid #3a3b4c;
  border-radius: 4px;
  accent-color: #e6c845;
  cursor: pointer;
  outline: none;
  transition: all 0.15s ease;
}
.range-slider:hover {
  border-color: #e6c845;
  box-shadow: 0 0 10px rgba(230, 200, 69, 0.3);
}

/* Custom switch toggle */
.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background-color: #09090e;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608;
  transition: .3s;
  border-radius: 2px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: #8c887b;
  transition: .3s;
  border-radius: 2px;
}

input:checked + .slider {
  background-color: #1c1825;
  border-color: #b59410;
  box-shadow: 0 0 8px rgba(181, 148, 16, 0.4);
}

input:checked + .slider:before {
  transform: translateX(22px);
  background-color: #e6c845;
}

.card-divider {
  border: none;
  border-top: 1px solid #1f202d;
  margin: 1.5rem 0;
}

.password-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  max-width: 500px;
}

.settings-side-col {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card, .danger-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.side-title {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 1rem;
}

.sys-specs {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.spec-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
}

.spec-line .k {
  color: #8c887b;
  font-family: 'VT323', monospace;
  font-size: 1.1rem;
}
.spec-line .v {
  color: #f3ede2;
  font-weight: 600;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.65rem;
}
.text-green { color: #34d399 !important; }

.danger-card {
  border-color: #8b1a1a;
  box-shadow: 0 0 0 2px #050608, 0 0 20px rgba(139, 26, 26, 0.25);
}

.danger-desc {
  font-size: 0.85rem;
  color: #9ca3af;
  line-height: 1.4;
  margin-bottom: 1.25rem;
}

.w-full {
  width: 100%;
}

</style>
