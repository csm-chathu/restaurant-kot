<template>
  <div>
    <!-- Screen toolbar (hidden when printing) -->
    <div class="no-print flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <router-link to="/sales"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition-colors">
          <ArrowLeftIcon class="w-4 h-4" /> Back to Bills
        </router-link>
        <span class="text-gray-300">/</span>
        <span class="text-sm font-medium text-gray-700">{{ sale?.invoice_number }}</span>
      </div>
      <div class="flex gap-2">
        <button @click="directPrint" :disabled="directPrinting || loading || !sale"
          class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <ArrowPathIcon v-if="directPrinting" class="w-4 h-4 animate-spin" />
          <PrinterIcon v-else class="w-4 h-4" />
          {{ directPrinting ? 'Printing...' : 'Direct Print' }}
        </button>
        <button @click="printReceipt"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-sm shadow-sm transition-colors">
          <PrinterIcon class="w-4 h-4" /> Print Receipt
        </button>
      </div>
    </div>

    <div v-if="directPrintError" class="no-print mb-4 px-3 py-2 rounded-lg border border-red-200 bg-red-50 text-red-700 text-sm">
      {{ directPrintError }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20 text-gray-400">
      <ArrowPathIcon class="w-5 h-5 animate-spin mr-2" /> Loading…
    </div>

    <!-- Receipt (76mm thermal preview) -->
    <div v-else-if="sale" id="receipt-wrapper">
      <div id="receipt" class="receipt-paper">

        <!-- ── HEADER ── -->
        <div style="text-align:center; margin-bottom:6px;">
          <div style="font-size:15px; font-weight:bold; letter-spacing:1px; text-transform:uppercase;">
            {{ receiptCompanyName }}
          </div>
          <div v-if="receiptAddress" style="font-size:10px; margin-top:2px; line-height:1.35; white-space:pre-line; font-weight:600;">
            {{ receiptAddress }}
          </div>
          <div style="font-size:10px; margin-top:2px;">Bill Receipt</div>
        </div>

        <hr class="receipt-divider-double" />

        <!-- ── INVOICE META ── -->
        <div style="font-size:11px; font-weight:600; line-height:1.5; margin-bottom:4px; color:#000;">
          <div class="flex-row">
            <span>Invoice :</span><span style="font-weight:bold; float:right;">{{ sale.invoice_number }}</span>
          </div>
          <div class="flex-row">
            <span>Date    :</span><span style="float:right;">{{ formatDate(sale.sold_at) }}</span>
          </div>
          <div class="flex-row">
            <span>Time    :</span><span style="float:right;">{{ formatTime(sale.sold_at) }}</span>
          </div>
          <div class="flex-row">
            <span>Cashier :</span><span style="float:right;">{{ sale.user?.name ?? '—' }}</span>
          </div>
          <div class="flex-row">
            <span>Payment :</span><span style="float:right; text-transform:capitalize;">{{ paymentSummary }}</span>
          </div>
          <div class="flex-row">
            <span>Status  :</span>
            <span style="float:right; font-weight:bold; text-transform:capitalize;">
              {{ (sale.payment_status || sale.status || 'completed').toUpperCase() }}
            </span>
          </div>
        </div>

        <hr class="receipt-divider" />

        <!-- ── CUSTOMER ── -->
        <div style="font-size:10px; margin-bottom:4px;">
          <div><strong>Customer:</strong> {{ sale.customer?.name ?? 'Walk-in' }}</div>
          <div v-if="sale.customer?.phone">Phone: {{ sale.customer.phone }}</div>
        </div>

        <hr class="receipt-divider" />

        <!-- ── ITEMS ── -->
        <div style="font-size:10px;">
          <!-- Column headers -->
          <div style="display:flex; font-weight:bold; border-bottom:1px solid #333; padding-bottom:3px; margin-bottom:3px;">
            <span style="flex:1;">Item</span>
            <span style="width:28px; text-align:center;">Qty</span>
            <span style="width:54px; text-align:right;">Price</span>
            <span style="width:58px; text-align:right;">Total</span>
          </div>

          <!-- Each item -->
          <div v-for="item in sale.items" :key="item.id" style="margin-bottom:5px;">
            <div style="display:flex; align-items:baseline;">
              <span style="flex:1; font-weight:bold; word-break:break-word; padding-right:4px;">
                {{ item.product?.name ?? 'Unknown' }}
              </span>
              <span style="width:28px; text-align:center;">{{ item.quantity }}</span>
              <span style="width:54px; text-align:right;">{{ lkr(item.unit_price) }}</span>
              <span style="width:58px; text-align:right; font-weight:bold;">{{ lkr(item.total) }}</span>
            </div>
            <!-- Sub-detail line -->
            <div style="color:#222; font-size:10px; padding-left:2px; line-height:1.35;">
              <span v-if="item.product?.sku">SKU:{{ item.product.sku }}  </span>
              <span v-if="item.product?.karat">{{ item.product.karat }}</span>
              <span v-if="item.product?.weight"> {{ item.product.weight }}g</span>
            </div>
            <!-- Value breakdown -->
            <div v-if="item.gold_value || item.making_charge || item.wastage_amount || item.gemstone_value"
              style="font-size:10px; color:#222; padding-left:2px;">
              <span v-if="item.gold_value">Gold:{{ lkr(item.gold_value) }}  </span>
              <span v-if="item.gemstone_value">Gem:{{ lkr(item.gemstone_value) }}  </span>
              <span v-if="item.making_charge">MC:{{ lkr(item.making_charge) }}  </span>
              <span v-if="item.wastage_amount">Wst:{{ lkr(item.wastage_amount) }}</span>
            </div>
            <div v-if="Number(item.discount) > 0" style="font-size:10px; color:#222; padding-left:2px;">
              Item Disc: -{{ lkr(item.discount) }}
            </div>
          </div>
        </div>

        <hr class="receipt-divider-solid" />

        <!-- ── TOTALS ── -->
        <div style="font-size:11px;">
          <div v-if="Number(sale.subtotal) !== Number(sale.total)" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Subtotal</span><span>LKR {{ lkr(sale.subtotal) }}</span>
          </div>
          <div v-if="Number(sale.discount) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Discount</span><span>-LKR {{ lkr(sale.discount) }}</span>
          </div>
          <div v-if="Number(sale.tax) > 0" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Tax ({{ sale.tax_rate }}%)</span><span>+LKR {{ lkr(sale.tax) }}</span>
          </div>
        </div>

        <hr class="receipt-divider-double" />

        <div style="display:flex; justify-content:space-between; font-size:14px; font-weight:bold; margin:4px 0;">
          <span>TOTAL</span><span>LKR {{ lkr(sale.total) }}</span>
        </div>

        <hr class="receipt-divider" />

        <div style="font-size:11px;">
          <div v-for="(payment, index) in sale.payments || []" :key="payment.id ?? index" style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>{{ payment.payment_method?.replace('_', ' ') }}</span><span>LKR {{ lkr(payment.amount) }}</span>
          </div>
          <div style="display:flex; justify-content:space-between; margin-bottom:2px;">
            <span>Paid</span><span>LKR {{ lkr(sale.amount_paid) }}</span>
          </div>
          <div v-if="Number(sale.amount_paid) > Number(sale.total)"
            style="display:flex; justify-content:space-between; font-weight:bold;">
            <span>Change</span><span>LKR {{ lkr(Number(sale.amount_paid) - Number(sale.total)) }}</span>
          </div>
          <div v-if="Number(sale.amount_paid) < Number(sale.total)"
            style="display:flex; justify-content:space-between; font-weight:bold;">
            <span>Balance Due</span><span>LKR {{ lkr(Number(sale.total) - Number(sale.amount_paid)) }}</span>
          </div>
        </div>

        <!-- ── NOTES ── -->
        <div v-if="sale.notes" style="margin-top:6px; font-size:10px; color:#222;">
          <hr class="receipt-divider" />
          Note: {{ sale.notes }}
        </div>

        <hr class="receipt-divider" />

        <!-- ── BARCODE-STYLE INVOICE NUMBER ── -->
        <div style="text-align:center; margin:6px 0 2px;">
          <!-- Visual barcode bars using CSS — works on all printers -->
          <div class="barcode-bars" :data-value="sale.invoice_number">
            <svg ref="barcodeSvg" style="display:block; width:100%; max-width:100%; height:34px; margin:0 auto;"></svg>
          </div>
          <div style="font-size:10px; letter-spacing:2px; margin-top:2px; font-weight:600;">{{ sale.invoice_number }}</div>
        </div>

        <hr class="receipt-divider" />

        <!-- ── FOOTER ── -->
        <div style="text-align:center; font-size:10px; line-height:1.6;">
          <div style="font-weight:bold;">*** Thank You! Come Again ***</div>
          <div style="font-size:10px; color:#222;">{{ formatDate(sale.sold_at) }}</div>
        </div>

      </div>
    </div>

    <!-- Error -->
    <div v-else class="text-center py-20 text-gray-400">
      <p>Bill not found.</p>
      <router-link to="/sales" class="text-amber-600 hover:underline text-sm mt-2 inline-block">← Back to Bills</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import JsBarcode from 'jsbarcode'
import { ArrowLeftIcon, PrinterIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const route          = useRoute()
const sale           = ref(null)
const loading        = ref(true)
const barcodeSvg     = ref(null)
const appName        = import.meta.env.VITE_APP_NAME ?? 'Liquor Shop POS'
const restaurant     = ref({ name: '', address: '', city: '', country: '' })
const preferredPrinter = import.meta.env.VITE_THERMAL_PRINTER ?? ''
const directPrinting = ref(false)
const directPrintError = ref('')

const receiptCompanyName = computed(() => (restaurant.value.name || appName))
const receiptAddress = computed(() => {
  const lines = [restaurant.value.address, [restaurant.value.city, restaurant.value.country].filter(Boolean).join(', ')]
    .map(v => (v || '').trim())
    .filter(Boolean)
  return lines.join('\n')
})
const paymentSummary = computed(() => {
  const payments = sale.value?.payments ?? []
  const methods = [...new Set(payments.map(payment => payment.payment_method).filter(Boolean))]

  if (methods.length > 1) {
    return methods.map(method => method.replace('_', ' ')).join(' + ')
  }

  if (methods.length === 1) {
    return methods[0].replace('_', ' ')
  }

  return sale.value?.payment_method?.replace('_', ' ') ?? 'Unpaid'
})

function lkr(val) {
  return Number(val || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatDate(d) {
  return new Date(d).toLocaleDateString('en-LK', { day: '2-digit', month: 'short', year: 'numeric' })
}
function formatTime(d) {
  return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' })
}

// Render a standards-compliant barcode for reliable handheld scanning.
function drawBarcode(svg, text) {
  if (!svg || !text) return
  const value = String(text).trim().toUpperCase().replace(/[^0-9A-Z\-\.\ \$\/\+%]/g, '')
  if (!value) return

  JsBarcode(svg, value, {
    format: 'CODE128',
    width: 2.2,
    height: 36,
    margin: 12,
    background: '#ffffff',
    lineColor: '#000000',
    displayValue: false,
  })
}

function printReceipt() { window.print() }

function qzScriptLoaded() {
  return typeof window !== 'undefined' && !!window.qz
}

function loadQzScript() {
  return new Promise((resolve, reject) => {
    if (qzScriptLoaded()) {
      resolve()
      return
    }

    const existing = document.querySelector('script[data-qz-tray="1"]')
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true })
      existing.addEventListener('error', () => reject(new Error('Failed to load QZ Tray library.')), { once: true })
      return
    }

    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js'
    script.async = true
    script.dataset.qzTray = '1'
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load QZ Tray library.'))
    document.head.appendChild(script)
  })
}

function buildDirectPrintHtml() {
  const receipt = document.getElementById('receipt')
  if (!receipt) return ''

  return `<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    html, body { margin: 0; padding: 0; background: #fff; }
    .receipt-paper {
      width: 75mm;
      max-width: 75mm;
      margin: 0;
      padding: 4mm 4mm 4mm 3mm;
      box-sizing: border-box;
      font-family: 'Courier New', Courier, monospace;
      font-size: 11pt;
      line-height: 1.45;
      font-weight: 500;
      color: #000;
      background: #fff;
    }
    .receipt-divider { border: none; border-top: 1px dashed #666; margin: 6px 0; }
    .receipt-divider-solid { border: none; border-top: 1px solid #555; margin: 6px 0; }
    .receipt-divider-double { border: none; border-top: 3px double #333; margin: 6px 0; }
    canvas { max-width: 100%; }
  </style>
</head>
<body>${receipt.outerHTML}</body>
</html>`
}

async function ensureQzConnected() {
  await loadQzScript()

  const qz = window.qz
  if (!qz) throw new Error('QZ Tray library is not available.')

  qz.api.setPromiseType(Promise)
  qz.security.setCertificatePromise(() => Promise.resolve(null))
  qz.security.setSignaturePromise(() => Promise.resolve(''))

  if (!qz.websocket.isActive()) {
    await qz.websocket.connect({ retries: 2, delay: 0.5 })
  }

  return qz
}

async function resolvePrinter(qz) {
  if (preferredPrinter) {
    const exact = await qz.printers.find(preferredPrinter).catch(() => null)
    if (exact) return exact
  }

  const fallback = await qz.printers.getDefault().catch(() => null)
  if (fallback) return fallback

  throw new Error('No printer found. Set a default printer in Windows or configure VITE_THERMAL_PRINTER.')
}

async function directPrint() {
  if (!sale.value) return
  directPrintError.value = ''
  directPrinting.value = true

  try {
    const html = buildDirectPrintHtml()
    if (!html) throw new Error('Receipt content is not ready yet.')

    const qz = await ensureQzConnected()
    const printer = await resolvePrinter(qz)

    const config = qz.configs.create(printer, {
      units: 'mm',
      copies: 1,
      scaleContent: true,
      rasterize: true,
      jobName: `Sale-${sale.value.invoice_number ?? sale.value.id}`,
    })

    await qz.print(config, [{ type: 'html', format: 'plain', data: html }])
  } catch (err) {
    const msg = err?.message || 'Direct print failed.'
    directPrintError.value = `${msg} Install/open QZ Tray, trust this site, then try again.`
  } finally {
    directPrinting.value = false
  }
}

watch(barcodeSvg, (svg) => {
  if (svg && sale.value) drawBarcode(svg, sale.value.invoice_number)
})

onMounted(async () => {
  try {
    const [saleRes, settingsRes] = await Promise.all([
      axios.get(`/api/sales/${route.params.id}`),
      axios.get('/api/settings/restaurant').catch(() => ({ data: {} })),
    ])

    sale.value = saleRes.data
    restaurant.value = settingsRes.data || {}
    await nextTick()
    drawBarcode(barcodeSvg.value, saleRes.data.invoice_number)
  } catch {
    sale.value = null
  } finally {
    loading.value = false
  }
})
</script>

<style>
/* ── Screen preview ─────────────────────────────────────── */
.receipt-paper {
  width: 287px;           /* 76mm ≈ 287px at 96dpi */
  padding: 16px 14px;
  margin: 0 auto 32px;
  background: #fff;
  box-shadow: 0 0 0 1px #e5e7eb, 0 4px 24px rgba(0,0,0,0.08);
  border-radius: 4px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  line-height: 1.45;
  font-weight: 500;
  color: #111;
}

.receipt-divider        { border: none; border-top: 1px dashed #666; margin: 6px 0; }
.receipt-divider-solid  { border: none; border-top: 1px solid #555; margin: 6px 0; }
.receipt-divider-double { border: none; border-top: 3px double #333; margin: 6px 0; }

/* ── 76mm Thermal print ──────────────────────────────────── */
@media print {
  /* Hide app shell/chrome */
  .no-print,
  aside,
  nav,
  header,
  footer {
    display: none !important;
  }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
    background: #fff !important;
  }

  /* Reset application layout wrappers to avoid clipping and extra whitespace */
  #app,
  #app > div,
  #app main {
    width: auto !important;
    min-width: 0 !important;
    height: auto !important;
    min-height: 0 !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #fff !important;
  }

  #receipt-wrapper {
    position: static !important;
    width: 75mm !important;
    padding: 0 !important;
    margin: 0 auto !important;
    overflow: visible !important;
    page-break-before: auto;
    page-break-after: auto;
  }

  .receipt-paper {
    width: 75mm !important;
    max-width: 75mm !important;
    margin: 0 !important;
    padding: 4mm 4mm 4mm 3mm !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    font-size: 11pt !important;
    font-weight: 500 !important;
    font-family: 'Courier New', Courier, monospace !important;
    color: #000 !important;
    background: #fff !important;
    break-inside: auto;
    page-break-inside: auto;
  }

  /* Force black — thermal has no colour ink */
  #receipt-wrapper,
  #receipt-wrapper * {
    color: #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    background: transparent !important;
  }

  @page {
    size: 76mm auto;
    margin: 0;
  }
}
</style>
