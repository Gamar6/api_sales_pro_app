<script setup>
import { computed } from "vue";

const props = defineProps({
    pagination: {
        type: Object,
        default: () => ({}),
    },

    averageDuration: {
        type: String,
        default: "0m 00s",
    },
});

defineEmits([
    "page-change",
]);

const pages = computed(() => {
    const current =
        props.pagination.current_page ?? 1;

    const last =
        props.pagination.last_page ?? 1;

    const result = [];

    const start = Math.max(
        1,
        current - 2,
    );

    const end = Math.min(
        last,
        current + 2,
    );

    for (
        let page = start;
        page <= end;
        page++
    ) {
        result.push(page);
    }

    return result;
});

const showingText = computed(() => {
    const total =
        props.pagination.total ?? 0;

    const current =
        props.pagination.current_page ?? 1;

    const perPage =
        props.pagination.per_page ?? 10;

    if (!total) {
        return "Showing 0 records";
    }

    const from =
        (current - 1) * perPage + 1;

    const to = Math.min(
        current * perPage,
        total,
    );

    return `Showing ${from}-${to} of ${total} records`;
});
</script>

<template>
    <div
        class="p-space-base bg-surface-container-low flex flex-wrap items-center justify-between gap-space-base"
    >
        <div
            class="flex items-center gap-space-md text-secondary font-body-sm text-body-sm"
        >
            <span>
                {{ showingText }}
            </span>

        <span>
            Avg Visit Duration:

            <strong>
                {{ averageDuration }}
            </strong>
        </span>
    </div>

    <div
        v-if="pagination.last_page > 1"
        class="flex items-center gap-space-xs"
    >
        <!-- Previous -->

        <button
            class="px-3 py-1 rounded"
            :class="
                pagination.current_page === 1
                    ? 'text-secondary opacity-40 cursor-not-allowed'
                    : 'text-primary hover:bg-surface-container-high'
            "
            :disabled="
                pagination.current_page === 1
            "
            @click="
                $emit(
                    'page-change',
                    pagination.current_page - 1
                )
            "
        >
            <span
                class="material-symbols-outlined text-[18px]"
            >
                chevron_left
            </span>
        </button>

        <!-- Pages -->

        <button
            v-for="page in pages"
            :key="page"
            class="px-3 py-1 rounded"
            :class="
                pagination.current_page === page
                    ? 'bg-surface-container-lowest text-primary shadow-sm'
                    : 'text-secondary hover:bg-surface-container-high'
            "
            @click="
                $emit(
                    'page-change',
                    page
                )
            "
        >
            {{ page }}
        </button>

        <!-- Next -->

        <button
            class="px-3 py-1 rounded"
            :class="
                pagination.current_page === pagination.last_page
                    ? 'text-secondary opacity-40 cursor-not-allowed'
                    : 'text-primary hover:bg-surface-container-high'
            "
            :disabled="
                pagination.current_page === pagination.last_page
            "
            @click="
                $emit(
                    'page-change',
                    pagination.current_page + 1
                )
            "
        >
            <span
                class="material-symbols-outlined text-[18px]"
            >
                chevron_right
            </span>
        </button>
    </div>
</div>

</template>
