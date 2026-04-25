<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Skills Assessment Center</h1>
          <p class="text-gray-600 mt-1">Take skill tests, earn badges, and showcase your verified abilities</p>
        </div>
      <div class="flex items-center gap-3">
        <Button 
          icon="pi pi-history" 
          label="View History" 
          outlined 
          @click="showHistory = true"
          :disabled="loading"
        />
        <Button 
          icon="pi pi-verified" 
          label="My Badges" 
          outlined 
          @click="showBadges = true"
          :disabled="loading"
        />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" v-if="stats">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="pi pi-check-circle text-blue-600"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.total_attempts || 0 }}</div>
            <div class="text-sm text-gray-600">Total Attempts</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="pi pi-trophy text-green-600"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.passed_count || 0 }}</div>
            <div class="text-sm text-gray-600">Passed</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <i class="pi pi-star text-purple-600"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.average_score || 0 }}%</div>
            <div class="text-sm text-gray-600">Average Score</div>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
            <i class="pi pi-verified text-amber-600"></i>
          </div>
          <div>
            <div class="text-2xl font-bold text-gray-900">{{ stats.skills_verified || 0 }}</div>
            <div class="text-sm text-gray-600">Skills Verified</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Category:</label>
          <Dropdown 
            v-model="filters.category" 
            :options="categories" 
            placeholder="All Categories"
            class="w-48"
            @change="loadTemplates"
          />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Difficulty:</label>
          <Dropdown 
            v-model="filters.difficulty" 
            :options="difficulties" 
            placeholder="All Levels"
            class="w-32"
            @change="loadTemplates"
          />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-gray-700">Status:</label>
          <Dropdown 
            v-model="filters.status" 
            :options="statusOptions" 
            placeholder="All"
            class="w-32"
            @change="filterTemplates"
          />
        </div>
      </div>
    </div>

    <!-- Assessment Templates -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" v-if="!loading">
      <div 
        v-for="template in filteredTemplates" 
        :key="template.id"
        class="bg-white rounded-lg border border-gray-200 hover:border-blue-300 transition-colors cursor-pointer"
        @click="startAssessment(template)"
      >
        <div class="p-6">
          <!-- Header -->
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="font-semibold text-gray-900 mb-1">{{ template.title }}</h3>
              <p class="text-sm text-gray-600 mb-2">{{ template.skill_name }}</p>
              <div class="flex items-center gap-2">
                <Badge 
                  :value="template.difficulty" 
                  :severity="getDifficultySeverity(template.difficulty)"
                  class="text-xs"
                />
                <Badge 
                  :value="template.category" 
                  severity="secondary"
                  class="text-xs"
                />
              </div>
            </div>
            <div class="flex flex-col items-end gap-1">
              <i 
                v-if="template.user_passed" 
                class="pi pi-check-circle text-green-500 text-xl"
                title="Passed"
              ></i>
              <i 
                v-else-if="template.user_attempts_count > 0" 
                class="pi pi-clock text-amber-500 text-xl"
                title="Attempted"
              ></i>
            </div>
          </div>

          <!-- Details -->
          <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Duration:</span>
              <span class="font-medium">{{ template.duration_minutes }} min</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Passing Score:</span>
              <span class="font-medium">{{ template.passing_score }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm" v-if="template.user_attempts_count > 0">
              <span class="text-gray-600">Attempts:</span>
              <span class="font-medium">{{ template.user_attempts_count }}</span>
            </div>
            <div class="flex items-center justify-between text-sm" v-if="template.user_best_score">
              <span class="text-gray-600">Best Score:</span>
              <span class="font-medium text-green-600">{{ template.user_best_score }}%</span>
            </div>
          </div>

          <!-- Action Button -->
          <Button 
            :label="getButtonLabel(template)" 
            :icon="getButtonIcon(template)"
            :severity="getButtonSeverity(template)"
            class="w-full"
            size="small"
          />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <ProgressSpinner size="large" />
    </div>

    <!-- Empty State -->
    <div v-if="!loading && filteredTemplates.length === 0" class="text-center py-12">
      <i class="pi pi-search text-gray-400 text-4xl mb-4"></i>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No assessments found</h3>
      <p class="text-gray-600">Try adjusting your filters to see more assessments.</p>
    </div>

    <!-- Assessment Modal -->
    <Dialog 
      v-model:visible="showAssessmentModal" 
      modal 
      :style="{ width: '90vw', maxWidth: '800px' }"
      :closable="false"
    >
      <template #header>
        <div class="flex items-center gap-3">
          <i class="pi pi-graduation-cap text-blue-600 text-xl"></i>
          <div>
            <div class="font-bold">{{ currentAssessment?.template?.title }}</div>
            <div class="text-sm text-gray-600">{{ currentAssessment?.template?.skill_name }}</div>
          </div>
        </div>
      </template>

      <div v-if="assessmentState === 'starting'" class="space-y-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
          <h4 class="font-semibold text-blue-900 mb-2">Assessment Instructions</h4>
          <ul class="space-y-1 text-sm text-blue-800">
            <li>• You have {{ currentAssessment?.template?.duration_minutes }} minutes to complete</li>
            <li>• Passing score is {{ currentAssessment?.template?.passing_score }}%</li>
            <li>• You can take this assessment multiple times</li>
            <li>• Your best score will be recorded</li>
          </ul>
        </div>
        <div class="flex justify-end gap-3">
          <Button label="Cancel" outlined @click="closeAssessment" />
          <Button label="Start Assessment" icon="pi pi-play" @click="beginAssessment" />
        </div>
      </div>

      <div v-else-if="assessmentState === 'active'" class="space-y-6">
        <!-- Timer -->
        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-3">
          <div class="flex items-center gap-2">
            <i class="pi pi-clock text-gray-600"></i>
            <span class="text-sm font-medium">Time Remaining:</span>
          </div>
          <div class="text-lg font-bold" :class="timeRemaining < 300 ? 'text-red-600' : 'text-gray-900'">
            {{ formatTime(timeRemaining) }}
          </div>
        </div>

        <!-- Progress -->
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span>Question {{ currentQuestionIndex + 1 }} of {{ questions.length }}</span>
            <span>{{ Math.round(((currentQuestionIndex + 1) / questions.length) * 100) }}% Complete</span>
          </div>
          <ProgressBar :value="((currentQuestionIndex + 1) / questions.length) * 100" />
        </div>

        <!-- Question -->
        <div v-if="currentQuestion" class="space-y-4">
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-900 mb-4">{{ currentQuestion.question }}</h4>
            
            <div v-if="currentQuestion.type === 'multiple_choice'" class="space-y-3">
              <div 
                v-for="(option, index) in currentQuestion.options" 
                :key="index"
                class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
                :class="{ 'bg-blue-50 border-blue-300': answers[currentQuestion.id] === option }"
                @click="selectAnswer(currentQuestion.id, option)"
              >
                <RadioButton 
                  :value="option" 
                  v-model="answers[currentQuestion.id]" 
                  :inputId="`option-${index}`"
                />
                <label :for="`option-${index}`" class="flex-1 cursor-pointer">{{ option }}</label>
              </div>
            </div>
          </div>

          <!-- Navigation -->
          <div class="flex justify-between">
            <Button 
              label="Previous" 
              icon="pi pi-chevron-left" 
              outlined 
              @click="previousQuestion"
              :disabled="currentQuestionIndex === 0"
            />
            <Button 
              v-if="currentQuestionIndex < questions.length - 1"
              label="Next" 
              icon="pi pi-chevron-right" 
              iconPos="right"
              @click="nextQuestion"
            />
            <Button 
              v-else
              label="Submit Assessment" 
              icon="pi pi-check" 
              @click="submitAssessment"
              :loading="submitting"
            />
          </div>
        </div>
      </div>

      <div v-else-if="assessmentState === 'completed'" class="space-y-4">
        <div class="text-center">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
               :class="assessmentResult.passed ? 'bg-green-100' : 'bg-red-100'">
            <i :class="assessmentResult.passed ? 'pi pi-check text-green-600 text-2xl' : 'pi pi-times text-red-600 text-2xl'"></i>
          </div>
          <h3 class="text-xl font-bold mb-2" :class="assessmentResult.passed ? 'text-green-900' : 'text-red-900'">
            {{ assessmentResult.passed ? 'Congratulations!' : 'Keep Practicing!' }}
          </h3>
          <p class="text-gray-600 mb-4">
            You scored {{ assessmentResult.score }}% ({{ assessmentResult.correct_answers }}/{{ assessmentResult.total_questions }} correct)
          </p>
          <Badge 
            :value="assessmentResult.grade" 
            :severity="getGradeSeverity(assessmentResult.grade)"
            class="text-lg px-4 py-2"
          />
        </div>

        <div v-if="assessmentResult.feedback" class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-semibold text-gray-900 mb-3">Feedback</h4>
          <div class="space-y-3 text-sm">
            <div v-if="assessmentResult.feedback.overall">
              <strong>Overall:</strong> {{ assessmentResult.feedback.overall }}
            </div>
            <div v-if="assessmentResult.feedback.strengths?.length">
              <strong>Strengths:</strong> {{ assessmentResult.feedback.strengths.join(', ') }}
            </div>
            <div v-if="assessmentResult.feedback.areas_for_improvement?.length">
              <strong>Areas for Improvement:</strong> {{ assessmentResult.feedback.areas_for_improvement.join(', ') }}
            </div>
            <div v-if="assessmentResult.feedback.next_steps">
              <strong>Next Steps:</strong> {{ assessmentResult.feedback.next_steps }}
            </div>
          </div>
        </div>

        <div class="flex justify-center gap-3">
          <Button label="Close" outlined @click="closeAssessment" />
          <Button 
            v-if="!assessmentResult.passed"
            label="Retake Assessment" 
            icon="pi pi-refresh" 
            @click="retakeAssessment"
          />
        </div>
      </div>
    </Dialog>

    <!-- History Modal -->
    <Dialog v-model:visible="showHistory" modal :style="{ width: '90vw', maxWidth: '1000px' }">
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-history text-blue-600"></i>
          <span class="font-bold">Assessment History</span>
        </div>
      </template>
      
      <DataTable :value="history" :loading="loadingHistory" stripedRows>
        <Column field="template.title" header="Assessment" />
        <Column field="template.skill_name" header="Skill" />
        <Column field="score" header="Score">
          <template #body="{ data }">
            <span :class="data.passed ? 'text-green-600 font-semibold' : 'text-red-600'">
              {{ data.score }}%
            </span>
          </template>
        </Column>
        <Column field="grade" header="Grade">
          <template #body="{ data }">
            <Badge :value="data.grade" :severity="getGradeSeverity(data.grade)" />
          </template>
        </Column>
        <Column field="completed_at" header="Date">
          <template #body="{ data }">
            {{ formatDate(data.completed_at) }}
          </template>
        </Column>
        <Column field="time_taken" header="Duration">
          <template #body="{ data }">
            {{ data.time_taken }}
          </template>
        </Column>
      </DataTable>
    </Dialog>

    <!-- Badges Modal -->
    <Dialog v-model:visible="showBadges" modal :style="{ width: '90vw', maxWidth: '800px' }">
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-verified text-amber-600"></i>
          <span class="font-bold">Verified Skills & Badges</span>
        </div>
      </template>
      
      <div v-if="!loadingBadges && verifiedSkills.length === 0" class="text-center py-8">
        <i class="pi pi-star text-gray-400 text-4xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No verified skills yet</h3>
        <p class="text-gray-600">Complete assessments with 80%+ scores to earn verified skill badges.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div 
          v-for="skill in verifiedSkills" 
          :key="skill.id"
          class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-lg border border-amber-200 p-4"
        >
          <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
              <i class="pi pi-verified text-amber-600 text-xl"></i>
            </div>
            <div>
              <h4 class="font-semibold text-amber-900">{{ skill.skill_name }}</h4>
              <p class="text-sm text-amber-700">Proficiency: {{ skill.proficiency_level }}%</p>
            </div>
          </div>
          <div class="text-xs text-amber-600">
            Verified {{ formatDate(skill.verified_at) }}
          </div>
        </div>
      </div>
    </Dialog>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '@/lib/api';
import AppLayout from '@/Components/AppLayout.vue';
import Button from 'primevue/button';
import Badge from 'primevue/badge';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import ProgressBar from 'primevue/progressbar';
import RadioButton from 'primevue/radiobutton';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const toast = useToast();

// State
const loading = ref(true);
const templates = ref([]);
const stats = ref(null);
const history = ref([]);
const verifiedSkills = ref([]);
const loadingHistory = ref(false);
const loadingBadges = ref(false);

// Filters
const filters = ref({
  category: null,
  difficulty: null,
  status: null
});

const categories = ref(['Healthcare', 'Nursing', 'Medical', 'Administrative', 'Technical']);
const difficulties = ref(['Beginner', 'Intermediate', 'Advanced', 'Expert']);
const statusOptions = ref(['All', 'Not Attempted', 'Attempted', 'Passed']);

// Assessment Modal
const showAssessmentModal = ref(false);
const assessmentState = ref('starting'); // starting, active, completed
const currentAssessment = ref(null);
const questions = ref([]);
const currentQuestionIndex = ref(0);
const answers = ref({});
const timeRemaining = ref(0);
const timer = ref(null);
const submitting = ref(false);
const assessmentResult = ref(null);

// Other Modals
const showHistory = ref(false);
const showBadges = ref(false);

// Computed
const filteredTemplates = computed(() => {
  let filtered = templates.value;
  
  if (filters.value.status && filters.value.status !== 'All') {
    if (filters.value.status === 'Not Attempted') {
      filtered = filtered.filter(t => t.user_attempts_count === 0);
    } else if (filters.value.status === 'Attempted') {
      filtered = filtered.filter(t => t.user_attempts_count > 0 && !t.user_passed);
    } else if (filters.value.status === 'Passed') {
      filtered = filtered.filter(t => t.user_passed);
    }
  }
  
  return filtered;
});

const currentQuestion = computed(() => {
  return questions.value[currentQuestionIndex.value];
});

// Methods
async function loadTemplates() {
  try {
    loading.value = true;
    const params = {};
    if (filters.value.category) params.category = filters.value.category;
    if (filters.value.difficulty) params.difficulty = filters.value.difficulty;
    
    const response = await api.get('/assessments/templates', { params });
    templates.value = response.data.data || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load assessment templates',
      life: 3000
    });
  } finally {
    loading.value = false;
  }
}

async function loadHistory() {
  try {
    loadingHistory.value = true;
    const response = await api.get('/assessments/history');
    const data = response.data.data;
    history.value = data.assessments || [];
    stats.value = data.summary || {};
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load assessment history',
      life: 3000
    });
  } finally {
    loadingHistory.value = false;
  }
}

async function loadVerifiedSkills() {
  try {
    loadingBadges.value = true;
    const response = await api.get('/skills/verified');
    verifiedSkills.value = response.data.data || [];
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load verified skills',
      life: 3000
    });
  } finally {
    loadingBadges.value = false;
  }
}

function filterTemplates() {
  // Trigger reactivity for computed property
}

async function startAssessment(template) {
  try {
    const response = await api.post(`/assessments/${template.id}/start`);
    const data = response.data.data;
    
    currentAssessment.value = data;
    questions.value = data.questions || [];
    answers.value = {};
    currentQuestionIndex.value = 0;
    assessmentState.value = 'starting';
    showAssessmentModal.value = true;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to start assessment',
      life: 3000
    });
  }
}

function beginAssessment() {
  assessmentState.value = 'active';
  timeRemaining.value = (currentAssessment.value.template.duration_minutes || 30) * 60;
  startTimer();
}

function startTimer() {
  timer.value = setInterval(() => {
    timeRemaining.value--;
    if (timeRemaining.value <= 0) {
      clearInterval(timer.value);
      submitAssessment();
    }
  }, 1000);
}

function selectAnswer(questionId, answer) {
  answers.value[questionId] = answer;
}

function nextQuestion() {
  if (currentQuestionIndex.value < questions.value.length - 1) {
    currentQuestionIndex.value++;
  }
}

function previousQuestion() {
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--;
  }
}

async function submitAssessment() {
  try {
    submitting.value = true;
    if (timer.value) {
      clearInterval(timer.value);
    }
    
    const timeTaken = (currentAssessment.value.template.duration_minutes * 60) - timeRemaining.value;
    
    const response = await api.post(`/assessments/${currentAssessment.value.assessment_id}/submit`, {
      answers: answers.value,
      time_taken_seconds: timeTaken
    });
    
    assessmentResult.value = response.data.data;
    assessmentState.value = 'completed';
    
    // Reload templates to update status
    await loadTemplates();
    
    toast.add({
      severity: assessmentResult.value.passed ? 'success' : 'info',
      summary: assessmentResult.value.passed ? 'Assessment Passed!' : 'Assessment Completed',
      detail: `You scored ${assessmentResult.value.score}%`,
      life: 5000
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to submit assessment',
      life: 3000
    });
  } finally {
    submitting.value = false;
  }
}

function retakeAssessment() {
  const template = templates.value.find(t => t.id === currentAssessment.value.template.id);
  if (template) {
    closeAssessment();
    startAssessment(template);
  }
}

function closeAssessment() {
  if (timer.value) {
    clearInterval(timer.value);
  }
  showAssessmentModal.value = false;
  currentAssessment.value = null;
  questions.value = [];
  answers.value = {};
  assessmentState.value = 'starting';
  assessmentResult.value = null;
}

// Utility functions
function getDifficultySeverity(difficulty) {
  const severities = {
    'Beginner': 'success',
    'Intermediate': 'warning', 
    'Advanced': 'danger',
    'Expert': 'contrast'
  };
  return severities[difficulty] || 'secondary';
}

function getGradeSeverity(grade) {
  if (grade === 'A' || grade === 'A+') return 'success';
  if (grade === 'B' || grade === 'B+') return 'info';
  if (grade === 'C' || grade === 'C+') return 'warning';
  return 'danger';
}

function getButtonLabel(template) {
  if (template.user_passed) return 'Retake';
  if (template.user_attempts_count > 0) return 'Try Again';
  return 'Start Assessment';
}

function getButtonIcon(template) {
  if (template.user_passed) return 'pi pi-refresh';
  return 'pi pi-play';
}

function getButtonSeverity(template) {
  if (template.user_passed) return 'success';
  return 'primary';
}

function formatTime(seconds) {
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs.toString().padStart(2, '0')}`;
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadTemplates(),
    loadHistory(),
    loadVerifiedSkills()
  ]);
});
</script>