<script setup>
import { Head, router } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, reactive, computed } from "vue";

const props = defineProps({
    vacancy: { type: Object, required: true },
    rows: { type: Array, default: () => [] }, // [{application_id,name,resume_score,interview_id,interview_score,final_score,panel_count,panels,status,created_at}]
    weights: { type: Object, default: () => ({ resume: 50, interview: 50 }) },
});

const open = ref({});

// editable forms per application
const forms = reactive({});
props.rows.forEach((r) => {
    forms[r.application_id] = {
        application_id: r.application_id,
        panel_count: r.panel_count || (r.panels?.length ?? 0),
        panels: r.panels?.length
            ? r.panels
            : [{ name: "", department: "", stars: 0, comment: "" }],
    };
});

function addPanel(appId) {
    forms[appId].panels.push({
        name: "",
        department: "",
        stars: 0,
        comment: "",
    });
    forms[appId].panel_count = forms[appId].panels.length;
}
function removePanel(appId, idx) {
    forms[appId].panels.splice(idx, 1);
    forms[appId].panel_count = forms[appId].panels.length;
}
function save(appId) {
    router.post("/hr/interviews/upsert", forms[appId], {
        preserveScroll: true,
    });
}

// quick helpers
function topClass(idx) {
    return idx === 0
        ? "rank-1"
        : idx === 1
          ? "rank-2"
          : idx === 2
            ? "rank-3"
            : "";
}
function ribbon(idx) {
    return idx === 0 ? "#1" : idx === 1 ? "#2" : idx === 2 ? "#3" : "";
}

// local recompute interview score (0..100) from form (for preview)
function previewInterviewScore(appId) {
    const p = forms[appId]?.panels || [];
    if (!p.length) return 0;
    const sum = p.reduce(
        (s, it) => s + Math.max(0, Math.min(5, parseInt(it.stars || 0))),
        0,
    );
    const avg = sum / p.length; // 0..5
    return Math.round((avg / 5) * 100);
}
function finalPreview(row) {
    const interview =
        previewInterviewScore(row.application_id) || (row.interview_score ?? 0);
    return Math.round(row.resume_score * 0.5 + interview * 0.5);
}

const ranked = computed(() => {
    const clone = JSON.parse(JSON.stringify(props.rows));
    // recompute preview finals for in-memory sort
    clone.forEach((r) => (r._final_preview = finalPreview(r)));
    return clone.sort((a, b) => b._final_preview - a._final_preview);
});
</script>

<template>
    <Head :title="`Evaluation – ${props.vacancy.title}`" />
    <StaffLayout>
        <div class="mx-auto max-w-7xl p-4">
            <header
                class="mb-4 rounded-2xl border border-white/25 bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-cyan-500/10 p-4 backdrop-blur"
            >
                <h1 class="text-xl font-semibold text-slate-900">
                    Evaluation —
                    <span class="text-slate-700">{{
                        props.vacancy.title
                    }}</span>
                </h1>
                <p class="text-sm text-slate-600">
                    Fair blend: <b>{{ weights.resume }}%</b> resume +
                    <b>{{ weights.interview }}%</b> interview. Rate each
                    candidate (panels 0–5 ★); we compute interview & final
                    scores and rank automatically.
                </p>
            </header>

            <div class="grid gap-4">
                <article
                    v-for="(r, idx) in ranked"
                    :key="r.application_id"
                    :class="['card relative', topClass(idx)]"
                >
                    <!-- top-3 ribbon -->
                    <div
                        v-if="idx < 3"
                        class="absolute -right-10 -top-8 rotate-45"
                    >
                        <div
                            class="rounded-md bg-gradient-to-r px-10 py-2 text-sm font-black tracking-wide shadow"
                            :class="
                                idx === 0
                                    ? 'from-amber-400 to-amber-500 text-amber-50'
                                    : idx === 1
                                      ? 'from-slate-300 to-slate-400 text-slate-800'
                                      : 'from-orange-300 to-orange-400 text-orange-900'
                            "
                        >
                            {{ ribbon(idx) }}
                        </div>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-semibold text-slate-900">
                                {{ r.name }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Applied: {{ r.created_at || "—" }}
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <div
                                class="rounded-xl bg-emerald-600/10 px-3 py-1.5 text-sm font-semibold text-emerald-700"
                            >
                                Final: {{ finalPreview(r) }}
                            </div>
                            <div class="text-xs text-slate-600">
                                Resume {{ r.resume_score }} · Interview
                                {{
                                    previewInterviewScore(r.application_id) ||
                                    (r.interview_score ?? 0)
                                }}
                            </div>
                        </div>
                    </div>

                    <!-- Panels editor -->
                    <div
                        class="mt-3 rounded-xl border border-white/40 bg-white/60 p-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-slate-900">
                                Panels
                            </div>
                            <button
                                class="btn-ghost"
                                @click="addPanel(r.application_id)"
                            >
                                + Add panel
                            </button>
                        </div>

                        <div class="mt-3 grid gap-3">
                            <div
                                v-for="(p, i) in forms[r.application_id].panels"
                                :key="`p-${i}`"
                                class="grid gap-2 rounded-xl border border-slate-200/70 bg-white p-3 md:grid-cols-12"
                            >
                                <input
                                    v-model="p.name"
                                    class="input md:col-span-3"
                                    placeholder="Panel name *"
                                />
                                <input
                                    v-model="p.department"
                                    class="input md:col-span-3"
                                    placeholder="Department"
                                />
                                <div
                                    class="flex items-center gap-2 md:col-span-2"
                                >
                                    <label class="label shrink-0">Stars</label>
                                    <select v-model="p.stars" class="select">
                                        <option
                                            v-for="s in [0, 1, 2, 3, 4, 5]"
                                            :key="s"
                                            :value="s"
                                        >
                                            {{ s }} ★
                                        </option>
                                    </select>
                                </div>
                                <input
                                    v-model="p.comment"
                                    class="input md:col-span-3"
                                    placeholder="Comment (optional)"
                                />
                                <div
                                    class="flex items-center justify-end md:col-span-1"
                                >
                                    <button
                                        class="btn-danger"
                                        @click="
                                            removePanel(r.application_id, i)
                                        "
                                        v-if="
                                            forms[r.application_id].panels
                                                .length > 1
                                        "
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-end gap-2">
                            <button
                                class="btn-primary"
                                @click="save(r.application_id)"
                            >
                                Save interview
                            </button>
                            <button
                                class="btn-ghost"
                                @click="
                                    open[r.application_id] =
                                        !open[r.application_id]
                                "
                            >
                                {{
                                    open[r.application_id]
                                        ? "Hide raw"
                                        : "View raw"
                                }}
                            </button>
                        </div>

                        <div
                            v-if="open[r.application_id]"
                            class="mt-2 text-xs text-slate-600"
                        >
                            <pre
                                class="overflow-x-auto rounded-lg bg-slate-50 p-3"
                                >{{ forms[r.application_id] }}</pre
                            >
                        </div>
                    </div>
                </article>

                <div
                    v-if="!ranked.length"
                    class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-slate-600"
                >
                    No shortlisted/in-review applications for this vacancy.
                </div>
            </div>
        </div>
    </StaffLayout>
</template>

<style scoped>
.card {
    @apply rounded-2xl border border-white/25 bg-gradient-to-br from-white/70 via-white/60 to-white/50 p-5 shadow backdrop-blur transition;
}
.card:hover {
    @apply ring-1 ring-emerald-300/40;
}
.rank-1 {
    @apply bg-gradient-to-br from-amber-50 via-white to-white ring-2 ring-amber-300/60;
}
.rank-2 {
    @apply bg-gradient-to-br from-slate-50 via-white to-white ring-2 ring-slate-300/70;
}
.rank-3 {
    @apply bg-gradient-to-br from-orange-50 via-white to-white ring-2 ring-orange-300/60;
}

.input {
    @apply w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200;
}
.select {
    @apply h-10 w-full rounded-xl border border-slate-300 bg-white px-3 pr-8 text-slate-900 shadow-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200;
}
.label {
    @apply text-xs font-semibold text-slate-700;
}

.btn-primary {
    @apply rounded-xl bg-emerald-600 px-4 py-2 font-medium text-white disabled:opacity-60;
}
.btn-ghost {
    @apply rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-700 disabled:opacity-60;
}
.btn-danger {
    @apply rounded-xl bg-rose-600 px-3 py-2 text-white;
}
</style>
