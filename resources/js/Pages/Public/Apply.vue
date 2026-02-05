<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import InputText from "@/Components/App/InputText.vue";
import InputTextArea from "@/Components/App/InputTextArea.vue";
import InputFile from "@/Components/App/InputFile.vue";
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
        <template #header
            ><h1 class="page-title">
                Apply to Vacancy #{{ vacancyId }}
            </h1></template
        >

        <form @submit.prevent="submit" class="form-row">
            <label class="label">Full Name</label>
            <input class="field" v-model="form.name" />
            <label class="label">Email</label>
            <input class="field" type="email" v-model="form.email" />
            <label class="label">Phone</label>
            <input class="field" v-model="form.phone" />
            <label class="label">Resume (PDF)</label>
            <input
                class="field"
                type="file"
                accept=".pdf"
                @change="(e) => (form.resume = e.target.files?.[0] ?? null)"
            />
            <div class="md:col-span-2">
                <label class="label">Cover Letter</label>
                <textarea
                    class="field"
                    rows="6"
                    v-model="form.cover"
                ></textarea>
            </div>
            <div class="flex justify-end gap-3 md:col-span-2">
                <button
                    type="button"
                    class="btn-outline"
                    @click="form.resume = null"
                >
                    Delete Resume
                </button>
                <button type="submit" class="btn-primary">Submit</button>
            </div>
        </form>
    </ApplicantLayout>
</template>
