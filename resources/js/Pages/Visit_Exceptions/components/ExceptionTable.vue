<script setup>
const props = defineProps({
    exceptions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["view"]);

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

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Date(date.replace(" ", "T")).toLocaleString("id-ID", {
        dateStyle: "medium",
        timeStyle: "short",
    });
};

const distanceClass = (distance) => {
    if (Number(distance) >= 1000) {
        return "bg-red-100 text-red-700";
    }

    return "bg-orange-100 text-orange-700";
};
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-800">
                    Exception Records
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Daftar visit yang dilakukan di luar radius store.
                </p>
            </div>

            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                {{ exceptions.length }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-4 font-semibold">
                            Sales
                        </th>

                        <th class="px-5 py-4 font-semibold">
                            Store
                        </th>

                        <th class="px-5 py-4 font-semibold">
                            Distance
                        </th>

                        <th class="px-5 py-4 font-semibold">
                            Accuracy
                        </th>

                        <th class="px-5 py-4 font-semibold">
                            Captured At
                        </th>

                        <th class="px-5 py-4 text-right font-semibold">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="exception in props.exceptions"
                        :key="exception.id"
                        class="transition hover:bg-slate-50"
                    >
                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="font-semibold text-slate-700">
                                {{ exception.sales.name }}
                            </div>

                            <div class="mt-1 text-xs text-slate-400">
                                {{ exception.sales.username }}
                            </div>
                        </td>

                        <td class="max-w-[240px] px-5 py-4">
                            <div class="font-medium text-slate-700">
                                {{ exception.store.name }}
                            </div>

                            <div class="mt-1 text-xs text-slate-400">
                                {{ exception.store.city }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4">
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="distanceClass(exception.distance_from_store)"
                            >
                                {{ formatDistance(exception.distance_from_store) }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                            {{
                                exception.sales_accuracy
                                    ? `${Number(exception.sales_accuracy).toFixed(2)} m`
                                    : "-"
                            }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                            {{ formatDate(exception.location_captured_at) }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <button
                                type="button"
                                class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                @click="emit('view', exception)"
                            >
                                Detail
                            </button>
                        </td>
                    </tr>

                    <tr v-if="exceptions.length === 0">
                        <td
                            colspan="6"
                            class="px-5 py-12 text-center text-sm text-slate-400"
                        >
                            Tidak ada visit exception ditemukan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
