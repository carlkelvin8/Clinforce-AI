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
import InputNumber from 'primevue/inputnumber'
import Swal from 'sweetalert2'

import { countries } from '@/lib/countries'

import ToggleSwitch from 'primevue/toggleswitch';

// Sessions
const sessions = ref([])
const sessionsLoading = ref(false)

async function loadSessions() {
  sessionsLoading.value = true
  try {
    const res = await api.get('/user/sessions')
    sessions.value = res.data?.data ?? res.data ?? []
  } catch {} finally { sessionsLoading.value = false }
}

async function revokeSession(tokenId) {
  try {
    await api.delete(`/user/sessions/${tokenId}`)
    sessions.value = sessions.value.filter(s => s.id !== tokenId)
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not revoke session' })
  }
}

async function revokeAllSessions() {
  const { isConfirmed } = await Swal.fire({ icon: 'warning', title: 'Revoke all other sessions?', showCancelButton: true })
  if (!isConfirmed) return
  try {
    await api.delete('/user/sessions')
    sessions.value = sessions.value.filter(s => s.is_current)
    await Swal.fire({ icon: 'success', title: 'Done', timer: 1500, showConfirmButton: false })
  } catch {}
}

// 2FA
const twoFa = ref({ enabled: false, qr_code_url: null, secret: null })
const twoFaLoading = ref(false)
const twoFaSaving = ref(false)
const twoFaCode = ref('')
const twoFaStep = ref('status') // 'status' | 'setup' | 'verify'

async function load2Fa() {
  twoFaLoading.value = true
  try {
    const res = await api.get('/2fa/status')
    twoFa.value = res.data?.data ?? res.data
  } catch {}
  finally { twoFaLoading.value = false }
}

async function setup2Fa() {
  twoFaSaving.value = true
  try {
    const res = await api.post('/2fa/setup')
    twoFa.value = { ...twoFa.value, ...(res.data?.data ?? res.data) }
    twoFaStep.value = 'verify'
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not set up 2FA' })
  } finally { twoFaSaving.value = false }
}

async function enable2Fa() {
  twoFaSaving.value = true
  try {
    await api.post('/2fa/enable', { code: twoFaCode.value })
    twoFa.value.enabled = true
    twoFaStep.value = 'status'
    twoFaCode.value = ''
    await Swal.fire({ icon: 'success', title: '2FA Enabled', text: 'Two-factor authentication is now active.', timer: 2000, showConfirmButton: false })
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Invalid code', text: e?.response?.data?.message || 'Verification failed' })
  } finally { twoFaSaving.value = false }
}

async function disable2Fa() {
  const { isConfirmed } = await Swal.fire({ icon: 'warning', title: 'Disable 2FA?', text: 'This will remove two-factor authentication from your account.', showCancelButton: true })
  if (!isConfirmed) return
  twoFaSaving.value = true
  try {
    await api.post('/2fa/disable')
    twoFa.value.enabled = false
    twoFaStep.value = 'status'
    await Swal.fire({ icon: 'success', title: 'Disabled', timer: 1500, showConfirmButton: false })
  } catch (e) {
    await Swal.fire({ icon: 'error', title: 'Failed', text: e?.response?.data?.message || 'Could not disable 2FA' })
  } finally { twoFaSaving.value = false }
}

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
  data_retention_days: null,
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
      form.value.data_retention_days = p.data_retention_days ?? null
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
  load2Fa()
  loadSessions()
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
                  <div class="space-y-1.5 md:col-span-2">
                    <label class="text-sm font-semibold text-slate-700">Data Retention</label>
                    <div class="flex items-center gap-3">
                      <InputNumber v-model="form.data_retention_days" :min="7" :max="3650" placeholder="90" class="w-40" />
                      <span class="text-sm text-slate-500">days — auto-delete rejected applications after this period</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Leave blank to use the platform default (90 days). Set to 0 to disable auto-deletion.</p>
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

          <!-- Sessions Card -->
          <Card class="h-full">
            <template #title>Active Sessions</template>
            <template #content>
              <div v-if="sessionsLoading" class="py-4 text-center text-slate-400"><i class="pi pi-spin pi-spinner"></i></div>
              <div v-else class="space-y-3">
                <div v-for="s in sessions" :key="s.id"
                  class="flex items-center justify-between p-3 rounded-xl border"
                  :class="s.is_current ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50'">
                  <div>
                    <div class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                      {{ s.name }}
                      <span v-if="s.is_current" class="text-[10px] bg-blue-600 text-white px-1.5 py-0.5 rounded-full">Current</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                      Last active: {{ s.last_used_at ? new Date(s.last_used_at).toLocaleString() : 'Never' }}
                    </div>
                  </div>
                  <Button v-if="!s.is_current" icon="pi pi-times" size="small" severity="danger" outlined
                    v-tooltip="'Revoke session'" @click="revokeSession(s.id)" />
                </div>
                <div v-if="!sessions.length" class="text-sm text-slate-400 py-2">No active sessions.</div>
                <div class="flex justify-end pt-1">
                  <Button label="Revoke all other sessions" size="small" severity="secondary" outlined
                    :disabled="sessions.filter(s => !s.is_current).length === 0"
                    @click="revokeAllSessions" />
                </div>
              </div>
            </template>
          </Card>

          <!-- 2FA Card -->
          <Card class="h-full">
            <template #title>Two-Factor Authentication</template>
            <template #content>
              <div v-if="twoFaLoading" class="py-4 text-center text-slate-400">
                <i class="pi pi-spin pi-spinner"></i>
              </div>
              <div v-else class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                  <div>
                    <div class="font-semibold text-slate-900">{{ twoFa.enabled ? '2FA is enabled' : '2FA is disabled' }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ twoFa.enabled ? 'Your account is protected with an authenticator app.' : 'Add an extra layer of security to your account.' }}</div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="text-xs" :class="twoFa.enabled ? 'text-emerald-600' : 'text-slate-400'">{{ twoFa.enabled ? 'Active' : 'Inactive' }}</span>
                    <span class="w-2.5 h-2.5 rounded-full" :class="twoFa.enabled ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                  </div>
                </div>

                <!-- Setup flow -->
                <div v-if="twoFaStep === 'verify' && twoFa.qr_code_url" class="space-y-4">
                  <p class="text-sm text-slate-700">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):</p>
                  <div class="flex justify-center">
                    <img :src="twoFa.qr_code_url" alt="2FA QR Code" class="w-48 h-48 border border-slate-200 rounded-xl p-2 bg-white" />
                  </div>
                  <div v-if="twoFa.secret" class="text-center">
                    <p class="text-xs text-slate-500 mb-1">Or enter this key manually:</p>
                    <code class="text-xs bg-slate-100 px-3 py-1.5 rounded-lg font-mono">{{ twoFa.secret }}</code>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-sm font-semibold text-slate-700">Enter 6-digit code to verify</label>
                    <InputText v-model="twoFaCode" placeholder="000000" maxlength="6" class="w-full text-center tracking-widest text-lg" />
                  </div>
                  <div class="flex gap-2">
                    <Button label="Cancel" text severity="secondary" @click="twoFaStep = 'status'" />
                    <Button label="Enable 2FA" icon="pi pi-shield" :loading="twoFaSaving" @click="enable2Fa" />
                  </div>
                </div>

                <div v-else class="flex gap-2">
                  <Button v-if="!twoFa.enabled" label="Set up 2FA" icon="pi pi-shield" :loading="twoFaSaving" @click="setup2Fa" />
                  <Button v-else label="Disable 2FA" icon="pi pi-shield" severity="danger" outlined :loading="twoFaSaving" @click="disable2Fa" />
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
