<script setup>
import { computed } from "vue";
import ExceptionMap from "./ExceptionMap.vue";

const props = defineProps({
    exception: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close"]);

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Date(date.replace(" ", "T")).toLocaleString("id-ID", {
        dateStyle: "full",
        timeStyle: "short",
    });
};

const formatDistance = (distance) => {
    const value = Number(distance);

    if (!Number.isFinite(value)) {
        return "-";
    }

    if (value >= 1000) {
        return `${(value / 1000).toFixed(2)} km`;
    }

    return `${Math.round(value)} m`;
};

const mapUrl = computed(() => {
    if (!props.exception) {
        return "#";
    }

    const latitude = props.exception.sales_latitude;
    const longitude = props.exception.sales_longitude;

    return `https://www.google.com/maps?q=${latitude},${longitude}`;
});
</script>

<template>
    <div
        v-if="exception"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
        @click.self="emit('close')"
    >
        <div
            class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-6 py-5"
            >
                <div>
                    <h2 class="text-lg font-bold text-slate-800">
                        Visit Exception Detail
                    </h2>

                    <p class="mt-1 text-xs text-slate-400">
                        Report #{{ exception.id }}
                    </p>
                </div>

                <button
                    type="button"
                    class="text-xl text-slate-400 transition hover:text-slate-700"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <div class="space-y-6 p-6">
                <section>
                    <h3
                        class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-400"
                    >
                        Informasi Sales
                    </h3>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="font-semibold text-slate-700">
                            {{ exception.sales.name }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ exception.sales.username }}
                        </p>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-400"
                    >
                        Informasi Store
                    </h3>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="font-semibold text-slate-700">
                            {{ exception.store.name }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ exception.store.address }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ exception.store.city }}
                        </p>
                    </div>
                </section>

                <section>
                    <h3
                        class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-400"
                    >
                        GPS Information
                    </h3>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-100 p-4">
                            <p class="text-xs text-slate-400">Latitude</p>

                            <p class="mt-1 font-semibold text-slate-700">
                                {{ exception.sales_latitude ?? "-" }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-4">
                            <p class="text-xs text-slate-400">Longitude</p>

                            <p class="mt-1 font-semibold text-slate-700">
                                {{ exception.sales_longitude ?? "-" }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-4">
                            <p class="text-xs text-slate-400">GPS Accuracy</p>

                            <p class="mt-1 font-semibold text-slate-700">
                                {{
                                    exception.sales_accuracy
                                        ? `${Number(exception.sales_accuracy).toFixed(2)} m`
                                        : "-"
                                }}
                            </p>
                        </div>

                        <div
                            class="rounded-xl border border-orange-100 bg-orange-50 p-4"
                        >
                            <p class="text-xs text-orange-600">
                                Distance from Store
                            </p>

                            <p class="mt-1 font-semibold text-orange-700">
                                {{
                                    formatDistance(
                                        exception.distance_from_store,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </section>

                <ExceptionMap :exception="exception" />

                <section>
                    <h3
                        class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-400"
                    >
                        Waktu
                    </h3>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-400">
                            Location Captured At
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ formatDate(exception.location_captured_at) }}
                        </p>
                    </div>
                </section>

                <a
                    :href="mapUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block w-full rounded-xl bg-[#1C467F] px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-[#15365f]"
                >
                    Buka Lokasi di Google Maps
                </a>
            </div>
        </div>
    </div>
</template>
