<script setup>
import { computed } from "vue";

const props = defineProps({
    statistics: {
        type: Object,
        default: () => ({
            total: 0,
            critical: 0,
            warning: 0,
            average_distance: 0,
        }),
    },
});

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

const cards = computed(() => [
    {
        title: "Total Exceptions",
        value: props.statistics.total,
        description: "Visit di luar radius",
        icon: "⚠️",
        color: "orange",
    },
    {
        title: "Critical Distance",
        value: props.statistics.critical,
        description: "Jarak lebih dari 1 km",
        icon: "🚨",
        color: "red",
    },
    {
        title: "Warning Distance",
        value: props.statistics.warning,
        description: "Jarak 100 m - 1 km",
        icon: "📍",
        color: "yellow",
    },
    {
        title: "Average Distance",
        value: formatDistance(props.statistics.average_distance),
        description: "Rata-rata jarak exception",
        icon: "📏",
        color: "blue",
    },
]);
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="card in cards"
            :key="card.title"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">
                        {{ card.title }}
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-slate-800">
                        {{ card.value }}
                    </h3>
                </div>

                <div class="text-xl">
                    {{ card.icon }}
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-400">
                {{ card.description }}
            </p>
        </div>
    </div>
</template>
