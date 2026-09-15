<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ProductCatalog from "./components/ProductCatalog.vue";

defineProps({
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
</script>

<template>
    <AdminLayout>
        <ProductCatalog
            :products="products"
            :categories="categories"
            :warehouses="warehouses"
            :summary="summary"
            :sync="sync"
        />
    </AdminLayout>
</template>
