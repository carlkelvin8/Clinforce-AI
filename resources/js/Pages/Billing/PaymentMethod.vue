<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="max-w-4xl mx-auto mb-8">
        <div class="text-center">
          <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
            Payment Methods
          </h1>
          <p class="text-gray-600">Securely manage your payment information</p>
        </div>
      </div>

      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
          <!-- Premium Header Bar -->
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6">
            <div class="flex items-center justify-between text-white">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                  <i class="bi bi-credit-card-2-front text-2xl"></i>
                </div>
                <div>
                  <h2 class="text-xl font-semibold">Payment Information</h2>
                  <p class="text-blue-100 text-sm">Manage your billing details</p>
                </div>
              </div>
              <div class="text-right">
                <div class="text-xs text-blue-100 uppercase tracking-wide">Secured by</div>
                <div class="text-lg font-bold">Stripe</div>
              </div>
            </div>
          </div>

          <div class="p-8">
            <!-- Existing Payment Method -->
            <div v-if="hasPaymentMethod && !showAddForm" class="space-y-6">
              <!-- Success Banner -->
              <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-6">
                <div class="flex items-start gap-4">
                  <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-check-lg text-white text-2xl"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Payment method verified</h3>
                    <p class="text-sm text-gray-600">Your payment information is securely stored and ready to use</p>
                  </div>
                </div>
              </div>
              
              <!-- Payment Cards -->
              <div class="space-y-3">
                <div 
                  v-for="card in paymentMethods" 
                  :key="card.id" 
                  class="group relative bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden"
                >
                  <!-- Card Background Pattern -->
                  <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-400 to-purple-400 rounded-full blur-3xl"></div>
                  </div>
                  
                  <div class="relative z-10">
                    <div class="flex justify-between items-start mb-8">
                      <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center">
                          <i class="bi bi-credit-card text-white text-xl"></i>
                        </div>
                        <div>
                          <div class="text-xs text-gray-400 uppercase tracking-wider">Payment Card</div>
                          <div class="text-white font-semibold text-lg">{{ card.brand.toUpperCase() }}</div>
                        </div>
                      </div>
                      <button 
                        @click="removeCard(card.id)" 
                        class="opacity-0 group-hover:opacity-100 transition-opacity px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 text-red-300 rounded-lg text-sm font-medium"
                      >
                        <i class="bi bi-trash"></i> Remove
                      </button>
                    </div>
                    
                    <div class="space-y-4">
                      <div class="flex items-center gap-2 text-white text-2xl font-mono tracking-wider">
                        <span>••••</span>
                        <span>••••</span>
                        <span>••••</span>
                        <span class="font-bold">{{ card.last4 }}</span>
                      </div>
                      
                      <div class="flex justify-between items-end">
                        <div>
                          <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Expires</div>
                          <div class="text-white font-semibold">{{ String(card.exp_month).padStart(2, '0') }}/{{ card.exp_year }}</div>
                        </div>
                        <div class="text-right">
                          <div class="inline-flex items-center gap-1 px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-xs font-semibold">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>Default</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <button 
                @click="showAddForm = true" 
                class="w-full py-4 border-2 border-dashed border-gray-300 rounded-xl text-gray-600 hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 font-medium"
              >
                <i class="bi bi-plus-circle mr-2"></i> Add Another Card
              </button>
            </div>

            <!-- Add Payment Method Form -->
            <div v-if="!hasPaymentMethod || showAddForm" class="space-y-6">
              <!-- Info Banner -->
              <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-5">
                <div class="flex items-start gap-3">
                  <i class="bi bi-info-circle text-blue-600 text-xl mt-0.5"></i>
                  <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Secure Payment Setup</h4>
                    <p class="text-sm text-gray-600">Add a payment method to unlock premium features and manage your subscription seamlessly.</p>
                  </div>
                </div>
              </div>

              <form @submit.prevent="addPaymentMethod" class="space-y-6">
                <!-- Card Element Container -->
                <div class="space-y-2">
                  <label class="block text-sm font-semibold text-gray-700">
                    Card Information
                  </label>
                  <div class="relative">
                    <div 
                      id="card-element" 
                      class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100 transition-all duration-200 bg-white"
                    >
                      <!-- Stripe Card Element will be mounted here -->
                    </div>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                      <i class="bi bi-lock-fill text-gray-400"></i>
                    </div>
                  </div>
                  <div v-if="cardError" class="flex items-center gap-2 text-red-600 text-sm mt-2">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ cardError }}</span>
                  </div>
                </div>

                <!-- Test Card Info -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                  <div class="flex items-start gap-3">
                    <i class="bi bi-lightning-charge-fill text-amber-600 text-lg"></i>
                    <div class="flex-1">
                      <div class="font-semibold text-amber-900 text-sm mb-1">Test Mode Active</div>
                      <div class="text-xs text-amber-800 space-y-1">
                        <div>Use test card: <code class="bg-amber-100 px-2 py-0.5 rounded font-mono">4242 4242 4242 4242</code></div>
                        <div>Any future expiry date and any 3-digit CVC</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4">
                  <div class="flex items-start gap-3">
                    <i class="bi bi-x-circle-fill text-red-600 text-lg"></i>
                    <div class="flex-1 text-sm text-red-800">{{ error }}</div>
                  </div>
                </div>

                <!-- Success Message -->
                <div v-if="success" class="bg-green-50 border border-green-200 rounded-xl p-4">
                  <div class="flex items-start gap-3">
                    <i class="bi bi-check-circle-fill text-green-600 text-lg"></i>
                    <div class="flex-1 text-sm text-green-800">{{ success }}</div>
                  </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                  <button 
                    type="submit" 
                    class="flex-1 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="loading || !cardComplete"
                  >
                    <span v-if="loading" class="flex items-center justify-center gap-2">
                      <span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                      <span>Processing...</span>
                    </span>
                    <span v-else class="flex items-center justify-center gap-2">
                      <i class="bi bi-shield-check"></i>
                      <span>Add Payment Method</span>
                    </span>
                  </button>

                  <button 
                    v-if="hasPaymentMethod && showAddForm"
                    type="button" 
                    class="px-6 py-4 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-200"
                    @click="showAddForm = false"
                  >
                    Cancel
                  </button>
                </div>

                <!-- Security Badge -->
                <div class="flex items-center justify-center gap-6 pt-4 border-t border-gray-100">
                  <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="bi bi-shield-fill-check text-green-600"></i>
                    <span>256-bit SSL Encrypted</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="bi bi-lock-fill text-blue-600"></i>
                    <span>PCI DSS Compliant</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="bi bi-check-circle-fill text-green-600"></i>
                    <span>Powered by Stripe</span>
                  </div>
                </div>
              </form>
            </div>

            <!-- Continue to Subscription -->
            <div v-if="hasPaymentMethod && !showAddForm" class="mt-8 pt-6 border-t border-gray-100">
              <button 
                @click="goToBilling" 
                class="w-full py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2"
              >
                <span>Continue to Billing</span>
                <i class="bi bi-arrow-right-circle"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Trust Indicators -->
        <div class="mt-8 text-center">
          <p class="text-sm text-gray-500 mb-4">Your payment information is secure and encrypted</p>
          <div class="flex items-center justify-center gap-8 opacity-60">
            <div class="text-xs text-gray-400 font-semibold">STRIPE</div>
            <div class="text-xs text-gray-400 font-semibold">SSL SECURED</div>
            <div class="text-xs text-gray-400 font-semibold">PCI COMPLIANT</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '@/lib/api';
import Swal from 'sweetalert2';

export default {
  name: 'PaymentMethod',
  
  data() {
    return {
      stripe: null,
      cardElement: null,
      cardComplete: false,
      cardError: null,
      loading: false,
      error: null,
      success: null,
      hasPaymentMethod: false,
      paymentMethods: [],
      showAddForm: false,
    };
  },

  async mounted() {
    await this.loadStripe();
    await this.checkPaymentMethods();
  },

  methods: {
    async loadStripe() {
      try {
        if (!window.Stripe) {
          const script = document.createElement('script');
          script.src = 'https://js.stripe.com/v3/';
          script.async = true;
          document.head.appendChild(script);
          
          await new Promise((resolve) => {
            script.onload = resolve;
          });
        }

        const stripeKey = import.meta.env.VITE_STRIPE_KEY || 'pk_test_51T5nzI08ycH5tldfAb4SMYxZBcOc3ofzHAw0QLEtW7UuoTQVYT88H16uVpq7oDvIZeulsEj9t9n6vjEBGEV0Vscn00y44Grcvc';
        this.stripe = window.Stripe(stripeKey);

        const elements = this.stripe.elements();
        this.cardElement = elements.create('card', {
          style: {
            base: {
              fontSize: '16px',
              color: '#1f2937',
              fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
              '::placeholder': {
                color: '#9ca3af',
              },
            },
            invalid: {
              color: '#dc2626',
            },
          },
        });

        this.cardElement.mount('#card-element');

        this.cardElement.on('change', (event) => {
          this.cardComplete = event.complete;
          this.cardError = event.error ? event.error.message : null;
        });
      } catch (error) {
        console.error('Failed to load Stripe:', error);
        this.error = 'Failed to load payment form. Please refresh the page.';
      }
    },

    async checkPaymentMethods() {
      try {
        const { data } = await api.get('/payment-methods');
        this.hasPaymentMethod = data.data.has_payment_method;
        this.paymentMethods = data.data.payment_methods || [];
      } catch (error) {
        console.error('Failed to check payment methods:', error);
      }
    },

    async addPaymentMethod() {
      this.loading = true;
      this.error = null;
      this.success = null;

      try {
        const { data: setupData } = await api.post('/payment-methods/setup-intent');
        const clientSecret = setupData.data.client_secret;

        const { setupIntent, error: stripeError } = await this.stripe.confirmCardSetup(
          clientSecret,
          {
            payment_method: {
              card: this.cardElement,
            },
          }
        );

        if (stripeError) {
          this.error = stripeError.message;
          this.loading = false;
          return;
        }

        await api.post('/payment-methods/confirm', {
          setup_intent_id: setupIntent.id,
        });

        this.success = 'Payment method added successfully!';
        this.showAddForm = false;
        
        await this.checkPaymentMethods();
        this.cardElement.clear();

        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Payment method added successfully.',
          timer: 2000,
          showConfirmButton: false,
        });

      } catch (error) {
        console.error('Failed to add payment method:', error);
        this.error = error.response?.data?.message || 'Failed to add payment method. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async removeCard(paymentMethodId) {
      const result = await Swal.fire({
        title: 'Remove Payment Method?',
        text: 'Are you sure you want to remove this payment method?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
      });

      if (!result.isConfirmed) return;

      try {
        await api.delete(`/payment-methods/${paymentMethodId}`);
        
        Swal.fire({
          icon: 'success',
          title: 'Removed!',
          text: 'Payment method has been removed.',
          timer: 2000,
          showConfirmButton: false,
        });

        await this.checkPaymentMethods();
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Failed to remove payment method.',
        });
      }
    },

    goToBilling() {
      this.$router.push({ name: 'employer.billing' });
    },
  },

  beforeUnmount() {
    if (this.cardElement) {
      this.cardElement.destroy();
    }
  },
};
</script>

<style scoped>
#card-element {
  min-height: 45px;
}
</style>
