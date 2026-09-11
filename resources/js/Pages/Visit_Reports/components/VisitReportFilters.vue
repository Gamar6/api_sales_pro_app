<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },

    sales: {
        type: Array,
        default: () => [],
    },

    statistics: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits([
    "update:filters",
    "reset",
]);

const showDatePicker = ref(false);
const showSalesPicker = ref(false);
const showStatusPicker = ref(false);

const localFilters = computed({
    get() {
        return props.filters;
    },

    set(value) {
        emit("update:filters", value);
    },
});

/*
|--------------------------------------------------------------------------
| DATE
|--------------------------------------------------------------------------
*/

const todayDate = new Date()
    .toISOString()
    .split("T")[0];

const formattedDate = computed(() => {
    if (!localFilters.value.date) {
        return "All Time";
    }

    const date = new Date(
        `${localFilters.value.date}T00:00:00`,
    );

    return new Intl.DateTimeFormat("en-US", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(date);
});

function updateFilters(data) {
    localFilters.value = {
        ...localFilters.value,
        ...data,
    };
}

function setToday() {
    updateFilters({
        date: todayDate,
        quick_filter: "today",
    });

    showDatePicker.value = false;
}

function setAllTime() {
    updateFilters({
        date: null,
        quick_filter: "all_time",
    });

    showDatePicker.value = false;
}

function setDate(date) {
    if (!date) {
        return;
    }

    updateFilters({
        date,
        quick_filter: "custom_date",
    });

    showDatePicker.value = false;
}

/*
|--------------------------------------------------------------------------
| SALES
|--------------------------------------------------------------------------
*/

const selectedSalesLabel = computed(() => {
    if (!localFilters.value.sales_id) {
        return `All Reps (${props.sales.length})`;
    }

    const selectedSales = props.sales.find(
        (salesUser) =>
            String(salesUser.id) ===
            String(localFilters.value.sales_id),
    );

    return selectedSales?.name ?? "Selected Rep";
});

function setSales(salesId) {
    updateFilters({
        sales_id: salesId || null,
    });

    showSalesPicker.value = false;
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

const selectedStatusLabel = computed(() => {
    const status = localFilters.value.status;

    if (!status) {
        return "Category: All";
    }

    const labels = {
        COMPLETED: "Completed",
        IN_VISIT: "Active Visit",
        CANCELLED: "Cancelled",
        PENDING: "Pending Sync",
    };

    return labels[status] ?? "Category: All";
});

function setStatus(status) {
    updateFilters({
        status: status || null,
    });

    showStatusPicker.value = false;
}

/*
|--------------------------------------------------------------------------
| QUICK FILTER
|--------------------------------------------------------------------------
*/

function setQuickFilter(type) {
    if (type === "all") {
        updateFilters({
            status: null,
            quick_filter: "all",
        });

        return;
    }

    if (type === "flagged") {
        updateFilters({
            status: "CANCELLED",
            quick_filter: "flagged",
        });

        return;
    }

    if (type === "pending") {
        updateFilters({
            status: "PENDING",
            quick_filter: "pending",
        });
    }
}

/*
|--------------------------------------------------------------------------
| RESET
|--------------------------------------------------------------------------
*/

function resetFilters() {
    showDatePicker.value = false;
    showSalesPicker.value = false;
    showStatusPicker.value = false;

    emit("reset");
}
</script>

<template>
    <div
        class="bg-surface-container-lowest rounded-xl p-space-base shadow-sm flex flex-wrap items-center gap-space-base justify-between"
    >
        <!-- LEFT FILTERS -->
        <div
            class="flex flex-wrap items-center gap-space-sm flex-1"
        >
            <!-- DATE -->
            <div class="relative">
                <button
                    type="button"
                    class="min-w-[210px] flex items-center justify-between bg-surface-container-low px-space-sm py-1.5 rounded hover:bg-surface-container transition-colors"
                    @click="showDatePicker = !showDatePicker"
                >
                    <div
                        class="flex items-center gap-space-xs"
                    >
                        <span
                            class="material-symbols-outlined text-secondary text-[18px]"
                        >
                            calendar_today
                        </span>

                    <span
                        class="font-body-md text-body-md font-semibold text-primary"
                    >
                        {{ formattedDate }}
                    </span>
                </div>

                <span
                    class="material-symbols-outlined text-secondary text-[16px]"
                >
                    {{
                        showDatePicker
                            ? "expand_less"
                            : "arrow_drop_down"
                    }}
                </span>
            </button>

            <!-- DATE DROPDOWN -->
            <div
                v-if="showDatePicker"
                class="absolute z-50 mt-2 left-0 w-[260px] bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant overflow-hidden"
            >
                <!-- TODAY -->
                <button
                    type="button"
                    class="w-full flex items-center gap-2 px-4 py-3 text-left hover:bg-surface-container-low transition-colors"
                    :class="
                        localFilters.quick_filter === 'today'
                            ? 'bg-surface-container-low text-primary font-bold'
                            : 'text-on-surface'
                    "
                    @click="setToday"
                >
                    <span
                        class="material-symbols-outlined text-[18px]"
                    >
                        today
                    </span>

                    <span>Today</span>
                </button>

                <!-- ALL TIME -->
                <button
                    type="button"
                    class="w-full flex items-center gap-2 px-4 py-3 text-left hover:bg-surface-container-low transition-colors"
                    :class="
                        localFilters.quick_filter === 'all_time'
                            ? 'bg-surface-container-low text-primary font-bold'
                            : 'text-on-surface'
                    "
                    @click="setAllTime"
                >
                    <span
                        class="material-symbols-outlined text-[18px]"
                    >
                        calendar_month
                    </span>

                    <span>All Time</span>
                </button>

                <div
                    class="h-px bg-outline-variant"
                />

                <!-- CUSTOM DATE -->
                <div class="p-3">
                    <span
                        class="block mb-2 text-[11px] uppercase tracking-wider text-secondary font-semibold"
                    >
                        Select Date
                    </span>

                    <input
                        type="date"
                        :value="localFilters.date || ''"
                        class="w-full bg-surface-container-low rounded px-3 py-2 text-primary outline-none"
                        @change="setDate($event.target.value)"
                    />
                </div>
            </div>
        </div>

        <!-- SALES -->
        <div class="relative">
            <button
                type="button"
                class="min-w-[200px] flex items-center justify-between bg-surface-container-low px-space-sm py-1.5 rounded hover:bg-surface-container transition-colors"
                @click="showSalesPicker = !showSalesPicker"
            >
                <div
                    class="flex items-center gap-space-xs"
                >
                    <span
                        class="material-symbols-outlined text-secondary text-[18px]"
                    >
                        group
                    </span>

                    <span
                        class="font-body-md text-body-md text-on-surface"
                    >
                        {{ selectedSalesLabel }}
                    </span>
                </div>

                <span
                    class="material-symbols-outlined"
                >
                    {{
                        showSalesPicker
                            ? "expand_less"
                            : "expand_more"
                    }}
                </span>
            </button>

            <div
                v-if="showSalesPicker"
                class="absolute z-50 mt-2 left-0 min-w-[240px] max-h-[280px] overflow-y-auto bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant py-2"
            >
                <button
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setSales(null)"
                >
                    All Reps
                </button>

                <button
                    v-for="salesUser in sales"
                    :key="salesUser.id"
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setSales(salesUser.id)"
                >
                    {{ salesUser.name }}
                </button>
            </div>
        </div>

        <!-- STATUS -->
        <div class="relative">
            <button
                type="button"
                class="min-w-[190px] flex items-center justify-between bg-surface-container-low px-space-sm py-1.5 rounded hover:bg-surface-container transition-colors"
                @click="showStatusPicker = !showStatusPicker"
            >
                <div
                    class="flex items-center gap-space-xs"
                >
                    <span class="material-symbols-outlined">
                        category
                    </span>

                    <span class="font-body-md text-body-md">
                        {{ selectedStatusLabel }}
                    </span>
                </div>

                <span
                    class="material-symbols-outlined"
                >
                    {{
                        showStatusPicker
                            ? "expand_less"
                            : "expand_more"
                    }}
                </span>
            </button>

            <div
                v-if="showStatusPicker"
                class="absolute z-50 mt-2 left-0 min-w-[220px] bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant py-2"
            >
                <button
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setStatus(null)"
                >
                    All Categories
                </button>

                <button
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setStatus('COMPLETED')"
                >
                    Completed
                </button>

                <button
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setStatus('IN_VISIT')"
                >
                    Active Visit
                </button>

                <button
                    type="button"
                    class="w-full text-left px-4 py-2 hover:bg-surface-container-low"
                    @click="setStatus('CANCELLED')"
                >
                    Cancelled
                </button>
            </div>
        </div>

        <!-- QUICK FILTER -->
        <div
            class="flex items-center gap-1.5 ml-2"
        >
            <button
                type="button"
                class="px-2.5 py-1 rounded-full font-label-caps text-label-caps uppercase"
                :class="
                    !localFilters.quick_filter ||
                    localFilters.quick_filter === 'all'
                        ? 'bg-primary text-on-primary'
                        : 'bg-surface-container text-secondary'
                "
                @click="setQuickFilter('all')"
            >
                All ({{ statistics.total_visits ?? 0 }})
            </button>

            <button
                type="button"
                class="px-2.5 py-1 rounded-full font-label-caps text-label-caps uppercase"
                :class="
                    localFilters.quick_filter === 'flagged'
                        ? 'bg-primary text-on-primary'
                        : 'bg-surface-container text-secondary'
                "
                @click="setQuickFilter('flagged')"
            >
                Flagged
            </button>

            <button
                type="button"
                class="px-2.5 py-1 rounded-full bg-surface-container text-secondary font-label-caps text-label-caps uppercase"
                :class="
                    localFilters.quick_filter === 'pending'
                        ? 'bg-primary text-on-primary'
                        : ''
                "
                @click="setQuickFilter('pending')"
            >
                Pending Sync
            </button>
        </div>
    </div>

    <!-- RIGHT ACTION -->
    <div class="flex items-center gap-space-sm">

        <!-- RESET -->
        <button
            type="button"
            class="p-2 rounded bg-surface-container-low text-secondary hover:text-primary"
            @click="resetFilters"
        >
            <span class="material-symbols-outlined">
                restart_alt
            </span>
        </button>

        <!-- VIEW MODE -->
        <div
            class="flex items-center gap-1 bg-surface-container-low p-1 rounded"
        >
            <button
                type="button"
                class="p-1 bg-surface-container-lowest rounded shadow-sm text-primary"
            >
                <span class="material-symbols-outlined">
                    table_rows
                </span>
            </button>

            <button
                type="button"
                class="p-1 text-secondary"
            >
                <span class="material-symbols-outlined">
                    map
                </span>
            </button>
        </div>
    </div>
</div>

</template>
