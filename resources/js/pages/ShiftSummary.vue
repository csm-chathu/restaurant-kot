<template>
  <div class="space-y-5">

    <!-- Header -->
    <div class="flex items-start justify-between gap-4 flex-wrap no-print">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Shift-wise Report</h2>
        <p class="text-sm text-gray-500 mt-0.5">Detailed breakdown per cashier shift</p>
      </div>
      <button @click="print" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:border-amber-300 hover:text-amber-700 no-print">
        <i class="fas fa-print"></i> Print
      </button>
    </div>

    <!-- Filters -->
    <div class="card py-3 flex gap-3 items-end flex-wrap no-print">
      <div>
        <label class="form-label">From</label>
        <input v-model="dateFrom" type="date" class="form-input" />
      </div>
      <div>
        <label class="form-label">To</label>
        <input v-model="dateTo" type="date" class="form-input" />
      </div>
      <button @click="load" :disabled="loading" class="btn-primary text-sm">
        {{ loading ? 'Loading…' : 'Generate' }}
      </button>
    </div>

    <!-- KPI summary -->
    <div v-if="totals" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Shifts</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ totals.shift_count }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Revenue</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ lkr(totals.total_revenue) }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Handover</p>
        <p class="text-2xl font-bold text-red-600 mt-1">{{ lkr(totals.total_handover) }}</p>
      </div>
      <div class="card py-3 px-4">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Leftover</p>
        <p class="text-2xl font-bold text-green-700 mt-1">{{ lkr(totals.total_leftover) }}</p>
      </div>
    </div>

    <!-- No data -->
    <div v-if="!loading && shifts.length === 0 && totals" class="card p-10 text-center text-gray-400">
      No closed shifts found for the selected period.
    </div>

    <!-- Per-shift cards -->
    <template v-for="(group, date) in groupedByDate" :key="date">
      <!-- Day header -->
      <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-600">{{ formatDate(date) }}</span>
        <div class="flex-1 border-t border-gray-200"></div>
        <span class="text-xs text-gray-400">{{ group.length }} shift{{ group.length > 1 ? 's' : '' }}</span>
      </div>

      <div v-for="shift in group" :key="shift.id" class="card p-0 overflow-hidden">

        <!-- Shift header row -->
        <div class="px-5 py-4 flex flex-wrap items-center justify-between gap-3 cursor-pointer select-none"
             @click="toggleShift(shift.id)">
          <div class="flex items-center gap-4">
            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
              <i class="fas fa-user text-amber-600 text-sm"></i>
            </div>
            <div>
              <p class="font-semibold text-gray-800">{{ shift.cashier }}</p>
              <p class="text-xs text-gray-400">
                {{ formatTime(shift.opened_at) }} — {{ formatTime(shift.closed_at) }}
                <span class="ml-1 text-gray-300">·</span>
                <span class="ml-1">{{ shift.total_sales }} bills · {{ shift.total_items }} items</span>
              </p>
            </div>
          </div>

          <!-- KPI pills -->
          <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
            <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">LKR {{ lkr(shift.total_revenue) }}</span>
            <span class="px-2.5 py-1 rounded-full bg-green-50 text-green-700">Cash {{ lkr(shift.cash_sales) }}</span>
            <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">Card {{ lkr(shift.card_sales) }}</span>
            <span v-if="shift.total_cash_outs > 0" class="px-2.5 py-1 rounded-full bg-orange-50 text-orange-600">Out −{{ lkr(shift.total_cash_outs) }}</span>
            <span class="px-2.5 py-1 rounded-full"
                  :class="shift.variance < -0.01 ? 'bg-red-50 text-red-600' : shift.variance > 0.01 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
              {{ shift.variance >= 0 ? '+' : '' }}{{ lkr(shift.variance) }}
            </span>
            <i class="fas text-gray-400 ml-1" :class="expanded[shift.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
          </div>
        </div>

        <!-- Expandable detail -->
        <div v-if="expanded[shift.id]" class="border-t border-gray-100">
          <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">

            <!-- Cash drawer -->
            <div class="px-5 py-4 space-y-2">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Cash Drawer</p>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Opening Cash</span>
                <span class="font-medium">{{ lkr(shift.opening_cash) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">+ Cash Sales</span>
                <span class="font-medium text-green-700">{{ lkr(shift.cash_sales) }}</span>
              </div>
              <div v-if="shift.total_cash_outs > 0" class="flex justify-between text-sm">
                <span class="text-gray-500">− Cash Outs</span>
                <span class="font-medium text-red-600">{{ lkr(shift.total_cash_outs) }}</span>
              </div>
              <div class="flex justify-between text-sm border-t border-dashed border-gray-200 pt-2 mt-1">
                <span class="text-gray-500">Expected Cash</span>
                <span class="font-medium">{{ lkr(shift.expected_cash) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Closing Cash</span>
                <span class="font-medium">{{ lkr(shift.closing_cash) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold"
                   :class="shift.variance < -0.01 ? 'text-red-600' : shift.variance > 0.01 ? 'text-green-700' : 'text-gray-500'">
                <span>Variance</span>
                <span>{{ shift.variance >= 0 ? '+' : '' }}{{ lkr(shift.variance) }}</span>
              </div>
              <div class="flex justify-between text-sm border-t border-dashed border-gray-200 pt-2 mt-1">
                <span class="text-gray-500">Handover</span>
                <span class="font-semibold text-red-600">{{ lkr(shift.handover_amount) }}</span>
              </div>
              <div class="flex justify-between text-sm font-semibold text-amber-700">
                <span>Leftover</span>
                <span>{{ lkr(shift.leftover_amount) }}</span>
              </div>
              <div v-if="shift.notes" class="text-xs text-gray-400 mt-2 italic border-t border-gray-100 pt-2">
                {{ shift.notes }}
              </div>
            </div>

            <!-- Payment breakdown -->
            <div class="px-5 py-4">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Payment Methods</p>
              <div v-if="shift.payment_breakdown.length" class="space-y-2">
                <div v-for="p in shift.payment_breakdown" :key="p.method"
                     class="flex justify-between items-center text-sm">
                  <span class="capitalize px-2 py-0.5 rounded text-xs font-medium"
                        :class="methodClass(p.method)">
                    {{ p.method.replace('_', ' ') }}
                  </span>
                  <span class="font-semibold">{{ lkr(p.total) }}</span>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400">No payments recorded</p>

              <!-- Cash outs list -->
              <template v-if="shift.cash_outs.length">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-5 mb-2">Cash Outs</p>
                <div class="space-y-1">
                  <div v-for="co in shift.cash_outs" :key="co.reason + co.amount"
                       class="flex justify-between text-sm">
                    <span class="text-gray-500 truncate max-w-[60%]">{{ co.reason }}</span>
                    <span class="text-red-600 font-medium">−{{ lkr(co.amount) }}</span>
                  </div>
                </div>
              </template>
            </div>

            <!-- Category breakdown -->
            <div class="px-5 py-4">
              <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Sales by Category</p>
              <div v-if="shift.category_breakdown.length" class="space-y-2">
                <div v-for="cat in shift.category_breakdown" :key="cat.name">
                  <div class="flex justify-between text-sm mb-0.5">
                    <span class="text-gray-700 font-medium">{{ cat.name }}</span>
                    <span class="font-semibold text-gray-800">{{ lkr(cat.total) }}</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                      <div class="bg-amber-400 h-1.5 rounded-full"
                           :style="{ width: catPct(cat.total, shift.total_revenue) + '%' }"></div>
                    </div>
                    <span class="text-xs text-gray-400 w-10 text-right">×{{ cat.qty }}</span>
                  </div>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400">No items sold</p>
            </div>

          </div>
        </div>

      </div>
    </template>

    <!-- Grand totals -->
    <div v-if="totals && shifts.length" class="card p-0 overflow-hidden">
      <div class="px-5 py-3 bg-amber-50 border-b border-amber-200">
        <span class="font-bold text-amber-800 text-sm">Grand Total — {{ dateFrom }} to {{ dateTo }}</span>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 divide-x divide-gray-100">
        <div v-for="(item, idx) in grandTotalCells" :key="idx" class="px-4 py-3 text-center">
          <p class="text-xs text-gray-400 font-medium">{{ item.label }}</p>
          <p class="text-sm font-bold mt-0.5" :class="item.cls">{{ item.value }}</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import axios from 'axios'

const today    = new Date().toISOString().slice(0, 10)
const dateFrom = ref(today)
const dateTo   = ref(today)
const loading  = ref(false)
const shifts   = ref([])
const totals   = ref(null)
const expanded = reactive({})

function toggleShift(id) {
  expanded[id] = !expanded[id]
}

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(dateStr) {
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-LK', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
}

function formatTime(dt) {
  if (!dt) return '—'
  return new Date(dt).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function catPct(val, total) {
  if (!total) return 0
  return Math.round((val / total) * 100)
}

function methodClass(m) {
  return {
    cash:          'bg-green-100 text-green-700',
    card:          'bg-blue-100 text-blue-700',
    bank_transfer: 'bg-purple-100 text-purple-700',
    cheque:        'bg-orange-100 text-orange-700',
  }[m] ?? 'bg-gray-100 text-gray-600'
}

const groupedByDate = computed(() => {
  const groups = {}
  for (const s of shifts.value) {
    if (!groups[s.date]) groups[s.date] = []
    groups[s.date].push(s)
  }
  return groups
})

const grandTotalCells = computed(() => {
  if (!totals.value) return []
  const t = totals.value
  const varCls = t.total_variance < -0.01 ? 'text-red-600' : t.total_variance > 0.01 ? 'text-green-700' : 'text-gray-500'
  return [
    { label: 'Shifts',    value: t.shift_count,                                                          cls: 'text-gray-700' },
    { label: 'Revenue',   value: 'LKR ' + lkr(t.total_revenue),                                         cls: 'text-amber-700' },
    { label: 'Cash',      value: 'LKR ' + lkr(t.cash_sales),                                            cls: 'text-gray-700' },
    { label: 'Card',      value: 'LKR ' + lkr(t.card_sales),                                            cls: 'text-gray-700' },
    { label: 'Cash Outs', value: 'LKR ' + lkr(t.total_cash_outs),                                       cls: 'text-orange-600' },
    { label: 'Variance',  value: (t.total_variance >= 0 ? '+' : '') + 'LKR ' + lkr(t.total_variance),  cls: varCls },
    { label: 'Handover',  value: 'LKR ' + lkr(t.total_handover),                                        cls: 'text-red-600' },
    { label: 'Leftover',  value: 'LKR ' + lkr(t.total_leftover),                                        cls: 'text-green-700' },
  ]
})

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/cashier-shifts/report', {
      params: { from: dateFrom.value, to: dateTo.value }
    })
    shifts.value = data.shifts
    totals.value = data.totals
    // auto-expand single shift
    if (data.shifts.length === 1) expanded[data.shifts[0].id] = true
  } catch {
    shifts.value = []
    totals.value = null
  } finally {
    loading.value = false
  }
}

function print() { window.print() }

onMounted(load)
</script>

<style>
@media print {
  .no-print { display: none !important; }
}
</style>
