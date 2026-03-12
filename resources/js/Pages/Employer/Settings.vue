<script setup>
import { onMounted, ref, watch } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import { me } from '@/lib/auth'
import api from '@/lib/api'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Message from 'primevue/message'
import Swal from 'sweetalert2'

import { countries } from '@/lib/countries'

import ToggleSwitch from 'primevue/toggleswitch';

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const checkoutBlocked = ref(false)
const currencyInfo = ref(null)
const plans = ref([])

const authUser = ref(null)

const notif = ref({
  frequency: 'immediate',
})
const notifLoading = ref(false)
const notifSaving = ref(false)
const frequencyOptions = [
  { label: 'Immediate', value: 'immediate' },
  { label: 'Daily digest', value: 'daily' },
  { label: 'Weekly summary', value: 'weekly' },
]

const businessTypes = [
  { label: 'Clinic', value: 'clinic' },
  { label: 'Hospital', value: 'hospital' },
  { label: 'Medical Agency', value: 'medical_agency' },
  { label: 'Other', value: 'other' },
];
let fallbackAlerted = false;

async function ensureBillingContext() {
  checkoutBlocked.value = false;
  try {
    const res = await api.get("/billing/currency");
    const data = res.data?.data || res.data || null;
    if (!data) {
      throw new Error("Missing billing currency payload");
    }

    currencyInfo.value = data;
    plans.value = Array.isArray(data.converted_prices) ? data.converted_prices : [];

    if (data.fallback_applied && !fallbackAlerted) {
      fallbackAlerted = true;
      await Swal.fire({
        icon: "info",
        title: "Currency adjusted",
        text:
          "Your preferred currency is not supported by the payment provider. Prices are shown in a supported currency instead.",
      });
    }

    if (!data.conversion_available) {
      // ...
    }

  } catch (e) {
    // swallow
  } finally {
    // no-op
  }
}

const form = ref({
  business_name: '',
  business_type: 'clinic',
  website_url: '',
  country: '',
  billing_currency_code: '',
  state: '',
  city: '',
  zip_code: '',
  tax_id: '',
  address_line: '',
})

const initializingLocation = ref(true)

const billingCurrencyDisplay = ref('USD')

watch(
  () => form.value.country,
  async (newCountry, oldCountry) => {
    if (!newCountry && !oldCountry) {
      form.value.billing_currency_code = 'USD'
      billingCurrencyDisplay.value = 'USD'
      return
    }

    const country = (newCountry || '').toString()
    const nextCurrency = country.toLowerCase() === 'philippines' ? 'PHP' : 'USD'
    form.value.billing_currency_code = nextCurrency
    billingCurrencyDisplay.value = nextCurrency

    if (initializingLocation.value) {
      return
    }

    await Swal.fire({
      icon: 'info',
      title: 'Billing currency updated',
      text: nextCurrency === 'PHP' ? 'Currency automatically set to PHP' : 'Currency automatically set to USD',
      timer: 2000,
      showConfirmButton: false,
    })
  }
)

async function loadMe() {
  try {
    const u = await me()
    authUser.value = u
  } catch {}
}

async function loadProfile() {
  loading.value = true
  error.value = ''
  try {
    const res = await api.get('/me/employer')
    const p = res?.data?.data
    if (p) {
      form.value.business_name = p.business_name || ''
      form.value.business_type = p.business_type || 'clinic'
      form.value.website_url = p.website_url || ''
      form.value.country = p.country || ''
      form.value.billing_currency_code = p.billing_currency_code || 'USD'
      billingCurrencyDisplay.value = form.value.billing_currency_code || 'USD'
      form.value.state = p.state || ''
      form.value.city = p.city || ''
      form.value.zip_code = p.zip_code || ''
      form.value.tax_id = p.tax_id || ''
      form.value.address_line = p.address_line || ''
    }
  } catch (e) {
    error.value = e?.__payload?.message || e?.message || 'Failed to load profile'
  } finally {
    loading.value = false
    initializingLocation.value = false
  }
}

async function loadNotificationPrefs() {
  notifLoading.value = true
  try {
    const res = await api.get('/notifications/preferences')
    const p = res?.data?.data
    if (p && typeof p.frequency === 'string') {
      notif.value.frequency = p.frequency
    } else {
      notif.value.frequency = 'immediate'
    }
  } catch (e) {
    // keep silent, non-blocking
  } finally {
    notifLoading.value = false
  }
}

async function saveNotificationPrefs() {
  notifSaving.value = true
  error.value = ''
  success.value = ''
  try {
    const payload = { frequency: notif.value.frequency }
    const res = await api.put('/notifications/preferences', payload)
    success.value = res?.data?.message || 'Notification preferences saved'
    await Swal.fire({
      icon: 'success',
      title: 'Preferences saved',
      text: 'Your notification frequency has been updated.',
      timer: 1800,
      showConfirmButton: false,
    })
  } catch (e) {
    const msg = e?.__payload?.message || e?.message || 'Failed to save notification preferences'
    error.value = msg
    await Swal.fire({
      icon: 'error',
      title: 'Save failed',
      text: msg,
    })
  } finally {
    notifSaving.value = false
  }
}

async function saveProfile() {
  if (!form.value.business_name || form.value.business_name.trim().length < 2) {
    await Swal.fire({
      icon: 'error',
      title: 'Invalid company name',
      text: 'Business name is required and must be at least 2 characters.',
    })
    return
  }
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const res = await api.put('/me/employer', form.value)
    success.value = res?.data?.message || 'Profile saved'
    await Swal.fire({
      icon: 'success',
      title: 'Profile saved',
      text: 'Your employer settings have been updated.',
      timer: 2000,
      showConfirmButton: false,
    })
  } catch (e) {
    const msg = e?.__payload?.message || e?.message || 'Failed to save profile'
    error.value = msg
    await Swal.fire({
      icon: 'error',
      title: 'Save failed',
      text: msg,
    })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadMe()
  loadProfile()
  loadNotificationPrefs()
  ensureBillingContext()
})
</script>
<template>
  <AppLayout>
    <div class="max-w-5xl mx-auto px-2 md:px-3 py-4 md:py-5 space-y-3 employer-settings-page">
      <div class="bg-white rounded-2xl p-3 md:p-4 relative overflow-hidden settings-header-card">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-70"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="space-y-1">
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 m-0">Employer Settings</h1>
            <p class="text-slate-600 text-sm md:text-base">Manage your brand, company profile, and billing details.</p>
          </div>
          <div class="hidden md:flex items-center gap-2 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span>Changes save instantly to your billing profile</span>
          </div>
        </div>
      </div>

      <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
      <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

      <div class="space-y-4 w-full">
        <Card class="h-full">
            <template #title>Company & Billing Details</template>
            <template #content>
              <div class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Company Name<span class="text-red-500">*</span></label>
                    <InputText v-model="form.business_name" class="w-full" placeholder="ACME Health Group" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Business Type<span class="text-red-500">*</span></label>
                    <Select
                      v-model="form.business_type"
                      :options="businessTypes"
                      optionLabel="label"
                      optionValue="value"
                      class="w-full"
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Country</label>
                    <Select
                      v-model="form.country"
                      :options="countries"
                      optionLabel="label"
                      optionValue="value"
                      class="w-full"
                      placeholder="Select country"
                    />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Billing currency</label>
                    <InputText :value="billingCurrencyDisplay" class="w-full" readonly />
                    <p class="text-[11px] text-slate-500 mt-1">
                      Automatically set based on selected country: PHP for Philippines, otherwise USD.
                    </p>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">State / Province</label>
                    <InputText v-model="form.state" class="w-full" placeholder="California" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">City</label>
                    <InputText v-model="form.city" class="w-full" placeholder="Los Angeles" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">ZIP / Postal code</label>
                    <InputText v-model="form.zip_code" class="w-full" placeholder="90001" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Tax identification number</label>
                    <InputText v-model="form.tax_id" class="w-full" placeholder="TIN / VAT / Tax ID" />
                  </div>
                  <div class="space-y-1.5 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Address</label>
                    <InputText v-model="form.address_line" class="w-full" placeholder="123 Main St" />
                  </div>
                </div>

                <div class="flex justify-end">
                  <Button label="Save changes" icon="pi pi-check" :loading="saving" @click="saveProfile" />
                </div>
              </div>
            </template>
          </Card>

          <Card class="h-full">
            <template #title>Notification Settings</template>
            <template #content>
              <div class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div class="space-y-1.5 md:col-span-1">
                    <label class="text-sm font-semibold text-slate-700">Notification frequency</label>
                    <Select
                      v-model="notif.frequency"
                      :options="frequencyOptions"
                      optionLabel="label"
                      optionValue="value"
                      class="w-full"
                      :loading="notifLoading"
                    />
                    <p class="text-[11px] text-slate-500 mt-1">
                      Choose how often to receive notifications: immediate, daily digest, or weekly summary.
                    </p>
                  </div>
                </div>
                <div class="flex justify-end">
                  <Button label="Save preferences" icon="pi pi-save" :loading="notifSaving" @click="saveNotificationPrefs" />
                </div>
              </div>
            </template>
          </Card>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.employer-settings-page :deep(.p-card),
.employer-settings-page .settings-header-card {
  border-width: 1px !important;
  border-style: solid !important;
  border-color: #e5e7eb !important; /* thin, light border */
  box-shadow: none !important;
}

.employer-settings-page :deep(.p-inputtext),
.employer-settings-page :deep(.p-select),
.employer-settings-page :deep(input),
.employer-settings-page :deep(textarea) {
  border-width: 1px !important;
  border-style: solid !important;
  border-color: #e5e7eb !important; /* thin, light border */
  box-shadow: none !important;
}
</style>
