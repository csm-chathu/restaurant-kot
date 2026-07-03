<template>
  <Teleport to="body">
    <!-- Modal overlay -->
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 no-print" @click.self="$emit('close')">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 flex flex-col max-h-[90vh]">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0"
          :class="headerBg">
          <div class="flex items-center gap-2">
            <span class="text-xl">{{ headerIcon }}</span>
            <div>
              <h3 class="text-base font-bold" :class="headerTextClass">{{ headerTitle }}</h3>
              <p class="text-xs text-gray-400 mt-0.5">{{ sale?.invoice_number }}</p>
            </div>
          </div>
          <button @click="$emit('close')" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg text-xl leading-none">✕</button>
        </div>

        <!-- KOT preview (scrollable) -->
        <div class="overflow-y-auto flex-1 p-4 flex justify-center">
          <div class="kot-paper">

            <div class="kot-header">
              <div class="kot-title" :class="type === 'cancel' ? 'kot-title-cancel' : ''">{{ ticketTitle }}</div>
              <div v-if="restaurantName" class="kot-restaurant">{{ restaurantName }}</div>
            </div>

            <div class="kot-divider">- - - - - - - - - - - - - -</div>

            <div class="kot-meta">
              <div class="kot-meta-row">
                <span>Order :</span>
                <span class="kot-bold">{{ sale?.order_type === 'takeaway' ? 'TAKEAWAY' : 'DINE IN' }}</span>
              </div>
              <div v-if="sale?.table_number" class="kot-meta-row">
                <span>Table :</span>
                <span class="kot-bold">{{ sale.table_number }}</span>
              </div>
              <div class="kot-meta-row">
                <span>Time  :</span>
                <span>{{ now }}</span>
              </div>
              <div class="kot-meta-row">
                <span>Ref   :</span>
                <span>{{ sale?.invoice_number }}</span>
              </div>
            </div>

            <div class="kot-divider">================================</div>

            <div class="kot-items">
              <div v-for="(item, i) in items" :key="i" class="kot-item">
                <div class="kot-item-main" :class="type === 'cancel' ? 'kot-item-cancel' : ''">
                  <span class="kot-qty">{{ item.quantity }}×</span>
                  <span class="kot-name">{{ item.name }}</span>
                </div>
                <div v-if="item.serving_ml > 0" class="kot-notes">&nbsp;&nbsp;&nbsp;↳ {{ item.serving_ml }}ml</div>
                <div v-if="item.notes" class="kot-notes">&nbsp;&nbsp;&nbsp;↳ {{ item.notes }}</div>
              </div>
            </div>

            <div class="kot-divider">================================</div>

            <div v-if="type === 'cancel'" class="kot-cancel-reason">
              *** STOP PREPARING ***
            </div>

            <div class="kot-footer">
              <div>Items: {{ items.length }}</div>
              <div>Qty: {{ totalQty }}</div>
            </div>

          </div>
        </div>

        <!-- Footer buttons -->
        <div class="shrink-0 flex gap-2 px-5 py-4 border-t border-gray-100">
          <button @click="$emit('close')"
            class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-semibold text-sm transition-colors">
            Close
          </button>
          <button @click="printKot"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white font-bold text-sm transition-colors shadow-lg"
            :class="type === 'cancel' ? 'bg-red-600 hover:bg-red-700 shadow-red-500/20' : 'bg-green-600 hover:bg-green-700 shadow-green-500/20'">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.75 19.5m10.56-5.671-.004.003M17.25 19.5l.75-5.671M4.5 19.5h15M4.5 8.25h15M6 8.25V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v2.25" />
            </svg>
            {{ type === 'cancel' ? 'Print Cancel Notice' : 'Print KOT' }}
          </button>
        </div>

      </div>
    </div>

    <!-- Print-only area -->
    <div v-if="show" class="kot-print-only">
      <div class="kot-paper">

        <div class="kot-header">
          <div class="kot-title" :class="type === 'cancel' ? 'kot-title-cancel' : ''">{{ ticketTitle }}</div>
          <div v-if="restaurantName" class="kot-restaurant">{{ restaurantName }}</div>
        </div>

        <div class="kot-divider">- - - - - - - - - - - - - -</div>

        <div class="kot-meta">
          <div class="kot-meta-row">
            <span>Order :</span>
            <span class="kot-bold">{{ sale?.order_type === 'takeaway' ? 'TAKEAWAY' : 'DINE IN' }}</span>
          </div>
          <div v-if="sale?.table_number" class="kot-meta-row">
            <span>Table :</span>
            <span class="kot-bold">{{ sale.table_number }}</span>
          </div>
          <div class="kot-meta-row">
            <span>Time  :</span>
            <span>{{ now }}</span>
          </div>
          <div class="kot-meta-row">
            <span>Ref   :</span>
            <span>{{ sale?.invoice_number }}</span>
          </div>
        </div>

        <div class="kot-divider">================================</div>

        <div class="kot-items">
          <div v-for="(item, i) in items" :key="i" class="kot-item">
            <div class="kot-item-main" :class="type === 'cancel' ? 'kot-item-cancel' : ''">
              <span class="kot-qty">{{ item.quantity }}×</span>
              <span class="kot-name">{{ item.name }}</span>
            </div>
            <div v-if="item.serving_ml > 0" class="kot-notes">&nbsp;&nbsp;&nbsp;↳ {{ item.serving_ml }}ml</div>
            <div v-if="item.notes" class="kot-notes">&nbsp;&nbsp;&nbsp;↳ {{ item.notes }}</div>
          </div>
        </div>

        <div class="kot-divider">================================</div>

        <div v-if="type === 'cancel'" class="kot-cancel-reason">
          *** STOP PREPARING ***
        </div>

        <div class="kot-footer">
          <div>Items: {{ items.length }}</div>
          <div>Qty: {{ totalQty }}</div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show:           { type: Boolean, default: false },
  sale:           { type: Object,  default: null },
  items:          { type: Array,   default: () => [] },
  restaurantName: { type: String,  default: '' },
  now:            { type: String,  default: '' },
  isAddon:        { type: Boolean, default: false },
  type:           { type: String,  default: 'kot' }, // 'kot' | 'cancel'
})

const emit = defineEmits(['close', 'printed'])

const totalQty = computed(() => props.items.reduce((s, i) => s + (i.quantity || 0), 0))

const headerBg = computed(() => {
  if (props.type === 'cancel') return 'bg-red-50'
  if (props.isAddon)           return 'bg-blue-50'
  return ''
})
const headerIcon = computed(() => {
  if (props.type === 'cancel') return '🚫'
  return '🍳'
})
const headerTitle = computed(() => {
  if (props.type === 'cancel') return 'Cancel Notice'
  if (props.isAddon)           return 'ADD-ON Order'
  return 'Kitchen Order Ticket'
})
const headerTextClass = computed(() => {
  if (props.type === 'cancel') return 'text-red-700'
  if (props.isAddon)           return 'text-blue-700'
  return 'text-gray-900'
})
const ticketTitle = computed(() => {
  if (props.type === 'cancel') return '*** CANCEL ORDER ***'
  if (props.isAddon)           return '*** ADD-ON ORDER ***'
  return 'KITCHEN ORDER TICKET'
})

function printKot() {
  emit('printed')
  window.print()
}
</script>

<style>
.kot-paper {
  width: 250px;
  font-family: 'Courier New', Courier, monospace;
  font-size: 12px;
  color: #000;
  background: #fff;
  padding: 10px 8px;
}

.kot-header        { text-align: center; margin-bottom: 6px; }
.kot-title         { font-size: 15px; font-weight: 900; letter-spacing: 1px; }
.kot-title-cancel  { color: #dc2626; }
.kot-restaurant    { font-size: 11px; margin-top: 2px; }
.kot-divider       { text-align: center; font-size: 10px; color: #555; margin: 5px 0; }
.kot-meta          { margin: 5px 0; }
.kot-meta-row      { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 11px; }
.kot-bold          { font-weight: 900; }
.kot-items         { margin: 4px 0; }
.kot-item          { margin-bottom: 7px; }
.kot-item-main     { display: flex; align-items: baseline; gap: 5px; font-weight: 700; font-size: 14px; }
.kot-item-cancel   { text-decoration: line-through; opacity: 0.7; }
.kot-qty           { min-width: 26px; font-size: 15px; font-weight: 900; }
.kot-name          { flex: 1; }
.kot-notes         { font-size: 10px; color: #333; font-style: italic; margin-top: 1px; font-weight: 400; }
.kot-cancel-reason { text-align: center; font-weight: 900; font-size: 12px; color: #dc2626; margin: 4px 0; }
.kot-footer        { display: flex; justify-content: space-between; font-size: 10px; color: #555; margin-top: 3px; }

.kot-print-only { display: none; }

@media print {
  @page { size: 76mm auto; margin: 0; }
  body > *         { display: none !important; }
  #app             { display: none !important; }
  .no-print        { display: none !important; }

  .kot-print-only {
    display: block !important;
    position: fixed;
    top: 0; left: 0;
    width: 76mm;
  }
  .kot-print-only .kot-paper      { width: 76mm; padding: 4mm 3mm; font-size: 12px; }
  .kot-print-only .kot-item-main  { font-size: 14px; }
  .kot-print-only .kot-qty        { font-size: 15px; }
  .kot-print-only .kot-title-cancel { color: #000; }
  .kot-print-only .kot-cancel-reason { color: #000; }
  .kot-print-only .kot-item-cancel   { text-decoration: line-through; }
}
</style>
