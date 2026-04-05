<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { toast } from '@/composables/useAppToast'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'

const router = useRouter()

const COLUMNS = [
  { key: 'submitted',   label: 'Applied',      color: 'bg-blue-500',   light: 'bg-blue-50 border-blue-200',   text: 'text-blue-700' },
  { key: 'shortlisted', label: 'Shortlisted',  color: 'bg-indigo-500', light: 'bg-indigo-50 border-indigo-200', text: 'text-indigo-700' },
  { key: 'interview',   label: 'Interview',    color: 'bg-amber-500',  light: 'bg-amber-50 border-amber-200',  text: 'text-amber-700' },
  { key: 'hired',       label: 'Hired',        color: 'bg-green-500',  light: 'bg-green-50 border-green-200',  text: 'text-green-700' },
  { key: 'rejected',    label: 'Rejected',     color: 'bg-red-400',    light: 'bg-red-50 border-red-200',      text: 'text-red-700' },
]

const VALID_TRANSITIONS = {
  submitted:   ['shortlisted', 'rejected', 'interview', 'hired'],
  shortlisted: ['interview', 'rejected', 'hired'],
  interview:   ['hired', 'rejected'],
  hired:       [],
  rejected:    [],
  withdrawn:   [],
}

const loading = ref(false)
const error = ref('')
const applications = ref([])
const jobs = ref([])
const selectedJob = ref('')
const movingId = ref(null)
const currentPage = ref(1)
const hasMore = ref(false)
const loadingMore = ref(false)

// Bulk selection
const selectedIds = ref(new Set())
const bulkMode = ref(false)
const bulkMoving = ref(false)

function toggleSelect(id) {
  if (selectedIds.value.has(id)) selectedIds.value.delete(id)
  else selectedIds.value.add(id)
  selectedIds.value = new Set(selectedIds.value)
}

async function bulkMove(toStatus) {
  if (!selectedIds.value.size) return
  bulkMoving.value = true
  const ids = [...selectedIds.value]
  // Optimistic update
  const prev = {}
  for (const app of applications.value) {
    if (ids.includes(app.id)) {
      prev[app.id] = app.status
      if (canMoveTo(app.status, toStatus)) app.status = toStatus
    }
  }
  try {
    await api.post('/applications/bulk-action', { application_ids: ids, action: toStatus })
    toast.success(`${ids.length} candidates moved to ${toStatus}`)
    selectedIds.value = new Set()
    bulkMode.value = false
  } catch (e) {
    // Revert
    for (const app of applications.value) {
      if (prev[app.id]) app.status = prev[app.id]
    }
    toast.error(e?.response?.data?.message || 'Bulk move failed')
  } finally {
    bulkMoving.value = false
  }
}

// drag state
const dragging = ref(null)
const dragOverCol = ref(null)

const jobOptions = computed(() => [
  { label: 'All jobs', value: '' },
  ...jobs.value.map(j => ({ label: j.title || `Job #${j.id}`, value: String(j.id) }))
])

const columns = computed(() => {
  return COLUMNS.map(col => ({
    ...col,
    cards: applications.value.filter(a => {
      if (a.status !== col.key) return false
      if (selectedJob.value) return String(a.job?.id || a.job_id) === selectedJob.value
      return true
    })
  }))
})

async function load() {
  loading.value = true
  error.value = ''
  currentPage.value = 1
  try {
    const res = await api.get('/applications', { params: { scope: 'owned', per_page: 50, page: 1 } })
    const data = res.data?.data ?? res.data
    const list = data?.applications?.data ?? data?.applications ?? (Array.isArray(data) ? data : [])
    applications.value = list
    hasMore.value = (data?.applications?.last_page ?? data?.last_page ?? 1) > 1

    const jobMap = new Map()
    for (const a of list) {
      const j = a.job
      if (j?.id) jobMap.set(j.id, j)
    }
    jobs.value = Array.from(jobMap.values())
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to load applications.'
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  loadingMore.value = true
  try {
    const nextPage = currentPage.value + 1
    const res = await api.get('/applications', { params: { scope: 'owned', per_page: 50, page: nextPage } })
    const data = res.data?.data ?? res.data
    const list = data?.applications?.data ?? data?.applications ?? (Array.isArray(data) ? data : [])
    applications.value = [...applications.value, ...list]
    currentPage.value = nextPage
    hasMore.value = nextPage < (data?.applications?.last_page ?? data?.last_page ?? 1)

    for (const a of list) {
      const j = a.job
      if (j?.id && !jobs.value.find(x => x.id === j.id)) jobs.value.push(j)
    }
  } catch {} finally {
    loadingMore.value = false
  }
}

function candidateName(app) {
  const p = app.applicant?.applicant_profile || app.applicant?.applicantProfile
  const first = (p?.first_name || '').trim()
  const last  = (p?.last_name  || '').trim()
  if (first) return last ? `${first} ${last[0].toUpperCase()}.` : first
  return app.applicant_name || app.applicant?.name || `App #${app.id}`
}

function initials(name) {
  return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase() || '?'
}

function canMoveTo(fromStatus, toStatus) {
  return (VALID_TRANSITIONS[fromStatus] || []).includes(toStatus)
}

// ── Drag & Drop ──────────────────────────────────────────────
function onDragStart(app) {
  dragging.value = app
}
function onDragEnd() {
  dragging.value = null
  dragOverCol.value = null
}
function onDragOver(colKey) {
  if (!dragging.value) return
  if (canMoveTo(dragging.value.status, colKey)) {
    dragOverCol.value = colKey
  }
}
function onDragLeave() {
  dragOverCol.value = null
}
async function onDrop(colKey) {
  dragOverCol.value = null
  if (!dragging.value) return
  const app = dragging.value
  dragging.value = null
  if (app.status === colKey) return
  if (!canMoveTo(app.status, colKey)) {
    toast.warn(`Cannot move from ${app.status} to ${colKey}`)
    return
  }
  await moveCard(app, colKey)
}

async function moveCard(app, toStatus) {
  movingId.value = app.id
  const prev = app.status
  // optimistic update
  app.status = toStatus
  try {
    await api.post(`/applications/${app.id}/status`, { status: toStatus })
    toast.success(`Moved to ${toStatus}`)
  } catch (e) {
    app.status = prev
    toast.error(e?.response?.data?.message || 'Move failed')
  } finally {
    movingId.value = null
  }
}

function viewApp(app) {
  router.push({ name: 'applicants.view', params: { id: app.id } })
}

onMounted(load)
</script>

<template>
  <AppLayout>
    <div class="max-w-full px-4 md:px-6 py-6 space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Pipeline Board</h1>
          <p class="text-gray-500 mt-1">Drag cards between stages to update candidate status.</p>
        </div>
        <div class="flex items-center gap-3">
          <Select
            v-model="selectedJob"
            :options="jobOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="All jobs"
            class="w-56"
          />
          <Button :label="bulkMode ? 'Cancel' : 'Select'" :icon="bulkMode ? 'pi pi-times' : 'pi pi-check-square'"
            :severity="bulkMode ? 'secondary' : 'secondary'" outlined size="small" @click="bulkMode = !bulkMode; selectedIds = new Set()" />
          <Button icon="pi pi-refresh" text severity="secondary" :loading="loading" @click="load" />
        </div>
      </div>

      <!-- Bulk action bar -->
      <div v-if="bulkMode && selectedIds.size > 0"
        class="flex items-center gap-3 px-4 py-3 bg-indigo-50 border border-indigo-200 rounded-xl">
        <span class="text-sm font-semibold text-indigo-700">{{ selectedIds.size }} selected</span>
        <div class="flex gap-2 flex-wrap">
          <Button v-for="col in COLUMNS.filter(c => !['submitted'].includes(c.key))" :key="col.key"
            :label="`→ ${col.label}`" size="small" severity="secondary" outlined
            :class="col.text" :loading="bulkMoving"
            @click="bulkMove(col.key)" />
        </div>
        <Button icon="pi pi-times" text size="small" severity="secondary" class="ml-auto"
          @click="selectedIds = new Set()" />
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

      <div v-if="loading && !applications.length" class="flex justify-center py-20">
        <ProgressSpinner />
      </div>

      <!-- Kanban grid -->
      <div v-else class="flex gap-4 overflow-x-auto pb-4 min-h-[70vh]">
        <div
          v-for="col in columns"
          :key="col.key"
          class="flex-shrink-0 w-72 flex flex-col rounded-2xl border transition-all duration-150"
          :class="[
            col.light,
            dragOverCol === col.key ? 'ring-2 ring-offset-1 ring-blue-400 scale-[1.01]' : ''
          ]"
          @dragover.prevent="onDragOver(col.key)"
          @dragleave="onDragLeave"
          @drop.prevent="onDrop(col.key)"
        >
          <!-- Column header -->
          <div class="flex items-center justify-between px-4 py-3 border-b" :class="col.light">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :class="col.color"></span>
              <span class="font-semibold text-sm text-gray-800">{{ col.label }}</span>
            </div>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-white/70 border" :class="col.text">
              {{ col.cards.length }}
            </span>
          </div>

          <!-- Cards -->
          <div class="flex-1 p-3 space-y-3 overflow-y-auto">
            <div
              v-for="app in col.cards"
              :key="app.id"
              :draggable="!bulkMode"
              @dragstart="!bulkMode && onDragStart(app)"
              @dragend="onDragEnd"
              class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm transition-all group"
              :class="[
                movingId === app.id ? 'opacity-50' : '',
                bulkMode ? 'cursor-pointer' : 'cursor-grab active:cursor-grabbing hover:shadow-md',
                bulkMode && selectedIds.has(app.id) ? 'ring-2 ring-indigo-400 border-indigo-300' : ''
              ]"
              @click="bulkMode && toggleSelect(app.id)"
            >
              <div class="flex items-start gap-2.5">
                <div v-if="bulkMode" class="flex-shrink-0 mt-0.5">
                  <input type="checkbox" :checked="selectedIds.has(app.id)"
                    class="w-4 h-4 rounded accent-indigo-600 cursor-pointer"
                    @click.stop="toggleSelect(app.id)" />
                </div>
                <Avatar
                  :label="initials(candidateName(app))"
                  shape="circle"
                  size="normal"
                  class="!w-9 !h-9 !text-xs flex-shrink-0"
                  :style="{ backgroundColor: '#eff6ff', color: '#3b82f6' }"
                />
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-sm text-gray-900 truncate">{{ candidateName(app) }}</div>
                  <div class="text-xs text-gray-500 truncate mt-0.5">{{ app.job?.title || '—' }}</div>
                  <div class="text-[11px] text-gray-400 mt-1">
                    {{ app.submitted_at ? new Date(app.submitted_at).toLocaleDateString() : '' }}
                  </div>
                </div>
              </div>

              <!-- Quick move buttons -->
              <div class="mt-3 flex flex-wrap gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  v-for="dest in COLUMNS.filter(c => canMoveTo(app.status, c.key))"
                  :key="dest.key"
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full border transition-colors"
                  :class="`${dest.light} ${dest.text} hover:opacity-80`"
                  @click.stop="moveCard(app, dest.key)"
                >
                  → {{ dest.label }}
                </button>
                <button
                  class="text-[10px] font-semibold px-2 py-0.5 rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
                  @click.stop="viewApp(app)"
                >
                  View
                </button>
              </div>
            </div>

            <!-- Empty column -->
            <div v-if="col.cards.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
              <i class="pi pi-inbox text-2xl text-gray-300 mb-2"></i>
              <p class="text-xs text-gray-400">No candidates here</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Load more -->
      <div v-if="hasMore" class="flex justify-center pt-2">
        <Button label="Load more" icon="pi pi-chevron-down" severity="secondary" outlined
          :loading="loadingMore" @click="loadMore" />
      </div>
    </div>
  </AppLayout>
</template>
