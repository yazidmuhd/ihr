<!-- resources/js/Components/Layouts/ApplicantLayout.vue -->
<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";

/* ✅ Notification bubble */
import NotificationBubble from "@/Components/NotificationBubble.vue";

const props = defineProps({
    showSidebar: { type: Boolean, default: false },
    contentMax: { type: String, default: "max-w-6xl" },
});

const page = usePage();
const mobileOpen = ref(false);
const showLogoutModal = ref(false);

// Global notifications from middleware
const notifications = computed(() => page.props?.notifications || {});
const pendingInterviews = computed(() =>
    Number(notifications.value.applicant?.pending_interviews || 0),
);

/* ✅ Auth user (merged with page user if exists) */
const authUser = computed(() => page.props?.auth?.user || null);
const pageUser = computed(() => page.props?.user || null);

const user = computed(() => {
    if (!authUser.value) return pageUser.value;
    if (!pageUser.value) return authUser.value;
    return { ...authUser.value, ...pageUser.value };
});

/* ✅ Avatar src with cache-bust */
const avatarSrc = computed(() => {
    const u = user.value;
    const src = u?.avatarUrl || u?.avatar_url || u?.avatar || null;
    if (!src) return null;

    const v = u?.updated_at || Date.now();
    const sep = String(src).includes("?") ? "&" : "?";
    return `${src}${sep}v=${encodeURIComponent(v)}`;
});

/* Active path */
const currentPath = computed(() => {
    const url = page.url || "/";
    const pathOnly = String(url).split("?")[0] || "/";
    return pathOnly;
});
const isActive = (p) =>
    currentPath.value === p || currentPath.value.startsWith(p + "/");

/* Sticky shadow on scroll */
const scrolled = ref(false);
const onScroll = () => (scrolled.value = (window.scrollY || 0) > 10);

/* Close mobile menu on navigation */
watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
    },
);

onMounted(() => {
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
});
onBeforeUnmount(() => {
    window.removeEventListener("scroll", onScroll);
});

/* Logout */
function openLogoutModal() {
    showLogoutModal.value = true;
}

function closeLogoutModal() {
    showLogoutModal.value = false;
}

function confirmLogout() {
    closeLogoutModal();
    router.post("/logout");
}

/* Menu structure */
const primaryTabs = [
    { label: "Dashboard", href: "/applicant/dashboard", icon: "dashboard" },
    { label: "Browse Jobs", href: "/applicant/jobs", icon: "search" },
    { label: "My Applications", href: "/applications", icon: "inbox" },
    {
        label: "Interviews",
        href: "/app/interviews",
        icon: "calendar",
        badge: () => pendingInterviews.value,
    },
    { label: "My Resume", href: "/app/resume", icon: "document" },
];

/* Icons */
const icons = {
    dashboard: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>`,
    search: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>`,
    inbox: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 14h5l2 3h4l2-3h5"/><path d="M3 14V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    document: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>`,
    menu: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>`,
    close: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
    logout: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
    user: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>',
};

const tabClass = (href) => `nav-tab ${isActive(href) ? "nav-tab-active" : ""}`;
</script>

<template>
    <div class="layout-wrapper">
        <!-- HEADER -->
        <header class="main-header" :class="{ 'header-scrolled': scrolled }">
            <!-- Brand Row -->
            <div class="header-container">
                <div class="header-row">
                    <!-- Brand -->
                    <Link href="/applicant/dashboard" class="brand-link">
                        <div class="brand-logo">
                            <img
                                src="/brand/HRLogo.png"
                                alt="i-HR"
                                class="logo-image"
                            />
                        </div>
                        <div class="brand-content">
                            <div class="brand-name">
                                <span class="brand-text">i-HR</span>
                                <span class="brand-badge">Career Portal</span>
                            </div>
                            <div class="brand-tagline">
                                Your Job Application Platform
                            </div>
                        </div>
                    </Link>

                    <!-- Desktop Actions -->
                    <div class="header-actions">
                        <Link href="/applicant/jobs" class="browse-button">
                            <span
                                class="button-icon"
                                v-html="icons.search"
                            ></span>
                            <span>Browse Jobs</span>
                        </Link>

                        <Link href="/profile" class="user-menu" v-if="user">
                            <div class="user-avatar">
                                <img
                                    v-if="avatarSrc"
                                    :src="avatarSrc"
                                    alt="Avatar"
                                    class="user-avatar-img"
                                />
                                <span v-else>
                                    {{
                                        (user.name || user.email || "U")
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </span>
                            </div>
                            <div class="user-info">
                                <div class="user-name">
                                    {{ user.name || user.email }}
                                </div>
                                <div class="user-email">{{ user.email }}</div>
                            </div>
                        </Link>

                        <button
                            class="logout-button"
                            type="button"
                            @click="openLogoutModal"
                            title="Logout"
                        >
                            <span
                                class="button-icon"
                                v-html="icons.logout"
                            ></span>
                        </button>
                    </div>

                    <!-- Mobile Toggle -->
                    <button
                        class="mobile-toggle"
                        @click="mobileOpen = !mobileOpen"
                        aria-label="Toggle menu"
                        :aria-expanded="mobileOpen"
                    >
                        <span
                            class="toggle-icon"
                            v-html="mobileOpen ? icons.close : icons.menu"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- Navigation Row -->
            <div class="nav-row">
                <div class="header-container">
                    <nav class="main-nav" aria-label="Applicant Navigation">
                        <Link
                            v-for="tab in primaryTabs"
                            :key="tab.href"
                            :href="tab.href"
                            :class="tabClass(tab.href)"
                            :aria-current="
                                isActive(tab.href) ? 'page' : undefined
                            "
                        >
                            <span
                                class="nav-icon"
                                v-html="icons[tab.icon]"
                            ></span>
                            <span class="nav-label">{{ tab.label }}</span>
                            <span
                                v-if="tab.badge && tab.badge()"
                                class="nav-notification"
                                :title="`${tab.badge()} pending`"
                            >
                                {{ tab.badge() }}
                            </span>
                        </Link>
                    </nav>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-show="mobileOpen" class="mobile-menu">
                <div class="header-container">
                    <div class="mobile-content">
                        <div class="mobile-section">
                            <div class="mobile-section-title">Navigation</div>

                            <Link
                                v-for="tab in primaryTabs"
                                :key="`m-${tab.href}`"
                                :href="tab.href"
                                class="mobile-item"
                                :class="{
                                    'mobile-item-active': isActive(tab.href),
                                }"
                            >
                                <span
                                    class="mobile-icon"
                                    v-html="icons[tab.icon]"
                                ></span>
                                <span class="mobile-label">{{
                                    tab.label
                                }}</span>
                                <span
                                    v-if="tab.badge && tab.badge()"
                                    class="mobile-badge"
                                >
                                    {{ tab.badge() }}
                                </span>
                            </Link>
                        </div>

                        <div class="mobile-actions">
                            <Link
                                href="/applicant/jobs"
                                class="mobile-button mobile-button-primary"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.search"
                                ></span>
                                <span>Browse Jobs</span>
                            </Link>

                            <Link
                                href="/profile"
                                class="mobile-button mobile-button-secondary"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.user"
                                ></span>
                                <span>Edit Profile</span>
                            </Link>

                            <button
                                class="mobile-button mobile-button-secondary"
                                type="button"
                                @click="openLogoutModal"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.logout"
                                ></span>
                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="main-content" :class="props.contentMax">
            <slot />
        </main>

        <!-- Floating Interview Notification -->
        <transition name="fab-slide">
            <Link
                v-if="pendingInterviews"
                href="/app/interviews"
                class="interview-fab"
                title="You have interview invitations waiting"
            >
                <div class="fab-pulse"></div>
                <div class="fab-icon">
                    <span v-html="icons.calendar"></span>
                </div>
                <div class="fab-text">
                    <div class="fab-title">Interview Invite</div>
                    <div class="fab-count">{{ pendingInterviews }} pending</div>
                </div>
                <div class="fab-badge">{{ pendingInterviews }}</div>
            </Link>
        </transition>

        <!-- Logout Confirmation Modal -->
        <transition name="modal">
            <div
                v-if="showLogoutModal"
                class="modal-backdrop"
                @click="closeLogoutModal"
            >
                <div class="modal-content" @click.stop>
                    <button class="modal-close" @click="closeLogoutModal">
                        <span v-html="icons.x"></span>
                    </button>

                    <div class="modal-icon">
                        <span v-html="icons.alert"></span>
                        <div class="modal-icon-ring"></div>
                    </div>

                    <h3 class="modal-title">Sign Out?</h3>
                    <p class="modal-text">
                        Are you sure you want to sign out of your account?
                    </p>

                    <div class="modal-actions">
                        <button
                            @click="closeLogoutModal"
                            class="modal-btn modal-btn-cancel"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmLogout"
                            class="modal-btn modal-btn-confirm"
                        >
                            <span class="btn-icon" v-html="icons.logout"></span>
                            <span>Sign Out</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Notification Bubble -->
        <NotificationBubble />
    </div>
</template>

<style scoped>
/* Import distinctive fonts */
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables - EMERALD THEME */
:root {
    --color-primary: #10b981;
    --color-primary-dark: #059669;
    --color-secondary: #34d399;
    --color-success: #10b981;
    --color-text: #0f172a;
    --color-text-light: #64748b;
    --color-border: #e2e8f0;
    --color-bg: #f8fafc;
    --color-card: #ffffff;
}

/* Icons */
.button-icon,
.toggle-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.button-icon :deep(svg),
.toggle-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.nav-icon,
.mobile-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.nav-icon :deep(svg),
.mobile-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.btn-icon {
    display: inline-flex;
    width: 16px;
    height: 16px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* Layout Wrapper */
.layout-wrapper {
    min-height: 100vh;
    background: var(--color-bg);
    font-family:
        "Plus Jakarta Sans",
        -apple-system,
        BlinkMacSystemFont,
        sans-serif;
}

/* Header */
.main-header {
    position: sticky;
    top: 0;
    z-index: 50;
    background: white;
    border-bottom: 1px solid var(--color-border);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-scrolled {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
}

.header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
    height: 80px;
}

/* Brand */
.brand-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.brand-link:hover {
    transform: scale(1.02);
}

.brand-logo {
    flex-shrink: 0;
}

.logo-image {
    height: 48px;
    width: auto;
}

.brand-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.brand-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.brand-text {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
}

.brand-badge {
    padding: 0.25rem 0.625rem;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 6px;
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--color-primary-dark);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.brand-tagline {
    font-size: 0.8125rem;
    color: var(--color-text-light);
    font-weight: 500;
}

/* Header Actions */
.header-actions {
    display: none;
    align-items: center;
    gap: 1rem;
}

@media (min-width: 1024px) {
    .header-actions {
        display: flex;
    }
}

.browse-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.browse-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

/* User Menu - NOW CLICKABLE */
.user-menu {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: var(--color-bg);
    border-radius: 12px;
    border: 1px solid var(--color-border);
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
}

.user-menu:hover {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.05),
        rgba(5, 150, 105, 0.02)
    );
    border-color: rgba(16, 185, 129, 0.3);
    transform: translateY(-1px);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
    overflow: hidden; /* ✅ for img */
}

.user-avatar-img {
    width: 100%;
    height: 100%;
    border-radius: 9999px;
    object-fit: cover;
    display: block;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-email {
    font-size: 0.75rem;
    color: var(--color-text-light);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.logout-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    color: var(--color-text-light);
    cursor: pointer;
    transition: all 0.3s ease;
}

.logout-button:hover {
    background: #fef2f2;
    color: #ef4444;
    border-color: #fecaca;
}

/* Mobile Toggle */
.mobile-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

@media (min-width: 1024px) {
    .mobile-toggle {
        display: none;
    }
}

.mobile-toggle:hover {
    background: var(--color-bg);
}

/* Navigation Row */
.nav-row {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-top: 1px solid var(--color-border);
}

@media (max-width: 1023px) {
    .nav-row {
        display: none;
    }
}

.main-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0;
}

.nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    color: var(--color-text);
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
}

.nav-tab:hover {
    background: rgba(16, 185, 129, 0.05);
    border-color: rgba(16, 185, 129, 0.2);
    color: var(--color-primary);
    transform: translateY(-1px);
}

.nav-tab-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-color: var(--color-primary);
    color: var(--color-primary-dark);
}

.nav-label {
    white-space: nowrap;
}

.nav-notification {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 0.375rem;
    background: #ef4444;
    color: white;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
}

/* Mobile Menu */
.mobile-menu {
    background: white;
    border-top: 1px solid var(--color-border);
    animation: mobileSlideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@media (min-width: 1024px) {
    .mobile-menu {
        display: none;
    }
}

@keyframes mobileSlideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 1000px;
    }
}

.mobile-content {
    padding: 1.5rem 0;
}

.mobile-section {
    margin-bottom: 1.5rem;
}

.mobile-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-light);
    margin-bottom: 0.75rem;
    padding: 0 0.5rem;
}

.mobile-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    color: var(--color-text);
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.mobile-item:hover {
    background: var(--color-bg);
}

.mobile-item-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-color: var(--color-primary);
    color: var(--color-primary-dark);
}

.mobile-label {
    flex: 1;
}

.mobile-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 0.375rem;
    background: #ef4444;
    color: white;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
}

.mobile-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-border);
}

.mobile-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mobile-button-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.mobile-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.mobile-button-secondary {
    background: white;
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.mobile-button-secondary:hover {
    background: var(--color-bg);
}

/* Main Content */
.main-content {
    margin: 0 auto;
    width: 100%;
    padding: 2rem;
    animation: contentFadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes contentFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Floating Interview FAB */
.interview-fab {
    position: fixed;
    right: 2rem;
    bottom: 2rem;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-radius: 16px;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fabAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fabAppear {
    from {
        opacity: 0;
        transform: translateY(100px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.interview-fab:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(16, 185, 129, 0.5);
}

.fab-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 16px;
    border: 2px solid rgba(16, 185, 129, 0.6);
    animation: fabPulse 2s ease-in-out infinite;
}

@keyframes fabPulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.8;
    }
    50% {
        transform: scale(1.1);
        opacity: 0;
    }
}

.fab-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fab-icon :deep(svg) {
    width: 22px;
    height: 22px;
}

.fab-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    flex: 1;
}

.fab-title {
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.fab-count {
    font-size: 0.75rem;
    opacity: 0.9;
}

.fab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 0.5rem;
    background: rgba(239, 68, 68, 0.95);
    color: white;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    flex-shrink: 0;
}

/* FAB Transitions */
.fab-slide-enter-active {
    animation: fabSlideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.fab-slide-leave-active {
    animation: fabSlideOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes fabSlideIn {
    from {
        opacity: 0;
        transform: translateY(100px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes fabSlideOut {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(100px) scale(0.8);
    }
}

/* Logout Modal */
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
    background: linear-gradient(
        135deg,
        rgba(245, 158, 11, 0.15),
        rgba(217, 119, 6, 0.15)
    );
    color: #f59e0b;
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
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
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}
.modal-btn-confirm:hover {
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
@media (max-width: 1280px) {
    .header-container {
        padding: 0 1.5rem;
    }

    .main-content {
        padding: 1.5rem;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }

    .interview-fab {
        right: 1rem;
        bottom: 1rem;
        padding: 0.875rem 1rem;
        gap: 0.75rem;
    }

    .fab-icon {
        width: 36px;
        height: 36px;
    }

    .fab-icon :deep(svg) {
        width: 18px;
        height: 18px;
    }

    .fab-title {
        font-size: 0.8125rem;
    }

    .fab-count {
        font-size: 0.6875rem;
    }

    .fab-badge {
        min-width: 24px;
        height: 24px;
        font-size: 0.75rem;
    }
}

@media (max-width: 640px) {
    .header-row {
        height: 70px;
    }

    .brand-text {
        font-size: 1.25rem;
    }

    .brand-tagline {
        font-size: 0.75rem;
    }

    .logo-image {
        height: 40px;
    }
}
</style>
