<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },

    sales: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["filter", "reset"]);

const search = ref(props.filters.search ?? "");
const date = ref(props.filters.date ?? "");
const salesId = ref(props.filters.sales_id ?? "");
const status = ref(props.filters.status ?? "all");

let searchTimeout = null;

const submitFilter = () => {
    emit("filter", {
        search: search.value,
        date: date.value,
        sales_id: salesId.value,
        status: status.value,
    });
};

const resetFilter = () => {
    search.value = "";
    date.value = "";
    salesId.value = "";
    status.value = "all";

    emit("reset");
};

watch(search, () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        submitFilter();
    }, 500);
});
</script>

<template>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800">
                    Filter Exceptions
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Cari berdasarkan sales, tanggal, atau status.
                </p>
            </div>

            <button
                type="button"
                class="text-sm font-medium text-blue-600 transition hover:text-blue-800"
                @click="resetFilter"
            >
                Reset
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                    Search
                </label>

                <input
                    v-model="search"
                    type="text"
                    placeholder="Nama sales atau store..."
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                    Tanggal
                </label>

                <input
                    v-model="date"
                    type="date"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    @change="submitFilter"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                    Sales
                </label>

                <select
                    v-model="salesId"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    @change="submitFilter"
                >
                    <option value="">Semua Sales</option>

                    <option
                        v-for="item in sales"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">
                    Status
                </label>

                <select
                    v-model="status"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    @change="submitFilter"
                >
                    <option value="all">Semua Status</option>
                    <option value="completed">Completed</option>
                    <option value="active">Active</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                type="button"
                class="rounded-xl bg-[#1C467F] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#15365f]"
                @click="submitFilter"
            >
                Terapkan Filter
            </button>
        </div>
    </div>
</template>
