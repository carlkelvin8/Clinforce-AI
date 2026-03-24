<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import Chart from 'primevue/chart'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Select from 'primevue/select'

const loading = ref(false)
const error = ref('')
const data = ref(null)

const rangeOptions = [
  { label: 'Last 30 days', value: 30 },
  { label: 'Last 60 days', value: 60 },
  { label: 'Last 90 days', value: 90 },
]
const range = ref(30)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/analytics/dashboard', { params: { days: range.value } })
    data.value = res.data?.data ?? res.data
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Failed to load analytics'
  } finally {
    loading.value = false
  }
}

onMounted(load)

// Applications per job bar chart
const appsPerJobChart = computed(() => {
  const items = data.value?.applications_per_job ?? []
  return {
    labels: items.map(i => i.title || `Job #${i.job_id}`),
    datasets: [{
      label: 'Applications',
      data: items.map(i => i.count),
      backgroundColor: 'rgba(99,102,241,0.8)',
      borderRadius: 8,
      barThickness: 32,
    }]
  }
})

// Trend line chart
const trendChart = computed(() => {
  const trend = data.value?.trend ?? []
  return {
    labels: trend.map(t => t.date),
    datasets: [{
      label: 'Applications',
      data: trend.map(t => t.count),
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99,102,241,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 3,
    }]
  }
})

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { backgroundColor: '#1e293b', bodyColor: '#f1f5f9' }
  },
  scales: {
    y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: { color: '#f1f5f9' } },
    x: { ticks: { color: '#64748b', maxRotation: 30 }, grid: { display: false } }
  }
}

const lineOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: { backgroundColor: '#1e293b', bodyColor: '#f1f5f9' }
  },
  scales: {
    y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: { color: '#f1f5f9' } },
    x: { ticks: { color: '#64748b', maxRotation: 30 }, grid: { display: false } }
  }
}

const kpis = computed(() => data.value?.kpis ?? {})
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Analytics</h1>
          <p class="text-slate-500 mt-1">Hiring performance and pipeline insights.</p>
        </div>
        <div class="flex items-center gap-2">
          <Select v-model="range" :options="rangeOptions" optionLabel="label" optionValue="value" class="w-44" @change="load" />
          <Button icon="pi pi-refresh" severity="secondary" outlined @click="load" :loading="loading" />
        </div>
      </div>

      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm">{{ error }}</div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Applications</div>
          <div class="text-3xl font-bold text-slate-900">{{ kpis.total_applications ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Conversion Rate</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ kpis.conversion_rate != null ? kpis.conversion_rate + '%' : '—' }}
          </div>
          <div class="text-xs text-slate-400">Applications → Hired</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg. Time to Hire</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ kpis.avg_time_to_hire_days != null ? kpis.avg_time_to_hire_days + 'd' : '—' }}
          </div>
          <div class="text-xs text-slate-400">Days from apply to hire</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Active Jobs</div>
          <div class="text-3xl font-bold text-slate-900">{{ kpis.active_jobs ?? '—' }}</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card class="!border !border-slate-200 !shadow-none">
          <template #title>Applications per Job</template>
          <template #content>
            <div v-if="loading" class="h-64 flex items-center justify-center">
              <i class="pi pi-spin pi-spinner text-2xl text-slate-300"></i>
            </div>
            <div v-else-if="!appsPerJobChart.labels.length" class="h-64 flex items-center justify-center text-slate-400 text-sm">
              No data available
            </div>
            <div v-else class="h-64">
              <Chart type="bar" :data="appsPerJobChart" :options="barOptions" class="w-full h-full" />
            </div>
          </template>
        </Card>

        <Card class="!border !border-slate-200 !shadow-none">
          <template #title>Application Trend</template>
          <template #content>
            <div v-if="loading" class="h-64 flex items-center justify-center">
              <i class="pi pi-spin pi-spinner text-2xl text-slate-300"></i>
            </div>
            <div v-else-if="!trendChart.labels.length" class="h-64 flex items-center justify-center text-slate-400 text-sm">
              No data available
            </div>
            <div v-else class="h-64">
              <Chart type="line" :data="trendChart" :options="lineOptions" class="w-full h-full" />
            </div>
          </template>
        </Card>
      </div>

      <!-- Applications per job table -->
      <Card class="!border !border-slate-200 !shadow-none">
        <template #title>Job Breakdown</template>
        <template #content>
          <div v-if="loading" class="py-8 text-center text-slate-400">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
          </div>
          <div v-else-if="!(data?.applications_per_job?.length)" class="py-8 text-center text-slate-400 text-sm">
            No data
          </div>
          <div v-else class="divide-y divide-slate-100">
            <div v-for="item in data.applications_per_job" :key="item.job_id"
              class="flex items-center justify-between py-3 px-1">
              <div>
                <div class="font-medium text-slate-900">{{ item.title || `Job #${item.job_id}` }}</div>
                <div class="text-xs text-slate-500 mt-0.5">{{ item.hired ?? 0 }} hired · {{ item.rejected ?? 0 }} rejected</div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-right">
                  <div class="text-lg font-bold text-slate-900">{{ item.count }}</div>
                  <div class="text-xs text-slate-400">applications</div>
                </div>
                <Tag v-if="item.conversion_rate != null" :value="item.conversion_rate + '%'" severity="success" />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
