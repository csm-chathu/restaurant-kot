<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <div class="flex flex-col items-center gap-3 text-gray-400">
        <svg class="w-10 h-10 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
        <p class="text-sm">Loading menu…</p>
      </div>
    </div>

    <!-- Invalid table -->
    <div v-else-if="tableError" class="flex-1 flex items-center justify-center p-6">
      <div class="text-center space-y-3">
        <div class="text-5xl">❌</div>
        <p class="font-bold text-gray-800 text-lg">Invalid QR Code</p>
        <p class="text-sm text-gray-500">{{ tableError }}</p>
      </div>
    </div>

    <!-- Phone number entry -->
    <div v-else-if="!phone" class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-sm space-y-6">
        <div class="text-center space-y-2">
          <div v-if="branch.logo" class="flex justify-center mb-3">
            <img :src="branch.logo" :alt="branch.name" class="h-16 object-contain" />
          </div>
          <h1 class="text-2xl font-black text-gray-900">{{ branch.name }}</h1>
          <p class="text-sm text-gray-500">Table <span class="font-bold text-amber-600">{{ tableNumber }}</span></p>
          <p class="text-base text-gray-600 mt-4">Enter your phone number to start ordering</p>
        </div>
        <div class="space-y-3">
          <input
            v-model="phoneInput"
            type="tel"
            placeholder="e.g. 0771234567"
            maxlength="15"
            class="w-full px-4 py-3.5 rounded-2xl border-2 border-gray-200 focus:border-amber-400 text-lg text-center font-semibold focus:outline-none"
            @keyup.enter="confirmPhone"
            autofocus
          />
          <p v-if="phoneError" class="text-sm text-red-500 text-center">{{ phoneError }}</p>
          <button
            @click="confirmPhone"
            :disabled="!phoneInput.trim()"
            class="w-full py-4 bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white rounded-2xl font-black text-lg transition-colors shadow-lg shadow-amber-500/30"
          >
            View Menu →
          </button>
        </div>
      </div>
    </div>

    <!-- Menu -->
    <template v-else>
      <!-- Top bar -->
      <div class="sticky top-0 z-20 bg-white border-b border-gray-200 shadow-sm">
        <div class="flex items-center gap-3 px-4 py-3">
          <div class="flex-1 min-w-0">
            <p class="font-bold text-gray-900 text-sm truncate">{{ branch.name }}</p>
            <p class="text-xs text-amber-600 font-semibold">Table {{ tableNumber }}</p>
          </div>
          <div class="text-xs text-gray-400">👤 {{ phone }}</div>
        </div>

        <!-- Category tabs -->
        <div class="flex gap-1.5 overflow-x-auto px-3 pb-2 scrollbar-hide">
          <button
            @click="activeCategory = ''"
            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-colors"
            :class="activeCategory === '' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
          >All</button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            @click="activeCategory = cat.id"
            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-colors"
            :class="activeCategory === cat.id ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600'"
          >{{ cat.name }}</button>
        </div>
      </div>

      <!-- Search -->
      <div class="px-4 pt-3 pb-1">
        <input
          v-model="search"
          type="search"
          placeholder="Search items…"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:border-amber-400 bg-white"
        />
      </div>

      <!-- Product grid -->
      <div class="flex-1 overflow-y-auto px-3 py-2 pb-32">
        <div v-if="!filteredProducts.length" class="flex flex-col items-center justify-center py-16 text-gray-400">
          <p class="text-4xl mb-3">🍽️</p>
          <p class="text-sm">No items found</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div
            v-for="product in filteredProducts"
            :key="product.id"
            @click="addToCart(product)"
            class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 flex flex-col cursor-pointer active:scale-95 transition-transform select-none"
            :class="cartQty(product.id) > 0 ? 'ring-2 ring-amber-400' : ''"
          >
            <!-- Image -->
            <div class="w-full aspect-square bg-gray-100 relative">
              <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-4xl">🍽️</div>
              <!-- Qty badge -->
              <div v-if="cartQty(product.id) > 0"
                class="absolute top-2 right-2 w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center text-xs font-black shadow">
                {{ cartQty(product.id) }}
              </div>
            </div>
            <!-- Info -->
            <div class="p-2.5 flex flex-col flex-1">
              <p class="text-sm font-bold text-gray-800 leading-tight">{{ product.name }}</p>
              <p v-if="product.description" class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ product.description }}</p>
              <div class="flex items-center justify-between mt-auto pt-2">
                <span class="text-sm font-black text-amber-600">LKR {{ lkr(product.price) }}</span>
                <div class="w-8 h-8 bg-amber-500 text-white rounded-full flex items-center justify-center font-bold text-xl shadow pointer-events-none">+</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cart bar (sticky bottom) -->
      <div v-if="cartItems.length" class="fixed bottom-0 left-0 right-0 z-20 px-4 pb-4 pt-2 bg-white border-t border-gray-200 shadow-2xl">
        <button
          @click="showCart = true"
          class="w-full flex items-center justify-between px-5 py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-bold shadow-lg shadow-amber-500/30 transition-colors active:scale-95"
        >
          <span class="bg-white/25 text-white text-sm font-black px-2.5 py-0.5 rounded-full">{{ totalQty }}</span>
          <span class="text-base font-black">View Order</span>
          <span class="font-black">LKR {{ lkr(cartTotal) }}</span>
        </button>
      </div>
    </template>

    <!-- Cart slide-up -->
    <Teleport to="body">
      <div v-if="showCart" class="fixed inset-0 z-50 flex flex-col justify-end bg-black/50" @click.self="showCart = false">
        <div class="bg-white rounded-t-3xl max-h-[85vh] flex flex-col">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-black text-gray-900 text-lg">Your Order</h3>
            <div class="flex items-center gap-2">
              <button @click="clearCart" class="text-xs text-red-400 hover:text-red-600 font-semibold px-2 py-1 rounded-lg hover:bg-red-50 transition-colors">Clear all</button>
              <button @click="showCart = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 rounded-full hover:bg-gray-100 text-xl">✕</button>
            </div>
          </div>

          <div class="overflow-y-auto flex-1 px-5 py-3 space-y-3">
            <div v-for="item in cartItems" :key="item.product.id" class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 shrink-0">
                <img v-if="item.product.image" :src="item.product.image" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-xl">🍽️</div>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-800 truncate">{{ item.product.name }}</p>
                <p class="text-xs text-amber-600 font-semibold">LKR {{ lkr(item.product.price) }}</p>
                <input
                  v-model="item.notes"
                  type="text"
                  placeholder="Add note (optional)"
                  maxlength="200"
                  class="mt-1 w-full text-xs px-2 py-1 rounded-lg border border-gray-200 focus:outline-none focus:border-amber-400 text-gray-600"
                />
              </div>
              <div class="flex items-center gap-1.5 shrink-0">
                <button @click="decrement(item)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-red-50 text-gray-700 hover:text-red-600 flex items-center justify-center font-bold text-lg">−</button>
                <span class="w-6 text-center font-black text-gray-900 text-sm">{{ item.qty }}</span>
                <button @click="item.qty++; saveCart()" class="w-8 h-8 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-lg">+</button>
                <button @click="removeItem(item)" class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-400 hover:text-red-600 flex items-center justify-center text-base leading-none">🗑</button>
              </div>
            </div>
          </div>

          <!-- Cart total + submit -->
          <div class="px-5 py-4 border-t border-gray-100 space-y-3">
            <div class="flex justify-between text-base font-black text-gray-900">
              <span>Total</span><span class="text-amber-600">LKR {{ lkr(cartTotal) }}</span>
            </div>
            <p class="text-xs text-gray-400 text-center">Staff will come to your table to collect payment.</p>
            <button
              @click="placeOrder"
              :disabled="submitting || !cartItems.length"
              class="w-full py-4 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-2xl font-black text-base transition-colors shadow-lg shadow-green-500/20 active:scale-95"
            >
              {{ submitting ? 'Placing Order…' : 'Place Order' }}
            </button>
            <p v-if="submitError" class="text-sm text-red-500 text-center">{{ submitError }}</p>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route  = useRoute()
const router = useRouter()

const tableNumber    = route.params.table
const branchId       = route.query.b

const loading        = ref(true)
const tableError     = ref('')
const branch         = ref({ name: '', logo: null, address: '' })
const categories     = ref([])
const products       = ref([])
const activeCategory = ref('')
const search         = ref('')
const showCart       = ref(false)
const submitting     = ref(false)
const submitError    = ref('')

// Phone gate
const phoneInput = ref('')
const phoneError = ref('')
const phone      = ref(localStorage.getItem(`menu_phone_${branchId}`) || '')

// Cart
const cartItems = ref(JSON.parse(localStorage.getItem(`menu_cart_${branchId}_${tableNumber}`) || '[]'))

function saveCart() {
  localStorage.setItem(`menu_cart_${branchId}_${tableNumber}`, JSON.stringify(cartItems.value))
}

const filteredProducts = computed(() => {
  let list = products.value
  if (activeCategory.value) list = list.filter(p => p.category_id === activeCategory.value)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(p => p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q))
  }
  return list
})

const totalQty   = computed(() => cartItems.value.reduce((s, i) => s + i.qty, 0))
const cartTotal  = computed(() => cartItems.value.reduce((s, i) => s + i.product.price * i.qty, 0))

function cartQty(productId) {
  return cartItems.value.find(i => i.product.id === productId)?.qty ?? 0
}

function addToCart(product) {
  const existing = cartItems.value.find(i => i.product.id === product.id)
  if (existing) { existing.qty++ } else { cartItems.value.push({ product, qty: 1, notes: '' }) }
  saveCart()
}

function decrement(item) {
  if (item.qty > 1) { item.qty-- } else { cartItems.value = cartItems.value.filter(i => i !== item) }
  saveCart()
}

function removeItem(item) {
  cartItems.value = cartItems.value.filter(i => i !== item)
  saveCart()
}

function clearCart() {
  cartItems.value = []
  saveCart()
  showCart.value = false
}

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function confirmPhone() {
  const cleaned = phoneInput.value.trim()
  if (!cleaned || cleaned.length < 7) { phoneError.value = 'Enter a valid phone number'; return }
  phone.value = cleaned
  localStorage.setItem(`menu_phone_${branchId}`, cleaned)
}

async function placeOrder() {
  submitting.value = true
  submitError.value = ''
  try {
    await axios.post('/api/public/orders', {
      branch_id:    branchId,
      table_number: tableNumber,
      phone:        phone.value,
      items: cartItems.value.map(i => ({
        product_id: i.product.id,
        quantity:   i.qty,
        item_notes: i.notes || null,
      })),
    })
    // Clear cart and go to confirm page
    localStorage.removeItem(`menu_cart_${branchId}_${tableNumber}`)
    router.push({ name: 'menu.confirm', params: { table: tableNumber }, query: { b: branchId } })
  } catch (e) {
    submitError.value = e.response?.data?.message ?? 'Failed to place order. Please try again.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const [tableRes, menuRes] = await Promise.all([
      axios.get(`/api/public/table/${tableNumber}`, { params: { b: branchId } }),
      axios.get('/api/public/menu', { params: { b: branchId } }),
    ])
    branch.value = {
      name:    tableRes.data.branch_name,
      logo:    tableRes.data.branch_logo,
      address: tableRes.data.branch_address,
      id:      tableRes.data.branch_id,
    }
    categories.value = menuRes.data.categories
    products.value   = menuRes.data.products
  } catch (e) {
    tableError.value = e.response?.status === 404
      ? 'This table does not exist. Please scan the correct QR code.'
      : 'Unable to load menu. Please try again.'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
