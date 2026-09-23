<script setup>
defineProps({
    kpis: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <section
        class="grid grid-cols-1 gap-space-lg sm:grid-cols-2 xl:grid-cols-4"
    >
        <article
            v-for="kpi in kpis"
            :key="kpi.key ?? kpi.label"
            class="relative flex min-h-[190px] flex-col justify-between overflow-hidden rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
        >
            <!-- Top accent -->
            <div
                class="absolute left-0 right-0 top-0 h-1 bg-surface-container"
            ></div>

            <!-- Header -->
            <div class="flex items-center justify-between gap-space-sm">
                <span
                    class="font-label-caps text-label-caps uppercase tracking-wider text-secondary"
                >
                    {{ kpi.label }}
                </span>

                <span
                    v-if="kpi.badge"
                    class="rounded bg-surface-container-high px-2 py-0.5 font-label-caps text-label-caps font-semibold text-primary"
                >
                    {{ kpi.badge }}
                </span>
            </div>

            <!-- Main value -->
            <div class="mt-space-sm">
                <div class="flex items-baseline gap-space-xs">
                    <span
                        class="font-display-lg text-display-lg tracking-tight text-primary"
                    >
                        {{ kpi.value }}
                    </span>

                    <span
                        v-if="kpi.suffix"
                        class="font-title-md text-title-md text-secondary"
                    >
                        {{ kpi.suffix }}
                    </span>
                </div>

                <!-- Context -->
                <p
                    v-if="kpi.context"
                    class="mt-1 font-body-sm text-body-sm text-secondary"
                >
                    {{ kpi.context }}
                </p>
            </div>

            <!-- Progress -->
            <div
                v-if="kpi.progress !== undefined && kpi.progress !== null"
                class="mt-space-sm"
            >
                <div class="flex items-center gap-space-sm">
                    <div
                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-container"
                    >
                        <div
                            class="h-1.5 rounded-full bg-primary-container transition-all duration-500"
                            :style="{
                                width: `${kpi.progress}%`,
                            }"
                        ></div>
                    </div>

                    <span
                        class="font-code-metric text-code-metric font-semibold text-primary-container"
                    >
                        {{ kpi.progress }}%
                    </span>
                </div>
            </div>

            <!-- Detail -->
            <div
                v-if="kpi.detail"
                class="pt-space-sm font-body-sm text-body-sm"
            >
                <span
                    :class="{
                        'text-emerald-600': kpi.status === 'success',

                        'text-primary': kpi.status === 'progress',

                        'text-secondary': kpi.status === 'neutral',
                    }"
                >
                    {{ kpi.detail }}
                </span>
            </div>
        </article>
    </section>
</template>
