<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import HrLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    rows: Array,
    filters: Object, // {min, vacancy_id, status}
    vacancies: Array, // [{id,title}]
});

const min = ref(props.filters.min ?? 0);
const vacancy_id = ref(props.filters.vacancy_id ?? 0);
const status = ref(props.filters.status ?? "All");

function go() {
    router.get(
        "/hr/filter",
        { min: min.value, vacancy_id: vacancy_id.value, status: status.value },
        { preserveState: true, replace: true },
    );
}
function shortlist(id) {
    router.post(
        `/hr/applications/${id}/decision`,
        { decision: "shortlisted" },
        { preserveScroll: true },
    );
}
function rejectIt(id) {
    router.post(
        `/hr/applications/${id}/decision`,
        { decision: "rejected" },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Filter candidates" />
    <HrLayout>
        <header class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    Filter candidates
                </h1>
                <p class="text-sm text-slate-600">
                    Search across all applications (anonymous).
                </p>
            </div>
        </header>

        <section
            class="rounded-2xl bg-white/70 p-4 ring-1 ring-white/40 backdrop-blur"
        >
            <div class="grid gap-3 md:grid-cols-6">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-slate-500"
                        >Vacancy</label
                    >
                    <select v-model="vacancy_id" class="field w-full">
                        <option :value="0">All</option>
                        <option
                            v-for="v in props.vacancies"
                            :value="v.id"
                            :key="v.id"
                        >
                            {{ v.title }}
                        </option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs text-slate-500"
                        >Status</label
                    >
                    <select v-model="status" class="field w-full">
                        <option>All</option>
                        <option>submitted</option>
                        <option>shortlisted</option>
                        <option>rejected</option>
                        <option>hired</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="mb-1 block text-xs text-slate-500"
                        >Min score</label
                    >
                    <input
                        v-model.number="min"
                        type="number"
                        min="0"
                        max="100"
                        step="5"
                        class="field w-full"
                    />
                </div>
                <div class="flex items-end md:col-span-1">
                    <button class="btn-primary w-full" @click="go">
                        Apply
                    </button>
                </div>
            </div>
        </section>

        <section class="mt-4 grid gap-3">
            <article v-for="r in props.rows" :key="r.id" class="card">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="font-semibold text-slate-900">
                            {{ r.anon_name }}
                        </div>
                        <div class="text-xs text-slate-500">
                            Vacancy: {{ r.vacancy_title }}
                        </div>
                    </div>
                    <div class="chip">{{ r.match_score ?? "—" }}</div>
                </div>
                <div class="mt-3 flex gap-2">
                    <button class="btn-ghost" @click="shortlist(r.id)">
                        Shortlist
                    </button>
                    <button class="btn-outline" @click="rejectIt(r.id)">
                        Reject
                    </button>
                    <Link
                        :href="`/hr/vacancies/${r.vacancy_id}/ai`"
                        class="btn-ghost"
                        >Open AI page</Link
                    >
                </div>
            </article>

            <div
                v-if="!props.rows?.length"
                class="rounded-2xl bg-white/70 p-10 text-center text-slate-600 ring-1 ring-white/40"
            >
                No results.
            </div>
        </section>
    </HrLayout>
</template>

<style scoped>
.field {
    @apply rounded-xl border border-slate-300 bg-white px-3 py-2 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200;
}
.card {
    @apply rounded-2xl bg-white/80 p-5 shadow ring-1 ring-white/40 backdrop-blur;
}
.btn-primary {
    @apply rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white;
}
.btn-ghost {
    @apply rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800;
}
.btn-outline {
    @apply rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700;
}
.chip {
    @apply rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-900;
}
</style>
