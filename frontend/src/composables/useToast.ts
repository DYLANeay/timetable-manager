import { ref } from 'vue'

export interface AppToast {
  id: number
  message: string
  notificationType: string
}

// Module-level singleton — shared across all component instances and the store
const toasts = ref<AppToast[]>([])
let nextId = 0

export function addToast(message: string, notificationType = '') {
  const id = ++nextId
  toasts.value.push({ id, message, notificationType })
  setTimeout(() => removeToast(id), 5000)
}

export function removeToast(id: number) {
  const i = toasts.value.findIndex((t) => t.id === id)
  if (i !== -1) toasts.value.splice(i, 1)
}

export function useToasts() {
  return { toasts, removeToast }
}
