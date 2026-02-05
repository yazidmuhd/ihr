<script setup>
import { Head, Link } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    rows: { type: Array, default: () => [] }, // [{id,title,department,location,shortlisted_cnt}]
});
</script>

<template>
    <Head title="Evaluation" />
    <StaffLayout>
        <div class="mx-auto max-w-7xl p-4">
            <h1 class="text-xl font-semibold text-slate-900">Evaluation</h1>
            <p class="mt-1 text-sm text-slate-600">
                Open vacancies with shortlisted candidates. Click a vacancy to
                score interviews and see final ranking.
            </p>

            <div class="mt-4 grid gap-3">
                <article
                    v-for="v in props.rows"
                    :key="v.id"
                    class="rounded-2xl border border-white/30 bg-white/70 p-4 shadow-sm backdrop-blur"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">
                                {{ v.title }}
                            </div>
                            <div class="text-sm text-slate-600">
                                {{ v.department || "—" }} •
                                {{ v.location || "—" }}
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="rounded-xl bg-emerald-600/10 px-3 py-1.5 text-sm font-semibold text-emerald-700"
                            >
                                Shortlisted: {{ v.shortlisted_cnt }}
                            </span>
                            <Link
                                :href="`/hr/vacancies/${v.id}/evaluation`"
                                class="btn-primary"
                                >Open</Link
                            >
                        </div>
                    </div>
                </article>

                <div
                    v-if="!props.rows.length"
                    class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-slate-600"
                >
                    No vacancies have shortlisted candidates yet.
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

<style scoped>
.btn-primary {
    @apply rounded-xl bg-emerald-600 px-4 py-2 font-medium text-white;
}
</style>
