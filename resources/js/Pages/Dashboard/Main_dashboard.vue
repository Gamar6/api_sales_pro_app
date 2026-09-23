<script setup>
import { computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import DashboardHeader from "./components/DashboardHeader.vue";
import DashboardKpiGrid from "./components/DashboardKpiGrid.vue";
import DashboardQuickAccess from "./components/DashboardQuickAccess.vue";
import DashboardOverview from "./components/DashboardOverview.vue";

const props = defineProps({
    dashboardStats: {
        type: Object,
        required: true,
    },

    stores: {
        type: Array,
        default: () => [],
    },

    salesOrderChart: {
        type: Array,
        default: () => [],
    },

    outsideRadiusAlerts: {
        type: Array,
        default: () => [],
    },
});

/*
|--------------------------------------------------------------------------
| KPI
|--------------------------------------------------------------------------
*/

const kpis = computed(() => {
    const stats = props.dashboardStats ?? {};

    const totalCheckIns = Number(stats.totalCheckInsToday ?? 0);
    const activeSalesCount = Number(stats.activeSalesCount ?? 0);
    const targetPerSales = Number(stats.checkInTargetPerSales ?? 5);

    const targetToday = Number(
        stats.checkInTargetToday ??
            activeSalesCount * targetPerSales,
    );

    const progress =
        targetToday > 0
            ? Math.min((totalCheckIns / targetToday) * 100, 100)
            : 0;

    const remaining = Math.max(targetToday - totalCheckIns, 0);

    const averageVisitDuration =
        stats.averageVisitDuration ?? "0m 00s";

    return [
        {
            key: "check-ins",
            label: "Today's Check-ins",
            value: totalCheckIns.toLocaleString("id-ID"),
            suffix: "Visits",

            progress: Number(progress.toFixed(1)),

            badge:
                targetToday > 0
                    ? `SHIFT GOAL: ${targetToday}`
                    : "NO ACTIVE SALES",

            context:
                targetToday > 0
                    ? `${activeSalesCount} active sales · ${targetPerSales} visits each`
                    : "No active sales available",

            detail:
                targetToday === 0
                    ? "No active sales"
                    : totalCheckIns >= targetToday
                      ? "Shift target achieved"
                      : `${remaining} visits to target`,

            status:
                targetToday === 0
                    ? "neutral"
                    : totalCheckIns >= targetToday
                      ? "success"
                      : "progress",
        },

        {
            key: "visit-duration",
            label: "Avg. Visit Duration",
            value: averageVisitDuration,
            suffix: "/ store",

            context: "Based on completed visits today",

            status: "neutral",
        },

        {
            key: "active-sales",
            label: "Active Sales",
            value: activeSalesCount.toLocaleString("id-ID"),
            suffix: "Sales",

            context: "Sales with activity today",

            status: activeSalesCount > 0 ? "success" : "neutral",
        },

        {
            key: "ordered-stores",
            label: "Ordered Stores",
            value: Number(
                stats.uniqueOrderStores ?? 0,
            ).toLocaleString("id-ID"),
            suffix: "Stores",

            context:
                Number(stats.orderEvents ?? 0) > 0
                    ? `${Number(stats.orderEvents).toLocaleString("id-ID")} order activities`
                    : "No order activity yet",

            status:
                Number(stats.uniqueOrderStores ?? 0) > 0
                    ? "success"
                    : "neutral",
        },
    ];
});
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-background text-on-surface">
            <main class="w-full">
                <div class="flex w-full flex-col">
                    <div
                        class="flex flex-col gap-space-xl p-space-xl"
                    >
                        <!-- ================================================= -->
                        <!-- HEADER -->
                        <!-- ================================================= -->

                        <DashboardHeader />

                        <!-- ================================================= -->
                        <!-- KPI -->
                        <!-- ================================================= -->

                        <DashboardKpiGrid :kpis="kpis" />

                        <!-- ================================================= -->
                        <!-- QUICK ACCESS -->
                        <!-- ================================================= -->

                        <DashboardQuickAccess />

                        <!-- ================================================= -->
                        <!-- OPERATIONAL OVERVIEW -->
                        <!-- ================================================= -->

                        <DashboardOverview
                            :stores="props.stores"
                            :sales-order-chart="props.salesOrderChart"
                            :outside-radius-alerts="
                                props.outsideRadiusAlerts
                            "
                        />
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
