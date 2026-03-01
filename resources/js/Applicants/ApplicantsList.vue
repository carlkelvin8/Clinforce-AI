<template>
  <AppLayout>
    <div class="flex flex-col gap-8 max-w-[1600px] mx-auto">
      <!-- Minimal Header -->
      <div class="flex flex-col md:flex-row justify-between items-end gap-4 border-b border-gray-100 pb-6">
        <div class="flex items-center gap-4">
          <Avatar :image="meAvatarUrl" :label="!meAvatarUrl ? meInitials : null" size="large" shape="circle" class="shadow-sm border border-gray-200 bg-white text-indigo-600" />
          <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Applicants</h1>
            <p class="text-gray-500 text-sm mt-1">Manage and track candidate applications</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <Button 
            icon="pi pi-refresh" 
            :loading="loading" 
            @click="fetchData(1)" 
            text 
            rounded
            severity="secondary"
            aria-label="Refresh"
          />
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total -->
        <div class="flex flex-col bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_4px_20px_-4px_rgba(79,70,229,0.1)] transition-shadow duration-300">
            <span class="text-sm text-gray-500 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-users text-blue-500 bg-blue-50 p-1.5 rounded-lg text-xs"></i>
                Total Applicants
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900 tracking-tight">{{ stats.total }}</span>
            </div>
        </div>

        <!-- Submitted -->
        <div class="flex flex-col bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_4px_20px_-4px_rgba(79,70,229,0.1)] transition-shadow duration-300">
            <span class="text-sm text-gray-500 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-file text-orange-500 bg-orange-50 p-1.5 rounded-lg text-xs"></i>
                New / Submitted
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900 tracking-tight">{{ stats.submitted }}</span>
            </div>
        </div>

        <!-- Interview -->
        <div class="flex flex-col bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_4px_20px_-4px_rgba(79,70,229,0.1)] transition-shadow duration-300">
            <span class="text-sm text-gray-500 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-calendar text-purple-500 bg-purple-50 p-1.5 rounded-lg text-xs"></i>
                Interviews
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900 tracking-tight">{{ stats.interview }}</span>
            </div>
        </div>

        <!-- Hired -->
        <div class="flex flex-col bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] hover:shadow-[0_4px_20px_-4px_rgba(79,70,229,0.1)] transition-shadow duration-300">
            <span class="text-sm text-gray-500 font-medium mb-2 flex items-center gap-2">
                <i class="pi pi-check-circle text-emerald-500 bg-emerald-50 p-1.5 rounded-lg text-xs"></i>
                Hired
            </span>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900 tracking-tight">{{ stats.hired }}</span>
            </div>
        </div>
      </div>

      <div v-if="demoMode" class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-2 text-sm">
        Showing demo applications to illustrate statuses. Connect real data to replace this.
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
            <!-- Filters Bar -->
            <div class="p-4 border-b border-gray-100 flex flex-col lg:flex-row gap-4 justify-between items-center bg-white">
                <div class="flex items-center gap-3 w-full lg:w-auto">
                    <IconField iconPosition="left" class="w-full lg:w-64">
                        <InputIcon class="pi pi-search text-gray-400" />
                        <InputText 
                            v-model="search" 
                            placeholder="Search applicants..." 
                            class="w-full !text-sm !py-2 !pl-10 !border-gray-200 !rounded-lg focus:!ring-indigo-500 focus:!border-indigo-500" 
                            @keydown.enter="applyFilters" 
                        />
                    </IconField>
                    <Dropdown 
                        v-model="status" 
                        :options="allowedStatuses" 
                        placeholder="Status" 
                        class="!border-gray-200 !rounded-lg !text-sm w-40"
                        showClear
                        @change="fetchData(1)"
                    />
                </div>
                
                <div class="flex gap-2 w-full lg:w-auto overflow-x-auto pb-1 lg:pb-0">
                     <Button 
                        v-for="s in quickStatuses" 
                        :key="s" 
                        :label="s" 
                        size="small" 
                        :severity="status === s ? 'primary' : 'secondary'" 
                        :text="status !== s"
                        class="!text-xs !px-3 !py-1.5 whitespace-nowrap"
                        @click="status = (status === s ? '' : s); fetchData(1)"
                     />
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="p-4 bg-red-50 border-b border-red-100">
                <Message severity="error" :closable="false" class="!m-0">{{ error }}</Message>
            </div>

            <!-- Data Table -->
            <DataTable 
                :value="items" 
                :loading="loading" 
                responsiveLayout="scroll" 
                :paginator="true" 
                :rows="10"
                class="text-sm"
                :pt="{ 
                    headerRow: { class: 'text-xs uppercase text-gray-400 font-medium bg-gray-50/50 border-b border-gray-100' },
                    bodyRow: { class: 'hover:bg-gray-50/50 transition-colors border-b border-gray-50 last:border-0' },
                    thead: { class: 'bg-gray-50/50' }
                }"
            >
                <template #empty>
                    <div class="text-center p-12">
                        <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="pi pi-users text-gray-300 text-2xl"></i>
                        </div>
                        <div class="text-base font-semibold text-gray-900 mb-1">No applicants found</div>
                        <div class="text-gray-500 text-sm">Try adjusting your search or filters.</div>
                        <Button 
                          label="Clear filters" 
                          @click="resetFilters" 
                          class="mt-3 !bg-indigo-600 !border-indigo-600 !text-white hover:!bg-indigo-700" 
                        />
                    </div>
                </template>

                <Column header="Applicant">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3 py-2">
                            <Avatar 
                                :image="candidateAvatarUrl(data)"
                                :label="!candidateAvatarUrl(data) ? initials(displayApplicantName(data)) : null" 
                                shape="circle" 
                                class="!bg-indigo-50 !text-indigo-600 font-bold w-10 h-10 text-sm border border-indigo-100" 
                            />
                            <div>
                                <div class="font-semibold text-gray-900">{{ displayApplicantName(data) }}</div>
                                <div class="text-xs text-gray-500">Application #{{ data.id }}</div>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Job Role">
                    <template #body="{ data }">
                        <div class="font-medium text-gray-900">{{ data.job?.title || 'Job' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">Applied {{ formatDate(data.submitted_at) }}</div>
                    </template>
                </Column>

                <Column header="Status">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                           <div class="w-2 h-2 rounded-full" :class="{
                               'bg-blue-500': data.status === 'submitted' || data.status === 'new',
                               'bg-yellow-500': data.status === 'interview' || data.status === 'review',
                               'bg-emerald-500': data.status === 'hired' || data.status === 'shortlisted',
                               'bg-red-500': data.status === 'rejected',
                               'bg-gray-400': data.status === 'withdrawn',
                           }"></div>
                           <span class="capitalize text-gray-700 font-medium">{{ data.status }}</span>
                       </div>
                    </template>
                </Column>

                <Column header="Actions" style="width: 100px" alignFrozen="right" frozen>
                    <template #body="{ data }">
                        <Button 
                            icon="pi pi-arrow-right" 
                            label="View" 
                            size="small" 
                            text
                            class="!text-indigo-600 hover:!bg-indigo-50"
                            @click="router.push({ name: 'applicants.view', params: { id: data.id } })" 
                        />
                    </template>
                </Column>
            </DataTable>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { me } from '@/lib/auth'

// PrimeVue
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Message from 'primevue/message'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()

const loading = ref(false)
const error = ref('')
const scope = ref('owned') // owned|mine
const demoMode = ref(false)

const status = ref('')
const search = ref('')

const allowedStatuses = [
  'submitted',
  'shortlisted',
  'rejected',
  'interview',
  'hired',
  'withdrawn',
]

const quickStatuses = ['submitted', 'interview', 'hired', 'rejected']

const raw = ref(null)

const hasActiveFilters = computed(() => !!status.value || !!search.value.trim())

const items = computed(() => {
  // Handle paginated response - data.data is the array
  const rows = Array.isArray(raw.value?.data?.data) ? raw.value.data.data : 
               Array.isArray(raw.value?.data) ? raw.value.data : []
  const q = search.value.trim().toLowerCase()

  return rows.filter((r) => {
    if (status.value && r.status !== status.value) return false
    if (!q) return true

    const hay = [
      String(r.id || ''),
      String(r.applicant_user_id || ''),
      String(r.status || ''),
      String(r.job?.title || ''),
    ].join(' ').toLowerCase()

    return hay.includes(q)
  })
})

const stats = computed(() => {
  // Handle paginated response - data.data is the array
  const rows = Array.isArray(raw.value?.data?.data) ? raw.value.data.data : 
               Array.isArray(raw.value?.data) ? raw.value.data : []
  const count = (s) => rows.filter((r) => r.status === s).length
  return {
    total: rows.length,
    submitted: count('submitted'),
    interview: count('interview'),
    hired: count('hired'),
  }
})

const pagination = computed(() => {
  if (!raw.value) return null
  return {
    current_page: raw.value.current_page,
    last_page: raw.value.last_page,
    next_page_url: raw.value.next_page_url,
    prev_page_url: raw.value.prev_page_url,
  }
})

function initials(v) {
  const s = String(v ?? '').trim()
  if (!s) return 'A'
  const parts = s.split(/\s+/).filter(Boolean)
  const first = parts[0]?.[0] || ''
  const last = parts.length > 1 ? parts[parts.length - 1]?.[0] : ''
  return (first + last).toUpperCase() || s.slice(0, 2).toUpperCase()
}

function buildAvatarUrl(path) {
  if (!path) return null
  const p = String(path).replace(/^\/+/, '')
  if (/^https?:\/\//i.test(p)) return path
  if (p.startsWith('uploads/')) return '/' + p
  return '/storage/' + p
}

function candidateAvatarUrl(row) {
  if (!row) return null
  const u = row.applicant || row.user || null
  if (u?.avatar_url) return u.avatar_url
  const raw =
    u?.applicant_profile?.avatar ||
    u?.applicantProfile?.avatar ||
    null
  return buildAvatarUrl(raw)
}

function displayApplicantName(row) {
  const u = row?.applicant || row?.user || null
  const p = u?.applicant_profile || u?.applicantProfile || null
  const fn = (p?.first_name || '').trim()
  const ln = (p?.last_name || '').trim()
  const name = fn ? `${fn}${ln ? ' ' + ln[0].toUpperCase() + '.' : ''}` : ''
  return (
    name ||
    p?.public_display_name ||
    u?.name ||
    u?.full_name ||
    `Applicant #${row?.applicant_user_id || row?.applicant_id || row?.user_id || ''}`.trim()
  )
}

function formatDate(v) {
  if (!v) return 'N/A'
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

async function fetchData(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { scope: scope.value, page }
    if (status.value) params.status = status.value

    console.log('Fetching applications with params:', params)
    const res = await api.get('/api/applications', { params })
    console.log('Applications response:', res.data)
    console.log('Response data.data:', res.data.data)
    
    // Handle different response structures
    if (res.data?.data?.applications) {
      // Employer response: { data: { applications: {...}, has_subscription: true } }
      raw.value = res.data.data.applications
      console.log('Using employer structure (applications key), raw.value:', raw.value)
    } else if (res.data?.data?.data) {
      // Paginated: { data: { data: [...], current_page, total, etc } }
      raw.value = res.data.data
      console.log('Using paginated structure, raw.value:', raw.value)
    } else if (res.data?.data) {
      // Wrapped: { data: [...] }
      raw.value = { data: res.data.data }
      console.log('Using wrapped structure, raw.value:', raw.value)
    } else {
      // Direct: [...]
      raw.value = { data: res.data }
      console.log('Using direct structure, raw.value:', raw.value)
    }
    
    console.log('Final raw.value:', raw.value)
    console.log('Final raw.value.data:', raw.value?.data)
    console.log('Is array?', Array.isArray(raw.value?.data))
    console.log('Items count:', raw.value?.data?.length || 'N/A')
    demoMode.value = false
  } catch (e) {
    console.error('Fetch error:', e)
    error.value = e?.__payload?.message || e?.message || 'Request failed'
    raw.value = null
  } finally {
    loading.value = false
  }
}

function applyFilters() { fetchData(1) }
function resetFilters() { status.value = ''; search.value = ''; fetchData(1) }

const meAvatarUrl = ref(null)
const meInitials = ref('EM')

onMounted(async () => { 
    fetchData(1) 
    try {
        const u = await me()
        if (u) {
             meAvatarUrl.value = u.avatar_url
             meInitials.value = (u.name || u.email || 'EM').charAt(0).toUpperCase()
        }
    } catch (e) { console.error(e) }
})
</script>

<style scoped>
/* No custom styles needed */
</style>
