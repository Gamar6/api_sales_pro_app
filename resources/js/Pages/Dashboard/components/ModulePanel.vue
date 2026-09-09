<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: "" },
    icon: { type: String, default: "" },
    action: { type: String, default: "" },
});
defineEmits(["action"]);
</script>

<template>
    <section
        class="flex flex-col gap-space-md rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
    >
        <header
            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
        >
            <div class="flex items-start gap-space-sm">
                <span
                    v-if="icon"
                    class="material-symbols-outlined text-primary"
                    >{{ icon }}</span>
                <div>
                    <h2
                        class="font-headline-sm text-headline-sm font-semibold text-primary"
                    >
                        {{ title }}
                    </h2>
                    <p class="font-body-sm text-body-sm text-secondary">
                        {{ description }}
                    </p>
                </div>
            </div>
            <button
                v-if="action"
                type="button"
                class="flex items-center gap-1 rounded bg-primary-container px-3 py-1.5 text-xs font-title-md text-on-primary"
                @click="$emit('action')"
            >
                <span class="material-symbols-outlined text-[16px]">add</span
                >{{ action }}
            </button>
        </header>
        <slot />
    </section>
</template>
