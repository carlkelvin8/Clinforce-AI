<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4">
    <div class="text-center max-w-md">
      <div class="text-7xl font-black text-slate-200 mb-4">404</div>
      <h1 class="text-xl font-bold text-slate-800 mb-2">Page not found</h1>
      <p class="text-slate-500 text-sm mb-6">
        The page <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs font-mono">{{ currentPath }}</code> doesn't exist.
      </p>
      <div class="flex gap-3 justify-center">
        <button @click="goBack"
          class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors">
          ← Go back
        </button>
        <button @click="goHome"
          class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
          Go home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const currentPath = computed(() => route.fullPath);

function goBack() {
  if (window.history.length > 1) {
    router.back();
  } else {
    goHome();
  }
}

function goHome() {
  const user = (() => { try { return JSON.parse(localStorage.getItem('auth_user') || '{}'); } catch { return {}; } })();
  if (!user?.role) return router.push('/');
  if (user.role === 'admin') return router.push('/admin');
  if (user.role === 'applicant') return router.push('/candidate/dashboard');
  return router.push('/employer/dashboard');
}
</script>
