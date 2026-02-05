<!-- resources/js/Pages/HR/Vacancies/Edit.vue -->
<script setup>
import { Head, router, Link, usePage } from "@inertiajs/vue3";
import { reactive, computed, ref } from "vue";
import HrLayout from "@/Components/Layouts/StaffLayout.vue";

const props = defineProps({
    vacancy: { type: Object, required: true },
});

const page = usePage();
const errors = computed(() => page.props.errors ?? {});
const today = new Date().toISOString().slice(0, 10);

// Toast
const toast = ref({ show: false, message: "", type: "" });
function showToast(message, type = "success") {
    toast.value = { show: true, message, type };
    setTimeout(() => (toast.value.show = false), 3000);
}

// Normalize existing skills into editable text
const initialSkillsText = (() => {
    const s = props.vacancy.skills_required;
    if (Array.isArray(s)) return s.join(", ");
    if (typeof s === "string") return s;
    return "";
})();

// Normalize deadline (may come as `deadline` or `closing_date`)
const initialDeadline = (() => {
    if (props.vacancy.deadline)
        return String(props.vacancy.deadline).slice(0, 10);
    if (props.vacancy.closing_date)
        return String(props.vacancy.closing_date).slice(0, 10);
    return "";
})();

// Prefer new range fields; fall back to legacy single value
const initialMinYears =
    props.vacancy.experience_min_years ??
    props.vacancy.experience_years_required ??
    "";

const initialMaxYears = props.vacancy.experience_max_years ?? "";

// Dropdown options (match Create.vue)
const employmentOptions = [
    { value: "permanent", label: "Permanent" },
    { value: "contract", label: "Contract" },
    { value: "intern", label: "Intern" },
];

const statusOptions = [
    { value: "Open", label: "Open" },
    { value: "Closed", label: "Closed" },
    { value: "Archived", label: "Archived" },
];

const departmentOptions = [
    { value: "Production / Operations", label: "Production / Operations" },
    { value: "Maintenance (Mechanical)", label: "Maintenance (Mechanical)" },
    { value: "Maintenance (Electrical)", label: "Maintenance (Electrical)" },
    {
        value: "Maintenance (Instrumentation)",
        label: "Maintenance (Instrumentation)",
    },
    {
        value: "Automation / Control Systems",
        label: "Automation / Control Systems",
    },
    { value: "Engineering (Process)", label: "Engineering (Process)" },
    { value: "Engineering (Project)", label: "Engineering (Project)" },
    { value: "Technical / Reliability", label: "Technical / Reliability" },
    { value: "HSE / Process Safety", label: "HSE / Process Safety" },
    { value: "Quality Assurance (QA)", label: "Quality Assurance (QA)" },
    {
        value: "Quality Control / Laboratory (QC/Lab)",
        label: "Quality Control / Laboratory (QC/Lab)",
    },
    { value: "Supply Chain / Logistics", label: "Supply Chain / Logistics" },
    { value: "Procurement / Purchasing", label: "Procurement / Purchasing" },
    { value: "Warehouse / Inventory", label: "Warehouse / Inventory" },
    { value: "IT / Digitalization", label: "IT / Digitalization" },
    { value: "Finance / Accounting", label: "Finance / Accounting" },
    { value: "Human Resources (HR)", label: "Human Resources (HR)" },
    {
        value: "Administration / Office Management",
        label: "Administration / Office Management",
    },
    {
        value: "Security / Emergency Response",
        label: "Security / Emergency Response",
    },
];

const locationOptions = [
    { value: "On-site (Gebeng, Kuantan)", label: "On-site (Gebeng, Kuantan)" },
    { value: "Hybrid (Gebeng + Remote)", label: "Hybrid (Gebeng + Remote)" },
    { value: "Work From Home (Remote)", label: "Work From Home (Remote)" },
];

const form = reactive({
    title: props.vacancy.title ?? "",
    department: props.vacancy.department ?? "",
    location: props.vacancy.location ?? "",
    description: props.vacancy.description ?? "",

    status: props.vacancy.status ?? "Open",
    employment_type: props.vacancy.employment_type ?? "permanent",

    experience_min_years: initialMinYears,
    experience_max_years: initialMaxYears,
    education_required: props.vacancy.education_required ?? "",
    skills_required_text: initialSkillsText,

    deadline: initialDeadline,
});

function parseList(text) {
    return (text || "")
        .split(/[,;\n]/)
        .map((s) => s.trim())
        .filter(Boolean);
}

function submit() {
    const skills = parseList(form.skills_required_text);

    const minYears =
        form.experience_min_years === "" || form.experience_min_years === null
            ? null
            : Number(form.experience_min_years);

    const maxYears =
        form.experience_max_years === "" || form.experience_max_years === null
            ? null
            : Number(form.experience_max_years);

    if (
        minYears !== null &&
        maxYears !== null &&
        !Number.isNaN(minYears) &&
        !Number.isNaN(maxYears) &&
        maxYears < minYears
    ) {
        showToast(
            "Max years should be greater than or equal to min years",
            "error",
        );
        return;
    }

    const payload = {
        title: form.title,
        department: form.department?.trim() || null,
        location: form.location?.trim() || null,
        description: form.description?.trim() || null,

        status: form.status,
        employment_type: form.employment_type,

        experience_min_years: minYears,
        experience_max_years: maxYears,
        experience_years_required: minYears, // compatibility

        education_required: form.education_required?.trim() || null,
        skills_required: skills.length ? skills : null,

        deadline: form.deadline?.trim() || null,
        closing_date: form.deadline?.trim() || null,
    };

    router.put(`/hr/vacancies/${props.vacancy.id}`, payload, {
        onSuccess: () => showToast("Vacancy updated successfully!", "success"),
        onError: () =>
            showToast("Validation failed. Please check the form.", "error"),
    });
}

const icons = {
    briefcase:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    building:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/></svg>',
    mapPin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    calendar:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
    target: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    award: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/></svg>',
    book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 19.5A2.5 2.5 0 0 0 6.5 22H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/></svg>',
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    fileText:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>',
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
    arrowLeft:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>',
    sparkles:
        '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0l1.5 5.5L19 7l-5.5 1.5L12 14l-1.5-5.5L5 7l5.5-1.5L12 0zM5 14l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zM18 13l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/></svg>',
};
</script>

<template>
    <Head :title="`Edit Vacancy — ${props.vacancy.title}`" />
    <HrLayout>
        <div class="page-container">
            <!-- Toast Notification -->
            <transition name="toast">
                <div
                    v-if="toast.show"
                    class="toast"
                    :class="`toast-${toast.type}`"
                >
                    <span
                        class="toast-icon"
                        v-html="
                            toast.type === 'success'
                                ? icons.check
                                : toast.type === 'error'
                                  ? icons.alert
                                  : icons.info
                        "
                    ></span>
                    <span class="toast-message">{{ toast.message }}</span>
                </div>
            </transition>

            <!-- Header -->
            <section class="header-card">
                <div class="header-glow"></div>
                <div class="header-content">
                    <div class="header-left">
                        <div class="header-icon">
                            <span v-html="icons.briefcase"></span>
                            <div class="icon-pulse"></div>
                        </div>
                        <div>
                            <h1 class="header-title">Edit Vacancy</h1>
                            <p class="header-subtitle">
                                Update job details and AI matching requirements
                            </p>
                        </div>
                    </div>

                    <Link href="/hr/vacancies" class="header-btn">
                        <span class="btn-icon" v-html="icons.arrowLeft"></span>
                        <span>Back to Vacancies</span>
                    </Link>
                </div>
            </section>

            <!-- Form -->
            <form @submit.prevent="submit" class="form-grid">
                <!-- Basic Information -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <span v-html="icons.fileText"></span>
                        </div>
                        <div>
                            <h2 class="section-title">Basic Information</h2>
                            <p class="section-subtitle">
                                Essential job details and posting configuration
                            </p>
                        </div>
                    </div>

                    <div class="fields-grid">
                        <!-- Job Title -->
                        <div class="field-group field-full">
                            <label class="field-label">
                                Job Title
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.briefcase"
                                ></span>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="form-input-icon form-input"
                                    required
                                    placeholder="e.g., Finance Analyst (Budget & Reporting)"
                                />
                            </div>
                            <p v-if="errors.title" class="field-error">
                                {{ errors.title }}
                            </p>
                        </div>

                        <!-- Department -->
                        <div class="field-group">
                            <label class="field-label">
                                Department
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.building"
                                ></span>
                                <select
                                    v-model="form.department"
                                    class="form-input-icon form-select"
                                    required
                                >
                                    <option value="" disabled>
                                        Select department...
                                    </option>
                                    <option
                                        v-for="o in departmentOptions"
                                        :key="o.value"
                                        :value="o.value"
                                    >
                                        {{ o.label }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="errors.department" class="field-error">
                                {{ errors.department }}
                            </p>
                        </div>

                        <!-- Location -->
                        <div class="field-group">
                            <label class="field-label">
                                Work Arrangement
                                <span class="required">*</span>
                            </label>
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.mapPin"
                                ></span>
                                <select
                                    v-model="form.location"
                                    class="form-input-icon form-select"
                                    required
                                >
                                    <option value="" disabled>
                                        Select work arrangement...
                                    </option>
                                    <option
                                        v-for="o in locationOptions"
                                        :key="o.value"
                                        :value="o.value"
                                    >
                                        {{ o.label }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="errors.location" class="field-error">
                                {{ errors.location }}
                            </p>
                        </div>

                        <!-- Employment Type -->
                        <div class="field-group">
                            <label class="field-label">
                                Employment Type
                                <span class="required">*</span>
                            </label>
                            <select
                                v-model="form.employment_type"
                                class="form-select"
                                required
                            >
                                <option
                                    v-for="o in employmentOptions"
                                    :key="o.value"
                                    :value="o.value"
                                >
                                    {{ o.label }}
                                </option>
                            </select>
                            <p
                                v-if="errors.employment_type"
                                class="field-error"
                            >
                                {{ errors.employment_type }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div class="field-group">
                            <label class="field-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option
                                    v-for="o in statusOptions"
                                    :key="o.value"
                                    :value="o.value"
                                >
                                    {{ o.label }}
                                </option>
                            </select>
                            <p v-if="errors.status" class="field-error">
                                {{ errors.status }}
                            </p>
                        </div>

                        <!-- Deadline -->
                        <div class="field-group">
                            <label class="field-label"
                                >Application Deadline</label
                            >
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.calendar"
                                ></span>
                                <input
                                    v-model="form.deadline"
                                    type="date"
                                    :min="today"
                                    class="form-input-icon form-input"
                                />
                            </div>
                            <p class="field-hint">
                                Optional. Applications close on this date.
                            </p>
                            <p
                                v-if="errors.deadline || errors.closing_date"
                                class="field-error"
                            >
                                {{ errors.deadline || errors.closing_date }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- AI Requirements -->
                <section class="form-section section-ai">
                    <div class="section-header">
                        <div class="section-icon">
                            <span v-html="icons.target"></span>
                        </div>
                        <div>
                            <h2 class="section-title">
                                AI Matching Requirements
                            </h2>
                            <p class="section-subtitle">
                                Criteria used for intelligent candidate scoring
                            </p>
                        </div>
                    </div>

                    <div class="fields-grid">
                        <!-- Min Experience -->
                        <div class="field-group">
                            <label class="field-label"
                                >Minimum Years of Experience</label
                            >
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.clock"
                                ></span>
                                <input
                                    v-model.number="form.experience_min_years"
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="e.g., 1"
                                    class="form-input-icon form-input"
                                />
                            </div>
                            <p class="field-hint">
                                Candidates below this get a lower score
                            </p>
                            <p
                                v-if="
                                    errors.experience_min_years ||
                                    errors.experience_years_required
                                "
                                class="field-error"
                            >
                                {{
                                    errors.experience_min_years ||
                                    errors.experience_years_required
                                }}
                            </p>
                        </div>

                        <!-- Max Experience -->
                        <div class="field-group">
                            <label class="field-label"
                                >Maximum Years of Experience (Ideal)</label
                            >
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.clock"
                                ></span>
                                <input
                                    v-model.number="form.experience_max_years"
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="e.g., 4"
                                    class="form-input-icon form-input"
                                />
                            </div>
                            <p class="field-hint">
                                Ideal range scores slightly better
                            </p>
                            <p
                                v-if="errors.experience_max_years"
                                class="field-error"
                            >
                                {{ errors.experience_max_years }}
                            </p>
                        </div>

                        <!-- Education -->
                        <div class="field-group">
                            <label class="field-label"
                                >Education Requirement</label
                            >
                            <div class="input-with-icon">
                                <span
                                    class="input-icon"
                                    v-html="icons.book"
                                ></span>
                                <input
                                    v-model="form.education_required"
                                    type="text"
                                    class="form-input-icon form-input"
                                    placeholder="e.g., Degree in Finance / Accounting / Business"
                                />
                            </div>
                            <p class="field-hint">
                                AI will match the candidate's education level
                            </p>
                            <p
                                v-if="errors.education_required"
                                class="field-error"
                            >
                                {{ errors.education_required }}
                            </p>
                        </div>

                        <!-- Core Skills -->
                        <div class="field-group">
                            <label class="field-label"
                                >Core Skills (for AI Matching)</label
                            >
                            <div class="input-with-icon">
                                <span
                                    class="input-icon input-icon-top"
                                    v-html="icons.award"
                                ></span>
                                <textarea
                                    v-model="form.skills_required_text"
                                    rows="3"
                                    class="form-input-icon form-textarea"
                                    placeholder="Example: excel, budgeting, forecasting, variance analysis, financial reporting, power bi"
                                ></textarea>
                            </div>
                            <p class="field-hint">
                                Separate skills with commas or one per line
                            </p>
                            <p
                                v-if="errors.skills_required"
                                class="field-error"
                            >
                                {{ errors.skills_required }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Job Description -->
                <section class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <span v-html="icons.fileText"></span>
                        </div>
                        <div>
                            <h2 class="section-title">Job Description</h2>
                            <p class="section-subtitle">
                                Detailed information about the role and
                                responsibilities
                            </p>
                        </div>
                    </div>

                    <div class="field-group">
                        <textarea
                            v-model="form.description"
                            rows="8"
                            class="form-textarea"
                            placeholder="Brief intro, key responsibilities, and requirements..."
                        ></textarea>
                        <p class="field-hint">
                            AI also scans this text for extra keywords and
                            context
                        </p>
                        <p v-if="errors.description" class="field-error">
                            {{ errors.description }}
                        </p>
                    </div>
                </section>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <span class="btn-icon" v-html="icons.sparkles"></span>
                        <span>Save Changes</span>
                    </button>
                    <Link href="/hr/vacancies" class="cancel-btn">
                        <span class="btn-icon" v-html="icons.arrowLeft"></span>
                        <span>Cancel</span>
                    </Link>
                </div>
            </form>
        </div>
    </HrLayout>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Bricolage+Grotesque:wght@600;700;800&display=swap");

/* Icons */
.btn-icon {
    display: inline-flex;
    width: 18px;
    height: 18px;
}
.btn-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.input-icon {
    display: inline-flex;
    width: 20px;
    height: 20px;
    color: #64748b;
}
.input-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.input-icon-top {
    align-self: flex-start;
    margin-top: 0.875rem;
}

/* Page Container */
.page-container {
    max-width: 80rem;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    gap: 2rem;
}

/* Toast */
.toast {
    position: fixed;
    top: 2rem;
    right: 2rem;
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    font-size: 0.9375rem;
    font-weight: 600;
}
.toast-icon {
    display: flex;
    width: 20px;
    height: 20px;
}
.toast-icon :deep(svg) {
    width: 100%;
    height: 100%;
}
.toast-success {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.95),
        rgba(5, 150, 105, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-error {
    background: linear-gradient(
        135deg,
        rgba(239, 68, 68, 0.95),
        rgba(220, 38, 38, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-info {
    background: linear-gradient(
        135deg,
        rgba(59, 130, 246, 0.95),
        rgba(37, 99, 235, 0.95)
    );
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.toast-enter-active {
    animation: toastIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-leave-active {
    animation: toastOut 0.3s cubic-bezier(0.4, 0, 1, 1);
}
@keyframes toastIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
@keyframes toastOut {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(-20px) scale(0.9);
    }
}

/* Header */
.header-card {
    position: relative;
    padding: 2rem;
    background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
    border-radius: 24px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
    box-shadow: 0 20px 60px -40px rgba(14, 165, 233, 0.45);
}
.header-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top right,
        rgba(6, 182, 212, 0.1) 0%,
        transparent 60%
    );
    pointer-events: none;
}
.header-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.header-icon {
    position: relative;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.15),
        rgba(14, 165, 233, 0.15)
    );
    border-radius: 16px;
    color: #0ea5e9;
}
.header-icon :deep(svg) {
    width: 32px;
    height: 32px;
    position: relative;
    z-index: 1;
}
.icon-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 16px;
    border: 2px solid rgba(6, 182, 212, 0.4);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.6;
    }
    50% {
        transform: scale(1.1);
        opacity: 0;
    }
}
.header-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin: 0;
}
.header-subtitle {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
    margin: 0.25rem 0 0;
}
.header-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0f172a;
    text-decoration: none;
    transition: all 0.3s;
    white-space: nowrap;
}
.header-btn:hover {
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(6, 182, 212, 0.2);
}

/* Form Grid */
.form-grid {
    display: grid;
    gap: 2rem;
}

/* Form Section */
.form-section {
    background: white;
    border-radius: 20px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    animation: sectionFadeIn 0.6s ease-out both;
}
@keyframes sectionFadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.section-ai {
    border-color: rgba(6, 182, 212, 0.3);
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        rgba(6, 182, 212, 0.01) 100%
    );
}

.section-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
}
.section-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(6, 182, 212, 0.1),
        rgba(14, 165, 233, 0.1)
    );
    border-radius: 12px;
    color: #0ea5e9;
    flex-shrink: 0;
}
.section-icon :deep(svg) {
    width: 24px;
    height: 24px;
}
.section-title {
    font-family: "Bricolage Grotesque", sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}
.section-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0.25rem 0 0;
}

/* Fields Grid */
.fields-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}
.field-full {
    grid-column: 1 / -1;
}

/* Field Group */
.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.required {
    color: #ef4444;
    margin-left: 0.25rem;
}
.field-hint {
    font-size: 0.8125rem;
    color: #64748b;
    margin-top: 0.375rem;
}
.field-error {
    font-size: 0.8125rem;
    color: #ef4444;
    margin-top: 0.375rem;
}

/* Form Inputs */
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    font-size: 0.9375rem;
    color: #0f172a;
    background: white;
    transition: all 0.3s;
    font-family: inherit;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}
.form-textarea {
    resize: vertical;
    min-height: 80px;
}
.input-with-icon {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}
.form-input-icon {
    padding-left: 3rem;
}
.input-with-icon .input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
}
.form-textarea.form-input-icon {
    padding-left: 3rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
}
.submit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}
.cancel-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: white;
    color: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.3);
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
}
.cancel-btn:hover {
    background: rgba(148, 163, 184, 0.05);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 1024px) {
    .page-container {
        padding: 1rem;
    }
    .fields-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 768px) {
    .header-card {
        padding: 1.5rem;
    }
    .header-content {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-left {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-btn {
        width: 100%;
        justify-content: center;
    }
    .form-section {
        padding: 1.5rem;
    }
    .section-header {
        flex-direction: column;
    }
    .form-actions {
        flex-direction: column;
    }
    .submit-btn,
    .cancel-btn {
        width: 100%;
    }
    .toast {
        top: 1rem;
        right: 1rem;
        left: 1rem;
    }
}
</style>
