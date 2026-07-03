<template>
  <div>
    <!-- Screen toolbar -->
    <div class="no-print flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <router-link to="/sales/new"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to POS
        </router-link>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium text-gray-700">Kitchen Ticket</span>
      </div>
      <button @click="printTicket" :disabled="loading || !sale"
        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
        <PrinterIcon class="w-4 h-4" />
        Print KOT
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
      <ArrowPathIcon class="w-6 h-6 animate-spin mr-2" /> Loading…
    </div>

    <!-- Not found -->
    <div v-else-if="!sale" class="text-center py-20 text-gray-400">Ticket not found.</div>

    <!-- KOT paper (screen preview + print target) -->
    <div v-else class="flex justify-center">
      <div class="kot-paper">

        <!-- Header -->
        <div class="kot-header">
          <div class="kot-title">KITCHEN ORDER TICKET</div>
          <div class="kot-restaurant">{{ restaurantName }}</div>
        </div>

        <div class="kot-divider">- - - - - - - - - - - - - - -</div>

        <!-- Meta -->
        <div class="kot-meta">
          <div class="kot-meta-row">
            <span>Order :</span>
            <span class="kot-bold">{{ sale.order_type === 'takeaway' ? 'TAKEAWAY 🥡' : 'DINE IN 🍽️' }}</span>
          </div>
          <div v-if="sale.table_number" class="kot-meta-row">
            <span>Table :</span>
            <span class="kot-bold">{{ sale.table_number }}</span>
          </div>
          <div class="kot-meta-row">
            <span>Time  :</span>
            <span>{{ formatTime(sale.sold_at || new Date()) }}</span>
          </div>
          <div class="kot-meta-row">
            <span>Ref   :</span>
            <span>{{ sale.invoice_number }}</span>
          </div>
        </div>

        <div class="kot-divider">================================</div>

        <!-- Items -->
        <div class="kot-items">
          <div v-for="item in sale.items" :key="item.id" class="kot-item">
            <div class="kot-item-main">
              <span class="kot-qty">{{ item.quantity }}x</span>
              <span class="kot-name">{{ item.product?.name ?? 'Item' }}</span>
            </div>
            <div v-if="item.serving_ml > 0" class="kot-notes">
              &nbsp;&nbsp;&nbsp;↳ {{ item.serving_ml }}ml serving
            </div>
            <div v-if="item.item_notes" class="kot-notes">
              &nbsp;&nbsp;&nbsp;↳ {{ item.item_notes }}
            </div>
          </div>
        </div>

        <div class="kot-divider">================================</div>

        <!-- Footer -->
        <div class="kot-footer">
          <div>Items: {{ totalItems }}</div>
          <div>Qty: {{ totalQty }}</div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { ArrowLeftIcon, PrinterIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const route  = useRoute()
const router = useRouter()

const sale           = ref(null)
const loading        = ref(true)
const restaurantName = ref('')

const totalItems = computed(() => sale.value?.items?.length ?? 0)
const totalQty   = computed(() => sale.value?.items?.reduce((s, i) => s + i.quantity, 0) ?? 0)

function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

function printTicket() {
  window.print()
}

onMounted(async () => {
  try {
    const [saleRes, settingsRes] = await Promise.all([
      axios.get(`/api/sales/${route.params.id}`),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])
    sale.value = saleRes.data
    restaurantName.value = settingsRes.data?.name ?? ''

    if (route.query.print === '1') {
      setTimeout(() => printTicket(), 400)
    }
  } catch {
    sale.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style>
.kot-paper {
  width: 287px;
  background: #fff;
  font-family: 'Courier New', Courier, monospace;
  font-size: 13px;
  color: #000;
  padding: 12px 10px;
  border: 1px dashed #ccc;
  border-radius: 4px;
}

.kot-header   { text-align: center; margin-bottom: 6px; }
.kot-title    { font-size: 16px; font-weight: 900; letter-spacing: 1px; }
.kot-restaurant { font-size: 11px; margin-top: 2px; }

.kot-divider  { text-align: center; color: #555; font-size: 11px; margin: 6px 0; }

.kot-meta     { margin: 6px 0; }
.kot-meta-row { display: flex; justify-content: space-between; margin-bottom: 2px; }
.kot-bold     { font-weight: 900; }

.kot-items    { margin: 4px 0; }
.kot-item     { margin-bottom: 6px; }
.kot-item-main{ display: flex; align-items: baseline; gap: 6px; font-weight: 700; font-size: 15px; }
.kot-qty      { min-width: 28px; font-size: 16px; font-weight: 900; }
.kot-name     { flex: 1; }
.kot-notes    { font-size: 11px; color: #333; font-style: italic; margin-top: 1px; font-weight: 400; }

.kot-footer   { display: flex; justify-content: space-between; font-size: 11px; color: #555; margin-top: 4px; }

/* Print */
@media print {
  @page { size: 76mm auto; margin: 0; }

  body > * { display: none !important; }
  #app      { display: none !important; }

  .no-print { display: none !important; }

  .kot-paper {
    display: block !important;
    position: fixed;
    top: 0; left: 0;
    width: 76mm;
    border: none;
    padding: 4mm 3mm;
    font-size: 12px;
  }

  .kot-item-main { font-size: 14px; }
  .kot-qty       { font-size: 15px; }
}
</style>
