<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const showPwd = ref(false);
const showPwd2 = ref(false);
const showSuccessModal = ref(false);

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const canSubmit = computed(() => !form.processing);

function submit() {
    form.post("/register", {
        onSuccess: () => {
            showSuccessModal.value = true;
        },
    });
}

function handleOkay() {
    showSuccessModal.value = false;
    window.location.href = "/login";
}

const icons = {
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    arrowRight:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    checkCircle:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    zap: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
    logIn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>',
    checkBadge:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
};
</script>

<template>
    <Head title="Create account" />

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
            <!-- Register Card -->
            <div class="register-card">
                <!-- Logo/Brand Section -->
                <div class="brand-section">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <span v-html="icons.sparkles"></span>
                        </div>
                    </div>
                    <h1 class="brand-title">Create Your Account</h1>
                    <p class="brand-subtitle">
                        Join us and start your journey today
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="register-form">
                    <!-- Name Field -->
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.user"></span>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-input"
                                placeholder="Enter your full name"
                                autocomplete="name"
                            />
                        </div>
                        <div v-if="form.errors.name" class="form-error">
                            {{ form.errors.name }}
                        </div>
                    </div>

                    <!-- Email Field -->
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
                            />
                        </div>
                        <div v-if="form.errors.email" class="form-error">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.lock"></span>
                            <input
                                v-model="form.password"
                                :type="showPwd ? 'text' : 'password'"
                                class="form-input"
                                placeholder="Create a strong password"
                                autocomplete="new-password"
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
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.lock"></span>
                            <input
                                v-model="form.password_confirmation"
                                :type="showPwd2 ? 'text' : 'password'"
                                class="form-input"
                                placeholder="Confirm your password"
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showPwd2 = !showPwd2"
                                class="password-toggle"
                            >
                                <span
                                    v-if="!showPwd2"
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

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="submit-btn"
                    >
                        <span v-if="!form.processing">Create Account</span>
                        <span v-else>Creating Account...</span>
                        <span class="btn-icon" v-html="icons.arrowRight"></span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <span class="divider-text">or</span>
                </div>

                <!-- Login Link -->
                <div class="login-section">
                    <p class="login-text">Already have an account?</p>
                    <Link href="/login" class="login-link">
                        <span class="link-icon" v-html="icons.logIn"></span>
                        <span>Sign in to your account</span>
                    </Link>
                </div>
            </div>

            <!-- Features -->
            <div class="features-list">
                <div class="feature-item">
                    <span
                        class="feature-icon"
                        v-html="icons.checkCircle"
                    ></span>
                    <span class="feature-text">Free to join</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon" v-html="icons.shield"></span>
                    <span class="feature-text">Secure & private</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon" v-html="icons.zap"></span>
                    <span class="feature-text">Quick setup</span>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <Transition name="modal">
            <div
                v-if="showSuccessModal"
                class="modal-overlay"
                @click="handleOkay"
            >
                <div class="modal-container" @click.stop>
                    <div class="success-icon-wrapper">
                        <div class="success-icon">
                            <span v-html="icons.checkBadge"></span>
                        </div>
                        <div class="success-particles">
                            <div
                                class="particle"
                                v-for="i in 12"
                                :key="i"
                            ></div>
                        </div>
                    </div>

                    <h2 class="modal-title">Account Created Successfully!</h2>
                    <p class="modal-message">
                        Your account has been registered and saved. You can now
                        sign in to access your dashboard.
                    </p>

                    <button @click="handleOkay" class="modal-btn">
                        <span>Continue to Login</span>
                        <span class="btn-icon" v-html="icons.arrowRight"></span>
                    </button>
                </div>
            </div>
        </Transition>
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
.link-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.link-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.feature-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
    color: #10b981;
}
.feature-icon :deep(svg) {
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

/* Register Card */
.register-card {
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

/* Form */
.register-form {
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
    display: flex;
    align-items: center;
    gap: 0.375rem;
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
    margin-top: 0.5rem;
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

/* Divider */
.divider {
    position: relative;
    margin: 2rem 0 1.5rem;
    text-align: center;
}
.divider::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(
        to right,
        transparent,
        rgba(148, 163, 184, 0.3),
        transparent
    );
}
.divider-text {
    position: relative;
    display: inline-block;
    padding: 0 1rem;
    background: rgba(255, 255, 255, 0.8);
    font-size: 0.8125rem;
    color: #64748b;
    font-weight: 600;
}

/* Login Section */
.login-section {
    text-align: center;
}
.login-text {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 1rem;
}
.login-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}
.login-link:hover {
    background: rgba(16, 185, 129, 0.05);
    border-color: #10b981;
    transform: translateY(-1px);
}

/* Features List */
.features-list {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}
.feature-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.feature-text {
    font-size: 0.875rem;
    color: #64748b;
    font-weight: 500;
}

/* Success Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
}

.modal-container {
    background: white;
    border-radius: 24px;
    padding: 3rem 2rem;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalSlideUp {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.success-icon-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 2rem;
}

.success-icon {
    width: 96px;
    height: 96px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    color: white;
    margin: 0 auto;
    box-shadow: 0 12px 32px rgba(16, 185, 129, 0.4);
    animation: successPop 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.2s
        backwards;
}

.success-icon :deep(svg) {
    width: 56px;
    height: 56px;
}

@keyframes successPop {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.success-particles {
    position: absolute;
    inset: 0;
}

.particle {
    position: absolute;
    width: 8px;
    height: 8px;
    background: linear-gradient(135deg, #10b981, #34d399);
    border-radius: 50%;
    top: 50%;
    left: 50%;
    animation: particleBurst 1s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    opacity: 0;
}

.particle:nth-child(1) {
    animation-delay: 0.3s;
}
.particle:nth-child(2) {
    animation-delay: 0.35s;
}
.particle:nth-child(3) {
    animation-delay: 0.4s;
}
.particle:nth-child(4) {
    animation-delay: 0.45s;
}
.particle:nth-child(5) {
    animation-delay: 0.5s;
}
.particle:nth-child(6) {
    animation-delay: 0.55s;
}
.particle:nth-child(7) {
    animation-delay: 0.6s;
}
.particle:nth-child(8) {
    animation-delay: 0.65s;
}
.particle:nth-child(9) {
    animation-delay: 0.7s;
}
.particle:nth-child(10) {
    animation-delay: 0.75s;
}
.particle:nth-child(11) {
    animation-delay: 0.8s;
}
.particle:nth-child(12) {
    animation-delay: 0.85s;
}

@keyframes particleBurst {
    0% {
        transform: translate(-50%, -50%) translate(0, 0) scale(1);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%)
            translate(
                calc(60px * cos(var(--angle))),
                calc(60px * sin(var(--angle)))
            )
            scale(0);
        opacity: 0;
    }
}

.particle:nth-child(1) {
    --angle: 0deg;
}
.particle:nth-child(2) {
    --angle: 30deg;
}
.particle:nth-child(3) {
    --angle: 60deg;
}
.particle:nth-child(4) {
    --angle: 90deg;
}
.particle:nth-child(5) {
    --angle: 120deg;
}
.particle:nth-child(6) {
    --angle: 150deg;
}
.particle:nth-child(7) {
    --angle: 180deg;
}
.particle:nth-child(8) {
    --angle: 210deg;
}
.particle:nth-child(9) {
    --angle: 240deg;
}
.particle:nth-child(10) {
    --angle: 270deg;
}
.particle:nth-child(11) {
    --angle: 300deg;
}
.particle:nth-child(12) {
    --angle: 330deg;
}

.modal-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 1rem;
    letter-spacing: -0.02em;
}

.modal-message {
    font-size: 1rem;
    color: #64748b;
    line-height: 1.6;
    margin: 0 0 2rem;
}

.modal-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.modal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}

.modal-btn:active {
    transform: translateY(0);
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: translateY(40px) scale(0.95);
}

/* Responsive */
@media (max-width: 640px) {
    .auth-page {
        padding: 1rem;
    }
    .register-card {
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
    .register-form {
        gap: 1rem;
    }
    .features-list {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
    .modal-container {
        padding: 2.5rem 1.5rem;
    }
    .modal-title {
        font-size: 1.5rem;
    }
    .success-icon {
        width: 80px;
        height: 80px;
    }
    .success-icon :deep(svg) {
        width: 48px;
        height: 48px;
    }
}
</style>
