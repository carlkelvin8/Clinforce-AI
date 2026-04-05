<script setup>
import { ref, onMounted, inject } from 'vue';
import AdminLayout from './AdminLayout.vue';
import api from '@/lib/api';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import { useAdminTheme } from '@/composables/useAdminTheme';

import AdminPagination from './AdminPagination.vue';

const { isDark, card, text, textSub, textMuted, divider, border, input, thead } = useAdminTheme();
const setBreadcrumb = inject('setBreadcrumb', () => {});

function maskName(name) {
  if (!name) return '—'
  const parts = String(name).trim().split(/[\s._-]+/).filter(Boolean)
  if (parts.length >= 2) return `${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}.`
  return name
}

const screenings = ref([]);
const meta = ref({});
const loading = ref(false);
const search = ref('');
const page = ref(1);
const viewDialog = ref(false);
const viewItem = ref(null);

onMounted(() => {
  setBreadcrumb([{ label: 'AI Screenings' }]);
  fetchScreenings();
});

async function fetchScreenings(p = 1) {
  loading.value = true;
  page.value = p;
  try {
    const res = await api.get('/admin/ai-screenings', { params: { q: search.value, page: p } });
    const d = res?.data?.data || res?.data;
    screenings.value = d?.data || d || [];
    meta.value = { total: d?.total, last_page: d?.last_page };
  } finally { loading.value = false; }
}

function scoreSeverity(score) {
  if (score >= 80) return 'success';
  if (score >= 50) return 'warn';
  return 'danger';
}

function openView(s) {
  viewItem.value = s;
  viewDialog.value = true;
}
</script>

<template>
  <AdminLayout>
    <div class="space-y-5">
      <div>
        <h1 :class="['text-2xl font-bold', text]">AI Screenings</h1>
        <p :class="['text-sm mt-1', textSub]">{{ meta.total ?? '—' }} total screenings</p>
      </div>

      <div class="flex gap-3">
        <InputText v-model="search" placeholder="Search by candidate email..." :class="['w-72', input]" @keydown.enter="fetchScreenings(1)" />
        <Button icon="pi pi-search" label="Search" size="small" @click="fetchScreenings(1)" />
      </div>

      <div :class="['rounded-2xl border overflow-hidden', card]">
        <table class="w-full text-sm">
          <thead>
            <tr :class="['border-b text-xs uppercase tracking-wider', border, thead]">
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">ID</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Candidate</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Job</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Score</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Date</th>
              <th :class="['px-5 py-3.5 text-left font-semibold', textMuted]">Actions</th>
            </tr>
          </thead>
          <tbody :class="['divide-y', divider]">
            <tr v-if="loading">
              <td colspan="6" class="px-5 py-10 text-center">
                <i :class="['pi pi-spin pi-spinner text-2xl', textMuted]"></i>
              </td>
            </tr>
            <tr v-else-if="!screenings.length">
              <td colspan="6" :class="['px-5 py-10 text-center text-sm', textMuted]">No screenings found</td>
            </tr>
            <tr v-else v-for="s in screenings" :key="s.id"
              :class="['transition-colors', isDark ? 'hover:bg-slate-800/50' : 'hover:bg-slate-50']">
              <td :class="['px-5 py-3.5 text-xs font-mono', textMuted]">#{{ s.id }}</td>
              <td :class="['px-5 py-3.5 text-sm', text]">{{ maskName(s.application?.user?.email?.split('@')[0] || s.application?.applicant?.applicant_profile?.first_name || '—') }}</td>
              <td :class="['px-5 py-3.5 text-xs max-w-[180px] truncate', textSub]">{{ s.application?.job?.title || '—' }}</td>
              <td class="px-5 py-3.5">
                <Tag v-if="s.score != null" :value="s.score + '%'" :severity="scoreSeverity(s.score)" class="text-xs" />
                <span v-else :class="['text-xs', textMuted]">—</span>
              </td>
              <td :class="['px-5 py-3.5 text-xs', textMuted]">{{ s.created_at ? new Date(s.created_at).toLocaleDateString() : '—' }}</td>
              <td class="px-5 py-3.5">
                <Button icon="pi pi-eye" size="small" severity="secondary" v-tooltip="'View result'" @click="openView(s)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <AdminPagination :page="page" :last-page="meta.last_page || 1" :total="meta.total" @change="fetchScreenings" />
    </div>

    <Dialog v-model:visible="viewDialog" header="AI Screening Result" :style="{ width: '560px' }" modal>
      <div v-if="viewItem" class="space-y-4 pt-2">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Candidate</p>
            <p :class="['text-sm', text]">{{ maskName(viewItem.application?.user?.email?.split('@')[0] || '—') }}</p>
          </div>
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Job</p>
            <p :class="['text-sm', text]">{{ viewItem.application?.job?.title || '—' }}</p>
          </div>
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Score</p>
            <Tag v-if="viewItem.score != null" :value="viewItem.score + '%'" :severity="scoreSeverity(viewItem.score)" />
            <span v-else :class="['text-sm', textMuted]">—</span>
          </div>
          <div>
            <p :class="['text-xs font-semibold uppercase tracking-wider mb-1', textMuted]">Date</p>
            <p :class="['text-sm', text]">{{ viewItem.created_at ? new Date(viewItem.created_at).toLocaleString() : '—' }}</p>
          </div>
        </div>
        <div v-if="viewItem.result || viewItem.summary">
          <p :class="['text-xs font-semibold uppercase tracking-wider mb-2', textMuted]">AI Summary</p>
          <div :class="['rounded-xl p-4 text-sm leading-relaxed whitespace-pre-wrap', isDark ? 'bg-slate-800 text-slate-300' : 'bg-slate-50 text-slate-700']">
            {{ viewItem.result || viewItem.summary }}
          </div>
        </div>
      </div>
    </Dialog>
  </AdminLayout>
</template>
