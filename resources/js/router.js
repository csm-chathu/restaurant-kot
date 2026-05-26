import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

NProgress.configure({ showSpinner: false, speed: 300, minimum: 0.1 })

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '',          name: 'dashboard',  component: () => import('@/pages/Dashboard.vue') },
            { path: 'products',  name: 'products',   component: () => import('@/pages/Products.vue') },
            { path: 'categories',name: 'categories', component: () => import('@/pages/Categories.vue') },
            { path: 'customers', name: 'customers',  component: () => import('@/pages/Customers.vue') },
            { path: 'suppliers', name: 'suppliers',  component: () => import('@/pages/Suppliers.vue') },
            { path: 'sales',          name: 'sales',          component: () => import('@/pages/Sales.vue') },
            { path: 'sales/new',      name: 'sales.new',      component: () => import('@/pages/NewSale.vue') },
            { path: 'sales/new2',     name: 'sales.new2',     component: () => import('@/pages/NewSale2.vue') },
            { path: 'sales/:id/edit', name: 'sales.edit',     component: () => import('@/pages/EditDraft.vue') },
            { path: 'sales/:id',      name: 'sales.receipt',  component: () => import('@/pages/SaleReceipt.vue') },
            { path: 'tables',         name: 'tables',         component: () => import('@/pages/Tables.vue') },
            { path: 'purchases', name: 'purchases',  component: () => import('@/pages/Purchases.vue') },
            { path: 'purchases/new', name: 'purchases.new', component: () => import('@/pages/NewPurchase.vue') },
            { path: 'price-matrix',  name: 'price-matrix',  component: () => import('@/pages/PriceMatrix.vue') },
            { path: 'bottle-deposits', name: 'bottle-deposits', component: () => import('@/pages/BottleDeposits.vue') },
            { path: 'damages',       name: 'damages',       component: () => import('@/pages/Damages.vue') },
            { path: 'users',         name: 'users',         component: () => import('@/pages/Users.vue') },
            { path: 'settings',      name: 'settings',      component: () => import('@/pages/RestaurantSettings.vue') },
            { path: 'reports',       name: 'reports',       component: () => import('@/pages/Reports.vue') },
            { path: 'day-end',       name: 'day-end',       component: () => import('@/pages/DayEnd.vue') },
            { path: 'finance',       name: 'finance',       component: () => import('@/pages/Finance.vue') },
            { path: 'audit-log',     name: 'audit-log',     component: () => import('@/pages/StockLedger.vue') },
            { path: 'grn',           name: 'grn',           component: () => import('@/pages/GRN.vue') },
            { path: 'supplier-returns', name: 'supplier-returns', component: () => import('@/pages/SupplierReturns.vue') },
            { path: 'open-bottles',    name: 'open-bottles',    component: () => import('@/pages/OpenBottles.vue') },
            { path: 'opening-balance', name: 'opening-balance', component: () => import('@/pages/OpeningBalance.vue') },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    NProgress.start()
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.token) return '/login'
    if (to.meta.guest && auth.token) return '/'
})

router.afterEach(() => { NProgress.done() })

export default router
