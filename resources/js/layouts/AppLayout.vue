<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col shrink-0">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
        <img v-if="restaurant.logo_url" :src="restaurant.logo_url" alt="Restaurant logo" class="w-10 h-10 rounded-lg object-cover border border-gray-700" />
        <span v-else class="text-2xl">🍻</span>
        <div>
          <p class="font-bold text-gold-400 text-sm leading-tight">{{ restaurant.name }}</p>
          <p class="text-xs text-gray-400">POS & Inventory System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto">
        <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</div>
        <router-link v-for="item in navItems" :key="item.to" :to="item.to"
          class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
          :class="isNavActive(item.to)
            ? 'bg-gold-600 text-white hover:bg-gold-700'
            : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          {{ item.label }}
        </router-link>

        <!-- Admin-only section -->
        <template v-if="['admin', 'owner'].includes(auth.user?.role)">
          <div class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</div>
          <router-link v-for="item in adminNavItems" :key="item.to" :to="item.to"
            class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors"
            :class="isNavActive(item.to)
              ? 'bg-gold-600 text-white hover:bg-gold-700'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white'">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            {{ item.label }}
          </router-link>
        </template>
      </nav>

      <!-- User info -->
      <div class="px-4 py-4 border-t border-gray-800">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-gold-600 flex items-center justify-center text-sm font-bold">
            {{ auth.user?.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
          <button @click="doLogout" title="Logout"
            class="p-1 rounded text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main area -->
    <div class="flex-1 flex flex-col min-h-0 min-w-0">
      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
        <div class="flex items-center gap-3 text-sm text-gray-500">
          <span>{{ currentDate }}</span>
          <button @click="showGuide = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 text-xs font-semibold transition-colors">
            <span class="w-4 h-4 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold leading-none">?</span>
            Getting Started
          </button>
        </div>
      </header>

      <!-- Page -->
      <main class="flex-1 overflow-auto p-6">
        <router-view />
      </main>
    </div>
  </div>

  <GettingStarted v-model="showGuide" />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import GettingStarted from '@/components/GettingStarted.vue'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon, SparklesIcon,
  UserGroupIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon, FireIcon, TableCellsIcon, ChartBarIcon, Cog6ToothIcon, BanknotesIcon,
} from '@heroicons/vue/24/outline'

const auth      = useAuthStore()
const router    = useRouter()
const route     = useRoute()
const showGuide = ref(false)
const restaurant = ref({ name: 'Liquor Shop + Bar', logo_url: '', address: '' })

const navItems = [
  { to: '/',           label: 'Dashboard',        icon: HomeIcon },
  { to: '/products',   label: 'Products',         icon: CubeIcon },
  { to: '/categories', label: 'Menu Categories',  icon: TagIcon },
  { to: '/customers',  label: 'Guests',           icon: UsersIcon },
  { to: '/tables',     label: 'Tables',           icon: TableCellsIcon },
  { to: '/suppliers',  label: 'Suppliers',        icon: TruckIcon },
  { to: '/sales',      label: 'POS Billing',      icon: ShoppingCartIcon },
  { to: '/reports',    label: 'Reports',          icon: ChartBarIcon },
  { to: '/purchases',  label: 'Purchase Orders',   icon: ArchiveBoxIcon },
]

const adminNavItems = [
  { to: '/price-matrix', label: 'Price Matrix',   icon: SparklesIcon },
  { to: '/opening-balance', label: 'Opening Balance', icon: ClipboardDocumentListIcon },
  { to: '/grn',        label: 'GRN',            icon: ClipboardDocumentCheckIcon },
  { to: '/supplier-returns', label: 'Supplier Returns', icon: ArchiveBoxIcon },
  { to: '/open-bottles', label: 'Open Bottles', icon: SparklesIcon },
  { to: '/bottle-deposits', label: 'Bottle Deposits', icon: CurrencyDollarIcon },
  { to: '/finance',    label: 'Finance',        icon: BanknotesIcon },
  { to: '/damages',    label: 'Damages',        icon: FireIcon },
  { to: '/audit-log',  label: 'Stock Ledger',   icon: ClipboardDocumentListIcon },
  { to: '/users',      label: 'Users & Roles',  icon: UserGroupIcon },
  { to: '/settings',   label: 'Settings',       icon: Cog6ToothIcon },
]

const pageTitles = {
  dashboard:     'Dashboard',
  products:      'Products',
  categories:    'Menu Categories',
  customers:     'Guests',
  tables:        'Restaurant Tables',
  suppliers:     'Suppliers',
  sales:         'POS Billing',
  'sales.new':   'New Bill',
  'sales.edit':  'Edit Draft Bill',
  'sales.receipt': 'Bill Receipt',
  purchases:     'Purchase Orders',
  'purchases.new': 'New Purchase Order',
  'price-matrix':    'Price Matrix',
  'grn':           'Goods Received Notes',
  'supplier-returns': 'Supplier Returns',
  'open-bottles':  'Open Bottle Tracking',
  'users':         'Users & Roles',
  'settings':      'Restaurant Settings',
  'reports':       'Reports & Analytics',
  'finance':       'Finance Management',
  'day-end':       'Shift Close',
  'audit-log':     'Stock Ledger',
  'bottle-deposits':      'Bottle Deposits',
  'damages':         'Damages & Waste',
  'opening-balance': 'Opening Balances',
}

const pageTitle  = computed(() => pageTitles[route.name] ?? 'Liquor Shop POS')
const currentDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

async function doLogout() {
  await auth.logout()
  router.push('/login')
}

async function loadRestaurant() {
  try {
    const { data } = await axios.get('/api/settings/restaurant')
    restaurant.value = {
      name: data.name || 'Liquor Shop + Bar',
      logo_url: data.logo_url || '',
      address: data.address || '',
    }
  } catch {
    // Keep fallbacks if settings are unavailable.
  }
}

function isNavActive(targetPath) {
  if (targetPath === '/') {
    return route.path === '/'
  }
  return route.path === targetPath || route.path.startsWith(`${targetPath}/`)
}

onMounted(loadRestaurant)
</script>
