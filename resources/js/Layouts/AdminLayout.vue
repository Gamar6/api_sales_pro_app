<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const isSidebarOpen = ref(true);

const navigation = [
  { 
    name: 'Dashboard', 
    href: '/dashboard', 
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
  },
  { 
    name: 'Live Tracking', 
    href: '/tracking', 
    icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z' 
  },
  { 
    name: 'Visit Logs & Reports', 
    href: '/visit-reports', 
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' 
  },
  { 
    name: 'Store Directory', 
    href: '/stores', 
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5' 
  },
  { 
    name: 'Katalog Produk', 
    href: '/products', 
    icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' 
  },
  { 
    name: 'Status Odoo Sync', 
    href: '/odoo-sync', 
    icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' 
  },
  { 
    name: 'Manajemen User', 
    href: '/users', 
    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' 
  },
];

// Cek menu mana yang sedang aktif
const isCurrentRoute = (path) => page.url.startsWith(path);
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans flex text-slate-800">
    <!-- SIDEBAR (35% Brand Secondary: #1C467F) -->
    <aside 
      :class="[
        'bg-[#1C467F] text-white flex flex-col transition-all duration-300 z-30 shadow-xl',
        isSidebarOpen ? 'w-64' : 'w-20'
      ]"
    >
      <!-- Brand Logo Header -->
      <div class="h-16 flex items-center justify-between px-4 border-b border-white/10 bg-[#163866]">
        <div class="flex items-center gap-3 overflow-hidden">
          <!-- Icon Logo Sales Pro App -->
          <div class="w-9 h-9 rounded-lg bg-[#F48110] flex items-center justify-center font-black text-white text-lg shrink-0 shadow-md">
            S
          </div>
          <span v-if="isSidebarOpen" class="font-bold text-lg tracking-wide whitespace-nowrap text-white">
            Sales Pro <span class="text-[#F48110] text-xs uppercase font-extrabold tracking-widest block -mt-1">App</span>
          </span>
        </div>
        <button 
          @click="isSidebarOpen = !isSidebarOpen"
          class="p-1.5 rounded-lg hover:bg-white/10 text-white/70 hover:text-white transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>

      <!-- Navigation Menu Links -->
      <nav class="flex-1 py-4 px-3 space-y-1.5 overflow-y-auto">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex items-center gap-3.5 px-3 py-2.5 rounded-lg font-medium text-sm transition-all duration-200 group relative',
            isCurrentRoute(item.href)
              ? 'bg-[#F48110] text-white shadow-md shadow-[#F48110]/20 font-semibold'
              : 'text-slate-200 hover:bg-white/10 hover:text-white'
          ]"
        >
          <svg 
            class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
          </svg>
          <span v-if="isSidebarOpen" class="truncate">{{ item.name }}</span>

          <!-- Indicator Strip jika Aktif -->
          <span 
            v-if="isCurrentRoute(item.href) && !isSidebarOpen" 
            class="absolute right-0 top-2 bottom-2 w-1 bg-[#F48110] rounded-l"
          ></span>
        </Link>
      </nav>

      <!-- Sidebar Footer (System Info) -->
      <div v-if="isSidebarOpen" class="p-4 border-t border-white/10 text-xs text-slate-300 bg-[#163866]/50">
        <p class="font-medium text-white">Sales Pro Enterprise v1.0</p>
        <p class="text-white/60">Odoo Connected &bull; Reverb Active</p>
      </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- TOPBAR HEADER (60% Brand Primary: #FFFFFF) -->
      <header class="h-16 bg-white border-b border-slate-200/80 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm">
        <!-- Search Quick Bar -->
        <div class="flex items-center gap-4 flex-1 max-w-md">
          <div class="relative w-full">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input 
              type="text" 
              placeholder="Cari sales, toko, atau log..." 
              class="w-full pl-9 pr-4 py-1.5 text-sm bg-slate-100/70 border-none rounded-lg focus:ring-2 focus:ring-[#F48110] transition-all"
            />
          </div>
        </div>

        <!-- Topbar Right Actions -->
        <div class="flex items-center gap-4">
          <!-- Realtime Sync Status Indicator -->
          <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Reverb Connected
          </div>

          <!-- User Profile Dropdown -->
          <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
            <div class="w-9 h-9 rounded-full bg-[#1C467F] text-white flex items-center justify-center font-bold text-sm shadow-sm">
              {{ page.props.auth?.user?.name ? page.props.auth.user.name.charAt(0) : 'A' }}
            </div>
            <div class="hidden md:block text-left">
              <p class="text-sm font-semibold text-[#1C467F] leading-none">
                {{ page.props.auth?.user?.name || 'Administrator' }}
              </p>
              <p class="text-xs text-slate-500 mt-0.5">Super Admin</p>
            </div>
          </div>
        </div>
      </header>

      <!-- PAGE CONTENT AREA -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>