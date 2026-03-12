              <template>
  <Transition name="fade-scale">
    <div v-if="isVisible" class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm">
      <div class="relative w-full max-w-2xl bg-white rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.2)] border border-white/50 overflow-hidden transform transition-all duration-500">
        <!-- Close Button -->
        <button @click="closePopup" class="absolute top-6 right-6 z-20 w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white hover:bg-white hover:text-slate-900 transition-all flex items-center justify-center shadow-lg group">
          <i class="pi pi-times group-hover:rotate-90 transition-transform"></i>
        </button>

        <div class="grid md:grid-cols-2">
          <!-- Left side with image -->
          <div class="relative h-64 md:h-auto overflow-hidden">
            <img :src="bannerImg" alt="Marketing" class="absolute inset-0 w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/40 to-indigo-600/40 mix-blend-multiply"></div>
            <div class="absolute inset-0 flex flex-col justify-end p-8 text-white">
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/20 text-[10px] font-black uppercase tracking-[0.2em] mb-4 w-fit">
                Exclusive Offer
              </div>
              <h3 class="text-3xl font-black leading-tight tracking-tight">Level up your <br/>clinical hiring.</h3>
            </div>
          </div>

          <!-- Right side with content -->
          <div class="p-10 flex flex-col justify-center">
            <h4 class="text-2xl font-black text-slate-900 mb-4 tracking-tight leading-tight">Get 20% off your first 3 months.</h4>
            <p class="text-slate-500 font-medium mb-8 leading-relaxed">
              Experience the power of AI-driven clinical recruitment. Join the thousands of healthcare providers who are filling shifts faster than ever.
            </p>

            <div class="space-y-4">
              <RouterLink :to="{ name: 'auth.register' }" @click="closePopup" class="w-full inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-blue-600 text-white font-black text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-100 hover:shadow-blue-200 active:scale-[0.98]">
                Claim Offer Now
              </RouterLink>
              <button @click="closePopup" class="w-full text-sm font-bold text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                Maybe later
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import bannerImg from '../../assets/banner.png';

const isVisible = ref(false);

const closePopup = () => {
  isVisible.value = false;
  sessionStorage.setItem('marketing_popup_seen', 'true');
};

onMounted(() => {
  // Always show in development or if not seen in session
  isVisible.value = false;
  
  setTimeout(() => {
    isVisible.value = true;
  }, 2000); // Show after 2 seconds for testing
});
</script>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: all 0.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}

.fade-scale-enter-to,
.fade-scale-leave-from {
  opacity: 1;
  transform: scale(1) translateY(0);
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out both;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
