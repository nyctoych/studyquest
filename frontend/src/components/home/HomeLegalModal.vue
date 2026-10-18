<template>
  <Teleport to="body">
    <div v-if="activeType" class="legal-modal-backdrop" @click.self="$emit('close')">
      <div class="legal-modal-card">
        <button class="modal-close" @click="$emit('close')" aria-label="Close modal">✕</button>

        <div class="modal-head">
          <div class="modal-badge">{{ activeType === 'terms' ? 'LEGAL CODEX' : 'DATA PROTECTION' }}</div>
          <h2 class="modal-title">
            {{ activeType === 'terms' ? 'StudyQuest Terms of Service' : 'StudyQuest Privacy Policy' }}
          </h2>
          <span class="modal-date">Last Updated: October 2026 · Academic Sanctum Standard</span>
        </div>

        <div class="modal-body">
          <template v-if="activeType === 'terms'">
            <div class="legal-clause">
              <h3>1. Acceptance of Terms</h3>
              <p>By accessing or creating an account on StudyQuest ("the Platform"), you agree to be bound by these Terms of Service. StudyQuest is an immersive, gamified learning platform engineered to assist undergraduate scholars and board-examination candidates in mastering psychology curricula through structured RPG progression.</p>
            </div>
            <div class="legal-clause">
              <h3>2. Academic Purpose & Educational Scope</h3>
              <p>StudyQuest provides academic reviewers, question banks, chapter manuscripts, and mock examinations designed for educational self-assessment. While our content is meticulously curated against official undergraduate syllabi and board-examination frameworks, StudyQuest is an independent review aid and does not replace official state licensure boards.</p>
            </div>
            <div class="legal-clause">
              <h3>3. Academic Integrity & Examination Honor Code</h3>
              <p>Scholars are expected to adhere to standard academic integrity principles: no automated scraping or answer injection during timed trials or examinations, and no software exploitation in Multiplayer Arena battles.</p>
            </div>
            <div class="legal-clause">
              <h3>4. Hall of Fame & Verification Protocol</h3>
              <p>The Hall of Fame recognizes verified board-examination passers. Applicants can choose between "Full Legal Name" or "🔒 Privacy Masked" to protect their personal privacy while celebrating academic success.</p>
            </div>
          </template>

          <template v-else>
            <div class="legal-clause">
              <h3>1. Information We Collect</h3>
              <p>We collect essential information required to deliver your learning journey: account credentials (email, username, bcrypt-hashed passwords), selected character class, chapter progress in Study Hall, mock examination history, and uploaded study notes in your private Vault.</p>
            </div>
            <div class="legal-clause">
              <h3>2. Hall of Fame & Name Privacy Controls</h3>
              <p>Scholars who apply to the Hall of Fame retain full control over public visibility. Choosing the "🔒 PRIVACY MASKED" option obscures surnames and identifying records on the public scroll.</p>
            </div>
            <div class="legal-clause">
              <h3>3. Data Protection & Security</h3>
              <p>Passwords are protected using one-way bcrypt hashing. Client storage (localStorage) is used solely for client configurations such as visual theme preference (studyquest_theme) and audio volumes (studyquest_settings).</p>
            </div>
            <div class="legal-clause">
              <h3>4. Non-Disclosure Guarantee</h3>
              <p>StudyQuest never sells, rents, or monetizes scholar academic records or test responses to commercial advertising networks or third-party data brokers.</p>
            </div>
          </template>
        </div>

        <div class="modal-footer">
          <router-link
            :to="activeType === 'terms' ? '/terms' : '/privacy'"
            class="btn-full-page"
            @click="$emit('close')"
          >
            Open Full Standalone Page ↗
          </router-link>
          <button class="btn-dismiss" @click="$emit('close')">
            Close
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  activeType: {
    type: String, // 'terms' | 'privacy' | null
    default: null
  }
})

defineEmits(['close'])
</script>

<style scoped>
.legal-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.legal-modal-card {
  max-width: 640px;
  width: 100%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  border-radius: 16px;
  padding: 2.25rem;
  position: relative;
  border: 1px solid;
  animation: modalIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.home-view.theme- .legal-modal-card,
.legal-modal-card {
  background: #0d0f17;
  border-color: #3d2a48;
  color: #d4c4a8;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
}

.home-view.theme- .legal-modal-card {
  background: #ffffff;
  border-color: #e2e8f0;
  color: #334155;
  box-shadow: 0 25px 50px rgba(15, 23, 42, 0.15);
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}

.modal-close {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  background: transparent;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
}

.modal-close:hover {
  opacity: 1;
}

.modal-head {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid;
}

.home-view.theme- .modal-head,
.modal-head {
  border-bottom-color: #242430;
}

.home-view.theme- .modal-head {
  border-bottom-color: #f1f5f9;
}

.modal-badge {
  display: inline-block;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  background: rgba(192, 57, 43, 0.15);
  color: #c0392b;
  margin-bottom: 0.5rem;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 800;
  margin-bottom: 0.35rem;
}

.home-view.theme- .modal-title,
.modal-title {
  color: #f0e8d0;
}

.home-view.theme- .modal-title {
  color: #0f172a;
}

.modal-date {
  font-size: 0.8rem;
  opacity: 0.7;
}

.modal-body {
  overflow-y: auto;
  padding-right: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  line-height: 1.6;
}

.legal-clause h3 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
}

.home-view.theme- .legal-clause h3,
.legal-clause h3 {
  color: #e8d8b8;
}

.home-view.theme- .legal-clause h3 {
  color: #1e293b;
}

.legal-clause p {
  font-size: 0.9rem;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 1.25rem;
  border-top: 1px solid;
}

.home-view.theme- .modal-footer,
.modal-footer {
  border-top-color: #242430;
}

.home-view.theme- .modal-footer {
  border-top-color: #f1f5f9;
}

.btn-full-page {
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  color: #c0392b;
}

.btn-full-page:hover {
  text-decoration: underline;
}

.btn-dismiss {
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  background: #c0392b;
  color: #ffffff;
  border: none;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-dismiss:hover {
  background: #d43f30;
}
</style>
