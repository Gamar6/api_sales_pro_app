<script setup>
import { computed } from "vue";

const props = defineProps({
    currentPage: {
        type: Number,
        default: 1,
    },

    totalPages: {
        type: Number,
        default: 1,
    },

    perPage: {
        type: Number,
        default: 12,
    },

    from: {
        type: Number,
        default: 0,
    },

    to: {
        type: Number,
        default: 0,
    },

    total: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits([
    "page",
    "update:per-page",
]);

const pages = computed(() => {
    const total = props.totalPages;
    const current = props.currentPage;

    if (total <= 7) {
        return Array.from(
            { length: total },
            (_, index) => index + 1
        );
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 5, "...", total];
    }

    if (current >= total - 3) {
        return [
            1,
            "...",
            total - 4,
            total - 3,
            total - 2,
            total - 1,
            total,
        ];
    }

    return [
        1,
        "...",
        current - 1,
        current,
        current + 1,
        "...",
        total,
    ];
});
</script>

<template>
    <footer
        class="flex flex-col gap-4 border-t border-outline-variant px-space-xl py-space-base sm:flex-row sm:items-center sm:justify-between"
    >
        <div
            class="flex flex-wrap items-center gap-3 text-xs text-secondary"
        >
            <span>Rows per page</span>

            <select
                :value="perPage"
                class="rounded border border-outline-variant bg-surface-container-low px-2 py-1.5 text-xs text-primary outline-none focus:border-primary"
                @change="
                    emit(
                        'update:per-page',
                        Number($event.target.value)
                    )
                "
            >
                <option :value="12">12</option>
                <option :value="24">24</option>
                <option :value="36">36</option>
                <option :value="48">48</option>
            </select>

            <span>
                Viewing
                <strong class="text-primary">
                    {{ from }}–{{ to }}
                </strong>
                of
                <strong class="text-primary">
                    {{ total }}
                </strong>
            </span>
        </div>

        <div
            class="flex items-center gap-1"
        >
            <button
                type="button"
                title="First page"
                class="flex h-8 w-8 items-center justify-center rounded text-secondary transition hover:bg-surface-container-highest disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="currentPage === 1"
                @click="emit('page', 1)"
            >
                <span
                    class="material-symbols-outlined text-[18px]"
                >
                    first_page
                </span>
            </button>

            <button
                type="button"
                title="Previous"
                class="flex h-8 w-8 items-center justify-center rounded text-secondary transition hover:bg-surface-container-highest disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="currentPage === 1"
                @click="
                    emit(
                        'page',
                        currentPage - 1
                    )
                "
            >
                <span
                    class="material-symbols-outlined text-[18px]"
                >
                    chevron_left
                </span>
            </button>

            <template
                v-for="(page, index) in pages"
                :key="`${page}-${index}`"
            >
                <span
                    v-if="page === '...'"
                    class="flex h-8 w-8 items-center justify-center text-xs text-secondary"
                >
                    …
                </span>

                <button
                    v-else
                    type="button"
                    class="flex h-8 min-w-8 items-center justify-center rounded px-2 text-xs font-bold transition"
                    :class="
                        currentPage === page
                            ? 'bg-primary text-on-primary'
                            : 'text-secondary hover:bg-surface-container-highest hover:text-primary'
                    "
                    @click="emit('page', page)"
                >
                    {{ page }}
                </button>
            </template>

            <button
                type="button"
                title="Next"
                class="flex h-8 w-8 items-center justify-center rounded text-secondary transition hover:bg-surface-container-highest disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="
                    currentPage === totalPages
                "
                @click="
                    emit(
                        'page',
                        currentPage + 1
                    )
                "
            >
                <span
                    class="material-symbols-outlined text-[18px]"
                >
                    chevron_right
                </span>
            </button>

            <button
                type="button"
                title="Last page"
                class="flex h-8 w-8 items-center justify-center rounded text-secondary transition hover:bg-surface-container-highest disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="
                    currentPage === totalPages
                "
                @click="
                    emit(
                        'page',
                        totalPages
                    )
                "
            >
                <span
                    class="material-symbols-outlined text-[18px]"
                >
                    last_page
                </span>
            </button>
        </div>
    </footer>
</template>
