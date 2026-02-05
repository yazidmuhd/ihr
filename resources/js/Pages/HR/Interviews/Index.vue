<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    shortlisted: { type: Array, default: () => [] },
    interviews: { type: Array, default: () => [] },
    weights: { type: Object, default: () => ({ resume: 0.6, interview: 0.4 }) },
});

/* ---------------- state ---------------- */
const q = ref("");
const tab = ref("upcoming");

// Modals
const showMarkDoneModal = ref(false);
const showNoShowModal = ref(false);
const showCancelModal = ref(false);
const showFinalizeModal = ref(false);
const selectedInterview = ref(null);

// Toast & Transfer notification
const toast = ref({ show: false, message: "", type: "" });
const transferNotification = ref({ show: false, candidateName: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

function showTransferNotification(candidateName) {
    transferNotification.value = { show: true, candidateName };
    setTimeout(() => {
        transferNotification.value.show = false;
    }, 5000);
}

/* ---------------- helpers ---------------- */
const norm = (v) =>
    String(v || "")
        .toLowerCase()
        .trim()
        .replace(/[\s-]+/g, "_");

const fmtDateTime = (value) => {
    if (!value) return "To be confirmed";
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

const modeLabel = (m) => {
    const t = norm(m);
    if (t === "online") return "Online";
    if (t === "onsite" || t === "on_site") return "On-site";
    if (t === "hybrid") return "Hybrid";
    return m || "—";
};

const interviewTone = (s) => {
    const t = norm(s);
    if (["scheduled", "invited"].includes(t)) return "badge-blue";
    if (["done", "scored"].includes(t)) return "badge-green";
    if (["cancelled", "no_show"].includes(t)) return "badge-red";
    return "badge-slate";
};

const responseTone = (s) => {
    const t = norm(s);
    if (["confirmed", "accepted", "confirm"].includes(t))
        return "chip chip-yes";
    if (t === "declined") return "chip chip-no";
    if (!t || t === "pending") return "chip chip-pending";
    return "chip chip-neutral";
};

const responseLabel = (s) => {
    const t = norm(s);
    if (["confirmed", "accepted", "confirm"].includes(t)) return "Confirmed";
    if (t === "declined") return "Declined";
    if (!t || t === "pending") return "Pending response";
    return s || "—";
};

const isDone = (i) => {
    const t = norm(i.status);
    return ["done", "scored"].includes(t);
};

const canEvaluate = (i) => {
    const t = norm(i.status);
    return ["done", "scored"].includes(t);
};

const matchesQuery = (i) => {
    const needle = norm(q.value);
    if (!needle) return true;

    const hay = [
        i.anon_name,
        i.applicant_name,
        i.applicant_email,
        i.resume_name,
        i.vacancy_title,
        i.application_status,
        i.status,
        i.mode,
        i.location,
        i.candidate_status,
    ]
        .map((x) => String(x || ""))
        .join(" ")
        .toLowerCase();

    return hay.includes(needle);
};

const matchesTab = (i) => {
    const r = norm(i.candidate_status);

    if (tab.value === "all") return true;
    if (tab.value === "upcoming") return !isDone(i);
    if (tab.value === "done") return isDone(i);
    if (tab.value === "pending") return !r || r === "pending";
    if (tab.value === "confirmed")
        return ["confirmed", "accepted", "confirm"].includes(r);
    if (tab.value === "declined") return r === "declined";

    return true;
};

const sorted = computed(() => {
    const arr = [...(props.interviews || [])]
        .filter(matchesQuery)
        .filter(matchesTab);

    return arr.sort((a, b) => {
        const aDone = isDone(a);
        const bDone = isDone(b);

        if (!aDone && bDone) return -1;
        if (aDone && !bDone) return 1;

        const aHas = !!a.scheduled_at;
        const bHas = !!b.scheduled_at;

        if (!aDone) {
            if (aHas && bHas)
                return String(a.scheduled_at).localeCompare(
                    String(b.scheduled_at),
                );
            if (aHas && !bHas) return -1;
            if (!aHas && bHas) return 1;
            return (a.id ?? 0) - (b.id ?? 0);
        }

        if (aHas && bHas)
            return String(b.scheduled_at).localeCompare(String(a.scheduled_at));
        if (aHas && !bHas) return -1;
        if (!aHas && bHas) return 1;
        return (b.id ?? 0) - (a.id ?? 0);
    });
});

const canMarkDone = (i) => {
    const resp = norm(i.candidate_status);
    const st = norm(i.status);

    const confirmed = ["confirmed", "accepted", "confirm"].includes(resp);
    const locked = ["done", "scored", "cancelled", "no_show"].includes(st);

    return confirmed && !locked;
};

/* ---------------- actions ---------------- */
function openApplication(i) {
    if (!i.vacancy_id) return;
    router.visit(`/hr/vacancies/${i.vacancy_id}/ai`);
}

function openEvaluation(i) {
    if (!canEvaluate(i)) return;
    if (!i.vacancy_id) return;
    const url = i.application_id
        ? `/hr/vacancies/${i.vacancy_id}/evaluation?application_id=${i.application_id}`
        : `/hr/vacancies/${i.vacancy_id}/evaluation`;
    router.visit(url);
}

function openMarkDoneModal(i) {
    selectedInterview.value = i;
    showMarkDoneModal.value = true;
}

function confirmMarkDone() {
    showMarkDoneModal.value = false;
    const interview = selectedInterview.value;
    const candidateName =
        interview.applicant_name || interview.anon_name || "Candidate";

    router.patch(
        `/hr/interviews/${interview.id}/status`,
        { status: "done" },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast("Interview marked as done!", "success");
                showTransferNotification(candidateName);
            },
            onError: () =>
                showToast("Failed to update interview status", "error"),
        },
    );
}

function openNoShowModal(i) {
    selectedInterview.value = i;
    showNoShowModal.value = true;
}

function confirmNoShow() {
    showNoShowModal.value = false;
    router.patch(
        `/hr/interviews/${selectedInterview.value.id}/status`,
        { status: "no_show" },
        {
            preserveScroll: true,
            onSuccess: () => showToast("Interview marked as no-show", "info"),
            onError: () =>
                showToast("Failed to update interview status", "error"),
        },
    );
}

function openCancelModal(i) {
    selectedInterview.value = i;
    showCancelModal.value = true;
}

function confirmCancel() {
    showCancelModal.value = false;
    router.patch(
        `/hr/interviews/${selectedInterview.value.id}/status`,
        { status: "cancelled" },
        {
            preserveScroll: true,
            onSuccess: () => showToast("Interview cancelled", "info"),
            onError: () => showToast("Failed to cancel interview", "error"),
        },
    );
}

function openFinalizeModal(i) {
    if (!canEvaluate(i)) return;
    selectedInterview.value = i;
    showFinalizeModal.value = true;
}

function confirmFinalize() {
    showFinalizeModal.value = false;
    router.patch(
        `/hr/interviews/${selectedInterview.value.id}/finalize`,
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                showToast("Interview evaluation finalized!", "success"),
            onError: () => showToast("Failed to finalize evaluation", "error"),
        },
    );
}

const icons = {
    calendar:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    clipboard:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
    file: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    arrowRight:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    checkCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>',
};
</script>

<template>
    <Head title="HR Interviews" />
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

            <!-- Transfer Notification -->
            <transition name="transfer">
                <div
                    v-if="transferNotification.show"
                    class="transfer-notification"
                >
                    <div class="transfer-icon">
                        <span v-html="icons.arrowRight"></span>
                    </div>
                    <div class="transfer-content">
                        <div class="transfer-title">Candidate Transferred!</div>
                        <div class="transfer-text">
                            <strong>{{
                                transferNotification.candidateName
                            }}</strong>
                            has been moved to the
                            <strong>"Done / Scored"</strong> section. You can
                            now complete the evaluation.
                        </div>
                    </div>
                    <div class="transfer-checkmark">
                        <span v-html="icons.checkCircle"></span>
                    </div>
                </div>
            </transition>

            <!-- Header -->
            <section class="header-card">
                <div class="header-glow"></div>
                <div class="header-top">
                    <div class="header-left">
                        <div class="header-icon">
                            <span v-html="icons.calendar"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">Interview Management</h1>
                            <p class="header-subtitle">
                                Track confirmations, manage schedules, and
                                evaluate candidates
                            </p>
                        </div>
                    </div>

                    <div class="header-actions">
                        <div class="search-wrapper">
                            <span
                                class="search-icon"
                                v-html="icons.search"
                            ></span>
                            <input
                                v-model="q"
                                class="search-input"
                                placeholder="Search candidates, emails, vacancies..."
                            />
                        </div>
                        <Link href="/hr/vacancies" class="header-btn">
                            <span
                                class="btn-icon"
                                v-html="icons.briefcase"
                            ></span>
                            <span>Vacancies</span>
                        </Link>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tabs-container">
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'upcoming' }"
                        @click="tab = 'upcoming'"
                    >
                        <span>Upcoming</span>
                        <span class="tab-count">{{
                            interviews.filter((i) => !isDone(i)).length
                        }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'pending' }"
                        @click="tab = 'pending'"
                    >
                        <span>Pending Reply</span>
                        <span class="tab-count">{{
                            interviews.filter(
                                (i) =>
                                    !norm(i.candidate_status) ||
                                    norm(i.candidate_status) === "pending",
                            ).length
                        }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'confirmed' }"
                        @click="tab = 'confirmed'"
                    >
                        <span>Confirmed</span>
                        <span class="tab-count">{{
                            interviews.filter((i) =>
                                ["confirmed", "accepted", "confirm"].includes(
                                    norm(i.candidate_status),
                                ),
                            ).length
                        }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'declined' }"
                        @click="tab = 'declined'"
                    >
                        <span>Declined</span>
                        <span class="tab-count">{{
                            interviews.filter(
                                (i) => norm(i.candidate_status) === "declined",
                            ).length
                        }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'done' }"
                        @click="tab = 'done'"
                    >
                        <span>Done / Scored</span>
                        <span class="tab-count">{{
                            interviews.filter((i) => isDone(i)).length
                        }}</span>
                    </button>
                    <button
                        class="tab-btn"
                        :class="{ 'tab-active': tab === 'all' }"
                        @click="tab = 'all'"
                    >
                        <span>All</span>
                        <span class="tab-count">{{ interviews.length }}</span>
                    </button>
                </div>
            </section>

            <!-- Empty State -->
            <div v-if="!sorted.length" class="empty-state">
                <div class="empty-icon">
                    <span v-html="icons.calendar"></span>
                </div>
                <h3 class="empty-title">No Interviews Found</h3>
                <p class="empty-text">
                    No interviews match the current filter criteria
                </p>
            </div>

            <!-- Interview Cards -->
            <section v-else class="interviews-grid">
                <article
                    v-for="(i, index) in sorted"
                    :key="i.id"
                    class="interview-card"
                    :class="{ 'card-done': isDone(i) }"
                    :style="{ '--card-delay': `${index * 0.05}s` }"
                >
                    <!-- Status indicator -->
                    <div v-if="isDone(i)" class="done-badge">
                        <span
                            class="badge-icon"
                            v-html="icons.checkCircle"
                        ></span>
                        <span>Completed</span>
                    </div>

                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="candidate-info">
                            <div class="candidate-name">
                                {{
                                    i.applicant_name ||
                                    i.anon_name ||
                                    `Candidate #${i.applicant_id ?? "—"}`
                                }}
                            </div>
                            <div
                                v-if="i.applicant_email"
                                class="candidate-email"
                            >
                                {{ i.applicant_email }}
                            </div>
                            <div class="vacancy-info">
                                <span class="vacancy-label">Vacancy:</span>
                                <span>{{ i.vacancy_title || "—" }}</span>
                            </div>
                        </div>

                        <div class="card-status">
                            <span
                                class="status-badge"
                                :class="interviewTone(i.status)"
                            >
                                {{ i.status || "invited" }}
                            </span>
                            <span :class="responseTone(i.candidate_status)">
                                {{ responseLabel(i.candidate_status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Interview Details -->
                    <div class="details-section">
                        <div class="detail-group">
                            <div class="detail-label">Schedule</div>
                            <div class="detail-value">
                                <div class="detail-row">
                                    <span
                                        class="detail-icon"
                                        v-html="icons.calendar"
                                    ></span>
                                    <span>{{
                                        i.scheduled_at
                                            ? fmtDateTime(i.scheduled_at)
                                            : "To be confirmed"
                                    }}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="mode-badge">{{
                                        modeLabel(i.mode)
                                    }}</span>
                                    <span
                                        v-if="i.location"
                                        class="detail-location"
                                        >{{ i.location }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Meeting Link</div>
                            <div class="detail-value">
                                <a
                                    v-if="i.meeting_link"
                                    :href="i.meeting_link"
                                    target="_blank"
                                    class="meeting-link"
                                >
                                    <span
                                        class="link-icon"
                                        v-html="icons.link"
                                    ></span>
                                    <span>Open Meeting</span>
                                </a>
                                <span v-else class="text-muted"
                                    >Not provided</span
                                >
                            </div>
                        </div>

                        <div v-if="i.resume_url" class="detail-group">
                            <div class="detail-label">Resume</div>
                            <div class="detail-value">
                                <a
                                    :href="i.resume_url"
                                    target="_blank"
                                    class="resume-link"
                                >
                                    <span
                                        class="link-icon"
                                        v-html="icons.file"
                                    ></span>
                                    <span>{{
                                        i.resume_name || "View Resume"
                                    }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="detail-group detail-full">
                            <div class="detail-label">Application Info</div>
                            <div class="detail-value detail-grid">
                                <div>
                                    <strong>ID:</strong>
                                    {{ i.application_id || "—" }}
                                </div>
                                <div>
                                    <strong>Match:</strong>
                                    {{ i.match_score ?? "—" }}
                                </div>
                                <div>
                                    <strong>Vacancy:</strong>
                                    {{ i.vacancy_id ?? "—" }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="i.extra_info"
                            class="detail-group detail-full"
                        >
                            <div class="detail-label">Notes</div>
                            <div class="detail-value detail-notes">
                                {{ i.extra_info }}
                            </div>
                        </div>

                        <div
                            v-if="i.candidate_reason"
                            class="detail-group detail-full"
                        >
                            <div class="detail-label">Candidate Message</div>
                            <div class="detail-value detail-reason">
                                "{{ i.candidate_reason }}"
                            </div>
                        </div>
                    </div>

                    <!-- Evaluation Lock Notice -->
                    <div v-if="!canEvaluate(i)" class="lock-notice">
                        <span class="lock-icon">🔒</span>
                        <span
                            >Evaluation locked. Mark interview as
                            <strong>Done</strong> first (candidate must
                            confirm).</span
                        >
                    </div>

                    <!-- Card Actions -->
                    <div class="card-actions">
                        <div class="action-group">
                            <button
                                @click="openApplication(i)"
                                class="action-btn action-view"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.eye"
                                ></span>
                                <span>View Application</span>
                            </button>

                            <button
                                @click="openEvaluation(i)"
                                class="action-btn action-evaluate"
                                :disabled="!canEvaluate(i)"
                                :title="
                                    !canEvaluate(i)
                                        ? 'Locked until interview is marked Done'
                                        : ''
                                "
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.clipboard"
                                ></span>
                                <span>Evaluation</span>
                            </button>
                        </div>

                        <div class="action-group">
                            <button
                                v-if="canMarkDone(i)"
                                @click="openMarkDoneModal(i)"
                                class="action-btn action-done"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>Mark Done</span>
                            </button>

                            <button
                                v-if="canMarkDone(i)"
                                @click="openNoShowModal(i)"
                                class="action-btn action-secondary"
                            >
                                No-Show
                            </button>

                            <button
                                @click="openCancelModal(i)"
                                class="action-btn action-secondary"
                            >
                                Cancel
                            </button>

                            <button
                                @click="openFinalizeModal(i)"
                                class="action-btn action-finalize"
                                :disabled="!canEvaluate(i)"
                                :title="
                                    !canEvaluate(i)
                                        ? 'Locked until interview is done'
                                        : ''
                                "
                            >
                                Finalize
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Mark Done Modal -->
            <transition name="modal">
                <div
                    v-if="showMarkDoneModal"
                    class="modal-backdrop"
                    @click="showMarkDoneModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showMarkDoneModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-success">
                            <span v-html="icons.check"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Mark Interview as Done?</h3>
                        <p class="modal-text">
                            This will move the candidate to the
                            <strong>"Done / Scored"</strong> section and unlock
                            the evaluation form.
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showMarkDoneModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmMarkDone"
                                class="modal-btn modal-btn-confirm"
                            >
                                Mark as Done
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- No-Show Modal -->
            <transition name="modal">
                <div
                    v-if="showNoShowModal"
                    class="modal-backdrop"
                    @click="showNoShowModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showNoShowModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-warning">
                            <span v-html="icons.alert"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Mark as No-Show?</h3>
                        <p class="modal-text">
                            The candidate did not attend the scheduled
                            interview.
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showNoShowModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmNoShow"
                                class="modal-btn modal-btn-warning"
                            >
                                Confirm No-Show
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Cancel Modal -->
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

                        <div class="modal-icon modal-icon-danger">
                            <span v-html="icons.x"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Cancel Interview?</h3>
                        <p class="modal-text">
                            This interview will be marked as cancelled.
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
                                class="modal-btn modal-btn-danger"
                            >
                                Cancel Interview
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Finalize Modal -->
            <transition name="modal">
                <div
                    v-if="showFinalizeModal"
                    class="modal-backdrop"
                    @click="showFinalizeModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showFinalizeModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-info">
                            <span v-html="icons.clipboard"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Finalize Evaluation?</h3>
                        <p class="modal-text">
                            This will lock the evaluation and set the interview
                            status to <strong>"Scored"</strong>.
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showFinalizeModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmFinalize"
                                class="modal-btn modal-btn-confirm"
                            >
                                Finalize Now
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
.search-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.search-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.detail-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
    color: #64748b;
}
.detail-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.link-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.link-icon :deep(svg) {
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

/* Page Container */
.page-container {
    max-width: 80rem;
    margin: 0 auto;
    padding: 1.5rem;
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

/* Transfer Notification */
.transfer-notification {
    position: fixed;
    top: 7rem;
    right: 2rem;
    z-index: 9998;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.98),
        rgba(5, 150, 105, 0.98)
    );
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(16, 185, 129, 0.4);
    backdrop-filter: blur(10px);
    max-width: 400px;
    border: 2px solid rgba(255, 255, 255, 0.3);
}
.transfer-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    flex-shrink: 0;
    animation: transferPulse 2s infinite;
}
@keyframes transferPulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}
.transfer-icon :deep(svg) {
    width: 24px;
    height: 24px;
}
.transfer-content {
    flex: 1;
}
.transfer-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.125rem;
    font-weight: 800;
    color: white;
    margin-bottom: 0.5rem;
}
.transfer-text {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.5;
}
.transfer-checkmark {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    color: #10b981;
    flex-shrink: 0;
}
.transfer-checkmark :deep(svg) {
    width: 20px;
    height: 20px;
}
.transfer-enter-active {
    animation: transferIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.transfer-leave-active {
    animation: transferOut 0.4s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes transferIn {
    from {
        opacity: 0;
        transform: translateX(100%) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}
@keyframes transferOut {
    from {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateX(100%) scale(0.9);
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
.header-top {
    position: relative;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
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
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
    margin: 0.25rem 0 0;
}
.header-actions {
    display: flex;
    gap: 1rem;
}
.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.search-input {
    width: 320px;
    padding: 0.875rem 1rem 0.875rem 3rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.9375rem;
    color: #0f172a;
    background: white;
    transition: all 0.3s;
}
.search-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.search-wrapper .search-icon {
    position: absolute;
    left: 1rem;
    pointer-events: none;
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

/* Tabs */
.tabs-container {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.tab-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s;
}
.tab-btn:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
}
.tab-active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
}
.tab-active .tab-count {
    background: rgba(255, 255, 255, 0.3);
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

/* Interview Cards */
.interviews-grid {
    display: grid;
    gap: 1.5rem;
}
.interview-card {
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
.interview-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}

.card-done {
    border-color: rgba(16, 185, 129, 0.3);
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(16, 185, 129, 0.02) 100%
    );
}
.card-done::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05), transparent);
    pointer-events: none;
}

.done-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    z-index: 10;
    animation: badgePop 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
}
@keyframes badgePop {
    from {
        opacity: 0;
        transform: translateX(-50%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) scale(1);
    }
}

.card-header {
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
.candidate-name {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.candidate-email {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}
.vacancy-info {
    font-size: 0.875rem;
    color: #64748b;
}
.vacancy-label {
    font-weight: 600;
    color: #0f172a;
    margin-right: 0.5rem;
}

.card-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0.875rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.badge-blue {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.badge-green {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.badge-red {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.badge-slate {
    background: linear-gradient(
        135deg,
        rgba(148, 163, 184, 0.15),
        rgba(100, 116, 139, 0.15)
    );
    color: #334155;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.chip {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.875rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
}
.chip-pending {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #92400e;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.chip-yes {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.chip-no {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.chip-neutral {
    background: rgba(148, 163, 184, 0.15);
    color: #475569;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

/* Details Section */
.details-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.5);
    border-radius: 12px;
}
.detail-group {
}
.detail-full {
    grid-column: 1 / -1;
}
.detail-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.detail-value {
    font-size: 0.875rem;
    color: #0f172a;
}
.detail-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}
.mode-badge {
    display: inline-flex;
    padding: 0.25rem 0.75rem;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #0ea5e9;
}
.detail-location {
    font-size: 0.875rem;
    color: #64748b;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.detail-notes {
    white-space: pre-line;
    line-height: 1.6;
}
.detail-reason {
    font-style: italic;
    color: #64748b;
}

.meeting-link,
.resume-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #0ea5e9;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}
.meeting-link:hover,
.resume-link:hover {
    color: #0284c7;
    text-decoration: underline;
}
.text-muted {
    color: #94a3b8;
}

/* Lock Notice */
.lock-notice {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.08),
        rgba(217, 119, 6, 0.05)
    );
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 12px;
    font-size: 0.875rem;
    color: #92400e;
    margin-bottom: 1.5rem;
}
.lock-icon {
    font-size: 1.25rem;
}

/* Card Actions */
.card-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}
.action-group {
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
.action-view {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.action-view:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    transform: translateY(-2px);
}
.action-evaluate {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-evaluate:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.action-evaluate:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.action-done {
    background: linear-gradient(135deg, #0f172a, #334155);
    color: white;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3);
}
.action-done:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.4);
}
.action-secondary {
    background: white;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.action-secondary:hover {
    background: rgba(148, 163, 184, 0.05);
    transform: translateY(-2px);
}
.action-finalize {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
.action-finalize:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}
.action-finalize:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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
    .header-top {
        flex-direction: column;
    }
    .header-actions {
        width: 100%;
        flex-direction: column;
    }
    .search-input {
        width: 100%;
    }
    .detail-grid {
        grid-template-columns: 1fr;
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
    .tabs-container {
        gap: 0.5rem;
    }
    .tab-btn {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
    }

    .interview-card {
        padding: 1.5rem;
    }
    .card-header {
        flex-direction: column;
    }
    .card-status {
        align-items: flex-start;
    }
    .details-section {
        grid-template-columns: 1fr;
    }
    .card-actions {
        flex-direction: column;
    }
    .action-group {
        width: 100%;
        flex-direction: column;
    }
    .action-btn {
        width: 100%;
        justify-content: center;
    }

    .toast,
    .transfer-notification {
        top: 1rem;
        right: 1rem;
        left: 1rem;
    }
    .transfer-notification {
        max-width: none;
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
    .interview-card {
        padding: 1.25rem;
    }
    .modal-content {
        padding: 1.5rem;
    }
}
</style>
