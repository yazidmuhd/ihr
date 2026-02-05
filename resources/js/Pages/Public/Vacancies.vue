<!-- resources/js/Pages/Public/Vacancies.vue -->
<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import FormField from "@/Components/App/FormField.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

/* Demo data: now includes salary + tags for nicer cards */
const vacancies = ref([
    {
        id: 1,
        title: "Frontend Engineer",
        dept: "IT",
        type: "Full-time",
        location: "Kuala Lumpur",
        posted_at: "2 days ago",
        salary: "RM 6k–9k",
        tags: ["Vue", "Tailwind", "REST"],
    },
    {
        id: 2,
        title: "HR Generalist",
        dept: "HR",
        type: "Contract",
        location: "Remote",
        posted_at: "5 days ago",
        salary: "RM 4k–6k",
        tags: ["Recruitment", "Payroll", "Policy"],
    },
    {
        id: 3,
        title: "Backend Engineer",
        dept: "IT",
        type: "Full-time",
        location: "Remote",
        posted_at: "1 day ago",
        salary: "RM 7k–10k",
        tags: ["Laravel", "PostgreSQL", "API"],
    },
]);

/* Filters (UI-only) */
const q = ref("");
const fType = ref("All");
const fDept = ref("All");
const types = computed(() => [
    "All",
    ...new Set(vacancies.value.map((v) => v.type)),
]);
const depts = computed(() => [
    "All",
    ...new Set(vacancies.value.map((v) => v.dept)),
]);

const filtered = computed(() => {
    const k = q.value.trim().toLowerCase();
    return vacancies.value.filter((v) => {
        const matchesQ =
            !k ||
            v.title.toLowerCase().includes(k) ||
            v.dept.toLowerCase().includes(k) ||
            v.location.toLowerCase().includes(k) ||
            v.type.toLowerCase().includes(k);
        const matchesType = fType.value === "All" || v.type === fType.value;
        const matchesDept = fDept.value === "All" || v.dept === fDept.value;
        return matchesQ && matchesType && matchesDept;
    });
});

function resetFilters() {
    q.value = "";
    fType.value = "All";
    fDept.value = "All";
}

const activeChips = computed(() => {
    const chips = [];
    if (q.value.trim()) chips.push({ key: "q", label: `“${q.value.trim()}”` });
    if (fType.value !== "All") chips.push({ key: "type", label: fType.value });
    if (fDept.value !== "All") chips.push({ key: "dept", label: fDept.value });
    return chips;
});
function removeChip(key) {
    if (key === "q") q.value = "";
    if (key === "type") fType.value = "All";
    if (key === "dept") fDept.value = "All";
}
</script>

<template>
    <ApplicantLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="page-title">Open Vacancies</h1>
                    <p class="page-sub">
                        Browse and apply for roles that fit you.
                    </p>
                </div>
                <Link href="/login" class="btn-ghost">Sign in</Link>
            </div>
        </template>

        <!-- Brand header + crisp white form -->
        <section
            class="mb-6 rounded-2xl bg-gradient-to-br from-primary to-primary-dark p-5 text-white shadow"
        >
            <div class="px-1">
                <h2 class="text-lg font-semibold">Find your next role</h2>
                <p class="text-sm/6 text-white/90">
                    Search and filter openings by keyword, type, and department.
                </p>
            </div>

            <div
                class="mt-4 rounded-xl bg-white p-5 text-slate-800 shadow-sm ring-1 ring-white/40"
            >
                <form @submit.prevent class="grid gap-5 md:grid-cols-12">
                    <!-- Search (4/12) -->
                    <div class="md:col-span-4">
                        <label class="label mb-1">Search</label>
                        <div class="relative">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                            >
                                <circle
                                    cx="11"
                                    cy="11"
                                    r="7"
                                    stroke-width="2"
                                />
                                <path d="M20 20l-3.5-3.5" stroke-width="2" />
                            </svg>
                            <input
                                v-model="q"
                                class="field h-12 w-full pl-10"
                                placeholder="Keyword: title, dept, location…"
                                aria-label="Search vacancies"
                            />
                        </div>
                        <p class="help mt-1">
                            Try “Frontend”, “HR”, or “Remote”.
                        </p>
                    </div>

                    <!-- Employment Type (4/12) -->
                    <div class="md:col-span-4">
                        <label class="label mb-1">Employment Type</label>
                        <select
                            v-model="fType"
                            class="field h-12 w-full"
                            aria-label="Employment Type"
                        >
                            <option v-for="t in types" :key="t" :value="t">
                                {{ t }}
                            </option>
                        </select>
                    </div>

                    <!-- Department (4/12) -->
                    <div class="md:col-span-4">
                        <label class="label mb-1">Department</label>
                        <select
                            v-model="fDept"
                            class="field h-12 w-full"
                            aria-label="Department"
                        >
                            <option v-for="d in depts" :key="d" :value="d">
                                {{ d }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 md:col-span-12">
                        <button
                            type="button"
                            class="btn-soft rounded-full px-5 py-2.5 shadow transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-slate-300"
                            @click="resetFilters"
                        >
                            Reset
                        </button>

                        <button
                            type="button"
                            class="btn-primary inline-flex items-center gap-2 rounded-full px-5 py-2.5 shadow-md transition hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-primary/30"
                        >
                            <!-- tiny funnel icon -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                            >
                                <path
                                    d="M3 5h18M6 12h12M10 19h4"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                </form>

                <!-- Active filter chips -->
                <div
                    v-if="activeChips.length"
                    class="mt-4 flex flex-wrap items-center gap-2"
                >
                    <span
                        v-for="c in activeChips"
                        :key="c.key"
                        class="badge-brand"
                    >
                        {{ c.label }}
                        <button
                            class="ml-1 text-xs hover:opacity-80"
                            @click="removeChip(c.key)"
                            aria-label="Remove filter"
                        >
                            ×
                        </button>
                    </span>
                    <button class="btn-ghost btn-sm" @click="resetFilters">
                        Clear all
                    </button>
                </div>
            </div>
        </section>

        <!-- Results header -->
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="muted">
                Showing <strong>{{ filtered.length }}</strong> position<span
                    v-if="filtered.length !== 1"
                    >s</span
                >
            </div>
            <div class="flex items-center gap-2">
                <Link href="/applicant/applications" class="btn-ghost btn-sm"
                    >My Applications</Link
                >
            </div>
        </div>

        <!-- Rich job cards -->
        <div v-if="filtered.length" class="space-y-3">
            <div
                v-for="v in filtered"
                :key="v.id"
                class="card card-hover flex items-start justify-between gap-4"
            >
                <!-- Left: logo + main info -->
                <div class="flex items-start gap-4">
                    <!-- simple logo placeholder with first letter -->
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 font-semibold text-primary"
                    >
                        {{ v.title.charAt(0) }}
                    </div>

                    <div>
                        <Link
                            :href="`/vacancies/${v.id}`"
                            class="btn-link text-lg font-semibold !no-underline hover:underline"
                        >
                            {{ v.title }}
                        </Link>
                        <div class="muted mt-0.5">
                            {{ v.dept }} • {{ v.location }}
                        </div>

                        <!-- Tags -->
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="badge-brand">{{ v.type }}</span>
                            <span
                                v-for="t in v.tags"
                                :key="t"
                                class="badge-muted"
                                >{{ t }}</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Right: meta + CTAs -->
                <div class="text-right">
                    <div v-if="v.salary" class="font-medium text-slate-900">
                        {{ v.salary }}
                    </div>
                    <div class="meta">Posted {{ v.posted_at }}</div>

                    <div class="mt-3 flex items-center justify-end gap-2">
                        <Link
                            :href="`/vacancies/${v.id}`"
                            class="btn-outline btn-sm"
                            >Details</Link
                        >
                        <Link
                            :href="`/applicant/apply/${v.id}`"
                            class="btn-primary btn-sm"
                            >Apply</Link
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="card">
            <div class="font-medium text-slate-900">No results</div>
            <p class="muted mt-1">
                Try a broader keyword (e.g., “Engineer” or “Remote”) or clear
                filters.
            </p>
            <div class="mt-3">
                <button class="btn-outline btn-sm" @click="resetFilters">
                    Clear all filters
                </button>
            </div>
        </div>
    </ApplicantLayout>
</template>
