<script setup>
defineEmits(["export"]);
</script>

<template>
    <section
        class="flex flex-col justify-between gap-space-md rounded-lg bg-surface-container-lowest p-space-lg shadow-sm md:flex-row md:items-center"
    >
        <div class="flex items-center gap-space-md">
            <div
                class="flex h-10 w-10 items-center justify-center rounded bg-primary-container text-on-primary"
            >
                <span class="material-symbols-outlined text-[24px]"
                    >satellite_alt</span
                >
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-space-xs">
                    <h1
                        class="font-headline-md text-headline-md leading-tight text-primary"
                    >
                        Field Command Operational Center
                    </h1>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-2 py-0.5 font-label-caps text-label-caps text-tertiary-container"
                        ><span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-on-tertiary-container"
                        ></span
                        >LIVE PING</span
                    >
                </div>
                <p class="font-body-sm text-body-sm text-secondary">
                    Central Territory Node · Active Roster 48 Field Agents · GPS
                    Sampling Interval: 15s
                </p>
            </div>
        </div>
        <div class="flex items-center gap-space-sm self-start md:self-auto">
            <button
                type="button"
                class="flex items-center gap-1.5 rounded bg-surface-container-low px-space-md py-2 font-title-md text-title-md text-primary hover:bg-surface-container"
            >
                <span class="material-symbols-outlined text-[18px]"
                    >calendar_today</span
                >Today (Shift 08:00–17:00)
            </button>
            <button
                type="button"
                class="flex items-center gap-1.5 rounded bg-primary-container px-space-md py-2 font-title-md text-title-md text-on-primary shadow-sm hover:bg-primary"
                @click="$emit('export')"
            >
                <span class="material-symbols-outlined text-[18px]"
                    >file_download</span
                >Export Shift Audit
            </button>
        </div>
    </section>
</template>
