<template>
  <AppLayout>
    <div class="flex flex-col gap-8 max-w-[1600px] mx-auto">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-end gap-4 border-b border-gray-100 pb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Applicants</h1>
          <p class="text-gray-500 text-sm mt-1">Manage and track candidate applications</p>
        </div>
        <Button icon="pi pi-refresh" :loading="loading" @click="fetchData(1)" text rounded severity="secondary" aria-label="Refresh" />
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <template v-if="loading && !raw">
          <SkeletonCard v-for="n in 4" :key="n" type="kpi" />
        </template>
        <template v-else>
          <div v-for="stat in statCards" :key="stat.label" class="flex flex-col bg-white p-5 rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
            <span class="text-sm text-gray-500 font-medium mb-2 flex items-center gap-2">
              <i :class="[stat.icon, stat.color, 'p-1.5 rounded-lg text-xs', stat.bg]"></i>
              {{ stat.label }}
            </span>
            <span class="text-3xl font-bold text-gray-900 tracking-tight">{{ stat.value }}</span>
          </div>
        </template>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
        <!-- Filters Bar -->
        <div class="p-4 border-b border-gray-100 flex flex-col lg:flex-row gap-4 justify-between items-center bg-white">
          <div class="flex items-center gap-3 w-full lg:w-auto">
            <IconField iconPosition="left" class="w-full lg:w-64">
              <InputIcon class="pi pi-search text-gray-400" />
              <InputText v-model="search" placeholder="Search applicants..." class="w-full !text-sm !py-2 !pl-10 !border-gray-200 !rounded-lg" @keydown.enter="fetchData(1)" />
            </IconField>
            <Select v-model="status" :options="allowedStatuses" placeholder="Status" class="!border-gray-200 !rounded-lg !text-sm w-40" showClear @change="fetchData(1)" />
          </div>
          <div class="flex gap-2 w-full lg:w-auto overflow-x-auto pb-1 lg:pb-0">
            <Button v-for="s in quickStatuses" :key="s" :label="s" size="small"
              :severity="status === s ? 'primary' : 'secondary'" :text="status !== s"
              class="!text-xs !px-3 !py-1.5 whitespace-nowrap"
              @click="status = (status === s ? '' : s); fetchData(1)" />
          </div>
        </div>

        <!-- Bulk Action Toolbar -->
        <div v-if="selectedItems.length > 0" class="px-4 py-2.5 bg-indigo-50 border-b border-indigo-100 flex items-center gap-3">
          <span class="text-sm font-semibold text-indigo-700">{{ selectedItems.length }} selected</span>
          <div class="flex gap-2">
            <Button v-for="opt in bulkActionOptions" :key="opt.value" :label="opt.label" size="small" severity="secondary" outlined
              class="!text-xs !border-indigo-200 !text-indigo-700 hover:!bg-indigo-100" :loading="bulkLoading" @click="executeBulkAction(opt.value)" />
          </div>
          <Button icon="pi pi-times" text size="small" severity="secondary" class="ml-auto !text-indigo-500" @click="selectedItems = []" />
        </div>

        <!-- Error -->
        <div v-if="error" class="p-4 bg-red-50 border-b border-red-100">
          <Message severity="error" :closable="false" class="!m-0">{{ error }}</Message>
        </div>

        <!-- Table -->
        <DataTable :value="items" :loading="loading" responsiveLayout="scroll"
          v-model:selection="selectedItems" class="text-sm"
          :pt="{
            headerRow: { class: 'text-xs uppercase text-gray-400 font-medium bg-gray-50/50 border-b border-gray-100' },
            bodyRow: { class: 'hover:bg-gray-50/50 transition-colors border-b border-gray-50 last:border-0' },
            thead: { class: 'bg-gray-50/50' }
          }">
          <template #empty>
            <div class="text-center p-16">
              <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="pi pi-users text-indigo-300 text-3xl"></i>
              </div>
              <div class="text-base font-semibold text-gray-900 mb-1">No applicants found</div>
              <div class="text-gray-500 text-sm mb-4">Try adjusting your search or filters.</div>
              <Button label="Clear filters" @click="resetFilters" class="!bg-indigo-600 !border-indigo-600 !text-white hover:!bg-indigo-700" />
            </div>
          </template>

          <Column selectionMode="multiple" style="width: 3rem" />

          <Column header="Applicant">
            <template #body="{ data }">
              <div class="flex items-center gap-3 py-2">
                <Avatar :image="candidateAvatarUrl(data)" :label="!candidateAvatarUrl(data) ? initials(displayApplicantName(data)) : null"
                  shape="circle" class="!bg-indigo-50 !text-indigo-600 font-bold w-10 h-10 text-sm border border-indigo-100" />
                <div>
                  <div class="font-semibold text-gray-900">{{ displayApplicantName(data) }}</div>
                  <div class="text-xs text-gray-500">{{ data.job?.title || 'Job Application' }}</div>
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
              <Button icon="pi pi-arrow-right" label="View" size="small" text class="!text-indigo-600 hover:!bg-indigo-50"
                @click="router.push({ name: 'applicants.view', params: { id: data.id } })" />
            </template>
          </Column>
        </DataTable>

        <!-- Server-side Paginator -->
        <div v-if="totalRecords > perPage" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50/50">
          <span class="text-xs text-gray-500">Showing {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, totalRecords) }} of {{ totalRecords }}</span>
          <div class="flex gap-1">
            <Button icon="pi pi-chevron-left" text size="small" :disabled="currentPage <= 1" @click="fetchData(currentPage - 1)" />
            <span class="px-3 py-1 text-sm font-medium text-gray-700">{{ currentPage }} / {{ lastPage }}</span>
            <Button icon="pi pi-chevron-right" text size="small" :disabled="currentPage >= lastPage" @click="fetchData(currentPage + 1)" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import SkeletonCard from '@/Components/SkeletonCard.vue'
import api from '@/lib/api'
import { toast } from '@/composables/useAppToast'

import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Avatar from 'primevue/avatar'
import Message from 'primevue/message'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const demoMode = ref(false)
const status = ref('')
const search = ref('')
const selectedItems = ref([])
const bulkLoading = ref(false)

// Server-side pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalRecords = ref(0)
const perPage = ref(15)

const items = ref([])
const raw = ref(null)

const statCards = computed(() => [
  { label: 'Total Applicants', value: totalRecords.value, icon: 'pi pi-users', color: 'text-blue-500', bg: 'bg-blue-50' },
  { label: 'New / Submitted', value: items.value.filter(r => r.status === 'submitted').length, icon: 'pi pi-file', color: 'text-orange-500', bg: 'bg-orange-50' },
  { label: 'Interviews', value: items.value.filter(r => r.status === 'interview').length, icon: 'pi pi-calendar', color: 'text-purple-500', bg: 'bg-purple-50' },
  { label: 'Hired', value: items.value.filter(r => r.status === 'hired').length, icon: 'pi pi-check-circle', color: 'text-emerald-500', bg: 'bg-emerald-50' },
])

const bulkActionOptions = [
  { label: 'Shortlist selected', value: 'shortlist' },
  { label: 'Reject selected', value: 'reject' },
  { label: 'Move to Interview', value: 'interview' },
]

const allowedStatuses = ['submitted', 'shortlisted', 'rejected', 'interview', 'hired', 'withdrawn']
const quickStatuses = ['submitted', 'interview', 'hired', 'rejected']

async function executeBulkAction(action) {
  if (!selectedItems.value.length) return
  bulkLoading.value = true
  try {
    const ids = selectedItems.value.map(i => i.id)
    await api.post('/applications/bulk-action', { application_ids: ids, action })
    selectedItems.value = []
    toast.success('Bulk action applied', `${ids.length} application(s) updated`)
    await fetchData(currentPage.value)
  } catch (e) {
    toast.error('Bulk action failed', e?.response?.data?.message || e?.message)
  } finally {
    bulkLoading.value = false
  }
}

async function fetchData(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { scope: 'owned', page, per_page: perPage.value }
    if (status.value) params.status = status.value
    if (search.value.trim()) params.search = search.value.trim()

    const res = await api.get('/applications', { params })
    const body = res.data?.data

    // Handle employer paginated response
    let paginator = null
    if (body?.applications?.data) {
      paginator = body.applications
    } else if (body?.data) {
      paginator = body
    } else if (Array.isArray(body)) {
      items.value = body
      totalRecords.value = body.length
      lastPage.value = 1
      currentPage.value = 1
      raw.value = body
      return
    }

    if (paginator) {
      items.value = paginator.data || []
      totalRecords.value = paginator.total || items.value.length
      lastPage.value = paginator.last_page || 1
      currentPage.value = paginator.current_page || page
    }

    raw.value = paginator
    demoMode.value = false
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'Request failed'
    items.value = []
  } finally {
    loading.value = false
  }
}

function resetFilters() { status.value = ''; search.value = ''; fetchData(1) }

function initials(v) {
  const s = String(v ?? '').trim()
  if (!s) return 'A'
  const parts = s.split(/\s+/).filter(Boolean)
  return ((parts[0]?.[0] || '') + (parts.length > 1 ? parts[parts.length - 1]?.[0] : '')).toUpperCase() || s.slice(0, 2).toUpperCase()
}

function buildAvatarUrl(path) {
  if (!path) return null
  const p = String(path).replace(/^\/+/, '')
  if (/^https?:\/\//i.test(p)) return path
  return p.startsWith('uploads/') ? '/' + p : '/storage/' + p
}

function candidateAvatarUrl(row) {
  if (!row) return null
  const u = row.applicant || row.user || null
  if (u?.avatar_url) return u.avatar_url
  return buildAvatarUrl(u?.applicant_profile?.avatar || u?.applicantProfile?.avatar || null)
}

function displayApplicantName(row) {
  const u = row?.applicant || row?.user || null
  const p = u?.applicant_profile || u?.applicantProfile || null
  const fn = (p?.first_name || '').trim()
  const ln = (p?.last_name || '').trim()
  const name = fn ? `${fn}${ln ? ' ' + ln[0].toUpperCase() + '.' : ''}` : ''
  return name || p?.public_display_name || u?.name || u?.full_name || `Applicant #${row?.applicant_user_id || row?.id || ''}`.trim()
}

function formatDate(v) {
  if (!v) return 'N/A'
  const d = new Date(v)
  return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => fetchData(1))
</script>
