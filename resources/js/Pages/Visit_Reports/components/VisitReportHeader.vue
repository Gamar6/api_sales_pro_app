<script setup>
defineProps({
    statistics: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits([
    "export",
    "audit",
]);
</script>

<template>
    <div
        class="flex flex-col md:flex-row md:items-end justify-between gap-space-base"
    >
        <div class="flex flex-col gap-space-2xs">
            <div
                class="flex items-center gap-space-xs text-on-surface-variant"
            >
                <span
                    class="font-label-caps text-label-caps uppercase tracking-wider text-secondary"
                >
                    Operations Command
                </span>

            <span class="text-[12px] opacity-40">
                /
            </span>

            <span
                class="font-label-caps text-label-caps uppercase tracking-wider text-primary"
            >
                Field Surveillance
            </span>
        </div>

        <div class="flex items-center gap-space-sm flex-wrap">
            <h1
                class="font-headline-lg text-headline-lg text-primary tracking-tight"
            >
                Visit Reports
            </h1>

            <span
                class="px-2 py-0.5 rounded-full bg-surface-container-high text-primary font-code-metric text-code-metric"
            >
                {{ statistics.total_visits ?? 0 }} Visits
            </span>
        </div>
    </div>

    <div
        class="flex items-center gap-space-sm self-start md:self-auto"
    >
        <button
            class="h-9 px-space-base rounded bg-surface-container-lowest text-primary shadow-sm hover:bg-surface-container transition-colors flex items-center gap-space-xs font-title-md text-title-md"
            @click="$emit('export')"
        >
            <span class="material-symbols-outlined text-[18px]">
                file_download
            </span>

            <span>
                Export
            </span>
        </button>

        <button
            class="h-9 px-space-base rounded bg-primary-container text-on-primary shadow-md hover:bg-primary transition-all flex items-center gap-space-xs font-title-md text-title-md"
            @click="$emit('audit')"
        >
            <span class="material-symbols-outlined text-[18px]">
                assessment
            </span>

            <span>
                Generate Report
            </span>
        </button>
    </div>
</div>

</template>
