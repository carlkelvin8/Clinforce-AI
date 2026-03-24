<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';

import AdminPagination from './AdminPagination.vue';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, divider, border, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const requests = ref([]);
const meta = ref({});
const loading = ref(false);
const page = ref(1);
const reviewDialog = ref(false);
const reviewTarget = ref(null);
const reviewNotes = ref('');
const saving = ref(false);

async function fetchRequests(p = 1) {
  loading.value = true; page.value = p;
  try {
    const res = await api.get('/verification-requests', { params: { page: p } });
    const d = res?.data?.data || res?.data;
    requests.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

function openReview(req) {
  reviewTarget.value = req;
  reviewNotes.value = req.notes || '';
  reviewDialog.value = true;
}

async function submitReview(status) {
  saving.value = true;
  try {
    await api.post(`/verification-requests/${reviewTarget.value.id}/review`, { status, notes: reviewNotes.value });
    toast.add({ severity: 'success', summary: 'Done', detail: `Request ${status}`, life: 2000 });
    reviewDialog.value = false;
    fetchRequests(page.value);
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  } finally { saving.value = false; }
}

const statusSeverity = { pending: 'warn', approved: 'success', rejected: 'danger' };

onMounted(() => {
  setBreadcrumb([{ label: 'Verifications' }]);
  fetchRequests();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Verifications</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total requests</p>
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">User</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Role</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Submitted</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="6" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!requests.length">
              <td colspan="6" :class="['px-5 py-10 text-center text-sm', textMuted]">No requests found</td>
            </tr>
            <tr v-else v-for="r in requests" :key="r.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ r.id }}</td>
              <td :class="['px-5 py-3.5 text-xs', text]">{{ r.user?.email || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">{{ r.role }}</td>
              <td class="px-5 py-3.5">
                <Tag :value="r.status" :severity="statusSeverity[r.status] || 'secondary'" class="text-xs" />
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ r.created_at ? new Date(r.created_at).toLocaleDateString() : '—' }}</td>
              <td class="px-5 py-3.5">
                <Button v-if="r.status === 'pending'" label="Review" size="small" icon="pi pi-eye" @click="openReview(r)" />
                <span v-else :class="['text-xs', textMuted]">Reviewed</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchRequests" />
    </div>

    <Dialog v-model:visible="reviewDialog" header="Review Verification" :style="{ width: '440px' }" modal>
      <div v-if="reviewTarget" class="space-y-4 pt-2">
        <div :class="['text-sm', textSub]">
          <span :class="['font-medium', text]">{{ reviewTarget.user?.email }}</span> — {{ reviewTarget.role }}
        </div>
        <div>
          <label :class="['text-xs font-semibold uppercase tracking-wide', textMuted]">Notes (optional)</label>
          <Textarea v-model="reviewNotes" rows="3" class="w-full mt-1.5" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Cancel" severity="secondary" @click="reviewDialog = false" />
          <Button label="Reject" severity="danger" :loading="saving" @click="submitReview('rejected')" />
          <Button label="Approve" severity="success" :loading="saving" @click="submitReview('approved')" />
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
