<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { toast } from '@/composables/useAppToast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import ToggleSwitch from 'primevue/toggleswitch'

const loading = ref(false)
const saving = ref(false)
const alerts = ref([])
const showDialog = ref(false)

const employmentTypes = [
  { label: 'Any', value: '' },
  { label: 'Full-time', value: 'full_time' },
  { label: 'Part-time', value: 'part_time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Temporary', value: 'temporary' },
]

const workModes = [
  { label: 'Any', value: '' },
  { label: 'Remote', value: 'remote' },
  { label: 'On-site', value: 'on_site' },
  { label: 'Hybrid', value: 'hybrid' },
]

const form = ref({
  keywords: '',
  location: '',
  employment_type: '',
  work_mode: '',
  is_active: true,
})

function resetForm() {
  form.value = { keywords: '', location: '', employment_type: '', work_mode: '', is_active: true }
}

async function load() {
  loading.value = true
  try {
    const res = await api.get('/job-alerts')
    alerts.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to load alerts')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const res = await api.post('/job-alerts', form.value)
    alerts.value.unshift(res.data?.data ?? res.data)
    showDialog.value = false
    resetForm()
    toast.success('You will be notified when matching jobs are posted.')
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to create alert')
  } finally {
    saving.value = false
  }
}

async function toggleAlert(alert) {
  try {
    const res = await api.put(`/job-alerts/${alert.id}`, { is_active: !alert.is_active })
    const updated = res.data?.data ?? res.data
    const idx = alerts.value.findIndex(a => a.id === alert.id)
    if (idx !== -1) alerts.value[idx] = updated
  } catch (e) {
    toast.error('Failed to update alert')
  }
}

async function deleteAlert(id) {
  try {
    await api.delete(`/job-alerts/${id}`)
    alerts.value = alerts.value.filter(a => a.id !== id)
    toast.success('Job alert removed.')
  } catch (e) {
    toast.error('Failed to delete alert')
  }
}

onMounted(load)
</script>

<template>
  <AppLayout>
    <div class="max-w-4xl mx-auto px-4 md:px-6 py-6 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Job Alerts</h1>
          <p class="text-slate-500 mt-1">Get notified when matching jobs are posted.</p>
        </div>
        <Button label="New Alert" icon="pi pi-plus" @click="showDialog = true" />
      </div>

      <div v-if="loading" class="py-12 text-center text-slate-400">
        <i class="pi pi-spin pi-spinner text-2xl"></i>
      </div>

      <div v-else-if="!alerts.length" class="bg-white rounded-2xl border border-dashed border-slate-200 py-16 text-center">
        <i class="pi pi-bell text-4xl text-slate-300 mb-3"></i>
        <p class="text-slate-600 font-medium">No job alerts yet</p>
        <p class="text-sm text-slate-400 mt-1">Create an alert to get notified when matching jobs are posted.</p>
        <Button label="Create Alert" icon="pi pi-plus" class="mt-4" @click="showDialog = true" />
      </div>

      <div v-else class="space-y-3">
        <div v-for="alert in alerts" :key="alert.id"
          class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="font-semibold text-slate-900">{{ alert.keywords || 'Any keywords' }}</span>
              <Tag v-if="alert.location" :value="alert.location" severity="secondary" />
              <Tag v-if="alert.employment_type" :value="alert.employment_type" severity="info" />
              <Tag v-if="alert.work_mode" :value="alert.work_mode" severity="info" />
            </div>
            <div class="text-xs text-slate-400">
              Created {{ new Date(alert.created_at).toLocaleDateString() }}
            </div>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
              <span class="text-xs text-slate-500">{{ alert.is_active ? 'Active' : 'Paused' }}</span>
              <ToggleSwitch :modelValue="alert.is_active" @update:modelValue="toggleAlert(alert)" />
            </div>
            <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="deleteAlert(alert.id)" />
          </div>
        </div>
      </div>

      <Dialog v-model:visible="showDialog" modal header="New Job Alert" :style="{ width: '480px' }">
        <div class="space-y-4 py-2">
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700">Keywords</label>
            <InputText v-model="form.keywords" placeholder="e.g. ICU Nurse, RN, Surgeon" class="w-full" />
            <p class="text-xs text-slate-400">Job title, specialty, or skills</p>
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700">Location</label>
            <InputText v-model="form.location" placeholder="e.g. Manila, Remote" class="w-full" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-slate-700">Employment type</label>
              <Select v-model="form.employment_type" :options="employmentTypes" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-slate-700">Work mode</label>
              <Select v-model="form.work_mode" :options="workModes" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text severity="secondary" @click="showDialog = false; resetForm()" />
          <Button label="Create Alert" icon="pi pi-check" :loading="saving" @click="save" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>
