<script setup>
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DashboardHeader from "./components/DashboardHeader.vue";
import DashboardKpiGrid from "./components/DashboardKpiGrid.vue";
import DashboardModuleTabs from "./components/DashboardModuleTabs.vue";
import DashboardOverview from "./components/DashboardOverview.vue";
import DashboardModulePanels from "./components/DashboardModulePanels.vue";
import {
    clusters,
    kpis,
    modules,
    products,
    stores,
    telemetryFeed,
} from "./dashboardData";

const activeModule = ref("dashboard");
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-background text-on-surface">
            <main class="w-full">
                <div class="flex w-full flex-col">
                    <div class="flex flex-col gap-space-xl p-space-xl">
                        <DashboardHeader />
                        <DashboardKpiGrid :kpis="kpis" />
                        <DashboardModuleTabs
                            v-model:active-module="activeModule"
                            :modules="modules"
                        />

                        <DashboardOverview
                            v-if="activeModule === 'dashboard'"
                            :clusters="clusters"
                            :telemetry="telemetryFeed"
                        />
                        <DashboardModulePanels
                            v-else
                            :active-module="activeModule"
                            :products="products"
                            :stores="stores"
                        />
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
