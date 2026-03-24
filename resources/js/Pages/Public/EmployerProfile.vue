<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api from '@/lib/api'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const route = useRoute()
const loading = ref(false)
const error = ref('')
const employer = ref(null)
const jobs = ref([])

async function load() {
  loading.value = true
  error.value = ''
  try {
    const slug = route.params.slug
    const res = await api.get(`/employers/${slug}`)
    const data = res.data?.data ?? res.data
    employer.value = data?.employer ?? data
    jobs.value = data?.jobs ?? []
  } catch (e) {
    error.value = e?.response?.status === 404 ? 'Employer not found.' : (e?.response?.data?.message || 'Failed to load profile')
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans">
    <!-- Simple nav -->
    <nav class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
      <RouterLink to="/" class="text-xl font-bold text-slate-900 no-underline">ClinForce</RouterLink>
      <RouterLink to="/login">
        <Button label="Sign in" severity="secondary" outlined size="small" />
      </RouterLink>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-10">
      <div v-if="loading" class="py-20 text-center text-slate-400">
        <i class="pi pi-spin pi-spinner text-3xl"></i>
      </div>

      <div v-else-if="error" class="py-20 text-center text-slate-500">
        <i class="pi pi-exclamation-circle text-4xl text-slate-300 mb-3"></i>
        <p class="font-medium">{{ error }}</p>
      </div>

      <template v-else-if="employer">
        <!-- Header -->
        <div class="bg-white rounded-2xl border border-slate-200 p-8 mb-6">
          <div class="flex flex-col sm:flex-row items-start gap-6">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-100 to-blue-100 flex items-center justify-center text-3xl font-bold text-indigo-600 shrink-0">
              {{ (employer.business_name || 'E').charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <h1 class="text-3xl font-bold text-slate-900">{{ employer.business_name }}</h1>
              <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-slate-500">
                <span v-if="employer.business_type" class="capitalize">{{ employer.business_type.replace('_', ' ') }}</span>
                <span v-if="employer.city || employer.country" class="flex items-center gap-1">
                  <i class="pi pi-map-marker text-xs"></i>
                  {{ [employer.city, employer.country].filter(Boolean).join(', ') }}
                </span>
                <a v-if="employer.website_url" :href="employer.website_url" target="_blank" rel="noreferrer"
                  class="flex items-center gap-1 text-blue-600 hover:underline">
                  <i class="pi pi-external-link text-xs"></i>
                  Website
                </a>
              </div>
              <p v-if="employer.description" class="mt-4 text-slate-700 leading-relaxed">{{ employer.description }}</p>
            </div>
          </div>
        </div>

        <!-- Open Jobs -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
          <h2 class="text-xl font-bold text-slate-900 mb-4">Open Positions</h2>
          <div v-if="!jobs.length" class="py-8 text-center text-slate-400 text-sm">
            No open positions at this time.
          </div>
          <div v-else class="space-y-3">
            <RouterLink
              v-for="job in jobs"
              :key="job.id"
              :to="{ name: 'candidate.jobs.view', params: { id: job.id } }"
              class="no-underline block"
            >
              <div class="flex items-center justify-between p-4 rounded-xl border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all cursor-pointer">
                <div>
                  <div class="font-semibold text-slate-900">{{ job.title }}</div>
                  <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                    <span v-if="job.city">{{ job.city }}</span>
                    <Tag v-if="job.employment_type" :value="job.employment_type" severity="secondary" class="!text-[10px]" />
                    <Tag v-if="job.work_mode" :value="job.work_mode" severity="info" class="!text-[10px]" />
                  </div>
                </div>
                <i class="pi pi-arrow-right text-slate-400"></i>
              </div>
            </RouterLink>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
