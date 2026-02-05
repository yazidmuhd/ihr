<script setup>
import StaffLayout from "@/Components/Layouts/StaffLayout.vue";
import FormField from "@/Components/App/FormField.vue";
import { ref } from "vue";

const form = ref({
    candidate: "",
    role: "",
    date: "",
    time: "",
    mode: "Online",
    note: "",
});

function schedule() {
    alert("Interview scheduled & notification sent (UI only).");
}
</script>

<template>
    <StaffLayout>
        <template #header>
            <h1 class="page-title">Manage Interview Session</h1>
            <p class="page-sub">
                Scheduling sends a notification to the candidate.
            </p>
        </template>

        <form @submit.prevent="schedule" class="form">
            <section class="form-section">
                <div class="form-grid">
                    <FormField
                        label="Candidate"
                        :required="true"
                        help="Type or paste the candidate’s name."
                    >
                        <input
                            v-model="form.candidate"
                            class="field"
                            placeholder="e.g., Aisyah Binti Ali"
                        />
                    </FormField>

                    <FormField label="Role" :required="true">
                        <input
                            v-model="form.role"
                            class="field"
                            placeholder="Frontend Engineer"
                        />
                    </FormField>

                    <FormField label="Date" :required="true">
                        <input type="date" v-model="form.date" class="field" />
                    </FormField>

                    <FormField label="Time" :required="true">
                        <input type="time" v-model="form.time" class="field" />
                    </FormField>

                    <FormField label="Mode" :required="true">
                        <select v-model="form.mode" class="field">
                            <option>Online</option>
                            <option>Onsite</option>
                        </select>
                    </FormField>

                    <div class="md:col-span-2">
                        <FormField
                            label="Notes"
                            help="Optional: add agenda, panel names, or meeting link if Online."
                        >
                            <textarea
                                v-model="form.note"
                                rows="4"
                                class="field"
                                placeholder="Panel: Rahman, Siti · Bring portfolio · Google Meet link…"
                            ></textarea>
                        </FormField>
                    </div>
                </div>
            </section>

            <div class="form-actions">
                <button class="btn-primary">Schedule & Notify</button>
            </div>
        </form>
    </StaffLayout>
</template>
