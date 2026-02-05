<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    v: { type: Object, required: true },
    myApplication: { type: Object, default: null },
    alreadyApplied: { type: Boolean, default: false },
});

// Modal state
const showConfirmModal = ref(false);

// --- Actions ---
function openApplyModal() {
    showConfirmModal.value = true;
}

function closeApplyModal() {
    showConfirmModal.value = false;
}

function confirmApply() {
    showConfirmModal.value = false;
    router.post("/applications", { vacancy_id: props.v.id });
}

function apply() {
    openApplyModal();
}

function withdraw() {
    if (!props.myApplication?.id) return;
    if (!confirm("Withdraw this application?")) return;

    router.delete(`/applications/${props.myApplication.id}`, {
        preserveScroll: true,
    });
}

// --- Helpers ---
const status = () => props.myApplication?.status || null;

const employmentLabel = () => {
    const t = (props.v.employment_type || props.v.type || "").toLowerCase();
    if (t === "permanent") return "Permanent";
    if (t === "contract") return "Contract";
    if (t === "intern" || t === "internship") return "Internship";
    return t ? t.charAt(0).toUpperCase() + t.slice(1) : "Not specified";
};

const experienceLabel = () => {
    const v = props.v || {};
    const min = v.experience_min_years ?? v.experience_years_required ?? null;
    const max = v.experience_max_years ?? null;

    if (min !== null && max !== null) {
        if (max > min) return `${min}–${max} years`;
        if (max === min) return `${min} years`;
    }
    if (min !== null) return `${min}+ years`;
    if (max !== null) return `Up to ${max} years`;
    return "Not specified";
};

const educationLabel = () => {
    const v = props.v || {};
    if (!v.education_required || String(v.education_required).trim() === "") {
        return "Not specified";
    }
    return v.education_required;
};

const skillsList = () => {
    const s = props.v.skills_required;
    if (!s) return [];
    if (Array.isArray(s)) return s;
    if (typeof s === "string") {
        try {
            const parsed = JSON.parse(s);
            if (Array.isArray(parsed)) return parsed;
        } catch (e) {}
        return String(s)
            .split(/[,;\n]/)
            .map((x) => x.trim().replace(/^"|"$/g, ""))
            .filter(Boolean);
    }
    return [];
};

const statusBadgeClass = () => {
    const s = (props.v.status || "").toLowerCase();
    if (s === "open") return "status-open";
    if (s === "closed") return "status-closed";
    if (s === "archived") return "status-archived";
    return "status-neutral";
};

const appStatusLabel = () => {
    const s = (status() || "").toLowerCase();
    if (!s) return "Not applied";
    if (s === "submitted") return "Submitted";
    if (s === "in_review") return "In review";
    if (s === "shortlisted") return "Shortlisted";
    if (s === "rejected") return "Rejected";
    if (s === "withdrawn") return "Withdrawn";
    if (s === "hired") return "Hired";
    return s;
};

const descriptionBlocks = computed(() => {
    const raw = props.v.description || "";
    return raw
        .split(/\r?\n\s*\r?\n/)
        .map((p) => p.trim())
        .filter((p) => p.length > 0);
});

// 🔒 Withdraw rules
const lockedStatuses = [
    "shortlisted",
    "interview_scheduled",
    "interview_invited",
    "interview",
    "in_interview",
    "offer",
    "offered",
    "hired",
];

const canWithdraw = computed(() => {
    const s = (status() || "").toLowerCase();
    if (!s) return false;
    if (["withdrawn", "rejected", "hired"].includes(s)) return false;
    return !lockedStatuses.includes(s);
});

const withdrawLockedReason = computed(() => {
    const s = (status() || "").toLowerCase();
    if (!s) return "";
    if (lockedStatuses.includes(s)) {
        return "You cannot withdraw once an interview has been scheduled or the hiring decision is in progress.";
    }
    if (["rejected", "hired"].includes(s)) {
        return "This application is already closed.";
    }
    return "";
});

// Icons
const icons = {
    back: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>`,
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    location: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    clock: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>`,
    award: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.11"/></svg>`,
    book: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    info: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>`,
    alert: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>`,
    sparkles: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>`,
    send: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>`,
    close: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
};
</script>

<template>
    <Head
        :title="
            props.v?.title ? `${props.v.title} – Job Details` : 'Job Details'
        "
    />
    <ApplicantLayout :show-sidebar="false" content-max="max-w-6xl">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb">
            <Link href="/jobs" class="breadcrumb-link">
                <span class="breadcrumb-icon" v-html="icons.back"></span>
                <span>Back to Jobs</span>
            </Link>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">{{
                props.v.title || "Job Details"
            }}</span>
        </nav>

        <!-- Application Status Banner -->
        <section v-if="alreadyApplied || status()" class="status-banner">
            <div class="banner-glow"></div>
            <div class="banner-content">
                <div class="banner-left">
                    <span class="banner-icon" v-html="icons.check"></span>
                    <div>
                        <div class="banner-label">Application Status</div>
                        <div class="banner-status">{{ appStatusLabel() }}</div>
                    </div>
                </div>
                <div class="banner-actions">
                    <button
                        v-if="status() === 'withdrawn'"
                        class="banner-btn banner-btn-primary"
                        @click="apply"
                    >
                        Re-apply
                    </button>
                    <button
                        v-else-if="alreadyApplied && canWithdraw"
                        class="banner-btn banner-btn-secondary"
                        @click="withdraw"
                    >
                        Withdraw
                    </button>
                    <button
                        v-else-if="alreadyApplied && !canWithdraw"
                        class="banner-btn banner-btn-disabled"
                        type="button"
                        :title="withdrawLockedReason || 'Cannot withdraw'"
                        disabled
                    >
                        Withdraw Disabled
                    </button>
                </div>
            </div>
        </section>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Left Column - Job Details -->
            <article class="job-details">
                <div class="details-glow"></div>

                <!-- Header -->
                <header class="job-header">
                    <div class="header-top">
                        <div class="header-icon" v-html="icons.briefcase"></div>
                        <span class="status-badge" :class="statusBadgeClass()">
                            <span class="badge-pulse"></span>
                            <span>{{ props.v.status || "Open" }}</span>
                        </span>
                    </div>

                    <h1 class="job-title">
                        {{ props.v.title || "Untitled Role" }}
                    </h1>

                    <div class="job-meta">
                        <div class="meta-item">
                            <span
                                class="meta-icon"
                                v-html="icons.briefcase"
                            ></span>
                            <span>{{ props.v.department || "—" }}</span>
                        </div>
                        <div class="meta-item">
                            <span
                                class="meta-icon"
                                v-html="icons.location"
                            ></span>
                            <span>{{ props.v.location || "—" }}</span>
                        </div>
                        <div class="meta-item" v-if="props.v.deadline">
                            <span
                                class="meta-icon"
                                v-html="icons.calendar"
                            ></span>
                            <span>Apply by {{ props.v.deadline }}</span>
                        </div>
                    </div>
                </header>

                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card">
                        <div class="stat-icon" v-html="icons.clock"></div>
                        <div class="stat-content">
                            <div class="stat-label">Experience</div>
                            <div class="stat-value">
                                {{ experienceLabel() }}
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" v-html="icons.award"></div>
                        <div class="stat-content">
                            <div class="stat-label">Education</div>
                            <div class="stat-value">{{ educationLabel() }}</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon" v-html="icons.briefcase"></div>
                        <div class="stat-content">
                            <div class="stat-label">Type</div>
                            <div class="stat-value">
                                {{ employmentLabel() }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Job Description -->
                <section class="description-section">
                    <h2 class="section-title">
                        <span class="title-icon" v-html="icons.book"></span>
                        <span>Job Description</span>
                    </h2>

                    <div v-if="!props.v.description" class="description-empty">
                        <span class="empty-icon" v-html="icons.info"></span>
                        <span>No description provided</span>
                    </div>

                    <div v-else class="description-content">
                        <p
                            v-for="(para, idx) in descriptionBlocks"
                            :key="idx"
                            class="description-paragraph"
                        >
                            {{ para }}
                        </p>
                    </div>
                </section>

                <!-- Skills Section -->
                <section v-if="skillsList().length" class="skills-section">
                    <h2 class="section-title">
                        <span class="title-icon" v-html="icons.sparkles"></span>
                        <span>Required Skills</span>
                    </h2>

                    <div class="skills-grid">
                        <span
                            v-for="(skill, idx) in skillsList()"
                            :key="idx"
                            class="skill-tag"
                        >
                            {{ skill }}
                        </span>
                    </div>
                </section>
            </article>

            <!-- Right Column - Sidebar -->
            <aside class="job-sidebar">
                <div class="sidebar-glow"></div>

                <!-- Apply Card -->
                <div class="apply-card">
                    <div class="apply-header">
                        <h3 class="apply-title">Ready to Apply?</h3>
                        <p class="apply-subtitle">
                            Your application will be reviewed by our team
                        </p>
                    </div>

                    <div class="apply-actions">
                        <!-- Re-apply -->
                        <button
                            v-if="status() === 'withdrawn'"
                            class="apply-btn apply-btn-primary"
                            @click="apply"
                        >
                            <span>Re-apply for this Role</span>
                        </button>

                        <!-- Already applied -->
                        <template v-else-if="alreadyApplied">
                            <button
                                class="apply-btn apply-btn-applied"
                                disabled
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>Application Submitted</span>
                            </button>
                            <button
                                v-if="canWithdraw"
                                class="apply-btn apply-btn-secondary"
                                @click="withdraw"
                            >
                                Withdraw Application
                            </button>
                            <div
                                v-else
                                class="withdraw-locked"
                                :title="withdrawLockedReason"
                            >
                                <span
                                    class="locked-icon"
                                    v-html="icons.alert"
                                ></span>
                                <span class="locked-text">{{
                                    withdrawLockedReason
                                }}</span>
                            </div>
                        </template>

                        <!-- First-time apply -->
                        <button
                            v-else
                            class="apply-btn apply-btn-primary"
                            @click="apply"
                        >
                            <span>Apply Now</span>
                        </button>
                    </div>
                </div>

                <!-- Job Details Card -->
                <div class="details-card">
                    <h3 class="card-title">Job Details</h3>

                    <dl class="details-list">
                        <div class="detail-item">
                            <dt class="detail-label">Department</dt>
                            <dd class="detail-value">
                                {{ props.v.department || "—" }}
                            </dd>
                        </div>

                        <div class="detail-item">
                            <dt class="detail-label">Location</dt>
                            <dd class="detail-value">
                                {{ props.v.location || "—" }}
                            </dd>
                        </div>

                        <div class="detail-item">
                            <dt class="detail-label">Employment Type</dt>
                            <dd class="detail-value">
                                {{ employmentLabel() }}
                            </dd>
                        </div>

                        <div class="detail-item">
                            <dt class="detail-label">Experience Required</dt>
                            <dd class="detail-value">
                                {{ experienceLabel() }}
                            </dd>
                        </div>

                        <div class="detail-item">
                            <dt class="detail-label">Education</dt>
                            <dd class="detail-value">{{ educationLabel() }}</dd>
                        </div>

                        <div v-if="props.v.deadline" class="detail-item">
                            <dt class="detail-label">Application Deadline</dt>
                            <dd class="detail-value deadline-value">
                                {{ props.v.deadline }}
                            </dd>
                        </div>

                        <div v-if="props.v.created_at" class="detail-item">
                            <dt class="detail-label">Posted</dt>
                            <dd class="detail-value">
                                {{ props.v.created_at }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Links -->
                <div class="links-card">
                    <Link href="/applications" class="quick-link">
                        <span>View My Applications</span>
                        <span class="link-arrow">→</span>
                    </Link>
                    <Link href="/jobs" class="quick-link">
                        <span>Browse More Jobs</span>
                        <span class="link-arrow">→</span>
                    </Link>
                </div>
            </aside>
        </div>

        <!-- Confirmation Modal -->
        <teleport to="body">
            <transition name="modal-fade">
                <div
                    v-if="showConfirmModal"
                    class="modal-overlay"
                    @click="closeApplyModal"
                >
                    <div class="modal-container" @click.stop>
                        <div class="modal-glow"></div>

                        <!-- Modal Header -->
                        <div class="modal-header">
                            <div class="modal-icon">
                                <span v-html="icons.send"></span>
                                <div class="icon-pulse"></div>
                            </div>
                            <button
                                type="button"
                                class="modal-close"
                                @click="closeApplyModal"
                                aria-label="Close modal"
                            >
                                <span v-html="icons.close"></span>
                            </button>
                        </div>

                        <!-- Modal Content -->
                        <div class="modal-content">
                            <h3 class="modal-title">Confirm Application</h3>
                            <p class="modal-description">
                                You're about to apply for this position:
                            </p>

                            <div class="modal-job-info">
                                <div
                                    class="job-info-icon"
                                    v-html="icons.briefcase"
                                ></div>
                                <div>
                                    <div class="job-info-title">
                                        {{ props.v.title }}
                                    </div>
                                    <div class="job-info-meta">
                                        {{ props.v.department || "—" }} •
                                        {{ props.v.location || "—" }}
                                    </div>
                                </div>
                            </div>

                            <div class="modal-notice">
                                <span
                                    class="notice-icon"
                                    v-html="icons.info"
                                ></span>
                                <span
                                    >Your resume and profile will be submitted
                                    to the hiring team.</span
                                >
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="modal-actions">
                            <button
                                type="button"
                                class="modal-btn modal-btn-cancel"
                                @click="closeApplyModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="modal-btn modal-btn-confirm"
                                @click="confirmApply"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.send"
                                ></span>
                                <span>Confirm Application</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </ApplicantLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables */
:root {
    --color-primary: #0ea5e9;
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-text: #0f172a;
    --color-text-light: #64748b;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 2rem;
    font-size: 0.9375rem;
}

.breadcrumb-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.breadcrumb-link:hover {
    gap: 0.75rem;
}

.breadcrumb-icon {
    width: 16px;
    height: 16px;
}

.breadcrumb-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.breadcrumb-separator {
    color: var(--color-text-light);
}

.breadcrumb-current {
    color: var(--color-text-light);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Status Banner */
.status-banner {
    position: relative;
    margin-bottom: 2rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 16px;
    overflow: hidden;
}

.banner-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top left,
        rgba(16, 185, 129, 0.2) 0%,
        transparent 60%
    );
    pointer-events: none;
}

.banner-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.banner-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.banner-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(16, 185, 129, 0.2);
    border-radius: 10px;
    color: #065f46;
}

.banner-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.banner-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #065f46;
}

.banner-status {
    font-size: 1.125rem;
    font-weight: 700;
    color: #065f46;
}

.banner-actions {
    display: flex;
    gap: 0.75rem;
}

.banner-btn {
    padding: 0.625rem 1.25rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.banner-btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.banner-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.banner-btn-secondary {
    background: white;
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.banner-btn-secondary:hover {
    background: rgba(16, 185, 129, 0.05);
}

.banner-btn-disabled {
    background: rgba(148, 163, 184, 0.1);
    color: var(--color-text-light);
    border: 1px solid rgba(148, 163, 184, 0.2);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

/* Job Details */
.job-details {
    position: relative;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.details-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(6, 182, 212, 0.05) 0%,
        transparent 60%
    );
    border-radius: 20px;
    pointer-events: none;
}

/* Job Header */
.job-header {
    position: relative;
    z-index: 1;
    margin-bottom: 2rem;
}

.header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.header-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 16px;
    color: var(--color-primary);
}

.header-icon :deep(svg) {
    width: 28px;
    height: 28px;
}

.status-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-open {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #065f46;
}

.status-closed {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #991b1b;
}

.status-archived {
    background: linear-gradient(
        135deg,
        rgba(148, 163, 184, 0.15),
        rgba(100, 116, 139, 0.15)
    );
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #475569;
}

.status-neutral {
    background: linear-gradient(
        135deg,
        rgba(148, 163, 184, 0.15),
        rgba(100, 116, 139, 0.15)
    );
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #475569;
}

.badge-pulse {
    width: 6px;
    height: 6px;
    background: currentColor;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

.job-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--color-text);
    line-height: 1.2;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    color: var(--color-text-light);
}

.meta-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.meta-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Quick Stats */
.quick-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    background: white;
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 10px;
    color: var(--color-primary);
}

.stat-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-value {
    font-size: 0.9375rem;
    font-weight: 700;
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Sections */
.description-section,
.skills-section {
    margin-bottom: 2rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 1.25rem;
}

.title-icon {
    width: 24px;
    height: 24px;
    color: var(--color-primary);
}

.title-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.description-empty {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1.5rem;
    background: rgba(148, 163, 184, 0.05);
    border: 1px dashed rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    color: var(--color-text-light);
    font-size: 0.9375rem;
}

.empty-icon {
    width: 20px;
    height: 20px;
}

.empty-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.description-content {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--color-text-light);
}

.description-paragraph {
    margin-bottom: 1.25rem;
    white-space: pre-line;
}

.description-paragraph:last-child {
    margin-bottom: 0;
}

/* Skills */
.skills-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.skill-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
    transition: all 0.3s ease;
}

.skill-tag:hover {
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.15),
        rgba(14, 165, 233, 0.15)
    );
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

/* Sidebar */
.job-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Apply Card */
.apply-card {
    position: relative;
    padding: 1.75rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

.sidebar-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top,
        rgba(16, 185, 129, 0.1) 0%,
        transparent 60%
    );
    pointer-events: none;
}

.apply-header {
    position: relative;
    z-index: 1;
    margin-bottom: 1.5rem;
}

.apply-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.apply-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.apply-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.apply-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.apply-btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.apply-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

.apply-btn-applied {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
    cursor: not-allowed;
}

.apply-btn-secondary {
    background: white;
    color: var(--color-text);
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.apply-btn-secondary:hover {
    background: rgba(248, 250, 252, 0.8);
    border-color: var(--color-text-light);
}

.btn-icon {
    width: 18px;
    height: 18px;
}

.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.withdraw-locked {
    padding: 1rem;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    border-radius: 10px;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.locked-icon {
    width: 20px;
    height: 20px;
    color: #92400e;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.locked-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.locked-text {
    font-size: 0.8125rem;
    color: #92400e;
    line-height: 1.5;
}

/* Details Card */
.details-card {
    padding: 1.5rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 16px;
}

.card-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 1.25rem;
}

.details-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.detail-item:last-child {
    padding-bottom: 0;
    border-bottom: none;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-text);
}

.deadline-value {
    color: var(--color-warning);
}

/* Links Card */
.links-card {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
}

.quick-link:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: var(--color-primary);
    transform: translateX(4px);
}

.link-arrow {
    font-size: 1.25rem;
    color: var(--color-primary);
    transition: transform 0.3s ease;
}

.quick-link:hover .link-arrow {
    transform: translateX(4px);
}

/* Responsive */
@media (max-width: 1280px) {
    .main-grid {
        gap: 1.5rem;
    }

    .job-details {
        padding: 1.75rem;
    }
}

@media (max-width: 1024px) {
    .main-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .job-sidebar {
        order: -1;
    }

    .apply-card {
        padding: 1.5rem;
    }

    .quick-stats {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .breadcrumb {
        font-size: 0.875rem;
        margin-bottom: 1.5rem;
    }

    .breadcrumb-current {
        max-width: 200px;
    }

    .status-banner {
        padding: 1rem 1.25rem;
    }

    .banner-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .banner-left {
        width: 100%;
    }

    .banner-actions {
        width: 100%;
        flex-wrap: wrap;
    }

    .banner-btn {
        flex: 1;
        min-width: 120px;
    }

    .job-details {
        padding: 1.5rem;
    }

    .header-icon {
        width: 48px;
        height: 48px;
    }

    .header-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }

    .job-title {
        font-size: 1.75rem;
    }

    .job-meta {
        gap: 1rem;
    }

    .quick-stats {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .stat-card {
        padding: 0.875rem;
    }

    .section-title {
        font-size: 1.125rem;
    }

    .description-content {
        font-size: 0.9375rem;
    }

    .apply-card {
        padding: 1.25rem;
    }

    .apply-title {
        font-size: 1.125rem;
    }

    .details-card {
        padding: 1.25rem;
    }
}

@media (max-width: 640px) {
    .breadcrumb {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .breadcrumb-current {
        max-width: 150px;
        flex-basis: 100%;
    }

    .breadcrumb-separator {
        display: none;
    }

    .status-banner {
        padding: 1rem;
    }

    .banner-icon {
        width: 36px;
        height: 36px;
    }

    .banner-icon :deep(svg) {
        width: 18px;
        height: 18px;
    }

    .banner-label {
        font-size: 0.8125rem;
    }

    .banner-status {
        font-size: 1rem;
    }

    .banner-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .banner-btn {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }

    .job-details {
        padding: 1.25rem;
    }

    .header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-icon {
        width: 44px;
        height: 44px;
    }

    .header-icon :deep(svg) {
        width: 22px;
        height: 22px;
    }

    .status-badge {
        align-self: flex-start;
    }

    .job-title {
        font-size: 1.5rem;
    }

    .job-meta {
        flex-direction: column;
        gap: 0.75rem;
    }

    .meta-item {
        font-size: 0.875rem;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
    }

    .stat-icon :deep(svg) {
        width: 18px;
        height: 18px;
    }

    .stat-label {
        font-size: 0.6875rem;
    }

    .stat-value {
        font-size: 0.875rem;
    }

    .section-title {
        font-size: 1rem;
        gap: 0.5rem;
    }

    .title-icon {
        width: 20px;
        height: 20px;
    }

    .description-content {
        font-size: 0.875rem;
        line-height: 1.6;
    }

    .description-paragraph {
        margin-bottom: 1rem;
    }

    .skill-tag {
        padding: 0.375rem 0.875rem;
        font-size: 0.8125rem;
    }

    .apply-card,
    .details-card {
        padding: 1rem;
    }

    .apply-title {
        font-size: 1rem;
    }

    .apply-subtitle {
        font-size: 0.8125rem;
    }

    .apply-btn {
        padding: 0.875rem 1.25rem;
        font-size: 0.9375rem;
    }

    .card-title {
        font-size: 0.9375rem;
    }

    .detail-item {
        padding-bottom: 0.75rem;
        gap: 0.375rem;
    }

    .detail-label {
        font-size: 0.6875rem;
    }

    .detail-value {
        font-size: 0.875rem;
    }

    .quick-link {
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
    }

    .withdraw-locked {
        padding: 0.875rem;
    }

    .locked-icon {
        width: 18px;
        height: 18px;
    }

    .locked-text {
        font-size: 0.75rem;
    }
}

@media (max-width: 480px) {
    .breadcrumb {
        font-size: 0.8125rem;
    }

    .breadcrumb-icon {
        width: 14px;
        height: 14px;
    }

    .job-title {
        font-size: 1.375rem;
    }

    .quick-stats {
        gap: 0.625rem;
    }

    .stat-card {
        padding: 0.75rem;
        gap: 0.75rem;
    }

    .skills-grid {
        gap: 0.5rem;
    }

    .skill-tag {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }
}

/* Landscape mobile phones */
@media (max-width: 896px) and (max-height: 414px) and (orientation: landscape) {
    .status-banner {
        padding: 0.875rem 1rem;
    }

    .banner-content {
        flex-direction: row;
        align-items: center;
    }

    .banner-actions {
        flex-direction: row;
        width: auto;
    }

    .job-details {
        padding: 1.25rem;
    }

    .job-title {
        font-size: 1.5rem;
    }

    .quick-stats {
        grid-template-columns: repeat(3, 1fr);
    }

    .main-grid {
        grid-template-columns: 1fr 360px;
    }

    .job-sidebar {
        order: 0;
    }
}

/* Tablet landscape */
@media (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {
    .main-grid {
        grid-template-columns: 1fr 380px;
    }

    .job-sidebar {
        order: 0;
    }
}

/* Very small devices */
@media (max-width: 360px) {
    .hero-title {
        font-size: 1.25rem;
    }

    .job-title {
        font-size: 1.25rem;
    }

    .stat-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .apply-btn {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }

    .modal-title {
        font-size: 1.375rem;
    }

    .job-info-title {
        font-size: 1rem;
    }
}

/* Confirmation Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(8px);
    padding: 1rem;
}

.modal-container {
    position: relative;
    width: 100%;
    max-width: 500px;
    background: white;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(100px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-glow {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 200px;
    background: radial-gradient(
        circle at top,
        rgba(16, 185, 129, 0.15) 0%,
        transparent 70%
    );
    pointer-events: none;
}

/* Modal Header */
.modal-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem 2rem 1rem;
}

.modal-icon {
    position: relative;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border-radius: 16px;
    color: var(--color-success);
}

.modal-icon :deep(svg) {
    width: 32px;
    height: 32px;
    position: relative;
    z-index: 1;
}

.icon-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 16px;
    border: 2px solid rgba(16, 185, 129, 0.4);
    animation: iconPulse 2s ease-in-out infinite;
}

@keyframes iconPulse {
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

.modal-close {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.1);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 10px;
    color: var(--color-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
    color: var(--color-danger);
    transform: rotate(90deg);
}

.modal-close :deep(svg) {
    width: 18px;
    height: 18px;
}

/* Modal Content */
.modal-content {
    padding: 0 2rem 2rem;
}

.modal-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    letter-spacing: -0.02em;
}

.modal-description {
    font-size: 1rem;
    color: var(--color-text-light);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.modal-job-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 16px;
    margin-bottom: 1.5rem;
}

.job-info-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 12px;
    color: var(--color-primary);
}

.job-info-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.job-info-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.25rem;
}

.job-info-meta {
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.modal-notice {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(6, 182, 212, 0.05);
    border: 1px solid rgba(6, 182, 212, 0.2);
    border-radius: 12px;
    font-size: 0.875rem;
    color: var(--color-text-light);
    line-height: 1.5;
}

.notice-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    color: var(--color-primary);
    margin-top: 0.125rem;
}

.notice-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Modal Actions */
.modal-actions {
    display: flex;
    gap: 1rem;
    padding: 1.5rem 2rem 2rem;
    border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.modal-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-btn-cancel {
    background: white;
    color: var(--color-text);
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.modal-btn-cancel:hover {
    background: rgba(248, 250, 252, 0.8);
    border-color: var(--color-text-light);
}

.modal-btn-confirm {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.modal-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

/* Modal Transitions */
.modal-fade-enter-active {
    transition: opacity 0.3s ease;
}

.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .modal-container {
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-leave-active .modal-container {
    animation: modalSlideDown 0.3s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes modalSlideDown {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(100px) scale(0.9);
    }
}

@media (max-width: 640px) {
    .modal-container {
        max-width: calc(100vw - 2rem);
    }

    .modal-header,
    .modal-content {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }

    .modal-actions {
        padding: 1.5rem;
        flex-direction: column;
    }

    .modal-title {
        font-size: 1.5rem;
    }
}
</style>
