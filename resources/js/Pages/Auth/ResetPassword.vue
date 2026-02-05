<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
});

const showPwd = ref(false);
const showPwdConfirm = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const canSubmit = computed(() => !form.processing);

function submit() {
    form.post("/password/update", { preserveScroll: true });
}

const icons = {
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    arrowRight:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="auth-page">
        <!-- Animated Background -->
        <div class="background-wrapper">
            <div class="background-gradient"></div>
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

        <div class="auth-container">
            <!-- Reset Password Card -->
            <div class="reset-card">
                <!-- Logo/Brand Section -->
                <div class="brand-section">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <span v-html="icons.shield"></span>
                        </div>
                    </div>
                    <h1 class="brand-title">Reset Your Password</h1>
                    <p class="brand-subtitle">
                        Create a strong new password for your account
                    </p>
                </div>

                <!-- Email Display -->
                <div class="email-display">
                    <span class="email-label">Resetting password for:</span>
                    <span class="email-value">{{ email }}</span>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="reset-form">
                    <!-- New Password Field -->
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.lock"></span>
                            <input
                                v-model="form.password"
                                :type="showPwd ? 'text' : 'password'"
                                class="form-input"
                                placeholder="Enter your new password"
                                autocomplete="new-password"
                                required
                            />
                            <button
                                type="button"
                                @click="showPwd = !showPwd"
                                class="password-toggle"
                            >
                                <span v-if="!showPwd" v-html="icons.eye"></span>
                                <span v-else v-html="icons.eyeOff"></span>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="form-error">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.lock"></span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPwdConfirm ? 'text' : 'password'"
                                class="form-input"
                                placeholder="Confirm your new password"
                                autocomplete="new-password"
                                required
                            />
                            <button
                                type="button"
                                @click="showPwdConfirm = !showPwdConfirm"
                                class="password-toggle"
                            >
                                <span
                                    v-if="!showPwdConfirm"
                                    v-html="icons.eye"
                                ></span>
                                <span v-else v-html="icons.eyeOff"></span>
                            </button>
                        </div>
                        <div
                            v-if="form.errors.password_confirmation"
                            class="form-error"
                        >
                            {{ form.errors.password_confirmation }}
                        </div>
                    </div>

                    <!-- Password Requirements -->
                    <div class="requirements-box">
                        <h4 class="requirements-title">
                            Password must contain:
                        </h4>
                        <ul class="requirements-list">
                            <li class="requirement-item">
                                <span
                                    class="requirement-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>At least 8 characters</span>
                            </li>
                            <li class="requirement-item">
                                <span
                                    class="requirement-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>At least one uppercase letter</span>
                            </li>
                            <li class="requirement-item">
                                <span
                                    class="requirement-icon"
                                    v-html="icons.check"
                                ></span>
                                <span>At least one number</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="submit-btn"
                    >
                        <span v-if="!form.processing">Reset Password</span>
                        <span v-else>Resetting...</span>
                        <span class="btn-icon" v-html="icons.arrowRight"></span>
                    </button>
                </form>
            </div>

            <!-- Help Text -->
            <div class="help-text">
                <p>
                    <Link href="/login" class="help-link">Back to Sign In</Link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

/* Icons */
.input-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.input-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.btn-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.requirement-icon {
    display: inline-flex;
    width: 14px;
    height: 14px;
    color: #10b981;
    flex-shrink: 0;
}
.requirement-icon :deep(svg) {
    width: 100%;
    height: 100%;
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

.floating-shapes {
    position: absolute;
    inset: 0;
}
.shape {
    position: absolute;
    border-radius: 16px;
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.1),
        rgba(5, 150, 105, 0.1)
    );
    animation: shapeFloat 15s ease-in-out infinite;
}
.shape-1 {
    width: 100px;
    height: 100px;
    top: 10%;
    left: 10%;
    animation-delay: 0s;
}
.shape-2 {
    width: 80px;
    height: 80px;
    top: 60%;
    right: 15%;
    animation-delay: -5s;
}
.shape-3 {
    width: 60px;
    height: 60px;
    bottom: 20%;
    left: 20%;
    animation-delay: -10s;
}

@keyframes shapeFloat {
    0%,
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
    50% {
        transform: translate(20px, -20px) rotate(180deg);
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

/* Reset Card */
.reset-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    box-shadow:
        0 20px 60px rgba(0, 0, 0, 0.1),
        0 0 1px rgba(0, 0, 0, 0.05) inset;
    padding: 2.5rem;
}

/* Brand Section */
.brand-section {
    text-align: center;
    margin-bottom: 2rem;
}
.logo-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}
.logo-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 16px;
    color: white;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    animation: logoFloat 3s ease-in-out infinite;
}
.logo-icon :deep(svg) {
    width: 32px;
    height: 32px;
}

@keyframes logoFloat {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.brand-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0 0 0.5rem;
}
.brand-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
    margin: 0;
}

/* Email Display */
.email-display {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 1rem;
    background: rgba(148, 163, 184, 0.05);
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.email-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.email-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
}

/* Form */
.reset-form {
    display: grid;
    gap: 1.5rem;
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
.password-toggle {
    position: absolute;
    right: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: transparent;
    border: none;
    color: #64748b;
    cursor: pointer;
    transition: all 0.3s;
}
.password-toggle:hover {
    background: rgba(148, 163, 184, 0.1);
    color: #0f172a;
}
.password-toggle :deep(svg) {
    width: 20px;
    height: 20px;
}

.form-error {
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #ef4444;
}

/* Requirements Box */
.requirements-box {
    padding: 1rem;
    background: rgba(16, 185, 129, 0.05);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 12px;
}
.requirements-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.75rem;
}
.requirements-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.requirement-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #64748b;
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
    position: relative;
    overflow: hidden;
}
.submit-btn::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}
.submit-btn:hover::before {
    opacity: 1;
}
.submit-btn:active {
    transform: translateY(0);
}
.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Help Text */
.help-text {
    margin-top: 2rem;
    text-align: center;
}
.help-text p {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}
.help-link {
    color: #10b981;
    font-weight: 600;
    text-decoration: none;
}
.help-link:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 640px) {
    .auth-page {
        padding: 1rem;
    }
    .reset-card {
        padding: 2rem 1.5rem;
    }
    .brand-title {
        font-size: 1.75rem;
    }
    .logo-icon {
        width: 56px;
        height: 56px;
    }
    .logo-icon :deep(svg) {
        width: 28px;
        height: 28px;
    }
}
</style>
