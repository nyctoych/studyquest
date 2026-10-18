<template>
  <div class="special-event-admin-page">
    <div class="admin-container animate-fade-in">
      <!-- Nav Header -->
      <div class="admin-nav-bar">
        <div class="nav-left-spacer" aria-hidden="true"></div>

        <div class="nav-title flex items-center gap-2">
          <PixelEmoji emoji="gear" size="sm" />
          <span>TEST YOUR KNOWLEDGE // ADMINISTRATION & ENGINE</span>
        </div>

        <button class="rpg-nav-back-btn" @click="goToGate">
          <span>Today's Exam Gate</span>
        </button>

        <div class="nav-right-spacer" aria-hidden="true"></div>
      </div>

      <!-- Control Panel -->
      <div class="admin-control-card gothic-panel">
        <div class="control-row-top">
          <div>
            <h2 class="section-title">TEST YOUR KNOWLEDGE GENERATION ENGINE</h2>
            <p class="section-desc">
              Manage daily 450-question two-session mock Psychometrician Board Examination events grounded strictly in Study Hall reviewers.
            </p>
          </div>

          <div class="gen-action-cluster">
            <input
              v-model="targetDate"
              type="date"
              class="date-input"
            />
            <label class="force-chk-label">
              <input v-model="forceRegenerate" type="checkbox" />
              <span>Force Regenerate</span>
            </label>
            <button
              class="btn-generate-event"
              :disabled="generating"
              @click="triggerGeneration"
            >
              <PixelEmoji emoji="lightning" size="xs" />
              <span>{{ generating ? 'Generating 450 Items...' : 'Generate 450-Item Mock Exam' }}</span>
            </button>
          </div>
        </div>

        <!-- Event Selection Tabs / Status -->
        <div class="events-list-bar">
          <span class="bar-lbl">ARCHIVED EVENTS:</span>
          <div class="events-scroll custom-scrollbar">
            <button
              v-for="ev in eventsList"
              :key="ev.id"
              class="event-pill-btn"
              :class="{ active: selectedEventId === ev.id }"
              @click="selectEvent(ev.id)"
            >
              <span class="pill-date">{{ ev.event_date }}</span>
              <span class="pill-status" :class="ev.status">[{{ ev.status }}]</span>
              <span class="pill-count">({{ ev.actual_question_count || 140 }} Qs)</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Event Inspector -->
      <div v-if="selectedEvent" class="inspector-section gothic-panel">
        <div class="inspector-header">
          <div>
            <span class="inspector-date">{{ selectedEvent.event_date }} • ID: #{{ selectedEvent.id }}</span>
            <h2 class="inspector-title">{{ selectedEvent.title }}</h2>
          </div>
          <div class="inspector-actions">
            <div v-if="selectedEvent.is_adaptive || selectedEvent.target_bloom" class="adaptive-target-pill flex items-center gap-1.5">
              <PixelEmoji emoji="crystal" size="xs" />
              <span>ADAPTIVE TARGET: {{ (selectedEvent.target_bloom || 'Standard').toUpperCase() }}</span>
              <span v-if="selectedEvent.secondary_bloom" class="sec-target">/ {{ selectedEvent.secondary_bloom.toUpperCase() }}</span>
            </div>
            <div class="status-indicator-badge" :class="selectedEvent.status">
              STATUS: {{ selectedEvent.status.toUpperCase() }}
            </div>
            <button
              v-if="selectedEvent.status !== 'published'"
              class="btn-publish-event"
              :disabled="selectedQuestions.length !== 140"
              @click="publishSelectedEvent"
            >
              <PixelEmoji emoji="shield" size="xs" /> Publish 140-Item Event
            </button>
          </div>
        </div>

        <!-- Filter bar -->
        <div class="questions-filter-bar">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search stems, concepts, or DSM-5 keywords..."
            class="search-input"
          />
          <div class="status-filter-pills">
            <button
              class="f-pill"
              :class="{ active: reviewFilter === 'all' }"
              @click="reviewFilter = 'all'"
            >
              All ({{ selectedQuestions.length }})
            </button>
            <button
              class="f-pill"
              :class="{ active: reviewFilter === 'approved' }"
              @click="reviewFilter = 'approved'"
            >
              Approved ({{ countByStatus('approved') }})
            </button>
            <button
              class="f-pill"
              :class="{ active: reviewFilter === 'rejected' }"
              @click="reviewFilter = 'rejected'"
            >
              Rejected ({{ countByStatus('rejected') }})
            </button>
          </div>
        </div>

        <!-- Question Cards List -->
        <div class="questions-accordion-list">
          <div
            v-for="(q, idx) in filteredQuestions"
            :key="q.id"
            class="admin-q-card"
            :class="{ 'is-rejected': q.review_status === 'rejected' }"
          >
            <div class="q-card-header">
              <span class="q-order-badge">#{{ q.display_order || (idx + 1) }}</span>
              <span class="q-domain-label">{{ q.domain_name }} • {{ q.part_title }}</span>
              <span class="q-diff-badge">{{ q.difficulty }}</span>
              <span class="q-type-badge">{{ q.question_type }}</span>

              <div class="q-actions-right">
                <span class="q-status-flag" :class="q.review_status">{{ q.review_status }}</span>
                <button
                  v-if="q.review_status !== 'approved'"
                  class="act-btn approve"
                  @click="approveQuestion(q.id)"
                  title="Approve Question"
                >
                  ✓ Approve
                </button>
                <button
                  v-if="q.review_status !== 'rejected'"
                  class="act-btn reject"
                  @click="rejectQuestion(q.id)"
                  title="Reject Question"
                >
                  ✕ Reject
                </button>
                <button
                  class="act-btn replace"
                  :disabled="replacingId === q.id"
                  @click="replaceQuestion(q.id)"
                  title="Replace with AI/Reviewer Regeneration"
                >
                  ↺ {{ replacingId === q.id ? 'Replacing...' : 'Replace' }}
                </button>
              </div>
            </div>

            <div class="q-card-body">
              <p class="q-text-stem">{{ q.question_text }}</p>

              <div class="q-choices-grid">
                <div
                  v-for="k in ['A', 'B', 'C', 'D']"
                  :key="k"
                  class="q-choice-row"
                  :class="{ 'is-correct': q.correct_choice === k }"
                >
                  <span class="k">{{ k }}.</span>
                  <span class="txt">{{ q['choice_' + k.toLowerCase()] }}</span>
                  <span v-if="q.correct_choice === k" class="correct-badge">CORRECT</span>
                </div>
              </div>

              <div class="q-explanation-note">
                <strong>Clinical Rationale:</strong> {{ q.explanation }}
              </div>

              <!-- Technical Provenance & Source Metadata -->
              <div class="q-metadata-footer">
                <span><strong>Concept:</strong> {{ q.concept_key }}</span>
                <span><strong>Source Hash:</strong> {{ q.source_hash?.substring(0, 12) }}...</span>
                <span><strong>Question Hash:</strong> {{ q.question_hash?.substring(0, 12) }}...</span>
                <span><strong>Model/Version:</strong> {{ q.generation_version }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { specialEventService } from '../services/specialEventService'
import { useToastStore } from '../stores/toast'

const router = useRouter()
const toast = useToastStore()

const targetDate = ref(new Date().toISOString().substring(0, 10))
const forceRegenerate = ref(false)
const generating = ref(false)
const replacingId = ref(null)

const eventsList = ref([])
const selectedEventId = ref(null)
const selectedEvent = ref(null)
const selectedQuestions = ref([])

const searchQuery = ref('')
const reviewFilter = ref('all')

const filteredQuestions = computed(() => {
  return selectedQuestions.value.filter(q => {
    // Status filter
    if (reviewFilter.value !== 'all' && q.review_status !== reviewFilter.value) {
      return false
    }
    // Search query
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      const inText = q.question_text?.toLowerCase().includes(query)
      const inConcept = q.concept_key?.toLowerCase().includes(query)
      const inDomain = q.domain_name?.toLowerCase().includes(query)
      return inText || inConcept || inDomain
    }
    return true
  })
})

function countByStatus(status) {
  return selectedQuestions.value.filter(q => q.review_status === status).length
}

async function loadEvents() {
  try {
    const res = await specialEventService.adminGetEvents()
    if (res.success) {
      eventsList.value = res.events || []
      if (eventsList.value.length > 0 && !selectedEventId.value) {
        selectEvent(eventsList.value[0].id)
      }
    }
  } catch (err) {
    toast.error('Failed to load events: ' + (err.response?.data?.message || 'Error'))
  }
}

async function selectEvent(id) {
  selectedEventId.value = id
  try {
    const res = await specialEventService.adminGetQuestions(id)
    if (res.success) {
      selectedEvent.value = res.event
      selectedQuestions.value = res.questions || []
    }
  } catch (err) {
    toast.error('Failed to load questions.')
  }
}

async function triggerGeneration() {
  generating.value = true
  try {
    const res = await specialEventService.adminGenerate(targetDate.value, forceRegenerate.value)
    if (res.success) {
      toast.success(res.message || 'Mock board exam items generated and verified!')
      await loadEvents()
      if (res.event_id) {
        selectEvent(res.event_id)
      }
    } else {
      toast.error(res.message || 'Generation failed.')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Generation error.')
  } finally {
    generating.value = false
  }
}

async function approveQuestion(id) {
  try {
    await specialEventService.adminApproveQuestion(id)
    const q = selectedQuestions.value.find(item => item.id === id)
    if (q) q.review_status = 'approved'
    toast.success('Question approved.')
  } catch (err) {
    toast.error('Failed to approve.')
  }
}

async function rejectQuestion(id) {
  try {
    await specialEventService.adminRejectQuestion(id)
    const q = selectedQuestions.value.find(item => item.id === id)
    if (q) q.review_status = 'rejected'
    toast.info('Question rejected.')
  } catch (err) {
    toast.error('Failed to reject.')
  }
}

async function replaceQuestion(id) {
  replacingId.value = id
  try {
    const res = await specialEventService.adminReplaceQuestion(id)
    if (res.success && res.question) {
      const idx = selectedQuestions.value.findIndex(item => item.id === id)
      if (idx !== -1) {
        selectedQuestions.value[idx] = res.question
      }
      toast.success('Question replaced and verified!')
    } else {
      toast.error(res.message || 'Replacement failed.')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error replacing question.')
  } finally {
    replacingId.value = null
  }
}

async function publishSelectedEvent() {
  if (!selectedEventId.value) return
  try {
    const res = await specialEventService.adminPublishEvent(selectedEventId.value)
    if (res.success) {
      toast.success('Event officially published!')
      if (selectedEvent.value) {
        selectedEvent.value.status = 'published'
      }
      loadEvents()
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'Cannot publish event.')
  }
}

function goToGate() {
  router.push('/special-event')
}

onMounted(() => {
  loadEvents()
})
</script>

<style scoped>
.special-event-admin-page {
  min-height: 100vh;
  background: #060410;
  color: #f1f5f9;
  padding: 1.5rem 1rem 4rem;
}

.admin-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.admin-nav-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 42px;
  gap: 1rem;
}

.nav-left-spacer {
  width: 195px;
  flex-shrink: 0;
}

.nav-right-spacer {
  width: 105px;
  flex-shrink: 0;
}

@media (max-width: 1024px) {
  .admin-nav-bar {
    flex-wrap: wrap;
    padding-top: 44px;
  }
  .nav-left-spacer, .nav-right-spacer {
    display: none;
  }
}

.rpg-nav-back-btn {
  background: rgba(22, 17, 39, 0.85);
  border: 1px solid rgba(139, 92, 246, 0.35);
  color: #c4b5fd;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
}
.rpg-nav-back-btn:hover {
  background: #8b5cf6;
  color: #fff;
}

.nav-title {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.7rem;
  color: #fde047;
}

.gothic-panel {
  background: rgba(16, 12, 30, 0.95);
  border: 1.5px solid rgba(139, 92, 246, 0.35);
  border-radius: 10px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
}

.control-row-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.25rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 1rem;
}

.section-title {
  font-family: 'Cinzel', serif;
  font-size: 1.35rem;
  color: #f8fafc;
  margin: 0;
}
.section-desc {
  font-size: 0.8rem;
  color: #94a3b8;
  margin-top: 0.2rem;
}

.gen-action-cluster {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.date-input {
  background: rgba(26, 20, 48, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.4);
  color: #fff;
  padding: 0.45rem 0.65rem;
  border-radius: 6px;
  font-size: 0.8rem;
}

.force-chk-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  color: #cbd5e1;
  cursor: pointer;
}

.btn-generate-event {
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
  border: 1px solid #c4b5fd;
  color: #fff;
  padding: 0.55rem 1rem;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}
.btn-generate-event:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.events-list-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}
.bar-lbl {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #94a3b8;
}
.events-scroll {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 4px;
}
.event-pill-btn {
  background: rgba(26, 20, 48, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #cbd5e1;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.72rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  white-space: nowrap;
}
.event-pill-btn.active {
  background: #8b5cf6;
  border-color: #c4b5fd;
  color: #fff;
}
.pill-status.published { color: #34d399; }
.pill-status.draft { color: #fbbf24; }

/* Inspector Header */
.inspector-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  border-bottom: 1px solid rgba(139, 92, 246, 0.25);
  padding-bottom: 1rem;
}
.inspector-date {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.58rem;
  color: #a78bfa;
}
.inspector-title {
  font-family: 'Cinzel', serif;
  font-size: 1.3rem;
  color: #f8fafc;
  margin: 0.2rem 0 0;
}
.inspector-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.status-indicator-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
}
.status-indicator-badge.published {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid #10b981;
}
.status-indicator-badge.draft,
.status-indicator-badge.generating {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid #f59e0b;
}

.adaptive-target-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  background: rgba(139, 92, 246, 0.2);
  color: #c4b5fd;
  border: 1px solid #a855f7;
}
.adaptive-target-pill .sec-target {
  color: #fca5a5;
  font-size: 0.5rem;
}

.btn-publish-event {
  background: #10b981;
  border: 1px solid #6ee7b7;
  color: #fff;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

/* Filter Bar */
.questions-filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin: 1.25rem 0;
  flex-wrap: wrap;
}
.search-input {
  flex: 1;
  min-width: 250px;
  background: rgba(22, 17, 40, 0.8);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #fff;
  padding: 0.45rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
}
.status-filter-pills {
  display: flex;
  gap: 0.4rem;
}
.f-pill {
  background: rgba(30, 24, 55, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #94a3b8;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  font-size: 0.7rem;
  cursor: pointer;
}
.f-pill.active {
  background: #8b5cf6;
  color: #fff;
}

/* Question List */
.questions-accordion-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.admin-q-card {
  background: rgba(22, 17, 40, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 8px;
  padding: 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}
.admin-q-card.is-rejected {
  border-color: rgba(239, 68, 68, 0.5);
  background: rgba(45, 15, 20, 0.5);
}

.q-card-header {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}
.q-order-badge {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  background: #8b5cf6;
  color: #fff;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
}
.q-domain-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #e2e8f0;
}
.q-diff-badge, .q-type-badge {
  font-size: 0.65rem;
  background: rgba(51, 65, 85, 0.5);
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
  color: #cbd5e1;
}

.q-actions-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}
.q-status-flag {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.2rem 0.4rem;
  border-radius: 3px;
}
.q-status-flag.approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.q-status-flag.rejected { background: rgba(239, 68, 68, 0.2); color: #f87171; }

.act-btn {
  border: none;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-size: 0.7rem;
  cursor: pointer;
  font-weight: 600;
}
.act-btn.approve { background: #10b981; color: #fff; }
.act-btn.reject { background: #ef4444; color: #fff; }
.act-btn.replace { background: #3b82f6; color: #fff; }

.q-text-stem {
  font-size: 0.92rem;
  line-height: 1.55;
  color: #f8fafc;
}
.q-choices-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.4rem;
}
.q-choice-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.65rem;
  background: rgba(30, 24, 55, 0.5);
  border-radius: 4px;
  font-size: 0.8rem;
}
.q-choice-row.is-correct {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid #10b981;
  color: #6ee7b7;
}
.correct-badge {
  margin-left: auto;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.5rem;
  color: #34d399;
}

.q-explanation-note {
  font-size: 0.78rem;
  color: #cbd5e1;
  background: rgba(15, 23, 42, 0.5);
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  border-left: 3px solid #8b5cf6;
}

.q-metadata-footer {
  display: flex;
  gap: 1rem;
  font-size: 0.65rem;
  color: #64748b;
  flex-wrap: wrap;
  border-top: 1px dashed rgba(148, 163, 184, 0.15);
  padding-top: 0.4rem;
}
</style>
