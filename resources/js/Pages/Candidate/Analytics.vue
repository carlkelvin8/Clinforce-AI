<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 font-sans pb-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
          <div>
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
              Career Analytics
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2 text-lg">
              Track your job search progress and get insights to improve your success rate.
            </p>
          </div>
          
          <div class="flex gap-3">
            <Select 
              v-model="timeRange" 
              :options="timeRangeOptions" 
              optionLabel="label" 
              optionValue="value" 
              @change="fetchAnalytics"
              class="!w-40"
            />
            <Button 
              icon="pi pi-refresh" 
              @click="fetchAnalytics" 
              :loading="loading"
              outlined
              class="!rounded-lg"
            />
          </div>
        </div>

        <div v-if="loading && !analytics" class="py-12 text-center">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-600 mb-4"></i>
          <p class="text-slate-500">Loading your analytics...</p>
        </div>

        <div v-else-if="error" class="py-12 text-center">
          <Message severity="error" :closable="false">{{ error }}</Message>
        </div>

        <div v-else-if="analytics" class="space-y-8">
          <!-- Overview Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
              <div class="flex items-start justify-between mb-4">
                <div class="p-2 bg-blue-50 dark:bg-blue-900/40 rounded-lg text-blue-600">
                  <i class="pi pi-send text-xl"></i>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Applications</span>
              </div>
              <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">
                {{ analytics.overview.total_applications }}
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Total submitted</div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
              <div class="flex items-start justify-between mb-4">
                <div class="p-2 bg-purple-50 dark:bg-purple-900/40 rounded-lg text-purple-600">
                  <i class="pi pi-calendar text-xl"></i>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Response Rate</span>
              </div>
              <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">
                {{ analytics.overview.response_rate }}%
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">
                {{ analytics.overview.interviews_received }} interviews
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
              <div class="flex items-start justify-between mb-4">
                <div class="p-2 bg-green-50 dark:bg-green-900/40 rounded-lg text-green-600">
                  <i class="pi pi-check-circle text-xl"></i>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Success Rate</span>
              </div>
              <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">
                {{ analytics.overview.success_rate }}%
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">
                {{ analytics.overview.offers_received }} offers
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
              <div class="flex items-start justify-between mb-4">
                <div class="p-2 bg-orange-50 dark:bg-orange-900/40 rounded-lg text-orange-600">
                  <i class="pi pi-chart-line text-xl"></i>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Interview Conv.</span>
              </div>
              <div class="text-3xl font-bold text-slate-900 dark:text-slate-100 mb-1">
                {{ analytics.overview.interview_conversion }}%
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Interview to offer</div>
            </div>
          </div>

          <!-- Application Funnel -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
              <i class="pi pi-chart-bar text-slate-400"></i>
              Application Funnel
            </h2>
            <div class="space-y-4">
              <div v-for="stage in analytics.application_funnel" :key="stage.stage" class="flex items-center gap-4">
                <div class="w-24 text-sm font-medium text-slate-600 dark:text-slate-400">{{ stage.stage }}</div>
                <div class="flex-1 bg-slate-100 dark:bg-slate-700 rounded-full h-8 overflow-hidden">
                  <div 
                    class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-end pr-3 transition-all duration-700"
                    :style="{ width: stage.percentage + '%' }"
                  >
                    <span class="text-white text-sm font-semibold">{{ stage.count }}</span>
                  </div>
                </div>
                <div class="w-16 text-sm font-semibold text-slate-900 dark:text-slate-100 text-right">
                  {{ stage.percentage }}%
                </div>
              </div>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Interview Metrics -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6">
              <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <i class="pi pi-video text-slate-400"></i>
                Interview Insights
              </h2>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Total Interviews</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">{{ analytics.interview_metrics.total_interviews }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Upcoming</span>
                  <span class="font-bold text-green-600">{{ analytics.interview_metrics.upcoming_count }}</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Avg Duration</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">{{ Math.round(analytics.interview_metrics.average_duration) }} min</span>
                </div>
                
                <!-- Interview Types -->
                <div v-if="Object.keys(analytics.interview_metrics.by_type).length" class="mt-6">
                  <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">By Type</h4>
                  <div class="space-y-2">
                    <div v-for="(count, type) in analytics.interview_metrics.by_type" :key="type" class="flex justify-between">
                      <span class="text-sm text-slate-600 dark:text-slate-400 capitalize">{{ type.replace('_', ' ') }}</span>
                      <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ count }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Salary Insights -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6">
              <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <i class="pi pi-dollar text-slate-400"></i>
                Salary Insights
              </h2>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Average Applied</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">
                    ₱{{ formatNumber(analytics.salary_insights.average_salary_applied) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Range Min</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">
                    ₱{{ formatNumber(analytics.salary_insights.salary_range_min) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Range Max</span>
                  <span class="font-bold text-slate-900 dark:text-slate-100">
                    ₱{{ formatNumber(analytics.salary_insights.salary_range_max) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-600 dark:text-slate-400">Offers with Salary</span>
                  <span class="font-bold text-green-600">{{ analytics.salary_insights.offers_with_salary }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Skills Analysis -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
              <i class="pi pi-cog text-slate-400"></i>
              Skills Analysis
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Applications by Role Type -->
              <div>
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Applications by Role Type</h4>
                <div class="space-y-3">
                  <div v-for="(count, role) in analytics.skills_analysis.applications_by_role_type" :key="role" class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">{{ role }}</span>
                    <div class="flex items-center gap-2">
                      <div class="w-20 bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                        <div 
                          class="h-full bg-blue-500 rounded-full"
                          :style="{ width: (count / Math.max(...Object.values(analytics.skills_analysis.applications_by_role_type)) * 100) + '%' }"
                        ></div>
                      </div>
                      <span class="font-semibold text-slate-900 dark:text-slate-100 w-8 text-right">{{ count }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Success Rate by Role -->
              <div>
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Success Rate by Role</h4>
                <div class="space-y-3">
                  <div v-for="(rate, role) in analytics.skills_analysis.success_rate_by_role" :key="role" class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">{{ role }}</span>
                    <div class="flex items-center gap-2">
                      <div class="w-20 bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                        <div 
                          class="h-full bg-green-500 rounded-full"
                          :style="{ width: rate + '%' }"
                        ></div>
                      </div>
                      <span class="font-semibold text-slate-900 dark:text-slate-100 w-12 text-right">{{ rate }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Skill Gaps & Strong Areas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
              <div>
                <h4 class="text-sm font-semibold text-red-600 mb-3 flex items-center gap-2">
                  <i class="pi pi-exclamation-triangle text-xs"></i>
                  Areas for Improvement
                </h4>
                <div class="space-y-2">
                  <div v-for="skill in analytics.skills_analysis.skill_gaps" :key="skill" class="px-3 py-2 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-lg text-sm">
                    {{ skill }}
                  </div>
                  <div v-if="!analytics.skills_analysis.skill_gaps.length" class="text-slate-500 dark:text-slate-400 text-sm italic">
                    No specific gaps identified
                  </div>
                </div>
              </div>

              <div>
                <h4 class="text-sm font-semibold text-green-600 mb-3 flex items-center gap-2">
                  <i class="pi pi-check-circle text-xs"></i>
                  Strong Areas
                </h4>
                <div class="space-y-2">
                  <div v-for="skill in analytics.skills_analysis.strong_areas" :key="skill" class="px-3 py-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg text-sm">
                    {{ skill }}
                  </div>
                  <div v-if="!analytics.skills_analysis.strong_areas.length" class="text-slate-500 dark:text-slate-400 text-sm italic">
                    Keep applying to identify strengths
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Market Position -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
              <i class="pi pi-chart-pie text-slate-400"></i>
              Market Position
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Application Activity</h4>
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ analytics.market_position.applications_vs_average.user }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Your applications</div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-semibold text-slate-600 dark:text-slate-400">{{ analytics.market_position.applications_vs_average.market_average }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Market average</div>
                  </div>
                </div>
                <div class="mt-2 text-center">
                  <span class="text-sm font-semibold" :class="getPercentileColor(analytics.market_position.applications_vs_average.percentile)">
                    {{ analytics.market_position.applications_vs_average.percentile }}th percentile
                  </span>
                </div>
              </div>

              <div>
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">Interview Performance</h4>
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ analytics.market_position.interviews_vs_average.user }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Your interviews</div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-semibold text-slate-600 dark:text-slate-400">{{ analytics.market_position.interviews_vs_average.market_average }}</div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">Market average</div>
                  </div>
                </div>
                <div class="mt-2 text-center">
                  <span class="text-sm font-semibold" :class="getPercentileColor(analytics.market_position.interviews_vs_average.percentile)">
                    {{ analytics.market_position.interviews_vs_average.percentile }}th percentile
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Recommendations -->
          <div v-if="analytics.recommendations.length" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-800 rounded-2xl border border-blue-100 dark:border-slate-700 p-6">
            <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
              <i class="pi pi-lightbulb text-blue-600"></i>
              Personalized Recommendations
            </h2>
            <div class="space-y-4">
              <div v-for="rec in analytics.recommendations" :key="rec.type" class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-blue-100 dark:border-slate-700">
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                    :class="rec.priority === 'high' ? 'bg-red-100 text-red-600' : rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600'">
                    <i class="pi pi-exclamation-triangle text-sm" v-if="rec.priority === 'high'"></i>
                    <i class="pi pi-info-circle text-sm" v-else></i>
                  </div>
                  <div class="flex-1">
                    <h4 class="font-semibold text-slate-900 dark:text-slate-100">{{ rec.title }}</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ rec.description }}</p>
                  </div>
                  <span class="text-xs font-semibold px-2 py-1 rounded-full"
                    :class="rec.priority === 'high' ? 'bg-red-100 text-red-700' : rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'">
                    {{ rec.priority }}
                  </span>
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
import { computed, onMounted, ref } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
import api from "@/lib/api";

import Button from 'primevue/button';
import Select from 'primevue/select';
import Message from 'primevue/message';

const loading = ref(false);
const error = ref("");
const analytics = ref(null);
const timeRange = ref('6_months');

const timeRangeOptions = [
  { label: '1 Month', value: '1_month' },
  { label: '3 Months', value: '3_months' },
  { label: '6 Months', value: '6_months' },
  { label: '1 Year', value: '1_year' },
];

async function fetchAnalytics() {
  loading.value = true;
  error.value = "";
  
  try {
    const res = await api.get('/candidate/analytics', {
      params: { time_range: timeRange.value }
    });
    analytics.value = res.data?.data ?? res.data;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Failed to load analytics";
  } finally {
    loading.value = false;
  }
}

function formatNumber(num) {
  if (!num) return '0';
  return new Intl.NumberFormat().format(num);
}

function getPercentileColor(percentile) {
  if (percentile >= 75) return 'text-green-600';
  if (percentile >= 50) return 'text-blue-600';
  if (percentile >= 25) return 'text-yellow-600';
  return 'text-red-600';
}

onMounted(() => {
  fetchAnalytics();
});
</script>

<style scoped>
.analytics-page :deep(.p-select) {
  border-width: 1px !important;
  border-color: #e5e7eb !important;
}
</style>