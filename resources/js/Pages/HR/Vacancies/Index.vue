<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import HrLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    rows: Object, // paginator
    filters: Object, // { q, dept, status, type }
    departments: Array,
    statuses: Array,
    types: Array,
});

const q = ref(props.filters.q || "");
const dept = ref(props.filters.dept || "All");
const status = ref(props.filters.status || "All");
const type = ref(props.filters.type || "All");

// Modal states
const showStatusModal = ref(false);
const showDeleteModal = ref(false);
const activeVacancy = ref(null);
const pendingStatus = ref("");

// Toast notification
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

function applyFilters() {
    router.get(
        "/hr/vacancies",
        {
            q: q.value,
            dept: dept.value,
            status: status.value,
            type: type.value,
        },
        { preserveState: true, replace: true },
    );
}

function resetFilters() {
    q.value = "";
    dept.value = "All";
    status.value = "All";
    type.value = "All";
    applyFilters();
}

function openStatusModal(vacancy, newStatus) {
    activeVacancy.value = vacancy;
    pendingStatus.value = newStatus;
    showStatusModal.value = true;
}

function confirmStatusChange() {
    if (!activeVacancy.value) return;

    router.patch(
        `/hr/vacancies/${activeVacancy.value.id}/status`,
        { status: pendingStatus.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                showStatusModal.value = false;
                showToast(
                    `Vacancy status changed to ${pendingStatus.value}`,
                    "success",
                );
            },
            onError: () => {
                showStatusModal.value = false;
                showToast("Failed to update vacancy status", "error");
            },
        },
    );
}

function openDeleteModal(vacancy) {
    activeVacancy.value = vacancy;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!activeVacancy.value) return;

    router.delete(`/hr/vacancies/${activeVacancy.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            showToast("Vacancy deleted successfully", "success");
        },
        onError: () => {
            showDeleteModal.value = false;
            showToast("Failed to delete vacancy", "error");
        },
    });
}

watch([dept, status, type], applyFilters);

const icons = {
    search: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`,
    filter: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>`,
    plus: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>`,
    edit: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
    ai: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2l1.5 5.5L19 9l-5.5 1.5L12 16l-1.5-5.5L5 9l5.5-1.5L12 2z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>`,
    close: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
    archive: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>`,
    trash: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>`,
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    refresh: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>`,
    check: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M20 6L9 17l-5-5"/></svg>`,
    alertCircle: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>`,
    alertTriangle: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>`,
    x: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
};
</script>

<template>
    <Head title="Vacancies" />
    <HrLayout>
        <div class="vacancies-page">
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
                                  ? icons.alertCircle
                                  : icons.alertTriangle
                        "
                    ></span>
                    <span class="toast-message">{{ toast.message }}</span>
                </div>
            </transition>

            <!-- Header Section -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-icon">
                        <span v-html="icons.briefcase"></span>
                    </div>
                    <div class="header-text">
                        <h1 class="page-title">Vacancies Management</h1>
                        <p class="page-subtitle">
                            Create and manage open job positions
                        </p>
                    </div>
                </div>
                <Link href="/hr/vacancies/create" class="create-button">
                    <span class="button-icon" v-html="icons.plus"></span>
                    <span>New Vacancy</span>
                    <div class="button-glow"></div>
                </Link>
            </div>

            <!-- Filters Section -->
            <section class="filters-card">
                <div class="filters-header">
                    <div class="header-left">
                        <span class="filter-icon" v-html="icons.filter"></span>
                        <div>
                            <h2 class="filters-title">Filter Vacancies</h2>
                            <p class="filters-subtitle">
                                Search and narrow by department, type, and
                                status
                            </p>
                        </div>
                    </div>
                </div>

                <div class="filters-body">
                    <form class="filters-grid" @submit.prevent="applyFilters">
                        <!-- Search Input -->
                        <div class="filter-group search-group">
                            <label class="filter-label">Search</label>
                            <div class="search-wrapper">
                                <span
                                    class="search-icon"
                                    v-html="icons.search"
                                ></span>
                                <input
                                    v-model="q"
                                    type="text"
                                    class="filter-input search-input"
                                    placeholder="Title, department, location..."
                                    @keyup.enter="applyFilters"
                                />
                            </div>
                        </div>

                        <!-- Department Filter -->
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

                        <!-- Type Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Employment Type</label>
                            <select v-model="type" class="filter-select">
                                <option value="All">All Types</option>
                                <option
                                    v-for="t in props.types"
                                    :key="t"
                                    :value="t"
                                >
                                    {{ t.charAt(0).toUpperCase() + t.slice(1) }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select v-model="status" class="filter-select">
                                <option value="All">All Statuses</option>
                                <option
                                    v-for="s in props.statuses"
                                    :key="s"
                                    :value="s"
                                >
                                    {{ s }}
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="filter-actions">
                            <button
                                type="button"
                                class="filter-button filter-button-reset"
                                @click="resetFilters"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.refresh"
                                ></span>
                                <span>Reset</span>
                            </button>
                            <button
                                type="submit"
                                class="filter-button filter-button-apply"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>Apply</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    Showing
                    <span class="count-number">{{ props.rows.from || 0 }}</span>
                    to
                    <span class="count-number">{{ props.rows.to || 0 }}</span>
                    of
                    <span class="count-number">{{ props.rows.total }}</span>
                    vacancies
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-card">
                <div class="table-wrapper">
                    <table class="vacancies-table">
                        <thead>
                            <tr>
                                <th class="th-title">Position</th>
                                <th class="th-normal">Department</th>
                                <th class="th-normal">Location</th>
                                <th class="th-normal">Type</th>
                                <th class="th-normal">Status</th>
                                <th class="th-date">Created</th>
                                <th class="th-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Empty State -->
                            <tr
                                v-if="!props.rows.data.length"
                                class="empty-row"
                            >
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div
                                            class="empty-icon"
                                            v-html="icons.briefcase"
                                        ></div>
                                        <div class="empty-title">
                                            No vacancies found
                                        </div>
                                        <div class="empty-text">
                                            Try adjusting your filters or create
                                            a new vacancy
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr
                                v-for="v in props.rows.data"
                                :key="v.id"
                                class="data-row"
                            >
                                <td class="td-title">
                                    <Link
                                        :href="`/hr/vacancies/${v.id}/edit`"
                                        class="vacancy-link"
                                    >
                                        {{ v.title }}
                                    </Link>
                                </td>
                                <td class="td-normal">
                                    {{ v.department || "—" }}
                                </td>
                                <td class="td-normal">
                                    {{ v.location || "—" }}
                                </td>
                                <td class="td-type">
                                    <span class="type-badge">
                                        {{ v.type }}
                                    </span>
                                </td>
                                <td class="td-status">
                                    <span
                                        :class="[
                                            'status-badge',
                                            v.status === 'Open'
                                                ? 'status-open'
                                                : v.status === 'Closed'
                                                  ? 'status-closed'
                                                  : 'status-archived',
                                        ]"
                                    >
                                        {{ v.status }}
                                    </span>
                                </td>
                                <td class="td-date">{{ v.created_at }}</td>
                                <td class="td-actions">
                                    <div class="action-buttons">
                                        <!-- AI Button -->
                                        <Link
                                            :href="`/hr/vacancies/${v.id}/ai`"
                                            class="action-btn action-btn-ai"
                                            title="AI Features"
                                        >
                                            <span v-html="icons.ai"></span>
                                        </Link>

                                        <!-- Edit Button -->
                                        <Link
                                            :href="`/hr/vacancies/${v.id}/edit`"
                                            class="action-btn action-btn-edit"
                                            title="Edit"
                                        >
                                            <span v-html="icons.edit"></span>
                                        </Link>

                                        <!-- Toggle Status Button -->
                                        <button
                                            class="action-btn action-btn-toggle"
                                            :title="
                                                v.status === 'Open'
                                                    ? 'Close'
                                                    : 'Reopen'
                                            "
                                            @click="
                                                openStatusModal(
                                                    v,
                                                    v.status === 'Open'
                                                        ? 'Closed'
                                                        : 'Open',
                                                )
                                            "
                                        >
                                            <span
                                                v-html="
                                                    v.status === 'Open'
                                                        ? icons.close
                                                        : icons.check
                                                "
                                            ></span>
                                        </button>

                                        <!-- Archive Button -->
                                        <button
                                            class="action-btn action-btn-archive"
                                            title="Archive"
                                            @click="
                                                openStatusModal(v, 'Archived')
                                            "
                                        >
                                            <span v-html="icons.archive"></span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            class="action-btn action-btn-delete"
                                            title="Delete"
                                            @click="openDeleteModal(v)"
                                        >
                                            <span v-html="icons.trash"></span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination" v-if="props.rows.data.length">
                    <div class="pagination-info">
                        <span
                            >Page {{ props.rows.current_page }} of
                            {{ props.rows.last_page }}</span
                        >
                    </div>
                    <div class="pagination-buttons">
                        <Link
                            v-if="props.rows.prev_page_url"
                            :href="props.rows.prev_page_url"
                            preserve-state
                            class="pagination-btn"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="props.rows.next_page_url"
                            :href="props.rows.next_page_url"
                            preserve-state
                            class="pagination-btn"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Status Change Modal -->
            <transition name="modal">
                <div
                    v-if="showStatusModal"
                    class="modal-backdrop"
                    @click="showStatusModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showStatusModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div
                            class="modal-icon"
                            :class="{
                                'modal-icon-warning':
                                    pendingStatus === 'Closed' ||
                                    pendingStatus === 'Archived',
                                'modal-icon-success': pendingStatus === 'Open',
                            }"
                        >
                            <span
                                v-html="
                                    pendingStatus === 'Closed'
                                        ? icons.close
                                        : pendingStatus === 'Archived'
                                          ? icons.archive
                                          : icons.check
                                "
                            ></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">
                            Change Status to {{ pendingStatus }}?
                        </h3>
                        <p class="modal-text">
                            You are about to change the status of
                            <strong>{{ activeVacancy?.title }}</strong> to
                            <strong>{{ pendingStatus }}</strong
                            >.
                            <span v-if="pendingStatus === 'Closed'">
                                This will stop accepting new applications.
                            </span>
                            <span v-else-if="pendingStatus === 'Archived'">
                                This vacancy will be archived and hidden from
                                the main list.
                            </span>
                            <span v-else>
                                This will reopen the vacancy for applications.
                            </span>
                        </p>

                        <div class="modal-actions">
                            <button
                                @click="showStatusModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmStatusChange"
                                class="modal-btn modal-btn-confirm"
                                :class="{
                                    'modal-btn-warning':
                                        pendingStatus === 'Closed' ||
                                        pendingStatus === 'Archived',
                                }"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>Confirm Change</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Delete Confirmation Modal -->
            <transition name="modal">
                <div
                    v-if="showDeleteModal"
                    class="modal-backdrop"
                    @click="showDeleteModal = false"
                >
                    <div class="modal-content" @click.stop>
                        <button
                            class="modal-close"
                            @click="showDeleteModal = false"
                        >
                            <span v-html="icons.x"></span>
                        </button>

                        <div class="modal-icon modal-icon-danger">
                            <span v-html="icons.alertTriangle"></span>
                            <div class="modal-icon-ring"></div>
                        </div>

                        <h3 class="modal-title">Delete Vacancy?</h3>
                        <p class="modal-text">
                            Are you sure you want to permanently delete
                            <strong>{{ activeVacancy?.title }}</strong
                            >? This action cannot be undone and will also remove
                            all associated applications.
                        </p>

                        <div class="modal-warning">
                            <span
                                class="warning-icon"
                                v-html="icons.alertCircle"
                            ></span>
                            <span
                                >This is a permanent action and cannot be
                                reversed.</span
                            >
                        </div>

                        <div class="modal-actions">
                            <button
                                @click="showDeleteModal = false"
                                class="modal-btn modal-btn-cancel"
                            >
                                Cancel
                            </button>
                            <button
                                @click="confirmDelete"
                                class="modal-btn modal-btn-danger"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.trash"
                                ></span>
                                <span>Delete Permanently</span>
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </HrLayout>
</template>

<style scoped>
/* Import Modern Fonts */
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables */
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
    --color-border: #e2e8f0;
}

/* Page Container */
.vacancies-page {
    font-family:
        "Plus Jakarta Sans",
        -apple-system,
        sans-serif;
    color: var(--color-text);
}

/* Toast Notification */
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
.toast-warning {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.95),
        rgba(217, 119, 6, 0.95)
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

/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex: 1;
    min-width: 250px;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
    flex-shrink: 0;
}

.header-icon :deep(svg) {
    width: 28px;
    height: 28px;
}

.header-text {
    flex: 1;
}

.page-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
    letter-spacing: -0.02em;
}

.page-subtitle {
    font-size: 0.9375rem;
    color: var(--color-text-light);
    margin: 0;
}

.create-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1.75rem;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 16px rgba(14, 165, 233, 0.3);
    overflow: hidden;
}

.create-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(14, 165, 233, 0.4);
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

.create-button:hover .button-glow {
    opacity: 1;
}

/* Filters Card */
.filters-card {
    background: var(--color-card);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    margin-bottom: 2rem;
    overflow: hidden;
}

.filters-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--color-border);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filter-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(
        135deg,
        rgba(14, 165, 233, 0.1),
        rgba(139, 92, 246, 0.1)
    );
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
}

.filter-icon :deep(svg) {
    width: 20px;
    height: 20px;
}

.filters-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 0.25rem 0;
}

.filters-subtitle {
    font-size: 0.875rem;
    color: var(--color-text-light);
    margin: 0;
}

.filters-body {
    padding: 2rem;
    background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 1.25rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.search-group {
    grid-column: span 12;
}

@media (min-width: 768px) {
    .search-group {
        grid-column: span 5;
    }

    .filter-group:not(.search-group):not(.filter-actions) {
        grid-column: span 2;
    }

    .filter-actions {
        grid-column: span 3;
    }
}

.filter-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-text);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.search-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--color-text-light);
    pointer-events: none;
}

.search-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.filter-input,
.filter-select {
    width: 100%;
    height: 48px;
    padding: 0 1rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: white;
    font-size: 0.9375rem;
    color: var(--color-text);
    transition: all 0.3s ease;
}

.search-input {
    padding-left: 3rem;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.filter-select {
    cursor: pointer;
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
}

.filter-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-button .button-icon {
    width: 16px;
    height: 16px;
}

.filter-button-reset {
    background: white;
    color: var(--color-text-light);
    border: 1px solid var(--color-border);
}

.filter-button-reset:hover {
    background: var(--color-bg);
    border-color: var(--color-text-light);
}

.filter-button-apply {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.filter-button-apply:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* Results Info */
.results-info {
    margin-bottom: 1rem;
}

.results-count {
    font-size: 0.875rem;
    color: var(--color-text-light);
}

.count-number {
    font-weight: 700;
    color: var(--color-text);
}

/* Table Card */
.table-card {
    background: var(--color-card);
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    overflow: hidden;
}

.table-wrapper {
    overflow-x: auto;
}

.vacancies-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
}

.vacancies-table thead {
    background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
}

.vacancies-table th {
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-light);
    border-bottom: 2px solid var(--color-border);
}

.th-title {
    min-width: 250px;
}

.th-normal {
    min-width: 150px;
}

.th-date {
    min-width: 120px;
}

.th-actions {
    text-align: right;
    min-width: 280px;
}

.vacancies-table tbody tr {
    transition: background-color 0.2s ease;
}

.data-row:hover {
    background: #f8fafc;
}

.vacancies-table td {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9375rem;
}

.td-title {
    font-weight: 600;
    color: var(--color-text);
}

.vacancy-link {
    color: var(--color-text);
    text-decoration: none;
    transition: color 0.2s ease;
}

.vacancy-link:hover {
    color: var(--color-primary);
    text-decoration: underline;
}

.td-normal {
    color: var(--color-text-light);
}

.td-type,
.td-status {
    text-align: left;
}

.type-badge {
    display: inline-flex;
    padding: 0.375rem 0.875rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-text);
    text-transform: capitalize;
}

.status-badge {
    display: inline-flex;
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 700;
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

.status-closed {
    background: linear-gradient(
        135deg,
        rgba(100, 116, 139, 0.15),
        rgba(71, 85, 105, 0.15)
    );
    color: #334155;
    border: 1px solid rgba(100, 116, 139, 0.3);
}

.status-archived {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(251, 191, 36, 0.15)
    );
    color: #78350f;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.td-date {
    color: var(--color-text-light);
    font-size: 0.875rem;
}

.td-actions {
    text-align: right;
}

.action-buttons {
    display: inline-flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background: white;
    color: var(--color-text-light);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.action-btn :deep(svg) {
    width: 16px;
    height: 16px;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-btn-ai:hover {
    background: rgba(139, 92, 246, 0.1);
    border-color: var(--color-secondary);
    color: var(--color-secondary);
}

.action-btn-edit:hover {
    background: rgba(14, 165, 233, 0.1);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.action-btn-toggle:hover {
    background: rgba(16, 185, 129, 0.1);
    border-color: var(--color-success);
    color: var(--color-success);
}

.action-btn-archive:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: var(--color-warning);
    color: var(--color-warning);
}

.action-btn-delete:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: var(--color-danger);
    color: var(--color-danger);
}

/* Empty State */
.empty-row td {
    padding: 0 !important;
    border: none !important;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 2rem;
    color: var(--color-text-light);
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

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 0.5rem;
}

.empty-text {
    font-size: 0.9375rem;
}

/* Pagination */
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--color-border);
    background: linear-gradient(to top, #ffffff 0%, #f8fafc 100%);
}

.pagination-info {
    font-size: 0.875rem;
    color: var(--color-text-light);
    font-weight: 500;
}

.pagination-buttons {
    display: flex;
    gap: 0.75rem;
}

.pagination-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.625rem 1.25rem;
    background: white;
    color: var(--color-text);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination-btn:hover {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
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
    max-width: 480px;
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
    z-index: 10;
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
.modal-icon-warning {
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #f59e0b;
}
.modal-icon-danger {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.15),
        rgba(220, 38, 38, 0.15)
    );
    color: #ef4444;
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
    margin: 0 0 1.5rem;
}

.modal-warning {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(239, 68, 68, 0.05);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 12px;
    color: #991b1b;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.warning-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    color: #ef4444;
}

.warning-icon :deep(svg) {
    width: 100%;
    height: 100%;
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-icon {
    width: 18px;
    height: 18px;
}

.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
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
.modal-btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}
.modal-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
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

/* Responsive Design */
@media (max-width: 1024px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }

    .search-group,
    .filter-group,
    .filter-actions {
        grid-column: span 1 !important;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }

    .create-button {
        justify-content: center;
    }

    .filters-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .action-buttons {
        flex-wrap: wrap;
    }

    .toast {
        top: 1rem;
        right: 1rem;
        left: 1rem;
    }
}

@media (max-width: 640px) {
    .page-title {
        font-size: 1.5rem;
    }

    .filters-body {
        padding: 1.5rem;
    }

    .pagination {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .pagination-buttons {
        width: 100%;
        justify-content: stretch;
    }

    .pagination-btn {
        flex: 1;
        justify-content: center;
    }

    .modal-content {
        padding: 1.5rem;
    }

    .modal-actions {
        flex-direction: column;
    }

    .modal-btn {
        width: 100%;
    }
}
</style>
