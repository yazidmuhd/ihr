<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    rows: { type: [Object, Array], default: () => ({ data: [], links: [] }) },
});

const list = computed(() =>
    Array.isArray(props.rows) ? props.rows : (props.rows?.data ?? []),
);
const expandedBreakdowns = ref(new Set());
const toggleBreakdown = (id) =>
    expandedBreakdowns.value.has(id)
        ? expandedBreakdowns.value.delete(id)
        : expandedBreakdowns.value.add(id);
const isBreakdownExpanded = (id) => expandedBreakdowns.value.has(id);

const ACTIVE = [
    "submitted",
    "in_review",
    "shortlisted",
    "review",
    "processing",
    "interview_invited",
    "interview_confirmed",
];
const WITHDRAWN = ["withdrawn", "canceled", "cancelled"];
const sNorm = (s) => (s || "").toLowerCase();
const isActiveStatus = (s) => ACTIVE.includes(sNorm(s));
const isWithdrawnStatus = (s) => WITHDRAWN.includes(sNorm(s));
const canWithdraw = (row) => isActiveStatus(row.status);
const canReapply = (row) =>
    isWithdrawnStatus(row.status) && sNorm(row.vacancy_status || "") === "open";

function badgeClass(status) {
    const s = sNorm(status);
    if (["submitted", "applied", "received"].includes(s))
        return "badge-submitted";
    if (["shortlisted", "review", "interview_invited", "interview"].includes(s))
        return "badge-review";
    if (["hired", "accepted", "offer", "interview_confirmed"].includes(s))
        return "badge-success";
    if (["rejected", "declined", "no_show"].includes(s))
        return "badge-rejected";
    if (WITHDRAWN.includes(s)) return "badge-withdrawn";
    return "badge-default";
}

function statusLabel(status) {
    const map = {
        submitted: "Submitted",
        in_review: "In Review",
        shortlisted: "Shortlisted",
        interview_invited: "Interview Invited",
        interview_confirmed: "Confirmed",
        hired: "Hired",
        rejected: "Rejected",
        withdrawn: "Withdrawn",
    };
    return map[sNorm(status)] || status || "—";
}

function breakdownObj(v) {
    if (!v) return null;
    if (typeof v === "object") return v;
    try {
        return JSON.parse(v);
    } catch {
        return null;
    }
}

const getScoreColor = (score) =>
    score >= 80
        ? "emerald"
        : score >= 60
          ? "blue"
          : score >= 40
            ? "amber"
            : "rose";
const withdrawApp = (id) =>
    confirm("Withdraw this application?") &&
    router.delete(`/applications/${id}`, { preserveScroll: true });
const reapplyApp = (row) =>
    confirm("Re-apply to this vacancy?") &&
    router.post(
        "/applications",
        { vacancy_id: row.vacancy_id },
        { preserveScroll: true },
    );
const respondInterview = (row, response) =>
    row.interview?.id &&
    confirm(
        response === "confirm"
            ? "Confirm this interview?"
            : "Decline this interview?",
    ) &&
    router.post(
        `/app/interviews/${row.interview.id}/respond`,
        { response },
        { preserveScroll: true },
    );

// SVG Icons
const icons = {
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    location:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    trophy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M18 17V9M13 17V5M8 17v-3"/></svg>',
    zap: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    calendar:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    arrow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
};
</script>

<template>
    <Head title="My Applications" />
    <ApplicantLayout :showSidebar="false" content-max="max-w-7xl">
        <!-- Hero Header -->
        <div class="hero">
            <div class="hero-content">
                <div class="hero-left">
                    <div class="hero-icon">
                        <span v-html="icons.briefcase"></span>
                        <div class="pulse"></div>
                    </div>
                    <div>
                        <h1 class="hero-title">My Applications</h1>
                        <p class="hero-sub">
                            Track your journey and manage interviews
                        </p>
                    </div>
                </div>
                <Link href="/jobs" class="hero-btn">
                    <span class="btn-icon" v-html="icons.arrow"></span>
                    <span>Browse Jobs</span>
                </Link>
            </div>

            <div v-if="list.length" class="stats">
                <div class="stat">
                    <span class="stat-val">{{ list.length }}</span
                    ><span class="stat-label">Total</span>
                </div>
                <div class="stat">
                    <span class="stat-val">{{
                        list.filter((r) => isActiveStatus(r.status)).length
                    }}</span
                    ><span class="stat-label">Active</span>
                </div>
                <div class="stat">
                    <span class="stat-val">{{
                        list.filter((r) => r.interview).length
                    }}</span
                    ><span class="stat-label">Interviews</span>
                </div>
            </div>
        </div>

        <!-- Applications List -->
        <section v-if="list.length" class="apps-grid">
            <article
                v-for="(r, idx) in list"
                :key="r.id"
                class="app-card"
                :style="{ '--delay': `${idx * 0.05}s` }"
            >
                <div class="card-glow"></div>

                <!-- Header -->
                <div class="card-header">
                    <div class="header-left">
                        <div class="job-icon">
                            <span v-html="icons.briefcase"></span>
                        </div>
                        <div class="header-info">
                            <h3 class="job-title">
                                {{
                                    r.title ||
                                    r.vacancy_title ||
                                    "Untitled Role"
                                }}
                            </h3>
                            <div class="job-meta">
                                <span class="meta-item">
                                    <span
                                        class="icon-sm"
                                        v-html="icons.briefcase"
                                    ></span>
                                    <span>{{ r.department || "—" }}</span>
                                </span>
                                <span class="meta-item">
                                    <span
                                        class="icon-sm"
                                        v-html="icons.location"
                                    ></span>
                                    <span>{{ r.location || "—" }}</span>
                                </span>
                            </div>
                            <div class="job-date">
                                <span
                                    class="icon-sm"
                                    v-html="icons.clock"
                                ></span>
                                <span
                                    >Applied
                                    {{
                                        r.applied_at || r.created_at || "—"
                                    }}</span
                                >
                                <span
                                    v-if="r.vacancy_status"
                                    class="vacancy-tag"
                                    >{{ r.vacancy_status }}</span
                                >
                            </div>
                        </div>
                    </div>
                    <div class="status-badge" :class="badgeClass(r.status)">
                        <span class="pulse-dot"></span
                        >{{ statusLabel(r.status) }}
                    </div>
                </div>

                <!-- Score Section -->
                <div v-if="r.match_score != null" class="score-section">
                    <div class="score-header">
                        <span class="icon-md" v-html="icons.trophy"></span>
                        <span>Match Score</span>
                    </div>
                    <div class="score-display">
                        <div
                            class="score-circle"
                            :class="`score-${getScoreColor(r.match_score)}`"
                        >
                            <svg viewBox="0 0 100 100" class="score-ring">
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="45"
                                    class="ring-bg"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="45"
                                    class="ring-fill"
                                    :style="{
                                        strokeDashoffset:
                                            283 - (283 * r.match_score) / 100,
                                    }"
                                />
                            </svg>
                            <div class="score-val">
                                <div class="score-number">
                                    {{ r.match_score }}
                                </div>
                                <div class="score-max">/100</div>
                            </div>
                        </div>

                        <button
                            v-if="breakdownObj(r.match_breakdown)"
                            @click="toggleBreakdown(r.id)"
                            class="breakdown-btn"
                        >
                            <span class="icon-sm" v-html="icons.chart"></span>
                            <span
                                >{{
                                    isBreakdownExpanded(r.id) ? "Hide" : "View"
                                }}
                                Breakdown</span
                            >
                            <span
                                class="chevron"
                                :class="{ rotated: isBreakdownExpanded(r.id) }"
                                >▼</span
                            >
                        </button>
                    </div>

                    <!-- Breakdown Panel -->
                    <transition name="slide">
                        <div
                            v-if="
                                isBreakdownExpanded(r.id) &&
                                breakdownObj(r.match_breakdown)
                            "
                            class="breakdown"
                        >
                            <div class="breakdown-header">
                                <span class="icon-sm" v-html="icons.zap"></span>
                                <span>Score Breakdown</span>
                            </div>
                            <div class="breakdown-grid">
                                <div class="breakdown-item">
                                    <div class="item-row">
                                        <span>Skills Match</span
                                        ><span
                                            :class="`text-${getScoreColor(breakdownObj(r.match_breakdown).skills || 0)}`"
                                            >{{
                                                breakdownObj(r.match_breakdown)
                                                    .skills || 0
                                            }}%</span
                                        >
                                    </div>
                                    <div class="progress">
                                        <div
                                            class="progress-fill skills"
                                            :style="{
                                                width: `${breakdownObj(r.match_breakdown).skills || 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <div class="breakdown-item">
                                    <div class="item-row">
                                        <span>Experience</span
                                        ><span
                                            :class="`text-${getScoreColor(breakdownObj(r.match_breakdown).experience || 0)}`"
                                            >{{
                                                breakdownObj(r.match_breakdown)
                                                    .experience || 0
                                            }}%</span
                                        >
                                    </div>
                                    <div class="progress">
                                        <div
                                            class="progress-fill experience"
                                            :style="{
                                                width: `${breakdownObj(r.match_breakdown).experience || 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <div class="breakdown-item">
                                    <div class="item-row">
                                        <span>Education</span
                                        ><span
                                            :class="`text-${getScoreColor(breakdownObj(r.match_breakdown).education || 0)}`"
                                            >{{
                                                breakdownObj(r.match_breakdown)
                                                    .education || 0
                                            }}%</span
                                        >
                                    </div>
                                    <div class="progress">
                                        <div
                                            class="progress-fill education"
                                            :style="{
                                                width: `${breakdownObj(r.match_breakdown).education || 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="
                                    breakdownObj(r.match_breakdown)
                                        .overlap_skills?.length
                                "
                                class="skills"
                            >
                                <div class="skills-header">
                                    <span
                                        class="icon-sm"
                                        v-html="icons.star"
                                    ></span>
                                    <span>Matching Skills</span>
                                </div>
                                <div class="skills-list">
                                    <span
                                        v-for="(skill, i) in breakdownObj(
                                            r.match_breakdown,
                                        ).overlap_skills"
                                        :key="i"
                                        class="skill-tag"
                                        >{{ skill }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Interview Section -->
                <div v-if="r.interview" class="interview">
                    <div class="interview-header">
                        <div class="interview-title">
                            <span
                                class="icon-md"
                                v-html="icons.calendar"
                            ></span>
                            <span>Interview Scheduled</span>
                        </div>
                        <span class="interview-mode">{{
                            r.interview.mode || "TBC"
                        }}</span>
                    </div>
                    <div class="interview-status">
                        {{
                            !r.interview.candidate_status ||
                            r.interview.candidate_status === "pending"
                                ? "⏳ Awaiting confirmation"
                                : r.interview.candidate_status === "confirmed"
                                  ? "✅ Confirmed"
                                  : "❌ Declined"
                        }}
                    </div>
                    <div class="interview-details">
                        <div class="detail-row">
                            <span class="icon-sm" v-html="icons.clock"></span>
                            <span>{{ r.interview.scheduled_at || "TBC" }}</span>
                        </div>
                        <div v-if="r.interview.location" class="detail-row">
                            <span
                                class="icon-sm"
                                v-html="icons.location"
                            ></span>
                            <span>{{ r.interview.location }}</span>
                        </div>
                        <a
                            v-if="r.interview.meeting_link"
                            :href="r.interview.meeting_link"
                            target="_blank"
                            class="meeting-link"
                        >
                            <span class="icon-sm" v-html="icons.link"></span>
                            <span>Join Meeting</span>
                            <span class="icon-sm" v-html="icons.arrow"></span>
                        </a>
                    </div>
                    <p v-if="r.interview.extra_info" class="interview-note">
                        {{ r.interview.extra_info }}
                    </p>
                    <div
                        v-if="
                            !r.interview.candidate_status ||
                            r.interview.candidate_status === 'pending'
                        "
                        class="interview-actions"
                    >
                        <button
                            @click="respondInterview(r, 'confirm')"
                            class="btn-confirm"
                        >
                            <span class="icon-sm" v-html="icons.check"></span>
                            <span>Confirm</span>
                        </button>
                        <button
                            @click="respondInterview(r, 'decline')"
                            class="btn-decline"
                        >
                            Decline
                        </button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card-actions">
                    <Link
                        :href="`/jobs/${r.vacancy_id}`"
                        class="action-btn view"
                    >
                        <span>View Job</span>
                        <span class="icon-sm" v-html="icons.arrow"></span>
                    </Link>
                    <button
                        v-if="canWithdraw(r)"
                        @click="withdrawApp(r.id)"
                        class="action-btn withdraw"
                    >
                        Withdraw
                    </button>
                    <button
                        v-else-if="canReapply(r)"
                        @click="reapplyApp(r)"
                        class="action-btn reapply"
                    >
                        <span class="icon-sm" v-html="icons.sparkles"></span>
                        <span>Re-apply</span>
                    </button>
                    <span
                        v-else-if="isWithdrawnStatus(r.status)"
                        class="disabled"
                        >Vacancy Closed</span
                    >
                </div>
            </article>
        </section>

        <!-- Empty State -->
        <div v-else class="empty">
            <div class="empty-icon">
                <span v-html="icons.briefcase"></span>
            </div>
            <h3>No Applications Yet</h3>
            <p>Start your journey by exploring available positions</p>
            <Link href="/jobs" class="empty-btn">
                <span class="icon-sm" v-html="icons.sparkles"></span>
                <span>Browse Jobs</span>
            </Link>
        </div>
    </ApplicantLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

* {
    box-sizing: border-box;
}

/* Hero */
.hero {
    position: relative;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
.hero-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 1.5rem;
}
.hero-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.hero-icon {
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
.hero-icon :deep(svg) {
    width: 32px;
    height: 32px;
}
.pulse {
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
.hero-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 0.25rem;
}
.hero-sub {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
    margin: 0;
}
.hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.3s;
}
.hero-btn:hover {
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
}

/* Icon Styles */
.icon-sm {
    display: inline-flex;
    width: 14px;
    height: 14px;
    color: currentColor;
}
.icon-sm :deep(svg) {
    width: 100%;
    height: 100%;
}
.icon-md {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: currentColor;
}
.icon-md :deep(svg) {
    width: 100%;
    height: 100%;
}
.btn-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
    color: currentColor;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Stats */
.stats {
    position: relative;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s;
}
.stat:hover {
    background: white;
    border-color: #0ea5e9;
    transform: translateY(-2px);
}
.stat-val {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #0ea5e9, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    line-height: 1;
    margin-bottom: 0.25rem;
}
.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
}

/* Applications Grid */
.apps-grid {
    display: grid;
    gap: 1.5rem;
}
.app-card {
    position: relative;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeIn 0.6s ease-out var(--delay) both;
    overflow: hidden;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.card-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(6, 182, 212, 0.08) 0%,
        transparent 60%
    );
    opacity: 0;
    transition: opacity 0.4s;
    pointer-events: none;
}
.app-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
    border-color: rgba(6, 182, 212, 0.3);
}
.app-card:hover .card-glow {
    opacity: 1;
}

/* Card Header */
.card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}
.header-left {
    display: flex;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}
.job-icon {
    width: 56px;
    height: 56px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 14px;
    color: #0ea5e9;
}
.job-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.header-info {
    flex: 1;
    min-width: 0;
}
.job-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.5rem;
    line-height: 1.3;
}
.job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}
.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}
.job-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
}
.vacancy-tag {
    padding: 0.125rem 0.5rem;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 4px;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-left: 0.5rem;
}

/* Status Badge */
.status-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    flex-shrink: 0;
}
.pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: pulseDot 2s infinite;
}
@keyframes pulseDot {
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
.badge-submitted {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #065f46;
}
.badge-submitted .pulse-dot {
    background: #10b981;
}
.badge-review {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #1e40af;
}
.badge-review .pulse-dot {
    background: #3b82f6;
}
.badge-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.2),
        rgba(5, 150, 105, 0.2)
    );
    border: 1px solid rgba(16, 185, 129, 0.4);
    color: #065f46;
}
.badge-success .pulse-dot {
    background: #10b981;
}
.badge-rejected {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #991b1b;
}
.badge-rejected .pulse-dot {
    background: #ef4444;
}
.badge-withdrawn {
    background: linear-gradient(
        135deg,
        rgba(148, 163, 184, 0.15),
        rgba(100, 116, 139, 0.15)
    );
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #475569;
}
.badge-withdrawn .pulse-dot {
    background: #64748b;
}
.badge-default {
    background: linear-gradient(
        135deg,
        rgba(148, 163, 184, 0.15),
        rgba(100, 116, 139, 0.15)
    );
    border: 1px solid rgba(148, 163, 184, 0.3);
    color: #475569;
}
.badge-default .pulse-dot {
    background: #64748b;
}

/* Score Section */
.score-section {
    position: relative;
    z-index: 1;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.6);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 1.5rem;
}
.score-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1.25rem;
}
.score-header .icon-md {
    color: #f59e0b;
}
.score-display {
    display: flex;
    align-items: center;
    gap: 2rem;
}
.score-circle {
    position: relative;
    width: 120px;
    height: 120px;
    flex-shrink: 0;
}
.score-ring {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}
.ring-bg {
    fill: none;
    stroke: rgba(148, 163, 184, 0.2);
    stroke-width: 8;
}
.ring-fill {
    fill: none;
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 283;
    transition: stroke-dashoffset 1s cubic-bezier(0.4, 0, 0.2, 1);
    animation: reveal 1.5s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
}
@keyframes reveal {
    from {
        stroke-dashoffset: 283;
    }
}
.score-emerald .ring-fill {
    stroke: #10b981;
}
.score-blue .ring-fill {
    stroke: #3b82f6;
}
.score-amber .ring-fill {
    stroke: #f59e0b;
}
.score-rose .ring-fill {
    stroke: #ef4444;
}
.score-val {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.125rem;
}
.score-number {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.05rem;
    font-weight: 800;
    line-height: 1;
    background: linear-gradient(135deg, #0ea5e9, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.score-max {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    line-height: 1;
}

/* Breakdown */
.breakdown-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    cursor: pointer;
    transition: all 0.3s;
}
.breakdown-btn:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
}
.chevron {
    transition: transform 0.3s;
    font-size: 0.75rem;
}
.chevron.rotated {
    transform: rotate(180deg);
}
.breakdown {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.breakdown-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1.25rem;
}
.breakdown-header .icon-sm {
    color: #0ea5e9;
}
.breakdown-grid {
    display: grid;
    gap: 1.25rem;
    margin-bottom: 1.5rem;
}
.breakdown-item {
    animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.breakdown-item:nth-child(1) {
    animation-delay: 0.1s;
}
.breakdown-item:nth-child(2) {
    animation-delay: 0.2s;
}
.breakdown-item:nth-child(3) {
    animation-delay: 0.3s;
}
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
.item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}
.text-emerald {
    color: #059669;
}
.text-blue {
    color: #2563eb;
}
.text-amber {
    color: #d97706;
}
.text-rose {
    color: #dc2626;
}
.progress {
    height: 8px;
    background: rgba(148, 163, 184, 0.2);
    border-radius: 50px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    border-radius: 50px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    animation: grow 1.2s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
}
@keyframes grow {
    from {
        width: 0 !important;
    }
}
.progress-fill.skills {
    background: linear-gradient(90deg, #10b981, #059669);
}
.progress-fill.experience {
    background: linear-gradient(90deg, #3b82f6, #2563eb);
}
.progress-fill.education {
    background: linear-gradient(90deg, #f59e0b, #d97706);
}

/* Skills */
.skills {
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
    animation: fadeInUp 0.6s ease-out 0.5s both;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.skills-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1rem;
}
.skills-header .icon-sm {
    color: #f59e0b;
}
.skills-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.skill-tag {
    padding: 0.375rem 0.875rem;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0ea5e9;
    animation: pop 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.skill-tag:nth-child(1) {
    animation-delay: 0.6s;
}
.skill-tag:nth-child(2) {
    animation-delay: 0.65s;
}
.skill-tag:nth-child(3) {
    animation-delay: 0.7s;
}
.skill-tag:nth-child(4) {
    animation-delay: 0.75s;
}
.skill-tag:nth-child(5) {
    animation-delay: 0.8s;
}
@keyframes pop {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Transitions */
.slide-enter-active {
    animation: slideDown 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-leave-active {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        max-height: 1000px;
        transform: translateY(0);
    }
}
@keyframes slideUp {
    from {
        opacity: 1;
        max-height: 1000px;
    }
    to {
        opacity: 0;
        max-height: 0;
    }
}

/* Interview */
.interview {
    position: relative;
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.08),
        rgba(5, 150, 105, 0.08)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 16px;
    margin-bottom: 1.5rem;
}
.interview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
}
.interview-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-family: "Bricolage Grotesque", sans-serif;
    font-weight: 700;
    color: #065f46;
}
.interview-title .icon-md {
    color: #047857;
}
.interview-mode {
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #065f46;
}
.interview-status {
    font-size: 0.875rem;
    color: #047857;
    font-weight: 500;
    margin-bottom: 1rem;
}
.interview-details {
    display: grid;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: #065f46;
    margin-bottom: 1rem;
}
.detail-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.meeting-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #0ea5e9;
    text-decoration: none;
    font-weight: 600;
}
.meeting-link:hover {
    text-decoration: underline;
}
.interview-note {
    padding: 1rem;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 10px;
    font-size: 0.875rem;
    color: #065f46;
    line-height: 1.6;
    margin-bottom: 1rem;
}
.interview-actions {
    display: flex;
    gap: 0.75rem;
}
.btn-confirm {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s;
}
.btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.btn-decline {
    flex: 1;
    padding: 0.875rem;
    background: white;
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-decline:hover {
    background: rgba(16, 185, 129, 0.05);
}

/* Actions */
.card-actions {
    position: relative;
    z-index: 1;
    display: flex;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
}
.action-btn.view {
    background: white;
    color: #0f172a;
    border: 1px solid #cbd5e1;
}
.action-btn.view:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
}
.action-btn.withdraw {
    background: white;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.action-btn.withdraw:hover {
    background: rgba(239, 68, 68, 0.05);
}
.action-btn.reapply {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-btn.reapply:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.disabled {
    padding: 0.75rem;
    font-size: 0.875rem;
    color: #64748b;
    opacity: 0.6;
}

/* Empty State */
.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 20px;
    border: 2px dashed #cbd5e1;
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
.empty h3 {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.5rem;
}
.empty p {
    font-size: 1rem;
    color: #64748b;
    margin: 0 0 2rem;
    text-align: center;
    max-width: 400px;
}
.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    transition: all 0.3s;
}
.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .hero {
        padding: 1.5rem;
    }
    .hero-content {
        flex-direction: column;
        align-items: flex-start;
        margin-bottom: 1.25rem;
    }
    .hero-left {
        flex-direction: column;
        align-items: flex-start;
    }
    .hero-icon {
        width: 56px;
        height: 56px;
    }
    .hero-icon :deep(svg) {
        width: 28px;
        height: 28px;
    }
    .hero-title {
        font-size: 1.75rem;
    }
    .hero-btn {
        width: 100%;
        justify-content: center;
    }
    .stats {
        grid-template-columns: 1fr;
    }
    .app-card {
        padding: 1.5rem;
    }
    .card-header {
        flex-direction: column;
        gap: 1rem;
    }
    .header-left {
        flex-direction: column;
        width: 100%;
    }
    .status-badge {
        align-self: flex-start;
    }
    .job-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    .score-display {
        flex-direction: column;
        align-items: center;
    }
    .breakdown-btn {
        width: 100%;
    }
    .interview-header {
        flex-wrap: wrap;
    }
    .interview-mode {
        width: 100%;
        text-align: center;
    }
    .interview-actions {
        flex-direction: column;
    }
    .card-actions {
        flex-direction: column;
    }
    .action-btn {
        width: 100%;
        text-align: center;
    }
}

@media (max-width: 640px) {
    .hero {
        padding: 1.25rem;
    }
    .hero-icon {
        width: 48px;
        height: 48px;
    }
    .hero-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }
    .hero-title {
        font-size: 1.5rem;
    }
    .app-card {
        padding: 1.25rem;
    }
    .job-icon {
        width: 48px;
        height: 48px;
    }
    .job-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }
    .job-title {
        font-size: 1.125rem;
    }
    .score-circle {
        width: 100px;
        height: 100px;
    }
    .score-number {
        font-size: 2rem;
    }
    .breakdown {
        padding: 1.25rem;
    }
    .interview {
        padding: 1.25rem;
    }
    .empty {
        padding: 4rem 1.5rem;
    }
}
</style>
