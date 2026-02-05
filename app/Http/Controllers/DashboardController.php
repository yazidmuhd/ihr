<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import Chart from "@/Components/App/Chart.vue";

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

// Calculate percentage changes (you can enhance this with real previous period data)
const statsWithTrends = computed(() => [
    {
        label: "Open Vacancies",
        value: props.stats.vacancies,
        icon: "briefcase",
        trend: "+12%",
        trendUp: true,
        color: "from-blue-500 to-cyan-500",
    },
    {
        label: "Applications",
        value: props.stats.applications,
        icon: "users",
        trend: "+8%",
        trendUp: true,
        color: "from-emerald-500 to-teal-500",
    },
    {
        label: "Interviews",
        value: props.stats.interviews,
        icon: "calendar",
        trend: "+5%",
        trendUp: true,
        color: "from-violet-500 to-purple-500",
    },
    {
        label: "Employees",
        value: props.stats.employees,
        icon: "check",
        trend: "+3%",
        trendUp: true,
        color: "from-orange-500 to-amber-500",
    },
]);

const icons = {
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    users: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>`,
    trending: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>`,
    arrow: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`,
};
</script>

<template>
    <StaffLayout>
        <div class="dashboard-container">
            <!-- Enhanced Header -->
            <div class="dashboard-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="dashboard-title">
                            <span class="title-gradient">HR</span> Dashboard
                        </h1>
                        <p class="dashboard-subtitle">
                            Welcome back,
                            <span class="user-name">{{
                                props.user?.name || "HR"
                            }}</span>
                        </p>
                    </div>

                    <Link href="/hr/vacancies" class="action-button">
                        <span>Manage Vacancies</span>
                        <span class="button-icon" v-html="icons.arrow"></span>
                    </Link>
                </div>
            </div>

            <!-- Enhanced KPI Cards -->
            <section class="kpi-grid">
                <div
                    v-for="(stat, idx) in statsWithTrends"
                    :key="stat.label"
                    class="kpi-card"
                    :style="{ animationDelay: `${idx * 0.1}s` }"
                >
                    <div class="kpi-icon-wrapper">
                        <div
                            class="kpi-icon-bg"
                            :class="`bg-gradient-to-br ${stat.color}`"
                        >
                            <span
                                class="kpi-icon"
                                v-html="icons[stat.icon]"
                            ></span>
                        </div>
                    </div>

                    <div class="kpi-content">
                        <div class="kpi-label">{{ stat.label }}</div>
                        <div class="kpi-value-row">
                            <div class="kpi-value">{{ stat.value }}</div>
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
                    </div>

                    <div class="kpi-decoration"></div>
                </div>
            </section>

            <!-- Enhanced Charts Section -->
            <section class="charts-grid">
                <div class="chart-card" style="animation-delay: 0.4s">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">Vacancies by Status</h3>
                            <p class="chart-subtitle">
                                Current recruitment pipeline
                            </p>
                        </div>
                        <div class="chart-badge">Bar Chart</div>
                    </div>
                    <div class="chart-content">
                        <Chart
                            type="bar"
                            :labels="statusLabels"
                            :values="statusValues"
                            :height="280"
                        />
                    </div>
                </div>

                <div class="chart-card" style="animation-delay: 0.5s">
                    <div class="chart-header">
                        <div>
                            <h3 class="chart-title">
                                Open Roles by Department
                            </h3>
                            <p class="chart-subtitle">Distribution overview</p>
                        </div>
                        <div class="chart-badge">Pie Chart</div>
                    </div>
                    <div class="chart-content">
                        <Chart
                            type="pie"
                            :labels="deptLabels"
                            :values="deptValues"
                            :height="280"
                        />
                    </div>
                </div>
            </section>

            <!-- Enhanced Data Tables Section -->
            <section class="tables-grid">
                <!-- Recent Applications -->
                <div class="data-card" style="animation-delay: 0.6s">
                    <div class="data-header">
                        <div>
                            <h3 class="data-title">Recent Applications</h3>
                            <p class="data-subtitle">
                                Latest candidate submissions
                            </p>
                        </div>
                        <Link
                            href="/hr/applications"
                            class="view-all-button"
                        >
                            <span>View All</span>
                            <span
                                class="button-icon-sm"
                                v-html="icons.arrow"
                            ></span>
                        </Link>
                    </div>

                    <div v-if="props.recentApps.length" class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Applicant</th>
                                    <th>Vacancy</th>
                                    <th>Status</th>
                                    <th class="text-right">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in props.recentApps"
                                    :key="row.id"
                                    class="table-row"
                                >
                                    <td class="table-id">#{{ row.id }}</td>
                                    <td class="table-name">
                                        <div class="applicant-avatar">
                                            {{
                                                row.applicant
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <span>{{ row.applicant }}</span>
                                    </td>
                                    <td class="table-text">
                                        {{ row.vacancy }}
                                    </td>
                                    <td>
                                        <span class="status-badge">{{
                                            row.status
                                        }}</span>
                                    </td>
                                    <td class="table-date text-right">
                                        {{ row.created_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="empty-state">
                        <div class="empty-icon" v-html="icons.users"></div>
                        <p>No applications yet</p>
                    </div>
                </div>

                <!-- Latest Vacancies -->
                <div class="data-card" style="animation-delay: 0.7s">
                    <div class="data-header">
                        <div>
                            <h3 class="data-title">Latest Vacancies</h3>
                            <p class="data-subtitle">Recently posted roles</p>
                        </div>
                        <Link href="/hr/vacancies" class="view-all-button">
                            <span>Browse</span>
                            <span
                                class="button-icon-sm"
                                v-html="icons.arrow"
                            ></span>
                        </Link>
                    </div>

                    <ul
                        v-if="props.latestVacancies.length"
                        class="vacancies-list"
                    >
                        <li
                            v-for="v in props.latestVacancies"
                            :key="v.id"
                            class="vacancy-item"
                        >
                            <div class="vacancy-content">
                                <div class="vacancy-icon-wrapper">
                                    <span
                                        class="vacancy-icon"
                                        v-html="icons.briefcase"
                                    ></span>
                                </div>
                                <div class="vacancy-details">
                                    <div class="vacancy-title">
                                        {{ v.title }}
                                    </div>
                                    <div class="vacancy-meta">
                                        <span>{{
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
                                            >{{ v.status || "Open" }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <Link
                                :href="`/hr/vacancies/${v.id}/edit`"
                                class="vacancy-edit-button"
                            >
                                Edit
                            </Link>
                        </li>
                    </ul>
                    <div v-else class="empty-state">
                        <div class="empty-icon" v-html="icons.briefcase"></div>
                        <p>No vacancies created yet</p>
                    </div>
                </div>
            </section>
        </div>
    </StaffLayout>
</template>

<style scoped>
/* Import Google Fonts - Choose a distinctive pairing */
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables for theming */
:root {
    --color-primary: #0ea5e9;
    --color-primary-dark: #0284c7;
    --color-secondary: #8b5cf6;
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-text: #0f172a;
    --color-text-light: #64748b;
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    --radius: 16px;
    --radius-lg: 24px;
}

/* Global Dashboard Styles */
.dashboard-container {
    font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont,
        sans-serif;
    color: var(--color-text);
}

/* Header Styles */
.dashboard-header {
    margin-bottom: 2.5rem;
    animation: slideDown 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.header-text {
    flex: 1;
    min-width: 250px;
}

.dashboard-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.5rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.02em;
    color: var(--color-text);
}

.title-gradient {
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dashboard-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-light);
    margin: 0;
    font-weight: 400;
}

.user-name {
    font-weight: 600;
    color: var(--color-text);
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
}

.button-icon {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.action-button:hover .button-icon {
    transform: translateX(3px);
}

/* KPI Cards */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.kpi-card {
    position: relative;
    background: var(--color-card);
    border-radius: var(--radius-lg);
    padding: 1.75rem;
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    border: 1px solid rgba(148, 163, 184, 0.1);
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.kpi-icon-wrapper {
    flex-shrink: 0;
}

.kpi-icon-bg {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: transform 0.3s ease;
}

.kpi-card:hover .kpi-icon-bg {
    transform: rotate(-5deg) scale(1.05);
}

.kpi-icon {
    width: 28px;
    height: 28px;
    color: white;
}

.kpi-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.kpi-content {
    flex: 1;
    min-width: 0;
}

.kpi-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text-light);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.kpi-value-row {
    display: flex;
    align-items: baseline;
    gap: 1rem;
}

.kpi-value {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    color: var(--color-text);
}

.kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.trend-up {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.trend-down {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.trend-icon {
    width: 12px;
    height: 12px;
}

.trend-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.kpi-decoration {
    position: absolute;
    top: -20px;
    right: -20px;
    width: 100px;
    height: 100px;
    background: radial-gradient(
        circle,
        rgba(14, 165, 233, 0.08) 0%,
        transparent 70%
    );
    pointer-events: none;
}

/* Charts Section */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.chart-card {
    background: var(--color-card);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    border: 1px solid rgba(148, 163, 184, 0.1);
}

.chart-card:hover {
    box-shadow: var(--shadow-xl);
}

.chart-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.chart-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
}

.chart-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin: 0;
}

.chart-badge {
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text-light);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.chart-content {
    margin-top: 1.5rem;
}

/* Data Tables Section */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
    gap: 1.5rem;
}

.data-card {
    background: var(--color-card);
    border-radius: var(--radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    border: 1px solid rgba(148, 163, 184, 0.1);
}

.data-card:hover {
    box-shadow: var(--shadow-xl);
}

.data-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.data-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
}

.data-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin: 0;
}

.view-all-button {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: rgba(14, 165, 233, 0.1);
    color: var(--color-primary);
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.view-all-button:hover {
    background: rgba(14, 165, 233, 0.2);
    transform: translateX(2px);
}

.button-icon-sm {
    width: 14px;
    height: 14px;
}

.button-icon-sm :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Table Styles */
.table-wrapper {
    overflow-x: auto;
    margin-top: 1.25rem;
    border-radius: var(--radius);
    border: 1px solid #e2e8f0;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.data-table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-light);
    border-bottom: 2px solid #e2e8f0;
}

.data-table tbody tr {
    transition: background-color 0.2s ease;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
}

.table-id {
    color: var(--color-text-light);
    font-weight: 500;
    font-size: 0.8125rem;
}

.table-name {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    color: var(--color-text);
}

.applicant-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
    flex-shrink: 0;
}

.table-text {
    color: var(--color-text-light);
}

.status-badge {
    display: inline-flex;
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text);
}

.table-date {
    color: var(--color-text-light);
    font-size: 0.8125rem;
}

/* Vacancies List */
.vacancies-list {
    list-style: none;
    padding: 0;
    margin: 1.25rem 0 0 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.vacancy-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-radius: var(--radius);
    border: 1px solid #e2e8f0;
    background: #fafafa;
    transition: all 0.3s ease;
}

.vacancy-item:hover {
    background: white;
    border-color: var(--color-primary);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.1);
    transform: translateX(4px);
}

.vacancy-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.vacancy-icon-wrapper {
    flex-shrink: 0;
}

.vacancy-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
}

.vacancy-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.vacancy-details {
    flex: 1;
    min-width: 0;
}

.vacancy-title {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.25rem;
    font-size: 0.9375rem;
}

.vacancy-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--color-text-light);
}

.meta-separator {
    color: #cbd5e1;
}

.vacancy-status {
    font-weight: 600;
}

.status-open {
    color: var(--color-success);
}

.vacancy-edit-button {
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: var(--radius);
    color: var(--color-text);
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.vacancy-edit-button:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    color: var(--color-text-light);
}

.empty-icon {
    width: 48px;
    height: 48px;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.empty-state p {
    margin: 0;
    font-size: 0.9375rem;
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-title {
        font-size: 2rem;
    }

    .kpi-grid {
        grid-template-columns: 1fr;
    }

    .charts-grid {
        grid-template-columns: 1fr;
    }

    .tables-grid {
        grid-template-columns: 1fr;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
    }

    .action-button {
        justify-content: center;
    }
}

@media (max-width: 640px) {
    .dashboard-title {
        font-size: 1.75rem;
    }

    .kpi-card {
        padding: 1.5rem;
    }

    .chart-card,
    .data-card {
        padding: 1.5rem;
    }
}
</style>