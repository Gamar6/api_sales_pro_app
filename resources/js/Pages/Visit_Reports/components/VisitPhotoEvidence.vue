<script setup>
defineProps({
    photos: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="flex flex-col gap-space-xs">
        <div
            class="flex items-center justify-between"
        >
            <span
                class="font-label-caps text-label-caps uppercase text-secondary tracking-wider"
            >
                Field Photo Evidence
                ({{ photos.length }})
            </span>

        <span
            class="font-code-metric text-[11px] text-primary"
        >
            Synced
        </span>
    </div>

    <div
        class="grid grid-cols-2 gap-space-sm"
    >
        <div
            v-for="photo in photos"
            :key="photo.id ?? photo.image"
            class="relative rounded overflow-hidden group shadow-sm bg-surface-container-high aspect-[4/3]"
        >
            <img
                :src="photo.image"
                :alt="photo.label"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />

            <div
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-primary/80 via-transparent to-transparent flex flex-col justify-end p-2"
            >
                <span
                    class="font-code-metric text-[10px] text-on-primary font-bold"
                >
                    {{ photo.label }}
                </span>
            </div>

            <a
                :href="photo.image"
                target="_blank"
                class="absolute top-1.5 right-1.5 p-1 rounded-full bg-primary/60 text-on-primary"
            >
                <span
                    class="material-symbols-outlined text-[14px]"
                >
                    fullscreen
                </span>
            </a>
        </div>
    </div>
</div>

</template>
