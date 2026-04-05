<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/Components/AppLayout.vue';

const router = useRouter();
const route = useRoute();

const status = route.query.status;
const isInvalid = status === 'invalid';
const countdown = ref(3);

onMounted(() => {
  if (isInvalid) return;
  const timer = setInterval(() => {
    countdown.value--;
    if (countdown.value <= 0) {
      clearInterval(timer);
      router.push({ name: 'auth.login' });
    }
  }, 1000);
});
</script>

<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 font-sans">
      <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">

          <!-- Invalid link -->
          <template v-if="isInvalid">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50 text-red-500">
              <span class="text-2xl">✕</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Invalid link</h1>
            <p class="text-slate-600 text-sm mb-6">
              This verification link is invalid or has expired. Please request a new one.
            </p>
            <RouterLink
              :to="{ name: 'auth.login' }"
              class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
            >
              Go to login
            </RouterLink>
          </template>

          <!-- Success -->
          <template v-else>
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
              <span class="text-2xl font-semibold">✓</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Email verified</h1>
            <p class="text-slate-600 text-sm mb-6">
              Your email has been verified. Redirecting to login in {{ countdown }}s...
            </p>
            <RouterLink
              :to="{ name: 'auth.login' }"
              class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
            >
              Go to login now
            </RouterLink>
          </template>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
