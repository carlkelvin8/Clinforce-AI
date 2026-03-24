<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen grid lg:grid-cols-2 font-sans">
      <!-- Left Side: Brand & Visuals -->
      <div class="relative hidden lg:block overflow-hidden bg-slate-900">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/90 via-blue-600/90 to-indigo-800/90 z-10"></div>
        <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Medical Technology" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50" />
        
        <div class="absolute inset-0 z-20 flex flex-col justify-between p-12 text-white">
          <div class="flex items-center gap-3">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-24 w-auto brightness-0 invert" />
          </div>
          
          <div class="mb-10">
            <h1 class="text-5xl font-bold leading-tight mb-6">Reset your password</h1>
            <p class="text-lg text-blue-100 max-w-lg leading-relaxed">
              Enter your email address and we'll send you a link to reset your password.
            </p>
          </div>
          
          <div class="text-sm text-blue-200">
            Secure password recovery for your account.
          </div>
        </div>
      </div>

      <!-- Right Side: Forgot Password Form -->
      <div class="min-h-screen flex items-center justify-center p-6 bg-white">
        <div class="w-full max-w-[520px]">
          <!-- Mobile Header -->
          <div class="mb-8 text-center lg:hidden">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-28 w-auto mx-auto mb-4" />
          </div>

          <div class="mb-8">
             <h2 class="text-3xl font-bold text-slate-900 mb-2">Forgot password?</h2>
             <p class="text-slate-500">No worries, we'll send you reset instructions.</p>
          </div>
          
          <form @submit.prevent="onSubmit" class="space-y-5">
             <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
             <Message v-if="success" severity="success" :closable="false" class="mb-4">{{ success }}</Message>
             
             <div class="space-y-1.5">
                <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                <InputText 
                  id="email" 
                  v-model="email" 
                  type="email" 
                  placeholder="you@example.com" 
                  :disabled="loading || !!success" 
                  class="w-full !rounded-lg !py-2.5" 
                  @input="clearError" 
                />
             </div>

             <Button 
               type="submit" 
               :label="loading ? 'Sending...' : 'Send reset link'" 
               :loading="loading" 
               :disabled="!!success"
               class="w-full !rounded-lg !py-3 !font-bold !text-base shadow-lg shadow-blue-100" 
             />
             
             <div class="text-center pt-4">
                <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 font-medium">
                   <i class="pi pi-arrow-left"></i>
                   <span>Back to login</span>
                </RouterLink>
             </div>
          </form>
          
          <div class="mt-8 text-center text-xs text-slate-400">
            © {{ year }} AI Clinforce Partners. All rights reserved.
          </div>
       </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { RouterLink } from "vue-router";
import axios from "axios";
import AppLayout from "@/Components/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Message from "primevue/message";

const email = ref("");
const loading = ref(false);
const error = ref("");
const success = ref("");
const year = new Date().getFullYear();

function clearError() {
  if (error.value) error.value = "";
}

async function onSubmit() {
  error.value = "";
  success.value = "";

  if (!email.value.trim()) {
    error.value = "Email is required.";
    return;
  }

  loading.value = true;
  try {
    await axios.post("/api/auth/forgot-password", {
      email: email.value.trim(),
    });

    success.value = "Password reset link sent! Please check your email.";
  } catch (e) {
    const msg = e?.response?.data?.message || "Failed to send reset link.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* No custom styles needed */
</style>
