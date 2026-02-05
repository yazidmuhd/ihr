<!-- resources/js/Components/Layouts/StaffLayout.vue -->
<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";

/* ✅ Notification bubble */
import NotificationBubble from "@/Components/NotificationBubble.vue";

const page = usePage();
const mobileOpen = ref(false);

/* Notifications injected by middleware (safe) */
const notifications = computed(() => page.props?.notifications || {});
const hrPending = computed(() =>
    Number(notifications.value.hr?.pending_interview_actions || 0),
);

/* Auth user (safe) */
const user = computed(() => page.props?.auth?.user || page.props?.user || null);

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

/* Hiring dropdown */
const hiringOpen = ref(false);
const hiringWrap = ref(null);
const hiringActive = computed(() =>
    [
        "/hr/shortlist",
        "/hr/interviews",
        "/hr/evaluations",
        "/hr/employees",
    ].some((p) => isActive(p)),
);

/* Close panels on navigation */
watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
        hiringOpen.value = false;
    },
);

/* Close hiring dropdown on outside click */
function onDocClick(e) {
    const el = hiringWrap.value;
    if (!el) return;
    if (hiringOpen.value && !el.contains(e.target)) hiringOpen.value = false;
}

onMounted(() => {
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("click", onDocClick);
});
onBeforeUnmount(() => {
    window.removeEventListener("scroll", onScroll);
    window.removeEventListener("click", onDocClick);
});

/* Logout */
function confirmLogout() {
    if (confirm("Sign out of i-HR?")) router.post("/logout");
}

/* Menu structure */
const primaryTabs = [
    { label: "Dashboard", href: "/hr/dashboard", icon: "dashboard" },
    { label: "Vacancies", href: "/hr/vacancies", icon: "briefcase" },
];

const hiringItems = [
    { label: "Shortlist", href: "/hr/shortlist", icon: "sparkles" },
    {
        label: "Interviews",
        href: "/hr/interviews",
        icon: "calendar",
        badge: () => hrPending.value,
        hint: "Invite, schedule & mark done",
    },
    {
        label: "Evaluations",
        href: "/hr/evaluations",
        icon: "clipboard",
        hint: "Rate panels & finalize scores",
    },
    {
        label: "Employees",
        href: "/hr/employees",
        icon: "users",
        hint: "Hired candidates list",
    },
];

/* Icons */
const icons = {
    dashboard: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>`,
    briefcase: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1"/><path d="M3 7h18v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/><path d="M3 12h18"/></svg>`,
    sparkles: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 2l1.5 5.5L19 9l-5.5 1.5L12 16l-1.5-5.5L5 9l5.5-1.5L12 2Z"/><path d="M5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3Z"/><path d="M18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3Z"/></svg>`,
    calendar: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>`,
    clipboard: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 3h6a2 2 0 0 1 2 2v2H7V5a2 2 0 0 1 2-2Z"/><path d="M7 7h10v14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7Z"/><path d="M9 12h6M9 16h6"/></svg>`,
    users: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
    hiring: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>`,
    chevron: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>`,
    menu: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>`,
    close: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>`,
    logout: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
    plus: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>`,
};

const tabClass = (href) => `nav-tab ${isActive(href) ? "nav-tab-active" : ""}`;
const hiringTabClass = computed(
    () => `nav-tab ${hiringActive.value ? "nav-tab-active" : ""}`,
);
</script>

<template>
    <div class="layout-wrapper">
        <!-- HEADER -->
        <header class="main-header" :class="{ 'header-scrolled': scrolled }">
            <!-- Brand Row -->
            <div class="header-container">
                <div class="header-row">
                    <!-- Brand -->
                    <Link href="/hr/dashboard" class="brand-link">
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
                                <span class="brand-badge">HR Console</span>
                            </div>
                            <div class="brand-tagline">
                                Recruitment & Hiring Platform
                            </div>
                        </div>
                    </Link>

                    <!-- Desktop Actions -->
                    <div class="header-actions">
                        <Link href="/hr/vacancies/create" class="create-button">
                            <span
                                class="button-icon"
                                v-html="icons.plus"
                            ></span>
                            <span>Create Vacancy</span>
                        </Link>

                        <div class="user-menu" v-if="user">
                            <div class="user-avatar">
                                {{
                                    (user.name || user.email || "U")
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </div>
                            <div class="user-info">
                                <div class="user-name">
                                    {{ user.name || user.email }}
                                </div>
                                <div class="user-email">{{ user.email }}</div>
                            </div>
                        </div>

                        <button
                            class="logout-button"
                            type="button"
                            @click="confirmLogout"
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
                    <nav class="main-nav" aria-label="HR Navigation">
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
                        </Link>

                        <!-- Hiring Dropdown -->
                        <div class="nav-dropdown" ref="hiringWrap">
                            <button
                                type="button"
                                :class="hiringTabClass"
                                @click.stop="hiringOpen = !hiringOpen"
                            >
                                <span
                                    class="nav-icon"
                                    v-html="icons.hiring"
                                ></span>
                                <span class="nav-label">Hiring</span>

                                <span
                                    v-if="hrPending"
                                    class="nav-notification"
                                    :title="`${hrPending} pending`"
                                >
                                    {{ hrPending }}
                                </span>

                                <span
                                    class="dropdown-chevron"
                                    :class="{ 'chevron-open': hiringOpen }"
                                    v-html="icons.chevron"
                                ></span>
                            </button>

                            <div
                                v-show="hiringOpen"
                                class="dropdown-menu"
                                role="menu"
                            >
                                <Link
                                    v-for="item in hiringItems"
                                    :key="item.href"
                                    :href="item.href"
                                    class="dropdown-item"
                                    :class="{
                                        'dropdown-item-active': isActive(
                                            item.href,
                                        ),
                                    }"
                                    @click="hiringOpen = false"
                                >
                                    <span
                                        class="dropdown-icon"
                                        v-html="icons[item.icon]"
                                    ></span>
                                    <div class="dropdown-content">
                                        <div class="dropdown-title">
                                            {{ item.label }}
                                            <span
                                                v-if="
                                                    item.badge && item.badge()
                                                "
                                                class="dropdown-badge"
                                            >
                                                {{ item.badge() }}
                                            </span>
                                        </div>
                                        <div
                                            v-if="item.hint"
                                            class="dropdown-hint"
                                        >
                                            {{ item.hint }}
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div v-show="mobileOpen" class="mobile-menu">
                <div class="header-container">
                    <div class="mobile-content">
                        <div class="mobile-section">
                            <div class="mobile-section-title">Main Menu</div>

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
                            </Link>
                        </div>

                        <div class="mobile-section">
                            <div class="mobile-section-title">
                                Hiring Process
                            </div>

                            <Link
                                v-for="item in hiringItems"
                                :key="`mh-${item.href}`"
                                :href="item.href"
                                class="mobile-item"
                                :class="{
                                    'mobile-item-active': isActive(item.href),
                                }"
                            >
                                <span
                                    class="mobile-icon"
                                    v-html="icons[item.icon]"
                                ></span>
                                <span class="mobile-label">{{
                                    item.label
                                }}</span>
                                <span
                                    v-if="item.badge && item.badge()"
                                    class="mobile-badge"
                                >
                                    {{ item.badge() }}
                                </span>
                            </Link>
                        </div>

                        <div class="mobile-actions">
                            <Link
                                href="/hr/vacancies/create"
                                class="mobile-button mobile-button-primary"
                            >
                                <span
                                    class="button-icon"
                                    v-html="icons.plus"
                                ></span>
                                <span>Create Vacancy</span>
                            </Link>

                            <button
                                class="mobile-button mobile-button-secondary"
                                type="button"
                                @click="confirmLogout"
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
        <main class="main-content">
            <slot />
        </main>

        <!-- ✅ Notification bubble -->
        <NotificationBubble />
    </div>
</template>

<style scoped>
/* Import distinctive fonts */
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;500;600;700;800&display=swap");

/* CSS Variables */
:root {
    --color-primary: #0ea5e9;
    --color-primary-dark: #0284c7;
    --color-secondary: #8b5cf6;
    --color-text: #0f172a;
    --color-text-light: #64748b;
    --color-border: #e2e8f0;
    --color-bg: #f8fafc;
    --color-card: #ffffff;
    --header-height: 140px;
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
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.02em;
}

.brand-badge {
    padding: 0.25rem 0.625rem;
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
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

.create-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.create-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
}

.button-icon {
    width: 18px;
    height: 18px;
}

.button-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* User Menu */
.user-menu {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: var(--color-bg);
    border-radius: 12px;
    border: 1px solid var(--color-border);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
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

.toggle-icon {
    width: 20px;
    height: 20px;
}

.toggle-icon :deep(svg) {
    width: 100%;
    height: 100%;
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
    background: rgba(14, 165, 233, 0.05);
    border-color: rgba(14, 165, 233, 0.2);
    color: var(--color-primary);
    transform: translateY(-1px);
}

.nav-tab-active {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-color: var(--color-primary);
    color: var(--color-primary-dark);
}

.nav-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.nav-icon :deep(svg) {
    width: 100%;
    height: 100%;
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

/* Dropdown */
.nav-dropdown {
    position: relative;
}

.dropdown-chevron {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
}

.chevron-open {
    transform: rotate(180deg);
}

.dropdown-chevron :deep(svg) {
    width: 100%;
    height: 100%;
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    min-width: 320px;
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    animation: dropdownSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes dropdownSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    color: var(--color-text);
    text-decoration: none;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid var(--color-border);
}

.dropdown-item:last-child {
    border-bottom: none;
}

.dropdown-item:hover {
    background: rgba(14, 165, 233, 0.05);
}

.dropdown-item-active {
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
}

.dropdown-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    margin-top: 0.125rem;
    color: var(--color-primary);
}

.dropdown-icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.dropdown-content {
    flex: 1;
    min-width: 0;
}

.dropdown-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.9375rem;
    margin-bottom: 0.25rem;
}

.dropdown-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 0.375rem;
    background: #ef4444;
    color: white;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
}

.dropdown-hint {
    font-size: 0.8125rem;
    color: var(--color-text-light);
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

.mobile-section:last-of-type {
    margin-bottom: 0;
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
    background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
    border-color: var(--color-primary);
    color: var(--color-primary-dark);
}

.mobile-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.mobile-icon :deep(svg) {
    width: 100%;
    height: 100%;
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
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.mobile-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
}

.mobile-button-secondary {
    background: white;
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.mobile-button-secondary:hover {
    background: #fef2f2;
    color: #ef4444;
    border-color: #fecaca;
}

/* Main Content */
.main-content {
    max-width: 1400px;
    margin: 0 auto;
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

/* Responsive */
@media (max-width: 1280px) {
    .header-container {
        padding: 0 1.5rem;
    }

    .main-content {
        padding: 1.5rem;
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

    .main-content {
        padding: 1rem;
    }
}
</style>
