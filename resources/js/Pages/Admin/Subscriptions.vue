<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useAdminTheme } from '@/composables/useAdminTheme';
import Swal from 'sweetalert2';

import AdminPagination from './AdminPagination.vue';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const subs = ref([]);
const meta = ref({});
const loading = ref(false);
const statusFilter = ref('');
const searchEmail = ref('');
const page = ref(1);

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Trial', value: 'trial' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Expired', value: 'expired' },
];
const statusSeverity = { active: 'success', trial: 'info', cancelled: 'danger', expired: 'secondary' };

async function fetchSubs(p = 1) {
  loading.value = true; page.value = p;
  try {
    const res = await api.get('/admin/subscriptions', { params: { status: statusFilter.value, q: searchEmail.value, page: p } });
    const d = res?.data?.data || res?.data;
    subs.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

async function cancelSub(s) {
  const result = await Swal.fire({
    title: 'Cancel subscription?',
    text: `#${s.id} — ${s.user?.email}`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Cancel subscription',
    confirmButtonColor: '#ef4444',
    background: isDark.value ? '#1e293b' : '#fff',
    color: isDark.value ? '#f1f5f9' : '#0f172a',
  });
  if (!result.isConfirmed) return;
  try {
    await api.post(`/subscriptions/${s.id}/cancel`);
    s.status = 'cancelled';
    toast.add({ severity: 'success', summary: 'Cancelled', life: 2000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed', life: 3000 });
  }
}

const fmt = (cents, cur) => cents != null ? `${(cents / 100).toFixed(2)} ${cur || 'USD'}` : '—';
const fmtDate = d => d ? new Date(d).toLocaleDateString() : '—';

onMounted(() => {
  setBreadcrumb([{ label: 'Subscriptions' }]);
  fetchSubs();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Subscriptions</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total</p>
      </div>

      <div class="flex flex-wrap gap-3">
        <InputText v-model="searchEmail" placeholder="Search by email..." :class="['w-56', input]" @keydown.enter="fetchSubs(1)" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" :class="['w-40', input]" @change="fetchSubs(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetchSubs(1)" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">User</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Plan</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Amount</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Started</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Expires</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="8" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!subs.length">
              <td colspan="8" :class="['px-5 py-10 text-center text-sm', textMuted]">No subscriptions found</td>
            </tr>
            <tr v-else v-for="s in subs" :key="s.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ s.id }}</td>
              <td :class="['px-5 py-3.5 text-xs', text]">{{ s.user?.email || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs font-medium', text]">{{ s.plan?.name || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">{{ fmt(s.amount_cents, s.currency_code) }}</td>
              <td class="px-5 py-3.5">
                <Tag :value="s.status" :severity="statusSeverity[s.status] || 'secondary'" class="text-xs" />
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ fmtDate(s.start_at) }}</td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ fmtDate(s.end_at) }}</td>
              <td class="px-5 py-3.5">
                <Button v-if="s.status === 'active' || s.status === 'trial'"
                  icon="pi pi-times-circle" label="Cancel" size="small" severity="danger" @click="cancelSub(s)" />
                <span v-else :class="['text-xs', textMuted]">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchSubs" />
    </div>
  </AdminLayout>
</template>
