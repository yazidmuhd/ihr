<script setup>
import TopNav from "./TopNav.vue";
import SideNav from "./SideNav.vue";
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-slate-800">
        <TopNav />
        <div
            class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 md:grid-cols-12 lg:px-8"
        >
            <aside class="hidden md:col-span-3 md:block lg:col-span-2">
                <SideNav />
            </aside>
            <main class="md:col-span-9 lg:col-span-10">
                <header v-if="$slots.header" class="mb-4">
                    <slot name="header" />
                </header>
                <section class="page">
                    <slot />
                </section>
            </main>
        </div>
    </div>
</template>
