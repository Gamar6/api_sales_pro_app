<script setup>
import VisitReportRow from "./VisitReportRow.vue";
import VisitPagination from "./VisitPagination.vue";

defineProps({
    visits: {
        type: Array,
        default: () => [],
    },

    pagination: {
        type: Object,
        default: () => ({}),
    },

    statistics: {
        type: Object,
        default: () => ({}),
    },

    selectedId: {
        type: [Number, String],

        default: null,
    },
});

defineEmits(["select", "page-change"]);
</script>

<template>
    <div
        class="w-full bg-surface-container-lowest rounded-xl shadow-md overflow-hidden flex flex-col"
    >
        <!-- Statistics -->

        <div
            class="px-space-base py-space-sm bg-surface-container flex flex-wrap items-center justify-between gap-space-sm text-on-surface-variant"
        >
            <div class="flex flex-wrap items-center gap-space-md">
                <span
                    class="font-label-caps text-label-caps uppercase tracking-wider text-secondary"
                >
                    Total:

                    <strong class="text-primary font-code-metric">
                        {{ statistics.total_visits ?? 0 }} Visits
                    </strong>
                </span>

                <span class="h-3 w-px bg-outline-variant" />

                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                >
                    Completed:

                    <strong class="text-primary font-code-metric">
                        {{ statistics.completed_visits ?? 0 }}
                    </strong>
                </span>

                <span class="h-3 w-px bg-outline-variant" />

                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                >
                    Active:

                    <strong class="text-primary font-code-metric">
                        {{ statistics.active_visits ?? 0 }}
                    </strong>
                </span>

                <span class="h-3 w-px bg-outline-variant" />

                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                >
                    Avg Duration:

                    <strong class="text-primary font-code-metric">
                        {{ statistics.average_duration ?? "0m 00s" }}
                    </strong>
                </span>
            </div>

            <span class="font-code-metric text-code-metric text-secondary">
                Viewing {{ visits.length }} records
            </span>
        </div>

        <!-- Table -->

        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-primary text-on-primary">
                        <th class="py-space-sm px-space-base">Date & Time</th>

                        <th class="py-space-sm px-space-base">Sales Agent</th>

                        <th class="py-space-sm px-space-base">Store / Retail</th>

                        <th class="py-space-sm px-space-base">
                            Visit Duration
                        </th>

                        <th class="py-space-sm px-space-base">Activities</th>

                        <th class="py-space-sm px-space-base">Status</th>
                        
                        <th class="py-space-sm px-space-base">Report</th>

                        <th class="py-space-sm px-space-base text-right">
                            Details
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <VisitReportRow
                        v-for="visit in visits"
                        :key="visit.id"
                        :visit="visit"
                        :selected="selectedId === visit.id"
                        @click="$emit('select', visit)"
                    />

                    <tr v-if="!visits.length">
                        <td
                            colspan="8"
                            class="py-16 text-center text-secondary"
                        >
                            <div class="flex flex-col items-center gap-3">
                                <span
                                    class="material-symbols-outlined text-[40px]"
                                >
                                    event_busy
                                </span>

                                <span> No visit reports found. </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <VisitPagination
            :pagination="pagination"
            :average-duration="statistics.average_duration"
            @page-change="$emit('page-change', $event)"
        />
    </div>
</template>
