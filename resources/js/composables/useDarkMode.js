import { ref, watch } from 'vue';

const isDark = ref(false);
const STORAGE_KEY = 'clinforce-dark-mode';

// Set up system theme listener once at module level
if (typeof window !== 'undefined') {
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (localStorage.getItem(STORAGE_KEY) === null) {
      isDark.value = e.matches;
    }
  });
}

export function useDarkMode() {
  const applyDarkMode = () => {
    if (isDark.value) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  const initDarkMode = () => {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored !== null) {
      isDark.value = stored === 'true';
    } else {
      isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyDarkMode();
  };

  const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    localStorage.setItem(STORAGE_KEY, isDark.value.toString());
    applyDarkMode();
  };

  const setDarkMode = (value) => {
    isDark.value = value;
    localStorage.setItem(STORAGE_KEY, value.toString());
    applyDarkMode();
  };

  watch(isDark, applyDarkMode);

  return {
    isDark,
    toggleDarkMode,
    setDarkMode,
    initDarkMode,
  };
}
