<script setup>
defineProps({ kpis: { type: Array, required: true } });
</script>

<template>
    <section
        class="grid grid-cols-1 gap-space-lg sm:grid-cols-2 xl:grid-cols-4"
    >
        <article
            v-for="kpi in kpis"
            :key="kpi.label"
            class="relative flex flex-col justify-between overflow-hidden rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
        >
            <div
                class="absolute left-0 right-0 top-0 h-1 bg-surface-container"
            ></div>
            <div class="flex items-center justify-between gap-2 pb-space-xs">
                <span
                    class="font-label-caps text-label-caps uppercase tracking-wider text-secondary"
                    >{{ kpi.label }}</span
                ><span
                    class="rounded bg-surface-container-high px-2 py-0.5 font-label-caps text-label-caps font-semibold text-primary"
                    >{{ kpi.badge }}</span
                >
            </div>
            <div class="my-space-xs">
                <div class="flex items-baseline gap-space-xs">
                    <span
                        class="font-display-lg text-display-lg tracking-tight text-primary"
                        >{{ kpi.value }}</span
                    ><span class="font-title-md text-title-md text-secondary">{{
                        kpi.suffix
                    }}</span>
                </div>
                <div v-if="kpi.progress" class="mt-1 flex items-center gap-2">
                    <div
                        class="h-1.5 w-full overflow-hidden rounded-full bg-surface-container"
                    >
                        <div
                            class="h-1.5 rounded-full bg-primary-container"
                            :style="{ width: `${kpi.progress}%` }"
                        ></div>
                    </div>
                    <span
                        class="font-code-metric text-code-metric font-semibold text-primary-container"
                        >{{ kpi.progress }}%</span
                    >
                </div>
                <div v-else class="mt-1 flex items-center gap-2 text-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span
                    ><span class="text-emerald-600">{{ kpi.trend }}</span>
                </div>
            </div>
            <div
                class="flex items-center justify-between pt-space-xs font-body-sm text-body-sm text-secondary"
            >
                <span class="font-medium text-emerald-600">{{ kpi.trend }}</span
                ><span class="font-code-metric text-secondary">{{
                    kpi.detail
                }}</span>
            </div>
        </article>
    </section>
</template>
