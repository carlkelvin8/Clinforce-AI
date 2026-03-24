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

import AdminPagination from './AdminPagination.vue';

const toast = useToast();
const { isDark, card, text, textSub, textMuted, skeleton, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

const jobs = ref([]);
const meta = ref({});
const loading = ref(false);
const search = ref('');
const statusFilter = ref('');
const page = ref(1);

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Published', value: 'published' },
  { label: 'Draft', value: 'draft' },
  { label: 'Archived', value: 'archived' },
  { label: 'Closed', value: 'closed' },
];
const statusSeverity = { published: 'success', draft: 'secondary', archived: 'warn', closed: 'danger' };

async function fetchJobs(p = 1) {
  loading.value = true; page.value = p;
  try {
    const res = await api.get('/admin/jobs', { params: { q: search.value, status: statusFilter.value, page: p } });
    const d = res?.data?.data || res?.data;
    jobs.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

async function setStatus(job, status) {
  try {
    await api.patch(`/admin/jobs/${job.id}`, { status });
    job.status = status;
    toast.add({ severity: 'success', summary: 'Updated', detail: `Job set to ${status}`, life: 2000 });
  } catch { toast.add({ severity: 'error', summary: 'Error', detail: 'Failed', life: 3000 }); }
}

onMounted(() => {
  setBreadcrumb([{ label: 'Jobs' }]);
  fetchJobs();
});
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">Jobs</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total jobs</p>
      </div>

      <div class="flex flex-wrap gap-3">
        <InputText v-model="search" placeholder="Search title, company..." :class="['w-64', input]" @keydown.enter="fetchJobs(1)" />
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" :class="['w-40', input]" @change="fetchJobs(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetchJobs(1)" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Title</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Company</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Owner</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Status</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Posted</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!jobs.length">
              <td colspan="7" :class="['px-5 py-10 text-center text-sm', textMuted]">No jobs found</td>
            </tr>
            <tr v-else v-for="j in jobs" :key="j.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ j.id }}</td>
              <td :class="['px-5 py-3.5 font-medium max-w-[180px] truncate', text]">{{ j.title }}</td>
              <td :class="['px-5 py-3.5 text-xs max-w-[130px] truncate', textSub]">{{ j.company_name || '—' }}</td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ j.owner?.email || '—' }}</td>
              <td class="px-5 py-3.5">
                <Tag :value="j.status" :severity="statusSeverity[j.status] || 'secondary'" class="text-xs" />
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ j.created_at ? new Date(j.created_at).toLocaleDateString() : '—' }}</td>
              <td class="px-5 py-3.5">
                <div class="flex gap-1.5">
                  <Button v-if="j.status === 'draft'" label="Publish" size="small" severity="success" @click="setStatus(j, 'published')" />
                  <Button v-if="j.status === 'published'" label="Unpublish" size="small" severity="warn" @click="setStatus(j, 'draft')" />
                  <Button v-if="j.status !== 'archived'" icon="pi pi-trash" size="small" severity="danger" v-tooltip="'Archive'" @click="setStatus(j, 'archived')" />
                  <Button v-if="j.status === 'archived'" label="Restore" size="small" severity="secondary" @click="setStatus(j, 'draft')" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchJobs" />
    </div>
  </AdminLayout>
</template>
