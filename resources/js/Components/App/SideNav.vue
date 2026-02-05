<!-- resources/js/Components/App/SideNav.vue -->
<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const path = computed(() => window.location.pathname || "/");
function isActive(prefix) {
    return path.value === prefix || path.value.startsWith(prefix + "/");
}
</script>

<template>
    <nav class="rounded-2xl bg-white p-3 text-sm shadow">
        <!-- Applicant Section -->
        <div class="px-2 py-1 text-xs uppercase tracking-wide text-slate-500">
            Applicant
        </div>
        <ul class="mb-3">
            <li>
                <Link
                    href="/vacancies"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/vacancies')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                    aria-current="page"
                >
                    Browse Jobs
                </Link>
            </li>
            <li>
                <Link
                    href="/applicant/profile"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/applicant/profile')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Profile
                </Link>
            </li>
            <li>
                <Link
                    href="/applicant/applications"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/applicant/applications')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Applications
                </Link>
            </li>
            <li>
                <Link
                    href="/applicant/notifications"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/applicant/notifications')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Interview Notices
                </Link>
            </li>
        </ul>

        <!-- HR Section -->
        <div class="px-2 py-1 text-xs uppercase tracking-wide text-slate-500">
            HR Staff
        </div>
        <ul>
            <li>
                <Link
                    href="/hr/vacancies"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/vacancies')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Manage Vacancies
                </Link>
            </li>
            <li>
                <Link
                    href="/hr/vacancies/create"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/vacancies/create')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Create Vacancy
                </Link>
            </li>
            <li>
                <Link
                    href="/hr/filter-resumes"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/filter-resumes')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Filter Resumes
                </Link>
            </li>
            <li>
                <Link
                    href="/hr/shortlist"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/shortlist')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Shortlist
                </Link>
            </li>
            <li>
                <Link
                    href="/hr/interviews"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/interviews')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Interviews
                </Link>
            </li>
            <li>
                <Link
                    href="/hr/employees/create"
                    class="block rounded-lg px-3 py-2 hover:bg-slate-100"
                    :class="
                        isActive('/hr/employees/create')
                            ? 'bg-primary/10 text-primary'
                            : ''
                    "
                >
                    Create Employee
                </Link>
            </li>
        </ul>
    </nav>
</template>
