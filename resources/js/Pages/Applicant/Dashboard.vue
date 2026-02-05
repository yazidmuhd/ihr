<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    user: { type: Object, default: () => null },
    welcome: { type: String, default: "Welcome to your i-HR dashboard!" },
    stats: {
        type: Object,
        default: () => ({ applications: 0, interviews: 0, hasResume: false }),
    },
    byStatus: {
        type: Object,
        default: () => ({ labels: [], values: [] }),
    },
});

const displayName = computed(
    () => props.user?.name || props.user?.email || "there",
);

// 🧮 Make counts robust even if backend uses slightly different keys
const totalApplications = computed(() => {
    const s = props.stats || {};
    return Number(
        s.applications ?? s.applications_count ?? s.total_applications ?? 0,
    );
});

const totalInterviews = computed(() => {
    const s = props.stats || {};
    return Number(
        s.interviews ?? s.interviews_count ?? s.total_interviews ?? 0,
    );
});

const hasResume = computed(() => {
    const s = props.stats || {};
    return Boolean(s.hasResume ?? s.has_resume ?? s.resume_active ?? false);
});

// 🔔 pull notifications (pending interview confirmations)
const page = usePage();
const notifications = computed(() => page.props?.notifications || {});
const pendingInterviewCount = computed(
    () => notifications.value.applicant?.pending_interviews || 0,
);

const icons = {
    hand: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 22a5 5 0 0 1-3.5-1.5L0 17l1.4-1.4c.4-.4 1-.4 1.4 0l2.7 2.7a3 3 0 0 0 4.2 0c.6-.6.6-1.5 0-2.1L4.3 10.8c-.6-.6-.6-1.5 0-2.1.6-.6 1.5-.6 2.1 0l5.4 5.4c.6.6 1.5.6 2.1 0 .6-.6.6-1.5 0-2.1L8.5 6.6c-.6-.6-.6-1.5 0-2.1.6-.6 1.5-.6 2.1 0l5.4 5.4c.6.6 1.5.6 2.1 0 .6-.6.6-1.5 0-2.1L13 2.7c-.6-.6-.6-1.5 0-2.1.6-.6 1.5-.6 2.1 0l8.5 8.5c2 2 2 5.1 0 7.1L17 23z"/></svg>`,
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    inbox: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 14h5l2 3h4l2-3h5"/><path d="M3 14V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    document: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>`,
    search: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    alert: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>`,
    sparkle: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>`,
    arrow: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`,
    user: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    trending: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>`,
};
</script>

<template>
    <Head title="Dashboard" />
    <ApplicantLayout :show-sidebar="false" content-max="max-w-7xl">
        <div class="applicant-dashboard">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-content">
                    <div class="welcome-badge">
                        <span class="badge-icon" v-html="icons.sparkle"></span>
                        <span>Your Career Portal</span>
                    </div>

                    <h1 class="hero-title">
                        Welcome back,
                        <span class="gradient-text">{{ displayName }}</span
                        >!
                    </h1>

                    <p class="hero-subtitle">
                        {{ props.welcome }}
                    </p>

                    <!-- Interview Alert -->
                    <div v-if="pendingInterviewCount" class="alert-banner">
                        <div class="alert-pulse"></div>
                        <div class="alert-content">
                            <span
                                class="alert-icon"
                                v-html="icons.alert"
                            ></span>
                            <div class="alert-text">
                                <span class="alert-title"
                                    >Action Required!</span
                                >
                                <span class="alert-message">
                                    You have
                                    <strong>{{ pendingInterviewCount }}</strong>
                                    interview{{
                                        pendingInterviewCount === 1 ? "" : "s"
                                    }}
                                    waiting for confirmation
                                </span>
                            </div>
                        </div>
                        <Link href="/app/interviews" class="alert-button">
                            <span>Review Now</span>
                            <span
                                class="button-icon"
                                v-html="icons.arrow"
                            ></span>
                        </Link>
                    </div>
                </div>

                <div class="hero-actions">
                    <Link
                        href="/applicant/jobs"
                        class="hero-button hero-button-primary"
                    >
                        <span class="button-icon" v-html="icons.search"></span>
                        <span>Browse Jobs</span>
                        <div class="button-glow"></div>
                    </Link>
                    <Link
                        href="/profile"
                        class="hero-button hero-button-secondary"
                    >
                        <span class="button-icon" v-html="icons.user"></span>
                        <span>Edit Profile</span>
                    </Link>
                </div>
            </section>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <!-- Applications Card -->
                <div class="stat-card">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="stat-header">
                        <div class="stat-icon stat-icon-blue">
                            <span v-html="icons.inbox"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div class="stat-badge">
                            <span v-html="icons.trending"></span>
                            <span>Active</span>
                        </div>
                    </div>

                    <div class="stat-body">
                        <div class="stat-value">{{ totalApplications }}</div>
                        <div class="stat-label">Applications</div>
                        <div class="stat-description">
                            Total applications you have submitted
                        </div>
                    </div>

                    <Link href="/applications" class="stat-link">
                        <span>View Applications</span>
                        <span class="link-icon" v-html="icons.arrow"></span>
                    </Link>

                    <div class="card-border"></div>
                </div>

                <!-- Interviews Card -->
                <div class="stat-card">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="stat-header">
                        <div class="stat-icon stat-icon-purple">
                            <span v-html="icons.calendar"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div
                            v-if="pendingInterviewCount"
                            class="stat-badge stat-badge-alert"
                        >
                            <span>{{ pendingInterviewCount }}</span>
                            <span>Pending</span>
                        </div>
                    </div>

                    <div class="stat-body">
                        <div class="stat-value">{{ totalInterviews }}</div>
                        <div class="stat-label">Interviews</div>
                        <div class="stat-description">
                            All interview sessions you've been invited to
                        </div>
                    </div>

                    <Link href="/app/interviews" class="stat-link">
                        <span>View & Confirm</span>
                        <span class="link-icon" v-html="icons.arrow"></span>
                    </Link>

                    <div class="card-border"></div>
                </div>

                <!-- Resume Card -->
                <div class="stat-card">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="stat-header">
                        <div class="stat-icon stat-icon-green">
                            <span v-html="icons.document"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div
                            class="stat-badge"
                            :class="
                                hasResume
                                    ? 'stat-badge-success'
                                    : 'stat-badge-inactive'
                            "
                        >
                            <span
                                v-html="hasResume ? icons.check : icons.alert"
                            ></span>
                            <span>{{ hasResume ? "Active" : "Inactive" }}</span>
                        </div>
                    </div>

                    <div class="stat-body">
                        <div class="stat-label">Resume Status</div>
                        <div class="stat-description">
                            {{
                                hasResume
                                    ? "Your active resume is ready for quick apply"
                                    : "Upload your resume to enable one-click apply"
                            }}
                        </div>
                    </div>

                    <Link href="/app/resume" class="stat-link">
                        <span>{{
                            hasResume ? "Manage Resume" : "Add Resume"
                        }}</span>
                        <span class="link-icon" v-html="icons.arrow"></span>
                    </Link>

                    <div class="card-border"></div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="actions-grid">
                <div class="action-card">
                    <div class="action-icon action-icon-blue">
                        <span v-html="icons.briefcase"></span>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Find Opportunities</div>
                        <div class="action-description">
                            Explore vacancies that match your skills and
                            interests
                        </div>
                    </div>
                    <Link href="/applicant/jobs" class="action-button">
                        <span>Search Jobs</span>
                        <span class="button-icon" v-html="icons.arrow"></span>
                    </Link>
                </div>

                <div class="action-card">
                    <div class="action-icon action-icon-purple">
                        <span v-html="icons.inbox"></span>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Track Progress</div>
                        <div class="action-description">
                            See where each application stands in the process
                        </div>
                    </div>
                    <Link href="/applications" class="action-button">
                        <span>View Applications</span>
                        <span class="button-icon" v-html="icons.arrow"></span>
                    </Link>
                </div>

                <div class="action-card">
                    <div class="action-icon action-icon-green">
                        <span v-html="icons.calendar"></span>
                    </div>
                    <div class="action-content">
                        <div class="action-title">Upcoming Interviews</div>
                        <div class="action-description">
                            Check interview times, locations, and confirm
                            attendance
                        </div>
                    </div>
                    <Link href="/app/interviews" class="action-button">
                        <span>Open Interviews</span>
                        <span class="button-icon" v-html="icons.arrow"></span>
                    </Link>
                </div>
            </section>

            <!-- Resume Tip Banner -->
            <section v-if="!hasResume" class="tip-banner">
                <div class="tip-icon">
                    <span v-html="icons.sparkle"></span>
                </div>
                <div class="tip-content">
                    <div class="tip-title">Pro Tip!</div>
                    <div class="tip-text">
                        Upload and activate your resume to enable one-click
                        apply on job pages
                    </div>
                </div>
                <Link href="/app/resume" class="tip-button">
                    <span>Add Resume Now</span>
                    <span class="button-icon" v-html="icons.arrow"></span>
                </Link>
            </section>
        </div>
    </ApplicantLayout>
</template>

<style scoped>
/* Import Modern Fonts */
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables */
:root {
    --color-primary: #0ea5e9;
    --color-secondary: #8b5cf6;
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-text: #0f172a;
    --color-text-light: #64748b;
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --color-border: #e2e8f0;
}

/* Dashboard Container */
.applicant-dashboard {
    font-family:
        "Plus Jakarta Sans",
        -apple-system,
        sans-serif;
    color: var(--color-text);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 50%, #fae8ff 100%);
    border-radius: 24px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(14, 165, 233, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.6);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: radial-gradient(
        circle,
        rgba(139, 92, 246, 0.15) 0%,
        transparent 70%
    );
    border-radius: 50%;
    transform: translate(30%, -30%);
}

.hero-content {
    position: relative;
    z-index: 1;
    margin-bottom: 2rem;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(14, 165, 233, 0.3);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 1rem;
    backdrop-filter: blur(10px);
}

.badge-icon {
    width: 14px;
    height: 14px;
    animation: sparkle 2s ease-in-out infinite;
}

.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

@keyframes sparkle {
    0%,
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.2) rotate(180deg);
    }
}

.hero-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0 0 1rem 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.gradient-text {
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-light);
    margin: 0 0 2rem 0;
}

/* Alert Banner */
.alert-banner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.1) 0%,
        rgba(220, 38, 38, 0.1) 100%
    );
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 16px;
    margin-top: 1.5rem;
    overflow: hidden;
}

.alert-pulse {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, #ef4444, #dc2626);
    animation: alertPulse 2s ease-in-out infinite;
}

@keyframes alertPulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.alert-icon {
    width: 24px;
    height: 24px;
    color: #dc2626;
}

.alert-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.alert-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.alert-title {
    font-weight: 700;
    font-size: 0.875rem;
    color: #7f1d1d;
}

.alert-message {
    font-size: 0.875rem;
    color: #991b1b;
}

.alert-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.alert-button:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

/* Hero Actions */
.hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.hero-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1.75rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.hero-button-primary {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    box-shadow: 0 4px 16px rgba(14, 165, 233, 0.3);
}

.hero-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
}

.hero-button-secondary {
    background: rgba(255, 255, 255, 0.9);
    color: var(--color-text);
    border: 1px solid var(--color-border);
    backdrop-filter: blur(10px);
}

.hero-button-secondary:hover {
    background: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.button-icon {
    width: 18px;
    height: 18px;
}

.button-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.button-glow {
    position: absolute;
    inset: -50%;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.3) 0%,
        transparent 70%
    );
    opacity: 0;
    transition: opacity 0.3s ease;
}

.hero-button-primary:hover .button-glow {
    opacity: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    position: relative;
    background: var(--color-card);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.card-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(
        circle,
        rgba(14, 165, 233, 0.1) 0%,
        transparent 70%
    );
    opacity: 0;
    transition: opacity 0.4s ease;
}

.stat-card:hover .card-glow {
    opacity: 1;
}

.card-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transition: left 0.6s;
}

.stat-card:hover .card-shine {
    left: 100%;
}

.card-border {
    position: absolute;
    inset: 0;
    border-radius: 20px;
    padding: 1px;
    background: linear-gradient(
        135deg,
        rgba(14, 165, 233, 0.3),
        rgba(139, 92, 246, 0.3)
    );
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.stat-card:hover .card-border {
    opacity: 1;
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.stat-icon {
    position: relative;
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
    position: relative;
    z-index: 1;
}

.icon-pulse {
    position: absolute;
    inset: 0;
    border-radius: 14px;
    background: inherit;
    opacity: 0.5;
    animation: iconPulse 2s ease-out infinite;
}

@keyframes iconPulse {
    0% {
        transform: scale(1);
        opacity: 0.5;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}

.stat-icon-blue {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    box-shadow: 0 4px 16px rgba(14, 165, 233, 0.4);
}

.stat-icon-purple {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 4px 16px rgba(139, 92, 246, 0.4);
}

.stat-icon-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.4);
}

.stat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--color-text);
}

.stat-badge :deep(svg) {
    width: 12px;
    height: 12px;
}

.stat-badge-alert {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #7f1d1d;
    border: 1px solid rgba(239, 68, 68, 0.3);
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.stat-badge-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.stat-badge-inactive {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(251, 191, 36, 0.15)
    );
    color: #78350f;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.stat-body {
    margin-bottom: 1.5rem;
}

.stat-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 3rem;
    font-weight: 700;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.stat-description {
    font-size: 0.875rem;
    color: var(--color-text-light);
    line-height: 1.5;
}

.stat-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.stat-link:hover {
    gap: 0.75rem;
}

.link-icon {
    width: 14px;
    height: 14px;
    transition: transform 0.3s ease;
}

.link-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.stat-link:hover .link-icon {
    transform: translateX(4px);
}

/* Actions Grid */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.action-card {
    background: var(--color-card);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-bottom: 1.25rem;
}

.action-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.action-icon-blue {
    background: linear-gradient(
        135deg,
        rgba(14, 165, 233, 0.15),
        rgba(14, 165, 233, 0.25)
    );
    color: #0284c7;
}

.action-icon-purple {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(139, 92, 246, 0.25)
    );
    color: #7c3aed;
}

.action-icon-green {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(16, 185, 129, 0.25)
    );
    color: #059669;
}

.action-content {
    margin-bottom: 1.5rem;
}

.action-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.action-description {
    font-size: 0.875rem;
    color: var(--color-text-light);
    line-height: 1.5;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: var(--color-bg);
    color: var(--color-text);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.action-button:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

/* Tip Banner */
.tip-banner {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-radius: 20px;
    border: 2px dashed rgba(14, 165, 233, 0.3);
}

.tip-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    animation: tipFloat 3s ease-in-out infinite;
}

@keyframes tipFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.tip-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.tip-content {
    flex: 1;
}

.tip-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0c4a6e;
    margin-bottom: 0.25rem;
}

.tip-text {
    font-size: 0.875rem;
    color: #0369a1;
}

.tip-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.tip-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-grid,
    .actions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-actions {
        flex-direction: column;
    }

    .hero-button {
        width: 100%;
        justify-content: center;
    }

    .alert-banner {
        flex-direction: column;
        align-items: stretch;
    }

    .alert-button {
        justify-content: center;
    }

    .stat-value {
        font-size: 2.5rem;
    }

    .tip-banner {
        flex-direction: column;
        text-align: center;
    }

    .tip-button {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 640px) {
    .hero-section {
        padding: 1.5rem;
    }

    .hero-title {
        font-size: 1.75rem;
    }

    .stat-card,
    .action-card {
        padding: 1.5rem;
    }
}
</style>
