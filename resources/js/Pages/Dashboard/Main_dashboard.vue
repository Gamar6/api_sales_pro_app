<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({

    // Props dinamis dari Controller Inertia

  auth: {
    type: Object,
    default: () => ({
      user: {
        name: 'Alex Reynolds',
        role: 'Ops Lead',
        avatar: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCFARgE1ELuppShWu5oG6X2BX4JtpfWE6qITc35o5pSPh9qNKPUDYy2U__UnmcH3sqbQJgRqnEozoWrNFaw371I9ncdXT2VlCORzO9UuaaVsiZR917I3R5r2EAiIUWAsNxB3rjgK1iqkHDRhV3-lvnyOyNq_rpCyEWMReNHevXrILrwosyT4gcm2hGD3KTjOMOcoHJiNJ7X1f3IfwncpeqP5WHvrdXIbU-bvGUa3oA2uWM5iNZO9qcG'
      }
    })
  },
  odooStatus: {
    type: Object,
    default: () => ({ lastSynced: '2m ago' })
  },
  kpi: {
    type: Object,
    default: () => ({
      activeReps: 42,
      totalReps: 48,
      activeRepsPct: 87.5,
      totalVisits: 318,
      targetVisits: 360,
      visitsPct: 88.3,
      newStores: 27,
      pendingGeoStores: 8,
      avgDuration: '28m 45s',
      durationPct: 94.2
    })
  },
  clusters: {
    type: Array,
    default: () => [
      { id: 1, name: 'North District Sector', reps: 14, target: 110, completed: 96, pct: 87 },
      { id: 2, name: 'Central District Sector', reps: 18, target: 160, completed: 148, pct: 92 },
      { id: 3, name: 'South District Sector', reps: 10, target: 90, completed: 74, pct: 82 }
    ]
  }
})

// Tab Modul Navigasi Interaktif
const activeModule = ref('dashboard')
const modules = [
  { id: 'dashboard', label: '1. Dashboard Overview', icon: 'dashboard' },
  { id: 'tracking', label: '2. Live Tracking Map', icon: 'my_location', hasPing: true },
  { id: 'visits', label: '3. Visit Logs & Reports', icon: 'assignment' },
  { id: 'stores', label: '4. Store Directory & Approval', icon: 'storefront', badge: '8' },
  { id: 'catalog', label: '5. Product Catalog', icon: 'inventory_2' },
  { id: 'odoo', label: '6. Odoo Sync Monitor', icon: 'sync_alt' },
  { id: 'users', label: '7. User & Roles', icon: 'manage_accounts' },
]

// Signal Handler
const emit = defineEmits(['quickAction', 'exportAudit', 'auditStores', 'fastApproval'])
</script>

<template>
  <div class="bg-background font-body-md text-on-surface antialiased">
    <div class="pl-sidebar-w-expanded">
      <!-- HEADER NAVBAR -->
      <header class="fixed top-0 left-sidebar-w-expanded right-0 h-topbar-h bg-surface-container-lowest border-b border-outline-variant/50 z-40 px-space-xl flex items-center justify-between shadow-[0_1px_3px_0_rgba(15,23,42,0.05)]">
        <div class="flex items-center gap-space-base flex-1 max-w-xl">
          <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
            <input 
              class="w-full h-9 pl-9 pr-space-base rounded border border-outline-variant bg-surface-bright font-body-sm text-body-sm text-on-surface placeholder:text-on-surface-variant/70 focus:outline-none focus:border-primary-container" 
              placeholder="Search rep, route ID, store #, or ERP SKU..." 
              type="text"
            />
          </div>
        </div>
        <div class="flex items-center gap-space-lg">
          <div class="hidden md:flex items-center gap-space-xs px-space-sm py-1 rounded bg-surface-container-low border border-outline-variant/40">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span class="font-code-metric text-code-metric text-on-surface-variant">Odoo synced {{ odooStatus.lastSynced }}</span>
          </div>
          <button 
            type="button"
            class="h-9 px-space-base bg-primary-container text-on-primary rounded font-title-md text-title-md flex items-center gap-space-xs hover:bg-primary transition-colors"
            @click="emit('quickAction')"
          >
            <span class="material-symbols-outlined text-[16px]">add</span>
            <span>Quick Action</span>
          </button>
          <button type="button" class="relative p-space-xs text-on-surface-variant hover:text-on-surface">
            <span class="material-symbols-outlined text-[22px]">notifications</span>
            <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-tertiary-container border-2 border-surface-container-lowest"></span>
          </button>
          <div class="h-6 w-px bg-outline-variant/60"></div>
          <div class="flex items-center gap-space-sm pl-space-xs">
            <img :src="auth.user.avatar" alt="Profile" class="w-8 h-8 rounded-full object-cover"/>
            <div class="hidden lg:flex flex-col text-left">
              <span class="font-title-md text-title-md text-on-surface leading-none">{{ auth.user.name }}</span>
              <span class="font-label-caps text-label-caps text-on-surface-variant uppercase mt-0.5">{{ auth.user.role }}</span>
            </div>
            <span class="material-symbols-outlined text-on-surface-variant text-[16px]">expand_more</span>
          </div>
        </div>
      </header>

      <!-- MAIN CONTENT -->
      <main class="relative pt-topbar-h w-full min-h-screen bg-background">
        <div class="flex flex-col w-full">
          <div class="p-space-xl flex flex-col gap-space-xl">
            <!-- SUBHEADER STRIP -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md bg-surface-container-lowest p-space-lg rounded-lg shadow-sm">
              <div class="flex items-center gap-space-md">
                <div class="w-10 h-10 rounded bg-primary-container flex items-center justify-center text-on-primary">
                  <span class="material-symbols-outlined text-[24px]">satellite_alt</span>
                </div>
                <div class="flex flex-col">
                  <div class="flex items-center gap-space-xs">
                    <h1 class="font-headline-md text-headline-md text-primary leading-tight">Field Command Operational Center</h1>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 text-tertiary-container font-label-caps text-label-caps">
                      <span class="w-1.5 h-1.5 rounded-full bg-on-tertiary-container animate-pulse"></span> LIVE PING
                    </span>
                  </div>
                  <p class="font-body-sm text-body-sm text-secondary">Central Territory Node · Active Roster {{ kpi.totalReps }} Field Agents · GPS Sampling Interval: 15s</p>
                </div>
              </div>
              <div class="flex items-center gap-space-sm self-start md:self-auto">
                <button type="button" class="px-space-md py-2 rounded bg-surface-container-low text-primary font-title-md text-title-md hover:bg-surface-container transition-colors flex items-center gap-1.5">
                  <span class="material-symbols-outlined text-[18px]">calendar_today</span>
                  <span>Today (Shift 08:00–18:00)</span>
                </button>
                <button 
                  type="button" 
                  class="px-space-md py-2 rounded bg-primary-container text-on-primary font-title-md text-title-md hover:bg-primary transition-colors flex items-center gap-1.5 shadow-sm"
                  @click="emit('exportAudit')"
                >
                  <span class="material-symbols-outlined text-[18px]">file_download</span>
                  <span>Export Shift Audit</span>
                </button>
              </div>
            </div>

            <!-- KPI SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-lg">
              <!-- Card 1: Active Sales Reps -->
              <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-surface-container"></div>
                <div class="flex items-center justify-between pb-space-xs">
                  <span class="font-label-caps text-label-caps text-secondary uppercase tracking-wider">Active Sales Reps Today</span>
                  <div class="flex items-center gap-1 px-1.5 py-0.5 rounded bg-orange-50 text-tertiary-container">
                    <span class="w-2 h-2 rounded-full bg-on-tertiary-container animate-ping"></span>
                    <span class="font-code-metric text-[10px]">LIVE BEACON</span>
                  </div>
                </div>
                <div class="my-space-xs">
                  <div class="flex items-baseline gap-space-xs">
                    <span class="font-display-lg text-display-lg text-primary tracking-tight">{{ kpi.activeReps }}</span>
                    <span class="font-headline-sm text-headline-sm text-secondary">/ {{ kpi.totalReps }} Reps</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                      <div class="bg-primary-container h-1.5 rounded-full" :style="{ width: `${kpi.activeRepsPct}%` }"></div>
                    </div>
                    <span class="font-code-metric text-code-metric text-primary-container font-semibold">{{ kpi.activeRepsPct }}%</span>
                  </div>
                </div>
                <div class="pt-space-xs flex items-center justify-between font-body-sm text-body-sm text-secondary">
                  <span class="flex items-center gap-1 text-emerald-600 font-medium">
                    <span class="material-symbols-outlined text-[16px]">arrow_upward</span> +4 vs yesterday
                  </span>
                  <span class="font-code-metric text-secondary">6 En Route HQ</span>
                </div>
              </div>

              <!-- Card 2: Total Check-Ins -->
              <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-surface-container"></div>
                <div class="flex items-center justify-between pb-space-xs">
                  <span class="font-label-caps text-label-caps text-secondary uppercase tracking-wider">Total Check-Ins Today</span>
                  <span class="font-label-caps text-label-caps px-2 py-0.5 rounded bg-surface-container-high text-primary font-semibold">SHIFT GOAL: {{ kpi.targetVisits }}</span>
                </div>
                <div class="my-space-xs">
                  <div class="flex items-baseline gap-space-xs">
                    <span class="font-display-lg text-display-lg text-primary tracking-tight">{{ kpi.totalVisits }}</span>
                    <span class="font-title-md text-title-md text-secondary">Visits</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                      <div class="bg-primary-container h-1.5 rounded-full" :style="{ width: `${kpi.visitsPct}%` }"></div>
                    </div>
                    <span class="font-code-metric text-code-metric text-primary-container font-semibold">{{ kpi.visitsPct }}%</span>
                  </div>
                </div>
                <div class="pt-space-xs flex items-center justify-between font-body-sm text-body-sm text-secondary">
                  <span class="flex items-center gap-1 text-emerald-600 font-medium">
                    <span class="material-symbols-outlined text-[16px]">trending_up</span> +14% vs 30d pace
                  </span>
                  <span class="font-code-metric text-secondary">{{ kpi.targetVisits - kpi.totalVisits }} visits to target</span>
                </div>
              </div>

              <!-- Card 3: New Registered Stores -->
              <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-surface-container"></div>
                <div class="flex items-center justify-between pb-space-xs">
                  <span class="font-label-caps text-label-caps text-secondary uppercase tracking-wider">New Registered Stores</span>
                  <span class="font-label-caps text-label-caps px-2 py-0.5 rounded bg-orange-100 text-tertiary-container font-bold animate-pulse">{{ kpi.pendingGeoStores }} PENDING GEO</span>
                </div>
                <div class="my-space-xs">
                  <div class="flex items-baseline gap-space-xs">
                    <span class="font-display-lg text-display-lg text-primary tracking-tight">{{ kpi.newStores }}</span>
                    <span class="font-title-md text-title-md text-secondary">Captured</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center gap-1 font-body-sm text-body-sm text-emerald-600 font-semibold">
                      <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 19 Approved
                    </span>
                    <span class="text-outline-variant">·</span>
                    <span class="inline-flex items-center gap-1 font-body-sm text-body-sm text-tertiary-container font-semibold">
                      <span class="w-2 h-2 rounded-full bg-on-tertiary-container"></span> {{ kpi.pendingGeoStores }} Verification Req.
                    </span>
                  </div>
                </div>
                <div class="pt-space-xs flex items-center justify-between font-body-sm text-body-sm text-secondary">
                  <span class="font-code-metric text-secondary">Avg Latency: 12m</span>
                  <button type="button" class="text-tertiary-container hover:underline font-label-caps text-label-caps" @click="emit('auditStores')">AUDIT NOW →</button>
                </div>
              </div>

              <!-- Card 4: Avg Visit Duration -->
              <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1 bg-surface-container"></div>
                <div class="flex items-center justify-between pb-space-xs">
                  <span class="font-label-caps text-label-caps text-secondary uppercase tracking-wider">Avg Visit Duration</span>
                  <span class="font-label-caps text-label-caps px-2 py-0.5 rounded bg-surface-container-high text-primary font-semibold">GUIDE: 20-45m</span>
                </div>
                <div class="my-space-xs">
                  <div class="flex items-baseline gap-space-xs">
                    <span class="font-display-lg text-display-lg text-primary tracking-tight">{{ kpi.avgDuration }}</span>
                    <span class="font-body-sm text-body-sm text-secondary">/ store</span>
                  </div>
                  <div class="flex items-center gap-2 mt-1">
                    <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
                      <div class="bg-emerald-500 h-1.5 rounded-full" :style="{ width: `${kpi.durationPct}%` }"></div>
                    </div>
                    <span class="font-code-metric text-code-metric text-emerald-600 font-semibold">{{ kpi.durationPct }}%</span>
                  </div>
                </div>
                <div class="pt-space-xs flex items-center justify-between font-body-sm text-body-sm text-secondary">
                  <span class="text-emerald-600 font-medium">Compliance Optimal</span>
                  <span class="font-code-metric text-secondary">6 Anomaly Flags</span>
                </div>
              </div>
            </div>

            <!-- MODULE SWITCHER BAR -->
            <div class="bg-surface-container-lowest rounded-lg p-space-sm shadow-sm overflow-x-auto">
              <div class="flex items-center gap-space-xs min-w-[780px]">
                <button
                  v-for="mod in modules"
                  :key="mod.id"
                  type="button"
                  :class="[
                    'px-space-base py-2.5 rounded font-title-md text-title-md flex items-center gap-2 transition-all',
                    activeModule === mod.id 
                      ? 'bg-primary-container text-on-primary shadow-sm' 
                      : 'bg-surface-bright text-secondary hover:bg-surface-container-low'
                  ]"
                  @click="activeModule = mod.id"
                >
                  <span class="material-symbols-outlined text-[18px]">{{ mod.icon }}</span>
                  <span>{{ mod.label }}</span>
                  <span v-if="mod.hasPing" class="w-2 h-2 rounded-full bg-on-tertiary-container animate-pulse"></span>
                  <span v-if="mod.badge" class="px-1.5 py-0.2 rounded text-[10px] bg-tertiary-container text-on-primary font-bold">{{ mod.badge }}</span>
                </button>
              </div>
            </div>

            <!-- MODULE CONTENT VIEW -->
            <div v-if="activeModule === 'dashboard'" class="flex flex-col gap-space-xl">
              <!-- SECTION A: CHART & CLUSTERS -->
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg">
                <!-- Left: SVG Chart -->
                <div class="lg:col-span-2 bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-space-base gap-2">
                    <div>
                      <div class="flex items-center gap-2">
                        <h2 class="font-headline-sm text-headline-sm text-primary font-semibold">Hourly Check-In Velocity Curve</h2>
                        <span class="px-2 py-0.5 rounded bg-surface-container text-primary font-label-caps text-label-caps">Shift: 08:00 - 18:00</span>
                      </div>
                      <p class="font-body-sm text-body-sm text-secondary">Hourly aggregation of geofence check-ins vs projected route schedules</p>
                    </div>
                    <div class="flex items-center gap-space-md font-label-caps text-label-caps text-secondary">
                      <div class="flex items-center gap-1.5">
                        <span class="w-3 h-0.5 bg-primary-container"></span>
                        <span>Actual Visits</span>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <span class="w-3 h-0.5 bg-secondary-container stroke-dashed"></span>
                        <span>Planned Target</span>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-on-tertiary-container"></span>
                        <span>Peak Activity</span>
                      </div>
                    </div>
                  </div>

                  <!-- SVG Chart Canvas -->
                  <div class="w-full overflow-hidden py-2">
                    <svg class="w-full h-56 overflow-visible" viewBox="0 0 740 220" preserveAspectRatio="none">
                      <defs>
                        <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="#1c467f" stop-opacity="0.22"></stop>
                          <stop offset="100%" stop-color="#1c467f" stop-opacity="0.00"></stop>
                        </linearGradient>
                        <linearGradient id="targetGradient" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stop-color="#bdd2fe" stop-opacity="0.15"></stop>
                          <stop offset="100%" stop-color="#bdd2fe" stop-opacity="0.0"></stop>
                        </linearGradient>
                      </defs>
                      <line stroke="#e5eeff" stroke-width="1" x1="40" x2="720" y1="20" y2="20"></line>
                      <line stroke="#e5eeff" stroke-width="1" x1="40" x2="720" y1="65" y2="65"></line>
                      <line stroke="#e5eeff" stroke-width="1" x1="40" x2="720" y1="110" y2="110"></line>
                      <line stroke="#e5eeff" stroke-width="1" x1="40" x2="720" y1="155" y2="155"></line>
                      <line stroke="#c3c6d1" stroke-width="1" x1="40" x2="720" y1="190" y2="190"></line>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="end" x="30" y="24">60</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="end" x="30" y="69">45</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="end" x="30" y="114">30</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="end" x="30" y="159">15</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="end" x="30" y="193">0</text>
                      <path d="M 50 170 L 115 145 L 180 110 L 245 75 L 310 85 L 375 70 L 440 60 L 505 85 L 570 120 L 635 150 L 700 175 L 700 190 L 50 190 Z" fill="url(#targetGradient)"></path>
                      <path d="M 50 170 L 115 145 L 180 110 L 245 75 L 310 85 L 375 70 L 440 60 L 505 85 L 570 120 L 635 150 L 700 175" fill="none" stroke="#bdd2fe" stroke-dasharray="4,4" stroke-width="2"></path>
                      <path d="M 50 180 Q 90 150 115 130 T 180 90 T 245 55 T 310 65 T 375 42 T 440 48 T 505 78 T 570 115 T 635 160 T 700 185 L 700 190 L 50 190 Z" fill="url(#areaGradient)"></path>
                      <path d="M 50 180 Q 90 150 115 130 T 180 90 T 245 55 T 310 65 T 375 42 T 440 48 T 505 78 T 570 115 T 635 160 T 700 185" fill="none" stroke="#1c467f" stroke-width="3"></path>
                      <circle cx="50" cy="180" fill="#1c467f" r="3.5"></circle>
                      <circle cx="115" cy="130" fill="#1c467f" r="3.5"></circle>
                      <circle cx="180" cy="90" fill="#1c467f" r="3.5"></circle>
                      <circle cx="245" cy="55" fill="#1c467f" r="3.5"></circle>
                      <circle cx="310" cy="65" fill="#1c467f" r="3.5"></circle>
                      <circle class="animate-ping" cx="245" cy="55" fill="#ff9d50" fill-opacity="0.3" r="6"></circle>
                      <circle cx="245" cy="55" fill="#703700" r="4.5" stroke="#ffffff" stroke-width="1.5"></circle>
                      <circle class="animate-ping" cx="375" cy="42" fill="#ff9d50" fill-opacity="0.35" r="7"></circle>
                      <circle cx="375" cy="42" fill="#703700" r="5" stroke="#ffffff" stroke-width="1.5"></circle>
                      <circle cx="440" cy="48" fill="#703700" r="4"></circle>
                      <circle cx="505" cy="78" fill="#1c467f" r="3.5"></circle>
                      <circle cx="570" cy="115" fill="#1c467f" r="3.5"></circle>
                      <circle cx="635" cy="160" fill="#1c467f" r="3.5"></circle>
                      <circle cx="700" cy="185" fill="#1c467f" r="3.5"></circle>
                      <g transform="translate(340, 10)">
                        <rect fill="#703700" height="22" rx="3" width="70"></rect>
                        <text fill="#ffffff" font-family="Inter" font-size="10" font-weight="700" text-anchor="middle" x="35" y="15">PEAK: 54/hr</text>
                      </g>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="50" y="210">08:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="115" y="210">09:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="180" y="210">10:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="245" y="210">11:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="310" y="210">12:00</text>
                      <text fill="#0b1c30" font-family="Inter" font-size="10" font-weight="700" text-anchor="middle" x="375" y="210">13:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="440" y="210">14:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="505" y="210">15:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="570" y="210">16:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="635" y="210">17:00</text>
                      <text fill="#737781" font-family="Inter" font-size="10" text-anchor="middle" x="700" y="210">18:00</text>
                    </svg>
                  </div>

                  <div class="grid grid-cols-3 gap-2 pt-space-sm bg-surface-container-low p-space-sm rounded">
                    <div class="flex flex-col">
                      <span class="font-label-caps text-[10px] text-secondary uppercase">Current Velocity</span>
                      <span class="font-title-md text-title-md text-primary font-bold">36 Visits/hr</span>
                    </div>
                    <div class="flex flex-col">
                      <span class="font-label-caps text-[10px] text-secondary uppercase">Projected End-of-Shift</span>
                      <span class="font-title-md text-title-md text-primary font-bold">364 Visits <span class="text-emerald-600 font-normal text-xs">(+1.1%)</span></span>
                    </div>
                    <div class="flex flex-col">
                      <span class="font-label-caps text-[10px] text-secondary uppercase">Optimal Route Efficiency</span>
                      <span class="font-title-md text-title-md text-primary font-bold">91.8% Adherence</span>
                    </div>
                  </div>
                </div>

                <!-- Right: Cluster Deployment -->
                <div class="bg-surface-container-lowest p-space-lg rounded-lg shadow-sm flex flex-col justify-between">
                  <div>
                    <div class="flex items-center justify-between pb-space-sm">
                      <h2 class="font-headline-sm text-headline-sm text-primary font-semibold">Cluster Deployment</h2>
                      <span class="material-symbols-outlined text-secondary text-[20px]">hub</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary pb-space-md">Live agent dispersal and device telemetry status</p>
                    
                    <div class="flex flex-col gap-space-sm">
                      <div v-for="cluster in clusters" :key="cluster.id" class="p-space-sm rounded bg-surface-container-low flex flex-col gap-1">
                        <div class="flex items-center justify-between">
                          <span class="font-title-md text-title-md text-primary">{{ cluster.name }}</span>
                          <span class="font-code-metric text-code-metric text-primary-container font-bold">{{ cluster.reps }} Reps</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-secondary">
                          <span>Target: {{ cluster.target }} Visits</span>
                          <span class="text-emerald-600 font-medium">{{ cluster.completed }} Completed ({{ cluster.pct }}%)</span>
                        </div>
                        <div class="w-full bg-surface-container rounded-full h-1.5 overflow-hidden mt-1">
                          <div class="bg-primary-container h-1.5 rounded-full" :style="{ width: `${cluster.pct}%` }"></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-space-md pt-space-sm bg-surface-bright p-space-sm rounded">
                    <span class="font-label-caps text-label-caps text-secondary uppercase block mb-2">Hardware Telemetry Health</span>
                    <div class="grid grid-cols-2 gap-2">
                      <div class="flex items-center gap-2 p-2 rounded bg-surface-container-lowest">
                        <span class="material-symbols-outlined text-emerald-600 text-[18px]">gps_fixed</span>
                        <div class="flex flex-col">
                          <span class="font-code-metric text-xs font-bold text-on-surface">40 High-Acc.</span>
                          <span class="text-[10px] text-secondary">&lt; 5m error radius</span>
                        </div>
                      </div>
                      <div class="flex items-center gap-2 p-2 rounded bg-orange-50">
                        <span class="material-symbols-outlined text-tertiary-container text-[18px]">warning</span>
                        <div class="flex flex-col">
                          <span class="font-code-metric text-xs font-bold text-tertiary-container">2 Degraded</span>
                          <span class="text-[10px] text-tertiary-container">Basement/Dense</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- DISPATCH ALERT BANNER -->
              <div class="bg-gradient-to-r from-orange-50 via-surface-container-lowest to-surface-container-low p-space-base rounded-lg shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-space-md">
                <div class="flex items-center gap-space-md">
                  <div class="w-10 h-10 rounded-full bg-tertiary-container text-on-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[22px]">notification_important</span>
                  </div>
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                      <span class="font-headline-sm text-headline-sm text-primary font-bold">Fast-Action Dispatch Alert</span>
                      <span class="px-2 py-0.5 rounded bg-tertiary-container text-on-primary font-label-caps text-[10px] uppercase">Urgent Proximity</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary">2 Newly registered stores awaiting geofence approval within 1.2km radius of rep position (Elena Rostova - Agent #R-104)</p>
                  </div>
                </div>
                <div class="flex items-center gap-space-sm shrink-0">
                  <button type="button" class="px-space-md py-2 rounded bg-surface-container-lowest text-primary font-title-md text-title-md hover:bg-surface-bright transition-colors shadow-sm">
                    Dismiss Flag
                  </button>
                  <button 
                    type="button" 
                    class="px-space-md py-2 rounded bg-tertiary-container text-on-primary font-title-md text-title-md hover:bg-tertiary transition-colors shadow-sm flex items-center gap-1.5"
                    @click="emit('fastApproval')"
                  >
                    <span class="material-symbols-outlined text-[16px]">verified</span>
                    <span>1-Click Fast Approval</span>
                  </button>
                </div>
              </div>

              <!-- SECTION B: REAL-TIME FEED HEADER (Sambungan dari potongan pesanmu) -->
              <div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col">
                <div class="p-space-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <h2 class="font-headline-sm text-headline-sm text-primary font-semibold">Real-Time Field Telemetry Feed</h2>
                      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                      <span class="font-code-metric text-xs text-secondary">5 latest verified check-in packets</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-secondary">Live validation feed cross-referencing GPS ping coordinates vs Odoo ERP store records</p>
                  </div>
                  <div class="flex items-center gap-space-sm">
                    <span class="font-label-caps text-label-caps text-secondary uppercase">Feed Auto-Refreshes in:</span>
                    <span class="font-code-metric text-code-metric font-bold text-primary-container px-2 py-0.5 rounded bg-surface-container">00:08s</span>
                  </div>
                </div>
                
                <!-- Di sini tempat isi tabel/feed lanjutan dari pesan ke-2 kamu -->
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>