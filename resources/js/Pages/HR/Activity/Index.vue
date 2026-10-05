<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";

const props = defineProps({
    entries: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    areas: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    application: { type: Object, default: null },
    timeline: { type: Array, default: () => [] },
});

const form = reactive({
    area: props.filters.area || "",
    user: props.filters.user || "",
    from: props.filters.from || "",
    to: props.filters.to || "",
    application: props.filters.application || "",
});

function apply() {
    const query = Object.fromEntries(
        Object.entries(form).filter(([, v]) => v !== "" && v !== null),
    );
    router.get("/hr/activity", query, { preserveState: true, preserveScroll: true });
}

function reset() {
    Object.keys(form).forEach((k) => (form[k] = ""));
    router.get("/hr/activity");
}

const fmt = (s) =>
    s
        ? new Date(s.replace(" ", "T")).toLocaleString(undefined, {
              dateStyle: "medium",
              timeStyle: "short",
          })
        : "";

const statusLabel = (s) => (s ? s.replace(/_/g, " ") : "—");

const actionText = { insert: "created", update: "updated", delete: "deleted" };
const actionClass = {
    insert: "bg-emerald-50 text-emerald-700 ring-emerald-200",
    update: "bg-sky-50 text-sky-700 ring-sky-200",
    delete: "bg-rose-50 text-rose-700 ring-rose-200",
};
</script>

<template>
    <Head title="Activity" />
    <StaffLayout>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Activity</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Every change to vacancies, applications, interviews and employees,
                    with who made it. Recorded automatically by the database.
                </p>
            </div>
        </div>

        <!-- Candidate timeline (when filtered to one application) -->
        <section v-if="application" class="box mt-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="title !mb-0">
                    Application #{{ application.id }} · {{ application.applicant }}
                    <span class="font-normal text-slate-500">for {{ application.vacancy }}</span>
                </h2>
                <span class="chip capitalize">Now: {{ statusLabel(application.status) }}</span>
            </div>

            <ol class="timeline mt-4">
                <li v-for="(t, i) in timeline" :key="i" class="timeline-item">
                    <span class="timeline-dot" />
                    <div class="text-sm">
                        <span class="font-semibold capitalize text-slate-900">
                            {{ t.from ? `${statusLabel(t.from)} → ${statusLabel(t.to)}` : `Submitted` }}
                        </span>
                        <span class="text-slate-500"> · {{ t.by }} · {{ fmt(t.at) }}</span>
                    </div>
                </li>
                <li v-if="!timeline.length" class="muted">No status changes recorded yet.</li>
            </ol>
        </section>

        <!-- Filters -->
        <form class="box mt-4 grid gap-3 md:grid-cols-6" @submit.prevent="apply">
            <label class="field">
                <span>Area</span>
                <select v-model="form.area" class="input">
                    <option value="">All areas</option>
                    <option v-for="(label, key) in areas" :key="key" :value="key">{{ label }}</option>
                </select>
            </label>
            <label class="field">
                <span>Changed by</span>
                <select v-model="form.user" class="input">
                    <option value="">Anyone</option>
                    <option v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                    <option value="system">System / applicants</option>
                </select>
            </label>
            <label class="field">
                <span>From</span>
                <input v-model="form.from" type="date" class="input" />
            </label>
            <label class="field">
                <span>To</span>
                <input v-model="form.to" type="date" class="input" />
            </label>
            <label class="field">
                <span>Application #</span>
                <input v-model="form.application" type="number" min="1" class="input" placeholder="e.g. 12" />
            </label>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary">Filter</button>
                <button type="button" class="btn-ghost" @click="reset">Reset</button>
            </div>
        </form>

        <!-- Entries -->
        <section class="box mt-4 !p-0">
            <div v-if="!entries.data.length" class="muted p-5">No activity matches these filters.</div>

            <ul v-else class="divide-y divide-slate-100">
                <li v-for="e in entries.data" :key="e.id" class="px-5 py-3">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                        <span class="badge ring-1" :class="actionClass[e.action]">
                            {{ e.area }} {{ actionText[e.action] }}
                        </span>
                        <span class="font-semibold text-slate-900">{{ e.record }}</span>
                        <span class="text-slate-500">· {{ e.who }} · {{ fmt(e.at) }}</span>
                    </div>
                    <ul v-if="e.changes.length" class="mt-2 space-y-0.5 pl-1 text-xs text-slate-600">
                        <li v-for="(c, i) in e.changes" :key="i">
                            <span class="font-semibold text-slate-700">{{ c.field }}:</span>
                            <span class="line-through decoration-slate-300">{{ c.old }}</span>
                            → <span class="text-slate-900">{{ c.new }}</span>
                        </li>
                    </ul>
                </li>
            </ul>

            <nav
                v-if="entries.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-5 py-3 text-sm"
            >
                <span class="text-slate-500">
                    {{ entries.from }}–{{ entries.to }} of {{ entries.total }}
                </span>
                <div class="flex gap-2">
                    <Link
                        v-if="entries.prev_page_url"
                        :href="entries.prev_page_url"
                        preserve-scroll
                        class="btn-ghost"
                        >Newer</Link
                    >
                    <Link
                        v-if="entries.next_page_url"
                        :href="entries.next_page_url"
                        preserve-scroll
                        class="btn-ghost"
                        >Older</Link
                    >
                </div>
            </nav>
        </section>
    </StaffLayout>
</template>

<style scoped>
.box {
    @apply rounded-2xl bg-white/80 p-5 text-sm text-slate-800 ring-1 ring-slate-200;
}
.title {
    @apply mb-3 text-sm font-extrabold text-slate-900;
}
.muted {
    @apply text-sm text-slate-500;
}
.chip {
    @apply inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700;
}
.badge {
    @apply inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold;
}
.field {
    @apply flex flex-col gap-1 text-xs font-semibold text-slate-600;
}
.input {
    @apply rounded-xl border-slate-200 text-sm font-normal text-slate-800;
}
.btn-primary {
    @apply rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700;
}
.btn-ghost {
    @apply rounded-xl px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50;
}
.timeline {
    @apply relative ml-2 space-y-3 border-l-2 border-slate-200 pl-5;
}
.timeline-item {
    @apply relative;
}
.timeline-dot {
    @apply absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-slate-900 ring-4 ring-white;
}
</style>
