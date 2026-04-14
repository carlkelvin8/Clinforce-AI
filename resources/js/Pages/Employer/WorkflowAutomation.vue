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
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';

const toast = useToast();
const { isDark } = useDarkMode();

const activeTab = ref('workflows');
const loading = ref(false);

// Workflows
const workflows = ref([]);
const workflowDialog = ref(false);
const newWorkflow = ref({
  name: '',
  description: '',
  scope: 'global',
  job_id: null,
  department: '',
  stages: [
    { name: 'applied', label: 'Applied', sla_hours: 24 },
    { name: 'screening', label: 'Screening', sla_hours: 48 },
    { name: 'interview', label: 'Interview', sla_hours: 72 },
    { name: 'offer', label: 'Offer', sla_hours: 24 },
    { name: 'hired', label: 'Hired', sla_hours: 0 }
  ],
  auto_advance_rules: {},
  approval_rules: {},
  sla_settings: {},
  is_default: false,
});

// SLA Violations
const slaViolations = ref([]);
const violationDialog = ref(false);
const selectedViolation = ref(null);
const resolutionNotes = ref('');

// Stage Transitions
const stageHistory = ref([]);
const historyDialog = ref(false);
const selectedApplication = ref(null);

const scopeOptions = [
  { label: 'Global (All Jobs)', value: 'global' },
  { label: 'Department', value: 'department' },
  { label: 'Specific Job', value: 'job' },
];

const stageOptions = [
  { label: 'Applied', value: 'applied' },
  { label: 'Screening', value: 'screening' },
  { label: 'Phone Screen', value: 'phone_screen' },
  { label: 'Interview', value: 'interview' },
  { label: 'Reference Check', value: 'reference_check' },
  { label: 'Background Check', value: 'background_check' },
  { label: 'Offer', value: 'offer' },
  { label: 'Hired', value: 'hired' },
  { label: 'Rejected', value: 'rejected' },
];

async function loadWorkflows() {
  loading.value = true;
  try {
    const res = await api.get('/workflow-automation/workflows');
    workflows.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function loadSlaViolations() {
  loading.value = true;
  try {
    const res = await api.get('/workflow-automation/sla-violations');
    slaViolations.value = res?.data?.data || res?.data || [];
  } finally { loading.value = false; }
}

async function createWorkflow() {
  try {
    // Build SLA settings from stages
    const slaSettings = {};
    newWorkflow.value.stages.forEach(stage => {
      if (stage.sla_hours > 0) {
        slaSettings[stage.name] = { hours: stage.sla_hours };
      }
    });
    
    const payload = {
      ...newWorkflow.value,
      sla_settings: slaSettings,
    };
    
    await api.post('/workflow-automation/workflows', payload);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Workflow created', life: 2000 });
    workflowDialog.value = false;
    resetWorkflowForm();
    loadWorkflows();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function deleteWorkflow(workflowId) {
  if (!confirm('Are you sure you want to delete this workflow?')) return;
  
  try {
    await api.delete(`/workflow-automation/workflows/${workflowId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Workflow deleted', life: 2000 });
    loadWorkflows();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function resolveSlaViolation() {
  if (!selectedViolation.value || !resolutionNotes.value.trim()) return;
  
  try {
    await api.post(`/workflow-automation/sla-violations/${selectedViolation.value.id}/resolve`, {
      resolution_notes: resolutionNotes.value
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'SLA violation resolved', life: 2000 });
    violationDialog.value = false;
    resolutionNotes.value = '';
    loadSlaViolations();
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

async function viewStageHistory(applicationId) {
  try {
    const res = await api.get(`/workflow-automation/applications/${applicationId}/history`);
    stageHistory.value = res?.data?.data || res?.data || [];
    historyDialog.value = true;
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load stage history', life: 3000 });
  }
}

function resetWorkflowForm() {
  newWorkflow.value = {
    name: '',
    description: '',
    scope: 'global',
    job_id: null,
    department: '',
    stages: [
      { name: 'applied', label: 'Applied', sla_hours: 24 },
      { name: 'screening', label: 'Screening', sla_hours: 48 },
      { name: 'interview', label: 'Interview', sla_hours: 72 },
      { name: 'offer', label: 'Offer', sla_hours: 24 },
      { name: 'hired', label: 'Hired', sla_hours: 0 }
    ],
    auto_advance_rules: {},
    approval_rules: {},
    sla_settings: {},
    is_default: false,
  };
}

function addStage() {
  newWorkflow.value.stages.push({
    name: '',
    label: '',
    sla_hours: 48
  });
}

function removeStage(index) {
  newWorkflow.value.stages.splice(index, 1);
}

function openViolationDialog(violation) {
  selectedViolation.value = violation;
  resolutionNotes.value = '';
  violationDialog.value = true;
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'workflows') loadWorkflows();
  if (tab === 'sla') loadSlaViolations();
}

const severityColors = {
  minor: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  major: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
  critical: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

onMounted(() => {
  loadWorkflows();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Workflow Automation</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Automate your hiring process with custom workflows and SLA tracking</p>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 p-1 rounded-xl w-fit bg-slate-100 dark:bg-slate-800">
        <button v-for="tab in [
          { key: 'workflows', label: 'Workflows', icon: 'pi-sitemap' },
          { key: 'sla', label: 'SLA Violations', icon: 'pi-exclamation-triangle' },
        ]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2', activeTab === tab.key
            ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white']"
          @click="switchTab(tab.key)">
          <i :class="['pi text-xs', tab.icon]"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Workflows Tab -->
      <template v-if="activeTab === 'workflows'">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">Hiring Workflows</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Create custom hiring pipelines with auto-advance rules and SLA tracking</p>
          </div>
          <Button label="Create Workflow" icon="pi pi-plus" @click="workflowDialog = true" />
        </div>

        <div v-if="loading" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="i in 3" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 h-48 animate-pulse"></div>
        </div>
        <div v-else-if="!workflows.length" class="text-center py-12">
          <i class="pi pi-sitemap text-4xl text-slate-400 mb-4"></i>
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No workflows yet</h3>
          <p class="text-slate-600 dark:text-slate-400 mb-4">Create your first hiring workflow to automate your process</p>
          <Button label="Create Workflow" icon="pi pi-plus" @click="workflowDialog = true" />
        </div>
        <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="workflow in workflows" :key="workflow.id" 
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h4 class="font-semibold text-slate-900 dark:text-white">{{ workflow.name }}</h4>
                <p v-if="workflow.description" class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ workflow.description }}</p>
              </div>
              <div class="flex gap-1">
                <span v-if="workflow.is_default" class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded font-medium">Default</span>
                <span v-if="!workflow.is_active" class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 text-xs rounded font-medium">Inactive</span>
              </div>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center gap-2 text-sm">
                <i class="pi pi-tag text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300 capitalize">{{ workflow.scope }}</span>
              </div>
              <div v-if="workflow.stages" class="flex items-center gap-2 text-sm">
                <i class="pi pi-list text-slate-400"></i>
                <span class="text-slate-600 dark:text-slate-300">{{ JSON.parse(workflow.stages).length }} stages</span>
              </div>
            </div>

            <div class="flex gap-2">
              <Button label="Edit" size="small" text />
              <Button label="Delete" size="small" text severity="danger" @click="deleteWorkflow(workflow.id)" />
            </div>
          </div>
        </div>
      </template>

      <!-- SLA Violations Tab -->
      <template v-else>
        <div class="flex justify-between items-center">
          <div>
            <h3 class="font-semibold text-slate-900 dark:text-white">SLA Violations</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Track and resolve hiring process delays</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="loadSlaViolations" />
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900">
              <tr>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Candidate</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Job</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Stage</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">SLA</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Breach</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Severity</th>
                <th class="px-6 py-3 text-left font-medium text-slate-500 dark:text-slate-400">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="7" class="px-6 py-10 text-center">
                  <i class="pi pi-spin pi-spinner text-2xl text-slate-400"></i>
                </td>
              </tr>
              <tr v-else-if="!slaViolations.length">
                <td colspan="7" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                  <i class="pi pi-check-circle text-2xl mb-2"></i>
                  <div>No SLA violations - great job!</div>
                </td>
              </tr>
              <tr v-else v-for="violation in slaViolations" :key="violation.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-3">
                  <div class="font-medium text-slate-900 dark:text-white">{{ violation.candidate_name }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ violation.candidate_email }}</div>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ violation.job_title }}</td>
                <td class="px-6 py-3">
                  <span class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs rounded capitalize">
                    {{ violation.stage_name.replace(/_/g, ' ') }}
                  </span>
                </td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">{{ violation.sla_hours }}h</td>
                <td class="px-6 py-3 text-slate-600 dark:text-slate-300">+{{ violation.breach_hours }}h</td>
                <td class="px-6 py-3">
                  <span :class="['px-2 py-1 rounded text-xs font-medium capitalize', severityColors[violation.severity] || severityColors.minor]">
                    {{ violation.severity }}
                  </span>
                </td>
                <td class="px-6 py-3">
                  <div class="flex gap-2">
                    <Button label="Resolve" size="small" text @click="openViolationDialog(violation)" />
                    <Button label="History" size="small" text @click="viewStageHistory(violation.application_id)" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Create Workflow Dialog -->
    <Dialog v-model:visible="workflowDialog" header="Create Hiring Workflow" :style="{ width: '700px' }" modal>
      <div class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Workflow Name</label>
            <InputText v-model="newWorkflow.name" class="w-full mt-1.5" placeholder="e.g. Standard Nursing Workflow" />
          </div>
          <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Scope</label>
            <Select v-model="newWorkflow.scope" :options="scopeOptions" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
          </div>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
          <Textarea v-model="newWorkflow.description" rows="2" class="w-full mt-1.5" placeholder="Describe this workflow..." />
        </div>

        <div v-if="newWorkflow.scope === 'department'">
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Department</label>
          <InputText v-model="newWorkflow.department" class="w-full mt-1.5" placeholder="e.g. Nursing, ICU, Emergency" />
        </div>

        <div>
          <div class="flex items-center justify-between mb-3">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Workflow Stages</label>
            <Button label="Add Stage" icon="pi pi-plus" size="small" text @click="addStage" />
          </div>
          
          <div class="space-y-3">
            <div v-for="(stage, index) in newWorkflow.stages" :key="index" 
              class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
              <div class="flex-1">
                <InputText v-model="stage.label" placeholder="Stage name" class="w-full" />
              </div>
              <div class="w-32">
                <InputNumber v-model="stage.sla_hours" placeholder="SLA Hours" class="w-full" :min="0" />
              </div>
              <Button icon="pi pi-trash" size="small" text severity="danger" @click="removeStage(index)" />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Checkbox v-model="newWorkflow.is_default" inputId="is_default" />
          <label for="is_default" class="text-sm text-slate-700 dark:text-slate-300">Set as default workflow</label>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button label="Cancel" severity="secondary" @click="workflowDialog = false" />
          <Button label="Create Workflow" @click="createWorkflow" />
        </div>
      </div>
    </Dialog>

    <!-- Resolve SLA Violation Dialog -->
    <Dialog v-model:visible="violationDialog" header="Resolve SLA Violation" :style="{ width: '500px' }" modal>
      <div v-if="selectedViolation" class="space-y-4 pt-2">
        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
          <h4 class="font-medium text-slate-900 dark:text-white mb-2">{{ selectedViolation.candidate_name }}</h4>
          <p class="text-sm text-slate-600 dark:text-slate-400 mb-1">{{ selectedViolation.job_title }}</p>
          <p class="text-sm text-slate-600 dark:text-slate-400">
            Stage: <span class="capitalize">{{ selectedViolation.stage_name.replace(/_/g, ' ') }}</span> 
            • Breach: {{ selectedViolation.breach_hours }} hours
          </p>
        </div>
        
        <div>
          <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Resolution Notes</label>
          <Textarea v-model="resolutionNotes" rows="4" class="w-full mt-1.5" 
            placeholder="Explain how this SLA violation was resolved..." />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="violationDialog = false" />
          <Button label="Resolve" @click="resolveSlaViolation" :disabled="!resolutionNotes.trim()" />
        </div>
      </div>
    </Dialog>

    <!-- Stage History Dialog -->
    <Dialog v-model:visible="historyDialog" header="Stage History" :style="{ width: '600px' }" modal>
      <div class="space-y-4 pt-2">
        <div v-if="!stageHistory.length" class="text-center py-8 text-slate-500 dark:text-slate-400">
          No stage history available
        </div>
        <div v-else class="space-y-3">
          <div v-for="transition in stageHistory" :key="transition.id"
            class="flex items-center gap-4 p-3 border border-slate-200 dark:border-slate-700 rounded-lg">
            <div class="flex-1">
              <div class="font-medium text-slate-900 dark:text-white capitalize">
                {{ transition.from_stage ? `${transition.from_stage} → ` : '' }}{{ transition.to_stage.replace(/_/g, ' ') }}
              </div>
              <div class="text-sm text-slate-600 dark:text-slate-400">
                {{ new Date(transition.entered_at).toLocaleString() }}
                <span v-if="transition.triggered_by_name"> • by {{ transition.triggered_by_name }}</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-slate-900 dark:text-white capitalize">{{ transition.trigger_type.replace(/_/g, ' ') }}</div>
              <div v-if="transition.time_in_stage_hours" class="text-xs text-slate-500 dark:text-slate-400">
                {{ transition.time_in_stage_hours }}h in stage
              </div>
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  </AppLayout>
</template>