<script setup>
defineProps({
    visit: {
        type: Object,
        required: true,
    },

    duration: {
        type: String,
        default: "-",
    },
});
</script>

<template>
    <div
        class="bg-surface-container-low rounded p-space-base flex flex-col gap-space-sm shadow-sm"
    >
        <div class="flex items-center justify-between">
            <span
                class="font-label-caps text-label-caps uppercase text-secondary tracking-wider"
            >
                Visit Information
            </span>

            <span
                class="inline-flex items-center gap-1 text-[11px] font-code-metric text-primary font-semibold"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="
                        visit.status === 'COMPLETED'
                            ? 'bg-emerald-600'
                            : visit.status === 'IN_VISIT'
                              ? 'bg-blue-600'
                              : 'bg-error'
                    "
                />

                {{ visit.status }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-space-base">
            <!-- Visit Date -->

            <div class="flex flex-col">
                <span class="text-[11px] text-secondary"> Visit Date </span>

                <span class="font-code-metric font-bold text-primary">
                    {{ visit.visit_date ?? "-" }}
                </span>
            </div>

            <!-- Check In -->

            <div class="flex flex-col">
                <span class="text-[11px] text-secondary"> Check-in </span>

                <span class="font-code-metric font-bold text-primary">
                    {{ visit.check_in_at ?? "-" }}
                </span>
            </div>

            <!-- Check Out -->

            <div class="flex flex-col">
                <span class="text-[11px] text-secondary"> Check-out </span>

                <span class="font-code-metric font-bold text-primary">
                    {{ visit.check_out_at ?? "Not checked out" }}
                </span>
            </div>

            <!-- Duration -->

            <div class="flex flex-col">
                <span class="text-[11px] text-secondary"> Total Duration </span>

                <span class="font-code-metric font-bold text-primary">
                    {{ duration }}
                </span>
            </div>

            <!-- Partner ID -->

            <div class="flex flex-col col-span-2">
                <span class="text-[11px] text-secondary">
                    Odoo Partner ID
                </span>

                <span class="font-code-metric font-bold text-primary">
                    #{{ visit.odoo_partner_id }}
                </span>
            </div>

            <!-- GPS Status -->
            <div
                class="col-span-2 border-t border-outline-variant pt-space-base"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] text-secondary">
                        Geofence Status
                    </span>

                    <span
                        class="rounded-full px-2 py-1 text-[10px] font-bold"
                        :class="
                            visit.report?.is_outside_radius
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-emerald-100 text-emerald-700'
                        "
                    >
                        {{
                            visit.report?.is_outside_radius
                                ? "Outside Radius"
                                : "Within Radius"
                        }}
                    </span>
                </div>
            </div>

            <!-- Distance -->
            <div class="flex flex-col">
                <span class="text-[11px] text-secondary">
                    Distance from Store
                </span>

                <span class="font-code-metric font-bold text-primary">
                    {{
                        visit.report?.distance_from_store != null
                            ? `${Number(visit.report.distance_from_store).toFixed(2)} m`
                            : "-"
                    }}
                </span>
            </div>

            <!-- Accuracy -->
            <div class="flex flex-col">
                <span class="text-[11px] text-secondary"> GPS Accuracy </span>

                <span class="font-code-metric font-bold text-primary">
                    {{
                        visit.report?.sales_accuracy != null
                            ? `${visit.report.sales_accuracy} m`
                            : "-"
                    }}
                </span>
            </div>

            <!-- Coordinates -->
            <div class="col-span-2 flex flex-col">
                <span class="text-[11px] text-secondary">
                    Sales Coordinates
                </span>

                <span
                    class="break-all font-code-metric text-xs font-bold text-primary"
                >
                    {{
                        visit.report?.sales_latitude != null &&
                        visit.report?.sales_longitude != null
                            ? `${visit.report.sales_latitude}, ${visit.report.sales_longitude}`
                            : "-"
                    }}
                </span>
            </div>

            <!-- Captured At -->
            <div class="col-span-2 flex flex-col">
                <span class="text-[11px] text-secondary">
                    Location Captured At
                </span>

                <span class="font-code-metric text-xs font-bold text-primary">
                    {{ visit.report?.location_captured_at ?? "-" }}
                </span>
            </div>
        </div>
    </div>
</template>
