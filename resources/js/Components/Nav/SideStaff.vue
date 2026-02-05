<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
const path = computed(() => window.location.pathname || "/");
const isActive = (p) => path.value === p || path.value.startsWith(p + "/");
</script>

<template>
    <nav
        class="rounded-2xl bg-white/80 p-3 text-sm shadow-xl ring-1 ring-slate-200/70 backdrop-blur"
    >
        <div class="px-2 py-1 text-xs uppercase tracking-wide text-slate-500">
            HR Staff
        </div>
        <ul class="mt-1 space-y-1">
            <li>
                <Link
                    href="/hr/vacancies"
                    class="nav-item"
                    :class="isActive('/hr/vacancies') ? 'nav-item-active' : ''"
                    >Manage Vacancies</Link
                >
            </li>
            <li>
                <Link
                    href="/hr/vacancies/create"
                    class="nav-item"
                    :class="
                        isActive('/hr/vacancies/create')
                            ? 'nav-item-active'
                            : ''
                    "
                    >Create Vacancy</Link
                >
            </li>
            <li>
                <Link
                    href="/hr/filter-resumes"
                    class="nav-item"
                    :class="
                        isActive('/hr/filter-resumes') ? 'nav-item-active' : ''
                    "
                    >Filter Resumes</Link
                >
            </li>
            <li>
                <Link
                    href="/hr/shortlist"
                    class="nav-item"
                    :class="isActive('/hr/shortlist') ? 'nav-item-active' : ''"
                    >Shortlist</Link
                >
            </li>
            <li>
                <Link
                    href="/hr/interviews"
                    class="nav-item"
                    :class="isActive('/hr/interviews') ? 'nav-item-active' : ''"
                    >Interviews</Link
                >
            </li>
            <li>
                <Link
                    href="/hr/employees/create"
                    class="nav-item"
                    :class="
                        isActive('/hr/employees/create')
                            ? 'nav-item-active'
                            : ''
                    "
                    >Create Employee</Link
                >
            </li>
        </ul>
    </nav>
</template>

<style scoped>
.nav-item {
    @apply block rounded-lg px-3 py-2 text-slate-700 transition;
    box-shadow: 0 0 0 0 rgba(2, 6, 23, 0);
}
.nav-item:hover {
    @apply bg-slate-100;
    transform: translateY(-1px);
    box-shadow: 0 14px 22px -18px rgba(2, 6, 23, 0.28);
}
.nav-item-active {
    @apply bg-emerald-50 text-emerald-700;
    box-shadow: 0 10px 18px -18px rgba(16, 185, 129, 0.45);
}
</style>
