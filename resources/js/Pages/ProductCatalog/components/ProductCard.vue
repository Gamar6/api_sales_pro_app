<script setup>
import { computed } from "vue";

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },

    density: {
        type: String,
        default: "comfortable",
    },
});

const formattedPrice = computed(() => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(props.product.price || 0));
});

const stockPercentage = computed(() => {
    const stock = Number(props.product.stock || 0);
    const reorder = Number(
        props.product.reorderLevel || 20
    );

    if (stock <= 0) return 0;

    return Math.min(
        100,
        Math.max(
            8,
            (stock / Math.max(reorder * 5, 1)) * 100
        )
    );
});

const statusClass = computed(() => {
    switch (props.product.statusType) {
        case "danger":
            return "bg-red-100 text-red-800";

        case "warning":
            return "bg-orange-100 text-orange-800";

        default:
            return "bg-emerald-100 text-emerald-800";
    }
});

const statusIcon = computed(() => {
    switch (props.product.statusType) {
        case "danger":
            return "remove_shopping_cart";

        case "warning":
            return "warning";

        default:
            return "check_circle";
    }
});

const stockText = computed(() => {
    const stock = Number(props.product.stock || 0);

    return new Intl.NumberFormat("id-ID", {
        maximumFractionDigits: 0,
    }).format(stock);
});
</script>

<template>
    <article
        data-product-card
        :data-sku="product.sku"
        :data-name="product.name"
        :data-category="product.category"
        :data-brand="product.brand"
        :data-stock="product.stock"
        :data-unit="product.unit"
        :data-price="product.price"
        :data-status="product.status"
        class="group flex flex-col overflow-hidden rounded bg-surface-container-lowest shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md"
    >
        <!-- Product image -->
        <div
            class="relative flex items-center justify-center bg-surface-container-low"
            :class="
                density === 'compact'
                    ? 'h-36'
                    : 'h-48'
            "
        >
            <img
                v-if="product.image"
                :src="product.image"
                :alt="product.name"
                class="h-full w-full object-contain p-6"
                loading="lazy"
            />

            <div
                v-else
                class="flex flex-col items-center justify-center text-secondary"
            >
                <span
                    class="material-symbols-outlined text-[56px]"
                >
                    inventory_2
                </span>

                <span
                    class="mt-2 font-label-caps text-[10px] uppercase tracking-widest"
                >
                    Product Image
                </span>
            </div>

            <div
                class="absolute left-3 top-3 rounded bg-surface-container-lowest px-2 py-1 font-label-caps text-[10px] font-bold uppercase tracking-wider text-primary shadow-sm"
            >
                {{ product.packaging }}
            </div>

            <div
                class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full px-2 py-1 font-label-caps text-[10px] font-bold uppercase tracking-wider"
                :class="statusClass"
            >
                <span
                    class="material-symbols-outlined text-[13px]"
                >
                    {{ statusIcon }}
                </span>

                {{ product.status }}
            </div>
        </div>

        <!-- Main info -->
        <div
            class="flex flex-1 flex-col p-space-md"
        >
            <div
                class="mb-2 flex items-start justify-between gap-3"
            >
                <div>
                    <span
                        class="font-label-caps text-[10px] uppercase tracking-wider text-secondary"
                    >
                        {{ product.category }}
                    </span>

                    <h3
                        class="mt-1 font-headline-sm text-headline-sm font-bold leading-tight text-primary"
                    >
                        {{ product.name }}
                    </h3>
                </div>

                <span
                    class="shrink-0 font-code-metric text-[10px] font-bold text-primary-container"
                >
                    {{ product.sku }}
                </span>
            </div>

            <div
                class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-secondary"
            >
                <span>
                    {{ product.brand }}
                </span>

                <span v-if="product.barcode">
                    EAN {{ product.barcode }}
                </span>

                <span>
                    {{ product.weight }}
                    {{ product.weightUnit }}
                </span>
            </div>

            <!-- Price -->
            <div
                class="mb-4 flex items-end justify-between gap-4"
            >
                <div>
                    <span
                        class="block font-label-caps text-[10px] uppercase tracking-wider text-secondary"
                    >
                        Unit Price
                    </span>

                    <strong
                        class="font-code-metric text-lg font-bold text-primary-container"
                    >
                        {{ formattedPrice }}
                    </strong>
                </div>

                <span
                    class="text-xs font-semibold text-secondary"
                >
                    / {{ product.unit }}
                </span>
            </div>

            <!-- Stock matrix -->
            <div
                class="rounded bg-surface-container-low p-3"
            >
                <div
                    class="mb-2 flex items-center justify-between"
                >
                    <span
                        class="font-label-caps text-[10px] font-bold uppercase tracking-wider text-secondary"
                    >
                        Available in Main DC
                    </span>

                    <strong
                        class="font-code-metric text-sm text-primary"
                    >
                        {{ stockText }}
                    </strong>
                </div>

                <div
                    class="mb-3 h-1.5 overflow-hidden rounded-full bg-surface-container-highest"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-all"
                        :style="{
                            width: `${stockPercentage}%`,
                        }"
                    ></div>
                </div>

                <div
                    class="grid grid-cols-2 gap-3 text-xs"
                >
                    <div>
                        <span
                            class="block text-secondary"
                        >
                            Allocated / Reserved
                        </span>

                        <strong class="text-primary">
                            {{
                                new Intl.NumberFormat(
                                    "id-ID"
                                ).format(
                                    Number(
                                        product.reserved || 0
                                    )
                                )
                            }}
                        </strong>
                    </div>

                    <div>
                        <span
                            class="block text-secondary"
                        >
                            Safety Buffer
                        </span>

                        <strong class="text-primary">
                            {{
                                new Intl.NumberFormat(
                                    "id-ID"
                                ).format(
                                    Number(
                                        product.reorderLevel ||
                                            0
                                    )
                                )
                            }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom strip -->
        <div
            class="flex items-center justify-between border-t border-outline-variant bg-surface-container-low px-space-md py-2.5"
        >
            <div
                class="flex items-center gap-2 text-xs"
            >
                <span
                    class="material-symbols-outlined text-[16px] text-tertiary-container"
                >
                    stars
                </span>

                <span class="text-secondary">
                    Rep Incentive:
                </span>

                <strong
                    class="font-code-metric text-[11px] text-tertiary-container"
                >
                    {{
                        product.incentive
                            ? `Rp ${Number(
                                  product.incentive
                              ).toLocaleString("id-ID")}`
                            : "N/A"
                    }}
                </strong>
            </div>

            <span
                v-if="product.priority"
                class="font-code-metric text-[11px] font-bold text-primary"
            >
                High Priority SKU
            </span>

            <span
                v-else
                class="text-[11px] text-secondary"
            >
                {{ product.bin || "Bin N/A" }}
            </span>
        </div>
    </article>
</template>
