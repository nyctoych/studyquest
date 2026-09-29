<template>
  <div class="auth-screen">
    <!-- Atmospheric background -->
    <div class="auth-bg">
      <div class="auth-bg-noise"></div>
      <div class="auth-bg-vignette"></div>
      <!-- Faint boss silhouettes -->
      <div class="auth-boss-left">
        <img src="/assets/bosses/skull/happy.png" class="pixel-art auth-boss-ghost" alt="" />
      </div>
      <div class="auth-boss-right">
        <img src="/assets/bosses/shadows/happy.png" class="pixel-art auth-boss-ghost" alt="" />
      </div>
    </div>

    <!-- Gothic panel -->
    <div class="auth-panel animate-scale-up">
      <!-- Corner runes -->
      <div class="auth-corner tl"></div>
      <div class="auth-corner tr"></div>
      <div class="auth-corner bl"></div>
      <div class="auth-corner br"></div>

      <!-- Logo -->
      <div class="auth-logo">
        <div class="auth-psi-wrap">
          <div class="auth-psi">Ψ</div>
        </div>
        <div>
          <h1 class="auth-brand">STUDYQUEST</h1>
          <p class="auth-tagline">WELCOME BACK, SCHOLAR</p>
        </div>
      </div>

      <!-- Gothic separator -->
      <div class="gothic-sep gothic-sep-crimson" style="margin: 0 0 1.5rem;"></div>

      <!-- Tabs -->
      <div class="auth-tabs">
        <span class="auth-tab active">LOGIN</span>
        <router-link to="/register" class="auth-tab">REGISTER</router-link>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label class="form-label" for="login-id">Username or Email</label>
          <input id="login-id" v-model="form.identifier" type="text" class="form-input gothic-input"
            placeholder="Enter username or email" autocomplete="username" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="login-pw">Password</label>
          <div class="input-group">
            <input id="login-pw" v-model="form.password" :type="showPw ? 'text' : 'password'"
              class="form-input gothic-input" placeholder="Your password" autocomplete="current-password" required />
            <button type="button" class="pw-toggle" @click="showPw = !showPw"
              :aria-label="showPw ? 'Hide password' : 'Show password'">
              {{ showPw ? '🙈' : '👁️' }}
            </button>
          </div>
        </div>

        <div v-if="error" class="auth-error">
          <span class="auth-error-icon">⚠</span> {{ error }}
        </div>

        <button id="login-submit" type="submit" class="btn btn-primary btn-lg auth-submit" :disabled="loading">
          <span v-if="loading">ENTERING THE WORLD…</span>
          <span v-else>⚔ ENTER THE WORLD</span>
        </button>
      </form>

      <!-- Gothic separator -->
      <div class="gothic-sep" style="margin: 1.25rem 0 0.75rem;"></div>

      <p class="auth-switch">
        New to StudyQuest?
        <router-link to="/register" class="auth-link">Create Account →</router-link>
      </p>

      <router-link to="/" class="back-to-title">← BACK TO TITLE</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const router = useRouter()
const auth   = useAuthStore()
const toast  = useToastStore()

const form    = ref({ identifier: '', password: '' })
const error   = ref('')
const loading = ref(false)
const showPw  = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  const result = await auth.login(form.value.identifier, form.value.password)
  loading.value = false

  if (result.success) {
    toast.success('Welcome back, ' + auth.nickname + '! ⚔️')
    router.push(auth.isNewPlayer ? '/tutorial' : '/world')
  } else {
    error.value = result.message
  }
}
</script>

<style scoped>
/* ── SCREEN ─────────────────────────────────── */
.auth-screen {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
  overflow: hidden;
  background: var(--gothic-void);
}

/* ── BACKGROUND ──────────────────────────────── */
.auth-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.auth-bg-noise {
  position: absolute;
  inset: 0;
  background-image:
    radial-gradient(ellipse at 25% 60%, rgba(139,26,26,0.07) 0%, transparent 55%),
    radial-gradient(ellipse at 75% 30%, rgba(74,26,107,0.06) 0%, transparent 45%);
}
.auth-bg-vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 30%, rgba(5,6,10,0.95) 100%);
}
.auth-boss-left,
.auth-boss-right {
  position: absolute;
  bottom: 0;
  opacity: 0.07;
}
.auth-boss-left  { left: -60px; transform: scaleX(-1); }
.auth-boss-right { right: -60px; }
.auth-boss-ghost {
  width: 280px;
  height: auto;
  filter: brightness(0.2) saturate(0);
}

/* ── GOTHIC PANEL ────────────────────────────── */
.auth-panel {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 420px;
  padding: 2.25rem;
  background: var(--gothic-surface);
  border: 1px solid var(--gothic-border-lit);
  box-shadow: var(--shadow-gothic), inset 0 1px 0 rgba(255,255,255,0.03);
}

/* Pixel corner decorations */
.auth-corner {
  position: absolute;
  width: 10px;
  height: 10px;
  background: var(--crimson);
}
.auth-corner.tl { top: -2px; left: -2px; }
.auth-corner.tr { top: -2px; right: -2px; }
.auth-corner.bl { bottom: -2px; left: -2px; }
.auth-corner.br { bottom: -2px; right: -2px; }

/* ── LOGO ────────────────────────────────────── */
.auth-logo {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}
.auth-psi-wrap {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  border: 2px solid var(--crimson);
  background: var(--gothic-deep);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  animation: crimsonThrob 3s ease-in-out infinite;
}
.auth-psi {
  font-size: 1.6rem;
  font-family: serif;
  color: var(--bone-white);
}
.auth-brand {
  font-family: var(--font-pixel) !important;
  font-size: var(--px-md) !important;
  color: var(--bone-white);
  letter-spacing: 0.08em;
  margin-bottom: 0.25rem;
}
.auth-tagline {
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  color: var(--text-muted);
  letter-spacing: 0.06em;
}

/* ── TABS ────────────────────────────────────── */
.auth-tabs {
  display: flex;
  gap: 2px;
  background: var(--gothic-void);
  border: 1px solid var(--gothic-border);
  padding: 2px;
  margin-bottom: 1.5rem;
}
.auth-tab {
  flex: 1;
  text-align: center;
  padding: 0.5rem;
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  cursor: pointer;
  color: var(--text-muted);
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  letter-spacing: 0.06em;
}
.auth-tab.active {
  background: var(--crimson-deep);
  color: var(--bone-white);
  border: 1px solid var(--crimson);
}
.auth-tab:not(.active):hover { color: var(--text-primary); background: var(--gothic-raised); }

/* ── FORM ────────────────────────────────────── */
.auth-form { display: flex; flex-direction: column; gap: 1rem; }
.input-group { position: relative; }
.input-group .form-input { padding-right: 3rem; }
.pw-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: var(--text-muted);
  padding: 0;
  transition: color 0.15s;
}
.pw-toggle:hover { color: var(--text-primary); }

.auth-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ff8080;
  font-family: var(--font-mono);
  font-size: 0.82rem;
  background: var(--crimson-void);
  border: 1px solid var(--crimson-deep);
  padding: 0.6rem 0.875rem;
  border-left: 3px solid var(--crimson);
}
.auth-error-icon { color: var(--crimson-bright); }

.auth-submit { width: 100%; justify-content: center; margin-top: 0.25rem; }

/* ── FOOTER ──────────────────────────────────── */
.auth-switch {
  text-align: center;
  margin-top: 0.5rem;
  font-family: var(--font-gothic);
  font-size: 0.9rem;
  color: var(--text-muted);
}
.auth-link {
  color: var(--bone-white);
  font-weight: 600;
  text-decoration: none;
  transition: color 0.15s;
}
.auth-link:hover { color: var(--aged-gold-bright); text-decoration: underline; }

.back-to-title {
  display: block;
  text-align: center;
  margin-top: 0.75rem;
  font-family: var(--font-pixel);
  font-size: var(--px-xs);
  color: var(--text-muted);
  text-decoration: none;
  letter-spacing: 0.05em;
  transition: color 0.15s;
}
.back-to-title:hover { color: var(--bone-dim); }

@media (max-width: 480px) {
  .auth-panel { padding: 1.5rem 1.25rem; }
  .auth-boss-left, .auth-boss-right { display: none; }
}
</style>
