<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

// PrimeVue
import Card from "primevue/card";
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Calendar from "primevue/calendar";
import Message from "primevue/message";

/* =========================
   Sidebar + auth (UNCHANGED)
   ========================= */
const route = useRoute();

/* =========================
   Helpers
   ========================= */
function unwrap(resData) {
  return resData?.data ?? resData;
}
function unwrapList(resData) {
  const body = unwrap(resData);
  if (Array.isArray(body)) return body;
  if (Array.isArray(body?.data)) return body.data;
  return [];
}
function toText(v) { return String(v ?? "").toLowerCase(); }
function parseDate(v) {
  const d = new Date(v || 0);
  return Number.isFinite(d.getTime()) ? d : null;
}
function fmtDate(d) {
  const dt = parseDate(d);
  if (!dt) return { date: "—", time: "" };
  return {
    date: dt.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" }),
    time: dt.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" }),
  };
}

/* =========================
   Data: schedule
   ========================= */
const loading = ref(true);
const q = ref("");
const jobFilter = ref("all");
const timeFilter = ref("upcoming");
const interviews = ref([]);

async function loadSchedule() {
  loading.value = true;
  try {
    const res = await api.get("/interviews");
    interviews.value = unwrapList(res.data);
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await loadSchedule();
  if (route.query.candidate_id) {
    openScheduleModal();
  }
});

const jobs = computed(() => {
  const map = new Map();
  for (const i of interviews.value) {
    const j = i?.application?.job;
    if (j?.id) map.set(String(j.id), j);
  }
  return Array.from(map.values());
});

const jobOptions = computed(() => {
    return [
        { label: 'All jobs', value: 'all' },
        ...jobs.value.map(j => ({ label: j.title || `Job #${j.id}`, value: j.id }))
    ];
});

const timeOptions = [
    { label: 'Upcoming only', value: 'upcoming' },
    { label: 'All', value: 'all' },
    { label: 'Past', value: 'past' }
];

const filtered = computed(() => {
  const term = q.value.trim().toLowerCase();
  const now = new Date();

  return interviews.value
    .filter((i) => {
      const app = i?.application || null;
      const job = app?.job || null;

      if (jobFilter.value !== "all") {
        const jid = job?.id || app?.job_id;
        if (String(jid) !== String(jobFilter.value)) return false;
      }

      if (term) {
        const candidateName =
          app?.applicant?.name ||
          app?.user?.name ||
          app?.applicant_name ||
          app?.applicant_full_name ||
          "";

        const hay = [
          candidateName,
          job?.title,
          i?.mode,
          i?.status,
          i?.meeting_link,
          i?.location_text,
        ].map(toText).join(" ");
        if (!hay.includes(term)) return false;
      }

      const dt = parseDate(i?.scheduled_start);
      if (!dt) return timeFilter.value === "all";

      if (timeFilter.value === "upcoming") return dt >= now;
      if (timeFilter.value === "past") return dt < now;
      return true;
    })
    .sort((a, b) => {
      const da = parseDate(a?.scheduled_start)?.getTime() ?? 0;
      const db = parseDate(b?.scheduled_start)?.getTime() ?? 0;
      return da - db;
    });
});

/* =========================
   Modal: schedule interview
   ========================= */
const modalOpen = ref(false);
const saving = ref(false);
const formError = ref("");
const formFieldErrors = ref({});

const appsLoading = ref(false);
const applications = ref([]);

const form = ref({
  application_id: "",
  scheduled_start: null,
  scheduled_end: null,
  mode: "video",
  meeting_link: "",
  location_text: "",
});

function resetForm() {
  formError.value = "";
  formFieldErrors.value = {};
  const now = new Date();
  const start = new Date(now.getTime() + 60 * 60 * 1000);
  const end = new Date(start.getTime() + 30 * 60 * 1000);

  form.value = {
    application_id: "",
    scheduled_start: start,
    scheduled_end: end,
    mode: "video",
    meeting_link: "",
    location_text: "",
  };
}

async function loadApplications() {
  appsLoading.value = true;
  try {
    // Load applications with open status (submitted, shortlisted, interview)
    const res = await api.get("/applications", { 
      params: { 
        scope: "owned", 
        with: "job,applicant"
      } 
    });
    
    const data = unwrap(res.data);
    applications.value = Array.isArray(data?.applications) ? data.applications : unwrapList(res.data);
    
    console.log('Loaded applications:', applications.value.length);
    
    // Auto-select application if candidate_id matches
    if (route.query.candidate_id) {
      const candId = String(route.query.candidate_id);
      const match = applications.value.find(a => 
        String(a?.applicant_id || a?.applicant_user_id || a?.user_id) === candId
      );
      if (match) {
        form.value.application_id = match.id;
      }
    }
  } catch (error) {
    console.error('Failed to load applications:', error);
    formError.value = "Failed to load applications. Please try again.";
  } finally {
    appsLoading.value = false;
  }
}

function openScheduleModal() {
  resetForm();
  modalOpen.value = true;
  loadApplications();
}

function closeScheduleModal() {
  modalOpen.value = false;
}

const applicationOptions = computed(() => {
  return applications.value.map((a) => {
    const jobTitle = a?.job?.title || a?.job_title || "Job";
    const cand =
      a?.applicant_name ||
      a?.applicant?.name ||
      a?.user?.name ||
      a?.applicant_full_name ||
      "Candidate";
    return {
      id: a.id,
      label: `${cand} — ${jobTitle} (App #${a.id})`,
    };
  });
});

const modeOptions = [
    { label: 'Video (Zoom)', value: 'video' },
    { label: 'In person', value: 'in_person' },
    { label: 'Phone', value: 'phone' }
];

watch(
  () => form.value.mode,
  (m) => {
    if (m !== "in_person") form.value.location_text = "";
  }
);

function setFieldErrors(payload) {
  const errs = payload?.errors || payload || {};
  formFieldErrors.value = typeof errs === "object" && errs ? errs : {};
}

async function submitSchedule() {
  formError.value = "";
  formFieldErrors.value = {};
  saving.value = true;

  try {
    if (!form.value.application_id) {
      formError.value = "Please select an application.";
      return;
    }

    const start = form.value.scheduled_start;
    const end = form.value.scheduled_end;

    if (!start || !end) {
      formError.value = "Start and end are required.";
      return;
    }

    const payload = {
      scheduled_start: start.toISOString(),
      scheduled_end: end.toISOString(),
      mode: form.value.mode,
      meeting_link: form.value.meeting_link?.trim() || null,
      location_text: form.value.location_text?.trim() || null,
    };

    const res = await api.post(`/applications/${form.value.application_id}/interviews`, payload);
    const created = unwrap(res.data);

    interviews.value = [...interviews.value, created].sort((a, b) => {
      const da = parseDate(a?.scheduled_start)?.getTime() ?? 0;
      const db = parseDate(b?.scheduled_start)?.getTime() ?? 0;
      return da - db;
    });

    closeScheduleModal();
  } catch (e) {
    const status = e?.response?.status;
    const payload = e?.response?.data;
    if (status === 422) {
      formError.value = payload?.message || "Validation failed.";
      setFieldErrors(payload);
    } else {
      formError.value = payload?.message || "Failed to schedule interview.";
    }
  } finally {
    saving.value = false;
  }
}

function getSeverity(status) {
    switch (String(status).toLowerCase()) {
        case 'scheduled': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto p-6 space-y-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Interviews</h1>
                <p class="text-gray-500 mt-2 text-lg">Manage your interview schedule and candidate meetings.</p>
            </div>
            <div class="flex gap-2">
                <Button 
                    :label="loading ? 'Refreshing...' : 'Refresh'" 
                    :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" 
                    severity="secondary" 
                    text
                    @click="loadSchedule" 
                    :disabled="loading" 
                />
                <Button 
                    label="Schedule Interview" 
                    icon="pi pi-plus" 
                    @click="openScheduleModal" 
                    rounded
                    class="!px-6"
                />
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-2">
             <div class="relative flex-1">
                <i class="pi pi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <InputText 
                  v-model="q" 
                  placeholder="Search by candidate, job, mode..." 
                  class="w-full !pl-10 !border-0 !shadow-none !bg-transparent focus:!ring-0 text-lg" 
                />
             </div>
             <div class="h-px md:h-auto md:w-px bg-gray-200 mx-2"></div>
             <div class="flex gap-2 md:w-auto w-full">
                 <Dropdown 
                    v-model="jobFilter" 
                    :options="jobOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    class="w-full md:w-48 !border-0 !shadow-none" 
                    placeholder="All Jobs"
                 />
                 <Dropdown 
                    v-model="timeFilter" 
                    :options="timeOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    class="w-full md:w-40 !border-0 !shadow-none" 
                 />
             </div>
        </div>

        <!-- Schedule table -->
        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <span class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Schedule</span>
                <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded border border-gray-200">{{ filtered.length }} item(s)</span>
            </div>
            
            <DataTable :value="filtered" :loading="loading" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
                <template #empty>
                    <div class="text-center py-12">
                        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="pi pi-calendar text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">No interviews found.</p>
                    </div>
                </template>

                <Column header="When" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                             <div class="flex flex-col items-center justify-center w-10 h-10 rounded bg-blue-50 text-blue-700">
                                 <span class="text-[10px] font-bold uppercase leading-none">{{ new Date(data.scheduled_start).toLocaleDateString('en-US', { month: 'short' }) }}</span>
                                 <span class="text-lg font-bold leading-none">{{ new Date(data.scheduled_start).getDate() }}</span>
                             </div>
                             <div>
                                 <div class="font-semibold text-gray-900">{{ fmtDate(data.scheduled_start).time }}</div>
                                 <div class="text-xs text-gray-500">{{ fmtDate(data.scheduled_end).time }}</div>
                             </div>
                        </div>
                    </template>
                </Column>

                <Column header="Candidate" style="min-width: 220px">
                    <template #body="{ data }">
                        <div class="font-bold text-gray-900">
                            {{
                                data?.application?.applicant?.name ||
                                data?.application?.user?.name ||
                                data?.application?.applicant_name ||
                                "Candidate"
                            }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">App #{{ data?.application?.id || data?.application_id || "—" }}</div>
                    </template>
                </Column>

                <Column header="Role" style="min-width: 200px">
                    <template #body="{ data }">
                        <div class="font-medium text-gray-900 truncate max-w-[200px]">
                            {{ data?.application?.job?.title || "—" }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                             <i class="pi pi-map-marker text-[10px]"></i>
                             {{ data?.location_text || "Remote/Online" }}
                        </div>
                    </template>
                </Column>

                <Column header="Mode">
                    <template #body="{ data }">
                        <Tag :value="data?.mode || '—'" severity="secondary" class="uppercase text-[10px]" rounded />
                    </template>
                </Column>

                <Column header="Status">
                    <template #body="{ data }">
                         <Tag :value="data?.status || '—'" :severity="getSeverity(data?.status)" class="uppercase text-[10px]" rounded />
                    </template>
                </Column>

                <Column header="Link" alignFrozen="right" frozen>
                    <template #body="{ data }">
                        <a v-if="data?.meeting_link" :href="data.meeting_link" target="_blank" rel="noreferrer" class="no-underline">
                            <Button icon="pi pi-external-link" rounded text size="small" aria-label="Open Link" />
                        </a>
                        <span v-else class="text-gray-300 text-sm">—</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Modal -->
        <Dialog v-model:visible="modalOpen" header="Schedule Interview" modal :style="{ width: '50rem' }" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" class="p-fluid">
             <template #header>
                <div class="flex flex-col gap-1">
                    <span class="text-xl font-bold text-gray-900">Schedule Interview</span>
                    <span class="text-sm text-gray-500">Set up a new meeting with a candidate</span>
                </div>
             </template>

             <div v-if="formError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-6 flex items-start gap-2">
                <i class="pi pi-exclamation-circle mt-0.5"></i>
                <span>{{ formError }}</span>
             </div>

             <div class="space-y-6 py-2">
                 <div class="flex flex-col gap-2">
                     <label class="text-sm font-semibold text-gray-700">Application</label>
                     <Dropdown 
                        v-model="form.application_id" 
                        :options="applicationOptions" 
                        optionLabel="label" 
                        optionValue="id" 
                        placeholder="Select candidate application..." 
                        :disabled="appsLoading" 
                        filter 
                        filterPlaceholder="Search by name or job..."
                        :filterFields="['label']"
                        class="w-full" 
                        :class="{'p-invalid': formFieldErrors?.application_id}" 
                        :loading="appsLoading"
                        emptyFilterMessage="No matching applications found"
                        emptyMessage="No applications available"
                    />
                     <small v-if="appsLoading" class="text-gray-500 text-xs">Loading applications...</small>
                     <small v-else-if="applicationOptions.length === 0" class="text-amber-600 text-xs">No open applications found. Make sure you have job applications to schedule interviews for.</small>
                     <small v-if="formFieldErrors?.application_id" class="text-red-600 text-xs">{{ formFieldErrors.application_id[0] }}</small>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">Start Time</label>
                         <Calendar v-model="form.scheduled_start" showTime hourFormat="12" class="w-full" :class="{'p-invalid': formFieldErrors?.scheduled_start}" />
                         <small v-if="formFieldErrors?.scheduled_start" class="text-red-600 text-xs">{{ formFieldErrors.scheduled_start[0] }}</small>
                     </div>
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">End Time</label>
                         <Calendar v-model="form.scheduled_end" showTime hourFormat="12" class="w-full" :class="{'p-invalid': formFieldErrors?.scheduled_end}" />
                         <small v-if="formFieldErrors?.scheduled_end" class="text-red-600 text-xs">{{ formFieldErrors.scheduled_end[0] }}</small>
                     </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">Interview Mode</label>
                         <Dropdown v-model="form.mode" :options="modeOptions" optionLabel="label" optionValue="value" class="w-full" :class="{'p-invalid': formFieldErrors?.mode}" />
                         <small v-if="formFieldErrors?.mode" class="text-red-600 text-xs">{{ formFieldErrors.mode[0] }}</small>
                     </div>
                     
                     <div class="flex flex-col gap-2">
                         <div v-if="form.mode === 'in_person'" class="flex flex-col gap-2">
                             <label class="text-sm font-semibold text-gray-700">Location</label>
                             <InputText v-model="form.location_text" placeholder="Clinic address or room number" class="w-full" :class="{'p-invalid': formFieldErrors?.location_text}" />
                             <small v-if="formFieldErrors?.location_text" class="text-red-600 text-xs">{{ formFieldErrors.location_text[0] }}</small>
                         </div>
                         <div v-else class="flex flex-col gap-2">
                             <label class="text-sm font-semibold text-gray-700">Meeting Link <span class="text-gray-400 font-normal">(Optional)</span></label>
                             <InputText v-model="form.meeting_link" placeholder="Auto-generated if left blank" class="w-full" :class="{'p-invalid': formFieldErrors?.meeting_link}" />
                             <small v-if="formFieldErrors?.meeting_link" class="text-red-600 text-xs">{{ formFieldErrors.meeting_link[0] }}</small>
                         </div>
                     </div>
                 </div>
             </div>

             <template #footer>
                <div class="flex justify-end gap-2 pt-4">
                    <Button label="Cancel" icon="pi pi-times" text severity="secondary" @click="closeScheduleModal" />
                    <Button label="Save Schedule" icon="pi pi-check" @click="submitSchedule" :loading="saving" />
                </div>
             </template>
        </Dialog>

    </div>
  </AppLayout>
</template>
