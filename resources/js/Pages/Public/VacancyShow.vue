<script setup>
import ApplicantLayout from "@/Components/Layouts/ApplicantLayout.vue";
import { Link, usePage } from "@inertiajs/vue3";
const id = usePage().props.id ?? 0;
const vacancy = {
    id,
    title: "Frontend Engineer",
    dept: "IT",
    type: "Full-time",
    location: "Kuala Lumpur",
    desc: "Build UI in Vue. Work on career portal.",
};
</script>

<template>
    <ApplicantLayout>
        <template #header
            ><h1 class="page-title">{{ vacancy.title }}</h1></template
        >
        <p class="muted">
            {{ vacancy.dept }} • {{ vacancy.type }} • {{ vacancy.location }}
        </p>
        <p class="mt-3">{{ vacancy.desc }}</p>
        <div class="pt-4">
            <Link :href="`/applicant/apply/${vacancy.id}`" class="btn-primary"
                >Apply</Link
            >
        </div>
    </ApplicantLayout>
</template>
