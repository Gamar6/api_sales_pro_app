<script setup>
import { ref } from "vue";

defineProps({
    clusters: { type: Array, required: true },
    telemetry: { type: Array, required: true },
});
const alertVisible = ref(true);
</script>

<template>
    <section class="flex flex-col gap-space-xl">
        <div class="grid grid-cols-1 gap-space-lg lg:grid-cols-3">
            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm lg:col-span-2"
            >
                <div
                    class="flex flex-col justify-between gap-2 pb-space-base sm:flex-row sm:items-center"
                >
                    <div>
                        <h2
                            class="font-headline-sm text-headline-sm font-semibold text-primary"
                        >
                            Hourly Check-In Velocity Curve
                        </h2>
                        <p class="font-body-sm text-body-sm text-secondary">
                            Hourly aggregation of geofence check-ins vs
                            projected route schedules
                        </p>
                    </div>
                    <span
                        class="rounded bg-surface-container px-2 py-0.5 font-label-caps text-label-caps text-primary"
                        >Shift: 08:00 - 17:00</span
                    >
                </div>
                <svg
                    class="h-56 w-full"
                    preserveAspectRatio="none"
                    viewBox="0 0 740 220"
                    role="img"
                    aria-label="Hourly check-in velocity chart"
                >
                    <line
                        v-for="y in [20, 65, 110, 155, 190]"
                        :key="y"
                        stroke="#e5eeff"
                        stroke-width="1"
                        x1="40"
                        x2="720"
                        :y1="y"
                        :y2="y"
                    />
                    <path
                        d="M 50 180 Q 90 150 115 130 T 180 90 T 245 55 T 310 65 T 375 42 T 440 48 T 505 78 T 570 115 T 635 160 T 700 185"
                        fill="none"
                        stroke="#1c467f"
                        stroke-width="3"
                    />
                    <circle
                        v-for="point in [
                            [50, 180],
                            [115, 130],
                            [180, 90],
                            [245, 55],
                            [310, 65],
                            [375, 42],
                            [440, 48],
                            [505, 78],
                            [570, 115],
                            [635, 160],
                            [700, 185],
                        ]"
                        :key="point[0]"
                        :cx="point[0]"
                        :cy="point[1]"
                        fill="#1c467f"
                        r="3.5"
                    />
                </svg>
                <div
                    class="grid grid-cols-1 gap-2 rounded bg-surface-container-low p-space-sm sm:grid-cols-3"
                >
                    <div>
                        <span
                            class="font-label-caps text-[10px] uppercase text-secondary"
                            >Current Velocity</span
                        ><strong
                            class="block font-title-md text-title-md text-primary"
                            >36 Visits/hr</strong
                        >
                    </div>
                    <div>
                        <span
                            class="font-label-caps text-[10px] uppercase text-secondary"
                            >Projected End-of-Shift</span
                        ><strong
                            class="block font-title-md text-title-md text-primary"
                            >364 Visits</strong
                        >
                    </div>
                    <div>
                        <span
                            class="font-label-caps text-[10px] uppercase text-secondary"
                            >Route Efficiency</span
                        ><strong
                            class="block font-title-md text-title-md text-primary"
                            >91.8% Adherence</strong
                        >
                    </div>
                </div>
            </article>
            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <h2
                        class="font-headline-sm text-headline-sm font-semibold text-primary"
                    >
                        Cluster Deployment
                    </h2>
                    <span class="material-symbols-outlined text-secondary"
                        >hub</span
                    >
                </div>
                <p class="pb-space-md font-body-sm text-body-sm text-secondary">
                    Live agent dispersal and device telemetry status
                </p>
                <div class="flex flex-col gap-space-sm">
                    <div
                        v-for="cluster in clusters"
                        :key="cluster.name"
                        class="rounded bg-surface-container-low p-space-sm"
                    >
                        <div class="flex justify-between">
                            <span
                                class="font-title-md text-title-md text-primary"
                                >{{ cluster.name }}</span
                            ><strong
                                class="font-code-metric text-primary-container"
                                >{{ cluster.reps }} Reps</strong
                            >
                        </div>
                        <div
                            class="flex justify-between text-xs text-secondary"
                        >
                            <span>Target: {{ cluster.target }} Visits</span
                            ><span class="text-emerald-600"
                                >{{ cluster.completed }} Completed ({{
                                    cluster.percentage
                                }}%)</span
                            >
                        </div>
                        <div
                            class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-container"
                        >
                            <div
                                class="h-full rounded-full bg-primary-container"
                                :style="{ width: `${cluster.percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
        <div
            v-if="alertVisible"
            class="flex flex-col justify-between gap-space-md rounded-lg bg-gradient-to-r from-orange-50 via-surface-container-lowest to-surface-container-low p-space-base shadow-sm md:flex-row md:items-center"
        >
            <div>
                <strong class="font-headline-sm text-headline-sm text-primary"
                    >Fast-Action Dispatch Alert</strong
                >
                <p class="font-body-sm text-body-sm text-secondary">
                    2 newly registered stores awaiting geofence approval within
                    1.2km of Elena Rostova.
                </p>
            </div>
            <button
                type="button"
                class="rounded bg-surface-container-lowest px-space-md py-2 font-title-md text-primary shadow-sm"
                @click="alertVisible = false"
            >
                Dismiss Flag
            </button>
        </div>
        <div
            class="overflow-hidden rounded-lg bg-surface-container-lowest shadow-sm"
        >
            <div class="p-space-lg">
                <h2
                    class="font-headline-sm text-headline-sm font-semibold text-primary"
                >
                    Real-Time Field Telemetry Feed
                </h2>
                <p class="font-body-sm text-body-sm text-secondary">
                    Live validation feed cross-referencing GPS pings against
                    Odoo ERP records
                </p>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="h-10 bg-primary font-label-caps text-label-caps text-on-primary"
                        >
                            <th class="px-space-base">Sales Agent</th>
                            <th class="px-space-base">Target Retail Store</th>
                            <th class="px-space-base">Timestamp</th>
                            <th class="px-space-base">Geofence Status</th>
                            <th class="px-space-base">Evidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in telemetry"
                            :key="item.id"
                            class="h-12 hover:bg-surface-container-low"
                        >
                            <td class="px-space-base">
                                <div class="flex items-center gap-space-sm">
                                    <img
                                        :src="item.avatar"
                                        :alt="item.name"
                                        class="h-7 w-7 rounded-full object-cover"
                                    />
                                    <div>
                                        <span
                                            class="block text-xs font-semibold text-primary"
                                            >{{ item.name }}</span
                                        ><span
                                            class="font-code-metric text-[10px] text-secondary"
                                            >{{ item.id }}</span
                                        >
                                    </div>
                                </div>
                            </td>
                            <td class="px-space-base">
                                <span
                                    class="block text-xs font-medium text-primary"
                                    >{{ item.store }}</span
                                ><span class="text-[11px] text-secondary">{{
                                    item.location
                                }}</span>
                            </td>
                            <td
                                class="px-space-base font-code-metric text-xs text-primary"
                            >
                                {{ item.time }}
                            </td>
                            <td class="px-space-base">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px]"
                                    :class="
                                        item.status === 'valid'
                                            ? 'bg-emerald-50 text-emerald-800'
                                            : 'bg-orange-50 text-tertiary-container'
                                    "
                                    >{{ item.proximity }}</span
                                >
                            </td>
                            <td class="px-space-base">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded bg-surface-container-low px-2 py-1 text-[11px] text-primary"
                                >
                                    <span
                                        class="material-symbols-outlined text-[14px]"
                                        >{{ item.evidenceIcon }}</span
                                    >{{ item.evidence }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
