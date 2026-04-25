<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { toast } from '@/composables/useAppToast'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'

const router = useRouter()
const route = useRoute()

const MAX_SLOTS = 3

const loading = ref(false)
const appsLoading = ref(false)
const applications = ref([])
const slots = ref([null, null, null]) // up to 3 selected application IDs
const profiles = ref({}) // keyed by application id

const appOptions = computed(() =>
  applications.value.map(a => ({
    label: `${candidateName(a)} — ${a.job?.title || 'Job'}`,
    value: a.id,
  }))
)

function avatarUrl(appId) {
  const p = getProfile(appId)
  return p?.applicant?.avatar_url || null
}

function candidateName(a) {
  const p = a.applicant?.applicant_profile || a.applicant?.applicantProfile
  const first = (p?.first_name || '').trim()
  const last  = (p?.last_name  || '').trim()
  if (first) return last ? `${first} ${last[0].toUpperCase()}.` : first
  return a.applicant_name || a.applicant?.name || `App #${a.id}`
}

async function loadApplications() {
  appsLoading.value = true
  try {
    const res = await api.get('/applications', { params: { scope: 'owned', per_page: 200 } })
    const data = res.data?.data ?? res.data
    const list = data?.applications?.data ?? data?.applications ?? (Array.isArray(data) ? data : [])
    applications.value = list.filter(a => !['rejected', 'withdrawn'].includes(a.status))
  } catch (e) {
    toast.error('Failed to load applications')
  } finally {
    appsLoading.value = false
  }
}

async function loadProfile(appId) {
  if (!appId || profiles.value[appId]) return
  loading.value = true
  try {
    const res = await api.get(`/applications/${appId}`)
    profiles.value[appId] = res.data?.data ?? res.data
  } catch (e) {
    toast.error('Failed to load candidate details')
  } finally {
    loading.value = false
  }
}

watch(slots, async (newSlots) => {
  for (const id of newSlots) {
    if (id) await loadProfile(id)
  }
}, { deep: true })

// Pre-fill from query params: ?ids=1,2,3
onMounted(async () => {
  await loadApplications()
  if (route.query.ids) {
    const ids = String(route.query.ids).split(',').map(Number).filter(Boolean).slice(0, MAX_SLOTS)
    ids.forEach((id, i) => { slots.value[i] = id })
  }
})

const activeSlots = computed(() => slots.value.filter(Boolean))

function getProfile(appId) {
  return profiles.value[appId] || null
}

function getApp(appId) {
  return applications.value.find(a => a.id === appId) || getProfile(appId)
}

function clearSlot(i) {
  slots.value[i] = null
}

function statusSeverity(s) {
  const m = { submitted: 'secondary', shortlisted: 'info', interview: 'warn', hired: 'success', rejected: 'danger', withdrawn: 'secondary' }
  return m[s] || 'secondary'
}

function formatEnum(v) {
  return String(v || '—').split('_').map(w => w ? w[0].toUpperCase() + w.slice(1) : '').join(' ')
}

function yrsExp(appId) {
  const p = getProfile(appId)
  const ap = p?.applicant?.applicant_profile || p?.applicant?.applicantProfile
  return ap?.years_experience != null ? `${ap.years_experience} yrs` : '—'
}

function headline(appId) {
  const p = getProfile(appId)
  const ap = p?.applicant?.applicant_profile || p?.applicant?.applicantProfile
  return ap?.headline || '—'
}

function location(appId) {
  const p = getProfile(appId)
  const ap = p?.applicant?.applicant_profile || p?.applicant?.applicantProfile
  if (!ap) return '—'
  return [ap.city, ap.country].filter(Boolean).join(', ') || '—'
}

function coverLetter(appId) {
  const p = getProfile(appId)
  return p?.cover_letter || '—'
}

function submittedAt(appId) {
  const p = getProfile(appId)
  const d = new Date(p?.submitted_at || 0)
  return isNaN(d) ? '—' : d.toLocaleDateString()
}

function hasResume(appId) {
  return getProfile(appId)?.has_resume
}

function viewApplicant(appId) {
  if (appId) router.push({ name: 'applicants.view', params: { id: appId } })
}

const ROWS = [
  { label: 'Status', key: 'status' },
  { label: 'Headline', key: 'headline' },
  { label: 'Experience', key: 'experience' },
  { label: 'Location', key: 'location' },
  { label: 'Applied', key: 'applied' },
  { label: 'Cover letter', key: 'cover' },
  { label: 'Resume', key: 'resume' },
  { label: 'Notes', key: 'notes' },
]

// Per-slot notes (stored locally, not persisted — just for comparison session)
const slotNotes = ref({ 0: '', 1: '', 2: '' })

function rowValue(row, appId) {
  if (row.key === 'notes') return null // handled separately in template
  switch (row.key) {
    case 'status': return getApp(appId)?.status || '—'
    case 'headline': return headline(appId)
    case 'experience': return yrsExp(appId)
    case 'location': return location(appId)
    case 'applied': return submittedAt(appId)
    case 'cover': return coverLetter(appId)
    case 'resume': return hasResume(appId) ? 'Yes' : 'No'
    default: return '—'
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Compare Candidates</h1>
          <p class="text-gray-500 mt-1">Select up to 3 candidates to compare side-by-side.</p>
        </div>
        <Button icon="pi pi-refresh" text severity="secondary" :loading="appsLoading" @click="loadApplications" />
      </div>

      <!-- Slot selectors -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div v-for="(slot, i) in slots" :key="i" class="space-y-2">
          <label class="text-xs font-bold uppercase tracking-wide text-gray-500">Candidate {{ i + 1 }}</label>
          <div class="flex gap-2">
            <Select
              v-model="slots[i]"
              :options="appOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select candidate..."
              filter
              class="flex-1"
              :loading="appsLoading"
            />
            <Button
              v-if="slots[i]"
              icon="pi pi-times"
              text
              rounded
              severity="secondary"
              size="small"
              @click="clearSlot(i)"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <!-- Comparison table -->
      <div v-else-if="activeSlots.length" class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
        <!-- Candidate headers -->
        <div class="grid border-b border-gray-100" :style="`grid-template-columns: 180px repeat(${activeSlots.length}, 1fr)`">
          <div class="p-4 bg-gray-50 border-r border-gray-100"></div>
          <div
            v-for="appId in activeSlots"
            :key="appId"
            class="p-4 border-r border-gray-100 last:border-r-0"
          >
            <div class="flex flex-col items-center text-center gap-2">
              <Avatar
                :image="avatarUrl(appId)"
                :label="!avatarUrl(appId) ? candidateName(getApp(appId)).charAt(0).toUpperCase() : undefined"
                shape="circle"
                size="large"
                :style="{ backgroundColor: '#eff6ff', color: '#3b82f6' }"
              />
              <div>
                <div class="font-bold text-gray-900 text-sm">{{ candidateName(getApp(appId)) }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ getApp(appId)?.job?.title || '—' }}</div>
              </div>
              <Button
                label="View Profile"
                icon="pi pi-external-link"
                size="small"
                text
                class="!text-xs"
                @click="viewApplicant(appId)"
              />
            </div>
          </div>
        </div>

        <!-- Comparison rows -->
        <div
          v-for="(row, ri) in ROWS"
          :key="row.key"
          class="grid border-b border-gray-100 last:border-b-0"
          :style="`grid-template-columns: 180px repeat(${activeSlots.length}, 1fr)`"
          :class="ri % 2 === 0 ? 'bg-white' : 'bg-gray-50/40'"
        >
          <!-- Row label -->
          <div class="p-4 border-r border-gray-100 flex items-center">
            <span class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ row.label }}</span>
          </div>

          <!-- Values -->
          <div
            v-for="(appId, slotIdx) in activeSlots"
            :key="appId"
            class="p-4 border-r border-gray-100 last:border-r-0 flex items-start"
          >
            <!-- Notes row — editable textarea -->
            <Textarea
              v-if="row.key === 'notes'"
              v-model="slotNotes[slotIdx]"
              rows="3"
              placeholder="Add notes for this candidate..."
              class="w-full !text-xs !rounded-xl !border-slate-200"
            />
            <!-- Status gets a Tag -->
            <Tag
              v-else-if="row.key === 'status'"
              :value="rowValue(row, appId)"
              :severity="statusSeverity(rowValue(row, appId))"
              class="capitalize"
            />
            <!-- Resume gets a badge -->
            <Tag
              v-else-if="row.key === 'resume'"
              :value="rowValue(row, appId)"
              :severity="rowValue(row, appId) === 'Yes' ? 'success' : 'secondary'"
            />
            <!-- Cover letter truncated -->
            <p
              v-else-if="row.key === 'cover'"
              class="text-sm text-gray-700 line-clamp-4 leading-relaxed"
            >
              {{ rowValue(row, appId) }}
            </p>
            <!-- Default -->
            <span v-else class="text-sm text-gray-800">{{ rowValue(row, appId) }}</span>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 py-16 text-center">
        <i class="pi pi-users text-4xl text-gray-300 mb-3 block"></i>
        <p class="text-gray-600 font-medium">Select candidates above to compare</p>
        <p class="text-sm text-gray-400 mt-1">Choose up to 3 candidates from your pipeline.</p>
      </div>
    </div>
  </AppLayout>
</template>
