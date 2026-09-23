<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const userRole = computed(
    () => page.props.auth?.user?.role ?? null,
);

const quickAccessItems = computed(() => {
    const items = [
        {
            key: "visit-reports",
            title: "Visit Logs & Reports",
            description:
                "Lihat histori kunjungan, aktivitas, dan laporan sales.",
            icon: "assignment",
            routeName: "visit-reports",
            tone: "primary",
        },

        {
            key: "visit-exceptions",
            title: "Visit Exceptions",
            description:
                "Tinjau kunjungan yang memiliki pengecualian lokasi.",
            icon: "location_off",
            routeName: "visit-exceptions.index",
            tone: "warning",
        },

        {
            key: "product-catalog",
            title: "Product Catalog",
            description:
                "Lihat katalog produk dan informasi stok.",
            icon: "inventory_2",
            routeName: "product-catalog",
            tone: "secondary",
        },
    ];

    if (
        userRole.value === "admin" ||
        userRole.value === "superadmin"
    ) {
        items.push({
            key: "users",
            title: "User Management",
            description:
                "Kelola akun, role, dan akses pengguna.",
            icon: "manage_accounts",
            routeName: "admin.users.index",
            tone: "secondary",
        });
    }

    return items;
});

const goTo = (routeName) => {
    window.location.href = route(routeName);
};
</script>

<template>
    <section
        class="rounded-lg bg-surface-container-lowest p-space-lg shadow-sm"
    >
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-xs font-semibold uppercase tracking-[0.12em] text-on-surface-variant"
                >
                    Quick Access
                </p>

                <h2
                    class="mt-1 text-lg font-bold text-on-surface"
                >
                    Operational Shortcuts
                </h2>

                <p
                    class="mt-1 text-sm text-on-surface-variant"
                >
                    Akses langsung ke menu operasional yang paling sering
                    digunakan.
                </p>
            </div>
        </div>

        <div
            class="mt-5 grid grid-cols-1 gap-space-sm sm:grid-cols-2 xl:grid-cols-4"
        >
            <button
                v-for="item in quickAccessItems"
                :key="item.key"
                type="button"
                class="group flex min-h-[112px] items-start gap-4 rounded-lg border border-outline-variant bg-surface-container-low p-4 text-left transition hover:-translate-y-0.5 hover:bg-surface-container hover:shadow-sm"
                @click="goTo(item.routeName)"
            >
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="
                        item.tone === 'warning'
                            ? 'bg-amber-100 text-amber-700'
                            : item.tone === 'primary'
                              ? 'bg-primary-container text-on-primary'
                              : 'bg-surface-container-high text-on-surface-variant'
                    "
                >
                    <span class="material-symbols-outlined">
                        {{ item.icon }}
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <div
                        class="flex items-start justify-between gap-2"
                    >
                        <h3
                            class="font-semibold text-on-surface"
                        >
                            {{ item.title }}
                        </h3>

                        <span
                            class="material-symbols-outlined shrink-0 text-base text-on-surface-variant transition group-hover:translate-x-0.5"
                        >
                            arrow_forward
                        </span>
                    </div>

                    <p
                        class="mt-1 text-xs leading-5 text-on-surface-variant"
                    >
                        {{ item.description }}
                    </p>
                </div>
            </button>
        </div>
    </section>
</template>
