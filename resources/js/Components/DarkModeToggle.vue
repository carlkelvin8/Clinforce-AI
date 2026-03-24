<template>
  <button
    @click="toggleDarkMode"
    class="dark-mode-toggle"
    :class="{ 'dark': isDark }"
    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    aria-label="Toggle dark mode"
  >
    <!-- Sun Icon (Light Mode) -->
    <svg
      v-if="!isDark"
      xmlns="http://www.w3.org/2000/svg"
      class="icon"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <circle cx="12" cy="12" r="5"></circle>
      <line x1="12" y1="1" x2="12" y2="3"></line>
      <line x1="12" y1="21" x2="12" y2="23"></line>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
      <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
      <line x1="1" y1="12" x2="3" y2="12"></line>
      <line x1="21" y1="12" x2="23" y2="12"></line>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
      <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
    </svg>

    <!-- Moon Icon (Dark Mode) -->
    <svg
      v-else
      xmlns="http://www.w3.org/2000/svg"
      class="icon"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>
  </button>
</template>

<script setup>
import { useDarkMode } from '../composables/useDarkMode';

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<style scoped>
.dark-mode-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #64748b;
}

.dark-mode-toggle:hover {
  background: rgba(100, 116, 139, 0.1);
  color: #475569;
}

.dark-mode-toggle.dark {
  color: #fbbf24;
}

.dark-mode-toggle.dark:hover {
  background: rgba(251, 191, 36, 0.1);
  color: #f59e0b;
}

.icon {
  width: 20px;
  height: 20px;
  transition: transform 0.3s ease;
}

.dark-mode-toggle:hover .icon {
  transform: rotate(15deg) scale(1.1);
}

.dark-mode-toggle.dark:hover .icon {
  transform: rotate(-15deg) scale(1.1);
}

/* Responsive */
@media (max-width: 768px) {
  .dark-mode-toggle {
    width: 36px;
    height: 36px;
  }
  
  .icon {
    width: 18px;
    height: 18px;
  }
}
</style>
