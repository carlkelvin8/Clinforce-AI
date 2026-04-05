<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 font-sans pb-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
          <div class="flex items-center gap-4">
            <Avatar :image="meAvatarUrl" :label="!meAvatarUrl ? meInitials : null" size="xlarge" shape="circle" class="shadow-sm border border-slate-200 bg-white text-blue-600" />
            <div>
              <h1 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">
                Good Day{{ firstName ? ', ' + firstName : '' }}
              </h1>
              <p class="text-slate-500 mt-2 text-lg">
                Here's what's happening with your job search today.
              </p>
            </div>
          </div>

          <div class="flex gap-3">
            <RouterLink :to="{ name: 'candidate.jobs' }">
              <Button label="Browse Jobs" icon="pi pi-search" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700 !rounded-lg !px-5" />
            </RouterLink>
            <RouterLink :to="{ name: 'candidate.profile' }">
              <Button label="Edit Profile" icon="pi pi-user-edit" severity="secondary" outlined class="!bg-white !text-slate-700 !border-slate-300 hover:!bg-slate-50 !rounded-lg" />
            </RouterLink>
          </div>
        </div>

        <!-- ── Next Steps / Action Prompts ──────────────────────────────── -->
        <div v-if="nextSteps.length" class="mb-10 space-y-3">
          <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-3">Next steps</h2>
          <div v-for="step in nextSteps" :key="step.id"
            class="flex items-center gap-4 p-4 rounded-2xl border transition-all hover:-translate-y-0.5 hover:shadow-md"
            :class="step.urgent ? 'bg-amber-50 border-amber-200' : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700'">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
              :class="step.urgent ? 'bg-amber-100 text-amber-600' : 'bg-blue-50 text-blue-600'">
              <i :class="['pi text-sm', step.icon]"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm">{{ step.title }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ step.desc }}</div>
            </div>
            <RouterLink :to="step.to" class="flex-shrink-0">
              <button class="text-xs font-bold px-3 py-1.5 rounded-lg transition-colors"
                :class="step.urgent ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-blue-600 text-white hover:bg-blue-700'">
                {{ step.cta }}
              </button>
            </RouterLink>
          </div>
        </div>

        <!-- ── Profile Strength Tips ──────────────────────────────────────── -->
        <div v-if="profileTips.length && stats.profileCompleteness < 100" class="mb-10">
          <div class="bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl border border-indigo-100 dark:border-slate-700 p-5">
            <div class="flex items-center gap-2 mb-4">
              <i class="pi pi-lightbulb text-indigo-500"></i>
              <h2 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest">Profile tips</h2>
              <span class="ml-auto text-xs font-bold text-indigo-500">{{ stats.profileCompleteness }}% complete</span>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
              <div v-for="tip in profileTips" :key="tip.field"
                class="flex items-start gap-3 p-3 bg-white dark:bg-slate-900 rounded-xl border border-indigo-100 dark:border-slate-700">
                <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <i :class="['pi text-xs text-indigo-600', tip.icon]"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ tip.title }}</div>
                  <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ tip.benefit }}</div>
                </div>
              </div>
            </div>
            <RouterLink :to="{ name: 'candidate.profile' }" class="mt-4 block">
              <button class="w-full py-2 text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                Complete your profile →
              </button>
            </RouterLink>
          </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
          <template v-if="!apps.length && jobsLoading">
            <SkeletonCard v-for="n in 4" :key="n" type="kpi" />
          </template>
          <template v-else>
          <!-- Stat 1 -->
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <div class="flex items-start justify-between mb-4">
              <div class="p-2 bg-blue-50 dark:bg-blue-900/40 rounded-lg text-blue-600">
                <i class="pi pi-briefcase text-xl"></i>
              </div>
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Applications</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">{{ stats.active }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Active applications</div>
          </div>

          <!-- Stat 2 -->
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <div class="flex items-start justify-between mb-4">
              <div class="p-2 bg-purple-50 dark:bg-purple-900/40 rounded-lg text-purple-600">
                <i class="pi pi-calendar text-xl"></i>
              </div>
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Interviews</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">{{ stats.upcomingInterviews }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Upcoming events</div>
          </div>

          <!-- Stat 3 -->
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <div class="flex items-start justify-between mb-4">
              <div class="p-2 bg-green-50 dark:bg-green-900/40 rounded-lg text-green-600">
                <i class="pi pi-check-circle text-xl"></i>
              </div>
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Profile</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">{{ stats.profileCompleteness }}%</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">Completion rate</div>
          </div>

          <!-- Stat 4 -->
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow duration-300">
            <div class="flex items-start justify-between mb-4">
              <div class="p-2 bg-orange-50 dark:bg-orange-900/40 rounded-lg text-orange-600">
                <i class="pi pi-bolt text-xl"></i>
              </div>
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Match</span>
            </div>
            <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">{{ stats.matchStrength }}</div>
            <div class="text-sm text-slate-500 dark:text-slate-400">AI Score</div>
          </div>
          </template>
        </div>

        <!-- Pipeline Progress -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 mb-10">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
              <i class="pi pi-chart-bar text-slate-400"></i>
              Application Pipeline
            </h2>
            <RouterLink :to="{ name: 'candidate.myapplications' }" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
              View all <i class="pi pi-arrow-right text-xs ml-1"></i>
            </RouterLink>
          </div>
          <div v-if="!apps.length" class="text-center py-6">
            <div class="w-12 h-12 bg-slate-50 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="pi pi-send text-slate-300 dark:text-slate-500 text-xl"></i>
            </div>
            <p class="text-slate-500 text-sm">No applications yet. Start applying!</p>
            <RouterLink :to="{ name: 'candidate.jobs' }">
              <Button label="Browse Jobs" text class="mt-2 !text-blue-600" size="small" />
            </RouterLink>
          </div>
          <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="stage in pipeline" :key="stage.key" class="flex flex-col gap-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500">{{ stage.label }}</span>
                <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', stage.light, stage.text]">{{ stage.count }}</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                <div :class="['h-full rounded-full transition-all duration-700', stage.color]" :style="{ width: stage.pct + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Left Column (Main) -->
          <div class="lg:col-span-2 space-y-8">
            
            <!-- Recommended Roles -->
            <section>
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-900">Recommended for you</h2>
                <RouterLink :to="{ name: 'candidate.jobs' }" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                  View all jobs <i class="pi pi-arrow-right text-xs ml-1"></i>
                </RouterLink>
              </div>

              <div v-if="jobsLoading" class="py-12 text-center text-slate-500 bg-white rounded-2xl border border-slate-100">
                <i class="pi pi-spin pi-spinner text-2xl mb-3 text-blue-500"></i>
                <p>Finding matches...</p>
              </div>

              <div v-else-if="jobs.length === 0" class="py-12 text-center text-slate-500 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
                <i class="pi pi-search text-3xl mb-3 text-slate-300 dark:text-slate-600"></i>
                <p>No specific recommendations yet.</p>
                <RouterLink :to="{ name: 'candidate.jobs' }">
                  <Button label="Browse all jobs" text class="mt-2 !text-blue-600" />
                </RouterLink>
              </div>

              <div v-else class="space-y-4">
                <RouterLink
                  v-for="j in jobs"
                  :key="j.id"
                  :to="{ name: 'candidate.jobs.view', params: { id: j.id } }"
                  class="block group"
                >
                  <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-md transition-all duration-300 relative overflow-hidden">
                    <div class="flex justify-between items-start">
                      <div class="flex gap-4">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100 dark:border-blue-800 shrink-0">
                          {{ (j.title || 'J')[0] }}
                        </div>
                        <div>
                          <h3 class="font-bold text-slate-900 dark:text-slate-100 group-hover:text-blue-600 transition-colors">{{ j.title }}</h3>
                          <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1 text-sm text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1">
                              <i class="pi pi-map-marker text-xs"></i>
                              {{ [j.city, j.country_code].filter(Boolean).join(', ') || 'Remote' }}
                            </span>
                            <span class="flex items-center gap-1">
                              <i class="pi pi-briefcase text-xs"></i>
                              {{ j.employment_type?.replace('_', ' ') || 'Full time' }}
                            </span>
                          </div>
                        </div>
                      </div>
                      <Tag :value="j.status || 'Active'" severity="success" class="!bg-green-50 !text-green-700 !border-green-100 !font-medium" rounded></Tag>
                    </div>
                  </div>
                </RouterLink>
              </div>
            </section>

            <!-- Recent Activity -->
            <section>
              <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6">Recent Activity</h2>
              <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div v-if="apps.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
                  No recent applications. Start applying!
                </div>
                <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
                  <div v-for="a in apps.slice(0, 3)" :key="a.id" class="p-5 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center justify-between group">
                    <div class="flex items-center gap-4">
                      <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:bg-white dark:group-hover:bg-slate-600 group-hover:shadow-sm transition-all">
                        <i class="pi pi-file"></i>
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 dark:text-slate-100">{{ a.job?.title || 'Unknown Job' }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Applied {{ relativeTime(a.created_at) }}</div>
                      </div>
                    </div>
                    <Tag :value="a.status" :severity="getStatusSeverity(a.status)" rounded></Tag>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Right Column (Sidebar) -->
          <div class="space-y-8">
            
            <!-- Upcoming Interviews -->
            <section class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 shadow-sm">
              <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-slate-900 dark:text-slate-100">Upcoming Interviews</h3>
                <RouterLink :to="{ name: 'candidate.interviews' }" class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide hover:text-blue-700">View All</RouterLink>
              </div>

              <div v-if="interviews.length === 0" class="text-center py-6">
                <div class="w-12 h-12 bg-slate-50 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-3">
                  <i class="pi pi-calendar-times text-slate-400 dark:text-slate-500"></i>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">No interviews scheduled yet.</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="i in interviews" :key="i.id" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-700">
                  <div class="w-10 h-10 rounded-lg bg-white dark:bg-slate-800 flex flex-col items-center justify-center text-xs font-bold shadow-sm text-slate-900 dark:text-slate-100 border border-slate-100 dark:border-slate-700 shrink-0">
                    <span>{{ new Date(i.scheduled_start).getDate() }}</span>
                    <span class="text-[10px] text-slate-400 uppercase">{{ new Date(i.scheduled_start).toLocaleDateString('en-US', { month: 'short' }) }}</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate">{{ i.application?.job?.title || 'Interview' }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(i.scheduled_start).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }} · {{ i.mode || 'Online' }}</div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Profile Completion -->
            <section class="bg-indigo-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
              <!-- Decorative circles -->
              <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-500 rounded-full opacity-50"></div>
              <div class="absolute bottom-10 -left-10 w-24 h-24 bg-indigo-500 rounded-full opacity-30"></div>
              
              <div class="relative z-10">
                <div class="flex justify-between items-end mb-4">
                  <div class="flex items-center gap-3">
                    <Avatar :image="meAvatarUrl" :label="!meAvatarUrl ? meInitials : null" shape="circle" class="bg-indigo-500 text-white border border-indigo-400 shrink-0" />
                    <h3 class="font-bold text-lg">Profile Status</h3>
                  </div>
                  <span class="text-2xl font-bold">{{ stats.profileCompleteness }}%</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-indigo-800 rounded-full h-2 mb-6 overflow-hidden">
                  <div class="bg-white h-2 rounded-full transition-all duration-1000" :style="{ width: `${stats.profileCompleteness}%` }"></div>
                </div>

                <div class="space-y-3 mb-6">
                  <div class="flex items-center gap-3 text-sm text-indigo-100">
                    <div class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center shrink-0">
                      <i class="pi pi-check text-xs text-white"></i>
                    </div>
                    <span>Basic details completed</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm text-indigo-100">
                    <div class="w-5 h-5 rounded-full bg-indigo-500 flex items-center justify-center shrink-0">
                      <i class="pi pi-check text-xs text-white"></i>
                    </div>
                    <span>CV Uploaded</span>
                  </div>
                  <div class="flex items-center gap-3 text-sm text-white font-medium">
                    <div class="w-5 h-5 rounded-full border-2 border-white flex items-center justify-center shrink-0">
                      <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                    </div>
                    <span>Add your certifications</span>
                  </div>
                </div>

                <RouterLink :to="{ name: 'candidate.profile' }">
                  <button class="w-full py-2.5 bg-white text-indigo-600 font-bold rounded-xl hover:bg-indigo-50 transition-colors text-sm">
                    Complete Profile
                  </button>
                </RouterLink>
              </div>
            </section>

          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRouter } from "vue-router";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";
import { me, getCachedUser } from "@/lib/auth";

import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import SkeletonCard from '@/Components/SkeletonCard.vue';

const meName = ref("ME");
const firstName = computed(() => (meName.value || "").split(" ")[0] || "");

const apps = ref([]);
const jobs = ref([]);
const jobsLoading = ref(false);

const meUser = ref(null);

function unwrap(resData) {
  return resData?.data ?? resData;
}
function unwrapPaginated(resData) {
  const body = unwrap(resData);
  if (body?.data && Array.isArray(body.data)) return body.data;
  if (Array.isArray(body)) return body;
  return [];
}
function relativeTime(v) {
  if (!v) return "—";
  const d = new Date(v);
  const diff = Date.now() - d.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins} min ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs} hours ago`;
  const days = Math.floor(hrs / 24);
  return `${days} days ago`;
}

function getStatusSeverity(status) {
  switch (status) {
    case 'interview': return 'warning';
    case 'hired': return 'success';
    case 'rejected': return 'danger';
    case 'withdrawn': return 'secondary';
    default: return 'info';
  }
}

function calcProfileCompleteness(user) {
  if (!user) return 0;
  const ap = user.applicant_profile || user.applicantProfile || null;
  if (!ap) return 0;

  const fields = [
    ap.first_name,
    ap.last_name,
    ap.headline,
    ap.summary,
    String(ap.years_experience ?? ""),
    ap.country_code,
    ap.city,
  ];
  const filled = fields.filter((v) => String(v || "").trim().length > 0).length;
  return Math.round((filled / fields.length) * 100);
}

const stats = computed(() => {
  const active = apps.value.filter((a) => !["rejected", "withdrawn"].includes(a.status)).length;
  const interviewStage = apps.value.filter((a) => a.status === "interview").length;
  const upcomingInterviews = interviewStage;
  const profileCompleteness = calcProfileCompleteness(meUser.value);
  const matchStrength = active > 0 ? "High" : "Low";
  return { active, interviewStage, upcomingInterviews, profileCompleteness, matchStrength };
});

// Next steps prompts
const nextSteps = computed(() => {
  const steps = []
  const ap = meUser.value?.applicant_profile || meUser.value?.applicantProfile || null

  // Pending interview confirmations
  const pendingInterviews = interviews.value.filter(i => i.status === 'proposed' || i.status === 'rescheduled')
  if (pendingInterviews.length) {
    steps.push({
      id: 'interviews',
      urgent: true,
      icon: 'pi-calendar',
      title: `${pendingInterviews.length} interview${pendingInterviews.length > 1 ? 's' : ''} awaiting confirmation`,
      desc: 'Confirm or decline your scheduled interviews',
      cta: 'Respond now',
      to: { name: 'candidate.interviews' },
    })
  }

  // Profile completeness
  const completeness = calcProfileCompleteness(meUser.value)
  if (completeness < 60) {
    steps.push({
      id: 'profile',
      urgent: false,
      icon: 'pi-user-edit',
      title: 'Complete your profile to get more matches',
      desc: `Your profile is ${completeness}% complete. Employers prefer complete profiles.`,
      cta: 'Complete now',
      to: { name: 'candidate.profile' },
    })
  }

  // No resume uploaded
  if (!ap?.avatar && !meUser.value?.avatar_url) {
    steps.push({
      id: 'photo',
      urgent: false,
      icon: 'pi-camera',
      title: 'Add a profile photo',
      desc: 'Profiles with photos get 3x more views from employers',
      cta: 'Add photo',
      to: { name: 'candidate.settings' },
    })
  }

  // No applications yet
  if (apps.value.length === 0) {
    steps.push({
      id: 'apply',
      urgent: false,
      icon: 'pi-send',
      title: 'Start applying to jobs',
      desc: 'Browse open healthcare roles and submit your first application',
      cta: 'Browse jobs',
      to: { name: 'candidate.jobs' },
    })
  }

  return steps.slice(0, 3)
})

// Profile strength tips
const profileTips = computed(() => {
  const tips = []
  const ap = meUser.value?.applicant_profile || meUser.value?.applicantProfile || null
  if (!ap) return tips

  if (!ap.headline) tips.push({ field: 'headline', icon: 'pi-tag', title: 'Add a professional headline', benefit: 'Candidates with headlines get 3x more employer views' })
  if (!ap.summary) tips.push({ field: 'summary', icon: 'pi-align-left', title: 'Write a short summary', benefit: 'Helps employers understand your background quickly' })
  if (!ap.years_experience) tips.push({ field: 'experience', icon: 'pi-briefcase', title: 'Add years of experience', benefit: 'Improves your match score on job listings' })
  if (!ap.city) tips.push({ field: 'city', icon: 'pi-map-marker', title: 'Add your city', benefit: 'Employers filter by location — be discoverable' })
  if (!ap.country) tips.push({ field: 'country', icon: 'pi-flag', title: 'Set your country', benefit: 'Required for international job matching' })

  return tips.slice(0, 4)
})

const upcomingInterviewRows = computed(() => {
  return apps.value
    .filter((a) => a.status === "interview")
    .slice(0, 3)
    .map((a) => ({
      id: a.id,
      title: a.job?.title || 'Job Application',
      when: "Scheduled",
    }));
});

const meAvatarUrl = ref(null);
const meInitials = ref("ME");

async function fetchMe() {
  try {
    const res = await api.get("/me");
    const u = unwrap(res.data);

    meUser.value = u || null;
    const ap = u?.applicant_profile || u?.applicantProfile || null;
    const publicName = ap?.public_display_name;
    const fn = ap?.first_name;
    const ln = ap?.last_name;
    const profileName = publicName || [fn, ln].filter(Boolean).join(" ").trim();
    meName.value = profileName || u?.full_name || u?.name || u?.user?.name || "";
    meAvatarUrl.value = u?.avatar_url || u?.avatar || buildAvatarUrl(ap?.avatar) || null;
    meInitials.value = (meName.value || "U").charAt(0).toUpperCase();

    try {
      const prevRaw = localStorage.getItem("auth_user") || "{}";
      const prev = JSON.parse(prevRaw);
      const merged = {
        ...prev,
        id: u?.id ?? prev.id,
        role: u?.role ?? prev.role,
        email: u?.email ?? prev.email,
        avatar: meAvatarUrl.value || prev.avatar,
        avatar_url: meAvatarUrl.value || prev.avatar_url,
      };
      localStorage.setItem("auth_user", JSON.stringify(merged));
      window.dispatchEvent(new Event("auth:changed"));
    } catch {}
  } catch {
    meName.value = "ME";
  }
}

function buildAvatarUrl(path) {
  if (!path) return null;
  const p = String(path).replace(/^\/+/, '');
  if (/^https?:\/\//i.test(p)) return path;
  if (p.startsWith('uploads/')) return '/' + p;
  return '/storage/' + p;
}

async function fetchApps() {
  const res = await api.get("/applications", { params: { scope: "mine" } });
  apps.value = unwrapPaginated(res.data);
}

const interviews = ref([])
async function fetchInterviews() {
  try {
    const res = await api.get("/interviews")
    const list = unwrapPaginated(res.data)
    const now = new Date()
    interviews.value = list
      .filter(i => i.scheduled_start && new Date(i.scheduled_start) >= now)
      .sort((a, b) => new Date(a.scheduled_start) - new Date(b.scheduled_start))
      .slice(0, 3)
  } catch {}
}

const pipeline = computed(() => {
  const stages = [
    { key: 'submitted', label: 'Applied', color: 'bg-blue-500', light: 'bg-blue-50', text: 'text-blue-700' },
    { key: 'review', label: 'In Review', color: 'bg-yellow-500', light: 'bg-yellow-50', text: 'text-yellow-700' },
    { key: 'interview', label: 'Interview', color: 'bg-purple-500', light: 'bg-purple-50', text: 'text-purple-700' },
    { key: 'hired', label: 'Hired', color: 'bg-emerald-500', light: 'bg-emerald-50', text: 'text-emerald-700' },
  ]
  const total = apps.value.length || 1
  return stages.map(s => ({
    ...s,
    count: apps.value.filter(a => a.status === s.key || (s.key === 'submitted' && a.status === 'new')).length,
    pct: Math.round((apps.value.filter(a => a.status === s.key || (s.key === 'submitted' && a.status === 'new')).length / total) * 100),
  }))
})

async function fetchJobs() {
  jobsLoading.value = true;
  try {
    const res = await api.get("/public/jobs", { params: { per_page: 3 } });
    jobs.value = unwrapPaginated(res.data);
  } finally {
    jobsLoading.value = false;
  }
}

onMounted(async () => {
  try {
    const cached = getCachedUser();
    if (cached) {
      meUser.value = cached;
      meName.value =
        cached.name ||
        cached.full_name ||
        cached.email ||
        "User";
      meAvatarUrl.value = cached.avatar_url || cached.avatar || null;
      meInitials.value = (meName.value || "U").charAt(0).toUpperCase();
    }
  } catch {}

  await fetchMe();
  await fetchApps();
  await fetchJobs();
  await fetchInterviews();
});
</script>

<style scoped>
/* Scoped styles if needed */
</style>
