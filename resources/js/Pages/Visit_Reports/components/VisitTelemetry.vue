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
        <div
            class="flex items-center justify-between"
        >
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

    <div
        class="grid grid-cols-2 gap-space-base"
    >
        <!-- Visit Date -->

        <div class="flex flex-col">
            <span
                class="text-[11px] text-secondary"
            >
                Visit Date
            </span>

            <span
                class="font-code-metric font-bold text-primary"
            >
                {{ visit.visit_date ?? "-" }}
            </span>
        </div>

        <!-- Check In -->

        <div class="flex flex-col">
            <span
                class="text-[11px] text-secondary"
            >
                Check-in
            </span>

            <span
                class="font-code-metric font-bold text-primary"
            >
                {{ visit.check_in_at ?? "-" }}
            </span>
        </div>

        <!-- Check Out -->

        <div class="flex flex-col">
            <span
                class="text-[11px] text-secondary"
            >
                Check-out
            </span>

            <span
                class="font-code-metric font-bold text-primary"
            >
                {{ visit.check_out_at ?? "Not checked out" }}
            </span>
        </div>

        <!-- Duration -->

        <div class="flex flex-col">
            <span
                class="text-[11px] text-secondary"
            >
                Total Duration
            </span>

            <span
                class="font-code-metric font-bold text-primary"
            >
                {{ duration }}
            </span>
        </div>

        <!-- Partner ID -->

        <div class="flex flex-col col-span-2">
            <span
                class="text-[11px] text-secondary"
            >
                Odoo Partner ID
            </span>

            <span
                class="font-code-metric font-bold text-primary"
            >
                #{{ visit.odoo_partner_id }}
            </span>
        </div>
    </div>
</div>

</template>