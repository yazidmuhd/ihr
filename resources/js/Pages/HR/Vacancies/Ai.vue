<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    vacancy: Object, // {id,title,department,location}
    rows: { type: Array, default: () => [] }, // each row has: id, anon_name, created_at, status, match_score, breakdown
});

function shortlist(id) {
    router.patch(`/hr/applications/${id}/status`, { status: "shortlisted" });
}
function reject(id) {
    router.patch(`/hr/applications/${id}/status`, { status: "rejected" });
}
function rescoreAll() {
    router.post(`/hr/vacancies/${props.vacancy.id}/ai/rescore`);
}
function badge(status) {
    const s = (status || "").toLowerCase();
    if (["shortlisted"].includes(s)) return "b-shortlisted";
    if (["rejected", "declined"].includes(s)) return "b-bad";
    if (["submitted", "in_review", "applied", "received"].includes(s))
        return "b-open";
    return "b-muted";
}
</script>

<template>
    <Head :title="`AI Ranking — ${props.vacancy.title || ''}`" />
    <StaffLayout>
        <!-- Header -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    AI Ranking
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    {{ props.vacancy.title }} •
                    {{ props.vacancy.department || "—" }} •
                    {{ props.vacancy.location || "—" }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Link href="/hr/vacancies" class="btn-ghost"
                    >← Back to vacancies</Link
                >
                <button class="btn-primary" @click="rescoreAll">
                    Rescore all
                </button>
            </div>
        </div>

        <!-- List -->
        <section class="grid gap-4">
            <article
                v-for="r in props.rows"
                :key="r.id"
                class="rounded-2xl bg-white/80 p-5 shadow ring-1 ring-white/40 backdrop-blur"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-[280px]">
                        <div class="text-lg font-semibold text-slate-900">
                            {{ r.anon_name }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                            Applied {{ r.created_at || "—" }}
                        </div>

                        <div class="mt-3 grid gap-1 text-sm">
                            <div>
                                <span class="font-medium">Total:</span>
                                <span class="ml-2 text-slate-900">
                                    {{
                                        r.match_score != null
                                            ? `${r.match_score} / 100`
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Skills:</span>
                                <span class="ml-2">
                                    {{
                                        r.breakdown?.skills != null
                                            ? `${r.breakdown.skills} pts`
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Experience:</span>
                                <span class="ml-2">
                                    {{
                                        r.breakdown?.experience != null
                                            ? `${r.breakdown.experience} pts`
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium">Education:</span>
                                <span class="ml-2">
                                    {{
                                        r.breakdown?.education != null
                                            ? `${r.breakdown.education} pts`
                                            : "—"
                                    }}
                                </span>
                            </div>
                            <div
                                v-if="r.breakdown?.overlap_skills?.length"
                                class="text-xs text-slate-600"
                            >
                                Overlap skills:
                                <span class="text-slate-900">{{
                                    r.breakdown.overlap_skills.join(", ")
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="tag" :class="badge(r.status)">{{
                            r.status || "—"
                        }}</span>
                        <button class="btn-ghost" @click="shortlist(r.id)">
                            Shortlist
                        </button>
                        <button class="btn-outline" @click="reject(r.id)">
                            Reject
                        </button>
                    </div>
                </div>
            </article>

            <div
                v-if="!props.rows.length"
                class="rounded-2xl bg-white/70 p-10 text-center text-slate-600 ring-1 ring-white/40"
            >
                No applications yet.
            </div>
        </section>
    </StaffLayout>
</template>

<style scoped>
.btn-primary {
    @apply rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white;
}
.btn-ghost {
    @apply rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800;
}
.btn-outline {
    @apply rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700;
}

.tag {
    @apply inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold;
}
.b-open {
    @apply bg-emerald-100 text-emerald-800;
}
.b-shortlisted {
    @apply bg-blue-100 text-blue-800;
}
.b-bad {
    @apply bg-rose-100 text-rose-800;
}
.b-muted {
    @apply bg-slate-200 text-slate-700;
}
</style>
