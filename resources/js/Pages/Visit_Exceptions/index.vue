<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import ExceptionStats from "./components/ExceptionStats.vue";
import ExceptionFilters from "./components/ExceptionFilters.vue";
import ExceptionTable from "./components/ExceptionTable.vue";
import ExceptionDetailModal from "./components/ExceptionDetailModal.vue";

const props = defineProps({
    exceptions: {
        type: Object,
        default: () => ({
            data: [],
            total: 0,
        }),
    },

    sales: {
        type: Array,
        default: () => [],
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    statistics: {
        type: Object,
        default: () => ({
            total: 0,
            critical: 0,
            warning: 0,
            average_distance: 0,
        }),
    },
});

const selectedException = ref(null);

const applyFilter = (filters) => {
    router.get("/visit_exception", filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilter = () => {
    router.get(
        "/visit_exception",
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const viewException = (exception) => {
    selectedException.value = exception;
};

const closeModal = () => {
    selectedException.value = null;
};
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1600px]">
                <!-- Header -->
                <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <div class="mb-2 flex items-center gap-2">
                            <span class="text-xl">⚠️</span>

                            <span class="text-xs font-bold uppercase tracking-widest text-orange-600">
                                Monitoring
                            </span>
                        </div>

                        <h1 class="text-2xl font-bold text-slate-800 sm:text-3xl">
                            Visit Exceptions
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm text-slate-500">
                            Monitor kunjungan sales yang dilakukan di luar radius
                            lokasi store.
                        </p>
                    </div>

                    <div class="rounded-xl border border-orange-100 bg-orange-50 px-4 py-3">
                        <p class="text-xs font-medium text-orange-600">
                            Total Detected
                        </p>

                        <p class="mt-1 text-2xl font-bold text-orange-700">
                            {{ statistics.total }}
                        </p>
                    </div>
                </div>

                <!-- Statistics -->
                <ExceptionStats
                    :statistics="statistics"
                    class="mb-6"
                />

                <!-- Filters -->
                <div class="mb-6">
                    <ExceptionFilters
                        :filters="filters"
                        :sales="sales"
                        @filter="applyFilter"
                        @reset="resetFilter"
                    />
                </div>

                <!-- Table -->
                <ExceptionTable
                    :exceptions="exceptions.data"
                    @view="viewException"
                />
            </div>
        </div>

        <!-- Detail Modal -->
        <ExceptionDetailModal
            :exception="selectedException"
            @close="closeModal"
        />
    </AdminLayout>
</template>
