<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden">
    <!-- Sidebar -->
    <aside :class="sidebarHidden ? 'w-0 overflow-hidden' : collapsed ? 'w-16' : 'w-64'" class="bg-gray-900 text-white flex flex-col shrink-0 transition-all duration-200">
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
        <div class="flex items-center gap-3">
          <router-link v-if="sidebarHidden" to="/" title="Dashboard"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold transition-colors">
            <HomeIcon class="w-4 h-4" />
            Home
          </router-link>
          <h1 class="text-lg font-semibold text-gray-800">{{ pageTitle }}</h1>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-500">
          <button @click="toggleSidebarHidden"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors">
            <ArrowsPointingOutIcon v-if="!sidebarHidden" class="w-3.5 h-3.5" />
            <ArrowsPointingInIcon v-else class="w-3.5 h-3.5" />
            {{ sidebarHidden ? 'Exit Full Screen' : 'Full Screen' }}
          </button>
          <span>{{ currentDate }}</span>

          <!-- Order notification bell -->
          <div class="relative" ref="bellRef">
            <button @click="showNotifPanel = !showNotifPanel"
              class="relative p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 transition-colors">
              <BellIcon class="w-5 h-5" />
              <span v-if="unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] font-black flex items-center justify-center leading-none">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <!-- Dropdown -->
            <div v-if="showNotifPanel"
              class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden">
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <span class="font-bold text-gray-800 text-sm">Customer Orders</span>
                <button v-if="notifications.length" @click="clearNotifications"
                  class="text-xs text-gray-400 hover:text-red-500 transition-colors">Clear all</button>
              </div>
              <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                <div v-if="!notifications.length" class="py-10 text-center text-sm text-gray-400">
                  No new orders yet
                </div>
                <div v-for="n in notifications" :key="n.id"
                  @click="openOrder(n)"
                  class="flex items-start gap-3 px-4 py-3 hover:bg-amber-50 cursor-pointer transition-colors"
                  :class="{ 'bg-amber-50/30': !n.read }">
                  <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center shrink-0 mt-0.5 text-base">🛎️</div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-800">Table {{ n.table_number }}</p>
                    <p class="text-xs text-gray-500">{{ n.items_count }} item{{ n.items_count !== 1 ? 's' : '' }} · LKR {{ lkrFmt(n.total) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ n.invoice_number }} · {{ n.at }}</p>
                  </div>
                  <div v-if="!n.read" class="w-2 h-2 bg-amber-500 rounded-full mt-2 shrink-0"></div>
                </div>
              </div>
            </div>
          </div>
          <button @click="openShiftModal"
            :class="currentShift
              ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
              : 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100'"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition-colors">
            <span :class="currentShift ? 'bg-green-500 animate-pulse' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
            <span v-if="currentShift">
              Shift · Since {{ new Date(currentShift.opened_at).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' }) }}
            </span>
            <span v-else>No Shift — Start Now</span>
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
          <button v-if="sidebarHidden" @click="doLogout" title="Logout"
            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
          </button>
        </div>
      </header>

      <!-- Page -->
      <main :class="route.name === 'sales.new' ? 'flex-1 overflow-hidden' : 'flex-1 overflow-auto p-6'">
        <router-view />
      </main>
    </div>
  </div>

  <GettingStarted v-model="showGuide" />
  <ShiftModal v-if="showShiftModal" :current-shift="currentShift" :required="shiftRequired || shiftStale" :stale="shiftStale" @close="ui.closeShiftModal()" @shifted="onShifted" />
  <CashOutModal v-if="showCashOutModal" @close="showCashOutModal = false" @saved="showCashOutModal = false" />
  <ToastContainer ref="toastRef" />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import axios from 'axios'
import GettingStarted from '@/components/GettingStarted.vue'
import ShiftModal from '@/components/ShiftModal.vue'
import CashOutModal from '@/components/CashOutModal.vue'
import ToastContainer from '@/components/ToastContainer.vue'
import {
  HomeIcon, CubeIcon, TagIcon, UsersIcon,
  TruckIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightOnRectangleIcon, SparklesIcon,
  UserGroupIcon, ClipboardDocumentCheckIcon,
  ClipboardDocumentListIcon, CurrencyDollarIcon, FireIcon, TableCellsIcon, ChartBarIcon, Cog6ToothIcon, BanknotesIcon,
  ChevronDoubleLeftIcon, ChevronDoubleRightIcon,
  ArrowsPointingOutIcon, ArrowsPointingInIcon, BellIcon,
} from '@heroicons/vue/24/outline'

const auth      = useAuthStore()
const ui        = useUiStore()
const router    = useRouter()
const route     = useRoute()
const showGuide      = ref(false)
const showShiftModal  = computed({
  get: () => ui.shiftModalOpen,
  set: (v) => v ? ui.openShiftModal() : ui.closeShiftModal(),
})
const shiftRequired   = ref(false)
const shiftStale      = ref(false)
const showCashOutModal = ref(false)
const currentShift    = ref(null)
const restaurant = ref({ name: 'Liquor Shop + Bar', logo_url: '', address: '' })

const collapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')
const sidebarHidden = ref(false)

// Order notifications
const notifications  = ref([])
const showNotifPanel = ref(false)
const bellRef        = ref(null)
const toastRef       = ref(null)
let   echoChannel    = null
let   notifId        = 0

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

function lkrFmt(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function clearNotifications() {
  notifications.value = []
}

function openOrder(n) {
  n.read = true
  showNotifPanel.value = false
  router.push({ name: 'sales.new', query: { draft: n.sale_id } })
}

function handleClickOutsideBell(e) {
  if (bellRef.value && !bellRef.value.contains(e.target)) {
    if (showNotifPanel.value) {
      notifications.value.forEach(n => { n.read = true })
      axios.post('/api/notifications/read').catch(() => {})
    }
    showNotifPanel.value = false
  }
}

function subscribeToOrders() {
  if (!window.Echo || !auth.user?.branch_id) {
    console.warn('[Echo] not ready or no branch_id', { Echo: !!window.Echo, branch_id: auth.user?.branch_id })
    return
  }
  const channelName = `branch.${auth.user.branch_id}.orders`
  console.log('[Echo] subscribing to', channelName)
  echoChannel = window.Echo.channel(channelName)
    .listen('.customer.order.placed', (data) => {
      console.log('[Echo] order received', data)
      notifications.value.unshift({ id: ++notifId, read: false, ...data })
      if (notifications.value.length > 50) notifications.value.pop()
      toastRef.value?.add({
        type:    'order',
        icon:    '🛎️',
        title:   `New order — Table ${data.table_number}`,
        message: `${data.items_count} item${data.items_count !== 1 ? 's' : ''} · LKR ${lkrFmt(data.total)}`,
        duration: 8000,
      })
    })
}

function unsubscribeFromOrders() {
  if (!window.Echo || !echoChannel) return
  window.Echo.leaveChannel(`branch.${auth.user?.branch_id}.orders`)
  echoChannel = null
}

async function loadNotifications() {
  try {
    const { data } = await axios.get('/api/notifications')
    // Merge DB records — put them behind any real-time ones already received
    const existingIds = new Set(notifications.value.map(n => n.invoice_number))
    const fresh = data
      .filter(n => !existingIds.has(n.invoice_number))
      .map(n => ({
        id:              ++notifId,
        read:            !!n.read_at,
        sale_id:         n.sale_id,
        invoice_number:  n.invoice_number,
        table_number:    n.table_number,
        total:           n.total,
        items_count:     n.items_count,
        at:              new Date(n.created_at).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' }),
      }))
    notifications.value = [...notifications.value, ...fresh]
  } catch { /* silent */ }
}

async function registerWebPush() {
  const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY
  if (!vapidKey || !('serviceWorker' in navigator) || !('PushManager' in window)) return
  try {
    const reg = await navigator.serviceWorker.register('/sw.js')
    const permission = await Notification.requestPermission()
    if (permission !== 'granted') return

    const existing = await reg.pushManager.getSubscription()
    const sub = existing || await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidKey),
    })

    await axios.post('/api/push/subscribe', {
      endpoint:   sub.endpoint,
      public_key: btoa(String.fromCharCode(...new Uint8Array(sub.getKey('p256dh')))),
      auth_token: btoa(String.fromCharCode(...new Uint8Array(sub.getKey('auth')))),
    })
  } catch (e) {
    console.warn('[WebPush] registration failed', e)
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4)
  const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const raw     = atob(base64)
  return Uint8Array.from([...raw].map(c => c.charCodeAt(0)))
}

watch(() => route.name, (name) => {
  if (name === 'sales.new') collapsed.value = true
}, { immediate: true })

function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value)
}

function toggleSidebarHidden() {
  sidebarHidden.value = !sidebarHidden.value
}


const allNavItems = [
  { to: '/',           label: 'Dashboard',        icon: HomeIcon,         roles: null },
  { to: '/products',   label: 'Products',         icon: CubeIcon,         roles: ['admin', 'owner', 'manager', 'store_keeper'] },
  { to: '/categories', label: 'Menu Categories',  icon: TagIcon,          roles: ['admin', 'owner', 'manager'] },
  { to: '/customers',  label: 'Guests',           icon: UsersIcon,        roles: ['admin', 'owner', 'manager'] },
  { to: '/tables',     label: 'Tables',           icon: TableCellsIcon,   roles: ['admin', 'owner', 'manager'] },
  { to: '/suppliers',  label: 'Suppliers',        icon: TruckIcon,        roles: ['admin', 'owner', 'manager'] },
  { to: '/sales',        label: 'POS Billing',      icon: ShoppingCartIcon, roles: null },
  { to: '/open-bottles', label: 'Open Bottles',    icon: SparklesIcon,     roles: ['admin', 'owner', 'manager', 'cashier'] },
  { to: '/reports',      label: 'Reports',          icon: ChartBarIcon,     roles: ['admin', 'owner', 'manager'] },
  { to: '/purchases',    label: 'Purchase Orders',  icon: ArchiveBoxIcon,   roles: ['admin', 'owner', 'manager', 'store_keeper'] },
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
  { to: '/bottle-deposits', label: 'Bottle Deposits', icon: CurrencyDollarIcon },
  { to: '/finance',       label: 'Finance',        icon: BanknotesIcon },
  { to: '/shift-summary', label: 'Shift Summary',  icon: ChartBarIcon },
  { to: '/damages',       label: 'Damages',        icon: FireIcon },
  { to: '/audit-log',     label: 'Stock Ledger',   icon: ClipboardDocumentListIcon },
  { to: '/users',         label: 'Users & Roles',  icon: UserGroupIcon },
  { to: '/settings',      label: 'Settings',       icon: Cog6ToothIcon },
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
  'reports':        'Reports & Analytics',
  'shift-summary':  'Shift Summary Report',
  'finance':        'Finance Management',
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
  ui.openShiftModal()
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
  const wasStale = shiftStale.value
  currentShift.value = shift
  shiftRequired.value = false
  shiftStale.value = false
  // After closing a stale shift, immediately prompt to open today's shift
  if (!shift && wasStale && auth.user?.role === 'cashier') {
    shiftRequired.value = true
    ui.openShiftModal()
  }
}

onMounted(async () => {
  loadRestaurant()
  await loadCurrentShift()
  if (auth.user?.role === 'cashier') {
    if (!currentShift.value) {
      shiftRequired.value = true
      ui.openShiftModal()
    } else {
      const shiftDay = new Date(currentShift.value.opened_at).toDateString()
      if (shiftDay !== new Date().toDateString()) {
        shiftStale.value = true
        ui.openShiftModal()
      }
    }
  }
  await loadNotifications()
  subscribeToOrders()
  registerWebPush()
  document.addEventListener('click', handleClickOutsideBell)
})

onUnmounted(() => {
  unsubscribeFromOrders()
  document.removeEventListener('click', handleClickOutsideBell)
})
</script>
