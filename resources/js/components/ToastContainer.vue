<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-2xl shadow-2xl border max-w-sm w-80 cursor-pointer select-none"
          :class="t.class"
          @click="remove(t.id)"
        >
          <span class="text-xl shrink-0 mt-0.5">{{ t.icon }}</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold leading-snug">{{ t.title }}</p>
            <p v-if="t.message" class="text-xs opacity-80 mt-0.5">{{ t.message }}</p>
          </div>
          <button class="text-xs opacity-50 hover:opacity-100 shrink-0 mt-0.5">✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

function add({ icon = '🔔', title, message = '', type = 'info', duration = 5000 }) {
  const id = ++nextId
  const classes = {
    info:    'bg-white border-gray-200 text-gray-800',
    success: 'bg-green-50 border-green-200 text-green-900',
    warning: 'bg-amber-50 border-amber-300 text-amber-900',
    order:   'bg-amber-500 border-amber-600 text-white',
  }
  toasts.value.push({ id, icon, title, message, class: classes[type] ?? classes.info })
  if (duration > 0) setTimeout(() => remove(id), duration)
}

function remove(id) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

defineExpose({ add })
</script>

<style scoped>
.toast-enter-active  { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-leave-active  { transition: all 0.25s ease-in; }
.toast-enter-from    { opacity: 0; transform: translateX(100%) scale(0.9); }
.toast-leave-to      { opacity: 0; transform: translateX(100%) scale(0.9); }
.toast-move          { transition: transform 0.3s ease; }
</style>
