<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import Swal from 'sweetalert2'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Message from 'primevue/message'
import Tag from 'primevue/tag'

const props = defineProps({
  id: { type: [String, Number], required: true },
})

const router = useRouter()
const loading = ref(false)
const error = ref('')
const profile = ref(null)
const inviting = ref(false)
const messaging = ref(false)

function formatDate(v) {
  if (!v) return 'N/A'
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleDateString()
}

function toArray(val) {
  if (!val) return []
  if (Array.isArray(val)) return val.filter(Boolean)
  if (typeof val === 'string') return val.split(',').map(s => s.trim()).filter(Boolean)
  return []
}

async function fetchProfile() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get(`/profiles/${props.id}`)
    profile.value = res.data?.data ?? res.data
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || 'Failed to load profile'
    profile.value = null
  } finally {
    loading.value = false
  }
}

async function invite() {
  if (!profile.value?.id) return
  if (inviting.value) return
  inviting.value = true
  try {
    const name = profile.value.first_name || profile.value.name || 'there'
    const msg = `Hi ${name}, we’d like to invite you to explore opportunities with us on Clinforce.`
    await api.post('/invitations', {
      candidate_id: profile.value.id,
      message: msg,
    })
    await Swal.fire({
      icon: 'success',
      title: 'Invitation sent',
      text: 'The candidate has been invited successfully.',
      timer: 2000,
      showConfirmButton: false,
    })
  } catch (e) {
    const msg = e?.__payload?.message || e?.message || 'Failed to send invitation.'
    await Swal.fire({
      icon: 'error',
      title: 'Invitation failed',
      text: msg,
    })
  } finally {
    inviting.value = false
  }
}

async function message() {
  if (!profile.value?.id) return
  if (messaging.value) return
  messaging.value = true
  try {
    const name = profile.value.first_name || profile.value.name || 'there'
    const firstMessage = `Hi ${name}, I’d like to connect with you about a potential role.`
    const payload = {
      participant_user_ids: [profile.value.id],
      subject: `Message to ${profile.value.name || name}`,
      first_message: firstMessage,
    }
    try {
      await api.post('/api/conversations', payload)
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

onMounted(fetchProfile)
</script>

<template>
  <AppLayout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-2xl border border-slate-200 p-6 relative overflow-hidden shadow-sm">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <Avatar 
              v-if="profile?.avatar" 
              :image="profile.avatar" 
              shape="circle" 
              size="large" 
              class="border border-slate-200 shadow-sm"
            />
            <Avatar 
              v-else-if="profile" 
              :label="profile.first_name ? profile.first_name[0] : 'C'" 
              shape="circle" 
              size="large" 
              class="bg-indigo-600 text-white border border-slate-200 shadow-sm"
            />
            <Button
              label="Back"
              icon="pi pi-arrow-left"
              link
              @click="$router.back()"
              class="!text-white hover:!text-white !no-underline !rounded-md !px-2 mb-2"
            />
            <div>
              <h1 class="text-2xl md:text-3xl font-bold m-0 text-slate-900">
                {{ profile?.name || 'Candidate Profile' }}
              </h1>
              <p class="text-slate-600 mt-1" v-if="profile">
                {{ profile.headline || 'No headline' }} • User #{{ profile.id }} • {{ [profile.city, profile.country_code].filter(Boolean).join(', ') || 'No location' }}
              </p>
            </div>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200">
            <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="fetchProfile" class="!bg-white !border-slate-300 !text-slate-700 !rounded-md !px-3 !py-2 hover:!bg-slate-100" />
            <Button label="Message" icon="pi pi-envelope" severity="secondary" outlined @click="message" :loading="messaging" :disabled="messaging" class="!bg-white !border-slate-300 !text-slate-700 !rounded-md !px-3 !py-2 hover:!bg-slate-100" />
            <Button label="Invite to Job" icon="pi pi-check" @click="invite" :loading="inviting" :disabled="inviting" class="!bg-green-600 !border-green-600 !rounded-md !px-3 !py-2 hover:!bg-green-700" />
            <a v-if="profile?.resume_url" :href="profile.resume_url" target="_blank" rel="noreferrer">
              <Button label="Download CV" icon="pi pi-file" outlined class="!bg-white !border-slate-300 !text-slate-700 !rounded-md !px-3 !py-2 hover:!bg-slate-100" />
            </a>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center py-12 text-slate-500">
        <i class="pi pi-spin pi-spinner text-4xl mb-3"></i>
        <div>Loading profile...</div>
      </div>
      
      <Message v-else-if="error" severity="error" :closable="false" class="mb-4">{{ error }}</Message>

      <div v-else-if="profile" class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-4 lg:col-span-3">
          <Card class="bg-white rounded-xl shadow-sm border border-slate-200 sticky top-6">
          <template #content>
            <div class="flex flex-col items-center text-center mb-4">
                <Avatar 
                    v-if="profile.avatar" 
                    :image="profile.avatar" 
                    size="xlarge" 
                    shape="circle" 
                    class="mb-3 w-6rem h-6rem border border-slate-200 shadow-sm"
                />
                <Avatar 
                    v-else 
                    :label="profile.first_name ? profile.first_name[0] : 'C'" 
                    size="xlarge" 
                    shape="circle" 
                    class="mb-3 w-6rem h-6rem text-3xl bg-indigo-600 text-white border border-slate-200 shadow-sm"
                />
              <div class="text-xl font-bold text-slate-900">{{ profile.name }}</div>
              <div class="text-slate-600">{{ profile.headline || 'No headline' }}</div>
            </div>

            <div class="my-3 border-t border-slate-200"></div>

            <div class="flex flex-col gap-3">
                <div>
                    <div class="text-xs text-slate-500 font-medium mb-1">Email</div>
                    <div class="text-slate-900 font-semibold break-all">{{ profile.email }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 font-medium mb-1">Phone</div>
                    <div class="text-slate-900 font-semibold">{{ profile.phone || 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 font-medium mb-1">Experience</div>
                    <div class="text-slate-900 font-semibold">{{ profile.years_experience }} years</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500 font-medium mb-1">Location</div>
                    <div class="text-slate-900 font-semibold">{{ profile.city }} {{ profile.country_code }}</div>
                </div>
                <div v-if="profile?.linkedin || profile?.website" class="pt-2">
                  <div class="text-xs text-slate-500 font-medium mb-1">Links</div>
                  <div class="flex flex-wrap gap-2">
                    <a v-if="profile.linkedin" :href="profile.linkedin" target="_blank" rel="noreferrer" class="text-blue-600 hover:text-blue-700 text-sm font-medium">LinkedIn</a>
                    <a v-if="profile.website" :href="profile.website" target="_blank" rel="noreferrer" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Website</a>
                  </div>
                </div>
            </div>
          </template>
        </Card>
        </div>

        <div class="md:col-span-8 lg:col-span-9">
          <div class="flex flex-col gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h3 class="text-lg font-bold text-slate-900 mb-3">Summary</h3>
              <p class="m-0 leading-7 text-slate-700">{{ profile.summary || 'No summary provided.' }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h3 class="text-lg font-bold text-slate-900 mb-3">Bio</h3>
              <p class="m-0 leading-7 text-slate-700">{{ profile.bio || 'No bio provided.' }}</p>
            </div>

            <div v-if="toArray(profile.skills).length" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h3 class="text-lg font-bold text-slate-900 mb-3">Skills</h3>
              <div class="flex flex-wrap gap-2">
                <Tag v-for="(s, i) in toArray(profile.skills)" :key="i" :value="s" class="!bg-indigo-50 !text-indigo-700 !border !border-indigo-100 !rounded-md" />
              </div>
            </div>

            <div v-if="Array.isArray(profile.experience) && profile.experience.length" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h3 class="text-lg font-bold text-slate-900 mb-3">Experience</h3>
              <div class="space-y-4">
                <div v-for="(x, i) in profile.experience" :key="i" class="flex items-start justify-between gap-4">
                  <div>
                    <div class="font-semibold text-slate-900">{{ x.title || x.role || 'Role' }}</div>
                    <div class="text-slate-600">{{ x.company || x.organization || 'Company' }}</div>
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ formatDate(x.start_date || x.start) }} - {{ x.end_date || x.end ? formatDate(x.end_date || x.end) : 'Present' }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="Array.isArray(profile.education) && profile.education.length" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
              <h3 class="text-lg font-bold text-slate-900 mb-3">Education</h3>
              <div class="space-y-3">
                <div v-for="(e, i) in profile.education" :key="i" class="flex items-start justify-between gap-4">
                  <div>
                    <div class="font-semibold text-slate-900">{{ e.degree || e.program || 'Degree' }}</div>
                    <div class="text-slate-600">{{ e.school || e.institution || 'Institution' }}</div>
                  </div>
                  <div class="text-sm text-slate-500">
                    {{ formatDate(e.start_date || e.start) }} - {{ e.end_date || e.end ? formatDate(e.end_date || e.end) : 'Present' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-16 text-slate-500 bg-white rounded-xl border border-dashed border-slate-200">Candidate not found.</div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* No custom styles needed */
</style>
