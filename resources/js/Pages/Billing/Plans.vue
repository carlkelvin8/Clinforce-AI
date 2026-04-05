<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
        <p class="text-xl text-gray-600">Unlock full access to candidate profiles and resumes</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
      </div>

      <!-- Plans Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <div v-for="plan in plans" :key="plan.id" 
             class="bg-white rounded-2xl shadow-xl p-8 border-2 transition-all hover:scale-105"
             :class="plan.is_popular ? 'border-blue-500 relative' : 'border-gray-200'">
          
          <!-- Popular Badge -->
          <div v-if="plan.is_popular" class="absolute -top-4 left-1/2 transform -translate-x-1/2">
            <span class="px-4 py-1 bg-blue-600 text-white text-sm font-semibold rounded-full">
              Most Popular
            </span>
          </div>

          <div class="text-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ plan.name }}</h3>
            <div class="text-4xl font-bold text-blue-600 mb-2">
              ${{ (plan.price_cents / 100).toFixed(2) }}
              <span class="text-lg text-gray-500">/ {{ plan.duration_months }} mo</span>
            </div>
          </div>

          <!-- Features -->
          <ul class="space-y-3 mb-8">
            <li class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>{{ plan.job_post_limit === -1 ? 'Unlimited' : plan.job_post_limit }} job posts</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>Full candidate profiles</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>Resume downloads</span>
            </li>
            <li class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>Contact information access</span>
            </li>
            <li v-if="plan.ai_screening_enabled" class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>AI-powered screening</span>
            </li>
            <li v-if="plan.analytics_enabled" class="flex items-start gap-2">
              <i class="pi pi-check text-green-600 mt-1"></i>
              <span>Advanced analytics</span>
            </li>
          </ul>

          <!-- CTA Button -->
          <button @click="selectPlan(plan)" 
                  :disabled="processing"
                  class="w-full py-3 rounded-lg font-semibold transition-colors"
                  :class="plan.is_popular 
                    ? 'bg-blue-600 text-white hover:bg-blue-700' 
                    : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
            <span v-if="processing && selectedPlanId === plan.id">
              <i class="pi pi-spin pi-spinner mr-2"></i>
              Processing...
            </span>
            <span v-else>
              Choose {{ plan.name }}
            </span>
          </button>
        </div>
      </div>

      <!-- Features Comparison -->
      <div class="mt-16 max-w-4xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">All Plans Include</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="flex items-start gap-3">
            <i class="pi pi-shield text-blue-600 text-2xl"></i>
            <div>
              <h4 class="font-semibold text-gray-900 mb-1">Secure & Private</h4>
              <p class="text-gray-600 text-sm">Your data is encrypted and protected</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <i class="pi pi-users text-blue-600 text-2xl"></i>
            <div>
              <h4 class="font-semibold text-gray-900 mb-1">Unlimited Candidates</h4>
              <p class="text-gray-600 text-sm">View as many profiles as you need</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <i class="pi pi-comments text-blue-600 text-2xl"></i>
            <div>
              <h4 class="font-semibold text-gray-900 mb-1">Direct Messaging</h4>
              <p class="text-gray-600 text-sm">Communicate with candidates</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <i class="pi pi-headphones text-blue-600 text-2xl"></i>
            <div>
              <h4 class="font-semibold text-gray-900 mb-1">24/7 Support</h4>
              <p class="text-gray-600 text-sm">We're here to help anytime</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Money Back Guarantee -->
      <div class="mt-12 text-center">
        <p class="text-gray-600">
          <i class="pi pi-shield text-green-600 mr-2"></i>
          30-day money-back guarantee • Cancel anytime
        </p>
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
const processing = ref(false);
const plans = ref([]);
const selectedPlanId = ref(null);

const loadPlans = async () => {
  try {
    loading.value = true;
    const response = await http.get('/plans');
    plans.value = response.data.map(plan => ({
      ...plan,
      is_popular: plan.name.toLowerCase().includes('professional') || plan.name.toLowerCase().includes('pro')
    }));
  } catch (error) {
    console.error('Failed to load plans:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to load plans. Please try again.',
    });
  } finally {
    loading.value = false;
  }
};

const selectPlan = async (plan) => {
  try {
    processing.value = true;
    selectedPlanId.value = plan.id;

    const response = await http.post('/stripe/checkout', {
      plan_id: plan.id
    });

    // Redirect to Stripe Checkout
    window.location.href = response.data.url;
  } catch (error) {
    console.error('Checkout failed:', error);
    Swal.fire({
      icon: 'error',
      title: 'Checkout Failed',
      text: error.response?.data?.message || 'Failed to create checkout session. Please try again.',
    });
    processing.value = false;
    selectedPlanId.value = null;
  }
};

onMounted(() => {
  loadPlans();
});
</script>
