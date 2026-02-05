<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import FormField from "@/Components/App/FormField.vue";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const id = usePage().props.id ?? null;
const form = ref({
    title: "",
    dept: "",
    type: "Full-time",
    location: "",
    description: "",
});
function save() {
    alert((id ? "Updated" : "Created") + " vacancy (UI only).");
}
</script>

<template>
    <StaffLayout>
        <template #header>
            <h1 class="page-title">
                {{ id ? "Edit Vacancy" : "Create Vacancy" }}
            </h1>
            <p class="page-sub">
                Define role details for applicants to view and apply.
            </p>
        </template>

        <form @submit.prevent="save" class="form">
            <section class="form-section">
                <div class="form-grid">
                    <FormField label="Title" :required="true">
                        <input
                            v-model="form.title"
                            class="field"
                            placeholder="Frontend Engineer"
                        />
                    </FormField>

                    <FormField label="Department" :required="true">
                        <input
                            v-model="form.dept"
                            class="field"
                            placeholder="IT"
                        />
                    </FormField>

                    <FormField label="Employment Type" :required="true">
                        <input
                            v-model="form.type"
                            class="field"
                            placeholder="Full-time / Contract / Internship"
                        />
                    </FormField>

                    <FormField label="Location" :required="true">
                        <input
                            v-model="form.location"
                            class="field"
                            placeholder="Kuala Lumpur / Remote"
                        />
                    </FormField>

                    <div class="md:col-span-2">
                        <FormField label="Description" :required="true">
                            <textarea
                                v-model="form.description"
                                rows="6"
                                class="field"
                                placeholder="Responsibilities, requirements, benefits..."
                            ></textarea>
                        </FormField>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button class="btn-primary">
                    {{ id ? "Save Changes" : "Create Vacancy" }}
                </button>
            </div>
        </form>
    </StaffLayout>
</template>
