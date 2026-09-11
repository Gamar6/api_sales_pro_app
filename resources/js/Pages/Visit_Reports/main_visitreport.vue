<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

import AdminLayout from "@/Layouts/AdminLayout.vue";

import VisitReportHeader from "./components/VisitReportHeader.vue";
import VisitReportFilters from "./components/VisitReportFilters.vue";
import VisitReportTable from "./components/VisitReportTable.vue";
import VisitInspectionDrawer from "./components/VisitInspectionDrawer.vue";

defineOptions({
    layout: AdminLayout,
});

const props = defineProps({
    visits: {
        type: Object,
        required: true,
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
        default: () => ({}),
    },
});

/*
|--------------------------------------------------------------------------
| Drawer State
|--------------------------------------------------------------------------
*/

const selectedVisit = ref(null);

/*
|--------------------------------------------------------------------------
| Local Filters
|--------------------------------------------------------------------------
*/

const localFilters = ref({
    date: props.filters?.date ?? "",
    sales_id: props.filters?.sales_id ?? "",
    status: props.filters?.status ?? "",
    search: props.filters?.search ?? "",
});

/*
|--------------------------------------------------------------------------
| Sync Filters From Backend
|--------------------------------------------------------------------------
*/

watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            date: newFilters?.date ?? "",
            sales_id: newFilters?.sales_id ?? "",
            status: newFilters?.status ?? "",
            search: newFilters?.search ?? "",
        };
    },
    {
        deep: true,
    },
);

/*
|--------------------------------------------------------------------------
| Visit Data
|--------------------------------------------------------------------------
*/

const visitData = computed(() => {
    return props.visits?.data ?? [];
});

/*
|--------------------------------------------------------------------------
| Apply Filters
|--------------------------------------------------------------------------
*/

function applyFilters(filters = localFilters.value, page = 1) {
    const query = {
        page,
    };

    if (filters.date) {
        query.date = filters.date;
    }

    if (filters.sales_id) {
        query.sales_id = filters.sales_id;
    }

    if (filters.status) {
        query.status = filters.status;
    }

    if (filters.search) {
        query.search = filters.search;
    }

    router.get(route("visit-reports"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

/*
|--------------------------------------------------------------------------
| Filter Changed
|--------------------------------------------------------------------------
*/

function handleFilterChange(newFilters) {
    localFilters.value = {
        ...localFilters.value,
        ...newFilters,
    };

    selectedVisit.value = null;

    applyFilters(localFilters.value, 1);
}

/*
|--------------------------------------------------------------------------
| Reset Filters
|--------------------------------------------------------------------------
*/

function resetFilters() {
    localFilters.value = {
        date: new Date().toISOString().split("T")[0],
        sales_id: "",
        status: "",
        search: "",
    };

    selectedVisit.value = null;

    applyFilters(localFilters.value, 1);
}

/*
|--------------------------------------------------------------------------
| Show All Time
|--------------------------------------------------------------------------
*/

function showAllTime() {
    localFilters.value = {
        ...localFilters.value,
        date: "",
    };

    selectedVisit.value = null;

    applyFilters(localFilters.value, 1);
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

function changePage(page) {
    const lastPage = props.visits?.last_page ?? 1;

    if (page < 1 || page > lastPage) {
        return;
    }

    selectedVisit.value = null;

    applyFilters(localFilters.value, page);
}

/*
|--------------------------------------------------------------------------
| Inspection Drawer
|--------------------------------------------------------------------------
*/

function selectVisit(visit) {
    selectedVisit.value = visit;
}

function closeDrawer() {
    selectedVisit.value = null;
}

/*
|--------------------------------------------------------------------------
| Header Actions
|--------------------------------------------------------------------------
*/

function exportReports() {
    console.log("Export Visit Reports");
}

function generateAuditReport() {
    console.log("Generate Audit Report");
}
</script>

<template>
    <main class="relative w-full min-h-screen bg-background">
        <div class="flex flex-col w-full">
            <div
                class="w-full max-w-[1720px] mx-auto p-space-xl flex flex-col gap-space-lg"
            >
                <!-- ==================================================== -->
                <!-- HEADER -->
                <!-- ==================================================== -->

                <VisitReportHeader
                    :statistics="statistics"
                    @export="exportReports"
                    @audit="generateAuditReport"
                />

                <!-- ==================================================== -->
                <!-- FILTERS -->
                <!-- ==================================================== -->

                <VisitReportFilters
                    :filters="localFilters"
                    :sales="sales"
                    :statistics="statistics"
                    @update:filters="handleFilterChange"
                    @reset="resetFilters"
                    @all-time="showAllTime"
                />

                <!-- ==================================================== -->
                <!-- TABLE + INSPECTION DRAWER -->
                <!-- ==================================================== -->

                <div
                    class="relative flex flex-col xl:flex-row items-start gap-space-lg w-full"
                >
                    <!-- Table -->

                    <div class="flex-1 min-w-0 w-full">
                        <VisitReportTable
                            :visits="visitData"
                            :pagination="visits"
                            :statistics="statistics"
                            :selected-id="selectedVisit?.id"
                            @select="selectVisit"
                            @page-change="changePage"
                        />
                    </div>

                    <!-- Inspection Drawer -->

                    <Transition name="inspection">
                        <VisitInspectionDrawer
                            v-if="selectedVisit"
                            :key="selectedVisit.id"
                            :visit="selectedVisit"
                            @close="closeDrawer"
                        />
                    </Transition>
                </div>
            </div>
        </div>
    </main>
</template>

<style scoped>
.inspection-enter-active,
.inspection-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.25s ease;
}

.inspection-enter-from,
.inspection-leave-to {
    opacity: 0;
    transform: translateX(24px);
}
</style>
