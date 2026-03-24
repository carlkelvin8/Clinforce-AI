<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 font-sans">
      <!-- Hero Section -->
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
          <div class="max-w-3xl">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 tracking-tight mb-4">
              Find your next <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">healthcare role</span>
            </h1>
            <p class="text-lg text-gray-600 leading-relaxed">
              Discover opportunities that match your expertise. From leading hospitals to private clinics, your next career move starts here.
            </p>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 -mt-8">
        <!-- Tabs -->
        <div class="flex gap-2 mb-6">
          <button
            @click="switchTab('browse')"
            class="px-5 py-2 rounded-full text-sm font-semibold transition-all"
            :class="activeTab === 'browse' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
          >Browse Jobs</button>
          <button
            @click="switchTab('saved')"
            class="px-5 py-2 rounded-full text-sm font-semibold transition-all flex items-center gap-1.5"
            :class="activeTab === 'saved' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
          >
            <i class="pi pi-bookmark text-xs"></i>
            Saved Jobs
            <span v-if="savedJobs.length" class="ml-1 bg-white/30 text-current rounded-full px-1.5 text-xs">{{ savedJobs.length }}</span>
          </button>
        </div>

        <!-- Saved Jobs Tab -->
        <div v-if="activeTab === 'saved'">
          <div v-if="savedLoading" class="py-12 text-center">
            <i class="pi pi-spin pi-spinner text-4xl text-blue-600 mb-4"></i>
          </div>
          <div v-else-if="savedJobs.length === 0" class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
            <i class="pi pi-bookmark text-4xl text-gray-300 mb-3 block"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-1">No saved jobs</h3>
            <p class="text-gray-500">Jobs you bookmark will appear here.</p>
          </div>
          <div v-else class="grid gap-4 md:gap-6">
            <div
              v-for="j in savedJobs"
              :key="j.id"
              class="group bg-white rounded-xl border border-gray-200 p-5 md:p-6 hover:shadow-xl hover:border-blue-200 transition-all duration-300 cursor-pointer"
              @click="viewJob(j)"
            >
              <div class="flex flex-col md:flex-row gap-4 md:items-start justify-between">
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">{{ j.title || 'Untitled job' }}</h3>
                  <div class="flex flex-wrap gap-2 mt-3">
                    <span v-if="pickLocation(j)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-50 text-gray-600 text-xs font-medium border border-gray-200">
                      <MapPin class="w-3 h-3" />{{ pickLocation(j) }}
                    </span>
                    <span v-if="j.employment_type" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-100">
                      <Clock class="w-3 h-3" />{{ formatEnum(j.employment_type) }}
                    </span>
                  </div>
                </div>
                <Button label="View" size="small" outlined class="!rounded-lg !text-sm !font-semibold !px-4 !border-gray-300 !text-gray-700 hover:!bg-gray-50" />
              </div>
            </div>
          </div>
        </div>

        <!-- Browse Tab -->
        <div v-if="activeTab === 'browse'">
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 md:p-6 mb-8 relative z-10">
          <div class="grid md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-4 space-y-1.5">
              <label for="search" class="block text-sm font-semibold text-gray-700">Search</label>
              <InputText 
                id="search" 
                v-model="q" 
                placeholder="Job title, keywords, or company" 
                @keydown.enter.prevent="fetchJobs(1)" 
                class="w-full !py-2.5 !pl-4 !rounded-lg !bg-gray-50 !border-gray-200 focus:!bg-white focus:!border-blue-500 focus:!ring-blue-500 transition-all"
              />
            </div>
            <div class="md:col-span-2 space-y-1.5">
              <label class="block text-sm font-semibold text-gray-700">Location</label>
              <Select 
                v-model="city" 
                :options="locationOptions" 
                optionLabel="label" 
                optionValue="value" 
                placeholder="Any location" 
                class="w-full !rounded-lg !bg-gray-50 !border-gray-200"
                @change="fetchJobs(1)"
              />
            </div>
            <div class="md:col-span-2 space-y-1.5">
              <label class="block text-sm font-semibold text-gray-700">Type</label>
              <Select 
                v-model="employmentType" 
                :options="employmentOptions" 
                optionLabel="label" 
                optionValue="value" 
                placeholder="Any type" 
                class="w-full !rounded-lg !bg-gray-50 !border-gray-200"
                @change="fetchJobs(1)"
              />
            </div>
            <div class="md:col-span-2 space-y-1.5">
              <label class="block text-sm font-semibold text-gray-700">Work mode</label>
              <Select 
                v-model="workMode" 
                :options="workModeOptions" 
                optionLabel="label" 
                optionValue="value" 
                placeholder="Any mode" 
                class="w-full !rounded-lg !bg-gray-50 !border-gray-200"
                @change="fetchJobs(1)"
              />
            </div>
            <div class="md:col-span-2 flex gap-2 items-end">
               <Button 
                label="Search" 
                @click="fetchJobs(1)" 
                :loading="loading"
                class="flex-1 !bg-blue-600 !border-blue-600 hover:!bg-blue-700 !rounded-lg !py-2.5 !font-semibold"
              />
              <Button 
                label="Reset" 
                text 
                @click="resetFilters" 
                :disabled="loading" 
                class="!text-gray-600 hover:!bg-gray-50 !rounded-lg"
              />
            </div>
          </div>
          <!-- Salary range row -->
          <div class="grid md:grid-cols-12 gap-4 items-end mt-4 pt-4 border-t border-gray-100">
            <div class="md:col-span-3 space-y-1.5">
              <label class="block text-sm font-semibold text-gray-700">Min salary</label>
              <InputNumber 
                v-model="salaryMin" 
                placeholder="e.g. 30000" 
                :min="0"
                :useGrouping="true"
                class="w-full"
                inputClass="!rounded-lg !bg-gray-50 !border-gray-200 !py-2.5 w-full"
                @blur="fetchJobs(1)"
              />
            </div>
            <div class="md:col-span-3 space-y-1.5">
              <label class="block text-sm font-semibold text-gray-700">Max salary</label>
              <InputNumber 
                v-model="salaryMax" 
                placeholder="e.g. 80000" 
                :min="0"
                :useGrouping="true"
                class="w-full"
                inputClass="!rounded-lg !bg-gray-50 !border-gray-200 !py-2.5 w-full"
                @blur="fetchJobs(1)"
              />
            </div>
            <div class="md:col-span-6 flex items-end">
              <p class="text-xs text-gray-400">Leave blank to show all salary ranges. Filters jobs where salary overlaps your range.</p>
            </div>
          </div>
        </div>

        <div v-if="loading" class="py-12 text-center">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-600 mb-4"></i>
          <div class="text-gray-500">Finding best matches...</div>
        </div>

        <div v-else>
          <Message v-if="error" severity="error" :closable="false" class="mb-6">{{ error }}</Message>

          <!-- Job Grid -->
          <div v-if="jobs.length" class="grid gap-4 md:gap-6">
            <div
              v-for="j in jobs"
              :key="String(j.id ?? j._id ?? j.uuid ?? j.slug)"
              class="group bg-white rounded-xl border border-gray-200 p-5 md:p-6 hover:shadow-xl hover:border-blue-200 transition-all duration-300 cursor-pointer relative overflow-hidden"
              @click="viewJob(j)"
            >
              <!-- Hover Gradient -->
              <div class="absolute inset-0 bg-gradient-to-r from-blue-50/0 via-blue-50/0 to-blue-50/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

              <div class="relative flex flex-col md:flex-row gap-4 md:items-start justify-between">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-3 mb-2">
                     <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 text-blue-600 font-bold text-sm border border-blue-100">
                        {{ initialsFromTitle(j.title) }}
                     </span>
                     <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 truncate group-hover:text-blue-600 transition-colors">{{ j.title || "Untitled job" }}</h3>
                        <div class="text-sm text-gray-500 truncate flex items-center gap-1">
                            <Building class="w-3 h-3" />
                            {{ j.hospital || "Clinforce Partner" }}
                        </div>
                     </div>
                  </div>

                  <div class="flex flex-wrap gap-2 mt-4">
                    <span v-if="pickLocation(j)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-50 text-gray-600 text-xs font-medium border border-gray-200">
                      <MapPin class="w-3 h-3" />
                      {{ pickLocation(j) }}
                    </span>
                    <span v-if="j.employment_type" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium border border-blue-100">
                      <Clock class="w-3 h-3" />
                      {{ formatEnum(j.employment_type) }}
                    </span>
                    <span v-if="j.work_mode" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-medium border border-indigo-100">
                      <Monitor class="w-3 h-3" />
                      {{ formatEnum(j.work_mode) }}
                    </span>
                  </div>
                </div>

                <div class="flex md:flex-col items-center md:items-end justify-between gap-4 mt-4 md:mt-0 pl-4 border-t md:border-t-0 border-gray-100 pt-4 md:pt-0">
                   <div class="text-xs font-medium text-gray-400 whitespace-nowrap">
                      {{ formatRelative(j.published_at || j.created_at) }}
                   </div>
                   <Button label="View Details" size="small" outlined class="!rounded-lg !text-sm !font-semibold !px-4 !border-gray-300 !text-gray-700 hover:!bg-gray-50" />
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <Search class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1">No jobs found</h3>
            <p class="text-gray-500 max-w-sm mx-auto">We couldn't find any roles matching your search. Try adjusting your filters.</p>
            <Button label="Clear Filters" text class="mt-4 !text-blue-600" @click="resetFilters" />
          </div>

          <!-- Pagination -->
          <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
            <Button 
              @click="fetchJobs(pagination.current_page - 1)" 
              :disabled="pagination.current_page <= 1 || loading" 
              text
              rounded
              class="!text-gray-500 hover:!bg-gray-100"
            >
                <template #icon>
                    <ChevronLeft class="w-5 h-5" />
                </template>
            </Button>
            <span class="flex items-center px-4 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
             <Button 
              @click="fetchJobs(pagination.current_page + 1)" 
              :disabled="pagination.current_page >= pagination.last_page || loading" 
              text
              rounded
              class="!text-gray-500 hover:!bg-gray-100"
            >
                <template #icon>
                    <ChevronRight class="w-5 h-5" />
                </template>
            </Button>
          </div>
        </div>
        </div><!-- end browse tab -->
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRouter, useRoute } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Message from 'primevue/message';
import { Search, MapPin, Briefcase, Clock, Monitor, Building, ChevronLeft, ChevronRight } from 'lucide-vue-next';

const router = useRouter();
const route = useRoute();

const meName = ref("ME");
const loading = ref(false);
const error = ref("");

const payload = ref(null);
const savedJobs = ref([]);
const activeTab = ref('browse'); // 'browse' | 'saved'
const savedLoading = ref(false);

async function fetchSavedJobs() {
  savedLoading.value = true;
  try {
    const res = await api.get('/saved-jobs');
    savedJobs.value = res.data?.data || [];
  } catch {
    savedJobs.value = [];
  } finally {
    savedLoading.value = false;
  }
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'saved') fetchSavedJobs();
}

const q = ref("");
const city = ref(null);
const dept = ref(null);
const employmentType = ref(null);
const workMode = ref(null);
const salaryMin = ref(null);
const salaryMax = ref(null);
const employerIdFilter = ref(route.query.employer_id ? String(route.query.employer_id) : "");

const locationOptions = [
  { label: 'All locations', value: '' },
  { label: 'Metro Manila', value: 'Metro Manila' },
  { label: 'Cebu', value: 'Cebu' },
  { label: 'Davao', value: 'Davao' },
  { label: 'Remote', value: 'Remote' }
];

const deptOptions = [
  { label: 'All departments', value: '' },
  { label: 'ICU / Critical Care', value: 'ICU' },
  { label: 'Emergency', value: 'Emergency' },
  { label: 'Medical-Surgical', value: 'Medical-Surgical' },
  { label: 'Telehealth', value: 'Telehealth' }
];

const employmentOptions = [
  { label: 'All types', value: '' },
  { label: 'Full time', value: 'full_time' },
  { label: 'Part time', value: 'part_time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Temporary', value: 'temporary' },
  { label: 'Internship', value: 'internship' }
];

const workModeOptions = [
  { label: 'All modes', value: '' },
  { label: 'On-site', value: 'on_site' },
  { label: 'Remote', value: 'remote' },
  { label: 'Hybrid', value: 'hybrid' }
];

function getStatusSeverity(status) {
  const statusLower = (status || '').toLowerCase();
  switch (statusLower) {
    case 'published':
    case 'open':
      return 'success';
    case 'draft':
      return 'warning';
    case 'closed':
    case 'archived':
      return 'danger';
    default:
      return 'info';
  }
}

function unwrapJobsList(body) {
  if (!body) return [];
  if (Array.isArray(body)) return body;

  if (body && typeof body === "object" && Array.isArray(body.data)) return body.data;

  if (body?.data?.data && Array.isArray(body.data.data)) return body.data.data;

  return [];
}

function unwrapPagination(body) {
  if (!body || typeof body !== "object") return null;
  if (Number.isFinite(body.current_page) && Number.isFinite(body.last_page)) {
    return { current_page: body.current_page, last_page: body.last_page };
  }
  if (body?.meta && Number.isFinite(body.meta.current_page) && Number.isFinite(body.meta.last_page)) {
    return { current_page: body.meta.current_page, last_page: body.meta.last_page };
  }
  return null;
}

const jobs = computed(() => unwrapJobsList(payload.value));
const pagination = computed(() => unwrapPagination(payload.value));

const canUseDetailsRoute = computed(() => {
  try {
    return router.hasRoute("candidate.jobs.view");
  } catch {
    return false;
  }
});

function trim(s, n) {
  const t = String(s ?? "");
  return t.length > n ? t.slice(0, n - 1) + "…" : t;
}

function formatEnum(v) {
  const t = String(v ?? "").trim();
  if (!t) return "—";
  return t
    .split("_")
    .map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w))
    .join(" ");
}

function initialsFromTitle(title) {
  if (!title) return "JO";
  return title
    .split(" ")
    .map((w) => w[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

function formatRelative(v) {
  if (!v) return "—";
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return String(v);

  const diff = Date.now() - d.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return "just now";
  if (mins < 60) return `${mins} min ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs} hours ago`;
  return `${Math.floor(hrs / 24)} days ago`;
}

function pickLocation(j) {
  const parts = [j.city, j.country_code].filter(Boolean);
  return parts.length ? parts.join(", ") : "—";
}

function viewJob(j) {
  if (!j?.id) return;
  router.push({ name: 'candidate.jobs.view', params: { id: j.id } });
}

async function fetchJobs(page = 1) {
  loading.value = true;
  error.value = "";

  try {
    const params = { page };

    if (q.value) params.q = q.value;
    if (city.value) params.city = city.value;

    if (employmentType.value) params.employment_type = employmentType.value;
    if (workMode.value) params.work_mode = workMode.value;
    if (salaryMin.value != null && salaryMin.value > 0) params.salary_min = salaryMin.value;
    if (salaryMax.value != null && salaryMax.value > 0) params.salary_max = salaryMax.value;
    if (employerIdFilter.value) params.employer_id = employerIdFilter.value;

    const res = await api.get("/public/jobs", { params });
    payload.value = res.data;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Failed to load jobs.";
    payload.value = null;
  } finally {
    loading.value = false;
  }
}

function resetFilters() {
  q.value = "";
  city.value = "";
  dept.value = "";
  employmentType.value = "";
  workMode.value = "";
  salaryMin.value = null;
  salaryMax.value = null;
  employerIdFilter.value = "";
  router.replace({ name: 'candidate.jobs', query: {} });
  fetchJobs(1);
}

watch(
  () => route.query.employer_id,
  (val) => {
    employerIdFilter.value = val ? String(val) : "";
    fetchJobs(1);
  }
);

onMounted(() => fetchJobs(1));
</script>

<style scoped>
</style>
