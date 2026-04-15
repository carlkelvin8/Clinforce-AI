<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import AppLayout from "@/Components/AppLayout.vue";
import { http } from "@/lib/http";
import Swal from "sweetalert2";

import Button from "primevue/button";
import Message from "primevue/message";
import Tag from "primevue/tag";
import Select from "primevue/select";

const loading    = ref(true);
const plans      = ref([]);
const subscription = ref(null);
const hasPaymentMethod = ref(false);
const invoices   = ref([]);
const showInvoices = ref(false);
const statusMsg  = ref("");
const errorMsg   = ref("");
const currencyInfo = ref(null);
const checkoutBlocked = ref(false);

// Country selector
const countryList    = ref([]);
const selectedCountry = ref(null);
const savingCountry  = ref(false);

let stripe = null;
let card   = null;

function safeParse(raw) {
  try { return raw ? JSON.parse(raw) : null; } catch { return null; }
}
const user = ref(safeParse(localStorage.getItem("auth_user")));
function refreshUser() { user.value = safeParse(localStorage.getItem("auth_user")); }
function onAuthChanged() { refreshUser(); }
function onStorage(e) { if (e.key === "auth_user") refreshUser(); }
onMounted(() => {
  refreshUser();
  window.addEventListener("auth:changed", onAuthChanged);
  window.addEventListener("storage", onStorage);
});
onBeforeUnmount(() => {
  window.removeEventListener("auth:changed", onAuthChanged);
  window.removeEventListener("storage", onStorage);
});

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
function decimalsFor(code) { return String(code||"").toUpperCase() === "JPY" ? 0 : 2; }
function formatMinor(amountCents, code) {
  if (amountCents == null) return "—";
  const dec = decimalsFor(code);
  return (amountCents / Math.pow(10, dec)).toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

async function loadCountries() {
  try {
    const res = await http.get("/billing/countries");
    const data = res.data?.data || res.data || [];
    countryList.value = Array.isArray(data) ? data.map(c => ({
      label: c.country_name,
      value: c.country_code,
    })) : [];
  } catch { /* non-fatal */ }
}

async function saveCountry() {
  if (!selectedCountry.value) return;
  savingCountry.value = true;
  try {
    await http.post("/billing/profile", { country_code: selectedCountry.value });
    await load();
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || "Failed to save billing region.";
  } finally {
    savingCountry.value = false;
  }
}

async function ensureBillingContext() {
  checkoutBlocked.value = false;
  try {
    const res = await http.get("/billing/currency");
    const data = res.data?.data || res.data || null;
    if (!data) throw new Error("Missing billing currency payload");
    currencyInfo.value = data;
    plans.value = Array.isArray(data.converted_prices) ? data.converted_prices : [];
    // Pre-select current country in dropdown
    if (data.country_code) selectedCountry.value = data.country_code;
    if (!data.conversion_available) {
      checkoutBlocked.value = true;
      await Swal.fire({ icon: "error", title: "Conversion unavailable", text: "Currency conversion rates unavailable. Checkout is temporarily disabled." });
    }
    return true;
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || e?.message || "Failed to load billing data.";
    return false;
  }
}

async function load() {
  loading.value = true;
  errorMsg.value = "";
  try {
    const pmRes = await http.get("/payment-methods");
    hasPaymentMethod.value = pmRes.data?.data?.has_payment_method || false;
    const ok = await ensureBillingContext();
    if (!ok) return;
    const sr = await http.get("/subscriptions");
    const pd = sr.data?.data;
    const subs = Array.isArray(pd?.data) ? pd.data : Array.isArray(pd) ? pd : Array.isArray(sr.data) ? sr.data : [];
    subscription.value = subs?.[0] || null;
    const ir = await http.get("/invoices");
    const ipd = ir.data?.data;
    invoices.value = Array.isArray(ipd?.data) ? ipd.data : Array.isArray(ipd) ? ipd : Array.isArray(ir.data) ? ir.data : [];
  } catch (e) {
    errorMsg.value = e?.response?.data?.message || e?.message || "Failed to load billing data.";
  } finally {
    loading.value = false;
  }
}

const currentPlan = computed(() => subscription.value?.plan || null);

function planId(p)             { return p?.id || p?.stripe_price_id || null; }
function planName(p)           { return p?.name || p?.title || "Plan"; }
function planCurrencySymbol(p) { return p?.currency_symbol || symbolFor(p?.currency_code || currencyInfo.value?.currency_code || "USD"); }
function planPriceLabel(p) {
  if (p?.is_trial || (typeof p?.price_cents === "number" && p.price_cents === 0)) return "Free";
  if (p?.price !== undefined && p?.price !== null) return String(p.price);
  if (typeof p?.price_cents === "number") return formatMinor(p.price_cents, p.currency_code || currencyInfo.value?.currency_code || "USD");
  return "—";
}
function planInterval(p) { return p?.interval || "month"; }
function planFeatures(p) { return Array.isArray(p?.features) ? p.features : (Array.isArray(p?.features_json) ? p.features_json : []); }
function isCurrent(p) {
  const c = currentPlan.value;
  if (!c) return false;
  return String(planId(c) ?? c?.id ?? "") === String(planId(p) ?? p?.id ?? "");
}

const selectedPriceId = ref(null);
const selectedPrice   = computed(() => plans.value.find(p => planId(p) === selectedPriceId.value) || null);

onMounted(async () => {
  await Promise.all([load(), loadCountries()]);
  selectedPriceId.value = planId(currentPlan.value) || planId(plans.value?.[0]) || null;
});

async function startSubscription() {
  statusMsg.value = "";
  errorMsg.value  = "";
  // Trial plan: skip payment method check
  const isTrial = selectedPrice.value?.is_trial || (typeof selectedPrice.value?.price_cents === "number" && selectedPrice.value?.price_cents === 0);
  if (!isTrial && !hasPaymentMethod.value) {
    const r = await Swal.fire({ icon: "warning", title: "Payment Method Required", text: "Please add a payment method before subscribing to a paid plan.", confirmButtonText: "Add Payment Method", showCancelButton: true });
    if (r.isConfirmed) window.location.href = "/employer/payment-method";
    return;
  }
  if (checkoutBlocked.value) {
    await Swal.fire({ icon: "error", title: "Checkout disabled", text: "Currency conversion is currently unavailable." });
    return;
  }
  try {
    const numericPlanId = parseInt(selectedPrice.value?.id ?? selectedPriceId.value, 10);
    if (!numericPlanId) { errorMsg.value = "Missing plan identifier."; return; }
    const res = await http.post("/subscriptions", { plan_id: numericPlanId });
    statusMsg.value = res.data?.message || (isTrial ? "Free trial activated!" : "Subscription created.");
    await load();
  } catch (e) {
    const payload = e?.response?.data;
    if (e?.response?.status === 422 && payload?.errors?.currency_code) {
      await Swal.fire({ icon: "error", title: "Currency error", text: payload?.message || "Could not lock a currency for this subscription." });
      checkoutBlocked.value = true;
      return;
    }
    errorMsg.value = payload?.message || e?.message || "Unable to start subscription.";
  }
}

async function cancelSubscription() {
  if (!subscription.value?.id) return;
  const r = await Swal.fire({ title: "Cancel Subscription?", html: `<p>Are you sure? You retain access until the end of your billing period.</p>`, icon: "warning", showCancelButton: true, confirmButtonText: "Yes, Cancel", cancelButtonText: "Keep Plan", confirmButtonColor: "#dc2626", reverseButtons: true });
  if (!r.isConfirmed) return;
  try {
    await http.post(`/subscriptions/${subscription.value.id}/cancel`);
    await Swal.fire({ icon: "success", title: "Cancelled", text: "Access continues until the billing period ends.", timer: 3000, showConfirmButton: false });
    await load();
  } catch (e) {
    await Swal.fire({ icon: "error", title: "Failed", text: e?.response?.data?.message || e?.message || "Cancel failed." });
  }
}

function fmtDate(val) {
  if (!val) return "—";
  const d = new Date(val);
  return Number.isFinite(d.getTime()) ? d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" }) : String(val);
}

function downloadInvoice(id) {
  window.open(`/api/invoices/${id}/download`, '_blank');
}

const subscriptionSymbol = computed(() => symbolFor(subscription.value?.currency_code || subscription.value?.plan?.currency || currencyInfo.value?.currency_code || "USD"));
const subscriptionPriceLabel = computed(() => {
  if (!subscription.value) return "—";
  const code = subscription.value.currency_code || subscription.value.plan?.currency || currencyInfo.value?.currency_code || "USD";
  if (typeof subscription.value.amount_cents === "number") return formatMinor(subscription.value.amount_cents, code);
  if (subscription.value.plan && typeof subscription.value.plan.price_cents === "number") return formatMinor(subscription.value.plan.price_cents, code);
  return planPriceLabel(subscription.value.plan);
});
</script>

<template>
  <AppLayout>
    <div class="billing-page min-h-screen">
      <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold billing-text-primary">Billing &amp; Subscription</h1>
            <p class="text-sm billing-text-muted mt-0.5">Manage your plan, payment method and invoices</p>
          </div>
          <Button :label="loading ? 'Refreshing…' : 'Refresh'" :icon="loading ? 'pi pi-spin pi-spinner' : 'pi pi-refresh'" severity="secondary" outlined size="small" @click="load" :disabled="loading" />
        </div>

        <!-- Alerts -->
        <Message v-if="errorMsg"  severity="error"   :closable="false">{{ errorMsg }}</Message>
        <Message v-if="statusMsg" severity="success" :closable="false">{{ statusMsg }}</Message>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-3">
          <i class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
          <span class="billing-text-muted text-sm">Loading billing data…</span>
        </div>

        <template v-else>

          <!-- ── Active subscription banner ── -->
          <div v-if="subscription" class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 p-6 text-white shadow-lg">
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-12 -left-12 h-40 w-40 rounded-full bg-white/10"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 flex-shrink-0">
                  <i class="pi pi-check-circle text-2xl"></i>
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xl font-bold">{{ planName(subscription.plan) }}</span>
                    <span class="rounded-full bg-white/20 px-3 py-0.5 text-xs font-semibold uppercase">{{ subscription.status }}</span>
                  </div>
                  <div class="mt-1 flex items-center gap-4 text-sm text-white/80 flex-wrap">
                    <span><i class="pi pi-calendar-plus mr-1 text-xs"></i>Started {{ fmtDate(subscription.start_at) }}</span>
                    <span><i class="pi pi-sync mr-1 text-xs"></i>Renews {{ fmtDate(subscription.end_at || subscription.current_period_end) }}</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <div class="text-3xl font-extrabold">{{ subscriptionSymbol }}{{ subscriptionPriceLabel }}</div>
                <div class="text-xs text-white/70">{{ planInterval(subscription.plan) }} · {{ subscription.currency_code }}</div>
                <Button v-if="subscription.status === 'active'" label="Cancel" icon="pi pi-times" size="small" class="!bg-white/20 !border-white/30 !text-white hover:!bg-white/30" @click="cancelSubscription" />
              </div>
            </div>
            <div v-if="subscription.cancelled_at" class="relative z-10 mt-4 flex items-center gap-2 rounded-xl bg-red-500/30 px-4 py-2 text-sm">
              <i class="pi pi-exclamation-triangle"></i>
              Cancelled on {{ fmtDate(subscription.cancelled_at) }} — access ends at renewal date.
            </div>
          </div>

          <!-- No subscription -->
          <div v-else class="billing-card rounded-2xl border-2 border-dashed p-8 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full billing-icon-bg">
              <i class="pi pi-box text-2xl billing-text-muted"></i>
            </div>
            <p class="font-semibold billing-text-primary">No active subscription</p>
            <p class="text-sm billing-text-muted mt-1">Choose a plan below to get started</p>
          </div>

          <!-- ── Billing Region + Payment Method ── -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <!-- Billing Region card -->
            <div class="billing-card rounded-2xl p-5 shadow-sm">
              <div class="flex items-center gap-3 mb-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl billing-icon-accent flex-shrink-0">
                  <i class="pi pi-globe text-blue-600 text-lg"></i>
                </div>
                <div>
                  <div class="text-xs font-semibold billing-text-muted uppercase tracking-wide">Billing Region</div>
                  <div class="font-bold billing-text-primary text-sm">
                    <span v-if="currencyInfo?.country_name">{{ currencyInfo.country_name }} ({{ currencyInfo.country_code }})</span>
                    <span v-else class="text-amber-500">Not set — select your country</span>
                  </div>
                </div>
              </div>
              <div class="flex gap-2">
                <Select
                  v-model="selectedCountry"
                  :options="countryList"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select country…"
                  filter
                  filterPlaceholder="Search country…"
                  class="flex-1 text-sm"
                  :loading="countryList.length === 0"
                />
                <Button label="Save" icon="pi pi-check" size="small" :loading="savingCountry" :disabled="!selectedCountry || savingCountry" @click="saveCountry" />
              </div>
              <div v-if="currencyInfo?.currency_code" class="mt-3 text-xs billing-text-muted flex items-center gap-1">
                Currency:
                <span class="font-bold text-blue-500 ml-1">{{ currencyInfo.symbol }} {{ currencyInfo.currency_code }}</span>
                <span v-if="currencyInfo.rate_is_stale" class="text-amber-500 ml-1">(estimated rates)</span>
              </div>
            </div>

            <!-- Payment Method card -->
            <div class="billing-card rounded-2xl p-5 shadow-sm flex items-center gap-4">
              <div class="flex h-10 w-10 items-center justify-center rounded-xl flex-shrink-0"
                   :class="hasPaymentMethod ? 'billing-icon-success' : 'billing-icon-warn'">
                <i class="text-lg" :class="hasPaymentMethod ? 'pi pi-credit-card text-green-600' : 'pi pi-exclamation-triangle text-amber-500'"></i>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-semibold billing-text-muted uppercase tracking-wide mb-0.5">Payment Method</div>
                <div v-if="hasPaymentMethod" class="font-bold billing-text-primary text-sm">Card on file</div>
                <div v-else class="font-bold text-amber-500 text-sm">No card added</div>
                <div class="text-xs billing-text-muted mt-0.5">
                  <span v-if="hasPaymentMethod">Card ending in &bull;&bull;&bull;&bull; &middot; Secured by Stripe</span>
                  <span v-else>Required to subscribe</span>
                </div>
              </div>
              <Button
                :label="hasPaymentMethod ? 'Manage' : 'Add Card'"
                :icon="hasPaymentMethod ? 'pi pi-cog' : 'pi pi-plus'"
                size="small"
                :severity="hasPaymentMethod ? 'secondary' : 'warning'"
                outlined
                @click="$router.push({ name: 'employer.payment-method' })"
              />
            </div>
          </div>

          <!-- ── Plans ── -->
          <div>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-bold billing-text-primary">Available Plans</h2>
              <span v-if="currencyInfo?.currency_code" class="text-xs billing-text-muted billing-badge px-3 py-1 rounded-full">
                Prices in {{ currencyInfo.symbol }} {{ currencyInfo.currency_code }}
              </span>
            </div>

            <div v-if="plans.length === 0" class="billing-card rounded-2xl p-10 text-center billing-text-muted">
              <i class="pi pi-info-circle text-3xl mb-2 block"></i>
              <p class="text-sm">Set your billing region above to see available plans.</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div
                v-for="p in plans"
                :key="planId(p)"
                class="billing-plan-card relative flex flex-col rounded-2xl border-2 shadow-sm cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
                :class="{
                  'border-blue-500 ring-2 ring-blue-200': selectedPriceId === planId(p) && !isCurrent(p),
                  'border-green-500 ring-2 ring-green-200': isCurrent(p),
                  'billing-card-border': selectedPriceId !== planId(p) && !isCurrent(p),
                }"
                @click="selectedPriceId = planId(p)"
              >
                <div class="h-1 w-full rounded-t-2xl" :class="p.is_trial ? 'bg-gradient-to-r from-emerald-400 to-teal-500' : 'bg-gradient-to-r from-blue-500 to-indigo-600'"></div>

                <div v-if="isCurrent(p)" class="absolute top-3 right-3 rounded-full bg-green-500 px-2.5 py-0.5 text-[10px] font-bold text-white uppercase shadow">Current</div>
                <div v-else-if="p.is_trial" class="absolute top-3 right-3 rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-bold text-white uppercase shadow">7 Days Trial</div>

                <div class="flex flex-col flex-1 p-5">
                  <div class="text-sm font-bold billing-text-primary leading-snug mb-3 pr-16">{{ planName(p) }}</div>

                  <div class="mb-4">
                    <template v-if="p.is_trial">
                      <div class="text-3xl font-extrabold text-emerald-500 leading-none">Free</div>
                      <div class="text-xs billing-text-muted mt-1">7 Days Trial · No card required</div>
                    </template>
                    <template v-else>
                      <div class="flex items-baseline gap-0.5">
                        <span class="text-sm font-semibold billing-text-muted">{{ planCurrencySymbol(p) }}</span>
                        <span class="text-3xl font-extrabold billing-text-primary leading-none">{{ planPriceLabel(p) }}</span>
                      </div>
                      <div class="text-xs billing-text-muted mt-1">{{ planInterval(p) }}</div>
                    </template>
                  </div>

                  <ul class="space-y-1.5 flex-1 mb-5">
                    <li v-for="(f, i) in planFeatures(p)" :key="i" class="flex items-start gap-2 text-xs billing-text-secondary">
                      <i class="pi pi-check text-blue-500 mt-0.5 flex-shrink-0 text-[10px]"></i>
                      <span>{{ f }}</span>
                    </li>
                  </ul>

                  <div class="flex items-center justify-center gap-2 rounded-xl py-2 text-sm font-semibold transition-colors"
                       :class="{
                         'bg-green-100 text-green-700': isCurrent(p),
                         'bg-blue-600 text-white': selectedPriceId === planId(p) && !isCurrent(p),
                         'billing-select-idle': selectedPriceId !== planId(p) && !isCurrent(p),
                       }">
                    <i class="pi text-sm" :class="{ 'pi-check-circle': isCurrent(p), 'pi-check': selectedPriceId === planId(p) && !isCurrent(p), 'pi-arrow-right': selectedPriceId !== planId(p) && !isCurrent(p) }"></i>
                    <span>{{ isCurrent(p) ? 'Current Plan' : selectedPriceId === planId(p) ? 'Selected' : 'Select' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── Checkout bar ── -->
          <div v-if="selectedPrice" class="billing-card rounded-2xl shadow-sm p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl billing-icon-accent flex-shrink-0">
                  <i class="pi pi-shopping-cart text-blue-600 text-xl"></i>
                </div>
                <div>
                  <div class="text-xs font-semibold billing-text-muted uppercase tracking-wide mb-0.5">Order Summary</div>
                  <div class="font-bold billing-text-primary">{{ planName(selectedPrice) }}</div>
                  <div class="text-sm billing-text-muted">
                    <template v-if="selectedPrice.is_trial">Free · 7 Days Trial</template>
                    <template v-else>{{ planCurrencySymbol(selectedPrice) }}{{ planPriceLabel(selectedPrice) }} · {{ planInterval(selectedPrice) }}</template>
                    <span v-if="currencyInfo?.rate_is_stale" class="text-amber-500 ml-1">(estimated)</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                  <div class="text-xs billing-text-muted">Total due</div>
                  <div class="text-2xl font-extrabold billing-text-primary">
                    <template v-if="selectedPrice.is_trial">Free</template>
                    <template v-else>{{ planCurrencySymbol(selectedPrice) }}{{ planPriceLabel(selectedPrice) }}</template>
                  </div>
                </div>
                <Button
                  :label="subscription ? 'Update Plan' : (selectedPrice.is_trial ? 'Start Free Trial (No Card Required)' : 'Subscribe Now')"
                  :icon="selectedPrice.is_trial ? 'pi pi-play' : 'pi pi-check-circle'"
                  :loading="loading"
                  :disabled="!selectedPriceId || checkoutBlocked || isCurrent(selectedPrice)"
                  class="!px-6 !py-3 !font-bold"
                  @click="startSubscription"
                />
              </div>
            </div>
          </div>

          <!-- ── Invoices ── -->
          <div class="billing-card rounded-2xl shadow-sm overflow-hidden">
            <button class="w-full flex items-center justify-between px-5 py-4 text-left billing-hover transition-colors" @click="showInvoices = !showInvoices">
              <div class="flex items-center gap-3">
                <i class="pi pi-file-pdf billing-text-muted"></i>
                <span class="font-semibold billing-text-primary">Invoice History</span>
                <span v-if="invoices.length" class="rounded-full bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5">{{ invoices.length }}</span>
              </div>
              <i class="pi billing-text-muted" :class="showInvoices ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
            </button>
            <div v-if="showInvoices" class="billing-border-top">
              <div v-if="invoices.length === 0" class="py-10 text-center text-sm billing-text-muted">
                <i class="pi pi-inbox text-3xl mb-2 block"></i>No invoices yet
              </div>
              <div v-else>
                <div v-for="invoice in invoices" :key="invoice.id" class="flex items-center justify-between px-5 py-3 billing-hover billing-border-bottom transition-colors">
                  <div>
                    <div class="font-semibold text-sm billing-text-primary">
                      {{ currencyInfo?.symbol || '' }}{{ formatMinor(invoice.amount_cents, invoice.currency_code) }}
                      <span class="text-xs billing-text-muted ml-1">{{ invoice.currency_code }}</span>
                    </div>
                    <div class="text-xs billing-text-muted mt-0.5">{{ fmtDate(invoice.issued_at) }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <Tag :value="invoice.status.toUpperCase()" :severity="invoice.status === 'paid' ? 'success' : invoice.status === 'pending' ? 'warning' : 'danger'" class="text-[10px]" />
                    <Button icon="pi pi-download" size="small" text severity="secondary" class="!p-1" title="Download invoice" @click.stop="downloadInvoice(invoice.id)" />
                  </div>
                </div>
              </div>
            </div>
          </div>

        </template>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* All billing page colors use CSS variables — no raw Tailwind color classes
   that get overridden by the global .dark .bg-white { !important } rules */

.billing-page {
  background-color: var(--c-bg-app);
  color: var(--c-text-primary);
}

.billing-card {
  background-color: var(--c-bg-surface);
  border: 1px solid var(--c-border);
}

.billing-card-border {
  border-color: var(--c-border);
}

.billing-plan-card {
  background-color: var(--c-bg-surface);
}

.billing-text-primary {
  color: var(--c-text-primary);
}

.billing-text-secondary {
  color: var(--c-text-secondary);
}

.billing-text-muted {
  color: var(--c-text-tertiary);
}

.billing-icon-bg {
  background-color: var(--c-bg-sidebar);
}

.billing-icon-accent {
  background-color: var(--c-accent-light);
}

.billing-icon-success {
  background-color: #dcfce7;
}
.dark .billing-icon-success {
  background-color: rgba(22, 163, 74, 0.2);
}

.billing-icon-warn {
  background-color: #fef3c7;
}
.dark .billing-icon-warn {
  background-color: rgba(202, 138, 4, 0.2);
}

.billing-badge {
  background-color: var(--c-bg-sidebar);
}

.billing-select-idle {
  background-color: var(--c-bg-sidebar);
  color: var(--c-text-secondary);
}

.billing-hover:hover {
  background-color: var(--c-bg-sidebar);
}

.billing-border-top {
  border-top: 1px solid var(--c-border);
}

.billing-border-bottom {
  border-bottom: 1px solid var(--c-border);
}

/* Plan card selected state ring fix */
.ring-blue-200 {
  --tw-ring-color: rgba(191, 219, 254, 0.5);
}
.ring-green-200 {
  --tw-ring-color: rgba(187, 247, 208, 0.5);
}
</style>
