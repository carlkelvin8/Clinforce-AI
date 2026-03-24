<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { useAdminTheme } from '@/composables/useAdminTheme';

import AdminPagination from './AdminPagination.vue';

const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const logs = ref([]);
const meta = ref({});
const loading = ref(false);
const page = ref(1);
const search = ref('');
const filterEntity = ref('');
const filterActor = ref('');
const filterAction = ref('');
const expandedLog = ref(null);
const metaDialogOpen = ref(false);

const actionOptions = [
  { label: 'All Actions', value: '' },
  { label: 'login', value: 'login' },
  { label: 'logout', value: 'logout' },
  { label: 'register', value: 'register' },
  { label: 'job_created', value: 'job_created' },
  { label: 'job_published', value: 'job_published' },
  { label: 'subscription_created', value: 'subscription_created' },
  { label: 'trial_activated', value: 'trial_activated' },
  { label: 'password_reset', value: 'password_reset' },
];

const actionColor = {
  login: 'bg-blue-500/10 text-blue-500',
  logout: 'bg-slate-500/10 text-slate-400',
  register: 'bg-emerald-500/10 text-emerald-500',
  job_created: 'bg-cyan-500/10 text-cyan-500',
  job_published: 'bg-green-500/10 text-green-500',
  job_archived: 'bg-amber-500/10 text-amber-500',
  subscription_created: 'bg-violet-500/10 text-violet-500',
  trial_activated: 'bg-indigo-500/10 text-indigo-500',
  password_reset: 'bg-red-500/10 text-red-500',
};

onMounted(() => {
  setBreadcrumb([{ label: 'Audit Logs' }]);
  fetchLogs();
});

async function fetchLogs(p = 1) {
  loading.value = true;
  page.value = p;
  try {
    const params = { page: p };
    if (search.value)                        params.q = search.value;
    if (filterEntity.value)                  params.entity_type = filterEntity.value;
    if (filterActor.value?.trim())           params.actor_user_id = filterActor.value.trim();
    if (filterAction.value)                  params.action = filterAction.value;

    const res = await api.get('/audit-logs', { params });
    const d = res?.data?.data || res?.data;
    logs.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } catch (e) {
    console.error('Audit logs error:', e?.response?.data || e);
    logs.value = [];
  } finally {
    loading.value = false;
  }
}

function clearFilters() {
  search.value = '';
  filterEntity.value = '';
  filterActor.value = '';
  filterAction.value = '';
  fetchLogs(1);
}

function formatMeta(metadata) {
  if (!metadata) return null;
  try { return JSON.stringify(typeof metadata === 'string' ? JSON.parse(metadata) : metadata, null, 2); }
  catch { return String(metadata); }
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Audit Logs</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} entries</p>
      </div>

      <div class="flex flex-wrap gap-3">
        <InputText v-model="search" placeholder="Search action, entity, IP..." :class="['w-56', input]" @keydown.enter="fetchLogs(1)" />
        <InputText v-model="filterEntity" placeholder="Entity type" :class="['w-36', input]" @keydown.enter="fetchLogs(1)" />
        <InputText v-model="filterActor" placeholder="Actor user ID" :class="['w-32', input]" @keydown.enter="fetchLogs(1)" />
        <Select v-model="filterAction" :options="actionOptions" optionLabel="label" optionValue="value" :class="['w-48', input]" @change="fetchLogs(1)" />
        <Button icon="pi pi-search" label="Filter" size="small" @click="fetchLogs(1)" />
        <Button icon="pi pi-times" label="Clear" size="small" severity="secondary" @click="clearFilters" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actor</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Action</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Entity</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">IP</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Time</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Meta</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!logs.length">
              <td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No logs found</td>
            </tr>
            <tr v-else v-for="log in logs" :key="log.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ log.id }}</td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">{{ log.actor?.email || log.actor_user_id || 'system' }}</td>
              <td class="px-5 py-3.5">
                <span :class="['text-xs px-2 py-0.5 rounded-full font-mono font-medium', actionColor[log.action] || 'bg-slate-500/10 text-slate-400']">
                  {{ log.action }}
                </span>
              </td>
              <td :class="['px-5 py-3.5 text-xs', textSub]">
                {{ log.entity_type }} <span :class="textMuted">#{{ log.entity_id }}</span>
              </td>
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">{{ log.ip_address || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs whitespace-nowrap', textMuted]">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '—' }}</td>
              <td class="px-5 py-3.5">
                <button v-if="log.metadata" @click="expandedLog = log; metaDialogOpen = true"
                  class="text-xs text-blue-500 hover:text-blue-400 transition-colors flex items-center gap-1">
                  <i class="pi pi-code text-xs"></i> View
                </button>
                <span v-else :class="['text-xs', textMuted]">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchLogs" />
    </div>

    <Dialog v-model:visible="metaDialogOpen" header="Log Metadata" :style="{ width: '500px' }" modal @hide="expandedLog = null; metaDialogOpen = false">
      <div v-if="expandedLog" class="space-y-3 pt-2">
        <div :class="['text-xs', textMuted]">
          Action: <span :class="['font-mono', text]">{{ expandedLog.action }}</span> ·
          Entity: <span :class="text">{{ expandedLog.entity_type }} #{{ expandedLog.entity_id }}</span>
        </div>
        <pre class="bg-slate-900 rounded-xl p-4 text-xs text-emerald-400 font-mono overflow-auto max-h-64">{{ formatMeta(expandedLog.metadata) }}</pre>
      </div>
    </Dialog>
  </AdminLayout>
</template>
