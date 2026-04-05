<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

// Mask last name — show first name + last initial only
function maskLastName(fullName) {
  if (!fullName) return 'Candidate'
  const parts = String(fullName).trim().split(/\s+/)
  if (parts.length < 2) return fullName
  return `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}.`
}

// PrimeVue
import Card from "primevue/card";
import Button from "primevue/button";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import DatePicker from "primevue/datepicker";
import Message from "primevue/message";
import Textarea from "primevue/textarea";
import Select from "primevue/select";

/* =========================
   Sidebar + auth (UNCHANGED)
   ========================= */
const route = useRoute();
const router = useRouter();

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
    
    // The employer/agency response is { applications: { data: [...] }, ... }
    if (data?.applications?.data && Array.isArray(data.applications.data)) {
        applications.value = data.applications.data;
    } else if (Array.isArray(data?.applications)) {
        applications.value = data.applications;
    } else {
        applications.value = unwrapList(res.data);
    }
    
    // Only show applications that are not rejected or withdrawn
    applications.value = applications.value.filter(app => !['rejected', 'withdrawn'].includes(app.status));
    
    console.log('Loaded applications:', applications.value.length);
    
    // Auto-select application if candidate_id matches
    if (route.query.candidate_id) {
      const candId = String(route.query.candidate_id);
      const match = applications.value.find(a => 
        String(a?.applicant_id || a?.applicant_user_id || a?.user_id) === candId
      );
      if (match) {
        form.value.application_id = Number(match.id);
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
    const cand = maskLastName(
      a?.applicant_name ||
      a?.applicant?.name ||
      a?.user?.name ||
      a?.applicant_full_name ||
      "Candidate"
    );

    return {
      appId: Number(a.id),
      label: `${cand} — ${jobTitle} (App #${a.id})`,
    };
  });
});

const selectedApplication = computed(() => {
  if (!form.value.application_id) return null;
  return applications.value.find(a => Number(a.id) === form.value.application_id);
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

    const appId = Number(form.value.application_id);
    if (!appId || appId <= 0) {
      formError.value = "Invalid application selected. Please choose again.";
      return;
    }

    const start = form.value.scheduled_start;
    const end = form.value.scheduled_end;

    if (!start || !end) {
      formError.value = "Start and end are required.";
      return;
    }

    // Send local datetime string (not UTC) so server's after:now check works correctly
    function toLocalISO(d) {
      const pad = n => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    const payload = {
      scheduled_start: toLocalISO(start),
      scheduled_end: toLocalISO(end),
      mode: form.value.mode,
      meeting_link: form.value.meeting_link?.trim() || null,
      location_text: form.value.location_text?.trim() || null,
    };

    const res = await api.post(`/applications/${appId}/interviews`, payload);
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

// No-show tracking
const noShowId = ref(null)
async function markNoShow(interview) {
  noShowId.value = interview.id
  try {
    await api.post(`/interviews/${interview.id}/no-show`)
    interview.no_show = true
    interview.status = 'completed'
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed')
  } finally { noShowId.value = null }
}

// ICS download
async function downloadIcs(interview) {
  try {
    const res = await api.get(`/interviews/${interview.id}/ics`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const a = document.createElement('a')
    a.href = url
    a.download = `interview-${interview.id}.ics`
    document.body.appendChild(a)
    a.click()
    a.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    alert('Failed to download calendar file')
  }
}

// View mode: list | calendar
const viewMode = ref('list')
const calendarDate = ref(new Date())

const calendarTitle = computed(() => {
  return calendarDate.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
})

function prevMonth() {
  const d = new Date(calendarDate.value)
  d.setMonth(d.getMonth() - 1)
  calendarDate.value = d
}
function nextMonth() {
  const d = new Date(calendarDate.value)
  d.setMonth(d.getMonth() + 1)
  calendarDate.value = d
}

const calendarCells = computed(() => {
  const year = calendarDate.value.getFullYear()
  const month = calendarDate.value.getMonth()
  const firstDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const today = new Date()

  const cells = []
  // leading empty cells
  for (let i = 0; i < firstDay; i++) {
    const prevDate = new Date(year, month, -firstDay + i + 1)
    cells.push({ day: prevDate.getDate(), isCurrentMonth: false, isToday: false, interviews: [], date: prevDate })
  }
  for (let d = 1; d <= daysInMonth; d++) {
    const date = new Date(year, month, d)
    const isToday = date.toDateString() === today.toDateString()
    const dayInterviews = interviews.value.filter(iv => {
      const s = parseDate(iv.scheduled_start)
      return s && s.getFullYear() === year && s.getMonth() === month && s.getDate() === d
    })
    cells.push({ day: d, isCurrentMonth: true, isToday, interviews: dayInterviews, date })
  }
  // trailing cells to fill 6 rows
  const remaining = 42 - cells.length
  for (let i = 1; i <= remaining; i++) {
    const nextDate = new Date(year, month + 1, i)
    cells.push({ day: i, isCurrentMonth: false, isToday: false, interviews: [], date: nextDate })
  }
  return cells
})

function candidateNameFromInterview(iv) {
  const name = iv?.application?.applicant?.name ||
    iv?.application?.applicant_name ||
    `App #${iv?.application_id || iv?.id}`
  return maskLastName(name)
}

function calEventClass(status) {
  const map = {
    proposed: 'bg-blue-100 text-blue-700',
    confirmed: 'bg-indigo-100 text-indigo-700',
    rescheduled: 'bg-amber-100 text-amber-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-600 line-through',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

// Feedback modal
const feedbackOpen = ref(false)
const feedbackInterview = ref(null)
const feedbackForm = ref({ rating: 5, notes: '', recommendation: 'neutral' })
const savingFeedback = ref(false)
const feedbackError = ref('')

const ratingOptions = [1,2,3,4,5].map(v => ({ label: `${v} — ${{ 1:'Poor', 2:'Fair', 3:'Good', 4:'Very Good', 5:'Excellent' }[v]}`, value: v }))
const recommendationOptions = [
  { label: 'Recommend', value: 'recommend' },
  { label: 'Neutral', value: 'neutral' },
  { label: 'Do not recommend', value: 'do_not_recommend' },
]

function openFeedback(interview) {
  feedbackInterview.value = interview
  feedbackForm.value = { rating: 5, notes: '', recommendation: 'neutral' }
  feedbackError.value = ''
  feedbackOpen.value = true
}

async function submitFeedback() {
  feedbackError.value = ''
  savingFeedback.value = true
  try {
    await api.post(`/interviews/${feedbackInterview.value.id}/feedback`, feedbackForm.value)
    feedbackOpen.value = false
    await loadSchedule()
  } catch (e) {
    feedbackError.value = e?.response?.data?.message || 'Failed to save feedback'
  } finally {
    savingFeedback.value = false
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

        <!-- View toggle -->
        <div class="flex gap-2">
          <button
            @click="viewMode = 'list'"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all"
            :class="viewMode === 'list' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
          >
            <i class="pi pi-list mr-1.5"></i>List
          </button>
          <button
            @click="viewMode = 'calendar'"
            class="px-4 py-2 rounded-xl text-sm font-semibold transition-all"
            :class="viewMode === 'calendar' ? 'bg-blue-600 text-white shadow' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'"
          >
            <i class="pi pi-calendar mr-1.5"></i>Calendar
          </button>
        </div>

        <!-- Filters (list only) -->
        <div v-if="viewMode === 'list'" class="bg-white p-2 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-2">
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
                 <Select 
                    v-model="jobFilter" 
                    :options="jobOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    class="w-full md:w-48 !border-0 !shadow-none" 
                    placeholder="All Jobs"
                 />
                 <Select 
                    v-model="timeFilter" 
                    :options="timeOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    class="w-full md:w-40 !border-0 !shadow-none" 
                 />
             </div>
        </div>

        <!-- Schedule table -->
        <div v-if="viewMode === 'list'" class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <span class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Schedule</span>
                <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded border border-gray-200">{{ filtered.length }} item(s)</span>
            </div>
            
            <DataTable :value="filtered" :loading="loading" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
                <template #empty>
                    <div class="text-center py-16">
                        <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-calendar text-blue-300 text-3xl"></i>
                        </div>
                        <p class="text-gray-700 font-semibold mb-1">No interviews scheduled</p>
                        <p class="text-gray-400 text-sm mb-4">Schedule your first interview to get started.</p>
                        <Button label="Schedule Interview" icon="pi pi-plus" size="small" @click="openScheduleModal" />
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
                                maskLastName(
                                  data?.application?.applicant?.name ||
                                  data?.application?.user?.name ||
                                  data?.application?.applicant_name ||
                                  "Candidate"
                                )
                            }}
                        </div>
                        <div class="text-xs text-gray-600 mt-1 flex flex-col gap-1">
                            <span class="flex items-center gap-1"><i class="pi pi-id-card text-[10px]"></i> App #{{ data?.application?.id || data?.application_id || "—" }}</span>
                        </div>
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

                <Column header="Actions" style="min-width: 260px">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- View Application -->
                            <Button
                                label="Application"
                                icon="pi pi-file-edit"
                                size="small"
                                outlined
                                class="!text-xs !px-3 !py-1.5 !border-slate-200 !text-slate-700 hover:!bg-blue-50 hover:!border-blue-300 hover:!text-blue-700 transition-colors"
                                @click="router.push({ name: 'applicants.view', params: { id: data?.application?.id || data?.application_id } })"
                                v-if="data?.application?.id || data?.application_id"
                            />
                            <!-- Join Meeting -->
                            <a
                                v-if="data?.meeting_link"
                                :href="data.meeting_link"
                                target="_blank"
                                rel="noreferrer"
                                class="no-underline"
                            >
                                <Button
                                    icon="pi pi-video"
                                    size="small"
                                    class="!text-xs !px-3 !py-1.5"
                                    aria-label="Join meeting"
                                    v-tooltip.top="'Join meeting'"
                                />
                            </a>
                            <!-- ICS Download -->
                            <Button
                                icon="pi pi-calendar-plus"
                                size="small"
                                severity="secondary"
                                outlined
                                class="!text-xs !px-2 !py-1.5"
                                aria-label="Add to calendar"
                                v-tooltip.top="'Download .ics'"
                                @click="downloadIcs(data)"
                            />
                            <!-- No-show -->
                            <Button
                                v-if="data?.status !== 'cancelled' && data?.status !== 'completed' && !data?.no_show"
                                icon="pi pi-user-minus"
                                size="small"
                                severity="warn"
                                outlined
                                class="!text-xs !px-2 !py-1.5"
                                v-tooltip.top="'Mark as no-show'"
                                :loading="noShowId === data?.id"
                                @click="markNoShow(data)"
                            />
                            <Tag v-if="data?.no_show" value="No-show" severity="warn" class="text-[10px]" />
                            <!-- Feedback (completed interviews) -->
                            <Button
                                v-if="data?.status === 'completed'"
                                icon="pi pi-star"
                                size="small"
                                severity="warn"
                                outlined
                                class="!text-xs !px-2 !py-1.5"
                                aria-label="Add feedback"
                                v-tooltip.top="'Add feedback'"
                                @click="openFeedback(data)"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Calendar view -->
        <div v-if="viewMode === 'calendar'" class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
          <!-- Month nav -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
            <button @click="prevMonth" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
              <i class="pi pi-chevron-left text-gray-600"></i>
            </button>
            <span class="text-base font-bold text-gray-900">
              {{ calendarTitle }}
            </span>
            <button @click="nextMonth" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
              <i class="pi pi-chevron-right text-gray-600"></i>
            </button>
          </div>
          <!-- Day headers -->
          <div class="grid grid-cols-7 border-b border-gray-100">
            <div v-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d"
              class="py-2 text-center text-xs font-bold text-gray-400 uppercase tracking-wider">
              {{ d }}
            </div>
          </div>
          <!-- Calendar grid -->
          <div class="grid grid-cols-7">
            <div
              v-for="(cell, idx) in calendarCells"
              :key="idx"
              class="min-h-[100px] border-b border-r border-gray-100 p-1.5 last:border-r-0"
              :class="cell.isToday ? 'bg-blue-50/40' : cell.isCurrentMonth ? 'bg-white' : 'bg-gray-50/60'"
            >
              <div class="flex items-center justify-between mb-1">
                <span
                  class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full"
                  :class="cell.isToday ? 'bg-blue-600 text-white' : cell.isCurrentMonth ? 'text-gray-700' : 'text-gray-300'"
                >
                  {{ cell.day }}
                </span>
              </div>
              <div class="space-y-1">
                <div
                  v-for="iv in cell.interviews"
                  :key="iv.id"
                  class="text-[10px] font-medium px-1.5 py-0.5 rounded truncate cursor-pointer hover:opacity-80 transition-opacity"
                  :class="calEventClass(iv.status)"
                  :title="`${candidateNameFromInterview(iv)} — ${iv.application?.job?.title || ''}`"
                  @click="openFeedback(iv)"
                >
                  {{ fmtDate(iv.scheduled_start).time }} {{ candidateNameFromInterview(iv) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Feedback Modal -->
        <Dialog v-model:visible="feedbackOpen" modal header="Interview Feedback" :style="{ width: '480px' }">
          <div class="space-y-4 py-2">
            <div v-if="feedbackError" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm">{{ feedbackError }}</div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Rating</label>
              <Select v-model="feedbackForm.rating" :options="ratingOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Recommendation</label>
              <Select v-model="feedbackForm.recommendation" :options="recommendationOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Notes</label>
              <Textarea v-model="feedbackForm.notes" rows="4" placeholder="Observations, strengths, concerns..." class="w-full !rounded-xl" />
            </div>
          </div>
          <template #footer>
            <Button label="Cancel" text severity="secondary" @click="feedbackOpen = false" />
            <Button label="Save Feedback" icon="pi pi-check" :loading="savingFeedback" @click="submitFeedback" />
          </template>
        </Dialog>

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
                     <Select 
                        v-model="form.application_id" 
                        :options="applicationOptions" 
                        optionLabel="label" 
                        optionValue="appId" 
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

                 <!-- Selected Application Detail -->
                 <div v-if="selectedApplication" class="p-4 bg-slate-50 rounded-2xl border border-slate-100 animate-fade-in">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Candidate Details</span>
                        <Tag :value="selectedApplication.status" severity="secondary" class="uppercase text-[10px]" rounded />
                    </div>
                    <div class="flex items-start gap-4">
                        <Avatar :image="selectedApplication.applicant?.avatar_url" icon="pi pi-user" size="large" shape="circle" class="bg-blue-100 text-blue-600" />
                        <div class="flex-1">
                            <div class="text-lg font-black text-slate-900 leading-none mb-1">
                                {{ maskLastName(selectedApplication.applicant?.name || selectedApplication.applicant_name || "Candidate") }}
                            </div>
                            <div class="text-sm font-medium text-slate-500 mb-2">{{ selectedApplication.job?.title || "Job Application" }}</div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600">
                                    <i class="pi pi-id-card text-slate-400"></i>
                                    App #{{ selectedApplication.id }}
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">Start Time</label>
                         <DatePicker v-model="form.scheduled_start" showTime hourFormat="12" class="w-full" :class="{'p-invalid': formFieldErrors?.scheduled_start}" />
                         <small v-if="formFieldErrors?.scheduled_start" class="text-red-600 text-xs">{{ formFieldErrors.scheduled_start[0] }}</small>
                     </div>
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">End Time</label>
                         <DatePicker v-model="form.scheduled_end" showTime hourFormat="12" class="w-full" :class="{'p-invalid': formFieldErrors?.scheduled_end}" />
                         <small v-if="formFieldErrors?.scheduled_end" class="text-red-600 text-xs">{{ formFieldErrors.scheduled_end[0] }}</small>
                     </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="flex flex-col gap-2">
                         <label class="text-sm font-semibold text-gray-700">Interview Mode</label>
                         <Select v-model="form.mode" :options="modeOptions" optionLabel="label" optionValue="value" class="w-full" :class="{'p-invalid': formFieldErrors?.mode}" />
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
