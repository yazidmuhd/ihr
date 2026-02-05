<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    employees: { type: Array, default: () => [] },
});

const page = usePage();
const flashMsg = computed(() => page.props?.flash?.status || "");

// Toast
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

// Show flash message as toast
if (flashMsg.value) {
    showToast(flashMsg.value, "success");
}

// Search and filter
const searchQuery = ref("");
const statusFilter = ref("all");

const filteredEmployees = computed(() => {
    let result = [...props.employees];

    // Search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((e) => {
            const searchText = [
                e.employee_no,
                e.name || e.applicant_name,
                e.email || e.applicant_email,
                e.position,
                e.department,
                e.vacancy_title,
            ]
                .join(" ")
                .toLowerCase();
            return searchText.includes(query);
        });
    }

    // Status filter
    if (statusFilter.value !== "all") {
        result = result.filter(
            (e) => (e.status || "active") === statusFilter.value,
        );
    }

    return result;
});

// Stats
const totalEmployees = computed(() => props.employees.length);
const activeEmployees = computed(
    () =>
        props.employees.filter((e) => (e.status || "active") === "active")
            .length,
);
const uniqueDepartments = computed(
    () =>
        new Set(props.employees.map((e) => e.department).filter(Boolean)).size,
);
const recentHires = computed(
    () =>
        props.employees.filter((e) => {
            // Consider employees hired in the last 30 days as recent
            // This is a simplified check - you might want to add a hire_date field
            return true; // Placeholder
        }).length,
);

const icons = {
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>',
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    userCheck:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
    building:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>',
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    hash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/></svg>',
    fileText:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    filter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>',
    trendingUp:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m23 6-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>',
    checkCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    xCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
};
</script>

<template>
    <Head title="Employees" />
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
                            <span v-html="icons.users"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">Employee Directory</h1>
                            <p class="header-subtitle">
                                Manage and view all hired employees
                            </p>
                        </div>
                    </div>

                    <div class="header-actions">
                        <div class="search-wrapper">
                            <span
                                class="search-icon"
                                v-html="icons.search"
                            ></span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="search-input"
                                placeholder="Search employees..."
                            />
                        </div>

                        <div class="filter-wrapper">
                            <span
                                class="filter-icon"
                                v-html="icons.filter"
                            ></span>
                            <select
                                v-model="statusFilter"
                                class="filter-select"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="on_leave">On Leave</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Grid -->
            <section class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-icon">
                        <span v-html="icons.users"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ totalEmployees }}</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                </div>

                <div class="stat-card stat-active">
                    <div class="stat-icon">
                        <span v-html="icons.userCheck"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ activeEmployees }}</div>
                        <div class="stat-label">Active Employees</div>
                    </div>
                </div>

                <div class="stat-card stat-departments">
                    <div class="stat-icon">
                        <span v-html="icons.building"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ uniqueDepartments }}</div>
                        <div class="stat-label">Departments</div>
                    </div>
                </div>

                <div class="stat-card stat-recent">
                    <div class="stat-icon">
                        <span v-html="icons.trendingUp"></span>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value">{{ employees.length }}</div>
                        <div class="stat-label">Recent Hires</div>
                    </div>
                </div>
            </section>

            <!-- Employees Table -->
            <section class="table-section">
                <div class="table-header">
                    <h2 class="table-title">
                        <span>Employee List</span>
                        <span class="employee-count"
                            >{{ filteredEmployees.length }}
                            {{
                                filteredEmployees.length === 1
                                    ? "employee"
                                    : "employees"
                            }}</span
                        >
                    </h2>
                </div>

                <div v-if="filteredEmployees.length" class="table-card">
                    <div class="table-wrapper">
                        <table class="employee-table">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.hash"
                                            ></span>
                                            <span>Employee No</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.user"
                                            ></span>
                                            <span>Name</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.mail"
                                            ></span>
                                            <span>Email</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.briefcase"
                                            ></span>
                                            <span>Position</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.building"
                                            ></span>
                                            <span>Department</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.fileText"
                                            ></span>
                                            <span>From Vacancy</span>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-content">
                                            <span
                                                class="th-icon"
                                                v-html="icons.checkCircle"
                                            ></span>
                                            <span>Status</span>
                                        </div>
                                    </th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(e, idx) in filteredEmployees"
                                    :key="e.id"
                                    class="table-row"
                                    :style="{ '--row-delay': `${idx * 0.03}s` }"
                                >
                                    <td>
                                        <span class="employee-number">{{
                                            e.employee_no || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <div class="employee-name-cell">
                                            <div class="employee-avatar">
                                                <span
                                                    v-html="icons.user"
                                                ></span>
                                            </div>
                                            <span class="employee-name">{{
                                                e.name ||
                                                e.applicant_name ||
                                                "-"
                                            }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="employee-email">{{
                                            e.email || e.applicant_email || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <span class="employee-position">{{
                                            e.position || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <span class="employee-department">{{
                                            e.department || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <span class="employee-vacancy">{{
                                            e.vacancy_title || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="{
                                                'status-active':
                                                    (e.status || 'active') ===
                                                    'active',
                                                'status-inactive':
                                                    (e.status || 'active') ===
                                                    'inactive',
                                                'status-leave':
                                                    (e.status || 'active') ===
                                                    'on_leave',
                                            }"
                                        >
                                            <span
                                                class="status-icon"
                                                v-html="
                                                    (e.status || 'active') ===
                                                    'active'
                                                        ? icons.checkCircle
                                                        : icons.xCircle
                                                "
                                            ></span>
                                            <span>{{
                                                e.status || "active"
                                            }}</span>
                                        </span>
                                    </td>

                                    <td class="text-right">
                                        <Link
                                            :href="`/hr/employees/${e.id}`"
                                            class="view-btn"
                                        >
                                            <span
                                                class="btn-icon"
                                                v-html="icons.eye"
                                            ></span>
                                            <span>View</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <span
                            v-html="searchQuery ? icons.search : icons.users"
                        ></span>
                    </div>
                    <h3 class="empty-title">
                        {{
                            searchQuery
                                ? "No Employees Found"
                                : "No Employees Yet"
                        }}
                    </h3>
                    <p class="empty-text">
                        {{
                            searchQuery
                                ? "Try adjusting your search or filter criteria"
                                : "Finalize interview scores and convert candidates to employees to see them here"
                        }}
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
    width: 16px;
    height: 16px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.search-icon,
.filter-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.search-icon :deep(svg),
.filter-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.th-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.th-icon :deep(svg) {
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
    max-width: 95rem;
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

.header-actions {
    display: flex;
    gap: 1rem;
}
.search-wrapper,
.filter-wrapper {
    position: relative;
}
.search-input,
.filter-select {
    padding: 0.875rem 1rem 0.875rem 3rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.9375rem;
    color: #0f172a;
    background: white;
    transition: all 0.3s;
}
.search-input {
    width: 300px;
}
.filter-select {
    width: 180px;
    cursor: pointer;
}
.search-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.search-wrapper .search-icon,
.filter-wrapper .filter-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
.stat-total .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.15),
        rgba(14, 165, 233, 0.15)
    );
    color: #0ea5e9;
}
.stat-total .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-active .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #10b981;
}
.stat-active .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-departments .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(139, 92, 246, 0.15),
        rgba(124, 58, 237, 0.15)
    );
    color: #8b5cf6;
}
.stat-departments .stat-icon :deep(svg) {
    width: 28px;
    height: 28px;
}
.stat-recent .stat-icon {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #f59e0b;
}
.stat-recent .stat-icon :deep(svg) {
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

/* Table Section */
.table-section {
}
.table-header {
    margin-bottom: 1.5rem;
}
.table-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
}
.employee-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    padding: 0.5rem 1rem;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 50px;
}

.table-card {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}
.table-wrapper {
    overflow-x: auto;
}
.employee-table {
    width: 100%;
    border-collapse: collapse;
}
.employee-table thead {
    background: linear-gradient(
        135deg,
        rgba(248, 250, 252, 0.8),
        rgba(241, 245, 249, 0.8)
    );
}
.employee-table th {
    padding: 1rem 1.25rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}
.th-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.employee-table tbody {
}
.table-row {
    border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    transition: all 0.3s;
    animation: rowFadeIn 0.6s ease-out var(--row-delay) both;
}
@keyframes rowFadeIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
.table-row:hover {
    background: rgba(6, 182, 212, 0.02);
}
.employee-table td {
    padding: 1.25rem;
    font-size: 0.875rem;
    color: #0f172a;
}

.employee-number {
    font-family: "Bricolage Grotesque", sans-serif;
    font-weight: 700;
    color: #0f172a;
}
.employee-name-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.employee-avatar {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 50%;
    color: #0ea5e9;
    flex-shrink: 0;
}
.employee-avatar :deep(svg) {
    width: 18px;
    height: 18px;
}
.employee-name {
    font-weight: 600;
    color: #0f172a;
}
.employee-email {
    color: #64748b;
}
.employee-position {
    font-weight: 500;
    color: #0f172a;
}
.employee-department {
    color: #64748b;
}
.employee-vacancy {
    color: #64748b;
    font-size: 0.8125rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: capitalize;
}
.status-active {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15),
        rgba(5, 150, 105, 0.15)
    );
    color: #065f46;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.status-inactive {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #991b1b;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.status-leave {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #92400e;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: white;
    color: #0ea5e9;
    border: 1px solid rgba(6, 182, 212, 0.3);
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}
.view-btn:hover {
    background: rgba(6, 182, 212, 0.05);
    transform: translateY(-1px);
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
    max-width: 400px;
}

/* Responsive */
@media (max-width: 1400px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 1024px) {
    .page-container {
        padding: 1rem;
    }
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-actions {
        width: 100%;
        flex-direction: column;
    }
    .search-input {
        width: 100%;
    }
    .filter-select {
        width: 100%;
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
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .stat-card {
        padding: 1.25rem;
    }
    .stat-value {
        font-size: 2rem;
    }

    .table-wrapper {
        overflow-x: scroll;
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
}
</style>
