<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md flex flex-col">
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          {{ currentShift ? 'Close Cashier Shift' : 'Open Cashier Shift' }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <!-- OPEN SHIFT FORM -->
      <div v-if="!currentShift" class="p-6 space-y-4">
        <div>
          <label class="form-label">Opening Cash (LKR) *</label>
          <input v-model.number="openingCash" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
        </div>
        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
      </div>

      <!-- CLOSE SHIFT FORM -->
      <div v-else class="p-6 space-y-4">
        <div class="text-sm text-gray-500 bg-gray-50 rounded-lg px-4 py-3">
          <div class="flex justify-between"><span>Shift opened</span><span class="font-medium text-gray-700">{{ formatDateTime(currentShift.opened_at) }}</span></div>
          <div class="flex justify-between mt-1"><span>Cashier</span><span class="font-medium text-gray-700">{{ currentShift.user?.name }}</span></div>
        </div>

        <!-- Summary (shown after close API returns) -->
        <template v-if="closeSummary">
          <hr class="border-dashed" />
          <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Total Bills</span><span class="font-semibold">{{ closeSummary.total_sales_count }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Total Items</span><span class="font-semibold">{{ closeSummary.total_items }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Total Revenue</span><span class="font-semibold text-gold-700">LKR {{ lkr(closeSummary.total_revenue) }}</span></div>
          </div>
          <template v-if="closeSummary.category_breakdown?.length">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2">By Category</div>
            <div class="space-y-0.5 text-sm">
              <div v-for="cat in closeSummary.category_breakdown" :key="cat.name" class="flex justify-between">
                <span class="text-gray-500">{{ cat.name }} <span class="text-gray-400">(×{{ cat.qty }})</span></span>
                <span>LKR {{ lkr(cat.total) }}</span>
              </div>
            </div>
          </template>
          <hr class="border-dashed my-1" />
          <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Cash Sales</span><span>LKR {{ lkr(closeSummary.cash_sales) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Card Sales</span><span>LKR {{ lkr(closeSummary.card_sales) }}</span></div>
            <div v-if="closeSummary.other_sales > 0" class="flex justify-between"><span class="text-gray-500">Other</span><span>LKR {{ lkr(closeSummary.other_sales) }}</span></div>
          </div>
          <template v-if="closeSummary.cash_outs?.length">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-2">Cash Outs</div>
            <div class="space-y-0.5 text-sm">
              <div v-for="co in closeSummary.cash_outs" :key="co.id" class="flex justify-between">
                <span class="text-gray-500 truncate max-w-[60%]">{{ co.reason }}</span>
                <span class="text-red-600 font-medium">− LKR {{ lkr(co.amount) }}</span>
              </div>
              <div class="flex justify-between font-semibold">
                <span class="text-gray-600">Total Cash Outs</span>
                <span class="text-red-600">− LKR {{ lkr(closeSummary.total_cash_outs) }}</span>
              </div>
            </div>
          </template>
          <hr class="border-dashed my-1" />
          <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Opening Cash</span><span>LKR {{ lkr(closeSummary.shift.opening_cash) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">+ Cash Sales</span><span>LKR {{ lkr(closeSummary.cash_sales) }}</span></div>
            <div v-if="closeSummary.total_cash_outs > 0" class="flex justify-between"><span class="text-gray-500">− Cash Outs</span><span class="text-red-600">LKR {{ lkr(closeSummary.total_cash_outs) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Expected Cash</span><span>LKR {{ lkr(closeSummary.expected_cash) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Closing Cash</span><span>LKR {{ lkr(closeSummary.shift.closing_cash) }}</span></div>
            <div class="flex justify-between font-semibold text-base" :class="closeSummary.variance < 0 ? 'text-red-600' : 'text-green-700'">
              <span>Variance</span><span>{{ closeSummary.variance >= 0 ? '+' : '' }}LKR {{ lkr(closeSummary.variance) }}</span>
            </div>
          </div>
        </template>

        <!-- Inputs (shown before close) -->
        <template v-else>
          <div>
            <label class="form-label">Closing Cash (LKR) *</label>
            <input v-model.number="closingCash" type="number" min="0" step="0.01" class="form-input" placeholder="0.00" />
          </div>
          <div>
            <label class="form-label">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea v-model="notes" rows="2" class="form-input" placeholder="Any remarks…"></textarea>
          </div>
        </template>

        <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
      </div>

      <!-- Hidden slip for printing -->
      <div id="shift-slip-wrapper" style="display:none;">
        <div id="shift-slip" class="shift-slip-paper">
          <!-- Header -->
          <div style="text-align:center; margin-bottom:4px;">
            <div style="font-size:15px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">{{ restaurant.name || 'POS System' }}</div>
            <div v-if="restaurant.address" style="font-size:10px; margin-top:2px; white-space:pre-line; font-weight:600;">{{ restaurant.address }}</div>
            <div style="font-size:12px; margin-top:5px; font-weight:bold; letter-spacing:0.5px;">
              {{ closeSummary ? '====  SHIFT CLOSED  ====' : '====  SHIFT OPENED  ====' }}
            </div>
          </div>
          <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />

          <!-- Shift Info -->
          <div style="font-size:11px; line-height:1.8; font-weight:600;">
            <div style="display:flex; justify-content:space-between;"><span>Cashier</span><span>{{ slipCashier }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Date</span><span>{{ slipDate }}</span></div>
            <div style="display:flex; justify-content:space-between;"><span>Time</span><span>{{ slipTime }}</span></div>
            <template v-if="closeSummary">
              <div style="display:flex; justify-content:space-between;"><span>Opened</span><span>{{ slipOpenedTime }}</span></div>
            </template>
          </div>

          <!-- Close summary -->
          <template v-if="closeSummary">
            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Sales Summary</div>
            <div style="font-size:11px; line-height:1.8; font-weight:600;">
              <div style="display:flex; justify-content:space-between;"><span>Total Bills</span><span>{{ closeSummary.total_sales_count }}</span></div>
              <div style="display:flex; justify-content:space-between;"><span>Total Items</span><span>{{ closeSummary.total_items }}</span></div>
              <div style="display:flex; justify-content:space-between; font-weight:800;"><span>Total Revenue</span><span>LKR {{ lkr(closeSummary.total_revenue) }}</span></div>
            </div>

            <template v-if="closeSummary.category_breakdown?.length">
              <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
              <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">By Category</div>
              <div style="font-size:11px; line-height:1.8; font-weight:600;">
                <div v-for="cat in closeSummary.category_breakdown" :key="cat.name" style="display:flex; justify-content:space-between;">
                  <span>{{ cat.name }} <span style="font-weight:500;">(×{{ cat.qty }})</span></span>
                  <span>{{ lkr(cat.total) }}</span>
                </div>
              </div>
            </template>

            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Payment Methods</div>
            <div style="font-size:11px; line-height:1.8; font-weight:600;">
              <div style="display:flex; justify-content:space-between;"><span>Cash</span><span>LKR {{ lkr(closeSummary.cash_sales) }}</span></div>
              <div style="display:flex; justify-content:space-between;"><span>Card</span><span>LKR {{ lkr(closeSummary.card_sales) }}</span></div>
              <div v-if="closeSummary.other_sales > 0" style="display:flex; justify-content:space-between;"><span>Other</span><span>LKR {{ lkr(closeSummary.other_sales) }}</span></div>
            </div>

            <template v-if="closeSummary.cash_outs?.length">
              <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
              <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Cash Outs</div>
              <div style="font-size:11px; line-height:1.8; font-weight:600;">
                <div v-for="co in closeSummary.cash_outs" :key="co.id" style="display:flex; justify-content:space-between;">
                  <span>{{ co.reason }}</span><span>- {{ lkr(co.amount) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-weight:800;">
                  <span>Total Cash Outs</span><span>- LKR {{ lkr(closeSummary.total_cash_outs) }}</span>
                </div>
              </div>
            </template>
            <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
            <div style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:3px;">Cash Drawer</div>
            <div style="font-size:11px; line-height:1.8; font-weight:600;">
              <div style="display:flex; justify-content:space-between;"><span>Opening Cash</span><span>LKR {{ lkr(closeSummary.shift.opening_cash) }}</span></div>
              <div style="display:flex; justify-content:space-between;"><span>+ Cash Sales</span><span>LKR {{ lkr(closeSummary.cash_sales) }}</span></div>
              <div v-if="closeSummary.total_cash_outs > 0" style="display:flex; justify-content:space-between;"><span>- Cash Outs</span><span>LKR {{ lkr(closeSummary.total_cash_outs) }}</span></div>
              <div style="display:flex; justify-content:space-between;"><span>Expected Cash</span><span>LKR {{ lkr(closeSummary.expected_cash) }}</span></div>
              <div style="display:flex; justify-content:space-between;"><span>Closing Cash</span><span>LKR {{ lkr(closeSummary.shift.closing_cash) }}</span></div>
              <div style="display:flex; justify-content:space-between; font-weight:800; font-size:12px; margin-top:2px;"
                   :style="closeSummary.variance < 0 ? 'color:#c00;' : 'color:#080;'">
                <span>Variance</span>
                <span>{{ closeSummary.variance >= 0 ? '+' : '' }}LKR {{ lkr(closeSummary.variance) }}</span>
              </div>
            </div>
            <template v-if="closeSummary.shift.notes">
              <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
              <div style="font-size:10px; font-weight:600; color:#555;">Notes: {{ closeSummary.shift.notes }}</div>
            </template>
          </template>

          <!-- Open shift only shows opening cash -->
          <template v-else>
            <div style="font-size:11px; line-height:1.8; font-weight:600; margin-top:2px;">
              <div style="display:flex; justify-content:space-between;"><span>Opening Cash</span><span>LKR {{ lkr(slipOpeningCash) }}</span></div>
            </div>
          </template>

          <hr style="border:none; border-top:1px dashed #666; margin:5px 0;" />
          <div style="text-align:center; font-size:10px; font-weight:600;">*** Thank You ***</div>
        </div>
      </div>

      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button v-if="!closeSummary" type="button" @click="$emit('close')" class="btn-secondary">Cancel</button>
        <button v-if="closeSummary" type="button" @click="$emit('close')" class="btn-secondary">Done</button>
        <button v-if="!closeSummary" @click="submit" :disabled="saving" class="btn-primary">
          {{ saving ? 'Please wait…' : (currentShift ? 'Close Shift & Print' : 'Open Shift & Print') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'

const props = defineProps({ currentShift: Object })
const emit  = defineEmits(['close', 'shifted'])

const openingCash  = ref(0)
const closingCash  = ref(0)
const notes        = ref('')
const saving       = ref(false)
const error        = ref('')
const closeSummary = ref(null)
const restaurant   = ref({ name: '', address: '' })

const preferredPrinter = import.meta.env.VITE_THERMAL_PRINTER ?? ''

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/settings/restaurant').catch(() => ({ data: {} }))
    restaurant.value = data || {}
  } catch {}
})

const slipCashier     = computed(() => closeSummary.value?.shift?.user?.name ?? props.currentShift?.user?.name ?? '')
const slipOpeningCash = computed(() => closeSummary.value?.shift?.opening_cash ?? openingCash.value)
const slipDate = computed(() => {
  const d = closeSummary.value ? new Date(closeSummary.value.shift.closed_at) : new Date()
  return d.toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
})
const slipTime = computed(() => {
  const d = closeSummary.value ? new Date(closeSummary.value.shift.closed_at) : new Date()
  return d.toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
})
const slipOpenedTime = computed(() => {
  const opened = closeSummary.value?.shift?.opened_at ?? props.currentShift?.opened_at
  if (!opened) return ''
  const d = new Date(opened)
  return d.toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDateTime(dt) {
  if (!dt) return ''
  const d = new Date(dt)
  return d.toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' +
         d.toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

function buildSlipHtml() {
  const el = document.getElementById('shift-slip')
  if (!el) return ''
  return `<!doctype html><html><head><meta charset="utf-8"><style>
    html,body{margin:0;padding:0;background:#fff;}
    .shift-slip-paper{width:75mm;max-width:75mm;margin:0;padding:4mm 4mm 4mm 3mm;box-sizing:border-box;
      font-family:'Courier New',Courier,monospace;font-size:11pt;line-height:1.45;font-weight:500;color:#000;background:#fff;}
    @page{size:76mm auto;margin:0;}
  </style></head><body>${el.outerHTML}</body></html>`
}

function loadQzScript() {
  return new Promise((resolve, reject) => {
    if (window.qz) { resolve(); return }
    const s = document.createElement('script')
    s.src = 'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js'
    s.async = true
    s.onload = () => resolve()
    s.onerror = () => reject(new Error('Failed to load QZ Tray'))
    document.head.appendChild(s)
  })
}

async function printSlip() {
  await nextTick()

  const html = buildSlipHtml()
  if (!html) return

  if (window.electronAPI?.printReceipt) {
    await window.electronAPI.printReceipt('pos', { pageSize: { width: 76000, height: 500000 } })
    return
  }

  try {
    await loadQzScript()
    const qz = window.qz
    qz.api.setPromiseType(Promise)
    qz.security.setCertificatePromise(() => Promise.resolve(null))
    qz.security.setSignaturePromise(() => Promise.resolve(''))
    if (!qz.websocket.isActive()) await qz.websocket.connect({ retries: 2, delay: 0.5 })

    let printer = null
    if (preferredPrinter) printer = await qz.printers.find(preferredPrinter).catch(() => null)
    if (!printer) printer = await qz.printers.getDefault().catch(() => null)
    if (!printer) throw new Error('No printer')

    const config = qz.configs.create(printer, { units: 'mm', copies: 1, scaleContent: true, rasterize: true })
    await qz.print(config, [{ type: 'html', format: 'plain', data: html }])
    return
  } catch {}

  // Fallback: open isolated print window so only the slip is printed
  const win = window.open('', '_blank', 'width=400,height=600')
  if (!win) return
  win.document.write(html)
  win.document.close()
  win.focus()
  win.print()
  win.close()
}

async function submit() {
  error.value = ''
  saving.value = true
  try {
    if (!props.currentShift) {
      const { data } = await axios.post('/api/cashier-shifts/open', { opening_cash: openingCash.value })
      emit('shifted', data)
      emit('close')
      printSlip().catch(() => {})
    } else {
      const { data } = await axios.post('/api/cashier-shifts/close', {
        closing_cash: closingCash.value,
        notes: notes.value || null,
      })
      closeSummary.value = data
      emit('shifted', null)
      await nextTick()
      printSlip().catch(() => {})
    }
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Something went wrong.'
  } finally {
    saving.value = false
  }
}
</script>

