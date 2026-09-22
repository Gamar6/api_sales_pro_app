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
    kpiConfig,
    modules,
    products,
    stores as staticStores,
    telemetryFeed,
} from "./dashboardData";

/*
|--------------------------------------------------------------------------
| Props dari Laravel / Inertia
|--------------------------------------------------------------------------
*/

const props = defineProps({
    dashboardStats: {
        type: Object,
        required: true,
    },

    /*
    |--------------------------------------------------------------------------
    | Dynamic area order performance
    |--------------------------------------------------------------------------
    |
    | Dikirim dari DashboardController:
    |
    | [
    |     {
    |         name,
    |         reps,
    |         visited,
    |         unique_orders,
    |         order_events,
    |         total,
    |         percentage
    |     }
    | ]
    |
    */

    stores: {
        type: Array,
        default: () => [],
    },

    /*
    |--------------------------------------------------------------------------
    | Dynamic sales order performance
    |--------------------------------------------------------------------------
    */

    salesOrderChart: {
        type: Array,
        default: () => [],
    },

    outsideRadiusAlerts: {
        type: Array,
        default: () => [],
    },
});

const activeModule = ref("dashboard");

/*
|--------------------------------------------------------------------------
| KPI
|--------------------------------------------------------------------------
*/

const kpis = computed(() => {
    const stats = props.dashboardStats;

    /*
    |--------------------------------------------------------------------------
    | Today's Check-ins
    |--------------------------------------------------------------------------
    */

    const totalCheckIns = Number(stats.totalCheckInsToday ?? 0);

    const activeSalesCount = Number(stats.activeSalesCount ?? 0);

    const targetPerSales = Number(stats.checkInTargetPerSales ?? 5);

    const targetToday = Number(
        stats.checkInTargetToday ?? activeSalesCount * targetPerSales,
    );

    const progress =
        targetToday > 0
            ? Math.min((totalCheckIns / targetToday) * 100, 100)
            : 0;

    const remaining = Math.max(targetToday - totalCheckIns, 0);

    /*
    |--------------------------------------------------------------------------
    | Check-in Status
    |--------------------------------------------------------------------------
    */

    const checkInStatus =
        targetToday === 0
            ? "No active sales"
            : totalCheckIns >= targetToday
              ? "Shift target achieved"
              : `${remaining} visits to target`;

    /*
    |--------------------------------------------------------------------------
    | Average Visit Duration
    |--------------------------------------------------------------------------
    */

    const averageVisitDuration = stats.averageVisitDuration ?? "0m 00s";

    return [
        {
            key: "check-ins",

            label: kpiConfig.checkIns.label,

            value: totalCheckIns.toLocaleString(),

            suffix: kpiConfig.checkIns.suffix,

            progress: Number(progress.toFixed(1)),

            badge:
                targetToday > 0
                    ? `SHIFT GOAL: ${targetToday}`
                    : "NO ACTIVE SALES",

            context:
                targetToday > 0
                    ? `${activeSalesCount} active sales · ${targetPerSales} visits each`
                    : "No active sales available",

            detail: checkInStatus,

            status:
                targetToday === 0
                    ? "neutral"
                    : totalCheckIns >= targetToday
                      ? "success"
                      : "progress",
        },

        {
            key: "visit-duration",

            label: kpiConfig.visitDuration.label,

            value: averageVisitDuration,

            suffix: kpiConfig.visitDuration.suffix,
        },
    ];
});

console.log("dashboardStats:", props.dashboardStats);
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-background text-on-surface">
            <main class="w-full">
                <div class="flex w-full flex-col">
                    <div class="flex flex-col gap-space-xl p-space-xl">
                        <!-- ================================================= -->
                        <!-- HEADER -->
                        <!-- ================================================= -->

                        <DashboardHeader />

                        <!-- ================================================= -->
                        <!-- KPI -->
                        <!-- ================================================= -->

                        <DashboardKpiGrid :kpis="kpis" />

                        <!-- ================================================= -->
                        <!-- MODULE TABS -->
                        <!-- ================================================= -->

                        <DashboardModuleTabs
                            v-model:active-module="activeModule"
                            :modules="modules"
                        />

                        <!-- ================================================= -->
                        <!-- DASHBOARD OVERVIEW -->
                        <!-- ================================================= -->

                        <DashboardOverview
                            v-if="activeModule === 'dashboard'"
                            :dashboard-stats="props.dashboardStats"
                            :stores="props.stores"
                            :telemetry="telemetryFeed"
                            :sales-order-chart="props.salesOrderChart"
                            :outside-radius-alerts="outsideRadiusAlerts"
                        />

                        <!-- ================================================= -->
                        <!-- OTHER MODULES -->
                        <!-- ================================================= -->

                        <DashboardModulePanels
                            v-else
                            :active-module="activeModule"
                            :products="products"
                            :stores="staticStores"
                        />
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
