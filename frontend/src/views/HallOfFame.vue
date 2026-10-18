<template>
  <div class="hall-of-fame-page">
    <!-- Atmospheric Ambient Background -->
    <div class="fame-bg-environment" aria-hidden="true">
      <div class="fame-bg-radial"></div>
      <div class="fame-bg-vignette"></div>
      <div class="fame-particle p1"></div>
      <div class="fame-particle p2"></div>
      <div class="fame-particle p3"></div>
      <div class="fame-particle p4"></div>
      <div class="fame-particle p5"></div>
    </div>

    <!-- Main Content Container -->
    <main class="fame-container animate-fade-in">
      <!-- 1. Ceremonial Header -->
      <header class="fame-header">
        <div class="ceremonial-banner text-center">
          <div class="monumental-crown">
            <span class="crown-icon">👑</span>
            <div class="crown-glow"></div>
          </div>
          <h1 class="fame-title font-pixel">HALL OF FAME</h1>
          <p class="fame-subtitle">
            Celebrating the dedication, perseverance, and achievements of our 2026 Board Examination Passers.
          </p>
          <div class="fame-metrics-summary font-pixel">
            <span class="summary-pill">🏛️ 2026 INAUGURAL CYCLE</span>
            <span class="summary-bullet">✦</span>
            <span class="summary-pill">⚔️ {{ announcements.length }} REGISTERED HEROES INSCRIBED</span>
            <span class="summary-bullet">✦</span>
            <span class="summary-pill">🛡️ PRC BOARD OF PSYCHOLOGY</span>
          </div>
          <div class="fame-gold-separator">
            <span class="diamond">❖</span>
          </div>
        </div>

        <!-- Call to Action Banner: "I PASSED THE 2026 BOARD EXAM" (Only visible if user hasn't submitted yet) -->
        <div v-if="userClaims.length === 0" class="fame-cta-banner">
          <div class="cta-banner-content">
            <div class="cta-text">
              <h3 class="cta-heading font-pixel">HAVE YOU CONQUERED THE 2026 BOARD EXAM?</h3>
              <p class="cta-desc">
                Submit your claim to be verified against official 2026 PRC licensure results and inscribe your hero handle upon the realm honor roll.
              </p>
            </div>
            <button
              class="btn-claim-fame font-pixel"
              @click="openClaimModal"
              id="hof-verify-btn"
            >
              <span class="btn-shine"></span>
              <PixelEmoji emoji="sparkles" size="sm" />
              <span>I PASSED THE 2026 BOARD EXAM</span>
            </button>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="fame-tabs-bar">
          <button
            class="fame-tab-btn font-pixel"
            :class="{ active: currentTab === 'scroll' }"
            @click="currentTab = 'scroll'"
          >
            <span>📜 2026 BOARD EXAM PASSERS</span>
            <span class="tab-count" v-if="announcements.length">({{ announcements.length }})</span>
          </button>
          <button
            class="fame-tab-btn font-pixel"
            :class="{ active: currentTab === 'my-claim' }"
            @click="currentTab = 'my-claim'"
          >
            <span>🛡️ MY CLAIM STATUS</span>
            <span v-if="userClaims.length" class="tab-dot pulse"></span>
          </button>
          <button
            v-if="isAdmin"
            class="fame-tab-btn font-pixel admin-tab"
            :class="{ active: currentTab === 'admin' }"
            @click="currentTab = 'admin'"
          >
            <span>⚙️ VERIFICATION QUEUE</span>
            <span v-if="adminPendingCount" class="tab-badge">{{ adminPendingCount }}</span>
          </button>
        </div>
      </header>

      <!-- ══════════════════════════════════════════════════════════
           TAB 1: 2026 OFFICIAL BOARD EXAM PASSERS ROSTER (LIST FORMAT)
           ══════════════════════════════════════════════════════════ -->
      <section v-if="currentTab === 'scroll'" class="fame-tab-content">
        <!-- Roster Controls & Filter Toolbar -->
        <div class="roster-toolbar-panel gothic-panel">
          <div class="filter-group-left">
            <div class="roster-filter-pills">
              <button
                class="roster-pill-btn font-pixel"
                :class="{ active: rosterFilter === 'all' }"
                @click="rosterFilter = 'all'"
              >
                ⚔️ ALL 2026 PASSERS ({{ announcements.length }})
              </button>
              <button
                class="roster-pill-btn font-pixel"
                :class="{ active: rosterFilter === 'full' }"
                @click="rosterFilter = 'full'"
              >
                📜 FULL NAMES
              </button>
              <button
                class="roster-pill-btn font-pixel"
                :class="{ active: rosterFilter === 'masked' }"
                @click="rosterFilter = 'masked'"
              >
                🔒 PRIVACY MASKED
              </button>
            </div>
          </div>

          <div class="filter-group-right flex items-center gap-3">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search username, passer, or school..."
                class="search-input"
              />
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="search-clear-btn"
                title="Clear search"
              >
                ✕
              </button>
            </div>
            <div class="roster-count-badge font-pixel">
              <span class="pulse-dot"></span>
              <span>{{ filteredAnnouncements.length }} / {{ announcements.length }} PASSERS</span>
            </div>
          </div>
        </div>

        <!-- Grand Roster List Stage -->
        <div class="roster-stage-container">
          <!-- Loading State -->
          <div v-if="loadingAnnouncements" class="fame-loading-box gothic-panel">
            <div class="loading-rune-spinner">❖</div>
            <p class="font-pixel text-xs">COMMUNING WITH REALM 2026 ARCHIVES...</p>
          </div>

          <!-- Empty State -->
          <div v-else-if="filteredAnnouncements.length === 0" class="fame-empty-box gothic-panel">
            <PixelEmoji emoji="scroll" size="game" />
            <h4 class="font-pixel text-gold mt-3 text-lg">NO 2026 PASSERS FOUND</h4>
            <p class="text-sm text-muted max-w-md mx-auto mt-2">
              {{ searchQuery ? 'No 2026 verified passers matched your search query.' : 'No verified passers found for this selection.' }}
            </p>
          </div>

          <!-- Vertical List of 2026 Passers -->
          <div v-else class="roster-vertical-list">
            <div
              v-for="(ann, idx) in filteredAnnouncements"
              :key="ann.id"
              class="roster-item-slate gothic-panel"
              @click="openInspectionModal(ann)"
            >
              <!-- Ambient Slate Glow Frame -->
              <div class="slate-glow-border"></div>

              <!-- Left Section: Registered Player Avatar & Account Identity -->
              <div class="slate-left-col">
                <div class="player-account-cluster">
                  <div class="player-avatar-frame">
                    <AvatarImg
                      :character="ann.class_type"
                      :player="ann"
                      :size="52"
                    />
                    <div class="online-gem"></div>
                  </div>
                  <div class="player-text-wrap">
                    <div class="player-username font-pixel">
                      @{{ ann.username || 'hero' }}
                    </div>
                    <div class="player-hero-tag font-pixel">
                      <span class="star-icon">✨</span>
                      <span>BOARD EXAM PASSER</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Middle Section: Passer Full/Masked Name, School, and Batch Proclamation -->
              <div class="slate-mid-col">
                <div class="proclamation-header flex items-center gap-2">
                  <span class="proclamation-prefix font-pixel">OFFICIAL CONGRATULATIONS</span>
                  <span class="header-divider">•</span>
                  <span class="board-exam-tag font-pixel">2026 BOARD EXAM PASSER</span>
                </div>

                <h3 class="passer-honor-name font-pixel">
                  {{ ann.display_name }}
                </h3>

                <div class="passer-meta-row flex flex-wrap items-center gap-2 mt-2">
                  <span v-if="ann.school" class="meta-pill school-pill">
                    <span class="pill-icon">🏛️</span>
                    <span class="pill-text">{{ ann.school }}</span>
                  </span>
                  <span class="meta-pill batch-pill font-pixel">
                    <span class="pill-icon">📜</span>
                    <span class="pill-text">{{ ann.batch_name.toUpperCase() }} (2026)</span>
                  </span>
                  <span
                    class="privacy-pill font-pixel"
                    :class="ann.display_preference.toLowerCase()"
                    :title="ann.display_preference === 'MASKED' ? 'Protected with Privacy Masking' : 'Full Name Verified'"
                  >
                    {{ ann.display_preference === 'MASKED' ? '🔒 PRIVACY MASKED' : '📜 FULL LEGAL NAME' }}
                  </span>
                </div>
              </div>

              <!-- Right Section: PRC Verified Seal & Inspection Modal Trigger -->
              <div class="slate-right-col">
                <div class="prc-seal-badge">
                  <div class="seal-check-circle">✓</div>
                  <div class="seal-text-col">
                    <strong class="seal-header font-pixel">PRC VERIFIED</strong>
                    <span class="seal-sub">Cross-Referenced</span>
                  </div>
                </div>

                <button
                  class="btn-inspect-action font-pixel"
                  @click.stop="openInspectionModal(ann)"
                  title="Inspect official PRC verification proof certificate"
                >
                  <span>INSPECT PROOF</span>
                  <span class="arrow-icon">↗</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom Summary Proclamation Strip -->
          <div class="roster-bottom-bar flex items-center justify-between mt-4">
            <div class="bottom-left-note font-pixel text-xs text-muted flex items-center gap-2">
              <span class="pulsing-radar-dot"></span>
              <span>2026 LICENSURE EXAMINATION ARCHIVE • REAL-TIME SYNCHRONIZED</span>
            </div>
            <div class="bottom-right-stats font-pixel text-xs text-gold">
              Showing {{ filteredAnnouncements.length }} of {{ announcements.length }} Registered 2026 RPms
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════════
           TAB 2: MY VERIFICATION STATUS
           ══════════════════════════════════════════════════════════ -->
      <section v-else-if="currentTab === 'my-claim'" class="fame-tab-content">
        <!-- If user has not submitted any claims yet -->
        <div v-if="userClaims.length === 0" class="my-claims-empty gothic-panel text-center">
          <PixelEmoji emoji="shield" size="game" />
          <h3 class="font-pixel text-gold mt-3 text-lg">NO PASSING CLAIMS SUBMITTED</h3>
          <p class="text-muted max-w-md mx-auto mt-2 text-sm">
            Have you completed and passed the Psychometrician Licensure Examination? Inscribe your deed into the realm archives by submitting your claim today.
          </p>
          <button class="btn-claim-fame font-pixel mt-4" @click="openClaimModal">
            VERIFY MY PASSING RESULT
          </button>
        </div>

        <!-- User's Claims List -->
        <div v-else class="my-claims-list">
          <div v-for="claim in userClaims" :key="claim.id" class="claim-status-card gothic-panel">
            <div class="claim-card-header flex items-center justify-between">
              <div>
                <span class="claim-date font-pixel">SUBMITTED ON {{ formatDate(claim.created_at) }}</span>
                <h3 class="claim-batch-title font-pixel text-gold">{{ claim.batch_name }} — {{ claim.exam_name }}</h3>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="btn-edit-claim font-pixel"
                  @click="openEditClaimModal(claim)"
                  title="Edit your submitted legal name or display preference"
                >
                  ✏️ EDIT NAME
                </button>
                <span class="claim-status-badge font-pixel" :class="claim.claim_status.toLowerCase()">
                  {{ formatStatus(claim.claim_status) }}
                </span>
              </div>
            </div>

            <!-- Visual 2-Stage Progress Bar -->
            <div class="verification-stepper">
              <div class="step-item" :class="{ completed: isStepDone(claim.claim_status, 1), active: isStepActive(claim.claim_status, 1) }">
                <div class="step-bubble font-pixel">1</div>
                <div class="step-meta">
                  <span class="step-title font-pixel">CLAIM SUBMITTED</span>
                  <span class="step-desc">Name & batch recorded</span>
                </div>
              </div>
              <div class="step-line" :class="{ completed: isStepDone(claim.claim_status, 2) }"></div>
              <div class="step-item" :class="{ completed: isStepDone(claim.claim_status, 2), active: isStepActive(claim.claim_status, 2) }">
                <div class="step-bubble font-pixel">2</div>
                <div class="step-meta">
                  <span class="step-title font-pixel">PRC ROSTER MATCHED & APPROVED</span>
                  <span class="step-desc">{{ isMatchPassed(claim.match_status) ? 'Passed & Inscribed upon Hall of Fame' : 'Unmatched' }}</span>
                </div>
              </div>
            </div>

            <!-- Claim Breakdown Details -->
            <div class="claim-details-grid">
              <div class="detail-cell">
                <div class="flex items-center justify-between mb-0.5">
                  <span class="detail-label font-pixel">SUBMITTED LEGAL NAME</span>
                  <button
                    type="button"
                    class="btn-edit-inline font-pixel"
                    @click="openEditClaimModal(claim)"
                    title="Edit legal name"
                  >
                    ✏️ EDIT
                  </button>
                </div>
                <strong class="detail-value">{{ claim.submitted_full_name }}</strong>
              </div>
              <div class="detail-cell">
                <span class="detail-label font-pixel">PUBLIC DISPLAY PREFERENCE</span>
                <strong class="detail-value flex items-center gap-1">
                  <span>{{ claim.display_preference === 'MASKED' ? '🔒 Privacy Masked' : '📜 Full Legal Name' }}</span>
                  <span class="masked-note">({{ claim.masked_name_preview }})</span>
                </strong>
              </div>
              <div class="detail-cell">
                <span class="detail-label font-pixel">MATCH VERIFICATION STATUS</span>
                <span class="match-badge font-pixel" :class="getMatchStatusClass(claim.match_status)">
                  {{ formatMatchStatus(claim.match_status) }}
                </span>
              </div>
              <div class="detail-cell">
                <span class="detail-label font-pixel">OFFICIAL PRC SOURCE</span>
                <a :href="claim.official_source_url" target="_blank" rel="noopener noreferrer" class="link-external font-pixel">
                  VIEW PRC BULLETIN ↗
                </a>
              </div>
            </div>

            <!-- If Approved: Show Live Announcement Snippet -->
            <div v-if="claim.claim_status === 'APPROVED'" class="approved-congrats-banner">
              <span class="party-icon">🎉</span>
              <div>
                <h4 class="font-pixel text-gold">CONGRATULATIONS, REGISTERED PSYCHOMETRICIAN!</h4>
                <p class="text-xs">
                  Your verified passing announcement is currently inscribed and active upon the Hall of Fame live scroll:
                </p>
                <div class="scroll-snippet font-pixel">
                  {{ claim.announcement_format }}
                </div>
              </div>
            </div>

            <!-- If Rejected / Needs Correction -->
            <div v-else-if="claim.claim_status === 'REJECTED' || claim.claim_status === 'NEEDS_CORRECTION'" class="alert-review-note">
              <span class="warn-icon">⚠️</span>
              <div>
                <strong class="font-pixel text-crimson">REVIEW NOTE FROM ADMINISTRATOR:</strong>
                <p class="text-xs mt-1">{{ claim.review_notes || 'No match found in the official PRC Successful Examinees list for this batch. Please verify spelling or batch selection.' }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ══════════════════════════════════════════════════════════
           TAB 4: ADMIN VERIFICATION QUEUE (AUTHORIZED USERS)
           ══════════════════════════════════════════════════════════ -->
      <section v-if="currentTab === 'admin' && isAdmin" class="fame-tab-content">
        <div class="admin-toolbar gothic-panel mb-4 flex items-center justify-between">
          <div class="admin-filter-pills flex items-center gap-2">
            <span class="font-pixel text-xs text-muted">QUEUE FILTER:</span>
            <button
              v-for="st in ['ALL', 'PENDING_IDENTITY_REVIEW', 'APPROVED', 'REJECTED']"
              :key="st"
              class="admin-filter-pill font-pixel"
              :class="{ active: adminQueueFilter === st }"
              @click="setAdminFilter(st)"
            >
              {{ st.replace(/_/g, ' ') }}
            </button>
          </div>

          <div class="admin-actions-right flex items-center gap-2">
            <button class="btn btn-gothic-ghost btn-xs font-pixel" @click="fetchAdminQueue">
              ↻ REFRESH QUEUE
            </button>
            <button class="btn btn-gothic-crimson btn-xs font-pixel" @click="showImportModal = true">
              + IMPORT PRC ROSTER
            </button>
          </div>
        </div>

        <div v-if="adminLoading" class="fame-loading-box">
          <div class="loading-rune-spinner">❖</div>
          <p class="font-pixel text-xs">LOADING ADMINISTRATIVE QUEUE...</p>
        </div>

        <div v-else-if="adminQueue.length === 0" class="fame-empty-box gothic-panel">
          <p class="font-pixel text-gold">NO CLAIMS IN QUEUE FOR THIS FILTER</p>
        </div>

        <div v-else class="admin-claims-table gothic-panel custom-scrollbar">
          <table class="w-full text-left">
            <thead>
              <tr class="table-head-row font-pixel">
                <th>ID</th>
                <th>APPLICANT</th>
                <th>SUBMITTED NAME</th>
                <th>BATCH</th>
                <th>MATCH RESULT</th>
                <th>DISPLAY PREF</th>
                <th>STATUS</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in adminQueue" :key="item.id" class="table-body-row">
                <td class="font-mono text-xs">#{{ item.id }}</td>
                <td>
                  <div class="applicant-cell">
                    <strong>{{ item.nickname || item.username }}</strong>
                    <span class="text-xs text-muted">{{ item.email }}</span>
                  </div>
                </td>
                <td>
                  <div class="submitted-cell">
                    <strong>{{ item.submitted_full_name }}</strong>
                    <span v-if="item.official_full_name" class="text-xs text-gold">
                      PRC Roster: {{ item.official_full_name }}
                    </span>
                  </div>
                </td>
                <td class="font-pixel text-xs">{{ item.batch_name }}</td>
                <td>
                  <span class="match-badge font-pixel" :class="getMatchStatusClass(item.match_status)">
                    {{ formatMatchStatus(item.match_status) }}
                  </span>
                </td>
                <td class="font-pixel text-xs">
                  {{ item.display_preference === 'MASKED' ? '🔒 Masked' : '📜 Full' }}
                </td>
                <td>
                  <span class="claim-status-badge font-pixel" :class="item.claim_status.toLowerCase()">
                    {{ item.claim_status }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons-cluster">
                    <button
                      v-if="item.claim_status !== 'APPROVED'"
                      class="btn-action approve font-pixel"
                      @click="adminDecide(item.id, 'APPROVE')"
                      title="Approve & Inscribe onto live scroll"
                    >
                      ✓ APPROVE
                    </button>
                    <button
                      v-if="item.claim_status !== 'REJECTED'"
                      class="btn-action reject font-pixel"
                      @click="adminDecide(item.id, 'REJECT')"
                      title="Reject claim"
                    >
                      ✕ REJECT
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- ══════════════════════════════════════════════════════════
         MODAL 1: "I PASSED THE BOARD EXAM" CLAIM SUBMISSION
         ══════════════════════════════════════════════════════════ -->
    <div v-if="showClaimModal" class="fame-modal-backdrop" @click.self="closeClaimModal">
      <div class="fame-modal-card gothic-panel animate-scale-up">
        <div class="modal-corner tl"></div>
        <div class="modal-corner tr"></div>
        <div class="modal-corner bl"></div>
        <div class="modal-corner br"></div>

        <div class="modal-header">
          <div class="flex items-center gap-2">
            <PixelEmoji emoji="crown" size="sm" />
            <h2 class="modal-title font-pixel text-gold">
              {{ isEditingClaim ? 'EDIT 2026 BOARD EXAM PASSING CLAIM' : 'VERIFY 2026 BOARD EXAM PASSING RESULT' }}
            </h2>
          </div>
          <button class="modal-close-btn font-pixel" @click="closeClaimModal">✕</button>
        </div>

        <form @submit.prevent="submitClaimForm" class="modal-form-body">
          <!-- Field A: Name as Published by PRC (Separate First Name, Last Name, and Middle Name) -->
          <div class="form-group name-fields-group">
            <label class="form-label font-pixel">
              NAME AS PUBLISHED BY THE PRC <span class="text-crimson">*</span>
            </label>
            
            <div class="name-inputs-grid">
              <div class="name-subfield">
                <label class="subfield-label font-pixel">
                  FIRST NAME <span class="text-crimson">*</span>
                </label>
                <input
                  v-model="claimForm.first_name"
                  type="text"
                  required
                  class="form-input"
                  placeholder="e.g. Juan Pedro"
                  autocomplete="off"
                />
              </div>

              <div class="name-subfield">
                <label class="subfield-label font-pixel">
                  LAST NAME / SURNAME <span class="text-crimson">*</span>
                </label>
                <input
                  v-model="claimForm.last_name"
                  type="text"
                  required
                  class="form-input"
                  placeholder="e.g. Dela Cruz"
                  autocomplete="off"
                />
              </div>

              <div class="name-subfield">
                <label class="subfield-label font-pixel">
                  MIDDLE NAME <span class="optional-tag font-pixel">(OPTIONAL)</span>
                </label>
                <input
                  v-model="claimForm.middle_name"
                  type="text"
                  class="form-input"
                  placeholder="e.g. Mendoza"
                  autocomplete="off"
                />
              </div>
            </div>

            <!-- PRC Format Preview Strip -->
            <div class="prc-format-preview-strip gothic-panel">
              <span class="preview-lbl font-pixel text-muted">PRC FORMATTED NAME:</span>
              <strong class="preview-val font-pixel text-gold">
                {{ prcFormattedName || 'LAST NAME, FIRST NAME MIDDLE NAME' }}
              </strong>
            </div>
            <p class="form-hint">
              PRC records are officially published as <strong class="text-gold">SURNAME, FIRST NAME MIDDLE NAME</strong>. StudyQuest automatically structures your input into this PRC standard for verification against 2026 examinees results.
            </p>
          </div>

          <!-- Field B: Examination Type -->
          <div class="form-group">
            <label class="form-label font-pixel">EXAMINATION / PROFESSION</label>
            <select v-model="claimForm.exam_type" class="form-select" disabled>
              <option value="BLEPP">Psychometrician Licensure Examination (RPm / BLEPP)</option>
            </select>
          </div>

          <!-- Field C: Examination Batch -->
          <div class="form-group">
            <label class="form-label font-pixel">
              EXAMINATION BATCH (2026 CYCLE) <span class="text-crimson">*</span>
            </label>
            <select v-model="claimForm.batch_id" required class="form-select">
              <option :value="0" disabled>Select your 2026 examination batch...</option>
              <option v-for="b in batches" :key="b.id" :value="b.id">
                {{ b.batch_name }} ({{ b.exam_name }})
              </option>
            </select>
            <p class="form-hint">
              StudyQuest was launched in 2026; only passers of the 2026 board examination are eligible for realm verification.
            </p>
          </div>

          <!-- Field D: Public Display Preference (Full vs Masked) -->
          <div class="form-group">
            <label class="form-label font-pixel">
              PUBLIC DISPLAY PREFERENCE <span class="text-crimson">*</span>
            </label>
            <div class="display-pref-cards">
              <label
                class="pref-card-option"
                :class="{ active: claimForm.display_preference === 'FULL' }"
              >
                <input
                  type="radio"
                  v-model="claimForm.display_preference"
                  value="FULL"
                  class="sr-only"
                />
                <div class="pref-card-header font-pixel">
                  <span>📜 DISPLAY MY FULL NAME</span>
                </div>
                <p class="pref-card-desc">
                  Show your full legal name upon the public Hall of Fame scroll.
                </p>
              </label>

              <label
                class="pref-card-option"
                :class="{ active: claimForm.display_preference === 'MASKED' }"
              >
                <input
                  type="radio"
                  v-model="claimForm.display_preference"
                  value="MASKED"
                  class="sr-only"
                />
                <div class="pref-card-header font-pixel">
                  <span>🔒 DISPLAY MY MASKED NAME</span>
                </div>
                <p class="pref-card-desc">
                  Mask your name to protect your privacy on the public announcement scroll.
                </p>
              </label>
            </div>

            <!-- Dynamic Live Masking Preview -->
            <div class="masking-live-preview gothic-panel">
              <span class="preview-tag font-pixel text-muted">LIVE PUBLIC SCROLL PREVIEW:</span>
              <div class="preview-scroll-text font-pixel">
                CONGRATULATIONS, {{ dynamicScrollPreview }}, RPm! — BATCH {{ selectedBatchName.toUpperCase() }}
              </div>
            </div>
          </div>

          <!-- Field E: Required Confirmations & Consents -->
          <div class="form-group consent-group">
            <label class="consent-checkbox-label">
              <input v-model="claimForm.consent_1" type="checkbox" required />
              <span>
                I confirm that I am submitting a genuine passing claim and consent to automated comparison against official PRC examination results.
              </span>
            </label>
            <label class="consent-checkbox-label mt-2">
              <input v-model="claimForm.consent_2" type="checkbox" required />
              <span>
                I confirm that matching my name in the official PRC examinees roster will immediately verify and automatically inscribe my passing achievement into the Hall of Fame.
              </span>
            </label>
          </div>

          <!-- Feedback Alert -->
          <div v-if="submissionFeedback" class="feedback-alert" :class="submissionFeedback.type">
            <span>{{ submissionFeedback.message }}</span>
          </div>

          <!-- Modal Actions -->
          <div class="modal-actions-row">
            <button type="button" class="btn btn-gothic-ghost font-pixel" @click="closeClaimModal">
              CANCEL
            </button>
            <button
              type="submit"
              class="btn-submit-verify font-pixel"
              :disabled="submittingClaim || !claimForm.consent_1 || !claimForm.consent_2 || !claimForm.first_name.trim() || !claimForm.last_name.trim()"
            >
              <span>{{ submittingClaim ? 'VERIFYING AGAINST PRC ROSTER...' : (isEditingClaim ? 'UPDATE & RE-VERIFY CLAIM' : 'VERIFY MY PASSING RESULT') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL 2: INSPECT ANNOUNCEMENT VERIFICATION PROOF
         ══════════════════════════════════════════════════════════ -->
    <div v-if="inspectionModalData" class="fame-modal-backdrop" @click.self="inspectionModalData = null">
      <div class="fame-modal-card gothic-panel animate-scale-up inspection-card">
        <div class="modal-header">
          <div class="flex items-center gap-2">
            <PixelEmoji emoji="trophy" size="sm" />
            <h2 class="modal-title font-pixel text-gold">OFFICIAL VERIFICATION CERTIFICATE</h2>
          </div>
          <button class="modal-close-btn font-pixel" @click="inspectionModalData = null">✕</button>
        </div>

        <div class="inspection-body">
          <div class="certificate-seal text-center">
            <span class="seal-emoji">📜</span>
            <h3 class="cert-title font-pixel text-gold">{{ inspectionModalData.announcement_format }}</h3>
          </div>

          <div class="cert-details-box gothic-panel">
            <div v-if="inspectionModalData.username" class="cert-row">
              <span class="cert-lbl font-pixel">STUDYQUEST PLAYER:</span>
              <strong class="text-gold font-pixel">@{{ inspectionModalData.username }}</strong>
            </div>
            <div class="cert-row">
              <span class="cert-lbl font-pixel">PROFESSIONAL TITLE:</span>
              <strong class="text-gold font-pixel">Registered Psychometrician (RPm)</strong>
            </div>
            <div class="cert-row">
              <span class="cert-lbl font-pixel">LICENSURE EXAMINATION:</span>
              <span>{{ inspectionModalData.exam_name }}</span>
            </div>
            <div v-if="inspectionModalData.rank_num" class="cert-row">
              <span class="cert-lbl font-pixel">EXAMINATION RANK:</span>
              <strong class="text-gold font-pixel">👑 RANK {{ inspectionModalData.rank_num }} TOPNOTCHER</strong>
            </div>
            <div v-if="inspectionModalData.school" class="cert-row">
              <span class="cert-lbl font-pixel">ALMA MATER / SCHOOL:</span>
              <span class="font-pixel">{{ inspectionModalData.school }}</span>
            </div>
            <div class="cert-row">
              <span class="cert-lbl font-pixel">EXAMINATION BATCH:</span>
              <span class="font-pixel">{{ inspectionModalData.batch_name }} (2026 BLEPP)</span>
            </div>
            <div class="cert-row">
              <span class="cert-lbl font-pixel">OFFICIAL PRC SOURCE:</span>
              <a
                :href="inspectionModalData.official_source_url"
                target="_blank"
                rel="noopener noreferrer"
                class="link-external font-pixel"
              >
                PRC OFFICIAL PRESS RELEASE BULLETIN ↗
              </a>
            </div>
            <div class="cert-row">
              <span class="cert-lbl font-pixel">VERIFICATION STAMP:</span>
              <span class="verified-stamp font-pixel text-gold">✓ PRC DATABASE CROSS-REFERENCED & VERIFIED</span>
            </div>
          </div>

          <p class="privacy-guarantee text-xs text-muted text-center mt-3">
            Protected under StudyQuest Privacy & Verification Protocols. Name publication strictly adheres to the applicant's chosen visibility preference.
          </p>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         MODAL 3: ADMIN ROSTER IMPORT
         ══════════════════════════════════════════════════════════ -->
    <div v-if="showImportModal && isAdmin" class="fame-modal-backdrop" @click.self="showImportModal = false">
      <div class="fame-modal-card gothic-panel animate-scale-up">
        <div class="modal-header">
          <h2 class="modal-title font-pixel text-gold">IMPORT OFFICIAL PRC EXAMINEE ROSTER</h2>
          <button class="modal-close-btn font-pixel" @click="showImportModal = false">✕</button>
        </div>

        <form @submit.prevent="submitRosterImport" class="modal-form-body">
          <div class="form-group">
            <label class="form-label font-pixel">TARGET BATCH</label>
            <select v-model="importForm.batch_id" required class="form-select">
              <option v-for="b in batches" :key="b.id" :value="b.id">
                {{ b.batch_name }} ({{ b.exam_name }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label font-pixel">PASTE OFFICIAL PRC PASSERS LIST</label>
            <textarea
              v-model="importForm.raw_roster"
              rows="8"
              required
              class="form-textarea font-mono"
              placeholder="Paste official examinees list, e.g.:&#10;1  DELA CRUZ, JUAN PEDRO MENDOZA&#10;2  SANTOS, MARIA CLARA BAUTISTA"
            ></textarea>
            <p class="form-hint">
              Supports lines formatted as "Seq. No.  Surname, Firstname Middlename" or plain names.
            </p>
          </div>

          <div class="modal-actions-row">
            <button type="button" class="btn btn-gothic-ghost font-pixel" @click="showImportModal = false">
              CANCEL
            </button>
            <button type="submit" class="btn-submit-verify font-pixel" :disabled="importingRoster">
              <span>{{ importingRoster ? 'PARSING & IMPORTING...' : 'PARSE & IMPORT TO ROSTER' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import hallOfFameService from '../services/hallOfFameService'
import AvatarImg from '../components/AvatarImg.vue'

const router = useRouter()
const auth = useAuthStore()

// State
const currentTab = ref('scroll') // 'scroll' | 'my-claim' | 'admin'
const announcements = ref([])
const batches = ref([])
const userClaims = ref([])
const loadingAnnouncements = ref(false)

// Filter & search state
const rosterFilter = ref('all') // 'all' | 'full' | 'masked'
const searchQuery = ref('')
let pollTimer = null

// Claim modal state
const showClaimModal = ref(false)
const submittingClaim = ref(false)
const submissionFeedback = ref(null)
const claimForm = ref({
  first_name: '',
  middle_name: '',
  last_name: '',
  submitted_full_name: '',
  exam_type: 'BLEPP',
  batch_id: 1,
  display_preference: 'MASKED',
  consent_1: false,
  consent_2: false
})

// Inspection modal
const inspectionModalData = ref(null)

// Admin queue state
const adminQueue = ref([])
const adminQueueFilter = ref('ALL')
const adminLoading = ref(false)
const showImportModal = ref(false)
const importingRoster = ref(false)
const importForm = ref({
  batch_id: 1,
  raw_roster: ''
})

// Admin detection
const isAdmin = computed(() => {
  const user = auth.user
  if (!user) return false
  const un = (user.username || '').toLowerCase()
  const em = (user.email || '').toLowerCase()
  return un === 'admin' || em.includes('admin') || [1, 4].includes(Number(user.id))
})

const adminPendingCount = computed(() => {
  return adminQueue.value.filter(c => c.claim_status === 'PENDING_IDENTITY_REVIEW').length
})

// Filtered 2026 announcements in list format
const filteredAnnouncements = computed(() => {
  let list = announcements.value
  if (rosterFilter.value === 'full') {
    list = list.filter(a => a.display_preference === 'FULL')
  } else if (rosterFilter.value === 'masked') {
    list = list.filter(a => a.display_preference === 'MASKED')
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(a =>
      (a.display_name || '').toLowerCase().includes(q) ||
      (a.username || '').toLowerCase().includes(q) ||
      (a.nickname || '').toLowerCase().includes(q) ||
      (a.school || '').toLowerCase().includes(q) ||
      (a.batch_name || '').toLowerCase().includes(q)
    )
  }
  return list
})

const selectedBatchName = computed(() => {
  const b = batches.value.find(item => item.id === claimForm.value.batch_id)
  return b ? b.batch_name : 'SEPTEMBER 2026'
})

// PRC Standard Formatted Name: LAST NAME, FIRST NAME MIDDLE NAME
const prcFormattedName = computed(() => {
  const last = (claimForm.value.last_name || '').trim().toUpperCase()
  const first = (claimForm.value.first_name || '').trim().toUpperCase()
  const mid = (claimForm.value.middle_name || '').trim().toUpperCase()
  if (!last && !first) return ''
  return mid ? `${last}, ${first} ${mid}` : `${last}, ${first}`
})

// Dynamic Live Masking Preview based on PRC format
const dynamicScrollPreview = computed(() => {
  const target = prcFormattedName.value || 'DELA CRUZ, JUAN PEDRO MENDOZA'
  if (claimForm.value.display_preference === 'FULL') {
    return target
  }
  return hallOfFameService.maskNamePreview(target).toUpperCase()
})

// Navigation
function goBack() {
  router.push('/world')
}

function openInspectionModal(ann) {
  inspectionModalData.value = ann
}

const isEditingClaim = ref(false)

function openClaimModal() {
  isEditingClaim.value = false
  submissionFeedback.value = null
  claimForm.value.first_name = ''
  claimForm.value.middle_name = ''
  claimForm.value.last_name = ''
  claimForm.value.submitted_full_name = ''
  claimForm.value.display_preference = 'MASKED'
  claimForm.value.consent_1 = false
  claimForm.value.consent_2 = false
  showClaimModal.value = true
}

function openEditClaimModal(claim) {
  isEditingClaim.value = true
  submissionFeedback.value = null

  // Parse legal name format "SURNAME, FIRSTNAME MIDDLENAME"
  let lastName = ''
  let firstName = ''
  let middleName = ''

  if (claim.submitted_full_name && claim.submitted_full_name.includes(',')) {
    const parts = claim.submitted_full_name.split(',')
    lastName = parts[0].trim()
    const tokens = parts[1].trim().split(/\s+/)
    firstName = tokens[0] || ''
    middleName = tokens.slice(1).join(' ') || ''
  } else {
    lastName = claim.submitted_full_name || ''
  }

  claimForm.value.batch_id = claim.batch_id || (batches.value[0]?.id ?? 1)
  claimForm.value.first_name = firstName
  claimForm.value.middle_name = middleName
  claimForm.value.last_name = lastName
  claimForm.value.submitted_full_name = claim.submitted_full_name || ''
  claimForm.value.display_preference = claim.display_preference || 'MASKED'
  claimForm.value.consent_1 = true
  claimForm.value.consent_2 = true

  showClaimModal.value = true
}

function openClaimModalForBatch(batchId) {
  claimForm.value.batch_id = batchId
  openClaimModal()
}

function closeClaimModal() {
  showClaimModal.value = false
  submissionFeedback.value = null
  claimForm.value.first_name = ''
  claimForm.value.middle_name = ''
  claimForm.value.last_name = ''
  claimForm.value.submitted_full_name = ''
  claimForm.value.consent_1 = false
  claimForm.value.consent_2 = false
}

// Data fetchers
async function fetchAnnouncements() {
  try {
    const res = await hallOfFameService.getAnnouncements()
    if (res.data?.success) {
      announcements.value = res.data.announcements || []
    }
  } catch (err) {
    console.error('[HallOfFame] Failed to load announcements:', err)
  }
}

async function fetchBatches() {
  try {
    const res = await hallOfFameService.getBatches()
    if (res.data?.success) {
      batches.value = res.data.batches || []
      if (batches.value.length && !claimForm.value.batch_id) {
        claimForm.value.batch_id = batches.value[0].id
      }
    }
  } catch (err) {
    console.error('[HallOfFame] Failed to load batches:', err)
  }
}

async function fetchUserClaims() {
  if (!auth.isLoggedIn) return
  try {
    const res = await hallOfFameService.getMyClaims()
    if (res.data?.success) {
      userClaims.value = res.data.claims || []
    }
  } catch (err) {
    console.error('[HallOfFame] Failed to load user claims:', err)
  }
}

async function fetchAdminQueue() {
  if (!isAdmin.value) return
  adminLoading.value = true
  try {
    const res = await hallOfFameService.getAdminQueue(adminQueueFilter.value)
    if (res.data?.success) {
      adminQueue.value = res.data.queue || []
    }
  } catch (err) {
    console.error('[HallOfFame] Failed to load admin queue:', err)
  } finally {
    adminLoading.value = false
  }
}

function setAdminFilter(status) {
  adminQueueFilter.value = status
  fetchAdminQueue()
}

// Actions
async function submitClaimForm() {
  if (!claimForm.value.first_name.trim() || !claimForm.value.last_name.trim()) {
    submissionFeedback.value = {
      type: 'error',
      message: 'Please provide both First Name and Last Name.'
    }
    return
  }

  submittingClaim.value = true
  submissionFeedback.value = null

  try {
    const prcName = prcFormattedName.value
    const payload = {
      batch_id: claimForm.value.batch_id,
      first_name: claimForm.value.first_name.trim(),
      middle_name: claimForm.value.middle_name.trim(),
      last_name: claimForm.value.last_name.trim(),
      submitted_full_name: prcName,
      display_preference: claimForm.value.display_preference,
      consent_confirmed: true
    }

    const res = await hallOfFameService.submitClaim(payload)
    if (res.data?.success) {
      submissionFeedback.value = {
        type: 'success',
        message: res.data.message || 'Claim submitted successfully!'
      }
      await fetchUserClaims()
      setTimeout(() => {
        closeClaimModal()
        currentTab.value = 'my-claim'
      }, 1800)
    } else {
      submissionFeedback.value = {
        type: 'error',
        message: res.data?.message || 'Verification could not be processed.'
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Error communicating with verification service.'
    submissionFeedback.value = { type: 'error', message: msg }
  } finally {
    submittingClaim.value = false
  }
}

async function adminDecide(claimId, decision) {
  try {
    const res = await hallOfFameService.adminDecide({
      claim_id: claimId,
      decision,
      notes: `Actioned as ${decision} via Hall of Fame administration dashboard.`
    })
    if (res.data?.success) {
      await fetchAdminQueue()
      await fetchAnnouncements()
      await fetchUserClaims()
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update claim.')
  }
}

async function submitRosterImport() {
  importingRoster.value = true
  try {
    const res = await hallOfFameService.adminImport({
      batch_id: importForm.value.batch_id,
      raw_roster: importForm.value.raw_roster
    })
    if (res.data?.success) {
      alert(res.data.message)
      showImportModal.value = false
      importForm.value.raw_roster = ''
      await fetchBatches()
      await fetchAdminQueue()
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to import roster.')
  } finally {
    importingRoster.value = false
  }
}

// Stepper helpers
function isStepDone(status, stepNum) {
  if (stepNum === 1) return true
  if (stepNum === 2) return status === 'APPROVED' || status === 'RESULT_MATCHED'
  return false
}

function isStepActive(status, stepNum) {
  if (stepNum === 2 && (status === 'SUBMITTED' || status === 'PENDING_IDENTITY_REVIEW')) return true
  return false
}

function formatStatus(st) {
  return (st || '').replace(/_/g, ' ')
}

function formatMatchStatus(status) {
  if (!status) return 'Unmatched'
  const s = String(status).toUpperCase()
  if (s === 'RESULT_MATCHED' || s === 'MATCHED' || s === 'PASSED' || s === 'APPROVED') {
    return 'Passed'
  }
  return 'Unmatched'
}

function getMatchStatusClass(status) {
  return formatMatchStatus(status) === 'Passed' ? 'passed' : 'unmatched'
}

function isMatchPassed(status) {
  return formatMatchStatus(status) === 'Passed'
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

onMounted(async () => {
  loadingAnnouncements.value = true
  await Promise.all([
    fetchAnnouncements(),
    fetchBatches(),
    fetchUserClaims()
  ])
  loadingAnnouncements.value = false

  if (isAdmin.value) {
    fetchAdminQueue()
  }

  // Periodic poll every 20 seconds for real-time 2026 announcement updates
  pollTimer = setInterval(() => {
    fetchAnnouncements()
  }, 20000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<style scoped>
/* ══════════════════════════════════════════════════════════════
   HALL OF FAME CEREMONIAL PAGE STYLES
   ══════════════════════════════════════════════════════════════ */
.hall-of-fame-page {
  position: relative;
  min-height: 100vh;
  width: 100%;
  background: #090a0f;
  color: var(--text-primary, #e2e8f0);
  overflow-x: hidden;
  padding: 1.5rem 1rem 4rem;
}

/* Atmospheric Background Environment with new Gamified RPG Artwork */
.fame-bg-environment {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
  background-image: url('/assets/game/hall_of_fame_bg.jpg');
  background-size: cover;
  background-position: center top;
  background-repeat: no-repeat;
}

.fame-bg-radial {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 18%, rgba(18, 14, 28, 0.72) 0%, rgba(9, 10, 16, 0.88) 60%, rgba(5, 5, 8, 0.96) 100%);
}

.fame-bg-vignette {
  position: absolute;
  inset: 0;
  box-shadow: inset 0 0 150px rgba(0, 0, 0, 0.85);
}

.fame-particle {
  position: absolute;
  border-radius: 50%;
  background: #ffd700;
  filter: blur(1px);
  opacity: 0.4;
  animation: fameFloat 8s infinite ease-in-out;
}
.fame-particle.p1 { width: 4px; height: 4px; top: 20%; left: 15%; animation-delay: 0s; }
.fame-particle.p2 { width: 6px; height: 6px; top: 35%; right: 20%; animation-delay: 2s; }
.fame-particle.p3 { width: 3px; height: 3px; top: 60%; left: 25%; animation-delay: 4s; }
.fame-particle.p4 { width: 5px; height: 5px; top: 75%; right: 15%; animation-delay: 1s; }
.fame-particle.p5 { width: 4px; height: 4px; top: 15%; right: 35%; animation-delay: 3s; }

@keyframes fameFloat {
  0%, 100% { transform: translateY(0) scale(1); opacity: 0.2; }
  50% { transform: translateY(-25px) scale(1.4); opacity: 0.65; }
}

/* Container */
.fame-container {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.fame-header {
  margin-bottom: 2rem;
}

.header-nav-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.prc-badge {
  background: rgba(217, 119, 6, 0.2);
  border: 1px solid rgba(217, 119, 6, 0.5);
  color: #fbbf24;
  font-size: 8px;
  padding: 0.35rem 0.65rem;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.profession-badge {
  background: rgba(30, 41, 59, 0.8);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #e2e8f0;
  font-size: 8px;
  padding: 0.35rem 0.65rem;
  letter-spacing: 0.05em;
}

/* Monumental Title */
.ceremonial-banner {
  margin-bottom: 1.5rem;
}

.monumental-crown {
  position: relative;
  display: inline-block;
  font-size: 2.2rem;
  margin-bottom: 0.4rem;
}

.crown-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, rgba(251, 191, 36, 0.4) 0%, transparent 70%);
  z-index: -1;
  animation: crownPulse 3s infinite;
}

@keyframes crownPulse {
  0%, 100% { transform: scale(1); opacity: 0.6; }
  50% { transform: scale(1.3); opacity: 1; }
}

.fame-title {
  font-size: 2.2rem;
  letter-spacing: 0.12em;
  color: #ffd700;
  text-shadow: 0 0 24px rgba(251, 191, 36, 0.4), 2px 2px 0 #3d2300;
  margin: 0 0 0.5rem;
}

.fame-subtitle {
  font-size: 0.95rem;
  color: #cbd5e1;
  max-width: 680px;
  margin: 0 auto 1rem;
  font-family: var(--font-gothic, serif);
  font-style: italic;
}

.fame-metrics-summary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  margin: 0.5rem auto 1.25rem;
  font-size: 9px;
  color: #fbbf24;
  letter-spacing: 0.05em;
  flex-wrap: wrap;
}

.fame-metrics-summary .summary-pill {
  background: rgba(217, 119, 6, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.3);
  padding: 0.25rem 0.65rem;
  border-radius: 3px;
}

.fame-metrics-summary .summary-bullet {
  color: #ffd700;
  font-size: 8px;
  opacity: 0.6;
}

.fame-gold-separator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  position: relative;
  width: 220px;
  height: 1px;
  background: linear-gradient(90deg, transparent, #fbbf24, transparent);
}

.fame-gold-separator .diamond {
  position: absolute;
  color: #ffd700;
  font-size: 10px;
  background: #090a0f;
  padding: 0 6px;
}

/* CTA Banner */
.fame-cta-banner {
  background: linear-gradient(135deg, rgba(45, 26, 11, 0.85) 0%, rgba(20, 15, 26, 0.92) 100%);
  border: 1px solid rgba(251, 191, 36, 0.4);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
  padding: 1.25rem 1.75rem;
  margin-bottom: 1.8rem;
}

.cta-banner-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.cta-heading {
  font-size: 13px;
  color: #ffd700;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.cta-desc {
  font-size: 0.85rem;
  color: #94a3b8;
  margin: 0;
}

.btn-claim-fame {
  position: relative;
  background: linear-gradient(135deg, #d97706 0%, #b45309 50%, #78350f 100%);
  border: 1px solid #ffd700;
  color: #ffffff;
  padding: 0.75rem 1.6rem;
  font-size: 11px;
  letter-spacing: 0.08em;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(217, 119, 6, 0.35);
  display: flex;
  align-items: center;
  gap: 0.6rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.btn-claim-fame:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(251, 191, 36, 0.5);
  border-color: #fff;
}

.btn-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
  transform: skewX(-25deg);
  animation: shineSweep 4s infinite;
}

@keyframes shineSweep {
  0% { left: -100%; }
  30%, 100% { left: 200%; }
}

/* Tabs Bar */
.fame-tabs-bar {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding-bottom: 0.5rem;
  flex-wrap: wrap;
}

.fame-tab-btn {
  background: transparent;
  border: 1px solid transparent;
  color: #94a3b8;
  font-size: 10px;
  padding: 0.6rem 1.1rem;
  cursor: pointer;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.18s ease;
}

.fame-tab-btn:hover {
  color: #f1f5f9;
  border-color: rgba(255, 255, 255, 0.15);
}

.fame-tab-btn.active {
  background: rgba(217, 119, 6, 0.15);
  border-color: #fbbf24;
  color: #ffd700;
  box-shadow: 0 2px 8px rgba(217, 119, 6, 0.2);
}

.admin-tab {
  margin-left: auto;
  border-color: rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.tab-badge {
  background: #dc2626;
  color: white;
  border-radius: 999px;
  font-size: 8px;
  padding: 0.1rem 0.4rem;
}

.tab-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fbbf24;
}

/* ══════════════════════════════════════════════════════════════
   TAB 1: 2026 ROSTER LIST FORMAT & TOOLBAR
   ══════════════════════════════════════════════════════════════ */
.roster-toolbar-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.4rem;
  margin-bottom: 1.5rem;
  background: rgba(16, 17, 28, 0.88);
  border: 1px solid rgba(245, 158, 11, 0.35);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  border-radius: 8px;
  backdrop-filter: blur(10px);
  flex-wrap: wrap;
  gap: 1rem;
}

.roster-filter-pills {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.roster-pill-btn {
  background: rgba(28, 32, 50, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #94a3b8;
  font-size: 9px;
  padding: 0.45rem 0.85rem;
  cursor: pointer;
  letter-spacing: 0.05em;
  border-radius: 4px;
  transition: all 0.18s ease;
}

.roster-pill-btn:hover {
  color: #f8fafc;
  border-color: #fbbf24;
  background: rgba(45, 52, 80, 0.8);
}

.roster-pill-btn.active {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border-color: #ffd700;
  color: #ffffff;
  box-shadow: 0 2px 10px rgba(217, 119, 6, 0.35);
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.7rem;
  font-size: 0.85rem;
  pointer-events: none;
  opacity: 0.6;
}

.search-input {
  background: rgba(8, 10, 16, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #f8fafc;
  font-size: 0.85rem;
  padding: 0.45rem 1.8rem 0.45rem 2.2rem;
  outline: none;
  width: 250px;
  border-radius: 4px;
  transition: border-color 0.15s ease;
}

.search-input:focus {
  border-color: #fbbf24;
  box-shadow: 0 0 10px rgba(251, 191, 36, 0.2);
}

.search-clear-btn {
  position: absolute;
  right: 0.6rem;
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 0.8rem;
}

.search-clear-btn:hover {
  color: #f87171;
}

.roster-count-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 9px;
  color: #fbbf24;
  background: rgba(217, 119, 6, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.3);
  padding: 0.4rem 0.8rem;
  border-radius: 4px;
  letter-spacing: 0.05em;
}

.pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 8px #10b981;
}

/* Grand Roster Stage Container */
.roster-stage-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Vertical List of Passers */
.roster-vertical-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.roster-item-slate {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1.25rem 1.75rem;
  background: linear-gradient(135deg, rgba(20, 24, 38, 0.94) 0%, rgba(13, 14, 24, 0.96) 100%);
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 10px;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.06);
  cursor: pointer;
  transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

.roster-item-slate:hover {
  transform: translateY(-3px);
  border-color: #ffd700;
  box-shadow: 0 10px 32px rgba(245, 158, 11, 0.28), inset 0 0 20px rgba(251, 191, 36, 0.06);
}

.roster-item-slate.is-topnotcher {
  background: linear-gradient(135deg, rgba(38, 28, 14, 0.95) 0%, rgba(18, 16, 26, 0.98) 100%);
  border: 1.5px solid rgba(251, 191, 36, 0.7);
  box-shadow: 0 8px 30px rgba(217, 119, 6, 0.32), inset 0 0 24px rgba(251, 191, 36, 0.1);
}

.slate-glow-border {
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(180deg, #ffd700 0%, #d97706 100%);
  opacity: 0.7;
}

.roster-item-slate.is-topnotcher .slate-glow-border {
  width: 6px;
  background: linear-gradient(180deg, #ffffff 0%, #ffd700 50%, #b45309 100%);
  box-shadow: 0 0 12px #ffd700;
  opacity: 1;
}

/* Left Column: Player Avatar & Account Identity */
.slate-left-col {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-width: 240px;
}

.player-account-cluster {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.player-avatar-frame {
  position: relative;
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.online-gem {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #090a0f;
  box-shadow: 0 0 6px #10b981;
  z-index: 2;
}

.player-text-wrap {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.player-username {
  font-size: 14px;
  color: #ffd700;
  letter-spacing: 0.06em;
  text-shadow: 0 0 8px rgba(251, 191, 36, 0.3);
}

.player-hero-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 7px;
  color: #fbbf24;
  background: rgba(217, 119, 6, 0.2);
  border: 1px solid rgba(251, 191, 36, 0.3);
  padding: 0.1rem 0.4rem;
  border-radius: 2px;
  letter-spacing: 0.05em;
  width: fit-content;
}

/* Middle Column: Honor Inscription */
.slate-mid-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.proclamation-header {
  font-size: 8px;
  color: #fbbf24;
  letter-spacing: 0.08em;
}

.proclamation-prefix {
  opacity: 0.9;
}

.header-divider {
  opacity: 0.4;
}

.board-exam-tag {
  color: #93c5fd;
  background: rgba(59, 130, 246, 0.15);
  padding: 0.1rem 0.35rem;
  border-radius: 2px;
}

.passer-honor-name {
  font-size: 1.25rem;
  color: #ffffff;
  letter-spacing: 0.05em;
  margin: 0.2rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
}

.passer-meta-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.78rem;
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
}

.school-pill {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.25);
  color: #e2e8f0;
}

.batch-pill {
  font-size: 8px;
  background: rgba(217, 119, 6, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.3);
  color: #fbbf24;
}

/* Right Column: PRC Seal & Proof CTA */
.slate-right-col {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.75rem;
  min-width: 180px;
  flex-shrink: 0;
}

.prc-seal-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
}

.seal-check-circle {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #10b981;
  color: #06070a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 11px;
}

.seal-text-col {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.seal-header {
  font-size: 8px;
  color: #6ee7b7;
  letter-spacing: 0.05em;
}

.seal-sub {
  font-size: 7px;
  color: #94a3b8;
}

.btn-inspect-action {
  background: rgba(217, 119, 6, 0.2);
  border: 1px solid rgba(251, 191, 36, 0.5);
  color: #ffd700;
  font-size: 9px;
  padding: 0.45rem 0.95rem;
  cursor: pointer;
  letter-spacing: 0.06em;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.18s ease;
}

.btn-inspect-action:hover {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border-color: #ffd700;
  color: #ffffff;
  box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
}

.roster-bottom-bar {
  background: rgba(13, 14, 22, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.2);
  padding: 0.75rem 1.25rem;
  border-radius: 6px;
}

/* Responsive Stacking */
@media (max-width: 900px) {
  .roster-item-slate {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .slate-left-col,
  .slate-right-col {
    width: 100%;
    min-width: 0;
  }
  .slate-right-col {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }
  .search-input {
    width: 100%;
  }
}

/* Tab 2: My Claims */
.my-claims-empty {
  padding: 3rem 1.5rem;
}

.claim-status-card {
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  background: rgba(18, 15, 26, 0.9);
}

.claim-date {
  font-size: 8px;
  color: #64748b;
  letter-spacing: 0.05em;
}

.claim-batch-title {
  font-size: 1.15rem;
  margin: 0.2rem 0;
}

.claim-status-badge {
  font-size: 8px;
  padding: 0.35rem 0.75rem;
  border-radius: 2px;
}

.claim-status-badge.approved { background: rgba(16, 185, 129, 0.25); border: 1px solid #10b981; color: #6ee7b7; }
.claim-status-badge.pending_identity_review { background: rgba(245, 158, 11, 0.25); border: 1px solid #f59e0b; color: #fcd34d; }
.claim-status-badge.rejected { background: rgba(239, 68, 68, 0.25); border: 1px solid #ef4444; color: #fca5a5; }

.btn-edit-claim {
  font-size: 8px;
  padding: 0.35rem 0.65rem;
  background: rgba(217, 119, 6, 0.15);
  border: 1px solid rgba(251, 191, 36, 0.45);
  color: #fbbf24;
  border-radius: 2px;
  cursor: pointer;
  letter-spacing: 0.05em;
  transition: all 0.15s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-edit-claim:hover {
  background: rgba(217, 119, 6, 0.3);
  border-color: #fde047;
  color: #fef08a;
  transform: translateY(-1px);
  box-shadow: 0 0 8px rgba(251, 191, 36, 0.35);
}

.btn-edit-inline {
  background: transparent;
  border: none;
  color: #fbbf24;
  font-size: 7px;
  cursor: pointer;
  padding: 0 2px;
  text-decoration: underline;
  transition: opacity 0.15s;
  letter-spacing: 0.05em;
}

.btn-edit-inline:hover {
  color: #fef08a;
  opacity: 0.9;
}

/* Stepper */
.verification-stepper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 1.5rem 0;
  padding: 1rem;
  background: #090a0f;
  border: 1px solid rgba(255, 255, 255, 0.06);
}

.step-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.step-bubble {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1e293b;
  border: 1px solid #475569;
  color: #94a3b8;
  font-size: 10px;
}

.step-item.completed .step-bubble {
  background: #059669;
  border-color: #10b981;
  color: white;
}

.step-item.active .step-bubble {
  background: #d97706;
  border-color: #fbbf24;
  color: white;
  animation: radarPulse 1.8s infinite;
}

.step-meta {
  display: flex;
  flex-direction: column;
}

.step-title {
  font-size: 8px;
  color: #e2e8f0;
}

.step-desc {
  font-size: 0.72rem;
  color: #64748b;
}

.step-line {
  flex: 1;
  height: 2px;
  background: #1e293b;
  margin: 0 0.85rem;
}

.step-line.completed {
  background: #10b981;
}

.claim-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: rgba(10, 8, 16, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.05);
  margin-bottom: 1.25rem;
}

.detail-label {
  display: block;
  font-size: 7px;
  color: #64748b;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 0.9rem;
}

.masked-note {
  font-size: 0.75rem;
  color: #94a3b8;
}

.match-badge {
  font-size: 8px;
  padding: 0.25rem 0.55rem;
}

.match-badge.passed,
.match-badge.result_matched { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid #10b981; }
.match-badge.unmatched,
.match-badge.no_match { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid #ef4444; }
.match-badge.ambiguous_match { background: rgba(245, 158, 11, 0.2); color: #fcd34d; border: 1px solid #f59e0b; }

.approved-congrats-banner {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(6, 78, 59, 0.2) 100%);
  border: 1px solid #10b981;
  padding: 1rem 1.25rem;
}

.scroll-snippet {
  background: #090a0f;
  border: 1px dashed #ffd700;
  color: #ffd700;
  padding: 0.5rem 0.85rem;
  margin-top: 0.5rem;
  font-size: 10px;
}

.alert-review-note {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.4);
  padding: 0.85rem 1rem;
}

/* Tab 4: Admin Table */
.admin-claims-table table {
  border-collapse: collapse;
}

.table-head-row {
  background: rgba(30, 41, 59, 0.5);
  border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.table-head-row th {
  padding: 0.75rem 0.85rem;
  font-size: 8px;
  color: #94a3b8;
  letter-spacing: 0.05em;
}

.table-body-row {
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  transition: background 0.15s;
}

.table-body-row:hover {
  background: rgba(255, 255, 255, 0.03);
}

.table-body-row td {
  padding: 0.75rem 0.85rem;
  font-size: 0.82rem;
}

.applicant-cell, .submitted-cell {
  display: flex;
  flex-direction: column;
}

.action-buttons-cluster {
  display: flex;
  gap: 0.35rem;
}

.btn-action {
  font-size: 7px;
  padding: 0.3rem 0.55rem;
  cursor: pointer;
  border: none;
  font-weight: bold;
}

.btn-action.approve { background: #059669; color: white; }
.btn-action.approve:hover { background: #10b981; }
.btn-action.reject { background: #dc2626; color: white; }
.btn-action.reject:hover { background: #ef4444; }

.admin-filter-pill {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.2);
  color: #94a3b8;
  font-size: 8px;
  padding: 0.3rem 0.6rem;
  cursor: pointer;
}

.admin-filter-pill.active {
  background: #3b82f6;
  color: white;
  border-color: #60a5fa;
}

/* Modals */
.fame-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(4px);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.fame-modal-card {
  position: relative;
  background: #100e18;
  border: 2px solid rgba(217, 119, 6, 0.5);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
  max-width: 620px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  padding: 1.75rem;
}

.inspection-card {
  max-width: 540px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid rgba(251, 191, 36, 0.25);
  padding-bottom: 0.75rem;
}

.modal-title {
  font-size: 13px;
  letter-spacing: 0.06em;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1rem;
  cursor: pointer;
}

.modal-close-btn:hover { color: #f8fafc; }

.form-group {
  margin-bottom: 1.15rem;
}

.form-label {
  display: block;
  font-size: 8px;
  color: #cbd5e1;
  letter-spacing: 0.05em;
  margin-bottom: 0.35rem;
}

.form-input, .form-select, .form-textarea {
  width: 100%;
  background: #06070a;
  border: 1px solid rgba(148, 163, 184, 0.3);
  color: #f8fafc;
  padding: 0.6rem 0.85rem;
  font-size: 0.9rem;
  outline: none;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  border-color: #fbbf24;
}

.form-hint {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* PRC Separate Name Grid */
.name-inputs-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

@media (max-width: 640px) {
  .name-inputs-grid {
    grid-template-columns: 1fr;
  }
}

.name-subfield {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.subfield-label {
  font-size: 7.5px;
  color: #94a3b8;
  letter-spacing: 0.05em;
}

.optional-tag {
  color: #64748b;
  font-size: 6.5px;
}

.prc-format-preview-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.6rem;
  padding: 0.5rem 0.85rem;
  background: rgba(15, 23, 42, 0.75);
  border: 1px dashed rgba(245, 158, 11, 0.4);
}

.prc-format-preview-strip .preview-lbl {
  font-size: 7.5px;
  flex-shrink: 0;
}

.prc-format-preview-strip .preview-val {
  font-size: 8px;
  letter-spacing: 0.05em;
  text-align: right;
  word-break: break-word;
}

/* Display Preference Radio Cards */
.display-pref-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.pref-card-option {
  background: #090a0f;
  border: 1px solid rgba(148, 163, 184, 0.25);
  padding: 0.85rem;
  cursor: pointer;
  transition: all 0.15s;
}

.pref-card-option:hover {
  border-color: rgba(251, 191, 36, 0.5);
}

.pref-card-option.active {
  border-color: #fbbf24;
  background: rgba(217, 119, 6, 0.15);
}

.pref-card-header {
  font-size: 8px;
  color: #ffd700;
  margin-bottom: 0.3rem;
}

.pref-card-desc {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0;
}

.masking-live-preview {
  background: #050608;
  border: 1px dashed rgba(251, 191, 36, 0.35);
  padding: 0.75rem;
}

.preview-tag {
  font-size: 7px;
  letter-spacing: 0.06em;
  display: block;
  margin-bottom: 0.2rem;
}

.preview-scroll-text {
  font-size: 10px;
  color: #ffd700;
}

.consent-checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  font-size: 0.8rem;
  line-height: 1.4;
  color: #94a3b8;
  cursor: pointer;
  user-select: none;
  transition: color 0.15s ease;
}

.consent-checkbox-label:hover {
  color: #e2e8f0;
}

.consent-checkbox-label input {
  margin-top: 0.15rem;
  width: 17px;
  height: 17px;
  accent-color: #ffd700;
  cursor: pointer;
  flex-shrink: 0;
}

.feedback-alert {
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.feedback-alert.success { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; }
.feedback-alert.error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }

.modal-actions-row {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 1rem;
}

.btn-submit-verify {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border: 1px solid #ffd700;
  color: white;
  padding: 0.65rem 1.4rem;
  font-size: 10px;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-submit-verify:hover:not(:disabled) {
  background: #f59e0b;
}

.btn-submit-verify:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Inspection Certificate */
.certificate-seal {
  margin-bottom: 1.25rem;
}

.seal-emoji {
  font-size: 2.2rem;
}

.cert-title {
  font-size: 14px;
  margin-top: 0.4rem;
  letter-spacing: 0.05em;
}

.cert-details-box {
  background: #090a0f;
  padding: 1rem 1.25rem;
}

.cert-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 0.82rem;
}

.cert-row:last-child {
  border-bottom: none;
}

.cert-lbl {
  font-size: 7px;
  color: #64748b;
  letter-spacing: 0.05em;
}

.verified-stamp {
  font-size: 8px;
  letter-spacing: 0.05em;
}

.link-external {
  color: #fbbf24;
  text-decoration: underline;
  font-size: 8px;
}

/* Loading & Empty */
.fame-loading-box, .fame-empty-box {
  text-align: center;
  padding: 3rem 1.5rem;
}

.loading-rune-spinner {
  font-size: 2rem;
  color: #ffd700;
  animation: spinRune 2s linear infinite;
  display: inline-block;
  margin-bottom: 0.5rem;
}

@keyframes spinRune {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ══════════════════════════════════════════════════════════════
   MORNING / LIGHT THEME SUPPORT
   ══════════════════════════════════════════════════════════════ */
.theme-morning .hall-of-fame-page,
[data-theme="morning"] .hall-of-fame-page {
  background: #FDFBF7;
  color: #2D2319;
}

.theme-morning .fame-bg-radial,
[data-theme="morning"] .fame-bg-radial {
  background: radial-gradient(circle at 50% 15%, rgba(217, 119, 6, 0.08) 0%, rgba(255, 248, 235, 0.9) 60%, #FAF6EE 100%);
}

.theme-morning .fame-title,
[data-theme="morning"] .fame-title {
  color: #8F642D;
  text-shadow: none;
}

.theme-morning .fame-subtitle,
[data-theme="morning"] .fame-subtitle {
  color: #5C4A38;
}

.theme-morning .ceremonial-scroll-stage,
[data-theme="morning"] .ceremonial-scroll-stage {
  background: #FFFDF7;
  border-color: #D4AF37;
  box-shadow: 0 8px 30px rgba(184, 134, 11, 0.15);
}

.theme-morning .announcement-card,
[data-theme="morning"] .announcement-card {
  background: #FFF8EB;
  border-color: #E2D3B8;
  color: #2D2319;
}

.theme-morning .ann-name,
[data-theme="morning"] .ann-name {
  color: #3D2B1F;
}

.theme-morning .fame-cta-banner,
[data-theme="morning"] .fame-cta-banner {
  background: #FFF4DF;
  border-color: #D4AF37;
}

.theme-morning .fame-modal-card,
[data-theme="morning"] .fame-modal-card {
  background: #FFFDF7;
  color: #2D2319;
}

.theme-morning .form-input,
.theme-morning .form-select,
.theme-morning .form-textarea,
[data-theme="morning"] .form-input,
[data-theme="morning"] .form-select,
[data-theme="morning"] .form-textarea {
  background: #FFFFFF;
  border-color: #D4AF37;
  color: #2D2319;
}

/* Responsive */
@media (max-width: 768px) {
  .fame-title { font-size: 1.6rem; }
  .display-pref-cards { grid-template-columns: 1fr; }
  .cta-banner-content { flex-direction: column; align-items: flex-start; }
  .announcement-cards-grid { grid-template-columns: 1fr; }
  .verification-stepper { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .step-line { display: none; }
}
</style>
