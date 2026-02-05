<script setup>
import { computed } from "vue";

const props = defineProps({
    label: { type: String, default: "" },
    help: { type: String, default: "" },
    error: { type: String, default: "" },
    required: { type: Boolean, default: false },
    id: { type: String, default: "" }, // optional: pass your own id
});

// simple auto id for accessibility (used if no id prop given)
const uid = computed(
    () => props.id || `ff-${Math.random().toString(36).slice(2, 8)}`,
);
</script>

<template>
    <div class="form-row">
        <label
            v-if="label"
            class="label"
            :class="{ 'label-required': required }"
            :for="uid"
            >{{ label }}</label
        >

        <!-- expose generated id to child input via slot prop -->
        <slot :id="uid" />

        <p v-if="help" class="help">{{ help }}</p>
        <p v-if="error" class="error">{{ error }}</p>
    </div>
</template>
