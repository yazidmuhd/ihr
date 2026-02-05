<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// --- Demo data (replace with props from backend later) ---
const rows = ref([
    {
        id: 11,
        title: "Frontend Engineer",
        dept: "IT",
        type: "Full-time",
        location: "Kuala Lumpur",
        posted_at: "2025-10-02",
        status: "Open",
    },
    {
        id: 12,
        title: "HR Generalist",
        dept: "HR",
        type: "Contract",
        location: "Remote",
        posted_at: "2025-10-05",
        status: "Open",
    },
    {
        id: 13,
        title: "Backend Engineer",
        dept: "IT",
        type: "Full-time",
        location: "Remote",
        posted_at: "2025-10-08",
        status: "Closed",
    },
]);

// --- UI-only filters ---
const q = ref("");
const fType = ref("All");
const fDept = ref("All");
const fStatus = ref("All");

const types = computed(() => [
    "All",
    ...new Set(rows.value.map((r) => r.type)),
]);
const depts = computed(() => [
    "All",
    ...new Set(rows.value.map((r) => r.dept)),
]);
const states = computed(() => [
    "All",
    ...new Set(rows.value.map((r) => r.status)),
]);

const filtered = computed(() => {
    const k = q.value.trim().toLowerCase();
    return rows.value.filter((r) => {
        const kMatch =
            !k ||
            r.title.toLowerCase().includes(k) ||
            r.dept.toLowerCase().includes(k) ||
            r.location.toLowerCase().includes(k) ||
            r.type.toLowerCase().includes(k);
        const tMatch = fType.value === "All" || r.type === fType.value;
        const dMatch = fDept.value === "All" || r.dept === fDept.value;
        const sMatch = fStatus.value === "All" || r.status === fStatus.value;
        return kMatch && tMatch && dMatch && sMatch;
    });
});

function resetFilters() {
    q.value = "";
    fType.value = "All";
    fDept.value = "All";
    fStatus.value = "All";
}

// --- UI actions (demo only) ---
function closeVacancy(v) {
    v.status = "Closed";
    alert(`Closed vacancy: ${v.title} (UI only).`);
}
function reopenVacancy(v) {
    v.status = "Open";
    alert(`Reopened vacancy: ${v.title} (UI only).`);
}
</script>

<template>
    <StaffLayout>
        <!-- Header -->
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="page-title">Vacancies</h1>
                    <p class="page-sub">
                        Manage open roles, status, and postings.
                    </p>
                </div>
                <Link href="/hr/vacancies/create" class="btn-brand"
                    >+ New Vacancy</Link
                >
            </div>
        </template>

        <!-- Toolbar (search + filters) -->
        <section class="card mb-4">
            <form @submit.prevent class="grid items-end gap-4 md:grid-cols-12">
                <!-- Search -->
                <div class="md:col-span-5">
                    <label class="label mb-1">Search</label>
                    <div class="relative">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <circle cx="11" cy="11" r="7" stroke-width="2" />
                            <path d="M20 20l-3.5-3.5" stroke-width="2" />
                        </svg>
                        <input
                            v-model="q"
                            class="field h-11 pl-10"
                            placeholder="Title, department, location, type…"
                        />
                    </div>
                </div>

                <!-- Type -->
                <div class="md:col-span-2">
                    <label class="label mb-1">Type</label>
                    <select v-model="fType" class="field h-11">
                        <option v-for="t in types" :key="t" :value="t">
                            {{ t }}
                        </option>
                    </select>
                </div>

                <!-- Department -->
                <div class="md:col-span-2">
                    <label class="label mb-1">Department</label>
                    <select v-model="fDept" class="field h-11">
                        <option v-for="d in depts" :key="d" :value="d">
                            {{ d }}
                        </option>
                    </select>
                </div>

                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="label mb-1">Status</label>
                    <select v-model="fStatus" class="field h-11">
                        <option v-for="s in states" :key="s" :value="s">
                            {{ s }}
                        </option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 md:col-span-1">
                    <button type="button" class="btn-soft">Reset</button>
                    <button type="button" class="btn-brand">Filter</button>
                </div>
            </form>
        </section>

        <!-- Result summary + actions-right -->
        <div class="mb-2 flex items-center justify-between">
            <div class="muted">
                Showing <strong>{{ filtered.length }}</strong> role<span
                    v-if="filtered.length !== 1"
                    >s</span
                >.
            </div>
            <div class="flex items-center gap-2">
                <Link href="/hr/vacancies/archive" class="btn-ghost btn-sm"
                    >Archive</Link
                >
                <Link href="/hr/interviews" class="btn-primary btn-sm"
                    >Interviews</Link
                >
            </div>
        </div>

        <!-- Table -->
        <div class="card overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Dept</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Posted</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="!filtered.length">
                        <td colspan="7" class="py-8 text-center">
                            <div class="muted">
                                No vacancies found. Try adjusting filters.
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-for="v in filtered"
                        :key="v.id"
                        class="odd:bg-white even:bg-slate-50/60"
                    >
                        <td class="font-medium text-slate-900">
                            <Link
                                :href="`/hr/vacancies/${v.id}`"
                                class="btn-link"
                                >{{ v.title }}</Link
                            >
                        </td>
                        <td class="muted">{{ v.dept }}</td>
                        <td>
                            <span class="badge badge-muted">{{ v.type }}</span>
                        </td>
                        <td class="muted">{{ v.location }}</td>
                        <td class="muted">{{ v.posted_at }}</td>
                        <td>
                            <span
                                :class="[
                                    'badge',
                                    v.status === 'Open'
                                        ? 'badge-ok'
                                        : 'badge-warn',
                                ]"
                                >{{ v.status }}</span
                            >
                        </td>
                        <td class="text-right">
                            <div class="inline-flex gap-2">
                                <Link
                                    :href="`/hr/vacancies/${v.id}/edit`"
                                    class="btn-ghost btn-sm"
                                    >Edit</Link
                                >
                                <button
                                    v-if="v.status === 'Open'"
                                    class="btn-outline btn-sm"
                                    @click="closeVacancy(v)"
                                >
                                    Close
                                </button>
                                <button
                                    v-else
                                    class="btn-outline btn-sm"
                                    @click="reopenVacancy(v)"
                                >
                                    Reopen
                                </button>
                                <Link
                                    :href="`/vacancies/${v.id}`"
                                    class="btn-primary btn-sm"
                                    >View</Link
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </StaffLayout>
</template>
