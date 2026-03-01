<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
      </div>

      <!-- Locked State -->
      <div v-else-if="locked" class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
          <i class="pi pi-lock text-6xl text-gray-300 mb-4"></i>
          <h2 class="text-2xl font-bold text-gray-900 mb-3">Subscription Required</h2>
          <p class="text-gray-600 mb-6">
            Subscribe to unlock full applicant profiles, contact information, and resumes.
          </p>
          
          <!-- Preview Card (Blurred) -->
          <div class="relative mb-6">
            <div class="absolute inset-0 backdrop-blur-md bg-white/50 z-10 rounded-lg"></div>
            <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
              <div class="flex items-center gap-4 mb-4">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-400 to-purple-500"></div>
                <div class="text-left">
                  <div class="h-6 w-32 bg-gray-300 rounded mb-2"></div>
                  <div class="h-4 w-48 bg-gray-200 rounded"></div>
                </div>
              </div>
              <div class="space-y-3">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                <div class="h-4 bg-gray-200 rounded w-2/3"></div>
              </div>
            </div>
          </div>

          <button @click="goToPlans" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-lg">
            <i class="pi pi-unlock mr-2"></i>
            Subscribe Now
          </button>
        </div>
      </div>

      <!-- Full Applicant Details -->
      <div v-else-if="applicant" class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
          <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-6">
              <img v-if="applicant.avatar_url" :src="applicant.avatar_url" 
                   class="w-24 h-24 rounded-full object-cover border-4 border-blue-100" />
              <div v-else class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-3xl font-bold">
                {{ applicant.full_name?.charAt(0) || '?' }}
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ applicant.full_name }}</h1>
                <p v-if="applicant.headline" class="text-lg text-gray-600 mb-3">{{ applicant.headline }}</p>
                <div class="flex gap-4 text-sm text-gray-600">
                  <span v-if="applicant.location?.city">
                    <i class="pi pi-map-marker mr-1"></i>
                    {{ applicant.location.city }}, {{ applicant.location.country }}
                  </span>
                  <span v-if="applicant.years_experience">
                    <i class="pi pi-briefcase mr-1"></i>
                    {{ applicant.years_experience }} years
                  </span>
                </div>
              </div>
            </div>
            <button @click="goBack" class="px-4 py-2 text-gray-600 hover:text-gray-900">
              <i class="pi pi-arrow-left mr-2"></i>
              Back
            </button>
          </div>

          <!-- Contact Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6 border-t border-gray-200">
            <div class="flex items-center gap-3">
              <i class="pi pi-envelope text-blue-600 text-xl"></i>
              <div>
                <p class="text-xs text-gray-500">Email</p>
                <p class="font-semibold">{{ applicant.email }}</p>
              </div>
            </div>
            <div v-if="applicant.phone" class="flex items-center gap-3">
              <i class="pi pi-phone text-blue-600 text-xl"></i>
              <div>
                <p class="text-xs text-gray-500">Phone</p>
                <p class="font-semibold">{{ applicant.phone }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div v-if="applicant.summary" class="bg-white rounded-xl shadow-lg p-8 mb-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">About</h2>
          <p class="text-gray-700 leading-relaxed">{{ applicant.summary }}</p>
        </div>

        <!-- Documents & Resume -->
        <div v-if="applicant.documents && applicant.documents.length > 0" class="bg-white rounded-xl shadow-lg p-8 mb-6">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Documents</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="doc in applicant.documents" :key="doc.id" 
                 class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
              <div class="flex items-center gap-3">
                <i class="pi pi-file text-2xl text-blue-600"></i>
                <div>
                  <p class="font-semibold text-gray-900">{{ doc.name }}</p>
                  <p class="text-xs text-gray-500">{{ doc.type }} • {{ formatFileSize(doc.size) }}</p>
                </div>
              </div>
              <div class="flex gap-2">
                <button @click="viewDocument(doc)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                  <i class="pi pi-eye"></i>
                </button>
                <button @click="downloadDocument(doc)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                  <i class="pi pi-download"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="flex gap-4">
            <button class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
              <i class="pi pi-calendar mr-2"></i>
              Schedule Interview
            </button>
            <button class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
              <i class="pi pi-check mr-2"></i>
              Shortlist
            </button>
            <button class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:border-gray-400 font-semibold">
              <i class="pi pi-comment mr-2"></i>
              Message
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppLayout from '@/Components/AppLayout.vue';
import { http } from '@/lib/http';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const loading = ref(true);
const locked = ref(false);
const applicant = ref(null);

const loadApplicant = async () => {
  try {
    loading.value = true;
    const applicantId = route.params.id;
    const response = await http.get(`/applicants/${applicantId}`);
    
    if (response.data.locked) {
      locked.value = true;
    } else {
      applicant.value = response.data.applicant;
    }
  } catch (error) {
    if (error.response?.status === 403) {
      locked.value = true;
      Swal.fire({
        title: 'Subscription Required',
        text: 'You need an active subscription to view applicant details.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Choose Plan',
        cancelButtonText: 'Go Back',
        confirmButtonColor: '#2563eb',
      }).then((result) => {
        if (result.isConfirmed) {
          goToPlans();
        } else {
          goBack();
        }
      });
    } else {
      console.error('Failed to load applicant:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load applicant details.',
      });
    }
  } finally {
    loading.value = false;
  }
};

const downloadDocument = async (doc) => {
  try {
    const response = await http.get(`/documents/${doc.id}/download`, {
      responseType: 'blob'
    });
    
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', doc.name);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    if (error.response?.status === 403) {
      Swal.fire({
        icon: 'warning',
        title: 'Subscription Required',
        text: 'An active subscription is required to download documents.',
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to download document.',
      });
    }
  }
};

const viewDocument = async (doc) => {
  window.open(`/api/documents/${doc.id}/stream`, '_blank');
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const goToPlans = () => {
  router.push('/billing/plans');
};

const goBack = () => {
  router.back();
};

onMounted(() => {
  loadApplicant();
});
</script>
