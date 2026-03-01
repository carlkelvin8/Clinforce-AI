<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Applicants</h1>
        
        <!-- Subscription Status Badge -->
        <div v-if="subscriptionStatus" class="flex items-center gap-3">
          <span v-if="subscriptionStatus.has_subscription" class="px-4 py-2 bg-green-100 text-green-800 rounded-lg">
            <i class="pi pi-check-circle mr-2"></i>
            {{ subscriptionStatus.plan_name }} - Active
          </span>
          <button v-else @click="showUpgradeModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="pi pi-lock mr-2"></i>
            Subscribe to Unlock
          </button>
        </div>
      </div>

      <!-- Subscription Required Banner -->
      <div v-if="!subscriptionStatus?.has_subscription" class="mb-6 p-6 bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl">
        <div class="flex items-start gap-4">
          <i class="pi pi-lock text-4xl text-blue-600"></i>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Unlock Full Applicant Profiles</h3>
            <p class="text-gray-700 mb-4">
              Subscribe now to view complete applicant details, contact information, resumes, and more.
            </p>
            <button @click="goToPlans" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
              View Plans & Pricing
            </button>
          </div>
        </div>
      </div>

      <!-- Applicants Grid -->
      <div v-if="loading" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
      </div>

      <div v-else-if="applicants.length === 0" class="text-center py-12">
        <i class="pi pi-users text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">No applicants found</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="applicant in applicants" :key="applicant.id" 
             class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow p-6 border border-gray-200"
             :class="{ 'relative overflow-hidden': applicant.locked }">
          
          <!-- Locked Overlay -->
          <div v-if="applicant.locked" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex items-center justify-center">
            <div class="text-center">
              <i class="pi pi-lock text-5xl text-gray-400 mb-3"></i>
              <p class="text-gray-600 font-semibold">Subscription Required</p>
            </div>
          </div>

          <!-- Applicant Preview -->
          <div class="flex items-start gap-4 mb-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xl font-bold">
              {{ applicant.initials || applicant.public_display_name?.charAt(0) || '?' }}
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-bold text-gray-900">
                {{ applicant.locked ? applicant.initials : (applicant.full_name || applicant.public_display_name) }}
              </h3>
              <p v-if="applicant.headline" class="text-sm text-gray-600 mt-1">{{ applicant.headline }}</p>
            </div>
          </div>

          <!-- Details -->
          <div class="space-y-2 mb-4">
            <div v-if="applicant.location" class="flex items-center text-sm text-gray-600">
              <i class="pi pi-map-marker mr-2"></i>
              {{ applicant.location }}
            </div>
            <div v-if="applicant.years_experience" class="flex items-center text-sm text-gray-600">
              <i class="pi pi-briefcase mr-2"></i>
              {{ applicant.years_experience }} years experience
            </div>
          </div>

          <!-- Action Button -->
          <button @click="viewApplicant(applicant)" 
                  class="w-full py-2 rounded-lg font-semibold transition-colors"
                  :class="applicant.locked 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-blue-600 text-white hover:bg-blue-700'">
            <i class="pi mr-2" :class="applicant.locked ? 'pi-lock' : 'pi-eye'"></i>
            {{ applicant.locked ? 'Locked' : 'View Details' }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-8 flex justify-center gap-2">
        <button v-for="page in pagination.last_page" :key="page"
                @click="loadPage(page)"
                class="px-4 py-2 rounded-lg"
                :class="page === pagination.current_page 
                  ? 'bg-blue-600 text-white' 
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
          {{ page }}
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppLayout from '@/Components/AppLayout.vue';
import { http } from '@/lib/http';
import Swal from 'sweetalert2';

const router = useRouter();
const loading = ref(true);
const applicants = ref([]);
const subscriptionStatus = ref(null);
const pagination = ref(null);

const loadApplicants = async (page = 1) => {
  try {
    loading.value = true;
    const response = await http.get(`/applicants?page=${page}`);
    applicants.value = response.data.applicants.data;
    subscriptionStatus.value = response.data.subscription_status;
    pagination.value = {
      current_page: response.data.applicants.current_page,
      last_page: response.data.applicants.last_page,
    };
  } catch (error) {
    console.error('Failed to load applicants:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load applicants. Please try again.',
    });
  } finally {
    loading.value = false;
  }
};

const viewApplicant = (applicant) => {
  if (applicant.locked) {
    showUpgradeModal();
  } else {
    router.push(`/employer/applicants/${applicant.id}`);
  }
};

const showUpgradeModal = () => {
  Swal.fire({
    title: 'Subscription Required',
    text: 'Subscribe to unlock applicant profiles and resumes.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Choose Plan',
    cancelButtonText: 'Maybe Later',
    confirmButtonColor: '#2563eb',
  }).then((result) => {
    if (result.isConfirmed) {
      goToPlans();
    }
  });
};

const goToPlans = () => {
  router.push('/billing/plans');
};

const loadPage = (page) => {
  loadApplicants(page);
};

onMounted(() => {
  loadApplicants();
});
</script>
