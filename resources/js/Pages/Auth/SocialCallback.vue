<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";

const router = useRouter();
const route = useRoute();

const loading = ref(true);
const error = ref("");

function saveAuth(token, user) {
  if (token) {
    localStorage.setItem("auth_token", token);
    localStorage.setItem("CLINFORCE_TOKEN", token);
  } else {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("CLINFORCE_TOKEN");
  }

  if (user) localStorage.setItem("auth_user", JSON.stringify(user));
  else localStorage.removeItem("auth_user");

  window.dispatchEvent(new Event("auth:changed"));
}

onMounted(() => {
  // Check for error from redirect
  if (route.query.social === 'error') {
    error.value = "Google sign in was cancelled or failed. Please try again.";
    loading.value = false;
    return;
  }

  const payloadRaw = route.query.payload;
  if (!payloadRaw) {
    error.value = "Missing social login data. Please try again.";
    loading.value = false;
    return;
  }

  try {
    const decoded = JSON.parse(atob(String(payloadRaw)));
    const token = decoded?.token || null;
    const user = decoded?.user || null;

    if (!token || !user) {
      throw new Error("Invalid payload");
    }

    saveAuth(token, user);

    const redirect = route.query.redirect ? String(route.query.redirect) : null;

    if (redirect) {
      router.replace(redirect);
      return;
    }

    if (String(user.role || "").toLowerCase() === "applicant") {
      router.replace({ name: "candidate.dashboard" });
    } else {
      router.replace({ name: "employer.dashboard" });
    }
  } catch (e) {
    console.error(e);
    error.value = "Could not complete Google sign in. Please try again.";
    loading.value = false;
  }
});
</script>

<template>
  <AppLayout :guestFull="true">
    <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 font-sans">
      <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
          <div v-if="loading">
            <div class="mx-auto mb-4 h-10 w-10 rounded-full border-2 border-slate-200 border-t-slate-900 animate-spin"></div>
            <h1 class="text-lg font-semibold text-slate-900 mb-1">
              Finishing sign in…
            </h1>
            <p class="text-slate-600 text-sm">
              Please wait while we complete your Google sign in.
            </p>
          </div>
          <div v-else>
            <h1 class="text-lg font-semibold text-slate-900 mb-2">
              Google sign in failed
            </h1>
            <p class="text-slate-600 text-sm mb-4">
              {{ error }}
            </p>
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors"
              @click="() => router.replace({ name: 'auth.login' })"
            >
              Back to login
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

