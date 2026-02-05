<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, watch, computed } from "vue";

const props = defineProps({
    vacancy: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
});

const page = usePage();
const flashMsg = computed(() => page.props?.flash?.status || "");

// local rows for optimistic UI
const localRows = ref(
    props.rows.map((r) => ({ ...r, _busy: false, invited: !!r.invited })),
);
watch(
    () => props.rows,
    (v) => {
        localRows.value = v.map((r) => ({
            ...r,
            _busy: false,
            invited: !!r.invited,
        }));
    },
);

// Modals
const showInviteModal = ref(false);
const showRejectModal = ref(false);
const showCancelModal = ref(false);
const showHireModal = ref(false);
const activeRow = ref(null);

// Toast
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

// Invite form - UPDATED
const COMPANY_LOCATION = "BASF PETRONAS Chemicals, Gebeng, Kuantan, Pahang";

const inviteForm = ref({
    scheduled_at: "",
    mode: "online",
    location: "",
    meeting_link: "",
    extra_info: "",
});

const isOnline = computed(() => inviteForm.value.mode === "online");
const isOnsite = computed(() => inviteForm.value.mode === "onsite");

watch(
    () => inviteForm.value.mode,
    (mode) => {
        if (mode === "online") {
            inviteForm.value.location = "";
        } else if (mode === "onsite") {
            inviteForm.value.meeting_link = "";
            inviteForm.value.location = COMPANY_LOCATION;
        }
    },
);

const evalLink = (row) => `/hr/vacancies/${row.vacancy_id}/evaluation`;
const canHire = (row) => !!row.invited;

/* ---------- Modal Actions ---------- */
function openInvite(row) {
    if (row._busy || row.invited) return;
    if (!row?.id || !row?.vacancy_id) {
        showToast("Missing application/vacancy id", "error");
        return;
    }

    activeRow.value = row;
    inviteForm.value = {
        scheduled_at: "",
        mode: "online",
        location: "",
        meeting_link: "",
        extra_info: "",
    };
    showInviteModal.value = true;
}

function closeInvite() {
    showInviteModal.value = false;
    activeRow.value = null;
}

function submitInvite() {
    const row = activeRow.value;
    if (!row) return;

    const f = inviteForm.value;

    if (!f.scheduled_at) {
        showToast("Please select interview date and time", "error");
        return;
    }
    if (!f.mode) {
        showToast("Please choose interview mode", "error");
        return;
    }
    if (f.mode === "online" && !f.meeting_link?.trim()) {
        showToast("Please enter meeting link", "error");
        return;
    }
    if (f.mode === "onsite" && !f.location?.trim()) {
        showToast("Interview location is required", "error");
        return;
    }

    let location = f.location?.trim() || "";
    let meetingLink = f.meeting_link?.trim() || "";

    if (f.mode === "online") {
        location = "";
    } else if (f.mode === "onsite") {
        meetingLink = "";
    }

    row._busy = true;

    router.post(
        "/hr/interviews/upsert",
        {
            vacancy_id: row.vacancy_id,
            application_id: row.id,
            scheduled_at: f.scheduled_at,
            mode: f.mode,
            location,
            meeting_link: meetingLink,
            extra_info: f.extra_info,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                row.invited = true;
                closeInvite();
                showToast("Interview invitation sent successfully!", "success");
                router.reload({ only: ["rows"] });
            },
            onError: () => {
                showToast("Failed to save interview", "error");
            },
            onFinish: () => {
                row._busy = false;
            },
        },
    );
}

function openRejectModal(row) {
    activeRow.value = row;
    showRejectModal.value = true;
}

function confirmReject() {
    showRejectModal.value = false;
    const row = activeRow.value;

    router.post(
        `/hr/applications/${row.id}/decision`,
        { decision: "rejected" },
        {
            preserveScroll: true,
            onSuccess: () => showToast("Candidate rejected", "info"),
            onError: () => showToast("Failed to reject candidate", "error"),
        },
    );
}

function openCancelModal(row) {
    if (row._busy || !row.invited) return;
    activeRow.value = row;
    showCancelModal.value = true;
}

function confirmCancel() {
    showCancelModal.value = false;
    const row = activeRow.value;
    row._busy = true;

    router.post(
        "/hr/interviews/upsert",
        {
            vacancy_id: row.vacancy_id,
            application_id: row.id,
            cancel: true,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                row.invited = false;
                showToast("Interview cancelled", "info");
                router.reload({ only: ["rows"] });
            },
            onError: () => {
                showToast("Failed to cancel interview", "error");
            },
            onFinish: () => {
                row._busy = false;
            },
        },
    );
}

function openHireModal(row) {
    if (!canHire(row)) {
        showToast("Candidate must have an interview first", "error");
        return;
    }
    activeRow.value = row;
    showHireModal.value = true;
}

function confirmHire() {
    showHireModal.value = false;
    const row = activeRow.value;

    router.post(
        `/hr/applications/${row.id}/decision`,
        { decision: "hired" },
        {
            preserveScroll: true,
            onSuccess: () =>
                showToast("Candidate hired successfully! 🎉", "success"),
            onError: () => showToast("Failed to hire candidate", "error"),
        },
    );
}

const icons = {
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    medal: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6l1.5 3 3.5.5-2.5 2.5.5 3.5-3-1.5-3 1.5.5-3.5L7 9l3.5-.5L12 6z" fill="white"/></svg>',
    arrowLeft:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    userCheck:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
    userX: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M18 8l5 5M23 8l-5 5"/></svg>',
    calendar:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    mapPin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    video: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m23 7-7 5 7 5V7zM14 5H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2z"/></svg>',
    fileText:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    clipboard:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
};
</script>

<template>
    <Head :title="`Shortlist — ${vacancy.title}`" />
    <StaffLayout>
        <div class="page-container">
            <!-- Toast Notification -->
            <transition name="toast">
                <div
                    v-if="toast.show"
                    class="toast"
                    :class="`toast-${toast.type}`"
                >
                    <span
                        class="toast-icon"
                        v-html="
                            toast.type === 'success'
                                ? icons.check
                                : toast.type === 'error'
                                  ? icons.alert
                                  : icons.info
                        "
                    ></span>
                    <span class="toast-message">{{ toast.message }}</span>
                </div>
            </transition>

            <!-- Header -->
            <section class="header-card">
                <div class="header-glow"></div>
                <div class="header-content">
                    <div class="header-left">
                        <div class="header-icon">
                            <span v-html="icons.users"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">Shortlisted Candidates</h1>
                            <p class="header-subtitle">{{ vacancy.title }}</p>
                            <div class="header-meta">
                                <span class="meta-item">
                                    <span
                                        class="meta-icon"
                                        v-html="icons.briefcase"
                                    ></span>
                                    <span>{{ vacancy.department || "—" }}</span>
                                </span>
                                <span class="meta-divider">•</span>
                                <span class="meta-item">
                                    <span
                                        class="meta-icon"
                                        v-html="icons.mapPin"
                                    ></span>
                                    <span>{{ vacancy.location || "—" }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="header-actions">
                        <Link href="/hr/shortlist" class="header-btn">
                            <span
                                class="btn-icon"
                                v-html="icons.arrowLeft"
                            ></span>
                            <span>Back to Vacancies</span>
                        </Link>
                    </div>
                </div>

                <!-- Flash Message -->
                <div v-if="flashMsg" class="flash-message">
                    <span class="flash-icon" v-html="icons.check"></span>
                    <span>{{ flashMsg }}</span>
                </div>
            </section>

            <!-- Info Banner -->
            <div class="info-banner">
                <span class="info-icon" v-html="icons.info"></span>
                <span
                    >Invite candidates for interviews, open evaluation board, or
                    finalize hiring decisions</span
                >
            </div>

            <!-- Top 3 Podium (if 3+ candidates) -->
            <section v-if="localRows.length >= 3" class="podium-section">
                <h2 class="podium-title">
                    <span class="title-icon" v-html="icons.star"></span>
                    <span>Top 3 Candidates</span>
                </h2>

                <div class="podium-grid">
                    <!-- 2nd Place -->
                    <div class="podium-card podium-silver">
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#2</span>
                        </div>
                        <div class="podium-name">
                            {{ localRows[1].anon_name }}
                        </div>
                        <div class="podium-score">
                            {{ localRows[1].match_score ?? 0 }}
                        </div>
                        <div class="podium-label">Match Score</div>
                    </div>

                    <!-- 1st Place -->
                    <div class="podium-card podium-gold">
                        <div class="podium-crown">
                            <span v-html="icons.star"></span>
                        </div>
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#1</span>
                        </div>
                        <div class="podium-name">
                            {{ localRows[0].anon_name }}
                        </div>
                        <div class="podium-score">
                            {{ localRows[0].match_score ?? 0 }}
                        </div>
                        <div class="podium-label">Match Score</div>
                    </div>

                    <!-- 3rd Place -->
                    <div class="podium-card podium-bronze">
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#3</span>
                        </div>
                        <div class="podium-name">
                            {{ localRows[2].anon_name }}
                        </div>
                        <div class="podium-score">
                            {{ localRows[2].match_score ?? 0 }}
                        </div>
                        <div class="podium-label">Match Score</div>
                    </div>
                </div>
            </section>

            <!-- Candidates List -->
            <section class="candidates-section">
                <h2 class="section-title">
                    <span>All Shortlisted Candidates</span>
                    <span class="candidate-count"
                        >{{ localRows.length }} total</span
                    >
                </h2>

                <div class="candidates-grid">
                    <article
                        v-for="(r, idx) in localRows"
                        :key="r.id"
                        class="candidate-card"
                        :class="{
                            'card-rank-1': idx === 0,
                            'card-rank-2': idx === 1,
                            'card-rank-3': idx === 2,
                        }"
                        :style="{ '--card-delay': `${idx * 0.05}s` }"
                    >
                        <!-- Rank Badge for Top 3 -->
                        <div
                            v-if="idx < 3"
                            class="rank-badge"
                            :class="`rank-${idx + 1}`"
                        >
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span>#{{ idx + 1 }}</span>
                        </div>

                        <!-- Invited Badge -->
                        <div v-if="r.invited" class="invited-badge">
                            <span
                                class="badge-icon"
                                v-html="icons.check"
                            ></span>
                            <span>Invited</span>
                        </div>

                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="candidate-info">
                                <div class="candidate-name-row">
                                    <span
                                        v-if="idx < 3"
                                        class="rank-text"
                                        :class="`rank-${idx + 1}`"
                                        >#{{ idx + 1 }}</span
                                    >
                                    <span class="candidate-name">{{
                                        r.anon_name
                                    }}</span>
                                </div>

                                <div class="candidate-meta">
                                    <span class="meta-text">{{
                                        r.vacancy_title
                                    }}</span>
                                    <span class="meta-divider">•</span>
                                    <span class="meta-text"
                                        >Shortlisted
                                        {{ r.created_at || "—" }}</span
                                    >
                                </div>
                            </div>

                            <div class="score-badge">
                                <div class="score-label">Match Score</div>
                                <div class="score-value">
                                    {{ r.match_score ?? 0 }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions">
                            <Link
                                :href="evalLink(r)"
                                class="action-btn action-evaluation"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.clipboard"
                                ></span>
                                <span>Evaluation Board</span>
                            </Link>

                            <button
                                v-if="!r.invited"
                                @click="openInvite(r)"
                                class="action-btn action-invite"
                                :disabled="r._busy"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.mail"
                                ></span>
                                <span v-if="r._busy">Saving...</span>
                                <span v-else>Invite for Interview</span>
                            </button>

                            <button
                                v-if="r.invited"
                                @click="openCancelModal(r)"
                                class="action-btn action-cancel"
                                :disabled="r._busy"
                            >
                                <span class="btn-icon" v-html="icons.x"></span>
                                <span>Cancel Interview</span>
                            </button>

                            <button
                                @click="openHireModal(r)"
                                class="action-btn action-hire"
                                :disabled="r._busy || !canHire(r)"
                                :title="
                                    canHire(r)
                                        ? 'Mark as hired'
                                        : 'Interview required first'
                                "
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.sparkles"
                                ></span>
                                <span>Hire</span>
                            </button>

                            <button
                                @click="openRejectModal(r)"
                                class="action-btn action-reject"
                                :disabled="r._busy"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.userX"
                                ></span>
                                <span>Reject</span>
                            </button>
                        </div>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-if="!localRows.length" class="empty-state">
                    <div class="empty-icon">
                        <span v-html="icons.users"></span>
                    </div>
                    <h3 class="empty-title">No Shortlisted Candidates</h3>
                    <p class="empty-text">
                        No candidates have been shortlisted yet for this vacancy
                    </p>
                </div>
            </section>

            <!-- Invite Modal - UPDATED -->
            <transition name="modal">
                <div
                    v-if="showInviteModal"
                    class="modal-backdrop"
                    @click="closeInvite"
                >
                    <div class="modal-content modal-large" @click.stop>
                        <button class="modal-close" @click="closeInvite">
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-info">
                            <span v-html="icons.calendar"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Schedule Interview</h3>
                        <p class="modal-text">
                            Fill in the interview details. The candidate will
                            see this information on their dashboard.
                        </p>

                        <div class="invite-form">
                            <!-- Date & Time -->
                            <div class="form-group">
                                <label class="form-label">
                                    Date & Time
                                    <span class="required">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    v-model="inviteForm.scheduled_at"
                                    class="form-input"
                                />
                            </div>

                            <!-- Mode - UPDATED -->
                            <div class="form-group">
                                <label class="form-label">
                                    Interview Mode
                                    <span class="required">*</span>
                                </label>
                                <select
                                    v-model="inviteForm.mode"
                                    class="form-select"
                                >
                                    <option value="online">
                                        Online (Virtual Meeting)
                                    </option>
                                    <option value="onsite">
                                        On-site (At Company Office)
                                    </option>
                                </select>
                                <p class="form-hint">
                                    Online = Meeting link required | On-site =
                                    Interview at BASF PETRONAS Chemicals office
                                </p>
                            </div>

                            <!-- Location (Only for On-site) - UPDATED -->
                            <div v-if="isOnsite" class="form-group">
                                <label class="form-label">
                                    Interview Location
                                    <span class="required">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <span
                                        class="input-icon"
                                        v-html="icons.mapPin"
                                    ></span>
                                    <input
                                        type="text"
                                        v-model="inviteForm.location"
                                        class="form-input-icon form-input"
                                        readonly
                                        style="
                                            background: rgba(
                                                148,
                                                163,
                                                184,
                                                0.05
                                            );
                                            cursor: default;
                                        "
                                    />
                                </div>
                                <p class="form-hint">
                                    Fixed location: Our company office in
                                    Gebeng, Kuantan
                                </p>
                            </div>

                            <!-- Meeting Link (Only for Online) - UPDATED -->
                            <div v-if="isOnline" class="form-group">
                                <label class="form-label">
                                    Online Meeting Link
                                    <span class="required">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <span
                                        class="input-icon"
                                        v-html="icons.video"
                                    ></span>
                                    <input
                                        type="url"
                                        v-model="inviteForm.meeting_link"
                                        class="form-input-icon form-input"
                                        placeholder="e.g., https://meet.google.com/abc-defg-hij"
                                    />
                                </div>
                                <p class="form-hint">
                                    Supported: Google Meet, Zoom, Microsoft
                                    Teams, or any meeting platform
                                </p>
                            </div>

                            <!-- Extra Info -->
                            <div class="form-group">
                                <label class="form-label">Extra Notes</label>
                                <div class="input-with-icon">
                                    <span
                                        class="input-icon input-icon-top"
                                        v-html="icons.fileText"
                                    ></span>
                                    <textarea
                                        v-model="inviteForm.extra_info"
                                        rows="3"
                                        class="form-input-icon form-textarea"
                                        placeholder="e.g., Please bring your portfolio and be ready for a short coding exercise"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button
                                @click="closeInvite"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitInvite"
                                class="modal-btn modal-btn-confirm"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.mail"
                                ></span>
                                <span>Send Invitation</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Reject Modal -->
            <transition name="modal">
                <div
                    v-if="showRejectModal"
                    class="modal-backdrop"
                    @click="showRejectModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showRejectModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-danger">
                            <span v-html="icons.userX"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Reject Candidate?</h3>
                        <p class="modal-text">
                            Are you sure you want to reject
                            <strong>{{ activeRow?.anon_name }}</strong
                            >? This action will notify the candidate.
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showRejectModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmReject"
                                class="modal-btn modal-btn-danger"
                            >
                                Reject Candidate
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Cancel Interview Modal -->
            <transition name="modal">
                <div
                    v-if="showCancelModal"
                    class="modal-backdrop"
                    @click="showCancelModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showCancelModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-warning">
                            <span v-html="icons.alert"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Cancel Interview?</h3>
                        <p class="modal-text">
                            Cancel the scheduled interview for
                            <strong>{{ activeRow?.anon_name }}</strong
                            >? The candidate will be notified.
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showCancelModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Back
                            </button>
                            <button
                                @click="confirmCancel"
                                class="modal-btn modal-btn-warning"
                            >
                                Cancel Interview
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Hire Modal -->
            <transition name="modal">
                <div
                    v-if="showHireModal"
                    class="modal-backdrop"
                    @click="showHireModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showHireModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-success">
                            <span v-html="icons.sparkles"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Hire Candidate? 🎉</h3>
                        <p class="modal-text">
                            Mark <strong>{{ activeRow?.anon_name }}</strong> as
                            hired and create their employee record?
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showHireModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmHire"
                                class="modal-btn modal-btn-hire"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.sparkles"
                                ></span>
                                <span>Hire Now!</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </StaffLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

/* Icons */
.btn-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.meta-icon {
    display: inline-flex;
    width: 14px;
    height: 14px;
}
.meta-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.title-icon {
    display: inline-flex;
    width: 24px;
    height: 24px;
}
.title-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.rank-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.rank-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.badge-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.flash-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.flash-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.info-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.info-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.input-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.input-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.input-icon-top {
    align-self: flex-start;
    margin-top: 0.875rem;
}

/* Page Container */
.page-container {
    max-width: 80rem;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    gap: 2rem;
}

/* Toast */
.toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    font-size: 0.9375rem;
    font-weight: 600;
}
.toast-icon {
    display: flex;
    width: 20px;
    height: 20px;
}
.toast-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.toast-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.95),
        rgba(5, 150, 105, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-error {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.95),
        rgba(220, 38, 38, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-info {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.95),
        rgba(37, 99, 235, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-enter-active {
    animation: toastIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
    animation: toastOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
@keyframes toastOut {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
}

/* Header */
.header-card {
    position: relative;
    padding: 2rem;
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
}
.header-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(6, 182, 212, 0.1) 0%,
        transparent 60%
    );
    pointer-events: none;
}
.header-content {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.header-icon {
    position: relative;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.15),
        rgba(14, 165, 233, 0.15)
    );
    border-radius: 16px;
    color: #0ea5e9;
}
.header-icon :deep(svg) {
    width: 32px;
    height: 32px;
    position: relative;
    z-index: 1;
}
.icon-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 16px;
    border: 2px solid rgba(6, 182, 212, 0.4);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.1);
        opacity: 0;
    }
}
.header-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}
.header-subtitle {
    font-size: 1.125rem;
    color: #64748b;
    font-weight: 600;
    margin: 0.25rem 0 0.5rem;
}
.header-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.meta-divider {
    color: #cbd5e1;
}

.header-actions {
    display: flex;
    gap: 1rem;
}
.header-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
}
.header-btn:hover {
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
}

.flash-message {
    position: relative;
    margin-top: 1.5rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    color: #065f46;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Info Banner */
.info-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.1),
        rgba(37, 99, 235, 0.05)
    );
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 12px;
    color: #1e40af;
    font-size: 0.9375rem;
    font-weight: 500;
}

/* Podium Section - Similar to AI Ranking */
.podium-section {
    padding: 3rem 2rem;
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.05),
        rgba(16, 185, 129, 0.05)
    );
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
}
.podium-title {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 3rem;
    text-align: center;
}
.podium-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    align-items: end;
}
.podium-card {
    position: relative;
    padding: 2rem 1.5rem;
    background: white;
    border-radius: 20px;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: podiumRise 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes podiumRise {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.podium-card:hover {
    transform: translateY(-8px);
}
.podium-gold {
    border: 3px solid #f59e0b;
    box-shadow: 0 20px 60px rgba(245, 158, 11, 0.3);
    min-height: 280px;
    animation-delay: 0.2s;
}
.podium-silver {
    border: 3px solid #94a3b8;
    box-shadow: 0 15px 45px rgba(148, 163, 184, 0.25);
    min-height: 240px;
    animation-delay: 0.1s;
}
.podium-bronze {
    border: 3px solid #f97316;
    box-shadow: 0 15px 45px rgba(249, 115, 22, 0.25);
    min-height: 240px;
    animation-delay: 0.3s;
}
.podium-crown {
    position: absolute;
    top: -24px;
    left: 50%;
    transform: translateX(-50%);
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 50%;
    color: white;
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4);
    animation: crownBounce 2s ease-in-out infinite;
}
@keyframes crownBounce {
    0%,
    100% {
        transform: translateX(-50%) translateY(0);
    }
    50% {
        transform: translateX(-50%) translateY(-8px);
    }
}
.podium-crown :deep(svg) {
    width: 28px;
    height: 28px;
}
.podium-rank {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.podium-gold .rank-number {
    color: #f59e0b;
}
.podium-silver .rank-number {
    color: #64748b;
}
.podium-bronze .rank-number {
    color: #f97316;
}
.rank-number {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
}
.podium-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1rem;
    line-height: 1.3;
    min-height: 2.6rem;
}
.podium-score {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.5rem;
}
.podium-gold .podium-score {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.podium-silver .podium-score {
    background: linear-gradient(135deg, #64748b, #475569);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.podium-bronze .podium-score {
    background: linear-gradient(135deg, #f97316, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.podium-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Candidates Section */
.candidates-section {
}
.section-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 1.5rem;
}
.candidate-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    padding: 0.5rem 1rem;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 50px;
}

.candidates-grid {
    display: grid;
    gap: 1.5rem;
}
.candidate-card {
    position: relative;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: cardFadeIn 0.6s ease-out var(--card-delay) both;
}
@keyframes cardFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.candidate-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}

/* Top 3 Card Styling */
.card-rank-1 {
    border: 2px solid #f59e0b;
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(245, 158, 11, 0.02) 100%
    );
    box-shadow: 0 8px 32px rgba(245, 158, 11, 0.2);
}
.card-rank-1::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), transparent);
    pointer-events: none;
}
.card-rank-2 {
    border: 2px solid #94a3b8;
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(148, 163, 184, 0.02) 100%
    );
    box-shadow: 0 6px 28px rgba(148, 163, 184, 0.2);
}
.card-rank-2::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.05), transparent);
    pointer-events: none;
}
.card-rank-3 {
    border: 2px solid #f97316;
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(249, 115, 22, 0.02) 100%
    );
    box-shadow: 0 6px 28px rgba(249, 115, 22, 0.2);
}
.card-rank-3::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(249, 115, 22, 0.05), transparent);
    pointer-events: none;
}

.rank-badge {
    position: absolute;
    top: -14px;
    left: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10;
    animation: badgePop 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
}
@keyframes badgePop {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.rank-badge.rank-1 {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}
.rank-badge.rank-2 {
    background: linear-gradient(135deg, #94a3b8, #64748b);
    color: white;
}
.rank-badge.rank-3 {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
}

.invited-badge {
    position: absolute;
    top: -14px;
    right: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    z-index: 10;
    animation: badgePop 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
}

/* Card Header */
.card-header {
    position: relative;
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}
.candidate-info {
    flex: 1;
    min-width: 0;
}
.candidate-name-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.rank-text {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 800;
}
.rank-text.rank-1 {
    color: #f59e0b;
}
.rank-text.rank-2 {
    color: #64748b;
}
.rank-text.rank-3 {
    color: #f97316;
}
.candidate-name {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
}
.candidate-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
    flex-wrap: wrap;
}
.meta-text {
}

.score-badge {
    text-align: center;
    padding: 1rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
}
.score-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.score-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #10b981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Actions */
.card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}
.action-evaluation {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.action-evaluation:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    transform: translateY(-2px);
}
.action-invite {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-invite:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.action-cancel {
    background: white;
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.action-cancel:hover:not(:disabled) {
    background: rgba(245, 158, 11, 0.05);
    transform: translateY(-2px);
}
.action-hire {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.action-hire:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}
.action-reject {
    background: white;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.action-reject:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.05);
    transform: translateY(-2px);
}
.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 20px;
    border: 2px dashed rgba(148, 163, 184, 0.3);
}
.empty-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 50%;
    color: #64748b;
    margin-bottom: 1.5rem;
}
.empty-icon :deep(svg) {
    width: 40px;
    height: 40px;
}
.empty-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.5rem;
}
.empty-text {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
    text-align: center;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal-content {
    position: relative;
    background: white;
    border-radius: 20px;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-large {
    max-width: 600px;
}
.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.1);
    color: #64748b;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 10;
}
.modal-close:hover {
    background: rgba(148, 163, 184, 0.2);
    transform: rotate(90deg);
}
.modal-close :deep(svg) {
    width: 16px;
    height: 16px;
}

.modal-icon {
    position: relative;
    width: 64px;
    height: 64px;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}
.modal-icon :deep(svg) {
    width: 32px;
    height: 32px;
    position: relative;
    z-index: 1;
}
.modal-icon-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid currentColor;
    animation: modalPulse 2s infinite;
}
@keyframes modalPulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.15);
        opacity: 0;
    }
}
.modal-icon-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #10b981;
}
.modal-icon-danger {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #ef4444;
}
.modal-icon-warning {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #f59e0b;
}
.modal-icon-info {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    color: #3b82f6;
}

.modal-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    text-align: center;
    margin: 0 0 0.75rem;
}
.modal-text {
    font-size: 0.9375rem;
    color: #64748b;
    text-align: center;
    line-height: 1.6;
    margin: 0 0 2rem;
}

/* Invite Form */
.invite-form {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.form-group {
}
.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.required {
    color: #ef4444;
    margin-left: 0.25rem;
}
.form-hint {
    font-size: 0.8125rem;
    color: #64748b;
    margin-top: 0.375rem;
}
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    color: #0f172a;
    background: white;
    transition: all 0.3s;
    font-family: inherit;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.form-input:disabled,
.form-select:disabled,
.form-textarea:disabled {
    background: rgba(148, 163, 184, 0.05);
    color: #94a3b8;
    cursor: not-allowed;
}
.input-with-icon {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}
.form-input-icon {
    padding-left: 3rem;
}
.input-with-icon .input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
}
.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
}
.modal-btn {
    flex: 1;
    padding: 0.875rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.modal-btn-cancel {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.modal-btn-cancel:hover {
    background: rgba(148, 163, 184, 0.05);
}
.modal-btn-confirm {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.modal-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.modal-btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.modal-btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}
.modal-btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
.modal-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
}
.modal-btn-hire {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}
.modal-btn-hire:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
}

.modal-enter-active {
    animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-leave-active {
    animation: modalOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes modalIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@keyframes modalOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}
.modal-enter-active .modal-content {
    animation: modalSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-leave-active .modal-content {
    animation: modalSlideOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes modalSlideIn {
    from {
        transform: translateY(20px) scale(0.95);
    }
    to {
        transform: translateY(0) scale(1);
    }
}
@keyframes modalSlideOut {
    from {
        transform: translateY(0) scale(1);
    }
    to {
        transform: translateY(20px) scale(0.95);
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .page-container {
        padding: 1rem;
    }
    .header-content {
        flex-direction: column;
    }
    .header-actions {
        width: 100%;
    }
    .podium-grid {
        grid-template-columns: 1fr;
    }
    .podium-card {
        min-height: auto !important;
    }
}

@media (max-width: 768px) {
    .header-card {
        padding: 1.5rem;
    }
    .header-left {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-icon {
        width: 56px;
        height: 56px;
    }
    .header-icon :deep(svg) {
        width: 28px;
        height: 28px;
    }
    .header-title {
        font-size: 1.75rem;
    }

    .candidate-card {
        padding: 1.5rem;
    }
    .card-header {
        flex-direction: column;
    }
    .score-badge {
        align-self: flex-start;
    }
    .card-actions {
        flex-direction: column;
    }
    .action-btn {
        width: 100%;
        justify-content: center;
    }

    .toast {
        top: 1rem;
        right: 1rem;
        left: 1rem;
    }
}

@media (max-width: 640px) {
    .header-card {
        padding: 1.25rem;
    }
    .header-icon {
        width: 48px;
        height: 48px;
    }
    .header-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }
    .header-title {
        font-size: 1.5rem;
    }
    .candidate-card {
        padding: 1.25rem;
    }
    .modal-content {
        padding: 1.5rem;
    }
    .rank-badge,
    .invited-badge {
        position: static;
        transform: none;
        margin-bottom: 1rem;
    }
}
</style>
