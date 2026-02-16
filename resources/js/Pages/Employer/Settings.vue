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
import Avatar from 'primevue/avatar'
import Swal from 'sweetalert2'

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

const authUser = ref(null)
const DEFAULT_EMPLOYER_LOGO = '/assets/brand/default-hospital-logo.svg'
const logoUrl = ref(DEFAULT_EMPLOYER_LOGO)

const businessTypes = [
  { label: 'Clinic', value: 'clinic' },
  { label: 'Hospital', value: 'hospital' },
  { label: 'Medical Agency', value: 'medical_agency' },
  { label: 'Other', value: 'other' },
]let fallbackAlerted = false;

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

const countries = [
  { label: 'Afghanistan', value: 'AF' },
  { label: 'Albania', value: 'AL' },
  { label: 'Algeria', value: 'DZ' },
  { label: 'Andorra', value: 'AD' },
  { label: 'Angola', value: 'AO' },
  { label: 'Antigua and Barbuda', value: 'AG' },
  { label: 'Argentina', value: 'AR' },
  { label: 'Armenia', value: 'AM' },
  { label: 'Australia', value: 'AU' },
  { label: 'Austria', value: 'AT' },
  { label: 'Azerbaijan', value: 'AZ' },
  { label: 'Bahamas', value: 'BS' },
  { label: 'Bahrain', value: 'BH' },
  { label: 'Bangladesh', value: 'BD' },
  { label: 'Barbados', value: 'BB' },
  { label: 'Belarus', value: 'BY' },
  { label: 'Belgium', value: 'BE' },
  { label: 'Belize', value: 'BZ' },
  { label: 'Benin', value: 'BJ' },
  { label: 'Bhutan', value: 'BT' },
  { label: 'Bolivia', value: 'BO' },
  { label: 'Bosnia and Herzegovina', value: 'BA' },
  { label: 'Botswana', value: 'BW' },
  { label: 'Brazil', value: 'BR' },
  { label: 'Brunei Darussalam', value: 'BN' },
  { label: 'Bulgaria', value: 'BG' },
  { label: 'Burkina Faso', value: 'BF' },
  { label: 'Burundi', value: 'BI' },
  { label: 'Cambodia', value: 'KH' },
  { label: 'Cameroon', value: 'CM' },
  { label: 'Canada', value: 'CA' },
  { label: 'Cape Verde', value: 'CV' },
  { label: 'Central African Republic', value: 'CF' },
  { label: 'Chad', value: 'TD' },
  { label: 'Chile', value: 'CL' },
  { label: 'China', value: 'CN' },
  { label: 'Colombia', value: 'CO' },
  { label: 'Comoros', value: 'KM' },
  { label: 'Congo', value: 'CG' },
  { label: 'Congo, Democratic Republic of the', value: 'CD' },
  { label: 'Costa Rica', value: 'CR' },
  { label: "Côte d'Ivoire", value: 'CI' },
  { label: 'Croatia', value: 'HR' },
  { label: 'Cuba', value: 'CU' },
  { label: 'Cyprus', value: 'CY' },
  { label: 'Czech Republic', value: 'CZ' },
  { label: 'Denmark', value: 'DK' },
  { label: 'Djibouti', value: 'DJ' },
  { label: 'Dominica', value: 'DM' },
  { label: 'Dominican Republic', value: 'DO' },
  { label: 'Ecuador', value: 'EC' },
  { label: 'Egypt', value: 'EG' },
  { label: 'El Salvador', value: 'SV' },
  { label: 'Equatorial Guinea', value: 'GQ' },
  { label: 'Eritrea', value: 'ER' },
  { label: 'Estonia', value: 'EE' },
  { label: 'Eswatini', value: 'SZ' },
  { label: 'Ethiopia', value: 'ET' },
  { label: 'Fiji', value: 'FJ' },
  { label: 'Finland', value: 'FI' },
  { label: 'France', value: 'FR' },
  { label: 'Gabon', value: 'GA' },
  { label: 'Gambia', value: 'GM' },
  { label: 'Georgia', value: 'GE' },
  { label: 'Germany', value: 'DE' },
  { label: 'Ghana', value: 'GH' },
  { label: 'Greece', value: 'GR' },
  { label: 'Grenada', value: 'GD' },
  { label: 'Guatemala', value: 'GT' },
  { label: 'Guinea', value: 'GN' },
  { label: 'Guinea-Bissau', value: 'GW' },
  { label: 'Guyana', value: 'GY' },
  { label: 'Haiti', value: 'HT' },
  { label: 'Honduras', value: 'HN' },
  { label: 'Hungary', value: 'HU' },
  { label: 'Iceland', value: 'IS' },
  { label: 'India', value: 'IN' },
  { label: 'Indonesia', value: 'ID' },
  { label: 'Iran', value: 'IR' },
  { label: 'Iraq', value: 'IQ' },
  { label: 'Ireland', value: 'IE' },
  { label: 'Israel', value: 'IL' },
  { label: 'Italy', value: 'IT' },
  { label: 'Jamaica', value: 'JM' },
  { label: 'Japan', value: 'JP' },
  { label: 'Jordan', value: 'JO' },
  { label: 'Kazakhstan', value: 'KZ' },
  { label: 'Kenya', value: 'KE' },
  { label: 'Kiribati', value: 'KI' },
  { label: 'Kuwait', value: 'KW' },
  { label: 'Kyrgyzstan', value: 'KG' },
  { label: 'Lao People\'s Democratic Republic', value: 'LA' },
  { label: 'Latvia', value: 'LV' },
  { label: 'Lebanon', value: 'LB' },
  { label: 'Lesotho', value: 'LS' },
  { label: 'Liberia', value: 'LR' },
  { label: 'Libya', value: 'LY' },
  { label: 'Liechtenstein', value: 'LI' },
  { label: 'Lithuania', value: 'LT' },
  { label: 'Luxembourg', value: 'LU' },
  { label: 'Madagascar', value: 'MG' },
  { label: 'Malawi', value: 'MW' },
  { label: 'Malaysia', value: 'MY' },
  { label: 'Maldives', value: 'MV' },
  { label: 'Mali', value: 'ML' },
  { label: 'Malta', value: 'MT' },
  { label: 'Marshall Islands', value: 'MH' },
  { label: 'Mauritania', value: 'MR' },
  { label: 'Mauritius', value: 'MU' },
  { label: 'Mexico', value: 'MX' },
  { label: 'Micronesia', value: 'FM' },
  { label: 'Moldova', value: 'MD' },
  { label: 'Monaco', value: 'MC' },
  { label: 'Mongolia', value: 'MN' },
  { label: 'Montenegro', value: 'ME' },
  { label: 'Morocco', value: 'MA' },
  { label: 'Mozambique', value: 'MZ' },
  { label: 'Myanmar', value: 'MM' },
  { label: 'Namibia', value: 'NA' },
  { label: 'Nauru', value: 'NR' },
  { label: 'Nepal', value: 'NP' },
  { label: 'Netherlands', value: 'NL' },
  { label: 'New Zealand', value: 'NZ' },
  { label: 'Nicaragua', value: 'NI' },
  { label: 'Niger', value: 'NE' },
  { label: 'Nigeria', value: 'NG' },
  { label: 'North Macedonia', value: 'MK' },
  { label: 'Norway', value: 'NO' },
  { label: 'Oman', value: 'OM' },
  { label: 'Pakistan', value: 'PK' },
  { label: 'Palau', value: 'PW' },
  { label: 'Panama', value: 'PA' },
  { label: 'Papua New Guinea', value: 'PG' },
  { label: 'Paraguay', value: 'PY' },
  { label: 'Peru', value: 'PE' },
  { label: 'Philippines', value: 'PH' },
  { label: 'Poland', value: 'PL' },
  { label: 'Portugal', value: 'PT' },
  { label: 'Qatar', value: 'QA' },
  { label: 'Romania', value: 'RO' },
  { label: 'Russian Federation', value: 'RU' },
  { label: 'Rwanda', value: 'RW' },
  { label: 'Saint Kitts and Nevis', value: 'KN' },
  { label: 'Saint Lucia', value: 'LC' },
  { label: 'Saint Vincent and the Grenadines', value: 'VC' },
  { label: 'Samoa', value: 'WS' },
  { label: 'San Marino', value: 'SM' },
  { label: 'Sao Tome and Principe', value: 'ST' },
  { label: 'Saudi Arabia', value: 'SA' },
  { label: 'Senegal', value: 'SN' },
  { label: 'Serbia', value: 'RS' },
  { label: 'Seychelles', value: 'SC' },
  { label: 'Sierra Leone', value: 'SL' },
  { label: 'Singapore', value: 'SG' },
  { label: 'Slovakia', value: 'SK' },
  { label: 'Slovenia', value: 'SI' },
  { label: 'Solomon Islands', value: 'SB' },
  { label: 'Somalia', value: 'SO' },
  { label: 'South Africa', value: 'ZA' },
  { label: 'South Sudan', value: 'SS' },
  { label: 'Spain', value: 'ES' },
  { label: 'Sri Lanka', value: 'LK' },
  { label: 'Sudan', value: 'SD' },
  { label: 'Suriname', value: 'SR' },
  { label: 'Sweden', value: 'SE' },
  { label: 'Switzerland', value: 'CH' },
  { label: 'Syrian Arab Republic', value: 'SY' },
  { label: 'Taiwan', value: 'TW' },
  { label: 'Tajikistan', value: 'TJ' },
  { label: 'Tanzania', value: 'TZ' },
  { label: 'Thailand', value: 'TH' },
  { label: 'Timor-Leste', value: 'TL' },
  { label: 'Togo', value: 'TG' },
  { label: 'Tonga', value: 'TO' },
  { label: 'Trinidad and Tobago', value: 'TT' },
  { label: 'Tunisia', value: 'TN' },
  { label: 'Turkey', value: 'TR' },
  { label: 'Turkmenistan', value: 'TM' },
  { label: 'Tuvalu', value: 'TV' },
  { label: 'Uganda', value: 'UG' },
  { label: 'Ukraine', value: 'UA' },
  { label: 'United Arab Emirates', value: 'AE' },
  { label: 'United Kingdom', value: 'GB' },
  { label: 'United States of America', value: 'US' },
  { label: 'Uruguay', value: 'UY' },
  { label: 'Uzbekistan', value: 'UZ' },
  { label: 'Vanuatu', value: 'VU' },
  { label: 'Venezuela', value: 'VE' },
  { label: 'Viet Nam', value: 'VN' },
  { label: 'Yemen', value: 'YE' },
  { label: 'Zambia', value: 'ZM' },
  { label: 'Zimbabwe', value: 'ZW' },
]

const form = ref({
  business_name: '',
  business_type: 'clinic',
  website_url: '',
  country_code: '',
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
  () => form.value.country_code,
  async (newCode, oldCode) => {
    if (!newCode && !oldCode) {
      form.value.billing_currency_code = 'USD'
      billingCurrencyDisplay.value = 'USD'
      return
    }

    const code = (newCode || '').toString().toUpperCase()
    const nextCurrency = code === 'PH' ? 'PHP' : 'USD'
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
      form.value.country_code = p.country_code || ''
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
  await loadProfile()
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

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <div class="lg:col-span-1">
          <Card class="h-full flex flex-col">
   
            <template #content>
              <div class="flex flex-col items-center gap-4 py-2">
                <Avatar
                  :image="logoUrl"
                  shape="square"
                  class="rounded-2xl bg-slate-50 text-slate-700 ring-1 ring-slate-200"
                  :style="{ width: '196px', height: '196px' }"
                />
                <p class="text-xs text-slate-500 text-center max-w-xs">
                  Default confidential hospital logo used for all employers.
                </p>
              </div>
            </template>
          </Card>
        </div>

        <div class="space-y-4 lg:col-span-2">
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
                      v-model="form.country_code"
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
        </div>
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
