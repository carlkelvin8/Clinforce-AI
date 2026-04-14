<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Mentorship Program</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Connect mentors with mentees for professional development</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Setup -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">Profile Setup</h2>
            
            <div class="space-y-4">
              <button 
                @click="activeTab = 'mentor'"
                :class="[
                  'w-full p-4 rounded-lg border-2 transition-colors',
                  activeTab === 'mentor' 
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="text-left">
                  <h3 class="font-medium text-gray-900 dark:text-white">Become a Mentor</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Share your expertise and guide others</p>
                </div>
              </button>

              <button 
                @click="activeTab = 'mentee'"
                :class="[
                  'w-full p-4 rounded-lg border-2 transition-colors',
                  activeTab === 'mentee' 
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="text-left">
                  <h3 class="font-medium text-gray-900 dark:text-white">Find a Mentor</h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Get guidance for your career growth</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Program Stats</h3>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Active Mentors</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ stats.activeMentors }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Seeking Mentees</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ stats.seekingMentees }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">Active Relationships</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ stats.activeRelationships }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
          <!-- Mentor Profile Form -->
          <div v-if="activeTab === 'mentor'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">Mentor Profile</h2>
            
            <form @submit.prevent="saveMentorProfile" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Years of Experience
                  </label>
                  <input
                    v-model.number="mentorForm.years_experience"
                    type="number"
                    min="1"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                    required
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Max Mentees
                  </label>
                  <select
                    v-model.number="mentorForm.max_mentees"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="1">1 Mentee</option>
                    <option value="2">2 Mentees</option>
                    <option value="3">3 Mentees</option>
                    <option value="5">5 Mentees</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Specialties
                </label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                  <label v-for="specialty in specialtyOptions" :key="specialty" class="flex items-center">
                    <input
                      type="checkbox"
                      :value="specialty"
                      v-model="mentorForm.specialties"
                      class="mr-2 text-blue-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ specialty }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Bio
                </label>
                <textarea
                  v-model="mentorForm.bio"
                  rows="4"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Tell potential mentees about your background and expertise..."
                  required
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Mentoring Style
                </label>
                <select
                  v-model="mentorForm.mentoring_style"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="hands_on">Hands-on</option>
                  <option value="advisory">Advisory</option>
                  <option value="coaching">Coaching</option>
                  <option value="mixed">Mixed Approach</option>
                </select>
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : 'Save Mentor Profile' }}
              </button>
            </form>
          </div>

          <!-- Mentee Profile Form -->
          <div v-if="activeTab === 'mentee'" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">Mentee Profile</h2>
            
            <form @submit.prevent="saveMenteeProfile" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Experience Level
                </label>
                <select
                  v-model="menteeForm.experience_level"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="student">Student</option>
                  <option value="new_grad">New Graduate</option>
                  <option value="early_career">Early Career</option>
                  <option value="mid_career">Mid Career</option>
                  <option value="career_change">Career Change</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Career Goals
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                  <label v-for="goal in careerGoalOptions" :key="goal" class="flex items-center">
                    <input
                      type="checkbox"
                      :value="goal"
                      v-model="menteeForm.career_goals"
                      class="mr-2 text-blue-600"
                    />
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ goal }}</span>
                  </label>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Background Summary
                </label>
                <textarea
                  v-model="menteeForm.background_summary"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Briefly describe your background and current situation..."
                  required
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  What are you seeking from mentorship?
                </label>
                <textarea
                  v-model="menteeForm.what_seeking"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Describe what you hope to gain from a mentoring relationship..."
                  required
                ></textarea>
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : 'Save Mentee Profile' }}
              </button>
            </form>

            <!-- Mentor Matches -->
            <div v-if="mentorMatches.length > 0" class="mt-8">
              <h3 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Recommended Mentors</h3>
              <div class="space-y-4">
                <div
                  v-for="match in mentorMatches"
                  :key="match.id"
                  class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                >
                  <div class="flex justify-between items-start">
                    <div class="flex-1">
                      <h4 class="font-medium text-gray-900 dark:text-white">{{ match.name }}</h4>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ match.bio }}</p>
                      <div class="mt-2 flex items-center space-x-4">
                        <span class="text-sm text-gray-500">{{ match.years_experience }} years experience</span>
                        <span class="text-sm text-green-600">{{ match.compatibility_score }}% match</span>
                      </div>
                    </div>
                    <button
                      @click="requestMentorship(match.mentor_id)"
                      class="ml-4 bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
                    >
                      Request Mentorship
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Relationships -->
      <div v-if="relationships.length > 0" class="mt-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-xl font-semibold mb-6 text-gray-900 dark:text-white">My Mentorship Relationships</h2>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Relationship
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Start Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Sessions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="relationship in relationships" :key="relationship.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ relationship.mentor_name || relationship.mentee_name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ relationship.mentor_id === currentUser.id ? 'Mentee' : 'Mentor' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(relationship.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ relationship.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ formatDate(relationship.start_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ relationship.total_sessions || 0 }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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

const activeTab = ref('mentor')
const loading = ref(false)
const currentUser = ref({})

const stats = ref({
  activeMentors: 0,
  seekingMentees: 0,
  activeRelationships: 0
})

const mentorForm = ref({
  years_experience: 5,
  specialties: [],
  mentoring_areas: [],
  bio: '',
  mentoring_philosophy: '',
  mentoring_style: 'mixed',
  max_mentees: 3,
  preferred_communication: ['video', 'email'],
  session_duration_minutes: 60,
  commitment_level: 'regular'
})

const menteeForm = ref({
  career_goals: [],
  areas_for_development: [],
  preferred_mentor_qualities: [],
  experience_level: 'new_grad',
  background_summary: '',
  what_seeking: '',
  preferred_communication: ['video', 'email'],
  commitment_level: 'regular',
  has_had_mentor_before: false,
  previous_mentoring_experience: ''
})

const specialtyOptions = [
  'Critical Care', 'Emergency Medicine', 'Pediatrics', 'Surgery',
  'Mental Health', 'Geriatrics', 'Oncology', 'Cardiology'
]

const careerGoalOptions = [
  'Leadership Development', 'Clinical Excellence', 'Career Advancement',
  'Skill Building', 'Networking', 'Work-Life Balance'
]

const mentorMatches = ref([])
const relationships = ref([])

onMounted(async () => {
  await loadUserData()
  await loadMentorshipData()
})

const loadUserData = async () => {
  try {
    const response = await http.get('/auth/me')
    currentUser.value = response.data.user
  } catch (error) {
    console.error('Error loading user data:', error)
  }
}

const loadMentorshipData = async () => {
  try {
    // Load relationships
    const relationshipsResponse = await http.get('/mentorship/relationships')
    relationships.value = relationshipsResponse.data.data || []

    // Load mentor matches if user is a mentee
    try {
      const matchesResponse = await http.get('/mentorship/mentor-matches')
      mentorMatches.value = matchesResponse.data.data || []
    } catch (error) {
      // User might not have mentee profile yet
    }

    // Mock stats for now
    stats.value = {
      activeMentors: 45,
      seekingMentees: 23,
      activeRelationships: relationships.value.filter(r => r.status === 'active').length
    }
  } catch (error) {
    console.error('Error loading mentorship data:', error)
  }
}

const saveMentorProfile = async () => {
  loading.value = true
  try {
    await http.post('/mentorship/mentor-profile', mentorForm.value)
    
    Swal.fire({
      title: 'Success!',
      text: 'Mentor profile saved successfully',
      icon: 'success',
      confirmButtonColor: '#3B82F6'
    })
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to save mentor profile',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  } finally {
    loading.value = false
  }
}

const saveMenteeProfile = async () => {
  loading.value = true
  try {
    await http.post('/mentorship/mentee-profile', menteeForm.value)
    
    // Generate mentor matches
    await http.post('/mentorship/generate-matches')
    await loadMentorshipData()
    
    Swal.fire({
      title: 'Success!',
      text: 'Mentee profile saved and mentor matches generated',
      icon: 'success',
      confirmButtonColor: '#3B82F6'
    })
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to save mentee profile',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  } finally {
    loading.value = false
  }
}

const requestMentorship = async (mentorId) => {
  try {
    const { value: message } = await Swal.fire({
      title: 'Request Mentorship',
      text: 'Add a personal message to your mentorship request:',
      input: 'textarea',
      inputPlaceholder: 'Tell them why you\'d like them as a mentor...',
      showCancelButton: true,
      confirmButtonColor: '#3B82F6',
      cancelButtonColor: '#6B7280'
    })

    if (message !== undefined) {
      await http.post('/mentorship/request', {
        mentor_id: mentorId,
        message: message
      })

      Swal.fire({
        title: 'Request Sent!',
        text: 'Your mentorship request has been sent',
        icon: 'success',
        confirmButtonColor: '#3B82F6'
      })

      await loadMentorshipData()
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to send mentorship request',
      icon: 'error',
      confirmButtonColor: '#3B82F6'
    })
  }
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    active: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    paused: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    terminated: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
  }
  return classes[status] || classes.pending
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString()
}
</script>