<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const loading = ref(false);
const activeTab = ref('overview');

// Date range
const dateRange = ref([
  new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // 30 days ago
  new Date()
]);

// Analytics data
const analyticsData = ref({});
const timeToHireData = ref([]);
const sourceAttributionData = ref([]);
const costPerHireData = ref([]);
const funnelData = ref([]);

// Filters
const groupByOptions = [
  { label: 'Role', value: 'role' },
  { label: 'Department', value: 'department' },
  { label: 'Location', value: 'location' },
  { label: 'Source', value: 'source' },
];

const selectedGroupBy = ref('role');

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

async function loadAnalyticsDashboard() {
  loading.value = true;
  try {
    const params = {
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    };
    
    const res = await api.get('/analytics-reporting/hiring-dashboard', { params });
    analyticsData.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load analytics data', life: 3000 });
  } finally {
    loading.value = false;
  }
}

async function loadTimeToHire() {
  try {
    const params = {
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
      group_by: selectedGroupBy.value,
    };
    
    const res = await api.get('/analytics-reporting/time-to-hire', { params });
    timeToHireData.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load time-to-hire data', life: 3000 });
  }
}

async function loadSourceAttribution() {
  try {
    const params = {
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    };
    
    const res = await api.get('/analytics-reporting/source-attribution', { params });
    sourceAttributionData.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load source attribution data', life: 3000 });
  }
}

async function loadCostPerHire() {
  try {
    const params = {
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    };
    
    const res = await api.get('/analytics-reporting/cost-per-hire', { params });
    costPerHireData.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load cost-per-hire data', life: 3000 });
  }
}

async function loadFunnelAnalysis() {
  try {
    const params = {
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    };
    
    const res = await api.get('/analytics-reporting/funnel-analysis', { params });
    funnelData.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load funnel analysis data', life: 3000 });
  }
}

function refreshData() {
  loadAnalyticsDashboard();
  if (activeTab.value === 'time-to-hire') loadTimeToHire();
  if (activeTab.value === 'source-attribution') loadSourceAttribution();
  if (activeTab.value === 'cost-per-hire') loadCostPerHire();
  if (activeTab.value === 'funnel') loadFunnelAnalysis();
}

function switchTab(tab) {
  activeTab.value = tab;
  
  switch (tab) {
    case 'time-to-hire':
      loadTimeToHire();
      break;
    case 'source-attribution':
      loadSourceAttribution();
      break;
    case 'cost-per-hire':
      loadCostPerHire();
      break;
    case 'funnel':
      loadFunnelAnalysis();
      break;
  }
}

const timeToHireChartData = computed(() => {
  if (!timeToHireData.value.length) return { labels: [], datasets: [] };
  
  return {
    labels: timeToHireData.value.map(item => item.category || 'Unknown'),
    datasets: [{
      label: 'Average Days to Hire',
      data: timeToHireData.value.map(item => item.avg_days),
      backgroundColor: '#3b82f6',
      borderColor: '#2563eb',
      borderWidth: 1,
    }],
  };
});

const sourceAttributionChartData = computed(() => {
  if (!sourceAttributionData.value.length) return { labels: [], datasets: [] };
  
  return {
    labels: sourceAttributionData.value.map(item => item.source_name || item.source_type),
    datasets: [{
      label: 'Applications',
      data: sourceAttributionData.value.map(item => item.applications_count),
      backgroundColor: [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
      ],
    }],
  };
});

const funnelChartData = computed(() => {
  if (!funnelData.value.length) return { labels: [], datasets: [] };
  
  return {
    labels: funnelData.value.map(item => item.stage.replace(/_/g, ' ').toUpperCase()),
    datasets: [{
      label: 'Candidates',
      data: funnelData.value.map(item => item.count),
      backgroundColor: '#3b82f6',
      borderColor: '#2563eb',
      borderWidth: 1,
    }],
  };
});

const overviewMetrics = computed(() => {
  const overview = analyticsData.value.overview || {};
  return [
    {
      title: 'Jobs Posted',
      value: overview.total_jobs_posted || 0,
      icon: 'pi-briefcase',
      color: 'text-blue-600',
      bgColor: 'bg-blue-100 dark:bg-blue-900',
    },
    {
      title: 'Applications',
      value: overview.total_applications || 0,
      icon: 'pi-users',
      color: 'text-green-600',
      bgColor: 'bg-green-100 dark:bg-green-900',
    },
    {
      title: 'Hires',
      value: overview.total_hires || 0,
      icon: 'pi-check-circle',
      color: 'text-purple-600',
      bgColor: 'bg-purple-100 dark:bg-purple-900',
    },
    {
      title: 'Avg. Time to Hire',
      value: Math.round(overview.avg_time_to_hire || 0),
      unit: 'days',
      icon: 'pi-clock',
      color: 'text-orange-600',
      bgColor: 'bg-orange-100 dark:bg-orange-900',
    },
  ];
});

onMounted(() => {
  loadAnalyticsDashboard();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Advanced Analytics Dashboard</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Comprehensive hiring analytics and performance insights</p>
        </div>
        
        <div class="flex items-center gap-3">
          <Calendar v-model="dateRange" selectionMode="range" :manualInput="false" 
            class="w-64" placeholder="Select date range" @date-select="refreshData" />
          <Button icon="pi pi-refresh" severity="secondary" @click="refreshData" :loading="loading" />
        </div>
      </div>

      <!-- Overview Metrics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="metric in overviewMetrics" :key="metric.title"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-600 dark:text-slate-400">{{ metric.title }}</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">
                {{ metric.value.toLocaleString() }}
                <span v-if="metric.unit" class="text-sm text-slate-500 dark:text-slate-400 ml-1">{{ metric.unit }}</span>
              </p>
            </div>
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', metric.bgColor]">
              <i :class="['pi text-lg', metric.icon, metric.color]"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'overview', label: 'Overview', icon: 'pi-chart-line' },
          { key: 'time-to-hire', label: 'Time to Hire', icon: 'pi-clock' },
          { key: 'source-attribution', label: 'Source Attribution', icon: 'pi-share-alt' },
          { key: 'cost-per-hire', label: 'Cost per Hire', icon: 'pi-dollar' },
          { key: 'funnel', label: 'Funnel Analysis', icon: 'pi-filter' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Overview Tab -->
      <template v-if="activeTab === 'overview'">
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Hiring Trends -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Hiring Trends</h3>
            <div class="h-64">
              <Chart v-if="analyticsData.trends" type="line" :data="analyticsData.trends" :options="chartOptions" />
              <div v-else class="flex items-center justify-center h-full text-slate-500 dark:text-slate-400">
                No trend data available
              </div>
            </div>
          </div>

          <!-- Top Sources -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Top Application Sources</h3>
            <div class="space-y-3">
              <div v-for="source in analyticsData.source_attribution?.slice(0, 5)" :key="source.source_type"
                class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ source.source_type.replace(/_/g, ' ') }}</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ source.count }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Time to Hire Tab -->
      <template v-else-if="activeTab === 'time-to-hire'">
        <div class="space-y-6">
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-slate-900 dark:text-white">Time to Hire Analysis</h3>
            <Select v-model="selectedGroupBy" :options="groupByOptions" optionLabel="label" optionValue="value" 
              class="w-48" @change="loadTimeToHire" />
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Average Time to Hire by {{ selectedGroupBy }}</h4>
              <div class="h-64">
                <Chart type="bar" :data="timeToHireChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Detailed Breakdown</h4>
              <DataTable :value="timeToHireData" class="text-sm">
                <Column field="category" header="Category" />
                <Column field="avg_days" header="Avg Days">
                  <template #body="{ data }">
                    {{ Math.round(data.avg_days) }}
                  </template>
                </Column>
                <Column field="sample_size" header="Sample Size" />
              </DataTable>
            </div>
          </div>
        </div>
      </template>

      <!-- Source Attribution Tab -->
      <template v-else-if="activeTab === 'source-attribution'">
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Source Attribution Analysis</h3>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Applications by Source</h4>
              <div class="h-64">
                <Chart type="doughnut" :data="sourceAttributionChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Source Performance</h4>
              <DataTable :value="sourceAttributionData" class="text-sm">
                <Column field="source_name" header="Source" />
                <Column field="applications_count" header="Applications" />
                <Column field="conversion_rate" header="Conversion %">
                  <template #body="{ data }">
                    {{ data.conversion_rate }}%
                  </template>
                </Column>
                <Column field="avg_cost" header="Avg Cost">
                  <template #body="{ data }">
                    ${{ Math.round(data.avg_cost || 0) }}
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>
        </div>
      </template>

      <!-- Cost per Hire Tab -->
      <template v-else-if="activeTab === 'cost-per-hire'">
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Cost per Hire Analysis</h3>

          <div class="grid md:grid-cols-3 gap-4">
            <div v-for="cost in costPerHireData" :key="cost.category"
              class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white">{{ cost.category }}</h4>
              <p class="text-2xl font-bold text-slate-900 dark:text-white mt-2">
                ${{ Math.round(cost.avg_cost || 0).toLocaleString() }}
              </p>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ cost.total_hires }} hires • ${{ Math.round(cost.total_cost || 0).toLocaleString() }} total
              </p>
            </div>
          </div>
        </div>
      </template>

      <!-- Funnel Analysis Tab -->
      <template v-else>
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Hiring Funnel Analysis</h3>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Funnel Visualization</h4>
              <div class="h-64">
                <Chart type="bar" :data="funnelChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Conversion Rates</h4>
              <div class="space-y-3">
                <div v-for="stage in funnelData" :key="stage.stage"
                  class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div>
                    <span class="font-medium text-slate-900 dark:text-white capitalize">
                      {{ stage.stage.replace(/_/g, ' ') }}
                    </span>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ stage.count }} candidates</p>
                  </div>
                  <div class="text-right">
                    <span class="text-lg font-bold text-slate-900 dark:text-white">{{ stage.conversion_rate }}%</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400">conversion</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>