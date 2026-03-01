<template>
  <Card class="!border-2 !border-blue-200 !bg-blue-50/50 !shadow-lg">
    <template #title>
      <div class="flex items-center gap-3">
        <i class="pi pi-lock text-2xl text-blue-600"></i>
        <div>
          <div class="text-lg font-bold text-slate-900">Resume & Documents Locked</div>
          <div class="text-sm text-slate-600 font-normal">Purchase document access to view</div>
        </div>
      </div>
    </template>
    <template #content>
      <div class="space-y-4">
        <div class="bg-white rounded-xl p-4 border border-blue-200">
          <div class="text-sm text-slate-700 mb-3">
            <p class="mb-2">Your subscription unlocks:</p>
            <ul class="space-y-1 ml-4">
              <li class="flex items-center gap-2">
                <i class="pi pi-check text-green-600 text-xs"></i>
                <span>Messaging candidates</span>
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-check text-green-600 text-xs"></i>
                <span>Scheduling interviews</span>
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-check text-green-600 text-xs"></i>
                <span>Hiring workflows</span>
              </li>
            </ul>
          </div>
          <div class="text-sm text-slate-700 pt-3 border-t border-slate-200">
            <p class="mb-2 font-semibold">Document access unlocks:</p>
            <ul class="space-y-1 ml-4">
              <li class="flex items-center gap-2">
                <i class="pi pi-file-pdf text-blue-600 text-xs"></i>
                <span>Resume download</span>
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-file text-blue-600 text-xs"></i>
                <span>All attachments (licenses, certificates, IDs)</span>
              </li>
              <li class="flex items-center gap-2">
                <i class="pi pi-eye text-blue-600 text-xs"></i>
                <span>Full profile details</span>
              </li>
            </ul>
          </div>
        </div>

        <div v-if="pricing" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm text-slate-600">One-time payment per candidate</div>
            <div class="text-3xl font-bold text-slate-900">
              {{ pricing.per_applicant.formatted }}
            </div>
          </div>
          <Button 
            label="Purchase Document Access" 
            icon="pi pi-unlock" 
            @click="purchaseAccess"
            class="w-full !rounded-lg !bg-gradient-to-r !from-blue-600 !to-indigo-600 hover:!from-blue-700 hover:!to-indigo-700"
            :loading="purchasing"
            :disabled="purchasing"
          />
          <div class="text-xs text-slate-500 text-center mt-2">
            Lifetime access to this candidate's documents
          </div>
        </div>

        <div v-else-if="pricingError" class="text-center py-4">
          <i class="pi pi-exclamation-circle text-2xl text-red-500 mb-2"></i>
          <p class="text-sm text-red-600">{{ pricingError }}</p>
          <Button 
            label="Retry" 
            icon="pi pi-refresh" 
            @click="fetchPricing"
            class="mt-3"
            size="small"
            outlined
          />
        </div>

        <div v-else class="text-center py-4">
          <i class="pi pi-spin pi-spinner text-2xl text-slate-400"></i>
          <p class="text-sm text-slate-500 mt-2">Loading pricing...</p>
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import api from '@/lib/api'
import Swal from 'sweetalert2'

const props = defineProps({
  applicantId: { type: Number, required: true },
  applicationId: { type: Number, default: null },
})

const emit = defineEmits(['purchased'])

const pricing = ref(null)
const purchasing = ref(false)
const pricingError = ref(null)

async function fetchPricing() {
  try {
    console.log('Fetching document access pricing...')
    const res = await api.get('/api/document-access/pricing')
    console.log('Pricing response:', res)
    pricing.value = res.data?.data || res.data
    console.log('Pricing value:', pricing.value)
  } catch (e) {
    console.error('Failed to fetch pricing:', e)
    pricingError.value = e?.response?.data?.message || e?.message || 'Failed to load pricing'
  }
}

async function purchaseAccess() {
  const result = await Swal.fire({
    title: 'Purchase Document Access?',
    html: `
      <p class="text-slate-600 mb-3">You will be charged <strong>${pricing.value.per_applicant.formatted}</strong> for lifetime access to this candidate's resume and documents.</p>
      <p class="text-sm text-slate-500">This is a one-time payment per candidate.</p>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Purchase Now',
    cancelButtonText: 'Cancel',
    customClass: {
      confirmButton: 'swal2-confirm',
      cancelButton: 'swal2-cancel',
    },
  })

  if (!result.isConfirmed) return

  purchasing.value = true
  try {
    const payload = {
      applicant_id: props.applicantId,
      application_id: props.applicationId,
    }
    
    await api.post('/api/document-access/purchase', payload)
    
    await Swal.fire({
      icon: 'success',
      title: 'Access Granted!',
      text: 'You now have full access to this candidate\'s resume and documents.',
      timer: 2000,
      showConfirmButton: false,
    })

    emit('purchased')
  } catch (e) {
    const msg = e?.response?.data?.message || e?.__payload?.message || e?.message || 'Purchase failed'
    await Swal.fire({
      icon: 'error',
      title: 'Purchase Failed',
      text: msg,
    })
  } finally {
    purchasing.value = false
  }
}

onMounted(() => {
  fetchPricing()
})
</script>
