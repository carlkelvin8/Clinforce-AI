<template>
  <AppLayout>
    <div class="min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 mb-6 relative overflow-hidden">
          <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
          <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 flex-wrap gap-y-3">
            <div>
              <div class="flex items-center gap-2">
                <Button label="Back" icon="pi pi-arrow-left" text @click="$router.back()" class="!text-slate-600 hover:!text-slate-900 !px-2 !py-1 !rounded-md" />
                <Button label="Refresh" icon="pi pi-refresh" text @click="fetchOne" class="!text-slate-600 hover:!text-slate-900 !px-2 !py-1 !rounded-md" />
              </div>
              <div class="mt-3 flex items-center gap-4">
                <Avatar
                  v-if="app"
                  :image="candidateAvatarUrl(app)"
                  :label="!candidateAvatarUrl(app) ? (String(app?.applicant_user_id || 'C').slice(-1)) : null"
                  shape="circle"
                  class="bg-white text-slate-700 font-bold w-12 h-12 text-sm ring-1 ring-slate-200"
                />
                <div class="flex items-center gap-3">
                  <h1 class="text-2xl md:text-3xl font-bold text-slate-900 m-0">Application #{{ app?.id ?? props.id }}</h1>
                  <Tag v-if="app" :value="titleCase(app.status)" :severity="getSeverity(app.status)" class="!whitespace-nowrap shrink-0" />
                </div>
              </div>
              <p class="text-slate-600 mt-1" v-if="app">
                Applicant #{{ app.applicant_user_id }} • Job: <span class="font-semibold">{{ app.job?.title || 'N/A' }}</span>
              </p>
            </div>
            <div class="flex flex-wrap items-start md:items-center gap-2">
              <Button label="Shortlist" icon="pi pi-check" @click="shortlist" class="!rounded-md" severity="success" :disabled="!app || busy || app.status!=='submitted'" />
              <Button label="Reject" icon="pi pi-times" @click="rejectApp" class="!rounded-md" severity="danger" :disabled="!app || busy || app.status==='rejected' || app.status==='hired'" outlined />
              <Button label="Schedule Interview" icon="pi pi-calendar" @click="scheduleInterview" class="!rounded-md" :disabled="!app || busy" />
              <Button v-if="canHire" label="Mark as hired" icon="pi pi-check-circle" severity="success" @click="hire" class="!rounded-md" />
              <Button label="Message" icon="pi pi-envelope" @click="goMessages" class="!rounded-md" outlined />
            </div>
          </div>
        </div>

        <div v-if="loading" class="py-16 text-center text-slate-500 bg-white rounded-2xl border border-slate-200">
          <i class="pi pi-spin pi-spinner text-3xl mb-3"></i>
          <div>Loading application…</div>
        </div>
        <Message v-else-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

        <div v-else-if="app" class="grid grid-cols-1 md:grid-cols-12 gap-6">
          <div class="md:col-span-3 lg:col-span-3">
            <Card class="lg:sticky lg:top-6 !border-0 !shadow-none">
              <template #title>Summary</template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-3 gap-y-2">
                  <div class="text-xs text-slate-500 font-semibold">Application ID</div>
                  <div class="text-sm font-bold text-slate-900 text-right break-words">#{{ app.id }}</div>
                  <div class="text-xs text-slate-500 font-semibold">Applicant User ID</div>
                  <div class="text-sm font-bold text-slate-900 text-right break-words">#{{ app.applicant_user_id }}</div>
                  <div class="text-xs text-slate-500 font-semibold">Submitted</div>
                  <div class="text-sm font-bold text-slate-900 text-right break-words">{{ formatDate(app.submitted_at) }}</div>
                  <div class="md:col-span-2 border-t border-slate-200 my-1"></div>
                  <div class="text-xs text-slate-500 font-semibold">Job</div>
                  <div class="text-sm font-bold text-slate-900 text-right truncate max-w-full md:max-w-[220px]" :title="app.job?.title">{{ app.job?.title || 'N/A' }}</div>
                  <div class="text-xs text-slate-500 font-semibold" v-if="app.job?.city || app.job?.country_code">Location</div>
                  <div class="text-sm font-bold text-slate-900 text-right truncate max-w-full md:max-w-[220px]" v-if="app.job?.city || app.job?.country_code" :title="[app.job?.city, app.job?.country_code].filter(Boolean).join(', ')">
                    {{ [app.job?.city, app.job?.country_code].filter(Boolean).join(', ') }}
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <div class="md:col-span-9 lg:col-span-9 space-y-6">
            <Card class="!border-0 !shadow-none">
              <template #title>
                <div class="flex items-start justify-between gap-4 flex-wrap">
                  <div>
                    <div class="text-base font-bold text-slate-900">Cover letter</div>
                    <div class="text-sm text-slate-500">Submitted <span class="font-semibold text-slate-700">{{ formatDate(app.submitted_at) }}</span></div>
                  </div>
                  <Tag :value="titleCase(app.status)" :severity="getSeverity(app.status)" class="shrink-0 !whitespace-nowrap" />
                </div>
              </template>
              <template #content>
                <p v-if="app.cover_letter" class="text-slate-700 leading-7 whitespace-pre-wrap break-words">{{ app.cover_letter }}</p>
                <p v-else class="text-slate-500">No cover letter provided.</p>
              </template>
            </Card>

            <Card id="history" class="!border-0 !shadow-none">
              <template #title>
                <div>
                  <div class="text-base font-bold text-slate-900">Status history</div>
                  <div class="text-sm text-slate-500">All changes recorded for this application</div>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div v-for="h in (app.status_history || [])" :key="h.id" class="flex items-start justify-between gap-4 p-3 bg-white border border-slate-200 rounded-xl">
                    <div class="min-w-0">
                      <div class="text-sm font-semibold text-slate-900 flex flex-wrap gap-2">
                        <Tag :value="titleCase(h.from_status || '—')" severity="secondary" class="!text-xs" />
                        <span class="text-slate-500">→</span>
                        <Tag :value="titleCase(h.to_status)" :severity="getSeverity(h.to_status)" class="!text-xs" />
                        <span v-if="h.note" class="text-slate-500">· {{ h.note }}</span>
                      </div>
                      <div class="text-xs text-slate-500 mt-1">{{ formatDate(h.created_at) }}</div>
                    </div>
                  </div>
                  <div v-if="(app.status_history || []).length === 0" class="text-slate-500">No history.</div>
                </div>
              </template>
            </Card>

            <Card id="interview" class="!border-0 !shadow-none">
              <template #title>
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <div class="text-base font-bold text-slate-900">Interview</div>
                    <div class="text-sm text-slate-500">Schedule and meeting details</div>
                  </div>
                  <Tag :value="app.interview ? 'Scheduled' : 'None'" :severity="app.interview ? 'success' : 'secondary'" />
                </div>
              </template>
              <template #content>
                <div v-if="app.interview" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">Status</span>
                    <span class="text-sm font-bold text-slate-900">{{ app.interview.status }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">Mode</span>
                    <span class="text-sm font-bold text-slate-900">{{ app.interview.mode }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">Start</span>
                    <span class="text-sm font-bold text-slate-900">{{ formatDate(app.interview.scheduled_start) }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">End</span>
                    <span class="text-sm font-bold text-slate-900">{{ formatDate(app.interview.scheduled_end) }}</span>
                  </div>
                  <div class="flex items-center justify-between" v-if="app.interview.location_text">
                    <span class="text-xs text-slate-500 font-semibold">Location</span>
                    <span class="text-sm font-bold text-slate-900 truncate max-w-[240px] text-right">{{ app.interview.location_text }}</span>
                  </div>
                  <div class="flex items-center justify-between" v-if="app.interview.meeting_link">
                    <span class="text-xs text-slate-500 font-semibold">Meeting link</span>
                    <a :href="app.interview.meeting_link" target="_blank" rel="noreferrer" class="text-sm font-medium text-blue-600 hover:text-blue-700 truncate max-w-[240px] text-right">
                      {{ app.interview.meeting_link }}
                    </a>
                  </div>
                </div>
                <div v-else class="text-slate-500">No interview scheduled.</div>
              </template>
            </Card>
          </div>
        </div>

        <div v-else class="py-16 text-center text-slate-500 bg-white rounded-2xl border border-dashed border-slate-200">
          No application found.
        </div>
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
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Avatar from 'primevue/avatar'
import Swal from 'sweetalert2'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const router = useRouter()
const loading = ref(false)
const error = ref('')
const app = ref(null)
const busy = ref(false)
const role = ref('')

function formatDate(v) {
  if (!v) return 'N/A'
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleString()
}
function getSeverity(s) {
  const v = String(s || '').toLowerCase()
  if (v === 'shortlisted' || v === 'hired') return 'success'
  if (v === 'interview') return 'warn'
  if (v === 'rejected') return 'danger'
  return 'secondary'
}
function titleCase(s) { if (!s) return ''; return s.charAt(0).toUpperCase() + s.slice(1) }
function scrollToSel(sel) {
  const el = document.querySelector(sel)
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const canHire = computed(() => {
  const r = String(role.value || '').toLowerCase()
  if (!app.value) return false
  if (!['employer', 'agency', 'admin'].includes(r)) return false
  return String(app.value.status || '') === 'interview' && !busy.value
})

function candidateAvatarUrl(a) {
  if (!a) return null
  const u = a.applicant || a.user || null
  return u?.avatar_url || (a.applicantProfile?.avatar ? `/storage/${a.applicantProfile.avatar}` : null) || null
}

async function fetchOne() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get(`/api/applications/${props.id}`)
    app.value = res.data?.data ?? res.data
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || 'Request failed'
    app.value = null
  } finally {
    loading.value = false
  }
}

async function doStatus(to, titleText) {
  if (!app.value?.id) return
  const { value: formValues } = await Swal.fire({
    title: titleText,
    input: 'textarea',
    inputLabel: 'Optional note',
    inputPlaceholder: 'Add a short note (optional)',
    inputAttributes: { 'aria-label': 'Note' },
    showCancelButton: true,
    confirmButtonText: 'Confirm',
    cancelButtonText: 'Cancel',
    focusConfirm: false,
    customClass: { confirmButton: 'swal2-confirm', cancelButton: 'swal2-cancel' },
  })
  if (formValues === undefined) return
  busy.value = true
  try {
    const body = { status: to }
    if (formValues && String(formValues).trim()) body.note = String(formValues).trim()
    const res = await api.post(`/api/applications/${app.value.id}/status`, body)
    app.value = res.data?.data ?? res.data ?? app.value
    await Swal.fire({ icon: 'success', title: 'Updated', text: 'Application status updated.' })
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.__payload?.message || e?.message || 'Update failed' })
  } finally {
    busy.value = false
  }
}

async function hire() {
  if (!app.value?.id) return
  await doStatus('hired', 'Mark this candidate as hired?')
}

function shortlist() {
  doStatus('shortlisted', 'Shortlist this candidate?')
}
function rejectApp() {
  doStatus('rejected', 'Reject this application?')
}
function scheduleInterview() {
  router.push({ name: 'employer.interviews' })
}
function goMessages() {
  router.push({ name: 'employer.messages' })
}

onMounted(async () => {
  await fetchOne()
  try {
    const u = await me()
    role.value = u?.role || ''
  } catch (e) {}
})
</script>

<style scoped>
</style>
