/**
 * StudyQuest — Study Hall Browser Built-in Text-to-Speech (TTS) Service
 * Powered strictly by native Web Speech API (SpeechSynthesis & SpeechSynthesisUtterance).
 * Zero external APIs, zero audio file storage, zero dependencies.
 */
import { ref } from 'vue'

const isSupported = ref(
  typeof window !== 'undefined' &&
  'speechSynthesis' in window &&
  'SpeechSynthesisUtterance' in window
)

const isPlaying = ref(false)
const isPaused = ref(false)
const activePartId = ref(null)
const currentChunkIndex = ref(0)
const totalChunks = ref(0)
const currentText = ref('')
const voices = ref([])
const selectedVoice = ref(null)
const errorMessage = ref('')

let speechQueue = []
let queueIndex = 0
let activeSessionId = 0
// Keep reference to active utterance to prevent Chromium/Opera garbage-collection bug
let activeUtterance = null

/**
 * Load available system voices asynchronously and pick a suitable English voice.
 */
function loadVoices() {
  if (!isSupported.value) return []
  try {
    const available = window.speechSynthesis.getVoices() || []
    voices.value = available

    if (available.length > 0 && !selectedVoice.value) {
      // Prioritize English voices, preferring natural / standard academic voices
      const enVoices = available.filter(v => v.lang && v.lang.toLowerCase().startsWith('en'))
      const preferred = enVoices.find(v =>
        /natural|google|samantha|jenny|guy|aria|andrew|daniel/i.test(v.name)
      ) || enVoices.find(v => v.default) || enVoices[0] || available[0]

      selectedVoice.value = preferred || null
    }
    return available
  } catch (err) {
    console.warn('[StudyHallTTS] Error querying voices:', err)
    return []
  }
}

// Initialize voices listener
if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
  loadVoices()
  if (window.speechSynthesis.onvoiceschanged !== undefined) {
    window.speechSynthesis.onvoiceschanged = () => {
      loadVoices()
    }
  }
  // Safety: stop on page unload
  window.addEventListener('beforeunload', () => {
    stop()
  })
}

/**
 * Extract actual educational content from the reviewer part data.
 * Filters out all interface elements, navigation, skip links, and assessment questions.
 */
function extractReviewerChunks(part) {
  if (!part) return []

  const chunks = []

  function addParagraph(text, prefix = '') {
    if (!text || typeof text !== 'string') return
    const cleaned = text.trim()
    if (!cleaned) return

    // Short paragraphs (< 220 chars) can be read as a single chunk
    if (cleaned.length <= 220) {
      chunks.push((prefix ? prefix + ' ' : '') + cleaned)
      return
    }

    // Split longer academic paragraphs along sentence boundaries (. ! ?)
    const sentences = cleaned.match(/[^.!?]+[.!?]+(\s+|$)|[^.!?]+$/g) || [cleaned]
    let currentBuffer = prefix ? prefix + ' ' : ''

    for (const s of sentences) {
      const sentence = s.trim()
      if (!sentence) continue

      if (currentBuffer.length + sentence.length > 200 && currentBuffer.trim().length > 0) {
        chunks.push(currentBuffer.trim())
        currentBuffer = sentence
      } else {
        currentBuffer += (currentBuffer.length ? ' ' : '') + sentence
      }
    }
    if (currentBuffer.trim()) {
      chunks.push(currentBuffer.trim())
    }
  }

  // 1. Reviewer Title & Overview
  if (part.part_number && part.title) {
    chunks.push(`Part ${part.part_number}: ${part.title}.`)
  } else if (part.title) {
    chunks.push(`${part.title}.`)
  }

  if (part.description) {
    addParagraph(part.description)
  }

  // 2. Section 1: Foundational Doctrine & Clinical Overview
  if (part.overview) {
    chunks.push('Section 1: Foundational Doctrine and Clinical Overview.')
    const paras = Array.isArray(part.overview)
      ? part.overview
      : part.overview.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))
    chunks.push('Licensure Examination Focus: Pay rigorous attention to the operational behavioral thresholds and duration requirements that distinguish this category from related phenomena.')
  }

  // 3. Section 2: DSM-5 Diagnostic Criteria & Symptom Thresholds
  if (part.diagnostic_criteria) {
    chunks.push('Section 2: DSM-5 Diagnostic Criteria and Symptom Thresholds.')
    const paras = Array.isArray(part.diagnostic_criteria)
      ? part.diagnostic_criteria
      : part.diagnostic_criteria.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))
  }

  // 4. Section 3: Subtypes, Specifiers & Severity Stratification
  if (part.specifiers) {
    chunks.push('Section 3: Subtypes, Specifiers, and Severity Stratification.')
    const paras = Array.isArray(part.specifiers)
      ? part.specifiers
      : part.specifiers.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))
  }

  // 5. Section 4: Differential Diagnosis & Clinical Boundaries
  if (part.differential_diagnosis) {
    chunks.push('Section 4: Differential Diagnosis and Clinical Boundaries.')
    const paras = Array.isArray(part.differential_diagnosis)
      ? part.differential_diagnosis
      : part.differential_diagnosis.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))
  }

  // 6. Section 5: Core Principles & Diagnostic Nomenclature
  if ((part.key_concepts && part.key_concepts.length) || (part.important_terms && part.important_terms.length)) {
    chunks.push('Section 5: Core Principles and Diagnostic Nomenclature.')

    if (part.key_concepts && part.key_concepts.length) {
      chunks.push('Theoretical and Clinical Principles.')
      part.key_concepts.forEach(kc => {
        if (kc.title && kc.definition) {
          addParagraph(kc.definition, kc.title + ':')
        }
      })
    }

    if (part.important_terms && part.important_terms.length) {
      chunks.push('Board Exam Vocabulary and Operational Definitions.')
      part.important_terms.forEach(t => {
        if (t.term && t.definition) {
          addParagraph(t.definition, t.term + ':')
        }
      })
    }
  }

  // 7. Section 6: Applied Clinical Vignette & Case Formulation
  if (part.clinical_example) {
    chunks.push('Section 6: Applied Clinical Vignette and Case Formulation.')
    const paras = Array.isArray(part.clinical_example)
      ? part.clinical_example
      : part.clinical_example.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))

    chunks.push('Clinical Implications and Diagnostic Reasoning.')
    chunks.push('Differential diagnosis requires ruling out secondary physiological effects of medical illnesses, toxicities, or prescribed pharmacotherapies prior to establishing primary psychiatric diagnoses.')
    chunks.push('Symptom presentation must satisfy comprehensive duration, clustering, and functional impairment benchmarks.')
    chunks.push('Diagnostic formulations must be grounded in multi-informant corroboration and qualified clinical interviewing.')
  }

  // 8. Section 7: Curricular Synthesis & Licensure Board Pearls
  if (part.summary_notes) {
    chunks.push('Section 7: Curricular Synthesis and Licensure Board Pearls.')
    const paras = Array.isArray(part.summary_notes)
      ? part.summary_notes
      : part.summary_notes.split(/\n\s*\n|\r\n\r\n/)
    paras.forEach(p => addParagraph(p))
  }

  chunks.push('Conclusion of reviewer lecture. You may now proceed to Section 8 to complete the Part Knowledge Assessment.')

  return chunks
}

/**
 * Sequential utterance execution.
 */
function playNextChunk(sessionId) {
  if (!isPlaying.value || sessionId !== activeSessionId) {
    return
  }

  if (queueIndex >= speechQueue.length) {
    // Finished reading all chunks of this reviewer
    stop()
    return
  }

  const chunkText = speechQueue[queueIndex]
  currentChunkIndex.value = queueIndex + 1
  currentText.value = chunkText

  try {
    const utterance = new SpeechSynthesisUtterance(chunkText)
    activeUtterance = utterance // Retain reference to prevent garbage-collection

    // Ensure voices are queried
    if (!selectedVoice.value) {
      loadVoices()
    }

    if (selectedVoice.value) {
      utterance.voice = selectedVoice.value
    }
    utterance.lang = selectedVoice.value?.lang || 'en-US'
    utterance.rate = 1.0
    utterance.pitch = 1.0
    utterance.volume = 1.0

    utterance.onstart = () => {
      console.log(`[StudyHallTTS] Speaking chunk ${queueIndex + 1}/${speechQueue.length}`)
    }

    utterance.onend = () => {
      activeUtterance = null
      if (sessionId !== activeSessionId || !isPlaying.value) return
      queueIndex++
      // Natural brief pause between chunks (100ms)
      setTimeout(() => {
        playNextChunk(sessionId)
      }, 100)
    }

    utterance.onerror = (event) => {
      activeUtterance = null
      if (sessionId !== activeSessionId || !isPlaying.value) return
      // Ignorable user cancellation or interruption
      if (event.error === 'canceled' || event.error === 'interrupted') {
        return
      }
      console.warn('[StudyHallTTS] Utterance error:', event.error)
      queueIndex++
      if (queueIndex < speechQueue.length) {
        setTimeout(() => {
          playNextChunk(sessionId)
        }, 120)
      } else {
        stop()
      }
    }

    // Chromium/Opera audio wake-up
    if (window.speechSynthesis.paused) {
      window.speechSynthesis.resume()
    }

    window.speechSynthesis.speak(utterance)

    // Secondary resume check for browsers that start in suspended audio state
    if (window.speechSynthesis.paused) {
      window.speechSynthesis.resume()
    }
  } catch (err) {
    console.warn('[StudyHallTTS] Speech dispatch error:', err)
    stop()
  }
}

/**
 * Begin reading the given reviewer part.
 */
function speakReviewer(part) {
  if (!isSupported.value) {
    errorMessage.value = 'Text-to-Speech is not supported in your browser.'
    return
  }
  if (!part) return

  // Cancel any previous speech
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    try {
      window.speechSynthesis.cancel()
    } catch (e) {
      // ignore
    }
  }

  // Advance session ID so any pending callbacks from previous sessions are discarded
  const currentSession = ++activeSessionId

  const chunks = extractReviewerChunks(part)
  if (!chunks.length) return

  speechQueue = chunks
  queueIndex = 0
  totalChunks.value = chunks.length
  currentChunkIndex.value = 1
  activePartId.value = part.id || part.part_number || 'active'
  isPlaying.value = true
  isPaused.value = false
  errorMessage.value = ''

  if (!selectedVoice.value) {
    loadVoices()
  }

  // Start reading the first chunk
  playNextChunk(currentSession)
}

/**
 * Pause active speech.
 */
function pause() {
  if (!isPlaying.value || isPaused.value) return
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    try {
      window.speechSynthesis.pause()
      isPaused.value = true
    } catch (err) {
      console.warn('[StudyHallTTS] Pause error:', err)
    }
  }
}

/**
 * Resume paused speech.
 */
function resume() {
  if (!isPlaying.value || !isPaused.value) return
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    try {
      window.speechSynthesis.resume()
      isPaused.value = false
    } catch (err) {
      console.warn('[StudyHallTTS] Resume error:', err)
    }
  }
}

/**
 * Immediately stop and cancel all speech.
 */
function stop() {
  activeSessionId++
  activeUtterance = null

  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    try {
      window.speechSynthesis.cancel()
    } catch (err) {
      console.warn('[StudyHallTTS] Cancel error:', err)
    }
  }

  isPlaying.value = false
  isPaused.value = false
  activePartId.value = null
  speechQueue = []
  queueIndex = 0
  currentChunkIndex.value = 0
  totalChunks.value = 0
  currentText.value = ''
}

/**
 * Toggle Read Aloud ON / OFF for the given reviewer.
 */
function toggle(part) {
  if (isPlaying.value) {
    stop()
  } else {
    speakReviewer(part)
  }
}

export const studyHallTtsService = {
  isSupported,
  isPlaying,
  isPaused,
  activePartId,
  currentChunkIndex,
  totalChunks,
  currentText,
  voices,
  selectedVoice,
  errorMessage,
  loadVoices,
  speakReviewer,
  pause,
  resume,
  stop,
  toggle
}
