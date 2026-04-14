<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import MultiSelect from 'primevue/multiselect';
import Checkbox from 'primevue/checkbox';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const loading = ref(false);
const activeTab = ref('reports');

// Custom Reports
const customReports = ref([]);
const reportDialog = ref(false);
const executeDialog = ref(false);
const selectedReport = ref(null);
const reportResults = ref({});

// Report Builder
const newReport = ref({
  name: '',
  description: '',
  report_type: 'hiring_analytics',
  data_sources: [],
  filters: [],
  grouping: [],
  metrics: [],
  visualization_config: {},
  schedule_frequency: null,
  schedule_config: {},
  is_public: false,
});

// Report Execution
const executionResults = ref([]);
const exportFormat = ref('excel');

const reportTypes = [
  { label: 'Hiring Analytics', value: 'hiring_analytics' },
  { label: 'Market Intelligence', value: 'market_intelligence' },
  { label: 'Custom Query', value: 'custom_query' },
];

const dataSourceOptions = {
  hiring_analytics: [
    { label: 'Hiring Metrics', value: 'hiring_metrics' },
    { label: 'Job Applications', value: 'job_applications' },
    { label: 'Jobs', value: 'jobs' },
    { label: 'Source Attribution', value: 'source_attribution' },
    { label: 'Funnel Events', value: 'funnel_events' },
  ],
  market_intelligence: [
    { label: 'Salary Benchmarks', value: 'salary_benchmarks' },
    { label: 'Supply & Demand', value: 'supply_demand' },
    { label: 'Competitor Intelligence', value: 'competitor_intelligence' },
    { label: 'Trending Skills', value: 'trending_skills' },
  ],
  custom_query: [
    { label: 'Custom Table', value: 'custom_table' },
  ],
};

const metricOptions = [
  { label: 'Count', value: 'count' },
  { label: 'Sum', value: 'sum' },
  { label: 'Average', value: 'average' },
  { label: 'Minimum', value: 'min' },
  { label: 'Maximum', value: 'max' },
];

const scheduleOptions = [
  { label: 'None', value: null },
  { label: 'Daily', value: 'daily' },
  { label: 'Weekly', value: 'weekly' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Quarterly', value: 'quarterly' },
];

const exportOptions = [
  { label: 'Excel', value: 'excel' },
  { label: 'CSV', value: 'csv' },
  { label: 'PDF', value: 'pdf' },
];

async function loadCustomReports() {
  loading.value = true;
  try {
    const res = await api.get('/analytics-reporting/custom-reports');
    customReports.value = res?.data?.data || [];
  } finally {
    loading.value = false;
  }
}

async function createReport() {
  try {
    await api.post('/analytics-reporting/custom-reports', newReport.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Custom report created', life: 2000 });
    reportDialog.value = false;
    resetReportForm();
    loadCustomReports();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function executeReport(report) {
  selectedReport.value = report;
  executeDialog.value = true;
  
  try {
    const res = await api.post(`/analytics-reporting/custom-reports/${report.id}/execute`);
    reportResults.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to execute report', life: 3000 });
  }
}

async function exportReport() {
  if (!selectedReport.value) return;
  
  try {
    const res = await api.post(`/analytics-reporting/reports/${selectedReport.value.id}/export`, {
      format: exportFormat.value,
    });
    
    if (res?.data?.data?.download_url) {
      window.open(res.data.data.download_url, '_blank');
      toast.add({ severity: 'success', summary: 'Success', detail: 'Report exported successfully', life: 2000 });
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to export report', life: 3000 });
  }
}

async function deleteReport(reportId) {
  if (!confirm('Are you sure you want to delete this report?')) return;
  
  try {
    await api.delete(`/analytics-reporting/custom-reports/${reportId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Report deleted', life: 2000 });
    loadCustomReports();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete report', life: 3000 });
  }
}

function resetReportForm() {
  newReport.value = {
    name: '',
    description: '',
    report_type: 'hiring_analytics',
    data_sources: [],
    filters: [],
    grouping: [],
    metrics: [],
    visualization_config: {},
    schedule_frequency: null,
    schedule_config: {},
    is_public: false,
  };
}

function addFilter() {
  newReport.value.filters.push({
    field: '',
    operator: '=',
    value: '',
  });
}

function removeFilter(index) {
  newReport.value.filters.splice(index, 1);
}

function addMetric() {
  newReport.value.metrics.push({
    name: '',
    type: 'count',
    field: '',
  });
}

function removeMetric(index) {
  newReport.value.metrics.splice(index, 1);
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'reports') loadCustomReports();
}

const availableDataSources = computed(() => {
  return dataSourceOptions[newReport.value.report_type] || [];
});

const reportResultsChartData = computed(() => {
  const data = reportResults.value.results?.data || [];
  if (!data.length) return { labels: [], datasets: [] };
  
  // Simple chart for demonstration
  return {
    labels: data.map((item, index) => `Item ${index + 1}`),
    datasets: [{
      label: 'Values',
      data: data.map(item => Object.values(item)[0] || 0),
      backgroundColor: '#3b82f6',
    }],
  };
});

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

onMounted(() => {
  loadCustomReports();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Custom Report Builder</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Create, schedule, and export custom analytics reports</p>
        </div>
        
        <Button label="Create Report" icon="pi pi-plus" @click="reportDialog = true" />
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'reports', label: 'My Reports', icon: 'pi-file' },
          { key: 'templates', label: 'Templates', icon: 'pi-clone' },
          { key: 'scheduled', label: 'Scheduled', icon: 'pi-calendar' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Reports Tab -->
      <template v-if="activeTab === 'reports'">
        <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 h-48 animate-pulse"></div>
        </div>
        <div v-else-if="!customReports.length" class="text-center py-12">
          <i class="pi pi-file text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No custom reports yet</h3>
          <p class="text-slate-600 dark:text-slate-400 mb-4">Create your first custom report to get started</p>
          <Button label="Create Report" icon="pi pi-plus" @click="reportDialog = true" />
        </div>
        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="report in customReports" :key="report.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ report.name }}</h4>
                <p v-if="report.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ report.description }}</p>
              </div>
              <div class="flex gap-1">
                <span v-if="report.is_public" class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded font-medium">Public</span>
                <span v-if="!report.is_active" class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 text-xs rounded font-medium">Inactive</span>
              </div>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-tag text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ report.report_type.replace(/_/g, ' ') }}</span>
              </div>
              <div v-if="report.schedule_frequency" class="flex items-center gap-2 text-sm">
                <i class="pi pi-calendar text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ report.schedule_frequency }}</span>
              </div>
              <div v-if="report.last_generated_at" class="flex items-center gap-2 text-sm">
                <i class="pi pi-clock text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300">{{ new Date(report.last_generated_at).toLocaleDateString() }}</span>
              </div>
            </div>

            <div class="flex gap-2">
              <Button label="Execute" size="small" @click="executeReport(report)" />
              <Button label="Edit" size="small" text />
              <Button label="Delete" size="small" text severity="danger" @click="deleteReport(report.id)" />
            </div>
          </div>
        </div>
      </template>

      <!-- Templates Tab -->
      <template v-else-if="activeTab === 'templates'">
        <div class="text-center py-12">
          <i class="pi pi-clone text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Report Templates</h3>
          <p class="text-slate-600 dark:text-slate-400">Pre-built report templates coming soon</p>
        </div>
      </template>

      <!-- Scheduled Tab -->
      <template v-else>
        <div class="text-center py-12">
          <i class="pi pi-calendar text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Scheduled Reports</h3>
          <p class="text-slate-600 dark:text-slate-400">View and manage scheduled report executions</p>
        </div>
      </template>
    </div>

    <!-- Create Report Dialog -->
    <Dialog v-model:visible="reportDialog" header="Create Custom Report" :style="{ width: '800px' }" modal>
      <div class="space-y-6 pt-2">
        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Report Name</label>
            <InputText v-model="newReport.name" class="w-full mt-1.5" placeholder="e.g. Monthly Hiring Report" />
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Report Type</label>
            <Select v-model="newReport.report_type" :options="reportTypes" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
          <Textarea v-model="newReport.description" rows="2" class="w-full mt-1.5" placeholder="Describe this report..." />
        </div>

        <!-- Data Sources -->
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Data Sources</label>
          <MultiSelect v-model="newReport.data_sources" :options="availableDataSources" optionLabel="label" optionValue="value" 
            class="w-full mt-1.5" placeholder="Select data sources" />
        </div>

        <!-- Filters -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Filters</label>
            <Button label="Add Filter" icon="pi pi-plus" size="small" text @click="addFilter" />
          </div>
          
          <div class="space-y-3">
            <div v-for="(filter, index) in newReport.filters" :key="index" 
              class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
              <InputText v-model="filter.field" placeholder="Field name" class="flex-1" />
              <Select v-model="filter.operator" :options="[
                { label: 'Equals', value: '=' },
                { label: 'Not equals', value: '!=' },
                { label: 'Greater than', value: '>' },
                { label: 'Less than', value: '<' },
                { label: 'Contains', value: 'like' },
              ]" optionLabel="label" optionValue="value" class="w-32" />
              <InputText v-model="filter.value" placeholder="Value" class="flex-1" />
              <Button icon="pi pi-trash" size="small" text severity="danger" @click="removeFilter(index)" />
            </div>
          </div>
        </div>

        <!-- Metrics -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Metrics</label>
            <Button label="Add Metric" icon="pi pi-plus" size="small" text @click="addMetric" />
          </div>
          
          <div class="space-y-3">
            <div v-for="(metric, index) in newReport.metrics" :key="index" 
              class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
              <InputText v-model="metric.name" placeholder="Metric name" class="flex-1" />
              <Select v-model="metric.type" :options="metricOptions" optionLabel="label" optionValue="value" class="w-32" />
              <InputText v-model="metric.field" placeholder="Field name" class="flex-1" />
              <Button icon="pi pi-trash" size="small" text severity="danger" @click="removeMetric(index)" />
            </div>
          </div>
        </div>

        <!-- Schedule -->
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Schedule</label>
            <Select v-model="newReport.schedule_frequency" :options="scheduleOptions" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
          <div class="flex items-center gap-2 mt-6">
            <Checkbox v-model="newReport.is_public" inputId="is_public" />
            <label for="is_public" class="text-sm text-slate-700 dark:text-slate-300">Make report public</label>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="reportDialog = false" />
          <Button label="Create Report" @click="createReport" />
        </div>
      </div>
    </Dialog>

    <!-- Execute Report Dialog -->
    <Dialog v-model:visible="executeDialog" :header="`Execute Report: ${selectedReport?.name}`" :style="{ width: '900px' }" modal>
      <div v-if="selectedReport" class="space-y-6 pt-2">
        <!-- Export Options -->
        <div class="flex justify-between items-center">
          <h4 class="font-medium text-slate-900 dark:text-white">Report Results</h4>
          <div class="flex items-center gap-3">
            <Select v-model="exportFormat" :options="exportOptions" optionLabel="label" optionValue="value" class="w-32" />
            <Button label="Export" icon="pi pi-download" @click="exportReport" />
          </div>
        </div>

        <!-- Results Summary -->
        <div v-if="reportResults.results" class="grid grid-cols-3 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">Total Records</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ reportResults.results.data?.length || 0 }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">Execution Time</p>
            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ reportResults.execution_time_ms || 0 }}ms</p>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <p class="text-sm text-slate-600 dark:text-slate-400">Generated</p>
            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ new Date().toLocaleString() }}</p>
          </div>
        </div>

        <!-- Results Visualization -->
        <div v-if="reportResults.results?.data?.length" class="grid md:grid-cols-2 gap-6">
          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
            <h5 class="font-medium text-slate-900 dark:text-white mb-4">Data Visualization</h5>
            <div class="h-64">
              <Chart type="bar" :data="reportResultsChartData" :options="chartOptions" />
            </div>
          </div>

          <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
            <h5 class="font-medium text-slate-900 dark:text-white mb-4">Data Table</h5>
            <DataTable :value="reportResults.results.data.slice(0, 10)" class="text-sm" scrollable scrollHeight="240px">
              <Column v-for="(value, key) in reportResults.results.data[0]" :key="key" 
                :field="key" :header="key" />
            </DataTable>
          </div>
        </div>

        <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
          <i class="pi pi-info-circle text-2xl mb-2"></i>
          <p>No data returned from report execution</p>
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>