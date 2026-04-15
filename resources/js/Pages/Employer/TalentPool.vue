<script setup>
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/Components/AppLayout.vue';
import api from '@/lib/api';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chart from 'primevue/chart';
import MultiSelect from 'primevue/multiselect';

const toast = useToast();
const loading = ref(false);
const pools = ref([]);
const selectedPool = ref(null);
const poolCandidates = ref([]);
const tags = ref([]);
const campaigns = ref([]);
const analytics = ref(null);

// Dialogs
const showPoolDialog = ref(false);
const showCandidateDialog = ref(false);
const showTagDialog = ref(false);
const showCampaignDialog = ref(false);
const showInteractionDialog = ref(false);

// Forms
const poolForm = ref({
  name: '',
  description: '',
  pool_type: 'general',
  target_skills: [],
  target_experience: []
});

const candidateForm = ref({
  candidate_id: null,
  notes: '',
  priority: 3
});

const tagForm = ref({
  name: '',
  color: '#3B82F6',
  category: null
});

const campaignForm = ref({
  name: '',
  description: '',
  trigger_type: 'manual',
  frequency_days: 30,
  max_touches: 5,
  target_pools: []
});

const interactionForm = ref({
  candidate_id: null,
  interaction_type: 'note',
  subject: '',
  notes: '',
  interaction_date: new Date().toISOString().split('T')[0]
});

const poolTypes = [
  { label: 'General', value: 'general' },
  { label: 'Future Role', value: 'future_role' },
  { label: 'Passive Candidates', value: 'passive' },
  { label: 'High Potential', value: 'high_potential' }
];

const priorityOptions = [
  { label: 'High', value: 1 },
  { label: 'Medium', value: 2 },
  { label: 'Low', value: 3 }
];

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Contacted', value: 'contacted' },
  { label: 'Engaged', value: 'engaged' },
  { label: 'Hired', value: 'hired' },
  { label: 'Removed', value: 'removed' }
];

const interactionTypes = [
  { label: 'Email', value: 'email' },
  { label: 'Call', value: 'call' },
  { label: 'Message', value: 'message' },
  { label: 'Meeting', value: 'meeting' },
  { label: 'Note', value: 'note' }
];

const tagCategories = [
  { label: 'Skill', value: 'skill' },
  { label: 'Experience', value: 'experience' },
  { label: 'Location', value: 'location' },
  { label: 'Industry', value: 'industry' }
];

onMounted(() => {
  loadPools();
  loadTags();
  loadCampaigns();
  loadAnalytics();
});

const loadPools = async () => {
  try {
    loading.value = true;
    const res = await api.get('/talent-pools');
    pools.value = res.data.data;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load talent pools', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const loadPoolDetails = async (poolId) => {
  try {
    loading.value = true;
    const res = await api.get(`/talent-pools/${poolId}`);
    selectedPool.value = res.data.data;
    poolCandidates.value = res.data.data.candidates || [];
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load pool details', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const loadTags = async () => {
  try {
    const res = await api.get('/talent-tags');
    tags.value = res.data.data;
  } catch (error) {
    console.error('Failed to load tags', error);
  }
};

const loadCampaigns = async () => {
  try {
    const res = await api.get('/nurture-campaigns');
    campaigns.value = res.data.data;
  } catch (error) {
    console.error('Failed to load campaigns', error);
  }
};

const loadAnalytics = async () => {
  try {
    const res = await api.get('/talent-pool/analytics?days=30');
    analytics.value = res.data.data;
  } catch (error) {
    console.error('Failed to load analytics', error);
  }
};

const createPool = async () => {
  try {
    loading.value = true;
    await api.post('/talent-pools', poolForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Talent pool created', life: 3000 });
    showPoolDialog.value = false;
    resetPoolForm();
    loadPools();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to create pool', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const deletePool = async (poolId) => {
  if (!confirm('Are you sure you want to delete this talent pool?')) return;
  
  try {
    await api.delete(`/talent-pools/${poolId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Pool deleted', life: 3000 });
    loadPools();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete pool', life: 3000 });
  }
};

const addCandidateToPool = async () => {
  try {
    loading.value = true;
    await api.post(`/talent-pools/${selectedPool.value.id}/candidates`, candidateForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Candidate added to pool', life: 3000 });
    showCandidateDialog.value = false;
    resetCandidateForm();
    loadPoolDetails(selectedPool.value.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to add candidate', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const updateCandidateStatus = async (candidate, newStatus) => {
  try {
    await api.put(`/talent-pools/${selectedPool.value.id}/candidates/${candidate.candidate_id}/status`, {
      status: newStatus,
      priority: candidate.priority
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Status updated', life: 3000 });
    loadPoolDetails(selectedPool.value.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update status', life: 3000 });
  }
};

const removeCandidateFromPool = async (candidateId) => {
  if (!confirm('Remove this candidate from the pool?')) return;
  
  try {
    await api.delete(`/talent-pools/${selectedPool.value.id}/candidates/${candidateId}`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Candidate removed', life: 3000 });
    loadPoolDetails(selectedPool.value.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to remove candidate', life: 3000 });
  }
};

const createTag = async () => {
  try {
    loading.value = true;
    await api.post('/talent-tags', tagForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Tag created', life: 3000 });
    showTagDialog.value = false;
    resetTagForm();
    loadTags();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to create tag', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const createCampaign = async () => {
  try {
    loading.value = true;
    await api.post('/nurture-campaigns', campaignForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Campaign created', life: 3000 });
    showCampaignDialog.value = false;
    resetCampaignForm();
    loadCampaigns();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to create campaign', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const logInteraction = async () => {
  try {
    loading.value = true;
    await api.post('/candidate-interactions', interactionForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Interaction logged', life: 3000 });
    showInteractionDialog.value = false;
    resetInteractionForm();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to log interaction', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const resetPoolForm = () => {
  poolForm.value = {
    name: '',
    description: '',
    pool_type: 'general',
    target_skills: [],
    target_experience: []
  };
};

const resetCandidateForm = () => {
  candidateForm.value = {
    candidate_id: null,
    notes: '',
    priority: 3
  };
};

const resetTagForm = () => {
  tagForm.value = {
    name: '',
    color: '#3B82F6',
    category: null
  };
};

const resetCampaignForm = () => {
  campaignForm.value = {
    name: '',
    description: '',
    trigger_type: 'manual',
    frequency_days: 30,
    max_touches: 5,
    target_pools: []
  };
};

const resetInteractionForm = () => {
  interactionForm.value = {
    candidate_id: null,
    interaction_type: 'note',
    subject: '',
    notes: '',
    interaction_date: new Date().toISOString().split('T')[0]
  };
};

const getPriorityLabel = (priority) => {
  const option = priorityOptions.find(o => o.value === priority);
  return option ? option.label : 'Unknown';
};

const getPrioritySeverity = (priority) => {
  if (priority === 1) return 'danger';
  if (priority === 2) return 'warning';
  return 'info';
};

const getStatusSeverity = (status) => {
  const map = {
    active: 'info',
    contacted: 'warning',
    engaged: 'success',
    hired: 'success',
    removed: 'danger'
  };
  return map[status] || 'info';
};

const chartData = computed(() => {
  if (!analytics.value) return null;
  
  return {
    labels: analytics.value.candidates_by_status.map(s => s.status),
    datasets: [{
      data: analytics.value.candidates_by_status.map(s => s.count),
      backgroundColor: ['#3B82F6', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444']
    }]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
};

</script>

<template>
  <AppLayout>
    <div class="p-6 max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Talent Pool Management</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Build and nurture your talent pipeline</p>
        </div>
        <Button label="Create Pool" icon="pi pi-plus" @click="showPoolDialog = true" />
      </div>

      <TabView>
        <!-- Pools Tab -->
        <TabPanel header="Talent Pools">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <Card v-for="pool in pools" :key="pool.id" class="cursor-pointer hover:shadow-lg transition-shadow">
              <template #title>
                <div class="flex justify-between items-start">
                  <span class="text-lg">{{ pool.name }}</span>
                  <Tag :value="pool.pool_type" severity="info" />
                </div>
              </template>
              <template #content>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">{{ pool.description }}</p>
                <div class="flex justify-between items-center">
                  <span class="text-2xl font-bold text-blue-600">{{ pool.candidate_count }}</span>
                  <div class="space-x-2">
                    <Button icon="pi pi-eye" text rounded @click="loadPoolDetails(pool.id)" />
                    <Button icon="pi pi-trash" text rounded severity="danger" @click="deletePool(pool.id)" />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Pool Details -->
          <Card v-if="selectedPool" class="mt-6">
            <template #title>
              <div class="flex justify-between items-center">
                <span>{{ selectedPool.name }} - Candidates</span>
                <Button label="Add Candidate" icon="pi pi-plus" size="small" @click="showCandidateDialog = true" />
              </div>
            </template>
            <template #content>
              <DataTable :value="poolCandidates" :loading="loading" paginator :rows="10">
                <Column field="public_display_name" header="Name">
                  <template #body="{ data }">
                    <div class="flex items-center gap-3">
                      <img v-if="data.avatar" :src="data.avatar" class="w-10 h-10 rounded-full" />
                      <div v-else class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                        {{ (data.first_name || data.public_display_name || 'U')[0] }}
                      </div>
                      <div>
                        <div class="font-semibold">{{ data.first_name }} {{ data.last_name }}</div>
                        <div class="text-sm text-gray-500">{{ data.email }}</div>
                      </div>
                    </div>
                  </template>
                </Column>
                <Column field="headline" header="Headline" />
                <Column field="location" header="Location" />
                <Column field="priority" header="Priority">
                  <template #body="{ data }">
                    <Tag :value="getPriorityLabel(data.priority)" :severity="getPrioritySeverity(data.priority)" />
                  </template>
                </Column>
                <Column field="status" header="Status">
                  <template #body="{ data }">
                    <Dropdown 
                      :modelValue="data.status" 
                      :options="statusOptions" 
                      optionLabel="label" 
                      optionValue="value"
                      @change="(e) => updateCandidateStatus(data, e.value)"
                      class="w-full"
                    />
                  </template>
                </Column>
                <Column field="tags" header="Tags">
                  <template #body="{ data }">
                    <div class="flex flex-wrap gap-1">
                      <Chip v-for="tag in data.tags" :key="tag.id" :label="tag.name" :style="{ backgroundColor: tag.color }" class="text-white text-xs" />
                    </div>
                  </template>
                </Column>
                <Column header="Actions">
                  <template #body="{ data }">
                    <div class="flex gap-2">
                      <Button icon="pi pi-comment" text rounded @click="interactionForm.candidate_id = data.candidate_id; showInteractionDialog = true" />
                      <Button icon="pi pi-trash" text rounded severity="danger" @click="removeCandidateFromPool(data.candidate_id)" />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- Tags Tab -->
        <TabPanel header="Tags">
          <div class="mb-4">
            <Button label="Create Tag" icon="pi pi-plus" @click="showTagDialog = true" />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <Chip 
              v-for="tag in tags" 
              :key="tag.id" 
              :label="tag.name" 
              :style="{ backgroundColor: tag.color }" 
              class="text-white"
            />
          </div>
        </TabPanel>

        <!-- Campaigns Tab -->
        <TabPanel header="Nurture Campaigns">
          <div class="mb-4">
            <Button label="Create Campaign" icon="pi pi-plus" @click="showCampaignDialog = true" />
          </div>
          <DataTable :value="campaigns" :loading="loading">
            <Column field="name" header="Campaign Name" />
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'info'" />
              </template>
            </Column>
            <Column field="trigger_type" header="Trigger" />
            <Column field="frequency_days" header="Frequency (days)" />
            <Column field="enrollment_count" header="Enrollments" />
            <Column field="step_count" header="Steps" />
            <Column header="Actions">
              <template #body="{ data }">
                <Button v-if="data.status === 'draft'" label="Activate" size="small" @click="activateCampaign(data.id)" />
                <Tag v-else value="Active" severity="success" />
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- Analytics Tab -->
        <TabPanel header="Analytics">
          <div v-if="analytics" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-blue-600">{{ analytics.total_candidates }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Total Candidates</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600">{{ analytics.active_pools }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Active Pools</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-purple-600">{{ analytics.engagement_rate }}%</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Engagement Rate</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-orange-600">{{ analytics.recent_interactions }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Recent Interactions</div>
                </div>
              </template>
            </Card>
          </div>

          <Card v-if="chartData" class="mb-6">
            <template #title>Candidates by Status</template>
            <template #content>
              <Chart type="doughnut" :data="chartData" :options="chartOptions" class="h-64" />
            </template>
          </Card>

          <Card v-if="analytics">
            <template #title>Pool Breakdown</template>
            <template #content>
              <DataTable :value="analytics.pool_breakdown">
                <Column field="name" header="Pool Name" />
                <Column field="pool_type" header="Type" />
                <Column field="candidate_count" header="Candidates" />
              </DataTable>
            </template>
          </Card>
        </TabPanel>
      </TabView>

      <!-- Create Pool Dialog -->
      <Dialog v-model:visible="showPoolDialog" header="Create Talent Pool" :modal="true" class="w-full max-w-2xl">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Pool Name</label>
            <InputText v-model="poolForm.name" class="w-full" placeholder="e.g., Senior Nurses" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <Textarea v-model="poolForm.description" class="w-full" rows="3" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Pool Type</label>
            <Dropdown v-model="poolForm.pool_type" :options="poolTypes" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showPoolDialog = false" />
          <Button label="Create" @click="createPool" :loading="loading" />
        </template>
      </Dialog>

      <!-- Add Candidate Dialog -->
      <Dialog v-model:visible="showCandidateDialog" header="Add Candidate to Pool" :modal="true" class="w-full max-w-lg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Candidate ID</label>
            <InputText v-model.number="candidateForm.candidate_id" class="w-full" type="number" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Priority</label>
            <Dropdown v-model="candidateForm.priority" :options="priorityOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Notes</label>
            <Textarea v-model="candidateForm.notes" class="w-full" rows="3" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showCandidateDialog = false" />
          <Button label="Add" @click="addCandidateToPool" :loading="loading" />
        </template>
      </Dialog>

      <!-- Create Tag Dialog -->
      <Dialog v-model:visible="showTagDialog" header="Create Tag" :modal="true" class="w-full max-w-lg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Tag Name</label>
            <InputText v-model="tagForm.name" class="w-full" placeholder="e.g., ICU Experience" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Color</label>
            <InputText v-model="tagForm.color" type="color" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Category</label>
            <Dropdown v-model="tagForm.category" :options="tagCategories" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showTagDialog = false" />
          <Button label="Create" @click="createTag" :loading="loading" />
        </template>
      </Dialog>

      <!-- Create Campaign Dialog -->
      <Dialog v-model:visible="showCampaignDialog" header="Create Nurture Campaign" :modal="true" class="w-full max-w-2xl">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Campaign Name</label>
            <InputText v-model="campaignForm.name" class="w-full" placeholder="e.g., Passive Candidate Outreach" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <Textarea v-model="campaignForm.description" class="w-full" rows="3" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Frequency (days)</label>
              <InputText v-model.number="campaignForm.frequency_days" type="number" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Max Touches</label>
              <InputText v-model.number="campaignForm.max_touches" type="number" class="w-full" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Target Pools</label>
            <MultiSelect v-model="campaignForm.target_pools" :options="pools" optionLabel="name" optionValue="id" class="w-full" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showCampaignDialog = false" />
          <Button label="Create" @click="createCampaign" :loading="loading" />
        </template>
      </Dialog>

      <!-- Log Interaction Dialog -->
      <Dialog v-model:visible="showInteractionDialog" header="Log Interaction" :modal="true" class="w-full max-w-lg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Interaction Type</label>
            <Dropdown v-model="interactionForm.interaction_type" :options="interactionTypes" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Subject</label>
            <InputText v-model="interactionForm.subject" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Notes</label>
            <Textarea v-model="interactionForm.notes" class="w-full" rows="4" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Date</label>
            <InputText v-model="interactionForm.interaction_date" type="date" class="w-full" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showInteractionDialog = false" />
          <Button label="Log" @click="logInteraction" :loading="loading" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>
