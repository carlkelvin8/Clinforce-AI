<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-2xl mx-auto text-center">
        <!-- Success Animation -->
        <div class="mb-8">
          <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="pi pi-check text-5xl text-green-600"></i>
          </div>
          <h1 class="text-4xl font-bold text-gray-900 mb-4">Subscription Activated!</h1>
          <p class="text-xl text-gray-600 mb-8">
            Welcome to Clinforce AI Premium. You now have full access to all applicant profiles.
          </p>
        </div>

        <!-- What's Unlocked -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">What's Now Unlocked</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
            <div class="flex items-start gap-3">
              <i class="pi pi-check-circle text-green-600 text-xl mt-1"></i>
              <div>
                <h4 class="font-semibold text-gray-900">Full Applicant Profiles</h4>
                <p class="text-sm text-gray-600">View complete candidate information</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <i class="pi pi-check-circle text-green-600 text-xl mt-1"></i>
              <div>
                <h4 class="font-semibold text-gray-900">Contact Details</h4>
                <p class="text-sm text-gray-600">Email and phone access</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <i class="pi pi-check-circle text-green-600 text-xl mt-1"></i>
              <div>
                <h4 class="font-semibold text-gray-900">Resume Downloads</h4>
                <p class="text-sm text-gray-600">Download and review resumes</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <i class="pi pi-check-circle text-green-600 text-xl mt-1"></i>
              <div>
                <h4 class="font-semibold text-gray-900">Direct Messaging</h4>
                <p class="text-sm text-gray-600">Connect with candidates</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button @click="viewApplicants" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            <i class="pi pi-users mr-2"></i>
            View Applicants
          </button>
          <button @click="manageBilling" class="px-8 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:border-gray-400 font-semibold">
            <i class="pi pi-cog mr-2"></i>
            Manage Billing
          </button>
        </div>

        <!-- Receipt Info -->
        <div class="mt-8 text-sm text-gray-600">
          <p>A receipt has been sent to your email address.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/Components/AppLayout.vue';
import { http } from '@/lib/http';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();

const processSuccess = async () => {
  const sessionId = route.query.session_id;
  
  if (!sessionId) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid Session',
      text: 'No session ID found.',
    }).then(() => {
      router.push('/billing/plans');
    });
    return;
  }

  try {
    await http.post('/stripe/checkout/success', {
      session_id: sessionId
    });
  } catch (error) {
    console.error('Failed to process success:', error);
  }
};

const viewApplicants = () => {
  router.push('/employer/applicants');
};

const manageBilling = () => {
  router.push('/billing/portal');
};

onMounted(() => {
  processSuccess();
});
</script>
