<script setup>
defineProps({ columns: Array, rows: Array, emptyText: { default: "No data" } });
</script>

<template>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th
                        v-for="c in columns"
                        :key="c.key"
                        class="px-4 py-2 text-left font-medium text-slate-600"
                    >
                        {{ c.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!rows || !rows.length">
                    <td
                        :colspan="columns.length"
                        class="px-4 py-6 text-center text-slate-500"
                    >
                        {{ emptyText }}
                    </td>
                </tr>
                <tr v-for="(r, i) in rows" :key="i" class="border-t">
                    <td v-for="c in columns" :key="c.key" class="px-4 py-3">
                        <slot :name="`cell:${c.key}`" :row="r">{{
                            r[c.key]
                        }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
