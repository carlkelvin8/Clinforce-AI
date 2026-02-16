<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import { http } from "../../../lib/http";

// PrimeVue
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Tag from "primevue/tag";
import Message from "primevue/message";

/** ===== Sidebar ===== */
const route = useRoute();

/** ===== Jobs Index logic ===== */
const loading = ref(true);
const error = ref("");

const q = ref("");
const status = ref("all"); // all | open | closed | draft
const sort = ref("recent"); // recent | title
const rows = ref([]);

// ---- helpers ----
function normalizeStatus(r) {
  const raw = String(r?.status || "").toLowerCase().trim();
  if (raw === "published") return "open";
  if (raw === "archived") return "closed";
  if (raw === "draft") return "draft";
  if (r?.is_published === true) return "open";
  return "draft";
}

function statusText(uiStatus) {
  if (uiStatus === "open") return "Open";
  if (uiStatus === "closed") return "Closed";
  return "Draft";
}

function getSeverity(uiStatus) {
    if (uiStatus === "open") return "success";
    if (uiStatus === "closed") return "danger";
    return "warn";
}

function pickTitle(r) {
  return r?.title || r?.role_title || "Untitled role";
}

function pickDepartment(r) {
  return r?.department || "—";
}

function pickLocation(r) {
  const city = String(r?.city || "").trim();
  const cc = String(r?.country_code || "").trim();
  const parts = [city, cc].filter(Boolean);
  return parts.length ? parts.join(", ") : (r?.location || "—");
}

function safeDate(val) {
  const d = new Date(val || 0);
  return Number.isFinite(d.getTime()) ? d : null;
}

function formatDate(val) {
  const d = safeDate(val);
  if (!d) return "";
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
}

function formatRelative(val) {
  const d = safeDate(val);
  if (!d) return "";
  const ms = Date.now() - d.getTime();
  const mins = Math.floor(ms / 60000);
  if (mins < 1) return "Just now";
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  return `${days}d ago`;
}

function initialsFromTitle(title) {
  const t = String(title || "").trim();
  if (!t) return "JR";
  const parts = t.split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] || "J";
  const b = parts.length > 1 ? parts[1]?.[0] : parts[0]?.[1] || "R";
  return (a + b).toUpperCase();
}

function getApplicationCount(job) {
  return job?.applications_count ?? (job?.applications?.length ?? 0);
}

function getActiveCount(job) {
  if (typeof job?.applications_active_count === 'number') return job.applications_active_count;
  const apps = job?.applications || [];
  const s = (x) => String(x || '').toLowerCase();
  const activeStatuses = ['submitted', 'shortlisted', 'interview'];
  return apps.filter(app => activeStatuses.includes(s(app?.status))).length;
}

function getViewCount(job) {
  return job?.views_count ?? (job?.views ?? 0);
}

function normalizeRows(payload) {
  const root = payload?.data ?? payload;
  if (Array.isArray(root)) return root;
  if (Array.isArray(root?.data)) return root.data;
  if (Array.isArray(root?.items)) return root.items;
  if (Array.isArray(payload?.data?.data)) return payload.data.data;
  return [];
}

// ---- server-side query params ----
function apiStatusParam(uiStatus) {
  if (uiStatus === "open") return "published";
  if (uiStatus === "closed") return "archived";
  if (uiStatus === "draft") return "draft";
  return null;
}

function apiSortParam(uiSort) {
  return uiSort === "title" ? "title" : "recent";
}

// ---- loading (debounced reload + stale response protection) ----
let debounceTimer = null;
let reqSeq = 0;

async function load({ silent = false } = {}) {
  const mySeq = ++reqSeq;

  if (!silent) {
    loading.value = true;
    error.value = "";
  }

  try {
    const params = {
      q: q.value.trim() || undefined,
      status: apiStatusParam(status.value) || undefined,
      sort: apiSortParam(sort.value),
      scope: "owned",
    };

    const res = await http.get("/jobs", { params });

    if (mySeq !== reqSeq) return;
    rows.value = normalizeRows(res.data);
  } catch (e) {
    if (mySeq !== reqSeq) return;

    const code = e?.response?.status;
    error.value =
      e?.response?.data?.message ||
      (code ? `Request failed (${code})` : "") ||
      e?.message ||
      "Failed to load jobs.";
    rows.value = [];
  } finally {
    if (mySeq === reqSeq && !silent) loading.value = false;
  }
}

function scheduleReload() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => load({ silent: true }), 250);
}

watch([q, status, sort], () => scheduleReload());

// ---- counts ----
const counts = computed(() => {
  const all = rows.value.length;
  const open = rows.value.filter((r) => normalizeStatus(r) === "open").length;
  const closed = rows.value.filter((r) => normalizeStatus(r) === "closed").length;
  const draft = rows.value.filter((r) => normalizeStatus(r) === "draft").length;
  return { all, open, closed, draft };
});

// ---- pinned drafts + client-side fallback filtering ----
function statusWeight(uiStatus) {
  if (uiStatus === "draft") return 0;
  if (uiStatus === "open") return 1;
  return 2;
}

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase();
  let data = rows.value.slice();

  if (term) {
    data = data.filter((r) => {
      const t = String(pickTitle(r)).toLowerCase();
      const d = String(r?.department || "").toLowerCase();
      const l = String(pickLocation(r)).toLowerCase();
      return t.includes(term) || d.includes(term) || l.includes(term);
    });
  }

  if (status.value !== "all") {
    data = data.filter((r) => normalizeStatus(r) === status.value);
  }

  data.sort((a, b) => {
    const sa = normalizeStatus(a);
    const sb = normalizeStatus(b);

    const wa = statusWeight(sa);
    const wb = statusWeight(sb);
    if (wa !== wb) return wa - wb;

    if (sort.value === "title") return String(pickTitle(a)).localeCompare(String(pickTitle(b)));

    const ta = safeDate(a?.created_at)?.getTime() || 0;
    const tb = safeDate(b?.created_at)?.getTime() || 0;
    return tb - ta;
  });

  return data;
});

// initial load
onMounted(() => load());
onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6 font-sans">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <span class="text-xs font-bold uppercase text-slate-500 tracking-wider">Employer</span>
          <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Manage Roles</h1>
          <p class="text-sm text-slate-500 mt-1">Create, manage, and publish clinical roles.</p>
        </div>
        <div class="flex gap-3">
           <Button 
             :label="loading ? 'Refreshing...' : 'Refresh'" 
             icon="pi pi-refresh" 
             severity="secondary" 
             @click="load()" 
             :loading="loading" 
             class="!bg-white !border-slate-300 !text-slate-700 hover:!bg-slate-50"
           />
           <RouterLink :to="{ name: 'employer.jobs.create' }" custom v-slot="{ navigate }">
              <Button 
                label="Post new role" 
                icon="pi pi-plus" 
                @click="navigate"
                class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" 
              />
           </RouterLink>
        </div>
      </div>

      <!-- Error -->
      <Message v-if="error" severity="error" :closable="false" class="mb-4">{{ error }} <Button label="Retry" text size="small" @click="load()" class="p-0 ml-2 !text-red-600" /></Message>

      <!-- Enhanced Filters & Toolbar -->
      <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap gap-2">
                <Button 
                  v-for="s in ['all', 'open', 'draft', 'closed']" 
                  :key="s"
                  :label="s.charAt(0).toUpperCase() + s.slice(1)"
                  :badge="counts[s].toString()"
                  :severity="status === s ? 'primary' : 'secondary'"
                  :outlined="status !== s"
                  size="small"
                  @click="status = s"
                  rounded
                  class="!px-4 !py-2 !font-medium"
                  :class="status === s ? '!bg-blue-50 !text-blue-600 !border-blue-200' : '!bg-white !text-slate-600 !border-slate-200 hover:!bg-slate-50'"
                />
            </div>
            <div class="flex flex-col sm:flex-row gap-3 items-center w-full lg:w-auto">
                <IconField class="w-full sm:w-auto">
                    <InputIcon class="pi pi-search text-slate-400" />
                    <InputText 
                      v-model="q" 
                      placeholder="Search roles..." 
                      class="w-full sm:w-64 !pl-10 !rounded-lg !border-slate-300 focus:!border-blue-500 focus:!ring-blue-500"
                    />
                </IconField>
                <Select 
                  v-model="sort" 
                  :options="[{label: 'Sort: Recent', value: 'recent'}, {label: 'Sort: Title', value: 'title'}]" 
                  optionLabel="label" 
                  optionValue="value" 
                  class="w-full sm:w-48 !rounded-lg !border-slate-300"
                />
                <span class="hidden sm:inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-50 text-slate-600 border border-slate-200">
                  {{ filtered.length }} role<span v-if="filtered.length !== 1">s</span>
                </span>
                <Button 
                  v-if="q || status !== 'all' || sort !== 'recent'"
                  label="Clear"
                  size="small"
                  text
                  severity="secondary"
                  class="!text-slate-600"
                  @click="q = ''; status = 'all'; sort = 'recent'; load()"
                />
            </div>
        </div>
      </div>

      <!-- Enhanced Job List -->
      <div v-if="loading" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-blue-600 mb-4"></i>
        <div class="text-slate-500">Loading opportunities...</div>
      </div>
      
      <div v-else-if="filtered.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
         <div v-for="r in filtered" :key="r.id" class="group relative bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:border-blue-300 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
            <RouterLink :to="{ name: 'employer.jobs.view', params: { id: r.id } }" class="absolute inset-0 z-10"></RouterLink>
            
            <!-- Header -->
            <div class="flex items-start justify-between gap-4 mb-4">
                <div class="flex items-center gap-3 overflow-hidden">
                     <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-md shrink-0">
                        {{ initialsFromTitle(pickTitle(r)) }}
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-lg text-slate-900 truncate" :title="pickTitle(r)">{{ pickTitle(r) }}</h3>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                             <i class="pi pi-map-marker text-xs"></i>
                             <span class="truncate">{{ pickLocation(r) }}</span>
                        </div>
                    </div>
                </div>
                 <Tag 
                  :value="statusText(normalizeStatus(r))" 
                  :severity="getSeverity(normalizeStatus(r))" 
                  rounded 
                  class="!text-xs !font-bold uppercase tracking-wide"
                />
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-2 py-3 border-t border-b border-slate-100 mb-4 bg-slate-50/50 rounded-lg">
                <div class="text-center px-2">
                    <div class="text-lg font-bold text-slate-900">{{ getApplicationCount(r) || 0 }}</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Applied</div>
                </div>
                <div class="text-center px-2 border-l border-slate-200">
                    <div class="text-lg font-bold text-green-600">{{ getActiveCount(r) || 0 }}</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Active</div>
                </div>
                <div class="text-center px-2 border-l border-slate-200">
                    <div class="text-lg font-bold text-blue-600">{{ getViewCount(r) || 0 }}</div>
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Views</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-between items-center relative z-20">
                <span class="text-xs text-slate-400 font-medium">{{ formatRelative(r.created_at) }}</span>
                <div class="flex items-center gap-1.5">
                  <RouterLink :to="{ name: 'employer.jobs.edit', params: { id: r.id } }" class="inline-flex items-center px-2 py-1 rounded-lg text-xs border border-slate-200 text-slate-600 bg-white hover:bg-slate-50 z-20">
                    Edit
                  </RouterLink>
                <RouterLink
                  :to="{ name: 'employer.jobs.view', params: { id: r.id } }"
                  class="inline-flex items-center px-2 py-1 rounded-lg text-xs text-blue-700 bg-blue-50 border border-blue-200 z-20"
                >
                  Manage <i class="pi pi-arrow-right text-[10px] ml-1"></i>
                </RouterLink>
                </div>
            </div>
         </div>
      </div>

      <div v-else class="text-center py-16 bg-white rounded-xl border border-dashed border-slate-300">
          <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blue-600 mb-4">
            <i class="pi pi-briefcase text-2xl"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-900 mb-2">No roles found</h3>
          <p class="text-slate-500 mb-6 max-w-md mx-auto">Try adjusting your filters or create a new job posting to find the perfect candidate.</p>
          <RouterLink :to="{ name: 'employer.jobs.create' }" custom v-slot="{ navigate }">
              <Button 
                label="Post New Role" 
                icon="pi pi-plus" 
                @click="navigate" 
                class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700 !px-6 !py-2.5 !rounded-lg"
              />
           </RouterLink>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* No custom CSS needed! Using Tailwind */
</style>
