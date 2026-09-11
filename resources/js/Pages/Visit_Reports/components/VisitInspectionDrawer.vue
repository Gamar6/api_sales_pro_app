<script setup>
import { computed } from "vue";

import VisitPhotoEvidence from "./VisitPhotoEvidence.vue";
import VisitTelemetry from "./VisitTelemetry.vue";

const props = defineProps({
    visit: {
        type: Object,
        required: true,
    },
});

defineEmits(["close"]);

const duration = computed(() => {
    const seconds = props.visit.duration_seconds;

    if (!seconds) {
        return props.visit.status === "IN_VISIT" ? "In Progress" : "-";
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${minutes}m ${String(remainingSeconds).padStart(2, "0")}s`;
});

const photos = computed(() => {
    const reportPhotos = props.visit.report?.photos;

    if (!Array.isArray(reportPhotos)) {
        return [];
    }

    return reportPhotos
        .map((photo, index) => {
            if (typeof photo === "string") {
                return {
                    id: index,
                    label: `Photo ${index + 1}`,
                    image: photo,
                };
            }

            return {
                id: photo.id ?? index,
                label: photo.label ?? `Photo ${index + 1}`,
                image: photo.image ?? photo.url ?? "",
            };
        })
        .filter((photo) => photo.image);
});

const statusConfig = computed(() => {
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
            class: "bg-error-container text-on-error-container",
            dot: "bg-error",
        },
    };

    return (
        configs[props.visit.status] ?? {
            label: props.visit.status ?? "Unknown",
            class: "bg-surface-container text-secondary",
            dot: "bg-secondary",
        }
    );
});

const storeName = computed(() => {
    return (
        props.visit.partner?.display_name ??
        props.visit.partner?.name ??
        "Unknown Store"
    );
});

const storeAddress = computed(() => {
    const partner = props.visit.partner;

    if (!partner) {
        return "Address unavailable";
    }

    if (partner.address) {
        return partner.address;
    }

    const parts = [
        partner.street,
        partner.street2,
        partner.city,
        partner.state,
        partner.zip,
        partner.country,
    ].filter(Boolean);

    return parts.length ? parts.join(", ") : "Address unavailable";
});
</script>

<template>
    <aside
        class="w-full xl:w-[480px] xl:sticky xl:top-6 xl:h-[calc(100vh-3rem)] shrink-0 bg-surface-container-lowest rounded-xl shadow-xl flex flex-col overflow-hidden"
    >
        <!-- ============================================================ -->
        <!-- HEADER -->
        <!-- ============================================================ -->


        <div
            class="shrink-0 p-space-base bg-primary text-on-primary flex items-start justify-between gap-space-base"
        >
            <div class="flex flex-col gap-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span
                        class="px-2 py-0.5 rounded bg-tertiary-container text-on-primary font-code-metric text-[11px] font-bold"
                    >
                        Visit #{{ visit.id }}
                    </span>

                    <span
                        class="text-label-caps text-surface-variant uppercase truncate"
                    >
                        {{ statusConfig.label }}
                    </span>
                </div>

                <h2
                    class="font-headline-sm text-headline-sm font-bold text-on-primary leading-tight truncate"
                    :title="storeName"
                >
                    {{ storeName }}
                </h2>

                <div
                    class="flex flex-wrap items-center gap-1.5 text-on-primary-container text-body-sm"
                >
                    <span class="material-symbols-outlined text-[16px]">
                        account_circle
                    </span>

                    <span>
                        {{ visit.sales?.name ?? "Unknown Sales" }}
                    </span>

                    <span>•</span>

                    <span> Check-in {{ visit.check_in_at ?? "-" }} </span>
                </div>
            </div>

            <button
                class="p-1 rounded shrink-0 bg-primary-container text-on-primary hover:bg-primary/50 transition-colors"
                title="Close inspection"
                @click="$emit('close')"
            >
                <span class="material-symbols-outlined text-[20px]">
                    close
                </span>
            </button>
        </div>

        <!-- ============================================================ -->
        <!-- STORE INFORMATION -->
        <!-- ============================================================ -->

        <div
            class="shrink-0 p-space-sm px-space-base bg-surface-container-highest border-b border-outline-variant/20"
        >
            <div class="flex items-center gap-space-xs">
                <div
                    class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center shrink-0"
                >
                    <span class="material-symbols-outlined text-[19px]">
                        store
                    </span>
                </div>

                <div class="flex flex-col min-w-0">
                    <span class="font-title-md text-primary font-bold truncate">
                        {{ storeName }}
                    </span>

                    <span
                        class="text-[11px] text-secondary truncate"
                        :title="storeAddress"
                    >
                        {{ storeAddress }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- SCROLLABLE BODY -->
        <!-- ============================================================ -->

        <div
            class="flex-1 min-h-0 p-space-base flex flex-col gap-space-lg overflow-y-auto overscroll-contain"
        >
            <!-- Photos -->

            <VisitPhotoEvidence v-if="photos.length" :photos="photos" />

            <!-- Telemetry -->

            <VisitTelemetry :visit="visit" :duration="duration" />

            <!-- ======================================================== -->
            <!-- REPORT -->
            <!-- ======================================================== -->

            <div v-if="visit.report" class="flex flex-col gap-space-base">
                <!-- PIC -->

                <div v-if="visit.report.pic_name" class="flex flex-col gap-1">
                    <span
                        class="font-label-caps text-label-caps uppercase text-secondary tracking-wider"
                    >
                        Store PIC
                    </span>

                    <span class="font-title-md text-primary font-semibold">
                        {{ visit.report.pic_name }}
                    </span>
                </div>

                <!-- Activities -->

                <div
                    v-if="visit.report.activities?.length"
                    class="flex flex-col gap-2"
                >
                    <span
                        class="font-label-caps text-label-caps uppercase text-secondary tracking-wider"
                    >
                        Activities
                    </span>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="activity in visit.report.activities"
                            :key="activity"
                            class="px-2.5 py-1 rounded-full bg-surface-container-high text-primary text-[11px] font-medium"
                        >
                            {{ activity }}
                        </span>
                    </div>
                </div>

                <!-- Stock -->

                <div
                    v-if="
                        visit.report.stock_percentage !== null ||
                        visit.report.stock_pcs !== null
                    "
                    class="grid grid-cols-2 gap-space-sm"
                >
                    <div class="p-space-sm rounded bg-surface-container-low">
                        <span class="text-[11px] text-secondary">
                            Stock Availability
                        </span>

                        <div class="font-headline-sm text-primary font-bold">
                            {{ visit.report.stock_percentage ?? "-" }}%
                        </div>
                    </div>

                    <div class="p-space-sm rounded bg-surface-container-low">
                        <span class="text-[11px] text-secondary">
                            Stock Quantity
                        </span>

                        <div class="font-headline-sm text-primary font-bold">
                            {{ visit.report.stock_pcs ?? "-" }} pcs
                        </div>
                    </div>
                </div>

                <!-- Notes -->

                <div v-if="visit.report.notes" class="flex flex-col gap-1.5">
                    <span
                        class="font-label-caps text-label-caps uppercase text-secondary tracking-wider"
                    >
                        Representative Notes
                    </span>

                    <div
                        class="p-space-base rounded bg-surface-container-low text-on-surface font-body-md leading-relaxed shadow-sm"
                    >
                        “{{ visit.report.notes }}”
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- NO REPORT -->
            <!-- ======================================================== -->

            <div
                v-else
                class="p-space-base rounded bg-surface-container-low text-secondary text-center"
            >
                <span class="material-symbols-outlined text-[28px] mb-2">
                    description
                </span>

                <p>No visit report has been submitted yet.</p>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STICKY FOOTER -->
        <!-- ============================================================ -->

        <div
            class="shrink-0 p-space-base bg-surface-container-low border-t border-outline-variant/20 shadow-[0_-4px_12px_rgba(0,0,0,0.04)] flex items-center justify-between gap-space-base"
        >
            <div class="flex flex-col min-w-0">
                <span class="text-[11px] text-secondary"> Visit Duration </span>

                <span class="font-code-metric font-bold text-primary">
                    {{ duration }}
                </span>
            </div>

            <span
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full font-code-metric text-[11px] font-semibold"
                :class="statusConfig.class"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="statusConfig.dot"
                />

                {{ statusConfig.label }}
            </span>
        </div>
    </aside>
</template>
