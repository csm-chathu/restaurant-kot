<template>
  <div class="flex flex-col bg-gray-100 overflow-hidden" style="height: calc(100vh - 60px)">

    <!-- Top bar -->
    <div class="flex items-center gap-2 px-4 py-2 bg-white border-b border-gray-200 shrink-0 flex-wrap">
      <router-link to="/sales" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 shrink-0">
        <ArrowLeftIcon class="w-4 h-4" /> Bills
      </router-link>
      <span class="text-gray-300 shrink-0">/</span>
      <h2 class="text-sm font-semibold text-gray-800 shrink-0">New Bill</h2>

      <!-- Draft tabs -->
      <div v-if="draftBills.length" class="flex items-center gap-1.5 ml-2 flex-wrap">
        <span class="text-xs text-gray-400 font-medium shrink-0">Drafts:</span>
        <button
          v-for="draft in draftBills"
          :key="draft.id"
          @click="loadDraft(draft)"
          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
          :class="activeDraftId === draft.id
            ? 'bg-amber-500 text-white border-amber-500'
            : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-50'"
        >
          <span class="font-mono">{{ draft.invoice_number }}</span>
          <span v-if="draft.table_number" class="opacity-60">· {{ draft.table_number }}</span>
        </button>
        <button
          v-if="activeDraftId"
          @click="resetForm"
          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 hover:bg-gray-200 border border-gray-200"
        >
          <PlusIcon class="w-3 h-3" /> New
        </button>
      </div>
    </div>

    <!-- Main 2-panel layout -->
    <div class="flex flex-1 overflow-hidden">

      <!-- ── LEFT: Product browser ── -->
      <div class="flex flex-col w-[56%] xl:w-[60%] overflow-hidden bg-white border-r border-gray-200">

        <!-- Search + barcode -->
        <div class="flex gap-2 px-3 py-2 border-b border-gray-100 shrink-0">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input
              v-model="productGridSearch"
              type="text"
              placeholder="Search products…"
              class="form-input pl-8 text-sm py-2"
            />
          </div>
          <div class="relative">
            <QrCodeIcon class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" />
            <input
              v-model="barcodeInput"
              type="text"
              placeholder="Barcode"
              class="form-input pl-8 text-sm py-2 w-32"
              @keyup.enter="scanBarcode"
            />
          </div>
          <button type="button" @click="openScanner" title="Camera scanner" class="inline-flex items-center justify-center w-10 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 shrink-0">
            <QrCodeIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Category tabs -->
        <div class="flex gap-1.5 px-3 py-2 overflow-x-auto border-b border-gray-100 shrink-0">
          <button
            v-for="cat in categoryTabs"
            :key="cat"
            @click="activeCategory = cat"
            class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold transition-colors"
            :class="activeCategory === cat
              ? 'bg-amber-500 text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ cat }}
          </button>
        </div>

        <!-- Barcode error -->
        <p v-if="barcodeError" class="mx-3 mt-2 text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-1.5 flex items-center gap-1.5 shrink-0">
          <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ barcodeError }}
        </p>

        <!-- Product grid -->
        <div class="flex-1 overflow-y-auto p-3">
          <div v-if="!gridProducts.length" class="flex flex-col items-center justify-center py-16 text-gray-400">
            <ShoppingBagIcon class="w-12 h-12 opacity-20 mb-2" />
            <p class="text-sm">No products found</p>
          </div>
          <div class="grid grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2">
            <button
              v-for="product in gridProducts"
              :key="product.id"
              @click="addProductFromGrid(product)"
              :disabled="product.stock_quantity < 1"
              type="button"
              class="relative flex flex-col items-start p-2.5 rounded-xl border-2 text-left transition-all select-none"
              :class="product.stock_quantity < 1
                ? 'border-gray-100 bg-gray-50 opacity-40 cursor-not-allowed'
                : isInBill(product.id)
                  ? 'border-amber-400 bg-amber-50 hover:bg-amber-100 shadow-sm'
                  : 'border-gray-200 bg-white hover:border-amber-300 hover:bg-amber-50 hover:shadow-sm active:scale-95'"
            >
              <!-- In-bill qty badge -->
              <span
                v-if="isInBill(product.id)"
                class="absolute top-1.5 right-1.5 w-5 h-5 bg-amber-500 text-white rounded-full text-xs font-bold flex items-center justify-center shadow"
              >{{ getBillQty(product.id) }}</span>

              <!-- Image -->
              <div class="w-full aspect-square rounded-lg overflow-hidden bg-gray-100 mb-2 shrink-0">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <ShoppingBagIcon class="w-6 h-6 text-gray-300" />
                </div>
              </div>

              <p class="text-xs font-semibold text-gray-800 leading-tight line-clamp-2 mb-1 w-full">{{ product.name }}</p>
              <p class="text-xs font-bold text-amber-600">LKR {{ lkr(product.selling_price) }}</p>
              <p class="text-xs text-gray-400 mt-0.5">
                <span :class="product.stock_quantity <= product.min_stock_level ? 'text-red-400' : ''">
                  {{ product.stock_quantity }} left
                </span>
              </p>
            </button>
          </div>
        </div>
      </div>

      <!-- ── RIGHT: Bill + Payment ── -->
      <div class="flex flex-col w-[44%] xl:w-[40%] overflow-hidden">

        <!-- Table + Customer -->
        <div class="px-3 py-2.5 bg-white border-b border-gray-200 shrink-0 space-y-2">
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Table</label>
              <select v-model="form.table_number" class="form-input text-sm py-1.5">
                <option value="">Walk-in</option>
                <option v-for="t in availableTables" :key="t.id" :value="t.table_number">
                  {{ t.table_number }}
                </option>
              </select>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 mb-1 block">Customer</label>
              <div class="flex gap-1">
                <select v-model="form.customer_id" class="form-input text-sm py-1.5 flex-1 min-w-0">
                  <option value="">Walk-in</option>
                  <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <button
                  @click="showNewCustomer = !showNewCustomer"
                  type="button"
                  class="w-8 shrink-0 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50"
                  title="Add new customer"
                >
                  <PlusIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Quick new customer -->
          <div v-if="showNewCustomer" class="bg-blue-50 border border-blue-200 rounded-lg p-2.5 space-y-1.5">
            <div class="grid grid-cols-2 gap-1.5">
              <input v-model="newCustomer.name" type="text" placeholder="Name *" class="form-input text-xs py-1.5" @keyup.enter="saveNewCustomer" />
              <input v-model="newCustomer.phone" type="tel" placeholder="Phone" class="form-input text-xs py-1.5" @keyup.enter="saveNewCustomer" />
            </div>
            <p v-if="newCustomerError" class="text-xs text-red-600">{{ newCustomerError }}</p>
            <button @click="saveNewCustomer" :disabled="savingCustomer || !newCustomer.name.trim()" class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-xs font-semibold">
              {{ savingCustomer ? 'Saving…' : 'Save & Select' }}
            </button>
          </div>

          <input v-model="form.notes" type="text" placeholder="Order notes…" class="form-input text-sm py-1.5" />
        </div>

        <!-- Bill items (scrollable) -->
        <div class="flex-1 overflow-y-auto bg-white">
          <div v-if="!form.items.length" class="flex flex-col items-center justify-center py-12 text-gray-400">
            <ShoppingCartIcon class="w-10 h-10 opacity-20 mb-2" />
            <p class="text-sm">Tap products to add</p>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="(item, i) in form.items"
              :key="i"
              class="px-3 py-2.5 hover:bg-gray-50 transition-colors"
            >
              <!-- If no product selected yet (manual line) -->
              <div v-if="!item.product_id" class="space-y-1.5">
                <select v-model="item.product_id" class="form-input text-sm" @change="fillProduct(item)">
                  <option value="">— Select product —</option>
                  <option v-for="p in products" :key="p.id" :value="p.id" :disabled="p.stock_quantity < 1">
                    {{ p.name }} ({{ p.stock_quantity }} in stock)
                  </option>
                </select>
                <button @click="removeItem(i)" class="text-xs text-red-400 hover:text-red-600">Remove</button>
              </div>

              <!-- Product line -->
              <template v-else>
                <div class="flex items-start gap-2">
                  <!-- Thumbnail -->
                  <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-200">
                    <img v-if="item.product_ref?.image" :src="item.product_ref.image" :alt="item.product_ref?.name" class="w-full h-full object-cover" />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <ShoppingBagIcon class="w-5 h-5 text-gray-300" />
                    </div>
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 leading-tight truncate">
                      {{ item.product_ref?.name || 'Product' }}
                    </p>
                    <p class="text-xs text-amber-600">
                      LKR {{ lkr(getEffectiveUnitPrice(item)) }}
                      <span v-if="item.serving_ml > 0" class="text-gray-400"> · {{ item.serving_ml }}ml</span>
                      <span v-if="!item.empty_bottle_returned && item.product_ref?.bottle_deposit_required" class="text-gray-400"> · +dep</span>
                    </p>
                  </div>

                  <!-- Qty control -->
                  <div class="flex items-center gap-1 shrink-0">
                    <button
                      @click="decrementItem(item, i)"
                      class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 hover:text-red-600 flex items-center justify-center text-gray-600 font-bold text-lg leading-none transition-colors"
                    >−</button>
                    <span class="w-6 text-center text-sm font-bold text-gray-900">{{ item.quantity }}</span>
                    <button
                      @click="item.quantity++; recalcItem(item)"
                      class="w-7 h-7 rounded-full bg-amber-100 hover:bg-amber-200 flex items-center justify-center text-amber-700 font-bold text-lg leading-none transition-colors"
                    >+</button>
                  </div>

                  <!-- Line total -->
                  <div class="shrink-0 w-20 text-right">
                    <p class="text-sm font-bold text-gray-900">LKR {{ lkr(item._lineTotal) }}</p>
                  </div>

                  <button @click="removeItem(i)" class="shrink-0 w-5 h-5 flex items-center justify-center text-gray-300 hover:text-red-500 transition-colors">
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>

                <!-- Extras row -->
                <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1.5 items-center">
                  <!-- Item discount -->
                  <div class="flex items-center gap-1">
                    <span class="text-xs text-gray-400">Disc:</span>
                    <input v-model.number="item.discount" type="number" min="0" class="w-16 form-input text-xs py-0.5 text-center" @input="recalcItem(item)" placeholder="0" />
                  </div>

                  <!-- Serving ml for liquor -->
                  <div v-if="item.product_ref?.product_type === 'Liquor'" class="flex items-center gap-1">
                    <span class="text-xs text-gray-400">ml:</span>
                    <button v-for="size in [30, 50, 60, 75]" :key="size"
                      @click="item.serving_ml = size; recalcItem(item)" type="button"
                      class="px-1.5 py-0.5 text-xs font-semibold rounded transition-colors"
                      :class="item.serving_ml === size ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-100'"
                    >{{ size }}</button>
                    <input v-model.number="item.serving_ml" type="number" min="0" class="w-14 form-input text-xs py-0.5 text-center" @input="recalcItem(item)" placeholder="0" />
                  </div>

                  <!-- Bottle deposit -->
                  <div v-if="item.product_ref?.bottle_deposit_required" class="flex items-center gap-1.5">
                    <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                      <input type="checkbox" v-model="item.empty_bottle_returned" @change="recalcItem(item)" class="rounded text-amber-600" />
                      Bottle returned
                    </label>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Add manual line -->
          <div class="px-3 py-2">
            <button @click="addItem" class="w-full flex items-center justify-center gap-1.5 py-2 rounded-lg border-2 border-dashed border-gray-200 text-gray-400 hover:border-amber-300 hover:text-amber-500 text-xs font-medium transition-colors">
              <PlusIcon class="w-3.5 h-3.5" /> Add line manually
            </button>
          </div>
        </div>

        <!-- ── Totals + Payment (pinned bottom) ── -->
        <div class="shrink-0 border-t border-gray-200 bg-white">

          <!-- Total row (always visible) + collapsible detail -->
          <div class="border-b border-gray-100">
            <button
              @click="showPricingDetails = !showPricingDetails"
              class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <span>TOTAL</span>
                <span v-if="form.discount > 0" class="text-red-400">−{{ lkr(form.discount) }}</span>
                <span v-if="form.tax > 0" class="text-blue-400">+tax {{ form.tax_rate }}%</span>
                <span class="text-gray-300">{{ showPricingDetails ? '▲' : '▼' }}</span>
              </div>
              <span class="text-xl font-bold text-amber-600">LKR {{ lkr(total) }}</span>
            </button>

            <!-- Collapsible: subtotal / discount / tax -->
            <div v-if="showPricingDetails" class="px-3 pb-2 space-y-1.5 border-t border-gray-100 bg-gray-50">
              <div class="flex justify-between text-xs text-gray-500 pt-1.5">
                <span>Subtotal</span><span>LKR {{ lkr(subtotal) }}</span>
              </div>
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs text-gray-500">Discount</span>
                  <input v-model.number="form.discount" type="number" min="0" class="w-20 form-input text-xs py-0.5 text-center" @input="recalc" placeholder="0" />
                </div>
                <span v-if="form.discount > 0" class="text-xs text-red-500 font-medium">−LKR {{ lkr(form.discount) }}</span>
              </div>
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs text-gray-500">Tax</span>
                  <select v-model="selectedTaxId" class="form-input text-xs py-0.5 w-28" @change="applyTax">
                    <option value="">None</option>
                    <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.name }} ({{ t.rate }}%)</option>
                  </select>
                </div>
                <span v-if="form.tax > 0" class="text-xs text-blue-500 font-medium">+LKR {{ lkr(form.tax) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment method + status (single row) -->
          <div class="px-3 pt-1.5 pb-1 flex gap-1 flex-wrap items-center">
            <button
              v-for="opt in paymentOptions"
              :key="opt.value"
              @click="form.payment_method = opt.value"
              class="px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
              :class="form.payment_method === opt.value
                ? 'bg-gray-800 text-white border-gray-800'
                : 'bg-gray-100 text-gray-500 border-gray-200 hover:border-gray-400'"
            >{{ opt.label }}</button>

            <button
              @click="form.payment_status = form.payment_status === 'pending' ? 'paid' : form.payment_status === 'paid' ? 'partial' : 'pending'"
              class="ml-auto px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-colors"
              :class="form.payment_status === 'pending'
                ? 'bg-yellow-100 text-yellow-700 border-yellow-300'
                : form.payment_status === 'partial'
                  ? 'bg-orange-100 text-orange-700 border-orange-300'
                  : 'bg-green-100 text-green-700 border-green-300'"
            >{{ form.payment_status === 'pending' ? 'Pending' : form.payment_status === 'partial' ? 'Partial' : 'Paid' }}</button>
          </div>

          <!-- Amount + quick cash -->
          <div class="px-3 pb-1.5">
            <div class="flex gap-1.5">
              <input
                v-model.number="form.amount_paid"
                type="number"
                min="0"
                class="form-input text-base font-bold py-1.5 text-center text-gray-900 flex-1"
                placeholder="Amount received"
                @input="recalc"
              />
              <button
                @click="form.amount_paid = total"
                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-300 transition-colors shrink-0"
              >Exact</button>
            </div>
            <div class="flex gap-1 mt-1">
              <button
                v-for="denom in quickDenominations"
                :key="denom"
                @click="form.amount_paid = denom"
                class="flex-1 py-1 rounded-md text-xs font-bold bg-gray-100 text-gray-600 hover:bg-amber-100 hover:text-amber-700 border border-gray-200 transition-colors"
              >{{ denom >= 1000 ? (denom / 1000) + 'K' : denom }}</button>
            </div>

            <!-- Change / balance (compact) -->
            <div v-if="changeDue > 0" class="mt-1 flex items-center justify-between bg-green-50 border border-green-200 rounded-lg px-2.5 py-1">
              <span class="text-xs font-semibold text-green-700">Change</span>
              <span class="text-base font-bold text-green-600">LKR {{ lkr(changeDue) }}</span>
            </div>
            <div v-else-if="balanceDue > 0.009" class="mt-1 flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded-lg px-2.5 py-1">
              <span class="text-xs font-semibold text-yellow-700">Balance due</span>
              <span class="text-base font-bold text-yellow-600">LKR {{ lkr(balanceDue) }}</span>
            </div>
          </div>

          <!-- Error -->
          <p v-if="error" class="mx-3 mb-1 text-xs text-red-600 bg-red-50 border border-red-200 px-2.5 py-1.5 rounded-lg flex items-center gap-1.5">
            <ExclamationTriangleIcon class="w-3.5 h-3.5 shrink-0" /> {{ error }}
          </p>

          <!-- Action buttons -->
          <div class="flex gap-2 px-3 pb-2.5 pt-1">
            <button
              @click="submit('draft')"
              :disabled="saving || !form.items.filter(i => i.product_id).length"
              class="flex-1 flex items-center justify-center gap-1.5 py-2 bg-gray-500 hover:bg-gray-600 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-semibold text-sm transition-colors"
            >
              <ArrowPathIcon v-if="saving" class="w-4 h-4 animate-spin" />
              <CheckCircleIcon v-else class="w-4 h-4" />
              Draft
            </button>
            <button
              @click="submit('completed')"
              :disabled="saving || !form.items.filter(i => i.product_id).length"
              class="flex-[2] flex items-center justify-center gap-2 py-2 bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed text-white rounded-xl font-bold text-sm shadow-md transition-colors"
            >
              <ArrowPathIcon v-if="saving" class="w-5 h-5 animate-spin" />
              <CheckCircleIcon v-else class="w-5 h-5" />
              {{ saving ? 'Processing…' : 'Complete Sale' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Camera scanner overlay -->
    <div v-if="scannerOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
      <div class="w-full max-w-lg bg-gray-900 text-white rounded-2xl overflow-hidden shadow-2xl border border-gray-700">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
          <div>
            <h3 class="text-lg font-semibold">Camera Scanner</h3>
            <p class="text-xs text-gray-400 mt-0.5">Point camera at a product barcode</p>
          </div>
          <button type="button" @click="closeScanner" class="text-gray-400 hover:text-white text-2xl leading-none">✕</button>
        </div>
        <div class="p-5 space-y-4">
          <div class="rounded-2xl overflow-hidden bg-black border border-gray-700 relative">
            <video ref="scannerVideo" autoplay playsinline muted class="w-full h-[280px] object-cover"></video>
            <div class="absolute inset-x-0 bottom-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
              <p class="text-xs text-gray-300">Detected: <span class="font-semibold text-amber-300">{{ scannerDetected || 'waiting…' }}</span></p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-400">{{ scannerStatus }}</span>
            <button @click="closeScanner" class="px-4 py-2 rounded-lg border border-gray-700 text-gray-200 hover:bg-gray-800 text-sm">Close</button>
          </div>
          <div v-if="scannerError" class="text-sm text-red-300 bg-red-950/60 border border-red-800 rounded-lg px-3 py-2">{{ scannerError }}</div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import {
  ArrowLeftIcon, PlusIcon, XMarkIcon,
  ShoppingCartIcon, CheckCircleIcon, ArrowPathIcon,
  ExclamationTriangleIcon, QrCodeIcon, MagnifyingGlassIcon, ShoppingBagIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route  = useRoute()

// ── Data ──────────────────────────────────────────────
const products        = ref([])
const customers       = ref([])
const availableTables = ref([])
const taxes           = ref([])
const draftBills      = ref([])
const loadingDraft    = ref(false)
const activeDraftId   = ref(null)

// Product browser state
const activeCategory    = ref('All')
const productGridSearch = ref('')

// Form
const form = reactive({
  customer_id: '', payment_method: 'cash', payment_status: 'paid',
  discount: 0, tax: 0, tax_rate: 0, amount_paid: 0, notes: '',
  table_number: '', status: 'completed',
  items: [],
})
const selectedTaxId = ref('')

// UI state
const saving              = ref(false)
const error               = ref('')
const showNewCustomer     = ref(false)
const savingCustomer      = ref(false)
const newCustomerError    = ref('')
const newCustomer         = reactive({ name: '', phone: '', email: '' })
const showPricingDetails  = ref(false)

// Barcode
const barcodeInput = ref('')
const barcodeError = ref('')
let barcodeClearTimer = null

// Scanner
const scannerOpen     = ref(false)
const scannerVideo    = ref(null)
const scannerDetected = ref('')
const scannerError    = ref('')
const scannerStatus   = ref('Idle')
const scannerSupported = computed(() => typeof window !== 'undefined' && 'BarcodeDetector' in window)
let scannerStream   = null
let scannerDetector = null
let scannerFrame    = null
let scannerBusy     = false

const paymentOptions = [
  { value: 'cash',          label: 'Cash' },
  { value: 'card',          label: 'Card' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'cheque',        label: 'Cheque' },
  { value: 'other',         label: 'Other' },
]

// ── Computed ──────────────────────────────────────────
const categoryTabs = computed(() => {
  const cats = new Set()
  for (const p of products.value) {
    if (p.category?.name) cats.add(p.category.name)
  }
  return ['All', ...cats]
})

const gridProducts = computed(() => {
  let list = products.value
  if (activeCategory.value !== 'All') {
    list = list.filter(p => p.category?.name === activeCategory.value)
  }
  const q = productGridSearch.value.trim().toLowerCase()
  if (q) {
    list = list.filter(p =>
      `${p.name || ''} ${p.sku || ''} ${p.barcode || ''} ${p.brand || ''}`.toLowerCase().includes(q)
    )
  }
  return list
})

const selectedCustomer = computed(() => customers.value.find(c => c.id == form.customer_id) ?? null)

const subtotal   = computed(() => form.items.reduce((s, i) => s + (i._lineTotal || 0), 0))
const total      = computed(() => Math.max(0, subtotal.value - (form.discount || 0) + (form.tax || 0)))
const balanceDue = computed(() => Math.max(0, total.value - Number(form.amount_paid || 0)))
const changeDue  = computed(() => Math.max(0, Number(form.amount_paid || 0) - total.value))

const quickDenominations = computed(() => {
  const t = total.value
  if (t <= 0) return [100, 500, 1000, 2000]
  const roundedUp = Math.ceil(t / 100) * 100
  const standard = [100, 500, 1000, 2000, 5000, 10000]
  const above = standard.filter(d => d > roundedUp).slice(0, 3)
  return [...new Set([roundedUp, ...above])].sort((a, b) => a - b).slice(0, 4)
})

// ── Bill helpers ───────────────────────────────────────
function isInBill(productId) {
  return form.items.some(i => i.product_id == productId)
}

function getBillQty(productId) {
  return form.items.find(i => i.product_id == productId)?.quantity ?? 0
}

function addProductFromGrid(product) {
  if (product.stock_quantity < 1) return
  const existing = form.items.find(i => i.product_id == product.id)
  if (existing) {
    existing.quantity++
    recalcItem(existing)
    return
  }
  const item = newItem()
  item.product_id     = product.id
  item.product_ref    = product
  item.product_search = product.name || ''
  item.unit_price     = product.selling_price
  form.items.push(item)
  recalcItem(item)
}

function decrementItem(item, index) {
  if (item.quantity > 1) {
    item.quantity--
    recalcItem(item)
  } else {
    removeItem(index)
  }
}

function newItem() {
  return {
    product_id: '', product_search: '', quantity: 1, unit_price: 0, discount: 0,
    empty_bottle_returned: false, bottle_deposit_amount: 100, serving_ml: 0,
    product_ref: null, _lineTotal: 0,
  }
}

function addItem()      { form.items.push(newItem()) }
function removeItem(i)  { form.items.splice(i, 1); recalc() }

function recalcItem(item) {
  const effectiveUnitPrice = getEffectiveUnitPrice(item)
  const depositAdd = item.product_ref?.bottle_deposit_required && !item.empty_bottle_returned
    ? Number(item.bottle_deposit_amount || 0) * Number(item.quantity || 0)
    : 0
  item._lineTotal = (effectiveUnitPrice * item.quantity) - (item.discount || 0) + depositAdd
  recalc()
}

function getEffectiveUnitPrice(item) {
  const baseUnitPrice = Number(item.unit_price || 0)
  const servingMl = Number(item.serving_ml || 0)
  const isLiquor = String(item.product_ref?.product_type || '').toLowerCase() === 'liquor'
  if (!isLiquor || servingMl <= 0) return baseUnitPrice
  const baseMl = parseBaseUnitMl(item.product_ref?.base_unit)
  if (baseMl <= 0) return baseUnitPrice
  return Math.round(((baseUnitPrice / baseMl) * servingMl) * 100) / 100
}

function parseBaseUnitMl(baseUnit) {
  const text = String(baseUnit || '').toLowerCase()
  const mlMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*ml/)
  if (mlMatch) return Number(mlMatch[1]) || 0
  const lMatch = text.match(/([0-9]+(?:\.[0-9]+)?)\s*l\b/)
  if (lMatch) return (Number(lMatch[1]) || 0) * 1000
  return 0
}

function recalc() {
  if (form.tax_rate > 0) {
    form.tax = Math.round(subtotal.value * (form.tax_rate / 100) * 100) / 100
  }
  form.amount_paid = total.value
}

function applyTax() {
  const t = taxes.value.find(x => x.id == selectedTaxId.value)
  if (t) { form.tax_rate = t.rate; recalc() }
  else   { form.tax_rate = 0; form.tax = 0 }
}

function fillProduct(item) {
  const p = products.value.find(x => x.id == item.product_id)
  if (!p) { item.product_ref = null; return }
  item.product_ref    = p
  item.product_search = p.name || ''
  item.unit_price     = p.selling_price
  item.serving_ml     = 0
  item.empty_bottle_returned = false
  item.bottle_deposit_amount = 100
  recalcItem(item)
}

function resetForm() {
  activeDraftId.value    = null
  form.customer_id       = ''
  form.payment_method    = 'cash'
  form.payment_status    = 'paid'
  form.discount          = 0
  form.tax               = 0
  form.tax_rate          = 0
  form.amount_paid       = 0
  form.notes             = ''
  form.table_number      = ''
  form.status            = 'completed'
  form.items             = []
  selectedTaxId.value       = ''
  error.value               = ''
  showPricingDetails.value  = false
}

// ── Draft loading ──────────────────────────────────────
async function loadDraft(draft) {
  loadingDraft.value = true
  try {
    const { data } = await axios.get(`/api/sales/${draft.id}`)
    activeDraftId.value   = data.id
    form.customer_id      = data.customer_id || ''
    form.payment_method   = data.payment_method || 'cash'
    form.payment_status   = 'draft'
    form.discount         = data.discount || 0
    form.tax              = data.tax || 0
    form.tax_rate         = data.tax_rate || 0
    form.amount_paid      = 0
    form.notes            = data.notes || ''
    form.table_number     = data.table_number || ''
    form.items = (data.items || []).map(si => ({
      product_id:            si.product_id,
      product_search:        si.product?.name || '',
      quantity:              Number(si.quantity || 0),
      unit_price:            Number(si.product?.selling_price ?? si.unit_price ?? 0),
      discount:              Number(si.discount || 0),
      serving_ml:            Number(si.serving_ml || 0),
      empty_bottle_returned: false,
      bottle_deposit_amount: 100,
      product_ref:           si.product,
      _lineTotal:            0,
    }))
    form.items.forEach(recalcItem)
  } finally {
    loadingDraft.value = false
  }
}

// ── Customer creation ──────────────────────────────────
async function saveNewCustomer() {
  if (!newCustomer.name.trim()) return
  savingCustomer.value = true; newCustomerError.value = ''
  try {
    const { data } = await axios.post('/api/customers', {
      name:  newCustomer.name.trim(),
      phone: newCustomer.phone.trim() || null,
      email: newCustomer.email.trim() || null,
    })
    customers.value.unshift(data)
    form.customer_id = data.id
    showNewCustomer.value = false
    newCustomer.name = ''; newCustomer.phone = ''; newCustomer.email = ''
  } catch (e) {
    newCustomerError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'Could not save customer'
  } finally { savingCustomer.value = false }
}

// ── Barcode scanner ────────────────────────────────────
function processBarcode(code) {
  const normalized = (code ?? '').trim()
  barcodeInput.value = ''
  if (!normalized) return

  const product = products.value.find(p =>
    p.barcode?.toLowerCase() === normalized.toLowerCase() ||
    p.sku?.toLowerCase() === normalized.toLowerCase()
  )
  if (!product) {
    barcodeError.value = `"${normalized}" not found`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }
  if (product.stock_quantity < 1) {
    barcodeError.value = `"${product.name}" is out of stock`
    clearTimeout(barcodeClearTimer)
    barcodeClearTimer = setTimeout(() => { barcodeError.value = '' }, 3000)
    return
  }
  addProductFromGrid(product)
  barcodeError.value = ''
}

function scanBarcode() { processBarcode(barcodeInput.value) }

async function openScanner() {
  scannerError.value = ''; scannerDetected.value = ''; scannerOpen.value = true
  await nextTick()
  if (!scannerSupported.value) { scannerStatus.value = 'Camera detection not supported in this browser.'; return }
  try {
    scannerStatus.value = 'Starting camera…'
    scannerDetector = new window.BarcodeDetector({ formats: ['code_128', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code'] })
    scannerStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false })
    if (scannerVideo.value) { scannerVideo.value.srcObject = scannerStream; await scannerVideo.value.play() }
    scannerStatus.value = 'Scanning…'
    scannerLoop()
  } catch (e) {
    scannerError.value = e?.message ?? 'Could not start camera.'
    scannerStatus.value = 'Scanner unavailable.'
  }
}

async function scannerLoop() {
  if (!scannerOpen.value || !scannerDetector || !scannerVideo.value || scannerBusy) return
  scannerFrame = requestAnimationFrame(scannerLoop)
  try {
    scannerBusy = true
    const codes = await scannerDetector.detect(scannerVideo.value)
    if (codes?.length) {
      const value = codes[0].rawValue || ''
      if (value) { scannerDetected.value = value; processBarcode(value); closeScanner() }
    }
  } catch (e) {
    scannerError.value = e?.message ?? 'Detection failed.'
  } finally { scannerBusy = false }
}

function closeScanner() {
  scannerOpen.value = false; scannerStatus.value = 'Idle'
  if (scannerFrame) cancelAnimationFrame(scannerFrame)
  scannerFrame = null; scannerBusy = false
  scannerDetected.value = ''; scannerError.value = ''
  if (scannerStream) { scannerStream.getTracks().forEach(t => t.stop()); scannerStream = null }
}

// ── Submit ─────────────────────────────────────────────
async function submit(billStatus) {
  saving.value = true; error.value = ''
  try {
    const payload = {
      customer_id:    form.customer_id || null,
      payment_method: form.payment_method,
      payment_status: billStatus === 'draft' ? 'pending' : form.payment_status,
      discount:       form.discount,
      tax:            form.tax,
      tax_rate:       form.tax_rate,
      amount_paid:    billStatus === 'draft' ? 0 : Number(form.amount_paid || 0),
      status:         billStatus,
      table_number:   form.table_number || null,
      notes:          form.notes,
      items: form.items.filter(i => i.product_id).map(i => ({
        product_id:            i.product_id,
        quantity:              i.quantity,
        unit_price:            getEffectiveUnitPrice(i),
        discount:              i.discount,
        empty_bottle_returned: i.empty_bottle_returned,
        bottle_deposit_amount: i.bottle_deposit_amount,
        serving_ml:            i.serving_ml,
      })),
    }

    let saleId
    if (activeDraftId.value && billStatus === 'draft') {
      const { data } = await axios.put(`/api/sales/${activeDraftId.value}`, payload)
      saleId = data.id ?? activeDraftId.value
    } else {
      const { data } = await axios.post('/api/sales', payload)
      saleId = data.id
    }

    if (billStatus === 'completed') {
      router.push(`/sales/${saleId}`)
    } else {
      router.push('/sales')
    }
  } catch (e) {
    error.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {}).flat().join(', ')
      ?? 'An error occurred. Please try again.'
  } finally { saving.value = false }
}

// ── Utilities ──────────────────────────────────────────
function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

onMounted(async () => {
  const [p, c, t, tb, drafts] = await Promise.all([
    axios.get('/api/products', { params: { per_page: 200 } }),
    axios.get('/api/customers/all'),
    axios.get('/api/tax-settings'),
    axios.get('/api/tables/all'),
    axios.get('/api/sales', { params: { status: 'draft', per_page: 50 } }),
  ])
  products.value        = p.data.data
  customers.value       = c.data
  taxes.value           = t.data.filter(x => x.is_active)
  availableTables.value = tb.data
  draftBills.value      = drafts.data.data

  // Auto-load a specific draft when coming from the Edit button
  const draftId = route.query.draft
  if (draftId) {
    const draft = draftBills.value.find(d => d.id == draftId)
    if (draft) {
      await loadDraft(draft)
    } else {
      // Draft not in the list (e.g. different page) — fetch it directly
      await loadDraft({ id: draftId })
    }
  }
})

onBeforeUnmount(() => {
  closeScanner()
  clearTimeout(barcodeClearTimer)
})
</script>
