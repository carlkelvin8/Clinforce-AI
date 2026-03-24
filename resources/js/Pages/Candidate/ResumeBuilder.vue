<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

const toast = useToast()
const loading = ref(false)
const saving = ref(false)

const skills = ref([])
const newSkill = ref('')

const workExperience = ref([])
const education = ref([])

function addSkill() {
  const s = newSkill.value.trim()
  if (s && !skills.value.includes(s)) skills.value.push(s)
  newSkill.value = ''
}

function removeSkill(i) { skills.value.splice(i, 1) }

function addWork() {
  workExperience.value.push({ title: '', company: '', start_date: '', end_date: '', description: '' })
}
function removeWork(i) { workExperience.value.splice(i, 1) }

function addEducation() {
  education.value.push({ degree: '', institution: '', start_date: '', end_date: '' })
}
function removeEducation(i) { education.value.splice(i, 1) }

async function load() {
  loading.value = true
  try {
    const res = await api.get('/me/applicant')
    const p = res?.data?.data || res?.data || {}
    skills.value = Array.isArray(p.skills) ? p.skills : []
    workExperience.value = Array.isArray(p.work_experience) ? p.work_experience : []
    education.value = Array.isArray(p.education) ? p.education : []
  } catch {}
  finally { loading.value = false }
}

async function save() {
  saving.value = true
  try {
    await api.put('/me/applicant', {
      skills: skills.value,
      work_experience: workExperience.value,
      education: education.value,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Resume updated successfully.', life: 3000 })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to save.', life: 5000 })
  } finally { saving.value = false }
}

onMounted(load)
</script>

<template>
  <AppLayout>
    <Toast />
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Resume Builder</h1>
          <p class="text-sm text-slate-500 mt-1">Build your structured profile to stand out to employers.</p>
        </div>
        <Button label="Save Resume" icon="pi pi-check" :loading="saving" @click="save" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" />
      </div>

      <!-- Skills -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
          <i class="pi pi-star text-blue-600"></i> Skills
        </h2>
        <div class="flex gap-2">
          <InputText v-model="newSkill" placeholder="e.g. ICU Care" class="flex-1" @keydown.enter.prevent="addSkill" />
          <Button icon="pi pi-plus" @click="addSkill" outlined />
        </div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(s, i) in skills"
            :key="i"
            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium border border-blue-100"
          >
            {{ s }}
            <button @click="removeSkill(i)" class="text-blue-400 hover:text-blue-700 ml-1">&times;</button>
          </span>
          <span v-if="skills.length === 0" class="text-sm text-slate-400">No skills added yet.</span>
        </div>
      </div>

      <!-- Work Experience -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-briefcase text-blue-600"></i> Work Experience
          </h2>
          <Button label="Add" icon="pi pi-plus" size="small" outlined @click="addWork" />
        </div>
        <div v-if="workExperience.length === 0" class="text-sm text-slate-400">No work experience added yet.</div>
        <div v-for="(w, i) in workExperience" :key="i" class="border border-slate-100 rounded-xl p-4 space-y-3 relative">
          <button @click="removeWork(i)" class="absolute top-3 right-3 text-slate-400 hover:text-red-500 text-lg leading-none">&times;</button>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Job Title</label>
              <InputText v-model="w.title" placeholder="e.g. Staff Nurse" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Company / Hospital</label>
              <InputText v-model="w.company" placeholder="e.g. St. Luke's Medical Center" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Start Date</label>
              <InputText v-model="w.start_date" placeholder="e.g. Jan 2020" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">End Date</label>
              <InputText v-model="w.end_date" placeholder="e.g. Dec 2023 or Present" class="w-full" />
            </div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-slate-500">Description</label>
            <Textarea v-model="w.description" rows="3" placeholder="Describe your responsibilities..." class="w-full" />
          </div>
        </div>
      </div>

      <!-- Education -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-graduation-cap text-blue-600"></i> Education
          </h2>
          <Button label="Add" icon="pi pi-plus" size="small" outlined @click="addEducation" />
        </div>
        <div v-if="education.length === 0" class="text-sm text-slate-400">No education added yet.</div>
        <div v-for="(e, i) in education" :key="i" class="border border-slate-100 rounded-xl p-4 space-y-3 relative">
          <button @click="removeEducation(i)" class="absolute top-3 right-3 text-slate-400 hover:text-red-500 text-lg leading-none">&times;</button>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Degree / Course</label>
              <InputText v-model="e.degree" placeholder="e.g. BSN Nursing" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Institution</label>
              <InputText v-model="e.institution" placeholder="e.g. University of Santo Tomas" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">Start Year</label>
              <InputText v-model="e.start_date" placeholder="e.g. 2016" class="w-full" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="text-xs font-medium text-slate-500">End Year</label>
              <InputText v-model="e.end_date" placeholder="e.g. 2020" class="w-full" />
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <Button label="Save Resume" icon="pi pi-check" :loading="saving" @click="save" class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700" />
      </div>
    </div>
  </AppLayout>
</template>
