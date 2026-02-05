<!-- resources/js/Components/App/TopNav.vue -->
<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount } from "vue";

const mobileOpen = ref(false);
const path = computed(() => window.location.pathname || "/");

function isActive(prefix) {
    return path.value === prefix || path.value.startsWith(prefix + "/");
}
function closeOnNavigate() {
    mobileOpen.value = false;
}

// subtle shadow on scroll
const scrolled = ref(false);
function handleScroll() {
    scrolled.value = window.scrollY > 2;
}
onMounted(() => {
    handleScroll();
    window.addEventListener("scroll", handleScroll, { passive: true });
});
onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <nav
        class="sticky top-0 z-40 border-b bg-white/90 backdrop-blur transition-shadow"
        :class="scrolled ? 'shadow-sm' : ''"
    >
        <div
            class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Brand -->
            <Link
                href="/"
                class="flex items-center gap-2"
                @click="closeOnNavigate"
            >
                <img src="/brand/i-hr-logo.svg" alt="i-HR" class="h-6 w-auto" />
                <span class="font-bold tracking-tight">i-HR</span>
            </Link>

            <!-- Desktop nav -->
            <div class="hidden items-center gap-2 text-sm md:flex">
                <Link
                    href="/vacancies"
                    class="btn-ghost"
                    :class="isActive('/vacancies') ? 'text-primary' : ''"
                >
                    Jobs
                </Link>

                <!-- Applicant -->
                <Link
                    href="/applicant/profile"
                    class="btn-ghost"
                    :class="
                        isActive('/applicant/profile') ? 'text-primary' : ''
                    "
                >
                    Profile
                </Link>
                <Link
                    href="/applicant/applications"
                    class="btn-ghost"
                    :class="
                        isActive('/applicant/applications')
                            ? 'text-primary'
                            : ''
                    "
                >
                    Applications
                </Link>
                <Link
                    href="/applicant/notifications"
                    class="btn-ghost"
                    :class="
                        isActive('/applicant/notifications')
                            ? 'text-primary'
                            : ''
                    "
                >
                    Notices
                </Link>

                <!-- HR -->
                <Link
                    href="/hr/vacancies"
                    class="btn-ghost"
                    :class="isActive('/hr') ? 'text-primary' : ''"
                >
                    HR
                </Link>

                <Link href="/login" class="btn-primary">Sign in</Link>
            </div>

            <!-- Mobile toggle -->
            <button
                class="btn-ghost px-3 md:hidden"
                aria-label="Toggle menu"
                @click="mobileOpen = !mobileOpen"
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
                        v-else
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div v-show="mobileOpen" class="border-t md:hidden">
            <div class="mx-auto grid max-w-7xl gap-2 px-4 py-3 text-sm">
                <Link
                    href="/vacancies"
                    class="btn-ghost w-full justify-start"
                    :class="isActive('/vacancies') ? 'text-primary' : ''"
                    @click="closeOnNavigate"
                >
                    Jobs
                </Link>

                <div class="mt-1 text-xs uppercase text-slate-500">
                    Applicant
                </div>
                <Link
                    href="/applicant/profile"
                    class="btn-ghost w-full justify-start"
                    :class="
                        isActive('/applicant/profile') ? 'text-primary' : ''
                    "
                    @click="closeOnNavigate"
                >
                    Profile
                </Link>
                <Link
                    href="/applicant/applications"
                    class="btn-ghost w-full justify-start"
                    :class="
                        isActive('/applicant/applications')
                            ? 'text-primary'
                            : ''
                    "
                    @click="closeOnNavigate"
                >
                    Applications
                </Link>
                <Link
                    href="/applicant/notifications"
                    class="btn-ghost w-full justify-start"
                    :class="
                        isActive('/applicant/notifications')
                            ? 'text-primary'
                            : ''
                    "
                    @click="closeOnNavigate"
                >
                    Notices
                </Link>

                <div class="mt-2 text-xs uppercase text-slate-500">
                    HR Staff
                </div>
                <Link
                    href="/hr/vacancies"
                    class="btn-ghost w-full justify-start"
                    :class="isActive('/hr') ? 'text-primary' : ''"
                    @click="closeOnNavigate"
                >
                    HR
                </Link>

                <Link
                    href="/login"
                    class="btn-primary mt-2 w-full"
                    @click="closeOnNavigate"
                >
                    Sign in
                </Link>
            </div>
        </div>
    </nav>
</template>
