<!-- resources/js/Pages/HR/FilterResumes.vue -->
<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, computed } from "vue";

// -------------------- Mock data (UI-only demo) --------------------
const rows = ref([
    {
        id: 1,
        candidate: "Aisyah",
        job: "Frontend Engineer",
        dept: "IT",
        score: 0.86,
        matchedSkills: ["Vue", "Tailwind", "REST"],
        applied: "2025-10-10",
        snippet:
            "Experienced Vue developer with 3 years building SPA dashboards. Strong in Tailwind and REST API integration.",
    },
    {
        id: 2,
        candidate: "Daniel",
        job: "Backend Engineer",
        dept: "IT",
        score: 0.74,
        matchedSkills: ["Laravel", "SQL", "Queues"],
        applied: "2025-10-09",
        snippet:
            "Laravel-focused backend engineer. Designed queue-based pipelines and SQL reporting. Some Docker and Redis.",
    },
    {
        id: 3,
        candidate: "Mira",
        job: "HR Generalist",
        dept: "HR",
        score: 0.62,
        matchedSkills: ["Recruiting", "Onboarding", "Payroll"],
        applied: "2025-10-08",
        snippet:
            "HR Generalist with solid recruiting, onboarding coordination and basic payroll systems experience.",
    },
    {
        id: 4,
        candidate: "Hakim",
        job: "Accountant",
        dept: "Finance",
        score: 0.81,
        matchedSkills: ["Accounting", "AP/AR", "Excel"],
        applied: "2025-10-07",
        snippet:
            "Accounting exec with AP/AR ownership. Strong Excel and reconciliation; helped monthly close procedures.",
    },
]);

// -------------------- Filters (UI-only) --------------------
const departments = [
    "All",
    "IT",
    "HR",
    "Finance",
    "Operations",
    "Marketing",
    "Sales",
];

const filters = ref({
    q: "",
    dept: "All",
    minScore: 0.6, // default threshold
});

const normalized = (s) => (s || "").toString().toLowerCase();
function escapeRegex(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}
function highlight(text) {
    const k = filters.value.q.trim();
    if (!k) return text;
    return text.replace(
        new RegExp(`(${escapeRegex(k)})`, "ig"),
        '<mark class="mark">$1</mark>',
    );
}

const filtered = computed(() => {
    const k = normalized(filters.value.q.trim());
    return rows.value.filter((r) => {
        const matchesDept =
            filters.value.dept === "All" || r.dept === filters.value.dept;
        const matchesScore = (r.score ?? 0) >= (filters.value.minScore ?? 0);
        const inName = normalized(r.candidate).includes(k);
        const inJob = normalized(r.job).includes(k);
        const inDept = normalized(r.dept).includes(k);
        const inSkills = r.matchedSkills.some((s) => normalized(s).includes(k));
        const inSnippet = normalized(r.snippet).includes(k);
        const matchesQ =
            !k || inName || inJob || inDept || inSkills || inSnippet;
        return matchesDept && matchesScore && matchesQ;
    });
});

function scoreBadge(score) {
    if (score >= 0.85) return "badge-ok";
    if (score >= 0.7) return "badge-brand";
    if (score >= 0.5) return "badge-muted";
    return "badge-warn";
}
function pct(score) {
    return Math.round((score ?? 0) * 100);
}

function clearFilters() {
    filters.value.q = "";
    filters.value.dept = "All";
    filters.value.minScore = 0;
}

function reevaluate(row) {
    alert(`Re-evaluating resume for ${row.candidate} (UI only).`);
}
</script>

<template>
    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="page-title">Filter Resumes</h1>
                    <p class="page-sub">
                        Use score, department and keywords to find the best
                        fits.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/hr/shortlist" class="btn-ghost">Shortlist</a>
                    <a href="/hr/interviews" class="btn-primary">Interviews</a>
                </div>
            </div>
        </template>

        <!-- Brand panel with a crisp filter form -->
        <section class="panel-brand mb-6">
            <div>
                <h2 class="panel-title">Smart filtering</h2>
                <p class="panel-sub">
                    Scores blend AI similarity and keyword coverage.
                </p>
            </div>

            <div class="panel-glass">
                <form
                    class="grid items-end gap-4 md:grid-cols-12"
                    @submit.prevent
                >
                    <!-- Search -->
                    <div class="md:col-span-6">
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
                                v-model="filters.q"
                                class="field h-11 pl-10"
                                placeholder="Keyword: candidate, job, skills…"
                                aria-label="Search"
                            />
                        </div>
                        <p class="help mt-1">
                            Examples: “Vue”, “Recruiting”, “Accountant”.
                        </p>
                    </div>

                    <!-- Department -->
                    <div class="md:col-span-3">
                        <label class="label mb-1">Department</label>
                        <select
                            v-model="filters.dept"
                            class="field h-11"
                            aria-label="Department"
                        >
                            <option
                                v-for="d in departments"
                                :key="d"
                                :value="d"
                            >
                                {{ d }}
                            </option>
                        </select>
                    </div>

                    <!-- Min Score -->
                    <div class="md:col-span-3">
                        <label class="label mb-1"
                            >Min score:
                            <strong
                                >{{
                                    Math.round(filters.minScore * 100)
                                }}%</strong
                            ></label
                        >
                        <input
                            v-model.number="filters.minScore"
                            type="range"
                            min="0"
                            max="1"
                            step="0.01"
                            class="w-full"
                            aria-label="Minimum score"
                        />
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 md:col-span-12">
                        <button
                            type="button"
                            class="btn-soft"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                        <button type="button" class="btn-brand">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Results header -->
        <div class="mb-3 flex items-center justify-between">
            <div class="muted">
                Showing <strong>{{ filtered.length }}</strong> candidate<span
                    v-if="filtered.length !== 1"
                    >s</span
                >
            </div>
            <div class="flex items-center gap-2">
                <a href="/hr/vacancies" class="btn-ghost btn-sm"
                    >Manage Vacancies</a
                >
                <a href="/hr/shortlist" class="btn-primary btn-sm"
                    >Go to Shortlist</a
                >
            </div>
        </div>

        <!-- Table -->
        <div
            class="overflow-x-auto rounded-2xl bg-white p-1 shadow ring-1 ring-slate-200"
        >
            <table class="table">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job / Dept</th>
                        <th>Score</th>
                        <th>Matched Skills</th>
                        <th>Applied</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-slate-50/40">
                    <tr v-if="!filtered.length">
                        <td colspan="6" class="py-8 text-center">
                            <div class="muted">
                                No results — try lowering the min score or
                                clearing filters.
                            </div>
                        </td>
                    </tr>

                    <tr v-for="row in filtered" :key="row.id">
                        <!-- Candidate -->
                        <td class="align-top">
                            <div
                                class="font-medium text-slate-900"
                                v-html="highlight(row.candidate)"
                            ></div>
                            <div class="meta">Resume ID: #{{ row.id }}</div>
                        </td>

                        <!-- Job / Dept -->
                        <td class="align-top">
                            <div
                                class="font-medium"
                                v-html="highlight(row.job)"
                            ></div>
                            <div
                                class="muted"
                                v-html="highlight(row.dept)"
                            ></div>
                        </td>

                        <!-- Score with badge + tiny bar -->
                        <td class="align-top">
                            <div class="flex items-center gap-2">
                                <span :class="['badge', scoreBadge(row.score)]"
                                    >{{ pct(row.score) }}%</span
                                >
                            </div>
                            <div class="score-bar mt-2">
                                <div
                                    class="score-fill"
                                    :style="{ width: pct(row.score) + '%' }"
                                ></div>
                            </div>
                        </td>

                        <!-- Skills (chips) -->
                        <td class="align-top">
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="s in row.matchedSkills"
                                    :key="s"
                                    class="badge-brand"
                                    >{{ s }}</span
                                >
                            </div>
                        </td>

                        <!-- Applied date -->
                        <td class="muted align-top">
                            {{ row.applied }}
                        </td>

                        <!-- Actions -->
                        <td class="text-right align-top">
                            <div class="inline-flex gap-2">
                                <button
                                    class="btn-ghost btn-sm"
                                    @click="reevaluate(row)"
                                >
                                    Re-evaluate
                                </button>
                                <a
                                    href="/hr/shortlist"
                                    class="btn-primary btn-sm"
                                    >Shortlist</a
                                >
                            </div>
                            <!-- Expandable preview -->
                            <details class="mt-3">
                                <summary
                                    class="cursor-pointer text-sm text-slate-600 hover:text-slate-800"
                                >
                                    Preview & highlights
                                </summary>
                                <div
                                    class="mt-2 rounded-xl bg-white p-3 ring-1 ring-slate-200"
                                >
                                    <div
                                        class="text-sm leading-relaxed"
                                        v-html="highlight(row.snippet)"
                                    ></div>
                                </div>
                            </details>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </StaffLayout>
</template>

<style scoped>
.mark {
    background: rgba(0, 176, 156, 0.18);
    color: #0f766e; /* teal-700-ish */
    padding: 0 0.2rem;
    border-radius: 0.25rem;
}
.score-bar {
    height: 8px;
    border-radius: 9999px;
    background: #e2e8f0; /* slate-200 */
    overflow: hidden;
}
.score-fill {
    height: 8px;
    border-radius: 9999px;
    background: var(--brand, #00b09c);
}
</style>
