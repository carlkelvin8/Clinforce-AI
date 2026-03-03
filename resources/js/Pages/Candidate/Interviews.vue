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

                               <RouterLink :to="{ name: 'candidate.applications.view', params: { id: String(row.application_id) } }">
                                  <Button label="View Application" size="small" text class="!px-0 hover:bg-transparent hover:underline" />
                               </RouterLink>
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
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressSpinner from 'primevue/progressspinner';

const meName = ref("ME");
const loading = ref(false);
const error = ref("");
const interviews = ref([]);

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
