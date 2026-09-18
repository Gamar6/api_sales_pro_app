<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },

    telemetry: {
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

const alertVisible = ref(true);

/*
|--------------------------------------------------------------------------
| Sales Order Performance
|--------------------------------------------------------------------------
*/

const chartMaxValue = computed(() => {
    if (!props.salesOrderChart.length) {
        return 1;
    }

    const max = Math.max(
        ...props.salesOrderChart.map((item) => Number(item.orders || 0)),
    );

    return Math.max(max, 1);
});

const totalOrders = computed(() => {
    return props.salesOrderChart.reduce(
        (total, item) => total + Number(item.orders || 0),
        0,
    );
});

const topSales = computed(() => {
    if (!props.salesOrderChart.length) {
        return null;
    }

    return props.salesOrderChart.reduce((highest, current) => {
        return Number(current.orders || 0) > Number(highest.orders || 0)
            ? current
            : highest;
    });
});

const averageOrders = computed(() => {
    if (!props.salesOrderChart.length) {
        return "0.0";
    }

    return (totalOrders.value / props.salesOrderChart.length).toFixed(1);
});

const getBarHeight = (orders) => {
    const value = Number(orders || 0);

    if (!value || chartMaxValue.value === 0) {
        return 4;
    }

    const percentage = (value / chartMaxValue.value) * 100;

    return Math.max(percentage, 6);
};

/*
|--------------------------------------------------------------------------
| Area dengan Order Tertinggi
|--------------------------------------------------------------------------
*/

const sortedStores = computed(() => {
    return [...props.stores]
        .map((store) => ({
            ...store,
            reps: Number(store.reps || 0),
            visited: Number(store.visited || 0),
            total: Number(store.total || 0),
            percentage: Number(store.percentage || 0),
        }))
        .sort((a, b) => {
            if (b.percentage !== a.percentage) {
                return b.percentage - a.percentage;
            }

            return b.visited - a.visited;
        });
});

const topAreas = computed(() => {
    return sortedStores.value.slice(0, 5);
});

const totalAreaStores = computed(() => {
    return props.stores.reduce(
        (total, store) => total + Number(store.total || 0),
        0,
    );
});

const totalAreaOrders = computed(() => {
    return props.stores.reduce(
        (total, store) => total + Number(store.visited || 0),
        0,
    );
});

const overallAreaPercentage = computed(() => {
    if (!totalAreaStores.value) {
        return 0;
    }

    return Math.round((totalAreaOrders.value / totalAreaStores.value) * 100);
});

/*
|--------------------------------------------------------------------------
| Telemetry
|--------------------------------------------------------------------------
*/

const telemetryItems = computed(() => {
    return props.telemetry.slice(0, 6);
});

const getTelemetryStatusClass = (status) => {
    const normalized = String(status || "").toLowerCase();

    if (
        normalized.includes("success") ||
        normalized.includes("active") ||
        normalized.includes("online") ||
        normalized.includes("completed")
    ) {
        return "bg-emerald-500";
    }

    if (normalized.includes("warning") || normalized.includes("pending")) {
        return "bg-amber-500";
    }

    if (
        normalized.includes("error") ||
        normalized.includes("failed") ||
        normalized.includes("offline")
    ) {
        return "bg-red-500";
    }

    return "bg-slate-400";
};

const outsideRadiusCount = computed(() => {
    return props.outsideRadiusAlerts.length;
});

const formatDistance = (distance) => {
    const value = Number(distance);

    if (!Number.isFinite(value)) {
        return "-";
    }

    if (value >= 1000) {
        return `${(value / 1000).toFixed(2)} km`;
    }

    return `${Math.round(value)} m`;
};

const formatAlertDate = (date) => {
    if (!date) return "-";

    return new Date(date.replace(" ", "T")).toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
};
</script>

<template>
    <section class="flex flex-col gap-space-xl">
        <!-- ============================================================= -->
        <!-- SALES ORDER + AREA PERFORMANCE -->
        <!-- ============================================================= -->

        <div class="grid grid-cols-1 gap-space-lg lg:grid-cols-3">
            <!-- ========================================================= -->
            <!-- SALES ORDER PERFORMANCE -->
            <!-- ========================================================= -->

            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm lg:col-span-2"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                        >
                            Performance
                        </p>

                        <h2 class="mt-1 text-lg font-bold text-on-surface">
                            Sales Order Performance
                        </h2>

                        <p class="mt-1 text-sm text-on-surface-variant">
                            Distribusi order berdasarkan sales pada periode
                            terpilih.
                        </p>
                    </div>

                    <div
                        class="rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface-variant"
                    >
                        {{ props.salesOrderChart.length }} Sales
                    </div>
                </div>

                <!-- Chart -->

                <div
                    v-if="salesOrderChart.length"
                    class="mt-8 flex h-64 items-end gap-3 overflow-x-auto pb-2"
                >
                    <div
                        v-for="sales in salesOrderChart"
                        :key="sales.salesId"
                        class="flex min-w-[72px] flex-1 flex-col items-center justify-end gap-2"
                    >
                        <!-- Order value -->

                        <span class="text-xs font-semibold text-on-surface">
                            {{ sales.orders }}
                        </span>

                        <!-- Bar -->

                        <div
                            class="flex h-48 w-full items-end justify-center rounded-md bg-surface-container"
                        >
                            <div
                                class="w-8 rounded-t-md bg-primary transition-all duration-300"
                                :style="{
                                    height: `${getBarHeight(sales.orders)}%`,
                                }"
                            ></div>
                        </div>

                        <!-- Sales name -->

                        <span
                            class="max-w-[72px] truncate text-center text-xs text-on-surface-variant"
                            :title="sales.name"
                        >
                            {{ sales.name }}
                        </span>
                    </div>
                </div>

                <!-- Empty state -->

                <div
                    v-else
                    class="mt-8 flex h-64 items-center justify-center rounded-lg bg-surface-container"
                >
                    <div class="text-center">
                        <p class="text-sm font-semibold text-on-surface">
                            Belum ada data order
                        </p>

                        <p class="mt-1 text-xs text-on-surface-variant">
                            Data akan muncul setelah terdapat report dengan
                            aktivitas Order.
                        </p>
                    </div>
                </div>

                <!-- Summary -->

                <div
                    class="mt-6 grid grid-cols-3 divide-x divide-outline-variant rounded-lg bg-surface-container"
                >
                    <div class="px-4 py-3">
                        <p class="text-xs text-on-surface-variant">
                            Total Orders
                        </p>

                        <p class="mt-1 text-lg font-bold text-on-surface">
                            {{ totalOrders.toLocaleString() }}
                        </p>
                    </div>

                    <div class="px-4 py-3">
                        <p class="text-xs text-on-surface-variant">
                            Average / Sales
                        </p>

                        <p class="mt-1 text-lg font-bold text-on-surface">
                            {{ averageOrders }}
                        </p>
                    </div>

                    <div class="px-4 py-3">
                        <p class="text-xs text-on-surface-variant">Top Sales</p>

                        <p
                            class="mt-1 truncate text-lg font-bold text-on-surface"
                            :title="topSales?.name"
                        >
                            {{ topSales?.name || "-" }}
                        </p>
                    </div>
                </div>
            </article>

            <!-- ========================================================= -->
            <!-- AREA DENGAN ORDER TERTINGGI -->
            <!-- ========================================================= -->

            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                        >
                            Area
                        </p>

                        <h2 class="mt-1 text-lg font-bold text-on-surface">
                            Area dengan Order Tertinggi
                        </h2>

                        <p class="mt-1 text-sm text-on-surface-variant">
                            Berdasarkan toko yang sudah melakukan Order.
                        </p>
                    </div>
                </div>

                <!-- Overall -->

                <div class="mt-6 rounded-lg bg-surface-container p-4">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xs text-on-surface-variant">
                                Overall Coverage
                            </p>

                            <p class="mt-1 text-2xl font-bold text-on-surface">
                                {{ overallAreaPercentage }}%
                            </p>
                        </div>

                        <p class="text-xs text-on-surface-variant">
                            {{ totalAreaOrders.toLocaleString() }}
                            /
                            {{ totalAreaStores.toLocaleString() }}
                            toko
                        </p>
                    </div>

                    <div
                        class="mt-3 h-2 overflow-hidden rounded-full bg-surface-container-high"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all duration-500"
                            :style="{
                                width: `${Math.min(
                                    overallAreaPercentage,
                                    100,
                                )}%`,
                            }"
                        ></div>
                    </div>
                </div>

                <!-- Area list -->

                <div v-if="topAreas.length" class="mt-5 flex flex-col gap-4">
                    <div
                        v-for="(area, index) in topAreas"
                        :key="area.name || index"
                        class="group"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-container text-[10px] font-bold text-on-surface-variant"
                                    >
                                        {{ index + 1 }}
                                    </span>

                                    <p
                                        class="truncate text-sm font-semibold text-on-surface"
                                        :title="area.name"
                                    >
                                        {{ area.name || "Unknown Area" }}
                                    </p>
                                </div>

                                <p
                                    class="mt-1 pl-8 text-xs text-on-surface-variant"
                                >
                                    {{ area.visited }} order /
                                    {{ area.total }} toko
                                </p>
                            </div>

                            <span
                                class="shrink-0 text-sm font-bold text-on-surface"
                            >
                                {{ area.percentage }}%
                            </span>
                        </div>

                        <div
                            class="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-container"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-all duration-500"
                                :style="{
                                    width: `${Math.min(area.percentage, 100)}%`,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Empty -->

                <div
                    v-else
                    class="mt-6 rounded-lg bg-surface-container p-6 text-center"
                >
                    <p class="text-sm font-semibold text-on-surface">
                        Belum ada data area
                    </p>

                    <p class="mt-1 text-xs text-on-surface-variant">
                        Data area akan muncul setelah snapshot retensi tersedia.
                    </p>
                </div>
            </article>
        </div>

        <!-- ============================================================= -->
        <!-- OUTSIDE RADIUS ALERT -->
        <!-- ============================================================= -->

        <article
            v-if="outsideRadiusCount > 0"
            class="rounded-lg border border-amber-200 bg-amber-50 p-space-lg shadow-sm"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-200 text-amber-800"
                    >
                        <span class="material-symbols-outlined">
                            location_off
                        </span>
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-sm font-bold text-amber-900">
                                Kunjungan di Luar Radius
                            </h3>

                            <span
                                class="rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-bold text-amber-900"
                            >
                                {{ outsideRadiusCount }} laporan
                            </span>
                        </div>

                        <p class="mt-1 text-sm leading-6 text-amber-800">
                            Terdapat laporan kunjungan dengan jarak GPS melebihi
                            radius yang ditentukan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-5 divide-y divide-amber-200">
                <div
                    v-for="alert in outsideRadiusAlerts"
                    :key="alert.id"
                    class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-bold text-amber-950"
                            >
                                {{ alert.store.name }}
                            </p>

                            <p class="mt-0.5 text-xs text-amber-800">
                                {{ alert.store.city }}
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-full bg-amber-200 px-2 py-1 text-[11px] font-bold text-amber-900"
                        >
                            Outside Radius
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-1 text-xs text-amber-800 sm:grid-cols-2"
                    >
                        <span>
                            Sales:
                            <strong>{{ alert.sales.name }}</strong>
                        </span>

                        <span>
                            Jarak:
                            <strong>
                                {{ formatDistance(alert.distance_from_store) }}
                            </strong>
                        </span>

                        <span>
                            Akurasi:
                            <strong>
                                {{ alert.sales_accuracy ?? "-" }} m
                            </strong>
                        </span>

                        <span>
                            Waktu:
                            <strong>
                                {{
                                    formatAlertDate(alert.location_captured_at)
                                }}
                            </strong>
                        </span>
                    </div>
                </div>
            </div>
        </article>

        <!-- ============================================================= -->
        <!-- TELEMETRY -->
        <!-- ============================================================= -->

        <article
            class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                    >
                        Live Activity
                    </p>

                    <h2 class="mt-1 text-lg font-bold text-on-surface">
                        Telemetry Feed
                    </h2>
                </div>

                <span
                    class="rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface-variant"
                >
                    {{ telemetryItems.length }} Events
                </span>
            </div>

            <div
                v-if="telemetryItems.length"
                class="mt-5 divide-y divide-outline-variant"
            >
                <div
                    v-for="(item, index) in telemetryItems"
                    :key="item.id ?? index"
                    class="flex items-center gap-3 py-3 first:pt-0 last:pb-0"
                >
                    <span
                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="getTelemetryStatusClass(item.status)"
                    ></span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-on-surface">
                            {{
                                item.message ||
                                item.title ||
                                item.activity ||
                                "Activity update"
                            }}
                        </p>

                        <p
                            v-if="item.description"
                            class="mt-0.5 truncate text-xs text-on-surface-variant"
                        >
                            {{ item.description }}
                        </p>
                    </div>

                    <span
                        v-if="item.time || item.created_at"
                        class="shrink-0 text-xs text-on-surface-variant"
                    >
                        {{ item.time || item.created_at }}
                    </span>
                </div>
            </div>

            <div
                v-else
                class="mt-5 rounded-lg bg-surface-container p-6 text-center"
            >
                <p class="text-sm font-semibold text-on-surface">
                    Belum ada aktivitas
                </p>

                <p class="mt-1 text-xs text-on-surface-variant">
                    Aktivitas terbaru akan tampil di sini.
                </p>
            </div>
        </article>
    </section>
</template>
