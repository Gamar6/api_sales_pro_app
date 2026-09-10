<script setup>
import { computed, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
defineProps({
    users: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AdminLayout>
        <div class="min-h-screen bg-background text-on-surface">
            <main class="w-full">
                <div class="flex w-full flex-col">
                    <div class="m-4 p-4">
                        <table
                            class="w-full text-center justify-center"
                        >
                            <tr class="bg-gray-200 font-semibold">
                                <th class="border border-gray-400">No</th>
                                <th class="border border-gray-400">Name</th>
                                <th class="border border-gray-400">Email</th>
                                <th class="border border-gray-400">Role</th>
                                <th class="border border-gray-400">No. Hp</th>
                            </tr>
                            <tr
                                v-for="(user, index) in users"
                                :key="user.id"
                                class="bg-white hover:bg-gray-100"
                            >
                                <td class="border border-gray-400">
                                    {{ index + 1 }}
                                </td>
                                <td class="border border-gray-400">{{ user.name }}</td>
                                <td class="border border-gray-400">{{ user.email }}</td>
                                <td class="border border-gray-400">{{ user.role }}</td>
                                <td class="border border-gray-400">{{ user.nohp }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>
