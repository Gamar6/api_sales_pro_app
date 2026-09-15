<script setup>
import ProductCard from "./ProductCard.vue";

defineProps({
    products: {
        type: Array,
        default: () => [],
    },

    density: {
        type: String,
        default: "comfortable",
    },
});
</script>

<template>
    <section class="px-space-xl py-space-lg">
        <div
            v-if="products.length"
            class="grid grid-cols-1 gap-space-md md:grid-cols-2 xl:grid-cols-3"
            :class="
                density === 'compact'
                    ? '2xl:grid-cols-4'
                    : '2xl:grid-cols-3'
            "
        >
            <ProductCard
                v-for="product in products"
                :key="product.id"
                :product="product"
                :density="density"
            />
        </div>

        <div
            v-else
            class="flex min-h-[360px] flex-col items-center justify-center rounded bg-surface-container-lowest px-6 text-center shadow-sm"
        >
            <div
                class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-surface-container-highest text-secondary"
            >
                <span
                    class="material-symbols-outlined text-[32px]"
                >
                    inventory_2
                </span>
            </div>

            <h3
                class="font-headline-sm text-headline-sm font-bold text-primary"
            >
                No products found
            </h3>

            <p
                class="mt-1 max-w-md text-sm text-secondary"
            >
                Try changing your search keyword or stock/category filter.
            </p>
        </div>
    </section>
</template>
