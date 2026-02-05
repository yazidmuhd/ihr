<script setup>
import { Head, router, usePage, Link } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    vacancy: { type: Object, required: true },
    interviews: { type: Array, default: () => [] },
    panels: { type: Object, default: () => ({}) },
    weights: { type: Object, default: () => ({ resume: 0.6, interview: 0.4 }) },
});

const page = usePage();
const flashMsg = computed(() => page.props?.flash?.status || "");

// Modals
const showFinalizeModal = ref(false);
const showHireModal = ref(false);
const showPanelModal = ref(false);
const showResumeModal = ref(false);
const selectedInterview = ref(null);
const selectedResume = ref(null);

// Toast & Celebration
const toast = ref({ show: false, message: "", type: "" });
const showCelebration = ref(false);
const celebrationCandidate = ref("");

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

function triggerCelebration(candidateName) {
    celebrationCandidate.value = candidateName;
    showCelebration.value = true;

    // Trigger confetti
    createConfetti();

    // Hide after 6 seconds
    setTimeout(() => {
        showCelebration.value = false;
    }, 6000);
}

// Confetti animation
function createConfetti() {
    const duration = 5000;
    const animationEnd = Date.now() + duration;
    const defaults = {
        startVelocity: 30,
        spread: 360,
        ticks: 60,
        zIndex: 10000,
    };

    function randomInRange(min, max) {
        return Math.random() * (max - min) + min;
    }

    const interval = setInterval(function () {
        const timeLeft = animationEnd - Date.now();

        if (timeLeft <= 0) {
            return clearInterval(interval);
        }

        const particleCount = 50 * (timeLeft / duration);

        // Create confetti particles
        for (let i = 0; i < particleCount; i++) {
            createConfettiParticle(
                randomInRange(0.1, 0.3),
                randomInRange(0.1, 0.3),
            );
        }

        // Second burst from sides
        for (let i = 0; i < particleCount; i++) {
            createConfettiParticle(
                randomInRange(0.7, 0.9),
                randomInRange(0.1, 0.3),
            );
        }
    }, 250);
}

function createConfettiParticle(x, y) {
    const colors = [
        "#10b981",
        "#3b82f6",
        "#f59e0b",
        "#ef4444",
        "#8b5cf6",
        "#ec4899",
    ];
    const particle = document.createElement("div");
    particle.className = "confetti-particle";
    particle.style.left = x * 100 + "%";
    particle.style.top = y * 100 + "%";
    particle.style.background =
        colors[Math.floor(Math.random() * colors.length)];
    particle.style.animationDuration = Math.random() * 3 + 2 + "s";
    particle.style.animationDelay = Math.random() * 0.5 + "s";

    document.body.appendChild(particle);

    setTimeout(() => {
        particle.remove();
    }, 5000);
}

/* ---------------- helpers ---------------- */
const norm = (v) =>
    String(v || "")
        .toLowerCase()
        .trim()
        .replace(/[\s-]+/g, "_");

const qsApplicationId = computed(() => {
    try {
        return new URLSearchParams(window.location.search).get(
            "application_id",
        );
    } catch {
        return null;
    }
});

const rows = computed(() => {
    const list = props.interviews || [];
    if (!qsApplicationId.value) return list;
    return list.filter(
        (r) => String(r.application_id) === String(qsApplicationId.value),
    );
});

function isUnlocked(row) {
    const st = norm(row.status);
    return st === "done" || st === "scored";
}

function isScored(row) {
    return norm(row.status) === "scored";
}

function panelsFor(interviewId) {
    const list = props.panels?.[interviewId] || [];
    return [...list].sort((a, b) => (a.panel_no ?? 0) - (b.panel_no ?? 0));
}

const panelCountDraft = ref({});
const ratingDraft = ref({});
const editingPanelName = ref({});

function ratingKey(interviewId, panelNo) {
    return `${interviewId}-${panelNo}`;
}

function getRating(interviewId, panel) {
    const k = ratingKey(interviewId, panel.panel_no);
    return ratingDraft.value[k] ?? panel.rating ?? 0;
}

function openPanelModal(row) {
    if (!isUnlocked(row)) {
        showToast("Evaluation locked. Mark interview as DONE first.", "error");
        return;
    }
    selectedInterview.value = row;
    showPanelModal.value = true;
}

function confirmSetPanels() {
    const row = selectedInterview.value;
    const count = panelCountDraft.value[row.id] ?? row.panel_count ?? 0;
    const n = Math.max(0, Math.min(20, Number(count || 0)));

    showPanelModal.value = false;

    router.post(
        `/hr/interviews/${row.id}/panels`,
        { panel_count: n },
        {
            preserveScroll: true,
            onSuccess: () =>
                showToast(`${n} panel(s) set successfully!`, "success"),
            onError: () => showToast("Failed to set panels", "error"),
        },
    );
}

function saveRating(interviewId, panelNo, rating, row) {
    if (!isUnlocked(row)) {
        showToast("Evaluation locked. Mark interview as DONE first.", "error");
        return;
    }

    ratingDraft.value[ratingKey(interviewId, panelNo)] = rating;

    router.post(
        `/hr/interviews/${interviewId}/rate`,
        { panel_no: panelNo, rating },
        {
            preserveScroll: true,
            onSuccess: () => showToast("Rating saved!", "success"),
            onError: () => showToast("Failed to save rating", "error"),
        },
    );
}

function openFinalizeModal(row) {
    if (!isUnlocked(row)) {
        showToast("Evaluation locked. Mark interview as DONE first.", "error");
        return;
    }
    selectedInterview.value = row;
    showFinalizeModal.value = true;
}

function confirmFinalize() {
    showFinalizeModal.value = false;

    router.patch(
        `/hr/interviews/${selectedInterview.value.id}/finalize`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => showToast("Evaluation finalized!", "success"),
            onError: () => showToast("Failed to finalize evaluation", "error"),
        },
    );
}

function openHireModal(row) {
    if (!isScored(row)) {
        showToast("Please Finalize first. Status must be SCORED.", "error");
        return;
    }

    if (row.is_employee) {
        showToast("Employee already created for this candidate.", "info");
        return;
    }

    selectedInterview.value = row;
    showHireModal.value = true;
}

function confirmHire() {
    const row = selectedInterview.value;
    const candidateName = row.applicant_name || row.anon_name || "Candidate";

    showHireModal.value = false;

    router.post(
        `/hr/interviews/${row.id}/hire`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast("Candidate converted to employee!", "success");
                triggerCelebration(candidateName);
            },
            onError: () => showToast("Failed to convert candidate", "error"),
        },
    );
}

function openResumeModal(row) {
    if (!row.resume_url) {
        showToast("No resume available", "info");
        return;
    }
    selectedResume.value = row;
    showResumeModal.value = true;
}

const icons = {
    clipboard:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    file: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    userCheck:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
    trophy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>',
    arrowLeft:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
};
</script>

<template>
    <Head :title="`Interview Evaluation — ${vacancy.title}`" />
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

            <!-- Celebration Overlay -->
            <transition name="celebration">
                <div v-if="showCelebration" class="celebration-overlay">
                    <div class="celebration-content">
                        <div class="celebration-icon">
                            <span v-html="icons.trophy"></span>
                        </div>
                        <h2 class="celebration-title">Congratulations! 🎉</h2>
                        <p class="celebration-text">
                            <strong>{{ celebrationCandidate }}</strong> has been
                            successfully converted to an employee!
                        </p>
                        <div class="celebration-badge">
                            <span v-html="icons.userCheck"></span>
                            <span>New Employee</span>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Header -->
            <section class="header-card">
                <div class="header-glow"></div>
                <div class="header-content">
                    <div class="header-left">
                        <div class="header-icon">
                            <span v-html="icons.clipboard"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">Interview Evaluation</h1>
                            <p class="header-subtitle">{{ vacancy.title }}</p>
                            <div class="weights-info">
                                <span class="weight-badge"
                                    >📄 Resume
                                    {{
                                        Math.round(weights.resume * 100)
                                    }}%</span
                                >
                                <span class="weight-badge"
                                    >🎤 Interview
                                    {{
                                        Math.round(weights.interview * 100)
                                    }}%</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="header-actions">
                        <Link href="/hr/interviews" class="header-btn">
                            <span
                                class="btn-icon"
                                v-html="icons.arrowLeft"
                            ></span>
                            <span>Back to Interviews</span>
                        </Link>
                        <Link href="/hr/shortlist" class="header-btn">
                            <span class="btn-icon" v-html="icons.users"></span>
                            <span>Shortlist</span>
                        </Link>
                    </div>
                </div>

                <!-- Flash Message -->
                <div v-if="flashMsg" class="flash-message">
                    <span class="flash-icon" v-html="icons.check"></span>
                    <span>{{ flashMsg }}</span>
                </div>
            </section>

            <!-- Workflow Steps -->
            <div class="workflow-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">HR marks <strong>Done</strong></div>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">Set <strong>Panels</strong></div>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Rate with ⭐ (1-5)</div>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-text"><strong>Finalize</strong></div>
                </div>
                <div class="step-arrow">→</div>
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-text">
                        Convert to <strong>Employee</strong>
                    </div>
                </div>
            </div>

            <!-- Candidates -->
            <section v-if="rows.length" class="candidates-grid">
                <article
                    v-for="(row, index) in rows"
                    :key="row.id"
                    class="candidate-card"
                    :class="{ 'card-employee': row.is_employee }"
                    :style="{ '--card-delay': `${index * 0.1}s` }"
                >
                    <!-- Employee Badge -->
                    <div v-if="row.is_employee" class="employee-badge">
                        <span
                            class="badge-icon"
                            v-html="icons.userCheck"
                        ></span>
                        <span>Employee</span>
                        <span v-if="row.employee_no" class="employee-no"
                            >#{{ row.employee_no }}</span
                        >
                    </div>

                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="candidate-info">
                            <div class="candidate-name">
                                {{
                                    row.applicant_name ||
                                    row.anon_name ||
                                    `Candidate #${row.applicant_id}`
                                }}
                            </div>
                            <div
                                v-if="row.applicant_email"
                                class="candidate-email"
                            >
                                {{ row.applicant_email }}
                            </div>

                            <div class="status-row">
                                <span class="status-item">
                                    <strong>Interview:</strong>
                                    {{ row.status || "—" }}
                                </span>
                                <span class="status-divider">•</span>
                                <span class="status-item">
                                    <strong>Candidate:</strong>
                                    {{ row.candidate_status || "pending" }}
                                </span>
                            </div>
                        </div>

                        <button
                            v-if="row.resume_url"
                            @click="openResumeModal(row)"
                            class="resume-btn"
                        >
                            <span class="btn-icon" v-html="icons.file"></span>
                            <span>View Resume</span>
                        </button>
                    </div>

                    <!-- Scores -->
                    <div class="scores-section">
                        <div class="score-card score-resume">
                            <div class="score-label">📄 Resume Score</div>
                            <div class="score-value">
                                {{ Math.round(row.resume_score ?? 0) }}
                            </div>
                        </div>

                        <div
                            v-if="row.interview_score != null"
                            class="score-card score-interview"
                        >
                            <div class="score-label">🎤 Interview Score</div>
                            <div class="score-value">
                                {{ row.interview_score }}
                            </div>
                        </div>

                        <div
                            v-if="row.final_score != null"
                            class="score-card score-final"
                        >
                            <div class="score-label">🏆 Final Score</div>
                            <div class="score-value">{{ row.final_score }}</div>
                        </div>
                    </div>

                    <!-- Lock Notice -->
                    <div v-if="!isUnlocked(row)" class="lock-notice">
                        <span class="lock-icon">🔒</span>
                        <span
                            >Evaluation is locked. Go to
                            <strong>HR Interviews</strong> and click
                            <strong>Mark Done</strong> first.</span
                        >
                    </div>

                    <!-- Panel Management -->
                    <div class="panel-management">
                        <div class="panel-header">
                            <div class="panel-title">
                                <span
                                    class="title-icon"
                                    v-html="icons.users"
                                ></span>
                                <span>Interview Panels</span>
                                <span class="panel-count"
                                    >{{
                                        panelsFor(row.id).length
                                    }}
                                    panel(s)</span
                                >
                            </div>
                            <button
                                @click="openPanelModal(row)"
                                class="panel-btn"
                                :disabled="!isUnlocked(row)"
                            >
                                Set Panels
                            </button>
                        </div>

                        <!-- Panels Grid -->
                        <div
                            v-if="panelsFor(row.id).length"
                            class="panels-grid"
                        >
                            <div
                                v-for="p in panelsFor(row.id)"
                                :key="p.panel_no"
                                class="panel-card"
                            >
                                <div class="panel-info">
                                    <div class="panel-name">
                                        {{ p.name || `Panel ${p.panel_no}` }}
                                    </div>
                                    <div class="panel-no">
                                        Panel #{{ p.panel_no }}
                                    </div>
                                </div>

                                <!-- Star Rating -->
                                <div class="star-rating">
                                    <button
                                        v-for="n in 5"
                                        :key="n"
                                        type="button"
                                        class="star-btn"
                                        :class="{
                                            'star-active':
                                                n <= getRating(row.id, p),
                                            'star-disabled': !isUnlocked(row),
                                        }"
                                        :disabled="!isUnlocked(row)"
                                        @click="
                                            saveRating(
                                                row.id,
                                                p.panel_no,
                                                n,
                                                row,
                                            )
                                        "
                                        :title="`Rate ${n} star${n > 1 ? 's' : ''}`"
                                    >
                                        ★
                                    </button>
                                    <span class="rating-text">
                                        {{
                                            getRating(row.id, p)
                                                ? `${getRating(row.id, p)}/5`
                                                : "No rating"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- No Panels Message -->
                        <div v-else-if="isUnlocked(row)" class="no-panels">
                            <span
                                >No panels set yet. Click "Set Panels" to
                                configure interview panels.</span
                            >
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-actions">
                        <button
                            @click="openFinalizeModal(row)"
                            class="action-btn action-finalize"
                            :disabled="!isUnlocked(row)"
                        >
                            <span
                                class="btn-icon"
                                v-html="icons.clipboard"
                            ></span>
                            <span>Finalize Evaluation</span>
                        </button>

                        <button
                            @click="openHireModal(row)"
                            class="action-btn action-hire"
                            :disabled="!isScored(row) || row.is_employee"
                        >
                            <span
                                class="btn-icon"
                                v-html="icons.sparkles"
                            ></span>
                            <span>{{
                                row.is_employee
                                    ? "Employee Created"
                                    : "Convert to Employee"
                            }}</span>
                        </button>
                    </div>
                </article>
            </section>

            <!-- Empty State -->
            <div v-else class="empty-state">
                <div class="empty-icon">
                    <span v-html="icons.clipboard"></span>
                </div>
                <h3 class="empty-title">No Interviews Found</h3>
                <p class="empty-text">No interviews found for this vacancy</p>
            </div>

            <!-- Set Panels Modal -->
            <transition name="modal">
                <div
                    v-if="showPanelModal"
                    class="modal-backdrop"
                    @click="showPanelModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showPanelModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-info">
                            <span v-html="icons.users"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Set Interview Panels</h3>
                        <p class="modal-text">
                            How many panels/examiners attended this interview?
                        </p>

                        <div class="panel-input-group">
                            <label class="input-label"
                                >Number of Panels (0-20)</label
                            >
                            <input
                                type="number"
                                min="0"
                                max="20"
                                class="panel-input"
                                v-model="panelCountDraft[selectedInterview?.id]"
                                :placeholder="
                                    String(
                                        selectedInterview?.panel_count ??
                                            panelsFor(selectedInterview?.id)
                                                .length ??
                                            0,
                                    )
                                "
                            />
                        </div>

                        <div class="modal-actions">
                            <button
                                @click="showPanelModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmSetPanels"
                                class="modal-btn modal-btn-confirm"
                            >
                                Set Panels
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

                        <div class="modal-icon modal-icon-success">
                            <span v-html="icons.check"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Finalize Evaluation?</h3>
                        <p class="modal-text">
                            This will lock the evaluation and calculate the
                            final score. The interview status will be set to
                            <strong>"Scored"</strong>.
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

            <!-- Hire Modal -->
            <transition name="modal">
                <div
                    v-if="showHireModal"
                    class="modal-backdrop"
                    @click="showHireModal = false"
                >
                    <div class="modal-content modal-celebration" @click.stop>
                        <button
                            class="modal-close"
                            @click="showHireModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-hire">
                            <span v-html="icons.sparkles"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Convert to Employee?</h3>
                        <p class="modal-text">
                            This will create an employee record for
                            <strong>{{
                                selectedInterview?.applicant_name ||
                                selectedInterview?.anon_name
                            }}</strong
                            >. Get ready for a celebration!
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
                                    class="sparkle-icon"
                                    v-html="icons.sparkles"
                                ></span>
                                <span>Convert Now!</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Resume Modal -->
            <transition name="modal">
                <div
                    v-if="showResumeModal"
                    class="modal-backdrop"
                    @click="showResumeModal = false"
                >
                    <div class="modal-content modal-large" @click.stop>
                        <button
                            class="modal-close"
                            @click="showResumeModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="resume-header">
                            <div class="resume-title">
                                <span
                                    class="title-icon"
                                    v-html="icons.file"
                                ></span>
                                <span>{{
                                    selectedResume?.resume_name || "Resume"
                                }}</span>
                            </div>
                            <a
                                v-if="selectedResume?.resume_url"
                                :href="selectedResume.resume_url"
                                target="_blank"
                                class="resume-download"
                            >
                                Open in New Tab →
                            </a>
                        </div>

                        <div class="resume-viewer">
                            <iframe
                                v-if="selectedResume?.resume_url"
                                :src="selectedResume.resume_url"
                                class="resume-iframe"
                            ></iframe>
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
.title-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.title-icon :deep(svg) {
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
.badge-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.sparkle-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    animation: sparkle 1.5s infinite;
}
.sparkle-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
@keyframes sparkle {
    0%,
    100% {
        transform: rotate(0deg) scale(1);
    }
    50% {
        transform: rotate(180deg) scale(1.2);
    }
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

/* Confetti Particles */
.confetti-particle {
    position: fixed;
    width: 10px;
    height: 10px;
    pointer-events: none;
    z-index: 9999;
    animation: confettiFall linear forwards;
}
@keyframes confettiFall {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
    }
}

/* Celebration Overlay */
.celebration-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.95),
        rgba(6, 182, 212, 0.95)
    );
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.celebration-content {
    text-align: center;
    max-width: 600px;
    animation: celebrationPop 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes celebrationPop {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
.celebration-icon {
    width: 120px;
    height: 120px;
    margin: 0 auto 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    color: white;
    animation: celebrationIconSpin 2s ease-in-out infinite;
}
@keyframes celebrationIconSpin {
    0%,
    100% {
        transform: rotate(0deg) scale(1);
    }
    50% {
        transform: rotate(360deg) scale(1.1);
    }
}
.celebration-icon :deep(svg) {
    width: 64px;
    height: 64px;
}
.celebration-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 3rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    animation: celebrationTitleBounce 1s ease-in-out infinite;
}
@keyframes celebrationTitleBounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}
.celebration-text {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.6;
    margin-bottom: 2rem;
}
.celebration-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: white;
    border-radius: 50px;
    font-size: 1.125rem;
    font-weight: 700;
    color: #10b981;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: celebrationBadgePulse 1.5s ease-in-out infinite;
}
@keyframes celebrationBadgePulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}
.celebration-badge :deep(svg) {
    width: 24px;
    height: 24px;
}

.celebration-enter-active {
    animation: celebrationIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.celebration-leave-active {
    animation: celebrationOut 0.4s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes celebrationIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
@keyframes celebrationOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
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
    font-weight: 500;
    margin: 0.25rem 0 0.75rem;
}
.weights-info {
    display: flex;
    gap: 0.5rem;
}
.weight-badge {
    padding: 0.375rem 0.875rem;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0ea5e9;
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

/* Workflow Steps */
.workflow-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.5rem;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
}
.step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.step-number {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.875rem;
}
.step-text {
    font-size: 0.875rem;
    color: #64748b;
}
.step-arrow {
    font-size: 1.5rem;
    color: #cbd5e1;
}

/* Candidates Grid */
.candidates-grid {
    display: grid;
    gap: 2rem;
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

.card-employee {
    border-color: rgba(59, 130, 246, 0.3);
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(59, 130, 246, 0.02) 100%
    );
}
.card-employee::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), transparent);
    pointer-events: none;
}

.employee-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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
.employee-no {
    padding: 0.25rem 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    font-size: 0.75rem;
}

/* Card Header */
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
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.candidate-email {
    font-size: 0.9375rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}
.status-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
}
.status-divider {
    color: #cbd5e1;
}

.resume-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.3s;
}
.resume-btn:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    transform: translateY(-2px);
}

/* Scores */
.scores-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.score-card {
    padding: 1.25rem;
    border-radius: 12px;
    text-align: center;
}
.score-resume {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.score-interview {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.1),
        rgba(37, 99, 235, 0.05)
    );
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.score-final {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.1),
        rgba(124, 58, 237, 0.05)
    );
    border: 1px solid rgba(139, 92, 246, 0.3);
}
.score-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.5rem;
}
.score-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #0ea5e9, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
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

/* Panel Management */
.panel-management {
    margin-bottom: 1.5rem;
}
.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}
.panel-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
}
.panel-count {
    padding: 0.25rem 0.75rem;
    background: rgba(6, 182, 212, 0.1);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #0ea5e9;
}
.panel-btn {
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}
.panel-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.panel-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.panels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}
.panel-card {
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
}
.panel-info {
    margin-bottom: 1rem;
}
.panel-name {
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.25rem;
}
.panel-no {
    font-size: 0.8125rem;
    color: #64748b;
}

/* Star Rating */
.star-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.star-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #cbd5e1;
    background: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    padding: 0;
}
.star-btn:hover:not(.star-disabled) {
    transform: scale(1.2);
}
.star-active {
    color: #f59e0b;
    animation: starPop 0.3s ease-out;
}
@keyframes starPop {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.3);
    }
    100% {
        transform: scale(1);
    }
}
.star-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}
.rating-text {
    font-size: 0.875rem;
    color: #64748b;
    margin-left: 0.5rem;
}

.no-panels {
    padding: 1.5rem;
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
    background: rgba(248, 250, 252, 0.8);
    border: 1px dashed rgba(148, 163, 184, 0.3);
    border-radius: 12px;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}
.action-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}
.action-finalize {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-finalize:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.action-hire {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    position: relative;
    overflow: hidden;
}
.action-hire::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(
        45deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: shimmer 3s infinite;
}
@keyframes shimmer {
    0% {
        transform: translateX(-100%) translateY(-100%) rotate(45deg);
    }
    100% {
        transform: translateX(100%) translateY(100%) rotate(45deg);
    }
}
.action-hire:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}
.action-finalize:disabled,
.action-hire:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
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
    max-width: 90vw;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.modal-celebration {
    border: 3px solid transparent;
    background:
        linear-gradient(white, white) padding-box,
        linear-gradient(135deg, #10b981, #3b82f6, #8b5cf6) border-box;
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
.modal-icon-info {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    color: #3b82f6;
}
.modal-icon-hire {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(124, 58, 237, 0.15)
    );
    color: #8b5cf6;
    animation: modalIconSpin 3s ease-in-out infinite;
}
@keyframes modalIconSpin {
    0%,
    100% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(15deg);
    }
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

.panel-input-group {
    margin-bottom: 2rem;
}
.input-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.panel-input {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 1.125rem;
    text-align: center;
    color: #0f172a;
    transition: all 0.3s;
    font-weight: 700;
}
.panel-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
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
.modal-btn-hire {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.modal-btn-hire:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
}

/* Resume Viewer */
.resume-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}
.resume-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
}
.resume-download {
    color: #0ea5e9;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
}
.resume-download:hover {
    text-decoration: underline;
}
.resume-viewer {
    flex: 1;
    min-height: 0;
}
.resume-iframe {
    width: 100%;
    height: 70vh;
    border: none;
    border-radius: 12px;
    background: #f8fafc;
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
        flex-wrap: wrap;
    }
    .workflow-steps {
        justify-content: flex-start;
    }
    .panels-grid {
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
    .weights-info {
        flex-direction: column;
    }

    .candidate-card {
        padding: 1.5rem;
    }
    .card-header {
        flex-direction: column;
    }
    .scores-section {
        grid-template-columns: 1fr;
    }
    .panel-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .card-actions {
        flex-direction: column;
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
}
</style>
