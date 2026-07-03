<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
    <div class="w-full max-w-sm text-center space-y-5">
      <div class="text-7xl animate-bounce">🎉</div>
      <h1 class="text-2xl font-black text-gray-900">Order Placed!</h1>
      <p class="text-gray-600 text-base">Your order has been sent to the kitchen.</p>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-2 text-left">
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">Table</span>
          <span class="font-bold text-amber-600">{{ tableNumber }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">Status</span>
          <span class="font-bold text-green-600">Confirmed ✓</span>
        </div>
      </div>
      <p class="text-sm text-gray-400">Staff will come to your table shortly. Payment at the end.</p>
      <button
        @click="goBack"
        class="w-full py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-2xl font-black text-base transition-colors shadow-lg shadow-amber-500/30"
      >
        Order More Items
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'

const route  = useRoute()
const router = useRouter()

const tableNumber = route.params.table
const branchId    = route.query.b

function goBack() {
  router.push({ name: 'menu', params: { table: tableNumber }, query: { b: branchId } })
}
</script>
