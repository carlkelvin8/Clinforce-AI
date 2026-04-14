<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Certification Tracking</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your professional certifications and renewals</p>
      </div>

      <!-- Analytics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/20">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Certifications</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.total_certifications }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/20">
              <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.active_certifications }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/20">
              <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Expiring Soon</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.expiring_soon }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900/20">
              <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Renewals Due</p>
              <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ analytics.renewals_due }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Certifications List -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">My Certifications</h2>
              <button
                @click="showAddCertificationModal = true"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                Add Certification
              </button>
            </div>

            <div class="p-6">
              <div v-if="certifications.length === 0" class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No certifications</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first certification.</p>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="cert in certifications"
                  :key="cert.id"
                  class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <div class="flex items-center space-x-2">
                        <h3 class="font-medium text-gray-900 dark:text-white">
                          {{ cert.certification_name }}
                        </h3>
                        <span v-if="cert.abbreviation" class="text-sm text-gray-500 dark:text-gray-400">
                          ({{ cert.abbreviation }})
                        </span>
                        <span :class="getStatusClass(cert.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                          {{ cert.status }}
                        </span>
                      </div>
                      
                      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ cert.issuing_organization }}
                      </p>
                      
                      <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <span class="text-gray-500 dark:text-gray-400">Issued:</span>
                          <span class="ml-1 text-gray-900 dark:text-white">{{ formatDate(cert.issued_date) }}</span>
                        </div>
                        <div v-if="cert.expiration_date">
                          <span class="text-gray-500 dark:text-gray-400">Expires:</span>
                          <span class="ml-1" :class="getExpirationClass(cert.expiration_date)">
                            {{ formatDate(cert.expiration_date) }}
                          </span>
                        </div>
                      </div>

                      <div v-if="cert.certification_number" class="mt-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Number:</span>
                        <span class="ml-1 text-gray-900 dark:text-white">{{ cert.certification_number }}</span>
                      </div>
                    </div>

                    <div class="ml-4 flex space-x-2">
                      <button
                        v-if="cert.certificate_file_path"
                        @click="downloadCertificate(cert.id)"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400"
                        title="Download Certificate"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                      </button>
                      
                      <button
                        @click="verifyCertification(cert.id)"
                        class="text-green-600 hover:text-green-800 dark:text-green-400"
                        title="Verify Certification"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                      </button>
                      
                      <button
                        @click="editCertification(cert)"
                        class="text-gray-600 hover:text-gray-800 dark:text-gray-400"
                        title="Edit Certification"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Renewals Due -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Renewals Due</h2>
            </div>

            <div class="p-6">
              <div v-if="renewalsDue.length === 0" class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">No renewals due</p>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="renewal in renewalsDue"
                  :key="renewal.id"
                  class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                >
                  <h3 class="font-medium text-gray-900 dark:text-white">
                    {{ renewal.certification_name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Due: {{ formatDate(renewal.renewal_due_date) }}
                  </p>
                  
                  <div class="mt-2">
                    <div class="flex justify-between text-sm">
                      <span class="text-gray-500 dark:text-gray-400">Progress</span>
                      <span class="text-gray-900 dark:text-white">{{ renewal.completion_percentage }}%</span>
                    </div>
                    <div class="mt-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div
                        class="bg-blue-600 h-2 rounded-full"
                        :style="{ width: renewal.completion_percentage + '%' }"
                      ></div>
                    </div>
                  </div>

                  <button
                    @click="startRenewal(renewal.id)"
                    class="mt-3 w-full bg-blue-600 text-white py-2 px-4 rounded-md text-sm hover:bg-blue-700"
                  >
                    {{ renewal.status === 'upcoming' ? 'Start Renewal' : 'Continue Renewal' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Certification Categories -->
          <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-xl font-semibold text-gray-900 dark:text-white">By Category</h2>
            </div>

            <div class="p-6">
              <div v-if="analytics.certifications_by_category" class="space-y-3">
                <div
                  v-for="category in analytics.certifications_by_category"
                  :key="category.category"
                  class="flex justify-between items-center"
                >
                  <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                    {{ category.category.replace('_', ' ') }}
                  </span>
                  <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ category.count }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Add/Edit Certification Modal -->
      <div v-if="showAddCertificationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
          <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ editingCertification ? 'Edit Certification' : 'Add Certification' }}
            </h3>
          </div>

          <form @submit.prevent="saveCertification" class="p-6 space-y-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Certification Type
              </label>
              <select
                v-model="certificationForm.certification_type_id"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                required
              >
                <option value="">Select a certification type</option>
                <option
                  v-for="type in certificationTypes"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.name }} ({{ type.abbreviation }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Certification Number
                </label>
                <input
                  v-model="certificationForm.certification_number"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Issuing Authority
                </label>
                <input
                  v-model="certificationForm.issuing_authority"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Issued Date
                </label>
                <input
                  v-model="certificationForm.issued_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Expiration Date
                </label>
                <input
                  v-model="certificationForm.expiration_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Certificate File
              </label>
              <input
                ref="fileInput"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              />
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Upload PDF, JPG, or PNG files (max 5MB)
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Notes
              </label>
              <textarea
                v-model="certificationForm.notes"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Additional notes about this certification..."
              ></textarea>
            </div>

            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (editingCertification ? 'Update' : 'Add') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { http } from '@/lib/http'
import AppLayout from '@/Components/AppLayout.vue'
import Swal from 'sweetalert2'

const loading = ref(false)
const showAddCertificationModal = ref(false)
const editingCertification = ref(null)
const fileInput = ref(null)

const analytics = ref({
  total_certifications: 0,
  active_certifications: 0,
  expiring_soon: 0,
  renewals_due: 0,
  certifications_by_category: [],
  renewal_completion_rate: 0
})

const certifications = ref([])
const renewalsDue = ref([])
const certificationTypes = ref([])

const certificationForm = ref({
  certification_type_id: '',
  certification_number: '',
  issued_date: '',
  expiration_date: '',
  issuing_authority: '',
  verification_url: '',
  notes: ''
})

onMounted(async () => {
  await loadData()
})

const loadData = async () => {
  try {
    const [analyticsRes, certificationsRes, renewalsRes, typesRes] = await Promise.all([
      http.get('/certification-tracking/analytics'),
      http.get('/certification-tracking/user-certifications'),
      http.get('/certification-tracking/renewals-due'),
      http.get('/certification-tracking/certification-types')
    ])

    analytics.value = analyticsRes.data.data
    certifications.value = certificationsRes.data.data
    renewalsDue.value = renewalsRes.data.data
    certificationTypes.value = typesRes.data.data
  } catch (error) {
    console.error('Error loading certification data:', error)
  }
}

const saveCertification = async () => {
  loading.value = true
  try {
    const formData = new FormData()
    
    // Add form fields
    Object.keys(certificationForm.value).forEach(key => {
      if (certificationForm.value[key]) {
        formData.append(key, certificationForm.value[key])
      }
    })

    // Add file if selected
    if (fileInput.value?.files[0]) {
      formData.append('certificate_file', fileInput.value.files[0])
    }

    if (editingCertification.value) {
      await http.post(`/certification-tracking/certifications/${editingCertification.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      await http.post('/certification-tracking/certifications', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }

    await loadData()
    closeModal()
    
    Swal.fire({
      title: 'Success!',
      text: `Certification ${editingCertification.value ? 'updated' : 'added'} successfully`,
      icon: 'success',
      confirmButtonColor: '#3B82F6'
    })
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to save certification',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  } finally {
    loading.value = false
  }
}

const editCertification = (cert) => {
  editingCertification.value = cert
  certificationForm.value = {
    certification_type_id: cert.certification_type_id,
    certification_number: cert.certification_number || '',
    issued_date: cert.issued_date,
    expiration_date: cert.expiration_date || '',
    issuing_authority: cert.issuing_authority || '',
    verification_url: cert.verification_url || '',
    notes: cert.notes || ''
  }
  showAddCertificationModal.value = true
}

const closeModal = () => {
  showAddCertificationModal.value = false
  editingCertification.value = null
  certificationForm.value = {
    certification_type_id: '',
    certification_number: '',
    issued_date: '',
    expiration_date: '',
    issuing_authority: '',
    verification_url: '',
    notes: ''
  }
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const downloadCertificate = async (certificationId) => {
  try {
    const response = await http.get(`/certification-tracking/certifications/${certificationId}/file`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'certificate.pdf')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Failed to download certificate',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  }
}

const verifyCertification = async (certificationId) => {
  try {
    await http.post(`/certification-tracking/certifications/${certificationId}/verify`)
    
    Swal.fire({
      title: 'Verified!',
      text: 'Certification verified successfully',
      icon: 'success',
      confirmButtonColor: '#3B82F6'
    })
    
    await loadData()
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Failed to verify certification',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  }
}

const startRenewal = async (renewalId) => {
  try {
    await http.post(`/certification-tracking/renewals/${renewalId}/start`)
    
    Swal.fire({
      title: 'Renewal Started',
      text: 'Renewal process has been initiated',
      icon: 'success',
      confirmButtonColor: '#3B82F6'
    })
    
    await loadData()
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Failed to start renewal process',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    expired: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    suspended: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    revoked: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    pending_renewal: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'
  }
  return classes[status] || classes.active
}

const getExpirationClass = (expirationDate) => {
  const today = new Date()
  const expiry = new Date(expirationDate)
  const daysUntilExpiry = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24))
  
  if (daysUntilExpiry < 0) {
    return 'text-red-600 dark:text-red-400 font-medium'
  } else if (daysUntilExpiry <= 90) {
    return 'text-yellow-600 dark:text-yellow-400 font-medium'
  }
  return 'text-gray-900 dark:text-white'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>