<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import AdminPagination from './AdminPagination.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const items = ref([]);
const meta = ref({});
const loading = ref(false);
const search = ref('');
const statusFilter = ref('pending');
const reasonFilter = ref('');
const page = ref(1);

const reviewDialog = ref(false);
const reviewTarget = ref(null);
const reviewStatus = ref('under_review');
const resolutionNotes = ref('');
const saving = ref(false);

// Moderation queue tab
const activeTab = ref('reports');
const queueItems = ref([]);
const queueMeta = ref({});
const queueLoading = ref(false);
const queuePage = ref(1);
const queueStatusFilter = ref('queued');

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Under Review', value: 'under_review' },
  { label: 'Action Taken', value: 'action_taken' },
  { label: 'Dismissed', value: 'dismissed' },
  { label: 'Escalated', value: 'escalated' },
  { label: 'All', value: '' },
];

const reasonOptions = [
  { label: 'All Reasons', value: '' },
  { label: 'Inappropriate', value: 'inappropriate' },
  { label: 'Spam', value: 'spam' },
  { label: 'Scam', value: 'scam' },
  { label: 'Harassment', value: 'harassment' },
  { label: 'Discrimination', value: 'discrimination' },
  { label: 'Fake', value: 'fake' },
  { label: 'Offensive', value: 'offensive' },
  { label: 'Other', value: 'other' },
];

const reviewStatusOptions = [
  { label: 'Under Review', value: 'under_review' },
  { label: 'Action Taken', value: 'action_taken' },
  { label: 'Dismissed', value: 'dismissed' },
  { label: 'Escalated', value: 'escalated' },
];

const statusSeverity = { pending: 'warn', under_review: 'info', action_taken: 'success', dismissed: 'secondary', escalated: 'danger' };
const reasonSeverity = { inappropriate: 'warn', spam: 'secondary', scam: 'danger', harassment: 'danger', discrimination: 'danger', fake: 'warn', offensive: 'warn', other: 'secondary' };
const prioritySeverity = { low: 'secondary', medium: 'warn', high: 'danger', urgent: 'danger' };

async function fetch(p = 1) {
  loading.value = true; page.value = p;
  try {
    const res = await api.get('/admin/trust/content-reports', {
      params: { page: p, status: statusFilter.value || undefined, reason: reasonFilter.value || undefined, q: search.value || undefined }
    });
    const d = res?.data?.data || res?.data;
    items.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

async function fetchQueue(p = 1) {
  queueLoading.value = true; queuePage.value = p;
  try {
    const res = await api.get('/admin/trust/moderation-queue', {
      params: { page: p, status: queueStatusFilter.value || undefined }
    });
    const d = res?.data?.data || res?.data;
    queueItems.value = d?.data || d || [];
    queueMeta.value = { total: d?.total, last_page: d?.last_page };
  } finally { queueLoading.value = false; }
}

function openReview(item) {
  reviewTarget.value = item;
  reviewStatus.value = 'under_review';
  resolutionNotes.value = item.resolution_notes || '';
  reviewDialog.value = true;
}

async function submitReview() {
  saving.value = true;
  try {
    const endpoint = activeTab.value === 'reports'
      ? `/admin/trust/content-reports/${reviewTarget.value.id}`
      : `/admin/trust/moderation-queue/${reviewTarget.value.id}`;
    const payload = activeTab.value === 'reports'
      ? { status: reviewStatus.value, resolution_notes: resolutionNotes.value }
      : { status: reviewStatus.value, moderator_notes: resolutionNotes.value };
    await api.patch(endpoint, payload);
    toast.add({ severity: 'success', summary: 'Done', detail: 'Updated', life: 2000 });
    reviewDialog.value = false;
    activeTab.value === 'reports' ? fetch(page.value) : fetchQueue(queuePage.value);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'queue' && !queueItems.value.length) fetchQueue(1);
}

onMounted(() => {
  setBreadcrumb([{ label: 'Trust & Safety', to: '/admin/trust' }, { label: 'Content Reports' }]);
  fetch();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Content Reports &amp; Moderation</h1>
        <p :class="['text-sm mt-1', textSub]">Review reported content and manage the moderation queue</p>
      </div>

      <!-- Tabs -->
      <div :class="['flex gap-1 p-1 rounded-xl w-fit', isDark ? 'bg-slate-800' : 'bg-slate-100']">
        <button v-for="tab in [{ key: 'reports', label: 'Content Reports' }, { key: 'queue', label: 'Moderation Queue' }]" :key="tab.key"
          :class="['px-4 py-2 rounded-lg text-sm font-medium transition', activeTab === tab.key
            ? (isDark ? 'bg-slate-700 text-white' : 'bg-white text-slate-900 shadow-sm')
            : (isDark ? 'text-slate-400 hover:text-white' : 'text-slate-500 hover:text-slate-900')]"
          @click="switchTab(tab.key)">
          {{ tab.label }}
        </button>
      </div>

      <!-- Content Reports Tab -->
      <template v-if="activeTab === 'reports'">
        <div class="flex flex-wrap gap-3">
          <InputText v-model="search" placeholder="Search email or reason…" :class="['text-sm', input]" @keyup.enter="fetch(1)" />
          <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" :class="['text-sm', input]" @change="fetch(1)" />
          <Select v-model="reasonFilter" :options="reasonOptions" optionLabel="label" optionValue="value" :class="['text-sm', input]" @change="fetch(1)" />
          <Button icon="pi pi-search" label="Search" size="small" @click="fetch(1)" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Reporter</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Content</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Reason</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Severity</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Date</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="loading"><td colspan="8" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!items.length"><td colspan="8" :class="['px-5 py-10 text-center text-sm', textMuted]">No reports found</td></tr>
              <tr v-else v-for="item in items" :key="item.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ item.id }}</td>
                <td :class="['px-5 py-3.5 text-xs', text]">{{ item.reporter_email }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ item.reportable_type?.split('\\').pop() }} #{{ item.reportable_id }}</td>
                <td class="px-5 py-3.5"><Tag :value="item.reason" :severity="reasonSeverity[item.reason] || 'secondary'" class="text-xs" /></td>
                <td class="px-5 py-3.5"><Tag :value="item.severity" :severity="prioritySeverity[item.severity] || 'secondary'" class="text-xs" /></td>
                <td class="px-5 py-3.5"><Tag :value="item.status?.replace(/_/g, ' ')" :severity="statusSeverity[item.status] || 'secondary'" class="text-xs" /></td>
                <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '—' }}</td>
                <td class="px-5 py-3.5">
                  <Button label="Review" size="small" @click="openReview(item)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetch" />
      </template>

      <!-- Moderation Queue Tab -->
      <template v-else>
        <div class="flex flex-wrap gap-3">
          <Select v-model="queueStatusFilter" :options="[
            { label: 'Queued', value: 'queued' },
            { label: 'In Review', value: 'in_review' },
            { label: 'Approved', value: 'approved' },
            { label: 'Rejected', value: 'rejected' },
            { label: 'All', value: '' },
          ]" optionLabel="label" optionValue="value" :class="['text-sm', input]" @change="fetchQueue(1)" />
          <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" @click="fetchQueue(1)" />
        </div>

        <div :class="['rounded-2xl border overflow-hidden', card, border]">
          <table class="w-full text-sm">
            <thead>
              <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Content</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Flag Reason</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Priority</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Date</th>
                <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
              </tr>
            </thead>
            <tbody :class="['divide-y', divider]">
              <tr v-if="queueLoading"><td colspan="7" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td></tr>
              <tr v-else-if="!queueItems.length"><td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">Queue is empty</td></tr>
              <tr v-else v-for="item in queueItems" :key="item.id"
                :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
                <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ item.id }}</td>
                <td :class="['px-5 py-3.5 text-xs', textSub]">{{ item.moderable_type?.split('\\').pop() }} #{{ item.moderable_id }}</td>
                <td :class="['px-5 py-3.5 text-xs capitalize', textSub]">{{ item.flag_reason?.replace(/_/g, ' ') }}</td>
                <td class="px-5 py-3.5"><Tag :value="item.priority" :severity="prioritySeverity[item.priority] || 'secondary'" class="text-xs" /></td>
                <td class="px-5 py-3.5"><Tag :value="item.status?.replace(/_/g, ' ')" :severity="statusSeverity[item.status] || 'secondary'" class="text-xs" /></td>
                <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '—' }}</td>
                <td class="px-5 py-3.5">
                  <Button label="Review" size="small" @click="openReview(item)" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <AdminPagination :page="queuePage" :last-page="queueMeta.last_page || 1" :total="queueMeta.total" @change="fetchQueue" />
      </template>
    </div>

    <!-- Review Dialog -->
    <Dialog v-model:visible="reviewDialog" header="Review Report" :style="{ width: '460px' }" modal>
      <div v-if="reviewTarget" class="space-y-4 pt-2">
        <div :class="['text-sm p-3 rounded-lg', isDark ? 'bg-slate-800' : 'bg-slate-50']">
          <div :class="['text-xs font-semibold uppercase mb-1', textMuted]">Content</div>
          <div :class="['text-sm', text]">
            {{ (reviewTarget.reportable_type || reviewTarget.moderable_type)?.split('\\').pop() }}
            #{{ reviewTarget.reportable_id || reviewTarget.moderable_id }}
          </div>
          <div v-if="reviewTarget.description || reviewTarget.description" :class="['text-xs mt-1', textSub]">{{ reviewTarget.description }}</div>
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Update Status</label>
          <Select v-model="reviewStatus" :options="reviewStatusOptions" optionLabel="label" optionValue="value" class="w-full mt-1.5" />
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Notes</label>
          <Textarea v-model="resolutionNotes" rows="3" class="w-full mt-1.5" placeholder="Add resolution notes…" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="reviewDialog = false" />
          <Button label="Save" :loading="saving" @click="submitReview" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
