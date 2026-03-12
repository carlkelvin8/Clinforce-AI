<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
import { http } from "@/lib/http";
import Swal from "sweetalert2";

import Card from "primevue/card";
import Button from "primevue/button";
import Message from "primevue/message";
import Tag from "primevue/tag";
import RadioButton from "primevue/radiobutton";

import { countries } from "@/lib/countries";

const loading = ref(true);
const plans = ref([]);
const subscription = ref(null);
const hasPaymentMethod = ref(false);
const invoices = ref([]);
const showInvoices = ref(false);

const company = ref("AI Clinforce Demo Hospital");
const email = ref("billing@example.com");

const statusMsg = ref("");
const errorMsg = ref("");

const currencyInfo = ref(null);
const checkoutBlocked = ref(false);

// Stripe variables
let stripe = null;
let card = null;

function safeParse(raw) {
  try {
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

const user = ref(safeParse(localStorage.getItem("auth_user")));
function refreshUser() {
  user.value = safeParse(localStorage.getItem("auth_user"));
}
function onAuthChanged() {
  refreshUser();
}
function onStorage(e) {
  if (e.key === "auth_user") refreshUser();
}

onMounted(() => {
  refreshUser();
  window.addEventListener("auth:changed", onAuthChanged);
  window.addEventListener("storage", onStorage);
});
onBeforeUnmount(() => {
  window.removeEventListener("auth:changed", onAuthChanged);
  window.removeEventListener("storage", onStorage);
});

const roleLabel = computed(() => {
  const r = String(user.value?.role || "employer");
  if (r === "admin") return "Admin";
  if (r === "agency") return "Agency";
  return "Employer";
});

function ensureStripeKey() {
  return window.STRIPE_PUBLISHABLE_KEY || import.meta.env?.VITE_STRIPE_PUBLISHABLE_KEY || "";
}

async function setupStripe() {
  if (!window.Stripe) return;

  const key = ensureStripeKey();
  if (!key) return;

  stripe = window.Stripe(key);
  const elements = stripe.elements();
  card = elements.create("card", { hidePostalCode: true });

  const el = document.querySelector("#card-element");
  if (el) el.innerHTML = "";
  card.mount("#card-element");
}

function symbolFor(code) {
  const c = String(code || "").toUpperCase();
  if (c === "USD") return "$";
  if (c === "EUR") return "€";
  if (c === "GBP") return "£";
  if (c === "PHP") return "₱";
  if (c === "JPY") return "¥";
  if (c === "AUD") return "A$";
  if (c === "CAD") return "CA$";
  return c || "$";
}

function decimalsFor(code) {
  const c = String(code || "").toUpperCase();
  if (c === "JPY") return 0;
  return 2;
}

function formatMinor(amountCents, code) {
  if (amountCents == null) return "—";
  const decimals = decimalsFor(code);
  const factor = Math.pow(10, decimals);
  const value = amountCents / factor;
  return value.toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

async function ensureBillingContext() {
  checkoutBlocked.value = false;
  try {
    const res = await http.get("/billing/currency");
    const data = res.data?.data || res.data || null;
    if (!data) {
      throw new Error("Missing billing currency payload");
    }

    currencyInfo.value = data;
    plans.value = Array.isArray(data.converted_prices) ? data.converted_prices : [];

    if (!data.conversion_available) {
      checkoutBlocked.value = true;
      await Swal.fire({
        icon: "error",
        title: "Conversion unavailable",
        text:
          "We could not load currency conversion rates for your billing currency. Checkout is temporarily disabled.",
      });
    }

    return true;
  } catch (e) {
    const status = e?.response?.status;
    const body = e?.response?.data;

    // Removed country check - backend now defaults to USD

    errorMsg.value =
      body?.message ||
      body?.error ||
      e?.message ||
      "Failed to load billing currency and plan pricing.";
    return false;
  }
}

async function load() {
  loading.value = true;
  errorMsg.value = "";
  try {
    // Check payment method first
    const pmRes = await http.get("/payment-methods");
    hasPaymentMethod.value = pmRes.data?.data?.has_payment_method || false;

    const ok = await ensureBillingContext();
    if (!ok) return;

    const sr = await http.get("/subscriptions");
    // Handle paginated response: sr.data.data is the paginator object, sr.data.data.data is the array
    const paginatedData = sr.data?.data;
    const subs = Array.isArray(paginatedData?.data) ? paginatedData.data : 
                 Array.isArray(paginatedData) ? paginatedData : 
                 Array.isArray(sr.data) ? sr.data : [];
    subscription.value = subs?.[0] || null;

    // Load invoices
    const ir = await http.get("/invoices");
    const invoicePaginatedData = ir.data?.data;
    invoices.value = Array.isArray(invoicePaginatedData?.data) ? invoicePaginatedData.data : 
                     Array.isArray(invoicePaginatedData) ? invoicePaginatedData : 
                     Array.isArray(ir.data) ? ir.data : [];
  } catch (e) {
    errorMsg.value =
      e?.response?.data?.message ||
      e?.response?.data?.error ||
      e?.message ||
      "Failed to load billing data.";
  } finally {
    loading.value = false;
  }
}

const currentPlan = computed(() => subscription.value?.plan || null);

function planId(p) {
  return p?.stripe_price_id || p?.price_id || p?.id || null;
}
function planName(p) {
  return p?.name || p?.title || "Plan";
}
function planDesc(p) {
  return p?.description || "";
}
function planCurrencySymbol(p) {
  if (p?.currency_symbol) return p.currency_symbol;
  const code = p?.currency_code || currencyInfo.value?.currency_code || p?.currency || "USD";
  return symbolFor(code);
}
function planPriceLabel(p) {
  if (p?.price !== undefined && p?.price !== null) {
    return String(p.price);
  }
  if (typeof p?.price_cents === "number") {
    const code = p.currency_code || currencyInfo.value?.currency_code || p.currency || "USD";
    return formatMinor(p.price_cents, code);
  }
  return p?.price_label || p?.price || (p?.amount ? String(p.amount) : "—");
}
function planInterval(p) {
  return p?.interval || "month";
}
function planFeatures(p) {
  return Array.isArray(p?.features) ? p.features : [];
}

function isCurrent(p) {
  const c = currentPlan.value;
  if (!c) return false;
  const a = String(planId(c) ?? c?.id ?? "");
  const b = String(planId(p) ?? p?.id ?? "");
  return a && b && a === b;
}

function planTier(p) {
  const n = String(planName(p)).toLowerCase();
  if (n.includes("enterprise")) return "enterprise";
  if (n.includes("pro") || n.includes("business")) return "pro";
  return "starter";
}

const selectedPriceId = ref(null);

onMounted(async () => {
  await load();
  selectedPriceId.value = planId(currentPlan.value) || planId(plans.value?.[0]) || null;
  await setupStripe();
});

async function startSubscription() {
  statusMsg.value = "";
  errorMsg.value = "";

  // Check if payment method exists
  if (!hasPaymentMethod.value) {
    const result = await Swal.fire({
      icon: "warning",
      title: "Payment Method Required",
      text: "Please add a payment method before subscribing.",
      confirmButtonText: "Add Payment Method",
      showCancelButton: true,
      cancelButtonText: "Cancel",
    });

    if (result.isConfirmed) {
      window.location.href = "/employer/payment-method";
    }
    return;
  }

  if (checkoutBlocked.value) {
    await Swal.fire({
      icon: "error",
      title: "Checkout disabled",
      text: "Currency conversion is currently unavailable. Please try again later.",
    });
    return;
  }

  if (!stripe || !card) {
    errorMsg.value = "Stripe is not initialized (missing publishable key or Stripe.js).";
    return;
  }

  try {
    const priceId = selectedPriceId.value;
    if (!priceId) {
      errorMsg.value = "Missing plan identifier.";
      return;
    }

    const res = await http.post("/subscriptions", {
      plan_id: priceId,
    });

    if (!res.data?.data) {
      statusMsg.value = res.data?.message || "Subscription created.";
    } else {
      statusMsg.value = "Subscription created.";
    }

    await load();
  } catch (e) {
    const status = e?.response?.status;
    const payload = e?.response?.data;

    if (status === 422 && payload?.errors?.currency_code) {
      await Swal.fire({
        icon: "error",
        title: "Currency error",
        text:
          payload?.message ||
          "We could not lock a currency for this subscription. Please check your billing settings.",
      });
      checkoutBlocked.value = true;
      return;
    }

    errorMsg.value =
      payload?.message ||
      payload?.error ||
      e?.message ||
      "Unable to start subscription.";
  }
}

async function cancelSubscription() {
  if (!subscription.value?.id) return;

  // Show confirmation dialog
  const result = await Swal.fire({
    title: 'Cancel Subscription?',
    html: `
      <p class="text-gray-700 mb-3">Are you sure you want to cancel your subscription?</p>
      <p class="text-sm text-gray-600">You will lose access to premium features at the end of your billing period.</p>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, Cancel Subscription',
    cancelButtonText: 'Keep Subscription',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
  });

  if (!result.isConfirmed) {
    return;
  }

  statusMsg.value = "";
  errorMsg.value = "";

  try {
    await http.post(`/subscriptions/${subscription.value.id}/cancel`);
    statusMsg.value = "Subscription canceled.";
    await Swal.fire({
      icon: 'success',
      title: 'Subscription Cancelled',
      text: 'Your subscription has been cancelled. You will retain access until the end of your billing period.',
      timer: 3000,
      showConfirmButton: true,
    });
    await load();
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || e?.message || "Cancel failed.";
    await Swal.fire({
      icon: 'error',
      title: 'Cancellation Failed',
      text: errorMsg.value,
    });
  }
}

function fmtNextInvoice(val) {
  if (!val) return "—";
  const d = new Date(val);
  if (!Number.isFinite(d.getTime())) return String(val);
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" });
}

const activePill = computed(() => (subscription.value?.id ? "Active" : "Not subscribed"));

const selectedPrice = computed(() => {
  return plans.value.find((p) => planId(p) === selectedPriceId.value) || null;
});

const subscriptionSymbol = computed(() => {
  const code =
    subscription.value?.currency_code ||
    subscription.value?.plan?.currency ||
    currencyInfo.value?.currency_code ||
    "USD";
  return symbolFor(code);
});

const subscriptionPriceLabel = computed(() => {
  if (!subscription.value) return "—";
  const code =
    subscription.value.currency_code ||
    subscription.value.plan?.currency ||
    currencyInfo.value?.currency_code ||
    "USD";
  if (typeof subscription.value.amount_cents === "number") {
    return formatMinor(subscription.value.amount_cents, code);
  }
  if (subscription.value.plan && typeof subscription.value.plan.price_cents === "number") {
    return formatMinor(subscription.value.plan.price_cents, code);
  }
  return planPriceLabel(subscription.value.plan);
});
</script>

<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 -m-6 p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Premium Header -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 rounded-3xl p-8 mb-6 shadow-2xl relative overflow-hidden">
          <div class="absolute inset-0 bg-black opacity-5"></div>
          <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mr-32 -mt-32"></div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-10 rounded-full -ml-24 -mb-24"></div>
          
          <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="text-white">
              <div class="flex items-center gap-2 mb-2">
                <i class="pi pi-credit-card text-2xl"></i>
                <span class="text-sm font-semibold uppercase tracking-wider opacity-90">Account Management</span>
              </div>
              <h1 class="text-4xl font-bold mb-3">Billing & Subscription</h1>
              <div class="flex items-center gap-3 flex-wrap">
                <div class="px-4 py-1.5 bg-white/20 backdrop-blur-sm rounded-full border border-white/30">
                  <span class="text-sm font-semibold">{{ activePill }}</span>
                </div>
                <div v-if="subscription" class="text-sm opacity-90">
                  <i class="pi pi-calendar mr-1"></i>
                  Next invoice: {{ fmtNextInvoice(subscription?.next_billing_at || subscription?.renews_at || subscription?.current_period_end) }}
                </div>
              </div>
            </div>
            <Button 
              :label="loading ? 'Refreshing…' : 'Refresh'" 
              :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" 
              @click="load" 
              :disabled="loading" 
              class="!bg-white !text-blue-600 hover:!bg-blue-50 !border-0 !shadow-lg"
            />
          </div>
        </div>

        <!-- Currency Info Card -->
        <div v-if="currencyInfo" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 mb-6">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="pi pi-globe text-white text-xl"></i>
              </div>
              <div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Billing Region</div>
                <div class="text-xl font-bold text-gray-900">
                  {{ currencyInfo.country_name || 'Set billing country' }}
                  <span v-if="currencyInfo.country_code" class="text-sm text-gray-500 ml-2">
                    ({{ currencyInfo.country_code }})
                  </span>
                </div>
                <div class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                  <i class="pi pi-info-circle text-gray-400"></i>
                  <span>Currency is determined by your billing country</span>
                  <span v-if="currencyInfo.rate_is_stale" class="font-semibold text-amber-600 ml-1">
                    (Estimated prices)
                  </span>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-3 px-5 py-3 bg-gradient-to-br from-slate-50 to-blue-50 rounded-xl border border-blue-100">
              <div class="text-right">
                <div class="text-xs font-semibold text-gray-600 uppercase mb-1">Currency</div>
                <div class="text-2xl font-bold text-blue-600">
                  {{ currencyInfo.symbol }} {{ currencyInfo.currency_code }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-lg mb-4">
            <i class="pi pi-spin pi-spinner text-4xl text-blue-600"></i>
          </div>
          <div class="text-gray-700 font-medium">Loading billing data...</div>
        </div>

        <template v-else>
          <!-- Messages -->
          <Message v-if="errorMsg" severity="error" :closable="false" class="mb-4 shadow-lg">{{ errorMsg }}</Message>
          <Message v-if="statusMsg" severity="success" :closable="false" class="mb-4 shadow-lg">{{ statusMsg }}</Message>

          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Sidebar -->
            <div class="flex flex-col gap-6">
              <!-- Current Subscription Card -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-4">
                  <div class="flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                      <i class="pi pi-check-circle text-2xl"></i>
                      <span class="font-bold text-lg">Current Plan</span>
                    </div>
                    <Tag v-if="subscription" :value="subscription.status.toUpperCase()" :severity="subscription.status === 'active' ? 'success' : 'warning'" />
                  </div>
                </div>
                
                <div class="p-6">
                  <div v-if="subscription">
                    <!-- Plan Name -->
                    <div class="text-center mb-6">
                      <div class="text-2xl font-bold text-gray-900 mb-2">{{ planName(subscription.plan) }}</div>
                      <div class="inline-flex items-baseline gap-1 px-6 py-3 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                        <span class="text-3xl font-extrabold text-green-700">{{ subscriptionSymbol }}{{ subscriptionPriceLabel }}</span>
                        <span class="text-sm font-medium text-gray-600">/ {{ planInterval(subscription.plan) }}</span>
                      </div>
                      <div class="text-xs text-gray-500 mt-2">{{ subscription.currency_code }} Currency</div>
                    </div>

                    <!-- Subscription Details -->
                    <div class="space-y-3 mb-6">
                      <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600 flex items-center gap-2">
                          <i class="pi pi-calendar-plus text-green-600"></i>
                          Started
                        </span>
                        <span class="font-semibold text-gray-900">{{ new Date(subscription.start_at).toLocaleDateString() }}</span>
                      </div>
                      <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600 flex items-center gap-2">
                          <i class="pi pi-sync text-blue-600"></i>
                          Renews
                        </span>
                        <span class="font-semibold text-gray-900">{{ fmtNextInvoice(subscription.end_at || subscription.current_period_end) }}</span>
                      </div>
                      <div v-if="subscription.cancelled_at" class="flex justify-between items-center p-3 bg-red-50 rounded-lg border border-red-200">
                        <span class="text-sm text-red-600 flex items-center gap-2">
                          <i class="pi pi-times-circle"></i>
                          Cancelled
                        </span>
                        <span class="font-semibold text-red-600">{{ new Date(subscription.cancelled_at).toLocaleDateString() }}</span>
                      </div>
                    </div>

                    <!-- Plan Features -->
                    <div v-if="planFeatures(subscription.plan).length > 0" class="mb-6">
                      <div class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <i class="pi pi-star-fill text-yellow-500"></i>
                        Plan Features
                      </div>
                      <ul class="space-y-2">
                        <li v-for="(f, i) in planFeatures(subscription.plan)" :key="i" class="flex items-start gap-2 text-sm text-gray-700">
                          <i class="pi pi-check text-green-600 text-xs mt-1 flex-shrink-0"></i>
                          <span>{{ f }}</span>
                        </li>
                      </ul>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2">
                      <Button
                        v-if="subscription.status === 'active'"
                        label="Cancel Subscription"
                        icon="pi pi-times"
                        severity="danger"
                        outlined
                        class="w-full"
                        @click="cancelSubscription"
                      />
                      <Button
                        label="View Invoices"
                        icon="pi pi-file"
                        severity="secondary"
                        outlined
                        class="w-full"
                        @click="showInvoices = !showInvoices"
                        :badge="invoices.length > 0 ? String(invoices.length) : null"
                      />
                    </div>

                    <!-- Invoices List -->
                    <div v-if="showInvoices && invoices.length > 0" class="mt-6 pt-6 border-t border-gray-200">
                      <div class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <i class="pi pi-file text-blue-600"></i>
                        Invoice History
                      </div>
                      <div class="space-y-2 max-h-64 overflow-y-auto">
                        <div 
                          v-for="invoice in invoices" 
                          :key="invoice.id"
                          class="flex justify-between items-center p-3 bg-gradient-to-r from-gray-50 to-blue-50 rounded-lg border border-gray-200 hover:shadow-md transition-all"
                        >
                          <div class="flex-1">
                            <div class="font-bold text-sm text-gray-900">
                              {{ currencyInfo?.symbol || '₱' }}{{ formatMinor(invoice.amount_cents, invoice.currency_code) }}
                            </div>
                            <div class="text-xs text-gray-600">
                              {{ new Date(invoice.issued_at).toLocaleDateString() }}
                            </div>
                          </div>
                          <Tag 
                            :value="invoice.status.toUpperCase()" 
                            :severity="invoice.status === 'paid' ? 'success' : invoice.status === 'pending' ? 'warning' : 'danger'" 
                          />
                        </div>
                      </div>
                    </div>
                    <div v-else-if="showInvoices && invoices.length === 0" class="mt-6 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                      <i class="pi pi-inbox text-3xl text-gray-300 mb-2"></i>
                      <p>No invoices yet</p>
                    </div>
                  </div>
                  
                  <div v-else class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                      <i class="pi pi-shopping-cart text-3xl text-gray-400"></i>
                    </div>
                    <p class="font-bold text-lg text-gray-900 mb-2">No Active Subscription</p>
                    <p class="text-sm text-gray-600">Select a plan below to unlock premium features</p>
                  </div>
                </div>
              </div>

              <!-- Payment Method Card -->
              <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-4">
                  <div class="flex items-center justify-between text-white">
                    <div class="flex items-center gap-2">
                      <i class="pi pi-credit-card text-2xl"></i>
                      <span class="font-bold text-lg">Payment Method</span>
                    </div>
                    <Button 
                      icon="pi pi-cog" 
                      size="small"
                      text
                      class="!text-white hover:!bg-white/20"
                      @click="$router.push({ name: 'employer.payment-method' })"
                    />
                  </div>
                </div>
                
                <div class="p-6">
                  <div v-if="hasPaymentMethod">
                    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl mb-4">
                      <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-check text-white text-xl"></i>
                      </div>
                      <div class="flex-1">
                        <div class="font-bold text-gray-900">Card on File</div>
                        <div class="text-sm text-gray-600 mt-0.5">•••• •••• •••• ••••</div>
                      </div>
                    </div>
                    <p class="text-xs text-gray-500 flex items-center gap-1 justify-center">
                      <i class="pi pi-lock text-xs"></i> 
                      Secured by Stripe
                    </p>
                  </div>
                  <div v-else>
                    <div class="flex items-center gap-3 p-4 bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-200 rounded-xl mb-4">
                      <div class="w-12 h-12 bg-amber-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-exclamation-triangle text-white text-xl"></i>
                      </div>
                      <div class="flex-1">
                        <div class="font-bold text-gray-900">No Payment Method</div>
                        <div class="text-sm text-gray-600 mt-0.5">Add a card to subscribe</div>
                      </div>
                    </div>
                    <Button 
                      label="Add Payment Method" 
                      icon="pi pi-plus" 
                      class="w-full !bg-gradient-to-r !from-blue-600 !to-indigo-600 hover:!from-blue-700 hover:!to-indigo-700 !border-0"
                      @click="$router.push({ name: 'employer.payment-method' })"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Section - Plans -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Checkout Summary -->
              <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100" v-if="currencyInfo">
                <div class="flex items-center gap-2 mb-4">
                  <i class="pi pi-shopping-cart text-blue-600 text-xl"></i>
                  <h3 class="text-lg font-bold text-gray-900">Checkout Summary</h3>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Country</div>
                    <div class="font-semibold text-gray-900">{{ currencyInfo.country_name || 'Not set' }}</div>
                  </div>
                  <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Currency</div>
                    <div class="font-semibold text-gray-900">{{ currencyInfo.symbol }} {{ currencyInfo.currency_code }}</div>
                  </div>
                  <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="text-xs text-blue-600 mb-1">Plan Price</div>
                    <div class="font-bold text-blue-700" v-if="selectedPrice">
                      {{ planCurrencySymbol(selectedPrice) }}{{ planPriceLabel(selectedPrice) }} / {{ planInterval(selectedPrice) }}
                    </div>
                    <div v-else class="text-gray-400">—</div>
                  </div>
                  <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Tax</div>
                    <div class="text-sm text-gray-700">Included</div>
                  </div>
                </div>
                <div class="mt-4 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                  <div class="flex justify-between items-center">
                    <span class="font-semibold text-gray-700">Total</span>
                    <div class="text-right">
                      <div class="text-2xl font-extrabold text-green-700" v-if="selectedPrice">
                        {{ planCurrencySymbol(selectedPrice) }}{{ planPriceLabel(selectedPrice) }}
                        <span class="text-sm font-medium text-gray-600">/ {{ planInterval(selectedPrice) }}</span>
                      </div>
                      <div v-else class="text-gray-400">—</div>
                      <div v-if="currencyInfo?.rate_is_stale" class="text-xs text-amber-600 mt-1">
                        (Estimated)
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Plans Grid -->
              <div>
                <h3 class="text-2xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                  <i class="pi pi-box text-blue-600"></i>
                  Available Plans
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                  <div 
                    v-for="p in plans" 
                    :key="p.id" 
                    class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all cursor-pointer relative overflow-hidden group"
                    :class="{
                      'ring-2 ring-blue-500 ring-offset-2': selectedPriceId === planId(p),
                      'border border-gray-200': selectedPriceId !== planId(p),
                    }"
                    @click="selectedPriceId = planId(p)"
                  >
                    <!-- Selected Badge -->
                    <div v-if="selectedPriceId === planId(p)" class="absolute top-0 right-0 bg-blue-600 text-white px-3 py-1 rounded-bl-lg text-xs font-bold">
                      SELECTED
                    </div>
                    
                    <!-- Current Badge -->
                    <div v-if="isCurrent(p)" class="absolute top-0 left-0 bg-green-600 text-white px-3 py-1 rounded-br-lg text-xs font-bold">
                      CURRENT
                    </div>

                    <!-- Plan Header -->
                    <div class="mb-4">
                      <div class="text-xl font-bold text-gray-900 mb-2">{{ planName(p) }}</div>
                      <div class="text-sm text-gray-600">{{ planDesc(p) }}</div>
                    </div>

                    <!-- Price -->
                    <div class="mb-6 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl">
                      <div class="flex items-baseline justify-center gap-1">
                        <span class="text-3xl font-extrabold text-blue-700">{{ planCurrencySymbol(p) }}{{ planPriceLabel(p) }}</span>
                        <span class="text-sm text-gray-600">/{{ planInterval(p) }}</span>
                      </div>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-2 mb-6">
                      <li v-for="(f, i) in planFeatures(p)" :key="i" class="flex items-start gap-2 text-sm text-gray-700">
                        <i class="pi pi-check-circle text-blue-600 mt-0.5 flex-shrink-0"></i>
                        <span>{{ f }}</span>
                      </li>
                    </ul>

                    <!-- Select Button -->
                    <Button 
                      class="w-full" 
                      :label="isCurrent(p) ? 'Current Plan' : (selectedPriceId === planId(p) ? 'Selected' : 'Select Plan')"
                      :severity="isCurrent(p) ? 'success' : (selectedPriceId === planId(p) ? 'primary' : 'secondary')"
                      :outlined="!isCurrent(p)"
                      :disabled="isCurrent(p)"
                      :icon="selectedPriceId === planId(p) ? 'pi pi-check' : 'pi pi-arrow-right'"
                    />
                  </div>
                </div>
              </div>

              <!-- Subscribe Button -->
              <div class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                <Button 
                  class="w-full !py-4 !text-lg !font-bold !bg-gradient-to-r !from-blue-600 !to-indigo-600 hover:!from-blue-700 hover:!to-indigo-700 !border-0 !shadow-xl"
                  :label="subscription ? 'Update Subscription' : 'Subscribe Now'"
                  icon="pi pi-check-circle"
                  :loading="loading"
                  @click="startSubscription"
                  :disabled="!selectedPriceId || checkoutBlocked"
                />
                <p class="text-xs text-center text-gray-500 mt-3">
                  <i class="pi pi-shield text-green-600"></i>
                  Secure payment powered by Stripe
                </p>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>
