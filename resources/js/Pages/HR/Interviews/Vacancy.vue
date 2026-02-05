<script setup>
import { Head, router } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    vacancy: Object,
    shortlisted: Array, // not yet interviewed for this vacancy
    interviews: Array, // existing interviews + ratings
    weights: Object,
});

function invite(app) {
    router.post(
        "/hr/interviews/upsert",
        {
            vacancy_id: props.vacancy.id,
            application_id: app.application_id,
        },
        { preserveScroll: true },
    );
}

function savePanels(interview) {
    // collect current ratings from inputs
    const rows = Array.from(
        document.querySelectorAll(`[data-if="${interview.id}"]`),
    )
        .map((el) => ({
            name: el.querySelector('[name="name"]')?.value?.trim(),
            department: el.querySelector('[name="dept"]')?.value?.trim(),
            stars: +(el.querySelector('[name="stars"]')?.value || 0),
            comment: el.querySelector('[name="comment"]')?.value?.trim(),
        }))
        .filter((r) => r.name);

    router.post(
        "/hr/interviews/upsert",
        {
            vacancy_id: props.vacancy.id,
            application_id: interview.application_id,
            panels: rows,
        },
        { preserveScroll: true },
    );
}

function finalize(interview) {
    router.post(
        `/hr/interviews/${interview.id}/finalize`,
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Evaluation – ${vacancy.title}`" />
    <StaffLayout>
        <header class="mb-4">
            <h1 class="text-xl font-semibold text-slate-900">
                Evaluation — {{ vacancy.title }}
            </h1>
            <p class="text-sm text-slate-600">
                Weights: résumé {{ Math.round(weights.resume * 100) }}%,
                interview {{ Math.round(weights.interview * 100) }}%
            </p>
        </header>

        <!-- Shortlisted to invite -->
        <section class="mb-8">
            <h2 class="mb-2 text-sm font-semibold text-slate-700">
                Shortlisted (not invited)
            </h2>
            <div class="grid gap-3">
                <article
                    v-for="r in shortlisted"
                    :key="r.application_id"
                    class="rounded-2xl bg-white/80 p-4 shadow ring-1 ring-white/40"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ r.anon_name }}</div>
                            <div class="text-xs text-slate-600">
                                Resume score: <b>{{ r.match_score ?? 0 }}</b>
                            </div>
                        </div>
                        <button
                            class="btn-primary"
                            :disabled="r.invited"
                            @click="invite(r)"
                        >
                            {{ r.invited ? "Invited" : "Invite for interview" }}
                        </button>
                    </div>
                </article>
                <p v-if="!shortlisted?.length" class="text-sm text-slate-500">
                    All shortlisted are invited.
                </p>
            </div>
        </section>

        <!-- Interviews -->
        <section>
            <h2 class="mb-2 text-sm font-semibold text-slate-700">
                Interviews
            </h2>

            <article
                v-for="i in interviews"
                :key="i.id"
                class="rounded-2xl bg-white/80 p-4 shadow ring-1 ring-white/40"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="min-w-[220px]">
                        <div class="font-medium text-slate-900">
                            Interview #{{ i.id }}
                        </div>
                        <div class="text-xs text-slate-600">
                            Panels: {{ i.panel_count ?? 0 }} • Avg ★:
                            {{ i.avg_stars }}
                        </div>
                        <div class="text-xs text-slate-600">
                            Status: <b>{{ i.status }}</b>
                        </div>
                    </div>
                    <div class="text-xs text-slate-600">
                        Interview score: <b>{{ i.interview_score ?? "-" }}</b> •
                        Final: <b>{{ i.final_score ?? "-" }}</b>
                    </div>
                </div>

                <!-- Panel ratings table (editable) -->
                <div class="mt-3 grid gap-2">
                    <div
                        v-for="(r, idx) in i.ratings || [{}, {}, {}]"
                        :key="r.id || idx"
                        class="grid grid-cols-12 items-center gap-2 rounded-lg border border-slate-200 p-2"
                        :data-if="i.id"
                    >
                        <input
                            class="input col-span-3"
                            name="name"
                            :value="r.panel_name"
                            placeholder="Panel name"
                        />
                        <input
                            class="input col-span-3"
                            name="dept"
                            :value="r.department"
                            placeholder="Department"
                        />
                        <select
                            class="input col-span-2"
                            name="stars"
                            :value="r.stars ?? 0"
                        >
                            <option
                                v-for="s in [0, 1, 2, 3, 4, 5]"
                                :key="s"
                                :value="s"
                            >
                                {{ s }} ★
                            </option>
                        </select>
                        <input
                            class="input col-span-4"
                            name="comment"
                            :value="r.comment"
                            placeholder="Comment (optional)"
                        />
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <button class="btn-ghost" @click="savePanels(i)">
                        Save ratings
                    </button>
                    <button class="btn-primary" @click="finalize(i)">
                        Finalize
                    </button>
                </div>
            </article>

            <p v-if="!interviews?.length" class="text-sm text-slate-500">
                No interviews yet. Invite first.
            </p>
        </section>
    </StaffLayout>
</template>

<style scoped>
.input {
    @apply w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm;
}
.btn-primary {
    @apply inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition;
    box-shadow: 0 8px 22px -10px rgba(16, 185, 129, 0.55);
}
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px -12px rgba(16, 185, 129, 0.55);
}
.btn-ghost {
    @apply inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800;
}
</style>
