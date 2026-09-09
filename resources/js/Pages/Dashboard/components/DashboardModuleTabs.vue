<script setup>
defineProps({
    modules: { type: Array, required: true },
    activeModule: { type: String, required: true },
});
defineEmits(["update:activeModule"]);
</script>

<template>
    <section
        class="overflow-x-auto rounded-lg bg-surface-container-lowest p-space-sm shadow-sm"
    >
        <div class="flex min-w-[780px] items-center gap-space-xs">
            <button
                v-for="module in modules"
                :key="module.key"
                type="button"
                class="flex items-center gap-2 rounded px-space-base py-2.5 font-title-md text-title-md transition-all"
                :class="
                    activeModule === module.key
                        ? 'bg-primary-container text-on-primary shadow-sm'
                        : 'bg-surface-bright text-secondary hover:bg-surface-container-low'
                "
                @click="$emit('update:activeModule', module.key)"
            >
                <span class="material-symbols-outlined text-[18px]">{{
                    module.icon
                }}</span
                ><span>{{ module.label }}</span
                ><span
                    v-if="module.live"
                    class="h-2 w-2 animate-pulse rounded-full bg-on-tertiary-container"
                ></span
                ><span
                    v-if="module.badge"
                    class="rounded bg-tertiary-container px-1.5 py-0.5 text-[10px] font-bold text-on-primary"
                    >{{ module.badge }}</span
                >
            </button>
        </div>
    </section>
</template>
