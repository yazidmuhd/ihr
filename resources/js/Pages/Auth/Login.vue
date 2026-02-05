<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const page = usePage();
const status = computed(() => page.props?.status || "");

const showPwd = ref(false);

const form = useForm({
    login: "",
    password: "",
    remember: false,
});

const canSubmit = computed(() => !form.processing);

function submit() {
    form.post(route("login.store"));
}

function googleLogin() {
    window.location.href = route("oauth.google.redirect");
}

const icons = {
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    lock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
    eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
    arrowRight:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    userPlus:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>',
    google: '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M21.35 11.1H12v2.9h5.35c-.35 1.9-2 3.4-5.35 3.4-3.2 0-5.8-2.6-5.8-5.8S8.8 5.8 12 5.8c1.7 0 2.85.7 3.5 1.3l2-2C16.2 3.9 14.3 3 12 3 7.6 3 4 6.6 4 11s3.6 8 8 8c4.6 0 7.6-3.2 7.6-7.7 0-.5-.05-.9-.25-1.2Z"/></svg>',
};
</script>

<template>
    <Head title="Sign in" />

    <div class="auth-page">
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
            <div class="login-card">
                <div class="brand-section">
                    <div class="logo-wrapper">
                        <div class="logo-icon">
                            <span v-html="icons.sparkles"></span>
                        </div>
                    </div>
                    <h1 class="brand-title">Welcome Back</h1>
                    <p class="brand-subtitle">
                        Sign in to continue to your dashboard
                    </p>
                </div>

                <!-- Status message (e.g., google failure msg, etc.) -->
                <div v-if="status" class="status-box">
                    {{ status }}
                </div>

                <!-- Google Sign In -->
                <a class="google-btn" href="/auth/google/redirect">
                    <span class="google-icon" v-html="icons.google"></span>
                    <span>Continue with Google</span>
                </a>

                <div class="divider">
                    <span class="divider-text">or</span>
                </div>

                <form @submit.prevent="submit" class="login-form">
                    <div class="form-group">
                        <label class="form-label">Username or Email</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.user"></span>
                            <input
                                v-model="form.login"
                                type="text"
                                class="form-input"
                                placeholder="Enter your username or email"
                                autocomplete="username"
                            />
                        </div>
                        <div v-if="form.errors.login" class="form-error">
                            {{ form.errors.login }}
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon" v-html="icons.lock"></span>
                            <input
                                v-model="form.password"
                                :type="showPwd ? 'text' : 'password'"
                                class="form-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
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

                    <div class="options-row">
                        <label class="checkbox-label">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="checkbox-input"
                            />
                            <span class="checkbox-text">Remember me</span>
                        </label>
                        <Link href="/password/request" class="forgot-link">
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="submit-btn"
                    >
                        <span v-if="!form.processing">Sign In</span>
                        <span v-else>Signing In...</span>
                        <span class="btn-icon" v-html="icons.arrowRight"></span>
                    </button>
                </form>

                <div class="register-section">
                    <p class="register-text">Don't have an account?</p>
                    <Link href="/register" class="register-link">
                        <span class="link-icon" v-html="icons.userPlus"></span>
                        <span>Create a new account</span>
                    </Link>
                </div>
            </div>

            <div class="features-list">
                <div class="feature-item">
                    <span class="feature-icon" v-html="icons.check"></span>
                    <span class="feature-text">Secure authentication</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon" v-html="icons.check"></span>
                    <span class="feature-text">Fast & reliable</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon" v-html="icons.check"></span>
                    <span class="feature-text">24/7 support</span>
                </div>
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
    max-width: 440px;
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

/* Login Card */
.login-card {
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
    margin-bottom: 1.25rem;
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

/* Google button */
.google-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 0.9rem 1rem;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.35);
    background: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s;
}
.google-btn:hover {
    transform: translateY(-1px);
    border-color: rgba(148, 163, 184, 0.7);
}
.google-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}

/* Divider */
.divider {
    position: relative;
    margin: 1.25rem 0 1.5rem;
    text-align: center;
}
.divider.small {
    margin: 1.25rem 0 1.25rem;
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
    font-weight: 700;
}

/* Form */
.login-form {
    display: grid;
    gap: 1.5rem;
}

/* Form Group */
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

/* Options Row */
.options-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}
.checkbox-input {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border: 1px solid rgba(148, 163, 184, 0.3);
    cursor: pointer;
    accent-color: #10b981;
}
.checkbox-text {
    font-size: 0.875rem;
    color: #64748b;
    user-select: none;
}
.forgot-link {
    font-size: 0.875rem;
    color: #10b981;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}
.forgot-link:hover {
    color: #059669;
    text-decoration: underline;
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
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
}
.submit-btn:active {
    transform: translateY(0);
}
.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Register Section */
.register-section {
    text-align: center;
    margin-top: 1.25rem;
}
.register-text {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 1rem;
}
.register-link {
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
.register-link:hover {
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

/* Responsive */
@media (max-width: 640px) {
    .auth-page {
        padding: 1rem;
    }
    .login-card {
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
    .options-row {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    .features-list {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
    }
}
</style>
