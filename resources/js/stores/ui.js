import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const shiftModalOpen = ref(false)

  function openShiftModal() {
    shiftModalOpen.value = true
  }

  function closeShiftModal() {
    shiftModalOpen.value = false
  }

  return { shiftModalOpen, openShiftModal, closeShiftModal }
})
