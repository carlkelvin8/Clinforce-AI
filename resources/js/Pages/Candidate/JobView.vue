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
                <span v-if="job.salary_min || job.salary_max" class="flex items-center gap-1 font-semibold text-green-600">
                  <i class="pi pi-dollar text-green-500"></i>
                  {{ formatSalary(job) }}
                </span>
                <span class="flex items-center gap-1 text-slate-400">
                  <i class="pi pi-clock"></i>
                  Posted {{ fmt(job.published_at || job.created_at) }}
                </span>
              </div>
            </div>
            <div class="flex gap-3">
              <Button label="Refresh" icon="pi pi-refresh" outlined class="!border-slate-300 !text-slate-600" @click="fetchJob" :loading="loading" />
              <Button
                :icon="saved ? 'pi pi-bookmark-fill' : 'pi pi-bookmark'"
                :label="saved ? 'Saved' : 'Save Job'"
                :severity="saved ? 'success' : 'secondary'"
                outlined
                @click="toggleSave"
                :loading="saveLoading"
              />
              <Button 
                v-if="!loading && !error && hasApplied" 
                label="Applied" 
                icon="pi pi-check" 
                severity="success"
                class="!bg-green-600 !border-green-600"
                disabled
              />
              <Button 
                v-else-if="!loading && !error" 
                label="Apply Now" 
                icon="pi pi-send" 
                class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" 
                @click="applyNow" 
                :loading="applyLoading" 
                :disabled="applyLoading" 
              />
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div v-if="applyError" class="mb-6 p-4 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
          {{ applyError }}
        </div>
        <div v-if="applyOk" class="mb-6 p-4 rounded bg-green-50 border border-green-200 text-green-700 text-sm">
          {{ applyOk }}
        </div>
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
                <div v-if="job.salary_min || job.salary_max" class="flex justify-between py-2 border-b border-slate-100">
                  <span class="text-slate-500">Salary</span>
                  <span class="font-medium">{{ formatSalary(job) }}</span>
                </div>
                <div class="pt-4">
                  <div class="mb-3">
                    <Button 
                      v-if="hasApplied"
                      label="✓ Applied" 
                      icon="pi pi-check"
                      severity="success"
                      class="w-full !bg-green-600 !border-green-600" 
                      disabled
                    />
                    <Button 
                      v-else
                      label="Apply for this Job" 
                      class="w-full !bg-blue-600 !border-blue-600 hover:!bg-blue-700" 
                      @click="applyNow" 
                      :loading="applyLoading" 
                      :disabled="applyLoading" 
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

import Button from 'primevue/button';

const router = useRouter();

const props = defineProps({
  id: { type: [String, Number], required: true },
});

const loading = ref(false);
const error = ref("");
const job = ref({});

// SEO meta tags
watch(job, (j) => {
  if (!j?.title) return
  document.title = `${j.title} — ClinForce`
  let desc = document.querySelector('meta[name="description"]')
  if (!desc) { desc = document.createElement('meta'); desc.name = 'description'; document.head.appendChild(desc) }
  desc.content = j.description ? j.description.slice(0, 160) : `Apply for ${j.title} at ClinForce`
  let ogTitle = document.querySelector('meta[property="og:title"]')
  if (!ogTitle) { ogTitle = document.createElement('meta'); ogTitle.setAttribute('property', 'og:title'); document.head.appendChild(ogTitle) }
  ogTitle.content = j.title
  let ogDesc = document.querySelector('meta[property="og:description"]')
  if (!ogDesc) { ogDesc = document.createElement('meta'); ogDesc.setAttribute('property', 'og:description'); document.head.appendChild(ogDesc) }
  ogDesc.content = desc.content
}, { immediate: false })

const applyLoading = ref(false);
const applyError = ref("");
const applyOk = ref("");
const resumeFile = ref(null);
const coverLetter = ref("");
const existingDocs = ref([]);
let triedAutoAttach = false;

const saved = ref(false);
const saveLoading = ref(false);
const hasApplied = ref(false);

async function toggleSave() {
  saveLoading.value = true;
  try {
    if (saved.value) {
      await api.delete(`/jobs/${props.id}/save`);
      saved.value = false;
    } else {
      await api.post(`/jobs/${props.id}/save`);
      saved.value = true;
    }
  } catch (e) {
    // silently ignore
  } finally {
    saveLoading.value = false;
  }
}

async function checkSaved() {
  try {
    const res = await api.get('/saved-jobs');
    const list = res.data?.data || [];
    saved.value = list.some(j => String(j?.id) === String(props.id));
  } catch {}
}

async function checkIfApplied() {
  try {
    const res = await api.get('/applications', { params: { scope: 'mine', job_id: props.id } });
    const p = unwrapPayload(res.data);
    const apps = Array.isArray(p?.data) ? p.data : Array.isArray(p) ? p : [];
    // Check if there's an active application (not withdrawn or rejected)
    hasApplied.value = apps.some(app => 
      String(app?.job_id) === String(props.id) && 
      !['withdrawn', 'rejected'].includes(String(app?.status || '').toLowerCase())
    );
  } catch {
    hasApplied.value = false;
  }
}

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

function formatSalary(j) {
  const min = j?.salary_min;
  const max = j?.salary_max;
  const currency = j?.salary_currency || 'USD';
  
  if (!min && !max) return "—";
  
  const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };
  
  if (min && max) {
    return `${formatAmount(min)} - ${formatAmount(max)}`;
  } else if (min) {
    return `From ${formatAmount(min)}`;
  } else {
    return `Up to ${formatAmount(max)}`;
  }
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
  await autoAttachResumeIfAvailable();

  const id = encodeURIComponent(String(props.id));
  const fd = new FormData();
  if (resumeFile.value) fd.append("resume", resumeFile.value);
  if (coverLetter.value.trim()) fd.append("cover_letter", coverLetter.value.trim());
  const candidates = [
    { method: "post", url: `/api/jobs/${id}/apply`, data: fd },
    { method: "post", url: `/jobs/${id}/apply`, data: fd },
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
    resumeFile.value = null;
    triedAutoAttach = false;
    hasApplied.value = true; // Update the applied status
    
    // Auto close after success is handled by UI showing success message
  } catch (e) {
    const payload = e?.response?.data;
    const errs = payload?.errors;
    let preferred = payload?.message || e?.message || "Submit failed";
    if (errs && typeof errs === 'object') {
      if (Array.isArray(errs.resume) && errs.resume[0]) {
        preferred = errs.resume[0];
      }
    }
    applyError.value = preferred;
  } finally {
    applyLoading.value = false;
  }
}

function isAllowedResume(d) {
  const mime = (d?.mime_type || '').toLowerCase();
  return ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(mime);
}

async function fetchAsBlob(url, mime) {
  const r = await fetch(url, { credentials: 'same-origin' });
  if (!r.ok) throw new Error('Failed to fetch document');
  const b = await r.blob();
  if (mime && b.type !== mime) {
    // keep original blob; server validates anyway
  }
  return b;
}

async function loadUserDocuments() {
  try {
    const res = await api.get('/api/documents');
    const p = unwrapPayload(res.data);
    const docs = Array.isArray(p?.data) ? p.data : Array.isArray(p) ? p : [];
    existingDocs.value = docs.filter(d => d?.status === 'active' && d?.file_url);
  } catch (e) {
    existingDocs.value = [];
  }
}

async function autoAttachResumeIfAvailable() {
  if (triedAutoAttach) return;
  triedAutoAttach = true;
  await loadUserDocuments();
  const resume = existingDocs.value.find(d => d.doc_type === 'resume' && isAllowedResume(d));
  if (!resume) return;
  try {
    const blob = await fetchAsBlob(resume.file_url, resume.mime_type);
    resumeFile.value = new File([blob], resume.file_name || 'resume', { type: resume.mime_type || blob.type });
  } catch (e) {
    // fall through - no auto attach
  }
}

onMounted(async () => {
  await fetchJob();
  checkSaved();
  checkIfApplied();
});
</script>

<style scoped>
.faq-expand-enter-active, .faq-expand-leave-active { transition: all 0.25s ease; overflow: hidden; }
.faq-expand-enter-from, .faq-expand-leave-to { opacity: 0; max-height: 0; }
.faq-expand-enter-to, .faq-expand-leave-from { opacity: 1; max-height: 400px; }
</style>
