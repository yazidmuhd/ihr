<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import HrLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    prefill: { type: Object, default: () => ({}) },
    context: { type: Object, default: () => null },
});

const form = useForm({
    application_id: props.prefill.application_id ?? null,
    name: props.prefill.name ?? "",
    position: props.prefill.position ?? "",
    hired_at: props.prefill.hired_at ?? "",
});

function submit() {
    form.post("/hr/employees", { preserveScroll: true });
}
</script>

<template>
    <Head title="Create Employee" />
    <HrLayout>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    Create Employee
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Create an employee record (manual or from a hired
                    application).
                </p>
            </div>
            <Link
                href="/hr/employees"
                class="text-sm font-medium text-slate-700 hover:underline"
                >Back</Link
            >
        </div>

        <div
            v-if="props.context"
            class="mt-4 rounded-2xl bg-emerald-50/70 p-4 ring-1 ring-emerald-100"
        >
            <div class="text-sm font-semibold text-slate-900">
                Source application
            </div>
            <div class="mt-1 text-sm text-slate-700">
                Applicant: <b>{{ props.context.applicant?.name }}</b>
            </div>
            <div class="text-sm text-slate-700">
                Vacancy: <b>{{ props.context.vacancy?.title }}</b>
            </div>
            <div class="mt-1 text-xs text-slate-600">
                Application #{{ props.context.application?.id }} • status:
                {{ props.context.application?.status }}
            </div>
        </div>

        <form
            @submit.prevent="submit"
            class="mt-5 grid gap-4 rounded-2xl bg-white/80 p-5 ring-1 ring-slate-200"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="label">Employee name</label>
                    <input v-model="form.name" class="input" type="text" />
                    <div v-if="form.errors.name" class="err">
                        {{ form.errors.name }}
                    </div>
                </div>

                <div>
                    <label class="label">Position</label>
                    <input v-model="form.position" class="input" type="text" />
                    <div v-if="form.errors.position" class="err">
                        {{ form.errors.position }}
                    </div>
                </div>

                <div>
                    <label class="label">Hired date</label>
                    <input v-model="form.hired_at" class="input" type="date" />
                    <div v-if="form.errors.hired_at" class="err">
                        {{ form.errors.hired_at }}
                    </div>
                </div>

                <div>
                    <label class="label">Linked application</label>
                    <input
                        v-model="form.application_id"
                        class="input"
                        type="number"
                        placeholder="optional"
                    />
                    <div v-if="form.errors.application_id" class="err">
                        {{ form.errors.application_id }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="btn-primary"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Saving..." : "Create employee" }}
                </button>
                <span
                    v-if="form.recentlySuccessful"
                    class="text-sm text-emerald-700"
                    >Saved.</span
                >
            </div>
        </form>
    </HrLayout>
</template>

<style scoped>
.label {
    @apply text-xs font-semibold text-slate-600;
}
.input {
    @apply mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900;
}
.err {
    @apply mt-1 text-xs text-rose-600;
}
.btn-primary {
    @apply inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-white transition;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
}
</style>
