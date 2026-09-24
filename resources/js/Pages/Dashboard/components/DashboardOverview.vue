<script setup>
import { computed, ref } from "vue";

const props = defineProps({
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

const showOutsideRadiusAlerts = ref(true);

// Sales Order Performance

const chartMaxValue = computed(() => {
    if (!props.salesOrderChart.length) {
        return 1;
    }

    const max = Math.max(
        ...props.salesOrderChart.map((item) =>
            Number(item.orders ?? 0),
        ),
    );

    return Math.max(max, 1);
});

const totalOrders = computed(() => {
    return props.salesOrderChart.reduce(
        (total, item) =>
            total + Number(item.orders ?? 0),
        0,
    );
});

const totalOrderEvents = computed(() => {
    return props.salesOrderChart.reduce(
        (total, item) =>
            total + Number(item.order_events ?? 0),
        0,
    );
});

const topSales = computed(() => {
    if (!props.salesOrderChart.length) {
        return null;
    }

    return props.salesOrderChart.reduce(
        (highest, current) => {
            return Number(current.orders ?? 0) >
                Number(highest.orders ?? 0)
                ? current
                : highest;
        },
    );
});

const averageOrders = computed(() => {
    if (!props.salesOrderChart.length) {
        return "0.0";
    }

    return (
        totalOrders.value /
        props.salesOrderChart.length
    ).toFixed(1);
});

const getBarHeight = (orders) => {
    const value = Number(orders ?? 0);

    if (!value || chartMaxValue.value === 0) {
        return 4;
    }

    const percentage =
        (value / chartMaxValue.value) * 100;

    return Math.max(percentage, 6);
};

//Area Performance

const sortedAreas = computed(() => {
    return [...props.stores]
        .map((store) => ({
            ...store,
            reps: Number(store.reps ?? 0),
            visited: Number(store.visited ?? 0),
            unique_orders: Number(
                store.unique_orders ?? 0,
            ),
            order_events: Number(
                store.order_events ?? 0,
            ),
            total: Number(store.total ?? 0),
            percentage: Number(
                store.percentage ?? 0,
            ),
        }))
        .sort((a, b) => {
            if (b.percentage !== a.percentage) {
                return b.percentage - a.percentage;
            }

            return (
                b.unique_orders -
                a.unique_orders
            );
        });
});

const topAreas = computed(() => {
    return sortedAreas.value.slice(0, 5);
});

const totalAreaStores = computed(() => {
    return props.stores.reduce(
        (total, store) =>
            total + Number(store.total ?? 0),
        0,
    );
});

const totalAreaOrders = computed(() => {
    return props.stores.reduce(
        (total, store) =>
            total +
            Number(
                store.unique_orders ??
                    store.visited ??
                    0,
            ),
        0,
    );
});

const totalAreaOrderEvents = computed(() => {
    return props.stores.reduce(
        (total, store) =>
            total +
            Number(store.order_events ?? 0),
        0,
    );
});

const overallAreaPercentage = computed(() => {
    if (!totalAreaStores.value) {
        return 0;
    }

    return Math.round(
        (totalAreaOrders.value /
            totalAreaStores.value) *
            100,
    );
});

//Outside Radius

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
    if (!date) {
        return "-";
    }

    const normalized = String(date).replace(
        " ",
        "T",
    );

    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return parsed.toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

const goToExceptions = () => {
    window.location.href = route(
        "visit-exceptions.index",
    );
};

const goToVisitReports = () => {
    window.location.href = route(
        "visit-reports",
    );
};
</script>

<template>
    <section class="flex flex-col gap-space-xl">
        <!-- SALES + AREA -->

        <div
            class="grid grid-cols-1 gap-space-lg xl:grid-cols-3"
        >
            <!-- SALES ORDER -->

            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm xl:col-span-2"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                        >
                            Performance
                        </p>

                        <h2
                            class="mt-1 text-lg font-bold text-on-surface"
                        >
                            Sales Order Performance
                        </h2>

                        <p
                            class="mt-1 text-sm text-on-surface-variant"
                        >
                            Distribusi store yang melakukan order
                            berdasarkan sales pada periode terpilih.
                        </p>
                    </div>

                    <span
                        class="self-start rounded-full bg-surface-container px-3 py-1.5 text-xs font-semibold text-on-surface-variant"
                    >
                        {{ salesOrderChart.length }}
                        Sales
                    </span>
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
                        <span
                            class="text-xs font-semibold text-on-surface"
                        >
                            {{ sales.orders }}
                        </span>

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

                        <span
                            class="max-w-[72px] truncate text-center text-xs text-on-surface-variant"
                            :title="sales.name"
                        >
                            {{ sales.name }}
                        </span>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-8 flex h-64 items-center justify-center rounded-lg bg-surface-container"
                >
                    <div class="text-center">
                        <span
                            class="material-symbols-outlined text-3xl text-on-surface-variant"
                        >
                            bar_chart
                        </span>

                        <p
                            class="mt-2 text-sm font-semibold text-on-surface"
                        >
                            Belum ada data order
                        </p>

                        <p
                            class="mt-1 text-xs text-on-surface-variant"
                        >
                            Data akan muncul setelah terdapat report
                            dengan aktivitas Order.
                        </p>
                    </div>
                </div>

                <!-- Summary -->

                <div
                    class="mt-6 grid grid-cols-1 divide-y divide-outline-variant rounded-lg bg-surface-container sm:grid-cols-3 sm:divide-x sm:divide-y-0"
                >
                    <div class="px-4 py-3">
                        <p
                            class="text-xs text-on-surface-variant"
                        >
                            Ordered Stores
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-on-surface"
                        >
                            {{ totalOrders.toLocaleString("id-ID") }}
                        </p>
                    </div>

                    <div class="px-4 py-3">
                        <p
                            class="text-xs text-on-surface-variant"
                        >
                            Order Events
                        </p>

                        <p
                            class="mt-1 text-lg font-bold text-on-surface"
                        >
                            {{
                                totalOrderEvents.toLocaleString(
                                    "id-ID",
                                )
                            }}
                        </p>
                    </div>

                    <div class="px-4 py-3">
                        <p
                            class="text-xs text-on-surface-variant"
                        >
                            Top Sales
                        </p>

                        <p
                            class="mt-1 truncate text-lg font-bold text-on-surface"
                            :title="topSales?.name"
                        >
                            {{ topSales?.name || "-" }}
                        </p>
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center justify-between gap-4"
                >
                    <p
                        class="text-xs text-on-surface-variant"
                    >
                        Rata-rata
                        {{ averageOrders }}
                        ordered stores per sales.
                    </p>

                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        @click="goToVisitReports"
                    >
                        Lihat reports
                        <span
                            class="material-symbols-outlined text-sm"
                        >
                            arrow_forward
                        </span>
                    </button>
                </div>
            </article>

            <!-- AREA -->

            <article
                class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
            >
                <div
                    class="flex items-start justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                        >
                            Area
                        </p>

                        <h2
                            class="mt-1 text-lg font-bold text-on-surface"
                        >
                            Order Performance
                        </h2>

                        <p
                            class="mt-1 text-sm text-on-surface-variant"
                        >
                            Perbandingan store yang melakukan order
                            terhadap populasi store per area.
                        </p>
                    </div>

                    <div
                        class="shrink-0 rounded-lg bg-primary-container px-3 py-2 text-center text-on-primary"
                    >
                        <p class="text-lg font-bold">
                            {{ overallAreaPercentage }}%
                        </p>

                        <p class="text-[10px] uppercase">
                            overall
                        </p>
                    </div>
                </div>

                <div
                    v-if="topAreas.length"
                    class="mt-6 space-y-5"
                >
                    <div
                        v-for="area in topAreas"
                        :key="area.name"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold text-on-surface"
                                    :title="area.name"
                                >
                                    {{ area.name }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-on-surface-variant"
                                >
                                    {{ area.reps }} sales
                                    ·
                                    {{ area.unique_orders }}
                                    ordered stores
                                </p>
                            </div>

                            <span
                                class="shrink-0 text-sm font-bold text-on-surface"
                            >
                                {{ area.percentage }}%
                            </span>
                        </div>

                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-surface-container"
                        >
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{
                                    width: `${Math.min(
                                        Math.max(
                                            area.percentage,
                                            0,
                                        ),
                                        100,
                                    )}%`,
                                }"
                            ></div>
                        </div>

                        <div
                            class="mt-1 flex justify-between text-[11px] text-on-surface-variant"
                        >
                            <span>
                                {{
                                    area.unique_orders.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                                / {{ area.total.toLocaleString("id-ID") }}
                                stores
                            </span>

                            <span>
                                {{
                                    area.order_events.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                                events
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-6 rounded-lg bg-surface-container p-6 text-center"
                >
                    <span
                        class="material-symbols-outlined text-3xl text-on-surface-variant"
                    >
                        location_city
                    </span>

                    <p
                        class="mt-2 text-sm font-semibold text-on-surface"
                    >
                        Belum ada data area
                    </p>

                    <p
                        class="mt-1 text-xs text-on-surface-variant"
                    >
                        Performance area akan muncul ketika data order
                        tersedia.
                    </p>
                </div>

                <div
                    class="mt-6 border-t border-outline-variant pt-4"
                >
                    <div
                        class="grid grid-cols-2 gap-4"
                    >
                        <div>
                            <p
                                class="text-xs text-on-surface-variant"
                            >
                                Total Stores
                            </p>

                            <p
                                class="mt-1 text-lg font-bold text-on-surface"
                            >
                                {{
                                    totalAreaStores.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs text-on-surface-variant"
                            >
                                Ordered Stores
                            </p>

                            <p
                                class="mt-1 text-lg font-bold text-on-surface"
                            >
                                {{
                                    totalAreaOrders.toLocaleString(
                                        "id-ID",
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- OUTSIDE RADIUS -->

        <article
            v-if="showOutsideRadiusAlerts"
            class="rounded-lg border border-amber-200 bg-amber-50 p-space-lg shadow-sm"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"
                    >
                        <span
                            class="material-symbols-outlined"
                        >
                            location_off
                        </span>
                    </div>

                    <div>
                        <div
                            class="flex flex-wrap items-center gap-2"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-[0.12em] text-amber-800"
                            >
                                Attention Required
                            </p>

                            <span
                                class="rounded-full bg-amber-200 px-2 py-0.5 text-[11px] font-bold text-amber-900"
                            >
                                {{ outsideRadiusCount }}
                                alert
                            </span>
                        </div>

                        <h2
                            class="mt-1 text-lg font-bold text-amber-950"
                        >
                            Outside Radius Visits
                        </h2>

                        <p
                            class="mt-1 max-w-2xl text-sm text-amber-900/80"
                        >
                            Kunjungan berikut terdeteksi berada di luar
                            radius toko dan membutuhkan peninjauan.
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-2"
                >
                    <button
                        type="button"
                        class="rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-semibold text-amber-900 transition hover:bg-amber-100"
                        @click="goToExceptions"
                    >
                        Lihat Semua
                    </button>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-md text-amber-800 hover:bg-amber-100"
                        title="Tutup"
                        @click="showOutsideRadiusAlerts = false"
                    >
                        <span
                            class="material-symbols-outlined text-lg"
                        >
                            close
                        </span>
                    </button>
                </div>
            </div>

            <div
                v-if="outsideRadiusAlerts.length"
                class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2"
            >
                <div
                    v-for="(alert, index) in outsideRadiusAlerts"
                    :key="alert.id ?? index"
                    class="rounded-lg border border-amber-200 bg-white p-4"
                >
                    <div
                        class="flex items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate font-semibold text-slate-900"
                            >
                                {{
                                    alert.sales_name ||
                                    alert.sales?.name ||
                                    "Sales"
                                }}
                            </p>

                            <p
                                class="mt-0.5 truncate text-sm text-slate-600"
                            >
                                {{
                                    alert.store_name ||
                                    alert.store?.name ||
                                    "Store"
                                }}
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold uppercase text-amber-800"
                        >
                            Outside Radius
                        </span>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-2 gap-3 text-xs"
                    >
                        <div>
                            <p class="text-slate-500">
                                Distance
                            </p>

                            <p
                                class="mt-1 font-semibold text-slate-900"
                            >
                                {{
                                    formatDistance(
                                        alert.distance_from_store ??
                                            alert.distance,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-slate-500">
                                Time
                            </p>

                            <p
                                class="mt-1 font-semibold text-slate-900"
                            >
                                {{
                                    formatAlertDate(
                                        alert.location_captured_at ??
                                            alert.created_at,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="alert.sales_accuracy"
                        class="mt-3 text-xs text-slate-600"
                    >
                        GPS accuracy:
                        <strong>
                            {{ alert.sales_accuracy }}
                        </strong>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="mt-5 rounded-lg border border-amber-200 bg-white p-6 text-center"
            >
                <span
                    class="material-symbols-outlined text-3xl text-amber-600"
                >
                    check_circle
                </span>

                <p
                    class="mt-2 text-sm font-semibold text-slate-900"
                >
                    Tidak ada exception
                </p>

                <p
                    class="mt-1 text-xs text-slate-600"
                >
                    Semua kunjungan dalam periode ini berada dalam
                    kondisi normal.
                </p>
            </div>
        </article>

        <!-- NO ALERT -->

        <article
            v-else
            class="flex items-center justify-between gap-4 rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-surface-container text-on-surface-variant"
                >
                    <span
                        class="material-symbols-outlined"
                    >
                        visibility_off
                    </span>
                </div>

                <div>
                    <p
                        class="text-sm font-semibold text-on-surface"
                    >
                        Outside radius alerts disembunyikan
                    </p>

                    <p
                        class="mt-1 text-xs text-on-surface-variant"
                    >
                        {{
                            outsideRadiusCount
                        }}
                        alert tersedia untuk ditinjau.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="text-xs font-semibold text-primary hover:underline"
                @click="showOutsideRadiusAlerts = true"
            >
                Tampilkan
            </button>
        </article>
    </section>
</template>
