<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import Chart from 'primevue/chart'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Select from 'primevue/select'
import ProgressBar from 'primevue/progressbar'
import Divider from 'primevue/divider'

const loading = ref(false)
const error = ref('')
const data = ref(null)
const appAnalytics = ref(null)

const rangeOptions = [
  { label: 'Last 7 days', value: '7d' },
  { label: 'Last 30 days', value: '30d' },
  { label: 'Last 90 days', value: '90d' },
  { label: 'Last year', value: '1y' },
  { label: 'All time', value: 'all' },
]
const range = ref('30d')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [dashRes, analyticsRes] = await Promise.all([
      api.get('/analytics/dashboard', { params: { days: parseInt(range.value) || 30 } }),
      api.get('/analytics/applications', { params: { period: range.value } }),
    ])
    data.value = dashRes.data?.data ?? dashRes.data
    appAnalytics.value = analyticsRes.data?.data ?? analyticsRes.data
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

// Funnel chart
const funnelChart = computed(() => {
  const funnel = appAnalytics.value?.funnel ?? {}
  const order = ['submitted', 'shortlisted', 'interview', 'hired', 'rejected']
  const colors = ['#3b82f6', '#6366f1', '#f59e0b', '#10b981', '#ef4444']
  const labels = { submitted: 'Applied', shortlisted: 'Shortlisted', interview: 'Interview', hired: 'Hired', rejected: 'Rejected' }

  const items = order.filter(k => funnel[k]).map((k, i) => ({
    label: labels[k] || k,
    value: funnel[k]?.count ?? 0,
    color: colors[i],
  }))

  return {
    labels: items.map(i => i.label),
    datasets: [{
      data: items.map(i => i.value),
      backgroundColor: items.map(i => i.color),
      hoverBackgroundColor: items.map(i => i.color + 'cc'),
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

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '60%',
  plugins: {
    legend: { position: 'bottom', labels: { color: '#64748b', padding: 16, usePointStyle: true } },
    tooltip: { backgroundColor: '#1e293b', bodyColor: '#f1f5f9' }
  },
}

const kpis = computed(() => data.value?.kpis ?? {})
const analytics = computed(() => appAnalytics.value ?? {})
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
          <Select v-model="range" :options="rangeOptions" optionLabel="label" optionValue="value" class="w-44" @update:modelValue="load" />
          <Button icon="pi pi-refresh" severity="secondary" outlined @click="load" :loading="loading" />
        </div>
      </div>

      <div v-if="error" class="bg-red-50 text-red-600 p-4 rounded-xl text-sm">{{ error }}</div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total Applications</div>
          <div class="text-3xl font-bold text-slate-900">{{ analytics.total_applications ?? kpis.total_applications ?? '—' }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">View Rate</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ analytics.view_rate != null ? analytics.view_rate + '%' : (kpis.conversion_rate != null ? kpis.conversion_rate + '%' : '—') }}
          </div>
          <div class="text-xs text-slate-400">Applied → Interview</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg. Response Time</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ analytics.avg_response_time_formatted ?? '—' }}
          </div>
          <div class="text-xs text-slate-400">Apply → Shortlist</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Avg. Time to Hire</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ analytics.avg_time_to_hire_formatted ?? (kpis.avg_time_to_hire_days ? kpis.avg_time_to_hire_days + 'd' : '—') }}
          </div>
          <div class="text-xs text-slate-400">Apply → Hired</div>
        </div>
      </div>

      <!-- Secondary KPI row -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Success Rate</div>
          <div class="text-3xl font-bold text-emerald-600">
            {{ analytics.success_rate != null ? analytics.success_rate + '%' : '—' }}
          </div>
          <div class="text-xs text-slate-400">Applications → Hired</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Pipeline Velocity</div>
          <div class="text-3xl font-bold text-slate-900">
            {{ analytics.pipeline_velocity_per_week ?? '—' }}
          </div>
          <div class="text-xs text-slate-400">Applications / week</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Interviews</div>
          <div class="text-3xl font-bold text-slate-900">{{ analytics.interview_count ?? kpis.interviews_scheduled ?? '—' }}</div>
          <div class="text-xs text-slate-400">Total scheduled</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 space-y-1">
          <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">Active Jobs</div>
          <div class="text-3xl font-bold text-slate-900">{{ kpis.active_jobs ?? '—' }}</div>
        </div>
      </div>

      <!-- Pipeline Funnel + Applications Trend -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Funnel -->
        <Card class="!border !border-slate-200 !shadow-none">
          <template #title>Pipeline Funnel</template>
          <template #content>
            <div v-if="loading" class="h-64 flex items-center justify-center">
              <i class="pi pi-spin pi-spinner text-2xl text-slate-300"></i>
            </div>
            <div v-else-if="!funnelChart.datasets[0].data.length" class="h-64 flex items-center justify-center text-slate-400 text-sm">
              No data available
            </div>
            <div v-else class="space-y-4">
              <!-- Visual funnel bars -->
              <div v-for="(item, idx) in funnelChart.labels.map((l, i) => ({ label: l, value: funnelChart.datasets[0].data[i], color: funnelChart.datasets[0].backgroundColor[i] }))" :key="item.label" class="space-y-1">
                <div class="flex items-center justify-between text-sm">
                  <span class="font-medium text-slate-700">{{ item.label }}</span>
                  <span class="text-slate-500">{{ item.value }}</span>
                </div>
                <ProgressBar :value="analytics.total_applications ? (item.value / analytics.total_applications * 100) : 0"
                  :style="`height: 8px`"
                  :class="item.color === '#3b82f6' ? '!bg-blue-500' :
                         item.color === '#6366f1' ? '!bg-indigo-500' :
                         item.color === '#f59e0b' ? '!bg-amber-500' :
                         item.color === '#10b981' ? '!bg-emerald-500' : '!bg-red-500'" />
              </div>
              <!-- Donut chart -->
              <div class="h-48 mt-4">
                <Chart type="doughnut" :data="funnelChart" :options="doughnutOptions" class="w-full h-full" />
              </div>
            </div>
          </template>
        </Card>

        <!-- Trend -->
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

      <!-- Applications per Job Bar Chart -->
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

      <!-- Per-job breakdown table -->
      <Card class="!border !border-slate-200 !shadow-none">
        <template #title>Job Breakdown</template>
        <template #content>
          <div v-if="loading" class="py-8 text-center text-slate-400">
            <i class="pi pi-spin pi-spinner text-2xl"></i>
          </div>
          <div v-else-if="!(analytics.per_job?.length)" class="py-8 text-center text-slate-400 text-sm">
            No data
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-100">
                  <th class="text-left py-3 px-2 text-slate-500 font-medium">Job</th>
                  <th class="text-center py-3 px-2 text-slate-500 font-medium">Total</th>
                  <th class="text-center py-3 px-2 text-slate-500 font-medium">Interviews</th>
                  <th class="text-center py-3 px-2 text-slate-500 font-medium">Hired</th>
                  <th class="text-center py-3 px-2 text-slate-500 font-medium">View Rate</th>
                  <th class="text-center py-3 px-2 text-slate-500 font-medium">Success Rate</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in analytics.per_job" :key="item.job_id" class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                  <td class="py-3 px-2 font-medium text-slate-900">{{ item.job_title }}</td>
                  <td class="py-3 px-2 text-center text-slate-700">{{ item.total }}</td>
                  <td class="py-3 px-2 text-center text-amber-600 font-semibold">{{ item.interviews }}</td>
                  <td class="py-3 px-2 text-center text-emerald-600 font-semibold">{{ item.hired }}</td>
                  <td class="py-3 px-2 text-center">
                    <Tag :value="item.view_rate + '%'" :severity="item.view_rate >= 30 ? 'success' : item.view_rate >= 15 ? 'warn' : 'danger'" />
                  </td>
                  <td class="py-3 px-2 text-center">
                    <Tag :value="item.success_rate + '%'" :severity="item.success_rate >= 10 ? 'success' : item.success_rate >= 5 ? 'warn' : 'danger'" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
