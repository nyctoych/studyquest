<template>
  <div v-if="isOpen" class="share-modal-overlay" @click.self="close">
    <div class="share-sheet-container animate-sheet-up">
      <!-- Sheet Header -->
      <div class="sheet-header">
        <div class="header-left">
          <span class="sheet-badge">
            <PixelEmoji emoji="trophy" size="xs" /> Authoritative Chronicle Poster
          </span>
          <h2 class="sheet-title">Download Achievement Poster</h2>
        </div>
        <button type="button" class="btn-close-sheet" @click="close" aria-label="Close modal">
          ✕
        </button>
      </div>

      <!-- Main Sheet Body: Preview & Poster Generator + Download Chooser -->
      <div class="sheet-body">
        <!-- Left: Live Poster Canvas & Preview -->
        <div class="poster-preview-pane">
          <div class="format-toggle-bar">
            <span class="format-label">Preview Format:</span>
            <div class="format-buttons">
              <button
                type="button"
                class="format-btn"
                :class="{ active: currentFormat === 'story' }"
                @click="setFormat('story')"
              >
                📱 Story (9:16)
              </button>
              <button
                type="button"
                class="format-btn"
                :class="{ active: currentFormat === 'post' }"
                @click="setFormat('post')"
              >
                🖼️ Feed (1:1)
              </button>
            </div>
          </div>

          <!-- Canvas Preview Container -->
          <div class="canvas-viewport" :class="`view-${currentFormat}`">
            <canvas ref="posterCanvas" class="rendered-poster"></canvas>
            <div v-if="rendering" class="rendering-overlay">
              <div class="spinner"></div>
              <span>Forging Achievement Poster...</span>
            </div>
          </div>

          <div class="preview-caption">
            <span>✦ Sharp Gothic Poster dynamically rendered from StudyQuest authoritative records</span>
          </div>
        </div>

        <!-- Right: Format Chooser & Direct Download Actions -->
        <div class="share-actions-pane">
          <!-- Section 1: Choose Poster Format & Direct Downloads -->
          <div class="share-section">
            <h4 class="section-title">Choose Size &amp; Download</h4>

            <div class="size-options-container">
              <!-- Format 1: Story (9:16) -->
              <div
                class="size-card"
                :class="{ active: currentFormat === 'story' }"
                @click="setFormat('story')"
              >
                <div class="size-card-header">
                  <div class="size-badge-icon">📱</div>
                  <div class="size-meta">
                    <span class="size-name">Story Format</span>
                    <span class="size-ratio">9:16 Portrait • 720 × 1280 px</span>
                  </div>
                  <span v-if="currentFormat === 'story'" class="selected-pill">Active Preview</span>
                </div>
                <p class="size-desc">
                  Tall vertical layout optimized for mobile screens, phone wallpapers, reels, and full-screen displays.
                </p>
                <div class="size-download-actions">
                  <button
                    type="button"
                    class="btn-size-download primary"
                    @click.stop="downloadPoster('png', 'story')"
                  >
                    💾 Download PNG (HD)
                  </button>
                  <button
                    type="button"
                    class="btn-size-download secondary"
                    @click.stop="downloadPoster('jpeg', 'story')"
                  >
                    🖼️ Download JPEG
                  </button>
                </div>
              </div>

              <!-- Format 2: Square Feed (1:1) -->
              <div
                class="size-card"
                :class="{ active: currentFormat === 'post' }"
                @click="setFormat('post')"
              >
                <div class="size-card-header">
                  <div class="size-badge-icon">🖼️</div>
                  <div class="size-meta">
                    <span class="size-name">Square Feed Format</span>
                    <span class="size-ratio">1:1 Square • 720 × 720 px</span>
                  </div>
                  <span v-if="currentFormat === 'post'" class="selected-pill">Active Preview</span>
                </div>
                <p class="size-desc">
                  Balanced square layout ideal for desktop monitors, profile showcases, discord, and square feeds.
                </p>
                <div class="size-download-actions">
                  <button
                    type="button"
                    class="btn-size-download primary"
                    @click.stop="downloadPoster('png', 'post')"
                  >
                    💾 Download PNG (HD)
                  </button>
                  <button
                    type="button"
                    class="btn-size-download secondary"
                    @click.stop="downloadPoster('jpeg', 'post')"
                  >
                    🖼️ Download JPEG
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { useToastStore } from '../../stores/toast'
import PixelEmoji from '../ui/PixelEmoji.vue'
import Swal from 'sweetalert2'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  card: {
    type: Object,
    default: null
  },
  milestone: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

const auth = useAuthStore()
const toast = useToastStore()

const posterCanvas = ref(null)
const currentFormat = ref('story') // 'story' (9:16) or 'post' (1:1)
const rendering = ref(false)

// Current targeted milestone
const targetMilestone = computed(() => {
  return props.milestone || props.card?.milestones?.[0] || null
})

// Close handler
function close() {
  emit('close')
}

// Format toggle
function setFormat(fmt) {
  currentFormat.value = fmt
  nextTick(() => {
    drawPoster()
  })
}

// Watch modal visibility and redraw
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      copiedLink.value = false
      nextTick(() => {
        drawPoster()
      })
    }
  }
)

watch(
  () => props.milestone,
  () => {
    if (props.isOpen) {
      nextTick(() => {
        drawPoster()
      })
    }
  }
)

/**
 * High-definition Canvas Poster Renderer.
 * Creates an authentic Gothic RPG achievement card with custom styling,
 * border filigree, badge artwork, player identity, and achievement details.
 */
function drawPoster() {
  if (!posterCanvas.value || !props.card) return
  rendering.value = true

  const canvas = posterCanvas.value
  const ctx = canvas.getContext('2d')

  // Set resolution
  const isStory = currentFormat.value === 'story'
  const width = isStory ? 720 : 720
  const height = isStory ? 1280 : 720

  canvas.width = width
  canvas.height = height

  const m = targetMilestone.value
  const c = props.card
  const playerName = auth.user?.nickname || auth.user?.username || 'Valiant Scholar'
  const playerLevel = auth.user?.level || 1
  const isClaimed = m?.status === 'claimed'

  // 1. Background Gradient & Vignette
  const bgGrad = ctx.createRadialGradient(width / 2, height / 2, 80, width / 2, height / 2, width * 0.75)
  bgGrad.addColorStop(0, '#1c1822')
  bgGrad.addColorStop(0.65, '#0d0d12')
  bgGrad.addColorStop(1, '#050608')
  ctx.fillStyle = bgGrad
  ctx.fillRect(0, 0, width, height)

  // 2. Subtle Gothic Grid / Parchment Texture Pattern
  ctx.strokeStyle = 'rgba(212, 175, 55, 0.05)'
  ctx.lineWidth = 1
  const step = 40
  for (let x = 0; x < width; x += step) {
    ctx.beginPath()
    ctx.moveTo(x, 0)
    ctx.lineTo(x, height)
    ctx.stroke()
  }
  for (let y = 0; y < height; y += step) {
    ctx.beginPath()
    ctx.moveTo(0, y)
    ctx.lineTo(width, y)
    ctx.stroke()
  }

  // 3. Ornate Double Gold Border
  const pad = 24
  ctx.strokeStyle = '#785a15'
  ctx.lineWidth = 3
  ctx.strokeRect(pad, pad, width - pad * 2, height - pad * 2)

  ctx.strokeStyle = '#d4af37'
  ctx.lineWidth = 1.5
  ctx.strokeRect(pad + 6, pad + 6, width - (pad + 6) * 2, height - (pad + 6) * 2)

  // Corner Ornaments
  const cornerSize = 20
  const corners = [
    [pad, pad],
    [width - pad, pad],
    [pad, height - pad],
    [width - pad, height - pad]
  ]
  corners.forEach(([cx, cy]) => {
    ctx.fillStyle = '#ffd700'
    ctx.fillRect(cx - 3, cy - 3, 6, 6)
  })

  // 4. Header: StudyQuest Brand Crest
  let curY = isStory ? 80 : 50

  ctx.textAlign = 'center'
  ctx.font = 'bold 15px "Cinzel", Georgia, serif'
  ctx.fillStyle = '#b59410'
  ctx.letterSpacing = '3px'
  ctx.fillText('✦  S T U D Y Q U E S T  ✦', width / 2, curY)

  curY += 24
  ctx.font = '12px "Press Start 2P", monospace'
  ctx.fillStyle = '#8b8070'
  ctx.fillText('HALL OF ACHIEVEMENTS', width / 2, curY)

  // Divider Line
  curY += 20
  ctx.strokeStyle = 'rgba(212, 175, 55, 0.35)'
  ctx.lineWidth = 1
  ctx.beginPath()
  ctx.moveTo(width / 2 - 120, curY)
  ctx.lineTo(width / 2 + 120, curY)
  ctx.stroke()

  // 5. Player Identity Pill
  curY += isStory ? 45 : 32
  const pillW = 260
  const pillH = 34
  const pillX = (width - pillW) / 2
  ctx.fillStyle = 'rgba(255, 255, 255, 0.05)'
  ctx.strokeStyle = 'rgba(212, 175, 55, 0.4)'
  ctx.lineWidth = 1
  ctx.fillRect(pillX, curY - 22, pillW, pillH)
  ctx.strokeRect(pillX, curY - 22, pillW, pillH)

  ctx.font = 'bold 14px "Cinzel", Georgia, serif'
  ctx.fillStyle = '#f3ede2'
  ctx.fillText(`⚔️ ${playerName}  •  LVL ${playerLevel}`, width / 2, curY)

  // 6. Central Heraldic Trophy / Icon Box
  curY += isStory ? 80 : 55
  const boxSize = isStory ? 120 : 96
  const boxX = (width - boxSize) / 2
  const boxY = curY

  // Glow
  const glow = ctx.createRadialGradient(width / 2, boxY + boxSize / 2, 10, width / 2, boxY + boxSize / 2, boxSize)
  glow.addColorStop(0, 'rgba(255, 215, 0, 0.25)')
  glow.addColorStop(1, 'rgba(0, 0, 0, 0)')
  ctx.fillStyle = glow
  ctx.fillRect(boxX - 30, boxY - 30, boxSize + 60, boxSize + 60)

  // Box border
  ctx.fillStyle = '#0b0c10'
  ctx.strokeStyle = '#ffd700'
  ctx.lineWidth = 2
  ctx.fillRect(boxX, boxY, boxSize, boxSize)
  ctx.strokeRect(boxX, boxY, boxSize, boxSize)

  // Trophy Emblem
  ctx.font = `${isStory ? 54 : 44}px sans-serif`
  ctx.fillText('🏆', width / 2, boxY + (isStory ? 76 : 64))

  // 7. Achievement Category Badge
  curY = boxY + boxSize + 35
  ctx.font = 'bold 13px "Cinzel", Georgia, serif'
  ctx.fillStyle = '#e6c845'
  const catText = `[ ${c.category?.toUpperCase() || 'ACHIEVEMENT'} ]`
  ctx.fillText(catText, width / 2, curY)

  // 8. Main Achievement Title
  curY += isStory ? 38 : 30
  ctx.font = 'bold 26px "Cinzel", Georgia, serif'
  ctx.fillStyle = '#ffffff'
  ctx.shadowColor = 'rgba(255, 215, 0, 0.5)'
  ctx.shadowBlur = 8
  wrapText(ctx, c.title, width / 2, curY, width - 100, 32)
  ctx.shadowBlur = 0

  // 9. Milestone Subtitle / Title if tiered
  if (m?.title && m.title !== c.title) {
    curY += isStory ? 45 : 35
    ctx.font = 'bold 17px "Cinzel", Georgia, serif'
    ctx.fillStyle = '#ecd485'
    ctx.fillText(`Milestone: ${m.title}`, width / 2, curY)
  }

  // 10. Milestone Description
  curY += isStory ? 38 : 30
  ctx.font = '15px "VT323", monospace, serif'
  ctx.fillStyle = '#b8ad9c'
  wrapText(ctx, m?.description || c.subtitle || '', width / 2, curY, width - 120, 22)

  // 11. Rewards & Completion Stamp
  curY += isStory ? 75 : 55

  // Reward pill box
  const rewW = 320
  const rewH = 38
  const rewX = (width - rewW) / 2
  ctx.fillStyle = 'rgba(10, 11, 15, 0.9)'
  ctx.strokeStyle = '#b59410'
  ctx.lineWidth = 1
  ctx.fillRect(rewX, curY - 24, rewW, rewH)
  ctx.strokeRect(rewX, curY - 24, rewW, rewH)

  ctx.font = 'bold 13px "Cinzel", serif'
  ctx.fillStyle = '#f3ede2'
  ctx.fillText(`REWARD: +${m?.xp_reward || 100} XP  •  +${m?.coins_reward || 50} COINS`, width / 2, curY)

  // 12. Verification & Stamp
  curY += isStory ? 55 : 42
  ctx.font = 'bold 13px "Press Start 2P", monospace'
  if (isClaimed) {
    ctx.fillStyle = '#48bb78'
    ctx.fillText('✓ CLAIMED & RECORDED', width / 2, curY)
  } else {
    ctx.fillStyle = '#ecc94b'
    ctx.fillText('★ UNLOCKED IN MINDREALM', width / 2, curY)
  }

  // 13. Footer / Watermark
  const footerY = height - (isStory ? 45 : 30)
  ctx.font = '11px "Cinzel", serif'
  ctx.fillStyle = '#706453'
  ctx.fillText('VERIFIED GAMEPLAY RECORD  •  STUDYQUEST.ONLINE', width / 2, footerY)

  rendering.value = false
}

// Helper to wrap text cleanly in canvas
function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
  if (!text) return
  const words = text.split(' ')
  let line = ''
  let curY = y

  for (let n = 0; n < words.length; n++) {
    const testLine = line + words[n] + ' '
    const metrics = ctx.measureText(testLine)
    const testWidth = metrics.width
    if (testWidth > maxWidth && n > 0) {
      ctx.fillText(line.trim(), x, curY)
      line = words[n] + ' '
      curY += lineHeight
    } else {
      line = testLine
    }
  }
  ctx.fillText(line.trim(), x, curY)
}

/**
 * Save & Download Poster (PNG or JPEG)
 * Only called when user explicitly clicks "Download PNG" or "Download JPEG"
 */
async function downloadPoster(format = 'png', targetFormat = null) {
  if (targetFormat && currentFormat.value !== targetFormat) {
    currentFormat.value = targetFormat
    await nextTick()
    drawPoster()
  }

  if (!posterCanvas.value) return

  try {
    const mime = format === 'jpeg' ? 'image/jpeg' : 'image/png'
    const ext = format === 'jpeg' ? 'jpg' : 'png'
    const cardSlug = (props.card?.title || 'achievement').toLowerCase().replace(/[^a-z0-9]/g, '_')
    const fileName = `StudyQuest_${cardSlug}_${currentFormat.value}.${ext}`

    const dataUrl = posterCanvas.value.toDataURL(mime, 0.95)
    const a = document.createElement('a')
    a.href = dataUrl
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

    toast.add(`Downloaded ${fileName}!`, 'success')
  } catch (err) {
    toast.add('Failed to export poster image.', 'error')
  }
}

</script>

<style scoped>
.share-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.share-sheet-container {
  background: #0d0e13;
  border: 1px solid rgba(212, 175, 55, 0.4);
  box-shadow: 0 0 0 2px #050608, 0 16px 40px rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  width: 100%;
  max-width: 960px;
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.animate-sheet-up {
  animation: slideUpModal 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUpModal {
  from {
    opacity: 0;
    transform: translateY(24px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Header */
.sheet-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.1rem 1.5rem;
  background: #12131a;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sheet-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 700;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.sheet-title {
  font-family: 'Cinzel', serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: #f3ede2;
  margin: 0.15rem 0 0 0;
}

.btn-close-sheet {
  background: transparent;
  border: none;
  color: #8b8070;
  font-size: 1.25rem;
  cursor: pointer;
  padding: 0.4rem 0.6rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-close-sheet:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.08);
}

/* Body */
.sheet-body {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 1.5rem;
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

@media (max-width: 860px) {
  .sheet-body {
    grid-template-columns: 1fr;
  }
}

/* Poster Preview Pane */
.poster-preview-pane {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  align-items: center;
}

.format-toggle-bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: space-between;
}

.format-label {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  color: #8b8070;
  text-transform: uppercase;
}

.format-buttons {
  display: flex;
  gap: 0.35rem;
}

.format-btn {
  background: #14151e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #a09789;
  font-family: 'Cinzel', serif;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.35rem 0.65rem;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.format-btn.active {
  background: #785a15;
  border-color: #ffd700;
  color: #ffffff;
}

.canvas-viewport {
  position: relative;
  background: #050608;
  border: 1px solid rgba(212, 175, 55, 0.4);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.8);
  border-radius: 6px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.canvas-viewport.view-story {
  aspect-ratio: 9 / 16;
  max-height: 480px;
}

.canvas-viewport.view-post {
  aspect-ratio: 1 / 1;
  max-height: 480px;
}

.rendered-poster {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.rendering-overlay {
  position: absolute;
  inset: 0;
  background: rgba(13, 14, 19, 0.85);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: #ecd485;
  font-family: 'Cinzel', serif;
  font-size: 0.85rem;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid rgba(212, 175, 55, 0.2);
  border-top-color: #ffd700;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.preview-caption {
  font-size: 0.72rem;
  color: #7a7060;
  text-align: center;
  line-height: 1.3;
}

/* Actions Pane */
.share-actions-pane {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.share-section {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.section-title {
  font-family: 'Cinzel', serif;
  font-size: 0.82rem;
  font-weight: 700;
  color: #b59410;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin: 0;
}

/* Size Options & Chooser */
.size-options-container {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.size-card {
  background: #111218;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  cursor: pointer;
  transition: all 0.25s ease;
  position: relative;
}

.size-card:hover {
  background: #161722;
  border-color: rgba(212, 175, 55, 0.4);
  transform: translateY(-1px);
}

.size-card.active {
  background: linear-gradient(135deg, rgba(28, 24, 34, 0.95) 0%, rgba(17, 18, 24, 0.95) 100%);
  border-color: #ffd700;
  box-shadow: 0 0 16px rgba(212, 175, 55, 0.15), inset 0 0 0 1px rgba(212, 175, 55, 0.3);
}

.size-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.size-badge-icon {
  font-size: 1.4rem;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.25);
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.size-meta {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.size-name {
  font-family: 'Cinzel', serif;
  font-size: 0.95rem;
  font-weight: 700;
  color: #f3ede2;
  letter-spacing: 0.03em;
}

.size-ratio {
  font-size: 0.72rem;
  color: #a89f91;
  font-family: monospace;
}

.selected-pill {
  background: #785a15;
  color: #ffd700;
  border: 1px solid #ffd700;
  font-family: 'Cinzel', serif;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.size-desc {
  font-size: 0.75rem;
  color: #9c907d;
  line-height: 1.4;
  margin: 0;
}

.size-download-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.btn-size-download {
  font-family: 'Cinzel', serif;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.55rem 0.75rem;
  border-radius: 5px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  transition: all 0.2s ease;
  letter-spacing: 0.03em;
}

.btn-size-download.primary {
  background: linear-gradient(135deg, #785a15 0%, #b59410 100%);
  color: #0d0e13;
  border: 1px solid #ffd700;
  font-weight: 800;
  box-shadow: 0 2px 8px rgba(212, 175, 55, 0.2);
}

.btn-size-download.primary:hover {
  background: linear-gradient(135deg, #94711e 0%, #d4af37 100%);
  color: #000;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.35);
}

.btn-size-download.secondary {
  background: #171822;
  color: #ecd485;
  border: 1px solid rgba(212, 175, 55, 0.3);
}

.btn-size-download.secondary:hover {
  background: #222332;
  border-color: #ffd700;
  color: #ffffff;
  transform: translateY(-1px);
}

/* Light / Morning Mode Theme Overrides */
:global([data-theme="morning"] .share-sheet-container) {
  background: #fdfaf3;
  border-color: #d4c4a8;
}

:global([data-theme="morning"] .sheet-header) {
  background: #f5eedf;
  border-color: #e2d7c3;
}

:global([data-theme="morning"] .sheet-title) {
  color: #2c2416;
}

:global([data-theme="morning"] .size-card) {
  background: #fbf7ee;
  border-color: #e2d7c3;
}

:global([data-theme="morning"] .size-card.active) {
  background: #f5edd9;
  border-color: #b59410;
}

:global([data-theme="morning"] .size-name) {
  color: #2c2416;
}
</style>
