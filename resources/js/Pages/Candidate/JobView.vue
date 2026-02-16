<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-50 font-sans">
      <!-- Hero Header -->
      <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <Button label="Back to Jobs" icon="pi pi-arrow-left" text class="!p-0 !text-white hover:!text-white mb-4" @click="goBack" />
              <h1 class="text-3xl font-bold text-slate-900">{{ job.title || "Loading..." }}</h1>
              <div class="flex flex-wrap gap-3 mt-3 text-sm text-slate-600">
                <span v-if="job.city || job.country_code" class="flex items-center gap-1">
                  <i class="pi pi-map-marker text-slate-400"></i>
                  {{ formatLocation(job) }}
                </span>
                <span v-if="job.employment_type" class="flex items-center gap-1">
                  <i class="pi pi-briefcase text-slate-400"></i>
                  {{ prettyEnum(job.employment_type) }}
                </span>
                <span v-if="job.work_mode" class="flex items-center gap-1">
                  <i class="pi pi-building text-slate-400"></i>
                  {{ prettyEnum(job.work_mode) }}
                </span>
                <span class="flex items-center gap-1 text-slate-400">
                  <i class="pi pi-clock"></i>
                  Posted {{ fmt(job.published_at || job.created_at) }}
                </span>
              </div>
            </div>
            <div class="flex gap-3">
              <Button label="Refresh" icon="pi pi-refresh" outlined class="!border-slate-300 !text-slate-600" @click="fetchJob" :loading="loading" />
              <Button v-if="!loading && !error" label="Apply Now" icon="pi pi-send" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" @click="openApply = true" />
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Loading State -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-slate-500">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-600 mb-4"></i>
          <div class="text-lg">Loading job details...</div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center text-red-700">
          <i class="pi pi-exclamation-triangle text-3xl mb-2 block"></i>
          {{ error }}
          <div class="mt-4">
            <Button label="Try Again" outlined severity="danger" @click="fetchJob" />
          </div>
        </div>

        <!-- Job Content -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm">
              <h2 class="text-xl font-bold text-slate-900 mb-6">About the Role</h2>
              <div class="prose prose-slate max-w-none text-slate-700 whitespace-pre-wrap leading-relaxed">
                {{ job.description || "No description provided." }}
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
              <h3 class="text-lg font-bold text-slate-900 mb-4">Job Summary</h3>
              <div class="space-y-4">
                <div class="flex justify-between py-2 border-b border-slate-100">
                  <span class="text-slate-500">Status</span>
                  <span class="font-medium capitalize px-2 py-0.5 rounded bg-green-100 text-green-700 text-sm">{{ prettyEnum(job.status || "active") }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                  <span class="text-slate-500">Location</span>
                  <span class="font-medium text-right">{{ formatLocation(job) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                  <span class="text-slate-500">Type</span>
                  <span class="font-medium">{{ prettyEnum(job.employment_type) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                  <span class="text-slate-500">Work Mode</span>
                  <span class="font-medium">{{ prettyEnum(job.work_mode) }}</span>
                </div>
                <div class="pt-4">
                  <Button label="Apply for this Job" class="w-full !bg-blue-600 !border-blue-600 hover:!bg-blue-700" @click="openApply = true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Apply modal -->
        <Dialog v-model:visible="openApply" modal header="Apply for this position" :style="{ width: '500px' }" class="p-0 overflow-hidden">
          <template #header>
            <div class="flex flex-col gap-1">
              <span class="text-xl font-bold text-slate-900">Apply Now</span>
              <span class="text-sm text-slate-500">Applying for <span class="font-medium text-slate-900">{{ job.title }}</span></span>
            </div>
          </template>

          <div class="pt-2">
            <transition
              enter-active-class="transition ease-out duration-300"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
              leave-active-class="transition ease-in duration-200"
              leave-from-class="opacity-100 translate-y-0"
              leave-to-class="opacity-0 -translate-y-2"
            >
              <div v-if="applyError" class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ applyError }}
              </div>
            </transition>

            <transition
              enter-active-class="transition ease-out duration-300"
              enter-from-class="opacity-0 -translate-y-2"
              enter-to-class="opacity-100 translate-y-0"
            >
              <div v-if="applyOk" class="mb-4 p-4 rounded bg-green-50 border border-green-200 text-green-700 flex flex-col items-center text-center">
                <i class="pi pi-check-circle text-4xl mb-2 text-green-600"></i>
                <div class="font-bold text-lg">Application Sent!</div>
                <div class="text-sm mt-1">{{ applyOk }}</div>
                <Button label="Close" class="mt-4 p-button-sm !bg-green-600 !border-green-600" @click="openApply = false" />
              </div>
            </transition>

            <div v-if="!applyOk" class="flex flex-col gap-4">
              <div class="flex flex-col gap-2">
                <label for="coverLetter" class="font-medium text-slate-700">Cover Letter <span class="text-slate-400 font-normal">(Optional)</span></label>
                <Textarea id="coverLetter" v-model="coverLetter" rows="6" placeholder="Introduce yourself and explain why you're a good fit..." class="w-full !border-slate-300 focus:!border-blue-500" autoResize />
              </div>
            </div>
          </div>

          <template #footer>
            <div v-if="!applyOk" class="flex justify-end gap-2">
              <Button label="Cancel" text class="!text-slate-600 hover:!bg-slate-50" @click="openApply = false" :disabled="applyLoading" />
              <Button :label="applyLoading ? 'Sending...' : 'Submit Application'" icon="pi pi-send" class="!bg-blue-600 !border-blue-600" @click="applyNow" :loading="applyLoading" />
            </div>
          </template>
        </Dialog>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';

const router = useRouter();

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const loading = ref(false);
const error = ref("");
const job = ref({});

const openApply = ref(false);
const coverLetter = ref("");
const applyLoading = ref(false);
const applyError = ref("");
const applyOk = ref("");

function goBack() {
  router.back();
}

function unwrapPayload(resData) {
  return resData?.data ?? resData;
}

function extractMessage(resData, fallback) {
  const p = unwrapPayload(resData);
  return p?.message || resData?.message || fallback;
}

function fmt(v) {
  if (!v) return "—";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatLocation(j) {
  const parts = [j?.city, j?.country_code].filter(Boolean);
  return parts.length ? parts.join(", ") : "—";
}

function prettyEnum(v) {
  const t = String(v ?? "").trim();
  if (!t) return "—";
  return t
    .split("_")
    .map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w))
    .join(" ");
}

async function fetchJob() {
  loading.value = true;
  error.value = "";

  try {
    const id = encodeURIComponent(String(props.id));
    const res = await api.get(`/public/jobs/${id}`);
    const p = unwrapPayload(res.data);
    job.value = p?.data ?? p ?? {};
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Request failed";
  } finally {
    loading.value = false;
  }
}

async function applyNow() {
  applyLoading.value = true;
  applyError.value = "";
  applyOk.value = "";

  const id = encodeURIComponent(String(props.id));
  const payload = {};
  if (coverLetter.value.trim()) payload.cover_letter = coverLetter.value.trim();

  // Try multiple endpoints to be robust
  const candidates = [
    { method: "post", url: `/jobs/${id}/apply`, data: payload },
    { method: "post", url: `/jobs/${id}/applications`, data: payload },
    { method: "post", url: `/applications`, data: { job_id: props.id, ...payload } },
  ];

  try {
    let res = null;
    let lastErr = null;

    for (const c of candidates) {
      try {
        // eslint-disable-next-line no-await-in-loop
        res = await api[c.method](c.url, c.data);
        lastErr = null;
        break;
      } catch (err) {
        const status = err?.response?.status;
        if (status === 404 || status === 405) {
          lastErr = err;
          continue;
        }
        throw err;
      }
    }

    if (!res) throw lastErr || new Error("Apply endpoint not reachable");

    const msg = extractMessage(res.data, "Application submitted successfully!");
    applyOk.value = msg;
    coverLetter.value = "";
    
    // Auto close after success is handled by UI showing success message
  } catch (e) {
    applyError.value = e?.response?.data?.message || e?.message || "Submit failed";
  } finally {
    applyLoading.value = false;
  }
}

onMounted(async () => {
  await fetchJob();
});
</script>

<style scoped>
</style>
