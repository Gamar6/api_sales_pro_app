<script setup>
import { computed } from "vue";

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },

    selected: {
        type: Boolean,
        default: false,
    },
});

const duration = computed(() => {
    const seconds = props.visit.duration_seconds;

    if (!seconds) {
        return props.visit.status === "IN_VISIT" ? "In Progress" : "-";
    }

    const minutes = Math.floor(seconds / 60);

    const remainingSeconds = seconds % 60;

    return `${minutes}m ${String(remainingSeconds).padStart(2, "0")}s`;
});

const statusConfig = computed(() => {
    const status = props.visit.status;

    const configs = {
        COMPLETED: {
            label: "Completed",
            class: "bg-emerald-50 text-emerald-700",
            dot: "bg-emerald-600",
        },

        IN_VISIT: {
            label: "In Visit",
            class: "bg-blue-50 text-blue-700",
            dot: "bg-blue-600",
        },

        CANCELLED: {
            label: "Cancelled",
            class: "text-red-600 font-code-metric",
            dot: "bg-red-600",
        },
    };

    return (
        configs[status] ?? {
            label: status ?? "Unknown",
            class: "bg-surface-container text-secondary",
            dot: "bg-secondary",
        }
    );
});

const hasReport = computed(() => {
    return !!props.visit.report;
});

const formatDate = (date) => {
    if (!date) return "-";

    return new Date(`${date}T00:00:00`).toLocaleDateString("id-ID", {
        weekday: "long",
        day: "2-digit",
        month: "long",
        year: "numeric",
    });
};

</script>

<template>
    <tr
        class="transition-colors cursor-pointer"
        :class="
            selected
                ? 'bg-surface-container-high'
                : 'bg-surface-container-lowest  hover:bg-surface-container-low'
        "
    >
        <!-- Date & Time -->

        <td class="py-space-sm px-space-base">
            <div class="flex flex-col">

                <span class="font-body-sm text-body-sm text-secondary">
                    {{ formatDate(visit.visit_date) }}
                </span>
            </div>
        </td>

        <!-- Sales -->

        <td class="py-space-sm px-space-base">
            <div class="flex items-center gap-space-sm">
                <img
                    v-if="visit.sales?.profile_photo_url"
                    :src="visit.sales.profile_photo_url"
                    :alt="visit.sales?.name"
                    class="w-8 h-8 rounded-full object-cover shadow-sm"
                />

                <div
                    v-else
                    class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-bold"
                >
                    {{ visit.sales?.name?.charAt(0)?.toUpperCase() ?? "?" }}
                </div>

                <div class="flex flex-col min-w-0">
                    <span
                        class="font-title-md text-title-md truncate"
                        :class="
                            selected
                                ? 'font-bold text-primary'
                                : 'text-on-surface'
                        "
                    >
                        {{ visit.sales?.name ?? "Unknown Sales" }}
                    </span>

                    <span class="font-code-metric text-[11px] text-secondary">
                        {{ visit.sales?.username ?? "-" }}
                    </span>
                </div>
            </div>
        </td>

        <!-- Store -->

        <td class="py-space-sm px-space-base">
            <div class="flex flex-col max-w-[260px]">
                <span
                    class="font-title-md text-title-md text-on-surface font-semibold truncate"
                >
                    {{ visit.partner?.name ?? "Unknown Store" }}
                </span>

                <span
                    class="font-body-sm text-body-sm text-on-surface-variant truncate"
                >
                    {{
                        `${visit.partner?.street2 ?? ""} ${visit.partner?.street ?? ""}`.trim() ||
                        "Address unavailable"
                    }}
                </span>
            </div>
        </td>

        <!-- Duration -->

        <td class="py-space-sm px-space-base">
            <div class="flex flex-col font-code-metric">
                <div class="flex items-center gap-1 text-on-surface">
                    <span>
                        {{ visit.check_in_at ?? "-" }}
                    </span>

                    <span class="text-secondary"> → </span>

                    <span v-if="visit.check_out_at">
                        {{ visit.check_out_at }}
                    </span>

                    <span
                        v-else-if="visit.status === 'IN_VISIT'"
                        class="text-tertiary-container font-semibold"
                    >
                        Active Now
                    </span>

                    <span v-else> - </span>
                </div>

                <span class="text-[11px] text-secondary">
                    Duration: {{ duration }}
                </span>
            </div>
        </td>

        <!-- Status -->

        <td class="py-space-sm px-space-base">
            <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full"
                :class="statusConfig.class"
            >
                <span class="w-2 h-2 rounded-full" :class="statusConfig.dot" />

                <span class="font-code-metric text-[12px] font-semibold">
                    {{ statusConfig.label }}
                </span>
            </div>
        </td>

        <!-- Report -->

        <td class="py-space-sm px-space-base">
            <div
                v-if="hasReport"
                class="flex items-center gap-1.5 text-emerald-700"
            >
                <span class="material-symbols-outlined text-[18px]">
                    description
                </span>

                <span class="font-code-metric text-[11px] font-semibold">
                    Submitted
                </span>
            </div>

            <span
                v-else
                class="px-2 py-0.5 rounded bg-surface-container text-secondary font-code-metric text-[11px]"
            >
                No Report
            </span>
        </td>

        <!-- Action -->

        <td class="py-space-sm px-space-base text-right">
            <button
                class="px-space-sm py-1 rounded font-body-sm flex items-center gap-1 ml-auto"
                :class="
                    selected
                        ? 'bg-primary text-on-primary shadow-sm'
                        : 'text-primary hover:bg-surface-container-high'
                "
            >
                <span>
                    {{ selected ? "Inspecting" : "Inspect" }}
                </span>

                <span class="material-symbols-outlined text-[14px]">
                    {{ selected ? "arrow_forward" : "chevron_right" }}
                </span>
            </button>
            
        </td>
    </tr>
</template>
