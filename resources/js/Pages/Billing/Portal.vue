<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Billing & Subscription</h1>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
        </div>

        <!-- Current Subscription -->
        <div v-else-if="subscription" class="bg-white rounded-xl shadow-lg p-8 mb-6">
          <div class="flex items-start justify-between mb-6">
            <div>
              <h2 class="text-2xl font-bold text-gray-900 mb-2">Current Plan</h2>
              <p class="text-gray-600">Manage your subscription and billing information</p>
            </div>
            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg font-semibold">
              {{ subscription.status }}
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <p class="text-sm text-gray-500 mb-1">Plan</p>
              <p class="text-lg font-semibold text-gray-900">{{ subscription.plan_name }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 mb-1">Next Billing Date</p>
              <p class="text-lg font-semibold text-gray-900">
                {{ formatDate(subscription.expires_at) }}
              </p>
            </div>
          </div>

          <div class="flex gap-4">
            <button @click="openPortal" :disabled="processing" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold disabled:opacity-50">
              <i class="pi pi-external-link mr-2"></i>
              <span v-if="processing">Opening...</span>
              <span v-else>Manage Subscription</span>
            </button>
            <button @click="confirmCancel" 
                    class="px-6 py-3 border-2 border-red-300 text-red-600 rounded-lg hover:border-red-400 font-semibold">
              <i class="pi pi-times mr-2"></i>
              Cancel Subscription
            </button>
          </div>
        </div>

        <!-- No Subscription -->
        <div v-else class="bg-white rounded-xl shadow-lg p-8 text-center">
          <i class="pi pi-info-circle text-6xl text-gray-300 mb-4"></i>
          <h2 class="text-2xl font-bold text-gray-900 mb-3">No Active Subscription</h2>
          <p class="text-gray-600 mb-6">
            Subscribe to unlock full access to applicant profiles and resumes.
          </p>
          <button @click="goToPlans" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            <i class="pi pi-shopping-cart mr-2"></i>
            View Plans
          </button>
        </div>

        <!-- Billing History -->
        <div class="bg-white rounded-xl shadow-lg p-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Billing History</h2>
          <p class="text-gray-600">
            View your complete billing history in the Stripe Customer Portal.
          </p>
        </div>
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
const subscription = ref(null);

const loadSubscription = async () => {
  try {
    loading.value = true;
    const response = await http.get('/subscriptions');
    
    if (response.data && response.data.length > 0) {
      const active = response.data.find(sub => 
        sub.status === 'active' || sub.status === 'trialing'
      );
      
      if (active) {
        subscription.value = {
          id: active.id,
          status: active.status,
          plan_name: active.plan?.name || 'Unknown Plan',
          expires_at: active.current_period_end || active.end_at,
          stripe_subscription_id: active.stripe_subscription_id,
        };
      }
    }
  } catch (error) {
    console.error('Failed to load subscription:', error);
  } finally {
    loading.value = false;
  }
};

const openPortal = async () => {
  try {
    processing.value = true;
    const response = await http.post('/stripe/portal');
    window.location.href = response.data.url;
  } catch (error) {
    console.error('Failed to open portal:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Failed to open billing portal.',
    });
    processing.value = false;
  }
};

const confirmCancel = () => {
  Swal.fire({
    title: 'Cancel Subscription?',
    text: 'You will lose access to applicant profiles at the end of your billing period.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Cancel',
    cancelButtonText: 'Keep Subscription',
    confirmButtonColor: '#dc2626',
  }).then((result) => {
    if (result.isConfirmed) {
      cancelSubscription();
    }
  });
};

const cancelSubscription = async () => {
  try {
    await http.post(`/subscriptions/${subscription.value.id}/cancel`);
    
    Swal.fire({
      icon: 'success',
      title: 'Subscription Canceled',
      text: 'Your subscription has been canceled. You will have access until the end of your billing period.',
    });
    
    loadSubscription();
  } catch (error) {
    console.error('Failed to cancel subscription:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Failed to cancel subscription. Please try again.',
    });
  }
};

const goToPlans = () => {
  router.push('/billing/plans');
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
};

onMounted(() => {
  loadSubscription();
});
</script>
