<script setup>
import { ref } from "vue";

const emit = defineEmits(["file"]);
const dragging = ref(false);

function onDrop(e) {
    dragging.value = false;
    const f = e.dataTransfer.files?.[0];
    if (f) emit("file", f);
}
function onChange(e) {
    const f = e.target.files?.[0];
    if (f) emit("file", f);
}
</script>

<template>
    <div
        class="group relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-300/70 bg-white/70 p-8 text-center ring-1 ring-white/40 backdrop-blur transition hover:bg-white"
        :class="dragging ? 'shadow-xl' : 'shadow-sm'"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="onDrop"
        @click="$refs.in.click()"
    >
        <div class="mb-1 text-sm font-medium text-emerald-800">
            Drop your resume here
        </div>
        <div class="text-xs text-slate-600">PDF, DOC, DOCX — up to 5 MB</div>
        <input
            ref="in"
            type="file"
            class="hidden"
            @change="onChange"
            accept=".pdf,.doc,.docx"
        />
        <div
            class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100"
            style="
                background: radial-gradient(
                    600px 240px at 50% -20%,
                    rgba(16, 185, 129, 0.08),
                    transparent 70%
                );
            "
        ></div>
    </div>
</template>
