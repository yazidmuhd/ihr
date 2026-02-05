<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();

// Laravel "back()->with('status', ...)" will appear here (depends on your Inertia share)
const statusMessage = computed(
    () => page.props?.status || page.props?.flash?.status || "",
);

const form = useForm({
    email: "",
});

function submit() {
    form.post("/password/email", {
        preserveScroll: true,
        onSuccess: () => {
            form.reset("email");
        },
    });
}

const icons = {
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    arrowRight:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    arrowLeft:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    checkCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="auth-page">
        <div class="background-wrapper">
            <div class="background-gradient"></div>
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
        </div>

        <div class="auth-container">
            <div class="forgot-card">
                <div class="icon-section">
                    <div class="icon-wrapper">
                        <div class="icon-circle">
                            <span v-html="icons.lock"></span>
                        </div>
                    </div>
                    <h1 class="title">Forgot Password?</h1>
                    <p class="subtitle">
                        No worries! Enter your email and we'll send you reset
                        instructions.
                    </p>
                </div>

                <!-- Success Message (from Laravel status) -->
                <div v-if="statusMessage" class="success-message">
                    <span
                        class="success-icon"
                        v-html="icons.checkCircle"
                    ></span>
                    <p>{{ statusMessage }}</p>
                </div>

                <form @submit.prevent="submit" class="forgot-form">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.mail"></span>
                            <input
                                v-model="form.email"
                                type="email"
                                class="form-input"
                                placeholder="Enter your email address"
                                autocomplete="email"
                                required
                            />
                        </div>
                        <div v-if="form.errors.email" class="form-error">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="submit-btn"
                    >
                        <span v-if="!form.processing">Send Reset Link</span>
                        <span v-else>Sending...</span>
                        <span class="btn-icon" v-html="icons.arrowRight"></span>
                    </button>
                </form>

                <div class="back-section">
                    <Link href="/login" class="back-link">
                        <span class="link-icon" v-html="icons.arrowLeft"></span>
                        <span>Back to Login</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

/* Icons */
.input-icon,
.btn-icon,
.link-icon,
.success-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.input-icon :deep(svg),
.btn-icon :deep(svg),
.link-icon :deep(svg),
.success-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.input-icon {
    color: #64748b;
}
.success-icon {
    color: #10b981;
}

/* Page Layout */
.auth-page {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    font-family: "Plus Jakarta Sans", sans-serif;
    overflow: hidden;
}

/* Background */
.background-wrapper {
    position: fixed;
    inset: 0;
    z-index: -1;
}
.background-gradient {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 50%, #f0fdf4 100%);
}
.gradient-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.6;
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

/* Container */
.auth-container {
    position: relative;
    width: 100%;
    max-width: 480px;
    z-index: 1;
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

/* Forgot Password Card */
.forgot-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.1),
        0 0 1px rgba(0, 0, 0, 0.05) inset;
    padding: 2.5rem;
}

/* Icon Section */
.icon-section {
    text-align: center;
    margin-bottom: 2rem;
}
.icon-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}
.icon-circle {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    color: white;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    animation: pulse 2s ease-in-out infinite;
}
.icon-circle :deep(svg) {
    width: 40px;
    height: 40px;
}

@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 0.5rem;
}
.subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
    line-height: 1.6;
}

/* Success Message */
.success-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 12px;
    margin-bottom: 1.5rem;
    animation: slideDown 0.4s ease-out;
}
.success-message p {
    margin: 0;
    font-size: 0.875rem;
    color: #059669;
    font-weight: 500;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Form */
.forgot-form {
    display: grid;
    gap: 1.25rem;
}

/* Form Group */
.form-group {
}
.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.input-icon {
    position: absolute;
    left: 1rem;
    pointer-events: none;
    z-index: 1;
}
.form-input {
    width: 100%;
    padding: 0.875rem 1rem 0.875rem 3rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.9375rem;
    color: #0f172a;
    background: white;
    transition: all 0.3s;
    font-family: inherit;
}
.form-input::placeholder {
    color: #94a3b8;
}
.form-input:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

.form-error {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #ef4444;
}

/* Submit Button */
.submit-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    margin-top: 0.5rem;
}
.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}
.submit-btn:active {
    transform: translateY(0);
}
.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Back Section */
.back-section {
    text-align: center;
    margin-top: 1.5rem;
}
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: #64748b;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}
.back-link:hover {
    background: rgba(148, 163, 184, 0.05);
    border-color: #64748b;
    color: #0f172a;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 640px) {
    .auth-page {
        padding: 1rem;
    }
    .forgot-card {
        padding: 2rem 1.5rem;
    }
    .title {
        font-size: 1.75rem;
    }
    .icon-circle {
        width: 64px;
        height: 64px;
    }
    .icon-circle :deep(svg) {
        width: 32px;
        height: 32px;
    }
}
</style>
