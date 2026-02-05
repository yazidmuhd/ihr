<script setup>
defineProps({ title: String, desc: String, actions: Boolean });
</script>

<template>
    <section class="form-section">
        <div class="form-header" v-if="title || desc || $slots.actions">
            <div>
                <h2 v-if="title" class="form-title">{{ title }}</h2>
                <p v-if="desc" class="form-desc">{{ desc }}</p>
            </div>
            <div v-if="$slots.actions"><slot name="actions" /></div>
        </div>
        <slot />
    </section>
</template>
