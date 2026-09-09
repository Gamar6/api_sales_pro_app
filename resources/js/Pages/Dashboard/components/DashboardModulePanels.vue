<script setup>
import ModulePanel from "./ModulePanel.vue";
defineProps({
    activeModule: { type: String, required: true },
    products: { type: Array, required: true },
    stores: { type: Array, required: true },
});
</script>

<template>
    <ModulePanel
        v-if="activeModule === 'tracking'"
        title="Interactive Live Fleet Geospatial Matrix"
        description="Real-time GPS telemetry tracks of 42 deployed sales reps with active route breadcrumbs"
        icon="map"
        ><div
            class="flex h-96 items-center justify-center rounded-lg bg-surface-container-low bg-cover bg-center text-secondary"
            style="
                background-image: url(&quot;https://lh3.googleusercontent.com/aida-public/AB6AXuAPLvzy1WiXzXOckBqRgjGvy0-okW9P3l3-L_ksFaBcOXc0C6ZC4pbQUl8iX46yA28X2FkmjoYjg5LDX3gI08O15xmj9EK1_3EMbx1s7wI1c3omgV2tgbHwi1tZBbtnT9DFg2LzqHnEzo658j13hRa8Cd0Fd_zz3eD3oUB8BciefprWQVV4xQ4aOQoBygkw-UJbks8tLM0CiaFwnALiWh8HS80MmCCOJOs01vrT1CXZ-8PocqSE09yA&quot;);
            "
        >
            42 REPS STREAMING
        </div></ModulePanel
    >
    <ModulePanel
        v-else-if="activeModule === 'visits'"
        title="Comprehensive Field Visit Audit & Performance Logs"
        description="Exportable operational records, visit timestamps, client signatures, and time-on-site validations"
        ><div class="grid grid-cols-1 gap-space-md md:grid-cols-3">
            <div
                v-for="metric in [
                    [
                        'First Check-in Recorded',
                        '08:04 AM',
                        'Rep: David Cho (Store #12)',
                    ],
                    [
                        'Average Customer Interaction',
                        '28m 45s',
                        '+3m vs last week',
                    ],
                    [
                        'Visits with Direct Order Placed',
                        '214 of 318',
                        '67.3% Conversion Hit Rate',
                    ],
                ]"
                :key="metric[0]"
                class="rounded bg-surface-container-low p-space-md"
            >
                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                    >{{ metric[0] }}</span
                ><strong
                    class="block font-headline-sm text-headline-sm text-primary"
                    >{{ metric[1] }}</strong
                ><span class="text-xs text-secondary">{{ metric[2] }}</span>
            </div>
        </div></ModulePanel
    >
    <ModulePanel
        v-else-if="activeModule === 'stores'"
        title="Store Directory & Geofence Verification Queue"
        description="Verify physical merchant premises, coordinate tags, and merchant license status before syncing to Odoo ERP"
        action="New Merchant"
        ><div class="grid grid-cols-1 gap-space-md md:grid-cols-2">
            <div
                v-for="store in stores"
                :key="store.name"
                class="flex items-center justify-between rounded bg-surface-container-low p-space-md"
            >
                <div>
                    <strong class="block text-primary">{{ store.name }}</strong
                    ><span class="block text-xs text-secondary">{{
                        store.submitted
                    }}</span
                    ><span
                        class="font-code-metric text-[11px]"
                        :class="
                            store.gpsType === 'success'
                                ? 'text-emerald-700'
                                : 'text-tertiary-container'
                        "
                        >{{ store.gps }}</span
                    >
                </div>
                <button
                    type="button"
                    class="rounded bg-tertiary-container px-3 py-1 font-label-caps text-[11px] font-bold text-on-primary"
                >
                    APPROVE
                </button>
            </div>
        </div></ModulePanel
    >
    <ModulePanel
        v-else-if="activeModule === 'catalog'"
        title="Field Product Catalog & Active Stock Levels"
        description="Live warehouse availability synchronized directly from Odoo inventory nodes"
        ><div
            class="grid grid-cols-1 gap-space-md sm:grid-cols-2 lg:grid-cols-4"
        >
            <div
                v-for="product in products"
                :key="product.sku"
                class="rounded bg-surface-container-low p-space-md"
            >
                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                    >{{ product.category }} SKU {{ product.sku }}</span
                ><strong class="block text-primary">{{ product.name }}</strong
                ><span class="text-xs text-secondary"
                    >Central Depot Stock: {{ product.stock }} units</span
                >
                <div class="mt-space-md flex justify-between">
                    <span
                        class="font-code-metric text-xs text-primary-container"
                        >{{ product.price }}</span
                    ><span
                        class="rounded px-2 py-0.5 text-[10px]"
                        :class="
                            product.statusType === 'success'
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-orange-100 text-tertiary-container'
                        "
                        >{{ product.status }}</span
                    >
                </div>
            </div>
        </div></ModulePanel
    >
    <ModulePanel
        v-else-if="activeModule === 'odoo'"
        title="Odoo Enterprise ERP Bi-Directional Synchronization Bus"
        description="Live JSON-RPC telemetry bus handling orders, invoices, route manifests, and customer master balances"
        action="Force Sync Pulse"
        ><div class="grid grid-cols-1 gap-space-md md:grid-cols-4">
            <div
                v-for="metric in [
                    ['ERP Gateway Status', 'Connected 200 OK'],
                    ['Average Latency', '114ms'],
                    ['Synced Orders Today', '214 Orders'],
                    ['Failed Packets (24h)', '0 Errors'],
                ]"
                :key="metric[0]"
                class="rounded bg-surface-container-low p-space-md"
            >
                <span
                    class="font-label-caps text-label-caps uppercase text-secondary"
                    >{{ metric[0] }}</span
                ><strong
                    class="block font-headline-sm text-headline-sm text-primary"
                    >{{ metric[1] }}</strong
                >
            </div>
        </div></ModulePanel
    >
    <ModulePanel
        v-else
        title="User Access Control & Territory Permissions"
        description="Configure dispatcher console roles, supervisor override permissions, and field rep devices"
        action="Provision New User"
        ><div class="grid grid-cols-1 gap-space-md md:grid-cols-3">
            <div
                v-for="role in [
                    [
                        'Dispatch Supervisors',
                        'Full geofence override authority',
                        '4 Active Users Assigned',
                    ],
                    [
                        'Field Sales Executives',
                        'Mobile check-in & catalog order entry',
                        '48 Active Field Licenses',
                    ],
                    [
                        'System Administrators',
                        'Odoo credentials & API bus config',
                        '2 Root Node Admins',
                    ],
                ]"
                :key="role[0]"
                class="rounded bg-surface-container-low p-space-md"
            >
                <strong class="block text-primary">{{ role[0] }}</strong
                ><span class="block text-xs text-secondary">{{ role[1] }}</span
                ><span class="font-code-metric text-xs text-primary">{{
                    role[2]
                }}</span>
            </div>
        </div></ModulePanel
    >
</template>
