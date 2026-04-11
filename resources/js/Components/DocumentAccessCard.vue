<template>
  <Card class="!border !border-blue-200 !bg-blue-50/40 !shadow-sm">
    <template #title>
      <div class="flex items-center gap-2">
        <i class="pi pi-lock text-base text-blue-500"></i>
        <span class="text-sm font-bold text-slate-900">Resume & Documents Locked</span>
      </div>
    </template>
    <template #content>
      <div class="space-y-2.5">
        <!-- What's included — compact single line -->
        <div class="flex items-center gap-3 text-xs text-slate-600 flex-wrap">
          <span class="flex items-center gap-1"><i class="pi pi-file-pdf text-blue-500 text-[10px]"></i> Resume</span>
          <span class="flex items-center gap-1"><i class="pi pi-file text-blue-500 text-[10px]"></i> Attachments</span>
          <span class="flex items-center gap-1"><i class="pi pi-eye text-blue-500 text-[10px]"></i> Full profile</span>
        </div>

        <!-- Pricing + Purchase -->
        <div v-if="pricing" class="flex items-center justify-between gap-3 bg-white rounded-lg px-3 py-2 border border-blue-100">
          <div>
            <div class="text-xs text-slate-500">One-time fee</div>
            <div class="text-base font-bold text-slate-900">{{ pricing.per_applicant.formatted }}</div>
          </div>
          <Button
            label="Unlock Access"
            icon="pi pi-unlock"
            @click="purchaseAccess"
            size="small"
            class="!rounded-lg !text-xs"
            :loading="purchasing"
            :disabled="purchasing"
          />
        </div>

        <div v-else-if="pricingError" class="text-center py-2">
          <p class="text-xs text-red-500">{{ pricingError }}</p>
          <Button label="Retry" icon="pi pi-refresh" @click="fetchPricing" class="mt-1" size="small" text />
        </div>

        <div v-else class="text-center py-2">
          <i class="pi pi-spin pi-spinner text-sm text-slate-400"></i>
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
    const res = await api.get('/api/document-access/pricing')
    pricing.value = res.data?.data || res.data
  } catch (e) {
    pricingError.value = e?.response?.data?.message || e?.message || 'Failed to load pricing'
  }
}

async function purchaseAccess() {
  const result = await Swal.fire({
    title: 'Purchase Document Access?',
    html: `<p class="text-slate-600 mb-3">One-time charge of <strong>${pricing.value.per_applicant.formatted}</strong> for lifetime access to this candidate's documents.</p>`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Purchase',
    cancelButtonText: 'Cancel',
  })

  if (!result.isConfirmed) return

  purchasing.value = true
  try {
    await api.post('/api/document-access/purchase', {
      applicant_id: props.applicantId,
      application_id: props.applicationId,
    })
    await Swal.fire({ icon: 'success', title: 'Access Granted!', timer: 2000, showConfirmButton: false })
    emit('purchased')
  } catch (e) {
    const msg = e?.response?.data?.message || e?.__payload?.message || e?.message || 'Purchase failed'
    await Swal.fire({ icon: 'error', title: 'Failed', text: msg })
  } finally {
    purchasing.value = false
  }
}

onMounted(() => { fetchPricing() })
</script>
