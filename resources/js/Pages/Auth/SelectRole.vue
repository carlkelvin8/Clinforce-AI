<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import Button from "primevue/button";
import Message from "primevue/message";
import api, { setToken } from "@/lib/api";

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const error = ref("");
const dataParam = ref(null);
const selectedRole = ref("");

const year = new Date().getFullYear();

const roleOptions = [
  {
    value: 'employer',
    title: 'Employer / HR',
    description: 'I want to hire healthcare professionals',
    icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
  },
  {
    value: 'applicant',
    title: 'Clinician / Healthcare Professional',
    description: 'I am looking for healthcare jobs',
    icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
  }
];

onMounted(() => {
  const param = route.query.data;
  if (!param) {
    error.value = "Missing registration data. Please try signing in again.";
    return;
  }
  dataParam.value = param;
});

async function continueWithRole() {
  if (!selectedRole.value) {
    error.value = "Please select your account type.";
    return;
  }
  if (!dataParam.value) {
    error.value = "Missing registration data. Please try signing in again.";
    return;
  }

  loading.value = true;
  error.value = "";

  try {
    const res = await api.post('/auth/google/complete', {
      data: dataParam.value,
      role: selectedRole.value,
    });

    const { token, user } = res.data.data;
    setToken(token);
    localStorage.setItem('auth_user', JSON.stringify(user));
    window.dispatchEvent(new Event('auth:changed'));

    if (user.role === 'employer' || user.role === 'agency') {
      router.push({ name: 'employer.dashboard' });
    } else {
      router.push({ name: 'candidate.dashboard' });
    }
  } catch (err) {
    error.value = err?.response?.data?.message || "Registration failed. Please try again.";
  } finally {
    loading.value = false;
  }
}

function goBack() {
  router.push({ name: 'auth.login' });
}
</script>

<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-slate-50 via-white to-blue-50 font-sans">
      <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
          <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-32 w-auto mx-auto mb-4" />
          <h1 class="text-3xl font-bold text-slate-900 mb-2">Choose your account type</h1>
          <p class="text-slate-600">Select how you'll be using AI Clinforce Partners</p>
        </div>

        <Message v-if="error" severity="error" :closable="false" class="mb-6">{{ error }}</Message>

        <!-- Role Selection Cards -->
        <div class="grid md:grid-cols-2 gap-4 mb-6">
          <button
            v-for="role in roleOptions"
            :key="role.value"
            type="button"
            @click="selectedRole = role.value"
            :class="[
              'relative p-6 rounded-2xl border-2 transition-all duration-200 text-left',
              selectedRole === role.value
                ? 'border-blue-600 bg-blue-50 shadow-lg shadow-blue-100'
                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'
            ]"
          >
            <!-- Selection Indicator -->
            <div
              :class="[
                'absolute top-4 right-4 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all',
                selectedRole === role.value
                  ? 'border-blue-600 bg-blue-600'
                  : 'border-slate-300 bg-white'
              ]"
            >
              <svg
                v-if="selectedRole === role.value"
                class="w-4 h-4 text-white"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>

            <!-- Icon -->
            <div
              :class="[
                'w-12 h-12 rounded-xl flex items-center justify-center mb-4',
                selectedRole === role.value
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-100 text-slate-600'
              ]"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="role.icon"></path>
              </svg>
            </div>

            <!-- Content -->
            <h3 class="text-lg font-bold text-slate-900 mb-2">{{ role.title }}</h3>
            <p class="text-sm text-slate-600">{{ role.description }}</p>
          </button>
        </div>

        <!-- Actions -->
        <div class="space-y-3">
          <Button
            type="button"
            label="Continue"
            :loading="loading"
            :disabled="!selectedRole || loading"
            @click="continueWithRole"
            class="w-full !rounded-xl !py-3 !font-bold !text-base shadow-lg shadow-blue-100"
          />
          
          <button
            type="button"
            @click="goBack"
            :disabled="loading"
            class="w-full px-4 py-3 border border-slate-200 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:border-slate-300 transition-all disabled:opacity-50"
          >
            Back to login
          </button>
        </div>

        <div class="mt-8 text-center text-xs text-slate-400">
          © {{ year }} AI Clinforce Partners. All rights reserved.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
