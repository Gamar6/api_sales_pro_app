<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    clusters: {
        type: Array,
        required: true,
    },

    telemetry: {
        type: Array,
        required: true,
    },

    salesOrderChart: {
        type: Array,
        default: () => [],
    },
});

const alertVisible = ref(true);

/*
|--------------------------------------------------------------------------
| Bar Chart Configuration
|--------------------------------------------------------------------------
*/

const chartMaxValue = computed(() => {
    if (!props.salesOrderChart.length) {
        return 1;
    }

    return Math.max(
        ...props.salesOrderChart.map((item) => item.orders)
    );
});

const totalOrders = computed(() => {
    return props.salesOrderChart.reduce(
        (total, item) => total + Number(item.orders || 0),
        0
    );
});

const topSales = computed(() => {
    if (!props.salesOrderChart.length) {
        return null;
    }

    return props.salesOrderChart.reduce((highest, current) => {
        return current.orders > highest.orders
            ? current
            : highest;
    });
});

const averageOrders = computed(() => {
    if (!props.salesOrderChart.length) {
        return 0;
    }

    return (
        totalOrders.value / props.salesOrderChart.length
    ).toFixed(1);
});

/*
|--------------------------------------------------------------------------
| Bar Height
|--------------------------------------------------------------------------
|
| Minimum 4% supaya bar dengan value kecil tetap terlihat.
|
*/

const getBarHeight = (orders) => {
    if (!orders || chartMaxValue.value === 0) {
        return 0;
    }

    const percentage =
        (Number(orders) / chartMaxValue.value) * 100;

    return Math.max(percentage, 4);
};
</script>

<template>
    <section class="flex flex-col gap-space-xl">

    <div class="grid grid-cols-1 gap-space-lg lg:grid-cols-3">

        <article
            class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm lg:col-span-2"
        >
            <!-- Header -->

            <div
                class="flex flex-col justify-between gap-2 pb-space-base sm:flex-row sm:items-center"
            >
                <div>
                    <h2
                        class="font-headline-sm text-headline-sm font-semibold text-primary"
                    >
                        Sales Order Performance
                    </h2>

                    <p
                        class="font-body-sm text-body-sm text-secondary"
                    >
                        Total completed order activities per sales
                    </p>
                </div>

                <span
                    class="rounded bg-surface-container px-2 py-0.5 font-label-caps text-label-caps text-primary"
                >
                    Order Activity
                </span>
            </div>

            <div
                v-if="salesOrderChart.length"
                class="mt-space-md h-64 rounded bg-surface-container-low p-space-base"
            >
                <div
                    class="flex h-full items-end justify-around gap-3"
                >
                    <div
                        v-for="item in salesOrderChart"
                        :key="item.salesId"
                        class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end"
                    >
                        <!-- Order Value -->

                        <span
                            class="mb-2 font-code-metric text-xs font-semibold text-primary"
                        >
                            {{ item.orders }}
                        </span>

                        <!-- Bar -->

                        <div
                            class="relative flex h-[190px] w-full max-w-[56px] items-end"
                        >
                            <!-- Background -->

                            <div
                                class="absolute inset-0 rounded-t bg-surface-container"
                            ></div>

                            <!-- Actual Bar -->

                            <div
                                class="relative w-full rounded-t bg-primary-container transition-all duration-500 group-hover:opacity-80"
                                :style="{
                                    height: `${getBarHeight(
                                        item.orders
                                    )}%`,
                                }"
                            ></div>
                        </div>

                        <!-- Sales Name -->

                        <span
                            class="mt-2 w-full truncate text-center text-[11px] font-medium text-secondary"
                            :title="item.name"
                        >
                            {{ item.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- EMPTY STATE -->
            <!-- ==================================================== -->

            <div
                v-else
                class="flex h-64 items-center justify-center rounded bg-surface-container-low"
            >
                <div class="text-center">
                    <span
                        class="material-symbols-outlined mb-2 text-3xl text-secondary"
                    >
                        bar_chart
                    </span>

                    <p class="font-body-sm text-body-sm text-secondary">
                        No order activity found for this period
                    </p>
                </div>
            </div>

            <div
                class="mt-space-md grid grid-cols-1 gap-2 rounded bg-surface-container-low p-space-sm sm:grid-cols-3"
            >
                <!-- Total Orders -->

                <div>
                    <span
                        class="font-label-caps text-[10px] uppercase text-secondary"
                    >
                        Total Orders
                    </span>

                    <strong
                        class="block font-title-md text-title-md text-primary"
                    >
                        {{ totalOrders }}
                    </strong>
                </div>

                <!-- Top Sales -->

                <div>
                    <span
                        class="font-label-caps text-[10px] uppercase text-secondary"
                    >
                        Top Sales
                    </span>

                    <strong
                        class="block truncate font-title-md text-title-md text-primary"
                    >
                        {{
                            topSales
                                ? topSales.name
                                : "-"
                        }}
                    </strong>
                </div>

                <!-- Average -->

                <div>
                    <span
                        class="font-label-caps text-[10px] uppercase text-secondary"
                    >
                        Avg Orders / Sales
                    </span>

                    <strong
                        class="block font-title-md text-title-md text-primary"
                    >
                        {{ averageOrders }}
                    </strong>
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

                <span
                    class="material-symbols-outlined text-secondary"
                >
                    hub
                </span>
            </div>

            <p
                class="pb-space-md font-body-sm text-body-sm text-secondary"
            >
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
                        >
                            {{ cluster.name }}
                        </span>

                        <strong
                            class="font-code-metric text-primary-container"
                        >
                            {{ cluster.reps }} Reps
                        </strong>
                    </div>

                    <div
                        class="flex justify-between text-xs text-secondary"
                    >
                        <span>
                            Target: {{ cluster.target }} Visits
                        </span>

                        <span class="text-emerald-600">
                            {{ cluster.completed }} Completed
                            ({{ cluster.percentage }}%)
                        </span>
                    </div>

                    <div
                        class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-container"
                    >
                        <div
                            class="h-full rounded-full bg-primary-container"
                            :style="{
                                width: `${cluster.percentage}%`,
                            }"
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
            <strong
                class="font-headline-sm text-headline-sm text-primary"
            >
                Fast-Action Dispatch Alert
            </strong>

            <p
                class="font-body-sm text-body-sm text-secondary"
            >
                2 newly registered stores awaiting geofence approval
                within 1.2km of Elena Rostova.
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

            <p
                class="font-body-sm text-body-sm text-secondary"
            >
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
                        <th class="px-space-base">
                            Sales Agent
                        </th>

                        <th class="px-space-base">
                            Target Retail Store
                        </th>

                        <th class="px-space-base">
                            Timestamp
                        </th>

                        <th class="px-space-base">
                            Geofence Status
                        </th>

                        <th class="px-space-base">
                            Evidence
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="item in telemetry"
                        :key="item.id"
                        class="h-12 hover:bg-surface-container-low"
                    >
                        <td class="px-space-base">
                            <div
                                class="flex items-center gap-space-sm"
                            >
                                <img
                                    :src="item.avatar"
                                    :alt="item.name"
                                    class="h-7 w-7 rounded-full object-cover"
                                />

                                <div>
                                    <span
                                        class="block text-xs font-semibold text-primary"
                                    >
                                        {{ item.name }}
                                    </span>

                                    <span
                                        class="font-code-metric text-[10px] text-secondary"
                                    >
                                        {{ item.id }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="px-space-base">
                            <span
                                class="block text-xs font-medium text-primary"
                            >
                                {{ item.store }}
                            </span>

                            <span
                                class="text-[11px] text-secondary"
                            >
                                {{ item.location }}
                            </span>
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
                            >
                                {{ item.proximity }}
                            </span>
                        </td>

                        <td class="px-space-base">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded bg-surface-container-low px-2 py-1 text-[11px] text-primary"
                            >
                                <span
                                    class="material-symbols-outlined text-[14px]"
                                >
                                    {{ item.evidenceIcon }}
                                </span>

                                {{ item.evidence }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

</template>
