<script setup>
import AppLayout from "@/Components/App/AppLayout.vue";
import { ref } from "vue";
const notices = ref([
    {
        id: 1,
        title: "Interview for Frontend Engineer",
        time: "2025-10-13 10:00",
        mode: "Online (Google Meet)",
        confirmed: false,
    },
    {
        id: 2,
        title: "Interview for HR Generalist",
        time: "2025-10-15 14:00",
        mode: "Onsite (HQ)",
        confirmed: true,
    },
]);
function confirm(n) {
    n.confirmed = true;
    alert("Interview confirmed (UI only).");
}
</script>

<template>
    <AppLayout>
        <template #header
            ><h1 class="page-title">Interview Notifications</h1></template
        >

        <div class="space-y-3">
            <div
                v-for="n in notices"
                :key="n.id"
                class="card flex items-center justify-between"
            >
                <div>
                    <div class="font-medium">{{ n.title }}</div>
                    <div class="muted">{{ n.time }} • {{ n.mode }}</div>
                </div>
                <button
                    v-if="!n.confirmed"
                    class="btn-primary"
                    @click="confirm(n)"
                >
                    Confirm
                </button>
                <span v-else class="badge-ok">Confirmed</span>
            </div>
        </div>
    </AppLayout>
</template>
