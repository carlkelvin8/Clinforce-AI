<template>
  <Transition name="slide-up">
    <div v-if="isVisible" class="fixed bottom-6 right-6 z-[900] max-w-sm bg-white rounded-3xl shadow-[0_20px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 p-6 animate-float">
      <button @click="closeCard" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
        <i class="pi pi-times text-[10px]"></i>
      </button>
      
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
          <i class="pi pi-gift text-xl"></i>
        </div>
        <div>
          <h5 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-1">Referral Program</h5>
          <p class="text-xs font-medium text-slate-500 leading-relaxed mb-4">
            Invite a colleague and get 1 month of premium features for free!
          </p>
          <RouterLink :to="{ name: 'auth.register' }" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:text-blue-700 transition-colors flex items-center gap-1 group">
            Invite Now
            <i class="pi pi-arrow-right group-hover:translate-x-1 transition-transform"></i>
          </RouterLink>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';

const isVisible = ref(false);

const closeCard = () => {
  isVisible.value = false;
  sessionStorage.setItem('sticky_marketing_seen', 'true');
};

onMounted(() => {
  isVisible.value = false;
  
  setTimeout(() => {
    isVisible.value = true;
  }, 4000); // Show after 4 seconds for testing
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.6s cubic-bezier(0.22, 1, 0.36, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(100px) scale(0.9);
}

.slide-up-enter-to,
.slide-up-leave-from {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.animate-float {
  animation: float 5s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
</style>
