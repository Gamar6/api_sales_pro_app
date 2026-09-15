<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";

import ProductCatalogHeader from "./ProductCatalogHeader.vue";
import ProductCatalogFilters from "./ProductCatalogFilters.vue";
import ProductGrid from "./ProductGrid.vue";
import ProductCatalogSummary from "./ProductCatalogSummary.vue";
import ProductCatalogPagination from "./ProductCatalogPagination.vue";

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },

    categories: {
        type: Array,
        default: () => [],
    },

    warehouses: {
        type: Array,
        default: () => [],
    },

    summary: {
        type: Object,
        default: () => ({}),
    },

    sync: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref("");
const stockFilter = ref("all");
const categoryFilter = ref("all");
const selectedWarehouse = ref(
    props.warehouses?.[0]?.id ?? null
);

const density = ref("comfortable");

const currentPage = ref(1);
const perPage = ref(12);

const filteredProducts = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    return props.products.filter((product) => {
        const matchesSearch =
            !keyword ||
            [
                product.sku,
                product.name,
                product.fullName,
                product.barcode,
                product.brand,
                product.category,
            ]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLowerCase()
                        .includes(keyword)
                );

        let matchesStock = true;

        if (stockFilter.value === "in_stock") {
            matchesStock = Number(product.stock) > 100;
        }

        if (stockFilter.value === "low_stock") {
            matchesStock =
                Number(product.stock) > 0 &&
                Number(product.stock) < 20;
        }

        if (stockFilter.value === "out_of_stock") {
            matchesStock = Number(product.stock) <= 0;
        }

        const matchesCategory =
            categoryFilter.value === "all" ||
            product.category === categoryFilter.value;

        return (
            matchesSearch &&
            matchesStock &&
            matchesCategory
        );
    });
});

const totalPages = computed(() => {
    return Math.max(
        1,
        Math.ceil(
            filteredProducts.value.length /
                perPage.value
        )
    );
});

const paginatedProducts = computed(() => {
    const start =
        (currentPage.value - 1) *
        perPage.value;

    return filteredProducts.value.slice(
        start,
        start + perPage.value
    );
});

const visibleFrom = computed(() => {
    if (!filteredProducts.value.length) return 0;

    return (
        (currentPage.value - 1) *
            perPage.value +
        1
    );
});

const visibleTo = computed(() => {
    return Math.min(
        currentPage.value * perPage.value,
        filteredProducts.value.length
    );
});

const totalVisibleStock = computed(() => {
    return filteredProducts.value.reduce(
        (total, product) =>
            total + Number(product.stock || 0),
        0
    );
});

const totalVisibleValue = computed(() => {
    return filteredProducts.value.reduce(
        (total, product) =>
            total +
            Number(product.stock || 0) *
                Number(product.price || 0),
        0
    );
});

const resetPage = () => {
    currentPage.value = 1;
};

const changePage = (page) => {
    currentPage.value = Math.min(
        Math.max(1, page),
        totalPages.value
    );
};

const changePerPage = (value) => {
    perPage.value = Number(value);
    currentPage.value = 1;
};

const focusSearch = () => {
    window.dispatchEvent(
        new CustomEvent("product-catalog-focus-search")
    );
};

const handleKeyboardShortcut = (event) => {
    if (
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === "k"
    ) {
        event.preventDefault();
        focusSearch();
    }
};

onMounted(() => {
    window.addEventListener(
        "keydown",
        handleKeyboardShortcut
    );
});

onUnmounted(() => {
    window.removeEventListener(
        "keydown",
        handleKeyboardShortcut
    );
});
</script>

<template>
    <main
        class="relative min-h-screen w-full bg-background"
    >
        <div class="flex w-full flex-col">
            <ProductCatalogHeader
                :warehouses="warehouses"
                :selected-warehouse="selectedWarehouse"
                :sync="sync"
                @update:selected-warehouse="
                    selectedWarehouse = $event
                "
            />

            <ProductCatalogFilters
                :search="search"
                :stock-filter="stockFilter"
                :category-filter="categoryFilter"
                :categories="categories"
                :density="density"
                :total="filteredProducts.length"
                @update:search="
                    search = $event;
                    resetPage();
                "
                @update:stock-filter="
                    stockFilter = $event;
                    resetPage();
                "
                @update:category-filter="
                    categoryFilter = $event;
                    resetPage();
                "
                @update:density="density = $event"
            />

            <ProductGrid
                :products="paginatedProducts"
                :density="density"
            />

            <ProductCatalogSummary
                :summary="summary"
                :visible-stock="totalVisibleStock"
                :visible-value="totalVisibleValue"
            />

            <ProductCatalogPagination
                :current-page="currentPage"
                :total-pages="totalPages"
                :per-page="perPage"
                :from="visibleFrom"
                :to="visibleTo"
                :total="filteredProducts.length"
                @page="changePage"
                @update:per-page="changePerPage"
            />
        </div>
    </main>
</template>
