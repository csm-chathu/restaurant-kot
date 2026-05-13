<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Bottle Deposit Management</h2>
        <p class="text-sm text-gray-500">Track collected deposits and process refund or credit returns.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div class="card">
        <p class="text-xs text-gray-500">Collected</p>
        <p class="text-xl font-bold text-emerald-700">LKR {{ Number(summary.collected || 0).toLocaleString() }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Refunded</p>
        <p class="text-xl font-bold text-red-700">LKR {{ Number(summary.refunded || 0).toLocaleString() }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Credited</p>
        <p class="text-xl font-bold text-blue-700">LKR {{ Number(summary.credited || 0).toLocaleString() }}</p>
      </div>
      <div class="card">
        <p class="text-xs text-gray-500">Outstanding</p>
        <p class="text-xl font-bold text-amber-700">LKR {{ Number(summary.outstanding || 0).toLocaleString() }}</p>
      </div>
    </div>

    <div class="card space-y-3">
      <h3 class="font-semibold text-gray-700">Process Bottle Return</h3>
      <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
        <div>
          <label class="form-label">Customer</label>
          <select v-model="returnForm.customer_id" class="form-input">
            <option value="">Walk-in</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Product *</label>
          <select v-model="returnForm.product_id" class="form-input">
            <option value="">Select product</option>
            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div>
          <label class="form-label">Qty *</label>
          <input v-model.number="returnForm.quantity" type="number" min="0.001" step="0.001" class="form-input" />
        </div>
        <div>
          <label class="form-label">Amount/Bottle *</label>
          <input v-model.number="returnForm.amount_per_bottle" type="number" min="0" step="0.01" class="form-input" />
        </div>
        <div>
          <label class="form-label">Method *</label>
          <select v-model="returnForm.method" class="form-input">
            <option value="refund">Refund</option>
            <option value="credit">Credit</option>
          </select>
        </div>
        <div>
          <button class="btn-primary w-full" :disabled="saving" @click="processReturn">{{ saving ? 'Processing...' : 'Process' }}</button>
        </div>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b">
          <tr>
            <th class="table-th">Type</th>
            <th class="table-th">Customer</th>
            <th class="table-th">Product</th>
            <th class="table-th">Qty</th>
            <th class="table-th">Amount</th>
            <th class="table-th">Status</th>
            <th class="table-th">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="d in deposits.data" :key="d.id">
            <td class="table-td capitalize">{{ d.type }}</td>
            <td class="table-td">{{ d.customer?.name || 'Walk-in' }}</td>
            <td class="table-td">{{ d.product?.name }}</td>
            <td class="table-td">{{ d.quantity }}</td>
            <td class="table-td">LKR {{ Number(d.total_amount).toLocaleString() }}</td>
            <td class="table-td"><span class="badge bg-gray-100 text-gray-700">{{ d.status }}</span></td>
            <td class="table-td">{{ formatDate(d.processed_at) }}</td>
          </tr>
          <tr v-if="!deposits.data?.length"><td colspan="7" class="table-td text-center text-gray-400">No bottle deposit records yet</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import axios from 'axios'

const deposits = ref({ data: [] })
const summary = ref({})
const customers = ref([])
const products = ref([])
const saving = ref(false)

const returnForm = reactive({
  customer_id: '',
  product_id: '',
  quantity: 1,
  amount_per_bottle: 100,
  method: 'refund',
})

function formatDate(v) {
  return new Date(v).toLocaleString('en-LK')
}

async function load() {
  const [depositRes, summaryRes, customerRes, productRes] = await Promise.all([
    axios.get('/api/bottle-deposits'),
    axios.get('/api/bottle-deposits/summary'),
    axios.get('/api/customers/all'),
    axios.get('/api/products', { params: { per_page: 300, product_type: 'Beer' } }),
  ])
  deposits.value = depositRes.data
  summary.value = summaryRes.data
  customers.value = customerRes.data
  products.value = productRes.data.data
}

async function processReturn() {
  saving.value = true
  try {
    await axios.post('/api/bottle-deposits/return', returnForm)
    await load()
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
