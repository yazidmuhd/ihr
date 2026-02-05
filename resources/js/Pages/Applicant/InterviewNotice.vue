<!-- resources/js/Pages/Applicant/InterviewNotice.vue -->
<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

const notices = ref([
    {
        id: 1,
        title: "Frontend Engineer — First Interview",
        time: "2025-10-15 10:30",
        mode: "Online",
        link: "https://meet.example.com/abc-123",
        confirmed: false,
    },
    {
        id: 2,
        title: "Backend Engineer — Technical Interview",
        time: "2025-10-18 14:00",
        mode: "Onsite",
        location: "i-HR HQ, Level 12, Meeting A",
        confirmed: true,
    },
]);

function confirm(n) {
    if (n.confirmed) return;
    n.confirmed = true;
    alert("Interview confirmed (UI only).");
}
</script>

<template>
    <ApplicantLayout>
        <!-- Keep layout header minimal; hero below shows the main title -->
        <template #header>
            <div class="sr-only">
                <h1 class="page-title">Interview Notifications</h1>
            </div>
        </template>

        <!-- Branded surface wrapper -->
        <section class="form-surface">
            <!-- Centered hero -->
            <div class="form-hero">
                <div class="form-hero-inner text-center">
                    <h2 class="form-hero-title">Interview Notifications</h2>
                    <p class="form-hero-sub">
                        Confirm your availability so HR can proceed with
                        scheduling.
                    </p>
                </div>
            </div>

            <!-- Toolbar -->
            <div
                class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="muted">
                    You have <strong>{{ notices.length }}</strong> notice<span
                        v-if="notices.length !== 1"
                        >s</span
                    >.
                </div>

                <div class="flex items-center gap-2 sm:justify-end">
                    <Link
                        href="/applicant/applications"
                        class="btn-ghost btn-sm"
                    >
                        Back to Applications
                    </Link>
                </div>
            </div>

            <!-- List -->
            <div class="space-y-3">
                <!-- Empty state -->
                <div v-if="!notices.length" class="card">
                    <div class="muted">No interview notices at the moment.</div>
                </div>

                <!-- Notice cards -->
                <div
                    v-for="n in notices"
                    :key="n.id"
                    class="card flex items-start justify-between gap-4"
                >
                    <div class="flex items-start gap-4">
                        <!-- Icon bubble -->
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary"
                            aria-hidden="true"
                        >
                            <!-- calendar icon -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M7 3v4M17 3v4" />
                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="16"
                                    rx="2"
                                />
                                <path d="M3 11h18" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div>
                            <div class="font-medium text-slate-900">
                                {{ n.title }}
                            </div>

                            <div
                                class="muted mt-1 flex flex-wrap items-center gap-2"
                            >
                                <!-- time -->
                                <span class="inline-flex items-center gap-1">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 3" />
                                    </svg>
                                    {{ n.time }}
                                </span>

                                <!-- divider dot -->
                                <span>•</span>

                                <!-- mode -->
                                <span class="badge-brand">{{ n.mode }}</span>
                            </div>

                            <div
                                v-if="n.mode === 'Online' && n.link"
                                class="mt-1"
                            >
                                <a
                                    :href="n.link"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn-link"
                                >
                                    Join meeting
                                </a>
                            </div>

                            <div v-else-if="n.location" class="muted mt-1">
                                Location: {{ n.location }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <span v-if="n.confirmed" class="badge badge-ok"
                            >Confirmed</span
                        >
                        <button v-else class="btn-brand" @click="confirm(n)">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </ApplicantLayout>
</template>
