<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import FormField from "@/Components/App/FormField.vue";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const vacancyId = usePage().props.vacancyId;
const form = ref({ name: "", email: "", phone: "", cover: "", resume: null });
function submit() {
    alert(`Applied to vacancy #${vacancyId} (UI only).`);
}
</script>

<template>
    <ApplicantLayout>
        <template #header>
            <h1 class="page-title">Apply to Vacancy #{{ vacancyId }}</h1>
            <p class="page-sub">
                Fill in your details and upload your resume (PDF).
            </p>
        </template>

        <form @submit.prevent="submit" class="form">
            <section class="form-section">
                <div class="form-grid">
                    <FormField label="Full Name" :required="true">
                        <input
                            v-model="form.name"
                            class="field"
                            placeholder="e.g., Aisyah Binti Ali"
                        />
                    </FormField>

                    <FormField
                        label="Email"
                        :required="true"
                        help="We’ll send interview updates here."
                    >
                        <input
                            v-model="form.email"
                            type="email"
                            class="field"
                            placeholder="you@example.com"
                        />
                    </FormField>

                    <FormField label="Phone" :required="true">
                        <input
                            v-model="form.phone"
                            class="field"
                            placeholder="012-3456789"
                        />
                    </FormField>

                    <FormField
                        label="Resume (PDF)"
                        :required="true"
                        help="Max 5MB. PDF format only."
                    >
                        <input
                            type="file"
                            accept=".pdf"
                            class="field"
                            @change="
                                (e) =>
                                    (form.resume = e.target.files?.[0] ?? null)
                            "
                        />
                    </FormField>

                    <div class="md:col-span-2">
                        <FormField label="Cover Letter">
                            <textarea
                                v-model="form.cover"
                                rows="6"
                                class="field"
                                placeholder="Briefly explain your fit..."
                            ></textarea>
                        </FormField>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button
                    type="button"
                    class="btn-outline"
                    @click="form.resume = null"
                >
                    Remove Resume
                </button>
                <button type="submit" class="btn-primary">
                    Submit Application
                </button>
            </div>
        </form>
    </ApplicantLayout>
</template>
