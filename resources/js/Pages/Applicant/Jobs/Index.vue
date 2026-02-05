<!-- resources/js/Pages/Applicant/Jobs/Index.vue -->
<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    rows: Object,
    filters: Object,
    departments: Array,
    types: Array,
    locations: Array,
});

const q = ref(props.filters?.q ?? "");
const dept = ref(props.filters?.dept ?? "All");
const type = ref(props.filters?.type ?? "All");
const loc = ref(props.filters?.loc ?? "All");

const total = computed(() => props.rows?.total ?? 0);
const isDirty = computed(
    () =>
        q.value ||
        dept.value !== "All" ||
        type.value !== "All" ||
        loc.value !== "All",
);

/** active filter chips */
const chips = computed(() => {
    const out = [];
    if (q.value) out.push({ key: "q", label: `"${q.value}"` });
    if (dept.value !== "All") out.push({ key: "dept", label: dept.value });
    if (type.value !== "All") out.push({ key: "type", label: type.value });
    if (loc.value !== "All") out.push({ key: "loc", label: loc.value });
    return out;
});
function clearChip(k) {
    if (k === "q") q.value = "";
    if (k === "dept") dept.value = "All";
    if (k === "type") type.value = "All";
    if (k === "loc") loc.value = "All";
    runSearch(true);
}

/* skeleton loading flag */
const pending = ref(false);

/* Debounced search */
let t;
function runSearch(immediate = false) {
    clearTimeout(t);
    const doIt = () => {
        pending.value = true;
        router.get(
            "/jobs",
            { q: q.value, dept: dept.value, type: type.value, loc: loc.value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => (pending.value = false),
            },
        );
    };
    immediate ? doIt() : (t = setTimeout(doIt, 260));
}

function clearSearch() {
    if (!q.value) return;
    q.value = "";
    runSearch(true);
}

function resetFilters() {
    if (!isDirty.value) return;
    q.value = "";
    dept.value = "All";
    type.value = "All";
    loc.value = "All";
    runSearch(true);
}

watch([dept, type, loc], () => runSearch(false));

/**
 * Has the current user already applied to this vacancy?
 */
const isApplied = (job) => {
    if (!job) return false;
    if (job.applied === true || job.already_applied === true) return true;
    if (job.my_application_id || job.application_id) return true;
    const s = (
        job.my_application_status ||
        job.application_status ||
        ""
    ).toLowerCase();
    if (!s) return false;
    if (
        [
            "submitted",
            "in_review",
            "shortlisted",
            "interview_scheduled",
            "interview",
            "in_interview",
            "offered",
            "hired",
        ].includes(s)
    ) {
        return true;
    }
    if (["withdrawn", "rejected"].includes(s)) {
        return false;
    }
    return false;
};

// Icons
const icons = {
    search: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`,
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    location: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    arrow: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    reset: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>`,
    close: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>`,
    sparkles: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>`,
};
</script>

<template>
    <Head title="Browse Jobs" />

    <ApplicantLayout :showSidebar="false" content-max="max-w-7xl">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-glow"></div>
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="badge-icon" v-html="icons.sparkles"></span>
                    <span>Career Opportunities</span>
                </div>
                <h1 class="hero-title">Find Your Next Role</h1>
                <p class="hero-subtitle" aria-live="polite">
                    <span class="count-badge">{{ total }}</span>
                    {{ total === 1 ? "position" : "positions" }} waiting for
                    talented people like you
                </p>
                <Link href="/applications" class="hero-link">
                    <span>My Applications</span>
                    <span class="link-arrow" v-html="icons.arrow"></span>
                </Link>
            </div>
        </section>

        <!-- Search & Filters -->
        <section
            class="filters-section"
            role="search"
            aria-label="Job search filters"
        >
            <div class="filters-glow"></div>

            <div class="filters-header">
                <div class="header-icon" v-html="icons.search"></div>
                <div>
                    <h2 class="filters-title">Search & Filter</h2>
                    <p class="filters-subtitle">Find the perfect opportunity</p>
                </div>
            </div>

            <form @submit.prevent="runSearch(true)" class="filters-form">
                <!-- Search Input -->
                <div class="search-wrapper">
                    <div class="search-icon" v-html="icons.search"></div>
                    <input
                        v-model="q"
                        @input="runSearch(false)"
                        class="search-input"
                        placeholder="Search by title, department, or location..."
                        aria-label="Search jobs"
                    />
                    <button
                        v-if="q"
                        type="button"
                        class="clear-btn"
                        @click="clearSearch"
                        aria-label="Clear search"
                    >
                        <span v-html="icons.close"></span>
                    </button>
                </div>

                <!-- Filter Selects -->
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Department</label>
                        <select v-model="dept" class="filter-select">
                            <option value="All">All Departments</option>
                            <option
                                v-for="d in props.departments"
                                :key="d"
                                :value="d"
                            >
                                {{ d }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Type</label>
                        <select v-model="type" class="filter-select">
                            <option value="All">All Types</option>
                            <option
                                v-for="t in props.types"
                                :key="t"
                                :value="t"
                            >
                                {{ t }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Location</label>
                        <select v-model="loc" class="filter-select">
                            <option value="All">All Locations</option>
                            <option
                                v-for="l in props.locations"
                                :key="l"
                                :value="l"
                            >
                                {{ l }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button
                            type="button"
                            class="reset-btn"
                            :disabled="!isDirty"
                            @click="resetFilters"
                            aria-label="Reset filters"
                        >
                            <span class="btn-icon" v-html="icons.reset"></span>
                            <span>Reset</span>
                        </button>
                        <button type="submit" class="apply-btn">
                            <span>Apply</span>
                        </button>
                    </div>
                </div>

                <!-- Active Chips -->
                <div
                    v-if="chips.length"
                    class="chips-container"
                    aria-live="polite"
                >
                    <span class="chips-label">Active filters:</span>
                    <div class="chips-list">
                        <button
                            v-for="c in chips"
                            :key="c.key + c.label"
                            type="button"
                            class="chip"
                            @click="clearChip(c.key)"
                            :aria-label="`Remove filter ${c.label}`"
                        >
                            <span class="chip-text">{{ c.label }}</span>
                            <span
                                class="chip-close"
                                v-html="icons.close"
                            ></span>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Job Cards Grid -->
        <section class="jobs-grid" role="list">
            <!-- Loading Skeletons -->
            <article
                v-if="pending"
                v-for="i in 6"
                :key="'skeleton-' + i"
                class="job-card skeleton-card"
                aria-hidden="true"
            >
                <div class="skeleton skeleton-badge"></div>
                <div class="skeleton skeleton-title"></div>
                <div class="skeleton skeleton-meta"></div>
                <div class="skeleton skeleton-description"></div>
                <div class="skeleton skeleton-footer"></div>
            </article>

            <!-- Job Cards -->
            <article
                v-for="(v, idx) in !pending ? props.rows.data : []"
                :key="v.id"
                role="listitem"
                class="job-card"
                :style="{ '--card-delay': `${idx * 0.05}s` }"
            >
                <div class="card-glow"></div>
                <div class="card-shine"></div>

                <div class="card-header">
                    <span class="status-badge">
                        <span class="badge-pulse"></span>
                        <span>Open</span>
                    </span>
                    <div class="job-icon" v-html="icons.briefcase"></div>
                </div>

                <h3 class="job-title">
                    <Link :href="`/jobs/${v.id}`" class="title-link">
                        {{ v.title }}
                    </Link>
                </h3>

                <div class="job-meta">
                    <div class="meta-item">
                        <span class="meta-icon" v-html="icons.briefcase"></span>
                        <span>{{ v.department || "—" }}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-icon" v-html="icons.location"></span>
                        <span>{{ v.location || "—" }}</span>
                    </div>
                    <div class="meta-item" v-if="v.type">
                        <span class="type-badge">{{ v.type }}</span>
                    </div>
                </div>

                <p class="job-description">
                    {{ v.description || "—" }}
                </p>

                <div v-if="v.deadline" class="deadline">
                    <span class="deadline-icon" v-html="icons.calendar"></span>
                    <span>Apply by {{ v.deadline }}</span>
                </div>

                <div class="card-footer">
                    <Link :href="`/jobs/${v.id}`" class="details-link">
                        <span>View Details</span>
                        <span class="link-arrow" v-html="icons.arrow"></span>
                    </Link>

                    <Link
                        v-if="!isApplied(v)"
                        :href="`/jobs/${v.id}#apply`"
                        class="apply-btn-card"
                    >
                        <span>Apply Now</span>
                    </Link>
                    <span v-else class="applied-badge">
                        <span class="badge-icon" v-html="icons.check"></span>
                        <span>Applied</span>
                    </span>
                </div>
            </article>

            <!-- Empty State -->
            <div v-if="!pending && !props.rows.data.length" class="empty-state">
                <div class="empty-icon" v-html="icons.search"></div>
                <h3 class="empty-title">No Jobs Found</h3>
                <p class="empty-text">
                    We couldn't find any positions matching your criteria. Try
                    adjusting your filters.
                </p>
                <button
                    v-if="isDirty"
                    type="button"
                    class="empty-btn"
                    @click="resetFilters"
                >
                    <span class="btn-icon" v-html="icons.reset"></span>
                    <span>Reset Filters</span>
                </button>
            </div>
        </section>

        <!-- Pagination -->
        <div class="pagination" v-if="!pending && props.rows.data.length">
            <div class="pagination-info">
                Showing
                <span class="info-highlight"
                    >{{ props.rows.from || 0 }}–{{ props.rows.to || 0 }}</span
                >
                of
                <span class="info-highlight">{{ props.rows.total }}</span>
                positions
            </div>
            <div class="pagination-buttons">
                <Link
                    v-if="props.rows.prev_page_url"
                    :href="props.rows.prev_page_url"
                    preserve-state
                    class="pagination-btn"
                >
                    Prev
                </Link>
                <Link
                    v-if="props.rows.next_page_url"
                    :href="props.rows.next_page_url"
                    preserve-state
                    class="pagination-btn pagination-btn-primary"
                >
                    Next
                </Link>
            </div>
        </div>
    </ApplicantLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables */
:root {
    --color-primary: #0ea5e9;
    --color-success: #10b981;
    --color-text: #0f172a;
    --color-text-light: #64748b;
}

/* Hero Section */
.hero-section {
    position: relative;
    margin-bottom: 2rem;
    padding: 3rem 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid rgba(6, 182, 212, 0.2);
    overflow: hidden;
}

.hero-glow {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at top right,
            rgba(6, 182, 212, 0.1) 0%,
            transparent 60%
        ),
        radial-gradient(
            circle at bottom left,
            rgba(139, 92, 246, 0.1) 0%,
            transparent 60%
        );
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(139, 92, 246, 0.1)
    );
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
}

.badge-icon {
    width: 16px;
    height: 16px;
    animation: sparkle 2s ease-in-out infinite;
}

@keyframes sparkle {
    0%,
    100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
    50% {
        transform: scale(1.2) rotate(180deg);
        opacity: 0.8;
    }
}

.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.hero-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-light);
    margin-bottom: 2rem;
    font-weight: 500;
}

.count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    height: 2.5rem;
    padding: 0 0.75rem;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
    border-radius: 12px;
    font-weight: 700;
    margin: 0 0.25rem;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.hero-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 12px;
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.hero-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
    border-color: var(--color-primary);
}

.link-arrow {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.hero-link:hover .link-arrow {
    transform: translateX(4px);
}

.link-arrow :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Filters Section */
.filters-section {
    position: relative;
    margin-bottom: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.filters-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top left,
        rgba(16, 185, 129, 0.05) 0%,
        transparent 50%
    );
    border-radius: 20px;
    pointer-events: none;
}

.filters-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.header-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.1)
    );
    border-radius: 12px;
    color: var(--color-success);
}

.header-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.filters-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
}

.filters-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.filters-form {
    position: relative;
    z-index: 1;
}

/* Search Input */
.search-wrapper {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: var(--color-text-light);
    pointer-events: none;
}

.search-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.search-input {
    width: 100%;
    padding: 1rem 3rem 1rem 3.5rem;
    background: rgba(248, 250, 252, 0.8);
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: 16px;
    font-size: 1rem;
    font-weight: 500;
    color: var(--color-text);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    background: white;
    border-color: var(--color-success);
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.clear-btn {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 8px;
    color: var(--color-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
}

.clear-btn:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.clear-btn :deep(svg) {
    width: 14px;
    height: 14px;
}

/* Filters Grid */
.filters-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
}

.filter-select {
    padding: 0.75rem 1rem;
    background: rgba(248, 250, 252, 0.8);
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    background: white;
    border-color: var(--color-success);
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.filter-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    justify-content: flex-end;
}

.reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: white;
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
}

.reset-btn:not(:disabled):hover {
    background: rgba(248, 250, 252, 0.8);
    border-color: var(--color-text-light);
    transform: translateY(-1px);
}

.reset-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.apply-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 700;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s ease;
}

.apply-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-icon {
    width: 16px;
    height: 16px;
}

.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Chips */
.chips-container {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}

.chips-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-light);
    flex-shrink: 0;
}

.chips-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.1)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #065f46;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chip:hover {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border-color: #10b981;
    transform: translateY(-1px);
}

.chip-text {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chip-close {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

.chip-close :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Jobs Grid */
.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Job Card */
.job-card {
    position: relative;
    padding: 1.5rem;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
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

.card-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(16, 185, 129, 0.1) 0%,
        transparent 60%
    );
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
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
    transition: left 0.6s ease;
    pointer-events: none;
}

.job-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

.job-card:hover .card-glow {
    opacity: 1;
}

.job-card:hover .card-shine {
    left: 100%;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.status-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #065f46;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.badge-pulse {
    width: 6px;
    height: 6px;
    background: #10b981;
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

.job-icon {
    width: 40px;
    height: 40px;
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

.job-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.job-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.75rem;
    line-height: 1.3;
}

.title-link {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.title-link:hover {
    color: var(--color-primary);
}

.job-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.meta-icon {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

.meta-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.type-badge {
    padding: 0.25rem 0.625rem;
    background: rgba(6, 182, 212, 0.1);
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-primary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.job-description {
    font-size: 0.9375rem;
    line-height: 1.6;
    color: var(--color-text-light);
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.deadline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: rgba(245, 158, 11, 0.1);
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 1rem;
}

.deadline-icon {
    width: 14px;
    height: 14px;
}

.deadline-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}

.details-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-primary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.details-link:hover {
    gap: 0.75rem;
}

.apply-btn-card {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 700;
    color: white;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s ease;
}

.apply-btn-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.applied-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #065f46;
}

/* Skeleton */
.skeleton-card {
    pointer-events: none;
}

.skeleton {
    background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 8px;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.skeleton-badge {
    width: 60px;
    height: 24px;
    margin-bottom: 1rem;
}

.skeleton-title {
    width: 80%;
    height: 28px;
    margin-bottom: 0.75rem;
}

.skeleton-meta {
    width: 60%;
    height: 20px;
    margin-bottom: 1rem;
}

.skeleton-description {
    width: 100%;
    height: 60px;
    margin-bottom: 1rem;
}

.skeleton-footer {
    width: 40%;
    height: 36px;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    border: 2px dashed rgba(148, 163, 184, 0.3);
}

.empty-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 50%;
    color: var(--color-text-light);
    margin-bottom: 1.5rem;
}

.empty-icon :deep(svg) {
    width: 32px;
    height: 32px;
}

.empty-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.empty-text {
    font-size: 1rem;
    color: var(--color-text-light);
    text-align: center;
    max-width: 400px;
    margin-bottom: 1.5rem;
}

.empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    border: none;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 700;
    color: white;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    transition: all 0.3s ease;
}

.empty-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
}

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.2);
}

.pagination-info {
    font-size: 0.9375rem;
    color: var(--color-text-light);
}

.info-highlight {
    font-weight: 700;
    color: var(--color-text);
}

.pagination-buttons {
    display: flex;
    gap: 0.75rem;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.625rem 1.25rem;
    background: white;
    border: 2px solid rgba(148, 163, 184, 0.2);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-text);
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: rgba(248, 250, 252, 0.8);
    border-color: var(--color-text-light);
    transform: translateY(-1px);
}

.pagination-btn-primary {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    border-color: transparent;
    color: white;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.pagination-btn-primary:hover {
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
}

/* Responsive */
@media (max-width: 1280px) {
    .hero-section {
        padding: 2.5rem 2rem;
    }

    .filters-section {
        padding: 1.75rem;
    }

    .jobs-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    }
}

@media (max-width: 1024px) {
    .hero-title {
        font-size: 2rem;
    }

    .filters-grid {
        grid-template-columns: 1fr 1fr;
    }

    .filter-actions {
        grid-column: 1 / -1;
        flex-direction: row;
    }

    .jobs-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 1.5rem;
    }

    .hero-title {
        font-size: 1.75rem;
    }

    .hero-subtitle {
        font-size: 1rem;
    }

    .count-badge {
        min-width: 2rem;
        height: 2rem;
        padding: 0 0.5rem;
        font-size: 0.875rem;
    }

    .filters-section {
        padding: 1.5rem;
    }

    .filters-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .filter-actions {
        flex-direction: row;
        gap: 0.75rem;
    }

    .reset-btn,
    .apply-btn {
        flex: 1;
    }

    .jobs-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .job-card {
        padding: 1.25rem;
    }

    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .job-title {
        font-size: 1.125rem;
    }

    .job-meta {
        flex-direction: column;
        gap: 0.5rem;
    }

    .card-footer {
        flex-direction: column;
        gap: 0.75rem;
    }

    .details-link,
    .apply-btn-card {
        width: 100%;
        justify-content: center;
    }

    .pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }

    .pagination-buttons {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 640px) {
    .hero-section {
        padding: 1.75rem 1.25rem;
    }

    .hero-badge {
        font-size: 0.8125rem;
        padding: 0.375rem 0.875rem;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-subtitle {
        font-size: 0.9375rem;
    }

    .hero-link {
        width: 100%;
        justify-content: center;
    }

    .filters-section {
        padding: 1.25rem;
    }

    .filters-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .header-icon {
        width: 36px;
        height: 36px;
    }

    .filters-title {
        font-size: 1.125rem;
    }

    .search-input {
        padding: 0.875rem 2.5rem 0.875rem 3rem;
        font-size: 0.9375rem;
    }

    .filter-select {
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
    }

    .chips-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .chips-list {
        width: 100%;
    }

    .chip {
        font-size: 0.8125rem;
        padding: 0.375rem 0.625rem;
    }

    .job-card {
        padding: 1rem;
    }

    .job-icon {
        width: 36px;
        height: 36px;
    }

    .job-icon :deep(svg) {
        width: 18px;
        height: 18px;
    }

    .status-badge {
        padding: 0.375rem 0.75rem;
        font-size: 0.6875rem;
    }

    .job-title {
        font-size: 1rem;
    }

    .job-description {
        font-size: 0.875rem;
    }

    .empty-state {
        padding: 3rem 1.5rem;
    }

    .empty-icon {
        width: 48px;
        height: 48px;
    }

    .empty-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }

    .empty-title {
        font-size: 1.25rem;
    }

    .empty-text {
        font-size: 0.9375rem;
    }
}

@media (max-width: 480px) {
    .hero-section {
        padding: 1.5rem 1rem;
    }

    .hero-title {
        font-size: 1.375rem;
    }

    .filters-section {
        padding: 1rem;
    }

    .filter-actions {
        flex-direction: column;
        gap: 0.5rem;
    }

    .reset-btn,
    .apply-btn {
        width: 100%;
    }

    .pagination-info {
        font-size: 0.875rem;
    }

    .pagination-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
}

/* Landscape mobile phones */
@media (max-width: 896px) and (max-height: 414px) and (orientation: landscape) {
    .hero-section {
        padding: 1.5rem;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .filters-section {
        padding: 1.25rem;
    }

    .jobs-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
