<script setup>
import AppLayout from "@/Components/App/AppLayout.vue";
const rows = [
    {
        id: 101,
        title: "Frontend Engineer",
        status: "Screening",
        updated: "2025-10-09",
    },
    {
        id: 102,
        title: "HR Generalist",
        status: "Interview Scheduled",
        updated: "2025-10-10",
    },
];
</script>

<template>
    <AppLayout>
        <template #header><h1 class="page-title">My Applications</h1></template>

        <ul class="divide-y">
            <li
                v-for="a in rows"
                :key="a.id"
                class="flex items-center justify-between py-3"
            >
                <div>
                    <div class="font-medium">{{ a.title }}</div>
                    <div class="meta">Updated {{ a.updated }}</div>
                </div>
                <span class="badge-muted">{{ a.status }}</span>
            </li>
        </ul>
    </AppLayout>
</template>
