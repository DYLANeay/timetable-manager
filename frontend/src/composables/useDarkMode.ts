import { ref, watch } from 'vue'

const isDark = ref(localStorage.getItem('theme') === 'dark')

function apply(dark: boolean) {
  document.documentElement.classList.toggle('dark', dark)
  localStorage.setItem('theme', dark ? 'dark' : 'light')
}

// Apply on load
apply(isDark.value)

watch(isDark, apply)

export function useDarkMode() {
  function toggle() {
    isDark.value = !isDark.value
  }

  return { isDark, toggle }
}
