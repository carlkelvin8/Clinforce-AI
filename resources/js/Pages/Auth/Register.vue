<!-- resources/js/Pages/Auth/Register.vue -->
<script setup>
import { ref, computed } from "vue";
import { useRouter, RouterLink } from "vue-router";
import api from "@/lib/api";
import AppLayout from "@/Components/AppLayout.vue";
import Card from "primevue/card";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Select from "primevue/select";
import Checkbox from "primevue/checkbox";
import Button from "primevue/button";
import Message from "primevue/message";
import Swal from "sweetalert2";

const router = useRouter();

const fullName = ref("");
const email = ref("");
const accountType = ref(""); // employer | clinician
const password = ref("");
const passwordConfirm = ref("");
const agree = ref(false);

const loading = ref(false);
const error = ref("");

const year = new Date().getFullYear();

const accountTypeOptions = [
  { label: 'Employer / HR', value: 'employer' },
  { label: 'Clinician / Healthcare professional', value: 'clinician' }
];

const roleForApi = computed(() => {
    if (accountType.value === "employer") return "employer";
    if (accountType.value === "clinician") return "applicant";
    return "";
  });
  
  function clearErrorOnType() {
    if (error.value) error.value = "";
  }
  
  async function onSubmit() {
    error.value = "";
  
    if (!fullName.value.trim()) return (error.value = "Full name is required.");
    if (!email.value.trim()) return (error.value = "Email is required.");
    if (!roleForApi.value) return (error.value = "Please select an account type.");
    if (!password.value || password.value.length < 8) return (error.value = "Password must be at least 8 characters.");
    if (password.value !== passwordConfirm.value) return (error.value = "Passwords do not match.");
    if (!agree.value) return (error.value = "You must agree to the Terms and Privacy Policy.");
  
    loading.value = true;
    try {
      await api.post("/auth/register", {
        role: roleForApi.value,
        email: email.value.trim(),
        phone: null,
        password: password.value,
      });

      Swal.fire({
        icon: "success",
        title: "Registration successful",
        html: "Please check your email to verify your account. You will be redirected to login.",
        timer: 3000,
        showConfirmButton: true,
        confirmButtonText: "Go to login",
      }).then(() => {
        router.push({ name: "auth.login", query: { registered: "1" } });
      });
    } catch (e) {
      const msg = e?.response?.data?.message || "Registration failed.";
      const errs = e?.response?.data?.errors;
      error.value = errs ? `${msg} ${Object.values(errs).flat().join(" ")}` : msg;
    } finally {
      loading.value = false;
    }
  }
  
  function signupWithGoogle() {
    if (loading.value) return;
    window.location.href = "/auth/google/redirect";
  }
  </script>
  
  <template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen grid lg:grid-cols-2 font-sans">
      <!-- Left Side: Brand & Visuals -->
      <div class="relative hidden lg:block overflow-hidden bg-slate-900">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/90 via-blue-600/90 to-indigo-800/90 z-10"></div>
        <img src="https://images.unsplash.com/photo-1551076805-e1869033e561?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="Medical Technology" class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-50" />
        
        <div class="absolute inset-0 z-20 flex flex-col justify-between p-12 text-white">
          <div class="flex items-center gap-3">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-48 w-auto brightness-0 invert" />
          </div>
          
          <div class="mb-10">
            <h1 class="text-5xl font-bold leading-tight mb-6">Join the future of healthcare staffing.</h1>
            <p class="text-lg text-blue-100 max-w-lg leading-relaxed mb-8">
              Create your account today to access thousands of jobs, qualified candidates, and powerful management tools.
            </p>
            
            <div class="grid gap-4">
               <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </div>
                  <div>
                    <div class="font-bold text-lg">Instant Verification</div>
                    <div class="text-blue-200 text-sm">Automated credential checks</div>
                  </div>
               </div>
               <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </div>
                  <div>
                    <div class="font-bold text-lg">Fast Hiring</div>
                    <div class="text-blue-200 text-sm">Reduce time-to-hire by 50%</div>
                  </div>
               </div>
            </div>
          </div>
          
          <div class="text-sm text-blue-200">
            Join 5,000+ clinicians and employers today.
          </div>
        </div>
      </div>

      <!-- Right Side: Register Form -->
      <div class="min-h-screen flex items-center justify-center p-6 bg-white">
        <div class="w-full max-w-[520px]">
          <!-- Mobile Header -->
          <div class="mb-8 text-center lg:hidden">
            <img :src="'/banners/logo.svg'" alt="AI Clinforce Partners" class="h-40 w-auto mx-auto mb-4" />
          </div>

          <div class="mb-8">
             <h2 class="text-3xl font-bold text-slate-900 mb-2">Create your account</h2>
             <p class="text-slate-500">Join as a healthcare employer or clinician.</p>
          </div>
          
          <form @submit.prevent="onSubmit" class="space-y-5">
             <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>
             
             <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                   <label for="fullName" class="block text-sm font-semibold text-slate-700">Full Name</label>
                   <InputText id="fullName" v-model="fullName" :disabled="loading" class="w-full !rounded-lg !py-2.5" @input="clearErrorOnType" />
                </div>
                <div class="space-y-1.5">
                   <label for="email" class="block text-sm font-semibold text-slate-700">Email Address</label>
                   <InputText id="email" v-model="email" type="email" :disabled="loading" class="w-full !rounded-lg !py-2.5" @input="clearErrorOnType" />
                </div>
             </div>

             <div class="space-y-1.5">
                <label for="accountType" class="block text-sm font-semibold text-slate-700">I am a...</label>
                <Select id="accountType" v-model="accountType" :options="accountTypeOptions" optionLabel="label" optionValue="value" placeholder="Select your role" :disabled="loading" class="w-full !rounded-lg" @change="clearErrorOnType" />
             </div>

             <div class="grid md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                   <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                   <Password id="password" v-model="password" :disabled="loading" toggleMask :feedback="false" inputClass="!w-full !rounded-lg !py-2.5" class="w-full" @input="clearErrorOnType" />
                </div>
                <div class="space-y-1.5">
                   <label for="passwordConfirm" class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                   <Password id="passwordConfirm" v-model="passwordConfirm" :disabled="loading" toggleMask :feedback="false" inputClass="!w-full !rounded-lg !py-2.5" class="w-full" @input="clearErrorOnType" />
                </div>
             </div>

             <div class="flex items-start gap-2 pt-2">
                <Checkbox v-model="agree" binary inputId="agree" :disabled="loading" class="mt-0.5" />
                <label for="agree" class="text-sm text-slate-600 leading-snug cursor-pointer">
                   I agree to the <RouterLink :to="{ name: 'terms' }" target="_blank" class="text-blue-600 font-medium hover:underline">Terms of Service</RouterLink> and <RouterLink :to="{ name: 'privacy' }" target="_blank" class="text-blue-600 font-medium hover:underline">Privacy Policy</RouterLink>.
                </label>
             </div>

             <Button type="submit" :label="loading ? 'Creating Account...' : 'Create Account'" :loading="loading" class="w-full !rounded-lg !py-3 !font-bold !text-base shadow-lg shadow-blue-100" />
             
             <div class="relative py-4">
                <div class="absolute inset-0 flex items-center">
                   <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center">
                   <span class="bg-white px-4 text-sm text-slate-500">Already have an account?</span>
                </div>
             </div>
             
             <div class="text-center">
                <RouterLink :to="{ name: 'auth.login' }" class="inline-flex items-center justify-center w-full px-4 py-3 border border-slate-200 rounded-lg text-slate-700 font-semibold hover:bg-slate-50 hover:border-slate-300 transition-all">
                   Log in
                </RouterLink>
             </div>
          </form>
          
          <div class="mt-6">
            <div class="relative py-2">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-slate-200"></div>
              </div>
              <div class="relative flex justify-center">
                <span class="bg-white px-3 text-xs text-slate-400 uppercase tracking-wide">Or sign up with</span>
              </div>
            </div>
            <button
              type="button"
              class="mt-3 w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-colors"
              @click="signupWithGoogle"
            >
              <img
                src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                alt="Google"
                class="h-4 w-4"
              />
              <span>Sign up with Google</span>
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
  
  <style scoped>
/* No custom styles needed */
</style>
  
