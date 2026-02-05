<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue";

const mobileOpen = ref(false);
const page = usePage();

/* === CSRF (works with Inertia fetch) === */
const csrf = computed(
    () =>
        page.props?.csrf_token ||
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ||
        "",
);

/* Current path + component (reactive with Inertia) */
const currentPath = computed(() => {
    try {
        return new URL(page.url, window.location.origin).pathname || "/";
    } catch {
        return window.location.pathname || "/";
    }
});
const currentComponent = computed(() => (page.component || "").toLowerCase());

/* Menu items */
const items = [
    { key: "dashboard", label: "Dashboard", href: "/applicant/dashboard" },
    { key: "jobs", label: "Jobs", href: "/applicant/jobs" },
    { key: "applications", label: "Applications", href: "/applications" },
    { key: "profile", label: "Profile", href: "/profile" },
];

/* URL-based match */
const isOnPath = (href) =>
    currentPath.value === href || currentPath.value.startsWith(href + "/");

/* Component-based match (covers /applicant/jobs and /applicant/jobs/:id) */
const componentKey = computed(() => {
    const c = currentComponent.value; // e.g. "Applicant/Jobs/Show"
    const p = currentPath.value; // e.g. "/applicant/jobs/12"

    if (c.startsWith("applicant/jobs") || p.startsWith("/applicant/jobs"))
        return "jobs";
    if (
        c.startsWith("applicant/dashboard") ||
        p.startsWith("/applicant/dashboard")
    )
        return "dashboard";
    if (c.includes("applications") || p.startsWith("/applications"))
        return "applications";
    if (c.includes("profile") || p.startsWith("/profile")) return "profile";
    return null;
});

/* Resolve active key using component first, then URL */
const activeKey = computed(() => {
    if (componentKey.value) return componentKey.value;
    const hit = items.find((i) => isOnPath(i.href));
    return hit ? hit.key : "dashboard";
});

/* Theme (soft gradients) */
const themes = {
    dashboard: ["#06b6d4", "#3b82f6"], // cyan → blue (muted via CSS)
    jobs: ["#10b981", "#34d399"], // emerald
    applications: ["#8b5cf6", "#a78bfa"], // violet
    profile: ["#f59e0b", "#fbbf24"], // amber
};
const activeStyle = computed(() => {
    const [from, to] = themes[activeKey.value] || themes.dashboard;
    return { "--accent-from": from, "--accent-to": to };
});

watch(
    () => page.url,
    () => (mobileOpen.value = false),
);

/* Header shadow on scroll */
const scrolled = ref(false);
const onScroll = () => (scrolled.value = window.scrollY > 2);
onMounted(() => {
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener("scroll", onScroll));

/* === FIXED LOGOUT: send CSRF with the POST === */
function confirmLogout() {
    if (!confirm("Sign out of i-HR?")) return;
    router.post(
        "/logout",
        { _token: csrf.value }, // ✅ CSRF included
        { preserveScroll: true },
    );
}

/* Ink-burst click */
function ink(e) {
    const el = e.currentTarget;
    const rect = el.getBoundingClientRect();
    el.style.setProperty("--ink-x", `${e.clientX - rect.left}px`);
    el.style.setProperty("--ink-y", `${e.clientY - rect.top}px`);
    el.classList.add("do-ink");
    setTimeout(() => el.classList.remove("do-ink"), 450);
}

const linkClass = (key, href) =>
    `nav-link btn-ghost ${activeKey.value === key || isOnPath(href) ? "is-active" : ""}`;
</script>

<template>
    <nav
        class="sticky top-0 z-40 border-b bg-white/90 backdrop-blur transition-shadow"
        :class="scrolled ? 'shadow-sm' : ''"
        :style="activeStyle"
    >
        <div class="accent-line" aria-hidden="true"></div>

        <div
            class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <Link href="/" class="flex items-center gap-2">
                <img src="/brand/HRLogo.png" alt="i-HR" class="h-7 w-auto" />
                <span class="font-bold tracking-tight">i-HR</span>
            </Link>

            <div class="desktop-menu hidden items-center gap-2 text-sm md:flex">
                <Link
                    v-for="it in items"
                    :key="it.key"
                    :href="it.href"
                    :class="linkClass(it.key, it.href)"
                    :aria-current="
                        activeKey.value === it.key ? 'page' : undefined
                    "
                    @mousedown="ink"
                    >{{ it.label }}</Link
                >

                <button class="btn-logout" @click="confirmLogout">
                    Logout
                </button>
            </div>

            <button
                class="mobile-toggle btn-ghost px-3 md:hidden"
                @click="mobileOpen = !mobileOpen"
                aria-label="Toggle menu"
                :aria-expanded="mobileOpen"
                aria-controls="applicant-mobile"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        v-if="!mobileOpen"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                    <path
                        v-elsez
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div
            v-show="mobileOpen"
            id="applicant-mobile"
            class="border-t md:hidden"
        >
            <div class="mx-auto grid max-w-7xl gap-2 px-4 py-3 text-sm">
                <Link
                    v-for="it in items"
                    :key="'m-' + it.key"
                    :href="it.href"
                    class="nav-link btn-ghost w-full justify-start"
                    :class="activeKey.value === it.key ? 'is-active' : ''"
                    :aria-current="
                        activeKey.value === it.key ? 'page' : undefined
                    "
                    @mousedown="ink"
                    >{{ it.label }}</Link
                >

                <button
                    class="btn-logout mt-2 w-full justify-center"
                    @click="confirmLogout"
                >
                    Logout
                </button>
            </div>
        </div>
    </nav>
</template>

<style scoped>
/* Accent matches active tab; softened via opacity */
.accent-line {
    height: 2px;
    background: linear-gradient(90deg, var(--accent-from), var(--accent-to));
    opacity: 0.8;
}
/* Links */
.nav-link {
    position: relative;
    border-radius: 0.75rem;
    padding: 0.5rem 0.85rem;
    transition:
        transform 0.15s,
        box-shadow 0.18s,
        background 0.25s,
        color 0.25s;
}
.nav-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px -18px rgba(2, 6, 23, 0.28);
}
/* Softer active pill */
.nav-link.is-active {
    color: #fff !important;
    background-image: linear-gradient(
        135deg,
        var(--accent-from),
        var(--accent-to)
    ) !important;
    filter: saturate(0.85) brightness(0.98);
    box-shadow: 0 14px 26px -18px rgba(2, 6, 23, 0.32);
}
/* Keyboard focus */
.nav-link:focus-visible {
    outline: 2px solid color-mix(in oklab, var(--accent-to) 22%, transparent);
    outline-offset: 2px;
    border-radius: 0.85rem;
}
/* Click ink-burst */
.nav-link::after {
    content: "";
    position: absolute;
    top: var(--ink-y, 50%);
    left: var(--ink-x, 50%);
    width: 0;
    height: 0;
    transform: translate(-50%, -50%) scale(0);
    pointer-events: none;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.45) 0%,
        rgba(255, 255, 255, 0) 60%
    );
    border-radius: 9999px;
    opacity: 0;
}
.nav-link.do-ink::after {
    animation: ink 0.45s ease-out forwards;
}
@keyframes ink {
    0% {
        width: 0;
        height: 0;
        opacity: 0.75;
        transform: translate(-50%, -50%) scale(0);
    }
    100% {
        width: 220%;
        height: 220%;
        opacity: 0;
        transform: translate(-50%, -50%) scale(1);
    }
}

/* Logout */
.btn-logout {
    @apply inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium text-white transition;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    filter: saturate(0.9) brightness(0.98);
    box-shadow: 0 8px 22px -12px rgba(16, 185, 129, 0.45);
}
.btn-logout:hover {
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    transform: translateY(-1px);
    box-shadow: 0 16px 28px -16px rgba(16, 185, 129, 0.45);
}
.btn-logout:focus-visible {
    outline: 2px solid #10b98144;
    outline-offset: 2px;
}

@media (min-width: 768px) {
    .mobile-toggle {
        display: none !important;
    }
    .desktop-menu {
        display: flex !important;
    }
}
</style>
