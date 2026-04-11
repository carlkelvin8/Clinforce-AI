<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import { toast } from '@/composables/useAppToast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const router = useRouter()
const confirm = useConfirm()

const loading = ref(false)
const saving = ref(false)
const templates = ref([])
const showDialog = ref(false)
const editingId = ref(null)

const employmentOptions = [
  { label: 'Any', value: '' },
  { label: 'Full-Time/Part-Time', value: 'full_time_part_time' },
  { label: 'Contract/Temporary', value: 'contract_temporary' },
  { label: 'Internship', value: 'internship' },
]
const workModeOptions = [
  { label: 'Any', value: '' },
  { label: 'On-site', value: 'on_site' },
  { label: 'Remote', value: 'remote' },
  { label: 'Hybrid', value: 'hybrid' },
]

const emptyForm = () => ({
  name: '',
  title: '',
  description: '',
  employment_type: '',
  work_mode: '',
  country: '',
  city: '',
  salary_min: '',
  salary_max: '',
  salary_currency: 'USD',
})

const form = ref(emptyForm())

async function load() {
  loading.value = true
  try {
    const res = await api.get('/job-templates')
    templates.value = res.data?.data ?? res.data ?? []
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to load templates')
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = emptyForm()
  showDialog.value = true
}

function openEdit(t) {
  editingId.value = t.id
  form.value = {
    name: t.name || '',
    title: t.title || '',
    description: t.description || '',
    employment_type: t.employment_type || '',
    work_mode: t.work_mode || '',
    country: t.country || '',
    city: t.city || '',
    salary_min: t.salary_min ?? '',
    salary_max: t.salary_max ?? '',
    salary_currency: t.salary_currency || 'USD',
  }
  showDialog.value = true
}

async function save() {
  if (!form.value.name.trim()) {
    toast.warn('Template name is required')
    return
  }
  saving.value = true
  try {
    if (editingId.value) {
      const res = await api.put(`/job-templates/${editingId.value}`, form.value)
      const updated = res.data?.data ?? res.data
      const idx = templates.value.findIndex(t => t.id === editingId.value)
      if (idx !== -1) templates.value[idx] = updated
      toast.success('Template updated')
    } else {
      const res = await api.post('/job-templates', form.value)
      templates.value.unshift(res.data?.data ?? res.data)
      toast.success('Template saved')
    }
    showDialog.value = false
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Save failed')
  } finally {
    saving.value = false
  }
}

function confirmDelete(t) {
  confirm.require({
    message: `Delete template "${t.name}"?`,
    header: 'Delete template',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
    acceptProps: { label: 'Delete', severity: 'danger' },
    accept: () => deleteTemplate(t),
  })
}

async function deleteTemplate(t) {
  try {
    await api.delete(`/job-templates/${t.id}`)
    templates.value = templates.value.filter(x => x.id !== t.id)
    toast.success('Template deleted')
  } catch (e) {
    toast.error('Delete failed')
  }
}

async function useTemplate(t) {
  try {
    const res = await api.post(`/job-templates/${t.id}/use`)
    const job = res.data?.data ?? res.data
    toast.success('Draft job created — redirecting to edit...')
    setTimeout(() => router.push({ name: 'employer.jobs.edit', params: { id: job.id } }), 800)
  } catch (e) {
    toast.error(e?.response?.data?.message || 'Failed to create job from template')
  }
}

function formatEnum(v) {
  return String(v || '').split('_').map(w => w ? w[0].toUpperCase() + w.slice(1) : '').join(' ')
}

onMounted(load)
</script>

<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-4 md:px-6 py-6 space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Job Templates</h1>
          <p class="text-gray-500 mt-1">Save reusable job structures to speed up posting.</p>
        </div>
        <Button label="New Template" icon="pi pi-plus" @click="openCreate" />
      </div>

      <div v-if="loading" class="py-12 text-center text-gray-400">
        <i class="pi pi-spin pi-spinner text-2xl"></i>
      </div>

      <div v-else-if="!templates.length" class="bg-white rounded-2xl border border-dashed border-gray-200 py-16 text-center">
        <i class="pi pi-copy text-4xl text-gray-300 mb-3 block"></i>
        <p class="text-gray-600 font-medium">No templates yet</p>
        <p class="text-sm text-gray-400 mt-1">Create a template to quickly reuse job details.</p>
        <Button label="Create Template" icon="pi pi-plus" class="mt-4" @click="openCreate" />
      </div>

      <div v-else class="grid gap-4 sm:grid-cols-2">
        <div
          v-for="t in templates"
          :key="t.id"
          class="bg-white rounded-2xl border border-gray-200 p-5 hover:shadow-md transition-all"
        >
          <div class="flex items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
              <h3 class="font-bold text-gray-900 truncate">{{ t.name }}</h3>
              <p v-if="t.title" class="text-sm text-gray-500 truncate mt-0.5">{{ t.title }}</p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <Button icon="pi pi-pencil" text rounded size="small" @click="openEdit(t)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="confirmDelete(t)" />
            </div>
          </div>

          <div class="flex flex-wrap gap-2 mb-4">
            <Tag v-if="t.employment_type" :value="formatEnum(t.employment_type)" severity="secondary" />
            <Tag v-if="t.work_mode" :value="formatEnum(t.work_mode)" severity="info" />
            <Tag v-if="t.city" :value="t.city" severity="secondary" />
            <span v-if="t.salary_min || t.salary_max" class="text-xs text-gray-500 flex items-center gap-1">
              <i class="pi pi-dollar text-[10px]"></i>
              {{ t.salary_min ? t.salary_min.toLocaleString() : '—' }} – {{ t.salary_max ? t.salary_max.toLocaleString() : '—' }}
              {{ t.salary_currency || '' }}
            </span>
          </div>

          <Button
            label="Use Template"
            icon="pi pi-play"
            size="small"
            class="w-full !rounded-xl"
            @click="useTemplate(t)"
          />
        </div>
      </div>

      <!-- Create/Edit Dialog -->
      <Dialog v-model:visible="showDialog" modal :header="editingId ? 'Edit Template' : 'New Template'" :style="{ width: '560px' }">
        <div class="space-y-4 py-2">
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-gray-700">Template name <span class="text-red-500">*</span></label>
            <InputText v-model="form.name" placeholder="e.g. ICU Nurse — Standard" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-gray-700">Job title</label>
            <InputText v-model="form.title" placeholder="e.g. ICU Registered Nurse" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-gray-700">Description</label>
            <Textarea v-model="form.description" rows="4" placeholder="Job description..." class="w-full !rounded-xl" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Employment type</label>
              <Select v-model="form.employment_type" :options="employmentOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Work mode</label>
              <Select v-model="form.work_mode" :options="workModeOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">City</label>
              <InputText v-model="form.city" placeholder="Manila" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Country</label>
              <InputText v-model="form.country" placeholder="Philippines" class="w-full" />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Min salary</label>
              <InputText v-model="form.salary_min" placeholder="30000" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Max salary</label>
              <InputText v-model="form.salary_max" placeholder="60000" class="w-full" />
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-semibold text-gray-700">Currency</label>
              <InputText v-model="form.salary_currency" placeholder="USD" class="w-full" />
            </div>
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text severity="secondary" @click="showDialog = false" />
          <Button :label="editingId ? 'Update' : 'Save Template'" icon="pi pi-check" :loading="saving" @click="save" />
        </template>
      </Dialog>

      <ConfirmDialog />
    </div>
  </AppLayout>
</template>
