<script setup>
import { computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";

import DashboardHeader from "./components/DashboardHeader.vue";
import DashboardKpiGrid from "./components/DashboardKpiGrid.vue";
import DashboardQuickAccess from "./components/DashboardQuickAccess.vue";
import DashboardOverview from "./components/DashboardOverview.vue";

import {
    clusters,
    kpiConfig,
    modules,
    products,
    stores as staticStores,
    telemetryFeed,
} from "./dashboardData";

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
| KPI & Metrics
|--------------------------------------------------------------------------
*/

const kpis = computed(() => {
    const stats = props.dashboardStats;

    // Computed store metrics derived from dashboardStats
    const totalStores = Number(stats.totalSnapshotStores ?? 0);
    const orderedStores = Number(stats.uniqueOrderStores ?? 0);
    const orderEvents = Number(stats.orderEvents ?? 0);

    const orderedStorePercentage =
        totalStores > 0
            ? ((orderedStores / totalStores) * 100).toFixed(1)
            : "0.0";

    const totalCheckIns = Number(stats.totalCheckInsToday ?? 0);
    const activeSalesCount = Number(stats.activeSalesCount ?? 0);
    const targetPerSales = Number(stats.checkInTargetPerSales ?? 5);
    const targetToday = Number(
        stats.checkInTargetToday ?? activeSalesCount * targetPerSales,
    );
    const averageVisitDuration = stats.averageVisitDuration ?? "0m 00s";

    // ─────────────────────────────
    // Check-in
    // ─────────────────────────────

    const checkInProgress =
        targetToday > 0
            ? Math.min((totalCheckIns / targetToday) * 100, 100)
            : 0;

    const remainingCheckIns = Math.max(targetToday - totalCheckIns, 0);

    const checkInDetail =
        targetToday === 0
            ? "No active sales available"
            : totalCheckIns >= targetToday
              ? "Shift target achieved"
              : `${remainingCheckIns} visits to target`;

    // ─────────────────────────────
    // Visit duration
    // ─────────────────────────────

    const hasCompletedVisits = averageVisitDuration !== "0m 00s";

    const visitDurationDetail = hasCompletedVisits
        ? "Within expected range"
        : "No completed visits today";

    return [
        {
            key: "check-ins",
            label: kpiConfig.checkIns.label,
            value: totalCheckIns.toLocaleString(),
            suffix: kpiConfig.checkIns.suffix,
            progress: Number(checkInProgress.toFixed(1)),
            badge:
                targetToday > 0
                    ? `SHIFT GOAL: ${targetToday}`
                    : "NO ACTIVE SALES",
            context:
                targetToday > 0
                    ? `${activeSalesCount} active sales · ${targetPerSales} visits each`
                    : "No active sales available",
            detail: checkInDetail,
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
            badge: `GUIDE: ${kpiConfig.visitDuration.guide}`,
            context: visitDurationDetail,
            detail: hasCompletedVisits
                ? "Based on completed visits today"
                : "Completed check-outs will appear here",
            status: hasCompletedVisits ? "success" : "neutral",
        },

        {
            key: "active-sales",
            label: kpiConfig.activeSales.label,
            value: activeSalesCount.toLocaleString(),
            suffix: kpiConfig.activeSales.suffix,
            context:
                activeSalesCount > 0
                    ? `${activeSalesCount} active sales currently available`
                    : "No active sales currently available",
            detail:
                activeSalesCount > 0
                    ? `Daily capacity: ${(
                          activeSalesCount * targetPerSales
                      ).toLocaleString()} visits`
                    : "Daily visit capacity: 0",
            status: activeSalesCount > 0 ? "success" : "neutral",
        },

        {
            key: "ordered-stores",
            label: kpiConfig.orderedStores.label,
            value: orderedStores.toLocaleString(),
            suffix: kpiConfig.orderedStores.suffix,
            context:
                totalStores > 0
                    ? `${orderedStores.toLocaleString()} / ${totalStores.toLocaleString()} stores ordered`
                    : "No stores available",
            detail: `${orderedStorePercentage}% store coverage · ${orderEvents.toLocaleString()} order events`,
            status: orderedStores > 0 ? "success" : "neutral",
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
                            :outside-radius-alerts="props.outsideRadiusAlerts"
                        />
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
