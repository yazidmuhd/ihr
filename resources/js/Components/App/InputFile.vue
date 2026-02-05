<!-- resources/js/Components/App/InputFile.vue -->
<script setup>
const props = defineProps({
    modelValue: { type: [File, String, null], default: null }, // supports v-model
    accept: { type: String, default: "" }, // e.g. ".pdf,.doc,.docx"
    id: {
        type: String,
        default: () => "file-" + Math.random().toString(36).slice(2),
    },
    label: { type: String, default: "Upload file" },
    hint: { type: String, default: "" },
});
const emit = defineEmits(["update:modelValue", "change"]);

function onChange(e) {
    const file = e.target.files?.[0] ?? null;
    emit("update:modelValue", file);
    emit("change", file);
}
</script>

<template>
    <label :for="id" class="block">
        <span class="label mb-1">{{ label }}</span>

        <div
            class="flex items-center justify-between rounded-xl border-2 border-dashed border-slate-300 bg-white px-4 py-3"
        >
            <div class="truncate text-sm text-slate-600">
                <span v-if="modelValue && modelValue.name">{{
                    modelValue.name
                }}</span>
                <span
                    v-else-if="typeof modelValue === 'string' && modelValue"
                    >{{ modelValue }}</span
                >
                <span v-else class="text-slate-400">No file selected</span>
            </div>

            <button
                type="button"
                class="btn-outline btn-sm"
                @click="$refs.input.click()"
            >
                Choose
            </button>

            <input
                ref="input"
                :id="id"
                type="file"
                class="hidden"
                :accept="accept"
                @change="onChange"
            />
        </div>

        <p v-if="hint" class="help mt-1">{{ hint }}</p>
    </label>
</template>
