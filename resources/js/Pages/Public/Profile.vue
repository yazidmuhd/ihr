<!-- resources/js/Pages/Applicant/Profile.vue -->
<script setup>
import AppLayout from "@/Components/App/AppLayout.vue";
import { ref } from "vue";

const profile = ref({
    name: "Your Name",
    email: "you@example.com",
    phone: "012-3456789",
});

const resumes = ref([
    { id: 1, name: "resume_2025.pdf", uploaded: "2025-10-07" },
]);

function save() {
    alert("Profile saved (UI only).");
}
function del(r) {
    resumes.value = resumes.value.filter((x) => x.id !== r.id);
    alert("Resume deleted (UI only).");
}
</script>

<template>
    <AppLayout>
        <!-- Page header (kept minimal since the hero below contains the main title) -->
        <template #header>
            <div class="sr-only">
                <h1 class="page-title">My Profile</h1>
            </div>
        </template>

        <!-- FORM SURFACE -->
        <section class="form-surface">
            <!-- Brand hero strip with centered, friendly heading -->
            <div class="form-hero">
                <div class="form-hero-inner text-center">
                    <h2 class="form-hero-title text-2xl">My Profile</h2>
                    <p class="form-hero-sub">
                        Keep your contact details up to date. These are used
                        only for your job applications.
                    </p>
                </div>
            </div>

            <form @submit.prevent="save" class="space-y-10">
                <!-- Personal details -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Full name -->
                    <div class="float-group">
                        <label
                            class="float-label"
                            :class="{ 'is-filled': !!profile.name }"
                            >Full name</label
                        >
                        <input v-model="profile.name" class="field-elevated" />
                    </div>

                    <!-- Email -->
                    <div class="float-group">
                        <label
                            class="float-label"
                            :class="{ 'is-filled': !!profile.email }"
                            >Email</label
                        >
                        <input
                            v-model="profile.email"
                            type="email"
                            class="field-elevated"
                        />
                    </div>

                    <!-- Phone -->
                    <div class="float-group md:col-span-2">
                        <label
                            class="float-label"
                            :class="{ 'is-filled': !!profile.phone }"
                            >Phone</label
                        >
                        <input v-model="profile.phone" class="field-elevated" />
                    </div>
                </div>

                <div class="section-sep"></div>

                <!-- Resumes -->
                <div>
                    <h3 class="form-title mb-3">My resumes</h3>
                    <div class="card">
                        <ul class="divide-y">
                            <li
                                v-for="r in resumes"
                                :key="r.id"
                                class="flex items-center justify-between py-3"
                            >
                                <div>
                                    <div class="font-medium text-slate-900">
                                        {{ r.name }}
                                    </div>
                                    <div class="meta">
                                        Uploaded {{ r.uploaded }}
                                    </div>
                                </div>
                                <button
                                    class="btn-outline btn-sm"
                                    @click="del(r)"
                                >
                                    Delete
                                </button>
                            </li>
                            <li
                                v-if="!resumes.length"
                                class="py-6 text-center text-sm text-slate-500"
                            >
                                No resumes uploaded yet.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        class="btn-soft rounded-full px-5 py-2.5 shadow transition hover:-translate-y-0.5 hover:shadow-md"
                        @click="$inertia.visit('/vacancies')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn-primary rounded-full px-5 py-2.5 shadow-md transition hover:-translate-y-0.5 hover:shadow-xl"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </section>
    </AppLayout>
</template>

<style scoped>
/* Floating label animation (uses .float-group/.float-label/.field-elevated from components.css) */
.float-label {
    transition:
        transform 150ms ease,
        color 150ms ease,
        background-color 150ms ease;
    transform: translateY(0) scale(1);
    border-radius: 0.375rem; /* match rounded-md look behind the label */
}
.float-group:focus-within .float-label,
.float-label.is-filled {
    transform: translateY(-14px) scale(0.92);
    color: var(--brand, #00b09c);
    background: white;
    padding-inline: 0.375rem;
}
</style>
