<!-- resources/js/Pages/HR/Vacancies/AIRanking.vue -->
<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, computed, watch } from "vue";

const props = defineProps({
    vacancy: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
});

const q = ref("");
const open = ref({});
const sortKey = ref("score");
const sortDir = ref("desc");
const statusOverride = ref({});
const isBusy = ref({});

// Modals
const showShortlistModal = ref(false);
const showRejectModal = ref(false);
const showInReviewModal = ref(false);
const selectedCandidate = ref(null);

// Toast
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

const localRows = ref(JSON.parse(JSON.stringify(props.rows)));
watch(
    () => props.rows,
    (v) => (localRows.value = JSON.parse(JSON.stringify(v))),
);

function toggle(id) {
    open.value[id] = !open.value[id];
}

function rescore() {
    router.post(
        `/hr/vacancies/${props.vacancy.id}/ai/rescore`,
        {},
        {
            onSuccess: () => showToast("Rescoring all candidates...", "info"),
            onError: () => showToast("Failed to rescore candidates", "error"),
        },
    );
}

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase();
    let list = localRows.value.filter((r) => {
        if (!term) return true;
        const hay = [
            r.name,
            ...(r.skills_matched || []),
            ...(r.skills_missing || []),
        ]
            .join(" ")
            .toLowerCase();
        return hay.includes(term);
    });

    const mul = sortDir.value === "desc" ? -1 : 1;
    list.sort((a, b) => {
        if (sortKey.value === "score")
            return mul * ((a.score ?? 0) - (b.score ?? 0));
        if (sortKey.value === "name") return mul * a.name.localeCompare(b.name);
        if (sortKey.value === "created")
            return (
                mul *
                String(a.created_at || "").localeCompare(
                    String(b.created_at || ""),
                )
            );
        return 0;
    });

    return list;
});

function rankLabel(i) {
    return i === 0 ? "#1" : i === 1 ? "#2" : i === 2 ? "#3" : "";
}

function effectiveStatus(r) {
    return statusOverride.value[r.id] || r.status || "in_review";
}

function statusChipClass(s) {
    return s === "shortlisted"
        ? "status-shortlisted"
        : s === "rejected"
          ? "status-rejected"
          : "status-review";
}

function openShortlistModal(candidate) {
    selectedCandidate.value = candidate;
    showShortlistModal.value = true;
}

function openRejectModal(candidate) {
    selectedCandidate.value = candidate;
    showRejectModal.value = true;
}

function openInReviewModal(candidate) {
    selectedCandidate.value = candidate;
    showInReviewModal.value = true;
}

function confirmShortlist() {
    showShortlistModal.value = false;
    setStatus(selectedCandidate.value.id, "shortlisted");
}

function confirmReject() {
    showRejectModal.value = false;
    setStatus(selectedCandidate.value.id, "rejected");
}

function confirmInReview() {
    showInReviewModal.value = false;
    setStatus(selectedCandidate.value.id, "in_review");
}

function setStatus(appId, status) {
    isBusy.value[appId] = true;
    statusOverride.value[appId] = status;

    const messages = {
        shortlisted: "Candidate shortlisted successfully!",
        rejected: "Candidate rejected",
        in_review: "Candidate marked as in-review",
    };

    router.patch(
        `/hr/applications/${appId}/status`,
        { status },
        {
            preserveScroll: true,
            onSuccess: () =>
                showToast(
                    messages[status],
                    status === "rejected" ? "error" : "success",
                ),
            onFinish: () => {
                isBusy.value[appId] = false;
            },
            onError: () => {
                delete statusOverride.value[appId];
                showToast("Failed to update status", "error");
            },
        },
    );
}

const icons = {
    trophy: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6M18 9h1.5a2.5 2.5 0 0 0 0-5H18M4 22h16M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>',
    medal: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6l1.5 3 3.5.5-2.5 2.5.5 3.5-3-1.5-3 1.5.5-3.5L7 9l3.5-.5L12 6z" fill="white"/></svg>',
    award: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    sort: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5h10M11 9h7M11 13h4M3 17l3 3 3-3M6 18V4"/></svg>',
    refresh:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>',
    arrowLeft:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    userCheck:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
    userX: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M18 8l5 5M23 8l-5 5"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    chevronDown:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>',
    chevronUp:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6-6-6 6"/></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
};
</script>

<template>
    <Head :title="`AI Matches – ${vacancy.title}`" />
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
                            <span v-html="icons.trophy"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">
                                AI-Powered Candidate Ranking
                            </h1>
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
                                <span class="meta-item">{{
                                    vacancy.location || "—"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="header-actions">
                        <Link href="/hr/vacancies" class="header-btn">
                            <span
                                class="btn-icon"
                                v-html="icons.arrowLeft"
                            ></span>
                            <span>Back to Vacancies</span>
                        </Link>
                    </div>
                </div>

                <!-- Controls -->
                <div class="controls-bar">
                    <div class="search-wrapper">
                        <span class="search-icon" v-html="icons.search"></span>
                        <input
                            v-model="q"
                            type="text"
                            placeholder="Search by skills or candidate name..."
                            class="search-input"
                        />
                    </div>

                    <div class="control-group">
                        <select v-model="sortKey" class="sort-select">
                            <option value="score">Sort by Score</option>
                            <option value="name">Sort by Name</option>
                            <option value="created">
                                Sort by Applied Time
                            </option>
                        </select>

                        <button
                            class="sort-direction"
                            @click="
                                sortDir = sortDir === 'desc' ? 'asc' : 'desc'
                            "
                            :title="
                                sortDir === 'desc' ? 'Descending' : 'Ascending'
                            "
                        >
                            <span
                                v-if="sortDir === 'desc'"
                                class="icon"
                                v-html="icons.chevronDown"
                            ></span>
                            <span
                                v-else
                                class="icon"
                                v-html="icons.chevronUp"
                            ></span>
                        </button>

                        <button class="rescore-btn" @click="rescore">
                            <span
                                class="btn-icon"
                                v-html="icons.refresh"
                            ></span>
                            <span>Rescore All</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Top 3 Podium -->
            <section v-if="filtered.length >= 3" class="podium-section">
                <h2 class="podium-title">
                    <span class="title-icon" v-html="icons.star"></span>
                    <span>Top 3 Candidates</span>
                </h2>

                <div class="podium-grid">
                    <!-- 2nd Place (Left) -->
                    <div class="podium-card podium-silver">
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#2</span>
                        </div>
                        <div class="podium-name">{{ filtered[1].name }}</div>
                        <div class="podium-score">
                            {{ filtered[1].score ?? 0 }}
                        </div>
                        <div class="podium-label">Score</div>
                    </div>

                    <!-- 1st Place (Center - Tallest) -->
                    <div class="podium-card podium-gold">
                        <div class="podium-crown">
                            <span v-html="icons.trophy"></span>
                        </div>
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#1</span>
                        </div>
                        <div class="podium-name">{{ filtered[0].name }}</div>
                        <div class="podium-score">
                            {{ filtered[0].score ?? 0 }}
                        </div>
                        <div class="podium-label">Score</div>
                    </div>

                    <!-- 3rd Place (Right) -->
                    <div class="podium-card podium-bronze">
                        <div class="podium-rank">
                            <span class="rank-icon" v-html="icons.medal"></span>
                            <span class="rank-number">#3</span>
                        </div>
                        <div class="podium-name">{{ filtered[2].name }}</div>
                        <div class="podium-score">
                            {{ filtered[2].score ?? 0 }}
                        </div>
                        <div class="podium-label">Score</div>
                    </div>
                </div>
            </section>

            <!-- Candidates List -->
            <section class="candidates-section">
                <h2 class="section-title">
                    <span>All Candidates</span>
                    <span class="candidate-count"
                        >{{ filtered.length }} total</span
                    >
                </h2>

                <div class="candidates-grid">
                    <article
                        v-for="(r, idx) in filtered"
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
                            <span>{{ rankLabel(idx) }}</span>
                        </div>

                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="candidate-info">
                                <div class="candidate-name-row">
                                    <span
                                        v-if="idx < 3"
                                        class="rank-text"
                                        :class="`rank-${idx + 1}`"
                                        >{{ rankLabel(idx) }}</span
                                    >
                                    <span class="candidate-name">{{
                                        r.name
                                    }}</span>
                                </div>

                                <div class="candidate-meta">
                                    <span class="meta-item">
                                        <span
                                            class="meta-icon"
                                            v-html="icons.clock"
                                        ></span>
                                        <span
                                            >Applied
                                            {{ r.created_at || "—" }}</span
                                        >
                                    </span>
                                    <span class="meta-divider">•</span>
                                    <span
                                        class="status-chip"
                                        :class="
                                            statusChipClass(effectiveStatus(r))
                                        "
                                    >
                                        {{ effectiveStatus(r) }}
                                    </span>
                                </div>
                            </div>

                            <div class="score-badge">
                                <div class="score-label">Match Score</div>
                                <div class="score-value">
                                    {{ r.score ?? 0 }}
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">
                                    <span
                                        class="stat-icon"
                                        v-html="icons.target"
                                    ></span>
                                    <span>Skills Match</span>
                                </div>
                                <div class="stat-value">
                                    <span
                                        v-if="(r.skills_matched || []).length"
                                        >{{ r.skills_matched.join(", ") }}</span
                                    >
                                    <span v-else class="text-muted">—</span>
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-label">
                                    <span
                                        class="stat-icon"
                                        v-html="icons.briefcase"
                                    ></span>
                                    <span>Experience</span>
                                </div>
                                <div class="stat-value">
                                    Needs {{ r.experience?.required ?? 0 }}y ·
                                    Has {{ r.experience?.candidate ?? 0 }}y
                                </div>
                            </div>

                            <div class="stat-item">
                                <div class="stat-label">
                                    <span
                                        class="stat-icon"
                                        v-html="icons.award"
                                    ></span>
                                    <span>Education</span>
                                </div>
                                <div class="stat-value">
                                    <span v-if="r.education?.candidate">{{
                                        r.education.candidate
                                    }}</span>
                                    <span v-else class="text-muted">—</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions">
                            <button
                                @click="openShortlistModal(r)"
                                class="action-btn action-shortlist"
                                :disabled="isBusy[r.id]"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.userCheck"
                                ></span>
                                <span>Shortlist</span>
                            </button>

                            <button
                                @click="openInReviewModal(r)"
                                class="action-btn action-review"
                                :disabled="isBusy[r.id]"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.eye"
                                ></span>
                                <span>In-Review</span>
                            </button>

                            <button
                                @click="openRejectModal(r)"
                                class="action-btn action-reject"
                                :disabled="isBusy[r.id]"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.userX"
                                ></span>
                                <span>Reject</span>
                            </button>

                            <button
                                @click="toggle(r.id)"
                                class="action-btn action-breakdown"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="
                                        open[r.id]
                                            ? icons.chevronUp
                                            : icons.chevronDown
                                    "
                                ></span>
                                <span
                                    >{{
                                        open[r.id] ? "Hide" : "View"
                                    }}
                                    Details</span
                                >
                            </button>
                        </div>

                        <!-- Breakdown -->
                        <transition name="slide">
                            <div v-if="open[r.id]" class="breakdown-panel">
                                <div class="breakdown-grid">
                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            Matched Skills
                                        </div>
                                        <div class="breakdown-value">
                                            <span
                                                v-if="
                                                    (r.skills_matched || [])
                                                        .length
                                                "
                                                >{{
                                                    r.skills_matched.join(", ")
                                                }}</span
                                            >
                                            <span v-else class="text-muted"
                                                >None</span
                                            >
                                        </div>
                                    </div>

                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            Missing Skills
                                        </div>
                                        <div class="breakdown-value">
                                            <span
                                                v-if="
                                                    (r.skills_missing || [])
                                                        .length
                                                "
                                                >{{
                                                    r.skills_missing.join(", ")
                                                }}</span
                                            >
                                            <span v-else class="text-muted"
                                                >None</span
                                            >
                                        </div>
                                    </div>

                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            Experience Details
                                        </div>
                                        <div class="breakdown-value">
                                            Required:
                                            {{
                                                r.experience?.required ?? 0
                                            }}
                                            year(s) • Candidate:
                                            {{
                                                r.experience?.candidate ?? 0
                                            }}
                                            year(s)
                                        </div>
                                    </div>

                                    <div class="breakdown-item">
                                        <div class="breakdown-label">
                                            Education Details
                                        </div>
                                        <div class="breakdown-value">
                                            Required:
                                            {{ r.education?.required || "—" }} •
                                            Candidate:
                                            {{ r.education?.candidate || "—" }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-if="!filtered.length" class="empty-state">
                    <div class="empty-icon">
                        <span v-html="icons.search"></span>
                    </div>
                    <h3 class="empty-title">No Candidates Found</h3>
                    <p class="empty-text">
                        No candidates match your current search criteria
                    </p>
                </div>
            </section>

            <!-- Shortlist Modal -->
            <transition name="modal">
                <div
                    v-if="showShortlistModal"
                    class="modal-backdrop"
                    @click="showShortlistModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showShortlistModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-success">
                            <span v-html="icons.userCheck"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Shortlist Candidate?</h3>
                        <p class="modal-text">
                            Add
                            <strong>{{ selectedCandidate?.name }}</strong> to
                            the shortlist for further review?
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showShortlistModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmShortlist"
                                class="modal-btn modal-btn-confirm"
                            >
                                Shortlist
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
                            <strong>{{ selectedCandidate?.name }}</strong
                            >?
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
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- In-Review Modal -->
            <transition name="modal">
                <div
                    v-if="showInReviewModal"
                    class="modal-backdrop"
                    @click="showInReviewModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showInReviewModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-info">
                            <span v-html="icons.eye"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Mark as In-Review?</h3>
                        <p class="modal-text">
                            Set <strong>{{ selectedCandidate?.name }}</strong
                            >'s status to in-review?
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showInReviewModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmInReview"
                                class="modal-btn modal-btn-confirm"
                            >
                                Confirm
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
.meta-icon {
    display: inline-flex;
    width: 14px;
    height: 14px;
}
.meta-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.stat-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.stat-icon :deep(svg) {
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
.icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Page Container */
.page-container {
    max-width: 90rem;
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
    position: sticky;
    top: 1rem;
    z-index: 50;
    padding: 2rem;
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
    box-shadow: 0 20px 60px -40px rgba(14, 165, 233, 0.45);
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
    margin-bottom: 1.5rem;
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
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    border-radius: 16px;
    color: #f59e0b;
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
    border: 2px solid rgba(245, 158, 11, 0.4);
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

/* Controls */
.controls-bar {
    position: relative;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}
.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 400px;
}
.search-input {
    width: 100%;
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
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

.control-group {
    display: flex;
    gap: 0.75rem;
}
.sort-select {
    padding: 0.875rem 2.5rem 0.875rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.875rem;
    color: #0f172a;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
}
.sort-select:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.sort-direction {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s;
}
.sort-direction:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    color: #0ea5e9;
}
.rescore-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.rescore-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

/* Podium Section */
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
    left: 50%;
    transform: translateX(-50%);
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
        transform: translateX(-50%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) scale(1);
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

.status-chip {
    display: inline-flex;
    padding: 0.375rem 0.875rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    text-transform: capitalize;
}
.status-shortlisted {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.status-rejected {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.status-review {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    color: #1e40af;
    border: 1px solid rgba(59, 130, 246, 0.3);
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.stat-item {
}
.stat-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.stat-value {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
}
.text-muted {
    color: #94a3b8;
}

/* Actions */
.card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
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
.action-shortlist {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-shortlist:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.action-review {
    background: white;
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.action-review:hover:not(:disabled) {
    background: rgba(59, 130, 246, 0.05);
    transform: translateY(-2px);
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
.action-breakdown {
    background: white;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
    margin-left: auto;
}
.action-breakdown:hover {
    background: rgba(148, 163, 184, 0.05);
}
.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Breakdown Panel */
.breakdown-panel {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: rgba(248, 250, 252, 0.8);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
}
.breakdown-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}
.breakdown-item {
}
.breakdown-label {
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.breakdown-value {
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.6;
}

.slide-enter-active {
    animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-leave-active {
    animation: slideUp 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        max-height: 500px;
        transform: translateY(0);
    }
}
@keyframes slideUp {
    from {
        opacity: 1;
        max-height: 500px;
    }
    to {
        opacity: 0;
        max-height: 0;
    }
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
    .controls-bar {
        flex-direction: column;
    }
    .search-wrapper {
        max-width: none;
    }
    .podium-grid {
        grid-template-columns: 1fr;
    }
    .podium-card {
        min-height: auto !important;
    }
    .stats-grid {
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
    .control-group {
        flex-direction: column;
        width: 100%;
    }
    .sort-select {
        width: 100%;
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
    .action-breakdown {
        margin-left: 0;
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
    .breakdown-grid {
        grid-template-columns: 1fr;
    }
    .modal-content {
        padding: 1.5rem;
    }
}
</style>
