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
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Chart from 'primevue/chart';
import FileUpload from 'primevue/fileupload';
import InputNumber from 'primevue/inputnumber';

const toast = useToast();
const loading = ref(false);
const programs = ref([]);
const myReferrals = ref([]);
const allReferrals = ref([]);
const leaderboard = ref([]);
const pendingBonuses = ref([]);
const analytics = ref(null);

// Dialogs
const showProgramDialog = ref(false);
const showReferralDialog = ref(false);
const showStatusDialog = ref(false);
const showBonusDialog = ref(false);

// Forms
const programForm = ref({
  name: '',
  description: '',
  bonus_amount: 1000,
  bonus_currency: 'USD',
  bonus_type: 'fixed',
  days_until_eligible: 90,
  allow_external_referrals: true
});

const referralForm = ref({
  referral_program_id: null,
  job_id: null,
  candidate_first_name: '',
  candidate_last_name: '',
  candidate_email: '',
  candidate_phone: '',
  candidate_linkedin: '',
  relationship_description: '',
  why_good_fit: '',
  years_known: null,
  resume: null
});

const statusForm = ref({
  referral_id: null,
  status: '',
  notes: '',
  rejection_reason: ''
});

const bonusForm = ref({
  bonus_id: null,
  payment_method: 'payroll',
  payment_reference: ''
});

const bonusTypes = [
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Percentage', value: 'percentage' },
  { label: 'Tiered', value: 'tiered' }
];

const statusOptions = [
  { label: 'Submitted', value: 'submitted' },
  { label: 'Reviewed', value: 'reviewed' },
  { label: 'Interviewing', value: 'interviewing' },
  { label: 'Hired', value: 'hired' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Withdrawn', value: 'withdrawn' }
];

const paymentMethods = [
  { label: 'Payroll', value: 'payroll' },
  { label: 'Check', value: 'check' },
  { label: 'Direct Deposit', value: 'direct_deposit' },
  { label: 'Gift Card', value: 'gift_card' }
];

const leaderboardPeriod = ref('all_time');
const leaderboardPeriods = [
  { label: 'All Time', value: 'all_time' },
  { label: 'This Year', value: 'year' },
  { label: 'This Quarter', value: 'quarter' },
  { label: 'This Month', value: 'month' }
];

onMounted(() => {
  loadPrograms();
  loadMyReferrals();
  loadLeaderboard();
  loadAnalytics();
});

const loadPrograms = async () => {
  try {
    const res = await api.get('/referral-programs');
    programs.value = res.data.data;
    if (programs.value.length > 0) {
      referralForm.value.referral_program_id = programs.value[0].id;
    }
  } catch (error) {
    console.error('Failed to load programs', error);
  }
};

const loadMyReferrals = async () => {
  try {
    loading.value = true;
    const res = await api.get('/referrals/my');
    myReferrals.value = res.data.data;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load referrals', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const loadAllReferrals = async () => {
  try {
    loading.value = true;
    const res = await api.get('/referrals/all');
    allReferrals.value = res.data.data;
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load referrals', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const loadLeaderboard = async () => {
  try {
    const res = await api.get(`/referral-leaderboard?period=${leaderboardPeriod.value}&limit=10`);
    leaderboard.value = res.data.data;
  } catch (error) {
    console.error('Failed to load leaderboard', error);
  }
};

const loadPendingBonuses = async () => {
  try {
    const res = await api.get('/referral-bonuses/pending');
    pendingBonuses.value = res.data.data;
  } catch (error) {
    console.error('Failed to load bonuses', error);
  }
};

const loadAnalytics = async () => {
  try {
    const res = await api.get('/referral-analytics?days=30');
    analytics.value = res.data.data;
  } catch (error) {
    console.error('Failed to load analytics', error);
  }
};

const createProgram = async () => {
  try {
    loading.value = true;
    await api.post('/referral-programs', programForm.value);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Program created', life: 3000 });
    showProgramDialog.value = false;
    resetProgramForm();
    loadPrograms();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to create program', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const submitReferral = async () => {
  try {
    loading.value = true;
    
    const formData = new FormData();
    Object.keys(referralForm.value).forEach(key => {
      if (referralForm.value[key] !== null && referralForm.value[key] !== '') {
        formData.append(key, referralForm.value[key]);
      }
    });
    
    await api.post('/referrals', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    toast.add({ severity: 'success', summary: 'Success', detail: 'Referral submitted', life: 3000 });
    showReferralDialog.value = false;
    resetReferralForm();
    loadMyReferrals();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to submit referral', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const updateStatus = async () => {
  try {
    loading.value = true;
    await api.put(`/referrals/${statusForm.value.referral_id}/status`, {
      status: statusForm.value.status,
      notes: statusForm.value.notes,
      rejection_reason: statusForm.value.rejection_reason
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Status updated', life: 3000 });
    showStatusDialog.value = false;
    loadAllReferrals();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update status', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const approveBonus = async (referralId) => {
  if (!confirm('Approve bonus for this referral?')) return;
  
  try {
    await api.post(`/referrals/${referralId}/bonus/approve`);
    toast.add({ severity: 'success', summary: 'Success', detail: 'Bonus approved', life: 3000 });
    loadAllReferrals();
    loadPendingBonuses();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to approve bonus', life: 3000 });
  }
};

const markBonusPaid = async () => {
  try {
    loading.value = true;
    await api.post(`/referral-bonuses/${bonusForm.value.bonus_id}/paid`, {
      payment_method: bonusForm.value.payment_method,
      payment_reference: bonusForm.value.payment_reference
    });
    toast.add({ severity: 'success', summary: 'Success', detail: 'Bonus marked as paid', life: 3000 });
    showBonusDialog.value = false;
    loadPendingBonuses();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to mark bonus as paid', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const refreshLeaderboard = async () => {
  try {
    loading.value = true;
    await api.post('/referral-leaderboard/refresh');
    toast.add({ severity: 'success', summary: 'Success', detail: 'Leaderboard refreshed', life: 3000 });
    loadLeaderboard();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to refresh leaderboard', life: 3000 });
  } finally {
    loading.value = false;
  }
};

const openStatusDialog = (referral) => {
  statusForm.value.referral_id = referral.id;
  statusForm.value.status = referral.status;
  statusForm.value.notes = '';
  statusForm.value.rejection_reason = '';
  showStatusDialog.value = true;
};

const openBonusDialog = (bonus) => {
  bonusForm.value.bonus_id = bonus.id;
  bonusForm.value.payment_method = 'payroll';
  bonusForm.value.payment_reference = '';
  showBonusDialog.value = true;
};

const resetProgramForm = () => {
  programForm.value = {
    name: '',
    description: '',
    bonus_amount: 1000,
    bonus_currency: 'USD',
    bonus_type: 'fixed',
    days_until_eligible: 90,
    allow_external_referrals: true
  };
};

const resetReferralForm = () => {
  referralForm.value = {
    referral_program_id: programs.value.length > 0 ? programs.value[0].id : null,
    job_id: null,
    candidate_first_name: '',
    candidate_last_name: '',
    candidate_email: '',
    candidate_phone: '',
    candidate_linkedin: '',
    relationship_description: '',
    why_good_fit: '',
    years_known: null,
    resume: null
  };
};

const getStatusSeverity = (status) => {
  const map = {
    submitted: 'info',
    reviewed: 'warning',
    interviewing: 'warning',
    hired: 'success',
    rejected: 'danger',
    withdrawn: 'secondary'
  };
  return map[status] || 'info';
};

const getBonusStatusSeverity = (status) => {
  const map = {
    pending: 'warning',
    eligible: 'info',
    approved: 'success',
    paid: 'success',
    forfeited: 'danger'
  };
  return map[status] || 'info';
};

const chartData = computed(() => {
  if (!analytics.value) return null;
  
  return {
    labels: analytics.value.by_status.map(s => s.status),
    datasets: [{
      data: analytics.value.by_status.map(s => s.count),
      backgroundColor: ['#3B82F6', '#F59E0B', '#8B5CF6', '#10B981', '#EF4444', '#6B7280']
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
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Employee Referral Program</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">Leverage your team to find top talent</p>
        </div>
        <div class="flex gap-2">
          <Button label="Submit Referral" icon="pi pi-user-plus" @click="showReferralDialog = true" />
          <Button label="Create Program" icon="pi pi-plus" outlined @click="showProgramDialog = true" />
        </div>
      </div>

      <TabView>
        <!-- My Referrals Tab -->
        <TabPanel header="My Referrals">
          <DataTable :value="myReferrals" :loading="loading" paginator :rows="10">
            <Column field="candidate_first_name" header="Candidate">
              <template #body="{ data }">
                <div>
                  <div class="font-semibold">{{ data.candidate_first_name }} {{ data.candidate_last_name }}</div>
                  <div class="text-sm text-gray-500">{{ data.candidate_email }}</div>
                </div>
              </template>
            </Column>
            <Column field="job_title" header="Position" />
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">
                {{ new Date(data.submitted_at).toLocaleDateString() }}
              </template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column field="bonus_status" header="Bonus">
              <template #body="{ data }">
                <div>
                  <Tag :value="data.bonus_status" :severity="getBonusStatusSeverity(data.bonus_status)" />
                  <div v-if="data.bonus_amount" class="text-sm text-gray-600 mt-1">
                    {{ data.bonus_currency }} {{ data.bonus_amount }}
                  </div>
                </div>
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- All Referrals (Admin) Tab -->
        <TabPanel header="All Referrals" @click="loadAllReferrals">
          <DataTable :value="allReferrals" :loading="loading" paginator :rows="10">
            <Column field="referrer_email" header="Referrer" />
            <Column field="candidate_first_name" header="Candidate">
              <template #body="{ data }">
                <div>
                  <div class="font-semibold">{{ data.candidate_first_name }} {{ data.candidate_last_name }}</div>
                  <div class="text-sm text-gray-500">{{ data.candidate_email }}</div>
                </div>
              </template>
            </Column>
            <Column field="job_title" header="Position" />
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">
                {{ new Date(data.submitted_at).toLocaleDateString() }}
              </template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column field="bonus_status" header="Bonus">
              <template #body="{ data }">
                <Tag :value="data.bonus_status" :severity="getBonusStatusSeverity(data.bonus_status)" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button icon="pi pi-pencil" text rounded @click="openStatusDialog(data)" />
                  <Button 
                    v-if="data.status === 'hired' && data.bonus_status === 'eligible'" 
                    label="Approve Bonus" 
                    size="small" 
                    @click="approveBonus(data.id)" 
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </TabPanel>

        <!-- Leaderboard Tab -->
        <TabPanel header="Leaderboard">
          <div class="flex justify-between items-center mb-4">
            <Dropdown v-model="leaderboardPeriod" :options="leaderboardPeriods" optionLabel="label" optionValue="value" @change="loadLeaderboard" />
            <Button label="Refresh" icon="pi pi-refresh" outlined @click="refreshLeaderboard" :loading="loading" />
          </div>

          <div class="grid grid-cols-1 gap-4">
            <Card v-for="(entry, index) in leaderboard" :key="entry.id" class="hover:shadow-lg transition-shadow">
              <template #content>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="text-3xl font-bold text-blue-600">
                      #{{ entry.rank }}
                    </div>
                    <div class="flex items-center gap-3">
                      <img v-if="entry.avatar" :src="entry.avatar" class="w-12 h-12 rounded-full" />
                      <div v-else class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-xl">
                        {{ (entry.first_name || entry.email)[0] }}
                      </div>
                      <div>
                        <div class="font-semibold text-lg">{{ entry.first_name }} {{ entry.last_name }}</div>
                        <div class="text-sm text-gray-500">{{ entry.email }}</div>
                      </div>
                    </div>
                  </div>
                  <div class="grid grid-cols-4 gap-6 text-center">
                    <div>
                      <div class="text-2xl font-bold text-blue-600">{{ entry.total_referrals }}</div>
                      <div class="text-xs text-gray-500">Total Referrals</div>
                    </div>
                    <div>
                      <div class="text-2xl font-bold text-green-600">{{ entry.successful_hires }}</div>
                      <div class="text-xs text-gray-500">Hired</div>
                    </div>
                    <div>
                      <div class="text-2xl font-bold text-purple-600">{{ entry.success_rate }}%</div>
                      <div class="text-xs text-gray-500">Success Rate</div>
                    </div>
                    <div>
                      <div class="text-2xl font-bold text-orange-600">${{ entry.total_bonuses_earned }}</div>
                      <div class="text-xs text-gray-500">Bonuses Earned</div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Bonuses Tab -->
        <TabPanel header="Bonuses" @click="loadPendingBonuses">
          <DataTable :value="pendingBonuses" :loading="loading">
            <Column field="referrer_email" header="Referrer" />
            <Column field="candidate_first_name" header="Candidate">
              <template #body="{ data }">
                {{ data.candidate_first_name }} {{ data.candidate_last_name }}
              </template>
            </Column>
            <Column field="amount" header="Amount">
              <template #body="{ data }">
                {{ data.currency }} {{ data.amount }}
              </template>
            </Column>
            <Column field="scheduled_payment_date" header="Scheduled">
              <template #body="{ data }">
                {{ new Date(data.scheduled_payment_date).toLocaleDateString() }}
              </template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" severity="warning" />
              </template>
            </Column>
            <Column header="Actions">
              <template #body="{ data }">
                <Button label="Mark Paid" size="small" @click="openBonusDialog(data)" />
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
                  <div class="text-3xl font-bold text-blue-600">{{ analytics.total_referrals }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Total Referrals</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-green-600">{{ analytics.hired_count }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Hired</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-purple-600">{{ analytics.conversion_rate }}%</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Conversion Rate</div>
                </div>
              </template>
            </Card>
            <Card>
              <template #content>
                <div class="text-center">
                  <div class="text-3xl font-bold text-orange-600">${{ analytics.bonuses_paid }}</div>
                  <div class="text-gray-600 dark:text-gray-400 mt-2">Bonuses Paid</div>
                </div>
              </template>
            </Card>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Card v-if="chartData">
              <template #title>Referrals by Status</template>
              <template #content>
                <Chart type="doughnut" :data="chartData" :options="chartOptions" class="h-64" />
              </template>
            </Card>

            <Card v-if="analytics">
              <template #title>Top Referrers (Last 30 Days)</template>
              <template #content>
                <div class="space-y-3">
                  <div v-for="referrer in analytics.top_referrers" :key="referrer.email" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                    <span class="font-medium">{{ referrer.email }}</span>
                    <span class="text-blue-600 font-bold">{{ referrer.referral_count }} referrals</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- Programs Tab -->
        <TabPanel header="Programs">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Card v-for="program in programs" :key="program.id">
              <template #title>
                <div class="flex justify-between items-start">
                  <span>{{ program.name }}</span>
                  <Tag :value="program.is_active ? 'Active' : 'Inactive'" :severity="program.is_active ? 'success' : 'secondary'" />
                </div>
              </template>
              <template #content>
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ program.description }}</p>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-500">Bonus Amount:</span>
                    <span class="font-semibold">{{ program.bonus_currency }} {{ program.bonus_amount }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Days Until Eligible:</span>
                    <span class="font-semibold">{{ program.days_until_eligible }} days</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Total Referrals:</span>
                    <span class="font-semibold">{{ program.total_referrals }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">Successful Hires:</span>
                    <span class="font-semibold text-green-600">{{ program.successful_hires }}</span>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </TabPanel>
      </TabView>

      <!-- Create Program Dialog -->
      <Dialog v-model:visible="showProgramDialog" header="Create Referral Program" :modal="true" class="w-full max-w-2xl">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Program Name</label>
            <InputText v-model="programForm.name" class="w-full" placeholder="e.g., 2024 Referral Program" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Description</label>
            <Textarea v-model="programForm.description" class="w-full" rows="3" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Bonus Amount</label>
              <InputNumber v-model="programForm.bonus_amount" class="w-full" mode="currency" currency="USD" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Bonus Type</label>
              <Dropdown v-model="programForm.bonus_type" :options="bonusTypes" optionLabel="label" optionValue="value" class="w-full" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Days Until Eligible</label>
            <InputNumber v-model="programForm.days_until_eligible" class="w-full" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showProgramDialog = false" />
          <Button label="Create" @click="createProgram" :loading="loading" />
        </template>
      </Dialog>

      <!-- Submit Referral Dialog -->
      <Dialog v-model:visible="showReferralDialog" header="Submit Referral" :modal="true" class="w-full max-w-2xl">
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">First Name *</label>
              <InputText v-model="referralForm.candidate_first_name" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Last Name *</label>
              <InputText v-model="referralForm.candidate_last_name" class="w-full" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Email *</label>
              <InputText v-model="referralForm.candidate_email" type="email" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Phone</label>
              <InputText v-model="referralForm.candidate_phone" class="w-full" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">LinkedIn Profile</label>
            <InputText v-model="referralForm.candidate_linkedin" class="w-full" placeholder="https://linkedin.com/in/..." />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">How do you know this candidate?</label>
            <Textarea v-model="referralForm.relationship_description" class="w-full" rows="2" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Why are they a good fit?</label>
            <Textarea v-model="referralForm.why_good_fit" class="w-full" rows="3" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Years Known</label>
            <InputNumber v-model="referralForm.years_known" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Resume (PDF, DOC, DOCX)</label>
            <FileUpload mode="basic" accept=".pdf,.doc,.docx" :maxFileSize="5000000" @select="(e) => referralForm.resume = e.files[0]" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showReferralDialog = false" />
          <Button label="Submit" @click="submitReferral" :loading="loading" />
        </template>
      </Dialog>

      <!-- Update Status Dialog -->
      <Dialog v-model:visible="showStatusDialog" header="Update Referral Status" :modal="true" class="w-full max-w-lg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Status</label>
            <Dropdown v-model="statusForm.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Notes</label>
            <Textarea v-model="statusForm.notes" class="w-full" rows="3" />
          </div>
          <div v-if="statusForm.status === 'rejected'">
            <label class="block text-sm font-medium mb-2">Rejection Reason</label>
            <Textarea v-model="statusForm.rejection_reason" class="w-full" rows="2" />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showStatusDialog = false" />
          <Button label="Update" @click="updateStatus" :loading="loading" />
        </template>
      </Dialog>

      <!-- Mark Bonus Paid Dialog -->
      <Dialog v-model:visible="showBonusDialog" header="Mark Bonus as Paid" :modal="true" class="w-full max-w-lg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-2">Payment Method</label>
            <Dropdown v-model="bonusForm.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium mb-2">Payment Reference</label>
            <InputText v-model="bonusForm.payment_reference" class="w-full" placeholder="Check #, Transaction ID, etc." />
          </div>
        </div>
        <template #footer>
          <Button label="Cancel" text @click="showBonusDialog = false" />
          <Button label="Mark Paid" @click="markBonusPaid" :loading="loading" />
        </template>
      </Dialog>
    </div>
  </AppLayout>
</template>
