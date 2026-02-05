<script setup>
defineProps({
    label: String,
    modelValue: [String, Number],
    type: { default: "text" },
    placeholder: String,
});
defineEmits(["update:modelValue"]);
</script>

<template>
    <label class="block">
        <span class="mb-1 block text-sm font-medium">{{ label }}</span>
        <input
            :type="type"
            :placeholder="placeholder"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full rounded-xl border-slate-300 focus:border-slate-500 focus:ring-0"
        />
    </label>
</template>
