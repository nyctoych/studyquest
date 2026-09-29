<template>
  <div class="create-quest-page animate-slide-up">
    <!-- Header -->
    <div class="page-header">
      <router-link to="/quests" class="back-link">← Back to Quests</router-link>
      <h1 class="page-title">{{ isEditing ? 'Edit Quest' : 'Forge a New Quest' }}</h1>
      <p class="page-subtitle">Define your academic objective, set deadlines, and claim your bounty of XP.</p>
    </div>

    <div class="quest-form-layout">
      <!-- Main Form -->
      <div class="glass-card form-card">
        <form @submit.prevent="submitQuest">
          <!-- Title -->
          <div class="form-group">
            <label class="form-label" for="quest-title">
              Quest Title <span class="required">*</span>
            </label>
            <input
              id="quest-title"
              v-model="form.title"
              type="text"
              class="form-input"
              placeholder="e.g. Complete Calculus Chapter 4 Exercises"
              required
            />
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label" for="quest-desc">
              Quest Description &amp; Objectives
            </label>
            <textarea
              id="quest-desc"
              v-model="form.description"
              rows="3"
              class="form-textarea"
              placeholder="Describe the steps, reference materials, or specific goals for this task..."
            ></textarea>
          </div>

          <!-- Row: Subject & Priority -->
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label" for="quest-subject">
                Subject / Category <span class="required">*</span>
              </label>
              <select id="quest-subject" v-model="form.subject" class="form-select" required>
                <option value="" disabled>Select a subject</option>
                <option v-for="s in subjects" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label class="form-label" for="quest-priority">
                Priority Level
              </label>
              <select id="quest-priority" v-model="form.priority" class="form-select">
                <option value="low">🟢 Low</option>
                <option value="medium">🟡 Medium</option>
                <option value="high">🔴 High</option>
              </select>
            </div>
          </div>

          <!-- Row: Difficulty & Deadline -->
          <div class="form-row">
            <div class="form-group flex-1">
              <label class="form-label">
                Difficulty Level <span class="required">*</span>
              </label>
              <div class="difficulty-picker">
                <button
                  type="button"
                  v-for="diff in difficulties"
                  :key="diff.id"
                  class="diff-btn"
                  :class="{ active: form.difficulty === diff.id, [diff.id]: true }"
                  @click="form.difficulty = diff.id"
                >
                  <span class="diff-icon">{{ diff.icon }}</span>
                  <span class="diff-name">{{ diff.name }}</span>
                </button>
              </div>
            </div>

            <div class="form-group flex-1">
              <label class="form-label" for="quest-deadline">
                Deadline <span class="required">*</span>
              </label>
              <input
                id="quest-deadline"
                v-model="form.deadline"
                type="datetime-local"
                class="form-input"
                required
              />
            </div>
          </div>

          <!-- Subtasks Checklist Section -->
          <div class="subtasks-section">
            <div class="subtasks-header">
              <label class="form-label mb-0">Subtasks &amp; Milestones (Optional)</label>
              <span class="text-muted-sm">{{ form.subtasks.length }} items</span>
            </div>

            <div class="subtask-input-row">
              <input
                v-model="newSubtaskTitle"
                type="text"
                class="form-input"
                placeholder="Add a milestone (e.g. Read pages 45-60)"
                @keyup.enter.prevent="addSubtask"
              />
              <button type="button" class="btn btn-secondary" @click="addSubtask">
                + Add
              </button>
            </div>

            <!-- Subtask list -->
            <ul v-if="form.subtasks.length > 0" class="subtask-list">
              <li v-for="(task, idx) in form.subtasks" :key="idx" class="subtask-item">
                <span class="subtask-num">{{ idx + 1 }}</span>
                <span class="subtask-name">{{ task.title }}</span>
                <button type="button" class="subtask-del" @click="removeSubtask(idx)">✕</button>
              </li>
            </ul>
          </div>

          <!-- Submit Buttons -->
          <div class="form-actions">
            <router-link to="/quests" class="btn btn-secondary">Cancel</router-link>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Saving...' : (isEditing ? 'Save Changes' : '⚔️ Forge Quest') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Live Quest Preview Card -->
      <div class="preview-sidebar">
        <div class="glass-card preview-card">
          <h3 class="preview-header">Quest Bounty Preview</h3>

          <div class="bounty-breakdown">
            <div class="bounty-item">
              <div class="bounty-icon xp-icon">⭐</div>
              <div class="bounty-info">
                <span class="bounty-val">+{{ calculatedXP }} XP</span>
                <span class="bounty-sub">Base: {{ baseXP }} + Subtasks: {{ subtaskXP }}</span>
              </div>
            </div>

            <div class="bounty-item">
              <div class="bounty-icon pt-icon">🪙</div>
              <div class="bounty-info">
                <span class="bounty-val">+{{ calculatedPoints }} Coins</span>
                <span class="bounty-sub">Spend in Rewards Shop</span>
              </div>
            </div>
          </div>

          <hr class="card-divider" />

          <!-- Mini Quest Card Preview -->
          <div class="mini-preview-label">Live Preview</div>
          <div class="mini-quest-card" :class="`border-diff-${form.difficulty}`">
            <div class="mini-top">
              <span class="subject-pill">{{ form.subject || 'General' }}</span>
              <span class="priority-pill" :class="form.priority">{{ form.priority.toUpperCase() }}</span>
            </div>
            <h4 class="mini-title">{{ form.title || 'Untitled Quest' }}</h4>
            <p class="mini-desc">{{ form.description || 'No description provided.' }}</p>
            <div class="mini-meta">
              <span>⏳ {{ formatPreviewDeadline }}</span>
              <span>⭐ {{ calculatedXP }} XP</span>
            </div>
          </div>

          <div class="tip-box">
            <span class="tip-icon">💡</span>
            <span class="tip-text">Breaking big tasks into subtasks awards bonus XP and makes studying less overwhelming!</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { questService } from '../services/questService'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const toast = useToastStore()

const isEditing = ref(false)
const questId = ref(null)
const submitting = ref(false)

const subjects = [
  'Mathematics',
  'Physics',
  'Computer Science',
  'Chemistry',
  'Biology',
  'Literature',
  'History',
  'Languages',
  'Art & Design',
  'General'
]

const difficulties = [
  { id: 'easy', name: 'Easy', icon: '🟢', xp: 50, pts: 10 },
  { id: 'medium', name: 'Medium', icon: '🔵', xp: 100, pts: 25 },
  { id: 'hard', name: 'Hard', icon: '🟠', xp: 200, pts: 50 },
  { id: 'epic', name: 'Epic', icon: '🟣', xp: 400, pts: 100 }
]

// Default deadline: tomorrow at 23:59
const getDefaultDeadline = () => {
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  tomorrow.setHours(23, 59, 0, 0)
  return tomorrow.toISOString().slice(0, 16)
}

const form = ref({
  title: '',
  description: '',
  subject: 'Mathematics',
  difficulty: 'medium',
  priority: 'medium',
  deadline: getDefaultDeadline(),
  subtasks: []
})

const newSubtaskTitle = ref('')

// Live Reward Calculations
const baseXP = computed(() => {
  const diff = difficulties.find(d => d.id === form.value.difficulty)
  return diff ? diff.xp : 100
})

const subtaskXP = computed(() => {
  return form.value.subtasks.length * 15
})

const calculatedXP = computed(() => {
  return baseXP.value + subtaskXP.value
})

const calculatedPoints = computed(() => {
  const diff = difficulties.find(d => d.id === form.value.difficulty)
  const base = diff ? diff.pts : 25
  return base + (form.value.subtasks.length * 5)
})

const formatPreviewDeadline = computed(() => {
  if (!form.value.deadline) return 'No deadline'
  const d = new Date(form.value.deadline)
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
})

function addSubtask() {
  if (!newSubtaskTitle.value.trim()) return
  form.value.subtasks.push({
    title: newSubtaskTitle.value.trim(),
    is_completed: false
  })
  newSubtaskTitle.value = ''
}

function removeSubtask(idx) {
  form.value.subtasks.splice(idx, 1)
}

async function submitQuest() {
  submitting.value = true
  try {
    const payload = {
      title: form.value.title,
      description: form.value.description,
      subject: form.value.subject,
      difficulty: form.value.difficulty,
      priority: form.value.priority,
      deadline: form.value.deadline,
      xp_reward: calculatedXP.value,
      points_reward: calculatedPoints.value,
      subtasks: form.value.subtasks
    }

    if (isEditing.value) {
      await questService.update(questId.value, payload)
      toast.add('Quest updated successfully!', 'success')
    } else {
      await questService.create(payload)
      toast.add('⚔️ Quest forged successfully!', 'success')
    }
    router.push('/quests')
  } catch (err) {
    console.error('Save failed', err)
    toast.add('Failed to save quest', 'error')
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  if (route.params.id) {
    isEditing.value = true
    questId.value = route.params.id
    try {
      const res = await questService.getById(questId.value)
      if (res.data && res.data.success) {
        const q = res.data.data
        form.value.title = q.title || ''
        form.value.description = q.description || ''
        form.value.subject = q.subject || 'General'
        form.value.difficulty = q.difficulty || 'medium'
        form.value.priority = q.priority || 'medium'
        if (q.deadline) {
          form.value.deadline = q.deadline.replace(' ', 'T').slice(0, 16)
        }
        form.value.subtasks = q.subtasks || []
      }
    } catch (err) {
      console.error('Failed to load quest for edit', err)
      toast.add('Could not load quest details', 'error')
    }
  }
})
</script>

<style scoped>
.create-quest-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  color: #e2d9cc;
}

.page-header {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.back-link {
  color: #e6c845;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  margin-bottom: 0.5rem;
  display: inline-block;
  letter-spacing: 0.04em;
}

.back-link:hover {
  color: #fef08a;
  text-shadow: 0 0 8px rgba(230, 200, 69, 0.4);
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

.quest-form-layout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 1.5rem;
  align-items: flex-start;
}

@media (max-width: 900px) {
  .quest-form-layout {
    grid-template-columns: 1fr;
  }
}

.form-card {
  padding: 1.75rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.4rem;
  letter-spacing: 0.03em;
}

.form-label .required {
  color: #ef4444;
}

.form-input, .form-textarea, .form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #09090e;
  border: 1px solid #252330;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  color: #f3ede2;
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
  transition: all 0.2s;
}

.form-input:focus, .form-textarea:focus, .form-select:focus {
  border-color: #b59410;
  box-shadow: 0 0 0 1px #b59410;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.difficulty-picker {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.diff-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.6rem 0.25rem;
  background: #09090e;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  color: #8c887b;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.diff-btn:hover {
  background: #141520;
  color: #f3ede2;
}

.diff-btn.active.easy {
  border-color: #10b981;
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
}
.diff-btn.active.medium {
  border-color: #3b82f6;
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
}
.diff-btn.active.hard {
  border-color: #b59410;
  background: rgba(181, 148, 16, 0.15);
  color: #e6c845;
}
.diff-btn.active.epic {
  border-color: #8b1a1a;
  background: rgba(139, 26, 26, 0.2);
  color: #f87171;
}

.diff-icon { font-size: 1rem; }
.diff-name {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
}

.subtasks-section {
  background: #08080d;
  border: 1px solid #202230;
  box-shadow: 0 0 0 1px #050608;
  border-radius: 2px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.subtasks-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.text-muted-sm {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #8c887b;
}

.subtask-input-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.subtask-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.subtask-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.75rem;
  background: #0d0e15;
  border: 1px solid #1f202c;
  border-radius: 2px;
  font-size: 0.85rem;
}

.subtask-num {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #e6c845;
  width: 20px;
}

.subtask-name {
  flex: 1;
  color: #e2e8f0;
}

.subtask-del {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  padding: 0.1rem 0.3rem;
  font-size: 0.85rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1rem;
}

.preview-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.preview-card {
  padding: 1.5rem;
  background: linear-gradient(180deg, #13121b 0%, #0a0a0f 100%);
  border: 2px solid #252330;
  box-shadow: 0 0 0 2px #050608, inset 0 0 0 1px rgba(181, 148, 16, 0.15);
  border-radius: 4px;
}

.preview-header {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 1rem;
}

.bounty-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.bounty-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #09090e;
  border: 1px solid #202230;
  padding: 0.75rem;
  border-radius: 2px;
}

.bounty-icon {
  font-size: 1.5rem;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 2px;
}

.bounty-icon.xp-icon { background: rgba(181, 148, 16, 0.15); border: 1px solid rgba(181, 148, 16, 0.3); }
.bounty-icon.pt-icon { background: rgba(139, 26, 26, 0.2); border: 1px solid rgba(139, 26, 26, 0.4); }

.bounty-val {
  display: block;
  font-family: 'Press Start 2P', monospace;
  font-size: 0.85rem;
  color: #e6c845;
}

.bounty-sub {
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #8c887b;
}

.card-divider {
  border: none;
  border-top: 1px solid #202230;
  margin: 1.25rem 0;
}

.mini-preview-label {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #8c887b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.mini-quest-card {
  background: #09090e;
  border: 1px solid #202230;
  border-left-width: 4px;
  border-radius: 2px;
  padding: 1rem;
}

.border-diff-easy { border-left-color: #10b981; }
.border-diff-medium { border-left-color: #3b82f6; }
.border-diff-hard { border-left-color: #b59410; }
.border-diff-epic { border-left-color: #8b1a1a; }

.mini-top {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.4rem;
}

.subject-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.6rem;
  color: #e6c845;
}

.priority-pill {
  font-family: 'Press Start 2P', monospace;
  font-size: 0.55rem;
  padding: 0.15rem 0.4rem;
  border-radius: 2px;
}

.priority-pill.low { color: #34d399; background: rgba(16, 185, 129, 0.15); }
.priority-pill.medium { color: #fbbf24; background: rgba(245, 158, 11, 0.15); }
.priority-pill.high { color: #f87171; background: rgba(239, 68, 68, 0.15); }

.mini-title {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #f3ede2;
  margin-bottom: 0.25rem;
}

.mini-desc {
  font-size: 0.8rem;
  color: #9ca3af;
  line-height: 1.3;
  margin-bottom: 0.75rem;
}

.mini-meta {
  display: flex;
  justify-content: space-between;
  font-family: 'VT323', monospace;
  font-size: 1rem;
  color: #8c887b;
}

.tip-box {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  background: rgba(181, 148, 16, 0.08);
  border: 1px solid rgba(181, 148, 16, 0.25);
  border-radius: 2px;
  padding: 0.75rem;
  margin-top: 1rem;
}

.tip-icon { font-size: 1.1rem; }
.tip-text { font-size: 0.8rem; color: #fef08a; line-height: 1.3; }
</style>
