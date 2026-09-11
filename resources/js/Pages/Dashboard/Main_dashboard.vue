<script setup>
import { computed, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import DashboardHeader from "./components/DashboardHeader.vue";
import DashboardKpiGrid from "./components/DashboardKpiGrid.vue";
import DashboardModuleTabs from "./components/DashboardModuleTabs.vue";
import DashboardOverview from "./components/DashboardOverview.vue";
import DashboardModulePanels from "./components/DashboardModulePanels.vue";

import {
    clusters,
    kpis as staticKpis,
    modules,
    products,
    stores,
    telemetryFeed,
} from "./dashboardData";

const props = defineProps({
    dashboardStats: {
        type: Object,
        required: true,
    },

    salesOrderChart: {
        type: Array,
        default: () => [],
    },
});

const activeModule = ref("dashboard");

const SHIFT_GOAL = 10;

const kpis = computed(() => {
    const totalCheckIns = props.dashboardStats.totalCheckInsToday ?? 0;

    const progress = Math.min((totalCheckIns / SHIFT_GOAL) * 100, 100);

    const remaining = Math.max(SHIFT_GOAL - totalCheckIns, 0);

    const averageVisitDuration =
        props.dashboardStats.averageVisitDuration ?? "0m 00s";

    return [
        {
            ...staticKpis[0],

            value: totalCheckIns.toLocaleString(),

            progress: Number(progress.toFixed(1)),

            badge: `SHIFT GOAL: ${SHIFT_GOAL}`,

            detail:
                remaining > 0
                    ? `${remaining} visits to target`
                    : "Shift target achieved",
        },

        {
            ...staticKpis[1],
            value: averageVisitDuration,
        },
    ];
});
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-background text-on-surface">
            <main class="w-full">
                <div class="flex w-full flex-col">
                    <div class="flex flex-col gap-space-xl p-space-xl">
                        <DashboardHeader />

                        <DashboardKpiGrid :kpis="kpis" />

                        <DashboardModuleTabs
                            v-model:active-module="activeModule"
                            :modules="modules"
                        />

                        <DashboardOverview
                            v-if="activeModule === 'dashboard'"
                            :clusters="clusters"
                            :telemetry="telemetryFeed"
                            :sales-order-chart="salesOrderChart"
                        />

                        <DashboardModulePanels
                            v-else
                            :active-module="activeModule"
                            :products="products"
                            :stores="stores"
                        />
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
