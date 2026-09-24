<script setup>
import { onMounted, onUnmounted, ref } from "vue";

const props = defineProps({
    search: {
        type: String,
        default: "",
    },

    stockFilter: {
        type: String,
        default: "all",
    },

    categoryFilter: {
        type: String,
        default: "all",
    },

    categories: {
        type: Array,
        default: () => [],
    },

    density: {
        type: String,
        default: "comfortable",
    },

    total: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits([
    "update:search",
    "update:stock-filter",
    "update:category-filter",
    "update:density",
    "export",
]);

const searchInput = ref(null);

const stockOptions = [
    {
        value: "all",
        label: "All Levels",
        icon: "inventory_2",
    },
    {
        value: "in_stock",
        label: "In Stock (>100)",
        icon: "check_circle",
    },
    {
        value: "low_stock",
        label: "Low Stock (<20)",
        icon: "warning",
    },
    {
        value: "out_of_stock",
        label: "Out of Stock",
        icon: "remove_shopping_cart",
    },
];

const focusSearch = () => {
    searchInput.value?.focus();
};

const handleFocusEvent = () => {
    focusSearch();
};

const exportProducts = () => {
    const headers = [
        "SKU",
        "Product Name",
        "Category",
        "Brand",
        "Stock",
        "Unit",
        "Price",
        "Status",
    ];

    const rows = props.total
        ? document.querySelectorAll(
              "[data-product-card]"
          )
        : [];

    const data = Array.from(rows).map((row) => {
        return [
            row.dataset.sku || "",
            row.dataset.name || "",
            row.dataset.category || "",
            row.dataset.brand || "",
            row.dataset.stock || "",
            row.dataset.unit || "",
            row.dataset.price || "",
            row.dataset.status || "",
        ];
    });

    if (!data.length) {
        return;
    }

    const csv = [
        headers,
        ...data,
    ]
        .map((row) =>
            row
                .map((value) =>
                    `"${String(value).replaceAll('"', '""')}"`
                )
                .join(",")
        )
        .join("\n");

    const blob = new Blob([csv], {
        type: "text/csv;charset=utf-8;",
    });

    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");

    anchor.href = url;
    anchor.download = "product-catalog.csv";
    anchor.click();

    URL.revokeObjectURL(url);
};

onMounted(() => {
    window.addEventListener(
        "product-catalog-focus-search",
        handleFocusEvent
    );
});

onUnmounted(() => {
    window.removeEventListener(
        "product-catalog-focus-search",
        handleFocusEvent
    );
});
</script>

<template>
    <section
        class="w-full border-b border-outline-variant bg-surface-container-lowest px-space-xl py-space-base"
    >
        <div
            class="flex flex-col gap-space-md"
        >
            <div
                class="flex flex-col gap-space-sm xl:flex-row xl:items-center xl:justify-between"
            >
                <div class="relative min-w-0 flex-1">
                    <span
                        class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-secondary"
                    >
                        search
                    </span>

                    <input
                        ref="searchInput"
                        :value="search"
                        type="search"
                        placeholder="Search by SKU, Product Name, Barcode, or Brand..."
                        class="h-11 w-full rounded border border-outline-variant bg-surface-container-low pl-10 pr-20 text-sm text-primary outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/10"
                        @input="
                            emit(
                                'update:search',
                                $event.target.value
                            )
                        "
                    />

                    <kbd
                        class="absolute right-3 top-1/2 hidden -translate-y-1/2 rounded border border-outline-variant bg-surface-container-highest px-2 py-1 font-mono text-[10px] text-secondary sm:block"
                    >
                        Ctrl K
                    </kbd>
                </div>

                <div
                    class="flex shrink-0 items-center gap-2"
                >
                    <div
                        class="flex rounded border border-outline-variant bg-surface-container-low p-1"
                    >
                        <button
                            v-for="option in stockOptions"
                            :key="option.value"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-xs font-semibold transition"
                            :class="
                                stockFilter === option.value
                                    ? 'bg-primary text-on-primary shadow-sm'
                                    : 'text-secondary hover:bg-surface-container-highest hover:text-primary'
                            "
                            @click="
                                emit(
                                    'update:stock-filter',
                                    option.value
                                )
                            "
                        >
                            <span
                                class="material-symbols-outlined text-[15px]"
                            >
                                {{ option.icon }}
                            </span>

                            <span class="hidden 2xl:inline">
                                {{ option.label }}
                            </span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 items-center gap-2 rounded border border-outline-variant bg-surface-container-low px-3 text-xs font-semibold text-primary transition hover:bg-surface-container-highest"
                        @click="exportProducts"
                    >
                        <span
                            class="material-symbols-outlined text-[18px]"
                        >
                            download
                        </span>

                        Export
                    </button>
                </div>
            </div>

            <div
                class="flex flex-col gap-space-sm lg:flex-row lg:items-center lg:justify-between"
            >
                <div
                    class="flex flex-wrap items-center gap-2"
                >
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-bold transition"
                        :class="
                            categoryFilter === 'all'
                                ? 'bg-primary text-on-primary'
                                : 'bg-surface-container-highest text-secondary hover:text-primary'
                        "
                        @click="
                            emit(
                                'update:category-filter',
                                'all'
                            )
                        "
                    >
                        All Products ({{ total }})
                    </button>

                    <button
                        v-for="category in categories"
                        :key="category.name"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            categoryFilter === category.name
                                ? 'bg-primary text-on-primary'
                                : 'bg-surface-container-highest text-secondary hover:text-primary'
                        "
                        @click="
                            emit(
                                'update:category-filter',
                                category.name
                            )
                        "
                    >
                        {{ category.name }}
                        ({{ category.count }})
                    </button>
                </div>

                <div
                    class="flex items-center justify-between gap-4"
                >
                    <span
                        class="text-xs text-secondary"
                    >
                        Showing
                        <strong class="text-primary">
                            {{ total }}
                        </strong>
                        products
                    </span>

                    <div
                        class="flex rounded border border-outline-variant bg-surface-container-low p-1"
                    >
                        <button
                            type="button"
                            title="Comfortable"
                            class="rounded p-1.5"
                            :class="
                                density === 'comfortable'
                                    ? 'bg-surface-container-highest text-primary'
                                    : 'text-secondary'
                            "
                            @click="
                                emit(
                                    'update:density',
                                    'comfortable'
                                )
                            "
                        >
                            <span
                                class="material-symbols-outlined text-[18px]"
                            >
                                view_agenda
                            </span>
                        </button>

                        <button
                            type="button"
                            title="Compact"
                            class="rounded p-1.5"
                            :class="
                                density === 'compact'
                                    ? 'bg-surface-container-highest text-primary'
                                    : 'text-secondary'
                            "
                            @click="
                                emit(
                                    'update:density',
                                    'compact'
                                )
                            "
                        >
                            <span
                                class="material-symbols-outlined text-[18px]"
                            >
                                grid_view
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
