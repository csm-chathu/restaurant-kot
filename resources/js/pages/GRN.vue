<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Goods Received Notes</h2>
        <p class="text-sm text-gray-500">Receive stock against purchase orders with partial receive support.</p>
      </div>
      <button class="btn-primary" @click="showForm = !showForm">{{ showForm ? 'Hide Form' : 'New GRN' }}</button>
    </div>

    <div v-if="showForm" class="card space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="form-label">Purchase Order *</label>
          <select v-model="form.purchase_id" class="form-input" @change="onPurchaseChange">
            <option value="">Select PO</option>
            <option v-for="po in purchases" :key="po.id" :value="po.id">{{ po.purchase_number }} - {{ po.supplier?.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Supplier Invoice</label>
          <input v-model="form.supplier_invoice_number" class="form-input" />
        </div>
        <div>
          <label class="form-label">Notes</label>
          <input v-model="form.notes" class="form-input" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr>
              <th class="table-th">Product</th>
              <th class="table-th">Receive Qty</th>
              <th class="table-th">Free Qty</th>
              <th class="table-th">Unit Cost</th>
              <th class="table-th">Batch</th>
              <th class="table-th">Expiry</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, i) in form.items" :key="i">
              <td class="table-td">{{ item.product_name }}</td>
              <td class="table-td"><input v-model.number="item.quantity_received" type="number" min="0" step="0.001" class="form-input" /></td>
              <td class="table-td"><input v-model.number="item.free_quantity" type="number" min="0" step="0.001" class="form-input" /></td>
              <td class="table-td"><input v-model.number="item.unit_cost" type="number" min="0" step="0.01" class="form-input" /></td>
              <td class="table-td"><input v-model="item.batch_number" class="form-input" /></td>
              <td class="table-td"><input v-model="item.expiry_date" type="date" class="form-input" /></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-end gap-2">
        <button class="btn-secondary" @click="showForm = false">Cancel</button>
        <button class="btn-primary" :disabled="saving" @click="submit">{{ saving ? 'Saving...' : 'Save GRN' }}</button>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">GRN #</th>
            <th class="table-th">PO #</th>
            <th class="table-th">Supplier</th>
            <th class="table-th">Status</th>
            <th class="table-th">Total</th>
            <th class="table-th">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="g in grns.data" :key="g.id">
            <td class="table-td font-mono text-xs">{{ g.grn_number }}</td>
            <td class="table-td">{{ g.purchase?.purchase_number }}</td>
            <td class="table-td">{{ g.supplier?.name }}</td>
            <td class="table-td"><span class="badge bg-blue-100 text-blue-700">{{ g.status }}</span></td>
            <td class="table-td">LKR {{ Number(g.total).toLocaleString() }}</td>
            <td class="table-td">{{ formatDate(g.received_at) }}</td>
          </tr>
          <tr v-if="!grns.data?.length"><td class="table-td text-center text-gray-400" colspan="6">No GRN records yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const grns = ref({ data: [] })
const purchases = ref([])
const showForm = ref(false)
const saving = ref(false)

const form = reactive({
  purchase_id: '',
  supplier_invoice_number: '',
  notes: '',
  items: [],
})

function formatDate(value) {
  return new Date(value).toLocaleDateString('en-LK')
}

async function load() {
  const [grnRes, purchaseRes] = await Promise.all([
    axios.get('/api/grns'),
    axios.get('/api/purchases', { params: { per_page: 200 } }),
  ])
  grns.value = grnRes.data
  purchases.value = purchaseRes.data.data
}

function onPurchaseChange() {
  const purchase = purchases.value.find(p => p.id == form.purchase_id)
  form.items = (purchase?.items ?? []).map(i => ({
    purchase_item_id: i.id,
    product_id: i.product_id,
    product_name: i.product?.name ?? 'Item',
    quantity_received: i.quantity,
    free_quantity: 0,
    unit_cost: i.unit_cost,
    batch_number: '',
    expiry_date: '',
  }))
}

async function submit() {
  saving.value = true
  try {
    await axios.post('/api/grns', {
      purchase_id: form.purchase_id,
      supplier_invoice_number: form.supplier_invoice_number,
      notes: form.notes,
      items: form.items,
    })
    showForm.value = false
    form.purchase_id = ''
    form.supplier_invoice_number = ''
    form.notes = ''
    form.items = []
    await load()
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
