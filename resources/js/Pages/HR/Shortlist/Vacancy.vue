<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, watch, computed } from "vue";

/**
 * Props expected from controller:
 * vacancy: { id, title, ... }
 * rows:    [{ id: application_id, applicant_id, match_score, created_at, vacancy_title, vacancy_id, anon_name, invited? }]
 */
const props = defineProps({
    vacancy: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
});

// Flash message from Laravel: return back()->with('status', '...').
const page = usePage();
const flashMsg = computed(() => page.props?.flash?.status || "");

// Work on a local, mutable copy so we can update UI immediately
const localRows = ref(
    props.rows.map((r) => ({ ...r, _busy: false, invited: !!r.invited })),
);
watch(
    () => props.rows,
    (v) =>
        (localRows.value = v.map((r) => ({
            ...r,
            _busy: false,
            invited: !!r.invited,
        }))),
);

const evalLink = (row) => `/hr/vacancies/${row.vacancy_id}/evaluation`;

function invite(row) {
    if (row._busy || row.invited) return;
    row._busy = true;

    // helpful dev breadcrumb (remove if you want)
    // console.log("Inviting application_id:", row.id, "vacancy_id:", row.vacancy_id);

    router.post(
        "/hr/interviews/upsert",
        { vacancy_id: row.vacancy_id, application_id: row.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Immediate UI feedback
                row.invited = true;
                // Optionally reload only this prop from server to stay 100% in sync:
                // router.reload({ only: ['rows'] });
            },
            onError: (err) => {
                alert("Failed to invite. Please check constraints/logs.");
                // console.error(err);
            },
            onFinish: () => {
                row._busy = false;
            },
        },
    );
}

function decide(appId, decision) {
    const label =
        decision === "hired"
            ? "Hire this candidate?"
            : "Reject this candidate?";
    if (!confirm(label)) return;

    router.post(
        `/hr/applications/${appId}/decision`,
        { decision },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="`Shortlist — ${props.vacancy.title}`" />
    <StaffLayout>
        <!-- Flash banner -->
        <div
            v-if="flashMsg"
            class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-800"
        >
            {{ flashMsg }}
        </div>

        <header class="mb-5 space-y-1">
            <h1 class="text-xl font-semibold text-slate-900">
                Shortlisted —
                <span class="text-slate-700">{{ props.vacancy.title }}</span>
            </h1>
            <p class="text-sm text-slate-600">
                Invite candidates for interview, open evaluation board, or
                finalize a decision.
            </p>
            <div class="mt-2">
                <Link href="/hr/shortlist" class="btn-ghost"
                    >← Back to vacancies</Link
                >
            </div>
        </header>

        <!-- List -->
        <section class="grid gap-3">
            <article
                v-for="(r, idx) in localRows"
                :key="r.id"
                class="card glass"
            >
                <!-- rank ribbon -->
                <div
                    class="rank-chip"
                    :class="{
                        gold: idx === 0,
                        silver: idx === 1,
                        bronze: idx === 2,
                    }"
                >
                    #{{ idx + 1 }}
                </div>

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-slate-900">
                                {{ r.anon_name }}
                            </h3>
                            <span class="score-badge"
                                >Score: {{ r.match_score ?? 0 }}</span
                            >
                        </div>

                        <div class="mt-1 text-sm text-slate-600">
                            {{ r.vacancy_title }}
                        </div>
                        <div class="text-xs text-slate-500">
                            Shortlisted: {{ r.created_at || "—" }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="evalLink(r)" class="btn-ghost">
                            Evaluation board
                        </Link>

                        <button
                            class="btn-primary"
                            :class="{
                                'cursor-not-allowed opacity-60':
                                    r._busy || r.invited,
                            }"
                            :disabled="r._busy || r.invited"
                            @click="invite(r)"
                            title="Send interview invite"
                        >
                            <span v-if="r._busy">Inviting…</span>
                            <span v-else-if="r.invited">Invited ✓</span>
                            <span v-else>Invite for interview</span>
                        </button>

                        <button
                            class="btn-ghost danger"
                            :disabled="r._busy"
                            @click="decide(r.id, 'rejected')"
                        >
                            Reject
                        </button>
                        <button
                            class="btn-ghost success"
                            :disabled="r._busy"
                            @click="decide(r.id, 'hired')"
                        >
                            Hire
                        </button>
                    </div>
                </div>
            </article>

            <div
                v-if="!localRows.length"
                class="rounded-2xl bg-white/80 p-10 text-center text-slate-600 ring-1 ring-white/40"
            >
                No shortlisted candidates yet.
            </div>
        </section>
    </StaffLayout>
</template>

<style scoped>
.card.glass {
    @apply relative rounded-2xl bg-white/80 p-4 shadow ring-1 ring-white/40 backdrop-blur;
}

/* Rank ribbon (top-left) */
.rank-chip {
    position: absolute;
    top: -10px;
    left: -10px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-weight: 700;
    font-size: 0.8rem;
    color: #0f172a;
    background: #e2e8f0;
    box-shadow: 0 10px 24px -14px rgba(2, 6, 23, 0.25);
    pointer-events: none; /* prevents accidental click blocking */
}
.rank-chip.gold {
    background: linear-gradient(135deg, #fde68a, #f59e0b);
}
.rank-chip.silver {
    background: linear-gradient(135deg, #e5e7eb, #9ca3af);
}
.rank-chip.bronze {
    background: linear-gradient(135deg, #fcd9b6, #f59e0b);
}

/* Score pill */
.score-badge {
    @apply rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700;
    box-shadow: 0 6px 16px -12px rgba(16, 185, 129, 0.55);
}

/* Buttons */
.btn-primary {
    @apply inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition;
    box-shadow: 0 8px 22px -10px rgba(16, 185, 129, 0.55);
}
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px -12px rgba(16, 185, 129, 0.55);
}
.btn-ghost {
    @apply inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 transition;
}
.btn-ghost:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 28px -16px rgba(2, 6, 23, 0.22);
}
.btn-ghost.danger {
    @apply border-red-300 text-red-700;
    background: #fff;
}
.btn-ghost.success {
    @apply border-emerald-300 text-emerald-700;
    background: #fff;
}
</style>
