<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import { http } from '@/lib/http'
import { me } from '@/lib/auth'
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Avatar from 'primevue/avatar';
import Toast from 'primevue/toast';
import FileUpload from 'primevue/fileupload';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

const toast = useToast();
const user = ref(null)
const loading = ref(false)
const saving = ref(false)

const form = ref({
  email: '',
  phone: '',
  password: '',
  password_confirmation: ''
})

const avatarPreview = ref(null)
const avatarFile = ref(null)
const fileUploadRef = ref(null)

// Cropper state
const showCropDialog = ref(false)
const cropImage = ref(null)
const cropperRef = ref(null)

const passwordStrength = computed(() => {
  const value = form.value.password || ''
  if (!value) return 0
  let score = 0
  if (value.length >= 6) score++
  if (value.length >= 10) score++
  if (/[A-Z]/.test(value) && /[0-9]/.test(value)) score++
  return Math.min(score, 3)
})

const passwordStrengthLabel = computed(() => {
  if (!form.value.password) return 'Enter a strong password.'
  if (passwordStrength.value === 1) return 'Weak password'
  if (passwordStrength.value === 2) return 'Good password'
  return 'Strong password'
})

const passwordStrengthWidth = computed(() => {
  if (!form.value.password) return '0%'
  if (passwordStrength.value === 1) return '33%'
  if (passwordStrength.value === 2) return '66%'
  return '100%'
})

const passwordStrengthClass = computed(() => {
  if (!form.value.password) return 'bg-slate-200'
  if (passwordStrength.value === 1) return 'bg-rose-500'
  if (passwordStrength.value === 2) return 'bg-amber-500'
  return 'bg-emerald-500'
})

async function fetchUser() {
  try {
    const u = await me()
    user.value = u
    if (u) {
      form.value.email = u.email || ''
      form.value.phone = u.phone || ''
      if (u.avatar_url) {
        avatarPreview.value = u.avatar_url
      }
    }
  } catch (e) {
    console.error("Failed to fetch user", e)
  }
}

onMounted(() => {
  fetchUser()
})

function onFileSelect(event) {
    const file = event.files[0];
    if (file) {
        // Create a blob URL for the cropper
        if (cropImage.value) {
            URL.revokeObjectURL(cropImage.value)
        }
        cropImage.value = URL.createObjectURL(file)
        showCropDialog.value = true
    }
}

function applyCrop() {
    const { canvas } = cropperRef.value.getResult();
    if (canvas) {
        canvas.toBlob((blob) => {
            // Create a new File object from the blob to mimic file selection
            const file = new File([blob], "avatar.png", { type: "image/png" });
            avatarFile.value = file;
            
            // Update preview
            if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
                URL.revokeObjectURL(avatarPreview.value)
            }
            avatarPreview.value = URL.createObjectURL(blob);
            
            showCropDialog.value = false;
            
            // Clear the file upload component so the same file can be selected again if needed
            if (fileUploadRef.value) {
                fileUploadRef.value.clear();
            }
        }, 'image/png');
    }
}

function cancelCrop() {
    showCropDialog.value = false;
    cropImage.value = null;
    // Clear the file upload component
    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }
}

function clearAvatar() {
  if (avatarPreview.value && avatarPreview.value.startsWith('blob:')) {
    URL.revokeObjectURL(avatarPreview.value)
  }
  avatarPreview.value = null
  avatarFile.value = null
}

async function saveSettings() {
  saving.value = true
  
  try {
    const formData = new FormData()
    formData.append('email', form.value.email || '')
    formData.append('phone', form.value.phone || '')
    
    if (form.value.password) {
      formData.append('password', form.value.password)
      formData.append('password_confirmation', form.value.password_confirmation)
    }

    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    
    // Use POST with _method=PUT for file uploads
    formData.append('_method', 'PUT')

    await http.post('/api/user/settings', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    await fetchUser()
    
    toast.add({ severity: 'success', summary: 'Success', detail: 'Settings updated successfully.', life: 3000 });
    
    // Clear password fields
    form.value.password = ''
    form.value.password_confirmation = ''
    avatarFile.value = null
  } catch (err) {
    console.error(err)
    const msg = err.response?.data?.message || 'Failed to update settings'
    
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 5000 });
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="min-h-screen">
      <div class="w-full max-w-4xl lg:max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-10">
        <Toast />

        <div class="overflow-hidden">
          <div class="px-5 md:px-8">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 md:gap-6">
              <div class="space-y-1.5">
                <div class="inline-flex flex-wrap items-center gap-2">
                  <h1 class="text-xl md:text-2xl font-semibold text-slate-900 tracking-tight">
                    Account settings
                  </h1>
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-sky-50 text-sky-700">
                    Candidate
                  </span>
                </div>
                <p class="text-sm text-slate-500 max-w-xl">
                  Manage how we contact you and secure your account.
                </p>
              </div>
              <Button
                label="Save changes"
                icon="pi pi-check"
                @click="saveSettings"
                :loading="saving"
                class="w-full md:w-auto !px-5 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800 active:!bg-slate-950 !border-slate-900 !text-sm shadow-sm hover:shadow-md transition-all"
              />
            </div>
          </div>

          <div class="px-5 md:px-8 py-6 md:py-8 space-y-6 settings-grid">
            <div class="grid grid-cols-1 lg:grid-cols-[1.1fr,0.9fr] gap-6">
              <Card class="transition-all hover:shadow-md hover:border-slate-200">
                <template #title>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">Profile picture</span>
                      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium  text-slate-500">
                        Step 1
                      </span>
                    </div>
                  </div>
                </template>
                <template #subtitle>
                  <span class="text-xs text-slate-500">Give your profile a friendly face.</span>
                </template>
                <template #content>
                  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pt-1">
                    <div class="relative w-24 h-24 rounded-full overflow-hidden ring-4 ring-sky-50 flex items-center justify-center shrink-0">
                      <img v-if="avatarPreview" :src="avatarPreview" alt="Profile Preview" class="w-full h-full object-cover" />
                      <span v-else class="text-3xl font-semibold text-slate-400">
                        {{ user?.email?.charAt(0).toUpperCase() || 'U' }}
                      </span>
                    </div>
                    <div class="flex flex-col gap-2.5">
                      <FileUpload
                        ref="fileUploadRef"
                        mode="basic"
                        name="avatar"
                        accept="image/*"
                        :maxFileSize="5000000"
                        @select="onFileSelect"
                        :auto="true"
                        customUpload
                        @uploader="() => {}"
                        chooseLabel="Change photo"
                        class="w-fit !px-4 !py-2.5 !rounded-full !bg-slate-900 hover:!bg-slate-800  !text-sm"
                      />
                      <div class="flex flex-wrap items-center gap-2">
                        <Button
                          v-if="avatarPreview"
                          label="Remove"
                          severity="secondary"
                          text
                          size="small"
                          class="!px-0 !py-0 !text-xs !font-medium"
                          @click="clearAvatar"
                        />
                        <span class="text-xs text-slate-500">
                          JPG, GIF or PNG. Max size of 5MB.
                        </span>
                      </div>
                    </div>
                  </div>
                </template>
              </Card>

              <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl transition-all hover:shadow-md hover:border-slate-200">
                <template #title>
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">Contact details</span>
                      <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-50 text-slate-500">
                        Step 2
                      </span>
                    </div>
                  </div>
                </template>
                <template #content>
                  <div class="pt-1 space-y-4">
                    <div class="flex flex-col gap-2">
                      <label for="email" class="font-medium text-xs uppercase tracking-wide text-slate-500">Email address</label>
                      <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="you@example.com"
                        class="w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      />
                    </div>
                    <div class="flex flex-col gap-2">
                      <label for="phone" class="font-medium text-xs uppercase tracking-wide text-slate-500">Phone number</label>
                      <InputText
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="+1 (555) 000-0000"
                        class="w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      />
                    </div>
                  </div>
                </template>
              </Card>
            </div>

            <Card class="shadow-sm border border-slate-100 bg-white rounded-2xl transition-all hover:shadow-md hover:border-slate-200">
              <template #title>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-900">Change password</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-50 text-slate-500">
                      Optional
                    </span>
                  </div>
                </div>
              </template>
              <template #subtitle>
                <span class="text-xs text-slate-500">Leave blank if you don't want to change it.</span>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 pt-1">
                  <div class="flex flex-col gap-2">
                    <label for="password" class="font-medium text-xs uppercase tracking-wide text-slate-500">New password</label>
                    <Password
                      id="password"
                      v-model="form.password"
                      toggleMask
                      :feedback="true"
                      placeholder="••••••••"
                      inputClass="!w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      class="w-full"
                    />
                    <div class="mt-1.5 flex flex-col gap-1">
                      <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all"
                          :class="passwordStrengthClass"
                          :style="{ width: passwordStrengthWidth }"
                        />
                      </div>
                      <span class="text-[11px] text-slate-500">
                        {{ passwordStrengthLabel }}
                      </span>
                    </div>
                  </div>
                  <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="font-medium text-xs uppercase tracking-wide text-slate-500">Confirm password</label>
                    <Password
                      id="password_confirmation"
                      v-model="form.password_confirmation"
                      toggleMask
                      :feedback="false"
                      placeholder="••••••••"
                      inputClass="!w-full !h-11 !px-3 !rounded-xl !border-slate-200 !bg-slate-50/80 hover:!bg-white hover:!border-slate-300 focus:!border-sky-500 focus:!ring-2 focus:!ring-sky-200 !text-sm transition-all"
                      class="w-full"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <Dialog v-model:visible="showCropDialog" modal header="Adjust image" :style="{ width: '500px' }" :closable="false">
            <div class="flex flex-col gap-4">
              <div class="w-full h-96 bg-black flex items-center justify-center overflow-hidden rounded-lg">
                <Cropper
                  ref="cropperRef"
                  class="cropper"
                  :src="cropImage"
                  :stencil-props="{ aspectRatio: 1/1 }"
                />
              </div>
              <div class="flex justify-end gap-2">
                <Button label="Cancel" text severity="secondary" @click="cancelCrop" />
                <Button label="Apply" icon="pi pi-check" @click="applyCrop" />
              </div>
            </div>
          </Dialog>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.cropper {
    height: 100%;
    width: 100%;
    background: #DDD;
}

.settings-grid .p-card {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: #e5e7eb !important; /* slate-200 */
    box-shadow: none !important;
}

.settings-grid .p-inputtext,
.settings-grid .p-password-input,
.settings-grid input,
.settings-grid textarea {
    border-width: 1px !important;
    border-style: solid !important;
    border-color: #e5e7eb !important; /* slate-200 */
    box-shadow: none !important;
}
</style>
