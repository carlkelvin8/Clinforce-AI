<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen grid lg:grid-cols-2 font-sans">
      <!-- Left Side: Brand & Visuals -->
      <div class="relative hidden lg:block overflow-hidden bg-slate-900">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/90 via-blue-600/90 to-indigo-800/90 z-10"></div>
        <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Medical Technology" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50" />
        
        <div class="absolute inset-0 z-20 flex flex-col justify-between p-12 text-white">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm grid place-content-center border border-white/10 shadow-lg">
                <span class="font-bold">AC</span>
            </div>
            <div class="text-lg font-bold tracking-tight">AI Clinforce Partners</div>
          </div>
          
          <div class="mb-10">
            <h1 class="text-5xl font-bold leading-tight mb-6">Set new password</h1>
            <p class="text-lg text-blue-100 max-w-lg leading-relaxed">
              Your new password must be different from previously used passwords.
            </p>
          </div>
          
          <div class="text-sm text-blue-200">
            Secure password recovery for your account.
          </div>
        </div>
      </div>

      <!-- Right Side: Reset Password Form -->
      <div class="min-h-screen flex items-center justify-center p-6 bg-white">
        <div class="w-full max-w-[520px]">
          <!-- Mobile Header -->
          <div class="mb-8 text-center lg:hidden">
            <div class="inline-flex items-center justify-center bg-blue-600 text-white rounded-xl w-12 h-12 mb-4 shadow-lg shadow-blue-200">
                <span class="font-bold text-lg">AC</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">AI Clinforce Partners</h2>
          </div>

          <div class="mb-8">
             <h2 class="text-3xl font-bold text-slate-900 mb-2">Set new password</h2>
             <p class="text-slate-500">Must be at least 8 characters.</p>
          </div>
          
          <form @submit.prevent="onSubmit" class="space-y-5">
             <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
             
             <div class="space-y-1.5">
                <label for="password" class="block text-sm font-semibold text-slate-700">New Password</label>
                <Password 
                  id="password" 
                  v-model="password" 
                  placeholder="••••••••" 
                  :disabled="loading" 
                  toggleMask 
                  :feedback="true"
                  inputClass="!w-full !rounded-lg !py-2.5" 
                  class="w-full" 
                  @input="clearError" 
                />
             </div>

             <div class="space-y-1.5">
                <label for="passwordConfirm" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                <Password 
                  id="passwordConfirm" 
                  v-model="passwordConfirm" 
                  placeholder="••••••••" 
                  :disabled="loading" 
                  toggleMask 
                  :feedback="false"
                  inputClass="!w-full !rounded-lg !py-2.5" 
                  class="w-full" 
                  @input="clearError" 
                />
             </div>

             <Button 
               type="submit" 
               :label="loading ? 'Resetting...' : 'Reset password'" 
               :loading="loading" 
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
import { ref, onMounted } from "vue";
import { useRouter, useRoute, RouterLink } from "vue-router";
import axios from "axios";
import AppLayout from "@/Components/AppLayout.vue";
import Password from "primevue/password";
import Button from "primevue/button";
import Message from "primevue/message";
import Swal from "sweetalert2";

const router = useRouter();
const route = useRoute();

const password = ref("");
const passwordConfirm = ref("");
const loading = ref(false);
const error = ref("");
const year = new Date().getFullYear();

const token = ref("");
const email = ref("");

onMounted(() => {
  token.value = route.params.token || route.query.token || "";
  email.value = route.query.email || "";
  
  if (!token.value) {
    error.value = "Invalid or missing reset token.";
  }
});

function clearError() {
  if (error.value) error.value = "";
}

async function onSubmit() {
  error.value = "";

  if (!token.value) {
    error.value = "Invalid or missing reset token.";
    return;
  }

  if (!password.value || password.value.length < 8) {
    error.value = "Password must be at least 8 characters.";
    return;
  }

  if (password.value !== passwordConfirm.value) {
    error.value = "Passwords do not match.";
    return;
  }

  loading.value = true;
  try {
    await axios.post("/api/auth/reset-password", {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirm.value,
    });

    await Swal.fire({
      icon: "success",
      title: "Password reset successful",
      text: "You can now login with your new password.",
      timer: 2000,
      showConfirmButton: false,
    });

    router.push({ name: "auth.login" });
  } catch (e) {
    const msg = e?.response?.data?.message || "Failed to reset password.";
    error.value = msg;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* No custom styles needed */
</style>
