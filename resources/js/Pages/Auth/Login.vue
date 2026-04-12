<!-- resources/js/Pages/Auth/Login.vue -->
<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute, RouterLink } from "vue-router";
import api from "@/lib/api";
import AppLayout from "@/Components/AppLayout.vue";
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";
import Message from "primevue/message";
import Swal from "sweetalert2";

const router = useRouter();
  const route = useRoute();
  
  const identifier = ref("");
  const password = ref("");
  const remember = ref(false);
  
  const loading = ref(false);
  const error = ref("");
  
  const showPassword = ref(false);
  const year = new Date().getFullYear();
  
  function isApplicantRole(role) {
    return String(role || "").toLowerCase() === "applicant";
  }
  
  function isStaffRole(role) {
    const r = String(role || "").toLowerCase();
    return r === "admin" || r === "employer" || r === "agency";
  }
  
  function dispatchAuthChanged() {
    window.dispatchEvent(new Event("auth:changed"));
  }
  
  function clearErrorOnType() {
    if (error.value) error.value = "";
  }

  onMounted(() => {
    const q = route.query || {};
    if (q.verified === "1") {
      Swal.fire({
        icon: "success",
        title: "Email verified",
        text: "Your email has been verified. You can now sign in.",
        timer: 2500,
        showConfirmButton: false,
      });
    } else if (q.verification === "invalid") {
      error.value = "This verification link is invalid or has expired. Please request a new one.";
    } else if (q.social === "error") {
      error.value = "Google sign in failed. Please try again or use email/password.";
    } else if (q.social === "no_account") {
      error.value = "No account found with this email. Please register first.";
    } else if (q.registered === "1") {
      Swal.fire({
        icon: "success",
        title: "Registration successful",
        text: "Please check your email to verify your account, then sign in below.",
        timer: 3500,
        showConfirmButton: false,
      });
    }
  });
  
  async function onSubmit() {
    loading.value = true;
    error.value = "";
  
    try {
      const res = await api.post("/auth/login", {
        identifier: identifier.value.trim(),
        password: password.value,
        remember: remember.value,
      });
  
      const token = res?.data?.data?.token || null;
      const user = res?.data?.data?.user || null;
  
      // IMPORTANT: keep both keys in sync (api.js reads both)
      if (token) {
        localStorage.setItem("auth_token", token);
        localStorage.setItem("CLINFORCE_TOKEN", token);
      } else {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("CLINFORCE_TOKEN");
      }
  
      if (user) localStorage.setItem("auth_user", JSON.stringify(user));
      else localStorage.removeItem("auth_user");
  
      dispatchAuthChanged();

      Swal.fire({
        icon: "success",
        title: "Login successful",
        text: "Welcome back.",
        timer: 1500,
        showConfirmButton: false,
      });

      const redirect = route.query.redirect ? String(route.query.redirect) : null;
      if (redirect) return router.push(redirect);
  
      if (user?.role === 'admin') return router.push({ name: 'admin.dashboard' });
      if (isApplicantRole(user?.role)) return router.push({ name: "candidate.dashboard" });
      if (isStaffRole(user?.role)) return router.push({ name: "employer.dashboard" });
  
      return router.push({ name: "candidate.dashboard" });
    } catch (e) {
      error.value = e?.response?.data?.message || "Login failed.";
    } finally {
      loading.value = false;
    }
  }
  
  function goForgot() {
    if (loading.value) return;
    error.value = "";
    Swal.fire({
      title: "Reset password",
      input: "email",
      inputLabel: "Enter the email on your account",
      inputPlaceholder: "you@example.com",
      showCancelButton: true,
      confirmButtonText: "Send reset link",
      showLoaderOnConfirm: true,
      preConfirm: async (value) => {
        const email = String(value || "").trim();
        if (!email) {
          Swal.showValidationMessage("Email is required");
          return;
        }
        try {
          await api.post("/auth/forgot-password", { email });
        } catch (e) {
          const msg = e?.response?.data?.message || "Could not send reset link.";
          Swal.showValidationMessage(msg);
        }
      },
      allowOutsideClick: () => !Swal.isLoading(),
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          icon: "success",
          title: "Check your email",
          text: "If an account with that email exists, a reset link has been sent.",
        });
      }
    });
  }
  
  function loginWithGoogle() {
    if (loading.value) return;
    const apiUrl = import.meta.env.VITE_API_URL || window.location.origin;
    const baseUrl = apiUrl.replace('/api', '');
    window.location.href = `${baseUrl}/auth/google/redirect?source=login`;
  }
  </script>
  
<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen grid lg:grid-cols-2 font-sans">
      <!-- Left Side: Brand & Visuals -->
      <div class="relative hidden lg:block overflow-hidden bg-slate-900">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/90 via-indigo-600/90 to-blue-800/90 z-10"></div>
        <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Medical Team" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50" />
        
        <div class="absolute inset-0 z-20 flex flex-col justify-between p-12 text-white">
          <div class="flex items-center gap-3">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-48 w-auto brightness-0 invert" />
          </div>
          
          <div class="mb-10">
            <h1 class="text-5xl font-bold leading-tight mb-6">Empowering healthcare workforce management.</h1>
            <p class="text-lg text-blue-100 max-w-lg leading-relaxed mb-8">
              Join thousands of healthcare providers and clinicians using our platform to streamline hiring, compliance, and shift management.
            </p>
            
            <div class="flex gap-4">
               <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex-1">
                  <div class="text-3xl font-bold mb-1">98%</div>
                  <div class="text-sm text-blue-100">Fill Rate</div>
               </div>
               <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex-1">
                  <div class="text-3xl font-bold mb-1">24h</div>
                  <div class="text-sm text-blue-100">Avg. Placement</div>
               </div>
               <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/10 flex-1">
                  <div class="text-3xl font-bold mb-1">5k+</div>
                  <div class="text-sm text-blue-100">Clinicians</div>
               </div>
            </div>
          </div>
          
          <div class="text-sm text-blue-200">
            Trusted by leading healthcare institutions nationwide.
          </div>
        </div>
      </div>

      <!-- Right Side: Login Form -->
      <div class="min-h-screen flex items-center justify-center p-6 bg-white dark:bg-slate-900 transition-colors duration-300">
        <div class="w-full max-w-[420px]">
          <!-- Mobile Header -->
          <div class="mb-8 text-center lg:hidden">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-40 w-auto mx-auto mb-4" />
          </div>

          <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-900 mb-2">Welcome back</h2>
            <p class="text-slate-500">Please enter your details to sign in.</p>
          </div>
          
          <form @submit.prevent="onSubmit" class="space-y-5">
            <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
            
            <div class="space-y-1.5">
              <label for="identifier" class="block text-sm font-semibold text-slate-700">Email or Phone</label>
              <InputText id="identifier" v-model="identifier" :disabled="loading" required @input="clearErrorOnType" class="w-full !rounded-lg !py-2.5" />
            </div>
            
            <div class="space-y-1.5">
              <div class="flex justify-between items-center">
                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                <RouterLink :to="{ name: 'auth.forgot-password' }" class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">Forgot password?</RouterLink>
              </div>
              <Password id="password" v-model="password" :disabled="loading" required @input="clearErrorOnType" toggleMask :feedback="false" inputClass="!w-full !rounded-lg !py-2.5" class="w-full" />
            </div>
            
            <div class="flex items-center gap-2">
              <Checkbox v-model="remember" binary inputId="remember" :disabled="loading" />
              <label for="remember" class="cursor-pointer text-sm font-medium text-slate-700">Keep me logged in for 30 days</label>
            </div>
            
            <Button type="submit" :label="loading ? 'Signing in...' : 'Sign in'" :loading="loading" class="w-full !rounded-lg !py-3 !font-bold !text-base shadow-lg shadow-blue-100" />
            
            <div class="relative py-4">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
              </div>
              <div class="relative flex justify-center">
                <span class="bg-white dark:bg-slate-900 px-4 text-sm text-slate-500">Don't have an account?</span>
              </div>
            </div>
            
            <div class="text-center">
              <RouterLink :to="{ name: 'auth.register' }" class="inline-flex items-center justify-center w-full px-4 py-3 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all">
                Create free account
              </RouterLink>
            </div>
          </form>
          
          <div class="mt-6">
            <div class="relative py-2">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
              </div>
              <div class="relative flex justify-center">
                <span class="bg-white dark:bg-slate-900 px-3 text-xs text-slate-400 uppercase tracking-wide">Or continue with</span>
              </div>
            </div>
            <button
              type="button"
              class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-300 transition-colors"
              @click="loginWithGoogle"
            >
              <img
                src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                alt="Google"
                class="h-4 w-4"
              />
              <span>Continue with Google</span>
            </button>
          </div>
          
          <div class="mt-8 text-center text-xs text-slate-400">
            © {{ year }} AI Clinforce Partners. All rights reserved.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
  
