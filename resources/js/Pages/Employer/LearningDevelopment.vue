<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from '@/Components/AppLayout.vue'
import api from '@/lib/api'
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import MultiSelect from 'primevue/multiselect';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import ProgressBar from 'primevue/progressbar';
import Badge from 'primevue/badge';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const loading = ref(false);
const activeTab = ref('dashboard');

// Dashboard Data
const dashboardData = ref({});
const skillGaps = ref([]);
const recommendations = ref([]);
const userSkills = ref([]);
const certifications = ref([]);

// Skills Management
const skillsCatalog = ref([]);
const skillDialog = ref(false);
const selectedSkill = ref(null);
const newSkill = ref({
  skill_id: null,
  proficiency_level: 'beginner',
  years_experience: null,
  notes: '',
  is_featured: false,
});

// Learning Courses
const courses = ref([]);
const courseDialog = ref(false);
const selectedCourse = ref(null);
const courseFilters = ref({
  category: null,
  difficulty_level: null,
  offers_certificate: null,
  search: '',
});

// Skill Gap Analysis
const gapAnalysisDialog = ref(false);
const gapAnalysisData = ref({
  target_role_title: '',
  gaps: [],
});

const proficiencyLevels = [
  { label: 'Beginner', value: 'beginner' },
  { label: 'Intermediate', value: 'intermediate' },
  { label: 'Advanced', value: 'advanced' },
  { label: 'Expert', value: 'expert' },
];

const skillCategories = [
  { label: 'Clinical', value: 'clinical' },
  { label: 'Technical', value: 'technical' },
  { label: 'Soft Skills', value: 'soft' },
  { label: 'Leadership', value: 'leadership' },
  { label: 'Compliance', value: 'compliance' },
];

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: isDark.value ? '#e2e8f0' : '#374151',
      },
    },
  },
  scales: {
    x: {
      ticks: { color: isDark.value ? '#94a3b8' : '#6b7280' },
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
    },
    y: {
      ticks: { color: isDark.value ? '#94a3b8' : '#6b7280' },
      grid: { color: isDark.value ? '#374151' : '#e5e7eb' },
    },
  },
}));

async function loadDashboard() {
  loading.value = true;
  try {
    const res = await api.get('/learning-development/dashboard');
    dashboardData.value = res?.data?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load dashboard', life: 3000 });
  } finally {
    loading.value = false;
  }
}

async function loadUserSkills() {
  try {
    const res = await api.get('/learning-development/user-skills');
    userSkills.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load skills', life: 3000 });
  }
}

async function loadSkillsCatalog() {
  try {
    const res = await api.get('/learning-development/skills-catalog');
    skillsCatalog.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load skills catalog', life: 3000 });
  }
}

async function loadSkillGaps() {
  try {
    const res = await api.get('/learning-development/skill-gaps');
    skillGaps.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load skill gaps', life: 3000 });
  }
}

async function loadRecommendations() {
  try {
    const res = await api.get('/learning-development/recommendations');
    recommendations.value = res?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load recommendations', life: 3000 });
  }
}

async function loadCourses() {
  try {
    const params = Object.fromEntries(
      Object.entries(courseFilters.value).filter(([_, v]) => v !== null && v !== '')
    );
    
    const res = await api.get('/learning-development/courses', { params });
    courses.value = res?.data?.data?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load courses', life: 3000 });
  }
}

async function addSkill() {
  try {
    await api.post('/learning-development/user-skills', newSkill.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Skill added successfully', life: 2000 });
    skillDialog.value = false;
    resetSkillForm();
    loadUserSkills();
    loadDashboard();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to add skill', life: 3000 });
  }
}

async function analyzeSkillGaps() {
  try {
    const res = await api.post('/learning-development/analyze-skill-gaps', {
      target_role_title: gapAnalysisData.value.target_role_title,
    });
    
    gapAnalysisData.value.gaps = res?.data?.data?.gaps || [];
    toast.add({ severity: 'success', summary: 'Success', detail: 'Skill gap analysis completed', life: 2000 });
    loadSkillGaps();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to analyze skill gaps', life: 3000 });
  }
}

async function enrollInCourse(courseId) {
  try {
    await api.post(`/learning-development/courses/${courseId}/enroll`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Successfully enrolled in course', life: 2000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to enroll', life: 3000 });
  }
}

function resetSkillForm() {
  newSkill.value = {
    skill_id: null,
    proficiency_level: 'beginner',
    years_experience: null,
    notes: '',
    is_featured: false,
  };
}

function switchTab(tab) {
  activeTab.value = tab;
  
  switch (tab) {
    case 'dashboard':
      loadDashboard();
      break;
    case 'skills':
      loadUserSkills();
      loadSkillsCatalog();
      break;
    case 'gaps':
      loadSkillGaps();
      break;
    case 'courses':
      loadCourses();
      break;
    case 'recommendations':
      loadRecommendations();
      break;
  }
}

function getPriorityColor(priority) {
  switch (priority) {
    case 'critical': return 'danger';
    case 'high': return 'warning';
    case 'medium': return 'info';
    default: return 'success';
  }
}

function getProficiencyColor(level) {
  switch (level) {
    case 'expert': return 'success';
    case 'advanced': return 'info';
    case 'intermediate': return 'warning';
    default: return 'secondary';
  }
}

const overviewMetrics = computed(() => {
  const overview = dashboardData.value.overview || {};
  return [
    {
      title: 'Total Skills',
      value: overview.total_skills || 0,
      icon: 'pi-star',
      color: 'text-blue-600',
      bgColor: 'bg-blue-100 dark:bg-blue-900',
    },
    {
      title: 'Skill Gaps',
      value: overview.skill_gaps || 0,
      icon: 'pi-exclamation-triangle',
      color: 'text-orange-600',
      bgColor: 'bg-orange-100 dark:bg-orange-900',
    },
    {
      title: 'Active Courses',
      value: overview.active_courses || 0,
      icon: 'pi-book',
      color: 'text-green-600',
      bgColor: 'bg-green-100 dark:bg-green-900',
    },
    {
      title: 'Certifications',
      value: overview.certifications || 0,
      icon: 'pi-verified',
      color: 'text-purple-600',
      bgColor: 'bg-purple-100 dark:bg-purple-900',
    },
  ];
});

const skillsChartData = computed(() => {
  if (!userSkills.value.length) return { labels: [], datasets: [] };
  
  const skillsByCategory = userSkills.value.reduce((acc, skill) => {
    acc[skill.category] = (acc[skill.category] || 0) + 1;
    return acc;
  }, {});
  
  return {
    labels: Object.keys(skillsByCategory),
    datasets: [{
      data: Object.values(skillsByCategory),
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
    }],
  };
});

onMounted(() => {
  loadDashboard();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-start">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Learning & Development</h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Manage skills, track certifications, and advance your career</p>
        </div>
        
        <div class="flex items-center gap-3">
          <Button label="Analyze Skill Gaps" icon="pi pi-search" @click="gapAnalysisDialog = true" />
          <Button label="Add Skill" icon="pi pi-plus" @click="skillDialog = true" />
        </div>
      </div>

      <!-- Overview Metrics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="metric in overviewMetrics" :key="metric.title"
          class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-slate-600 dark:text-slate-400">{{ metric.title }}</p>
              <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ metric.value.toLocaleString() }}</p>
            </div>
            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', metric.bgColor]">
              <i :class="['pi text-lg', metric.icon, metric.color]"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'dashboard', label: 'Dashboard', icon: 'pi-chart-line' },
          { key: 'skills', label: 'My Skills', icon: 'pi-star' },
          { key: 'gaps', label: 'Skill Gaps', icon: 'pi-exclamation-triangle' },
          { key: 'courses', label: 'Courses', icon: 'pi-book' },
          { key: 'recommendations', label: 'Recommendations', icon: 'pi-lightbulb' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Dashboard Tab -->
      <template v-if="activeTab === 'dashboard'">
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Skills Distribution -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Skills by Category</h3>
            <div class="h-64">
              <Chart v-if="userSkills.length" type="doughnut" :data="skillsChartData" :options="chartOptions" />
              <div v-else class="flex items-center justify-center h-full text-slate-500 dark:text-slate-400">
                No skills added yet
              </div>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Learning Progress</h3>
            <div class="space-y-4">
              <div v-if="dashboardData.progress?.recent_courses?.length" class="space-y-3">
                <div v-for="course in dashboardData.progress.recent_courses" :key="course.id"
                  class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                  <div>
                    <p class="font-medium text-slate-900 dark:text-white">{{ course.title }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ course.provider_name }}</p>
                  </div>
                  <div class="text-right">
                    <ProgressBar :value="course.progress_percentage" class="w-20" />
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ Math.round(course.progress_percentage) }}%</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-slate-500 dark:text-slate-400">
                <i class="pi pi-book text-2xl mb-2"></i>
                <p>No active courses</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Skills Tab -->
      <template v-else-if="activeTab === 'skills'">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
          <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">My Skills</h3>
          </div>
          
          <DataTable :value="userSkills" class="text-sm">
            <Column field="name" header="Skill" />
            <Column field="category" header="Category">
              <template #body="{ data }">
                <Badge :value="data.category" class="capitalize" />
              </template>
            </Column>
            <Column field="proficiency_level" header="Proficiency">
              <template #body="{ data }">
                <Badge :value="data.proficiency_level" :severity="getProficiencyColor(data.proficiency_level)" class="capitalize" />
              </template>
            </Column>
            <Column field="years_experience" header="Experience">
              <template #body="{ data }">
                {{ data.years_experience || 0 }} years
              </template>
            </Column>
            <Column field="verification_status" header="Verified">
              <template #body="{ data }">
                <i :class="['pi', data.verification_status === 'certified' ? 'pi-check-circle text-green-500' : 'pi-circle text-slate-400']"></i>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>

      <!-- Skill Gaps Tab -->
      <template v-else-if="activeTab === 'gaps'">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
          <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-semibold text-slate-900 dark:text-white">Identified Skill Gaps</h3>
          </div>
          
          <DataTable :value="skillGaps" class="text-sm">
            <Column field="skill_name" header="Skill" />
            <Column field="current_level" header="Current Level">
              <template #body="{ data }">
                <Badge :value="data.current_level" :severity="getProficiencyColor(data.current_level)" class="capitalize" />
              </template>
            </Column>
            <Column field="required_level" header="Required Level">
              <template #body="{ data }">
                <Badge :value="data.required_level" :severity="getProficiencyColor(data.required_level)" class="capitalize" />
              </template>
            </Column>
            <Column field="priority" header="Priority">
              <template #body="{ data }">
                <Badge :value="data.priority" :severity="getPriorityColor(data.priority)" class="capitalize" />
              </template>
            </Column>
            <Column field="gap_score" header="Gap Score">
              <template #body="{ data }">
                <ProgressBar :value="data.gap_score" :severity="getPriorityColor(data.priority)" />
              </template>
            </Column>
          </DataTable>
        </div>
      </template>

      <!-- Courses Tab -->
      <template v-else-if="activeTab === 'courses'">
        <div class="space-y-6">
          <!-- Filters -->
          <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="grid md:grid-cols-4 gap-4">
              <Select v-model="courseFilters.category" :options="skillCategories" optionLabel="label" optionValue="value" 
                placeholder="Category" @change="loadCourses" />
              <Select v-model="courseFilters.difficulty_level" :options="proficiencyLevels" optionLabel="label" optionValue="value" 
                placeholder="Difficulty" @change="loadCourses" />
              <Select v-model="courseFilters.offers_certificate" :options="[
                { label: 'With Certificate', value: true },
                { label: 'No Certificate', value: false }
              ]" optionLabel="label" optionValue="value" placeholder="Certificate" @change="loadCourses" />
              <InputText v-model="courseFilters.search" placeholder="Search courses..." @input="loadCourses" />
            </div>
          </div>

          <!-- Courses Grid -->
          <div v-if="courses.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="course in courses" :key="course.id" 
              class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
              <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                  <h4 class="font-semibold text-slate-900 dark:text-white">{{ course.title }}</h4>
                  <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ course.provider_name }}</p>
                </div>
                <Badge v-if="course.offers_certificate" value="Certificate" severity="success" />
              </div>
              
              <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 line-clamp-3">{{ course.description }}</p>
              
              <div class="flex items-center justify-between text-sm text-slate-500 dark:text-slate-400 mb-4">
                <span>{{ course.duration_hours }}h</span>
                <span class="capitalize">{{ course.difficulty_level }}</span>
                <span v-if="course.price">{{ course.currency }} {{ course.price }}</span>
                <span v-else>Free</span>
              </div>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-1">
                  <i class="pi pi-star-fill text-yellow-400"></i>
                  <span class="text-sm">{{ course.rating || 'N/A' }}</span>
                </div>
                <Button label="Enroll" size="small" @click="enrollInCourse(course.id)" />
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <i class="pi pi-book text-4xl text-slate-400 mb-4"></i>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No courses found</h3>
            <p class="text-slate-600 dark:text-slate-400">Try adjusting your filters</p>
          </div>
        </div>
      </template>

      <!-- Recommendations Tab -->
      <template v-else>
        <div class="space-y-4">
          <div v-for="rec in recommendations" :key="rec.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <Badge :value="rec.recommendation_type.replace('_', ' ')" class="capitalize" />
                  <span class="text-sm text-slate-500 dark:text-slate-400">{{ rec.relevance_score }}% match</span>
                </div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ rec.course_title || rec.skill_name }}</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ rec.reason }}</p>
              </div>
              <div class="flex gap-2">
                <Button label="View" size="small" text />
                <Button label="Enroll" size="small" v-if="rec.course_id" @click="enrollInCourse(rec.course_id)" />
              </div>
            </div>
          </div>
          
          <div v-if="!recommendations.length" class="text-center py-12">
            <i class="pi pi-lightbulb text-4xl text-slate-400 mb-4"></i>
            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No recommendations yet</h3>
            <p class="text-slate-600 dark:text-slate-400">Complete your profile to get personalized recommendations</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Add Skill Dialog -->
    <Dialog v-model:visible="skillDialog" header="Add New Skill" :style="{ width: '500px' }" modal>
      <div class="space-y-4 pt-2">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Skill</label>
          <Select v-model="newSkill.skill_id" :options="skillsCatalog" optionLabel="name" optionValue="id" 
            class="w-full mt-1.5" placeholder="Select a skill" />
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Proficiency Level</label>
          <Select v-model="newSkill.proficiency_level" :options="proficiencyLevels" optionLabel="label" optionValue="value" 
            class="w-full mt-1.5" />
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Years of Experience</label>
          <InputText v-model="newSkill.years_experience" type="number" min="0" max="50" class="w-full mt-1.5" />
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notes</label>
          <Textarea v-model="newSkill.notes" rows="3" class="w-full mt-1.5" placeholder="Additional details..." />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="skillDialog = false" />
          <Button label="Add Skill" @click="addSkill" />
        </div>
      </div>
    </Dialog>

    <!-- Skill Gap Analysis Dialog -->
    <Dialog v-model:visible="gapAnalysisDialog" header="Skill Gap Analysis" :style="{ width: '600px' }" modal>
      <div class="space-y-4 pt-2">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Target Role</label>
          <InputText v-model="gapAnalysisData.target_role_title" class="w-full mt-1.5" 
            placeholder="e.g. Senior Nurse Manager" />
        </div>

        <div v-if="gapAnalysisData.gaps.length" class="mt-6">
          <h4 class="font-medium text-slate-900 dark:text-white mb-3">Identified Gaps</h4>
          <div class="space-y-2">
            <div v-for="gap in gapAnalysisData.gaps" :key="gap.skill_id"
              class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
              <div>
                <span class="font-medium text-slate-900 dark:text-white">{{ gap.skill_name }}</span>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                  {{ gap.current_level }} → {{ gap.required_level }}
                </p>
              </div>
              <Badge :value="gap.priority" :severity="getPriorityColor(gap.priority)" class="capitalize" />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="gapAnalysisDialog = false" />
          <Button label="Analyze" @click="analyzeSkillGaps" />
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>