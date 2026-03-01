<template>
  <div v-if="!hasSubscription" class="bg-gradient-to-r from-blue-50 to-sky-50 border-2 border-blue-300 rounded-xl p-4 mb-6">
    <div class="flex items-start gap-4">
      <div class="flex-shrink-0">
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
          <i class="pi pi-exclamation-triangle text-blue-600 text-xl"></i>
        </div>
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-bold text-slate-900 mb-1">Subscription Required</h3>
        <p class="text-sm text-slate-700 mb-3">
          Subscribe to unlock messaging, interview scheduling, and hiring workflows.
        </p>
        <div class="flex flex-wrap gap-2">
          <Button 
            label="View Plans" 
            icon="pi pi-arrow-right" 
            @click="$router.push({ name: 'employer.billing' })"
            class="!rounded-md !bg-blue-600 hover:!bg-blue-700"
            size="small"
          />
          <Button 
            label="Learn More" 
            icon="pi pi-info-circle" 
            @click="showInfo"
            class="!rounded-md"
            outlined
            size="small"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Swal from 'sweetalert2'

const props = defineProps({
  subscription: { type: Object, default: null },
})

const router = useRouter()

const hasSubscription = computed(() => {
  return props.subscription && props.subscription.status === 'active'
})

function showInfo() {
  Swal.fire({
    title: 'Subscription Benefits',
    html: `
      <div class="text-left space-y-3">
        <div>
          <h4 class="font-semibold text-slate-900 mb-2">✅ Included with Subscription:</h4>
          <ul class="space-y-1 ml-4 text-sm text-slate-700">
            <li>• Message candidates directly</li>
            <li>• Schedule and manage interviews</li>
            <li>• Hire candidates and send offers</li>
            <li>• Manage application statuses</li>
          </ul>
        </div>
        <div class="pt-3 border-t border-slate-200">
          <h4 class="font-semibold text-slate-900 mb-2">📄 Separate Purchase Required:</h4>
          <ul class="space-y-1 ml-4 text-sm text-slate-700">
            <li>• Resume downloads</li>
            <li>• Document access (licenses, certificates)</li>
            <li>• Full profile details</li>
          </ul>
          <p class="text-xs text-slate-500 mt-2">One-time payment per candidate</p>
        </div>
      </div>
    `,
    icon: 'info',
    confirmButtonText: 'Got it',
  })
}
</script>
