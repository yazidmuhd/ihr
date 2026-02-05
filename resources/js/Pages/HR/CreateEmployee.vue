<!-- resources/js/Pages/HR/CreateEmployee.vue -->
<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import { ref, computed } from "vue";

const form = ref({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    department: "",
    position: "",
    startDate: "",
    salary: "",
    notes: "",
});

const deptOptions = ["IT", "HR", "Finance", "Operations", "Marketing", "Sales"];

const basePositions = [
    {
        dept: "IT",
        roles: ["Frontend Engineer", "Backend Engineer", "QA Analyst"],
    },
    { dept: "HR", roles: ["HR Generalist", "Recruiter", "HR Assistant"] },
    {
        dept: "Finance",
        roles: ["Accountant", "AP/AR Executive", "Financial Analyst"],
    },
    {
        dept: "Operations",
        roles: ["Ops Executive", "Ops Coordinator", "Logistics Officer"],
    },
    {
        dept: "Marketing",
        roles: ["Content Specialist", "Performance Marketer", "Brand Exec"],
    },
    {
        dept: "Sales",
        roles: ["Sales Executive", "Account Manager", "BD Representative"],
    },
];

const suggestions = computed(() => {
    const d = form.value.department;
    const found = basePositions.find((x) => x.dept === d);
    return found ? found.roles : [];
});

function applySuggestion(role) {
    form.value.position = role;
}

function save() {
    alert("Employee created (UI only).");
}
</script>

<template>
    <StaffLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="page-title">Create Employee</h1>
                    <p class="page-sub">
                        Add a new team member and assign a position.
                    </p>
                </div>
            </div>
        </template>

        <!-- Card background + shadow -->
        <section
            class="form-surface rounded-2xl border bg-white/95 p-0 shadow-lg ring-1 ring-slate-200"
        >
            <!-- Hero strip (more spacing + higher contrast title) -->
            <div class="form-hero">
                <div class="form-hero-inner py-8 text-center md:py-12">
                    <h2
                        class="form-hero-title text-3xl font-extrabold tracking-tight text-slate-900 drop-shadow-sm md:text-3xl"
                    >
                        New Employee Profile
                    </h2>
                    <p
                        class="form-hero-sub mt-2 text-base text-black/95 md:text-lg"
                    >
                        <span class="font-semibold">Fill in the essentials</span
                        >, then pick a suggested position by department.
                    </p>
                </div>
            </div>

            <form class="space-y-10 p-6 md:p-8" @submit.prevent="save">
                <!-- Personal information -->
                <div>
                    <div class="form-header">
                        <h3 class="form-title">Personal information</h3>
                        <p class="form-desc">Identity and contact.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <div class="float-group">
                                <label
                                    for="firstName"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.firstName }"
                                    >First name</label
                                >
                                <input
                                    id="firstName"
                                    v-model="form.firstName"
                                    class="field-elevated"
                                    autocomplete="given-name"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-6">
                            <div class="float-group">
                                <label
                                    for="lastName"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.lastName }"
                                    >Last name</label
                                >
                                <input
                                    id="lastName"
                                    v-model="form.lastName"
                                    class="field-elevated"
                                    autocomplete="family-name"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-6">
                            <div class="float-group">
                                <label
                                    for="email"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.email }"
                                    >Email</label
                                >
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="field-elevated"
                                    autocomplete="email"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-6">
                            <div class="float-group">
                                <label
                                    for="phone"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.phone }"
                                    >Phone</label
                                >
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    class="field-elevated"
                                    placeholder="+60…"
                                    autocomplete="tel"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-sep"></div>

                <!-- Department & role (with suggestions) -->
                <div>
                    <div class="form-header">
                        <h3 class="form-title">Department & role</h3>
                        <p class="form-desc">
                            Pick a department to get position suggestions.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-4">
                            <div class="float-group">
                                <label
                                    for="department"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.department }"
                                    >Department</label
                                >
                                <select
                                    id="department"
                                    v-model="form.department"
                                    class="field-elevated"
                                >
                                    <option value=""></option>
                                    <option v-for="d in deptOptions" :key="d">
                                        {{ d }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="md:col-span-8">
                            <div class="float-group">
                                <label
                                    for="position"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.position }"
                                    >Position / Job title</label
                                >
                                <input
                                    id="position"
                                    v-model="form.position"
                                    class="field-elevated"
                                    placeholder="e.g. HR Generalist"
                                />
                            </div>

                            <!-- Suggestions -->
                            <div
                                v-if="suggestions.length"
                                class="mt-3 flex flex-wrap items-center gap-2"
                            >
                                <span class="meta">Suggestions:</span>
                                <button
                                    v-for="r in suggestions"
                                    :key="r"
                                    type="button"
                                    class="badge badge-brand hover:shadow"
                                    @click="applySuggestion(r)"
                                    title="Apply suggestion"
                                >
                                    {{ r }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-sep"></div>

                <!-- Employment details -->
                <div>
                    <div class="form-header">
                        <h3 class="form-title">Employment details</h3>
                        <p class="form-desc">Start date and package.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <div class="md:col-span-4">
                            <!-- DATE: hide Safari’s yyyy-mm-dd until focus/value -->
                            <div class="float-group">
                                <label
                                    for="startDate"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.startDate }"
                                    >Start date</label
                                >
                                <input
                                    id="startDate"
                                    v-model="form.startDate"
                                    type="date"
                                    class="field-elevated date-clean"
                                    :class="{ 'has-value': !!form.startDate }"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-4">
                            <div class="float-group">
                                <label
                                    for="salary"
                                    class="float-label"
                                    :class="{ 'is-filled': !!form.salary }"
                                    >Salary (RM)</label
                                >
                                <input
                                    id="salary"
                                    v-model="form.salary"
                                    class="field-elevated"
                                    placeholder="e.g. 6500"
                                />
                            </div>
                        </div>

                        <div class="md:col-span-12">
                            <label class="label mb-1">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="field w-full"
                                placeholder="Optional notes…"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:justify-end"
                >
                    <a
                        href="/hr/vacancies"
                        class="btn-soft rounded-full px-5 py-2.5 shadow transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="btn-brand rounded-full px-5 py-2.5 shadow-md transition hover:-translate-y-0.5 hover:shadow-xl"
                    >
                        Create employee
                    </button>
                </div>
            </form>
        </section>
    </StaffLayout>
</template>

<style scoped>
/* Floating label: never block clicks */
.float-label {
    pointer-events: none;
    position: absolute;
    left: 0.85rem;
    top: 0.9rem;
    background: white;
    padding: 0 0.4rem;
    font-size: 0.9rem;
    color: #64748b; /* slate-500 */
    border-radius: 0.375rem;
    transition:
        transform 150ms ease,
        color 150ms ease,
        font-size 150ms ease,
        background-color 150ms ease;
}

.float-group {
    position: relative;
}

.field-elevated {
    height: 3rem; /* 48px click target */
    width: 100%;
    border-radius: 0.75rem; /* rounded-xl */
    border: 2px solid rgb(226, 232, 240); /* slate-200 */
    padding: 0 0.875rem;
    outline: none;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease;
}
.field-elevated:focus {
    border-color: var(--brand, #00b09c);
    box-shadow: 0 0 0 4px rgba(0, 176, 156, 0.12);
}

/* Float the label on focus or when the field has a value */
.float-group:focus-within .float-label,
.float-label.is-filled {
    transform: translateY(-1.15rem) scale(0.9);
    color: var(--brand, #00b09c);
    font-size: 0.75rem;
}

/* ---- Date field cleanup (WebKit: hide yyyy until focus/value) ---- */
.date-clean::-webkit-datetime-edit {
    color: transparent;
}
.date-clean:focus::-webkit-datetime-edit,
.date-clean.has-value::-webkit-datetime-edit {
    color: inherit;
}
/* Optional: style the calendar icon slightly */
.date-clean::-webkit-calendar-picker-indicator {
    opacity: 0.8;
}
</style>
