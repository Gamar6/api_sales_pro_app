<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Fiva Sales App" />

    <div
        class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900"
    >
        <!-- Background decoration -->
        <div
            class="pointer-events-none absolute -right-40 -top-40 h-[520px] w-[520px] rounded-full bg-[#1C467F]/[0.06] blur-3xl"
        ></div>

        <div
            class="pointer-events-none absolute -bottom-48 -left-32 h-[500px] w-[500px] rounded-full bg-[#1C467F]/[0.05] blur-3xl"
        ></div>

        <!-- Main wrapper -->
        <div
            class="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 sm:px-8 lg:px-12"
        >
            <!-- Header -->
            <header class="flex items-center justify-between py-7 sm:py-8">
                <div class="flex items-center">
                    <img
                        src="/images/logo.png"
                        alt="Fiva Sales App"
                        class="h-16 w-auto object-contain sm:h-24"
                    />
                </div>

                <div v-if="canLogin">
                    <Link
                        :href="
                            $page.props.auth.user
                                ? route('dashboard')
                                : route('login')
                        "
                        class="group inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[#1C467F]/20 hover:text-[#1C467F] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#1C467F]/20"
                    >
                        <span>
                            {{
                                $page.props.auth.user ? "Dashboard" : "Sign in"
                            }}
                        </span>

                        <svg
                            class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M3 10a.75.75 0 0 1 .75-.75h10.638l-3.19-3.19a.75.75 0 1 1 1.06-1.06l4.47 4.47a.75.75 0 0 1 0 1.06l-4.47 4.47a.75.75 0 0 1-1.06-1.06l3.19-3.19H3.75A.75.75 0 0 1 3 10Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </Link>
                </div>
            </header>

            <!-- Hero -->
            <main class="flex flex-1 items-center py-12 sm:py-16 lg:py-20">
                <div
                    class="grid w-full items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-20"
                >
                    <!-- Left content -->
                    <section class="max-w-2xl">
                        <div
                            class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#1C467F]/10 bg-[#1C467F]/[0.06] px-3.5 py-2 text-xs font-semibold tracking-wide text-[#1C467F]"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-[#1C467F]"
                            ></span>

                            INTERNAL SALES PLATFORM
                        </div>

                        <h1
                            class="max-w-2xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-6xl"
                        >
                            Field operations,
                            <span class="text-[#1C467F]"> made simpler. </span>
                        </h1>

                        <p
                            class="mt-6 max-w-xl text-base leading-7 text-slate-500 sm:text-lg sm:leading-8"
                        >
                            Fiva Sales App brings your sales activities together
                            in one place — from customer visits and outlet
                            management to products and field operations.
                        </p>

                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            <Link
                                v-if="canLogin"
                                :href="
                                    $page.props.auth.user
                                        ? route('dashboard')
                                        : route('login')
                                "
                                class="group inline-flex items-center justify-center gap-3 rounded-xl bg-[#1C467F] px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#1C467F]/20 transition duration-200 hover:-translate-y-0.5 hover:bg-[#163964] hover:shadow-xl hover:shadow-[#1C467F]/25 focus:outline-none focus:ring-4 focus:ring-[#1C467F]/20"
                            >
                                <span>
                                    {{
                                        $page.props.auth.user
                                            ? "Go to dashboard"
                                            : "Sign in to continue"
                                    }}
                                </span>

                                <svg
                                    class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M3 10a.75.75 0 0 1 .75-.75h10.638l-3.19-3.19a.75.75 0 1 1 1.06-1.06l4.47 4.47a.75.75 0 0 1 0 1.06l-4.47 4.47a.75.75 0 0 1-1.06-1.06l3.19-3.19H3.75A.75.75 0 0 1 3 10Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </Link>
                        </div>

                        <!-- Feature indicators -->
                        <div
                            class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-500"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                >
                                    <svg
                                        class="h-3 w-3"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.91 3.91 7.477-9.347a.75.75 0 0 1 1.037-.13Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                                Visit management
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                >
                                    <svg
                                        class="h-3 w-3"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.91 3.91 7.477-9.347a.75.75 0 0 1 1.037-.13Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                                Outlet management
                            </div>

                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                                >
                                    <svg
                                        class="h-3 w-3"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10a.75.75 0 0 1 1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.91 3.91 7.477-9.347a.75.75 0 0 1 1.037-.13Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                                Product & stock
                            </div>
                        </div>
                    </section>

                    <!-- Right visual -->
                    <section
                        class="relative mx-auto w-full max-w-lg lg:max-w-none"
                    >
                        <!-- Main card -->
                        <div
                            class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-5 shadow-2xl shadow-slate-900/[0.08] sm:p-7"
                        >
                            <!-- Card header -->
                            <div
                                class="flex items-center justify-between border-b border-slate-100 pb-5"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1C467F]/10"
                                    >
                                        <svg
                                            class="h-5 w-5 text-[#1C467F]"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 12h18M12 3v18"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5.5 5.5h13v13h-13z"
                                            />
                                        </svg>
                                    </div>

                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            Fiva Sales App
                                        </p>

                                        <p class="text-xs text-slate-400">
                                            Field operations
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-600"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                    ></span>
                                    Active
                                </span>
                            </div>

                            <!-- Visual dashboard preview -->
                            <div class="py-6">
                                <div class="mb-5">
                                    <div
                                        class="h-2.5 w-28 rounded-full bg-slate-100"
                                    ></div>

                                    <div
                                        class="mt-2 h-3 w-44 rounded-full bg-slate-200"
                                    ></div>
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                                >
                                    <div
                                        class="rounded-2xl bg-[#1C467F] p-4 text-white"
                                    >
                                        <div
                                            class="mb-4 h-8 w-8 rounded-lg bg-white/15"
                                        ></div>

                                        <div
                                            class="h-2 w-12 rounded-full bg-white/30"
                                        ></div>

                                        <div
                                            class="mt-2 h-4 w-16 rounded-full bg-white/80"
                                        ></div>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
                                    >
                                        <div
                                            class="mb-4 h-8 w-8 rounded-lg bg-[#1C467F]/10"
                                        ></div>

                                        <div
                                            class="h-2 w-12 rounded-full bg-slate-200"
                                        ></div>

                                        <div
                                            class="mt-2 h-4 w-16 rounded-full bg-slate-300"
                                        ></div>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
                                    >
                                        <div
                                            class="mb-4 h-8 w-8 rounded-lg bg-emerald-100"
                                        ></div>

                                        <div
                                            class="h-2 w-12 rounded-full bg-slate-200"
                                        ></div>

                                        <div
                                            class="mt-2 h-4 w-16 rounded-full bg-slate-300"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Activity preview -->
                                <div
                                    class="mt-4 rounded-2xl border border-slate-100 bg-white p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div
                                            class="h-2.5 w-24 rounded-full bg-slate-200"
                                        ></div>

                                        <div
                                            class="h-2 w-12 rounded-full bg-slate-100"
                                        ></div>
                                    </div>

                                    <div class="mt-5 flex h-24 items-end gap-2">
                                        <div
                                            class="h-[35%] flex-1 rounded-t-md bg-[#1C467F]/10"
                                        ></div>
                                        <div
                                            class="h-[52%] flex-1 rounded-t-md bg-[#1C467F]/15"
                                        ></div>
                                        <div
                                            class="h-[43%] flex-1 rounded-t-md bg-[#1C467F]/20"
                                        ></div>
                                        <div
                                            class="h-[72%] flex-1 rounded-t-md bg-[#1C467F]/30"
                                        ></div>
                                        <div
                                            class="h-[61%] flex-1 rounded-t-md bg-[#1C467F]/40"
                                        ></div>
                                        <div
                                            class="h-[88%] flex-1 rounded-t-md bg-[#1C467F]"
                                        ></div>
                                        <div
                                            class="h-[68%] flex-1 rounded-t-md bg-[#1C467F]/50"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card footer -->
                            <div
                                class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3.5"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm"
                                >
                                    <svg
                                        class="h-4 w-4 text-[#1C467F]"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 6v6l4 2"
                                        />
                                        <circle cx="12" cy="12" r="9" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="truncate text-xs font-semibold text-slate-700"
                                    >
                                        Stay on top of every visit
                                    </p>

                                    <p
                                        class="mt-0.5 truncate text-[11px] text-slate-400"
                                    >
                                        Keep your field activities organized.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative floating element -->
                        <div
                            class="absolute -right-4 -top-5 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/[0.08] sm:block"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50"
                                >
                                    <svg
                                        class="h-4 w-4 text-emerald-600"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.91 3.91 7.477-9.347a.75.75 0 0 1 1.037-.13Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <div
                                        class="h-1.5 w-12 rounded-full bg-slate-200"
                                    ></div>

                                    <div
                                        class="mt-1.5 h-2 w-20 rounded-full bg-slate-300"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative dot pattern -->
                        <div
                            class="pointer-events-none absolute -bottom-8 -right-8 -z-10 grid grid-cols-5 gap-2 opacity-40"
                        >
                            <span
                                v-for="n in 25"
                                :key="n"
                                class="h-1.5 w-1.5 rounded-full bg-[#1C467F]"
                            ></span>
                        </div>
                    </section>
                </div>
            </main>

            <!-- Footer -->
            <footer
                class="flex flex-col gap-3 border-t border-slate-200/80 py-6 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between"
            >
                <p>© 2026 Fiva Sales App. Internal use only.</p>

                <div class="flex items-center gap-2">
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-[#1C467F]/60"
                    ></span>
                    <span>Sales Operations Platform</span>
                </div>
            </footer>
        </div>
    </div>
</template>
