<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import MultiSelect from 'primevue/multiselect';
import Chart from 'primevue/chart';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const loading = ref(false);
const activeTab = ref('overview');

// Market Intelligence Data
const marketOverview = ref({});
const salaryBenchmarks = ref([]);
const supplyDemandData = ref([]);
const competitorData = ref([]);
const trendingSkills = ref([]);
const marketForecast = ref({});

// Filters
const selectedLocation = ref('United States');
const selectedJobCategory = ref('nursing');
const selectedJobTitles = ref(['Registered Nurse', 'Nurse Practitioner']);
const selectedLocations = ref(['New York', 'California', 'Texas']);
const selectedCompetitors = ref([]);

const locationOptions = [
  'United States', 'New York', 'California', 'Texas', 'Florida', 'Illinois',
  'Pennsylvania', 'Ohio', 'Georgia', 'North Carolina', 'Michigan'
];

const jobCategoryOptions = [
  { label: 'Nursing', value: 'nursing' },
  { label: 'Physician', value: 'physician' },
  { label: 'Therapist', value: 'therapist' },
  { label: 'Technician', value: 'technician' },
  { label: 'Administrative', value: 'administrative' },
];

const jobTitleOptions = [
  'Registered Nurse', 'Nurse Practitioner', 'Licensed Practical Nurse',
  'Certified Nursing Assistant', 'ICU Nurse', 'Emergency Room Nurse',
  'Operating Room Nurse', 'Pediatric Nurse', 'Psychiatric Nurse'
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

async function loadMarketOverview() {
  loading.value = true;
  try {
    const params = {
      location: selectedLocation.value,
      job_category: selectedJobCategory.value,
    };
    
    const res = await api.get('/market-intelligence/dashboard', { params });
    marketOverview.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load market overview', life: 3000 });
  } finally {
    loading.value = false;
  }
}

async function loadSalaryBenchmarks() {
  try {
    const params = {
      job_titles: selectedJobTitles.value,
      locations: selectedLocations.value,
    };
    
    const res = await api.get('/market-intelligence/salary-benchmarks', { params });
    salaryBenchmarks.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load salary benchmarks', life: 3000 });
  }
}

async function loadSupplyDemandAnalysis() {
  try {
    const params = {
      job_category: selectedJobCategory.value,
      locations: selectedLocations.value,
      time_period: '6m',
    };
    
    const res = await api.get('/market-intelligence/supply-demand', { params });
    supplyDemandData.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load supply/demand data', life: 3000 });
  }
}

async function loadCompetitorIntelligence() {
  try {
    const params = {
      job_categories: [selectedJobCategory.value],
      locations: selectedLocations.value,
      days_back: 30,
    };
    
    if (selectedCompetitors.value.length) {
      params.competitors = selectedCompetitors.value;
    }
    
    const res = await api.get('/market-intelligence/competitors', { params });
    competitorData.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load competitor data', life: 3000 });
  }
}

async function loadTrendingSkills() {
  try {
    const params = {
      job_category: selectedJobCategory.value,
      locations: selectedLocations.value,
      months_back: 12,
    };
    
    const res = await api.get('/market-intelligence/trending-skills', { params });
    trendingSkills.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load trending skills', life: 3000 });
  }
}

async function loadMarketForecast() {
  try {
    const params = {
      job_category: selectedJobCategory.value,
      location: selectedLocation.value,
      forecast_months: 6,
    };
    
    const res = await api.get('/market-intelligence/forecast', { params });
    marketForecast.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load market forecast', life: 3000 });
  }
}

function refreshData() {
  loadMarketOverview();
  
  switch (activeTab.value) {
    case 'salary':
      loadSalaryBenchmarks();
      break;
    case 'supply-demand':
      loadSupplyDemandAnalysis();
      break;
    case 'competitors':
      loadCompetitorIntelligence();
      break;
    case 'skills':
      loadTrendingSkills();
      break;
    case 'forecast':
      loadMarketForecast();
      break;
  }
}

function switchTab(tab) {
  activeTab.value = tab;
  
  switch (tab) {
    case 'salary':
      loadSalaryBenchmarks();
      break;
    case 'supply-demand':
      loadSupplyDemandAnalysis();
      break;
    case 'competitors':
      loadCompetitorIntelligence();
      break;
    case 'skills':
      loadTrendingSkills();
      break;
    case 'forecast':
      loadMarketForecast();
      break;
  }
}

const salaryChartData = computed(() => {
  if (!salaryBenchmarks.value.length) return { labels: [], datasets: [] };
  
  return {
    labels: salaryBenchmarks.value.map(item => `${item.normalized_title} (${item.location_value})`),
    datasets: [
      {
        label: 'Min Salary',
        data: salaryBenchmarks.value.map(item => item.min_salary),
        backgroundColor: '#ef4444',
      },
      {
        label: 'Median Salary',
        data: salaryBenchmarks.value.map(item => item.median_salary),
        backgroundColor: '#3b82f6',
      },
      {
        label: 'Max Salary',
        data: salaryBenchmarks.value.map(item => item.max_salary),
        backgroundColor: '#10b981',
      },
    ],
  };
});

const supplyDemandChartData = computed(() => {
  if (!supplyDemandData.value.length) return { labels: [], datasets: [] };
  
  return {
    labels: supplyDemandData.value.map(item => item.location_value),
    datasets: [
      {
        label: 'Supply/Demand Ratio',
        data: supplyDemandData.value.map(item => item.avg_supply_demand_ratio),
        backgroundColor: '#3b82f6',
        yAxisID: 'y',
      },
      {
        label: 'Competition Index',
        data: supplyDemandData.value.map(item => item.competition_index),
        backgroundColor: '#ef4444',
        yAxisID: 'y1',
      },
    ],
  };
});

const skillsChartData = computed(() => {
  const hotSkills = trendingSkills.value.skills_analysis?.hot_skills || [];
  if (!hotSkills.length) return { labels: [], datasets: [] };
  
  return {
    labels: hotSkills.map(skill => skill.skill_name),
    datasets: [{
      label: 'Demand Score',
      data: hotSkills.map(skill => skill.avg_demand_score),
      backgroundColor: '#10b981',
    }],
  };
});

const overviewMetrics = computed(() => {
  const overview = marketOverview.value.market_overview || {};
  return [
    {
      title: 'Job Postings',
      value: overview.total_job_postings || 0,
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
      title: 'Supply/Demand Ratio',
      value: (overview.avg_supply_demand_ratio || 0).toFixed(1),
      icon: 'pi-chart-line',
      color: 'text-purple-600',
      bgColor: 'bg-purple-100 dark:bg-purple-900',
    },
    {
      title: 'Avg. Time to Fill',
      value: Math.round(overview.avg_time_to_fill || 0),
      unit: 'days',
      icon: 'pi-clock',
      color: 'text-orange-600',
      bgColor: 'bg-orange-100 dark:bg-orange-900',
    },
  ];
});

onMounted(() => {
  loadMarketOverview();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Market Intelligence</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Real-time market insights and competitive analysis</p>
        </div>
        
        <div class="flex items-center gap-3">
          <Select v-model="selectedLocation" :options="locationOptions" class="w-48" 
            placeholder="Select location" @change="refreshData" />
          <Select v-model="selectedJobCategory" :options="jobCategoryOptions" optionLabel="label" optionValue="value" 
            class="w-48" placeholder="Select category" @change="refreshData" />
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
                {{ typeof metric.value === 'number' ? metric.value.toLocaleString() : metric.value }}
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
          { key: 'overview', label: 'Overview', icon: 'pi-chart-pie' },
          { key: 'salary', label: 'Salary Benchmarks', icon: 'pi-dollar' },
          { key: 'supply-demand', label: 'Supply & Demand', icon: 'pi-chart-bar' },
          { key: 'competitors', label: 'Competitors', icon: 'pi-eye' },
          { key: 'skills', label: 'Trending Skills', icon: 'pi-star' },
          { key: 'forecast', label: 'Forecast', icon: 'pi-forward' },
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
          <!-- Market Trends -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Market Activity</h3>
            <div class="space-y-4">
              <div v-for="competitor in marketOverview.competitor_activity?.slice(0, 5)" :key="competitor.competitor_name"
                class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-300">{{ competitor.competitor_name }}</span>
                <div class="text-right">
                  <span class="font-medium text-slate-900 dark:text-white">{{ competitor.recent_postings }}</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400 ml-1">jobs</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Skill Gaps -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">High-Demand Skills</h3>
            <div class="space-y-3">
              <div v-for="skill in marketOverview.skill_gaps?.slice(0, 5)" :key="skill.skill_name"
                class="flex items-center justify-between">
                <span class="text-slate-600 dark:text-slate-300">{{ skill.skill_name }}</span>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ Math.round(skill.avg_demand) }}</span>
                  <div class="w-16 h-2 bg-slate-200 dark:bg-slate-700 rounded-full">
                    <div class="h-full bg-blue-500 rounded-full" :style="{ width: `${skill.avg_demand}%` }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Salary Benchmarks Tab -->
      <template v-else-if="activeTab === 'salary'">
        <div class="space-y-6">
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-slate-900 dark:text-white">Salary Benchmarks</h3>
            <div class="flex gap-3">
              <MultiSelect v-model="selectedJobTitles" :options="jobTitleOptions" 
                class="w-64" placeholder="Select job titles" @change="loadSalaryBenchmarks" />
              <MultiSelect v-model="selectedLocations" :options="locationOptions" 
                class="w-64" placeholder="Select locations" @change="loadSalaryBenchmarks" />
            </div>
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Salary Ranges</h4>
              <div class="h-64">
                <Chart type="bar" :data="salaryChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Detailed Benchmarks</h4>
              <DataTable :value="salaryBenchmarks" class="text-sm" scrollable scrollHeight="240px">
                <Column field="normalized_title" header="Role" />
                <Column field="location_value" header="Location" />
                <Column field="median_salary" header="Median">
                  <template #body="{ data }">
                    ${{ Math.round(data.median_salary || 0).toLocaleString() }}
                  </template>
                </Column>
                <Column field="total_samples" header="Samples" />
              </DataTable>
            </div>
          </div>
        </div>
      </template>

      <!-- Supply & Demand Tab -->
      <template v-else-if="activeTab === 'supply-demand'">
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Supply & Demand Analysis</h3>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Market Dynamics</h4>
              <div class="h-64">
                <Chart type="bar" :data="supplyDemandChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Market Classification</h4>
              <div class="space-y-3">
                <div v-for="market in supplyDemandData.slice(0, 8)" :key="market.location_value"
                  class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div>
                    <span class="font-medium text-slate-900 dark:text-white">{{ market.location_value }}</span>
                    <p class="text-sm text-slate-500 dark:text-slate-400 capitalize">{{ market.market_type?.replace(/_/g, ' ') }}</p>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ market.avg_supply_demand_ratio?.toFixed(1) }}</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400">ratio</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Competitors Tab -->
      <template v-else-if="activeTab === 'competitors'">
        <div class="space-y-6">
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-slate-900 dark:text-white">Competitor Intelligence</h3>
            <InputText v-model="selectedCompetitors" placeholder="Filter competitors..." class="w-64" />
          </div>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Most Active Competitors</h4>
              <div class="space-y-3">
                <div v-for="competitor in competitorData.intelligence?.slice(0, 10)" :key="competitor.competitor_name"
                  class="flex items-center justify-between">
                  <span class="text-slate-600 dark:text-slate-300">{{ competitor.competitor_name }}</span>
                  <div class="text-right">
                    <span class="font-medium text-slate-900 dark:text-white">{{ competitor.job_postings_count }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 ml-1">jobs</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Competitive Insights</h4>
              <div class="space-y-4">
                <div v-if="competitorData.insights?.market_salary_range" class="p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">Market Salary Range</p>
                  <p class="text-lg font-bold text-slate-900 dark:text-white">
                    ${{ competitorData.insights.market_salary_range.average?.toLocaleString() }}
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Competitive threshold: ${{ competitorData.insights.market_salary_range.competitive_threshold?.toLocaleString() }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Trending Skills Tab -->
      <template v-else-if="activeTab === 'skills'">
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Trending Skills Analysis</h3>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Hot Skills</h4>
              <div class="h-64">
                <Chart type="bar" :data="skillsChartData" :options="chartOptions" />
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Skills by Category</h4>
              <div class="space-y-4">
                <div v-for="(skills, category) in trendingSkills.skills_analysis" :key="category">
                  <h5 class="text-sm font-medium text-slate-900 dark:text-white capitalize mb-2">
                    {{ category.replace(/_/g, ' ') }} ({{ skills?.length || 0 }})
                  </h5>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="skill in skills?.slice(0, 5)" :key="skill.skill_name"
                      class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs rounded">
                      {{ skill.skill_name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Forecast Tab -->
      <template v-else>
        <div class="space-y-6">
          <h3 class="font-semibold text-slate-900 dark:text-white">Market Forecast</h3>

          <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">6-Month Forecast</h4>
              <div v-if="marketForecast.forecast?.length" class="space-y-3">
                <div v-for="month in marketForecast.forecast" :key="month.month"
                  class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <span class="text-slate-600 dark:text-slate-300">{{ month.month }}</span>
                  <div class="text-right">
                    <span class="font-medium text-slate-900 dark:text-white">{{ month.predicted_job_postings }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 ml-1">jobs</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ month.confidence }}% confidence</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                No forecast data available
              </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
              <h4 class="font-medium text-slate-900 dark:text-white mb-4">Forecast Insights</h4>
              <div v-if="marketForecast.confidence_level" class="space-y-4">
                <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">Confidence Level</p>
                  <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ Math.round(marketForecast.confidence_level) }}%</p>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">Methodology</p>
                  <p class="text-sm text-slate-600 dark:text-slate-400">{{ marketForecast.methodology }}</p>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">Data Points</p>
                  <p class="text-lg font-bold text-slate-900 dark:text-white">{{ marketForecast.historical_data_points }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>