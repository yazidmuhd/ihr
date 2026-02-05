<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import confetti from "canvas-confetti";
import { router } from "@inertiajs/vue3";

const open = ref(false);
const items = ref([]);
const unread = ref(0);
const toast = ref(null);

let timer = null;
let booted = false; // ✅ prevent “first load” animations
const seen = new Set();

async function fetchNotifications() {
    const { data } = await axios.get("/notifications/feed"); // ✅ match web.php

    unread.value = data.unread_count || 0;
    items.value = data.items || [];

    // ✅ first load: mark all as seen, no animations
    if (!booted) {
        for (const n of items.value) seen.add(n.id);
        booted = true;
        return;
    }

    // ✅ later polls: animate only truly new items
    for (const n of items.value) {
        if (seen.has(n.id)) continue;
        seen.add(n.id);

        if (n.read_at) continue;

        const kind = n?.data?.kind;

        // 🔥 urgent toast examples (adjust to your kinds)
        if (kind === "interview_invited" || kind === "interview_reminder") {
            showUrgentToast(n);
        }

        // 🎉 confetti examples (adjust to your kinds)
        if (
            kind === "interview_outcome" &&
            n?.data?.meta?.outcome === "hired"
        ) {
            doConfetti(n);
        }
        if (kind === "employee_converted" || kind === "hired") {
            doConfetti(n);
        }
    }
}

async function markAllRead() {
    await axios.post("/notifications/read-all");
    await fetchNotifications();
}

async function markRead(id) {
    try {
        await axios.post(`/notifications/${id}/read`);
    } catch (e) {
        // ignore
    }
}

function toggleOpen() {
    open.value = !open.value;
    if (open.value) markAllRead(); // your requirement: clear count when open
}

function showUrgentToast(n) {
    toast.value = {
        title: n.data?.title || "Interview",
        message: n.data?.message || "",
        url: n.data?.url || "/app/interviews",
    };
    setTimeout(() => (toast.value = null), 6500);
}

function doConfetti() {
    confetti({ particleCount: 160, spread: 90, origin: { y: 0.7 } });
    toast.value = {
        title: "Congratulations 🎉",
        message: "You’ve been hired!",
        url: "/applicant/dashboard",
    };
    setTimeout(() => (toast.value = null), 6500);
}

function goTo(n) {
    const url = n?.data?.url || "#";
    if (url === "#") return;

    // mark read (safe) then navigate via inertia
    markRead(n.id);
    open.value = false;
    router.visit(url);
}

onMounted(async () => {
    await fetchNotifications();
    timer = setInterval(fetchNotifications, 15000);
});

onBeforeUnmount(() => timer && clearInterval(timer));
</script>

<template>
    <!-- urgent toast -->
    <transition name="toast">
        <div
            v-if="toast"
            class="fixed bottom-24 right-6 z-[9999] w-[320px] rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-200"
        >
            <div class="text-sm font-semibold text-slate-900">
                {{ toast.title }}
            </div>
            <div class="mt-1 text-xs text-slate-600">{{ toast.message }}</div>
            <button
                type="button"
                class="mt-2 inline-block text-xs font-semibold text-emerald-700 hover:underline"
                @click="router.visit(toast.url)"
            >
                Open
            </button>
        </div>
    </transition>

    <!-- floating bubble -->
    <button
        @click="toggleOpen"
        class="floaty fixed bottom-6 right-6 z-[9999] flex h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-white shadow-xl ring-1 ring-white/30"
        :class="{ pulse: unread > 0 }"
        title="Notifications"
        type="button"
    >
        <!-- icon -->
        <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 01-6 0"
            />
        </svg>

        <!-- unread badge -->
        <span
            v-if="unread > 0"
            class="absolute -right-1 -top-1 flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold"
        >
            {{ unread }}
        </span>
    </button>

    <!-- panel -->
    <transition name="panel">
        <div
            v-if="open"
            class="fixed bottom-24 right-6 z-[9999] max-h-[520px] w-[360px] overflow-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 p-4"
            >
                <div class="text-sm font-semibold text-slate-900">
                    Notifications
                </div>
                <button
                    class="text-xs text-slate-500 hover:underline"
                    @click="open = false"
                    type="button"
                >
                    Close
                </button>
            </div>

            <div v-if="!items.length" class="p-6 text-sm text-slate-500">
                No notifications yet.
            </div>

            <button
                v-for="n in items"
                :key="n.id"
                type="button"
                @click="goTo(n)"
                class="block w-full border-b border-slate-100 px-4 py-3 text-left hover:bg-slate-50"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-xs font-semibold text-slate-900">
                            {{ n.data?.title || "Notification" }}
                        </div>
                        <div class="mt-1 text-xs text-slate-600">
                            {{ n.data?.message || "" }}
                        </div>
                    </div>
                    <span
                        v-if="!n.read_at"
                        class="mt-1 inline-block h-2 w-2 rounded-full bg-emerald-500"
                        title="Unread"
                    ></span>
                </div>
            </button>
        </div>
    </transition>
</template>

<style scoped>
.floaty {
    animation: floaty 2.8s ease-in-out infinite;
}
@keyframes floaty {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

.pulse {
    animation: pulse 1.4s ease-in-out infinite;
}
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.06);
    }
    100% {
        transform: scale(1);
    }
}

.toast-enter-active,
.toast-leave-active {
    transition: all 0.22s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(10px) scale(0.98);
}

.panel-enter-active,
.panel-leave-active {
    transition: all 0.18s ease;
}
.panel-enter-from,
.panel-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>
