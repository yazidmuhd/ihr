<!-- resources/js/Pages/Applicant/Profile.vue -->
<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import { ref } from "vue";

const form = ref({
    firstName: "",
    lastName: "",
    email: "applicant@example.com", // demo only
    phone: "",
    yearsExp: "",
    expectedSalary: "",
    city: "",
    country: "",
    linkedin: "",
    website: "",
    resumeName: "",
});

function save() {
    alert("Profile saved (UI only).");
}

function chooseResume(e) {
    const f = e.target.files?.[0];
    if (f) form.value.resumeName = f.name;
}

function deleteResume() {
    form.value.resumeName = "";
    alert("Resume deleted (UI only).");
}
</script>

<template>
    <ApplicantLayout>
        <!-- App header is minimal; the hero below is the visible title -->
        <template #header><span class="sr-only">My Profile</span></template>

        <!-- Card background for the whole form -->
        <section class="form-surface mx-auto max-w-5xl">
            <!-- Brand strip -->
            <div class="form-hero">
                <div class="form-hero-inner">
                    <h1 class="form-hero-title">My Profile</h1>
                    <p class="form-hero-sub">
                        Your information is private and used only for job
                        applications.
                    </p>
                </div>
            </div>

            <form class="space-y-12 p-6 sm:p-8" @submit.prevent="save">
                <!-- Personal info -->
                <div>
                    <div class="section-head">
                        <h2 class="section-title">Personal information</h2>
                        <p class="section-sub">
                            Basic identification and contact.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="label mb-1">First name</label>
                            <input
                                v-model="form.firstName"
                                class="field h-12 w-full"
                            />
                        </div>

                        <div class="md:col-span-6">
                            <label class="label mb-1">Last name</label>
                            <input
                                v-model="form.lastName"
                                class="field h-12 w-full"
                            />
                        </div>

                        <div class="min-w-0 md:col-span-6">
                            <label class="label mb-1">Email</label>
                            <input
                                v-model="form.email"
                                class="field h-12 w-full bg-slate-50"
                                disabled
                            />
                            <p class="help mt-1">
                                Email comes from your login account.
                            </p>
                        </div>

                        <div class="md:col-span-6">
                            <label class="label mb-1">Phone</label>
                            <input
                                v-model="form.phone"
                                class="field h-12 w-full"
                                placeholder="+60…"
                            />
                        </div>
                    </div>
                </div>

                <div class="section-sep" />

                <!-- Work -->
                <div>
                    <div class="section-head">
                        <h2 class="section-title">Work &amp; location</h2>
                        <p class="section-sub">
                            Experience and where you’re based.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-3">
                            <label class="label mb-1"
                                >Years of experience</label
                            >
                            <select
                                v-model="form.yearsExp"
                                class="field h-12 w-full"
                            >
                                <option value="">Select…</option>
                                <option>0–1</option>
                                <option>2–3</option>
                                <option>4–6</option>
                                <option>7–10</option>
                                <option>10+</option>
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="label mb-1"
                                >Expected salary (RM)</label
                            >
                            <input
                                v-model="form.expectedSalary"
                                class="field h-12 w-full"
                                placeholder="e.g. 6000"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="label mb-1">City</label>
                            <input
                                v-model="form.city"
                                class="field h-12 w-full"
                            />
                        </div>

                        <div class="md:col-span-3">
                            <label class="label mb-1">Country</label>
                            <input
                                v-model="form.country"
                                class="field h-12 w-full"
                            />
                        </div>
                    </div>
                </div>

                <div class="section-sep" />

                <!-- Links -->
                <div>
                    <div class="section-head">
                        <h2 class="section-title">Links</h2>
                        <p class="section-sub">
                            Share your LinkedIn or portfolio.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="label mb-1">LinkedIn</label>
                            <input
                                v-model="form.linkedin"
                                class="field h-12 w-full"
                                placeholder="https://linkedin.com/in/…"
                            />
                        </div>
                        <div class="md:col-span-6">
                            <label class="label mb-1"
                                >Portfolio / Website</label
                            >
                            <input
                                v-model="form.website"
                                class="field h-12 w-full"
                                placeholder="https://…"
                            />
                        </div>
                    </div>
                </div>

                <div class="section-sep" />

                <!-- Resume upload -->
                <div>
                    <div class="section-head">
                        <h2 class="section-title">Resume</h2>
                        <p class="section-sub">PDF or DOCX • max 5 MB.</p>
                    </div>

                    <div class="resume-drop">
                        <div
                            class="flex flex-col items-center gap-3 text-center sm:flex-row sm:justify-between sm:text-left"
                        >
                            <div>
                                <div class="font-medium text-slate-900">
                                    {{ form.resumeName || "No file selected" }}
                                </div>
                                <p class="help">
                                    Click <strong>“Upload resume”</strong> to
                                    attach a file.
                                </p>
                            </div>

                            <div class="flex gap-3">
                                <label class="btn-outline cursor-pointer">
                                    <input
                                        type="file"
                                        class="hidden"
                                        @change="chooseResume"
                                    />
                                    Upload resume
                                </label>
                                <button
                                    type="button"
                                    class="btn-danger"
                                    @click="deleteResume"
                                    :disabled="!form.resumeName"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="btn-soft rounded-full px-5 py-2.5 shadow transition hover:-translate-y-0.5 hover:shadow-md"
                        @click="$inertia.visit('/vacancies')"
                    >
                        Cancel
                    </button>

                    <!-- changed to btn-white -->
                    <button
                        type="submit"
                        class="btn-white rounded-full px-5 py-2.5 shadow-md transition hover:-translate-y-0.5 hover:shadow-xl"
                    >
                        Save changes
                    </button>
                </div>
            </form>
        </section>
    </ApplicantLayout>
</template>

<style scoped>
/* Brand var (matches your project color) */
:root {
    --brand: #00b09c;
}

/* ---------- Card background (form surface) ---------- */
.form-surface {
    background: #fff;
    border-radius: 1.25rem; /* ~rounded-2xl */
    box-shadow: 0 10px 30px rgba(2, 6, 23, 0.06);
    border: 1px solid rgba(15, 23, 42, 0.06);
    overflow: hidden;
}

/* ---------- Brand hero with centered title ---------- */
.form-hero {
    background:
        radial-gradient(
            60% 120% at 0% 0%,
            rgba(0, 176, 156, 0.35),
            rgba(0, 176, 156, 0.15) 50%,
            transparent 90%
        ),
        linear-gradient(135deg, var(--brand), #009884);
    color: #fff;
    padding: 2.25rem 1rem; /* py-9 px-4 */
}
.form-hero-inner {
    max-width: 42rem; /* ~max-w-2xl */
    margin: 0 auto;
    text-align: center;
}
.form-hero-title {
    margin: 0;
    font-weight: 800;
    letter-spacing: -0.01em;
    font-size: clamp(1.5rem, 1.1rem + 1vw, 1.875rem); /* 2xl on large */
}
.form-hero-sub {
    margin-top: 0.5rem;
    font-size: 0.95rem;
    opacity: 0.95;
}

/* ---------- Centered section headings ---------- */
.section-head {
    text-align: center;
    margin-bottom: 0.75rem;
}
.section-title {
    margin: 0;
    font-weight: 700;
    color: rgb(15 23 42);
}
.section-sub {
    margin-top: 0.25rem;
    color: rgb(100 116 139);
    font-size: 0.9rem;
}

/* Brand tinted divider */
.section-sep {
    height: 1px;
    width: 100%;
    background: linear-gradient(
        90deg,
        rgba(0, 176, 156, 0) 0%,
        rgba(0, 176, 156, 0.25) 20%,
        rgba(0, 176, 156, 0.25) 80%,
        rgba(0, 176, 156, 0) 100%
    );
}

/* ---------- Resume drop area ---------- */
.resume-drop {
    background: #fff;
    border-radius: 1rem;
    padding: 1rem;
    border: 2px dashed rgba(100, 116, 139, 0.35); /* slate-500/35 dashed look */
}

/* Button fallbacks (uses your global utility classes, but safe here) */
.btn-soft {
    background: rgb(241 245 249);
    color: rgb(51 65 85);
}
.btn-primary {
    background: var(--brand);
    color: #fff;
}
.btn-primary:hover {
    filter: brightness(0.95);
}

/* Keep Tailwind label/field look consistent if global classes aren’t present */
.label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: rgb(51 65 85);
}
.field {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid rgb(203 213 225);
    padding: 0.6rem 0.875rem;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.field:focus {
    outline: none;
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(0, 176, 156, 0.15);
}
.help {
    font-size: 0.75rem;
    color: rgb(148 163 184);
}
.btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.35rem 0.8rem;
    border-radius: 0.6rem;
    border: 1px solid rgb(203 213 225);
}
.btn-danger {
    background: #dc2626;
    color: #fff;
}
.btn-danger:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
