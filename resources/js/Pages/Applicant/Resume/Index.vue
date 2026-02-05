<!-- resources/js/Pages/Applicant/Resume/Index.vue -->
<script setup>
import { Head, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    rows: { type: Array, default: () => [] },
});

// Local state for drag-and-drop
const localRows = ref([...props.rows]);
const uploading = ref(false);
const draggedIndex = ref(null);

// ✅ keep localRows updated when server returns new props (after upload/delete/activate)
watch(
    () => props.rows,
    (newRows) => {
        localRows.value = [...(newRows || [])];
    },
    { deep: true },
);

// Modals
const showDeleteModal = ref(false);
const showActivateModal = ref(false);
const showRetryModal = ref(false);
const selectedResume = ref(null);

// Toast
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

/* ---------- helpers ---------- */
function parseAi(v) {
    try {
        if (!v) return null;
        if (typeof v === "string") return JSON.parse(v);
        return v;
    } catch {
        return null;
    }
}

function ai(row) {
    return parseAi(row.ai_parsed) || {};
}

function years(aiObj) {
    return (
        aiObj.years_experience ??
        aiObj.experience_years ??
        aiObj.total_experience_years ??
        aiObj.years_of_experience ??
        0
    );
}

/* ---------- actions ---------- */
function onUpload(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validate file
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        showToast("File size must be less than 5MB", "error");
        e.target.value = "";
        return;
    }

    // ✅ Keep consistent with backend extractor (PDF + DOCX only)
    const validTypes = [".pdf", ".docx"];
    const fileExt = "." + file.name.split(".").pop().toLowerCase();
    if (!validTypes.includes(fileExt)) {
        showToast("Please upload PDF or DOCX files only", "error");
        e.target.value = "";
        return;
    }

    const form = new FormData();
    form.append("resume", file);
    uploading.value = true;

    router.post("/app/resume", form, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showToast("Resume uploaded successfully!", "success");
            e.target.value = "";
        },
        onError: () => showToast("Failed to upload resume", "error"),
        onFinish: () => (uploading.value = false),
    });
}

function openActivateModal(resume) {
    selectedResume.value = resume;
    showActivateModal.value = true;
}

function confirmActivate() {
    showActivateModal.value = false;
    router.patch(
        `/app/resume/${selectedResume.value.id}/activate`,
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                showToast("Resume activated successfully!", "success"),
            onError: () => showToast("Failed to activate resume", "error"),
        },
    );
}

function openDeleteModal(resume) {
    selectedResume.value = resume;
    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(`/app/resume/${selectedResume.value.id}`, {
        preserveScroll: true,
        onSuccess: () => showToast("Resume deleted successfully!", "success"),
        onError: () => showToast("Failed to delete resume", "error"),
    });
}

function openRetryModal(resume) {
    selectedResume.value = resume;
    showRetryModal.value = true;
}

function confirmRetry() {
    showRetryModal.value = false;
    router.post(
        `/app/resume/${selectedResume.value.id}/retry`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => showToast("Parsing retry initiated!", "info"),
            onError: () => showToast("Failed to retry parsing", "error"),
        },
    );
}

/* ---------- drag and drop ---------- */
function onDragStart(index) {
    draggedIndex.value = index;
}

function onDragOver(e, index) {
    e.preventDefault();
    if (draggedIndex.value === null || draggedIndex.value === index) return;

    const items = [...localRows.value];
    const draggedItem = items[draggedIndex.value];
    items.splice(draggedIndex.value, 1);
    items.splice(index, 0, draggedItem);

    localRows.value = items;
    draggedIndex.value = index;
}

function onDragEnd() {
    draggedIndex.value = null;
    saveOrder();
    showToast("Order updated", "info");
}

/* ---------- arrow buttons ---------- */
function moveUp(index) {
    if (index === 0) return;
    const items = [...localRows.value];
    [items[index - 1], items[index]] = [items[index], items[index - 1]];
    localRows.value = items;
    saveOrder();
    showToast("Order updated", "info");
}

function moveDown(index) {
    if (index === localRows.value.length - 1) return;
    const items = [...localRows.value];
    [items[index], items[index + 1]] = [items[index + 1], items[index]];
    localRows.value = items;
    saveOrder();
    showToast("Order updated", "info");
}

function saveOrder() {
    const order = localRows.value.map((r) => r.id);
    // POST /app/resume/reorder with {order: [id1, id2, ...]}
    router.post("/app/resume/reorder", { order }, { preserveScroll: true });
}

const icons = {
    resume: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    refresh:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>',
    arrowUp:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>',
    arrowDown:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>',
    grip: '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    star: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
};
</script>

<template>
    <Head title="My Resume" />
    <ApplicantLayout :showSidebar="false" content-max="max-w-5xl">
        <!-- Toast Notification -->
        <transition name="toast">
            <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
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
                        <span v-html="icons.resume"></span>
                        <div class="icon-pulse"></div>
                    </div>
                    <div>
                        <h1 class="header-title">My Resumes</h1>
                        <p class="header-subtitle">
                            Upload and manage your resumes. Mark one as active
                            for quick apply.
                        </p>
                    </div>
                </div>

                <label class="upload-btn">
                    <input
                        type="file"
                        class="sr-only"
                        accept=".pdf,.docx"
                        @change="onUpload"
                    />
                    <span class="btn-icon" v-html="icons.upload"></span>
                    <span>{{
                        uploading ? "Uploading…" : "Upload Resume"
                    }}</span>
                </label>
            </div>
        </section>

        <!-- List -->
        <section class="resumes-grid">
            <article
                v-for="(r, index) in localRows"
                :key="r.id"
                class="resume-card"
                :class="{
                    'resume-active': r.is_active,
                    'resume-dragging': draggedIndex === index,
                }"
                :style="{ '--card-delay': `${index * 0.05}s` }"
                draggable="true"
                @dragstart="onDragStart(index)"
                @dragover="onDragOver($event, index)"
                @dragend="onDragEnd"
            >
                <!-- Active Badge -->
                <div v-if="r.is_active" class="active-badge">
                    <span class="badge-icon" v-html="icons.star"></span>
                    <span>Active Resume</span>
                </div>

                <div class="card-content">
                    <!-- Drag Handle & Controls -->
                    <div class="card-controls">
                        <div class="drag-handle" title="Drag to reorder">
                            <span v-html="icons.grip"></span>
                        </div>
                        <div class="arrow-buttons">
                            <button
                                @click="moveUp(index)"
                                :disabled="index === 0"
                                class="arrow-btn"
                                :class="{ 'arrow-disabled': index === 0 }"
                                title="Move up"
                            >
                                <span v-html="icons.arrowUp"></span>
                            </button>
                            <button
                                @click="moveDown(index)"
                                :disabled="index === localRows.length - 1"
                                class="arrow-btn"
                                :class="{
                                    'arrow-disabled':
                                        index === localRows.length - 1,
                                }"
                                title="Move down"
                            >
                                <span v-html="icons.arrowDown"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="card-main">
                        <div class="card-header">
                            <div class="file-info">
                                <div class="file-name">
                                    {{ r.file_name || "resume" }}
                                    <span class="file-size">
                                        ({{
                                            Math.round(
                                                (r.file_size || 0) / 1024,
                                            )
                                        }}
                                        KB)
                                    </span>
                                </div>
                                <div class="file-date">
                                    <span
                                        class="date-icon"
                                        v-html="icons.upload"
                                    ></span>
                                    <span>Uploaded {{ r.created_at }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- AI Parsed Preview -->
                        <template v-if="parseAi(r.ai_parsed)">
                            <div class="ai-preview">
                                <div class="ai-header">
                                    <span
                                        class="ai-icon"
                                        v-html="icons.check"
                                    ></span>
                                    <span>AI Parsed Successfully</span>
                                </div>

                                <div class="ai-grid">
                                    <div class="ai-item">
                                        <span class="ai-label">Skills:</span>
                                        <span class="ai-value">
                                            {{
                                                (ai(r).skills || []).join(
                                                    ", ",
                                                ) || "—"
                                            }}
                                        </span>
                                    </div>

                                    <div class="ai-item">
                                        <span class="ai-label"
                                            >Experience:</span
                                        >
                                        <span class="ai-value"
                                            >{{ years(ai(r)) || 0 }} years</span
                                        >
                                    </div>

                                    <div class="ai-item">
                                        <span class="ai-label">Education:</span>
                                        <span class="ai-value">{{
                                            ai(r).education || "—"
                                        }}</span>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        (ai(r).work || ai(r).experiences || [])
                                            .length
                                    "
                                    class="ai-section"
                                >
                                    <div class="section-title">
                                        Recent Roles
                                    </div>
                                    <ul class="roles-list">
                                        <li
                                            v-for="(w, idx) in (
                                                ai(r).work ||
                                                ai(r).experiences ||
                                                []
                                            ).slice(0, 3)"
                                            :key="'w' + idx"
                                            class="role-item"
                                        >
                                            <span class="role-title">{{
                                                w.role || w.title || "—"
                                            }}</span>
                                            <span
                                                v-if="w.company"
                                                class="role-company"
                                            >
                                                @ {{ w.company }}</span
                                            >
                                            <span
                                                v-if="
                                                    w.start ||
                                                    w.from ||
                                                    w.end ||
                                                    w.to
                                                "
                                                class="role-period"
                                            >
                                                —
                                                {{ w.start || w.from || "" }} –
                                                {{ w.end || w.to || "Present" }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                <div
                                    v-if="(ai(r).projects || []).length"
                                    class="ai-section"
                                >
                                    <div class="section-title">Projects</div>
                                    <ul class="projects-list">
                                        <li
                                            v-for="(p, idx) in (
                                                ai(r).projects || []
                                            ).slice(0, 2)"
                                            :key="'p' + idx"
                                        >
                                            <span class="project-name">{{
                                                p.name || p.title || "—"
                                            }}</span>
                                            <span v-if="p.role">
                                                — {{ p.role }}</span
                                            >
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </template>

                        <!-- AI Error -->
                        <div
                            v-else-if="r.ai_status === 'error'"
                            class="ai-error"
                        >
                            <div class="error-header">
                                <span
                                    class="error-icon"
                                    v-html="icons.alert"
                                ></span>
                                <span>AI Parsing Failed</span>
                            </div>
                            <div class="error-message">
                                {{ r.ai_error || "Unknown error occurred" }}
                            </div>
                            <button
                                class="retry-btn"
                                @click="openRetryModal(r)"
                            >
                                <span
                                    class="btn-icon"
                                    v-html="icons.refresh"
                                ></span>
                                <span>Retry Parsing</span>
                            </button>
                        </div>

                        <!-- AI Pending/Processing -->
                        <div v-else-if="r.ai_status" class="ai-processing">
                            <span class="processing-spinner"></span>
                            <span>AI Processing: {{ r.ai_status }}...</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-actions">
                        <a
                            v-if="r.url"
                            :href="r.url"
                            target="_blank"
                            class="action-btn action-preview"
                        >
                            <span class="btn-icon" v-html="icons.eye"></span>
                            <span>Preview</span>
                        </a>

                        <button
                            v-if="!r.is_active"
                            @click="openActivateModal(r)"
                            class="action-btn action-activate"
                        >
                            <span class="btn-icon" v-html="icons.check"></span>
                            <span>Set Active</span>
                        </button>

                        <button
                            @click="openDeleteModal(r)"
                            class="action-btn action-delete"
                        >
                            <span class="btn-icon" v-html="icons.trash"></span>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            </article>

            <!-- Empty State -->
            <div v-if="!localRows.length" class="empty-state">
                <div class="empty-icon">
                    <span v-html="icons.resume"></span>
                </div>
                <h3 class="empty-title">No Resumes Yet</h3>
                <p class="empty-text">
                    Upload your first resume to get started with AI-powered job
                    matching
                </p>
            </div>
        </section>

        <!-- Activate Modal -->
        <transition name="modal">
            <div
                v-if="showActivateModal"
                class="modal-backdrop"
                @click="showActivateModal = false"
            >
                <div class="modal-content" @click.stop>
                    <button
                        class="modal-close"
                        @click="showActivateModal = false"
                    >
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon modal-icon-success">
                        <span v-html="icons.check"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Set Active Resume?</h3>
                    <p class="modal-text">
                        This resume will be used for one-click job applications.
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="showActivateModal = false"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmActivate"
                            class="modal-btn modal-btn-confirm"
                        >
                            Set Active
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Delete Modal -->
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
                        <span v-html="icons.trash"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Delete Resume?</h3>
                    <p class="modal-text">
                        This action cannot be undone. Are you sure you want to
                        delete this resume?
                    </p>

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
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Retry Modal -->
        <transition name="modal">
            <div
                v-if="showRetryModal"
                class="modal-backdrop"
                @click="showRetryModal = false"
            >
                <div class="modal-content" @click.stop>
                    <button class="modal-close" @click="showRetryModal = false">
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon modal-icon-info">
                        <span v-html="icons.refresh"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Retry AI Parsing?</h3>
                    <p class="modal-text">
                        We'll attempt to parse your resume again using our AI
                        system.
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="showRetryModal = false"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRetry"
                            class="modal-btn modal-btn-confirm"
                        >
                            Retry Now
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </ApplicantLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

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
.date-icon {
    display: inline-flex;
    width: 14px;
    height: 14px;
}
.date-icon :deep(svg) {
    width: 100%;
    height: 100%;
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
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
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

.upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    transition: all 0.3s;
    border: none;
}
.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

/* Resumes Grid */
.resumes-grid {
    display: grid;
    gap: 1.5rem;
}

/* Resume Card */
.resume-card {
    position: relative;
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: cardFadeIn 0.6s ease-out var(--card-delay) both;
    cursor: move;
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
.resume-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}
.resume-dragging {
    opacity: 0.5;
    transform: scale(0.98);
}

/* Active Resume Styling */
.resume-active {
    border: 2px solid #10b981;
    box-shadow: 0 8px 32px rgba(16, 185, 129, 0.2);
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(16, 185, 129, 0.02) 100%
    );
}
.resume-active::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.05)
    );
    pointer-events: none;
}

.active-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 50px;
    font-size: 0.8125rem;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
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
.badge-icon {
    display: flex;
    width: 14px;
    height: 14px;
}
.badge-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.card-content {
    position: relative;
    display: flex;
    gap: 1.5rem;
    padding: 2rem;
}

/* Drag & Controls */
.card-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
    padding-right: 1rem;
    border-right: 1px solid rgba(148, 163, 184, 0.2);
}
.drag-handle {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    cursor: grab;
    transition: all 0.3s;
    border-radius: 8px;
}
.drag-handle:hover {
    background: rgba(148, 163, 184, 0.1);
    color: #0ea5e9;
}
.drag-handle:active {
    cursor: grabbing;
}
.drag-handle :deep(svg) {
    width: 20px;
    height: 20px;
}

.arrow-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.arrow-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 8px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s;
}
.arrow-btn:hover:not(.arrow-disabled) {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    color: #0ea5e9;
    transform: translateY(-1px);
}
.arrow-btn :deep(svg) {
    width: 16px;
    height: 16px;
}
.arrow-disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.card-main {
    flex: 1;
    min-width: 0;
}

.card-header {
    margin-bottom: 1.5rem;
}
.file-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.file-size {
    font-size: 0.875rem;
    font-weight: 500;
    color: #64748b;
    margin-left: 0.5rem;
}
.file-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
}

/* AI Preview */
.ai-preview {
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.05),
        rgba(5, 150, 105, 0.03)
    );
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.ai-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 1.25rem;
}
.ai-icon {
    display: flex;
    width: 20px;
    height: 20px;
}
.ai-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.ai-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.ai-label {
    font-size: 0.875rem;
    font-weight: 700;
    color: #047857;
    margin-right: 0.5rem;
}
.ai-value {
    font-size: 0.875rem;
    color: #065f46;
}

.ai-section {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(16, 185, 129, 0.2);
}
.section-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #047857;
    margin-bottom: 0.75rem;
}
.roles-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 0.75rem;
}
.role-item {
    font-size: 0.875rem;
    color: #065f46;
}
.role-title {
    font-weight: 600;
}
.role-company {
    font-weight: 500;
    color: #047857;
}
.role-period {
    font-size: 0.8125rem;
    color: #059669;
}
.projects-list {
    list-style: disc;
    padding-left: 1.25rem;
    margin: 0;
    display: grid;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #065f46;
}
.project-name {
    font-weight: 600;
}

/* AI Error */
.ai-error {
    padding: 1.5rem;
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.05),
        rgba(220, 38, 38, 0.03)
    );
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.error-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #991b1b;
    margin-bottom: 0.75rem;
}
.error-icon {
    display: flex;
    width: 20px;
    height: 20px;
}
.error-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.error-message {
    font-size: 0.875rem;
    color: #dc2626;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.retry-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    background: white;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #dc2626;
    cursor: pointer;
    transition: all 0.3s;
}
.retry-btn:hover {
    background: rgba(239, 68, 68, 0.05);
    transform: translateY(-1px);
}

/* AI Processing */
.ai-processing {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(59, 130, 246, 0.05);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 12px;
    font-size: 0.875rem;
    color: #1e40af;
    margin-bottom: 1.5rem;
}
.processing-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(59, 130, 246, 0.3);
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Card Actions */
.card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
    align-items: center;
}

/* ✅ keep buttons compact by default */
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    border: none;
    width: auto;
    flex: 0 0 auto;
}

.action-preview {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.action-preview:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    transform: translateY(-2px);
}

.action-activate {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.action-activate:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.action-delete {
    background: white;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.action-delete:hover {
    background: rgba(239, 68, 68, 0.05);
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
    max-width: 400px;
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
    .card-content {
        flex-direction: column;
    }
    .card-controls {
        flex-direction: row;
        padding-right: 0;
        padding-bottom: 1rem;
        border-right: none;
        border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        justify-content: space-between;
        width: 100%;
    }
    .arrow-buttons {
        flex-direction: row;
    }
}

@media (max-width: 768px) {
    .header-card {
        padding: 1.5rem;
    }
    .header-content {
        flex-direction: column;
        align-items: flex-start;
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
    .upload-btn {
        width: 100%;
        justify-content: center;
    }

    .card-content {
        padding: 1.5rem;
    }

    /* ✅ keep buttons normal size and wrapped */
    .card-actions {
        justify-content: flex-start;
    }
    .action-btn {
        width: auto;
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

    .card-content {
        padding: 1.25rem;
    }
    .file-name {
        font-size: 1rem;
    }
    .modal-content {
        padding: 1.5rem;
    }
}
</style>
