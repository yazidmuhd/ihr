<script setup>
import { Head, Link } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, computed } from "vue";

const props = defineProps({
    vacancies: { type: Array, default: () => [] },
});

const q = ref("");
const filtered = computed(() => {
    const t = q.value.trim().toLowerCase();
    if (!t) return props.vacancies;
    return props.vacancies.filter((v) =>
        [v.title, v.department, v.location].join(" ").toLowerCase().includes(t),
    );
});

const totalShortlisted = computed(() => {
    return props.vacancies.reduce(
        (sum, v) => sum + (v.shortlisted_count || 0),
        0,
    );
});

const totalInReview = computed(() => {
    return props.vacancies.reduce(
        (sum, v) => sum + (v.in_review_count || 0),
        0,
    );
});

const totalApplications = computed(() => {
    return props.vacancies.reduce((sum, v) => sum + (v.total_apps || 0), 0);
});

const icons = {
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    building:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>',
    mapPin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    userCheck:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    fileText:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    trendingUp:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m23 6-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>',
    checkCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
};
</script>

<template>
    <Head title="Shortlist – Pick a vacancy" />
    <StaffLayout>
        <div class="page-container">
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
                            <h1 class="header-title">Shortlist Management</h1>
                            <p class="header-subtitle">
                                Select a vacancy to view and manage shortlisted
                                candidates
                            </p>
                        </div>
                    </div>

                    <div class="search-wrapper">
                        <span class="search-icon" v-html="icons.search"></span>
                        <input
                            v-model="q"
                            type="text"
                            class="search-input"
                            placeholder="Search by vacancy, department, or location..."
                        />
                    </div>
                </div>
            </section>

            <!-- Stats Summary -->
            <section class="stats-grid">
                <div class="stat-card stat-shortlisted">
                    <div class="stat-icon">
                        <span v-html="icons.userCheck"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ totalShortlisted }}</div>
                        <div class="stat-label">Total Shortlisted</div>
                    </div>
                </div>

                <div class="stat-card stat-review">
                    <div class="stat-icon">
                        <span v-html="icons.eye"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ totalInReview }}</div>
                        <div class="stat-label">In Review</div>
                    </div>
                </div>

                <div class="stat-card stat-total">
                    <div class="stat-icon">
                        <span v-html="icons.fileText"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ totalApplications }}</div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
            </section>

            <!-- Vacancies Grid -->
            <section class="vacancies-section">
                <h2 class="section-title">
                    <span>Open Vacancies</span>
                    <span class="vacancy-count"
                        >{{ filtered.length }} available</span
                    >
                </h2>

                <div class="vacancies-grid">
                    <article
                        v-for="(v, idx) in filtered"
                        :key="v.id"
                        class="vacancy-card"
                        :style="{ '--card-delay': `${idx * 0.05}s` }"
                    >
                        <!-- Status Badge -->
                        <div
                            class="status-badge"
                            :class="{ 'status-open': v.status === 'open' }"
                        >
                            <span
                                class="status-icon"
                                v-html="icons.checkCircle"
                            ></span>
                            <span>{{
                                (v.status || "open").toUpperCase()
                            }}</span>
                        </div>

                        <!-- Card Header -->
                        <div class="card-header">
                            <div class="vacancy-icon">
                                <span v-html="icons.briefcase"></span>
                            </div>
                            <div class="vacancy-info">
                                <h3 class="vacancy-title">{{ v.title }}</h3>
                                <div class="vacancy-meta">
                                    <span class="meta-item">
                                        <span
                                            class="meta-icon"
                                            v-html="icons.building"
                                        ></span>
                                        <span>{{ v.department || "—" }}</span>
                                    </span>
                                    <span class="meta-divider">•</span>
                                    <span class="meta-item">
                                        <span
                                            class="meta-icon"
                                            v-html="icons.mapPin"
                                        ></span>
                                        <span>{{ v.location || "—" }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="card-stats">
                            <div class="stat-item stat-shortlisted">
                                <div class="stat-number">
                                    {{ v.shortlisted_count || 0 }}
                                </div>
                                <div class="stat-text">Shortlisted</div>
                            </div>
                            <div class="stat-item stat-review">
                                <div class="stat-number">
                                    {{ v.in_review_count || 0 }}
                                </div>
                                <div class="stat-text">In Review</div>
                            </div>
                            <div class="stat-item stat-apps">
                                <div class="stat-number">
                                    {{ v.total_apps || 0 }}
                                </div>
                                <div class="stat-text">Total Apps</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions">
                            <Link
                                :href="`/hr/shortlist/vacancy/${v.id}`"
                                class="action-btn action-primary"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.users"
                                ></span>
                                <span>View Shortlisted</span>
                            </Link>
                            <Link
                                :href="`/hr/vacancies/${v.id}/ai`"
                                class="action-btn action-secondary"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.target"
                                ></span>
                                <span>AI Ranking</span>
                            </Link>
                        </div>
                    </article>
                </div>

                <!-- Empty State -->
                <div v-if="!filtered.length" class="empty-state">
                    <div class="empty-icon">
                        <span v-html="icons.search"></span>
                    </div>
                    <h3 class="empty-title">No Vacancies Found</h3>
                    <p class="empty-text">
                        No vacancies match your search criteria
                    </p>
                </div>
            </section>
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
.status-icon {
    display: inline-flex;
    width: 14px;
    height: 14px;
}
.status-icon :deep(svg) {
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

/* Header */
.header-card {
    position: relative;
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
    align-items: center;
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
    flex-shrink: 0;
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

.search-wrapper {
    position: relative;
    width: 100%;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}
.stat-card {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.5rem;
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.3s;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
}
.stat-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    flex-shrink: 0;
}
.stat-shortlisted .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #10b981;
}
.stat-shortlisted .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-review .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.15),
        rgba(37, 99, 235, 0.15)
    );
    color: #3b82f6;
}
.stat-review .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-total .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(124, 58, 237, 0.15)
    );
    color: #8b5cf6;
}
.stat-total .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-content {
    flex: 1;
}
.stat-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.25rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 0.25rem;
}
.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
}

/* Vacancies Section */
.vacancies-section {
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
.vacancy-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    padding: 0.5rem 1rem;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 50px;
}

.vacancies-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 1.5rem;
}
.vacancy-card {
    position: relative;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 1.5rem;
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
.vacancy-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
    border-color: rgba(6, 182, 212, 0.3);
}

/* Status Badge */
.status-badge {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    background: rgba(148, 163, 184, 0.1);
    color: #64748b;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.025em;
}
.status-open {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

/* Card Header */
.card-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.vacancy-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 12px;
    color: #0ea5e9;
    flex-shrink: 0;
}
.vacancy-icon :deep(svg) {
    width: 24px;
    height: 24px;
}
.vacancy-info {
    flex: 1;
    min-width: 0;
    padding-right: 3rem;
}
.vacancy-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.5rem;
    line-height: 1.3;
}
.vacancy-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.8125rem;
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

/* Card Stats */
.card-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding: 1.25rem;
    background: rgba(248, 250, 252, 0.8);
    border-radius: 12px;
    margin-bottom: 1.25rem;
}
.stat-item {
    text-align: center;
}
.stat-number {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.375rem;
}
.stat-shortlisted .stat-number {
    background: linear-gradient(135deg, #10b981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stat-review .stat-number {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stat-apps .stat-number {
    background: linear-gradient(135deg, #64748b, #475569);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stat-text {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 0.75rem;
}
.action-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}
.action-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.action-secondary {
    background: white;
    color: #0ea5e9;
    border: 1px solid rgba(6, 182, 212, 0.3);
}
.action-secondary:hover {
    background: rgba(6, 182, 212, 0.05);
    transform: translateY(-2px);
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

/* Responsive */
@media (max-width: 1024px) {
    .page-container {
        padding: 1rem;
    }
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    .search-wrapper {
        max-width: none;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .vacancies-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

    .stats-grid {
        gap: 1rem;
    }
    .stat-card {
        padding: 1.25rem;
    }
    .stat-value {
        font-size: 2rem;
    }

    .vacancies-grid {
        grid-template-columns: 1fr;
    }
    .vacancy-card {
        padding: 1.25rem;
    }
    .card-actions {
        flex-direction: column;
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

    .stat-icon {
        width: 48px;
        height: 48px;
    }
    .stat-icon :deep(svg) {
        width: 24px;
        height: 24px;
    }
    .stat-value {
        font-size: 1.75rem;
    }

    .vacancy-card {
        padding: 1rem;
    }
    .card-header {
        flex-direction: column;
    }
    .vacancy-info {
        padding-right: 0;
    }
    .card-stats {
        grid-template-columns: 1fr;
        text-align: left;
    }
}
</style>
