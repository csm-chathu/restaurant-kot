<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Restaurant Settings</h2>
      <p class="text-sm text-gray-500 mt-0.5">Update restaurant name, logo, and address shown in reports and printouts.</p>
    </div>

    <div class="card space-y-5">
      <div v-if="canSelectBranch" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Branch</label>
          <select v-model="selectedBranchId" @change="load" class="form-input">
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }} ({{ branch.code }})
            </option>
          </select>
        </div>
        <div>
          <label class="form-label">Branch Code</label>
          <input :value="branchCode" class="form-input bg-gray-100" readonly />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="form-label">Restaurant Name *</label>
          <input v-model="form.name" class="form-input" maxlength="120" required />
        </div>
        <div>
          <label class="form-label">Logo</label>
          <input type="file" accept="image/*" @change="onLogoChange" class="form-input" />
        </div>

        <div class="md:col-span-2">
          <label class="form-label">Address</label>
          <textarea v-model="form.address" rows="2" class="form-input"></textarea>
        </div>

        <div>
          <label class="form-label">City</label>
          <input v-model="form.city" class="form-input" maxlength="100" />
        </div>
        <div>
          <label class="form-label">Country</label>
          <input v-model="form.country" class="form-input" maxlength="100" />
        </div>
      </div>

      <div v-if="previewLogo || currentLogo" class="pt-2">
        <p class="text-xs text-gray-500 mb-2 uppercase tracking-wide">Logo Preview</p>
        <img :src="previewLogo || currentLogo" alt="Restaurant logo" class="w-24 h-24 object-cover rounded-lg border border-gray-200" />
      </div>

      <p v-if="message" class="text-sm text-emerald-700 bg-emerald-50 px-3 py-2 rounded-lg">{{ message }}</p>
      <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>

      <div class="flex gap-3">
        <button @click="load" type="button" class="btn-secondary">Reset</button>
        <button @click="save" type="button" :disabled="saving" class="btn-primary">
          {{ saving ? 'Saving…' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const canSelectBranch = computed(() => ['admin', 'owner'].includes(auth.user?.role))

const form = reactive({
  name: '',
  address: '',
  city: '',
  country: '',
})

const branches = ref([])
const selectedBranchId = ref(auth.user?.branch_id ?? null)
const branchCode = ref('')
const currentLogo = ref('')
const previewLogo = ref('')
const logoFile = ref(null)
const saving = ref(false)
const message = ref('')
const error = ref('')

function onLogoChange(event) {
  const file = event.target.files?.[0]
  logoFile.value = file ?? null
  previewLogo.value = file ? URL.createObjectURL(file) : ''
}

async function load() {
  message.value = ''
  error.value = ''
  const requestConfig = canSelectBranch.value && selectedBranchId.value
    ? { params: { branch_id: selectedBranchId.value } }
    : {}
  const { data } = await axios.get('/api/settings/restaurant', requestConfig)
  form.name = data.name ?? ''
  form.address = data.address ?? ''
  form.city = data.city ?? ''
  form.country = data.country ?? ''
  branchCode.value = data.code ?? ''
  currentLogo.value = data.logo_url ?? ''
  previewLogo.value = ''
  logoFile.value = null
}

async function loadBranches() {
  if (!canSelectBranch.value) return
  const { data } = await axios.get('/api/branches')
  branches.value = data
  if (!selectedBranchId.value && branches.value.length) {
    selectedBranchId.value = branches.value[0].id
  }
}

async function save() {
  saving.value = true
  message.value = ''
  error.value = ''

  try {
    const payload = new FormData()
    payload.append('name', form.name || '')
    payload.append('address', form.address || '')
    payload.append('city', form.city || '')
    payload.append('country', form.country || '')
    if (canSelectBranch.value && selectedBranchId.value) payload.append('branch_id', selectedBranchId.value)
    if (logoFile.value) payload.append('logo', logoFile.value)

    const { data } = await axios.post('/api/settings/restaurant', payload)
    message.value = data.message || 'Settings saved'
    currentLogo.value = data.settings?.logo_url || ''
    previewLogo.value = ''
    logoFile.value = null
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Failed to save settings'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadBranches()
  await load()
})
</script>
