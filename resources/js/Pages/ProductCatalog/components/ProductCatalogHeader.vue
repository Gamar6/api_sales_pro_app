<script setup>
defineProps({
    warehouses: {
        type: Array,
        default: () => [],
    },

    selectedWarehouse: {
        type: [Number, String, null],
        default: null,
    },

    sync: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits([
    "update:selected-warehouse",
]);
</script>

<template>
    <section
        class="w-full bg-surface-container-low px-space-xl py-space-base shadow-sm"
    >
        <div
            class="flex flex-col gap-space-md lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <div
                    class="mb-1 flex flex-wrap items-center gap-space-sm"
                >
                    <h1
                        class="font-headline-lg text-headline-lg tracking-tight text-primary-container"
                    >
                        Product Catalog &amp; Warehouse Stock
                    </h1>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-highest px-2.5 py-0.5 font-label-caps text-label-caps uppercase tracking-wider text-primary"
                    >
                        <span
                            class="material-symbols-outlined text-[14px]"
                        >
                            lock
                        </span>

                        Read-Only Reference • Synced from Odoo ERP
                    </span>
                </div>

                <p
                    class="flex items-center gap-2 font-body-sm text-body-sm text-on-surface-variant"
                >
                    <span
                        class="material-symbols-outlined text-[16px]"
                    >
                        database
                    </span>

                    {{ sync.database || "Odoo ERP" }}

                    <span class="text-outline">•</span>

                    <span
                        class="inline-flex items-center gap-1.5"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-500"
                        ></span>

                        {{ sync.status || "Connected" }}
                    </span>
                </p>
            </div>

            <div
                class="flex flex-wrap items-center gap-space-sm"
            >
                <div
                    class="flex items-center gap-3 rounded bg-surface-container-lowest px-space-base py-2 shadow-sm"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded bg-surface-container text-primary"
                    >
                        <span
                            class="material-symbols-outlined text-[20px]"
                        >
                            warehouse
                        </span>
                    </div>

                    <div>
                        <span
                            class="block font-label-caps text-[10px] uppercase tracking-wider text-secondary"
                        >
                            Selected Distribution Node
                        </span>

                        <select
                            :value="selectedWarehouse"
                            class="mt-0.5 border-0 bg-transparent p-0 pr-7 text-sm font-bold text-primary outline-none focus:ring-0"
                            @change="
                                $emit(
                                    'update:selected-warehouse',
                                    Number($event.target.value)
                                )
                            "
                        >
                            <option
                                v-for="warehouse in warehouses"
                                :key="warehouse.id"
                                :value="warehouse.id"
                            >
                                {{ warehouse.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div
                    class="rounded bg-surface-container-lowest px-space-base py-2 shadow-sm"
                >
                    <span
                        class="block font-label-caps text-[10px] uppercase tracking-wider text-secondary"
                    >
                        Warehouse Latency
                    </span>

                    <strong
                        class="font-code-metric text-sm text-primary"
                    >
                        {{ sync.latency ? `${sync.latency}ms` : "Live" }}
                    </strong>
                </div>
            </div>
        </div>
    </section>
</template>
