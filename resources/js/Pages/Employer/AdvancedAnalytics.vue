<script setup>
import { ref, onMounted, computed } from 'vue';
import EmployerLayout from './EmployerLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Chart from 'primevue/chart';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const activeTab = ref('hiring');
const loading = ref(false);
const days = ref(30);

// Hiring Analytics
const hiringData = ref({});
const timeToHireData = ref([]);
const sourceData = ref([]);
const costData = ref({});

// Market Intelligence
const salaryBenchmarks = ref([]);
const supplyDemand = ref([]);
const trendingSkills = ref([]);
const competitors = ref([]);

// Custom Reports
const customReports = ref([]);
const reportDialog = ref(false);
const newReport = ref({
  report_name: '',
  description: '',
  report_type: 'hiring_analytics',
  config: {},
  columns: [],
});

const periodOptions = [
  { label: '7 Days', value: 7 },
  { label: '30 Days', value: 30 },
  { label: '90 Days', value: 90 },
  { label: '180 Days', value: 180 },
];

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: isDark.value ? '#e2e8f0' : '#374151',
      },
    },
  },
  scales: {
    x: {
      ticks: { color: isDark.value ? '#94a3b8' : '#6b7280' },
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
    },
    y: {
      ticks: { color: isDark.value ? '#94a3b8' : '#6b7280' },
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
    },
  },
}));

async function loadHiringAnalytics() {
  loading.value = true;
  try {
    const [dashboard, timeToHire, sources, costs] = await Promise.all([
      api.get('/analytics/hiring-dashboard', { params: { days: days.value } }),
      api.get('/analytics/time-to-hire', { params: { days: days.value, dimension: 'role' } }),
      api.get('/analytics/source-attribution', { params: { days: days.value } }),
      api.get('/analytics/cost-per-hire', { params: { days: days.value } }),
    ]);

    hiringData.value = dashboard?.data?.data || dashboard?.data || {};
    timeToHireData.value = timeToHire?.data?.data?.breakdown || timeToHire?.data?.breakdown || [];
    sourceData.value = sources?.data?.data || sources?.data || [];
    costData.value = costs?.data?.data || costs?.data || {};
  } finally { loading.value = false; }
}

async function loadMarketIntelligence() {
  loading.value = true;
  try {
    const [benchmarks, supply, skills, comp] = await Promise.all([
      api.get('/analytics/salary-benchmarks', { params: { country: 'Philippines' } }),
      api.get('/analytics/supply-demand', { params: { country: 'Philippines' } }),
      api.get('/analytics/trending-skills'),
      api.get('/analytics/competitors'),
    ]);

    salaryBenchmarks.value = benchmarks?.data?.data || benchmarks?.data || [];
    supplyDemand.value = supply?.data?.data || supply?.data || [];
    trendingSkills.value = skills?.data?.data || skills?.data || [];
    competitors.value = comp?.data?.data || comp?.data || [];
  } finally { loading.value = false; }
}

async function loadCustomReports() {
  loading.value = true;
  try {
    const res = await api.get('/custom-reports');
    customReports.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'hiring') loadHiringAnalytics();
  if (tab === 'market') loadMarketIntelligence();
  if (tab === 'reports') loadCustomReports();
}

const funnelChartData = computed(() => {
  const funnel = hiringData.value.funnel || {};
  return {
    labels: ['Applied', 'Viewed', 'Shortlisted', 'Interviewed', 'Offered', 'Hired'],
    datasets: [{
      label: 'Candidates',
      data: [
        funnel.applied || 0,
        funnel.viewed || 0,
        funnel.shortlisted || 0,
        funnel.interviewed || 0,
        funnel.offered || 0,
        funnel.hired || 0,
      ],
      backgroundColor: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    }],
  };
});

const sourceChartData = computed(() => {
  const sources = hiringData.value.source_attribution || [];
  return {
    labels: sources.map(s => s.source_type?.replace(/_/g, ' ') || 'Unknown'),
    datasets: [{
      label: 'Applications',
      data: sources.map(s => s.count || 0),
      backgroundColor: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'],
    }],
  };
});

async function createReport() {
  try {
    await api.post('/custom-reports', newReport.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Report created', life: 2000 });
    reportDialog.value = false;
    loadCustomReports();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function executeReport(reportId, format = 'json') {
  try {
    const res = await api.post(`/custom-reports/${reportId}/execute`, {}, { params: { format } });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Report generation started', life: 2000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

onMounted(() => {
  loadHiringAnalytics();
});
</script>

<template>
  <EmployerLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Advanced Analytics</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Hiring insights, market intelligence, and custom reports</p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'hiring', label: 'Hiring Analytics', icon: 'pi-chart-line' },
          { key: 'market', label: 'Market Intelligence', icon: 'pi-globe' },
          { key: 'reports', label: 'Custom Reports', icon: 'pi-file-export' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Hiring Analytics Tab -->
      <template v-if="activeTab === 'hiring'">
        <div class="flex items-center gap-3">
          <Select v-model="days" :options="periodOptions" optionLabel="label" optionValue="value" 
            class="text-sm" @change="loadHiringAnalytics" />
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="loadHiringAnalytics" />
        </div>

        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="i in 4" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 h-24 animate-pulse"></div>
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ hiringData.time_to_hire_days || 0 }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Avg Time to Hire (days)</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">${{ hiringData.cost_per_hire || 0 }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cost per Hire</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ hiringData.funnel?.hired || 0 }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hires ({{ days }} days)</div>
          </div>
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">
              {{ hiringData.funnel?.applied && hiringData.funnel?.hired 
                ? Math.round((hiringData.funnel.hired / hiringData.funnel.applied) * 100) 
                : 0 }}%
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Conversion Rate</div>
          </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
          <!-- Hiring Funnel -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Hiring Funnel</h3>
            <div class="h-64">
              <Chart type="bar" :data="funnelChartData" :options="chartOptions" />
            </div>
          </div>

          <!-- Source Attribution -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Application Sources</h3>
            <div class="h-64">
              <Chart type="doughnut" :data="sourceChartData" :options="chartOptions" />
            </div>
          </div>
        </div>

        <!-- Time to Hire by Role -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Time to Hire by Role</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Role</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Avg Days</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Hires</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <tr v-if="!timeToHireData.length">
                  <td colspan="3" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No data available</td>
                </tr>
                <tr v-else v-for="item in timeToHireData" :key="item.dimension_value">
                  <td class="px-6 py-3 text-slate-900 dark:text-white">{{ item.dimension_value }}</td>
                  <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ Math.round(item.avg_days) }}</td>
                  <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ item.sample_size }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- Market Intelligence Tab -->
      <template v-else-if="activeTab === 'market'">
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Salary Benchmarks -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
              <h3 class="font-semibold text-slate-900 dark:text-white">Salary Benchmarks</h3>
            </div>
            <div class="max-h-80 overflow-y-auto">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900 sticky top-0">
                  <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Role</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Median</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Range</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                  <tr v-if="!salaryBenchmarks.length">
                    <td colspan="3" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No benchmarks available</td>
                  </tr>
                  <tr v-else v-for="b in salaryBenchmarks" :key="b.normalized_title">
                    <td class="px-4 py-3 text-slate-900 dark:text-white text-xs">{{ b.normalized_title }}</td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs">{{ b.currency_code }} {{ Math.round(b.median_salary).toLocaleString() }}</td>
                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300 text-xs">{{ Math.round(b.p25_salary).toLocaleString() }} - {{ Math.round(b.p75_salary).toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Trending Skills -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
              <h3 class="font-semibold text-slate-900 dark:text-white">Trending Skills</h3>
            </div>
            <div class="max-h-80 overflow-y-auto">
              <div class="p-4 space-y-3">
                <div v-if="!trendingSkills.length" class="text-center text-slate-500 dark:text-slate-400 py-8">No trending skills data</div>
                <div v-else v-for="skill in trendingSkills.slice(0, 10)" :key="skill.skill_name" class="flex items-center justify-between">
                  <div>
                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ skill.skill_name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">{{ skill.skill_category?.replace(/_/g, ' ') }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-600 dark:text-slate-300">{{ skill.mention_count }}</span>
                    <i :class="['pi text-xs', skill.trend_direction === 'rising' ? 'pi-arrow-up text-green-500' : skill.trend_direction === 'declining' ? 'pi-arrow-down text-red-500' : 'pi-minus text-slate-400']"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Supply/Demand Heatmap -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Supply/Demand by Location</h3>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-50 dark:bg-slate-900">
                <tr>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Location</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Category</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Jobs</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Candidates</th>
                  <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Market</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <tr v-if="!supplyDemand.length">
                  <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No supply/demand data</td>
                </tr>
                <tr v-else v-for="item in supplyDemand" :key="`${item.state}-${item.city}-${item.job_category}`">
                  <td class="px-6 py-3 text-slate-900 dark:text-white">{{ item.city }}, {{ item.state }}</td>
                  <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ item.job_category }}</td>
                  <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ Math.round(item.avg_jobs) }}</td>
                  <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ Math.round(item.avg_candidates) }}</td>
                  <td class="px-6 py-3">
                    <span :class="['px-2 py-1 rounded text-xs font-medium', 
                      item.temperature === 'very_hot' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                      item.temperature === 'hot' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' :
                      item.temperature === 'warm' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                      'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200']">
                      {{ item.temperature?.replace(/_/g, ' ') }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>

      <!-- Custom Reports Tab -->
      <template v-else>
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Custom Reports</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Create and schedule custom analytics reports</p>
          </div>
          <Button label="Create Report" icon="pi pi-plus" @click="reportDialog = true" />
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Report Name</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Type</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Schedule</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Last Run</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="!customReports.length">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">No custom reports yet</td>
              </tr>
              <tr v-else v-for="report in customReports" :key="report.id">
                <td class="px-6 py-3">
                  <div class="font-medium text-slate-900 dark:text-white">{{ report.report_name }}</div>
                  <div v-if="report.description" class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ report.description }}</div>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300 capitalize">{{ report.report_type?.replace(/_/g, ' ') }}</td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300 capitalize">{{ report.schedule_frequency || 'Manual' }}</td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ report.last_run_at ? new Date(report.last_run_at).toLocaleDateString() : '—' }}</td>
                <td class="px-6 py-3">
                  <div class="flex gap-2">
                    <Button label="Run" size="small" text @click="executeReport(report.id)" />
                    <Button label="Export" size="small" text @click="executeReport(report.id, 'csv')" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Create Report Dialog -->
    <Dialog v-model:visible="reportDialog" header="Create Custom Report" :style="{ width: '500px' }" modal>
      <div class="space-y-4 pt-2">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Report Name</label>
          <InputText v-model="newReport.report_name" class="w-full mt-1.5" placeholder="e.g. Weekly Hiring Summary" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
          <Textarea v-model="newReport.description" rows="2" class="w-full mt-1.5" placeholder="Optional description…" />
        </div>
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Report Type</label>
          <Select v-model="newReport.report_type" :options="[
            { label: 'Hiring Analytics', value: 'hiring_analytics' },
            { label: 'Market Intelligence', value: 'market_intelligence' },
            { label: 'Custom', value: 'custom' },
          ]" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="reportDialog = false" />
          <Button label="Create" @click="createReport" />
        </div>
      </div>
    </Dialog>
  </EmployerLayout>
</template>