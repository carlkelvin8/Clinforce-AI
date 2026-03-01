<script setup>
import { RouterLink } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
</script>

<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 font-sans">
      <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
          <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
            <span class="text-2xl font-semibold">✓</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-900 mb-2">
            Email verified
          </h1>
          <p class="text-slate-600 text-sm mb-6">
            Your email address has been successfully verified. You can now sign in to your account.
          </p>
          <RouterLink
            :to="{ name: 'auth.login' }"
            class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
          >
            Go to login
          </RouterLink>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

