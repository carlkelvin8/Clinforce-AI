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

const loading = ref(true);
const plans = ref([]);
const subscription = ref(null);

const company = ref("AI Clinforce Demo Hospital");
const email = ref("billing@example.com");

const statusMsg = ref("");
const errorMsg = ref("");

const currencyInfo = ref(null);
const checkoutBlocked = ref(false);

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

    if (status === 422 && body?.errors?.code === "billing_country_required") {
      const countries = Array.isArray(body.errors.countries) ? body.errors.countries : [];
      if (!countries.length) {
        errorMsg.value = body.message || "Billing country is required.";
        return false;
      }

      const options = {};
      countries.forEach((c) => {
        options[c.country_code] = `${c.country_name} (${c.currency_code})`;
      });

      const result = await Swal.fire({
        title: "Select billing country",
        text: "We use your billing country to determine the correct currency.",
        input: "select",
        inputOptions: options,
        inputPlaceholder: "Choose country",
        confirmButtonText: "Save",
        allowOutsideClick: false,
        allowEscapeKey: false,
        preConfirm: (value) => {
          if (!value) {
            Swal.showValidationMessage("Country is required");
          }
          return value;
        },
      });

      if (!result.isConfirmed || !result.value) {
        errorMsg.value = "Billing country is required to view localized pricing.";
        return false;
      }

      await http.post("/billing/profile", {
        country_code: result.value,
      });

      return ensureBillingContext();
    }

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
    const ok = await ensureBillingContext();
    if (!ok) return;

    const sr = await http.get("/subscriptions");
    const subs = Array.isArray(sr.data?.data) ? sr.data.data : Array.isArray(sr.data) ? sr.data : [];
    subscription.value = subs?.[0] || null;
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

  statusMsg.value = "";
  errorMsg.value = "";

  try {
    await http.post(`/subscriptions/${subscription.value.id}/cancel`);
    statusMsg.value = "Subscription canceled.";
    await load();
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || e?.message || "Cancel failed.";
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
    <div class="flex flex-col gap-4">
      <div class="bg-white border border-slate-200 rounded-2xl p-5 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <div class="text-xs font-bold text-gray-600 uppercase tracking-wide">Account</div>
            <h1 class="text-3xl font-bold m-0 text-gray-900 mt-1">Billing & Subscription</h1>
            <div class="flex items-center gap-3 mt-2">
              <Tag :value="activePill" :severity="subscription?.id ? 'success' : 'secondary'" rounded />
              <div v-if="subscription" class="text-sm text-gray-600">Next invoice: {{ fmtNextInvoice(subscription?.next_billing_at || subscription?.renews_at || subscription?.current_period_end) }}</div>
            </div>
          </div>
          <div class="flex gap-2">
            <Button 
              :label="loading ? 'Refreshing…' : 'Refresh'" 
              :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" 
              @click="load" 
              :disabled="loading" 
              outlined 
              severity="secondary" 
            />
          </div>
        </div>
      </div>

      <div
        v-if="currencyInfo"
        class="bg-white border border-slate-200 rounded-2xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-3"
      >
        <div>
          <div class="text-xs font-bold text-gray-600 uppercase tracking-wide">Billing region</div>
          <div class="text-lg font-bold text-gray-900 mt-1">
            {{ currencyInfo.country_name || 'Set billing country' }}
            <span v-if="currencyInfo.country_code" class="text-xs text-gray-500 ml-1">
              ({{ currencyInfo.country_code }})
            </span>
          </div>
          <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
            <i class="pi pi-info-circle text-gray-400"></i>
            <span>Currency is determined by your billing country.</span>
            <span
              v-if="currencyInfo.rate_is_stale"
              class="font-semibold text-amber-600 ml-1"
            >
              (Estimated prices)
            </span>
          </div>
        </div>
        <div class="flex flex-col items-start md:items-end gap-1">
          <div class="text-xs font-semibold text-gray-600 uppercase">Currency</div>
          <div
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-slate-200 bg-slate-50 text-sm"
          >
            <span>{{ currencyInfo.symbol }}</span>
            <span class="font-semibold">{{ currencyInfo.currency_code }}</span>
            <span class="text-[11px] text-gray-500">(Based on your country)</span>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center p-5 text-gray-600">
        <i class="pi pi-spin pi-spinner text-4xl mb-3"></i>
        <div>Loading billing data...</div>
      </div>

      <template v-else>
        <!-- Messages -->
        <Message v-if="errorMsg" severity="error" :closable="false" class="mb-3">{{ errorMsg }}</Message>
        <Message v-if="statusMsg" severity="success" :closable="false" class="mb-3">{{ statusMsg }}</Message>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="flex flex-col gap-4">
            <Card>
              <template #title>Current Subscription</template>
              <template #content>
                <div v-if="subscription" class="text-center p-3">
                  <div class="text-xl font-bold text-gray-900 mb-1">{{ planName(subscription.plan) }}</div>
                  <div class="text-3xl font-bold text-green-600 mb-1">
                    <span>{{ subscriptionSymbol }}</span>
                    {{ subscriptionPriceLabel }}
                    <span class="text-base font-medium text-gray-600">/ {{ planInterval(subscription.plan) }}</span>
                  </div>
                  <div class="text-xs text-gray-500 mb-3">Renews: {{ fmtNextInvoice(subscription?.next_billing_at || subscription?.renews_at || subscription?.current_period_end) }}</div>
                  <div class="flex justify-center gap-3 items-center mb-4">
                    <Tag :value="subscription.status" :severity="subscription.status === 'active' ? 'success' : 'warning'" rounded />
                    <span v-if="subscription.ends_at" class="text-sm text-gray-600">
                      Ends: {{ new Date(subscription.ends_at).toLocaleDateString() }}
                    </span>
                  </div>

                  <Button
                    v-if="subscription.status === 'active'"
                    label="Cancel Subscription"
                    icon="pi pi-times"
                    severity="danger"
                    outlined
                    @click="cancelSubscription"
                  />
                </div>
                
                <div v-else class="text-center p-4 text-gray-600">
                  <p class="font-medium">No active subscription.</p>
                  <p class="text-sm">Select a plan below to get started.</p>
                </div>
              </template>
            </Card>

            <Card>
              <template #title>Payment Method</template>
              <template #content>
                <div class="py-2">
                  <label class="block text-sm font-bold mb-2 text-gray-700">Credit or Debit Card</label>
                  <div id="card-element" class="border border-gray-300 rounded p-3 bg-white hover:border-gray-400 transition-colors"></div>
                  <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                    <i class="pi pi-lock text-xs"></i> Secured by Stripe
                  </p>
                </div>
              </template>
            </Card>
          </div>

          <div class="lg:col-span-2">
            <div class="mb-4 bg-white border border-slate-200 rounded-2xl p-4" v-if="currencyInfo">
              <div class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">
                Checkout summary
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-500">Country</span>
                  <span class="font-medium text-slate-900">
                    {{ currencyInfo.country_name || 'Not set' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Currency</span>
                  <span class="font-medium text-slate-900">
                    <span v-if="currencyInfo">
                      {{ currencyInfo.symbol }} {{ currencyInfo.currency_code }}
                    </span>
                    <span v-else>—</span>
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Plan price</span>
                  <span class="font-semibold text-slate-900">
                    <span v-if="selectedPrice">
                      {{ planCurrencySymbol(selectedPrice) }}
                      {{ planPriceLabel(selectedPrice) }}
                      / {{ planInterval(selectedPrice) }}
                    </span>
                    <span v-else>—</span>
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Tax</span>
                  <span class="font-medium text-slate-900">
                    Included where applicable
                  </span>
                </div>
                <div class="flex justify-between sm:col-span-2">
                  <span class="text-slate-500">Total</span>
                  <span class="font-extrabold text-slate-900">
                    <span v-if="selectedPrice">
                      {{ planCurrencySymbol(selectedPrice) }}
                      {{ planPriceLabel(selectedPrice) }}
                      / {{ planInterval(selectedPrice) }}
                    </span>
                    <span v-else>—</span>
                    <span
                      v-if="currencyInfo?.rate_is_stale"
                      class="text-[11px] text-amber-600 ml-1"
                    >
                      (Estimated)
                    </span>
                  </span>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div 
                v-for="p in plans" 
                :key="p.id" 
                class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow transition-all cursor-pointer"
                :class="{
                  'border-blue-500 ring-1 ring-blue-200': selectedPriceId === planId(p),
                  'border-slate-200': selectedPriceId !== planId(p),
                }"
                @click="selectedPriceId = planId(p)"
              >
                <div class="flex items-baseline justify-between">
                  <div class="text-lg font-bold text-slate-900">{{ planName(p) }}</div>
                  <Tag v-if="isCurrent(p)" value="Current" severity="success" rounded />
                </div>
                <div class="mt-2">
                  <div class="text-3xl font-extrabold text-slate-900">
                    <span>{{ planCurrencySymbol(p) }}</span>
                    {{ planPriceLabel(p) }}
                    <span class="text-sm text-slate-600 font-medium">/{{ planInterval(p) }}</span>
                  </div>
                  <div class="text-sm text-slate-600 mt-1">{{ planDesc(p) }}</div>
                </div>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                  <li v-for="(f, i) in planFeatures(p)" :key="i" class="flex items-start gap-2">
                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    <span>{{ f }}</span>
                  </li>
                </ul>
                <div class="mt-5">
                  <Button 
                    class="w-full" 
                    :label="isCurrent(p) ? 'Selected' : (selectedPriceId === planId(p) ? 'Choose Plan' : 'Select')"
                    :severity="isCurrent(p) ? 'success' : (selectedPriceId === planId(p) ? 'primary' : 'secondary')"
                    outlined
                    :disabled="isCurrent(p)"
                  />
                </div>
                <div class="sr-only">
                  <RadioButton :modelValue="selectedPriceId" :value="planId(p)" name="plan" />
                </div>
              </div>
            </div>
            <div class="mt-4">
              <Button 
                class="w-full" 
                :label="subscription ? 'Update Subscription' : 'Subscribe Now'"
                :loading="loading"
                @click="startSubscription"
                :disabled="!selectedPriceId || checkoutBlocked"
              />
            </div>
          </div>
        </div>
      </template>
      
    </div>
  </AppLayout>
</template>
