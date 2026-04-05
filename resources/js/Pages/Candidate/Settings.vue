<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import { http } from '@/lib/http'
import { me as authMe, getCachedUser } from '@/lib/auth'
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import FileUpload from 'primevue/fileupload';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const toast = useToast();
const user = ref(null)
// Sessions
const sessions = ref([])
const sessionsLoading = ref(false)

async function loadSessions() {
  sessionsLoading.value = true
  try {
    const res = await http.get('/user/sessions')
    sessions.value = res.data?.data ?? res.data ?? []
  } catch {} finally { sessionsLoading.value = false }
}

async function revokeSession(tokenId) {
  try {
    await http.delete(`/user/sessions/${tokenId}`)
    sessions.value = sessions.value.filter(s => s.id !== tokenId)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 })
  }
}

async function revokeAllSessions() {
  try {
    await http.delete('/user/sessions')
    sessions.value = sessions.value.filter(s => s.is_current)
    toast.add({ severity: 'success', summary: 'Done', detail: 'All other sessions revoked.', life: 2000 })
  } catch {}
}

const loading = ref(false)
const saving = ref(false)

// GDPR & account deletion
const exportingData = ref(false)
const requestingDeletion = ref(false)
const deletionPending = ref(null)

async function loadDeletionStatus() {
  try {
    const res = await http.get('/user/deletion-status')
    deletionPending.value = res.data?.data?.pending || null
  } catch {}
}

async function exportGdprData() {
  exportingData.value = true
  try {
    const res = await http.get('/user/gdpr-export', { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([res.data], { type: 'application/json' }))
    const a = document.createElement('a'); a.href = url; a.download = 'my-clinforce-data.json'; a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Export failed', life: 3000 })
  } finally { exportingData.value = false }
}

async function requestAccountDeletion() {
  const Swal = (await import('sweetalert2')).default
  const { value: reason, isConfirmed } = await Swal.fire({
    title: 'Delete your account?',
    html: '<p class="text-sm text-slate-600">Your account will be permanently deleted after 30 days. You can cancel before then.</p>',
    input: 'text',
    inputLabel: 'Reason (optional)',
    inputPlaceholder: 'e.g. No longer looking for work',
    showCancelButton: true,
    confirmButtonText: 'Schedule deletion',
    confirmButtonColor: '#ef4444',
  })
  if (!isConfirmed) return
  requestingDeletion.value = true
  try {
    const res = await http.post('/user/request-deletion', { reason: reason || null })
    deletionPending.value = res.data?.data || { delete_at: new Date(Date.now() + 30 * 86400000).toISOString() }
    toast.add({ severity: 'warn', summary: 'Scheduled', detail: 'Account deletion scheduled. Check your email.', life: 5000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 })
  } finally { requestingDeletion.value = false }
}

async function cancelAccountDeletion() {
  try {
    await http.delete('/user/cancel-deletion')
    deletionPending.value = null
    toast.add({ severity: 'success', summary: 'Cancelled', detail: 'Account deletion cancelled.', life: 3000 })
  } catch {}
}

// Notification preferences
const notif = ref({ frequency: 'immediate', category_toggles: {
  status_changes: true,
  interviews: true,
  messages: true,
  job_alerts: true,
  invitations: true,
} })
const notifLoading = ref(false)
const notifSaving = ref(false)
const frequencyOptions = [
  { label: 'Immediate', value: 'immediate' },
  { label: 'Daily digest', value: 'daily' },
  { label: 'Weekly summary', value: 'weekly' },
]
const notifTypes = [
  { key: 'status_changes', label: 'Application status changes', icon: 'pi-file' },
  { key: 'interviews',     label: 'Interview invitations',       icon: 'pi-calendar' },
  { key: 'messages',       label: 'New messages',                icon: 'pi-envelope' },
  { key: 'job_alerts',     label: 'Job alerts',                  icon: 'pi-bell' },
  { key: 'invitations',    label: 'Employer invitations',        icon: 'pi-send' },
]

async function loadNotificationPrefs() {
  notifLoading.value = true
  try {
    const res = await http.get('/notifications/preferences')
    const p = res?.data?.data
    if (p) {
      if (typeof p.frequency === 'string') notif.value.frequency = p.frequency
      if (p.category_toggles && typeof p.category_toggles === 'object') {
        notif.value.category_toggles = { ...notif.value.category_toggles, ...p.category_toggles }
      }
    }
  } catch {}
  finally { notifLoading.value = false }
}

async function saveNotificationPrefs() {
  notifSaving.value = true
  try {
    await http.put('/notifications/preferences', {
      frequency: notif.value.frequency,
      category_toggles: notif.value.category_toggles,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Notification preferences updated.', life: 3000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to save preferences.', life: 5000 })
  } finally { notifSaving.value = false }
}

const form = ref({
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const avatarPreview = ref(null)
const avatarFile = ref(null)
const fileUploadRef = ref(null)

// Cropper state
const showCropDialog = ref(false)
const cropImage = ref(null)
const cropperRef = ref(null)

function buildAvatarUrl(raw) {
  if (!raw) return null
  const v = String(raw)
  if (/^https?:\/\//i.test(v)) return v
  if (v.startsWith('/uploads/')) return v
  if (v.startsWith('uploads/')) return `/${v}`
  if (v.startsWith('/storage/')) return v
  if (v.startsWith('storage/')) return `/${v}`
  return `/storage/${v.replace(/^\/+/, '')}`
}

const passwordStrength = computed(() => {
  const value = form.value.password || ''
  if (!value) return 0
  let score = 0
  if (value.length >= 6) score++
  if (value.length >= 10) score++
  if (/[A-Z]/.test(value) && /[0-9]/.test(value)) score++
  return Math.min(score, 3)
})

const passwordStrengthLabel = computed(() => {
  if (!form.value.password) return 'Enter a strong password.'
  if (passwordStrength.value === 1) return 'Weak password'
  if (passwordStrength.value === 2) return 'Good password'
  return 'Strong password'
})

const passwordStrengthWidth = computed(() => {
  if (!form.value.password) return '0%'
  if (passwordStrength.value === 1) return '33%'
  if (passwordStrength.value === 2) return '66%'
  return '100%'
})

const passwordStrengthClass = computed(() => {
  if (!form.value.password) return 'bg-slate-200'
  if (passwordStrength.value === 1) return 'bg-rose-500'
  if (passwordStrength.value === 2) return 'bg-amber-500'
  return 'bg-emerald-500'
})

async function fetchUser() {
  try {
    const res = await http.get('/me')
    const outer = res?.data?.data ?? res?.data ?? {}
    const u = outer?.data ?? outer?.user ?? outer
    user.value = u
    if (u) {
      form.value.email = u.email || ''
      form.value.phone = u.phone || ''
      const url = u.avatar_url || u.avatar || null
      if (url) {
        avatarPreview.value = buildAvatarUrl(url)
      }
      try {
        localStorage.setItem('auth_user', JSON.stringify({
          ...(JSON.parse(localStorage.getItem('auth_user') || '{}')),
          ...u,
        }))
        window.dispatchEvent(new Event('auth:changed'))
      } catch {}
    }
    if (!avatarPreview.value) {
      try {
        const authUser = await authMe()
        if (authUser && (authUser.avatar_url || authUser.avatar)) {
          avatarPreview.value = buildAvatarUrl(authUser.avatar_url || authUser.avatar)
        }
      } catch {}
    }
  } catch (e) {
    console.error("Failed to fetch user", e)
  }
}

onMounted(() => {
  try {
    const cached = getCachedUser()
    if (cached && (cached.avatar_url || cached.avatar)) {
      avatarPreview.value = buildAvatarUrl(cached.avatar_url || cached.avatar)
    }
  } catch {}
  fetchUser()
  loadNotificationPrefs()
  loadSessions()
  loadDeletionStatus()
})

function onFileSelect(event) {
    const file = event.files[0];
    if (file) {
        // Create a blob URL for the cropper
        if (cropImage.value) {
            URL.revokeObjectURL(cropImage.value)
        }
        cropImage.value = URL.createObjectURL(file)
        showCropDialog.value = true
    }
}

function applyCrop() {
    const { canvas } = cropperRef.value.getResult();
    if (!canvas) return;

    // Downscale to keep well under 5MB and reduce dimensions
    const maxDim = 640;
    const scale = Math.min(1, maxDim / Math.max(canvas.width, canvas.height));
    const outW = Math.max(1, Math.round(canvas.width * scale));
    const outH = Math.max(1, Math.round(canvas.height * scale));
    const outCanvas = document.createElement('canvas');
    outCanvas.width = outW;
    outCanvas.height = outH;
    const ctx = outCanvas.getContext('2d');
    ctx.drawImage(canvas, 0, 0, outW, outH);

    const finalize = (blob) => {
      if (!blob) return;
      const file = new File([blob], "avatar.jpg", { type: "image/jpeg" });
      avatarFile.value = file;
      if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
        URL.revokeObjectURL(avatarPreview.value);
      }
      avatarPreview.value = URL.createObjectURL(blob);
      showCropDialog.value = false;
      if (fileUploadRef.value) fileUploadRef.value.clear();
    };

    // Encode as JPEG with quality; adjust if still large
    outCanvas.toBlob((blob) => {
      if (!blob) return;
      if (blob.size > 4.8 * 1024 * 1024) {
        outCanvas.toBlob((b2) => finalize(b2), 'image/jpeg', 0.75);
      } else {
        finalize(blob);
      }
    }, 'image/jpeg', 0.85);
}

function cancelCrop() {
    showCropDialog.value = false;
    cropImage.value = null;
    // Clear the file upload component
    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }
}

function clearAvatar() {
  if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(avatarPreview.value)
  }
  avatarPreview.value = null
  avatarFile.value = null
}

async function saveSettings() {
  saving.value = true
  
  try {
    const formData = new FormData()
    const emailTrim = (form.value.email || '').trim()
    const phoneTrim = (form.value.phone || '').trim()
    if (emailTrim) formData.append('email', emailTrim)
    if (phoneTrim) formData.append('phone', phoneTrim)
    
    if (form.value.password) {
      formData.append('password', form.value.password)
      formData.append('password_confirmation', form.value.password_confirmation)
    }

    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    
    // Use POST with _method=PUT for file uploads
    formData.append('_method', 'PUT')

    const res = await http.post('/api/user/settings', formData)
    const payload = res?.data?.data || res?.data || {}
    const updatedUser = payload.user || payload
    if (updatedUser && (updatedUser.avatar_url || updatedUser.avatar)) {
      avatarPreview.value = buildAvatarUrl(updatedUser.avatar_url || updatedUser.avatar)
    }
    
    await fetchUser()
    try {
      await authMe()
    } catch {}
    
    toast.add({ severity: 'success', summary: 'Success', detail: 'Settings updated successfully.', life: 3000 });
    
    // Clear password fields
    form.value.password = ''
    form.value.password_confirmation = ''
    avatarFile.value = null
  } catch (err) {
    const payload = err?.response?.data;
    let msg = payload?.message || 'Failed to update settings';
    const errs = payload?.errors;
    if (errs && typeof errs === 'object') {
      const firstKey = Object.keys(errs)[0];
      const firstMsg = firstKey && Array.isArray(errs[firstKey]) ? errs[firstKey][0] : null;
      if (firstMsg) msg = firstMsg;
    }
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 6000 });
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="min-h-screen">
      <div class="w-full max-w-4xl lg:max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-10">
        <Toast />

        <div class="overflow-hidden">
          <div class="px-5 md:px-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 md:gap-6">
              <div class="space-y-1.5">
                <div class="inline-flex flex-wrap items-center gap-2">
                  <h1 class="text-xl md:text-2xl font-semibold text-slate-900 tracking-tight">
                    Account settings
                  </h1>
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-sky-50 text-sky-700">
                    Candidate
                  </span>
                </div>
                <p class="text-sm text-slate-500 max-w-xl">
                  Manage how we contact you and secure your account.
                </p>
              </div>
              <Button
                label="Save changes"
                icon="pi pi-check"
                @click="saveSettings"
                :loading="saving"
                class="w-full md:w-auto !px-5 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800 active:!bg-slate-950 !border-slate-900 !text-sm shadow-sm hover:shadow-md transition-all"
              />
            </div>
          </div>

          <div class="px-5 md:px-8 py-6 md:py-8 space-y-6 settings-grid">
            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr,0.9fr] gap-6">
              <Card class="transition-all hover:shadow-md hover:border-slate-200">
                <template #title>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">Profile picture</span>
                      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium  text-slate-500">
                        Step 1
                      </span>
                    </div>
                  </div>
                </template>
                <template #subtitle>
                  <span class="text-xs text-slate-500">Give your profile a friendly face.</span>
                </template>
                <template #content>
                  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pt-1">
                    <div class="relative w-24 h-24 rounded-full overflow-hidden ring-4 ring-sky-50 flex items-center justify-center shrink-0">
                      <img v-if="avatarPreview" :src="avatarPreview" alt="Profile Preview" class="w-full h-full object-cover" />
                      <span v-else class="text-3xl font-semibold text-slate-400">
                        {{ user?.email?.charAt(0).toUpperCase() || 'U' }}
                      </span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                      <FileUpload
                        ref="fileUploadRef"
                        mode="basic"
                        name="avatar"
                        accept="image/*"
                        :maxFileSize="5000000"
                        @select="onFileSelect"
                        :auto="true"
                        customUpload
                        @uploader="() => {}"
                        chooseLabel="Change photo"
                        class="w-fit !px-4 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800  !text-sm"
                      />
                      <div class="flex flex-wrap items-center gap-2">
                        <Button
                          v-if="avatarPreview"
                          label="Remove"
                          severity="secondary"
                          text
                          size="small"
                          class="!px-0 !py-0 !text-xs !font-medium"
                          @click="clearAvatar"
                        />
                        <span class="text-xs text-slate-500">
                          JPG, GIF or PNG. Max size of 5MB.
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>

              <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl transition-all hover:shadow-md hover:border-slate-200">
                <template #title>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">Contact details</span>
                      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-50 text-slate-500">
                        Step 2
                      </span>
                    </div>
                  </div>
                </template>
                <template #content>
                  <div class="pt-1 space-y-4">
                    <div class="flex flex-col gap-2">
                      <label for="email" class="font-medium text-xs uppercase tracking-wide text-slate-500">Email address</label>
                      <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="you@example.com"
                        class="w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      />
                    </div>
                    <div class="flex flex-col gap-2">
                      <label for="phone" class="font-medium text-xs uppercase tracking-wide text-slate-500">Phone number</label>
                      <InputText
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="+1 (555) 000-0000"
                        class="w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      />
                    </div>
                  </div>
                </template>
              </Card>
            </div>

            <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl transition-all hover:shadow-md hover:border-slate-200">
              <template #title>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-900">Change password</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-50 text-slate-500">
                      Optional
                    </span>
                  </div>
                </div>
              </template>
              <template #subtitle>
                <span class="text-xs text-slate-500">Leave blank if you don't want to change it.</span>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 pt-1">
                  <div class="flex flex-col gap-2">
                    <label for="password" class="font-medium text-xs uppercase tracking-wide text-slate-500">New password</label>
                    <Password
                      id="password"
                      v-model="form.password"
                      toggleMask
                      :feedback="true"
                      placeholder="••••••••"
                      inputClass="!w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      class="w-full"
                    />
                    <div class="mt-1.5 flex flex-col gap-1">
                      <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all"
                          :class="passwordStrengthClass"
                          :style="{ width: passwordStrengthWidth }"
                        />
                      </div>
                      <span class="text-[11px] text-slate-500">
                        {{ passwordStrengthLabel }}
                      </span>
                    </div>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="font-medium text-xs uppercase tracking-wide text-slate-500">Confirm password</label>
                    <Password
                      id="password_confirmation"
                      v-model="form.password_confirmation"
                      toggleMask
                      :feedback="false"
                      placeholder="••••••••"
                      inputClass="!w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      class="w-full"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

            <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl transition-all hover:shadow-md hover:border-slate-200">
              <template #title>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-slate-900">Notification preferences</span>
                </div>
              </template>
              <template #subtitle>
                <span class="text-xs text-slate-500">Choose how often you receive notifications.</span>
              </template>
              <template #content>
                <div class="pt-1 space-y-5">
                  <div class="flex flex-col gap-2">
                    <label class="font-medium text-xs uppercase tracking-wide text-slate-500">Frequency</label>
                    <Select
                      v-model="notif.frequency"
                      :options="frequencyOptions"
                      optionLabel="label"
                      optionValue="value"
                      :loading="notifLoading"
                      class="w-full md:w-64"
                    />
                  </div>
                  <div class="space-y-2">
                    <label class="font-medium text-xs uppercase tracking-wide text-slate-500">Notify me about</label>
                    <div v-for="t in notifTypes" :key="t.key"
                      class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                      <div class="flex items-center gap-2.5">
                        <i :class="['pi text-sm text-slate-400', t.icon]"></i>
                        <span class="text-sm font-medium text-slate-700">{{ t.label }}</span>
                      </div>
                      <button @click="notif.category_toggles[t.key] = !notif.category_toggles[t.key]"
                        :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                          notif.category_toggles[t.key] ? 'bg-sky-600' : 'bg-slate-200']">
                        <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
                          notif.category_toggles[t.key] ? 'translate-x-4' : 'translate-x-1']"></span>
                      </button>
                    </div>
                  </div>
                  <div class="flex justify-end">
                    <Button label="Save preferences" icon="pi pi-save" size="small" :loading="notifSaving" @click="saveNotificationPrefs" class="!rounded-full !bg-slate-900 hover:!bg-slate-800 !border-slate-900 !text-sm" />
                  </div>
                </div>
              </template>
            </Card>

            <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl">
              <template #title><span class="text-sm font-semibold text-slate-900">Active Sessions</span></template>
              <template #subtitle><span class="text-xs text-slate-500">Manage where you're logged in.</span></template>
              <template #content>
                <div v-if="sessionsLoading" class="py-4 text-center text-slate-400"><i class="pi pi-spin pi-spinner"></i></div>
                <div v-else class="space-y-2 pt-1">
                  <div v-for="s in sessions" :key="s.id"
                    class="flex items-center justify-between p-3 rounded-xl border"
                    :class="s.is_current ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-slate-50'">
                    <div>
                      <div class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                        {{ s.name }}
                        <span v-if="s.is_current" class="text-[10px] bg-sky-600 text-white px-1.5 py-0.5 rounded-full">Current</span>
                      </div>
                      <div class="text-xs text-slate-500 mt-0.5">
                        Last active: {{ s.last_used_at ? new Date(s.last_used_at).toLocaleString() : 'Never' }}
                      </div>
                    </div>
                    <Button v-if="!s.is_current" icon="pi pi-times" size="small" severity="danger" outlined
                      v-tooltip="'Revoke'" @click="revokeSession(s.id)" />
                  </div>
                  <div v-if="!sessions.length" class="text-sm text-slate-400 py-2">No active sessions.</div>
                  <div class="flex justify-end pt-1">
                    <Button label="Revoke all other sessions" size="small" severity="secondary" outlined
                      class="!rounded-full !text-sm"
                      :disabled="sessions.filter(s => !s.is_current).length === 0"
                      @click="revokeAllSessions" />
                  </div>
                </div>
              </template>
            </Card>

            <!-- GDPR + Account Deletion -->
            <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl">
              <template #title><span class="text-sm font-semibold text-slate-900">Privacy & Data</span></template>
              <template #subtitle><span class="text-xs text-slate-500">Your data rights under GDPR.</span></template>
              <template #content>
                <div class="pt-1 space-y-4">
                  <!-- Data export -->
                  <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <div>
                      <div class="text-sm font-semibold text-slate-900">Export my data</div>
                      <div class="text-xs text-slate-500 mt-0.5">Download all your profile, applications, and messages as JSON.</div>
                    </div>
                    <Button label="Download" icon="pi pi-download" size="small" severity="secondary" outlined
                      :loading="exportingData" @click="exportGdprData" class="!rounded-full !text-sm flex-shrink-0 ml-4" />
                  </div>
                  <!-- Account deletion -->
                  <div class="flex items-center justify-between p-4 rounded-xl border"
                    :class="deletionPending ? 'bg-red-50 border-red-200' : 'bg-slate-50 border-slate-100'">
                    <div>
                      <div class="text-sm font-semibold" :class="deletionPending ? 'text-red-700' : 'text-slate-900'">
                        {{ deletionPending ? 'Account deletion scheduled' : 'Delete my account' }}
                      </div>
                      <div class="text-xs mt-0.5" :class="deletionPending ? 'text-red-500' : 'text-slate-500'">
                        {{ deletionPending
                          ? `Your account will be deleted on ${new Date(deletionPending.delete_at).toLocaleDateString()}. Log in before then to cancel.`
                          : 'Permanently delete your account after a 30-day grace period.' }}
                      </div>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                      <Button v-if="!deletionPending" label="Delete account" icon="pi pi-trash" size="small" severity="danger" outlined
                        :loading="requestingDeletion" @click="requestAccountDeletion" class="!rounded-full !text-sm" />
                      <Button v-else label="Cancel deletion" icon="pi pi-times" size="small" severity="secondary" outlined
                        @click="cancelAccountDeletion" class="!rounded-full !text-sm" />
                    </div>
                  </div>
                </div>
              </template>
            </Card>

          <Dialog v-model:visible="showCropDialog" modal header="Adjust image" :style="{ width: '500px' }" :closable="false">
            <div class="flex flex-col gap-4">
              <div class="w-full h-96 bg-black flex items-center justify-center overflow-hidden rounded-lg">
                <Cropper
                  ref="cropperRef"
                  class="cropper"
                  :src="cropImage"
                  :stencil-props="{ aspectRatio: 1/1 }"
                />
              </div>
              <div class="flex justify-end gap-2">
                <Button label="Cancel" text severity="secondary" @click="cancelCrop" />
                <Button label="Apply" icon="pi pi-check" @click="applyCrop" />
              </div>
            </div>
          </Dialog>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.cropper {
    height: 100%;
    width: 100%;
    background: #DDD;
}

.settings-grid .p-card {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: #e5e7eb !important; /* slate-200 */
    box-shadow: none !important;
}

.settings-grid .p-inputtext,
.settings-grid .p-password-input,
.settings-grid input,
.settings-grid textarea {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: #e5e7eb !important; /* slate-200 */
    box-shadow: none !important;
}
</style>
