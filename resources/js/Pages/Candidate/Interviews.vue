<template>
  <AppLayout>
    <div class="w-full max-w-6xl mx-auto p-6 lg:p-10">
       <!-- Header -->
       <div class="flex flex-col md:flex-row justify-between items-end gap-4 mb-10">
          <div>
              <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Interviews</h1>
              <p class="text-gray-500 mt-2 text-lg">Manage your schedule and prepare for upcoming meetings.</p>
          </div>
          <div class="flex gap-3">
             <Button icon="pi pi-refresh" :loading="loading" @click="loadInterviews" severity="secondary" text rounded aria-label="Refresh" />
          </div>
       </div>

       <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
          <!-- Main: Upcoming -->
          <div class="lg:col-span-8 space-y-8">
             <section>
                <div class="flex items-center justify-between mb-6">
                   <h2 class="text-xl font-semibold text-gray-900">Upcoming Schedule</h2>
                   <RouterLink :to="{ name: 'candidate.applications' }" class="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">
                      View all applications
                   </RouterLink>
                </div>

                <div v-if="loading" class="flex justify-center p-12">
                   <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
                </div>
                
                <div v-else-if="error" class="p-4 rounded-lg bg-red-50 text-red-600 text-sm font-medium">
                   {{ error }}
                </div>

                <div v-else-if="upcoming.length === 0" class="flex flex-col items-center justify-center p-12 bg-white rounded-2xl border border-dashed border-gray-200 text-center">
                   <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                      <i class="pi pi-calendar text-gray-400 text-xl"></i>
                   </div>
                   <h3 class="text-gray-900 font-medium mb-1">No upcoming interviews</h3>
                   <p class="text-gray-500 text-sm max-w-xs mx-auto">When an employer schedules an interview, it will appear here.</p>
                </div>

                <div v-else class="space-y-4">
                   <div v-for="row in upcoming" :key="row.id" class="group relative bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                      <div class="p-5 sm:p-6 flex flex-col sm:flex-row gap-6">
                         <!-- Date Box -->
                         <div class="flex-shrink-0 flex sm:flex-col items-center justify-center sm:w-20 bg-blue-50 sm:bg-transparent rounded-lg sm:rounded-none px-4 py-2 sm:p-0 gap-2 sm:gap-0">
                            <span class="text-xs sm:text-sm font-bold text-blue-600 uppercase tracking-wider">{{ getMonth(row.scheduled_start) }}</span>
                            <span class="text-xl sm:text-3xl font-bold text-gray-900">{{ getDay(row.scheduled_start) }}</span>
                            <span class="text-xs text-gray-500 sm:hidden">{{ getTime(row.scheduled_start) }}</span>
                         </div>
                         
                         <!-- Content -->
                         <div class="flex-1 min-w-0 border-l-0 sm:border-l border-gray-100 sm:pl-6">
                            <div class="flex justify-between items-start mb-2">
                               <div>
                                  <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ row.jobTitle }}</h3>
                                  <div class="flex items-center gap-3 mt-2 text-sm text-gray-500">
                                     <span class="flex items-center gap-1.5">
                                        <i :class="getModeIcon(row.mode)" class="text-gray-400"></i>
                                        {{ row.modeLabel }}
                                     </span>
                                     <span class="hidden sm:inline text-gray-300">•</span>
                                     <span class="hidden sm:inline">{{ getTime(row.scheduled_start) }}</span>
                                  </div>
                               </div>
                               <Tag :value="row.status || 'Scheduled'" severity="success" class="bg-green-50 text-green-700 border border-green-100" />
                            </div>

                            <!-- Actions / Details -->
                            <div class="mt-5 pt-4 border-t border-gray-50 flex flex-wrap gap-4 items-center justify-between">
                               <div class="flex gap-4 text-sm">
                                  <a v-if="row.joinUrl" :href="row.joinUrl" target="_blank" class="inline-flex items-center gap-2 font-medium text-blue-600 hover:text-blue-700">
                                     <i class="pi pi-video"></i>
                                     Join Meeting
                                  </a>
                                  <div v-if="row.mode === 'in_person' && row.location_text" class="flex items-center gap-2 text-gray-600">
                                     <i class="pi pi-map-marker text-gray-400"></i>
                                     {{ row.location_text }}
                                  </div>
                               </div>

                               <div class="flex items-center gap-2 flex-wrap">
                                  <!-- Confirm / Decline (only when proposed/rescheduled) -->
                                  <template v-if="row.status === 'proposed' || row.status === 'rescheduled'">
                                    <Button label="Confirm" icon="pi pi-check" size="small" severity="success"
                                      :loading="respondingId === row.id"
                                      @click="respondInterview(row, 'confirmed')" />
                                    <Button label="Decline" icon="pi pi-times" size="small" severity="danger" outlined
                                      :loading="respondingId === row.id"
                                      @click="respondInterview(row, 'declined')" />
                                  </template>
                                  <Tag v-else-if="row.status === 'confirmed'" value="Confirmed" severity="success" />

                                  <RouterLink :to="{ name: 'candidate.applications.view', params: { id: String(row.application_id) } }">
                                     <Button label="View Application" size="small" text class="!px-0 hover:bg-transparent hover:underline" />
                                  </RouterLink>
                                  <Button
                                     icon="pi pi-calendar-plus"
                                     size="small"
                                     severity="secondary"
                                     outlined
                                     v-tooltip.top="'Add to calendar (.ics)'"
                                     @click="downloadIcs(row)"
                                     aria-label="Download calendar"
                                  />
                                  <!-- Feedback (completed interviews) -->
                                  <Button
                                    v-if="row.status === 'completed' || row.status === 'cancelled'"
                                    icon="pi pi-star"
                                    size="small"
                                    severity="secondary"
                                    outlined
                                    v-tooltip.top="'View feedback'"
                                    @click="viewFeedback(row)"
                                  />
                               </div>
                            </div>
                         </div>
                      </div>
                   </div>
                </div>
             </section>
          </div>

          <!-- Side: Tips -->
          <div class="lg:col-span-4 space-y-6">
             <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                <div class="flex items-center gap-2 mb-4">
                   <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-sm text-yellow-500">
                      <i class="pi pi-bolt"></i>
                   </div>
                   <h3 class="font-bold text-gray-900">Quick Prep</h3>
                </div>
                
                <ul class="space-y-4">
                   <li v-for="(tip, i) in tips" :key="i" class="flex gap-3 text-sm text-gray-600">
                      <i class="pi pi-check-circle text-green-500 mt-0.5 shrink-0"></i>
                      <span>{{ tip }}</span>
                   </li>
                </ul>

                <div class="mt-6 pt-5 border-t border-gray-200/60">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-2">Pro Tip</p>
                    <p class="text-sm text-gray-700 italic">"Open your interview link 5–10 minutes early to test your setup."</p>
                </div>
             </div>
          </div>
       </div>
    </div>
  </AppLayout>

  <!-- Feedback Dialog -->
  <Dialog v-model:visible="feedbackDialog" header="Interview Feedback" :style="{ width: '460px' }" modal>
    <div v-if="feedbackLoading" class="py-8 text-center text-slate-400"><i class="pi pi-spin pi-spinner text-2xl"></i></div>
    <div v-else-if="!feedbackData" class="py-8 text-center text-slate-400">
      <i class="pi pi-star text-3xl mb-3 block"></i>
      <p class="text-sm">No feedback has been submitted for this interview yet.</p>
    </div>
    <div v-else class="space-y-4 pt-2">
      <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-slate-600">Overall rating:</span>
        <div class="flex gap-0.5">
          <i v-for="n in 5" :key="n" :class="['pi pi-star-fill text-sm', n <= feedbackData.rating ? 'text-amber-400' : 'text-slate-200']"></i>
        </div>
        <span class="text-sm font-bold text-slate-900">{{ feedbackData.rating }}/5</span>
      </div>
      <div v-if="feedbackData.recommendation" class="flex items-center gap-2">
        <span class="text-sm font-semibold text-slate-600">Recommendation:</span>
        <span class="text-sm font-bold capitalize px-2 py-0.5 rounded-full"
          :class="feedbackData.recommendation === 'hire' || feedbackData.recommendation === 'recommend' ? 'bg-emerald-100 text-emerald-700' : feedbackData.recommendation === 'reject' || feedbackData.recommendation === 'do_not_recommend' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'">
          {{ feedbackData.recommendation.replace('_', ' ') }}
        </span>
      </div>
      <div v-if="feedbackData.notes" class="p-4 bg-slate-50 rounded-xl">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Notes</p>
        <p class="text-sm text-slate-700 leading-relaxed">{{ feedbackData.notes }}</p>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';
import Dialog from 'primevue/dialog';

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
  } catch {
    alert('Failed to download calendar file')
  }
}

const meName = ref("ME");
const loading = ref(false);
const error = ref("");
const interviews = ref([]);
const respondingId = ref(null);

// Feedback viewer
const feedbackDialog = ref(false)
const feedbackData = ref(null)
const feedbackLoading = ref(false)

async function viewFeedback(row) {
  feedbackDialog.value = true
  feedbackData.value = null
  feedbackLoading.value = true
  try {
    const res = await api.get(`/interviews/${row.id}/feedback`)
    feedbackData.value = res.data?.data ?? res.data ?? null
  } catch { feedbackData.value = null }
  finally { feedbackLoading.value = false }
}

async function respondInterview(row, response) {
  if (response === 'declined') {
    const { value: reason, isConfirmed } = await import('sweetalert2').then(({ default: Swal }) =>
      Swal.fire({
        title: 'Decline interview?',
        input: 'text',
        inputLabel: 'Reason (optional)',
        inputPlaceholder: 'e.g. scheduling conflict',
        showCancelButton: true,
        confirmButtonText: 'Decline',
        confirmButtonColor: '#ef4444',
      })
    )
    if (!isConfirmed) return
    respondingId.value = row.id
    try {
      const res = await api.post(`/interviews/${row.id}/respond`, { response: 'declined', reason: reason || '' })
      row.status = 'cancelled'
    } catch (e) { alert(e?.response?.data?.message || 'Failed') }
    finally { respondingId.value = null }
    return
  }
  respondingId.value = row.id
  try {
    await api.post(`/interviews/${row.id}/respond`, { response: 'confirmed' })
    row.status = 'confirmed'
  } catch (e) { alert(e?.response?.data?.message || 'Failed') }
  finally { respondingId.value = null }
}

const tips = [
  "Review the job description and match your experience.",
  "Prepare 2–3 real cases showing clinical decision-making.",
  "Test your camera and microphone beforehand.",
  "Have your CV and licenses ready to share."
];

function unwrap(resData) {
  return resData?.data ?? resData;
}
function unwrapList(resData) {
  const body = unwrap(resData);
  if (body?.data && Array.isArray(body.data)) return body.data;
  if (Array.isArray(body)) return body;
  return [];
}

// Date helpers
function getMonth(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-US', { month: 'short' });
}
function getDay(d) {
    if (!d) return '';
    return new Date(d).getDate();
}
function getTime(d) {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}
function getModeIcon(mode) {
    switch(mode) {
        case 'video': return 'pi pi-video';
        case 'phone': return 'pi pi-phone';
        case 'in_person': return 'pi pi-map-marker';
        default: return 'pi pi-calendar';
    }
}

function normalizeRow(i) {
  const app = i?.application || null;
  const job = app?.job || null;
  const joinUrl = i?.meeting_link || i?.provider_join_url || null;

  return {
    id: i?.id,
    application_id: i?.application_id,
    scheduled_start: i?.scheduled_start,
    scheduled_end: i?.scheduled_end,
    mode: i?.mode,
    location_text: i?.location_text,
    status: i?.status,
    joinUrl,
    modeLabel:
      i?.mode === "video"
        ? "Video Call"
        : i?.mode === "phone"
          ? "Phone Call"
          : i?.mode === "in_person"
            ? "In-person"
            : "Interview",
    jobTitle: job?.title || 'Job Application',
  };
}

async function loadInterviews() {
  loading.value = true;
  error.value = "";
  try {
    const res = await api.get("/interviews"); // -> /api/interviews
    const list = unwrapList(res.data);
    interviews.value = list.map(normalizeRow);
  } catch (e) {
    const msg = e?.response?.data?.message || e?.message || "Failed to load interviews.";
    error.value = msg;
    interviews.value = [];
  } finally {
    loading.value = false;
  }
}

const upcoming = computed(() => {
  const now = new Date();
  return interviews.value
    .slice()
    .sort((a, b) => new Date(a.scheduled_start || 0) - new Date(b.scheduled_start || 0))
    .filter((i) => {
      const dt = new Date(i.scheduled_start || 0);
      return Number.isFinite(dt.getTime()) ? dt >= now : true;
    });
});

onMounted(() => {
    loadInterviews();
});
</script>
