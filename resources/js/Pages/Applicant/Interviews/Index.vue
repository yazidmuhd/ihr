<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    interviews: { type: Array, default: () => [] },
});

/* ---------- Helpers ---------- */

// sort: upcoming (earliest first), then others
const sortedInterviews = computed(() => {
    const arr = [...(props.interviews || [])];

    return arr.sort((a, b) => {
        const aHas = !!a.scheduled_at;
        const bHas = !!b.scheduled_at;

        if (aHas && bHas) {
            return a.scheduled_at.localeCompare(b.scheduled_at);
        }
        if (aHas && !bHas) return -1;
        if (!aHas && bHas) return 1;

        return (a.id ?? 0) - (b.id ?? 0);
    });
});

// nice datetime formatting (fallback to raw if parsing fails)
const formatDateTime = (value) => {
    if (!value) return "To be confirmed";

    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        // unknown format → show raw
        return value;
    }

    return d.toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

const modeLabel = (m) => {
    const t = (m || "").toLowerCase();
    if (t === "online") return "Online (video call)";
    if (t === "onsite" || t === "on-site") return "On-site (physical)";
    if (t === "hybrid") return "Hybrid";
    return m || "Not specified";
};

const statusTone = (s) => {
    const t = (s || "").toLowerCase();
    if (["scheduled"].includes(t)) return "badge-blue";
    if (["scored", "completed", "evaluated", "done"].includes(t))
        return "badge-green";
    if (["cancelled", "canceled", "no_show"].includes(t)) return "badge-red";
    return "badge-slate";
};

const candidateBadgeClass = (s) => {
    const t = (s || "").toLowerCase();
    if (t === "confirmed" || t === "accepted") return "chip chip-yes";
    if (t === "declined") return "chip chip-no";
    if (!t || t === "pending") return "chip chip-pending";
    return "chip chip-neutral";
};

const candidateLabel = (s) => {
    const t = (s || "").toLowerCase();
    if (t === "confirmed" || t === "accepted") return "Confirmed";
    if (t === "declined") return "Declined";
    if (!t || t === "pending") return "Pending response";
    return s;
};

// can applicant still act? (only when not yet responded)
const canRespond = (i) => {
    const t = (i.candidate_status || "").toLowerCase();
    return !t || t === "pending";
};

/* ---------- Respond (confirm / decline) ---------- */

const declineId = ref(null);
const reasons = ref({});

function respond(i, action) {
    // action: "confirm" | "decline"
    const payload = { response: action };
    if (action === "decline") {
        payload.reason = reasons.value[i.id] || "";
    }

    router.post(`/app/interviews/${i.id}/respond`, payload, {
        preserveScroll: true,
        onFinish: () => {
            if (action === "decline") {
                declineId.value = null;
            }
        },
    });
}

function toggleDecline(i) {
    if (!canRespond(i)) return;
    declineId.value = declineId.value === i.id ? null : i.id;
}

const icons = {
    calendar:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    video: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m23 7-7 5 7 5V7zM14 5H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2z"/></svg>',
    mapPin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    fileText:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    externalLink:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>',
    alertCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
};
</script>

<template>
    <Head title="My Interviews" />
    <ApplicantLayout>
        <div class="page-container">
            <!-- Animated Background -->
            <div class="background-wrapper">
                <div class="gradient-orb orb-1"></div>
                <div class="gradient-orb orb-2"></div>
                <div class="gradient-orb orb-3"></div>
            </div>

            <!-- Header Card -->
            <section class="header-card">
                <div class="header-glow"></div>
                <div class="header-content">
                    <div class="header-icon">
                        <span v-html="icons.calendar"></span>
                        <div class="icon-pulse"></div>
                    </div>
                    <div class="header-text">
                        <h1 class="header-title">My Interview Schedule</h1>
                        <p class="header-subtitle">
                            Review your upcoming interviews and confirm your
                            attendance. Make sure to check the date, time, and
                            location details carefully.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Stats Banner -->
            <div v-if="sortedInterviews.length" class="stats-banner">
                <div class="stat-item">
                    <span class="stat-icon" v-html="icons.calendar"></span>
                    <div class="stat-content">
                        <div class="stat-value">
                            {{ sortedInterviews.length }}
                        </div>
                        <div class="stat-label">Total Interviews</div>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-icon" v-html="icons.clock"></span>
                    <div class="stat-content">
                        <div class="stat-value">
                            {{
                                sortedInterviews.filter((i) =>
                                    ["pending", ""].includes(
                                        (
                                            i.candidate_status || ""
                                        ).toLowerCase(),
                                    ),
                                ).length
                            }}
                        </div>
                        <div class="stat-label">Pending Response</div>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <span class="stat-icon" v-html="icons.check"></span>
                    <div class="stat-content">
                        <div class="stat-value">
                            {{
                                sortedInterviews.filter((i) =>
                                    ["confirmed", "accepted"].includes(
                                        (
                                            i.candidate_status || ""
                                        ).toLowerCase(),
                                    ),
                                ).length
                            }}
                        </div>
                        <div class="stat-label">Confirmed</div>
                    </div>
                </div>
            </div>

            <!-- Interviews List -->
            <section class="interviews-section">
                <!-- Empty State -->
                <div v-if="!sortedInterviews.length" class="empty-state">
                    <div class="empty-icon">
                        <span v-html="icons.calendar"></span>
                    </div>
                    <h3 class="empty-title">No Interviews Yet</h3>
                    <p class="empty-text">
                        You don't have any interview invitations at the moment.
                        Keep applying to positions that interest you!
                    </p>
                    <Link href="/applicant/jobs" class="empty-btn">
                        <span class="btn-icon" v-html="icons.briefcase"></span>
                        <span>Browse Available Jobs</span>
                    </Link>
                </div>

                <!-- Interview Cards -->
                <div v-else class="interviews-grid">
                    <article
                        v-for="(i, idx) in sortedInterviews"
                        :key="i.id"
                        class="interview-card"
                        :style="{ '--card-delay': `${idx * 0.1}s` }"
                    >
                        <!-- Status Badge (Top Left) -->
                        <div class="card-status-badge">
                            <span
                                :class="['status-dot', statusTone(i.status)]"
                            ></span>
                            <span>{{ i.status || "Invited" }}</span>
                        </div>

                        <!-- Response Badge (Top Right) -->
                        <div
                            :class="[
                                'card-response-badge',
                                candidateBadgeClass(i.candidate_status),
                            ]"
                        >
                            <span
                                class="badge-icon"
                                v-html="
                                    (i.candidate_status || '').toLowerCase() ===
                                        'confirmed' ||
                                    (i.candidate_status || '').toLowerCase() ===
                                        'accepted'
                                        ? icons.check
                                        : (
                                                i.candidate_status || ''
                                            ).toLowerCase() === 'declined'
                                          ? icons.x
                                          : icons.clock
                                "
                            ></span>
                            <span>{{
                                candidateLabel(i.candidate_status)
                            }}</span>
                        </div>

                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="vacancy-icon">
                                <span v-html="icons.sparkles"></span>
                            </div>
                            <div>
                                <h2 class="vacancy-title">
                                    {{ i.vacancy_title || "Interview" }}
                                </h2>
                                <div class="interview-datetime">
                                    <span
                                        class="datetime-icon"
                                        v-html="icons.calendar"
                                    ></span>
                                    <span>
                                        {{
                                            i.scheduled_at
                                                ? formatDateTime(i.scheduled_at)
                                                : "Time to be confirmed"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Details -->
                        <div class="card-details">
                            <!-- Mode -->
                            <div class="detail-row">
                                <span
                                    class="detail-icon"
                                    v-html="icons.video"
                                ></span>
                                <div class="detail-content">
                                    <div class="detail-label">
                                        Interview Mode
                                    </div>
                                    <div class="detail-value">
                                        {{ modeLabel(i.mode) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="detail-row">
                                <span
                                    class="detail-icon"
                                    v-html="icons.mapPin"
                                ></span>
                                <div class="detail-content">
                                    <div class="detail-label">Location</div>
                                    <div class="detail-value">
                                        {{
                                            i.location
                                                ? i.location
                                                : "Not specified / Online only"
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Meeting Link -->
                            <div v-if="i.meeting_link" class="detail-row">
                                <span
                                    class="detail-icon"
                                    v-html="icons.externalLink"
                                ></span>
                                <div class="detail-content">
                                    <div class="detail-label">Meeting Link</div>
                                    <a
                                        :href="i.meeting_link"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="meeting-link"
                                    >
                                        <span>Join Online Meeting</span>
                                        <span
                                            class="link-icon"
                                            v-html="icons.externalLink"
                                        ></span>
                                    </a>
                                </div>
                            </div>

                            <!-- Extra Info -->
                            <div
                                v-if="i.extra_info"
                                class="detail-row detail-full"
                            >
                                <span
                                    class="detail-icon detail-icon-top"
                                    v-html="icons.fileText"
                                ></span>
                                <div class="detail-content">
                                    <div class="detail-label">
                                        Additional Notes
                                    </div>
                                    <div class="detail-value detail-notes">
                                        {{ i.extra_info }}
                                    </div>
                                </div>
                            </div>

                            <!-- Candidate Reason (if declined) -->
                            <div
                                v-if="i.candidate_reason"
                                class="detail-row detail-full"
                            >
                                <span
                                    class="detail-icon detail-icon-top"
                                    v-html="icons.alertCircle"
                                ></span>
                                <div class="detail-content">
                                    <div class="detail-label">Your Reason</div>
                                    <div class="detail-value detail-reason">
                                        "{{ i.candidate_reason }}"
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="card-actions">
                            <button
                                type="button"
                                class="action-btn action-confirm"
                                :disabled="!canRespond(i)"
                                @click="canRespond(i) && respond(i, 'confirm')"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>
                                    {{
                                        canRespond(i)
                                            ? "Confirm Attendance"
                                            : "Response Recorded"
                                    }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="action-btn action-decline"
                                :disabled="!canRespond(i)"
                                @click="toggleDecline(i)"
                            >
                                <span class="btn-icon" v-html="icons.x"></span>
                                <span>Unable to Attend</span>
                            </button>
                        </div>

                        <!-- Decline Reason Panel -->
                        <transition name="decline-panel">
                            <div
                                v-if="declineId === i.id && canRespond(i)"
                                class="decline-panel"
                            >
                                <div class="decline-header">
                                    <span
                                        class="decline-icon"
                                        v-html="icons.alertCircle"
                                    ></span>
                                    <div>
                                        <h4 class="decline-title">
                                            Tell us why you can't attend
                                        </h4>
                                        <p class="decline-text">
                                            This helps HR decide whether to
                                            reschedule or close your
                                            application.
                                        </p>
                                    </div>
                                </div>

                                <textarea
                                    rows="3"
                                    v-model="reasons[i.id]"
                                    class="decline-textarea"
                                    placeholder="Example: I have a final exam at the same time and cannot attend on this date."
                                ></textarea>

                                <div class="decline-actions">
                                    <button
                                        type="button"
                                        class="decline-btn decline-btn-cancel"
                                        @click="declineId = null"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="decline-btn decline-btn-submit"
                                        @click="respond(i, 'decline')"
                                    >
                                        <span
                                            class="btn-icon"
                                            v-html="icons.x"
                                        ></span>
                                        <span>Submit & Decline</span>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </article>
                </div>
            </section>
        </div>
    </ApplicantLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

/* Page Container */
.page-container {
    position: relative;
    max-width: 80rem;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    gap: 2rem;
    font-family: "Plus Jakarta Sans", sans-serif;
}

/* Background */
.background-wrapper {
    position: fixed;
    inset: 0;
    z-index: -1;
    overflow: hidden;
}
.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
    animation: float 20s ease-in-out infinite;
}
.orb-1 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, #10b981, #34d399);
    top: -200px;
    left: -200px;
}
.orb-2 {
    width: 500px;
    height: 500px;
    background: linear-gradient(135deg, #059669, #10b981);
    bottom: -250px;
    right: -250px;
    animation-delay: -10s;
}
.orb-3 {
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #34d399, #6ee7b7);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: -5s;
}

@keyframes float {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -30px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

/* Header Card */
.header-card {
    position: relative;
    padding: 2rem;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.9) 0%,
        rgba(236, 253, 245, 0.9) 100%
    );
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(16, 185, 129, 0.2);
    box-shadow: 0 20px 60px rgba(16, 185, 129, 0.1);
    overflow: hidden;
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(16, 185, 129, 0.15) 0%,
        transparent 60%
    );
    pointer-events: none;
}

.header-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.header-icon {
    position: relative;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    color: white;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
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
    border: 2px solid rgba(16, 185, 129, 0.6);
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

.header-text {
    flex: 1;
}

.header-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 0.5rem;
}

.header-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
    line-height: 1.6;
}

/* Stats Banner */
.stats-banner {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 1.5rem;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.1s backwards;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border-radius: 12px;
    color: #10b981;
}

.stat-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.stat-content {
}

.stat-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
}

.stat-label {
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 600;
    margin-top: 0.25rem;
}

.stat-divider {
    width: 1px;
    height: 48px;
    background: linear-gradient(
        to bottom,
        transparent,
        rgba(148, 163, 184, 0.3),
        transparent
    );
}

/* Interviews Section */
.interviews-section {
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 5rem 2rem;
    background: white;
    border-radius: 24px;
    border: 2px dashed rgba(148, 163, 184, 0.3);
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s backwards;
}

.empty-icon {
    width: 96px;
    height: 96px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border-radius: 50%;
    color: #10b981;
    margin-bottom: 1.5rem;
}

.empty-icon :deep(svg) {
    width: 48px;
    height: 48px;
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
    margin: 0 0 2rem;
    text-align: center;
    max-width: 400px;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.btn-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}

.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Interviews Grid */
.interviews-grid {
    display: grid;
    gap: 1.5rem;
}

/* Interview Card */
.interview-card {
    position: relative;
    background: white;
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: cardFadeIn 0.6s ease-out var(--card-delay) both;
    overflow: hidden;
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
    box-shadow: 0 12px 40px rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.interview-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #10b981, #34d399, #6ee7b7);
}

/* Card Badges */
.card-status-badge {
    position: absolute;
    top: 1.5rem;
    left: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    text-transform: capitalize;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    animation: dotPulse 2s infinite;
}

@keyframes dotPulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.status-dot.badge-blue {
    background: #3b82f6;
}

.status-dot.badge-green {
    background: #10b981;
}

.status-dot.badge-red {
    background: #ef4444;
}

.status-dot.badge-slate {
    background: #64748b;
}

.card-response-badge {
    position: absolute;
    top: 1.5rem;
    right: 2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

.chip-pending {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: white;
}

.chip-yes {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.chip-no {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.chip-neutral {
    background: linear-gradient(135deg, #94a3b8, #64748b);
    color: white;
}

/* Card Header */
.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-top: 2rem;
}

.vacancy-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    border-radius: 14px;
    color: #10b981;
}

.vacancy-icon :deep(svg) {
    width: 28px;
    height: 28px;
}

.vacancy-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.5rem;
    letter-spacing: -0.01em;
}

.interview-datetime {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 600;
}

.datetime-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
    color: #10b981;
}

.datetime-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Card Details */
.card-details {
    display: grid;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(248, 250, 252, 0.8),
        rgba(241, 245, 249, 0.8)
    );
    border-radius: 16px;
    margin-bottom: 1.5rem;
}

.detail-row {
    display: flex;
    gap: 1rem;
}

.detail-full {
    grid-column: 1 / -1;
}

.detail-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 10px;
    color: #10b981;
    flex-shrink: 0;
}

.detail-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.detail-icon-top {
    align-self: flex-start;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}

.detail-value {
    font-size: 0.9375rem;
    color: #0f172a;
    font-weight: 600;
}

.detail-notes {
    line-height: 1.6;
    white-space: pre-line;
}

.detail-reason {
    font-style: italic;
    color: #64748b;
}

.meeting-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.meeting-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
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

/* Card Actions */
.card-actions {
    display: flex;
    gap: 1rem;
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

.action-confirm {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.action-confirm:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.action-confirm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-decline {
    background: white;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.action-decline:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.05);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.action-decline:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Decline Panel */
.decline-panel {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(254, 242, 242, 0.9),
        rgba(254, 226, 226, 0.9)
    );
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 16px;
}

.decline-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.decline-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 10px;
    color: #ef4444;
    flex-shrink: 0;
}

.decline-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.decline-title {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #991b1b;
    margin: 0 0 0.25rem;
}

.decline-text {
    font-size: 0.8125rem;
    color: #dc2626;
    margin: 0;
    line-height: 1.5;
}

.decline-textarea {
    width: 100%;
    padding: 0.875rem;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 10px;
    font-size: 0.875rem;
    color: #0f172a;
    background: white;
    resize: vertical;
    font-family: inherit;
    transition: all 0.3s;
}

.decline-textarea:focus {
    outline: none;
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.decline-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1rem;
}

.decline-btn {
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

.decline-btn-cancel {
    background: white;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.decline-btn-cancel:hover {
    background: rgba(148, 163, 184, 0.05);
}

.decline-btn-submit {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.decline-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
}

/* Decline Panel Transition */
.decline-panel-enter-active,
.decline-panel-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.decline-panel-enter-from {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
}

.decline-panel-enter-to {
    opacity: 1;
    transform: translateY(0);
    max-height: 500px;
}

.decline-panel-leave-from {
    opacity: 1;
    transform: translateY(0);
    max-height: 500px;
}

.decline-panel-leave-to {
    opacity: 0;
    transform: translateY(-20px);
    max-height: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .page-container {
        padding: 1rem;
    }

    .stats-banner {
        flex-direction: column;
        gap: 1rem;
    }

    .stat-divider {
        width: 100%;
        height: 1px;
    }
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
    }

    .header-title {
        font-size: 1.75rem;
    }

    .card-actions {
        flex-direction: column;
    }

    .card-status-badge,
    .card-response-badge {
        position: static;
        margin-bottom: 1rem;
    }

    .card-header {
        padding-top: 0;
    }
}

@media (max-width: 640px) {
    .header-icon {
        width: 56px;
        height: 56px;
    }

    .header-icon :deep(svg) {
        width: 28px;
        height: 28px;
    }

    .header-title {
        font-size: 1.5rem;
    }

    .interview-card {
        padding: 1.5rem;
    }

    .vacancy-title {
        font-size: 1.25rem;
    }

    .decline-actions {
        flex-direction: column;
    }

    .decline-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
