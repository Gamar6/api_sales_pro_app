<script setup>
import { computed } from "vue";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },

    visibleStock: {
        type: Number,
        default: 0,
    },

    visibleValue: {
        type: Number,
        default: 0,
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
};

const healthIndex = computed(() => {
    return Number(
        props.summary.healthIndex || 0
    ).toFixed(1);
});
</script>

<template>
    <section
        class="mx-space-xl mb-space-xl rounded bg-surface-container-lowest p-space-base shadow-sm"
    >
        <div
            class="grid grid-cols-1 gap-space-base md:grid-cols-2 xl:grid-cols-4"
        >
            <!-- SKU -->
            <div
                class="rounded bg-surface-container-low p-space-base"
            >
                <span
                    class="block font-label-caps text-label-caps uppercase text-on-surface-variant"
                >
                    Total Field-Active SKUs
                </span>

                <div
                    class="mt-1 flex items-baseline gap-2"
                >
                    <span
                        class="font-headline-lg text-headline-lg font-bold text-primary"
                    >
                        {{ summary.totalSkus || 0 }}
                    </span>

                    <span
                        class="text-xs font-semibold text-emerald-700"
                    >
                        100% Synced
                    </span>
                </div>

                <p
                    class="mt-1 text-xs text-secondary"
                >
                    Current Odoo product reference
                </p>
            </div>

            <!-- Valuation -->
            <div
                class="rounded bg-surface-container-low p-space-base"
            >
                <span
                    class="block font-label-caps text-label-caps uppercase text-on-surface-variant"
                >
                    Active DC Stock Valuation
                </span>

                <strong
                    class="mt-1 block font-headline-sm text-headline-sm font-bold text-primary"
                >
                    {{ formatCurrency(summary.totalValuation) }}
                </strong>

                <p
                    class="mt-1 text-xs text-secondary"
                >
                    {{ visibleStock.toLocaleString("id-ID") }}
                    units currently available
                </p>
            </div>

            <!-- Health -->
            <div
                class="rounded bg-surface-container-low p-space-base"
            >
                <span
                    class="block font-label-caps text-label-caps uppercase text-on-surface-variant"
                >
                    Warehouse Health Index
                </span>

                <div
                    class="mt-1 flex items-center gap-2"
                >
                    <strong
                        class="font-headline-sm text-headline-sm font-bold text-primary"
                    >
                        {{ healthIndex }}%
                    </strong>

                    <span
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800"
                    >
                        Fulfillable
                    </span>
                </div>

                <div
                    class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-[11px]"
                >
                    <span class="text-emerald-700">
                        {{ summary.healthyCount || 0 }}
                        Normal
                    </span>

                    <span class="text-orange-700">
                        {{ summary.lowStockCount || 0 }}
                        Low Stock
                    </span>

                    <span class="text-red-700">
                        {{ summary.outOfStockCount || 0 }}
                        Depleted
                    </span>
                </div>
            </div>

            <!-- ERP -->
            <div
                class="rounded bg-surface-container-low p-space-base"
            >
                <span
                    class="block font-label-caps text-label-caps uppercase text-on-surface-variant"
                >
                    ERP Channel Pipe
                </span>

                <strong
                    class="mt-1 block font-headline-sm text-headline-sm font-bold text-primary"
                >
                    Odoo ERP
                </strong>

                <p
                    class="mt-1 text-xs text-secondary"
                >
                    XML-RPC inventory integration
                </p>

                <div
                    class="mt-3 flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>

                    Live synchronization
                </div>
            </div>
        </div>
    </section>
</template>
