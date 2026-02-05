<!-- resources/js/Pages/Applicant/Profile/Edit.vue -->
<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    name: props.user.name ?? "",
    username: props.user.username ?? "",
    phone: props.user.phone ?? "",
    about: props.user.about ?? "",
});

// Modals
const showSaveModal = ref(false);
const showCancelModal = ref(false);
const showRemoveModal = ref(false);

// Toast
const toast = ref({ show: false, message: "", type: "" });

function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

function openSaveModal() {
    if (!form.isDirty) {
        showToast("No changes to save", "info");
        return;
    }
    showSaveModal.value = true;
}

function confirmSave() {
    showSaveModal.value = false;
    form.put("/profile", {
        preserveScroll: true,
        onSuccess: () => showToast("Profile updated successfully!", "success"),
        onError: () => showToast("Failed to update profile", "error"),
    });
}

function openCancelModal() {
    if (!form.isDirty) {
        window.location.href = "/applicant/dashboard";
        return;
    }
    showCancelModal.value = true;
}

function confirmCancel() {
    showCancelModal.value = false;
    window.location.href = "/applicant/dashboard";
}

/* avatar upload */
const avatarForm = useForm({ avatar: null });
const preview = ref(props.user.avatarUrl);
const isUploading = ref(false);

function onPick(e) {
    const f = e.target.files?.[0];
    if (!f) return;

    // Validate file size (max 2MB)
    if (f.size > 2 * 1024 * 1024) {
        showToast("Image size must be less than 2MB", "error");
        return;
    }

    // Validate file type
    if (!f.type.startsWith("image/")) {
        showToast("Please select an image file", "error");
        return;
    }

    avatarForm.avatar = f;
    const reader = new FileReader();
    reader.onload = (ev) => {
        preview.value = ev.target.result;
        showToast("Image selected! Click Upload to save", "info");
    };
    reader.readAsDataURL(f);
}

function uploadAvatar() {
    isUploading.value = true;
    avatarForm.post("/profile/avatar", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isUploading.value = false;
            showToast("Avatar updated successfully!", "success");

            router.reload({ only: ["auth", "user"], preserveScroll: true });
        },
        onError: () => {
            isUploading.value = false;
            showToast("Failed to upload avatar", "error");
        },
    });
}

function openRemoveModal() {
    if (!preview.value) {
        showToast("No avatar to remove", "info");
        return;
    }
    showRemoveModal.value = true;
}

function confirmRemove() {
    showRemoveModal.value = false;
    avatarForm.delete("/profile/avatar", {
        preserveScroll: true,
        onSuccess: () => {
            preview.value = null;
            showToast("Avatar removed successfully!", "success");
            router.reload({ only: ["auth", "user"], preserveScroll: true });
        },
        onError: () => showToast("Failed to remove avatar", "error"),
    });
}

// Watch for flash messages
watch(
    () => props.flash?.status,
    (newVal) => {
        if (newVal) showToast(newVal, "success");
    },
    { immediate: true },
);

const icons = {
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    resume: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    camera: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
    upload: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>',
    trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
    save: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
};
</script>

<template>
    <Head title="Profile" />
    <ApplicantLayout :showSidebar="false" content-max="max-w-4xl">
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
                        <span v-html="icons.user"></span>
                        <div class="icon-pulse"></div>
                    </div>
                    <div>
                        <h1 class="header-title">Your Profile</h1>
                        <p class="header-subtitle">
                            Keep your details fresh and up-to-date
                        </p>
                    </div>
                </div>
                <Link href="/app/resume" class="header-btn">
                    <span class="btn-icon" v-html="icons.resume"></span>
                    <span>Manage Resume</span>
                </Link>
            </div>
        </section>

        <div class="main-grid">
            <!-- Avatar Card -->
            <div class="avatar-card">
                <div class="avatar-content">
                    <div class="avatar-wrapper">
                        <img
                            :src="preview || '/brand/avatar-placeholder.png'"
                            alt="Avatar"
                            class="avatar-img"
                            :class="{ 'avatar-uploading': isUploading }"
                        />
                        <div class="avatar-overlay">
                            <label for="pickAvatar" class="avatar-overlay-btn">
                                <span
                                    class="icon-md"
                                    v-html="icons.camera"
                                ></span>
                            </label>
                        </div>
                        <div v-if="isUploading" class="avatar-spinner">
                            <div class="spinner"></div>
                        </div>
                    </div>

                    <input
                        type="file"
                        accept="image/*"
                        class="sr-only"
                        id="pickAvatar"
                        @change="onPick"
                    />

                    <div class="avatar-actions">
                        <label
                            for="pickAvatar"
                            class="avatar-btn avatar-btn-choose"
                        >
                            <span class="icon-sm" v-html="icons.camera"></span>
                            <span>Choose Image</span>
                        </label>

                        <button
                            v-if="avatarForm.avatar"
                            class="avatar-btn avatar-btn-upload"
                            type="button"
                            @click="uploadAvatar"
                            :disabled="avatarForm.processing"
                        >
                            <span class="icon-sm" v-html="icons.upload"></span>
                            <span>{{
                                avatarForm.processing ? "Uploading…" : "Upload"
                            }}</span>
                        </button>

                        <button
                            v-if="preview"
                            class="avatar-btn avatar-btn-remove"
                            type="button"
                            @click="openRemoveModal"
                            :disabled="avatarForm.processing"
                        >
                            <span class="icon-sm" v-html="icons.trash"></span>
                            <span>Remove</span>
                        </button>
                    </div>

                    <p v-if="avatarForm.errors.avatar" class="avatar-error">
                        {{ avatarForm.errors.avatar }}
                    </p>
                </div>
            </div>

            <!-- Profile Form -->
            <form @submit.prevent="openSaveModal" class="profile-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label class="field-label">Full Name</label>
                        <input
                            v-model="form.name"
                            class="field-input"
                            placeholder="Jane Doe"
                        />
                        <p v-if="form.errors.name" class="field-error">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="form-field">
                        <label class="field-label">Username</label>
                        <input
                            v-model="form.username"
                            class="field-input"
                            placeholder="janedoe"
                        />
                        <p v-if="form.errors.username" class="field-error">
                            {{ form.errors.username }}
                        </p>
                    </div>

                    <div class="form-field">
                        <label class="field-label">Email</label>
                        <input
                            :value="user.email"
                            class="field-input field-disabled"
                            disabled
                        />
                        <p class="field-hint">Email cannot be changed</p>
                    </div>

                    <div class="form-field">
                        <label class="field-label">Phone</label>
                        <input
                            v-model="form.phone"
                            class="field-input"
                            placeholder="+60 12-345 6789"
                        />
                        <p v-if="form.errors.phone" class="field-error">
                            {{ form.errors.phone }}
                        </p>
                    </div>

                    <div class="form-field form-field-full">
                        <label class="field-label">About You</label>
                        <textarea
                            v-model="form.about"
                            rows="5"
                            class="field-input field-textarea"
                            placeholder="Tell us about yourself, your experience, and what you're looking for..."
                        />
                        <p v-if="form.errors.about" class="field-error">
                            {{ form.errors.about }}
                        </p>
                    </div>
                </div>

                <div class="form-actions">
                    <button
                        type="button"
                        @click="openCancelModal"
                        class="form-btn form-btn-cancel"
                    >
                        <span class="icon-sm" v-html="icons.x"></span>
                        <span>Cancel</span>
                    </button>
                    <button
                        type="submit"
                        class="form-btn form-btn-save"
                        :disabled="form.processing"
                    >
                        <span class="icon-sm" v-html="icons.save"></span>
                        <span>{{
                            form.processing ? "Saving…" : "Save Changes"
                        }}</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Save Confirmation Modal -->
        <transition name="modal">
            <div
                v-if="showSaveModal"
                class="modal-backdrop"
                @click="showSaveModal = false"
            >
                <div class="modal-content" @click.stop>
                    <button class="modal-close" @click="showSaveModal = false">
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon modal-icon-save">
                        <span v-html="icons.save"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Save Changes?</h3>
                    <p class="modal-text">
                        Are you sure you want to update your profile
                        information?
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="showSaveModal = false"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmSave"
                            class="modal-btn modal-btn-confirm"
                        >
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Cancel Confirmation Modal -->
        <transition name="modal">
            <div
                v-if="showCancelModal"
                class="modal-backdrop"
                @click="showCancelModal = false"
            >
                <div class="modal-content" @click.stop>
                    <button
                        class="modal-close"
                        @click="showCancelModal = false"
                    >
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon modal-icon-warning">
                        <span v-html="icons.alert"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Discard Changes?</h3>
                    <p class="modal-text">
                        You have unsaved changes. Are you sure you want to leave
                        without saving?
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="showCancelModal = false"
                            class="modal-btn modal-btn-cancel"
                        >
                            Stay
                        </button>
                        <button
                            @click="confirmCancel"
                            class="modal-btn modal-btn-danger"
                        >
                            Discard
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Remove Avatar Modal -->
        <transition name="modal">
            <div
                v-if="showRemoveModal"
                class="modal-backdrop"
                @click="showRemoveModal = false"
            >
                <div class="modal-content" @click.stop>
                    <button
                        class="modal-close"
                        @click="showRemoveModal = false"
                    >
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon modal-icon-danger">
                        <span v-html="icons.trash"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Remove Avatar?</h3>
                    <p class="modal-text">
                        Are you sure you want to remove your profile picture?
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="showRemoveModal = false"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmRemove"
                            class="modal-btn modal-btn-danger"
                        >
                            Remove
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
.icon-sm {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.icon-sm :deep(svg) {
    width: 100%;
    height: 100%;
}
.icon-md {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.icon-md :deep(svg) {
    width: 100%;
    height: 100%;
}
.btn-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
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

/* Header Card */
.header-card {
    position: relative;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
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
}
.header-btn:hover {
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
}

/* Main Grid */
.main-grid {
    display: grid;
    gap: 2rem;
    grid-template-columns: 280px 1fr;
}

/* Avatar Card */
.avatar-card {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    transition: all 0.3s;
}
.avatar-card:hover {
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}
.avatar-content {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.avatar-wrapper {
    position: relative;
    width: 160px;
    height: 160px;
    margin-bottom: 1.5rem;
}
.avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border: 4px solid white;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.avatar-img:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 48px rgba(6, 182, 212, 0.3);
    border-color: #0ea5e9;
}
.avatar-uploading {
    animation: avatarPulse 1.5s ease-in-out infinite;
}
@keyframes avatarPulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
}
.avatar-overlay {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    cursor: pointer;
}
.avatar-wrapper:hover .avatar-overlay {
    opacity: 1;
}
.avatar-overlay-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: white;
    border-radius: 50%;
    color: #0ea5e9;
    cursor: pointer;
    transition: all 0.3s;
}
.avatar-overlay-btn:hover {
    transform: scale(1.1);
}
.avatar-spinner {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
}
.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(6, 182, 212, 0.2);
    border-top-color: #0ea5e9;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.avatar-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
}
.avatar-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}
.avatar-btn-choose {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.avatar-btn-choose:hover {
    background: rgba(6, 182, 212, 0.05);
    border-color: #0ea5e9;
    transform: translateY(-2px);
}
.avatar-btn-upload {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.avatar-btn-upload:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.avatar-btn-remove {
    background: white;
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.avatar-btn-remove:hover {
    background: rgba(239, 68, 68, 0.05);
    transform: translateY(-2px);
}
.avatar-error {
    margin-top: 1rem;
    font-size: 0.8125rem;
    color: #ef4444;
    text-align: center;
}

/* Profile Form */
.profile-form {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}
.form-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr 1fr;
}
.form-field {
    display: flex;
    flex-direction: column;
}
.form-field-full {
    grid-column: 1 / -1;
}
.field-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.field-input {
    padding: 0.875rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    color: #0f172a;
    transition: all 0.3s;
    background: white;
}
.field-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.field-input:hover:not(:disabled) {
    border-color: #0ea5e9;
}
.field-disabled {
    background: rgba(148, 163, 184, 0.05);
    color: #64748b;
    cursor: not-allowed;
}
.field-textarea {
    resize: vertical;
    min-height: 120px;
}
.field-error {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #ef4444;
}
.field-hint {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}
.form-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}
.form-btn-cancel {
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
}
.form-btn-cancel:hover {
    background: rgba(148, 163, 184, 0.05);
    transform: translateY(-2px);
}
.form-btn-save {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.form-btn-save:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.form-btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
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
.modal-icon-save {
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
    .main-grid {
        grid-template-columns: 1fr;
    }
    .avatar-card {
        padding: 1.5rem;
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
    .header-btn {
        width: 100%;
        justify-content: center;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
    .form-actions {
        flex-direction: column-reverse;
    }
    .form-btn {
        width: 100%;
        justify-content: center;
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

    .avatar-wrapper {
        width: 140px;
        height: 140px;
    }
    .profile-form {
        padding: 1.5rem;
    }
    .modal-content {
        padding: 1.5rem;
    }
}
</style>
