<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 to-gray-800 py-12 px-4">
    <div class="max-w-md w-full">
      <div class="text-center mb-8">
        <!-- Logo -->
        <div class="flex justify-center mb-4">
          <img v-if="logoUrl" :src="logoUrl" alt="Logo"
            class="h-20 w-auto object-contain drop-shadow-lg" />
          <span v-else class="text-6xl">🍻</span>
        </div>
        <h2 class="text-3xl font-bold text-white">{{ shopName }}</h2>
        <p class="mt-2 text-gray-400">Sign in to continue</p>
      </div>

      <div class="card">
        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="form-label">Email</label>
            <input v-model="form.email" type="email" required class="form-input" placeholder="admin@store.local" />
          </div>
          <div>
            <label class="form-label">Password</label>
            <input v-model="form.password" type="password" required class="form-input" placeholder="••••••••" />
          </div>
          <p v-if="error" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ error }}</p>
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? 'Signing in…' : 'Sign in' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const auth     = useAuthStore()
const router   = useRouter()
const form     = reactive({ email: '', password: '' })
const error    = ref('')
const loading  = ref(false)
const logoUrl  = ref(null)
const shopName = ref('POS System')

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/public/settings')
    shopName.value = data.name ?? 'POS System'
    logoUrl.value  = data.logo_url ?? null

    if (data.logo_url) {
      const link = document.querySelector("link[rel='icon']") ?? document.createElement('link')
      link.rel  = 'icon'
      link.href = data.logo_url
      document.head.appendChild(link)
    }
  } catch {}
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.email, form.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>
