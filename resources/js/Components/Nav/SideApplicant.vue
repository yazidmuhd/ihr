<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const path = computed(() => window.location.pathname || "/");
const isActive = (p) => path.value === p || path.value.startsWith(p + "/");
</script>

<template>
    <nav class="rounded-2xl bg-white p-3 text-sm shadow">
        <div class="px-2 py-1 text-xs uppercase tracking-wide text-slate-500">
            Applicant
        </div>
        <ul class="mt-1 space-y-1">
            <li>
                <Link
                    href="/app/dashboard"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/app/dashboard')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Dashboard
                </Link>
            </li>
            <li>
                <Link
                    href="/jobs"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/jobs') ? 'bg-primary/10 text-primary' : ''
                    "
                >
                    Browse Jobs
                </Link>
            </li>
            <li>
                <Link
                    href="/applications"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/applications')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    My Applications
                </Link>
            </li>
            <li>
                <Link
                    href="/profile"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/profile') ? 'bg-primary/10 text-primary' : ''
                    "
                >
                    Profile / Resume
                </Link>
            </li>
        </ul>
    </nav>
</template>

<style scoped>
.text-primary {
    @apply text-emerald-700;
}
.bg-primary\/10 {
    background-color: rgba(16, 185, 129, 0.1);
}
</style>
