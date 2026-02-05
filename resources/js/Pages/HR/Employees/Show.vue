<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    employee: { type: Object, default: () => null },
    candidate: { type: Object, default: () => null },
    application: { type: Object, default: () => null },
    vacancy: { type: Object, default: () => null },
    resume: { type: Object, default: () => null },
    interview: { type: Object, default: () => null },
    panels: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Employee" />
    <StaffLayout>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">
                    {{ employee?.name || "Employee #" + employee?.id }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Employee record
                    <span v-if="vacancy?.title" class="text-slate-400">
                        • Hired for: {{ vacancy.title }}
                    </span>
                </p>
            </div>

            <Link
                href="/hr/employees"
                class="text-sm font-medium text-slate-700 hover:underline"
                >Back</Link
            >
        </div>

        <!-- Employee -->
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <section class="box">
                <h2 class="title">Employee Details</h2>

                <div class="row"><b>ID:</b> {{ employee?.id }}</div>
                <div class="row"><b>Name:</b> {{ employee?.name }}</div>
                <div class="row"><b>Position:</b> {{ employee?.position }}</div>
                <div class="row"><b>Hired At:</b> {{ employee?.hired_at }}</div>

                <div class="row" v-if="employee?.hr_user_id">
                    <b>HR User ID:</b> {{ employee.hr_user_id }}
                </div>
                <div class="row" v-if="employee?.application_id">
                    <b>Application ID:</b> {{ employee.application_id }}
                </div>
                <div class="row" v-if="employee?.applicant_id">
                    <b>Applicant ID:</b> {{ employee.applicant_id }}
                </div>
            </section>

            <!-- Candidate -->
            <section class="box">
                <h2 class="title">Candidate Profile (Before Hiring)</h2>

                <div v-if="!candidate" class="muted">
                    No candidate profile linked. (Check employees.applicant_id)
                </div>

                <template v-else>
                    <div class="row"><b>Name:</b> {{ candidate.name }}</div>
                    <div class="row" v-if="candidate.email">
                        <b>Email:</b> {{ candidate.email }}
                    </div>
                    <div class="row" v-if="candidate.phone">
                        <b>Phone:</b> {{ candidate.phone }}
                    </div>
                    <div class="row" v-if="candidate.headline">
                        <b>Headline:</b> {{ candidate.headline }}
                    </div>
                    <div class="row" v-if="candidate.location">
                        <b>Location:</b> {{ candidate.location }}
                    </div>

                    <div class="mt-3" v-if="candidate.skills_text">
                        <div class="text-sm font-semibold text-slate-800">
                            Skills
                        </div>
                        <div
                            class="mt-1 whitespace-pre-wrap text-sm text-slate-700"
                        >
                            {{ candidate.skills_text }}
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2 text-sm">
                        <a
                            v-if="candidate.linkedin_url"
                            :href="candidate.linkedin_url"
                            target="_blank"
                            class="chip"
                            >LinkedIn</a
                        >
                        <a
                            v-if="candidate.github_url"
                            :href="candidate.github_url"
                            target="_blank"
                            class="chip"
                            >GitHub</a
                        >
                        <a
                            v-if="candidate.portfolio_url"
                            :href="candidate.portfolio_url"
                            target="_blank"
                            class="chip"
                            >Portfolio</a
                        >
                        <a
                            v-if="candidate.website_url"
                            :href="candidate.website_url"
                            target="_blank"
                            class="chip"
                            >Website</a
                        >
                    </div>
                </template>
            </section>
        </div>

        <!-- Application -->
        <section class="box mt-4">
            <h2 class="title">Application Summary</h2>

            <div v-if="!application" class="muted">
                No application linked. (Check employees.application_id)
            </div>

            <template v-else>
                <div class="grid gap-2 md:grid-cols-3">
                    <div class="row">
                        <b>Application ID:</b> {{ application.id }}
                    </div>
                    <div class="row" v-if="application.status">
                        <b>Status:</b> {{ application.status }}
                    </div>
                    <div class="row" v-if="application.match_score != null">
                        <b>Resume/Match Score:</b> {{ application.match_score }}
                    </div>
                </div>

                <div class="mt-3" v-if="resume?.url">
                    <a :href="resume.url" target="_blank" class="chip underline"
                        >📄 {{ resume.name || "Resume" }}</a
                    >
                </div>
            </template>
        </section>

        <!-- Interview -->
        <section class="box mt-4">
            <h2 class="title">Interview Results</h2>

            <div v-if="!interview" class="muted">
                No interview found for this employee.
            </div>

            <template v-else>
                <div class="grid gap-2 md:grid-cols-3">
                    <div class="row">
                        <b>Interview ID:</b> {{ interview.id }}
                    </div>
                    <div class="row" v-if="interview.status">
                        <b>Status:</b> {{ interview.status }}
                    </div>
                    <div class="row" v-if="interview.scheduled_at">
                        <b>Scheduled:</b> {{ interview.scheduled_at }}
                    </div>
                    <div class="row" v-if="interview.mode">
                        <b>Mode:</b> {{ interview.mode }}
                    </div>
                    <div class="row" v-if="interview.location">
                        <b>Location:</b> {{ interview.location }}
                    </div>
                    <div class="row" v-if="interview.interview_score != null">
                        <b>Interview Score:</b> {{ interview.interview_score }}
                    </div>
                    <div class="row" v-if="interview.final_score != null">
                        <b>Final Score:</b> {{ interview.final_score }}
                    </div>
                </div>

                <div class="mt-4" v-if="panels?.length">
                    <div class="mb-2 text-sm font-semibold text-slate-800">
                        Panel Ratings
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-200 text-left text-slate-600"
                                >
                                    <th class="py-2 pr-3">Panel No</th>
                                    <th class="py-2 pr-3">Name</th>
                                    <th class="py-2 pr-3">Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="p in panels"
                                    :key="p.id || p.panel_no"
                                    class="border-b border-slate-100"
                                >
                                    <td class="py-2 pr-3">{{ p.panel_no }}</td>
                                    <td class="py-2 pr-3">
                                        {{ p.name || `Panel ${p.panel_no}` }}
                                    </td>
                                    <td class="py-2 pr-3">
                                        <span class="stars">
                                            <span
                                                v-for="n in 5"
                                                :key="n"
                                                :class="
                                                    n <= (p.rating ?? 0)
                                                        ? 'on'
                                                        : 'off'
                                                "
                                                >★</span
                                            >
                                        </span>
                                        <span class="ml-2 text-slate-500"
                                            >({{ p.rating ?? 0 }}/5)</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="muted mt-3" v-else>No panel ratings recorded.</div>
            </template>
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
.row {
    @apply py-1;
}
.muted {
    @apply text-sm text-slate-500;
}
.chip {
    @apply inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700;
}
.stars {
    font-size: 16px;
    line-height: 1;
}
.on {
    color: #f59e0b;
}
.off {
    color: #cbd5e1;
}
</style>
