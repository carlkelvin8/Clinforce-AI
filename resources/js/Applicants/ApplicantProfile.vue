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
                  <h1 class="text-2xl md:text-3xl font-bold text-slate-900 m-0">{{ applicantDisplayName(app) }}</h1>
                  <Tag v-if="app" :value="titleCase(app.status)" :severity="getSeverity(app.status)" class="!whitespace-nowrap shrink-0" />
                </div>
              </div>
              <p class="text-slate-600 mt-1" v-if="app">
                Job: <span class="font-semibold">{{ app.job?.title || 'N/A' }}</span>
              </p>
            </div>
            <div class="flex flex-wrap items-start md:items-center gap-2">
              <Button label="Shortlist" icon="pi pi-check" @click="shortlist" class="!rounded-md" severity="success" :disabled="!app || busy || app.status!=='submitted'" />
              <Button label="Reject" icon="pi pi-times" @click="rejectApp" class="!rounded-md" severity="danger" :disabled="!app || busy || app.status==='rejected' || app.status==='hired'" outlined />
              <Button label="Hire" icon="pi pi-check-circle" @click="hire" class="!rounded-md" severity="success" :disabled="!canHire" />
              <Button label="Schedule Interview" icon="pi pi-calendar" @click="scheduleInterview" class="!rounded-md" :disabled="!app || busy" />
              <Button label="Message" icon="pi pi-envelope" @click="goMessages" class="!rounded-md" outlined :loading="messaging" :disabled="messaging" />
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
                  <div class="text-xs text-slate-500 font-semibold">Applicant</div>
                  <div class="text-sm font-bold text-slate-900 text-right break-words">{{ applicantDisplayName(app) }}</div>
                  <div class="text-xs text-slate-500 font-semibold">Submitted</div>
                  <div class="text-sm font-bold text-slate-900 text-right break-words">{{ formatDate(app.submitted_at) }}</div>
                  <div class="md:col-span-2 border-t border-slate-200 my-1"></div>
                  <div class="text-xs text-slate-500 font-semibold">Job</div>
                  <div class="text-sm font-bold text-slate-900 text-right truncate max-w-full md:max-w-[220px]" :title="app.job?.title">{{ app.job?.title || 'N/A' }}</div>
                  <div class="text-xs text-slate-500 font-semibold" v-if="app.job?.city || app.job?.country_code">Location</div>
                  <div
                    class="text-sm font-bold text-slate-900 text-right truncate max-w-full md:max-w-[220px]"
                    v-if="app.job?.city || app.job?.country_code"
                    :title="[app.job?.city, countryName(app.job?.country_code)].filter(Boolean).join(', ')"
                  >
                    {{ [app.job?.city, countryName(app.job?.country_code)].filter(Boolean).join(', ') }}
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

            <!-- Document Access Card -->
            <DocumentAccessCard 
              v-if="!hasDocumentAccess"
              :applicant-id="app.applicant_user_id"
              :application-id="app.id"
              @purchased="onDocumentAccessPurchased"
            />

            <!-- Resume Section -->
            <Card v-else class="!border-0 !shadow-none">
              <template #title>
                <div class="flex items-start justify-between gap-4 flex-wrap">
                  <div>
                    <div class="text-base font-bold text-slate-900">Resume & Documents</div>
                    <div class="text-sm text-slate-500">Full access granted</div>
                  </div>
                  <Tag value="Unlocked" severity="success" icon="pi pi-unlock" />
                </div>
              </template>
              <template #content>
                <div v-if="hasResume">
                  <div class="flex gap-2">
                    <Button 
                      label="Preview Resume" 
                      icon="pi pi-eye" 
                      @click="previewResume"
                      class="!rounded-md"
                      outlined
                    />
                    <Button 
                      label="Download Resume" 
                      icon="pi pi-download" 
                      @click="downloadResume"
                      class="!rounded-md"
                      :loading="downloadingResume"
                      :disabled="downloadingResume"
                    />
                  </div>
                </div>
                <div v-else class="text-center py-8 bg-slate-50 rounded-lg border border-slate-200">
                  <i class="pi pi-file-pdf text-4xl text-slate-300 mb-3"></i>
                  <p class="text-slate-600 font-medium">No resume uploaded</p>
                  <p class="text-sm text-slate-500 mt-1">This applicant hasn't uploaded a resume yet</p>
                </div>
              </template>
            </Card>

            <!-- Resume Preview Dialog -->
            <Dialog v-model:visible="showResumePreview" modal header="Resume Preview" :style="{ width: '90vw', maxWidth: '1200px' }" :dismissableMask="true">
              <iframe 
                v-if="resumePreviewUrl" 
                :src="resumePreviewUrl" 
                class="w-full h-[70vh] border-0 rounded-lg"
                title="Resume Preview"
              ></iframe>
              <div v-else class="text-center py-8">
                <i class="pi pi-spin pi-spinner text-3xl text-slate-400"></i>
                <p class="text-slate-600 mt-3">Loading preview...</p>
              </div>
            </Dialog>

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

            <!-- Notes -->
            <Card class="!border-0 !shadow-none">
              <template #title>
                <div class="text-base font-bold text-slate-900">Internal Notes</div>
                <div class="text-sm text-slate-500">Private notes visible only to your team</div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div class="flex gap-2">
                    <Textarea v-model="newNote" rows="2" placeholder="Add a note..." class="flex-1 !rounded-xl !text-sm" />
                    <Button icon="pi pi-send" :loading="savingNote" @click="addNote" class="self-end" />
                  </div>
                  <div v-if="notesLoading" class="text-center py-4 text-slate-400">
                    <i class="pi pi-spin pi-spinner"></i>
                  </div>
                  <div v-else-if="!notes.length" class="text-slate-400 text-sm py-2">No notes yet.</div>
                  <div v-else class="space-y-2">
                    <div v-for="note in notes" :key="note.id"
                      class="flex items-start justify-between gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                      <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-800 whitespace-pre-wrap">{{ note.note }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ new Date(note.created_at).toLocaleString() }}</p>
                      </div>
                      <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="deleteNote(note.id)" />
                    </div>
                  </div>
                </div>
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
import DocumentAccessCard from '@/Components/DocumentAccessCard.vue'
import api from '@/lib/api'
import { me } from '@/lib/auth'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Avatar from 'primevue/avatar'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
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
const messaging = ref(false)
const hasDocumentAccess = ref(false)
const downloadingResume = ref(false)
const hasResume = ref(false)
const showResumePreview = ref(false)
const resumePreviewUrl = ref(null)

// Notes
const notes = ref([])
const notesLoading = ref(false)
const newNote = ref('')
const savingNote = ref(false)

async function loadNotes() {
  if (!props.id) return
  notesLoading.value = true
  try {
    const res = await api.get(`/applications/${props.id}/notes`)
    notes.value = res.data?.data ?? res.data ?? []
  } catch {}
  finally { notesLoading.value = false }
}

async function addNote() {
  if (!newNote.value.trim()) return
  savingNote.value = true
  try {
    const res = await api.post(`/applications/${props.id}/notes`, { note: newNote.value.trim() })
    notes.value.unshift(res.data?.data ?? res.data)
    newNote.value = ''
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not save note' })
  } finally { savingNote.value = false }
}

async function deleteNote(noteId) {
  try {
    await api.delete(`/applications/${props.id}/notes/${noteId}`)
    notes.value = notes.value.filter(n => n.id !== noteId)
  } catch {}
}

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

function countryName(code) {
  const c = String(code || '').trim()
  if (!c) return ''
  try {
    if (typeof Intl !== 'undefined' && Intl.DisplayNames) {
      const dn = new Intl.DisplayNames(['en'], { type: 'region' })
      return dn.of(c.toUpperCase()) || c.toUpperCase()
    }
  } catch {}
  return c.toUpperCase()
}

function applicantDisplayName(app) {
  const u = app?.applicant || app?.user || null
  const p = u?.applicant_profile || u?.applicantProfile || null
  const fn = (p?.first_name || '').trim()
  const ln = (p?.last_name || '').trim()
  const name = fn ? `${fn}${ln ? ' ' + ln[0].toUpperCase() + '.' : ''}` : ''
  return (
    name ||
    p?.public_display_name ||
    u?.name ||
    u?.full_name ||
    `Applicant #${app?.applicant_user_id || app?.applicant_id || app?.user_id || ''}`.trim()
  )
}

const canHire = computed(() => {
  const r = String(role.value || '').toLowerCase()
  if (!app.value) return false
  if (!['employer', 'agency', 'admin'].includes(r)) return false
  const status = String(app.value.status || '').toLowerCase()
  // Can hire from submitted, shortlisted, or interview status (matches backend allowed transitions)
  return ['submitted', 'shortlisted', 'interview'].includes(status) && !busy.value
})

function candidateAvatarUrl(a) {
  if (!a) return null
  const u = a.applicant || a.user || null
  if (u?.avatar_url) return u.avatar_url
  const raw =
    u?.applicant_profile?.avatar ||
    u?.applicantProfile?.avatar ||
    null
  if (!raw) return null
  const p = String(raw).replace(/^\/+/, '')
  if (/^https?:\/\//i.test(p)) return raw
  if (p.startsWith('uploads/')) return '/' + p
  return '/storage/' + p
}

async function fetchOne() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get(`/applications/${props.id}`)
    app.value = res.data?.data ?? res.data
    
    // Set resume availability from backend
    hasResume.value = app.value?.has_resume || false
    
    // Check document access
    if (app.value?.applicant_user_id) {
      await checkDocumentAccess(app.value.applicant_user_id)
    }
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || 'Request failed'
    app.value = null
  } finally {
    loading.value = false
  }
}

async function checkDocumentAccess(applicantId) {
  try {
    console.log('Checking document access for applicant:', applicantId)
    const res = await api.get('/document-access/check', {
      params: { applicant_id: applicantId }
    })
    console.log('Document access response:', res.data)
    hasDocumentAccess.value = res.data?.data?.has_access || false
    console.log('hasDocumentAccess set to:', hasDocumentAccess.value)
  } catch (e) {
    console.error('Failed to check document access:', e)
    hasDocumentAccess.value = false
  }
}

async function onDocumentAccessPurchased() {
  hasDocumentAccess.value = true
  await fetchOne()
}

async function previewResume() {
  if (!app.value?.id) return
  
  try {
    // Get the resume URL for preview
    const response = await api.get(`/applications/${app.value.id}/resume`, {
      responseType: 'blob'
    })
    
    // Create blob URL for preview
    const blob = new Blob([response.data], { type: response.data.type || 'application/pdf' })
    resumePreviewUrl.value = window.URL.createObjectURL(blob)
    showResumePreview.value = true
  } catch (e) {
    console.error('Resume preview error:', e)
    
    const status = e?.response?.status
    let msg = 'Preview failed'
    
    if (status === 402) {
      msg = 'Document access payment required.'
    } else if (status === 404) {
      msg = 'Resume not found.'
    } else {
      if (e?.response?.data instanceof Blob) {
        try {
          const text = await e.response.data.text()
          const json = JSON.parse(text)
          msg = json.message || msg
        } catch (parseError) {
          console.error('Could not parse error blob:', parseError)
        }
      } else {
        msg = e?.response?.data?.message || e?.__payload?.message || e?.message || msg
      }
    }
    
    await Swal.fire({
      icon: 'error',
      title: 'Preview Failed',
      text: msg,
    })
  }
}

async function downloadResume() {
  if (!app.value?.id) return
  
  downloadingResume.value = true
  try {
    console.log('Downloading resume for application:', app.value.id)
    const response = await api.get(`/applications/${app.value.id}/resume`, {
      params: { download: 1 },
      responseType: 'blob'
    })
    
    console.log('Resume download response:', response)
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `resume_${app.value.applicant_user_id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    await Swal.fire({
      icon: 'success',
      title: 'Downloaded',
      text: 'Resume downloaded successfully',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (e) {
    console.error('Resume download error:', e)
    console.error('Error response:', e?.response)
    
    const status = e?.response?.status
    let msg = 'Download failed'
    
    if (status === 402) {
      msg = 'Document access payment required. Please purchase document access to download resumes.'
      await Swal.fire({
        icon: 'warning',
        title: 'Document Access Required',
        html: `<p>${msg}</p><p class="text-sm text-slate-600 mt-2">Subscription unlocks messaging and hiring, but documents require separate payment.</p>`,
        confirmButtonText: 'OK',
      })
    } else if (status === 404) {
      msg = 'Resume not found for this applicant.'
      await Swal.fire({
        icon: 'error',
        title: 'Not Found',
        text: msg,
      })
    } else {
      // Try to read error message from blob response
      if (e?.response?.data instanceof Blob) {
        try {
          const text = await e.response.data.text()
          const json = JSON.parse(text)
          msg = json.message || msg
        } catch (parseError) {
          console.error('Could not parse error blob:', parseError)
        }
      } else {
        msg = e?.response?.data?.message || e?.__payload?.message || e?.message || msg
      }
      
      await Swal.fire({
        icon: 'error',
        title: 'Download Failed',
        text: msg,
      })
    }
  } finally {
    downloadingResume.value = false
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
    const res = await api.post(`/applications/${app.value.id}/status`, body)
    app.value = res.data?.data ?? res.data ?? app.value
    await Swal.fire({ icon: 'success', title: 'Updated', text: 'Application status updated.' })
  } catch (e) {
    const status = e?.response?.status
    const msg = e?.response?.data?.message || e?.__payload?.message || e?.message || 'Update failed'
    
    if (status === 402) {
      await Swal.fire({ 
        icon: 'warning', 
        title: 'Subscription Required', 
        html: `<p>${msg}</p><p class="text-sm text-slate-600 mt-2">Your subscription unlocks messaging, interviews, and hiring workflows.</p>`,
        confirmButtonText: 'View Plans',
        showCancelButton: true,
      }).then((result) => {
        if (result.isConfirmed) {
          router.push({ name: 'employer.billing' })
        }
      })
    } else {
      await Swal.fire({ icon: 'error', title: 'Failed', text: msg })
    }
  } finally {
    busy.value = false
  }
}
async function goMessages() {
  if (!app.value || messaging.value) return
  messaging.value = true
  try {
    const u = app.value.applicant || app.value.user || null
    const p = u?.applicant_profile || u?.applicantProfile || null
    const fn = (p?.first_name || '').trim()
    const ln = (p?.last_name || '').trim()
    const display = fn ? `${fn}${ln ? ' ' + ln[0].toUpperCase() + '.' : ''}` : ''
    const name = display || p?.public_display_name || u?.name || u?.full_name || 'there'
    const jobTitle = app.value.job?.title || null
    const firstMessage = jobTitle
      ? `Hi ${name}, thank you for applying for ${jobTitle}. I’d like to connect with you about this role.`
      : `Hi ${name}, I’d like to connect with you about a potential opportunity.`
    const subject = jobTitle
      ? `Regarding your application for ${jobTitle}`
      : `Message to ${name}`
    const targetId =
      app.value.applicant_user_id ||
      u?.id ||
      app.value.applicant_id ||
      app.value.user_id ||
      null
    if (!targetId) throw new Error('Candidate not found')

    const payload = {
      participant_user_ids: [targetId],
      subject,
      first_message: firstMessage,
    }

    try {
      await api.post('/conversations', payload)
    } catch {
      await api.post('/conversations', payload)
    }

    await Swal.fire({
      icon: 'success',
      title: 'Message started',
      text: 'A new conversation with this candidate has been created.',
      timer: 2000,
      showConfirmButton: false,
    })
    router.push({ name: 'employer.messages' })
  } catch (e) {
    const status = e?.response?.status
    const msg =
      e?.response?.data?.message ||
      e?.__payload?.message ||
      e?.message ||
      'Failed to start conversation.'

    if (status === 403) {
      await Swal.fire({
        icon: 'error',
        title: 'Cannot start conversation',
        text: msg || 'You can only message candidates you invited or who applied to your jobs.',
      })
    } else {
      await Swal.fire({
        icon: 'error',
        title: 'Message failed',
        text: msg,
      })
    }
  } finally {
    messaging.value = false
  }
}

onMounted(async () => {
  await fetchOne()
  loadNotes()
  try {
    const u = await me()
    role.value = u?.role || ''
  } catch (e) {}
})
</script>

<style scoped>
</style>
