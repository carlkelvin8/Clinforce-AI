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
const statusFilter = ref('');
const page = ref(1);

const reviewDialog = ref(false);
const reviewTarget = ref(null);
const reviewStatus = ref('verified');
const rejectionReason = ref('');
const saving = ref(false);

const detailDialog = ref(false);
const detailItem = ref(null);

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Expired', value: 'expired' },
];

const statusSeverity = { pending: 'warn', processing: 'info', verified: 'success', rejected: 'danger', expired: 'secondary' };

async function fetch(p = 1) {
  loading.value = true; page.value = p;
  try {
    const res = await api.get('/admin/trust/identity-verifications', {
      params: { page: p, status: statusFilter.value || undefined, q: search.value || undefined }
    });
    const d = res?.data?.data || res?.data;
    items.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

function openReview(item) {
  reviewTarget.value = item;
  reviewStatus.value = 'verified';
  rejectionReason.value = '';
  reviewDialog.value = true;
}

function openDetail(item) {
  detailItem.value = item;
  detailDialog.value = true;
}

async function submitReview() {
  saving.value = true;
  try {
    await api.patch(`/admin/trust/identity-verifications/${reviewTarget.value.id}`, {
      status: reviewStatus.value,
      rejection_reason: rejectionReason.value || undefined,
    });
    toast.add({ severity: 'success', summary: 'Done', detail: `Verification ${reviewStatus.value}`, life: 2000 });
    reviewDialog.value = false;
    fetch(page.value);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

onMounted(() => {
  setBreadcrumb([{ label: 'Trust & Safety', to: '/admin/trust' }, { label: 'Identity Verifications' }]);
  fetch();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Identity Verifications</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total submissions</p>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <InputText v-model="search" placeholder="Search email or name…" :class="['text-sm', input]" @keyup.enter="fetch(1)" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Status" :class="['text-sm', input]" @change="fetch(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetch(1)" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card, border]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">User</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Doc Type</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Extracted Name</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Confidence</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Submitted</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="8" class="px-5 py-10 text-center"><i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i></td>
            </tr>
            <tr v-else-if="!items.length">
              <td colspan="8" :class="['px-5 py-10 text-center text-sm', textMuted]">No verifications found</td>
            </tr>
            <tr v-else v-for="item in items" :key="item.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ item.id }}</td>
              <td :class="['px-5 py-3.5 text-xs', text]">{{ item.user_email }}</td>
              <td :class="['px-5 py-3.5 text-xs capitalize', textSub]">{{ item.document_type?.replace(/_/g, ' ') }}</td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">{{ item.extracted_name || '—' }}</td>
              <td class="px-5 py-3.5">
                <span v-if="item.confidence_score != null" :class="['text-xs font-semibold', item.confidence_score >= 0.8 ? 'text-green-500' : item.confidence_score >= 0.6 ? 'text-yellow-500' : 'text-red-500']">
                  {{ Math.round(item.confidence_score * 100) }}%
                </span>
                <span v-else :class="['text-xs', textMuted]">—</span>
              </td>
              <td class="px-5 py-3.5">
                <Tag :value="item.verification_status" :severity="statusSeverity[item.verification_status] || 'secondary'" class="text-xs" />
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ item.created_at ? new Date(item.created_at).toLocaleDateString() : '—' }}</td>
              <td class="px-5 py-3.5 flex gap-2">
                <Button icon="pi pi-eye" size="small" text @click="openDetail(item)" v-tooltip="'View details'" />
                <Button v-if="item.verification_status === 'pending' || item.verification_status === 'processing'"
                  label="Review" size="small" @click="openReview(item)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetch" />
    </div>

    <!-- Review Dialog -->
    <Dialog v-model:visible="reviewDialog" header="Review Identity Verification" :style="{ width: '440px' }" modal>
      <div v-if="reviewTarget" class="space-y-4 pt-2">
        <div :class="['text-sm', textSub]">
          User: <span :class="['font-medium', text]">{{ reviewTarget.user_email }}</span>
        </div>
        <div :class="['text-sm', textSub]">
          Document: <span :class="['font-medium capitalize', text]">{{ reviewTarget.document_type?.replace(/_/g, ' ') }}</span>
          <span v-if="reviewTarget.extracted_name"> — {{ reviewTarget.extracted_name }}</span>
        </div>
        <div v-if="reviewTarget.document_front_url" class="space-y-2">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Document Front</label>
          <a :href="reviewTarget.document_front_url" target="_blank" :class="['text-xs text-blue-500 underline block', ]">View Document</a>
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Decision</label>
          <div class="flex gap-2 mt-2">
            <button v-for="opt in ['verified', 'rejected']" :key="opt"
              :class="['px-4 py-2 rounded-lg text-sm font-medium border transition', reviewStatus === opt
                ? (opt === 'verified' ? 'bg-green-500 text-white border-green-500' : 'bg-red-500 text-white border-red-500')
                : (isDark ? 'border-slate-600 text-slate-300 hover:bg-slate-700' : 'border-slate-300 text-slate-600 hover:bg-slate-50')]"
              @click="reviewStatus = opt">
              {{ opt === 'verified' ? '✓ Verify' : '✗ Reject' }}
            </button>
          </div>
        </div>
        <div v-if="reviewStatus === 'rejected'">
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Rejection Reason</label>
          <Textarea v-model="rejectionReason" rows="3" class="w-full mt-1.5" placeholder="Explain why the document was rejected…" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="reviewDialog = false" />
          <Button :label="reviewStatus === 'verified' ? 'Verify' : 'Reject'"
            :severity="reviewStatus === 'verified' ? 'success' : 'danger'"
            :loading="saving" @click="submitReview" />
        </div>
      </div>
    </Dialog>

    <!-- Detail Dialog -->
    <Dialog v-model:visible="detailDialog" header="Verification Details" :style="{ width: '500px' }" modal>
      <div v-if="detailItem" class="space-y-3 pt-2 text-sm">
        <div class="grid grid-cols-2 gap-3">
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">User</span><div :class="['mt-1', text]">{{ detailItem.user_email }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Status</span><div class="mt-1"><Tag :value="detailItem.verification_status" :severity="statusSeverity[detailItem.verification_status]" class="text-xs" /></div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Doc Type</span><div :class="['mt-1 capitalize', text]">{{ detailItem.document_type?.replace(/_/g, ' ') }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Doc Number</span><div :class="['mt-1', text]">{{ detailItem.document_number || '—' }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Extracted Name</span><div :class="['mt-1', text]">{{ detailItem.extracted_name || '—' }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Confidence</span><div :class="['mt-1 font-semibold', detailItem.confidence_score >= 0.8 ? 'text-green-500' : 'text-yellow-500']">{{ detailItem.confidence_score != null ? Math.round(detailItem.confidence_score * 100) + '%' : '—' }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">IP Address</span><div :class="['mt-1 font-mono text-xs', text]">{{ detailItem.ip_address || '—' }}</div></div>
          <div><span :class="['text-xs font-semibold uppercase', textMuted]">Submitted</span><div :class="['mt-1', text]">{{ detailItem.created_at ? new Date(detailItem.created_at).toLocaleString() : '—' }}</div></div>
        </div>
        <div v-if="detailItem.rejection_reason">
          <span :class="['text-xs font-semibold uppercase', textMuted]">Rejection Reason</span>
          <div :class="['mt-1 text-red-500', text]">{{ detailItem.rejection_reason }}</div>
        </div>
        <div class="flex gap-3 pt-2">
          <a v-if="detailItem.document_front_url" :href="detailItem.document_front_url" target="_blank" class="text-xs text-blue-500 underline">View Front</a>
          <a v-if="detailItem.document_back_url" :href="detailItem.document_back_url" target="_blank" class="text-xs text-blue-500 underline">View Back</a>
          <a v-if="detailItem.video_selfie_url" :href="detailItem.video_selfie_url" target="_blank" class="text-xs text-blue-500 underline">View Selfie Video</a>
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
