<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside :class="collapsed ? 'w-16' : 'w-64'" class="bg-gray-900 text-white flex flex-col shrink-0 transition-all duration-200">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-3 py-5 border-b border-gray-800 min-h-[72px]">
        <img v-if="restaurant.logo_url" :src="restaurant.logo_url" alt="Restaurant logo" class="w-10 h-10 rounded-lg object-cover border border-gray-700 shrink-0" />
        <span v-else class="text-2xl shrink-0">🍻</span>
        <div v-if="!collapsed" class="overflow-hidden">
          <p class="font-bold text-gold-400 text-sm leading-tight truncate">{{ restaurant.name }}</p>
          <p class="text-xs text-gray-400">POS & Inventory System</p>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden">
        <div v-if="!collapsed" class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</div>
        <router-link v-for="item in navItems" :key="item.to" :to="item.to"
          :title="collapsed ? item.label : ''"
          :class="[
            'flex items-center py-2.5 mx-2 rounded-lg text-sm transition-colors',
            collapsed ? 'justify-center px-0' : 'gap-3 px-4',
            isNavActive(item.to) ? 'bg-gold-600 text-white hover:bg-gold-700' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
          ]">
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span v-if="!collapsed">{{ item.label }}</span>
        </router-link>

        <!-- Admin-only section -->
        <template v-if="['admin', 'owner'].includes(auth.user?.role)">
          <div v-if="!collapsed" class="px-4 mt-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</div>
          <div v-else class="my-3 mx-3 border-t border-gray-700"></div>
          <router-link v-for="item in adminNavItems" :key="item.to" :to="item.to"
            :title="collapsed ? item.label : ''"
            :class="[
              'flex items-center py-2.5 mx-2 rounded-lg text-sm transition-colors',
              collapsed ? 'justify-center px-0' : 'gap-3 px-4',
              isNavActive(item.to) ? 'bg-gold-600 text-white hover:bg-gold-700' : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            ]">
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
        </template>
      </nav>

      <!-- User info + collapse toggle -->
      <div class="px-2 py-4 border-t border-gray-800 space-y-2">
        <!-- Toggle button -->
        <button @click="toggleCollapse"
          :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          class="w-full flex items-center justify-center gap-2 py-1.5 rounded-lg text-gray-400 hover:text-white hover:bg-gray-700 transition-colors text-xs">
          <ChevronDoubleLeftIcon v-if="!collapsed" class="w-4 h-4" />
          <ChevronDoubleRightIcon v-else class="w-4 h-4" />
          <span v-if="!collapsed">Collapse</span>
        </button>

        <div :class="collapsed ? 'justify-center' : 'gap-3'" class="flex items-center">
          <div class="w-8 h-8 rounded-full bg-gold-600 flex items-center justify-center text-sm font-bold shrink-0">
            {{ auth.user?.name?.charAt(0) }}
          </div>
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</p>
          </div>
          <button v-if="!collapsed" @click="doLogout" title="Logout"
            class="p-1 rounded text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
            <ArrowRightOnRectangleIcon class="w-5 h-5" />
          </button>
          <button v-else @click="doLogout" title="Logout"
            class="p-1 rounded text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
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
          <button @click="openShiftModal"
            :class="currentShift
              ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
              : 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100'"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors">
            <span :class="currentShift ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
            {{ currentShift ? 'Shift Open' : 'Open Shift' }}
          </button>
          <button v-if="currentShift" @click="showCashOutModal = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100 text-xs font-semibold transition-colors">
            <BanknotesIcon class="w-3.5 h-3.5" />
            Cash Out
          </button>
          <button @click="showGuide = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 text-xs font-semibold transition-colors">
            <span class="w-4 h-4 rounded-full bg-amber-500 text-white flex items-center justify-center text-[10px] font-bold leading-none">?</span>
            Getting Started
          </button>
        </div>
      </header>

      <!-- Page -->
      <main :class="['sales.new', 'sales.new2'].includes(route.name) ? 'flex-1 overflow-hidden' : 'flex-1 overflow-auto p-6'">
        <router-view />
      </main>
    </div>
  </div>

  <GettingStarted v-model="showGuide" />
  <ShiftModal v-if="showShiftModal" :current-shift="currentShift" @close="showShiftModal = false" @shifted="onShifted" />
  <CashOutModal v-if="showCashOutModal" @close="showCashOutModal = false" @saved="showCashOutModal = false" />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import GettingStarted from '@/components/GettingStarted.vue'
import ShiftModal from '@/components/ShiftModal.vue'
import CashOutModal from '@/components/CashOutModal.vue'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon, SparklesIcon,
  UserGroupIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon, FireIcon, TableCellsIcon, ChartBarIcon, Cog6ToothIcon, BanknotesIcon,
  ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
} from '@heroicons/vue/24/outline'

const auth      = useAuthStore()
const router    = useRouter()
const route     = useRoute()
const showGuide      = ref(false)
const showShiftModal = ref(false)
const showCashOutModal = ref(false)
const currentShift   = ref(null)
const restaurant = ref({ name: 'Liquor Shop + Bar', logo_url: '', address: '' })

const collapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value)
}


const allNavItems = [
  { to: '/',           label: 'Dashboard',        icon: HomeIcon,         roles: null },
  { to: '/products',   label: 'Products',         icon: CubeIcon,         roles: ['admin', 'owner', 'manager', 'store_keeper'] },
  { to: '/categories', label: 'Menu Categories',  icon: TagIcon,          roles: ['admin', 'owner', 'manager'] },
  { to: '/customers',  label: 'Guests',           icon: UsersIcon,        roles: ['admin', 'owner', 'manager'] },
  { to: '/tables',     label: 'Tables',           icon: TableCellsIcon,   roles: ['admin', 'owner', 'manager'] },
  { to: '/suppliers',  label: 'Suppliers',        icon: TruckIcon,        roles: ['admin', 'owner', 'manager'] },
  { to: '/sales',      label: 'POS Billing',      icon: ShoppingCartIcon, roles: null },
  { to: '/reports',    label: 'Reports',          icon: ChartBarIcon,     roles: ['admin', 'owner', 'manager'] },
  { to: '/purchases',  label: 'Purchase Orders',  icon: ArchiveBoxIcon,   roles: ['admin', 'owner', 'manager', 'store_keeper'] },
]

const navItems = computed(() => {
  const role = auth.user?.role
  return allNavItems.filter(item => !item.roles || item.roles.includes(role))
})

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
  'sales.new2':  'New Bill',
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
  window.location.href = '/login'
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

async function openShiftModal() {
  await loadCurrentShift()
  showShiftModal.value = true
}

function isNavActive(targetPath) {
  if (targetPath === '/') {
    return route.path === '/'
  }
  return route.path === targetPath || route.path.startsWith(`${targetPath}/`)
}

async function loadCurrentShift() {
  try {
    const { data } = await axios.get('/api/cashier-shifts/current')
    currentShift.value = (data && data.id) ? data : null
  } catch {
    currentShift.value = null
  }
}

function onShifted(shift) {
  currentShift.value = shift
}

onMounted(() => { loadRestaurant(); loadCurrentShift() })
</script>
