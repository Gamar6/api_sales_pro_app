<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const page = usePage();

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({
            search: "",
            role: "",
            status: "",
        }),
    },

    currentUser: {
        type: Object,
        required: true,
    },
});

/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showResetPasswordModal = ref(false);
const showStatusModal = ref(false);

const selectedUser = ref(null);
const selectedStatus = ref(null);

const search = ref(props.filters?.search ?? "");
const roleFilter = ref(props.filters?.role ?? "");
const statusFilter = ref(props.filters?.status ?? "");

/*
|--------------------------------------------------------------------------
| Role
|--------------------------------------------------------------------------
*/

const isSuperadmin = computed(
    () => props.currentUser?.role === "superadmin",
);

const isAdmin = computed(
    () => props.currentUser?.role === "admin",
);

/*
|--------------------------------------------------------------------------
| Forms
|--------------------------------------------------------------------------
*/

const createForm = useForm({
    name: "",
    username: "",
    email: "",
    nohp: "",
    role: "sales",
    password: "",
    password_confirmation: "",
});

const editForm = useForm({
    name: "",
    username: "",
    email: "",
    nohp: "",
    role: "sales",
});

const resetPasswordForm = useForm({
    password: "",
    password_confirmation: "",
});

const statusForm = useForm({
    status: "",
});

/*
|--------------------------------------------------------------------------
| Search & Filter
|--------------------------------------------------------------------------
*/

let searchTimeout = null;

const applyFilters = () => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            route("admin.users.index"),
            {
                search: search.value || undefined,
                role: roleFilter.value || undefined,
                status: statusFilter.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
};

const clearFilters = () => {
    search.value = "";
    roleFilter.value = "";
    statusFilter.value = "";

    router.get(
        route("admin.users.index"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

/*
|--------------------------------------------------------------------------
| Create User
|--------------------------------------------------------------------------
*/

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();

    createForm.role = "sales";

    showCreateModal.value = true;
};

const closeCreateModal = () => {
    if (createForm.processing) {
        return;
    }

    showCreateModal.value = false;
};

const submitCreate = () => {
    createForm.post(route("admin.users.store"), {
        preserveScroll: true,

        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

/*
|--------------------------------------------------------------------------
| Edit User
|--------------------------------------------------------------------------
*/

const openEditModal = (user) => {
    selectedUser.value = user;

    editForm.clearErrors();

    editForm.name = user.name ?? "";
    editForm.username = user.username ?? "";
    editForm.email = user.email ?? "";
    editForm.nohp = user.nohp ?? "";
    editForm.role = user.role ?? "sales";

    showEditModal.value = true;
};

const closeEditModal = () => {
    if (editForm.processing) {
        return;
    }

    showEditModal.value = false;
    selectedUser.value = null;
};

const submitEdit = () => {
    if (!selectedUser.value) {
        return;
    }

    editForm.put(
        route("admin.users.update", selectedUser.value.id),
        {
            preserveScroll: true,

            onSuccess: () => {
                showEditModal.value = false;
                selectedUser.value = null;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Reset Password
|--------------------------------------------------------------------------
*/

const openResetPasswordModal = (user) => {
    selectedUser.value = user;

    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();

    showResetPasswordModal.value = true;
};

const closeResetPasswordModal = () => {
    if (resetPasswordForm.processing) {
        return;
    }

    showResetPasswordModal.value = false;
    selectedUser.value = null;
};

const submitResetPassword = () => {
    if (!selectedUser.value) {
        return;
    }

    resetPasswordForm.post(
        route(
            "admin.users.reset-password",
            selectedUser.value.id,
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                showResetPasswordModal.value = false;
                selectedUser.value = null;
                resetPasswordForm.reset();
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Status
|--------------------------------------------------------------------------
*/

const openStatusModal = (user, status) => {
    selectedUser.value = user;
    selectedStatus.value = status;

    statusForm.clearErrors();
    statusForm.status = status;

    showStatusModal.value = true;
};

const closeStatusModal = () => {
    if (statusForm.processing) {
        return;
    }

    showStatusModal.value = false;
    selectedUser.value = null;
    selectedStatus.value = null;
};

const submitStatus = () => {
    if (!selectedUser.value) {
        return;
    }

    statusForm.patch(
        route(
            "admin.users.status",
            selectedUser.value.id,
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                showStatusModal.value = false;
                selectedUser.value = null;
                selectedStatus.value = null;
            },
        },
    );
};

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(new Date(date));
};

const roleLabel = (role) => {
    return {
        superadmin: "Super Admin",
        admin: "Administrator",
        sales: "Sales",
    }[role] ?? role;
};

const statusLabel = (status) => {
    return {
        active: "Active",
        suspended: "Suspended",
        inactive: "Inactive",
    }[status] ?? status;
};

const statusDescription = computed(() => {
    return {
        active:
            "User akan dapat login dan menggunakan aplikasi kembali.",
        suspended:
            "User tidak dapat login sementara. Semua token aplikasi akan dicabut.",
        inactive:
            "User akan dinonaktifkan dan tidak dapat menggunakan aplikasi.",
    }[selectedStatus.value];
});

const statusActionLabel = computed(() => {
    return {
        active: "Aktifkan User",
        suspended: "Suspend User",
        inactive: "Nonaktifkan User",
    }[selectedStatus.value];
});

const avatarInitial = (name) => {
    return name?.charAt(0)?.toUpperCase() ?? "?";
};

/*
|--------------------------------------------------------------------------
| Permission Helpers
|--------------------------------------------------------------------------
*/

const canEditUser = (user) => {
    if (isSuperadmin.value) {
        return true;
    }

    return isAdmin.value && user.role === "sales";
};

const canManageStatus = (user) => {
    if (props.currentUser?.id === user.id) {
        return false;
    }

    if (isSuperadmin.value) {
        return true;
    }

    return isAdmin.value && user.role === "sales";
};

const canResetPassword = (user) => {
    if (isSuperadmin.value) {
        return true;
    }

    return isAdmin.value && user.role === "sales";
};

const flashSuccess = computed(
    () => page.props.flash?.success,
);

const flashError = computed(
    () => page.props.flash?.error,
);
</script>

<template>
    <Head title="Manajemen User" />

    <AdminLayout>
        <div class="min-h-screen bg-[#F8FAFC]">
            <!-- ========================================================= -->
            <!-- PAGE HEADER -->
            <!-- ========================================================= -->

            <div
                class="border-b border-slate-200 bg-white"
            >
                <div
                    class="px-6 py-6 lg:px-8"
                >
                    <div
                        class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div
                                class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-[#F48110]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-[#F48110]"
                                ></span>

                                Administration
                            </div>

                            <h1
                                class="mt-1 text-2xl font-bold tracking-tight text-[#1C467F]"
                            >
                                Manajemen User
                            </h1>

                            <p
                                class="mt-1 text-sm text-slate-500"
                            >
                                Kelola akun, role, status,
                                dan akses pengguna
                                aplikasi.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#F48110] px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#F48110]/20 transition hover:bg-[#dc7008] focus:outline-none focus:ring-2 focus:ring-[#F48110] focus:ring-offset-2"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>

                            Tambah User
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- FLASH MESSAGE -->
            <!-- ========================================================= -->

            <div
                v-if="flashSuccess || flashError"
                class="px-6 pt-6 lg:px-8"
            >
                <div
                    v-if="flashSuccess"
                    class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                    <span>
                        {{ flashSuccess }}
                    </span>
                </div>

                <div
                    v-if="flashError"
                    class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    <svg
                        class="mt-0.5 h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M10.29 3.86l-7.82 14a2 2 0 001.74 3h15.58a2 2 0 001.74-3l-7.82-14a2 2 0 00-3.48 0z"
                        />
                    </svg>

                    <span>
                        {{ flashError }}
                    </span>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- CONTENT -->
            <!-- ========================================================= -->

            <div
                class="space-y-5 p-6 lg:p-8"
            >
                <!-- ===================================================== -->
                <!-- FILTER CARD -->
                <!-- ===================================================== -->

                <div
                    class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div
                        class="grid grid-cols-1 gap-3 md:grid-cols-12"
                    >
                        <!-- Search -->

                        <div
                            class="relative md:col-span-6"
                        >
                            <svg
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>

                            <input
                                v-model="search"
                                @input="applyFilters"
                                type="text"
                                placeholder="Cari nama, username, email, atau no. HP..."
                                class="w-full rounded-lg border-slate-200 bg-slate-50 py-2.5 pl-9 pr-4 text-sm transition focus:border-[#F48110] focus:ring-[#F48110]"
                            />
                        </div>

                        <!-- Role -->

                        <div
                            class="md:col-span-2"
                        >
                            <select
                                v-model="roleFilter"
                                @change="applyFilters"
                                class="w-full rounded-lg border-slate-200 bg-slate-50 py-2.5 text-sm text-slate-700 focus:border-[#F48110] focus:ring-[#F48110]"
                            >
                                <option value="">
                                    Semua Role
                                </option>

                                <option
                                    v-if="isSuperadmin"
                                    value="superadmin"
                                >
                                    Super Admin
                                </option>

                                <option
                                    v-if="isSuperadmin"
                                    value="admin"
                                >
                                    Administrator
                                </option>

                                <option value="sales">
                                    Sales
                                </option>
                            </select>
                        </div>

                        <!-- Status -->

                        <div
                            class="md:col-span-2"
                        >
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="w-full rounded-lg border-slate-200 bg-slate-50 py-2.5 text-sm text-slate-700 focus:border-[#F48110] focus:ring-[#F48110]"
                            >
                                <option value="">
                                    Semua Status
                                </option>

                                <option value="active">
                                    Active
                                </option>

                                <option value="suspended">
                                    Suspended
                                </option>

                                <option value="inactive">
                                    Inactive
                                </option>
                            </select>
                        </div>

                        <!-- Reset -->

                        <div
                            class="md:col-span-2"
                        >
                            <button
                                type="button"
                                @click="clearFilters"
                                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                Reset Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ===================================================== -->
                <!-- USER TABLE -->
                <!-- ===================================================== -->

                <div
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                >
                    <div
                        class="overflow-x-auto"
                    >
                        <table
                            class="min-w-full divide-y divide-slate-200"
                        >
                            <thead
                                class="bg-slate-50"
                            >
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        User
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        Role
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        Status
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        No. HP
                                    </th>

                                    <th
                                        class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        Bergabung
                                    </th>

                                    <th
                                        class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-slate-100"
                            >
                                <!-- Empty -->

                                <tr
                                    v-if="
                                        !users.data?.length
                                    "
                                >
                                    <td
                                        colspan="6"
                                        class="px-6 py-14 text-center"
                                    >
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100"
                                        >
                                            <svg
                                                class="h-6 w-6 text-slate-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm10 10v-2a4 4 0 00-3-3.87m-1-9a4 4 0 010 7.75"
                                                />
                                            </svg>
                                        </div>

                                        <p
                                            class="mt-3 text-sm font-semibold text-slate-700"
                                        >
                                            User tidak ditemukan
                                        </p>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            Coba ubah kata pencarian
                                            atau filter.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Rows -->

                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="transition hover:bg-slate-50/70"
                                >
                                    <!-- User -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4"
                                    >
                                        <div
                                            class="flex items-center gap-3"
                                        >
                                            <img
                                                v-if="
                                                    user.profile_photo_url
                                                "
                                                :src="
                                                    user.profile_photo_url
                                                "
                                                :alt="
                                                    user.name
                                                "
                                                class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-100"
                                            />

                                            <div
                                                v-else
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-[#1C467F] text-sm font-bold text-white"
                                            >
                                                {{
                                                    avatarInitial(
                                                        user.name,
                                                    )
                                                }}
                                            </div>

                                            <div
                                                class="min-w-0"
                                            >
                                                <p
                                                    class="truncate text-sm font-semibold text-slate-800"
                                                >
                                                    {{
                                                        user.name
                                                    }}

                                                    <span
                                                        v-if="
                                                            currentUser.id ===
                                                            user.id
                                                        "
                                                        class="ml-1 text-xs font-medium text-[#F48110]"
                                                    >
                                                        (Anda)
                                                    </span>
                                                </p>

                                                <p
                                                    class="truncate text-xs text-slate-500"
                                                >
                                                    @{{ user.username }}
                                                </p>

                                                <p
                                                    class="truncate text-xs text-slate-400"
                                                >
                                                    {{ user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Role -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                                                user.role ===
                                                'superadmin'
                                                    ? 'bg-purple-50 text-purple-700'
                                                    : user.role ===
                                                      'admin'
                                                      ? 'bg-blue-50 text-blue-700'
                                                      : 'bg-slate-100 text-slate-700',
                                            ]"
                                        >
                                            {{
                                                roleLabel(
                                                    user.role,
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <!-- Status -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                                user.status ===
                                                'active'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : user.status ===
                                                      'suspended'
                                                      ? 'bg-amber-50 text-amber-700'
                                                      : 'bg-red-50 text-red-700',
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    user.status ===
                                                    'active'
                                                        ? 'bg-emerald-500'
                                                        : user.status ===
                                                          'suspended'
                                                          ? 'bg-amber-500'
                                                          : 'bg-red-500',
                                                ]"
                                            ></span>

                                            {{
                                                statusLabel(
                                                    user.status,
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <!-- Phone -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-600"
                                    >
                                        {{
                                            user.nohp ||
                                            "-"
                                        }}
                                    </td>

                                    <!-- Created -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-sm text-slate-500"
                                    >
                                        {{
                                            formatDate(
                                                user.created_at,
                                            )
                                        }}
                                    </td>

                                    <!-- Actions -->

                                    <td
                                        class="whitespace-nowrap px-6 py-4 text-right"
                                    >
                                        <div
                                            class="flex justify-end gap-2"
                                        >
                                            <button
                                                v-if="
                                                    canEditUser(
                                                        user,
                                                    )
                                                "
                                                type="button"
                                                @click="
                                                    openEditModal(
                                                        user,
                                                    )
                                                "
                                                title="Edit user"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-blue-50 hover:text-[#1C467F]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-8.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 7.5-7.5z"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                v-if="
                                                    canResetPassword(
                                                        user,
                                                    )
                                                "
                                                type="button"
                                                @click="
                                                    openResetPasswordModal(
                                                        user,
                                                    )
                                                "
                                                title="Reset password"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-orange-50 hover:text-[#F48110]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                v-if="
                                                    canManageStatus(
                                                        user,
                                                    ) &&
                                                    user.status !==
                                                        'active'
                                                "
                                                type="button"
                                                @click="
                                                    openStatusModal(
                                                        user,
                                                        'active',
                                                    )
                                                "
                                                title="Aktifkan user"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                v-if="
                                                    canManageStatus(
                                                        user,
                                                    ) &&
                                                    user.status ===
                                                        'active'
                                                "
                                                type="button"
                                                @click="
                                                    openStatusModal(
                                                        user,
                                                        'suspended',
                                                    )
                                                "
                                                title="Suspend user"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                v-if="
                                                    canManageStatus(
                                                        user,
                                                    ) &&
                                                    user.status !==
                                                        'inactive'
                                                "
                                                type="button"
                                                @click="
                                                    openStatusModal(
                                                        user,
                                                        'inactive',
                                                    )
                                                "
                                                title="Nonaktifkan user"
                                                class="rounded-lg p-2 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- ================================================= -->
                    <!-- PAGINATION -->
                    <!-- ================================================= -->

                    <div
                        v-if="
                            users.links &&
                            users.links.length > 3
                        "
                        class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p
                            class="text-xs text-slate-500"
                        >
                            Menampilkan
                            <span
                                class="font-semibold text-slate-700"
                            >
                                {{ users.from ?? 0 }}
                            </span>
                            -
                            <span
                                class="font-semibold text-slate-700"
                            >
                                {{ users.to ?? 0 }}
                            </span>
                            dari
                            <span
                                class="font-semibold text-slate-700"
                            >
                                {{ users.total ?? 0 }}
                            </span>
                            user
                        </p>

                        <div
                            class="flex flex-wrap gap-1"
                        >
                            <template
                                v-for="(
                                    link, index
                                ) in users.links"
                                :key="index"
                            >
                                <Link
                                    v-if="
                                        link.url
                                    "
                                    :href="link.url"
                                    preserve-scroll
                                    preserve-state
                                    :class="[
                                        'min-w-9 rounded-lg px-3 py-2 text-center text-xs font-medium transition',
                                        link.active
                                            ? 'bg-[#1C467F] text-white'
                                            : 'bg-slate-50 text-slate-600 hover:bg-slate-100',
                                    ]"
                                    v-html="
                                        link.label
                                    "
                                />

                                <span
                                    v-else
                                    :class="[
                                        'min-w-9 rounded-lg px-3 py-2 text-center text-xs font-medium text-slate-300',
                                    ]"
                                    v-html="
                                        link.label
                                    "
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- CREATE USER MODAL -->
        <!-- ============================================================= -->

        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @click.self="closeCreateModal"
        >
            <div
                class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <!-- Header -->

                <div
                    class="border-b border-slate-100 px-6 py-5"
                >
                    <div
                        class="flex items-start justify-between"
                    >
                        <div>
                            <h2
                                class="text-lg font-bold text-[#1C467F]"
                            >
                                Tambah User
                            </h2>

                            <p
                                class="mt-1 text-sm text-slate-500"
                            >
                                Buat akun pengguna baru
                                untuk aplikasi.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="
                                closeCreateModal
                            "
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->

                <form
                    @submit.prevent="
                        submitCreate
                    "
                >
                    <div
                        class="grid grid-cols-1 gap-4 px-6 py-6 sm:grid-cols-2"
                    >
                        <!-- Name -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Nama Lengkap
                            </label>

                            <input
                                v-model="
                                    createForm.name
                                "
                                type="text"
                                placeholder="Contoh: Budi Santoso"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .name
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .name
                                }}
                            </p>
                        </div>

                        <!-- Username -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Username
                            </label>

                            <input
                                v-model="
                                    createForm.username
                                "
                                type="text"
                                placeholder="contoh: budi01"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .username
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .username
                                }}
                            </p>
                        </div>

                        <!-- Email -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Email
                            </label>

                            <input
                                v-model="
                                    createForm.email
                                "
                                type="email"
                                placeholder="nama@company.com"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .email
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .email
                                }}
                            </p>
                        </div>

                        <!-- Phone -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                No. HP
                            </label>

                            <input
                                v-model="
                                    createForm.nohp
                                "
                                type="text"
                                placeholder="08xxxxxxxxxx"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .nohp
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .nohp
                                }}
                            </p>
                        </div>

                        <!-- Role -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Role
                            </label>

                            <select
                                v-model="
                                    createForm.role
                                "
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            >
                                <option value="sales">
                                    Sales
                                </option>

                                <option
                                    v-if="
                                        isSuperadmin
                                    "
                                    value="admin"
                                >
                                    Administrator
                                </option>

                                <option
                                    v-if="
                                        isSuperadmin
                                    "
                                    value="superadmin"
                                >
                                    Super Admin
                                </option>
                            </select>

                            <p
                                v-if="
                                    createForm.errors
                                        .role
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .role
                                }}
                            </p>
                        </div>

                        <!-- Password -->

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Password
                            </label>

                            <input
                                v-model="
                                    createForm.password
                                "
                                type="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .password
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .password
                                }}
                            </p>
                        </div>

                        <!-- Confirm Password -->

                        <div
                            class="sm:col-span-2"
                        >
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Konfirmasi Password
                            </label>

                            <input
                                v-model="
                                    createForm.password_confirmation
                                "
                                type="password"
                                placeholder="Ulangi password"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    createForm.errors
                                        .password_confirmation
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    createForm.errors
                                        .password_confirmation
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4"
                    >
                        <button
                            type="button"
                            @click="
                                closeCreateModal
                            "
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="
                                createForm.processing
                            "
                            class="rounded-lg bg-[#F48110] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#dc7008] disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{
                                createForm.processing
                                    ? "Menyimpan..."
                                    : "Buat User"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- EDIT USER MODAL -->
        <!-- ============================================================= -->

        <div
            v-if="
                showEditModal &&
                selectedUser
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @click.self="closeEditModal"
        >
            <div
                class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="border-b border-slate-100 px-6 py-5"
                >
                    <div
                        class="flex items-start justify-between"
                    >
                        <div>
                            <h2
                                class="text-lg font-bold text-[#1C467F]"
                            >
                                Edit User
                            </h2>

                            <p
                                class="mt-1 text-sm text-slate-500"
                            >
                                Perbarui informasi
                                pengguna.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="
                                closeEditModal
                            "
                            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <form
                    @submit.prevent="
                        submitEdit
                    "
                >
                    <div
                        class="grid grid-cols-1 gap-4 px-6 py-6 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Nama Lengkap
                            </label>

                            <input
                                v-model="
                                    editForm.name
                                "
                                type="text"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    editForm.errors
                                        .name
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    editForm.errors
                                        .name
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Username
                            </label>

                            <input
                                v-model="
                                    editForm.username
                                "
                                type="text"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    editForm.errors
                                        .username
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    editForm.errors
                                        .username
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Email
                            </label>

                            <input
                                v-model="
                                    editForm.email
                                "
                                type="email"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    editForm.errors
                                        .email
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    editForm.errors
                                        .email
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                No. HP
                            </label>

                            <input
                                v-model="
                                    editForm.nohp
                                "
                                type="text"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    editForm.errors
                                        .nohp
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    editForm.errors
                                        .nohp
                                }}
                            </p>
                        </div>

                        <div
                            class="sm:col-span-2"
                        >
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Role
                            </label>

                            <select
                                v-model="
                                    editForm.role
                                "
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            >
                                <option value="sales">
                                    Sales
                                </option>

                                <option
                                    v-if="
                                        isSuperadmin
                                    "
                                    value="admin"
                                >
                                    Administrator
                                </option>

                                <option
                                    v-if="
                                        isSuperadmin
                                    "
                                    value="superadmin"
                                >
                                    Super Admin
                                </option>
                            </select>

                            <p
                                v-if="
                                    editForm.errors
                                        .role
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    editForm.errors
                                        .role
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4"
                    >
                        <button
                            type="button"
                            @click="
                                closeEditModal
                            "
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="
                                editForm.processing
                            "
                            class="rounded-lg bg-[#1C467F] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#163866] disabled:opacity-60"
                        >
                            {{
                                editForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Perubahan"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- RESET PASSWORD MODAL -->
        <!-- ============================================================= -->

        <div
            v-if="
                showResetPasswordModal &&
                selectedUser
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @click.self="
                closeResetPasswordModal
            "
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="border-b border-slate-100 px-6 py-5"
                >
                    <h2
                        class="text-lg font-bold text-[#1C467F]"
                    >
                        Reset Password
                    </h2>

                    <p
                        class="mt-1 text-sm text-slate-500"
                    >
                        Atur password baru untuk
                        <span
                            class="font-semibold text-slate-700"
                        >
                            {{
                                selectedUser.name
                            }}
                        </span>
                        .
                    </p>
                </div>

                <form
                    @submit.prevent="
                        submitResetPassword
                    "
                >
                    <div
                        class="space-y-4 px-6 py-6"
                    >
                        <div
                            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800"
                        >
                            Setelah password direset,
                            seluruh sesi API user akan
                            dicabut dan user perlu login
                            kembali di aplikasi mobile.
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Password Baru
                            </label>

                            <input
                                v-model="
                                    resetPasswordForm.password
                                "
                                type="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    resetPasswordForm
                                        .errors
                                        .password
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    resetPasswordForm
                                        .errors
                                        .password
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-semibold text-slate-700"
                            >
                                Konfirmasi Password
                            </label>

                            <input
                                v-model="
                                    resetPasswordForm.password_confirmation
                                "
                                type="password"
                                placeholder="Ulangi password"
                                class="w-full rounded-lg border-slate-200 px-3 py-2.5 text-sm focus:border-[#F48110] focus:ring-[#F48110]"
                            />

                            <p
                                v-if="
                                    resetPasswordForm
                                        .errors
                                        .password_confirmation
                                "
                                class="mt-1 text-xs text-red-500"
                            >
                                {{
                                    resetPasswordForm
                                        .errors
                                        .password_confirmation
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4"
                    >
                        <button
                            type="button"
                            @click="
                                closeResetPasswordModal
                            "
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="
                                resetPasswordForm.processing
                            "
                            class="rounded-lg bg-[#F48110] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#dc7008] disabled:opacity-60"
                        >
                            {{
                                resetPasswordForm.processing
                                    ? "Mereset..."
                                    : "Reset Password"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- STATUS MODAL -->
        <!-- ============================================================= -->

        <div
            v-if="
                showStatusModal &&
                selectedUser
            "
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @click.self="closeStatusModal"
        >
            <div
                class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl"
            >
                <div
                    class="px-6 pt-6"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl"
                        :class="
                            selectedStatus ===
                            'active'
                                ? 'bg-emerald-50 text-emerald-600'
                                : selectedStatus ===
                                  'suspended'
                                  ? 'bg-amber-50 text-amber-600'
                                  : 'bg-red-50 text-red-600'
                        "
                    >
                        <svg
                            v-if="
                                selectedStatus ===
                                'active'
                            "
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                        <svg
                            v-else-if="
                                selectedStatus ===
                                'suspended'
                            "
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                            />
                        </svg>

                        <svg
                            v-else
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </div>

                    <h2
                        class="mt-4 text-lg font-bold text-slate-800"
                    >
                        {{
                            statusActionLabel
                        }}?
                    </h2>

                    <p
                        class="mt-2 text-sm leading-relaxed text-slate-500"
                    >
                        Kamu akan mengubah status
                        <span
                            class="font-semibold text-slate-700"
                        >
                            {{
                                selectedUser.name
                            }}
                        </span>
                        menjadi
                        <span
                            class="font-semibold"
                        >
                            {{
                                statusLabel(
                                    selectedStatus,
                                )
                            }}
                        </span>
                        .
                    </p>

                    <p
                        class="mt-2 text-xs leading-relaxed text-slate-400"
                    >
                        {{
                            statusDescription
                        }}
                    </p>
                </div>

                <div
                    class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 mt-6"
                >
                    <button
                        type="button"
                        @click="
                            closeStatusModal
                        "
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="submitStatus"
                        :disabled="
                            statusForm.processing
                        "
                        :class="[
                            'rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60',
                            selectedStatus ===
                            'active'
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : selectedStatus ===
                                  'suspended'
                                  ? 'bg-amber-600 hover:bg-amber-700'
                                  : 'bg-red-600 hover:bg-red-700',
                        ]"
                    >
                        {{
                            statusForm.processing
                                ? "Memproses..."
                                : "Konfirmasi"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
