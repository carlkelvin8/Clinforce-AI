<!-- resources/js/Applicants/ApplicantsList.vue -->
<template>
  <AppLayout>
    <div class="w-full max-w-7xl mx-auto p-4">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Applicants</h1>
          <p class="text-gray-500 text-sm mt-1">This list is driven by job applications from your API.</p>
          <p class="text-gray-500 text-xs mt-1">
             Role: <span class="font-bold">{{ meRole || "unknown" }}</span> • Scope: <span class="font-bold">{{ scope }}</span>
          </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
           <Select v-model="scope" :options="scopeOptions" optionLabel="label" optionValue="value" class="w-full md:w-60" @change="fetchData(1)" />
           <Button label="Refresh" icon="pi pi-refresh" :loading="loading" @click="fetchData(1)" outlined />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Filters -->
        <div class="md:col-span-1">
          <Card class="sticky top-20">
            <template #title>Filters</template>
            <template #content>
               <div class="flex flex-col gap-4">
                 <div class="flex flex-col gap-2">
                   <label class="font-bold text-xs text-gray-500 uppercase tracking-wider">Search</label>
                   <InputText v-model="search" placeholder="Job title, applicant ID…" @keydown.enter="applyFilters" class="w-full" />
                 </div>
                 <div class="flex flex-col gap-2">
                   <label class="font-bold text-xs text-gray-500 uppercase tracking-wider">Status</label>
                   <Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All" class="w-full" showClear />
                 </div>
                 <div class="flex gap-2 mt-2">
                   <Button label="Apply" @click="applyFilters" :disabled="loading" class="flex-1" />
                   <Button label="Reset" severity="secondary" @click="resetFilters" :disabled="loading" class="flex-1" outlined />
                 </div>
                 <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
                 <Message v-if="debugHint" severity="info" :closable="false" class="break-all">{{ debugHint }}</Message>
               </div>
            </template>
          </Card>
        </div>

        <!-- Results -->
        <div class="md:col-span-3 flex flex-col gap-4">
           <div v-if="loading" class="flex justify-center p-8">
             <span class="pi pi-spinner pi-spin text-2xl text-gray-500"></span>
           </div>

           <Card v-else-if="items.length === 0">
             <template #content>
               <div class="text-center p-8">
                 <div class="font-bold text-gray-900">No applicants match your filters.</div>
               </div>
             </template>
           </Card>

           <Card v-for="row in items" :key="row.id">
             <template #content>
               <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                 <div class="flex-1 min-w-0">
                   <div class="flex items-center flex-wrap gap-2 mb-2">
                     <span class="font-bold text-gray-900 text-sm">Applicant #{{ row.applicant_user_id }}</span>
                     <span class="text-gray-300">•</span>
                     <span class="font-bold text-gray-700 truncate max-w-md">{{ row.job?.title || "Job" }}</span>
                     <Tag :value="row.status || '—'" :severity="getSeverity(row.status)" />
                   </div>
                   <div class="flex flex-wrap gap-2 text-xs text-gray-500 mb-2">
                     <span>Submitted <span class="font-bold text-gray-900">{{ formatDate(row.submitted_at) }}</span></span>
                     <span class="text-gray-300">•</span>
                     <span>ID <span class="font-bold text-gray-900">#{{ row.id }}</span></span>
                   </div>
                   <p v-if="row.cover_letter" class="text-sm text-gray-700 line-clamp-2">{{ row.cover_letter }}</p>
                 </div>
                 <Button label="View" icon="pi pi-eye" size="small" @click="router.push({ name: 'applicants.view', params: { id: row.id } })" />
               </div>
             </template>
           </Card>

           <!-- Pagination -->
           <div v-if="pagination" class="flex justify-between items-center mt-4 text-sm text-gray-500">
             <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
             <div class="flex gap-2">
               <Button label="Prev" size="small" outlined :disabled="loading || pagination.current_page <= 1" @click="fetchPage(pagination.current_page - 1)" />
               <Button label="Next" size="small" outlined :disabled="loading || pagination.current_page >= pagination.last_page" @click="fetchPage(pagination.current_page + 1)" />
             </div>
           </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Message from 'primevue/message';

const router = useRouter();

const loading = ref(false);
const error = ref("");
const debugHint = ref("");

const meRole = ref(""); // admin|employer|agency|applicant

const scope = ref("mine"); // default safe for applicants
const status = ref(null);
const search = ref("");

const allowedStatuses = [
  "submitted",
  "shortlisted",
  "rejected",
  "interview",
  "hired",
  "withdrawn",
];
const statusOptions = allowedStatuses.map(s => ({ label: s.charAt(0).toUpperCase() + s.slice(1), value: s }));

const raw = ref(null);

const canOwned = computed(() => ["admin", "employer", "agency"].includes(meRole.value));
const canMine = computed(() => ["admin", "applicant"].includes(meRole.value) || !meRole.value); // allow while loading

const scopeOptions = computed(() => {
    const opts = [];
    if (canOwned.value) opts.push({ label: 'Owned (employer / agency)', value: 'owned' });
    if (canMine.value) opts.push({ label: 'Mine (applicant)', value: 'mine' });
    return opts;
});

function unwrapPaginator(resData) {
  // ApiController::ok($paginator) => { message, data: paginator }
  const outer = resData?.data ?? resData;
  return outer?.data ?? outer;
}

function buildUrl() {
  const base = String(api?.defaults?.baseURL ?? "");
  return base.includes("/api") ? "/applications" : "/api/applications";
}

const items = computed(() => {
  const rows = raw.value?.data || [];
  const q = search.value.trim().toLowerCase();

  return rows.filter((r) => {
    if (status.value && r.status !== status.value) return false;
    if (!q) return true;

    const hay = [
      String(r.id || ""),
      String(r.applicant_user_id || ""),
      String(r.status || ""),
      String(r.job?.title || ""),
    ].join(" ").toLowerCase();

    return hay.includes(q);
  });
});

const pagination = computed(() => {
  if (!raw.value) return null;
  return {
    current_page: Number(raw.value.current_page || 1),
    last_page: Number(raw.value.last_page || 1),
  };
});

function formatDate(v) {
  if (!v) return "N/A";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);
  return d.toLocaleString();
}

function getSeverity(s) {
  switch (s) {
    case "submitted": return "secondary";
    case "shortlisted": return "info";
    case "interview": return "warn";
    case "hired": return "success";
    case "rejected": return "danger";
    case "withdrawn": return "secondary";
    default: return "secondary";
  }
}

async function fetchMeRole() {
  // If baseURL is "/api", use "/me"
  // If baseURL is "/", use "/api/me"
  const base = String(api?.defaults?.baseURL ?? "");
  const url = base.includes("/api") ? "/me" : "/api/me";

  const res = await api.get(url);
  const outer = res.data?.data ?? res.data; // { message, data } or plain
  const user = outer?.data ?? outer?.user ?? outer;
  meRole.value = user?.role || "";

  // Auto-fix scope (this is the main reason for 403)
  if (meRole.value === "admin") scope.value = "owned";
  else if (["employer", "agency"].includes(meRole.value)) scope.value = "owned";
  else scope.value = "mine";
}

async function fetchData(page = 1) {
  loading.value = true;
  error.value = "";
  debugHint.value = "";

  try {
    const params = { scope: scope.value, page };
    if (status.value) params.status = status.value;

    const url = buildUrl();
    const res = await api.get(url, { params });

    const paginator = unwrapPaginator(res.data);
    raw.value = paginator;

    if (!raw.value || !Array.isArray(raw.value.data)) {
      raw.value = { data: [], current_page: 1, last_page: 1 };
    }
  } catch (e) {
    const code = e?.response?.status;
    const msg = e?.response?.data?.message || e?.message || "Request failed";
    error.value = msg;

    debugHint.value = `HTTP ${code || "?"} — role=${meRole.value || "unknown"} scope=${scope.value} url=${buildUrl()}`;
    raw.value = { data: [], current_page: 1, last_page: 1 };
  } finally {
    loading.value = false;
  }
}

function fetchPage(p) {
  fetchData(Math.max(1, Number(p || 1)));
}
function applyFilters() {
  fetchData(1);
}
function resetFilters() {
  status.value = null;
  search.value = "";
  fetchData(1);
}

onMounted(async () => {
  try {
    await fetchMeRole();
  } catch {
    // if /me fails, you’ll see it on debugHint after fetchData
  }
  await fetchData(1);
});
</script>

<style scoped>
:deep(.p-card) {
  border: none !important;
  box-shadow: none !important;
  background-color: transparent !important;
}
</style>
