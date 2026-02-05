<script setup>
import { computed, ref, onMounted } from "vue";
import { Link } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import Chart from "@/Components/App/ChartEnhanced.vue";

const props = defineProps({
    user: { type: Object, default: () => ({ name: "HR" }) },
    stats: {
        type: Object,
        default: () => ({
            vacancies: 0,
            applications: 0,
            interviews: 0,
            employees: 0,
        }),
    },
    byStatus: { type: Object, default: () => ({ labels: [], values: [] }) },
    byDept: { type: Object, default: () => ({ labels: [], values: [] }) },
    recentApps: { type: Array, default: () => [] },
    latestVacancies: { type: Array, default: () => [] },
});

const statusLabels = computed(() =>
    (props.byStatus?.labels || []).length ? props.byStatus.labels : ["No data"],
);
const statusValues = computed(() =>
    (props.byStatus?.values || []).length ? props.byStatus.values : [0],
);
const deptLabels = computed(() =>
    (props.byDept?.labels || []).length ? props.byDept.labels : ["No data"],
);
const deptValues = computed(() =>
    (props.byDept?.values || []).length ? props.byDept.values : [0],
);

// Animation state
const isLoaded = ref(false);

onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});

// Enhanced stats with colors and icons
const statsWithTrends = computed(() => [
    {
        label: "Open Vacancies",
        value: props.stats.vacancies,
        icon: "briefcase",
        trend: "+12%",
        trendUp: true,
        gradient: "from-cyan-400 via-blue-500 to-purple-600",
        glowColor: "rgba(6, 182, 212, 0.5)",
        description: "Active positions",
    },
    {
        label: "Applications",
        value: props.stats.applications,
        icon: "users",
        trend: "+8%",
        trendUp: true,
        gradient: "from-emerald-400 via-teal-500 to-cyan-600",
        glowColor: "rgba(16, 185, 129, 0.5)",
        description: "Total submissions",
    },
    {
        label: "Interviews",
        value: props.stats.interviews,
        icon: "calendar",
        trend: "+5%",
        trendUp: true,
        gradient: "from-violet-400 via-purple-500 to-fuchsia-600",
        glowColor: "rgba(139, 92, 246, 0.5)",
        description: "Scheduled meetings",
    },
    {
        label: "Employees",
        value: props.stats.employees,
        icon: "check",
        trend: "+3%",
        trendUp: true,
        gradient: "from-orange-400 via-amber-500 to-yellow-600",
        glowColor: "rgba(251, 146, 60, 0.5)",
        description: "Successfully hired",
    },
]);

const icons = {
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    users: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    trending: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>`,
    arrow: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`,
    sparkle: `<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>`,
    pulse: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>`,
    chartPie: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>`,
};

// Status color mapping
const getStatusStyle = (status) => {
    const statusLower = (status || "").toLowerCase();
    if (statusLower.includes("hired") || statusLower.includes("approved")) {
        return "status-success";
    } else if (statusLower.includes("shortlist")) {
        return "status-info";
    } else if (
        statusLower.includes("submitted") ||
        statusLower.includes("pending")
    ) {
        return "status-warning";
    } else if (statusLower.includes("rejected")) {
        return "status-danger";
    }
    return "status-default";
};
</script>

<template>
    <StaffLayout>
        <div class="futuristic-dashboard" :class="{ loaded: isLoaded }">
            <!-- Animated Background Effects -->
            <div class="bg-effects">
                <div class="gradient-orb orb-1"></div>
                <div class="gradient-orb orb-2"></div>
                <div class="gradient-orb orb-3"></div>
                <div class="grid-overlay"></div>
            </div>

            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-content">
                    <div class="welcome-badge">
                        <span class="badge-icon" v-html="icons.sparkle"></span>
                        <span>Welcome back</span>
                    </div>
                    <h1 class="hero-title">
                        <span class="title-line">{{
                            props.user?.name || "HR Manager"
                        }}</span>
                        <span class="title-gradient">Command Center</span>
                    </h1>
                    <p class="hero-subtitle">
                        Real-time recruitment analytics and workforce insights
                    </p>
                </div>
                <Link href="/hr/vacancies" class="hero-action">
                    <span>Manage Vacancies</span>
                    <span class="action-icon" v-html="icons.arrow"></span>
                </Link>
            </div>

            <!-- KPI Matrix -->
            <section class="kpi-matrix">
                <div
                    v-for="(stat, idx) in statsWithTrends"
                    :key="stat.label"
                    class="neo-card kpi-card"
                    :style="{
                        '--delay': `${idx * 0.15}s`,
                        '--glow-color': stat.glowColor,
                    }"
                >
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="kpi-header">
                        <div class="kpi-icon-container">
                            <div
                                class="icon-bg"
                                :class="`bg-gradient-to-br ${stat.gradient}`"
                            >
                                <span
                                    class="icon-svg"
                                    v-html="icons[stat.icon]"
                                ></span>
                            </div>
                            <div class="icon-pulse"></div>
                        </div>
                        <div
                            class="kpi-trend"
                            :class="stat.trendUp ? 'trend-up' : 'trend-down'"
                        >
                            <span
                                class="trend-icon"
                                v-html="icons.trending"
                            ></span>
                            <span>{{ stat.trend }}</span>
                        </div>
                    </div>

                    <div class="kpi-body">
                        <div class="kpi-value">{{ stat.value }}</div>
                        <div class="kpi-label">{{ stat.label }}</div>
                        <div class="kpi-description">
                            {{ stat.description }}
                        </div>
                    </div>

                    <div class="card-border"></div>
                </div>
            </section>

            <!-- Analytics Grid -->
            <section class="analytics-grid">
                <!-- Vacancies by Status - Enhanced Bar Chart -->
                <div class="neo-card chart-card chart-card-primary">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>
                    <div class="chart-corner chart-corner-tl"></div>
                    <div class="chart-corner chart-corner-br"></div>

                    <div class="chart-header">
                        <div class="header-content">
                            <div class="chart-icon">
                                <span v-html="icons.pulse"></span>
                            </div>
                            <div>
                                <h3 class="chart-title">
                                    Recruitment Pipeline
                                </h3>
                                <p class="chart-subtitle">
                                    Vacancies by status distribution
                                </p>
                            </div>
                        </div>
                        <div class="chart-badge chart-badge-live">
                            <span
                                class="badge-icon"
                                v-html="icons.pulse"
                            ></span>
                            <span>Live Data</span>
                            <span class="badge-pulse-ring"></span>
                        </div>
                    </div>

                    <div class="chart-wrapper chart-bar-wrapper">
                        <div class="chart-bg-gradient"></div>
                        <div class="chart-grid-overlay"></div>
                        <Chart
                            type="bar"
                            :labels="statusLabels"
                            :values="statusValues"
                            :height="320"
                        />
                    </div>

                    <div class="card-border"></div>
                </div>

                <!-- Department Distribution - Futuristic Pie Chart -->
                <div class="neo-card chart-card chart-card-secondary">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>
                    <div class="chart-corner chart-corner-tl"></div>
                    <div class="chart-corner chart-corner-br"></div>

                    <div class="chart-header">
                        <div class="header-content">
                            <div class="chart-icon">
                                <span v-html="icons.sparkle"></span>
                            </div>
                            <div>
                                <h3 class="chart-title">Department Matrix</h3>
                                <p class="chart-subtitle">
                                    Open roles distribution
                                </p>
                            </div>
                        </div>
                        <div class="chart-badge chart-badge-analytics">
                            <span
                                class="badge-icon"
                                v-html="icons.chartPie"
                            ></span>
                            <span>Analytics</span>
                        </div>
                    </div>

                    <div class="chart-wrapper pie-wrapper">
                        <div class="pie-bg-gradient"></div>
                        <div class="pie-glow-ring"></div>
                        <div class="pie-decorative-dots">
                            <span class="dot dot-1"></span>
                            <span class="dot dot-2"></span>
                            <span class="dot dot-3"></span>
                            <span class="dot dot-4"></span>
                        </div>
                        <Chart
                            type="pie"
                            :labels="deptLabels"
                            :values="deptValues"
                            :height="320"
                        />
                    </div>

                    <div class="card-border"></div>
                </div>
            </section>

            <!-- Data Stream Section -->
            <section class="data-stream-grid">
                <!-- Recent Applications Stream -->
                <div class="neo-card stream-card">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="stream-header">
                        <div class="header-left">
                            <div class="stream-icon">
                                <span v-html="icons.users"></span>
                            </div>
                            <div>
                                <h3 class="stream-title">Application Stream</h3>
                                <p class="stream-subtitle">
                                    Latest candidate submissions
                                </p>
                            </div>
                        </div>
                        <Link href="/hr/applications" class="stream-action">
                            <span>View All</span>
                            <span
                                class="action-icon-sm"
                                v-html="icons.arrow"
                            ></span>
                        </Link>
                    </div>

                    <div v-if="props.recentApps.length" class="stream-list">
                        <div
                            v-for="(row, idx) in props.recentApps"
                            :key="row.id"
                            class="stream-item"
                            :style="{ '--item-delay': `${idx * 0.05}s` }"
                        >
                            <div class="item-indicator"></div>
                            <div class="applicant-info">
                                <div class="applicant-avatar">
                                    {{ row.applicant.charAt(0).toUpperCase() }}
                                    <div class="avatar-ring"></div>
                                </div>
                                <div class="applicant-details">
                                    <div class="applicant-name">
                                        {{ row.applicant }}
                                    </div>
                                    <div class="applicant-meta">
                                        <span class="meta-id"
                                            >#{{ row.id }}</span
                                        >
                                        <span class="meta-separator">•</span>
                                        <span class="meta-vacancy">{{
                                            row.vacancy
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="item-right">
                                <span
                                    class="status-pill"
                                    :class="getStatusStyle(row.status)"
                                >
                                    {{ row.status }}
                                </span>
                                <span class="item-date">{{
                                    row.created_at
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <div class="empty-icon" v-html="icons.users"></div>
                        <p class="empty-text">No applications yet</p>
                    </div>

                    <div class="card-border"></div>
                </div>

                <!-- Latest Vacancies -->
                <div class="neo-card stream-card">
                    <div class="card-glow"></div>
                    <div class="card-shine"></div>

                    <div class="stream-header">
                        <div class="header-left">
                            <div class="stream-icon">
                                <span v-html="icons.briefcase"></span>
                            </div>
                            <div>
                                <h3 class="stream-title">Active Vacancies</h3>
                                <p class="stream-subtitle">
                                    Recently posted positions
                                </p>
                            </div>
                        </div>
                        <Link href="/hr/vacancies" class="stream-action">
                            <span>Browse</span>
                            <span
                                class="action-icon-sm"
                                v-html="icons.arrow"
                            ></span>
                        </Link>
                    </div>

                    <div
                        v-if="props.latestVacancies.length"
                        class="vacancy-list"
                    >
                        <div
                            v-for="(v, idx) in props.latestVacancies"
                            :key="v.id"
                            class="vacancy-item"
                            :style="{ '--item-delay': `${idx * 0.05}s` }"
                        >
                            <div class="vacancy-left">
                                <div class="vacancy-icon-box">
                                    <span v-html="icons.briefcase"></span>
                                </div>
                                <div class="vacancy-info">
                                    <div class="vacancy-title">
                                        {{ v.title }}
                                    </div>
                                    <div class="vacancy-meta">
                                        <span class="meta-dept">{{
                                            v.department || "—"
                                        }}</span>
                                        <span class="meta-separator">•</span>
                                        <span
                                            class="vacancy-status"
                                            :class="
                                                v.status === 'Open'
                                                    ? 'status-open'
                                                    : ''
                                            "
                                        >
                                            {{ v.status || "Open" }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <Link
                                :href="`/hr/vacancies/${v.id}/edit`"
                                class="vacancy-button"
                            >
                                Edit
                            </Link>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <div class="empty-icon" v-html="icons.briefcase"></div>
                        <p class="empty-text">No vacancies created yet</p>
                    </div>

                    <div class="card-border"></div>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>

<style scoped>
/* Import Futuristic Fonts */
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap");

/* CSS Variables */
:root {
    --color-primary: #06b6d4;
    --color-secondary: #8b5cf6;
    --color-accent: #f59e0b;
    --color-success: #10b981;
    --color-danger: #ef4444;
    --color-text: #f8fafc;
    --color-text-dark: #0f172a;
    --color-text-secondary: #94a3b8;
    --color-bg: #0f172a;
    --color-surface: rgba(30, 41, 59, 0.8);
    --color-border: rgba(148, 163, 184, 0.2);
    --glow-cyan: rgba(6, 182, 212, 0.6);
    --glow-purple: rgba(139, 92, 246, 0.6);
    --glow-orange: rgba(245, 158, 11, 0.6);
}

/* Dashboard Container */
.futuristic-dashboard {
    position: relative;
    min-height: 100vh;
    font-family:
        "Inter",
        -apple-system,
        BlinkMacSystemFont,
        sans-serif;
    color: var(--color-text);
    padding-bottom: 4rem;
}

/* Animated Background Effects */
.bg-effects {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 0;
    pointer-events: none;
}

.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.3;
    animation: float 20s infinite ease-in-out;
}

.orb-1 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, var(--glow-cyan) 0%, transparent 70%);
    top: -250px;
    right: -250px;
    animation-delay: 0s;
}

.orb-2 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, var(--glow-purple) 0%, transparent 70%);
    bottom: -200px;
    left: -200px;
    animation-delay: 7s;
}

.orb-3 {
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--glow-orange) 0%, transparent 70%);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    animation-delay: 14s;
}

@keyframes float {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    25% {
        transform: translate(50px, -50px) scale(1.1);
    }
    50% {
        transform: translate(-30px, 30px) scale(0.9);
    }
    75% {
        transform: translate(30px, 50px) scale(1.05);
    }
}

.grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image:
        linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
    0% {
        transform: translate(0, 0);
    }
    100% {
        transform: translate(50px, 50px);
    }
}

/* Hero Header */
.hero-header {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 3rem;
    padding: 2rem 0;
    opacity: 0;
    transform: translateY(20px);
    animation: slideUp 0.8s ease-out 0.2s forwards;
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hero-content {
    flex: 1;
}

.welcome-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 1.5rem;
    backdrop-filter: blur(10px);
}

.badge-icon {
    width: 16px;
    height: 16px;
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
    font-family: "Space Grotesk", sans-serif;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1.2;
    margin: 0 0 1rem 0;
    letter-spacing: -0.02em;
}

.title-line {
    display: block;
    color: var(--color-text);
}

.title-gradient {
    display: block;
    background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 50%, #f59e0b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 3s ease-in-out infinite;
    background-size: 200% auto;
}

@keyframes gradientShift {
    0%,
    100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-secondary);
    margin: 0;
    font-weight: 400;
}

.hero-action {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.2) 0%,
        rgba(139, 92, 246, 0.2) 100%
    );
    border: 1px solid rgba(6, 182, 212, 0.4);
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    color: var(--color-text);
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.hero-action::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transition: left 0.5s;
}

.hero-action:hover::before {
    left: 100%;
}

.hero-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(6, 182, 212, 0.4);
    border-color: rgba(6, 182, 212, 0.6);
}

.action-icon {
    width: 18px;
    height: 18px;
    transition: transform 0.3s ease;
}

.action-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.hero-action:hover .action-icon {
    transform: translateX(4px);
}

/* Neo Card Base */
.neo-card {
    position: relative;
    background: var(--color-surface);
    border-radius: 20px;
    border: 1px solid var(--color-border);
    backdrop-filter: blur(20px);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(
        circle,
        var(--glow-color, var(--glow-cyan)) 0%,
        transparent 70%
    );
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.neo-card:hover .card-glow {
    opacity: 0.3;
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
        rgba(255, 255, 255, 0.1),
        transparent
    );
    transition: left 0.6s;
    pointer-events: none;
}

.neo-card:hover .card-shine {
    left: 100%;
}

.card-border {
    position: absolute;
    inset: 0;
    border-radius: 20px;
    padding: 1px;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.3),
        rgba(139, 92, 246, 0.3)
    );
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.4s ease;
}

.neo-card:hover .card-border {
    opacity: 1;
}

/* KPI Matrix */
.kpi-matrix {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.kpi-card {
    padding: 2rem;
    opacity: 0;
    transform: translateY(30px) scale(0.95);
    animation: cardAppear 0.6s ease-out var(--delay, 0s) forwards;
}

@keyframes cardAppear {
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.kpi-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.kpi-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1.5rem;
}

.kpi-icon-container {
    position: relative;
}

.icon-bg {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
    animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.icon-svg {
    width: 30px;
    height: 30px;
    color: white;
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.3));
}

.icon-svg :deep(svg) {
    width: 100%;
    height: 100%;
}

.icon-pulse {
    position: absolute;
    top: 0;
    left: 0;
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: inherit;
    opacity: 0.5;
    animation: pulse 2s ease-out infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 0.5;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    backdrop-filter: blur(10px);
}

.trend-up {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.trend-down {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.trend-icon {
    width: 14px;
    height: 14px;
}

.trend-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.kpi-body {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.kpi-value {
    font-family: "Space Grotesk", sans-serif;
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    color: var(--color-text);
    text-shadow: 0 0 20px rgba(6, 182, 212, 0.5);
}

.kpi-label {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.kpi-description {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

/* Analytics Grid */
.analytics-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

/* Decorative Corners */
.chart-corner {
    position: absolute;
    width: 40px;
    height: 40px;
    opacity: 0;
    transition:
        opacity 0.4s ease,
        transform 0.4s ease;
}

.chart-corner::before,
.chart-corner::after {
    content: "";
    position: absolute;
    background: var(--accent-color);
}

.chart-corner-tl {
    top: 0;
    left: 0;
}

.chart-corner-tl::before {
    top: 0;
    left: 0;
    width: 3px;
    height: 20px;
}

.chart-corner-tl::after {
    top: 0;
    left: 0;
    width: 20px;
    height: 3px;
}

.chart-corner-br {
    bottom: 0;
    right: 0;
}

.chart-corner-br::before {
    bottom: 0;
    right: 0;
    width: 3px;
    height: 20px;
}

.chart-corner-br::after {
    bottom: 0;
    right: 0;
    width: 20px;
    height: 3px;
}

.chart-card:hover .chart-corner {
    opacity: 0.6;
}

.chart-card:hover .chart-corner-tl {
    transform: translate(-5px, -5px);
}

.chart-card:hover .chart-corner-br {
    transform: translate(5px, 5px);
}

.chart-card {
    padding: 2rem;
    opacity: 0;
    transform: translateY(30px);
    animation: cardAppear 0.8s ease-out 0.6s forwards;
    position: relative;
    overflow: hidden;
}

.chart-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(
        90deg,
        transparent,
        var(--accent-color),
        transparent
    );
    opacity: 0;
    transition: opacity 0.4s ease;
}

.chart-card:hover::before {
    opacity: 1;
}

.chart-card-primary {
    --accent-color: #06b6d4;
}

.chart-card-secondary {
    --accent-color: #8b5cf6;
}

.chart-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.chart-card:hover .card-glow {
    opacity: 0.4;
}

.chart-card:hover .chart-bg-gradient,
.chart-card:hover .pie-bg-gradient {
    opacity: 1;
}

.chart-card:hover .pie-glow-ring {
    opacity: 0.8;
    transform: translate(-50%, -50%) scale(1.05);
}

.chart-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.chart-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.2),
        rgba(139, 92, 246, 0.2)
    );
    border: 1px solid rgba(6, 182, 212, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
    flex-shrink: 0;
}

.chart-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.chart-title {
    font-family: "Space Grotesk", sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
}

.chart-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0;
}

.chart-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-primary);
    backdrop-filter: blur(10px);
    position: relative;
}

.badge-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Live Data Badge - Animated Pulse */
.chart-badge-live {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    border-color: rgba(16, 185, 129, 0.4);
    color: #065f46;
}

.chart-badge-live .badge-icon {
    animation: pulseActivity 2s ease-in-out infinite;
}

@keyframes pulseActivity {
    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

.badge-pulse-ring {
    position: absolute;
    left: 0.5rem;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid rgba(16, 185, 129, 0.6);
    animation: pulsRing 2s ease-out infinite;
}

@keyframes pulsRing {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

/* Analytics Badge */
.chart-badge-analytics {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(124, 58, 237, 0.15)
    );
    border-color: rgba(139, 92, 246, 0.4);
    color: #5b21b6;
}

.chart-badge-analytics .badge-icon {
    animation: rotateChart 3s ease-in-out infinite;
}

@keyframes rotateChart {
    0%,
    100% {
        transform: rotate(0deg);
    }
    50% {
        transform: rotate(15deg);
    }
}

.badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-primary);
    animation: blink 2s ease-in-out infinite;
}

@keyframes blink {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.3;
    }
}

.chart-wrapper {
    position: relative;
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(248, 250, 252, 0.8) 0%,
        rgba(241, 245, 249, 0.8) 100%
    );
    border-radius: 16px;
    overflow: hidden;
}

/* Bar Chart Enhancements */
.chart-bar-wrapper {
    position: relative;
    border: 1px solid rgba(6, 182, 212, 0.2);
}

.chart-bg-gradient {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at top left,
            rgba(6, 182, 212, 0.08) 0%,
            transparent 50%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(139, 92, 246, 0.08) 0%,
            transparent 50%
        );
    pointer-events: none;
    z-index: 0;
}

.chart-grid-overlay {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(6, 182, 212, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6, 182, 212, 0.03) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
    z-index: 0;
}

.chart-bar-wrapper :deep(canvas) {
    position: relative;
    z-index: 1;
}

/* Pie Chart Enhancements */
.pie-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    border: 1px solid rgba(139, 92, 246, 0.2);
}

.pie-bg-gradient {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at center,
            rgba(139, 92, 246, 0.05) 0%,
            transparent 60%
        ),
        conic-gradient(
            from 0deg,
            rgba(6, 182, 212, 0.03),
            rgba(139, 92, 246, 0.03),
            rgba(245, 158, 11, 0.03),
            rgba(6, 182, 212, 0.03)
        );
    pointer-events: none;
    animation: rotateConic 20s linear infinite;
    z-index: 0;
}

@keyframes rotateConic {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.pie-glow-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 280px;
    height: 280px;
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(139, 92, 246, 0.15) 0%,
        transparent 70%
    );
    opacity: 0.6;
    animation: ringPulse 3s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}

.pie-decorative-dots {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 0;
}

.dot {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.6),
        rgba(139, 92, 246, 0.6)
    );
    box-shadow: 0 0 20px rgba(6, 182, 212, 0.5);
    animation: floatDot 4s ease-in-out infinite;
}

.dot-1 {
    top: 15%;
    left: 15%;
    animation-delay: 0s;
}

.dot-2 {
    top: 15%;
    right: 15%;
    animation-delay: 1s;
}

.dot-3 {
    bottom: 15%;
    left: 15%;
    animation-delay: 2s;
}

.dot-4 {
    bottom: 15%;
    right: 15%;
    animation-delay: 3s;
}

@keyframes floatDot {
    0%,
    100% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
    }
    50% {
        transform: translateY(-15px) scale(1.2);
        opacity: 1;
    }
}

.pie-wrapper :deep(canvas) {
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 4px 20px rgba(139, 92, 246, 0.2));
}

@keyframes ringPulse {
    0%,
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 0.4;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.1);
        opacity: 0.6;
    }
}

/* Data Stream Grid */
.data-stream-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
}

.stream-card {
    padding: 2rem;
    opacity: 0;
    transform: translateY(30px);
    animation: cardAppear 0.8s ease-out 0.8s forwards;
}

.stream-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.stream-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.header-left {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.stream-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.2),
        rgba(139, 92, 246, 0.2)
    );
    border: 1px solid rgba(6, 182, 212, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
    flex-shrink: 0;
}

.stream-icon :deep(svg) {
    width: 24px;
    height: 24px;
}

.stream-title {
    font-family: "Space Grotesk", sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
}

.stream-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0;
}

.stream-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: rgba(6, 182, 212, 0.1);
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-primary);
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stream-action:hover {
    background: rgba(6, 182, 212, 0.2);
    transform: translateX(4px);
}

.action-icon-sm {
    width: 14px;
    height: 14px;
}

.action-icon-sm :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Stream List - IMPROVED READABILITY */
.stream-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stream-item {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.25rem 1.25rem 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(226, 232, 240, 0.95) 0%,
        rgba(241, 245, 249, 0.95) 100%
    );
    border: 1px solid rgba(203, 213, 225, 0.5);
    border-radius: 16px;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateX(-20px);
    animation: itemSlideIn 0.4s ease-out var(--item-delay, 0s) forwards;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@keyframes itemSlideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.stream-item:hover {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.98) 0%,
        rgba(248, 250, 252, 0.98) 100%
    );
    border-color: rgba(6, 182, 212, 0.4);
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.15);
}

.item-indicator {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 70%;
    background: linear-gradient(to bottom, #06b6d4, #8b5cf6);
    border-radius: 0 4px 4px 0;
}

.applicant-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.applicant-avatar {
    position: relative;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #06b6d4, #8b5cf6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
}

.avatar-ring {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid transparent;
    background: linear-gradient(
            135deg,
            var(--color-primary),
            var(--color-secondary)
        )
        border-box;
    -webkit-mask:
        linear-gradient(#fff 0 0) padding-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    animation: ringRotate 3s linear infinite;
}

@keyframes ringRotate {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.applicant-details {
    flex: 1;
    min-width: 0;
}

.applicant-name {
    font-weight: 700;
    font-size: 0.9375rem;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.applicant-meta {
    font-size: 0.8125rem;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.meta-id {
    font-weight: 700;
    color: #06b6d4;
}

.meta-separator {
    color: #cbd5e1;
}

.meta-vacancy {
    color: #475569;
    font-weight: 500;
}

.item-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
    flex-shrink: 0;
}

.status-pill {
    padding: 0.375rem 0.875rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: capitalize;
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.status-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.2),
        rgba(5, 150, 105, 0.2)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.4);
}

.status-info {
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.2),
        rgba(14, 165, 233, 0.2)
    );
    color: #0c4a6e;
    border: 1px solid rgba(6, 182, 212, 0.4);
}

.status-warning {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.2),
        rgba(251, 191, 36, 0.2)
    );
    color: #78350f;
    border: 1px solid rgba(245, 158, 11, 0.4);
}

.status-danger {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.2),
        rgba(220, 38, 38, 0.2)
    );
    color: #7f1d1d;
    border: 1px solid rgba(239, 68, 68, 0.4);
}

.status-default {
    background: linear-gradient(
        135deg,
        rgba(100, 116, 139, 0.2),
        rgba(71, 85, 105, 0.2)
    );
    color: #1e293b;
    border: 1px solid rgba(100, 116, 139, 0.4);
}

.item-date {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

/* Vacancy List - IMPROVED READABILITY */
.vacancy-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vacancy-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem;
    background: linear-gradient(
        135deg,
        rgba(226, 232, 240, 0.95) 0%,
        rgba(241, 245, 249, 0.95) 100%
    );
    border: 1px solid rgba(203, 213, 225, 0.5);
    border-radius: 16px;
    transition: all 0.3s ease;
    opacity: 0;
    transform: translateX(-20px);
    animation: itemSlideIn 0.4s ease-out var(--item-delay, 0s) forwards;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.vacancy-item:hover {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.98) 0%,
        rgba(248, 250, 252, 0.98) 100%
    );
    border-color: rgba(139, 92, 246, 0.4);
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(139, 92, 246, 0.15);
}

.vacancy-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.vacancy-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(168, 85, 247, 0.15)
    );
    border: 1px solid rgba(139, 92, 246, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7c3aed;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
}

.vacancy-icon-box :deep(svg) {
    width: 24px;
    height: 24px;
}

.vacancy-info {
    flex: 1;
    min-width: 0;
}

.vacancy-title {
    font-weight: 700;
    font-size: 0.9375rem;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.vacancy-meta {
    font-size: 0.8125rem;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.meta-dept {
    font-weight: 500;
}

.vacancy-status {
    font-weight: 700;
}

.status-open {
    color: #059669;
}

.vacancy-button {
    padding: 0.625rem 1.25rem;
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(168, 85, 247, 0.15)
    );
    border: 1px solid rgba(139, 92, 246, 0.3);
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 700;
    color: #6d28d9;
    text-decoration: none;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(139, 92, 246, 0.1);
}

.vacancy-button:hover {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.25),
        rgba(168, 85, 247, 0.25)
    );
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: var(--color-text-secondary);
}

.empty-icon {
    width: 64px;
    height: 64px;
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.empty-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.empty-text {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .analytics-grid,
    .data-stream-grid {
        grid-template-columns: 1fr;
    }

    .hero-header {
        flex-direction: column;
        align-items: stretch;
    }

    .hero-action {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }

    .kpi-matrix {
        grid-template-columns: 1fr;
    }

    .kpi-value {
        font-size: 2.5rem;
    }

    .chart-title,
    .stream-title {
        font-size: 1.25rem;
    }
}

@media (max-width: 640px) {
    .futuristic-dashboard {
        padding-bottom: 2rem;
    }

    .hero-header {
        padding: 1rem 0;
        margin-bottom: 2rem;
    }

    .hero-title {
        font-size: 1.75rem;
    }

    .kpi-card,
    .chart-card,
    .stream-card {
        padding: 1.5rem;
    }

    .stream-item,
    .vacancy-item {
        flex-direction: column;
        align-items: stretch;
        padding: 1rem;
    }

    .item-right {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }

    .applicant-meta,
    .vacancy-meta {
        flex-wrap: wrap;
    }
}
</style>
