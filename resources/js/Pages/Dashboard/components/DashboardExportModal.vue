<script setup>
import { computed, ref, watch } from "vue";

const emit = defineEmits(["close", "export"]);

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const today = () => new Date().toISOString().slice(0, 10);

const getStartOfWeek = () => {
    const date = new Date();

    const day = date.getDay();
    const diff = day === 0 ? -6 : 1 - day;

    date.setDate(date.getDate() + diff);

    return date.toISOString().slice(0, 10);
};

const getStartOfMonth = () => {
    const date = new Date();

    date.setDate(1);

    return date.toISOString().slice(0, 10);
};

const period = ref("today");

const dateFrom = ref(today());
const dateTo = ref(today());

const errorMessage = ref("");

const periodOptions = [
    {
        value: "today",
        label: "Today",
        description: "Export today's shift audit",
    },
    {
        value: "week",
        label: "This Week",
        description: "From Monday until today",
    },
    {
        value: "month",
        label: "This Month",
        description: "From the first day of this month",
    },
    {
        value: "custom",
        label: "Custom Range",
        description: "Choose your own date range",
    },
];

watch(period, (value) => {
    errorMessage.value = "";

    if (value === "today") {
        dateFrom.value = today();
        dateTo.value = today();
    }

    if (value === "week") {
        dateFrom.value = getStartOfWeek();
        dateTo.value = today();
    }

    if (value === "month") {
        dateFrom.value = getStartOfMonth();
        dateTo.value = today();
    }

    if (value === "custom") {
        dateFrom.value = today();
        dateTo.value = today();
    }
});

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        period.value = "today";
        dateFrom.value = today();
        dateTo.value = today();
        errorMessage.value = "";
    },
);

const selectedPeriod = computed(() => {
    return periodOptions.find(
        (option) => option.value === period.value,
    );
});

const handleExport = () => {
    if (!dateFrom.value || !dateTo.value) {
        errorMessage.value = "Please select both dates.";
        return;
    }

    if (dateFrom.value > dateTo.value) {
        errorMessage.value =
            "Start date cannot be later than end date.";
        return;
    }

    emit("export", {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    });
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="$emit('close')"
        >
            <div
                class="w-full max-w-lg rounded-lg bg-surface-container-lowest p-space-xl shadow-xl"
            >
                <!-- Header -->
                <div class="flex items-start justify-between gap-space-md">
                    <div>
                        <h2
                            class="font-headline-sm text-headline-sm text-primary"
                        >
                            Export Shift Audit
                        </h2>

                        <p
                            class="mt-1 font-body-sm text-body-sm text-secondary"
                        >
                            Choose the period you want to include in the
                            Excel report.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded text-secondary hover:bg-surface-container"
                        @click="$emit('close')"
                    >
                        <span class="material-symbols-outlined">
                            close
                        </span>
                    </button>
                </div>

                <!-- Period Options -->
                <div class="mt-space-lg grid gap-space-sm sm:grid-cols-2">
                    <label
                        v-for="option in periodOptions"
                        :key="option.value"
                        class="cursor-pointer rounded-lg border p-space-md transition"
                        :class="
                            period === option.value
                                ? 'border-primary bg-primary-container/10'
                                : 'border-surface-container hover:bg-surface-container-low'
                        "
                    >
                        <input
                            v-model="period"
                            type="radio"
                            name="export-period"
                            :value="option.value"
                            class="sr-only"
                        />

                        <div class="flex items-start gap-space-sm">
                            <div
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                                :class="
                                    period === option.value
                                        ? 'border-primary'
                                        : 'border-secondary'
                                "
                            >
                                <div
                                    v-if="period === option.value"
                                    class="h-2.5 w-2.5 rounded-full bg-primary"
                                ></div>
                            </div>

                            <div>
                                <p
                                    class="font-title-md text-title-md text-primary"
                                >
                                    {{ option.label }}
                                </p>

                                <p
                                    class="mt-0.5 font-body-sm text-body-sm text-secondary"
                                >
                                    {{ option.description }}
                                </p>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Custom Range -->
                <div
                    v-if="period === 'custom'"
                    class="mt-space-lg rounded-lg bg-surface-container-low p-space-md"
                >
                    <p
                        class="mb-space-sm font-label-caps text-label-caps uppercase text-secondary"
                    >
                        Custom Date Range
                    </p>

                    <div class="grid gap-space-md sm:grid-cols-2">
                        <label class="flex flex-col gap-1">
                            <span
                                class="font-label-md text-label-md text-primary"
                            >
                                From
                            </span>

                            <input
                                v-model="dateFrom"
                                type="date"
                                class="rounded border border-surface-container bg-surface-container-lowest px-space-md py-2 font-body-sm text-body-sm text-primary outline-none focus:border-primary"
                            />
                        </label>

                        <label class="flex flex-col gap-1">
                            <span
                                class="font-label-md text-label-md text-primary"
                            >
                                To
                            </span>

                            <input
                                v-model="dateTo"
                                type="date"
                                class="rounded border border-surface-container bg-surface-container-lowest px-space-md py-2 font-body-sm text-body-sm text-primary outline-none focus:border-primary"
                            />
                        </label>
                    </div>
                </div>

                <!-- Error -->
                <p
                    v-if="errorMessage"
                    class="mt-space-md rounded bg-red-50 px-space-md py-2 font-body-sm text-body-sm text-red-700"
                >
                    {{ errorMessage }}
                </p>

                <!-- Selected Period -->
                <div
                    v-if="selectedPeriod"
                    class="mt-space-lg rounded bg-surface-container-low px-space-md py-2"
                >
                    <div class="flex items-center gap-space-sm">
                        <span
                            class="material-symbols-outlined text-[18px] text-secondary"
                        >
                            calendar_month
                        </span>

                        <span
                            class="font-body-sm text-body-sm text-secondary"
                        >
                            {{ selectedPeriod.label }}
                        </span>

                        <span
                            class="ml-auto font-code-metric text-code-metric font-semibold text-primary"
                        >
                            {{ dateFrom }} → {{ dateTo }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="mt-space-xl flex justify-end gap-space-sm"
                >
                    <button
                        type="button"
                        class="rounded bg-surface-container-low px-space-md py-2 font-title-md text-title-md text-primary hover:bg-surface-container"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded bg-primary-container px-space-md py-2 font-title-md text-title-md text-on-primary hover:bg-primary"
                        @click="handleExport"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            file_download
                        </span>

                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
