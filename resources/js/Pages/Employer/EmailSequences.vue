<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Chart from 'primevue/chart';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const activeTab = ref('sequences');
const loading = ref(false);

// Email Sequences
const sequences = ref([]);
const sequenceDialog = ref(false);
const newSequence = ref({
  name: '',
  description: '',
  type: 'welcome',
  trigger_event: 'application_submitted',
  trigger_conditions: {},
  target_audience: {},
});

// Sequence Steps
const selectedSequence = ref(null);
const steps = ref([]);
const stepDialog = ref(false);
const newStep = ref({
  step_number: 1,
  subject: '',
  body_template: '',
  delay_hours: 0,
  send_conditions: {},
});

// Enrollments
const enrollments = ref([]);
const enrollmentDialog = ref(false);
const enrollmentData = ref({
  sequence_id: null,
  user_id: null,
  context_data: {},
});

// Analytics
const analytics = ref({});

const sequenceTypes = [
  { label: 'Welcome Series', value: 'welcome' },
  { label: 'Nurture Campaign', value: 'nurture' },
  { label: 'Re-engagement', value: 're_engagement' },
  { label: 'Follow-up', value: 'follow_up' },
  { label: 'Onboarding', value: 'onboarding' },
  { label: 'Rejection', value: 'rejection' },
];

const triggerEvents = [
  { label: 'Application Submitted', value: 'application_submitted' },
  { label: 'Profile Created', value: 'profile_created' },
  { label: 'Interview Completed', value: 'interview_completed' },
  { label: 'Offer Sent', value: 'offer_sent' },
  { label: 'Hired', value: 'hired' },
  { label: 'Inactive Period', value: 'inactive_period' },
  { label: 'Custom', value: 'custom' },
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

async function loadSequences() {
  loading.value = true;
  try {
    const res = await api.get('/email-sequences');
    sequences.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function loadSequenceSteps(sequenceId) {
  try {
    const res = await api.get(`/email-sequences/${sequenceId}/steps`);
    steps.value = res?.data?.data || res?.data || [];
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load steps', life: 3000 });
  }
}

async function loadEnrollments() {
  loading.value = true;
  try {
    const res = await api.get('/email-sequences/enrollments');
    enrollments.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function loadAnalytics(sequenceId) {
  try {
    const res = await api.get(`/email-sequences/${sequenceId}/analytics`);
    analytics.value = res?.data?.data || res?.data || {};
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load analytics', life: 3000 });
  }
}

async function createSequence() {
  try {
    await api.post('/email-sequences', newSequence.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Email sequence created', life: 2000 });
    sequenceDialog.value = false;
    resetSequenceForm();
    loadSequences();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function createStep() {
  if (!selectedSequence.value) return;
  
  try {
    await api.post(`/email-sequences/${selectedSequence.value.id}/steps`, newStep.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Email step created', life: 2000 });
    stepDialog.value = false;
    resetStepForm();
    loadSequenceSteps(selectedSequence.value.id);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function deleteSequence(sequenceId) {
  if (!confirm('Are you sure you want to delete this email sequence?')) return;
  
  try {
    await api.delete(`/email-sequences/${sequenceId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Email sequence deleted', life: 2000 });
    loadSequences();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function deleteStep(stepId) {
  if (!confirm('Are you sure you want to delete this email step?')) return;
  
  try {
    await api.delete(`/email-sequences/${selectedSequence.value.id}/steps/${stepId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Email step deleted', life: 2000 });
    loadSequenceSteps(selectedSequence.value.id);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function pauseEnrollment(enrollmentId) {
  try {
    await api.post(`/email-sequences/enrollments/${enrollmentId}/pause`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Enrollment paused', life: 2000 });
    loadEnrollments();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function resumeEnrollment(enrollmentId) {
  try {
    await api.post(`/email-sequences/enrollments/${enrollmentId}/resume`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Enrollment resumed', life: 2000 });
    loadEnrollments();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

function resetSequenceForm() {
  newSequence.value = {
    name: '',
    description: '',
    type: 'welcome',
    trigger_event: 'application_submitted',
    trigger_conditions: {},
    target_audience: {},
  };
}

function resetStepForm() {
  newStep.value = {
    step_number: steps.value.length + 1,
    subject: '',
    body_template: '',
    delay_hours: 0,
    send_conditions: {},
  };
}

function openSequenceSteps(sequence) {
  selectedSequence.value = sequence;
  loadSequenceSteps(sequence.id);
  loadAnalytics(sequence.id);
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'sequences') loadSequences();
  if (tab === 'enrollments') loadEnrollments();
}

const performanceChartData = computed(() => {
  if (!analytics.value.email_stats) return { labels: [], datasets: [] };
  
  const stats = analytics.value.email_stats;
  return {
    labels: ['Sent', 'Delivered', 'Opened', 'Clicked'],
    datasets: [{
      label: 'Email Performance',
      data: [
        stats.total_emails_sent || 0,
        stats.delivered_count || 0,
        stats.opened_count || 0,
        stats.clicked_count || 0,
      ],
      backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
    }],
  };
});

onMounted(() => {
  loadSequences();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Email Sequences</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Automate candidate communication with personalized email sequences</p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'sequences', label: 'Sequences', icon: 'pi-send' },
          { key: 'enrollments', label: 'Enrollments', icon: 'pi-users' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Sequences Tab -->
      <template v-if="activeTab === 'sequences'">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Email Sequences</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Create automated email campaigns for different stages of your hiring process</p>
          </div>
          <Button label="Create Sequence" icon="pi pi-plus" @click="sequenceDialog = true" />
        </div>

        <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 h-48 animate-pulse"></div>
        </div>
        <div v-else-if="!sequences.length" class="text-center py-12">
          <i class="pi pi-send text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No email sequences yet</h3>
          <p class="text-slate-600 dark:text-slate-400 mb-4">Create your first email sequence to automate candidate communication</p>
          <Button label="Create Sequence" icon="pi pi-plus" @click="sequenceDialog = true" />
        </div>
        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="sequence in sequences" :key="sequence.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow cursor-pointer"
            @click="openSequenceSteps(sequence)">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ sequence.name }}</h4>
                <p v-if="sequence.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ sequence.description }}</p>
              </div>
              <span v-if="!sequence.is_active" class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 text-xs rounded font-medium">Inactive</span>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-tag text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ sequence.type.replace(/_/g, ' ') }}</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-bolt text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ sequence.trigger_event.replace(/_/g, ' ') }}</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-list text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300">{{ sequence.steps_count || 0 }} emails</span>
              </div>
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-users text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300">{{ sequence.active_enrollments || 0 }} active</span>
              </div>
            </div>

            <div class="flex gap-2" @click.stop>
              <Button label="Edit" size="small" text />
              <Button label="Delete" size="small" text severity="danger" @click="deleteSequence(sequence.id)" />
            </div>
          </div>
        </div>
      </template>

      <!-- Enrollments Tab -->
      <template v-else>
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Email Enrollments</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Manage users enrolled in email sequences</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="loadEnrollments" />
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">User</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Sequence</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Status</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Progress</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Next Email</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="6" class="px-6 py-10 text-center">
                  <i class="pi pi-spin pi-spinner text-2xl text-slate-400"></i>
                </td>
              </tr>
              <tr v-else-if="!enrollments.length">
                <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                  No enrollments found
                </td>
              </tr>
              <tr v-else v-for="enrollment in enrollments" :key="enrollment.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-3">
                  <div class="font-medium text-slate-900 dark:text-white">{{ enrollment.user_name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ enrollment.user_email }}</div>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ enrollment.sequence_name }}</td>
                <td class="px-6 py-3">
                  <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', 
                    enrollment.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                    enrollment.status === 'paused' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                    enrollment.status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                    'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200']">
                    {{ enrollment.status }}
                  </span>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">Step {{ enrollment.current_step }}</td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">
                  {{ enrollment.next_email_at ? new Date(enrollment.next_email_at).toLocaleDateString() : '—' }}
                </td>
                <td class="px-6 py-3">
                  <div class="flex gap-2">
                    <Button v-if="enrollment.status === 'active'" label="Pause" size="small" text @click="pauseEnrollment(enrollment.id)" />
                    <Button v-if="enrollment.status === 'paused'" label="Resume" size="small" text @click="resumeEnrollment(enrollment.id)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Sequence Steps Dialog -->
    <Dialog v-model:visible="selectedSequence" :header="`${selectedSequence?.name} - Email Steps`" :style="{ width: '900px' }" modal>
      <div v-if="selectedSequence" class="space-y-6">
        <!-- Analytics -->
        <div v-if="analytics.email_stats" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.enrollment_stats?.total_enrollments || 0 }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Total Enrollments</div>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.email_stats?.total_emails_sent || 0 }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Emails Sent</div>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.open_rate || 0 }}%</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Open Rate</div>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ analytics.click_rate || 0 }}%</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Click Rate</div>
          </div>
        </div>

        <!-- Performance Chart -->
        <div v-if="analytics.email_stats" class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
          <h4 class="font-medium text-slate-900 dark:text-white mb-4">Email Performance</h4>
          <div class="h-64">
            <Chart type="bar" :data="performanceChartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Email Steps -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-slate-900 dark:text-white">Email Steps</h4>
            <Button label="Add Step" icon="pi pi-plus" size="small" @click="stepDialog = true" />
          </div>
          
          <div v-if="!steps.length" class="text-center py-8 text-slate-500 dark:text-slate-400">
            No email steps yet. Add your first email to get started.
          </div>
          <div v-else class="space-y-3">
            <div v-for="step in steps" :key="step.id" 
              class="flex items-center gap-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
              <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full flex items-center justify-center text-sm font-medium">
                {{ step.step_number }}
              </div>
              <div class="flex-1">
                <div class="font-medium text-slate-900 dark:text-white">{{ step.subject }}</div>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                  Delay: {{ step.delay_hours }}h
                  <span v-if="!step.is_active" class="ml-2 px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded">Inactive</span>
                </div>
              </div>
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" size="small" text />
                <Button icon="pi pi-trash" size="small" text severity="danger" @click="deleteStep(step.id)" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Create Sequence Dialog -->
    <Dialog v-model:visible="sequenceDialog" header="Create Email Sequence" :style="{ width: '600px' }" modal>
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Sequence Name</label>
            <InputText v-model="newSequence.name" class="w-full mt-1.5" placeholder="e.g. Welcome to ClinForce" />
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Type</label>
            <Select v-model="newSequence.type" :options="sequenceTypes" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
          <Textarea v-model="newSequence.description" rows="2" class="w-full mt-1.5" placeholder="Describe this email sequence..." />
        </div>

        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Trigger Event</label>
          <Select v-model="newSequence.trigger_event" :options="triggerEvents" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="sequenceDialog = false" />
          <Button label="Create Sequence" @click="createSequence" />
        </div>
      </div>
    </Dialog>

    <!-- Create Step Dialog -->
    <Dialog v-model:visible="stepDialog" header="Add Email Step" :style="{ width: '700px' }" modal>
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Step Number</label>
            <InputNumber v-model="newStep.step_number" class="w-full mt-1.5" :min="1" />
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Delay (Hours)</label>
            <InputNumber v-model="newStep.delay_hours" class="w-full mt-1.5" :min="0" />
          </div>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email Subject</label>
          <InputText v-model="newStep.subject" class="w-full mt-1.5" placeholder="e.g. Welcome to {{company_name}}!" />
        </div>

        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email Body</label>
          <Textarea v-model="newStep.body_template" rows="8" class="w-full mt-1.5" 
            placeholder="Hi {{user_name}},&#10;&#10;Welcome to our platform! We're excited to have you join us.&#10;&#10;Available placeholders: {{user_name}}, {{user_email}}, {{company_name}}, {{current_date}}" />
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="stepDialog = false" />
          <Button label="Add Step" @click="createStep" />
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>