<template>
  <div v-if="!isVerified && user?.email && !dismissed" class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-lg shadow-sm">
    <div class="flex items-start gap-3">
      <div class="flex-shrink-0">
        <i class="pi pi-exclamation-triangle text-amber-600 text-xl"></i>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="text-sm font-bold text-amber-900 mb-1">Email Verification Required</h3>
        <p class="text-sm text-amber-800 mb-3">
          Please verify your email address <strong>{{ user.email }}</strong> to unlock all features and ensure account security.
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            @click="resendVerification"
            :disabled="resending || cooldown > 0"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <i v-if="resending" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-envelope"></i>
            <span v-if="cooldown > 0">Resend in {{ cooldown }}s</span>
            <span v-else-if="resending">Sending...</span>
            <span v-else>Resend Verification Email</span>
          </button>
          <button
            @click="checkVerification"
            :disabled="checking"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-amber-300 text-amber-900 text-sm font-semibold rounded-lg hover:bg-amber-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <i v-if="checking" class="pi pi-spin pi-spinner"></i>
            <i v-else class="pi pi-refresh"></i>
            <span v-if="checking">Checking...</span>
            <span v-else>I've Verified</span>
          </button>
        </div>
        <p v-if="message" class="text-sm mt-2" :class="messageType === 'success' ? 'text-green-700' : 'text-red-700'">
          {{ message }}
        </p>
      </div>
      <button
        @click="dismiss"
        class="flex-shrink-0 text-amber-600 hover:text-amber-800 transition-colors"
        title="Dismiss"
      >
        <i class="pi pi-times"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { getCachedUser, me } from '@/lib/auth';
import api from '@/lib/api';

const user = ref(getCachedUser());
const isVerified = computed(() => !!user.value?.email_verified_at);
const dismissed = ref(false);

const resending = ref(false);
const checking = ref(false);
const cooldown = ref(0);
const message = ref('');
const messageType = ref(''); // 'success' | 'error'

let cooldownInterval = null;

async function resendVerification() {
  if (resending.value || cooldown.value > 0) return;
  
  resending.value = true;
  message.value = '';
  
  try {
    await api.post('/auth/resend-verification');
    message.value = 'Verification email sent! Please check your inbox.';
    messageType.value = 'success';
    
    // Start cooldown
    cooldown.value = 60;
    cooldownInterval = setInterval(() => {
      cooldown.value--;
      if (cooldown.value <= 0) {
        clearInterval(cooldownInterval);
      }
    }, 1000);
  } catch (e) {
    message.value = e?.response?.data?.message || 'Failed to send verification email.';
    messageType.value = 'error';
  } finally {
    resending.value = false;
  }
}

async function checkVerification() {
  if (checking.value) return;
  
  checking.value = true;
  message.value = '';
  
  try {
    await me(); // Refresh user data
    user.value = getCachedUser();
    
    if (isVerified.value) {
      message.value = 'Email verified successfully!';
      messageType.value = 'success';
      setTimeout(() => {
        dismissed.value = true;
      }, 2000);
    } else {
      message.value = 'Email not yet verified. Please check your inbox and click the verification link.';
      messageType.value = 'error';
    }
  } catch (e) {
    message.value = 'Failed to check verification status.';
    messageType.value = 'error';
  } finally {
    checking.value = false;
  }
}

function dismiss() {
  dismissed.value = true;
  // Store dismissal in session storage (will show again on page reload)
  sessionStorage.setItem('email_verification_dismissed', 'true');
}

onMounted(() => {
  // Check if user dismissed the banner in this session
  if (sessionStorage.getItem('email_verification_dismissed') === 'true') {
    dismissed.value = true;
  }
});

onUnmounted(() => {
  if (cooldownInterval) {
    clearInterval(cooldownInterval);
  }
});
</script>
