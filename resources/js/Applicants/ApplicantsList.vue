<template>
  <AppLayout>
    <div class="flex flex-col gap-8 max-w-[1600px] mx-auto">
      <!-- Header -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Candidates</h1>
          <p class="text-gray-500 text-sm mt-1">Manage and track candidate applications</p>
        </div>
        <div class="flex items-center gap-2 ml-auto">
          <Button icon="pi pi-download" label="Export CSV" size="small" severity="secondary" outlined @click="exportCsv" :loading="exporting" class="!text-sm" />
          <Button icon="pi pi-refresh" :loading="loading" @click="fetchData(1)" text rounded severity="secondary" aria-label="Refresh" />
        </div>
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
              <InputText v-model="search" placeholder="Search candidates..." class="w-full !text-sm !py-2 !pl-10 !border-gray-200 !rounded-lg" @keydown.enter="fetchData(1)" />
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
            <Button v-for="opt in bulkActionOptions.filter(o => o.value !== 'reject')" :key="opt.value"
              :label="opt.label" size="small" severity="secondary" outlined
              class="!text-xs !border-indigo-200 !text-indigo-700 hover:!bg-indigo-100" :loading="bulkLoading" @click="executeBulkAction(opt.value)" />
            <!-- Quick reject with reason -->
            <Button label="Reject with reason" size="small" severity="danger" outlined
              class="!text-xs" @click="openBulkReject" />
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
              <div class="text-base font-semibold text-gray-900 mb-1">No candidates found</div>
              <div class="text-gray-500 text-sm mb-4">Try adjusting your search or filters.</div>
              <Button label="Clear filters" @click="resetFilters" class="!bg-indigo-600 !border-indigo-600 !text-white hover:!bg-indigo-700" />
            </div>
          </template>

          <Column selectionMode="multiple" style="width: 3rem" />

          <Column header="Candidate">
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
              <div class="flex items-center gap-1">
                <!-- Star rating -->
                <div class="flex gap-0.5 mr-1">
                  <button v-for="star in 5" :key="star"
                    @click.stop="rateCandidate(data, star)"
                    class="text-sm transition-colors"
                    :class="star <= (data.employer_rating || 0) ? 'text-amber-400' : 'text-gray-200 hover:text-amber-300'">
                    ★
                  </button>
                </div>
                <Button icon="pi pi-arrow-right" label="View" size="small" text class="!text-indigo-600 hover:!bg-indigo-50"
                  @click="router.push({ name: 'applicants.view', params: { id: data.id } })" />
              </div>
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

  <!-- Quick Reject Dialog -->
  <Dialog v-model:visible="rejectDialog" header="Reject with reason" :style="{ width: '480px' }" modal>
    <div class="space-y-4 pt-2">
      <p class="text-sm text-slate-600">Rejecting <strong>{{ selectedItems.length }}</strong> candidate(s). An email will be sent with the message below.</p>
      <div class="space-y-2">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Template</label>
        <div class="flex flex-col gap-1.5">
          <button v-for="(t, i) in REJECT_TEMPLATES" :key="i"
            @click="rejectReason = t"
            :class="['text-left text-xs px-3 py-2 rounded-lg border transition-colors',
              rejectReason === t ? 'border-red-300 bg-red-50 text-red-800' : 'border-slate-200 hover:border-slate-300 text-slate-600']">
            {{ t.slice(0, 80) }}...
          </button>
        </div>
      </div>
      <div class="space-y-1.5">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Message (editable)</label>
        <Textarea v-model="rejectReason" rows="4" class="w-full text-sm" />
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <Button label="Cancel" severity="secondary" @click="rejectDialog = false" />
        <Button label="Send & Reject" icon="pi pi-times" severity="danger" :loading="rejectingBulk" @click="confirmBulkReject" />
      </div>
    </div>
  </Dialog>
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
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const demoMode = ref(false)
const status = ref('')
const search = ref('')
const selectedItems = ref([])
const bulkLoading = ref(false)
const exporting = ref(false)

async function exportCsv() {
  exporting.value = true
  try {
    const res = await api.get('/applications/export', { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }))
    const a = document.createElement('a')
    a.href = url; a.download = 'candidates-export.csv'; a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Export failed', e?.response?.data?.message || e?.message)
  } finally {
    exporting.value = false }
}

async function rateCandidate(row, rating) {
  const prev = row.employer_rating
  row.employer_rating = rating
  try {
    await api.post(`/applications/${row.id}/rate`, { rating })
    toast.success('Rating saved')
  } catch (e) {
    row.employer_rating = prev
    toast.error('Failed to save rating')
  }
}

// Server-side pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalRecords = ref(0)
const perPage = ref(15)

const items = ref([])
const raw = ref(null)
const analyticsStats = ref(null)

const statCards = computed(() => {
  // Use analytics pipeline data if available (covers all pages)
  if (analyticsStats.value?.pipeline) {
    const p = analyticsStats.value.pipeline
    const newCount = (p['pending'] || 0) + (p['submitted'] || 0) + (p['applied'] || 0) + (p['new'] || 0)
    const interviewCount = (p['interview'] || 0) + (p['interviewed'] || 0) + (p['reviewing'] || 0) + (p['assessment'] || 0)
    const hiredCount = (p['hired'] || 0)
    return [
      { label: 'Total Candidates', value: analyticsStats.value.kpis?.total_applications || totalRecords.value, icon: 'pi pi-users', color: 'text-blue-500', bg: 'bg-blue-50' },
      { label: 'New / Submitted', value: newCount, icon: 'pi pi-file', color: 'text-orange-500', bg: 'bg-orange-50' },
      { label: 'Interviews', value: interviewCount, icon: 'pi pi-calendar', color: 'text-purple-500', bg: 'bg-purple-50' },
      { label: 'Hired', value: hiredCount, icon: 'pi pi-check-circle', color: 'text-emerald-500', bg: 'bg-emerald-50' },
    ]
  }
  // Fallback: count from current page items
  const newCount = items.value.filter(r => ['submitted', 'new', 'pending', 'applied'].includes(r.status)).length
  const interviewCount = items.value.filter(r => ['interview', 'interviewed', 'reviewing', 'assessment'].includes(r.status)).length
  const hiredCount = items.value.filter(r => r.status === 'hired').length
  return [
    { label: 'Total Candidates', value: totalRecords.value, icon: 'pi pi-users', color: 'text-blue-500', bg: 'bg-blue-50' },
    { label: 'New / Submitted', value: newCount, icon: 'pi pi-file', color: 'text-orange-500', bg: 'bg-orange-50' },
    { label: 'Interviews', value: interviewCount, icon: 'pi pi-calendar', color: 'text-purple-500', bg: 'bg-purple-50' },
    { label: 'Hired', value: hiredCount, icon: 'pi pi-check-circle', color: 'text-emerald-500', bg: 'bg-emerald-50' },
  ]
})

const bulkActionOptions = [
  { label: 'Shortlist selected', value: 'shortlist' },
  { label: 'Reject selected', value: 'reject' },
  { label: 'Move to Interview', value: 'interview' },
]

// Quick reject with reason
const rejectDialog = ref(false)
const rejectReason = ref('')
const rejectingBulk = ref(false)
const REJECT_TEMPLATES = [
  'Thank you for applying. After careful consideration, we have decided to move forward with other candidates.',
  'We appreciate your interest. Unfortunately, your profile does not match our current requirements.',
  'Thank you for your time. The position has been filled internally.',
]

function openBulkReject() {
  rejectReason.value = REJECT_TEMPLATES[0]
  rejectDialog.value = true
}

async function confirmBulkReject() {
  if (!selectedItems.value.length) return
  rejectingBulk.value = true
  try {
    const ids = selectedItems.value.map(i => i.id)
    await api.post('/applications/bulk-action', {
      application_ids: ids,
      action: 'reject',
      reason: rejectReason.value,
    })
    selectedItems.value = []
    rejectDialog.value = false
    toast.success('Rejected', `${ids.length} application(s) rejected`)
    await fetchData(currentPage.value)
  } catch (e) {
    toast.error('Failed', e?.response?.data?.message || e?.message)
  } finally { rejectingBulk.value = false }
}

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

    // Fetch applications and analytics stats in parallel
    const [res, analyticsRes] = await Promise.allSettled([
      api.get('/applications', { params }),
      api.get('/analytics/dashboard', { params: { days: 365 } }),
    ])

    // Handle analytics stats
    if (analyticsRes.status === 'fulfilled') {
      const body = analyticsRes.value?.data?.data ?? analyticsRes.value?.data ?? analyticsRes.value
      analyticsStats.value = body
    }

    if (res.status === 'rejected') {
      throw res.reason
    }

    const body = res.value.data?.data

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
  return name || p?.public_display_name || u?.name || u?.full_name || `Candidate #${row?.applicant_user_id || row?.id || ''}`.trim()
}

function formatDate(v) {
  if (!v) return 'N/A'
  const d = new Date(v)
  return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => fetchData(1))
</script>
